<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cron_job extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'upload']);
        $this->load->helper(['url', 'language', 'file']);
        $this->load->model(['Seller_model', 'Promo_code_model']);
    }

    public function settle_seller_commission()
    {
        // This controller had no authentication check on any method - confirmed live with no
        // session cookie at all: the request succeeded and returned a normal application
        // response rather than being rejected. Both endpoints here move real money (crediting
        // seller commissions / settling promo code discounts) and are triggered from an
        // authenticated admin button (the "Settle Promo Code Discount" button on the Orders
        // page), so - as with every other admin controller in this codebase - they should only
        // ever run for a logged-in administrator.
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        $this->form_validation->set_data($this->input->get());
        $this->form_validation->set_rules('is_date', 'is_date', 'trim|required|xss_clean');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = strip_tags(validation_errors());
            $this->response['data'] = array();
            print_r(json_encode($this->response));
        } else {
            $is_date = (isset($_GET['is_date']) && is_numeric($_GET['is_date']) && !empty(trim($_GET['is_date']))) ? $this->input->get('is_date') : false;
            return $this->Seller_model->settle_seller_commission($is_date);
        }
    }
    // Expiry is otherwise only evaluated lazily at read time (get_active_subscription()
    // checks end_date on every read, so nothing depends on is_active being accurate
    // between reads) - this endpoint exists only so `is_active` itself doesn't go
    // stale indefinitely, and so a future report/notification pass has an accurate
    // "expired today" signal to work from. Token-protected rather than login-gated
    // (like every other method in this controller) since an external OS cron can't
    // hold an admin session - set application/config/cron.php's `secret` before
    // wiring this into an actual scheduled job.
    public function expire_seller_subscriptions($token = null)
    {
        $this->config->load('cron', true);
        $expected = $this->config->item('secret', 'cron');
        $token = $token !== null ? $token : $this->input->get('token');

        if (empty($expected) || $expected === 'change-me-before-use' || empty($token) || !hash_equals((string) $expected, (string) $token)) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        $affected = $this->db
            ->set('is_active', 0)
            ->where('is_active', 1)
            ->where('end_date IS NOT NULL', null, false)
            ->where('end_date <', date('Y-m-d H:i:s'))
            ->update('seller_subscriptions');

        $this->response['error'] = false;
        $this->response['message'] = 'Expired subscriptions flagged.';
        $this->response['data'] = ['affected_rows' => $this->db->affected_rows()];
        echo json_encode($this->response);
        return false;
    }

    public function settle_cashback_discount()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->response['error'] = true;
            $this->response['message'] = 'Unauthorized';
            echo json_encode($this->response);
            return false;
        }

        return $this->Promo_code_model->settle_cashback_discount();
    }

    // reset_system_data() and reset_system_media() were REMOVED here. They were
    // demo-reset scaffolding inherited from the upstream eShop vendor package, and
    // both were reachable with NO authentication at all (unlike every other method
    // in this controller):
    //   - reset_system_data() opened a hardcoded mysqli('localhost','root','','eshop_vendor')
    //     connection and multi_query()'d an eshop_vendor.sql fetched over HTTP - i.e. a
    //     full database wipe/reimport, pointed at a database this install does not use.
    //   - reset_system_media() ran delete_files(FCPATH.'uploads/media', true) - deleting
    //     EVERY uploaded product/seller image - and then tried to restore them from an
    //     uploads/media.zip that does not exist in this repo, so the deletion was
    //     unrecoverable.
    // Neither was referenced by any controller, view, JS file or route (verified by
    // grep across application/ and assets/), and neither's required file
    // (eshop_vendor.sql, uploads/media.zip) exists here. Deleting them outright is
    // safer than adding an auth gate, because even an authenticated call would
    // irreversibly destroy all media on this install.
}
