<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clears rows that outlived the account they belong to.
 *
 * Deleting a seller previously did not delete the `users` row at all (it only demoted the
 * account from the seller group), and deleting a customer removed the user without ever
 * touching seller_subscriptions. Both left rows keyed on a user id that no longer resolves.
 *
 * The one that actually caused visible damage is `seller_subscriptions`: an orphan there is
 * still counted by the launch-offer holder count, so a deleted vendor permanently consumed
 * one of the 100 promotional slots. (There is one such row in the live data.)
 *
 * Only rows whose parent user is genuinely gone are removed - nothing belonging to a live
 * account is touched. Orders, transactions and order_items are left alone: they are
 * financial history and are deliberately kept after an account is deleted.
 */
class Migration_clean_orphaned_user_rows extends CI_Migration
{
    public function up()
    {
        $tables = [
            'users_groups'         => 'user_id',
            'user_permissions'     => 'user_id',
            'seller_data'          => 'user_id',
            'seller_subscriptions' => 'seller_id',
            'addresses'            => 'user_id',
            'cart'                 => 'user_id',
            'favorites'            => 'user_id',
        ];

        foreach ($tables as $table => $column) {
            if (!$this->db->table_exists($table)) {
                continue;
            }

            $this->db->query(
                'DELETE t FROM `' . $table . '` t
                 LEFT JOIN users u ON u.id = t.`' . $column . '`
                 WHERE u.id IS NULL'
            );
        }
    }

    public function down()
    {
        // Not reversible - these rows referenced accounts that no longer exist.
    }
}
