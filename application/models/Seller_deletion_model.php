<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller account-deletion requests.
 *
 * A seller cannot delete their own account. They file a request with a note (this model's
 * create()), an admin reviews it, and the account is deleted only by the admin - through the
 * existing admin/sellers/delete_sellers action - after which complete() closes the request.
 */
class Seller_deletion_model extends CI_Model
{
    const MIN_NOTE = 10;
    const MAX_NOTE = 1000;

    public function table_ready()
    {
        return $this->db->table_exists('seller_deletion_requests');
    }

    public function get_pending($seller_id)
    {
        if (!$this->table_ready()) {
            return null;
        }
        $row = $this->db->where(['seller_id' => (int) $seller_id, 'status' => 'pending'])
            ->order_by('id', 'DESC')->limit(1)->get('seller_deletion_requests')->row_array();
        return $row ?: null;
    }

    /** The latest declined request from the last two weeks, so the seller learns the outcome. */
    public function get_recent_declined($seller_id)
    {
        if (!$this->table_ready()) {
            return null;
        }
        $row = $this->db->where(['seller_id' => (int) $seller_id, 'status' => 'declined'])
            ->where('reviewed_at >', date('Y-m-d H:i:s', strtotime('-14 days')))
            ->order_by('id', 'DESC')->limit(1)->get('seller_deletion_requests')->row_array();
        return $row ?: null;
    }

    public function count_pending()
    {
        return $this->table_ready()
            ? (int) $this->db->where('status', 'pending')->count_all_results('seller_deletion_requests')
            : 0;
    }

    /**
     * File a request. Returns [error(bool), message].
     * One pending request per seller; the note is required.
     */
    public function create($seller_id, $note)
    {
        if (!$this->table_ready()) {
            return [true, 'Account deletion requests are not available yet. Please contact support.'];
        }
        $note = trim(preg_replace('/\s+/u', ' ', (string) $note));
        if (mb_strlen($note) < self::MIN_NOTE) {
            return [true, 'Please tell us why you want to leave (at least ' . self::MIN_NOTE . ' characters).'];
        }
        if ($this->get_pending($seller_id)) {
            return [true, 'You already have a deletion request waiting for the admin.'];
        }
        $note = mb_substr($note, 0, self::MAX_NOTE);

        $user = $this->db->select('username, mobile, email')->where('id', (int) $seller_id)->get('users')->row_array();
        $shop = $this->db->select('shop_name')->where('user_id', (int) $seller_id)->get('seller_data')->row_array();
        $snapshot = json_encode([
            'name'   => $user['username'] ?? '',
            'mobile' => $user['mobile'] ?? '',
            'email'  => $user['email'] ?? '',
            'shop'   => $shop['shop_name'] ?? '',
        ], JSON_UNESCAPED_UNICODE);

        $this->db->insert('seller_deletion_requests', [
            'seller_id' => (int) $seller_id, 'note' => $note, 'snapshot' => $snapshot, 'status' => 'pending',
        ]);
        $id = (int) $this->db->insert_id();

        $label = !empty($user['username']) ? $user['username'] : ('Seller #' . $seller_id);
        $this->db->insert('system_notification', [
            'title'   => 'Seller account deletion requested',
            'message' => $label . ' has asked for their seller account to be deleted. Review it under Sellers > Deletion Requests.',
            'type'    => 'seller_deletion_request',
            'type_id' => $id,
            'read_by' => 0,
        ]);
        return [false, 'Your request has been sent to the admin. Your account stays active until they act on it.'];
    }

    /** The seller changed their mind. Returns [error, message]. */
    public function cancel($seller_id)
    {
        $pending = $this->get_pending($seller_id);
        if (!$pending) {
            return [true, 'There is no pending request to cancel.'];
        }
        $this->db->where('id', $pending['id'])->delete('seller_deletion_requests');
        return [false, 'Your deletion request was cancelled.'];
    }

    public function get_request($id)
    {
        $row = $this->db->where('id', (int) $id)->get('seller_deletion_requests')->row_array();
        return $row ?: null;
    }

    /** Admin declines: the account is untouched and the seller is told why. */
    public function decline($id, $admin_id, $admin_note)
    {
        $req = $this->get_request($id);
        if (!$req || $req['status'] !== 'pending') {
            return [true, 'This request is no longer pending.'];
        }
        $admin_note = mb_substr(trim((string) $admin_note), 0, 500);
        $this->db->where('id', (int) $id)->update('seller_deletion_requests', [
            'status' => 'declined', 'admin_note' => $admin_note,
            'reviewed_at' => date('Y-m-d H:i:s'), 'reviewed_by' => (int) $admin_id,
        ]);
        add_user_notification((int) $req['seller_id'], 'Account deletion request not approved',
            'Your account deletion request was not approved and your account stays active.'
            . ($admin_note !== '' ? ' Note from admin: ' . $admin_note : ''),
            'seller_deletion_request', 'seller/home/profile', $id);
        return [false, 'Request declined. The seller has been told.'];
    }

    /** Close a request whose account the admin has now deleted. */
    public function complete($id, $admin_id)
    {
        $req = $this->get_request($id);
        if (!$req || $req['status'] !== 'pending') {
            return [true, 'This request is no longer pending.'];
        }
        // Only close it if the account really is gone.
        if ($this->db->where('id', (int) $req['seller_id'])->count_all_results('users') > 0) {
            return [true, 'The seller account still exists, so the request was left open.'];
        }
        $this->db->where('id', (int) $id)->update('seller_deletion_requests', [
            'status' => 'completed', 'reviewed_at' => date('Y-m-d H:i:s'), 'reviewed_by' => (int) $admin_id,
        ]);
        return [false, 'Request closed.'];
    }

    /**
     * Requests whose seller account was deleted some other way (e.g. directly from Manage
     * Sellers) are closed, so they do not sit in the pending list forever.
     */
    public function reconcile()
    {
        if (!$this->table_ready()) {
            return;
        }
        $rows = $this->db->select('r.id')->from('seller_deletion_requests r')
            ->join('users u', 'u.id = r.seller_id', 'left')
            ->where('r.status', 'pending')->where('u.id IS NULL', null, false)->get()->result_array();
        foreach ($rows as $r) {
            $this->db->where('id', $r['id'])->update('seller_deletion_requests', [
                'status' => 'completed', 'reviewed_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * What deleting this seller would take with it - shown to the admin before they decide.
     * Deleting a seller removes their products and their order lines.
     */
    public function impact($seller_id)
    {
        $seller_id = (int) $seller_id;
        $products = (int) $this->db->where('seller_id', $seller_id)->count_all_results('products');
        $open = (int) $this->db->where('seller_id', $seller_id)
            ->where_not_in('active_status', ['delivered', 'cancelled', 'returned'])
            ->count_all_results('order_items');
        $total_orders = (int) $this->db->where('seller_id', $seller_id)->count_all_results('order_items');
        $wallet = $this->db->select('balance')->where('id', $seller_id)->get('users')->row_array();
        return [
            'products'     => $products,
            'open_orders'  => $open,
            'order_lines'  => $total_orders,
            'wallet'       => $wallet ? (float) $wallet['balance'] : 0.0,
        ];
    }

    /** Requests for the admin screen, with the seller's name from the live row or the snapshot. */
    public function list_requests($statuses = ['pending'], $limit = 0)
    {
        $this->db->select('r.*, u.username, u.mobile, sd.shop_name')
            ->from('seller_deletion_requests r')
            ->join('users u', 'u.id = r.seller_id', 'left')
            ->join('seller_data sd', 'sd.user_id = r.seller_id', 'left')
            ->where_in('r.status', (array) $statuses)
            ->order_by('r.id', 'DESC');
        if ($limit > 0) {
            $this->db->limit($limit);
        }
        $rows = $this->db->get()->result_array();
        foreach ($rows as &$r) {
            $snap = json_decode((string) $r['snapshot'], true) ?: [];
            // ?: rather than ??: signup leaves some of these as '' rather than NULL.
            $r['display_name']   = $r['username'] ?: (!empty($snap['name']) ? $snap['name'] : ($r['shop_name'] ?: ('Seller #' . $r['seller_id'])));
            $r['display_shop']   = $r['shop_name'] ?: (isset($snap['shop']) ? $snap['shop'] : '');
            $r['display_mobile'] = $r['mobile'] ?: (isset($snap['mobile']) ? $snap['mobile'] : '');
        }
        return $rows;
    }
}
