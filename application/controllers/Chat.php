<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Support assistant behind the storefront's floating chat widget.
 *
 * Replies are built as a structured message - text plus optional quick-reply chips and
 * product cards - so the widget can render a real conversation instead of a wall of
 * plain text, and so the follow-up options always match what was just said.
 */
class Chat extends CI_Controller {
    const STATE_ORDER_ID = 'waiting_order_id';
    const STATE_PAYMENT_ISSUE = 'waiting_payment_issue';
    const STATE_PRODUCT_NAME = 'waiting_product_name';

    /** Newest messages restored into the widget when it is re-opened. */
    const HISTORY_LIMIT = 40;

    /* Each candidate code costs two usage subqueries in validate_promo_code(), and a chat
     * bubble listing more than a handful of codes stops being readable anyway. */
    const MAX_PROMO_CODES = 5;

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
            $this->respond($this->menu_reply('Please type a message, or pick one of the options below.'));
            return;
        }

        $user_id = $this->get_chat_user_id();

        if ($action === 'reset') {
            $this->clear_chat_state();
            $this->respond($this->greeting_reply());
            return;
        }

        if ($message !== '') {
            $this->log_message_row($user_id, $message, 'user');
        }

        $state = $this->session->userdata('chat_state');

        /* A pending prompt ("send me your Order ID") used to swallow the NEXT request whatever
         * it was, so pressing any other button while a prompt was open answered it as if the
         * button's label were the order id / payment description: "Track Order" then
         * "Cancel Order" replied "We could not find an order with that ID." An explicit button
         * press is a deliberate change of subject, so it now cancels the pending prompt. Only
         * free text still feeds it. */
        if ($action !== '') {
            $this->clear_chat_state();
            $reply = $this->reply_for_action($action, $message);
        }
        elseif ($state === self::STATE_ORDER_ID) {
            $this->clear_chat_state();
            /* An Order ID is a number. Anything with no digit in it is a new question, not a
             * badly typed id - answering "मेरा ऑर्डर कहाँ है" with "that does not look like an
             * Order ID" dead-ends the visitor on their own follow-up. */
            $reply = preg_match('/\d/', $message)
                ? $this->reply_with_order_status($message)
                : $this->get_bot_reply($message);
        }
        elseif ($state === self::STATE_PAYMENT_ISSUE) {
            $this->clear_chat_state();
            $reply = $this->reply_with_payment_issue($message);
        }
        elseif ($state === self::STATE_PRODUCT_NAME) {
            $this->clear_chat_state();
            $reply = $this->reply_with_product_matches($message);
        }
        else {
            $reply = $this->get_bot_reply($message);
        }

        if (empty($reply['text'])) {
            $reply = $this->msg('Thanks - we will help you shortly.', $this->main_menu_chips());
        }

        // The bot's own half of the conversation was never stored, so `chat_messages` held only
        // the customer's side: the transcript read as a monologue and support had no way to see
        // what the customer had already been told.
        $this->log_message_row($user_id, $reply['text'], 'agent');

        $this->respond($reply);
    }

    /**
     * Transcript for the current visitor, so re-opening the widget does not look like a brand
     * new conversation. The rows were already being written on every message but nothing ever
     * read them back, so the whole history was invisible and effectively write-only.
     */
    public function history()
    {
        $user_id = $this->get_chat_user_id();

        $this->db->select('sender, message, created_at')
            ->from('chat_messages')
            ->order_by('id', 'DESC')
            ->limit(self::HISTORY_LIMIT);

        // A logged-in customer keeps their history across devices; a guest is identified by
        // the widget's own thread id (see chat_thread_id() for why not the session id).
        if ($user_id > 0) {
            $this->db->where('user_id', $user_id);
        } else {
            $thread = $this->chat_thread_id();
            if ($thread === '') {
                $this->respond_json(['error' => false, 'messages' => []]);
                return;
            }
            // COALESCE, not `IS NULL`: some legacy rows recorded a guest as user_id 0.
            $this->db->where('session_id', $thread)->where('COALESCE(user_id, 0) = 0', null, false);
        }

        $rows = $this->db->get()->result_array();
        $rows = array_reverse($rows);

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = [
                'sender' => $row['sender'] === 'agent' ? 'agent' : 'user',
                'text'   => stripcslashes((string) $row['message']),
                'time'   => $this->display_time($row['created_at']),
            ];
        }

        $this->respond_json([
            'error'         => false,
            'messages'      => $messages,
            'quick_replies' => $this->main_menu_chips(),
            'greeting'      => $this->greeting_reply(),
        ]);
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
            case 'my_orders':
                return $this->recent_orders_reply();
            case 'cancel_order':
                return $this->cancel_policy_reply();
            case 'return_item':
                return $this->return_policy_reply();
            case 'payment_issue':
                return $this->start_payment_issue_flow();
            case 'product_inquiry':
                return $this->start_product_enquiry_flow();
            case 'shipping':
                return $this->shipping_reply();
            case 'offers':
                return $this->offers_reply();
            case 'account':
                return $this->account_reply();
            case 'contact':
                return $this->contact_reply();
            case 'support':
                return $this->support_handoff_reply();
            case 'menu':
                return $this->menu_reply('Here is everything I can help you with.');
            default:
                // Unknown action: fall back to whatever the user actually typed rather than
                // dead-ending on an unrecognised button.
                return $message !== '' ? $this->get_bot_reply($message) : $this->fallback_reply();
        }
    }

    /* ------------------------------------------------------------------ *
     * Structured message helpers
     * ------------------------------------------------------------------ */

    /**
     * A single bot message: body text, the chips offered underneath it, and any product cards.
     * Every handler returns this shape so the widget never has to guess what to show next.
     */
    private function msg($text, $chips = [], $cards = [])
    {
        return [
            'text'          => (string) $text,
            'quick_replies' => array_values($chips),
            // Normalised so a link card and a product card carry the same keys - the widget
            // then never has to test for a key's existence, only for an empty value.
            'cards'         => array_map([$this, 'normalise_card'], array_values($cards)),
        ];
    }

    private function normalise_card($card)
    {
        return [
            'type'  => isset($card['type']) ? $card['type'] : 'link',
            'title' => isset($card['title']) ? (string) $card['title'] : '',
            'body'  => isset($card['body']) ? (string) $card['body'] : '',
            'price' => isset($card['price']) ? (string) $card['price'] : '',
            'image' => isset($card['image']) ? (string) $card['image'] : '',
            'url'   => isset($card['url']) ? (string) $card['url'] : '',
        ];
    }

    private function chip($label, $message, $action = '')
    {
        return ['label' => $label, 'message' => $message, 'action' => $action];
    }

    private function main_menu_chips()
    {
        return [
            $this->chip('📦 Track order', 'track order', 'track_order'),
            $this->chip('🧾 My orders', 'my orders', 'my_orders'),
            $this->chip('🚚 Shipping', 'shipping info', 'shipping'),
            $this->chip('↩️ Returns', 'return item', 'return_item'),
            $this->chip('💳 Payment help', 'payment issue', 'payment_issue'),
            $this->chip('🛍️ Find a product', 'product inquiry', 'product_inquiry'),
            $this->chip('🎁 Offers', 'offers', 'offers'),
            $this->chip('🙋 Talk to support', 'customer support', 'support'),
        ];
    }

    /** Chips shown after an answer: two related follow-ups plus a way back to the menu. */
    private function follow_up_chips($extra = [])
    {
        $chips = $extra;
        $chips[] = $this->chip('☰ Main menu', 'menu', 'menu');
        return $chips;
    }

    private function greeting_reply()
    {
        $name = $this->get_customer_name();
        $hello = $name !== '' ? 'Hi ' . $name . ' 👋' : 'Hi there 👋';

        return $this->msg(
            $hello . "\nI'm the " . $this->store_name() . " assistant. I can track orders, explain shipping and returns, help with payments, or find a product for you.",
            $this->main_menu_chips()
        );
    }

    private function menu_reply($lead = '')
    {
        $lead = $lead !== '' ? $lead : 'What would you like help with?';
        return $this->msg($lead, $this->main_menu_chips());
    }

    /* ------------------------------------------------------------------ *
     * Free-text intent matching
     * ------------------------------------------------------------------ */

    private function get_bot_reply($message)
    {
        $normalized = $this->normalize($message);

        // matches_greeting() existed but was never called, so "hi" fell through to the generic
        // "please select one of the available options" list - a cold open for every visitor who
        // says hello first.
        if ($this->matches_greeting($normalized)) {
            return $this->greeting_reply();
        }

        if ($this->matches_any($normalized, ['thank', 'thanks', 'thx', 'appreciate'])) {
            return $this->msg("You're welcome! 😊 Anything else I can help with?", $this->main_menu_chips());
        }

        if ($this->matches_any($normalized, ['human', 'agent', 'real person', 'talk to someone', 'talk to support', 'customer support', 'contact support', 'raise ticket', 'raise a ticket', 'complaint', 'baat karni', 'shikayat'])) {
            return $this->support_handoff_reply();
        }

        if ($this->matches_any($normalized, ['my orders', 'order history', 'past orders', 'previous orders', 'recent order', 'mere order'])) {
            return $this->recent_orders_reply();
        }

        /* The storefront's customers write in Hinglish and Hindi as often as English, and every
         * one of those messages used to fall straight through to "please select an option".
         * Matching a handful of the common phrasings costs nothing and covers most of them. */
        if ($this->matches_any($normalized, ['track order', 'order status', 'where is my order', 'delivery status', 'track my order', 'order update',
                                             'order kahan', 'kahan hai', 'kaha hai', 'kab aayega', 'kab ayega', 'मेरा ऑर्डर', 'कहाँ', 'कहां', 'कब आएगा'])) {
            return $this->start_order_tracking_flow();
        }

        if ($this->matches_any($normalized, ['cancel order', 'cancel my order', 'cancel', 'रद्द'])) {
            return $this->cancel_policy_reply();
        }

        if ($this->matches_any($normalized, ['refund', 'money back', 'money not received', 'paise wapas', 'paisa wapas', 'वापस'])) {
            return $this->refund_reply();
        }

        if ($this->matches_any($normalized, ['payment issue', 'payment problem', 'payment failed', 'payment'])) {
            return $this->start_payment_issue_flow();
        }

        if ($this->matches_any($normalized, ['coupon', 'promo code', 'discount', 'offer', 'sale'])) {
            return $this->offers_reply();
        }

        if ($this->matches_any($normalized, ['shipping', 'delivery charge', 'delivery fee', 'how long', 'how many days', 'cod', 'cash on delivery'])) {
            return $this->shipping_reply();
        }

        if ($this->matches_any($normalized, ['return item', 'start return', 'exchange', 'replace', 'return', 'wapas karna', 'badalna'])) {
            return $this->return_policy_reply();
        }

        if ($this->matches_any($normalized, ['login', 'log in', 'password', 'sign up', 'signup', 'register', 'otp', 'my account'])) {
            return $this->account_reply();
        }

        if ($this->matches_any($normalized, ['contact', 'phone number', 'email', 'address', 'whatsapp', 'call you'])) {
            return $this->contact_reply();
        }

        if ($this->matches_any($normalized, ['product inquiry', 'product enquiry', 'search', 'looking for', 'do you have', 'in stock', 'availability', 'product', 'browse', 'catalogue', 'catalog'])) {
            return $this->start_product_enquiry_flow();
        }

        // Anything else that reads like a product name gets searched instead of dead-ending on
        // the menu - a visitor typing "silver earrings" wants results, not a list of options.
        if (str_word_count($normalized) <= 6 && strlen($normalized) >= 3) {
            $matches = $this->reply_with_product_matches($message, true);
            if ($matches !== null) {
                return $matches;
            }
        }

        return $this->fallback_reply();
    }

    /* ------------------------------------------------------------------ *
     * Flows
     * ------------------------------------------------------------------ */

    private function start_order_tracking_flow() {
        $this->session->set_userdata('chat_state', self::STATE_ORDER_ID);
        $chips = [];
        if ($this->get_chat_user_id() > 0) {
            $chips[] = $this->chip('🧾 Show my orders', 'my orders', 'my_orders');
        }
        return $this->msg(
            'Sure — send me your Order ID and I will look it up. You can find it on the order confirmation email or under My Orders.',
            $this->follow_up_chips($chips)
        );
    }

    private function start_payment_issue_flow() {
        $this->session->set_userdata('chat_state', self::STATE_PAYMENT_ISSUE);
        return $this->msg(
            "Tell me what happened with the payment — for example \"amount debited but no order\", \"payment pending\" or \"refund not received\" — and mention the payment method if you can.",
            $this->follow_up_chips([$this->chip('💰 Refund status', 'refund', '')])
        );
    }

    private function start_product_enquiry_flow() {
        $this->session->set_userdata('chat_state', self::STATE_PRODUCT_NAME);
        return $this->msg(
            'What are you looking for? Send me a product name or a keyword and I will show you what we have.',
            $this->follow_up_chips()
        );
    }

    private function reply_with_order_status($order_id) {
        $order_id = trim($order_id);

        // People paste "#16" or "order 16" as often as a bare number.
        if (preg_match('/(\d{1,10})/', $order_id, $m)) {
            $order_id = $m[1];
        }

        if (!ctype_digit($order_id)) {
            return $this->msg(
                "That does not look like an Order ID. It is a number, like 1043 — you will find it under My Orders.",
                $this->follow_up_chips([$this->chip('📦 Try again', 'track order', 'track_order')])
            );
        }

        $user_id = $this->get_chat_user_id();
        if ($user_id <= 0) {
            return $this->msg(
                'Please log in to your account first — order details are only shown to the account that placed the order.',
                $this->follow_up_chips([$this->chip('🔐 Login help', 'login', 'account')])
            );
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
            return $this->msg(
                'I could not find order #' . (int) $order_id . ' under your account. Please double-check the ID.',
                $this->follow_up_chips([
                    $this->chip('🧾 Show my orders', 'my orders', 'my_orders'),
                    $this->chip('🙋 Talk to support', 'customer support', 'support'),
                ])
            );
        }

        $status = $this->get_order_status_from_database((int) $order_id, $order);
        $estimated_delivery = $this->get_estimated_delivery_from_database($order);

        $text = 'Order #' . $order['id'] . ' — ' . $this->humanise_status($status);
        if ($estimated_delivery !== '') {
            $text .= "\nEstimated delivery: " . $estimated_delivery;
        }

        return $this->msg($text, $this->follow_up_chips([
            $this->chip('📦 Track another', 'track order', 'track_order'),
            $this->chip('↩️ Return an item', 'return item', 'return_item'),
        ]));
    }

    /**
     * The widget used to have no way to answer "what did I order?" - people had to know an
     * Order ID before the bot could tell them anything at all.
     */
    private function recent_orders_reply()
    {
        $user_id = $this->get_chat_user_id();
        if ($user_id <= 0) {
            return $this->msg(
                'Log in and I can list your recent orders here.',
                $this->follow_up_chips([$this->chip('🔐 Login help', 'login', 'account')])
            );
        }

        $select = ['o.id', 'o.final_total', 'o.date_added'];
        if ($this->db->field_exists('active_status', 'orders')) {
            $select[] = 'o.active_status';
        }

        $orders = $this->db->select(implode(',', $select))
            ->where('o.user_id', $user_id)
            ->order_by('o.id', 'DESC')
            ->limit(4)
            ->get('orders o')
            ->result_array();

        if (empty($orders)) {
            return $this->msg(
                "You do not have any orders yet. Once you place one it will show up here.",
                $this->follow_up_chips([$this->chip('🛍️ Find a product', 'product inquiry', 'product_inquiry')])
            );
        }

        $lines = [];
        foreach ($orders as $order) {
            $status = $this->humanise_status($this->get_order_status_from_database((int) $order['id'], $order));
            $lines[] = '#' . $order['id'] . ' · ' . $this->format_price($order['final_total']) . ' · ' . $status;
        }

        return $this->msg(
            "Your latest orders:\n" . implode("\n", $lines),
            $this->follow_up_chips([
                $this->chip('📦 Track one', 'track order', 'track_order'),
                $this->chip('🧾 Open My Orders', 'my orders page', ''),
            ]),
            [[
                'type'  => 'link',
                'title' => 'View all orders',
                'body'  => 'Invoices, item status and returns',
                'url'   => base_url('my-account/orders'),
            ]]
        );
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

        return '';
    }

    /**
     * `order_items.active_status` holds raw values like `awaiting`, `received`, `out_for_delivery`.
     * They were shown to customers verbatim ("is currently out_for_delivery").
     */
    private function humanise_status($status)
    {
        if ($status === '') {
            return 'status not available yet';
        }

        $labels = [
            'awaiting'         => 'Awaiting confirmation',
            'received'         => 'Received',
            'pending'          => 'Pending',
            'confirmed'        => 'Confirmed',
            'processed'        => 'Being prepared',
            'processing'       => 'Being prepared',
            'shipped'          => 'Shipped',
            'out_for_delivery' => 'Out for delivery',
            'delivered'        => 'Delivered ✅',
            'cancelled'        => 'Cancelled',
            'canceled'         => 'Cancelled',
            'returned'         => 'Returned',
            'returned_request' => 'Return requested',
            'return_request'   => 'Return requested',
            'refunded'         => 'Refunded',
        ];

        $parts = array_map('trim', explode(',', $status));
        $out = [];
        foreach ($parts as $part) {
            $key = strtolower($part);
            $out[] = isset($labels[$key]) ? $labels[$key] : ucfirst(str_replace('_', ' ', $part));
        }

        // A multi-seller order can legitimately have items in different states.
        return count($out) > 1 ? 'items are ' . implode(' / ', $out) : $out[0];
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

        if ($date !== '') {
            $stamp = strtotime($date);
            if ($stamp !== false) {
                $date = date('d M Y', $stamp);
            }
        }

        // Was "Not available", which the caller then printed as
        // "Estimated delivery: Not available." on every single order, because no order in the
        // system carries a delivery date. An empty string lets the caller drop the line.
        return trim($date . ' ' . $time);
    }

    private function reply_with_payment_issue($message) {
        $normalized = $this->normalize($message);
        $chips = $this->follow_up_chips([$this->chip('🙋 Talk to support', 'customer support', 'support')]);

        if ($this->matches_any($normalized, ['deducted', 'debited', 'money taken', 'amount deducted', 'no order'])) {
            return $this->msg('If the amount was debited but no order was created, the payment is usually auto-reversed within 30 minutes. If it has been longer than that, raise a support ticket with the transaction reference and we will trace it.', $chips);
        }
        if ($this->matches_any($normalized, ['pending', 'processing', 'waiting'])) {
            return $this->msg('Your payment looks pending. Please do not pay again for the same order — a duplicate charge takes longer to reverse than the pending one takes to settle.', $chips);
        }
        if ($this->matches_any($normalized, ['refund', 'money back', 'refund pending'])) {
            return $this->refund_reply();
        }
        if ($this->matches_any($normalized, ['upi', 'gpay', 'phonepe', 'paytm'])) {
            return $this->msg('For UPI, check the app for the transaction ID and whether it says SUCCESS. Share that reference with support and we can match it to your order straight away.', $chips);
        }
        if ($this->matches_any($normalized, ['card', 'visa', 'mastercard', 'credit card', 'debit card'])) {
            return $this->msg('For card payments, check whether your bank actually approved the transaction. Failed authorisations are released by the bank automatically, usually within a few working days.', $chips);
        }
        if ($this->matches_any($normalized, ['cod', 'cash on delivery'])) {
            return $this->shipping_reply();
        }

        return $this->msg('Thanks — to look into this we need your Order ID and the payment reference number. Raise a support ticket with both and our team will pick it up on the ticket.', $chips);
    }

    private function refund_reply()
    {
        return $this->msg(
            'Refunds are issued once a return is approved or an order is cancelled. Money back to a card or UPI account usually takes 5–7 working days depending on your bank; refunds to your ' . $this->store_name() . ' wallet are instant.',
            $this->follow_up_chips([
                $this->chip('↩️ Return an item', 'return item', 'return_item'),
                $this->chip('🙋 Talk to support', 'customer support', 'support'),
            ])
        );
    }

    /**
     * @param bool $soft when true, returns null instead of a "nothing found" message, so an
     *                   ordinary sentence that happened to be short is not answered with a
     *                   product-search failure.
     */
    /**
     * @param bool $soft when true, returns null instead of a "nothing found" message, so an
     *                   ordinary sentence that happened to be short is not answered with a
     *                   product-search failure.
     */
    private function reply_with_product_matches($product_name, $soft = false) {
        $product_name = trim($product_name);

        if ($product_name === '') {
            return $soft ? null : $this->no_products_reply($product_name);
        }

        /* This used to be a hand-rolled query over `products` filtered only on p.status, which
         * is NOT what the storefront shows: fetch_product() also requires pv.status,
         * sd.status and p.listing_visibility = 1 (the seller's plan listing cap) and applies
         * the GST state restriction. So the chat happily listed products whose own detail page
         * immediately redirects to /products - on this database that is 176 of 290 products.
         * Reusing the platform's own read means a result can always be opened. */
        $user_id = $this->get_chat_user_id();
        $filter = ['search' => $product_name];
        if (function_exists('get_customer_state')) {
            $filter['customer_state'] = get_customer_state();
        }

        $result = fetch_product($user_id > 0 ? $user_id : null, $filter, null, null, 5, 0);
        $products = (!empty($result['product']) && is_array($result['product'])) ? $result['product'] : [];

        if (empty($products)) {
            return $soft ? null : $this->no_products_reply($product_name);
        }

        $cards = [];
        foreach ($products as $product) {
            // Seller and product names come out of the DB with the escaping slashes the
            // platform's escape_array() baked in, so the bot was quoting shops as
            // "Developer's Den". Every other view runs values through output_escaping();
            // this is the same stripcslashes() it uses.
            $seller_name = !empty($product['store_name']) ? $product['store_name'] : (isset($product['seller_name']) ? $product['seller_name'] : '');
            $seller_name = trim(stripcslashes((string) $seller_name));

            $cards[] = [
                'type'  => 'product',
                'title' => stripcslashes((string) $product['name']),
                'body'  => $seller_name !== '' ? 'by ' . $seller_name : '',
                'price' => $this->product_price_label($product),
                // fetch_product() has already put the full URL on `image`; `image_sm` is the
                // thumbnail, which is all a 46px card needs.
                'image' => !empty($product['image_sm']) ? $product['image_sm'] : (!empty($product['image']) ? $product['image'] : ''),
                // Product results used to be plain text with no link at all, so the one thing a
                // shopper wanted to do next - open the product - was impossible from the chat.
                'url'   => !empty($product['slug']) ? base_url('products/details/' . $product['slug']) : base_url('products'),
            ];
        }

        /* Phrased to stand on its own. The transcript stores message text only, so a reply
         * ending in a colon ("Here are the top 5 matches for X:") reads as truncated when the
         * conversation is replayed from history with no cards under it. */
        $count = count($cards);
        $text = $count === 1
            ? 'I found one match for "' . $product_name . '".'
            : 'I found ' . ($count === 5 ? 'these top 5 matches' : 'these ' . $count . ' matches') . ' for "' . $product_name . '".';

        return $this->msg($text, $this->follow_up_chips([
            $this->chip('🔍 Search again', 'product inquiry', 'product_inquiry'),
            $this->chip('🚚 Shipping', 'shipping info', 'shipping'),
        ]), $cards);
    }

    /**
     * A product with variants spans a price range, and a special price is the one people pay.
     */
    private function product_price_label($product)
    {
        $range = isset($product['min_max_price']) && is_array($product['min_max_price']) ? $product['min_max_price'] : [];

        $min = 0;
        if (!empty($range['special_price'])) {
            $min = $range['special_price'];
        } elseif (!empty($range['min_price'])) {
            $min = $range['min_price'];
        }

        if (empty($min)) {
            return '';
        }

        $max = !empty($range['max_price']) ? $range['max_price'] : $min;
        if ((float) $max > (float) $min) {
            return $this->format_price($min) . ' – ' . $this->format_price($max);
        }

        return $this->format_price($min);
    }

    private function no_products_reply($product_name)
    {
        $text = $product_name !== ''
            ? 'I could not find anything matching "' . $product_name . '". Try a shorter keyword — "earrings" instead of "silver drop earrings for wedding".'
            : 'Tell me a product name or keyword and I will search for you.';

        /* "Browse the shop" was a chip carrying no action and the free text "browse", which
         * matches no intent - so it answered with the generic "I did not quite get that",
         * the same dead-end this whole pass is about. The card below already offers exactly
         * that destination, so the chip was redundant as well as broken. */
        return $this->msg($text, $this->follow_up_chips([
            $this->chip('🔍 Try another word', 'product inquiry', 'product_inquiry'),
        ]), [[
            'type'  => 'link',
            'title' => 'Browse all products',
            'body'  => 'See the full ' . $this->store_name() . ' catalogue',
            'url'   => base_url('products'),
        ]]);
    }

    private function cancel_policy_reply()
    {
        return $this->msg(
            'You can cancel from My Account → My Orders → Cancel while the order is still being prepared. Once it has shipped, cancellation is handled by our team — raise a support ticket and we will check whether it can still be stopped.',
            $this->follow_up_chips([
                $this->chip('📦 Track order', 'track order', 'track_order'),
                $this->chip('🙋 Talk to support', 'customer support', 'support'),
            ])
        );
    }

    private function return_policy_reply() {
        $return_days = $this->get_return_window_days();

        if ($return_days !== '') {
            // The setting is often 1 on a new store, which rendered as "within 1 days".
            $unit = ((int) $return_days === 1) ? ' day' : ' days';
            $text = 'Items can be returned within ' . (int) $return_days . $unit . ' of delivery, if the product page marks them as returnable. Start it from My Account → My Orders → Return.';
        } else {
            $text = 'Items can be returned within the return window shown on the product page. Start it from My Account → My Orders → Return.';
        }

        return $this->msg($text, $this->follow_up_chips([
            $this->chip('💰 Refund status', 'refund', ''),
            $this->chip('🙋 Talk to support', 'customer support', 'support'),
        ]), [[
            'type'  => 'link',
            'title' => 'Go to My Orders',
            'body'  => 'Start a return or exchange',
            'url'   => base_url('my-account/orders'),
        ]]);
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

    private function shipping_reply()
    {
        $settings = function_exists('get_settings') ? get_settings('system_settings', true) : [];
        $lines = ['Delivery charges are calculated at checkout from your pincode and the seller’s location, so the exact amount is shown before you pay.'];

        if (!empty($settings['min_amount_for_free_delivery'])) {
            $lines[] = 'Orders over ' . $this->format_price($settings['min_amount_for_free_delivery']) . ' ship free.';
        }
        if (isset($settings['is_cod_allowed']) && (string) $settings['is_cod_allowed'] === '1') {
            $lines[] = 'Cash on Delivery is available on eligible pincodes.';
        }
        $lines[] = 'Most orders are handmade to order, so dispatch usually takes a few working days — the product page shows the seller’s own timeline.';

        return $this->msg(implode("\n", $lines), $this->follow_up_chips([
            $this->chip('📦 Track order', 'track order', 'track_order'),
            $this->chip('↩️ Returns', 'return item', 'return_item'),
        ]));
    }

    /**
     * Offers used to be answered with a card pointing at base_url() - the home page. On the
     * home page that is a navigation to the page you are already on, so it read as a dead
     * button; and there is no offers page on this storefront to point at instead. Worse, the
     * `offers` banner table is routinely empty, so even a working link showed nothing.
     *
     * A customer asking about offers wants the codes, so the codes are what they now get.
     */
    private function offers_reply()
    {
        $codes = $this->usable_promo_codes();

        if (empty($codes)) {
            return $this->msg(
                "There are no coupon codes running just now. Discounts on individual products are shown on the product page itself, and any offer you are eligible for is applied in your cart before you pay.",
                $this->follow_up_chips([
                    $this->chip('🛍️ Find a product', 'product inquiry', 'product_inquiry'),
                ])
                // Deliberately no card: with no codes and no offers page there is nowhere
                // useful to send anyone, and an inert card reads as broken.
            );
        }

        $lines = [count($codes) === 1 ? 'One code you can use right now:' : 'Codes you can use right now:'];
        foreach ($codes as $code) {
            $lines[] = '';
            $lines[] = '🏷️ ' . $code['code'] . ' — ' . $code['benefit'];
            foreach ($code['notes'] as $note) {
                $lines[] = '   ' . $note;
            }
        }
        $lines[] = '';
        $lines[] = 'Enter the code in your cart before checkout — the discount is shown before you pay.';

        return $this->msg(implode("\n", $lines), $this->follow_up_chips([
            $this->chip('🛍️ Find a product', 'product inquiry', 'product_inquiry'),
        ]), $this->cart_card());
    }

    /**
     * Publicly listable promo codes that this particular visitor could actually redeem.
     *
     * The listing gate (status, date window, and `list_promocode` so targeted codes stay
     * private) comes from Promo_code_model::get_promo_codes(), which is the same read the
     * mobile API and My Account use. Redeemability is then settled by the platform's own
     * validate_promo_code() rather than re-implemented here - it owns the global no_of_users
     * cap, the already-redeemed check and the per-customer repeat quota, and duplicating any
     * of that is how the bot would end up advertising a code checkout then rejects.
     */
    private function usable_promo_codes()
    {
        $this->load->model('Promo_code_model');

        // Explicit sort: the model's own default is 'u.id', an alias that does not exist in
        // its query, so leaving it out would be a SQL error.
        $listed = $this->Promo_code_model->get_promo_codes(25, 0, 'id', 'DESC');
        $rows = (!empty($listed['data']) && is_array($listed['data'])) ? $listed['data'] : [];
        if (empty($rows)) {
            return [];
        }

        $user_id = $this->get_chat_user_id();

        /* validate_promo_code() applies the minimum-order gate against the cart total we hand
         * it. We are not pricing a cart here, so pass a total high enough to clear any
         * threshold and surface the real minimum to the customer as a note instead - otherwise
         * every code with a minimum would look unavailable to a browsing visitor. */
        $probe_total = 100000000;

        $usable = [];
        foreach ($rows as $row) {
            if (count($usable) >= self::MAX_PROMO_CODES) {
                break;
            }

            $code = trim(stripcslashes((string) $row['promo_code']));
            if ($code === '') {
                continue;
            }

            if (function_exists('validate_promo_code')) {
                $check = validate_promo_code($code, $user_id, $probe_total);
                // A guest has no redemption history, so only the campaign-wide caps can
                // exclude a code for them - which is the right answer before they sign in.
                if (!empty($check['error'])) {
                    continue;
                }
            }

            $usable[] = [
                'code'    => $code,
                'benefit' => $this->promo_benefit_label($row),
                'notes'   => $this->promo_notes($row),
            ];
        }

        return $usable;
    }

    /** "20% off, up to Rs.500" / "Rs.100 off" / cashback wording. */
    private function promo_benefit_label($row)
    {
        $discount = isset($row['discount']) ? (float) $row['discount'] : 0;
        $is_cashback = !empty($row['is_cashback']);
        $word = $is_cashback ? ' cashback' : ' off';

        if (isset($row['discount_type']) && strtolower((string) $row['discount_type']) === 'percentage') {
            $label = rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.') . '%' . $word;
            if (!empty($row['max_discount_amt'])) {
                $label .= ', up to ' . $this->format_price($row['max_discount_amt']);
            }
            return $label;
        }

        return $this->format_price($discount) . $word;
    }

    /** The conditions worth stating up front, so nothing is a surprise at checkout. */
    private function promo_notes($row)
    {
        $notes = [];

        if (!empty($row['min_order_amt']) && (float) $row['min_order_amt'] > 0) {
            $notes[] = 'on orders over ' . $this->format_price($row['min_order_amt']);
        }

        if (!empty($row['end_date'])) {
            $end = strtotime((string) $row['end_date']);
            if ($end !== false) {
                $notes[] = 'valid until ' . date('d M Y', $end);
            }
        }

        if (!empty($row['is_cashback'])) {
            $notes[] = 'credited to your wallet after the order';
        }

        $message = isset($row['message']) ? trim(stripcslashes((string) $row['message'])) : '';
        if ($message !== '') {
            $notes[] = $message;
        }

        return $notes;
    }

    /**
     * A card only when there is something real behind it: the cart, and only if the customer
     * has something in it to apply the code to.
     */
    private function cart_card()
    {
        $user_id = $this->get_chat_user_id();
        if ($user_id <= 0 || !$this->db->table_exists('cart')) {
            return [];
        }

        $row = $this->db->select('COUNT(id) AS items')
            ->where('user_id', $user_id)
            ->where('is_saved_for_later', 0)
            ->get('cart')
            ->row_array();

        if (empty($row['items'])) {
            return [];
        }

        return [[
            'type'  => 'link',
            'title' => 'Apply it in your cart',
            'body'  => ((int) $row['items'] === 1) ? '1 item waiting' : (int) $row['items'] . ' items waiting',
            'url'   => base_url('cart'),
        ]];
    }

    private function account_reply()
    {
        return $this->msg(
            'You can sign in with your mobile number and password, or with Google. If a login OTP has not arrived, wait a minute and request it again — and check that the number is the one you registered with.',
            $this->follow_up_chips([$this->chip('🙋 Talk to support', 'customer support', 'support')]),
            [[
                'type'  => 'link',
                'title' => 'Go to My Account',
                'body'  => 'Profile, addresses and orders',
                'url'   => base_url('my-account'),
            ]]
        );
    }

    private function contact_reply()
    {
        $settings = function_exists('get_settings') ? get_settings('system_settings', true) : [];
        $lines = ['Here is how to reach a person on our team:'];

        if (!empty($settings['support_email'])) {
            $lines[] = '✉️ ' . stripcslashes($settings['support_email']);
        }
        if (!empty($settings['support_number'])) {
            $lines[] = '📞 ' . stripcslashes($settings['support_number']);
        }
        $lines[] = 'Support tickets are the fastest route — they go straight to the team that can act on your order.';

        $cards = [];
        $whatsapp = function_exists('whatsapp_support_link') ? whatsapp_support_link() : '';
        if (!empty($whatsapp)) {
            $cards[] = [
                'type'  => 'link',
                'title' => 'Chat on WhatsApp',
                'body'  => 'Message our support team',
                'url'   => $whatsapp,
            ];
        }

        return $this->msg(implode("\n", $lines), $this->follow_up_chips([
            $this->chip('🎫 Raise a ticket', 'customer support', 'support'),
        ]), $cards);
    }

    private function support_handoff_reply()
    {
        if ($this->get_chat_user_id() <= 0) {
            return $this->msg(
                'Our team answers on support tickets. Please log in first, then open My Account → Support to raise one — you will get replies on the ticket and can follow its status there.',
                $this->follow_up_chips([$this->chip('🔐 Login help', 'login', 'account')]),
                [[
                    'type'  => 'link',
                    'title' => 'Log in to raise a ticket',
                    'body'  => 'Then head to My Account → Support',
                    'url'   => base_url('login'),
                ]]
            );
        }

        $cards = [[
            'type'  => 'link',
            'title' => 'Raise a support ticket',
            'body'  => 'Our team replies on the ticket itself',
            'url'   => base_url('my-account/support'),
        ]];

        $whatsapp = function_exists('whatsapp_support_link') ? whatsapp_support_link() : '';
        if (!empty($whatsapp)) {
            $cards[] = [
                'type'  => 'link',
                'title' => 'Chat on WhatsApp',
                'body'  => 'For a quick question',
                'url'   => $whatsapp,
            ];
        }

        return $this->msg(
            'Happy to hand you over. Raise a support ticket and a human from our team picks it up — you will see their replies on the ticket and can attach photos there too.',
            $this->follow_up_chips([$this->chip('📞 Contact details', 'contact', 'contact')]),
            $cards
        );
    }

    private function fallback_reply() {
        return $this->msg(
            "Sorry, I did not quite get that. I am best at orders, shipping, returns, payments and finding products — pick one below, or ask for a human and I will hand you over.",
            $this->main_menu_chips()
        );
    }

    /* ------------------------------------------------------------------ *
     * Plumbing
     * ------------------------------------------------------------------ */

    /**
     * Single exit point for the endpoint. Always emits a JSON content type (the widget was
     * parsing a text/html response) and always returns a fresh CSRF hash so a long-lived
     * widget can keep posting - this endpoint used to 403 on every single message because the
     * widget sent no CSRF token at all and `chat/send` is not in csrf_exclude_uris.
     */
    private function respond($reply)
    {
        $this->respond_json([
            'error'         => false,
            'reply'         => $reply['text'],
            'quick_replies' => $reply['quick_replies'],
            'cards'         => $reply['cards'],
            'time'          => $this->display_time(),
            'messages'      => [],
        ]);
    }

    private function respond_json($payload)
    {
        if (isset($this->security)) {
            $payload['csrfName'] = $this->security->get_csrf_token_name();
            $payload['csrfHash'] = $this->security->get_csrf_hash();
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function log_message_row($user_id, $message, $sender)
    {
        $message = (string) $message;
        if ($message === '') {
            return;
        }

        // `chat_messages.message` is NOT NULL, so an empty body would abort the insert and -
        // with db_debug on - print a database error page into the middle of the JSON response.
        $thread = $this->chat_thread_id();

        $this->db->insert('chat_messages', [
            'chat_session_id' => 1,
            'user_id' => $user_id > 0 ? $user_id : null,
            'session_id' => $thread !== '' ? $thread : null,
            'sender' => in_array($sender, ['user', 'agent'], true) ? $sender : 'user',
            // is_read is only meaningful for the customer's own messages (it tells support what
            // it has not looked at yet); the bot's own replies are read by definition.
            'is_read' => $sender === 'user' ? 0 : 1,
            'message' => mb_substr($message, 0, 5000)
        ]);
    }

    /**
     * Stable identifier for one visitor's conversation.
     *
     * This used to be the raw session_id, but config.php sets sess_time_to_update = 300 with
     * sess_regenerate_destroy = TRUE: CodeIgniter mints a new session id every five minutes.
     * So a guest's transcript became unreadable after five minutes and the rows already
     * written were orphaned - unreachable by any later request. Session *userdata* does carry
     * across a regeneration, so a token kept there stays valid for the whole session.
     */
    private function chat_thread_id()
    {
        $thread = $this->session->userdata('chat_thread_id');

        if (empty($thread) || !is_string($thread)) {
            try {
                $thread = bin2hex(random_bytes(16));
            } catch (Exception $e) {
                // random_bytes() can only fail if the platform has no CSPRNG; this id is not a
                // security token, so a weaker unique value is an acceptable fallback.
                $thread = md5(uniqid((string) session_id(), true));
            }
            $this->session->set_userdata('chat_thread_id', $thread);
        }

        return substr($thread, 0, 128);
    }

    private function get_chat_user_id() {
        $user_id = $this->session->userdata('user_id');
        return !empty($user_id) && ctype_digit((string) $user_id) ? (int) $user_id : 0;
    }

    /** First name of the signed-in customer, for the greeting. */
    private function get_customer_name()
    {
        $user_id = $this->get_chat_user_id();
        if ($user_id <= 0) {
            return '';
        }

        $row = $this->db->select('username')->where('id', $user_id)->limit(1)->get('users')->row_array();
        if (empty($row['username'])) {
            return '';
        }

        $name = trim(stripcslashes((string) $row['username']));
        $first = strtok($name, ' ');
        return $first !== false ? $first : '';
    }

    private function store_name()
    {
        if (function_exists('get_settings')) {
            $settings = get_settings('system_settings', true);
            if (!empty($settings['app_name'])) {
                return stripcslashes($settings['app_name']);
            }
        }
        return 'our';
    }

    private function display_time($stamp = null)
    {
        $time = $stamp === null ? time() : strtotime((string) $stamp);
        if ($time === false) {
            $time = time();
        }
        return date('g:i A', $time);
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
        return (bool) preg_match('/^\s*(hello|hi|hii+|hey|yo|namaste|namaskar|hola|good morning|good afternoon|good evening|start|menu|नमस्ते)\b[\s!.?]*$/iu', $message);
    }

    private function format_price($price) {
        if ($price === null || $price === '') {
            return 'Not available';
        }

        if (is_numeric($price)) {
            $price = (float) $price;
            // Whole-rupee amounts read better without ".00" in a chat bubble.
            return '₹' . number_format($price, ($price == (int) $price) ? 0 : 2);
        }

        return '₹' . $price;
    }
}
