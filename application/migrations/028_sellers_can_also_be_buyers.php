<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Lets one mobile number be both a buyer and a seller.
 *
 * users.mobile is UNIQUE *and* it is ion_auth's identity column, so two separate accounts
 * genuinely cannot share a number - login, password reset and OTP would all become
 * ambiguous. The fix is therefore one account holding both roles, not two accounts.
 *
 * Previously a seller account held only group 4, and the storefront login refuses anyone
 * who is not in group 2 ("members"), so a seller literally could not buy anything without
 * registering a second account on a different number. This backfills group 2 onto every
 * existing seller so they can shop with the account they already have. New signups get
 * both groups at registration (seller/Auth.php), and an existing buyer can now add the
 * seller role to their account instead of being told their number is taken.
 *
 * Sellers keep group 4, so nothing about their seller access changes.
 */
class Migration_sellers_can_also_be_buyers extends CI_Migration
{
    /** ion_auth group ids: 1 admin, 2 members (buyers), 3 delivery_boy, 4 seller. */
    const GROUP_MEMBERS = 2;
    const GROUP_SELLER  = 4;

    public function up()
    {
        $sellers = $this->db
            ->select('ug.user_id')
            ->from('users_groups ug')
            ->where('ug.group_id', self::GROUP_SELLER)
            // Only those who don't already have the buyer group.
            ->where('NOT EXISTS (SELECT 1 FROM users_groups m WHERE m.user_id = ug.user_id AND m.group_id = ' . self::GROUP_MEMBERS . ')', null, false)
            ->get()
            ->result_array();

        if (empty($sellers)) {
            return;
        }

        $rows = [];
        foreach ($sellers as $seller) {
            $rows[] = [
                'user_id'  => (int) $seller['user_id'],
                'group_id' => self::GROUP_MEMBERS,
            ];
        }

        $this->db->insert_batch('users_groups', $rows);
    }

    public function down()
    {
        // Deliberately not reversible: there is no way to tell a buyer role that was
        // backfilled here from one a seller legitimately signed up for, and removing the
        // wrong ones would lock those people out of their own order history.
    }
}
