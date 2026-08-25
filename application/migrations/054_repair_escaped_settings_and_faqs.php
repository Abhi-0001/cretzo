<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Removes the leftover backslash escaping from text that was saved through the double-escaping
 * bug in Faq_model::add_faq() and Setting_model::update_web_setting().
 *
 * Both wrote user-entered text through escape_array() first - which runs db->escape_str() over
 * every value - and then handed the result to the query builder, which escapes it a SECOND time.
 * Each save therefore added another layer of backslashes to the same text, and because both edit
 * forms are populated from the stored value, editing the same record repeatedly doubled the
 * damage each time. Reproduced on this database before the write paths were fixed: an FAQ answer
 * containing "it's" became "it\'s", then "it\\\'s", then "it\\\\\\\'s" over three consecutive
 * saves of otherwise unchanged text.
 *
 * The write paths are fixed now, so nothing new gets escaped, but the text already in the
 * database is still carrying whatever layers it accumulated. That text is customer-facing: the
 * FAQ answers are the /home/faq page and the mobile app's get_faqs endpoint, and
 * web_settings.app_short_description is the paragraph in the storefront footer, which was
 * rendering "handmade is more than a product-it\'s an expression" on every page of the site.
 *
 * What this does, per affected field:
 *   - repeatedly strips one layer of escaping while the value still looks escaped, so records
 *     that were saved several times come back as well as records saved once;
 *   - only touches a value that actually shows the escaping signature (a backslash before a
 *     quote, another backslash, or an r/n/t), so text that was always clean is left untouched
 *     and re-running the migration is a no-op.
 *
 * Deliberately NOT a blanket stripcslashes(): that also interprets \t, \n and \x41 as control
 * characters, which would corrupt a legitimate answer mentioning a Windows path like C:\temp.
 * Only the sequences db->escape_str() actually produces are unwound.
 */
class Migration_repair_escaped_settings_and_faqs extends CI_Migration
{
    /** Fields inside the web_settings JSON blob that hold admin-entered prose. */
    private $web_settings_fields = [
        'site_title',
        'copyright_details',
        'address',
        'app_short_description',
        'map_iframe',
        'meta_keywords',
        'meta_description',
        'app_download_section_title',
        'app_download_section_tagline',
        'app_download_section_short_description',
        'shipping_title',
        'shipping_description',
        'return_title',
        'return_description',
        'support_title',
        'support_description',
        'safety_security_title',
        'safety_security_description',
    ];

    /** Same, for the system_settings blob. */
    private $system_settings_fields = [
        'app_name',
        'tax_name',
        'tax_number',
        'company_name',
        'message_for_customer_app',
        'message_for_seller_app',
        'message_for_delivery_boy_app',
        'message_for_web',
    ];

    public function up()
    {
        $this->repair_faqs();
        $this->repair_settings_blob('web_settings', $this->web_settings_fields);
        $this->repair_settings_blob('system_settings', $this->system_settings_fields);
    }

    public function down()
    {
        // Re-inserting the stray backslashes is not a state worth being able to return to.
    }

    private function repair_faqs()
    {
        if (!$this->db->table_exists('faqs')) {
            return;
        }

        $rows = $this->db->select('id, question, answer')->get('faqs')->result_array();
        foreach ($rows as $row) {
            $update = [];
            foreach (['question', 'answer'] as $field) {
                $clean = $this->unescape($row[$field]);
                if ($clean !== $row[$field]) {
                    $update[$field] = $clean;
                }
            }
            if (!empty($update)) {
                $this->db->where('id', $row['id'])->update('faqs', $update);
            }
        }
    }

    private function repair_settings_blob($variable, array $fields)
    {
        $row = $this->db->select('value')->where('variable', $variable)->get('settings')->row_array();
        if (empty($row)) {
            return;
        }

        $data = json_decode($row['value'], true);
        if (!is_array($data)) {
            return;
        }

        $changed = false;
        foreach ($fields as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                continue;
            }
            $clean = $this->unescape($data[$field]);
            if ($clean !== $data[$field]) {
                $data[$field] = $clean;
                $changed = true;
            }
        }

        if ($changed) {
            settings_write_done($this->db->where('variable', $variable)->update('settings', ['value' => json_encode($data)]));
        }
    }

    /**
     * Peels off however many layers of db->escape_str() escaping a value picked up.
     *
     * Each pass undoes exactly the substitutions escape_str() makes - a backslash before a
     * single quote, a double quote, or another backslash - plus the \r and \n that MySQL's
     * escaping produces for real newlines, so a multi-line address comes back as actual line
     * breaks instead of the literal text "\r\n". It stops as soon as a pass changes nothing, and
     * returns the input untouched when there was no escaping to begin with.
     *
     * The loop is capped so a value that somehow always "looks escaped" cannot spin forever.
     */
    private function unescape($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        for ($pass = 0; $pass < 12; $pass++) {
            // Only sequences escape_str() itself emits. A lone backslash in front of anything
            // else (C:\temp, a regex, a Windows share) is left exactly as the admin typed it.
            //
            // Line breaks are only unwound as the PAIR \r\n. Every one of these fields is
            // filled from a browser textarea, so a real line break always reaches the database
            // as CRLF and always comes back as that pair; matching a lone \r or \n as well
            // would mean rewriting "C:\reports" to "C:<CR>eports" for the sake of a case that
            // does not occur here.
            $next = preg_replace_callback(
                '/\\\\r\\\\n|\\\\([\'"\\\\])/',
                function ($m) {
                    return isset($m[1]) && $m[1] !== '' ? $m[1] : "\r\n";
                },
                $value
            );

            if ($next === null || $next === $value) {
                return $value;
            }
            $value = $next;
        }

        return $value;
    }
}
