<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Customer extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['Customer_model', 'address_model']);

        if (!has_permissions('read', 'customers')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-customer';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'View Customer | ' . $settings['app_name'];
            $this->data['meta_description'] = ' View Customer  | ' . $settings['app_name'];
            $this->data['about_us'] = get_settings('about_us');
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_customer()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Customer_model->get_customer_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function update_customer_wallet()
    {

        if (print_msg(!has_permissions('update', 'customers'), PERMISSION_ERROR_MSG, 'customers', false)) {
            return false;
        }

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->form_validation->set_rules('user_id', 'User ID', 'trim|required|xss_clean');
            $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean');
            // 'numeric' alone allows negative amounts and zero - a credit/refund submitted with
            // a negative amount would run update_wallet_balance()'s credit branch but actually
            // DEBIT the balance (balance + a negative number), while the transaction log still
            // records it as type=credit - a direction mismatch between what's logged and what
            // actually happened to the balance.
            $this->form_validation->set_rules('amount', 'Amount', 'trim|required|xss_clean|numeric|greater_than[0]');
            $this->form_validation->set_rules('message', 'Message', 'trim|required|xss_clean');

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                if ($_POST['type'] == 'debit' || $_POST['type'] == 'credit') {
                    $message = (isset($_POST['message']) && !empty($_POST['message'])) ? $this->input->post('message', true) : "Balance " . $_POST['type'] . "ed.";
                    $response = update_wallet_balance($_POST['type'], $_POST['user_id'], $_POST['amount'], $message);
                    $response['csrfName'] = $this->security->get_csrf_token_name();
                    $response['csrfHash'] = $this->security->get_csrf_hash();
                    print_r(json_encode($response));
                }
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function delete_customer()
    {
        if (print_msg(!has_permissions('delete', 'customers'), PERMISSION_ERROR_MSG, 'customers', false)) {
            return false;
        }

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->form_validation->set_rules('user_id', 'User ID', 'trim|required|xss_clean|numeric');

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
            } else {
                $user_id = (int) $this->input->post('user_id');
                // Only allow removing accounts that are actually customers (group_id 2) -
                // repurposing this endpoint against a seller/admin/delivery-boy id would
                // silently wipe an unrelated account.
                $is_customer = $this->db->where(['user_id' => $user_id, 'group_id' => 2])->get('users_groups')->row_array();
                if (empty($is_customer)) {
                    $this->response['error'] = true;
                    $this->response['message'] = 'Customer not found.';
                } elseif (user_has_role($user_id, 'seller') || user_has_role($user_id, 'admin')) {
                    // Sellers and admins now also hold the buyer role on the same account
                    // (one mobile = one account, several roles), so "is in group 2" alone no
                    // longer proves this is *only* a customer - without this check, deleting
                    // a "customer" here would destroy a seller's or admin's whole account.
                    $this->response['error'] = true;
                    $this->response['message'] = 'This account is also a seller or admin account. Delete it from the Sellers / System Users screen instead.';
                } else {
                    $this->Customer_model->delete_customer($user_id);
                    $this->response['error'] = false;
                    $this->response['message'] = 'Customer deleted successfully.';
                }
            }
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function search_user()
    {
        // The search term was pasted directly into a raw WHERE string - a real, live SQL
        // injection reachable by any logged-in admin/sub-admin. Uses the query builder's
        // own escaping instead.
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        // Fetch users
        $this->db->select('*');
        $this->db->like('username', $search);
        $fetched_records = $this->db->get('users');
        $users = $fetched_records->result_array();
        // Initialize Array with fetched data
        $data = array();
        foreach ($users as $user) {
            $data[] = array("id" => $user['id'], "text" => $user['username']);
        }
        echo json_encode($data);
    }
    public function addresses()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage-address';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'View Address | ' . $settings['app_name'];
            if (isset($_GET['view_id'])) {
                $this->data['view_id'] = (isset($_GET['view_id'])) ? $_GET['view_id'] : null;
            }
            $this->data['meta_description'] = ' View Address  | ' . $settings['app_name'];
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_address()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // This admin view is read-only (no edit/delete/set-default controls of its own) -
            // the action buttons this model can build post straight to the customer-facing
            // My_account endpoints and have no place being rendered here.
            $this->address_model->get_address_list('', true, false);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
