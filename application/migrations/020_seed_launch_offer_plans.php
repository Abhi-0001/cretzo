<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seeds the "Launch Offer" promo plan used by the launch promotion:
 *  - "Launch Offer": auto-granted to the first 100 vendors who register
 *    (50 free listings, valid for 1 year / 365 days).
 *
 * This is the only plan seeded here. The ordinary catalogue plans (Basic /
 * Standard / Premium etc.) are created and edited by the admin in the admin
 * panel, and normal sign-ups are placed on the admin's lowest-priced free plan
 * automatically — nothing about those plans is hardcoded.
 *
 * Idempotent: the plan is only inserted when one with the same name does not
 * exist. The registration flow (Seller_subscription_model::assign_registration_offer)
 * also creates it on demand, so running this migration is optional; it just keeps
 * the promo visible on the seller subscription page from day one.
 */
class Migration_seed_launch_offer_plans extends CI_Migration
{
    public function up()
    {
        $has_features = $this->db->field_exists('features', 'subscriptions');

        $plan = [
            'name'           => 'Launch Offer',
            'price'          => '0',
            'listings_limit' => '50',
            'validity'       => '365',
            'features'       => json_encode([
                ['id' => 'launch_free_listings', 'name' => '50 Free Listings', 'description' => 'List up to 50 products free for 1 year'],
                ['id' => 'launch_first_100', 'name' => 'First 100 Vendors', 'description' => 'Exclusive to the first 100 vendors who join'],
            ]),
        ];

        $exists = $this->db->where('name', $plan['name'])->get('subscriptions')->row_array();
        if (empty($exists)) {
            if (!$has_features) {
                unset($plan['features']);
            }
            $this->db->insert('subscriptions', $plan);
        }
    }

    public function down()
    {
        // Only remove the promo plan if no seller is subscribed to it.
        $plan = $this->db->where('name', 'Launch Offer')->get('subscriptions')->row_array();
        if (empty($plan)) {
            return;
        }
        $in_use = $this->db->where('subscription_id', $plan['id'])->count_all_results('seller_subscriptions');
        if ($in_use == 0) {
            $this->db->where('id', $plan['id'])->delete('subscriptions');
        }
    }
}
