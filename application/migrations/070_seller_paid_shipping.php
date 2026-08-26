<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller-paid shipping (the Meesho model).
 *
 * The customer is no longer charged freight at checkout - every order ships free - and the
 * ACTUAL courier charge Shiprocket bills for the parcel is recovered from the seller at
 * settlement instead. That needs three things the schema could not hold:
 *
 *  1. Somewhere to record what Shiprocket actually charged for a shipment. Nothing did:
 *     `order_charges.delivery_charge` is the figure QUOTED TO THE CUSTOMER at checkout, which
 *     under this model is always 0 and in any case was a serviceability estimate taken before
 *     the parcel existed - not the freight the courier eventually billed.
 *  2. A per-ORDER-ITEM freight figure, because settlement runs per order item. A parcel's
 *     freight is one number for several items, so it is apportioned across them.
 *  3. The `seller_paid_shipping` switch, seeded ON. `seller_settlements.shipping_deduction`
 *     already exists (migration 037) and has been written as 0 on every settlement so far,
 *     because calculate_settlement_breakdown() was always called with $shipping = 0.
 *
 * Nothing here is destructive and every column defaults to 0 / NULL, so existing orders read
 * exactly as they did before: no freight captured means no freight deducted.
 */
class Migration_seller_paid_shipping extends CI_Migration
{
    public function up()
    {
        /*
         * Freight as billed by the courier, held against the shipment it belongs to.
         * `freight_charge_source` records WHERE the figure came from - the AWB assignment
         * response, a later reconciliation sweep, or an admin typing it in - because a 0.00
         * is otherwise ambiguous between "the courier charged nothing" (never true) and
         * "we never managed to capture it", and those need opposite responses.
         */
        $tracking_columns = [
            'freight_charge' => [
                'type'       => 'DOUBLE',
                'null'       => FALSE,
                'default'    => 0,
                'comment'    => 'Actual courier freight billed by Shiprocket for this shipment',
            ],
            'freight_charge_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => TRUE,
                'comment'    => 'awb_assignment | reconciliation | manual',
            ],
            'freight_captured_at' => [
                'type'       => 'DATETIME',
                'null'       => TRUE,
            ],
        ];
        foreach ($tracking_columns as $column => $definition) {
            if (!$this->db->field_exists($column, 'order_tracking')) {
                $this->dbforge->add_column('order_tracking', [$column => $definition]);
            }
        }

        /*
         * Parcel-level total, for the seller's own order screen and for reconciliation against
         * the sum of the apportioned per-item figures. Deliberately NOT reusing
         * order_charges.delivery_charge, which means "what the customer was quoted".
         */
        if (!$this->db->field_exists('freight_charge', 'order_charges')) {
            $this->dbforge->add_column('order_charges', [
                'freight_charge' => [
                    'type'    => 'DOUBLE',
                    'null'    => FALSE,
                    'default' => 0,
                    'comment' => 'Actual Shiprocket freight for this seller parcel, recovered at settlement',
                ],
            ]);
        }

        /*
         * The per-item figure settlement actually deducts. Named to match the settlement
         * breakdown key and the seller_settlements column it ends up in, so the same number
         * carries one name from capture through to the seller's statement.
         */
        if (!$this->db->field_exists('shipping_deduction', 'order_items')) {
            $this->dbforge->add_column('order_items', [
                'shipping_deduction' => [
                    'type'    => 'DOUBLE',
                    'null'    => FALSE,
                    'default' => 0,
                    'comment' => 'Freight apportioned to this item, deducted from the seller at settlement',
                ],
            ]);
        }

        /*
         * The settlement sweep reads freight per item; without an index it is a column on a
         * table already scanned by (active_status, is_credited, delivered_at) so no new index
         * is needed there. What IS worth an index is finding shipments whose freight was never
         * captured, which the reconciliation cron does on every run.
         */
        if ($this->db->field_exists('freight_charge', 'order_tracking')) {
            $existing = $this->db->query("SHOW INDEX FROM `order_tracking` WHERE Key_name = 'idx_order_tracking_freight'")->num_rows();
            if ($existing == 0) {
                $this->db->query('ALTER TABLE `order_tracking` ADD KEY `idx_order_tracking_freight` (`freight_charge`, `is_canceled`, `is_return`)');
            }
        }

        /*
         * Seed the switch ON - this is the model the marketplace is moving to, and an install
         * that never opens the settings screen must still ship free and recover freight.
         * Seeded only when absent, so an admin who has already turned it off keeps it off.
         */
        $row = $this->db->where('variable', 'system_settings')->get('settings')->row_array();
        if (empty($row)) {
            return;
        }
        $settings = json_decode($row['value'], true);
        if (!is_array($settings)) {
            return;
        }
        if (!isset($settings['seller_paid_shipping']) || $settings['seller_paid_shipping'] === '') {
            $settings['seller_paid_shipping'] = '1';
            $this->db->where('variable', 'system_settings')
                ->update('settings', ['value' => json_encode($settings)]);
            if (function_exists('clear_settings_cache')) {
                clear_settings_cache();
            }
        }
    }

    public function down()
    {
        if ($this->db->field_exists('freight_charge', 'order_tracking')) {
            $existing = $this->db->query("SHOW INDEX FROM `order_tracking` WHERE Key_name = 'idx_order_tracking_freight'")->num_rows();
            if ($existing > 0) {
                $this->db->query('ALTER TABLE `order_tracking` DROP KEY `idx_order_tracking_freight`');
            }
        }
        foreach (['freight_charge', 'freight_charge_source', 'freight_captured_at'] as $column) {
            if ($this->db->field_exists($column, 'order_tracking')) {
                $this->dbforge->drop_column('order_tracking', $column);
            }
        }
        if ($this->db->field_exists('freight_charge', 'order_charges')) {
            $this->dbforge->drop_column('order_charges', 'freight_charge');
        }
        if ($this->db->field_exists('shipping_deduction', 'order_items')) {
            $this->dbforge->drop_column('order_items', 'shipping_deduction');
        }
    }
}
