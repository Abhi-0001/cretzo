<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Slider_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function add_slider($data)
    {
        $data = escape_array($data);

        // type_id and link are now BOTH always written, for every type. Previously type_id was
        // only assigned inside the per-type branches, so:
        //   - a 'default' (plain image) slider never set type_id at all on insert, and
        //   - editing a slider from 'products'/'categories' to any other type left the OLD
        //     type_id in the row, pointing the record at a product/category it no longer means.
        // resolve_banner_target() keys off (type, type_id) to decide whether a banner is still
        // reachable, so a stale type_id is not harmless bookkeeping - it can resurrect a link
        // the admin thought they had removed.
        $slider_type = isset($data['slider_type']) ? $data['slider_type'] : '';
        $slider_data = [
            'type' => $slider_type,
            'image' => $data['image'],
            'link' => '',
            'type_id' => 0,
        ];

        if ($slider_type == 'categories' && !empty($data['category_id'])) {
            $slider_data['type_id'] = $data['category_id'];
        } elseif ($slider_type == 'products' && !empty($data['product_id'])) {
            $slider_data['type_id'] = $data['product_id'];
        } elseif ($slider_type == 'slider_url' && !empty($data['link'])) {
            $slider_data['link'] = $data['link'];
        }

        if (isset($data['edit_slider'])) {
            if (empty($data['image'])) {
                unset($slider_data['image']);
            }

            $this->db->set($slider_data)->where('id', $data['edit_slider'])->update('sliders');
        } else {
            $this->db->insert('sliders', $slider_data);
        }
    }

    function get_slider_list()
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['id', 'type', 'type_id', 'image', 'link'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'desc';
        } else {
            $order = 'asc';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['`id`' => $search, '`type`' => $search];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        // Was or_where() here (exact match) while the data query below uses or_like() (partial
        // match) - a partial search term matched real rows in the data query but zero rows in
        // the count query, so the pagination footer/total disagreed with what was actually shown.
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $slider_count = $count_res->get('sliders')->result_array();

        foreach ($slider_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $slider_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('sliders')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($slider_search_res as $row) {
            $row = output_escaping($row);

            $operate = ' <a href="' . base_url('admin/slider?edit_id=' . $row['id']) . '" class="btn btn-success action-btn btn-xs ml-1 mr-1 mb-1"  title="Edit" data-id="' . $row['id'] . '" data-url="admin/slider/"><i class="fa fa-pen"></i></a>';
            $operate .= ' <a  href="javascript:void(0)" class="btn btn-danger btn-xs action-btn mr-1 mb-1 ml-1"  title="Delete" id="delete-slider" data-id="' . $row['id'] . '"  ><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row['id'];
            $tempRow['type'] = html_escape($row['type']);
            $tempRow['type_id'] = $row['type_id'];
            // output_escaping() only strips backslash-escaping, it does not HTML-encode - link
            // is an admin-entered URL that gets rendered raw into the table, a stored-XSS route
            // the same as already fixed on other list pages.
            $tempRow['link'] = html_escape($row['link']);

            if (empty($row['image']) || file_exists(FCPATH . $row['image']) == FALSE) {
                $row['image'] = base_url() . NO_IMAGE;
                $row['image_main'] = base_url() . NO_IMAGE;
            } else {
                $row['image_main'] = base_url($row['image']);
                $row['image'] = get_image_url($row['image'], 'thumb', 'sm');
            }
            $tempRow['image'] = "<div class='image-box-100'><a href='" . $row['image_main'] . "' data-toggle='lightbox' data-gallery='gallery'> <img src='" . $row['image'] . "' class='rounded' ></a></div>";
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
