<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stores WHY a customer cancelled or returned an item.
 *
 * The storefront's return flow has always asked the customer nothing: it fired a
 * bare window.confirm() and posted order_id + status. The rebuilt My Account >
 * Orders popup does ask for a reason, but there was nowhere to put it - so the
 * answer was collected and thrown away, and nobody could tell whether returns
 * were coming from sizing, damage in transit, or the wrong item being shipped.
 *
 * That is the difference between "we had 40 returns last month" and "38 of them
 * were sizing on one seller's kurtas", so the column lives on `order_items`
 * rather than on `orders`: returns are already tracked per item (see
 * My_account::update_order_item_status and the seller/admin status screens), and
 * per item is the grain that can be grouped by product and by seller.
 *
 * Nullable with no default on purpose - NULL means "not asked / not answered"
 * and stays distinguishable from an empty answer. Nothing reads it as required.
 */
class Migration_order_return_reason extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('return_reason', 'order_items')) {
            $this->dbforge->add_column('order_items', [
                'return_reason' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                    'default'    => null,
                    // Next to the status columns it belongs with, not appended at
                    // the end of a 40-column table.
                    'after'      => 'active_status',
                ],
            ]);
        }

        /*
         * `return_reason_at` is what makes the column auditable: a reason with no
         * timestamp cannot be tied to the status change that produced it, and an
         * item can be cancelled, reinstated and returned again.
         */
        if (!$this->db->field_exists('return_reason_at', 'order_items')) {
            $this->dbforge->add_column('order_items', [
                'return_reason_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'return_reason',
                ],
            ]);
        }
    }

    public function down()
    {
        foreach (['return_reason_at', 'return_reason'] as $column) {
            if ($this->db->field_exists($column, 'order_items')) {
                $this->dbforge->drop_column('order_items', $column);
            }
        }
    }
}
