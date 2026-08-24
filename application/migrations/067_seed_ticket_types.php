<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Default support categories.
 *
 * Both support pages - `seller/support` and `my-account/support` - gate ticket creation on
 * `ticket_types` having at least one row ($has_types in their views): with none, the "New Ticket"
 * button is disabled and the page says "Support categories have not been set up yet". That is the
 * correct behaviour, because a ticket's `ticket_type_id` must point at a real row, but it left the
 * live site with support switched off for everybody and no hint that the missing piece was a table
 * only reachable from Admin > Support Tickets > Ticket Types.
 *
 * Seeding a starter set makes support work out of the box. Titles were chosen to cover what a
 * marketplace actually gets tickets about, and to read sensibly to BOTH audiences, since sellers
 * and customers pick from the same list.
 *
 * Deliberately only seeds when the table is EMPTY: if an admin has already curated their own
 * categories, adding to them would be meddling.
 */
class Migration_seed_ticket_types extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('ticket_types')) {
            return;
        }

        if ($this->db->count_all_results('ticket_types') > 0) {
            return;
        }

        $titles = [
            'General Enquiry',
            'Order Issue',
            'Payment or Refund',
            'Shipping and Delivery',
            'Product or Listing',
            'Account and Login',
            'Technical Problem',
            'Other',
        ];

        $rows = [];
        foreach ($titles as $title) {
            $rows[] = ['title' => $title];
        }
        $this->db->insert_batch('ticket_types', $rows);
    }

    /**
     * Only removes the seeded titles, and only the ones nothing points at - a ticket whose
     * category row vanished would render a blank category for ever.
     */
    public function down()
    {
        if (!$this->db->table_exists('ticket_types')) {
            return;
        }

        $used = $this->db->distinct()->select('ticket_type_id')->get('tickets')->result_array();
        $used_ids = array_filter(array_map('intval', array_column($used, 'ticket_type_id')));

        $this->db->where_in('title', [
            'General Enquiry',
            'Order Issue',
            'Payment or Refund',
            'Shipping and Delivery',
            'Product or Listing',
            'Account and Login',
            'Technical Problem',
            'Other',
        ]);
        if (!empty($used_ids)) {
            $this->db->where_not_in('id', $used_ids);
        }
        $this->db->delete('ticket_types');
    }
}
