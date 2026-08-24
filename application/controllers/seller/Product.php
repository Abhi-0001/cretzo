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


            // Same status filter as admin/Product::create_product() - a de-activated
            // attribute or attribute set must not be offered on either form.
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

    /**
     * "Visible Listings": which of the seller's products the shop is allowed to show.
     *
     * A plan's listings_limit caps how many products a seller can have live at once. When
     * they have more than that (they downgraded, or their plan lapsed to the free tier),
     * the overflow is hidden from buyers rather than the whole catalogue staying up - and
     * this is where the seller decides WHICH ones keep the slots.
     */
    public function listing_visibility()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            return;
        }

        $seller_id = $this->session->userdata('user_id');
        $settings = get_settings('system_settings', true);

        // Re-run the cap first: the plan may have changed (or lapsed) since the last visit.
        $this->Seller_subscription_model->ensure_free_tier_fallback($seller_id);
        $state = $this->Seller_subscription_model->enforce_listing_visibility($seller_id);

        $this->data['main_page'] = FORMS . 'listing-visibility';
        $this->data['title'] = 'Visible Listings | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Visible Listings | ' . $settings['app_name'];
        $this->data['listing_state'] = $state;
        $this->data['current_plan'] = $this->Seller_subscription_model->get_current_plan($seller_id);
        $this->data['products'] = $this->db
            ->select('id, name, image, status, listing_visibility, date_added')
            ->where('seller_id', $seller_id)
            ->order_by('listing_visibility', 'ASC')
            ->order_by('id', 'DESC')
            ->get('products')->result_array();

        $this->load->view('seller/template', $this->data);
    }

    public function save_listing_visibility()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            return;
        }

        if (print_msg(!is_modification_allowed('update'), DEMO_VERSION_MSG, 'product', false)) {
            return false;
        }

        $seller_id = $this->session->userdata('user_id');
        $visible_ids = $this->input->post('visible_ids');
        $visible_ids = is_array($visible_ids) ? $visible_ids : [];

        $result = $this->Seller_subscription_model->set_visible_listings($seller_id, $visible_ids);

        $this->response['error'] = !$result['saved'];
        $this->response['message'] = $result['message'];
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        print_r(json_encode($this->response));
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
            // Also clears cart/favorites/faqs/ratings, which the old three-table delete left
            // orphaned - see Product_model::delete_product_cascade().
            if ($this->product_model->delete_product_cascade($_GET['id'], $seller_id)) {
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
        // Only a simple / digital product posts these as single values. A variable
        // product posts one per variant (weight[], height[], ...) and CI's validation
        // flattens an array field declared under its scalar name, so these four rules
        // were silently reducing every variant's parcel dimensions to one mangled
        // value - which is why per-variant weight/dimensions always came back 0.
        // Admin's controller sets no rule on them at all for the same reason.
        if ($product_type !== 'variable_product') {
            $this->form_validation->set_rules('weight', 'Weight', 'trim|xss_clean');
            $this->form_validation->set_rules('height', 'Height', 'trim|xss_clean');
            $this->form_validation->set_rules('breadth', 'Breadth', 'trim|xss_clean');
            $this->form_validation->set_rules('length', 'Length', 'trim|xss_clean');
        }
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
            // Prices were validated as 'numeric' only - which accepts a NEGATIVE price, and
            // allows a special ("offer") price ABOVE the regular price, i.e. a discount that
            // is really an increase. The seller API already enforced both of these bounds;
            // the web panel did not, so the same product was constrained differently
            // depending on where it was created.
            $this->form_validation->set_rules('simple_price', 'Price', 'trim|required|numeric|greater_than_equal_to[0]|xss_clean');
            $this->form_validation->set_rules(
                'simple_special_price',
                'Special Price',
                'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[' . (float) $this->input->post('simple_price') . ']|xss_clean'
            );
    
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
                            $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|greater_than_equal_to[0]|xss_clean');
                            $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[' . (float) $this->input->post('variant_price[' . $key . ']') . ']|xss_clean');
                        }
                    }
                } else {
                    if (isset($_POST['variant_price']) && is_array($_POST['variant_price'])) {
                        foreach ($_POST['variant_price'] as $key => $value) {
                            $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|greater_than_equal_to[0]|xss_clean');
                            $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[' . (float) $this->input->post('variant_price[' . $key . ']') . ']|xss_clean');
                            $this->form_validation->set_rules('variant_sku[' . $key . ']', 'SKU', 'trim|xss_clean');
                            $this->form_validation->set_rules('variant_total_stock[' . $key . ']', 'Total Stock', 'trim|required|numeric|xss_clean');
                            $this->form_validation->set_rules('variant_level_stock_status[' . $key . ']', 'Stock Status', 'trim|required|numeric|xss_clean');
                        }
                    }
                }
            } else {
                if (isset($_POST['variant_price']) && is_array($_POST['variant_price'])) {
                    foreach ($_POST['variant_price'] as $key => $value) {
                        $this->form_validation->set_rules('variant_price[' . $key . ']', 'Price', 'trim|required|numeric|greater_than_equal_to[0]|xss_clean');
                        $this->form_validation->set_rules('variant_special_price[' . $key . ']', 'Special Price', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[' . (float) $this->input->post('variant_price[' . $key . ']') . ']|xss_clean');
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
     
         // A seller must never dictate their own product's approval status. $_POST is handed
         // to add_product() wholesale, and the model treats a supplied `status` as
         // authoritative when editing - so a seller whose account is set to
         // "Require Product's Approval" could simply POST status=1 alongside an edit and
         // publish their own product without review. The model already falls back to the
         // row's existing status (new products) or the admin-configured approval rule, which
         // is what should decide this.
         unset($_POST['status']);

         // The seller's form has no "Is cancelable ?" switch (nor its "Till which status ?"
         // select) any more - cancellation is a platform policy, not a per-seller choice.
         // Product_model::add_product() reads both straight out of this array and writes
         // is_cancelable = 0 / cancelable_till = '' whenever the keys are missing, so an
         // ordinary seller edit would silently clear whatever the admin had set. Ignore
         // anything posted under those names and hand back the stored values instead; a new
         // product gets the model's default of not cancelable, exactly what the unticked
         // switch used to produce.
         unset($_POST['is_cancelable'], $_POST['cancelable_till']);
         if (!empty($_POST['edit_product_id'])) {
             $stored_cancelable = fetch_details('products', ['id' => $_POST['edit_product_id']], 'is_cancelable,cancelable_till');
             if (!empty($stored_cancelable)) {
                 $_POST['is_cancelable'] = $stored_cancelable[0]['is_cancelable'];
                 $_POST['cancelable_till'] = $stored_cancelable[0]['cancelable_till'];
             }
         }

         // Save product
         $new_product_id = $this->product_model->add_product($_POST);

         $message = isset($_POST['edit_product_id']) ? 'Product Updated Successfully' : 'Product Added Successfully';

         // Say so when the product is NOT live yet. Unless admin has ticked "Require
         // Product's Approval = off" for this seller, a new product is saved at status 2
         // and the storefront (which requires status 1) never shows it. The seller was told
         // only "Product Added Successfully" and then had no way to understand why their
         // product never appeared - most sellers have no permissions row at all, so this is
         // the default path, not an edge case.
         $check_id = isset($_POST['edit_product_id']) && !empty($_POST['edit_product_id'])
             ? $_POST['edit_product_id']
             : $new_product_id;
         if (!empty($check_id)) {
             $saved = fetch_details('products', ['id' => $check_id], 'status');
             if (!empty($saved) && (string) $saved[0]['status'] === '2') {
                 $message .= '. It is pending admin approval and will appear in the store once approved.';
             }
         }

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

    /**
     * Stream a bulk upload template with the page's settings already filled in.
     *
     * A blank sample left the seller to work out which of 31 columns mattered and what to write
     * in the coded ones. This writes the settings columns on every row from the choices made on
     * the page, in words, and leaves the product columns empty - so the file that arrives back
     * only ever needed the name, image and price typing in, and cannot be wrong about the parts
     * that used to cause the failures.
     */
    public function bulk_upload_template()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            return;
        }

        $defaults = collect_bulk_upload_defaults();
        $vocab = bulk_setting_vocabulary();

        $variants = (int) $this->input->post('template_variants');
        $variants = ($variants >= 1 && $variants <= 10) ? $variants : 1;
        $blank_rows = (int) $this->input->post('template_rows');
        $blank_rows = ($blank_rows >= 1 && $blank_rows <= 200) ? $blank_rows : 10;

        $header = [
            'category_id', 'product_type', 'name', 'short_description', 'description', 'image',
            'other_images', 'tags', 'sku', 'stock', 'tax', 'made_in', 'warranty_period',
            'guarantee_period', 'video_type', 'video', 'minimum_order_quantity',
            'quantity_step_size', 'total_allowed_quantity', 'cod_allowed', 'prices_include_tax',
            'returnable', 'food_type', 'delivery_area', 'pincodes',
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
        // No cancellable_until column: that is the admin's call, so the seller is neither
        // asked for it on the page nor given a column to answer it in.
        $prefilled = [
            19 => ($defaults['cod_allowed'] == 1) ? 'Yes' : 'No',
            20 => ($defaults['is_prices_inclusive_tax'] == 1) ? 'Yes' : 'No',
            21 => ($defaults['is_returnable'] == 1) ? 'Yes' : 'No',
            22 => $vocab['food_type']['labels'][(string) $defaults['indicator']] ?? 'Not a food product',
            23 => $vocab['delivery_area']['labels'][$defaults['deliverable_type']] ?? 'Everywhere',
            24 => $defaults['deliverable_zipcodes'],
        ];

        $row_template = array_fill(0, count($header), '');
        foreach ($prefilled as $index => $value) {
            $row_template[$index] = $value;
        }
        // Two hints the seller cannot get wrong by leaving them: quantities default to 1 anyway,
        // and product_type is the one column with only two legal spellings.
        $row_template[1] = 'simple_product';
        $row_template[16] = '1';
        $row_template[17] = '1';

        $filename = 'cretzo-bulk-upload-template.csv';
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
                // Excel on a non-US locale writes tab- or semicolon-separated files. Reading one
                // of those with a hard-coded "," collapsed every row into a single column, so the
                // importer rejected valid data with a message about column 2.
                $delimiter = detect_csv_delimiter($csv);
                // Widest index any branch below reads: 30 + (49 * 7) = 373.
                $row_width = 380;
                $handle = fopen($csv, "r");
                $allowed_status = array("received", "processed", "shipped");
                $video_types = array("youtube", "vimeo");
                $this->response['message'] = '';
                $type = $_POST['type'];

                // A file whose columns do not line up with the layout the code indexes would have
                // its variant block read off-by-one - price landing in attribute_value_ids and so
                // on - and silently import corrupt products. The seller layout has no seller_id
                // column (it comes from the login), so 29 product columns then 7 per variant on
                // upload, 6 per variant on update. Multi-variant files are still accepted.
                // The upload sheet is the simple layout: no seller_id column (ownership comes
                // from the login) and no numeric-coded setting columns. The update layout is
                // unchanged.
                $fixed_columns = ($type == 'upload') ? SIMPLE_FIXED_COLUMNS : 29;
                $variant_block = ($type == 'upload') ? SIMPLE_VARIANT_COLUMNS : 6;
                $header_row = fgetcsv($handle, 10000, $delimiter);
                $header_count = ($header_row === FALSE) ? 0 : count($header_row);
                if (!is_valid_bulk_header_width($header_count, $fixed_columns, $variant_block)) {
                    fclose($handle);
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = bulk_header_width_message($header_count, $fixed_columns, $variant_block);
                    print_r(json_encode($this->response));
                    return false;
                }
                rewind($handle);
                if ($type == 'upload') {
                    // The upload sheet used to carry every product setting as a numeric code -
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
                        print_r(json_encode($this->response));
                        return false;
                    }

                    // Column map for the simple sheet. Variant blocks start at
                    // SIMPLE_FIXED_COLUMNS and repeat every SIMPLE_VARIANT_COLUMNS.
                    $C = [
                        'category_id' => 0, 'product_type' => 1, 'name' => 2, 'short_description' => 3,
                        'description' => 4, 'image' => 5, 'other_images' => 6, 'tags' => 7,
                        'sku' => 8, 'stock' => 9, 'tax' => 10, 'made_in' => 11,
                        'warranty_period' => 12, 'guarantee_period' => 13, 'video_type' => 14, 'video' => 15,
                        'minimum_order_quantity' => 16, 'quantity_step_size' => 17, 'total_allowed_quantity' => 18,
                        // Written as words by the generated template; blank falls back to the form.
                        // No 'cancellable_until' key - the column is gone from the seller sheet,
                        // and resolve_bulk_row_settings() skips it when the map does not name it.
                        'cod_allowed' => 19, 'prices_include_tax' => 20, 'returnable' => 21,
                        'food_type' => 22, 'delivery_area' => 23, 'pincodes' => 24,
                    ];
                    $rows_data = [];
                    while (($row = fgetcsv($handle, 10000, $delimiter)) != FALSE) //get row values
                    {
                        // Short rows used to raise an "Undefined array key" warning per read; those warnings
                        // were echoed ahead of the JSON reply and left the caller unable to parse it.
                        $row = pad_csv_row($row, $row_width);

                        if ($temp != 0) {
                            $line = $temp;

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
                                $base = SIMPLE_FIXED_COLUMNS + ($v * SIMPLE_VARIANT_COLUMNS);
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
                        print_r(json_encode($this->response));
                        return false;
                    }

                    // Enforce the plan's listing limit before inserting bulk products.
                    $rows_to_add = count($rows_data);
                    $quota = $this->Seller_subscription_model->check_listing_quota($this->ion_auth->get_user_id(), $rows_to_add);
                    if (!$quota['allowed']) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = $this->Seller_subscription_model->quota_error_message($quota, $rows_to_add);
                        print_r(json_encode($this->response));
                        return false;
                    }

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
                        // Fixed, not taken from $settings: neither the page nor the sheet asks the
                        // seller about cancellation any more, and a hand-crafted POST of
                        // default_cancelable_till must not become a way around that. The admin can
                        // switch it on afterwards from the product form.
                        $data['is_cancelable'] = 0;
                        $data['cancelable_till'] = '';
                        $data['deliverable_type'] = $settings['deliverable_type'];
                        $data['deliverable_zipcodes'] = $settings['deliverable_zipcodes'];
                        // Ownership always comes from the login. There is deliberately no
                        // seller_id column in this sheet for a seller to get wrong.
                        $data['seller_id'] = $this->ion_auth->get_user_id();

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
                    print_r(json_encode($this->response));
                    return false;
                } else {
                    while (($row = fgetcsv($handle, 10000, $delimiter)) != FALSE) //get row vales
                    {
                        // Short rows used to raise an "Undefined array key" warning per read; those warnings
                        // were echoed ahead of the JSON reply and left the caller unable to parse it.
                        $row = pad_csv_row($row, $row_width);
                       
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
                    while (($row = fgetcsv($handle, 10000, $delimiter)) != FALSE) //get row values
                    {
                        // Short rows used to raise an "Undefined array key" warning per read; those warnings
                        // were echoed ahead of the JSON reply and left the caller unable to parse it.
                        $row = pad_csv_row($row, $row_width);
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
                                    $data['stock_type'] = normalise_stock_type($row[4]);
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
                                // Columns 14 and 15 of the update sheet are ignored on purpose:
                                // cancellation is the admin's setting, so a seller's update always
                                // keeps what the product already has, whatever the file says.
                                $data['is_cancelable'] = $product[0]['is_cancelable'];
                                $data['cancelable_till'] = $product[0]['cancelable_till'];
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
                                    $data['stock'] = sanitise_import_stock($row[24]);
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
                                        $variant_data[$i]['stock'] = sanitise_import_stock($row[$index]);
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
