<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * The seller's read-only window onto returns of their own products.
 *
 * Sellers previously had no visibility at all: the only place a return request appeared was the
 * admin screen, and the seller's own order page showed nothing but a status badge on the item
 * once somebody else had acted. They could see stock come back and their commission reversed
 * without ever being told a return had been raised.
 *
 * Deliberately read-only. Approving or rejecting a return is the admin's decision, and the
 * transition to 'returned' is written by the courier callback
 * (sync_shiprocket_shipment_status()) when the reverse pickup is delivered. This page reports
 * that flow; it does not participate in it.
 */
class Return_request extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model('Return_request_model');
    }

    /** Sellers awaiting approval still need to see their orders, hence status 0 is allowed. */
    private function is_authorised_seller()
    {
        return $this->ion_auth->logged_in()
            && $this->ion_auth->is_seller()
            && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0);
    }

    public function index()
    {
        if (!$this->is_authorised_seller()) {
            redirect('seller/login', 'refresh');
            return;
        }

        $settings = get_settings('system_settings', true);
        $seller_id = $this->session->userdata('user_id');

        $this->data['main_page'] = TABLES . 'return-requests';
        $this->data['title'] = 'Return Requests | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Return Requests | ' . $settings['app_name'];
        $this->data['summary'] = $this->Return_request_model->get_seller_return_summary($seller_id);
        $this->load->view('seller/template', $this->data);
    }

    /** bootstrap-table data source. */
    public function view_return_request_list()
    {
        if (!$this->is_authorised_seller()) {
            redirect('seller/login', 'refresh');
            return;
        }

        return $this->Return_request_model->get_seller_return_request_list($this->session->userdata('user_id'));
    }
}
