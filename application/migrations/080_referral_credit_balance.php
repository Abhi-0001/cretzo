<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * `users`.`referral_credit` - the part of a wallet balance that came from the
 * referral programme and therefore cannot be withdrawn as cash.
 *
 * The owner's decision was that referral money is spend-only: usable against
 * orders, subscriptions and seller services, never paid out to a bank account.
 * A wallet with one `balance` column cannot express that on its own - once a
 * Rs 100 reward lands in it, the money is indistinguishable from a refund or a
 * settlement, and the next payout takes it.
 *
 * So the promotional part is counted separately here:
 *
 *   credited reward        referral_credit += amount
 *   any wallet debit       referral_credit -= min(debit, referral_credit)
 *   reversed reward        referral_credit -= whatever was recovered
 *
 * Spending it FIRST is deliberate. It is the reading most favourable to the
 * user (their restricted money goes first, leaving their withdrawable money
 * intact), and it is the only rule that needs no per-transaction tagging - which
 * matters because wallet debits happen in a dozen places in this codebase.
 *
 * The counter is maintained inside update_wallet_balance(), the one function
 * every wallet movement in the application already goes through, for the same
 * reason the referral code hook lives inside Ion_auth_model::register(): a rule
 * enforced in one chokepoint holds for callers nobody has thought about yet.
 *
 * `withdrawable_balance()` in the referral helper is what payout paths must ask
 * instead of reading `balance` directly.
 */
class Migration_referral_credit_balance extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('referral_credit', 'users')) {
            $this->db->query("ALTER TABLE `users`
                ADD COLUMN `referral_credit` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `balance`");
        }

        /* Nothing to backfill: no referral reward has ever been credited on this
         * platform - the legacy feature could not pay (no user had a code), and the
         * new engine has not run. A backfill would therefore be inventing history. */
    }

    public function down()
    {
        /* Left in place. Dropping it would silently make every restricted rupee
         * withdrawable, which is the one outcome this column exists to prevent. */
    }
}
