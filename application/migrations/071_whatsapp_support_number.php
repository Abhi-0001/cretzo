<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Puts the confirmed support number back into `system_settings.whatsapp_number` and switches
 * WhatsApp support on.
 *
 * Migration 052 settled which number is correct, but it only rewrote fields that held the
 * *wrong* number - and by then `whatsapp_number` was not wrong, it was EMPTY. The settings form
 * blanks the number whenever it is saved with the "Whatsapp" toggle off (that wipe is now
 * removed in Setting_model), so one such save left the field empty and the toggle at 0. Every
 * WhatsApp support button on the site reads those two fields, so all of them disappeared and the
 * chat pages read "WhatsApp support is currently unavailable".
 *
 * Deliberately narrow, the same way 052 was: it only fills a field that is empty or holds the
 * known-wrong number, so an admin who has since set a different number on purpose keeps it, and
 * re-running the migration does nothing.
 */
class Migration_whatsapp_support_number extends CI_Migration
{
    /** The number the owner confirmed on 2026-08-20. */
    const CORRECT = '7290024349';

    /** The transposed value migration 052 was written to clean up. */
    const WRONG = '7290024359';

    public function up()
    {
        $row = $this->db->select('value')->where('variable', 'system_settings')->get('settings')->row_array();
        if (empty($row['value'])) {
            return;
        }

        $settings = json_decode($row['value'], true);
        if (!is_array($settings)) {
            log_message('error', 'Migration 071: system_settings is not decodable JSON - left untouched.');
            return;
        }

        $changed = false;

        $digits = isset($settings['whatsapp_number']) ? preg_replace('/\D+/', '', (string) $settings['whatsapp_number']) : '';
        if ($digits === '' || $digits === self::WRONG || $digits === '91' . self::WRONG) {
            $settings['whatsapp_number'] = self::CORRECT;
            $changed = true;
        }

        // With a number now present, the toggle being off is the leftover of the save that wiped
        // the number rather than a decision to hide support, so turn it on.
        if (empty($settings['whatsapp_status'])) {
            $settings['whatsapp_status'] = '1';
            $changed = true;
        }

        if ($changed) {
            settings_write_done($this->db->set('value', json_encode($settings))->where('variable', 'system_settings')->update('settings'));
        }
    }

    public function down()
    {
        // Taking the support channel away again is not a state worth being able to return to.
    }
}
