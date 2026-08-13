<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sellers extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model('Seller_model');
        if (!has_permissions('read', 'seller')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-seller';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Seller Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Seller Management  | ' . $settings['app_name'];
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function manage_seller()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'seller';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Add Seller | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Seller | ' . $settings['app_name'];
            $this->data['categories'] = $this->category_model->get_categories();
            // Only populated when editing. On the "Add Seller" screen the view still reads it,
            // so hand it an empty array instead of leaving the variable undefined.
            $this->data['fetched_data'] = [];

            $this->data['all_categories'] = [];
            if ($this->db->table_exists('categories')) {
                $this->data['all_categories'] = $this->db
                    ->select('id,name,parent_id')
                    ->from('categories')
                    ->where('status', 1)
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result_array();
            }

            $this->data['indian_banks'] = [];
            if ($this->db->table_exists('indian_banks')) {
                $this->data['indian_banks'] = $this->db
                    ->select('bank_name')
                    ->from('indian_banks')
                    ->order_by('bank_name', 'ASC')
                    ->get()
                    ->result_array();
            }

            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['title'] = 'Update Seller | ' . $settings['app_name'];
                $this->data['meta_description'] = 'Update Seller | ' . $settings['app_name'];
                // seller_data was an INNER join - a seller with no seller_data row yet (e.g.
                // registered through self-service sign-up before that flow created one) matched
                // zero rows here, and the next line's [0] access on an empty result faulted with
                // an undefined-index warning while rendering a blank/broken form - this seller
                // could be listed nowhere AND not opened directly by id either.
                // email/phone are coalesced from seller_data with a fallback to users.email/mobile
                // (mirrors seller/Home::profile()) so a seller created before the KYC wizard
                // existed - and who therefore has no seller_data.email/phone yet - still shows
                // its real contact info instead of a blank field.
                $this->data['fetched_data'] = $this->db
                    ->select('u.*, sd.*, u.id as user_id, COALESCE(NULLIF(sd.email, ""), u.email) as email, COALESCE(NULLIF(sd.phone, ""), u.mobile) as phone')
                    ->join('users_groups ug', ' ug.user_id = u.id ')
                    ->join('seller_data sd', ' sd.user_id = u.id ', 'left')
                    ->where(['ug.group_id' => '4', 'ug.user_id' => $_GET['edit_id']])
                    ->get('users u')
                    ->result_array();

                if (!empty($this->data['fetched_data'])) {
                    $this->data['fetched_data'][0] = output_escaping($this->data['fetched_data'][0]);
                }
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_sellers()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Seller_model->get_sellers_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function remove_sellers()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'seller'), PERMISSION_ERROR_MSG, 'seller', false)) {
                return true;
            }

            if (!isset($_GET['id']) && empty($_GET['id'])) {
                $this->response['error'] = true;
                $this->response['message'] = 'Seller id is required';
                print_r(json_encode($this->response));
                return;
                exit();
            }
            $all_status = [0, 1, 2, 7];
            $status = $this->input->get('status', true);
            $id = $this->input->get('id', true);
            if (!in_array($status, $all_status)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Invalid status';
                print_r(json_encode($this->response));
                return;
                exit();
            }
            if ($status == 2) {
                $this->response['error'] = true;
                $this->response['message'] = 'First approve this Seller from edit seller.';
                print_r(json_encode($this->response));
                return;
                exit();
            }
            // For a Deactive seller (status 0), this used to fall through to the final ": 1"
            // branch - clicking "Remove Seller" on a deactivated seller silently APPROVED them
            // instead, despite the button, confirmation dialog, and "removed successfully"
            // message all describing a removal. Only a currently-Removed (7) seller should be
            // restored (-> 1); every other actionable status (0 or 1) should be removed (-> 7).
            $status = ($status == 7) ? 1 : 7;

            if (update_details(['status' => $status], ['user_id' => $id], 'seller_data') == TRUE) {
                $this->response['error'] = false;
                $this->response['message'] = 'Seller removed succesfully';
                print_r(json_encode($this->response));
            } else {
                $this->response['error'] = true;
                $this->response['message'] = 'Something Went Wrong';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function delete_sellers()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'seller'), PERMISSION_ERROR_MSG, 'seller', false)) {
                return true;
            }

            if (!isset($_GET['id']) && empty($_GET['id'])) {
                $this->response['error'] = true;
                $this->response['message'] = 'Seller id is required';
                print_r(json_encode($this->response));
                return;
                exit();
            }
            $id = $this->input->get('id', true);
            $delete = array(
                "media" => 0,
                "payment_requests" => 0,
                "products" => 0,
                "product_attributes" => 0,
                "order_items" => 0,
                "orders" => 0,
                "order_bank_transfer" => 0,
                "seller_commission" => 0,
                "seller_data" => 0,
            );

            $seller_media = fetch_details('seller_data', ['user_id' => $id], 'id,logo,authorized_signature,national_identity_card,address_proof');

            if (!empty($seller_media)) {
                // Every one of these columns is optional, and most sellers have at least one
                // empty. FCPATH . '' resolves to the application root, so unlink() was being
                // called on a DIRECTORY and emitted four warnings that printed straight into
                // the response body - which broke the JSON the admin UI parses, so a delete
                // that actually succeeded still reported an error in the browser.
                foreach (['logo', 'national_identity_card', 'address_proof', 'authorized_signature'] as $media_field) {
                    $path = isset($seller_media[0][$media_field]) ? trim((string) $seller_media[0][$media_field]) : '';
                    if ($path === '') {
                        continue;
                    }
                    $full = FCPATH . $path;
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
            }

            if (update_details(['seller_id' => 0], ['seller_id' => $id], 'media')) {
                $delete['media'] = 1;
            }

            /* check for retur requesst if seller's product have */
            $return_req = $this->db->where(['p.seller_id' => $id])->join('products p', 'p.id=rr.product_id')->get('return_requests rr')->result_array();
            if (!empty($return_req)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Seller could not be deleted.Either found some order items which has return request.Finalize those before deleting it';
                print_r(json_encode($this->response));
                return;
                exit();
            }
            // fetch_details() returns NULL (not []) when the seller has no products, so the
            // foreach below raised "foreach() argument must be of type array|object, null
            // given" for every seller without a catalogue - another warning printed into the
            // JSON body.
            $pr_ids = fetch_details("products", ['seller_id' => $id], "id");
            $pr_ids = !empty($pr_ids) ? $pr_ids : [];
            if (delete_details(['seller_id' => $id], 'products')) {
                $delete['products'] = 1;
            }
            foreach ($pr_ids as $row) {
                if (delete_details(['product_id' => $row['id']], 'product_attributes')) {
                    $delete['product_attributes'] = 1;
                }
            }

            /* check order items */
            $order_items = fetch_details('order_items', ['seller_id' => $id], 'id,order_id');
            if (delete_details(['seller_id' => $id], 'order_items')) {
                $delete['order_items'] = 1;
            }
            if (!empty($order_items)) {
                $res_order_id = array_values(array_unique(array_column($order_items, "order_id")));
                for ($i = 0; $i < count($res_order_id); $i++) {
                    $orders = $this->db->where('oi.seller_id != ' . $id . ' and oi.order_id=' . $res_order_id[$i])->join('orders o', 'o.id=oi.order_id', 'right')->get('order_items oi')->result_array();
                    if (empty($orders)) {
                        // delete orders
                        if (delete_details(['seller_id' => $id], 'order_items')) {
                            $delete['order_items'] = 1;
                        }
                        if (delete_details(['id' => $res_order_id[$i]], 'orders')) {
                            $delete['orders'] = 1;
                        }
                        if (delete_details(['order_id' => $res_order_id[$i]], 'order_bank_transfer')) {
                            $delete['order_bank_transfer'] = 1;
                        }
                    }
                }
            } else {
                $delete['order_items'] = 1;
                $delete['orders'] = 1;
                $delete['order_bank_transfer'] = 1;
            }
            if (!empty($res_order_id)) {

                if (delete_details(['id' => $res_order_id[$i]], 'orders')) {
                    $delete['orders'] = 1;
                }
            } else {
                $delete['orders'] = 1;
            }

            if (delete_details(['seller_id' => $id], 'seller_commission')) {
                $delete['seller_commission'] = 1;
            }
            if (delete_details(['user_id' => $id], 'seller_data')) {
                $delete['seller_data'] = 1;
            }

            // This used to only flip users_groups from 4 (seller) to 2 (customer) and leave
            // the `users` row in place. The seller vanished from the admin list, so it read
            // as a deletion - but the account still existed, so its mobile number stayed
            // taken and signing up again with it was refused as "already registered". That
            // mismatch is exactly what admins were hitting.
            //
            // "Remove Seller" (remove_sellers(), status 7) is the reversible action; this
            // one is labelled Delete, so it now really deletes the account and frees the
            // number - unless the account is also an admin, which must never be destroyed
            // through the seller screen.
            if (user_has_role($id, 'admin')) {
                $this->response['error'] = true;
                $this->response['message'] = 'This account also has admin access, so it cannot be deleted from here. Remove the admin role first.';
                print_r(json_encode($this->response));
                return;
            }

            $deleted = FALSE;
            if (isset($delete['seller_data']) && !empty($delete['seller_data']) && isset($delete['seller_commission']) && !empty($delete['seller_commission'])) {
                $deleted = TRUE;
            }

            // Rows keyed on the seller that would otherwise be left pointing at a user id
            // that no longer exists. seller_subscriptions is the one that actually bit us:
            // an orphan there still counted towards the 100-vendor launch-offer cap.
            delete_details(['seller_id' => $id], 'seller_subscriptions');
            delete_details(['user_id' => $id], 'addresses');
            delete_details(['user_id' => $id], 'cart');
            delete_details(['user_id' => $id], 'favorites');
            delete_details(['user_id' => $id], 'users_groups');

            if (delete_details(['id' => $id], 'users')) {
                $this->response['error'] = false;
                $this->response['message'] = 'Seller deleted successfully. This mobile number is now free to register again.';
                print_r(json_encode($this->response));
            } else {
                $this->response['error'] = true;
                $this->response['message'] = 'Something Went Wrong';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function add_seller()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_seller'])) {
                if (print_msg(!has_permissions('update', 'seller'), PERMISSION_ERROR_MSG, 'seller')) {
                    return true;
                }
            } else {
                if (print_msg(!has_permissions('create', 'seller'), PERMISSION_ERROR_MSG, 'seller')) {
                    return true;
                }
            }
            $user = $this->ion_auth->user()->row();
            $is_edit = isset($_POST['edit_seller']);

            $this->form_validation->set_rules('first_name', 'First Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('last_name', 'Last Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean');
            $this->form_validation->set_rules('address1', 'Address', 'trim|required|xss_clean');
            $this->form_validation->set_rules('pin', 'PIN Code', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('state', 'State', 'trim|required|xss_clean');
            $this->form_validation->set_rules('district', 'District', 'trim|required|xss_clean');
            $this->form_validation->set_rules('city', 'City', 'trim|required|xss_clean');
            $this->form_validation->set_rules('shop_name', 'Shop Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('shop_phone', 'Shop Phone', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('pickup_address1', 'Pickup Address', 'trim|required|xss_clean');
            $this->form_validation->set_rules('pickup_pin', 'Pickup PIN Code', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('primary_category_id', 'Primary Category', 'trim|required|xss_clean');
            $this->form_validation->set_rules('entity_type', 'Entity Type', 'trim|required|xss_clean');
            $this->form_validation->set_rules('pan', 'PAN Number', 'trim|required|xss_clean');
            $this->form_validation->set_rules('business_address1', 'Business Address', 'trim|required|xss_clean');
            $this->form_validation->set_rules('business_pin', 'Business PIN Code', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('business_state', 'Business State', 'trim|required|xss_clean');
            $this->form_validation->set_rules('business_district', 'Business District', 'trim|required|xss_clean');
            $this->form_validation->set_rules('business_city', 'Business City', 'trim|required|xss_clean');
            $this->form_validation->set_rules('account_number', 'Account Number', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('confirm_account_number', 'Confirm Account Number', 'trim|required|matches[account_number]|xss_clean');
            $this->form_validation->set_rules('account_holder_name', 'Account Holder Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('ifsc', 'IFSC Code', 'trim|required|xss_clean');
            $this->form_validation->set_rules('branch', 'Branch Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('bank_name', 'Bank Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('status', 'Status', 'trim|required|xss_clean');

            if (!$is_edit) {
                $this->form_validation->set_rules('phone', 'Phone', 'trim|required|numeric|xss_clean|min_length[5]|edit_unique[users.mobile.' . $user->id . ']');
                $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
                $this->form_validation->set_rules('confirm_password', 'Confirm password', 'trim|required|matches[password]|xss_clean');
            } else {
                $this->form_validation->set_rules('phone', 'Phone', 'trim|required|numeric|xss_clean');
            }

            // GST vs GST-enrollment requirement mirrors the "We are not GST registered"
            // (gst_check) toggle on the form - same logic as seller/Login::update_user().
            if (!isset($_POST['gst_check'])) {
                $this->form_validation->set_rules('gst', 'GST Number', 'trim|required|xss_clean');
            } else {
                $this->form_validation->set_rules('gst_enrollment_number', 'GST Enrollment Number', 'trim|required|xss_clean');
            }

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return;
            }

            // Duplicate email/phone check (add mode only - an existing seller keeps their own).
            if (!$is_edit) {
                if (!$this->form_validation->is_unique($_POST['phone'], 'users.mobile') || !$this->form_validation->is_unique($_POST['email'], 'users.email')) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = "Email or Phone already exists !";
                    print_r(json_encode($this->response));
                    return;
                }
            }

            // Shop Name / Store URL must be unique across sellers (excluding the seller's own
            // row on edit) - mirrored from seller/Login::update_user(), checked up front so a
            // save doesn't burn file uploads on a name/URL that's going to be rejected anyway.
            $current_user_id_for_dup = $is_edit ? $this->input->post('edit_seller', true) : 0;
            $posted_shop_name = trim($this->input->post('shop_name', true) ?? '');
            if ($posted_shop_name !== '') {
                $shop_name_taken = $this->db
                    ->where('user_id !=', $current_user_id_for_dup)
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
            $slug_source = ($posted_slug_raw !== '') ? $posted_slug_raw : $posted_shop_name;
            if ($slug_source !== '') {
                $normalized_slug_check = strtolower(url_title($slug_source, '-', TRUE));
                $slug_taken = $this->db
                    ->where('user_id !=', $current_user_id_for_dup)
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

            if (!file_exists(FCPATH . SELLER_DOCUMENTS_PATH)) {
                mkdir(FCPATH . SELLER_DOCUMENTS_PATH, 0777);
            }

            // process seller photo (-> users.image, same column/path every other user type uses)
            $new_seller_photo = '';
            $seller_photo_error = '';
            if (!empty($_FILES['seller_photo']['name'])) {
                if (!file_exists(FCPATH . USER_IMG_PATH)) {
                    mkdir(FCPATH . USER_IMG_PATH, 0777, true);
                }
                $this->upload->initialize([
                    'upload_path' => FCPATH . USER_IMG_PATH,
                    'allowed_types' => 'jpg|png|jpeg|gif|pdf',
                    'max_size' => 8000,
                ]);
                if ($this->upload->do_upload('seller_photo')) {
                    $photo_data = $this->upload->data();
                    $new_seller_photo = $photo_data['file_name'];
                    resize_image($photo_data, FCPATH . USER_IMG_PATH);
                    $old_seller_photo = trim($this->input->post('old_seller_photo', true) ?? '');
                    if ($old_seller_photo !== '' && file_exists(FCPATH . USER_IMG_PATH . $old_seller_photo)) {
                        @unlink(FCPATH . USER_IMG_PATH . $old_seller_photo);
                    }
                } else {
                    $seller_photo_error = $this->upload->display_errors();
                }
            }
            if ($seller_photo_error !== '') {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $seller_photo_error;
                print_r(json_encode($this->response));
                return;
            }

            // process the 10 seller_data-bound documents (identity/signature/logo/business docs),
            // each falling back to its existing old_<field> value and, once replaced, deleting the
            // file it replaces. Required-ness mirrors the same show/hide rules the form's JS uses
            // for entity_type/gst_check, since a field hidden on the form should not block a save.
            $entity_type_posted = $this->input->post('entity_type', true);
            $gst_check_set = isset($_POST['gst_check']);
            $business_proof_required = $gst_check_set && !in_array($entity_type_posted, ['individual', ''], true);
            $doc_required = [
                'national_identity_card' => true,
                'authorized_signature' => true,
                'store_logo' => false,
                'pan_card_document' => true,
                'gstin_document' => !$gst_check_set,
                'gst_enrollment_ack_document' => $gst_check_set,
                'partnership_deed_document' => ($entity_type_posted === 'partnership_firm'),
                'business_proof_document' => $business_proof_required,
                'business_address_proof_document' => $business_proof_required,
                'bank_account_proof_document' => false,
            ];
            $document_values = [];
            $document_error = '';
            $doc_config = [
                'upload_path' => FCPATH . SELLER_DOCUMENTS_PATH,
                'allowed_types' => 'jpg|png|jpeg|gif|pdf',
                'max_size' => 8000,
            ];
            foreach ($doc_required as $field => $is_required) {
                $old_value = trim($this->input->post('old_' . $field, true) ?? '');
                $document_values[$field] = ($old_value !== '') ? $old_value : null;
                if (!empty($_FILES[$field]['name'])) {
                    $this->upload->initialize($doc_config);
                    if ($this->upload->do_upload($field)) {
                        $doc_data = $this->upload->data();
                        resize_review_images($doc_data, FCPATH . SELLER_DOCUMENTS_PATH); // no-op for PDFs
                        if ($old_value !== '') {
                            $old_parts = explode('/', $old_value);
                            delete_images(SELLER_DOCUMENTS_PATH, end($old_parts));
                        }
                        $document_values[$field] = SELLER_DOCUMENTS_PATH . $doc_data['file_name'];
                    } else {
                        $doc_label = ucwords(str_replace('_', ' ', str_replace('_document', '', $field)));
                        $document_error = $doc_label . ': ' . $this->upload->display_errors();
                        break;
                    }
                } elseif ($is_required && empty($document_values[$field])) {
                    $doc_label = ucwords(str_replace('_', ' ', str_replace('_document', '', $field)));
                    $document_error = $doc_label . ' is required.';
                    break;
                }
            }
            if ($document_error !== '') {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $document_error;
                print_r(json_encode($this->response));
                return;
            }

            $secondary_categories_post = trim($this->input->post('secondary_category_ids', true) ?? '');
            $secondary_category_ids = null;
            if ($secondary_categories_post !== '') {
                $ids = array_filter(array_map('intval', explode(',', $secondary_categories_post)));
                $secondary_category_ids = !empty($ids) ? implode(',', $ids) : null;
            }
            $primary_category_id = $this->input->post('primary_category_id', true);
            $primary_category_id = ($primary_category_id !== null && $primary_category_id !== '') ? (int) $primary_category_id : null;

            // process permissions of sellers
            $permmissions = array();
            $permmissions['require_products_approval'] = (isset($_POST['require_products_approval'])) ? 1 : 0;
            $permmissions['customer_privacy'] = (isset($_POST['customer_privacy'])) ? 1 : 0;
            $permmissions['view_order_otp'] = (isset($_POST['view_order_otp'])) ? 1 : 0;
            $permmissions['assign_delivery_boy'] = (isset($_POST['assign_delivery_boy'])) ? 1 : 0;

            // Fields shared by both the add and edit branches - same shape/column names
            // Seller_model::add_seller() already expects from seller/Login::update_user().
            $shared_seller_data = array(
                'national_identity_card' => $document_values['national_identity_card'],
                'authorized_signature' => $document_values['authorized_signature'],
                'store_logo' => $document_values['store_logo'],
                'first_name' => $this->input->post('first_name', true),
                'middle_name' => $this->input->post('middle_name', true),
                'last_name' => $this->input->post('last_name', true),
                'phone' => $this->input->post('phone', true),
                'email' => $this->input->post('email', true),
                'address1' => $this->input->post('address1', true),
                'district' => $this->input->post('district', true),
                'city' => $this->input->post('city', true),
                'state' => $this->input->post('state', true),
                'pin' => $this->input->post('pin', true),
                'shop_name' => $this->input->post('shop_name', true),
                'social' => $this->input->post('social', true),
                'shop_phone' => $this->input->post('shop_phone', true),
                'store_description' => $this->input->post('store_description', true),
                'category_ids' => $secondary_category_ids,
                'primary_category_id' => $primary_category_id,
                'pickup_address1' => $this->input->post('pickup_address1', true),
                'pickup_address2' => $this->input->post('pickup_address2', true),
                'pickup_district' => $this->input->post('pickup_district', true),
                'pickup_city' => $this->input->post('pickup_city', true),
                'pickup_state' => $this->input->post('pickup_state', true),
                'pickup_pin' => $this->input->post('pickup_pin', true),
                'entity_type' => $entity_type_posted,
                'legal_business_name' => $this->input->post('legal_business_name', true),
                'pan' => $this->input->post('pan', true),
                'gst' => $this->input->post('gst', true),
                'is_gst_registered' => $gst_check_set ? 0 : 1,
                'gst_enrollment_number' => $gst_check_set ? $this->input->post('gst_enrollment_number', true) : null,
                'business_address1' => $this->input->post('business_address1', true),
                'business_address2' => $this->input->post('business_address2', true),
                'business_district' => $this->input->post('business_district', true),
                'business_city' => $this->input->post('business_city', true),
                'business_state' => $this->input->post('business_state', true),
                'business_pin' => $this->input->post('business_pin', true),
                'account_number' => $this->input->post('account_number', true),
                'account_holder_name' => $this->input->post('account_holder_name', true),
                'ifsc' => $this->input->post('ifsc', true),
                'branch' => $this->input->post('branch', true),
                'bank_name' => $this->input->post('bank_name', true),
                'pan_card_document' => $document_values['pan_card_document'],
                'gstin_document' => $document_values['gstin_document'],
                'gst_enrollment_ack_document' => $document_values['gst_enrollment_ack_document'],
                'business_proof_document' => $document_values['business_proof_document'],
                'business_address_proof_document' => $document_values['business_address_proof_document'],
                'partnership_deed_document' => $document_values['partnership_deed_document'],
                'bank_account_proof_document' => $document_values['bank_account_proof_document'],
            );

            // Persists status/permissions/global commission directly against seller_data -
            // the shared Seller_model::add_seller() whitelist predates these columns and
            // silently drops them, and it's shared with the seller's own profile-save path
            // (seller/Login::update_user()), so it's safer to fix this here only rather than
            // touch that whitelist. Same direct-update pattern as save_verification_note().
            $persist_admin_only_fields = function ($target_user_id) use ($permmissions) {
                update_details([
                    'status' => $this->input->post('status', true),
                    'permissions' => json_encode($permmissions),
                    'commission' => (isset($_POST['global_commission']) && $_POST['global_commission'] !== '') ? $this->input->post('global_commission', true) : 0,
                ], ['user_id' => $target_user_id], 'seller_data');
            };

            if ($is_edit) {

                $target_user_id = $this->input->post('edit_seller', true);

                $current_status = fetch_details('seller_data', ['user_id' => $target_user_id], 'status');
                $current_status = !empty($current_status) ? $current_status[0] : ['status' => null];

                if ($current_status['status'] != $this->input->post('status', true)) {
                    $system_settings = get_settings('system_settings', true);
                    if ($this->input->post('status', true) == 0 || $this->input->post('status', true) == '0') {
                        $title = 'Account Deactivation Notice';
                        $fcm_admin_msg = 'We hope this message finds you well. We are writing to inform you about the deactivation of your seller account on our platform.';
                        $mail_admin_msg = 'We hope this message finds you well. We are writing to inform you about the deactivation of your seller account on our platform.Please be aware that this action is not reversible, and your access to the seller dashboard and associated services will be terminated.';
                    }
                    if ($this->input->post('status', true) == 1 || $this->input->post('status', true) == '1') {
                        $title = 'Congratulations! Your Seller Account Has Been Approved';
                        $fcm_admin_msg = 'We are delighted to inform you that your application to become an approved seller on our platform has been successful! Congratulations on this significant milestone.';
                        $mail_admin_msg = 'We are delighted to inform you that your application to become an approved seller on our platform has been successful! Congratulations on this significant milestone.With your approval, you gain access to a range of exclusive features and tools that will help you manage your business effectively. Our platform is designed to empower sellers like you, providing all the necessary resources to enhance your success.';
                    }
                    if ($this->input->post('status', true) == 2 || $this->input->post('status', true) == '2') {
                        $title = 'Update on Your Seller Account Application';
                        $fcm_admin_msg = 'We hope this message finds you well. We wanted to take a moment to inform you about the status of your recent seller account application with ' . $system_settings['app_name'];
                        $mail_admin_msg = 'We hope this message finds you well. We wanted to take a moment to inform you about the status of your recent seller account application with ' . $system_settings['app_name'] . 'We appreciate your interest in becoming a seller on our platform and thank you for taking the time to submit your application. We understand that starting your journey as a seller requires dedication and effort, and we value your commitment to becoming part of our growing community.';
                    }
                    $seller_fcm = fetch_details('users', ['id' => $target_user_id], 'fcm_id,email,username');
                    $seller_fcm_id[0] = $seller_fcm[0]['fcm_id'];

                    $registrationIDs_chunks = array_chunk($seller_fcm_id, 1000);

                    if (!empty($seller_fcm_id)) {
                        $fcmMsg = array(
                            'title' => $title,
                            'body' => $fcm_admin_msg,
                            'type' => "seller_account_update",
                            'content_available' => true
                        );
                        send_notification($fcmMsg, $registrationIDs_chunks);
                    }
                    $email_message = array(
                        'username' => 'Hello, Dear <b>' . ucfirst($seller_fcm[0]['username']) . '</b>, ',
                        'subject' => $title,
                        'message' => $mail_admin_msg
                    );
                    // send_mail($seller_fcm[0]['email'],  $title, $this->load->view('admin/pages/view/contact-email-template', $email_message, TRUE));
                }

                $seller_data = array_merge($shared_seller_data, [
                    'user_id' => $target_user_id,
                    'edit_seller_data_id' => $this->input->post('edit_seller_data_id', true),
                    'slug' => create_unique_slug($slug_source, 'seller_data', 'slug', 'user_id', $target_user_id),
                ]);
                $seller_profile = array(
                    'name' => $this->input->post('first_name', true),
                    'email' => $this->input->post('email', true),
                    'mobile' => $this->input->post('phone', true),
                    'address' => $this->input->post('address1', true),
                );
                if (!empty($new_seller_photo)) {
                    $seller_profile['image'] = $new_seller_photo;
                }

                $com_data = array();
                if (isset($_POST['commission_data']) && !empty($_POST['commission_data'])) {
                    $commission_data = json_decode($this->input->post('commission_data'), true);
                    if (is_array($commission_data['category_id'])) {
                        if (count($commission_data['category_id']) >= 2) {
                            $cat_array = array_unique($commission_data['category_id']);
                            foreach ($commission_data['commission'] as $key => $val) {
                                if (!array_key_exists($key, $cat_array)) unset($commission_data['commission'][$key]);
                            }
                            $cat_array = array_values($cat_array);
                            $com_array = array_values($commission_data['commission']);

                            for ($i = 0; $i < count($cat_array); $i++) {
                                $tmp['seller_id'] = $target_user_id;
                                $tmp['category_id'] = $cat_array[$i];
                                $tmp['commission'] = $com_array[$i];
                                $com_data[] = $tmp;
                            }
                        } else {
                            $com_data[0] = array(
                                "seller_id" => $target_user_id,
                                "category_id" => $commission_data['category_id'],
                                "commission" => $commission_data['commission'],
                            );
                        }
                    } else {
                        $com_data[0] = array(
                            "seller_id" => $target_user_id,
                            "category_id" => $commission_data['category_id'],
                            "commission" => $commission_data['commission'],
                        );
                    }
                }

                if ($this->Seller_model->add_seller($seller_data, $seller_profile, $com_data)) {
                    $persist_admin_only_fields($target_user_id);

                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Seller Update Successfully';
                    print_r(json_encode($this->response));
                } else {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = "Seller data was not updated";
                    print_r(json_encode($this->response));
                }
            } else {

                $identity_column = $this->config->item('identity', 'ion_auth');
                $email = strtolower($this->input->post('email'));
                $phone = $this->input->post('phone');
                $identity = ($identity_column == 'mobile') ? $phone : $email;
                $password = $this->input->post('password');

                $additional_data = [
                    'username' => $this->input->post('first_name', true),
                    'address' => $this->input->post('address1', true),
                    'type' => 'phone',
                ];

                $this->ion_auth->register($identity, $password, $email, $additional_data, ['4']);
                if (update_details(['active' => 1], [$identity_column => $identity], 'users')) {
                    $user_id = fetch_details('users', ['mobile' => $phone], 'id');
                    $target_user_id = $user_id[0]['id'];

                    $com_data = array();
                    if (isset($_POST['commission_data']) && !empty($_POST['commission_data'])) {
                        $commission_data = json_decode($this->input->post('commission_data'), true);

                        if (is_array($commission_data['category_id'])) {
                            if (count($commission_data['category_id']) >= 2) {
                                $cat_array = array_unique($commission_data['category_id']);
                                foreach ($commission_data['commission'] as $key => $val) {
                                    if (!array_key_exists($key, $cat_array)) unset($commission_data['commission'][$key]);
                                }
                                $cat_array = array_values($cat_array);
                                $com_array = array_values($commission_data['commission']);

                                for ($i = 0; $i < count($cat_array); $i++) {
                                    $tmp['seller_id'] = $target_user_id;
                                    $tmp['category_id'] = $cat_array[$i];
                                    $tmp['commission'] = $com_array[$i];
                                    $com_data[] = $tmp;
                                }
                            } else {
                                $com_data[0] = array(
                                    "seller_id" => $target_user_id,
                                    "category_id" => $commission_data['category_id'],
                                    "commission" => $commission_data['commission'],
                                );
                            }
                        } else {
                            $com_data[0] = array(
                                "seller_id" => $target_user_id,
                                "category_id" => $commission_data['category_id'],
                                "commission" => $commission_data['commission'],
                            );
                        }
                    }

                    $data = array_merge($shared_seller_data, [
                        'user_id' => $target_user_id,
                        'slug' => create_unique_slug($slug_source, 'seller_data', 'slug', 'user_id', $target_user_id),
                    ]);
                    $insert_id = $this->Seller_model->add_seller($data, [], $com_data);
                    if (!empty($insert_id)) {
                        $persist_admin_only_fields($target_user_id);

                        $this->response['error'] = false;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'Seller Added Successfully';
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
                    $this->response['message'] = 'Seller not Added.';
                    print_r(json_encode($this->response));
                }
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    // Kept deliberately separate from add_seller()'s huge legacy save flow - that method
    // rewrites almost every seller_data column from a giant hidden-field/file-upload form
    // that has no idea about the newer KYC schema. This just needs to persist the admin's
    // review note against a specific seller without touching anything else.
    public function save_verification_note()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('update', 'seller'), PERMISSION_ERROR_MSG, 'seller', false)) {
                return false;
            }

            $this->form_validation->set_rules('user_id', 'Seller', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('verification_note', 'Verification note', 'trim|required|xss_clean');

            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return;
            }

            $updated = update_details(
                ['verification_note' => $this->input->post('verification_note', true)],
                ['user_id' => $this->input->post('user_id', true)],
                'seller_data'
            );

            $this->response['error'] = !$updated;
            $this->response['message'] = $updated ? 'Verification note saved.' : 'Could not save the verification note.';
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_seller_commission_data()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $result = array();
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $id = $this->input->post('id', true);
                $result = $this->Seller_model->get_seller_commission_data($id);
                if (empty($result)) {
                    $result = $this->category_model->get_categories();
                }
            } else {
                $result = fetch_details('categories', "",  'id,name');
            }
            if (empty($result)) {
                $this->response['error'] = true;
                $this->response['message'] = "No category & commission data found for seller.";
                $this->response['data'] = [];
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                print_r(json_encode($this->response));
                return false;
            } else {
                $this->response['error'] = false;
                $this->response['data'] = $result;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                print_r(json_encode($this->response));
                return false;
            }
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            $this->response['data'] = [];
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            print_r(json_encode($this->response));
            return false;
        }
    }

    public function create_slug()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $tmpRow = $update_batch = array();
            $sellers = fetch_details('seller_data', 'slug IS NULL', 'id,store_name');
            if (!empty($sellers)) {
                foreach ($sellers as $row) {
                    $tmpRow['id'] = $row['id'];
                    $tmpRow['slug'] = create_unique_slug($row['store_name'], 'seller_data');
                    $this->Seller_model->create_slug($tmpRow);
                }
                $this->response['error'] = false;
                $this->response['message'] = "Slug Created Successfully.";
                $this->response['data'] = [];
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                print_r(json_encode($this->response));
                return false;
            } else {
                $this->response['error'] = true;
                $this->response['message'] = 'Already Created No need to create again.';
                $this->response['data'] = [];
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                print_r(json_encode($this->response));
                return false;
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function top_seller()
    {
        $this->Seller_model->top_sellers();
    }

    public function approved_sellers()
    {
        $this->Seller_model->approved_sellers();
    }
    public function not_approved_sellers()
    {
        $this->Seller_model->not_approved_sellers();
    }
    public function deactive_sellers()
    {
        $this->Seller_model->deactive_sellers();
    }
}
