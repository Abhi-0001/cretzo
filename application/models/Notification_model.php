<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
class Notification_model extends CI_Model
{

    public function add_notification($data)
    {
        $data = escape_array($data);
        $notification_data = array(
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'send_to' => $data['send_to'],
            'users_id' => (isset($data['users_id']) && !empty($data['users_id'])) ? $data['users_id'] : 0,
        );

        if (isset($data['type']) && $data['type'] == 'categories') {
            $notification_data['type_id'] = $data['category_id'];
        }
        if (isset($data['type']) && $data['type'] == 'products') {
            $notification_data['type_id'] = $data['product_id'];
        }
        if (isset($data['type']) && $data['type'] == 'notification_url') {
            $notification_data['link'] = $data['link'];
        }
        if (isset($data['send_to']) && $data['send_to'] == 'specific_user') {
            $notification_data['users_id'] = stripslashes($data['select_user_id']);
        }

        if (isset($data['image']) && !empty($data['image'])) {
            $notification_data['image'] = $data['image'];
        }
        return $this->db->insert('notifications', $notification_data);
    }

    function get_notifications($offset, $limit, $sort, $order)
    {
        $notification_data = [];
        $count_res = $this->db->select(' COUNT(id) as `total` ')->get('notifications')->result_array();
        $search_res = $this->db->select(' * ')->order_by($sort, $order)->limit($limit, $offset)->get('notifications')->result_array();
        for ($i = 0; $i < count($search_res); $i++) {
            $search_res[$i]['title'] = output_escaping($search_res[$i]['title']);
            $search_res[$i]['message'] = output_escaping($search_res[$i]['message']);
            $search_res[$i]['send_to'] = output_escaping($search_res[$i]['send_to']);
            $search_res[$i]['users_id'] = output_escaping($search_res[$i]['users_id']);
            $search_res[$i]['link'] = (isset($search_res[$i]['link']) && !empty($search_res[$i]['link']) ? $search_res[$i]['link'] : '');
            if (empty($search_res[$i]['image'])) {
                $search_res[$i]['image'] = '';
            } else {
                if (file_exists(FCPATH . $search_res[$i]['image']) == FALSE) {
                    $search_res[$i]['image'] = base_url() . NO_IMAGE;
                } else {
                    $search_res[$i]['image'] = base_url() . $search_res[$i]['image'];
                }
            }
        }
        $notification_data['total'] = $count_res[0]['total'];
        $notification_data['data'] = $search_res;
        return  $notification_data;
    }
    public function get_notifications_data($offset = 0, $limit = 10, $sort = 'read_by', $order = 'ASC')
    {

        $multipleWhere = '';
        $where = [];
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // $_GET['sort'] was passed straight into order_by() with no whitelist - the same
        // injection-shaped pattern fixed on every other list page in this admin panel.
        $sortable = ['id' => 'id', 'title' => 'title', 'type' => 'type', 'read_by' => 'read_by'];
        $sort = (isset($_GET['sort']) && isset($sortable[$_GET['sort']])) ? $sortable[$_GET['sort']] : 'read_by';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['id' => $search, 'title' => $search, 'message' => $search];
        }

        // message_type was concatenated directly into a raw WHERE string with no escaping or
        // validation at all - $_GET['message_type'] went straight from the request into SQL.
        // Confirmed live: requesting message_type=0 OR 1=1 returned every row (18) instead of
        // the single matching one, proving arbitrary boolean conditions could be injected here.
        // read_by is only ever 0 or 1, so anything else is simply ignored instead of filtered.
        if (isset($_GET['message_type']) && in_array($_GET['message_type'], ['0', '1'], true)) {
            $where['read_by'] = (int) $_GET['message_type'];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (!empty($where)) {
            $count_res->where($where);
        }
        $city_count = $count_res->get('system_notification')->result_array();

        foreach ($city_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (!empty($where)) {
            $search_res->where($where);
        }

        $city_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('system_notification')->result_array();

        // Notification "type" is only ever one of a small, known set produced elsewhere in the
        // codebase (place_order, seller_verification_request); the View button used to always
        // link to the order detail page regardless, so a seller-verification notification's
        // "View" button sent an admin to admin/orders/edit_orders with a seller's user id
        // passed off as an order id - that page immediately does $res[0]['address_id'] on an
        // empty result, an "Undefined array key 0" (and further downstream) warning. Each known
        // type now links somewhere that actually exists for it, and an unrecognised future type
        // gets no View button at all rather than a guaranteed-broken link.
        $view_links = [
            'place_order' => ['url' => 'admin/orders/edit_orders', 'param' => 'edit_id', 'title' => 'View Order'],
            'seller_verification_request' => ['url' => 'admin/sellers/manage-seller', 'param' => 'edit_id', 'title' => 'View Seller'],
        ];

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        foreach ($city_search_res as $row) {
            $row = output_escaping($row);
            $tempRow = array();

            $operate = ' <a class="delete_system_noti action-btn  btn btn-danger btn-xs mr-1 mb-1 ml-1" title="Delete" href="javascript:void(0)"  data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';

            if ($row['read_by'] != '1') {
                $operate .= '<a href="javascript:void(0)" class="mark_notification_read action-btn btn btn-secondary btn-xs mr-1 mb-1 ml-1" title="Mark as Read" data-id="' . $row['id'] . '"><i class="fa fa-check"></i></a>';
            }

            if (isset($view_links[$row['type']])) {
                $link = $view_links[$row['type']];
                $operate .= '<a href="' . base_url($link['url']) . '?' . $link['param'] . '=' . rawurlencode($row['type_id']) . '&noti_id=' . $row['id'] . '" class="btn action-btn btn-primary btn-xs ml-1 mr-1 mb-1" title="' . $link['title'] . '"><i class="fa fa-eye"></i></a>';
            }

            $tempRow['id'] = $row['id'];
            $tempRow['title'] = html_escape((string) $row['title']);
            $tempRow['message'] = html_escape((string) $row['message']);
            $tempRow['type'] = html_escape(ucwords(str_replace('_', ' ', (string) $row['type'])));
            $tempRow['type_id'] = $row['type_id'];
            $tempRow['read_by'] = ($row['read_by'] == 1) ? '<label class="badge badge-primary">Read</label>' : '<label class="badge badge-danger">Un-Read</label>';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    /**
     * Marks one system notification as read. mark_all_as_read() below already existed for the
     * bulk case, but there was no way to mark a single notification read on its own - the only
     * thing that ever set read_by=1 for one row was a side effect of clicking through to
     * admin/orders/edit_orders, which only ever applied to "place_order" notifications and
     * silently did nothing for any other type.
     */
    public function mark_notification_read($id)
    {
        $response = array();
        if (update_details(['read_by' => '1'], ['id' => $id], 'system_notification')) {
            $response['error'] = false;
            $response['message'] = 'Marked as read.';
        } else {
            $response['error'] = true;
            $response['message'] = 'Something went wrong.';
        }
        print_r(json_encode($response));
    }
    /**
     * @param int|null $for_user_id When set, only notifications this user is a recipient of are
     *                              returned. Omitted (null) means "everything", which is what the
     *                              admin log needs.
     *
     * The customer-facing "My Account > Notifications" page calls this same endpoint, and it used
     * to receive EVERY notification row ever created - including ones the admin had targeted at a
     * single named customer via "Send to > Specific user". So one customer's targeted message
     * (which can name an order, a refund, an account issue) was displayed verbatim to every other
     * customer who opened their notifications page.
     */
    public function get_notification_list($offset = 0, $limit = 10, $sort = 'id', $order = 'ASC', $for_user_id = null)
    {

        $multipleWhere = '';
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Whitelist against the actual selected columns - $_GET['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id', 'title', 'type', 'message', 'send_to'];
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
            $multipleWhere = ['id' => $search, 'title' => $search, 'message' => $search];
        }

        // `users_id` holds a json array of id strings (e.g. ["4","7"]) written by
        // Notification_model::add_notification(), so match on the quoted id to avoid "4" also
        // matching "14"/"41". Rows with send_to anything other than 'specific_user' are
        // broadcasts and belong to everyone.
        $recipient_id = ($for_user_id !== null && (int) $for_user_id > 0) ? (int) $for_user_id : null;

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start()->or_like($multipleWhere)->group_end();
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }
        if ($recipient_id !== null) {
            $count_res->group_start()
                ->where('send_to !=', 'specific_user')
                ->or_where('send_to IS NULL')
                ->or_like('users_id', '"' . $recipient_id . '"')
                ->group_end();
        }
        $city_count = $count_res->get('notifications')->result_array();

        foreach ($city_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start()->or_like($multipleWhere)->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }
        if ($recipient_id !== null) {
            $search_res->group_start()
                ->where('send_to !=', 'specific_user')
                ->or_where('send_to IS NULL')
                ->or_like('users_id', '"' . $recipient_id . '"')
                ->group_end();
        }

        $city_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('notifications')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($city_search_res as $row) {
            $row = output_escaping($row);
            // The Delete button calls an admin-only endpoint. It was rendered for customers too,
            // so every customer's notifications page showed a delete icon that answered with the
            // admin-login redirect when clicked.
            $operate = ($recipient_id === null)
                ? ' <a class="delete_notifications btn btn-danger action-btn btn-xs mr-1 ml-1 mb-1" title="Delete" href="javascript:void(0)"  data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>'
                : '';
            $tempRow['id'] = $row['id'];
            // output_escaping() only strips backslash-escaping, it does not HTML-encode - a
            // stored-XSS route the same as already fixed on other list pages (title/message are
            // admin-entered and only pass through xss_clean's blocklist filter before saving).
            $tempRow['title'] = html_escape($row['title']);
            $tempRow['type'] = html_escape(ucwords(str_replace('_', ' ', $row['type'])));
            $tempRow['message'] = html_escape($row['message']);
            $tempRow['send_to'] = html_escape(ucwords(str_replace('_', " ", $row['send_to'])));
            $tempRow['users_id'] = html_escape(str_replace(array('[', ']', '"'), '', $row['users_id']));

            if (empty($row['image'])) {
                $row['image'] = '';
            } else {
                if (file_exists(FCPATH . $row['image']) == FALSE) {
                    $row['image'] = base_url() . NO_IMAGE;
                } else {
                    $row['image'] = base_url() . $row['image'];
                }
            }
            $tempRow['image_src'] = $row['image'];
            $tempRow['image'] = "<div class='mx-auto product-image image-box-100'><a href='" . html_escape($row['image']) . "' data-toggle='lightbox' data-gallery='gallery' >
      <img class='rounded'  src='"  . html_escape($row['image']) . "'></a></div>";
            $tempRow['link'] = html_escape($row['link']);
            // Consumed by the customer-facing "My Account > Notifications" front-end page
            // (front-end/*/pages/notifications.php), which renders this single combined field
            // rather than the individual title/message/image columns above.
            $tempRow['full_notification'] = '<h4>' . html_escape($row['title']) . '</h4><p>' . html_escape($row['message']) . '</p><img src="' . html_escape($row['image']) . '" alt="Notification Image" height="100px" />';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    public function mark_all_as_read()
    {
        if (update_details(['read_by' => '1'], ['read_by' => 0], 'system_notification')) {
            $response_data['error'] =  false;
            $response_data['message'] =  'All notifications marked as read successfully.';
        } else {
            $response_data['error'] =  true;
            $response_data['message'] =  'Opps! Something went wrong.';
        }
        print_r(json_encode($response_data));
    }

    /* ==================== per-user notification inbox ====================
     *
     * `notifications` is a broadcast log, not a per-user mailbox: one row can be addressed to
     * everybody, to a role, or to a list of user ids (`send_to` + `users_id`). Nothing here
     * existed before - the storefront bell was a static image with a hardcoded 0, the seller
     * panel had no bell at all, and the only reader was a bootstrap-table that the customer
     * notifications page pointed at an ADMIN url. These methods are the shared inbox the buyer
     * page, the buyer bell and the seller panel all now read through.
     */

    /**
     * Which broadcast audiences this user is in.
     *
     * Backward compatible with the two values already in the table: 'all_users' and anything
     * unrecognised (including NULL) means everyone, which is what every pre-existing row
     * relies on. 'all_customers' / 'all_sellers' are the new role-targeted audiences - before
     * them an admin had no way to notify sellers as a group at all.
     *
     * $panel is which surface is asking, and it matters because EVERY seller account is also in
     * the `members` group (registration puts them there; sellers shop too). Resolving audiences
     * from group membership alone therefore handed a seller 'all_customers' as well, so an
     * admin broadcast addressed to All Customers showed up in the seller dashboard's own
     * notification list - contradicting what that panel says on the tin ("Announcements from
     * the platform team"). Each panel now sees only its own role audience:
     *
     *   panel 'seller'   -> all_users + all_sellers
     *   panel 'customer' -> all_users + all_customers
     *
     * plus, in both cases, anything addressed to the user by id. A seller who is also shopping
     * still receives All Customers broadcasts - on the storefront bell / My Account page, which
     * is where a message written for buyers belongs.
     */
    public function audiences_for_user($user_id, $panel = 'customer')
    {
        $user_id = (int) $user_id;
        $audiences = ['all_users'];
        if ($user_id < 1) {
            return $audiences;
        }

        $groups = $this->db->select('g.name')
            ->join('groups g', 'g.id = ug.group_id', 'inner')
            ->where('ug.user_id', $user_id)
            ->get('users_groups ug')
            ->result_array();
        $names = array_column($groups, 'name');

        if ($panel === 'seller') {
            if (in_array('seller', $names, true)) {
                $audiences[] = 'all_sellers';
            }
        } elseif (in_array('members', $names, true)) {
            $audiences[] = 'all_customers';
        }
        return $audiences;
    }

    /**
     * Restricts the active query builder to the notifications $user_id may see: any broadcast to
     * an audience they belong to, plus anything addressed to them by id.
     *
     * $audiences is passed in rather than looked up here on purpose: `$this->db` is a single
     * shared query builder, so running audiences_for_user()'s own SELECT partway through
     * building the outer query merged the two together ("Unknown column 'n.id'"). Callers
     * resolve the audience list BEFORE they start staging their query.
     *
     * `users_id` holds a json array of id strings (e.g. ["4","7"]), so the LIKE matches on the
     * QUOTED id - otherwise user 4 would also receive everything addressed to 14 and 41.
     */
    private function scope_to_user($builder, $user_id, $audiences)
    {
        $user_id = (int) $user_id;
        $quoted_id = '"' . $user_id . '"';

        $builder->group_start()
            ->where_in('send_to', $audiences)
            ->or_where('send_to IS NULL')
            ->or_group_start()
                ->where('send_to', 'specific_user')
                ->like('users_id', $quoted_id)
            ->group_end()
        ->group_end();

        return $builder;
    }

    /**
     * Unread count for the bell. Unread == no matching notification_reads row.
     *
     * $panel must match the bell being drawn, or the badge counts notifications the list below
     * it will not show - see audiences_for_user().
     */
    public function count_user_unread($user_id, $panel = 'customer')
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return 0;
        }

        // Resolved before the outer query is staged - see scope_to_user().
        $audiences = $this->audiences_for_user($user_id, $panel);

        $this->db->select('COUNT(n.id) as total', false)
            ->join('notification_reads nr', 'nr.notification_id = n.id AND nr.user_id = ' . $user_id, 'left')
            ->where('nr.id IS NULL');
        $this->scope_to_user($this->db, $user_id, $audiences);

        $row = $this->db->get('notifications n')->row_array();
        return !empty($row) ? (int) $row['total'] : 0;
    }

    /**
     * This user's notifications, newest first, each flagged read/unread.
     *
     * $only_unread powers the bell dropdown; the full list page passes false.
     */
    public function get_user_inbox($user_id, $limit = 10, $offset = 0, $only_unread = false, $panel = 'customer')
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return ['total' => 0, 'rows' => []];
        }
        $limit  = ($limit > 0 && $limit <= 100) ? (int) $limit : 10;
        $offset = ($offset > 0) ? (int) $offset : 0;

        $audiences = $this->audiences_for_user($user_id, $panel);

        // Count and data queries must apply identical conditions or the pager lies.
        $count_builder = $this->db->select('COUNT(n.id) as total', false)
            ->join('notification_reads nr', 'nr.notification_id = n.id AND nr.user_id = ' . $user_id, 'left');
        if ($only_unread) {
            $count_builder->where('nr.id IS NULL');
        }
        $this->scope_to_user($count_builder, $user_id, $audiences);
        $count = $count_builder->get('notifications n')->row_array();
        $total = !empty($count) ? (int) $count['total'] : 0;

        $builder = $this->db->select('n.*, nr.id as read_marker', false)
            ->join('notification_reads nr', 'nr.notification_id = n.id AND nr.user_id = ' . $user_id, 'left');
        if ($only_unread) {
            $builder->where('nr.id IS NULL');
        }
        $this->scope_to_user($builder, $user_id, $audiences);
        $records = $builder->order_by('n.id', 'DESC')->limit($limit, $offset)->get('notifications n')->result_array();

        $rows = [];
        foreach ($records as $row) {
            $row = output_escaping($row);

            $image = '';
            if (!empty($row['image'])) {
                $image = (file_exists(FCPATH . $row['image']) === false) ? base_url() . NO_IMAGE : base_url() . $row['image'];
            }

            $rows[] = [
                'id' => (int) $row['id'],
                // output_escaping() only strips backslash-escaping, it does not HTML-encode.
                'title'      => html_escape((string) $row['title']),
                'message'    => html_escape((string) $row['message']),
                'type'       => html_escape((string) $row['type']),
                'type_label' => html_escape(ucwords(str_replace('_', ' ', (string) $row['type']))),
                'type_id'    => (string) $row['type_id'],
                'image'      => $image,
                'link'       => $this->inbox_link($row, $panel),
                'is_read'    => !empty($row['read_marker']),
                'date_sent'  => $row['date_sent'],
            ];
        }

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * Where a notification should take the recipient.
     *
     * Without this every notification was a dead end: the row carries a type and a type_id but
     * nothing ever turned them into a destination, so a "your order shipped" notification could
     * not be clicked through to the order.
     *
     * $panel selects the audience's own panel - a seller must not be sent to the customer
     * my-account pages, which is the same mistake the ticket emails used to make.
     */
    private function inbox_link($row, $panel = 'customer')
    {
        $type    = (string) $row['type'];
        $type_id = trim((string) $row['type_id']);
        $is_seller = ($panel === 'seller');

        if ($type === 'notification_url' && !empty($row['link'])) {
            return (string) $row['link'];
        }
        if (in_array($type, ['ticket_message', 'ticket_status'], true)) {
            $base = $is_seller ? 'seller/support' : 'my-account/support';
            return base_url($base . ($type_id !== '' ? '?ticket_id=' . urlencode($type_id) : ''));
        }
        if (strpos($type, 'order') !== false) {
            return base_url($is_seller ? 'seller/orders' : 'my-account/orders');
        }
        if ($is_seller) {
            // A seller has no storefront product/category destination worth linking to.
            return '';
        }
        if ($type === 'products' && $type_id !== '') {
            return base_url('products?id=' . urlencode($type_id));
        }
        if ($type === 'categories' && $type_id !== '') {
            return base_url('products?category=' . urlencode($type_id));
        }
        return '';
    }

    /**
     * Marks one notification, or every notification currently visible to the user, as read.
     *
     * INSERT IGNORE against the unique (user_id, notification_id) key, so re-marking is a no-op
     * rather than a duplicate-key error - the bell fires this on every open.
     */
    public function mark_user_read($user_id, $notification_id = null, $panel = 'customer')
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return false;
        }

        // Same panel scope as the list the button sits under: "mark all as read" must clear
        // exactly what that page shows, not the user's other panel too.
        $audiences = $this->audiences_for_user($user_id, $panel);

        if ($notification_id !== null) {
            $notification_id = (int) $notification_id;
            if ($notification_id < 1) {
                return false;
            }
            // Only mark something this user can actually see, so an arbitrary id cannot be used
            // to create read-markers for notifications addressed to somebody else.
            $this->scope_to_user($this->db, $user_id, $audiences);
            $exists = $this->db->where('id', $notification_id)->count_all_results('notifications');
            if ($exists < 1) {
                return false;
            }
            $ids = [$notification_id];
        } else {
            $builder = $this->db->select('n.id')
                ->join('notification_reads nr', 'nr.notification_id = n.id AND nr.user_id = ' . $user_id, 'left')
                ->where('nr.id IS NULL');
            $this->scope_to_user($builder, $user_id, $audiences);
            $ids = array_column($builder->get('notifications n')->result_array(), 'id');
        }

        if (empty($ids)) {
            return true;
        }

        $values = [];
        foreach ($ids as $id) {
            $values[] = '(' . $user_id . ',' . (int) $id . ')';
        }
        $this->db->query('INSERT IGNORE INTO `notification_reads` (`user_id`, `notification_id`) VALUES ' . implode(',', $values));
        return true;
    }
}
