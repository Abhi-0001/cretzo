<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Give the subscription plans distinct commission rates.
 *
 * All three paid tiers shipped with an identical 8 / 10 / 12, so a seller could pay 999 for
 * Premium and receive exactly the commission they already had for free on Basic. The slab
 * table is presented in the seller panel as the plan's headline benefit, which made the
 * pricing ladder meaningless on its only stated axis.
 *
 * The rates below are chosen so that NO existing seller's commission increases - Basic keeps
 * the current 8/10/12 and the paid tiers only go down from there. Combined with rates now
 * being locked to the sale (migration 037), orders already placed are unaffected either way.
 *
 *   Basic     (free)  8 / 10 / 12   unchanged
 *   Standard  (399)   7 /  9 / 11
 *   Premium   (999)   6 /  8 / 10
 *   Launch Offer      8 / 10 / 12   was NULL, which settled at 0% commission
 *
 * These are a commercial default, not a law. They live in one row each and the admin can
 * edit them on the Subscriptions screen at any time.
 *
 * Guarded: a plan is only updated while it still holds the shipped 8/10/12 (or NULL for
 * Launch Offer). If someone has already set their own rates, this leaves them alone.
 */
class Migration_differentiate_plan_commission extends CI_Migration
{
    public function up()
    {
        $targets = [
            'Standard'     => ['first50' => 7, 'mid' => 9,  'after' => 11],
            'Premium'      => ['first50' => 6, 'mid' => 8,  'after' => 10],
            'Launch Offer' => ['first50' => 8, 'mid' => 10, 'after' => 12],
        ];

        foreach ($targets as $name => $rates) {
            $plan = $this->db->where('name', $name)->get('subscriptions')->row_array();
            if (empty($plan)) {
                continue;
            }

            $untouched = ($plan['commission_first50'] === null && $plan['commission_51_100'] === null && $plan['commission_after100'] === null)
                || ((float) $plan['commission_first50'] === 8.0
                    && (float) $plan['commission_51_100'] === 10.0
                    && (float) $plan['commission_after100'] === 12.0);

            if (!$untouched) {
                continue; // admin has customised this plan - respect it
            }

            $this->db->where('id', $plan['id'])->update('subscriptions', [
                'commission_first50'  => $rates['first50'],
                'commission_51_100'   => $rates['mid'],
                'commission_after100' => $rates['after'],
            ]);
        }
    }

    public function down()
    {
        // No revert: restoring identical rates across every tier would recreate the problem.
    }
}
