<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Zero-commission launch offer (owner, 2026-10-24): the first 20 sellers pay 0% commission on
 * their first 10 orders.
 *
 *   seller_commission_offer   one row per ENROLLED seller. Enrolment is what makes someone one
 *                             of "the first 20" - it happens once, at sign-up (or by an admin),
 *                             and the cap is checked atomically at insert, so the set of sellers
 *                             cannot drift later. orders_limit is copied from the settings at
 *                             enrolment, so changing the setting later does not move the goal
 *                             posts for sellers already enrolled.
 *
 *   settings.zero_commission_offer   {"enabled":1,"seller_limit":20,"orders_per_seller":10}
 *                             enabled controls NEW enrolments only - switching the offer off never
 *                             takes the benefit away from a seller who was already promised it
 *                             (an admin can revoke one seller explicitly).
 *
 * Orders are counted, not order items: each of the seller's first 10 orders is commission-free
 * on every line. The rate is locked onto order_items at checkout with commission_rate_source
 * 'zero_offer', and a cancelled or returned order gives its slot back. See
 * Commission_offer_model.
 */
class Migration_zero_commission_offer extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('seller_commission_offer')) {
            $this->dbforge->add_field([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'seller_id'    => ['type' => 'INT', 'constraint' => 11, 'NULL' => FALSE],
                'orders_limit' => ['type' => 'INT', 'constraint' => 11, 'NULL' => FALSE, 'default' => 10],
                'source'       => ['type' => 'VARCHAR', 'constraint' => 20, 'NULL' => FALSE, 'default' => 'signup'],
                'status'       => ['type' => 'TINYINT', 'constraint' => 1, 'NULL' => FALSE, 'default' => 1],
                'enrolled_at'  => ['type' => 'DATETIME', 'NULL' => FALSE],
                'revoked_at'   => ['type' => 'DATETIME', 'NULL' => TRUE],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('seller_commission_offer', TRUE);
            $this->db->query('ALTER TABLE seller_commission_offer ADD UNIQUE KEY uq_seller (seller_id)');
        }

        $exists = $this->db->where('variable', 'zero_commission_offer')->count_all_results('settings');
        if (!$exists) {
            $this->db->insert('settings', [
                'variable' => 'zero_commission_offer',
                'value'    => json_encode(['enabled' => 1, 'seller_limit' => 20, 'orders_per_seller' => 10]),
            ]);
        }
        if (function_exists('clear_settings_cache')) {
            clear_settings_cache();
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('seller_commission_offer', TRUE);
        $this->db->where('variable', 'zero_commission_offer')->delete('settings');
    }
}
