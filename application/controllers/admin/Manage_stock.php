<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Manage_stock extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['product_model', 'product_faqs_model', 'category_model']);
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'manage_stock';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Stock Management| ' . $settings['app_name'];
            $this->data['meta_description'] = 'Stock Management |' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {

                // No guard against a stale/deleted/tampered edit_id - $stock[0] on an empty
                // result threw an undefined-index notice and left the modal populated with
                // nothing useful. The seller-side equivalent of this page already guards this.
                $stock = fetch_details("product_variants", ['id' => $_GET['edit_id']], ['stock', 'product_id', 'attribute_value_ids']);
                if (!empty($stock)) {
                    $attribute_value = fetch_details("attribute_values", ['id' => $stock[0]['attribute_value_ids']], ['value']);
                    $id = $stock[0]['product_id'];

                    $this->data['fetched_data'] = fetch_product("", "", $id);
                    $this->data['fetched'] = $stock[0]['stock'];
                    $this->data['attribute'] = $attribute_value;
                }
            }
            $this->data['categories'] = $this->category_model->get_categories();

            $this->data['sellers'] = $this->db->select(' u.username as seller_name,u.id as seller_id,sd.category_ids,sd.id as seller_data_id  ')
                ->join('users_groups ug', ' ug.user_id = u.id ')
                ->join('seller_data sd', ' sd.user_id = u.id ')
                ->where(['ug.group_id' => '4'])
                ->get('users u')->result_array();

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_stock_list()
    {
        $seller_id =  (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) ? $this->input->get('seller_id', true) : NULL;
        $status =  (isset($_GET['status']) && $_GET['status'] != "") ? $this->input->get('status', true) : NULL;
        if (isset($_GET['flag']) && !empty($_GET['flag'])) {
            return $this->product_model->get_product_details($_GET['flag'], $seller_id, $status);
        }
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            return $this->product_model->get_stock_details();
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function update_stock()
    {
        $this->form_validation->set_rules('product_name', 'Product Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('current_stock', 'Current Stock', 'trim|required|xss_clean|numeric');
        // Only checked 'required' before - a negative quantity on type=add subtracted from
        // stock instead of adding to it (the only floor check in this whole method lived
        // inside the type=='subtract' branch, so 'add' had no protection at all), and a
        // non-numeric value would have flowed straight into intval() math downstream.
        $this->form_validation->set_rules('quantity', 'Quantity', 'trim|required|xss_clean|numeric|greater_than[0]');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|xss_clean|in_list[add,subtract]');
        $this->form_validation->set_rules('variant_id', 'Variant', 'trim|required|xss_clean|numeric');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = validation_errors();
            print_r(json_encode($this->response));
        } else {

            // Never checked whether variant_id actually still exists - a stale/deleted variant
            // id silently updated nothing while the response still claimed success.
            $variant_exists = fetch_details('product_variants', ['id' => $_POST['variant_id']], 'id');
            if (empty($variant_exists)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'This product variant no longer exists. Please refresh and try again.';
                print_r(json_encode($this->response));
                return;
            }

            if ($_POST['type'] == 'add') {
                update_stock([$_POST['variant_id']], [$_POST['quantity']], 'plus');
            } else {
                if (
                    $_POST['quantity'] > $_POST['current_stock']
                ) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = "Subtracted stock cannot be greater than current stock";
                    print_r(
                        json_encode($this->response)
                    );
                    return;
                }
                update_stock([$_POST['variant_id']], [$_POST['quantity']]);
            }


            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'Stock Updated Successfully';
            print_r(json_encode($this->response));
        }
    }


    public function get_product_data()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $seller_id =  (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) ? $this->input->get('seller_id', true) : NULL;
            $status =  (isset($_GET['status']) && $_GET['status'] != "") ? $this->input->get('status', true) : NULL;
            if (isset($_GET['flag']) && !empty($_GET['flag'])) {
                return $this->product_model->get_product_details($_GET['flag'], $seller_id, $status);
            }
            return $this->product_model->get_product_details(null, $seller_id, $status);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
