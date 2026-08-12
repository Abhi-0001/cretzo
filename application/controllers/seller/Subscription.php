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
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
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

        // Upgrades and downgrades are both allowed and take effect immediately. Switching
        // to a DIFFERENT plan now credits the unused portion of the current paid plan
        // against the new plan's price (see calculate_proration), so a mid-cycle upgrade no
        // longer means paying twice for the overlapping days. Re-buying the SAME plan is a
        // renewal instead, and carries the unused days forward.
        $proration    = $this->Seller_subscription_model->calculate_proration($seller_id, $plan);
        $full_price   = $proration['full_price'];
        $amount_value = $proration['payable'];

        // Free plan, or a switch fully covered by the credit: activate with no payment.
        if ($amount_value <= 0) {
            $success = $this->Seller_subscription_model->assign_subscription(
                $seller_id,
                $subscription_id,
                isset($plan['validity']) ? $plan['validity'] : null,
                true
            );

            if ($success && $proration['credit'] > 0) {
                $message = 'Subscription activated. The ' . number_format($proration['credit'], 2)
                    . ' credit for the ' . $proration['days_remaining'] . ' unused day(s) on your '
                    . $proration['from_plan'] . ' plan covered the full cost.';
            } else {
                $message = 'Subscription activated successfully.';
            }

            $response = [
                'error' => !$success,
                'requires_payment' => false,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => $success ? $message : 'Failed to activate subscription.',
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

        // Razorpay is India-only for this account, and every price on the site is quoted
        // and displayed in Rupees — this must never read $payment_settings['currency_code'],
        // which is the PayPal gateway's own currency setting (a different, disabled
        // payment method) and was set to "USD" here, which is why the Razorpay checkout
        // widget showed a $ amount instead of ₹.
        $currency = 'INR';

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
            // Surfaced so the checkout screen can explain why the charge is lower than the
            // advertised plan price, instead of looking like a pricing bug.
            'full_price' => $full_price,
            'proration_credit' => $proration['credit'],
            'proration_days' => $proration['days_remaining'],
            'proration_from_plan' => $proration['from_plan'],
            'message' => $proration['credit'] > 0
                ? 'Razorpay order created. A credit of ' . number_format($proration['credit'], 2) . ' for ' . $proration['days_remaining'] . ' unused day(s) on your ' . $proration['from_plan'] . ' plan has been applied.'
                : 'Razorpay order created. Proceed to payment.',
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
        $razorpay_order_id = $this->input->post('razorpay_order_id', true);
        $razorpay_signature = $this->input->post('razorpay_signature', true);

        $this->load->library('razorpay');

        // 1. REPLAY GUARD. Nothing previously stopped the same razorpay_payment_id being
        //    POSTed here repeatedly, each time granting a fresh validity period from one
        //    single payment. (The wallet top-up path in app/v1/Api.php already had this
        //    check; the subscription and checkout paths did not.)
        $already_used = fetch_details('transactions', ['txn_id' => $razorpay_payment_id, 'status' => 'success'], 'id');
        if (!empty($already_used)) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'This payment has already been processed.',
            ];
            echo json_encode($response);
            return;
        }

        // 2. SIGNATURE. razorpay_signature was previously declared required and then never
        //    read - any non-empty string passed. This is the HMAC that actually proves the
        //    (order_id, payment_id) pair came from Razorpay and was not forged client-side.
        if (!$this->razorpay->verify_payment($razorpay_order_id, $razorpay_payment_id, $razorpay_signature)) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'Payment signature verification failed.',
            ];
            echo json_encode($response);
            return;
        }

        // 3. ORDER BINDING. purchase() stamps each order's receipt with
        //    seller_sub_{seller_id}_{subscription_id}_{time}. Re-reading it from Razorpay
        //    ties this payment to the seller and plan the order was actually created for,
        //    so a genuine payment for one (cheap) plan can't be replayed against a
        //    different, more expensive plan by editing the posted subscription_id.
        $order = $this->razorpay->fetch_order($razorpay_order_id);
        $receipt = isset($order['receipt']) ? (string) $order['receipt'] : '';
        $expected_receipt_prefix = 'seller_sub_' . $seller_id . '_' . $subscription_id . '_';
        if ($receipt === '' || strpos($receipt, $expected_receipt_prefix) !== 0) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'This payment does not belong to the selected plan.',
            ];
            echo json_encode($response);
            return;
        }

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

        // The amount to check against is the one on the Razorpay ORDER we created in
        // purchase(), not the plan's list price. Since proration can legitimately discount
        // a mid-cycle switch, comparing against the list price would reject a correct,
        // fully-paid prorated payment. The order is the authoritative record of what we
        // asked for: it was created server-side, its receipt is already bound to this
        // seller and plan (checked above), and Razorpay will not let a payment settle for
        // less than the order it belongs to.
        $amount_value = 0;
        if (isset($order['amount']) && is_numeric($order['amount'])) {
            $amount_value = ((float) $order['amount']) / 100; // paise -> rupees
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

        // 4. The payment Razorpay actually holds must reference the same order we just
        //    validated. Belt-and-braces alongside the HMAC above: it catches a payment id
        //    from a different order (another plan, a storefront order, a wallet top-up)
        //    being presented here.
        $payment_order_id = isset($verification['data']['order_id']) ? $verification['data']['order_id'] : null;
        if (empty($payment_order_id) || !hash_equals((string) $razorpay_order_id, (string) $payment_order_id)) {
            $response = [
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'This payment does not belong to the selected order.',
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

        // Renewing the SAME plan before it lapses is a renewal, not a switch: the unused
        // days are carried forward. Previously every purchase restarted the period from
        // today, so a seller who renewed a 365-day plan with 200 days still on it paid for
        // a year and silently lost those 200 days. (Carry-over applies only to the paid
        // path - re-selecting a free plan still just restarts it, so a free plan can't be
        // stacked into an ever-growing entitlement.)
        $success = $this->Seller_subscription_model->assign_subscription(
            $seller_id,
            $subscription_id,
            isset($plan['validity']) ? $plan['validity'] : null,
            true
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
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
        }

        $payment_id = $this->input->get('payment_id', true);
        $this->data['main_page'] = VIEW . 'payment_success';
        $this->data['title'] = 'Payment Success';
        $this->data['payment_id'] = $payment_id;
        $this->load->view('seller/template', $this->data);
    }
}

