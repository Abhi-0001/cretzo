<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller personal-details profile update.
 * Adds an optional middle name column so the seller profile form can
 * capture First / Middle / Last name separately.
 */
class Migration_seller_personal_details extends CI_Migration
{
    public function up()
    {
        $fields = array(
            'middle_name' => array(
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'NULL'       => TRUE,
                'default'    => NULL,
                'after'      => 'first_name'
            ),
        );
        $this->dbforge->add_column('seller_data', $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column('seller_data', 'middle_name');
    }
}
