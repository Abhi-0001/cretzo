<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller notifications.
 *
 * The seller panel had no notification surface whatsoever - no bell, no list, no page. Sellers
 * are notified about new orders, settlements and ticket replies, but every one of those channels
 * was push-only (FCM) or admin-only, and FCM is unconfigured on this deployment (the server key
 * is still the literal string "your_fcm_server_key"), so in practice a seller was never told
 * anything at all.
 *
 * This reads the same `notifications` table the customer side reads, scoped through
 * Notification_model::get_user_inbox() so a seller sees broadcasts addressed to all users or to
 * sellers, plus anything addressed to them by id - and nothing else.
 */
class Notifications extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model('notification_model');
    }

    /** Panel access, matching every other seller controller: status 2 or 7 is refused. */
    private function seller_allowed()
    {
        return ($this->ion_auth->logged_in() && $this->ion_auth->is_seller()
            && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0));
    }

    private function notification_json($payload)
    {
        $payload['csrfName'] = $this->security->get_csrf_token_name();
        $payload['csrfHash'] = $this->security->get_csrf_hash();
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }

    public function index()
    {
        if (!$this->seller_allowed()) {
            redirect('seller/login', 'refresh');
            return;
        }

        $settings = get_settings('system_settings', true);
        $this->data['main_page'] = TABLES . 'notifications';
        $this->data['title'] = 'Notifications | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Notifications | ' . $settings['app_name'];
        $this->load->view('seller/template', $this->data);
    }

    /** Paginated list of this seller's notifications. */
    public function get_notifications()
    {
        if (!$this->seller_allowed()) {
            $this->notification_json(['error' => true, 'message' => 'Please log in.', 'total' => 0, 'rows' => []]);
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $limit  = (is_numeric($this->input->get('limit')) && (int) $this->input->get('limit') > 0) ? min((int) $this->input->get('limit'), 50) : 10;
        $offset = (is_numeric($this->input->get('offset')) && (int) $this->input->get('offset') > 0) ? (int) $this->input->get('offset') : 0;
        $unread_only = ($this->input->get('unread') === '1');

        $result = $this->notification_model->get_user_inbox($user_id, $limit, $offset, $unread_only, 'seller');
        $result['error'] = false;
        $result['unread'] = $this->notification_model->count_user_unread($user_id);
        $this->notification_json($result);
    }

    /** Unread count for the navbar bell, polled by the panel. */
    public function unread_count()
    {
        if (!$this->seller_allowed()) {
            $this->notification_json(['error' => true, 'unread' => 0]);
            return;
        }
        $this->notification_json([
            'error'  => false,
            'unread' => $this->notification_model->count_user_unread((int) $this->session->userdata('user_id')),
        ]);
    }

    /** Marks one notification read, or all of them when no id is given. */
    public function mark_read()
    {
        if (!$this->seller_allowed()) {
            $this->notification_json(['error' => true, 'message' => 'Please log in.']);
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $id = $this->input->post('notification_id');
        $id = (is_numeric($id) && (int) $id > 0) ? (int) $id : null;

        $ok = $this->notification_model->mark_user_read($user_id, $id);
        $this->notification_json([
            'error'   => !$ok,
            'message' => $ok ? 'Marked as read.' : 'Could not update the notification.',
            'unread'  => $this->notification_model->count_user_unread($user_id),
        ]);
    }
}
