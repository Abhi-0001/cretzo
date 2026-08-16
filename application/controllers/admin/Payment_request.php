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
            if (print_msg(!has_permissions('update', 'payment_request'), PERMISSION_ERROR_MSG, 'payment_request')) {
                return false;
            }
            // Was read before the permission check and straight out of $_POST with no isset()
            // guard, so a request missing payment_request_id raised an undefined-index notice
            // before any validation ran.
            $request_id = $this->input->post('payment_request_id', true);
            $status = !empty($request_id) ? fetch_details('payment_requests', ['id' => $request_id], 'status') : [];
            $this->form_validation->set_rules('payment_request_id', 'id', 'trim|required|numeric|xss_clean');
            // 'numeric' alone accepted any number - including 7, or 0 to "re-pend" a request.
            // Only the three real states are valid.
            $this->form_validation->set_rules('status', 'Status', 'trim|required|in_list[0,1,2]|xss_clean');
            $this->form_validation->set_rules('update_remarks', 'Remarks ', 'trim|xss_clean');
            $this->form_validation->set_rules('payment_reference', 'Payout Reference', 'trim|max_length[128]|xss_clean');
            // An approval asserts the money was actually sent, so it has to carry the reference
            // for that transfer - otherwise there is no record tying the approval to a payment.
            if ($this->input->post('status') == 1) {
                $this->form_validation->set_rules('payment_reference', 'Payout Reference', 'trim|required|max_length[128]|xss_clean');
            }
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
                // empty($status) covers an id that doesn't exist - previously that dereferenced
                // $status[0] and fataled on an undefined index.
                if (empty($status) || $status[0]['status'] != 0) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = empty($status)
                        ? 'Payment request not found.'
                        : 'This payment request has already been finalized.';
                    print_r(json_encode($this->response));
                } elseif ($this->input->post('status') == 0) {
                    // Saving a still-Pending request as Pending is a no-op that would stamp a
                    // processed_by/processed_at audit entry for a decision never made.
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Choose Approved or Rejected to action this request.';
                    print_r(json_encode($this->response));
                } else {
                    $updated = $this->payment_request_model->update_payment_request($_POST, $this->ion_auth->get_user_id());
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