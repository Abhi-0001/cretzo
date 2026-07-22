<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Search extends CI_Controller
{

    public function __construct()   
    {
        parent::__construct();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['cart_model', 'category_model', 'rating_model', 'Home_model', 'product_model', 'brand_model', 'product_faqs_model']);
        $this->load->library(['pagination']);
        $this->data['settings'] = get_settings('system_settings', true);
        $this->data['web_settings'] = get_settings('web_settings', true);
        $this->data['is_logged_in'] = ($this->ion_auth->logged_in()) ? 1 : 0;
        $this->data['user'] = ($this->ion_auth->logged_in()) ? $this->ion_auth->user()->row() : array();
        $this->response['csrfName'] = $this->security->get_csrf_token_name();
        $this->response['csrfHash'] = $this->security->get_csrf_hash();
    }

    /**
     * Search products by name and return results in JSON format
     *
     * @param string $search (optional) The search term to look for in product names
     * @param int $limit (optional) Maximum number of results to return (default: 10)
     * @return json JSON response containing array of products with id and name
     */
    public function search_data()
    {
        // Always return JSON
        $this->output->set_content_type('application/json');

        // Read search parameter
        $search_term = trim($this->input->get_post('search', true));
        $limit       = $this->input->get_post('limit', true) ?: 10;

        // Validate input
        if (empty($search_term)) {
            return $this->output->set_output(json_encode([
                            'csrfName' => $this->security->get_csrf_token_name(),
                            'csrfHash' => $this->security->get_csrf_hash(),
                            'error'    => true,
                            'message'  => 'Search term is required',
                            'data'     => []
                        ]));
                    }

    // Load model
        $this->load->model('product_model');

    // Get product search results
    $results = $this->product_model->search_products_by_name($search_term, $limit);

    // Send response
    return $this->output->set_output(json_encode([
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'error'    => false,
                'message'  => 'Search results retrieved successfully',
                'data'     => $results
            ]));
}


}