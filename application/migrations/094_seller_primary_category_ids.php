<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * A seller may now sell under several top-level categories. `primary_category_ids` holds all of
 * them (comma-separated); `primary_category_id` keeps the first one so everything that reads a
 * single primary category (commission defaults, dashboard) keeps working.
 */
class Migration_seller_primary_category_ids extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('primary_category_ids', 'seller_data')) {
            $this->dbforge->add_column('seller_data', [
                'primary_category_ids' => ['type' => 'TEXT', 'null' => TRUE, 'after' => 'primary_category_id'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('primary_category_ids', 'seller_data')) {
            $this->dbforge->drop_column('seller_data', 'primary_category_ids');
        }
    }
}
