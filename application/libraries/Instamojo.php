<?php
/* 
    1. get_credentials()
    2. create_order($amount,$receipt='')
    3. fetch_payments($id ='')
    4. capture_payment($amount, $id, $currency = "INR")
    5. verify_payment($order_id, $razorpay_payment_id, $razorpay_signature)

    0. curl($url, $method = 'GET', $data = [])
*/
class Instamojo
{
    private $client_id = "";
    private $client_secret = "";
    private $url = "";

    function __construct()
    {
        $settings = get_settings('payment_method', true);
        $system_settings = get_settings('system_settings', true);

        $this->client_id = (isset($settings['instamojo_client_id'])) ? $settings['instamojo_client_id'] : "";
        $this->client_secret = (isset($settings['instamojo_client_secret'])) ? $settings['instamojo_client_secret'] : "";
        $this->url = (isset($settings['instamojo_payment_mode']) && $settings['instamojo_payment_mode'] == "sandbox") ? 'https://test.instamojo.com/' : 'https://www.instamojo.com/';
    }
    public function generate_token()
    {

        $client_id = $this->client_id;
        $client_secret = $this->client_secret;
        $url = $this->url . 'oauth2/token/';
        $method = 'POST';
        $payload = [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ];

        $ch = curl_init();
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
        );
        if (strtolower($method) == 'post') {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($payload);
        } else {
            $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }
        curl_setopt_array($ch, $curl_options);
        $result = array(
            'body' => curl_exec($ch),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        );
        $data = json_decode($result['body'], true);
        $token = $data['access_token'];
        return $token;
    }
    public function payment_requests($data)
    {
        $url = $this->url . 'v2/payment_requests/';
        $method = 'POST';
        $payload = [
            'purpose' => $data['purpose'],
            'amount' => $data['amount'],
            'buyer_name' => $data['buyer_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            // 'redirect_url' => $data['redirect_url'],
            'webhook' => base_url('admin/webhook/instamojo_webhook'),
            'allow_repeated_payments' => 'False',
        ];

       
        $response = $this->curl($url, $method, $payload);
        $res = json_decode($response['body'],true);
        $res['http_code'] = $response['http_code'];
        return $res;
    }

    public function payment_requests_detail($id)
    {
        $url = $this->url . 'v2/payment_requests/'.$id.'/';

        $response = $this->curl($url);
        // $res = json_decode($response['body'], true);
        return $response;
    }

    /**
     * Ask Instamojo what actually happened, instead of believing the webhook body.
     *
     * The webhook handler used to read `status`, `amount` and `purpose` straight out
     * of $_POST with no verification of any kind - no signature, no `mac`, no
     * callback to Instamojo - and then credited a wallet with the posted figure. A
     * single unauthenticated POST was therefore worth an arbitrary amount of store
     * credit.
     *
     * Instamojo does offer a `mac` HMAC on webhooks, but its key is the account's
     * PRIVATE SALT, which this installation has never stored (payment settings hold
     * only client id and client secret). Rather than add a setting that an operator
     * has to find and paste correctly for payments to stay secure, this verifies out
     * of band: fetch the payment request over the authenticated v2 API using the
     * client credentials we already hold, find the individual payment inside it, and
     * return Instamojo's own status and amount. An attacker can forge a body; they
     * cannot forge what Instamojo's API says back to us over our own OAuth token.
     *
     * This is the stronger of the two checks anyway - a valid `mac` proves the message
     * came from Instamojo, while this proves the payment exists and is worth what it
     * claims.
     *
     * @param  string $payment_request_id From the webhook's `payment_request_id`.
     * @param  string $payment_id         From the webhook's `payment_id`.
     * @return array{error: bool, message: string, status: string, amount: string, purpose: string}
     */
    public function verify_webhook_payment($payment_request_id, $payment_id)
    {
        $fail = function ($message) {
            return ['error' => true, 'message' => $message, 'status' => '', 'amount' => '', 'purpose' => ''];
        };

        if (empty($this->client_id) || empty($this->client_secret)) {
            return $fail('Instamojo credentials are not configured, so a webhook cannot be verified.');
        }
        if (empty($payment_request_id) || empty($payment_id)) {
            return $fail('Webhook did not carry both payment_request_id and payment_id.');
        }

        $response = $this->payment_requests_detail($payment_request_id);
        if (!isset($response['http_code']) || (int) $response['http_code'] !== 200) {
            // Refuse rather than assume. A gateway outage must not become a window in
            // which unverified webhooks are accepted; Instamojo retries its webhooks,
            // so a genuine payment is not lost by answering "not now".
            return $fail('Instamojo API did not confirm the payment request (http '
                . (isset($response['http_code']) ? $response['http_code'] : 'none') . ').');
        }

        $body = json_decode(isset($response['body']) ? $response['body'] : '', true);
        if (!is_array($body) || empty($body['payment_request'])) {
            return $fail('Instamojo API response could not be parsed.');
        }

        $request = $body['payment_request'];
        $payments = isset($request['payments']) && is_array($request['payments']) ? $request['payments'] : [];

        // Match on the payment id the webhook named. A payment request can hold more
        // than one payment, and taking the first one would let a forged body about a
        // cheap payment be credited as an expensive one from the same request.
        foreach ($payments as $payment) {
            if (!isset($payment['payment_id']) || !hash_equals((string) $payment['payment_id'], (string) $payment_id)) {
                continue;
            }

            return [
                'error'   => false,
                'message' => 'Verified with Instamojo.',
                // Instamojo's own words, not the caller's.
                'status'  => isset($payment['status']) ? (string) $payment['status'] : '',
                'amount'  => isset($payment['amount']) ? (string) $payment['amount'] : '',
                'purpose' => isset($request['purpose']) ? (string) $request['purpose'] : '',
            ];
        }

        return $fail('Instamojo has no payment ' . $payment_id . ' on request ' . $payment_request_id . '.');
    }

    public function curl($url, $method = 'GET', $data = [])
    {
        $token = $this->generate_token();

        $ch = curl_init();
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token
            )
        );
        if (strtolower($method) == 'post') {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
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
