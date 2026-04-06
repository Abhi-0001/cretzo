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
    
    
    public function manage_deliverable_locations()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $this->data['main_page'] = TABLES . 'manage-deliverable-locations';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Deliverable Locations | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Deliverable Locations | ' . $settings['app_name'];
            $this->data['has_states_table'] = $this->db->table_exists('states');
            $this->data['has_districts_table'] = $this->db->table_exists('districts');
            $seller_id = (int)$this->session->userdata('user_id');
            // NOTE: Guard against missing pickup_locations table to avoid SQL error 1054/1146 on fresh or partial schemas.
            $this->data['has_pickup_locations_table'] = $this->db->table_exists('pickup_locations');
            // Only query seller pickup/source locations when the table exists; otherwise return an empty list safely.
            $this->data['deliverable_locations'] = !empty($this->data['has_pickup_locations_table'])
                ? fetch_details('pickup_locations', ['seller_id' => $seller_id])
                : [];
            $this->data['zipcode_count'] = $this->db->table_exists('zipcodes') ? (int)$this->db->count_all('zipcodes') : 0;
            $this->data['city_count'] = $this->db->table_exists('cities') ? (int)$this->db->count_all('cities') : 0;
            $this->data['state_count'] = !empty($this->data['has_states_table']) ? (int)$this->db->count_all('states') : 0;
            $this->data['district_count'] = !empty($this->data['has_districts_table']) ? (int)$this->db->count_all('districts') : 0;
            $this->ensure_seller_deliverable_locations_table();
            $this->data['zipcodes_list'] = $this->db->table_exists('zipcodes') ? $this->db->select('id,zipcode')->order_by('id', 'DESC')->limit(200)->get('zipcodes')->result_array() : [];
            $this->data['cities_list'] = $this->db->table_exists('cities') ? $this->db->select('id,name')->order_by('id', 'DESC')->limit(200)->get('cities')->result_array() : [];
            $this->data['states_list'] = !empty($this->data['has_states_table']) ? $this->db->select('id,name')->order_by('id', 'DESC')->limit(200)->get('states')->result_array() : [];
            $this->data['districts_list'] = !empty($this->data['has_districts_table']) ? $this->db->select('id,name')->order_by('id', 'DESC')->limit(200)->get('districts')->result_array() : [];
            $this->data['selected_zipcodes'] = $this->get_selected_deliverable_ids($seller_id, 'zipcode');
            $this->data['selected_cities'] = $this->get_selected_deliverable_ids($seller_id, 'city');
            $this->data['selected_states'] = $this->get_selected_deliverable_ids($seller_id, 'state');
            $this->data['selected_districts'] = $this->get_selected_deliverable_ids($seller_id, 'district');
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function save_deliverable_scope()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {
            redirect('seller/login', 'refresh');
        }

        $type = $this->input->post('location_type', true);
        $allowed_types = ['zipcode', 'city', 'state', 'district'];
        if (!in_array($type, $allowed_types, true)) {
            $this->session->set_flashdata('error', 'Invalid location type selected.');
            redirect('seller/area/manage/deliverable_locations', 'refresh');
        }

        $selected_ids = $this->input->post('selected_ids');
        $selected_ids = is_array($selected_ids) ? array_values(array_filter(array_map('intval', $selected_ids))) : [];
        $seller_id = (int)$this->session->userdata('user_id');

        $this->ensure_seller_deliverable_locations_table();
        $this->db->where(['seller_id' => $seller_id, 'location_type' => $type])->delete('seller_deliverable_locations');

        if (!empty($selected_ids)) {
            $rows = [];
            foreach ($selected_ids as $selected_id) {
                $rows[] = [
                    'seller_id' => $seller_id,
                    'location_type' => $type,
                    'location_id' => $selected_id,
                ];
            }
            $this->db->insert_batch('seller_deliverable_locations', $rows);
        }

        $this->session->set_flashdata('message', ucfirst($type) . ' deliverable locations updated successfully.');
        redirect('seller/area/manage/deliverable_locations', 'refresh');
    }


    public function manage($section = null)
    {
        if ($section === 'deliverable_locations') {
            return $this->manage_deliverable_locations();
        }
        show_404();
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
    private function ensure_seller_deliverable_locations_table()
    {
        if ($this->db->table_exists('seller_deliverable_locations')) {
            return;
        }
        $this->load->dbforge();
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'seller_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'location_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'location_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'date_added TIMESTAMP default CURRENT_TIMESTAMP',
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('seller_deliverable_locations', true);
    }

    private function get_selected_deliverable_ids($seller_id, $type)
    {
        if (!$this->db->table_exists('seller_deliverable_locations')) {
            return [];
        }
        $rows = $this->db->select('location_id')
            ->where(['seller_id' => $seller_id, 'location_type' => $type])
            ->get('seller_deliverable_locations')
            ->result_array();
        return array_map(static function ($row) {
            return (int)$row['location_id'];
        }, $rows);
    }
}
