<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Product extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['product_model', 'category_model', 'rating_model']);

        if (!has_permissions('read', 'product')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }
    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-product';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Product Management | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Product Management |' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('product_faqs', ['id' => $_GET['edit_id']]);
            }
            $this->data['categories'] = $this->category_model->get_categories();
            // Added the store name so the seller filter reads "username - store name", the same
            // as the seller dropdown on the Add/Edit Product screen. With multiple sellers using
            // similar usernames, a username-only list is not enough to tell them apart.
            $this->data['sellers'] = array_map(function ($row) {
                return array_map(function ($value) {
                    return is_string($value) ? stripslashes($value) : $value;
                }, $row);
            }, $this->db->select(' u.username as seller_name, u.id as seller_id, sd.category_ids, COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name, sd.id as seller_data_id ')
                ->join('users_groups ug', ' ug.user_id = u.id ')
                ->join('seller_data sd', ' sd.user_id = u.id ')
                ->where(['ug.group_id' => '4'])
                ->get('users u')->result_array());
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function create_product()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'product';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Add Product | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Product | ' . $settings['app_name'];
            $this->data['taxes'] = fetch_details('taxes', null, '*');
            $this->data['countries'] = fetch_details('countries', null, 'name,id');
            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $product_row = fetch_details('products', ['id' => $_GET['edit_id']], 'seller_id');
                $seller_id = !empty($product_row) ? $product_row[0]['seller_id'] : 0;
                $this->data['shipping_data'] = fetch_details('pickup_locations', ['status' => 1, 'seller_id' => $seller_id], 'id,pickup_location');
            } else {
                // Previously this listed the pickup locations of EVERY seller on the platform, so
                // a product being created for one seller could be assigned another seller's
                // pickup address. The list is now loaded for the chosen seller only, once that
                // seller has been selected.
                $this->data['shipping_data'] = [];
            }
            $this->data['brands'] = fetch_details('brands', null, 'name,id');

            /* $this->data['sellers'] = $this->db->select(' u.username as seller_name,u.id as seller_id,sd.category_ids,sd.store_name,sd.id as seller_data_id  ')
                ->join('users_groups ug', ' ug.user_id = u.id ')
                ->join('seller_data sd', ' sd.user_id = u.id ')
                ->where(['ug.group_id' => '4'])
                ->get('users u')->result_array(); */
                
            $this->data['sellers'] = array_map(function($row) {
                return array_map(function($value) {
                    return is_string($value) ? stripslashes($value) : $value;
                }, $row);
            }, $this->db->select(' u.username as seller_name, u.id as seller_id, sd.category_ids, COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name, sd.id as seller_data_id ')
                        ->join('users_groups ug', ' ug.user_id = u.id ')
                        ->join('seller_data sd', ' sd.user_id = u.id ')
                        ->where(['ug.group_id' => '4'])
                        ->get('users u')
                        ->result_array());

            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['title'] = 'Update Product | ' . $settings['app_name'];
                $this->data['meta_description'] = 'Update Product | ' . $settings['app_name'];
                $product_details = fetch_details('products', ['id' => $_GET['edit_id']], '*');
                $countries = fetch_details('countries', ['name' => $product_details[0]['made_in']], 'name');
                if (!empty($product_details)) {
                    $this->data['product_details'] = $product_details;
                    $this->data['product_variants'] = get_variants_values_by_pid($_GET['edit_id']);
                    // A simple / digital product keeps its price, weight and dimensions on one
                    // product_variants row. When that row was soft-removed (status 7) the default
                    // status filter hid it and the edit form opened blank. Only widen the filter
                    // for these types - a variable product must still list its live variants only.
                    if (empty($this->data['product_variants']) && !empty($product_details[0]['type']) && $product_details[0]['type'] != 'variable_product') {
                        $this->data['product_variants'] = get_variants_values_by_pid($_GET['edit_id'], [0, 1, 7]);
                    }
                    $product_attributes = fetch_details('product_attributes', ['product_id' => $_GET['edit_id']]);
                    if (!empty($product_attributes) && !empty($product_details)) {
                        $this->data['product_attributes'] = $product_attributes;
                    }
                } else {
                    redirect('admin/product/create_product', 'refresh');
                }
            }


            $attributes = $this->db->select('attr_val.id,attr.name as attr_name ,attr_set.name as attr_set_name,attr_val.value')
                ->join('attributes attr', 'attr.id=attr_val.attribute_id')
                ->join('attribute_set attr_set', 'attr_set.id=attr.attribute_set_id')
                ->where(['attr.status' => 1, 'attr_set.status' => 1])
                ->get('attribute_values attr_val')->result_array();

            $attributes_refind = array();

            for ($i = 0; $i < count($attributes); $i++) {
                if (!array_key_exists($attributes[$i]['attr_set_name'], $attributes_refind)) {
                    $attributes_refind[$attributes[$i]['attr_set_name']] = array();
                    for ($j = 0; $j < count($attributes); $j++) {
                        if ($attributes[$i]['attr_set_name'] == $attributes[$j]['attr_set_name']) {
                            if (!array_key_exists($attributes[$j]['attr_name'], $attributes_refind[$attributes[$i]['attr_set_name']])) {
                                $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']] = array();
                            }
                            $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']][$j]['id'] = $attributes[$j]['id'];
                            $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']][$j]['text'] = $attributes[$j]['value'];
                            $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']][$j]['data-values'] = $attributes[$j]['value'];
                            $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']] = array_values($attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']]);
                        }
                    }
                }
            }
            $this->data['categories'] = $this->category_model->get_categories();
            $this->data['attributes_refind'] = $attributes_refind;
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /**
     * Active pickup locations for one seller, for the product form's shipping dropdown.
     *
     * The form previously had no way to refresh this list, so choosing a different seller left
     * the previous seller's pickup addresses on screen and selectable.
     */
    public function get_seller_pickup_locations()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            echo json_encode(['error' => true, 'message' => 'Unauthorized', 'data' => []]);
            return false;
        }

        $seller_id = $this->input->get('seller_id', true);
        if (!is_numeric($seller_id)) {
            echo json_encode(['error' => true, 'message' => 'Invalid seller', 'data' => []]);
            return false;
        }

        $rows = fetch_details('pickup_locations', ['status' => 1, 'seller_id' => (int) $seller_id], 'id,pickup_location');
        $data = [];
        foreach ((array) $rows as $row) {
            $data[] = ['id' => $row['id'], 'pickup_location' => $row['pickup_location']];
        }

        echo json_encode(['error' => false, 'data' => $data]);
        return false;
    }

    public function product_order()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'product_order')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'products-order';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Product Order | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Product Order | ' . $settings['app_name'];
            $this->data['categories'] = $this->category_model->get_categories();
            // The full product list (every one of the 290+ rows in this catalogue, images
            // included) used to be fetched here and rendered directly into the page. The view
            // now fetches the same list itself, through search_category_wise_products(), so it
            // can render one consistent way whether that's the initial "All" load or a filtered
            // one, instead of the two diverging code paths described below.
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_variants_by_id()
    {
        $attr_values = array();
        $final_variant_ids = array();
        $variant_ids = json_decode($this->input->get('variant_ids'));
        $attributes_values = json_decode($this->input->get('attributes_values'));
        foreach ($attributes_values as $a => $b) {
            foreach ($b as $key => $value) {
                array_push($attr_values, $value);
            }
        }
        $res = $this->db->select('id,value')->where_in('id', $attr_values)->get('attribute_values')->result_array();

        for ($i = 0; $i < count($variant_ids); $i++) {
            for ($j = 0; $j < count($variant_ids[$i]); $j++) {
                $k = array_search($variant_ids[$i][$j], array_column($res, 'id'));
                $final_variant_ids[$i][$j] = $res[$k];
            }
        }
        $response['result'] = $final_variant_ids;
        print_r(json_encode($response));
    }

    public function fetch_attributes_by_id()
    {
        $variants = get_variants_values_by_pid($_GET['edit_id']);
        $res['attr_values'] = get_attribute_values_by_pid($_GET['edit_id']);
        $res['pre_selected_variants_names'] = (!empty($variants)) ? $variants[0]['attr_name'] : null;
        $res['pre_selected_variants_ids'] = $variants;
        $response['csrfName'] = $this->security->get_csrf_token_name();
        $response['csrfHash'] = $this->security->get_csrf_hash();
        $response['result'] = $res;
        print_r(json_encode($response));
    }

    public function fetch_attribute_values_by_id($id = NULL)
    {
        if (isset($id) && !empty($id)) {
            $aid = $id;
        } else {
            $aid = $_GET['id'];
        }
        $variant_ids = get_attribute_values_by_id($aid);
        print_r(json_encode($variant_ids));
    }

    public function fetch_variants_values_by_pid()
    {
        $res = get_variants_values_by_pid($_GET['edit_id']);
        $response['result'] = $res;
        print_r(json_encode($response));
    }



    public function update_product_order()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('update', 'product_order'), PERMISSION_ERROR_MSG, 'product_order', false)) {
                return false;
            }

            // Had no validation at all: a request with product_id missing, or not an array,
            // raised a PHP warning (foreach on null) or a fatal TypeError (foreach on a scalar)
            // depending on what was sent, and every id inside it was written straight into a
            // WHERE clause with no type check.
            if (!isset($_GET['product_id']) || !is_array($_GET['product_id'])) {
                $response['error'] = true;
                $response['message'] = 'No products to reorder';
                echo json_encode($response);
                return false;
            }

            $this->db->trans_start();
            $i = 0;
            foreach ($_GET['product_id'] as $row) {
                if (!is_numeric($row)) {
                    continue;
                }
                $this->db->where(['id' => (int) $row])->update('products', ['row_order' => $i]);
                $i++;
            }
            $this->db->trans_complete();

            $response['error'] = ($this->db->trans_status() === false);
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = $response['error'] ? 'Something went wrong. Please try again.' : 'Product order saved';

            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function search_category_wise_products()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            // $_GET['cat_id'] was read with no isset() check, and the category filter's OWN
            // join+or_where were fine in isolation, but the response built from them was raw,
            // un-escaped product data - the (customer- or seller-supplied) name went straight
            // into the page via jQuery's .html() on the client, and a product with no image
            // rendered a broken <img src="admin/"> pointing at the site root rather than a
            // placeholder. Confirmed live: 10 of this database's 290 products have no image at
            // all. Both are fixed at the source here so every caller gets safe data.
            $cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : 0;

            $this->db->select('p.id, p.name, p.image, p.row_order, p.status');
            if (is_numeric($cat_id) && (int) $cat_id > 0) {
                $this->db->group_start();
                $this->db->where('p.category_id', (int) $cat_id);
                $this->db->or_where('c.parent_id', (int) $cat_id);
                $this->db->group_end();
            }
            // Was an inner join. 177 of this database's 290 products reference a category_id
            // that no longer exists in the categories table (a deleted or otherwise orphaned
            // category), and an inner join silently excludes every one of them from the result.
            // Confirmed live: requesting "All" returned 113 products instead of 290. A left join
            // keeps them in the list - they simply never match the "in category X" filter below,
            // which is correct, since they have no valid category to match against.
            $rows = $this->db->order_by('row_order')->join('categories c', 'p.category_id = c.id', 'left')->get('products p')->result_array();

            $products = array();
            foreach ($rows as $row) {
                $products[] = [
                    'id' => (int) $row['id'],
                    'name' => html_escape((string) $row['name']),
                    'image' => get_image_url($row['image'], 'thumb', 'sm'),
                    'row_order' => (int) $row['row_order'],
                    'status' => (int) $row['status'],
                ];
            }

            echo json_encode(['error' => false, 'data' => $products]);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function delete_product()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('delete', 'product'), PERMISSION_ERROR_MSG, 'product')) {
                return false;
            }

            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $response['error'] = true;
                $response['message'] = 'Invalid product id';
                echo json_encode($response);
                return false;
            }
            $product_id = (int) $_GET['id'];

            // Also clears cart/favorites/faqs/ratings - see delete_product_cascade().
            $this->load->model('product_model');
            if ($this->product_model->delete_product_cascade($product_id)) {
                $response['error'] = false;
                $response['message'] = 'Deleted Successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something Went Wrong';
            }
            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_product()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_product_id'])) {
                if (print_msg(!has_permissions('update', 'product'), PERMISSION_ERROR_MSG, 'product')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'product'), PERMISSION_ERROR_MSG, 'product')) {
                    return false;
                }

                // A product added here is filed against a seller and counts towards that
                // seller's listing usage, but the plan's limit was only ever checked in the
                // seller's own panel - so this screen (and only this screen) could push a
                // seller past a cap they would then be stuck over. Enforced here too, with a
                // message pointing admin at the fix they actually have available.
                $quota_seller_id = $this->input->post('seller_id', true);
                if (!empty($quota_seller_id) && is_numeric($quota_seller_id)) {
                    $this->load->model('Seller_subscription_model');
                    $quota = $this->Seller_subscription_model->check_listing_quota($quota_seller_id, 1);
                    if (!$quota['allowed']) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = $this->Seller_subscription_model->quota_error_message($quota, 1, true)
                            . ' You can change or extend their plan under Subscriptions > Seller Subscriptions.';
                        print_r(json_encode($this->response));
                        return false;
                    }
                }
            }
            $this->form_validation->set_rules('pro_input_name', 'Product Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('short_description', 'Short Description', 'trim|required|xss_clean');
            $this->form_validation->set_rules('category_id', 'Category Id', 'trim|required|xss_clean', array('required' => 'Category is required'));
            $this->form_validation->set_rules('pro_input_tax', 'Tax', 'trim|xss_clean');
            $this->form_validation->set_rules('pro_input_image', 'Image', 'trim|required|xss_clean', array('required' => 'Image is required'));
            $this->form_validation->set_rules('made_in', 'Made In', 'trim|xss_clean');
            $this->form_validation->set_rules('brand', 'Brand', 'trim|xss_clean');
            $this->form_validation->set_rules('product_type', 'Product type', 'trim|required|xss_clean');
            $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|xss_clean');
            $this->form_validation->set_rules('total_allowed_quantity', 'Total Allowed Quantity', 'trim|xss_clean');
            $this->form_validation->set_rules('minimum_order_quantity', 'Minimum Order Quantity', 'trim|xss_clean');
            $this->form_validation->set_rules('quantity_step_size', 'Quantity Step Size', 'trim|xss_clean');
            $this->form_validation->set_rules('warranty_period', 'Warranty Period', 'trim|xss_clean');
            $this->form_validation->set_rules('guarantee_period', 'Guarantee Period', 'trim|xss_clean');
            $this->form_validation->set_rules('hsn_code', 'HSN_Code', 'trim|xss_clean');
            $this->form_validation->set_rules('video', 'Video', 'trim|xss_clean');
            $this->form_validation->set_rules('video_type', 'Video Type', 'trim|xss_clean');
            $this->form_validation->set_rules('deliverable_type', 'Deliverable Type', 'required|trim|xss_clean');

            if (isset($_POST['video_type']) && $_POST['video_type'] != '') {
                if ($_POST['video_type'] == 'youtube' || $_POST['video_type'] == 'vimeo') {
                    $this->form_validation->set_rules('video', 'Video link', 'trim|required|xss_clean', array('required' => " Please paste a %s in the input box. "));
                } else {
                    $this->form_validation->set_rules('pro_input_video', 'Video file', 'trim|required|xss_clean', array('required' => " Please choose a %s to be set. "));
                }
            }
            if (isset($_POST['download_allowed']) && $_POST['download_allowed'] != '' && !empty($_POST['download_allowed']) && $_POST['download_allowed'] == 'on') {
                $this->form_validation->set_rules('download_link_type', 'Download Link Type', 'required|xss_clean');
                if (isset($_POST['download_link_type']) && $_POST['download_link_type'] != '' && !empty($_POST['download_link_type']) && $_POST['download_link_type'] == 'self_hosted') {
                    $this->form_validation->set_rules('pro_input_zip', 'Zip file ', 'required|xss_clean');
                }
                if (isset($_POST['download_link_type']) && $_POST['download_link_type'] != '' && !empty($_POST['download_link_type']) && $_POST['download_link_type'] == 'add_link') {
                    $this->form_validation->set_rules('download_link', 'Digital Product URL/Link', 'required|xss_clean');
                }
            }

            if (isset($_POST['tags']) && $_POST['tags'] != '') {
                $_POST['tags'] = json_decode($_POST['tags'], 1);
                $tags = array_column($_POST['tags'], 'value');
                $_POST['tags'] = implode(",", $tags);
            }

            if (isset($_POST['is_cancelable']) && $_POST['is_cancelable'] == '1') {
                $this->form_validation->set_rules('cancelable_till', 'Till which status', 'trim|required|xss_clean');
            }
            if (isset($_POST['cod_allowed'])) {
                $this->form_validation->set_rules('cod_allowed', 'COD allowed', 'trim|xss_clean');
            }
            if (isset($_POST['is_prices_inclusive_tax'])) {
                $this->form_validation->set_rules('is_prices_inclusive_tax', 'Tax included in prices', 'trim|xss_clean');
            }
            // Read directly from $_POST before validation had run, so a request without this
            // field raised an "Undefined array key" warning on PHP 8 - and because the response
            // is JSON, that warning was emitted into the response body and broke the parse,
            // leaving the form with no error message at all.
            $deliverable_type = isset($_POST['deliverable_type']) ? $_POST['deliverable_type'] : '';
            if ($deliverable_type == INCLUDED || $deliverable_type == EXCLUDED) {
                $this->form_validation->set_rules('deliverable_zipcodes[]', 'Deliverable Zipcodes', 'trim|required|xss_clean');
            }

            // If product type is simple			
            if (isset($_POST['product_type']) && $_POST['product_type'] == 'simple_product' || $_POST['product_type'] == 'digital_product') {

                $this->form_validation->set_rules('simple_price', 'Price', 'trim|required|numeric|greater_than_equal_to[' . $this->input->post('simple_special_price') . ']|xss_clean');
                $this->form_validation->set_rules('simple_special_price', 'Special Price', 'trim|numeric|less_than_equal_to[' . $this->input->post('simple_price') . ']|xss_clean');


                if (isset($_POST['simple_product_stock_status']) && in_array($_POST['simple_product_stock_status'], array('0', '1')) && $_POST['product_type'] != 'digital_product') {

                    $this->form_validation->set_rules('product_sku', 'SKU', 'trim|xss_clean');
                    $this->form_validation->set_rules('product_total_stock', 'Total Stock', 'trim|required|numeric|xss_clean');
                    $this->form_validation->set_rules('simple_product_stock_status', 'Stock Status', 'trim|required|numeric|xss_clean');
                }
            } elseif (isset($_POST['product_type']) && $_POST['product_type'] == 'variable_product') { //If product type is variant	
                if (isset($_POST['variant_stock_status']) && $_POST['variant_stock_status'] == '0') {
                    if ($_POST['variant_stock_level_type'] == "product_level") {

                        $this->form_validation->set_rules('sku_pro_type', 'SKU', 'trim|xss_clean');
                        $this->form_validation->set_rules('total_stock_variant_type', 'Total Stock', 'trim|required|xss_clean');
                        $this->form_validation->set_rules('variant_stock_status', 'Stock Status', 'trim|required|xss_clean');
                        if (isset($_POST['variant_price']) && isset($_POST['variant_special_price'])) {
                            foreach ($_POST['variant_price'] as $key => $value) {
                                $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|xss_clean|greater_than_equal_to[' . $this->input->post('variant_special_price[' . $key . ']') . ']');
                                $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|xss_clean|less_than_equal_to[' . $this->input->post('variant_price[' . $key . ']') . ']');
                            }
                        } else {
                            $this->form_validation->set_rules('variant_price', 'Price', 'trim|required|numeric|xss_clean|greater_than_equal_to[' . $this->input->post('variant_special_price[0]') . ']');
                            $this->form_validation->set_rules('variant_special_price', 'Special Price', 'trim|numeric|xss_clean|less_than_equal_to[' . $this->input->post('variant_price') . ']');
                        }
                    } else {
                        if (isset($_POST['variant_price']) && isset($_POST['variant_special_price']) && isset($_POST['variant_sku']) && isset($_POST['variant_total_stock']) && isset($_POST['variant_stock_status'])) {
                            foreach ($_POST['variant_price'] as $key => $value) {
                                $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|xss_clean|greater_than_equal_to[' . $this->input->post('variant_special_price[' . $key . ']') . ']');
                                $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|xss_clean|less_than_equal_to[' . $this->input->post('variant_price[' . $key . ']') . ']');
                                $this->form_validation->set_rules('variant_sku[' . $key . ']', 'SKU', 'trim|xss_clean');
                                $this->form_validation->set_rules('variant_total_stock[' . $key . ']', 'Total Stock asd', 'trim|required|numeric|xss_clean');
                                $this->form_validation->set_rules('variant_level_stock_status[' . $key . ']', 'Stock Status', 'trim|required|numeric|xss_clean');
                            }
                        } else {
                            $this->form_validation->set_rules('variant_price', 'Price', 'trim|required|numeric|xss_clean|greater_than_equal_to[' . $this->input->post('variant_special_price[0]') . ']');
                            $this->form_validation->set_rules('variant_special_price', 'Special Price', 'trim|numeric|xss_clean|less_than_equal_to[' . $this->input->post('variant_price') . ']');
                            $this->form_validation->set_rules('variant_sku', 'SKU', 'trim|xss_clean');
                            $this->form_validation->set_rules('variant_total_stock', 'Total Stock asd', 'trim|required|numeric|xss_clean');
                            $this->form_validation->set_rules('variant_level_stock_status', 'Stock Status', 'trim|required|numeric|xss_clean');
                        }
                    }
                } else {
                    if (isset($_POST['variant_price']) && isset($_POST['variant_special_price'])) {
                        foreach ($_POST['variant_price'] as $key => $value) {
                            $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|xss_clean|greater_than_equal_to[' . $this->input->post('variant_special_price[' . $key . ']') . ']');
                            $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|xss_clean|less_than_equal_to[' . $this->input->post('variant_price[' . $key . ']') . ']');
                        }
                    } else {
                        $this->form_validation->set_rules('variant_price', 'Price', 'trim|required|numeric|xss_clean|greater_than_equal_to[' . $this->input->post('variant_special_price[0]') . ']');
                        $this->form_validation->set_rules('variant_special_price', 'Special Price', 'trim|numeric|xss_clean|less_than_equal_to[' . $this->input->post('variant_price') . ']');
                    }
                }
            }

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                if (!empty($_POST['deliverable_zipcodes'])) {
                    $_POST['zipcodes'] = implode(",", $_POST['deliverable_zipcodes']);
                } else {
                    $_POST['zipcodes'] = NULL;
                }

               
                $this->product_model->add_product($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_product_id'])) ? 'Product Updated Successfully' : 'Product Added Successfully';
                $this->response['message'] = $message;
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_product_data()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $seller_id =  (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) ? $this->input->get('seller_id', true) : NULL;
            $status =  (isset($_GET['status']) && $_GET['status'] != "") ? $this->input->get('status', true) : NULL;
            if (isset($_GET['flag']) && !empty($_GET['flag'])) {
                return $this->product_model->get_product_details($_GET['flag'], $seller_id, $status);
            }
            return $this->product_model->get_product_details(null, $seller_id, $status);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_digital_product_data()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $seller_id =  (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) ? $this->input->get('seller_id', true) : NULL;
            $status =  (isset($_GET['status']) && $_GET['status'] != "") ? $this->input->get('status', true) : NULL;
            // if (isset($_GET['flag']) && !empty($_GET['flag'])) {
            //     return $this->product_model->get_product_details($_GET['flag'], $seller_id, $status);
            // }
            return $this->product_model->get_digital_product_details(null, $seller_id, $status);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_countries_data()
    {
        $search = $this->input->get('search');
        $response = $this->product_model->get_countries($search);
        echo json_encode($response);
    }

    public function get_brands_data()
    {
        $search = $this->input->get('search');
        $response = $this->product_model->get_brands($search);
        echo json_encode($response);
    }

    public function get_product_data_list()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->product_model->get_product_details('low');
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_rating_list()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            return $this->rating_model->get_rating();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function fetch_attributes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $attributes = $this->db->select('attr_val.id,attr.name as attr_name ,attr_set.name as attr_set_name,attr_val.value')->join('attributes attr', 'attr.id=attr_val.attribute_id')->join('attribute_set attr_set', 'attr_set.id=attr_val.attribute_set_id')->where(' attr.status=1 ')->get('attribute_values attr_val')->result_array();
            $attributes_refind = array();
            for ($i = 0; $i < count($attributes); $i++) {

                if (!array_key_exists($attributes[$i]['attr_set_name'], $attributes_refind)) {
                    $attributes_refind[$attributes[$i]['attr_set_name']] = array();

                    for ($j = 0; $j < count($attributes); $j++) {

                        if ($attributes[$i]['attr_set_name'] == $attributes[$j]['attr_set_name']) {

                            if (!array_key_exists($attributes[$j]['attr_name'], $attributes_refind[$attributes[$i]['attr_set_name']])) {

                                $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']] = array();
                            }
                            $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']][$j]['id'] = $attributes[$j]['id'];

                            $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']][$j]['text'] = $attributes[$j]['value'];

                            $attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']] = array_values($attributes_refind[$attributes[$i]['attr_set_name']][$attributes[$j]['attr_name']]);
                        }
                    }
                }
            }
            print_r(json_encode($attributes_refind));
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function view_product()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['main_page'] = VIEW . 'products';
                $settings = get_settings('system_settings', true);
                $this->data['title'] = 'View Product | ' . $settings['app_name'];
                $this->data['meta_description'] = 'View Product | ' . $settings['app_name'];
                $res = fetch_product($user_id = NULL, ["show_only_active_products" => 0], $this->input->get('edit_id', true));
                $this->data['product_details'] = $res['product'];
                $this->data['product_attributes'] = get_attribute_values_by_pid($_GET['edit_id']);
                $this->data['product_variants'] = get_variants_values_by_pid($_GET['edit_id'], [0, 1, 7]);
                $this->data['product_rating'] = $this->rating_model->fetch_rating((isset($_GET['edit_id'])) ? $_GET['edit_id'] : '', '');
                $this->data['currency'] = $settings['currency'];
                $this->data['category_result'] = fetch_details('categories', ['status' => '1'], 'id,name');
                if (!empty($res['product'])) {
                    $this->load->view('admin/template', $this->data);
                } else {
                    redirect('admin/product', 'refresh');
                }
            } else {
                redirect('admin/product', 'refresh');
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function delete_rating()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'product'), PERMISSION_ERROR_MSG, 'product', false)) {
                return false;
            }

            $this->rating_model->delete_rating($_GET['id']);

            $this->response['error'] = false;
            $this->response['message'] = 'Deleted Succesfully';

            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function change_variant_status($id = '', $status = '', $product_id = '')
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('update', 'product'), PERMISSION_ERROR_MSG, 'product', false)) {
                return false;
            }

            $status = (trim($status) != '' && is_numeric(trim($status))) ? trim($status) : "";
            $id = (!empty(trim($id)) && is_numeric(trim($id))) ? trim($id) : "";

            if (empty($id) || $status == '') {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = "Invalid Status or ID value supplied";

                $this->session->set_flashdata('message', $this->response['message']);
                $this->session->set_flashdata('message_type', 'error');
                if (!empty($product_id)) {
                    $callback_url = base_url("admin/product/view-product?edit_id=$product_id");
                    header("location:$callback_url");
                    return false;
                } else {
                    print_r(json_encode($this->response));
                    return false;
                }
            }
            $all_status = [0, 1, 7];
            if (!in_array($status, $all_status)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = "Invalid Status value supplied";

                $this->session->set_flashdata('message', $this->response['message']);
                $this->session->set_flashdata('message_type', 'error');
                if (!empty($product_id)) {
                    $callback_url = base_url("admin/product/view-product?edit_id=$product_id");
                    header("location:$callback_url");
                    return false;
                } else {
                    print_r(json_encode($this->response));
                    return false;
                }
            }

            /* change variant status to the new status */
            update_details(['status' => $status], ['id' => $id], 'product_variants');

            $this->response['error'] = false;
            $this->response['message'] = 'Variant status changed successfully';
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();

            $this->session->set_flashdata('message', $this->response['message']);
            $this->session->set_flashdata('message_type', 'success');
            if (!empty($product_id)) {
                $callback_url = base_url("admin/product/view-product?edit_id=$product_id");
                header("location:$callback_url");
                return false;
            } else {
                print_r(json_encode($this->response));
                return false;
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function bulk_upload()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'bulk-upload';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Bulk Upload | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Bulk Upload | ' . $settings['app_name'];

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /**
     * Stream a bulk upload template with the page's settings already filled in.
     *
     * A blank sample left the admin to work out which of 51 columns mattered and what to write
     * in the coded ones. This writes the settings columns on every row from the choices made on
     * the page, in words, and leaves the product columns empty - so the file that arrives back
     * only ever needed the name, image and price typing in, and cannot be wrong about the parts
     * that used to cause the failures.
     */
    public function bulk_upload_template()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_admin())) {
            redirect('admin/login', 'refresh');
            return;
        }

        $defaults = collect_bulk_upload_defaults();
        $vocab = bulk_setting_vocabulary();

        $variants = (int) $this->input->post('template_variants');
        $variants = ($variants >= 1 && $variants <= 10) ? $variants : 1;
        $blank_rows = (int) $this->input->post('template_rows');
        $blank_rows = ($blank_rows >= 1 && $blank_rows <= 200) ? $blank_rows : 10;

        $header = [
            'seller_id', 'category_id', 'product_type', 'name', 'short_description', 'description', 'image',
            'other_images', 'tags', 'sku', 'stock', 'tax', 'made_in', 'warranty_period',
            'guarantee_period', 'video_type', 'video', 'minimum_order_quantity',
            'quantity_step_size', 'total_allowed_quantity', 'cod_allowed', 'prices_include_tax',
            'returnable', 'cancellable_until', 'food_type', 'delivery_area', 'pincodes',
        ];
        for ($v = 1; $v <= $variants; $v++) {
            $suffix = ($variants > 1) ? '_' . $v : '';
            $header[] = 'attribute_value_ids' . $suffix;
            $header[] = 'price' . $suffix;
            $header[] = 'special_price' . $suffix;
            $header[] = 'sku_variant' . $suffix;
            $header[] = 'stock_variant' . $suffix;
        }

        // The words for the settings the seller picked, so the file reads back as English.
        $cancelable_key = ($defaults['is_cancelable'] == 1) ? $defaults['cancelable_till'] : '';
        // One higher than the seller sheet's, because seller_id sits in front of them.
        $prefilled = [
            20 => ($defaults['cod_allowed'] == 1) ? 'Yes' : 'No',
            21 => ($defaults['is_prices_inclusive_tax'] == 1) ? 'Yes' : 'No',
            22 => ($defaults['is_returnable'] == 1) ? 'Yes' : 'No',
            23 => $vocab['cancellable_until']['labels'][$cancelable_key] ?? 'No',
            24 => $vocab['food_type']['labels'][(string) $defaults['indicator']] ?? 'Not a food product',
            25 => $vocab['delivery_area']['labels'][$defaults['deliverable_type']] ?? 'Everywhere',
            26 => $defaults['deliverable_zipcodes'],
        ];

        $row_template = array_fill(0, count($header), '');
        foreach ($prefilled as $index => $value) {
            $row_template[$index] = $value;
        }
        // Two hints the seller cannot get wrong by leaving them: quantities default to 1 anyway,
        // and product_type is the one column with only two legal spellings.
        $row_template[2] = 'simple_product';
        $row_template[17] = '1';
        $row_template[18] = '1';

        $filename = 'cretzo-admin-bulk-upload-template.csv';
        $this->output
            ->set_content_type('text/csv')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate');

        // No byte-order mark: CI's output layer re-encodes the response, so a BOM written here
        // came back double-encoded and showed as three junk characters glued to the first column
        // heading. A plain UTF-8 body is read correctly by Excel, Sheets and LibreOffice alike.
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $header);
        for ($i = 0; $i < $blank_rows; $i++) {
            fputcsv($handle, $row_template);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->output->set_output($csv);
    }

    public function process_bulk_upload()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('create', 'product'), PERMISSION_ERROR_MSG, 'product')) {
                return false;
            }
            // When a POST body exceeds post_max_size, PHP discards both $_POST and $_FILES and
            // hands the script an empty request. Without this check the user was told "The Type
            // field is required" even though they had selected one, which gave no hint that the
            // real problem was the size of the file.
            if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'The file is larger than this server accepts (limit: ' . ini_get('post_max_size') . '). Please split it into smaller files and upload them one at a time.';
                echo json_encode($this->response);
                return false;
            }

            $this->form_validation->set_rules('bulk_upload', '', 'xss_clean');
            $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
            if (empty($_FILES['upload_file']['name'])) {
                $this->form_validation->set_rules('upload_file', 'File', 'trim|required|xss_clean', array('required' => 'Please choose file'));
            }

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $allowed_mime_type_arr = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv');
                $mime = get_mime_by_extension($_FILES['upload_file']['name']);
                if (!in_array($mime, $allowed_mime_type_arr)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Invalid file format!';
                    print_r(json_encode($this->response));
                    return false;
                }
                // The upload status was never inspected. When an upload failed - a file above
                // upload_max_filesize, a partial transfer, or a missing temp directory - PHP
                // leaves tmp_name empty while still populating the file name, so the extension
                // check above passed and execution reached fopen(''). On PHP 8 that throws an
                // uncaught ValueError, the request died with HTTP 500, and because the page's
                // submit handler had no error branch the button stayed on "Please Wait..."
                // indefinitely with nothing shown to the user.
                $upload_error = isset($_FILES['upload_file']['error']) ? $_FILES['upload_file']['error'] : UPLOAD_ERR_NO_FILE;
                if ($upload_error !== UPLOAD_ERR_OK) {
                    $upload_messages = [
                        UPLOAD_ERR_INI_SIZE   => 'The file is larger than this server accepts (limit: ' . ini_get('upload_max_filesize') . '). Please split it into smaller files.',
                        UPLOAD_ERR_FORM_SIZE  => 'The file is larger than this form accepts. Please split it into smaller files.',
                        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_FILE    => 'Please choose a file to upload.',
                        UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder configured for uploads. Please contact your hosting provider.',
                        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file to disk. Please contact your hosting provider.',
                        UPLOAD_ERR_EXTENSION  => 'The upload was blocked by a server extension.',
                    ];
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = isset($upload_messages[$upload_error]) ? $upload_messages[$upload_error] : 'The file could not be uploaded. Please try again.';
                    echo json_encode($this->response);
                    return false;
                }

                $csv = $_FILES['upload_file']['tmp_name'];

                if (empty($csv) || !is_uploaded_file($csv)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'The uploaded file could not be read. Please try again.';
                    echo json_encode($this->response);
                    return false;
                }

                $temp = 0;
                $temp1 = 0;
                $handle = fopen($csv, "r");

                if ($handle === false) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'The uploaded file could not be opened. Please try again.';
                    echo json_encode($this->response);
                    return false;
                }

                $allowed_status = array("received", "processed", "shipped");
                $video_types = array("youtube", "vimeo");
                $this->response['message'] = '';
                $type = $_POST['type'];

                // Excel on a non-US locale writes tab- or semicolon-separated files. Reading one
                // of those with a hard-coded "," collapsed every row into a single column, so the
                // importer rejected valid data with a message about column 2 - and the PHP
                // "Undefined array key" warnings it printed ahead of the JSON reply left the page
                // unable to read the response at all.
                $delimiter = detect_csv_delimiter($csv);
                // Widest index any branch below reads: 30 + (49 * 7) = 373.
                $row_width = 380;

                // A file whose columns do not line up with the layout the code indexes would have
                // its variant block read off-by-one - price landing in attribute_value_ids and so
                // on - and silently import corrupt products. The upload sheet is the simple
                // layout: seller_id then the same product columns the seller page uses, with the
                // settings written as words. The update sheet is the same shape with product_id
                // in front and a variant_id at the head of each variant block.
                $fixed_columns = ($type == 'upload') ? ADMIN_SIMPLE_FIXED_COLUMNS : UPDATE_SIMPLE_FIXED_COLUMNS;
                $variant_block = ($type == 'upload') ? SIMPLE_VARIANT_COLUMNS : UPDATE_SIMPLE_VARIANT_COLUMNS;
                $header_row = fgetcsv($handle, 10000, $delimiter);
                $header_count = ($header_row === FALSE) ? 0 : count($header_row);
                if (!is_valid_bulk_header_width($header_count, $fixed_columns, $variant_block)) {
                    fclose($handle);
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = bulk_header_width_message($header_count, $fixed_columns, $variant_block);
                    echo json_encode($this->response);
                    return false;
                }
                rewind($handle);

                if ($type == 'upload') {
                    // The upload sheet used to be 51 columns wide, carrying every product setting
                    // as a numeric code -
                    // stock_type 0/1/2, indicator 0/1/2, deliverable_type 0..3, cod_allowed,
                    // is_returnable, is_cancelable, cancelable_till - which sellers had no way to
                    // read off a spreadsheet. Those settings are the same for every row of a
                    // typical upload, so they now come from labelled inputs on the page and the
                    // sheet is reduced to the product columns that genuinely differ per product,
                    // plus a short variant block.
                    $defaults = collect_bulk_upload_defaults();
                    if ($defaults['error'] !== '') {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = $defaults['error'];
                        echo json_encode($this->response);
                        return false;
                    }

                    // Column map for the simple sheet. Variant blocks start at
                    // SIMPLE_FIXED_COLUMNS and repeat every SIMPLE_VARIANT_COLUMNS.
                    // Same layout as the seller sheet with seller_id in front, so both pages teach
                    // the same file and the shared helpers apply unchanged.
                    $C = [
                        'seller_id' => 0,
                        'category_id' => 1, 'product_type' => 2, 'name' => 3, 'short_description' => 4,
                        'description' => 5, 'image' => 6, 'other_images' => 7, 'tags' => 8,
                        'sku' => 9, 'stock' => 10, 'tax' => 11, 'made_in' => 12,
                        'warranty_period' => 13, 'guarantee_period' => 14, 'video_type' => 15, 'video' => 16,
                        'minimum_order_quantity' => 17, 'quantity_step_size' => 18, 'total_allowed_quantity' => 19,
                        // Written as words by the generated template; blank falls back to the form.
                        'cod_allowed' => 20, 'prices_include_tax' => 21, 'returnable' => 22,
                        'cancellable_until' => 23, 'food_type' => 24, 'delivery_area' => 25, 'pincodes' => 26,
                    ];
                    $rows_data = [];
                    while (($row = fgetcsv($handle, 10000, $delimiter)) != FALSE) //get row values
                    {
                        // Short rows used to raise an "Undefined array key" warning per read; those warnings
                        // were echoed ahead of the JSON reply and left the caller unable to parse it.
                        $row = pad_csv_row($row, $row_width);

                        if ($temp != 0) {
                            $line = $temp;

                            if (trim($row[$C['seller_id']]) === '') {
                                return bulk_upload_row_error('Seller is empty at row ' . $line . '. Put the numeric seller user id from Sellers > Manage Sellers in the first column.');
                            }
                            $seller_row = fetch_details('seller_data', ['user_id' => $row[$C['seller_id']]], 'user_id');
                            if (empty($seller_row)) {
                                return bulk_upload_row_error('Seller ID "' . $row[$C['seller_id']] . '" at row ' . $line . ' does not match any seller. Use the numeric user id from Sellers > Manage Sellers, not a store name or code.');
                            }

                            if (trim($row[$C['category_id']]) === '') {
                                return bulk_upload_row_error('Category is empty at row ' . $line . '. Put the Category ID from the Categories page in the first column.');
                            }
                            $category = fetch_details('categories', ['id' => $row[$C['category_id']]], 'id');
                            if (empty($category)) {
                                return bulk_upload_row_error('Category ID "' . $row[$C['category_id']] . '" at row ' . $line . ' does not exist. Copy the ID from the Categories page.');
                            }

                            $product_type = trim($row[$C['product_type']]);
                            if ($product_type != 'simple_product' && $product_type != 'variable_product') {
                                return bulk_upload_row_error('Product type at row ' . $line . ' must be simple_product or variable_product.');
                            }

                            if (trim($row[$C['name']]) === '') {
                                return bulk_upload_row_error('Name is empty at row ' . $line . '.');
                            }

                            if (trim($row[$C['image']]) === '') {
                                return bulk_upload_row_error('Image is empty at row ' . $line . '. Copy the image path from the Media page.');
                            }

                            $video_type = trim($row[$C['video_type']]);
                            if ($video_type !== '' && !in_array($video_type, $video_types)) {
                                return bulk_upload_row_error('Video type at row ' . $line . ' must be youtube or vimeo (or left blank).');
                            }

                            // A variant is present when its price cell has a value. Counting on
                            // attribute value ids instead would make every simple_product - which
                            // legitimately has none - look like it had no variants at all.
                            $variants = [];
                            for ($v = 0; $v < 50; $v++) {
                                $base = ADMIN_SIMPLE_FIXED_COLUMNS + ($v * SIMPLE_VARIANT_COLUMNS);
                                if (trim($row[$base + 1]) === '') {
                                    continue;
                                }
                                $variants[] = [
                                    'attribute_value_ids' => trim($row[$base]),
                                    'price'               => trim($row[$base + 1]),
                                    'special_price'       => trim($row[$base + 2]),
                                    'sku'                 => trim($row[$base + 3]),
                                    'stock'               => trim($row[$base + 4]),
                                ];
                            }

                            if (empty($variants)) {
                                return bulk_upload_row_error('Price is empty at row ' . $line . '. Every product needs at least a price.');
                            }
                            if ($product_type == 'simple_product' && count($variants) > 1) {
                                return bulk_upload_row_error('Row ' . $line . ' is a simple_product but has ' . count($variants) . ' prices. Use variable_product for more than one variant.');
                            }

                            foreach ($variants as $n => $variant) {
                                if (!is_numeric($variant['price'])) {
                                    return bulk_upload_row_error('Price "' . $variant['price'] . '" at row ' . $line . ' is not a number.');
                                }
                                if ($variant['special_price'] !== '' && !is_numeric($variant['special_price'])) {
                                    return bulk_upload_row_error('Special price "' . $variant['special_price'] . '" at row ' . $line . ' is not a number.');
                                }
                                if ($variant['special_price'] !== '' && (float) $variant['special_price'] > (float) $variant['price']) {
                                    return bulk_upload_row_error('Special price is higher than the price at row ' . $line . '. The special price is the discounted one.');
                                }
                                if ($product_type == 'variable_product' && $variant['attribute_value_ids'] === '') {
                                    return bulk_upload_row_error('Attribute value ids are empty for variant ' . ($n + 1) . ' at row ' . $line . '. A variable_product needs them on every variant.');
                                }
                            }

                            $rows_data[] = ['row' => $row, 'type' => $product_type, 'variants' => $variants];
                        }
                        $temp++;
                    }

                    fclose($handle);

                    if (empty($rows_data)) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'The file has a header row but no products.';
                        echo json_encode($this->response);
                        return false;
                    }

                    // No plan-quota check here: this is the admin importer, and an admin adding
                    // listings on a seller's behalf is not spending that seller's own allowance.
                    $rows_to_add = count($rows_data);

                    $this->db->trans_start();
                    foreach ($rows_data as $entry) {
                        $row = $entry['row'];
                        $variants = $entry['variants'];

                        // stock_type is derived, never asked for: a seller cannot be expected to
                        // know that 0 means product-level on a simple product while 2 means
                        // variant-level on a variable one. Whichever stock column they filled in
                        // says which level they meant, and no stock at all means untracked.
                        $variant_stock_given = false;
                        foreach ($variants as $variant) {
                            if ($variant['stock'] !== '') {
                                $variant_stock_given = true;
                                break;
                            }
                        }
                        $product_stock_given = (trim($row[$C['stock']]) !== '');
                        if ($entry['type'] == 'simple_product') {
                            $stock_type = $product_stock_given ? 0 : null;
                        } elseif ($variant_stock_given) {
                            $stock_type = 2;
                        } elseif ($product_stock_given) {
                            $stock_type = 1;
                        } else {
                            $stock_type = null;
                        }

                        $data = [];
                        $data['category_id'] = $row[$C['category_id']];
                        if (trim($row[$C['tax']]) !== '') {
                            $data['tax'] = $row[$C['tax']];
                        }
                        $data['type'] = $entry['type'];
                        if ($stock_type !== null) {
                            $data['stock_type'] = $stock_type;
                        }
                        $data['name'] = $row[$C['name']];
                        $data['short_description'] = $row[$C['short_description']];
                        $data['slug'] = create_unique_slug($row[$C['name']], 'products');
                        $data['description'] = $row[$C['description']];
                        $data['image'] = $row[$C['image']];
                        if (trim($row[$C['other_images']]) !== '') {
                            $data['other_images'] = json_encode(array_map('trim', explode(',', $row[$C['other_images']])), 1);
                        } else {
                            $data['other_images'] = '[]';
                        }
                        $data['video_type'] = $row[$C['video_type']];
                        $data['video'] = $row[$C['video']];
                        $data['tags'] = $row[$C['tags']];
                        $data['warranty_period'] = $row[$C['warranty_period']];
                        $data['guarantee_period'] = $row[$C['guarantee_period']];
                        $data['made_in'] = $row[$C['made_in']];
                        if (trim($row[$C['sku']]) !== '') {
                            $data['sku'] = $row[$C['sku']];
                        }
                        if ($product_stock_given) {
                            $data['stock'] = sanitise_import_stock($row[$C['stock']]);
                        }

                        // Plain numbers stay in the sheet - they need no decoding, and a seller may
                        // well want a different minimum order per product. Blank means the column
                        // default (1 / 1 / no cap).
                        if (trim($row[$C['minimum_order_quantity']]) !== '') {
                            $data['minimum_order_quantity'] = $row[$C['minimum_order_quantity']];
                        }
                        if (trim($row[$C['quantity_step_size']]) !== '') {
                            $data['quantity_step_size'] = $row[$C['quantity_step_size']];
                        }
                        if (trim($row[$C['total_allowed_quantity']]) !== '') {
                            $data['total_allowed_quantity'] = $row[$C['total_allowed_quantity']];
                        }

                        // availability is derived rather than asked for: it only ever meant "is
                        // there stock", so a seller answering it separately could contradict the
                        // stock column they just filled in.
                        $availability = ($product_stock_given && (int) $row[$C['stock']] <= 0) ? 0 : 1;
                        $data['availability'] = $availability;

                        // The settings that used to be numeric codes. The template the upload page
                        // generates writes them as words on every row, so the file normally answers
                        // them; a blank cell falls back to what was chosen on the form. Either way
                        // the seller never types a code.
                        $settings = resolve_bulk_row_settings($row, $C, $defaults);
                        $data['indicator'] = $settings['indicator'];
                        $data['cod_allowed'] = $settings['cod_allowed'];
                        $data['is_prices_inclusive_tax'] = $settings['is_prices_inclusive_tax'];
                        $data['is_returnable'] = $settings['is_returnable'];
                        $data['is_cancelable'] = $settings['is_cancelable'];
                        $data['cancelable_till'] = $settings['cancelable_till'];
                        $data['deliverable_type'] = $settings['deliverable_type'];
                        $data['deliverable_zipcodes'] = $settings['deliverable_zipcodes'];
                        // An admin imports on behalf of a seller, so ownership is the row's own
                        // seller_id - already checked to exist during validation.
                        $data['seller_id'] = $row[$C['seller_id']];

                        $this->db->insert('products', $data);
                        $product_id = $this->db->insert_id();

                        $attribute_value_ids = [];
                        foreach ($variants as $variant) {
                            if ($variant['attribute_value_ids'] !== '') {
                                $attribute_value_ids[] = $variant['attribute_value_ids'];
                            }
                        }
                        $this->db->insert('product_attributes', [
                            'product_id' => $product_id,
                            'attribute_value_ids' => implode(',', $attribute_value_ids),
                        ]);

                        foreach ($variants as $variant) {
                            $variant_data = [
                                'product_id' => $product_id,
                                'attribute_value_ids' => $variant['attribute_value_ids'],
                                'price' => $variant['price'],
                                'special_price' => ($variant['special_price'] !== '') ? $variant['special_price'] : 0,
                                'images' => '[]',
                                'availability' => ($variant['stock'] !== '' && (int) $variant['stock'] <= 0) ? 0 : $availability,
                            ];
                            if ($variant['sku'] !== '') {
                                $variant_data['sku'] = $variant['sku'];
                            }
                            if ($variant['stock'] !== '') {
                                $variant_data['stock'] = sanitise_import_stock($variant['stock']);
                            }
                            $this->db->insert('product_variants', $variant_data);
                        }
                    }
                    $this->db->trans_complete();
                    $this->response['error'] = ($this->db->trans_status() === false);
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = ($this->db->trans_status() === false)
                        ? 'Upload failed, no products were added. Please check your file and try again.'
                        : $rows_to_add . ' product(s) uploaded successfully!';
                    echo json_encode($this->response);
                    return false;
                } else { // bulk_update
                    // The update sheet used to be 36 columns of the same numeric codes as the old
                    // upload sheet, plus a seller_id that could silently move a product between
                    // sellers. It is now the upload layout with product_id in front and variant_id
                    // at the head of each variant block, and every setting is written in words.
                    // Ownership is never touched here: the product already has an owner.
                    //
                    // A blank cell means "leave this as it is" - that is the whole point of an
                    // update - so unlike the upload branch there are no form defaults to fall back
                    // to, and the settings panel is hidden for this action.
                    $C = [
                        'product_id' => 0,
                        'category_id' => 1, 'product_type' => 2, 'name' => 3, 'short_description' => 4,
                        'description' => 5, 'image' => 6, 'other_images' => 7, 'tags' => 8,
                        'sku' => 9, 'stock' => 10, 'tax' => 11, 'made_in' => 12,
                        'warranty_period' => 13, 'guarantee_period' => 14, 'video_type' => 15, 'video' => 16,
                        'minimum_order_quantity' => 17, 'quantity_step_size' => 18, 'total_allowed_quantity' => 19,
                        'cod_allowed' => 20, 'prices_include_tax' => 21, 'returnable' => 22,
                        'cancellable_until' => 23, 'food_type' => 24, 'delivery_area' => 25, 'pincodes' => 26,
                    ];
                    // Plain text and number columns that are copied across untouched when filled.
                    $plain_columns = [
                        'name' => 'name', 'short_description' => 'short_description',
                        'description' => 'description', 'image' => 'image', 'tags' => 'tags',
                        'sku' => 'sku', 'tax' => 'tax', 'made_in' => 'made_in',
                        'warranty_period' => 'warranty_period', 'guarantee_period' => 'guarantee_period',
                        'video_type' => 'video_type', 'video' => 'video',
                        'minimum_order_quantity' => 'minimum_order_quantity',
                        'quantity_step_size' => 'quantity_step_size',
                        'total_allowed_quantity' => 'total_allowed_quantity',
                    ];

                    $rows_data = [];
                    while (($row = fgetcsv($handle, 10000, $delimiter)) != FALSE) //get row values
                    {
                        // Short rows used to raise an "Undefined array key" warning per read; those warnings
                        // were echoed ahead of the JSON reply and left the page unable to parse it.
                        $row = pad_csv_row($row, $row_width);

                        if ($temp != 0) {
                            $line = $temp;

                            if (trim($row[$C['product_id']]) === '') {
                                return bulk_upload_row_error('Product ID is empty at row ' . $line . '. Put the ID from Products > Manage Products in the first column.');
                            }
                            $product = fetch_details('products', ['id' => $row[$C['product_id']]], 'id,seller_id');
                            if (empty($product)) {
                                return bulk_upload_row_error('Product ID "' . $row[$C['product_id']] . '" at row ' . $line . ' does not exist. Copy the ID from Products > Manage Products.');
                            }

                            if (trim($row[$C['category_id']]) !== '') {
                                $category = fetch_details('categories', ['id' => $row[$C['category_id']]], 'id');
                                if (empty($category)) {
                                    return bulk_upload_row_error('Category ID "' . $row[$C['category_id']] . '" at row ' . $line . ' does not exist. Copy the ID from the Categories page.');
                                }
                            }

                            $product_type = trim($row[$C['product_type']]);
                            if ($product_type !== '' && $product_type != 'simple_product' && $product_type != 'variable_product') {
                                return bulk_upload_row_error('Product type at row ' . $line . ' must be simple_product or variable_product, or left blank to keep it as it is.');
                            }

                            $video_type = trim($row[$C['video_type']]);
                            if ($video_type !== '' && !in_array($video_type, $video_types)) {
                                return bulk_upload_row_error('Video type at row ' . $line . ' must be youtube or vimeo (or left blank).');
                            }

                            // A variant block counts as present when it names a variant_id. Price
                            // cannot be the marker here the way it is on upload, because changing
                            // only a variant's stock is a perfectly ordinary update.
                            $variants = [];
                            for ($v = 0; $v < 50; $v++) {
                                $base = UPDATE_SIMPLE_FIXED_COLUMNS + ($v * UPDATE_SIMPLE_VARIANT_COLUMNS);
                                if (trim($row[$base]) === '') {
                                    continue;
                                }
                                $variant_id = trim($row[$base]);
                                $variant_row = fetch_details('product_variants', ['id' => $variant_id], 'id,product_id');
                                if (empty($variant_row)) {
                                    return bulk_upload_row_error('Variant ID "' . $variant_id . '" at row ' . $line . ' does not exist. Copy it from the product edit screen.');
                                }
                                // Without this a variant id typed one digit out would be rewritten
                                // with another product's prices, and nothing would look wrong.
                                if ($variant_row[0]['product_id'] != $row[$C['product_id']]) {
                                    return bulk_upload_row_error('Variant ID "' . $variant_id . '" at row ' . $line . ' belongs to product ' . $variant_row[0]['product_id'] . ', not product ' . $row[$C['product_id']] . '.');
                                }
                                $variants[] = [
                                    'id'                  => $variant_id,
                                    'attribute_value_ids' => trim($row[$base + 1]),
                                    'price'               => trim($row[$base + 2]),
                                    'special_price'       => trim($row[$base + 3]),
                                    'sku'                 => trim($row[$base + 4]),
                                    'stock'               => trim($row[$base + 5]),
                                ];
                            }

                            foreach ($variants as $variant) {
                                if ($variant['price'] !== '' && !is_numeric($variant['price'])) {
                                    return bulk_upload_row_error('Price "' . $variant['price'] . '" at row ' . $line . ' is not a number.');
                                }
                                if ($variant['special_price'] !== '' && !is_numeric($variant['special_price'])) {
                                    return bulk_upload_row_error('Special price "' . $variant['special_price'] . '" at row ' . $line . ' is not a number.');
                                }
                                if ($variant['price'] !== '' && $variant['special_price'] !== '' && (float) $variant['special_price'] > (float) $variant['price']) {
                                    return bulk_upload_row_error('Special price is higher than the price at row ' . $line . '. The special price is the discounted one.');
                                }
                            }

                            $rows_data[] = ['row' => $row, 'type' => $product_type, 'variants' => $variants];
                        }
                        $temp++;
                    }

                    fclose($handle);

                    if (empty($rows_data)) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'The file has a header row but no products.';
                        echo json_encode($this->response);
                        return false;
                    }

                    // As with the upload branch, all updates are applied in one transaction so a
                    // failure cannot leave half the products updated and half untouched.
                    $this->db->trans_start();
                    foreach ($rows_data as $entry) {
                        $row = $entry['row'];
                        $data = [];

                        foreach ($plain_columns as $column => $field) {
                            if (trim($row[$C[$column]]) !== '') {
                                $data[$field] = $row[$C[$column]];
                            }
                        }
                        // The slug is derived from the name, so it has to move with it.
                        if (trim($row[$C['name']]) !== '') {
                            $data['slug'] = create_unique_slug($row[$C['name']], 'products');
                        }
                        if (trim($row[$C['category_id']]) !== '') {
                            $data['category_id'] = $row[$C['category_id']];
                        }
                        if ($entry['type'] !== '') {
                            $data['type'] = $entry['type'];
                        }
                        if (trim($row[$C['other_images']]) !== '') {
                            $data['other_images'] = json_encode(array_map('trim', explode(',', $row[$C['other_images']])), 1);
                        }
                        if (trim($row[$C['stock']]) !== '') {
                            $data['stock'] = sanitise_import_stock($row[$C['stock']]);
                            // Availability follows the stock figure, as it does on upload - a
                            // separate column for it could only ever contradict this.
                            $data['availability'] = ((int) $row[$C['stock']] <= 0) ? 0 : 1;
                        }

                        // resolve_bulk_row_settings() is shared with the upload branch, so it is
                        // handed an empty set of defaults and the result compared against it: only
                        // the settings this row actually named are written.
                        $blank = [
                            'indicator' => null, 'cod_allowed' => null, 'is_prices_inclusive_tax' => null,
                            'is_returnable' => null, 'is_cancelable' => null, 'cancelable_till' => null,
                            'deliverable_type' => null, 'deliverable_zipcodes' => null, 'error' => '',
                        ];
                        $settings = resolve_bulk_row_settings($row, $C, $blank);
                        foreach (['indicator', 'cod_allowed', 'is_prices_inclusive_tax', 'is_returnable',
                                  'is_cancelable', 'cancelable_till', 'deliverable_type', 'deliverable_zipcodes'] as $key) {
                            if ($settings[$key] !== null) {
                                $data[$key] = $settings[$key];
                            }
                        }

                        if (!empty($data)) {
                            $this->db->where('id', $row[$C['product_id']])->update('products', $data);
                        }

                        foreach ($entry['variants'] as $variant) {
                            $variant_data = [];
                            if ($variant['attribute_value_ids'] !== '') {
                                $variant_data['attribute_value_ids'] = $variant['attribute_value_ids'];
                            }
                            if ($variant['price'] !== '') {
                                $variant_data['price'] = $variant['price'];
                            }
                            if ($variant['special_price'] !== '') {
                                $variant_data['special_price'] = $variant['special_price'];
                            }
                            if ($variant['sku'] !== '') {
                                $variant_data['sku'] = $variant['sku'];
                            }
                            if ($variant['stock'] !== '') {
                                $variant_data['stock'] = sanitise_import_stock($variant['stock']);
                                $variant_data['availability'] = ((int) $variant['stock'] <= 0) ? 0 : 1;
                            }
                            if (!empty($variant_data)) {
                                $this->db->where('id', $variant['id'])->update('product_variants', $variant_data);
                            }
                        }
                    }
                    $this->db->trans_complete();

                    if ($this->db->trans_status() === false) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'The update failed and no products were changed. Please check the file and try again.';
                        echo json_encode($this->response);
                        return false;
                    }

                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = count($rows_data) . ' product(s) updated successfully!';
                    echo json_encode($this->response);
                    return false;
                }
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function get_faqs_list()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            return $this->product_model->get_faqs();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function edit_product_faqs()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->form_validation->set_rules('answer', 'Answer', 'trim|required|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $this->product_model->add_product_faqs($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_product_faq'])) ? 'FAQ Updated Successfully' : 'FAQ Added Successfully';
                $this->response['message'] = $message;
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function delete_product_faq()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'product'), PERMISSION_ERROR_MSG, 'product', false)) {
                return false;
            }

            $this->product_model->delete_faq($_GET['id']);

            $this->response['error'] = false;
            $this->response['message'] = 'Deleted Succesfully';

            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
