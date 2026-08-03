<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Tax_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function add_tax($data)
    {
        $data = escape_array($data);
        $tax_data = [
            'title' => $data['title'],
            'percentage' => $data['percentage'],
        ];

        if (isset($data['edit_tax_id'])) {
            $this->db->set($tax_data)->where('id', $data['edit_tax_id'])->update('taxes');
        } else {
            $this->db->insert('taxes', $tax_data);
        }
    }

    function get_tax_list()
    {
        $offset = (isset($_GET['offset']) && is_numeric($_GET['offset'])) ? (int) $_GET['offset'] : 0;
        $limit  = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int) $_GET['limit'] : 10;

        // Sorting was taken straight from the query string and passed into order_by(), and the
        // direction was hardcoded to "asc" even though $_GET['order'] was read - so the
        // Percentage and ID headers moved their arrows but never actually sorted descending.
        // Resolved through a whitelist so the fix does not open an injection route.
        // `percentage` is stored as mediumtext, so ordering on it directly sorts alphabetically
        // rather than numerically - 5 came out above 28, which came out above 18. Cast to a
        // number for that column. Values here are fixed literals from the map below, never user
        // input, so disabling identifier escaping on order_by() is safe.
        $sortable = [
            'id' => 'id',
            'title' => 'title',
            'percentage' => 'CAST(percentage AS DECIMAL(10,2))',
            'status' => 'status',
        ];
        $sort = (isset($_GET['sort']) && isset($sortable[$_GET['sort']])) ? $sortable[$_GET['sort']] : 'id';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';

        $search = (isset($_GET['search']) && $_GET['search'] !== '') ? $_GET['search'] : '';

        // The count query used or_where() (exact equality) while the list query used or_like()
        // (partial match). Searching "GST" therefore returned 5 rows alongside a total of 0, so
        // the table read "Showing 1 to 5 of 0 entries" and pagination broke entirely. Both
        // queries now apply the same LIKE filter.
        $count_res = $this->db->select('COUNT(id) as `total`');
        if ($search !== '') {
            $count_res->group_start()->like('id', $search)->or_like('title', $search)->group_end();
        }
        $tax_count = $count_res->get('taxes')->result_array();
        $total = isset($tax_count[0]['total']) ? (int) $tax_count[0]['total'] : 0;

        $search_res = $this->db->select('id, title, percentage, status');
        if ($search !== '') {
            $search_res->group_start()->like('id', $search)->or_like('title', $search)->group_end();
        }

        $tax_search_res = $search_res->order_by($sort, $order, false)->limit($limit, $offset)->get('taxes')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();

        $is_seller = $this->ion_auth->is_seller();

        foreach ($tax_search_res as $row) {
            $row = output_escaping($row);
            $tempRow = array();

            $operate = '';
            if (!$is_seller) {
                // Previously the whole action column was blank for any tax whose status was not
                // 1, so a deactivated tax could never be edited, deleted or switched back on -
                // the record became permanently stranded. Actions are now always available and a
                // status toggle is offered in both directions.
                $operate = ' <a href="javascript:void(0)" class="edit_btn btn action-btn btn-success btn-xs mr-1 mb-1 ml-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/taxes/"><i class="fa fa-pen"></i></a>';

                if ($row['status'] == '1') {
                    $operate .= '<a class="btn btn-warning btn-xs action-btn update_active_status mr-1 mb-1 ml-1" data-table="taxes" title="Deactivate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '"><i class="fa fa-eye-slash"></i></a>';
                } else {
                    $operate .= '<a class="btn btn-primary btn-xs action-btn update_active_status mr-1 mb-1 ml-1" data-table="taxes" title="Activate" href="javascript:void(0)" data-id="' . $row['id'] . '" data-status="' . $row['status'] . '"><i class="fa fa-eye"></i></a>';
                }

                // Was id="delete-tax" on every row, repeating the same element id once per record.
                $operate .= ' <a href="javascript:void(0)" class="btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1 delete-tax" title="Delete" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></a>';
            }

            $tempRow['id'] = $row['id'];
            $tempRow['title'] = html_escape((string) $row['title']);
            // Percentage is stored as free text; render it as a number with its unit so the
            // column is unambiguous rather than a bare "5".
            $tempRow['percentage'] = html_escape((string) $row['percentage']) . ' %';
            $tempRow['status'] = ($row['status'] == '1')
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-danger">Inactive</span>';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        echo json_encode($bulkData);
    }
}
