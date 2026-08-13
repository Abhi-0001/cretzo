<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_seller_settlements extends CI_Migration
{
    public function up()
    {
        /* creating seller_settlements table */
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => '11',
                'auto_increment' => TRUE,
                'NULL'           => FALSE,
            ],
            'seller_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            'order_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            'order_item_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            'order_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'NULL'       => FALSE,
                'default'    => '0.00',
            ],
            'commission_percent' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'NULL'       => FALSE,
                'default'    => '0.00',
            ],
            'commission_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'NULL'       => FALSE,
                'default'    => '0.00',
            ],
            'net_payable' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'NULL'       => FALSE,
                'default'    => '0.00',
            ],
            'settlement_status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'NULL'       => FALSE,
                'default'    => 'settled',
            ],
            'created_at TIMESTAMP default CURRENT_TIMESTAMP',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('seller_id');
        $this->dbforge->create_table('seller_settlements', TRUE);

        // CI3's dbforge has NO add_unique_key() method - calling it threw
        // "Call to undefined method CI_DB_mysqli_forge::add_unique_key()" and killed the
        // migration run at 024, so nothing from 024 onward could ever be applied on any
        // installation. The unique index is added with plain SQL instead, guarded so a
        // replay doesn't fail with "Duplicate key name".
        $existing = $this->db->query(
            "SHOW INDEX FROM `seller_settlements` WHERE Key_name = 'uniq_order_item'"
        )->num_rows();

        if ($existing == 0) {
            $this->db->query(
                'ALTER TABLE `seller_settlements` ADD UNIQUE KEY `uniq_order_item` (`order_item_id`)'
            );
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('seller_settlements', TRUE);
    }
}
