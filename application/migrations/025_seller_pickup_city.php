<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * The seller profile form's "Pickup City" field (pickup_city) has never had a
 * matching seller_data column — every other pickup_* field (address1/address2/
 * district/state/pin) does. The value was silently dropped on save and the
 * field always reloaded blank.
 */
class Migration_seller_pickup_city extends CI_Migration
{
    public function up()
    {
        $fields = array(
            'pickup_city' => array(
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'NULL'       => TRUE,
                'default'    => NULL,
                'after'      => 'pickup_pin'
            ),
        );
        $this->dbforge->add_column('seller_data', $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column('seller_data', 'pickup_city');
    }
}
