<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Brand_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }


    public function add_brand($data)
    {
        $data = escape_array($data);

        // create_unique_slug() must be told which row we're editing, otherwise it finds THIS
        // brand's own existing slug, treats it as a collision and mints "name-1", "name-2", ...
        // on every save - silently changing the brand's public URL each time it is edited.
        $edit_id = (isset($data['edit_brand']) && !empty($data['edit_brand'])) ? $data['edit_brand'] : null;

        $brands_data = [
            'name' => $data['brand_input_name'],
            'slug' => ($edit_id !== null)
                ? create_unique_slug($data['brand_input_name'], 'brands', 'slug', 'id', $edit_id)
                : create_unique_slug($data['brand_input_name'], 'brands'),
            'status' => '1',
        ];

        $this->db->trans_start();

        if (isset($data['edit_brand'])) {
            unset($brands_data['status']);
            if (isset($data['brand_input_image'])) {
                $brands_data['image'] = $data['brand_input_image'];
            }

            // products.brand is a plain string column (no foreign key - see migration 008),
            // matched by name via get_brands()'s join. Renaming a brand without propagating the
            // new name here would silently detach every product that referenced the old name -
            // they'd still exist, but stop showing under this brand anywhere the join is used.
            $old_brand = fetch_details('brands', ['id' => $data['edit_brand']], 'name');
            $old_name = isset($old_brand[0]['name']) ? $old_brand[0]['name'] : null;

            $this->db->set($brands_data)->where('id', $data['edit_brand'])->update('brands');

            if ($old_name !== null && $old_name !== $brands_data['name']) {
                $this->db->set('brand', $brands_data['name'])->where('brand', $old_name)->update('products');
            }
        } else {
            if (isset($data['brand_input_image'])) {
                $brands_data['image'] = $data['brand_input_image'];
            }
            $this->db->insert('brands', $brands_data);
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() !== false);
    }



    public function delete_brand($id)
    {
        $id = escape_array($id);

        $brand = fetch_details('brands', ['id' => $id], 'name');
        if (empty($brand[0]['name'])) {
            return ['success' => false, 'message' => 'Brand does not exist!'];
        }

        // Same reasoning as the rename guard above: products.brand is a plain string column
        // with no foreign key, so deleting a brand still referenced by products would silently
        // orphan them (they'd keep their old brand name, but it would no longer match anything).
        $products_using_brand = $this->db->where('brand', $brand[0]['name'])->count_all_results('products');
        if ($products_using_brand > 0) {
            return ['success' => false, 'message' => 'This brand is still assigned to ' . $products_using_brand . ' product(s). Reassign them to another brand before deleting.'];
        }

        $this->db->delete('brands', ['id' => $id]);
        return ['success' => ($this->db->affected_rows() > 0), 'message' => 'Deleted Succesfully'];
    }

    public function get_brands($id = NULL, $limit = '', $offset = '', $sort = 'row_order', $order = 'ASC')
    {
        $this->db->select('b.id as brand_id, b.name as brand_name, b.slug as brand_slug, b.image as brand_img');

        $this->db->join('products p', 'p.brand = b.name', 'left');

        $this->db->group_by('b.id');

        if (!empty($limit) || !empty($offset)) {
            $this->db->offset($offset);
            $this->db->limit($limit);
        }

        // brands has no row_order column (see migration 008_brands.php) even though every
        // caller in this codebase passes 'row_order' as $sort - that made this call guaranteed-
        // fatal ("Unknown column 'b.row_order'"), confirmed firing repeatedly in the logs.
        // Whitelisted against real columns, falling back to 'id', instead of trusting the caller.
        $allowed_sort_columns = ['id', 'name', 'slug', 'image', 'status'];
        $sort = in_array($sort, $allowed_sort_columns, true) ? $sort : 'id';
        $order = (strtolower((string) $order) === 'desc') ? 'DESC' : 'ASC';
        $this->db->order_by('b.' . $sort, $order);

        $query = $this->db->get('brands b');
        return $query->result_array();
    }

    public function get_brand_list()
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $multipleWhere = '';
        $where = ['status !=' => NULL];

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // $_GET['sort'] used to be passed straight into order_by() with no whitelist - an
        // injection route the same as already fixed on other list pages - and $_GET['order']
        // was read but then ignored (order_by() below was hardcoded to "asc" regardless).
        $allowed_sort_columns = ['id', 'name', 'image', 'status'];
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
            $multipleWhere = ['`id`' => $search, '`name`' => $search];
        }
        // Previously the count query never applied $where (only the data query did), so if any
        // row's status ever fell outside 0/1 the reported total and the actual returned rows
        // would disagree, breaking the table's pagination footer.
        $count_res = $this->db->select(' COUNT(id) as `total` ');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        $count_res->where($where);
        $brand_count = $count_res->get('brands')->result_array();
        foreach ($brand_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $brand_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('brands')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($brand_search_res as $row) {

            if (!$this->ion_auth->is_seller()) {
                $operate = '<a href="' . base_url('admin/brand/create_brand' . '?edit_id=' . $row['id']) . '" class=" btn action-btn btn-success btn-xs mr-1 mb-1 ml-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/brand/create_brand"><i class="fa fa-pen"></i></a>';
                $operate .= '<a class="delete-brand btn action-btn btn-danger btn-xs mr-1 mb-1 ml-1" title="Delete" href="javascript:void(0)" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            }
            if ($row['status'] == '1') {
                $tempRow['status'] = '<a class="badge badge-success text-white" >Active</a>';
                if (!$this->ion_auth->is_seller()) {
                    $operate .= '<a class="btn action-btn btn-warning btn-xs update_active_status mr-1 mb-1 ml-1" data-table="brands" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye-slash"></i></a>';
                }
            } else {
                $tempRow['status'] = '<a class="badge badge-danger text-white" >Inactive</a>';
                if (!$this->ion_auth->is_seller()) {
                    $operate .= '<a class="btn action-btn btn-primary mr-1 mb-1 btn-xs update_active_status ml-1" data-table="brands" href="javascript:void(0)" title="Active" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye"></i></a>';
                }
            }

            $tempRow['id'] = $row['id'];
            // Was output_escaping() (which only undoes backslash-escaping, not real HTML
            // encoding) plus a stray, unmatched closing </a> tag left over from another table's
            // cell - rendered raw into the page, a stored-XSS route for any brand name that
            // slipped past input-side filtering (e.g. the CSV bulk-upload path, which never runs
            // xss_clean on the name at all).
            $tempRow['name'] = html_escape($row['name']);

            if (empty($row['image']) || file_exists(FCPATH  . $row['image']) == FALSE) {
                $row['image'] = base_url() . NO_IMAGE;
                $row['image_main'] = base_url() . NO_IMAGE;
            } else {
                $row['image_main'] = base_url($row['image']);
                $row['image'] = get_image_url($row['image'], 'thumb', 'sm');
            }
            $tempRow['image'] = "<div class='image-box-100'><a href='" . $row['image_main'] . "' data-toggle='lightbox' data-gallery='gallery'> <img class='rounded' src='" . $row['image'] . "'></a></div>";
            if (!$this->ion_auth->is_seller()) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
