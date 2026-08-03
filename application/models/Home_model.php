<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
class Home_model extends CI_Model
{

    public function count_new_orders($type = '')
    {
        $res = $this->db->select('count(o.id) as counter');
        if (!empty($type) && $type != 'api') {
            if ($this->ion_auth->is_delivery_boy()) {
                $user_id = $this->session->userdata('user_id');
                $this->db->where('oi.delivery_boy_id', $user_id);
            }
        }
        if ($this->ion_auth->is_delivery_boy()) {
            $this->db->join('order_items oi', 'oi.order_id=o.id', 'left');
            $user_id = $this->session->userdata('user_id');
            $this->db->where('oi.delivery_boy_id', $user_id);
        }
        $res = $this->db->get('`orders` o')->result_array();
        
        return $res[0]['counter'];
    }

    public function count_orders_by_status($status)
    {
        $res = $this->db->select('count(id) as counter');
        $this->db->where('active_status', $status);
        $res = $this->db->get('`orders` o')->result_array();
        return $res[0]['counter'];
    }

    public function count_new_users()
    {
        $res = $this->db->select('count(u.id) as counter')->join('users_groups ug', ' ug.`user_id` = u.`id` ')
            ->where('ug.group_id=2')
            ->get('`users u`')->result_array();
        return $res[0]['counter'];
    }

    public function count_delivery_boys()
    {
        $res = $this->db->select('count(u.id) as counter')->where('ug.group_id', '3')->join('users_groups ug', 'ug.user_id=u.id')
            ->get('`users` u')->result_array();
        return $res[0]['counter'];
    }

    public function count_products($seller_id = "")
    {
        $res = $this->db->select('count(id) as counter ');
        if (!empty($seller_id) && $seller_id != '') {
            $res->where('seller_id', $seller_id);
        }
        $count = $res->get('`products`')->result_array();
        return $count[0]['counter'];
    }

    public function count_products_stock_low_status($seller_id = "")
    {
        $settings = get_settings('system_settings', true);
        $low_stock_limit = isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : 5;
        $count_res = $this->db->select(' COUNT( distinct(p.id)) as `total` ')->join('product_variants', 'product_variants.product_id = p.id');
        $where = "p.stock_type is  NOT NULL";

        $count_res->where($where);
        $count_res->group_Start();
        $count_res->where('p.stock  <=', $low_stock_limit);
        $count_res->where('p.availability  =', '1');
        $count_res->or_where('product_variants.stock  <=', $low_stock_limit);
        $count_res->where('product_variants.availability  =', '1');
        $count_res->group_End();
        if (!empty($seller_id) && $seller_id != '') {
            $count_res->where('p.seller_id  =', $seller_id);
        }
        $product_count = $count_res->get('products p')->result_array();
        return $product_count[0]['total'];
    }

    public function count_products_availability_status($seller_id = "")
    {
        $count_res = $this->db->select(' COUNT( distinct(p.id)) as `total` ')->join('product_variants', 'product_variants.product_id = p.id');
        $where = "p.stock_type is  NOT NULL";
        $count_res->where($where);
        $count_res->group_Start();
        $count_res->where('p.stock ', '0');
        $count_res->where('p.availability ', '0');
        $count_res->or_where('product_variants.stock ', '0');
        $count_res->where('product_variants.availability', '0');
        $count_res->group_End();
        if (!empty($seller_id) && $seller_id != '') {
            $count_res->where('p.seller_id  =', $seller_id);
        }
        $product_count = $count_res->get('products p')->result_array();

        return  $product_count[0]['total'];
    }

    /**
     * Counts sellers in a given seller_data.status bucket.
     *
     * The dashboard cards and the modal lists behind them used to be built from different
     * queries. The cards counted seller_data rows alone, while the lists
     * (Seller_model::approved_sellers / not_approved_sellers / deactive_sellers) additionally
     * require the linked user to be active and to be in the seller group - so an orphaned or
     * deactivated seller_data row was counted on the card but never appeared in the list.
     * On the current database that showed "4" approved sellers above a list containing 1, and
     * "8" pending above a list containing 6. This mirrors the list queries exactly so the
     * number on the card is always the number of rows in the modal.
     *
     * @param int $status 1 = approved, 2 = pending approval, 0 = deactivated
     */
    private function count_sellers_by_status($status)
    {
        $res = $this->db->select('COUNT(u.id) as counter')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->join('seller_data sd', 'sd.user_id = u.id')
            ->where('sd.status', $status)
            ->where('u.active', 1)
            ->where('ug.group_id', '4')
            ->get('users u')->result_array();

        return isset($res[0]['counter']) ? (int) $res[0]['counter'] : 0;
    }

    public function count_approved_seller()
    {
        return $this->count_sellers_by_status(1);
    }

    public function count_not_approved_seller()
    {
        return $this->count_sellers_by_status(2);
    }

    public function count_deactive_seller()
    {
        // Was where('status', ''), which MySQL coerces to 0 on this tinyint column only by
        // accident and errors outright under strict mode.
        return $this->count_sellers_by_status(0);
    }

    public function total_earnings($type = "admin", $seller_id = "")
    {
        $select = "";
        if ($type == "admin") {
            $select = "SUM(admin_commission_amount) as total ";
        }
        if ($type == "seller") {
            $select = "SUM(seller_commission_amount) as total ";
        }
        if ($type == "overall") {
            $select = "SUM(sub_total) as total ";
        }
        $count_res = $this->db->select($select);
        $count_res->where('is_credited', 1);
        if (!empty($seller_id)) {
            $count_res->where('seller_id', $seller_id);
        }

        $product_count = $count_res->get('order_items')->result_array();
        // SUM() returns NULL when nothing matches, and the dashboard fed that straight into
        // number_format(), which is a deprecation warning on PHP 8.1+ (this install is 8.2).
        return isset($product_count[0]['total']) ? (float) $product_count[0]['total'] : 0.0;
    }
}
