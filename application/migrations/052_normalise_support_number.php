<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Settles the support phone number on one value across every place it is stored.
 *
 * The store held two different numbers whose last two digits were transposed:
 *
 *   system_settings.support_number   7290024359
 *   system_settings.whatsapp_number  7290024359
 *   web_settings.support_number      7290024349   <- website footer
 *   contact_us page copy             7290024349
 *
 * So a customer reading the footer called one number and a customer pressing "WhatsApp Support"
 * reached a different one. The owner has confirmed 7290024349 is correct, so the settings holding
 * the other value are corrected to match.
 *
 * Deliberately narrow: it only rewrites a field that currently holds the KNOWN-WRONG value. If
 * somebody has since set a third number on purpose, this leaves it alone rather than overwriting
 * a deliberate choice, and re-running it does nothing.
 */
class Migration_normalise_support_number extends CI_Migration
{
    /** The number the owner confirmed. */
    const CORRECT = '7290024349';

    /** The transposed value that was in the settings rows. */
    const WRONG = '7290024359';

    public function up()
    {
        $this->fix_setting('system_settings', ['support_number', 'whatsapp_number']);
        $this->fix_setting('web_settings', ['support_number', 'contact_number', 'phone']);
    }

    public function down()
    {
        // Putting the wrong number back is not a state worth being able to return to.
    }

    /**
     * Rewrites the named keys inside a JSON settings row, but only where they currently hold the
     * wrong value. The row is JSON-encoded text, so it is decoded, edited and re-encoded rather
     * than string-replaced - a blind REPLACE() would also hit any other field that happened to
     * contain the same digits (an address line, a policy page, an order note).
     */
    private function fix_setting($variable, array $keys)
    {
        $row = $this->db->select('value')->where('variable', $variable)->get('settings')->row_array();
        if (empty($row['value'])) {
            return;
        }

        $decoded = json_decode($row['value'], true);
        if (!is_array($decoded)) {
            log_message('error', 'Migration 052: ' . $variable . ' is not decodable JSON - left untouched.');
            return;
        }

        $changed = false;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $decoded)) {
                continue;
            }
            // Compare on digits only, so a stored "+91 72900 24359" is still recognised.
            $digits = preg_replace('/\D+/', '', (string) $decoded[$key]);
            if ($digits === self::WRONG || $digits === '91' . self::WRONG) {
                $decoded[$key] = self::CORRECT;
                $changed = true;
            }
        }

        if ($changed) {
            settings_write_done($this->db->set('value', json_encode($decoded))->where('variable', $variable)->update('settings'));
        }
    }
}
