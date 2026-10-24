<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin > Zero Commission Offer.
 *
 * The launch promotion "the first 20 sellers pay 0% commission on their first 10 orders":
 * settings, who holds a place, how many free orders each has used, and enrol / revoke.
 * All rules live in Commission_offer_model.
 */
class Commission_offer extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model('Commission_offer_model');
    }

    private function guard($action = 'read')
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            return false;
        }
        if ($action === 'read' && !has_permissions('read', 'seller')) {
            deny_panel_access();
            return false;
        }
        return true;
    }

    private function respond($error, $message, $extra = [])
    {
        echo json_encode(array_merge([
            'error'    => $error,
            'message'  => $message,
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
        ], $extra));
    }

    private function state()
    {
        $s = $this->Commission_offer_model->settings();
        return [
            'settings'   => $s,
            'enrolled'   => $this->Commission_offer_model->enrolled_count(),
            'slots_left' => $this->Commission_offer_model->slots_left(),
            'is_open'    => $this->Commission_offer_model->is_open(),
            'rows'       => $this->Commission_offer_model->admin_list(),
            'candidates' => $this->Commission_offer_model->enrollable_sellers(),
        ];
    }

    public function index()
    {
        if (!$this->guard()) {
            return;
        }
        $settings = get_settings('system_settings', true);
        $this->data['main_page'] = TABLES . 'zero-commission-offer';
        $this->data['title'] = 'Zero Commission Offer | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Zero Commission Offer | ' . $settings['app_name'];
        $this->data['offer'] = $this->state();
        $this->data['table_ready'] = $this->Commission_offer_model->table_ready();
        $this->data['can_edit'] = has_permissions('update', 'seller');
        $this->load->view('admin/template', $this->data);
    }

    public function save_settings()
    {
        if (!$this->guard('write')) {
            return;
        }
        if (print_msg(!has_permissions('update', 'seller'), PERMISSION_ERROR_MSG, 'seller')) {
            return;
        }
        $this->form_validation->set_rules('seller_limit', 'Number of sellers', 'trim|required|is_natural|less_than_equal_to[10000]');
        $this->form_validation->set_rules('orders_per_seller', 'Free orders per seller', 'trim|required|is_natural|less_than_equal_to[100000]');
        if (!$this->form_validation->run()) {
            return $this->respond(true, strip_tags(validation_errors()));
        }
        $this->Commission_offer_model->save_settings(
            $this->input->post('enabled') == '1',
            $this->input->post('seller_limit'),
            $this->input->post('orders_per_seller')
        );
        $this->respond(false, 'Offer settings saved. Sellers already enrolled keep the number of free orders they were given.', ['state' => $this->state()]);
    }

    public function enrol()
    {
        if (!$this->guard('write')) {
            return;
        }
        if (print_msg(!has_permissions('update', 'seller'), PERMISSION_ERROR_MSG, 'seller')) {
            return;
        }
        $seller_id = (int) $this->input->post('seller_id');
        $res = $this->Commission_offer_model->enrol($seller_id, 'admin');
        $this->respond(!$res['ok'], $res['message'], ['state' => $this->state()]);
    }

    public function revoke()
    {
        if (!$this->guard('write')) {
            return;
        }
        if (print_msg(!has_permissions('update', 'seller'), PERMISSION_ERROR_MSG, 'seller')) {
            return;
        }
        $ok = $this->Commission_offer_model->revoke((int) $this->input->post('seller_id'));
        $this->respond(!$ok, $ok ? 'Seller removed from the offer. Their place is free for someone else; orders already placed keep their 0% rate.' : 'That seller is not enrolled.', ['state' => $this->state()]);
    }
}
