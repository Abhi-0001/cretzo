<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->database();
		$this->load->library(['ion_auth', 'form_validation','upload']);
		$this->load->helper(['url', 'language','file']);		
        $this->load->model('Transaction_model');

		// This controller had NO permission check of any kind - only ion_auth->is_admin(),
		// which every admin-panel role satisfies. Verified live: an "Editor" account holding
		// only faq:read and settings:read could open every customer wallet, every seller
		// wallet and the full payment-transaction ledger. See the new 'transactions' module
		// in config/eshop.php; edit_transactions() is gated on 'update' separately below.
		if (!has_permissions('read', 'transactions')) {
			$this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
			redirect('admin/home', 'refresh');
		}	
	}

	public function customer_wallet()
	{
		if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{
			$this->data['main_page'] = TABLES.'customer-wallet';
			$settings=get_settings('system_settings',true);
			$this->data['title'] = 'Customer wallet | '.$settings['app_name'];
			$this->data['meta_description'] = ' Customer wallet  | '.$settings['app_name'];	
			$this->load->view('admin/template',$this->data);
		}
		else{
			redirect('admin/login','refresh');
		}
	}

    public function wallet_transactions()
	{
		if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{
			$this->data['main_page'] = TABLES.'seller-wallet';
			$settings=get_settings('system_settings',true);
			$this->data['title'] = 'Seller wallet | '.$settings['app_name'];
			$this->data['meta_description'] = ' Seller wallet  | '.$settings['app_name'];	
			$this->load->view('admin/template',$this->data);
		}
		else{
			redirect('admin/login','refresh');
		}
	}

	public function view_transaction()
	{
		if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{
			$this->data['main_page'] = TABLES.'transaction';
			$settings=get_settings('system_settings',true);
			$this->data['title'] = 'View Transaction | '.$settings['app_name'];
			$this->data['meta_description'] = ' View Transaction  | '.$settings['app_name'];	
			$this->load->view('admin/template',$this->data);
		}
		else{
			redirect('admin/login','refresh');
		}
	}

	public function view_transactions()
	{
		if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{			
			return $this->Transaction_model->get_transactions_list();
		}
		else{
			redirect('admin/login','refresh');
		}
	}
    public function edit_transactions()
    {
        if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{			
            // Rewriting a transaction's status and gateway txn_id is a money-facing write
            // and was completely ungated - the same Editor account above got back
            // "Transaction Updated Successfuly." from this endpoint.
            if (print_msg(!has_permissions('update', 'transactions'), PERMISSION_ERROR_MSG, 'transactions')) {
                return false;
            }
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
                $_POST['message'] = (isset($_POST['message']) && trim($_POST['message']) != "") ? $this->input->post('message', true) : "";
                // Was never checked - the model's update() result (a real boolean) was
                // discarded, so this always reported success even if nothing was saved.
                $updated = $this->Transaction_model->edit_transactions($_POST);
                $this->response['error'] = !$updated;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $updated ? "Transaction Updated Successfuly." : "Something went wrong.";
                print_r(json_encode($this->response));
            }
		}
		else{
			redirect('admin/login','refresh');
		}
    }
}	

?>