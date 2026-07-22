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
        $this->load->model('Seller_model');
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
            $this->load->view('seller/login', $this->data);
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller()) {
            redirect('seller/home', 'refresh');
        } else if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            redirect('admin/home', 'refresh');
        }
    }

    public function sign_up()
    {
        
        $this->data['main_page'] = FORMS . 'seller-registration';
        $settings = get_settings('system_settings', true);
        $this->data['title'] = 'Sign Up Seller | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Sign Up Seller | ' . $settings['app_name'];
        $this->data['logo'] = get_settings('logo');

        if (isset($_SESSION['to_be_seller_name']) && !empty($_SESSION['to_be_seller_name']) && isset($_SESSION['to_be_seller_mobile']) && !empty($_SESSION['to_be_seller_mobile']) && isset($_SESSION['to_be_seller_id']) && !empty($_SESSION['to_be_seller_id'])) {
            $this->data['title'] = 'Update Seller | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Update Seller | ' . $settings['app_name'];
            $this->data['user_data'] = $_SESSION;
        }
        
        $this->load->view('seller/signup', $this->data);
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
        $otp = 123456; //rand(100000, 999999);

        // Store OTP & mobile in session for 10 minutes
        $this->session->set_tempdata('otp', $otp, 10 * 60);
        

        // Twilio credentials
        $token  = "27acec586b41c090c0f71690425e1341";
        $sid  = "AC98662e8c1491ef426a93b295856918dc";
        $twilio_number = "+18573424919";

        try {
            // require_once(APPPATH.'third_party/twilio/vendor/autoload.php');
            // $client = new TwilioClient($sid, $token);
            // $otp_response = $client->messages->create(
            //     "+91".$mobile,
            //     [
            //         "from" => $twilio_number,
            //         "body" => "Your OTP is: ".$otp
            //     ]
            // );

            // print_r(json_encode($otp_response));
            echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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

    public function ajax_signup(){

        // Setup validation rules
        // $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
        // $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');
        $password = $this->input->post('password', true);
        $confirm_password = $this->input->post('confirm_password', true);
        // print_r($password);
        // print_r($confirm_password);
        $mobile = $this->input->post('mobile', true);

        if(/*!$this->form_validation->run()*/ $password !== $confirm_password){
            $response = [
                'status' => 'failed',
                'message' => 'Passwords do not match'
            ];
        } else {
            $identity =  $mobile;
            try{
                $user_id = $this->ion_auth->register($identity, $password, '', [], [4]); //4 is seller group id
                 
                if($user_id){
                    
                    //set session for further details
                    // $_SESSION['to_be_seller_name'] = $additional_data['first_name'].' '.$additional_data['last_name'];
                    // $_SESSION['to_be_seller_mobile'] = $additional_data['phone'];
                    // $_SESSION['to_be_seller_id'] = $user_id;

                    $response = [
                        'status' => 'success',
                        'message' => 'Seller registered successfully!'
                    ];
                } else {
                    $response = [
                        'status' => 'failed',
                        'message' => 'Failed to register seller!'
                    ];
                }
            }catch(Exception $e){
                $response = [
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }
        if($response['status'] == 'success'){
            $login_response = $this->ion_auth->login($mobile, $password);
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    public function login(){
        $identity = $this->input->post('identity', true);
        $password = $this->input->post('password', true);

        $remember = $this->input->post('remember', true);

        try{
            
            $user_data = fetch_details('users', ['mobile' => $identity]);
            
            if($group = fetch_details('users_groups', ['user_id' => $user_data[0]['id']] )){
                if($group[0]['group_id'] !=4){
                    $response['error'] = true;
                    redirect('seller/login?error=true');
                        // $response['message'] = 'Invalid user';
                        // echo json_encode($response);
                        // return false;
                }
            }
           
            $response = $this->ion_auth->login($identity, $password, $remember);
            
          
            if($response){
                redirect('seller/home', 'refresh');
            }else {
                redirect('seller/login?error=true');
                // echo json_encode([
                //     'status'=> 'failed',
                //     'message' => $response.json_encode(),
                // ]);
            }
        }catch(Exception $e){
            echo json_encode([
                'status'=> 'failed',
                'message' => $e.Message::get_error_message(),
            ]);
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
        // $this->form_validation->set_rules('pan', 'PAN Number', 'required|regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/]');
        // $this->form_validation->set_rules('gst', 'GST Number', 'required|regex_match[/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/]');

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

            $user_id = $this->ion_auth->register($seler_register['mobile'], '123456', $seler_register['first_name'], ['phone' => $seler_register['mobile']], [4]);

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
            
            $this->ion_auth->login($this->input->post('phone'), '123456', true);



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

                if (!$this->form_validation->is_unique($_POST['mobile'], 'users.mobile') || !$this->form_validation->is_unique($_POST['email'], 'users.email')) {
                    $response["error"]   = true;
                    $response["message"] = "Email or mobile already exists !";
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
                $this->ion_auth->register($identity, $password, $email, $additional_data, ['4']);
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
