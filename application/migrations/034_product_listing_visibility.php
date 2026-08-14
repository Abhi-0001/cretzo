<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Storefront visibility of a product, independent of its approval status.
 *
 * A subscription's listings_limit only ever gated *adding* products: a seller who listed
 * 5,000 products on an unlimited plan and then dropped to a 100-listing plan kept all
 * 5,000 of them live in the shop. The cap now applies to what buyers can see, and this
 * column is what carries it.
 *
 * Deliberately separate from products.status, which means something else entirely
 * (1 approved / 2 awaiting admin approval / 0 deactivated by admin) and is set by people,
 * not by the plan. A product can be approved and still be over its seller's plan cap, and
 * both facts have to survive independently - overloading status would lose one of them
 * every time the other changed.
 *
 * Values:
 *   1 - visible in the shop (default)
 *   2 - hidden because the seller's plan has no slot for it; restored automatically when
 *       a slot frees up (a bigger plan, or another product taking its place)
 *   0 - hidden because the seller (or admin) chose to; NEVER restored automatically
 */
class Migration_product_listing_visibility extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('listing_visibility', 'products')) {
            $this->dbforge->add_column('products', [
                'listing_visibility' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => false,
                    'default'    => 1,
                    'comment'    => '1=visible, 2=hidden by plan limit, 0=hidden by seller/admin',
                    'after'      => 'status',
                ],
            ]);
        }

        // Every storefront query filters on this alongside status, so it needs to be
        // indexed with it rather than scanned.
        $index_exists = $this->db->query(
            "SHOW INDEX FROM `products` WHERE Key_name = 'idx_products_status_visibility'"
        )->num_rows();

        if (!$index_exists) {
            $this->db->query('ALTER TABLE `products` ADD INDEX `idx_products_status_visibility` (`status`, `listing_visibility`)');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('listing_visibility', 'products')) {
            $this->dbforge->drop_column('products', 'listing_visibility');
        }
    }
}
