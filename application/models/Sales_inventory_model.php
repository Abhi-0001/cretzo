
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sales_inventory_model extends CI_Model
{
    public function get_sales_inventory_list(
        $offset = 0,
        $limit = 10,
        $sort = " oi.id ",
        $order = 'DESC'
    ) {
        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        // Whitelist against the actual selected columns - $_GET['sort']/$_GET['order'] were
        // never read at all before, and the sort direction was hardcoded to ASC regardless of
        // what the bootstrap-table widget sent.
        $allowed_sort_columns = ['id' => 'oi.id', 'name' => 'p.name', 'qty' => 'qty', 'stock' => 'stock'];
        if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sort_columns)) {
            $sort = $allowed_sort_columns[$_GET['sort']];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];

            $filters = [
                'oi.id' => $search,
                'p.name' => $search,
            ];
        }
        $count_res = $this->db->select('oi.id')
            ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
            ->join('products p', 'p.id=pv.product_id', 'left');

        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $count_res->where("DATE(oi.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $count_res->where("DATE(oi.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }

        if (isset($filters) && !empty($filters)) {
            $this->db->group_Start();
            $count_res->or_like($filters);
            $this->db->group_End();
        }

        // Was "!= null" here vs "!empty()" on the data query below - loosely inconsistent at
        // seller_id="0" (filter applied to the count but not to the displayed rows).
        if (!empty($_GET['seller_id'])) {
            $count_res->where("oi.seller_id", $_GET['seller_id']);
        }

        $sales_count = $count_res->group_by('oi.product_variant_id')->get('order_items oi')->result_array();
        $total = count($sales_count);

        // Was "(CASE WHEN (p.stock OR pv.stock) THEN p.stock ELSE pv.stock END)" - that reads
        // as "if either has any truthy stock, use the PRODUCT's stock, not the variant's",
        // which is backwards for a variant row and diverged from the seller-facing query's own
        // (differently broken) version of the same CASE. Use the variant's own stock when it's
        // actually tracked there, falling back to the parent product's otherwise (a variant row
        // can exist with stock left untracked at the variant level).
        $search_res = $this->db->select('oi.id,oi.product_variant_id, p.name, SUM(oi.quantity) AS qty,(p.availability OR pv.availability ) AS availability, COALESCE(pv.stock, p.stock) AS stock')
            ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
            ->join('products p', 'p.id=pv.product_id', 'left');

        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $search_res->where("DATE(oi.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $search_res->where("DATE(oi.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }

        if (isset($filters) && !empty($filters)) {
            $search_res->group_Start();
            $search_res->or_like($filters);
            $search_res->group_End();
        }

        if (!empty($_GET['seller_id'])) {
            $search_res->where("oi.seller_id", $_GET['seller_id']);
        }
        $user_details = $search_res->group_by('oi.product_variant_id')->order_by($sort, $order)->limit($limit, $offset)->get('order_items oi')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($user_details as $row) {
            // Was "$row['stock'] != ''", which is true for the string "0" - an out-of-stock
            // item rendered as a green "in stock: 0" badge instead of "N/A"/out-of-stock.
            if (isset($row['stock']) && $row['stock'] !== null && $row['stock'] !== '' && (int) $row['stock'] > 0) {
                $stock = "<span class='badge badge-success'>" . $row['stock'] . "</span>";
            } else if (isset($row['stock']) && $row['stock'] !== null && $row['stock'] !== '') {
                $stock = "<span class='badge badge-danger'>N/A</span>";
            } else if (($row['availability'] <= 0) && $row['stock'] <= 0) {
                $stock = "<span class='badge badge-warning'>available</span>";
            } else {
                $stock = "<span class='badge badge-danger'>N/A</span>";
            }
            $tempRow['id'] = (isset($row['id']) && $row['id'] != '') ?  $row['id'] : "-";
            $tempRow['name'] = (isset($row['name']) && $row['name'] != '') ?  $row['name'] : "-";
            $tempRow['stock'] = $stock;
            $tempRow['qty'] = (isset($row['qty']) && $row['qty'] != '') ?  $row['qty'] : "-";
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }

    public function get_seller_sales_inventory_list(
        $seller_id = null,
        $offset = 0,
        $limit = 10,
        $sort = " oi.id ",
        $order = 'ASC'
    ) {
        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];

            $filters = [
                'oi.id' => $search,
                'p.name' => $search,
            ];
        }

        $count_res = $this->db->select('oi.id')
            ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
            ->join('products p', 'p.id=pv.product_id', 'left')
            ->where('oi.seller_id', $seller_id);
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $count_res->where("DATE(oi.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $count_res->where("DATE(oi.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }

        if (isset($filters) && !empty($filters)) {
            $this->db->group_Start();
            $count_res->or_like($filters);
            $this->db->group_End();
        }

        $sales_count = $count_res->group_by('oi.product_variant_id')->get('order_items oi')->result_array();
        $total = count($sales_count);
        $search_res = $this->db->select('oi.id,oi.product_variant_id, p.name, SUM(oi.quantity) AS qty,(p.availability OR pv.availability ) AS availability, COALESCE(pv.stock, p.stock) AS stock')
            ->join('product_variants pv', 'pv.id=oi.product_variant_id', 'left')
            ->join('products p', 'p.id=pv.product_id', 'left')
            ->where('oi.seller_id', $seller_id);
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $search_res->where("DATE(oi.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $search_res->where("DATE(oi.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }

        if (isset($filters) && !empty($filters)) {
            $search_res->group_Start();
            $search_res->or_like($filters);
            $search_res->group_End();
        }

        $user_details = $search_res->group_by('oi.product_variant_id')->order_by($sort, "ASC")->limit($limit, $offset)->get('order_items oi')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($user_details as $row) {
            if (isset($row['stock']) && $row['stock'] !== null && $row['stock'] !== '' && (int) $row['stock'] > 0) {
                $stock = "<span class='badge badge-success'>" . $row['stock'] . "</span>";
            } else if (isset($row['stock']) && $row['stock'] !== null && $row['stock'] !== '') {
                $stock = "<span class='badge badge-danger'>N/A</span>";
            } else if (($row['availability'] <= 0) && $row['stock'] <= 0) {
                $stock = "<span class='badge badge-warning'>available</span>";
            } else {
                $stock = "<span class='badge badge-danger'>N/A</span>";
            }
            $tempRow['id'] = (isset($row['id']) && $row['id'] != '') ?  $row['id'] : "-";
            $tempRow['name'] = (isset($row['name']) && $row['name'] != '') ?  $row['name'] : "-";
            $tempRow['stock'] = $stock;
            $tempRow['qty'] = (isset($row['qty']) && $row['qty'] != '') ?  $row['qty'] : "-";
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
