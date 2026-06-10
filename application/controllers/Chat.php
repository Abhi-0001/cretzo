<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {

    public function send() {
        $message = trim((string) $this->input->post('message'));
        $action = trim((string) $this->input->post('action'));
        $order_id = trim((string) $this->input->post('order_id'));

        $this->output->set_content_type('application/json');

        if ($message === '' && $action === '') {
            echo json_encode([
                'reply' => 'Please type a message first.'
            ]);
            return;
        }

        $this->save_message($message, 'user');

        $reply = $this->get_reply($message, $action, $order_id);

        $this->save_message($reply, 'bot');

        echo json_encode([
            'reply' => $reply
        ]);
    }


    private function get_reply($message, $action, $order_id) {
        switch ($action) {
            case 'track_order':
                return $this->track_order($order_id ?: $message); 
            
            case 'cancel_order':
                return 'To cancel an order, open My Account → My Orders, choose the order, then tap Cancel. If the order is already packed/shipped, please share the order ID with support so we can check whether cancellation is still possible.';
            case 'return_item':
                return 'For returns, open My Account → My Orders → Return. Keep the product unused with original packaging. If the return button is unavailable, the item may be outside the return window or already processed.';
            case 'payment_issue':
                return 'For payment issues, first check whether the amount was debited. If it was debited but the order was not created, wait a few minutes and share the payment reference/order ID with support for verification.';
            case 'product_inquiry':
                return 'For product questions, send the product name or product link. I can help with availability, delivery options, size/variant confusion, and seller questions.';
            case 'support':
                return 'I can connect you with support. Please describe your issue in one message with any order ID, payment reference, or product link so the team can help faster.';
        }

        if (ctype_digit($message)) {
            return $this->track_order($message);
        }
        if (stripos($message, 'track') !== false || stripos($message, 'where is my order') !== false || stripos($message, 'order') !== false) {
            return 'Please enter your Order ID so I can estimate the delivery timeline.';
        }
        if (stripos($message, 'cancel') !== false) {
            return $this->get_reply($message, 'cancel_order', '');
        }
        if (stripos($message, 'return') !== false) {
            return $this->get_reply($message, 'return_item', '');
        }
        if (stripos($message, 'payment') !== false) {
            return $this->get_reply($message, 'payment_issue', '');
        }
        if (stripos($message, 'product') !== false) {
            return $this->get_reply($message, 'product_inquiry', '');
        }
        if (stripos($message, 'support') !== false || stripos($message, 'help') !== false) {
            return $this->get_reply($message, 'support', '');
        }

        return "Thanks 👍 We'll help you shortly. You can also choose Track Order, Cancel Order, Return Item, Payment Issue, Product Inquiry, or Support for faster help.";
    }

    private function track_order($order_id) {
        $order_id = preg_replace('/[^0-9]/', '', (string) $order_id);

        if ($order_id === '') {
            return 'Please enter a valid numeric Order ID so I can check the expected arrival.';
        }

        if (!$this->db->table_exists('orders')) {
            return 'I could not access order tracking right now. Please share your Order ID with support and we will check it manually.';
        }

        $this->db->select('id, user_id, delivery_date, delivery_time, date_added');
        $this->db->where('id', $order_id);

        if ($this->ion_auth->logged_in()) {
            $this->db->where('user_id', (int) $this->session->userdata('user_id'));
        }

        $order = $this->db->get('orders')->row_array();

        if (empty($order)) {
            return 'I could not find Order #' . $order_id . '. Please check the ID and try again, or contact support if you placed the order as a guest.';
        }

        $status = $this->get_order_status($order_id);
        if (in_array($status, ['delivered', 'cancelled', 'returned'], true)) {
            return 'Order #' . $order_id . ' is already marked as ' . ucfirst($status) . '.';
        }

        $estimated_date = $this->get_estimated_delivery_date($order);
        if ($estimated_date === '') {
            return 'Order #' . $order_id . ' is ' . ucfirst($status ?: 'being processed') . '. I could not find an estimated delivery date, so please contact support for the latest update.';
        }

        $today = new DateTime(date('Y-m-d'));
        $delivery = new DateTime($estimated_date);
        $days_left = (int) $today->diff($delivery)->format('%r%a');

        if ($days_left > 1) {
            $arrival = $days_left . ' days left';
        } elseif ($days_left === 1) {
            $arrival = '1 day left';
        } elseif ($days_left === 0) {
            $arrival = 'arriving today';
        } else {
            $arrival = 'delivery date has passed; please contact support for an updated ETA';
        }

        return 'Order #' . $order_id . ' is ' . ucfirst($status ?: 'in progress') . '. Estimated delivery: ' . date('d M Y', strtotime($estimated_date)) . ' (' . $arrival . ').';
    }

    private function get_order_status($order_id) {
        if (!$this->db->table_exists('order_items')) {
            return '';
        }

        $this->db->select('active_status');
        $this->db->where('order_id', $order_id);
        $this->db->order_by('id', 'DESC');
        $item = $this->db->get('order_items')->row_array();

        return !empty($item['active_status']) ? $item['active_status'] : '';
    }

    private function get_estimated_delivery_date($order) {
        if (!empty($order['delivery_date']) && $order['delivery_date'] !== '0000-00-00') {
            return date('Y-m-d', strtotime($order['delivery_date']));
        }

        if (!empty($order['date_added'])) {
            return date('Y-m-d', strtotime($order['date_added'] . ' + 7 days'));
        }

        return '';
    }

    private function save_message($message, $sender) {
        if ($message === '') {
            return;
        }

        if (!$this->db->table_exists('chat_messages')) {
            log_message('debug', 'Skipping floating chat persistence because chat_messages table does not exist.');
            return;
        }

        $this->db->insert('chat_messages', [
            'user_id' => (int) $this->session->userdata('user_id'),
            'message' => $message,
            'sender' => $sender
        ]);
    }
}