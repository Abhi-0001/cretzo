<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mobile alerts: every in-app notification a seller or buyer receives is also sent to their
 * mobile number by SMS (see notify_user_mobile() in sms_helper.php).
 *
 * `notification_mobile_log` records each attempt - what was sent, to whom, and whether the
 * gateway accepted it - so an admin can see why a seller did not get a text, and so the same
 * message is not sent twice in a short window.
 *
 * Settings row `mobile_notifications` = {sms, sellers, customers, broadcasts}:
 *   sms        master switch for the channel
 *   sellers    send to sellers
 *   customers  send to buyers
 *   broadcasts also text admin broadcasts to a whole audience ("All Sellers" etc.). Off by default:
 *              one broadcast would be one SMS per account, and SMS is billed per message.
 * Nothing is sent until an SMS gateway is configured, so enabling this is safe by itself.
 */
class Migration_mobile_notification_alerts extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `notification_mobile_log` (
            `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `channel` VARCHAR(16) NOT NULL DEFAULT 'sms',
            `mobile` VARCHAR(20) DEFAULT NULL,
            `type` VARCHAR(64) DEFAULT NULL,
            `type_id` VARCHAR(128) DEFAULT NULL,
            `message_hash` CHAR(32) NOT NULL,
            `message` VARCHAR(600) DEFAULT NULL,
            `status` VARCHAR(12) NOT NULL DEFAULT 'sent',
            `error` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_dedupe` (`user_id`, `channel`, `message_hash`, `created_at`),
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $exists = $this->db->where('variable', 'mobile_notifications')->count_all_results('settings');
        if (!$exists) {
            $this->db->insert('settings', [
                'variable' => 'mobile_notifications',
                'value'    => json_encode(['sms' => 1, 'sellers' => 1, 'customers' => 1, 'broadcasts' => 0]),
            ]);
        }
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `notification_mobile_log`");
        $this->db->where('variable', 'mobile_notifications')->delete('settings');
    }
}
