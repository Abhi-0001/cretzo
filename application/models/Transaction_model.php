<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Transaction_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function add_transaction($data)
    {
        $this->load->model('Order_model');
        $data = escape_array($data);
        /* transaction_type : transaction - for payment transactions | wallet - for wallet transactions  */
        $transaction_type = (!isset($data['transaction_type']) || empty($data['transaction_type'])) ? 'transaction' : $data['transaction_type'];
        $trans_data = [
            'transaction_type' => $transaction_type,
            'user_id' => $data['user_id'],
            'order_id' => $data['order_id'],
            'order_item_id' => isset($data['order_item_id']) ? $data['order_item_id'] : null,
            'type' => strtolower($data['type']),
            'txn_id' => $data['txn_id'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'message' => $data['message'],
        ];
        $this->db->insert('transactions', $trans_data);
    }

    function get_transactions_list($user_id = '', $group_id = 2)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';
        $where = [];
        if (isset($_GET['transaction_type']))
            $where = ['transactions.transaction_type' => $_GET['transaction_type']];
        
        // if (isset($_GET['type']))
        //     $where = ['transactions.type' => $_GET['type']];

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['id', 'transactions.id', 'amount', 'transactions.amount', 'date_created', 'transactions.date_created'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'DESC';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['`transactions.id`' => $search, '`transactions.amount`' => $search, '`transactions.date_created`' => $search, 'users.username' => $search, 'users.mobile' => $search, 'users.email' => $search, 'transactions.type' => $search, 'transactions.status' => $search, 'transactions.txn_id' => $search];
        }
        if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $where = ['users.id' => $_GET['user_id']];
        }
        if (isset($user_id) && !empty($user_id)) {
            $user_where = ['users.id' => $user_id];
        }

        if (isset($_GET['user_type']) && !empty($_GET['user_type'])) {
            $group_id_res = fetch_details("groups", ['name' => $_GET['user_type']], "id");
            // An unrecognized user_type left $group_id as an undefined array key, which
            // stringified to '' in the "ug.group_id = $group_id" clause below and threw a
            // SQL syntax error — keep the caller-supplied default instead.
            if (!empty($group_id_res)) {
                $group_id = $group_id_res[0]['id'];
            }
        }
        $count_res = $this->db->select(' COUNT(transactions.id) as `total`  ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_Start();
            $count_res->or_like($multipleWhere);
            $this->db->group_End();
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }
        if (isset($user_where) && !empty($user_where)) {
            $count_res->where($user_where);
        }
        // Was or_where('ug.group_id = 1') here, followed unconditionally by
        // where('ug.group_id = ' . $group_id) below - SQL's AND binding tighter than OR
        // collapsed the whole clause to "<other conditions> OR (ug.group_id=1 AND
        // ug.group_id=2)", an always-false second half, which silently dropped the group
        // filter entirely the moment any other where/search condition was present (any
        // $_GET['transaction_type']) - customer-only views like View Transaction and Customer
        // Wallet ended up showing every role's transactions mixed together instead of just
        // customers'. Grouped so the two group ids are evaluated together, independent of
        // whatever other conditions are already attached.
        if (isset($group_id) && !empty($group_id) && $group_id == 2) {
            $count_res->group_start()->where('ug.group_id', 1)->or_where('ug.group_id', 2)->group_end();
        } else {
            $count_res->where('ug.group_id', $group_id);
        }
        // users_groups was joined with no join-type argument, which CodeIgniter renders as a
        // plain (INNER) JOIN. Combined with the mandatory ug.group_id filter, any transaction
        // whose user has no matching users_groups row (deleted account, orphaned/system
        // transaction) was silently excluded from both the count and the row list, on every
        // page, under every filter.
        $txn_count = $count_res->join('users', ' transactions.user_id = users.id', 'left')->join('users_groups ug', 'ug.user_id = users.id', 'left')->get('transactions')->result_array();

        $total = $txn_count[0]['total'];
        // ---------------------------------------


        $search_res = $this->db->select(' transactions.*,users.username as name  ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_Start();
            $search_res->or_like($multipleWhere);
            $this->db->group_End();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }
        if (isset($user_where) && !empty($user_where)) {
            $search_res->where($user_where);
        }
        if (isset($group_id) && !empty($group_id) && $group_id == 2) {
            $search_res->group_start()->where('ug.group_id', 1)->or_where('ug.group_id', 2)->group_end();
        } else {
            $search_res->where('ug.group_id', $group_id);
        }
        $search_res->join('users', ' transactions.user_id = users.id', 'left')->join('users_groups ug', 'ug.user_id = users.id', 'left');
        $txn_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('transactions')->result_array();
        // print_R(count($txn_search_res));
       
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($txn_search_res as $row) {
            $row = output_escaping($row);
            // output_escaping() only strips backslash-escaping, it does not HTML-encode - name/
            // message/txn_id are free text (the message in particular is admin/seller-entered)
            // and were rendered raw, a stored-XSS route the same as already fixed elsewhere.
            if ($row['type'] == 'bank_transfer') {
                $operate = ' <a href="javascript:void(0)" class="edit_transaction action-btn btn btn-success btn-xs mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-txn_id="' . html_escape($row['txn_id']) . '" data-status="' . html_escape($row['status']) . '" data-message="' . html_escape($row['message']) . '"  data-target="#transaction_modal" data-toggle="modal"><i class="fa fa-pen"></i></a>';
            } else {
                $operate = "";
            }
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = html_escape($row['name']);
            $tempRow['order_id'] = $row['order_id'];
            $tempRow['type'] = $row['type'];
            $tempRow['txn_id'] = html_escape($row['txn_id']);
            $tempRow['payu_txn_id'] = $row['payu_txn_id'];
            $tempRow['amount'] = $row['amount'];
            $tempRow['status'] = $row['status'];
            $tempRow['message'] = html_escape($row['message']);
            $tempRow['txn_date'] = $row['transaction_date'];
            $tempRow['date'] = $row['date_created'];
            $tempRow['operate'] = $operate;

            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    function get_transactions($id = '', $user_id = '', $transaction_type = '', $search = '', $offset = '0', $limit = '25', $sort = 'id', $order = 'DESC')
    {
        $where = $multiple_where = [];
        $count_sql = $this->db->select(' COUNT(id) as `total`');
        if (!empty($user_id)) {
            $where['user_id'] = $user_id;
        }

        if ($transaction_type != '') {
            $where['transaction_type'] = $transaction_type;
        }

        // if ($type != '') {
        //     $where['type'] = $type;
        // }

        if ($id !== '') {
            $where['id'] = $id;
        }

        if ($search !== '') {
            $multiple_where = [
                'id' => $search,
                'transaction_type' => $search,
                'type' => $search,
                'order_id' => $search,
                'txn_id' => $search,
                'amount' => $search,
                'status' => $search,
                'message' => $search,
                'transaction_date' => $search,
                'date_created' => $search,
            ];
        }

        if (isset($where) && !empty($where)) {
            $count_sql->where($where);
        }

        if (isset($multiple_where) && !empty($multiple_where)) {
            $count_sql->group_start();  //group start
            $count_sql->or_like($multiple_where);
            $count_sql->group_end();  //group end
        }

        $count = $count_sql->get('transactions')->result_array();
        $total = $count[0]['total'];

        /* query for transactions list */
        $transactions_sql = $this->db->select('*');
        if (isset($where) && !empty($where)) {
            $transactions_sql->where($where);
        }

        if (isset($multiple_where) && !empty($multiple_where)) {
            $transactions_sql->group_start();  //group start
            $transactions_sql->or_like($multiple_where);
            $transactions_sql->group_end();  //group end
        }

        if ($limit != '' && $offset !== '') {
            $transactions_sql->limit($limit, $offset);
        }

        $transactions_sql->order_by($sort, $order);
        $q = $this->db->get('transactions');

        $transactions['data'] = $q->result_array();
        if (!empty($transactions['data'])) {
            for ($i = 0; $i < count($transactions['data']); $i++) {
                $transactions['data'][$i]['order_id'] = ($transactions['data'][$i]['order_id'] != null) ? $transactions['data'][$i]['order_id'] : "";
                $transactions['data'][$i]['order_item_id'] = ($transactions['data'][$i]['order_item_id'] != null) ? $transactions['data'][$i]['order_item_id'] : "";
                $transactions['data'][$i]['txn_id'] = ($transactions['data'][$i]['txn_id'] != null) ? $transactions['data'][$i]['txn_id'] : "";
                $transactions['data'][$i]['status'] = ($transactions['data'][$i]['status'] != null) ? $transactions['data'][$i]['status'] : "";
                $transactions['data'][$i]['payu_txn_id'] = ($transactions['data'][$i]['payu_txn_id'] != null) ? $transactions['data'][$i]['payu_txn_id'] : "";
                $transactions['data'][$i]['currency_code'] = ($transactions['data'][$i]['currency_code'] != null) ? $transactions['data'][$i]['currency_code'] : "";
                $transactions['data'][$i]['payer_email'] = ($transactions['data'][$i]['payer_email'] != null) ? $transactions['data'][$i]['payer_email'] : "";
            }
        }
        $transactions['total'] = $total;
        return $transactions;
    }

    function edit_transactions($data)
    {
        $data = escape_array($data);

        $t_data = [
            'id' => $data['id'],
            'status' => $data['status'],
            'txn_id' => $data['txn_id'],
            'message' => $data['message'],
        ];

        return $this->db->set($t_data)->where('id', $data['id'])->update('transactions');
    }
    function get_withdrawal_transactions_list($user_id = '')
    {
        // print_r($_GET);
        $sort = 'id';
        $order = 'ASC';
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];



        if (!empty($user_id)) {
            $user_where = ['user_id' => $user_id];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($user_where) && !empty($user_where)) {
            $count_res->where($user_where);
        }

        $txn_count = $count_res->get('payment_requests')->result_array();
        foreach ($txn_count as $row) {
            $total = $row['total'];
        }
        $search_res = $this->db->select(' * ');
        $search_res->where($user_where);
        $txn_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('payment_requests')->result_array();


        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();


        $username = fetch_details('users', ['id' => $user_id], 'username');
        foreach ($txn_search_res as $row) {
            $row = output_escaping($row);

            $tempRow['id'] = $row['id'];
            $tempRow['name'] = $username[0]['username'];
            $tempRow['payment_address'] = $row['payment_address'];
            $tempRow['amount_requested'] = $row['amount_requested'];
            $status = [
                '0' => '<span class="badge badge-secondary">Pending</span>',
                '1' => '<span class="badge badge-success">Approved</span>',
                '2' => '<span class="badge badge-danger">Rejected</span>',
            ];
            $tempRow['status'] = $status[$row['status']];
            $tempRow['date_created'] = $row['date_created'];
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
