<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Store wallet money as DECIMAL, not DOUBLE.
 *
 * users.balance was DOUBLE, i.e. binary floating point, which cannot represent most decimal
 * money values exactly. Real balances in this database already read back as
 * 44444.36000000002 - the error compounds with every credit and debit, and any comparison
 * (a withdrawal's "amount <= balance" check, a reconciliation against the transaction log)
 * can disagree with what the seller is shown. DECIMAL(12,2) is exact.
 *
 * order_items.admin_commission_amount / seller_commission_amount were DOUBLE(15,2) which is
 * still binary floating point despite the scale, so they get the same treatment.
 *
 * MySQL converts existing values by rounding to 2 decimal places, which is exactly the
 * intended value in every case (44444.36000000002 -> 44444.36).
 */
class Migration_money_columns_decimal extends CI_Migration
{
    public function up()
    {
        $this->convert('users', 'balance', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
        $this->convert('users', 'cash_received', 'DECIMAL(12,2) NULL DEFAULT 0.00');
        $this->convert('order_items', 'admin_commission_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
        $this->convert('order_items', 'seller_commission_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
        $this->convert('transactions', 'amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
    }

    /**
     * Only touches the column when it is not already DECIMAL, so a replay is a no-op and an
     * install that already corrected the type by hand is left alone.
     */
    private function convert($table, $column, $definition)
    {
        if (!$this->db->table_exists($table) || !$this->db->field_exists($column, $table)) {
            return;
        }

        foreach ($this->db->field_data($table) as $field) {
            if ($field->name === $column && strtolower($field->type) === 'decimal') {
                return;
            }
        }

        $this->db->query('ALTER TABLE `' . $table . '` MODIFY `' . $column . '` ' . $definition);
    }

    public function down()
    {
        // Deliberately not reverted: going back to DOUBLE would reintroduce the rounding
        // error this migration exists to remove, and nothing depends on the old type.
    }
}
