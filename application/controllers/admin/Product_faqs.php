<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Product_faqs extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['product_model', 'product_faqs_model']);

        // 'product_faqs' has never existed as a module in application/config/eshop.php's
        // system_modules list. has_permissions() denies access whenever the requested module
        // isn't registered there at all, so this check could never pass for a restricted admin
        // (system user) no matter what permissions they were granted - only the primary
        // administrator account bypasses permission checks entirely and could ever reach this
        // page. Delete_product_faq() below already (correctly) checks the 'product' module;
        // this now matches it.
        if (!has_permissions('read', 'product')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-product-faqs';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Product FAQS Management | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Product FAQs Management |' . $settings['app_name'];
            if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('product_faqs', ['id' => $_GET['edit_id']]);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_faqs_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            return $this->product_faqs_model->get_faqs();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /**
     * Reachable directly at admin/product_faqs/edit_product_faqs, but not used by any current
     * admin view - the edit modals on the FAQ list and product list both post to
     * admin/product/edit_product_faqs instead, which already updates correctly.
     *
     * This action was still broken in its own right: it always called
     * Product_faqs_model::add_product_faqs(), which unconditionally INSERTs, regardless of
     * whether an existing FAQ id was supplied. Reproduced live: submitting an "edit" of FAQ #3
     * through this exact endpoint returned {"error":false,"message":"FAQ Updated Successfully"}
     * while leaving FAQ #3 completely untouched and inserting a brand new duplicate row instead.
     * Now branches to the model's real update method (already used correctly by the mobile API),
     * built from an explicit field list rather than the raw POST body.
     */
    public function edit_product_faqs()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->form_validation->set_rules('answer', 'Answer', 'trim|required|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                if (isset($_POST['edit_product_faq']) && is_numeric($_POST['edit_product_faq'])) {
                    $edit_data = [
                        'answer' => $this->input->post('answer', true),
                        'answered_by' => $this->ion_auth->get_user_id(),
                    ];
                    $this->product_faqs_model->edit_product_faqs($edit_data, (int) $_POST['edit_product_faq']);
                    $message = 'FAQ Updated Successfully';
                } else {
                    $this->product_faqs_model->add_product_faqs($_POST);
                    $message = 'FAQ Added Successfully';
                }
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $message;
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function create_product_faqs()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'add-product-faqs';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Add Product FAQS Management | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Add Product FAQs Management |' . $settings['app_name'];
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_faqs()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Previously had no validation at all, despite the form displaying required-field
            // asterisks for all three inputs. Reproduced live: an empty POST to this endpoint
            // reached the database and crashed with a raw, unhandled SQL error page -
            // "Column 'product_id' cannot be null" - which exposed the literal INSERT statement
            // and an internal file path in the response body.
            $this->form_validation->set_rules('product_id', 'Product', 'trim|required|xss_clean');
            $this->form_validation->set_rules('question', 'Question', 'trim|required|xss_clean');
            $this->form_validation->set_rules('answer', 'Answer', 'trim|required|xss_clean');

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return false;
            }

            $this->product_faqs_model->add_product_faqs($_POST);
            $this->response['error'] = false;
            // csrfName/csrfHash were missing here. The shared submit handler
            // (assets/admin/custom/custom.js) unconditionally overwrites the page's csrfName/
            // csrfHash globals with whatever the response provides on every successful submit -
            // without these two keys it set both to "undefined", silently breaking every
            // subsequent form submission on the page. Latent in this install only because CSRF
            // protection happens to be switched off site-wide.
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'FAQ added successfully';
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function delete_product_faq()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'product'), PERMISSION_ERROR_MSG, 'product', false)) {
                return false;
            }

            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $this->response['error'] = true;
                $this->response['message'] = 'Invalid FAQ id';
                echo json_encode($this->response);
                return false;
            }

            $this->product_faqs_model->delete_faq((int) $_GET['id']);

            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'Deleted successfully';

            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
