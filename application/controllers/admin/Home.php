<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'function_helper', 'bootstrap_table_helper', 'file']);
        $this->load->model(['Home_model', 'Order_model']);
    }

    public function index()
    {

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->data['main_page'] = FORMS . 'home';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Admin Panel | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Admin Panel | ' . $settings['app_name'];
            $this->data['curreny'] = get_settings('currency');
            $this->data['order_counter'] = $this->Home_model->count_new_orders();
            $this->data['user_counter'] = $this->Home_model->count_new_users();
            $this->data['delivery_boy_counter'] = $this->Home_model->count_delivery_boys();
            $this->data['product_counter'] = $this->Home_model->count_products();
            $this->data['count_products_low_status'] = $this->Home_model->count_products_stock_low_status();
            $this->data['count_products_availability_status'] = $this->Home_model->count_products_availability_status();
            $this->data['total_earnings'] = $this->Home_model->total_earnings('overall');
            $this->data['admin_earnings'] = $this->Home_model->total_earnings('admin');
            $this->data['seller_earnings'] = $this->Home_model->total_earnings('seller');
            $orders_count['awaiting'] = orders_count("awaiting");
            $orders_count['received'] = orders_count("received");
            $orders_count['processed'] = orders_count("processed");
            $orders_count['shipped'] = orders_count("shipped");
            $orders_count['delivered'] = orders_count("delivered");
            $orders_count['cancelled'] = orders_count("cancelled");
            $orders_count['returned'] = orders_count("returned");
            $this->data['status_counts'] = $orders_count;

            // Only the counts are rendered on the dashboard; the seller lists themselves are
            // loaded on demand by the modals from admin/sellers/*. Fetching every seller row
            // here as well meant three extra full-table SELECT * queries on every page load
            // whose results were then thrown away.
            $this->data['count_approved_sellers'] = $this->Home_model->count_approved_seller();
            $this->data['count_not_approved_sellers'] = $this->Home_model->count_not_approved_seller();
            $this->data['count_deactive_sellers'] = $this->Home_model->count_deactive_seller();

            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    public function reset_password()
    {
        /* Parameters to be passed
            mobile_no:7894561235
            new: pass@123
        */

        // This endpoint reset the password of ANY user account given only a mobile number,
        // with no authentication check whatsoever - an unauthenticated visitor could POST to
        // admin/home/reset_password and take over any account on the platform, including
        // other administrators. Restricted to a logged-in admin.
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        $this->form_validation->set_rules('mobile', 'Mobile No', 'trim|numeric|required|xss_clean|max_length[16]');
        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean');

        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            print_r(json_encode($this->response));
            return false;
        }

        $identity_column = $this->config->item('identity', 'ion_auth');
        $res = fetch_details('users', ['mobile' => $_POST['mobile']]);
        if (!empty($res)) {
            $identity = ($identity_column  == 'email') ? $res[0]['email'] : $res[0]['mobile'];
            if (!$this->ion_auth->reset_password($identity, $_POST['new_password'])) {
                $this->response['error'] = true;
                $this->response['message'] = $this->ion_auth->messages();
                $this->response['data'] = array();
                echo json_encode($this->response);
                return false;
            } else {
                $this->response['error'] = false;
                $this->response['message'] = 'Reset Password Successfully';
                $this->response['data'] = array();
                echo json_encode($this->response);
                return false;
            }
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'User does not exists !';
            $this->response['data'] = array();
            echo json_encode($this->response);
            return false;
        }
    }

    public function category_wise_product_sales()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $res = $this->db->select('c.name as category,count(oi.product_variant_id) as sales')
                ->join(' `product_variants` `pv` ', 'oi.`product_variant_id`=pv.`id`')
                ->join(' `products` p  ', ' pv.`product_id`=p.`id` ')
                ->join(' categories c ', ' p.category_id=c.id ')
                ->group_by('p.category_id')->get('`order_items` oi')->result_array();
            $response['category'] = array_column($res, 'category');
            $response['sales'] = array_column($res, 'sales');
            echo json_encode($response);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function fetch_sales()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $sales = array();

            // Month chart. The previous grouping was GROUP BY year(CURDATE()), MONTH(date_added):
            // year(CURDATE()) is a constant, so it contributed nothing and the rows collapsed on
            // month alone - April 2025 and April 2026 were summed into a single "Apr" bar, and the
            // ordering was equally meaningless. Grouped and ordered on the real year+month of the
            // order, and limited to the last 12 months so the axis stays readable.
            $month_res = $this->db->select('SUM(final_total) AS total_sale, DATE_FORMAT(date_added, "%b %Y") AS month_name')
                ->where('date_added >=', date('Y-m-01', strtotime('-11 months')))
                ->group_by('YEAR(date_added), MONTH(date_added)')
                ->order_by('YEAR(date_added), MONTH(date_added)', 'ASC')
                ->get('`orders`')->result_array();

            $month_wise_sales = array();
            $month_wise_sales['total_sale'] = array_map('intval', array_column($month_res, 'total_sale'));
            $month_wise_sales['month_name'] = array_column($month_res, 'month_name');

            $sales[0] = $month_wise_sales;

            // Week chart. "last sunday" / "next saturday" are relative to today, so on a Sunday the
            // window started 7 days in the past and on a Saturday it ended 7 days in the future -
            // an 8-to-14 day "week". Anchored to the current week explicitly instead.
            $today = strtotime('today');
            $start = date('Y-m-d', strtotime('-' . (int) date('w', $today) . ' days', $today)); // date('w') is 0 on Sunday
            $end   = date('Y-m-d', strtotime($start . ' +6 days'));

            $week_res = $this->db->select("DATE_FORMAT(date_added, '%d-%b') as date, SUM(final_total) as total_sale")
                ->where('DATE(date_added) >=', $start)
                ->where('DATE(date_added) <=', $end)
                ->group_by('DATE(date_added)')
                ->order_by('DATE(date_added)', 'ASC')
                ->get('`orders`')->result_array();

            $week_wise_sales = array();
            $week_wise_sales['total_sale'] = array_map('intval', array_column($week_res, 'total_sale'));
            $week_wise_sales['week'] = array_column($week_res, 'date');

            $sales[1] = $week_wise_sales;

            // Day chart. GROUP BY day(date_added) alone merged the 1st of this month with the 1st
            // of last month whenever the 30-day window straddled a month boundary, and produced a
            // bare day-of-month label that jumped backwards mid-axis.
            $day_res = $this->db->select("DATE_FORMAT(date_added, '%d-%b') as date, SUM(final_total) as total_sale")
                ->where('date_added >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)')
                ->group_by('DATE(date_added)')
                ->order_by('DATE(date_added)', 'ASC')
                ->get('`orders`')->result_array();

            $day_wise_sales = array();
            $day_wise_sales['total_sale'] = array_map('intval', array_column($day_res, 'total_sale'));
            $day_wise_sales['day'] = array_column($day_res, 'date');

            $sales[2] = $day_wise_sales;
            echo json_encode($sales);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function category_wise_product_count()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $res = $this->db->select('c.name as name,count(c.id) as counter')->where(['p.status' => '1', 'c.status' => '1'])->join('products p', 'p.category_id=c.id')->group_by('c.id')->get('categories c')->result_array();
            $result = array();
            // Column headers were still the Google Charts sample labels ("Task" / "Hours per Day"),
            // which showed up verbatim in the chart tooltips and legend.
            $result[0] = array('Category', 'Products');
            foreach ($res as $row) {
                $result[] = array((string) $row['name'], intval($row['counter']));
            }
            echo json_encode($result);
        } else {
            redirect('admin/login', 'refresh');
        }
    }


    public function delete_image()
    {
        // Had no authentication check: an unauthenticated POST could delete an arbitrary file
        // and null out an arbitrary column in an arbitrary table, since every argument came
        // straight from $_POST.
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        $required = ['id', 'path', 'field', 'img_name', 'table_name'];
        foreach ($required as $key) {
            if (!isset($_POST[$key])) {
                $this->response['error'] = true;
                $this->response['message'] = 'Missing parameter: ' . $key;
                echo json_encode($this->response);
                return false;
            }
        }

        $this->response['is_deleted'] = delete_image($_POST['id'], $_POST['path'], $_POST['field'], $_POST['img_name'], $_POST['table_name'], isset($_POST['isjson']) ? $_POST['isjson'] : true);
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        echo json_encode($this->response);
    }
    public function logout()
    {
        $this->ion_auth->logout();
        redirect('admin/login', 'refresh');
    }

    public function profile()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $identity_column = $this->config->item('identity', 'ion_auth');
            $this->data['users'] = $this->ion_auth->user()->row();
            $settings = get_settings('system_settings', true);
            $this->data['identity_column'] = $identity_column;
            $this->data['main_page'] = FORMS . 'profile';
            $this->data['title'] = 'Change Password | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Change Password | ' . $settings['app_name'];
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/home', 'refresh');
        }
    }

    public function update_status()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $this->response['error'] = true;
                $this->response['message'] = DEMO_VERSION_MSG;
                echo json_encode($this->response);
                return false;
                exit();
            }
            // The table name came straight from $_GET and was passed unvalidated into update(),
            // letting a crafted request flip a status/active column on any table in the schema.
            // Restricted to the tables the admin UI actually toggles.
            $allowed_tables = [
                'users', 'products', 'categories', 'brands', 'sliders', 'offers', 'promo_codes',
                'taxes', 'faqs', 'blogs', 'seller_data', 'subscriptions', 'featured_sections',
                'attributes', 'attribute_sets', 'time_slots', 'delivery_boy', 'system_users'
            ];
            $table = isset($_GET['table']) ? trim($_GET['table']) : '';
            $id    = isset($_GET['id']) ? $_GET['id'] : null;

            if (!in_array($table, $allowed_tables, true) || !is_numeric($id)) {
                $response['error'] = true;
                $response['csrfName'] = $this->security->get_csrf_token_name();
                $response['csrfHash'] = $this->security->get_csrf_hash();
                $response['message'] = 'Invalid request';
                echo json_encode($response);
                return false;
            }

            $status = (isset($_GET['status']) && $_GET['status'] == '1') ? 0 : 1;

            $this->db->trans_start();
            // $this->db->escape() wraps the value in quotes and set() escapes again by default,
            // so the column was being written as a doubly-quoted string rather than an integer.
            if ($table === 'users') {
                $this->db->set('active', $status);
            } else {
                $this->db->set('status', $status);
            }

            $this->db->where('id', (int) $id)->update($table);
            $this->db->trans_complete();
            $error = false;
            $message = str_replace('_', ' ', $table);
            if ($this->db->trans_status() === true) {
                $error = true;
            }
            $response['error'] = $error;
            $response['csrfName'] = $this->security->get_csrf_token_name();
            $response['csrfHash'] = $this->security->get_csrf_hash();
            $response['message'] = $message;
            print_r(json_encode($response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }
    // send admin notification
    public function get_notification()
    {
        // Was publicly readable without a session.
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            echo json_encode(['error' => true, 'message' => 'Unauthorized']);
            return false;
        }

        $count_noti = fetch_details('system_notification',   ["read_by" => 0],  'count(id) as total');

        $response['error'] = false;
        $response['count_notifications'] = $count_noti[0]['total'];

        echo json_encode($response);
    }

    public function new_notification_list()
    {
        // Was publicly readable without a session, and returned the full notification bodies.
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            echo json_encode(['error' => true, 'message' => 'Unauthorized']);
            return false;
        }

        $notifications = fetch_details('system_notification', ["read_by" => 0],  '*',  '3', '0',  'id', 'DESC',  '',  '');

        $response['error'] = false;
        $response['notifications'] = $notifications;

        echo json_encode($response);
    }

    
}
