<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pickup_location extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper', 'file']);
        $this->load->model('Pickup_location_model');
        if (!has_permissions('read', 'pickup_location')) {
            $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
            redirect('admin/home', 'refresh');
        } else {
            $this->session->set_flashdata('authorize_flag', "");
        }
    }

    public function manage_pickup_locations()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (!has_permissions('read', 'pickup_location')) {
                $this->session->set_flashdata('authorize_flag', PERMISSION_ERROR_MSG);
                redirect('admin/home', 'refresh');
            }
            $this->data['main_page'] = TABLES . 'manage-pickup_location';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Pickup location Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Pickup location Management  | ' . $settings['app_name'];
            $this->data['sellers'] = $this->db->select(' u.username as seller_name,u.id as seller_id,sd.category_ids,COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name,sd.id as seller_data_id  ')
                ->join('users_groups ug', ' ug.user_id = u.id ')
                ->join('seller_data sd', ' sd.user_id = u.id ')
                ->where(['ug.group_id' => '4'])
                ->get('users u')->result_array();
            if (isset($_GET['edit_id'])) {
                $this->data['fetched_data'] = fetch_details('pickup_locations', ['id' => $_GET['edit_id']]);
            }
            $this->load->view('admin/template', $this->data);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function add_pickup_location()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {

            if (isset($_POST['edit_pickup_location'])) {
                if (print_msg(!has_permissions('update', 'pickup_location'), PERMISSION_ERROR_MSG, 'pickup_location')) {
                    return false;
                }
            } else {
                if (print_msg(!has_permissions('create', 'pickup_location'), PERMISSION_ERROR_MSG, 'pickup_location')) {
                    return false;
                }
            }

            $this->form_validation->set_rules('pickup_location', ' Pickup Location ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('name', ' Name ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('email', ' Email ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('phone', ' Phone ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('city', ' City ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('state', ' State ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('country', ' Country ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('pincode', ' Pincode ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('address', ' Address ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('address2', ' Address 2 ', 'trim|xss_clean');
            $this->form_validation->set_rules('latitude', ' Latitude ', 'trim|numeric|xss_clean');
            $this->form_validation->set_rules('longitude', ' Longitude ', 'trim|numeric|xss_clean');

            if (!$this->form_validation->run()) {

                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = validation_errors();
                print_r(json_encode($this->response));
            } else {
                $result = $this->Pickup_location_model->add_pickup_location($_POST);

                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();

                // The Shiprocket registration result was discarded and this always reported
                // success, so a pickup address the courier had rejected looked fine here and only
                // failed later when a shipment was booked from it.
                if (!empty($result['error'])) {
                    $this->response['error'] = true;
                    $this->response['message'] = $result['message'];
                    print_r(json_encode($this->response));
                    return;
                }

                $this->response['error'] = false;
                // pickup_locations.status defaults to 0 and nothing sets it on insert, while the
                // product form only lists pickup locations with status = 1 - so even an
                // admin-created one is not selectable until it is activated from this list.
                $this->response['message'] = (isset($_POST['edit_pickup_location']))
                    ? 'Update Pickup Location'
                    : 'Pickup location added. Activate it in the list to make it selectable on products.';
                print_r(json_encode($this->response));
            }
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function view_pickup_location()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            return $this->Pickup_location_model->get_list($table = 'pickup_locations');
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_seller_pickup_location()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            // seller_id was read straight from the query string with no guard, so opening this
            // without one warned and then passed NULL into the model as a filter.
            $seller_id = (isset($_GET['seller_id']) && is_numeric($_GET['seller_id'])) ? (int) $_GET['seller_id'] : null;
            return $this->Pickup_location_model->get_list('pickup_locations', NULL, $seller_id);
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    /**
     * Pulls the pickup addresses registered on the Shiprocket account into `pickup_locations`.
     *
     * These addresses already exist on Shiprocket's side - the account cannot ship without them.
     * Until now the only way to get them into this platform was to retype each one, and a single
     * mistyped pincode here is invisible: it surfaces as a customer being told their address is
     * not serviceable, with nothing pointing at the real cause. Taking them from Shiprocket
     * removes the retyping and the class of error that comes with it.
     *
     * Matching is on (seller_id, pickup_location) - the nickname is Shiprocket's own key for an
     * address within an account - so re-running this updates the rows it already created rather
     * than duplicating them, and it never deletes anything a human entered.
     *
     * A seller_id must be supplied because this platform scopes pickup addresses per seller while
     * a Shiprocket account holds one flat list; the admin says which seller the account's
     * addresses belong to.
     */
    public function import_from_shiprocket()
    {
        if (!($this->ion_auth->logged_in() && $this->ion_auth->is_admin())) {
            redirect('admin/login', 'refresh');
            return;
        }
        if (print_msg(!has_permissions('create', 'pickup_location'), PERMISSION_ERROR_MSG, 'pickup_location')) {
            return false;
        }

        $this->form_validation->set_rules('seller_id', 'Seller', 'trim|required|numeric|xss_clean');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = validation_errors();
            print_r(json_encode($this->response));
            return false;
        }

        $seller_id = (int) $this->input->post('seller_id', true);

        $shipping_settings = get_settings('shipping_method', true);
        if (empty($shipping_settings['email']) || empty($shipping_settings['password'])) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $this->response['message'] = 'Shiprocket credentials are not configured. Set them in '
                . 'Settings > Shipping Methods first.';
            print_r(json_encode($this->response));
            return false;
        }

        $this->load->library('shiprocket');
        $result = $this->shiprocket->get_pickup_locations();

        // curl() returns a non-array on a transport failure, and last_error() carries the real
        // reason - report it rather than a generic failure the admin cannot act on.
        if (!is_array($result) || !isset($result['data']['shipping_address'])) {
            $this->response['error'] = true;
            $this->response['csrfName'] = $this->security->get_csrf_token_name();
            $this->response['csrfHash'] = $this->security->get_csrf_hash();
            $detail = method_exists($this->shiprocket, 'last_error') ? (string) $this->shiprocket->last_error() : '';
            $this->response['message'] = 'Could not read pickup locations from Shiprocket.'
                . ($detail !== '' ? ' ' . $detail : ' Check the credentials in Settings > Shipping Methods.');
            print_r(json_encode($this->response));
            return false;
        }

        $addresses = $result['data']['shipping_address'];
        $added = $updated = $skipped = 0;

        foreach ($addresses as $address) {
            $nickname = isset($address['pickup_location']) ? trim((string) $address['pickup_location']) : '';
            $pin      = isset($address['pin_code']) ? trim((string) $address['pin_code']) : '';

            // Both are what everything downstream keys on. An address missing either cannot be
            // used to quote or book, so importing it would just move the problem.
            if ($nickname === '' || $pin === '') {
                $skipped++;
                continue;
            }

            $row = [
                'seller_id'       => $seller_id,
                'pickup_location' => $nickname,
                'name'            => isset($address['name']) ? $address['name'] : '',
                'email'           => isset($address['email']) ? $address['email'] : '',
                'phone'           => isset($address['phone']) ? $address['phone'] : '',
                'address'         => isset($address['address']) ? $address['address'] : '',
                'address_2'       => isset($address['address_2']) ? $address['address_2'] : '',
                'city'            => isset($address['city']) ? $address['city'] : '',
                'state'           => isset($address['state']) ? $address['state'] : '',
                'country'         => isset($address['country']) ? $address['country'] : 'India',
                'pin_code'        => $pin,
                // pickup_locations.status defaults to 0 and the storefront treats 0 as inactive,
                // so an imported address has to be marked active or it would be imported and then
                // ignored. Shiprocket's own status wins when it sends one.
                'status'          => (isset($address['status']) && $address['status'] == 0) ? 0 : 1,
                // Proof that Shiprocket holds this address. Booking is rejected outright for any
                // nickname it does not have ("Wrong Pickup location entered"), and nothing used to
                // record which of our rows it had actually confirmed.
                'shiprocket_verified_at' => date('Y-m-d H:i:s'),
                // Shiprocket will not schedule a pickup from an address whose phone is unverified.
                'phone_verified'  => (isset($address['phone_verified']) && $address['phone_verified'] == 1) ? 1 : 0,
            ];

            $existing = $this->db->select('id')
                ->where('seller_id', $seller_id)
                ->where('pickup_location', $nickname)
                ->get('pickup_locations')->row_array();

            if (!empty($existing)) {
                $this->db->where('id', $existing['id'])->update('pickup_locations', $row);
                $updated++;
            } else {
                $this->db->insert('pickup_locations', $row);
                $added++;
            }
        }

        $this->response['error'] = false;
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
        $this->response['message'] = 'Imported from Shiprocket: ' . $added . ' added, ' . $updated
            . ' updated' . ($skipped > 0 ? ', ' . $skipped . ' skipped (no nickname or pincode)' : '') . '.';
        $this->response['data'] = ['added' => $added, 'updated' => $updated, 'skipped' => $skipped];
        print_r(json_encode($this->response));
    }
}
