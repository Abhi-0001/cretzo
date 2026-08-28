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

    /** settings.variable holding "do not attempt a login before this time" */
    const LOGIN_COOLDOWN_SETTING = 'shiprocket_login_cooldown';

    /** How long to stop trying after Shiprocket refuses a login. */
    const LOGIN_COOLDOWN_SECONDS = 900;

    /**
     * Whether a forced re-authentication has already been tried in this PHP process.
     *
     * Static, not per-instance: several libraries' worth of Shiprocket calls can happen in one
     * request (a checkout books a parcel per seller), each on its own instance, and the point is
     * to bound the number of LOGINS per request - see the long note in curl().
     */
    private static $refresh_attempted = false;

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

        /*
         * Refuse to log in at all for a while after Shiprocket has rejected a login.
         *
         * Shiprocket does not merely rate-limit auth/login, it BLOCKS the API user: "User blocked
         * due to too many failed login attempts". Once that happens nothing can authenticate, so
         * a wrong password does not just break order creation - it takes serviceability, tracking
         * and pickup lookups down with it, and the storefront starts telling customers their
         * address is not deliverable.
         *
         * That is not hypothetical: on 2026-08-26 the stored password was rejected ("Invalid
         * email and password combination"), every checkout retried the login, and the user was
         * blocked. A cooldown keeps a bad password a shipping problem instead of an outage.
         */
        $cooldown_until = $this->login_cooldown_until();
        if ($cooldown_until > time()) {
            log_message('error', 'Shiprocket login suppressed for another ' . ($cooldown_until - time())
                . 's - the last attempt was refused. Repeating it risks Shiprocket blocking the API user'
                . ' outright, which disables tracking and serviceability too.');
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
            $this->start_login_cooldown();
            return "";
        }

        $this->clear_login_cooldown();
        $this->store_token($token);
        return $token;
    }

    /** Unix time before which no login should be attempted, or 0. */
    private function login_cooldown_until()
    {
        $t = &get_instance();
        $row = $t->db->select('value')->where('variable', self::LOGIN_COOLDOWN_SETTING)->get('settings')->row_array();
        return empty($row['value']) ? 0 : (int) $row['value'];
    }

    private function start_login_cooldown()
    {
        $this->write_setting(self::LOGIN_COOLDOWN_SETTING, (string) (time() + self::LOGIN_COOLDOWN_SECONDS));
    }

    private function clear_login_cooldown()
    {
        if ($this->login_cooldown_until() > 0) {
            $this->write_setting(self::LOGIN_COOLDOWN_SETTING, '0');
        }
    }

    /** Upsert one settings row. Same shape store_token() uses. */
    private function write_setting($variable, $value)
    {
        $t = &get_instance();
        $exists = $t->db->select('id')->where('variable', $variable)->get('settings')->row_array();
        if (empty($exists)) {
            settings_write_done($t->db->insert('settings', ['variable' => $variable, 'value' => $value]));
        } else {
            settings_write_done($t->db->set('value', $value)->where('variable', $variable)->update('settings'));
        }
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
            settings_write_done($t->db->insert('settings', ['variable' => self::TOKEN_SETTING, 'value' => $payload]));
        } else {
            settings_write_done($t->db->set('value', $payload)->where('variable', self::TOKEN_SETTING)->update('settings'));
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

    /**
     * Lists the pickup addresses registered on the Shiprocket account.
     *
     * The library could only ever PUSH a pickup location (add_pickup_location), so the local
     * `pickup_locations` table had to be filled in by hand, retyping addresses that Shiprocket
     * already holds - and a typo in the pincode there is invisible until a customer is told their
     * address is not serviceable. This is the read side, so the platform can take them from
     * Shiprocket instead of asking someone to key them in again.
     *
     * @return array decoded Shiprocket response; the addresses live under
     *               ['data']['shipping_address'].
     */
    public function get_pickup_locations()
    {
        return $this->curl($this->url . 'settings/company/pickup');
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

        /*
         * Shiprocket answers {"message":"token_expired","status_code":401} for TWO completely
         * different situations, and nothing in the body distinguishes them:
         *
         *   1. the bearer token really has expired or been invalidated, and
         *   2. the API user has no access to the module that endpoint belongs to.
         *
         * (2) is the live state of this account, verified against it on 2026-08-27: one and the
         * same cached token returns 200 on settings/company/pickup, courier/serviceability,
         * orders/show/{id} and courier/track/*, and 401 "token_expired" on orders/create/adhoc
         * and shipments/{id}. The token is fine; the API user is not permitted those modules.
         *
         * Re-authenticating cannot fix (2), and refreshing unconditionally turned every such
         * call into a fresh login - one per checkout, one per candidate in the freight cron, one
         * per render of the order edit page. Shiprocket does not just throttle auth/login, it
         * blocks the API user ("User blocked due to too many failed login attempts"), and a
         * blocked user cannot authenticate for anything: tracking and serviceability go down
         * with order creation. That is precisely the sequence in the logs for 2026-08-26.
         *
         * So: at most ONE forced re-authentication per PHP process. A genuinely invalidated
         * token is still recovered, while a permission-blocked endpoint costs one login instead
         * of one per call.
         */
        if ($status === 401 && !$is_retry) {
            if (self::$refresh_attempted) {
                log_message('error', 'Shiprocket 401 on ' . $url . ' - a re-authentication was already attempted in'
                    . ' this request, so not logging in again. If the token works elsewhere this is a module'
                    . ' permission on the API user, not an expiry.');
            } else {
                self::$refresh_attempted = true;
                log_message('error', 'Shiprocket returned 401 - refreshing the cached token and retrying once: ' . $url);
                $refreshed = $this->generate_token(true);
                if (!empty($refreshed)) {
                    $this->token = $refreshed;
                    return $this->curl($url, $method, $data, true);
                }
                // Deliberately keep the token we already have. A failed login leaves the cached
                // token untouched, and here that token demonstrably still works on the endpoints
                // the API user IS permitted - discarding it would break those too.
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
                    $message = $this->flatten_message($decoded['message']);
                } elseif (!empty($decoded['errors'])) {
                    $message = $this->flatten_message($decoded['errors']);
                }
            }
            $this->last_error = ($message !== '') ? $message : ('Shiprocket request failed (http ' . $status . ').');

            /*
             * "token_expired" is what Shiprocket says; it is not usually what is wrong. Passing it
             * straight through sent sellers and admins chasing an expiry that had not happened -
             * the token is still valid on every endpoint the API user is permitted. Say what the
             * caller can actually act on, and keep Shiprocket's own wording alongside it.
             */
            if ($status === 429) {
                // Documented: "You have exceeded the API call rate limit." Distinct from a
                // rejection - the request was never evaluated, so a caller that reports this as
                // "Shiprocket refused the shipment" sends someone looking for a fault in the
                // parcel instead of retrying.
                $this->last_error = 'Shiprocket is rate-limiting this account (HTTP 429). The request was not'
                    . ' processed - retry shortly.';
            } elseif ($status === 401) {
                $this->last_error = 'Shiprocket rejected the request as unauthorised (it reports "'
                    . ($message !== '' ? $message : 'unauthorised')
                    . '"). If shipping works elsewhere the token is valid and the API user is not permitted this'
                    . ' module - check "Modules to Access" on the API user under Shiprocket > Settings > API.'
                    . ' Otherwise re-enter the API user credentials under Admin > Shipping Settings.';
            }

            log_message('error', 'Shiprocket error on ' . $url . ' (http ' . $status . '): ' . substr((string) $raw, 0, 300));
        }

        return $decoded;
    }

    /**
     * Shiprocket reports validation failures as {"field": ["sentence", ...]}. json_encode()-ing
     * that put raw JSON in front of the seller ({"pickup_location":["Address name already
     * exists..."]}), so flatten it to the sentences only.
     */
    private function flatten_message($message)
    {
        if (is_string($message)) {
            return trim($message);
        }
        if (!is_array($message)) {
            return '';
        }
        $parts = [];
        array_walk_recursive($message, function ($value) use (&$parts) {
            if (is_string($value) && trim($value) !== '') {
                $parts[] = trim($value);
            }
        });
        return implode(' ', array_unique($parts));
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
        $cod = (isset($data['cod']) && !empty($data['cod'])) ? $data['cod'] : 0;

        /*
         * A zero weight has to become the nominal weight HERE, not at the call sites.
         *
         * `!empty($data['weight'])` turned a weight of 0 into the empty string, Shiprocket
         * answered "Weight Required" with no couriers at all, and the storefront rendered that as
         * "not deliverable on the selected address". Most product variants on this store carry
         * weight 0 - the shipping fields were added to the product form after the catalogue was
         * entered - so that applied to almost the whole shop.
         *
         * Three call sites still pass a raw variant weight straight through (the product page's
         * deliverability check and two in the mobile API), which is exactly why the floor belongs
         * in the library: every caller is protected, including the next one. The forward-booking
         * paths already floor it via shiprocket_parcel_weight(); this is the same constant, and
         * flooring an already-floored value is a no-op.
         */
        $weight = (isset($data['weight'])) ? (float) $data['weight'] : 0;
        if ($weight <= 0) {
            $weight = defined('SHIPROCKET_NOMINAL_WEIGHT_KG') ? SHIPROCKET_NOMINAL_WEIGHT_KG : 0.5;
        }

        // Without both pincodes the answer is meaningless, and Shiprocket charges the account an
        // API call to say so. Fail locally with something a caller can report.
        if ($pickup_location === "" || $delivery_pincode === "") {
            $this->last_error = 'A pickup pincode and a delivery pincode are both required to check serviceability.';
            $this->last_status = null;
            log_message('error', 'Shiprocket serviceability skipped - pickup="' . $pickup_location
                . '" delivery="' . $delivery_pincode . '".');
            return null;
        }

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

    /**
     * Step 8 of Shiprocket's documented flow - returns the PDF URL for a generated manifest.
     *
     * This was the one step of the whole documented sequence with no implementation at all.
     * `manifests/generate` (step 7) existed but had no callers, and without `manifests/print`
     * there was nothing that could produce a manifest URL - so `order_tracking.manifest_url` was
     * written as '' at shipment creation and never updated, while the seller app happily reads
     * that column and offers it alongside the label and invoice. Sellers had a permanently empty
     * manifest link.
     *
     * NOT the same identifier as generate_manifests(), which is the trap this pair sets: step 7
     * posts `shipment_id`, step 8 posts `order_ids`, and those are two different numbers in
     * Shiprocket. This method was being handed shipment ids under the `order_ids` key by its only
     * caller, so even once the account can reach the endpoint it would have manifested the wrong
     * records (or nothing). Callers must pass order_tracking.shiprocket_order_id here.
     *
     * Shiprocket returns {"manifest_url": "..."}.
     *
     * @param int|array $shiprocket_order_ids SHIPROCKET order ids, not shipment ids.
     */
    public function print_manifest($shiprocket_order_ids)
    {
        $url = $this->url . 'manifests/print';
        $data = array(
            'order_ids' => is_array($shiprocket_order_ids) ? array_values($shiprocket_order_ids) : [$shiprocket_order_ids]
        );
        return $this->curl($url, 'POST', json_encode($data));
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
     * Step 11 of Shiprocket's documented flow - tracking by AWB rather than by shipment id.
     *
     * The AWB is the courier's own consignment number and is what the scan history is actually
     * filed under; `courier/track/shipment/{id}` resolves to the same data only while Shiprocket's
     * shipment record still points at the right AWB. When a shipment is re-assigned to a different
     * courier the AWB changes and tracking by shipment id can return the previous courier's
     * timeline. Prefer this whenever an AWB is known - order_tracking.awb_code stores it.
     */
    public function track_awb($awb_code)
    {
        $url = $this->url . 'courier/track/awb/' . rawurlencode((string) $awb_code);
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
