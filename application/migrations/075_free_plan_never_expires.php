<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clears the leftover expiry dates on Free Seller subscriptions.
 *
 * Migration 074 made the free tier lifetime (blank `validity` -> assign_subscription()
 * writes a NULL end_date). But sellers put on that plan while it was still the 30-day
 * "Basic" already had an end_date written into `seller_subscriptions`, and every
 * active-plan check reads that row, not the plan. Left alone, those sellers would lapse
 * off a plan that no longer expires - two of them already had, which silently drops the
 * seller to "no plan" on the dashboard and in the expiry cron.
 *
 * Scoped to rows whose plan currently parses as never-expiring, so a seller on a real
 * paid term keeps their date.
 */
class Migration_free_plan_never_expires extends CI_Migration
{
    public function up()
    {
        $this->load->model('Seller_subscription_model');

        $plans = $this->db->select('id, validity')->get('subscriptions')->result_array();

        $lifetime_ids = [];
        foreach ($plans as $plan) {
            if ($this->Seller_subscription_model->parse_validity_days($plan['validity']) === null) {
                $lifetime_ids[] = (int) $plan['id'];
            }
        }

        if (empty($lifetime_ids)) {
            return;
        }

        $this->db->where_in('subscription_id', $lifetime_ids)
            ->where('end_date IS NOT NULL', null, false)
            ->update('seller_subscriptions', ['end_date' => null]);
    }

    public function down()
    {
        // The discarded dates were wrong for the plan; nothing to restore.
    }
}
