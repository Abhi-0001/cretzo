<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Audit trail for every stock movement.
 *
 * Stock was previously mutated in place with no record of who changed it, why, or what it was
 * before. A seller reporting "my stock is wrong" could not be answered: an order deduction, a
 * cancellation restore, a manual adjustment and a CSV re-import all left the column looking
 * identical. This records each movement so a level can be reconstructed and disputed.
 *
 * Deliberately append-only - nothing updates or deletes rows here.
 */
class Migration_stock_logs extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('stock_logs')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => '11',
                'auto_increment' => TRUE,
                'NULL'           => FALSE,
            ],
            'product_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
            ],
            'product_variant_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
            ],
            // Which column the movement actually landed on, since that depends on stock_type.
            'stock_table' => [
                'type'       => 'VARCHAR',
                'constraint' => '32',
                'NULL'       => TRUE,
            ],
            // order_deduct | order_restore | manual_add | manual_subtract | import | expiry_restore
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => '32',
                'NULL'       => FALSE,
                'default'    => 'unknown',
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
                'default'    => 0,
            ],
            // Signed: negative for a deduction. quantity stays absolute for easy summing.
            'delta' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
                'default'    => 0,
            ],
            'stock_before' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
            ],
            'stock_after' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
            ],
            'order_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
            ],
            // Who caused it, when it is attributable to a logged-in user.
            'user_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
            ],
            'note' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'NULL'       => TRUE,
            ],
            'created_at TIMESTAMP default CURRENT_TIMESTAMP',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('product_variant_id');
        $this->dbforge->add_key('product_id');
        $this->dbforge->add_key('order_id');
        $this->dbforge->create_table('stock_logs', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('stock_logs', TRUE);
    }
}
