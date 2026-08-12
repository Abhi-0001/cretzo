<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Product extends CI_Controller
{
    // Sellers pick an existing category from the dropdown only — this never creates one.
    private function resolve_selected_category_id($input)
    {
        $input = is_string($input) ? trim($input) : $input;
        if (!is_string($input) || $input === '') {
            return 0;
        }

        return (int) $input;
    }

    
    private function is_seller_admin_verified()
    {
        $seller_id = $this->session->userdata('user_id');
        $seller = $this->db->select('status')->where('user_id', $seller_id)->get('seller_data')->row_array();

        // A missing seller_data row means this account predates the KYC/verification
        // flow (or the row was otherwise never created) — it must NOT be treated the
        // same as a brand-new signup awaiting review, or an already-operating seller
        // gets retroactively locked out of product management the moment this row is
        // absent. Only an EXISTING row with an explicit non-approved status blocks.
        if (empty($seller)) {
            return true;
        }

        return (string) $seller['status'] === '1';
    }

    private function ensure_product_access($expects_json = false)
    {
        if ($this->is_seller_admin_verified()) {
            return true;
        }

        if ($expects_json) {
            $response = [
                'error' => true,
                'message' => 'Product management is locked until admin verification is approved.',
                'total' => 0,
                'rows' => []
            ];
            print_r(json_encode($response));
            return false;
        }

        $this->session->set_flashdata('message', 'Please submit/complete admin verification. Product section unlocks only after admin approval.');
        redirect('seller/home/profile?section=admin', 'refresh');
        return false;
    }
    // admin verification added to add the product y the seller


    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['product_model', 'category_model', 'rating_model', 'Seller_subscription_model']);
        $this->response = [];
    }
    public function index()
{
    if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
        redirect('seller/login', 'refresh');
        return;
    }

    $seller_id = $this->session->userdata('user_id');

    if (!$this->ensure_product_access()) {
        return;
    }

    $this->data['main_page'] = TABLES . 'manage-product';
    $settings = get_settings('system_settings', true);
    $this->data['title'] = 'Product Management | ' . $settings['app_name'];
    $this->data['meta_description'] = 'Product Management | ' . $settings['app_name'];

    if (isset($_GET['edit_id'])) {
        // Scoped to this seller — without this, any seller could read another seller's
        // product FAQ just by changing ?edit_id= in the URL.
        $this->data['fetched_data'] = $this->fetch_owned_faq($_GET['edit_id'], $seller_id);
    }

    // Sellers may list products in ANY active category, not just the primary/secondary
    // categories picked on their profile (that's data-collection only) — full tree, no seller filter.
    $this->data['categories'] = json_decode(json_encode($this->category_model->get_categories()), 1);
    $this->data['brands'] = fetch_details('brands', ['status' => 1], 'id,name');
    $this->load->view('seller/template', $this->data);
}

    public function create_product()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            if (!$this->ensure_product_access()){
                return;
             }
            $seller_id = $this->session->userdata('user_id');
            $this->data['main_page'] = FORMS . 'product';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Add Product | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Product | ' . $settings['app_name'];
            $this->data['taxes'] = fetch_details('taxes', null,  '*');
            $this->data['seller_id'] = $seller_id;
            $this->data['pickup_locations'] = fetch_details('pickup_locations', ['seller_id' => $seller_id, 'status' => 1], 'id,pickup_location');
            $this->data['listing_quota'] = $this->Seller_subscription_model->check_listing_quota($seller_id, 1);
            $this->data['countries'] = fetch_details('countries', null, 'name,id');
            $this->data['brands'] = fetch_details('brands', null, 'name,id');

            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['title'] = 'Update Product | ' . $settings['app_name'];
                $this->data['meta_description'] = 'Update Product | ' . $settings['app_name'];
                // Scoped to this seller — without this, changing ?edit_id= in the URL let any
                // seller load and edit any other seller's product.
                $product_details = fetch_details('products', ['id' => $_GET['edit_id'], 'seller_id' => $seller_id], '*');

                if (!empty($product_details)) {
                    $countries = fetch_details('countries', ['name' => $product_details[0]['made_in']], 'name');
                    $this->data['product_details'] = $product_details;
                    $this->data['product_variants'] = get_variants_values_by_pid($_GET['edit_id']);
                    // Same fallback as the admin form: a simple / digital product's price and
                    // dimensions live on a single variant row that may have been soft-removed.
                    if (empty($this->data['product_variants']) && !empty($product_details[0]['type']) && $product_details[0]['type'] != 'variable_product') {
                        $this->data['product_variants'] = get_variants_values_by_pid($_GET['edit_id'], [0, 1, 7]);
                    }
                    $product_attributes = fetch_details('product_attributes', ['product_id' => $_GET['edit_id']]);
                    if (!empty($product_attributes) && !empty($product_details)) {
                        $this->data['product_attributes'] = $product_attributes;
                    }
                } else {
                    redirect('seller/product/create_product', 'refresh');
                }
            }


            $attributes = $this->db->select('attr_val.id,attr.name as attr_name ,attr_set.name as attr_set_name,attr_val.value')
                ->join('attributes attr', 'attr.id=attr_val.attribute_id')
                ->join('attribute_set attr_set', 'attr_set.id=attr.attribute_set_id')
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
            // Sellers may list products in ANY active category, not just the primary/secondary
            // categories picked on their profile (that's data-collection only) — full tree, no seller filter.
            $this->data['categories'] = $this->category_model->get_categories();
            $this->data['attributes_refind'] = $attributes_refind;
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function get_variants_by_id()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }
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
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }
        // Scoped to this seller — without this, any seller could pull another seller's
        // product's variants/attributes just by passing that product's edit_id.
        $owned_product = fetch_details('products', ['id' => $_GET['edit_id'], 'seller_id' => $this->session->userdata('user_id')], 'id');
        if (empty($owned_product)) {
            print_r(json_encode(['result' => ['attr_values' => [], 'pre_selected_variants_names' => null, 'pre_selected_variants_ids' => []]]));
            return;
        }

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
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }
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
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }
        // Scoped to this seller — same reasoning as fetch_attributes_by_id() above.
        $owned_product = fetch_details('products', ['id' => $_GET['edit_id'], 'seller_id' => $this->session->userdata('user_id')], 'id');
        if (empty($owned_product)) {
            print_r(json_encode(['result' => []]));
            return;
        }

        $res = get_variants_values_by_pid($_GET['edit_id']);
        $response['result'] = $res;
        print_r(json_encode($response));
    }

    public function search_category_wise_products()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->db->select('p.*');
            if ($_GET['cat_id'] == 0) {
                $data = "";
            } else {
                $this->db->where('p.category_id', $_GET['cat_id']);
                $this->db->or_where('c.parent_id', $_GET['cat_id']);
            }

            $product_data = json_encode($this->db->order_by('row_order')->join('categories c', 'p.category_id = c.id')->get('products p')->result_array());
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function delete_product()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            if (!$this->ensure_product_access()){
                return;
            }
            if (print_msg(!is_modification_allowed('create'), DEMO_VERSION_MSG, 'product', false)) {
                return false;
            }
            $seller_id = $this->ion_auth->get_user_id();
            $owned_product = fetch_details('products', ['id' => $_GET['id'], 'seller_id' => $seller_id], 'id');
            if (empty($owned_product)) {
                $response['error'] = true;
                $response['message'] = 'Product not found';
                print_r(json_encode($response));
                return false;
            }
            if (delete_details(['product_id' => $_GET['id']], 'product_variants')) {

                delete_details(['id' => $_GET['id'], 'seller_id' => $seller_id], 'products');
                delete_details(['product_id' => $_GET['id']], 'product_attributes');
                $response['error'] = false;
                $response['message'] = 'Deleted Succesfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something Went Wrong';
            }
            print_r(json_encode($response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function add_product()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            return;
        }
    
        if (!$this->ensure_product_access(true)) {
            return;
        }
    
        if (print_msg(!is_modification_allowed('create'), DEMO_VERSION_MSG, 'product', false)) {
            return false;
        }
        $_POST['seller_id'] = $this->ion_auth->get_user_id();
        if (!empty($_POST['edit_product_id'])) {
            $owned_product = fetch_details('products', ['id' => $_POST['edit_product_id'], 'seller_id' => $_POST['seller_id']], 'id');
            if (empty($owned_product)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Product not found';
                print_r(json_encode($this->response));
                return;
            }
        }

        if (empty($_POST['edit_product_id'])) {
            $quota_seller_id = $this->session->userdata('user_id');
            $quota = $this->Seller_subscription_model->check_listing_quota($quota_seller_id, 1);
            if (!$quota['allowed']) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $this->Seller_subscription_model->quota_error_message($quota, 1);
                print_r(json_encode($this->response));
                return;
            }
        }

        // Sync product_type from type field if not set
        if (empty($_POST['product_type']) && !empty($_POST['type'])) {
            $_POST['product_type'] = trim($_POST['type']);
        }

        // ADD THIS RIGHT HERE, before any validation rules
        $product_type = isset($_POST['product_type']) ? trim($_POST['product_type']) : '';
       
       
        // Normalize category_id (can arrive as array from tree widgets or duplicated form fields)
        if (isset($_POST['category_id']) && is_array($_POST['category_id'])) {
            $_POST['category_id'] = reset($_POST['category_id']);
        }
        if (isset($_POST['category_id']) && is_string($_POST['category_id'])) {
            $_POST['category_id'] = trim($_POST['category_id']);
        }
        if (isset($_POST['category_id'])) {
            $_POST['category_id'] = $this->resolve_selected_category_id($_POST['category_id']);
        }
        
        // Set validation rules
        $this->form_validation->set_rules('pro_input_name', 'Product Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('short_description', 'Short Description', 'trim|required|xss_clean');
        $this->form_validation->set_rules('category_id', 'Category Id', 'trim|required|numeric|xss_clean', array('required' => 'Category is required'));
        $this->form_validation->set_rules('pro_input_tax', 'Tax', 'trim|xss_clean');
        $image_rule = (isset($_POST['edit_product_id']) && !empty($_POST['edit_product_id'])) ? 'trim|xss_clean' : 'trim|required|xss_clean';
        $this->form_validation->set_rules('pro_input_image', 'Image', $image_rule, ['required' => 'Image is required']);
        $this->form_validation->set_rules('pro_input_image', 'Image', $image_rule, ['required' => 'Image is required']);
        // $this->form_validation->set_rules('pro_input_image', 'Image', 'trim|required|xss_clean', ['required' => 'Image is required']);
        $this->form_validation->set_rules('made_in', 'Made In', 'trim|xss_clean');
        $this->form_validation->set_rules('brand', 'Brand', 'trim|xss_clean');
        $this->form_validation->set_rules('product_type', 'Product type', 'trim|required|xss_clean');
        $this->form_validation->set_rules('total_allowed_quantity', 'Total Allowed Quantity', 'trim|xss_clean');
        $this->form_validation->set_rules('minimum_order_quantity', 'Minimum Order Quantity', 'trim|xss_clean');
        $this->form_validation->set_rules('quantity_step_size', 'Quantity Step Size', 'trim|xss_clean');
        $this->form_validation->set_rules('warranty_period', 'Warranty Period', 'trim|xss_clean');
        $this->form_validation->set_rules('guarantee_period', 'Guarantee Period', 'trim|xss_clean');
        $this->form_validation->set_rules('hsn_code', 'HSN Code', 'trim|xss_clean');
        $this->form_validation->set_rules('indicator', 'Indicator', 'trim|xss_clean');
        $this->form_validation->set_rules('weight', 'Weight', 'trim|xss_clean');
        $this->form_validation->set_rules('height', 'Height', 'trim|xss_clean');
        $this->form_validation->set_rules('breadth', 'Breadth', 'trim|xss_clean');
        $this->form_validation->set_rules('length', 'Length', 'trim|xss_clean');
        if (isset($_POST['is_attachment_required'])) {
            $this->form_validation->set_rules('is_attachment_required', 'Attachment required', 'trim|xss_clean');
        }

        // products.pickup_location is NOT NULL — submitting the form without one used
        // to fatal with "Column 'pickup_location' cannot be null" instead of a normal
        // validation message. Require it only when the seller actually has a pickup
        // location to pick from; a seller with none yet can still create the product
        // (it just can't be shipped via Shiprocket until they add one and select it).
        $seller_pickup_locations = fetch_details('pickup_locations', ['seller_id' => $_POST['seller_id'], 'status' => 1], 'id');
        if (!empty($seller_pickup_locations)) {
            $this->form_validation->set_rules('pickup_location', 'Pickup Location', 'trim|required|xss_clean', [
                'required' => 'Please select a pickup location for this product.'
            ]);
        } else {
            $this->form_validation->set_rules('pickup_location', 'Pickup Location', 'trim|xss_clean');
        }
        $_POST['pickup_location'] = (isset($_POST['pickup_location']) && $_POST['pickup_location'] !== 'NULL') ? trim((string) $_POST['pickup_location']) : '';

        $this->form_validation->set_rules('video', 'Video', 'trim|xss_clean');
        $this->form_validation->set_rules('video_type', 'Video Type', 'trim|xss_clean');
        $this->form_validation->set_rules('deliverable_type', 'Deliverable Type', 'trim|xss_clean');
        $this->form_validation->set_rules('seller_id', 'Seller Id', 'required|trim|xss_clean|numeric');
    
        // Video validation
        if (isset($_POST['video_type']) && $_POST['video_type'] != '') {
            if ($_POST['video_type'] == 'youtube' || $_POST['video_type'] == 'vimeo') {
                $this->form_validation->set_rules('video', 'Video link', 'trim|required|xss_clean', ['required' => 'Please paste a %s in the input box.']);
            } else {
                $this->form_validation->set_rules('pro_input_video', 'Video file', 'trim|required|xss_clean', ['required' => 'Please choose a %s to be set.']);
            }
        }
    
        // Download validation
        if (isset($_POST['download_allowed']) && $_POST['download_allowed'] == 'on') {
            $this->form_validation->set_rules('download_link_type', 'Download Link Type', 'required|xss_clean');
            if (isset($_POST['download_link_type']) && $_POST['download_link_type'] == 'self_hosted') {
                $this->form_validation->set_rules('pro_input_zip', 'Zip file for download', 'required|xss_clean');
            }
            if (isset($_POST['download_link_type']) && $_POST['download_link_type'] == 'add_link') {
                $this->form_validation->set_rules('download_link', 'Digital Product URL/Link', 'required|xss_clean');
            }
        }
    
        // Tags processing
        if (isset($_POST['tags']) && $_POST['tags'] != '') {
            $decoded_tags = json_decode($_POST['tags'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_tags)) {
            $tags = array_column($decoded_tags, 'value');
            $_POST['tags'] = implode(",", array_filter($tags, 'strlen'));
            } else {
            $_POST['tags'] = trim((string)$_POST['tags']);
        }
        }
    
        // Cancelable validation — required once checked, now that the form has a real
        // "Cancelable till" select (it used to be checkbox-only with no matching input).
        if (isset($_POST['is_cancelable']) && $_POST['is_cancelable'] == '1') {
            $this->form_validation->set_rules('cancelable_till', 'Cancelable till', 'trim|required|xss_clean');
        }
    
        if (isset($_POST['cod_allowed'])) {
            $this->form_validation->set_rules('cod_allowed', 'COD allowed', 'trim|xss_clean');
        }
    
        if (isset($_POST['is_prices_inclusive_tax'])) {
            $this->form_validation->set_rules('is_prices_inclusive_tax', 'Tax included in prices', 'trim|xss_clean');
        }
    
        // Deliverable zipcodes validation
        if (isset($_POST['deliverable_type']) && ($_POST['deliverable_type'] == INCLUDED || $_POST['deliverable_type'] == EXCLUDED)) {
            $this->form_validation->set_rules('deliverable_zipcodes[]', 'Deliverable Zipcodes', 'trim|required|xss_clean');
        }
    
        // Product type specific validation
    
        if ($product_type == 'simple_product' || $product_type == 'digital_product') {
            $this->form_validation->set_rules('simple_price', 'Price', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('simple_special_price', 'Special Price', 'trim|numeric|xss_clean');
    
            if (isset($_POST['simple_product_stock_status']) && in_array($_POST['simple_product_stock_status'], ['0', '1'])) {
                $this->form_validation->set_rules('product_sku', 'SKU', 'trim|xss_clean');
                $this->form_validation->set_rules('product_total_stock', 'Total Stock', 'trim|required|numeric|xss_clean');
                $this->form_validation->set_rules('simple_product_stock_status', 'Stock Status', 'trim|required|numeric|xss_clean');
            }
        } elseif ($product_type == 'variable_product') {
            if (isset($_POST['variant_stock_status']) && $_POST['variant_stock_status'] == '0') {
                if (isset($_POST['variant_stock_level_type']) && $_POST['variant_stock_level_type'] == 'product_level') {
                    // Product_model::add_product() reads $data['sku_variant_type'] (not
                    // sku_pro_type) for this branch - this rule name previously didn't
                    // match what the model actually consumes.
                    $this->form_validation->set_rules('sku_variant_type', 'SKU', 'trim|xss_clean');
                    $this->form_validation->set_rules('total_stock_variant_type', 'Total Stock', 'trim|required|xss_clean');
                    $this->form_validation->set_rules('variant_status', 'Stock Status', 'trim|required|xss_clean');
    
                    if (isset($_POST['variant_price']) && is_array($_POST['variant_price'])) {
                        foreach ($_POST['variant_price'] as $key => $value) {
                            $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|xss_clean');
                            $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|xss_clean');
                        }
                    }
                } else {
                    if (isset($_POST['variant_price']) && is_array($_POST['variant_price'])) {
                        foreach ($_POST['variant_price'] as $key => $value) {
                            $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|xss_clean');
                            $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|xss_clean');
                            $this->form_validation->set_rules('variant_sku[' . $key . ']', 'SKU', 'trim|xss_clean');
                            $this->form_validation->set_rules('variant_total_stock[' . $key . ']', 'Total Stock', 'trim|required|numeric|xss_clean');
                            $this->form_validation->set_rules('variant_level_stock_status[' . $key . ']', 'Stock Status', 'trim|required|numeric|xss_clean');
                        }
                    }
                }
            } else {
                if (isset($_POST['variant_price']) && is_array($_POST['variant_price'])) {
                    foreach ($_POST['variant_price'] as $key => $value) {
                        $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|xss_clean');
                        $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|xss_clean');
                    }
                }
            }
        }

         // Run validation
         if (!$this->form_validation->run()) {
             $this->response['error'] = true;
             $this->response['csrfName'] = $this->security->get_csrf_token_name();
             $this->response['csrfHash'] = $this->security->get_csrf_hash();
             $this->response['message'] = validation_errors();
             $this->response['errors'] = $this->form_validation->error_array();
             print_r(json_encode($this->response));
             return;
         }
     
         // Process zipcodes
         if (!empty($_POST['deliverable_zipcodes']) && is_array($_POST['deliverable_zipcodes'])) {
             $_POST['zipcodes'] = implode(",", $_POST['deliverable_zipcodes']);
         } else {
             $_POST['zipcodes'] = NULL;
         }
     
         // Save product
         $this->product_model->add_product($_POST);

         $message = isset($_POST['edit_product_id']) ? 'Product Updated Successfully' : 'Product Added Successfully';

        // Add this line to store the message in the session
        $this->session->set_flashdata('message', $message);
        $this->session->set_flashdata('message_type', 'success');

        $this->response['error'] = false;
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        $this->response['message'] = $message;
        $this->response['redirect'] = base_url('seller/product');
        print_r(json_encode($this->response));
    }
        
        
         
   

    public function get_subcategories()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            return;
        }

        $parent_id = (int) $this->input->get('parent_id', true);

        // Sellers may browse/pick ANY active category's sub-categories, not just the ones
        // in their profile's category_ids (that field is data-collection only).
        $this->db->select('id,name,parent_id');
        $this->db->from('categories');
        $this->db->where(['parent_id' => $parent_id, 'status' => 1]);
        $rows = $this->db->order_by('name', 'ASC')->get()->result_array();

        $response = [
            'error' => false,
            'rows' => $rows,
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash()
        ];
        print_r(json_encode($response));
    }
    // process_category() was REMOVED: an ungated `public` wrapper that did nothing but
    // forward POST input to the private resolve_selected_category_id() helper and
    // return its value. Because it was public it was routable as
    // /seller/product/process_category by anyone, while every other method in this
    // controller is gated - and since it only returned (never echoed), it produced a
    // blank response and served no purpose. Unreferenced by any view, JS or route.

    public function get_product_data()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            if (!$this->ensure_product_access(true)) {
                return;
            }
            // seller_id must always come from the authenticated session — trusting a
            // client-supplied ?seller_id= would let a seller view another seller's
            // entire product catalog just by changing a URL parameter.
            $seller_id = $this->session->userdata('user_id');
            $status =  (isset($_GET['status']) && $_GET['status'] != "") ? $this->input->get('status', true) : NULL;
            if (isset($_GET['flag']) && !empty($_GET['flag'])) {
                return $this->product_model->get_product_details($_GET['flag'], $seller_id, $status);
            }
            return $this->product_model->get_product_details(null, $seller_id, $status);
        } else {
            redirect('seller/login', 'refresh');
        }
    }


    public function get_rating_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            return $this->rating_model->get_rating($this->ion_auth->get_user_id());
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function fetch_attributes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $attributes = $this->db->select('attr_val.id,attr.name as attr_name ,attr_set.name as attr_set_name,attr_val.value')->join('attributes attr', 'attr.id=attr_val.attribute_id')->join('attribute_set attr_set', 'attr_set.id=attr_val.attribute_set_id')->get('attribute_values attr_val')->result_array();
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
            redirect('seller/login', 'refresh');
        }
    }


    public function view_product()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                // Scoped to this seller — without this, changing ?edit_id= in the URL let any
                // seller view any other seller's product.
                $owned_product = fetch_details('products', ['id' => $_GET['edit_id'], 'seller_id' => $this->session->userdata('user_id')], 'id');
                if (empty($owned_product)) {
                    redirect('seller/product', 'refresh');
                    return;
                }

                $this->data['main_page'] = VIEW . 'products';
                $settings = get_settings('system_settings', true);
                $this->data['title'] = 'View Product | ' . $settings['app_name'];
                $this->data['meta_description'] = 'View Product | ' . $settings['app_name'];
                $res = fetch_product($user_id = NULL, $filter = NULL, $this->input->get('edit_id', true));
                $this->data['product_details'] = $res['product'];
                $this->data['product_attributes'] = get_attribute_values_by_pid($_GET['edit_id']);
                $this->data['product_variants'] = get_variants_values_by_pid($_GET['edit_id'], [0, 1, 7]);
                $this->data['product_rating'] = $this->rating_model->fetch_rating((isset($_GET['edit_id'])) ? $_GET['edit_id'] : '', '');
                $this->data['currency'] = $settings['currency'];
                $this->data['category_result'] = fetch_details('categories', ['status' => '1'], 'id,name');
                if (!empty($res['product'])) {
                    $this->load->view('seller/template', $this->data);
                } else {
                    redirect('seller/product', 'refresh');
                }
            } else {
                redirect('seller/product', 'refresh');
            }
        } else {
            redirect('seller/login', 'refresh');
        }
    }


    public function delete_rating()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            if (print_msg(!is_modification_allowed('create'), DEMO_VERSION_MSG, 'product', false)) {
                return false;
            }

            // Scoped to this seller — without this, any seller could delete a review off
            // another seller's product (and their aggregate rating would be recalculated
            // to match).
            $rating = fetch_details('product_rating', ['id' => $_GET['id']], 'product_id');
            $owned_product = !empty($rating) ? fetch_details('products', ['id' => $rating[0]['product_id'], 'seller_id' => $this->ion_auth->get_user_id()], 'id') : [];
            if (empty($owned_product)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Rating not found';
                print_r(json_encode($this->response));
                return false;
            }

            $this->rating_model->delete_rating($_GET['id']);

            $this->response['error'] = false;
            $this->response['message'] = 'Deleted Succesfully';

            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function change_variant_status($id = '', $status = '', $product_id = '')
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

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
                    $callback_url = base_url("seller/product/view-product?edit_id=$product_id");
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
                    $callback_url = base_url("seller/product/view-product?edit_id=$product_id");
                    header("location:$callback_url");
                    return false;
                } else {
                    print_r(json_encode($this->response));
                    return false;
                }
            }

            // Scoped to this seller — without this, any seller could disable/enable
            // another seller's product variant just by knowing its id.
            $variant = fetch_details('product_variants', ['id' => $id], 'product_id');
            $owned_product = !empty($variant) ? fetch_details('products', ['id' => $variant[0]['product_id'], 'seller_id' => $this->session->userdata('user_id')], 'id') : [];
            if (empty($owned_product)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Variant not found';

                $this->session->set_flashdata('message', $this->response['message']);
                $this->session->set_flashdata('message_type', 'error');
                if (!empty($product_id)) {
                    $callback_url = base_url("seller/product/view-product?edit_id=$product_id");
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
                $callback_url = base_url("seller/product/view-product?edit_id=$product_id");
                header("location:$callback_url");
                return false;
            } else {
                print_r(json_encode($this->response));
                return false;
            }
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function bulk_upload()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = FORMS . 'bulk-upload';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Bulk Upload | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Bulk Upload | ' . $settings['app_name'];

            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function process_bulk_upload()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            if (print_msg(!is_modification_allowed('create'), DEMO_VERSION_MSG, 'product', false)) {
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
                $csv = $_FILES['upload_file']['tmp_name'];
                $temp = 0;
                $temp1 = 0;
                $handle = fopen($csv, "r");
                $allowed_status = array("received", "processed", "shipped");
                $video_types = array("youtube", "vimeo");
                $this->response['message'] = '';
                $type = $_POST['type'];
                if ($type == 'upload') {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        
                        if ($temp != 0) {
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Category id is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if ($row[2] != 'simple_product' && $row[2] != 'variable_product') {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Product type is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (empty($row[4])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Name is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }


                            if (!empty($row[7]) && $row[7] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'COD allowed is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[11]) && $row[11] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Is prices inclusive tax is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[12]) && $row[12] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Is Returnable is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[13]) && $row[13] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Is Cancelable is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[13]) && $row[13] == 1 && (empty($row[14]) || !in_array($row[14], $allowed_status))) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Cancelable till is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (empty($row[13]) && !(empty($row[14]))) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Cancelable till is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (empty($row[15])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Image is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[17]) && !in_array($row[17], $video_types)) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Video type is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if ($row[27] != 0 && $row[27] != 1 && $row[27] != 2 && $row[27] != 3 && $row[27] == "") {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Not valid value for deliverable_type at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if ($row[27] == INCLUDED || $row[27] == EXCLUDED) {
                                if (empty($row[28])) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'Deliverable_zipcodes is empty at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }

                            $seller_id = $this->ion_auth->get_user_id();

                            // Sellers may list products in any active category — no longer
                            // restricted to their profile's category_ids (data-collection only).

                            $index1 = 30;
                            $total_variants = 0;
                            for ($j = 0; $j < 50; $j++) {

                                if (!empty($row[$index1])) {
                                    $total_variants++;
                                }
                                $index1 = $index1 + 7;
                            }
                            $variant_index = 29;
                            for ($k = 0; $k < $total_variants; $k++) {
                                if ($row[2] == 'variable_product') {
                                    if (empty($row[$variant_index])) {
                                        $this->response['error'] = true;
                                        $this->response['message'] = 'Attribute value ids is empty at row  ' . $temp;
                                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                        print_r(json_encode($this->response));
                                        return false;
                                    }
                                    $variant_index = $variant_index + 7;
                                }
                            }
                            if ($total_variants == 0) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Variants not found at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            } elseif ($row[2] == 'simple_product' && $total_variants > 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'You can not add variants more than one for simple prodcuct at row  ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                        }
                        $temp++;
                    }

                    fclose($handle);

                    // Enforce the plan's listing limit before inserting bulk products
                    // ($temp counted header + data rows in the validation pass above).
                    $rows_to_add = ($temp > 0) ? ($temp - 1) : 0;
                    $quota = $this->Seller_subscription_model->check_listing_quota($this->ion_auth->get_user_id(), $rows_to_add);
                    if (!$quota['allowed']) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = $this->Seller_subscription_model->quota_error_message($quota, $rows_to_add);
                        print_r(json_encode($this->response));
                        return false;
                    }

                    $handle = fopen($csv, "r");
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp1 != 0) {
                            $data = [];
                            $data['category_id'] = $row[0];
                            if (!empty($row[1])) {
                                $data['tax'] = $row[1];
                            }
                            $data['type'] = $row[2];
                            if ($row[3] != '') {
                                $data['stock_type'] = $row[3];
                            }

                            $data['name'] = $row[4];
                            $data['short_description'] = $row[5];
                            $data['slug'] = create_unique_slug($row[4], 'products');
                            if ($row[6] != '') {
                                $data['indicator'] = $row[6];
                            }
                            if ($row[7] != '') {
                                $data['cod_allowed'] = $row[7];
                            }

                            if ($row[8] != '') {
                                $data['minimum_order_quantity'] = $row[8];
                            }
                            if ($row[9] != '') {
                                $data['quantity_step_size'] = $row[9];
                            }
                            if ($row[10] != '') {
                                $data['total_allowed_quantity'] = $row[10];
                            }
                            if ($row[11] != '') {
                                $data['is_prices_inclusive_tax'] = $row[11];
                            }
                            if ($row[12] != '') {
                                $data['is_returnable'] = $row[12];
                            }
                            if ($row[13] != '') {
                                $data['is_cancelable'] = $row[13];
                            }
                            $data['cancelable_till'] = $row[14];
                            $data['image'] = $row[15];
                            if (isset($row[16]) && $row[16] != '') {
                                $other_images = explode(',', $row[16]);
                                $data['other_images'] = json_encode($other_images, 1);
                            } else {
                                $data['other_images'] = '[]';
                            }
                            $data['video_type'] = $row[17];
                            $data['video'] = $row[18];
                            $data['tags'] = $row[19];
                            $data['warranty_period'] = $row[20];
                            $data['guarantee_period'] = $row[21];
                            $data['made_in'] = $row[22];

                            if (!empty($row[23])) {
                                $data['sku'] = $row[23];
                            }
                            if (!empty($row[24])) {
                                $data['stock'] = $row[24];
                            }
                            if ($row[25] != '') {
                                $data['availability'] = $row[25];
                            }

                            $data['description'] = $row[26];
                            $data['deliverable_type'] = $row[27]; //in csv its 28th
                            $data['deliverable_zipcodes'] = $row[28]; // in csv its 29th
                            $data['seller_id'] = $this->ion_auth->get_user_id();
                            // $data['brand'] = $row[36];
                            // $data['hsn_code'] = $row[37];
                            // $data['pickup_location'] = $row[38];
                            // $data['extra_description'] = $row[39];
                            $this->db->insert('products', $data);
                            $product_id = $this->db->insert_id();

                            $index1 = 30;
                            $total_variants = 0;
                            for ($j = 0; $j < 50; $j++) {
                                if (!empty($row[$index1])) {
                                    $total_variants++;
                                }
                                $index1 = $index1 + 7;
                            }

                            $index1 = 29;
                            $attribute_value_ids = '';
                            for ($j = 0; $j < $total_variants; $j++) {
                                if (!empty($row[$index1])) {
                                    if (!empty($attribute_value_ids)) {
                                        $attribute_value_ids .= ',' . strval($row[$index1]);
                                    } else {
                                        $attribute_value_ids = strval($row[$index1]);
                                    }
                                }
                                $index1 = $index1 + 7;
                            }
                            $attribute_value_ids = !empty($attribute_value_ids) ? $attribute_value_ids : '';
                            $pro_attr_data = [

                                'product_id' => $product_id,
                                'attribute_value_ids' => $attribute_value_ids,

                            ];
                            $this->db->insert('product_attributes', $pro_attr_data);
                            $variant_data = [];
                            $index = 29;
                            for ($i = 0; $i < $total_variants; $i++) {
                                $variant_data[$i]['images'] = '[]';
                                $variant_data[$i]['product_id'] = $product_id;
                                $variant_data[$i]['attribute_value_ids'] = $row[$index] ?? '';
                                $index++;
                                $variant_data[$i]['price'] = $row[$index] ?? '';
                                $index++;
                                if (isset($row[$index]) && !empty($row[$index])) {
                                    $variant_data[$i]['special_price'] = $row[$index];
                                } else {
                                    $variant_data[$i]['special_price'] = 0;
                                }

                                $index++;
                                if (isset($row[$index]) && !empty($row[$index])) {
                                    $variant_data[$i]['sku'] = $row[$index];
                                }
                                $index++;
                                if (isset($row[$index]) && !empty($row[$index])) {
                                    $variant_data[$i]['stock'] = $row[$index];
                                }

                                $index++;
                                if (isset($row[$index]) && $row[$index] != '' && !empty($row[$index])) {
                                    $images = explode(',', $row[$index]);
                                    $variant_data[$i]['images'] = json_encode($images, 1);
                                }

                                $index++;
                                if (isset($row[$index]) && $row[$index] != '') {
                                    $variant_data[$i]['availability'] = $row[$index];
                                }

                                $index++;
                                $this->db->insert('product_variants', $variant_data[$i]);
                            }
                        }
                        $temp1++;
                    }
                    fclose($handle);
                    $this->db->trans_complete();
                    $this->response['error'] = ($this->db->trans_status() === false);
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = ($this->db->trans_status() === false) ? 'Upload failed, no products were added. Please check your file and try again.' : 'Products uploaded successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                       
                        if ($temp != 0) {
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Product id is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[3]) && $row[3] != 'simple_product' && $row[3] != 'variable_product') {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Product type is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }


                            if (!empty($row[8]) && $row[8] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'COD allowed is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[12]) && $row[12] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Is prices inclusive tax is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[13]) && $row[13] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Is Returnable is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[14]) && $row[14] != 1) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Is Cancelable is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[14]) && $row[14] == 1 && (empty($row[15]) || !in_array($row[15], $allowed_status))) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Cancelable till is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (empty($row[14]) && !(empty($row[15]))) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Cancelable till is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[18]) && !in_array($row[18], $video_types)) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Video type is invalid at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            // if ($row[27] != "") {
                            //     if ($row[27] != 0 && $row[27] != 1 && $row[27] != 2 && $row[27] != 3) {
                            //         $this->response['error'] = true;
                            //         $this->response['message'] = 'Not valid value for deliverable_type at row ' . $temp;
                            //         $this->response['csrfName'] = $this->security->get_csrf_token_name();
                            //         $this->response['csrfHash'] = $this->security->get_csrf_hash();
                            //         print_r(json_encode($this->response));
                            //         return false;
                            //     }
                            // }

                            if ($row[27] != "" && ($row[27] == INCLUDED || $row[27] == EXCLUDED)) {
                                if (empty($row[28])) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'Deliverable_zipcodes is empty at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }

                            // Sellers may list products in any active category — no longer
                            // restricted to their profile's category_ids (data-collection only).
                            $seller_id = $this->ion_auth->get_user_id();
                        }
                        $temp++;
                    }

                    fclose($handle);
                    $handle = fopen($csv, "r");
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        if ($temp1 != 0) {
                            $data = [];
                            $product_id = $row[0];
                            $current_seller_id = $this->ion_auth->get_user_id();
                            $product = fetch_details('products', ['id' => $product_id, 'seller_id' => $current_seller_id], '*');
                            $product_owned_by_seller = isset($product[0]) && !empty($product[0]);
                            if ($product_owned_by_seller) {
                                if (!empty($row[1])) {
                                    $data['category_id'] = $row[1];
                                } else {
                                    $data['category_id'] = $product[0]['category_id'];
                                }
                                if (!empty($row[2])) {
                                    $data['tax'] = $row[2];
                                } else {
                                    $data['tax'] = $product[0]['tax'];
                                }
                                if (!empty($row[3])) {
                                    $data['type'] = $row[3];
                                } else {
                                    $data['type'] = $product[0]['type'];
                                }
                                if ($row[4] != '') {
                                    $data['stock_type'] = $row[4];
                                } else {
                                    $data['stock_type'] = $product[0]['stock_type'];
                                }
                                if (!empty($row[5])) {
                                    $data['name'] = $row[5];
                                    $data['slug'] = create_unique_slug($row[5], 'products');
                                } else {
                                    $data['name'] = $product[0]['name'];
                                }
                                if (!empty($row[6])) {
                                    $data['short_description'] = $row[6];
                                } else {
                                    $data['short_description'] = $product[0]['short_description'];
                                }
                                if ($row[7] != '') {
                                    $data['indicator'] = $row[7];
                                } else {
                                    $data['indicator'] = $product[0]['indicator'];
                                }
                                if (!empty($row[8])) {
                                    $data['cod_allowed'] = $row[8];
                                } else {
                                    $data['cod_allowed'] = $product[0]['cod_allowed'];
                                }

                                if (!empty($row[9])) {
                                    $data['minimum_order_quantity'] = $row[9];
                                } else {
                                    $data['minimum_order_quantity'] = $product[0]['minimum_order_quantity'];
                                }
                                if (!empty($row[10])) {
                                    $data['quantity_step_size'] = $row[10];
                                } else {
                                    $data['quantity_step_size'] = $product[0]['quantity_step_size'];
                                }
                                if ($row[11] != '') {
                                    $data['total_allowed_quantity'] = $row[11];
                                } else {
                                    $data['total_allowed_quantity'] = $product[0]['total_allowed_quantity'];
                                }
                                if ($row[12] != '') {
                                    $data['is_prices_inclusive_tax'] = $row[12];
                                } else {
                                    $data['is_prices_inclusive_tax'] = $product[0]['is_prices_inclusive_tax'];
                                }
                                if ($row[13] != '') {
                                    $data['is_returnable'] = $row[13];
                                } else {
                                    $data['is_returnable'] = $product[0]['is_returnable'];
                                }
                                if ($row[14] != '') {
                                    $data['is_cancelable'] = $row[14];
                                } else {
                                    $data['is_cancelable'] = $product[0]['is_cancelable'];
                                }
                                if (!empty($row[15])) {
                                    $data['cancelable_till'] = $row[15];
                                } else {
                                    $data['cancelable_till'] = $product[0]['cancelable_till'];
                                }
                                if (!empty($row[16])) {
                                    $data['image'] = $row[16];
                                } else {
                                    $data['image'] = $product[0]['image'];
                                }
                                if (!empty($row[17])) {
                                    $data['video_type'] = $row[17];
                                } else {
                                    $data['video_type'] = $product[0]['video_type'];
                                }
                                if (!empty($row[18])) {
                                    $data['video'] = $row[18];
                                } else {
                                    $data['video'] = $product[0]['video'];
                                }
                                if (!empty($row[19])) {
                                    $data['tags'] = $row[19];
                                } else {
                                    $data['tags'] = $product[0]['tags'];
                                }
                                if (!empty($row[20])) {
                                    $data['warranty_period'] = $row[20];
                                } else {
                                    $data['warranty_period'] = $product[0]['warranty_period'];
                                }
                                if (!empty($row[21])) {
                                    $data['guarantee_period'] = $row[21];
                                } else {
                                    $data['guarantee_period'] = $product[0]['guarantee_period'];
                                }
                                if (!empty($row[22])) {
                                    $data['made_in'] = $row[22];
                                } else {
                                    $data['made_in'] = $product[0]['made_in'];
                                }
                                if (!empty($row[23])) {
                                    $data['sku'] = $row[23];
                                } else {
                                    $data['sku'] = $product[0]['sku'];
                                }
                                if ($row[24] != '') {
                                    $data['stock'] = $row[24];
                                } else {
                                    $data['stock'] = $product[0]['stock'];
                                }
                                if ($row[25] != '') {
                                    $data['availability'] = $row[25];
                                } else {
                                    $data['availability'] = $product[0]['availability'];
                                }
                                if ($row[26] != '') {
                                    $data['description'] = $row[26];
                                } else {
                                    $data['description'] = $product[0]['description'];
                                }
                                if ($row[27] != '') {
                                    $data['deliverable_type'] = $row[27];
                                } else {
                                    $data['deliverable_type'] = $product[0]['deliverable_type'];
                                }
                                if ($row[27] != '' && ($row[27] == INCLUDED || $row[27] == EXCLUDED)) {
                                    $data['deliverable_zipcodes'] = $row[28];
                                } else {
                                    $data['deliverable_zipcodes'] = $product[0]['deliverable_zipcodes'];
                                }
                                // seller_id is intentionally NOT settable from the CSV — a seller
                                // must not be able to reassign product ownership via bulk update.
                                $data['seller_id'] = $product[0]['seller_id'];
                                if (!empty($row[35])) {
                                    $data['brand'] = $row[35];
                                } else {
                                    $data['brand'] = $product[0]['brand'];
                                }
                                if (!empty($row[36])) {
                                    $data['hsn_code'] = $row[36];
                                } else {
                                    $data['hsn_code'] = $product[0]['hsn_code'];
                                }
                                if (!empty($row[37])) {
                                    $data['pickup_location'] = $row[37];
                                } else {
                                    $data['pickup_location'] = $product[0]['pickup_location'];
                                }
                                if (!empty($row[38])) {
                                    $data['extra_description'] = $row[38];
                                } else {
                                    $data['extra_description'] = $product[0]['extra_description'];
                                }
                                if (!empty($row[39])) {
                                    $data['made_in'] = $row[39];
                                } else {
                                    $data['made_in'] = $product[0]['made_in'];
                                }
                                $this->db->where(['id' => $row[0], 'seller_id' => $current_seller_id])->update('products', $data);
                            }
                            $index1 = 31;
                            $total_variants = 0;
                            for ($j = 0; $j < 50; $j++) {
                                if (!empty($row[$index1])) {
                                    $total_variants++;
                                }
                                $index1 = $index1 + 6;
                            }
                            $variant_data = [];
                            $index = 30;
                            for ($i = 0; $i < $total_variants; $i++) {
                                $variant_id = $row[$index];
                                // Only allow updating a variant that belongs to THIS row's
                                // product, and only when that product was verified above to
                                // belong to the current seller — otherwise a seller could
                                // update any variant in the system just by knowing its id.
                                $variant = $product_owned_by_seller
                                    ? fetch_details('product_variants', ['id' => $variant_id, 'product_id' => $product_id], '*')
                                    : [];
                                $index++;
                                if (isset($variant[0]) && !empty($variant[0])) {
                                    $variant_data[$i]['product_id'] = $variant[0]['product_id'];
                                    if (isset($row[$index]) && !empty($row[$index])) {
                                        $variant_data[$i]['price'] = $row[$index];
                                    } else {
                                        $variant_data[$i]['price'] = $variant[0]['price'];
                                    }
                                    $index++;
                                    if (isset($row[$index]) && $row[$index] != '') {
                                        $variant_data[$i]['special_price'] = $row[$index];
                                    } else {
                                        $variant_data[$i]['special_price'] = $variant[0]['special_price'];
                                    }
                                    $index++;
                                    if (isset($row[$index]) && !empty($row[$index])) {
                                        $variant_data[$i]['sku'] = $row[$index];
                                    } else {
                                        $variant_data[$i]['sku'] = $variant[0]['sku'];
                                    }
                                    $index++;
                                    if (isset($row[$index]) && $row[$index] != '') {
                                        $variant_data[$i]['stock'] = $row[$index];
                                    } else {
                                        $variant_data[$i]['stock'] = $variant[0]['stock'];
                                    }

                                    $index++;
                                    if (isset($row[$index]) && $row[$index] != '') {
                                        $variant_data[$i]['availability'] = $row[$index];
                                    } else {
                                        $variant_data[$i]['availability'] = $variant[0]['availability'];
                                    }
                                    $index++;
                                    $this->db->where(['id' => $variant_id, 'product_id' => $product_id])->update('product_variants', $variant_data[$i]);
                                } else {
                                    // Keep column alignment for the remaining variants in this
                                    // row even when this one is skipped/not found.
                                    $index += 5;
                                }
                            }
                        }
                        $temp1++;
                    }
                    fclose($handle);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Products updated successfully!';
                    print_r(json_encode($this->response));
                    return false;
                }
            }
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function get_countries_data()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }
        $search = $this->input->get('search');
        $response = $this->product_model->get_countries($search);
        echo json_encode($response);
    }

    public function get_brands_data()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }
        $search = $this->input->get('search');
        $response = $this->product_model->get_brands($search);
        echo json_encode($response);
    }

    // Fetches a FAQ only if its product belongs to the given seller — used before any
    // update/delete so a seller can never touch another seller's FAQ by guessing an id.
    private function fetch_owned_faq($faq_id, $seller_id)
    {
        $faq = fetch_details('product_faqs', ['id' => $faq_id], '*');
        if (empty($faq)) {
            return null;
        }
        $owned_product = fetch_details('products', ['id' => $faq[0]['product_id'], 'seller_id' => $seller_id], 'id');
        if (empty($owned_product)) {
            return null;
        }
        return $faq;
    }

    public function edit_product_faqs()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {
            $this->form_validation->set_rules('answer', 'Answer', 'trim|required|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return;
            }

            if (!empty($_POST['edit_product_faq'])) {
                if (empty($this->fetch_owned_faq($_POST['edit_product_faq'], $this->ion_auth->get_user_id()))) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'FAQ not found';
                    print_r(json_encode($this->response));
                    return;
                }
            } else {
                // Creating a brand-new FAQ (this branch, unlike the edit one above, had no
                // ownership check at all) must be scoped to a product this seller owns —
                // otherwise a seller could post fake Q&A onto another seller's listing,
                // attributed to an arbitrary customer via a forged user_id.
                $owned_product = !empty($_POST['product_id'])
                    ? fetch_details('products', ['id' => $_POST['product_id'], 'seller_id' => $this->ion_auth->get_user_id()], 'id')
                    : [];
                if (empty($owned_product)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Please select one of your own products';
                    print_r(json_encode($this->response));
                    return;
                }
                $_POST['seller_id'] = $this->ion_auth->get_user_id();
                $_POST['user_id'] = $this->ion_auth->get_user_id();
            }

            $this->product_model->add_product_faqs($_POST);
            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $message = (isset($_POST['edit_product_faq'])) ? 'FAQ Updated Successfully' : 'FAQ Added Successfully';
            $this->response['message'] = $message;
            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }
    public function get_faqs_list()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {

            return $this->product_model->get_faqs($this->ion_auth->get_user_id());
        } else {
            redirect('seller/login', 'refresh');
        }
    }
    public function delete_product_faq()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {
            if (empty($this->fetch_owned_faq($_GET['id'], $this->ion_auth->get_user_id()))) {
                $this->response['error'] = true;
                $this->response['message'] = 'FAQ not found';
                print_r(json_encode($this->response));
                return;
            }

            $this->product_model->delete_faq($_GET['id']);

            $this->response['error'] = false;
            $this->response['message'] = 'Deleted Succesfully';

            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }
}
