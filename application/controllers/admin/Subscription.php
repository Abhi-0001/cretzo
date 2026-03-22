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

            echo json_encode($this->response);
            return;
        }

        /* ---------- FEATURES JSON ---------- */

        $features = $this->input->post('features');
        $features_array = [];

        if (!empty($features)) {

            foreach ($features as $feature) {

                if (!empty($feature['description'])) {
                    $features_array[] = [
                        "description" => $feature['description']
                    ];
                }

            }
        }

        /* ---------- DATA ARRAY ---------- */

        $data = [
            'name' => $this->input->post('name'),
            'price' => $this->input->post('price'),
            'listings_limit' => $this->input->post('listings_limit'),
            'validity' => $this->input->post('validity'),
            'commission_first50' => $this->input->post('commission_first50'),
            'commission_51_100' => $this->input->post('commission_51_100'),
            'commission_after100' => $this->input->post('commission_after100'),

            // save features JSON
            'features' => json_encode($features_array)
        ];
        if ($this->input->post('edit_subscription')) {

            $data['edit_subscription'] = $this->input->post('edit_subscription');
        
        }

        /* ---------- UPDATE OR INSERT ---------- */

        if ($this->input->post('edit_subscription')) {

            $id = $this->input->post('edit_subscription');

            $this->Subscription_model->add_subscription($data);

            $message = 'Subscription Updated Successfully';

        } else {

            $this->Subscription_model->add_subscription($data);

            $message = 'Subscription Added Successfully';
        }

        $this->response['error'] = false;
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        $this->response['message'] = $message;

        echo json_encode($this->response);

    } else {

        redirect('admin/login', 'refresh');

    }
 }
}

