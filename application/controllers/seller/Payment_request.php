<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Payment_request extends CI_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['payment_request_model', 'delivery_boy_model']);
    }

    public function withdrawal_requests()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = TABLES . 'withdrawal-request';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Seller wallet | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Seller wallet  | ' . $settings['app_name'];
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }



    public function view_withdrawal_request_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $seller_id = $this->session->userdata('user_id');
            return $this->payment_request_model->get_payment_request_list($seller_id);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function send_withdrawal_request()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = FORMS . 'send-withdrawal-request';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Send Withdrawal Request | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Send Withdrawal Request  | ' . $settings['app_name'];
            $seller_id = $this->session->userdata('user_id');
            $this->data['seller_id'] = $seller_id;
            // The form asked for an amount with no indication of what was actually available
            // or what the minimum was, so the only way to discover either was to be rejected.
            $balance = fetch_details('users', ['id' => $seller_id], 'balance');
            $this->data['wallet_balance'] = isset($balance[0]['balance']) ? (float) $balance[0]['balance'] : 0;
            $this->data['min_withdrawal'] = Payment_request_model::MIN_WITHDRAWAL_AMOUNT;
            $this->data['has_pending'] = (bool) $this->db
                ->where('user_id', $seller_id)
                ->where('status', 0)
                ->count_all_results('payment_requests');
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function add_withdrawal_request()
    {
        // This endpoint had NO auth check at all, and trusted a client-supplied user_id
        // for whose wallet balance to withdraw from and deduct — meaning any unauthenticated
        // request could drain any user's balance to an attacker-supplied payment address.
        // user_id must always be the authenticated seller's own id, never a POST value.
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
            return;
        }

        $this->form_validation->set_rules('payment_address', 'Payment Address', 'trim|required|xss_clean');
        $this->form_validation->set_rules('amount', 'Amount', 'trim|required|xss_clean|numeric|greater_than[0]');

        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            print_r(json_encode($this->response));
        } else {
            $user_id = $this->ion_auth->get_user_id();
            $payment_address = $this->input->post('payment_address', true);
            $amount = round((float) $this->input->post('amount', true), 2);

            // The whole balance-check / insert / debit sequence now runs through one model
            // method inside a single transaction with the user row locked. Previously the
            // check and the debit were separate unlocked statements, so two requests
            // submitted at once could both pass the "amount <= balance" check against the
            // same starting balance and both be inserted - letting a seller withdraw more
            // than they had and leaving users.balance negative.
            $result = $this->payment_request_model->create_withdrawal_request($user_id, 'seller', $amount, $payment_address);

            $this->response['error'] = $result['error'];
            $this->response['message'] = $result['message'];
            $this->response['data'] = isset($result['balance']) ? $result['balance'] : array();
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();

            // This used to sit inside `if (!empty($userData))`, so when the user row could not
            // be read the endpoint returned a completely EMPTY body - the seller's form showed
            // no error and no success, it just silently did nothing.
            print_r(json_encode($this->response));
        }
    }
}
