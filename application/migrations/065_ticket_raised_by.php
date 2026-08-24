<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Adds `tickets.raised_by` so the admin panel can tell a customer ticket from a seller ticket.
 *
 * Sellers and customers both live in `users` (they differ only by their users_groups row), and
 * tickets.user_id points straight at users.id. Once sellers can raise tickets from their own
 * panel, the admin list would show both kinds side by side with no way to distinguish or filter
 * them - and notify_ticket_event() would email a seller a link to the customer "my-account"
 * page they cannot use. Deriving the answer with a users_groups join on every list query is
 * both slower and wrong for a seller whose group later changes: the ticket was still raised
 * from the seller panel. So it is recorded on the ticket itself.
 *
 * Existing rows are backfilled from current group membership, which is the best available
 * signal for tickets created before this column existed.
 */
class Migration_ticket_raised_by extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('tickets')) {
            return;
        }

        if (!$this->db->field_exists('raised_by', 'tickets')) {
            $this->db->query("ALTER TABLE `tickets` ADD `raised_by` VARCHAR(20) NOT NULL DEFAULT 'customer' AFTER `user_id`");
        }

        // Backfill: anything whose owner is currently in the seller group is a seller ticket.
        if ($this->db->table_exists('users_groups') && $this->db->table_exists('groups')) {
            $this->db->query("
                UPDATE `tickets` t
                JOIN `users_groups` ug ON ug.`user_id` = t.`user_id`
                JOIN `groups` g ON g.`id` = ug.`group_id`
                SET t.`raised_by` = 'seller'
                WHERE g.`name` = 'seller'
            ");
        }

        // Every admin list query filters/sorts on this column.
        $index = $this->db->query("SHOW INDEX FROM `tickets` WHERE Key_name = 'idx_tickets_raised_by'")->result_array();
        if (empty($index)) {
            $this->db->query('ALTER TABLE `tickets` ADD INDEX `idx_tickets_raised_by` (`raised_by`)');
        }
    }

    public function down()
    {
        if ($this->db->table_exists('tickets') && $this->db->field_exists('raised_by', 'tickets')) {
            $this->db->query('ALTER TABLE `tickets` DROP COLUMN `raised_by`');
        }
    }
}
