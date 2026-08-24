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
        $this->load->model(['Seller_settlement_model', 'Tax_compliance_model']);

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

            // TCS / TDS actually withheld this financial year, per seller and NET OF RETURNS.
            // This is the figure that gets deposited and filed (GSTR-8 for the TCS split,
            // 26Q / Form 26QF for the TDS), and nothing produced it before: the settlement
            // rows carried the deductions but reversals carried nothing back out, so a
            // returned order's TCS and TDS stayed payable forever. get_deduction_summary()
            // subtracts every reversed row, so a return unwinds its own deductions.
            $fy = $this->input->get('fy', true);
            $fy = preg_match('/^\d{4}-\d{2}$/', (string) $fy) ? $fy : $this->Tax_compliance_model->financial_year();
            $this->data['financial_year'] = $fy;
            $this->data['financial_years'] = $this->available_financial_years();
            $this->data['tax_compliance'] = $this->Tax_compliance_model->get_deduction_summary($fy);
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

    /**
     * Financial years the settlement table actually has records in, newest first, so the
     * picker cannot offer a year with nothing behind it. The current year is always present
     * even on a fresh install with no settlements yet.
     */
    private function available_financial_years()
    {
        $rows = $this->db->select('MIN(created_at) AS first_at, MAX(created_at) AS last_at', false)
            ->get('seller_settlements')->row_array();

        $current = $this->Tax_compliance_model->financial_year();
        if (empty($rows['first_at'])) {
            return [$current];
        }

        $years = [];
        $from = (int) substr($this->Tax_compliance_model->financial_year($rows['first_at']), 0, 4);
        $to   = (int) substr($this->Tax_compliance_model->financial_year($rows['last_at']), 0, 4);
        $to   = max($to, (int) substr($current, 0, 4));
        for ($year = $to; $year >= $from; $year--) {
            $years[] = $year . '-' . substr((string) ($year + 1), -2);
        }

        return $years;
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
