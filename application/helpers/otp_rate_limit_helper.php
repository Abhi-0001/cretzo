<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * OTP request rate limiting.
 *
 * WHAT THIS PROTECTS
 * ------------------
 * `verify_user` is the "send me an OTP" endpoint, and it exists twice - once for
 * the browser (controllers/Auth.php) and once for the mobile app
 * (controllers/app/v1/Api.php). Neither had any limit. The app copy reaches
 * set_user_otp(), which calls send_sms() - one billable gateway request per
 * call - so a loop against it spends real money and spams a real person's phone.
 * On the browser copy the SMS is sent by Firebase rather than by us, and Google's
 * own abuse detection does catch floods there, but that is Google's control, not
 * ours: it does not apply to the mobile API, and it stops applying the moment
 * `authentication_method` is switched from "firebase" to "sms".
 *
 * TWO SCOPES, DELIBERATELY UNEQUAL
 * --------------------------------
 * 'mobile' is the real control. It caps how many messages any one number can be
 * made to receive, and it cannot be evaded, because the number is the target of
 * the abuse - changing it means attacking somebody else instead.
 *
 * 'ip' is a softer backstop against one attacker cycling through many numbers,
 * and it is set much looser on purpose. This site sits behind Hostinger's CDN
 * ("Server: hcdn") and `proxy_ips` in config/config.php is empty, so CodeIgniter's
 * ip_address() may report a CDN edge address that is SHARED BY MANY REAL
 * VISITORS. A tight per-IP cap could therefore lock out a whole population of
 * innocent users - a far worse outcome than the flooding it prevents. So the IP
 * cap is generous, and client_ip() below prefers the forwarded client address
 * when the CDN supplies one. That forwarded header is client-settable and so is
 * spoofable; that is an accepted weakness of the SOFT control only, and it is why
 * the per-number cap is the one doing the actual work.
 *
 * Tune the limits by defining these in config/constants.php - no need to edit
 * this file.
 */

if (!defined('OTP_RATE_LIMIT_WINDOW')) {
    define('OTP_RATE_LIMIT_WINDOW', 3600);      // seconds
}
if (!defined('OTP_RATE_LIMIT_PER_MOBILE')) {
    define('OTP_RATE_LIMIT_PER_MOBILE', 5);     // OTPs per number per window
}
if (!defined('OTP_RATE_LIMIT_PER_IP')) {
    define('OTP_RATE_LIMIT_PER_IP', 30);        // OTPs per address per window
}

if (!function_exists('otp_rate_limit_key')) {
    /**
     * Collapses the many ways one phone number is written into a single key.
     *
     * The browser posts "9675916976" while an app build may post "919675916976"
     * or "+91 9675916976". Keying on the raw string would let an attacker reset
     * the counter just by prefixing the country code, so digits-only and the last
     * 10 are used. Two different countries' numbers can in principle end in the
     * same 10 digits and share a counter; that errs towards limiting slightly
     * more than necessary, which is the safe direction for a cap this generous.
     */
    function otp_rate_limit_key($mobile)
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        return (strlen($digits) > 10) ? substr($digits, -10) : $digits;
    }
}

if (!function_exists('otp_rate_limit_client_ip')) {
    function otp_rate_limit_client_ip()
    {
        $ci = &get_instance();

        // Hostinger's CDN forwards the visitor address here. It is not trustworthy
        // (any client can set it), but see the header comment: preferring it fails
        // in the direction of letting a spoofer through rather than of blocking a
        // crowd of real users who share one CDN egress address.
        $forwarded = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        if ($forwarded !== '') {
            // Left-most entry is the original client; the rest are proxy hops.
            $first = trim(explode(',', $forwarded)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }

        $ip = $ci->input->ip_address();
        return ($ip !== FALSE && $ip !== '') ? $ip : '0.0.0.0';
    }
}

if (!function_exists('otp_rate_limit_guard')) {
    /**
     * Checks the caps and, when the request is allowed, counts it.
     *
     * Returns ['allowed' => bool, 'message' => string, 'retry_after' => int],
     * where retry_after is seconds until the window rolls over.
     *
     * Call this AFTER input validation and AFTER the "already registered" checks,
     * so a user who simply mistyped an existing address does not spend quota.
     */
    function otp_rate_limit_guard($mobile)
    {
        $ci = &get_instance();
        $now = time();

        $mobile_key = otp_rate_limit_key($mobile);
        if ($mobile_key === '') {
            // Nothing to key on. Validation upstream should have caught this, so
            // rather than fail open OR fail closed on a guess, let it pass and let
            // the caller's own validation speak.
            return ['allowed' => true, 'message' => '', 'retry_after' => 0];
        }

        $scopes = [
            ['scope' => 'mobile', 'identifier' => $mobile_key,              'limit' => OTP_RATE_LIMIT_PER_MOBILE],
            ['scope' => 'ip',     'identifier' => otp_rate_limit_client_ip(), 'limit' => OTP_RATE_LIMIT_PER_IP],
        ];

        // Read both counters BEFORE incrementing either. If the number is already
        // blocked, its attempt must not also burn the IP's budget - otherwise one
        // stuck user retrying could exhaust the allowance of everyone sharing
        // their address.
        $state = [];
        foreach ($scopes as $s) {
            $row = $ci->db->select('attempts, window_start')
                ->where('scope', $s['scope'])
                ->where('identifier', $s['identifier'])
                ->get('otp_rate_limits')
                ->row_array();

            $window_start = (!empty($row['window_start'])) ? strtotime($row['window_start']) : 0;
            $expired = ($window_start <= 0 || ($now - $window_start) >= OTP_RATE_LIMIT_WINDOW);

            $state[] = [
                'scope'        => $s['scope'],
                'identifier'   => $s['identifier'],
                'limit'        => $s['limit'],
                'attempts'     => $expired ? 0 : (int) $row['attempts'],
                'window_start' => $expired ? $now : $window_start,
                'expired'      => $expired,
            ];
        }

        foreach ($state as $s) {
            if ($s['attempts'] >= $s['limit']) {
                $retry_after = max(1, ($s['window_start'] + OTP_RATE_LIMIT_WINDOW) - $now);
                $minutes = (int) ceil($retry_after / 60);

                log_message('info', 'otp_rate_limit: blocked ' . $s['scope'] . ' ' . $s['identifier']
                    . ' after ' . $s['attempts'] . ' requests');

                // The wording does not say which cap was hit. Telling an attacker
                // whether they tripped the number or the address limit tells them
                // which one to work around.
                return [
                    'allowed'     => false,
                    'message'     => 'Too many OTP requests. Please try again in ' . $minutes
                        . ' ' . ($minutes === 1 ? 'minute' : 'minutes') . '.',
                    'retry_after' => $retry_after,
                ];
            }
        }

        $stamp = date('Y-m-d H:i:s', $now);
        foreach ($state as $s) {
            /* ON DUPLICATE KEY UPDATE against uq_otp_rate_scope_identifier, so two
               requests arriving together cannot create two rows for one key. The
               window is restarted in SQL rather than PHP when it has expired, so
               the decision is made against the row as it exists at write time. */
            $ci->db->query(
                "INSERT INTO `otp_rate_limits` (`scope`, `identifier`, `attempts`, `window_start`, `updated_at`)
                 VALUES (?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    `attempts`     = IF(`window_start` <= ?, 1, `attempts` + 1),
                    `window_start` = IF(`window_start` <= ?, VALUES(`window_start`), `window_start`),
                    `updated_at`   = VALUES(`updated_at`)",
                [
                    $s['scope'],
                    $s['identifier'],
                    $stamp,
                    $stamp,
                    date('Y-m-d H:i:s', $now - OTP_RATE_LIMIT_WINDOW),
                    date('Y-m-d H:i:s', $now - OTP_RATE_LIMIT_WINDOW),
                ]
            );
        }

        otp_rate_limit_prune();

        return ['allowed' => true, 'message' => '', 'retry_after' => 0];
    }
}

if (!function_exists('otp_rate_limit_prune')) {
    /**
     * Drops counters whose window closed long ago.
     *
     * Done here, occasionally, rather than on a cron: the table only ever holds
     * one row per number seen in the last window, so it is small, and adding a
     * scheduled job for it would be one more thing that can silently stop running
     * - which this codebase has been bitten by before.
     */
    function otp_rate_limit_prune()
    {
        if (random_int(1, 50) !== 1) {
            return;
        }
        $ci = &get_instance();
        $ci->db->query(
            "DELETE FROM `otp_rate_limits` WHERE `window_start` < ?",
            [date('Y-m-d H:i:s', time() - (OTP_RATE_LIMIT_WINDOW * 24))]
        );
    }
}
