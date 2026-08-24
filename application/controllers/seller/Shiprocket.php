<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Endpoints custom.js already called but which did not exist.
 *
 * `getShiprocketParcelDefaults()` in assets/admin/custom/custom.js POSTs to
 * `seller/shiprocket/parcel-defaults` as soon as a pickup location is selected on the "Create
 * Shiprocket Order" modal, to pre-fill the four required parcel fields. There was no such
 * controller, so the request 404'd; because the response is an HTML error page rather than
 * JSON, jQuery's `.done()` never fired and there was no `.fail()` handler, so the seller simply
 * saw four empty required fields and got "Parcel Weight is required" on submit with nothing
 * explaining why.
 */
class Shiprocket extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language']);
    }

    public function parcel_defaults()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller()
            || !($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode([
                'error'   => true,
                'message' => 'Please sign in again.',
                'data'    => [],
            ]));
            return;
        }

        // The seller is taken from the session, never from the posted shiprocket_seller_id -
        // that field is client-controlled, and reading it would let one seller read another
        // seller's parcel weights off their orders.
        $seller_id = $this->ion_auth->get_user_id();
        $order_id  = (int) $this->input->post('order_id', true);
        $pickup    = (string) $this->input->post('pickup_location', true);

        $response = [
            'error'    => true,
            'message'  => 'Parcel defaults could not be calculated.',
            'data'     => [],
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
        ];

        if ($order_id <= 0 || trim($pickup) === '') {
            $response['message'] = 'Select a pickup location first.';
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode($response));
            return;
        }

        $defaults = shiprocket_parcel_defaults($order_id, $seller_id, $pickup);

        $response['error']   = $defaults['error'];
        $response['message'] = $defaults['message'];
        $response['data']    = $defaults['data'];

        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($response));
    }
}
