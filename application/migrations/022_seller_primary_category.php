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
        $this->dbforge->drop_column('seller_data', 'primary_category_id');
    }
}
