<?php

use Twilio\TwiML\Messaging\Message;
defined('BASEPATH') or exit('No direct script access allowed');

use Twilio\Rest\Client as TwilioClient;

class Auth extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['Seller_model', 'Seller_subscription_model']);
        $this->lang->load('auth');
    }

    public function index()
    {
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_seller()) {
            $this->data['main_page'] = FORMS . 'login';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Seller Login Panel | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Seller Login Panel | ' . $settings['app_name'];
            $this->data['logo'] = get_settings('logo');
            $this->data['app_name'] = $settings['app_name'];
            $identity = $this->config->item('identity', 'ion_auth');
            if (empty($identity)) {
                $identity_column = 'text';
            } else {
                $identity_column = $identity;
            }
            $this->data['identity_column'] = $identity_column;
            $this->data['launch_offer_active'] = $this->Seller_subscription_model->is_launch_offer_active();
            $this->load->view('seller/login', $this->data);
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {
            redirect('seller/home', 'refresh');
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            redirect('admin/home', 'refresh');
        } else {
            // Reached by a logged-in seller who is Deactive/Removed (or any other
            // logged-in principal): every branch above fails, and without this the
            // method fell off the end and rendered a blank page. seller/Login::index()
            // is the canonical seller entry point and force-logs-out a
            // deactivated/removed seller before showing the form again.
            redirect('seller/login', 'refresh');
        }
    }

    public function sign_up()
    {
        $settings = get_settings('system_settings', true);
        $this->data['title'] = 'Sign Up Seller | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Sign Up Seller | ' . $settings['app_name'];
        $this->data['logo'] = get_settings('logo');

        if (isset($_SESSION['to_be_seller_name']) && !empty($_SESSION['to_be_seller_name']) && isset($_SESSION['to_be_seller_mobile']) && !empty($_SESSION['to_be_seller_mobile']) && isset($_SESSION['to_be_seller_id']) && !empty($_SESSION['to_be_seller_id'])) {
            $this->data['title'] = 'Update Seller | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Update Seller | ' . $settings['app_name'];
            $this->data['user_data'] = $_SESSION;
        }

        $this->data['launch_offer_active'] = $this->Seller_subscription_model->is_launch_offer_active();
        $this->load->view('seller/pages/forms/seller-registration', $this->data);
    }

     // 1. Send OTP via Twilio
    public function send_otp(){
        $mobile = $this->input->post('mobile', true);
        // Basic check
        if(empty($mobile) || strlen($mobile) != 10){
            echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number']);
            return;
        }

        // Generate OTP
        $otp = random_int(100000, 999999);

        // Store OTP & mobile in session for 10 minutes
        $this->session->set_tempdata('otp', $otp, 10 * 60);

        $this->config->load('twilio', true);
        $sid = $this->config->item('sid', 'twilio');
        $token = $this->config->item('token', 'twilio');
        $twilio_number = $this->config->item('from_number', 'twilio');

        try {
            $client = new TwilioClient($sid, $token);
            $client->messages->create(
                "+91".$mobile,
                [
                    "from" => $twilio_number,
                    "body" => "Your OTP is: ".$otp
                ]
            );

            echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
        } catch (Exception $e) {
            log_message('error', 'Twilio OTP send failed: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP. Please try again.']);
        }
    }

    public function verify_otp(){
        $mobile = $this->input->post('mobile', true);
        $otp = (int) $this->input->post('otp', true);
        // $stored_otp = (int) '123456';
        $stored_otp = (int) $this->session->tempdata('otp');
        $response = [];
     
        
        if(empty($stored_otp)){
             $response = [
                'status' => 'failed',
                'message' => 'OTP has expired!'
            ];
            
        }
        elseif($stored_otp !== $otp){
            $response = [
                'status' => 'Failed',
                'message' => 'Please enter correct OTP'
            ];
            
        }
        else {
            $response = [
                'status' => 'success',
                'message' => 'OTP verified successfully!'
            ];
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    /**
     * Proves the person signing up actually owns the pre-existing account on this mobile,
     * before we attach a seller profile to it.
     *
     * Two accepted proofs:
     *   1. A Firebase phone ID token we verify ourselves (signature, audience, expiry,
     *      provider really 'phone', and its phone claim matching the account). The signup
     *      page already runs Firebase phone auth, so this is the normal path.
     *   2. The account's CURRENT password. Covers the case where the browser lost the
     *      token, and is the same proof a login would demand.
     *
     * Deliberately does NOT trust $_POST['phone_verified'] - that is a hidden field the
     * page sets to '1' in JavaScript and anyone can post it.
     */
    private function _owns_existing_account($user, $submitted_password)
    {
        $id_token = $this->input->post('firebase_id_token', false);
        if (!empty($id_token)) {
            $verified = verify_firebase_id_token($id_token);
            if (empty($verified['error'])
                && $verified['provider'] === 'phone'
                && !empty($verified['phone'])
                && phone_digits_match($verified['phone'], $user['mobile'])) {
                return true;
            }
        }

        // Fall back to the existing password. ion_auth's own login() is used so the hash
        // comparison and any rehashing stay consistent with normal sign-in.
        if (!empty($submitted_password)) {
            $identity_column = $this->config->item('identity', 'ion_auth');
            $identity = ($identity_column == 'email') ? $user['email'] : $user['mobile'];
            if ($this->ion_auth->login($identity, $submitted_password)) {
                // Don't leave them half-logged-in from a signup POST; the flow asks them to
                // log in properly afterwards.
                $this->ion_auth->logout();
                return true;
            }
        }

        return false;
    }

    /**
     * Adds the seller role to an existing account, keeping every role it already has, and
     * creates the seller profile + registration subscription it needs.
     */
    private function _grant_seller_role($user_id, $name, $email, $mobile)
    {
        $this->load->model(['Seller_model', 'Seller_subscription_model']);

        if (!user_has_role($user_id, 'seller')) {
            $this->ion_auth->add_to_group(4, $user_id);
        }
        // Keep the buyer role too, so enabling selling never costs them their storefront
        // access or order history.
        if (!user_has_role($user_id, 'customer')) {
            $this->ion_auth->add_to_group(2, $user_id);
        }

        // Only create the seller profile if one doesn't already exist, so re-running this
        // can't produce a duplicate seller_data row.
        if (empty(fetch_details('seller_data', ['user_id' => $user_id], 'id'))) {
            $this->Seller_model->seller_cereate_user([
                'user_id' => $user_id,
                'status'  => 2, // Not-Approved: pending admin review, same as every other path
                'shop_name' => $name,
                'email'   => $email,
                'phone'   => $mobile,
                'authorized_signature' => '',
            ]);
        }

        // assign_registration_offer() no-ops when a subscription already exists.
        $this->Seller_subscription_model->assign_registration_offer($user_id);
    }

    public function ajax_signup(){
        try {
            // Get form data
            $password = $this->input->post('password', true);
            $confirm_password = $this->input->post('confirm_password', true);
            $mobile = $this->input->post('mobile', true);
            $name = $this->input->post('name', true);
            $email = $this->input->post('email', true);
            $phone_verified = $this->input->post('phone_verified', true);

            $response = [
                'status' => 'failed',
                'message' => 'Registration failed'
            ];

            // Validate inputs
            if (empty($name) || strlen($name) < 2) {
                $response['message'] = 'Full name is required';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Valid email address is required';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            if (empty($mobile) || !preg_match('/^[6-9]\d{9}$/', $mobile)) {
                $response['message'] = 'Valid 10-digit mobile number is required';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            // Same rule the profile form and the admin seller form enforce: one email
            // address per seller account. Checked here too, otherwise signup could create
            // the exact clash that the profile save would then refuse to let anyone fix.
            $email_clash = duplicate_seller_contacts(['email' => $email], 0);
            if (!empty($email_clash)) {
                $response['message'] = implode(' ', $email_clash);
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            if ($phone_verified != '1') {
                $response['message'] = 'Please verify your phone number with OTP';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            if (empty($password) || strlen($password) < 6) {
                $response['message'] = 'Password must be at least 6 characters';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            if ($password !== $confirm_password) {
                $response['message'] = 'Passwords do not match';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            // One mobile number = one account (users.mobile is UNIQUE and is ion_auth's
            // identity column), but that account may hold SEVERAL roles. An existing buyer
            // who now wants to sell used to be turned away with "Mobile number already
            // registered", which forced anyone with a single phone number to choose between
            // buying and selling. Instead we add the seller role to the account they
            // already have.
            $existing_user = fetch_details('users', ['mobile' => $mobile]);
            if (!empty($existing_user)) {
                $existing_id = $existing_user[0]['id'];

                if (user_has_role($existing_id, 'seller')) {
                    $response['message'] = 'This mobile number is already registered as a seller. Please log in instead.';
                    $this->output->set_content_type('application/json')->set_output(json_encode($response));
                    return;
                }

                // Adding a seller profile to somebody else's account would be a takeover, so
                // ownership has to be proven. phone_verified is just a hidden field the page
                // sets to '1' in JS - it proves nothing on its own. Accept either a Firebase
                // phone token we verify ourselves, or the account's current password.
                if (!$this->_owns_existing_account($existing_user[0], $password)) {
                    $response['message'] = 'This mobile number already has an account. '
                        . 'Enter that account\'s existing password to add selling to it, or reset your password first.';
                    $this->output->set_content_type('application/json')->set_output(json_encode($response));
                    return;
                }

                $this->_grant_seller_role($existing_id, $name, $email, $mobile);

                $response['status'] = 'success';
                $response['message'] = 'Selling has been enabled on your existing account. '
                    . 'Please log in with your usual password. Your seller profile is pending admin approval.';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            // Register user. Group 2 ("members") as well as 4 ("seller") so a seller can
            // also shop on the storefront - the storefront login refuses anyone who is not
            // in group 2, so seller-only accounts previously could not buy anything.
            $user_id = $this->ion_auth->register($mobile, $password, $email, ['name' => $name], [2, 4]);

            if (!$user_id) {
                $response['message'] = 'Registration failed. ' . $this->ion_auth->messages();
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }

            // This never created a seller_data row, so every seller who registered through this
            // endpoint had a users/users_groups row but no seller profile at all - a "ghost"
            // seller, invisible on admin/sellers/ (Seller_model::get_sellers_list() innner-joined
            // seller_data, so a seller with no row there never matched, on any page or filter)
            // and unopenable by its edit link either. status 2 (Not-Approved) matches how every
            // other new-seller path in this app starts a profile, pending admin review.
            $this->Seller_model->seller_cereate_user([
                'user_id' => $user_id,
                'status' => 2,
                'shop_name' => $name,
                'email' => $email,
                'phone' => $mobile,
                // NOT NULL with no default in the schema - relying on the server's SQL mode to
                // silently substitute '' would work locally but isn't guaranteed everywhere.
                'authorized_signature' => '',
            ]);

            // Launch promotion: first 20 vendors get "Launch Offer" (30 free listings /
            // 1 year), everyone after gets the free plan. Never blocks registration.
            $this->Seller_subscription_model->assign_registration_offer($user_id);

            // Try to login
            $login_result = $this->ion_auth->login($mobile, $password);
            if ($login_result) {
                $response['status'] = 'success';
                $response['message'] = 'Account created successfully!';
            } else {
                $response['status'] = 'success';
                $response['message'] = 'Account created. Please log in.';
            }

        } catch (Exception $e) {
            $response = [
                'status' => 'failed',
                'message' => 'Server error: ' . $e->getMessage()
            ];
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    /**
     * Resolve whatever the seller typed into the "Email and Mobile" box to the mobile
     * number of their seller account.
     *
     * $config['identity'] in config/ion_auth.php is 'mobile', so ion_auth->login() only
     * ever matches on `users`.`mobile`. Signup collects an email AND a mobile, so sellers
     * reasonably try either - but the email was passed straight through to a mobile-keyed
     * lookup, matched nothing, and every email login failed. Resolving to the mobile here
     * keeps ion_auth (and the session `identity` the rest of the panel reads) unchanged.
     *
     * Returns the seller row (id + mobile), or NULL when no seller account matches.
     */
    private function _resolve_seller_login($identity)
    {
        $rows = fetch_details('users', ['mobile' => $identity], 'id,mobile');

        // Only try email when the input cannot be a mobile number at all, so a normal
        // mobile login does not pay for a second, pointless query.
        if (empty($rows) && filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $rows = fetch_details('users', ['email' => $identity], 'id,mobile');
        }

        if (empty($rows)) {
            return NULL;
        }

        // `users`.`email` carries no unique index and customers share the table, so an
        // email can return several rows - take the one that actually holds the seller
        // role rather than assuming it is the first.
        //
        // in_group() rather than reading the first `users_groups` row: every seller also
        // carries the customer group (2) now that one account can both sell and buy, and
        // MySQL returns those rows in index order on (user_id, group_id), so the customer
        // row comes back first for every dual-role seller. Reading row 0 rejected every
        // seller who could also shop.
        foreach ($rows as $row) {
            if (!empty($row['mobile']) && $this->ion_auth_model->in_group('seller', $row['id'])) {
                return $row;
            }
        }

        return NULL;
    }

    public function login(){
        try {
            $identity = $this->input->post('identity', true);
            $password = $this->input->post('password', true);
            $remember = $this->input->post('remember', true);

            // Validate inputs
            if (empty($identity) || empty($password)) {
                redirect('seller/login?error=true');
                return;
            }

            // Accepts either the registered mobile number or the registered email.
            $seller = $this->_resolve_seller_login($identity);

            if (empty($seller)) {
                redirect('seller/login?error=true');
                return;
            }

            // Attempt login with the identity column ion_auth understands.
            $login_result = $this->ion_auth->login($seller['mobile'], $password, $remember);
            
            if ($login_result) {
                redirect('seller/home', 'refresh');
            } else {
                redirect('seller/login?error=true');
            }

        } catch (Exception $e) {
            log_message('error', 'Seller login error: ' . $e->getMessage());
            redirect('seller/login?error=true');
        }
    }

    // public function create_seller() {

    //     $response = [];
    //     // Set validation rules
    //     $this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
    //     $this->form_validation->set_rules('last_name', 'Last Name', 'trim');
    //     $this->form_validation->set_rules('phone', 'Phone Number', 'required|numeric|min_length[10]|max_length[15]');
    //     $this->form_validation->set_rules('email', 'Email ID', 'required|valid_email');

    //     // Optional address fields
    //     $this->form_validation->set_rules('address1', 'Address Line 1', 'required|trim');
    //     $this->form_validation->set_rules('address2', 'Address Line 2', 'trim');
    //     $this->form_validation->set_rules('district', 'District', 'required|trim');
    //     $this->form_validation->set_rules('city', 'City', 'trim');
    //     $this->form_validation->set_rules('state', 'State', 'required|trim');
    //     $this->form_validation->set_rules('pin', 'PIN Code', 'numeric|trim|required|min_length[6]|max_length[6]');

    //     // Step 2 fields
    //     $this->form_validation->set_rules('shop_name', 'Shop Name', 'required|trim');
    //     $this->form_validation->set_rules('social', 'Social Media Handle', 'required|trim');
    //     $this->form_validation->set_rules('shop_phone', 'Shop Phone Number', 'required|numeric|min_length[10]|max_length[15]');
    //     $this->form_validation->set_rules('pickup_address1', 'Pickup Address Lane 1', 'required|trim');
    //     $this->form_validation->set_rules('pickup_address2', 'Pickup Address Lane 2', 'trim');
    //       $this->form_validation->set_rules('pickup_district', 'District', 'required|trim');
    //     $this->form_validation->set_rules('pickup_city', 'City', 'trim');
    //     $this->form_validation->set_rules('pickup_state', 'State', 'required|trim');
    //     $this->form_validation->set_rules('pickup_pin', 'PIN Code', 'numeric|trim|required|min_length[6]|max_length[6]');

    //     // Step 3 fields
    //     $this->form_validation->set_rules('entity_type', 'Entity Type', 'required|trim');
    //     $this->form_validation->set_rules('pan', 'PAN Number', 'required|regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/]');
    //     $this->form_validation->set_rules('gst', 'GST Number', 'required|regex_match[/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/]');
        
    //     $this->form_validation->set_rules('account_number', "Account Number", 'required|trim|numeric');
    //     $this->form_validation->set_rules('confirm_account_number', "Confirm Account Number", 'required|trim|numeric|matches[account_number]');

    //     $this->form_validation->set_rules('account_holder_name', 'Account Holder\'s Name', 'required|trim');
    //     $this->form_validation->set_rules('ifsc', 'IFSC Code', 'required|trim');
    //     $this->form_validation->set_rules('branch', 'Branch Name', 'required|trim');
    //     $this->form_validation->set_rules('bank_name', 'Bank Name', 'required|trim');

    //     if (!$this->form_validation->run()) {
    //         $response['error'] = true;
    //         $response['message'] = validation_errors();
    //         // print_r(json_encode($this->response));
    //     } else {
    //          $response['error'] = false;
    //         $response['message'] = "Seller succesfully registered";
            
    //     }

    //     $response['csrfName'] = $this->security->get_csrf_token_name();
    //     $response['csrfHash'] = $this->security->get_csrf_hash();

    //     echo json_encode($response);
    // }
    
    public function create_seller()
    {

        $response = [];
        // Set validation rules
        $this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
        // $this->form_validation->set_rules('last_name', 'Last Name', 'trim');
        $this->form_validation->set_rules('phone', 'Phone Number', 'required|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('email', 'Email ID', 'required|valid_email');

        // Optional address fields
        // $this->form_validation->set_rules('address1', 'Address Line 1', 'required|trim');
        // $this->form_validation->set_rules('address2', 'Address Line 2', 'trim');
        // $this->form_validation->set_rules('district', 'District', 'required|trim');
        // $this->form_validation->set_rules('city', 'City', 'trim');
        // $this->form_validation->set_rules('state', 'State', 'required|trim');
        // $this->form_validation->set_rules('pin', 'PIN Code', 'numeric|trim|required|min_length[6]|max_length[6]');

        // Step 2 fields
        // $this->form_validation->set_rules('shop_name', 'Shop Name', 'required|trim');
        // $this->form_validation->set_rules('social', 'Social Media Handle', 'required|trim');
        // $this->form_validation->set_rules('shop_phone', 'Shop Phone Number', 'required|numeric|min_length[10]|max_length[15]');
        // $this->form_validation->set_rules('pickup_address1', 'Pickup Address Lane 1', 'required|trim');
        // $this->form_validation->set_rules('pickup_address2', 'Pickup Address Lane 2', 'trim');
        // $this->form_validation->set_rules('pickup_district', 'District', 'required|trim');
        // $this->form_validation->set_rules('pickup_city', 'City', 'trim');
        // $this->form_validation->set_rules('pickup_state', 'State', 'required|trim');
        // $this->form_validation->set_rules('pickup_pin', 'PIN Code', 'numeric|trim|required|min_length[6]|max_length[6]');

        // Step 3 fields
        // $this->form_validation->set_rules('entity_type', 'Entity Type', 'required|trim');
        // Restored, minus the `required`.
        //
        // These two were commented out along with the rest of this endpoint's rules, which left
        // PAN and GSTIN as unvalidated free text everywhere they are written - and those two
        // fields are what Tax_compliance_model reads to decide the TDS and TCS rate for every
        // settlement. The result was a live marketplace deducting 5% s.206AA from sellers whose
        // "PAN" was a random digit string, and collecting no TCS from anybody because no GSTIN
        // validated. See seller_tax_identifier_errors() for the full account.
        //
        // `required` stays off deliberately: this endpoint only asks for name/phone/email (the
        // seller completes their tax details later on the profile form, which now validates them
        // too), so demanding a PAN here would break the registration it is meant to allow. What
        // matters is that a value which IS supplied has to be a real identifier.
        $this->form_validation->set_rules('pan', 'PAN Number', 'trim|xss_clean|regex_match[/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/]');
        $this->form_validation->set_rules('gst', 'GST Number', 'trim|xss_clean|regex_match[/^[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}[1-9A-Za-z]{1}[Zz]{1}[0-9A-Za-z]{1}$/]');

        // $this->form_validation->set_rules('account_number', "Account Number", 'required|trim|numeric');
        // $this->form_validation->set_rules('confirm_account_number', "Confirm Account Number", 'required|trim|numeric|matches[account_number]');

        // $this->form_validation->set_rules('account_holder_name', 'Account Holder\'s Name', 'required|trim');
        // $this->form_validation->set_rules('ifsc', 'IFSC Code', 'required|trim');
        // $this->form_validation->set_rules('branch', 'Branch Name', 'required|trim');
        // $this->form_validation->set_rules('bank_name', 'Bank Name', 'required|trim');

        if (!$this->form_validation->run()) {
            $response['error'] = true;
            $response['message'] = validation_errors();
            // print_r(json_encode($this->response));
        } else {

            $seler_register = [
                'email' => $this->input->post('email'),
                'mobile' => $this->input->post('phone'),
                'first_name' => $this->input->post('first_name'),
                'address' => $this->input->post('address1') . ', ' . $this->input->post('address2') . ', ' . $this->input->post('district')
            ];

            // This endpoint doesn't collect a password field from the caller (see the
            // validation rules above - only first_name/phone/email are required), so a
            // random one is generated rather than reusing a fixed value across every
            // account created this way. The seller can set their own via forgot-password.
            $temp_password = bin2hex(random_bytes(8));
            // Groups 2 + 4: a seller also holds the buyer role so they can shop on the
            // storefront, which refuses anyone not in group 2.
            $user_id = $this->ion_auth->register($seler_register['mobile'], $temp_password, $seler_register['first_name'], ['phone' => $seler_register['mobile']], [2, 4]);

            if (!$user_id) {
                $response['error'] = true;
                $response['message'] = 'Failed to register user';
                $response['data'] = $seler_register;
            } else {
                $seler_register['user_id'] = $user_id;
            }

            if (!empty($_FILES['store_logo']['name'])) {

                $uploadDir = FCPATH . 'uploads/seller/';

                // Create directory if not exists
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = $_FILES['store_logo']['name'];
                $fileTmpName = $_FILES['store_logo']['tmp_name'];
                $fileSize = $_FILES['store_logo']['size'];
                $fileError = $_FILES['store_logo']['error'];

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                // Validation
                if (!in_array($fileExt, $allowed)) {
                    echo json_encode([
                        'error' => true,
                        'message' => 'Invalid file type'
                    ]);
                    return;
                }

                if ($fileSize > 2 * 1024 * 1024) { // 2MB
                    echo json_encode([
                        'error' => true,
                        'message' => 'File size must be less than 2MB'
                    ]);
                    return;
                }

                if ($fileError !== 0) {
                    echo json_encode([
                        'error' => true,
                        'message' => 'File upload error'
                    ]);
                    return;
                }

                // Keep original name (or use uniqid())
                $newFileName = $fileName;
                // $newFileName = uniqid() . '.' . $fileExt; // optional

                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpName, $destination)) {

                    // Save this path in DB
                    $store_logo_path = 'uploads/seller/' . $newFileName;

                } else {
                    echo json_encode([
                        'error' => true,
                        'message' => 'Failed to upload file'
                    ]);
                    return;
                }
            }


            $seller_data = [
                'user_id' => $user_id,
                'first_name' => $this->input->post('first_name') ?? null,
                'last_name' => $this->input->post('last_name') ?? null,
                'phone' => $this->input->post('phone') ?? null,
                'email' => $this->input->post('email') ?? null,
                'address1' => $this->input->post('address1') ?? null,
                'address2' => $this->input->post('address2') ?? null,
                'district' => $this->input->post('district') ?? null,
                'city' => $this->input->post('city') ?? null,
                'state' => $this->input->post('state') ?? null,
                'pin' => $this->input->post('pin') ?? null,
                'logo' => (!empty($store_logo_path)) ? $store_logo_path : null,
                'shop_name' => $this->input->post('shop_name') ?? null,
                'social' => $this->input->post('social') ?? null,
                'shop_phone' => $this->input->post('shop_phone') ?? null,
                'pickup_address1' => $this->input->post('pickup_address1') ?? null,
                'pickup_address2' => $this->input->post('pickup_address2') ?? null,
                'pickup_district' => $this->input->post('pickup_district') ?? null,
                'pickup_state' => $this->input->post('pickup_state') ?? null,
                'pickup_pin' => $this->input->post('pickup_pin') ?? null,
                'entity_type' => $this->input->post('entity_type') ?? null,
                'pan' => $this->input->post('pan') ?? null,
                'gst' => $this->input->post('gst') ?? null,
                'account_number' => $this->input->post('account_number') ?? null,
                'account_holder_name' => $this->input->post('account_holder_name') ?? null,
                'ifsc' => $this->input->post('ifsc') ?? null,
                'branch' => $this->input->post('branch') ?? null,
                'bank_name' => $this->input->post('bank_name') ?? null
            ];

            $this->Seller_model->seller_cereate_user($seller_data);

            // Launch promotion: first 20 vendors get "Launch Offer" (30 free listings /
            // 1 year), everyone after gets the free plan.
            if (!empty($user_id)) {
                $this->Seller_subscription_model->assign_registration_offer($user_id);
            }

            $this->ion_auth->login($this->input->post('phone'), $temp_password, true);



            $response['error'] = false;
            $response['message'] = "Seller succesfully registered";

        }

        $response['csrfName'] = $this->security->get_csrf_token_name();
        $response['csrfHash'] = $this->security->get_csrf_hash();

        echo json_encode($response);
    }
    
    public function create_seller_old()
    {
        if (!isset($_POST['user_id'])) {
            $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|xss_clean|min_length[5]');
            $this->form_validation->set_rules('email', 'Mail', 'trim|required|xss_clean');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
            $this->form_validation->set_rules('confirm_password', 'Confirm password', 'trim|required|matches[password]|xss_clean');
            $this->form_validation->set_rules('address', 'Address', 'trim|required|xss_clean');
        } else {
            $this->form_validation->set_rules('user_name', 'Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('user_mobile', 'Mobile', 'trim|required|xss_clean|min_length[5]');
        }
        $this->form_validation->set_rules('store_name', 'Store Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('tax_name', 'Tax Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('tax_number', 'Tax Number', 'trim|required|xss_clean');
        $this->form_validation->set_rules('store_logo', 'Store Logo', 'trim|xss_clean');
        $this->form_validation->set_rules('national_identity_card', 'National Identity Card', 'trim|xss_clean');
        $this->form_validation->set_rules('authorized_signature', 'Authorized Signature', 'trim|xss_clean');
        $this->form_validation->set_rules('address_proof', 'Address Proof', 'trim|xss_clean');




        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = validation_errors();
            print_r(json_encode($this->response));
        } else {
            if (!file_exists(FCPATH . SELLER_DOCUMENTS_PATH)) {
                mkdir(FCPATH . SELLER_DOCUMENTS_PATH, 0777);
            }

            //process store logo
            $temp_array_logo = $store_logo_doc = array();
            $logo_files = $_FILES;
            $store_logo_error = "";
            $config = [
                'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                'allowed_types' => 'jpg|png|jpeg|gif',
                'max_size' => 8000,
            ];
            if (isset($logo_files['store_logo']) && !empty($logo_files['store_logo']['name']) && isset($logo_files['store_logo']['name'])) {
                $other_img = $this->upload;
                $other_img->initialize($config);

                if (isset($_POST['edit_seller']) && !empty($_POST['edit_seller']) && isset($_POST['old_store_logo']) && !empty($_POST['old_store_logo'])) {
                    $old_logo = explode('/', $this->input->post('old_store_logo', true));
                    delete_images(SELLER_DOCUMENTS_PATH, $old_logo[2]);
                }

                if (!empty($logo_files['store_logo']['name'])) {

                    $_FILES['temp_image']['name'] = $logo_files['store_logo']['name'];
                    $_FILES['temp_image']['type'] = $logo_files['store_logo']['type'];
                    $_FILES['temp_image']['tmp_name'] = $logo_files['store_logo']['tmp_name'];
                    $_FILES['temp_image']['error'] = $logo_files['store_logo']['error'];
                    $_FILES['temp_image']['size'] = $logo_files['store_logo']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $store_logo_error = 'Images :' . $store_logo_error . ' ' . $other_img->display_errors();
                    } else {
                        $temp_array_logo = $other_img->data();
                        resize_review_images($temp_array_logo, FCPATH . SELLER_DOCUMENTS_PATH);
                        $store_logo_doc  = SELLER_DOCUMENTS_PATH . $temp_array_logo['file_name'];
                    }
                } else {
                    $_FILES['temp_image']['name'] = $logo_files['store_logo']['name'];
                    $_FILES['temp_image']['type'] = $logo_files['store_logo']['type'];
                    $_FILES['temp_image']['tmp_name'] = $logo_files['store_logo']['tmp_name'];
                    $_FILES['temp_image']['error'] = $logo_files['store_logo']['error'];
                    $_FILES['temp_image']['size'] = $logo_files['store_logo']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $store_logo_error = $other_img->display_errors();
                    }
                }
                //Deleting Uploaded Images if any overall error occured
                if ($store_logo_error != NULL || !$this->form_validation->run()) {
                    if (isset($store_logo_doc) && !empty($store_logo_doc || !$this->form_validation->run())) {
                        foreach ($store_logo_doc as $key => $val) {
                            unlink(FCPATH . SELLER_DOCUMENTS_PATH . $store_logo_doc[$key]);
                        }
                    }
                }
            }

            if ($store_logo_error != NULL) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] =  $store_logo_error;
                print_r(json_encode($this->response));
                return;
            }

            //process Authorized Signature
            $temp_array_authorized_signature = $authorized_signature_doc = array();
            $authorized_signature_files = $_FILES;
            $authorized_signature_error = "";
            $config = [
                'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                'allowed_types' => 'jpg|png|jpeg|gif',
                'max_size' => 8000,
            ];
            if (isset($authorized_signature_files['authorized_signature']) && !empty($authorized_signature_files['authorized_signature']['name']) && isset($authorized_signature_files['authorized_signature']['name'])) {
                $other_img = $this->upload;
                $other_img->initialize($config);

                if (isset($_POST['edit_seller']) && !empty($_POST['edit_seller']) && isset($_POST['old_authorized_signature']) && !empty($_POST['old_authorized_signature'])) {
                    $old_authorized_signature = explode('/', $this->input->post('old_authorized_signature', true));
                    delete_images(SELLER_DOCUMENTS_PATH, $old_authorized_signature[2]);
                }

                if (!empty($authorized_signature_files['authorized_signature']['name'])) {

                    $_FILES['temp_image']['name'] = $authorized_signature_files['authorized_signature']['name'];
                    $_FILES['temp_image']['type'] = $authorized_signature_files['authorized_signature']['type'];
                    $_FILES['temp_image']['tmp_name'] = $authorized_signature_files['authorized_signature']['tmp_name'];
                    $_FILES['temp_image']['error'] = $authorized_signature_files['authorized_signature']['error'];
                    $_FILES['temp_image']['size'] = $authorized_signature_files['authorized_signature']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $authorized_signature_error = 'Images :' . $authorized_signature_error . ' ' . $other_img->display_errors();
                    } else {
                        $temp_array_authorized_signature = $other_img->data();
                        resize_review_images($temp_array_authorized_signature, FCPATH . SELLER_DOCUMENTS_PATH);
                        $authorized_signature_doc  = SELLER_DOCUMENTS_PATH . $temp_array_authorized_signature['file_name'];
                    }
                } else {
                    $_FILES['temp_image']['name'] = $authorized_signature_files['authorized_signature']['name'];
                    $_FILES['temp_image']['type'] = $authorized_signature_files['authorized_signature']['type'];
                    $_FILES['temp_image']['tmp_name'] = $authorized_signature_files['authorized_signature']['tmp_name'];
                    $_FILES['temp_image']['error'] = $authorized_signature_files['authorized_signature']['error'];
                    $_FILES['temp_image']['size'] = $authorized_signature_files['authorized_signature']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $authorized_signature_error = $other_img->display_errors();
                    }
                }
                //Deleting Uploaded Images if any overall error occured
                if ($authorized_signature_error != NULL || !$this->form_validation->run()) {
                    if (isset($authorized_signature_doc) && !empty($authorized_signature_doc || !$this->form_validation->run())) {
                        foreach ($authorized_signature_doc as $key => $val) {
                            unlink(FCPATH . SELLER_DOCUMENTS_PATH . $authorized_signature_doc[$key]);
                        }
                    }
                }
            }

            if ($authorized_signature_error != NULL) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] =  $authorized_signature_error;
                print_r(json_encode($this->response));
                return;
            }

            //process national_identity_card
            $temp_array_id_card = $id_card_doc = array();
            $id_card_files = $_FILES;
            $id_card_error = "";
            $config = [
                'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                'allowed_types' => 'jpg|png|jpeg|gif',
                'max_size' => 8000,
            ];
            if (isset($id_card_files['national_identity_card']) &&  !empty($id_card_files['national_identity_card']['name']) && isset($id_card_files['national_identity_card']['name'])) {
                $other_img = $this->upload;
                $other_img->initialize($config);

                if (isset($_POST['edit_seller']) && !empty($_POST['edit_seller']) && isset($_POST['old_national_identity_card']) && !empty($_POST['old_national_identity_card'])) {
                    $old_national_identity_card = explode('/', $this->input->post('old_national_identity_card', true));
                    delete_images(SELLER_DOCUMENTS_PATH, $old_national_identity_card[2]);
                }

                if (!empty($id_card_files['national_identity_card']['name'])) {

                    $_FILES['temp_image']['name'] = $id_card_files['national_identity_card']['name'];
                    $_FILES['temp_image']['type'] = $id_card_files['national_identity_card']['type'];
                    $_FILES['temp_image']['tmp_name'] = $id_card_files['national_identity_card']['tmp_name'];
                    $_FILES['temp_image']['error'] = $id_card_files['national_identity_card']['error'];
                    $_FILES['temp_image']['size'] = $id_card_files['national_identity_card']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $id_card_error = 'Images :' . $id_card_error . ' ' . $other_img->display_errors();
                    } else {
                        $temp_array_id_card = $other_img->data();
                        resize_review_images($temp_array_id_card, FCPATH . SELLER_DOCUMENTS_PATH);
                        $id_card_doc  = SELLER_DOCUMENTS_PATH . $temp_array_id_card['file_name'];
                    }
                } else {
                    $_FILES['temp_image']['name'] = $id_card_files['national_identity_card']['name'];
                    $_FILES['temp_image']['type'] = $id_card_files['national_identity_card']['type'];
                    $_FILES['temp_image']['tmp_name'] = $id_card_files['national_identity_card']['tmp_name'];
                    $_FILES['temp_image']['error'] = $id_card_files['national_identity_card']['error'];
                    $_FILES['temp_image']['size'] = $id_card_files['national_identity_card']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $id_card_error = $other_img->display_errors();
                    }
                }
                //Deleting Uploaded Images if any overall error occured
                if ($id_card_error != NULL || !$this->form_validation->run()) {
                    if (isset($id_card_doc) && !empty($id_card_doc || !$this->form_validation->run())) {
                        foreach ($id_card_doc as $key => $val) {
                            unlink(FCPATH . SELLER_DOCUMENTS_PATH . $id_card_doc[$key]);
                        }
                    }
                }
            }

            if ($id_card_error != NULL) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] =  $id_card_error;
                print_r(json_encode($this->response));
                return;
            }

            //process address_proof
            $temp_array_proof = $proof_doc = array();
            $proof_files = $_FILES;
            $proof_error = "";
            $config = [
                'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                'allowed_types' => 'jpg|png|jpeg|gif',
                'max_size' => 8000,
            ];
            if (isset($proof_files['address_proof']) && !empty($proof_files['address_proof']['name']) && isset($proof_files['address_proof']['name'])) {
                $other_img = $this->upload;
                $other_img->initialize($config);

                if (isset($_POST['edit_seller']) && !empty($_POST['edit_seller']) && isset($_POST['old_address_proof']) && !empty($_POST['old_address_proof'])) {
                    $old_address_proof = explode('/', $this->input->post('old_address_proof', true));
                    delete_images(SELLER_DOCUMENTS_PATH, $old_address_proof[2]);
                }

                if (!empty($proof_files['address_proof']['name'])) {

                    $_FILES['temp_image']['name'] = $proof_files['address_proof']['name'];
                    $_FILES['temp_image']['type'] = $proof_files['address_proof']['type'];
                    $_FILES['temp_image']['tmp_name'] = $proof_files['address_proof']['tmp_name'];
                    $_FILES['temp_image']['error'] = $proof_files['address_proof']['error'];
                    $_FILES['temp_image']['size'] = $proof_files['address_proof']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $proof_error = 'Images :' . $proof_error . ' ' . $other_img->display_errors();
                    } else {
                        $temp_array_proof = $other_img->data();
                        resize_review_images($temp_array_proof, FCPATH . SELLER_DOCUMENTS_PATH);
                        $proof_doc  = SELLER_DOCUMENTS_PATH . $temp_array_proof['file_name'];
                    }
                } else {
                    $_FILES['temp_image']['name'] = $proof_files['address_proof']['name'];
                    $_FILES['temp_image']['type'] = $proof_files['address_proof']['type'];
                    $_FILES['temp_image']['tmp_name'] = $proof_files['address_proof']['tmp_name'];
                    $_FILES['temp_image']['error'] = $proof_files['address_proof']['error'];
                    $_FILES['temp_image']['size'] = $proof_files['address_proof']['size'];
                    if (!$other_img->do_upload('temp_image')) {
                        $proof_error = $other_img->display_errors();
                    }
                }
                //Deleting Uploaded Images if any overall error occured
                if ($proof_error != NULL || !$this->form_validation->run()) {
                    if (isset($proof_doc) && !empty($proof_doc || !$this->form_validation->run())) {
                        foreach ($proof_doc as $key => $val) {
                            unlink(FCPATH . SELLER_DOCUMENTS_PATH . $proof_doc[$key]);
                        }
                    }
                }
            }

            if ($proof_error != NULL) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] =  $proof_error;
                print_r(json_encode($this->response));
                return;
            }
            if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {

                /* check whether user exist or not */
                $user_id_to_seller = $this->input->post('user_id', true);
                $user = fetch_users($this->input->post('user_id', true));
                if (empty($user)) {
                    $this->response['error'] = true;
                    $this->response['message'] = "User not found!";
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['data'] = [];
                    print_r(json_encode($this->response));
                    return false;
                }
                $seller_data = array(
                    'user_id' => $this->input->post('user_id', true),
                    'address_proof' => (!empty($proof_doc)) ? $proof_doc : null,
                    'national_identity_card' => (!empty($id_card_doc)) ? $id_card_doc : null,
                    'store_logo' => (!empty($store_logo_doc)) ? $store_logo_doc : null,
                    'authorized_signature' => (!empty($authorized_signature_doc)) ? $authorized_signature_doc : $this->input->post('authorized_signature', true),
                    'pan_number' => (isset($_POST['pan_number']) && !empty($_POST['pan_number'])) ? $this->input->post('pan_number', true) : "",
                    'tax_number' => $this->input->post('tax_number', true),
                    'tax_name' => $this->input->post('tax_name', true),
                    'bank_name' => (isset($_POST['bank_name']) && !empty($_POST['bank_name'])) ? $this->input->post('bank_name', true) : "",
                    'bank_code' => (isset($_POST['bank_code']) && !empty($_POST['bank_code'])) ? $this->input->post('bank_code', true) : "",
                    'account_name' => (isset($_POST['account_name']) && !empty($_POST['account_name'])) ? $this->input->post('account_name', true) : "",
                    'account_number' => (isset($_POST['account_number']) && !empty($_POST['account_number'])) ? $this->input->post('account_number', true) : "",
                    'store_description' => (isset($_POST['store_description']) && !empty($_POST['store_description'])) ? $this->input->post('store_description', true) : "",
                    'store_url' => (isset($_POST['store_url']) && !empty($_POST['store_url'])) ? $this->input->post('store_url', true) : "",
                    'store_name' => (isset($_POST['store_name']) && !empty($_POST['store_name'])) ? $this->input->post('store_name', true) : "",
                    'slug' => create_unique_slug($this->input->post('store_name', true), 'seller_data')
                );


                if ($this->Seller_model->add_seller($seller_data)) {
                    $group_id = $this->ion_auth->get_users_groups($user_id_to_seller)->row()->id;
                    $this->ion_auth->remove_from_group($group_id, $user_id_to_seller);
                    $this->ion_auth->add_to_group('4', $user_id_to_seller);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $message = 'Seller Update Successfully';
                    $this->response['message'] = $message;
                    print_r(json_encode($this->response));
                } else {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = "Seller data was not updated";
                    print_r(json_encode($this->response));
                }
            } else {

                // An existing account on this mobile is no longer a dead end: if it isn't
                // already a seller, the seller role is added to it (ownership proven by the
                // Firebase phone token or the account's current password), so a buyer with
                // one phone number can start selling. See ajax_signup() for the same logic.
                $existing_user = fetch_details('users', ['mobile' => $_POST['mobile']]);
                if (!empty($existing_user)) {
                    $existing_id = $existing_user[0]['id'];

                    if (user_has_role($existing_id, 'seller')) {
                        $response["error"] = true;
                        $response["message"] = "This mobile number is already registered as a seller. Please log in instead.";
                    } elseif (!$this->_owns_existing_account($existing_user[0], $this->input->post('password'))) {
                        $response["error"] = true;
                        $response["message"] = "This mobile number already has an account. Enter that account's existing password to add selling to it.";
                    } else {
                        $this->_grant_seller_role(
                            $existing_id,
                            $this->input->post('name', true),
                            strtolower($this->input->post('email')),
                            $this->input->post('mobile')
                        );
                        $response["error"] = false;
                        $response["message"] = "Selling has been enabled on your existing account. Wait for approval of admin.";
                    }

                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $response["data"] = array();
                    echo json_encode($response);
                    return false;
                }

                if (!$this->form_validation->is_unique($_POST['email'], 'users.email')) {
                    $response["error"]   = true;
                    $response["message"] = "Email already exists !";
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $response["data"] = array();
                    echo json_encode($response);
                    return false;
                }

                $identity_column = $this->config->item('identity', 'ion_auth');
                $email = strtolower($this->input->post('email'));
                $mobile = $this->input->post('mobile');
                $identity = ($identity_column == 'mobile') ? $mobile : $email;
                $password = $this->input->post('password');

                $additional_data = [
                    'username' => $this->input->post('name', true),
                    'address' => $this->input->post('address', true),
                    'type' => 'phone',
                ];
                // Groups 2 + 4 - see the note in ajax_signup(): a seller also needs the
                // buyer role or the storefront login will refuse them.
                $this->ion_auth->register($identity, $password, $email, $additional_data, ['2', '4']);
                if (update_details(['active' => 1], [$identity_column => $identity], 'users')) {
                    $user_id = fetch_details('users', ['mobile' => $mobile], 'id');

                    $data = array(
                        'user_id' => $user_id[0]['id'],
                        'address_proof' => (!empty($proof_doc)) ? $proof_doc : null,
                        'national_identity_card' => (!empty($id_card_doc)) ? $id_card_doc : null,
                        'store_logo' => (!empty($store_logo_doc)) ? $store_logo_doc : null,
                        'authorized_signature' => (!empty($authorized_signature_doc)) ? $authorized_signature_doc : null,
                        'pan_number' => (isset($_POST['pan_number']) && !empty($_POST['pan_number'])) ? $this->input->post('pan_number', true) : "",
                        'tax_number' => $this->input->post('tax_number', true),
                        'tax_name' => $this->input->post('tax_name', true),
                        'bank_name' => (isset($_POST['bank_name']) && !empty($_POST['bank_name'])) ? $this->input->post('bank_name', true) : "",
                        'bank_code' => (isset($_POST['bank_code']) && !empty($_POST['bank_code'])) ? $this->input->post('bank_code', true) : "",
                        'account_name' => (isset($_POST['account_name']) && !empty($_POST['account_name'])) ? $this->input->post('account_name', true) : "",
                        'account_number' => (isset($_POST['account_number']) && !empty($_POST['account_number'])) ? $this->input->post('account_number', true) : "",
                        'store_description' => (isset($_POST['store_description']) && !empty($_POST['store_description'])) ? $this->input->post('store_description', true) : "",
                        'store_url' => (isset($_POST['store_url']) && !empty($_POST['store_url'])) ? $this->input->post('store_url', true) : "",
                        'store_name' => (isset($_POST['store_name']) && !empty($_POST['store_name'])) ? $this->input->post('store_name', true) : "",
                        'slug' => create_unique_slug($this->input->post('store_name', true), 'seller_data')
                    );
                    $insert_id = $this->Seller_model->add_seller($data);
                    if (!empty($insert_id)) {
                        $this->response['error'] = false;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'Seller registared Successfully. Wait for aprooval of admin.';
                        print_r(json_encode($this->response));
                    } else {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = "Seller data was not added";
                        print_r(json_encode($this->response));
                    }
                } else {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $message = (isset($_POST['edit_seller'])) ? 'Seller not Updated' : 'Seller not Registared.';
                    $this->response['message'] = $message;
                    print_r(json_encode($this->response));
                }
            }
        }
    }

    public function verify_account()
    {
        $identity_column = $this->config->item('identity', 'ion_auth');
        $identity = $this->input->post('identity', true);
        $this->form_validation->set_rules('identity', 'Mobile', 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
        if ($this->form_validation->run()) {
            $res = $this->db->select('id,mobile,username')->where($identity_column, $identity)->get('users')->result_array();
            if (!empty($res)) {
                // exiting user  
                if ($this->ion_auth_model->in_group('seller', $res[0]['id'])) {
                    // already seller
                    $response['error'] = false;
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $response['message'] = "This user is already seller please do login";
                    $response['data'] = array();
                    $response['redirect'] = 1;
                    echo json_encode($response);
                } else {
                    // already user
                    $this->session->set_flashdata('to_be_seller_name', $res[0]['username']);
                    $this->session->set_flashdata('to_be_seller_mobile', $res[0]['mobile']);
                    $this->session->set_flashdata('to_be_seller_id', $res[0]['id']);
                    $response['error'] = false;
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $response['message'] = "Already user";
                    $response['data'] = array();
                    $response['redirect'] = 3;
                    echo json_encode($response);
                }
            } else {
                // no user
                $response['error'] = true;
                $response['csrfName'] = $this->security->get_csrf_token_name();
                $response['csrfHash'] = $this->security->get_csrf_hash();
                $response['message'] = "redirect to new registration";
                $response['data'] = array();
                $response['redirect'] = 2;
                echo json_encode($response);
            }
        } else {
            $response['error'] = true;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = validation_errors();
            $response['data'] = array();
            $response['redirect'] = 0;
            echo json_encode($response);
        }
    }


    public function update_user()
    {
        // This had NO auth gate at all. It resolves the target account purely from
        // the caller's own session identity and then updates username/email and
        // (optionally) the password on it - so any logged-in NON-seller (e.g. a plain
        // customer) could call this seller-panel endpoint and mutate their own
        // credentials through it, and a guest hit a fatal on $user->id below rather
        // than a clean rejection.
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            $response['error'] = true;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = 'Unauthorized';
            echo json_encode($response);
            return false;
        }

        $identity_column = $this->config->item('identity', 'ion_auth');
        $identity = $this->session->userdata('identity');
        $user = $this->ion_auth->user()->row();
        if ($identity_column == 'email') {
            $this->form_validation->set_rules('email', 'Email', 'required|xss_clean|trim|valid_email|edit_unique[users.email.' . $user->id . ']');
        } else {
            $this->form_validation->set_rules('mobile', 'Mobile', 'required|xss_clean|trim|numeric|edit_unique[users.mobile.' . $user->id . ']');
        }
        $this->form_validation->set_rules('username', 'Username', 'required|xss_clean|trim');

        if (!empty($_POST['old']) || !empty($_POST['new']) || !empty($_POST['new_confirm'])) {
            $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
            $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
            $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');
        }


        $tables = $this->config->item('tables', 'ion_auth');
        if (!$this->form_validation->run()) {
            if (validation_errors()) {
                $response['error'] = true;
                $response['csrfName'] = $this->security->get_csrf_token_name();
                $response['csrfHash'] = $this->security->get_csrf_hash();
                $response['message'] = validation_errors();
                echo json_encode($response);
                return false;
                exit();
            }
            if ($this->session->flashdata('message')) {
                $response['error'] = false;
                $response['csrfName'] = $this->security->get_csrf_token_name();
                $response['csrfHash'] = $this->security->get_csrf_hash();
                $response['message'] = $this->session->flashdata('message');
                echo json_encode($response);
                return false;
                exit();
            }
        } else {

            if (!empty($_POST['old']) || !empty($_POST['new']) || !empty($_POST['new_confirm'])) {
                if (!$this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'))) {
                    $response['error'] = true;
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $response['message'] = $this->ion_auth->errors();
                    echo json_encode($response);
                    return;
                    exit();
                }
            }
            $set = ['username' => $this->input->post('username'), 'email' => $this->input->post('email')];
            $set = escape_array($set);
            $this->db->set($set)->where($identity_column, $identity)->update($tables['login_users']);
            $response['error'] = false;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = 'Profile Update Succesfully';
            echo json_encode($response);
            return;
        }
    }
    public function auth()
    {
        $identity_column = $this->config->item('identity', 'ion_auth');
        $identity = $this->input->post('identity', true);
        $this->form_validation->set_rules('identity', 'Email', 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
        $res = $this->db->select('id')->where($identity_column, $identity)->get('users')->result_array();
        if ($this->form_validation->run()) {
            if (!empty($res)) {
                if ($this->ion_auth_model->in_group('seller', $res[0]['id'])) {
                    $remember = (bool)$this->input->post('remember');
                    if ($this->ion_auth->login($this->input->post('identity', true), $this->input->post('password', true), $remember)) {
                        //if the login is successful
                        $response['error'] = false;
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response['message'] = $this->ion_auth->messages();
                        echo json_encode($response);
                    } else {
                        // if the login was un-successful
                        $response['error'] = true;
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response['message'] = $this->ion_auth->errors();
                        echo json_encode($response);
                    }
                } else {
                    $response['error'] = true;
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $response['message'] = ucfirst($identity_column) . ' field is not correct';
                    echo json_encode($response);
                }
            } else {
                $response['error'] = true;
                $response['csrfName'] = $this->security->get_csrf_token_name();
                $response['csrfHash'] = $this->security->get_csrf_hash();
                $response['message'] = '' . ucfirst($identity_column) . ' field is not correct';
                echo json_encode($response);
            }
        } else {
            $response['error'] = true;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = validation_errors();
            echo json_encode($response);
        }
    }

    public function forgot_password()
    {
        $this->data['main_page'] = FORMS . 'forgot-password';
        $this->data['title'] = 'Forget Password | Seller Panel';
        $this->data['meta_description'] = 'Ekart';
        $this->data['logo'] = get_settings('logo');
        $this->load->view('seller/login', $this->data);
    }
    public function check_phone()
{
    
    $this->response = [
        'error'   => false,
        'message' => 'Phone is valid',
        'data'    => []
    ];

    // Read POST JSON data
    $phone = $this->input->post('phone', true);

    if (empty($phone)) {
        $this->response['error'] = true;
        $this->response['message'] = 'Phone is required';
        echo json_encode($this->response);
        return;
    }

    // DB check
    $exists = $this->db
        ->where('mobile', $phone)
        ->get('users')
        ->row();

    if ($exists) {
        $this->response['error'] = true;
        $this->response['message'] = 'Phone already exists';
    }

    echo json_encode($this->response);
    }
    public function check_email()
    {
        $this->response = [
            'error'   => false,
            'message' => 'Email is valid',
            'data'    => []
        ];

        // Read POST JSON data
       $email = $this->input->post('email', true);

        if (empty($email)) {
            $this->response['error'] = true;
            $this->response['message'] = 'Email is required';
            echo json_encode($this->response);
            return;
        }

        // DB check
        $exists = $this->db
            ->where('email', $email)
            ->get('users')
            ->row();

        if ($exists) {
            $this->response['error'] = true;
            $this->response['message'] = 'Email already exists';
        }

        echo json_encode($this->response);
    }
}
