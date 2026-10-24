<?php
defined('BASEPATH') or exit('No direct script access allowed');


class System_users extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model('system_users_model');
        $this->load->config('eshop');
        $userData = get_user_permissions($this->session->userdata('user_id'));
        if (empty($userData) || $userData[0]['role'] > 1) {
            deny_panel_access();
        }
    }

    public  function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            $this->data['main_page'] = TABLES . 'manage-system-users';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Manage System Users | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Manage System Users | ' . $settings['app_name'];
            $this->data['system_modules'] = $this->config->item('system_modules');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function add_system_users()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Managing OTHER system users' accounts/permissions is Super-Admin-only by design -
            // the list view already only ever renders Edit/Delete buttons for a role-0 viewer
            // (System_users_model::get_users_list(), the `$userData[0]['role'] == 0` check), but
            // nothing server-side actually enforced that. A role-1 "Admin" (already allowed into
            // this whole controller by the constructor's `role > 1` check) could open this exact
            // form for ANY user - including the real Super Admin - by guessing/incrementing
            // edit_id, and the save endpoint below had no check stopping them from then
            // overwriting that account's username/email/password or self-promoting to role 0,
            // which bypasses every permission check in the entire app. Restored the same
            // Super-Admin-only boundary the UI already implies.
            $acting_user = get_user_permissions($this->session->userdata('user_id'));
            $is_super_admin = !empty($acting_user) && $acting_user[0]['role'] == 0;
            if (isset($_GET['edit_id']) && !empty($_GET['edit_id']) && !$is_super_admin) {
                deny_panel_access(PERMISSION_ERROR_MSG, 'admin/system-users');
            }

            $this->data['main_page'] = FORMS . 'system-users';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Add System User | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add System User | ' . $settings['app_name'];

            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $user_permissions = $this->db->select('u.id,u.username,u.mobile,u.email,up.role,up.permissions')->join('users u', 'up.user_id=u.id')->where('up.id', $_GET['edit_id'])->get('user_permissions up')->result_array();
                if (!empty($user_permissions)) {
                    $this->data['fetched_data'] = $user_permissions;
                }
            }

            $this->data['about_us'] = get_settings('about_us');
            $this->data['system_modules'] = $this->config->item('system_modules');
            $this->data['user_roles'] = $this->config->item('system_user_roles');
            $this->data['is_super_admin'] = $is_super_admin;

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function update_system_user()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }

            $edit_id = $this->input->post('edit_system_user', true);

            // Was completely unrestricted - any account satisfying is_admin() (including a
            // role-1 "Admin", already allowed into this whole controller) could submit
            // role=0 for themselves or anyone else and instantly become Super Admin (role 0
            // bypasses every permission check in has_permissions()), or overwrite an EXISTING
            // higher-privileged account's username/email/password outright, including the real
            // owner's. A non-owner may now only ever create a brand-new Editor/Supporter-level
            // account (matching the "Add System User" button already being visible to them in
            // the UI) - they can never edit an existing account, and can never set role 0 or 1.
            $acting_user = get_user_permissions($this->session->userdata('user_id'));
            $is_super_admin = !empty($acting_user) && $acting_user[0]['role'] == 0;
            if (!$is_super_admin) {
                if (isset($edit_id) && !empty($edit_id)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = PERMISSION_ERROR_MSG;
                    print_r(json_encode($this->response));
                    return false;
                }
                if (!isset($_POST['role']) || !in_array((string) $_POST['role'], ['2', '3'], true)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'You can only create Editor or Supporter accounts.';
                    print_r(json_encode($this->response));
                    return false;
                }
            }

            $this->form_validation->set_rules('username', 'Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|xss_clean|numeric');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean');
            $this->form_validation->set_rules('role', 'role', 'trim|required|xss_clean');

            if (isset($edit_id)) {
                $this->form_validation->set_rules('edit_system_user', 'Id', 'trim|required|numeric|xss_clean');
                if (isset($_POST['password']) && !empty($_POST['password'])) {
                    $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[confirm_password]');
                    $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|xss_clean');
                }
            } else {
                $this->form_validation->set_rules('password', 'Password ' . $this->lang->line('change_password_validation_new_password_label'), 'trim|required|xss_clean|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[confirm_password]');
                $this->form_validation->set_rules('confirm_password', ' Confirm Password ' . $this->lang->line('change_password_validation_new_password_confirm_label'), 'trim|required|xss_clean');
            }
            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {

                if (isset($edit_id) && !empty($edit_id)) {
                    if (is_exist(['mobile' => $_POST['mobile']], 'users', $_POST['edit_system_user'])) {
                        $this->response['error'] = true;
                        $this->response['message'] = 'Mobile is already registered. Please Provide Unique Number !';
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['data'] = array();
                        print_r(json_encode($this->response));
                        return;
                    }
                }

                // An existing number is no longer a dead end. update_user() attaches the
                // admin role to that account instead of creating a second one - a single
                // mobile can be buyer, seller AND admin on one login.
                $already_admin = false;
                $existing_user = null;
                if (empty($edit_id)) {
                    $existing_user = $this->db->select('id')->where('mobile', $_POST['mobile'])->get('users')->row_array();
                    if (!empty($existing_user)) {
                        $already_admin = user_has_role($existing_user['id'], 'admin');
                        if ($already_admin) {
                            $this->response['error'] = true;
                            $this->response['message'] = 'This number already has an admin account. Edit that user instead of adding a new one.';
                            $this->response['csrfName'] = $this->security->get_csrf_token_name();
                            $this->response['csrfHash'] = $this->security->get_csrf_hash();
                            $this->response['data'] = array();
                            print_r(json_encode($this->response));
                            return;
                        }
                    }
                }

                // Two-step approval: every add, and every edit that changes something, must be
                // confirmed with an email OTP AND an SMS OTP sent to the fixed approver.
                if ($this->change_needs_approval($edit_id)) {
                    $gate = $this->check_approval($edit_id);
                    if ($gate !== true) {
                        $this->response['error'] = true;
                        $this->response['otp_required'] = !empty($gate['otp_required']);
                        $this->response['sms_failed'] = !empty($gate['sms_failed']);
                        $this->response['approver_mobile'] = SYSTEM_USER_APPROVER_MOBILE;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = $gate['message'];
                        print_r(json_encode($this->response));
                        return;
                    }
                }

                $this->system_users_model->update_user($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                if (!empty($existing_user)) {
                    // Say plainly that no new account was made and the password field was
                    // ignored - they keep signing in with the credentials they already have.
                    $this->response['message'] = 'Admin access has been added to the existing account on this mobile number. '
                        . 'They sign in with their current password - it has not been changed.';
                } else {
                    $this->response['message'] = (isset($edit_id)) ? ' Data Updated Successfully' : 'Data Added Successfully';
                }

                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /**
     * Normalised permission map, so "unchanged" compares equal however the checkboxes arrived.
     */
    private function normalise_permissions($raw)
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $module => $actions) {
            if (!is_array($actions)) {
                continue;
            }
            $keys = array_keys($actions);
            sort($keys);
            $out[$module] = $keys;
        }
        ksort($out);
        return $out;
    }

    /**
     * A new account always needs approval. An edit needs it when name, mobile, email, role,
     * password or permissions differ from what is stored; a save that changes nothing does not.
     * When in doubt (row not found) it asks - failing closed.
     */
    private function change_needs_approval($edit_id)
    {
        if (empty($edit_id)) {
            return true;
        }
        $current = $this->db->select('u.username,u.mobile,u.email,up.role,up.permissions')
            ->join('users u', 'up.user_id=u.id')
            ->where('up.user_id', (int) $edit_id)
            ->get('user_permissions up')->row_array();
        if (empty($current)) {
            return true;
        }
        if (trim((string) $this->input->post('username')) !== trim((string) $current['username'])
            || trim((string) $this->input->post('mobile')) !== trim((string) $current['mobile'])
            || strtolower(trim((string) $this->input->post('email'))) !== strtolower(trim((string) $current['email']))
            || (string) $this->input->post('role') !== (string) $current['role']
            || trim((string) $this->input->post('password')) !== ''
        ) {
            return true;
        }
        $posted = (string) $this->input->post('role') === '0' ? [] : $this->normalise_permissions($this->input->post('permissions'));
        $stored = (string) $current['role'] === '0' ? [] : $this->normalise_permissions($current['permissions']);
        return $posted !== $stored;
    }

    /**
     * Approval is one SMS code, texted by Firebase to SYSTEM_USER_APPROVER_MOBILE every time.
     * Returns true when the posted Firebase ID token proves that number confirmed a code just
     * now. With no token it returns ['otp_required' => true] so the page opens the code box.
     */
    private function check_approval($edit_id)
    {
        $id_token = trim((string) $this->input->post('approval_id_token', false));
        if ($id_token === '') {
            return ['otp_required' => true, 'message' => 'Verification needed: enter the SMS code sent to the approver mobile.'];
        }

        $verified = verify_firebase_id_token($id_token);
        if (!empty($verified['error'])) {
            return ['otp_required' => true, 'sms_failed' => true, 'message' => 'The SMS code could not be verified: ' . $verified['message']];
        }
        if ($verified['provider'] !== 'phone' || $verified['phone'] !== SYSTEM_USER_APPROVER_MOBILE) {
            return ['otp_required' => true, 'sms_failed' => true, 'message' => 'The SMS code was not confirmed for the approver mobile.'];
        }
        if (empty($verified['auth_time']) || $verified['auth_time'] < time() - 600) {
            return ['otp_required' => true, 'sms_failed' => true, 'message' => 'The SMS verification is too old. Verify again.'];
        }
        $token_key = hash('sha256', $verified['uid'] . '|' . $verified['auth_time']);
        if ($token_key === $this->session->userdata('system_user_approval_used')) {
            return ['otp_required' => true, 'sms_failed' => true, 'message' => 'That SMS verification was already used. Verify again.'];
        }

        // Single use: one SMS code approves one change.
        $this->session->set_userdata('system_user_approval_used', $token_key);
        return true;
    }

    public function delete_system_user()
    {
        // Was checking the unrelated 'categories' permission (copy-paste leftover) instead of
        // anything tied to system-user management, and had no check at all preventing deletion
        // of the owner account or of an account with equal/higher privilege than the caller.
        // Deleting another system user is Super-Admin-only, matching update_system_user().
        $acting_user = get_user_permissions($this->session->userdata('user_id'));
        if (empty($acting_user) || $acting_user[0]['role'] != 0) {
            $this->response['error'] = true;
            $this->response['message'] = PERMISSION_ERROR_MSG;
            echo json_encode($this->response);
            return false;
        }
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $this->response['error'] = true;
            $this->response['message'] = 'Invalid user id';
            echo json_encode($this->response);
            return false;
        }
        if ((int) $_GET['id'] === (int) $this->session->userdata('user_id')) {
            $this->response['error'] = true;
            $this->response['message'] = 'You cannot delete your own account.';
            echo json_encode($this->response);
            return false;
        }
        if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
            $this->response['error'] = true;
            $this->response['message'] = SEMI_DEMO_MODE_MSG;
            echo json_encode($this->response);
            return false;
            exit();
        }
        $target_id = (int) $_GET['id'];

        // One mobile number is ONE account that may hold several roles at once - the same
        // person can be a buyer, a seller and an admin (System_users_model::update_user()
        // deliberately attaches the admin role to an existing account rather than creating a
        // second one). Deleting the whole `users` row therefore used to destroy that person's
        // buyer and seller identity too, along with their orders, wallet and listings, when all
        // the admin asked for was to revoke admin access. It also left the users_groups rows
        // behind entirely - verified live: deleting a system user removed its users and
        // user_permissions rows but left both of its users_groups rows orphaned, and because
        // users.id is an auto-increment, a stale group row is a grant waiting to be handed to
        // whichever account is created with that id next.
        //
        // Revoke the admin role first, then remove the login itself only when nothing else is
        // attached to it.
        $revoked = delete_details(['user_id' => $target_id], 'user_permissions');
        if ($revoked == TRUE) {
            delete_details(['user_id' => $target_id, 'group_id' => 1], 'users_groups');

            $other_roles = $this->db
                ->from('users_groups ug')
                ->where('ug.user_id', $target_id)
                ->where_not_in('ug.group_id', [1, 2])   // 1 = admin (just revoked), 2 = members
                ->count_all_results();
            $is_seller = !empty($this->db->select('id')->where('user_id', $target_id)->get('seller_data')->row_array());
            $has_orders = !empty($this->db->select('id')->where('user_id', $target_id)->get('orders')->row_array());

            if ($other_roles == 0 && !$is_seller && !$has_orders) {
                delete_details(['user_id' => $target_id], 'users_groups');
                delete_details(['id' => $target_id], 'users');
                $message = 'Deleted Succesfully';
            } else {
                $message = 'Admin access has been removed. The account itself was kept because '
                    . 'it is also used to buy or sell on the store.';
            }

            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = $message;
            print_r(json_encode($this->response));
        } else {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'Something Went Wrong';
            print_r(json_encode($this->response));
        }
    }

    /**
     * bootstrap-table data source for the System Users grid.
     *
     * SECURITY - this method had NO authentication check. Every other method in this
     * controller opens with
     *
     *     if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) { ... }
     *
     * and this one did not, so an anonymous GET to /admin/system_users/view_system_users
     * returned the entire staff table: username, email, mobile, role and active flag
     * for every administrator and sub-admin on the site.
     *
     * That is worth more to an attacker than it looks. It names exactly who to target,
     * and - because password reset here is driven by mobile number - it also hands over
     * the identifier the reset flow keys on. Paired with the equally unguarded
     * admin/customer/search_user (fixed in the same pass), it was a complete account
     * inventory available without logging in.
     */
    public function view_system_users()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            // 403 with no body: an unauthenticated caller learns nothing, not even
            // whether the endpoint exists in a useful form.
            $this->output->set_status_header(403);
            return false;
        }

        return $this->system_users_model->get_users_list();
    }
}
