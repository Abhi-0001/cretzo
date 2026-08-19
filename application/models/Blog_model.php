<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Blog_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    /*
     * Removed: get_categories() used to live here, but it SELECTed from the `categories` table
     * (product categories) while every caller believed it returned blog categories, and it
     * filtered/ordered on parent_id and row_order - columns blog_categories does not have. Its
     * only two callers (admin/Blogs::create_category and ::create_blog) assigned the result to
     * view variables that neither form template ever read, so it was dead weight on top of being
     * wrong. Blog categories are listed by get_category_list() and searched by
     * get_blog_category(), both of which query blog_categories correctly.
     */

    public function add_category($data)
    {
        $data = escape_array($data);

        // create_unique_slug() must be told which row we're editing, otherwise it finds THIS
        // category's own existing slug, treats it as a collision and mints "name-1", "name-2",
        // ... on every save - silently changing its slug each time it is edited.
        $edit_id = (isset($data['edit_category']) && !empty($data['edit_category'])) ? $data['edit_category'] : null;

        $cat_data = [
            'name' => $data['category_input_name'],
            'slug' => ($edit_id !== null)
                ? create_unique_slug($data['category_input_name'], 'blog_categories', 'slug', 'id', $edit_id)
                : create_unique_slug($data['category_input_name'], 'blog_categories'),
            'status' => '1',
        ];

        if (isset($data['edit_category'])) {
            unset($cat_data['status']);
            // Guard on !empty(), not isset(): the image field is optional on edit, so a save
            // that posts it empty used to overwrite the stored path with '' and wipe the
            // category's image. Same for banner, which was unconditionally reset to '' whenever
            // the form didn't happen to include the field.
            if (!empty($data['category_input_image'])) {
                $cat_data['image'] = $data['category_input_image'];
            }
            if (!empty($data['banner'])) {
                $cat_data['banner'] = $data['banner'];
            }

            $this->db->set($cat_data)->where('id', $data['edit_category'])->update('blog_categories');
        } else {
            // blog_categories.image and .banner are both NOT NULL with no default - omitting
            // either from the INSERT fails outright under STRICT_TRANS_TABLES, so always
            // supply a value.
            $cat_data['image'] = (!empty($data['category_input_image'])) ? $data['category_input_image'] : '';
            $cat_data['banner'] = (!empty($data['banner'])) ? $data['banner'] : '';
            $this->db->insert('blog_categories', $cat_data);
        }
    }



    public function get_category_list($seller_id = NULL)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';

        // Was $where['parent_id'] = $_GET['id'] - copy-pasted from the PRODUCT category list.
        // blog_categories has no parent_id column (it is a flat list), so any request carrying
        // ?id= died with "Unknown column 'parent_id' in 'where clause'". Filter on the row's own
        // id instead, which is the only thing an ?id= could sensibly mean here.
        if (isset($_GET['id']) && is_numeric($_GET['id']))
            $where['id'] = (int) $_GET['id'];
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['id', 'name', 'status'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['`id`' => $search, '`name`' => $search];
        }
        if (isset($seller_id) && $seller_id != "") {
            $this->db->select('category_ids');
            $where1 = 'user_id = ' . $seller_id;
            $this->db->where($where1);
            $result = $this->db->get('seller_data')->result_array();
            $cat_ids = explode(',', $result[0]['category_ids']);
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }

        // The count query skipped $where entirely, so the pagination total ignored the ?id=
        // filter that the data query below applies - the footer count disagreed with the rows.
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        if (isset($seller_id) && $seller_id != "") {
            $count_res->where_in('id', $cat_ids);
        }

        $cat_count = $count_res->get('blog_categories')->result_array();
        $total = 0;
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        // Was $count_res->where_in() - applied to the already-executed COUNT builder instead of
        // the data query, so a seller-scoped call still listed every blog category.
        if (isset($seller_id) && $seller_id != "") {
            $search_res->where_in('id', $cat_ids);
        }

        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('blog_categories')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($cat_search_res as $row) {

            if (!$this->ion_auth->is_seller()) {
                $operate = '<a href="' . base_url('admin/blogs/create_category' . '?edit_id=' . $row['id']) . '" class=" btn action-btn btn-success btn-xs ml-1 mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/blogs/create_category"><i class="fa fa-pen"></i></a>';
                $operate .= '<a class="delete-blog-category action-btn btn btn-danger btn-xs ml-1 mr-1 mb-1" title="Delete" href="javascript:void(0)" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            }

            if ($row['status'] == '1') {
                $tempRow['status'] = '<a class="badge badge-success text-white" >Active</a>';
                if (!$this->ion_auth->is_seller()) {
                    $operate .= '<a class="btn btn-warning btn-xs action-btn update_active_status ml-1 mr-1 mb-1" data-table="blog_categories" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye-slash"></i></a>';
                }
            } else {
                $tempRow['status'] = '<a class="badge badge-danger text-white" >Inactive</a>';
                if (!$this->ion_auth->is_seller()) {
                    $operate .= '<a class="btn btn-primary mr-1 mb-1 ml-1 action-btn btn-xs update_active_status" data-table="blog_categories" href="javascript:void(0)" title="Active" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye"></i></a>';
                }
            }

            $tempRow['id'] = $row['id'];
            // output_escaping() only strips backslash-escaping, it does not HTML-encode -
            // a stored-XSS route the same as already fixed on other list pages.
            // The href pointed at admin/category (the PRODUCT category module) with a blog
            // category's id - clicking a blog category name landed on an unrelated, wrong page.
            // Point it at this category's own edit form, which is what the row's Edit button uses.
            $tempRow['name'] = '<a href="' . base_url('admin/blogs/create_category?edit_id=' . $row['id']) . '">' . html_escape($row['name']) . '</a>';

            if (empty($row['image']) || file_exists(FCPATH  . $row['image']) == FALSE) {
                $row['image'] = base_url() . NO_IMAGE;
                $row['image_main'] = base_url() . NO_IMAGE;
            } else {
                $row['image_main'] = base_url($row['image']);
                $row['image'] = get_image_url($row['image'], 'thumb', 'sm');
            }
            $tempRow['image'] = "<div class='image-box-100' ><a href='" . $row['image_main'] . "' data-toggle='lightbox' data-gallery='gallery'> <img class='rounded' src='" . $row['image'] . "' ></a></div>";

            if (empty($row['banner']) || file_exists(FCPATH  . $row['banner']) == FALSE) {
                $row['banner'] = base_url() . NO_IMAGE;
                $row['banner_main'] = base_url() . NO_IMAGE;
            } else {
                $row['banner_main'] = base_url($row['banner']);
                $row['banner'] = get_image_url($row['banner'], 'thumb', 'sm');
            }
            $tempRow['banner'] = "<div class='image-box-100' ><a href='" . $row['banner_main'] . "' data-toggle='lightbox' data-gallery='gallery'> <img src='" . $row['banner'] . "' class='rounded'></a></div>";

            if (!$this->ion_auth->is_seller()) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }



    public function add_blog($data)
    {
        $data = escape_array($data);

        // create_unique_slug() must be told which row we're editing, otherwise it finds THIS
        // post's own existing slug, treats it as a collision and mints "title-1", "title-2",
        // ... on every save - silently changing the post's public URL
        // (blogs/view_detail/<slug>) each time it is edited.
        $edit_id = (isset($data['edit_blog']) && !empty($data['edit_blog'])) ? $data['edit_blog'] : null;

        $blog_data = [
            'title' => $data['blog_title'],
            'category_id' => $data['blog_category'],
            'image' => $data['blog_image'],
            'description' => $data['blog_description'],
            'slug' => ($edit_id !== null)
                ? create_unique_slug($data['blog_title'], 'blogs', 'slug', 'id', $edit_id)
                : create_unique_slug($data['blog_title'], 'blogs'),
            'status' => '1',
        ];

        // Both branches used to re-read the image from 'category_input_image' - the BLOG
        // CATEGORY form's field name, which this form never posts. Dead on every request; the
        // real value is 'blog_image', already assigned above. Keep only a !empty() guard so a
        // save that somehow posts an empty path can't blank a stored image.
        if (empty($blog_data['image'])) {
            unset($blog_data['image']);
        }

        if ($edit_id !== null) {
            unset($blog_data['status']);
            $this->db->set($blog_data)->where('id', $edit_id)->update('blogs');
        } else {
            if (!isset($blog_data['image'])) {
                $blog_data['image'] = '';
            }
            $this->db->insert('blogs', $blog_data);
        }
    }

    function get_blog_category($search_term = "")
    {
        // $search_term used to be spliced directly into a raw WHERE string ("name like '%...%'"),
        // bypassing the query builder's escaping entirely - a real SQL injection reachable at
        // admin/blogs/get_blog_category?search=... by any logged-in staff member. like() escapes
        // the value and builds the same query safely.
        $this->db->select('name,id');
        $this->db->like('name', $search_term);
        $this->db->where("status", 1);

        $fetched_records = $this->db->get('blog_categories');
        $categories = $fetched_records->result_array();
        
        // Initialize Array with fetched data
        $data = array();
        foreach ($categories as $categories) {
            $data[] = array("id" => $categories['id'], "text" => $categories['name']);
        }
        return $data;
    }


    public function get_blogs_list($seller_id = NULL)
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';
        // Read unguarded before - a direct call to this endpoint without category_id (the
        // page's own JS always sends it, even as "", but nothing else calling this is
        // guaranteed to) raised an undefined-index warning that, on a server configured to
        // display errors, gets prepended to the JSON response and breaks the AJAX parse.
        $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';

        if (!empty($category_id)) {
            $where['category_id'] = $category_id;
        }

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['id', 'title', 'category_id'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['`id`' => $search, '`description`' => $search, '`category_id`' => $search, '`title`' => $search];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }

        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $cat_count = $count_res->get('blogs')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        // limit()'s 2nd param is the offset - it takes no 3rd argument, so the $category_id
        // passed here was always silently ignored (filtering by category is already handled
        // above via $where['category_id']).
        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('blogs')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($cat_search_res as $row) {
            $category_id = $row['category_id'];
            $category_name =  fetch_details('blog_categories', "", 'name,id', "", "", "", "", "id", $category_id);
            // print_r($category_name[0]['name']);
            if (!$this->ion_auth->is_seller()) {
                $operate = '<a href="' . base_url('admin/blogs/create_blog' . '?edit_id=' . $row['id']) . '" class=" btn btn-success btn-xs action-btn mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/blogs/create_blog"><i class="fa fa-pen"></i></a>';
                $operate .= '<a class="delete-blog btn action-btn btn-danger btn-xs mr-1 mb-1" title="Delete" href="javascript:void(0)" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            }

            if ($row['status'] == '1') {
                $tempRow['status'] = '<a class="badge badge-success text-white" >Active</a>';
                if (!$this->ion_auth->is_seller()) {
                    $operate .= '<a class="btn action-btn btn-warning btn-xs update_active_status mr-1 mb-1" data-table="blogs" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye-slash"></i></a>';
                }
            } else {
                $tempRow['status'] = '<a class="badge badge-danger text-white" >Inactive</a>';
                if (!$this->ion_auth->is_seller()) {
                    $operate .= '<a class="btn action-btn btn-primary mr-1 mb-1 btn-xs update_active_status" data-table="blogs" href="javascript:void(0)" title="Active" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye"></i></a>';
                }
            }

            $tempRow['id'] = $row['id'];
            foreach ($category_name as $categories) {

                $tempRow['blog_category'] = $categories['name'];
            }
            // Title was rendered completely raw (no escaping call at all); description only had
            // output_escaping() (backslash-stripping, not real HTML encoding) applied. Both are
            // author-controlled - a stored-XSS route on this admin list, same as already fixed
            // elsewhere.
            $tempRow['title'] = html_escape($row['title']);
            $tempRow['description'] = html_escape(description_word_limit(str_replace('\r\n', '&#13;&#10;', $row['description'])));

            if (empty($row['image']) || file_exists(FCPATH  . $row['image']) == FALSE) {
                $row['image'] = base_url() . NO_IMAGE;
                $row['image_main'] = base_url() . NO_IMAGE;
            } else {
                $row['image_main'] = base_url($row['image']);
                $row['image'] = get_image_url($row['image'], 'thumb', 'sm');
            }
            $tempRow['image'] = "<div class='image-box-100' ><a href='" . $row['image_main'] . "' data-toggle='lightbox' data-gallery='gallery'> <img class='rounded' src='" . $row['image'] . "' ></a></div>";


            if (!$this->ion_auth->is_seller()) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }


    function get_blogs($offset, $limit, $sort, $order, $search = NULL, $category_id = NULL)
    {
        $blog_data = [];
        $multipleWhere = '';

        $where['b.status'] = '1';
        if (isset($category_id) && !empty($category_id)) {
            $where['b.category_id'] = $category_id;
        }
        if (isset($search) and $search != '') {
            $multipleWhere = ['b.title' => $search, 'b.slug' => $search];
        }

        // A post whose parent blog category has been deactivated used to stay fully visible on
        // the storefront - nothing here or on the detail page ever consulted
        // blog_categories.status, so unpublishing a whole category had no effect at all. Joined
        // LEFT and allowed through when there is no category row, so an orphaned post (deleted
        // category) still lists rather than silently vanishing.
        $apply = function ($builder) use ($where, $multipleWhere) {
            $builder->join('blog_categories bc', 'bc.id = b.category_id', 'left');
            if (!empty($multipleWhere)) {
                $builder->group_start()->or_like($multipleWhere)->group_end();
            }
            $builder->where($where);
            $builder->group_start()->where('bc.status', 1)->or_where('bc.id IS NULL', null, false)->group_end();
            return $builder;
        };

        $count_res = $apply($this->db->select(' COUNT(b.id) as `total` '))->get('blogs b')->result_array();

        // $sort/$order arrive as NULL from Blogs::index, and CI's order_by() returns early on an
        // empty field - so the listing came back in whatever order MySQL felt like, which means
        // paging through it could repeat or skip posts. Order newest-first by default, and only
        // honour an explicit sort against a whitelist.
        $allowed_sort = ['id', 'title', 'date_added'];
        $sort_col = (in_array((string) $sort, $allowed_sort, true)) ? 'b.' . $sort : 'b.date_added';
        $sort_dir = (strtolower((string) $order) === 'asc') ? 'ASC' : 'DESC';

        $search_res = $apply($this->db->select(' b.* '))
            ->order_by($sort_col, $sort_dir)->limit($limit, $offset)->get('blogs b')->result_array();
        if (!empty($search_res)) {
            for ($i = 0; $i < count($search_res); $i++) {
                $search_res[$i] = output_escaping($search_res[$i]);
            }
        }
        $blog_data['total'] = $count_res[0]['total'];
        $blog_data['data'] = $search_res;
        return  $blog_data;
    }
}
