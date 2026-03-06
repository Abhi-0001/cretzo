<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['Subscription_model', 'Seller_subscription_model']);
    }

    public function index()
    {
        redirect('seller/subscription/manage_subscriptions');
    }

    public function manage_subscriptions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $user_id = $this->session->userdata('user_id');
            $settings = get_settings('system_settings', true);

            $this->data['main_page'] = VIEW . 'subscription';
            $this->data['title'] = 'Subscription Plans | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Subscription Plans | ' . $settings['app_name'];
            $this->data['plans'] = $this->Subscription_model->get_plans();
            $this->data['active_subscription'] = $this->Seller_subscription_model->get_active_subscription($user_id);
            $this->data['latest_subscription'] = $this->Seller_subscription_model->get_latest_subscription($user_id);

            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function purchase()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            redirect('seller/login', 'refresh');
        }

        $this->form_validation->set_rules('subscription_id', 'Subscription Plan', 'trim|required|integer|xss_clean');

        if (!$this->form_validation->run()) {
            $response['error'] = true;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = validation_errors();
            echo json_encode($response);
            return;
        }

        $seller_id = $this->session->userdata('user_id');
        $subscription_id = $this->input->post('subscription_id', true);

        $plan = $this->db->where('id', $subscription_id)->get('subscriptions')->row_array();
        if (empty($plan)) {
            $response['error'] = true;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = 'Selected subscription plan not found';
            echo json_encode($response);
            return;
        }

        // prevent downgrades: only allow same or higher priced plans
        $current_subscription = $this->Seller_subscription_model->get_active_subscription($seller_id);
        if (empty($current_subscription)) {
            $current_subscription = $this->Seller_subscription_model->get_latest_subscription($seller_id);
        }

        if (!empty($current_subscription)) {
            $current_plan = $this->db->where('id', $current_subscription['subscription_id'])->get('subscriptions')->row_array();

            $current_price = 0;
            $new_price = 0;

            if (!empty($current_plan['price'])) {
                $current_price_clean = preg_replace('/[^\d\.]/', '', $current_plan['price']);
                $current_price = is_numeric($current_price_clean) ? (float) $current_price_clean : 0;
            }

            if (!empty($plan['price'])) {
                $new_price_clean = preg_replace('/[^\d\.]/', '', $plan['price']);
                $new_price = is_numeric($new_price_clean) ? (float) $new_price_clean : 0;
            }

            if ($current_price > 0 && $new_price > 0 && $new_price < $current_price) {
                $response['error'] = true;
                $response['csrfName'] = $this->security->get_csrf_token_name();
                $response['csrfHash'] = $this->security->get_csrf_hash();
                $response['message'] = 'You cannot downgrade to a lower plan. Please choose the same or a higher plan.';
                echo json_encode($response);
                return;
            }
        }

        $success = $this->Seller_subscription_model->assign_subscription($seller_id, $subscription_id, isset($plan['validity']) ? $plan['validity'] : null);

        $response['error'] = !$success;
        $response['csrfName'] = $this->security->get_csrf_token_name();
        $response['csrfHash'] = $this->security->get_csrf_hash();
        $response['message'] = $success ? 'Subscription purchased successfully' : 'Failed to purchase subscription';

        echo json_encode($response);
    }
}

