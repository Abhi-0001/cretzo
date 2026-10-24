<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * `seller_profile_change_requests` - major profile edits waiting for an admin's decision.
 *
 * Once a seller has been approved, changing a "major" business or personal detail (legal name,
 * PAN/GST, bank account, identity documents ... - the list lives in
 * Seller_profile_change_model::MAJOR_FIELDS) no longer writes straight to seller_data. The new
 * values are parked here and the live profile keeps the approved ones until an admin approves.
 *
 *   changes  JSON  { field: { "label": "...", "old": "...", "new": "..." } }
 *                  for document fields old/new are stored file paths.
 *   status   pending | approved | rejected
 *
 * A seller has at most one pending request: saving again replaces it, and putting every field
 * back to its approved value withdraws it.
 */
class Migration_seller_profile_change_requests extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `seller_profile_change_requests` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `seller_id` INT(11) NOT NULL,
            `changes` MEDIUMTEXT NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `reject_reason` VARCHAR(500) DEFAULT NULL,
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
        $this->db->query("DROP TABLE IF EXISTS `seller_profile_change_requests`");
    }
}
