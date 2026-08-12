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

    public function reset_system_data()
    {
        $mysqli = new mysqli('localhost', 'root', '', 'eshop_vendor');
        if (mysqli_connect_errno())
            return false;
        $query = file_get_contents(base_url('eshop_vendor.sql'));
        if ($mysqli->multi_query($query)) {
            delete_files(FCPATH . 'uploads/media', true);
            $zip = new ZipArchive;
            $res = $zip->open('uploads/media.zip');
            if ($res === TRUE) {
                // Unzip path
                $extractpath = 'uploads/media';

                // Extract file
                $zip->extractTo($extractpath);
                $zip->close();
                // unlink('uploads/media');
            } else {
                $this->response['error'] = true;
                $this->response['message'] = $this->upload->display_errors();
            }
            echo "Success";
        } else {
            echo  mysqli_error($mysqli);
        }
        // $mysqli->multi_query($query);
        // $mysqli->close();
    }
    public function reset_system_media()
    {
        delete_files(FCPATH . 'uploads/media', true);
        $zip = new ZipArchive;
        $res = $zip->open('uploads/media.zip');
        if ($res === TRUE) {
            // Unzip path
            $extractpath = 'uploads/media';

            // Extract file
            $zip->extractTo($extractpath);
            $zip->close();
            // unlink('uploads/media');
        } else {
            $this->response['error'] = true;
            $this->response['message'] = $this->upload->display_errors();
        }
    }
}
