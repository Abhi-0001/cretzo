<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sellers extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['cart_model', 'category_model', 'rating_model', 'Home_model', 'Seller_model', 'Order_model']);
        $this->load->library(['pagination']);
        $this->data['settings'] = get_settings('system_settings', true);
        $this->data['web_settings'] = get_settings('web_settings', true);
        $this->data['is_logged_in'] = ($this->ion_auth->logged_in()) ? 1 : 0;
        $this->data['user'] = ($this->ion_auth->logged_in()) ? $this->ion_auth->user()->row() : array();
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
    }

    public function index()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        $this->form_validation->set_data($this->input->get(null, true));
        $this->form_validation->set_rules('per-page', 'Per Page', 'trim|numeric|xss_clean');

        if (!empty($_GET) && !$this->form_validation->run()) {
            redirect(base_url('sellers'));
        }

        /*
         * per-page is echoed back into the toolbar and used as the SQL limit, so it is
         * pinned to the four values the control actually offers - anything else (?per-page=9999
         * or a negative) falls back to 12 instead of being trusted.
         */
        $allowed_limits = [12, 16, 20, 24];
        $limit = (int) ($this->input->get('per-page', true) ?: 12);
        if (!in_array($limit, $allowed_limits, true)) {
            $limit = 12;
        }

        $sort_by = (string) $this->input->get('sort', true);
        $seller_search = (string) $this->input->get('seller_search', true);
        $view_type = ($this->input->get('type', true) === 'list') ? 'list' : 'grid';

        /*
         * Sorting. The old code left $sort/$order as empty strings for the default case,
         * which reached the model as order_by('', '') - so "Relevance" produced whatever
         * order MySQL felt like returning, and the same seller could appear on two pages
         * while another appeared on none. Every branch now names a real column.
         */
        $sort = 'u.id';
        $order = 'DESC';
        if ($sort_by == "top-rated") {
            $sort = 'sd.rating';
            $order = 'DESC';
        } elseif ($sort_by == "date-desc") {
            $sort = 'u.id';
            $order = 'DESC';
        } elseif ($sort_by == "date-asc") {
            $sort = 'u.id';
            $order = 'ASC';
        } else {
            $sort_by = '';
        }

        $page_no = (empty($this->uri->segment(2))) ? 1 : $this->uri->segment(2);
        if (!is_numeric($page_no)) {
            redirect(base_url('sellers'));
        }
        $page_no = max(1, (int) $page_no);
        $offset = ($page_no - 1) * $limit;

        /*
         * ONE query, not two. get_sellers() used to be called twice: once with no arguments
         * purely to read $sellers['total'], then again with the real filters. The first call
         * ignored the search term, so the pager was built from the UNFILTERED count - a search
         * matching two sellers still rendered pages 1..9 of nothing - and it also ran the
         * per-seller product-count subquery for every seller in the database on every page
         * load. The filtered call already returns its own filter-aware total, so use that.
         */
        $sellers = $this->Seller_model->get_sellers("", $limit, $offset, $sort, $order, $seller_search);
        $total_sellers = (int) $sellers['total'];

        $this->data['links'] = storefront_pagination(base_url('sellers'), $total_sellers, $limit);
        $this->data['main_page'] = 'seller-listing';
        $this->data['title'] = 'Seller Listing | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'Seller Listing, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Seller Listing | ' . $this->data['web_settings']['meta_description'];
        $this->data['seller_search'] = $seller_search;
        $this->data['sort_by'] = $sort_by;
        $this->data['per_page'] = $limit;
        $this->data['per_page_options'] = $allowed_limits;
        $this->data['view_type'] = $view_type;
        $this->data['page_no'] = $page_no;
        $this->data['total_sellers'] = $total_sellers;
        $this->data['sellers'] = $sellers['data'];
        $this->data['page_main_bread_crumb'] = "Seller Listing";
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }


    public function seller_details($seller_slug = '')
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        $this->form_validation->set_data($this->input->get(null, true));
        $this->form_validation->set_rules('per-page', 'Per Page', 'trim|numeric|xss_clean');

        if (!empty($_GET) && !$this->form_validation->run()) {
            redirect(base_url('sellers'));
        }
        $seller_slug = urldecode($seller_slug);
        $seller_data = fetch_details('seller_data', ['slug' => $seller_slug, 'status' => 1]);

        // A seller whose slug was never filled in can only be linked to by user id, and
        // older links may still carry an id. Resolve those here instead of bouncing the
        // visitor to the full seller listing, and mint the missing slug on the way so the
        // storefront settles on one canonical URL.
        if (empty($seller_data) && ctype_digit((string) $seller_slug) && (int) $seller_slug > 0) {
            $seller_data = fetch_details('seller_data', ['user_id' => (int) $seller_slug, 'status' => 1]);
            if (!empty($seller_data)) {
                $existing_slug = isset($seller_data[0]['slug']) ? trim((string) $seller_data[0]['slug']) : '';
                if ($existing_slug === '') {
                    $source = '';
                    foreach (['store_name', 'shop_name'] as $field) {
                        if (!empty($seller_data[0][$field]) && trim($seller_data[0][$field]) !== '') {
                            $source = $seller_data[0][$field];
                            break;
                        }
                    }
                    $existing_slug = ($source !== '') ? create_unique_slug($source, 'seller_data', 'slug', 'id', $seller_data[0]['id']) : '';
                    if ($existing_slug === '') {
                        $existing_slug = create_unique_slug('seller-' . $seller_data[0]['user_id'], 'seller_data', 'slug', 'id', $seller_data[0]['id']);
                    }
                    update_details(['slug' => $existing_slug], ['id' => $seller_data[0]['id']], 'seller_data');
                }
                redirect(base_url('sellers/seller_details/' . rawurlencode($existing_slug)));
            }
        }

        if (empty($seller_data)) {
            redirect(base_url('sellers'));
        }
        $seller_details = fetch_details('users', ['id' => $seller_data[0]['user_id']]);


        $total_ord = 0;
        $sellers = $this->Seller_model->get_sellers();
        $total_orders =  fetch_details('order_items', ['seller_id' => $seller_data[0]['user_id']]);
        foreach ($total_orders as $total) {
            $total_ord += $total['quantity'];
        }
        // print_r($total_ord);

        // echo "<pre>";
        // print_r($seller_data);
        // print_r($sellers['data']);
        // die;

        $theme = fetch_details('themes', ['status' => 1], 'name');

        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $seller_products_count = fetch_product('', '', '', '', '', '', '', '', true, '', $seller_data[0]['user_id']);

        $page_no = (empty($this->uri->segment(4))) ? 1 : $this->uri->segment(4);
        if (!is_numeric($page_no)) {
            redirect(base_url('sellers'));
        }
        $offset = ($page_no - 1) * $limit;
        $this->data['links'] = storefront_pagination(
            base_url('sellers/seller_details/' . $seller_slug),
            $seller_products_count,
            $limit,
            ['uri_segment' => 4]
        );


        $this->data['main_page'] = 'seller-details';
        $this->data['title'] = 'Seller Details | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'Seller Details, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Seller Details | ' . $this->data['web_settings']['meta_description'];
        $this->data['sellers'] = $seller_data;
        $this->data['seller_details'] = $seller_details;
        $seller_products = fetch_product('', '', '', '', $limit, $offset, '', '', '', '', $seller_data[0]['user_id']);
        $this->data['seller_products'] = $seller_products['product'];
        $this->data['seller_products_count'] = $seller_products_count;
        $this->data['total_orders'] = $total_ord;
        $this->data['page_main_bread_crumb'] = "Seller Details";

        /* Adding for Cretzo */
        // $seller_categories = $this->category_model->get_seller_categories($seller_data[0]['user_id']);
        $seller_categories = $this->category_model->get_categories('', 8, '', 'clicks', 'DESC', 'false', '', '', $seller_data[0]['user_id'], false);
        $this->data['seller_categories'] = $seller_categories;

        /* Products for different types (most selling, top rated, etc) */
        $filters = [];
        $filters['show_only_active_products'] = true;
        
        $filters['product_type'] = 'new_added_products';
        $this->data['products_new_added'] = fetch_product('', $filters, '', '', 12, $offset, '', '', '', '', $seller_data[0]['user_id'])['product'];
        
        $filters['product_type'] = 'most_selling_products';
        $this->data['products_most_selling'] = fetch_product('', $filters, '', '', 12, $offset, '', '', '', '', $seller_data[0]['user_id'])['product'];

        $filters['product_type'] = 'top_rated_products';
        $this->data['products_top_rated'] = fetch_product('', $filters, '', '', 12, $offset, '', '', '', '', $seller_data[0]['user_id'])['product'];


        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    public function get_seller_details()
    {
        $this->form_validation->set_rules('seller_slug', 'Seller Slug', 'trim|xss_clean|required');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = validation_errors();
            $this->response['data'] = array();
            echo json_encode($this->response);
        } else {
            $seller_slug = $_POST['seller_slug'];
            $seller_data = fetch_details('seller_data', ['slug' => $seller_slug]);

            $seller_categories = $this->category_model->get_categories('', 40, '', 'clicks', 'DESC', 'false', '', '', $seller_data[0]['user_id'], false);
            $seller_data[0]['seller_categories'] = $seller_categories;

            $this->response['error'] = false;
            $this->response['message'] = "Fetched seller data.";
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['data'] = $seller_data;
            echo json_encode($this->response);
            return false;
            
        }
    }
    
}