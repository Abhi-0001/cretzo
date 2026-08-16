<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Itemised commission breakdown.
 *
 * Two problems this fixes at the schema level:
 *
 * 1. The commission RATE was never recorded against the sale. It was looked up from the
 *    seller's *current* plan at settlement time, which can be weeks later - so a seller who
 *    changed plan in between had the new rate applied retroactively to sales already
 *    completed under the old one. order_items now carries the rate that was in force when
 *    the sale happened, plus where it came from.
 *
 * 2. A settlement could only ever be expressed as one commission number and one net number,
 *    so "deductions" could never be itemised on a seller statement. seller_settlements now
 *    carries a line for each component of the ladder.
 *
 * Every new column defaults to 0 / NULL, so existing settlements read back exactly as they
 * were recorded and nothing is restated.
 */
class Migration_commission_breakdown extends CI_Migration
{
    public function up()
    {
        // --- order_items: lock the rate to the sale -------------------------------------
        $add = [];
        if (!$this->db->field_exists('commission_rate', 'order_items')) {
            $add['commission_rate'] = [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'NULL'       => TRUE,
                'after'      => 'seller_commission_amount',
            ];
        }
        if (!$this->db->field_exists('commission_rate_source', 'order_items')) {
            // 'plan_slab' | 'platform_default' - which rule produced the rate above.
            $add['commission_rate_source'] = [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'NULL'       => TRUE,
                'after'      => 'commission_rate',
            ];
        }
        if (!empty($add)) {
            $this->dbforge->add_column('order_items', $add);
        }

        // --- seller_settlements: one column per statement line ---------------------------
        $lines = [
            'taxable_value'         => 'Commission base: the ex-GST value of the goods',
            'product_tax_amount'    => 'GST on the goods - passed through to the seller, not a deduction',
            'commission_gst_amount' => 'GST charged on the platform commission',
            'tcs_amount'            => 'TCS collected under GST',
            'tds_amount'            => 'TDS deducted under s.194-O',
            'shipping_deduction'    => 'Shipping recovered from the seller',
            'gateway_fee'           => 'Payment gateway fee passed on to the seller',
        ];

        $settlement_add = [];
        foreach ($lines as $column => $_comment) {
            if (!$this->db->field_exists($column, 'seller_settlements')) {
                $settlement_add[$column] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'NULL'       => FALSE,
                    'default'    => '0.00',
                ];
            }
        }
        if (!$this->db->field_exists('commission_rate_source', 'seller_settlements')) {
            $settlement_add['commission_rate_source'] = [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'NULL'       => TRUE,
            ];
        }
        if (!empty($settlement_add)) {
            $this->dbforge->add_column('seller_settlements', $settlement_add);
        }

        // Historic rows recorded commission on the GST-inclusive amount with no breakdown.
        // Backfilling taxable_value = order_amount is accurate for every existing row,
        // because no product in the catalogue carries a non-zero tax rate yet - so for all
        // of them the gross and the taxable value are genuinely the same number. Rows
        // created from here on are computed properly.
        // Raw SQL because this assigns one column from another, which the query builder's
        // update() would escape as a literal string rather than a column reference.
        $this->db->query('UPDATE `seller_settlements` SET `taxable_value` = `order_amount` WHERE `taxable_value` = 0');
    }

    public function down()
    {
        foreach (['commission_rate', 'commission_rate_source'] as $column) {
            if ($this->db->field_exists($column, 'order_items')) {
                $this->dbforge->drop_column('order_items', $column);
            }
        }
        foreach ([
            'taxable_value', 'product_tax_amount', 'commission_gst_amount',
            'tcs_amount', 'tds_amount', 'shipping_deduction', 'gateway_fee',
            'commission_rate_source',
        ] as $column) {
            if ($this->db->field_exists($column, 'seller_settlements')) {
                $this->dbforge->drop_column('seller_settlements', $column);
            }
        }
    }
}
