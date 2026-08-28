<?php

defined('BASEPATH') or exit('No direct script access allowed');
class System_users_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function update_user($data)
    {
        $data = escape_array($data);

        if (isset($data['edit_system_user'])) {

            $user_data = [
                'ip_address' => $this->input->ip_address(),
                'mobile' => $data['mobile'],
                'email' => $data['email'],
                'username' => $data['username'],
                'active' => 1
            ];
            if (isset($data['password']) && !empty($data['password'])) {
                $password = $this->ion_auth->hash_password($data['password']);
                $user_data['password'] = $password;
            }
            $permission_data = [
                'role' => $data['role']
            ];
            if ($data['role'] > 0) {
                $permission_data['permissions'] = json_encode(isset($data['permissions']) ? $data['permissions'] : []);
            } else {
                $permission_data['permissions'] = NULL;
            }
            $this->db->set($permission_data)->where('user_id', $data['edit_system_user'])->update('user_permissions');
            /* get_user_permissions() memoises this row for the rest of the request - drop it
             * so a save followed by a re-read reflects the new permission set. */
            if (function_exists('clear_user_permissions_cache')) {
                clear_user_permissions_cache();
            }
            $this->db->set($user_data)->where('id', $data['edit_system_user'])->update('users');
        } else {

            $password = $this->ion_auth->hash_password($data['password']);

            $user_data = [
                'ip_address' => $this->input->ip_address(),
                'mobile' => $data['mobile'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => $password,
                'active' => 1
            ];

            $permission_data = [
                'role' => $data['role']
            ];

            if ($data['role'] > 0) {
                $permission_data['permissions'] = json_encode(isset($data['permissions']) ? $data['permissions'] : []);
            } else {
                $permission_data['permissions'] = NULL;
            }

            // One mobile number = one account (users.mobile is UNIQUE and is ion_auth's
            // identity column) that may hold SEVERAL roles. If this number already belongs
            // to a buyer or seller, grant admin access ON that account instead of refusing
            // it - otherwise anyone who runs the marketplace and also shops or sells on it
            // needs a second phone number just to hold an admin login.
            $existing = $this->db->select('id')->where('mobile', $data['mobile'])->get('users')->row_array();

            if (!empty($existing)) {
                $last_id = (int) $existing['id'];

                // Deliberately does NOT touch the existing password, username or email:
                // this is "add the admin role to an account that already exists", not
                // "overwrite that account". The person keeps signing in with what they
                // already use.
                if (empty($this->db->where(['user_id' => $last_id, 'group_id' => 1])->get('users_groups')->row_array())) {
                    $this->db->insert('users_groups', ['user_id' => $last_id, 'group_id' => '1']);
                }

                $permission_data['user_id'] = $last_id;
                if (!empty($this->db->where('user_id', $last_id)->get('user_permissions')->row_array())) {
                    $this->db->set($permission_data)->where('user_id', $last_id)->update('user_permissions');
                    /* get_user_permissions() memoises this row for the rest of the request - drop it
                     * so a save followed by a re-read reflects the new permission set. */
                    if (function_exists('clear_user_permissions_cache')) {
                        clear_user_permissions_cache();
                    }
                } else {
                    $this->db->insert('user_permissions', $permission_data);
                    /* get_user_permissions() memoises this row for the rest of the request - drop it
                     * so a save followed by a re-read reflects the new permission set. */
                    if (function_exists('clear_user_permissions_cache')) {
                        clear_user_permissions_cache();
                    }
                }

                return $last_id;
            }

            $this->db->insert('users', $user_data);
            $last_id = $this->db->insert_id();
            $this->db->insert('users_groups', ['user_id' => $last_id, 'group_id' => '1']);
            // Every account can shop; admins are no exception. Without the buyer group the
            // storefront login refuses them, which is the same trap sellers used to hit.
            $this->db->insert('users_groups', ['user_id' => $last_id, 'group_id' => '2']);
            $permission_data['user_id'] = $last_id;
            $this->db->insert('user_permissions', $permission_data);
            /* get_user_permissions() memoises this row for the rest of the request - drop it
             * so a save followed by a re-read reflects the new permission set. */
            if (function_exists('clear_user_permissions_cache')) {
                clear_user_permissions_cache();
            }

            return $last_id;
        }
    }


    function get_users_list()
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

        // Whitelist against the actual selected columns - $_GET['sort'] was previously
        // passed straight into order_by() unchecked (SQL injection shape).
        $allowed_sort_columns = ['id', 'username', 'email', 'mobile', 'role', 'active'];
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
            $multipleWhere = ['up.`id`' => $search, '`u.username`' => $search, 'u.`mobile`' => $search];
        }

        $count_res = $this->db->select(' COUNT(up.id) as `total` ')->join('users u', 'up.user_id=u.id');


        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $sys_user_count = $count_res->get('user_permissions up')->result_array();

        foreach ($sys_user_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('up.id,u.id as user_id,u.username,u.email,up.role,u.mobile,up.permissions,u.active')->join('users u', 'up.user_id=u.id');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $sys_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('user_permissions up')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $current_user_id = $this->ion_auth->user()->row()->id;
        $userData = fetch_details('user_permissions', ['user_id' => $current_user_id]);
        foreach ($sys_search_res as $row) {

            $operate = '';
            if ($current_user_id != $row['user_id'] && $userData[0]['role'] == 0) {
                $operate .= ' <a href="javascript:void(0)" class="edit_btn action-btn btn btn-success btn-xs mb-1 ml-1"  title="Edit" data-id="' . $row['id'] . '" data-url="admin/system_users/add_system_users"><i class="fa fa-pen"></i></a>';
                $operate .= ' <a  href="javascript:void(0)" class="btn btn-danger action-btn btn-xs mr-1 mb-1 ml-1"  title="Delete" id="delete-system-users" data-id="' . $row['user_id'] . '"  ><i class="fa fa-trash"></i></a>';

                if ($row['active'] == '1') {
                    $tempRow['status'] = '<a class="badge badge-success text-white" >Active</a>';
                    $operate .= '<a class="btn btn-warning btn-xs update_active_status action-btn mr-1 mb-1 ml-1" data-table="users" title="Deactivate" href="javascript:void(0)" data-id="' . $row['user_id'] . '" data-status="' . $row['active'] . '" ><i class="fa fa-eye-slash"></i></a>';
                } else {
                    $tempRow['active'] = '<a class="badge badge-danger text-white" >Inactive</a>';
                    $operate .= '<a class="btn btn-primary mr-1 mb-1 ml-1 btn-xs update_active_status action-btn" data-table="users" href="javascript:void(0)" title="Active" data-id="' . $row['user_id'] . '" data-status="' . $row['active'] . '" ><i class="fa fa-eye"></i></a>';
                }
            }

            $tempRow['id'] = $row['id'];
            $tempRow['username'] = html_escape(ucfirst($row['username']));

            if (isset($row['email']) && !empty($row['email']) && $row['email'] != "" && $row['email'] != " ") {
                $tempRow['email'] = html_escape((defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) ? str_repeat("X", strlen($row['email']) - 3) . substr($row['email'], -3) : ucfirst($row['email']));
            } else {
                $tempRow['email'] = "";
            }
            if (isset($row['mobile']) && !empty($row['mobile']) && $row['mobile'] != "" && $row['mobile'] != " ") {
                $tempRow['mobile'] =  (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) ? str_repeat("X", strlen($row['mobile']) - 3) . substr($row['mobile'], -3) : $row['mobile'];
            }else{
                $tempRow['mobile'] = "";
            }
            if ($row['role'] == '0') {
                $row['role'] = "<span class='badge badge-primary'>Super Admin</span>";
            }
            if ($row['role'] == '1') {
                $row['role'] = "<span class='badge badge-danger'>Admin</span>";
            }
            if ($row['role'] == '2') {
                $row['role'] = "<span class='badge badge-warning'>Editor</span>";
            }
            if ($row['role'] == '3') {
                $row['role'] = "<span class='badge badge-secondary'>Supporter</span>";
            }

            $tempRow['role'] = $row['role'];
            $tempRow['permissions'] = $row['permissions'];
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
