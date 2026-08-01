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
        $this->dbforge->add_unique_key('order_item_id');
        $this->dbforge->create_table('seller_settlements', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('seller_settlements', TRUE);
    }
}
