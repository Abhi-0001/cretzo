<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Backfills seller_subscriptions.end_date for rows that were saved with NULL.
 *
 * Those rows date from before Seller_subscription_model::parse_validity_days() could
 * read the plans' `validity` column, so no expiry was ever computed and the subscription
 * silently became a lifetime one. Symptom on the front end: the seller dashboard's
 * "Valid till ..." line is driven by end_date, so it simply rendered nothing.
 *
 * Two cases are handled differently on purpose:
 *
 *  - Computed end date still in the future -> written as-is. Nothing changes for the
 *    seller except that an accurate expiry now displays.
 *
 *  - Computed end date ALREADY in the past (e.g. a 30-day plan started months ago). These
 *    are genuinely lapsed, but writing the true date would flip them to Expired the moment
 *    this migration runs and - now that expiry actually blocks new listings - lock those
 *    vendors out with no warning. They instead get a short grace window from the migration
 *    date so admin and the seller both get a chance to act on it. GRACE_DAYS is the one
 *    number to change if you would rather cut them off immediately (set it to 0).
 *
 * Inactive (superseded) rows are left alone - they are history, nothing reads their
 * end_date, and inventing dates for them would only muddy the audit trail.
 */
class Migration_backfill_subscription_end_dates extends CI_Migration
{
    /** Grace window for active subscriptions whose true expiry has already passed. */
    const GRACE_DAYS = 14;

    public function up()
    {
        $this->load->model('Seller_subscription_model');

        $rows = $this->db
            ->select('ss.id, ss.start_date, s.validity')
            ->join('subscriptions s', 's.id = ss.subscription_id')
            ->where('ss.end_date IS NULL', null, false)
            ->where('ss.is_active', 1)
            ->get('seller_subscriptions ss')
            ->result_array();

        $today = date('Y-m-d');

        foreach ($rows as $row) {
            $days = $this->Seller_subscription_model->parse_validity_days($row['validity']);

            // Genuinely unlimited / unparseable validity: leave NULL, which is what the
            // rest of the code already reads as "never expires".
            if ($days === null || empty($row['start_date'])) {
                continue;
            }

            $end = date('Y-m-d', strtotime('+' . $days . ' days', strtotime($row['start_date'])));

            if ($end < $today) {
                $end = date('Y-m-d', strtotime('+' . self::GRACE_DAYS . ' days', strtotime($today)));
            }

            $this->db->set('end_date', $end)->where('id', $row['id'])->update('seller_subscriptions');
        }
    }

    public function down()
    {
        // Deliberately not reversible: there is no way to tell a backfilled end_date from
        // one a real purchase wrote, so nulling them out again would destroy live expiry
        // dates belonging to paying sellers.
    }
}
