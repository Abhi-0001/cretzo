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
        $this->output->set_content_type('application/json');

        $search_term = trim($this->input->get_post('search', true));
        $limit = (int) $this->input->get_post('limit', true);
        if ($limit <= 0) {
            $limit = 12;
        }
        if ($limit > 30) {
            $limit = 30;
        }

        if (empty($search_term)) {
            return $this->output->set_output(json_encode([
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'error'    => false,
                'message'  => 'Search term is required',
                'data'     => []
            ]));
        }

        $results = [];

        // Categories (all levels)
        $categories = $this->db->select('id, name, slug, parent_id')
            ->from('categories')
            ->like('name', $search_term)
            ->where('status', 1)
            ->order_by('name', 'ASC')
            ->limit($limit)
            ->get()
            ->result_array();

        foreach ($categories as $cat) {
            $type = 'category';
            if (!empty($cat['parent_id']) && (int)$cat['parent_id'] > 0) {
                $parent = $this->db->select('id, parent_id')
                    ->from('categories')
                    ->where('id', (int)$cat['parent_id'])
                    ->where('status', 1)
                    ->get()
                    ->row_array();

                if (!empty($parent) && !empty($parent['parent_id']) && (int)$parent['parent_id'] > 0) {
                    $type = 'child_category';
                } else {
                    $type = 'sub_category';
                }
            }

            $results[] = [
                'id' => 'c-' . $cat['id'],
                'type' => $type,
                'name' => $cat['name'],
                'meta' => ucwords(str_replace('_', ' ', $type)),
                'url' => base_url('products/category/' . $cat['slug'])
            ];
        }

        // Products
        $products = $this->db->select('p.id, p.name, p.slug, c.name as category_name')
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->like('p.name', $search_term)
            ->where('p.status', 1)
            ->where('c.status', 1)
            ->order_by('p.name', 'ASC')
            ->limit($limit)
            ->get()
            ->result_array();

        foreach ($products as $row) {
            $results[] = [
                'id' => 'p-' . $row['id'],
                'type' => 'product',
                'name' => $row['name'],
                'meta' => '',
                'url' => base_url('products/details/' . $row['slug'])
            ];
        }

        // Brands
        $brands = $this->db->select('id, name, slug')
            ->from('brands')
            ->like('name', $search_term)
            ->where('status', 1)
            ->order_by('name', 'ASC')
            ->limit($limit)
            ->get()
            ->result_array();

        foreach ($brands as $brand) {
            $results[] = [
                'id' => 'b-' . $brand['id'],
                'type' => 'brand',
                'name' => $brand['name'],
                'meta' => '',
                'url' => base_url('products/brand/' . $brand['slug'])
            ];
        }

        // Deduplicate and cap while preserving priority order:
        // categories/sub-categories/child-categories first, then products.
        $unique = [];
        $final = [];
        foreach ($results as $row) {
            if (!isset($unique[$row['id']])) {
                $unique[$row['id']] = true;
                $final[] = $row;
            }
            if (count($final) >= $limit) {
                break;
            }
        }

        return $this->output->set_output(json_encode([
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'error'    => false,
                'message'  => 'Search results retrieved successfully',
                'data'     => $final
            ]));
    }


}