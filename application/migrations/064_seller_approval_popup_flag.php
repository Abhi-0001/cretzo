<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Remember that a seller has already been shown the "your account is approved" popup.
 *
 * The approval popup is a one-time congratulation: it must appear on the first dashboard
 * load after the admin flips seller_data.status to 1 and never again. A session flag is not
 * enough - the seller would see it again on every new login - so the acknowledgement is
 * stamped against the seller row. Nulling it again (which admin/Sellers does whenever the
 * status leaves 1) is what re-arms the popup for a re-approval.
 */
class Migration_seller_approval_popup_flag extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('seller_data')) {
            return;
        }

        if (!$this->db->field_exists('approval_popup_seen_at', 'seller_data')) {
            $this->dbforge->add_column('seller_data', [
                'approval_popup_seen_at' => [
                    'type' => 'DATETIME',
                    'NULL' => TRUE,
                ],
            ]);
        }

        // Existing approved sellers have been operating for a while; congratulating them on
        // an approval they received months ago would read as a bug, so they start
        // acknowledged. Only approvals granted from here on trigger the popup.
        $this->db->where('status', 1)
            ->where('approval_popup_seen_at IS NULL', NULL, FALSE)
            ->update('seller_data', ['approval_popup_seen_at' => date('Y-m-d H:i:s')]);
    }

    public function down()
    {
        if ($this->db->field_exists('approval_popup_seen_at', 'seller_data')) {
            $this->dbforge->drop_column('seller_data', 'approval_popup_seen_at');
        }
    }
}
