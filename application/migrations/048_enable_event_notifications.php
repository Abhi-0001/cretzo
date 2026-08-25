<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Turns on the event notification matrix that has never been configured.
 *
 * `notify_event()` (application/helpers/sms_helper.php) is the single dispatcher for every
 * transactional email and SMS on the platform - order placed, received, processed, shipped,
 * delivered, cancelled, returned, return request approved/declined, wallet transaction, seller
 * commission settlement, bank transfer receipt status. Its very first act is:
 *
 *     $send_notification_settings = get_settings('send_notification_settings', true);
 *     if (!isset($send_notification_settings[$event])) { return ["error" => true, ...]; }
 *
 * The `send_notification_settings` row exists but its value is an EMPTY STRING, because the
 * on/off matrix under Admin > SMS Gateway Settings > Notification Modules has to be saved once
 * by hand before it is populated. Until that happens the condition above is false for every
 * event, so notify_event() returned early every single time and NOT ONE transactional email or
 * SMS was ever sent - silently, with nothing logged. All 14 message templates in `custom_sms`
 * were authored and sitting unused.
 *
 * This seeds the matrix so the feature works out of the box:
 *
 *   - notification_via_mail is ON. Email is configured (settings.email_settings holds real SMTP
 *     credentials) and the templates exist, so this is the channel that can actually deliver.
 *   - notification_via_sms is OFF. `sms_gateway_settings` is an empty object `{}` and
 *     `sms_gateway_method` is blank - no gateway is configured, so enabling SMS would only
 *     produce failed HTTP calls on the customer's request path. Switch it on in the admin screen
 *     after configuring a gateway.
 *   - Recipients follow $config['notification_modules'] in config/eshop.php, which declares which
 *     roles each event is allowed to reach. `admin` is deliberately left off except for
 *     bank_transfer_proof (where an admin has to act on a submitted receipt) so the support
 *     mailbox does not receive a copy of every wallet movement.
 *
 * Existing settings are respected: if a value is already saved, this migration leaves it alone.
 */
class Migration_enable_event_notifications extends CI_Migration
{
    public function up()
    {
        $existing = $this->db->get_where('settings', ['variable' => 'send_notification_settings'])->row_array();

        if (!empty($existing['value'])) {
            $decoded = json_decode($existing['value'], true);
            // A previously-saved, non-empty matrix is somebody's deliberate configuration.
            // (The literal string "null" is what the unguarded admin save wrote when every box
            // was unticked - treat that as unconfigured, not as a choice.)
            if (is_array($decoded) && !empty($decoded)) {
                return;
            }
        }

        // Note: load without the $use_sections flag and read the item unqualified - the rest of
        // this codebase (e.g. allowed_media_types()) reads eshop config the same way, and the
        // sectioned form returns nothing when the file is already loaded flat.
        $this->config->load('eshop', false, true);
        $modules = $this->config->item('notification_modules');

        if (empty($modules) || !is_array($modules)) {
            return;
        }

        // Only this event needs the admin in the loop by default.
        $admin_events = ['bank_transfer_proof'];

        $matrix = [];
        foreach ($modules as $event => $allowed_recipients) {
            $entry = ['notification_via_mail' => 'on'];

            foreach (['customer', 'seller', 'delivery_boy'] as $role) {
                if (in_array($role, $allowed_recipients, true)) {
                    $entry[$role] = 'on';
                }
            }

            if (in_array('admin', $allowed_recipients, true) && in_array($event, $admin_events, true)) {
                $entry['admin'] = 'on';
            }

            $matrix[$event] = $entry;
        }

        $value = json_encode($matrix);

        if (empty($existing)) {
            $this->db->insert('settings', [
                'variable' => 'send_notification_settings',
                'value'    => $value,
            ]);
            clear_settings_cache();
        } else {
            settings_write_done($this->db->set('value', $value)->where('variable', 'send_notification_settings')->update('settings'));
        }
    }

    public function down()
    {
        // Restoring "no notifications are ever sent" is not a state worth being able to return to,
        // and the admin screen can switch any of this off. Left as a no-op deliberately.
    }
}
