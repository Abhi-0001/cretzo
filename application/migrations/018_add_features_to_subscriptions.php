<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_features_to_subscriptions extends CI_Migration
{
    public function up()
    {
        /* Add features column to subscriptions table */
        // Skip if it's already there - see the note in 019; a replay would otherwise fail
        // with "Duplicate column name 'features'" and block every later migration.
        if ($this->db->field_exists('features', 'subscriptions')) {
            return;
        }
        $this->dbforge->add_column('subscriptions', [
            'features' => [
                'type'       => 'LONGTEXT',
                'NULL'       => TRUE,
                'DEFAULT'    => '[]',
                'after'      => 'commission_after100'
            ]
        ]);
    }

    public function down()
    {
        /* Drop features column */
        if ($this->db->field_exists('features', 'subscriptions')) {
            $this->dbforge->drop_column('subscriptions', 'features');
        }
    }
}
