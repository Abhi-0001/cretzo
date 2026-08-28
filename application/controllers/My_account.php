<?php
defined('BASEPATH') or exit('No direct script access allowed');

class My_account extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'pagination']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['chat_model', 'media_model', 'cart_model', 'category_model', 'address_model', 'order_model', 'Transaction_model', 'Promo_code_model', 'Customer_model', 'Area_model', 'ticket_model']);
        $this->lang->load('auth');
        // Unlike the seller/admin/delivery_boy dashboards, this only checked "is
        // logged in" - a seller or admin account viewing /my-account would be let
        // in as if they were a customer. Restricted to group 2 (customer).
        $this->data['is_logged_in'] = ($this->ion_auth->logged_in() && $this->ion_auth_model->in_group(2, $this->session->userdata('user_id'))) ? 1 : 0;
        $this->data['user'] = ($this->data['is_logged_in']) ? $this->ion_auth->user()->row() : array();
        $this->data['settings'] = get_settings('system_settings', true);
        $this->data['web_settings'] = get_settings('web_settings', true);
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
    }


    public function index()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->data['is_logged_in']) {
            $this->data['main_page'] = 'dashboard';
            $this->data['title'] = 'Dashboard | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Dashboard, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Dashboard | ' . $this->data['web_settings']['meta_description'];

            $this->data['users'] = $this->ion_auth->user()->row();
            
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function profile()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $identity_column = $this->config->item('identity', 'ion_auth');
            $this->data['users'] = $this->ion_auth->user()->row();
            $this->data['system_settings'] = get_settings('system_settings', true);
            $this->data['identity_column'] = $identity_column;
            $this->data['main_page'] = 'profile';
            $this->data['title'] = 'Profile | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = $this->data['web_settings']['meta_description'];
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function orders()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['main_page'] = 'orders';
            $this->data['title'] = 'Orders | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Orders, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Orders | ' . $this->data['web_settings']['meta_description'];

            /* Added search for Cretzo, for filtering order items based on search query */
            $search_2 = ($this->input->get('search')) ? $this->input->get('search') : null;

            $total = fetch_orders(false, $this->data['user']->id, false, false, 1, NULL, NULL, NULL, NULL, $search_2);
            
            $limit = 10;
            $page_no = (empty($this->uri->segment(3))) ? 1 : $this->uri->segment(3);
            if (!is_numeric($page_no)) {
                redirect(base_url('my-account/orders'));
            }
            $offset = ($page_no - 1) * $limit;
            $this->data['links'] = storefront_pagination(
                base_url('my-account/orders'),
                $total['total'],
                $limit,
                ['uri_segment' => 3]
            );
            $this->data['orders'] = fetch_orders(false, $this->data['user']->id, false, false, $limit, $offset, 'date_added', 'DESC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', true, $search_2);
            $this->data['payment_methods'] = get_settings('payment_method', true);
            $this->data['users'] = $this->ion_auth->user()->row();
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function order_details()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $bank_transfer = array();
            $this->data['main_page'] = 'order-details';
            $this->data['title'] = 'Orders | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Orders, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Orders | ' . $this->data['web_settings']['meta_description'];
            $order_id = $this->uri->segment(3);
            $order = fetch_orders($order_id, $this->data['user']->id, false, false, 1, NULL, NULL, NULL, NULL);

            if (!isset($order['order_data']) || empty($order['order_data'])) {
                redirect(base_url('my-account/orders'));
            }

            $this->data['order'] = $order['order_data'][0];
            if ($order['order_data'][0]['payment_method'] == "Bank Transfer") {
                $bank_transfer = fetch_details('order_bank_transfer', ['order_id' => $order['order_data'][0]['id']]);
            }
            $this->data['bank_transfer'] = $bank_transfer;

            $this->data['users'] = $this->ion_auth->user()->row();

            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function order_invoice($order_id)
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['main_page'] = VIEW . 'api-order-invoice';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Invoice Management |' . $settings['app_name'];
            $this->data['meta_description'] = 'Invoice Management | ' . $this->data['web_settings']['meta_description'];;
            if (isset($order_id) && !empty($order_id)) {
                // Scoped to the logged-in customer. This looked the order up by id alone, so
                // any signed-in user could read any other customer's invoice - products,
                // amounts and shipping address - just by changing the id in the URL.
                // order_details() above already scopes the same way.
                $res = $this->order_model->get_order_details([
                    'o.id'      => $order_id,
                    'o.user_id' => $this->ion_auth->get_user_id(),
                ], true);
                if (!empty($res)) {
                    $items = [];
                    $promo_code = [];
                    if (!empty($res[0]['promo_code'])) {
                        $promo_code = fetch_details('promo_codes', ['promo_code' => trim($res[0]['promo_code'])]);
                    }
                    foreach ($res as $row) {
                        $row = output_escaping($row);
                        $temp['product_id'] = $row['product_id'];
                        $temp['seller_id'] = $row['seller_id'];
                        $temp['product_variant_id'] = $row['product_variant_id'];
                        $temp['pname'] = $row['pname'];
                        $temp['quantity'] = $row['quantity'];
                        $temp['discounted_price'] = $row['discounted_price'];
                        $temp['tax_percent'] = $row['tax_percent'];
                        $temp['tax_amount'] = $row['tax_amount'];
                        $temp['price'] = $row['price'];
                        $temp['delivery_boy'] = $row['delivery_boy'];
                        $temp['mobile_number'] = $row['mobile_number'];
                        $temp['active_status'] = $row['oi_active_status'];
                        $temp['hsn_code'] = $row['hsn_code'];
                        array_push($items, $temp);
                    }
                    $this->data['order_detls'] = $res;
                    $this->data['items'] = $items;
                    $this->data['promo_code'] = $promo_code;
                    $this->data['print_btn_enabled'] = true;
                    $this->data['settings'] = get_settings('system_settings', true);
                    $this->load->view('admin/invoice-template', $this->data);
                } else {
                    redirect(base_url(), 'refresh');
                }
            } else {
                redirect(base_url(), 'refresh');
            }
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function update_order_item_status()
    {
        // Neither this method nor update_order() below checked that the caller was logged in,
        // let alone that the order was theirs - every other method in this controller gates on
        // is_logged_in, these two were simply missed. order_item_id is a sequential integer, so
        // anyone could POST one and cancel a stranger's order: that refunds the ORDER OWNER's
        // wallet, restores stock and claws the commission back off the seller.
        if (!$this->data['is_logged_in']) {
            $this->response['error'] = true;
            $this->response['message'] = "Please login to continue.";
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return false;
        }

        $this->form_validation->set_rules('order_item_id', 'Order item id', 'trim|required|numeric|xss_clean');
        $this->form_validation->set_rules('status', 'Status', 'trim|required|xss_clean|in_list[cancelled,returned]');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
        } else {
            $order_item = fetch_details('order_items', ['id' => $_POST['order_item_id']], 'id,order_id,user_id');
            if (empty($order_item) || (string) $order_item[0]['user_id'] !== (string) $this->data['user']->id) {
                // Same response whether the item is missing or belongs to someone else, so this
                // cannot be used to probe which order ids exist.
                $this->response['error'] = true;
                $this->response['message'] = "Order item not found.";
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }

            $this->response = $this->order_model->update_order_item($_POST['order_item_id'], trim($_POST['status']));
            if (trim($_POST['status']) != 'returned' && $this->response['error'] == false) {
                process_refund($_POST['order_item_id'], trim($_POST['status']), 'order_items');
            }
            if ($this->response['error'] == false && trim($_POST['status']) == 'cancelled') {
                restore_order_item_stock($_POST['order_item_id'], 'Order item cancelled by customer');
            }
        }
        print_r(json_encode($this->response));
    }

    public function update_order()
    {
        // See update_order_item_status(): unauthenticated and with no ownership check, this
        // cancelled or returned any order by id.
        if (!$this->data['is_logged_in']) {
            $this->response['error'] = true;
            $this->response['message'] = "Please login to continue.";
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return false;
        }

        $this->form_validation->set_rules('order_id', 'Order id', 'trim|required|numeric|xss_clean');
        $this->form_validation->set_rules('status', 'Status', 'trim|required|xss_clean|in_list[cancelled,returned]');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = validation_errors();
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return false;
        } else {
            $order = fetch_details('orders', ['id' => $_POST['order_id']], 'id,user_id');
            if (empty($order) || (string) $order[0]['user_id'] !== (string) $this->data['user']->id) {
                $this->response['error'] = true;
                $this->response['message'] = "Order not found.";
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }

            $res = validate_order_status($_POST['order_id'], $_POST['status'], 'orders', '', true);

            if ($res['error']) {
                $this->response['error'] = (isset($res['return_request_flag'])) ? false : true;
                $this->response['message'] = $res['message'];
                $this->response['data'] = $res['data'];
                print_r(json_encode($this->response));
                return false;
            }
            if ($_POST['status'] == 'returned') {
                $_POST['status'] = 'return_request_pending';
            }
            if ($this->order_model->update_order(['status' => $_POST['status']], ['order_id' => $_POST['order_id']], true)) {
                $this->order_model->update_order(['active_status' => $_POST['status']], ['order_id' => $_POST['order_id']], false, 'order_items');
                if ($this->order_model->update_order(['status' => $_POST['status']], ['order_id' => $_POST['order_id']], true, 'order_items')) {

                    $this->order_model->update_order(['active_status' => $_POST['status']], ['order_id' => $_POST['order_id']], false, 'order_items');
                    process_refund($_POST['order_id'], $_POST['status'], 'orders');
                    // Was `trim($_POST['status'] == 'cancelled')` - the comparison happens
                    // INSIDE trim(), so this trimmed a boolean and then tested the resulting
                    // string. trim(true) is "1" (truthy) and trim(false) is "" (falsy), so it
                    // happened to behave correctly, but only by accident; a 'returned' status
                    // reached it as "" and was skipped for the right reason by luck.
                    if (trim($_POST['status']) == 'cancelled') {
                        restore_order_stock($_POST['order_id'], 'Order cancelled by customer');
                    }
                    $this->response['error'] = false;
                    $this->response['message'] = 'Order Updated Successfully';
                    $this->response['data'] = array();
                    print_r(json_encode($this->response));
                    return false;
                }
            }
        }
    }

    public function notifications()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['main_page'] = 'notifications';
            $this->data['title'] = 'Notification | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Notification, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Notification | ' . $this->data['web_settings']['meta_description'];
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    /* ============================ notifications ============================
     *
     * The storefront bell was a static image with a hardcoded "0" - not a link, no count, no
     * click target - and the notifications page it should have opened fed a bootstrap-table
     * from an ADMIN url (admin/Notification_settings/get_notification_list). These endpoints
     * give the customer side its own read path with real per-user read state.
     */

    private function notification_json($payload)
    {
        $payload['csrfName'] = $this->security->get_csrf_token_name();
        $payload['csrfHash'] = $this->security->get_csrf_hash();
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }

    /** Paginated list of this customer's notifications. */
    public function get_notifications()
    {
        if (!$this->data['is_logged_in']) {
            $this->notification_json(['error' => true, 'message' => 'Please log in.', 'total' => 0, 'rows' => []]);
            return;
        }

        $this->load->model('notification_model');
        $limit  = (is_numeric($this->input->get('limit')) && (int) $this->input->get('limit') > 0) ? min((int) $this->input->get('limit'), 50) : 10;
        $offset = (is_numeric($this->input->get('offset')) && (int) $this->input->get('offset') > 0) ? (int) $this->input->get('offset') : 0;
        $unread_only = ($this->input->get('unread') === '1');

        $result = $this->notification_model->get_user_inbox(
            (int) $this->session->userdata('user_id'),
            $limit,
            $offset,
            $unread_only,
            'customer'
        );

        $result['error'] = false;
        $result['unread'] = $this->notification_model->count_user_unread((int) $this->session->userdata('user_id'));
        $this->notification_json($result);
    }

    /** Unread badge for the header bell. */
    public function notification_count()
    {
        if (!$this->data['is_logged_in']) {
            $this->notification_json(['error' => true, 'unread' => 0]);
            return;
        }
        $this->load->model('notification_model');
        $this->notification_json([
            'error'  => false,
            'unread' => $this->notification_model->count_user_unread((int) $this->session->userdata('user_id')),
        ]);
    }

    /** Marks one notification read, or all of them when no id is given. */
    public function mark_notification_read()
    {
        if (!$this->data['is_logged_in']) {
            $this->notification_json(['error' => true, 'message' => 'Please log in.']);
            return;
        }
        $this->load->model('notification_model');
        $id = $this->input->post('notification_id');
        $id = (is_numeric($id) && (int) $id > 0) ? (int) $id : null;

        $ok = $this->notification_model->mark_user_read((int) $this->session->userdata('user_id'), $id);
        $this->notification_json([
            'error'   => !$ok,
            'message' => $ok ? 'Marked as read.' : 'Could not update the notification.',
            'unread'  => $this->notification_model->count_user_unread((int) $this->session->userdata('user_id')),
        ]);
    }

    public function manage_address()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['main_page'] = 'address';
            $this->data['title'] = 'Address | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Address, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Address | ' . $this->data['web_settings']['meta_description'];
            $this->data['cities'] = get_cities();
            $this->data['areas'] = fetch_details('areas', NULL);

            // added for Cretzo theme, fetch cusotmer's address while loading page.
            $this->data['addresses'] = $this->address_model->get_address_list($this->data['user']->id, false, false);
            // $this->data['addresses'] = $this->address_model->get_address_list($this->data['user']->id, false, false);

            $this->data['users'] = $this->ion_auth->user()->row();
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }
    public function get_cities()
    {
        $search = $this->input->get('search');
        $response = $this->Area_model->get_cities_list($search);
        echo json_encode($response);
    }

    public function wallet()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['main_page'] = 'wallet';
            $this->data['title'] = 'Wallet | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Wallet, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Wallet | ' . $this->data['web_settings']['meta_description'];
            $this->data['users'] = $this->ion_auth->user()->row();
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function transactions()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['main_page'] = 'transactions';
            $this->data['title'] = 'Transactions | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Transactions, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Transactions | ' . $this->data['web_settings']['meta_description'];
            $this->data['users'] = $this->ion_auth->user()->row();
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function add_address()
    {
        if ($this->ion_auth->logged_in()) {
            $this->form_validation->set_rules('type', 'Type', 'trim|xss_clean');
            $this->form_validation->set_rules('country_code', 'Country Code', 'trim|xss_clean');
            $this->form_validation->set_rules('name', 'Name', 'trim|xss_clean|required');
            $this->form_validation->set_rules('mobile', 'Mobile', 'trim|numeric|xss_clean|required');
            $this->form_validation->set_rules('alternate_mobile', 'Alternative Mobile', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('address', 'Address', 'trim|xss_clean|required');
            $this->form_validation->set_rules('landmark', 'Landmark', 'trim|xss_clean');
            $this->form_validation->set_rules('city_name', 'City', 'trim|xss_clean');
            $this->form_validation->set_rules('area_name', 'Area', 'trim|xss_clean');
            $this->form_validation->set_rules('area_id', 'Area', 'trim|xss_clean');
            $this->form_validation->set_rules('city_id', 'City', 'trim|xss_clean');
            $this->form_validation->set_rules('pincode', 'Pincode', 'trim|numeric|xss_clean|required');
            $this->form_validation->set_rules('state', 'State', 'trim|xss_clean|required');
            $this->form_validation->set_rules('country', 'Country', 'trim|xss_clean|required');
            $this->form_validation->set_rules('latitude', 'Latitude', 'trim|xss_clean');
            $this->form_validation->set_rules('longitude', 'Longitude', 'trim|xss_clean');


            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }

            $arr = $this->input->post(null, true);
            $arr['user_id'] = $this->data['user']->id;
            $this->address_model->set_address($arr);
            $res = $this->address_model->get_address($this->data['user']->id, false, true);
            $this->response['error'] = false;
            $this->response['message'] = 'Address Added Successfully';
            $this->response['data'] = $res;
            print_r(json_encode($this->response));
            return false;
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }

    public function edit_address()
    {
        if ($this->ion_auth->logged_in()) {
            $this->form_validation->set_rules('id', 'Id', 'trim|required|numeric|xss_clean');
            $this->form_validation->set_rules('type', 'Type', 'trim|xss_clean');
            $this->form_validation->set_rules('country_code', 'Country Code', 'trim|xss_clean');
            $this->form_validation->set_rules('name', 'Name', 'required|trim|xss_clean');
            $this->form_validation->set_rules('mobile', 'Mobile', 'required|trim|numeric|xss_clean');
            $this->form_validation->set_rules('alternate_mobile', 'Alternative Mobile', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('address', 'Address', 'trim|xss_clean');
            $this->form_validation->set_rules('landmark', 'Landmark', 'trim|xss_clean');
            $this->form_validation->set_rules('area_id', 'Area', 'trim|xss_clean');
            $this->form_validation->set_rules('city_id', 'City', 'trim|xss_clean');

            if (isset($_POST['pincode_name']) && !empty($_POST['pincode_name'])) {
                $this->form_validation->set_rules('pincode_name', 'Pincode Name', 'trim|exact_length[6]|xss_clean|required');
            }
            else{
                $this->form_validation->set_rules('pincode', 'Pincode', 'trim|numeric|exact_length[6]|xss_clean|required');
            }

            $this->form_validation->set_rules('state', 'State', 'required|trim|xss_clean');
            $this->form_validation->set_rules('country', 'Country', 'required|trim|xss_clean');

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }
            // Was never verified that this address actually belongs to the logged-in user -
            // any logged-in customer could edit any other customer's address just by knowing
            // its numeric id (IDOR).
            $owned = fetch_details('addresses', ['id' => $_POST['id'], 'user_id' => $this->data['user']->id], 'id');
            if (empty($owned)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Address not found';
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }
            // print_R($_POST);
            $this->address_model->set_address($_POST);
            $res = $this->address_model->get_address(null, $_POST['id'], true);
            $this->response['error'] = false;
            $this->response['message'] = 'Address updated Successfully';
            $this->response['data'] = $res;
            print_r(json_encode($this->response));
            return false;
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }

    //delete_address
    public function delete_address()
    {
        if ($this->ion_auth->logged_in()) {
            $this->form_validation->set_rules('id', 'Id', 'trim|required|numeric|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }
            // Was never verified that this address actually belongs to the logged-in user -
            // any logged-in customer could delete any other customer's address just by knowing
            // its numeric id (IDOR).
            $owned = fetch_details('addresses', ['id' => $_POST['id'], 'user_id' => $this->data['user']->id], 'id');
            if (empty($owned)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Address not found';
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }
            $this->address_model->delete_address($_POST);
            $this->response['error'] = false;
            $this->response['message'] = 'Address Deleted Successfully';
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return false;
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }

    //set default_address
    public function set_default_address()
    {
        if ($this->ion_auth->logged_in()) {
            $this->form_validation->set_rules('id', 'Id', 'trim|required|numeric|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }
            // Was never verified that this address actually belongs to the logged-in user -
            // any logged-in customer could make any other customer's address "default" (and,
            // via set_address()'s is_default handling, clear that OTHER customer's own default
            // flag) just by knowing its numeric id (IDOR).
            $owned = fetch_details('addresses', ['id' => $_POST['id'], 'user_id' => $this->data['user']->id], 'id');
            if (empty($owned)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Address not found';
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return false;
            }
            $_POST['user_id'] = $this->data['user']->id;
            $_POST['is_default'] = true;
            $this->address_model->set_address($_POST);
            $this->response['error'] = false;
            $this->response['message'] = 'Set as default successfully';
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return false;
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }

    //get_address
    public function get_address()
    {
        if ($this->ion_auth->logged_in()) {
            $res = $this->address_model->get_address($this->data['user']->id);
            $is_default_counter = array_count_values(array_column($res, 'is_default'));


            if (!empty($res)) {
                $this->response['error'] = false;
                $this->response['message'] = 'Address Retrieved Successfully';
                $this->response['data'] = $res;
            } else {
                $this->response['error'] = true;
                $this->response['message'] = "No Details Found !";
                $this->response['data'] = array();
            }
            print_r(json_encode($this->response));
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }
    public function get_promo_codes()
    {
        if ($this->ion_auth->logged_in()) {
            $this->form_validation->set_rules('sort', 'sort', 'trim|xss_clean');
            $this->form_validation->set_rules('limit', 'limit', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('offset', 'offset', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('order', 'order', 'trim|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['message'] = strip_tags(validation_errors());
                $this->response['data'] = array();
                print_r(json_encode($this->response));
                return;
            } else {
                $limit = (isset($_POST['limit']) && is_numeric($_POST['limit']) && !empty(trim($_POST['limit']))) ? $this->input->post('limit', true) : 25;
                $offset = (isset($_POST['offset']) && is_numeric($_POST['offset']) && !empty(trim($_POST['offset']))) ? $this->input->post('offset', true) : 0;
                $order = (isset($_POST['order']) && !empty(trim($_POST['order']))) ? $_POST['order'] : 'DESC';
                $sort = (isset($_POST['sort']) && !empty(trim($_POST['sort']))) ? $_POST['sort'] : 'id';
                $this->response['error'] = false;
                $this->response['message'] = 'Promocodes retrived Successfully !';
                $result = $this->Promo_code_model->get_promo_codes($limit, $offset, $sort, $order);
                $this->response['total'] = $result['total'];
                $this->response['offset'] = (isset($offset) && !empty($offset)) ? $offset : '0';
                $this->response['promo_codes'] = $result['data'];
                print_r(json_encode($this->response));
                return;
            }
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }

    public function get_address_list()
    {
        if ($this->ion_auth->logged_in()) {
            return $this->address_model->get_address_list($this->data['user']->id);
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }

    public function get_areas()
    {
        if ($this->ion_auth->logged_in()) {
            $this->form_validation->set_rules('city_id', 'City Id', 'trim|required|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return false;
            }

            $city_id = $this->input->post('city_id', true);
            $areas = fetch_details('areas', ['city_id' => $city_id]);
            if (empty($areas)) {
                $this->response['error'] = true;
                $this->response['message'] = "No Areas found for this City.";
                print_r(json_encode($this->response));
                return false;
            }
            $this->response['error'] = false;
            $this->response['data'] = $areas;
            print_r(json_encode($this->response));
            return false;
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }
    public function get_zipcode()
    {
        if ($this->ion_auth->logged_in()) {
            $this->form_validation->set_rules('city_id', 'City Id', 'trim|required|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
                return false;
            }

            $city_id = $this->input->post('city_id', true);

            //if zipcode table is not sync with area table then gte zipcode list from area table 
            if ($this->db->field_exists('minimum_free_delivery_order_amount', 'zipcodes')) {
                $zipcodes = fetch_details('zipcodes', ['city_id' => $city_id], 'zipcode,id');
            } else {
                $array = $this->db->select('z.zipcode,z.id as id')->join('zipcodes z', 'z.id=a.zipcode_id')->where('city_id', $city_id)->get('areas a')->result_array();
                //remove duplicate value from $array
                $zipcodes = array_map("unserialize", array_unique(array_map("serialize", $array)));
            }

            if (empty($zipcodes)) {
                $this->response['error'] = true;
                $this->response['message'] = "No Zipcodes found for this area.";
                print_r(json_encode($this->response));
                return false;
            }
            $this->response['error'] = false;
            $this->response['data'] = $zipcodes;
            print_r(json_encode($this->response));
            return false;
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized access is not allowed';
            print_r(json_encode($this->response));
            return false;
        }
    }

    public function favorites()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->data['is_logged_in']) {
            $this->data['main_page'] = 'favorites';
            $this->data['title'] = 'Dashboard | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Dashboard, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Dashboard | ' . $this->data['web_settings']['meta_description'];

            $limit = 12;
            $total_rows = get_favorites($this->data['user']->id, NULL, NULL, TRUE);
            $theme = fetch_details('themes', ['status' => 1], 'name');
            $page_no = (empty($this->uri->segment(3))) ? 1 : $this->uri->segment(3);
            if (!is_numeric($page_no)) {
                redirect(base_url('my-account/favorites'));
            }
            $offset = ($page_no - 1) * $limit;
            $this->data['links'] = storefront_pagination(
                base_url('my-account/favorites'),
                $total_rows,
                $limit,
                ['uri_segment' => 3]
            );
            $this->data['total_rows'] = $total_rows;
            $this->data['page_no'] = $page_no;
            $this->data['per_page'] = $limit;
            $this->data['num_pages'] = (int) ceil($total_rows / $limit);
            $this->data['products'] = get_favorites($this->data['user']->id, $limit, $offset);
            $this->data['settings'] = get_settings('system_settings', true);
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function manage_favorites()
    {
        if ($this->data['is_logged_in']) {
            $this->form_validation->set_rules('product_id', 'Product Id', 'trim|numeric|required|xss_clean');
            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                $this->response['data'] = array();
                // Filled the response and then never sent it - every success path below returns
                // from inside the else, so a failed validation fell out of the method with an
                // EMPTY body. The wishlist button posts with dataType:'json', so an empty body
                // fails to parse, the success callback never fires, and the button simply hangs
                // with nothing shown to the shopper.
                print_r(json_encode($this->response));
                return false;
            } else {
                $data = [
                    'user_id' => $this->data['user']->id,
                    'product_id' => $this->input->post('product_id', true),
                ];
                if (is_exist($data, 'favorites')) {
                    $this->db->delete('favorites', $data);
                    $this->response['error']   = false;
                    $this->response['message'] = "Product removed from favorite !";
                    print_r(json_encode($this->response));
                    return false;
                }
                $data = escape_array($data);
                $this->db->insert('favorites', $data);
                $this->response['error'] = false;
                $this->response['message'] = 'Product Added to favorite';
                print_r(json_encode($this->response));
                return false;
            }
        } else {
            $this->response['error'] = true;
            $this->response['message'] = "Login First to Add Products in Favorite List.";
            print_r(json_encode($this->response));
            return false;
        }
    }

    /* Add multiple products to favorites. (FN added for Cretzo) */
    public function add_to_favorites()
    {
        if ($this->data['is_logged_in']) {
            $this->form_validation->set_rules('product_ids[]', 'Product Ids', 'trim|required|xss_clean');

            if (!$this->form_validation->run()) {
                $this->response['error'] = true;
                $this->response['message'] = validation_errors();
                $this->response['data'] = array();
                // Same silent failure as manage_favorites(): the response was built and then
                // dropped, so the caller received an empty body instead of a reason.
                print_r(json_encode($this->response));
                return false;
            } else {
                $user_id = $this->data['user']->id;
                $product_ids = $this->input->post('product_ids', true);

                foreach ($product_ids as $product_id) {
                    $data = [
                        'user_id' => $user_id,
                        'product_id' => $product_id,
                    ];

                    if (!is_exist($data, 'favorites')) {
                        // Add to favorites if it doesn't exist
                        $data = escape_array($data);
                        $this->db->insert('favorites', $data);
                        $this->response['message'][] = "Product $product_id added to favorites!";   
                    }

                    /* if (is_exist($data, 'favorites')) {
                        // Remove from favorites if it exists
                        $this->db->delete('favorites', $data);
                        $this->response['message'][] = "Product $product_id removed from favorites!";
                    } else {
                        // Add to favorites if it doesn't exist
                        $data = escape_array($data);
                        $this->db->insert('favorites', $data);
                        $this->response['message'][] = "Product $product_id added to favorites!";
                    } */
                }

                $this->response['error'] = false;
                print_r(json_encode($this->response));
                return false;
            }
        } else {
            $this->response['error'] = true;
            $this->response['message'] = "Login First to Add Products in Favorite List.";
            print_r(json_encode($this->response));
            return false;
        }
    }

    public function get_transactions()
    {
        if ($this->ion_auth->logged_in()) {
            return $this->Transaction_model->get_transactions_list($this->data['user']->id);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function get_wallet_transactions()
    {
        if ($this->ion_auth->logged_in()) {
            return $this->Transaction_model->get_transactions_list($this->data['user']->id);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    /**
     * Customer wallet withdrawal request.
     *
     * This endpoint had NO authentication check of any kind and took the user_id whose wallet to
     * debit straight from the request body - so an unauthenticated POST could drain ANY user's
     * balance to an attacker-supplied payment address. It is the same hole that was closed on
     * seller/Payment_request::add_withdrawal_request(); the customer-side copy was missed.
     *
     * Everything else it did by hand is also wrong, and all of it is already solved correctly in
     * Payment_request_model::create_withdrawal_request(), so this now routes through that:
     *
     *   - the balance was read, compared and debited in three separate unlocked statements, so
     *     two requests submitted at once both passed `amount <= balance` against the same
     *     starting balance and both were inserted - letting a customer withdraw more than they
     *     had and leaving users.balance negative;
     *   - the debit went through update_balance_customer(), which moves users.balance and writes
     *     NO `transactions` row, so the withdrawal never appeared in the customer's own wallet
     *     history and the stored balance drifted from the ledger by every withdrawal ever made;
     *   - the insert and the debit were unwrapped, so a failure between them either took the
     *     money without recording the request or recorded a request without taking the money;
     *   - there was no minimum amount and no limit on open requests.
     */
    public function withdraw_money()
    {
        // user_id is the SESSION's user, never a POST value - see the note above.
        if (!$this->ion_auth->logged_in()) {
            $this->response['error'] = true;
            $this->response['message'] = 'Please sign in to request a withdrawal.';
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return false;
        }

        $this->form_validation->set_rules('payment_address', 'Payment Address', 'trim|required|xss_clean');
        $this->form_validation->set_rules('amount', 'Amount', 'trim|required|xss_clean|numeric|greater_than[0]');

        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return false;
        }

        $this->load->model('payment_request_model');

        $user_id = $this->data['user']->id;
        $payment_address = $this->input->post('payment_address', true);
        $amount = round((float) $this->input->post('amount', true), 2);

        $result = $this->payment_request_model->create_withdrawal_request($user_id, 'customer', $amount, $payment_address);

        $this->response['error'] = $result['error'];
        $this->response['message'] = $result['message'];
        // The view shows the remaining balance, so keep returning it - but from the model's
        // post-commit read rather than a second unsynchronised SELECT.
        $this->response['data'] = isset($result['balance']) ? $result['balance'] : array();

        // Was inside `if (!empty($userData))`, so a user row that could not be read produced a
        // completely EMPTY response body - the form showed neither an error nor a success.
        print_r(json_encode($this->response));
    }

    public function get_withdrawal_request()
    {
        if ($this->ion_auth->logged_in()) {
            return $this->Transaction_model->get_withdrawal_transactions_list($this->data['user']->id);
        } else {
            redirect(base_url(), 'refresh');
        }
    }


    // ======================== code for chat ====================================

    public function chat()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['main_page'] = 'chat';
            $this->data['title'] = 'Transactions | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Transactions, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Transactions | ' . $this->data['web_settings']['meta_description'];

            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Update Notification Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Update Notification Settings  | ' . $settings['app_name'];
            $this->data['fcm_server_key'] = get_settings('fcm_server_key');
            $users = $this->chat_model->get_chat_history($_SESSION['user_id'], 10, 0);
            $user = array();
            $i = 0;
            $type = 'person';
            $to_id = $this->session->userdata('user_id');

            foreach ($users as $row) {

                // Was $row['id'], which is the id of the newest MESSAGE in this conversation, not
                // the id of the person it is with - get_chat_history() aliases that as
                // `opponent_user_id`. Every unread badge on the chat list was therefore counted
                // against an unrelated user id (message #7 -> user #7), so the numbers shown were
                // arbitrary: usually 0, occasionally somebody else's unread total.
                $from_id = isset($row['opponent_user_id']) ? (int) $row['opponent_user_id'] : 0;

                // Also declared outside the loop, so a row with no opponent id reused the previous
                // row's count instead of showing zero.
                $unread_meg = 0;
                if ($from_id > 0) {
                    $unread_meg = $this->chat_model->get_unread_msg_count($type, $from_id, $to_id);
                }

                $user[$i] = $row;
                $user[$i]['unread_msg'] = $unread_meg;
                // Chat_model::get_chat_history() selects the other party's name as
                // `opponent_username` (u.username AS opponent_username) - there is no plain
                // `username` key in these rows, so this warned "Undefined array key
                // username" on every visit to the chat pages and assigned null.
                $user[$i]['picture'] = isset($row['opponent_username']) ? $row['opponent_username'] : '';

                $date = strtotime('now');
                // Same mix-up: comparing the viewer's user id against a message id.
                if ($to_id == $from_id) {
                    $user[$i]['is_online'] = 1;
                } else {
                    if ($row['last_online'] > $date) {
                        $user[$i]['is_online'] = 1;
                    } else {
                        $user[$i]['is_online'] = 0;
                    }
                }
                $i++;
            }
            // if ($this->ion_auth->is_admin()) {
            //     $this->data['not_in_groups'] = $this->chat_model->get_groups_all($to_id);
            // } else {
            //     $this->data['not_in_groups'] = '';
            // }

            // $this->data['groups'] = $this->chat_model->get_groups($to_id);
            $this->data['supporters'] = $this->chat_model->get_supporters($to_id);
            $this->data['users'] = $user;
            $this->load->view('front-end/' . THEME . '/template', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function make_me_online()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {

            $user_id = $this->session->userdata('user_id');
            $date = strtotime('now');
            $date = $date + 60;
            $data = array(
                'last_online' => $date
            );

            if ($this->chat_model->make_me_online($user_id, $data)) {

                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }
    public function get_system_settings()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $response = get_settings('firebase_settings');
            echo json_encode($response);
        }
    }

    public function get_online_members()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $user_id = $this->session->userdata('user_id');

            $date = strtotime('now');
            $date = $date + 15;
            $data = array(
                'last_online' => $date
            );

            $this->chat_model->make_me_online($user_id, $data);

            $users = $this->chat_model->get_chat_history($user_id, 20, 0);

            /*
             * This used to read:
             *     $user_ids = explode(',', $users[0]['id']);
             * `$users[0]['id']` is a single integer - the id of the newest message in the first
             * conversation - so exploding it on commas yielded a one-element array holding a
             * MESSAGE id, which was then looked up in `users`. Result: the online-members panel
             * listed at most one user, and that user was whoever happened to own the id matching
             * a message id, not one of the viewer's actual contacts. It also raised "Undefined
             * array key 0" whenever the viewer had no conversations yet.
             *
             * The conversation rows already carry the correct contact id as `opponent_user_id`.
             */
            $user_ids = array();
            foreach ($users as $row) {
                if (!empty($row['opponent_user_id'])) {
                    $user_ids[] = (int) $row['opponent_user_id'];
                }
            }
            $user_ids = array_values(array_unique($user_ids));

            $member = array();
            $i = 0;

            $type = 'person';
            $to_id = $this->session->userdata('user_id');

            $members = !empty($user_ids) ? $this->chat_model->get_members($user_ids) : array();

            foreach ($members as $row) {

                $from_id = (int) $row['id'];

                $unread_meg = $this->chat_model->get_unread_msg_count($type, $from_id, $to_id);

                $member[$i] = $row;
                $member[$i]['unread_msg'] = $unread_meg;
                $member[$i]['picture']  = isset($row['image']) ? $row['image'] : '';
                $date = strtotime('now');

                if ($row['last_online'] > $date) {
                    $member[$i]['is_online'] = 1;
                } else {
                    $member[$i]['is_online'] = 0;
                }
                $i++;
            }

            // $data1['groups'] = $this->chat_model->get_groups($to_id);
            $data1['members'] = $member;

            if (!empty($member)) {
                $response['error'] = false;
                $response['data'] = $data1;
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    // public function create_group()
    // {

    //     if ($this->ion_auth->logged_in()) {


    //         $user_id = $this->session->userdata('user_id');

    //         $this->form_validation->set_rules('title', 'Titel', 'trim|required|xss_clean');
    //         $this->form_validation->set_rules('description', 'Description', 'trim|required|xss_clean');
    //         if (!$this->form_validation->run()) {

    //             $this->response['error'] = true;
    //             $this->response['csrfName'] = $this->security->get_csrf_token_name();
    //             $this->response['csrfHash'] = $this->security->get_csrf_hash();
    //             $this->response['message'] = validation_errors();
    //             print_r(json_encode($this->response));
    //         } else {
    //             $admin_id = $this->session->userdata('user_id');

    //             if (!empty($this->input->post('users'))) {
    //                 $group_mem_ids = implode(",", $this->input->post('users')) . ',' . $admin_id;
    //                 $group_mem_ids = explode(",", $group_mem_ids);
    //             } else {
    //                 $group_mem_ids = array($this->session->userdata('user_id'));
    //             }


    //             $no_of_mem = count($group_mem_ids);

    //             $data = array(
    //                 'title' => strip_tags($this->input->post('title', true)),
    //                 'description' => strip_tags($this->input->post('description', true)),
    //                 'created_by' => $this->session->userdata('user_id'),
    //                 'no_of_members' => $no_of_mem
    //             );

    //             $group_id = $this->chat_model->create_group($data);

    //             if ($group_id != false) {

    //                 foreach ($group_mem_ids as $user_id) {
    //                     $data1 = array(
    //                         'group_id' => $group_id,
    //                         'user_id' => $user_id,
    //                     );
    //                     $this->chat_model->add_group_members($data1);
    //                 }
    //                 $admins_ids = array($admin_id);
    //                 $this->chat_model->make_group_admin($group_id, $admins_ids);

    //                 $this->session->set_flashdata('message', 'Group Created successfully.');
    //                 $this->session->set_flashdata('message_type', 'success');
    //             } else {
    //                 $this->session->set_flashdata('message', 'Group could not Created! Try again!');
    //                 $this->session->set_flashdata('message_type', 'error');
    //             }

    //             $response['error'] = false;
    //             $response['message'] = 'Successful';
    //             echo json_encode($response);
    //         }
    //     } else {
    //         redirect('admin/login', 'refresh');
    //     }
    // }

    public function update_web_fcm()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $fcm = $this->input->post('web_fcm');
            $user_id = $this->session->userdata('user_id');
            if ($this->chat_model->update_web_fcm($user_id, $fcm)) {

                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    public function send_msg()
    {
        // print_r($_FILES);
        // return;
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $user_id = $this->session->userdata('user_id');



            $data = array(
                'type' => $this->input->post('chat_type'),
                'from_id' => $this->session->userdata('user_id'),
                'to_id' => $this->input->post('opposite_user_id'),
                'message' => $this->input->post('chat-input-textarea')
            );
            $msg_id = $this->chat_model->send_msg($data);

            if (!empty($_FILES['documents']['name'])) {

                $year = date('Y');
                // CHAT_MEDIA_PATH already ends in '/', so appending another produced
                // 'uploads/chat_media//' - stored verbatim in media.sub_directory and in every
                // URL built from it. Harmless to the filesystem, but it means the stored path no
                // longer matches CHAT_MEDIA_PATH, which Chat_model::delete_msg() now relies on.
                $target_path = FCPATH . CHAT_MEDIA_PATH;
                $sub_directory = CHAT_MEDIA_PATH;

                if (!file_exists($target_path)) {
                    mkdir($target_path, 0777, true);
                }

                $temp_array = $media_ids = $other_images_new_name = array();
                $files = $_FILES;
                $other_image_info_error = "";
                $allowed_media_types = implode('|', allowed_media_types());
                $config['upload_path'] = $target_path;
                $config['allowed_types'] = $allowed_media_types;
                // No size cap was set (CI defaults max_size to 0 = unlimited) on an endpoint that
                // accepts every type the media library allows, from any logged-in user.
                $config['max_size'] = 20480;
                $other_image_cnt = count($_FILES['documents']['name']);
                $other_img = $this->upload;
                $other_img->initialize($config);
                for ($i = 0; $i < $other_image_cnt; $i++) {
                    if (!empty($_FILES['documents']['name'][$i])) {

                        $_FILES['temp_image']['name'] = $files['documents']['name'][$i];
                        $_FILES['temp_image']['type'] = $files['documents']['type'][$i];
                        $_FILES['temp_image']['tmp_name'] = $files['documents']['tmp_name'][$i];
                        $_FILES['temp_image']['error'] = $files['documents']['error'][$i];
                        $_FILES['temp_image']['size'] = $files['documents']['size'][$i];
                        if (!$other_img->do_upload('temp_image')) {
                            $other_image_info_error = $other_image_info_error . ' ' . $other_img->display_errors();
                        } else {
                            $temp_array = $other_img->data();
                            $temp_array['sub_directory'] = $sub_directory;
                            $media_ids[] = $media_id = $this->media_model->set_media($temp_array); /* set media in database */
                            if (strtolower($temp_array['image_type']) != 'gif')
                                resize_image($temp_array,  $target_path, $media_id);
                            $other_images_new_name[$i] = $temp_array['file_name'];

                            // Three bugs in the row this used to write, all of which made the
                            // attachment unreachable:
                            //   - file_name stored $_FILES['temp_image']['tmp_name'], i.e. PHP's
                            //     temporary path (C:\...\phpXXXX.tmp), which no longer exists by
                            //     the time anyone reads the row. The name the file was actually
                            //     saved under ($temp_array['file_name']) was thrown away, so
                            //     nothing could build a URL to it - and Chat_model::delete_msg(),
                            //     which unlinks by this column, could never find the real file.
                            //   - file_extension stored the browser-supplied MIME type, not an
                            //     extension.
                            //   - the insert sat OUTSIDE this else, so a FAILED upload still
                            //     created a chat_media row pointing at nothing.
                            $data = array(
                                'original_file_name' => $_FILES['temp_image']['name'],
                                'file_name' => $temp_array['file_name'],
                                'file_extension' => ltrim($temp_array['file_ext'], '.'),
                                'file_size' => $_FILES['temp_image']['size'],
                                'user_id' => $this->session->userdata('user_id'),
                                'message_id' => $msg_id
                            );
                            $file_id = $this->chat_model->add_file($data);
                            $this->chat_model->add_media_ids_to_msg($msg_id, $file_id);
                        }
                    } else {

                        $_FILES['temp_image']['name'] = $files['documents']['name'][$i];
                        $_FILES['temp_image']['type'] = $files['documents']['type'][$i];
                        $_FILES['temp_image']['tmp_name'] = $files['documents']['tmp_name'][$i];
                        $_FILES['temp_image']['error'] = $files['documents']['error'][$i];
                        $_FILES['temp_image']['size'] = $files['documents']['size'][$i];
                        if (!$other_img->do_upload('temp_image')) {
                            $other_image_info_error = $other_img->display_errors();
                        }
                        // No chat_media row here: this branch is reached only when the
                        // slot's filename is EMPTY, i.e. nothing was uploaded. It used to insert
                        // a row anyway, attaching a phantom file to the message.
                    }
                }

                // Deleting Uploaded Images if any overall error occured
                if ($other_image_info_error != NULL) {
                    if (isset($other_images_new_name) && !empty($other_images_new_name)) {
                        foreach ($other_images_new_name as $key => $val) {
                            unlink($target_path . $other_images_new_name[$key]);
                        }
                    }
                }
            }


            $messages = $this->chat_model->get_msg_by_id($msg_id, $this->input->post('opposite_user_id'), $this->session->userdata('user_id'), $this->input->post('chat_type'));
            $message = array();
            $i = 0;
            foreach ($messages as $row) {
                $message[$i] = $row;
                $media_files = $this->chat_model->get_media($row['id']);
                $message[$i]['media_files'] = !empty($media_files) ? $media_files : '';
                $message[$i]['text'] = $row['message'];
                $i++;
            }
            $new_msg = $message;

            if (!empty($msg_id)) {

                $to_id = $this->input->post('opposite_user_id');
                $from_id = $this->session->userdata('user_id');

                // if ($to_id == $from_id && $this->input->post('chat_type') == 'person') {
                //     return false;
                // }

                // single user msg
                if (($this->input->post('chat_type') == 'person') || ($this->input->post('chat_type') == 'supporter')) {
                    // this is the user who going to recive FCM msg
                    // $user = $this->users_model->get_user_by_id($to_id);
                    $user = fetch_details('users', ['active' => 1, 'id' => $to_id]);

                    // this is the user who going to send FCM msg 
                    // $senders_info = $this->users_model->get_user_by_id($this->session->userdata('user_id'));
                    $senders_info = fetch_details('users', ['active' => 1, 'id' => $this->session->userdata('user_id')]);

                    $data = $notification = array();
                    $notification['title'] = $senders_info[0]['username'];
                    // $notification['picture'] = mb_substr($senders_info[0]['first_name'], 0, 1) . '' . mb_substr($senders_info[0]['last_name'], 0, 1);

                    // $notification['profile'] = !empty($senders_info[0]['profile']) ? $senders_info[0]['profile'] : '';

                    $notification['senders_name'] = $senders_info[0]['username'];

                    $notification['type'] = 'message';
                    $notification['message_type'] = 'person';
                    $notification['from_id'] = $from_id;
                    $notification['to_id'] = $to_id;
                    $notification['msg_id'] = $msg_id;
                    $notification['new_msg'] = json_encode($new_msg);
                    $notification['body'] = $this->input->post('chat-input-textarea');
                    // $notification['icon'] = 'assets/icons/' . (!empty(get_half_logo()) ? get_half_logo() : 'logo-half.png');
                    $notification['base_url'] = base_url('chat');
                    $data['data']['data'] = $notification;
                    $data['data']['webpush']['fcm_options']['link'] = base_url('chat');
                    $data['to'] = $user[0]['web_fcm'];

                    $ch = curl_init();
                    $fcm_key = get_settings('fcm_server_key');


                    $fcm_key = !empty($fcm_key) ? $fcm_key : '';

                    // $fcm_key = !empty($fcm_key->fcm_server_key) ? $fcm_key->fcm_server_key : '';

                    curl_setopt($ch, CURLOPT_POST, 1);
                    $headers = array();
                    $headers[] = "Authorization: key = " . $fcm_key;
                    $headers[] = "Content-Type: application/json";
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                    $result['error'] = false;
                    $result['response'] = curl_exec($ch);
                    if (curl_errno($ch))
                        echo 'Error:' . curl_error($ch);

                    curl_close($ch);
                } else {
                    /*
                     * The block that used to live here was the "group chat" delivery path, and it
                     * was a guaranteed fatal error for anyone who reached it:
                     *
                     *   PHP Error - Call to undefined method Chat_model::get_group_members()
                     *
                     * Group chat was removed from this product - `chat_groups` /
                     * `chat_group_members` are not in the schema and both model methods this
                     * branch called (get_group_members(), set_group_msg_as_unread()) are
                     * commented out in Chat_model. But `chat_type` is posted by the client, so
                     * ANY value other than 'person'/'supporter' landed here: a one-line request
                     * with chat_type=group crashed the endpoint with an uncaught Error (verified
                     * live). Chat_model::send_msg() now normalises the type, so the message row
                     * itself is written correctly as a person message; there is simply no group
                     * fan-out left to do.
                     */
                    log_message('debug', 'send_msg: ignoring unsupported chat_type "' . $this->input->post('chat_type') . '" - group chat is not part of this schema.');
                }

                $response['error'] = false;
                $response['message'] = 'Successful';
                $response['msg_id'] = $msg_id;
                $response['new_msg'] = $new_msg;

                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    public function mark_msg_read()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {

            $type = $this->input->post('type');
            $to_id = $this->session->userdata('user_id');
            $from_id = $this->input->post('from_id');
            if ($this->chat_model->mark_msg_read($type, $from_id, $to_id)) {
                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }


    public function delete_msg()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $from_id = $this->session->userdata('user_id');
            $msg_id = $this->uri->segment(3);

            if (empty($msg_id) || !is_numeric($msg_id) || $msg_id < 1) {
                redirect('chat', 'refresh');
                return false;
                exit(0);
            }

            if ($this->chat_model->delete_msg($from_id, $msg_id)) {
                $response['error'] = false;
                $response['message'] = 'Successful';
                echo json_encode($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Not Successful';
                echo json_encode($response);
            }
        }
    }

    public function load_chat()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $user_id = $this->session->userdata('user_id');

            $type = $this->input->post('type');
            $to_id = $this->session->userdata('user_id');
            $from_id = $this->input->post('from_id');

            $offset = (!empty($_POST['offset'])) ? $this->input->post('offset') : 0;
            $limit = (!empty($_POST['limit'])) ? $this->input->post('limit') : 100;

            $sort = (!empty($_POST['sort'])) ? $this->input->post('sort') : 'id';
            $order = (!empty($_POST['order'])) ? $this->input->post('order') : 'DESC';

            $search = (!empty($_POST['search'])) ? $this->input->post('search') : '';

            $message = array();

            $messages = $this->chat_model->load_chat($from_id, $to_id, $type,  $offset, $limit, $sort, $order, $search);
            // print_r($from_id);
            // print_r($to_id);
            // print_r($user_id);
            // print_r($offset);
            // print_r($limit);
            // print_r($sort);
            // print_r($order);
            // print_r($search);
            // print_r($messages);
            if ($messages['total_msg'] == 0) {

                $message['error'] = true;
                $message['error_msg'] = 'No Chat OR Msg Found';
                print_r(json_encode($message));
                return false;
            }

            $i = 0;
            $message['total_msg'] = $messages['total_msg'];
            foreach ($messages['msg'] as $row) {
                $message['msg'][$i] = $row;
                $media_files = $this->chat_model->get_media($row['id']);
                $message['msg'][$i]['media_files'] = !empty($media_files) ? $media_files : '';
                $message['msg'][$i]['text'] = $row['message'];
                if ($row['from_id'] == $to_id) {
                    $message['msg'][$i]['position'] = 'right';
                } else {
                    $message['msg'][$i]['position'] = 'left';
                }
                $i++;
            }
            print_r(json_encode($message));
        }
    }

    public function switch_chat()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {
            $type = $this->input->post('type');
            $id = $this->input->post('from_id');
            $users = $this->chat_model->switch_chat($id, $type);
            // $grp_members = $this->chat_model->get_group_members($id);
            // print_r($type);
            // print_r($id);
            // print_r($users);
            // die;

            $user = array();
            $i = 0;
            foreach ($users as $row) {
                // print_r($row);

                $user[$i] = $row;
                if (($type == 'person') || ($type == 'supporter')) {
                    $user[$i]['picture'] = $row['username'];

                    $date = strtotime('now');

                    if ($row['last_online'] > $date) {
                        $user[$i]['is_online'] = 1;
                    } else {
                        $user[$i]['is_online'] = 0;
                    }
                }

                $i++;
            }
            // $user['grp_members'] = $grp_members;

            print_r(json_encode($user));
        }
    }

    public function send_fcm()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        } else {

            $to_id = $this->input->post('receiver_id');
            $from_id = $this->session->userdata('user_id');

            if ($to_id == $from_id) {
                return false;
            }

            $title = $this->input->post('title');
            $type = $this->input->post('type');
            $msg = $this->input->post('msg');
            // $user = $this->users_model->get_user_by_id($to_id);
            $user = fetch_details('users', ['active' => 1, 'id' => $to_id]);

            $message_type = !empty($this->input->post('message_type')) ? $this->input->post('message_type') : 'other';

            $data = $notification = array();
            $fcmFields = [];

            $fcmMsg = array(
                'content_available' => true,
                'title' => 'test',
                'body' => $msg,
                'type' => $type,
                "from_id" => $from_id,
                "to_id" => $to_id,
                "chat_type" => "person"
            );

            $fcmFields = array(
                'registration_ids' => [$user[0]['web_fcm']],  // expects an array of ids
                'priority' => 'high',
                'notification' => $fcmMsg,
                'data' => $fcmMsg,
            );

            $headers = array(
                'Authorization: key=' . get_settings('fcm_server_key'),
                'Content-Type: application/json'
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
            $result = curl_exec($ch);
            curl_close($ch);
            echo $result;


            print_r(json_encode($fcmFields));
        }
    }

    public function floating_chat_classic()
    {
        // floating_chat_modern() sets this; this method did not, so the theme's
        // include-css / include-script had no $main_page to build their per-page
        // asset paths from.
        $this->data['main_page'] = 'floating_chat';

        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if ($this->ion_auth->logged_in()) {
            $this->data['title'] = 'Transactions | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Transactions, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Transactions | ' . $this->data['web_settings']['meta_description'];

            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Update Notification Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Update Notification Settings  | ' . $settings['app_name'];
            $this->data['fcm_server_key'] = get_settings('fcm_server_key');
            // echo "<pre>";
            // print_r($_SESSION['user_id']);
            // die;    
            $user = array();
            $users = $this->chat_model->get_chat_history($_SESSION['user_id'], 10, 0);
            // print_R($users);
            // die;
            $i = 0;
            $type = 'person';
            $to_id = $this->session->userdata('user_id');

            foreach ($users as $row) {

                // Was $row['id'], which is the id of the newest MESSAGE in this conversation, not
                // the id of the person it is with - get_chat_history() aliases that as
                // `opponent_user_id`. Every unread badge on the chat list was therefore counted
                // against an unrelated user id (message #7 -> user #7), so the numbers shown were
                // arbitrary: usually 0, occasionally somebody else's unread total.
                $from_id = isset($row['opponent_user_id']) ? (int) $row['opponent_user_id'] : 0;

                // Also declared outside the loop, so a row with no opponent id reused the previous
                // row's count instead of showing zero.
                $unread_meg = 0;
                if ($from_id > 0) {
                    $unread_meg = $this->chat_model->get_unread_msg_count($type, $from_id, $to_id);
                }

                $user[$i] = $row;
                $user[$i]['unread_msg'] = $unread_meg;
                // Chat_model::get_chat_history() selects the other party's name as
                // `opponent_username` (u.username AS opponent_username) - there is no plain
                // `username` key in these rows, so this warned "Undefined array key
                // username" on every visit to the chat pages and assigned null.
                $user[$i]['picture'] = isset($row['opponent_username']) ? $row['opponent_username'] : '';

                $date = strtotime('now');
                // Same mix-up: comparing the viewer's user id against a message id.
                if ($to_id == $from_id) {
                    $user[$i]['is_online'] = 1;
                } else {
                    if ($row['last_online'] > $date) {
                        $user[$i]['is_online'] = 1;
                    } else {
                        $user[$i]['is_online'] = 0;
                    }
                }
                $i++;
            }
            // if ($this->ion_auth->is_admin()) {
            //     $this->data['not_in_groups'] = $this->chat_model->get_groups_all($to_id);
            // } else {
            //     $this->data['not_in_groups'] = '';
            // }

            // $this->data['groups'] = $this->chat_model->get_groups($to_id);
            $this->data['supporters'] = $this->chat_model->get_supporters($to_id);
            $this->data['users'] = $user;
            $this->load->view('front-end/' . THEME . '/pages/floating_chat', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }
    public function floating_chat_modern()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            /* redirect him to the page where he can enter the purchase code */
            redirect(base_url("maintenance"));
        }
        if (THEME == 'cretzo') {
            $settings = get_settings('system_settings', true);
            $this->data['main_page'] = 'floating_chat';
            $this->data['title'] = 'Chat | ' . $settings['app_name'];
            $this->data['meta_description'] = 'Chat | ' . $settings['app_name'];
            // The widget's "WhatsApp Support" button had the phone number hardcoded into the
            // view instead of reading the configured one, and the two had drifted apart - the
            // store's settings held two different numbers whose last digits were transposed.
            // The owner has since confirmed the correct number, and migration 052 normalises
            // every place it is stored. This reads the setting so it can never drift again, and
            // the view hides the button when WhatsApp is switched off.
            $this->data['whatsapp_status'] = !empty($settings['whatsapp_status']) ? $settings['whatsapp_status'] : 0;
            $this->data['whatsapp_number'] = !empty($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '';
            $this->data['support_email'] = !empty($settings['support_email']) ? $settings['support_email'] : '';
            $this->data['is_logged_in'] = $this->ion_auth->logged_in();
            $this->load->view('front-end/' . THEME . '/pages/floating_chat', $this->data);
            return;
        }
        
        if ($this->ion_auth->logged_in()) {
            $this->data['title'] = 'Transactions | ' . $this->data['web_settings']['site_title'];
            $this->data['keywords'] = 'Transactions, ' . $this->data['web_settings']['meta_keywords'];
            $this->data['description'] = 'Transactions | ' . $this->data['web_settings']['meta_description'];

            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Update Notification Settings | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Update Notification Settings  | ' . $settings['app_name'];
            $this->data['fcm_server_key'] = get_settings('fcm_server_key');
            // echo "<pre>";
            // print_r($_SESSION['user_id']);
            // die;    
            $user = array();
            $users = $this->chat_model->get_chat_history($_SESSION['user_id'], 10, 0);
            $i = 0;
            $type = 'person';
            $to_id = $this->session->userdata('user_id');

            foreach ($users as $row) {

                // Was $row['id'], which is the id of the newest MESSAGE in this conversation, not
                // the id of the person it is with - get_chat_history() aliases that as
                // `opponent_user_id`. Every unread badge on the chat list was therefore counted
                // against an unrelated user id (message #7 -> user #7), so the numbers shown were
                // arbitrary: usually 0, occasionally somebody else's unread total.
                $from_id = isset($row['opponent_user_id']) ? (int) $row['opponent_user_id'] : 0;

                // Also declared outside the loop, so a row with no opponent id reused the previous
                // row's count instead of showing zero.
                $unread_meg = 0;
                if ($from_id > 0) {
                    $unread_meg = $this->chat_model->get_unread_msg_count($type, $from_id, $to_id);
                }

                $user[$i] = $row;
                $user[$i]['unread_msg'] = $unread_meg;
                // Chat_model::get_chat_history() selects the other party's name as
                // `opponent_username` (u.username AS opponent_username) - there is no plain
                // `username` key in these rows, so this warned "Undefined array key
                // username" on every visit to the chat pages and assigned null.
                $user[$i]['picture'] = isset($row['opponent_username']) ? $row['opponent_username'] : '';

                $date = strtotime('now');
                // Same mix-up: comparing the viewer's user id against a message id.
                if ($to_id == $from_id) {
                    $user[$i]['is_online'] = 1;
                } else {
                    if ($row['last_online'] > $date) {
                        $user[$i]['is_online'] = 1;
                    } else {
                        $user[$i]['is_online'] = 0;
                    }
                }
                $i++;
            }
            // if ($this->ion_auth->is_admin()) {
            //     $this->data['not_in_groups'] = $this->chat_model->get_groups_all($to_id);
            // } else {
            //     $this->data['not_in_groups'] = '';
            // }

            // $this->data['groups'] = $this->chat_model->get_groups($to_id);
            $this->data['supporters'] = $this->chat_model->get_supporters($to_id);
            $this->data['users'] = $user;
            $this->load->view('front-end/' . THEME . '/pages/floating_chat', $this->data);
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function search_user()
    {
        // Had no auth check at all and spliced $_GET['search'] straight into a raw WHERE string
        // ("seller_user.username like '%" . $_GET['search'] . "%'"), so any anonymous visitor
        // could inject arbitrary SQL. It also SELECT *'d the joined users row, meaning the
        // response carried password hashes, salts and api keys for every matching seller.
        if (!$this->ion_auth->logged_in()) {
            show_404();
            return;
        }

        $search = trim((string) $this->input->get('search', true));
        if ($search === '') {
            echo json_encode([]);
            return;
        }

        $users = $this->db->select('seller_user.id, seller_user.username')
            ->from('seller_data')
            ->join('users as seller_user', 'seller_data.user_id = seller_user.id')
            ->like('seller_user.username', $search)
            ->limit(25)
            ->get()
            ->result_array();

        $data = array();
        foreach ($users as $user) {
            $data[] = array("id" => $user['id'], "text" => $user['username']);
        }
        echo json_encode($data);
    }

    /* ============================ customer support tickets ============================
     *
     * The support-ticket system existed only in the admin panel and the mobile API: there was
     * no way whatsoever for a customer to raise, read or reply to a ticket on the website.
     * The "Chat with us" page is a Coming Soon placeholder and the floating widget is a
     * scripted FAQ bot, so a web customer who needed a human had no channel at all besides
     * WhatsApp. These methods provide the missing half, reusing the same tables, statuses and
     * notification triggers the admin side already works with.
     */

    /** Human labels for tickets.status (PENDING..REOPEN). */
    private function ticket_status_labels()
    {
        return [
            PENDING  => ['label' => 'Pending',  'class' => 'secondary'],
            OPENED   => ['label' => 'Open',     'class' => 'info'],
            RESOLVED => ['label' => 'Resolved', 'class' => 'success'],
            CLOSED   => ['label' => 'Closed',   'class' => 'danger'],
            REOPEN   => ['label' => 'Reopened', 'class' => 'warning'],
        ];
    }

    /**
     * Loads a ticket only if it belongs to the logged-in customer. Every ticket endpoint below
     * goes through here so a customer can never read or write another customer's ticket by
     * changing the id in the request (the app API had exactly that hole).
     */
    private function get_own_ticket($ticket_id)
    {
        $ticket_id = (int) $ticket_id;
        $user_id = (int) $this->session->userdata('user_id');
        if ($ticket_id < 1 || $user_id < 1) {
            return null;
        }
        $ticket = $this->db->select('t.*, tty.title as ticket_type')
            ->join('ticket_types tty', 'tty.id = t.ticket_type_id', 'left')
            ->where('t.id', $ticket_id)
            ->where('t.user_id', $user_id)
            ->get('tickets t')
            ->row_array();
        return !empty($ticket) ? $ticket : null;
    }

    private function ticket_json($payload)
    {
        $payload['csrfName'] = $this->security->get_csrf_token_name();
        $payload['csrfHash'] = $this->security->get_csrf_hash();
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }

    public function support()
    {
        $web_doctor_brown = get_settings('web_doctor_brown', true);
        $system_settings = get_settings('system_settings', true);

        if ((!isset($web_doctor_brown) || empty($web_doctor_brown))) {
            redirect(base_url("admin/purchase-code"));
        }
        if ((isset($system_settings['is_web_under_maintenance']) && $system_settings['is_web_under_maintenance'] == 1)) {
            redirect(base_url("maintenance"));
        }
        if (!$this->data['is_logged_in']) {
            redirect(base_url('login'), 'refresh');
            return;
        }

        $this->data['main_page'] = 'support';
        $this->data['title'] = 'Support | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'Support, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Support | ' . $this->data['web_settings']['meta_description'];

        $this->data['ticket_types'] = $this->db->select('id, title')->order_by('title', 'ASC')->get('ticket_types')->result_array();
        $this->data['status_labels'] = $this->ticket_status_labels();
        $this->data['support_email'] = !empty($system_settings['support_email']) ? $system_settings['support_email'] : '';
        $this->data['open_ticket_id'] = (int) $this->input->get('ticket_id');
        $this->data['users'] = $this->data['user'];

        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    /** Server-side paginated list of the customer's own tickets (bootstrap-table format). */
    public function get_my_tickets()
    {
        if (!$this->data['is_logged_in']) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.', 'total' => 0, 'rows' => []]);
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $limit  = (is_numeric($this->input->get('limit')) && (int) $this->input->get('limit') > 0) ? min((int) $this->input->get('limit'), 100) : 10;
        $offset = (is_numeric($this->input->get('offset')) && (int) $this->input->get('offset') > 0) ? (int) $this->input->get('offset') : 0;
        $search = trim((string) $this->input->get('search', true));
        $status = $this->input->get('status', true);

        $labels = $this->ticket_status_labels();

        $count_builder = $this->db->where('t.user_id', $user_id);
        if ($search !== '') {
            $count_builder->group_start()->like('t.subject', $search)->or_like('t.description', $search)->group_end();
        }
        if (isset($labels[(string) $status])) {
            $count_builder->where('t.status', $status);
        }
        $total = $count_builder->count_all_results('tickets t');

        $this->db->select('t.*, tty.title as ticket_type')->join('ticket_types tty', 'tty.id = t.ticket_type_id', 'left')->where('t.user_id', $user_id);
        if ($search !== '') {
            $this->db->group_start()->like('t.subject', $search)->or_like('t.description', $search)->group_end();
        }
        if (isset($labels[(string) $status])) {
            $this->db->where('t.status', $status);
        }
        $tickets = $this->db->order_by('t.last_updated', 'DESC')->limit($limit, $offset)->get('tickets t')->result_array();

        $rows = [];
        foreach ($tickets as $ticket) {
            $key = (string) $ticket['status'];
            $replies = $this->db->where('ticket_id', $ticket['id'])->count_all_results('ticket_messages');
            // "unread" from the customer's point of view: staff replies newer than the last
            // time this customer opened the ticket. Tracked per-user in the session rather than
            // with a schema change, so it degrades to "0" rather than lying after a new login.
            $seen = (array) $this->session->userdata('ticket_seen');
            $last_seen = isset($seen[$ticket['id']]) ? (int) $seen[$ticket['id']] : 0;
            $unread = $this->db->where('ticket_id', $ticket['id'])
                ->where('user_type', 'admin')
                ->where('id >', $last_seen)
                ->count_all_results('ticket_messages');

            $rows[] = [
                'id'           => (int) $ticket['id'],
                'subject'      => html_escape((string) $ticket['subject']),
                'description'  => html_escape((string) $ticket['description']),
                'ticket_type'  => html_escape((string) $ticket['ticket_type']),
                'status'       => $key,
                'status_label' => isset($labels[$key]) ? $labels[$key]['label'] : 'Pending',
                'status_class' => isset($labels[$key]) ? $labels[$key]['class'] : 'secondary',
                'replies'      => (int) $replies,
                'unread'       => (int) $unread,
                'is_closed'    => in_array($key, [RESOLVED, CLOSED], true),
                'date_created' => $ticket['date_created'],
                'last_updated' => $ticket['last_updated'],
            ];
        }

        $this->ticket_json(['error' => false, 'total' => $total, 'rows' => $rows]);
    }

    public function create_ticket()
    {
        if (!$this->data['is_logged_in']) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in to raise a ticket.']);
            return;
        }

        $this->form_validation->set_rules('ticket_type_id', 'Category', 'trim|required|numeric|xss_clean');
        $this->form_validation->set_rules('subject', 'Subject', 'trim|required|min_length[4]|max_length[190]|xss_clean');
        $this->form_validation->set_rules('description', 'Description', 'trim|required|min_length[10]|max_length[5000]|xss_clean');

        if (!$this->form_validation->run()) {
            $this->ticket_json(['error' => true, 'message' => strip_tags(validation_errors())]);
            return;
        }

        $ticket_type_id = (int) $this->input->post('ticket_type_id', true);
        if ($this->db->where('id', $ticket_type_id)->count_all_results('ticket_types') < 1) {
            $this->ticket_json(['error' => true, 'message' => 'Please choose a valid category.']);
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $user = $this->db->select('email, username')->where('id', $user_id)->get('users')->row_array();

        // Rate limit: a stuck submit button (or an impatient customer) used to be able to create
        // an unbounded number of identical tickets, which is what buries a real one.
        $recent = $this->db->where('user_id', $user_id)
            ->where('date_created >', date('Y-m-d H:i:s', strtotime('-1 minute')))
            ->count_all_results('tickets');
        if ($recent >= 3) {
            $this->ticket_json(['error' => true, 'message' => 'You have raised several tickets just now. Please wait a moment before creating another.']);
            return;
        }

        $insert_id = $this->ticket_model->add_ticket([
            'ticket_type_id' => $ticket_type_id,
            'user_id'        => $user_id,
            'subject'        => $this->input->post('subject', true),
            'email'          => !empty($user['email']) ? $user['email'] : '',
            'description'    => $this->input->post('description', true),
            'status'         => PENDING,
        ]);

        if (empty($insert_id)) {
            $this->ticket_json(['error' => true, 'message' => 'Could not create the ticket. Please try again.']);
            return;
        }

        // Tells the admin bell a new ticket is waiting; previously nothing anywhere announced
        // a newly created ticket, so it sat unnoticed until someone opened the tickets page.
        notify_ticket_event($insert_id, 'created', 'user');

        $this->ticket_json([
            'error'     => false,
            'message'   => 'Ticket #' . $insert_id . ' created. Our team will reply here.',
            'ticket_id' => (int) $insert_id,
        ]);
    }

    /** Full conversation for one of the customer's own tickets. */
    public function get_ticket_thread()
    {
        if (!$this->data['is_logged_in']) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.', 'data' => []]);
            return;
        }

        $ticket = $this->get_own_ticket($this->input->get('ticket_id'));
        if ($ticket === null) {
            $this->ticket_json(['error' => true, 'message' => 'Ticket not found.', 'data' => []]);
            return;
        }

        $messages = $this->db->select('tm.id, tm.user_type, tm.user_id, tm.message, tm.attachments, tm.date_created, u.username')
            ->join('users u', 'u.id = tm.user_id', 'left')
            ->where('tm.ticket_id', (int) $ticket['id'])
            ->order_by('tm.id', 'ASC')
            ->get('ticket_messages tm')
            ->result_array();

        $types = $this->config->item('type');
        $rows = [];
        $last_id = 0;
        foreach ($messages as $row) {
            $last_id = max($last_id, (int) $row['id']);
            $attachments = [];
            if (!empty($row['attachments'])) {
                $decoded = json_decode($row['attachments'], true);
                // Tolerate both shapes on read: older rows stored a bare JSON string rather
                // than an array (see Ticket_model::add_ticket_message).
                if (is_string($decoded)) {
                    $decoded = [$decoded];
                }
                if (is_array($decoded)) {
                    foreach ($decoded as $file) {
                        if (!is_string($file) || trim($file) === '') {
                            continue;
                        }
                        $ext = strtolower((new SplFileInfo($file))->getExtension());
                        $kind = 'document';
                        foreach (['image', 'video', 'document', 'archive'] as $candidate) {
                            if (isset($types[$candidate]['types']) && in_array($ext, $types[$candidate]['types'], true)) {
                                $kind = $candidate;
                                break;
                            }
                        }
                        $attachments[] = ['url' => get_image_url($file), 'type' => $kind, 'name' => basename($file)];
                    }
                }
            }

            $rows[] = [
                'id'           => (int) $row['id'],
                'from_support' => ($row['user_type'] === 'admin'),
                'author'       => ($row['user_type'] === 'admin') ? 'Support Team' : html_escape((string) $row['username']),
                'message'      => html_escape((string) $row['message']),
                'attachments'  => $attachments,
                'date_created' => $row['date_created'],
            ];
        }

        // Mark the thread read for this customer so the list stops badging it.
        $seen = (array) $this->session->userdata('ticket_seen');
        $seen[(int) $ticket['id']] = $last_id;
        $this->session->set_userdata('ticket_seen', $seen);

        $labels = $this->ticket_status_labels();
        $key = (string) $ticket['status'];

        $this->ticket_json([
            'error'  => false,
            'ticket' => [
                'id'           => (int) $ticket['id'],
                'subject'      => html_escape((string) $ticket['subject']),
                'description'  => html_escape((string) $ticket['description']),
                'ticket_type'  => html_escape((string) $ticket['ticket_type']),
                'status'       => $key,
                'status_label' => isset($labels[$key]) ? $labels[$key]['label'] : 'Pending',
                'status_class' => isset($labels[$key]) ? $labels[$key]['class'] : 'secondary',
                'can_reply'    => ($key !== CLOSED),
                'can_close'    => !in_array($key, [RESOLVED, CLOSED], true),
                'can_reopen'   => in_array($key, [RESOLVED, CLOSED], true),
                'date_created' => $ticket['date_created'],
            ],
            'data' => $rows,
        ]);
    }

    public function reply_ticket()
    {
        if (!$this->data['is_logged_in']) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.']);
            return;
        }

        $ticket = $this->get_own_ticket($this->input->post('ticket_id'));
        if ($ticket === null) {
            $this->ticket_json(['error' => true, 'message' => 'Ticket not found.']);
            return;
        }
        if ((string) $ticket['status'] === CLOSED) {
            $this->ticket_json(['error' => true, 'message' => 'This ticket is closed. Please raise a new one.']);
            return;
        }

        $message = trim((string) $this->input->post('message', true));
        $attachments = $this->upload_ticket_attachments($error);
        if ($error !== '') {
            $this->ticket_json(['error' => true, 'message' => $error]);
            return;
        }
        if ($message === '' && empty($attachments)) {
            $this->ticket_json(['error' => true, 'message' => 'Please type a message or attach a file.']);
            return;
        }

        $insert_id = $this->ticket_model->add_ticket_message([
            'user_type'   => 'user',
            'user_id'     => (int) $this->session->userdata('user_id'),
            'ticket_id'   => (int) $ticket['id'],
            'message'     => $message,
            'attachments' => $attachments,
        ]);

        if (empty($insert_id)) {
            // Do not leave orphaned uploads behind if the row could not be written.
            foreach ($attachments as $file) {
                $path = FCPATH . ltrim($file, '/');
                if (is_file($path)) {
                    unlink($path);
                }
            }
            $this->ticket_json(['error' => true, 'message' => 'Could not send your reply. Please try again.']);
            return;
        }

        notify_ticket_event((int) $ticket['id'], 'message', 'user');

        $this->ticket_json(['error' => false, 'message' => 'Reply sent.', 'message_id' => (int) $insert_id]);
    }

    /**
     * Customer-side status change: they may mark their own ticket resolved, or reopen one that
     * was resolved/closed. Anything else stays an admin decision.
     */
    public function update_ticket_status()
    {
        if (!$this->data['is_logged_in']) {
            $this->ticket_json(['error' => true, 'message' => 'Please log in.']);
            return;
        }

        $ticket = $this->get_own_ticket($this->input->post('ticket_id'));
        if ($ticket === null) {
            $this->ticket_json(['error' => true, 'message' => 'Ticket not found.']);
            return;
        }

        $action = (string) $this->input->post('action', true);
        $current = (string) $ticket['status'];

        if ($action === 'resolve') {
            if (in_array($current, [RESOLVED, CLOSED], true)) {
                $this->ticket_json(['error' => true, 'message' => 'This ticket is already closed.']);
                return;
            }
            $new_status = RESOLVED;
        } elseif ($action === 'reopen') {
            if (!in_array($current, [RESOLVED, CLOSED], true)) {
                $this->ticket_json(['error' => true, 'message' => 'This ticket is still open.']);
                return;
            }
            $new_status = REOPEN;
        } else {
            $this->ticket_json(['error' => true, 'message' => 'Invalid request.']);
            return;
        }

        if (!update_details(['status' => $new_status], ['id' => (int) $ticket['id']], 'tickets')) {
            $this->ticket_json(['error' => true, 'message' => 'Could not update the ticket.']);
            return;
        }

        notify_ticket_event((int) $ticket['id'], 'status', 'user');

        $labels = $this->ticket_status_labels();
        $this->ticket_json([
            'error'        => false,
            'message'      => 'Ticket marked as ' . $labels[$new_status]['label'] . '.',
            'status'       => $new_status,
            'status_label' => $labels[$new_status]['label'],
        ]);
    }

    /**
     * Handles the optional attachments[] on a customer reply. Returns the stored relative paths
     * and sets $error when anything was rejected.
     */
    private function upload_ticket_attachments(&$error)
    {
        $error = '';
        $stored = [];

        if (empty($_FILES['attachments']['name'][0])) {
            return $stored;
        }

        $target = FCPATH . TICKET_IMG_PATH;
        if (!file_exists($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
            $error = 'Attachment storage is unavailable.';
            return $stored;
        }

        $count = count($_FILES['attachments']['name']);
        if ($count > 3) {
            $error = 'You can attach at most 3 files.';
            return $stored;
        }

        $this->load->library('upload');
        $config = [
            'upload_path'   => $target,
            // Deliberately narrower than allowed_media_types(): a support attachment has no
            // reason to accept archives or executables from an unauthenticated-adjacent form.
            'allowed_types' => 'jpg|jpeg|png|gif|webp|pdf|doc|docx|txt',
            'max_size'      => 5120,
            'encrypt_name'  => TRUE,
        ];
        $this->upload->initialize($config);

        $files = $_FILES;
        for ($i = 0; $i < $count; $i++) {
            if (empty($files['attachments']['name'][$i])) {
                continue;
            }
            $_FILES['ticket_attachment'] = [
                'name'     => $files['attachments']['name'][$i],
                'type'     => $files['attachments']['type'][$i],
                'tmp_name' => $files['attachments']['tmp_name'][$i],
                'error'    => $files['attachments']['error'][$i],
                'size'     => $files['attachments']['size'][$i],
            ];
            if (!$this->upload->do_upload('ticket_attachment')) {
                $error = strip_tags($this->upload->display_errors('', ''));
                // Roll back whatever already landed so a partially-failed upload does not leave
                // half the attachments referenced and half orphaned.
                foreach ($stored as $done) {
                    $path = FCPATH . ltrim($done, '/');
                    if (is_file($path)) {
                        unlink($path);
                    }
                }
                return [];
            }
            $data = $this->upload->data();
            $stored[] = TICKET_IMG_PATH . $data['file_name'];
        }

        return $stored;
    }

    // =================================== end code for chat ==========================================

}
