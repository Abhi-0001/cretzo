<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Product_faqs extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['product_model', 'product_faqs_model']);
    }

    // Fetches a FAQ only if its product belongs to the current seller — used before any
    // read/update/delete so a seller can never touch another seller's FAQ by guessing an id.
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

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {
            $this->data['main_page'] = TABLES . 'manage-product-faqs';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Product FAQS Management | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Product FAQs Management |' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = $this->fetch_owned_faq($_GET['edit_id'], $this->ion_auth->get_user_id());
            }
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function get_faqs_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {

            return $this->product_faqs_model->get_faqs($this->ion_auth->get_user_id());
        } else {
            redirect('seller/login', 'refresh');
        }
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

            if (!empty($_POST['edit_product_faq']) && empty($this->fetch_owned_faq($_POST['edit_product_faq'], $this->ion_auth->get_user_id()))) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'FAQ not found';
                print_r(json_encode($this->response));
                return;
            }

            $this->product_faqs_model->add_product_faqs($_POST);
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
    public function create_product_faqs()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {
            $this->data['main_page'] = FORMS . 'add-product-faqs';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Add Product FAQS Management | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Product FAQs Management |' . $settings['app_name'];
            $this->data['seller_products'] = fetch_details('products', ['seller_id' => $this->ion_auth->get_user_id()], 'id,name');
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function add_faqs()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {
            $seller_id = $this->ion_auth->get_user_id();

            // A seller must only be able to add a FAQ against their own product — otherwise
            // they could post fake/unwanted Q&A content onto a competitor's listing.
            $owned_product = !empty($_POST['product_id'])
                ? fetch_details('products', ['id' => $_POST['product_id'], 'seller_id' => $seller_id], 'id')
                : [];
            if (empty($owned_product)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Please select one of your own products';
                print_r(json_encode($this->response));
                return;
            }

            $_POST['seller_id'] = $seller_id;
            $this->product_faqs_model->add_product_faqs($_POST);
            $this->response['error'] = false;
            $this->response['message'] = 'Faq added Succesfully';
            print_r(json_encode($this->response));
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

            $this->product_faqs_model->delete_faq($_GET['id']);

            $this->response['error'] = false;
            $this->response['message'] = 'Deleted Succesfully';

            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }
}
