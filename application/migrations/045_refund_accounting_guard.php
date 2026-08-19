<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Separates "the customer has been paid" from "the books have been adjusted".
 *
 * Migration 044 gave order_items a single `refunded_at` stamp and process_refund() skipped its
 * ENTIRE body once that was set. That is right for the payment - nobody should be paid twice -
 * but it also gated the accounting: the seller commission clawback, the per-seller
 * order_charges rewrite and the order totals.
 *
 * The two are not always done by the same actor. An admin who refunds a card from the order
 * screen pays the customer and stamps `refunded_at`, but changes nothing about the order's
 * books. When that item's return was approved afterwards, process_refund() saw the stamp and
 * returned immediately - so the seller kept the commission on a sale that had been refunded,
 * the order's totals still counted the returned line, and the seller's parcel in order_charges
 * was never resized. The money left the platform and nothing recorded that it had.
 *
 * `accounted_at` is the second, independent stamp. Payment is guarded by `refunded_at`,
 * accounting by `accounted_at`, and each runs at most once regardless of which path gets there
 * first or in what order.
 */
class Migration_refund_accounting_guard extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('order_items')) {
            return;
        }

        if (!$this->db->field_exists('accounted_at', 'order_items')) {
            $this->dbforge->add_column('order_items', array(
                'accounted_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                    'default' => NULL,
                ),
            ));
        }

        // Backfill: anything already stamped as refunded went through the old code, which did
        // the payment and the accounting together in one pass. Marking those accounted keeps
        // the guard honest - without it the next status change on an old item would re-run the
        // clawback and the totals rewrite against figures that already had it applied.
        // Raw SQL because the query builder would bind 'refunded_at' as a string literal
        // rather than reference the column.
        $this->db->query('UPDATE `order_items` SET `accounted_at` = `refunded_at`
                          WHERE `refunded_at` IS NOT NULL AND `accounted_at` IS NULL');
    }

    public function down()
    {
        if ($this->db->field_exists('accounted_at', 'order_items')) {
            $this->dbforge->drop_column('order_items', 'accounted_at');
        }
    }
}
