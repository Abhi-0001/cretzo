<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['Home_model', 'Order_model', 'Subscription_model', 'Seller_subscription_model']);
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $user_id = $this->session->userdata('user_id');
            // Single query for user + seller data instead of two separate queries
            $user_res = $this->db->select('u.balance, u.username, sd.rating, sd.no_of_ratings')
            ->from('users u')
            ->join('seller_data sd', 'sd.user_id = u.id', 'left')
            ->where('u.id', $user_id)
            ->get()->row_array();

            $this->data['balance']  = $user_res['balance'] ?? 0;
            $this->data['username'] = $user_res['username'];
            $this->data['ratings']  = [['rating' => $user_res['rating'], 'no_of_ratings' => $user_res['no_of_ratings']]];
            $settings = get_settings('system_settings', true);
            $this->data['curreny'] = get_settings('currency');
            $this->data['profile_completion'] = $this->get_seller_profile_completion($user_id);
            $this->data['title'] = 'Seller Panel | ' . $settings['app_name'];
            $this->data['user_counter'] = (get_seller_permission($this->ion_auth->get_user_id(), 'customer_privacy')) ? $this->Home_model->count_new_users() : 0;
            $this->data['products'] = $this->Home_model->count_products($user_id);
            $this->data['seller_earnings'] = $this->Home_model->total_earnings('seller', $user_id);
            $this->data['meta_description'] = 'Seller Panel | ' . $settings['app_name'];
            $this->data['count_products_low_status'] = $this->Home_model->count_products_stock_low_status($user_id);
            $this->data['count_products_availability_status'] = $this->Home_model->count_products_availability_status($user_id);
            // Single query to get all order status counts instead of 8 separate queries
            $status_rows = $this->db->select('active_status, COUNT(id) as total')
            ->where('seller_id', $user_id)
            ->group_by('active_status')
            ->get('order_items')
            ->result_array();
            
            $orders_count = [
            'awaiting'   => 0,
            'received'   => 0,
            'processed'  => 0,
            'shipped'    => 0,
            'delivered'  => 0,
            'cancelled'  => 0,
            'returned'   => 0,
            ];
            $total_orders = 0;
            foreach ($status_rows as $row) {
            $status = $row['active_status'];
            if (isset($orders_count[$status])) {
                $orders_count[$status] = $row['total'];
            }
            $total_orders += $row['total'];
            }
            $this->data['order_counter'] = $total_orders;
            $this->data['status_counts'] = $orders_count;

             // subscription status for seller
            // Settle a lapsed plan onto the free tier first, so the dashboard doesn't report
            // "expired" for a seller who is, by then, listing on the free plan.
            $this->Seller_subscription_model->ensure_free_tier_fallback($user_id);
            $active_subscription = $this->Seller_subscription_model->get_active_subscription($user_id);
            $latest_subscription = $this->Seller_subscription_model->get_latest_subscription($user_id);

            $subscription_status = 'none'; // none | active | expired
            $current_subscription = null;
            if (!empty($active_subscription)) {
                $subscription_status = 'active';
                $current_subscription = $active_subscription;
            } elseif (!empty($latest_subscription)) {
                $subscription_status = 'expired';
                $current_subscription = $latest_subscription;
            }

            $current_plan = null;
            $subscription_expired_on = null;
            if (!empty($current_subscription)) {
                $current_plan = $this->Subscription_model->get_plan($current_subscription['subscription_id']);
                if (!empty($current_subscription['end_date']) && $subscription_status === 'expired') {
                    $subscription_expired_on = date('d M Y', strtotime($current_subscription['end_date']));
                }
            }

            $this->data['active_subscription'] = $active_subscription;
            $this->data['latest_subscription'] = $latest_subscription;
            $this->data['subscription_status'] = $subscription_status;
            $this->data['current_subscription_plan'] = $current_plan;
            $this->data['subscription_expired_on'] = $subscription_expired_on;

            $this->data['subscription_plans'] = [];
            $this->data['show_subscription_popup'] = false;

            if ($subscription_status !== 'active') {
                $plans = $this->Subscription_model->get_plans();
                if (!empty($plans)) {
                    $this->data['subscription_plans'] = $plans;
                    $this->data['show_subscription_popup'] = true;
                }
            }

            // Approval gate state drives the dashboard popups: nag until the profile is
            // submitted for review, reassure while it is pending, and congratulate exactly
            // once when the admin approves it.
            $approval = seller_approval_state($user_id);
            $this->data['seller_approval'] = $approval;
            $this->data['show_approval_success_popup'] = $approval['show_approval_popup'];

            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    private function get_seller_profile_completion($user_id)
    {
        $verification_requested_at_column = $this->db->field_exists('verification_request_at', 'seller_data');

        $select_fields = 'sd.first_name, sd.last_name, sd.phone, sd.email, sd.district, sd.city, sd.state, sd.pin, sd.shop_name, sd.social, sd.shop_phone, sd.pickup_address1, sd.pickup_address2, sd.pickup_district, sd.pickup_state, sd.pickup_pin, sd.entity_type, sd.pan, sd.gst, sd.is_gst_registered, sd.gst_enrollment_number, sd.account_number, sd.account_holder_name, sd.ifsc, sd.branch, sd.bank_name, sd.status, sd.primary_category_id';
        if ($verification_requested_at_column) {
            $select_fields .= ', sd.verification_request_at';
        }

        $profile_data =$this->db->select($select_fields)
            ->from('users u')
            ->join('seller_data sd', 'sd.user_id = u.id', 'left')
            ->where('u.id', $user_id)
            ->get()
            ->row_array();

            if (empty($profile_data)) {
            return [
                'percentage' => 0,
                'missing_sections' => [
                    ['label' => 'Complete Personal Details', 'link' => base_url('seller/home/profile?section=personal')],
                    ['label' => 'Complete Store Details', 'link' => base_url('seller/home/profile?section=store')],
                    ['label' => 'Add Bank Account Details', 'link' => base_url('seller/home/profile?section=account')],
                ],
            ];
        }

        // Section definitions and the per-field "is it filled?" rule both live in
        // seller_profile_sections() / seller_profile_field_filled() so this meter, the
        // profile page's missing-section list and the verification gate agree exactly.
        $completed_weight = 0;
        $missing_sections = [];

        foreach (seller_profile_sections() as $key => $section) {
            $is_complete = true;

            foreach ($section['fields'] as $field) {
                if (!seller_profile_field_filled($profile_data, $field)) {
                    $is_complete = false;
                    break;
                }
            }

            if ($is_complete) {
                $completed_weight += (int) $section['weight'];
                continue;
            }

            $missing_sections[] = [
                'label' => 'Complete ' . $section['label'],
                'link' => base_url('seller/home/profile?section=' . $key),
            ];
        }

        // Admin Verification Page setup
        $is_admin_verified = isset($profile_data['status']) && (string) $profile_data['status'] === '1';
        if ($is_admin_verified) {
            $completed_weight += 25;
        } else {
            // No "request verification" action to offer any more - saving a complete profile
            // files the request by itself - so this row only ever reports where the seller is.
            if (!empty($profile_data['verification_request_at'])) {
                $verification_label = 'Admin Verification Pending Approval';
                $verification_link = base_url('seller/home/profile?section=admin');
            } elseif (empty($missing_sections)) {
                $verification_label = 'Save your profile to send it for admin verification';
                $verification_link = base_url('seller/home/profile?section=personal');
            } else {
                $verification_label = 'Admin Verification (unlocks once your profile is complete)';
                $verification_link = base_url('seller/home/profile?section=personal');
            }
            $missing_sections[] = [
                'label' => $verification_label,
                'link' => $verification_link,
            ];
        }

        return [
            'percentage' => $completed_weight,
            'missing_sections' => $missing_sections,
        ];
    }

    /**
     * Legacy endpoint for "send my profile for verification".
     *
     * The profile page no longer has a Request Admin Verification button - the admin cannot
     * review a half-filled profile, so saving a complete one files the request by itself (see
     * seller_file_verification_request()). This is kept only so a seller sitting on a cached
     * copy of the old page gets the same behaviour instead of an error, and it no longer asks
     * for a note: nothing ever showed that note to the admin except a varchar(40) column that
     * truncated it.
     */
    public function request_admin_verification()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access.';
            print_r(json_encode($this->response));
            return;
        }

        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $this->response['error'] = true;
            $this->response['message'] = DEMO_VERSION_MSG;
            print_r(json_encode($this->response));
            return;
        }

        $verification = seller_file_verification_request($this->session->userdata('user_id'));

        if ($verification['filed'] || $verification['already_requested']) {
            $this->response['error'] = false;
            $this->response['message'] = $verification['filed']
                ? 'Your profile has been sent to the admin for verification.'
                : 'Your profile is already awaiting admin verification.';
        } elseif ($verification['approved']) {
            $this->response['error'] = false;
            $this->response['message'] = 'Your account is already admin verified.';
        } elseif (!empty($verification['missing_sections'])) {
            $labels = array_unique(array_column($verification['missing_sections'], 'label'));
            $this->response['error'] = true;
            $this->response['message'] = 'Complete these sections first: ' . implode(', ', $labels) . '.';
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unable to send your profile for verification. Please try again.';
        }

        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        print_r(json_encode($this->response));
    }

    /**
     * Marks the one-time "your account is approved" popup as seen.
     *
     * Stamped in the database rather than the session so the congratulation does not come
     * back on the seller's next login. Deliberately forgiving: if the column is missing or
     * the stamp fails, the popup simply shows again - it is informational, so a repeat is
     * better than blocking the dashboard on it.
     */
    public function acknowledge_approval_popup()
    {
        $this->response = [];

        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller())) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access.';
            echo json_encode($this->response);
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $acknowledged = false;

        if ($this->db->field_exists('approval_popup_seen_at', 'seller_data')) {
            $acknowledged = (bool) $this->db->where('user_id', $user_id)
                ->update('seller_data', ['approval_popup_seen_at' => date('Y-m-d H:i:s')]);
        }

        $this->response['error'] = !$acknowledged;
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        echo json_encode($this->response);
    }

    public function profile()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $identity_column = $this->config->item('identity', 'ion_auth');
            $settings = get_settings('system_settings', true);
            $user_id = $this->session->userdata('user_id');
            // print_r($user_id);
            $this->data['identity_column'] = $identity_column;
            $this->data['main_page'] = FORMS . 'profile';
            $this->data['title'] = 'Seller Profile | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Seller Profile | ' . $settings['app_name'];
            $this->data['current_profile_section'] = $this->input->get('section', true) ?? 'personal';
            // Drives the review step's checklist: the seller sees exactly what is still
            // missing, since saving a complete profile is what sends it to the admin.
            $this->data['profile_missing_sections'] = seller_profile_incomplete_sections($user_id);
            // $this->data['fetched_data'] = $this->db->select(' u.*,sd.* ')
            //     ->join('users_groups ug', ' ug.user_id = u.id ')
            //     ->join('seller_data sd', ' sd.user_id = u.id ')
            //     ->where(['ug.group_id' => '4', 'ug.user_id' => $user_id])
            //     ->get('users u')
            //     ->result_array();

            // $this->data['fetched_data'] = $this->db->select(' u.*')
            //     ->join('users_groups ug', ' ug.user_id = u.id ')
            //     ->where(['ug.group_id' => '4', 'ug.user_id' => $user_id])
            //     ->get('users u')
            //     ->result_array();
            
            $this->data['fetched_data'] = $this->db
                // sd.* is selected after u.*, so its columns win when both tables share a
                // name (e.g. email/phone). seller_data.email is NULL for sellers who haven't
                // resaved their profile since that column was added, so fall back to the
                // users table's email/mobile — listed last so it wins the same way.
                ->select('u.*, sd.*, COALESCE(NULLIF(sd.email, ""), u.email) as email, COALESCE(NULLIF(sd.phone, ""), u.mobile) as phone')
                ->from('users u')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->join('seller_data sd', 'sd.user_id = u.id')
                ->where('ug.group_id', 4)
                ->where('ug.user_id', $user_id)
                ->get()
                ->result_array();
                
                // print_r($this->data['fetched_data']);
                // exit;

            $this->data['fetched_data'] = output_escaping_new($this->data['fetched_data']);
            // Added Bank Names Logic 
            $this->data['indian_banks'] = [];
            if ($this->db->table_exists('indian_banks')) {
                $this->data['indian_banks'] = $this->db
                    ->select('bank_name')
                    ->from('indian_banks')
                    ->order_by('bank_name', 'ASC')
                    ->get()
                    ->result_array();
            }
            // Adding the State District and City logic
            $this->data['states'] = [];
            $this->data['districts'] = [];
            $this->data['cities'] = [];
            $this->data['all_categories'] = [];

            if ($this->db->table_exists('categories')) {
                // parent_id = 0 marks a top-level category; children point at their
                // parent's id. Needed so the profile form can scope the Secondary
                // Categories picker to whichever top-level category is chosen as Primary.
                $this->data['all_categories'] = $this->db
                    ->select('id,name,parent_id')
                    ->from('categories')
                    ->where('status', 1)
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result_array();
            }

            $selected_state_name = $this->data['fetched_data'][0]['state'] ?? '';
            $selected_district_name = $this->data['fetched_data'][0]['district'] ?? '';

            $selected_state_id = null;
            $selected_district_id = null;

            if ($this->db->table_exists('states')) {
                $this->data['states'] = $this->db
                    ->select('id,name')
                    ->from('states')
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result_array();

                if (!empty($selected_state_name)) {
                    $state = $this->db
                        ->select('id')
                        ->from('states')
                        ->where('name', $selected_state_name)
                        ->get()
                        ->row_array();
                    $selected_state_id = $state['id'] ?? null;
                }
            }

            if ($this->db->table_exists('districts') && !empty($selected_state_id)) {
                $this->data['districts'] = $this->db
                    ->select('id,name')
                    ->from('districts')
                    ->where('state_id', $selected_state_id)
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result_array();

                if (!empty($selected_district_name)) {
                    $district = $this->db
                        ->select('id')
                        ->from('districts')
                        ->where('state_id', $selected_state_id)
                        ->where('name', $selected_district_name)
                        ->get()
                        ->row_array();
                    $selected_district_id = $district['id'] ?? null;
                }
            }

            if ($this->db->table_exists('cities') && !empty($selected_state_id) && !empty($selected_district_id)) {
                // cities' real schema is city_id/city_name/district_id (no state_id column at
                // all, and no plain id/name) — same schema this app hit before in
                // Area_model::get_cities_list()/Address_model::set_address(). Selecting the
                // old id/name + filtering on a nonexistent state_id 500'd this page for any
                // seller whose state/district happen to resolve to real rows.
                $this->data['cities'] = $this->db
                    ->select('city_id as id, city_name as name')
                    ->from('cities')
                    ->where('district_id', $selected_district_id)
                    ->order_by('city_name', 'ASC')
                    ->get()
                    ->result_array();
            }

            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/home', 'refresh');
        }
    }
    public function get_districts_by_state()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel())) {
            echo json_encode([]);
            return;
        }

        $state_id = $this->input->get('state_id', true);
        if (empty($state_id) || !$this->db->table_exists('districts')) {
            echo json_encode([]);
            return;
        }

        $districts = $this->db
            ->select('id,name')
            ->from('districts')
            ->where('state_id', $state_id)
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();

        echo json_encode($districts);
    }

    public function get_cities_by_district()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel())) {
            echo json_encode([]);
            return;
        }

        $district_id = $this->input->get('district_id', true);

        if (empty($district_id) || !$this->db->table_exists('cities')) {
            echo json_encode([]);
            return;
        }

        // cities' real schema is city_id/city_name/district_id (no state_id column at all) — see
        // the identical fix a few lines up in this file (city dropdown for the profile form).
        $cities = $this->db
            ->select('city_id as id, city_name as name')
            ->from('cities')
            ->where('district_id', $district_id)
            ->order_by('city_name', 'ASC')
            ->get()
            ->result_array();

        echo json_encode($cities);
    }
    //  End of Function for the states, cities and districts 
    
    public function update_status()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $this->response['error'] = true;
                $this->response['message'] = DEMO_VERSION_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            // Only tables a seller actually owns rows in may be toggled here, and only
            // rows belonging to the logged-in seller — this endpoint was copy-pasted from
            // the admin controller (which is allowed to touch any table/row) without ever
            // adding that restriction, so as written any logged-in seller could flip the
            // active/status flag on an arbitrary row of an arbitrary table (e.g. disable
            // another user's account) just by calling this URL directly.
            $allowed_tables = ['products', 'pickup_locations'];

            // $user_id was never assigned in this method - the ownership check below and the
            // UPDATE's WHERE both compared seller_id against an undefined variable (NULL), so
            // $owns_row was always false and EVERY seller product activate/deactivate click
            // came back "Not allowed." The seller-side publish toggle simply did not work.
            $user_id = $this->session->userdata('user_id');

            // $_GET['table'] / ['id'] / ['status'] were all read unguarded - a call missing any
            // of them raised undefined-index warnings that get prepended to the JSON body and
            // break the AJAX parse on a server with display_errors on (which this app ships with).
            $table  = isset($_GET['table']) ? trim($_GET['table']) : '';
            $id     = isset($_GET['id']) ? $_GET['id'] : null;

            if (!in_array($table, $allowed_tables, true) || !is_numeric($id)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Not allowed.';
                print_r(json_encode($this->response));
                return;
            }
            $owns_row = $this->db->where('id', (int) $id)->where('seller_id', $user_id)->get($table)->num_rows() > 0;
            if (!$owns_row) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Not allowed.';
                print_r(json_encode($this->response));
                return;
            }

            $status = (isset($_GET['status']) && $_GET['status'] == '1') ? 0 : 1;

            // A pickup location only reaches status 1 after Shiprocket has accepted the address
            // (Pickup_location_model::add_pickup_location) or an admin has verified/imported it.
            // This toggle is reused from the product publish switch, which flips both ways -
            // but letting a seller flip a pickup location back to 1 themselves would let them
            // re-activate one Shiprocket never confirmed, so only the 1 -> 0 direction is
            // allowed here; re-activating stays an admin-only action.
            if ($table === 'pickup_locations' && $status == 1) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Only an admin can re-activate a pickup location.';
                print_r(json_encode($this->response));
                return;
            }

            $this->db->trans_start();
            // $this->db->escape() wraps the value in quotes and set() escapes again by default,
            // so the column was being written as a doubly-quoted string rather than an integer -
            // the same bug already fixed on the admin side of this endpoint.
            $this->db->set('status', $status);
            $this->db->where('id', (int) $id)->where('seller_id', $user_id)->update($table);
            $this->db->trans_complete();
            // Was inverted (success set error=true); normalised to error=false meaning success,
            // matching the admin endpoint and the rest of the app. The caller in
            // views/seller/pages/tables/manage-product.php was updated to match.
            $response['error'] = ($this->db->trans_status() === false);
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = str_replace('_', ' ', $table);
            print_r(json_encode($response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function fetch_sales()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $sales[] = array();

            $month_res = $this->db->select('SUM(sub_total) AS total_sale,DATE_FORMAT(date_added,"%b") AS month_name ')
                ->where('seller_id', $_SESSION['user_id'])
                ->group_by('year(CURDATE()),MONTH(date_added)')
                ->order_by('year(CURDATE()),MONTH(date_added)')
                ->get('`order_items`')->result_array();

            $month_wise_sales['total_sale'] = array_map('intval', array_column($month_res, 'total_sale'));
            $month_wise_sales['month_name'] = array_column($month_res, 'month_name');

            $sales[0] = $month_wise_sales;
            $d = strtotime("today");
            $start_week = strtotime("last sunday midnight", $d);
            $end_week = strtotime("next saturday", $d);
            $start = date("Y-m-d", $start_week);
            $end = date("Y-m-d", $end_week);
            $week_res = $this->db->select("DATE_FORMAT(date_added, '%d-%b') as date, SUM(sub_total) as total_sale")
                ->where('seller_id', $_SESSION['user_id'])
                ->where("date(date_added) >='$start' and date(date_added) <= '$end' ")
                ->group_by('day(date_added)')->get('`order_items`')->result_array();


            $week_wise_sales['total_sale'] = array_map('intval', array_column($week_res, 'total_sale'));
            $week_wise_sales['week'] = array_column($week_res, 'date');

            $sales[1] = $week_wise_sales;

            $day_res = $this->db->select("DAY(date_added) as date, SUM(sub_total) as total_sale")
                ->where('seller_id', $_SESSION['user_id'])
                ->where('date_added >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)')
                ->group_by('day(date_added)')->get('`order_items`')->result_array();

            $day_wise_sales['total_sale'] = array_map('intval', array_column($day_res, 'total_sale'));
            $day_wise_sales['day'] = array_column($day_res, 'date');

            $sales[2] = $day_wise_sales;
            print_r(json_encode($sales));
        } else {
            redirect('seller/login', 'refresh');
        }
    }


    public function category_wise_product_count()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $user_id = $this->session->userdata('user_id');
            $seller_row = $this->db->select('category_ids')->where('user_id', $user_id)->get('seller_data')->row_array();
            $category_ids = explode(",", $seller_row['category_ids'] ?? '');

            $res = $this->db->select('c.name as name,count(c.id) as counter')->group_Start()->where_in('c.id', $category_ids)->group_End()->where(['p.status' => '1', 'c.status' => '1'])->join('products p', 'p.category_id=c.id')->group_by('c.id')->get('categories c')->result_array();
            $result = array();
            $result[0][] = 'Task';
            $result[0][] = 'Hours per Day';
            array_walk($res, function ($v, $k) use (&$result) {
                $result[$k + 1][] = $v['name'];
                $result[$k + 1][] = intval($v['counter']);
            });
            echo json_encode(array_values($result));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    /**
     * Removes one image from a product / variant the seller owns.
     *
     * The admin counterpart (admin/Home::delete_image) takes the table, column and row id
     * straight from the request, which is fine behind an admin check but would be a way for
     * any seller to blank any column of any row here. This one accepts only the two
     * (table, field) pairs the product form actually renders a delete button for, and
     * resolves ownership of the row before touching it.
     */
    public function delete_image()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel())) {
            $this->response['error'] = true;
            $this->response['is_deleted'] = false;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        $allowed = ['products' => 'other_images', 'product_variants' => 'images'];
        $table = $this->input->post('table_name', true);
        $field = $this->input->post('field', true);
        $id = (int) $this->input->post('id', true);
        $img_name = $this->input->post('img_name', true);

        if (!isset($allowed[$table]) || $allowed[$table] !== $field || $id <= 0 || $img_name === null || $img_name === '') {
            $this->response['error'] = true;
            $this->response['is_deleted'] = false;
            $this->response['message'] = 'This image cannot be deleted from here.';
            echo json_encode($this->response);
            return false;
        }

        $seller_id = $this->session->userdata('user_id');
        if ($table === 'products') {
            $owned = fetch_details('products', ['id' => $id, 'seller_id' => $seller_id], 'id');
        } else {
            $owned = $this->db->select('pv.id')
                ->join('products p', 'p.id = pv.product_id')
                ->where(['pv.id' => $id, 'p.seller_id' => $seller_id])
                ->get('product_variants pv')->result_array();
        }

        if (empty($owned)) {
            $this->response['error'] = true;
            $this->response['is_deleted'] = false;
            $this->response['message'] = 'Product not found';
            echo json_encode($this->response);
            return false;
        }

        $this->response['error'] = false;
        $this->response['is_deleted'] = delete_image($id, $this->input->post('path', true), $field, $img_name, $table, true);
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        echo json_encode($this->response);
    }

    public function logout()
    {
        $this->ion_auth->logout();
        redirect('seller/login', 'refresh');
    }
}
