<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
        $this->load->model(['Subscription_model', 'Seller_subscription_model', 'Transaction_model']);
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
            $this->data['launch_offer_active'] = $this->Seller_subscription_model->is_launch_offer_active();

            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function details($id = null)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller()) {
            redirect('seller/login', 'refresh');
        }

        $settings = get_settings('system_settings', true);
        $plan = $this->Subscription_model->get_plan($id);

        $this->data['main_page'] = VIEW . 'subscription_details';
        $this->data['title'] = 'Subscription Detail | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Subscription Detail | ' . $settings['app_name'];
        $this->data['plan'] = $plan;

        $this->load->view('seller/template', $this->data);
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
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'Selected subscription plan not found',
            ];
            echo json_encode($response);
            return;
        }

        // The launch promotion is auto-granted to the first 100 vendors at sign up only;
        // it can't be self-selected here (that would bypass the 100-vendor cap).
        if (isset($plan['name']) && strcasecmp(trim($plan['name']), 'Launch Offer') === 0) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'The Launch Offer is an automatic promotion for the first 100 vendors and cannot be selected manually.',
            ];
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
                $response = [
                    'error' => true,
                    'csrfName' => $this->security->get_csrf_token_name(),
                    'csrfHash' => $this->security->get_csrf_hash(),
                    'message' => 'You cannot downgrade to a lower plan. Please choose the same or a higher plan.',
                ];
                echo json_encode($response);
                return;
            }
        }

        // normalize plan amount (numeric only, treat non‑numeric / empty as 0 i.e. free)
        $amount_value = 0;
        if (!empty($plan['price'])) {
            $clean_price = preg_replace('/[^\d\.]/', '', $plan['price']);
            $amount_value = is_numeric($clean_price) ? (float) $clean_price : 0;
        }

        // If plan is free or has 0 price, activate subscription immediately (no payment)
        if ($amount_value <= 0) {
            $success = $this->Seller_subscription_model->assign_subscription(
                $seller_id,
                $subscription_id,
                isset($plan['validity']) ? $plan['validity'] : null
            );

            $response = [
                'error' => !$success,
                'requires_payment' => false,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => $success ? 'Subscription activated successfully.' : 'Failed to activate subscription.',
            ];
            echo json_encode($response);
            return;
        }

        // Paid plan – create Razorpay order and return details to frontend
        $payment_settings = get_settings('payment_method', true);
        if (empty($payment_settings['razorpay_payment_method']) || $payment_settings['razorpay_payment_method'] != '1') {
            $response = [
                'error' => true,
                'requires_payment' => false,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'Razorpay payment method is not enabled. Please contact administrator.',
            ];
            echo json_encode($response);
            return;
        }

        $currency = isset($payment_settings['currency_code']) && !empty($payment_settings['currency_code']) ? $payment_settings['currency_code'] : 'INR';

        $this->load->library('razorpay');

        $amount_paise = (int) round($amount_value * 100);
        $receipt = 'seller_sub_' . $seller_id . '_' . $subscription_id . '_' . time();

        $order = $this->razorpay->create_order($amount_paise, $receipt, $currency);

        if (isset($order['error'])) {
            $message = isset($order['error']['description']) ? $order['error']['description'] : 'Unable to create Razorpay order.';
            $response = [
                'error' => true,
                'requires_payment' => false,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => $message,
            ];
            echo json_encode($response);
            return;
        }

        $credentials = $this->razorpay->get_credentials();
        $user = $this->ion_auth->user($seller_id)->row();

        $response = [
            'error' => false,
            'requires_payment' => true,
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
            'razorpay_order_id' => isset($order['id']) ? $order['id'] : '',
            'amount' => $amount_value,
            'currency' => $currency,
            'plan_name' => isset($plan['name']) ? $plan['name'] : '',
            'subscription_id' => (int) $subscription_id,
            'razorpay_key_id' => isset($credentials['key_id']) ? $credentials['key_id'] : '',
            'seller_name' => isset($user->username) ? $user->username : '',
            'seller_email' => isset($user->email) ? $user->email : '',
            'seller_contact' => isset($user->mobile) ? $user->mobile : '',
            'message' => 'Razorpay order created. Proceed to payment.',
        ];

        echo json_encode($response);
    }

    public function razorpay_callback()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            redirect('seller/login', 'refresh');
        }

        $this->form_validation->set_rules('subscription_id', 'Subscription Plan', 'trim|required|integer|xss_clean');
        $this->form_validation->set_rules('razorpay_payment_id', 'Razorpay Payment ID', 'trim|required|xss_clean');
        $this->form_validation->set_rules('razorpay_order_id', 'Razorpay Order ID', 'trim|required|xss_clean');
        $this->form_validation->set_rules('razorpay_signature', 'Razorpay Signature', 'trim|required|xss_clean');

        if (!$this->form_validation->run()) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => validation_errors(),
            ];
            echo json_encode($response);
            return;
        }

        $seller_id = $this->session->userdata('user_id');
        $subscription_id = $this->input->post('subscription_id', true);
        $razorpay_payment_id = $this->input->post('razorpay_payment_id', true);

        $plan = $this->db->where('id', $subscription_id)->get('subscriptions')->row_array();
        if (empty($plan)) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'Selected subscription plan not found',
            ];
            echo json_encode($response);
            return;
        }

        $amount_value = 0;
        if (!empty($plan['price'])) {
            $clean_price = preg_replace('/[^\d\.]/', '', $plan['price']);
            $amount_value = is_numeric($clean_price) ? (float) $clean_price : 0;
        }

        if ($amount_value <= 0) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'This subscription does not require payment.',
            ];
            echo json_encode($response);
            return;
        }

        // Verify payment with Razorpay APIs (captures if needed)
        $verification = verify_payment_transaction($razorpay_payment_id, 'razorpay');
        if (!isset($verification['error']) || $verification['error'] === true) {
            $msg = isset($verification['message']) ? $verification['message'] : 'Unable to verify Razorpay payment.';
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => $msg,
            ];
            echo json_encode($response);
            return;
        }

        $paid_amount = isset($verification['amount']) ? (float) $verification['amount'] : 0.0;
        if ($paid_amount <= 0 || $paid_amount < $amount_value) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'Paid amount does not match plan amount.',
            ];
            echo json_encode($response);
            return;
        }

        $success = $this->Seller_subscription_model->assign_subscription(
            $seller_id,
            $subscription_id,
            isset($plan['validity']) ? $plan['validity'] : null
        );

        if ($success) {
            $transaction_data = [
                'transaction_type' => 'transaction',
                'user_id' => $seller_id,
                'order_id' => 0,
                'order_item_id' => 0,
                'type' => 'razorpay',
                'txn_id' => $razorpay_payment_id,
                'amount' => $paid_amount,
                'status' => 'success',
                'message' => 'Seller subscription payment for plan ' . (isset($plan['name']) ? $plan['name'] : ''),
            ];
            $this->Transaction_model->add_transaction($transaction_data);
        }

        $response = [
            'error' => !$success,
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
            'message' => $success ? 'Subscription activated successfully.' : 'Failed to activate subscription after payment.',
        ];

        echo json_encode($response);
    }

    public function payment_success()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller()) {
            redirect('seller/login', 'refresh');
        }

        $payment_id = $this->input->get('payment_id', true);
        $this->data['main_page'] = VIEW . 'payment_success';
        $this->data['title'] = 'Payment Success';
        $this->data['payment_id'] = $payment_id;
        $this->load->view('seller/template', $this->data);
    }
}

