<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * `seller_deletion_requests` - a seller asking the admin to delete their account.
 *
 * Sellers can no longer delete their own account (the web panel never offered it; the seller
 * mobile API's delete_seller used to, and now files one of these instead). They write a note,
 * an admin reads it and either deletes the account (status 'completed') or declines it.
 *
 * `snapshot` keeps the seller's name / shop / mobile as they were when the request was made,
 * because the account is gone by the time a completed request is looked at again.
 */
class Migration_seller_deletion_requests extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `seller_deletion_requests` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `seller_id` INT(11) NOT NULL,
            `note` VARCHAR(1000) NOT NULL,
            `snapshot` VARCHAR(1000) DEFAULT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `admin_note` VARCHAR(500) DEFAULT NULL,
            `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `reviewed_at` DATETIME DEFAULT NULL,
            `reviewed_by` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_seller_status` (`seller_id`, `status`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `seller_deletion_requests`");
    }
}
