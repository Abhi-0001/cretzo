<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller price calculator.
 *
 * Answers two questions with one engine: "what should I charge to make X%?" and
 * "if I charge Y, what do I actually get?".
 *
 * The only thing this model computes on its own is the RECOMMENDED PRICE. Every rupee it
 * reports back comes from Seller_model::calculate_settlement_breakdown() - the same function
 * the nightly payout run uses to decide what a seller is paid. Nothing here re-implements a
 * deduction, and nothing may: the moment this file grows its own commission or GST arithmetic,
 * the estimate and the settlement start drifting and the calculator becomes a liability.
 *
 * The order of operations is therefore deliberate:
 *
 *   1. resolve the rates (GST band, plan slab, statutory rates for THIS seller)
 *   2. solve for the price that hits the target margin           <- the only new maths
 *   3. run the real settlement ladder on that price              <- the numbers shown
 *   4. report what the ladder produced, never what step 2 assumed
 *
 * So if step 2 is ever slightly off (it can be, at a TDS threshold boundary), the seller still
 * sees a truthful settlement - just for a price a rupee away from the ideal one. The failure
 * mode is a marginally imperfect suggestion, never a wrong payout figure.
 */
class Price_calculator_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['function_helper']);
        $this->load->model(['Seller_model', 'Seller_subscription_model', 'Tax_compliance_model']);
    }

    /* ------------------------------------------------------------------ options ------ */

    /**
     * The GST bands a seller can choose from.
     *
     * Read from `taxes`, which is where the platform's GST rates genuinely live - a product's
     * band is `products`.`tax` pointing at one of these rows. There is deliberately no HSN or
     * category lookup here: `products`.`hsn_code` is free text that drives nothing and
     * `categories` carries no tax column, so a field offering to derive GST from either would
     * be inventing an answer. That mapping is a separate piece of work with a table behind it.
     */
    public function tax_bands()
    {
        return $this->db->select('id, title, percentage')
            ->where('status', 1)
            ->order_by('percentage + 0', 'ASC', false)
            ->get('taxes')
            ->result_array();
    }

    /** Every plan a seller could be on, so an upgrade can be priced before it is bought. */
    public function plans()
    {
        return $this->db->select('id, name, price, validity, listings_limit,
                                  commission_first50, commission_51_100, commission_after100')
            ->order_by('price + 0', 'ASC', false)
            ->get('subscriptions')
            ->result_array();
    }

    /**
     * What this seller's parcels have actually cost to ship, if anything is known.
     *
     * Freight only enters the system when Shiprocket assigns an AWB
     * (record_shiprocket_freight()), so on an installation where booking is failing this is
     * legitimately empty. It returns null in that case and the screen shows no hint at all,
     * rather than presenting a made-up average as though it came from the seller's history.
     *
     * @return float|null median of the non-zero per-item freight over the window
     */
    public function median_shipping($seller_id, $days = 90)
    {
        $rows = $this->db->select('shipping_deduction')
            ->where('seller_id', (int) $seller_id)
            ->where('shipping_deduction >', 0)
            ->where('date_added >=', date('Y-m-d H:i:s', strtotime('-' . (int) $days . ' days')))
            ->order_by('shipping_deduction', 'ASC')
            ->get('order_items')
            ->result_array();

        if (empty($rows)) {
            return null;
        }

        $values = array_map(function ($row) {
            return (float) $row['shipping_deduction'];
        }, $rows);

        $count = count($values);
        $mid = (int) floor($count / 2);

        return ($count % 2)
            ? round($values[$mid], 2)
            : round(($values[$mid - 1] + $values[$mid]) / 2, 2);
    }

    /* -------------------------------------------------------------------- quote ------ */

    /**
     * Price one unit, end to end.
     *
     * @param array $in seller_id, plan_id, tax_id, product_cost, cost_includes_gst,
     *                  shipping, target_margin, selling_price (optional - price this instead
     *                  of recommending one).
     * @return array ['error' => bool, 'message' => string, ...]
     */
    public function quote($in)
    {
        $seller_id = (int) $in['seller_id'];

        $cost     = max(0, round((float) $in['product_cost'], 2));
        $shipping = max(0, round((float) $in['shipping'], 2));
        $margin   = (float) $in['target_margin'];

        $rates = $this->resolve_rates($seller_id, $in);
        if ($rates['error']) {
            return $rates;
        }

        // Input tax credit, and only where it is real. A GST-registered seller reclaims the
        // GST inside their purchase cost, so their true cost is the ex-GST figure. An
        // unregistered seller (trading on an enrollment ID) reclaims nothing and the tax is
        // part of what the goods cost them. Applying the credit to everyone alike would
        // overstate the margin of exactly the sellers least able to absorb it.
        $effective_cost = $cost;
        $itc = 0.0;
        if (!empty($in['cost_includes_gst']) && $rates['gst_registered'] && $rates['gst_percent'] > 0) {
            $effective_cost = round($cost / (1 + ($rates['gst_percent'] / 100)), 2);
            $itc = round($cost - $effective_cost, 2);
        }

        // Either price the seller's own figure, or solve for one.
        if (isset($in['selling_price']) && $in['selling_price'] !== '' && (float) $in['selling_price'] > 0) {
            $price = round((float) $in['selling_price'], 2);
            $solved = ['reachable' => true, 'keep_share' => null];
        } else {
            $solved = $this->solve_price($effective_cost, $shipping, $margin, $rates, $seller_id);
            if (!$solved['reachable']) {
                return [
                    'error'   => true,
                    'message' => $solved['message'],
                ];
            }
            $price = $solved['price'];
        }

        if ($price <= 0) {
            return ['error' => true, 'message' => 'Enter a product cost above zero.'];
        }

        $gateway_fee = $this->gateway_fee_for($price, $rates);

        // THE settlement ladder. Not a copy of it.
        $breakdown = $this->Seller_model->calculate_settlement_breakdown(
            $price,
            $rates['gst_percent'],
            $rates['commission_percent'],
            $shipping,
            $gateway_fee,
            ['seller_id' => $seller_id, 'fy_turnover' => $rates['fy_turnover']]
        );

        $wallet_credit = (float) $breakdown['net_payable'];
        $gst_remitted  = (float) $breakdown['product_tax_amount'];
        $profit        = round($wallet_credit - $gst_remitted - $effective_cost, 2);

        return [
            'error'   => false,
            'message' => 'Priced',
            'rates'   => $rates,
            'cost'    => [
                'entered'          => $cost,
                'effective'        => $effective_cost,
                'input_tax_credit' => $itc,
                'itc_applied'      => ($itc > 0),
            ],
            'price'     => $price,
            'breakdown' => $breakdown,
            'earnings'  => [
                'wallet_credit'   => $wallet_credit,
                'gst_remitted'    => $gst_remitted,
                'product_cost'    => $effective_cost,
                'shipping'        => $shipping,
                'profit'          => $profit,
                'margin_percent'  => ($price > 0) ? round($profit / $price * 100, 2) : 0,
                'markup_percent'  => ($effective_cost > 0) ? round($profit / $effective_cost * 100, 2) : null,
            ],
        ];
    }

    /* ------------------------------------------------------------------- rates ------- */

    /**
     * Every percentage that applies to this sale, each with where it came from.
     *
     * The sources matter as much as the numbers: a seller who is shown "7%" learns nothing,
     * but one shown "7% - orders 1-50 on the 100 Listings plan" can predict their own next
     * sale. Every value here is read from the database or the settings, never assumed.
     */
    private function resolve_rates($seller_id, $in)
    {
        $band = null;
        if (!empty($in['tax_id'])) {
            $band = $this->db->where('id', (int) $in['tax_id'])->get('taxes')->row_array();
        }
        if (empty($band)) {
            return ['error' => true, 'message' => 'Choose a GST rate for these goods.'];
        }

        // The plan being priced - the seller's own unless they asked about another one.
        $plan = null;
        if (!empty($in['plan_id'])) {
            $plan = $this->db->where('id', (int) $in['plan_id'])->get('subscriptions')->row_array();
        }
        if (empty($plan)) {
            $plan = $this->Seller_subscription_model->get_current_plan($seller_id);
        }

        $current_plan = $this->Seller_subscription_model->get_current_plan($seller_id);
        $orders_done  = $this->Seller_model->get_seller_order_count($seller_id);
        $order_no     = $orders_done + 1;

        $commission = $this->Seller_model->commission_rate_for_plan($plan, $order_no);

        // GST on the platform's commission, and the gateway rates, exactly as the settlement
        // engine reads them: the admin settings blob first, config/commission.php behind it.
        $this->config->load('commission', true);
        $settings = get_settings('system_settings', true);
        $setting_or_config = function ($key) use ($settings) {
            if (isset($settings[$key]) && $settings[$key] !== '') {
                return (float) $settings[$key];
            }
            return (float) $this->config->item($key, 'commission');
        };

        // Statutory rates for THIS seller, from their own PAN class, GSTIN and turnover -
        // not flat percentages. Probed against a nominal taxable value so the answer reflects
        // the rules rather than one particular sale; solve_price() re-probes at the real value
        // once it has one, which is what catches a TDS threshold crossing.
        $profile  = $this->Tax_compliance_model->get_seller_tax_profile($seller_id);
        $fy       = $this->Tax_compliance_model->financial_year();
        $turnover = $this->Tax_compliance_model->get_fy_turnover($seller_id, $fy);

        $tcs = $this->Tax_compliance_model->calculate_tcs($profile, 1000, 'unknown');
        $tds = $this->Tax_compliance_model->calculate_tds($profile, 1000, $turnover);

        return [
            'error'              => false,
            'gst_percent'        => (float) $band['percentage'],
            'gst_band'           => $band['title'],
            'gst_band_id'        => (int) $band['id'],
            'commission_percent' => $commission['rate'],
            'commission_source'  => $commission['source'],
            'commission_gst'     => $setting_or_config('commission_gst_percent'),
            'tcs_percent'        => $tcs['percent'],
            'tcs_basis'          => $tcs['basis'],
            'tds_percent'        => $tds['percent'],
            'tds_basis'          => $tds['basis'],
            'gateway_percent'    => $setting_or_config('payment_gateway_percent'),
            'gateway_fixed'      => $setting_or_config('payment_gateway_fixed'),
            'gst_registered'     => !empty($profile['gst_registered']),
            'entity_class'       => $profile['entity_class'],
            'fy_turnover'        => round((float) $turnover, 2),
            'financial_year'     => $fy,
            'orders_done'        => $orders_done,
            'order_no'           => $order_no,
            'plan'               => $plan,
            'plan_id'            => !empty($plan['id']) ? (int) $plan['id'] : null,
            'is_current_plan'    => (!empty($plan['id']) && !empty($current_plan['id']) && $plan['id'] == $current_plan['id']),
            'slab'               => ($order_no <= 50) ? 'first50' : (($order_no <= 100) ? '51_100' : 'after100'),
        ];
    }

    /**
     * The payment gateway's cut of a sale.
     *
     * Both rates ship absent, so this is 0 and the screen hides the line entirely. It is read
     * from settings rather than hard-coded so that turning it on is an admin action - but note
     * that until the settlement run passes the same figure, this must stay at 0: a fee shown
     * here and not deducted there would tell sellers they earn less than they do.
     */
    private function gateway_fee_for($price, $rates)
    {
        $fee = ($price * $rates['gateway_percent'] / 100) + $rates['gateway_fixed'];

        return round(max(0, $fee), 2);
    }

    /* ------------------------------------------------------------------- solve ------- */

    /**
     * The selling price that leaves the seller a given margin.
     *
     * Every deduction in the ladder is linear in the price, so this solves in closed form
     * rather than by search. Writing k for the share of the ex-GST value taken by the platform
     * and the statute (commission + GST on it + TCS + TDS), P for the GST-inclusive price and
     * g for the product's GST rate:
     *
     *     wallet credit   = P - k*P/(1+g) - gw*P - gwf - F
     *     GST the seller remits = P*g/(1+g)
     *     kept            = P*(1-k)/(1+g) - gw*P - gwf - F
     *     profit          = kept - C
     *
     * Setting profit = m*P and rearranging:
     *
     *     P = (C + F + gwf) / [ (1-k)/(1+g) - gw - m ]
     *
     * The bracket is the share of every rupee the seller keeps. If it is zero or negative the
     * target is unreachable at ANY price - the deductions plus the target exceed the whole
     * sale - and this says so rather than returning an enormous number.
     *
     * TDS is the one rate that depends on the answer (the s.194-O threshold is measured on
     * cumulative turnover INCLUDING this sale), so the solve is repeated with the rate
     * re-probed at the price it produced. It settles in one extra pass except exactly at the
     * threshold, where the loop stops and the forward ladder has the final word anyway.
     */
    private function solve_price($cost, $shipping, $margin, $rates, $seller_id)
    {
        $g   = $rates['gst_percent'] / 100;
        $m   = $margin / 100;
        $gw  = $rates['gateway_percent'] / 100;
        $gwf = $rates['gateway_fixed'];

        $tds_percent = $rates['tds_percent'];
        $price = 0.0;

        $profile = $this->Tax_compliance_model->get_seller_tax_profile($seller_id);

        for ($pass = 0; $pass < 3; $pass++) {
            $k = ($rates['commission_percent'] / 100)
                + ($rates['commission_percent'] / 100) * ($rates['commission_gst'] / 100)
                + ($rates['tcs_percent'] / 100)
                + ($tds_percent / 100);

            $keep_share = ((1 - $k) / (1 + $g)) - $gw - $m;

            if ($keep_share <= 0.0001) {
                return [
                    'reachable' => false,
                    'message'   => 'A ' . rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.')
                        . '% margin is not reachable on these goods. After GST, commission and the'
                        . ' statutory deductions you keep about '
                        . number_format(max(0, ((1 - $k) / (1 + $g)) - $gw) * 100, 1)
                        . '% of the selling price, so the margin has to sit below that.',
                ];
            }

            $price = round(($cost + $shipping + $gwf) / $keep_share, 2);

            // Re-probe TDS at the value this price actually produces. Nothing else in k moves.
            $taxable = ($g > 0) ? round($price / (1 + $g), 2) : $price;
            $probe = $this->Tax_compliance_model->calculate_tds($profile, $taxable, $rates['fy_turnover']);

            if (abs($probe['percent'] - $tds_percent) < 0.0001) {
                return ['reachable' => true, 'price' => $price, 'keep_share' => $keep_share];
            }
            $tds_percent = $probe['percent'];
        }

        return ['reachable' => true, 'price' => $price, 'keep_share' => null];
    }
}
