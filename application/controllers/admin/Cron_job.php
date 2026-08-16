<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cron_job extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['Seller_model', 'Promo_code_model']);
    }

    public function settle_seller_commission()
    {
        // This controller had no authentication check on any method - confirmed live with no
        // session cookie at all: the request succeeded and returned a normal application
        // response rather than being rejected. Both endpoints here move real money (crediting
        // seller commissions / settling promo code discounts) and are triggered from an
        // authenticated admin button (the "Settle Promo Code Discount" button on the Orders
        // page), so - as with every other admin controller in this codebase - they should only
        // ever run for a logged-in administrator.
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        $this->form_validation->set_data($this->input->get());
        $this->form_validation->set_rules('is_date', 'is_date', 'trim|required|xss_clean');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
        } else {
            $is_date = (isset($_GET['is_date']) && is_numeric($_GET['is_date']) && !empty(trim($_GET['is_date']))) ? $this->input->get('is_date') : false;
            return $this->Seller_model->settle_seller_commission($is_date);
        }
    }
    // Expiry is otherwise only evaluated lazily at read time (get_active_subscription()
    // checks end_date on every read, so nothing depends on is_active being accurate
    // between reads) - this endpoint exists only so `is_active` itself doesn't go
    // stale indefinitely, and so a future report/notification pass has an accurate
    // "expired today" signal to work from. Token-protected rather than login-gated
    // (like every other method in this controller) since an external OS cron can't
    // hold an admin session - set application/config/cron.php's `secret` before
    // wiring this into an actual scheduled job.
    /**
     * Shared gate for the token-protected cron endpoints. Returns TRUE when the caller
     * presented the configured secret, otherwise emits the 401 body and returns FALSE.
     * A logged-in admin is also allowed through so these can be triggered by hand from
     * the browser while testing, without exposing the secret in a URL bar.
     */
    private function cron_authorized($token = null)
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return true;
        }

        $this->config->load('cron', true);
        $expected = $this->config->item('secret', 'cron');
        $token = $token !== null ? $token : $this->input->get('token');

        if (empty($expected) || $expected === 'change-me-before-use' || empty($token) || !hash_equals((string) $expected, (string) $token)) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        return true;
    }

    public function expire_seller_subscriptions($token = null)
    {
        if (!$this->cron_authorized($token)) {
            return false;
        }

        // Compare against today's DATE, not a full timestamp. end_date is a DATE column, so
        // a subscription ending today reads as midnight today and `end_date < NOW()` was
        // true from 00:00:01 onwards - this sweep therefore deactivated subscriptions on
        // their final valid day, while get_active_subscription() (`end_date >= today`)
        // still counted them as active. The two disagreed by a day, and because the sweep
        // also clears is_active the cron's answer won: sellers lost their last day.
        // Whose plans are about to be flagged - read before the update, since afterwards
        // they are indistinguishable from every other inactive row.
        $lapsed = $this->db
            ->select('DISTINCT seller_id', false)
            ->where('is_active', 1)
            ->where('end_date IS NOT NULL', null, false)
            ->where('end_date <', date('Y-m-d'))
            ->get('seller_subscriptions')
            ->result_array();

        $this->db
            ->set('is_active', 0)
            ->where('is_active', 1)
            ->where('end_date IS NOT NULL', null, false)
            ->where('end_date <', date('Y-m-d'))
            ->update('seller_subscriptions');

        $affected = $this->db->affected_rows();

        // Expiry drops a seller to the free tier rather than leaving them with no plan.
        // The seller panel applies this lazily too, so this only brings the data forward
        // for sellers who haven't logged in - admin reports and the API then see the same
        // free-tier state the seller would.
        $this->load->model('Seller_subscription_model');
        $moved = 0;
        $hidden = 0;
        foreach ($lapsed as $row) {
            if (!empty($this->Seller_subscription_model->ensure_free_tier_fallback($row['seller_id']))) {
                $moved++;
            }
            // Whether or not the plan changed, the shop has to respect the current cap:
            // anything the seller listed beyond it stops being visible to buyers here.
            $visibility = $this->Seller_subscription_model->enforce_listing_visibility($row['seller_id']);
            $hidden += (int) $visibility['changed'];
        }

        $this->response['error'] = false;
        $this->response['message'] = 'Expired subscriptions flagged.';
        $this->response['data'] = ['affected_rows' => $affected, 'moved_to_free_tier' => $moved, 'listings_visibility_changed' => $hidden];
        echo json_encode($this->response);
        return false;
    }

    /**
     * Emails sellers whose subscription is about to lapse (at the thresholds configured in
     * config/cron.php) and once more on the day it lapses. Previously nothing warned a
     * seller at all - the first they knew of an expiry was being refused when they tried
     * to add a product.
     *
     * Idempotent: each (subscription period, threshold) pair is recorded in
     * seller_subscription_reminders under a UNIQUE key, and the insert is attempted BEFORE
     * the mail is sent, so a duplicate cron run (or two overlapping ones) cannot double-send.
     */
    public function subscription_expiry_reminders($token = null)
    {
        if (!$this->cron_authorized($token)) {
            return false;
        }

        $this->config->load('cron', true);
        $thresholds = $this->config->item('expiry_reminder_days', 'cron');
        if (empty($thresholds) || !is_array($thresholds)) {
            $thresholds = [7, 3, 1];
        }
        // 0 = the "expired today" notice, always included.
        $thresholds[] = 0;
        $thresholds = array_unique(array_map('intval', $thresholds));

        $settings  = get_settings('system_settings', true);
        $app_name  = isset($settings['app_name']) ? $settings['app_name'] : 'Your store';
        $today     = date('Y-m-d');
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($thresholds as $days) {
            $target_date = date('Y-m-d', strtotime('+' . (int) $days . ' days', strtotime($today)));

            $rows = $this->db
                ->select('ss.id AS sub_id, ss.seller_id, ss.end_date, s.name AS plan_name, u.email, u.username, sd.shop_name')
                ->join('subscriptions s', 's.id = ss.subscription_id', 'left')
                ->join('users u', 'u.id = ss.seller_id')
                ->join('seller_data sd', 'sd.user_id = ss.seller_id', 'left')
                ->where('ss.is_active', 1)
                ->where('ss.end_date', $target_date)
                ->get('seller_subscriptions ss')
                ->result_array();

            foreach ($rows as $row) {
                if (empty($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                // Claim the slot first. The UNIQUE key makes a second attempt fail, which is
                // what makes this safe to re-run rather than a check-then-send race.
                $this->db->db_debug = false;
                $claimed = $this->db->insert('seller_subscription_reminders', [
                    'seller_subscription_id' => $row['sub_id'],
                    'seller_id'              => $row['seller_id'],
                    'threshold_days'         => $days,
                    'sent_at'                => date('Y-m-d H:i:s'),
                ]);
                $this->db->db_debug = true;

                if (!$claimed) {
                    $skipped++; // already sent for this period/threshold
                    continue;
                }

                $name = !empty($row['shop_name']) ? $row['shop_name'] : $row['username'];
                $plan = !empty($row['plan_name']) ? $row['plan_name'] : 'your plan';
                $when = date('d M Y', strtotime($row['end_date']));

                if ($days === 0) {
                    $subject = 'Your ' . $app_name . ' subscription has expired';
                    $body = 'Hi ' . $name . ",\n\n"
                        . 'Your ' . $plan . ' subscription expired on ' . $when . ".\n\n"
                        . "Your existing products remain live, but you cannot add new listings until you renew.\n\n"
                        . 'Renew here: ' . base_url('seller/subscription') . "\n\n"
                        . $app_name;
                } else {
                    $subject = 'Your ' . $app_name . ' subscription expires in ' . $days . ' day' . ($days === 1 ? '' : 's');
                    $body = 'Hi ' . $name . ",\n\n"
                        . 'Your ' . $plan . ' subscription expires on ' . $when . ' (' . $days . ' day' . ($days === 1 ? '' : 's') . " from now).\n\n"
                        . "Renew before then to keep adding new listings without interruption.\n\n"
                        . 'Renew here: ' . base_url('seller/subscription') . "\n\n"
                        . $app_name;
                }

                $result = send_mail($row['email'], $subject, $body);
                if (!empty($result['error'])) {
                    // Release the claim so the next run can retry a genuinely failed send.
                    $this->db->where('seller_subscription_id', $row['sub_id'])
                             ->where('threshold_days', $days)
                             ->delete('seller_subscription_reminders');
                    $failed++;
                    continue;
                }

                $sent++;
            }
        }

        $this->response['error'] = false;
        $this->response['message'] = 'Subscription expiry reminders processed.';
        $this->response['data'] = ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
        echo json_encode($this->response);
        return false;
    }

    /**
     * Release stock held by orders that were created but never paid for.
     *
     * Token-protected like the other scheduled endpoints, since an external cron cannot hold
     * an admin session. The window comes from config/cron.php (abandoned_order_hours) and can
     * be overridden per-call with ?hours= for a one-off sweep.
     */
    public function expire_abandoned_orders($token = null)
    {
        if (!$this->cron_authorized($token)) {
            return false;
        }

        $this->config->load('cron', true);
        $hours = $this->input->get('hours');
        if (!is_numeric($hours) || $hours < 1) {
            $hours = $this->config->item('abandoned_order_hours', 'cron');
        }
        if (!is_numeric($hours) || $hours < 1) {
            $hours = 48;
        }

        $this->load->model('Order_model');
        $result = $this->Order_model->expire_abandoned_orders((int) $hours);

        $this->response['error'] = false;
        $this->response['message'] = 'Abandoned unpaid orders released.';
        $this->response['data'] = array_merge($result, ['window_hours' => (int) $hours]);
        echo json_encode($this->response);
        return false;
    }

    /**
     * Email sellers about products that have fallen to or below the low-stock threshold.
     *
     * Nothing warned anyone before: the threshold only drove a dashboard tile and a list
     * filter, so a seller had to go looking. Each (variant, level) pair is claimed before the
     * mail is sent, so re-running does not re-notify; a further drop counts as a new
     * condition, and recovering above the threshold clears the claim.
     */
    public function low_stock_alerts($token = null)
    {
        if (!$this->cron_authorized($token)) {
            return false;
        }

        $this->load->model('Product_model');
        $settings = get_settings('system_settings', true);
        $limit = isset($settings['low_stock_limit']) ? (int) $settings['low_stock_limit'] : 5;
        $app_name = isset($settings['app_name']) ? $settings['app_name'] : 'Your store';

        $items = $this->Product_model->get_low_stock_items($limit);

        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $by_seller = [];

        foreach ($items as $item) {
            if (!$this->Product_model->claim_low_stock_alert($item['product_id'], $item['product_variant_id'], (int) $item['stock'])) {
                $skipped++; // already told them about this level
                continue;
            }
            $by_seller[$item['seller_id']][] = $item;
        }

        // Products that have recovered can alert again next time they fall.
        $this->Product_model->clear_recovered_low_stock_alerts($limit);

        foreach ($by_seller as $seller_id => $seller_items) {
            $seller = fetch_details('users', ['id' => $seller_id], 'username,email');
            if (empty($seller) || empty($seller[0]['email']) || !filter_var($seller[0]['email'], FILTER_VALIDATE_EMAIL)) {
                $failed += count($seller_items);
                continue;
            }

            // Lowest first, so the most urgent items are the ones that survive the cap.
            usort($seller_items, function ($a, $b) {
                return (int) $a['stock'] <=> (int) $b['stock'];
            });

            // A seller with a large catalogue can easily have hundreds of items under the
            // threshold; listing them all produces a wall of text nobody reads. Show the
            // worst offenders and point at the full list.
            $max_listed = 25;
            $lines = '';
            foreach (array_slice($seller_items, 0, $max_listed) as $row) {
                $lines .= '  - ' . $row['product_name']
                    . (!empty($row['variant_name']) ? ' (' . $row['variant_name'] . ')' : '')
                    . ': ' . (int) $row['stock'] . " left\n";
            }
            $remaining = count($seller_items) - $max_listed;
            if ($remaining > 0) {
                $lines .= '  ... and ' . $remaining . " more\n";
            }

            $subject = count($seller_items) . ' product(s) running low on stock';
            $body = 'Hi ' . $seller[0]['username'] . ",\n\n"
                . "These items are at or below your low-stock threshold of " . $limit . ":\n\n"
                . $lines . "\n"
                . "Restock them here: " . base_url('seller/manage-stock') . "\n\n"
                . $app_name;

            $result = send_mail($seller[0]['email'], $subject, $body);
            if (!empty($result['error'])) {
                // Release the claims so a genuinely failed send is retried next run.
                foreach ($seller_items as $row) {
                    $this->Product_model->release_low_stock_alert($row['product_id'], $row['product_variant_id']);
                }
                $failed += count($seller_items);
                continue;
            }
            $sent += count($seller_items);
        }

        $this->response['error'] = false;
        $this->response['message'] = 'Low stock alerts processed.';
        $this->response['data'] = ['low_items' => count($items), 'alerted' => $sent, 'already_alerted' => $skipped, 'failed' => $failed, 'threshold' => $limit];
        echo json_encode($this->response);
        return false;
    }

    public function settle_cashback_discount()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        return $this->Promo_code_model->settle_cashback_discount();
    }

    // reset_system_data() and reset_system_media() were REMOVED here. They were
    // demo-reset scaffolding inherited from the upstream eShop vendor package, and
    // both were reachable with NO authentication at all (unlike every other method
    // in this controller):
    //   - reset_system_data() opened a hardcoded mysqli('localhost','root','','eshop_vendor')
    //     connection and multi_query()'d an eshop_vendor.sql fetched over HTTP - i.e. a
    //     full database wipe/reimport, pointed at a database this install does not use.
    //   - reset_system_media() ran delete_files(FCPATH.'uploads/media', true) - deleting
    //     EVERY uploaded product/seller image - and then tried to restore them from an
    //     uploads/media.zip that does not exist in this repo, so the deletion was
    //     unrecoverable.
    // Neither was referenced by any controller, view, JS file or route (verified by
    // grep across application/ and assets/), and neither's required file
    // (eshop_vendor.sql, uploads/media.zip) exists here. Deleting them outright is
    // safer than adding an auth gate, because even an authenticated call would
    // irreversibly destroy all media on this install.
}
