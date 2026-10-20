<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Category-wise commission.
 *
 * Commission used to come from the seller's subscription plan, in three slabs keyed on how many
 * orders the seller had completed (subscriptions.commission_first50 / _51_100 / _after100). It
 * now comes from the category the product is listed in:
 *
 *   categories.commission_percent  NULL = "inherit from the parent category". The rate for a
 *                                  product is the first non-NULL value walking up from its
 *                                  category to the root, and config default_commission_percent
 *                                  when the whole chain is unset - see
 *                                  Category_model::resolve_commission_for_category().
 *
 *   order_items.commission_category_id  the category whose rate was locked onto the sale, so a
 *                                  statement can say where the percentage came from even after
 *                                  the admin has edited the category.
 *
 * The plan commission columns are left in place (unread) so this is reversible.
 *
 * Seeding: the owner's rate card is applied to the live top-level categories it matches by name.
 * Only a NULL rate is written, so re-running never overwrites a rate the admin has since edited.
 * Anything unmatched stays NULL and settles at the platform default until it is set in
 * Admin > Categories.
 */
class Migration_category_commission extends CI_Migration
{
    /**
     * Owner's rate card (2026-10-20), keyed by the live category name in lower case.
     * Ranges on the card were resolved to their upper bound: Food 8-10% -> 10, Craft supplies
     * 6-8% -> 8. Both are editable from the admin at any time.
     */
    private $seed = [
        'jewellery and accessories' => 12, // Handmade Jewellery
        'footwear and bags'         => 10, // Bags & Accessories
        'home and living'           => 10, // Home Decor
        'clothing'                  => 10, // Handmade Clothing & Fashion
        'beauty'                    => 10, // Candles, Soaps & Lifestyle Products
        'art and collectibles'      => 8,  // Artwork, Paintings & Illustrations
        'snacks and food'           => 10, // Food, Snacks & Homemade Products (8-10%)
        'craft supplies'            => 8,  // Craft Supplies & Raw Materials (6-8%)
        'special picks'             => 10, // Gifts & Personalised Products
    ];

    public function up()
    {
        if (!$this->db->field_exists('commission_percent', 'categories')) {
            $this->dbforge->add_column('categories', [
                'commission_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'NULL'       => TRUE,
                    'default'    => NULL,
                    'after'      => 'parent_id',
                ],
            ]);
        }

        if (!$this->db->field_exists('commission_category_id', 'order_items')) {
            $this->dbforge->add_column('order_items', [
                'commission_category_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'NULL'       => TRUE,
                    'default'    => NULL,
                    'after'      => 'commission_rate_source',
                ],
            ]);
        }

        $roots = $this->db->query(
            "SELECT id, name FROM categories WHERE (parent_id = 0 OR parent_id IS NULL) AND commission_percent IS NULL"
        )->result_array();
        foreach ($roots as $row) {
            $key = strtolower(trim(stripslashes($row['name'])));
            if (isset($this->seed[$key])) {
                $this->db->where('id', $row['id'])
                    ->where('commission_percent IS NULL', null, false)
                    ->update('categories', ['commission_percent' => $this->seed[$key]]);
            }
        }
    }

    public function down()
    {
        if ($this->db->field_exists('commission_category_id', 'order_items')) {
            $this->dbforge->drop_column('order_items', 'commission_category_id');
        }
        if ($this->db->field_exists('commission_percent', 'categories')) {
            $this->dbforge->drop_column('categories', 'commission_percent');
        }
    }
}
