<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Seller_subscription_model extends CI_Model
{
    public function get_active_subscription($seller_id)
    {
        if (empty($seller_id)) {
            return null;
        }

        $this->db->where('seller_id', $seller_id);
        $this->db->where('is_active', 1);
        $this->db->group_start();
        $this->db->where('end_date >=', date('Y-m-d H:i:s'));
        $this->db->or_where('end_date IS NULL', null, false);
        $this->db->group_end();
        $this->db->order_by('start_date', 'DESC');
        $query = $this->db->get('seller_subscriptions');

        return $query->row_array();
    }

    public function get_latest_subscription($seller_id)
    {
        if (empty($seller_id)) {
            return null;
        }

        $this->db->where('seller_id', $seller_id);
        $this->db->order_by('start_date', 'DESC');
        $query = $this->db->get('seller_subscriptions');

        return $query->row_array();
    }

    public function assign_subscription($seller_id, $subscription_id, $validity = null)
    {
        if (empty($seller_id) || empty($subscription_id)) {
            return false;
        }

        // mark existing subscriptions as inactive
        $this->db->set('is_active', 0)->where('seller_id', $seller_id)->update('seller_subscriptions');

        $start = date('Y-m-d H:i:s');
        $end   = null;

        // basic validity handling: treat numeric value as days, anything else as unlimited
        if (!empty($validity) && ctype_digit((string) $validity)) {
            $end = date('Y-m-d H:i:s', strtotime('+' . (int) $validity . ' days', strtotime($start)));
        }

        $data = [
            'seller_id'       => $seller_id,
            'subscription_id' => $subscription_id,
            'start_date'      => $start,
            'end_date'        => $end,
            'is_active'       => 1,
        ];

        return $this->db->insert('seller_subscriptions', $data);
    }
}

