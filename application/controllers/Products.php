<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Products extends CI_Controller
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
        $this->form_validation->set_data($this->input->get(null, true));
        $this->form_validation->set_rules('category', 'Category', 'trim|xss_clean');
        $this->form_validation->set_rules('per-page', 'Per Page', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('sort', 'Sort', 'trim|xss_clean');
        $this->form_validation->set_rules('min-price', 'Min Price', 'trim|xss_clean');
        $this->form_validation->set_rules('max-price', 'Max Price', 'trim|xss_clean');

        if (!empty($_GET) && !$this->form_validation->run()) {
            redirect(base_url('products'));
        }


        $attribute_values = '';
        $attribute_names = '';
        foreach ($this->input->get(null, true) as $key => $value) {
            if (strpos($key, 'filter-') !== false) {
                if (!empty($attribute_values)) {
                    $attribute_values .= "|" . $this->input->get($key, true);
                } else {
                    $attribute_values = $this->input->get($key, true);
                }

                $key = str_replace('filter-', '', $key);
                if (!empty($attribute_names)) {
                    $attribute_names .= "|" . $key;
                } else {
                    $attribute_names = $key;
                }
            }
        }

        //get attributes ids
        $attribute_values = explode('|', $attribute_values);
        $attribute_names = explode('|', $attribute_names);
        $filter['attribute_value_ids'] = get_attribute_ids_by_value($attribute_values, $attribute_names);
        $filter['attribute_value_ids'] = implode(',', $filter['attribute_value_ids']);

        $category_param = $this->input->get('category', true);
        $category_id = null;
        if (!empty($category_param)) {
            $category_ids = [];
            $category_values = array_filter(array_map('trim', explode('|', $category_param)));
            foreach ($category_values as $category_value) {
                if (ctype_digit((string)$category_value)) {
                    $category_ids[] = (int)$category_value;
                } else {
                    $category_details = fetch_details('categories', ['slug' => urldecode($category_value)], 'id');
                    if (!empty($category_details) && isset($category_details[0]['id'])) {
                        $category_ids[] = (int)$category_details[0]['id'];
                    }
                }
            }
            $category_ids = array_values(array_unique($category_ids));
            $category_id = !empty($category_ids) ? $category_ids : null;
        }


        /* filter within price range */
        $min_price = ($this->input->get('min-price')) ? $this->input->get('min-price') : null;
        $max_price = ($this->input->get('max-price')) ? $this->input->get('max-price') : null;
        $filter['min_price'] = $min_price;
        $filter['max_price'] = $max_price;


        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $sort_by = ($this->input->get('sort')) ? $this->input->get('sort', true) : '';
        $seller_slug = (isset($_GET['seller']) && !empty($_GET['seller']) && $_GET['seller'] != "") ? $this->input->get('seller', true) : '';
        $seller_id = $seller = "";
        if (!empty($seller_slug)) {
            $seller = fetch_details("seller_data", ['slug' => $seller_slug], "user_id,store_name");
            $seller_id = (!empty($seller) && isset($seller[0]['user_id'])) ? $seller[0]['user_id'] : "";
        }

        $brand_slug = (isset($_GET['brand']) && !empty($_GET['brand']) && $_GET['brand'] != "") ? $this->input->get('brand', true) : '';
        /* if (!empty($brand_slug)) {
            $brand = fetch_details('brands', ['slug' => $brand_slug]);
            $filter['brand'] =  $brand[0]['name'];
        } */

        /* We have instead added filtering for multiple brands at once */
        if (!empty($brand_slug)) {
            $filter['brands'] = $brand_slug;
        }

        $offset = 0;
        $sort = 'row_order';
        $order = 'ASC';
        $brands = $this->brand_model->get_brands('', $limit = NULL, $offset, $sort, $order, 'false');

        $products = fetch_product(null, $filter, null, $category_id, $limit = NULL, $offset, $sort, $order);
        // echo "<pre>";
        // print_r($brands);
        // die;


        // $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        // $brands = array();
        // for ($i = 0; $i < count($products['product']); $i++) {
        //     if (!empty($products['product'][$i]['brand'])) {
        //         // echo "<pre>";
        //         $brand = explode(',', $products['product'][$i]['brand']);
        //         $brands = array_values(array_unique(array_merge($brands, $brand)));
        //     }
        // }

        $category = $this->category_model->get_categories(
            '',
            null,
            $offset = 0,
            $sort = null,
            $order = null,
            $has_child_or_item = 'true',
            '',
            '',
            $seller_id,
            $ignore_categories_with_no_products = 'true'
        );

        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }

        //Product Sorting
        $sort = '';
        $order = '';
        $filter['search'] = null;
        if ($sort_by == "top-rated") {
            $filter['product_type'] = "top_rated_product_including_all_products";
        } elseif ($sort_by == "date-desc") {
            $sort = 'pv.date_added';
            $order = 'desc';
        } elseif ($sort_by == "date-asc") {
            $sort = 'pv.date_added';
            $order = 'asc';
        } elseif ($sort_by == "price-asc") {
            $sort = 'pv.price';
            $order = 'ASC';
        } elseif ($sort_by == "price-desc") {
            $sort = 'pv.price';
            $order = 'DESC';
        }
        $total_rows = fetch_product(null, $filter, null, $category_id, null, null, null, null, TRUE, NULL, $seller_id);

        /* $min_price_record = fetch_product($user_id, $filter, null, $category_id, 1, null, 'pv.price', 'ASC', FALSE, NULL, $seller_id);
        $max_price_record = fetch_product($user_id, $filter, null, $category_id, 1, null, 'pv.price', 'DESC', FALSE, NULL, $seller_id);

        print_r($min_price_record);
        print_r("\n\n</br></br>");
        print_r($max_price_record);
        die; */

        $theme = fetch_details('themes', ['status' => 1], 'name');
        // print_r($filter);
        // die;
        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 20;
        $config['base_url'] = base_url('products');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $limit;
        $config['num_links'] = 7;
        $config['use_page_numbers'] = TRUE;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = FALSE;

        $config['attributes'] = array('class' => 'page-link');
        $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul>';

        if (isset($theme[0]['name']) && (strtolower($theme[0]['name']) == 'modern' || strtolower($theme[0]['name']) == 'cretzo')) {

            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_link'] = '<i class="uil uil-arrow-left"></i>';
            $config['prev_tag_close'] = '</li>';

            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_link'] = '<i class="uil uil-arrow-right"></i>';
            $config['next_tag_close'] = '</li>';
        } else {
            $config['first_tag_open'] = '<li class="page-item">';
            $config['first_link'] = 'First';
            $config['first_tag_close'] = '</li>';

            $config['last_tag_open'] = '<li class="page-item">';
            $config['last_link'] = 'Last';
            $config['last_tag_close'] = '</li>';

            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_link'] = '<i class="fa fa-arrow-left"></i>';
            $config['prev_tag_close'] = '</li>';

            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_link'] = '<i class="fa fa-arrow-right"></i>';
            $config['next_tag_close'] = '</li>';
        }

        $config['cur_tag_open'] = '<li class="page-item active disabled"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';

        $page_no = (empty($this->uri->segment(2))) ? 1 : $this->uri->segment(2);
        if (!is_numeric($page_no)) {
            redirect(base_url('products'));
        }
        $offset = ($page_no - 1) * $limit;
        // print_r($offset);
        $this->pagination->initialize($config);
        $this->data['links'] = $this->pagination->create_links();
        $this->data['main_page'] = 'product-listing';
        $this->data['title'] = 'Product Listing | ' . $this->data['web_settings']['site_title'];
        $this->data['seller'] = $seller;
        $this->data['brands'] = (isset($brands)) ? (json_encode($brands)) : "";
        $this->data['categories'] = (isset($category)) ? (json_encode($category)) : "";
        $this->data['sub_categories'] = (isset($sub_category)) ? ($sub_category) : [];
        $this->data['keywords'] = 'Product Listing, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Product Listing | ' . $this->data['web_settings']['meta_description'];
        $this->data['products'] = fetch_product($user_id, $filter, null, $category_id, $limit, $offset, $sort, $order, NULL, NULL, $seller_id);


        /* we are adding total_rows and page_no to passed data as well */
        $this->data['total_rows'] = $config['total_rows'];
        $this->data['page_no'] = $page_no;
        $this->data['per_page'] = $limit;
        $this->data['num_pages'] = (int) ceil($config['total_rows'] / $limit);


        $this->data['filters'] = (isset($this->data['products']['filters'])) ? json_encode($this->data['products']['filters']) : "";
        // echo "<pre>";
        //         print_r($brands);
        //         die;
        $this->data['filters_key'] = 'all_products_listing';
        $this->data['is_category_page'] = false;
        $this->data['page_main_bread_crumb'] = "Product Listing";
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    public function category($category_slug = '')
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
        $this->form_validation->set_data($this->input->get(null, true));

        $this->form_validation->set_rules('per-page', 'Per Page', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('sort', 'Sort', 'trim|xss_clean');
        $this->form_validation->set_rules('min-price', 'Min Price', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('max-price', 'Max Price', 'trim|numeric|xss_clean');
        if (!empty($_GET) && !$this->form_validation->run()) {
            redirect(base_url('products'));
        }

        $brand_slug = (isset($_GET['brand']) && !empty($_GET['brand']) && $_GET['brand'] != "") ? $this->input->get('brand', true) : '';
        if (!empty($brand_slug)) {
            $brand = fetch_details('brands', ['slug' => $brand_slug]);
            $filter['brand'] = $brand[0]['name'];
        }

        // $offset =  0;
        // $sort = 'row_order';
        // $order =  'ASC';
        // $brands = $this->brand_model->get_brands('', $limit = NULL, $offset, $sort, $order, 'false');
        $offset = 0;
        $sort = 'row_order';
        $order = 'ASC';
        $brands = $this->brand_model->get_brands('', $limit = NULL, $offset, $sort, $order, 'false');

        // If $category_slug is empty (possible on some server configs),
        // try to extract a non-numeric slug from URI segments as a fallback.
        if (empty($category_slug)) {
            $segments = $this->uri->segment_array();
            foreach ($segments as $seg) {
                if (empty($seg)) continue;
                $seg = urldecode($seg);
                if (!is_numeric($seg) && $seg !== 'products' && $seg !== 'category') {
                    $category_slug = $seg;
                    break;
                }
            }
        }

        $category_id = get_category_id_by_slug($category_slug);
        if (empty($category_id)) {
            redirect(base_url('products'));
        }
        $category = $this->category_model->get_categories(
            $category_id,
            $limit = null,
            $offset = null,
            $sort = null,
            $order = null,
            $has_child_or_item = 'true'
        );

        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 20;
        $products = fetch_product(null, '', null, $category_id, $limit, $offset, $sort, $order);

        // $brands = array();
        // for ($i = 0; $i < count($products['product']); $i++) {
        //     if (!empty($products['product'][$i]['brand'])) {
        //         // echo "<pre>";
        //         $brand = explode(',', $products['product'][$i]['brand']);
        //         $brands = array_values(array_unique(array_merge($brands, $brand)));
        //     }
        // }

        if (empty($category)) {
            redirect(base_url('products'));
        }
        $category = $category[0];

        $attribute_values = '';
        $attribute_names = '';
        foreach ($this->input->get(null, true) as $key => $value) {
            if (strpos($key, 'filter-') !== false) {
                if (!empty($attribute_values)) {
                    $attribute_values .= "|" . $this->input->get($key, true);
                } else {
                    $attribute_values = $this->input->get($key, true);
                }

                $key = str_replace('filter-', '', $key);
                if (!empty($attribute_names)) {
                    $attribute_names .= "|" . $key;
                } else {
                    $attribute_names = $key;
                }
            }
        }

        //get attributes ids
        $attribute_values = explode('|', $attribute_values);
        $attribute_names = explode('|', $attribute_names);
        $filter['attribute_value_ids'] = get_attribute_ids_by_value($attribute_values, $attribute_names);
        $filter['attribute_value_ids'] = implode(',', $filter['attribute_value_ids']);

        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $sort_by = ($this->input->get('sort')) ? $this->input->get('sort', true) : '';
        $category_id = $category['id'];
        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }


        /* filter within price range */
        $min_price = ($this->input->get('min-price')) ? $this->input->get('min-price') : null;
        $max_price = ($this->input->get('max-price')) ? $this->input->get('max-price') : null;
        $filter['min_price'] = $min_price;
        $filter['max_price'] = $max_price;


        //Product Sorting
        $sort = '';
        $order = '';
        $filter['search'] = null;
        if ($sort_by == "top-rated") {
            $filter['product_type'] = "top_rated_product_including_all_products";
        } elseif ($sort_by == "date-desc") {
            $sort = 'pv.date_added';
            $order = 'desc';
        } elseif ($sort_by == "date-asc") {
            $sort = 'pv.date_added';
            $order = 'asc';
        } elseif ($sort_by == "price-asc") {
            $sort = 'price';
            $order = 'asc';
        } elseif ($sort_by == "price-desc") {
            $sort = 'price';
            $order = 'desc';
        }
        $total_rows = fetch_product($user_id, $filter, null, $category_id, null, null, null, null, TRUE);

        $config['base_url'] = base_url('products/category/' . $category_slug);
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $limit;
        $config['use_page_numbers'] = TRUE;
        $config['uri_segment'] = 4;
        $config['num_links'] = 7;
        $config['use_page_numbers'] = TRUE;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = FALSE;

        $config['attributes'] = array('class' => 'page-link');
        $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul>';

        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_link'] = 'First';
        $config['first_tag_close'] = '</li>';

        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_link'] = 'Last';
        $config['last_tag_close'] = '</li>';

        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_link'] = '<i class="fa fa-arrow-left"></i>';
        $config['prev_tag_close'] = '</li>';

        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_link'] = '<i class="fa fa-arrow-right"></i>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $page_no = (empty($this->uri->segment(4))) ? 1 : $this->uri->segment(4);
        if (!is_numeric($page_no)) {
            redirect(base_url('products'));
        }
        $offset = ($page_no - 1) * $limit;
        $this->pagination->initialize($config);
        $this->data['links'] = $this->pagination->create_links();
        $page_title = $category['name'] . " " . ((!empty($this->data['sub_categories'])) ? "Subcategories" : "") . " " . ((!empty($this->data['sub_categories']) && !empty($this->data['products']['product'])) ? "&" : "") . " " . ((!empty($this->data['products']['product'])) ? "Products" : "");
        $this->data['main_page'] = 'product-listing';
        $this->data['title'] = $page_title . ' | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = $page_title . ',Product Listing, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = $page_title . ' Product Listing | ' . $this->data['web_settings']['meta_description'];
        $this->data['left_breadcrumb'] = $category['name'];
        $category_lang = !empty($this->lang->line("category")) ? $this->lang->line("category") : "Category";
        $this->data['right_breadcrumb'] = array(
            '<a href="' . base_url('home/categories') . '">' . $category_lang . '</a>',
        );


        /* we are adding total_rows and page_no to passed data as well */
        $this->data['total_rows'] = $config['total_rows'];
        $this->data['page_no'] = $page_no;
        $this->data['per_page'] = $limit;
        $this->data['num_pages'] = (int) ceil($config['total_rows'] / $limit);


        $this->data['products'] = fetch_product(null, $filter, null, $category_id, $limit, $offset, $sort, $order);
        $this->data['filters'] = (isset($this->data['products']['filters'])) ? json_encode($this->data['products']['filters']) : "";
        $this->data['filters_key'] = 'category_products_' . $category_slug;
        $this->data['is_category_page'] = true;
        $this->data['brands'] = (isset($brands)) ? json_encode($brands) : "";
        // $this->data['categories'] = (isset($category)) ? ($category) : "";
        $this->data['single_category'] = $category;
        $this->data['sub_categories'] = $this->category_model->sub_categories($category['id'], 1);
        $this->data['page_main_bread_crumb'] = $page_title;
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    public function details($slug = '')
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
        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }

        $slug = urldecode($slug);
        $valid_zipcode = (!empty($this->session->userdata('valid_zipcode'))) ? $this->session->userdata('valid_zipcode') : "";
        // GST enrollment restriction : out-of-state customers must not view a
        // state-restricted product; the state filter makes the fetch return empty for them.
        $product = fetch_product($user_id, ['slug' => $slug, 'customer_state' => get_customer_state()], NULL, NULL, NULL, NULL, NULL, NULL, NULL, $valid_zipcode);

        // Empty check moved above the variant access so a restricted/not-found product
        // redirects cleanly instead of erroring on a missing variant.
        if (empty($product['product'])) {
            redirect(base_url('products'));
        }

        $product_variant_id = $product['product'][0]['variants'][0]['id'];
        $res = get_statistics($product_variant_id);
        $product['product'][0]['zipcode'] = $valid_zipcode;
        $this->data['product'] = $product;
        $user_rating_limit = 5;
        $user_rating_offset = 0;
        $this->data['product_ratings'] = $this->rating_model->fetch_rating($product['product'][0]['id'], null, $user_rating_limit, $user_rating_offset, 'pr.id', 'DESC');
        $this->data['review_images'] = $this->rating_model->fetch_rating($product['product'][0]['id'], '', 2, 0, 'pr.id', 'DESC', '', 1);
        $this->data['my_rating'] = array();
        if ($this->ion_auth->logged_in()) {
            $this->data['my_rating'] = $this->rating_model->fetch_rating($product['product'][0]['id'], $this->data['user']->id);
        }
        $this->data['related_products'] = fetch_product($user_id, NULL, NULL, $product['product'][0]['category_id'], 12);


        $this->data['seller_products'] = fetch_product('', '', '', '', 12, '', '', '', '', '', $product['product'][0]['seller_id']);
        /* $seller_products = fetch_product('', '', '', '', 8, '', '', '', '', '', $product['product'][0]['seller_id']);
        $this->data['seller_products'] = $seller_products['product']; */

        $this->data['main_page'] = 'product-page';
        $this->data['title'] = $product['product'][0]['name'] . ' in ' . $product['product'][0]['category_name'] . ' | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = $product['product'][0]['name'] . ', ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = $product['product'][0]['name'] . ' | ' . $this->data['web_settings']['meta_description'];
        $this->data['product_image'] = $product['product'][0]['image'];
        $this->data['username'] = $this->session->userdata('username');
        $this->data['user_rating_limit'] = $user_rating_limit;
        $this->data['statistics'] = $res;
        $this->data['seller_products_count'] = $this->Home_model->count_products($product['product'][0]['seller_id']);
        $this->data['user_rating_offset'] = $user_rating_limit + $user_rating_offset;
        $category_id = fetch_details('products', ['id' => $product['product'][0]['id']], 'category_id');
        $this->data['faq'] = $this->product_model->get_product_faqs('', $product['product'][0]['id'], $user_id, '', 0, 10, 'id', 'DESC');
        $this->db->set('clicks', 'clicks+1', FALSE);
        $this->db->where('id', $category_id[0]['category_id']);
        $this->db->update('categories');
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    public function get_details($product_id = '')
    {
        // Both early exits used to `return false`, i.e. send an EMPTY body. This is fetched with
        // $.getJSON (the quick-view modal in the classic theme), and jQuery treats an empty body
        // as a parse failure - so the modal just never opened, with nothing logged and no error
        // shown. Answer with JSON either way so the caller can tell what happened.
        if (empty($product_id)) {
            print_r(json_encode(['error' => true, 'message' => 'No product specified.', 'data' => []]));
            return false;
        }
        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }
        $valid_zipcode = (!empty($this->session->userdata('valid_zipcode'))) ? $this->session->userdata('valid_zipcode') : "";
        // GST enrollment restriction (P3.2): don't expose a state-restricted product to out-of-state customers.
        $product = fetch_product($user_id, ['customer_state' => get_customer_state()], $product_id, NULL, NULL, NULL, NULL, NULL, NULL, $valid_zipcode);
        if (empty($product['product'])) {
            // Also covers a product hidden from THIS customer by the GST state restriction, which
            // is a legitimate "not available" rather than an error worth alarming anyone about.
            print_r(json_encode(['error' => true, 'message' => 'Product not available.', 'data' => []]));
            return false;
        }
        $product['product'][0]['zipcode'] = $valid_zipcode;
        $product = $product['product'][0];
        $product['get_price'] = get_price_range_of_product($product['id']);
        print_r(json_encode($product));
    }

    public function section($section_id = '', $section_title = '')
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
        if (empty($section_id)) {
            redirect(base_url());
        }
        $this->form_validation->set_data($this->input->get(null, true));
        $this->form_validation->set_rules('per-page', 'Per Page', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('sort', 'Sort', 'trim|xss_clean');
        $this->form_validation->set_rules('min-price', 'Min Price', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('max-price', 'Max Price', 'trim|numeric|xss_clean');
        if (!empty($_GET) && !$this->form_validation->run()) {
            redirect(base_url('products'));
        }
        // Honour the publish flag (migration 046). Without the status condition an unpublished
        // section stayed fully reachable at its own /products/section/<id> URL - the same
        // "hidden from the listing but live at its direct link" hole the blog detail page had.
        $section = $this->db->where('id', $section_id)->where('status', 1)->get('sections')->row_array();
        if (empty($section)) {
            redirect(base_url());
        }

        $category = $this->category_model->get_categories(
            '',
            null,
            $offset = 0,
            $sort = null,
            $order = null,
            $has_child_or_item = 'true'
        );

        $attribute_values = '';
        $attribute_names = '';
        foreach ($this->input->get(null, true) as $key => $value) {
            if (strpos($key, 'filter-') !== false) {
                if (!empty($attribute_values)) {
                    $attribute_values .= "|" . $this->input->get($key, true);
                } else {
                    $attribute_values = $this->input->get($key, true);
                }

                $key = str_replace('filter-', '', $key);
                if (!empty($attribute_names)) {
                    $attribute_names .= "|" . $key;
                } else {
                    $attribute_names = $key;
                }
            }
        }

        //get attributes ids
        $attribute_values = explode('|', $attribute_values);
        $attribute_names = explode('|', $attribute_names);
        $filter = array();
        $filter['attribute_value_ids'] = get_attribute_ids_by_value($attribute_values, $attribute_names);
        $filter['attribute_value_ids'] = implode(',', $filter['attribute_value_ids']);
        $product_ids = explode(',', (string) $section['product_ids']);
        $product_ids = array_filter($product_ids);
        if (isset($section['product_type']) && !empty($section['product_type'])) {
            $filter['product_type'] = (isset($section['product_type'])) ? $section['product_type'] : null;
        }
        $product_categories = (isset($section['categories']) && !empty($section['categories']) && $section['categories'] != NULL) ? explode(',', $section['categories']) : null;


        /* filter within price range */
        $min_price = ($this->input->get('min-price')) ? $this->input->get('min-price') : null;
        $max_price = ($this->input->get('max-price')) ? $this->input->get('max-price') : null;
        $filter['min_price'] = $min_price;
        $filter['max_price'] = $max_price;


        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 20;
        $products = fetch_product(null, $filter, $product_ids, $product_categories, $limit, $offset, $sort, $order);

        // $brands = array();
        // for ($i = 0; $i < count($products['product']); $i++) {
        //     if (!empty($products['product'][$i]['brand'])) {
        //         // echo "<pre>";
        //         $brand = explode(',', $products['product'][$i]['brand']);
        //         $brands = array_values(array_unique(array_merge($brands, $brand)));
        //     }
        // }

        $offset = 0;
        $sort = 'row_order';
        $order = 'ASC';
        $brands = $this->brand_model->get_brands('', $limit = NULL, $offset, $sort, $order, 'false');

        $brand_slug = (isset($_GET['brand']) && !empty($_GET['brand']) && $_GET['brand'] != "") ? $this->input->get('brand', true) : '';
        /* if (!empty($brand_slug)) {
            $brand = fetch_details('brands', ['slug' => $brand_slug]);
            $filter['brand'] =  $brand[0]['name'];
        } */
        // print_r($brand_slug);
        // die;

        /* We have instead added filtering for multiple brands at once */
        if (!empty($brand_slug)) {
            $filter['brands'] = $brand_slug;
        }


        $category_slug = (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] != "") ? $this->input->get('category', true) : '';
        if (!empty($category_slug)) {
            // $category = fetch_details('brands', ['slug' => $brand_slug]);
            $filter['category'] = $category_slug;
        }



        // $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $sort_by = ($this->input->get('sort')) ? $this->input->get('sort', true) : '';
        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }
        //Product Sorting
        $sort = '';
        $order = '';
        $filter['search'] = null;
        if ($sort_by == "top-rated") {
            $filter['product_type'] = "top_rated_product_including_all_products";
        } elseif ($sort_by == "date-desc") {
            $sort = 'pv.date_added';
            $order = 'desc';
        } elseif ($sort_by == "date-asc") {
            $sort = 'pv.date_added';
            $order = 'asc';
        } elseif ($sort_by == "price-asc") {
            $sort = 'price';
            $order = 'asc';
        } elseif ($sort_by == "price-desc") {
            $sort = 'price';
            $order = 'desc';
        }
        $total_rows = fetch_product($user_id, $filter, $product_ids, $product_categories, null, null, null, null, TRUE);
        $config['base_url'] = base_url('products/section/' . $section_id . '/' . $section_title);
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $limit;
        $config['uri_segment'] = 5;
        $config['use_page_numbers'] = TRUE;
        $config['num_links'] = 7;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = FALSE;

        // $config['attributes'] = array('class' => 'page-link');

        $theme = fetch_details('themes', ['status' => 1], 'name');

        if (isset($theme[0]['name']) && (strtolower($theme[0]['name']) == 'modern' || strtolower($theme[0]['name']) == 'cretzo')) {
            $config['attributes'] = array('class' => 'page-link');
            $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
            $config['full_tag_close'] = '</ul>';

            // $config['first_tag_open'] = '<li class="page-item">';
            // $config['first_link'] = 'First';
            // $config['first_tag_close'] = '</li>';

            // $config['last_tag_open'] = '<li class="page-item">';
            // $config['last_link'] = 'Last';
            // $config['last_tag_close'] = '</li>';

            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_link'] = '<i class="uil uil-arrow-left"></i>';
            $config['prev_tag_close'] = '</li>';

            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_link'] = '<i class="uil uil-arrow-right"></i>';
            $config['next_tag_close'] = '</li>';

            $config['cur_tag_open'] = '<li class="page-item active disabled"><a class="page-link">';
            $config['cur_tag_close'] = '</a></li>';

            $config['num_tag_open'] = '<li class="page-item">';
            $config['num_tag_close'] = '</li>';
        } else {
            $config['attributes'] = array('class' => 'page-link');
            $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
            $config['full_tag_close'] = '</ul>';

            $config['first_tag_open'] = '<li class="page-item">';
            $config['first_link'] = 'First';
            $config['first_tag_close'] = '</li>';

            $config['last_tag_open'] = '<li class="page-item">';
            $config['last_link'] = 'Last';
            $config['last_tag_close'] = '</li>';

            $config['prev_tag_open'] = '<li class="page-item">';
            $config['prev_link'] = '<i class="fa fa-arrow-left"></i>';
            $config['prev_tag_close'] = '</li>';

            $config['next_tag_open'] = '<li class="page-item">';
            $config['next_link'] = '<i class="fa fa-arrow-right"></i>';
            $config['next_tag_close'] = '</li>';

            $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
            $config['cur_tag_close'] = '</a></li>';

            $config['num_tag_open'] = '<li class="page-item">';
            $config['num_tag_close'] = '</li>';
        }
        $page_no = (empty($this->uri->segment(5))) ? 1 : $this->uri->segment(5);
        if (!is_numeric($page_no)) {
            redirect(base_url('products'));
        }
        $offset = ($page_no - 1) * $limit;
        $this->pagination->initialize($config);
        $this->data['links'] = $this->pagination->create_links();
        $page_title = $section['title'] . " Products";
        $page_title = output_escaping($page_title);
        $this->data['main_page'] = 'product-listing';
        $this->data['title'] = $page_title . ' | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = $page_title . ',Product Section, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = $page_title . ' Product Section | ' . $this->data['web_settings']['meta_description'];
        $this->data['left_breadcrumb'] = $section['title'];
        $category_lang = !empty($this->lang->line("section")) ? $this->lang->line("section") : "Section";
        $this->data['right_breadcrumb'] = array(
            !empty($this->lang->line("section")) ? $this->lang->line("section") : "Section",
        );

        $this->data['brands'] = (isset($brands)) ? json_encode($brands) : "";
        $this->data['categories'] = (isset($category)) ? json_encode($category) : "";


        /* we are adding total_rows and page_no to passed data as well */
        $this->data['total_rows'] = $config['total_rows'];
        $this->data['page_no'] = $page_no;
        $this->data['per_page'] = $limit;
        $this->data['num_pages'] = $limit == 0 ? 1 : (int) ceil($config['total_rows'] / $limit);


        $this->data['products'] = fetch_product(null, $filter, $product_ids, $product_categories, $limit, $offset, $sort, $order);
        $this->data['filters'] = (isset($this->data['products']['filters'])) ? json_encode($this->data['products']['filters']) : "";
        $this->data['section_slug'] = fetch_details('sections', ['id' => $section_id]);
        $this->data['filters_key'] = 'products_section_' . $section_id;
        $this->data['page_main_bread_crumb'] = $page_title;
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }
    public function search()
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
        $this->form_validation->set_data($this->input->get(null, true));
        $this->form_validation->set_rules('per-page', 'Per Page', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('sort', 'Sort', 'trim|xss_clean');
        $this->form_validation->set_rules('min-price', 'Min Price', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('max-price', 'Max Price', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('q', 'search', 'required|trim|xss_clean');
        if (!empty($_GET) && !$this->form_validation->run()) {
            redirect(base_url('products'));
        }

        $attribute_values = '';
        $attribute_names = '';
        foreach ($this->input->get(null, true) as $key => $value) {
            if (strpos($key, 'filter-') !== false) {
                if (!empty($attribute_values)) {
                    $attribute_values .= "|" . $this->input->get($key, true);
                } else {
                    $attribute_values = $this->input->get($key, true);
                }

                $key = str_replace('filter-', '', $key);
                if (!empty($attribute_names)) {
                    $attribute_names .= "|" . $key;
                } else {
                    $attribute_names = $key;
                }
            }
        }

        //get attributes ids
        $attribute_values = explode('|', $attribute_values);
        $attribute_names = explode('|', $attribute_names);
        $filter = array();
        $filter['attribute_value_ids'] = get_attribute_ids_by_value($attribute_values, $attribute_names);
        $filter['attribute_value_ids'] = implode(',', $filter['attribute_value_ids']);
        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $sort_by = ($this->input->get('sort')) ? $this->input->get('sort', true) : '';
        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }


        /* filter within price range */
        $min_price = ($this->input->get('min-price')) ? $this->input->get('min-price') : null;
        $max_price = ($this->input->get('max-price')) ? $this->input->get('max-price') : null;
        $filter['min_price'] = $min_price;
        $filter['max_price'] = $max_price;


        //Product Sorting
        $sort = '';
        $order = '';
        $filter['search'] = $this->input->get('q', true);
        if ($sort_by == "top-rated") {
            $filter['product_type'] = "top_rated_product_including_all_products";
        } elseif ($sort_by == "date-desc") {
            $sort = 'pv.date_added';
            $order = 'desc';
        } elseif ($sort_by == "date-asc") {
            $sort = 'pv.date_added';
            $order = 'asc';
        } elseif ($sort_by == "price-asc") {
            $sort = 'price';
            $order = 'asc';
        } elseif ($sort_by == "price-desc") {
            $sort = 'price';
            $order = 'desc';
        }
        $total_rows = fetch_product($user_id, $filter, null, null, null, null, null, null, TRUE);

        $config['base_url'] = base_url('products/search');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $limit;
        $config['uri_segment'] = 3;
        $config['use_page_numbers'] = TRUE;
        $config['num_links'] = 7;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = FALSE;

        $config['attributes'] = array('class' => 'page-link');
        $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul>';

        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_link'] = 'First';
        $config['first_tag_close'] = '</li>';

        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_link'] = 'Last';
        $config['last_tag_close'] = '</li>';

        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_link'] = '<i class="fa fa-arrow-left"></i>';
        $config['prev_tag_close'] = '</li>';

        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_link'] = '<i class="fa fa-arrow-right"></i>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $page_no = (empty($this->uri->segment(3))) ? 1 : $this->uri->segment(3);
        if (!is_numeric($page_no)) {
            redirect(base_url('products'));
        }
        $offset = ($page_no - 1) * $limit;
        $this->pagination->initialize($config);
        $this->data['links'] = $this->pagination->create_links();
        // Read unguarded, so /products/search with no q - a bare visit, or a cleared search box -
        // printed a warning into the page.
        $page_title = 'Search Result for "' . html_escape(isset($_GET['q']) ? $_GET['q'] : '') . '"';
        $this->data['main_page'] = 'product-listing';
        $this->data['title'] = $page_title . ' | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = $page_title . ',Product Section, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = $page_title . ' Product Section | ' . $this->data['web_settings']['meta_description'];
        $this->data['left_breadcrumb'] = "Search";
        $category_lang = !empty($this->lang->line("section")) ? $this->lang->line("section") : "Section";
        $this->data['right_breadcrumb'] = array(
            !empty($this->lang->line("search")) ? $this->lang->line("search") : "Search",
        );


        /* we are adding total_rows and page_no to passed data as well */
        $this->data['total_rows'] = $config['total_rows'];
        $this->data['page_no'] = $page_no;
        $this->data['per_page'] = $limit;
        $this->data['num_pages'] = (int) ceil($config['total_rows'] / $limit);


        $this->data['products'] = fetch_product(null, $filter, null, null, $limit, $offset, $sort, $order);
        $this->data['filters'] = (isset($this->data['products']['filters'])) ? json_encode($this->data['products']['filters']) : "";
        $this->data['filters_key'] = 'products_search';
        $this->data['is_category_page'] = false;
        $this->data['page_main_bread_crumb'] = $page_title;
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    public function tags($tag = '')
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
        if (empty($tag)) {
            redirect(base_url());
        }
        $this->form_validation->set_data($this->input->get(null, true));
        $this->form_validation->set_rules('per-page', 'Per Page', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('sort', 'Sort', 'trim|xss_clean');
        $this->form_validation->set_rules('min-price', 'Min Price', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('max-price', 'Max Price', 'trim|numeric|xss_clean');
        if (!empty($_GET) && !$this->form_validation->run()) {
            redirect(base_url('products'));
        }

        $attribute_values = '';
        $attribute_names = '';
        foreach ($this->input->get(null, true) as $key => $value) {
            if (strpos($key, 'filter-') !== false) {
                if (!empty($attribute_values)) {
                    $attribute_values .= "|" . $this->input->get($key, true);
                } else {
                    $attribute_values = $this->input->get($key, true);
                }

                $key = str_replace('filter-', '', $key);
                if (!empty($attribute_names)) {
                    $attribute_names .= "|" . $key;
                } else {
                    $attribute_names = $key;
                }
            }
        }

        //get attributes ids
        $attribute_values = explode('|', $attribute_values);
        $attribute_names = explode('|', $attribute_names);
        $filter = array();
        $filter['tags'] = xss_clean($tag);
        $filter['attribute_value_ids'] = get_attribute_ids_by_value($attribute_values, $attribute_names);
        $filter['attribute_value_ids'] = implode(',', $filter['attribute_value_ids']);
        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $sort_by = ($this->input->get('sort')) ? $this->input->get('sort', true) : '';
        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }
        //Product Sorting
        $sort = '';
        $order = '';
        $filter['search'] = null;
        if ($sort_by == "top-rated") {
            $filter['product_type'] = "top_rated_product_including_all_products";
        } elseif ($sort_by == "date-desc") {
            $sort = 'pv.date_added';
            $order = 'desc';
        } elseif ($sort_by == "date-asc") {
            $sort = 'pv.date_added';
            $order = 'asc';
        } elseif ($sort_by == "price-asc") {
            $sort = 'price';
            $order = 'asc';
        } elseif ($sort_by == "price-desc") {
            $sort = 'price';
            $order = 'desc';
        }
        $total_rows = fetch_product($user_id, $filter, null, null, null, null, null, null, TRUE);

        $config['base_url'] = base_url('products/search');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $limit;
        $config['uri_segment'] = 3;
        $config['use_page_numbers'] = TRUE;
        $config['num_links'] = 7;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = FALSE;

        $config['attributes'] = array('class' => 'page-link');
        $config['full_tag_open'] = '<ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul>';

        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_link'] = 'First';
        $config['first_tag_close'] = '</li>';

        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_link'] = 'Last';
        $config['last_tag_close'] = '</li>';

        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_link'] = '<i class="fa fa-arrow-left"></i>';
        $config['prev_tag_close'] = '</li>';

        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_link'] = '<i class="fa fa-arrow-right"></i>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';

        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $page_no = (empty($this->uri->segment(4))) ? 1 : $this->uri->segment(4);
        if (!is_numeric($page_no)) {
            redirect(base_url('products'));
        }
        $offset = ($page_no - 1) * $limit;
        $this->pagination->initialize($config);
        $this->data['links'] = $this->pagination->create_links();
        $page_title = 'Products by tag "' . xss_clean($tag) . '"';
        $this->data['main_page'] = 'product-listing';
        $this->data['title'] = $page_title . ' | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = $page_title . ', Product Section, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = $page_title . ' Product Section | ' . $this->data['web_settings']['meta_description'];
        $this->data['left_breadcrumb'] = "Search";
        $category_lang = !empty($this->lang->line("section")) ? $this->lang->line("section") : "Section";
        $this->data['right_breadcrumb'] = array(
            !empty($this->lang->line("tags")) ? $this->lang->line("tags") : "Tags",
        );
        $this->data['products'] = fetch_product(null, $filter, null, null, $limit, $offset, $sort, $order);
        // Provide the pagination vars the listing view expects (mirrors index()/category())
        // so the tags page doesn't emit PHP notices on its initial render.
        $this->data['total_rows'] = $config['total_rows'];
        $this->data['page_no'] = $page_no;
        $this->data['per_page'] = $limit;
        $this->data['num_pages'] = (int) ceil($config['total_rows'] / $limit);
        $this->data['filters'] = (isset($this->data['products']['filters'])) ? json_encode($this->data['products']['filters']) : "";
        $this->data['filters_key'] = 'products_tags';
        $this->data['is_category_page'] = false;
        $this->data['page_main_bread_crumb'] = $page_title;
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    // 9 save_rating
    public function save_rating()
    {

        /*
            user_id: 21
            product_id: 33
            rating: 4.2
            comment: 'Done' {optional}
        */
        if (!$this->ion_auth->logged_in()) {
            return false;
        }
        $this->form_validation->set_rules('product_id', 'Product Id', 'trim|numeric|xss_clean|required');
        $this->form_validation->set_rules('rating', 'Rating', 'trim|numeric|xss_clean|greater_than[0]|less_than[6]|required');
        $this->form_validation->set_rules('comment', 'Comment', 'trim|xss_clean');
        $_POST['user_id'] = $this->data['user']->id;
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = validation_errors();
            $this->response['data'] = array();
            echo json_encode($this->response);
        } else {
            if (!file_exists(FCPATH . REVIEW_IMG_PATH)) {
                mkdir(FCPATH . REVIEW_IMG_PATH, 0777);
            }

            $temp_array = array();
            $files = $_FILES;
            $images_new_name_arr = array();
            $images_info_error = "";
            $config = [
                'upload_path' => FCPATH . REVIEW_IMG_PATH,
                'allowed_types' => 'jpg|png|jpeg|gif',
                'max_size' => 8000,
            ];

            if (!empty($_FILES['images']['name'][0]) && isset($_FILES['images']['name'])) {
                $other_image_cnt = count($_FILES['images']['name']);
                $other_img = $this->upload;
                $other_img->initialize($config);

                for ($i = 0; $i < $other_image_cnt; $i++) {

                    if (!empty($_FILES['images']['name'][$i])) {

                        $_FILES['temp_image']['name'] = $files['images']['name'][$i];
                        $_FILES['temp_image']['type'] = $files['images']['type'][$i];
                        $_FILES['temp_image']['tmp_name'] = $files['images']['tmp_name'][$i];
                        $_FILES['temp_image']['error'] = $files['images']['error'][$i];
                        $_FILES['temp_image']['size'] = $files['images']['size'][$i];
                        if (!$other_img->do_upload('temp_image')) {
                            $images_info_error = 'Images :' . $images_info_error . ' ' . $other_img->display_errors();
                        } else {
                            $temp_array = $other_img->data();
                            resize_review_images($temp_array, FCPATH . REVIEW_IMG_PATH);
                            $images_new_name_arr[$i] = REVIEW_IMG_PATH . $temp_array['file_name'];
                        }
                    } else {
                        $_FILES['temp_image']['name'] = $files['images']['name'][$i];
                        $_FILES['temp_image']['type'] = $files['images']['type'][$i];
                        $_FILES['temp_image']['tmp_name'] = $files['images']['tmp_name'][$i];
                        $_FILES['temp_image']['error'] = $files['images']['error'][$i];
                        $_FILES['temp_image']['size'] = $files['images']['size'][$i];
                        if (!$other_img->do_upload('temp_image')) {
                            $images_info_error = $other_img->display_errors();
                        }
                    }
                }

                //Deleting Uploaded Images if any overall error occured
                if ($images_info_error != NULL || !$this->form_validation->run()) {
                    if (isset($images_new_name_arr) && !empty($images_new_name_arr || !$this->form_validation->run())) {
                        foreach ($images_new_name_arr as $key => $val) {
                            if (file_exists(FCPATH . REVIEW_IMG_PATH . $images_new_name_arr[$key])) {
                                unlink(FCPATH . REVIEW_IMG_PATH . $images_new_name_arr[$key]);
                            }
                        }
                    }
                }
            }

            if ($images_info_error != NULL) {
                $this->response['error'] = true;
                $this->response['csrfName'] = $this->security->get_csrf_token_name();
                $this->response['csrfHash'] = $this->security->get_csrf_hash();
                $this->response['message'] = $images_info_error;
                print_r(json_encode($this->response));
                return;
            }

            $res = $this->db->select('*')->join('product_variants pv', 'pv.id=oi.product_variant_id')->join('products p', 'p.id=pv.product_id')->where(['pv.product_id' => $_POST['product_id'], 'oi.user_id' => $_POST['user_id'], 'oi.active_status!=' => 'returned'])->limit(1)->get('order_items oi')->result_array();
            if (empty($res)) {
                $this->response['error'] = true;
                $this->response['message'] = 'You cannot review as the product is not purchased yet!';
                $this->response['data'] = array();
                echo json_encode($this->response);
                return;
            }

            $rating_data = fetch_details('product_rating', ['user_id' => $_POST['user_id'], 'product_id' => $_POST['product_id']], 'images');
            $rating_images = $images_new_name_arr;
            if (isset($rating_data[0]['images']) && isset($rating_data) && !empty($rating_data[0]['images'])) {
                $existing_images = json_decode($rating_data[0]['images']);
                $rating_images = array_merge($existing_images, $images_new_name_arr);
            }

            $_POST['images'] = $rating_images;
            $this->rating_model->set_rating($_POST);
            $rating_data = $this->rating_model->fetch_rating((isset($_POST['product_id'])) ? $_POST['product_id'] : '', '', '25', '0', 'id', 'DESC');
            $rating['product_rating'] = $rating_data['product_rating'];
            $rating['no_of_rating'] = $rating_data['no_of_rating'];
            $this->response['error'] = false;
            $this->response['message'] = 'Product Rated Successfully';
            $this->response['data'] = $rating;
            echo json_encode($this->response);
            return;
        }
    }

    public function delete_rating()
    {
        if (!$this->ion_auth->logged_in()) {
            return false;
        }
        $this->form_validation->set_rules('rating_id', 'Rating Id', 'trim|numeric|required|xss_clean');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = validation_errors();
            $this->response['data'] = array();
            echo json_encode($this->response);
            return false;
        } else {
            $rating_data = fetch_details('product_rating', ['id' => $_POST['rating_id']]);
            if (empty($rating_data)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Invalid Rating ID.';
                echo json_encode($this->response);
                return false;
            }
            $rating_data = $rating_data[0];
            if ($rating_data['user_id'] != $this->data['user']->id) {
                $this->response['error'] = true;
                $this->response['message'] = 'You are not authorised to delete this rating.';
                echo json_encode($this->response);
                return false;
            }
            $this->rating_model->delete_rating($_POST['rating_id']);
            $data = $this->rating_model->fetch_rating($rating_data['product_id']);
            $this->response['error'] = false;
            $this->response['message'] = 'Deleted Rating Successfully';
            $this->response['data'] = $data;
            echo json_encode($this->response);
        }
    }
    public function get_rating()
    {
        $this->form_validation->set_data($_GET);
        $this->form_validation->set_rules('limit', 'Limit', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('offset', 'Offset', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('sort', 'Sort', 'trim|xss_clean');
        $this->form_validation->set_rules('order', 'Order', 'trim|xss_clean');
        $this->form_validation->set_rules('product_id', 'Product', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('user_id', 'User', 'trim|numeric|xss_clean');
        if (!empty($_GET) && !$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = validation_errors();
            $this->response['data'] = array();
            echo json_encode($this->response);
            return false;
        }

        $product_id = (isset($_GET['product_id'])) ? $_GET['product_id'] : null;
        $user_id = (isset($_GET['user_id'])) ? $_GET['user_id'] : null;
        $limit = (isset($_GET['limit'])) ? $_GET['limit'] : 2;
        $offset = (isset($_GET['offset'])) ? $_GET['offset'] : 0;
        $sort = (isset($_GET['sort'])) ? $_GET['sort'] : 'pr.id';
        $order = (isset($_GET['order'])) ? $_GET['order'] : 'DESC';
        $has_images = (isset($_GET['has_images'])) ? $_GET['has_images'] : null;

        $data = $this->rating_model->fetch_rating($product_id, $user_id, $limit, $offset, $sort, $order, null, $has_images);

        if (empty($data)) {
            $this->response['error'] = true;
            $this->response['message'] = 'No more reviews found.';
            $this->response['data'] = $data;
            echo json_encode($this->response);
            return false;
        }
        $this->response['error'] = false;
        $this->response['message'] = 'Ratings retrieved Successfully';
        $this->response['data'] = $data;
        echo json_encode($this->response);
        return false;
    }

    public function check_zipcode()
    {
        $this->form_validation->set_rules('product_id', 'Product Id', 'trim|numeric|xss_clean|required');
        $this->form_validation->set_rules('zipcode', 'Zipcode', 'trim|xss_clean|required');
        if (!$this->form_validation->run()) {
            $this->response['error'] = true;
            $this->response['message'] = validation_errors();
            $this->response['data'] = array();
            echo json_encode($this->response);
        } else {
            $this->load->library(['Shiprocket']);
            $settings = get_settings('shipping_method', true);
            $zipcode = $this->input->post('zipcode', true);
            $is_pincode = is_exist(['zipcode' => $zipcode], 'zipcodes');
            $product_id = $this->input->post('product_id', true);
            $seller_row = fetch_details('products', ['id' => $product_id], 'seller_id');
            $seller_id  = !empty($seller_row) ? $seller_row[0]['seller_id'] : null;
            if (!empty($seller_id)) {
                $seller_kyc = fetch_details('seller_data', ['user_id' => $seller_id], 'is_gst_registered,state');
                if (!empty($seller_kyc) && isset($seller_kyc[0]['is_gst_registered']) && $seller_kyc[0]['is_gst_registered'] == 0) {
                    $seller_state   = trim((string) $seller_kyc[0]['state']);
                    $customer_state = get_state_from_pincode($zipcode);
                    if ($seller_state !== '' && $customer_state !== '' && !states_match($seller_state, $customer_state)) {
                        $this->response['error']   = true;
                        $this->response['message'] = '<b class="text-danger">Sorry, this product is not available in your area. This seller can only deliver within ' . html_escape($seller_state) . '.</b>';
                        echo json_encode($this->response);
                        return false;
                    }
                }
            }

            if ($is_pincode) {
                $zipcode_id = fetch_details('zipcodes', ['zipcode' => $zipcode], 'id');
                $is_available = is_product_delivarable($type = 'zipcode', $zipcode_id[0]['id'], $product_id);
                if ($is_available) {
                    $_SESSION['valid_zipcode'] = $zipcode;
                    $this->response['error'] = false;
                    $this->response['message'] = '<b class="text-success">Product is deliverable on "' . $zipcode . '"</b>';
                    echo json_encode($this->response);
                    return false;
                } else {
                    $this->response['error'] = true;
                    $this->response['message'] = '<b class="text-danger">Product is not deliverable on "' . $zipcode . '"</b>';
                    echo json_encode($this->response);
                    return false;
                }
            } else {
                $product_data = fetch_details('products', ['id' => $product_id], 'pickup_location');
                $product_varient_data = fetch_details('product_variants', ['product_id' => $product_id], 'weight');
                $pickup_pincode = fetch_details('pickup_locations', ['pickup_location' => $product_data[0]['pickup_location']], 'pin_code');
                if (isset($zipcode)) {
                    $availibility_data = [
                        'pickup_postcode' => (isset($pickup_pincode[0]['pin_code']) && !empty($pickup_pincode[0]['pin_code'])) ? $pickup_pincode[0]['pin_code'] : "",
                        'delivery_postcode' => $zipcode,
                        'cod' => 0,
                        'weight' => $product_varient_data[0]['weight'],
                    ];
                    $check_deliveribility = $this->shiprocket->check_serviceability($availibility_data);
                    if (isset($check_deliveribility['status_code']) && $check_deliveribility['status_code'] == 422) {
                        $this->response['error'] = true;
                        $this->response['message'] = '<b class="text-danger">Invalid Delivery Pincode "' . $zipcode . '"</b>';
                        echo json_encode($this->response);
                        return false;
                    } else {
                        if (isset($check_deliveribility['status']) && $check_deliveribility['status'] == 200 && !empty($check_deliveribility['data']['available_courier_companies'])) {
                            $estimate_date = $check_deliveribility['data']['available_courier_companies'][0]['etd'];
                            $_SESSION['valid_zipcode'] = $zipcode;
                            $this->response['error'] = false;
                            $this->response['message'] = '<b class="text-success">Product is deliverable by ' . $estimate_date . '</b>';
                            echo json_encode($this->response);
                            return false;
                        } else {
                            $this->response['error'] = true;
                            $this->response['message'] = '<b class="text-danger">Product is not deliverable on "' . $zipcode . '"</b>';
                            echo json_encode($this->response);
                            return false;
                        }
                    }
                } else {
                    $this->response['error'] = true;
                    $this->response['message'] = '<b class="text-danger">Cannot deliver to "' . $zipcode . '".</b>';
                    echo json_encode($this->response);
                    return false;
                }
            }
        }
    }

    public function add_faqs()
    {
        if ($this->ion_auth->logged_in()) {
            $this->product_faqs_model->add_product_faqs($_POST);
            $this->response['error'] = false;
            $this->response['message'] = 'Faq added Succesfully';
            print_r(json_encode($this->response));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_faqs_data()
    {
        $search = $this->input->get('search');
        $response = $this->product_model->get_faqs_data($search);
        echo json_encode($response);
    }

    public function download_link_hash()
    {
        $order_item_id = $this->uri->segment(3);
        $user_id = $this->data['user']->id;
        $oreder_item_data = fetch_details('order_items', ['id' => $order_item_id], '*');

        $transaction_data = fetch_details('transactions', ['order_id' => $oreder_item_data[0]['order_id']], 'status');

        if (isset($oreder_item_data) && !empty($oreder_item_data) && isset($transaction_data) && !empty($transaction_data)) {
            if ($order_item_id == $oreder_item_data[0]['id'] && $user_id == $oreder_item_data[0]['user_id']) {
                if ($transaction_data[0]['status'] == 'success') {
                    $file = $oreder_item_data[0]['hash_link'];
                    $file = explode("?", $file);
                    $url = $file[0];
                    $file_name = basename($url);
                    if (preg_match('(http:|https:)', $url) === 1) {
                        $file_url = ltrim(parse_url($url, PHP_URL_PATH), '/');
                    } else {
                        $file_url = $url;
                    }
                    // Process download
                    if (file_exists($file_url)) {
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . basename($url) . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($url));
                        flush(); // Flush system output buffer
                        readfile($url);
                        update_details(['active_status' => 'delivered'], ['id' => $order_item_id], 'order_items');
                        die();
                    } else {
                        redirect($file_url);
                        // http_response_code(404);
                        // die();
                    }
                } else {
                    redirect($_SERVER['HTTP_REFERER']);
                }
            } else {
                $this->response['error'] = true;
                $this->response['message'] = 'You are not Autorized to download this item.';
                echo json_encode($this->response);

                redirect($_SERVER['HTTP_REFERER']);
            }
        } else {
            $this->response['error'] = true;
            $this->response['message'] = 'No order data found.';
            echo json_encode($this->response);

            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function test($slug = '')
    {
        echo "<pre>";
        $slug = urldecode($slug);
        $user_id = NULL;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }
        $product = fetch_product($user_id, ['slug' => $slug], NULL, NULL, NULL, NULL, NULL, NULL, NULL, '');
        $product_variant_id = $product['product'][0]['variants'][0]['id'];
        $res = get_statistics($product_variant_id);
        print_R($product_variant_id);

        print_R($res);
        // print_r($product['product'][0]['variants'][0]['id']);
    }

    /**
     * AJAX function to get filtered and paginated products
     */
    public function ajax_get_products()
    {
        $get = $this->input->get(null, true);
        $filter = [];

        /* -------- ATTRIBUTE FILTERS -------- */
        $attribute_values = '';
        $attribute_names = '';
        if (!empty($get['searchData'])) {
            $filter['search'] = $get['searchData'];
        }

        /* -------- CATEGORY -------- */
        $category_id = null;
        if (!empty($get['subCategory'])) {
            $category_id = get_category_id_by_slug($get['subCategory']);
        } elseif (!empty($get['category'])) {
            $category_values = array_filter(array_map('trim', explode('|', $get['category'])));
            $category_ids = [];
            foreach ($category_values as $category_value) {
                if (ctype_digit((string)$category_value)) {
                    $category_ids[] = (int)$category_value;
                } else {
                    $category_details = fetch_details('categories', ['slug' => urldecode($category_value)], 'id');
                    if (!empty($category_details) && isset($category_details[0]['id'])) {
                        $category_ids[] = (int)$category_details[0]['id'];
                    }
                }
            }
            $category_ids = array_values(array_unique($category_ids));
            if (!empty($category_ids)) {
                $category_id = count($category_ids) === 1 ? $category_ids[0] : $category_ids;
            }
        }

        foreach ($get as $key => $value) {
            if (strpos($key, 'filter-') === 0) {
                $attribute_values .= ($attribute_values ? '|' : '') . $value;
                $attribute_names .= ($attribute_names ? '|' : '') . str_replace('filter-', '', $key);
            }
        }

        if (!empty($attribute_values)) {
            $filter['attribute_value_ids'] = get_attribute_ids_by_value(
                explode('|', $attribute_values),
                explode('|', $attribute_names)
            );
            $filter['attribute_value_ids'] = implode(',', $filter['attribute_value_ids']);
        }

        /* -------- PRICE -------- */
        $filter['min_price'] = $get['min-price'] ?? null;
        $filter['max_price'] = $get['max-price'] ?? null;

        /* -------- BRAND -------- */
        if (!empty($get['brand'])) {
            $filter['brands'] = $get['brand'];
        }

       

        /* -------- SELLER -------- */
        $seller_id = null;
        if (!empty($get['seller'])) {
            $seller = fetch_details('seller_data', ['slug' => $get['seller']], 'user_id');
            $seller_id = $seller[0]['user_id'] ?? null;
        }

        /* -------- SORT -------- */
        $sort = '';
        $order = '';

        switch ($get['sort'] ?? '') {
            case 'top-rated':
                $filter['product_type'] = 'top_rated_product_including_all_products';
                break;
            case 'date-desc':
                $sort = 'pv.date_added';
                $order = 'DESC';
                break;
            case 'date-asc':
                $sort = 'pv.date_added';
                $order = 'ASC';
                break;
            case 'price-asc':
                $sort = 'pv.price';
                $order = 'ASC';
                break;
            case 'price-desc':
                $sort = 'pv.price';
                $order = 'DESC';
                break;
            default:
                $sort = 'p.row_order';
                $order = 'ASC';
        }

        /* -------- PAGINATION -------- */
        $limit = (isset($get['per-page']) && is_numeric($get['per-page']) && (int)$get['per-page'] > 0) ? (int)$get['per-page'] : 20;
        $page = (isset($get['page']) && is_numeric($get['page']) && (int)$get['page'] > 0) ? (int)$get['page'] : 1;
        $offset = ($page - 1) * $limit;

        /* -------- GET LOGGED-IN USER ID -------- */
        $user_id = null;
        if ($this->data['is_logged_in']) {
            $user_id = $this->data['user']->id;
        }

        /* GST enrollment restriction (P3.2): hide state-restricted sellers' products
           from customers outside the seller's state (when the customer's state is known). */
        $filter['customer_state'] = get_customer_state();

        /* -------- FETCH -------- */
        $products = fetch_product(
            $user_id,
            $filter,
            null,
            $category_id,
            $limit,
            $offset,
            $sort,
            $order,
            null,
            null,
            $seller_id
        );

        $total_rows = fetch_product(
            $user_id,
            $filter,
            null,
            $category_id,
            null,
            null,
            null,
            null,
            true,
            null,
            $seller_id
        );

        echo json_encode([
            'status' => 'success',
            'products' => $products,
            'total_rows' => $total_rows,
            'filter' => $filter,
            'limit' => $limit,
            'page' => $page
        ]);
    }


}
