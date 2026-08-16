<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Records when an order item was actually delivered, and backfills it for existing rows.
 *
 * WHY: the seller-payout delay and the customer return window are supposed to be the same
 * window - a seller should not be paid until the buyer can no longer send the item back.
 * They were measured from different dates:
 *
 *   - settlement eligibility (Seller_model::settle_seller_commission)
 *         oi.date_added + max_product_return_days   <- ORDER PLACEMENT date
 *   - customer return eligibility (function_helper.php, is_returnable)
 *         delivery date + max_product_return_days   <- DELIVERY date
 *
 * Since delivery always happens at or after placement, the settlement clock ran out FIRST.
 * For any order that took longer than max_product_return_days to arrive - i.e. essentially
 * every real order - the seller was paid the moment the item was marked delivered, with the
 * buyer's entire return window still open. Every return approved in that overlap refunds the
 * customer while the seller keeps the money.
 *
 * order_items had no column recording the delivery moment at all (only a free-text JSON
 * status history), so there was nothing to measure the correct window from. This adds it.
 */
class Migration_order_item_delivered_at extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('delivered_at', 'order_items')) {
            $this->dbforge->add_column('order_items', [
                'delivered_at' => [
                    'type' => 'DATETIME',
                    'NULL' => TRUE,
                    'after' => 'active_status',
                ],
            ]);
        }

        $existing = $this->db->query(
            "SHOW INDEX FROM `order_items` WHERE Key_name = 'idx_order_items_settlement'"
        )->num_rows();
        if ($existing == 0) {
            // The settlement sweep filters on exactly these three columns. active_status is a
            // varchar(1024) under utf8mb4 (4096 bytes), which alone blows past InnoDB's
            // 3072-byte index limit, so it is indexed by a 32-byte prefix - far longer than
            // any status value actually stored ('return_request_approved' is the longest at
            // 23 chars) and therefore just as selective.
            $this->db->query(
                'ALTER TABLE `order_items` ADD KEY `idx_order_items_settlement` (`active_status`(32), `is_credited`, `delivered_at`)'
            );
        }

        $this->backfill_delivered_at();
    }

    /**
     * Recover the delivery moment for already-delivered items from the JSON status history.
     *
     * The history is a list of [status, "d-m-Y h:i:sa"] pairs. Parsed in PHP rather than SQL
     * because the timestamps are stored in a display format MySQL's STR_TO_DATE handling of
     * lowercase am/pm cannot be relied on across versions. Rows whose history cannot be parsed
     * fall back to date_added, which is the pre-existing behaviour, so nothing regresses.
     */
    private function backfill_delivered_at()
    {
        $rows = $this->db
            ->select('id, status, date_added')
            ->where('active_status', 'delivered')
            ->where('delivered_at IS NULL', null, false)
            ->get('order_items')
            ->result_array();

        foreach ($rows as $row) {
            $delivered_at = null;
            $history = json_decode($row['status'], true);

            if (is_array($history)) {
                foreach ($history as $entry) {
                    if (!is_array($entry) || count($entry) < 2 || $entry[0] !== 'delivered') {
                        continue;
                    }
                    $ts = strtotime(str_replace(['am', 'pm'], [' am', ' pm'], $entry[1]));
                    if ($ts !== false) {
                        $delivered_at = date('Y-m-d H:i:s', $ts);
                    }
                    break;
                }
            }

            if ($delivered_at === null) {
                $delivered_at = $row['date_added'];
            }

            $this->db->set('delivered_at', $delivered_at)->where('id', $row['id'])->update('order_items');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('delivered_at', 'order_items')) {
            $this->dbforge->drop_column('order_items', 'delivered_at');
        }
    }
}
