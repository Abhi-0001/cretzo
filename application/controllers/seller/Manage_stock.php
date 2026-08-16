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
        $this->load->model(['product_model', 'product_faqs_model']);
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {
            $this->data['main_page'] = TABLES . 'manage_stock';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Stock Management| ' . $settings['app_name'];
            $this->data['meta_description'] = 'Stock Management |' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {

                $stock = fetch_details("product_variants", ['id' => $_GET['edit_id']], ['stock', 'product_id', 'attribute_value_ids']);
                // Only prefill this variant's details if its product actually belongs to
                // the current seller — otherwise opening this page with another seller's
                // variant id would leak that seller's product name and stock count.
                $owned_product = !empty($stock)
                    ? fetch_details('products', ['id' => $stock[0]['product_id'], 'seller_id' => $_SESSION['user_id']], 'id')
                    : [];
                if (!empty($owned_product)) {
                    $attribute_value = fetch_details("attribute_values", ['id' => $stock[0]['attribute_value_ids']], ['value']);

                    $id = $stock[0]['product_id'];
                    $this->data['fetched_data'] = fetch_product("", "", $id);
                    $this->data['fetched'] = $stock[0]['stock'];
                    $this->data['attribute'] = $attribute_value;
                }
            }
            $seller_id = $_SESSION['user_id'];
            $this->data['categories'] = $this->category_model->get_categories('', '', '', '', '', '', '', '', $seller_id);
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function get_stock_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel()) {

            return $this->product_model->get_seller_stock_details();
        } else {
            redirect('seller/login', 'refresh');
        }
    }


    public function update_stock()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && $this->ion_auth->can_access_seller_panel())) {
            redirect('seller/login', 'refresh');
            return;
        }

        // These were all bare 'required'. quantity accepted anything, so a NEGATIVE quantity
        // with type=add ran the add branch and SUBTRACTED stock, and a non-numeric value fell
        // through to intval(). type accepted any string. Matches the admin controller's rules.
        $this->form_validation->set_rules('variant_id', 'Variant', 'trim|required|numeric|xss_clean');
        $this->form_validation->set_rules('product_name', 'Product Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('quantity', 'Quantity', 'trim|required|numeric|greater_than[0]|xss_clean');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|in_list[add,subtract]|xss_clean');
        if (!$this->form_validation->run()) {

            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = validation_errors();
            print_r(json_encode($this->response));
        } else {
            // The variant must belong to a product owned by the current seller —
            // otherwise any logged-in seller could edit any other seller's stock levels
            // just by knowing (or guessing) a variant id.
            $owned_variant = $this->db
                ->select('pv.id')
                ->join('products p', 'p.id = pv.product_id')
                ->where(['pv.id' => $_POST['variant_id'], 'p.seller_id' => $this->ion_auth->get_user_id()])
                ->get('product_variants pv')
                ->result_array();
            if (empty($owned_variant)) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Product variant not found';
                print_r(json_encode($this->response));
                return;
            }
            if ($this->input->post('type', true) == 'add') {
                set_stock_movement_context('manual_add', null, $this->ion_auth->get_user_id());
                update_stock([$this->input->post('variant_id', true)], [$this->input->post('quantity', true)], 'plus');
            } else {
                // The ceiling was read from a POSTED current_stock field - a number supplied by
                // the same client making the request, so it proved nothing and could simply be
                // inflated to subtract past the real stock. Read the actual value instead.
                $actual = get_variant_current_stock($this->input->post('variant_id', true));
                if ($actual !== null && $this->input->post('quantity', true) > $actual) {
                    $this->response['error'] = true;
                    $this->response['csrfName'] = $this->security->get_csrf_token_name();
                    $this->response['csrfHash'] = $this->security->get_csrf_hash();
                    $this->response['message'] = "Subtracted stock cannot be greater than current stock";
                    print_r(json_encode($this->response));
                    return;
                }
                set_stock_movement_context('manual_subtract', null, $this->ion_auth->get_user_id());
                update_stock([$this->input->post('variant_id', true)], [$this->input->post('quantity', true)]);
            }

            $this->response['error'] = false;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'Stock Updated Successfully';
            print_r(json_encode($this->response));
        }
    }

}
