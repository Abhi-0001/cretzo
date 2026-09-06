<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * `otp_rate_limits` - counters that cap how often an OTP can be requested.
 *
 * WHY THIS IS NEEDED
 * ------------------
 * Nothing in this application limited OTP requests. `verify_user` on the web
 * (controllers/Auth.php) and on the mobile API (controllers/app/v1/Api.php)
 * could both be called in a loop, and the app one reaches set_user_otp(), which
 * calls send_sms() - a real, billable gateway request per call. The only thing
 * standing in the way was Firebase's own abuse detection on the browser path,
 * which (a) is Google's control and not ours, (b) does not cover the mobile API
 * at all, and (c) disappears entirely the day `authentication_method` is
 * switched from "firebase" to "sms".
 *
 * SHAPE
 * -----
 * One row per (scope, identifier) holding a fixed-window counter. Two scopes:
 *
 *   'mobile' - the number the OTP would be sent to. This is the control that
 *              actually matters: it caps what any single person's phone can be
 *              made to receive, and an attacker cannot dodge it, because the
 *              number IS the thing being attacked.
 *   'ip'     - the requesting address, to slow down someone cycling through
 *              many different numbers. Weaker on purpose; see the note about
 *              proxies in helpers/otp_rate_limit_helper.php.
 *
 * A fixed window is used rather than a rolling log of every attempt so the
 * table stays at one row per active number instead of growing forever. Stale
 * rows are reset in place when their window expires, and pruned opportunistically
 * by the helper.
 */
class Migration_otp_rate_limits extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `otp_rate_limits` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `scope` VARCHAR(16) NOT NULL,
            `identifier` VARCHAR(64) NOT NULL,
            `attempts` INT(11) NOT NULL DEFAULT 0,
            `window_start` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_otp_rate_scope_identifier` (`scope`, `identifier`),
            KEY `idx_otp_rate_window_start` (`window_start`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        /* Dropping this removes the only OTP flood protection the application
         * owns, so it is deliberately not automated. Drop it by hand if you
         * really mean to. */
    }
}
