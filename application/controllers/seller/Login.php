<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
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
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            redirect('seller/home', 'refresh');
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_delivery_boy()) {
            redirect('delivery_boy/home', 'refresh');
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            redirect('admin/home', 'refresh');
        } else {
            // Reaches here when: nobody is logged in, a plain customer is logged in
            // (no seller/admin/delivery_boy role), or a seller is logged in but
            // suspended/rejected (status 2/7). In every case we show the seller
            // login form instead of a 404 — a logged-in customer can switch into a
            // seller session by entering valid seller credentials (ion_auth->login()
            // fully overwrites the session identity), same as any logged-out visitor.
            if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 2 || $this->ion_auth->seller_status() == 7)) {
                $this->ion_auth->logout();
            }
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
        }
    }

    /**
     * Live "is this contact free?" check for the profile form, so the seller finds out while
     * typing instead of after a full multi-step submit. Authoritative validation still runs
     * in update_user() - this endpoint only mirrors it.
     * POST: field (phone|shop_phone|email), value, and optionally phone (so shop_phone can be
     * compared against the seller's own personal number, which is allowed to match).
     */
    public function check_contact()
    {
        $out = ['valid' => true, 'message' => ''];
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller()) {
            $this->output->set_content_type('application/json')->set_output(json_encode($out));
            return;
        }
        $field = $this->input->post('field', true);
        $value = trim((string) $this->input->post('value', true));
        if (!in_array($field, ['phone', 'shop_phone', 'email'], true) || $value === '') {
            $this->output->set_content_type('application/json')->set_output(json_encode($out));
            return;
        }
        $values = [$field => $value];
        if ($field === 'shop_phone') {
            $values['phone'] = trim((string) $this->input->post('phone', true));
        }
        $message = seller_contact_validation_message($values, $this->session->userdata('user_id'));
        // A clash reported against the OTHER field (the personal number posted for context)
        // is not this field's problem - only surface messages about the field being checked.
        if ($message !== '' && $field === 'shop_phone' && stripos($message, 'Shop Phone') === false) {
            $message = '';
        }
        $out = ['valid' => ($message === ''), 'message' => $message];
        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    public function update_user()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $this->response['error'] = true;
                $this->response['message'] = DEMO_VERSION_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            } 

            $identity_column = $this->config->item('identity', 'ion_auth');
            $identity = $this->session->userdata('identity');
            $user = $this->ion_auth->user()->row();
            if ($identity_column == 'email') {
                $this->form_validation->set_rules('email', 'Email', 'required|xss_clean|trim|valid_email');
            } else {
                // $this->form_validation->set_rules('mobile', 'Mobile', 'required|xss_clean|trim|numeric');
            }
            // $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('email', 'Mail', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|xss_clean|min_length[5]');
            if (!empty($_POST['old']) || !empty($_POST['new']) || !empty($_POST['new_confirm'])) {
                $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
                $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
                $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');
            }
            // $this->form_validation->set_rules('address', 'Address', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('store_name', 'Store Name', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('tax_name', 'Tax Name', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('tax_number', 'Tax Number', 'trim|required|xss_clean');
            // $this->form_validation->set_rules('status', 'Status', 'trim|required|xss_clean');

            if (!isset($_POST['edit_seller'])) {
                $this->form_validation->set_rules('store_logo', 'Store Logo', 'trim|xss_clean');
                $this->form_validation->set_rules('store_banner', 'Store Banner', 'trim|xss_clean');
                $this->form_validation->set_rules('authorized_signature', 'Authorized Signature', 'trim|xss_clean');
                $this->form_validation->set_rules('national_identity_card', 'National Identity Card', 'trim|xss_clean');
                $this->form_validation->set_rules('address_proof', 'Address Proof', 'trim|xss_clean');
            }

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {

                // Duplicacy check: shop name and store URL must be unique across sellers.
                // Checked up front (before any file upload processing) so a seller can't
                // burn an upload attempt on a name/URL that's going to be rejected anyway.
                // Store URL normally auto-de-dupes with a numeric suffix via
                // create_unique_slug() below, but the requirement here is to reject the
                // save outright instead of silently changing what the seller asked for.
                $duplicate_user_id = $this->session->userdata('user_id');
                $posted_shop_name = trim($this->input->post('shop_name', true) ?? '');
                if ($posted_shop_name !== '') {
                    $shop_name_taken = $this->db
                        ->where('user_id !=', $duplicate_user_id)
                        ->where('LOWER(TRIM(shop_name))', strtolower($posted_shop_name))
                        ->get('seller_data')
                        ->num_rows() > 0;
                    if ($shop_name_taken) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'Shop Name already exists. Please choose a different Shop Name.';
                        print_r(json_encode($this->response));
                        return;
                    }
                }

                $posted_slug_raw = trim($this->input->post('slug', true) ?? '');
                $slug_check_source = ($posted_slug_raw !== '') ? $posted_slug_raw : $posted_shop_name;
                if ($slug_check_source !== '') {
                    $normalized_slug_check = strtolower(url_title($slug_check_source, '-', TRUE));
                    $slug_taken = $this->db
                        ->where('user_id !=', $duplicate_user_id)
                        ->where('slug', $normalized_slug_check)
                        ->get('seller_data')
                        ->num_rows() > 0;
                    if ($slug_taken) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'Store URL already exists. Please choose a different Store URL.';
                        print_r(json_encode($this->response));
                        return;
                    }
                }

                // Phone / Shop Phone / Email: exact 10-digit mobiles and a well-formed email,
                // and none of them already in use by another account. A seller may reuse their
                // own personal number as the shop number - only OTHER accounts clash.
                $contact_error = seller_contact_validation_message([
                    'phone' => $this->input->post('phone', true),
                    'shop_phone' => $this->input->post('shop_phone', true),
                    'email' => $this->input->post('email', true),
                ], $duplicate_user_id);
                if ($contact_error !== '') {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = $contact_error;
                    print_r(json_encode($this->response));
                    return;
                }

                // PAN / GSTIN / GST Enrollment ID / bank account number belong to exactly one
                // seller. Only the GST field that the "We are not GST registered" toggle
                // actually keeps is checked - the other one stays in the DOM while hidden and
                // can still post a stale value the seller can no longer see or correct.
                $is_non_gst_posted = isset($_POST['gst_check']);
                $duplicate_identifiers = duplicate_seller_identifiers([
                    'pan' => $this->input->post('pan', true),
                    'gst' => $is_non_gst_posted ? '' : $this->input->post('gst', true),
                    'gst_enrollment_number' => $is_non_gst_posted ? $this->input->post('gst_enrollment_number', true) : '',
                    'account_number' => $this->input->post('account_number', true),
                ], $duplicate_user_id);
                if (!empty($duplicate_identifiers)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = duplicate_seller_identifiers_message($duplicate_identifiers);
                    print_r(json_encode($this->response));
                    return;
                }

                // process images of seller

                if (!file_exists(FCPATH . SELLER_DOCUMENTS_PATH)) {
                    mkdir(FCPATH . SELLER_DOCUMENTS_PATH, 0777);
                }

                //process personal photo (stored on users.image, same column/path used by every other user type)
                $new_seller_photo = '';
                $seller_photo_error = "";
                if (!empty($_FILES['seller_photo']['name'])) {
                    if (!file_exists(FCPATH . USER_IMG_PATH)) {
                        mkdir(FCPATH . USER_IMG_PATH, 0777, true);
                    }
                    $photo_config = [
                        'upload_path' => FCPATH . USER_IMG_PATH,
                        'allowed_types' => 'jpg|png|jpeg|gif|pdf',
                        'max_size' => 8000,
                    ];
                    $this->upload->initialize($photo_config);
                    if ($this->upload->do_upload('seller_photo')) {
                        $photo_data = $this->upload->data();
                        $new_seller_photo = $photo_data['file_name'];
                        resize_image($photo_data, FCPATH . USER_IMG_PATH);
                    } else {
                        $seller_photo_error = $this->upload->display_errors();
                    }
                }

                if ($seller_photo_error != NULL && $seller_photo_error != '') {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = $seller_photo_error;
                    print_r(json_encode($this->response));
                    return;
                }

                //process store logo
                $temp_array_logo = $store_logo_doc = array();
                $logo_files = $_FILES;
                $store_logo_error = "";
                $config = [
                    'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                    'allowed_types' => 'jpg|png|jpeg|gif|pdf',
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

                //process store banner
                $temp_array_store_banner = $store_banner_doc = array();
                $store_banner_files = $_FILES;
                $store_banner_error = "";
                $config = [
                    'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                    'allowed_types' => 'jpg|png|jpeg|gif|pdf',
                    'max_size' => 8000,
                ];
                if (isset($store_banner_files['store_banner']) && !empty($store_banner_files['store_banner']['name']) && isset($store_banner_files['store_banner']['name'])) {
                    $other_img = $this->upload;
                    $other_img->initialize($config);

                    if (isset($_POST['edit_seller']) && !empty($_POST['edit_seller']) && isset($_POST['old_store_banner']) && !empty($_POST['old_store_banner'])) {
                        $old_logo = explode('/', $this->input->post('old_store_banner', true));
                        delete_images(SELLER_DOCUMENTS_PATH, $old_logo[2]);
                    }

                    if (!empty($store_banner_files['store_banner']['name'])) {

                        $_FILES['temp_image']['name'] = $store_banner_files['store_banner']['name'];
                        $_FILES['temp_image']['type'] = $store_banner_files['store_banner']['type'];
                        $_FILES['temp_image']['tmp_name'] = $store_banner_files['store_banner']['tmp_name'];
                        $_FILES['temp_image']['error'] = $store_banner_files['store_banner']['error'];
                        $_FILES['temp_image']['size'] = $store_banner_files['store_banner']['size'];
                        if (!$other_img->do_upload('temp_image')) {
                            $store_banner_error = 'Images :' . $store_banner_error . ' ' . $other_img->display_errors();
                        } else {
                            $temp_array_store_banner = $other_img->data();
                            resize_review_images($temp_array_store_banner, FCPATH . SELLER_DOCUMENTS_PATH);
                            $store_banner_doc  = SELLER_DOCUMENTS_PATH . $temp_array_store_banner['file_name'];
                        }
                    } else {
                        $_FILES['temp_image']['name'] = $store_banner_files['store_banner']['name'];
                        $_FILES['temp_image']['type'] = $store_banner_files['store_banner']['type'];
                        $_FILES['temp_image']['tmp_name'] = $store_banner_files['store_banner']['tmp_name'];
                        $_FILES['temp_image']['error'] = $store_banner_files['store_banner']['error'];
                        $_FILES['temp_image']['size'] = $store_banner_files['store_banner']['size'];
                        if (!$other_img->do_upload('temp_image')) {
                            $store_banner_error = $other_img->display_errors();
                        }
                    }
                    //Deleting Uploaded Images if any overall error occured
                    if ($store_banner_error != NULL || !$this->form_validation->run()) {
                        if (isset($store_banner_doc) && !empty($store_banner_doc || !$this->form_validation->run())) {
                            foreach ($store_banner_doc as $key => $val) {
                                unlink(FCPATH . SELLER_DOCUMENTS_PATH . $store_banner_doc[$key]);
                            }
                        }
                    }
                }

                if ($store_banner_error != NULL) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] =  $store_banner_error;
                    print_r(json_encode($this->response));
                    return;
                }

                //process Authorized Signature

                $temp_array_authorized_signature = $authorized_signature_doc = array();
                $authorized_signature_files = $_FILES;
                $authorized_signature_error = "";
                $config = [
                    'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                    'allowed_types' => 'jpg|png|jpeg|gif|pdf',
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
                    'allowed_types' => 'jpg|png|jpeg|gif|pdf',
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
                    'allowed_types' => 'jpg|png|jpeg|gif|pdf',
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

                // Business Details documents: PAN card, GSTIN, GST enrollment acknowledgement,
                // business proof, business address proof, partnership deed, bank account proof.
                // Same upload_path/old_-fallback pattern as national_identity_card/authorized_signature/
                // address_proof above, looped since it's 7 near-identical fields.
                $business_document_fields = [
                    'pan_card_document',
                    'gstin_document',
                    'gst_enrollment_ack_document',
                    'business_proof_document',
                    'business_address_proof_document',
                    'partnership_deed_document',
                    'bank_account_proof_document',
                ];
                $business_document_values = [];
                $business_document_error = '';
                foreach ($business_document_fields as $doc_field) {
                    $business_document_values[$doc_field] = $this->input->post('old_' . $doc_field, true);
                    if (!empty($_FILES[$doc_field]['name'])) {
                        $doc_config = [
                            'upload_path' => FCPATH . SELLER_DOCUMENTS_PATH,
                            'allowed_types' => 'jpg|png|jpeg|gif|pdf',
                            'max_size' => 8000,
                        ];
                        $this->upload->initialize($doc_config);
                        if ($this->upload->do_upload($doc_field)) {
                            $doc_data = $this->upload->data();
                            resize_review_images($doc_data, FCPATH . SELLER_DOCUMENTS_PATH); // no-op for PDFs
                            $business_document_values[$doc_field] = SELLER_DOCUMENTS_PATH . $doc_data['file_name'];
                        } else {
                            $doc_label = ucwords(str_replace('_', ' ', str_replace('_document', '', $doc_field)));
                            $business_document_error = $doc_label . ': ' . $this->upload->display_errors();
                            break;
                        }
                    }
                }

                if ($business_document_error !== '') {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = $business_document_error;
                    print_r(json_encode($this->response));
                    return;
                }

                    $user_id = $this->session->userdata('user_id');

                    // Store URL/slug: use whatever the seller typed, falling back to the
                    // shop name so every seller still gets a working storefront link.
                    // create_unique_slug() already excludes this seller's OWN row (so
                    // resaving the same value doesn't get bumped to "-1" every time) and
                    // silently de-dupes with a numeric suffix on collision, same as every
                    // other slug in this app.
                    $posted_slug = trim($this->input->post('slug', true) ?? '');
                    $slug_source = ($posted_slug !== '') ? $posted_slug : ($this->input->post('shop_name', true) ?? '');
                    $final_slug = ($slug_source !== '') ? create_unique_slug($slug_source, 'seller_data', 'slug', 'user_id', $user_id) : null;

                    // The category picker JS already joins checked ids into one hidden text
                    // input as a comma-separated string; re-parse + re-join here only to
                    // sanitize it down to plain integers before it goes in the DB.
                    $secondary_categories_post = trim($this->input->post('secondary_category_ids', true) ?? '');
                    $secondary_category_ids = null;
                    if ($secondary_categories_post !== '') {
                        $ids = array_filter(array_map('intval', explode(',', $secondary_categories_post)));
                        $secondary_category_ids = !empty($ids) ? implode(',', $ids) : null;
                    }
                    $primary_category_id = $this->input->post('primary_category_id', true);
                    $primary_category_id = ($primary_category_id !== null && $primary_category_id !== '') ? (int) $primary_category_id : null;

                if ($user_id) {

                    $seller_data = array(
                        'user_id' => $user_id,
                        'edit_seller_data_id' => $user_id,
                        'address_proof' => (!empty($proof_doc)) ? $proof_doc : $this->input->post('old_address_proof', true),
                        'national_identity_card' => (!empty($id_card_doc)) ? $id_card_doc : $this->input->post('old_national_identity_card', true),
                        'store_logo' => (!empty($store_logo_doc)) ? $store_logo_doc : $this->input->post('old_store_logo', true),
                        'authorized_signature' => (!empty($authorized_signature_doc)) ? $authorized_signature_doc : $this->input->post('old_authorized_signature', true),
                        'status' => $this->input->post('status', true) ?? null,
                        'pan_number' => $this->input->post('pan_number', true)  ?? null,
                        'tax_number' => $this->input->post('tax_number', true) ?? null,
                        'tax_name' => $this->input->post('tax_name', true) ?? null,
                        'bank_name' => $this->input->post('bank_name', true) ?? null,
                        'bank_code' => $this->input->post('bank_code', true) ?? null,
                        'account_name' => $this->input->post('account_name', true) ?? null,
                        'account_number' => $this->input->post('account_number', true) ?? null,
                        'store_description' => $this->input->post('store_description', true) ?? null,
                        'slug' => $final_slug,
                        'category_ids' => $secondary_category_ids,
                        'primary_category_id' => $primary_category_id,
                        'first_name' => $this->input->post('first_name') ?? null,
                        'middle_name' => $this->input->post('middle_name') ?? null,
                        'last_name' => $this->input->post('last_name') ?? null,
                        'phone' => $this->input->post('phone') ?? null,
                        'email' => $this->input->post('email') ?? null,
                        'address1' => $this->input->post('address1') ?? null,
                        'address2' => $this->input->post('address2') ?? null,
                        'district' => $this->input->post('district') ?? null,
                        'city' => $this->input->post('city') ?? null,
                        'state' => $this->input->post('state') ?? null,
                        'pin' => $this->input->post('pin') ?? null,
                        // 'logo' => (!empty($store_logo_path)) ? $store_logo_path : null,
                        'shop_name' => $this->input->post('shop_name') ?? null,
                        'social' => $this->input->post('social') ?? null,
                        'shop_phone' => $this->input->post('shop_phone') ?? null,
                        'pickup_address1' => $this->input->post('pickup_address1') ?? null,
                        'pickup_address2' => $this->input->post('pickup_address2') ?? null,
                        'pickup_district' => $this->input->post('pickup_district') ?? null,
                        'pickup_city' => $this->input->post('pickup_city') ?? null,
                        'pickup_state' => $this->input->post('pickup_state') ?? null,
                        'pickup_pin' => $this->input->post('pickup_pin') ?? null,
                        'entity_type' => $this->input->post('entity_type') ?? null,
                        'legal_business_name' => $this->input->post('legal_business_name') ?? null,
                        'pan' => $this->input->post('pan') ?? null,
                        'gst' => $this->input->post('gst') ?? null,
                        // GST enrollment restriction: "We are not GST registered" (gst_check) => state-restricted seller.
                        'is_gst_registered' => isset($_POST['gst_check']) ? 0 : 1,
                        'gst_enrollment_number' => isset($_POST['gst_check']) ? ($this->input->post('gst_enrollment_number') ?? null) : null,
                        'business_address1' => $this->input->post('business_address1') ?? null,
                        'business_address2' => $this->input->post('business_address2') ?? null,
                        'business_district' => $this->input->post('business_district') ?? null,
                        'business_city' => $this->input->post('business_city') ?? null,
                        'business_state' => $this->input->post('business_state') ?? null,
                        'business_pin' => $this->input->post('business_pin') ?? null,
                        'account_number' => $this->input->post('account_number') ?? null,
                        'account_holder_name' => $this->input->post('account_holder_name') ?? null,
                        'ifsc' => $this->input->post('ifsc') ?? null,
                        'branch' => $this->input->post('branch') ?? null,
                        'bank_name' => $this->input->post('bank_name') ?? null
                    );
                    $seller_data = array_merge($seller_data, $business_document_values);
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
                    $seller_profile = array(
                        'name' => $this->input->post('first_name', true),
                        'email' => $this->input->post('email', true),
                        'mobile' => $this->input->post('phone', true),
                        'address' => $this->input->post('address1', true),
                        'latitude' => $this->input->post('latitude', true),
                        'longitude' => $this->input->post('longitude', true)
                    );

                    if (!empty($new_seller_photo)) {
                        if (!empty($user->image) && file_exists(FCPATH . USER_IMG_PATH . $user->image)) {
                            @unlink(FCPATH . USER_IMG_PATH . $user->image);
                        }
                        $seller_profile['image'] = $new_seller_photo;
                    }

                    if ($this->Seller_model->add_seller($seller_data, $seller_profile)) {

                        // Saving a complete profile IS the request for admin verification -
                        // there is no separate "Request Admin Verification" button, because
                        // the admin has nothing to review until every section is filled in.
                        // An incomplete save is still kept; it just doesn't raise a review.
                        $verification = seller_file_verification_request($user_id);
                        if ($verification['filed']) {
                            $message = 'Profile saved and sent to the admin for verification.';
                        } elseif (!empty($verification['missing_sections'])) {
                            $labels = array_unique(array_column($verification['missing_sections'], 'label'));
                            $message = 'Profile saved. Still to complete before it can go for admin verification: ' . implode(', ', $labels) . '.';
                        } else {
                            $message = 'Profile updated successfully.';
                        }

                        $this->response = [
                            'error'    => false,
                            'csrfName' => $this->security->get_csrf_token_name(),
                            'csrfHash' => $this->security->get_csrf_hash(),
                            'message'  => $message,
                            'verification_filed' => $verification['filed']
                        ];
                    
                        echo json_encode($this->response);
                        exit;
                    
                    } else {
                    
                        $this->response = [
                            'error'    => true,
                            'csrfName' => $this->security->get_csrf_token_name(),
                            'csrfHash' => $this->security->get_csrf_hash(),
                            'message'  => 'Seller data was not updated'
                        ];
                    
                        echo json_encode($this->response);
                        exit;
                    }

                }
            }
        } else {
            redirect('seller/home', 'refresh');
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
            // ONE message for every way a login can fail. This used to answer three
            // distinguishable ways - "<identity> field is not correct" when no such user
            // existed, the same text when the user existed but wasn't a seller, and
            // ion_auth's own "Incorrect login" when the account existed and only the
            // password was wrong. Comparing the replies let anyone enumerate which emails
            // or phone numbers hold a seller account, which is the useful half of a
            // credential-stuffing run. Nothing legitimate needs the distinction: a real
            // seller typing their own password correctly gets in either way.
            $generic_failure = 'The ' . $identity_column . ' or password you entered is incorrect.';

            if (!empty($res) && $this->ion_auth_model->in_group('seller', $res[0]['id'])) {
                $remember = (bool)$this->input->post('remember');
                if ($this->ion_auth->login($this->input->post('identity', true), $this->input->post('password', true), $remember)) {
                    //if the login is successful
                    $response['error'] = false;
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    $response['message'] = $this->ion_auth->messages();
                    echo json_encode($response);
                    return;
                }
            } else {
                // Spend the same work as a real password check even when there is no seller
                // account to check against, so the reply time doesn't leak what the message
                // no longer does.
                password_verify((string) $this->input->post('password', true), '$2y$10$' . str_repeat('0', 53));
            }

            $response['error'] = true;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = $generic_failure;
            echo json_encode($response);
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
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $this->response['error'] = true;
                $this->response['message'] = DEMO_VERSION_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }

        $this->data['main_page'] = FORMS . 'forgot-password';
        $settings = get_settings('system_settings', true);
        $this->data['title'] = 'Forgot Password | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Forget Password | ' . $settings['app_name'];
        $this->data['logo'] = get_settings('logo');
        // Which OTP channel this site actually uses. With 'firebase' the OTP SMS is sent
        // and confirmed by Firebase in the browser (same as seller registration); the
        // server-side send_sms() path only applies when an SMS gateway is configured.
        $auth_settings = get_settings('authentication_settings', true);
        $this->data['authentication_method'] = !empty($auth_settings['authentication_method'])
            ? $auth_settings['authentication_method']
            : 'sms';
        $this->data['firebase_settings'] = get_settings('firebase_settings', true);
        $this->load->view('seller/login', $this->data);
    }

    // Looks up a seller (group "seller") user row by mobile number, shared by
    // send_reset_otp()/reset_password() below.
    private function _find_reset_seller($mobile)
    {
        $res = fetch_details('users', ['mobile' => $mobile]);
        if (empty($res) || !$this->ion_auth->is_seller($res[0]['id'])) {
            return null;
        }
        return $res[0];
    }

    // Distinguishes "no such account" from "that account lives on another portal",
    // instead of answering both with a flat "You have not registered using this number."
    private function _reset_lookup_error($mobile)
    {
        $owner = classify_mobile_owner($mobile);
        if (!$owner['exists']) {
            return 'You have not registered using this number.';
        }
        // Membership, not "primary role" - one account can hold both the buyer and seller
        // roles. See Home.php::_reset_lookup_error().
        if (!user_has_role($owner['user']['id'], 'seller')) {
            $portal = reset_portal_for_role($owner['role']);
            $where  = !empty($portal['url'])
                ? 'Please reset your password here: ' . base_url($portal['url'])
                : 'Please reset your password from the customer login on the main site.';
            return 'This number is registered as ' . $portal['label'] . ', not a seller account. ' . $where;
        }
        return null;
    }

    /**
     * Confirms a mobile number belongs to a seller account, without sending anything.
     *
     * Used by the Firebase reset flow before it asks Firebase to send an SMS: Firebase
     * OTPs are metered and rate-limited per number, so there is no sense burning one on a
     * number that isn't registered - and it keeps the existing "this is a customer
     * account, reset it here instead" guidance, which would otherwise be lost now that the
     * server no longer drives the send.
     */
    public function check_reset_account()
    {
        $this->form_validation->set_rules('mobile_number', 'Mobile No', 'trim|numeric|required|xss_clean|max_length[16]');
        if (!$this->form_validation->run()) {
            echo json_encode(['error' => true, 'message' => strip_tags(validation_errors())]);
            return false;
        }

        $lookup_error = $this->_reset_lookup_error($this->input->post('mobile_number'));
        if ($lookup_error !== null) {
            echo json_encode(['error' => true, 'message' => $lookup_error]);
            return false;
        }

        echo json_encode(['error' => false, 'message' => 'Account found.']);
        return false;
    }

    public function send_reset_otp()
    {
        $this->form_validation->set_rules('mobile_number', 'Mobile No', 'trim|numeric|required|xss_clean|max_length[16]');
        if (!$this->form_validation->run()) {
            $response['error'] = true;
            $response['message'] = strip_tags(validation_errors());
            echo json_encode($response);
            return false;
        }

        $lookup_error = $this->_reset_lookup_error($this->input->post('mobile_number'));
        if ($lookup_error !== null) {
            $response['error'] = true;
            $response['message'] = $lookup_error;
            echo json_encode($response);
            return false;
        }
        $user = $this->_find_reset_seller($this->input->post('mobile_number'));

        $sent = send_password_reset_otp($this->input->post('mobile_number'), $user);
        if (empty($sent) || !empty($sent['error'])) {
            $response['error'] = true;
            $response['message'] = !empty($sent['message']) ? $sent['message'] : 'Could not send the OTP. Please try again later.';
            echo json_encode($response);
            return false;
        }

        $response['error'] = false;
        // Name the channel: with no SMS gateway configured the OTP is emailed.
        $response['message'] = !empty($sent['message']) ? $sent['message'] : 'OTP sent successfully.';
        $response['channel'] = !empty($sent['channel']) ? $sent['channel'] : '';
        echo json_encode($response);
        return false;
    }

    public function reset_password()
    {
        $this->form_validation->set_rules('mobile_number', 'Mobile No', 'trim|numeric|required|xss_clean|max_length[16]');
        $this->form_validation->set_rules('otp', 'OTP', 'trim|required|xss_clean');
        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean');
        if (!$this->form_validation->run()) {
            $response['error'] = true;
            $response['message'] = strip_tags(validation_errors());
            echo json_encode($response);
            return false;
        }

        $identity_column = $this->config->item('identity', 'ion_auth');
        $user = $this->_find_reset_seller($this->input->post('mobile_number'));
        if (empty($user)) {
            $response['error'] = true;
            $response['message'] = 'User does not exists !';
            echo json_encode($response);
            return false;
        }

        $otp_check = verify_password_reset_otp($this->input->post('mobile_number'), $this->input->post('otp'));
        if ($otp_check['error']) {
            $response['error'] = true;
            $response['message'] = $otp_check['message'];
            echo json_encode($response);
            return false;
        }

        $identity = ($identity_column == 'email') ? $user['email'] : $user['mobile'];
        if (!$this->ion_auth->reset_password($identity, $this->input->post('new_password'))) {
            $response['error'] = true;
            $response['message'] = $this->ion_auth->messages();
        } else {
            $response['error'] = false;
            $response['message'] = 'Reset Password Successfully';
        }
        echo json_encode($response);
        return false;
    }

    /**
     * Password reset for sites whose authentication_method is "firebase".
     *
     * This site has NO SMS gateway configured (settings.sms_gateway_settings is '{}') and
     * never has - phone OTPs are minted client-side by Firebase phone auth, which is what
     * seller REGISTRATION already uses. Password reset, however, only ever knew about the
     * server-side send_sms() path plus an email fallback, so on this configuration it had
     * no way to deliver anything to a mobile number and always ended in "We could not
     * deliver your OTP right now".
     *
     * Here the OTP is sent and confirmed by Firebase on the client; the browser then posts
     * the resulting ID token, which we verify server-side. Nothing the client claims is
     * trusted: the phone number is read out of the *verified* token, the sign-in provider
     * must actually be 'phone' (so an email/Facebook token cannot be replayed here), and
     * the reset only proceeds for the seller who owns that number.
     */
    public function reset_password_firebase()
    {
        $this->form_validation->set_rules('mobile_number', 'Mobile No', 'trim|numeric|required|xss_clean|max_length[16]');
        $this->form_validation->set_rules('id_token', 'Verification token', 'trim|required|xss_clean');
        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|min_length[6]|xss_clean');
        if (!$this->form_validation->run()) {
            $response['error'] = true;
            $response['message'] = strip_tags(validation_errors());
            echo json_encode($response);
            return false;
        }

        // All the verification lives in firebase_phone_reset() so the seller, customer and
        // admin portals cannot drift apart on it.
        $result = firebase_phone_reset(
            $this->input->post('id_token', false),
            $this->input->post('mobile_number', true),
            $this->input->post('new_password'),
            'seller'
        );

        echo json_encode($result);
        return false;
    }
}
