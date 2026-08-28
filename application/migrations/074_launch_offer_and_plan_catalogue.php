<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Aligns the seller plan catalogue with the published launch pricing (2026-08-29).
 *
 * The launch promotion shrank from "first 100 vendors / 50 listings" to "first 20 vendors /
 * 30 listings" (the constants in Seller_subscription_model moved with it), and the paid
 * catalogue was renamed to what the marketing page advertises:
 *
 *   Free Seller   Rs 0    10 listings        ongoing (blank validity = lifetime)
 *   Launch Offer  Rs 0    30 listings        365 days, first 20 vendors only
 *   100 Listings  Rs 399  100 listings       365 days
 *   Unlimited     Rs 999  Unlimited          365 days
 *
 * Rows are updated in place by id so existing `seller_subscriptions` keep pointing at a valid
 * plan. Migration 020 seeds the Launch Offer row only when it is missing, so the promo row is
 * corrected here too. Plans stay admin-editable afterwards - this only sets the starting values.
 */
class Migration_launch_offer_and_plan_catalogue extends CI_Migration
{
    public function up()
    {
        $plans = [
            1 => ['name' => 'Free Seller',  'price' => '0',   'listings_limit' => '10',        'validity' => ''],
            2 => ['name' => '100 Listings', 'price' => '399', 'listings_limit' => '100',       'validity' => '365'],
            3 => ['name' => 'Unlimited',    'price' => '999', 'listings_limit' => 'Unlimited', 'validity' => '365'],
        ];

        foreach ($plans as $id => $plan) {
            if ($this->db->where('id', $id)->count_all_results('subscriptions')) {
                $this->db->where('id', $id)->update('subscriptions', $plan);
            }
        }

        // The promo row is matched by name, the way the model resolves it.
        $this->db->where('name', 'Launch Offer')
            ->update('subscriptions', ['price' => '0', 'listings_limit' => '30', 'validity' => '365']);
    }

    public function down()
    {
        // Pricing is admin-managed; there is no meaningful earlier state to restore.
    }
}
