<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Point_of_sale_model extends CI_Model
{
    function get_users($search_term = "")
    {
        // Fetch users — select only the columns needed here (never '*', which would
        // include the password hash/salt) and use the query builder's like()/or_like()
        // so the search term is escaped, instead of concatenating it into raw SQL strings
        // (the previous version was a SQL-injectable, unauthenticated endpoint).
        $this->db->select('id, username, mobile, email');
        $this->db->group_start();
        $this->db->like('username', $search_term);
        $this->db->or_like('id', $search_term);
        $this->db->or_like('mobile', $search_term);
        $this->db->or_like('email', $search_term);
        $this->db->group_end();
        $fetched_records = $this->db->get('users');
        $users = $fetched_records->result_array();


        // Initialize Array with fetched data
        $data = array();
        foreach ($users as $user) {
            $data[] = array("id" => $user['id'], "text" => $user['username'] . " | " . $user['mobile'] . " | " . $user['email'], "number" => $user['mobile'], "email" => $user['email'], "name" => $user['username']);
        }
        return $data;
    }
}
