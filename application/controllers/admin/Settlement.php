<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin-side view of seller commission settlements.
 *
 * Nothing read the seller_settlements table on the admin side at all - the only figure an
 * admin could see was the single "total earnings" number on the dashboard, computed from
 * order_items rather than from the settlement records. There was no way to answer "what
 * commission did we take from this seller", "which settlements failed", or "what have we
 * credited out in total", and no way to spot order items stuck unsettled because their
 * seller has no subscription plan.
 */
class Settlement extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['Seller_settlement_model']);

        if (!has_permissions('read', 'seller')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $settings = get_settings('system_settings', true);
            $this->data['main_page'] = TABLES . 'seller-settlements';
            $this->data['title'] = 'Commission & Settlements | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Commission & Settlements | ' . $settings['app_name'];
            $this->data['summary'] = $this->Seller_settlement_model->get_settlement_summary();
            $this->data['unsettled'] = $this->Seller_settlement_model->get_unsettled_summary();
            // Surfaces sellers whose wallet balance no longer matches their ledger, so drift
            // is visible instead of being discovered during a dispute.
            $this->data['reconciliation'] = $this->Seller_settlement_model->get_wallet_reconciliation();
            $this->data['sellers'] = $this->db
                ->select('u.id, u.username')
                ->join('users u', 'u.id = ug.user_id')
                ->where('ug.group_id', 4)
                ->order_by('u.username', 'ASC')
                ->get('users_groups ug')
                ->result_array();
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_settlement_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // NULL = every seller. The model only honours ?seller_filter when it is passed
            // NULL here, so a seller-scoped caller can never widen their own scope.
            return $this->Seller_settlement_model->get_settlement_list(NULL);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
