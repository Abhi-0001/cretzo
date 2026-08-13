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
        $this->dbforge->drop_column('seller_data', 'middle_name');
    }
}
