<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data repair for the defects found in the end-to-end payment/commission audit.
 *
 * The CODE holes are closed elsewhere in this change (a buyer-posted `discount` is now ignored
 * outside the POS, non-positive quantities are refused, PAN/GSTIN are format-validated, and the
 * statutory deposit report can no longer go negative). This migration cleans up the rows those
 * holes left behind, plus one setting that silently switched off a tax.
 *
 * Four repairs, each independently guarded so a partial re-run is safe:
 *
 *   A  system_settings.commission_gst_percent was the string "0", which overrode the statutory
 *      18 that config/commission.php ships. GST on the platform's own commission was therefore
 *      never charged on any settlement.
 *
 *   B  Four `transactions` rows carry transaction_type = 'razorpay', which is not one of the two
 *      values the application uses ('wallet' / 'transaction'). Every reconciliation query filters
 *      on transaction_type, so these rows were invisible to all of them.
 *
 *   C  Three orders hold negative totals - the arithmetic result of the two price-override holes.
 *
 *   D  Four order items cancelled in Nov 2024 have accounted_at but no refunded_at, because the
 *      column did not exist when they were cancelled and migration 044's backfill could not
 *      reconstruct it.
 *
 * DELIBERATELY NOT DONE HERE - these move real money and are the owner's call, not a migration's:
 *   - seller 7's wallet reads 0.00 against a ledger of 44,444.36;
 *   - order item 5 was refunded 1,900 TWICE (transactions 66 and 67, 45 seconds apart);
 *   - user 8 is left 1,900 short after repair B.
 * All three are surfaced by the admin Commission & Settlements reconciliation panel.
 */
class Migration_payment_flow_repair extends CI_Migration
{
    public function up()
    {
        $this->fix_commission_gst_percent();
        $this->fix_stray_transaction_types();
        $this->fix_negative_order_totals();
        $this->fix_legacy_refund_stamps();
    }

    /* ------------------------------------------------------------------ A ------ */

    /**
     * Commission is a service the marketplace supplies to the seller, so it attracts GST and the
     * platform issues a tax invoice for it. The rate lives in system_settings so an accountant can
     * set it without a developer, and Setting_model defaults it to '18' - but only when the stored
     * value is EMPTY. An explicit '0' wins, and that is what this database held, so
     * calculate_settlement_breakdown() computed commission_gst_amount = 0.00 on all 18 settled
     * rows (about 725 of uninvoiced GST on 4,027.65 of commission).
     *
     * Only an explicit zero / blank is corrected. A deliberate non-zero rate someone has already
     * chosen is left exactly as it is, so re-running this cannot overwrite a real decision.
     */
    private function fix_commission_gst_percent()
    {
        $row = $this->db->select('value')->where('variable', 'system_settings')->get('settings')->row_array();
        if (empty($row['value'])) {
            return;
        }

        // The column stores a JSON blob, sometimes wrapped in an extra layer of encoding by the
        // settings writer. Decode defensively and leave the row untouched if it is not the shape
        // expected - a half-understood blob must not be rewritten.
        $decoded = json_decode($row['value'], true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        if (!is_array($decoded) || !array_key_exists('commission_gst_percent', $decoded)) {
            return;
        }

        $current = trim((string) $decoded['commission_gst_percent']);
        if ($current !== '' && (float) $current != 0.0) {
            return; // already set to something deliberate
        }

        $decoded['commission_gst_percent'] = '18';
        $this->db->where('variable', 'system_settings')
            ->update('settings', ['value' => json_encode($decoded)]);

        // The settings blob is memoised per request; anything reading it after this write in the
        // same request would otherwise still see the old value.
        if (function_exists('clear_settings_cache')) {
            clear_settings_cache('system_settings');
        }
    }

    /* ------------------------------------------------------------------ B ------ */

    /**
     * Re-type the four stray ledger rows as the wallet refunds they actually are.
     *
     * They carry transaction_type = 'razorpay', type = 'refund', a NULL txn_id and an
     * order_item_id. A real gateway refund always has the gateway's refund id in txn_id, so a
     * NULL one was never a gateway movement - the money went to the customer's wallet, and the
     * balances prove it: reclassifying these closes user 6's balance-vs-ledger gap EXACTLY
     * (3,900.00 against 3,900.00) and moves user 8 from 8,000 out to 1,900 out.
     *
     * Narrow WHERE clause on purpose: only rows that match the whole fingerprint are touched, so
     * a genuine gateway row that happens to be typed this way in future is left alone.
     */
    private function fix_stray_transaction_types()
    {
        if (!$this->db->table_exists('transactions')) {
            return;
        }

        $this->db->where('transaction_type', 'razorpay')
            ->where('type', 'refund')
            ->where('txn_id IS NULL', null, false)
            ->update('transactions', ['transaction_type' => 'wallet']);
    }

    /* ------------------------------------------------------------------ C ------ */

    /**
     * Rebuild the totals on orders that hold a negative amount.
     *
     * These are what the two price-override holes produced. orders.id=27 carries discount=3971
     * against a basket of 1099, giving total_payable = -2872 - a COD bill the courier can only
     * "collect" by handing money back. orders.id=26 carries a negative basket total from a
     * negative quantity.
     *
     * The reconstruction uses the same rule process_refund() uses: an order's value is the sum of
     * its order_items that are still live, i.e. excluding lines already cancelled or returned.
     * That gives the right answer whether or not a refund has run.
     *
     * The bogus `discount` is cleared rather than clamped. Clamping 3971 down to the 1099 basket
     * would assert that the goods were given away free, which is a claim about a real transaction
     * that nothing in the data supports; 3971 is larger than anything that was ever for sale, so
     * it was never a discount at all. Restoring full price keeps the arithmetic honest and leaves
     * any genuine counter discount for a human to re-enter on that specific bill.
     */
    private function fix_negative_order_totals()
    {
        if (!$this->db->table_exists('orders') || !$this->db->table_exists('order_items')) {
            return;
        }

        $has_discount_col = $this->db->field_exists('discount', 'orders');
        $has_payable_col  = $this->db->field_exists('total_payable', 'orders');

        $broken = $this->db->select('id, delivery_charge, promo_discount, wallet_balance'
                . ($has_discount_col ? ', discount' : '')
                . ($has_payable_col ? ', total_payable' : ''))
            ->group_start()
                ->where('total <', 0)
                ->or_where('final_total <', 0)
            ->group_end()
            ->get('orders')->result_array();

        if ($has_payable_col) {
            $also = $this->db->select('id, delivery_charge, promo_discount, wallet_balance'
                    . ($has_discount_col ? ', discount' : '') . ', total_payable')
                ->where('total_payable <', 0)
                ->get('orders')->result_array();
            foreach ($also as $row) {
                $broken[$row['id']] = $row;
            }
        }

        foreach ($broken as $order) {
            $live = $this->db->select('COALESCE(SUM(sub_total), 0) AS live_total', false)
                ->where('order_id', $order['id'])
                ->where_not_in('active_status', ['cancelled', 'returned'])
                ->get('order_items')->row_array();

            $total = round((float) $live['live_total'], 2);
            $delivery = round((float) $order['delivery_charge'], 2);
            $delivery = ($delivery < 0) ? 0 : $delivery;
            $promo = round((float) $order['promo_discount'], 2);
            $promo = ($promo < 0) ? 0 : $promo;
            $wallet = round((float) $order['wallet_balance'], 2);
            $wallet = ($wallet < 0) ? 0 : $wallet;

            $final = round($total + $delivery - $promo, 2);
            $final = ($final < 0) ? 0 : $final;
            $payable = round($final - $wallet, 2);
            $payable = ($payable < 0) ? 0 : $payable;

            $set = [
                'total'           => $total,
                'final_total'     => $final,
                'delivery_charge' => $delivery,
                'promo_discount'  => $promo,
                'wallet_balance'  => $wallet,
            ];
            if ($has_discount_col) {
                $set['discount'] = 0;
            }
            if ($has_payable_col) {
                $set['total_payable'] = $payable;
            }

            $this->db->where('id', $order['id'])->update('orders', $set);

            log_message('error', 'Migration 072: rebuilt totals on order ' . $order['id']
                . ' (total=' . $total . ' final_total=' . $final . ' total_payable=' . $payable
                . '). The original figures were negative and any counter discount on this bill'
                . ' was cleared - please re-check it against the physical receipt.');

            // order_charges holds the same figures per seller parcel and went negative alongside
            // the order (order 26's parcel row reads -1099). Rebuilt from the same live-item rule.
            if ($this->db->table_exists('order_charges')) {
                $parcels = $this->db->select('seller_id')
                    ->where('order_id', $order['id'])
                    ->get('order_charges')->result_array();

                foreach ($parcels as $parcel) {
                    $parcel_total = $this->db->select('COALESCE(SUM(sub_total), 0) AS t', false)
                        ->where('order_id', $order['id'])
                        ->where('seller_id', $parcel['seller_id'])
                        ->where_not_in('active_status', ['cancelled', 'returned'])
                        ->get('order_items')->row_array();

                    $pt = round((float) $parcel_total['t'], 2);
                    $this->db->where('order_id', $order['id'])
                        ->where('seller_id', $parcel['seller_id'])
                        ->update('order_charges', ['sub_total' => $pt, 'total' => $pt]);
                }
            }
        }
    }

    /* ------------------------------------------------------------------ D ------ */

    /**
     * Close out the pre-migration cancellations so the integrity checks mean something.
     *
     * Four items cancelled in Nov 2024 have accounted_at (backfilled by migration 044/045) but
     * refunded_at NULL, because that column did not exist when they were cancelled. Left NULL
     * they read as "cancelled but never refunded", which is indistinguishable from a live refund
     * that genuinely failed - so a real one would hide among them.
     *
     * accounted_at is used as the stamp because it is the only evidence on the row of when the
     * cancellation was actually processed. Only rows that already have it are touched: an item
     * cancelled with NEITHER stamp has genuinely not been through process_refund() and must stay
     * visible.
     */
    private function fix_legacy_refund_stamps()
    {
        if (!$this->db->table_exists('order_items')
            || !$this->db->field_exists('refunded_at', 'order_items')
            || !$this->db->field_exists('accounted_at', 'order_items')
        ) {
            return;
        }

        $this->db->set('refunded_at', 'accounted_at', false)
            ->where('refunded_at IS NULL', null, false)
            ->where('accounted_at IS NOT NULL', null, false)
            ->where_in('active_status', ['cancelled', 'returned'])
            ->update('order_items');
    }

    public function down()
    {
        // Deliberately empty. Every step here replaces a corrupt or contradictory value with a
        // correct one; restoring negative order totals, an un-typed ledger row or a tax rate of
        // zero is not a state worth being able to return to.
    }
}
