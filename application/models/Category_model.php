<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Category_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }
    public function get_categories($id = NULL, $limit = '', $offset = '', $sort = 'row_order', $order = 'ASC', $has_child_or_item = 'true', $slug = '', $ignore_status = '', $seller_id = '', $ignore_categories_with_no_products = 'false')
    {
        $level = 0;
        if ($ignore_status == 1) {
            $where = (isset($id) && !empty($id)) ? ['c1.id' => $id] : ['c1.parent_id' => 0];
        } else {
            $where = (isset($id) && !empty($id)) ? ['c1.id' => $id, 'c1.status' => 1] : ['c1.parent_id' => 0, 'c1.status' => 1];
        }

        // Built as its own, fully separate query (not count_all_results() after get()) —
        // count_all_results() called post-get() runs against a query builder that get()
        // has already reset, so it silently counted the whole table instead of the
        // filtered set. COUNT(DISTINCT c1.id) mirrors the group_by('c1.id') cases below
        // so the total isn't inflated by the join fan-out.
        $this->db->select('COUNT(DISTINCT c1.id) as total');
        $this->db->where($where);
        if (!empty($slug)) {
            $this->db->where('c1.slug', $slug);
        }
        if (!empty($seller_id)) {
            $this->db->join('products p3', 'p3.category_id = c1.id AND p3.seller_id = ' . $this->db->escape($seller_id), 'inner');
        }
        if ($has_child_or_item == 'false') {
            $this->db->join('categories c2', 'c2.parent_id = c1.id', 'left');
            $this->db->join('products p2', ' p2.category_id = c1.id', 'left');
            $this->db->group_start();
            $this->db->or_where(['c1.id ' => ' p2.category_id ', ' c2.parent_id ' => ' c1.id '], NULL, FALSE);
            $this->db->group_End();
        }
        if ($ignore_categories_with_no_products == 'true') {
            $this->db->join('products p', 'p.category_id = c1.id AND p.status = 1 AND p.listing_visibility = 1', 'left');
            $this->db->having('COUNT(p.id) > 0');
        }
        $count_row = $this->db->get('categories c1')->row_array();
        $count_res = $count_row['total'] ?? 0;

        $this->db->select('c1.*');
        $this->db->where($where);
        if (!empty($slug)) {
            $this->db->where('c1.slug', $slug);
        }

        /* Added for cretzo */
        if (!empty($seller_id)) {
            $this->db->join('products p3', 'p3.category_id = c1.id AND p3.seller_id = ' . $this->db->escape($seller_id), 'inner');
        }

        if ($has_child_or_item == 'false') {
            $this->db->join('categories c2', 'c2.parent_id = c1.id', 'left');
            $this->db->join('products p2', ' p2.category_id = c1.id', 'left');
            $this->db->group_start();
            $this->db->or_where(['c1.id ' => ' p2.category_id ', ' c2.parent_id ' => ' c1.id '], NULL, FALSE);
            $this->db->group_End();
            $this->db->group_by('c1.id');
        }

        if ($ignore_categories_with_no_products == 'true') {
            $this->db->join('products p', 'p.category_id = c1.id AND p.status = 1 AND p.listing_visibility = 1', 'left');
            $this->db->group_by('c1.id');
            $this->db->having('COUNT(p.id) > 0');
        }

        if (!empty($limit) || !empty($offset)) {
            $this->db->offset($offset);
            $this->db->limit($limit);
        }

        $this->db->order_by((string)$sort, (string)$order);

        $parent = $this->db->get('categories c1');
        $categories = $parent->result();
        $i = 0;
        foreach ($categories as $p_cat) {
            $categories[$i]->children = $this->sub_categories($p_cat->id, $level);
            $categories[$i]->text = output_escaping($p_cat->name);
            $categories[$i]->name = output_escaping($categories[$i]->name);
            $categories[$i]->state = ['opened' => true];
            $categories[$i]->icon = "jstree-folder";
            $categories[$i]->level = $level;
            $categories[$i]->image = get_image_url($categories[$i]->image, 'thumb', 'sm');
            $categories[$i]->banner = get_image_url($categories[$i]->banner, 'thumb', 'md');
            $i++;
        }
        if (isset($categories[0])) {
            $categories[0]->total = $count_res;
        }
        return  json_decode(json_encode($categories), 1);
    }

    public function get_categories_by_ids($category_ids) {
        $category_ids_array = explode(',', $category_ids);
    
        // Remove any empty elements and trim whitespace from each category ID
        $category_ids_array = array_filter(array_map('trim', $category_ids_array));
    
        // Built as its own query before get() — count_all_results() called after get() ran
        // against a query builder get() had already reset, so it counted every category in
        // the table instead of just the ones in $category_ids_array.
        $count_res = $this->db->where_in('id', $category_ids_array)->count_all_results('categories');

        // Build the WHERE condition to fetch categories for the provided IDs
        $this->db->where_in('id', $category_ids_array);

        $level = 0;
        $query = $this->db->get('categories');
        $categories = $query->result();
        $i = 0;
        foreach ($categories as $p_cat) {
            $categories[$i]->children = $this->sub_categories($p_cat->id, $level);
            $categories[$i]->text = output_escaping($p_cat->name);
            $categories[$i]->name = output_escaping($categories[$i]->name);
            $categories[$i]->state = ['opened' => true];
            $categories[$i]->icon = "jstree-folder";
            $categories[$i]->level = $level;
            $categories[$i]->image = get_image_url($categories[$i]->image, 'thumb', 'sm');
            $categories[$i]->banner = get_image_url($categories[$i]->banner, 'thumb', 'md');
            $i++;
        }
        if (isset($categories[0])) {
            $categories[0]->total = $count_res;
        }
        return  json_decode(json_encode($categories), 1);
    }

    public function get_seller_categories($seller_id)
    {
        $level = 0;
        $where = 'user_id = ' . $seller_id;
        // Built as its own query before get() — count_all_results() called after get() ran
        // against a query builder get() had already reset, so it counted every seller_data
        // row in the table instead of just this seller's.
        $count_res = $this->db->where($where)->count_all_results('seller_data');
        $this->db->select('category_ids');
        $this->db->where($where);
        $result = $this->db->get('seller_data')->result_array();
        $result = empty($result[0]['category_ids']) ? [] : explode(",", $result[0]['category_ids']);
        $categories =  fetch_details('categories', "status = 1", '*', "", "", "", "", "id", $result);
        $i = 0;
        foreach ($categories as $p_cat) {
            $categories[$i]['children'] = $this->sub_categories($p_cat['id'], $level);
            $categories[$i]['text'] = output_escaping($p_cat['name']);
            $categories[$i]['name'] = output_escaping($categories[$i]['name']);
            $categories[$i]['state'] = ['opened' => true];
            $categories[$i]['icon'] = "jstree-folder";
            $categories[$i]['level'] = $level;
            $categories[$i]['image'] = get_image_url($categories[$i]['image'], 'thumb', 'md');
            $categories[$i]['banner'] = get_image_url($categories[$i]['banner'], 'thumb', 'md');
            $i++;
        }
        if (isset($categories[0])) {
            $categories[0]['total'] = $count_res;
        }
        return  $categories;
    }

    public function sub_categories($id, $level)
    {
        $level = $level + 1;
        $this->db->select('c1.*');
        $this->db->from('categories c1');
        $this->db->where(['c1.parent_id' => $id, 'c1.status' => 1]);
        $child = $this->db->get();
        $categories = $child->result();
        $i = 0;
        foreach ($categories as $p_cat) {

            $categories[$i]->children = $this->sub_categories($p_cat->id, $level);
            $categories[$i]->text = output_escaping($p_cat->name);
            $categories[$i]->state = ['opened' => true];
            $categories[$i]->level = $level;
            $categories[$i]->image = get_image_url($categories[$i]->image, 'thumb', 'md');
            $categories[$i]->banner = get_image_url($categories[$i]->banner, 'thumb', 'md');
            $i++;
        }
        return $categories;
    }


    /**
     * Delete a category, parking any products it holds in "Uncategorised".
     *
     * This used to be a soft delete: `UPDATE categories SET status = NULL`, with the admin list
     * filtering `status IS NOT NULL` to hide the row. That stopped working when migration 050
     * made `categories.status` NOT NULL DEFAULT 0 - and it stopped working SILENTLY, because
     * MySQL is not in strict mode here, so the NULL was coerced to 0 instead of raising.
     * "Delete" therefore degraded into "deactivate": the category dropped off the storefront
     * (which requires status = 1) but stayed in the admin list forever, while the endpoint
     * reported "Deleted Successfully".
     *
     * Re-allowing NULL is not the answer - 050 removed that state deliberately, because a
     * NULL-status category silently swallowed every product filed under it (the storefront
     * product reads filter on `c.status = '1' OR c.status = '0'`). So this is a real DELETE
     * now, following the same shape as Brand_model::delete_brand().
     *
     * @return array{success: bool, message: string}
     */
    public function delete_category($id)
    {
        $id = escape_array($id);

        $category = fetch_details('categories', ['id' => $id], 'id,name');
        if (empty($category)) {
            return ['success' => false, 'message' => 'Category does not exist!'];
        }

        // Subcategories first. Deleting a parent out from under its children leaves them
        // pointing at a parent_id that no longer resolves, and orphaned category references
        // have already broken this storefront once (see migrations 056 / 057, which had to
        // rescue 177 products whose category_id pointed at a category that did not exist).
        // Blocking is also what the brand and subscription-plan deletes do with dependents.
        $children = $this->db->where('parent_id', $id)->count_all_results('categories');
        if ($children > 0) {
            return [
                'success' => false,
                'message' => 'This category still has ' . $children . ' subcategor' . ($children > 1 ? 'ies' : 'y')
                    . '. Delete or move ' . ($children > 1 ? 'them' : 'it') . ' before deleting this category.',
            ];
        }

        // Products have to land somewhere that exists and is browsable. The old code moved
        // them to a hardcoded category_id of 1, which on this database is a leftover named
        // "test" with status 0 - i.e. it parked them somewhere deactivated and invisible.
        // "Uncategorised" is the category migrations 056/057 established for exactly this.
        $products_held = $this->db->where('category_id', $id)->count_all_results('products');
        $fallback = fetch_details('categories', ['slug' => 'uncategorised'], 'id');
        if ($products_held > 0 && empty($fallback[0]['id'])) {
            return [
                'success' => false,
                'message' => 'This category holds ' . $products_held . ' product(s) and there is no '
                    . '"Uncategorised" category to move them to. Reassign them before deleting.',
            ];
        }

        $this->db->trans_start();

        if ($products_held > 0) {
            $this->db->set('category_id', $fallback[0]['id'])->where('category_id', $id)->update('products');
        }
        $this->db->delete('categories', ['id' => $id]);
        $deleted = ($this->db->affected_rows() > 0);

        $this->db->trans_complete();

        // Both statements share one transaction so a failure can never leave the products
        // moved with the category still sitting there (or the reverse).
        if ($this->db->trans_status() === FALSE || !$deleted) {
            return ['success' => false, 'message' => 'Failed to delete category'];
        }

        $moved = ($products_held > 0)
            ? ' ' . $products_held . ' product(s) moved to Uncategorised.'
            : '';

        return ['success' => true, 'message' => 'Deleted Successfully.' . $moved];
    }


    public function get_category_list($seller_id = NULL)
    {
        $offset = 0;
        $limit = 10;
        $multipleWhere = '';
        // CI renders this as `status IS NOT NULL`, which has matched every row since migration
        // 050 made the column NOT NULL. It is kept only because it is harmless: there is no
        // soft-deleted state to hide any more - delete_category() removes the row outright.
        $where = ['status !=' => NULL];

        if (isset($_GET['id']))
            $where['parent_id'] = $_GET['id'];
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // $_GET['sort'] was passed straight into order_by() with no whitelist, and the
        // direction was hardcoded to "asc" a few lines down regardless of what $_GET['order']
        // said - every sortable column header moved its arrow but the query never actually
        // reversed. Both fixed together, matching the pattern used on every other list page.
        $sortable = ['id' => 'id', 'name' => 'name', 'status' => 'status'];
        $sort = (isset($_GET['sort']) && isset($sortable[$_GET['sort']])) ? $sortable[$_GET['sort']] : 'id';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';

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

        // Grouped so the OR'd search terms can't escape the status/seller-ownership AND
        // conditions below — ungrouped, SQL's AND-binds-tighter-than-OR precedence meant a
        // search match alone was enough to return a category regardless of status or
        // whether it was actually in this seller's category_ids.
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        if (isset($seller_id) && $seller_id != "") {
            $count_res->where_in('id', $cat_ids);
        }

        $cat_count = $count_res->get('categories')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' * ');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        if (isset($seller_id) && $seller_id != "") {
            $search_res->where_in('id', $cat_ids);
        }

        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('categories')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        if (!empty($cat_search_res)) {
            foreach ($cat_search_res as $row) {

                if (!$this->ion_auth->is_seller()) {
                    $operate = '<a href="' . base_url('admin/category/create_category' . '?edit_id=' . $row['id']) . '" class=" btn action-btn btn-success btn-xs mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/category/create_category"><i class="fa fa-pen"></i></a>';
                    $operate .= '<a class="delete-categoty btn action-btn btn-danger btn-xs mr-1 mb-1 ml-1" title="Delete" href="javascript:void(0)" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
                }
                if ($row['status'] == '1') {
                    $tempRow['status'] = '<a class="badge badge-success text-white" >Active</a>';
                    if (!$this->ion_auth->is_seller()) {
                        $operate .= '<a class="btn btn-warning action-btn btn-xs update_active_status ml-1 mr-1 mb-1" data-table="categories" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye-slash"></i></a>';
                    }
                } else {
                    $tempRow['status'] = '<a class="badge badge-danger text-white" >Inactive</a>';
                    if (!$this->ion_auth->is_seller()) {
                        $operate .= '<a class="btn btn-primary action-btn mr-1 mb-1 ml-1 btn-xs update_active_status" data-table="categories" href="javascript:void(0)" title="Active" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '" ><i class="fa fa-eye"></i></a>';
                    }
                }

                $tempRow['id'] = $row['id'];
                // This list is served to both portals. The link was hard-coded to admin/category,
                // so a seller clicking a category name was sent to an admin URL their role cannot
                // open - it bounced them out of the page they were on.
                $category_drill_url = $this->ion_auth->is_seller() ? 'seller/category?id=' : 'admin/category?id=';
                $tempRow['name'] = '<a href="' . base_url() . $category_drill_url . $row['id'] . '">' . output_escaping($row['name']) . '</a>';

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
        }
        print_r(json_encode($bulkData));
    }

    public function add_category($data)
    {
        // NOT escape_array(): every value below is written through CodeIgniter's query builder
        // (->set()/->insert()), which parameter-escapes on its own. Pre-escaping added a layer
        // of backslashes that COMPOUNDED on each save, so a category named "Men's Wear" was
        // stored as "Men\'s Wear" and re-editing it produced "Men\\\'s Wear".
        //
        // It showed up as "Men/s Wear" in the seller profile's secondary-category picker, which
        // prints the name with htmlspecialchars() and nothing else. The admin category list
        // happened to hide it because that one read path runs output_escaping() (really
        // stripcslashes), which peels exactly one layer back off.
        //
        // Migration 059 repairs the names already damaged in the database.

        // create_unique_slug() must be told which row we're editing, otherwise it finds THIS
        // category's own existing slug, treats it as a collision and mints "name-1", "name-2",
        // ... on every save - silently changing the category's public URL
        // (products/category/<slug>) each time it is edited. Every top-level category in the
        // live database was already sitting on a "-1" slug because of this.
        $edit_id = (isset($data['edit_category']) && !empty($data['edit_category'])) ? $data['edit_category'] : null;

        $cat_data = [
            'name' => $data['category_input_name'],
            'parent_id' => ($data['category_parent'] == NULL && isset($data['category_parent'])) ? '0' : $data['category_parent'],
            'slug' => ($edit_id !== null)
                ? create_unique_slug($data['category_input_name'], 'categories', 'slug', 'id', $edit_id)
                : create_unique_slug($data['category_input_name'], 'categories'),
            'status' => '1',
        ];

        if (isset($data['edit_category'])) {
            unset($cat_data['status']);
            if (isset($data['category_input_image'])) {
                $cat_data['image'] = $data['category_input_image'];
            }

            $cat_data['banner'] = (isset($data['banner'])) ? $data['banner'] : '';

            $this->db->set($cat_data)->where('id', $data['edit_category'])->update('categories');
        } else {
            if (!empty($data['category_input_image']) && isset($data['category_input_image'])) {
                $cat_data['image'] = $data['category_input_image'];
            }
            if (isset($data['banner'])) {
                $cat_data['banner'] = (isset($data['banner']) && !empty($data['banner'])) ? $data['banner'] : '';
            }
            $this->db->insert('categories', $cat_data);
        }
    }

    public function top_category()
    {
        // Was select('*'), which shipped every column of every category to the browser when the
        // dashboard widget only renders three of them.
        $query = $this->db->select('id, name, clicks')
            ->limit('4')
            ->order_by('clicks', 'Desc')
            ->get('categories');

        $rows = $query->result_array();
        foreach ($rows as &$row) {
            // Category names are admin-supplied free text rendered by bootstrap-table, which
            // does not escape cell values by default.
            $row['name']   = html_escape((string) $row['name']);
            $row['clicks'] = (int) $row['clicks'];
        }
        unset($row);

        $data['total'] = count($rows);
        $data['rows'] = $rows;

        echo json_encode($data);
    }
}
