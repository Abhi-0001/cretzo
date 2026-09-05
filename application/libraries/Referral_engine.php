<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * =============================================================================
 *  REFERRAL REWARD ENGINE (PHASE 2)
 * =============================================================================
 *
 * The whole life of a referral reward lives in this one class: a milestone
 * matching an event, a reward row created under a unique key, a hold while the
 * return window runs, the wallet credit, and the reversal when the order behind
 * it comes back.
 *
 * WHY ONE CLASS
 * -------------
 * The thing it replaces, process_referral_bonus(), was called from nine places
 * and did all of this inline - so its bugs existed nine times over. Those nine
 * call sites are kept exactly as they are and now delegate here; the entry
 * points below are the only way money is created or destroyed by the programme.
 *
 * THE FIVE STATES
 * ---------------
 *   attributed  the referral itself: two accounts bound at signup (phase 1)
 *   pending     a milestone matched. Amount frozen at today's rules, `qualified_at`
 *               holds the date it becomes payable
 *   qualified   transient - the release run's own working state
 *   credited    wallet credited via update_wallet_balance(), `transaction_id` stored
 *   reversed    the qualifying order was returned or cancelled
 *   rejected    an admin said no
 *
 * A reward is never credited at the moment it is earned. That is what makes the
 * clawback in reverse_for_order() possible at all, and returns are common enough
 * here that paying on delivery would leak money continuously.
 *
 * IDEMPOTENCY
 * -----------
 * Everything here can be called twice. The nine call sites fire per order ITEM,
 * so a three-item order calls order_delivered() three times; the unique key on
 * (referral_id, milestone_id, role) is what makes the second and third calls
 * no-ops. The engine checks first and lets the index have the final say, rather
 * than trusting either alone.
 *
 * WHAT IS HERE, AND WHAT IS NOT
 * -----------------------------
 * Phase 2 built the referrer's side: earn, hold, credit, reverse. Phase 4 added
 * the three ways a REFEREE is rewarded (a bound discount code, a wallet credit,
 * a listing bonus) and the two seller triggers - a shop going live, and a
 * referred seller's first delivered SALE, which is a different event from their
 * first purchase.
 *
 * Phase 5 added the ambassador tiers - the one part of the programme that is not
 * triggered by an event on a single referral, but by a COUNT of referrals that
 * have actually paid - and the notifications, which go to the in-app store and
 * to email rather than to push alone.
 */
class Referral_engine
{
    /** @var CI_Controller */
    private $CI;

    /** Policy defaults, used when `referral_settings` has no value for a key. */
    private $defaults = [
        'withdrawable'                  => '0',
        'monthly_budget_cap'            => '10000',
        'per_referrer_monthly_cap'      => '2000',
        'hold_days_after_return_window' => '1',
        'wallet_orders_qualify'         => '1',
        'allow_negative_on_reversal'    => '0',
        'min_order_amount'              => '499',
        'flag_review_hold_hours'        => '24',
        'credit_expiry_months'          => '12',
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
    }

    // ---------------------------------------------------------------- config

    public function settings()
    {
        static $settings = null;
        if ($settings !== null) {
            return $settings;
        }

        $stored = get_settings('referral_settings', true);
        if (!is_array($stored)) {
            $stored = [];
        }

        return $settings = array_merge($this->defaults, $stored);
    }

    private function setting($key)
    {
        $settings = $this->settings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }

    /**
     * Days between the qualifying event and the money being payable.
     *
     * NULL `hold_days` on a milestone means "follow the store's return window",
     * which is the owner's rule: credit the day after returns close. Reading the
     * window live is deliberate - storing today's number would decouple the two
     * the moment somebody edits the return policy.
     */
    private function hold_days($milestone)
    {
        if (isset($milestone['hold_days']) && $milestone['hold_days'] !== null && $milestone['hold_days'] !== '') {
            return (int) $milestone['hold_days'];
        }

        $system = get_settings('system_settings', true);
        $return_days = isset($system['max_product_return_days']) ? (int) $system['max_product_return_days'] : 0;

        return $return_days + (int) $this->setting('hold_days_after_return_window');
    }

    // ------------------------------------------------------------- entry: earn

    /**
     * An order reached "delivered". Creates the referrer's reward if this buyer
     * was referred, the programme is on, and the order qualifies.
     *
     * Returns a small array for logging; callers ignore it. Nothing in here may
     * throw or halt - it runs inside the request that marks an order delivered,
     * and a referral problem must never stop that from completing.
     */
    public function order_delivered($user_id, $order_id)
    {
        $user_id = (int) $user_id;
        $order_id = (int) $order_id;

        if ($user_id <= 0 || $order_id <= 0) {
            return $this->result(false, 'bad_arguments');
        }

        $referral = $this->active_referral_for($user_id);
        if (empty($referral)) {
            return $this->result(false, 'not_referred');
        }

        $milestone = $this->milestone_for($referral['program_id'], 'first_delivered_order');
        if (empty($milestone)) {
            return $this->result(false, 'no_active_milestone');
        }

        /* Cheap pre-check. The unique index is the real guarantee, but the nine
         * call sites fire once per order item, so this keeps a normal three-item
         * delivery from attempting three inserts and logging two key violations. */
        if ($this->reward_exists($referral['id'], $milestone['id'], 'referrer')) {
            return $this->result(false, 'already_rewarded');
        }

        $order = $this->qualifying_order($order_id, $user_id, $milestone);
        if (empty($order)) {
            return $this->result(false, 'order_not_qualifying');
        }

        $amount = (float) $milestone['referrer_amount'];
        if ($amount <= 0) {
            return $this->result(false, 'zero_amount');
        }

        /* The due date is computed once, from the delivery, and stored. A reward
         * that is already earned should not move because somebody edited the
         * return window afterwards. */
        $due = date('Y-m-d H:i:s', strtotime('+' . $this->hold_days($milestone) . ' days'));

        $reward = [
            'referral_id'     => (int) $referral['id'],
            'milestone_id'    => (int) $milestone['id'],
            'beneficiary_id'  => (int) $referral['referrer_id'],
            'role'            => 'referrer',
            'amount'          => $amount,
            'benefit_type'    => 'wallet',
            'status'          => 'pending',
            'source_order_id' => $order_id,
            'qualified_at'    => $due,
            /* A referral flagged at signup carries the flag onto its money, so the
             * release run holds it for review instead of paying it silently. */
            'flagged'         => !empty($referral['flagged']) ? 1 : 0,
            'flag_reason'     => !empty($referral['flagged']) ? $referral['flag_reason'] : null,
        ];

        $this->CI->db->insert('referral_rewards', $reward);

        /* insert_id() reports the last id on the connection whether or not this
         * insert worked - the trap behind the phantom orders in this codebase.
         * affected_rows() is what actually says a row was written. */
        if ($this->CI->db->affected_rows() < 1) {
            log_message('error', 'Referral_engine: reward insert failed for referral ' . $referral['id']);
            return $this->result(false, 'insert_failed');
        }

        /* First reward on this referral: the relationship is live, not just
         * attributed. */
        if ($referral['status'] === 'attributed') {
            $this->CI->db->where('id', $referral['id'])->update('referrals', ['status' => 'active']);
        }

        $this->create_referee_reward($referral, $milestone, $due, $order_id);

        $this->notify(
            (int) $referral['referrer_id'],
            'You earned a referral reward',
            get_settings('currency') . $amount . ' is on its way. It will be credited to your wallet on '
                . date('d M Y', strtotime($due)) . ', once the return window on the order has closed.',
            'referral_earned'
        );

        return $this->result(true, 'reward_pending', ['amount' => $amount, 'due' => $due]);
    }

    /**
     * The other half of a milestone: what the person who was REFERRED gets.
     *
     * Written as its own reward row rather than handed over immediately, so it
     * lives under the same hold, the same review and the same reversal as the
     * referrer's money. A referee benefit delivered at once would survive a
     * returned order that took the referrer's reward away with it.
     *
     * `role` is 'referee', which is what keeps the unique key from colliding
     * with the referrer's row on the same milestone.
     */
    private function create_referee_reward($referral, $milestone, $due, $order_id)
    {
        $type = $milestone['referee_benefit_type'];
        $value = (float) $milestone['referee_benefit_value'];

        if ($type === 'none' || $value <= 0) {
            return false;
        }

        if ($this->reward_exists($referral['id'], $milestone['id'], 'referee')) {
            return false;
        }

        $this->CI->db->insert('referral_rewards', [
            'referral_id'     => (int) $referral['id'],
            'milestone_id'    => (int) $milestone['id'],
            'beneficiary_id'  => (int) $referral['referee_id'],
            'role'            => 'referee',
            'amount'          => $value,
            'benefit_type'    => $type,
            'status'          => 'pending',
            'source_order_id' => $order_id,
            'qualified_at'    => $due,
            'flagged'         => !empty($referral['flagged']) ? 1 : 0,
            'flag_reason'     => !empty($referral['flagged']) ? $referral['flag_reason'] : null,
        ]);

        return $this->CI->db->affected_rows() > 0;
    }

    // ------------------------------------------------- entry: seller triggers

    /**
     * A referred seller's shop went live (KYC done, admin approved).
     *
     * This is the seller programme's first milestone, and the one event in the
     * programme that is not about an order at all. It is called from the admin
     * seller-status transition, on the change INTO approved - not on every read
     * of the approval state, which would fire on every dashboard load.
     */
    public function seller_approved($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return $this->result(false, 'bad_arguments');
        }

        $referral = $this->active_referral_for($user_id);
        if (empty($referral)) {
            return $this->result(false, 'not_referred');
        }

        $milestone = $this->milestone_for($referral['program_id'], 'kyc_shop_live');
        if (empty($milestone)) {
            return $this->result(false, 'no_active_milestone');
        }

        if ($this->reward_exists($referral['id'], $milestone['id'], 'referrer')) {
            return $this->result(false, 'already_rewarded');
        }

        $amount = (float) $milestone['referrer_amount'];

        /* No order behind this milestone, so there is no return window to wait
         * for - but a shop can be approved and then removed, so the hold still
         * applies. It just counts from today. */
        $due = date('Y-m-d H:i:s', strtotime('+' . $this->hold_days($milestone) . ' days'));

        if ($amount > 0) {
            $this->CI->db->insert('referral_rewards', [
                'referral_id'    => (int) $referral['id'],
                'milestone_id'   => (int) $milestone['id'],
                'beneficiary_id' => (int) $referral['referrer_id'],
                'role'           => 'referrer',
                'amount'         => $amount,
                'benefit_type'   => 'wallet',
                'status'         => 'pending',
                'qualified_at'   => $due,
                'flagged'        => !empty($referral['flagged']) ? 1 : 0,
                'flag_reason'    => !empty($referral['flagged']) ? $referral['flag_reason'] : null,
            ]);

            if ($this->CI->db->affected_rows() < 1) {
                return $this->result(false, 'insert_failed');
            }
        }

        if ($referral['status'] === 'attributed') {
            $this->CI->db->where('id', $referral['id'])->update('referrals', ['status' => 'active']);
        }

        $this->create_referee_reward($referral, $milestone, $due, null);

        return $this->result(true, 'reward_pending', ['amount' => $amount, 'due' => $due]);
    }

    /**
     * A referred SELLER made their first delivered sale.
     *
     * The seller programme's second milestone is about the referred seller
     * trading, not buying - proof the shop actually works. The nine call sites
     * only know the BUYER, so the sellers on the order are read from its items
     * here; one delivered order can complete this milestone for more than one
     * seller, and each is scoped to its own referral by the unique key.
     */
    private function sellers_on_order($order_id)
    {
        $rows = $this->CI->db->select('DISTINCT(seller_id) AS seller_id, SUM(sub_total) AS seller_total', false)
            ->where('order_id', (int) $order_id)
            ->where('active_status', 'delivered')
            ->group_by('seller_id')
            ->get('order_items')
            ->result_array();

        return !empty($rows) ? $rows : [];
    }

    public function seller_sale_delivered($order_id)
    {
        $order_id = (int) $order_id;
        $created = 0;

        foreach ($this->sellers_on_order($order_id) as $line) {
            $seller_id = (int) $line['seller_id'];
            if ($seller_id <= 0) {
                continue;
            }

            $referral = $this->active_referral_for($seller_id);
            if (empty($referral)) {
                continue;
            }

            /* Only the seller programme has a sale milestone. A seller who was
             * referred as a CUSTOMER earns on their own first purchase instead,
             * which order_delivered() has already handled. */
            $program = $this->CI->db->select('code')->where('id', $referral['program_id'])->get('referral_programs')->row_array();
            if (empty($program) || $program['code'] !== 'seller_seller') {
                continue;
            }

            $milestone = $this->milestone_for($referral['program_id'], 'first_delivered_order');
            if (empty($milestone) || $this->reward_exists($referral['id'], $milestone['id'], 'referrer')) {
                continue;
            }

            /* The minimum applies to what this seller actually sold on the order,
             * not to the whole basket - otherwise a Rs 60 line inside a Rs 900
             * multi-seller order would qualify. */
            $minimum = max((float) $milestone['min_order_amount'], (float) $this->setting('min_order_amount'));
            if ((float) $line['seller_total'] < $minimum) {
                continue;
            }

            $amount = (float) $milestone['referrer_amount'];
            if ($amount <= 0) {
                continue;
            }

            $due = date('Y-m-d H:i:s', strtotime('+' . $this->hold_days($milestone) . ' days'));

            $this->CI->db->insert('referral_rewards', [
                'referral_id'     => (int) $referral['id'],
                'milestone_id'    => (int) $milestone['id'],
                'beneficiary_id'  => (int) $referral['referrer_id'],
                'role'            => 'referrer',
                'amount'          => $amount,
                'benefit_type'    => 'wallet',
                'status'          => 'pending',
                'source_order_id' => $order_id,
                'qualified_at'    => $due,
                'flagged'         => !empty($referral['flagged']) ? 1 : 0,
                'flag_reason'     => !empty($referral['flagged']) ? $referral['flag_reason'] : null,
            ]);

            if ($this->CI->db->affected_rows() > 0) {
                $created++;
                $this->create_referee_reward($referral, $milestone, $due, $order_id);
            }
        }

        return $this->result($created > 0, $created > 0 ? 'reward_pending' : 'nothing_to_reward', ['count' => $created]);
    }

    // ---------------------------------------------------------- entry: release

    /**
     * Credit every reward whose hold has elapsed. Called by the cron endpoint.
     *
     * The caps are applied HERE rather than when the reward is created, on
     * purpose: a reward that would breach this month's budget is not cancelled,
     * it simply waits and is picked up by a later run. That matches what the
     * programme terms promise - the money is not lost, it is deferred - and it
     * means a busy month cannot quietly destroy somebody's earned reward.
     */
    public function release_due_rewards($limit = 200)
    {
        $now = date('Y-m-d H:i:s');
        $review_cutoff = date('Y-m-d H:i:s', strtotime('-' . (int) $this->setting('flag_review_hold_hours') . ' hours'));

        $due = $this->CI->db->select('*')
            ->where('status', 'pending')
            ->where('qualified_at <=', $now)
            /* A flagged reward waits for an admin, but only for the configured
             * window - then it releases itself if nothing else is wrong. A reward
             * that waits forever on a review nobody performs is a reward that never
             * pays, and phase 1 flags on shared IP/device, which honest referrals
             * trip constantly. */
            ->group_start()
                ->where('flagged', 0)
                ->or_where('created_at <=', $review_cutoff)
            ->group_end()
            ->order_by('id', 'asc')
            ->limit((int) $limit)
            ->get('referral_rewards')
            ->result_array();

        /* `amount` is wallet money only. Benefits - a discount code, a listing
         * bonus - are a real cost and count against the budget, but they are not
         * rupees leaving a wallet, and a summary that added them together would
         * report a payout that never happened. */
        $summary = [
            'considered' => count($due),
            'credited'   => 0,
            'deferred'   => 0,
            'failed'     => 0,
            'amount'     => 0.0,
            'benefits'   => 0,
        ];
        $month_spend = $this->credited_this_month();

        foreach ($due as $reward) {
            $amount = (float) $reward['amount'];
            $is_wallet = (empty($reward['benefit_type']) || $reward['benefit_type'] === 'wallet');

            if ($month_spend + $amount > (float) $this->setting('monthly_budget_cap')) {
                $summary['deferred']++;
                continue;
            }

            if ($this->referrer_month_total($reward['beneficiary_id']) + $amount > (float) $this->setting('per_referrer_monthly_cap')) {
                $summary['deferred']++;
                continue;
            }

            if ($this->credit($reward)) {
                $summary['credited']++;
                $month_spend += $amount;

                if ($is_wallet) {
                    $summary['amount'] += $amount;
                } else {
                    $summary['benefits']++;
                }
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * Move one reward's money. The only place in the programme that credits a
     * wallet.
     *
     * update_wallet_balance() - not customer_model->update_balance(), which is
     * marked DEPRECATED - do not call in this repo because it writes
     * users.balance outside a transaction and is the documented cause of stored
     * balances drifting from the ledger. This one wraps both writes in a
     * transaction.
     */
    private function credit($reward)
    {
        $amount = (float) $reward['amount'];
        $beneficiary = (int) $reward['beneficiary_id'];

        /* Not every reward is money. A referee's benefit can be a discount code
         * or a listing bonus, and those are delivered rather than credited - no
         * wallet movement, no budget consumption, no restricted-balance entry. */
        if (!empty($reward['benefit_type']) && $reward['benefit_type'] !== 'wallet') {
            return $this->deliver_benefit($reward);
        }

        /* An earlier reversal that could not be recovered in full (wallets are
         * never allowed to go negative) is settled here, out of the next reward,
         * before anything is paid. */
        $shortfall = $this->outstanding_shortfall($beneficiary);
        $payable = $amount - $shortfall;
        $settled = min($shortfall, $amount);

        if ($payable <= 0) {
            /* The whole reward went to clearing the debt. No wallet movement, but
             * the reward is settled and the debt reduced by exactly this much. */
            $this->clear_shortfall($beneficiary, $settled);
            $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
                'status'       => 'credited',
                'credited_at'  => date('Y-m-d H:i:s'),
                'note'         => 'Applied in full against an earlier reversal',
            ]);
            return true;
        }

        $result = update_wallet_balance(
            'credit',
            $beneficiary,
            $payable,
            'Referral reward',
            '',
            0,
            'wallet'
        );

        if (!empty($result['error'])) {
            log_message('error', 'Referral_engine: wallet credit failed for reward ' . $reward['id'] . ' - ' . $result['message']);
            return false;
        }

        if ($settled > 0) {
            $this->clear_shortfall($beneficiary, $settled);
        }

        /* Spend-only: the credited amount is also counted into the restricted
         * sub-balance, so payout paths can tell it apart from withdrawable money.
         * update_wallet_balance() has no idea this rupee is restricted - only the
         * programme does. */
        $this->CI->db->set('referral_credit', '`referral_credit` + ' . (float) $payable, false)
            ->where('id', $beneficiary)
            ->update('users');

        /* Both of these read from the database, so they are resolved BEFORE any
         * update chain is started. CodeIgniter's query builder is one shared
         * accumulator: a lookup called from inside ->where(...)->update(...) picks
         * up the outer conditions, and the first version of this method lost every
         * transaction_id that way - the lookup ran as
         * "WHERE id = <reward id> AND user_id = <beneficiary>" and matched nothing. */
        $transaction_id = $this->last_transaction_id($beneficiary);
        $program_id = $this->program_of_reward($reward);

        $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
            'status'         => 'credited',
            'credited_at'    => date('Y-m-d H:i:s'),
            'transaction_id' => $transaction_id,
            'note'           => ($settled > 0) ? 'Reduced by ' . $settled . ' settled against an earlier reversal' : null,
        ]);

        /* Programme spend, for the budget cap and the cost report. */
        if (!empty($program_id)) {
            $this->CI->db->set('spent_to_date', '`spent_to_date` + ' . (float) $payable, false)
                ->where('id', $program_id)
                ->update('referral_programs');
        }

        $this->notify(
            $beneficiary,
            'Referral reward credited',
            get_settings('currency') . $payable . ' has been added to your wallet'
                . ($settled > 0 ? ' (' . get_settings('currency') . $settled . ' was applied to an earlier reversal)' : '')
                . '. Referral credit can be spent on Cretzo but cannot be withdrawn.',
            'referral_credited'
        );

        /* Crediting is the only moment a person's qualified-referral count can
         * change, so it is the only place tiers need to be re-checked. */
        $this->award_ambassador_tiers($beneficiary);

        return true;
    }

    /**
     * Tell somebody about their own money.
     *
     * In-app first: add_user_notification() writes the row that My Account >
     * Notifications reads, which is the only channel that works regardless of
     * whether push is configured - and push has been the ONLY channel for most
     * events in this codebase, which meant no trace at all when FCM was unset.
     * Email is attempted after, best-effort: a mail failure must never abort a
     * wallet credit that has already happened.
     */
    private function notify($user_id, $title, $message, $type)
    {
        if (!function_exists('add_user_notification')) {
            return;
        }

        add_user_notification($user_id, $title, $message, $type, base_url('my-account/refer-and-earn'));

        $user = $this->CI->db->select('email, username')->where('id', (int) $user_id)->get('users')->row_array();

        if (empty($user['email']) || !function_exists('send_mail')) {
            return;
        }

        try {
            send_mail(
                $user['email'],
                $title,
                '<p>Hi ' . html_escape($user['username']) . ',</p><p>' . html_escape($message) . '</p>'
                    . '<p><a href="' . base_url('my-account/refer-and-earn') . '">See your referrals</a></p>'
            );
        } catch (Exception $e) {
            log_message('error', 'Referral_engine: reward email failed - ' . $e->getMessage());
        }
    }

    /**
     * Hand over a non-wallet benefit: a discount code bound to the referee, or
     * extra listings on their seller account.
     */
    private function deliver_benefit($reward)
    {
        switch ($reward['benefit_type']) {
            case 'promo_code':
                $code = $this->issue_promo_code($reward);
                if ($code === '') {
                    return false;
                }
                $note = 'Discount code ' . $code . ' issued';
                break;

            case 'listing_bonus':
                $this->CI->db->set('listing_bonus', '`listing_bonus` + ' . (int) $reward['amount'], false)
                    ->where('id', (int) $reward['beneficiary_id'])
                    ->update('users');
                $note = (int) $reward['amount'] . ' extra listings added';
                break;

            default:
                log_message('error', 'Referral_engine: unknown benefit type ' . $reward['benefit_type'] . ' on reward ' . $reward['id']);
                return false;
        }

        $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
            'status'      => 'credited',
            'credited_at' => date('Y-m-d H:i:s'),
            'note'        => $note,
        ]);

        return true;
    }

    /**
     * Take back a delivered benefit when its milestone is reversed.
     *
     * A coupon is DISABLED rather than deleted - if the customer already spent
     * it, the order that used it has to keep pointing at a real campaign row, or
     * every later recalculation of that order's discount breaks.
     */
    private function withdraw_benefit($reward)
    {
        if ($reward['benefit_type'] === 'listing_bonus') {
            $this->CI->db->set('listing_bonus', 'GREATEST(0, `listing_bonus` - ' . (int) $reward['amount'] . ')', false)
                ->where('id', (int) $reward['beneficiary_id'])
                ->update('users');
            return;
        }

        if ($reward['benefit_type'] === 'promo_code') {
            $binding = $this->CI->db->select('promo_code_id')
                ->where('referral_reward_id', (int) $reward['id'])
                ->get('promo_code_users')
                ->row_array();

            if (!empty($binding)) {
                $this->CI->db->where('id', $binding['promo_code_id'])->update('promo_codes', ['status' => 0]);
            }
        }
    }

    /**
     * Create the referee's single-use discount code and bind it to them.
     *
     * `list_promocode = 0` keeps it out of the public coupon list, and the row in
     * `promo_code_users` is what stops anyone else redeeming it - validate_promo_code()
     * refuses a bound code to any account it is not bound to. Without that binding a
     * code sitting in somebody's inbox belongs to whoever types it first.
     */
    private function issue_promo_code($reward)
    {
        $settings = $this->settings();
        $days = (int) (isset($settings['promo_validity_days']) ? $settings['promo_validity_days'] : 30);
        $min_cart = (float) (isset($settings['promo_min_cart']) ? $settings['promo_min_cart'] : 499);
        $discount = (float) $reward['amount'];

        /* Unguessable, and visibly a referral code when it turns up in support. */
        $code = 'REF' . strtoupper(bin2hex(random_bytes(3)));

        $this->CI->db->insert('promo_codes', [
            'promo_code'           => $code,
            'message'              => 'Referral reward - ' . get_settings('currency') . $discount . ' off your first order',
            'start_date'           => date('Y-m-d'),
            'end_date'             => date('Y-m-d', strtotime('+' . $days . ' days')),
            'no_of_users'          => 1,
            'minimum_order_amount' => $min_cart,
            'discount'             => $discount,
            'discount_type'        => 'amount',
            'max_discount_amount'  => $discount,
            'repeat_usage'         => 0,
            'no_of_repeat_usage'   => 1,
            'status'               => 1,
            'is_cashback'          => 0,
            /* Out of the public list: this code belongs to one customer. */
            'list_promocode'       => 0,
        ]);

        if ($this->CI->db->affected_rows() < 1) {
            log_message('error', 'Referral_engine: could not create promo code for reward ' . $reward['id']);
            return '';
        }

        $promo_id = (int) $this->CI->db->insert_id();

        $this->CI->db->insert('promo_code_users', [
            'promo_code_id'      => $promo_id,
            'user_id'            => (int) $reward['beneficiary_id'],
            'referral_reward_id' => (int) $reward['id'],
        ]);

        return $code;
    }

    // --------------------------------------------------------- entry: reverse

    /**
     * The order behind a reward was returned or cancelled.
     *
     * Rewards that have not been paid are simply cancelled. Paid ones are
     * recovered from the wallet - but never below zero, which was the owner's
     * explicit decision. What cannot be recovered is written to
     * `reversed_shortfall` and comes out of that user's next referral reward,
     * so the loss stays visible instead of disappearing into a debit that was
     * quietly refused.
     */
    public function reverse_for_order($order_id, $reason = 'Order returned or cancelled')
    {
        $order_id = (int) $order_id;
        if ($order_id <= 0) {
            return $this->result(false, 'bad_arguments');
        }

        $rewards = $this->CI->db->select('*')
            ->where('source_order_id', $order_id)
            ->where_in('status', ['pending', 'qualified', 'credited'])
            ->get('referral_rewards')
            ->result_array();

        if (empty($rewards)) {
            return $this->result(false, 'nothing_to_reverse');
        }

        $reversed = 0;
        $recovered_total = 0.0;

        foreach ($rewards as $reward) {
            $recovered_total += $this->reverse_one($reward, $reason);
            $reversed++;
        }

        return $this->result(true, 'reversed', ['count' => $reversed, 'recovered' => $recovered_total]);
    }

    /**
     * Reverse a single reward and return however much was actually recovered.
     *
     * Shared by the automatic path (an order came back) and the admin one (a
     * human reversing a reward from the review queue), so there is exactly one
     * implementation of the rule that a wallet is never pushed below zero.
     */
    private function reverse_one($reward, $reason)
    {
        $amount = (float) $reward['amount'];

        /* A benefit that was delivered rather than credited is withdrawn in its
         * own terms: the coupon is switched off, the listings are taken back.
         * There is no wallet movement to undo, so this returns 0 recovered. */
        if (!empty($reward['benefit_type']) && $reward['benefit_type'] !== 'wallet') {
            if ($reward['status'] === 'credited') {
                $this->withdraw_benefit($reward);
            }

            $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
                'status' => 'reversed',
                'note'   => $reason,
            ]);

            return 0.0;
        }

        if ($reward['status'] !== 'credited') {
            /* Never paid, so there is nothing to take back. */
            $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
                'status' => 'reversed',
                'note'   => $reason,
            ]);
            return 0.0;
        }

        $balance = (float) $this->balance_of($reward['beneficiary_id']);
        $recoverable = ($this->setting('allow_negative_on_reversal') == '1')
            ? $amount
            : max(0, min($amount, $balance));

        if ($recoverable > 0) {
            $result = update_wallet_balance(
                'debit',
                $reward['beneficiary_id'],
                $recoverable,
                'Referral reward reversed' . (!empty($reward['source_order_id']) ? ' - order #' . $reward['source_order_id'] : ''),
                '',
                0,
                'wallet'
            );

            if (!empty($result['error'])) {
                log_message('error', 'Referral_engine: reversal debit failed for reward ' . $reward['id'] . ' - ' . $result['message']);
                $recoverable = 0;
            }
        }

        $shortfall = round($amount - $recoverable, 2);

        /* The restricted sub-balance falls by what was actually taken back - never
         * by more, or the user ends up with withdrawable money they never earned.
         * It may already have been spent down, so it floors at 0. */
        if ($recoverable > 0) {
            /* Resolved before the chains below, for the same reason as in credit(). */
            $program_id = $this->program_of_reward($reward);

            $this->CI->db->set('referral_credit', 'GREATEST(0, `referral_credit` - ' . (float) $recoverable . ')', false)
                ->where('id', $reward['beneficiary_id'])
                ->update('users');

            if (!empty($program_id)) {
                $this->CI->db->set('spent_to_date', 'GREATEST(0, `spent_to_date` - ' . (float) $recoverable . ')', false)
                    ->where('id', $program_id)
                    ->update('referral_programs');
            }
        }

        $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
            'status'             => 'reversed',
            'reversed_shortfall' => $shortfall,
            'note'               => $reason . ($shortfall > 0 ? ' (could not recover ' . $shortfall . ')' : ''),
        ]);

        return (float) $recoverable;
    }

    // --------------------------------------------------------- ambassador tiers

    /**
     * How many of this person's referrals have actually paid out.
     *
     * The owner's rule: a referral counts toward a tier only once one of its
     * rewards has been CREDITED - not when somebody signs up. Signups are free to
     * manufacture; a credited reward means a real order was delivered, held
     * through the return window and paid.
     *
     * Tier bonuses themselves are excluded, or reaching a tier would count
     * toward the next one.
     */
    public function qualified_referral_count($user_id)
    {
        $row = $this->CI->db->select('COUNT(DISTINCT r.id) AS total', false)
            ->from('referrals r')
            ->join('referral_rewards rw', 'rw.referral_id = r.id', 'inner')
            ->join('referral_milestones m', 'm.id = rw.milestone_id', 'inner')
            ->join('referral_programs p', 'p.id = m.program_id', 'inner')
            ->where('r.referrer_id', (int) $user_id)
            ->where('rw.role', 'referrer')
            ->where('rw.status', 'credited')
            ->where('p.code !=', 'ambassador')
            ->get()
            ->row_array();

        return (int) $row['total'];
    }

    /**
     * Award any ambassador tier this referrer has now passed.
     *
     * Cumulative, by the owner's decision: somebody who reaches 25 has passed 5
     * and 10 on the way and is paid for each, Rs 4,000 in total. Tiers are read
     * from the ambassador programme's milestones (`tier_5`, `tier_10`, `tier_25`)
     * rather than hard-coded, so the thresholds and amounts stay editable in the
     * admin panel like every other amount in the programme.
     *
     * Called after a reward is credited, which is the only moment the count can
     * change. Safe to call repeatedly - each tier is guarded by a lookup for an
     * existing reward on that milestone for that person.
     */
    public function award_ambassador_tiers($user_id)
    {
        $user_id = (int) $user_id;
        $awarded = [];

        $program = $this->CI->db->select('id, status')
            ->where('code', 'ambassador')
            ->limit(1)
            ->get('referral_programs')
            ->row_array();

        if (empty($program) || empty($program['status'])) {
            return $awarded;
        }

        $count = $this->qualified_referral_count($user_id);
        if ($count < 1) {
            return $awarded;
        }

        $tiers = $this->CI->db->select('*')
            ->where('program_id', $program['id'])
            ->where('status', 1)
            ->order_by('sequence', 'asc')
            ->get('referral_milestones')
            ->result_array();

        /* The referral that carried them over the line, used only to satisfy the
         * reward row's foreign key - a tier is earned across every referral, not
         * on any one of them. */
        $latest = $this->CI->db->select('id')
            ->where('referrer_id', $user_id)
            ->order_by('id', 'desc')
            ->limit(1)
            ->get('referrals')
            ->row_array();

        if (empty($latest)) {
            return $awarded;
        }

        $highest = 0;

        foreach ($tiers as $tier) {
            /* tier_5 -> 5. The threshold lives in the milestone code so that the
             * admin panel edits amounts without being able to accidentally move a
             * threshold and re-award a tier somebody already has. */
            if (!preg_match('/(\d+)$/', $tier['code'], $m)) {
                continue;
            }
            $threshold = (int) $m[1];

            if ($count < $threshold) {
                continue;
            }

            $highest = max($highest, $threshold);

            /* Guarded by beneficiary + milestone rather than by the unique key: a
             * tier is not tied to one referral, so the key alone would allow the
             * same tier to be paid again on a different referral row. */
            $already = $this->CI->db
                ->where('milestone_id', $tier['id'])
                ->where('beneficiary_id', $user_id)
                ->count_all_results('referral_rewards');

            if ($already) {
                continue;
            }

            $amount = (float) $tier['referrer_amount'];
            if ($amount <= 0) {
                continue;
            }

            $this->CI->db->insert('referral_rewards', [
                'referral_id'    => (int) $latest['id'],
                'milestone_id'   => (int) $tier['id'],
                'beneficiary_id' => $user_id,
                'role'           => 'referrer',
                'amount'         => $amount,
                'benefit_type'   => 'wallet',
                'status'         => 'pending',
                /* No order behind a tier, so nothing to wait for a return on. It is
                 * payable on the next release run. */
                'qualified_at'   => date('Y-m-d H:i:s'),
                'note'           => $tier['name'],
            ]);

            if ($this->CI->db->affected_rows() > 0) {
                $awarded[] = ['tier' => $threshold, 'amount' => $amount, 'name' => $tier['name']];
                $this->notify(
                    $user_id,
                    'You reached ' . $tier['name'],
                    'You have ' . $count . ' successful referrals. Your ' . get_settings('currency') . $amount
                        . ' ambassador bonus is on its way to your wallet.',
                    'referral_tier'
                );
            }
        }

        /* The badge. Stored on the user so every screen that shows a name can show
         * the tier without recounting referrals. */
        if ($highest > 0) {
            $this->CI->db->where('id', $user_id)
                ->where('ambassador_tier <', $highest)
                ->update('users', ['ambassador_tier' => $highest]);
        }

        return $awarded;
    }

    // ------------------------------------------------------- entry: admin queue

    /**
     * An admin approving a held reward from the review queue.
     *
     * Approval clears the FLAG - the "several signups from one address" signal a
     * human has now looked at - and pays immediately if the hold has elapsed. It
     * does NOT bypass the caps: the monthly budget is the owner's spending limit,
     * not a fraud check, and one approval should not be able to breach it. A
     * capped reward stays pending and is paid by a later run, which is what the
     * programme terms promise.
     */
    public function admin_approve($reward_id, $admin_id = null)
    {
        $reward = $this->reward($reward_id);
        if (empty($reward)) {
            return $this->result(false, 'not_found');
        }

        if ($reward['status'] !== 'pending') {
            return $this->result(false, 'not_pending');
        }

        $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
            'flagged'     => 0,
            'flag_reason' => null,
            'reviewed_by' => $admin_id,
        ]);

        if ($reward['qualified_at'] > date('Y-m-d H:i:s')) {
            /* Approved early: the hold on the qualifying order has not run out, so
             * the money is not payable yet. It will be, without another review. */
            return $this->result(true, 'approved_awaiting_hold', ['due' => $reward['qualified_at']]);
        }

        $month_spend = $this->credited_this_month();
        $amount = (float) $reward['amount'];

        if ($month_spend + $amount > (float) $this->setting('monthly_budget_cap')) {
            return $this->result(true, 'approved_deferred_budget');
        }

        if ($this->referrer_month_total($reward['beneficiary_id']) + $amount > (float) $this->setting('per_referrer_monthly_cap')) {
            return $this->result(true, 'approved_deferred_cap');
        }

        $reward['flagged'] = 0;

        return $this->credit($reward)
            ? $this->result(true, 'credited')
            : $this->result(false, 'credit_failed');
    }

    /** An admin refusing a reward that has not been paid. */
    public function admin_reject($reward_id, $admin_id = null, $note = 'Rejected by admin')
    {
        $reward = $this->reward($reward_id);
        if (empty($reward)) {
            return $this->result(false, 'not_found');
        }

        if ($reward['status'] === 'credited') {
            /* Money has moved. Rejecting is no longer the right word for it - that
             * is a reversal, and it has to go through the wallet. */
            return $this->result(false, 'already_credited');
        }

        if (in_array($reward['status'], ['reversed', 'rejected'], true)) {
            return $this->result(false, 'already_closed');
        }

        $this->CI->db->where('id', $reward['id'])->update('referral_rewards', [
            'status'      => 'rejected',
            'reviewed_by' => $admin_id,
            'note'        => $note,
        ]);

        return $this->result(true, 'rejected');
    }

    /** An admin clawing back a reward that has already been paid. */
    public function admin_reverse($reward_id, $admin_id = null, $reason = 'Reversed by admin')
    {
        $reward = $this->reward($reward_id);
        if (empty($reward)) {
            return $this->result(false, 'not_found');
        }

        if (!in_array($reward['status'], ['pending', 'qualified', 'credited'], true)) {
            return $this->result(false, 'already_closed');
        }

        $recovered = $this->reverse_one($reward, $reason);
        $this->CI->db->where('id', $reward['id'])->update('referral_rewards', ['reviewed_by' => $admin_id]);

        return $this->result(true, 'reversed', ['recovered' => $recovered]);
    }

    private function reward($reward_id)
    {
        $reward = $this->CI->db->select('*')->where('id', (int) $reward_id)->limit(1)->get('referral_rewards')->row_array();
        return !empty($reward) ? $reward : [];
    }

    // ------------------------------------------------------------ cost report

    /**
     * What the programme has cost, by programme and month, against the budget.
     * This is the number that decides whether the programme continues, so it
     * counts credited money only - not rewards that are still pending, and not
     * reversals, which are money that came back.
     */
    public function cost_by_month($months = 6)
    {
        $since = date('Y-m-01 00:00:00', strtotime('-' . ((int) $months - 1) . ' months'));

        return $this->CI->db->select("
                DATE_FORMAT(rw.credited_at, '%Y-%m') AS month,
                p.name AS program,
                p.code AS program_code,
                COUNT(rw.id) AS rewards,
                COALESCE(SUM(rw.amount), 0) AS spent", false)
            ->from('referral_rewards rw')
            ->join('referrals r', 'r.id = rw.referral_id', 'left')
            ->join('referral_programs p', 'p.id = r.program_id', 'left')
            ->where('rw.status', 'credited')
            ->where('rw.credited_at >=', $since)
            ->group_by(["DATE_FORMAT(rw.credited_at, '%Y-%m')", 'p.id'])
            ->order_by('month', 'desc')
            ->get()
            ->result_array();
    }

    /**
     * Referrers ranked by referrals that actually paid, for the ambassador roster.
     * Money columns count only what the programme has really cost per person.
     */
    public function ambassador_roster($limit = 100)
    {
        return $this->CI->db->select("
                u.id, u.username, u.mobile, u.email, u.ambassador_tier,
                COUNT(DISTINCT CASE WHEN rw.status = 'credited' AND p.code != 'ambassador' THEN r.id END) AS qualified,
                COUNT(DISTINCT r.id) AS referrals,
                COALESCE(SUM(CASE WHEN rw.status = 'credited' THEN rw.amount ELSE 0 END), 0) AS earned,
                COALESCE(SUM(CASE WHEN rw.status = 'credited' AND p.code = 'ambassador' THEN rw.amount ELSE 0 END), 0) AS tier_bonuses", false)
            ->from('referrals r')
            ->join('users u', 'u.id = r.referrer_id', 'inner')
            ->join('referral_rewards rw', 'rw.referral_id = r.id AND rw.role = \'referrer\'', 'left')
            ->join('referral_milestones m', 'm.id = rw.milestone_id', 'left')
            ->join('referral_programs p', 'p.id = m.program_id', 'left')
            ->group_by('u.id')
            ->order_by('qualified', 'desc')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    /** Headline figures for the top of the admin screens. */
    public function totals()
    {
        $row = $this->CI->db->select("
                COALESCE(SUM(CASE WHEN status = 'credited' THEN amount ELSE 0 END), 0) AS credited,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending,
                COALESCE(SUM(CASE WHEN status = 'credited' AND credited_at >= '" . date('Y-m-01 00:00:00') . "' THEN amount ELSE 0 END), 0) AS this_month,
                COALESCE(SUM(reversed_shortfall), 0) AS shortfall,
                SUM(CASE WHEN status = 'pending' AND flagged = 1 THEN 1 ELSE 0 END) AS flagged_count", false)
            ->get('referral_rewards')
            ->row_array();

        $row['referrals'] = (int) $this->CI->db->count_all_results('referrals');
        $row['budget'] = (float) $this->setting('monthly_budget_cap');
        $row['budget_left'] = max(0, $row['budget'] - (float) $row['this_month']);

        return $row;
    }

    // ------------------------------------------------------------- read model

    /**
     * How much of a wallet can actually be paid out. Payout paths must ask this
     * instead of reading `balance`, or spend-only stops being true.
     */
    public function withdrawable_balance($user_id)
    {
        $user = $this->CI->db->select('balance, referral_credit')
            ->where('id', (int) $user_id)
            ->get('users')
            ->row_array();

        if (empty($user)) {
            return 0.0;
        }

        if ($this->setting('withdrawable') == '1') {
            return (float) $user['balance'];
        }

        return max(0, (float) $user['balance'] - (float) $user['referral_credit']);
    }

    // ---------------------------------------------------------------- helpers

    /** The live referral this user is the REFEREE of, with its programme on. */
    private function active_referral_for($user_id)
    {
        $referral = $this->CI->db->select('r.*, p.status AS program_status')
            ->from('referrals r')
            ->join('referral_programs p', 'p.id = r.program_id', 'inner')
            ->where('r.referee_id', (int) $user_id)
            ->where_in('r.status', ['attributed', 'active'])
            ->where('p.status', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($referral)) {
            return [];
        }

        /* A programme with dates set only runs between them. */
        $now = date('Y-m-d H:i:s');
        $program = $this->CI->db->select('starts_at, ends_at')->where('id', $referral['program_id'])->get('referral_programs')->row_array();

        if (!empty($program['starts_at']) && $program['starts_at'] > $now) {
            return [];
        }
        if (!empty($program['ends_at']) && $program['ends_at'] < $now) {
            return [];
        }

        return $referral;
    }

    private function milestone_for($program_id, $code)
    {
        $milestone = $this->CI->db->select('*')
            ->where('program_id', (int) $program_id)
            ->where('code', $code)
            ->where('status', 1)
            ->limit(1)
            ->get('referral_milestones')
            ->row_array();

        return !empty($milestone) ? $milestone : [];
    }

    private function reward_exists($referral_id, $milestone_id, $role)
    {
        return (bool) $this->CI->db
            ->where(['referral_id' => (int) $referral_id, 'milestone_id' => (int) $milestone_id, 'role' => $role])
            ->count_all_results('referral_rewards');
    }

    /**
     * The order, if it is one the programme pays on: belongs to this buyer, meets
     * the minimum value, and - when the owner switches `wallet_orders_qualify`
     * off - was not paid from wallet balance.
     *
     * That switch is off-by-default in the code and ON in this deployment by the
     * owner's decision. With it on, referral credit can buy an order that earns
     * more referral credit; the Rs 499 floor, the per-referrer monthly cap and
     * spend-only bound how much that loop can be worth.
     */
    private function qualifying_order($order_id, $user_id, $milestone)
    {
        $order = $this->CI->db->select('id, user_id, final_total, wallet_balance')
            ->where('id', (int) $order_id)
            ->limit(1)
            ->get('orders')
            ->row_array();

        if (empty($order) || (int) $order['user_id'] !== (int) $user_id) {
            return [];
        }

        $minimum = max((float) $milestone['min_order_amount'], (float) $this->setting('min_order_amount'));
        if ((float) $order['final_total'] < $minimum) {
            return [];
        }

        if ($this->setting('wallet_orders_qualify') != '1' && (float) $order['wallet_balance'] > 0) {
            return [];
        }

        return $order;
    }

    /** Total credited by the programme in the current calendar month. */
    private function credited_this_month()
    {
        $row = $this->CI->db->select('COALESCE(SUM(amount), 0) AS total', false)
            ->where('status', 'credited')
            ->where('credited_at >=', date('Y-m-01 00:00:00'))
            ->get('referral_rewards')
            ->row_array();

        return (float) $row['total'];
    }

    /** Total credited to one person in the current calendar month. */
    private function referrer_month_total($user_id)
    {
        $row = $this->CI->db->select('COALESCE(SUM(amount), 0) AS total', false)
            ->where('beneficiary_id', (int) $user_id)
            ->where('status', 'credited')
            ->where('credited_at >=', date('Y-m-01 00:00:00'))
            ->get('referral_rewards')
            ->row_array();

        return (float) $row['total'];
    }

    private function outstanding_shortfall($user_id)
    {
        $row = $this->CI->db->select('COALESCE(SUM(reversed_shortfall), 0) AS total', false)
            ->where('beneficiary_id', (int) $user_id)
            ->where('status', 'reversed')
            ->get('referral_rewards')
            ->row_array();

        return (float) $row['total'];
    }

    /**
     * Reduce recorded shortfalls by an amount that has now been settled, oldest
     * first, so the ledger says which reversal was made good rather than just
     * showing a smaller total.
     */
    private function clear_shortfall($user_id, $amount)
    {
        $remaining = (float) $amount;

        $rows = $this->CI->db->select('id, reversed_shortfall')
            ->where('beneficiary_id', (int) $user_id)
            ->where('status', 'reversed')
            ->where('reversed_shortfall >', 0)
            ->order_by('id', 'asc')
            ->get('referral_rewards')
            ->result_array();

        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (float) $row['reversed_shortfall']);
            $this->CI->db->where('id', $row['id'])->update('referral_rewards', [
                'reversed_shortfall' => round((float) $row['reversed_shortfall'] - $take, 2),
            ]);
            $remaining -= $take;
        }
    }

    private function balance_of($user_id)
    {
        $user = $this->CI->db->select('balance')->where('id', (int) $user_id)->get('users')->row_array();
        return !empty($user) ? (float) $user['balance'] : 0.0;
    }

    private function program_of_reward($reward)
    {
        $referral = $this->CI->db->select('program_id')->where('id', $reward['referral_id'])->get('referrals')->row_array();
        return !empty($referral['program_id']) ? (int) $referral['program_id'] : 0;
    }

    /**
     * The ledger row update_wallet_balance() just wrote. It does not return one,
     * and insert_id() is not safe to reach for across its internal transaction,
     * so the newest wallet row for this user is read back instead - correct
     * within a single request, which is the only context this runs in.
     */
    private function last_transaction_id($user_id)
    {
        $row = $this->CI->db->select('id')
            ->where('user_id', (int) $user_id)
            ->where('transaction_type', 'wallet')
            ->order_by('id', 'desc')
            ->limit(1)
            ->get('transactions')
            ->row_array();

        return !empty($row) ? (int) $row['id'] : null;
    }

    private function result($ok, $reason, $extra = [])
    {
        return array_merge(['ok' => $ok, 'reason' => $reason], $extra);
    }
}
