<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Brand extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['Brand_model']);

        if (!has_permissions('read', 'brands')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-brands';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Brand Management | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Brand Management | ' . $settings['app_name'];
            // Was a dead if/else checking a local $id that was never defined anywhere in this
            // method (always fell to the else) - get_brand_list() doesn't read an id filter
            // either, so this never did anything; simplified to what actually always ran.
            $this->data['base_brand_url'] = base_url() . 'admin/brand/brand_list';
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function create_brand()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'brand';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) ? 'Edit Brand | ' . $settings['app_name'] : 'Add Brand | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Brand , Create Brand | ' . $settings['app_name'];
            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('brands', ['id' => $_GET['edit_id']]);
            }
            $this->load->model(['Brand_model']);
            // $this->data['brands'] = $this->Brand_model->get_brands();

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function add_brand()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_brand'])) {
                if (print_msg(!has_permissions('update', 'brands'), PERMISSION_ERROR_MSG, 'brands')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'brands'), PERMISSION_ERROR_MSG, 'brands')) {
                    return false;
                }
            }

            if (isset($_POST['edit_brand'])) {
                $this->form_validation->set_rules('brand_input_name', 'Brand Name', 'trim|required|xss_clean|edit_unique[brands.name.' . $_POST['edit_brand'] . ']');
                $this->form_validation->set_rules('brand_input_image', 'Image', 'trim|xss_clean');
            } else {
                $this->form_validation->set_rules('brand_input_name', 'Brand Name', 'trim|required|xss_clean|is_unique[brands.name]');
                $this->form_validation->set_rules('brand_input_image', 'Image', 'trim|required|xss_clean', array('required' => 'Brand image is required'));
            }


            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {

                $this->Brand_model->add_brand($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_brand'])) ? 'Brand Updated Successfully' : 'Brand Added Successfully';
                $this->response['message'] = $message;
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    function delete_brand()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'brands'), PERMISSION_ERROR_MSG, 'brands')) {
                return false;
            }
            if (!is_numeric($_GET['id'])) {
                $this->response['error'] = true;
                $this->response['message'] = 'Invalid request';
                print_r(json_encode($this->response));
                return false;
            }

            // delete_brand() used to unconditionally return TRUE (even for a missing/invalid id
            // or a failed query), and this branch had no else, so a genuine failure would print
            // nothing at all - an empty response body the AJAX call's dataType:'json' can't
            // parse. The model now reports what actually happened, including refusing to delete
            // a brand still assigned to real products.
            $result = $this->Brand_model->delete_brand((int) $_GET['id']);
            $this->response['error'] = !$result['success'];
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = $result['message'];
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function brand_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Brand_model->get_brand_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function bulk_upload()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'brand-bulk-upload';
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
            // This endpoint bulk-inserts/updates brands, not products - it was checking the
            // 'product' permission module instead of 'brands', so a role with product-management
            // access (but no brand access at all) could bulk-create/edit brands via CSV, while a
            // role correctly granted brand management (but not product management) would be
            // wrongly blocked from using this page.
            if (print_msg(!has_permissions('create', 'brands'), PERMISSION_ERROR_MSG, 'brands')) {
                return false;
            }

            // When a POST body exceeds post_max_size, PHP discards both $_POST and $_FILES and
            // hands the script an empty request. Without this check the admin was told "The Type
            // field is required" even after selecting one, with no hint the real problem was size.
            if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'The file is larger than this server accepts (limit: ' . ini_get('post_max_size') . '). Please split it into smaller files and upload them one at a time.';
                echo json_encode($this->response);
                return false;
            }

            $this->form_validation->set_rules('bulk_upload', '', 'xss_clean');
            $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
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

                // The upload status was never inspected. When an upload failed - a file above
                // upload_max_filesize, a partial transfer, or a missing temp directory - PHP
                // leaves tmp_name empty while still populating the file name, so the extension
                // check above passed and execution reached fopen(''). On PHP 8 that throws an
                // uncaught ValueError -> HTTP 500, and with no error handling on the page the
                // submit button stayed on "Please Wait..." indefinitely with nothing shown.
                $upload_error = isset($_FILES['upload_file']['error']) ? $_FILES['upload_file']['error'] : UPLOAD_ERR_NO_FILE;
                if ($upload_error !== UPLOAD_ERR_OK) {
                    $upload_messages = [
                        UPLOAD_ERR_INI_SIZE   => 'The file is larger than this server accepts (limit: ' . ini_get('upload_max_filesize') . '). Please split it into smaller files.',
                        UPLOAD_ERR_FORM_SIZE  => 'The file is larger than this form accepts. Please split it into smaller files.',
                        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_FILE    => 'Please choose a file to upload.',
                        UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder configured for uploads. Please contact your hosting provider.',
                        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file to disk. Please contact your hosting provider.',
                        UPLOAD_ERR_EXTENSION  => 'The upload was blocked by a server extension.',
                    ];
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = isset($upload_messages[$upload_error]) ? $upload_messages[$upload_error] : 'The file could not be uploaded. Please try again.';
                    echo json_encode($this->response);
                    return false;
                }

                $csv = $_FILES['upload_file']['tmp_name'];

                if (empty($csv) || !is_uploaded_file($csv)) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'The uploaded file could not be read. Please try again.';
                    echo json_encode($this->response);
                    return false;
                }

                $temp = 0;
                $temp1 = 0;
                $handle = fopen($csv, "r");

                if ($handle === false) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = 'The uploaded file could not be opened. Please try again.';
                    echo json_encode($this->response);
                    return false;
                }

                $this->response['message'] = '';
                $type = $_POST['type'];
                if ($type == 'upload') {
                    // Names seen so far in THIS file - is_exist() below only checks against
                    // brands already saved to the database, so two rows in the same CSV sharing
                    // an identical name both passed validation and were both inserted (silently
                    // producing two brands with the same name, told apart only by their
                    // auto-suffixed slugs).
                    $seen_names = [];
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
                    {
                        if ($temp != 0) {
                            // "upload" expects exactly [name, image] - accepting any row shape
                            // silently swallowed rows from the wrong CSV layout (e.g. the
                            // 3-column "update" template, where a numeric brand id would have
                            // been stored as the brand's name and the real name as its image).
                            if (count($row) !== 2) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Row ' . $temp . ' does not have the expected 2 columns (name, image). Check you are using the upload sample file, not the update one.';
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Name is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (!empty($row[0])) {
                                if (is_exist(['name' => trim($row[0])], 'brands')) {
                                    $response["error"]   = true;
                                    $response["message"] = "brand Already Exist! Provide another brand name at row." . $temp;
                                    $response['csrfName'] = $this->security->get_csrf_token_name();
                                    $response['csrfHash'] = $this->security->get_csrf_hash();
                                    $response["data"] = array();
                                    echo json_encode($response);
                                    return false;
                                }
                                $name_key = strtolower(trim($row[0]));
                                if (isset($seen_names[$name_key])) {
                                    $response["error"]   = true;
                                    $response["message"] = "Duplicate brand name at row " . $temp . " (already used at row " . $seen_names[$name_key] . " in this same file).";
                                    $response['csrfName'] = $this->security->get_csrf_token_name();
                                    $response['csrfHash'] = $this->security->get_csrf_hash();
                                    $response["data"] = array();
                                    echo json_encode($response);
                                    return false;
                                }
                                $seen_names[$name_key] = $temp;
                            }
                            if (empty($row[1])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Image is empty at row ' . $temp;
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
                    // Every row is written in one transaction. Previously each brand was committed
                    // as it was read, so any failure part-way through a file left the catalogue
                    // holding a partial import with no record of where it stopped.
                    $this->db->trans_start();
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp1 != 0) {
                            $data['name'] = trim($row[0]);
                            $data['slug'] = create_unique_slug(trim($row[0]), 'brands');
                            $data['image'] = $row[1];
                            $data['status'] = 1;
                            $this->db->insert('brands', $data);
                        }
                        $temp1++;
                    }
                    fclose($handle);
                    $this->db->trans_complete();

                    if ($this->db->trans_status() === false) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'The import failed and no brands were added. Please check the file and try again.';
                        echo json_encode($this->response);
                        return false;
                    }

                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = max(0, $temp1 - 1) . ' brand(s) uploaded successfully!';
                    print_r(json_encode($this->response));
                    return false;
                } else { // bulk_update
                    while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
                    {
                        if ($temp != 0) {
                            // "update" expects exactly [id, name, image] - same wrong-CSV-layout
                            // guard as the upload branch above.
                            if (count($row) !== 3) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Row ' . $temp . ' does not have the expected 3 columns (brand id, name, image). Check you are using the update sample file, not the upload one.';
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (empty($row[0])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'brand id is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (!empty($row[0])) {
                                if (!is_exist(['id' => $row[0]], 'brands')) {
                                    $response["error"]   = true;
                                    $response["message"] = "brand is not exist Provide another brand id at row." . $temp;
                                    $response['csrfName'] = $this->security->get_csrf_token_name();
                                    $response['csrfHash'] = $this->security->get_csrf_hash();
                                    $response["data"] = array();
                                    echo json_encode($response);
                                    return false;
                                }
                            }
                            if (empty($row[1])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Name is empty at row ' . $temp;
                                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                                print_r(json_encode($this->response));
                                return false;
                            }
                            if (empty($row[2])) {
                                $this->response['error'] = true;
                                $this->response['message'] = 'Image is empty at row ' . $temp;
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
                        if (
                            $temp1 != 0
                        ) {
                            $brand_id = $row[0];
                            $brands = fetch_details('brands', ['id' => $brand_id], '*');
                            if (isset($brands[0]) && !empty($brands[0])) {
                                // $data was never reset between rows, and this branch used to
                                // leave 'slug' untouched whenever a row's name column was empty -
                                // so a row with no name silently inherited whatever slug the
                                // PREVIOUS row in the file had just set, corrupting this brand's
                                // slug to an unrelated, stale value.
                                if (!empty($row[1])) {
                                    $data['name'] = trim($row[1]);
                                    $data['slug'] = create_unique_slug(trim($row[1]), 'brands');
                                } else {
                                    $data['name'] = $brands[0]['name'];
                                    $data['slug'] = $brands[0]['slug'];
                                }
                                if (!empty($row[2])) {
                                    $data['image'] = $row[2];
                                } else {
                                    $data['image'] = $brands[0]['image'];
                                }
                                $this->db->where('id', $row[0])->update('brands', $data);
                            }
                        }
                        $temp1++;
                    }
                    fclose($handle);
                    $this->db->trans_complete();

                    if ($this->db->trans_status() === false) {
                        $this->response['error'] = true;
                        $this->response['csrfName'] = $this->security->get_csrf_token_name();
                        $this->response['csrfHash'] = $this->security->get_csrf_hash();
                        $this->response['message'] = 'The import failed and no brands were updated. Please check the file and try again.';
                        echo json_encode($this->response);
                        return false;
                    }

                    $this->response['error'] = false;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = max(0, $temp1 - 1) . ' brand(s) updated successfully!';
                    print_r(json_encode($this->response));
                    return false;
                }
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
