<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Publish/unpublish flag for homepage featured sections.
 *
 * `sections` was the only piece of storefront content with no visibility flag at all: creating a
 * featured section published it to the homepage immediately, and the only way to take it back
 * down was to delete it - losing its title, style, category list and hand-picked product list in
 * the process. Every other content table (products, categories, brands, blogs, blog_categories,
 * sliders' targets, ...) already has a status the admin can toggle.
 *
 * Defaults to 1 so every existing section stays exactly as visible as it is today.
 *
 * Values:
 *   1 - shown on the homepage (default)
 *   0 - hidden; the section and everything configured on it is preserved
 */
class Migration_featured_section_status extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('status', 'sections')) {
            $this->dbforge->add_column('sections', [
                'status' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => false,
                    'default'    => 1,
                    'comment'    => '1=shown on homepage, 0=hidden',
                    'after'      => 'style',
                ],
            ]);
        }

        // The homepage reads these ordered by row_order and filtered on status on every request.
        $index_exists = $this->db->query(
            "SHOW INDEX FROM `sections` WHERE Key_name = 'idx_sections_status_row_order'"
        )->num_rows();

        if (!$index_exists) {
            $this->db->query('ALTER TABLE `sections` ADD INDEX `idx_sections_status_row_order` (`status`, `row_order`)');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('status', 'sections')) {
            $this->dbforge->drop_column('sections', 'status');
        }
    }
}
