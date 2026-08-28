<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Blogs extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'language', 'timezone_helper']);
        $this->load->model(['blog_model']);
        $this->data['is_logged_in'] = ($this->ion_auth->logged_in()) ? 1 : 0;
        $this->data['user'] = ($this->ion_auth->logged_in()) ? $this->ion_auth->user()->row() : array();
        $this->data['settings'] = get_settings('system_settings', true);
        $this->data['web_settings'] = get_settings('web_settings', true);
        $this->load->library(['pagination']);
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
        $limit = ($this->input->get('per-page')) ? $this->input->get('per-page', true) : 12;
        $category_id = ($this->input->get('category_id')) ? $this->input->get('category_id', true) : NULL;
        $blog_search = ($this->input->get('blog_search')) ? $this->input->get('blog_search', true) : '';
        $total_rows = $this->blog_model->get_blogs(null, null, null, null, $blog_search, $category_id);
        $page_no = (empty($this->uri->segment(2))) ? 1 : $this->uri->segment(2);
        if (!is_numeric($page_no)) {
            redirect(base_url('blogs'));
        }

        $offset = ($page_no - 1) * $limit;
        $this->data['links'] = storefront_pagination(base_url('blogs'), $total_rows['total'], $limit);
        $this->data['main_page'] = 'blogs';
        $this->data['title'] = 'Blogs | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'Blogs, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'Blogs | ' . $this->data['web_settings']['meta_description'];
        $this->data['meta_description'] = 'Blogs | ' . $this->data['web_settings']['site_title'];
        $this->data['blog_search'] = $blog_search;
        $this->data['blogs'] = $this->blog_model->get_blogs($offset, $limit, null, null, $blog_search, $category_id);
        // Had no status filter, so the storefront's "Filter By Category" dropdown listed
        // categories the admin had deactivated - and picking one returned nothing.
        $this->data['fetched_data'] = fetch_details('blog_categories', ['status' => 1]);
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }

    public function view_detail()
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

        $this->data['main_page'] = 'view_blog';
        $this->data['title'] = 'View Blog | ' . $this->data['web_settings']['site_title'];
        $this->data['keywords'] = 'View Blog, ' . $this->data['web_settings']['meta_keywords'];
        $this->data['description'] = 'View Blog | ' . $this->data['web_settings']['meta_description'];
        $this->data['meta_description'] = 'View Blog | ' . $this->data['web_settings']['site_title'];
        $blog_id = $this->uri->segment(3);
        // Two bugs here:
        //   1. No status filter, so a post the admin had UNPUBLISHED stayed fully readable at
        //      its direct URL - the toggle only ever removed it from the listing (get_blogs()
        //      does filter status = 1). Also honour the parent category's status, which nothing
        //      on the storefront consulted, so posts in a deactivated category stayed live too.
        //   2. No empty guard. The view dereferences $blog[0] unconditionally, so ANY unknown
        //      slug rendered a 200 page with "Undefined array key 0" warnings printed into it
        //      (this app ships with display_errors on). Now a 404, like a missing product.
        $blog = $this->db->select('b.id,b.title,b.description,b.image,b.slug,b.date_added')
            ->join('blog_categories bc', 'bc.id = b.category_id', 'left')
            ->where('b.slug', $blog_id)
            ->where('b.status', 1)
            ->group_start()
            ->where('bc.status', 1)
            ->or_where('bc.id IS NULL', null, false)
            ->group_end()
            ->get('blogs b')->result_array();

        if (empty($blog)) {
            show_404();
            return;
        }

        $this->data['blog'] = $blog;
        $this->data['title'] = $blog[0]['title'] . ' | ' . $this->data['web_settings']['site_title'];
        $this->load->view('front-end/' . THEME . '/template', $this->data);
    }
}
