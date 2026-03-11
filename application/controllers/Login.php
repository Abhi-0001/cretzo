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
        $this->load->model('Seller_model');
        $this->lang->load('auth');
    }

    public function index()
    {
      
        print_r($this->ion_auth->seller_status() );
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
            $this->load->view('seller/login', $this->data);
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 2 || $this->ion_auth->seller_status() == 7)) {
            $this->ion_auth->logout();
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
            $this->load->view('seller/login', $this->data);
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            redirect('seller/home', 'refresh');
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_delivery_boy()) {
            redirect('delivery_boy/home', 'refresh');
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            redirect('admin/home', 'refresh');
        }
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

            // ----------------------------------------------------------------
            // BUG FIX #6 — Server-Side Validation (was mostly commented out)
            // ----------------------------------------------------------------

            // Personal Details
            $this->form_validation->set_rules('first_name', 'First Name', 'trim|required|xss_clean|alpha');
            $this->form_validation->set_rules('last_name',  'Last Name',  'trim|required|xss_clean|alpha');
            $this->form_validation->set_rules('phone', 'Phone Number', 'trim|required|xss_clean|numeric|exact_length[10]');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean|valid_email');
            $this->form_validation->set_rules('address1', 'Address Lane 1', 'trim|required|xss_clean');
            $this->form_validation->set_rules('district',  'District',  'trim|required|xss_clean');
            $this->form_validation->set_rules('city',  'City',  'trim|required|xss_clean');
            $this->form_validation->set_rules('state', 'State', 'trim|required|xss_clean');
            $this->form_validation->set_rules('pin', 'PIN Code', 'trim|required|xss_clean|numeric|exact_length[6]');

            // Store Details
            $this->form_validation->set_rules('shop_name',      'Shop Name',         'trim|required|xss_clean');
            $this->form_validation->set_rules('social',         'Social Media',      'trim|required|xss_clean');
            $this->form_validation->set_rules('shop_phone',     'Shop Phone Number', 'trim|required|xss_clean|numeric|exact_length[10]');
            $this->form_validation->set_rules('pickup_address1','Pickup Address',    'trim|required|xss_clean');

            // Account & Entity Details
            $this->form_validation->set_rules('pan',  'PAN Number',  'trim|required|xss_clean|callback_validate_pan');
            $this->form_validation->set_rules('gst',  'GST Number',  'trim|required|xss_clean|callback_validate_gst');
            $this->form_validation->set_rules('account_number',         'Account Number',         'trim|required|xss_clean|numeric|min_length[9]|max_length[18]');
            $this->form_validation->set_rules('confirm_account_number', 'Confirm Account Number', 'trim|required|xss_clean|matches[account_number]');
            $this->form_validation->set_rules('account_holder_name',    'Account Holder Name',    'trim|required|xss_clean');
            $this->form_validation->set_rules('ifsc',   'IFSC Code',   'trim|required|xss_clean|exact_length[11]|callback_validate_ifsc');
            $this->form_validation->set_rules('branch', 'Branch Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('bank_name', 'Bank Name','trim|required|xss_clean');

            // Password change (only if any password field is filled)
            if (!empty($_POST['old']) || !empty($_POST['new']) || !empty($_POST['new_confirm'])) {
                $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
                $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
                $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');
            }

            // File fields (only when not editing)
            if (!isset($_POST['edit_seller'])) {
                $this->form_validation->set_rules('store_logo',            'Store Logo',            'trim|xss_clean');
                $this->form_validation->set_rules('store_banner',          'Store Banner',          'trim|xss_clean');
                $this->form_validation->set_rules('authorized_signature',  'Authorized Signature',  'trim|xss_clean');
                $this->form_validation->set_rules('national_identity_card','National Identity Card','trim|xss_clean');
                $this->form_validation->set_rules('address_proof',         'Address Proof',         'trim|xss_clean');
            }

            // ----------------------------------------------------------------
            // END OF BUG FIX #6 VALIDATION RULES
            // ----------------------------------------------------------------

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));

            } else {

                // process images of seller

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

                //process store banner
                $temp_array_store_banner = $store_banner_doc = array();
                $store_banner_files = $_FILES;
                $store_banner_error = "";
                $config = [
                    'upload_path' =>  FCPATH . SELLER_DOCUMENTS_PATH,
                    'allowed_types' => 'jpg|png|jpeg|gif',
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

                $user_id = $this->session->userdata('user_id');

                if ($user_id) {

                    // ----------------------------------------------------------------
                    // BUG FIX #6 — Duplicate keys removed (account_number & bank_name
                    // were defined twice; kept the XSS-cleaned version with true flag)
                    // ----------------------------------------------------------------
                    $seller_data = array(
                        'user_id'                  => $user_id,
                        'edit_seller_data_id'      => $user_id,
                        'address_proof'            => (!empty($proof_doc)) ? $proof_doc : $this->input->post('old_address_proof', true),
                        'national_identity_card'   => (!empty($id_card_doc)) ? $id_card_doc : $this->input->post('old_national_identity_card', true),
                        'store_logo'               => (!empty($store_logo_doc)) ? $store_logo_doc : $this->input->post('old_store_logo', true),
                        'authorized_signature'     => (!empty($authorized_signature_doc)) ? $authorized_signature_doc : $this->input->post('old_authorized_signature', true),
                        'status'                   => $this->input->post('status', true) ?? null,
                        'pan_number'               => $this->input->post('pan_number', true) ?? null,
                        'tax_number'               => $this->input->post('tax_number', true) ?? null,
                        'tax_name'                 => $this->input->post('tax_name', true) ?? null,
                        'bank_name'                => $this->input->post('bank_name', true) ?? null,   // FIXED: removed duplicate, kept true (XSS-safe)
                        'bank_code'                => $this->input->post('bank_code', true) ?? null,
                        'account_name'             => $this->input->post('account_name', true) ?? null,
                        'account_number'           => $this->input->post('account_number', true) ?? null, // FIXED: removed duplicate, kept true (XSS-safe)
                        'store_description'        => $this->input->post('store_description', true) ?? null,
                        'store_url'                => $this->input->post('store_url', true) ?? null,
                        'store_name'               => $this->input->post('store_name', true) ?? null,
                        'slug'                     => create_unique_slug($this->input->post('store_name', true), 'seller_data') ?? null,
                        'first_name'               => $this->input->post('first_name', true) ?? null,
                        'last_name'                => $this->input->post('last_name', true) ?? null,
                        'phone'                    => $this->input->post('phone', true) ?? null,
                        'email'                    => $this->input->post('email', true) ?? null,
                        'address1'                 => $this->input->post('address1', true) ?? null,
                        'address2'                 => $this->input->post('address2', true) ?? null,
                        'district'                 => $this->input->post('district', true) ?? null,
                        'city'                     => $this->input->post('city', true) ?? null,
                        'state'                    => $this->input->post('state', true) ?? null,
                        'pin'                      => $this->input->post('pin', true) ?? null,
                        'shop_name'                => $this->input->post('shop_name', true) ?? null,
                        'social'                   => $this->input->post('social', true) ?? null,
                        'shop_phone'               => $this->input->post('shop_phone', true) ?? null,
                        'pickup_address1'          => $this->input->post('pickup_address1', true) ?? null,
                        'pickup_address2'          => $this->input->post('pickup_address2', true) ?? null,
                        'pickup_district'          => $this->input->post('pickup_district', true) ?? null,
                        'pickup_state'             => $this->input->post('pickup_state', true) ?? null,
                        'pickup_pin'               => $this->input->post('pickup_pin', true) ?? null,
                        'entity_type'              => $this->input->post('entity_type', true) ?? null,
                        'pan'                      => strtoupper($this->input->post('pan', true)) ?? null,
                        'gst'                      => strtoupper($this->input->post('gst', true)) ?? null,
                        'account_holder_name'      => $this->input->post('account_holder_name', true) ?? null,
                        'ifsc'                     => strtoupper($this->input->post('ifsc', true)) ?? null,
                        'branch'                   => $this->input->post('branch', true) ?? null,
                    );

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
                        'name'      => $this->input->post('first_name', true),
                        'email'     => $this->input->post('email', true),
                        'mobile'    => $this->input->post('phone', true),
                        'address'   => $this->input->post('address1', true),
                        'latitude'  => $this->input->post('latitude', true),
                        'longitude' => $this->input->post('longitude', true)
                    );

                    if ($this->Seller_model->add_seller($seller_data, $seller_profile)) {

                        $this->response = [
                            'error'    => false,
                            'csrfName' => $this->security->get_csrf_token_name(),
                            'csrfHash' => $this->security->get_csrf_hash(),
                            'message'  => 'Seller updated successfully'
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

    // ----------------------------------------------------------------
    // BUG FIX #6 — Custom validation callback methods for PAN, GST, IFSC
    // ----------------------------------------------------------------

    public function validate_pan($pan)
    {
        $pan = strtoupper(trim($pan));
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
            $this->form_validation->set_message('validate_pan', 'Invalid {field} format. Example: ABCDE1234F');
            return false;
        }
        return true;
    }

    public function validate_gst($gst)
    {
        $gst = strtoupper(trim($gst));
        if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst)) {
            $this->form_validation->set_message('validate_gst', 'Invalid {field} format. Example: 22ABCDE0000A1Z5');
            return false;
        }
        return true;
    }

    public function validate_ifsc($ifsc)
    {
        $ifsc = strtoupper(trim($ifsc));
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            $this->form_validation->set_message('validate_ifsc', 'Invalid {field} format. Example: SBIN0001234');
            return false;
        }
        return true;
    }

    // ----------------------------------------------------------------
    // END OF BUG FIX #6 CALLBACK METHODS
    // ----------------------------------------------------------------

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
        $this->load->view('seller/login', $this->data);
    }
}