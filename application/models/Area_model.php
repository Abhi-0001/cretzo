<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Area_model extends CI_Model
{

    function add_city($data)
    {
        $data = escape_array($data);
        // cities' real name/PK columns are city_name/city_id, not name/id - this previously
        // threw a raw "Unknown column" database error on every single Add/Edit City save.
        $city_data = [
            'city_name' => $data['city_name'],
        ];
        if (isset($data['edit_city'])) {
            return $this->db->set($city_data)->where('city_id', $data['edit_city'])->update('cities');
        } else {
            return $this->db->insert('cities', $city_data);
        }
    }
    function add_zipcode($data)
    {
        $data = escape_array($data);
        $zipcode_data = [
            'zipcode' => $data['zipcode'],
            'city_id' => $data['city'],
            'minimum_free_delivery_order_amount' => $data['minimum_free_delivery_order_amount'],
            'delivery_charges' => $data['delivery_charges'],
        ];
        if (isset($data['edit_zipcode'])) {
            return $this->db->set($zipcode_data)->where('id', $data['edit_zipcode'])->update('zipcodes');
        } else {
            return $this->db->insert('zipcodes', $zipcode_data);
        }
    }
    function add_area($data)
    {
        $data = escape_array($data);

        $area_data = [
            'name' => $data['area_name'],
            'city_id' => $data['city'],
            'zipcode_id' => $data['zipcode'],
            'minimum_free_delivery_order_amount' => $data['minimum_free_delivery_order_amount'],
            'delivery_charges' => $data['delivery_charges'],
        ];

        if (isset($data['edit_area'])) {
            return $this->db->set($area_data)->where('id', $data['edit_area'])->update('areas');
        } else {
            return $this->db->insert('areas', $area_data);
        }
    }
    function bulk_edit_area($data)
    {
        $data = escape_array($data);

        $area_data = [
            'minimum_free_delivery_order_amount' => $data['bulk_update_minimum_free_delivery_order_amount'],
            'delivery_charges' => $data['bulk_update_delivery_charges'],
        ];
        return $this->db->set($area_data)->where('city_id', $data['city'])->update('areas');
    }
    public function get_list($table, $offset = 0, $limit = 10, $sort = 'u.id')
    {
        $multipleWhere = '';
        $order = 'asc';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Whitelist against the actual selected columns - $_GET['sort'] was previously passed
        // straight into order_by() unchecked for any value other than "id" (an injection
        // shape), and $_GET['order'] was read but never actually applied below (hardcoded
        // "asc" regardless of what was requested).
        if ($table == 'areas') {
            $allowed_sort_columns = ['id' => 'areas.id', 'name' => 'areas.name', 'city_name' => 'cities.city_name', 'zipcode' => 'zipcodes.zipcode', 'minimum_free_delivery_order_amount' => 'areas.minimum_free_delivery_order_amount', 'delivery_charges' => 'areas.delivery_charges'];
        } elseif ($table == 'cities') {
            $allowed_sort_columns = ['id' => 'city_id', 'name' => 'city_name'];
        } else {
            $allowed_sort_columns = ['id' => 'id', 'name' => 'name'];
        }
        if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sort_columns)) {
            $sort = $allowed_sort_columns[$_GET['sort']];
        } else {
            $sort = $allowed_sort_columns['id'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            if ($table == 'areas') {
                // cities' own primary/name columns are city_id/city_name (not id/name) —
                // areas.id/areas.name and zipcodes.* are unaffected, they use plain id/name.
                $multipleWhere = ['areas.id' => $search, 'areas.name' => $search, 'cities.city_name' => $search, 'areas.minimum_free_delivery_order_amount' => $search, 'areas.delivery_charges' => $search, 'zipcodes.zipcode' => $search];
            } elseif ($table == 'cities') {
                $multipleWhere = ['cities.city_name' => $search, 'cities.city_id' => $search];
            } else {
                $multipleWhere = ['id' => $search, 'name' => $search];
            }
        }
        if ($table == 'areas') {
            $count_res = $this->db->select(' COUNT(areas.id) as `total` ')->join('cities', 'areas.city_id=cities.city_id')->join('zipcodes', 'areas.zipcode_id=zipcodes.id');
        } elseif ($table == 'cities') {
            $count_res = $this->db->select(' COUNT(city_id) as `total` ');
        } else {
            $count_res = $this->db->select(' COUNT(id) as `total` ');
        }


        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $city_count = $count_res->get($table)->result_array();

        foreach ($city_count as $row) {
            $total = $row['total'];
        }

        if ($table == 'areas') {
            $search_res = $this->db->select(' areas.* , cities.city_name as city_name , zipcodes.zipcode as zipcode')->join('cities', 'areas.city_id=cities.city_id')->join('zipcodes', 'areas.zipcode_id=zipcodes.id');
        } else {
            $search_res = $this->db->select(' * ');
        }

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
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
            // cities rows come back keyed by city_id/city_name, not id/name.
            $row_id = ($table == 'cities') ? $row['city_id'] : $row['id'];
            $row_name = ($table == 'cities') ? $row['city_name'] : $row['name'];
            if (!$this->ion_auth->is_seller()) {
                // The Edit button was commented out here - the list's Action column only ever
                // showed Delete, with no way to open the Edit modal (the row's own edit_btn
                // markup is what custom.js's generic .edit_btn handler loads into the modal).
                $operate = ' <a href="javascript:void(0)" class="edit_btn action-btn btn btn-success btn-xs mr-1 mb-1 ml-1" title="Edit" data-id="' . (int) $row_id . '" data-url="admin/area/' . $url . '"><i class="fa fa-pen"></i></a>';
                $operate .= '  <a  href="javascript:void(0)" class=" btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1" title="Delete" id="delete-location" data-table="' . $table . '" data-id="' . (int) $row_id . '" ><i class="fa fa-trash"></i></a>';
            }
            $tempRow['id'] = $row_id;
            // Was run through output_escaping() only - despite the name, that helper does NOT
            // HTML-encode anything (just stripcslashes()), so a name/zipcode containing a
            // script tag rendered as live, unescaped HTML in every admin's browser (stored XSS).
            $tempRow['name'] = html_escape($row_name);
            if ($table == 'areas') {
                $tempRow['city_name'] = html_escape($row['city_name']);
                $tempRow['zipcode'] = html_escape($row['zipcode']);
                $tempRow['minimum_free_delivery_order_amount'] = $row['minimum_free_delivery_order_amount'];
                $tempRow['delivery_charges'] = $row['delivery_charges'];
            }
            if (!$this->ion_auth->is_seller()) {

                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    function get_zipcode_list()
    {
        $offset = 0;
        $limit = 10;
        $sort = 'zipcodes.id';
        $order = 'asc';
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Whitelist against the actual selected columns - $_GET['sort'] was previously passed
        // straight into order_by() unchecked (an injection shape), and $_GET['order'] was read
        // but never actually applied below (hardcoded "asc" regardless).
        $allowed_sort_columns = ['id' => 'zipcodes.id', 'zipcode' => 'zipcodes.zipcode', 'city_name' => 'cities.city_name', 'minimum_free_delivery_order_amount' => 'zipcodes.minimum_free_delivery_order_amount', 'delivery_charges' => 'zipcodes.delivery_charges'];
        if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sort_columns)) {
            $sort = $allowed_sort_columns[$_GET['sort']];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            // Was wrapping the whole "table.column" string in one pair of backticks
            // ('`zipcodes.id`') - CI3 sees the existing backtick and skips re-quoting, so this
            // was sent to MySQL as a single, nonexistent identifier literally named
            // "zipcodes.id", throwing a database error on every search on this page.
            $multipleWhere = ['zipcodes.id' => $search, 'zipcodes.zipcode' => $search];
        }

        $count_res = $this->db->select(' COUNT(zipcodes.id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            // Was or_where() here (exact match) against the same $multipleWhere the data
            // query below matches with or_like() (partial match) - a partial search term
            // returned real rows from the data query but a mismatched (often 0) count from
            // this query, breaking the table's reported total/pagination.
            $count_res->or_like($multipleWhere);
        }

        $tax_count = $count_res->get('zipcodes')->result_array();

        foreach ($tax_count as $row) {
            $total = $row['total'];
        }

        if (!$this->db->field_exists('city_id', 'zipcodes')) {
            $search_res = $this->db->select(' * ');
        } else {
            // cities' own PK/name columns are city_id/city_name, not id/name.
            $search_res = $this->db->select(' zipcodes.* ,cities.city_name as city_name')->join('cities', 'zipcodes.city_id=cities.city_id', 'left');
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }

        $tax_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('zipcodes')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($tax_search_res as $row) {
            if (!$this->ion_auth->is_seller()) {
                $operate = ' <a href="javascript:void(0)" class="edit_btn btn action-btn btn-success btn-xs mr-1 mb-1 ml-1"  title="Edit" data-id="' . (int) $row['id'] . '" data-url="admin/area/manage_zipcodes"><i class="fa fa-pen"></i></a>';
                $operate .= ' <a  href="javascript:void(0)" class="btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1"  title="Delete" id="delete-zipcode" data-id="' . (int) $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            }
            $tempRow['id'] = $row['id'];
            // Was run through output_escaping() only - despite the name, that helper does
            // NOT HTML-encode anything (just stripcslashes()), so a zipcode/city name
            // containing a script tag rendered as live, unescaped HTML in every admin's
            // browser viewing this list (stored XSS).
            $tempRow['zipcode'] = html_escape($row['zipcode']);
            if (!$this->db->field_exists('city_id', 'zipcodes')) {
                $tempRow['city_name'] = '';
                $tempRow['minimum_free_delivery_order_amount'] = 0;
                $tempRow['delivery_charges'] = 0;
            }else{
                $tempRow['city_name'] = html_escape($row['city_name']);
                $tempRow['minimum_free_delivery_order_amount'] = $row['minimum_free_delivery_order_amount'];
                $tempRow['delivery_charges'] = $row['delivery_charges'];
            }
            if (!$this->ion_auth->is_seller()) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }


    function get_zipcodes($search = '', $limit = NULL, $offset = NULL, $state_id = null)
    {
        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                '`zipcode`' => $search
            ];
        }

        $count_res = $this->db->select(' COUNT(zipcodes.id) as `total`');
        if ($state_id !== null) {
            $count_res->join('cities', 'cities.city_id=zipcodes.city_id')->join('districts', 'districts.id=cities.district_id')->where('districts.state_id', $state_id);
        }

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }


        $cat_count = $count_res->get('zipcodes')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('zipcodes.*');
        if ($state_id !== null) {
            $search_res->join('cities', 'cities.city_id=zipcodes.city_id')->join('districts', 'districts.id=cities.district_id')->where('districts.state_id', $state_id);
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $cat_search_res = $search_res->limit($limit, $offset)->get('zipcodes')->result_array();
        $rows = $tempRow = $bulkData = array();
        $bulkData['error'] = (empty($cat_search_res)) ? true : false;
        $bulkData['message'] = (empty($cat_search_res)) ? 'Pincodes(s) does not exist' : 'Pincodes retrieved successfully';
        $bulkData['total'] = (empty($cat_search_res)) ? 0 : $total;
        if (!empty($cat_search_res)) {
            foreach ($cat_search_res as $row) {
                $row = output_escaping($row);
                $tempRow['id'] = $row['id'];
                $tempRow['zipcode'] = $row['zipcode'];
                $tempRow['date_created'] = $row['date_created'];
                $rows[] = $tempRow;
            }
            $bulkData['data'] = $rows;
        } else {
            $bulkData['data'] = [];
        }
        return $bulkData;
    }

    /* Deliverable locations : lists the seller's own products along with the
       zipcodes each of them can be shipped to. Prints the bootstrap-table payload. */
    function get_seller_deliverable_products($seller_id)
    {
        $offset = (isset($_GET['offset'])) ? (int) $_GET['offset'] : 0;
        $limit = (isset($_GET['limit'])) ? (int) $_GET['limit'] : 10;
        $sort = (isset($_GET['sort']) && in_array($_GET['sort'], ['id', 'name', 'deliverable_type'])) ? $_GET['sort'] : 'id';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) == 'asc') ? 'ASC' : 'DESC';
        $search = (isset($_GET['search'])) ? trim($_GET['search']) : '';

        $this->db->from('products')->where('products.seller_id', $seller_id)->where('products.type !=', 'digital_product');
        if ($search != '') {
            $this->db->group_start()->like('products.name', $search)->or_like('products.id', $search)->group_end();
        }
        $total = $this->db->count_all_results('', false);

        $products = $this->db->select('products.id, products.name, products.image, products.status, products.deliverable_type, products.deliverable_zipcodes')
            ->order_by('products.' . $sort, $order)->limit($limit, $offset)->get()->result_array();

        // resolve every zipcode id used on this page in a single query
        $zipcode_ids = [];
        foreach ($products as $row) {
            foreach (explode(',', (string) $row['deliverable_zipcodes']) as $id) {
                if (trim($id) != '') {
                    $zipcode_ids[] = (int) $id;
                }
            }
        }
        $zipcode_map = [];
        if (!empty($zipcode_ids)) {
            $zipcodes = $this->db->select('id, zipcode')->where_in('id', array_unique($zipcode_ids))->get('zipcodes')->result_array();
            foreach ($zipcodes as $zipcode) {
                $zipcode_map[$zipcode['id']] = $zipcode['zipcode'];
            }
        }

        $labels = [
            NONE => ['Not deliverable', 'danger'],
            ALL => ['All locations', 'success'],
            INCLUDED => ['Only selected zipcodes', 'info'],
            EXCLUDED => ['All except selected', 'warning'],
        ];

        $rows = [];
        foreach ($products as $row) {
            $row = output_escaping($row);
            $type = (string) $row['deliverable_type'];
            list($label, $badge) = isset($labels[$type]) ? $labels[$type] : ['Not deliverable', 'danger'];

            $selected = [];
            foreach (explode(',', (string) $row['deliverable_zipcodes']) as $id) {
                $id = (int) trim($id);
                if ($id && isset($zipcode_map[$id])) {
                    $selected[$id] = $zipcode_map[$id];
                }
            }

            if ($type == INCLUDED || $type == EXCLUDED) {
                if (empty($selected)) {
                    $zipcode_html = '<span class="text-muted">No zipcodes selected</span>';
                } else {
                    $shown = array_slice($selected, 0, 6);
                    $zipcode_html = '';
                    foreach ($shown as $zipcode) {
                        $zipcode_html .= '<span class="badge badge-light border mr-1 mb-1">' . html_escape($zipcode) . '</span>';
                    }
                    if (count($selected) > count($shown)) {
                        $zipcode_html .= '<span class="badge badge-secondary mb-1">+' . (count($selected) - count($shown)) . ' more</span>';
                    }
                }
            } else {
                $zipcode_html = '<span class="text-muted">&mdash;</span>';
            }

            $tempRow = [];
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = '<div class="d-flex align-items-center">'
                . '<img src="' . base_url(!empty($row['image']) ? $row['image'] : 'assets/no-image.png') . '" onerror="this.src=\'' . base_url('assets/no-image.png') . '\'" style="width:38px;height:38px;object-fit:cover;border-radius:6px;margin-right:10px;">'
                . '<span>' . html_escape($row['name']) . '</span></div>';
            $tempRow['deliverable_type'] = '<span class="badge badge-' . $badge . '">' . $label . '</span>';
            $tempRow['deliverable_zipcodes'] = $zipcode_html;
            $tempRow['operate'] = '<a href="javascript:void(0)" class="btn btn-success btn-xs action-btn edit-deliverable-location" title="Edit"'
                . ' data-id="' . $row['id'] . '"'
                . ' data-name="' . html_escape($row['name']) . '"'
                . ' data-type="' . $type . '"'
                . ' data-zipcodes=\'' . htmlspecialchars(json_encode(array_map(function ($id, $zipcode) {
                    return ['id' => (string) $id, 'text' => $zipcode];
                }, array_keys($selected), $selected)), ENT_QUOTES, 'UTF-8') . '\'><i class="fa fa-pen"></i></a>';
            $rows[] = $tempRow;
        }

        $bulkData = ['total' => $total, 'rows' => $rows];
        print_r(json_encode($bulkData));
    }

    /* Resolves a free-text state name (as stored on seller_data.state) to a states.id, case/whitespace-insensitive. */
    function get_state_id_by_name($state_name)
    {
        if (trim((string) $state_name) === '') {
            return null;
        }
        $row = $this->db->where('LOWER(TRIM(name)) =', strtolower(trim($state_name)))->get('states')->row_array();
        return $row ? (int) $row['id'] : null;
    }

    /* True only if every given zipcode id belongs to a city/district under the given state. */
    function zipcodes_belong_to_state($zipcode_ids, $state_id)
    {
        $zipcode_ids = array_values(array_unique(array_filter(array_map('intval', (array) $zipcode_ids))));
        if (empty($zipcode_ids) || empty($state_id)) {
            return false;
        }
        $matched = $this->db->select('zipcodes.id')
            ->join('cities', 'cities.city_id=zipcodes.city_id')
            ->join('districts', 'districts.id=cities.district_id')
            ->where('districts.state_id', $state_id)
            ->where_in('zipcodes.id', $zipcode_ids)
            ->get('zipcodes')->num_rows();
        return $matched === count($zipcode_ids);
    }

    /* Applies a deliverable type (+ zipcode list) to the given products, scoped to the seller who owns them. */
    function update_seller_deliverable_products($seller_id, $product_ids, $deliverable_type, $zipcode_ids)
    {
        $product_ids = array_values(array_filter(array_map('intval', (array) $product_ids)));
        if (empty($product_ids)) {
            return 0;
        }

        $update = ['deliverable_type' => (int) $deliverable_type];
        if ($deliverable_type == INCLUDED || $deliverable_type == EXCLUDED) {
            $zipcode_ids = array_values(array_unique(array_filter(array_map('intval', (array) $zipcode_ids))));
            $update['deliverable_zipcodes'] = implode(',', $zipcode_ids);
        } else {
            $update['deliverable_zipcodes'] = NULL;
        }

        $this->db->where('seller_id', $seller_id)->where_in('id', $product_ids)->update('products', $update);
        return $this->db->affected_rows();
    }

    function get_area_by_city($city_id, $sort = "a.name", $order = "ASC", $search = "", $limit = '', $offset = '')
    {
        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                '`z.zipcode`' => $search
            ];
        }
        if ($city_id != '') {
            $where['a.city_id'] = $city_id;
        }
        if ($this->db->field_exists('minimum_free_delivery_order_amount', 'zipcodes')) {
            $areas = fetch_details('zipcodes', ['city_id' => $city_id], 'zipcode,id');
        }else{
            $search_res = $this->db->select('z.zipcode,z.id as id')->join('zipcodes z', 'z.id=a.zipcode_id');
            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $search_res->group_start();
                $search_res->or_like($multipleWhere);
                $search_res->group_end();
            }
            $areas = $search_res->where('city_id', $city_id)->order_by($sort, $order)->limit($limit, $offset)->get('areas a')->result_array();
        }
       
        $bulkData = array();
        $bulkData['error'] = (empty($areas)) ? true : false;
        if (!empty($areas)) {
            for ($i = 0; $i < count($areas); $i++) {
                $areas[$i] = output_escaping($areas[$i]);
            }
        }
        $bulkData['data'] = (empty($areas)) ? [] : $areas;
        return $bulkData;
    }

    function get_cities_list($search = "")
    {
        // Fetch cities
        $this->db->select('*');
        $this->db->like('city_name', $search);
        $fetched_records = $this->db->get('cities');
        $cities = $fetched_records->result_array();

        // Initialize Array with fetched data
        $data = array();
        foreach ($cities as $city) {
            $data[] = array("id" => $city['city_id'], "text" => $city['city_name']);
        }
        return $data;
    }

    // The cities table's real columns are city_id / city_name - it has no `id` or
    // `name` column at all. Every reference here used c.id / c.name, so this query
    // failed outright with "Unknown column 'c.id' in 'on clause'". That made
    // app/v1/api/get_cities, seller/app/v1/api/get_cities and admin/app/v1/api/get_cities
    // return a Database Error page instead of JSON - an HTTP 200 whose body was
    // unparseable to the mobile app. Also fixed the `'`c.name`'` search key, which was
    // quoted as one identifier (so it could never match a column) rather than c.name.
    function get_cities($sort = "c.city_name", $order = "ASC", $search = "", $limit = '', $offset = '')
    {
        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                'c.city_name' => $search
            ];
        }

        // Guard against a caller passing the old c.name / c.id sort values.
        $allowed_sort = ['c.city_name', 'c.city_id', 'c.district_id'];
        $sort_map = ['c.name' => 'c.city_name', 'c.id' => 'c.city_id', 'name' => 'c.city_name', 'id' => 'c.city_id'];
        if (isset($sort_map[$sort])) {
            $sort = $sort_map[$sort];
        }
        if (!in_array($sort, $allowed_sort, true)) {
            $sort = 'c.city_name';
        }
        $order = (strtolower((string) $order) === 'desc') ? 'DESC' : 'ASC';

        $search_res = $this->db->select('c.*')->join('areas a', 'c.city_id=a.city_id', "left");

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }
        // Second, independent bug here: callers that omit $limit (e.g.
        // seller/app/v1/api/get_cities, which passes only sort/order/search) fell through to
        // the '' default, and CI casts that to (int) 0 -> "LIMIT 0" -> zero rows. That
        // endpoint therefore always answered {"error":true,"data":[]} even with cities in
        // the table. Only apply a limit when one was actually requested.
        $search_res->group_by('c.city_id')->order_by($sort, $order);
        if (is_numeric($limit) && (int) $limit > 0) {
            $search_res->limit((int) $limit, (is_numeric($offset) && $offset >= 0) ? (int) $offset : 0);
        }
        $cities = $search_res->get('cities c')->result_array();
        $bulkData = array();
        $bulkData['error'] = (empty($cities)) ? true : false;
        if (!empty($cities)) {
            for ($i = 0; $i < count($cities); $i++) {
                $cities[$i] = output_escaping($cities[$i]);
            }
        }
        $bulkData['data'] = (empty($cities)) ? [] : $cities;
        return $bulkData;
    }

    function get_zipcode($search = "")
    {
        // Fetch users
        $this->db->select('*');
        // Was raw string concatenation of $search directly into a where() string with no
        // escaping at all - a real SQL injection reachable via this select2 search endpoint.
        $this->db->like('zipcode', $search);
        $fetched_records = $this->db->get('zipcodes');
        $zipcodes = $fetched_records->result_array();

        // Initialize Array with fetched data
        $data = array();
        foreach ($zipcodes as $zipcode) {
            $data[] = array("id" => $zipcode['id'], "text" => $zipcode['zipcode']);
        }
        return $data;
    }
    public function get_countries_list(
        $offset = 0,
        $limit = 10,
        $sort = 'id',
        $order = 'ASC'
    ) {
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Whitelist against the actual selected columns - $_GET['sort'] was previously passed
        // straight into order_by() unchecked for any value other than "id" (an injection shape).
        $allowed_sort_columns = ['id', 'numeric_code', 'name', 'capital', 'phonecode', 'currency', 'currency_name', 'currency_symbol'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['numeric_code' => $search, 'name' => $search, 'currency' => $search];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $attr_count = $count_res->get('countries')->result_array();

        foreach ($attr_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('*');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $city_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('countries')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($city_search_res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['numeric_code'] = html_escape($row['numeric_code']);
            $tempRow['name'] = html_escape($row['name']);
            $tempRow['capital'] = html_escape($row['capital']);
            $tempRow['phonecode'] = html_escape($row['phonecode']);
            $tempRow['currency'] = html_escape($row['currency']);
            $tempRow['currency_name'] = html_escape($row['currency_name']);
            $tempRow['currency_symbol'] = html_escape($row['currency_symbol']);
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
