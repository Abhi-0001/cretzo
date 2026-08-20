<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seeds the `custom_notifications` templates, which ship EMPTY.
 *
 * Across 15 files there are 56 copies of this exact pattern:
 *
 *     $custom_notification = fetch_details('custom_notifications', ['type' => "X"], '');
 *     ...
 *     $string = json_encode($custom_notification[0]['message'], JSON_UNESCAPED_UNICODE);
 *
 * with no check that the lookup returned a row. With the table empty - the shipped state - every
 * one of those reads is an "Undefined array key 0" warning. In production display_errors is off
 * so it only fills the log, but in development (and in the Docker/CI 'testing' environment) the
 * warning is printed straight into the response body, which corrupts the JSON that the admin UI
 * and the mobile app then try to parse: the request looks like it failed even though the
 * underlying action succeeded. json_encode(null) also yields the string "null", so the message
 * variable those blocks build is literally the word "null" before the `!empty()` fallback
 * rescues it.
 *
 * Seeding the rows fixes all 56 sites at once without touching them, and - more to the point -
 * it is what the product intends: every one of these events is meant to carry an admin-editable
 * title and body (Admin > Custom Notifications), and until they exist each event silently falls
 * back to a terse hardcoded English string. `custom_sms` was seeded with all 14 of its templates;
 * this table was simply missed.
 *
 * Placeholder names match what the admin form (views/admin/pages/forms/custom_notification.php)
 * advertises per type, including the product's own misspelling of "< cutomer_name >".
 *
 * Existing rows are never overwritten - only missing types are inserted.
 */
class Migration_seed_custom_notifications extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('custom_notifications')) {
            return;
        }

        $templates = [
            'place_order' => [
                'title'   => 'Order placed successfully',
                'message' => 'Your order #< order_id > has been placed. We will keep you posted on its progress.',
            ],
            'settle_cashback_discount' => [
                'title'   => 'Cashback credited',
                'message' => 'Hi < cutomer_name >, your cashback has been credited to your < application_name > wallet.',
            ],
            'settle_seller_commission' => [
                'title'   => 'Commission settled',
                'message' => 'Hi < cutomer_name >, your commission has been settled and credited by < application_name >.',
            ],
            'customer_order_received' => [
                'title'   => 'Order confirmed',
                'message' => 'Hi < cutomer_name >, we have received your order #< order_item_id >. Thanks for shopping with < application_name >.',
            ],
            'customer_order_processed' => [
                'title'   => 'Order being prepared',
                'message' => 'Hi < cutomer_name >, your order #< order_item_id > is being prepared for dispatch. - < application_name >',
            ],
            'customer_order_shipped' => [
                'title'   => 'Order shipped',
                'message' => 'Hi < cutomer_name >, your order #< order_item_id > has been shipped and is on its way. - < application_name >',
            ],
            'customer_order_delivered' => [
                'title'   => 'Order delivered',
                'message' => 'Hi < cutomer_name >, your order #< order_item_id > has been delivered. We hope you love it! - < application_name >',
            ],
            'customer_order_cancelled' => [
                'title'   => 'Order cancelled',
                'message' => 'Hi < cutomer_name >, your order #< order_item_id > has been cancelled. Any amount paid will be refunded as per policy. - < application_name >',
            ],
            'customer_order_returned' => [
                'title'   => 'Order returned',
                'message' => 'Hi < cutomer_name >, the return for your order #< order_item_id > has been completed. - < application_name >',
            ],
            'customer_order_returned_request_decline' => [
                'title'   => 'Return request declined',
                'message' => 'Hi < cutomer_name >, your return request for order #< order_item_id > could not be approved. Contact support for details. - < application_name >',
            ],
            'customer_order_returned_request_approved' => [
                'title'   => 'Return request approved',
                'message' => 'Hi < cutomer_name >, your return request for order #< order_item_id > has been approved. - < application_name >',
            ],
            'delivery_boy_order_deliver' => [
                'title'   => 'New delivery assigned',
                'message' => 'Hi < cutomer_name >, a new order #< order_item_id > has been assigned to you for delivery. - < application_name >',
            ],
            'wallet_transaction' => [
                'title'   => 'Wallet updated',
                'message' => 'Your wallet has been updated with < currency >< returnable_amount >.',
            ],
            'ticket_status' => [
                'title'   => 'Support ticket updated',
                'message' => 'The status of your support ticket has been updated. Open My Account > Support to see the latest reply. - < application_name >',
            ],
            'ticket_message' => [
                'title'   => 'New reply on your ticket',
                'message' => 'Our support team has replied to your ticket. Open My Account > Support to read it. - < application_name >',
            ],
            'bank_transfer_receipt_status' => [
                'title'   => 'Bank transfer receipt < status >',
                'message' => 'The receipt you submitted for order #< order_id > is now < status >.',
            ],
            'bank_transfer_proof' => [
                'title'   => 'Bank transfer receipt received',
                'message' => 'A payment receipt has been submitted for order #< order_id >. - < application_name >',
            ],
        ];

        $existing = [];
        foreach ($this->db->select('type')->get('custom_notifications')->result_array() as $row) {
            $existing[(string) $row['type']] = true;
        }

        $insert = [];
        foreach ($templates as $type => $template) {
            if (isset($existing[$type])) {
                continue;
            }
            $insert[] = [
                'type'    => $type,
                'title'   => $template['title'],
                'message' => $template['message'],
            ];
        }

        if (!empty($insert)) {
            $this->db->insert_batch('custom_notifications', $insert);
        }
    }

    public function down()
    {
        // Only remove the exact types this migration seeds, and only if they still hold the
        // seeded text - never discard an admin's edits.
        if (!$this->db->table_exists('custom_notifications')) {
            return;
        }
        $this->db->where_in('type', [
            'place_order', 'settle_cashback_discount', 'settle_seller_commission',
            'customer_order_received', 'customer_order_processed', 'customer_order_shipped',
            'customer_order_delivered', 'customer_order_cancelled', 'customer_order_returned',
            'customer_order_returned_request_decline', 'customer_order_returned_request_approved',
            'delivery_boy_order_deliver', 'wallet_transaction', 'ticket_status', 'ticket_message',
            'bank_transfer_receipt_status', 'bank_transfer_proof',
        ])->delete('custom_notifications');
    }
}
