<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Category extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['category_model']);

        if (!has_permissions('read', 'categories')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    /**
     * True if setting $category_id's parent to $proposed_parent_id would create a cycle -
     * i.e. $proposed_parent_id is $category_id itself, or is reached by walking up $category_id's
     * own descendants. Bounded to 50 hops so a pre-existing cycle elsewhere in the table (from
     * before this check existed) can't hang the request in an infinite loop.
     */
    private function creates_category_cycle($category_id, $proposed_parent_id)
    {
        $current = $proposed_parent_id;
        $hops = 0;
        while ($current != 0 && $hops < 50) {
            if ($current == $category_id) {
                return true;
            }
            $row = $this->db->select('parent_id')->where('id', $current)->get('categories')->row_array();
            if (empty($row)) {
                return false;
            }
            $current = (int) $row['parent_id'];
            $hops++;
        }
        return false;
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-category';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Category Management | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Category Management | ' . $settings['app_name'];
            $id = $this->input->get('id', true);
            if (isset($id) && !empty($id)) {
                $this->data['base_category_url'] = base_url() . 'admin/category/category_list?id=' . $id;
            } else {
                $this->data['base_category_url']  = base_url() . 'admin/category/category_list';
            }
            $this->data['category_result'] = $this->category_model->get_categories();
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_categories()
    {
        $ignore_status = isset($_GET['ignore_status']) && $_GET['ignore_status'] == 1 ? 1 : '';
        $seller_id = (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) ? $_GET['seller_id'] : 0;
        $response['data'] = $this->data['category_result'] = $this->category_model->get_categories(NULL, '', '', 'row_order', 'ASC', 'true', '', $ignore_status, $seller_id);
        echo json_encode($response);
        return;
    }

    public function get_seller_categories()
    {
        $this->form_validation->set_data($this->input->get());
        $this->form_validation->set_rules('seller_id', 'Seller ID', 'trim|numeric|required|xss_clean');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {
            $seller_id = $this->input->get('seller_id', true);
            $ignore_status = isset($_GET['ignore_status']) && $_GET['ignore_status'] == 1 ? 1 : '';
            $response['data'] = $this->category_model->get_seller_categories($seller_id);
            echo json_encode($response);
            return;
        }
    }


    public function create_category()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'category';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) ? 'Edit Category | ' . $settings['app_name'] : 'Add Category | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Category , Create Category | ' . $settings['app_name'];
            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('categories', ['id' => $_GET['edit_id']]);
            }

            $this->data['categories'] = $this->category_model->get_categories();

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function category_order()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (!has_permissions('read', 'category_order')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }

            $this->data['main_page'] = TABLES . 'category-order';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Category Order | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Category Order | ' . $settings['app_name'];
            $this->data['categories'] = $this->category_model->get_categories();
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    function delete_category()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'categories'), PERMISSION_ERROR_MSG, 'categories')) {
                return false;
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }

            // id was never validated as numeric before reaching the model's where('id', ...).
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Invalid category id';
                print_r(json_encode($this->response));
                return false;
            }

            // 'error' was hardcoded to true on the SUCCESS branch, and there was no response
            // at all on failure - so a successful delete showed the JS's "Oops..." warning
            // dialog with the text "Deleted Succesfully", and a failed delete showed nothing.
            //
            // The model now returns why it refused rather than a bare boolean, so the admin
            // sees the actual reason ("still has 3 subcategories", "does not exist") instead of
            // the old blanket "Deleted Successfully" - which it reported even for an id that had
            // never existed, because the UPDATE it ran matched zero rows without failing.
            $result = $this->category_model->delete_category($_GET['id']);

            $this->response['error'] = empty($result['success']);
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = !empty($result['message']) ? $result['message'] : 'Failed to delete category';
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function category_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->category_model->get_category_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }



    public function add_category()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_category'])) {
                if (print_msg(!has_permissions('update', 'categories'), PERMISSION_ERROR_MSG, 'categories')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'categories'), PERMISSION_ERROR_MSG, 'categories')) {
                    return false;
                }
            }

            $this->form_validation->set_rules('category_input_name', 'Category Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('banner', 'Banner', 'trim|xss_clean');

            if (isset($_POST['edit_category'])) {

                $this->form_validation->set_rules('category_input_image', 'Image', 'trim|xss_clean');
            } else {
                $this->form_validation->set_rules('category_input_image', 'Image', 'trim|required|xss_clean', array('required' => 'Category image is required'));
            }


            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else if (isset($_POST['edit_category']) && !empty($_POST['category_parent']) && $this->creates_category_cycle((int) $_POST['edit_category'], (int) $_POST['category_parent'])) {
                // The "Select Parent" dropdown never excluded the category being edited or any
                // of its own descendants, and nothing here checked for one either - reproduced
                // live: setting category #2's own parent to #14 (a direct child of #2) was
                // accepted outright ("Category Updated Successfully") and left the database
                // with #2 -> parent #14 -> parent #2, a two-node cycle. Anything that walks a
                // category's parent chain (breadcrumbs, the category tree view) could loop
                // forever on data like that. Blocked by walking up from the proposed parent
                // until either reaching a root (parent_id 0) or the category being edited itself.
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'A category cannot be set as its own parent, directly or through one of its subcategories.';
                print_r(json_encode($this->response));
            } else {

                $this->category_model->add_category($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_category'])) ? 'Category Updated Successfully' : 'Category Added Successfully';
                $this->response['message'] = $message;
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function update_category_order()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('update', 'category_order'), PERMISSION_ERROR_MSG, 'category_order', false)) {
                return false;
            }

            // Had no validation at all: a request with category_id missing, or not an array,
            // raised a PHP warning (foreach on null) but still reported "Category Order Saved !",
            // and every id inside it was written straight into a WHERE clause with no type check.
            if (!isset($_GET['category_id']) || !is_array($_GET['category_id'])) {
                $response['error'] = true;
                $response['message'] = 'No categories to reorder';
                print_r(json_encode($response));
                return false;
            }

            $this->db->trans_start();
            $i = 0;
            foreach ($_GET['category_id'] as $row) {
                if (!is_numeric($row)) {
                    continue;
                }
                $this->db->where(['id' => (int) $row])->update('categories', ['row_order' => $i]);
                $i++;
            }
            $this->db->trans_complete();

            $response['error'] = ($this->db->trans_status() === false);
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = $response['error'] ? 'Something went wrong. Please try again.' : 'Category Order Saved !';

            print_r(json_encode($response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function top_category()
    {

        $this->category_model->top_category();
    }
}
