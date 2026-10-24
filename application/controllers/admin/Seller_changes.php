<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin review of major seller-profile edits made after the seller was approved.
 *
 * The seller's profile form parks changes to personal/business/bank details and documents in
 * seller_profile_change_requests instead of applying them (see Seller_profile_change_model).
 * This screen lists them with the approved value beside the proposed one; approving writes the
 * proposed values to the profile, rejecting discards them (and their uploaded files) and tells
 * the seller why.
 */
class Seller_changes extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model('Seller_profile_change_model');

        if (!has_permissions('read', 'seller')) {
            deny_panel_access();
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $settings = get_settings('system_settings', true);
            $this->data['main_page'] = TABLES . 'seller-profile-changes';
            $this->data['title'] = 'Seller Profile Changes | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Seller Profile Changes | ' . $settings['app_name'];
            $this->data['table_ready'] = $this->Seller_profile_change_model->table_ready();
            $this->data['pending'] = $this->data['table_ready'] ? $this->Seller_profile_change_model->list_requests('pending') : [];
            $this->data['approved'] = [];
            $this->data['rejected'] = [];
            if ($this->data['table_ready']) {
                $this->data['approved'] = array_slice($this->Seller_profile_change_model->list_requests('approved'), 0, 10);
                $this->data['rejected'] = array_slice($this->Seller_profile_change_model->list_requests('rejected'), 0, 10);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /** POST: id, action = approve|reject, reason (reject only). */
    public function review()
    {
        $reply = function ($error, $message) {
            echo json_encode([
                'error'    => $error,
                'message'  => $message,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
            ]);
        };

        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_admin())) {
            return $reply(true, 'Please log in again.');
        }
        if (!has_permissions('update', 'seller')) {
            return $reply(true, PERMISSION_ERROR_MSG);
        }

        $id = (int) $this->input->post('id');
        $action = $this->input->post('action', true);
        if ($id < 1 || !in_array($action, ['approve', 'reject'], true)) {
            return $reply(true, 'Invalid request.');
        }

        $admin_id = (int) $this->session->userdata('user_id');
        if ($action === 'approve') {
            list($error, $message) = $this->Seller_profile_change_model->approve($id, $admin_id);
        } else {
            list($error, $message) = $this->Seller_profile_change_model->reject($id, $admin_id, $this->input->post('reason', true));
        }
        return $reply($error, $message);
    }
}
