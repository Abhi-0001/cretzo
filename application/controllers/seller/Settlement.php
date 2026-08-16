<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Settlement extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['Seller_settlement_model']);
    }

    public function settlement_history()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $seller_id = $this->session->userdata('user_id');
            $this->data['main_page'] = TABLES . 'settlement-history';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Settlement History | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Settlement History | ' . $settings['app_name'];
            $this->data['summary'] = $this->Seller_settlement_model->get_settlement_summary($seller_id);
            $balance = fetch_details('users', ['id' => $seller_id], 'balance');
            $this->data['wallet_balance'] = isset($balance[0]['balance']) ? $balance[0]['balance'] : 0;
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function view_settlement_history_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $seller_id = $this->session->userdata('user_id');
            return $this->Seller_settlement_model->get_settlement_list($seller_id);
        } else {
            redirect('seller/login', 'refresh');
        }
    }
}
