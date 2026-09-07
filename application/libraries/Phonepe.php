<?php
/* 
    1. get_credentials()
    2. create_order($amount,$receipt='')
    3. fetch_payments($id ='')
    4. capture_payment($amount, $id, $currency = "INR")
    5. verify_payment($order_id, $razorpay_payment_id, $razorpay_signature)

    0. curl($url, $method = 'GET', $data = [])
*/
class Phonepe
{
    private $salt_index = "";
    private $salt_key = "";
    private $merchant_id = "";
    private $url = "";
    private $environment = "";
    private $app_id = "";

    function __construct()
    {
        $settings = get_settings('payment_method', true);
        $system_settings = get_settings('system_settings', true);

        $this->salt_index = (isset($settings['phonepe_salt_index'])) ? $settings['phonepe_salt_index'] : " ";
        $this->app_id = (isset($settings['phonepe_app_id'])) ? $settings['phonepe_app_id'] : " ";
        $this->salt_key = (isset($settings['phonepe_salt_key'])) ? $settings['phonepe_salt_key'] : " ";
        $this->merchant_id = (isset($settings['phonepe_marchant_id'])) ? $settings['phonepe_marchant_id'] : " ";
        $this->url = (isset($settings['phonepe_payment_mode']) && $settings['phonepe_payment_mode'] == "live") ? "https://api.phonepe.com/apis/hermes" : "https://api-preprod.phonepe.com/apis/pg-sandbox";
        $this->environment = (isset($settings['phonepe_payment_mode']) && $settings['phonepe_payment_mode'] == "live") ? 'PRODUCTION' : 'UAT';
    }

    public function get_credentials()
    {
        $data['salt_index'] = $this->salt_index;
        $data['salt_key'] = $this->salt_key;
        $data['merchant_id'] = $this->merchant_id;
        $data['url'] = $this->url;
        return $data;
    }

    public function pay($data)
    {
        $data['merchantId'] = $this->merchant_id;
        $data['app_id'] = $this->app_id;
        $data['environment'] = $this->environment;
        $data['paymentInstrument'] = array(
            'type' => 'PAY_PAGE',
        );
        $url = $this->url . '/pg/v1/pay';
        $method = 'POST';

        /** generating a X-VERIFY header */
        $encode = base64_encode(json_encode($data));
        $saltKey = $this->salt_key;
        $saltIndex = $this->salt_index;
        $string = $encode . '/pg/v1/pay' . $saltKey;
        $sha256 = hash('sha256', $string);
        $finalXHeader = $sha256 . '###' . $saltIndex;

        $header = [
            "Content-Type: application/json",
            "accept: application/json",
            "X-VERIFY: $finalXHeader"
        ];
        $response = $this->curl($url, $method, json_encode(['request' => $encode]), $header);
        $res = json_decode($response['body'], true);
        return $res;
    }

    public function phonepe_payment($data)
    {
        $data['merchantId'] = $this->merchant_id;
        $url = $this->url . '/pg/v1/pay';
        $method = 'POST';

        /** generating a X-VERIFY header */
        $encode = base64_encode(json_encode($data));
        $saltKey = $this->salt_key;
        $saltIndex = $this->salt_index;
        $string = $encode . '/pg/v1/pay' . $saltKey;
        $sha256 = hash('sha256', $string);
        $finalXHeader = $sha256 . '###' . $saltIndex;

        $header = [
            "Content-Type: application/json",
            "accept: application/json",
            "X-VERIFY: $finalXHeader"
        ];
        $response = $this->curl($url, $method, json_encode(['request' => $encode]), $header);
        $res = json_decode($response['body'], true);
        return $res;
    }

    public function check_status($id = '')
    {
        $data['merchantId'] = $this->merchant_id;
        $data['paymentInstrument'] = array(
            'type' => 'PAY_PAGE',
        );
        $endpoint = "/pg/v1/status/$this->merchant_id/$id";
        $url = $this->url . $endpoint;
        $method = 'GET';

        /** generating a X-VERIFY header */
        $saltKey = $this->salt_key;
        $saltIndex = $this->salt_index;
        $string = $endpoint . "" . $saltKey;
        $sha256 = hash('sha256', $string);
        $finalXHeader = $sha256 . '###' . $saltIndex;

        $header = [
            "Content-Type: application/json",
            "X-VERIFY: $finalXHeader",
            "X-MERCHANT-ID: $this->merchant_id",
        ];
        $response = $this->curl($url, $method, [], $header);
        $res = json_decode($response['body'], true);
        return $res;
    }

    /**
     * Verify the X-VERIFY header on an inbound PhonePe callback.
     *
     * PhonePe signs the base64 `response` string from the callback body with the
     * merchant salt: sha256(base64_payload + saltKey) + '###' + saltIndex. The
     * webhook handler never checked this header at all, so the only thing it knew
     * about a caller was that they had found the URL.
     *
     * Note this takes the base64 STRING as PhonePe sent it, not the decoded JSON -
     * the digest is over the encoded form.
     *
     * @param  string $base64_response The raw `response` value from the callback body.
     * @param  string $x_verify        Value of the X-VERIFY header.
     * @return bool
     */
    public function verify_callback_signature($base64_response, $x_verify)
    {
        $salt_key = trim((string) $this->salt_key);
        if ($salt_key === '' || $base64_response === '' || empty($x_verify)) {
            return false;
        }

        $expected = hash('sha256', (string) $base64_response . $salt_key) . '###' . trim((string) $this->salt_index);

        return hash_equals($expected, trim((string) $x_verify));
    }

    /**
     * Ask PhonePe what a transaction is really worth and whether it really succeeded.
     *
     * The webhook handler did call check_status(), but then ignored what it returned:
     * it only tested that the call produced SOMETHING truthy, and went on to branch on
     * `$request['code']` and credit `$request['data']['amount']` - both from the
     * request body. So a caller who knew a pending merchantTransactionId could declare
     * it PAYMENT_SUCCESS for an amount of their choosing, and check_status() returning
     * a perfectly ordinary "still pending" response did not stop them.
     *
     * This wraps check_status() so callers get PhonePe's own verdict in a shape that
     * is hard to use wrongly: a boolean, a code, and an amount already converted from
     * paise to rupees.
     *
     * @param  string $txn_id merchantTransactionId.
     * @return array{error: bool, message: string, code: string, amount: float, state: string}
     */
    public function verify_transaction($txn_id)
    {
        $fail = function ($message) {
            return ['error' => true, 'message' => $message, 'code' => '', 'amount' => 0.0, 'state' => ''];
        };

        if (trim((string) $this->salt_key) === '' || trim((string) $this->merchant_id) === '') {
            return $fail('PhonePe credentials are not configured, so a callback cannot be verified.');
        }
        if (empty($txn_id)) {
            return $fail('No merchantTransactionId to verify.');
        }

        $res = $this->check_status($txn_id);
        if (!is_array($res) || !isset($res['code'])) {
            // Refuse rather than guess. PhonePe retries its callbacks, so answering
            // "not now" to a gateway hiccup does not lose a genuine payment - whereas
            // treating an unreadable response as success loses money.
            return $fail('PhonePe status API gave no usable response for ' . $txn_id . '.');
        }

        // PhonePe quotes amounts in paise.
        $amount = isset($res['data']['amount']) ? ((float) $res['data']['amount']) / 100 : 0.0;

        return [
            'error'   => false,
            'message' => 'Verified with PhonePe.',
            'code'    => (string) $res['code'],
            'amount'  => $amount,
            'state'   => isset($res['data']['state']) ? (string) $res['data']['state'] : '',
        ];
    }
    public function curl($url, $method = 'POST', $data = [], $header = [])
    {
        $ch = curl_init();
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $header
        );
        if (strtolower($method) == 'post') {
            $curl_options[CURLOPT_POST] = 1;
            if (!empty($data)) {
                $curl_options[CURLOPT_POSTFIELDS] = $data;
            }
        } else {
            $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }
        curl_setopt_array($ch, $curl_options);
        $result = array(
            'body' => curl_exec($ch),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        );
        return $result;
    }
}
