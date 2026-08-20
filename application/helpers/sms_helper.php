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

    foreach ($sendPhone as $phone_number) {
        $result = send_sms($phone_number, $message);
        if (!empty($result['error']) || empty($result['http_code']) || $result['http_code'] < 200 || $result['http_code'] >= 300) {
            $failed['sms']++;
        } else {
            $sent['sms']++;
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
    return curl_sms($data["base_url"], isset($data["sms_gateway_method"]) ? $data["sms_gateway_method"] : 'GET', $data["body"], $data["header"]);
}

function curl_sms($url, $method = 'GET', $data = [], $headers = [])
{

    $ch = curl_init();
    $curl_options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 0,
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
