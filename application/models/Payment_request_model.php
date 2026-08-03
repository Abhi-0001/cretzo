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

    function get_payment_request_list($user_id = NULL)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'pr.id';
        $order = 'ASC';
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

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
                $operate = '<a href="javascript:void(0)" class="edit_request action-btn btn btn-success btn-xs mr-1 mb-1 ml-1" title="Edit" data-target="#payment_request_modal" data-toggle="modal" ><i class="fa fa-pen"></i></a>';
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
                '0' => '<span class="badge badge-success">Pending</span>',
                '1' => '<span class="badge badge-primary">Approved</span>',
                '2' => '<span class="badge badge-danger">Rejected</span>',
            ];

            $tempRow['status'] = $status[$row['status']];
            $tempRow['status_digit'] = $row['status'];
            $tempRow['remarks'] = html_escape($row['remarks']);
            if (!isset($user_id) && empty($user_id)) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }


    function update_payment_request($data)
    {

        $data = escape_array($data);
        $request = array(
            'status' => $data['status'],
            'remarks' => (isset($data['update_remarks']) && !empty($data['update_remarks'])) ? $data['update_remarks'] : null,
        );
        $amount = fetch_details("payment_requests", ['id' => $data['payment_request_id']], "amount_requested,user_id,status");

        // The wallet credit and the status flip were two separate, unwrapped writes - if the
        // process died between them, a retry could see the still-pending status and credit
        // the wallet a second time. Wrapped so both happen atomically or not at all.
        $this->db->trans_start();
        if ($data['status'] == 2 && $amount[0]['status'] != 2) {
            update_balance($amount[0]['amount_requested'], $amount[0]['user_id'], "add");
        }
        $this->db->where('id', $data['payment_request_id'])->update('payment_requests', $request);
        $this->db->trans_complete();

        return $this->db->trans_status() !== FALSE;
    }
}
