<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription_model extends CI_Model
{

    public function add_subscription($data)
    {

        $data = escape_array($data);

        $subscription_data = [

            'name' => isset($data['name']) ? $data['name'] : null,
            'price' => isset($data['price']) ? $data['price'] : null,
            'listings_limit' => isset($data['listings_limit']) ? $data['listings_limit'] : null,
            'validity' => isset($data['validity']) ? $data['validity'] : null,

            // No commission here any more: commission is set per CATEGORY (Admin > Categories >
            // Category Commission). The old plan slab columns are left in the table, unwritten
            // and unread, so migration 086 stays reversible.

            // IMPORTANT FIX
            'features' => isset($data['features']) ? $data['features'] : '[]'

        ];


        /* ---------- UPDATE ---------- */

        if (!empty($data['edit_subscription'])) {

            $this->db->where('id', $data['edit_subscription']);
            $this->db->update('subscriptions', $subscription_data);

            return $data['edit_subscription'];

        }


        /* ---------- INSERT ---------- */

        $this->db->insert('subscriptions', $subscription_data);

        return $this->db->insert_id();

    }



    public function get_plans()
    {

        return $this->db
            ->order_by('id', 'ASC')
            ->get('subscriptions')
            ->result_array();

    }



    public function get_list($table = 'subscriptions', $offset = 0, $limit = 10, $sort = 'id')
    {

        $offset = $this->input->get('offset') ?? $offset;
        $limit = $this->input->get('limit') ?? $limit;

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['id', 'name', 'price', 'listings_limit', 'validity'];
        $requested_sort = $this->input->get('sort');
        $sort = in_array($requested_sort, $allowed_sort_columns, true) ? $requested_sort : $sort;
        $order = (strtolower((string) $this->input->get('order')) === 'desc') ? 'desc' : 'asc';

        $search = $this->input->get('search');

        if (!empty($search)) {

            $this->db->group_start();
            $this->db->like('id', $search);
            $this->db->or_like('name', $search);
            $this->db->or_like('price', $search);
            $this->db->or_like('listings_limit', $search);
            $this->db->or_like('validity', $search);
            $this->db->group_end();

        }

        $total = $this->db->count_all_results($table, false);

        $result = $this->db
            ->order_by($sort, $order)
            ->limit($limit, $offset)
            ->get()
            ->result_array();


        $rows = [];

        foreach ($result as $row) {

            $row = unslash($row);

            $operate = '';

            if (!$this->ion_auth->is_seller()) {

                $operate .= '<a href="javascript:void(0)" 
                class="edit_btn action-btn btn btn-success btn-xs mr-1 mb-1 ml-1"
                title="Edit"
                data-id="'.$row['id'].'"
                data-url="admin/subscription/manage_subscriptions">
                <i class="fa fa-pen"></i></a>';

                $operate .= '<a href="javascript:void(0)" 
                class="btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1"
                title="Delete"
                id="delete-location"
                data-table="'.$table.'"
                data-id="'.$row['id'].'">
                <i class="fa fa-trash"></i></a>';

            }


            $tempRow = [];

            $tempRow['id'] = $row['id'];
            // output_escaping() only strips backslash-escaping, it does not HTML-encode - a
            // stored-XSS route the same as already fixed on other list pages.
            $tempRow['name'] = html_escape($row['name']);
            $tempRow['price'] = $row['price'];
            $tempRow['listings_limit'] = $row['listings_limit'];
            $tempRow['validity'] = $row['validity'];

            if (!$this->ion_auth->is_seller()) {
                $tempRow['operate'] = $operate;
            }

            $rows[] = $tempRow;

        }

        $bulkData = [
            'total' => $total,
            'rows' => $rows
        ];

        echo json_encode($bulkData);

    }



    public function get_plan($id)
    {

        if (empty($id)) {
            return null;
        }

        return $this->db
            ->where('id', $id)
            ->get('subscriptions')
            ->row_array();

    }

}