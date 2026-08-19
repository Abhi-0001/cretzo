<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Featured_section_model extends CI_Model
{
    function add_featured_section($data)
    {

        $data = escape_array($data);
       
        if(isset($data['product_ids']) && !empty($data['product_ids']) && trim($data['product_type']) == 'custom_products'){
            $product_ids = implode(',', $data['product_ids']);
        }elseif(isset($data['digital_product_ids']) && !empty($data['digital_product_ids']) && trim($data['product_type']) == 'digital_product'){
            $product_ids = implode(',', $data['digital_product_ids']);
        }
        else{
            $product_ids = null;
        }
       
        $featured_data = [
            'title' => $data['title'],
            'short_description' => $data['short_description'],
            'product_type' => $data['product_type'],
            'categories' => (isset($data['categories']) && !empty($data['categories'])) ? implode(',', $data['categories']) : null,
            'product_ids' => $product_ids,
            'style' => $data['style']
        ];
        
        if (isset($data['edit_featured_section'])) {
            if (strtolower(trim($data['product_type'])) != 'custom_products' && trim($data['product_type']) != 'digital_product') {
                $featured_data['product_ids'] = null;
            }
            $this->db->set($featured_data)->where('id', $data['edit_featured_section'])->update('sections');
        } else {
            $this->db->insert('sections', $featured_data);
        }
    }
    public function get_section_list()
    {
        $offset = 0;
        $limit = 10;
        // Defaulted to 'u.id', copy-pasted from a users-table list method - this table has no
        // 'u' alias, so any call reaching order_by() without an explicit $_GET['sort'] (bypassing
        // the bootstrap-table UI, which always sends one) would throw an "Unknown column" error.
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['id', 'title', 'short_description', 'style', 'product_type', 'status', 'date_added'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'DESC';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = ['id' => $search, 'title' => $search, 'short_description' => $search];
        }

        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }

        $city_count = $count_res->get('sections')->result_array();

        foreach ($city_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }

        // $order was read from $_GET but the query always hardcoded "asc" - clicking a
        // sortable column header to sort descending had no effect.
        $city_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('sections')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($city_search_res as $row) {
            $row = output_escaping($row);

            $operate = ' <a href="javascript:void(0)" class="edit_btn action-btn btn btn-primary btn-xs ml-1 mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/Featured_sections/"><i class="fa fa-pen"></i></a>';
            $operate .= ' <a  href="javascript:void(0)" class="btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1" title="Delete" data-id="' . $row['id'] . '" id="delete-featured-section" ><i class="fa fa-trash"></i></a>';

            // Featured sections had no publish/unpublish control at all - creating one pushed it
            // live to the homepage and the only way to take it down was to delete it, losing its
            // title, style, categories and hand-picked product list. Backed by the status column
            // added in migration 046.
            $status = isset($row['status']) ? $row['status'] : 1;
            if ($status == '1') {
                $tempRow['status'] = '<a class="badge badge-success text-white">Active</a>';
                $operate .= ' <a class="btn btn-warning btn-xs action-btn update_active_status ml-1 mr-1 mb-1" data-table="sections" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $status . '" ><i class="fa fa-eye-slash"></i></a>';
            } else {
                $tempRow['status'] = '<a class="badge badge-danger text-white">Inactive</a>';
                $operate .= ' <a class="btn btn-primary btn-xs action-btn update_active_status ml-1 mr-1 mb-1" data-table="sections" title="Activate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $status . '" ><i class="fa fa-eye"></i></a>';
            }

            $tempRow['id'] = $row['id'];
            // output_escaping() only strips backslash-escaping, it does not HTML-encode - a
            // stored-XSS route the same as already fixed on other list pages.
            $tempRow['title'] = html_escape($row['title']);
            $tempRow['short_description'] = html_escape($row['short_description']);
            $tempRow['style'] = ucfirst(str_replace('_', ' ', $row['style']));
            $tempRow['product_ids'] = $row['product_ids'];
            $tempRow['categories'] = $row['categories'];
            $tempRow['product_type'] = ucwords(str_replace('_', ' ', $row['product_type']));
            $tempRow['date'] = $row['date_added'];
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
