<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Makes refunds and stock restoration idempotent, and gives return shipments a home.
 *
 * There are SIX independent code paths that can refund an order item and restore its stock:
 * the customer's own cancel/return, the app API, the admin order screen, the seller order
 * screen, the delivery boy screen, the return-request approval screen, and the Shiprocket
 * webhook. None of them knew about the others, and process_refund() had no guard, so a
 * perfectly ordinary return - approve the request, then mark the item "returned" when the
 * parcel comes back - credited the customer twice and put the stock back twice.
 *
 * Rather than try to make seven callers agree on who goes first, the fact that an item has
 * already been paid out is recorded ON THE ITEM. process_refund() and the new
 * restore_order_item_stock() both check and set these, so whichever path runs first wins and
 * every later one is a no-op. That also covers webhook retries and double-clicks, which no
 * amount of caller ordering ever could.
 *
 * Backfill matters here: without it, every order item that was ALREADY cancelled or returned
 * before this migration would look un-refunded, and the very next status change on it would
 * hand out a second refund. Existing finished items are therefore stamped as already handled.
 */
class Migration_return_refund_integrity extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('order_items')) {
            return;
        }

        $fields = array();

        if (!$this->db->field_exists('refunded_at', 'order_items')) {
            $fields['refunded_at'] = array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'default' => NULL,
            );
        }
        if (!$this->db->field_exists('refund_amount', 'order_items')) {
            $fields['refund_amount'] = array(
                'type' => 'DECIMAL',
                'constraint' => '24,2',
                'null' => TRUE,
                'default' => NULL,
            );
        }
        // 'wallet' when the money went to the customer's wallet, 'gateway' when it was pushed
        // back to the original payment instrument. The admin Refund button reads this so it
        // cannot pay a Razorpay refund on top of a wallet credit for the same item.
        if (!$this->db->field_exists('refund_mode', 'order_items')) {
            $fields['refund_mode'] = array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => TRUE,
                'default' => NULL,
            );
        }
        if (!$this->db->field_exists('stock_restored_at', 'order_items')) {
            $fields['stock_restored_at'] = array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'default' => NULL,
            );
        }

        if (!empty($fields)) {
            $this->dbforge->add_column('order_items', $fields);
        }

        // Marks an order_tracking row as the REVERSE leg (customer -> seller) rather than the
        // forward delivery. Without it the Shiprocket webhook cannot tell "your parcel is on
        // its way to you" from "the return you approved has been collected", and both write to
        // the same order item.
        if ($this->db->table_exists('order_tracking') && !$this->db->field_exists('is_return', 'order_tracking')) {
            $this->dbforge->add_column('order_tracking', array(
                'is_return' => array(
                    'type' => 'TINYINT',
                    'constraint' => '4',
                    'null' => FALSE,
                    'default' => '0',
                ),
            ));
        }

        $this->backfill_finished_items();
    }

    /**
     * Stamp items that are already finished, so the guard treats them as settled rather than
     * as fresh candidates for a refund.
     *
     * Timestamps come from the actual refund transaction where one exists, so the audit trail
     * stays truthful; items with no transaction row (COD orders where nothing was ever owed,
     * for instance) are stamped with the item's own date so the guard still holds without
     * inventing a payment that never happened - refund_amount is left NULL for those.
     */
    private function backfill_finished_items()
    {
        $finished = $this->db
            ->select('id, active_status, date_added')
            ->where_in('active_status', array('cancelled', 'returned', 'return_request_approved'))
            ->where('refunded_at IS NULL', null, false)
            ->get('order_items')
            ->result_array();

        foreach ($finished as $item) {
            $set = array(
                // Stock: these items have all been through a path that restored it. Stamping
                // prevents a future status change from restoring it a second time.
                'stock_restored_at' => $item['date_added'],
                'refunded_at' => $item['date_added'],
            );

            if ($this->db->table_exists('transactions')) {
                $refund = $this->db
                    ->select('amount, date_created')
                    ->where('order_item_id', $item['id'])
                    ->where_in('type', array('credit', 'refund'))
                    ->order_by('id', 'ASC')
                    ->limit(1)
                    ->get('transactions')
                    ->row_array();

                if (!empty($refund)) {
                    $set['refund_amount'] = $refund['amount'];
                    $set['refund_mode'] = 'wallet';
                    if (!empty($refund['date_created'])) {
                        $set['refunded_at'] = $refund['date_created'];
                    }
                }
            }

            $this->db->where('id', $item['id'])->update('order_items', $set);
        }
    }

    public function down()
    {
        foreach (array('refunded_at', 'refund_amount', 'refund_mode', 'stock_restored_at') as $column) {
            if ($this->db->field_exists($column, 'order_items')) {
                $this->dbforge->drop_column('order_items', $column);
            }
        }
        if ($this->db->field_exists('is_return', 'order_tracking')) {
            $this->dbforge->drop_column('order_tracking', 'is_return');
        }
    }
}
