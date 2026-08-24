<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Seller_settlement_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    /**
     * How many of the seller's order items have already been settled successfully.
     * Used to pick the commission slab for the seller's next order.
     */
    public function get_settled_order_count($seller_id)
    {
        return (int) $this->db
            ->where('seller_id', $seller_id)
            ->where('settlement_status', 'settled')
            ->count_all_results('seller_settlements');
    }

    /**
     * Upsert rather than plain insert. seller_settlements has a UNIQUE key on order_item_id,
     * so once a failed attempt is recorded for an item a plain insert_details() on the retry
     * would hit a duplicate-key error and the successful settlement would never be recorded.
     * ON DUPLICATE KEY UPDATE lets the run that finally succeeds overwrite the failed row.
     */
    public function record_settlement($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');

        $columns = [];
        $values = [];
        $updates = [];
        foreach ($data as $column => $value) {
            $columns[] = $this->db->protect_identifiers($column);
            $values[] = $this->db->escape($value);
            // order_item_id is the conflict key itself, so it never needs re-writing.
            if ($column != 'order_item_id') {
                $updates[] = $this->db->protect_identifiers($column) . ' = VALUES(' . $this->db->protect_identifiers($column) . ')';
            }
        }

        $sql = 'INSERT INTO ' . $this->db->protect_identifiers('seller_settlements')
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')'
            . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);

        return $this->db->query($sql);
    }

    /**
     * Sellers whose wallet balance disagrees with the sum of their wallet ledger.
     *
     * Nothing compared the two before, so drift was invisible: a credit recorded without the
     * balance moving (the old Razorpay skip bug) or a balance edited outside the ledger both
     * went unnoticed until someone summed the table by hand.
     *
     * Voided rows are excluded - they are recorded precisely because they never moved money.
     *
     * Note that a mismatch is not automatically a fault: a balance seeded directly into
     * users.balance (demo data, a manual DB edit) legitimately has no ledger history behind
     * it, which is why the ledger row count is reported alongside. Zero ledger rows with a
     * non-zero balance is seeded data; a mismatch with plenty of rows is worth investigating.
     *
     * @return array rows of user_id, username, balance, ledger, difference, ledger_rows
     */
    public function get_wallet_reconciliation($only_sellers = true)
    {
        $this->db->select(
            'u.id AS user_id, u.username, u.balance,
             COALESCE(SUM(CASE WHEN t.type IN (\'credit\', \'refund\') THEN t.amount
                               WHEN t.type = \'debit\' THEN -t.amount ELSE 0 END), 0) AS ledger,
             COUNT(t.id) AS ledger_rows',
            false
        )
            ->join('transactions t', "t.user_id = u.id AND t.transaction_type = 'wallet' AND (t.status IS NULL OR t.status != 'void')", 'left');

        if ($only_sellers) {
            $this->db->join('users_groups ug', 'ug.user_id = u.id')->where('ug.group_id', 4);
        }

        $rows = $this->db->group_by('u.id')->get('users u')->result_array();

        $out = [];
        foreach ($rows as $row) {
            $difference = round((float) $row['balance'] - (float) $row['ledger'], 2);
            if (abs($difference) < 0.005) {
                continue;
            }
            $out[] = [
                'user_id'     => $row['user_id'],
                'username'    => $row['username'],
                'balance'     => (float) $row['balance'],
                'ledger'      => (float) $row['ledger'],
                'difference'  => $difference,
                'ledger_rows' => (int) $row['ledger_rows'],
            ];
        }

        return $out;
    }

    /**
     * Delivered order items that have NOT been credited yet, and how many of them are stuck
     * because their seller has no subscription plan for the commission slab to come from.
     *
     * settle_seller_commission() skips those rows silently and retries them forever, so a
     * seller who never subscribed accrues delivered orders that are never paid out with
     * nothing anywhere reporting it. This is what surfaces them.
     */
    public function get_unsettled_summary()
    {
        $pending = $this->db
            ->select('COUNT(id) as items, COALESCE(SUM(sub_total), 0) as amount', false)
            ->where('active_status', 'delivered')
            ->where('is_credited', 0)
            ->get('order_items')
            ->row_array();

        // "No plan" mirrors Seller_subscription_model::get_current_plan(): a seller with no
        // seller_subscriptions row at all (it falls back to the latest row when none is
        // active, so only a total absence yields null).
        $blocked = $this->db
            ->select('COUNT(oi.id) as items, COALESCE(SUM(oi.sub_total), 0) as amount', false)
            ->join('seller_subscriptions ss', 'ss.seller_id = oi.seller_id', 'left')
            ->where('oi.active_status', 'delivered')
            ->where('oi.is_credited', 0)
            ->where('ss.id IS NULL', null, false)
            ->get('order_items oi')
            ->row_array();

        return [
            'pending_items'      => (int) $pending['items'],
            'pending_amount'     => (float) $pending['amount'],
            'blocked_items'      => (int) $blocked['items'],
            'blocked_amount'     => (float) $blocked['amount'],
        ];
    }

    /**
     * @param int|null $seller_id  A seller id scopes the list to that seller (the seller panel).
     *                             NULL lists every seller's settlements (the admin report).
     */
    function get_settlement_list($seller_id = NULL)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'ss.id';
        $order = 'DESC';

        // Clamped. A negative offset reached MySQL verbatim as `LIMIT -5, 10`, which is a
        // syntax error - the endpoint returned a raw DB error page instead of JSON and the
        // table broke. A limit of 0 or a non-numeric value silently returned nothing, and an
        // unbounded limit let one request ask for the entire table.
        if (isset($_GET['offset'])) {
            $offset = max(0, (int) $_GET['offset']);
        }
        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
            $limit = ($limit < 1) ? 10 : min($limit, 1000);
        }

        // $_GET['sort'] and $_GET['order'] were passed straight into order_by() unchecked -
        // the same SQL-injection shape already fixed on the payment-request list. Whitelisted
        // against the columns this list actually exposes.
        $allowed_sort_columns = [
            'id'                 => 'ss.id',
            'order_id'           => 'ss.order_id',
            'order_amount'       => 'ss.order_amount',
            'commission_percent' => 'ss.commission_percent',
            'commission_amount'  => 'ss.commission_amount',
            'net_payable'        => 'ss.net_payable',
            'settlement_status'  => 'ss.settlement_status',
            'created_at'         => 'ss.created_at',
            'seller_name'        => 'u.username',
        ];
        if (isset($_GET['sort']) && isset($allowed_sort_columns[$_GET['sort']])) {
            $sort = $allowed_sort_columns[$_GET['sort']];
        }
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

        $where = [];
        if (!empty($seller_id)) {
            $where['ss.seller_id'] = $seller_id;
        } elseif (isset($_GET['seller_filter']) && $_GET['seller_filter'] != '') {
            // Admin-only filter; ignored entirely when the caller is a seller, so it can't be
            // used to widen a seller's own scope.
            $where['ss.seller_id'] = (int) $_GET['seller_filter'];
        }
        if (isset($_GET['status_filter']) && $_GET['status_filter'] != '') {
            $where['ss.settlement_status'] = $_GET['status_filter'];
        }

        if (isset($_GET['search']) && $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['ss.id' => $search, 'ss.order_id' => $search, 'ss.settlement_status' => $search, 'u.username' => $search];
        }

        $count_res = $this->db->select('COUNT(ss.id) as `total`')->join('users u', 'u.id = ss.seller_id', 'left');
        // Grouped so the OR'd search terms cannot escape the seller-ownership AND below.
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }
        if (!empty($where)) {
            $count_res->where($where);
        }
        $total = (int) $count_res->get('seller_settlements ss')->row_array()['total'];

        $search_res = $this->db->select('ss.*, u.username as seller_name')->join('users u', 'u.id = ss.seller_id', 'left');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (!empty($where)) {
            $search_res->where($where);
        }
        $rows_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('seller_settlements ss')->result_array();

        $status_badge = [
            'settled'  => '<span class="badge badge-success">Settled</span>',
            'failed'   => '<span class="badge badge-danger">Failed</span>',
            // Written when the order item is later cancelled or returned and the commission
            // is clawed back out of the seller's wallet.
            'reversed' => '<span class="badge badge-secondary">Reversed</span>',
        ];

        $rows = array();
        foreach ($rows_res as $row) {
            $row = output_escaping($row);
            $rows[] = [
                'id' => $row['id'],
                'seller_id' => $row['seller_id'],
                // output_escaping() only strips backslash-escaping, it does not HTML-encode.
                'seller_name' => html_escape($row['seller_name']),
                'order_id' => $row['order_id'],
                'order_amount' => $row['order_amount'],
                // The itemised statement lines. Previously a settlement could only be shown as
                // one commission figure and one net figure, so "deductions" could never be
                // broken down for the seller.
                'product_tax_amount' => isset($row['product_tax_amount']) ? $row['product_tax_amount'] : '0.00',
                'taxable_value' => isset($row['taxable_value']) ? $row['taxable_value'] : $row['order_amount'],
                'commission_percent' => $row['commission_percent'],
                'commission_amount' => $row['commission_amount'],
                'commission_gst_amount' => isset($row['commission_gst_amount']) ? $row['commission_gst_amount'] : '0.00',
                'tcs_amount' => isset($row['tcs_amount']) ? $row['tcs_amount'] : '0.00',
                // The IGST / CGST+SGST split is what the quarterly GSTR-8 return is filed on,
                // so it is reported per settlement rather than only as a combined figure.
                'tcs_igst_amount' => isset($row['tcs_igst_amount']) ? $row['tcs_igst_amount'] : '0.00',
                'tcs_cgst_amount' => isset($row['tcs_cgst_amount']) ? $row['tcs_cgst_amount'] : '0.00',
                'tcs_sgst_amount' => isset($row['tcs_sgst_amount']) ? $row['tcs_sgst_amount'] : '0.00',
                'tcs_percent' => isset($row['tcs_percent']) ? $row['tcs_percent'] : '0.00',
                'tds_amount' => isset($row['tds_amount']) ? $row['tds_amount'] : '0.00',
                'tds_percent' => isset($row['tds_percent']) ? $row['tds_percent'] : '0.00',
                // Which rule produced each deduction. Without this a 0.00 TDS line is
                // ambiguous - it could be a threshold-exempt proprietor or a seller the
                // engine simply failed to classify, and those need opposite responses.
                'tds_basis' => $this->describe_tds_basis(isset($row['tds_basis']) ? $row['tds_basis'] : null),
                'tcs_basis' => $this->describe_tcs_basis(isset($row['tcs_basis']) ? $row['tcs_basis'] : null),
                'seller_entity_class' => isset($row['seller_entity_class']) ? html_escape((string) $row['seller_entity_class']) : '',
                'place_of_supply' => isset($row['place_of_supply']) ? html_escape((string) $row['place_of_supply']) : '',
                'financial_year' => isset($row['financial_year']) ? html_escape((string) $row['financial_year']) : '',
                'net_payable' => $row['net_payable'],
                'settlement_status' => isset($status_badge[$row['settlement_status']]) ? $status_badge[$row['settlement_status']] : html_escape($row['settlement_status']),
                'created_at' => $row['created_at'],
            ];
        }

        $bulkData = array();
        $bulkData['total'] = $total;
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    /**
     * Headline totals for the settlement pages: what the orders were worth, what the platform
     * kept as commission, and what was credited to sellers. Nothing summed these before, so
     * neither the admin nor the seller could see a commission total anywhere.
     */
    public function get_settlement_summary($seller_id = NULL)
    {
        $this->db->select(
            'COUNT(id) as total_settlements,'
                . ' COALESCE(SUM(order_amount), 0) as gross_amount,'
                . ' COALESCE(SUM(commission_amount), 0) as commission_amount,'
                . ' COALESCE(SUM(net_payable), 0) as net_payable',
            false
        )->where('settlement_status', 'settled');

        if (!empty($seller_id)) {
            $this->db->where('seller_id', $seller_id);
        }

        $row = $this->db->get('seller_settlements')->row_array();

        $this->db->where('settlement_status', 'failed');
        if (!empty($seller_id)) {
            $this->db->where('seller_id', $seller_id);
        }
        $failed_count = (int) $this->db->count_all_results('seller_settlements');

        // Reversals are reported separately rather than folded into the totals above: the
        // headline figures stay "what is currently settled", and the clawed-back value is
        // visible as its own number instead of silently disappearing from the report.
        $this->db->select('COUNT(id) as c, COALESCE(SUM(net_payable), 0) as amt, COALESCE(SUM(commission_amount), 0) as comm', false)
            ->where('settlement_status', 'reversed');
        if (!empty($seller_id)) {
            $this->db->where('seller_id', $seller_id);
        }
        $rev = $this->db->get('seller_settlements')->row_array();

        return [
            'total_settlements' => (int) $row['total_settlements'],
            'gross_amount'      => (float) $row['gross_amount'],
            'commission_amount' => (float) $row['commission_amount'],
            'net_payable'       => (float) $row['net_payable'],
            'failed_count'      => $failed_count,
            'reversed_count'    => (int) $rev['c'],
            'reversed_amount'   => (float) $rev['amt'],
            'reversed_commission' => (float) $rev['comm'],
        ];
    }

    /**
     * Plain-English label for the rule that set the TDS on a settlement.
     *
     * A bare 0.00 in the TDS column means two completely different things - a proprietor
     * still inside their Rs. 5 lakh annual threshold (correct, nothing to do) or deductions
     * switched off platform-wide (a compliance gap). Labelling it removes the guesswork.
     */
    public function describe_tds_basis($basis)
    {
        $labels = [
            'threshold_exempt'   => '<span class="badge badge-light" title="Individual / HUF seller still within the Rs. 5,00,000 threshold for this financial year.">Under threshold</span>',
            'sec_194o'           => '<span class="badge badge-info" title="Deducted under Income Tax s.194-O at the standard rate.">194-O</span>',
            'sec_206aa_no_pan'   => '<span class="badge badge-danger" title="No valid PAN on file, so the higher s.206AA rate applies. Collect the seller\'s PAN to reduce this.">206AA (no PAN)</span>',
            'deductions_disabled' => '<span class="badge badge-warning" title="Statutory deductions are switched off in Settings.">Disabled</span>',
            'flat_rate'          => '<span class="badge badge-secondary" title="Recorded before per-seller classification existed, using the flat configured rate.">Flat rate</span>',
        ];

        return isset($labels[$basis]) ? $labels[$basis] : '<span class="text-muted">-</span>';
    }

    /** Same, for the GST TCS side. */
    public function describe_tcs_basis($basis)
    {
        $labels = [
            'unregistered'        => '<span class="badge badge-light" title="Seller has no GSTIN (Enrollment ID only), so no TCS is collected and they may only supply within their own state.">Unregistered</span>',
            'intra_state'         => '<span class="badge badge-info" title="Intra-state supply: collected as CGST + SGST.">CGST + SGST</span>',
            'inter_state'         => '<span class="badge badge-info" title="Inter-state supply: collected as IGST.">IGST</span>',
            'inter_state_assumed' => '<span class="badge badge-warning" title="The delivery state could not be resolved, so this was collected as IGST. The total withheld is unaffected.">IGST (state unknown)</span>',
            'deductions_disabled' => '<span class="badge badge-warning" title="Statutory deductions are switched off in Settings.">Disabled</span>',
            'flat_rate'           => '<span class="badge badge-secondary" title="Recorded before per-seller classification existed, using the flat configured rate.">Flat rate</span>',
        ];

        return isset($labels[$basis]) ? $labels[$basis] : '<span class="text-muted">-</span>';
    }
}
