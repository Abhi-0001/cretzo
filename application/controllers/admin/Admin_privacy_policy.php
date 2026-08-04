<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin_privacy_policy extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model('Setting_model');
        if (!has_permissions('read', 'admin_privacy_policy')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'admin-privacy-policy';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Admin Privacy Policy | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Admin Privacy Policy | ' . $settings['app_name'];
            $this->data['privacy_policy'] = get_settings('admin_privacy_policy');
            $this->data['terms_n_condition'] = get_settings('admin_terms_conditions');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function update_privacy_policy_settings()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Was checking the generic 'privacy_policy' module - the same module the
            // customer-facing Privacy Policy controller uses, meaning a grant on one would
            // silently unlock the other three (admin/delivery-boy/seller) too. Each variant
            // now has its own module.
            if (print_msg(!has_permissions('update', 'admin_privacy_policy'), PERMISSION_ERROR_MSG, 'admin_privacy_policy')) {
                return false;
            }

            $this->form_validation->set_rules('terms_n_conditions_input_description', 'Terms and Condition Description', 'trim|required|xss_clean');

            $this->form_validation->set_rules('privacy_policy_input_description', 'Privay Policy Description', 'trim|required|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $updated = $this->Setting_model->update_admin_privacy_policy($_POST);
                $updated = $this->Setting_model->update_admin_terms_n_condtions($_POST) && $updated;

                $this->response['error'] = !$updated;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $updated ? 'System Setting Updated Successfully' : 'Something went wrong.';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function privacy_policy_page()
    {
        $settings = get_settings('system_settings', true);
        $this->data['title'] = 'Privacy Policy | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Privacy Policy | ' . $settings['app_name'];
        $this->data['privacy_policy'] = get_settings('admin_privacy_policy');
        $this->load->view('admin/pages/view/privacy-policy', $this->data);
    }

    public function terms_and_conditions_page()
    {
        $settings = get_settings('system_settings', true);
        $this->data['title'] = 'Terms & Conditions | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Terms & Conditions | ' . $settings['app_name'];
        $this->data['terms_and_conditions'] = get_settings('admin_terms_conditions');
        $this->load->view('admin/pages/view/terms-and-conditions', $this->data);
    }
}
