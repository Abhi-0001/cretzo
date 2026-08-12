<?php
defined('BASEPATH') or exit('No direct script access allowed');

function parseSmsString($string, $data = [])
{

    foreach ($data as $key => $val) {
        // echo ($key).": " . $val;
        if ($val != null) {
            $string = str_replace("{" . $key . "}", $val, $string);
        }else{
            $string = str_replace("{" . $key . "}", "NULL", $string);
        }
    }
    return $string;
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
        return [
            "error" => true,
            "message" => "Template not found."
        ];
    }

    $title = parseSmsString($template[0]["title"], $data);
    $message = parseSmsString($template[0]["message"], $data);

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



    foreach ($sendPhone as $phone) {
        if ($phone != "") {
            (send_sms($phone, $message));
            // print_r((send_sms($phone, $message)));
        }
    }
    foreach ($sendEmail as $email) {
        if ($email != "") {
            (send_mail($email, $title, $message));
            // print_r((send_mail($email, $title, $message)));
        }
    }

    // print_r($message);
    return [];
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

    $result = array(
        'body' => json_decode(curl_exec($ch), true),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
    );
    return $result;
}
