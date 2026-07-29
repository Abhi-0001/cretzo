<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Seller_subscription_model extends CI_Model
{
    /** Plan auto-granted to the first N vendors who register. */
    const LAUNCH_OFFER_PLAN_NAME = 'Launch Offer';

    /** Number of vendors eligible for the launch promotion. */
    const LAUNCH_OFFER_SELLER_CAP = 100;

    /** Listing allowance granted by the launch promotion. */
    const LAUNCH_OFFER_LISTINGS = 50;

    /** Validity of the launch promotion, in days (1 year). */
    const LAUNCH_OFFER_VALIDITY_DAYS = 365;

    public function get_active_subscription($seller_id)
    {
        if (empty($seller_id)) {
            return null;
        }

        $this->db->where('seller_id', $seller_id);
        $this->db->where('is_active', 1);
        $this->db->group_start();
        // end_date is a DATE column, not a datetime — comparing it against a full
        // timestamp made subscriptions expire up to a day early any time after midnight
        // on their actual expiry day.
        $this->db->where('end_date >=', date('Y-m-d'));
        $this->db->or_where('end_date IS NULL', null, false);
        $this->db->group_end();
        $this->db->order_by('start_date', 'DESC');
        $query = $this->db->get('seller_subscriptions');

        return $query->row_array();
    }

    public function get_latest_subscription($seller_id)
    {
        if (empty($seller_id)) {
            return null;
        }

        $this->db->where('seller_id', $seller_id);
        $this->db->order_by('start_date', 'DESC');
        $query = $this->db->get('seller_subscriptions');

        return $query->row_array();
    }

    public function assign_subscription($seller_id, $subscription_id, $validity = null)
    {
        if (empty($seller_id) || empty($subscription_id)) {
            return false;
        }

        // mark existing subscriptions as inactive
        $this->db->set('is_active', 0)->where('seller_id', $seller_id)->update('seller_subscriptions');

        $start = date('Y-m-d H:i:s');
        $end   = null;

        // basic validity handling: treat numeric value as days, anything else as unlimited
        if (!empty($validity) && ctype_digit((string) $validity)) {
            $end = date('Y-m-d H:i:s', strtotime('+' . (int) $validity . ' days', strtotime($start)));
        }

        $data = [
            'seller_id'       => $seller_id,
            'subscription_id' => $subscription_id,
            'start_date'      => $start,
            'end_date'        => $end,
            'is_active'       => 1,
        ];

        return $this->db->insert('seller_subscriptions', $data);
    }

    /**
     * Auto-assign a subscription when a vendor registers.
     * The first LAUNCH_OFFER_SELLER_CAP vendors receive the "Launch Offer"
     * plan (50 free listings, valid 1 year). Everyone after is put on the admin's
     * default free plan (the lowest-priced plan configured in the admin panel,
     * e.g. "Basic") — its listing limit / validity come from the admin panel,
     * they are NOT hardcoded here.
     *
     * @param int $seller_id  The newly registered seller's user id.
     * @return bool           True when a subscription was assigned.
     */
    public function assign_registration_offer($seller_id)
    {
        if (empty($seller_id)) {
            return false;
        }

        // Never overwrite an existing subscription (e.g. duplicate calls).
        if (!empty($this->get_latest_subscription($seller_id))) {
            return false;
        }

        $launch_plan = $this->ensure_launch_offer_plan();

        if (!empty($launch_plan['id']) && $this->count_plan_holders($launch_plan['id']) < self::LAUNCH_OFFER_SELLER_CAP) {
            // Within the first 100 vendors -> grant the launch promotion.
            return $this->assign_subscription($seller_id, $launch_plan['id'], $launch_plan['validity']);
        }

        // Everyone after -> the admin's default free plan (values from admin panel).
        $free_plan = $this->get_default_signup_plan();
        if (empty($free_plan['id'])) {
            return false;
        }

        return $this->assign_subscription($seller_id, $free_plan['id'], $free_plan['validity']);
    }

    /**
     * Whether the launch promotion is still available, i.e. fewer than
     * LAUNCH_OFFER_SELLER_CAP vendors have been granted it so far.
     * Used to hide the "Launch Offer" banner once the cap is reached.
     */
    public function is_launch_offer_active()
    {
        $launch_plan = $this->ensure_launch_offer_plan();

        if (empty($launch_plan['id'])) {
            return false;
        }

        return $this->count_plan_holders($launch_plan['id']) < self::LAUNCH_OFFER_SELLER_CAP;
    }

    /**
     * Count the distinct vendors who have ever been granted a given plan.
     */
    public function count_plan_holders($subscription_id)
    {
        if (empty($subscription_id)) {
            return 0;
        }

        $row = $this->db
            ->query('SELECT COUNT(DISTINCT seller_id) AS cnt FROM seller_subscriptions WHERE subscription_id = ?', [$subscription_id])
            ->row_array();

        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

    /**
     * Fetch the "Launch Offer" plan, creating it on demand if it doesn't exist.
     */
    public function ensure_launch_offer_plan()
    {
        $plan = $this->get_plan_by_name(self::LAUNCH_OFFER_PLAN_NAME);
        if (!empty($plan)) {
            return $plan;
        }

        $data = [
            'name'           => self::LAUNCH_OFFER_PLAN_NAME,
            'price'          => '0',
            'listings_limit' => (string) self::LAUNCH_OFFER_LISTINGS,
            'validity'       => (string) self::LAUNCH_OFFER_VALIDITY_DAYS,
        ];

        if ($this->db->field_exists('features', 'subscriptions')) {
            $data['features'] = json_encode([
                ['id' => 'launch_free_listings', 'name' => '50 Free Listings', 'description' => 'List up to 50 products free for 1 year'],
                ['id' => 'launch_first_100', 'name' => 'First 100 Vendors', 'description' => 'Exclusive to the first 100 vendors who join'],
            ]);
        }

        $this->db->insert('subscriptions', $data);
        $data['id'] = $this->db->insert_id();

        return $data;
    }

    /**
     * The admin's default free plan for normal sign-ups: the lowest-priced plan
     * configured in the admin panel (e.g. "Basic"), excluding the Launch Offer
     * promo. Its listing limit / validity are whatever the admin set — nothing is
     * hardcoded here. Returns null only when there are no (non-promo) plans at all.
     */
    public function get_default_signup_plan()
    {
        $plans = $this->db->order_by('id', 'ASC')->get('subscriptions')->result_array();

        $best = null;
        $best_price = null;
        foreach ($plans as $plan) {
            // Never auto-assign the promo plan as the ordinary sign-up plan.
            if (isset($plan['name']) && strcasecmp(trim($plan['name']), self::LAUNCH_OFFER_PLAN_NAME) === 0) {
                continue;
            }
            $price = $this->price_to_number(isset($plan['price']) ? $plan['price'] : 0);
            if ($best === null || $price < $best_price) {
                $best = $plan;
                $best_price = $price;
            }
        }

        return $best;
    }

    /**
     * Normalise a free-text price ("₹399", "399", "", "Free") to a number.
     */
    private function price_to_number($raw)
    {
        $clean = preg_replace('/[^\d.]/', '', (string) $raw);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * Look up a subscription plan by its exact name.
     */
    private function get_plan_by_name($name)
    {
        return $this->db->where('name', $name)->get('subscriptions')->row_array();
    }

    /**
     * Return the seller's current active (or, failing that, latest) plan row,
     * or null when the seller has no subscription at all.
     */
    public function get_current_plan($seller_id)
    {
        if (empty($seller_id)) {
            return null;
        }

        $sub = $this->get_active_subscription($seller_id);
        if (empty($sub)) {
            $sub = $this->get_latest_subscription($seller_id);
        }
        if (empty($sub) || empty($sub['subscription_id'])) {
            return null;
        }

        return $this->db->where('id', $sub['subscription_id'])->get('subscriptions')->row_array();
    }

    /**
     * Resolve the seller's listing cap from their current plan's listings_limit.
     * The column is free text ("50", "Unlimited", "100 extra listings", blank...),
     * so we normalise it: blank / "unlimited" / no digits => no cap (null);
     * otherwise the first integer found is the cap.
     *
     * @return int|null  Integer cap, or null when unlimited / no plan applies.
     */
    public function get_listing_limit($seller_id)
    {
        $plan = $this->get_current_plan($seller_id);
        if (empty($plan) || !isset($plan['listings_limit'])) {
            return null; // no plan / no limit configured -> do not block
        }

        return $this->parse_listing_limit($plan['listings_limit']);
    }

    private function parse_listing_limit($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null; // blank -> unlimited
        }
        if (stripos($raw, 'unlimited') !== false) {
            return null; // "Unlimited" -> unlimited
        }
        if (preg_match('/\d+/', $raw, $m)) {
            return (int) $m[0]; // first number is the cap
        }
        return null; // unrecognised text -> do not block
    }

    /**
     * How many listings (products) the seller currently owns.
     */
    public function get_listing_usage($seller_id)
    {
        if (empty($seller_id)) {
            return 0;
        }

        return (int) $this->db->where('seller_id', $seller_id)->count_all_results('products');
    }

    /**
     * Check whether the seller may add $adding new listing(s) under their plan.
     *
     * @return array {
     *   allowed:   bool,        // false when the new total would exceed the cap
     *   limit:     int|null,    // the cap, or null when unlimited
     *   used:      int,         // current listing count
     *   remaining: int|null,    // slots left, or null when unlimited
     *   plan_name: string       // current plan name (for messaging)
     * }
     */
    public function check_listing_quota($seller_id, $adding = 1)
    {
        $plan  = $this->get_current_plan($seller_id);
        $limit = $this->parse_listing_limit(isset($plan['listings_limit']) ? $plan['listings_limit'] : '');
        $used  = $this->get_listing_usage($seller_id);
        $plan_name = !empty($plan['name']) ? $plan['name'] : '';

        if ($limit === null) {
            return ['allowed' => true, 'limit' => null, 'used' => $used, 'remaining' => null, 'plan_name' => $plan_name];
        }

        $remaining = $limit - $used;
        if ($remaining < 0) {
            $remaining = 0;
        }

        return [
            'allowed'   => ($used + (int) $adding) <= $limit,
            'limit'     => $limit,
            'used'      => $used,
            'remaining' => $remaining,
            'plan_name' => $plan_name,
        ];
    }
}

