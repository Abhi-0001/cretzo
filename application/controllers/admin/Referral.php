<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin control for the referral programme (phase 3).
 *
 * Four screens, in the order an owner actually needs them:
 *
 *   programs   switch a programme on or off, and set what it pays
 *   ledger     who referred whom, and what it has cost so far
 *   queue      the rewards a human still has to decide about
 *   report     spend by programme and month, against the budget
 *
 * This phase deliberately comes BEFORE the seller programmes are built. The
 * expensive lines in the reward matrix are the seller ones, and nobody should be
 * asked to switch those on without a way to see what they cost and a switch to
 * turn them off again.
 *
 * Money is never moved here. Approve, reject and reverse all call
 * Referral_engine, which owns the caps, the hold and the never-negative-wallet
 * rule - the same code the nightly release run uses. An admin action that took a
 * shortcut around the engine would be a second implementation of those rules,
 * and this codebase has enough of those already.
 */
class Referral extends CI_Controller
{
    /** View payload, the way every other admin controller here holds it. */
    public $data = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'referral_engine']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model('Referral_model');

        if (!has_permissions('read', 'referral')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    private function guard()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
            return false;
        }
        return true;
    }

    private function page($view, $title, $extra = [])
    {
        $settings = get_settings('system_settings', true);
        $this->data = array_merge($this->data, $extra);
        $this->data['main_page'] = $view;
        $this->data['title'] = $title . ' | ' . $settings['app_name'];
        $this->data['meta_description'] = $title . ' | ' . $settings['app_name'];
        $this->data['totals'] = $this->referral_engine->totals();
        $this->load->view('admin/template', $this->data);
    }

    /** Landing on the section goes to the configuration screen. */
    public function index()
    {
        $this->programs();
    }

    // ------------------------------------------------------------- programmes

    public function programs()
    {
        if (!$this->guard()) {
            return;
        }

        $this->page(FORMS . 'referral-programs', 'Referral Programs', [
            'programs' => $this->Referral_model->programs_with_milestones(),
            'policy'   => $this->referral_engine->settings(),
        ]);
    }

    /**
     * Save one programme's switch and its caps.
     *
     * Switching a programme ON is the single most consequential control in this
     * section - it is the moment the platform starts committing real money - so
     * it is an explicit POST on one programme at a time rather than a bulk save.
     */
    public function save_program()
    {
        if (!$this->guard()) {
            return;
        }

        if (!has_permissions('update', 'referral')) {
            $this->respond(true, PERMISSION_ERROR_MSG);
            return;
        }

        $id = (int) $this->input->post('id', true);
        if ($id <= 0) {
            $this->respond(true, 'Unknown program.');
            return;
        }

        /* The form posts plain dates, and the window check in Referral_engine
         * compares them against a full DATETIME. A bare '2026-09-30' becomes
         * midnight AT THE START of the 30th, which would have ended a programme
         * a day early - the last day the admin picked would never run. So the
         * two ends of the window are pinned to the ends of their own days.
         *
         * `per_referrer_monthly_cap` is deliberately NOT written here. There is
         * no field for it on this form, so every save posted nothing and wrote
         * NULL over whatever was in the column; the engine reads the per-referrer
         * cap from referral_settings, never from the programme row. */
        $update = [
            'status'     => $this->input->post('status', true) ? 1 : 0,
            'budget_cap' => $this->nullable_number($this->input->post('budget_cap', true)),
            'starts_at'  => $this->day_boundary($this->input->post('starts_at', true), '00:00:00'),
            'ends_at'    => $this->day_boundary($this->input->post('ends_at', true), '23:59:59'),
        ];

        $this->db->where('id', $id)->update('referral_programs', $update);

        $program = $this->db->select('name, status')->where('id', $id)->get('referral_programs')->row_array();

        $this->respond(false, $program['name'] . ' is now ' . (!empty($program['status']) ? 'LIVE' : 'off') . '.');
    }

    /**
     * Save one milestone's amounts.
     *
     * `hold_days` blank means "follow the store's return window" - the owner's
     * rule, and the reason the column is nullable. The form says so; this keeps
     * an empty field meaning that rather than zero, which would pay the same day
     * the order is delivered.
     */
    public function save_milestone()
    {
        if (!$this->guard()) {
            return;
        }

        if (!has_permissions('update', 'referral')) {
            $this->respond(true, PERMISSION_ERROR_MSG);
            return;
        }

        $id = (int) $this->input->post('id', true);
        if ($id <= 0) {
            $this->respond(true, 'Unknown milestone.');
            return;
        }

        $required = $this->require_numbers([
            'referrer_amount'       => 'Referrer gets',
            'referee_benefit_value' => 'Value',
            'min_order_amount'      => 'Min order',
        ]);

        if ($required !== '') {
            $this->respond(true, $required);
            return;
        }

        /* Blank is a real value here, and the only one in this form: it means
         * "follow the store's return window", which is why the field carries no
         * required marker. (float)'' would have silently become 0 - a reward
         * payable the same day the order is delivered. */
        $hold = trim((string) $this->input->post('hold_days', true));

        $this->db->where('id', $id)->update('referral_milestones', [
            'referrer_amount'       => (float) $this->input->post('referrer_amount', true),
            'referee_benefit_type'  => $this->input->post('referee_benefit_type', true),
            'referee_benefit_value' => (float) $this->input->post('referee_benefit_value', true),
            'min_order_amount'      => (float) $this->input->post('min_order_amount', true),
            'hold_days'             => ($hold === '') ? null : (int) $hold,
            'status'                => $this->input->post('status', true) ? 1 : 0,
        ]);

        $this->respond(false, 'Milestone saved.');
    }

    /**
     * Save the programme-wide policy: caps, windows, expiry.
     *
     * These live in the `referral_settings` settings row rather than on a
     * programme, because one budget covers all programmes and one per-referrer
     * cap follows the person.
     */
    public function save_policy()
    {
        if (!$this->guard()) {
            return;
        }

        if (!has_permissions('update', 'referral')) {
            $this->respond(true, PERMISSION_ERROR_MSG);
            return;
        }

        $editable = [
            'monthly_budget_cap', 'per_referrer_monthly_cap', 'hold_days_after_return_window',
            'min_order_amount', 'flag_review_hold_hours', 'credit_expiry_months',
            'expiry_notice_days', 'promo_discount', 'promo_min_cart', 'promo_validity_days',
            'wallet_orders_qualify', 'allow_negative_on_reversal', 'withdrawable',
        ];

        $required = $this->require_numbers([
            'monthly_budget_cap'            => 'Monthly budget',
            'per_referrer_monthly_cap'      => 'Per referrer, per month',
            'min_order_amount'              => 'Minimum qualifying order',
            'hold_days_after_return_window' => 'Days after return window',
            'flag_review_hold_hours'        => 'Flagged reward review window',
            'credit_expiry_months'          => 'Credit expires after',
            'expiry_notice_days'            => 'Expiry notice',
            'promo_discount'                => 'First-order discount',
            'promo_min_cart'                => 'Discount minimum cart',
            'promo_validity_days'           => 'Discount valid for',
        ]);

        if ($required !== '') {
            $this->respond(true, $required);
            return;
        }

        $policy = $this->referral_engine->settings();
        foreach ($editable as $key) {
            $posted = $this->input->post($key, true);
            if ($posted !== null) {
                $policy[$key] = (string) $posted;
            }
        }

        /* The three switches are checkboxes: absent means off, and the loop above
         * cannot tell "unchecked" from "not on this form". */
        foreach (['wallet_orders_qualify', 'allow_negative_on_reversal', 'withdrawable'] as $flag) {
            $policy[$flag] = $this->input->post($flag, true) ? '1' : '0';
        }

        $json = json_encode($policy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($this->db->where('variable', 'referral_settings')->count_all_results('settings')) {
            $this->db->where('variable', 'referral_settings')->update('settings', ['value' => $json]);
        } else {
            $this->db->insert('settings', ['variable' => 'referral_settings', 'value' => $json]);
        }

        /* Settings are cached across requests. Without this the panel would show
           the new number while the engine kept using the old one for up to five
           minutes - the sort of discrepancy that gets diagnosed as a bug in the
           engine. */
        if (function_exists('clear_settings_cache')) {
            clear_settings_cache();
        }

        $this->respond(false, 'Program settings saved.');
    }

    // ----------------------------------------------------------------- ledger

    public function ledger()
    {
        if (!$this->guard()) {
            return;
        }

        $this->page(TABLES . 'referral-ledger', 'Referral Ledger');
    }

    public function ledger_list()
    {
        if (!$this->guard()) {
            return;
        }

        return $this->Referral_model->ledger_list();
    }

    // ------------------------------------------------------------------ queue

    public function queue()
    {
        if (!$this->guard()) {
            return;
        }

        $this->page(TABLES . 'referral-rewards', 'Referral Rewards');
    }

    public function rewards_list()
    {
        if (!$this->guard()) {
            return;
        }

        return $this->Referral_model->rewards_list();
    }

    /**
     * Approve, reject or reverse one reward. Every branch is the engine's.
     */
    public function review()
    {
        if (!$this->guard()) {
            return;
        }

        if (!has_permissions('update', 'referral')) {
            $this->respond(true, PERMISSION_ERROR_MSG);
            return;
        }

        $id = (int) $this->input->post('id', true);
        $action = $this->input->post('action', true);
        $note = trim((string) $this->input->post('note', true));
        $admin_id = $this->session->userdata('user_id');

        switch ($action) {
            case 'approve':
                $result = $this->referral_engine->admin_approve($id, $admin_id);
                break;
            case 'reject':
                $result = $this->referral_engine->admin_reject($id, $admin_id, ($note !== '') ? $note : 'Rejected by admin');
                break;
            case 'reverse':
                $result = $this->referral_engine->admin_reverse($id, $admin_id, ($note !== '') ? $note : 'Reversed by admin');
                break;
            default:
                $this->respond(true, 'Unknown action.');
                return;
        }

        /* The engine answers in reason codes; an admin needs a sentence. Anything
         * unmapped falls through as the raw code rather than a generic "done", so
         * a new engine outcome is visible instead of silently reading as success. */
        $messages = [
            'credited'                 => 'Approved and credited.',
            'approved_awaiting_hold'   => 'Approved. It will be credited when the hold on the order ends'
                . (!empty($result['due']) ? ' (' . date('d-m-Y', strtotime($result['due'])) . ').' : '.'),
            'approved_deferred_budget' => 'Approved, but this month\'s program budget is used up - it will be paid next month.',
            'approved_deferred_cap'    => 'Approved, but this referrer has reached the monthly cap - it will be paid next month.',
            'rejected'                 => 'Reward rejected.',
            'reversed'                 => 'Reward reversed'
                . (isset($result['recovered']) ? ', ' . get_settings('currency') . $result['recovered'] . ' recovered.' : '.'),
            'not_found'                => 'That reward no longer exists.',
            'not_pending'              => 'That reward is not waiting for a decision.',
            'already_credited'         => 'Already paid - use Reverse to take it back.',
            'already_closed'           => 'That reward is already closed.',
            'credit_failed'            => 'The wallet credit failed. Check the log.',
        ];

        $message = isset($messages[$result['reason']]) ? $messages[$result['reason']] : $result['reason'];

        $this->respond(empty($result['ok']), $message);
    }

    // -------------------------------------------------------------- ambassadors

    /**
     * Who is actually bringing people in, ranked by referrals that PAID.
     *
     * Ranking by signups would put whoever shared the most links at the top;
     * ranking by credited referrals puts the people whose invitations turned into
     * delivered orders there, which is the only version of the list worth acting
     * on.
     */
    public function ambassadors()
    {
        if (!$this->guard()) {
            return;
        }

        $this->page(TABLES . 'referral-ambassadors', 'Ambassador Roster', [
            'roster' => $this->referral_engine->ambassador_roster(100),
            'tiers'  => $this->db->select('m.code, m.name, m.referrer_amount')
                ->from('referral_milestones m')
                ->join('referral_programs p', 'p.id = m.program_id', 'inner')
                ->where('p.code', 'ambassador')
                ->order_by('m.sequence', 'asc')
                ->get()
                ->result_array(),
        ]);
    }

    // ----------------------------------------------------------------- report

    public function report()
    {
        if (!$this->guard()) {
            return;
        }

        $this->page(VIEW . 'referral-cost-report', 'Referral Cost Report', [
            'cost_rows' => $this->referral_engine->cost_by_month(6),
            'programs'  => $this->Referral_model->programs_with_milestones(),
            'policy'    => $this->referral_engine->settings(),
        ]);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Reject blanks and non-numbers on the fields marked required in the form.
     *
     * The red asterisk on those labels is a promise, and this is what keeps it:
     * without a server-side check the browser's `required` attribute is the only
     * thing standing between a mistyped form and a saved value, and it is absent
     * the moment anyone posts to this endpoint directly.
     *
     * It matters most for the caps. Every numeric here is later read with a
     * (float) cast, so a blank becomes 0 - and 0 in `monthly_budget_cap` does not
     * mean "no limit", it means every reward is deferred forever. A silently
     * emptied field would stop the whole programme paying with nothing on screen
     * to say why.
     *
     * Returns '' when everything is present, or a sentence naming the fields
     * that are not.
     */
    private function require_numbers($fields)
    {
        $missing = [];

        foreach ($fields as $name => $label) {
            $value = $this->input->post($name, true);

            if ($value === null || trim((string) $value) === '' || !is_numeric($value)) {
                $missing[] = $label;
            }
        }

        if (empty($missing)) {
            return '';
        }

        return (count($missing) === 1)
            ? $missing[0] . ' needs a number.'
            : 'These need a number: ' . implode(', ', $missing) . '.';
    }

    private function nullable_number($value)
    {
        $value = trim((string) $value);
        return ($value === '') ? null : (float) $value;
    }

    /**
     * A `type=date` value as a DATETIME at one end of that day.
     *
     * Blank stays NULL - "no boundary" is a real setting here and means the
     * programme runs until it is switched off. Anything that is not a date is
     * also read as blank rather than handed to MySQL, which would store
     * 0000-00-00 and silently park the programme outside its own window.
     */
    private function day_boundary($value, $time)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $parts = explode('-', substr($value, 0, 10));

        if (count($parts) !== 3 || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            /* checkdate, not DateTime: DateTime rolls a nonsense date forward
             * instead of rejecting it, so a posted '2026-13-45' would have
             * quietly become February 2027 and moved the window. */
            return null;
        }

        return sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]) . ' ' . $time;
    }

    private function respond($error, $message)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'error'    => (bool) $error,
            'message'  => $message,
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
        ]));
    }
}
