<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clears rows left behind by product deletes that ran before
 * Product_model::delete_product_cascade() existed.
 *
 * The old delete removed only products, product_variants and product_attributes, so every
 * deleted product left its cart lines, favourites, FAQs and ratings pointing at rows that
 * no longer exist. The damaging one is `cart`: a shopper keeps a line in their basket whose
 * variant is gone, and the joins that price it return nothing.
 *
 * Each statement deletes ONLY rows whose parent has already gone - nothing belonging to a
 * product that still exists is touched.
 *
 * `order_items` and `return_requests` are intentionally excluded: they are financial
 * history, and order_items denormalises product_name/variant_name/price so past orders
 * still render correctly without the product row.
 */
class Migration_clean_orphaned_product_rows extends CI_Migration
{
    public function up()
    {
        // Cart lines whose variant no longer exists.
        $this->db->query(
            'DELETE c FROM cart c
             LEFT JOIN product_variants v ON v.id = c.product_variant_id
             WHERE v.id IS NULL'
        );

        // Variants whose product no longer exists.
        $this->db->query(
            'DELETE v FROM product_variants v
             LEFT JOIN products p ON p.id = v.product_id
             WHERE p.id IS NULL'
        );

        foreach (['favorites', 'product_faqs', 'product_rating', 'product_attributes'] as $table) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            $this->db->query(
                'DELETE t FROM `' . $table . '` t
                 LEFT JOIN products p ON p.id = t.product_id
                 WHERE p.id IS NULL'
            );
        }
    }

    public function down()
    {
        // Not reversible - the deleted rows referenced products that no longer exist, so
        // there is nothing coherent to restore them to.
    }
}
