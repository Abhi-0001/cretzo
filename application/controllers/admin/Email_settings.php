<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Email_settings extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model('Setting_model');

        if (!has_permissions('read', 'email_settings')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'email-settings';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Email Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Email Settings | ' . $settings['app_name'];
            $this->data['email_settings'] = get_settings('email_settings', true);
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    /**
     * Sends a real email through the saved SMTP settings and reports back what the mail
     * server actually said.
     *
     * There was previously no way to tell whether these settings worked - they were only
     * exercised indirectly by things like the password-reset OTP, which reports a generic
     * "we could not deliver your OTP" to the end user and buries the real cause in the
     * log. A failure here returns the redacted server response (e.g. Gmail's
     * "534-5.7.9 Application-specific password required"), so the fix is obvious.
     */
    public function send_test_email()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('update', 'email_settings'), PERMISSION_ERROR_MSG, 'email_settings')) {
            return false;
        }

        $this->form_validation->set_rules('test_email', 'Recipient', 'trim|required|valid_email|xss_clean');
        if (!$this->form_validation->run()) {
            echo json_encode([
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => strip_tags(validation_errors()),
            ]);
            return false;
        }

        $to = $this->input->post('test_email', true);
        $settings = get_settings('system_settings', true);
        $app_name = !empty($settings['app_name']) ? $settings['app_name'] : 'Cretzo';

        $result = send_mail(
            $to,
            $app_name . ' test email',
            'This is a test email from ' . $app_name . '. If you received it, your SMTP settings are working.'
        );

        if (!empty($result['error'])) {
            // $result['config'] holds the SMTP password - never echo it. 'reason' is the
            // redacted server response produced by send_mail().
            echo json_encode([
                'error' => true,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'message' => 'Test email failed: ' . (!empty($result['reason']) ? $result['reason'] : $result['message']),
            ]);
            return false;
        }

        echo json_encode([
            'error' => false,
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
            'message' => 'Test email sent to ' . $to . '. Check the inbox (and spam folder).',
        ]);
        return false;
    }

    public function set_email_settings()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('update', 'email_settings'), PERMISSION_ERROR_MSG, 'email_settings')) {
                return false;
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            $this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
            $this->form_validation->set_rules('smtp_host', 'Smpt Host', 'trim|required|xss_clean');
            $this->form_validation->set_rules('smtp_port', 'Smpt Port', 'trim|required|xss_clean');
            $this->form_validation->set_rules('mail_content_type', 'Mail Content Type', 'trim|required|xss_clean');
            $this->form_validation->set_rules('smtp_encryption', 'Smpt Encryption', 'trim|required|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $updated = $this->Setting_model->update_email_settings($_POST);
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
}
