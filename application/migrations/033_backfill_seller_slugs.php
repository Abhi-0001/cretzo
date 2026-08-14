<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gives every seller profile a storefront slug.
 *
 * seller_data.slug is what every storefront link is built from - the "View Seller
 * Profile" link on a product page, the seller cards on /sellers, and the
 * "products?seller=" listings. Only the seller/Login::update_user() save path ever
 * filled it in, so every seller created by admin (admin/Sellers::add_seller) or by
 * self-service sign-up (seller/Auth::ajax_signup) kept slug = NULL, and every one of
 * those links rendered as a bare ".../sellers/seller_details/" which
 * Sellers::seller_details() could only answer by bouncing the visitor to the full
 * seller listing. Confirmed live on cretzo.com: the product page and the seller
 * listing were both emitting slug-less links.
 *
 * Falls back through store_name -> shop_name -> username -> "seller-<user_id>" so a
 * row with no naming at all still gets a working, unique URL. Rows that already have
 * a slug are left untouched.
 */
class Migration_backfill_seller_slugs extends CI_Migration
{
    public function up()
    {
        $rows = $this->db
            ->select('sd.id, sd.user_id, sd.store_name, sd.shop_name, u.username')
            ->from('seller_data sd')
            ->join('users u', 'u.id = sd.user_id', 'left')
            ->group_start()
                ->where('sd.slug IS NULL', null, false)
                ->or_where('sd.slug', '')
            ->group_end()
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $source = '';
            foreach (['store_name', 'shop_name', 'username'] as $field) {
                if (!empty($row[$field]) && trim($row[$field]) !== '') {
                    $source = $row[$field];
                    break;
                }
            }

            // create_unique_slug() strips everything url_title() can't use, so a name
            // made up entirely of punctuation can still reduce to an empty string -
            // hence the second check rather than relying on the source alone.
            $slug = ($source !== '') ? create_unique_slug($source, 'seller_data', 'slug', 'id', $row['id']) : '';
            if ($slug === '') {
                $slug = create_unique_slug('seller-' . $row['user_id'], 'seller_data', 'slug', 'id', $row['id']);
            }

            $this->db->where('id', $row['id'])->update('seller_data', ['slug' => $slug]);
        }
    }

    public function down()
    {
        // Deliberately not reversed: once written, a backfilled slug is
        // indistinguishable from one a seller chose, and blanking it would break the
        // storefront links this migration exists to fix.
    }
}
