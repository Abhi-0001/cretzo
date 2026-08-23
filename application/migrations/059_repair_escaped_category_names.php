<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Unwinds the escape_array() residue in `categories`.`name`.
 *
 * Category_model::add_category() ran escape_array() over the posted data and then wrote it
 * through CodeIgniter's query builder, which escapes again - so every save added a layer of
 * backslashes, and because the edit form is repopulated from the stored value, re-editing the
 * same category doubled the damage:
 *
 *     Men's Wear  ->  Men\'s Wear  ->  Men\\\'s Wear  ->  ...
 *
 * It surfaced in the seller profile's secondary-category picker ("Men/s Wear"), which prints
 * the name through htmlspecialchars() alone. The admin category list masked it, because that
 * read path runs output_escaping() - really stripcslashes() - and peels exactly one layer back
 * off. The write path is fixed; this repairs what is already stored.
 *
 * Deliberately NOT a blanket stripcslashes(): that interprets every C escape, so a name
 * containing "C:\temp" would come back as "C:<tab>emp". Only the three sequences escape_str()
 * actually produces are unwound - \' \" \\ - and repeatedly, to undo however many layers a
 * given row accumulated. Same approach as migration 054 took for the settings/FAQ blobs.
 *
 * Slugs are left alone: create_unique_slug() strips punctuation, so a slug generated from
 * "Men\'s Wear" is the same "mens-wear" it would have been from the clean name.
 */
class Migration_repair_escaped_category_names extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('categories')) {
            return;
        }

        // Filtered in PHP rather than with a LIKE. A backslash is BOTH the string escape and
        // the default LIKE escape in MySQL, so how many backslashes a pattern needs depends on
        // which layer you count from - the first version of this migration used
        // ->like('name', '\\\\', 'both', false) and silently matched nothing at all.
        // strpos() has no such ambiguity.
        $rows = $this->db->select('id, name')->get('categories')->result_array();

        $examined = 0;
        $repaired = 0;

        foreach ($rows as $row) {
            if (strpos((string) $row['name'], '\\') === false) {
                continue;
            }

            $examined++;
            $clean = $this->unescape($row['name']);

            if ($clean === $row['name']) {
                continue;
            }

            $this->db->set('name', $clean)->where('id', $row['id'])->update('categories');
            $repaired++;

            log_message(
                'error',
                'migration 059: repaired category ' . $row['id'] . ' name "' . $row['name'] . '" -> "' . $clean . '"'
            );
        }

        log_message('error', 'migration 059: ' . $examined . ' category name(s) contained a backslash, repaired ' . $repaired . '.');
    }

    /**
     * Strip the escaping escape_str() adds, however many times it was applied.
     */
    private function unescape($value)
    {
        $previous = null;

        // Bounded so a pathological value can never spin: 10 rounds is far more than the
        // handful of saves any of these rows has seen.
        for ($i = 0; $i < 10 && $value !== $previous; $i++) {
            $previous = $value;
            $value = str_replace(["\\'", '\\"', '\\\\'], ["'", '"', '\\'], $value);
        }

        return $value;
    }

    public function down()
    {
        // Nothing to undo - re-adding backslashes to a name would only restore the bug.
    }
}
