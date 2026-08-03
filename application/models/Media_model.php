<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Media_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function set_media($data, $seller_id = 0)
    {
        $data = escape_array($data);
        $extenstion = trim($data['file_ext'], '.');
        $extenstionData = find_media_type($extenstion);
        $media_type = $extenstionData[0];
        if (empty($seller_id))
            $seller_id = ($this->ion_auth->is_seller()) ? $this->session->userdata('user_id') : 0;
        $data = [
            'name' => $data['file_name'],
            'seller_id' => $seller_id,
            'extension' => ltrim($data['file_ext'], '.'),
            'title' => $data['raw_name'],
            'type' => ($media_type != false) ? $media_type : 'other',
            'size' => $data['file_size'],
            'sub_directory' => $data['sub_directory'],
        ];

        $this->db->insert('media', $data);
        $insert_id = $this->db->insert_id();
        return  $insert_id;
    }

    function get_media_by_id($id)
    {
        $this->db->where('id', $id);
        $q = $this->db->get('media');
        return $q->result_array();
    }

    /**
     * Whether this file's path is still referenced anywhere in the site. There is no real
     * foreign key between the media library and where an image actually gets used - every
     * consuming table just stores the same relative path (sub_directory.name) as a plain
     * string - so this is the only way to answer "is it safe to delete this?" Deleting a file
     * still in use here would silently break a live product/category/brand/etc image with no
     * warning, since get_image_url() just falls back to a placeholder for a missing file.
     * Checked against the tables that actually store an image-path column, per the schema;
     * JSON/CSV array columns (other_images, product_variants.images, etc.) are matched with
     * LIKE since the path is a substring of the stored array, not an exact match.
     */
    public function is_media_in_use($relative_path)
    {
        $exact_match_columns = [
            'blogs' => ['image'],
            'blog_categories' => ['image', 'banner'],
            'brands' => ['image'],
            'categories' => ['image', 'banner'],
            'notifications' => ['image'],
            'offers' => ['image'],
            'products' => ['image'],
            'promo_codes' => ['image'],
            'seller_data' => ['logo', 'store_banner'],
            'sliders' => ['image'],
            'themes' => ['image'],
            'users' => ['image'],
        ];
        foreach ($exact_match_columns as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->db->where($column, $relative_path)->count_all_results($table) > 0) {
                    return true;
                }
            }
        }

        $like_match_columns = [
            'products' => ['other_images'],
            'product_rating' => ['images'],
            'product_variants' => ['images'],
        ];
        foreach ($like_match_columns as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->db->like($column, $relative_path)->count_all_results($table) > 0) {
                    return true;
                }
            }
        }

        return false;
    }


    public function fetch_media($fromSeller = false, $seller_id = null)
    {
        if (($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) || ($this->ion_auth->logged_in() && $this->ion_auth->is_seller() && ($this->ion_auth->seller_status() == 1 || $this->ion_auth->seller_status() == 0))) {

            $multipleWhere = $where_in = '';
            $offset = 0;
            $limit = 10;
            $sort = 'id';
            $order = 'DESC';

            if (isset($_GET['offset']))
                $offset = $_GET['offset'];
            if (isset($_GET['limit']))
                $limit = $_GET['limit'];

            // Sort column was passed straight into order_by() with no whitelist - an injection
            // route the same as already fixed on other list pages.
            $allowed_sort_columns = ['id', 'name', 'size', 'extension', 'sub_directory', 'date_created'];
            if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
                $sort = $_GET['sort'];
            }
            if (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') {
                $order = 'ASC';
            }

            if (isset($_GET['search']) and $_GET['search'] != '') {
                $search = $_GET['search'];
                $multipleWhere = ['id' => $search, 'name' => $search];
            }
            if (isset($_GET['type']) and $_GET['type'] != '') {
                // $where['type'] = trim(strtolower($_GET['type']));
                $type = explode(",", $this->input->get('type'));
                $where_in = $type;
            }
           
            // seller_id must come from the authenticated session (passed in as $seller_id
            // by the seller controller), never from the client-supplied $_GET value —
            // otherwise any seller could browse every other seller's media library just
            // by changing the seller_id query param.
            if ($fromSeller == true && !empty($seller_id)) {
                $where['seller_id'] = $seller_id;
            }
            $count_res = $this->db->select(' COUNT(id) as `total` ');

            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $count_res->or_like($multipleWhere);
            }
            if (isset($where) && !empty($where)) {
                $count_res->where($where);
            }
            if(isset($where_in) && !empty($where_in)){
                $count_res->where_in("type", $where_in);
            }
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {

                $count_res->where("DATE(date_created) >=", date('Y-m-d', strtotime($_GET['start_date'])));
                $count_res->where("DATE(date_created) <=", date('Y-m-d', strtotime($_GET['end_date'])));
            }
            $attr_count = $count_res->get('media')->result_array();

            foreach ($attr_count as $row) {
                $total = $row['total'];
            }

            $search_res = $this->db->select('*');
            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $search_res->or_like($multipleWhere);
            }
            if (isset($where) && !empty($where)) {
                $search_res->where($where);
            }

            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {

                $search_res->where("DATE(date_created) >=", date('Y-m-d', strtotime($_GET['start_date'])));
                $search_res->where("DATE(date_created) <=", date('Y-m-d', strtotime($_GET['end_date'])));
            }
            
            if(isset($where_in) && !empty($where_in)){
                $search_res->where_in("type", $where_in);
            }

            $city_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('media')->result_array();
            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $tempRow = array();

            $i = 0;
            foreach ($city_search_res as $row) {
                $operate = "";
                if ($this->ion_auth->is_seller() && $row['seller_id'] == $this->session->userdata('user_id')) {
                    $operate = '<a href="javascript:void(0);" class="delete-media action-btn btn btn-danger btn-xs ml-1 mr-1 mb-1" title="Delete" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
                }
                // Only checked is_admin(), not this admin's own delete permission on the media
                // module - the button showed for every admin regardless, even though the server
                // side (Media::delete()) already correctly blocked a restricted admin from
                // actually using it. Harmless as a security matter, but confusing to show a
                // working-looking button that silently errors on click.
                if ($this->ion_auth->is_admin() && has_permissions('delete', 'media')) {
                    $operate = '<a href="javascript:void(0);" class="delete-media action-btn btn btn-danger btn-xs ml-1 mr-1 mb-1" title="Delete" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
                }
                $operate .= '<a href="javascript:void(0);" class="copy-to-clipboard btn btn-primary btn-xs action-btn ml-1 mr-1 mb-1" title="Copy to clipboard" ><i class="fa fa-copy"></i></a>';
                // data-path's value used to be unquoted - harmless today only because
                // Security::sanitize_filename() already strips the characters that would be
                // needed to break out of an unquoted attribute at upload time, but that's an
                // upstream side effect, not a defense this line actually provides itself.
                $operate .= '<a href="javascript:void(0);" class="btn btn-info btn-xs mr-1 mb-1 ml-1 action-btn copy-relative-path" data-path="' . html_escape($row['sub_directory'] . $row['name']) . '" title="Copy image path for csv file"><i class="fa fa-copy"></i></a>';

                $tempRow['id'] = $row['id'];
                $tempRow['seller_id'] = $row['seller_id'];
                $tempRow['name'] = $row['name'];
                if (file_exists(FCPATH . $row['sub_directory'] . $row['name'])) {
                    $row['image'] = get_image_url($row['sub_directory'] . $row['name'], 'thumb', 'sm', trim(strtolower($row['type'])));
                } else {
                    $row['image'] = base_url() . NO_IMAGE;
                }

                $tempRow['image'] = '<div class="image-upload-div image-box-100 text-center"><span class="path d-none">' . base_url() . $row['sub_directory'] . $row['name'] . '</span><span class="relative-path d-none">' . $row['sub_directory'] . $row['name'] . '</span><a href="' . $row['image'] . '" data-toggle="lightbox" data-gallery="gallery" ><img class="rounded" src="' .  $row['image'] . '" ></a></div>';


                $tempRow['extension'] = $row['extension'];
                $tempRow['seller_id'] = $row['seller_id'];
                $tempRow['sub_directory'] = $row['sub_directory'];
                $tempRow['size'] = ($row['size'] > 1) ? formatBytes($row['size']) : $row['size'];
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
                $i++;
            }
            $bulkData['rows'] = $rows;
            print_r(json_encode($bulkData));
        } else {
            redirect('admin/login', 'refresh');
        }
    }

    public function get_media($limit = "", $offset = '', $sort = 'id', $order = 'DESC', $search = NULL, $type = "", $seller_id = NULL)
    {

        $multipleWhere = '';

        if (isset($search) and $search != '') {
            $multipleWhere = ['id' => $search, 'name' => $search];
        }

        // if (isset($type) and $type != '') {
        //     $where['type'] = trim(strtolower($type));
        // }
        if (isset($type) and $type != '') {
            // $where['type'] = trim(strtolower($_GET['type']));
            $media_type = explode(",", $type);
            $where_in = $media_type;
        }
        if (isset($seller_id) and $seller_id != '') {
            $where['seller_id'] = $seller_id;
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }
        if(isset($where_in) && !empty($where_in)){
            $count_res->where_in("type", $where_in);
        }
        $attr_count = $count_res->get('media')->result_array();

        foreach ($attr_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('*');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }
        if(isset($where_in) && !empty($where_in)){
            $search_res->where_in("type", $where_in);
        }

        $city_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('media')->result_array();
        $bulkData = array();
        $bulkData['error'] = (empty($city_search_res)) ? true : false;
        $bulkData['message'] = (empty($city_search_res)) ? 'Media(s) does not exist' : 'Media retrieved successfully';
        $bulkData['total'] = (empty($city_search_res)) ? 0 : $total;
        $rows = $tempRow = array();
        $i = 0;
        foreach ($city_search_res as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['seller_id'] = $row['seller_id'];
            $tempRow['name'] = $row['name'];
            if (file_exists(FCPATH . $row['sub_directory'] . $row['name'])) {
                $row['image'] = get_image_url($row['sub_directory'] . $row['name'], 'thumb', 'sm', trim(strtolower($row['type'])));
            } else {
                $row['image'] = base_url() . NO_IMAGE;
            }
            $tempRow['image'] =  base_url() . $row['sub_directory'] . $row['name'];
            $tempRow['extension'] = $row['extension'];
            $tempRow['seller_id'] = $row['seller_id'];
            $tempRow['sub_directory'] = $row['sub_directory'];
            $tempRow['relative_path'] = $row['sub_directory'] . $row['name'];
            $tempRow['size'] = ($row['size'] > 1) ? formatBytes($row['size']) : $row['size'];
            $rows[] = $tempRow;
            $i++;
        }
        $bulkData['data'] = $rows;
        print_r(json_encode($bulkData));
    }
}
