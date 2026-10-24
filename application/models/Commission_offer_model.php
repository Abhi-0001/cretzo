<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Zero-commission launch offer: the first N sellers (20) pay 0% commission on their first M
 * orders (10). See migration 087 for the schema and the rules behind it.
 *
 * Everything that decides or displays the offer goes through this model:
 *   - enrol()            at sign-up / by an admin; the seller cap is enforced atomically here.
 *   - checkout_rate()    Order_model::place_order() asks once per seller per order; a hit
 *                        locks 0% onto every line of that seller's part of the order.
 *   - seller_status()    what every seller and admin screen shows.
 *
 * "Orders used" is counted from order_items, never stored: an order counts while any of its
 * lines for this seller carries commission_rate_source = 'zero_offer' and is not cancelled or
 * returned. A cancelled order therefore hands its slot back automatically.
 */
class Commission_offer_model extends CI_Model
{
    const SOURCE = 'zero_offer';
    const TABLE = 'seller_commission_offer';

    private $defaults = ['enabled' => 1, 'seller_limit' => 20, 'orders_per_seller' => 10];

    /** Per-request memo of seller_status(), cleared by every write below. */
    private $status_memo = [];

    public function table_ready()
    {
        return $this->db->table_exists(self::TABLE);
    }

    /* ----------------------------------------------------------------- settings ---- */

    public function settings()
    {
        $raw = get_settings('zero_commission_offer', true);
        $s = is_array($raw) ? array_merge($this->defaults, $raw) : $this->defaults;
        return [
            'enabled'           => (int) !empty($s['enabled']),
            'seller_limit'      => max(0, (int) $s['seller_limit']),
            'orders_per_seller' => max(0, (int) $s['orders_per_seller']),
        ];
    }

    public function save_settings($enabled, $seller_limit, $orders_per_seller)
    {
        $value = json_encode([
            'enabled'           => $enabled ? 1 : 0,
            'seller_limit'      => max(0, (int) $seller_limit),
            'orders_per_seller' => max(0, (int) $orders_per_seller),
        ]);
        $exists = $this->db->where('variable', 'zero_commission_offer')->count_all_results('settings');
        if ($exists) {
            $this->db->where('variable', 'zero_commission_offer')->update('settings', ['value' => $value]);
        } else {
            $this->db->insert('settings', ['variable' => 'zero_commission_offer', 'value' => $value]);
        }
        clear_settings_cache();
        $this->status_memo = [];
        return true;
    }

    /* --------------------------------------------------------------- enrolment ---- */

    public function enrolled_count()
    {
        if (!$this->table_ready()) {
            return 0;
        }
        return (int) $this->db->where('status', 1)->count_all_results(self::TABLE);
    }

    public function slots_left()
    {
        $s = $this->settings();
        return max(0, $s['seller_limit'] - $this->enrolled_count());
    }

    /** Is the offer still open to new sellers? Drives the sign-up / login banners. */
    public function is_open()
    {
        $s = $this->settings();
        return $this->table_ready() && $s['enabled'] && $s['orders_per_seller'] > 0 && $this->slots_left() > 0;
    }

    /**
     * Enrol a seller in the offer, if a slot is free.
     *
     * The cap check and the insert are ONE statement (INSERT ... SELECT ... WHERE count < cap),
     * so two sign-ups racing for the last slot cannot both get it. The UNIQUE key on seller_id
     * makes a repeat call a no-op.
     *
     * @param int    $seller_id
     * @param string $source 'signup' | 'admin'
     * @return array{ok: bool, message: string}
     */
    public function enrol($seller_id, $source = 'signup')
    {
        $seller_id = (int) $seller_id;
        if ($seller_id <= 0 || !$this->table_ready()) {
            return ['ok' => false, 'message' => 'Offer is not available.'];
        }
        $s = $this->settings();

        $existing = $this->db->where('seller_id', $seller_id)->get(self::TABLE)->row_array();
        if (!empty($existing) && (int) $existing['status'] === 1) {
            return ['ok' => true, 'message' => 'Seller is already enrolled.'];
        }

        // New sign-ups only join while the offer is switched on. An admin can enrol by hand
        // while it is off (e.g. to honour a promise made before it was switched off), but the
        // seller cap still applies to everyone.
        if ($source === 'signup' && (!$s['enabled'] || $s['orders_per_seller'] <= 0)) {
            return ['ok' => false, 'message' => 'The offer is switched off.'];
        }
        if (!$this->is_seller($seller_id)) {
            return ['ok' => false, 'message' => 'That user is not a seller.'];
        }

        $now = date('Y-m-d H:i:s');
        if (!empty($existing)) {
            // Re-instating a revoked seller takes a slot again.
            $this->db->query(
                'UPDATE ' . self::TABLE . ' t
                 JOIN (SELECT COUNT(*) AS n FROM ' . self::TABLE . ' WHERE status = 1) c
                 SET t.status = 1, t.revoked_at = NULL
                 WHERE t.seller_id = ? AND c.n < ?',
                [$seller_id, $s['seller_limit']]
            );
        } else {
            $this->db->query(
                'INSERT INTO ' . self::TABLE . ' (seller_id, orders_limit, source, status, enrolled_at)
                 SELECT ?, ?, ?, 1, ? FROM DUAL
                 WHERE (SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE status = 1) < ?',
                [$seller_id, $s['orders_per_seller'], $source === 'admin' ? 'admin' : 'signup', $now, $s['seller_limit']]
            );
        }
        $this->status_memo = [];

        if ($this->db->affected_rows() > 0) {
            return ['ok' => true, 'message' => 'Seller enrolled in the zero-commission offer.'];
        }
        return ['ok' => false, 'message' => 'All ' . $s['seller_limit'] . ' offer places are taken.'];
    }

    /** Called from every seller-creation path. Never throws and never blocks registration. */
    public function enrol_new_seller($seller_id)
    {
        try {
            return $this->enrol($seller_id, 'signup');
        } catch (Throwable $e) {
            log_message('error', 'Commission_offer_model::enrol_new_seller(' . (int) $seller_id . '): ' . $e->getMessage());
            return ['ok' => false, 'message' => 'error'];
        }
    }

    public function revoke($seller_id)
    {
        $this->db->where('seller_id', (int) $seller_id)->where('status', 1)
            ->update(self::TABLE, ['status' => 0, 'revoked_at' => date('Y-m-d H:i:s')]);
        $this->status_memo = [];
        return $this->db->affected_rows() > 0;
    }

    private function is_seller($seller_id)
    {
        return $this->db->where(['user_id' => (int) $seller_id, 'group_id' => 4])->count_all_results('users_groups') > 0;
    }

    /* ------------------------------------------------------------------ usage ------ */

    /** Distinct orders of this seller that are using an offer slot right now. */
    public function orders_used($seller_id)
    {
        $row = $this->db->query(
            "SELECT COUNT(DISTINCT order_id) AS n FROM order_items
             WHERE seller_id = ? AND commission_rate_source = ?
               AND active_status NOT IN ('cancelled', 'returned')",
            [(int) $seller_id, self::SOURCE]
        )->row_array();
        return (int) $row['n'];
    }

    /**
     * The offer as it stands for one seller.
     *
     * @return array{enrolled: bool, active: bool, limit: int, used: int, remaining: int,
     *               enrolled_at: string|null, revoked: bool}
     */
    public function seller_status($seller_id)
    {
        $seller_id = (int) $seller_id;
        if (isset($this->status_memo[$seller_id])) {
            return $this->status_memo[$seller_id];
        }
        $out = ['enrolled' => false, 'active' => false, 'limit' => 0, 'used' => 0, 'remaining' => 0, 'enrolled_at' => null, 'revoked' => false];
        if ($seller_id > 0 && $this->table_ready()) {
            $row = $this->db->where('seller_id', $seller_id)->get(self::TABLE)->row_array();
            if (!empty($row)) {
                $used = $this->orders_used($seller_id);
                $limit = (int) $row['orders_limit'];
                $out = [
                    'enrolled'    => (int) $row['status'] === 1,
                    'active'      => (int) $row['status'] === 1 && $used < $limit,
                    'limit'       => $limit,
                    'used'        => min($used, $limit),
                    'remaining'   => max(0, $limit - $used),
                    'enrolled_at' => $row['enrolled_at'],
                    'revoked'     => (int) $row['status'] !== 1,
                ];
            }
        }
        return $this->status_memo[$seller_id] = $out;
    }

    /**
     * The rate to lock onto a new order for this seller, or NULL when the offer does not apply
     * (the caller then uses the category rate). Asked once per seller per order.
     *
     * @return array{rate: float, source: string}|null
     */
    public function checkout_rate($seller_id)
    {
        $st = $this->seller_status($seller_id);
        if (!$st['active']) {
            return null;
        }
        // The order about to be placed takes one of the remaining slots; drop the memo so the
        // next order in the same request (POS, bulk) re-counts.
        unset($this->status_memo[(int) $seller_id]);
        return ['rate' => 0.0, 'source' => self::SOURCE];
    }

    /** Every enrolled (or revoked) seller with their usage, for the admin screen. */
    public function admin_list()
    {
        if (!$this->table_ready()) {
            return [];
        }
        $rows = $this->db->query(
            "SELECT o.*, u.username, u.mobile, COALESCE(NULLIF(sd.shop_name, ''), sd.store_name) AS store
             FROM " . self::TABLE . " o
             LEFT JOIN users u ON u.id = o.seller_id
             LEFT JOIN seller_data sd ON sd.user_id = o.seller_id
             ORDER BY o.status DESC, o.enrolled_at ASC, o.id ASC"
        )->result_array();
        foreach ($rows as &$r) {
            $used = $this->orders_used($r['seller_id']);
            $r['used'] = min($used, (int) $r['orders_limit']);
            $r['remaining'] = max(0, (int) $r['orders_limit'] - $used);
            $r['offer_sales'] = (float) $this->db->query(
                "SELECT COALESCE(SUM(oi.sub_total / (1 + COALESCE(oi.tax_percent, 0) / 100)), 0) AS v
                 FROM order_items oi WHERE oi.seller_id = ? AND oi.commission_rate_source = ?
                   AND oi.active_status NOT IN ('cancelled', 'returned')",
                [(int) $r['seller_id'], self::SOURCE]
            )->row()->v;
        }
        return $rows;
    }

    /** Sellers not enrolled, for the admin "enrol a seller" picker. */
    public function enrollable_sellers()
    {
        $where = $this->table_ready() ? 'AND u.id NOT IN (SELECT seller_id FROM ' . self::TABLE . ')' : '';
        return $this->db->query(
            "SELECT u.id, u.username, u.mobile, COALESCE(NULLIF(sd.shop_name, ''), sd.store_name) AS store
             FROM users u JOIN users_groups ug ON ug.user_id = u.id AND ug.group_id = 4
             LEFT JOIN seller_data sd ON sd.user_id = u.id
             WHERE 1 = 1 $where ORDER BY u.id ASC"
        )->result_array();
    }
}
