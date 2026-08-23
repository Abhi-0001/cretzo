<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Address_model extends CI_Model
{

    function set_address($data)
    {
        // Every value here used to be escaped TWICE before the query builder escaped it a third
        // time - escape_array() on the whole $data array here, and escape_array() again on
        // $address_data at the insert below. Both run db->escape_str(), which is only correct
        // for a string being pasted straight into SQL text; the query builder parameter-escapes
        // on its own. Reproduced live: saving a delivery address for "O'Brien Test" at
        // "5 O'Brien Road, St. Mary's Colony" stored the name as "O\\\'Brien Test" and the
        // street as "5 O\\\'Brien Road, St. Mary\\\'s Colony". That text is what gets copied
        // onto the order, the invoice and the shipping label, so any customer with an
        // apostrophe in their name or street - which is common - had a mangled address printed
        // on the parcel. Editing the address re-escaped it again on every save.

        $address_data = [];
       
        if (isset($data['user_id'])) {
            $address_data['user_id'] = $data['user_id'];
        }
        if (isset($data['id'])) {
            $address_data['id'] = $data['id'];
        }
        if (isset($data['type'])) {
            $address_data['type'] = $data['type'];
        }
        if (isset($data['name'])) {
            $address_data['name'] = $data['name'];
        }
        if (isset($data['mobile'])) {
            $address_data['mobile'] = $data['mobile'];
        }
        $address_data['country_code'] = (isset($data['country_code']) && !empty($data['country_code']) && is_numeric($data['country_code'])) ? $data['country_code'] : 0;

        if (isset($data['alternate_mobile'])) {
            $address_data['alternate_mobile'] = $data['alternate_mobile'];
        }

        if (isset($data['address'])) {
            $address_data['address'] = $data['address'];
        }

        if (isset($data['landmark'])) {
            $address_data['landmark'] = $data['landmark'];
        }
        // Read unconditionally even when the caller (e.g. set_default_address(), which only
        // ever posts an id) has neither key - a guaranteed warning on every such call.
        $city = isset($data['city_id']) ? fetch_details('cities', ['city_id' => $data['city_id']], 'city_name') : [];
        $area = isset($data['area_id']) ? fetch_details('areas', ['id' => $data['area_id']], 'name') : [];

        if (isset($data['general_area_name'])) {
            // $address_data['general_area_name'] = isset($data['general_area_name']) && !empty($data['general_area_name']) ? $data['general_area_name'] : '';
            $address_data['area'] = isset($data['general_area_name']) && !empty($data['general_area_name']) ? $data['general_area_name'] : '';
        }
        if (isset($data['edit_general_area_name'])) {
            // $address_data['general_area_name'] = isset($data['general_area_name']) && !empty($data['general_area_name']) ? $data['general_area_name'] : '';
            $address_data['area'] = isset($data['edit_general_area_name']) && !empty($data['edit_general_area_name']) ? $data['edit_general_area_name'] : '';
        }
        // if (isset($data['area_id'])) {
        //     $address_data['area_id'] = isset($data['area_id']) && !empty($data['area_id']) ? $data['area_id'] : 0;
        //     $address_data['area'] = isset($area) && !empty($area) ?$area[0]['name'] : '';
        // }
        /*
         * city / city_id.
         *
         * Two things were wrong here.
         *
         * 1. `city_id` was written straight from the POST with no check that the id exists. The
         *    address form is pincode-first: it fills the `city_name` text box from the pincode
         *    lookup and leaves the hidden city_id field at whatever it already held. `cities` is
         *    seeded with 18 demo cities (Mumbai, Pune, Bangalore, ...) and holds no South Delhi
         *    or Kotdwara, so the lookup could not set it and a stale 1 was saved instead - 11 of
         *    the 14 addresses on this database point at Mumbai, and 3 at a city_id of 0.
         *    Everything that joins on it (admin order filtering by city, the local-delivery zone
         *    lookup, the invoice city) therefore read the wrong city or none.
         *
         *    So the id is now validated, and preferably DERIVED from the city text the customer
         *    actually gave - which keeps the two columns telling the same story. A city that is
         *    not in the table stores 0 rather than a wrong id: honestly absent beats wrong, and
         *    every read path falls back to the `city` text.
         *
         * 2. Four of these lines fell back to $city[0]['city_name'] / $area[0]['name'] without
         *    checking the lookup found anything - the exact case that happens whenever city_id
         *    is 0 or stale - so an empty city_name posted alongside an unresolvable city_id
         *    raised "Undefined array key 0". Also `&` (bitwise) where `&&` was meant.
         */
        $city_text = '';
        if (isset($data['city_name']) && trim((string) $data['city_name']) !== '') {
            $city_text = trim((string) $data['city_name']);
        } elseif (isset($data['other_city']) && trim((string) $data['other_city']) !== '') {
            $city_text = trim((string) $data['other_city']);
        } elseif (!empty($city)) {
            $city_text = $city[0]['city_name'];
        }

        if ($city_text !== '') {
            $address_data['city'] = $city_text;
        }

        if (isset($data['city_id']) || $city_text !== '') {
            $resolved_city_id = 0;

            // The city TEXT is authoritative whenever there is one: derive the id from it so the
            // two columns can never disagree. Checking only that a posted id exists is not
            // enough - a stale hidden city_id of 1 does resolve (to Mumbai), which is exactly
            // how "New Delhi" ended up filed under Mumbai on 11 addresses.
            if ($city_text !== '') {
                $matched = $this->db->select('city_id')
                    ->where('LOWER(city_name)', strtolower($city_text))
                    ->get('cities')->row_array();
                $resolved_city_id = !empty($matched['city_id']) ? (int) $matched['city_id'] : 0;
            } elseif (!empty($data['city_id']) && !empty($city)) {
                // No text supplied at all - an existing posted id is the only thing to go on.
                $resolved_city_id = (int) $data['city_id'];
            }

            $address_data['city_id'] = $resolved_city_id;
        }

        if (isset($data['area_name']) && !empty($data['area_name'])) {
            $address_data['area'] = $data['area_name'];
        } elseif (isset($data['area_name']) && !empty($area)) {
            $address_data['area'] = $area[0]['name'];
        }
        if (isset($data['other_areas']) && !empty($data['other_areas'])) {
            $address_data['area'] = $data['other_areas'];
        }
        if (isset($data['pincode_name']) || isset($data['pincode'])) {
            $address_data['system_pincode'] = (isset($data['pincode_name']) && !empty($data['pincode_name'])) ? 0 : 1 ;
            $address_data['pincode'] = (isset($data['pincode_name']) && !empty($data['pincode_name'])) ? $data['pincode_name'] : $data['pincode'];
        }
        

        // if (isset($data['pincode'])) {
        //     $address_data['pincode'] = $data['pincode'];
        // }

        if (isset($data['state'])) {
            $address_data['state'] = $data['state'];
        }

        if (isset($data['country'])) {
            $address_data['country'] = $data['country'];
        }
        if (isset($data['latitude'])) {
            $address_data['latitude'] = $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $address_data['longitude'] = $data['longitude'];
        }

        if (isset($data['id']) && !empty($data['id'])) {
            if (isset($data['is_default']) && $data['is_default'] == true) {
                $address = fetch_details('addresses', ['id' => $data['id']], '*');
                $this->db->where('user_id', $address[0]['user_id'])->set(['is_default' => '0'])->update('addresses');
                $this->db->where('id', $data['id'])->set(['is_default' => '1'])->update('addresses');
            }

            $this->db->set($address_data)->where('id', $data['id'])->update('addresses');
        } else {
            $this->db->insert('addresses', $address_data);
            $last_added_id = $this->db->insert_id();

            // A customer's very first address was never marked default unless the client
            // happened to ask for it, so a newly registered buyer ended up with exactly one
            // address and no default one. Make the first address the default.
            $is_first_address = isset($data['user_id'])
                && $this->db->where('user_id', $data['user_id'])->count_all_results('addresses') === 1;

            if ((isset($data['is_default']) && $data['is_default'] == true) || $is_first_address) {
                $this->db->where('user_id', $data['user_id'])->set('is_default', '0')->update('addresses');
                $this->db->where('id', $last_added_id)->set('is_default', '1')->update('addresses');
            }
        }
    }

    function delete_address($data)
    {
        $this->db->delete('addresses', ['id' => $data['id']]);
    }

    function get_address($user_id, $id = false, $fetch_latest = false, $is_default = false)
    {
        $where = [];
        if (isset($user_id) || $id != false) {
            if (isset($user_id) && $user_id != null && !empty($user_id)) {
                $where['user_id'] = $user_id;
            }
            if ($id != false) {
                $where['addr.id'] = $id;
            }
            $this->db->select('addr.*')
                ->where($where)
                ->group_by('addr.id')->order_by('addr.id', 'DESC');
            if ($fetch_latest == true) {
                $this->db->limit('1');
            }
            if (!empty($is_default)) {
                $this->db->where('is_default', 1);
            }
            $res = $this->db->get('addresses addr')->result_array();

            if (!empty($res)) {
                for ($i = 0; $i < count($res); $i++) {
                    $area_id = (isset($res[$i]['area_id']) && ($res[$i]['area_id']) != 0) ? $res[$i]['area_id'] : "";
                    $minimum_free_delivery_order_amount =  fetch_details('areas', ['id' => $area_id], 'minimum_free_delivery_order_amount,delivery_charges');
                    $amount = !empty($minimum_free_delivery_order_amount) ? $minimum_free_delivery_order_amount[0]['minimum_free_delivery_order_amount'] : null;
                    $delivery_charges = !empty($minimum_free_delivery_order_amount) ? $minimum_free_delivery_order_amount[0]['delivery_charges'] : null;
                    $res[$i] = output_escaping($res[$i]);
                    $res[$i]['minimum_free_delivery_order_amount'] = (isset($amount) && $amount != NULL) ? "$amount" : "0";
                    $res[$i]['delivery_charges'] = (isset($delivery_charges) && $delivery_charges != NULL) ? "$delivery_charges" : "0";
                }
            }
            return $res;
        }
    }

    public function get_address_list($user_id = '', $print = true, $include_action_btns = true)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';

        if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $where['user_id'] = $_GET['user_id'];
        }

        if (!empty($user_id)) {
            $where['user_id'] = $user_id;
        }

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Whitelist against the actual selected columns - $_GET['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id', 'name', 'type', 'mobile', 'alternate_mobile', 'landmark', 'area', 'city', 'state', 'pincode', 'country'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        } else {
            $order = 'asc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['addr.name' => $search, 'addr.address' => $search, 'mobile' => $search, 'area' => $search, 'city' => $search, 'state' => $search, 'country' => $search, 'pincode' => $search];
        }

        $count_res = $this->db->select(' COUNT(addr.id) as `total` ,addr.*');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $address_count = $count_res->get('addresses addr')->result_array();

        foreach ($address_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('addr.*');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            // Was grouping on $count_res (already finalized above) instead of $search_res -
            // the OR-search terms never actually got their parentheses on the real data query.
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $address_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('addresses addr')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($address_search_res as $row) {

            $row = output_escaping($row);
            $default = $row['is_default'] == 1 ? 'Default' : 'Set as default';
            $btn = $row['is_default'] == 1 ? 'info' : 'secondary';
            $class = $row['is_default'] == 1 ? '' : 'default-address ';
            
            $tempRow['id'] = $row['id'];
            // output_escaping() only strips backslash-escaping, it does not HTML-encode - a
            // stored-XSS route on this admin list the same as already fixed on other pages.
            $tempRow['name'] = html_escape($row['name']);
            $tempRow['type'] = html_escape($row['type']);
            $tempRow['mobile'] = (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) ? str_repeat("X", strlen($row['mobile']) - 3) . substr($row['mobile'], -3) : $row['mobile'];
            $tempRow['alternate_mobile'] = $row['alternate_mobile'];
            $tempRow['address'] = html_escape($row['address']);
            $tempRow['landmark'] = html_escape($row['landmark']);
            $tempRow['area'] = html_escape($row['area']);
            $tempRow['area_id'] = $row['area_id'];
            $tempRow['city'] = html_escape($row['city']);
            $tempRow['city_id'] = $row['city_id'];
            $tempRow['state'] = html_escape($row['state']);
            $tempRow['pincode'] = $row['pincode'];
            $tempRow['system_pincode'] = $row['system_pincode'];
            $tempRow['pincode_name'] = $row['pincode'];
            $tempRow['country'] = html_escape($row['country']);
            $tempRow['is_default'] = $row['is_default'];

            if($include_action_btns){
                $operate = '<a href="javascript:void(0)" class="edit-address btn btn-success btn-xs mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-toggle="modal" data-target="#address-modal"><i class="fa fa-pen uil uil-pen"></i></a>';
                $operate .= '<a href="javascript:void(0)" class="delete-address btn btn-danger btn-xs mr-1 mb-1" title="Delete" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></a>';
                $operate .= '<a href="javascript:void(0)" class="' . $class . ' btn btn-' . $btn . ' btn-xs mr-1 mb-1" title="' . $default . '" data-id="' . $row['id'] . '"><i class="fa fa-check-square"></i></a>';
                $tempRow['action'] = $operate;
            }
            
            // if default address, add it to the beginning of the array
            if($row['is_default'] == 1){
                array_unshift($rows, $tempRow);
            }
            else{
                $rows[] = $tempRow;
            }
        }
        $bulkData['rows'] = $rows;
        if($print){
            print_r(json_encode($bulkData));
        }
        else{
            return $bulkData;
        }
    }
}
