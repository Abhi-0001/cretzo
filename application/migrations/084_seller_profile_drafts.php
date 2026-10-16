<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * `seller_profile_drafts` - one auto-saved, half-filled profile form per seller.
 *
 * The seller profile is a five-step form. A reload, a closed tab or a dropped
 * connection used to throw away everything typed so far, because nothing was
 * stored until the final Submit. The form now posts its text answers here as
 * the seller types, and restores them (and the step they were on) the next time
 * the page opens - on any browser, not just the one they started in.
 *
 * `data` holds a JSON object of field name => value. Uploaded files are NOT part
 * of a draft (a browser cannot hand a chosen file back to a page after reload);
 * those are stored by the normal Submit. The row is deleted when the profile is
 * saved for real.
 */
class Migration_seller_profile_drafts extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `seller_profile_drafts` (
            `user_id` INT(11) NOT NULL,
            `step` VARCHAR(32) NOT NULL DEFAULT 'personal',
            `data` MEDIUMTEXT NOT NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `seller_profile_drafts`");
    }
}
