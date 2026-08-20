<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Repairs the schema behind the messaging / support-ticket / notification stack.
 *
 * Every change here fixes something that was demonstrably broken in production data:
 *
 *  1. `ticket_messages.id` was `int(11) NOT NULL PRIMARY KEY` with NO AUTO_INCREMENT. The
 *     first reply on the whole platform inserted with id=0 (and, because insert_id() then
 *     returns 0, was reported back to the admin as "Ticket message could not be sent!"),
 *     and every reply after that died with "Duplicate entry '0' for key 'PRIMARY'". In other
 *     words the support-ticket conversation feature could never hold more than one message.
 *
 *  2. `system_notification.message` was `varchar(20)`, so every admin notification was
 *     truncated mid-word after 20 characters ("New order received f"). Widened to 512 to
 *     match the `notifications.message` column it mirrors.
 *
 *  3. `tickets.status` defaulted to 0, which is not one of the five defined statuses
 *     (PENDING..REOPEN = 1..5) and renders as a blank badge in the admin list.
 *
 *  4. `messages` rows with from_id/to_id = 0 are undeliverable leftovers from the unvalidated
 *     send endpoints (fixed in the controllers): they surfaced in chat history as a
 *     conversation with a non-existent "user 0".
 *
 *  5. Missing indexes on the columns every chat / ticket lookup filters by.
 */
class Migration_messaging_and_ticket_integrity extends CI_Migration
{
    public function up()
    {
        /* ---- 1. ticket_messages.id must auto-increment ---------------------------- */
        if ($this->db->table_exists('ticket_messages')) {
            $field = $this->get_column('ticket_messages', 'id');
            if (!empty($field) && stripos($field['Extra'], 'auto_increment') === false) {
                // An existing id=0 row would block the AUTO_INCREMENT conversion (MySQL will
                // not allow 0 in an auto-increment PK under NO_AUTO_VALUE_ON_ZERO-less mode),
                // so renumber it out of the way first, keeping the message itself.
                $this->db->query('UPDATE `ticket_messages` SET `id` = (SELECT COALESCE(MAX(t.`id`),0) + 1 FROM (SELECT `id` FROM `ticket_messages`) t) WHERE `id` = 0');
                $this->db->query('ALTER TABLE `ticket_messages` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT');
            }
        }

        /* ---- 2. system_notification.message was truncating at 20 chars ------------ */
        if ($this->db->table_exists('system_notification')) {
            $field = $this->get_column('system_notification', 'message');
            if (!empty($field) && stripos($field['Type'], 'varchar(20)') !== false) {
                $this->db->query('ALTER TABLE `system_notification` MODIFY `message` VARCHAR(512) DEFAULT NULL');
            }
        }

        /* ---- 2b. notifications.type was varchar(12) ------------------------------- */
        // Too narrow for the type strings the product itself writes: 'notification_url' (16),
        // 'ticket_message' (14) and 'ticket_status' (13) were all silently truncated mid-word
        // on insert, so nothing downstream could match on them.
        if ($this->db->table_exists('notifications')) {
            $field = $this->get_column('notifications', 'type');
            if (!empty($field) && preg_match('/varchar\((\d+)\)/i', $field['Type'], $m) && (int) $m[1] < 64) {
                $this->db->query('ALTER TABLE `notifications` MODIFY `type` VARCHAR(64) NOT NULL');
            }
        }

        /* ---- 3. tickets.status default must be a real status ---------------------- */
        if ($this->db->table_exists('tickets')) {
            $this->db->query('ALTER TABLE `tickets` MODIFY `status` TINYINT(4) NOT NULL DEFAULT 1');
            $this->db->query('UPDATE `tickets` SET `status` = 1 WHERE `status` NOT BETWEEN 1 AND 5');
        }

        /* ---- 4. drop undeliverable chat rows -------------------------------------- */
        if ($this->db->table_exists('messages')) {
            $this->db->query('DELETE FROM `messages` WHERE `from_id` = 0 OR `to_id` = 0');
        }

        /* ---- 5. indexes ----------------------------------------------------------- */
        $this->add_index('messages', 'idx_messages_from_to', '`from_id`, `to_id`');
        $this->add_index('messages', 'idx_messages_to_read', '`to_id`, `is_read`');
        $this->add_index('ticket_messages', 'idx_ticket_messages_ticket', '`ticket_id`');
        $this->add_index('tickets', 'idx_tickets_user', '`user_id`');
        $this->add_index('tickets', 'idx_tickets_status', '`status`');
        $this->add_index('chat_messages', 'idx_chat_messages_user', '`user_id`');
        $this->add_index('chat_messages', 'idx_chat_messages_session', '`session_id`');
        $this->add_index('chat_media', 'idx_chat_media_message', '`message_id`');
    }

    public function down()
    {
        // Deliberately minimal: the "down" of a data-integrity repair would re-break the
        // feature. Only the indexes are reversible without losing anything.
        foreach ([
            'messages'        => ['idx_messages_from_to', 'idx_messages_to_read'],
            'ticket_messages' => ['idx_ticket_messages_ticket'],
            'tickets'         => ['idx_tickets_user', 'idx_tickets_status'],
            'chat_messages'   => ['idx_chat_messages_user', 'idx_chat_messages_session'],
            'chat_media'      => ['idx_chat_media_message'],
        ] as $table => $indexes) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            foreach ($indexes as $index) {
                if ($this->index_exists($table, $index)) {
                    $this->db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
                }
            }
        }
    }

    private function get_column($table, $column)
    {
        $row = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($column))->row_array();
        return !empty($row) ? $row : null;
    }

    private function index_exists($table, $index)
    {
        $row = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $this->db->escape($index))->row_array();
        return !empty($row);
    }

    private function add_index($table, $index, $columns)
    {
        if (!$this->db->table_exists($table) || $this->index_exists($table, $index)) {
            return;
        }
        $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $index . '` (' . $columns . ')');
    }
}
