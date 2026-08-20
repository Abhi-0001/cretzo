<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Pickup_location_model extends CI_Model
{

    /**
     * @return array ['error' => bool, 'message' => string, 'shiprocket' => mixed]
     *
     * Returned a value for the first time here. The Shiprocket registration result used to be
     * discarded entirely, and the local row was written first and unconditionally - so when
     * Shiprocket rejected the address (duplicate nickname, unserviceable pincode, malformed
     * phone) the platform ended up holding a pickup location the courier had never heard of.
     * Nothing surfaced that. The seller saw "Add Pickup Location", chose it on a product, and
     * then every shipment booked from it failed at create-order time with Shiprocket's
     * "Wrong Pickup location entered" - with no way to connect that back to this step.
     */
    function add_pickup_location($data)
    {
        /*
         * escape_array() is deliberately NOT used here.
         *
         * CI's query builder already parameter-escapes everything passed to insert()/set(), so
         * running escape_array() first escapes it TWICE and the backslashes are persisted: an
         * address for "12 O'Brien Lane" was stored as "12 O\'Brien Lane" and shown that way on
         * every screen. Verified live before this change. The same double-escaping also reached
         * Shiprocket, so the address registered with the courier - and printed on the shipping
         * label - carried literal backslashes too.
         */
        $raw = $data;

        $columns = [
            'seller_id'       => 'seller_id',
            'pickup_location' => 'pickup_location',
            'name'            => 'name',
            'email'           => 'email',
            'phone'           => 'phone',
            'address'         => 'address',
            'address_2'       => 'address2',
            'city'            => 'city',
            'state'           => 'state',
            'country'         => 'country',
            'pin_code'        => 'pincode',
            'latitude'        => 'latitude',
            'longitude'       => 'longitude',
        ];

        $pickup_location_data = [];
        $shiprocket_payload = [];
        foreach ($columns as $column => $post_key) {
            // latitude/longitude/address2 are optional on both forms; reading them unguarded
            // warned "Undefined array key" on every save that left them blank.
            $pickup_location_data[$column] = isset($raw[$post_key]) ? $raw[$post_key] : '';
            $shiprocket_payload[$column] = isset($raw[$post_key]) ? $raw[$post_key] : '';
        }

        // Shiprocket has no use for our internal seller id, and rejects unexpected empties on
        // the coordinate fields.
        unset($shiprocket_payload['seller_id']);
        foreach (['latitude', 'longitude', 'address_2'] as $optional) {
            if ($shiprocket_payload[$optional] === '' || $shiprocket_payload[$optional] === null) {
                unset($shiprocket_payload[$optional]);
            }
        }

        if (isset($raw['edit_pickup_location'])) {
            // Scoped to this seller on the row being matched, not just the seller_id being
            // written — previously the WHERE ignored ownership entirely, so any seller could
            // overwrite another seller's pickup location by id, and since seller_id was part
            // of the SET data, the row was simultaneously reassigned to the caller's account.
            $this->db->set($pickup_location_data)->where(['id' => $raw['edit_pickup_location'], 'seller_id' => $pickup_location_data['seller_id']])->update('pickup_locations');
            return ['error' => false, 'message' => 'Pickup location updated.', 'shiprocket' => null];
        }

        if (!$this->db->insert('pickup_locations', $pickup_location_data)) {
            return ['error' => true, 'message' => 'Could not save the pickup location.', 'shiprocket' => null];
        }

        // Register it with Shiprocket. Only attempted when Shiprocket shipping is actually the
        // configured method - otherwise this made a pointless authenticated API call (and logged
        // a credentials error) on every pickup location added by a store that ships locally.
        $shipping = get_settings('shipping_method', true);
        if (empty($shipping['shiprocket_shipping_method']) || $shipping['shiprocket_shipping_method'] != 1) {
            return ['error' => false, 'message' => 'Pickup location added.', 'shiprocket' => null];
        }

        $this->load->library(['Shiprocket']);
        $result = $this->shiprocket->add_pickup_location($shiprocket_payload);

        // Shiprocket answers a successful addpickup with success = 1. Anything else - including a
        // transport failure, which now returns null - means the courier does not have this
        // address, and shipments booked from it will fail later.
        $accepted = is_array($result) && (
            (isset($result['success']) && $result['success'])
            || (isset($result['status']) && (int) $result['status'] === 200)
            || isset($result['address']['pickup_code'])
        );

        if (!$accepted) {
            $reason = $this->shiprocket->last_error();
            if (empty($reason) && is_array($result) && !empty($result['message'])) {
                $reason = is_string($result['message']) ? $result['message'] : json_encode($result['message']);
            }
            log_message('error', 'Shiprocket rejected pickup location "' . $pickup_location_data['pickup_location']
                . '": ' . ($reason !== '' ? $reason : json_encode($result)));

            return [
                'error'      => true,
                'message'    => 'Saved here, but Shiprocket did not accept this pickup address'
                    . ($reason ? ' (' . $reason . ')' : '')
                    . '. Shipments booked from it will fail until it is corrected.',
                'shiprocket' => $result,
            ];
        }

        return ['error' => false, 'message' => 'Pickup location added.', 'shiprocket' => $result];
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
