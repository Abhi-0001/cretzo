<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Return_request_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function get_return_request_list()
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';
        // Whitelist against the actual selected columns - $_GET['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id', 'order_id', 'username', 'product_name', 'price', 'discounted_price', 'quantity', 'sub_total', 'status'];

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

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
            // oi.id included: the list has an 'Order Item ID' column and searching by the
            // value shown in it found nothing.
            $multipleWhere = ['rr.`id`' => $search, 'oi.`id`' => $search, 'oi.`order_id`' => $search, 'u.`username`' => $search, 'u.`email`' => $search, 'u.`mobile`' => $search, 'p.`name`' => $search, 'oi.`price`' => $search,];
        }

        $count_res = $this->db->select(' COUNT(rr.id) as `total` ')->join('users u', 'u.id=rr.user_id')->join('products p', 'p.id=rr.product_id')->join('order_items oi', 'oi.id=rr.order_item_id');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            // Was or_where() (exact match) while the data query below uses or_like()
            // (partial match) - a mismatch here means the reported pagination "total"
            // doesn't equal the number of rows the search actually returns.
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $request_count = $count_res->get('return_requests rr')->result_array();

        foreach ($request_count as $row) {
            $total = $row['total'];
        }

        // seller and refund columns joined in so the admin can see WHOSE product is coming
        // back and whether the customer has already been paid - the decision on this screen
        // triggers the refund, and there was no way to tell from here what it did.
        $search_res = $this->db->select(' rr.id,rr.remarks, oi.order_id, u.id as user_id,u.username as username ,p.name as product_name,oi.price,oi.discounted_price,oi.id as order_item_id,oi.quantity,oi.sub_total,rr.status, s.username as seller_name, oi.refund_amount, oi.refund_mode, oi.refunded_at, oi.active_status')->join('users u', 'u.id=rr.user_id')->join('products p', 'p.id=rr.product_id')->join('order_items oi', 'oi.id=rr.order_item_id')->join('users s', 's.id=p.seller_id', 'left');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $offer_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('return_requests rr')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($offer_search_res as $row) {
            $row = output_escaping($row);

            $operate = '<a href="javascript:void(0)" class="edit_request edit_return_request action-btn btn btn-success btn-xs ml-1 mr-1 mb-1" title="Edit" data-id="' . $row['order_item_id'] . '"  data-target="#request_rating_modal" data-toggle="modal" ><i class="fa fa-pen"></i></a>';

            $tempRow['id'] = $row['id'];
            $tempRow['user_id'] = $row['user_id'];
            $tempRow['user_name'] = html_escape($row['username']);
            $tempRow['order_id'] = $row['order_id'];
            $tempRow['order_item_id'] = $row['order_item_id'];
            $tempRow['product_name'] = html_escape($row['product_name']);
            $tempRow['price'] = $row['price'];
            $tempRow['discounted_price'] = $row['discounted_price'];
            $tempRow['quantity'] = $row['quantity'];
            $tempRow['sub_total'] = $row['sub_total'];
            $tempRow['status_digit'] = $row['status'];
            $status = [
                '0' => '<span class="badge badge-success">Pending</span>',
                '1' => '<span class="badge badge-primary">Approved</span>',
                '2' => '<span class="badge badge-danger">Rejected</span>',
            ];

            $tempRow['status'] = $status[$row['status']];
            $tempRow['seller_name'] = html_escape((string) $row['seller_name']);
            $tempRow['item_status'] = html_escape((string) $row['active_status']);

            // What the customer actually got back, and through which channel. Approving a
            // return refunds them straight away - to the card for a gateway payment, to the
            // wallet otherwise - and this screen previously gave no sign of it either way.
            $refund_labels = [
                'gateway' => 'Original payment method',
                'wallet' => 'Customer wallet',
                'gateway+wallet' => 'Payment method + wallet',
                'none' => 'Nothing was owed',
            ];
            if (!empty($row['refunded_at'])) {
                $where = isset($refund_labels[$row['refund_mode']]) ? $refund_labels[$row['refund_mode']] : 'Customer';
                $tempRow['refund'] = number_format((float) $row['refund_amount'], 2)
                    . '<br><small class="text-muted">' . html_escape($where) . '</small>';
            } else {
                $tempRow['refund'] = '<span class="text-muted">Not refunded</span>';
            }

            $tempRow['remarks'] = html_escape($row['remarks']);
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
    /**
     * Counts of this seller's return requests by state, for the cards on the seller page.
     *
     * @param  int $seller_id
     * @return array{pending:int, approved:int, rejected:int, total:int}
     */
    function get_seller_return_summary($seller_id)
    {
        $rows = $this->db->select('rr.status, COUNT(rr.id) as total', false)
            ->join('products p', 'p.id = rr.product_id')
            ->where('p.seller_id', (int) $seller_id)
            ->group_by('rr.status')
            ->get('return_requests rr')
            ->result_array();

        $summary = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        $key = ['0' => 'pending', '1' => 'approved', '2' => 'rejected'];
        foreach ($rows as $row) {
            if (isset($key[(string) $row['status']])) {
                $summary[$key[(string) $row['status']]] = (int) $row['total'];
                $summary['total'] += (int) $row['total'];
            }
        }
        return $summary;
    }

    /**
     * The seller's own return requests, as a bootstrap-table payload.
     *
     * Read-only by design: a seller sees what has been raised against their products and what
     * it cost them, but the approve/reject decision is the admin's and the final 'returned'
     * transition is written by the courier callback (sync_shiprocket_shipment_status()).
     *
     * Every query here is scoped to products.seller_id - without it a seller would be reading
     * other sellers' returns, customer names and order ids included.
     *
     * @param  int  $seller_id
     * @param  bool $echo  false returns the payload instead of printing it, for tests
     * @return array|void
     */
    function get_seller_return_request_list($seller_id, $echo = true)
    {
        $seller_id = (int) $seller_id;

        $offset = (isset($_GET['offset']) && is_numeric($_GET['offset'])) ? (int) $_GET['offset'] : 0;
        $limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int) $_GET['limit'] : 10;

        // Whitelisted. $_GET['sort'] reaching order_by() unchecked is an injection route, and is
        // exactly how the admin version of this list was written before it was fixed.
        $sort_columns = [
            'id' => 'rr.id',
            'order_id' => 'oi.order_id',
            'product_name' => 'p.name',
            'quantity' => 'oi.quantity',
            'sub_total' => 'oi.sub_total',
            'status' => 'rr.status',
            'date_created' => 'rr.date_created',
        ];
        $sort = (isset($_GET['sort']) && isset($sort_columns[$_GET['sort']])) ? $_GET['sort'] : 'id';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'asc' : 'desc';

        $scope = function ($builder) use ($seller_id) {
            $builder->join('users u', 'u.id = rr.user_id', 'left')
                ->join('products p', 'p.id = rr.product_id')
                ->join('order_items oi', 'oi.id = rr.order_item_id')
                ->where('p.seller_id', $seller_id);

            if (isset($_GET['status']) && in_array((string) $_GET['status'], ['0', '1', '2'], true)) {
                $builder->where('rr.status', (int) $_GET['status']);
            }
            if (isset($_GET['search']) && $_GET['search'] !== '') {
                $builder->group_start()
                    ->like('rr.id', $_GET['search'])
                    ->or_like('oi.order_id', $_GET['search'])
                    ->or_like('u.username', $_GET['search'])
                    ->or_like('p.name', $_GET['search'])
                    ->group_end();
            }
            return $builder;
        };

        // Counted with the SAME predicates as the data query. The admin list historically used
        // an exact match for the count and a LIKE for the rows, so the reported total did not
        // agree with the number of rows a search actually returned.
        $total = $scope($this->db->select('COUNT(rr.id) as total', false))
            ->get('return_requests rr')->row_array();

        $rows = $scope($this->db->select('rr.id, rr.status, rr.remarks, rr.date_created,
                oi.order_id, oi.id as order_item_id, oi.quantity, oi.sub_total, oi.active_status,
                oi.refund_amount, oi.refund_mode, oi.refunded_at,
                p.name as product_name, u.username', false))
            ->order_by($sort_columns[$sort], $order)
            ->limit($limit, $offset)
            ->get('return_requests rr')->result_array();

        $settings = get_settings('system_settings', true);
        $currency = isset($settings['currency']) ? $settings['currency'] : '';

        $status_badges = [
            '0' => '<span class="badge badge-warning">Awaiting admin decision</span>',
            '1' => '<span class="badge badge-success">Approved</span>',
            '2' => '<span class="badge badge-danger">Rejected</span>',
        ];
        $refund_labels = [
            'gateway' => 'Original payment method',
            'wallet' => 'Customer wallet',
            'gateway+wallet' => 'Payment method + wallet',
            'none' => 'Nothing was owed',
        ];

        $out = ['total' => isset($total['total']) ? (int) $total['total'] : 0, 'rows' => []];

        foreach ($rows as $row) {
            $refund = '<span class="text-muted">Not refunded yet</span>';
            if (!empty($row['refunded_at'])) {
                $where = isset($refund_labels[$row['refund_mode']]) ? $refund_labels[$row['refund_mode']] : 'Customer';
                $refund = $currency . number_format((float) $row['refund_amount'], 2)
                    . '<br><small class="text-muted">' . html_escape($where) . '</small>';
            }

            $out['rows'][] = [
                'id' => $row['id'],
                'order_id' => $row['order_id'],
                'order_item_id' => $row['order_item_id'],
                'product_name' => html_escape($row['product_name']),
                'customer' => html_escape((string) $row['username']),
                'quantity' => $row['quantity'],
                'sub_total' => $currency . number_format((float) $row['sub_total'], 2),
                'status' => isset($status_badges[(string) $row['status']]) ? $status_badges[(string) $row['status']] : '',
                'item_status' => html_escape((string) $row['active_status']),
                'refund' => $refund,
                'remarks' => html_escape((string) $row['remarks']),
                'date_created' => $row['date_created'],
            ];
        }

        if (!$echo) {
            return $out;
        }
        print_r(json_encode($out));
    }

    function update_return_request($data)
    {

        $data = escape_array($data);

        // Claim the request ATOMICALLY, before doing anything else.
        //
        // `UPDATE ... WHERE id = ? AND status = 0` is decided by the database under a row lock,
        // so exactly one caller can ever see a non-zero affected_rows for a given request. That
        // is what stops two admins double-clicking Approve from both running the refund - a
        // read-then-write guard cannot, because both reads can return 0 before either writes.
        //
        // Deliberately NOT inside a transaction. Everything after this point moves real money
        // (a gateway refund leaves the platform's Razorpay balance) or talks to Shiprocket, and
        // a rollback cannot undo either. Wrapping them in a transaction only created the
        // possibility of the money going out while the record of it was rolled back - leaving
        // an item that looks un-refunded and would be refunded again. The claim above is the
        // idempotency mechanism; process_refund() and restore_order_item_stock() carry their
        // own per-item guards on top of it.
        $request = array(
            'status' => $data['status'],
            'remarks' => (isset($data['update_remarks']) && !empty($data['update_remarks'])) ? $data['update_remarks'] : null,
        );
        $item_id  = $data['order_item_id'];

        $this->db->where('id', $data['return_request_id'])
            ->where('status', '0')
            ->update('return_requests', $request);

        if ($this->db->affected_rows() < 1) {
            return ['error' => true, 'message' => 'This return request has already been finalized.'];
        }

        if ($data['status'] == '1') {
            $this->load->model('order_model');
            $refund_res = process_refund($data['order_item_id'], 'returned');
            if (!empty($refund_res['error'])) {
                // Hand the request back so the admin can retry once the cause is fixed -
                // nothing was paid, so leaving it "approved" would strand the customer with an
                // approved return and no refund.
                $this->db->where('id', $data['return_request_id'])->update('return_requests', ['status' => '0']);
                return ['error' => true, 'message' => $refund_res['message']];
            }
            $deliver_by = isset($data['deliver_by']) && !empty($data['deliver_by']) ? $data['deliver_by'] : null;

            // Idempotent (migration 044). This used to be a bare update_stock(..., 'plus'),
            // and marking the item "returned" once the parcel arrived restored it a second
            // time.
            restore_order_item_stock($item_id, 'Return request approved');

            $data = fetch_details('order_items', ['id' => $data['order_item_id']], 'product_variant_id,quantity,user_id');

            // Book the physical return through Shiprocket - the same courier that carried the
            // forward shipment now collects it from the customer and takes it back to the
            // seller's pickup location. This is deliberately not fatal: the approval and the
            // refund above stand whether or not the courier booking succeeds, so a Shiprocket
            // outage or a seller with no pickup location configured cannot block a customer's
            // refund. The failure reason is surfaced to the admin so the pickup can be
            // arranged by hand.
            $return_shipment = create_shiprocket_return_shipment($item_id);
            $shipment_note = '';
            if (!empty($return_shipment['error'])) {
                $shipment_note = ' Note: the return pickup could not be booked automatically (' . $return_shipment['message'] . '). Please arrange collection manually.';
            }

            if (!empty($deliver_by)) {
                update_details(['delivery_boy_id' => $deliver_by], ['id' => $item_id], 'order_items');
            }
            $this->order_model->update_order_item($item_id, 'return_request_approved', 1);

            //for delivery boy notification
            $order_item_res = fetch_details('order_items', ['id' => $item_id], 'order_id');
            $user_id = $deliver_by;
            $cutomer_id = $data[0]['user_id'];
            $settings = get_settings('system_settings', true);
            $app_name = isset($settings['app_name']) && !empty($settings['app_name']) ? $settings['app_name'] : '';
            // A delivery boy is now optional - the return travels back through Shiprocket, and
            // only installations still using local shipping assign one. Every read below is
            // guarded accordingly: this block used to index $user_res[0] unconditionally, which
            // is a fatal on a Shiprocket-shipped return because there is no delivery boy to
            // look up.
            $user_res = !empty($user_id) ? fetch_details('users', ['id' => $user_id], 'username,fcm_id,email,mobile') : [];
            $customer_res = fetch_details('users', ['id' => $cutomer_id], 'username,fcm_id,email,mobile');
            $has_delivery_boy = !empty($user_res);
            $customer_name = !empty($customer_res[0]['username']) ? $customer_res[0]['username'] : 'Customer';
            $fcm_ids = array();
            //custom message

            $custom_notification =  fetch_details('custom_notifications', ['type' => "customer_order_returned_request_approved"], '');
            $hashtag_cutomer_name = '< cutomer_name >';
            $hashtag_order_id = '< order_item_id >';
            $hashtag_application_name = '< application_name >';
            $message = '';
            if (!empty($custom_notification) && isset($custom_notification[0]['message'])) {
                $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                $hashtag = html_entity_decode($string);
                $data1 = str_replace(array($hashtag_cutomer_name, $hashtag_order_id, $hashtag_application_name), array($customer_name, $order_item_res[0]['order_id'], $app_name), $hashtag);
                $message = output_escaping(trim($data1, '"'));
            }
            $delivery_boy_msg = $has_delivery_boy
                ? 'Hello Dear ' . $user_res[0]['username'] . ' you have new order to be pickup order ID #' . $order_item_res[0]['order_id'] . ' please take note of it! Thank you. Regards ' . $app_name
                : '';
            $customer_msg = (!empty($message)) ? $message :  'Hello Dear ' . $customer_name . ', your return request for order item ' . $item_id  . ' has been approved.';

            $notify_emails = ["customer" => [$customer_res[0]['email']]];
            $notify_mobiles = ["customer" => [$customer_res[0]['mobile']]];
            if ($has_delivery_boy) {
                $notify_emails["delivery_boy"] = [$user_res[0]['email']];
                $notify_mobiles["delivery_boy"] = [$user_res[0]['mobile']];
            }
            (notify_event(
                "customer_order_returned_request_approved",
                $notify_emails,
                $notify_mobiles,
                ["users.mobile" => $customer_res[0]['mobile']]
            ));

            if ($has_delivery_boy && !empty($user_res[0]['fcm_id'])) {
                $fcmMsg = array(
                    'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "You have new order to deliver",
                    'body' => $delivery_boy_msg,
                    'type' => "order",
                );

                $fcm_ids[0][] = $user_res[0]['fcm_id'];
                send_notification($fcmMsg, $fcm_ids);
            }
            if (!empty($customer_res[0]['fcm_id'])) {
                $fcmMsg = array(
                    'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Order status updated",
                    'body' => $customer_msg,
                    'type' => "order",
                );

                $fcm_ids[0][] = $customer_res[0]['fcm_id'];
                send_notification($fcmMsg, $fcm_ids);
            }
        } elseif ($data['status'] == '2') {

            $this->load->model('order_model');
            $this->order_model->update_order_item($item_id, 'return_request_decline', 1);
            //for delivery boy notification
            $data = fetch_details('order_items', ['id' => $data['order_item_id']], 'product_variant_id,quantity,user_id');
            $order_item_res = fetch_details('order_items', ['id' => $item_id], 'order_id');

            $cutomer_id = $data[0]['user_id'];
            $settings = get_settings('system_settings', true);
            $app_name = isset($settings['app_name']) && !empty($settings['app_name']) ? $settings['app_name'] : '';
            $customer_res = fetch_details('users', ['id' => $cutomer_id], 'username,fcm_id,email,mobile,username');
            $fcm_ids = array();
            //custom message



            $custom_notification =  fetch_details('custom_notifications', ['type' => "customer_order_returned_request_decline"], '');
            $hashtag_cutomer_name = '< cutomer_name >';
            $hashtag_order_id = '< order_item_id >';
            $hashtag_application_name = '< application_name >';
            // Same guard as the approval branch: the template row is optional, and reading
            // [0]['message'] off an empty result warned on every rejection.
            $message = '';
            if (!empty($custom_notification) && isset($custom_notification[0]['message'])) {
                $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
                $hashtag = html_entity_decode($string);
                $data1 = str_replace(array($hashtag_cutomer_name, $hashtag_order_id, $hashtag_application_name), array($customer_res[0]['username'], $order_item_res[0]['order_id'], $app_name), $hashtag);
                $message = output_escaping(trim($data1, '"'));
            }
            $customer_msg = (!empty($message)) ? $message :  'Hello Dear ' . $customer_res[0]['username'] . ', your return request for order item ' . $item_id  . ' has been declined.';

            (notify_event(
                "customer_order_returned_request_decline",
                ["customer" => [$customer_res[0]['email']]],
                ["customer" => [$customer_res[0]['mobile']]],
                ["users.mobile" => $customer_res[0]['mobile']]
            ));


            if (!empty($customer_res[0]['fcm_id'])) {
                $fcmMsg = array(
                    'title' => (!empty($custom_notification)) ? $custom_notification[0]['title'] : "Order status updated",
                    'body' => $customer_msg,
                    'type' => "order",
                );

                $fcm_ids[0][] = $customer_res[0]['fcm_id'];
                send_notification($fcmMsg, $fcm_ids);
            }
        }

        return [
            'error' => false,
            'message' => 'Return request updated successfully.' . (isset($shipment_note) ? $shipment_note : ''),
        ];
    }
}
