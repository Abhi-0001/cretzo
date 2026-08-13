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
                // Only add columns that aren't already present. add_column() emits a bare
        // ALTER TABLE ... ADD, which fails with "Duplicate column name" if this migration
        // is replayed on a database where the column already exists (e.g. a restored dump,
        // or a `migrations` version that lags the real schema). One failure aborts the
        // whole run, so every later migration is blocked too.
        $fields = array_filter($fields, function ($name) {
            return !$this->db->field_exists($name, 'seller_data');
        }, ARRAY_FILTER_USE_KEY);
        if (empty($fields)) {
            return;
        }

        $this->dbforge->add_column('seller_data', $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column('seller_data', 'pickup_city');
    }
}
