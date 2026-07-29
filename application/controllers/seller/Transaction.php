<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Transaction extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model('Transaction_model');
    }

    public function wallet_transactions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = TABLES . 'seller-wallet';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Seller wallet | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Seller wallet  | ' . $settings['app_name'];
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function view_transactions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $seller_id = $this->session->userdata('user_id');
            // group_id defaults to 2 ("members"/customers) in the model — a seller's own
            // account is in group 4, so without passing it explicitly here, the mandatory
            // group join in get_transactions_list() would always exclude every one of this
            // seller's own commission-credit transactions, making this page permanently
            // empty regardless of actual earnings.
            return $this->Transaction_model->get_transactions_list($seller_id, 4);
        } else {
            redirect('seller/login', 'refresh');
        }
    }
    public function edit_transactions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->form_validation->set_rules('status', 'status', 'trim|required|xss_clean');
            $this->form_validation->set_rules('txn_id', 'txn_id', 'trim|required|xss_clean');
            $this->form_validation->set_rules('id', 'id', 'trim|required|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                // A transaction's own user_id is the customer's, not the seller's, so
                // ownership here means: does this transaction's order actually include one
                // of the current seller's own order items? Without this check, any seller
                // could edit the status/txn_id/message of any transaction in the system —
                // including marking someone else's bank-transfer payment as verified.
                $txn = fetch_details('transactions', ['id' => $_POST['id']], 'order_id');
                $owned_order_item = !empty($txn)
                    ? fetch_details('order_items', ['order_id' => $txn[0]['order_id'], 'seller_id' => $this->ion_auth->get_user_id()], 'id')
                    : [];
                if (empty($owned_order_item)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = "Transaction not found";
                    print_r(json_encode($this->response));
                    return;
                }
                $_POST['message'] = (isset($_POST['message']) && trim($_POST['message']) != "") ? $this->input->post('message', true) : "";
                $updated = $this->Transaction_model->edit_transactions($_POST);
                $this->response['error'] = !$updated;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $updated ? "Transaction Updated Successfuly." : "Transaction update failed. Please try again.";
                print_r(json_encode($this->response));
            }
        } else {
            redirect('seller/login', 'refresh');
        }
    }
}
