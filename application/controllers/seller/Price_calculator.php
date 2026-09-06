<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller > Price Calculator.
 *
 * "What should I charge, and what will I actually be paid?" - answered from the same
 * deduction ladder that pays the seller, so the estimate on this screen and the figure that
 * lands in their wallet cannot disagree.
 *
 * The page renders with a worked result already on it (from the seller's own plan and their
 * first available GST band) rather than an empty form, because an empty calculator explains
 * nothing about how the deductions work.
 */
class Price_calculator extends CI_Controller
{
    public $data = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language']);
        $this->load->model(['Price_calculator_model', 'Seller_subscription_model']);
    }

    private function guard()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_seller() || !$this->ion_auth->can_access_seller_panel()) {
            return false;
        }
        return true;
    }

    public function index()
    {
        if (!$this->guard()) {
            redirect('seller/login', 'refresh');
            return;
        }

        $seller_id = $this->session->userdata('user_id');
        $settings = get_settings('system_settings', true);

        $this->data['main_page'] = VIEW . 'price-calculator';
        $this->data['title'] = 'Price Calculator | ' . $settings['app_name'];
        $this->data['meta_description'] = 'Work out what to charge and what you will be paid | ' . $settings['app_name'];

        $this->data['tax_bands'] = $this->Price_calculator_model->tax_bands();
        $this->data['plans'] = $this->Price_calculator_model->plans();
        $this->data['current_plan'] = $this->Seller_subscription_model->get_current_plan($seller_id);
        // null when this seller has no recovered freight on record, which is the normal state
        // wherever Shiprocket booking has not been capturing it. The view shows no hint at all
        // in that case rather than inventing an average.
        $this->data['median_shipping'] = $this->Price_calculator_model->median_shipping($seller_id);
        $this->data['currency'] = get_settings('currency');

        $this->load->view('seller/template', $this->data);
    }

    /**
     * Price one unit and return the full ladder as JSON.
     *
     * Every branch ends in exactly one echo. Several endpoints in this codebase build a
     * validation-failure response and then never send it, which leaves the browser waiting on
     * an empty body and the UI stuck on "Calculating" forever.
     */
    public function calculate()
    {
        if (!$this->guard()) {
            print_r(json_encode([
                'error'   => true,
                'message' => 'Your session has ended. Sign in again to keep using the calculator.',
            ]));
            return;
        }

        $this->form_validation->set_rules('tax_id', 'GST rate', 'trim|required|numeric|xss_clean');
        $this->form_validation->set_rules('product_cost', 'Product cost', 'trim|required|numeric|greater_than_equal_to[0]|xss_clean');
        $this->form_validation->set_rules('shipping', 'Shipping cost', 'trim|numeric|greater_than_equal_to[0]|xss_clean');
        $this->form_validation->set_rules('target_margin', 'Target margin', 'trim|required|numeric|greater_than_equal_to[0]|less_than[100]|xss_clean');
        $this->form_validation->set_rules('plan_id', 'Subscription plan', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('selling_price', 'Selling price', 'trim|numeric|greater_than_equal_to[0]|xss_clean');

        if (!$this->form_validation->run()) {
            print_r(json_encode([
                'error'     => true,
                'message'   => validation_errors(),
                'csrfName'  => $this->security->get_csrf_token_name(),
                'csrfHash'  => $this->security->get_csrf_hash(),
            ]));
            return;
        }

        $result = $this->Price_calculator_model->quote([
            'seller_id'         => $this->session->userdata('user_id'),
            'tax_id'            => $this->input->post('tax_id', true),
            'plan_id'           => $this->input->post('plan_id', true),
            'product_cost'      => $this->input->post('product_cost', true),
            'cost_includes_gst' => ($this->input->post('cost_includes_gst', true) == '1'),
            'shipping'          => $this->input->post('shipping', true),
            'target_margin'     => $this->input->post('target_margin', true),
            'selling_price'     => $this->input->post('selling_price', true),
        ]);

        $result['csrfName'] = $this->security->get_csrf_token_name();
        $result['csrfHash'] = $this->security->get_csrf_hash();

        print_r(json_encode($result));
    }
}
