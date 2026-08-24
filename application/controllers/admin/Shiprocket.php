<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin counterpart of seller/Shiprocket - see the note there. custom.js POSTs to
 * `admin/shiprocket/parcel-defaults` from the "Create Shiprocket Order" modal and this did not
 * exist, so the parcel fields were never pre-filled.
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
        $response = [
            'error'    => true,
            'message'  => 'Parcel defaults could not be calculated.',
            'data'     => [],
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
        ];

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $response['message'] = 'Please sign in again.';
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode($response));
            return;
        }

        $order_id  = (int) $this->input->post('order_id', true);
        // An admin ships on any seller's behalf, so the seller does come from the request here -
        // but only ever as a filter on rows that belong to the order being shipped.
        $seller_id = (int) $this->input->post('shiprocket_seller_id', true);
        $pickup    = (string) $this->input->post('pickup_location', true);

        if ($order_id <= 0 || $seller_id <= 0 || trim($pickup) === '') {
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
