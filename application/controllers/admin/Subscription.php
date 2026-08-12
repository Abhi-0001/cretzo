<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper', 'file']);
        $this->load->model(['Subscription_model', 'Setting_model', 'Seller_subscription_model']);

        // permission checks can be added here later if necessary
    }

    // Admin visibility into per-seller subscriptions (plan, status, listing usage,
    // expiry) - previously admin had no way to see any of this outside opening the
    // seller-facing dashboard as that seller, and no way to assign/extend/cancel a
    // seller's subscription at all.
    public function seller_subscriptions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // is_admin() is true for EVERY system-user role (super_admin/admin/editor/
            // supporter are all in group 1), so it alone does not restrict anything.
            // These endpoints assign, extend and cancel real paid subscriptions, so they
            // need a granular permission too - 'subscription' is now a registered module
            // in config/eshop.php.
            if (!has_permissions('read', 'subscription')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            $this->data['main_page'] = TABLES . 'seller-subscriptions';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Seller Subscriptions | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Seller Subscriptions | ' . $settings['app_name'];
            $this->data['plans'] = $this->Subscription_model->get_plans();
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_seller_subscriptions()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('read', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $rows = $this->Seller_subscription_model->get_all_seller_subscription_status();
        foreach ($rows as &$row) {
            $row['shop_name'] = html_escape($row['shop_name']);
            $row['operate'] = '<button type="button" class="btn btn-primary-theme btn-xs manage-subscription-btn" data-seller-id="' . $row['seller_id'] . '" data-shop-name="' . $row['shop_name'] . '"><i class="fa fa-cog"></i> Manage</button>';
        }

        echo json_encode(['total' => count($rows), 'rows' => $rows]);
    }

    public function assign_seller_subscription()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('update', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|integer|xss_clean');
        $this->form_validation->set_rules('subscription_id', 'Plan', 'trim|required|integer|xss_clean');
        if (!$this->form_validation->run()) {
            echo json_encode(['error' => true, 'message' => validation_errors()]);
            return;
        }

        $seller_id = $this->input->post('seller_id', true);
        $subscription_id = $this->input->post('subscription_id', true);
        $plan = $this->db->where('id', $subscription_id)->get('subscriptions')->row_array();
        if (empty($plan)) {
            echo json_encode(['error' => true, 'message' => 'Plan not found.']);
            return;
        }

        $success = $this->Seller_subscription_model->assign_subscription($seller_id, $subscription_id, isset($plan['validity']) ? $plan['validity'] : null);
        echo json_encode(['error' => !$success, 'message' => $success ? 'Plan assigned successfully.' : 'Failed to assign plan.']);
    }

    public function extend_seller_subscription()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('update', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|integer|xss_clean');
        $this->form_validation->set_rules('days', 'Days', 'trim|required|integer|greater_than[0]|xss_clean');
        if (!$this->form_validation->run()) {
            echo json_encode(['error' => true, 'message' => validation_errors()]);
            return;
        }

        $success = $this->Seller_subscription_model->extend_subscription($this->input->post('seller_id', true), $this->input->post('days', true));
        echo json_encode(['error' => !$success, 'message' => $success ? 'Subscription extended successfully.' : 'No active, time-limited subscription to extend.']);
    }

    public function cancel_seller_subscription()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('delete', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|integer|xss_clean');
        if (!$this->form_validation->run()) {
            echo json_encode(['error' => true, 'message' => validation_errors()]);
            return;
        }

        $success = $this->Seller_subscription_model->deactivate_subscription($this->input->post('seller_id', true));
        echo json_encode(['error' => !$success, 'message' => $success ? 'Subscription cancelled successfully.' : 'No active subscription to cancel.']);
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
        // price/validity only had 'xss_clean' - not even 'numeric' - so a plan could be saved
        // with a negative or non-numeric price, or a zero/negative validity period, with
        // nothing stopping it server-side beyond a client-side keypress filter that a direct
        // POST bypasses entirely.
        $this->form_validation->set_rules('price', 'Price', 'trim|required|numeric|greater_than_equal_to[0]|xss_clean');
        $this->form_validation->set_rules('listings_limit', 'Listings Limit', 'trim|xss_clean');
        $this->form_validation->set_rules('validity', 'Validity', 'trim|required|numeric|greater_than[0]|xss_clean');
        $this->form_validation->set_rules('commission_first50', 'Commission (first 50 orders)', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[100]|xss_clean');
        $this->form_validation->set_rules('commission_51_100', 'Commission (51-100 orders)', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[100]|xss_clean');
        $this->form_validation->set_rules('commission_after100', 'Commission (after 100 orders)', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[100]|xss_clean');
        // Feature descriptions had no validation rule at all - completely bypassing xss_clean
        // unlike every other free-text field on this same form.
        $features_post = $this->input->post('features');
        if (!empty($features_post) && is_array($features_post)) {
            foreach (array_keys($features_post) as $i) {
                $this->form_validation->set_rules('features[' . $i . '][description]', 'Feature', 'trim|xss_clean');
            }
        }

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

