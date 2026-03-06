<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper', 'file']);
        $this->load->model(['Subscription_model', 'Setting_model']);

        // permission checks can be added here later if necessary
    }

    public function index()
    {
        redirect('admin/subscription/manage_subscriptions');
    }

    public function manage_subscriptions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // optional permission check
            // if (!has_permissions('read', 'subscription')) { ... }

            $this->data['main_page'] = TABLES . 'manage-subscriptions';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Subscription Plans | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Subscription Plans  | ' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('subscriptions', ['id' => $_GET['edit_id']]);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_subscription()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Subscription_model->get_list('subscriptions');
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_subscription()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // permission checks (optional) can be added here if the permission type is defined


            $this->form_validation->set_rules('name', 'Plan Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('price', 'Price', 'trim|xss_clean');
            $this->form_validation->set_rules('listings_limit', 'Listings Limit', 'trim|xss_clean');
            $this->form_validation->set_rules('validity', 'Validity', 'trim|xss_clean');
            $this->form_validation->set_rules('commission_first50', 'Commission (first 50 orders)', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('commission_51_100', 'Commission (51-100 orders)', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('commission_after100', 'Commission (after 100 orders)', 'trim|numeric|xss_clean');

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $this->Subscription_model->add_subscription($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_subscription'])) ? 'Subscription Updated Successfully' : 'Subscription Added Successfully';
                $this->response['message'] = $message;
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
