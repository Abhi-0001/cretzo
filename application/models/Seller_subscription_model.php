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
        // start_date is a DATE, so every subscription change made on the same day ties and
        // MySQL is free to return any of them. `id DESC` breaks the tie deterministically
        // on insertion order, i.e. the genuinely most recent row. Without it a seller who
        // switched plans twice in one day could read back either one at random.
        $this->db->order_by('start_date', 'DESC');
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('seller_subscriptions');

        return $query->row_array();
    }

    public function get_latest_subscription($seller_id)
    {
        if (empty($seller_id)) {
            return null;
        }

        $this->db->where('seller_id', $seller_id);
        // Same tie-break as get_active_subscription(), and it matters more here because
        // this method does NOT filter on is_active: for a seller with several same-day
        // rows it was returning an arbitrary superseded one, so the dashboard read the
        // wrong plan and the wrong (usually empty) end date.
        $this->db->order_by('is_active', 'DESC');
        $this->db->order_by('start_date', 'DESC');
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('seller_subscriptions');

        return $query->row_array();
    }

    /**
     * Resolve a plan's `validity` column into a number of days.
     *
     * The admin form labels this field "Validity no of days" and now validates it as
     * numeric, but it is a free-text VARCHAR and the live plans were saved with prose
     * ("1 month", "Valid up to one year"). The previous ctype_digit() test rejected
     * every one of those, so $end stayed NULL and NO paid subscription ever expired -
     * which in turn made renewal, the expiry cron and admin's "extend" action all
     * no-ops. Parsing the common prose forms as well means such rows still behave
     * sensibly instead of silently becoming lifetime plans.
     *
     * @return int|null Days, or NULL for genuinely unlimited / unparseable values.
     */
    public function parse_validity_days($validity)
    {
        $raw = strtolower(trim((string) $validity));
        if ($raw === '') {
            return null;
        }

        // Explicitly unlimited.
        if (preg_match('/unlimited|lifetime|forever|no expiry|never/', $raw)) {
            return null;
        }

        // The documented format: a bare number of days.
        if (ctype_digit($raw)) {
            return ((int) $raw) > 0 ? (int) $raw : null;
        }

        // Prose forms: "1 month", "one year", "12 months", "2 weeks", "monthly"...
        $units = [
            'year'  => 365,
            'annual' => 365,
            'month' => 30,
            'week'  => 7,
            'day'   => 1,
        ];
        foreach ($units as $unit => $days_per_unit) {
            if (strpos($raw, $unit) === false) {
                continue;
            }
            // Leading count, if any: "12 months" -> 12. Word forms map to 1.
            if (preg_match('/(\d+)\s*' . preg_quote($unit, '/') . '/', $raw, $m)) {
                $count = (int) $m[1];
            } elseif (preg_match('/\b(a|an|one)\b/', $raw)) {
                $count = 1;
            } else {
                $count = 1; // "monthly", "valid up to one year" etc.
            }
            return $count > 0 ? $count * $days_per_unit : null;
        }

        // A number with no recognisable time unit (e.g. the "100 extra listings" that had
        // been typed into this field) is deliberately NOT treated as days - guessing would
        // silently invent an expiry date. Left unlimited; admin-side numeric validation
        // now prevents new values like this being saved.
        return null;
    }

    /**
     * Start a new subscription period for a seller on the given plan.
     *
     * @param int   $seller_id
     * @param int   $subscription_id
     * @param mixed $validity       The plan's raw `validity` column.
     * @param bool  $carry_over     When true and the seller is currently on an ACTIVE,
     *                              time-limited subscription for this SAME plan, the days
     *                              still remaining are added on top of the new period
     *                              instead of being thrown away. This is what makes an
     *                              early renewal a renewal rather than a reset - renewing
     *                              a 365-day plan with 200 days left used to silently
     *                              destroy those 200 days.
     */
    public function assign_subscription($seller_id, $subscription_id, $validity = null, $carry_over = false)
    {
        if (empty($seller_id) || empty($subscription_id)) {
            return false;
        }

        // Remaining time on the current period, counted BEFORE it is deactivated below.
        $carry_days = 0;
        if ($carry_over) {
            $current = $this->get_active_subscription($seller_id);
            if (!empty($current)
                && (int) $current['subscription_id'] === (int) $subscription_id
                && !empty($current['end_date'])) {
                $seconds_left = strtotime($current['end_date']) - time();
                if ($seconds_left > 0) {
                    $carry_days = (int) ceil($seconds_left / 86400);
                }
            }
        }

        // mark existing subscriptions as inactive
        $this->db->set('is_active', 0)->where('seller_id', $seller_id)->update('seller_subscriptions');

        $start = date('Y-m-d H:i:s');
        $end   = null;

        $days = $this->parse_validity_days($validity);
        if ($days !== null) {
            $end = date('Y-m-d H:i:s', strtotime('+' . ($days + $carry_days) . ' days', strtotime($start)));
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

        if (!empty($launch_plan['id'])) {
            // count_plan_holders() + assign_subscription() used to be a plain
            // check-then-act with no lock: two concurrent registrations near vendor
            // #100 could both pass the count check and both get granted the offer.
            // FOR UPDATE inside a transaction locks the counted rows so a second,
            // concurrent registration blocks until the first one commits.
            $this->db->trans_start();
            $count_row = $this->db
                ->query(
                    'SELECT COUNT(DISTINCT ss.seller_id) AS cnt
                       FROM seller_subscriptions ss
                       JOIN users_groups ug ON ug.user_id = ss.seller_id AND ug.group_id = 4
                      WHERE ss.subscription_id = ? FOR UPDATE',
                    [$launch_plan['id']]
                )
                ->row_array();
            $count = isset($count_row['cnt']) ? (int) $count_row['cnt'] : 0;

            if ($count < self::LAUNCH_OFFER_SELLER_CAP) {
                // Within the first 100 vendors -> grant the launch promotion.
                $this->assign_subscription($seller_id, $launch_plan['id'], $launch_plan['validity']);
                $this->db->trans_complete();
                return $this->db->trans_status();
            }
            $this->db->trans_complete();
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
     *
     * Joins users_groups so only rows belonging to a seller who still exists are counted.
     * A plain COUNT over seller_subscriptions also counts orphans - rows left behind when
     * a seller is deleted - and for the launch promotion those orphans permanently burn a
     * slot out of the 100 that can never be reclaimed. (There is at least one such row in
     * the live data already.)
     */
    public function count_plan_holders($subscription_id)
    {
        if (empty($subscription_id)) {
            return 0;
        }

        $row = $this->db
            ->query(
                'SELECT COUNT(DISTINCT ss.seller_id) AS cnt
                   FROM seller_subscriptions ss
                   JOIN users_groups ug ON ug.user_id = ss.seller_id AND ug.group_id = 4
                  WHERE ss.subscription_id = ?',
                [$subscription_id]
            )
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
     * Work out what a seller actually owes to switch to $new_plan right now, crediting the
     * unused portion of the plan they are currently paying for.
     *
     * Without this, switching plans mid-cycle charged the new plan's full price and threw
     * away whatever was left on the old one - so a seller who upgraded a month into an
     * annual plan paid twice for that overlap. Credit is strictly a discount on this
     * purchase: it is capped at the new plan's price and never becomes a cash refund.
     *
     * Not applied when:
     *   - the new plan is free (nothing to discount),
     *   - the seller has no active subscription, or it has no end date (an unlimited plan
     *     has no "unused portion" to measure),
     *   - the old plan was free (nothing was paid for the remaining time),
     *   - it is the SAME plan, which is a renewal - that is handled by carrying the unused
     *     days forward in assign_subscription(), and doing both would double-count them.
     *
     * @return array {full_price, credit, payable, days_remaining, from_plan}
     */
    public function calculate_proration($seller_id, $new_plan)
    {
        $full = $this->price_to_number(isset($new_plan['price']) ? $new_plan['price'] : 0);
        $result = [
            'full_price'     => $full,
            'credit'         => 0.0,
            'payable'        => $full,
            'days_remaining' => 0,
            'from_plan'      => '',
        ];

        if ($full <= 0) {
            return $result;
        }

        $current = $this->get_active_subscription($seller_id);
        if (empty($current) || empty($current['end_date'])) {
            return $result;
        }

        if ((int) $current['subscription_id'] === (int) $new_plan['id']) {
            return $result; // renewal, not a switch
        }

        $old_plan = $this->db->where('id', $current['subscription_id'])->get('subscriptions')->row_array();
        if (empty($old_plan)) {
            return $result;
        }

        $old_price = $this->price_to_number($old_plan['price']);
        if ($old_price <= 0) {
            return $result;
        }

        $total_days = $this->parse_validity_days($old_plan['validity']);
        if ($total_days === null || $total_days <= 0) {
            return $result;
        }

        $days_remaining = (int) ceil((strtotime($current['end_date']) - time()) / 86400);
        if ($days_remaining <= 0) {
            return $result;
        }
        // Guard against a hand-edited end_date further out than the plan's own length,
        // which would otherwise generate a credit larger than was ever paid.
        if ($days_remaining > $total_days) {
            $days_remaining = $total_days;
        }

        $credit = round($old_price * ($days_remaining / $total_days), 2);
        if ($credit > $full) {
            $credit = $full; // discount only, never a refund
        }

        $result['credit']         = $credit;
        $result['payable']        = round($full - $credit, 2);
        $result['days_remaining'] = $days_remaining;
        $result['from_plan']      = isset($old_plan['name']) ? $old_plan['name'] : '';

        return $result;
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
     * Admin action: deactivate the seller's current active subscription (if any).
     * Leaves the row in place (history preserved), just flips is_active to 0.
     */
    public function deactivate_subscription($seller_id)
    {
        if (empty($seller_id)) {
            return false;
        }

        $this->db->set('is_active', 0)->where('seller_id', $seller_id)->where('is_active', 1)->update('seller_subscriptions');

        // update() returns TRUE whenever the QUERY succeeded, including when it matched no
        // rows - so cancelling a seller who had nothing active reported "Subscription
        // cancelled successfully" and told admin they had just done something they hadn't.
        // affected_rows() is what actually distinguishes the two.
        return $this->db->affected_rows() > 0;
    }

    /**
     * Admin action: extend the seller's current active subscription's end_date
     * by $days. A null end_date (unlimited plan) has nothing to extend.
     */
    public function extend_subscription($seller_id, $days)
    {
        $sub = $this->get_active_subscription($seller_id);
        if (empty($sub) || empty($sub['end_date'])) {
            return false;
        }

        $new_end = date('Y-m-d H:i:s', strtotime('+' . (int) $days . ' days', strtotime($sub['end_date'])));
        return $this->db->set('end_date', $new_end)->where('id', $sub['id'])->update('seller_subscriptions');
    }

    /**
     * Admin dashboard listing: every seller's current plan / status / payment / usage /
     * expiry, for the seller-subscriptions management table.
     *
     * Deliberately set-based. The previous version looped over sellers calling
     * get_current_plan() + get_active_subscription() + check_listing_quota() per row,
     * which is ~7 queries per seller; the table renders every seller at once
     * (client-side pagination), so a few hundred vendors meant a few thousand queries
     * on a single page load. This runs a fixed five regardless of vendor count.
     */
    public function get_all_seller_subscription_status()
    {
        $sellers = $this->db
            ->select('u.id as seller_id, u.username, u.email, u.mobile, sd.shop_name, sd.status as seller_status')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->join('seller_data sd', 'sd.user_id = u.id', 'left')
            ->where('ug.group_id', 4)
            ->order_by('u.id', 'ASC')
            ->get('users u')
            ->result_array();

        if (empty($sellers)) {
            return [];
        }

        $seller_ids = array_map(function ($s) { return (int) $s['seller_id']; }, $sellers);

        // Plans, keyed by id.
        $plans = [];
        foreach ($this->db->get('subscriptions')->result_array() as $plan) {
            $plans[(int) $plan['id']] = $plan;
        }

        // Every seller's most recent subscription row, active or not. ORDER BY is_active
        // first so an active row always wins over a newer-but-cancelled one.
        $subs = [];
        $sub_rows = $this->db
            ->where_in('seller_id', $seller_ids)
            ->order_by('is_active', 'DESC')
            ->order_by('start_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get('seller_subscriptions')
            ->result_array();
        foreach ($sub_rows as $row) {
            if (!isset($subs[(int) $row['seller_id']])) {
                $subs[(int) $row['seller_id']] = $row;
            }
        }

        // Listing counts.
        $usage = [];
        $usage_rows = $this->db
            ->select('seller_id, COUNT(*) as cnt')
            ->where_in('seller_id', $seller_ids)
            ->group_by('seller_id')
            ->get('products')
            ->result_array();
        foreach ($usage_rows as $row) {
            $usage[(int) $row['seller_id']] = (int) $row['cnt'];
        }

        // Most recent successful subscription payment per seller.
        $payments = [];
        foreach ($this->get_subscription_payments($seller_ids) as $row) {
            if (!isset($payments[(int) $row['user_id']])) {
                $payments[(int) $row['user_id']] = $row;
            }
        }

        $today = strtotime(date('Y-m-d'));
        $rows  = [];

        foreach ($sellers as $seller) {
            $seller_id = (int) $seller['seller_id'];
            $sub  = isset($subs[$seller_id]) ? $subs[$seller_id] : null;
            $plan = ($sub && isset($plans[(int) $sub['subscription_id']])) ? $plans[(int) $sub['subscription_id']] : null;
            $used = isset($usage[$seller_id]) ? $usage[$seller_id] : 0;

            // Mirrors get_active_subscription(): is_active AND (no end_date OR not yet past).
            $is_active = false;
            if (!empty($sub) && (int) $sub['is_active'] === 1) {
                $is_active = empty($sub['end_date']) || strtotime(date('Y-m-d', strtotime($sub['end_date']))) >= $today;
            }

            if (empty($sub)) {
                $status = 'None';
            } elseif ($is_active) {
                $status = 'Active';
            } else {
                $status = 'Expired';
            }

            $limit = $plan ? $this->parse_listing_limit($plan['listings_limit']) : null;
            $price = $plan ? $this->price_to_number($plan['price']) : 0.0;

            // Does the PLAN itself define a finite term? This is what separates a genuinely
            // open-ended subscription from one whose end_date simply never got written.
            $validity_days = $plan ? $this->parse_validity_days($plan['validity']) : null;

            // Days left on the current period; null for unlimited / no plan.
            $days_left = null;
            if ($is_active && !empty($sub['end_date'])) {
                $days_left = (int) floor((strtotime(date('Y-m-d', strtotime($sub['end_date']))) - $today) / 86400);
                if ($days_left < 0) {
                    $days_left = 0;
                }
            }

            // Expiry / days-left had one bucket for two very different situations, and
            // reported both as "Never" / "Unlimited":
            //   (a) the plan really is open-ended  -> Never is correct;
            //   (b) the plan has a term (e.g. Basic = 30 days) but end_date was never
            //       written, because these rows predate parse_validity_days() being able to
            //       read the validity column. Calling that "Never" told admin a 30-day plan
            //       lasts forever, and it silently never expires anywhere else either.
            // (b) is a data gap, so it is now labelled as one and the row is flagged.
            $missing_expiry = ($is_active && empty($sub['end_date']) && $validity_days !== null);

            if (empty($sub)) {
                $expiry_text = '';
                $days_text   = '-';
            } elseif (!empty($sub['end_date'])) {
                $expiry_text = $sub['end_date'];
                $days_text   = $days_left === null ? '-' : $days_left;
            } elseif ($missing_expiry) {
                $expiry_text = 'Not set';
                $days_text   = 'Not set';
            } else {
                $expiry_text = 'Never';
                $days_text   = $is_active ? 'Unlimited' : '-';
            }

            // A seller with NO plan is uncapped only because nothing has been assigned to
            // them - reporting that as "Unlimited" made it indistinguishable from a genuine
            // unlimited plan, which is exactly the row an admin most needs to notice.
            if (!$plan) {
                $limit_text     = 'No plan';
                $remaining_text = 'No plan';
            } elseif ($limit === null) {
                $limit_text     = 'Unlimited';
                $remaining_text = 'Unlimited';
            } else {
                $limit_text     = $limit;
                $remaining_text = max(0, $limit - $used);
            }

            $payment = isset($payments[$seller_id]) ? $payments[$seller_id] : null;

            $rows[] = [
                'seller_id'      => $seller_id,
                'shop_name'      => !empty($seller['shop_name'])
                    ? $seller['shop_name']
                    // Fall back to the mobile so a seller with neither shop name nor
                    // username doesn't render as a bare "-" that identifies nobody.
                    : (!empty($seller['username']) ? $seller['username'] : ('#' . $seller_id . ' · ' . $seller['mobile'])),
                'email'          => (string) $seller['email'],
                'mobile'         => (string) $seller['mobile'],
                'plan_name'      => $plan ? $plan['name'] : 'None',
                'plan_type'      => $plan ? ($price > 0 ? 'Paid' : 'Free') : '-',
                'price'          => $plan ? $price : 0,
                'status'         => $status,
                'start_date'     => !empty($sub['start_date']) ? $sub['start_date'] : '',
                'expiry'         => $expiry_text,
                'days_left'      => $days_text,
                'used'           => $used,
                'limit'          => $limit_text,
                'remaining'      => $remaining_text,
                'over_limit'     => ($limit !== null && $used > $limit),
                'no_plan'        => empty($plan),
                'missing_expiry' => $missing_expiry,
                'launch_offer'   => ($plan && strcasecmp(trim($plan['name']), self::LAUNCH_OFFER_PLAN_NAME) === 0) ? 'Yes' : 'No',
                'last_payment'   => $payment ? $payment['amount'] : '',
                'last_paid_on'   => $payment ? $payment['date_created'] : '',
                'last_txn_id'    => $payment ? $payment['txn_id'] : '',
            ];
        }

        return $rows;
    }

    /**
     * Successful subscription payments, newest first. Subscription purchases are written
     * to `transactions` by seller/Subscription.php::razorpay_callback() with order_id 0
     * and a "Seller subscription payment for plan ..." message, which is what separates
     * them from storefront order payments and wallet top-ups for the same user.
     *
     * @param int|array|null $seller_ids One seller, several, or null for all.
     */
    public function get_subscription_payments($seller_ids = null, $limit = null)
    {
        $this->db
            ->where('transaction_type', 'transaction')
            ->where('status', 'success')
            // '0' as a STRING: transactions.order_id is a VARCHAR, and comparing it to the
            // integer 0 makes MySQL coerce every non-numeric order id ("ORD123", ...) to 0
            // as well, which matches far more rows than intended.
            ->where('order_id', '0')
            ->like('message', 'Seller subscription payment', 'after')
            ->order_by('id', 'DESC');

        if (is_array($seller_ids)) {
            if (empty($seller_ids)) {
                return [];
            }
            $this->db->where_in('user_id', $seller_ids);
        } elseif (!empty($seller_ids)) {
            $this->db->where('user_id', (int) $seller_ids);
        }

        if (!empty($limit)) {
            $this->db->limit((int) $limit);
        }

        return $this->db->get('transactions')->result_array();
    }

    /**
     * Full subscription history for one seller (newest first), with the plan joined in -
     * i.e. every activation, renewal, upgrade/downgrade and cancellation.
     */
    public function get_subscription_history($seller_id)
    {
        if (empty($seller_id)) {
            return [];
        }

        return $this->db
            ->select('ss.id, ss.subscription_id, ss.start_date, ss.end_date, ss.is_active, s.name as plan_name, s.price, s.listings_limit, s.validity')
            ->join('subscriptions s', 's.id = ss.subscription_id', 'left')
            ->where('ss.seller_id', $seller_id)
            ->order_by('ss.start_date', 'DESC')
            ->order_by('ss.id', 'DESC')
            ->get('seller_subscriptions ss')
            ->result_array();
    }

    /**
     * Launch-promotion counters for the admin summary strip: how many of the
     * LAUNCH_OFFER_SELLER_CAP free-listing slots have been claimed.
     */
    public function get_launch_offer_stats()
    {
        $plan = $this->ensure_launch_offer_plan();
        $claimed = !empty($plan['id']) ? $this->count_plan_holders($plan['id']) : 0;

        return [
            'claimed'   => $claimed,
            'cap'       => self::LAUNCH_OFFER_SELLER_CAP,
            'remaining' => max(0, self::LAUNCH_OFFER_SELLER_CAP - $claimed),
            'active'    => $claimed < self::LAUNCH_OFFER_SELLER_CAP,
            'plan_id'   => !empty($plan['id']) ? (int) $plan['id'] : 0,
        ];
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
        $used   = $this->get_listing_usage($seller_id);
        $latest = $this->get_latest_subscription($seller_id);

        // No subscription row at all: a legacy seller predating the subscription module,
        // or one whose auto-assignment at sign-up failed. Never block them - that would
        // retroactively lock existing vendors out of their own catalogue.
        if (empty($latest)) {
            return ['allowed' => true, 'limit' => null, 'used' => $used, 'remaining' => null, 'plan_name' => '', 'status' => 'none'];
        }

        $active = $this->get_active_subscription($seller_id);
        $plan   = $this->get_current_plan($seller_id);
        $plan_name = !empty($plan['name']) ? $plan['name'] : '';
        $limit  = $this->parse_listing_limit(isset($plan['listings_limit']) ? $plan['listings_limit'] : '');

        // EXPIRED. get_current_plan() deliberately falls back to the seller's most recent
        // (now lapsed) subscription so the UI can still name the plan they were on - but
        // that fallback previously flowed straight into the quota maths here, so a seller
        // whose plan had expired kept the full listing allowance of the plan they were no
        // longer paying for. Expiry has to actually stop new listings, otherwise nothing
        // in the product ever depends on a subscription staying current.
        if (empty($active)) {
            return [
                'allowed'   => false,
                'limit'     => $limit,
                'used'      => $used,
                'remaining' => 0,
                'plan_name' => $plan_name,
                'status'    => 'expired',
            ];
        }

        if ($limit === null) {
            return ['allowed' => true, 'limit' => null, 'used' => $used, 'remaining' => null, 'plan_name' => $plan_name, 'status' => 'active'];
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
            'status'    => 'active',
        ];
    }

    /**
     * Human-readable reason a listing was refused, shared by every enforcement point
     * (seller panel, seller bulk upload, seller API, admin-on-behalf-of-seller) so they
     * cannot drift apart.
     */
    public function quota_error_message($quota, $adding = 1, $for_admin = false)
    {
        $who  = $for_admin ? 'This seller' : 'You';
        $whos = $for_admin ? "the seller's" : 'your';
        $plan_label = !empty($quota['plan_name']) ? ' on the ' . $quota['plan_name'] . ' plan' : '';

        if (isset($quota['status']) && $quota['status'] === 'expired') {
            return $who . '\'s subscription' . $plan_label . ' has expired, so no new products can be added. Please renew '
                . $whos . ' subscription plan to continue listing.';
        }

        if ((int) $adding > 1) {
            return 'This upload has ' . (int) $adding . ' products, but ' . $whos . ' listing limit is ' . $quota['limit']
                . $plan_label . ' and ' . $quota['used'] . ' are already used (' . $quota['remaining'] . ' left). '
                . 'Please upgrade the subscription or upload fewer products.';
        }

        return $who . ' reached ' . $whos . ' listing limit of ' . $quota['limit'] . ' products' . $plan_label
            . ' (currently used ' . $quota['used'] . '). Please upgrade the subscription plan to add more products.';
    }
}

