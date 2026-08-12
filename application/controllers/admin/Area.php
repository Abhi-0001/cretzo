<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Area extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper', 'file']);
        $this->load->model(['Area_model', 'Setting_model']);

        // Was `!area || !city || !zipcodes`, i.e. it demanded ALL THREE modules to enter
        // the controller - so an admin granted only 'area' (or only 'city') was redirected
        // straight back out of the Areas page they had been given access to. This
        // controller serves areas, cities and zipcodes as separate pages, each with its
        // own per-action has_permissions() check further down, so entry only needs read
        // on at least one of them.
        if (!has_permissions('read', 'area') && !has_permissions('read', 'city') && !has_permissions('read', 'zipcodes')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        } else {
            $this->session->set_flashdata('authorize_flag', "");
        }
    }

    public function manage_areas()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (!has_permissions('read', 'area')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'manage-area';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Area Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Area Management  | ' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('areas', ['id' => $_GET['edit_id']]);
            }
            $this->data['city'] = fetch_details('cities', '');
            $this->data['zipcodes'] = fetch_details('zipcodes', '');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function view_area()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Area_model->get_list($table = 'areas');
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function manage_countries()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-countries';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Countries Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Countries Management  | ' . $settings['app_name'];
            $this->data['countries'] = fetch_details('countries', '');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function country_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Area_model->get_countries_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function get_cities()
    {
        $search = $this->input->get('search');
        $response = $this->Area_model->get_cities_list($search);
        echo json_encode($response);
    }


    public function get_zipcode_list()
    {
        $search = $this->input->get('search');
        $response = $this->Area_model->get_zipcode($search);
        echo json_encode($response);
    }
    public function add_area()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_area'])) {
                if (print_msg(!has_permissions('update', 'area'), PERMISSION_ERROR_MSG, 'area')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'area'), PERMISSION_ERROR_MSG, 'area')) {
                    return false;
                }
            }

            $this->form_validation->set_rules('area_name', ' Area Name ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('city', ' City ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('zipcode', ' Zipcode ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('minimum_free_delivery_order_amount', ' Minimum Free Delivery Amount ', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('delivery_charges', ' Delivery Charges ', 'trim|required|numeric|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                if (isset($_POST['edit_area'])) {
                    if (is_exist(['name' => $_POST['area_name'], 'city_id' => $_POST['city'], 'zipcode_id' => $_POST['zipcode']], 'areas', $_POST['edit_area'])) {
                        $response["error"]   = true;
                        $response["message"] = "Combination Already Exist ! Provide a unique Combination";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                } else {
                    if (is_exist(['name' => $_POST['area_name'], 'city_id' => $_POST['city'], 'zipcode_id' => $_POST['zipcode']], 'areas')) {
                        $response["error"]   = true;
                        $response["message"] = "Combination Already Exist ! Provide a unique Combination";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                }

                $saved = $this->Area_model->add_area($_POST);
                $this->response['error'] = !$saved;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_area'])) ? 'Area Updated Successfully' : 'Area Added Successfully';
                $this->response['message'] = $saved ? $message : 'Something went wrong.';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function bulk_update()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Was reachable by anyone who merely passed the constructor's blanket read-only
            // gate - this bulk-overwrites the delivery charge/free-delivery threshold for
            // every area in a city with no create/update permission check of its own.
            if (print_msg(!has_permissions('update', 'area'), PERMISSION_ERROR_MSG, 'area')) {
                return false;
            }

            $this->form_validation->set_rules('city', ' City ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('bulk_update_minimum_free_delivery_order_amount', ' Minimum Free Delivery Amount ', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('bulk_update_delivery_charges', ' Delivery Charges ', 'trim|required|numeric|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $updated = $this->Area_model->bulk_edit_area($_POST);
                $this->response['error'] = !$updated;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $updated ? 'Delivery Charge Updated Successfully' : 'Something went wrong.';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function manage_cities()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (!has_permissions('read', 'city')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'manage-city';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'City Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' City Management  | ' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                // cities' real primary key column is city_id, not id - this previously
                // threw a raw "Unknown column 'id'" database error the moment anyone clicked
                // Edit on a city, making city editing completely unreachable.
                $this->data['fetched_data'] = fetch_details('cities', ['city_id' => $_GET['edit_id']]);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_city()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Area_model->get_list($table = 'cities');
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function delete_city()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // $_GET['table'] used to go straight into delete_details() with no whitelist at
            // all - this is a shared generic delete-by-id endpoint (used by cities, areas,
            // pickup locations, AND subscription plans, per their respective list pages), so an
            // unrestricted table name meant any admin who could reach this URL could delete a
            // row from ANY table in the database, not just the four this button is meant for.
            $allowed_tables = ['cities', 'area', 'pickup_locations', 'subscriptions'];
            $table = trim((string) $_GET['table']);
            if (!in_array($table, $allowed_tables, true)) {
                $response['error'] = true;
                $response['message'] = 'Invalid request';
                echo json_encode($response);
                return false;
            }

            // Was checking 'area' permission for every non-'cities' table, including
            // 'pickup_locations' AND 'cities' itself - a role granted delete on City (or
            // Pickup Location) but not on Area, or vice versa, got the wrong answer. 'subscriptions'
            // intentionally stays on the 'area' check it already had - that module was never
            // registered in eshop.php, and this whole feature has no real permission gating yet
            // (flagged separately, not fixed, in an earlier pass) - switching it to a
            // never-registered module here would newly deny every non-owner admin rather than
            // just correct a mismatch.
            if ($table === 'pickup_locations') {
                $permission_module = 'pickup_location';
            } elseif ($table === 'cities') {
                $permission_module = 'city';
            } else {
                $permission_module = 'area';
            }
            if (print_msg(!has_permissions('delete', $permission_module), PERMISSION_ERROR_MSG, $permission_module)) {
                return false;
            }
            if ($table == 'cities') {
                delete_details(['city_id' => $_GET['id']], 'areas');
            }

            // Deleting a subscription plan while sellers are still on it doesn't just orphan a
            // reference - Seller_subscription_model::get_listing_limit() treats a missing plan
            // row as "no limit configured" and silently grants those sellers UNLIMITED listings,
            // not an error. Same reasoning as the brand delete-guard added earlier this pass.
            if ($table == 'subscriptions') {
                $this->load->model('Subscription_model');
                $sellers_on_plan = $this->db->where('subscription_id', $_GET['id'])->count_all_results('seller_subscriptions');
                if ($sellers_on_plan > 0) {
                    $response['error'] = true;
                    $response['message'] = 'This plan is still assigned to ' . $sellers_on_plan . ' seller subscription(s). Move them to another plan before deleting.';
                    echo json_encode($response);
                    return false;
                }
            }

            // cities' real PK column is city_id, not id - deleting a city with the generic
            // 'id' key previously threw a raw "Unknown column 'id'" database error.
            $delete_key = ($table === 'cities') ? 'city_id' : 'id';
            if (delete_details([$delete_key => $_GET['id']], $table)) {
                $response['error'] = false;
                $response['message'] = 'Deleted Successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong';
            }
            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_city()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (isset($_POST['edit_city'])) {
                if (print_msg(!has_permissions('update', 'city'), PERMISSION_ERROR_MSG, 'city')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'city'), PERMISSION_ERROR_MSG, 'city')) {
                    return false;
                }
            }

            // Was "is_unique[cities.name]" - cities has no "name" column (its real name
            // column is city_name), so this threw a raw database error on every single
            // Add/Edit City submission. Also, being wired to run on BOTH add and edit meant
            // that even with the column name fixed, saving an edit without changing the name
            // would always fail (the name still "exists" as the row being edited) - the
            // uniqueness check belongs only in the is_exist()-based check below, matching how
            // every sibling create/edit flow in this file (area, zipcode) already handles it.
            $this->form_validation->set_rules('city_name', ' City Name ', 'trim|required|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                if (isset($_POST['edit_city'])) {
                    // is_exist()'s update-id exclusion path hardcodes 'id' as the PK column
                    // (correct for every other table this helper is used against) - cities is
                    // the one table in this app whose real PK is city_id, so checked inline here
                    // instead of teaching the shared helper about a table-specific exception.
                    $duplicate = $this->db->where('city_name', $_POST['city_name'])->where_not_in('city_id', $_POST['edit_city'])->get('cities')->num_rows() > 0;
                    if ($duplicate) {
                        $response["error"]   = true;
                        $response["message"] = "City Name Already Exist ! Provide a unique name";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                } else {
                    if (is_exist(['city_name' => $_POST['city_name']], 'cities')) {
                        $response["error"]   = true;
                        $response["message"] = "City Name Already Exist ! Provide a unique name";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                }
                $saved = $this->Area_model->add_city($_POST);
                $this->response['error'] = !$saved;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_city'])) ? 'City Updated Successfully' : 'City Added Successfully';
                $this->response['message'] = $saved ? $message : 'Something went wrong.';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    // manage zipcodes

    public function manage_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (!has_permissions('read', 'zipcodes')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'manage-zipcodes';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Zipcodes Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Zipcode Management  | ' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('zipcodes', ['id' => $_GET['edit_id']]);
            }
            $this->data['city'] = fetch_details('cities', '');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Area_model->get_zipcode_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function get_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            $limit = (isset($_GET['limit'])) ? $this->input->post('limit', true) : 25;
            $offset = (isset($_GET['offset'])) ? $this->input->post('offset', true) : 0;
            $search =  (isset($_GET['search'])) ? $_GET['search'] : null;
            $zipcodes = $this->Area_model->get_zipcodes($search, $limit, $offset);
            $this->response['data'] = $zipcodes['data'];
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_zipcode()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (isset($_POST['edit_zipcode'])) {
                if (print_msg(!has_permissions('update', 'zipcodes'), PERMISSION_ERROR_MSG, 'zipcodes')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'zipcodes'), PERMISSION_ERROR_MSG, 'zipcodes')) {
                    return false;
                }
            }

            $this->form_validation->set_rules('city', ' City ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('zipcode', ' Zipcode ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('minimum_free_delivery_order_amount', ' Minimum Free Delivery Amount ', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('delivery_charges', ' Delivery Charges ', 'trim|required|numeric|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {

                if (isset($_POST['edit_zipcode'])) {
                    if (is_exist(['city_id' => $_POST['city'], 'zipcode' => $_POST['zipcode']], 'zipcodes', $_POST['edit_zipcode'])) {
                        $response["error"]   = true;
                        $response["message"] = "Combination Already Exist ! Provide a unique Combination";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                } else {
                    if (is_exist(['city_id' => $_POST['city'], 'zipcode' => $_POST['zipcode']], 'zipcodes')) {
                        $response["error"]   = true;
                        $response["message"] = "Combination Already Exist ! Provide a unique Combination";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                }
                $saved = $this->Area_model->add_zipcode($_POST);
                $this->response['error'] = !$saved;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_zipcode'])) ? 'Zipcode Updated Successfully' : 'Zipcode Added Successfully';
                $this->response['message'] = $saved ? $message : 'Something went wrong.';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function delete_zipcode()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('delete', 'zipcodes'), PERMISSION_ERROR_MSG, 'zipcodes')) {
                return false;
            }

            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $response['error'] = true;
                $response['message'] = 'Invalid zipcode id';
                echo json_encode($response);
                return false;
            }
            $zipcode_id = (int) $_GET['id'];

            if (!is_exist(['id' => $zipcode_id], 'zipcodes')) {
                $response['error'] = true;
                $response['message'] = 'Zipcode not found';
                echo json_encode($response);
                return false;
            }

            delete_details(['zipcode_id' => $zipcode_id], 'areas');
            if (delete_details(['id' => $zipcode_id], 'zipcodes')) {
                // A deleted zipcode's id can still be sitting inside a product's
                // comma-separated deliverable_zipcodes list (Included/Excluded delivery
                // areas) - strip it out everywhere it's referenced so the list doesn't
                // silently point at an id that no longer exists.
                $this->db->query(
                    "UPDATE products SET deliverable_zipcodes = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', deliverable_zipcodes, ','), CONCAT(',', ?, ','), ',')) WHERE FIND_IN_SET(?, deliverable_zipcodes)",
                    [$zipcode_id, $zipcode_id]
                );
                $response['error'] = false;
                $response['message'] = 'Deleted Successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something went wrong';
            }
            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function location_bulk_upload()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'location-bulk-upload';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Bulk Upload | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Bulk Upload | ' . $settings['app_name'];

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function process_bulk_upload()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Was checking 'create' on the unrelated 'product' module (a copy-paste
            // leftover) regardless of what this specific upload actually creates/updates -
            // cities, areas, or zipcodes. A role granted product-create access but no
            // area/city/zipcode access could bulk-write location data regardless, while a
            // role correctly granted area/city/zipcode access alone was wrongly blocked from
            // using this page's own bulk upload feature.
            $location_type = isset($_POST['location_type']) ? $_POST['location_type'] : '';
            $upload_type = isset($_POST['type']) ? $_POST['type'] : '';
            $module_map = ['city' => 'city', 'area' => 'area', 'zipcode' => 'zipcodes'];
            $permission_module = isset($module_map[$location_type]) ? $module_map[$location_type] : null;
            $permission_action = ($upload_type === 'update') ? 'update' : 'create';
            if ($permission_module === null || print_msg(!has_permissions($permission_action, $permission_module), PERMISSION_ERROR_MSG, $permission_module)) {
                return false;
            }
            $this->form_validation->set_rules('bulk_upload', '', 'xss_clean');
            $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
            $this->form_validation->set_rules('location_type', 'Location Type', 'trim|required|xss_clean');
            if (empty($_FILES['upload_file']['name'])) {
                $this->form_validation->set_rules('upload_file', 'File', 'trim|required|xss_clean', array('required' => 'Please choose file'));
            }

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $allowed_mime_type_arr = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv');
                $mime = get_mime_by_extension($_FILES['upload_file']['name']);
                if (!in_array($mime, $allowed_mime_type_arr)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Invalid file format!';
                    print_r(json_encode($this->response));
                    return false;
                }
                $csv = $_FILES['upload_file']['tmp_name'];
                $temp = 0;
                $temp1 = 0;
                $handle = fopen($csv, "r");
                $allowed_status = array("received", "processed", "shipped");
                $video_types = array("youtube", "vimeo");
                $this->response['message'] = '';
                $type = $this->input->post('type', true);
                $location_type = $this->input->post('location_type', true);
                if ($type == 'upload' && $location_type == 'zipcode') {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        if ($temp != 0) {
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Zipcode is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (empty($row[1])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'City Id is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (!empty($row[1]) && $row[1] != "") {
                                if (!is_exist(['city_id' => $row[1]], 'cities')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'City is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }
                            if (empty($row[2])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Minimum Free Delivery Order Amount is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (is_exist(['city_id' => $row[1], 'zipcode' => $row[0]], 'zipcodes')) {
                                $response["error"]   = true;
                                $response["message"] = "Combination Already Exist ! Provide a unique Combination";
                                $response['csrfName'] = $this->security->get_csrf_token_name();
                                $response['csrfHash'] = $this->security->get_csrf_hash();
                                $response["data"] = array();
                                echo json_encode($response);
                                return false;
                            }
                        }
                        $temp++;
                    }

                    fclose($handle);
                    $handle = fopen($csv, "r");
                    // A failure partway through this loop used to leave a half-imported file
                    // with no way to tell where it stopped - now all-or-nothing.
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp1 != 0) {
                            $data['zipcode'] = $row[0];
                            $data['city_id'] = $row[1];
                            $data['minimum_free_delivery_order_amount'] = $row[2];
                            $data['delivery_charges'] = $row[3];
                            $this->db->insert('zipcodes', $data);
                        }
                        $temp1++;
                    }
                    $this->db->trans_complete();
                    fclose($handle);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Zipcodes uploaded successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else if ($type == 'upload' && $location_type == 'city') {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        if ($temp != 0) {
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'City Name is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                        }
                        $temp++;
                    }

                    fclose($handle);
                    $handle = fopen($csv, "r");
                    // cities' real name column is city_name, not name - this previously threw
                    // a raw "Unknown column" database error on every bulk city upload.
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp1 != 0) {
                            $data['city_name'] = $row[0];
                            $this->db->insert('cities', $data);
                        }
                        $temp1++;
                    }
                    $this->db->trans_complete();
                    fclose($handle);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Cities uploaded successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else if ($type == 'upload' && $location_type == 'area') {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        if ($temp != 0) {
                            if (empty($row[0]) && $row[0] == "") {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Area name is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (empty($row[1]) && $row[1] == "") {
                                $this->response['error'] = true;
                                $this->response['message'] = 'City id is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (!empty($row[1]) && $row[1] != "") {
                                if (!is_exist(['city_id' => $row[1]], 'cities')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'City is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }
                            if (empty($row[2]) && $row[2] == "") {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Zipcode id is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (!empty($row[2]) && $row[2] != "") {
                                if (!is_exist(['id' => $row[2]], 'zipcodes')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'Zipcode is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }
                            if (is_exist(['name' => $row[0], 'city_id' => $row[1], 'zipcode_id' => $row[2]], 'areas')) {
                                $response["error"]   = true;
                                $response["message"] = "Combination Already Exist ! Provide a unique Combination at row $temp";
                                $response['csrfName'] = $this->security->get_csrf_token_name();
                                $response['csrfHash'] = $this->security->get_csrf_hash();
                                $response["data"] = array();
                                echo json_encode($response);
                                return false;
                            }
                        }
                        $temp++;
                    }

                    fclose($handle);
                    $handle = fopen($csv, "r");
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp1 != 0) {
                            $data['name'] = $row[0];
                            $data['city_id'] = $row[1];
                            $data['zipcode_id'] = $row[2];
                            $data['minimum_free_delivery_order_amount'] = (isset($row[3]) && $row[3] != "") ? $row[3] : 100;
                            $data['delivery_charges'] = (isset($row[4]) && $row[4] != "") ? $row[4] : 0;
                            $this->db->insert('areas', $data);
                        }
                        $temp1++;
                    }
                    $this->db->trans_complete();
                    fclose($handle);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Areas uploaded successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else if ($type == 'update' && $location_type == 'zipcode') {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp != 0) {
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Zipcode id empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[0]) && $row[0] != "") {
                                if (!is_exist(['id' => $row[0]], 'zipcodes')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'Zipcode id is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }

                            if (empty($row[1])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Zipcode empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (empty($row[2])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'City Id is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (!empty($row[2]) && $row[2] != "") {
                                if (!is_exist(['city_id' => $row[2]], 'cities')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'City is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }
                            if (empty($row[3])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Minimum Free Delivery Order Amount is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (is_exist(['city_id' => $row[2], 'zipcode' => $row[1]], 'zipcodes', $row[0])) {
                                $response["error"]   = true;
                                $response["message"] = "Combination Already Exist ! Provide a unique Combination";
                                $response['csrfName'] = $this->security->get_csrf_token_name();
                                $response['csrfHash'] = $this->security->get_csrf_hash();
                                $response["data"] = array();
                                echo json_encode($response);
                                return false;
                            }
                        }
                        $temp++;
                    }
                    fclose($handle);
                    $handle = fopen($csv, "r");
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {

                        if ($temp1 != 0) {
                            $zipcode_id = $row[0];
                            $zipcode = fetch_details('zipcodes', ['id' => $zipcode_id], '*');
                            if (!empty($zipcode)) {
                                if (!empty($row[1])) {
                                    $data['zipcode'] = $row[1];
                                    $data['city_id'] = $row[2];
                                    $data['minimum_free_delivery_order_amount'] = $row[3];
                                    $data['delivery_charges'] = $row[4];
                                } else {
                                    $data['zipcode'] = $zipcode[0]['zipcode'];
                                    $data['city_id'] = $row[2];
                                    $data['minimum_free_delivery_order_amount'] = $row[3];
                                    $data['delivery_charges'] = $row[4];
                                }
                                $this->db->where('id', $zipcode_id)->update('zipcodes', $data);
                            } else {
                                $this->response['error'] = true;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                $this->response['message'] = 'Zipcode id: ' . $zipcode_id . ' not exist!';
                            }
                        }
                        $temp1++;
                    }
                    $this->db->trans_complete();
                    fclose($handle);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Zipcodes updated successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else if ($type == 'update' && $location_type == 'city') {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp != 0) {
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'City id empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[0]) && $row[0] != "") {
                                if (!is_exist(['city_id' => $row[0]], 'cities')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'City id is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }

                            if (empty($row[1])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'City name empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                        }
                        $temp++;
                    }
                    fclose($handle);
                    $handle = fopen($csv, "r");
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        if ($temp1 != 0) {
                            $city_id = $row[0];
                            // cities' real PK/name columns are city_id/city_name, not id/name -
                            // this whole branch was fetching/matching/writing against columns
                            // that don't exist on this table, which would have thrown a raw SQL
                            // error on every bulk "update city" upload that reached this row.
                            $city = fetch_details('cities', ['city_id' => $city_id], '*');
                            if (!empty($city)) {
                                if (!empty($row[1])) {
                                    $data['city_name'] = $row[1];
                                } else {
                                    $data['city_name'] = $city[0]['city_name'];
                                }
                                $this->db->where('city_id', $city_id)->update('cities', $data);
                            } else {
                                $this->response['error'] = true;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                $this->response['message'] = 'City id: ' . $city_id . ' not exist!';
                            }
                        }
                        $temp1++;
                    }
                    $this->db->trans_complete();
                    fclose($handle);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'City updated successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else if ($type == 'update' && $location_type == 'area') {
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp != 0) {
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Area id empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }

                            if (!empty($row[0]) && $row[0] != "") {
                                if (!is_exist(['id' => $row[0]], 'areas')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'Area id is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }

                            if (empty($row[1])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Area name empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (!empty($row[2]) && $row[2] != "") {
                                if (!is_exist(['city_id' => $row[2]], 'cities')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'City is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }
                            if (!empty($row[3]) && $row[3] != "") {
                                if (!is_exist(['id' => $row[3]], 'zipcodes')) {
                                    $this->response['error'] = true;
                                    $this->response['message'] = 'Zipcode is not exist in your database at row ' . $temp;
                                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                    print_r(json_encode($this->response));
                                    return false;
                                }
                            }
                        }
                        $temp++;
                    }
                    fclose($handle);
                    $handle = fopen($csv, "r");
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        if ($temp1 != 0) {
                            $area_id = $row[0];
                            $area = fetch_details('areas', ['id' => $area_id], '*');
                            if (!empty($area)) {
                                if (!empty($row[1])) {
                                    $data['name'] = $row[1];
                                } else {
                                    $data['name'] = $area[0]['name'];
                                }
                                if (!empty($row[2])) {
                                    $data['city_id'] = $row[2];
                                } else {
                                    $data['city_id'] = $area[0]['city_id'];
                                }
                                if (!empty($row[3])) {
                                    $data['zipcode_id'] = $row[3];
                                } else {
                                    $data['zipcode_id'] = $area[0]['zipcode_id'];
                                }
                                if (!empty($row[4])) {
                                    $data['minimum_free_delivery_order_amount'] = $row[4];
                                } else {
                                    $data['minimum_free_delivery_order_amount'] = $area[0]['minimum_free_delivery_order_amount'];
                                }
                                if (!empty($row[5])) {
                                    $data['delivery_charges'] = $row[5];
                                } else {
                                    $data['delivery_charges'] = $area[0]['delivery_charges'];
                                }
                                $this->db->where('id', $area_id)->update('areas', $data);
                            } else {
                                $this->response['error'] = true;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                $this->response['message'] = 'Area id: ' . $area_id . ' not exist!';
                            }
                        }
                        $temp1++;
                    }
                    $this->db->trans_complete();
                    fclose($handle);
                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Area updated successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'Invalid Type or Type Location!';
                    print_r(json_encode($this->response));
                    return false;
                }
            }
        }
    }

    public function table_sync()
    {
        // Was reachable by anyone who merely passed the constructor's blanket read-only
        // gate, despite mutating the zipcodes table's schema and bulk-overwriting rows.
        if (print_msg(!has_permissions('update', 'zipcodes'), PERMISSION_ERROR_MSG, 'zipcodes')) {
            return false;
        }
        $columns_to_check = array('city_id', 'minimum_free_delivery_order_amount', 'delivery_charges'); // Add the column names you want to check
        // check if $columns_to_check is exist in zipcodes table if not add that column
        if ($this->db->field_exists('city_id', 'zipcodes')) {
            $response_data['error'] =  false;
            $response_data['message'] =  'Zipcode table is already sync with Area table no need to sync it again.';
            print_r(json_encode($response_data));
        } else {
            foreach ($columns_to_check as $column) {
                if (!$this->db->field_exists($column, 'zipcodes')) {
                    $this->add_field($column);
                }
            }
            // Get data from the area table

            // cities' real PK/name columns are city_id/city_name, not id/name - this join
            // previously referenced a nonexistent cities.id and would have thrown a raw SQL
            // error on every sync attempt for an install with any area data to sync.
            $query = $this->db->select(' areas.* , cities.city_name as city_name , zipcodes.zipcode as zipcode')->join('cities', 'areas.city_id=cities.city_id')->join('zipcodes', 'areas.zipcode_id=zipcodes.id');
            $area_data = $query->get('areas')->result_array();

            if (!empty($area_data)) {
                // Process data in chunks of 500 records
                $chunks = array_chunk($area_data, 500);
                if (isset($chunks) && !empty($chunks)) {
                    foreach ($chunks as $chunk) {
                        $this->process_chunk($chunk);
                    }
                    $response_data['error'] =  false;
                    $response_data['message'] =  'Zipcode table sync with Area table successfully.';
                } else {
                    $response_data['error'] =  true;
                    $response_data['message'] =  'No data found for sync.';
                }
                print_r(json_encode($response_data));
            }
        }
    }
    public function add_field($field_name)
    {
        // Was directly callable at admin/area/add_field/<segment> with no permission check
        // of its own (only table_sync()'s internal calls were ever meant to reach it), and
        // relied on the constructor's blanket read-only gate.
        if (print_msg(!has_permissions('update', 'zipcodes'), PERMISSION_ERROR_MSG, 'zipcodes')) {
            return false;
        }
        $this->load->dbforge();
        $fields = null;
        if (isset($field_name) && $field_name == 'city_id') {
            // Add city_id field to the zipcode table

            $fields = array(
                'city_id' => array(
                    'type' => 'INT',
                    'constraint'     => '11',
                    'null' => FALSE,
                    'after' => 'zipcode'
                ),
            );
        }
        if (isset($field_name) && $field_name == 'minimum_free_delivery_order_amount') {
            // Add minimum_free_delivery_order_amount field to the zipcode table

            $fields = array(
                'minimum_free_delivery_order_amount' => array(
                    'type' => 'DOUBLE',
                    'null' => FALSE,
                    'default' => 0,
                    'after' => 'city_id'
                ),
            );
        }
        if (isset($field_name) && $field_name == 'delivery_charges') {
            // Add delivery_charges field to the zipcode table

            $fields = array(
                'delivery_charges' => array(
                    'type' => 'DOUBLE',
                    'null' => FALSE,
                    'default' => 0,
                    'after' => 'minimum_free_delivery_order_amount'
                ),
            );
        }

        if (empty($fields)) {
            return false;
        }
        $this->dbforge->add_column('zipcodes', $fields);
    }

    // private: an internal bulk-import helper called only as $this->process_chunk().
    // As a public method it was routable as /admin/area/process_chunk and performs
    // update_details() on the zipcodes table.
    private function process_chunk($chunk)
    {
        foreach ($chunk as $row) {
            $existing_record = $this->db->get_where('zipcodes', array('zipcode' => $row['zipcode']))->row_array();
            if ($existing_record['minimum_free_delivery_order_amount'] == 0 || $existing_record['delivery_charges'] == 0) {
                // Insert the record into the zipcode table
                $set = [
                    'minimum_free_delivery_order_amount' => $row['minimum_free_delivery_order_amount'],
                    'delivery_charges' => $row['delivery_charges'],
                    'city_id' => $row['city_id'],
                ];
                update_details($set, ['zipcode' => $row['zipcode']], 'zipcodes');
            }
        }
    }
}
