<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reattaches order_items rows that were written against an order id that does not exist.
 *
 * place_order() took `$this->db->insert_id()` after inserting the order and never checked whether
 * the insert had actually succeeded. When it had not - production runs with db_debug FALSE, so a
 * failed query returns quietly and execution carries on - every order item was written against
 * whatever insert_id() happened to hold: 0 when nothing had been inserted in that session, or the
 * id of the last AUTO_INCREMENT row otherwise, which on a wallet-paid checkout is the wallet debit
 * in `transactions`. The order row itself was missing, so the customer saw "Order Placed
 * Successfully" and an order nobody could find, while stock had already been deducted.
 *
 * The code path is fixed (Order_model::place_order now aborts and refunds the wallet debit). This
 * repairs the rows already in the table.
 *
 * On this database that is one row: order_items 11, user 25, seller 7, 449.00, written
 * 2026-01-01 18:03:48 with order_id 0 - while order 9 carries the same user, the same second and
 * the same 449.00 with no items at all.
 *
 * The match is deliberately narrow, because guessing which order a payment belongs to is not a
 * repair. A row is only reattached when ALL of the following hold, which together leave no room
 * for a different reading:
 *
 *   - its order_id names no existing order;
 *   - exactly ONE order has that same user_id AND that same date_added to the second;
 *   - that order has no order_items of its own;
 *   - the items' sub_total adds up to that order's `total`.
 *
 * Anything that does not match all four is left alone and reported in the log, because it needs a
 * human who can see the payment gateway.
 */
class Migration_reattach_orphaned_order_items extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('order_items') || !$this->db->table_exists('orders')) {
            return;
        }

        $orphans = $this->db->query('
            SELECT oi.id, oi.order_id, oi.user_id, oi.date_added, oi.sub_total
            FROM order_items oi
            LEFT JOIN orders o ON o.id = oi.order_id
            WHERE o.id IS NULL
        ')->result_array();

        if (empty($orphans)) {
            return;
        }

        // Group by the (user, second) pair they were written in: a multi-item order produces
        // several orphans that must all move to the same order.
        $groups = [];
        foreach ($orphans as $row) {
            $groups[$row['user_id'] . '@' . $row['date_added']][] = $row;
        }

        $reattached = 0;
        $skipped = 0;

        foreach ($groups as $key => $items) {
            list($user_id, $date_added) = explode('@', $key, 2);

            $candidates = $this->db->select('id, total')
                ->where('user_id', $user_id)
                ->where('date_added', $date_added)
                ->where('(SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = orders.id) =', 0, false)
                ->get('orders')->result_array();

            if (count($candidates) !== 1) {
                log_message('error', 'migration 053: ' . count($items) . ' orphaned order_items for user '
                    . $user_id . ' at ' . $date_added . ' matched ' . count($candidates)
                    . ' itemless orders - left alone, needs a human');
                $skipped += count($items);
                continue;
            }

            $sub_total = 0;
            foreach ($items as $row) {
                $sub_total += (float) $row['sub_total'];
            }

            // Compared to the paisa; orders.total excludes the delivery charge, which is what
            // the item sub_totals add up to.
            if (round($sub_total, 2) != round((float) $candidates[0]['total'], 2)) {
                log_message('error', 'migration 053: orphaned order_items for user ' . $user_id . ' at '
                    . $date_added . ' add up to ' . $sub_total . ' but order ' . $candidates[0]['id']
                    . ' totals ' . $candidates[0]['total'] . ' - left alone, needs a human');
                $skipped += count($items);
                continue;
            }

            foreach ($items as $row) {
                $this->db->set('order_id', $candidates[0]['id'])->where('id', $row['id'])->update('order_items');
                $reattached++;
            }
        }

        log_message('error', 'migration 053: reattached ' . $reattached . ' orphaned order_items, left ' . $skipped . ' for review');
    }

    public function down()
    {
        // Deliberately not reversible: the original order_id was a wrong value (0, or an id
        // belonging to another table), so there is nothing meaningful to put back.
    }
}
