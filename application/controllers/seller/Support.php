<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller support tickets.
 *
 * The ticket system was admin-panel + mobile-API only until the customer half was built on
 * My_account; the seller panel still had no way at all for a seller to reach the platform team.
 * A seller's only channel was the Chat page (seller <-> customer messaging, not support) or
 * email off-platform, so seller problems - payouts, KYC, a wrong commission - left no record
 * anywhere the admin could work through.
 *
 * This is the seller half of the same system: the same `tickets` / `ticket_messages` tables, the
 * same PENDING..REOPEN statuses, and the same notify_ticket_event() triggers the admin panel
 * already reacts to. Tickets raised here are stamped raised_by = 'seller' so the admin list can
 * tell them apart from customer tickets and filter on it.
 */
class Support extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file', 'function_helper']);
        $this->load->model('ticket_model');
    }

    /**
     * Panel access, matching every other seller controller: a seller whose status is 2 or 7 is
     * refused outright. Note that a seller still awaiting approval (status 0) IS allowed in -
     * "why has my account not been approved" is one of the main reasons to raise a ticket, so
     * gating support behind approval would lock out exactly the sellers who most need it.
     */
    private function seller_allowed()
    {
        return ($this->ion_auth->logged_in() && $this->ion_auth->is_seller()
            && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0));
    }

    /** Human labels for tickets.status (PENDING..REOPEN). Mirrors the customer-side map. */
    private function ticket_status_labels()
    {
        return [
            PENDING  => ['label' => 'Pending',  'class' => 'secondary'],
            OPENED   => ['label' => 'Open',     'class' => 'info'],
            RESOLVED => ['label' => 'Resolved', 'class' => 'success'],
            CLOSED   => ['label' => 'Closed',   'class' => 'danger'],
            REOPEN   => ['label' => 'Reopened', 'class' => 'warning'],
        ];
    }

    private function ticket_json($payload)
    {
        $payload['csrfName'] = $this->security->get_csrf_token_name();
        $payload['csrfHash'] = $this->security->get_csrf_hash();
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }

    /**
     * Loads a ticket only if it belongs to the logged-in seller AND was raised from the seller
     * panel. Every endpoint below goes through here, so changing the id in the request can never
     * reach another account's ticket - the same hole the app API had.
     */
    private function get_own_ticket($ticket_id)
    {
        $ticket_id = (int) $ticket_id;
        $user_id = (int) $this->session->userdata('user_id');
        if ($ticket_id < 1 || $user_id < 1) {
            return null;
        }
        $ticket = $this->db->select('t.*, tty.title as ticket_type')
            ->join('ticket_types tty', 'tty.id = t.ticket_type_id', 'left')
            ->where('t.id', $ticket_id)
            ->where('t.user_id', $user_id)
            ->where('t.raised_by', 'seller')
            ->get('tickets t')
            ->row_array();
        return !empty($ticket) ? $ticket : null;
    }

    public function index()
    {
        if (!$this->seller_allowed()) {
            redirect('seller/login', 'refresh');
            return;
        }

        $settings = get_settings('system_settings', true);
        $this->data['main_page'] = TABLES . 'support-tickets';
        $this->data['title'] = 'Support | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Support | ' . $settings['app_name'];
        $this->data['ticket_types'] = $this->db->select('id, title')->order_by('title', 'ASC')->get('ticket_types')->result_array();
        $this->data['status_labels'] = $this->ticket_status_labels();
        $this->data['support_email'] = !empty($settings['support_email']) ? $settings['support_email'] : '';
        $this->data['open_ticket_id'] = (int) $this->input->get('ticket_id');
        $this->load->view('seller/template', $this->data);
    }

    /** Paginated list of this seller's own tickets. */
    public function get_my_tickets()
    {
        if (!$this->seller_allowed()) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.', 'total' => 0, 'rows' => []]);
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $limit  = (is_numeric($this->input->get('limit')) && (int) $this->input->get('limit') > 0) ? min((int) $this->input->get('limit'), 100) : 10;
        $offset = (is_numeric($this->input->get('offset')) && (int) $this->input->get('offset') > 0) ? (int) $this->input->get('offset') : 0;
        $search = trim((string) $this->input->get('search', true));
        $status = $this->input->get('status', true);
        $labels = $this->ticket_status_labels();

        // The count and data queries must apply exactly the same conditions, or the pagination
        // footer disagrees with the rows on screen.
        $count_builder = $this->db->where('t.user_id', $user_id)->where('t.raised_by', 'seller');
        if ($search !== '') {
            $count_builder->group_start()->like('t.subject', $search)->or_like('t.description', $search)->group_end();
        }
        if (isset($labels[(string) $status])) {
            $count_builder->where('t.status', $status);
        }
        $total = $count_builder->count_all_results('tickets t');

        $this->db->select('t.*, tty.title as ticket_type')
            ->join('ticket_types tty', 'tty.id = t.ticket_type_id', 'left')
            ->where('t.user_id', $user_id)
            ->where('t.raised_by', 'seller');
        if ($search !== '') {
            $this->db->group_start()->like('t.subject', $search)->or_like('t.description', $search)->group_end();
        }
        if (isset($labels[(string) $status])) {
            $this->db->where('t.status', $status);
        }
        $tickets = $this->db->order_by('t.last_updated', 'DESC')->limit($limit, $offset)->get('tickets t')->result_array();

        $rows = [];
        foreach ($tickets as $ticket) {
            $key = (string) $ticket['status'];
            $replies = $this->db->where('ticket_id', $ticket['id'])->count_all_results('ticket_messages');
            // "unread" from the seller's point of view: admin replies newer than the last time
            // this seller opened the thread. Session-tracked, so it degrades to 0 after a fresh
            // login rather than claiming unread messages that were already read.
            $seen = (array) $this->session->userdata('seller_ticket_seen');
            $last_seen = isset($seen[$ticket['id']]) ? (int) $seen[$ticket['id']] : 0;
            $unread = $this->db->where('ticket_id', $ticket['id'])
                ->where('user_type', 'admin')
                ->where('id >', $last_seen)
                ->count_all_results('ticket_messages');

            $rows[] = [
                'id'           => (int) $ticket['id'],
                'subject'      => html_escape((string) $ticket['subject']),
                'description'  => html_escape((string) $ticket['description']),
                'ticket_type'  => html_escape((string) $ticket['ticket_type']),
                'status'       => $key,
                'status_label' => isset($labels[$key]) ? $labels[$key]['label'] : 'Pending',
                'status_class' => isset($labels[$key]) ? $labels[$key]['class'] : 'secondary',
                'replies'      => (int) $replies,
                'unread'       => (int) $unread,
                'is_closed'    => in_array($key, [RESOLVED, CLOSED], true),
                'date_created' => $ticket['date_created'],
                'last_updated' => $ticket['last_updated'],
            ];
        }

        $this->ticket_json(['error' => false, 'total' => $total, 'rows' => $rows]);
    }

    public function create_ticket()
    {
        if (!$this->seller_allowed()) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in to raise a ticket.']);
            return;
        }

        $this->form_validation->set_rules('ticket_type_id', 'Category', 'trim|required|numeric|xss_clean');
        $this->form_validation->set_rules('subject', 'Subject', 'trim|required|min_length[4]|max_length[190]|xss_clean');
        $this->form_validation->set_rules('description', 'Description', 'trim|required|min_length[10]|max_length[5000]|xss_clean');

        if (!$this->form_validation->run()) {
            $this->ticket_json(['error' => true, 'message' => strip_tags(validation_errors())]);
            return;
        }

        $ticket_type_id = (int) $this->input->post('ticket_type_id', true);
        if ($this->db->where('id', $ticket_type_id)->count_all_results('ticket_types') < 1) {
            $this->ticket_json(['error' => true, 'message' => 'Please choose a valid category.']);
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $user = $this->db->select('email, username')->where('id', $user_id)->get('users')->row_array();

        // Rate limit: a double-tapped submit button used to be able to create an unbounded
        // number of identical tickets, which is what buries the real one.
        $recent = $this->db->where('user_id', $user_id)
            ->where('date_created >', date('Y-m-d H:i:s', strtotime('-1 minute')))
            ->count_all_results('tickets');
        if ($recent >= 3) {
            $this->ticket_json(['error' => true, 'message' => 'You have raised several tickets just now. Please wait a moment before creating another.']);
            return;
        }

        $insert_id = $this->ticket_model->add_ticket([
            'ticket_type_id' => $ticket_type_id,
            'user_id'        => $user_id,
            'subject'        => $this->input->post('subject', true),
            'email'          => !empty($user['email']) ? $user['email'] : '',
            'description'    => $this->input->post('description', true),
            'status'         => PENDING,
            'raised_by'      => 'seller',
        ]);

        if (empty($insert_id)) {
            $this->ticket_json(['error' => true, 'message' => 'Could not create the ticket. Please try again.']);
            return;
        }

        notify_ticket_event($insert_id, 'created', 'user');

        $this->ticket_json([
            'error'     => false,
            'message'   => 'Ticket #' . $insert_id . ' created. Our team will reply here.',
            'ticket_id' => (int) $insert_id,
        ]);
    }

    /** Full conversation for one of this seller's own tickets. */
    public function get_ticket_thread()
    {
        if (!$this->seller_allowed()) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.', 'data' => []]);
            return;
        }

        $ticket = $this->get_own_ticket($this->input->get('ticket_id'));
        if ($ticket === null) {
            $this->ticket_json(['error' => true, 'message' => 'Ticket not found.', 'data' => []]);
            return;
        }

        $messages = $this->db->select('tm.id, tm.user_type, tm.user_id, tm.message, tm.attachments, tm.date_created, u.username')
            ->join('users u', 'u.id = tm.user_id', 'left')
            ->where('tm.ticket_id', (int) $ticket['id'])
            ->order_by('tm.id', 'ASC')
            ->get('ticket_messages tm')
            ->result_array();

        $types = $this->config->item('type');
        $rows = [];
        $last_id = 0;
        foreach ($messages as $row) {
            $last_id = max($last_id, (int) $row['id']);
            $attachments = [];
            if (!empty($row['attachments'])) {
                $decoded = json_decode($row['attachments'], true);
                // Tolerate both shapes on read: older rows stored a bare JSON string rather than
                // an array (see Ticket_model::add_ticket_message).
                if (is_string($decoded)) {
                    $decoded = [$decoded];
                }
                if (is_array($decoded)) {
                    foreach ($decoded as $file) {
                        if (!is_string($file) || trim($file) === '') {
                            continue;
                        }
                        $ext = strtolower((new SplFileInfo($file))->getExtension());
                        $kind = 'document';
                        foreach (['image', 'video', 'document', 'archive'] as $candidate) {
                            if (isset($types[$candidate]['types']) && in_array($ext, $types[$candidate]['types'], true)) {
                                $kind = $candidate;
                                break;
                            }
                        }
                        $attachments[] = ['url' => get_image_url($file), 'type' => $kind, 'name' => basename($file)];
                    }
                }
            }

            $rows[] = [
                'id'           => (int) $row['id'],
                'from_support' => ($row['user_type'] === 'admin'),
                'author'       => ($row['user_type'] === 'admin') ? 'Support Team' : html_escape((string) $row['username']),
                'message'      => html_escape((string) $row['message']),
                'attachments'  => $attachments,
                'date_created' => $row['date_created'],
            ];
        }

        // Mark the thread read for this seller so the list stops badging it.
        $seen = (array) $this->session->userdata('seller_ticket_seen');
        $seen[(int) $ticket['id']] = $last_id;
        $this->session->set_userdata('seller_ticket_seen', $seen);

        $labels = $this->ticket_status_labels();
        $key = (string) $ticket['status'];

        $this->ticket_json([
            'error'  => false,
            'ticket' => [
                'id'           => (int) $ticket['id'],
                'subject'      => html_escape((string) $ticket['subject']),
                'description'  => html_escape((string) $ticket['description']),
                'ticket_type'  => html_escape((string) $ticket['ticket_type']),
                'status'       => $key,
                'status_label' => isset($labels[$key]) ? $labels[$key]['label'] : 'Pending',
                'status_class' => isset($labels[$key]) ? $labels[$key]['class'] : 'secondary',
                'can_reply'    => ($key !== CLOSED),
                'can_close'    => !in_array($key, [RESOLVED, CLOSED], true),
                'can_reopen'   => in_array($key, [RESOLVED, CLOSED], true),
                'date_created' => $ticket['date_created'],
            ],
            'data' => $rows,
        ]);
    }

    public function reply_ticket()
    {
        if (!$this->seller_allowed()) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.']);
            return;
        }

        $ticket = $this->get_own_ticket($this->input->post('ticket_id'));
        if ($ticket === null) {
            $this->ticket_json(['error' => true, 'message' => 'Ticket not found.']);
            return;
        }
        if ((string) $ticket['status'] === CLOSED) {
            $this->ticket_json(['error' => true, 'message' => 'This ticket is closed. Please raise a new one.']);
            return;
        }

        $message = trim((string) $this->input->post('message', true));
        $attachments = $this->upload_ticket_attachments($error);
        if ($error !== '') {
            $this->ticket_json(['error' => true, 'message' => $error]);
            return;
        }
        if ($message === '' && empty($attachments)) {
            $this->ticket_json(['error' => true, 'message' => 'Please type a message or attach a file.']);
            return;
        }

        // 'user' (not 'seller'): ticket_messages.user_type is only ever read as "is this admin
        // or not" - by the admin panel's thread renderer, the app API, and
        // Ticket_model::touch_ticket_on_reply(). A third value would fall through every one of
        // those checks and render a seller's own reply as if it had come from support.
        $insert_id = $this->ticket_model->add_ticket_message([
            'user_type'   => 'user',
            'user_id'     => (int) $this->session->userdata('user_id'),
            'ticket_id'   => (int) $ticket['id'],
            'message'     => $message,
            'attachments' => $attachments,
        ]);

        if (empty($insert_id)) {
            // Do not leave orphaned uploads behind if the row could not be written.
            foreach ($attachments as $file) {
                $path = FCPATH . ltrim($file, '/');
                if (is_file($path)) {
                    unlink($path);
                }
            }
            $this->ticket_json(['error' => true, 'message' => 'Could not send your reply. Please try again.']);
            return;
        }

        notify_ticket_event((int) $ticket['id'], 'message', 'user');

        $this->ticket_json(['error' => false, 'message' => 'Reply sent.', 'message_id' => (int) $insert_id]);
    }

    /**
     * Seller-side status change: they may mark their own ticket resolved, or reopen one that was
     * resolved/closed. Anything else stays an admin decision.
     */
    public function update_ticket_status()
    {
        if (!$this->seller_allowed()) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.']);
            return;
        }

        $ticket = $this->get_own_ticket($this->input->post('ticket_id'));
        if ($ticket === null) {
            $this->ticket_json(['error' => true, 'message' => 'Ticket not found.']);
            return;
        }

        $action = (string) $this->input->post('action', true);
        $current = (string) $ticket['status'];

        if ($action === 'resolve') {
            if (in_array($current, [RESOLVED, CLOSED], true)) {
                $this->ticket_json(['error' => true, 'message' => 'This ticket is already closed.']);
                return;
            }
            $new_status = RESOLVED;
        } elseif ($action === 'reopen') {
            if (!in_array($current, [RESOLVED, CLOSED], true)) {
                $this->ticket_json(['error' => true, 'message' => 'This ticket is still open.']);
                return;
            }
            $new_status = REOPEN;
        } else {
            $this->ticket_json(['error' => true, 'message' => 'Invalid request.']);
            return;
        }

        if (!update_details(['status' => $new_status], ['id' => (int) $ticket['id']], 'tickets')) {
            $this->ticket_json(['error' => true, 'message' => 'Could not update the ticket.']);
            return;
        }

        notify_ticket_event((int) $ticket['id'], 'status', 'user');

        $labels = $this->ticket_status_labels();
        $this->ticket_json([
            'error'        => false,
            'message'      => 'Ticket marked as ' . $labels[$new_status]['label'] . '.',
            'status'       => $new_status,
            'status_label' => $labels[$new_status]['label'],
        ]);
    }

    /**
     * Handles the optional attachments[] on a seller reply. Returns the stored relative paths and
     * sets $error when anything was rejected.
     */
    private function upload_ticket_attachments(&$error)
    {
        $error = '';
        $stored = [];

        if (empty($_FILES['attachments']['name'][0])) {
            return $stored;
        }

        $target = FCPATH . TICKET_IMG_PATH;
        if (!file_exists($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
            $error = 'Attachment storage is unavailable.';
            return $stored;
        }

        $count = count($_FILES['attachments']['name']);
        if ($count > 3) {
            $error = 'You can attach at most 3 files.';
            return $stored;
        }

        $config = [
            'upload_path'   => $target,
            // Deliberately narrower than allowed_media_types(): a support attachment has no
            // reason to accept archives or executables.
            'allowed_types' => 'jpg|jpeg|png|gif|webp|pdf|doc|docx|txt',
            'max_size'      => 5120,
            'encrypt_name'  => TRUE,
        ];
        $this->upload->initialize($config);

        $files = $_FILES;
        for ($i = 0; $i < $count; $i++) {
            if (empty($files['attachments']['name'][$i])) {
                continue;
            }
            $_FILES['ticket_attachment'] = [
                'name'     => $files['attachments']['name'][$i],
                'type'     => $files['attachments']['type'][$i],
                'tmp_name' => $files['attachments']['tmp_name'][$i],
                'error'    => $files['attachments']['error'][$i],
                'size'     => $files['attachments']['size'][$i],
            ];
            if (!$this->upload->do_upload('ticket_attachment')) {
                $error = strip_tags($this->upload->display_errors('', ''));
                // Roll back whatever already landed, so a partially-failed upload does not leave
                // half the attachments referenced and half orphaned.
                foreach ($stored as $done) {
                    $path = FCPATH . ltrim($done, '/');
                    if (is_file($path)) {
                        unlink($path);
                    }
                }
                return [];
            }
            $data = $this->upload->data();
            $stored[] = TICKET_IMG_PATH . $data['file_name'];
        }

        return $stored;
    }
}
