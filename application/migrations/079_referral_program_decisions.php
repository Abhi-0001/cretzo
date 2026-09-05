<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Applies the owner's decisions of 2026-09-05 to the referral rule set seeded by
 * migration 078.
 *
 * 078 created the tables with placeholder amounts and every program disabled,
 * because the amounts are real cash liabilities and were not ours to choose.
 * This migration writes the answers in. Programs stay DISABLED - the reward
 * engine (phase 2) does not exist yet, and a rule with an amount on it is not
 * the same thing as a live program.
 *
 * WHAT WAS DECIDED
 * ----------------
 *   Wallet money       spend-only, never withdrawable as cash
 *   Hold before paying return window + 1 day, followed dynamically (see below)
 *   Wallet-paid orders DO qualify for a referral reward
 *   Minimum order      Rs 499
 *   Reversals          never push a wallet below zero
 *   Budget             Rs 10,000 a month across the whole program
 *   Per referrer       Rs 2,000 a month
 *   Flagged rewards    short admin-editable hold, then auto-release if clean
 *   Referred seller    Rs 50 wallet credit, NOT 25 extra listings
 *   First-order coupon Rs 100 off, Rs 499 minimum cart, 30 days, one order
 *   Ambassador tiers   cumulative - each tier pays once as it is passed
 *   Tier counting      only referrals that reached a credited milestone
 *   Credit expiry      12 months, notice at 30 days remaining
 *
 * WHY THE HOLD IS NULL AND NOT A NUMBER
 * -------------------------------------
 * The instruction was "fix the return window first, then credit the next day".
 * Storing today's return window as a number here would silently decouple the two
 * the moment somebody edits the window in the admin panel - and the window is
 * currently `max_product_return_days = 0` while 251 of 270 products are marked
 * returnable, so it WILL be edited. `hold_days` is therefore nullable from here
 * on, and NULL means "read the store's return window and add
 * referral_settings.hold_days_after_return_window". The rule follows the policy
 * instead of copying it once.
 *
 * WHY PROGRAM-WIDE POLICY LIVES IN `settings` AND NOT IN THESE TABLES
 * ------------------------------------------------------------------
 * The caps and windows decided here are not per-program - one Rs 10,000 monthly
 * ceiling covers all four programs together, and a Rs 2,000 per-referrer cap
 * follows the person, not the program. They go into a `referral_settings` blob
 * beside the other settings groups this app already stores that way, which is
 * also what phase 3's admin screen will edit.
 */
class Migration_referral_program_decisions extends CI_Migration
{
    /**
     * Program-wide policy. Every value here is meant to be edited by an admin
     * later; these are the owner's launch numbers, not constants.
     */
    private function settings()
    {
        return [
            /* Spend-only. The reward engine must refuse to include referral credit
             * in a withdrawal/settlement payout - it pays for subscriptions,
             * listings and services only. */
            'withdrawable'                   => '0',

            /* Rs 10,000 a month across all four programs. When the month's credited
             * total reaches this, the engine stops creating new rewards and says so
             * on the admin screen, rather than overspending quietly. */
            'monthly_budget_cap'             => '10000',

            /* One person cannot take more than this in a calendar month. */
            'per_referrer_monthly_cap'       => '2000',

            /* Added to the store's return window to get the hold before a reward is
             * creditable. "Credit the next day" = 1. */
            'hold_days_after_return_window'  => '1',

            /* Owner's decision, against the recommendation: an order paid from
             * wallet balance still qualifies. Kept as a switch because it is the
             * one setting that can turn the program into a loop - referral credit
             * buys an order, that order earns more referral credit. The Rs 499
             * minimum, the Rs 2,000 monthly per-referrer cap and spend-only
             * together bound the exposure; flip this to 0 if farming appears. */
            'wallet_orders_qualify'          => '1',

            /* A reversal never pushes a wallet below zero. Whatever cannot be
             * recovered is recorded on the reward row as a shortfall and set
             * against that user's next referral credit instead. */
            'allow_negative_on_reversal'     => '0',

            /* Minimum order value that earns a referral reward. */
            'min_order_amount'               => '499',

            /* The referee's first-order coupon. */
            'promo_discount'                 => '100',
            'promo_min_cart'                 => '499',
            'promo_validity_days'            => '30',

            /* Flagged rewards wait this long for a human before auto-releasing, if
             * the order behind them is otherwise clean. Deliberately short - a
             * reward that waits on an admin who never looks is a reward that never
             * pays, and phase 1 flags on shared IP/device, which is common among
             * genuine referrals. An admin can approve or reject inside the window. */
            'flag_review_hold_hours'         => '24',

            /* Unused referral credit expires, with notice. */
            'credit_expiry_months'           => '12',
            'expiry_notice_days'             => '30',

            /* Ambassador tiers pay cumulatively (5 + 10 + 25 = Rs 4,000 in total for
             * someone who reaches Elite), and a referral only counts toward a tier
             * once one of its milestones has actually been credited. */
            'ambassador_cumulative'          => '1',
            'tier_counts_credited_only'      => '1',
        ];
    }

    public function up()
    {
        $this->allow_null_hold_days();
        $this->add_shortfall_column();
        $this->write_settings();
        $this->apply_milestone_decisions();
    }

    /**
     * NULL hold_days = follow the store's return window. Existing rows are moved
     * to NULL so no milestone keeps the placeholder 7 days from 078.
     */
    private function allow_null_hold_days()
    {
        $this->db->query("ALTER TABLE `referral_milestones`
            MODIFY `hold_days` INT(11) NULL DEFAULT NULL");

        $this->db->query("UPDATE `referral_milestones` SET `hold_days` = NULL");
    }

    /**
     * What a reversal could not recover. With negative balances disallowed, a
     * returned order whose reward has already been spent leaves a gap: the money
     * is gone from the wallet and the order it was earned on no longer exists.
     * Writing the gap down here keeps it visible and lets the engine set it
     * against that user's next credit, instead of the loss vanishing into a
     * balance that simply refused to go below zero.
     */
    private function add_shortfall_column()
    {
        if (!$this->db->field_exists('reversed_shortfall', 'referral_rewards')) {
            $this->db->query("ALTER TABLE `referral_rewards`
                ADD COLUMN `reversed_shortfall` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `amount`");
        }
    }

    private function write_settings()
    {
        $existing = $this->db->select('value')->where('variable', 'referral_settings')->get('settings')->row_array();

        /* Merge rather than overwrite: re-running this must not undo an amount an
         * admin has since changed. */
        $current = [];
        if (!empty($existing['value'])) {
            $decoded = json_decode($existing['value'], true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        $merged = array_merge($this->settings(), $current);
        $json = json_encode($merged, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (empty($existing)) {
            $this->db->insert('settings', ['variable' => 'referral_settings', 'value' => $json]);
        } else {
            $this->db->where('variable', 'referral_settings')->update('settings', ['value' => $json]);
        }
    }

    /**
     * The two decisions that change a milestone rather than a global setting.
     */
    private function apply_milestone_decisions()
    {
        /* Every order-triggered milestone gets the Rs 499 floor. The seeded value
         * was Rs 500 for the customer programs and Rs 0 for the seller's own first
         * delivered sale, which would have paid on a Rs 40 order. */
        $this->db->where('code', 'first_delivered_order')
            ->update('referral_milestones', ['min_order_amount' => 499]);

        /* The referred seller was to get 25 extra listings. The owner's decision:
         * Rs 50 in the wallet instead, because free listings are already the offer
         * on the Free Seller plan, so extra listings are not much of a reward.
         *
         * `users`.`listing_bonus` (added by 078) is left in place and unused - the
         * cap-reading code phase 4 would have touched is not written yet, so there
         * is nothing to unpick, and a listing bonus is the obvious shape for any
         * future seller perk. */
        $program = $this->db->select('id')->where('code', 'seller_seller')->get('referral_programs')->row_array();

        if (!empty($program)) {
            $this->db->where(['program_id' => $program['id'], 'code' => 'kyc_shop_live'])
                ->update('referral_milestones', [
                    'referee_benefit_type'  => 'wallet',
                    'referee_benefit_value' => 50,
                ]);
        }
    }

    public function down()
    {
        /* Only the settings row is removed. Reverting the milestone amounts would
         * restore placeholders that were never approved by anybody, which is worse
         * than leaving the owner's real numbers in place. */
        $this->db->where('variable', 'referral_settings')->delete('settings');
    }
}
