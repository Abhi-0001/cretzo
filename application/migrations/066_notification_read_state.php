<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Per-user read state for `notifications`, so buyers and sellers can have a working bell.
 *
 * `notifications` is a broadcast log: one row can be addressed to everybody, to a role, or to a
 * list of user ids (`send_to` + `users_id`). It has no read flag at all, so "how many unread
 * notifications does THIS user have" was unanswerable - which is why the storefront bell was a
 * static image with a hardcoded 0 and the seller panel had no bell at all. A flag on
 * `notifications` cannot work either: one broadcast row is read by one user and not another.
 *
 * Hence a join table. Absence of a row means unread, so nothing needs backfilling: every
 * existing notification simply starts unread, which is honest.
 */
class Migration_notification_read_state extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('notification_reads')) {
            $this->db->query("
                CREATE TABLE `notification_reads` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `user_id` INT(11) NOT NULL,
                    `notification_id` INT(11) NOT NULL,
                    `date_read` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uniq_notification_reads` (`user_id`, `notification_id`),
                    KEY `idx_notification_reads_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        // Every audience query filters on send_to; it was unindexed.
        if ($this->db->table_exists('notifications')) {
            $index = $this->db->query("SHOW INDEX FROM `notifications` WHERE Key_name = 'idx_notifications_send_to'")->result_array();
            if (empty($index)) {
                $this->db->query('ALTER TABLE `notifications` ADD INDEX `idx_notifications_send_to` (`send_to`)');
            }

            // `link` and `type_id` are both NOT NULL with no default, so every insert that
            // omits them (the ticket and order notification helpers both do) relies on MySQL's
            // non-strict mode silently substituting ''. Under STRICT_TRANS_TABLES - the default
            // on MySQL 5.7+ and on most managed hosts - those same inserts are rejected
            // outright and the notification is simply lost. Giving the columns a default makes
            // the existing callers correct rather than lucky.
            //
            // Run unconditionally rather than gated on the current Default: MODIFY to an
            // identical definition is a no-op, whereas reading the current default back
            // portably (SHOW COLUMNS reports it differently across MySQL/MariaDB versions) is
            // not worth the guard - and getting that guard subtly wrong silently skips the fix.
            if ($this->db->field_exists('link', 'notifications')) {
                $this->db->query("ALTER TABLE `notifications` MODIFY `link` VARCHAR(512) NOT NULL DEFAULT ''");
            }
            if ($this->db->field_exists('type_id', 'notifications')) {
                $this->db->query("ALTER TABLE `notifications` MODIFY `type_id` VARCHAR(128) NOT NULL DEFAULT ''");
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('notification_reads')) {
            $this->db->query('DROP TABLE `notification_reads`');
        }
    }
}
