<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sms_gateway_settings extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper', 'sms_helper']);
        $this->load->model(['Setting_model', 'notification_model', 'category_model', 'custom_sms_model']);
    }

    // test() and test_sms() were REMOVED from this controller. Both were
    // unauthenticated (this controller has no constructor gate, and every real
    // method gates itself with logged_in()+is_admin()+has_permissions), both were
    // unreferenced debug leftovers from the upstream vendor, and both contained
    // that vendor's developers' hardcoded personal contact details:
    //   - test() dumped $_SESSION to the browser and fired an FCM push to a
    //     hardcoded device token.
    //   - test_sms() called set_user_otp(), sending a real SMS through the
    //     configured gateway and writing OTP state, with hardcoded email
    //     addresses and phone numbers. Anonymous, repeatable, and billable.
    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'sms-gateway-settings')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            $this->data['main_page'] = FORMS . 'sms-gateway-settings';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'SMS Gateway Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = ' SMS Gateway Settings  | ' . $settings['app_name'];
            $this->data['sms_gateway_settings'] = get_settings('sms_gateway_settings', true);
            $this->data['send_notification_settings'] = get_settings('send_notification_settings', true);
            $this->data['notification_modules'] = $this->config->item('notification_modules');
            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('custom_sms', ['id' => $_GET['edit_id']]);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_sms_data()
    {
        // echo "<pre>";
        // print_R($_POST);
        // die;
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'sms-gateway-settings')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            if (print_msg(!has_permissions('update', 'sms-gateway-settings'), PERMISSION_ERROR_MSG, 'sms-gateway-settings')) {
                return false;
            }

            // $this->form_validation->set_rules('base_url', 'Base URL', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('sms_gateway_method', 'Method ', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('var_header_key', 'Header', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('var_header_value', 'Header value', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('mobile_no_val', 'Mobile number', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('country_code_val', 'country code', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('mobile_with_country_key_val', 'Mobile number with country code', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('message_val', 'Message value', 'trim|required|xss_clean');

            // if (!$this->form_validation->run()) {

            //     $this->response['error'] = true;
            //     $this->response['csrfName'] = $this->security->get_csrf_token_name();
            //     $this->response['csrfHash'] = $this->security->get_csrf_hash();
            //     $this->response['message'] = validation_errors();
            //     print_r(json_encode($this->response));
            // } else {
            $updated = $this->Setting_model->update_smsgateway($_POST);
            $this->response['error'] = !$updated;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = $updated ? 'System Setting Updated Successfully' : 'Something went wrong.';
            print_r(json_encode($this->response));
            // }


        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function update_notification_module()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'sms-gateway-settings')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            // Was only checking 'read' - a role granted read-only access to SMS Gateway
            // Settings could still change which events send SMS/email/push notifications.
            if (print_msg(!has_permissions('update', 'sms-gateway-settings'), PERMISSION_ERROR_MSG, 'sms-gateway-settings')) {
                return false;
            }

            $updated = $this->Setting_model->update_notification_setting($_POST);
            $this->response['error'] = !$updated;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = $updated ? 'Data Updated Successfully' : 'Something went wrong.';

            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

}
