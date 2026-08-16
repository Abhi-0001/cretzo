<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Void two wallet-credit rows that were recorded but never actually paid.
 *
 * Both were written by the old update_wallet_balance(), whose Razorpay skip-check ran even
 * when no order item was supplied: with an empty order_item_id it matched an arbitrary
 * unrelated transaction, and if that row happened to be a razorpay payment the balance update
 * was skipped while the `transactions` row was still inserted. The result is a ledger that
 * claims the seller was credited when users.balance never moved.
 *
 * Verified individually before writing this migration:
 *
 *   id 60  seller 7  credit 500.00   "…for Order Item ID : 1"
 *          order_items #1 is CANCELLED, is_credited = 0, commission columns 0,
 *          and has no seller_settlements row - it was never settled, so nothing was owed.
 *
 *   id 64  seller 7  credit 2760.00  "…for Order Item ID  : 29"
 *          order_items #29 does not exist, and has no seller_settlements row.
 *
 * So the BALANCE is right and the two ledger rows are the error. They are voided rather than
 * deleted, so the history and the explanation survive. No balance is touched: adding a
 * reversing entry would be correct only if the original had moved money, and it did not.
 *
 * IMPORTANT - why this is matched row by row rather than by pattern: the obvious signature
 * (a wallet credit with order_item_id NULL or 0 and a "Commission Amount Credited" message)
 * also matches every LEGITIMATE commission credit, because the settlement path calls
 * update_wallet_balance() without an order item and the column stores 0. A pattern-based
 * sweep would have voided 20 valid rows on this database alone. Each row is therefore
 * matched on id + user + amount + message together, so this no-ops on any installation where
 * those ids mean something else.
 */
class Migration_void_orphan_wallet_credits extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('status', 'transactions')) {
            return;
        }

        $targets = [
            ['id' => 60, 'user_id' => 7, 'amount' => '500.00',  'message' => 'Commission Amount Credited for Order Item ID : 1'],
            ['id' => 64, 'user_id' => 7, 'amount' => '2760.00', 'message' => 'Commission Amount Credited for Order Item ID  : 29'],
        ];

        foreach ($targets as $target) {
            $row = $this->db->where('id', $target['id'])->get('transactions')->row_array();

            // Every field must still match, or this is a different install's row.
            if (
                empty($row)
                || (string) $row['user_id'] !== (string) $target['user_id']
                || number_format((float) $row['amount'], 2, '.', '') !== $target['amount']
                || trim((string) $row['message']) !== $target['message']
                || $row['type'] !== 'credit'
                || $row['transaction_type'] !== 'wallet'
                || $row['status'] === 'void'
            ) {
                continue;
            }

            $this->db->where('id', $row['id'])->update('transactions', [
                'status'  => 'void',
                'message' => $row['message'] . ' [VOIDED: recorded but never credited - the order item was cancelled or does not exist and was never settled]',
            ]);
        }
    }

    public function down()
    {
        // Not reverted: restoring these to 'success' would re-assert a payment that never
        // happened. If a void turns out to be wrong, correct it deliberately rather than by
        // rolling a migration back.
    }
}
