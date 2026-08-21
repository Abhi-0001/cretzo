<?php
// NOTE: error reporting and display_errors are set per ENVIRONMENT in index.php -
// production turns display_errors OFF. This file used to force it back ON at the top,
// unconditionally, which meant any PHP notice or warning raised anywhere in this
// controller was rendered INTO the live page or the JSON body on production: file paths
// leaked to shoppers, and an AJAX response that picked up a warning stopped parsing.
// Leave the environment to decide.
defined('BASEPATH') or exit('No direct script access allowed');


class Maintenance extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->library(['Jwt', 'Key']);
        $this->load->model(['address_model', 'category_model', 'product_model', 'brand_model', 'cart_model', 'faq_model', 'blog_model', 'ion_auth_model']);
        $this->load->library(['pagination']);
        $this->data['is_logged_in'] = ($this->ion_auth->logged_in()) ? 1 : 0;
        $this->data['user'] = ($this->ion_auth->logged_in()) ? $this->ion_auth->user()->row() : array();
        $this->data['settings'] = get_settings('system_settings', true);
        $this->data['web_settings'] = get_settings('web_settings', true);
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
    }

    public function index()
    {

        $this->data['main_page'] = 'under_maintenance';
        $this->data['title'] = 'Maintenance | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'Maintenance, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Maintenance | ' . $this->data['web_settings']['meta_description'];
        $this->data['web_maintenance_mode'] = get_settings('system_settings', true);
        $settings = get_settings('system_settings', true);

        // $web_doctor_brown = get_settings('web_doctor_brown', true);
        if ((isset($settings['is_web_under_maintenance']) || $settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            // redirect(base_url("under_maintenance"));
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        }
    }
}
