<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Puts products whose category no longer exists back on the storefront.
 *
 * The storefront listing (fetch_product()) joins products to categories and keeps a row only when
 * the category still exists. 177 live products point at category ids in the 201-620 range while
 * the categories table only goes up to 81, so every one of them was silently dropped from the
 * shop - not hidden, not flagged, simply absent. Combined with the seller's plan listing cap this
 * left the storefront showing NOTHING: products/ajax_get_products returned {"total":0} against a
 * catalogue of 290 live products, and every product detail page redirected back to the listing.
 *
 * Those category ids carry no recoverable meaning. Each one holds only one or two products, and
 * where it holds two they are duplicates of the same item - so it was never a grouping, just a
 * per-row number that came in with an import whose categories were never loaded. There is nothing
 * to map them back to.
 *
 * So this does the one thing that is certainly correct: it stops the broken reference from hiding
 * real products, by pointing them at a real "Uncategorised" category. It deliberately does NOT
 * guess which of the nine real categories each product belongs to - that is a merchandising
 * decision for whoever runs the shop, and a wrong guess is worse than an obviously-unsorted
 * bucket. The category is created active and visible precisely so the products surface and the
 * gap is easy to see and work through in the admin.
 *
 * Products remain subject to every other visibility rule, notably the seller's plan listing cap -
 * this does not smuggle anything past that. On this database it makes 99 products visible, which
 * is what the seller's Standard (100 listing) plan already permits.
 *
 * Idempotent: it only touches rows whose category_id currently resolves to nothing, so re-running
 * it is a no-op, and anything recategorised by hand afterwards is left alone.
 */
class Migration_repair_orphaned_product_categories extends CI_Migration
{
    const CATEGORY_NAME = 'Uncategorised';
    const CATEGORY_SLUG = 'uncategorised';

    public function up()
    {
        if (!$this->db->table_exists('products') || !$this->db->table_exists('categories')) {
            return;
        }

        $orphans = (int) $this->db->query(
            "SELECT COUNT(*) AS total
               FROM products p
              WHERE p.category_id IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.id = p.category_id)"
        )->row()->total;

        if ($orphans === 0) {
            return; // nothing orphaned - re-run, or a database that never had the problem
        }

        $category_id = $this->ensure_category();
        if (empty($category_id)) {
            log_message('error', 'Migration 056: could not create the Uncategorised category; orphaned products left as they were.');
            return;
        }

        $this->db->query(
            "UPDATE products p
                SET p.category_id = ?
              WHERE p.category_id IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.id = p.category_id)",
            [$category_id]
        );

        log_message('error', 'Migration 056: repointed ' . $orphans
            . ' product(s) with a missing category to "' . self::CATEGORY_NAME . '" (id ' . $category_id . ').');
    }

    public function down()
    {
        // The original category ids referred to categories that never existed in this database,
        // so there is nothing to restore them to - putting them back would only re-hide the
        // products. The Uncategorised category is left in place; it is a normal category and can
        // be removed from the admin once its products have been sorted.
    }

    /**
     * The Uncategorised category, created if it is not already there.
     *
     * Written column by column against what the table actually has, because `categories` differs
     * between installs of this project (row_order, image and clicks are present here but are not
     * guaranteed) and a migration that assumes a column will abort the whole run.
     */
    private function ensure_category()
    {
        $existing = $this->db->select('id')
            ->where('slug', self::CATEGORY_SLUG)
            ->get('categories')->row_array();
        if (!empty($existing['id'])) {
            return (int) $existing['id'];
        }

        $row = [
            'name'      => self::CATEGORY_NAME,
            'slug'      => self::CATEGORY_SLUG,
            'parent_id' => 0,
            'status'    => 1,
        ];
        foreach (['image' => '', 'banner' => '', 'row_order' => 999, 'clicks' => 0] as $column => $value) {
            if ($this->db->field_exists($column, 'categories')) {
                $row[$column] = $value;
            }
        }

        $this->db->insert('categories', $row);
        $id = (int) $this->db->insert_id();

        return $id > 0 ? $id : null;
    }
}
