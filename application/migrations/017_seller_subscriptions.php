<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_seller_subscriptions extends CI_Migration
{
    public function up()
    {
        /* creating seller_subscriptions table */
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
            'subscription_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            'start_date' => [
                'type' => 'DATETIME',
                'NULL' => FALSE,
            ],
            'end_date' => [
                'type' => 'DATETIME',
                'NULL' => TRUE,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => '1',
                'NULL'       => FALSE,
                'default'    => '1',
            ],
            'created_at TIMESTAMP default CURRENT_TIMESTAMP',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('seller_id');
        $this->dbforge->add_key('subscription_id');
        $this->dbforge->create_table('seller_subscriptions', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('seller_subscriptions', TRUE);
    }
}

