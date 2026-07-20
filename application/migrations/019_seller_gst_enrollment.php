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
        $this->dbforge->add_column('seller_data', $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column('seller_data', 'is_gst_registered');
        $this->dbforge->drop_column('seller_data', 'gst_enrollment_number');
    }
}
