<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * `referrals`.`signup_source` - how a referral code reached the person who used it.
 *
 * Added with the QR feature, and deliberately not after it: the source of a
 * referral cannot be reconstructed once the signup has completed. The moment a
 * code is accepted, the difference between "scanned a card at a stall" and
 * "typed it from a WhatsApp message" is gone.
 *
 * That difference is the only thing that can answer the question printed cards
 * raise - should we print another 500? - so the column ships with the first QR
 * rather than being retro-fitted later, when it would be empty for every
 * referral that had already happened.
 *
 * Values:
 *   qr      the visitor arrived on a link carrying src=qr - a scanned code
 *   link    a ?ref= share link, from WhatsApp or anywhere else
 *   typed   the code was entered by hand into the signup field
 *   ''      referred before this column existed
 *
 * It is a plain VARCHAR rather than an ENUM on purpose: a later channel (a
 * printed magazine insert, a partner site) should be one more string, not a
 * schema migration.
 */
class Migration_referral_signup_source extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('signup_source', 'referrals')) {
            $this->db->query("ALTER TABLE `referrals`
                ADD COLUMN `signup_source` VARCHAR(16) NOT NULL DEFAULT '' AFTER `code_used`");

            /* Indexed because the admin ledger and the cost report both group by
             * it, and it is low-cardinality - exactly the shape an index helps. */
            $this->db->query("ALTER TABLE `referrals`
                ADD KEY `idx_referral_source` (`signup_source`)");
        }
    }

    public function down()
    {
        /* Left in place: dropping it destroys the only record of which channel
         * brought each referral, and nothing depends on its absence. */
    }
}
