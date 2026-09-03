<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widens `settings`.`value` to utf8mb4 so 4-byte characters stop being
 * destroyed on save.
 *
 * The database and the `settings` table are both utf8mb4, but the `value`
 * COLUMN was left at `utf8_general_ci` - MySQL's 3-byte "utf8", which cannot
 * represent any character outside the BMP. A column charset overrides the
 * table default, so every emoji the admin typed into a settings field was
 * silently replaced with a literal `?` at INSERT time. MySQL does not warn;
 * the text just comes back wrong.
 *
 * Two rows show the damage today: `privacy_policy` (8 stray `?`) and
 * `seller_terms_conditions` (12), where a phone/mail/pin emoji preceded each
 * contact line and now reads as "???? support@cretzo.com".
 *
 * This migration stops the ongoing corruption. It CANNOT undo it - the original
 * bytes never reached the database, so there is nothing to recover. Those two
 * documents need the affected lines re-entered in the admin panel; that is
 * owner-authored legal copy, so it is deliberately not rewritten here.
 *
 * Widening a column charset is a non-destructive conversion: every byte that
 * fits in utf8 also fits in utf8mb4. The only real cost is index length, and
 * this column is not indexed.
 */
class Migration_settings_value_utf8mb4 extends CI_Migration
{
    public function up()
    {
        $column = $this->db->query("SHOW FULL COLUMNS FROM `settings` LIKE 'value'")->row_array();
        if (empty($column)) {
            return;
        }

        // Already wide enough - nothing to do (and re-running a CONVERT on a
        // mediumtext column of this size is not free).
        if (isset($column['Collation']) && strpos($column['Collation'], 'utf8mb4') === 0) {
            return;
        }

        $this->db->query("ALTER TABLE `settings`
            MODIFY `value` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
    }

    public function down()
    {
        // Deliberately not reverted. Narrowing the column back to utf8 would
        // re-introduce the data loss this migration exists to stop, and would
        // corrupt any 4-byte character saved in the meantime.
    }
}
