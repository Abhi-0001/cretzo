<?php
defined('BASEPATH') or exit('No direct script access allowed');

function parseSmsString($string, $data = [])
{
    $string = (string) $string;

    foreach ($data as $key => $val) {
        // A null/empty field used to be substituted with the literal text "NULL", so a customer
        // whose order had no delivery date received "Estimated Delivery: NULL". An empty string
        // reads correctly and is what the surrounding template sentence expects.
        $string = str_replace("{" . $key . "}", ($val === null || $val === '') ? '' : (string) $val, $string);
    }

    // Any placeholder the data set does not provide would otherwise be delivered to the customer
    // verbatim, e.g. "Best regards, {system.company_name}".
    $string = preg_replace('/\{[a-z0-9_.]+\}/i', '', $string);

    return $string;
}

/**
 * The seeded notification templates in `custom_sms` store their line breaks as the LITERAL
 * two-character sequences \r and \n rather than real control characters (verified in the live
 * data: LOCATE('\\r', message) is non-zero on every row). Delivered as-is, the customer got one
 * unbroken paragraph with visible "\r\n" strings sprinkled through it.
 *
 * Normalise to real newlines here, so SMS gets plain text and the HTML mail path can turn them
 * into <br>.
 */
function normalize_notification_text($text)
{
    $text = (string) $text;
    // Literal backslash-r / backslash-n written into the template.
    $text = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $text);
    // Real CRLF -> LF so nl2br output is consistent.
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    return $text;
}

/**
 *
 ** This function sends verifies the modules and sends sms for email from config saved in database.
 *@param array $emails = [
 *    "customer" => [],
 *    "admin" => [],
 *    "seller" => [],
 *    "delivery_boy" => []
 *]
 * @param array $phone = [
 *    "customer" => [],
 *    "admin" => [],
 *    "seller" => [],
 *    "delivery_boy" => []
 *]
 * @param string $event
 * This the the event like place_order, update_order_status, etc...
 * @return array [
 *   "error" => bool,
 *   "message" => string,
 *   "data" => mixed
 *]
 */
function notify_event(string $event,array $emails = [], array $phone = [],  $where = []): array
{

    $send_notification_settings = get_settings('send_notification_settings', true);

    if (!isset($send_notification_settings[$event])) {
        // This was a silent early return, and the `send_notification_settings` row ships EMPTY:
        // the on/off matrix under Admin > SMS Gateway Settings has to be saved once before it
        // exists at all. Until then this function bailed out here for EVERY event, so not one
        // order-confirmation, shipped, delivered, cancelled, return-approved, wallet or
        // settlement email or SMS was ever sent - with nothing logged and no error surfaced
        // anywhere. Migration 048 seeds a sensible default; this log makes the state visible if
        // the row is ever cleared again.
        log_message('error', 'notify_event: no send_notification_settings entry for event "' . $event . '" - nothing sent. Save Admin > SMS Gateway Settings > Notification Modules to configure it.');
        return [
            "error" => true,
            "message" => "setting not found"
        ];
    }
    $send_notification_settings = $send_notification_settings[$event];
    $data = get_order_data($where);
    if ($data["error"]) {
        return $data;
    }
    if (count($data["data"]) == 0) {
        return [
            "error" => true,
            "message" => "No data found"
        ];
    }
    $data = $data["data"];
    $template =  fetch_details('custom_sms', ['type' => $event], ['title', 'message']);
    if (count($template) == 0) {
        log_message('error', 'notify_event: no custom_sms template for event "' . $event . '" - nothing sent.');
        return [
            "error" => true,
            "message" => "Template not found."
        ];
    }

    $title = trim(parseSmsString(normalize_notification_text($template[0]["title"]), $data));
    $message = parseSmsString(normalize_notification_text($template[0]["message"]), $data);

    $sendEmail = [];
    $sendPhone = [];
    if (isset($send_notification_settings["notification_via_mail"]) && $send_notification_settings["notification_via_mail"] == "on") {
        if (isset($send_notification_settings["customer"]) && $send_notification_settings["customer"] == "on") {
            $array = isset($emails["customer"]) && (count($emails["customer"]) != 0)  ? $emails["customer"] : [];
            $sendEmail = array_merge($sendEmail, $array);
        }
        if (isset($send_notification_settings["admin"]) && $send_notification_settings["admin"] == "on") {
            // $array = isset($emails["admin"]) ? $emails["admin"] : [];
            array_push($sendEmail, $data['system.support_email']);
        }
        if (isset($send_notification_settings["seller"]) && $send_notification_settings["seller"] == "on") {
            $array = isset($emails["seller"]) && (count($emails["seller"]) != 0) ? $emails["seller"] : [];
            $sendEmail = array_merge($sendEmail, $array);
        }
        if (isset($send_notification_settings["delivery_boy"]) && $send_notification_settings["delivery_boy"] == "on") {
            $array = isset($emails["delivery_boy"]) && (count($emails["delivery_boy"]) != 0)  ? $emails["delivery_boy"] : [];
            $sendEmail = array_merge($sendEmail, $array);
        }
    }
    if (isset($send_notification_settings["notification_via_sms"]) && $send_notification_settings["notification_via_sms"] == "on") {
        if (isset($send_notification_settings["customer"]) && $send_notification_settings["customer"] == "on") {
            $array = isset($phone["customer"])  && (count($phone["customer"]) != 0)  ? $phone["customer"] : [];
            $sendPhone = array_merge($sendPhone, $array);
        }
        if (isset($send_notification_settings["admin"]) && $send_notification_settings["admin"] == "on") {
            // $array = isset($phone["admin"]) ? $phone["admin"] : [];
            // $sendPhone = array_merge($sendPhone, $array);
            array_push($sendPhone, $data['system.support_number']);

        }
        if (isset($send_notification_settings["seller"]) && $send_notification_settings["seller"] == "on") {
            $array = isset($phone["seller"]) && (count($phone["seller"]) != 0)  ? $phone["seller"] : [];
            $sendPhone = array_merge($sendPhone, $array);
        }
        if (isset($send_notification_settings["delivery_boy"]) && $send_notification_settings["delivery_boy"] == "on") {
            $array = isset($phone["delivery_boy"]) && (count($phone["delivery_boy"]) != 0)  ? $phone["delivery_boy"] : [];
            $sendPhone = array_merge($sendPhone, $array);
        }
    }

    $t = &get_instance();


    $sms_event = $t->config->item('notification_modules');
    // foreach ($sms_event as $key => $value) {
    # code...
    if (!array_key_exists($event, $sms_event)) {
        return [
            "error" => true,
            "message" => "Invalid event."
        ];
    }



    // Some callers pass an already-array value wrapped in another array, e.g. Cart.php's
    // bank_transfer_proof call does ["admin" => [$admin_email]] where $admin_email is itself a
    // list. Those nested entries used to survive array_merge() and reach send_mail()/send_sms()
    // as an array, which is an "Array to string conversion" and a message sent to the literal
    // recipient "Array". Flatten first so the shape a caller happens to use cannot break delivery.
    $flatten = function (array $list) {
        $out = [];
        array_walk_recursive($list, function ($value) use (&$out) {
            $out[] = $value;
        });
        return $out;
    };
    $sendEmail = $flatten($sendEmail);
    $sendPhone = $flatten($sendPhone);

    // The same address/number routinely lands in these lists more than once - e.g. the customer
    // IS the admin on a small shop, or the seller and delivery boy share a number - and each
    // duplicate sent another identical copy of the message.
    $sendPhone = array_values(array_unique(array_filter(array_map('strval', $sendPhone), function ($v) {
        return trim($v) !== '';
    })));
    $sendEmail = array_values(array_unique(array_filter(array_map('strval', $sendEmail), function ($v) {
        return trim($v) !== '' && filter_var(trim($v), FILTER_VALIDATE_EMAIL);
    })));

    $sent = ['sms' => 0, 'mail' => 0];
    $failed = ['sms' => 0, 'mail' => 0];

    // The Mobile alerts feature texts the same people about the same order events (see
    // notify_user_mobile()). Each path records what it sent and skips what the other already
    // sent for this order + status, so nobody gets two texts for one event.
    $order_ref = isset($where['orders.id']) ? (string) $where['orders.id'] : '';
    foreach ($sendPhone as $phone_number) {
        if (sms_already_sent_for_order($phone_number, $order_ref, $event)) {
            continue;
        }
        $result = send_sms($phone_number, $message);
        if (!empty($result['error']) || empty($result['http_code']) || $result['http_code'] < 200 || $result['http_code'] >= 300) {
            $failed['sms']++;
        } else {
            $sent['sms']++;
            sms_record_for_order($phone_number, $order_ref, $event, $message);
        }
    }

    // send_mail() is configured with mail_content_type=html, so raw newlines would collapse to a
    // single run-on paragraph in the delivered mail regardless of how clean the source text is.
    $html_message = nl2br(html_escape($message));

    foreach ($sendEmail as $email_address) {
        $result = send_mail($email_address, $title, $html_message);
        if (!empty($result['error'])) {
            $failed['mail']++;
        } else {
            $sent['mail']++;
        }
    }

    // The return value used to be a bare [], so no caller could tell the difference between
    // "delivered" and "silently did nothing".
    if ($failed['sms'] > 0 || $failed['mail'] > 0) {
        log_message('error', 'notify_event(' . $event . '): ' . $failed['mail'] . ' email and ' . $failed['sms'] . ' sms deliveries failed.');
    }

    return [
        "error"   => ($sent['sms'] + $sent['mail']) === 0 && ($failed['sms'] + $failed['mail']) > 0,
        "message" => "sent " . $sent['mail'] . " email(s) and " . $sent['sms'] . " sms",
        "data"    => ['sent' => $sent, 'failed' => $failed],
    ];
}

function send_sms($phone, $msg, $country_code = "+91")
{
    $data = get_settings('sms_gateway_settings', true);

    // Every key below used to be accessed directly (`$data["body_key"]`) with no
    // isset() guard - when no SMS gateway is configured in admin yet (or only
    // partially), each missing key emitted a PHP warning that got printed
    // straight into the HTTP response body, corrupting any endpoint that calls
    // send_sms() and returns JSON (jQuery's `dataType:'json'` then fails to
    // parse the response and silently falls into the error handler).
    if (empty($data) || empty($data["base_url"])) {
        return ['body' => null, 'http_code' => 0, 'error' => 'SMS gateway is not configured.'];
    }

    $data["body"] = [];
    if (!empty($data["body_key"])) {
        for ($i = 0; $i < count($data["body_key"]); $i++) {
            $key = $data["body_key"][$i];
            $value = parse_sms($data["body_value"][$i], $phone, $msg, $country_code);

            $data["body"][$key] = $value;
        }
    }
    $data["header"] = [];
    if (!empty($data["header_key"])) {

        for ($i = 0; $i < count($data["header_key"]); $i++) {
            $key = $data["header_key"][$i];
            $value = parse_sms($data["header_value"][$i], $phone, $msg, $country_code);

            $data["header"][] = $key . ": " . $value;
        }
    }
    $data["params"] = [];
    if (!empty($data["params_key"])) {
        for ($i = 0; $i < count($data["params_key"]); $i++) {
            $key = $data["params_key"][$i];
            $value = parse_sms($data["params_value"][$i], $phone, $msg, $country_code);

            $data["params"][$key] = $value;
        }
    }
    // The params rows built above were never used: a GET gateway needs them in the query string
    // and nothing appended them, so such a gateway received a bare base URL.
    $url = $data["base_url"];
    if (!empty($data["params"])) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data["params"]);
    }
    return curl_sms($url, isset($data["sms_gateway_method"]) ? $data["sms_gateway_method"] : 'GET', $data["body"], $data["header"]);
}

function curl_sms($url, $method = 'GET', $data = [], $headers = [])
{

    $ch = curl_init();
    $curl_options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 0,
        // No timeout at all before: a gateway that accepted the connection and never answered
        // held the request (or the after-response worker) open until PHP's limit.
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
        )
    );

    if (count($headers) != 0) {
        // print_r($headers);
        $curl_options[CURLOPT_HTTPHEADER] = $headers;
    }

    if (strtolower($method) == 'post') {
        $curl_options[CURLOPT_POST] = 1;
        $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
    } else {
        $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
    }
    curl_setopt_array($ch, $curl_options);
    // print_r($curl_options);

    $raw = curl_exec($ch);
    $result = array(
        'body' => json_decode($raw, true),
        'raw_body' => $raw,
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
    );

    // Transport-level failures (DNS, TLS, timeout) leave http_code at 0 and were
    // previously indistinguishable from success to every caller.
    if (curl_errno($ch)) {
        $result['error'] = 'SMS gateway request failed: ' . curl_error($ch);
    }
    curl_close($ch);

    if (!empty($result['error'])) {
        log_message('error', 'curl_sms: ' . $result['error'] . ' (url: ' . $url . ')');
    }
    return $result;
}


/* ======================================================================================
 *  MOBILE ALERTS - every in-app notification is also texted to the account's mobile number
 * ======================================================================================
 * add_user_notification() (the one writer of per-user notifications: orders, tickets, approvals,
 * payouts, referral rewards...) calls notify_user_mobile() after saving the in-app row, and the
 * admin "Send Notification" screen calls notify_audience_mobile() for what it broadcasts.
 *
 * Delivery runs after the HTTP response (run_after_response) and can never throw into the caller:
 * a gateway outage must not break an order or a form save. Every attempt is written to
 * notification_mobile_log, which is also what suppresses an identical message to the same
 * person within 10 minutes. Nothing is sent while no SMS gateway is configured.
 *
 * Channel list: only 'sms' exists. A WhatsApp sender plugs in by adding a channel to
 * mobile_alert_channels() and a branch in mobile_alert_deliver().
 */

function mobile_alert_settings()
{
    $raw = get_settings('mobile_notifications', true);
    $s = is_array($raw) ? $raw : [];
    return [
        'sms'        => (int) (isset($s['sms']) ? $s['sms'] : 1),
        'sellers'    => (int) (isset($s['sellers']) ? $s['sellers'] : 1),
        'customers'  => (int) (isset($s['customers']) ? $s['customers'] : 1),
        'broadcasts' => (int) (isset($s['broadcasts']) ? $s['broadcasts'] : 0),
    ];
}

function mobile_alert_channels()
{
    $s = mobile_alert_settings();
    $gateway = get_settings('sms_gateway_settings', true);
    $channels = [];
    if ($s['sms'] && !empty($gateway) && !empty($gateway['base_url'])) {
        $channels[] = 'sms';
    }
    return $channels;
}

/** The text a person receives: app name, title and message on one line, no markup. */
function mobile_alert_text($title, $message)
{
    $settings = get_settings('system_settings', true);
    $app = !empty($settings['app_name']) ? $settings['app_name'] : '';
    $body = trim(html_entity_decode(strip_tags((string) $title)) . ' - ' . html_entity_decode(strip_tags((string) $message)), ' -');
    $body = trim(preg_replace('/\s+/u', ' ', $body));
    $text = ($app !== '' ? $app . ': ' : '') . $body;
    return mb_strlen($text) > 300 ? mb_substr($text, 0, 297) . '...' : $text;
}

/** National number for the gateway: digits only, last 10 when a country code is stored with it. */
function mobile_alert_number($raw)
{
    $digits = preg_replace('/\D/', '', (string) $raw);
    if (strlen($digits) > 10) {
        $digits = substr($digits, -10);
    }
    return strlen($digits) === 10 ? $digits : '';
}

/**
 * Text one account about a notification. Called for sellers and buyers alike; which of the two it
 * is comes from the link the notification points at (seller panel links carry "/seller").
 *
 * @param string $audience 'auto' | 'sellers' | 'customers' - 'auto' reads it from $link
 */
function notify_user_mobile($user_id, $title, $message, $type = '', $type_id = '', $link = '', $audience = 'auto')
{
    try {
        $user_id = (int) $user_id;
        $channels = mobile_alert_channels();
        if ($user_id < 1 || empty($channels)) {
            return false;
        }
        if ($audience === 'auto') {
            $audience = strpos((string) $link, '/seller') !== false ? 'sellers' : 'customers';
        }
        $s = mobile_alert_settings();
        if (empty($s[$audience])) {
            return false;
        }
        $text = mobile_alert_text($title, $message);
        if ($text === '') {
            return false;
        }
        $type = (string) $type;
        $type_id = (string) $type_id;
        run_after_response(function () use ($user_id, $channels, $text, $type, $type_id) {
            mobile_alert_deliver($user_id, $channels, $text, $type, $type_id);
        }, 'mobile alert');
        return true;
    } catch (Throwable $e) {
        log_message('error', 'notify_user_mobile: ' . $e->getMessage());
        return false;
    }
}

/** The worker: look the number up, skip repeats, send, log. */
function mobile_alert_deliver($user_id, array $channels, $text, $type, $type_id)
{
    $t = &get_instance();
    $row = $t->db->select('mobile')->where('id', (int) $user_id)->get('users')->row_array();
    $mobile = mobile_alert_number(isset($row['mobile']) ? $row['mobile'] : '');
    $hash = md5($text);

    foreach ($channels as $channel) {
        $log = [
            'user_id' => (int) $user_id, 'channel' => $channel, 'mobile' => $mobile,
            'type' => mb_substr($type, 0, 64), 'type_id' => mb_substr($type_id, 0, 128),
            'message_hash' => $hash, 'message' => mb_substr($text, 0, 600),
        ];
        if ($mobile === '') {
            $t->db->insert('notification_mobile_log', $log + ['status' => 'skipped', 'error' => 'No valid 10-digit mobile number on the account.']);
            continue;
        }
        $dup = $t->db->where(['user_id' => (int) $user_id, 'channel' => $channel, 'message_hash' => $hash, 'status' => 'sent'])
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 600))->count_all_results('notification_mobile_log');
        if ($dup || ($channel === 'sms' && sms_already_sent_for_order($mobile, $type_id, $type))) {
            continue;
        }
        $res = send_sms($mobile, $text);
        $ok = empty($res['error']) && !empty($res['http_code']) && $res['http_code'] >= 200 && $res['http_code'] < 300;
        $t->db->insert('notification_mobile_log', $log + [
            'status' => $ok ? 'sent' : 'failed',
            'error'  => $ok ? null : mb_substr(!empty($res['error']) ? $res['error'] : ('Gateway answered HTTP ' . (int) $res['http_code'] . ' ' . substr((string) (isset($res['raw_body']) ? $res['raw_body'] : ''), 0, 120)), 0, 255),
        ]);
    }
}

/**
 * Admin broadcast -> mobile. A named list of accounts is always texted; a whole audience only
 * when the "broadcasts" switch is on, because that is one billed SMS per account.
 */
function notify_audience_mobile($send_to, array $user_ids, $title, $message, $type = '', $type_id = '', $link = '')
{
    try {
        $s = mobile_alert_settings();
        if (empty(mobile_alert_channels())) {
            return 0;
        }
        $t = &get_instance();
        $seller_rows = $t->db->select('user_id')->where('group_id', 4)->get('users_groups')->result_array();
        $sellers = array_map('intval', array_column($seller_rows, 'user_id'));
        $targets = [];   // user_id => audience
        if ($send_to === 'specific_user') {
            foreach ($user_ids as $id) {
                $targets[(int) $id] = in_array((int) $id, $sellers, true) ? 'sellers' : 'customers';
            }
        } elseif ($s['broadcasts']) {
            if ($send_to === 'all_sellers' || $send_to === 'all_users') {
                foreach ($sellers as $id) {
                    $targets[$id] = 'sellers';
                }
            }
            if ($send_to === 'all_customers' || $send_to === 'all_users') {
                $cust = $t->db->select('u.id')->from('users u')->join('users_groups ug', 'ug.user_id = u.id AND ug.group_id = 2')
                    ->where('u.active', 1)->get()->result_array();
                foreach ($cust as $c) {
                    if (!in_array((int) $c['id'], $sellers, true)) {
                        $targets[(int) $c['id']] = 'customers';
                    }
                }
            }
        }
        $count = 0;
        foreach (array_slice($targets, 0, 5000, true) as $id => $aud) {
            if ($id > 0 && notify_user_mobile($id, $title, $message, $type, $type_id, $link, $aud)) {
                $count++;
            }
        }
        return $count;
    } catch (Throwable $e) {
        log_message('error', 'notify_audience_mobile: ' . $e->getMessage());
        return 0;
    }
}

/**
 * One word for the status an SMS is about, so the SMS Matrix event "customer_order_shipped" and
 * the notification type "order_shipped" count as the same thing. '' when it is not an order
 * status (then nothing is de-duplicated across the two paths).
 */
function sms_status_key($event_or_type)
{
    $e = strtolower((string) $event_or_type);
    if ($e === 'place_order') {
        return 'placed';
    }
    if (preg_match('/^(?:customer_|seller_|delivery_boy_)?order_(received|processed|shipped|delivered|cancelled|returned)$/', $e, $m)) {
        return $m[1];
    }
    return '';
}

/** Has this number already been texted about this order + status in the last 10 minutes? */
function sms_already_sent_for_order($mobile, $order_ref, $event_or_type)
{
    try {
        $number = mobile_alert_number($mobile);
        $key = sms_status_key($event_or_type);
        if ($number === '' || (string) $order_ref === '' || $key === '') {
            return false;
        }
        $t = &get_instance();
        if (!$t->db->table_exists('notification_mobile_log')) {
            return false;
        }
        $rows = $t->db->select('type')->where(['mobile' => $number, 'type_id' => (string) $order_ref, 'status' => 'sent', 'channel' => 'sms'])
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 600))->get('notification_mobile_log')->result_array();
        foreach ($rows as $r) {
            if (sms_status_key($r['type']) === $key) {
                return true;
            }
        }
    } catch (Throwable $e) {
        log_message('error', 'sms_already_sent_for_order: ' . $e->getMessage());
    }
    return false;
}

/** Remember an SMS sent by the SMS Matrix path, so the Mobile alerts path can see it. */
function sms_record_for_order($mobile, $order_ref, $event, $message)
{
    try {
        $number = mobile_alert_number($mobile);
        if ($number === '' || (string) $order_ref === '' || sms_status_key($event) === '') {
            return;
        }
        $t = &get_instance();
        if (!$t->db->table_exists('notification_mobile_log')) {
            return;
        }
        $t->db->insert('notification_mobile_log', [
            'user_id' => 0, 'channel' => 'sms', 'mobile' => $number, 'type' => mb_substr((string) $event, 0, 64),
            'type_id' => mb_substr((string) $order_ref, 0, 128), 'message_hash' => md5((string) $message),
            'message' => mb_substr((string) $message, 0, 600), 'status' => 'sent', 'error' => null,
        ]);
    } catch (Throwable $e) {
        log_message('error', 'sms_record_for_order: ' . $e->getMessage());
    }
}
