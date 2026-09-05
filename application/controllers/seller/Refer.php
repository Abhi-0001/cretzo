<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller > Refer & Grow.
 *
 * A seller has TWO things to invite: other sellers, and customers. They are
 * different programmes paying different amounts on different milestones, but
 * they travel on the same referral code - the programme a referral falls under
 * is decided by what the person who signs up turns out to be, not by which link
 * was clicked.
 *
 * So this page shows one code and two explanations, and then the honest state of
 * every seller they invited: awaiting KYC, awaiting first sale, or paid. That
 * middle state is the whole reason this page exists rather than a number on the
 * dashboard - a referring seller who cannot see WHY nothing has been paid
 * assumes the programme is broken.
 */
class Refer extends CI_Controller
{
    public $data = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'referral_engine']);
        $this->load->helper(['url', 'language', 'file']);
    }

    public function index()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }

        $seller_id = $this->session->userdata('user_id');
        $settings = get_settings('system_settings', true);

        $this->data['main_page'] = VIEW . 'refer-and-grow';
        $this->data['title'] = 'Refer & Grow | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Refer & Grow | ' . $settings['app_name'];

        $this->data['referral'] = referral_stats($seller_id);
        $this->data['qualified_count'] = $this->referral_engine->qualified_referral_count($seller_id);
        $this->data['policy'] = $this->referral_engine->settings();
        $this->data['programs'] = $this->db->select('p.code, p.name, p.status, m.code AS milestone_code, m.name AS milestone_name, m.referrer_amount, m.referee_benefit_type, m.referee_benefit_value')
            ->from('referral_programs p')
            ->join('referral_milestones m', 'm.program_id = p.id', 'left')
            ->where_in('p.code', ['seller_seller', 'seller_customer'])
            ->order_by('p.id', 'asc')
            ->order_by('m.sequence', 'asc')
            ->get()
            ->result_array();

        $this->data['invited'] = $this->invited_rows($seller_id);

        $wallet = fetch_details('users', ['id' => $seller_id], 'balance, referral_credit');
        $this->data['wallet_balance'] = !empty($wallet) ? (float) $wallet[0]['balance'] : 0;
        $this->data['referral_credit'] = !empty($wallet) ? (float) $wallet[0]['referral_credit'] : 0;

        $this->load->view('seller/template', $this->data);
    }

    /**
     * The printable referral card.
     *
     * Rendered as its own bare page rather than inside the seller panel: it is
     * meant to be printed, and printing a page wrapped in a sidebar, a navbar and
     * a footer wastes most of the sheet. Two sizes on one screen - an A5 sheet for
     * a stall table and a business-card size for a parcel insert - so a seller
     * picks one and prints it, rather than being asked to configure anything.
     *
     * No PDF library. The browser's own print dialogue writes PDFs, and adding a
     * PHP renderer to produce a document the browser can already produce would be
     * a dependency and a deploy step bought for nothing.
     */
    public function card()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            redirect('seller/login', 'refresh');
            return;
        }

        $seller_id = $this->session->userdata('user_id');
        $settings = get_settings('system_settings', true);

        $stats = referral_stats($seller_id);

        /* The shop's own name goes on the card. A card that says only "Cretzo" is
         * the platform's card; one that names the shop is the seller's, and a card
         * a seller is proud of is a card that actually gets handed out. */
        $shop = fetch_details('seller_data', ['user_id' => $seller_id], 'store_name');
        $seller = fetch_details('users', ['id' => $seller_id], 'username');

        $policy = $this->referral_engine->settings();

        $this->load->view('seller/pages/view/refer-card', [
            'code'        => $stats['code'],
            'qr_link'     => referral_qr_link($stats['code']),
            'store_name'  => !empty($shop[0]['store_name']) ? $shop[0]['store_name'] : (!empty($seller[0]['username']) ? $seller[0]['username'] : ''),
            'app_name'    => isset($settings['app_name']) ? $settings['app_name'] : 'Cretzo',
            'currency'    => get_settings('currency'),
            'discount'    => isset($policy['promo_discount']) ? (float) $policy['promo_discount'] : 100,
            'min_cart'    => isset($policy['promo_min_cart']) ? (float) $policy['promo_min_cart'] : 499,
        ]);
    }

    /**
     * Everyone this seller invited, with the milestone each one is waiting on.
     *
     * Scoped by referrer_id - the rows on the other side of this join belong to
     * other people's accounts, and only the first name and the state of the
     * seller's own reward are read out of them.
     */
    private function invited_rows($seller_id)
    {
        $rows = $this->db->select("r.id, r.created_at,
                u.username AS name,
                p.code AS program_code,
                sd.status AS seller_status,
                COALESCE(SUM(CASE WHEN rw.status = 'credited' AND rw.role = 'referrer' THEN rw.amount ELSE 0 END), 0) AS earned,
                COALESCE(SUM(CASE WHEN rw.status = 'pending' AND rw.role = 'referrer' THEN rw.amount ELSE 0 END), 0) AS pending,
                MIN(CASE WHEN rw.status = 'pending' AND rw.role = 'referrer' THEN rw.qualified_at END) AS due_at,
                SUM(CASE WHEN rw.status = 'credited' AND rw.role = 'referrer' THEN 1 ELSE 0 END) AS paid_milestones", false)
            ->from('referrals r')
            ->join('users u', 'u.id = r.referee_id', 'left')
            ->join('referral_programs p', 'p.id = r.program_id', 'left')
            ->join('seller_data sd', 'sd.user_id = r.referee_id', 'left')
            ->join('referral_rewards rw', 'rw.referral_id = r.id', 'left')
            ->where('r.referrer_id', (int) $seller_id)
            ->group_by('r.id')
            ->order_by('r.id', 'desc')
            ->limit(100)
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $first = trim((string) $row['name']);
            $row['name'] = ($first !== '') ? strtok($first, ' ') : 'A new member';
            $row['is_seller'] = ($row['program_code'] === 'seller_seller');

            if ((float) $row['pending'] > 0) {
                $row['state'] = 'pending';
                $row['state_text'] = !empty($row['due_at'])
                    ? 'Reward due ' . date('d M Y', strtotime($row['due_at']))
                    : 'Reward on the way';
            } elseif ($row['is_seller']) {
                /* seller_data.status: 1 approved, anything else not yet. The two
                 * seller milestones are shop-live then first sale, so a seller who
                 * is approved and has been paid once is waiting on their sale. */
                if ((string) $row['seller_status'] !== '1') {
                    $row['state'] = 'waiting';
                    $row['state_text'] = 'Waiting for admin approval of their shop';
                } elseif ((int) $row['paid_milestones'] < 2) {
                    $row['state'] = 'waiting';
                    $row['state_text'] = 'Shop is live - waiting for their first delivered sale';
                } else {
                    $row['state'] = 'earned';
                    $row['state_text'] = 'Both milestones paid';
                }
            } else {
                $row['state'] = ((float) $row['earned'] > 0) ? 'earned' : 'waiting';
                $row['state_text'] = ((float) $row['earned'] > 0)
                    ? 'Reward credited'
                    : 'Waiting for their first delivered order';
            }
        }

        return $rows;
    }
}
