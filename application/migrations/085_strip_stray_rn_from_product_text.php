<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Removes the stray "rn" lines from product description / extra_description text.
 *
 * A literal "\r\n" pasted into the editor lost its backslashes on the way through and came out
 * as the letters "rn", one per line, between every paragraph of the storefront description.
 * New saves are cleaned by Product_model; this repairs rows already stored. Idempotent - a row
 * without a stray "rn" is not written.
 */
class Migration_strip_stray_rn_from_product_text extends CI_Migration
{
    public function up()
    {
        $this->load->helper('function_helper');
        foreach (['description', 'extra_description'] as $field) {
            $rows = $this->db->query("SELECT id, `$field` AS v FROM products WHERE `$field` LIKE '%rn%'")->result_array();
            foreach ($rows as $row) {
                $clean = strip_stray_rn_lines($row['v']);
                if ($clean !== $row['v']) {
                    $this->db->where('id', $row['id'])->update('products', [$field => $clean]);
                }
            }
        }
    }

    public function down()
    {
        // Irreversible data repair.
    }
}
