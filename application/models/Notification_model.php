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
}
