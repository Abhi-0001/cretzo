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
    }

    public function manage_pickup_locations()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {

            $this->data['main_page'] = TABLES . 'manage-pickup_location';
            $settings = get_settings('system_settings', true);
            $this->data['title'] = 'Pickup location Management | ' . $settings['app_name'];
            $this->data['meta_description'] = ' Pickup location Management  | ' . $settings['app_name'];
            if (isset($_GET['edit_id'])) {
                // Scoped to this seller — without this, any seller could read another
                // seller's warehouse address, contact name, email, phone and coordinates
                // just by changing ?edit_id= in the URL.
                $this->data['fetched_data'] = fetch_details('pickup_locations', ['id' => $_GET['edit_id'], 'seller_id' => $this->session->userdata('user_id')]);
            }
            $this->load->view('seller/template', $this->data);
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function add_pickup_location()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {



            $this->form_validation->set_rules('pickup_location', ' Pickup Location ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('name', ' Name ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('email', ' Email ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('phone', ' Phone ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('city', ' City ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('state', ' State ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('country', ' Country ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('pincode', ' Pincode ', 'trim|required|xss_clean');
            $this->form_validation->set_rules('address', ' Address ', 'trim|required|xss_clean');
            // Optional, matching the form (these three carry no required marker) and the
            // admin-side controller. They were `required` here, so a seller who filled in
            // every starred field still got "The Address 2 field is required." — and there
            // is no way for a seller to supply latitude/longitude from this form at all.
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
                $_POST['seller_id'] = $this->session->userdata('user_id');
                // seller_id must come from the session. It was taken from $_POST, so a seller
                // could file a pickup location under another seller's account.
                $_POST['seller_id'] = $this->ion_auth->get_user_id();

                $result = $this->Pickup_location_model->add_pickup_location($_POST);

                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();

                if (!empty($result['error'])) {
                    // The Shiprocket registration result was previously thrown away and this
                    // always reported success, so a pickup address the courier had rejected
                    // looked fine here and only failed much later, at shipment booking.
                    $this->response['error'] = true;
                    $this->response['message'] = $result['message'];
                    print_r(json_encode($this->response));
                    return;
                }

                $this->response['error'] = false;
                if (isset($_POST['edit_pickup_location'])) {
                    $this->response['message'] = 'Update Pickup Location';
                } else {
                    /*
                     * pickup_locations.status defaults to 0 and nothing sets it on insert, while
                     * the product form only offers pickup locations with status = 1
                     * (seller/Product.php). So a newly added pickup location does not appear when
                     * the seller goes to use it - and the old flat "Add Pickup Location" message
                     * gave no hint why. Admin activation is the intended gate (the admin list has
                     * an activate/deactivate control), so say so rather than changing it.
                     */
                    $this->response['message'] = 'Pickup location added. It becomes selectable on your products once an admin activates it.';
                }
                print_r(json_encode($this->response));
            }
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function view_pickup_location()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            return $this->Pickup_location_model->get_list($table = 'pickup_locations', NULL, $this->session->userdata('user_id'));
        } else {
            redirect('seller/login', 'refresh');
        }
    }

    public function delete_pickup_location()
    {
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0)) {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                $this->response['error'] = true;
                $this->response['message'] = 'Invalid request.';
                print_r(json_encode($this->response));
                return;
            }

            $result = $this->Pickup_location_model->delete_pickup_location($id, $this->ion_auth->get_user_id());
            $this->response['error'] = $result['error'];
            $this->response['message'] = $result['message'];
            print_r(json_encode($this->response));
        } else {
            redirect('seller/login', 'refresh');
        }
    }
}
