<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller store-details profile update.
 * Adds a single "primary product category" column. `category_ids` (existing,
 * comma-separated) continues to hold the seller's secondary/additional
 * categories — primary is kept as its own column instead of relying on
 * list-order within that string, which isn't guaranteed anywhere it's written.
 */
class Migration_seller_primary_category extends CI_Migration
{
    public function up()
    {
        $fields = array(
            'primary_category_id' => array(
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
                'default'    => NULL,
                'after'      => 'category_ids'
            ),
        );
        $this->dbforge->add_column('seller_data', $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column('seller_data', 'primary_category_id');
    }
}
