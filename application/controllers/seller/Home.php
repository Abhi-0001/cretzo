<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['Home_model', 'Order_model']);
    }

    public function index()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $user_id = $this->session->userdata('user_id');
            // Single query for user + seller data instead of two separate queries
            $user_res = $this->db->select('u.balance, u.username, sd.rating, sd.no_of_ratings')
            ->from('users u')
            ->join('seller_data sd', 'sd.user_id = u.id', 'left')
            ->where('u.id', $user_id)
            ->get()->row_array();

            $this->data['balance']  = $user_res['balance'] ?? 0;
            $this->data['username'] = $user_res['username'];
            $this->data['ratings']  = [['rating' => $user_res['rating'], 'no_of_ratings' => $user_res['no_of_ratings']]];
            $settings = get_settings('system_settings', true);
            $this->data['curreny'] = get_settings('currency');
            $this->data['profile_completion'] = $this->get_seller_profile_completion($user_id);
            $this->data['title'] = 'Seller Panel | ' . $settings['app_name'];
            $this->data['order_counter'] = orders_count("", $user_id);
            $this->data['user_counter'] = (get_seller_permission($this->ion_auth->get_user_id(), 'customer_privacy')) ? $this->Home_model->count_new_users() : 0;
            $this->data['balance'] = ($user_res[0]['balance'] == NULL) ? 0 : $user_res[0]['balance'];
            $this->data['products'] = $this->Home_model->count_products($user_id);
            $this->data['seller_earnings'] = $this->Home_model->total_earnings($type = 'seller');
            $this->data['username'] =  $user_res[0]['username'];
            $this->data['ratings'] =  fetch_details("seller_data", ['user_id' => $user_id], "rating,no_of_ratings");
            $this->data['profile_completion'] = $this->get_seller_profile_completion($user_id);
            $this->data['meta_description'] = 'Seller Panel | ' . $settings['app_name'];
            $this->data['count_products_low_status'] = $this->Home_model->count_products_stock_low_status($user_id);
            $this->data['count_products_availability_status'] = $this->Home_model->count_products_availability_status($user_id);
            // Single query to get all order status counts instead of 8 separate queries
            $status_rows = $this->db->select('active_status, COUNT(id) as total')
            ->where('seller_id', $user_id)
            ->group_by('active_status')
            ->get('order_items')
            ->result_array();
            
            $orders_count = [
            'awaiting'   => 0,
            'received'   => 0,
            'processed'  => 0,
            'shipped'    => 0,
            'delivered'  => 0,
            'cancelled'  => 0,
            'returned'   => 0,
            ];
            $total_orders = 0;
            foreach ($status_rows as $row) {
            $status = $row['active_status'];
            if (isset($orders_count[$status])) {
                $orders_count[$status] = $row['total'];
            }
            $total_orders += $row['total'];
            }
            $this->data['order_counter'] = $total_orders;
            $this->data['status_counts'] = $orders_count;

            $this->data['status_counts'] = $orders_count;
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    private function get_seller_profile_completion($user_id)
    {
        $profile_data =$this->db->select('sd.first_name, sd.last_name, sd.phone, sd.email, sd.district, sd.city, sd.state, sd.pin, sd.shop_name, sd.social, sd.shop_phone, sd.pickup_address1, sd.pickup_address2, sd.pickup_district, sd.pickup_state, sd.pickup_pin, sd.entity_type, sd.pan, sd.gst, sd.account_number, sd.account_holder_name, sd.ifsc, sd.branch, sd.bank_name')
            ->from('users u')
            ->join('seller_data sd', 'sd.user_id = u.id', 'left')
            ->where('u.id', $user_id)
            ->get()
            ->row_array();

            // TEMP DEBUG
            // file_put_contents('C:/xampp/htdocs/cretzo/debug_profile.txt', print_r($profile_data, true));      
            
            if (empty($profile_data)) {
            return [
                'percentage' => 0,
                'missing_sections' => [
                    ['label' => 'Complete Personal Details', 'link' => base_url('seller/home/profile?section=personal')],
                    ['label' => 'Complete Store Details', 'link' => base_url('seller/home/profile?section=store')],
                    ['label' => 'Add Bank Account Details', 'link' => base_url('seller/home/profile?section=account')],
                ],
            ];
        }

        $sections = [
            'personal' => [
                'weight' => 35,
                'label' => 'Complete Personal Details',
                'fields' => ['first_name', 'last_name', 'phone', 'email', 'district', 'city', 'state', 'pin'],
                'link' => base_url('seller/home/profile?section=personal'),
            ],
            'store' => [
                'weight' => 35,
                'label' => 'Complete Store Details',
                'fields' => ['shop_name', 'shop_phone', 'pickup_address1', 'entity_type', 'pan', 'gst'],
                'link' => base_url('seller/home/profile?section=store'),
            ],
            'account' => [
                'weight' => 30,
                'label' => 'Add Bank Account Details',
                'fields' => ['account_number', 'account_holder_name', 'ifsc', 'branch', 'bank_name'],
                'link' => base_url('seller/home/profile?section=account'),
            ],
        ];

        $completed_weight = 0;
        $missing_sections = [];

        foreach ($sections as $section) {
            $is_complete = true;

            foreach ($section['fields'] as $field) {
                if (!$this->is_profile_value_present($profile_data, $field)) {
                    $is_complete = false;
                    break;
                }
            }

            if ($is_complete) {
                $completed_weight += (int) $section['weight'];
                continue;
            }

            $missing_sections[] = [
                'label' => $section['label'],
                'link' => $section['link'],
            ];
        }

        return [
            'percentage' => $completed_weight,
            'missing_sections' => $missing_sections,
        ];
        file_put_contents('C:/xampp/htdocs/cretzo/debug_completion.txt', print_r([
            'profile_data' => $profile_data,
            'completed_weight' => $completed_weight,
            'missing_sections' => $missing_sections
        ], true));
    }

    private function is_profile_value_present($profile_data, $key)
    {
        if (!isset($profile_data[$key])) {
            return false;
        }

        return trim((string) $profile_data[$key]) !== '';
    }
   
    public function profile()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $identity_column = $this->config->item('identity', 'ion_auth');
            $settings = get_settings('system_settings', true);
            $user_id = $this->session->userdata('user_id');
            // print_r($user_id);
            $this->data['identity_column'] = $identity_column;
            $this->data['main_page'] = FORMS . 'profile';
            $this->data['title'] = 'Seller Profile | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Seller Profile | ' . $settings['app_name'];
            $this->data['current_profile_section'] = $this->input->get('section', true) ?? 'personal';
            // $this->data['fetched_data'] = $this->db->select(' u.*,sd.* ')
            //     ->join('users_groups ug', ' ug.user_id = u.id ')
            //     ->join('seller_data sd', ' sd.user_id = u.id ')
            //     ->where(['ug.group_id' => '4', 'ug.user_id' => $user_id])
            //     ->get('users u')
            //     ->result_array();

            // $this->data['fetched_data'] = $this->db->select(' u.*')
            //     ->join('users_groups ug', ' ug.user_id = u.id ')
            //     ->where(['ug.group_id' => '4', 'ug.user_id' => $user_id])
            //     ->get('users u')
            //     ->result_array();
            
            $this->data['fetched_data'] = $this->db
                ->select('u.*, sd.*')
                ->from('users u')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->join('seller_data sd', 'sd.user_id = u.id')
                ->where('ug.group_id', 4)
                ->where('ug.user_id', $user_id)
                ->get()
                ->result_array();
                
                // print_r($this->data['fetched_data']);
                // exit;

            $this->data['fetched_data'] = output_escaping_new($this->data['fetched_data']);
            // Added Bank Names Logic 
            $this->data['indian_banks'] = [];
            if ($this->db->table_exists('indian_banks')) {
                $this->data['indian_banks'] = $this->db
                    ->select('bank_name')
                    ->from('indian_banks')
                    ->order_by('bank_name', 'ASC')
                    ->get()
                    ->result_array();
            }
            // Adding the State District and City logic
            $this->data['states'] = [];
            $this->data['districts'] = [];
            $this->data['cities'] = [];

            $selected_state_name = $this->data['fetched_data'][0]['state'] ?? '';
            $selected_district_name = $this->data['fetched_data'][0]['district'] ?? '';

            $selected_state_id = null;
            $selected_district_id = null;

            if ($this->db->table_exists('states')) {
                $this->data['states'] = $this->db
                    ->select('id,name')
                    ->from('states')
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result_array();

                if (!empty($selected_state_name)) {
                    $state = $this->db
                        ->select('id')
                        ->from('states')
                        ->where('name', $selected_state_name)
                        ->get()
                        ->row_array();
                    $selected_state_id = $state['id'] ?? null;
                }
            }

            if ($this->db->table_exists('districts') && !empty($selected_state_id)) {
                $this->data['districts'] = $this->db
                    ->select('id,name')
                    ->from('districts')
                    ->where('state_id', $selected_state_id)
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result_array();

                if (!empty($selected_district_name)) {
                    $district = $this->db
                        ->select('id')
                        ->from('districts')
                        ->where('state_id', $selected_state_id)
                        ->where('name', $selected_district_name)
                        ->get()
                        ->row_array();
                    $selected_district_id = $district['id'] ?? null;
                }
            }

            if ($this->db->table_exists('cities') && !empty($selected_state_id) && !empty($selected_district_id)) {
                $this->data['cities'] = $this->db
                    ->select('id,name')
                    ->from('cities')
                    ->where('state_id', $selected_state_id)
                    ->where('district_id', $selected_district_id)
                    ->order_by('name', 'ASC')
                    ->get()
                    ->result_array();
            }

            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/home', 'refresh');
        }
    }
    public function get_districts_by_state()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller())) {
            echo json_encode([]);
            return;
        }

        $state_id = $this->input->get('state_id', true);
        if (empty($state_id) || !$this->db->table_exists('districts')) {
            echo json_encode([]);
            return;
        }

        $districts = $this->db
            ->select('id,name')
            ->from('districts')
            ->where('state_id', $state_id)
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();

        echo json_encode($districts);
    }

    public function get_cities_by_district()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_seller())) {
            echo json_encode([]);
            return;
        }

        $state_id = $this->input->get('state_id', true);
        $district_id = $this->input->get('district_id', true);

        if (empty($state_id) || empty($district_id) || !$this->db->table_exists('cities')) {
            echo json_encode([]);
            return;
        }

        $cities = $this->db
            ->select('id,name')
            ->from('cities')
            ->where('state_id', $state_id)
            ->where('district_id', $district_id)
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();

        echo json_encode($cities);
    }
    //  End of Function for the states, cities and districts 
    
    public function update_status()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $this->response['error'] = true;
                $this->response['message'] = DEMO_VERSION_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            if ($_GET['status'] == '1') {
                $_GET['status'] = 0;
            } else if ($_GET['status'] == '0') {
                $_GET['status'] = 1;
            }
            $this->db->trans_start();
            if ($_GET['table'] == 'users') {
                $this->db->set('active', $this->db->escape($_GET['status']));
            } else {
                $this->db->set('status', $this->db->escape($_GET['status']));
            }

            $this->db->where('id', $_GET['id'])->update($_GET['table']);
            $this->db->trans_complete();
            $error = false;
            $message = str_replace('_', ' ', $_GET['table']);
            if ($this->db->trans_status() === true) {
                $error = true;
            }
            $response['error'] = $error;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = $message;
            print_r(json_encode($response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function fetch_sales()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $sales[] = array();

            $month_res = $this->db->select('SUM(sub_total) AS total_sale,DATE_FORMAT(date_added,"%b") AS month_name ')
                ->where('seller_id', $_SESSION['user_id'])
                ->group_by('year(CURDATE()),MONTH(date_added)')
                ->order_by('year(CURDATE()),MONTH(date_added)')
                ->get('`order_items`')->result_array();

            $month_wise_sales['total_sale'] = array_map('intval', array_column($month_res, 'total_sale'));
            $month_wise_sales['month_name'] = array_column($month_res, 'month_name');

            $sales[0] = $month_wise_sales;
            $d = strtotime("today");
            $start_week = strtotime("last sunday midnight", $d);
            $end_week = strtotime("next saturday", $d);
            $start = date("Y-m-d", $start_week);
            $end = date("Y-m-d", $end_week);
            $week_res = $this->db->select("DATE_FORMAT(date_added, '%d-%b') as date, SUM(sub_total) as total_sale")
                ->where('seller_id', $_SESSION['user_id'])
                ->where("date(date_added) >='$start' and date(date_added) <= '$end' ")
                ->group_by('day(date_added)')->get('`order_items`')->result_array();


            $week_wise_sales['total_sale'] = array_map('intval', array_column($week_res, 'total_sale'));
            $week_wise_sales['week'] = array_column($week_res, 'date');

            $sales[1] = $week_wise_sales;

            $day_res = $this->db->select("DAY(date_added) as date, SUM(sub_total) as total_sale")
                ->where('seller_id', $_SESSION['user_id'])
                ->where('date_added >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)')
                ->group_by('day(date_added)')->get('`order_items`')->result_array();

            $day_wise_sales['total_sale'] = array_map('intval', array_column($day_res, 'total_sale'));
            $day_wise_sales['day'] = array_column($day_res, 'date');

            $sales[2] = $day_wise_sales;
            print_r(json_encode($sales));
        } else {
            redirect('seller/login', 'refresh');
        }
    }


    public function category_wise_product_count()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $user_id = $this->session->userdata('user_id');
            $this->db->select('category_ids');
            $where = 'user_id = ' . $user_id;
            $this->db->where($where);
            $result = $this->db->get('seller_data')->result_array();
            $result = explode(",", $result[0]['category_ids']);

            $res = $this->db->select('c.name as name,count(c.id) as counter')->group_Start()->where_in('c.id', $result)->group_End()->where(['p.status' => '1', 'c.status' => '1'])->join('products p', 'p.category_id=c.id')->group_by('c.id')->get('categories c')->result_array();
            $result = array();
            $result[0][] = 'Task';
            $result[0][] = 'Hours per Day';
            array_walk($res, function ($v, $k) use (&$result) {
                $result[$k + 1][] = $v['name'];
                $result[$k + 1][] = intval($v['counter']);
            });
            echo json_encode(array_values($result));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function logout()
    {
        $this->ion_auth->logout();
        redirect('seller/login', 'refresh');
    }
}
