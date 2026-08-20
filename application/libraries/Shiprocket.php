<?php
/* 

    1. get_credentials()
    2. create_order($amount,$receipt='')
    3. fetch_payments($id ='')
    4. capture_payment($amount, $id, $currency = "INR")
    5. verify_payment($order_id, $razorpay_payment_id, $razorpay_signature)

    0. curl($url, $method = 'GET', $data = [])
*/
class Shiprocket
{
    /** settings.variable that holds the cached auth token */
    const TOKEN_SETTING = 'shiprocket_auth_token';

    private $email = "";
    private $password = "";
    private $url = "";
    private $token = null;

    /**
     * Details of the most recent transport-level failure, for callers that want to report
     * something better than a generic "request not sent". curl() used to throw the HTTP status
     * and the curl error away entirely, so a 422 validation rejection, an expired token and a
     * dead network all looked identical to every caller.
     */
    private $last_error = null;
    private $last_status = null;

    function __construct()
    {
        $settings = get_settings('shipping_method', true);

        $this->url = "https://apiv2.shiprocket.in/v1/external/";
        $this->email = (isset($settings['email'])) ? $settings['email'] : "";
        $this->password = (isset($settings['password'])) ? $settings['password'] : "";
    }
    public function get_credentials()
    {
        $data['email'] = $this->email;
        $data['password'] = $this->password;
        return $data;
    }

    /**
     * Logs in to Shiprocket and returns a bearer token.
     *
     * Three problems this addresses:
     *
     *  1. The request body was assembled by string concatenation:
     *         '{"email":"' . $this->email . '","password": "' . $this->password . '"}'
     *     A password containing a double quote or a backslash - both perfectly legal, and this
     *     store's password already contains ^ # $ - produces malformed JSON, Shiprocket rejects
     *     the login, and every shipping call then goes out with an empty bearer token. Silently:
     *     see point 3. json_encode() cannot produce that.
     *
     *  2. There was no caching. A Shiprocket token is valid for 10 days, but a fresh instance of
     *     this library is constructed on every HTTP request that ships anything, and the token
     *     lived only on the instance - so the platform re-authenticated constantly. Shiprocket
     *     rate-limits the login endpoint, and once it starts refusing, EVERY shipping operation
     *     fails at once. The token is now cached in the settings table and reused.
     *
     *  3. A failed login returned "" with nothing logged, so a wrong password or a rate-limited
     *     login was indistinguishable from "shipping is broken for unknown reasons".
     */
    public function generate_token($force_refresh = false)
    {
        if (!$force_refresh) {
            $cached = $this->get_cached_token();
            if (!empty($cached)) {
                return $cached;
            }
        }

        if (empty($this->email) || empty($this->password)) {
            log_message('error', 'Shiprocket: no API credentials configured (Admin > Shipping Settings) - shipping calls cannot be authenticated.');
            return "";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apiv2.shiprocket.in/v1/external/auth/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'email'    => $this->email,
                'password' => $this->password,
            ]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $result = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);

        $response = (!empty($result)) ? json_decode($result, true) : "";
        $token = (is_array($response) && isset($response['token'])) ? $response['token'] : "";

        if (empty($token)) {
            log_message('error', 'Shiprocket login failed (http ' . $status . '): '
                . substr((string) ($curl_error !== '' ? $curl_error : $result), 0, 300));
            return "";
        }

        $this->store_token($token);
        return $token;
    }

    /**
     * Cached token, or "" when absent/expired.
     *
     * Kept in `settings` under its own variable rather than a new table: it is a single
     * transient value, and the settings row is already read on nearly every request.
     */
    private function get_cached_token()
    {
        $t = &get_instance();
        $row = $t->db->select('value')->where('variable', self::TOKEN_SETTING)->get('settings')->row_array();
        if (empty($row['value'])) {
            return "";
        }
        $cached = json_decode($row['value'], true);
        if (!is_array($cached) || empty($cached['token']) || empty($cached['expires_at'])) {
            return "";
        }
        // Re-authenticate well before the real 10-day expiry so a token never expires mid-request.
        if ((int) $cached['expires_at'] <= time()) {
            return "";
        }
        // A credential change must invalidate the cache, or the platform keeps using a token
        // issued for the previous account.
        if (!isset($cached['account']) || $cached['account'] !== md5($this->email . '|' . $this->password)) {
            return "";
        }
        return $cached['token'];
    }

    private function store_token($token)
    {
        $t = &get_instance();
        $payload = json_encode([
            'token'      => $token,
            'account'    => md5($this->email . '|' . $this->password),
            'expires_at' => time() + (8 * 24 * 60 * 60), // 8 days; Shiprocket issues 10
        ]);

        $exists = $t->db->select('id')->where('variable', self::TOKEN_SETTING)->get('settings')->row_array();
        if (empty($exists)) {
            $t->db->insert('settings', ['variable' => self::TOKEN_SETTING, 'value' => $payload]);
        } else {
            $t->db->set('value', $payload)->where('variable', self::TOKEN_SETTING)->update('settings');
        }
    }

    public function add_pickup_location($data)
    {
        // firebase server url to send the curl request

        $url = $this->url . 'settings/company/addpickup';
        $result = $this->curl($url, "POST", json_encode($data));
       
        //and return the result 
       
        return $result;
    }

    public function curl($url, $method = 'GET', $data = [], $is_retry = false)
    {
        $this->last_error = null;
        $this->last_status = null;

        if (empty($this->token)) {
            $this->token = $this->generate_token();
        }

        if (empty($this->token)) {
            // Without this the request went out as "Authorization: Bearer " and came back 401,
            // which callers reported to the user as a shipping failure with no explanation.
            $this->last_error = 'Shiprocket authentication failed - check the credentials under Admin > Shipping Settings.';
            log_message('error', 'Shiprocket curl aborted, no auth token available: ' . $url);
            return null;
        }

        $ch = curl_init();
        $headers = array(
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        );
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        );
        if (strtolower($method) == 'post') {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = $data;
        } else {
            $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }
        curl_setopt_array($ch, $curl_options);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $this->last_status = $status;

        // A cached token that Shiprocket has since invalidated returns 401. Re-authenticate once
        // and replay - otherwise every call would fail until the cache expired on its own.
        if ($status === 401 && !$is_retry) {
            log_message('error', 'Shiprocket returned 401 - refreshing the cached token and retrying once: ' . $url);
            $this->token = $this->generate_token(true);
            if (!empty($this->token)) {
                return $this->curl($url, $method, $data, true);
            }
        }

        if ($raw === false) {
            $this->last_error = 'Could not reach Shiprocket: ' . $curl_error;
            log_message('error', 'Shiprocket transport failure on ' . $url . ' -> ' . $curl_error);
            return null;
        }

        $decoded = json_decode($raw, true);

        if ($decoded === null && trim((string) $raw) !== '') {
            // Shiprocket answered with something that is not JSON (an HTML error or maintenance
            // page). Previously json_decode()'s null was returned as-is and callers read array
            // keys off it, producing a cascade of warnings instead of one clear failure.
            $this->last_error = 'Shiprocket returned an unreadable response (http ' . $status . ').';
            log_message('error', 'Shiprocket non-JSON response on ' . $url . ' (http ' . $status . '): ' . substr((string) $raw, 0, 300));
            return null;
        }

        if ($status < 200 || $status > 299) {
            $message = '';
            if (is_array($decoded)) {
                if (!empty($decoded['message'])) {
                    $message = is_string($decoded['message']) ? $decoded['message'] : json_encode($decoded['message']);
                } elseif (!empty($decoded['errors'])) {
                    $message = json_encode($decoded['errors']);
                }
            }
            $this->last_error = ($message !== '') ? $message : ('Shiprocket request failed (http ' . $status . ').');
            log_message('error', 'Shiprocket error on ' . $url . ' (http ' . $status . '): ' . substr((string) $raw, 0, 300));
        }

        return $decoded;
    }

    /** Human-readable reason the last call failed, or null if it did not. */
    public function last_error()
    {
        return $this->last_error;
    }

    /** HTTP status of the last call, or null if the request never went out. */
    public function last_status()
    {
        return $this->last_status;
    }

    public function check_serviceability($data)
    {
        $pickup_location = (isset($data['pickup_postcode']) && !empty($data['pickup_postcode'])) ? $data['pickup_postcode'] : "";
        $delivery_pincode = (isset($data['delivery_postcode']) && !empty($data['delivery_postcode'])) ? $data['delivery_postcode'] : "";
        $weight = (isset($data['weight']) && !empty($data['weight'])) ? $data['weight'] : "";
        $cod = (isset($data['cod']) && !empty($data['cod'])) ? $data['cod'] : 0;

        $query = array(
            "pickup_postcode" => $pickup_location,
            "delivery_postcode" => $delivery_pincode,
            "weight" => $weight,
            "cod" => $cod
        );

        $qry_str = http_build_query($query);

        $url = $this->url . 'courier/serviceability/?' . $qry_str;

        $result = $this->curl($url);
        return $result;
    }

    public function create_order($data)
    {
        // firebase server url to send the curl request
        $url = $this->url . 'orders/create/adhoc';

        //building headers for the request

        $data = json_encode($data);
        $result = $this->curl($url, $method = 'POST', $data);
        return $result;
    }

    /**
     * Books a REVERSE pickup - the courier collects the item from the customer and returns it
     * to the seller's pickup location.
     *
     * This is a distinct Shiprocket endpoint from orders/create/adhoc: the forward endpoint
     * ships seller -> customer, and there is no way to invert it. Without this, approving a
     * return only moved rows in this database - the parcel itself had to be arranged by hand,
     * or a delivery boy assigned, which is not how this platform ships anything else.
     *
     * @param  array $data  see build_shiprocket_return_payload() in function_helper.php
     * @return array decoded Shiprocket response
     */
    public function create_return_order($data)
    {
        $url = $this->url . 'orders/create/return';

        // curl() here already json_decodes the body (unlike the Razorpay library's, which
        // returns ['body','http_code']) - so this returns the decoded response directly, the
        // same shape create_order() gives its callers. A transport failure yields a non-array,
        // which callers must treat as a failure rather than reading keys off it.
        return $this->curl($url, 'POST', json_encode($data));
    }

    public function generate_awb($shipment_id, $courier_id = null)
    {
        $url = $this->url . 'courier/assign/awb';
        $data = array(
            'shipment_id' => $shipment_id,
        );
        // Ask Shiprocket for the courier this shipment was actually rated and picked
        // with. Without it Shiprocket falls back to its own default courier, so the
        // courier the platform recommended (and quoted the delivery charge from) was
        // never the one that carried the parcel.
        if (!empty($courier_id)) {
            $data['courier_id'] = $courier_id;
        }
        $result = $this->curl($url, "POST", json_encode($data));

        return $result;
    }

    public function request_for_pickup($shipment_id)
    {
        // firebase server url to send the curl request
        $url = $this->url . 'courier/generate/pickup';

        $shipment_id = array('shipment_id' => $shipment_id);
        $result = $this->curl($url, "POST", json_encode($shipment_id));

        //and return the result 
        return $result;
    }

    public function generate_manifests($shipment_id)
    {
        $url = $this->url . 'manifests/generate';
        $data = array(
            'shipment_id' => $shipment_id
        );
        $result = $this->curl($url, 'POST', json_encode($data));
        return $result;
    }

    public function generate_label($shipment_id)
    {
        $url = $this->url . 'courier/generate/label';
        $data = array(
            'shipment_id' => [$shipment_id]
        );
        $result = $this->curl($url, 'POST', json_encode($data));
        return $result;
    }
    public function generate_invoice($order_id)
    {
        $url = $this->url . 'orders/print/invoice';
        $data = array(
            'ids' => [$order_id]
        );
        $result = $this->curl($url, 'POST', json_encode($data));
        return $result;
    }
    public function get_order($shipment_id)
    {
        // firebase server url to send the curl request

        // print_r($data);
        $url = $this->url . 'shipments/' . $shipment_id;
        $result = $this->curl($url);

        //and return the result 
        return $result;
    }
    public function get_specific_order($order_id)
    {
        // firebase server url to send the curl request

        // print_r($data);
        $url = $this->url . 'orders/show/' . $order_id;
        $result = $this->curl($url);

        //and return the result 
        return $result;
    }
    /**
     * Live tracking for a shipment.
     *
     * Was calling `shipments/{id}` - the same endpoint get_order() already uses. That returns the
     * shipment RECORD and carries no scan history, so "track" and "get" were two names for one
     * call and there was no way to see where a parcel actually is.
     * `courier/track/shipment/{id}` is the tracking endpoint: it returns the activity timeline
     * and the current status.
     */
    public function track_order($shipment_id)
    {
        $url = $this->url . 'courier/track/shipment/' . $shipment_id;
        return $this->curl($url, "GET");
    }

    /**
     * Cancels a forward order.
     *
     * The parameter was named $shipment_id, but `orders/cancel` takes SHIPROCKET ORDER ids -
     * a different identifier that this app stores in its own column
     * (order_tracking.shiprocket_order_id). The single caller, cancel_shiprocket_order() in
     * function_helper.php, does pass the order id, so the behaviour was right and only the name
     * was misleading - which is exactly how the wrong id gets passed by the next person.
     */
    public function cancel_order($shiprocket_order_id)
    {
        $url = $this->url . 'orders/cancel';
        $data = array(
            'ids' => [$shiprocket_order_id]
        );
        return $this->curl($url, "POST", json_encode($data));
    }

    /**
     * Cancels a shipment that has already had an AWB assigned.
     *
     * Distinct endpoint from orders/cancel: once a courier has been assigned, Shiprocket wants
     * the shipment cancelled, and cancelling the order alone leaves the AWB live. There was no
     * way to do this from here at all.
     */
    public function cancel_shipment($awb_codes)
    {
        $url = $this->url . 'orders/cancel/shipment/awbs';
        $data = array(
            'awbs' => is_array($awb_codes) ? array_values($awb_codes) : [$awb_codes]
        );
        return $this->curl($url, "POST", json_encode($data));
    }
}
