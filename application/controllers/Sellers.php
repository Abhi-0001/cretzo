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
        $sellers = $this->Seller_model->get_sellers();
        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $sort_by = ($this->input->get('sort')) ? $this->input->get('sort', true) : '';
        $seller_search = ($this->input->get('seller_search')) ? $this->input->get('seller_search', true) : '';
        if (!empty($category_id)) {
            $category_id = explode('|', $category_id);
        }

        //Seller Sorting
        $sort = $order = '';
        if ($sort_by == "top-rated") {
            $sort = 'rating';
            $order = 'DESC';
        } elseif ($sort_by == "date-desc") {
            $sort = 'u.id';
            $order = 'desc';
        } elseif ($sort_by == "date-asc") {
            $sort = 'u.id';
            $order = 'asc';
        }

        $config['base_url'] = base_url('sellers');
        $config['total_rows'] = $sellers['total'];
        $config['per_page'] = $limit;
        $config['num_links'] = 7;
        $config['use_page_numbers'] = TRUE;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = FALSE;

        $config['attributes'] = array('class' => 'page-link');
        $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul>';

        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_link'] = 'First';
        $config['first_tag_close'] = '</li>';

        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_link'] = 'Last';
        $config['last_tag_close'] = '</li>';

        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_link'] = '<i class="fa fa-arrow-left"></i>';
        $config['prev_tag_close'] = '</li>';

        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_link'] = '<i class="fa fa-arrow-right"></i>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $page_no = (empty($this->uri->segment(2))) ? 1 : $this->uri->segment(2);
        if (!is_numeric($page_no)) {
            redirect(base_url('sellers'));
        }
        $offset = ($page_no - 1) * $limit;
        $this->pagination->initialize($config);
        $this->data['links'] =  $this->pagination->create_links();

        $this->data['main_page'] = 'seller-listing';
        $this->data['title'] = 'Seller Listing | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'Seller Listing, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Seller Listing | ' . $this->data['web_settings']['meta_description'];
        $this->data['seller_search'] = $seller_search;
        $sellers = $this->Seller_model->get_sellers("", $limit, $offset, $sort, $order, $seller_search);
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

        $config['base_url'] = base_url('sellers/seller_details/' . $seller_slug);
        $config['total_rows'] = $seller_products_count;
        $config['per_page'] = $limit;
        $config['num_links'] = 7;
        $config['use_page_numbers'] = TRUE;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = FALSE;

        $config['attributes'] = array('class' => 'page-link');
        $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul>';

        if (isset($theme[0]['name']) && strtolower($theme[0]['name']) == 'modern') {

            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_link'] = '<i class="uil uil-arrow-left"></i>';
            $config['prev_tag_close'] = '</li>';

            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_link'] = '<i class="uil uil-arrow-right"></i>';
            $config['next_tag_close'] = '</li>';
        } else {
            $config['first_tag_open'] = '<li class="page-item">';
            $config['first_link'] = 'First';
            $config['first_tag_close'] = '</li>';

            $config['last_tag_open'] = '<li class="page-item">';
            $config['last_link'] = 'Last';
            $config['last_tag_close'] = '</li>';

            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_link'] = '<i class="fa fa-arrow-left"></i>';
            $config['prev_tag_close'] = '</li>';

            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_link'] = '<i class="fa fa-arrow-right"></i>';
            $config['next_tag_close'] = '</li>';
        }

        $config['cur_tag_open'] = '<li class="page-item active disabled"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';

        $page_no = (empty($this->uri->segment(4))) ? 1 : $this->uri->segment(4);
        if (!is_numeric($page_no)) {
            redirect(base_url('sellers'));
        }
        $offset = ($page_no - 1) * $limit;
        $this->pagination->initialize($config);
        $this->data['links'] =  $this->pagination->create_links();


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