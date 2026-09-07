<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Error_404 extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['address_model', 'category_model', 'cart_model', 'faq_model']);
        $this->data['is_logged_in'] = ($this->ion_auth->logged_in()) ? 1 : 0;
        $this->data['user'] = ($this->ion_auth->logged_in()) ? $this->ion_auth->user()->row() : array();
        $this->data['settings'] = get_settings('system_settings', true);
        $this->data['web_settings'] = get_settings('web_settings', true);
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
    }

    public function index()
    {
        /* Send an actual 404 STATUS, not just a 404-looking page.
         *
         * routes.php maps 404_override to this controller, and a controller reached
         * normally returns HTTP 200 - so every dead URL on the site answered
         * "200 OK" with a "Page Not Found" body. CodeIgniter's own show_404() sets the
         * status header; routing through a custom controller skips that.
         *
         * Three reasons this matters beyond tidiness:
         *
         *  - Security scanning and audit. A removed endpoint and a live one look
         *    identical to any tool that reads status codes, so a real hole and a
         *    correct 404 land in the same bucket. During this audit that cost real
         *    time: several endpoints had to be checked by hand because 200 suggested
         *    they still existed.
         *  - Search engines. A 200 on a dead URL is a soft-404; Google indexes it and
         *    the not-found page competes with real pages in search results.
         *  - Callers. Any AJAX or app request to a mistyped route gets HTML with a
         *    success status and fails at the JSON parse instead of on the status check.
         *
         * The page itself is unchanged - the visitor still sees the themed 404. */
        $this->output->set_status_header(404);

        $this->data['main_page'] = '404_page';
        $this->data['title'] = 'Page Not Found | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'Home, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Page Not Found | ' . $this->data['web_settings']['meta_description'];
        $this->data['hide_header_footer'] = true;
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }
}
