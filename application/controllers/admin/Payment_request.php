<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_request extends CI_Controller {


	public function __construct(){
		parent::__construct();
		$this->load->database();
		$this->load->library(['ion_auth', 'form_validation','upload']);
		$this->load->helper(['url', 'language','file']);		
		$this->load->model('payment_request_model');		

        if (!has_permissions('read', 'payment_request')) {
            $this->session->set_flashdata('authorize_flag',PERMISSION_ERROR_MSG);
            redirect('admin/home','refresh');
        }

	}

	public function index(){
		if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{
			$this->data['main_page'] = TABLES.'payment-request';
			$settings=get_settings('system_settings',true);
			$this->data['title'] = 'Payment Request | '.$settings['app_name'];
			$this->data['meta_description'] = ' Return Request  | '.$settings['app_name'];
			$this->load->view('admin/template',$this->data);
		}
		else{
			redirect('admin/login','refresh');
		}
    }

	public function update_payment_request()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $status = fetch_details('payment_requests', ['id' => $_POST['payment_request_id']], 'status');
            if (print_msg(!has_permissions('update', 'payment_request'), PERMISSION_ERROR_MSG, 'payment_request')) {
                return false;
            }
            $this->form_validation->set_rules('payment_request_id', 'id', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('status', 'Status', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('update_remarks', 'Remarks ', 'trim|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                // Was only blocking a second REJECT (status==2) - an already-Approved request
                // (status==1, meaning the payout was already sent to the seller for real,
                // outside this system) could still be flipped to Rejected, which credits the
                // withdrawal amount back to the seller's wallet a second time on top of the
                // real payout already made. Once a request has left Pending, it's final.
                if ($status[0]['status'] != 0) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'This payment request has already been finalized.';
                    print_r(json_encode($this->response));
                } else {
                    $updated = $this->payment_request_model->update_payment_request($_POST);
                    $this->response['error'] = !$updated;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = $updated ? 'Payment request updated successfully' : 'Something went wrong.';
                    print_r(json_encode($this->response));
                }
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function view_payment_request_list(){
		if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{			
			return $this->payment_request_model->get_payment_request_list();
		} else {
			redirect('admin/login','refresh');
		}		
	}
}
?>