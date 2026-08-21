<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Themes extends CI_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model('Setting_model');

        if (!has_permissions('read', 'settings')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'themes';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Themes | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Themes  | ' . $settings['app_name'];
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_themes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->Setting_model->get_theme_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function set_default_theme()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Was missing entirely - a restricted admin with zero update rights on Settings
            // could still change the storefront's default theme via this endpoint.
            if (print_msg(!has_permissions('update', 'settings'), PERMISSION_ERROR_MSG, 'settings')) {
                return false;
            }
            $this->form_validation->set_rules('theme_id', 'Theme', 'trim|required|xss_clean|numeric');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return false;
            }
            $theme_id = $this->input->post('theme_id', true);
            $theme = $this->db->where('id', $theme_id)->get('themes')->row_array();
            if (empty($theme)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = "No theme found.";
                $this->response['test'] = $theme;
                print_r(json_encode($this->response));
                return false;
            }

            if ($theme['status'] == 0) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = "You can not set Inactive theme as default.";
                print_r(json_encode($this->response));
                return false;
            }
            $this->db->trans_start();

            $this->db->set('is_default', 0);
            $this->db->update('themes');

            $this->db->set('is_default', 1);
            $this->db->where('id', $theme_id)->update('themes');

            $this->db->trans_complete();
            $error = true;
            if ($this->db->trans_status() === true) {
                $error = false;
            }
            $this->response['error'] = $error;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            // Reported "Default Theme Updated." unconditionally, including when the transaction had

            // rolled back - the admin saw a success toast while the storefront kept the old theme.

            $this->response['message'] = $error ? "Could not update the default theme." : "Default Theme Updated.";
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function switch()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Was missing entirely - a restricted admin with zero update rights on Settings
            // could still activate/deactivate storefront themes via this endpoint.
            if (print_msg(!has_permissions('update', 'settings'), PERMISSION_ERROR_MSG, 'settings')) {
                return false;
            }
            $this->form_validation->set_rules('id', 'Theme', 'trim|required|xss_clean|numeric');
            $this->form_validation->set_rules('status', 'Status', 'trim|required|xss_clean|numeric|in_list[0,1]');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return false;
            }
            $theme_id = $this->input->post('id', true);
            $status = $this->input->post('status', true);
            $theme = $this->db->where('id', $theme_id)->get('themes')->row_array();

            if (empty($theme)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = "No theme found.";
                $this->response['test'] = $theme;
                print_r(json_encode($this->response));
                return false;
            }

            // Exactly one theme is meant to be active at a time - the $status == 1 branch below
            // enforces that by deactivating every other row first. Deactivating the active theme
            // therefore leaves NONE active, and MyConfig's THEME resolution silently falls back
            // to the 'default_theme' from config/eshop.php - i.e. the storefront changes theme
            // as a side effect of a button that only claims to deactivate one. Refuse it and
            // tell the admin to activate the theme they want instead.
            if ($status == 0 && (int) $theme['status'] === 1) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = "This is the active theme. Activate another theme instead of deactivating this one.";
                print_r(json_encode($this->response));
                return false;
            }

            $this->db->trans_start();
            if ($status == 1) {
                $this->db->set('status', 0);
                $this->db->set('is_default', 0);
                $this->db->update('themes');
            }
            $this->db->set('status', $status);
            $this->db->where('id', $theme_id)->update('themes');
            $this->db->trans_complete();
            $error = ($this->db->trans_status() === false);

            $this->response['error'] = $error;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            // Was hardcoded to the success text even when the transaction had rolled back.
            $this->response['message'] = ($error) ? "Theme could not be changed." : "Theme changed successfully.";
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}