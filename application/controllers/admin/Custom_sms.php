<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Custom_sms extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['custom_sms_model']);
    }

    public function index()
     {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            return;
        }

        /*
         * This route returned a hard 500 - "Unable to load the requested file:
         * admin/pages/forms/custom_sms.php". That view does not exist and never did; the
         * custom-SMS/notification-template editor and its list are rendered inside
         * admin/pages/forms/sms-gateway-settings.php instead (it posts to
         * admin/custom_sms/add_sms and reads admin/custom_sms/view_sms, both of which work).
         *
         * Nothing in the admin sidebar links here, so the 500 was only reachable by typing the
         * URL - but it is still a broken page on an admin panel, and now that the transactional
         * notification matrix is switched on these templates matter. Send the admin to the screen
         * that actually edits them rather than erroring.
         */
        $target = 'admin/sms-gateway-settings';
        if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
            $target .= '?edit_id=' . (int) $_GET['edit_id'];
        }
        redirect($target, 'refresh');
    }
    public function add_sms()
    {
        if (isset($_POST['edit_sms'])) {
            if (print_msg(!has_permissions('update', 'custom_sms'), PERMISSION_ERROR_MSG, 'custom_sms')) {
                return false;
            }
        } else {
            if (print_msg(!has_permissions('create', 'custom_sms'), PERMISSION_ERROR_MSG, 'custom_sms')) {
                return false;
            }
        }


		if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->form_validation->set_rules('title', 'Title Name', 'trim|required');
            $this->form_validation->set_rules('message', 'Message', 'trim|required');
            $this->form_validation->set_rules('type', 'Type Name', 'trim|required|xss_clean');
			if (!$this->form_validation->run()) {

				$this->response['error'] = true;
				$this->response['csrfName'] = $this->security->get_csrf_token_name();
				$this->response['csrfHash'] = $this->security->get_csrf_hash();
				$this->response['message'] = validation_errors();
				print_r(json_encode($this->response));
			} else {
				if (isset($_POST['edit_custom_sms'])) {
					if (is_exist(['type' => $_POST['type']], 'custom_sms', $_POST['edit_custom_sms'])) {
						$response["error"]   = true;
						$response["message"] = "Name Already Exist ! Provide a unique type";
						$response['csrfName'] = $this->security->get_csrf_token_name();
						$response['csrfHash'] = $this->security->get_csrf_hash();
						$response["data"] = array();
						echo json_encode($response);
						return false;
					}
				} else {
					if (!$this->form_validation->is_unique($_POST['type'], 'custom_sms.type')) {
						$response["error"]   = true;
						$response["message"] = "Name Already Exist ! Provide a unique type";
						$response['csrfName'] = $this->security->get_csrf_token_name();
						$response['csrfHash'] = $this->security->get_csrf_hash();
						$response["data"] = array();
						echo json_encode($response);
						return false;
					}
				}

                $this->custom_sms_model->add_custom_sms($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_custom_sms'])) ? 'SMS Updated Successfully' : 'SMS Added Successfully';
                $this->response['message'] = $message;
                print_r(json_encode($this->response));
			}
		} else {
			redirect('admin/login', 'refresh');
		}
    }

    public function delete_custom_sms(){
		if($this->ion_auth->logged_in() && $this->ion_auth->is_admin())
		{			
            if ( print_msg(!has_permissions('delete', 'custom_sms'),PERMISSION_ERROR_MSG , 'custom_sms',false)) {
                return false;
            }

            // $_GET['id'] was read unchecked - a missing parameter was an undefined-index warning
            // printed into the JSON body, and a non-numeric one reached the delete.
            if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int) $_GET['id'] < 1) {
                $this->response['error'] = true;
                $this->response['message'] = 'Invalid id';
                print_r(json_encode($this->response));
                return false;
            }

            if (delete_details(['id' => (int) $_GET['id']], 'custom_sms') == TRUE) {
				$this->response['error'] = false;				
				$this->response['message'] = 'Deleted Succesfully';
				print_r(json_encode($this->response));	
			}else{
				$this->response['error'] = true;				
				$this->response['message'] = 'Something Went Wrong';
				print_r(json_encode($this->response));	
			}	
		}
		else{
			redirect('admin/login','refresh');
		}
	}

    public function view_sms()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->custom_sms_model->get_custom_sms_data();
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
