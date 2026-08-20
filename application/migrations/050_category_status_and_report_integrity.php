<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Closes two data traps behind the product/stock reports, and indexes the shipping tables.
 *
 *  1. `categories.status` is nullable with no default. Every storefront product read filters on
 *     `(c.status = '1' OR c.status = '0')`, so a category row whose status was never set silently
 *     swallows every product filed under it - the products vanish from listings, search, sections
 *     and the stock report with no error anywhere. Seven categories on this database are in that
 *     state, including real ones ("HOME & LIVING", "FOOD & SNACKS", "Bags"). Backfilled to 0 and
 *     given an explicit default so it cannot recur.
 *
 *     0 rather than 1 is deliberate: category NAVIGATION requires status = 1
 *     (Category_model::get_categories), so backfilling to 1 would publish categories nobody chose
 *     to publish - including two named "__TEST_DELETE_ME__". 0 keeps them exactly where they are
 *     today while making the state explicit.
 *
 *  2. `order_tracking` had no index at all beyond its primary key, yet the Shiprocket webhook
 *     looks rows up by `awb_code` on every callback, and the order screens look them up by
 *     `order_id` / `shipment_id`.
 *
 *  3. `order_tracking.courier_agency` is varchar(20), which truncates real courier names
 *     ("Delhivery Surface 10kg" is 22 characters), and `data` / `others` are varchar(255) holding
 *     Shiprocket response fragments. Widened.
 */
class Migration_category_status_and_report_integrity extends CI_Migration
{
    public function up()
    {
        /* ---- 1. categories.status ------------------------------------------------- */
        if ($this->db->table_exists('categories')) {
            $this->db->query('UPDATE `categories` SET `status` = 0 WHERE `status` IS NULL');
            $this->db->query('ALTER TABLE `categories` MODIFY `status` TINYINT(4) NOT NULL DEFAULT 0');
        }

        /* ---- 2. order_tracking indexes ------------------------------------------- */
        $this->add_index('order_tracking', 'idx_order_tracking_awb', '`awb_code`');
        $this->add_index('order_tracking', 'idx_order_tracking_order', '`order_id`');
        $this->add_index('order_tracking', 'idx_order_tracking_shipment', '`shipment_id`');
        $this->add_index('order_tracking', 'idx_order_tracking_sr_order', '`shiprocket_order_id`');

        /* ---- 3. order_tracking column widths ------------------------------------- */
        if ($this->db->table_exists('order_tracking')) {
            $this->widen('order_tracking', 'courier_agency', 'VARCHAR(128) DEFAULT NULL', 128);
            $this->widen('order_tracking', 'data', 'TEXT DEFAULT NULL', 0);
            $this->widen('order_tracking', 'others', 'VARCHAR(255) NOT NULL DEFAULT ""', 255);
        }

        /* ---- 4. indexes the money reports scan on -------------------------------- */
        $this->add_index('transactions', 'idx_transactions_user_type', '`user_id`, `transaction_type`');
        $this->add_index('transactions', 'idx_transactions_order', '`order_id`');
        $this->add_index('order_items', 'idx_order_items_seller_status', '`seller_id`, `active_status`(32)');
        $this->add_index('orders', 'idx_orders_date_added', '`date_added`');
    }

    public function down()
    {
        foreach ([
            'order_tracking' => ['idx_order_tracking_awb', 'idx_order_tracking_order', 'idx_order_tracking_shipment', 'idx_order_tracking_sr_order'],
            'transactions'   => ['idx_transactions_user_type', 'idx_transactions_order'],
            'order_items'    => ['idx_order_items_seller_status'],
            'orders'         => ['idx_orders_date_added'],
        ] as $table => $indexes) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            foreach ($indexes as $index) {
                if ($this->index_exists($table, $index)) {
                    $this->db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
                }
            }
        }
        // The categories.status backfill is deliberately not reversed - restoring NULLs would
        // re-hide products with no way to tell which rows were originally NULL.
    }

    private function index_exists($table, $index)
    {
        $row = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $this->db->escape($index))->row_array();
        return !empty($row);
    }

    private function add_index($table, $index, $columns)
    {
        if (!$this->db->table_exists($table) || $this->index_exists($table, $index)) {
            return;
        }
        $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $index . '` (' . $columns . ')');
    }

    /**
     * Widens a column only when it is currently narrower than $min_len (0 = always widen,
     * used when converting to TEXT), so re-running never shrinks a column somebody enlarged.
     */
    private function widen($table, $column, $definition, $min_len)
    {
        $row = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($column))->row_array();
        if (empty($row)) {
            return;
        }
        if ($min_len > 0) {
            if (preg_match('/varchar\((\d+)\)/i', $row['Type'], $m) && (int) $m[1] >= $min_len) {
                return;
            }
        } elseif (stripos($row['Type'], 'text') !== false) {
            return;
        }
        $this->db->query('ALTER TABLE `' . $table . '` MODIFY `' . $column . '` ' . $definition);
    }
}
