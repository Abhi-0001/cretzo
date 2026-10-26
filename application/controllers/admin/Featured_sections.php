<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Featured_sections extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['Featured_section_model', 'category_model']);
        if (!has_permissions('read', 'featured_section')) {
            deny_panel_access();
        }
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'featured_section';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Featured Sections Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Featured Sections Management  | ' . $settings['app_name'];
            $this->data['categories'] = $this->category_model->get_categories();
            // Approved sellers, for the product picker's "filter by seller" dropdown.
            $this->data['sellers'] = $this->db
                ->select('sd.user_id, COALESCE(NULLIF(sd.shop_name, ""), NULLIF(sd.store_name, ""), u.username) AS name', false)
                ->join('users u', 'u.id = sd.user_id')
                ->where('sd.status', 1)
                ->order_by('name')
                ->get('seller_data sd')->result_array();
            // Was a bare isset() followed by an unconditional $featured_data[0] - an ?edit_id=
                // that is empty, non-numeric, or names a deleted section blew up on "Undefined
                // array key 0". Also guard the product_ids lookup: a section with no products
                // (product_ids NULL) explode()d to [''] and asked for WHERE id IN ('').
            $edit_id = $this->input->get('edit_id', true);
            if (!empty($edit_id) && is_numeric($edit_id)) {
                $featured_data = fetch_details('sections', ['id' => (int) $edit_id]);
                if (!empty($featured_data)) {
                    $product_ids = array_filter(explode(',', (string) $featured_data[0]['product_ids']), 'strlen');
                    $this->data['product_details'] = (!empty($product_ids))
                        ? $this->db
                            ->select('p.id, p.name, p.image, p.status, p.listing_visibility, COALESCE(NULLIF(sd.shop_name, ""), NULLIF(sd.store_name, ""), su.username, "") AS seller_name, (SELECT MIN(IF(pv.special_price > 0, pv.special_price, pv.price)) FROM product_variants pv WHERE pv.product_id = p.id) AS price', false)
                            ->join('seller_data sd', 'sd.user_id = p.seller_id', 'left')
                            ->join('users su', 'su.id = p.seller_id', 'left')
                            ->where_in('p.id', $product_ids)->get('products p')->result_array()
                        : [];
                    // Show the picks in the order they were saved in, not table order.
                    $pick_position = array_flip(array_values($product_ids));
                    usort($this->data['product_details'], function ($a, $b) use ($pick_position) {
                        return ($pick_position[$a['id']] ?? PHP_INT_MAX) <=> ($pick_position[$b['id']] ?? PHP_INT_MAX);
                    });
                    $this->data['fetched_data'] = $featured_data;
                }
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function add_featured_section()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_featured_section'])) {
                if (print_msg(!has_permissions('update', 'featured_section'), PERMISSION_ERROR_MSG, 'featured_section')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'featured_section'), PERMISSION_ERROR_MSG, 'featured_section')) {
                    return false;
                }
            }

            $this->form_validation->set_rules('title', ' Title ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('short_description', ' Short Description ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('style', ' Style ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('product_type', ' Product Type ', 'trim|required|xss_clean', array('required' => 'Select Product Type'));
            $this->form_validation->set_rules('product_ids[]', ' Product ', 'trim|xss_clean');

            $picked_products = array_filter((array) $this->input->post('product_ids'), 'strlen');
            $is_digital = (trim((string) $this->input->post('product_type')) == 'digital_product');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
            } elseif (!$is_digital && empty($picked_products)) {
                // A section with no picked products renders nothing on the home page, so saving one
                // would look like it worked while the section silently never appeared.
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = 'Pick at least one product for this section - it only shows the products you choose.';
            } else {

                if (isset($_POST['edit_featured_section'])) {

                    if (is_exist(['title' => $_POST['title']], 'sections', $_POST['edit_featured_section'])) {
                        $response["error"]   = true;
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["message"] = "Title Already Exists !";
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                } else {
                    if (is_exist(['title' => $_POST['title']], 'sections')) {
                        $response["error"]   = true;
                        $response['csrfName'] = $this->security->get_csrf_token_name();
                        $response['csrfHash'] = $this->security->get_csrf_hash();
                        $response["message"] = "Title Already Exists !";
                        $response["data"] = array();
                        echo json_encode($response);
                        return false;
                    }
                }

                $this->Featured_section_model->add_featured_section($_POST);
                $this->response['error'] = false;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $message = (isset($_POST['edit_featured_section'])) ? 'Section Updated Successfully' : 'Section Added Successfully';
                $this->response['message'] = $message;
            }
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function section_order()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = TABLES . 'section-order';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Section Order | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Section Order | ' . $settings['app_name'];
            $sections = $this->db->select('*')->order_by('row_order')->get('sections')->result_array();
            $this->data['section_result'] = $sections;
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function update_section_order()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // Had no permission check at all - the constructor only requires 'read' access for
            // this whole controller, so a user granted read-only access to Featured Sections
            // could still reorder them, matching the exact permission check its own sibling
            // create/update/delete endpoints already have.
            if (print_msg(!has_permissions('update', 'featured_section'), PERMISSION_ERROR_MSG, 'featured_section', false)) {
                return false;
            }
            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $this->response['error'] = true;
                $this->response['message'] = DEMO_VERSION_MSG;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                echo json_encode($this->response);
                return false;
            }

            // Had no validation at all: a request with section_id missing, or not an array,
            // raised a PHP warning (foreach on null) and every id inside it was written straight
            // into a WHERE clause with no type check - the same bug already fixed on the
            // equivalent Category/Product order-save endpoints.
            if (!isset($_GET['section_id']) || !is_array($_GET['section_id'])) {
                $this->response['error'] = true;
                $this->response['message'] = 'No sections to reorder';
                echo json_encode($this->response);
                return false;
            }

            $this->db->trans_start();
            $i = 0;
            foreach ($_GET['section_id'] as $row) {
                if (!is_numeric($row)) {
                    continue;
                }
                $this->db->where(['id' => (int) $row])->update('sections', ['row_order' => $i]);
                $i++;
            }
            $this->db->trans_complete();

            app_cache_delete('home_sections.guest');
            $this->response['error'] = ($this->db->trans_status() === false);
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = $this->response['error'] ? 'Something went wrong. Please try again.' : 'Section order updated successfully';
            echo json_encode($this->response);
            return false;
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function get_section_list()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Featured_section_model->get_section_list();
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function delete_featured_section()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (print_msg(!has_permissions('delete', 'featured_section'), PERMISSION_ERROR_MSG, 'featured_section', false)) {
                return false;
            }
            if (defined('SEMI_DEMO_MODE') && SEMI_DEMO_MODE == 0) {
                $this->response['error'] = true;
                $this->response['message'] = SEMI_DEMO_MODE_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            if (delete_details(['id' => $_GET['id']], 'sections') == TRUE) {
                app_cache_delete('home_sections.guest');
                $this->response['error'] = false;
                $this->response['message'] = 'Deleted Succesfully';
                print_r(json_encode($this->response));
            } else {
                // Was hardcoded to error:false (success) even in this failure branch, so a
                // failed delete still reported success to the caller.
                $this->response['error'] = true;
                $this->response['message'] = 'Something Went Wrong';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }
}
