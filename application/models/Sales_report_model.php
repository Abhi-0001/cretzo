<?php


defined('BASEPATH') or exit('No direct script access allowed');


class Sales_report_model extends CI_Model
{
    public function get_sales_list(
        $offset = 0,
        $limit = 10,
        $sort = " o.id ",
        $order = 'ASC'
    ) {
        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        // Whitelist against the actual selected columns - $_GET['sort']/$_GET['order'] were
        // never read at all before, and the sort direction was hardcoded to DESC regardless of
        // what the bootstrap-table widget sent.
        $allowed_sort_columns = ['id', 'date_added', 'final_total', 'username', 'email', 'mobile', 'product_name'];
        $sort_column_map = ['id' => 'o.id', 'date_added' => 'o.date_added', 'final_total' => 'o.final_total', 'username' => 'u.username', 'email' => 'u.email', 'mobile' => 'u.mobile', 'product_name' => 'oi.product_name'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $sort_column_map[$_GET['sort']];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') {
            $order = 'ASC';
        } else {
            $order = 'DESC';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $filters = [
                'u.username' => $search,
                'u.email' => $search,
                'u.mobile' => $search,
                'o.final_total' => $search,
                'o.date_added' => $search,
                'o.id' => $search,
                'oi.product_name' => $search,
            ];
        }

        // seller_id is only ever compared against a numeric id column - cast to int rather
        // than concatenating the raw $_GET/$_POST value into a where() string, which was a
        // real SQL injection (the value was never escaped or bound).
        $seller_id = null;
        if (!empty($_GET['seller_id'])) {
            $seller_id = (int) $_GET['seller_id'];
        } elseif (!empty($_POST['seller_id'])) {
            $seller_id = (int) $_POST['seller_id'];
        }

        $count_res = $this->db->select(' COUNT(DISTINCT o.id) as `total` ')->join(' `users` u', 'u.id= o.user_id');
        $count_res->join(' `order_items` oi', 'oi.order_id=o.id');
        if (!empty($seller_id)) {
            $count_res->where('oi.seller_id', $seller_id);
        }

        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {

            $count_res->where("DATE(o.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $count_res->where("DATE(o.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }

        if (isset($filters) && !empty($filters)) {
            $this->db->group_Start();
            $count_res->or_like($filters);
            $this->db->group_End();
        }
        $sales_count = $count_res->get('`orders` o')->result_array();

        foreach ($sales_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select(' o.*,oi.* , u.username ,u.email,u.mobile,COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name,u.username as seller_name ')
            ->join('users u', 'u.id= o.user_id', 'left')
            ->join('order_items oi', 'oi.order_id=o.id', 'left')
            ->join('seller_data sd', 'sd.user_id=oi.seller_id', 'left');

        if (!empty($seller_id)) {
            // Was previously applied to $count_res a second time (already-spent query
            // builder object) instead of $search_res, so the "Filter By Seller" dropdown had
            // zero effect on the actually-displayed rows.
            $search_res->where('oi.seller_id', $seller_id);
        }

        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $search_res->where("DATE(o.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $search_res->where("DATE(o.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }

        if (isset($filters) && !empty($filters)) {
            $search_res->group_Start();
            $search_res->or_like($filters);
            $search_res->group_End();
        }
        $search_res->group_by('o.id');
        $user_details = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('`orders` o')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $total_amount = 0;
        $final_total_amount = 0;
        $total_delivery_charge = 0;
        foreach ($user_details as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['user_id'] = $row['user_id'];
            $tempRow['name'] = $row['username'];
            $tempRow['product_name'] = $row['product_name'];
            $tempRow['product_name'] .= (!empty($row['variant_name'])) ? '(' . $row['variant_name'] . ')' : "";
            if (!$this->ion_auth->is_seller()) {
                $tempRow['address'] = $row['address'];
            }
            if (!$this->ion_auth->is_seller()) {
                $tempRow['mobile'] = (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) ? str_repeat("X", strlen($row['mobile']) - 3) . substr($row['mobile'], -3) : $row['mobile'];
            }
            $tempRow['date_added'] = $row['date_added'];
            $tempRow['final_total'] = $row['final_total'];
            // intval() truncated every figure to whole rupees - Rs. 1099.50 counted as 1099 -
            // and these three running totals were then never returned to the caller at all, so
            // the work was both wrong and thrown away. Summed as floats and exposed below.
            $total_amount += (float) $row['total'];
            $final_total_amount += (float) $row['final_total'];
            $total_delivery_charge += (float) $row['delivery_charge'];
            if ($this->ion_auth->is_seller()) {
                $tempRow['total'] = '<span class="badge badge-danger">' . $row['total'] . '</span>';
                $tempRow['payment_method'] = $row['payment_method'];
                $tempRow['tax_amount'] = $row['tax_amount'];
                $tempRow['discounted_price'] = (isset($row['discounted_price']) && $row['discounted_price'] != '') ? $row['discounted_price'] : 0;
                // stripcslashes via output_escaping(): this codebase stores seller-authored text
                // SQL-escaped and strips it again on read, but these two were emitted raw - so a
                // store called "Developer's Den" was shown to the seller as "Developer's Den",
                // backslash and all, on their own sales report. html_escape() as well because both
                // are seller-supplied strings rendered into the table's HTML payload.
                $tempRow['store_name'] =  html_escape(output_escaping((string) $row['store_name']));
                $tempRow['delivery_charge'] =  $row['delivery_charge'];
                $tempRow['seller_name'] =  html_escape(output_escaping((string) $row['seller_name']));
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        // Computed above since the report was written, but never returned - so nothing could
        // display a sales total. Rounded to 2dp because these are money figures.
        $bulkData['total_amount'] = round($total_amount, 2);
        $bulkData['final_total_amount'] = round($final_total_amount, 2);
        $bulkData['total_delivery_charge'] = round($total_delivery_charge, 2);
        print_r(json_encode($bulkData));
    }

    public function get_seller_sales_list(
        $seller_id = null,
        $offset = 0,
        $limit = 10,
        $sort = " o.id ",
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
                'u.username' => $search,
                'u.email' => $search,
                'u.mobile' => $search,
                'o.final_total' => $search,
                'o.date_added' => $search,
                'o.id' => $search,
                'oi.product_name' => $search,
                'o.payment_method' => $search,
            ];
        }
        // The bootstrap-table widget sends sort/order, and this function ignored both entirely -
        // $sort stayed at its " o.id " default and the direction was hardcoded DESC below, so
        // every column header in the seller's sales report did nothing when clicked.
        $allowed_sort_columns = ['id', 'date_added', 'final_total', 'product_name', 'payment_method'];
        $sort_column_map = ['id' => 'o.id', 'date_added' => 'o.date_added', 'final_total' => 'o.final_total', 'product_name' => 'oi.product_name', 'payment_method' => 'o.payment_method'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $sort_column_map[$_GET['sort']];
        }
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

        // seller_id must always come from the authenticated session (passed in by the
        // controller) — the previous version read it from $_GET/$_POST and concatenated it
        // directly into a raw where() string, which was both a SQL injection point and let
        // any seller see another seller's sales-report row count by changing a URL param.
        //
        // COUNT(DISTINCT o.id), not COUNT(o.id): the data query below GROUPs BY o.id, so it
        // returns one row per ORDER, while this count joined order_items and therefore counted
        // one row per ORDER ITEM. Any order containing two of the seller's products was counted
        // twice. Confirmed live - seller 7's report advertised 42 records and returned 25, so the
        // pagination offered pages that do not exist and the record count a seller reads off this
        // screen was simply wrong.
        $count_res = $this->db->select(' COUNT(DISTINCT o.id) as `total` ')->join(' `users` u', 'u.id= o.user_id');
        if (!empty($seller_id)) {
            $count_res->join(' `order_items` oi', 'oi.order_id=o.id');
            $count_res->where('oi.seller_id', $seller_id);
        }
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $count_res->where("DATE(o.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $count_res->where("DATE(o.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }

        if (isset($filters) && !empty($filters)) {
            $this->db->group_Start();
            $count_res->or_like($filters);
            $this->db->group_End();
        }
        $sales_count = $count_res->get('`orders` o')->result_array();

        foreach ($sales_count as $row) {
            $total = $row['total'];
        }

        /*
         * `seller_share` is this seller's own money on the order.
         *
         * The report renders o.final_total in its "Final Total" column, and final_total is the
         * WHOLE order - every seller's items, plus delivery and discounts. On a marketplace order
         * split across two sellers, each seller's own sales report showed the other seller's
         * revenue as part of their sales figure. The subquery is restricted to this seller's
         * items on this order, so the column can show what the seller actually sold.
         */
        $search_res = $this->db->select(' o.*,oi.* , u.username ,u.email,u.mobile,COALESCE(NULLIF(sd.shop_name, ""), sd.store_name) as store_name,u.username as seller_name,
            (SELECT ROUND(SUM(soi.sub_total), 2) FROM order_items soi WHERE soi.order_id = o.id AND soi.seller_id = ' . (int) $seller_id . ') as seller_share ', false)
            ->join('users u', 'u.id= o.user_id', 'left')
            ->join('order_items oi', 'oi.order_id=o.id', 'left')
            ->join('seller_data sd', 'sd.user_id=oi.seller_id', 'left')
            ->where('oi.seller_id', $seller_id);

        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $search_res->where("DATE(o.date_added) >=", date('Y-m-d', strtotime($_GET['start_date'])));
            $search_res->where("DATE(o.date_added) <=", date('Y-m-d', strtotime($_GET['end_date'])));
        }
        if (isset($filters) && !empty($filters)) {
            $search_res->group_Start();
            $search_res->or_like($filters);
            $search_res->group_End();
        }
        $search_res->group_by('o.id');
        // Was order_by($sort, "DESC") with the direction hardcoded, ignoring $order entirely.
        $user_details = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('`orders` o')->result_array();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $total_amount = 0;
        $final_total_amount = 0;
        $total_delivery_charge = 0;
        foreach ($user_details as $row) {
            if (!$this->ion_auth->is_seller()) {
                $operate = '<a href=' . base_url('admin/orders/edit_orders') . '?edit_id=' . $row['id'] . ' class="btn btn-primary btn-xs mr-1 mb-1" title="View" ><i class="fa fa-eye"></i></a>';
                $operate .= '<a href="javascript:void(0)" class="delete-orders btn btn-danger btn-xs mr-1 mb-1" data-id=' . $row['id'] . ' title="Delete" ><i class="fa fa-trash"></i></a>';
                $operate .= '<a href="' . base_url() . 'admin/invoice?edit_id=' . $row['id'] . '" class="btn btn-info btn-xs mr-1 mb-1" title="Invoice" ><i class="fa fa-file"></i></a>';
            }
            $tempRow['id'] = $row['id'];
            $tempRow['product_name'] = $row['product_name'];
            $tempRow['product_name'] .= (!empty($row['variant_name'])) ? '(' . $row['variant_name'] . ')' : "";
            if (!$this->ion_auth->is_seller()) {
                $tempRow['address'] = $row['address'];
            }
            if (!$this->ion_auth->is_seller()) {
                $tempRow['mobile'] = (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) ? str_repeat("X", strlen($row['mobile']) - 3) . substr($row['mobile'], -3) : $row['mobile'];
            }
            $tempRow['date_added'] = $row['date_added'];
            // A seller sees their own share; an admin viewing this same report sees the order.
            // `order_final_total` always carries the whole-order figure so nothing is lost.
            $tempRow['final_total'] = ($this->ion_auth->is_seller() && isset($row['seller_share']))
                ? $row['seller_share']
                : $row['final_total'];
            $tempRow['order_final_total'] = $row['final_total'];
            // intval() truncated every figure to whole rupees - Rs. 1099.50 counted as 1099 -
            // and these three running totals were then never returned to the caller at all, so
            // the work was both wrong and thrown away. Summed as floats and exposed below.
            $total_amount += (float) $row['total'];
            $final_total_amount += (float) (($this->ion_auth->is_seller() && isset($row['seller_share'])) ? $row['seller_share'] : $row['final_total']);
            $total_delivery_charge += (float) $row['delivery_charge'];
            if ($this->ion_auth->is_seller()) {
                $tempRow['payment_method'] = $row['payment_method'];
                // stripcslashes via output_escaping(): this codebase stores seller-authored text
                // SQL-escaped and strips it again on read, but these two were emitted raw - so a
                // store called "Developer's Den" was shown to the seller as "Developer's Den",
                // backslash and all, on their own sales report. html_escape() as well because both
                // are seller-supplied strings rendered into the table's HTML payload.
                $tempRow['store_name'] =  html_escape(output_escaping((string) $row['store_name']));
                $tempRow['seller_name'] =  html_escape(output_escaping((string) $row['seller_name']));
            }
            if (!$this->ion_auth->is_seller()) {
                $tempRow['operate'] = $operate;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        // Computed above since the report was written, but never returned - so nothing could
        // display a sales total. Rounded to 2dp because these are money figures.
        $bulkData['total_amount'] = round($total_amount, 2);
        $bulkData['final_total_amount'] = round($final_total_amount, 2);
        $bulkData['total_delivery_charge'] = round($total_delivery_charge, 2);
        print_r(json_encode($bulkData));
    }
}
