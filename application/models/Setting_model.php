<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setting_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper', 'timezone_helper']);
    }


    public function update_system_setting($post)
    {
        // NOT escape_array()'d - $system_data is json_encode()'d below and the resulting blob
        // is written through the query builder, which already parameter-escapes it. Pre-escaping
        // each field first and then letting the builder escape the encoded blob again
        // double-escapes quotes and backslashes, and the damage COMPOUNDS on every save: an app
        // name of O'Brien becomes O\'Brien, then O\\\'Brien, and so on. Same root cause already
        // fixed for update_web_setting() below and for Faq_model::add_faq().

        // Every field below used to be read straight out of $post with no isset() guard, so a
        // request that omitted any of them (an older cached copy of the form, a field the
        // browser did not submit, the near-duplicate Web_setting::update_system_settings
        // endpoint which validates a smaller field set, or any partial API-style POST) raised
        // an "Undefined array key" warning AND silently wrote NULL over the stored value.
        // Verified live: a POST carrying only the fields Web_setting validates wiped 26
        // settings in one shot - tax_name, tax_number, current_version_ios, whatsapp_number,
        // every refer-and-earn value, all four maintenance messages, supported_locals and
        // decimal_point - with nothing shown to the admin, because display_errors is off in
        // production so the warnings are invisible there. Anything not present in this request
        // now keeps whatever is already stored.
        $stored = get_settings('system_settings', true);
        $stored = is_array($stored) ? $stored : [];

        // Free-text / numeric field: take the submitted value, else keep what is stored, else
        // fall back to $default.
        $keep = function ($key, $default = '') use ($post, $stored) {
            if (array_key_exists($key, $post)) {
                return $post[$key];
            }
            return array_key_exists($key, $stored) ? $stored[$key] : $default;
        };
        // Same, but never returns an empty string - used for the numeric fields that other
        // code divides by or compares against.
        $keep_non_empty = function ($key, $default) use ($keep) {
            $value = $keep($key, $default);
            return ($value === '' || $value === null) ? $default : $value;
        };
        // Checkbox: an unchecked box is simply absent from the POST, so "absent" can only be
        // read as "off" when this really is the settings form. system_configurations is that
        // form's always-present hidden marker; without it we are looking at a partial payload
        // and must not silently switch every toggle off.
        $is_full_form = array_key_exists('system_configurations', $post);
        $toggle = function ($key) use ($post, $stored, $is_full_form) {
            if (isset($post[$key]) && $post[$key] !== '' && $post[$key] !== '0') {
                return '1';
            }
            if ($is_full_form) {
                return '0';
            }
            return (isset($stored[$key]) && $stored[$key] == '1') ? '1' : '0';
        };

        $system_data = [

            'system_configurations' => $keep('system_configurations', '1'),
            'system_timezone_gmt' => $keep('system_timezone_gmt', '+05:30'),
            'system_configurations_id' => $keep('system_configurations_id', '13'),
            'app_name' => $keep('app_name'),
            'support_number' => $keep('support_number'),
            'support_email' => $keep('support_email'),
            'current_version' => $keep('current_version'),
            'current_version_ios' => $keep('current_version_ios'),
            'is_version_system_on' => $toggle('is_version_system_on'),
            'area_wise_delivery_charge' => $toggle('area_wise_delivery_charge'),
            'currency' => $keep('currency'),
            'delivery_charge' => $keep('delivery_charge'),
            'min_amount' => $keep('min_amount'),
            'system_timezone' => $keep('system_timezone'),
            'is_refer_earn_on' => $toggle('is_refer_earn_on'),
            'min_refer_earn_order_amount' => $keep('min_refer_earn_order_amount'),
            'refer_earn_bonus' => $keep('refer_earn_bonus'),
            'refer_earn_method' => $keep('refer_earn_method'),
            'max_refer_earn_amount' => $keep('max_refer_earn_amount'),
            'refer_earn_bonus_times' => $keep('refer_earn_bonus_times'),
            'welcome_wallet_balance_on' => $toggle('welcome_wallet_balance_on'),
            'wallet_balance_amount' => $keep('wallet_balance_amount'),
            'allow_order_attachments' => $toggle('allow_order_attachments'),
            'upload_limit' => $keep('upload_limit'),
            'minimum_cart_amt' => $keep('minimum_cart_amt'),
            'low_stock_limit' => $keep_non_empty('low_stock_limit', '5'),
            // Statutory deductions taken at settlement. Kept here rather than in a config file
            // so they can be set by whoever has the answer (an accountant, alongside an admin)
            // without a developer editing code. Blank means "not applicable" and is stored as
            // 0, which withholds nothing.
            'commission_gst_percent' => $keep_non_empty('commission_gst_percent', '0'),
            'tcs_percent' => $keep_non_empty('tcs_percent', '0'),
            'tds_percent' => $keep_non_empty('tds_percent', '0'),
            'max_items_cart' => $keep('max_items_cart'),
            'delivery_boy_bonus_percentage' => $keep('delivery_boy_bonus_percentage'),
            'max_product_return_days' => $keep('max_product_return_days'),
            'is_delivery_boy_otp_setting_on' => $toggle('is_delivery_boy_otp_setting_on'),
            'is_single_seller_order' => $toggle('is_single_seller_order'),
            'is_customer_app_under_maintenance' => $toggle('is_customer_app_under_maintenance'),
            'inspect_element' => $toggle('inspect_element'),
            'is_seller_app_under_maintenance' => $toggle('is_seller_app_under_maintenance'),
            'is_delivery_boy_app_under_maintenance' => $toggle('is_delivery_boy_app_under_maintenance'),
            'is_web_under_maintenance' => $toggle('is_web_under_maintenance'),
            'message_for_customer_app' => $keep('message_for_customer_app'),
            'message_for_seller_app' => $keep('message_for_seller_app'),
            'message_for_delivery_boy_app' => $keep('message_for_delivery_boy_app'),
            'message_for_web' => $keep('message_for_web'),
            'cart_btn_on_list' => $toggle('cart_btn_on_list'),
            'google_login' => $toggle('google_login'),
            'facebook_login' => $toggle('facebook_login'),
            'apple_login' => $toggle('apple_login'),
            'whatsapp_status' => $toggle('whatsapp_status'),
            'whatsapp_number' => $keep('whatsapp_number'),

            'expand_product_images' => $toggle('expand_product_images'),
            'tax_name' => $keep('tax_name'),
            'tax_number' => $keep('tax_number'),
            'company_name' => $keep('company_name'),
            'company_url' => $keep('company_url'),
            'supported_locals' => $keep('supported_locals'),
            'decimal_point' => $keep('decimal_point'),
        ];


        if ($system_data['whatsapp_status'] == 0) {
            $system_data['whatsapp_number'] = '';
        }

        // Same guard as the fields above: an absent logo/favicon means "not part of this
        // request", and both writes below are already skipped when the value is empty.
        $main_image_name = isset($post['logo']) ? $post['logo'] : '';
        $favicon_image_name = isset($post['favicon']) ? $post['favicon'] : '';
        $currency = isset($post['currency']) ? $post['currency'] : '';

        $system_data = json_encode($system_data);
        $query = $this->db->get_where('settings', array(
            'variable' => 'system_settings'
        ));
        $count = $query->num_rows();

        $this->db->trans_start();
        if ($main_image_name != NULL && !empty($main_image_name)) {
            $logo_res = $this->db->get_where('settings', array(
                'variable' => 'logo'
            ));
            $logo_count = $logo_res->num_rows();
            if ($logo_count == 0) {
                $this->db->insert('settings', ['value' => $main_image_name, 'variable' => 'logo']);
            } else {
                $this->db->set('value', $main_image_name)->where('variable', 'logo')->update('settings');
            }
        }
        if ($favicon_image_name != NULL && !empty($favicon_image_name)) {
            $favicon_res = $this->db->get_where('settings', array(
                'variable' => 'favicon'
            ));
            $favicon_count = $favicon_res->num_rows();
            if ($favicon_count == 0) {
                $this->db->insert('settings', ['value' => $favicon_image_name, 'variable' => 'favicon']);
            } else {
                $this->db->set('value', $favicon_image_name)->where('variable', 'favicon')->update('settings');
            }
        }
        if ($count === 0) {
            $data = array(
                'variable' => 'system_settings',
                'value' => $system_data
            );
            $this->db->insert('settings', $data);
            // Was missing the 'variable' key entirely - 'variable' is NOT NULL with no default,
            // so the very first save on a fresh install (before any settings rows exist) would
            // fail to insert a usable currency row, leaving every currency lookup broken until
            // someone fixed it directly in the database.
            $this->db->insert('settings', ['variable' => 'currency', 'value' => $currency]);
        } else {
            $this->db->set('value', $system_data)->where('variable', 'system_settings')->update('settings');
            // Only rewrite the standalone `currency` row when this request actually carried a
            // currency. It used to read $post['currency'] unconditionally, so a partial POST
            // blanked the row that every price on the storefront is formatted with.
            if ($currency !== '') {
                $this->db->set('value', $currency)->where('variable', 'currency')->update('settings');
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status() !== FALSE;
    }

    public function update_web_setting($post)
    {
        // NOT escape_array()'d - this whole $post array is json_encode()'d below and the
        // resulting blob is written through the query builder, which already parameter-
        // escapes it correctly. Pre-escaping every field here first (as this used to do) then
        // letting the builder escape the encoded blob again double-escapes control characters -
        // a real CRLF inside a multi-line field like "address" would become the literal,
        // visible text "\r\n" every time this form was saved (same root cause already fixed for
        // Contact Us / About Us / Privacy Policy elsewhere in this pass).
        $post['app_download_section'] = (isset($post['app_download_section']) && !empty($post['app_download_section'])) ?: 0;
        $post['shipping_mode'] = (isset($post['shipping_mode']) && !empty($post['shipping_mode'])) ?: 0;
        $post['return_mode'] = (isset($post['return_mode']) && !empty($post['return_mode'])) ?: 0;
        $post['support_mode'] = (isset($post['support_mode']) && !empty($post['support_mode'])) ?: 0;
        $post['safety_security_mode'] = (isset($post['safety_security_mode']) && !empty($post['safety_security_mode'])) ?: 0;
        $main_image_name = (isset($post['logo']) && !empty($post['logo'])) ? $post['logo'] : "";
        $favicon_image_name = (isset($post['favicon']) && !empty($post['favicon'])) ? $post['favicon'] : "";
        $system_data = json_encode($post);
        $query = $this->db->get_where('settings', array(
            'variable' => 'web_settings'
        ));
        $count = $query->num_rows();
        if ($main_image_name != NULL && !empty($main_image_name)) {
            $logo_res = $this->db->get_where('settings', array(
                'variable' => 'web_logo'
            ));
            $logo_count = $logo_res->num_rows();
            if ($logo_count == 0) {
                $this->db->insert('settings', ['value' => $main_image_name, 'variable' => 'web_logo']);
            } else {
                $this->db->set('value', $main_image_name)->where('variable', 'web_logo')->update('settings');
            }
        }
        if ($favicon_image_name != NULL && !empty($favicon_image_name)) {
            $favicon_res = $this->db->get_where('settings', array(
                'variable' => 'web_favicon'
            ));
            $favicon_count = $favicon_res->num_rows();
            if ($favicon_count == 0) {
                $this->db->insert('settings', ['value' => $favicon_image_name, 'variable' => 'web_favicon']);
            } else {
                $this->db->set('value', $favicon_image_name)->where('variable', 'web_favicon')->update('settings');
            }
        }
        if ($count === 0) {
            $data = array(
                'variable' => 'web_settings',
                'value' => $system_data
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $system_data)->where('variable', 'web_settings')->update('settings');
        }
    }
    public function update_payment_method($post)
    {
        // NOT escape_array()'d, and merged over what is already stored rather than rebuilt from
        // the request. Both matter here more than anywhere else in this file, because this blob
        // holds every payment gateway's live credentials.
        //
        // Rebuilt-from-POST was the dangerous half. Every field defaulted to '' or '0' when the
        // request did not carry it, so ANY partial submission silently disabled every gateway and
        // blanked every key and secret - and the store simply stops being able to take money, with
        // nothing logged and no error shown. Reproduced by accident while probing admin endpoints
        // with empty payloads: one request wiped all eleven gateway flags and the live Razorpay
        // key and secret in a single write.
        //
        // escape_array() was the quieter half: it ran db->escape_str() over values the query
        // builder then escapes again, so a gateway secret containing a quote or a backslash was
        // stored corrupted - and a corrupted secret does not announce itself, it just fails
        // signature verification later and looks like the gateway rejecting you.
        $stored = get_settings('payment_method', true);
        $stored = is_array($stored) ? $stored : [];

        // Keep the stored value when this request does not carry the field.
        $keep = function ($key) use ($post, $stored) {
            if (isset($post[$key]) && $post[$key] !== '') {
                return $post[$key];
            }
            return isset($stored[$key]) ? $stored[$key] : '';
        };
        // A checkbox is absent when off, so "absent" only means off for a real form submission.
        // The settings form always posts this marker; without it we are looking at a partial
        // payload and must not switch every gateway off.
        $is_full_form = array_key_exists('payment_method_form', $post)
            || array_key_exists('currency_code', $post);
        $toggle = function ($key) use ($post, $stored, $is_full_form) {
            if (isset($post[$key])) {
                return '1';
            }
            if ($is_full_form) {
                return '0';
            }
            return (isset($stored[$key]) && $stored[$key] == '1') ? '1' : '0';
        };



        $payment_data = array();
        $payment_data['paypal_payment_method'] = $toggle('paypal_payment_method');
        $payment_data['paypal_mode'] = $keep('paypal_mode');
        $payment_data['paypal_business_email'] = $keep('paypal_business_email');
        $payment_data['currency_code'] = $keep('currency_code');

        $payment_data['razorpay_payment_method'] = $toggle('razorpay_payment_method');
        $payment_data['razorpay_key_id'] = $keep('razorpay_key_id');
        $payment_data['razorpay_secret_key'] = $keep('razorpay_secret_key');
        $payment_data['refund_webhook_secret_key'] = $keep('refund_webhook_secret_key');


        $payment_data['paystack_payment_method'] = $toggle('paystack_payment_method');
        $payment_data['paystack_key_id'] = $keep('paystack_key_id');
        $payment_data['paystack_secret_key'] = $keep('paystack_secret_key');


        $payment_data['stripe_payment_method'] = $toggle('stripe_payment_method');
        // Defaulted to 'test' when absent, which would quietly drop a LIVE Stripe account back to
        // test mode on any partial save - payments would appear to succeed and take no money.
        $payment_data['stripe_payment_mode'] = $keep('stripe_payment_mode') !== '' ? $keep('stripe_payment_mode') : 'test';
        $payment_data['stripe_publishable_key'] = $keep('stripe_publishable_key');
        $payment_data['stripe_secret_key'] = $keep('stripe_secret_key');
        $payment_data['stripe_webhook_secret_key'] = $keep('stripe_webhook_secret_key');
        $payment_data['stripe_currency_code'] = $keep('stripe_currency_code');

        $payment_data['flutterwave_payment_method'] = $toggle('flutterwave_payment_method');
        $payment_data['flutterwave_public_key'] = $keep('flutterwave_public_key');
        $payment_data['flutterwave_secret_key'] = $keep('flutterwave_secret_key');
        $payment_data['flutterwave_encryption_key'] = $keep('flutterwave_encryption_key');
        $payment_data['flutterwave_webhook_secret_key'] = $keep('flutterwave_webhook_secret_key');
        $payment_data['flutterwave_currency_code'] = $keep('flutterwave_currency_code');

        $payment_data['paytm_payment_method'] = $toggle('paytm_payment_method');
        $payment_data['paytm_payment_mode'] = $keep('paytm_payment_mode');
        $payment_data['paytm_merchant_key'] = $keep('paytm_merchant_key');
        $payment_data['paytm_merchant_id'] = $keep('paytm_merchant_id');
        // Both were forced to Paytm's STAGING values whenever paytm_payment_mode was absent from
        // the request - so a partial save silently moved a live Paytm account onto the staging
        // website/industry id, where real payments cannot complete. And when the mode WAS
        // production they read paytm_website / paytm_industry_type_id unguarded, warning if the
        // request carried the mode but not those two. Only apply the staging fallback when this
        // request is genuinely telling us the mode is not production.
        $paytm_mode = $keep('paytm_payment_mode');
        if ($paytm_mode === 'production') {
            $payment_data['paytm_website'] = $keep('paytm_website') !== '' ? $keep('paytm_website') : 'DEFAULT';
            $payment_data['paytm_industry_type_id'] = $keep('paytm_industry_type_id') !== '' ? $keep('paytm_industry_type_id') : 'Retail';
        } else {
            $payment_data['paytm_website'] = 'WEBSTAGING';
            $payment_data['paytm_industry_type_id'] = 'Retail';
        }

        $payment_data['midtrans_payment_mode'] = $keep('midtrans_payment_mode');
        $payment_data['midtrans_payment_method'] = $toggle('midtrans_payment_method');
        $payment_data['midtrans_client_key'] = $keep('midtrans_client_key');
        $payment_data['midtrans_merchant_id'] = $keep('midtrans_merchant_id');
        $payment_data['midtrans_server_key'] = $keep('midtrans_server_key');

        $payment_data['direct_bank_transfer'] = $toggle('direct_bank_transfer');
        $payment_data['account_name'] = $keep('account_name');
        $payment_data['account_number'] = $keep('account_number');
        $payment_data['bank_name'] = $keep('bank_name');
        $payment_data['bank_code'] = $keep('bank_code');
        $payment_data['notes'] = $keep('notes');

        $payment_data['myfaoorah_payment_method'] = $toggle('myfaoorah_payment_method');
        $payment_data['myfatoorah_token'] = $keep('myfatoorah_token');
        $payment_data['myfatoorah_payment_mode'] = $keep('myfatoorah_payment_mode');
        $payment_data['myfatoorah__successUrl'] = $keep('myfatoorah__successUrl');
        $payment_data['myfatoorah__errorUrl'] = $keep('myfatoorah__errorUrl');
        $payment_data['myfatoorah_language'] = $keep('myfatoorah_language');
        $payment_data['myfatoorah_country'] = $keep('myfatoorah_country');
        $payment_data['myfatoorah__secret_key'] = $keep('myfatoorah__secret_key');

        $payment_data['instamojo_payment_method'] = $toggle('instamojo_payment_method');
        $payment_data['instamojo_payment_mode'] = $keep('instamojo_payment_mode');
        $payment_data['instamojo_client_id'] = $keep('instamojo_client_id');
        $payment_data['instamojo_client_secret'] = $keep('instamojo_client_secret');
        $payment_data['instamojo_webhook_url'] = $keep('instamojo_webhook_url');

        $payment_data['phonepe_payment_method'] = $toggle('phonepe_payment_method');
        $payment_data['phonepe_payment_mode'] = $keep('phonepe_payment_mode');
        $payment_data['phonepe_marchant_id'] = $keep('phonepe_marchant_id');
        $payment_data['phonepe_app_id'] = $keep('phonepe_app_id');
        $payment_data['phonepe_salt_key'] = $keep('phonepe_salt_key');
        $payment_data['phonepe_salt_index'] = $keep('phonepe_salt_index');
        $payment_data['phonepe_webhook_url'] = $keep('phonepe_webhook_url');

        $payment_data['cod_method'] = $toggle('cod_method');

        $payment_data = json_encode($payment_data);

        $query = $this->db->get_where('settings', array(
            'variable' => 'payment_method'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'payment_method',
                'value' => $payment_data
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $payment_data)->where('variable', 'payment_method')->update('settings');
        }
    }

    public function update_time_slot($post)
    {
        $post = escape_array($post);

        $time_slot_data = [
            'title' => $post['title'],
            'from_time' => $post['from_time'],
            'to_time' => $post['to_time'],
            'last_order_time' => $post['last_order_time'],
            'status' => $post['status'],
        ];
        if (isset($post['edit_time_slot']) && !empty($post['edit_time_slot'])) {
            return $this->db->set($time_slot_data)->where('id', $post['edit_time_slot'])->update('time_slots');
        } else {
            return $this->db->insert('time_slots', $time_slot_data);
        }
    }


    public function update_time_slot_config($data)
    {
        // print_R($_POST);
        $data = escape_array($data);

        $config_data = [
            'time_slot_config' => $data['time_slot_config'],
            'is_time_slots_enabled' => isset($data['is_time_slots_enabled']) ? '1' : '0',
            'delivery_starts_from' => $data['delivery_starts_from'],
            'allowed_days' => $data['allowed_days'],
        ];
        $config_data = json_encode($config_data);

        $query = $this->db->get_where('settings', array(
            'variable' => 'time_slot_config'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'time_slot_config',
                'value' => $config_data
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $config_data)->where('variable', 'time_slot_config')->update('settings');
        }
    }

    function update_authentication_setting($post)
    {
        $post = escape_array($post);
        $authentication_data = array();

        // $authentication_data['authentication_method'] = isset($post['authentication_method']) && !empty($post['authentication_method']) ? $post['authentication_method'] : '';
        $authentication_data['authentication_method'] = 'firebase';

        $authentication_data = json_encode($authentication_data);

        $query = $this->db->get_where('settings', array(
            'variable' => 'authentication_settings'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'authentication_settings',
                // 'value' => json_encode($post)
                'value' => $authentication_data
            );
            $this->db->insert('settings', $data);
        } else {
            $this->db->set('value', $authentication_data)->where('variable', 'authentication_settings')->update('settings');
        }
    }
    public function update_fcm_details($post)
    {
        $post = escape_array($post);

        $query = $this->db->get_where('settings', array(
            'variable' => 'fcm_server_key'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'fcm_server_key',
                'value' => $post['fcm_server_key']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['fcm_server_key'])->where('variable', 'fcm_server_key')->update('settings');
        }
    }
    public function update_vapkey($post)
    {
        $post = escape_array($post);

        $query = $this->db->get_where('settings', array(
            'variable' => 'vap_id_Key'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'vap_id_Key',
                'value' => $post['vap_id_Key']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['vap_id_Key'])->where('variable', 'vap_id_Key')->update('settings');
        }
    }
    public function update_smsgateway($post)
    {
        $post = escape_array($post);
        // print_r($post);
        $smsgateway_data = array();

        $smsgateway_data['base_url'] = isset($post['base_url']) ? $post['base_url'] : '';
        $smsgateway_data['sms_gateway_method'] = isset($post['sms_gateway_method']) ? $post['sms_gateway_method'] : 'POST';
        $smsgateway_data['country_code_include'] = isset($post['country_code_include']) ? $post['country_code_include'] : '0';
        $smsgateway_data['header_key'] = isset($post['header_key']) && !empty($post['header_key']) ? $post['header_key'] : '';
        $smsgateway_data['header_value'] = isset($post['header_value']) && !empty($post['header_value']) ? $post['header_value'] : '';
        $smsgateway_data['text_format_data'] = isset($post['text_format_data']) && !empty($post['text_format_data']) ? $post['text_format_data'] : '';
        $smsgateway_data['params_key'] = isset($post['params_key']) && !empty($post['params_key']) ? $post['params_key'] : '';
        $smsgateway_data['params_value'] = isset($post['params_value']) && !empty($post['params_value']) ? $post['params_value'] : '';
        $smsgateway_data['body_key'] = isset($post['body_key']) && !empty($post['body_key']) ? $post['body_key'] : '';
        $smsgateway_data['body_value'] = isset($post['body_value']) && !empty($post['body_value']) ? $post['body_value'] : '';


        $smsgateway_data = json_encode($smsgateway_data);


        $query = $this->db->get_where('settings', array(
            'variable' => 'sms_gateway_settings'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'sms_gateway_settings',
                'value' => $smsgateway_data
            );
            // Was passing $smsgateway_data (an already-JSON-encoded STRING) instead of $data
            // (the array insert() actually expects) - on the very first save ever (before this
            // settings row existed), the insert failed/produced garbage while the controller
            // still reported success, leaving the SMS gateway silently unconfigured.
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $smsgateway_data)->where('variable', 'sms_gateway_settings')->update('settings');
        }
    }

    public function update_notification_setting($data)
    {
        // $data['permissions'] was read unguarded. The form only submits it when at least one
        // checkbox is ticked, so an admin who unticked EVERYTHING got an "Undefined array key
        // permissions" warning and json_encode(null) - the literal string "null" - written into
        // the settings row. notify_event() then treats that as "no settings at all" and silently
        // stops sending every notification on the platform, with no way to tell from the UI.
        $permissions = (isset($data['permissions']) && is_array($data['permissions'])) ? $data['permissions'] : [];
        $data = escape_array($data);
        $notification_data = json_encode($permissions);
        $query = $this->db->get_where('settings', array(
            'variable' => 'send_notification_settings'
        ));

        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'send_notification_settings',
                'value' => $notification_data
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $notification_data)->where('variable', 'send_notification_settings')->update('settings');
        }
    }

    public function update_contact_details($post)
    {
        // NOT escape_array()'d here - the query builder's set()/insert() already parameter-
        // escapes these values; running escape_array()'s db->escape_str() first and then
        // letting the builder escape again double-escapes control characters, turning a real
        // CRLF inside the saved rich text into the literal, visible text "\r\n" every time the
        // page is re-saved (confirmed live, then reverted before this fix).
        $query = $this->db->get_where('settings', array(
            'variable' => 'contact_us'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'contact_us',
                'value' => $post['contact_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['contact_input_description'])->where('variable', 'contact_us')->update('settings');
        }
    }

    public function update_privacy_policy($post)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping
        // the query builder already does.
        $query = $this->db->get_where('settings', array(
            'variable' => 'privacy_policy'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'privacy_policy',
                'value' => $post['privacy_policy_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['privacy_policy_input_description'])->where('variable', 'privacy_policy')->update('settings');
        }
    }


    public function update_shipping_policy($post)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'shipping_policy'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'shipping_policy',
                'value' => $post['shipping_policy_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['shipping_policy_input_description'])->where('variable', 'shipping_policy')->update('settings');
        }
    }



    public function update_return_policy($post)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'return_policy'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'return_policy',
                'value' => $post['return_policy_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['return_policy_input_description'])->where('variable', 'return_policy')->update('settings');
        }
    }


    public function update_terms_n_condtions($post)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'terms_conditions'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'terms_conditions',
                'value' => $post['terms_n_conditions_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['terms_n_conditions_input_description'])->where('variable', 'terms_conditions')->update('settings');
        }
    }

    public function update_about_us($post)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping
        // (confirmed live: a real CRLF byte in saved content became the literal, visible text
        // "\r\n" the next time this method's original escape_array()+query-builder combination
        // ran - reverted before landing, fixed at the root here instead).
        $query = $this->db->get_where('settings', array(
            'variable' => 'about_us'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'about_us',
                'value' => $post['about_us_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $post['about_us_input_description'])->where('variable', 'about_us')->update('settings');
        }
    }

    public function update_email_settings($data)
    {
        $data = escape_array($data);
        $email_data = json_encode($data);
        $query = $this->db->get_where('settings', array(
            'variable' => 'email_settings'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'email_settings',
                'value' => $email_data
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $email_data)->where('variable', 'email_settings')->update('settings');
        }
    }



    public function update_delivery_boy_privacy_policy($data)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'delivery_boy_privacy_policy'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'delivery_boy_privacy_policy',
                'value' => $data['privacy_policy_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $data['privacy_policy_input_description'])->where('variable', 'delivery_boy_privacy_policy')->update('settings');
        }
    }
    public function update_seller_privacy_policy($data)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'seller_privacy_policy'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'seller_privacy_policy',
                'value' => $data['privacy_policy_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $data['privacy_policy_input_description'])->where('variable', 'seller_privacy_policy')->update('settings');
        }
    }

    public function update_delivery_boy_terms_n_condtions($data)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'delivery_boy_terms_conditions'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'delivery_boy_terms_conditions',
                'value' => $data['terms_n_conditions_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $data['terms_n_conditions_input_description'])->where('variable', 'delivery_boy_terms_conditions')->update('settings');
        }
    }
    public function update_seller_terms_n_condtions($data)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'seller_terms_conditions'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'seller_terms_conditions',
                'value' => $data['terms_n_conditions_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $data['terms_n_conditions_input_description'])->where('variable', 'seller_terms_conditions')->update('settings');
        }
    }
    public function update_admin_privacy_policy($data)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'admin_privacy_policy'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'admin_privacy_policy',
                'value' => $data['privacy_policy_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $data['privacy_policy_input_description'])->where('variable', 'admin_privacy_policy')->update('settings');
        }
    }

    public function update_admin_terms_n_condtions($data)
    {
        // See update_contact_details() above - not escape_array()'d, to avoid double-escaping.
        $query = $this->db->get_where('settings', array(
            'variable' => 'admin_terms_conditions'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'admin_terms_conditions',
                'value' => $data['terms_n_conditions_input_description']
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $data['terms_n_conditions_input_description'])->where('variable', 'admin_terms_conditions')->update('settings');
        }
    }

    public function get_time_slot_details()
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Whitelist against the actual selected columns - $_GET['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id', 'title', 'from_time', 'to_time', 'last_order_time', 'status'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        } else {
            $order = 'asc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['`id`' => $search, '`title`' => $search, '`from_time`' => $search, '`to_time`' => $search];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            // Was or_where() (exact match) while the data query below uses or_like() (partial
            // match), corrupting the pagination total on a partial search.
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $count = $count_res->get('time_slots')->result_array();

        foreach ($count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('time_slots')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($search_res as $row) {

            $operate = ' <a href="javascript:void(0)" class="edit_btn btn btn-primary action-btn btn-xs mr-1 mb-1 ml-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/time-slots/"><i class="fa fa-pen"></i></a>';
            $operate .= '<a class="btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1" title="Delete" id="delete-time-slot" href="javascript:void(0)" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row['id'];
            $tempRow['title'] = html_escape($row['title']);
            $tempRow['from_time'] = $row['from_time'];
            $tempRow['to_time'] = $row['to_time'];
            $tempRow['last_order_time'] = $row['last_order_time'];
            $tempRow['status'] = ($row['status'] == 1) ? "<span class='badge badge-success'>Active</span>" : "<span class='badge badge-danger'>Deactive</span>";
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    public function get_theme_list()
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Whitelist against the actual selected columns - $_GET['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id', 'name', 'slug', 'is_default', 'status'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') {
            $order = 'asc';
        } else {
            $order = 'desc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['id' => $search, 'name' => $search, 'slug' => $search, 'is_default' => $search, 'status' => $search];
        }

        $count_res = $this->db->select(' COUNT(id) as `total`');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $address_count = $count_res->get('themes')->result_array();

        foreach ($address_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('*');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $theme = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('themes')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($theme as $row) {
            $row = output_escaping($row);
            $operate = '';
            $tempRow['id'] = $row['id'];
            $tempRow['name'] = html_escape($row['name']);
            $tempRow['image'] = "<div class='image-box-100'><a href='" . base_url('assets/front_end/' . $row['slug'] . '/preview-image/' . $row['image']) . "' data-toggle='lightbox' data-gallery='gallery'><img src='" . base_url('assets/front_end/' . $row['slug'] . '/preview-image/' . $row['image']) . "' class='rounded'></a></div>";
            if ($row['is_default'] == '1') {
                $tempRow['is_default'] = '<a class="badge badge-success text-white" >Yes</a>';
            } else {
                $tempRow['is_default'] = '<a class="badge badge-danger text-white" >No</a>';
                $operate .= '<a class="btn btn-success action-btn btn-xs update_default_theme mr-1 mb-1 ml-1" title="Default" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-check-circle"></i></a>';
            }
            // data-status carries the DESIRED new status, because admin/themes/switch writes it
            // through verbatim (unlike admin/home/update_status, which inverts what it is sent).
            // This was '. !$row['status'] .', and PHP renders the boolean false as the EMPTY
            // STRING when concatenated - so on an active theme the button posted status="",
            // which failed switch()'s 'required' rule and made deactivating a theme impossible.
            // (int) keeps the intent and renders a real 0/1.
            $desired_status = (int) !$row['status'];
            if ($row['status'] == '1') {
                $tempRow['status'] = '<a class="badge badge-success text-white" >Active</a>';
                $operate .= '<a class="btn btn-warning btn-xs action-btn update_active_status mb-1 ml-1 mr-1" data-table="themes" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $desired_status . '" ><i class="fa fa-eye-slash"></i></a>';
            } else {
                $tempRow['status'] = '<a class="badge badge-danger text-white" >Inactive</a>';
                $operate .= '<a class="btn btn-primary mr-1 ml-1 mb-1 btn-xs action-btn update_active_status" data-table="themes" href="javascript:void(0)" title="Active" data-id="' . $row['id'] . '" data-status="' . $desired_status . '" ><i class="fa fa-eye"></i></a>';
            }
            $tempRow['created_on'] = $row['created_on'];
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    public function firebase_setting($post)
    {
        // NOT escape_array()'d - same double-escaping issue fixed elsewhere in this file
        // (json_encode()'d then written through the query builder, which already escapes it).
        $system_data = json_encode($post);
        $query = $this->db->get_where('settings', array(
            'variable' => 'firebase_settings'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'firebase_settings',
                'value' => $system_data
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $system_data)->where('variable', 'firebase_settings')->update('settings');
        }
    }

    public function update_shipping_method($post)
    {
        // NOT escape_array()'d. That ran db->escape_str() over every value and the query builder
        // then escaped the encoded blob again - the same double-escaping fixed in
        // update_system_setting() above. It matters more here than most places: the Shiprocket
        // API password is machine-generated and may contain a quote or a backslash, and a
        // corrupted password does not announce itself. auth/login simply fails and every shipping
        // call dies with "authentication failed", which looks like wrong credentials rather than
        // credentials this code mangled on the way in.
        $stored = get_settings('shipping_method', true);
        $stored = is_array($stored) ? $stored : [];

        // Free-text field: use what was submitted, else keep what is already stored. Rebuilt
        // purely from POST before, so a request that did not carry every field silently blanked
        // the rest - saving just the credentials wiped minimum_free_delivery_order_amount, and
        // the same shape would wipe the webhook token.
        $keep = function ($key, $default = '') use ($post, $stored) {
            if (array_key_exists($key, $post) && $post[$key] !== '') {
                return $post[$key];
            }
            return array_key_exists($key, $stored) ? $stored[$key] : $default;
        };
        // Checkboxes are absent when off, so "absent" can only mean off for a real form
        // submission. shiprocket_shipping_method is always present on that form (it is the first
        // control and the controller rejects a save with neither method selected), so use it as
        // the marker for "this is the whole form".
        $is_full_form = array_key_exists('shiprocket_shipping_method', $post)
            || array_key_exists('local_shipping_method', $post);
        $toggle = function ($key) use ($post, $stored, $is_full_form) {
            if (isset($post[$key])) {
                return '1';
            }
            if ($is_full_form) {
                return '0';
            }
            return (isset($stored[$key]) && $stored[$key] == '1') ? '1' : '0';
        };

        $shipping_data = array();

        $shipping_data['shiprocket_shipping_method'] = $toggle('shiprocket_shipping_method');
        $shipping_data['email'] = $keep('email');
        $shipping_data['password'] = $keep('password');
        $shipping_data['webhook_token'] = $keep('webhook_token');
        $shipping_data['local_shipping_method'] = $toggle('local_shipping_method');
        $shipping_data['standard_shipping_free_delivery'] = $toggle('standard_shipping_free_delivery');
        $shipping_data['minimum_free_delivery_order_amount'] = $keep('minimum_free_delivery_order_amount');

        $shipping_data = json_encode($shipping_data);

        $query = $this->db->get_where('settings', array(
            'variable' => 'shipping_method'
        ));
        $count = $query->num_rows();
        if ($count === 0) {
            $data = array(
                'variable' => 'shipping_method',
                'value' => $shipping_data
            );
            return $this->db->insert('settings', $data);
        } else {
            return $this->db->set('value', $shipping_data)->where('variable', 'shipping_method')->update('settings');
        }
    }
}
