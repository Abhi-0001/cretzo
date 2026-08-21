<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Records which pickup addresses Shiprocket actually knows about, and whether their phone is
 * verified.
 *
 * Shiprocket books a pickup by NICKNAME, and rejects anything it does not hold on the account:
 *
 *     "Wrong Pickup location entered. Please choose one location from the data given"
 *
 * Reproduced live on this store. `pickup_locations` held one hand-entered row, "Developer's Den"
 * (Noida 201301), which is not on the Shiprocket account at all - the account's three real
 * addresses are in South Delhi. Nothing distinguished the two, so the pickup resolver picked the
 * stale row (it sorts oldest-first) and every booking failed. Worse, it failed LATE: the
 * serviceability check only needs a pincode, so the storefront happily quoted a delivery charge
 * and took the order, and the rejection only appeared when someone tried to ship it.
 *
 * Two columns:
 *   shiprocket_verified_at - stamped by the Import from Shiprocket action. NULL means "we have
 *                            never seen Shiprocket confirm this address", which is exactly the
 *                            state that breaks booking.
 *   phone_verified         - Shiprocket reports this per address and will not schedule a pickup
 *                            from one whose phone is unverified.
 *
 * Both are nullable with no default, so existing rows keep working and simply rank below verified
 * ones in resolve_seller_pickup_location().
 */
class Migration_pickup_location_shiprocket_sync extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('pickup_locations')) {
            return;
        }

        if (!$this->db->field_exists('shiprocket_verified_at', 'pickup_locations')) {
            $this->dbforge->add_column('pickup_locations', [
                'shiprocket_verified_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }

        if (!$this->db->field_exists('phone_verified', 'pickup_locations')) {
            $this->dbforge->add_column('pickup_locations', [
                'phone_verified' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('pickup_locations')) {
            return;
        }
        foreach (['shiprocket_verified_at', 'phone_verified'] as $column) {
            if ($this->db->field_exists($column, 'pickup_locations')) {
                $this->dbforge->drop_column('pickup_locations', $column);
            }
        }
    }
}
