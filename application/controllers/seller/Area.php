<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Area extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model('Area_model');
    }

    public function manage_areas()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = TABLES . 'manage-area';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Area Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Area Management  | ' . $settings['app_name'];
            $this->data['city'] = fetch_details('cities', '');
            $this->data['zipcodes'] = fetch_details('zipcodes', '');
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function manage_deliverable_locations()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = TABLES . 'manage-deliverable-locations';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Deliverable Locations | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Deliverable Locations  | ' . $settings['app_name'];
            $this->data['gst_restriction'] = $this->get_seller_gst_restriction();
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function view_deliverable_products()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $seller_id = $this->session->userdata('user_id');
            return $this->Area_model->get_seller_deliverable_products($seller_id);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    /* Zipcode search for the deliverable-locations picker. Sellers who registered with a
       GST Enrollment Number (state-restricted) only get zipcodes within their own state. */
    public function get_deliverable_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $limit = 25;
            $offset = (isset($_GET['page']) ? ((int) $_GET['page'] - 1) : 0) * $limit;
            $search = isset($_GET['search']) ? $_GET['search'] : null;

            $restriction = $this->get_seller_gst_restriction();
            $state_id = $restriction['restricted'] ? $restriction['state_id'] : null;

            $zipcodes = $this->Area_model->get_zipcodes($search, $limit, $offset, $state_id);
            $this->response['data'] = $zipcodes['data'];
            $this->response['total'] = $zipcodes['total'];
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    /* Bulk-select helper for the deliverable-locations picker: returns every zipcode in
       the seller's own profile state, so a state-restricted (GST Enrollment Number) seller
       doesn't have to search and add zipcodes one at a time. Capped at 5000 rows — no
       Indian state has anywhere near that many zipcodes, but the cap keeps the query bounded. */
    public function get_state_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $seller_id = $this->session->userdata('user_id');
            $seller_data = fetch_details('seller_data', ['user_id' => $seller_id], 'state');
            $state = isset($seller_data[0]['state']) ? trim((string) $seller_data[0]['state']) : '';
            $state_id = $state !== '' ? $this->Area_model->get_state_id_by_name($state) : null;

            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();

            if (empty($state_id)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Please set a valid state in your seller profile before using this option.';
                print_r(json_encode($this->response));
                return;
            }

            $zipcodes = $this->Area_model->get_zipcodes(null, 5000, 0, $state_id);
            $this->response['error'] = false;
            $this->response['state'] = $state;
            $this->response['data'] = $zipcodes['data'];
            $this->response['total'] = $zipcodes['total'];
            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function update_deliverable_location()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $product_ids = isset($_POST['product_id']) ? (array) $_POST['product_id'] : [];
            $deliverable_type = isset($_POST['deliverable_type']) ? $_POST['deliverable_type'] : NONE;
            $zipcode_ids = isset($_POST['deliverable_zipcodes']) ? $_POST['deliverable_zipcodes'] : [];

            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();

            if (empty($product_ids) || !in_array($deliverable_type, [NONE, ALL, INCLUDED, EXCLUDED])) {
                $this->response['error'] = true;
                $this->response['message'] = 'Please select a valid deliverable type.';
                print_r(json_encode($this->response));
                return;
            }

            $restriction = $this->get_seller_gst_restriction();
            if ($restriction['restricted']) {
                if (in_array($deliverable_type, [ALL, EXCLUDED])) {
                    $this->response['error'] = true;
                    $this->response['message'] = 'Your account is registered with a GST Enrollment Number, so you can only deliver within your own state (' . $restriction['state'] . '). Please choose "Only selected zipcodes".';
                    print_r(json_encode($this->response));
                    return;
                }
                if ($deliverable_type == INCLUDED) {
                    if (empty($restriction['state_id'])) {
                        $this->response['error'] = true;
                        $this->response['message'] = 'Please set a valid state in your seller profile before choosing deliverable zipcodes.';
                        print_r(json_encode($this->response));
                        return;
                    }
                    if (!$this->Area_model->zipcodes_belong_to_state($zipcode_ids, $restriction['state_id'])) {
                        $this->response['error'] = true;
                        $this->response['message'] = 'You can only select zipcodes within your own state (' . $restriction['state'] . ').';
                        print_r(json_encode($this->response));
                        return;
                    }
                }
            }

            if (($deliverable_type == INCLUDED || $deliverable_type == EXCLUDED) && empty($zipcode_ids)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Please select at least one zipcode.';
                print_r(json_encode($this->response));
                return;
            }

            $seller_id = $this->session->userdata('user_id');
            $this->Area_model->update_seller_deliverable_products($seller_id, $product_ids, $deliverable_type, $zipcode_ids);

            $this->response['error'] = false;
            $this->response['message'] = 'Deliverable location updated successfully.';
            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    /* Returns the current seller's GST-enrollment delivery restriction: sellers registered via a
       GST Enrollment Number (as opposed to a full GST number) may only deliver within their own state. */
    private function get_seller_gst_restriction()
    {
        $seller_id = $this->session->userdata('user_id');
        $seller_data = fetch_details('seller_data', ['user_id' => $seller_id], 'is_gst_registered,state');
        $is_gst_registered = isset($seller_data[0]['is_gst_registered']) ? (int) $seller_data[0]['is_gst_registered'] : 1;
        $state = isset($seller_data[0]['state']) ? trim((string) $seller_data[0]['state']) : '';

        return [
            'restricted' => ($is_gst_registered == 0),
            'state' => $state,
            'state_id' => $state !== '' ? $this->Area_model->get_state_id_by_name($state) : null,
        ];
    }

    public function manage_countries()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller()) {
            $this->data['main_page'] = TABLES . 'manage-countries';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Countries Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Countries Management  | ' . $settings['app_name'];
            $this->data['countries'] = fetch_details('countries', '');
       
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }
    public function country_list()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller()) {
            return $this->Area_model->get_countries_list();
        } else {
            redirect('seller/login', 'refresh');
        }
    }
    public function view_area()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            return $this->Area_model->get_list($table = 'areas');
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function manage_cities()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            $this->data['main_page'] = TABLES . 'manage-city';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'City Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' City Management  | ' . $settings['app_name'];

            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function view_city()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            return $this->Area_model->get_list($table = 'cities');
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function manage_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            $this->data['main_page'] = TABLES . 'manage-zipcodes';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Zipcodes Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Zipcode Management  | ' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('zipcodes', ['id' => $_GET['edit_id']]);
            }
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function view_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $seller_id = $this->session->userdata('user_id');

            return $this->Area_model->get_zipcode_list($seller_id);
        } else {
            redirect('seller/login', 'refresh');
        }
    }
    public function get_zipcodes()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            $limit = (isset($_GET['limit'])) ? $this->input->post('limit', true) : 25;
            $offset = (isset($_GET['offset'])) ? $this->input->post('offset', true) : 0;
            $search =  (isset($_GET['search'])) ? $_GET['search'] : null;
            $zipcodes = $this->Area_model->get_zipcodes($search, $limit, $offset);
            $this->response['data'] = $zipcodes['data'];
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }
}
