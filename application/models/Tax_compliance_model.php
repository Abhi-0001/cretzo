<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Statutory deduction engine for seller settlements: TDS under Income Tax s.194-O and
 * TCS under GST s.52.
 *
 * Everything here answers one question - "for THIS seller, on THIS sale, what must be
 * withheld?" - because neither statute has a single flat answer. The rate depends on facts
 * about the seller (entity class from their PAN, whether they hold a GSTIN) and about the
 * supply (intra-state or inter-state, and how much the seller has already sold this
 * financial year).
 *
 * The rules implemented, in the order they are applied:
 *
 *   TDS s.194-O, on the ex-GST taxable value
 *     - No PAN, or a PAN that fails the format check  -> 5%     (s.206AA)
 *     - PAN 4th letter P (individual) or H (HUF)      -> 0% while cumulative turnover for
 *       the financial year is <= Rs. 5,00,000; 0.1% on every sale after it is crossed
 *     - PAN 4th letter F (firm) / L (LLP) / C (company), and every other class
 *                                                    -> 0.1% from the first rupee, no
 *                                                       threshold at all
 *
 *   TCS GST s.52, on the ex-GST net taxable value
 *     - Seller registered under GST (GSTIN)          -> 0.5%, collected as IGST on an
 *       inter-state supply, or as CGST 0.25% + SGST 0.25% on an intra-state one
 *     - Seller on an Enrollment ID (unregistered)    -> nil, and the marketplace must not
 *       let them supply out of state at all (see is_interstate_supply_blocked())
 *
 * Base note: GST charged on the goods is EXCLUDED from both bases. The seller invoices and
 * remits that GST themselves, so it is never part of what the marketplace deducts against.
 *
 * Every rate is overridable from the admin settings screen; the statutory figures are the
 * defaults and are what a fresh install runs on.
 */
class Tax_compliance_model extends CI_Model
{
    /** PAN 4th character -> entity class. */
    private $pan_holder_types = [
        'P' => 'individual',
        'H' => 'huf',
        'F' => 'firm',
        'L' => 'llp',
        'C' => 'company',
        'A' => 'aop',
        'B' => 'boi',
        'T' => 'trust',
        'J' => 'artificial_juridical',
        'G' => 'government',
        'K' => 'krish',
    ];

    /**
     * Classes that get the s.194-O threshold exemption. It is written for an individual or
     * a HUF only - a firm, LLP or company is deducted from the first rupee.
     */
    private $threshold_classes = ['individual', 'huf'];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'function_helper']);
    }

    /* ------------------------------------------------------------------ settings ----- */

    /**
     * A statutory rate, from the admin settings screen if set there, else config/commission.php,
     * else the statutory default hard-coded here.
     *
     * The last fallback matters: an install whose settings blob predates the compliance
     * fields would otherwise read every rate as 0 and withhold nothing, which is exactly the
     * silent non-compliance this class exists to remove.
     */
    public function rate($key)
    {
        static $statutory = [
            'tcs_percent'          => 0.5,
            'tds_percent'          => 0.1,
            'tds_percent_no_pan'   => 5.0,
            'tds_threshold_amount' => 500000.0,
        ];

        $settings = get_settings('system_settings', true);
        if (isset($settings[$key]) && $settings[$key] !== '' && $settings[$key] !== null) {
            return (float) $settings[$key];
        }

        $this->config->load('commission', true);
        $configured = $this->config->item($key, 'commission');
        if ($configured !== null && $configured !== '') {
            return (float) $configured;
        }

        return isset($statutory[$key]) ? $statutory[$key] : 0.0;
    }

    /** Master switch. Turning it off withholds nothing, which is a deliberate admin choice. */
    public function deductions_enabled()
    {
        $settings = get_settings('system_settings', true);
        // Absent means "not configured yet". Defaulting that to ON is the compliant reading:
        // an install that has never visited the settings screen still deducts.
        return !isset($settings['statutory_deductions_enabled']) || (string) $settings['statutory_deductions_enabled'] === '1';
    }

    /* ---------------------------------------------------------------- identifiers ---- */

    /**
     * Validate a PAN and read the entity class out of it.
     *
     * Format is AAAAA9999A: five letters, four digits, one letter. The 4th letter is the
     * holder type, which is the whole reason this matters - it decides whether the
     * Rs. 5 lakh threshold applies.
     *
     * @return array{valid: bool, pan: string, holder_code: string, entity_class: string, threshold_applies: bool}
     */
    public function classify_pan($pan)
    {
        $pan = strtoupper(preg_replace('/\s+/', '', (string) $pan));

        if ($pan === '' || !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            return [
                'valid'             => false,
                'pan'               => $pan,
                'holder_code'       => '',
                'entity_class'      => 'no_pan',
                'threshold_applies' => false,
            ];
        }

        $code  = $pan[3];
        $class = isset($this->pan_holder_types[$code]) ? $this->pan_holder_types[$code] : 'other';

        return [
            'valid'             => true,
            'pan'               => $pan,
            'holder_code'       => $code,
            'entity_class'      => $class,
            'threshold_applies' => in_array($class, $this->threshold_classes, true),
        ];
    }

    /**
     * Is this a well-formed GSTIN? 15 characters: 2-digit state code, the 10-character PAN
     * of the holder, an entity number, 'Z', and a checksum character.
     *
     * A seller flagged registered but carrying a malformed GSTIN is treated as unregistered
     * rather than collected against - depositing TCS against an identifier that does not
     * exist is worse than not collecting it, because the seller can never claim the credit.
     */
    public function is_valid_gstin($gstin)
    {
        $gstin = strtoupper(preg_replace('/\s+/', '', (string) $gstin));
        return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin);
    }

    /* -------------------------------------------------------------------- seller ----- */

    /**
     * Everything about a seller that the two statutes turn on, in one read.
     */
    public function get_seller_tax_profile($seller_id)
    {
        $seller = $this->db
            ->select('sd.user_id, sd.entity_type, sd.pan, sd.pan_number, sd.gst, sd.tax_number,
                      sd.is_gst_registered, sd.gst_enrollment_number,
                      sd.business_state, sd.state, sd.pickup_state')
            ->where('sd.user_id', (int) $seller_id)
            ->get('seller_data sd')
            ->row_array();

        // `pan` is the field the seller registration form writes; `pan_number` is the older
        // column some rows still carry. Either is acceptable evidence of a PAN.
        $pan_raw = '';
        if (!empty($seller['pan'])) {
            $pan_raw = $seller['pan'];
        } elseif (!empty($seller['pan_number'])) {
            $pan_raw = $seller['pan_number'];
        }
        $pan = $this->classify_pan($pan_raw);

        // Same story for the GSTIN: `gst` is current, `tax_number` is legacy.
        $gstin = '';
        if (!empty($seller['gst'])) {
            $gstin = $seller['gst'];
        } elseif (!empty($seller['tax_number'])) {
            $gstin = $seller['tax_number'];
        }

        // Registration is only accepted when the flag AND a well-formed GSTIN agree. The
        // flag alone defaults to 1 on every seller_data row, so trusting it by itself would
        // have collected TCS against sellers who never supplied a GSTIN.
        $flagged_registered = !isset($seller['is_gst_registered']) || (int) $seller['is_gst_registered'] === 1;
        $gst_registered = $flagged_registered && $this->is_valid_gstin($gstin);

        $state = '';
        foreach (['business_state', 'state', 'pickup_state'] as $key) {
            if (!empty($seller[$key])) {
                $state = $seller[$key];
                break;
            }
        }

        return [
            'seller_id'          => (int) $seller_id,
            'entity_type'        => isset($seller['entity_type']) ? $seller['entity_type'] : null,
            'pan'                => $pan['pan'],
            'pan_valid'          => $pan['valid'],
            'entity_class'       => $pan['entity_class'],
            'threshold_applies'  => $pan['threshold_applies'],
            'gstin'              => strtoupper(trim((string) $gstin)),
            'gst_registered'     => $gst_registered,
            'enrollment_number'  => isset($seller['gst_enrollment_number']) ? $seller['gst_enrollment_number'] : null,
            'state'              => $this->normalise_state($state),
            'exists'             => !empty($seller),
        ];
    }

    /** Lower-cased, punctuation-free state name, so "Uttar Pradesh" and "uttar  pradesh" match. */
    public function normalise_state($state)
    {
        return preg_replace('/[^a-z]/', '', strtolower((string) $state));
    }

    /* -------------------------------------------------------------- place of supply -- */

    /**
     * Intra-state or inter-state, for one order.
     *
     * The total TCS is the same either way (0.5%); what changes is the head it is collected
     * under, and the quarterly GSTR-8 return has to report those separately - so it has to
     * be decided at settlement time and stored, not re-derived later from an address that
     * the customer may since have edited.
     *
     * @return string 'intra_state' | 'inter_state' | 'unknown'
     */
    public function resolve_place_of_supply($order_id, $seller_state)
    {
        $buyer_state = $this->get_order_delivery_state($order_id);
        $seller_state = $this->normalise_state($seller_state);

        if ($buyer_state === '' || $seller_state === '') {
            return 'unknown';
        }

        return ($buyer_state === $seller_state) ? 'intra_state' : 'inter_state';
    }

    /** Delivery state for an order, from the address it was shipped to. */
    public function get_order_delivery_state($order_id)
    {
        $row = $this->db->select('a.state')
            ->join('addresses a', 'a.id = o.address_id', 'left')
            ->where('o.id', (int) $order_id)
            ->get('orders o')
            ->row_array();

        return $this->normalise_state(isset($row['state']) ? $row['state'] : '');
    }

    /* ------------------------------------------------------------ financial year ----- */

    /** Indian financial year (1 April - 31 March) containing $date, as "2026-27". */
    public function financial_year($date = null)
    {
        $ts    = $date ? strtotime($date) : time();
        $year  = (int) date('Y', $ts);
        $month = (int) date('n', $ts);
        $start = ($month >= 4) ? $year : $year - 1;

        return $start . '-' . substr((string) ($start + 1), -2);
    }

    /** First and last calendar dates of a financial year string. */
    public function financial_year_range($fy)
    {
        $start_year = (int) substr((string) $fy, 0, 4);
        return [$start_year . '-04-01', ($start_year + 1) . '-03-31'];
    }

    /**
     * A seller's cumulative taxable turnover for a financial year, as it stands right now.
     *
     * Only 'settled' rows count. That is what makes returns self-correcting: a reversal
     * flips the row to 'reversed', so the returned sale drops straight back out of the
     * total and the seller is not pushed over the Rs. 5 lakh threshold by a sale that was
     * ultimately refunded.
     *
     * Measured on taxable value (ex-GST) to match the base every deduction is computed on.
     */
    public function get_fy_turnover($seller_id, $fy = null)
    {
        $fy = $fy ?: $this->financial_year();
        list($from, $to) = $this->financial_year_range($fy);

        $row = $this->db
            ->select('COALESCE(SUM(taxable_value), 0) AS turnover', false)
            ->where('seller_id', (int) $seller_id)
            ->where('settlement_status', 'settled')
            ->where('created_at >=', $from . ' 00:00:00')
            ->where('created_at <=', $to . ' 23:59:59')
            ->get('seller_settlements')
            ->row_array();

        return round((float) $row['turnover'], 2);
    }

    /* ---------------------------------------------------------------------- TDS ------ */

    /**
     * TDS due on one sale.
     *
     * @param array $profile       from get_seller_tax_profile()
     * @param float $taxable_value ex-GST value of the line
     * @param float $turnover      the seller's FY turnover BEFORE this line
     * @return array{percent: float, amount: float, basis: string, threshold: float, turnover_before: float}
     */
    public function calculate_tds($profile, $taxable_value, $turnover)
    {
        $taxable_value = round((float) $taxable_value, 2);
        $turnover      = round((float) $turnover, 2);
        $threshold     = $this->rate('tds_threshold_amount');

        if (!$this->deductions_enabled()) {
            return $this->tds_result(0, 0, 'deductions_disabled', $threshold, $turnover);
        }

        // s.206AA: no PAN, or one that is not a PAN at all. The higher rate is the penalty
        // for the marketplace being unable to deposit against an identity.
        if (empty($profile['pan_valid'])) {
            $percent = $this->rate('tds_percent_no_pan');
            return $this->tds_result($percent, round($taxable_value * $percent / 100, 2), 'sec_206aa_no_pan', $threshold, $turnover);
        }

        $percent = $this->rate('tds_percent');

        // Individual / HUF only: nothing is due while cumulative turnover for the year stays
        // within the threshold. Measured INCLUSIVE of this sale - the exemption is written as
        // "gross amount of sales does not exceed Rs. 5,00,000", so the sale that lands exactly
        // on the line is still exempt and the first one past it is not.
        if (!empty($profile['threshold_applies'])) {
            if (($turnover + $taxable_value) <= $threshold) {
                return $this->tds_result(0, 0, 'threshold_exempt', $threshold, $turnover);
            }
        }

        // Firm / LLP / company, or an individual past the threshold: charged in full on this
        // sale. The statute deducts on the whole subsequent sale, not only on the part of it
        // that pokes above the threshold.
        return $this->tds_result($percent, round($taxable_value * $percent / 100, 2), 'sec_194o', $threshold, $turnover);
    }

    private function tds_result($percent, $amount, $basis, $threshold, $turnover)
    {
        return [
            'percent'         => round((float) $percent, 2),
            'amount'          => round((float) $amount, 2),
            'basis'           => $basis,
            'threshold'       => (float) $threshold,
            'turnover_before' => (float) $turnover,
        ];
    }

    /* ---------------------------------------------------------------------- TCS ------ */

    /**
     * TCS due on one sale, split by head.
     *
     * @param string $place_of_supply 'intra_state' | 'inter_state' | 'unknown'
     * @return array{percent: float, amount: float, igst: float, cgst: float, sgst: float, basis: string}
     */
    public function calculate_tcs($profile, $taxable_value, $place_of_supply)
    {
        $taxable_value = round((float) $taxable_value, 2);

        if (!$this->deductions_enabled()) {
            return $this->tcs_result(0, 0, 0, 0, 0, 'deductions_disabled');
        }

        // An unregistered seller trading on an Enrollment ID has no GSTIN for the collection
        // to be deposited against, so nothing is collected. Their supplies are restricted to
        // their own state instead - enforced at checkout, not here.
        if (empty($profile['gst_registered'])) {
            return $this->tcs_result(0, 0, 0, 0, 0, 'unregistered');
        }

        $percent = $this->rate('tcs_percent');
        $amount  = round($taxable_value * $percent / 100, 2);

        if ($place_of_supply === 'intra_state') {
            // Split in half, with the remainder rounding into CGST so the two halves always
            // add back to the total - a 0.5% collection on an odd taxable value otherwise
            // loses a paisa between the heads and the settlement stops reconciling.
            $cgst = round($amount / 2, 2);
            $sgst = round($amount - $cgst, 2);
            return $this->tcs_result($percent, $amount, 0, $cgst, $sgst, 'intra_state');
        }

        // Inter-state, and also the 'unknown' case: collected as IGST. The total withheld is
        // identical either way, so an unresolvable delivery state cannot change what the
        // seller is paid - only which head it is reported under, and IGST is the safe default
        // because it does not assert a state that was never established.
        return $this->tcs_result($percent, $amount, $amount, 0, 0, $place_of_supply === 'unknown' ? 'inter_state_assumed' : 'inter_state');
    }

    private function tcs_result($percent, $amount, $igst, $cgst, $sgst, $basis)
    {
        return [
            'percent' => round((float) $percent, 2),
            'amount'  => round((float) $amount, 2),
            'igst'    => round((float) $igst, 2),
            'cgst'    => round((float) $cgst, 2),
            'sgst'    => round((float) $sgst, 2),
            'basis'   => $basis,
        ];
    }

    /* --------------------------------------------------------- checkout enforcement -- */

    /**
     * May this seller supply to this state?
     *
     * A seller trading on a GST Enrollment ID rather than a GSTIN is not registered, and an
     * unregistered supplier cannot make an inter-state supply - registration would be
     * compulsory the moment they did. The marketplace has to stop the order rather than
     * discover it afterwards, because by then the supply has been made.
     *
     * @return bool TRUE when the order must be refused.
     */
    public function is_interstate_supply_blocked($seller_id, $delivery_state)
    {
        $settings = get_settings('system_settings', true);
        if (isset($settings['enforce_intrastate_unregistered']) && (string) $settings['enforce_intrastate_unregistered'] !== '1') {
            return false;
        }

        $profile = $this->get_seller_tax_profile($seller_id);
        if (empty($profile['exists']) || !empty($profile['gst_registered'])) {
            return false;
        }

        $delivery_state = $this->normalise_state($delivery_state);
        // Nothing to compare against: allowing the order is the only option that does not
        // block legitimate checkouts on missing reference data.
        if ($delivery_state === '' || $profile['state'] === '') {
            return false;
        }

        return $delivery_state !== $profile['state'];
    }

    /* --------------------------------------------------------------- return matrix --- */

    /**
     * What the marketplace actually owes the government, net of returns, per seller.
     *
     * Both statutes are computed on supplies NET of returns, so a settlement that was later
     * reversed must reduce the amount deposited rather than be silently left in the total.
     * Nothing did that netting before - the settlement rows carried the deductions and the
     * reversals carried nothing, so a returned order's TCS and TDS stayed payable forever.
     *
     * Settled rows add; reversed rows subtract the same amounts back out.
     *
     * @param string|null $fy        financial year, defaults to the current one
     * @param int|null    $seller_id NULL for every seller
     */
    public function get_deduction_summary($fy = null, $seller_id = null)
    {
        $fy = $fy ?: $this->financial_year();
        list($from, $to) = $this->financial_year_range($fy);

        $sign = "CASE WHEN ss.settlement_status = 'reversed' THEN -1 WHEN ss.settlement_status = 'settled' THEN 1 ELSE 0 END";

        $this->db
            ->select("ss.seller_id, u.username AS seller_name, sd.shop_name,
                      sd.pan, sd.gst, sd.is_gst_registered,
                      COALESCE(SUM($sign * ss.taxable_value), 0)   AS taxable_value,
                      COALESCE(SUM($sign * ss.order_amount), 0)    AS gross_amount,
                      COALESCE(SUM($sign * ss.tds_amount), 0)      AS tds_amount,
                      COALESCE(SUM($sign * ss.tcs_amount), 0)      AS tcs_amount,
                      COALESCE(SUM($sign * ss.tcs_igst_amount), 0) AS tcs_igst,
                      COALESCE(SUM($sign * ss.tcs_cgst_amount), 0) AS tcs_cgst,
                      COALESCE(SUM($sign * ss.tcs_sgst_amount), 0) AS tcs_sgst,
                      SUM(CASE WHEN ss.settlement_status = 'settled' THEN 1 ELSE 0 END)  AS settled_items,
                      SUM(CASE WHEN ss.settlement_status = 'reversed' THEN 1 ELSE 0 END) AS returned_items", false)
            ->join('users u', 'u.id = ss.seller_id', 'left')
            ->join('seller_data sd', 'sd.user_id = ss.seller_id', 'left')
            ->where('ss.created_at >=', $from . ' 00:00:00')
            ->where('ss.created_at <=', $to . ' 23:59:59')
            ->where_in('ss.settlement_status', ['settled', 'reversed']);

        if (!empty($seller_id)) {
            $this->db->where('ss.seller_id', (int) $seller_id);
        }

        $rows = $this->db->group_by('ss.seller_id')->order_by('taxable_value', 'DESC')
            ->get('seller_settlements ss')->result_array();

        $out = [];
        foreach ($rows as $row) {
            $pan = $this->classify_pan($row['pan']);
            $out[] = [
                'seller_id'      => (int) $row['seller_id'],
                'seller_name'    => !empty($row['shop_name']) ? $row['shop_name'] : $row['seller_name'],
                'pan'            => $pan['pan'],
                'pan_valid'      => $pan['valid'],
                'entity_class'   => $pan['entity_class'],
                'gstin'          => (string) $row['gst'],
                'gst_registered' => $this->is_valid_gstin($row['gst']),
                'taxable_value'  => round((float) $row['taxable_value'], 2),
                'gross_amount'   => round((float) $row['gross_amount'], 2),
                'tds_amount'     => round((float) $row['tds_amount'], 2),
                'tcs_amount'     => round((float) $row['tcs_amount'], 2),
                'tcs_igst'       => round((float) $row['tcs_igst'], 2),
                'tcs_cgst'       => round((float) $row['tcs_cgst'], 2),
                'tcs_sgst'       => round((float) $row['tcs_sgst'], 2),
                'settled_items'  => (int) $row['settled_items'],
                'returned_items' => (int) $row['returned_items'],
                'financial_year' => $fy,
            ];
        }

        return $out;
    }
}
