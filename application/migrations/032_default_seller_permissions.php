<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gives every seller profile that has no permissions blob the default set.
 *
 * seller_data.permissions was left NULL by every registration path, and
 * get_seller_permission() resolves every key of a NULL blob to null - which all ~15
 * call sites treat as "not permitted". The practical effect was that a seller who
 * signed up and was approved still could not see the order OTP, and so could not
 * mark an item delivered, until an admin opened their profile and re-saved it.
 *
 * Only rows with no permissions at all are touched; anything an admin has already
 * configured is left exactly as it is.
 */
class Migration_default_seller_permissions extends CI_Migration
{
    private function default_permissions()
    {
        return json_encode([
            'require_products_approval' => 1,
            'customer_privacy'          => 0,
            'view_order_otp'            => 1,
            'assign_delivery_boy'       => 0,
        ]);
    }

    public function up()
    {
        $this->db
            ->group_start()
                ->where('permissions IS NULL', null, false)
                ->or_where('permissions', '')
            ->group_end()
            ->update('seller_data', ['permissions' => $this->default_permissions()]);
    }

    public function down()
    {
        // Deliberately not reversed: the rows this filled in are indistinguishable
        // afterwards from rows an admin has since edited by hand, so putting NULL back
        // would risk discarding real configuration.
    }
}
