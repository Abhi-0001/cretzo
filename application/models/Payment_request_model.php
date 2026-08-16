<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Payment_request_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    /**
     * Minimum a seller may withdraw in one request. Below this the payout fees make the
     * transfer pointless, and it stops the request queue filling with 1-rupee rows.
     */
    const MIN_WITHDRAWAL_AMOUNT = 100;

    /**
     * Create a withdrawal request and move the money off the seller's wallet, atomically.
     *
     * Everything the old inline controller code did wrong is handled here:
     *  - the balance is re-read INSIDE the transaction with FOR UPDATE, so two concurrent
     *    requests can't both pass the same balance check and overdraw the wallet;
     *  - the debit is written through update_wallet_balance(), so it produces a row in
     *    `transactions`. The old path called Delivery_boy_model::update_balance(), which
     *    only touches users.balance and writes NO ledger row - so the seller's Wallet
     *    Transactions page never showed the withdrawal at all, and users.balance
     *    permanently disagreed with the sum of the transaction log by every withdrawal
     *    ever made;
     *  - the insert and the debit commit together, so a failure can't take the money
     *    without recording the request (or record a request without taking the money).
     *
     * @return array{error: bool, message: string, balance?: string}
     */
    public function create_withdrawal_request($user_id, $payment_type, $amount, $payment_address)
    {
        $amount = round((float) $amount, 2);

        if ($amount < self::MIN_WITHDRAWAL_AMOUNT) {
            return [
                'error'   => true,
                'message' => 'Minimum withdrawal amount is ' . self::MIN_WITHDRAWAL_AMOUNT . '.',
            ];
        }

        // One pending request at a time. Without this a seller could submit their whole
        // balance, then submit it again the moment the first debit landed... except the
        // balance was already deducted, so instead they'd stack several partial requests the
        // admin has no way to tell apart. Keeping it to one open request also means the
        // admin's approve/reject decision is unambiguous.
        $pending = $this->db
            ->where('user_id', $user_id)
            ->where('status', 0)
            ->count_all_results('payment_requests');
        if ($pending > 0) {
            return [
                'error'   => true,
                'message' => 'You already have a withdrawal request awaiting approval. Please wait for it to be processed.',
            ];
        }

        $this->db->trans_start();

        // FOR UPDATE holds the row until this transaction commits, which is what serialises
        // two simultaneous withdrawal attempts by the same seller.
        $user = $this->db->query(
            'SELECT balance FROM users WHERE id = ' . $this->db->escape($user_id) . ' FOR UPDATE'
        )->row_array();

        if (empty($user)) {
            $this->db->trans_complete();
            return ['error' => true, 'message' => 'User does not exist.'];
        }

        if ($amount > (float) $user['balance']) {
            $this->db->trans_complete();
            return [
                'error'   => true,
                'message' => "You don't have enough balance to sent the withdraw request.",
            ];
        }

        $inserted = $this->db->insert('payment_requests', [
            'user_id'          => $user_id,
            'payment_address'  => $payment_address,
            'payment_type'     => $payment_type,
            'amount_requested' => $amount,
            'status'           => 0,
        ]);

        $debited = ['error' => true];
        if ($inserted) {
            $request_id = $this->db->insert_id();
            $debited = update_wallet_balance(
                'debit',
                $user_id,
                $amount,
                'Withdrawal request #' . $request_id . ' - amount held for payout'
            );
        }

        $this->db->trans_complete();

        if (!$inserted || !empty($debited['error']) || $this->db->trans_status() === FALSE) {
            return [
                'error'   => true,
                'message' => 'Cannot sent Withdrawal Request.Please Try again later.',
            ];
        }

        $balance = fetch_details('users', ['id' => $user_id], 'balance');

        return [
            'error'   => false,
            'message' => 'Withdrawal Request Sent Successfully',
            'balance' => isset($balance[0]['balance']) ? $balance[0]['balance'] : '0',
        ];
    }

    function get_payment_request_list($user_id = NULL)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'pr.id';
        $order = 'ASC';
        $multipleWhere = '';

        // Clamped: a negative offset reached MySQL as `LIMIT -5, 10`, a syntax error that
        // returned a raw DB error page instead of JSON and broke the withdrawal table.
        if (isset($_GET['offset'])) {
            $offset = max(0, (int) $_GET['offset']);
        }
        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
            $limit = ($limit < 1) ? 10 : min($limit, 1000);
        }

        // Whitelist against the actual selected columns - $_GET['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id' => 'pr.id', 'user_name' => 'u.username', 'payment_type' => 'pr.payment_type', 'amount_requested' => 'pr.amount_requested', 'date_created' => 'pr.date_created'];
        if (isset($_GET['sort']) && isset($allowed_sort_columns[$_GET['sort']])) {
            $sort = $allowed_sort_columns[$_GET['sort']];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') {
            $order = 'asc';
        } else {
            $order = 'desc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['pr.`id`' => $search, 'u.`username`' => $search, 'u.`email`' => $search, 'u.`mobile`' => $search];
        }
        if (isset($_GET['user_filter']) && $_GET['user_filter'] != '') {
            $where = ['payment_type' =>  $_GET['user_filter']];
        }

        $count_res = $this->db->select(' COUNT(pr.id) as `total` ')->join('users u', 'u.id=pr.user_id');

        // Grouped so the OR'd search terms can't escape the seller-ownership AND below —
        // ungrouped, SQL's AND-binds-tighter-than-OR precedence meant the ownership
        // constraint only applied to the last OR term, leaking every user's withdrawal
        // requests to a seller the moment they typed anything into the search box.
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_where($multipleWhere);
            $count_res->group_end();
        }

        if (isset($user_id) && !empty($user_id)) {
            $where = ['pr.user_id' => $user_id];
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $request_count = $count_res->get('payment_requests pr')->result_array();

        foreach ($request_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->join('users u', 'u.id=pr.user_id');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $offer_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->select('u.username,pr.*')->get('payment_requests pr')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($offer_search_res as $row) {
            $row = output_escaping($row);
            if (!isset($user_id) && empty($user_id)) {
                // The button carried NO data attributes, and no JS anywhere bound to
                // .edit_request - so the modal opened completely blank, its hidden
                // payment_request_id was never filled in, and every submit failed validation
                // with "The id field is required". Admin approval/rejection was therefore
                // impossible through the UI. The row's own values are attached here for the
                // handler in custom.js to read.
                if ($row['status'] == 0) {
                    $operate = '<a href="javascript:void(0)" class="edit_request action-btn btn btn-success btn-xs mr-1 mb-1 ml-1" title="Approve / Reject"'
                        . ' data-target="#payment_request_modal" data-toggle="modal"'
                        . ' data-id="' . html_escape($row['id']) . '"'
                        . ' data-status="' . html_escape($row['status']) . '"'
                        . ' data-amount="' . html_escape($row['amount_requested']) . '"'
                        . ' data-username="' . html_escape($row['username']) . '"'
                        . ' data-address="' . html_escape($row['payment_address']) . '"'
                        . ' data-remarks="' . html_escape($row['remarks']) . '"'
                        . '><i class="fa fa-pen"></i></a>';
                } else {
                    // Already finalised - the controller refuses to change it, so don't offer
                    // an action that can only ever return "already been finalized".
                    $operate = '<span class="text-muted small">&mdash;</span>';
                }
            }
            $tempRow['id'] = $row['id'];
            $tempRow['user_id'] = $row['user_id'];
            // output_escaping() only strips backslash-escaping, it does not HTML-encode - a
            // stored-XSS route the same as already fixed on other pages.
            $tempRow['user_name'] = html_escape($row['username']);
            $tempRow['payment_type'] = html_escape($row['payment_type']);
            $tempRow['amount_requested'] = $row['amount_requested'];
            $tempRow['payment_address'] = html_escape($row['payment_address']);
            $tempRow['date_created'] = $row['date_created'];
            $status = [
                '0' => '<span class="badge badge-warning">Pending</span>',
                '1' => '<span class="badge badge-success">Approved</span>',
                '2' => '<span class="badge badge-danger">Rejected</span>',
            ];

            // Pending was rendered with badge-success (green), reading as "done" - the exact
            // opposite of what it means. Pending is now amber, approved green, rejected red.
            $tempRow['status'] = isset($status[$row['status']]) ? $status[$row['status']] : html_escape($row['status']);
            $tempRow['status_digit'] = $row['status'];
            $tempRow['remarks'] = html_escape($row['remarks']);
            $tempRow['payment_reference'] = isset($row['payment_reference']) ? html_escape($row['payment_reference']) : '';
            $tempRow['processed_at'] = (isset($row['processed_at']) && !empty($row['processed_at'])) ? $row['processed_at'] : '';
            if (!isset($user_id) && empty($user_id)) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }


    function update_payment_request($data, $admin_id = null)
    {

        $data = escape_array($data);
        $request = array(
            'status' => $data['status'],
            'remarks' => (isset($data['update_remarks']) && !empty($data['update_remarks'])) ? $data['update_remarks'] : null,
            // Audit trail: who actioned it and when. Approval used to be a bare status flag
            // with nothing recording which admin made the payout decision or on what date.
            'processed_by' => $admin_id,
            'processed_at' => date('Y-m-d H:i:s'),
            // The real-world payout reference (bank UTR / UPI ref). The actual money movement
            // happens outside this system, so without this an "Approved" row was an assertion
            // with no evidence attached and no way for a seller to chase a missing payment.
            'payment_reference' => (isset($data['payment_reference']) && $data['payment_reference'] !== '') ? $data['payment_reference'] : null,
        );
        $amount = fetch_details("payment_requests", ['id' => $data['payment_request_id']], "amount_requested,user_id,status");

        if (empty($amount)) {
            return false;
        }

        // The wallet credit and the status flip were two separate, unwrapped writes - if the
        // process died between them, a retry could see the still-pending status and credit
        // the wallet a second time. Wrapped so both happen atomically or not at all.
        // A rejection has to return money, so verify up front that there is somewhere to return
        // it to. update_wallet_balance() reports a missing user by RETURNING an error rather
        // than throwing, and that return value used to be discarded - so the request was still
        // stamped "Rejected" while the refund silently did nothing, telling the seller their
        // money was back when it had simply vanished. Checked before the transaction opens,
        // because CI's managed transactions (trans_start/trans_complete) have no supported way
        // to force a rollback from application code once they are underway.
        // Finality is enforced HERE as well as in the controller. It used to live only in
        // admin/Payment_request.php, so the model would happily action a request that had
        // already been decided: calling it with status=2 on an APPROVED request passed the
        // `!= 2` test below and refunded the money a second time - on top of the payout the
        // admin had already made in the real world. Any future caller (a cron, a bulk tool, an
        // API) would have inherited that. Once a request leaves Pending it is done.
        if ((int) $amount[0]['status'] !== 0) {
            return false;
        }

        $is_rejection = ($data['status'] == 2 && $amount[0]['status'] != 2);
        if ($is_rejection) {
            $payee = fetch_details('users', ['id' => $amount[0]['user_id']], 'id');
            if (empty($payee) || (float) $amount[0]['amount_requested'] <= 0) {
                return false;
            }
        }

        $this->db->trans_start();
        $refund_failed = false;
        if ($is_rejection) {
            // Was update_balance(), which bumps users.balance and writes NO `transactions`
            // row - so a rejected withdrawal silently restored the money with nothing in the
            // seller's wallet history to explain where it came from, and the ledger drifted
            // further from users.balance on every rejection. update_wallet_balance() records
            // the matching credit, so the debit taken at request time and this refund now
            // both appear on the seller's Wallet Transactions page and reconcile.
            $refund = update_wallet_balance(
                'credit',
                $amount[0]['user_id'],
                $amount[0]['amount_requested'],
                'Withdrawal request #' . $data['payment_request_id'] . ' rejected - amount returned to wallet'
            );
            // The refund's return value was ignored. update_wallet_balance() returns an error
            // rather than throwing (a deleted user, a zero amount), so a no-op refund still let
            // the request be stamped "Rejected" - telling the seller their money was returned
            // when it never left the request. Fail the whole update instead, so the request
            // stays Pending and an admin can see it still needs handling.
            $refund_failed = !empty($refund['error']);
        }
        // Only stamp the new status if the refund it promises actually happened. Skipping the
        // update leaves the request Pending, which is recoverable; stamping it Rejected without
        // the refund is not.
        if (!$refund_failed) {
            $this->db->where('id', $data['payment_request_id'])->update('payment_requests', $request);
        }
        $this->db->trans_complete();

        return !$refund_failed && $this->db->trans_status() !== FALSE;
    }
}
