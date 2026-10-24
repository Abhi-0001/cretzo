<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin screen for seller account-deletion requests.
 *
 * Sellers cannot delete their own accounts: they send a request with a note (seller/Home), and
 * only an admin deletes - through the existing admin/sellers/delete_sellers action, which this
 * page's "Delete account" button calls before closing the request here via complete().
 * "Decline" leaves the account alone and tells the seller why.
 */
class Seller_deletions extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model('Seller_deletion_model');

        if (!has_permissions('read', 'seller')) {
            deny_panel_access();
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $settings = get_settings('system_settings', true);
            $this->data['main_page'] = TABLES . 'seller-deletion-requests';
            $this->data['title'] = 'Seller Deletion Requests | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Seller Deletion Requests | ' . $settings['app_name'];
            $this->data['table_ready'] = $this->Seller_deletion_model->table_ready();
            $this->data['pending'] = [];
            $this->data['closed'] = [];
            if ($this->data['table_ready']) {
                $this->Seller_deletion_model->reconcile();
                $this->data['pending'] = $this->Seller_deletion_model->list_requests(['pending']);
                foreach ($this->data['pending'] as &$req) {
                    $req['impact'] = $this->Seller_deletion_model->impact($req['seller_id']);
                }
                unset($req);
                $this->data['closed'] = $this->Seller_deletion_model->list_requests(['completed', 'declined'], 15);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /** POST: id, action = decline|complete, note (decline only). */
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
        // Declining changes nothing about the account; closing a request follows a delete, which
        // already demanded the delete permission. Either way the caller must hold update here.
        if (!has_permissions('update', 'seller')) {
            return $reply(true, PERMISSION_ERROR_MSG);
        }

        $id = (int) $this->input->post('id');
        $action = $this->input->post('action', true);
        if ($id < 1 || !in_array($action, ['decline', 'complete'], true)) {
            return $reply(true, 'Invalid request.');
        }

        $admin_id = (int) $this->session->userdata('user_id');
        if ($action === 'decline') {
            list($error, $message) = $this->Seller_deletion_model->decline($id, $admin_id, $this->input->post('note', true));
        } else {
            list($error, $message) = $this->Seller_deletion_model->complete($id, $admin_id);
        }
        return $reply($error, $message);
    }
}
