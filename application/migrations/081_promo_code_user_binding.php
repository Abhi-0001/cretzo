<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * `promo_code_users` - promo codes that belong to one named person.
 *
 * WHY THIS IS NEEDED
 * ------------------
 * The referral programme owes a referred customer Rs 100 off their first order.
 * The obvious way to deliver that is a promo code, and `promo_codes` already has
 * everything else needed (discount, minimum order, validity window, and
 * `list_promocode = 0` to keep it out of the public coupon list).
 *
 * What it does not have is an owner. validate_promo_code() gates on how many
 * DISTINCT USERS have redeemed a code against `no_of_users` - so a single-use
 * referral code issued to one customer is redeemable by whoever types it first.
 * Referral codes are handed out by email and sit in inboxes; "first to guess it
 * wins" is not an acceptable rule for money owed to a specific person.
 *
 * This table binds a code to the accounts allowed to use it. The guard in
 * validate_promo_code() is deliberately shaped so that it only applies to codes
 * that HAVE bindings: every existing campaign has none and is completely
 * unaffected.
 *
 * `referral_reward_id` links the coupon back to the reward that paid for it, so
 * the admin ledger can show what a referee actually received and a reversal can
 * find the coupon it needs to withdraw.
 */
class Migration_promo_code_user_binding extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `promo_code_users` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `promo_code_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL,
            `referral_reward_id` INT(11) NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_promo_code_user` (`promo_code_id`, `user_id`),
            KEY `idx_promo_code_user_user` (`user_id`),
            KEY `idx_promo_code_user_reward` (`referral_reward_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        /* Dropping this makes every bound referral coupon public - the exact
         * problem the table exists to prevent - so it is left alone. */
    }
}
