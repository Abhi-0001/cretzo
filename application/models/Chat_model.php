<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chat_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
    }

    // function create_group($data)
    // {
    //     if ($this->db->insert('chat_groups', $data))
    //         return $this->db->insert_id();
    //     else
    //         return false;
    // }

    function make_me_online($id, $data)
    {
        $this->db->where('id', $id);
        if ($this->db->update('users', $data))
            return true;
        else
            return false;
    }

    // function edit_group($data, $id)
    // {
    //     $this->db->where('id', $id);
    //     if ($this->db->update('chat_groups', $data))
    //         return true;
    //     else
    //         return false;
    // }

    // function add_group_members($data)
    // {

    //     $query = $this->db->query("SELECT count(id) as total FROM chat_group_members WHERE group_id=" . $data['group_id'] . " AND user_id=" . $data['user_id'] . " ");
    //     $result = $query->result_array();
    //     $result = $result[0]['total'];

    //     if ($result == 0) {
    //         if ($this->db->insert('chat_group_members', $data))
    //             return $this->db->insert_id();
    //         else
    //             return false;
    //     }
    // }

    // function remove_all_group_members($id, $user_id)
    // {
    //     $user_id = implode(",", $user_id);
    //     if ($this->db->query("DELETE FROM chat_group_members WHERE group_id = $id AND user_id NOT IN ($user_id) "))
    //         return true;
    //     else
    //         return false;
    // }

    // function make_group_admin($id, $user_id)
    // {
    //     $user_id = implode(",", $user_id);

    //     if ($this->db->query("UPDATE chat_group_members SET is_admin=1 WHERE group_id = $id AND is_admin=0 AND user_id IN ($user_id) "))
    //         if ($this->db->query("UPDATE chat_group_members SET is_admin=0 WHERE group_id = $id AND is_admin=1 AND user_id NOT IN ($user_id) "))
    //             return true;
    //         else
    //             return false;
    //     else
    //         return false;
    // }

    // function get_group_members($group_id)
    // {
    //     $query = $this->db->query("SELECT gm.*,u.username,u.image,g.title,g.description FROM chat_group_members gm 
    //     LEFT JOIN chat_groups g ON gm.group_id = g.id
    //     LEFT JOIN users u ON gm.user_id = u.id
    //     WHERE gm.group_id=$group_id ");
    //     return $query->result_array();
    // }

    // function check_group_admin($group_id, $user_id)
    // {

    //     $query = $this->db->query("SELECT * FROM chat_group_members WHERE group_id=$group_id AND user_id=$user_id AND is_admin=1 ");
    //     $data = $query->result_array();

    //     if (!empty($data))
    //         return true;
    //     else
    //         return false;
    // }

    // function delete_group($group_id, $user_id)
    // {

    //     $query = $this->db->query("SELECT * FROM messages WHERE to_id=$group_id AND type='group' ");
    //     $messages = $query->result_array();

    //     if (!empty($messages)) {
    //         $abspath = getcwd();
    //         foreach ($messages as $message) {
    //             $query = $this->db->query("SELECT * FROM chat_media WHERE message_id=" . $message['id']);
    //             $chat_media = $query->result_array();
    //             if (!empty($chat_media)) {
    //                 foreach ($chat_media as $media) {
    //                     unlink('uploads/chat_media/' . $media['file_name']);
    //                 }
    //             }
    //             $this->db->delete('chat_media', array('message_id' => $message['id']));
    //         }
    //     }

    //     $this->db->delete('chat_group_members', array('group_id' => $group_id));

    //     $this->db->delete('chat_groups', array('id' => $group_id));

    //     if ($this->db->delete('messages', array('to_id' => $group_id, 'type' => 'group'))) {
    //         return true;
    //     } else {
    //         return false;
    //     }
    // }

    function delete_msg($from_id, $msg_id)
    {

        // $msg_id and $from_id are spliced straight into these queries, so force them to ints.
        $msg_id = (int) $msg_id;
        $from_id = (int) $from_id;

        $query = $this->db->query("SELECT * FROM chat_media WHERE message_id=$msg_id ");
        $data = $query->result_array();
        if (!empty($data)) {
            foreach ($data as $row) {
                // Was unlink('assets/chats/' . $row['file_name']) with no existence check:
                // wrong directory (uploads are written to CHAT_MEDIA_PATH) and, because
                // file_name held PHP's temp path rather than the saved name, an unlink() that
                // could never match anything - it just raised a warning per attachment while
                // leaving the real file on disk forever. Both halves are fixed now.
                $file = FCPATH . CHAT_MEDIA_PATH . $row['file_name'];
                if (!empty($row['file_name']) && is_file($file)) {
                    unlink($file);
                }
            }
            $this->db->delete('chat_media', array('message_id' => $msg_id));
        }

        if ($this->db->query("DELETE FROM messages WHERE from_id=$from_id AND id=$msg_id")) {
            return true;
        } else {
            return false;
        }
    }

    function get_members($user_id)
    {

        $this->db->from('users');
        $this->db->where_in('id', $user_id);
        $this->db->order_by("username", "asc");
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * The staff accounts a customer or seller can start a support conversation with.
     *
     * Three separate problems with what used to be here:
     *
     *  1. It filtered on `up.role = 3` ("Supporter") only. Nothing in this product creates a
     *     role-3 user by default, and a real install can easily have none - this database has
     *     zero. So the query returned an EMPTY list and the "Support" column on the customer and
     *     seller chat pages rendered with nobody in it: there was literally no one to message.
     *     Super Admin (0) and Admin (1) are included now, so the list is only empty if the
     *     platform has no staff accounts at all.
     *
     *  2. It `LEFT JOIN`ed `messages` and selected `m.*` alongside `GROUP BY u.id`. That is
     *     invalid under ONLY_FULL_GROUP_BY (a hard error on any MySQL 5.7+/8 default config), and
     *     where it did run it filled `id`, `from_id`, `to_id`, `is_read` and `message` with values
     *     from ONE arbitrary message row - which then collided with the `u.id` the views read. The
     *     join contributed nothing but breakage; the unread count the views actually want is
     *     computed properly below.
     *
     *  3. It did not select `image`, which the chat views read for the contact avatar.
     *
     * @param int|null $viewer_id When given, each supporter carries that viewer's unread count.
     */
    function get_supporters($viewer_id = null)
    {
        $supporters = $this->db
            ->select('up.user_id as user_permission_id, up.role as user_role, u.id as userto_id, u.username, u.image, u.last_online, u.web_fcm')
            ->join('user_permissions up', 'up.user_id = u.id', 'inner')
            ->where_in('up.role', [0, 1, 3])
            ->where('u.active', 1)
            ->order_by('up.role', 'ASC')
            ->order_by('u.username', 'ASC')
            ->get('users u')
            ->result_array();

        $viewer_id = (int) $viewer_id;
        foreach ($supporters as &$supporter) {
            $supporter['unread_msg'] = ($viewer_id > 0)
                ? $this->get_unread_msg_count('person', (int) $supporter['userto_id'], $viewer_id)
                : 0;
        }
        unset($supporter);

        return $supporters;
    }

    // function get_groups_all($user_id)
    // {
    //     $group_ids = array();
    //     $my_groups = $this->get_groups($user_id);
    //     foreach ($my_groups as $my_group) {
    //         $group_ids[] =  $my_group['group_id'];
    //     }

    //     if (!empty($group_ids)) {
    //         $group_ids = implode(",", $group_ids);

    //         $sql = "SELECT * FROM chat_groups WHERE id NOT IN ($group_ids) ORDER BY title ASC";
    //         $query = $this->db->query($sql);
    //         $groups =  $query->result_array();
    //         return $groups;
    //     }
    // }

    /**
     * Messages from $from_id to $to_id that $to_id has not read yet.
     *
     * Note the inverted convention this whole table uses: `is_read = 1` means UNREAD and
     * mark_msg_read() sets it to 0. That is what the column default (1) is for - a new message
     * starts unread.
     *
     * All three arguments were interpolated straight into raw SQL. $from_id in particular
     * reaches this from POST data on several endpoints.
     */
    function get_unread_msg_count($type, $from_id, $to_id)
    {
        return (int) $this->db->where('type', (string) $type)
            ->where('is_read', 1)
            ->where('from_id', (int) $from_id)
            ->where('to_id', (int) $to_id)
            ->count_all_results('messages');
    }


    // function get_chat_history($user_id, $limit = '', $offset = '', $from_user = false)
    // {
    //     $members = $this->db->query("SELECT m1.*, u.email,u.mobile,u.image,u.active,u.last_online, u.username AS opponent_username, u.id AS opponent_user_id
    //     FROM messages m1
    //     INNER JOIN users u ON ( (m1.from_id = $user_id AND u.id = m1.to_id) OR (m1.to_id = $user_id AND u.id = m1.from_id) )
    //     WHERE m1.id = ( SELECT MAX(m2.id) FROM messages m2 WHERE (m2.from_id = $user_id OR m2.to_id = $user_id) AND ( (m2.from_id = m1.from_id AND m2.to_id = m1.to_id) OR (m2.from_id = m1.to_id AND m2.to_id = m1.from_id))) ORDER BY m1.id DESC");

    //     return $members->result_array();
    // }

    function get_chat_history($from_id, $limit = '', $offset = '', $from_user = false)
    {
        $members = $this->db->select('m1.id, m1.from_id, m1.to_id, m1.is_read, m1.message, m1.type, m1.media, u.id AS opponent_user_id, u.username AS opponent_username, u.email, u.mobile, u.image, u.active, u.last_online')
            ->from('messages m1')
            ->join('users u', '(m1.from_id = ' . $from_id . ' AND u.id = m1.to_id) OR (m1.to_id = ' . $from_id . ' AND u.id = m1.from_id)', 'inner')
            ->where('m1.id = (SELECT MAX(m2.id) FROM messages m2 WHERE (m2.from_id = ' . $from_id . ' OR m2.to_id = ' . $from_id . ') AND ((m2.from_id = m1.from_id AND m2.to_id = m1.to_id) OR (m2.from_id = m1.to_id AND m2.to_id = m1.from_id)))')
            ->order_by('m1.id', 'desc')
            ->get()
            ->result_array();


        // $members = $this->db->select('u.id,m.from_id,m.to_id,m.is_read,m.message,m.type,m.media,u.username,u.email,u.mobile,u.image,u.active,u.last_online')
        //     ->join('users u', 'u.id = m.to_id', 'left')
        //     ->where('to_id', $from_id)
        //     ->or_where('from_id', $from_id)
        //     ->group_by('to_id')
        //     ->limit($limit, $offset)->get('`messages` m')->result_array();

        return $members;
    }

    function mark_msg_read($type, $from_id, $to_id)
    {
        // CONFIRMED LIVE SQL INJECTION before this change: $from_id arrives straight from
        // $this->input->post('from_id') in My_account/seller/admin mark_msg_read(), and $type
        // from POST too, and both were spliced into this raw UPDATE. Posting
        // from_id=1 AND (  to my-account/mark_msg_read returned MySQL error 1064 quoting the
        // assembled statement, i.e. arbitrary SQL could be appended to an UPDATE on `messages`.
        $type = (string) $type;
        $from_id = (int) $from_id;
        $to_id = (int) $to_id;

        if ($from_id < 1 || $to_id < 1) {
            return false;
        }

        if ($type !== 'group') {
            $this->db->set('is_read', 0)
                ->where('type', $type)
                ->where('is_read', 1)
                ->where('from_id', $from_id)
                ->where('to_id', $to_id)
                ->update('messages');
            return true;
        }
        //  else  if ($type == 'supporter') {
        //     if ($this->db->query("UPDATE messages SET is_read=0 WHERE type='$type' AND is_read=1 AND from_id=$from_id AND to_id=$to_id"))
        //         return true;
        //     else
        //         return false;
        // } 
        // Group chat was removed from this product: `chat_groups` and `chat_group_members` do
        // not exist in the schema, and the model methods that managed them are commented out
        // further up this file. Reaching this branch used to be a hard "Table doesn't exist"
        // database error (a 500 with db_debug on) triggerable by anyone simply POSTing
        // type=group. Refuse it instead.
        if (!$this->db->table_exists('chat_group_members')) {
            return false;
        }

        $this->db->set('is_read', 0)
            ->where('is_read', 1)
            ->where('group_id', $from_id)
            ->where('user_id', $to_id)
            ->update('chat_group_members');
        return true;
    }

    // function set_group_msg_as_unread($group_id, $my_id)
    // {
    //     if ($this->db->query("UPDATE chat_group_members SET is_read=1 WHERE is_read=0  AND group_id=$group_id AND user_id!=$my_id "))
    //         return true;
    //     else
    //         return false;
    // }

    function update_web_fcm($user_id, $fcm)
    {
        // CONFIRMED LIVE SQL INJECTION before this change. $fcm came straight from
        // $this->input->post('web_fcm') and was concatenated inside double quotes, so posting
        //     web_fcm=abc" , username="PWNED
        // to my-account/update_web_fcm rewrote a SECOND column on the users row - verified by
        // observing users.username actually change. Any column on `users` (balance, email,
        // api tokens) was writable this way by any logged-in user.
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return false;
        }

        // FCM registration tokens are opaque base64url-ish strings; anything else is not a token.
        $fcm = trim((string) $fcm);
        if ($fcm !== '' && !preg_match('/^[A-Za-z0-9_:.\-]{1,512}$/', $fcm)) {
            return false;
        }

        return (bool) $this->db->set('web_fcm', $fcm !== '' ? $fcm : null)
            ->where('id', $user_id)
            ->update('users');
    }

    /**
     * Single choke point for writing a chat message - every one of the six controllers that can
     * send one (customer, seller, admin, and the three app APIs) calls through here, so the
     * validation belongs here rather than in six places that had none of it.
     *
     * What was previously accepted, because the row was inserted verbatim with no checks:
     *   - to_id = 0, or any other id that is not a user. This actually happened: the live
     *     `messages` table contained rows addressed to "user 0", which are undeliverable and
     *     which surfaced in the chat contact list as a conversation with a non-existent person.
     *   - to_id belonging to ANY user on the platform, chosen freely by the client - the
     *     recipient was whatever `opposite_user_id` the browser posted.
     *   - an empty message body (messages.message is NOT NULL but '' satisfies it), so a
     *     stray submit produced blank bubbles in the thread.
     *   - from_id == to_id, i.e. messaging yourself.
     *   - type = anything, including 'group', for which no tables exist.
     */
    function send_msg($data)
    {
        $from_id = isset($data['from_id']) ? (int) $data['from_id'] : 0;
        $to_id   = isset($data['to_id']) ? (int) $data['to_id'] : 0;
        $type    = isset($data['type']) ? (string) $data['type'] : '';
        $message = isset($data['message']) ? trim((string) $data['message']) : '';
        $has_upload = !empty($_FILES['documents']['name']);

        if ($from_id < 1 || $to_id < 1 || $from_id === $to_id) {
            return false;
        }

        // Only one-to-one chat exists in this schema; see mark_msg_read() for why 'group' cannot
        // work. Default an unrecognised type to 'person' rather than storing a type that no
        // reader filters on (load_chat() matches on type exactly, so a typo'd type made the
        // message invisible to both parties while still sitting in the table).
        if (!in_array($type, ['person', 'supporter'], true)) {
            $type = 'person';
        }

        if ($message === '' && !$has_upload) {
            return false;
        }

        // Both parties must be real, active accounts.
        $participants = $this->db->select('id')
            ->where_in('id', [$from_id, $to_id])
            ->where('active', 1)
            ->get('users')
            ->result_array();
        if (count($participants) < 2) {
            return false;
        }

        $row = [
            'from_id' => $from_id,
            'to_id'   => $to_id,
            'type'    => $type,
            'message' => mb_substr($message, 0, 5000),
            // is_read defaults to 1 (= unread, see get_unread_msg_count) but be explicit: a
            // caller passing its own is_read could otherwise mark its own message pre-read.
            'is_read' => 1,
            'media'   => '',
        ];

        if (!$this->db->insert('messages', $row)) {
            return false;
        }
        return $this->db->insert_id();
    }

    function get_msg_by_id($msg_id, $to_id, $from_id, $type)
    {

        $sql = "SELECT * FROM messages WHERE id='$msg_id' ";
        $query = $this->db->query($sql);
        $messages =  $query->result_array();
        $product = array();
        $i = 0;
        // print_r($_POST);
        foreach ($messages as $message) {
            // print_r($message);
            // die;
            $product[$i] = $message;

            if ($type == 'person') {
                if ($to_id == $message['to_id']) {
                    $me_user = $this->switch_chat($message['from_id'], $type);

                    if (isset($me_user) && !empty($me_user)) {
                        $product[$i]['picture'] = $me_user[0]['username'];

                        // $product[$i]['profile'] = $me_user[0]['profile'];

                        $product[$i]['senders_name'] = $me_user[0]['username'];

                        $product[$i]['position'] = 'right';
                    } else {
                        return $responce['error'] = true;
                    }
                } else {
                    $oppo_user = $this->switch_chat($message['from_id'], $type);
                    $product[$i]['picture'] = $oppo_user[0]['username'];

                    $product[$i]['profile'] = $oppo_user[0]['profile'];

                    $product[$i]['senders_name'] = $oppo_user[0]['username'];

                    $product[$i]['position'] = 'left';
                }
            } else {

                // new group msg arrived and you have change here

                $oppo_user = $this->switch_chat($message['from_id'], 'person');
                $product[$i]['picture'] = $oppo_user[0]['username'];

                // $product[$i]['profile'] = $oppo_user[0]['profile'];

                $product[$i]['senders_name'] = $oppo_user[0]['username'];

                if ($from_id == $message['from_id']) {
                    $product[$i]['position'] = 'right';
                } else {
                    $product[$i]['position'] = 'left';
                }
            }

            $i++;
        }
        return $product;
    }

    function load_chat($from_id, $to_id, $type = '',  $offset = '', $limit = '', $sort = '', $order = '', $search = '')
    {

        // $from_id is a group id when $type is = group

        // Every value below used to be interpolated straight into raw SQL with no
        // escaping — from_id/search in particular are attacker-controlled (POSTed by the
        // client), making this a direct SQL injection point. Casting ids to int and
        // escaping the search term closes that off.
        $from_id = (int) $from_id;
        $to_id = (int) $to_id;
        $type = $this->db->escape_str($type);
        $sort = in_array($sort, ['id', 'date_created'], true) ? $sort : 'id';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $offset = is_numeric($offset) ? (int) $offset : '';
        $limit = is_numeric($limit) ? (int) $limit : '';

        $search = ($search !== '' && $search !== '') ? " AND (`message` like '%" . $this->db->escape_like_str($search) . "%') " : "";

        // Previously, any $type other than the literal string 'person' produced a query
        // with NO participant filter at all — SELECT * FROM messages, returning every
        // private message ever sent on the platform between any two users, to anyone who
        // was merely logged in. Both branches now always require to_id=$to_id (the
        // session user), which is the one part of this query that was never attacker-
        // controlled, so a caller can only ever read conversations they're a party to.
        $query1 = "SELECT count(id) as total FROM messages WHERE type='$type' AND ((from_id=$from_id AND to_id=$to_id) OR (from_id=$to_id AND to_id=$from_id)) ";
        $query1 = $this->db->query($query1);
        $rowcount = $query1->result_array();
        $rowcount = $rowcount[0]['total'];

        $sql = "SELECT * FROM messages WHERE type='$type' AND ((from_id=$from_id AND to_id=$to_id) OR (from_id=$to_id AND to_id=$from_id)) ";

        $sql .= ($sort !== '' && $order !== '') ? " ORDER BY $sort $order " : "";
        $sql .= ($offset !== '' && $limit !== '') ? " Limit $offset,$limit " : "";



        $query = $this->db->query($sql);
        $messages =  $query->result_array();

        $product = array();
        $i = 0;

        foreach ($messages as $message) {
            $product['msg'][$i] = $message;
            $me_user = $this->switch_chat($message['from_id'], 'person');

            $product['msg'][$i]['picture'] = $me_user[0]['username'];

            $product['msg'][$i]['profile'] = $me_user[0]['username'];;

            $product['msg'][$i]['senders_name'] = $me_user[0]['username'];
            // if ($message['type'] == 'person') {
            //     $product['msg'][$i]['group_name'] = $me_user[0]['username'];
            // }

            $i++;
        }
        $product['total_msg'] = $rowcount;
        return $product;
    }

    function get_media($msg_id)
    {
        $msg_id = (int) $msg_id;
        $query = $this->db->query("SELECT * FROM chat_media WHERE message_id=$msg_id ");
        //     $res = $query->result_array();
        //     $rows = [];

        //    foreach ($res as $value) {
        //         $file_extention = explode('.', $value['original_file_name']);
        //         $tempRow['id'] = $value['id'];
        //         $tempRow['message_id'] = $value['message_id'];
        //         $tempRow['user_id'] = $value['user_id'];
        //         $tempRow['original_file_name'] = $value['original_file_name'];
        //         $tempRow['file_name'] = $value['file_name'];
        //         $tempRow['file_url'] = base_url('uploads/chat_media/'.$value['original_file_name']);
        //         $tempRow['file_extension'] = end($file_extention);
        //         $tempRow['file_size'] = $value['file_size'];
        //         $tempRow['date_created'] = $value['date_created'];
        //          $rows[] = $tempRow;
        //    }
        //     $bulkData = $rows;


        return $query->result_array();
    }

    function add_file($data)
    {
        if ($this->db->insert('chat_media', $data))
            return $this->db->insert_id();
        else
            return false;
    }

    function switch_chat($user_or_group_id, $type)
    {
        // Cast to int (was raw string interpolation into SQL) and, for a user lookup,
        // select only display columns — this used to be `SELECT *` on `users`, handing
        // back the caller's password hash, salt, api key, address and more.
        $user_or_group_id = (int) $user_or_group_id;
        if ($type == 'person' || !$this->db->table_exists('chat_groups')) {
            // `chat_groups` does not exist in this schema (group chat was removed - see
            // mark_msg_read), so the else branch was a guaranteed "Table doesn't exist" database
            // error for any caller passing a non-'person' type, which is client-controlled on
            // every switch_chat endpoint. Fall back to the user lookup instead of erroring.
            $query = $this->db->query("SELECT id, username, image, last_online, web_fcm FROM users WHERE id=$user_or_group_id ");
        } else {
            $query = $this->db->query("SELECT * FROM chat_groups WHERE id=$user_or_group_id ");
        }

        // print_r($user_or_group_id);
        // echo $this->db->last_query();
        $messages =  $query->result_array();
        return $messages;
    }

    function get_user_picture($user_id)
    {
        // `users` has no first_name / last_name columns on this schema (they are username /
        // email / mobile), so both reads were undefined-index warnings and the function always
        // returned an empty string. Build the initials from username, and stop SELECT *'ing the
        // password hash to do it.
        $row = $this->db->select('username')->where('id', (int) $user_id)->get('users')->row_array();
        if (empty($row) || trim((string) $row['username']) === '') {
            return '';
        }

        $parts = preg_split('/\s+/', trim((string) $row['username']));
        $initials = mb_substr($parts[0], 0, 1);
        if (count($parts) > 1) {
            $initials .= mb_substr($parts[count($parts) - 1], 0, 1);
        }
        return mb_strtoupper($initials);
    }

    function get_web_fcm($user_id)
    {
        // $user_id was interpolated raw; it comes from POST on the chat endpoints.
        return $this->db->select('web_fcm')->where('id', (int) $user_id)->get('users')->result_array();
    }

    function add_media_ids_to_msg($msg_id, $media_id)
    {
        // $ids was only assigned inside `if (!empty($query))`, and a CI query object is never
        // empty - but if the message row itself was gone, $product_ids stayed undefined and this
        // warned "Undefined variable $product_ids" before writing the media id anyway. Both ids
        // are also cast now rather than concatenated into raw SQL.
        $msg_id = (int) $msg_id;
        $media_id = (int) $media_id;
        if ($msg_id < 1 || $media_id < 1) {
            return false;
        }

        $row = $this->db->select('media')->where('id', $msg_id)->get('messages')->row_array();
        if (empty($row)) {
            return false;
        }

        $existing = trim((string) $row['media']);
        $ids = ($existing !== '') ? $existing . ',' . $media_id : (string) $media_id;

        return (bool) $this->db->set('media', $ids)->where('id', $msg_id)->update('messages');
    }

    function make_user_admin($workspace_id, $user_id)
    {

        // in this func we are adding users id in the workspace - data format 1,2,3 

        $query = $this->db->query('SELECT admin_id FROM workspace WHERE id=' . $workspace_id . ' ');

        if (!empty($query)) {
            foreach ($query->result_array() as $row) {
                $product_ids = $row['admin_id'];
            }
            $admin_id = $product_ids . ',' . $user_id;
        }

        if ($this->db->query('UPDATE workspace SET admin_id="' . $admin_id . '" WHERE id=' . $workspace_id . ' '))
            return true;
        else
            return false;
    }

    function remove_user_from_admin($workspace_id, $user_id)
    {

        // in this func we are adding users id in the workspace - data format 1,2,3 
        $query = $this->db->query('SELECT admin_id FROM workspace WHERE FIND_IN_SET(' . $user_id . ',`admin_id`) and id =' . $workspace_id . ' ');
        $result = $query->result_array();
        if (!empty($result)) {
            $admin_id = $result[0]['admin_id'];
            $admin_id = preg_replace('/\s+/', '', $admin_id);
            $admin_ids = explode(",", $admin_id);
            if (($key = array_search($user_id, $admin_ids)) !== false) {
                unset($admin_ids[$key]);
            }
            $admin_id = implode(",", $admin_ids);
            if ($this->db->query('UPDATE workspace SET admin_id="' . $admin_id . '" WHERE id=' . $workspace_id . ' '))
                return true;
            else
                return false;
        } else {
            return false;
        }
    }

    function remove_user_from_workspace($workspace_id, $user_id)
    {
        $this->remove_user_from_admin($workspace_id, $user_id);
        $query = $this->db->query('SELECT user_id FROM workspace WHERE FIND_IN_SET(' . $user_id . ',`user_id`) and id =' . $workspace_id . ' ');
        $result = $query->result_array();
        if (!empty($result)) {
            $admin_id = $result[0]['user_id'];
            $admin_id = preg_replace('/\s+/', '', $admin_id);
            $admin_ids = explode(",", $admin_id);
            if (($key = array_search($user_id, $admin_ids)) !== false) {
                unset($admin_ids[$key]);
            }
            $admin_id = implode(",", $admin_ids);
            if ($this->db->query('UPDATE workspace SET user_id="' . $admin_id . '" WHERE id=' . $workspace_id . ' ')) {

                $query = $this->db->query('SELECT workspace_id FROM users WHERE FIND_IN_SET(' . $workspace_id . ',`workspace_id`) and id =' . $user_id . ' ');
                $result = $query->result_array();
                if (!empty($result)) {
                    $admin_id = $result[0]['workspace_id'];
                    $admin_id = preg_replace('/\s+/', '', $admin_id);
                    $admin_ids = explode(",", $admin_id);
                    if (($key = array_search($workspace_id, $admin_ids)) !== false) {
                        unset($admin_ids[$key]);
                    }
                    $admin_id = implode(",", $admin_ids);
                    if ($this->db->query('UPDATE users SET workspace_id="' . $admin_id . '" WHERE id=' . $user_id . ' ')) {
                        $query = $this->db->query('SELECT id,user_id FROM projects WHERE FIND_IN_SET(' . $user_id . ',`user_id`) and workspace_id =' . $workspace_id . ' ');
                        $results = $query->result_array();
                        if (!empty($results)) {
                            foreach ($results as $result) {
                                $admin_id = $result['user_id'];
                                $id = $result['id'];
                                $admin_id = preg_replace('/\s+/', '', $admin_id);
                                $admin_ids = explode(",", $admin_id);
                                if (($key = array_search($user_id, $admin_ids)) !== false) {
                                    unset($admin_ids[$key]);
                                }
                                $admin_id = implode(",", $admin_ids);
                                $this->db->query('UPDATE projects SET user_id="' . $admin_id . '" WHERE id=' . $id . ' ');
                            }
                        }
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function get_user($user_id)
    {

        // $user_id is array of users ids 

        $this->db->from('users');
        $this->db->where_in('id', $user_id);
        $query = $this->db->get();
        return $query->result();
    }

    function get_user_array_responce($user_id)
    {

        // $user_id is array of users ids 

        $this->db->from('users');
        $this->db->where_in('id', $user_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    function get_user_not_in_workspace($user_id)
    {

        // $user_id is array of users ids 

        $this->db->from('users');
        $this->db->where_not_in('id', $user_id);
        $query = $this->db->get();
        return $query->result();
    }

    function get_users_by_email($email)
    {
        // Was a raw where() string with $email concatenated into three LIKE clauses - straight
        // SQL injection from the search box. It also referenced `first_name` / `last_name`,
        // which do not exist on this `users` table (the columns are username/email/mobile), so
        // the query was a hard error 1054 the moment it ran at all. And it SELECT *'d, returning
        // password hashes and api keys for every match.
        $email = trim((string) $email);
        if ($email === '') {
            return [];
        }

        return $this->db->select('id, username, email, mobile, image')
            ->group_start()
            ->like('email', $email)
            ->or_like('username', $email)
            ->or_like('mobile', $email)
            ->group_end()
            ->limit(25)
            ->get('users')
            ->result_array();
    }

    function get_users_by_email_for_add($email)
    {

        $this->db->from('users');
        $this->db->where('email', $email);
        $query = $this->db->get();
        return $query->result_array();
    }

    function get_user_by_id($user_id)
    {

        $this->db->from('users');
        $this->db->where('id', $user_id);
        $query = $this->db->get();
        return $query->result_array();
    }
}
