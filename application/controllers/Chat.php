<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {
    const STATE_ORDER_ID = 'waiting_order_id';
    const STATE_PAYMENT_ISSUE = 'waiting_payment_issue';
    const STATE_PRODUCT_NAME = 'waiting_product_name';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
    }

    public function send() {
        $message = $this->sanitize_message($this->input->post('message', true));
        // The widget posts an `action` for every button press (track_order, payment_issue, ...).
        // It was accepted by the client and then completely ignored here, so a button only ever
        // worked by coincidence - because its data-chat-message text happened to contain a
        // keyword get_bot_reply() recognises. Renaming a button label silently broke it. The
        // action is now the authoritative signal, with the free-text keyword match as fallback.
        $action = (string) $this->input->post('action', true);

        if ($message === '' && $action === '') {
            $this->respond('Please enter a message.');
            return;
        }

        $user_id = $this->get_chat_user_id();

        if ($message !== '') {
            $this->log_message_row($user_id, $message, 'user');
        }

        $state = $this->session->userdata('chat_state');

        if ($state === self::STATE_ORDER_ID) {
            $this->clear_chat_state();
            $reply = $this->reply_with_order_status($message);
        }
        elseif ($state === self::STATE_PAYMENT_ISSUE) {
            $this->clear_chat_state();
            $reply = $this->reply_with_payment_issue($message);
        }
        elseif ($state === self::STATE_PRODUCT_NAME) {
            $this->clear_chat_state();
            $reply = $this->reply_with_product_matches($message);
        }
        elseif ($action !== '') {
            $reply = $this->reply_for_action($action, $message);
        }
        else {
            $reply = $this->get_bot_reply($message);
        }

        if (empty($reply)) {
            $reply = "Thanks - we will help you shortly.";
        }

        // The bot's own half of the conversation was never stored, so `chat_messages` held only
        // the customer's side: the transcript read as a monologue and support had no way to see
        // what the customer had already been told.
        $this->log_message_row($user_id, $reply, 'agent');

        $this->respond($reply);
    }

    /**
     * Maps a widget button to its flow. Kept separate from the free-text keyword matcher so a
     * button's behaviour does not depend on its visible label.
     */
    private function reply_for_action($action, $message)
    {
        switch ($action) {
            case 'track_order':
                return $this->start_order_tracking_flow();
            case 'cancel_order':
                return 'You can cancel from My Account > My Orders > Cancel. If the order has already shipped, raise a support ticket and we will check it manually.';
            case 'return_item':
                return $this->return_policy_reply();
            case 'payment_issue':
                return $this->start_payment_issue_flow();
            case 'product_inquiry':
                return $this->start_product_enquiry_flow();
            case 'support':
                return $this->support_handoff_reply();
            default:
                // Unknown action: fall back to whatever the user actually typed rather than
                // dead-ending on an unrecognised button.
                return $message !== '' ? $this->get_bot_reply($message) : $this->fallback_reply();
        }
    }

    private function support_handoff_reply()
    {
        if ($this->get_chat_user_id() <= 0) {
            return 'Please log in, then open My Account > Support to raise a ticket with our team.';
        }
        return 'You can raise a support ticket from My Account > Support. Our team replies on the ticket itself and you can follow its status there.';
    }

    /**
     * Single exit point for the endpoint. Always emits a JSON content type (the widget was
     * parsing a text/html response) and always returns a fresh CSRF hash so a long-lived
     * widget can keep posting - this endpoint used to 403 on every single message because the
     * widget sent no CSRF token at all and `chat/send` is not in csrf_exclude_uris.
     */
    private function respond($reply)
    {
        $payload = [
            'error'    => false,
            'reply'    => $reply,
            'messages' => [],
        ];
        if (isset($this->security)) {
            $payload['csrfName'] = $this->security->get_csrf_token_name();
            $payload['csrfHash'] = $this->security->get_csrf_hash();
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    

    private function get_bot_reply($message)
    {
        $normalized = $this->normalize($message);
    
        if ($this->matches_any($normalized, ['track order', 'order status', 'where is my order', 'delivery status'])) {
            return $this->start_order_tracking_flow();
        }
    
        if ($this->matches_any($normalized, ['cancel order', 'cancel my order'])) {
            return 'Go to My Orders → Cancel ❌';
        }
    
        if ($this->matches_any($normalized, ['payment issue', 'payment problem', 'payment'])) {
            return $this->start_payment_issue_flow();
        }
    
        if ($this->matches_any($normalized, ['product inquiry', 'product enquiry', 'product'])) {
            return $this->start_product_enquiry_flow();
        }
    
        if ($this->matches_any($normalized, ['return item', 'start return', 'return'])) {
            return $this->return_policy_reply();
        }
    
    
        return $this->fallback_reply();
    }

    private function get_customer_user_id()
    {
        $user_id = $this->session->userdata('user_id');
        return !empty($user_id) ? (int) $user_id : null;
    }

    
    
    private function start_order_tracking_flow() {
        $this->session->set_userdata('chat_state', self::STATE_ORDER_ID);
        return 'Please enter your Order ID.';
    }

    private function start_payment_issue_flow() {
        $this->session->set_userdata('chat_state', self::STATE_PAYMENT_ISSUE);
        return 'Please describe your payment issue.';
    }

    private function start_product_enquiry_flow() {
        $this->session->set_userdata('chat_state', self::STATE_PRODUCT_NAME);
        return 'Please enter the product name.';
    }

    private function reply_with_order_status($order_id) {
        $order_id = trim($order_id);

        if (!ctype_digit($order_id)) {
            return 'We could not find an order with that ID. Please check and try again.';
        }

        $user_id = $this->get_chat_user_id();
        if ($user_id <= 0) {
            return 'Please log in to your account to track this order.';
        }

        $select = ['o.id'];
        foreach (['active_status', 'delivery_date', 'delivery_time', 'estimated_delivery_date', 'estimated_delivery_time', 'expected_delivery_date', 'expected_delivery_time'] as $field) {
            if ($this->db->field_exists($field, 'orders')) {
                $select[] = 'o.' . $field;
            }
        }

        $order = $this->db->select(implode(',', $select))
            ->where('o.id', (int) $order_id)
            ->where('o.user_id', $user_id)
            ->limit(1)
            ->get('orders o')
            ->row_array();

        if (empty($order)) {
            return 'We could not find an order with that ID under your account. Please check and try again.';
        }

        $status = $this->get_order_status_from_database((int) $order_id, $order);
        $estimated_delivery = $this->get_estimated_delivery_from_database($order);

        return 'Your order #' . $order['id'] . ' is currently ' . $status . '. Estimated delivery: ' . $estimated_delivery . '.';
    }

    private function get_order_status_from_database($order_id, $order) {
        if (!empty($order['active_status'])) {
            return $order['active_status'];
        }

        if ($this->db->table_exists('order_items') && $this->db->field_exists('active_status', 'order_items')) {
            $items = $this->db->select('active_status')
                ->where('order_id', $order_id)
                ->get('order_items')
                ->result_array();

            $statuses = [];
            foreach ($items as $item) {
                if (!empty($item['active_status'])) {
                    $statuses[] = $item['active_status'];
                }
            }
            $statuses = array_values(array_unique($statuses));

            if (count($statuses) === 1) {
                return $statuses[0];
            }
            if (count($statuses) > 1) {
                return implode(', ', $statuses);
            }
        }

        return 'not available';
    }

    private function get_estimated_delivery_from_database($order) {
        $date_fields = ['delivery_date', 'estimated_delivery_date', 'expected_delivery_date'];
        $time_fields = ['delivery_time', 'estimated_delivery_time', 'expected_delivery_time'];
        $date = '';
        $time = '';

        foreach ($date_fields as $field) {
            if (!empty($order[$field]) && $order[$field] !== '0000-00-00') {
                $date = $order[$field];
                break;
            }
        }

        foreach ($time_fields as $field) {
            if (!empty($order[$field])) {
                $time = $order[$field];
                break;
            }
        }

        $delivery = trim($date . ' ' . $time);
        return $delivery !== '' ? $delivery : 'Not available';
    }

    private function reply_with_payment_issue($message) {
        $normalized = $this->normalize($message);

        if ($this->matches_any($normalized, ['deducted', 'debited', 'money taken', 'amount deducted'])) {
            return 'We can see that your payment may have been processed without successful order creation. Please wait up to 30 minutes. If the amount is not reversed, contact support with your transaction reference.';
        }
        if ($this->matches_any($normalized, ['pending', 'processing', 'waiting'])) {
            return 'Your payment appears to be pending. Please avoid making another payment until the current transaction is completed.';
        }
        if ($this->matches_any($normalized, ['refund', 'money back', 'refund pending'])) {
            return 'Refunds typically take 5-7 business days depending on your bank. Please check your registered payment method.';
        }
        if ($this->matches_any($normalized, ['upi', 'gpay', 'phonepe', 'paytm'])) {
            return 'Please verify your UPI transaction ID and ensure the payment was successfully completed.';
        }
        if ($this->matches_any($normalized, ['card', 'visa', 'mastercard', 'credit card', 'debit card'])) {
            return 'Please verify whether your bank approved the transaction. Failed authorizations are generally reversed automatically.';
        }

        return 'Please provide your order ID and transaction reference number so our support team can assist further.';
    }

    private function reply_with_product_matches($product_name) {
        $product_name = trim($product_name);

        if ($product_name === '' || !$this->db->table_exists('products')) {
            return 'No matching products were found.';
        }

        $this->db->select('p.name as product_name, MIN(CASE WHEN pv.special_price IS NOT NULL AND pv.special_price > 0 THEN pv.special_price ELSE pv.price END) as product_price, u.username as seller_name, COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name', false)
            ->from('products p')
            ->join('product_variants pv', 'p.id = pv.product_id', 'left')
            ->join('users u', 'p.seller_id = u.id', 'left')
            ->join('seller_data sd', 'p.seller_id = sd.user_id', 'left')
            ->like('p.name', $product_name)
            ->group_by(['p.id', 'p.name', 'u.username', 'sd.shop_name', 'sd.store_name'])
            ->limit(5);

        if ($this->db->field_exists('status', 'products')) {
            $this->db->where('p.status', '1');
        }

        $products = $this->db->get()->result_array();

        if (empty($products)) {
            return 'No matching products were found.';
        }

        $lines = [];
        foreach ($products as $product) {
            $price = $product['product_price'];
            $seller_name = !empty($product['store_name']) ? $product['store_name'] : $product['seller_name'];

            $lines[] = 'Product: ' . $product['product_name'] . "\n" .
                'Price: ' . $this->format_price($price) . "\n" .
                'Seller: ' . ($seller_name !== '' ? $seller_name : 'Not available');
        }

        return implode("\n\n", $lines);
    }

    private function return_policy_reply() {
        $return_days = $this->get_return_window_days();

        if ($return_days !== '') {
            // The setting is often 1 on a new store, which rendered as "within 1 days".
            $unit = ((int) $return_days === 1) ? ' day' : ' days';
            return 'Items can be returned within ' . (int) $return_days . $unit . ', subject to the platform return policy and product eligibility shown on the product page.';
        }

        return 'Items can generally be returned within the allowed return period shown on the product page.';
    }

    private function get_return_window_days() {
        if (function_exists('get_settings')) {
            $settings = get_settings('system_settings', true);
            if (!empty($settings['max_product_return_days'])) {
                return $settings['max_product_return_days'];
            }
        }

        return '';
    }

    private function fallback_reply() {
        return "I can help with:\n• Order Tracking\n• Order Cancellation\n• Payment Issues\n• Product Enquiries\n• Returns\n• Customer Support\n\nPlease select one of the available options.";
    }

    private function log_message_row($user_id, $message, $sender)
    {
        $message = (string) $message;
        if ($message === '') {
            return;
        }

        // `chat_messages.session_id` is varchar(128) and `message` is NOT NULL, so guard both:
        // an over-long session id or an empty body would abort the insert and - with db_debug
        // on - print a database error page into the middle of the JSON response.
        $session_id = substr((string) session_id(), 0, 128);

        $this->db->insert('chat_messages', [
            'chat_session_id' => 1,
            'user_id' => $user_id > 0 ? $user_id : null,
            'session_id' => $session_id !== '' ? $session_id : null,
            'sender' => in_array($sender, ['user', 'agent'], true) ? $sender : 'user',
            // is_read is only meaningful for the customer's own messages (it tells support what
            // it has not looked at yet); the bot's own replies are read by definition.
            'is_read' => $sender === 'user' ? 0 : 1,
            'message' => mb_substr($message, 0, 5000)
        ]);
    }

    private function get_chat_message_table() {
        if ($this->db->table_exists('chat_message')) {
            return 'chat_message';
        }
        if ($this->db->table_exists('chat_messages')) {
            return 'chat_messages';
        }
        return 'chat_message';
    }
    private function get_chat_user_id() {
        $user_id = $this->session->userdata('user_id');
        return !empty($user_id) && ctype_digit((string) $user_id) ? (int) $user_id : 0;
    }

    private function clear_chat_state() {
        $this->session->unset_userdata('chat_state');
    }

    private function sanitize_message($message) {
        return trim(strip_tags((string) $message));
    }

    private function normalize($message) {
        return strtolower(trim($message));
    }

    private function matches_any($message, $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function matches_greeting($message) {
        return (bool) preg_match('/\b(hello|hi|hey|good morning|good evening)\b/i', $message);
    }

    private function format_price($price) {
        if ($price === null || $price === '') {
            return 'Not available';
        }

        if (is_numeric($price)) {
            return '₹' . number_format((float) $price, 2);
        }

        return '₹' . $price;
    }


}
