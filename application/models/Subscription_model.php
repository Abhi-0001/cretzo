<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription_model extends CI_Model
{
    /**
     * insert or update subscription record
     * expects an array from POST with keys matching column names
     */
    public function add_subscription($data)
    {
        $data = escape_array($data);

        $subscription_data = [
            'name'       => $data['name'] ?? null,
            'price'      => $data['price'] ?? null,
            'listings_limit' => $data['listings_limit'] ?? null,
            'validity'   => $data['validity'] ?? null,
            'commission_first50'  => isset($data['commission_first50']) ? $data['commission_first50'] : null,
            'commission_51_100'   => isset($data['commission_51_100']) ? $data['commission_51_100'] : null,
            'commission_after100' => isset($data['commission_after100']) ? $data['commission_after100'] : null,
        ];
        if (isset($data['edit_subscription'])) {
            $this->db->set($subscription_data)->where('id', $data['edit_subscription'])->update('subscriptions');
        } else {
            $this->db->insert('subscriptions', $subscription_data);
        }
    }

    public function get_plans()
    {
        return $this->db->order_by('id', 'ASC')->get('subscriptions')->result_array();
    }

    /**
     * server-side listing for bootstrap table
     */
    public function get_list($table = 'subscriptions', $offset = 0, $limit = 10, $sort = 'id')
    {
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        $order = isset($_GET['order']) ? $_GET['order'] : 'asc';

        $multipleWhere = '';
        if (isset($_GET['search']) && $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = [
                'id' => $search,
                'name' => $search,
                'price' => $search,
                'listings_limit' => $search,
                'validity' => $search,
            ];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');
        if (!empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        $total = 0;
        $count_query = $count_res->get($table)->result_array();
        foreach ($count_query as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('*');
        if (!empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }

        $result = $search_res->order_by($sort, $order)->limit($limit, $offset)->get($table)->result_array();
        $bulkData = ['total' => $total];
        $rows = [];
        foreach ($result as $row) {
            $row = output_escaping($row);
            $operate = '';
            if (!$this->ion_auth->is_seller()) {
                $operate .= '<a href="javascript:void(0)" class="edit_btn action-btn btn btn-success btn-xs mr-1 mb-1 ml-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/subscription/manage_subscriptions"><i class="fa fa-pen"></i></a>';
                $operate .= '<a href="javascript:void(0)" class="btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1" title="Delete" id="delete-location" data-table="' . $table . '" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></a>';
            }
            $tempRow = [];
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = $row['name'];
            $tempRow['price'] = $row['price'];
            $tempRow['listings_limit'] = $row['listings_limit'];
            $tempRow['validity'] = $row['validity'];
            $tempRow['commission_first50'] = $row['commission_first50'];
            $tempRow['commission_51_100'] = $row['commission_51_100'];
            $tempRow['commission_after100'] = $row['commission_after100'];
            if (!$this->ion_auth->is_seller()) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    public function get_plan($id)
    {
        if (empty($id)) {
            return null;
        }

        return $this->db->where('id', $id)->get('subscriptions')->row_array();
    }
}
