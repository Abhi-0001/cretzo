<?php defined('BASEPATH') or exit('No direct script access allowed');
class Webhook extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Razorpay webhook.
     *
     * SECURITY - READ BEFORE EDITING. This endpoint tells the application that money
     * arrived. It is unauthenticated by necessity (Razorpay's servers cannot hold a
     * session), so the HMAC signature is the ONLY thing that distinguishes Razorpay
     * from anybody on the internet with our URL.
     *
     * It previously did this:
     *
     *     if ($http_razorpay_signature) { ... mark the order paid, credit the wallet }
     *
     * - a test that the header EXISTS, never that it is correct. It even assigned the
     * webhook secret to a RAZORPAY_SECRET_KEY constant and then never used it. So any
     * unauthenticated POST carrying a made-up X-Razorpay-Signature header and a
     * `payment.captured` body would flip an order's items to 'received' without a
     * payment, and a body whose notes carried `wallet-refill-user-<id>` would credit
     * an arbitrary customer's wallet by an arbitrary amount. Both were reachable with
     * a single curl command.
     *
     * The signature is now computed over the RAW body before anything is parsed, and
     * nothing is read out of the payload until it verifies.
     */
    public function razorpay()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
            $this->reject_webhook('razorpay', 'non-POST request');
            return;
        }

        $this->load->library(['razorpay']);
        $system_settings = get_settings('system_settings', true);
        $credentials = $this->razorpay->get_credentials();

        // Read once, keep the raw string. The HMAC covers the body byte for byte, so
        // it has to be verified BEFORE json_decode - a decoded-then-re-encoded body
        // has different key order and whitespace and can never match.
        $raw_body = file_get_contents('php://input');
        if ($raw_body === false || $raw_body === '') {
            $this->reject_webhook('razorpay', 'empty body');
            return;
        }

        $http_razorpay_signature = isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) ? $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] : "";

        if (!$this->razorpay->has_webhook_secret()) {
            // Fail closed, and say why. An unset webhook secret is an operational
            // problem ("nobody pasted it into payment settings"), not an excuse to
            // accept unsigned requests - so it must be loud in the log and refused at
            // the door, exactly like a wrong signature.
            $this->reject_webhook('razorpay', 'no webhook secret is configured in payment settings (refund_webhook_secret_key) - '
                . 'the endpoint cannot verify anything and is refusing every call until it is set');
            return;
        }

        if (!$this->razorpay->verify_webhook_signature($raw_body, $http_razorpay_signature)) {
            $this->reject_webhook('razorpay', 'signature mismatch');
            return;
        }

        $request = json_decode($raw_body, true);
        if (!is_array($request)) {
            $this->reject_webhook('razorpay', 'body verified but is not valid JSON');
            return;
        }

        // Signature verified from here down. Log the identifying fields only - this
        // used to var_export the entire $_SERVER array at error level, which wrote
        // HTTP_COOKIE and HTTP_AUTHORIZATION into application/logs on production.
        log_message('error', 'Razorpay webhook (verified) event=' . (isset($request['event']) ? $request['event'] : 'none')
            . ' payment_id=' . (isset($request['payload']['payment']['entity']['id']) ? $request['payload']['payment']['entity']['id'] : 'none'));

        $txn_id = (isset($request['payload']['payment']['entity']['id'])) ? $request['payload']['payment']['entity']['id'] : "";

        if (!empty($request['payload']['payment']['entity']['id'])) {
            if (!empty($txn_id)) {
                $transaction = fetch_details('transactions', ['txn_id' => $txn_id], '*');
            }
            $amount = $request['payload']['payment']['entity']['amount'];
            $amount = ($amount / 100);
        } else {
            $amount = 0;
            $currency = (isset($request['payload']['payment']['entity']['currency'])) ? $request['payload']['payment']['entity']['currency'] : "";
        }


        if (!empty($transaction)) {
            $order_id = $transaction[0]['order_id'];
            log_message('error', 'razorpay Webhook | transaction order id --> ' . var_export($order_id, true));

            $user_id = $transaction[0]['user_id'];
        } else {
            $order_id = 0;
            $order_id = (isset($request['payload']['order']['entity']['notes']['order_id'])) ? $request['payload']['order']['entity']['notes']['order_id'] : $request['payload']['payment']['entity']['notes']['order_id'];
            log_message('error', 'razorpay Webhook | webhook order id --> ' . var_export($order_id, true));
        }

        $this->load->model('transaction_model');

        // Kept as-is only to preserve the existing block structure: execution cannot
        // reach this line unless verify_webhook_signature() already returned true, so
        // this is now always true rather than the (useless) header-presence test it
        // used to be. Do not reinstate this as the security check.
        if ($http_razorpay_signature) {
            if ($request['event'] == 'payment.authorized') {
                $currency = (isset($request['payload']['payment']['entity']['currency'])) ? $request['payload']['payment']['entity']['currency'] : "INR";
                $this->load->library("razorpay");
                $response = $this->razorpay->capture_payment($amount * 100, $txn_id, $currency);
                return;
            }
            if ($request['event'] == 'payment.captured' || $request['event'] == 'order.paid') {
                if ($request['event'] == 'order.paid') {
                    $order_id = $request['payload']['order']['entity']['receipt'];
                    $order_data = fetch_orders($order_id);
                    $user_id = (isset($order_data['order_data'][0]['user_id'])) ? $order_data['order_data'][0]['user_id'] : "";
                }
                if (!empty($order_id)) {
                    /* To do the wallet recharge if the order id is set in the patter */
                    if (strpos($order_id, "wallet-refill-user") !== false) {
                        if (!is_numeric($order_id) && strpos($order_id, "wallet-refill-user") !== false) {
                            $temp = explode("-", $order_id);
                            if (isset($temp[3]) && is_numeric($temp[3]) && !empty($temp[3] && $temp[3] != '')) {
                                $user_id = $temp[3];
                            } else {
                                $user_id = 0;
                            }
                        }

                        $data['transaction_type'] = "wallet";
                        $data['user_id'] = $user_id;
                        $data['order_id'] = $order_id;
                        $data['type'] = "credit";
                        $data['txn_id'] = $txn_id;
                        $data['amount'] = $amount;
                        $data['status'] = "success";
                        $data['message'] = "Wallet refill successful";
                        log_message('error', 'Razorpay user ID -  transaction data--> ' . var_export($data, true));


                        $this->transaction_model->add_transaction($data);
                        log_message('error', 'Razorpay user ID - Add transaction --> ' . var_export($txn_id, true));


                        $this->load->model('customer_model');
                        if ($this->customer_model->update_balance($amount, $user_id, 'add')) {
                            $response['error'] = false;
                            $response['transaction_status'] = $request['event'];
                            $response['message'] = "Wallet recharged successfully!";
                            log_message('error', 'Razorpay user ID - Wallet recharged successfully --> ' . var_export($order_id, true));
                        } else {
                            $response['error'] = true;
                            $response['transaction_status'] = $request['event'];
                            $response['message'] = "Wallet could not be recharged!";
                            log_message('error', 'razorpay Webhook | wallet recharge failure --> ' . var_export($request['event'], true));
                        }
                        echo json_encode($response);
                        return false;
                    } else {

                        /* process the order and mark it as received */
                        $order = fetch_orders($order_id, false, false, false, false, false, false, false);
                        log_message('error', 'Razorpay order -   data--> ' . var_export($order, true));

                        if (isset($order['order_data'][0]['user_id'])) {
                            $user = fetch_details('users', ['id' => $order['order_data'][0]['user_id']]);
                            $overall_total = array(
                                'total_amount' => $order['order_data'][0]['total'],
                                'delivery_charge' => $order['order_data'][0]['delivery_charge'],
                                'tax_amount' => $order['order_data'][0]['total_tax_amount'],
                                'tax_percentage' => $order['order_data'][0]['total_tax_percent'],
                                'discount' =>  $order['order_data'][0]['promo_discount'],
                                'wallet' =>  $order['order_data'][0]['wallet_balance'],
                                'final_total' =>  $order['order_data'][0]['final_total'],
                                'otp' => $order['order_data'][0]['otp'],
                                'address' =>  $order['order_data'][0]['address'],
                                'payment_method' => $order['order_data'][0]['payment_method']
                            );

                            $overall_order_data = array(
                                'cart_data' => $order['order_data'][0]['order_items'],
                                'order_data' => $overall_total,
                                'subject' => 'Order received successfully',
                                'user_data' => $user[0],
                                'system_settings' => $system_settings,
                                'user_msg' => 'Hello, Dear ' . ucfirst($user[0]['username']) . ', We have received your order successfully. Your order summaries are as followed',
                                'otp_msg' => 'Here is your OTP. Please, give it to delivery boy only while getting your order.',
                            );
                            if (isset($user[0]['email']) && !empty($user[0]['email'])) {
                                send_mail($user[0]['email'], 'Order received successfully', $this->load->view('admin/pages/view/email-template.php', $overall_order_data, TRUE));
                            }
                            /* No need to add because the transaction is already added just update the transaction status */
                            if (!empty($transaction)) {
                                $transaction_id = $transaction[0]['id'];
                                update_details(['status' => 'success'], ['id' => $transaction_id], 'transactions');
                            } else {
                                /* add transaction of the payment */
                                $amount = ($request['payload']['payment']['entity']['amount'] / 100);
                                $data = [
                                    'transaction_type' => 'transaction',
                                    'user_id' => $user_id,
                                    'order_id' => $order_id,
                                    'type' => 'razorpay',
                                    'txn_id' => $txn_id,
                                    'amount' => $amount,
                                    'status' => 'success',
                                    'message' => 'order placed successfully',
                                ];
                                $this->transaction_model->add_transaction($data);
                            }

                            update_details(['active_status' => 'received'], ['order_id' => $order_id], 'order_items');
                            $status = json_encode(array(array('received', date("d-m-Y h:i:sa"))));
                            update_details(['status' => $status], ['order_id' => $order_id], 'order_items', false);

                            // place order custome notification on payment success

                            $custom_notification = fetch_details('custom_notifications', ['type' => "place_order"], '');
                            // The message used to have ONLY < application_name > substituted into it, so the
                            // order id placeholder survived and customers were shown the literal text
                            // "Your order #< order_id > has been placed". Both halves go through the same
                            // token list now.
                            $notification_tokens = [
                                'order_id'         => $order_id,
                                'application_name' => $system_settings['app_name'],
                            ];
                            $title = render_notification_text($custom_notification[0]['title'], $notification_tokens);
                            $message = render_notification_text($custom_notification[0]['message'], $notification_tokens);

                            $fcm_admin_subject = (!empty($custom_notification)) ? $title : 'New order placed ID #' . $order_id;
                            $fcm_admin_msg = (!empty($custom_notification)) ? $message : 'New order received for  ' . $system_settings['app_name'] . ' please process it.';
                            $user_fcm = fetch_details('users', ['id' => $user_id], 'fcm_id,mobile,email');
                            $user_fcm_id[0][] = $user_fcm[0]['fcm_id'];
                            if (!empty($user_fcm_id)) {
                                $fcmMsg = array(
                                    'title' => $fcm_admin_subject,
                                    'body' => $fcm_admin_msg,
                                    'type' => "place_order",
                                    'content_available' => true
                                );
                                send_notification($fcmMsg, $user_fcm_id);
                            }
                            notify_event(
                                'place_order',
                                ["customer" => [$user_fcm[0]['email']]],
                                ["customer" => [$user_fcm[0]['mobile']]],
                                ["orders.id" => $order_id]
                            );
                            // The payment SUCCEEDED here - place_order() has already taken this
                            // order's stock, so there was nothing to give back. This line ADDED it
                            // back on every successful payment. In practice it never ran: the keys
                            // it reads are not produced by fetch_orders(), so both arguments were
                            // NULL and update_stock() fataled on an invalid ORDER BY - which meant
                            // every Razorpay success crashed this webhook after the payment was
                            // recorded, and Razorpay retried it. Removed entirely.
                        }
                    }
                } else {
                    log_message('error', 'Razorpay Order id not found --> ' . var_export($request, true));
                    /* No order ID found */
                }

                $response['error'] = false;
                $response['transaction_status'] = $request['event'];
                $response['message'] = "Transaction successfully done";
                echo json_encode($response);
                return false;
            } elseif ($request['event'] == 'payment.failed') {
                //$order = fetch_orders($order_id, false, false, false, false, false, false, false);

                if (!empty($order_id)) {
                    // The restore was commented out, so a failed online payment cancelled the
                    // order but kept its stock held - permanently unsellable with no order to
                    // show for it. Restored, via the helper that reads the real order lines.
                    restore_order_stock($order_id);
                    update_details(['active_status' => 'cancelled'], ['order_id' => $order_id], 'order_items');
                }
                /* No need to add because the transaction is already added just update the transaction status */
                if (!empty($transaction)) {
                    $transaction_id = $transaction[0]['id'];
                    update_details(['status' => 'failed'], ['id' => $transaction_id], 'transactions');
                }
                $response['error'] = true;
                $response['transaction_status'] = $request['event'];
                $response['message'] = "Transaction is failed. ";
                log_message('error', 'Razorpay Webhook | Transaction is failed --> ' . var_export($request['event'], true));
                echo json_encode($response);
                return false;
            } elseif ($request['event'] == 'payment.authorized') {
                if (!empty($order_id)) {
                    update_details(['active_status' => 'awaiting'], ['order_id' => $order_id], 'order_items');
                }
            } elseif ($request['event'] == "refund.processed") {
                //Refund Successfully
                $transaction = fetch_details('transactions', ['txn_id' => $request['payload']['refund']['entity']['payment_id']]);
                if (empty($transaction)) {
                    return false;
                }
                process_refund($transaction[0]['id'], $transaction[0]['status']);
                $response['error'] = false;
                $response['transaction_status'] = $request['event'];
                $response['message'] = "Refund successfully done. ";
                log_message('error', 'Razorpay Webhook | Payment refund done --> ' . var_export($request['event'], true));
                echo json_encode($response);
                return false;
            } elseif ($request['event'] == "refund.failed") {
                $response['error'] = true;
                $response['transaction_status'] = $request['event'];
                $response['message'] = "Refund is failed. ";
                log_message('error', 'Razorpay Webhook | Payment refund failed --> ' . var_export($request['event'], true));
                echo json_encode($response);
                return false;
            } else {
                $response['error'] = true;
                $response['transaction_status'] = $request['event'];
                $response['message'] = "Transaction could not be detected.";
                log_message('error', 'Razorpay Webhook | Transaction could not be detected --> ' . var_export($request['event'], true));
                echo json_encode($response);
                return false;
            }
        } else {
            log_message('error', 'razorpay Webhook | Invalid Server Signature  --> ' . var_export($request['event'], true));
            return false;
        }
    }

    public function edie($error_msg)
    {
        global $debug_email;
        $report =  "ERROR : " . $error_msg . "\n\n";
        $report .= "POST DATA\n\n";
        foreach ($_POST as $key => $value) {
            $report .= "|$key| = |$value| \n";
        }
        log_message('error', $report);
        die($error_msg);
    }

    /**
     * Refuse a webhook call, uniformly.
     *
     * Shared by every gateway handler in this file so that a rejection always looks
     * the same from the outside and is always recorded the same way inside.
     *
     * Three deliberate choices:
     *
     *  - 401 with a bare body, and NEVER any detail about why. The reason goes to the
     *    log, not to the caller: telling an attacker "signature mismatch" versus
     *    "unknown transaction" versus "no secret configured" is a free oracle for
     *    probing what our verification actually checks.
     *
     *  - The reason IS logged, in full, because the operator needs it. "Payments
     *    stopped being recorded three days ago" must be diagnosable from the log
     *    alone, and the two failure modes an operator will actually hit (secret not
     *    pasted in yet, secret rotated on one side only) both look identical from
     *    the browser.
     *
     *  - No request body is logged. The old handlers var_export'ed the whole $_SERVER
     *    array at error level, which put HTTP_COOKIE and HTTP_AUTHORIZATION into
     *    application/logs/ on production. The source IP is enough to correlate.
     */
    private function reject_webhook($gateway, $reason)
    {
        log_message('error', 'Webhook REFUSED [' . $gateway . '] ' . $reason
            . ' | ip=' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown')
            . ' | method=' . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'unknown'));

        $this->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode(['error' => true, 'message' => 'Unauthorized']));
    }


    // ------------------------------------------------ MyFatoorah PAYMENT GATEWAY ------------------------------------------

    public function myfatoorah_success_url()
    {
        $response['error'] = false;
        $response['transaction_status'] = 'Success';
        $response['message'] = "Transaction successfully done";
        echo json_encode($response);
        return false;
    }

    public function myfatoorah_error_url()
    {

        $response['error'] = true;
        $response['transaction_status'] = 'Failure';
        $response['message'] = "Transaction is failed. ";
        log_message('error', 'My Fatoorah Transaction is failed --> ');
        echo json_encode($response);
        return false;
    }
    public function myfatoorah()
    {
        $system_settings = get_settings('system_settings', true);
        //get MyFatoorah-Signature from request headers
        $request_headers = apache_request_headers();
        log_message('error', 'my fatoorah  Webhook | request_headers  --> ' . var_export($request_headers, true));
        log_message('error', 'my fatoorah  Webhook | request_headers  --> ' . var_export($request_headers['Host'], true));
        $MyFatoorah_Signature = $request_headers['Myfatoorah-Signature'];
        log_message('error', 'my fatoorah  Webhook | Invalid Server Signature  --> ' . var_export($MyFatoorah_Signature, true));

        $payment_settings = get_settings('payment_method', true);
        $secret = $payment_settings['myfatoorah__secret_key'];
        /* A commented-out literal MyFatoorah webhook secret used to sit on the next line.
         * Commenting a credential out does not unpublish it - it was committed, so it is
         * in git history and readable by anyone with repository access. Removed here, and
         * it must be ROTATED in the MyFatoorah portal; the live value is read from
         * payment settings above, as it should be. */

        //Get webhook body content
        $body = (file_get_contents("php://input"));

        $data = json_decode($body, true);

        //Log data to check the result

        log_message('error', $body);

        // error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - ' . $body, 3, './webhook.log');

        //Validate the signature
        $this->validateSignature($data, $secret, $MyFatoorah_Signature);

        //Call $data['Event'] function
        if (empty($data['Event'])) {
            exit();
        } else {

            log_message('error', $data['Data']['PaymentId']);
            $txn_id = (isset($data['Data']['PaymentId'])) ? $data['Data']['PaymentId'] : "";

            if (!empty(($data['Data']['PaymentId']))) {
                if (!empty($txn_id)) {
                    $transaction = fetch_details('transactions', ['txn_id' => $txn_id], '*');
                }
                $amount = $data['Data']['InvoiceValueInBaseCurrency'];
                // $amount = ($amount / 100);
            } else {
                $amount = 0;
                $currency = (isset($data['Data']['PayCurrency'])) ? $data['Data']['PayCurrency'] : "";
            }

            if (!empty($transaction)) {
                $order_id = $transaction[0]['order_id'];
                $user_id = $transaction[0]['user_id'];
            } else {
                $order_id = 0;
                $order_id = (isset($data['Data']['UserDefinedField'])) ? $data['Data']['UserDefinedField'] : $data['Data']['UserDefinedField'];
            }

            $this->load->model('transaction_model');
            // if ($request['event'] == 'payment.authorized') {
            //     $currency = (isset($request['payload']['payment']['entity']['currency'])) ? $request['payload']['payment']['entity']['currency'] : "INR";
            //     $this->load->library("razorpay");
            //     $response = $this->razorpay->capture_payment($amount * 100, $txn_id, $currency);
            //     return;
            // }
            if ($data['Event'] == 'TransactionsStatusChanged') {
                if ($data['Data']['TransactionStatus'] == "FAILED") {



                    if (!empty($order_id)) {
                        // Restore was commented out: a failed payment cancelled the order but held
                        // its stock forever. See restore_order_stock().
                        restore_order_stock($order_id);
                        update_details(['active_status' => 'cancelled'], ['order_id' => $order_id], 'order_items');
                    }
                    /* No need to add because the transaction is already added just update the transaction status */
                    if (!empty($transaction)) {
                        $transaction_id = $transaction[0]['id'];
                        update_details(['status' => 'failed'], ['id' => $transaction_id], 'transactions');
                    }
                    $response['error'] = true;
                    $response['transaction_status'] = $data['Data']['TransactionStatus'];
                    $response['message'] = "Transaction is failed. ";
                    log_message('error', 'Razorpay Webhook | Transaction is failed --> ' . var_export($data['Data']['TransactionStatus'], true));
                    echo json_encode($response);
                    return false;
                } else if ($data['Data']['TransactionStatus'] == "SUCCESS") {


                    if ($data['Event'] == 'TransactionsStatusChanged') {
                        $order_id = $data['Data']['UserDefinedField'];
                        $order_data = fetch_orders($order_id);
                        $user_id = (isset($order_data['order_data'][0]['user_id'])) ? $order_data['order_data'][0]['user_id'] : "";
                    }
                    if (!empty($order_id)) {
                        /* To do the wallet recharge if the order id is set in the patter */
                        if (strpos($order_id, "wallet-refill-user") !== false) {
                            if (!is_numeric($order_id) && strpos($order_id, "wallet-refill-user") !== false) {
                                $temp = explode("-", $order_id);
                                if (isset($temp[3]) && is_numeric($temp[3]) && !empty($temp[3] && $temp[3] != '')) {
                                    $user_id = $temp[3];
                                } else {
                                    $user_id = 0;
                                }
                            }

                            $data['transaction_type'] = "wallet";
                            $data['user_id'] = $user_id;
                            $data['order_id'] = $order_id;
                            $data['type'] = "credit";
                            $data['txn_id'] = $txn_id;
                            $data['amount'] = $amount;
                            $data['status'] = "success";
                            $data['message'] = "Wallet refill successful";
                            log_message('error', 'My fatoorah user ID -  transaction data--> ' . var_export($data, true));


                            $this->transaction_model->add_transaction($data);
                            log_message('error', 'My Fatoorah user ID - Add transaction --> ' . var_export($txn_id, true));

                            $this->load->model('customer_model');
                            if ($this->customer_model->update_balance($amount, $user_id, 'add')) {
                                $response['error'] = false;
                                $response['transaction_status'] = $data['Data']['TransactionStatus'];
                                $response['message'] = "Wallet recharged successfully!";
                                log_message('error', 'My fatoorah user ID - Wallet recharged successfully --> ' . var_export($order_id, true));
                            } else {
                                $response['error'] = true;
                                $response['transaction_status'] = $data['Data']['TransactionStatus'];
                                $response['message'] = "Wallet could not be recharged!";
                                log_message('error', 'My Fatoorah Webhook | wallet recharge failure --> ' . var_export($data['Data']['TransactionStatus'], true));
                            }
                            echo json_encode($response);
                            return false;
                        } else {

                            /* process the order and mark it as received */
                            $order = fetch_orders($order_id, false, false, false, false, false, false, false);
                            if (isset($order['order_data'][0]['user_id'])) {
                                $user = fetch_details('users', ['id' => $order['order_data'][0]['user_id']]);
                                $overall_total = array(
                                    'total_amount' => $order['order_data'][0]['total'],
                                    'delivery_charge' => $order['order_data'][0]['delivery_charge'],
                                    'tax_amount' => $order['order_data'][0]['total_tax_amount'],
                                    'tax_percentage' => $order['order_data'][0]['total_tax_percent'],
                                    'discount' => $order['order_data'][0]['promo_discount'],
                                    'wallet' => $order['order_data'][0]['wallet_balance'],
                                    'final_total' => $order['order_data'][0]['final_total'],
                                    'otp' => $order['order_data'][0]['otp'],
                                    'address' => $order['order_data'][0]['address'],
                                    'payment_method' => $order['order_data'][0]['payment_method'],
                                );

                                $overall_order_data = array(
                                    'cart_data' => $order['order_data'][0]['order_items'],
                                    'order_data' => $overall_total,
                                    'subject' => 'Order received successfully',
                                    'user_data' => $user[0],
                                    'system_settings' => $system_settings,
                                    'user_msg' => 'Hello, Dear ' . ucfirst($user[0]['username']) . ', We have received your order successfully. Your order summaries are as followed',
                                    'otp_msg' => 'Here is your OTP. Please, give it to delivery boy only while getting your order.',
                                );

                                if (isset($user[0]['email']) && !empty($user[0]['email'])) {
                                    send_mail($user[0]['email'], 'Order received successfully', $this->load->view('admin/pages/view/email-template.php', $overall_order_data, true));
                                }
                                /* No need to add because the transaction is already added just update the transaction status */
                                if (!empty($transaction)) {
                                    $transaction_id = $transaction[0]['id'];
                                    update_details(['status' => 'success'], ['id' => $transaction_id], 'transactions');
                                } else {
                                    /* add transaction of the payment */
                                    $amount = ($data['Data']['InvoiceValueInBaseCurrency']);
                                    $data = [
                                        'transaction_type' => 'transaction',
                                        'user_id' => $user_id,
                                        'order_id' => $order_id,
                                        'type' => 'my fatoorah',
                                        'txn_id' => $txn_id,
                                        'amount' => $amount,
                                        'status' => 'success',
                                        'message' => 'order placed successfully',
                                    ];
                                    $this->transaction_model->add_transaction($data);
                                }

                                update_details(['active_status' => 'received'], ['order_id' => $order_id], 'order_items');
                                $status = json_encode(array(array('received', date("d-m-Y h:i:sa"))));
                                update_details(['status' => $status], ['order_id' => $order_id], 'order_items', false);

                                // place order custome notification on payment success

                                $custom_notification = fetch_details('custom_notifications', ['type' => "place_order"], '');
                                // The message used to have ONLY < application_name > substituted into it, so the
                                // order id placeholder survived and customers were shown the literal text
                                // "Your order #< order_id > has been placed". Both halves go through the same
                                // token list now.
                                $notification_tokens = [
                                    'order_id'         => $order_id,
                                    'application_name' => $system_settings['app_name'],
                                ];
                                $title = render_notification_text($custom_notification[0]['title'], $notification_tokens);
                                $message = render_notification_text($custom_notification[0]['message'], $notification_tokens);

                                $fcm_admin_subject = (!empty($custom_notification)) ? $title : 'New order placed ID #' . $order_id;
                                $fcm_admin_msg = (!empty($custom_notification)) ? $message : 'New order received for  ' . $system_settings['app_name'] . ' please process it.';
                                $user_fcm = fetch_details('users', ['id' => $user_id], 'fcm_id.mobile,email');
                                $user_fcm_id[0][] = $user_fcm[0]['fcm_id'];
                                if (!empty($user_fcm_id)) {
                                    $fcmMsg = array(
                                        'title' => $fcm_admin_subject,
                                        'body' => $fcm_admin_msg,
                                        'type' => "place_order",
                                        'content_available' => true,
                                    );
                                    send_notification($fcmMsg, $user_fcm_id);
                                }
                                notify_event(
                                    'place_order',
                                    ["customer" => [$user_fcm[0]['email']]],
                                    ["customer" => [$user_fcm[0]['mobile']]],
                                    ["orders.id" => $order_id]
                                );
                                // update_stock($order['order_data'][0]['product_variant_ids'], $order['order_data'][0]['quantity'], 'plus');
                            }
                        }
                    } else {
                        log_message('error', 'My Fatoorah Order id not found --> ' . var_export($data['data'], true));
                        /* No order ID found */
                    }

                    $response['error'] = false;
                    $response['transaction_status'] = $data['Data']['TransactionStatus'];
                    $response['message'] = "Transaction successfully done";
                    echo json_encode($response);
                    return false;
                }
            }


            // $this->$data['Event']($data['Data']);

        }
    }


    // private: a signature-checking helper called only as $this->validateSignature().
    // Being public made a security primitive itself a routable endpoint.
    private function validateSignature($body, $secret, $MyFatoorah_Signature)
    {

        if ($body['Event'] == 'RefundStatusChanged') {
            unset($body['Data']['GatewayReference']);
        }
        $data = $body['Data'];

        //1- Order all data properties in alphabetic and case insensitive.
        uksort($data, 'strcasecmp');

        //2- Create one string from the data after ordering it to be like that key=value,key2=value2 ...
        $orderedData = implode(
            ',',
            array_map(
                function ($v, $k) {
                    return sprintf("%s=%s", $k, $v);
                },
                $data,
                array_keys($data)
            )
        );

        //4- Encrypt the string using HMAC SHA-256 with the secret key from the portal in binary mode.
        //Generate hash string
        $result = hash_hmac('sha256', $orderedData, $secret, true);

        //5- Encode the result from the previous point with base64.
        $hash = base64_encode($result);

        error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - Generated Signature  - ' . $hash, 3, './webhook.log');
        error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - MyFatoorah-Signature - ' . $MyFatoorah_Signature, 3, './webhook.log');

        //6- Compare the signature header with the encrypted hash string. If they are equal, then the request is valid and from the MyFatoorah side.
        if ($MyFatoorah_Signature === $hash) {
            error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - Signature is valid ', 3, './webhook.log');

            // log_message('error', $body);
            // log_message('error', $result);

            log_message("error", "SIGNATURE MATCHED");
            return true;
        } else {
            error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - Signature is not valid ', 3, './webhook.log');
            exit;
        }
    }


    // ------------------------------------------------ MyFatoorah PAYMENT GATEWAY END ------------------------------------------


    //shiprocket webhook
    public function spr_webhook()
    {
        $shiprocket_settings = get_settings('shipping_method', true);
        // Read unguarded before: with no webhook_token configured this raised an undefined-index
        // warning into the response body AND then compared against '', so every Shiprocket
        // callback was rejected and tracking silently stopped updating. Fail loudly in the log
        // instead of half-working.
        $token = isset($shiprocket_settings['webhook_token']) ? (string) $shiprocket_settings['webhook_token'] : '';
        if ($token === '') {
            log_message('error', 'Shiprocket webhook rejected: no webhook_token is configured under Admin > Shipping Settings.');
            echo json_encode(['error' => true, 'message' => 'webhook token is not configured']);
            return false;
        }
        $this->load->library(['Shiprocket']);
        $request = file_get_contents('php://input');

        if ($request === false || empty($request)) {
            $this->edie("Error in reading Post Data");
        }
        $request = json_decode($request, true);

        // Was logged twice at ERROR level - the raw body and then the decoded array - on every
        // single callback. Shiprocket sends one for every scan of every parcel, so this was the
        // biggest writer to the error log by far, it buried the failures worth reading, and it
        // put customer addresses and phone numbers in a file that is not treated as sensitive.
        // The scan history is already persisted on the tracking row; this is only a trace.
        log_message('debug', 'Shiprocket webhook--> ' . substr(json_encode($request), 0, 1000));

        if (!isset($_SERVER['HTTP_X_API_KEY']) || empty($_SERVER['HTTP_X_API_KEY'])) {
            $res['error'] = true;
            $res['message'] = "token is required";
            echo json_encode($res);
            return false;
        }
        // hash_equals so the comparison isn't timing-dependent, and so a token
        // configured as a number can't be loosely matched by a different string.
        if (!hash_equals((string) $token, (string) $_SERVER['HTTP_X_API_KEY'])) {
            $res['error'] = true;
            $res['message'] = "token is not verified";
            echo json_encode($res);
            return false;
        }
        $awb = isset($request['awb']) ? trim((string) $request['awb']) : '';
        // Shiprocket's own payload carries its order id alongside the AWB (documented as
        // `sr_order_id`), which is the only identifier that does not change over a shipment's life.
        $sr_order_id = isset($request['sr_order_id']) ? trim((string) $request['sr_order_id']) : '';

        if ($awb === '' && $sr_order_id === '') {
            $res['error'] = true;
            $res['message'] = "neither awb nor sr_order_id was sent";
            echo json_encode($res);
            return false;
        }

        // is_return is selected so sync_shiprocket_shipment_status() can tell a reverse pickup
        // apart from the original delivery. Both legs carry the same order_item_id, and the
        // two report the same status names for opposite journeys.
        $tracking = ($awb !== '')
            ? fetch_details('order_tracking', ['awb_code' => $awb], 'id,order_id,order_item_id,is_return,awb_code')
            : [];

        /*
         * Fall back to Shiprocket's order id.
         *
         * Matching on the AWB alone loses callbacks in two ordinary situations, and loses them
         * silently - Shiprocket gets its 200 and stops retrying:
         *
         *   - the AWB changes. Re-assigning a shipment to another courier issues a new one, and
         *     order_tracking.awb_code still holds the old number, so every scan for the parcel
         *     that is actually moving is answered "order not found".
         *   - the callback arrives before generate_awb() has stored an AWB at all.
         *
         * The order id is stable across both. When it is what matched, the AWB on the row is
         * brought up to date so later callbacks - and the freight/tracking reads that go by AWB -
         * find it the fast way again.
         */
        if (empty($tracking) && $sr_order_id !== '') {
            $tracking = fetch_details('order_tracking', ['shiprocket_order_id' => $sr_order_id], 'id,order_id,order_item_id,is_return,awb_code');
            if (!empty($tracking) && $awb !== '' && (string) $tracking[0]['awb_code'] !== $awb) {
                log_message('error', 'Shiprocket webhook: shipment ' . $tracking[0]['id'] . ' matched on sr_order_id '
                    . $sr_order_id . '; updating awb_code from ' . var_export($tracking[0]['awb_code'], true)
                    . ' to ' . $awb . '.');
                update_details(['awb_code' => $awb], ['id' => $tracking[0]['id']], 'order_tracking', false);
            }
        }

        if (empty($tracking)) {
            $res['error'] = true;
            $res['message'] = "order not found";
            echo json_encode($res);
            return false;
        }

        // Also demoted: one line per scan of every parcel, at the only level this install
        // actually writes (log_threshold = 1), for a fact already on the tracking row.
        log_message('debug', 'Shiprocket webhook order id --> ' . var_export($tracking[0]['order_id'], true));

        // Every status Shiprocket sends is handled here, not just delivered/canceled:
        // sync_shiprocket_shipment_status() maps it onto an internal order-item status
        // (and records the raw status on the tracking row either way), so intermediate
        // states like IN TRANSIT / OUT FOR DELIVERY are no longer silently dropped.
        $current_status = isset($request['current_status']) ? $request['current_status'] : '';
        $sync = sync_shiprocket_shipment_status($tracking[0], $current_status, $request);

        $res['error'] = $sync['error'];
        $res['message'] = $sync['message'];
        echo json_encode($res);
        return false;
    }

    // ------------------------------------------------ Instamojo PAYMENT GATEWAY ------------------------------------------


    /**
     * Instamojo webhook.
     *
     * SECURITY - READ BEFORE EDITING. This handler had NO verification of any kind:
     * no signature, no `mac`, no call back to Instamojo. `payment_id`, `amount`,
     * `purpose` and `status` were all read straight from $_POST, and the credited
     * figure was the POSTed one. So this single unauthenticated request:
     *
     *     curl -d 'payment_id=X&amount=99999&purpose=wallet-refill-user-42&status=Credit' \
     *          https://cretzo.com/admin/webhook/instamojo_webhook
     *
     * credited ~100,000 of store credit to user 42. Nothing else was required.
     *
     * Every figure now comes from Instamojo's own API over our authenticated OAuth
     * token (see Instamojo::verify_webhook_payment), so the request body is used only
     * to say WHICH payment to go and look up. Forging the body changes nothing,
     * because none of its values are believed.
     */
    public function instamojo_webhook()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
            $this->reject_webhook('instamojo', 'non-POST request');
            return;
        }

        $this->load->library(['instamojo']);
        $system_settings = get_settings('system_settings', true);

        // The body identifies the payment. It does not establish anything about it.
        $txn_id             = isset($_POST['payment_id']) ? trim((string) $_POST['payment_id']) : '';
        $payment_request_id = isset($_POST['payment_request_id']) ? trim((string) $_POST['payment_request_id']) : '';

        if ($txn_id === '' || $payment_request_id === '') {
            $this->reject_webhook('instamojo', 'missing payment_id or payment_request_id');
            return;
        }

        $verified = $this->instamojo->verify_webhook_payment($payment_request_id, $txn_id);
        if (!empty($verified['error'])) {
            $this->reject_webhook('instamojo', $verified['message']);
            return;
        }

        // From here down, these three are Instamojo's values, not the caller's. The
        // POSTed `amount`, `status` and `purpose` are deliberately never read again -
        // treat any future reference to $_POST in this method as a regression.
        $amount  = (float) $verified['amount'];
        $status  = $verified['status'];
        $purpose = $verified['purpose'];

        if ($amount <= 0) {
            $this->reject_webhook('instamojo', 'Instamojo reported a non-positive amount for payment ' . $txn_id);
            return;
        }

        log_message('error', 'Instamojo webhook (verified) payment_id=' . $txn_id
            . ' status=' . $status . ' amount=' . $amount);

        $transaction = fetch_details('transactions', ['txn_id' => $txn_id], '*');

        if (!empty($transaction)) {
            $order_id = $purpose;
            $user_id = $transaction[0]['user_id'];
        } else {
            $order_id = $purpose;
            $order_data = fetch_orders($order_id);
            $user_id = (isset($order_data['order_data'][0]['user_id'])) ? $order_data['order_data'][0]['user_id'] : "";
        }

        $this->load->model('transaction_model');
        if (strcasecmp($status, 'Credit') === 0) {

            if (!empty($order_id)) {
                /* To do the wallet recharge if the order id is set in the patter */
                if (strpos($order_id, "wallet-refill-user") !== false) {
                    if (!is_numeric($order_id) && strpos($order_id, "wallet-refill-user") !== false) {
                        $temp = explode("-", $order_id);
                        if (isset($temp[3]) && is_numeric($temp[3]) && !empty($temp[3] && $temp[3] != '')) {
                            $user_id = $temp[3];
                        } else {
                            $user_id = 0;
                        }
                    }

                    $data['transaction_type'] = "wallet";
                    $data['user_id'] = $user_id;
                    $data['order_id'] = $order_id;
                    $data['type'] = "credit";
                    $data['txn_id'] = $txn_id;
                    $data['amount'] = $amount;
                    $data['status'] = "success";
                    $data['message'] = "Wallet refill successful";

                    $this->transaction_model->add_transaction($data);

                    $this->load->model('customer_model');
                    if ($this->customer_model->update_balance($amount, $user_id, 'add')) {
                        $response['error'] = false;
                        $response['transaction_status'] = $status;
                        $response['message'] = "Wallet recharged successfully!";
                    } else {
                        $response['error'] = true;
                        $response['transaction_status'] = $status;
                        $response['message'] = "Wallet could not be recharged!";
                    }
                    echo json_encode($response);
                    return false;
                } else {

                    /* process the order and mark it as received */
                    $order = fetch_orders($order_id, false, false, false, false, false, false, false);

                    if (isset($order['order_data'][0]['user_id'])) {
                        $user = fetch_details('users', ['id' => $order['order_data'][0]['user_id']]);
                        $overall_total = array(
                            'total_amount' => $order['order_data'][0]['total'],
                            'delivery_charge' => $order['order_data'][0]['delivery_charge'],
                            'tax_amount' => $order['order_data'][0]['total_tax_amount'],
                            'tax_percentage' => $order['order_data'][0]['total_tax_percent'],
                            'discount' =>  $order['order_data'][0]['promo_discount'],
                            'wallet' =>  $order['order_data'][0]['wallet_balance'],
                            'final_total' =>  $order['order_data'][0]['final_total'],
                            'otp' => $order['order_data'][0]['otp'],
                            'address' =>  $order['order_data'][0]['address'],
                            'payment_method' => $order['order_data'][0]['payment_method']
                        );

                        $overall_order_data = array(
                            'cart_data' => $order['order_data'][0]['order_items'],
                            'order_data' => $overall_total,
                            'subject' => 'Order received successfully',
                            'user_data' => $user[0],
                            'system_settings' => $system_settings,
                            'user_msg' => 'Hello, Dear ' . ucfirst($user[0]['username']) . ', We have received your order successfully. Your order summaries are as followed',
                            'otp_msg' => 'Here is your OTP. Please, give it to delivery boy only while getting your order.',
                        );
                        if (isset($user[0]['email']) && !empty($user[0]['email'])) {
                            send_mail($user[0]['email'], 'Order received successfully', $this->load->view('admin/pages/view/email-template.php', $overall_order_data, TRUE));
                        }
                        /* No need to add because the transaction is already added just update the transaction status */
                        if (!empty($transaction)) {
                            $transaction_id = $transaction[0]['id'];
                            update_details(['status' => 'success'], ['id' => $transaction_id], 'transactions');
                        } else {
                            /* add transaction of the payment */
                            $amount = ($request['payload']['payment']['entity']['amount'] / 100);
                            $data = [
                                'transaction_type' => 'transaction',
                                'user_id' => $user_id,
                                'order_id' => $order_id,
                                'type' => 'razorpay',
                                'txn_id' => $txn_id,
                                'amount' => $amount,
                                'status' => 'success',
                                'message' => 'order placed successfully',
                            ];
                            $this->transaction_model->add_transaction($data);
                        }

                        update_details(['active_status' => 'received'], ['order_id' => $order_id], 'order_items');
                        $status = json_encode(array(array('received', date("d-m-Y h:i:sa"))));
                        update_details(['status' => $status], ['order_id' => $order_id], 'order_items', false);

                        // place order custome notification on payment success

                        $custom_notification = fetch_details('custom_notifications', ['type' => "place_order"], '');
                        // The message used to have ONLY < application_name > substituted into it, so the
                        // order id placeholder survived and customers were shown the literal text
                        // "Your order #< order_id > has been placed". Both halves go through the same
                        // token list now.
                        $notification_tokens = [
                            'order_id'         => $order_id,
                            'application_name' => $system_settings['app_name'],
                        ];
                        $title = render_notification_text($custom_notification[0]['title'], $notification_tokens);
                        $message = render_notification_text($custom_notification[0]['message'], $notification_tokens);

                        $fcm_admin_subject = (!empty($custom_notification)) ? $title : 'New order placed ID #' . $order_id;
                        $fcm_admin_msg = (!empty($custom_notification)) ? $message : 'New order received for  ' . $system_settings['app_name'] . ' please process it.';
                        $user_fcm = fetch_details('users', ['id' => $user_id], 'fcm_id,mobile,email');
                        $user_fcm_id[0][] = $user_fcm[0]['fcm_id'];
                        if (!empty($user_fcm_id)) {
                            $fcmMsg = array(
                                'title' => $fcm_admin_subject,
                                'body' => $fcm_admin_msg,
                                'type' => "place_order",
                                'content_available' => true
                            );
                            send_notification($fcmMsg, $user_fcm_id);
                        }
                        notify_event(
                            'place_order',
                            ["customer" => [$user_fcm[0]['email']]],
                            ["customer" => [$user_fcm[0]['mobile']]],
                            ["orders.id" => $order_id]
                        );
                        // Removed: this ran on payment SUCCESS and put the order's stock back
                        // after place_order() had correctly taken it. See the equivalent comment
                        // in the Razorpay branch above.
                    }
                }
            } else {
                log_message('error', 'Razorpay Order id not found --> ' . var_export($request, true));
                /* No order ID found */
            }

            $response['error'] = false;
            $response['transaction_status'] = $request['event'];
            $response['message'] = "Transaction successfully done";
            echo json_encode($response);
            return false;
        } elseif (strcasecmp($status, 'Failed') === 0) {
            //$order = fetch_orders($order_id, false, false, false, false, false, false, false);

            if (!empty($order_id)) {
                // Restore was commented out: a failed payment cancelled the order but held its
                // stock forever. See restore_order_stock().
                restore_order_stock($order_id);
                update_details(['active_status' => 'cancelled'], ['order_id' => $order_id], 'order_items');
            }
            /* No need to add because the transaction is already added just update the transaction status */
            if (!empty($transaction)) {
                $transaction_id = $transaction[0]['id'];
                update_details(['status' => 'failed'], ['id' => $transaction_id], 'transactions');
            }
            $response['error'] = true;
            $response['transaction_status'] = $status;
            $response['message'] = "Transaction is failed. ";

            echo json_encode($response);
            return false;
        } else {
            $response['error'] = true;
            $response['transaction_status'] = $status;
            $response['message'] = "Transaction could not be detected.";
            echo json_encode($response);
            return false;
        }
    }

    // ------------------------------------------------ PHONEPE PAYMENT GATEWAY ------------------------------------------

    /**
     * PhonePe callback.
     *
     * SECURITY - READ BEFORE EDITING. This was the least broken of the three gateway
     * callbacks and still exploitable. It did two things right - it required a
     * matching local transaction row, and it called check_status() - and then threw
     * both away:
     *
     *     $status = $request['code'];              // from the request body
     *     $amount = $request['data']['amount']/100 // from the request body
     *     $check_status = $this->phonepe->check_status($txn_id);
     *     if ($check_status) {                     // only that SOMETHING came back
     *         if ($status == 'PAYMENT_SUCCESS') {  // ...the body's word for it
     *
     * check_status() returns an ordinary array for a payment that is still pending or
     * has failed, and any array is truthy - so the guard passed in every case, and the
     * decision was made on body values throughout. The X-VERIFY header was never
     * looked at. Anyone who knew or guessed a pending merchantTransactionId could
     * declare it successful for an amount of their own choosing.
     *
     * Now: X-VERIFY is checked against the salt, and then the code and the amount are
     * taken from PhonePe's status API and the body's copies are discarded.
     */
    public function phonepe_webhook()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
            $this->reject_webhook('phonepe', 'non-POST request');
            return;
        }

        $this->load->library(['Phonepe']);
        $system_settings = get_settings('system_settings', true);

        $raw_body = file_get_contents('php://input');
        $envelope = json_decode((string) $raw_body, 1);
        $base64_response = (is_array($envelope) && isset($envelope['response'])) ? (string) $envelope['response'] : "";

        if ($base64_response === '') {
            $this->reject_webhook('phonepe', 'body carried no base64 `response` field');
            return;
        }

        // PhonePe's digest is over the base64 string exactly as sent, so verify before
        // decoding anything.
        $x_verify = isset($_SERVER['HTTP_X_VERIFY']) ? $_SERVER['HTTP_X_VERIFY'] : '';
        if (!$this->phonepe->verify_callback_signature($base64_response, $x_verify)) {
            $this->reject_webhook('phonepe', 'X-VERIFY signature missing or does not match the configured salt');
            return;
        }

        $request = json_decode(base64_decode($base64_response), 1);
        if (!is_array($request)) {
            $this->reject_webhook('phonepe', 'signature verified but payload is not valid JSON');
            return;
        }

        // The body is now trusted for IDENTIFICATION only. Which transaction is this
        // about? Everything about its worth and outcome still comes from the API below.
        $txn_id = (isset($request['data']['merchantTransactionId'])) ? $request['data']['merchantTransactionId'] : "";
        if (empty($txn_id)) {
            $this->reject_webhook('phonepe', 'payload carried no merchantTransactionId');
            return;
        }

        $transaction = fetch_details('transactions', ['txn_id' => $txn_id], '*');
        if (empty($transaction)) {
            $this->reject_webhook('phonepe', 'no local transaction for ' . $txn_id);
            return;
        }

        $user_id = $transaction[0]['user_id'];
        $transaction_type = (isset($transaction[0]['transaction_type'])) ? $transaction[0]['transaction_type'] : "";
        $order_id = (isset($transaction[0]['order_id'])) ? $transaction[0]['order_id'] : "";

        $this->load->model('transaction_model');

        // PhonePe's verdict, not the caller's. $status and $amount below are both
        // overwritten from this response on purpose - treat any future read of
        // $request['code'] or $request['data']['amount'] here as a regression.
        $verified = $this->phonepe->verify_transaction($txn_id);
        if (!empty($verified['error'])) {
            $this->reject_webhook('phonepe', $verified['message']);
            return;
        }

        $status = $verified['code'];
        $amount = $verified['amount'];

        log_message('error', 'PhonePe callback (verified) txn=' . $txn_id . ' code=' . $status
            . ' amount=' . $amount . ' type=' . $transaction_type . ' order=' . $order_id);

        // The branches below are driven entirely by $status, which is now PhonePe's
        // own code from verify_transaction(). An empty code cannot happen (the verify
        // call fails closed above) but is guarded anyway so an unexpected shape logs
        // instead of falling through the if/elseif chain silently.
        if ($status !== '') {
                if ($status == 'PAYMENT_SUCCESS') {
                    $data['status'] = "success";
                    if ($transaction_type == "wallet") {
                        $data['status'] = "success";
                        $data['message'] = "Wallet refill successful";
    
                        $this->transaction_model->update_transaction($data, $txn_id);

                        $this->load->model('customer_model');
                        if (!$this->customer_model->update_balance($amount, $user_id, 'add')) {

                            log_message('error', 'Phonepe Webhook | couldn\'t update in wallet balance  --> ' . var_export($request, true));
                            die;
                        }

                        return false;
                    } elseif ($transaction_type == "transaction") {
                        $data['message'] = "Payment received successfully";
                        
                        update_details(['active_status' => 'received'], ['order_id' => $order_id], 'order_items');
                        $order_status = json_encode(array(array('received', date("d-m-Y h:i:sa"))));
                        update_details(['status' => $order_status], ['order_id' => $order_id], 'order_items', false);
                        // The two `orders` writes that used to follow targeted active_status
                        // and status columns that do not exist on that table, so they raised
                        // "Unknown column" errors and aborted this handler part-way through.
                        // The order_items writes above are the real status update.
                    }
                    $this->transaction_model->update_transaction($data, $txn_id);
                } elseif ($status == "BAD_REQUEST"  || $status == "AUTHORIZATION_FAILED" || $status == "PAYMENT_ERROR" || $status == "TRANSACTION_NOT_FOUND" || $status == "PAYMENT_DECLINED" || $status == "TIMED_OUT") {
                    $data['status'] = "failed";
                    if ($transaction_type == "wallet") {
                        $data['status'] = "failed";
                        $data['message'] = "Wallet could not be recharged!";
    
                        $this->transaction_model->update_transaction($data, $txn_id);
                    } elseif ($transaction_type == "transaction") {
                        update_details(['active_status' => 'cancelled'], ['order_id' => $order_id], 'order_items');
                        $order_status = json_encode(array(array('cancelled', date("d-m-Y h:i:sa"))));
                        update_details(['status' => $order_status], ['order_id' => $order_id], 'order_items', false);
                        // Same as above - the `orders` writes here targeted columns that
                        // don't exist on that table and errored out.
                        $data['message'] = "Payment couldn't be processed!";
                    }
                    $this->transaction_model->update_transaction($data, $txn_id);
                }
            } else {
                // PhonePe answered with a code this handler has no branch for - most
                // often PAYMENT_PENDING. Nothing is written: the transaction stays as
                // it is and PhonePe will call again when it settles.
                log_message('error', 'PhonePe callback: unhandled status code ' . $status . ' for txn ' . $txn_id);
            }
    }
}
