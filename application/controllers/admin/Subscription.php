<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper', 'file']);
        $this->load->model(['Subscription_model', 'Setting_model', 'Seller_subscription_model']);

        // permission checks can be added here later if necessary
    }

    // Admin visibility into per-seller subscriptions (plan, status, listing usage,
    // expiry) - previously admin had no way to see any of this outside opening the
    // seller-facing dashboard as that seller, and no way to assign/extend/cancel a
    // seller's subscription at all.
    public function seller_subscriptions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // is_admin() is true for EVERY system-user role (super_admin/admin/editor/
            // supporter are all in group 1), so it alone does not restrict anything.
            // These endpoints assign, extend and cancel real paid subscriptions, so they
            // need a granular permission too - 'subscription' is now a registered module
            // in config/eshop.php.
            if (!has_permissions('read', 'subscription')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            $this->data['main_page'] = TABLES . 'seller-subscriptions';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Seller Subscriptions | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Seller Subscriptions | ' . $settings['app_name'];
            $this->data['plans'] = $this->Subscription_model->get_plans();
            $this->data['launch_offer'] = $this->Seller_subscription_model->get_launch_offer_stats();

            // Counters for the "needs attention" strip, so the two silent data problems
            // (no plan assigned / term-limited plan with no expiry saved) are visible
            // without the admin having to read every row.
            $all_rows = $this->Seller_subscription_model->get_all_seller_subscription_status();
            $no_plan = 0;
            $missing_expiry = 0;
            foreach ($all_rows as $r) {
                if (!empty($r['no_plan'])) {
                    $no_plan++;
                }
                if (!empty($r['missing_expiry'])) {
                    $missing_expiry++;
                }
            }
            $this->data['attention'] = [
                'no_plan'        => $no_plan,
                'missing_expiry' => $missing_expiry,
                'total'          => count($all_rows),
            ];

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_seller_subscriptions()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('read', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $status_filter = $this->input->get('status_filter', true);

        $rows = $this->Seller_subscription_model->get_all_seller_subscription_status();
        $result = [];
        foreach ($rows as $row) {
            if ($status_filter === 'needs_attention') {
                // Rows an admin actually has to do something about: no plan assigned, or a
                // term-limited plan with no expiry recorded.
                if (empty($row['no_plan']) && empty($row['missing_expiry'])) {
                    continue;
                }
            } elseif (!empty($status_filter) && strcasecmp($row['status'], $status_filter) !== 0) {
                continue;
            }

            $row['shop_name'] = html_escape($row['shop_name']);
            $row['email']     = html_escape($row['email']);
            $row['mobile']    = html_escape($row['mobile']);
            $row['plan_name'] = html_escape($row['plan_name']);

            $badge = $row['status'] === 'Active' ? 'success' : ($row['status'] === 'Expired' ? 'danger' : 'secondary');
            $row['status'] = '<span class="badge badge-' . $badge . '">' . $row['status'] . '</span>';

            $row['plan_type'] = '<span class="badge badge-' . ($row['plan_type'] === 'Paid' ? 'info' : 'light') . '">' . $row['plan_type'] . '</span>';

            // "Not set" means the plan has a term but no end_date was ever written - a data
            // gap, not a lifetime subscription. Flag it so it reads as something to fix
            // rather than as a normal state.
            if ($row['expiry'] === 'Not set') {
                $row['expiry'] = '<span class="badge badge-warning" title="This plan has a validity period but no expiry date was saved. Run admin/migrate to backfill, or use Manage > Assign to restart the plan.">Not set</span>';
                $row['days_left'] = '<span class="text-warning font-weight-bold">Not set</span>';
            } elseif ($row['expiry'] === 'Never') {
                $row['expiry'] = '<span class="text-muted" title="This plan has no validity period configured, so it does not expire.">Never</span>';
            }

            // Usage reads as "12 / 50", flagged red once the seller is over the cap they
            // are currently entitled to (possible after an admin-side downgrade), and amber
            // when there is no plan at all - uncapped by omission rather than by design.
            $usage = $row['used'] . ' / ' . $row['limit'];
            if ($row['over_limit']) {
                $row['usage'] = '<span class="text-danger font-weight-bold">' . $usage . '</span>';
            } elseif (!empty($row['no_plan'])) {
                $row['usage'] = '<span class="text-warning" title="No subscription assigned, so nothing caps this seller\'s listings. Use Manage > Assign to put them on a plan.">' . $usage . '</span>';
            } else {
                $row['usage'] = $usage;
            }

            // Kept as a raw sortable value for the hidden "Last Payment Date" column - the
            // visible cell below is HTML, so it cannot be sorted on.
            $row['last_paid_on'] = html_escape((string) $row['last_paid_on']);

            $row['last_payment'] = ($row['last_payment'] === '' || $row['last_payment'] === null)
                ? '<span class="text-muted">-</span>'
                : html_escape($row['last_payment']) . '<br><small class="text-muted">' . $row['last_paid_on'] . '</small>';

            $row['operate'] = '<button type="button" class="btn btn-primary-theme btn-xs manage-subscription-btn" data-seller-id="' . $row['seller_id'] . '" data-shop-name="' . $row['shop_name'] . '"><i class="fa fa-cog"></i> Manage</button>';

            $result[] = $row;
        }

        echo json_encode(['total' => count($result), 'rows' => $result]);
    }

    /**
     * Per-seller subscription history + payment history, for the Manage modal. Admin
     * previously had no record of what a seller had been on before, or of what they had
     * actually paid - only their current plan.
     */
    public function seller_subscription_history()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('read', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $seller_id = $this->input->get('seller_id', true);
        if (empty($seller_id) || !is_numeric($seller_id)) {
            echo json_encode(['error' => true, 'message' => 'Invalid seller.']);
            return;
        }

        $history = $this->Seller_subscription_model->get_subscription_history((int) $seller_id);
        $payments = $this->Seller_subscription_model->get_subscription_payments((int) $seller_id, 20);
        $quota = $this->Seller_subscription_model->check_listing_quota((int) $seller_id, 0);

        foreach ($history as &$h) {
            $h['plan_name'] = html_escape((string) $h['plan_name']);
        }
        unset($h);
        foreach ($payments as &$p) {
            $p['txn_id'] = html_escape((string) $p['txn_id']);
            $p['type']   = html_escape((string) $p['type']);
        }
        unset($p);

        echo json_encode([
            'error'    => false,
            'history'  => $history,
            'payments' => $payments,
            'quota'    => $quota,
        ]);
    }

    /**
     * Admin view of one seller's visible listings, and the ability to change the selection.
     *
     * A plan's listings_limit caps how many of a seller's products the shop will show. When
     * a seller is over that cap the overflow is hidden from buyers, and normally the seller
     * chooses which listings keep the slots - this is the same screen for admin, for
     * supporting a seller (or overriding what they picked).
     */
    public function seller_listing_visibility()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('read', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $seller_id = (int) $this->input->get('seller_id', true);
        $seller = $this->db->select('u.id, u.username, sd.store_name')
            ->join('seller_data sd', 'sd.user_id = u.id', 'left')
            ->where('u.id', $seller_id)
            ->get('users u')->row_array();

        if (empty($seller)) {
            $this->session->set_flashdata('error', 'Seller not found.');
            redirect('admin/subscription/seller_subscriptions', 'refresh');
            return;
        }

        $settings = get_settings('system_settings', true);

        // Same order of operations as the seller's own page: settle any lapsed plan, then
        // re-apply the cap, so admin is looking at the state buyers are actually served.
        $this->Seller_subscription_model->ensure_free_tier_fallback($seller_id);
        $state = $this->Seller_subscription_model->enforce_listing_visibility($seller_id);

        $this->data['main_page'] = TABLES . 'seller-listing-visibility';
        $this->data['title'] = 'Visible Listings | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Visible Listings | ' . $settings['app_name'];
        $this->data['seller'] = $seller;
        $this->data['listing_state'] = $state;
        $this->data['current_plan'] = $this->Seller_subscription_model->get_current_plan($seller_id);
        $this->data['products'] = $this->db
            ->select('id, name, image, status, listing_visibility')
            ->where('seller_id', $seller_id)
            ->order_by('listing_visibility', 'ASC')
            ->order_by('id', 'DESC')
            ->get('products')->result_array();

        $this->load->view('admin/template', $this->data);
    }

    public function save_seller_listing_visibility()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('update', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }
        if (print_msg(!is_modification_allowed('update'), DEMO_VERSION_MSG, 'subscription', false)) {
            return false;
        }

        $seller_id = (int) $this->input->post('seller_id', true);
        if ($seller_id <= 0) {
            echo json_encode(['error' => true, 'message' => 'Invalid seller.']);
            return;
        }

        $visible_ids = $this->input->post('visible_ids');
        $visible_ids = is_array($visible_ids) ? $visible_ids : [];

        $result = $this->Seller_subscription_model->set_visible_listings($seller_id, $visible_ids);

        echo json_encode([
            'error'    => !$result['saved'],
            'message'  => $result['message'],
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
        ]);
    }

    public function assign_seller_subscription()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('update', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|integer|xss_clean');
        $this->form_validation->set_rules('subscription_id', 'Plan', 'trim|required|integer|xss_clean');
        if (!$this->form_validation->run()) {
            echo json_encode(['error' => true, 'message' => validation_errors()]);
            return;
        }

        $seller_id = $this->input->post('seller_id', true);
        $subscription_id = $this->input->post('subscription_id', true);
        $plan = $this->db->where('id', $subscription_id)->get('subscriptions')->row_array();
        if (empty($plan)) {
            echo json_encode(['error' => true, 'message' => 'Plan not found.']);
            return;
        }

        // The 100-vendor cap on the launch promotion was only enforced on the two paths a
        // seller can reach (sign-up auto-grant, and seller/Subscription::purchase(), which
        // refuses it outright). Assigning from this dropdown went straight through, so the
        // promo could be handed out to vendor 101+ and quietly inflate the count that
        // is_launch_offer_active() uses to decide whether to keep showing the banner.
        if (isset($plan['name']) && strcasecmp(trim($plan['name']), 'Launch Offer') === 0) {
            $stats = $this->Seller_subscription_model->get_launch_offer_stats();
            $already_on_plan = !empty($this->db->where('seller_id', $seller_id)->where('subscription_id', $subscription_id)->get('seller_subscriptions')->row_array());
            if (!$stats['active'] && !$already_on_plan) {
                echo json_encode(['error' => true, 'message' => 'The Launch Offer is limited to the first ' . $stats['cap'] . ' vendors and all slots have been claimed (' . $stats['claimed'] . '/' . $stats['cap'] . ').']);
                return;
            }
        }

        // Re-assigning the plan the seller is already actively on is a renewal, not a
        // switch: carry their unused days forward instead of discarding them.
        $success = $this->Seller_subscription_model->assign_subscription($seller_id, $subscription_id, isset($plan['validity']) ? $plan['validity'] : null, true);

        if (!$success) {
            echo json_encode(['error' => true, 'message' => 'Failed to assign plan.']);
            return;
        }

        // A downgrade does not delete products, so flag it rather than failing silently:
        // the seller keeps more live listings than their new plan allows and simply cannot
        // add any more until they are back under the cap.
        $quota = $this->Seller_subscription_model->check_listing_quota($seller_id, 0);
        $message = 'Plan assigned successfully.';
        if ($quota['limit'] !== null && $quota['used'] > $quota['limit']) {
            $message .= ' Note: this seller already has ' . $quota['used'] . ' live listings, which is over the new plan\'s limit of ' . $quota['limit'] . '. Existing products stay live, but no new ones can be added until they are under the limit.';
        }

        echo json_encode(['error' => false, 'message' => $message]);
    }

    public function extend_seller_subscription()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('update', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|integer|xss_clean');
        $this->form_validation->set_rules('days', 'Days', 'trim|required|integer|greater_than[0]|xss_clean');
        if (!$this->form_validation->run()) {
            echo json_encode(['error' => true, 'message' => validation_errors()]);
            return;
        }

        $success = $this->Seller_subscription_model->extend_subscription($this->input->post('seller_id', true), $this->input->post('days', true));
        echo json_encode(['error' => !$success, 'message' => $success ? 'Subscription extended successfully.' : 'No active, time-limited subscription to extend.']);
    }

    public function cancel_seller_subscription()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('admin/login', 'refresh');
        }
        if (print_msg(!has_permissions('delete', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|integer|xss_clean');
        if (!$this->form_validation->run()) {
            echo json_encode(['error' => true, 'message' => validation_errors()]);
            return;
        }

        $success = $this->Seller_subscription_model->deactivate_subscription($this->input->post('seller_id', true));
        echo json_encode(['error' => !$success, 'message' => $success ? 'Subscription cancelled successfully.' : 'No active subscription to cancel.']);
    }

    public function index()
    {
        redirect('admin/subscription/manage_subscriptions');
    }

    public function manage_subscriptions()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // is_admin() is group 1, which every system-user role (admin/editor/supporter)
            // belongs to - it gates nothing on its own. Plan CRUD sets the prices, listing
            // limits and commission rates the whole marketplace bills on, so it needs the
            // same 'subscription' module check the seller-subscription screens already use.
            if (!has_permissions('read', 'subscription')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'manage-subscriptions';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Subscription Plans | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Subscription Plans  | ' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('subscriptions', ['id' => $_GET['edit_id']]);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_subscription()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('read', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
                return false;
            }
            return $this->Subscription_model->get_list('subscriptions');
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_subscription()
{
    if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

        $is_edit = (bool) $this->input->post('edit_subscription');
        if (print_msg(!has_permissions($is_edit ? 'update' : 'create', 'subscription'), PERMISSION_ERROR_MSG, 'subscription')) {
            return false;
        }

        $this->form_validation->set_rules('name', 'Plan Name', 'trim|required|xss_clean');
        // price/validity only had 'xss_clean' - not even 'numeric' - so a plan could be saved
        // with a negative or non-numeric price, or a zero/negative validity period, with
        // nothing stopping it server-side beyond a client-side keypress filter that a direct
        // POST bypasses entirely.
        $this->form_validation->set_rules('price', 'Price', 'trim|required|numeric|greater_than_equal_to[0]|xss_clean');
        $this->form_validation->set_rules('listings_limit', 'Listings Limit', 'trim|xss_clean');
        $this->form_validation->set_rules('validity', 'Validity', 'trim|required|numeric|greater_than[0]|xss_clean');
        // 'required' added. These were optional, so a plan could be saved with the commission
        // fields left blank - they stored NULL, and the settlement engine casts NULL to 0.0,
        // meaning every seller on that plan was settled at 0% commission and the platform
        // earned nothing on their sales, silently. (The shipped "Launch Offer" plan is in
        // exactly this state.) A plan must now state its rate explicitly, including a
        // deliberate 0.
        $this->form_validation->set_rules('commission_first50', 'Commission (first 50 orders)', 'trim|required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]|xss_clean');
        $this->form_validation->set_rules('commission_51_100', 'Commission (51-100 orders)', 'trim|required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]|xss_clean');
        $this->form_validation->set_rules('commission_after100', 'Commission (after 100 orders)', 'trim|required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]|xss_clean');
        // Feature descriptions had no validation rule at all - completely bypassing xss_clean
        // unlike every other free-text field on this same form.
        $features_post = $this->input->post('features');
        if (!empty($features_post) && is_array($features_post)) {
            foreach (array_keys($features_post) as $i) {
                $this->form_validation->set_rules('features[' . $i . '][description]', 'Feature', 'trim|xss_clean');
            }
        }

        if (!$this->form_validation->run()) {

            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = validation_errors();

            echo json_encode($this->response);
            return;
        }

        /* ---------- FEATURES JSON ---------- */

        $features = $this->input->post('features');
        $features_array = [];

        if (!empty($features)) {

            foreach ($features as $feature) {

                if (!empty($feature['description'])) {
                    $features_array[] = [
                        "description" => $feature['description']
                    ];
                }

            }
        }

        /* ---------- DATA ARRAY ---------- */

        $data = [
            'name' => $this->input->post('name'),
            'price' => $this->input->post('price'),
            'listings_limit' => $this->input->post('listings_limit'),
            'validity' => $this->input->post('validity'),
            'commission_first50' => $this->input->post('commission_first50'),
            'commission_51_100' => $this->input->post('commission_51_100'),
            'commission_after100' => $this->input->post('commission_after100'),

            // save features JSON
            'features' => json_encode($features_array)
        ];
        if ($this->input->post('edit_subscription')) {

            $data['edit_subscription'] = $this->input->post('edit_subscription');
        
        }

        /* ---------- UPDATE OR INSERT ---------- */

        if ($this->input->post('edit_subscription')) {

            $id = $this->input->post('edit_subscription');

            $this->Subscription_model->add_subscription($data);

            $message = 'Subscription Updated Successfully';

        } else {

            $this->Subscription_model->add_subscription($data);

            $message = 'Subscription Added Successfully';
        }

        $this->response['error'] = false;
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        $this->response['message'] = $message;

        echo json_encode($this->response);

    } else {

        redirect('admin/login', 'refresh');

    }
 }
}

