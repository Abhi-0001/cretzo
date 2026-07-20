<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {

    public function send() {

        $message = $this->input->post('message');

        // save user message
        $this->db->insert('chat_messages', [
            'user_id' => 0,
            'message' => $message,
            'sender' => 'user'
        ]);

        // bot reply logic
        $reply = "Thanks 👍 We'll help you shortly.";

        if (stripos($message, 'order') !== false) {
            $reply = "Please enter your Order ID 📦";
        } 
        elseif (stripos($message, 'cancel') !== false) {
            $reply = "Go to My Orders → Cancel ❌";
        } 
        elseif (stripos($message, 'return') !== false) {
            $reply = "Go to Orders → Return 🔄";
        } 
        elseif (stripos($message, 'payment') !== false) {
            $reply = "Describe your payment issue 💳";
        } 
        elseif (stripos($message, 'support') !== false) {
            $reply = "Connecting to support 🎧";
        }

        // save bot reply
        $this->db->insert('chat_messages', [
            'user_id' => 0,
            'message' => $reply,
            'sender' => 'bot'
        ]);

        echo json_encode([
            'reply' => $reply
        ]);
    }
}