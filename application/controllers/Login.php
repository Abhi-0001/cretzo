<?php defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->lang->load('auth');
        $this->data['is_logged_in'] = ($this->ion_auth->logged_in()) ? 1 : 0;
        $this->data['user'] = ($this->ion_auth->logged_in()) ? $this->ion_auth->user()->row() : array();
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        $this->data['settings'] = get_settings('system_settings', true);
    }

    public function login_check()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if (!$this->ion_auth->logged_in()) {
            $this->data['main_page'] = 'home';
            $this->data['title'] = 'Login Panel | ' . $this->data['settings']['app_name'];
            $this->data['meta_description'] = 'Login Panel | ' . $this->data['settings']['app_name'];

            $identity_column = $this->config->item('identity', 'ion_auth');
            if ($identity_column == 'mobile') {
                $this->form_validation->set_rules('mobile', 'Mobile', 'trim|numeric|required|xss_clean');
            } elseif ($identity_column == 'email') {
                $this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');
            } else {
                $this->form_validation->set_rules('identity', 'Identity', 'trim|required|xss_clean');
            }
            $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');

            $login = $this->ion_auth->login($this->input->post('mobile'), $this->input->post('password'));
            if ($login) {
                $data = fetch_details('users', ['mobile' => $this->input->post('mobile', true)]);
                // Unlike Home::login(), this AJAX login-check endpoint performed no group
                // filtering at all - any active user (including a seller/admin account)
                // whose mobile+password matched would be logged in here. Matches the
                // group-2 (customer) restriction Home::login() already enforces.
                if (!$this->ion_auth_model->in_group(2, $data[0]['id'])) {
                    $this->ion_auth->logout();
                    $this->response['error'] = true;
                    $this->response['message'] = 'Invalid user';
                    echo json_encode($this->response);
                    return false;
                }
                $username = $this->session->set_userdata('username', $data[0]['username']);
                $this->response['error'] = false;
                $this->response['message'] = 'Login Succesfully';
                echo json_encode($this->response);
                return false;
            } else {
                $this->response['error'] = true;
                $this->response['message'] = 'Mobile Number or Password is wrong.';
                echo json_encode($this->response);
                return false;
            }
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'You are already logged in.';
            echo json_encode($this->response);
            return false;
        }
    }

    public function logout()
    {
        $this->ion_auth->logout();
        redirect('home', 'refresh');
    }

    public function update_user()
    {

        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $this->response['error'] = true;
            $this->response['message'] = DEMO_VERSION_MSG;
            echo json_encode($this->response);
            return false;
        }

        // if (!has_permissions('update', 'profile')) {
        //     $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
        //     redirect('admin/home', 'refresh');
        // }

        $identity_column = $this->config->item('identity', 'ion_auth');
        // $identity = $this->session->userdata('identity');
        $user_id = $_SESSION['user_id'];
        $identity_col = fetch_details('users', ['id' => $user_id], ['mobile', 'email']);
        // print_r($_SESSION);
        // print_r($identity_column);
        $identity = $identity_col[0]['mobile'];
        $user = $this->ion_auth->user()->row();
        if ($identity_column == 'email') {
            $this->form_validation->set_rules('email', 'Email', 'required|xss_clean|trim|valid_email|edit_unique[users.email.' . $user->id . ']');
        } else {
            // `numeric` alone accepted a number of ANY length - and floats and signs with it -
            // so the profile form happily saved a 30-digit "mobile number". Every mobile this
            // store deals with is a 10-digit Indian number: signup, the Firebase OTP step and
            // the seller registration form all already require exactly that.
            $this->form_validation->set_rules(
                'mobile',
                'Mobile',
                'required|xss_clean|trim|regex_match[/^[0-9]{10}$/]|edit_unique[users.mobile.' . $user->id . ']',
                ['regex_match' => 'The Mobile number must be exactly 10 digits.']
            );
        }
        $this->form_validation->set_rules('username', 'Username', 'required|xss_clean|trim');

        // Both optional: an empty submission clears the stored value rather than failing.
        $this->form_validation->set_rules(
            'gender',
            'Gender',
            'xss_clean|trim|in_list[,male,female,other]',
            ['in_list' => 'Please choose a valid Gender.']
        );
        $this->form_validation->set_rules('dob', 'Date of Birth', 'xss_clean|trim|callback_valid_dob');

        if (!empty($_POST['old']) || !empty($_POST['new']) || !empty($_POST['new_confirm'])) {
            $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
            $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
            $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');
        }


        $tables = $this->config->item('tables', 'ion_auth');
        if (!$this->form_validation->run()) {
            if (validation_errors()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                echo json_encode($this->response);
                return false;
                exit();
            }
            if ($this->session->flashdata('message')) {
                $this->response['error'] = false;
                $this->response['message'] = $this->session->flashdata('message');
                echo json_encode($this->response);
                return false;
                exit();
            }
        } else {

            if (!empty($_POST['old']) || !empty($_POST['new']) || !empty($_POST['new_confirm'])) {
                if (!$this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'))) {
                    // if the login was un-successful
                    $this->response['error'] = true;
                    $this->response['message'] = $this->ion_auth->errors();
                    echo json_encode($this->response);
                    return false;
                }
            }
            $user_details = [
                'username' => $this->input->post('username'),
                'email'    => $this->input->post('email'),
                'mobile'   => $this->input->post('mobile'),
                // Optional fields. Posted-but-empty is stored as NULL so "not answered" stays
                // distinguishable from an answer, and clearing the field actually clears it.
                'gender'   => ($this->input->post('gender', true) !== '' && $this->input->post('gender', true) !== null)
                    ? strtolower($this->input->post('gender', true)) : null,
                'dob'      => ($this->input->post('dob', true) !== '' && $this->input->post('dob', true) !== null)
                    ? $this->input->post('dob', true) : null,
            ];

            $new_image = $this->_upload_profile_image($user_id);
            if ($new_image === false) {
                // _upload_profile_image() has already emitted the JSON error.
                return false;
            }
            if ($new_image !== null) {
                $user_details['image'] = $new_image;
            }

            // NOT escape_array(): every value here is written through CodeIgniter's query
            // builder, which parameter-escapes on its own, so pre-escaping added a layer of
            // backslashes that COMPOUNDED on each save - a username of "D'Souza" came back as
            // "D\'Souza", then "D\\\'Souza". This view renders the username raw, so no
            // read-side unescaping was masking it either.
            $this->db->set($user_details)->where($identity_column, $identity)->update($tables['login_users']);
            $this->response['error'] = false;
            $this->response['message'] = 'Profile Update Succesfully';
            echo json_encode($this->response);
            return false;
        }
    }

    /**
     * Validation callback for the optional date of birth.
     *
     * Empty passes - the field is not mandatory. Anything present must be a real calendar date
     * in the Y-m-d that the date input posts, and must not be in the future.
     */
    public function valid_dob($dob)
    {
        if ($dob === '' || $dob === null) {
            return true;
        }

        $parsed = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$parsed || $parsed->format('Y-m-d') !== $dob) {
            $this->form_validation->set_message('valid_dob', 'Please enter a valid Date of Birth.');
            return false;
        }
        if ($parsed > new DateTime('today')) {
            $this->form_validation->set_message('valid_dob', 'Date of Birth cannot be in the future.');
            return false;
        }

        return true;
    }

    /**
     * Store an uploaded avatar for $user_id and return its new file name.
     *
     * Returns NULL when no file was submitted (leave `users`.`image` alone) and FALSE when the
     * upload was rejected, having already sent the JSON error response.
     *
     * Mirrors the mobile app's update_user_profile: the file lands in USER_IMG_PATH and only
     * its bare name goes in the column, so both clients read the same rows the same way.
     */
    private function _upload_profile_image($user_id)
    {
        if (empty($_FILES['image']['name'])) {
            return null;
        }

        if (!file_exists(FCPATH . USER_IMG_PATH)) {
            mkdir(FCPATH . USER_IMG_PATH, 0777, true);
        }

        $this->upload->initialize([
            'upload_path'   => FCPATH . USER_IMG_PATH,
            'allowed_types' => 'jpeg|jpg|png|gif',
            'max_size'      => 4096,
            'encrypt_name'  => true,
        ]);

        if (!$this->upload->do_upload('image')) {
            $this->response['error'] = true;
            $this->response['message'] = 'Profile Photo: ' . strip_tags($this->upload->display_errors());
            echo json_encode($this->response);
            return false;
        }

        $image_data = $this->upload->data();
        resize_image($image_data, FCPATH . USER_IMG_PATH);

        // Drop the file the account was using, otherwise every change leaves the old avatar
        // (plus the thumb/cropped variants resize_image makes) behind forever.
        $previous = fetch_details('users', ['id' => $user_id], 'image');
        if (!empty($previous[0]['image'])) {
            // delete_images() prepends FCPATH itself, so it takes the RELATIVE path.
            delete_images(USER_IMG_PATH, $previous[0]['image']);
        }

        return $image_data['file_name'];
    }
}
