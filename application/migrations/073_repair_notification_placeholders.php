<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Repairs notification rows that were stored with their template placeholders unresolved.
 *
 * Both stores are cleaned: `notifications` (the customer / seller inbox) and
 * `system_notification` (the admin header bell and Manage System Notifications), which is where
 * the place_order admin copy lands.
 *
 * The custom_notifications templates are written with "< order_id >"-style placeholders, and
 * the place_order send paths substituted them into the TITLE but not into the MESSAGE - so
 * rows landed in `notifications` reading "Your order #< order_id > has been placed. We will
 * keep you posted on its progress." That text is what the customer and the admin list both
 * show, and nothing downstream can repair it once it is written. The send paths are fixed in
 * code; this cleans up what they already wrote.
 *
 * Where the row records which thing it was about (`type_id`), the real id is put back. Where
 * it does not, the placeholder is removed along with the '#' in front of it, so the sentence
 * reads "Your order has been placed." rather than showing raw template syntax.
 *
 * Re-running is harmless: a row with no "< token >" left in it is not touched.
 */
class Migration_repair_notification_placeholders extends CI_Migration
{
    public function up()
    {
        // Each store is guarded individually inside repair_table().
        $app_name = '';
        $settings = $this->db->select('value')->where('variable', 'system_settings')->get('settings')->row_array();
        if (!empty($settings['value'])) {
            $decoded = json_decode($settings['value'], true);
            if (is_array($decoded) && !empty($decoded['app_name'])) {
                $app_name = $decoded['app_name'];
            }
        }

        // Title width differs between the two stores (128 vs 256 - see migration 047), and
        // filling a placeholder can only ever shorten the text, so the cap is per-table to
        // avoid clipping a title that was already legitimately long.
        $this->repair_table('notifications', $app_name, 128);
        $this->repair_table('system_notification', $app_name, 256);
    }

    /**
     * @param string $table     Either notification store; both carry title/message/type_id.
     * @param string $app_name  Value for the < application_name > token.
     * @param int    $title_max  Width of that table's title column.
     */
    private function repair_table($table, $app_name, $title_max)
    {
        if (!$this->db->table_exists($table)) {
            return;
        }

        $rows = $this->db->select('id, title, message, type_id')
            ->group_start()
            ->like('title', '<', 'both')
            ->or_like('message', '<', 'both')
            ->group_end()
            ->get($table)
            ->result_array();

        foreach ($rows as $row) {
            $tokens = ['application_name' => $app_name];

            // type_id is the id of the thing the notification was about - the order, for the
            // order events. Only used when it is actually a number.
            if (isset($row['type_id']) && ctype_digit((string) $row['type_id']) && (int) $row['type_id'] > 0) {
                $tokens['order_id'] = (int) $row['type_id'];
                $tokens['order_item_id'] = (int) $row['type_id'];
            }

            $title = fill_notification_placeholders($row['title'], $tokens);
            $message = fill_notification_placeholders($row['message'], $tokens);

            if ($title === (string) $row['title'] && $message === (string) $row['message']) {
                continue;
            }

            $this->db->where('id', (int) $row['id'])->update($table, [
                'title'   => mb_substr($title, 0, $title_max),
                'message' => mb_substr($message, 0, 512),
            ]);
        }
    }

    public function down()
    {
        // Nothing to undo - the placeholders held no information that could be restored.
    }
}
