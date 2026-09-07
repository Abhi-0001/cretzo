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

if (!function_exists('lookup_rate_limit_guard')) {
    /**
     * Per-IP cap for cheap, unauthenticated "does this account exist?" endpoints.
     *
     * WHY THIS EXISTS. Four endpoints answer, without any authentication, whether a
     * given mobile number or email belongs to an account here - and one of them also
     * says WHICH portal it belongs to:
     *
     *     admin/login/check_reset_account       "Account found." / which portal
     *     seller/login/check_reset_account
     *     home/check_reset_account
     *     seller/auth/check_phone, check_email
     *
     * They exist for a good reason: the reset form should tell someone they typed the
     * wrong number before it burns an OTP send on it. But unthrottled they are a
     * directory: sweep a number range and you have a list of every account on the
     * site, sorted by role, which is exactly the input to a credential-stuffing or
     * targeted-reset campaign. The existing otp_rate_limit_guard() does not cover
     * them, because it caps SENDING, and these endpoints deliberately send nothing.
     *
     * Keyed on IP only. There is no per-identifier key on purpose: capping per number
     * would let an attacker walk a range freely (one lookup each) while doing nothing
     * about the sweep, which is the actual attack.
     *
     * Reuses the otp_rate_limits table and its ON DUPLICATE KEY UPDATE pattern, so
     * there is no new schema and no second implementation of window handling to keep
     * in step. The `scope` column keeps these counters separate from the OTP ones.
     *
     * Deliberately generous (60/hour by default): a real person correcting a typo may
     * try a handful of times, several people can share one CDN address, and the goal
     * is to make a 10,000-number sweep impractical rather than to police normal use.
     *
     * FAILS OPEN. If the table is missing (migration not run) this returns allowed,
     * because locking every user out of password reset is a worse outcome than an
     * unthrottled lookup - and the lookup was unthrottled before this existed anyway.
     *
     * @param  string $scope  Short label, e.g. 'lookup'. Namespaces the counter.
     * @param  int    $max    Requests permitted per window per IP.
     * @param  int    $window Window length in seconds.
     * @return array{allowed: bool, message: string, retry_after: int}
     */
    function lookup_rate_limit_guard($scope = 'lookup', $max = 60, $window = 3600)
    {
        $ci  = &get_instance();
        $now = time();
        $ip  = otp_rate_limit_client_ip();

        if ($ip === '' || $ip === null) {
            return ['allowed' => true, 'message' => '', 'retry_after' => 0];
        }

        // Identifier column is VARCHAR(64); an IPv6 address fits, but hash anyway so
        // the length is fixed and the raw address is not stored in a second place.
        $identifier = substr(hash('sha256', (string) $ip), 0, 64);
        $scope      = substr((string) $scope, 0, 16);

        try {
            $row = $ci->db->query(
                "SELECT `attempts`, UNIX_TIMESTAMP(`window_start`) AS `started`
                   FROM `otp_rate_limits`
                  WHERE `scope` = ? AND `identifier` = ?
                  LIMIT 1",
                [$scope, $identifier]
            )->row_array();
        } catch (Exception $e) {
            log_message('error', 'lookup_rate_limit_guard: counter table unavailable, allowing request - ' . $e->getMessage());
            return ['allowed' => true, 'message' => '', 'retry_after' => 0];
        }

        if (!empty($row) && (int) $row['started'] > ($now - $window) && (int) $row['attempts'] >= $max) {
            $retry_after = ((int) $row['started'] + $window) - $now;
            $minutes     = max(1, (int) ceil($retry_after / 60));

            log_message('error', 'lookup_rate_limit_guard: refusing ' . $scope . ' from ' . $ip
                . ' after ' . $row['attempts'] . ' requests in the current window');

            return [
                'allowed'     => false,
                'message'     => 'Too many attempts. Please try again in ' . $minutes
                    . ' ' . ($minutes === 1 ? 'minute' : 'minutes') . '.',
                'retry_after' => $retry_after,
            ];
        }

        $stamp = date('Y-m-d H:i:s', $now);
        $edge  = date('Y-m-d H:i:s', $now - $window);

        try {
            $ci->db->query(
                "INSERT INTO `otp_rate_limits` (`scope`, `identifier`, `attempts`, `window_start`, `updated_at`)
                 VALUES (?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    `attempts`     = IF(`window_start` <= ?, 1, `attempts` + 1),
                    `window_start` = IF(`window_start` <= ?, VALUES(`window_start`), `window_start`),
                    `updated_at`   = VALUES(`updated_at`)",
                [$scope, $identifier, $stamp, $stamp, $edge, $edge]
            );
        } catch (Exception $e) {
            log_message('error', 'lookup_rate_limit_guard: could not record attempt - ' . $e->getMessage());
        }

        otp_rate_limit_prune();

        return ['allowed' => true, 'message' => '', 'retry_after' => 0];
    }
}
