<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Pickup_location_model extends CI_Model
{

    function add_pickup_location($data)
    {
        $data = escape_array($data);
        $pickup_location_data = [
            'seller_id' => $data['seller_id'],
            'pickup_location' => $data['pickup_location'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'address_2' => $data['address2'],
            'city' => $data['city'],
            'state' => $data['state'],
            'country' => $data['country'],
            'pin_code' => $data['pincode'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ];
        if (isset($data['edit_pickup_location'])) {
            // Scoped to this seller on the row being matched, not just the seller_id being
            // written — previously the WHERE ignored ownership entirely, so any seller could
            // overwrite another seller's pickup location by id, and since seller_id was part
            // of the SET data, the row was simultaneously reassigned to the caller's account.
            $this->db->set($pickup_location_data)->where(['id' => $data['edit_pickup_location'], 'seller_id' => $data['seller_id']])->update('pickup_locations');
        } else {
            $this->db->insert('pickup_locations', $pickup_location_data);

            //    send add_pickup_location request in shiprocket

            $this->load->library(['Shiprocket']);
            $this->shiprocket->add_pickup_location($pickup_location_data);
        }
    }

    public function get_list($table, $where = NULL, $seller_id = 0, $from_app = false)
    {

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';
        $where = [];

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_POST['offset']))
            $offset = $_POST['offset'];

        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        if (isset($_POST['limit']))
            $limit = $_POST['limit'];

        // Whitelist against the actual selected columns - $_GET/$_POST['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id', 'pickup_location', 'name', 'email', 'phone', 'address', 'address_2', 'city', 'state', 'country', 'pin_code', 'status'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_POST['sort']) && in_array($_POST['sort'], $allowed_sort_columns, true)) {
            $sort = $_POST['sort'];
        }

        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        }
        if (isset($_POST['order']) && strtolower($_POST['order']) === 'desc') {
            $order = 'desc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            if ($table == 'pickup_locations') {
                $multipleWhere = ['pickup_locations.id' => $search, 'pickup_locations.pickup_location' => $search, 'pickup_locations.email' => $search, 'pickup_locations.phone' => $search];
            }
        }
        if (isset($_POST['search']) and $_POST['search'] != '') {
            $search = $_POST['search'];
            if ($table == 'pickup_locations') {
                $multipleWhere = ['pickup_locations.id' => $search, 'pickup_locations.pickup_location' => $search, 'pickup_locations.email' => $search, 'pickup_locations.phone' => $search];
            }
        }
        if (isset($_GET['seller_id']) and $_GET['seller_id'] != '') {
            $where = ['seller_id' => $_GET['seller_id']];
        }
        if (isset($seller_id) && $seller_id != 0) {
            $where = ['seller_id' => $seller_id];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');



        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_Start();
            $count_res->or_like($multipleWhere);
            $this->db->group_End();
        }


        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $city_count = $count_res->get($table)->result_array();

        foreach ($city_count as $row) {
            $total = $row['total'];
        }


        $search_res = $this->db->select(' * ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_Start();
            $search_res->or_like($multipleWhere);
            $this->db->group_End();
        }

        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $city_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get($table)->result_array();
        
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $url = 'manage_' . $table;
        foreach ($city_search_res as $row) {

            $row = output_escaping($row);
            if ($this->ion_auth->is_admin()) {
                $operate = ' <a href="javascript:void(0)" class="edit_btn  btn action-btn image.png btn-success btn-xs mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/Pickup_location/' . $url . '"><i class="fa fa-pen"></i></a>';

                if ($row['status'] == '1') {
                    $verify = '<a class="btn btn-success btn-xs update_active_status mr-1" data-table="pickup_locations" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fas fa-check-square"></i></a>';
                } else {
                    $verify = '<a class="btn btn-danger mr-1 btn-xs update_active_status" data-table="pickup_locations" href="javascript:void(0)" title="Active" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fas fa-times"></i></a>';
                }
                $operate .= '  <a  href="javascript:void(0)" class=" btn action-btn image.png btn-danger btn-xs mr-1 mb-1" title="Delete" id="delete-location" data-table="' . $table . '" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            }
            $tempRow['id'] = $row['id'];
            $tempRow['seller_id'] = $row['seller_id'];
            $tempRow['pickup_location'] = html_escape($row['pickup_location']);
            $tempRow['name'] = html_escape($row['name']);
            $tempRow['email'] = html_escape($row['email']);
            $tempRow['phone'] = html_escape($row['phone']);
            $tempRow['address'] = html_escape($row['address']);
            $tempRow['address2'] = html_escape($row['address_2']);
            $tempRow['city'] = html_escape($row['city']);
            $tempRow['state'] = html_escape($row['state']);
            $tempRow['country'] = html_escape($row['country']);
            $tempRow['pin_code'] = html_escape($row['pin_code']);
            if ($this->ion_auth->is_admin()) {
                $tempRow['verified'] = $verify;
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        if ($from_app == true) {
            return $bulkData;
        } else {
            print_r(json_encode($bulkData));
        }
    }
}
