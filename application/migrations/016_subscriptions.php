<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_subscriptions extends CI_Migration
{
    public function up()
    {
        /* creating subscriptions table */
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => '11',
                'auto_increment' => TRUE,
                'NULL'           => FALSE,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '256',
                'NULL'       => TRUE,
            ],
            'price' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'NULL'       => TRUE,
            ],
            'listings_limit' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'NULL'       => TRUE,
            ],
            'validity' => [
                'type'       => 'VARCHAR',
                'constraint' => '128',
                'NULL'       => TRUE,
            ],
            'commission_first50' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'NULL'       => TRUE,
            ],
            'commission_51_100' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'NULL'       => TRUE,
            ],
            'commission_after100' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'NULL'       => TRUE,
            ],
            'date_added TIMESTAMP default CURRENT_TIMESTAMP',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('subscriptions');
    }

    public function down()
    {
        $this->dbforge->drop_table('subscriptions');
    }
}
