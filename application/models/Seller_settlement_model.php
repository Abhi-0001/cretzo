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

    public function record_settlement($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return insert_details($data, 'seller_settlements');
    }

    function get_settlement_list($seller_id = NULL)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        if (isset($_GET['sort']) && $_GET['sort'] != '')
            $sort = $_GET['sort'];
        if (isset($_GET['order']) && $_GET['order'] != '')
            $order = $_GET['order'];

        $where = ['seller_id' => $seller_id];

        if (isset($_GET['search']) && $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['id' => $search, 'order_id' => $search, 'settlement_status' => $search];
        }

        $count_res = $this->db->select('COUNT(id) as `total`')->where($where);
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }
        $total = (int) $count_res->get('seller_settlements')->row_array()['total'];

        $search_res = $this->db->where($where);
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        $rows_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('seller_settlements')->result_array();

        $status_badge = [
            'settled' => '<span class="badge badge-success">Settled</span>',
            'failed'  => '<span class="badge badge-danger">Failed</span>',
        ];

        $rows = array();
        foreach ($rows_res as $row) {
            $row = output_escaping($row);
            $rows[] = [
                'id' => $row['id'],
                'order_id' => $row['order_id'],
                'order_amount' => $row['order_amount'],
                'commission_percent' => $row['commission_percent'],
                'commission_amount' => $row['commission_amount'],
                'net_payable' => $row['net_payable'],
                'settlement_status' => isset($status_badge[$row['settlement_status']]) ? $status_badge[$row['settlement_status']] : $row['settlement_status'],
                'created_at' => $row['created_at'],
            ];
        }

        $bulkData = array();
        $bulkData['total'] = $total;
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
