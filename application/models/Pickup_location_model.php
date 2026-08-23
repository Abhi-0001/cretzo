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
            /*
             * An edit can make Shiprocket's copy of this address stale.
             *
             * Shiprocket holds pickup addresses by nickname on its own side; editing the row
             * here changed only the local copy, while `shiprocket_verified_at` stayed stamped.
             * Everything downstream reads that stamp as "Shiprocket has confirmed this address"
             * (see shiprocket_pickup_is_bookable / resolve_seller_pickup_location), so after an
             * edit the platform would keep booking against an address Shiprocket no longer
             * matches - parcels quoted and labelled from the OLD address.
             *
             * So: if any field that identifies the address changed, the confirmation no longer
             * holds and is cleared. Re-syncing it (Admin > Pickup Location > import from
             * Shiprocket) re-stamps whatever Shiprocket actually has. A cosmetic edit - the
             * contact name, say - leaves the confirmation alone.
             */
            $existing = $this->db->select('address, address_2, city, state, country, pin_code, phone, pickup_location')
                ->where(['id' => $raw['edit_pickup_location'], 'seller_id' => $pickup_location_data['seller_id']])
                ->get('pickup_locations')->row_array();

            $address_changed = false;
            if (!empty($existing)) {
                foreach (['pickup_location', 'address', 'address_2', 'city', 'state', 'country', 'pin_code', 'phone'] as $field) {
                    if ((string) $existing[$field] !== (string) $pickup_location_data[$field]) {
                        $address_changed = true;
                        break;
                    }
                }
            }

            if ($address_changed && $this->db->field_exists('shiprocket_verified_at', 'pickup_locations')) {
                $pickup_location_data['shiprocket_verified_at'] = null;
            }

            // Scoped to this seller on the row being matched, not just the seller_id being
            // written — previously the WHERE ignored ownership entirely, so any seller could
            // overwrite another seller's pickup location by id, and since seller_id was part
            // of the SET data, the row was simultaneously reassigned to the caller's account.
            $this->db->set($pickup_location_data)->where(['id' => $raw['edit_pickup_location'], 'seller_id' => $pickup_location_data['seller_id']])->update('pickup_locations');

            if ($address_changed) {
                return [
                    'error'   => false,
                    'message' => 'Pickup location updated. Because the address changed, it needs to be '
                        . 're-registered with Shiprocket before shipments can be booked from it.',
                    'shiprocket' => null,
                ];
            }

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

        /*
         * Record that Shiprocket accepted it.
         *
         * Nothing set this on insert - only the admin's "import from Shiprocket" sync did - so a
         * pickup location a seller added, and that Shiprocket accepted right here, was still
         * left with shiprocket_verified_at NULL. Everything that decides whether an address can
         * be booked from reads that column, so a perfectly good new address counted as
         * unconfirmed and shipments from it were refused until an admin happened to run the
         * sync. Stamped from the same response that told us it was accepted.
         */
        if ($this->db->field_exists('shiprocket_verified_at', 'pickup_locations')) {
            $new_id = $this->db->insert_id();
            if (!empty($new_id)) {
                $verified = ['shiprocket_verified_at' => date('Y-m-d H:i:s')];
                // Shiprocket will not schedule a pickup from an address whose phone it has not
                // verified, and it reports that back on the address it just registered.
                if (isset($result['address']['phone_verified']) && $this->db->field_exists('phone_verified', 'pickup_locations')) {
                    $verified['phone_verified'] = ((int) $result['address']['phone_verified'] === 1) ? 1 : 0;
                }
                $this->db->set($verified)->where('id', $new_id)->update('pickup_locations');
            }
        }

        return ['error' => false, 'message' => 'Pickup location added.', 'shiprocket' => $result];
    }

    /**
     * Deletes a seller's own pickup location, scoped by (id, seller_id) so one seller cannot
     * delete another's row by guessing an id.
     *
     * products.pickup_location stores this row's NICKNAME, not its id (see Product_model), so
     * deleting the row here would silently orphan any product still pointing at that nickname -
     * its pickup location would read as a name nothing in `pickup_locations` matches anymore.
     * Blocked the same way the admin subscription-plan delete guards against deleting a plan
     * sellers are still on.
     */
    public function delete_pickup_location($id, $seller_id)
    {
        $location = $this->db->select('pickup_location')
            ->where(['id' => $id, 'seller_id' => $seller_id])
            ->get('pickup_locations')->row_array();

        if (empty($location)) {
            return ['error' => true, 'message' => 'Pickup location not found.'];
        }

        $products_using_it = $this->db->where(['seller_id' => $seller_id, 'pickup_location' => $location['pickup_location']])
            ->count_all_results('products');

        if ($products_using_it > 0) {
            return [
                'error'   => true,
                'message' => 'This pickup location is used by ' . $products_using_it . ' product'
                    . ($products_using_it > 1 ? 's' : '') . '. Change their pickup location before deleting it.',
            ];
        }

        $this->db->where(['id' => $id, 'seller_id' => $seller_id])->delete('pickup_locations');
        return ['error' => false, 'message' => 'Pickup location deleted.'];
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
            } elseif ($table === 'pickup_locations' && $this->ion_auth->is_seller()) {
                // Sellers get their own Edit/Delete/Hide set, scoped to rows they own by the
                // controllers behind these URLs. Re-activating (status 0 -> 1) is deliberately
                // NOT offered here - only admin's own list can do that, because a pickup
                // location only reaches status 1 after Shiprocket has accepted the address (see
                // Pickup_location_model::add_pickup_location), and letting a seller flip it back
                // on themselves would bypass that check.
                $seller_operate = '<a href="' . base_url('seller/pickup_location/manage_pickup_locations?edit_id=' . $row['id']) . '" class="btn btn-info btn-xs mr-1 mb-1" title="Edit"><i class="fa fa-pen"></i></a>';

                if ($row['status'] == '1') {
                    $seller_operate .= ' <a href="javascript:void(0)" class="btn btn-success btn-xs update_active_status mr-1 mb-1" data-table="pickup_locations" title="Deactivate" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '"><i class="fas fa-check-square"></i></a>';
                } else {
                    $seller_operate .= ' <span class="badge badge-secondary mr-1" title="Inactive - contact an admin to re-activate it">Inactive</span>';
                }

                $seller_operate .= ' <a href="javascript:void(0)" class="btn btn-danger btn-xs delete-pickup-location mr-1 mb-1" title="Delete" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></a>';
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
            } elseif ($table === 'pickup_locations' && $this->ion_auth->is_seller()) {
                $tempRow['operate'] = $seller_operate;
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
