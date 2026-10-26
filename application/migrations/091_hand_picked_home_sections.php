<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Home page sections are hand-picked.
 *
 * Trending Deals, Best Seller and the New Arrivals sections used to rank products
 * automatically (most selling / newest). They now show only the products an admin picks in
 * Admin > Featured Sections, so every section is converted to the "custom_products" type.
 *
 * To keep the live home page from going blank on deploy, each converted section is seeded with
 * the products it would have shown - the same ranking, the same per-seller cap, the same
 * category restriction - and the admin can swap any of them from then on. Sections that were
 * already hand-picked, and digital sections, are left alone, so this is safe to re-run.
 *
 * `down()` cannot restore the old automatic types (the original type is not kept), so it is a no-op.
 */
class Migration_hand_picked_home_sections extends CI_Migration
{
    public function up()
    {
        $sections = $this->db->where_not_in('product_type', ['custom_products', 'digital_product'])->get('sections')->result_array();

        foreach ($sections as $section) {
            // Same slot counts the home page uses: 6 for Trending Deals / Special Picks, 12 otherwise.
            $slots = in_array($section['style'], ['cretzo_trending', 'cretzo_special_picks'], true) ? 6 : 12;
            $cap = isset($section['max_per_seller']) ? (int) $section['max_per_seller'] : 0;

            $this->db->reset_query();
            $this->db->select('p.id, p.seller_id')
                ->from('products p')
                ->join('seller_data sd', 'sd.user_id = p.seller_id AND sd.status = 1')
                // A product the storefront cannot draw (no variant, or a category that no longer
                // exists) would take up a slot and show nothing.
                ->join('categories c', 'c.id = p.category_id AND c.status IN (0, 1)')
                ->where('EXISTS (SELECT 1 FROM product_variants pvx WHERE pvx.product_id = p.id)', null, false)
                ->where('p.status', 1)
                ->where('p.listing_visibility', 1);

            if (!empty($section['categories'])) {
                $category_ids = [];
                foreach (explode(',', $section['categories']) as $cid) {
                    $cid = (int) $cid;
                    if ($cid > 0) {
                        $category_ids = array_merge($category_ids, function_exists('category_descendant_ids') ? (array) category_descendant_ids($cid) : [$cid]);
                    }
                }
                if (!empty($category_ids)) {
                    $this->db->where_in('p.category_id', array_unique($category_ids));
                }
            }

            switch ($section['product_type']) {
                case 'most_selling_products':
                    $this->db->join('product_variants pv', 'pv.product_id = p.id', 'left')
                        ->join('order_items oi', 'oi.product_variant_id = pv.id', 'left')
                        ->group_by('p.id')
                        ->order_by('COUNT(oi.id)', 'DESC', false)
                        ->order_by('p.id', 'DESC');
                    break;
                case 'top_rated_products':
                    $this->db->where('p.no_of_ratings >', 0)->order_by('p.rating', 'DESC')->order_by('p.no_of_ratings', 'DESC');
                    break;
                case 'products_on_sale':
                    $this->db->where('EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.special_price > 0 AND pv.special_price < pv.price)', null, false)
                        ->order_by('p.id', 'DESC');
                    break;
                default: // new_added_products
                    $this->db->order_by('p.id', 'DESC');
            }

            $ids = [];
            $per_seller = [];
            foreach ($this->db->limit(500)->get()->result_array() as $row) {
                $seller = $row['seller_id'];
                $per_seller[$seller] = isset($per_seller[$seller]) ? $per_seller[$seller] + 1 : 1;
                if ($cap > 0 && $per_seller[$seller] > $cap) {
                    continue;
                }
                $ids[] = (int) $row['id'];
                if (count($ids) >= $slots) {
                    break;
                }
            }

            $update = [
                'product_type' => 'custom_products',
                'product_ids' => !empty($ids) ? implode(',', $ids) : null,
            ];
            if ($this->db->field_exists('max_per_seller', 'sections')) {
                $update['max_per_seller'] = 0;
            }
            $this->db->where('id', $section['id'])->update('sections', $update);
        }

        if (function_exists('app_cache_delete')) {
            app_cache_delete('home_sections.guest');
        }
    }

    public function down()
    {
        // The automatic type each section had is not recorded, so there is nothing to restore.
    }
}
