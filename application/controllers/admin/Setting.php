<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setting extends CI_Controller
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
        } else {
            $this->session->set_flashdata('authorize_flag', "");
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'settings';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Settings  | ' . $settings['app_name'];
            $this->data['timezone'] = timezone_list();
            $this->data['logo'] = get_settings('logo');
            $this->data['favicon'] = get_settings('favicon');
            $this->data['settings'] = get_settings('system_settings', true);
            $this->data['currency'] = get_settings('currency');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function update_system_settings()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('update', 'settings'), PERMISSION_ERROR_MSG, 'settings')) {
                return false;
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            $this->form_validation->set_rules('app_name', 'App Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('support_number', 'Support number', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('support_email', 'Support Email', 'trim|required|xss_clean|valid_email');
            $this->form_validation->set_rules('current_version', 'Current Version Of Android APP', 'trim|required|xss_clean');
            $this->form_validation->set_rules('current_version_ios', 'Current Version Of IOS APP', 'trim|required|xss_clean');
            $this->form_validation->set_rules('delivery_charge', 'Delivery charge', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('min_amount', 'Minimum amount', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('system_timezone_gmt', 'System GMT timezone', 'trim|required|xss_clean');
            $this->form_validation->set_rules('system_timezone', 'System timezone', 'trim|required|xss_clean');
            $this->form_validation->set_rules('is_version_system_on', 'Version System', 'trim|xss_clean');
            $this->form_validation->set_rules('area_wise_delivery_charge', 'Area Wise Delivery Charges', 'trim|xss_clean');
            $this->form_validation->set_rules('currency', 'Currency', 'trim|required|xss_clean');
            $this->form_validation->set_rules('max_product_return_days', 'Maximum Product Return Day', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('delivery_boy_bonus_percentage', 'Delivery Boy Bonus', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('minimum_cart_amt', 'Minimum Cart Amount', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('low_stock_limit', 'Low stock limit', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('max_items_cart', 'Max items Allowed In Cart', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('cart_btn_on_list', 'Cart Button on Products List', 'trim|xss_clean');
            $this->form_validation->set_rules('expand_product_images', 'Expand Product Images', 'trim|xss_clean');
            $this->form_validation->set_rules('tax_name', 'Tax Name', 'trim|xss_clean');
            $this->form_validation->set_rules('tax_number', 'Tax Number', 'trim|xss_clean');
            $this->form_validation->set_rules('is_refer_earn_on', 'Refer and Earn system', 'trim|xss_clean');
            $this->form_validation->set_rules('welcome_wallet_balance_on', 'Welcome Wallet Balance', 'trim|xss_clean');
            $this->form_validation->set_rules('allow_order_attachments', 'Upload Order Attachments', 'trim|xss_clean');
            $this->form_validation->set_rules('logo', 'Logo', 'trim|required|xss_clean', array('required' => 'Logo is required'));
            $this->form_validation->set_rules('favicon', 'Favicon', 'trim|required|xss_clean', array('required' => 'Favicon is required'));
            $this->form_validation->set_rules('supported_locals', 'Supported Locals', 'trim|xss_clean');
            $this->form_validation->set_rules('decimal_point', 'Decimal Point', 'trim|xss_clean');

            if (isset($_POST['is_refer_earn_on']) && $_POST['is_refer_earn_on']) {
                $this->form_validation->set_rules('min_refer_earn_order_amount', 'Minimum Refer & Earn Order Amount', 'trim|required|numeric|xss_clean');
                $this->form_validation->set_rules('refer_earn_bonus', 'Refer & Earn Bonus', 'trim|required|numeric|xss_clean');
                $this->form_validation->set_rules('refer_earn_method', 'Refer Earn method', 'trim|required|xss_clean');
                $this->form_validation->set_rules('max_refer_earn_amount', 'Maximum Refer & Earn Bonus', 'trim|required|xss_clean');
                $this->form_validation->set_rules('refer_earn_bonus_times', 'Refer & Earn Bonus times', 'trim|required|xss_clean');
            }
            if (isset($_POST['welcome_wallet_balance_on']) && $_POST['welcome_wallet_balance_on']) {
                $this->form_validation->set_rules('wallet_balance_amount', 'Welcome Wallet Balance Amount', 'trim|required|numeric|xss_clean');
            }
            if (isset($_POST['allow_order_attachments']) && $_POST['allow_order_attachments']) {
                $this->form_validation->set_rules('upload_limit', 'Mamimum Upload Limit', 'trim|required|xss_clean|max_length[10]');
            }
            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $_POST['system_timezone_gmt'] = preg_replace('/\s+/', '', $_POST['system_timezone_gmt']);
                $_POST['system_timezone_gmt'] = ($_POST['system_timezone_gmt'] == '00:00') ? "+" . $_POST['system_timezone_gmt'] : $_POST['system_timezone_gmt'];
                $_POST['whatsapp_number'] =  $_POST['whatsapp_number'];
                // Was never checked - the model's write result was discarded, so this always
                // reported success even if the underlying database write actually failed.
                $updated = $this->Setting_model->update_system_setting($_POST);
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

    public function web()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'web-settings';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Settings  | ' . $settings['app_name'];
            $this->data['web_settings'] = get_settings('web_settings', true);
            $this->data['logo'] = get_settings('web_logo');
            $this->data['favicon'] = get_settings('web_favicon');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function update_web_settings()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('update', 'settings'), PERMISSION_ERROR_MSG, 'settings')) {
                return false;
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            $this->form_validation->set_rules('site_title', 'Site Title', 'trim|required|xss_clean');
            $this->form_validation->set_rules('support_number', 'Support number', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('support_email', 'Support Email', 'trim|required|xss_clean|valid_email');
            // The rest of this form's ~20 fields had zero filtering at all before storage -
            // only SQL-escaping, no XSS blocklist - despite several being rendered raw on the
            // public storefront (e.g. app_short_description in the site footer). xss_clean is
            // only added below for URL and colour-code fields: CI3's xss_clean has a confirmed
            // bug (reproduced live against this exact form, then reverted) that mangles
            // multi-byte UTF-8 characters (©, em dashes, accented letters) and standalone `%`
            // signs - applying it to the free-text title/description fields below would have
            // silently corrupted legitimate content like "Copyright 2026 © cretzo.com" on the
            // very next save. Those fields are left as `trim` only and rely on the html_escape()
            // now applied at every render site instead (admin form + public storefront).
            $this->form_validation->set_rules('copyright_details', 'Copyright Details', 'trim');
            $this->form_validation->set_rules('address', 'Address', 'trim');
            $this->form_validation->set_rules('app_short_description', 'App Short Description', 'trim');
            // NOT xss_clean either - this field is deliberately meant to hold a raw <iframe>
            // embed (a Google Maps embed code) and is rendered raw on the storefront for that
            // reason. CI3's xss_clean specifically neutralizes <iframe>/<object>/<embed> tags as
            // a security measure on top of the UTF-8 bug above, which would strip this field's
            // entire purpose out from under it on every save (confirmed live).
            $this->form_validation->set_rules('map_iframe', 'Map Iframe', 'trim');
            $this->form_validation->set_rules('meta_keywords', 'Meta Keywords', 'trim');
            $this->form_validation->set_rules('meta_description', 'Meta Description', 'trim');
            $this->form_validation->set_rules('app_download_section_title', 'App Download Section Title', 'trim');
            $this->form_validation->set_rules('app_download_section_tagline', 'App Download Section Tagline', 'trim');
            $this->form_validation->set_rules('app_download_section_short_description', 'App Download Section Short Description', 'trim');
            $this->form_validation->set_rules('app_download_section_playstore_url', 'Playstore URL', 'trim|xss_clean');
            $this->form_validation->set_rules('app_download_section_appstore_url', 'Appstore URL', 'trim|xss_clean');
            $this->form_validation->set_rules('twitter_link', 'Twitter Link', 'trim|xss_clean');
            $this->form_validation->set_rules('facebook_link', 'Facebook Link', 'trim|xss_clean');
            $this->form_validation->set_rules('instagram_link', 'Instagram Link', 'trim|xss_clean');
            $this->form_validation->set_rules('youtube_link', 'Youtube Link', 'trim|xss_clean');
            $this->form_validation->set_rules('shipping_title', 'Shipping Title', 'trim');
            $this->form_validation->set_rules('shipping_description', 'Shipping Description', 'trim');
            $this->form_validation->set_rules('return_title', 'Return Title', 'trim');
            $this->form_validation->set_rules('return_description', 'Return Description', 'trim');
            $this->form_validation->set_rules('support_title', 'Support Title', 'trim');
            $this->form_validation->set_rules('support_description', 'Support Description', 'trim');
            $this->form_validation->set_rules('safety_security_title', 'Safety Security Title', 'trim');
            $this->form_validation->set_rules('safety_security_description', 'Safety Security Description', 'trim');
            $this->form_validation->set_rules('primary_color', 'Primary Color', 'trim|xss_clean');
            $this->form_validation->set_rules('secondary_color', 'Secondary Color', 'trim|xss_clean');
            $this->form_validation->set_rules('font_color', 'Font Color', 'trim|xss_clean');
            $this->form_validation->set_rules('modern_theme_color', 'Modern Theme Color', 'trim|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                // Was $this->input->post(null, true) - the `true` forces CI's own xss_clean()
                // over EVERY field unconditionally, regardless of each field's own
                // form_validation rule above. That's what was still mangling map_iframe's
                // <iframe> markup even after removing xss_clean from its specific rule
                // (confirmed live), plus needlessly re-running the same UTF-8-unsafe filter a
                // second time on every other field. The per-field rules above already apply
                // xss_clean exactly where each field needs it.
                $updated = $this->Setting_model->update_web_setting($this->input->post(null, false));
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
            $this->response['message'] = "Default Theme Updated.";
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
