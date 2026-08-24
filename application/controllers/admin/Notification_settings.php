<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Notification_settings extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['Setting_model', 'notification_model', 'category_model']);
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'notification_setting')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            $this->data['main_page'] = FORMS . 'notification-settings';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Update Notification Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Update Notification Settings  | ' . $settings['app_name'];
            $this->data['fcm_server_key'] = get_settings('fcm_server_key');
            $this->data['vap_id_Key'] = get_settings('vap_id_Key');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function manage_notifications()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'send_notification')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'manage-notifications';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Send Notification | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Send Notification | ' . $settings['app_name'];
            $this->data['categories'] = $this->category_model->get_categories();
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_notification_list()
    {
        // Intentionally NOT restricted to is_admin() - this same endpoint backs the
        // customer-facing "My Account > Notifications" page (front-end/*/pages/notifications.php),
        // so a logged-in customer legitimately needs to reach it too.
        //
        // What WAS wrong is that both audiences got the same unfiltered result set: a customer
        // saw every notification ever sent to anyone, including messages the admin had targeted
        // at one specific named user. A non-admin viewer is now scoped to their own recipient id
        // (broadcasts plus anything addressed to them); admins still see the full log.
        if (!$this->ion_auth->logged_in()) {
            redirect('admin/login', 'refresh');
            return;
        }

        $for_user_id = $this->ion_auth->is_admin() ? null : (int) $this->session->userdata('user_id');

        return $this->notification_model->get_notification_list(0, 10, 'id', 'ASC', $for_user_id);
    }
    public function get_notifications_data()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->notification_model->get_notifications_data();
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function manage_system_notifications()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'send_notification')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'manage-system-notification';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'System Notification | ' . $settings['app_name'];
            $this->data['meta_description'] = ' System Notification | ' . $settings['app_name'];

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function delete_notification()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'send_notification'), PERMISSION_ERROR_MSG, 'send_notification', false)) {
                return true;
            }

            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $response['error'] = true;
                $response['message'] = 'Invalid notification id';
                echo json_encode($response);
                return false;
            }

            if (delete_details(['id' => (int) $_GET['id']], 'notifications')) {
                $response['error'] = false;
                $response['message'] = 'Deleted Succesfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something Went Wrong';
            }
            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function update_notification_settings()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'notification_setting')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            if (print_msg(!has_permissions('update', 'notification_setting'), PERMISSION_ERROR_MSG, 'notification_setting')) {
                return false;
            }

            $this->form_validation->set_rules('fcm_server_key', 'Fcm Server Key', 'trim|required|xss_clean');
            $this->form_validation->set_rules('vap_id_Key', 'Vap Id Key', 'trim|required|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $updated = $this->Setting_model->update_fcm_details($_POST);
                $updated = $this->Setting_model->update_vapkey($_POST) && $updated;
                $this->response['error'] = !$updated;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $updated ? 'System Setting Updated Successfully' : 'Something went wrong.';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /**
     * Resolves the FCM device tokens for an audience.
     *
     * Push is a best-effort side channel here, NOT the notification itself - see
     * send_notifications(). This only decides who gets a device push on top of the in-app
     * record that is always written.
     */
    private function fcm_tokens_for($send_to, $user_ids = [])
    {
        $tokens = [];

        if ($send_to === 'specific_user') {
            if (empty($user_ids)) {
                return [];
            }
            // Was looping with an off-by-one bound and, on every matching iteration, re-merging
            // the ENTIRE result array (empty/'NULL' fcm_id rows included) instead of appending
            // the one qualifying row - so the filter never actually filtered anything.
            $rows = fetch_details('users', null, 'fcm_id', 10000, 0, '', '', 'id', $user_ids);
        } elseif ($send_to === 'all_sellers' || $send_to === 'all_customers') {
            // Role-targeted: only devices belonging to that group.
            $group = ($send_to === 'all_sellers') ? 'seller' : 'members';
            $rows = $this->db->select('u.fcm_id')
                ->join('users_groups ug', 'ug.user_id = u.id', 'inner')
                ->join('groups g', 'g.id = ug.group_id', 'inner')
                ->where('g.name', $group)
                ->get('users u')
                ->result_array();
        } else {
            // Everybody: registered app devices plus every user row holding a token.
            $rows = array_merge(
                fetch_details('user_fcm', NULL, 'fcm_id'),
                $this->db->select('fcm_id')->get('users')->result_array()
            );
        }

        foreach ($rows as $row) {
            if (!empty($row['fcm_id']) && $row['fcm_id'] !== 'NULL') {
                $tokens[] = $row['fcm_id'];
            }
        }
        return array_values(array_unique($tokens));
    }

    public function send_notifications()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('create', 'send_notification'), PERMISSION_ERROR_MSG, 'send_notification')) {
                return false;
            }
            $is_image_included = (isset($_POST['image_checkbox']) && $_POST['image_checkbox'] == 'on') ? TRUE : FALSE;
            if ($is_image_included) {
                $this->form_validation->set_rules('image', 'Image', 'trim|required|xss_clean', array('required' => 'Image is required'));
            }
            $this->form_validation->set_rules('title', 'Title', 'trim|required|xss_clean');
            // send_to was only checked 'required', so any string at all was written to the
            // column - and an unrecognised audience is treated as "everyone" by the inbox
            // scope, which is the worst possible way to get it wrong.
            $this->form_validation->set_rules('send_to', 'Send To', 'trim|required|xss_clean|in_list[all_users,all_customers,all_sellers,specific_user]');
            $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
            $this->form_validation->set_rules('message', 'Message', 'trim|required|xss_clean');

            if (isset($_POST['type']) && $_POST['type'] == 'categories') {
                $this->form_validation->set_rules('category_id', 'Category', 'trim|required|xss_clean');
            }

            if (isset($_POST['type']) && $_POST['type'] == 'products') {
                $this->form_validation->set_rules('product_id', 'Product', 'trim|required|xss_clean');
            }
            if (isset($_POST['type']) && $_POST['type'] == 'notification_url') {
                $this->form_validation->set_rules('link', 'Link', 'trim|required|xss_clean');
            }
            if (isset($_POST['send_to']) && $_POST['send_to'] == 'specific_user') {
                // send to specific user
                $this->form_validation->set_rules('select_user_id[]', 'User', 'trim|required|xss_clean', ["required" => "Please select atleast one user"]);
            }

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                print_r(json_encode($this->response));
                return;
            }

            $data     = $this->input->post(null, true);
            $title    = $this->input->post('title', true);
            $send_to  = $this->input->post('send_to', true);
            $type     = $this->input->post('type', true);
            $message  = $this->input->post('message', true);
            $type_ids = '';
            if ($type === 'categories') {
                $type_ids = $this->input->post('category_id', true);
            } elseif ($type === 'products') {
                $type_ids = $this->input->post('product_id', true);
            }

            $user_ids = ($send_to === 'specific_user') ? (array) $this->input->post('select_user_id', true) : [];
            if ($send_to === 'specific_user') {
                $data['select_user_id'] = json_encode(array_values(array_map('strval', $user_ids)));
            }
            if ($is_image_included) {
                $data['image'] = $_POST['image'];
            }

            /* ------------------------------------------------------------------------------
             * Write the in-app notification FIRST. This is the whole point of the feature and
             * it used to be the part that never happened.
             *
             * The method previously aborted before this point with "No FCM Key Found" whenever
             * push was unconfigured (the shipped default IS unconfigured - the key is the
             * literal string "your_fcm_server_key"), and aborted again with "There is no users
             * to send notification" whenever no account held a device token. fcm_id is only
             * ever written by the mobile app, so on a website-only deployment that second
             * condition is ALWAYS true. Between the two, an admin could not deliver a single
             * notification to the site, and nothing was recorded either - the compose form
             * reported an error and the message vanished.
             *
             * Push is now what it should always have been: a best-effort extra on top of a
             * record that is always saved and always readable in the buyer/seller panels.
             * ---------------------------------------------------------------------------- */
            if (!$this->notification_model->add_notification($data)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Something went wrong saving the notification.';
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                echo json_encode($this->response);
                return;
            }

            /* ---- best-effort push ---- */
            $fcm_key   = get_settings('fcm_server_key');
            $push_note = '';
            $fcmFields = ['notification' => [], 'data' => []];

            $fcmMsg = array(
                'content_available' => true,
                'title'        => (string) $title,
                'body'         => (string) $message,
                'image'        => $is_image_included ? base_url() . $_POST['image'] : '',
                'type'         => (string) $type,
                'type_id'      => (string) $type_ids,
                'link'         => (isset($data['link']) && !empty($data['link'])) ? $data['link'] : '',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            );

            if (empty($fcm_key) || $fcm_key === 'your_fcm_server_key') {
                $push_note = ' Push notifications are not configured, so app devices were not alerted.';
            } else {
                $registrationIDs = $this->fcm_tokens_for($send_to, $user_ids);
                if (empty($registrationIDs)) {
                    $push_note = ' No app devices are registered for this audience, so only the in-app notification was delivered.';
                } else {
                    $fcmFields = send_notification($fcmMsg, array_chunk($registrationIDs, 1000));
                    $push_note = ' Pushed to ' . count($registrationIDs) . ' device(s).';
                }
            }

            $audience_label = [
                'all_users'     => 'all users',
                'all_customers' => 'all customers',
                'all_sellers'   => 'all sellers',
                'specific_user' => count($user_ids) . ' selected user(s)',
            ];

            $this->response['notification'] = isset($fcmFields['notification']) ? $fcmFields['notification'] : [];
            $this->response['data'] = isset($fcmFields['data']) ? $fcmFields['data'] : [];
            $this->response['error'] = false;
            $this->response['message'] = 'Notification sent to ' . (isset($audience_label[$send_to]) ? $audience_label[$send_to] : $send_to) . '.' . $push_note;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            echo json_encode($this->response);
            return;
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function delete_system_notification()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'send_notification'), PERMISSION_ERROR_MSG, 'send_notification', false)) {
                return true;
            }

            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $response['error'] = true;
                $response['message'] = 'Invalid notification id';
                echo json_encode($response);
                return false;
            }

            if (delete_details(['id' => (int) $_GET['id']], 'system_notification')) {
                $response['error'] = false;
                $response['message'] = 'Deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something Went Wrong';
            }
            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function mark_notification_read()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                echo json_encode(['error' => true, 'message' => 'Invalid notification id']);
                return false;
            }
            return $this->notification_model->mark_notification_read((int) $_GET['id']);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function mark_all_as_read()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->notification_model->mark_all_as_read();
        } else {
            $response_data['error'] =  true;
            $response_data['message'] =  'You are not authorized to perform this action.';
            print_r(json_encode($response_data));
        }
    }
}
