<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Taxes extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model('Tax_model');

        if (!has_permissions('read', 'tax')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'tax';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Add Tax | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Tax | ' . $settings['app_name'];
            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('taxes', ['id' => $_GET['edit_id']]);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function manage_taxes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-taxes';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Manage Taxes | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Manage Taxes | ' . $settings['app_name'];
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_tax()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_tax_id'])) {
                if (print_msg(!has_permissions('update', 'tax'), PERMISSION_ERROR_MSG, 'tax')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'tax'), PERMISSION_ERROR_MSG, 'tax')) {
                    return false;
                }
            }

            $this->form_validation->set_rules('title', 'Title', 'trim|required|xss_clean|max_length[100]');
            // "numeric" alone accepted -50 and 5000 as valid tax rates, which then flow straight
            // into order totals. Bounded to a sane percentage range.
            $this->form_validation->set_rules('percentage', 'Percentage', 'trim|required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]|xss_clean');

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {

                if (isset($_POST['edit_tax_id'])) {
                    if (is_exist(['title' => $_POST['title']], 'taxes', $_POST['edit_tax_id'])) {
                        $response["error"]   = true;
                        $response["message"] = "Name Already Exist ! Provide a unique name";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                } else {
                    if (is_exist(['title' => $_POST['title']], 'taxes')) {
                        $response["error"]   = true;
                        $response["message"] = "Name Already Exist ! Provide a unique name";
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                }

                $this->Tax_model->add_tax($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                // Editing an existing tax also reported "Added Successfully".
                $this->response['message'] = (isset($_POST['edit_tax_id']) && !empty($_POST['edit_tax_id']))
                    ? 'Tax Details Updated Successfully'
                    : 'Tax Details Added Successfully';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_tax_list()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Tax_model->get_tax_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function delete_tax()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (print_msg(!has_permissions('delete', 'tax'), PERMISSION_ERROR_MSG, 'tax', false)) {
                return false;
            }

            // The record id came from the query string with no validation at all.
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!is_numeric($id)) {
                $response['error'] = true;
                $response['message'] = 'Invalid tax id';
                echo json_encode($response);
                return false;
            }

            if (delete_details(['id' => (int) $id], 'taxes') == TRUE) {
                $response['error'] = false;
                $response['message'] = 'Deleted Successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something Went Wrong';
            }
            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
