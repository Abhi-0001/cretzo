<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * GST-based seller restriction.
 * Adds columns to track sellers who register with a GST Enrollment Number
 * (instead of a full GST number). Such sellers are restricted to selling
 * within their own state.
 */
class Migration_seller_gst_enrollment extends CI_Migration
{
    public function up()
    {
        $fields = array(
            // 1 = full GST registered (default, unrestricted).
            // 0 = registered via GST Enrollment Number -> state-restricted.
            'is_gst_registered' => array(
                'type'       => 'TINYINT',
                'constraint' => '1',
                'NULL'       => FALSE,
                'default'    => 1,
                'after'      => 'gst'
            ),
            'gst_enrollment_number' => array(
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'NULL'       => TRUE,
                'default'    => NULL,
                'after'      => 'is_gst_registered'
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
        $this->dbforge->drop_column('seller_data', 'is_gst_registered');
        $this->dbforge->drop_column('seller_data', 'gst_enrollment_number');
    }
}
