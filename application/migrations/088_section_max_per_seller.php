<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Featured sections: "Max products per seller".
 *
 * The "New Arrivals" sections list the newest products site-wide, so a seller who uploads or
 * edits ten products in one sitting filled the whole grid on their own. `max_per_seller` caps
 * how many of a seller's products one section may show (0 = no cap). It is set per section in
 * Admin > Featured Sections and only applies to the "New Added Products" type.
 *
 * Existing "New Added Products" sections start at 2; other sections stay at 0.
 */
class Migration_section_max_per_seller extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('max_per_seller', 'sections')) {
            $this->dbforge->add_column('sections', [
                'max_per_seller' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => TRUE, 'NULL' => FALSE, 'default' => 0],
            ]);
            $this->db->where('product_type', 'new_added_products')->update('sections', ['max_per_seller' => 2]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('max_per_seller', 'sections')) {
            $this->dbforge->drop_column('sections', 'max_per_seller');
        }
    }
}
