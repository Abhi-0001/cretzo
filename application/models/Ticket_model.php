<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Ticket_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function add_ticket($data)
    {
        $data = escape_array($data);
        if (isset($data['edit_ticket_status'])) {
            $ticket_data = [
                'status' =>  $data['status'],
            ];
        } else {
            $ticket_data = [
                'ticket_type_id' => $data['ticket_type_id'],
                'user_id' => $data['user_id'],
                'subject' => $data['subject'],
                'email' => $data['email'],
                'description' => $data['description'],
                'status' =>  $data['status'],
            ];
        }
        if (isset($data['edit_ticket'])) {
            return $this->db->set($ticket_data)->where('id', $data['edit_ticket'])->update('tickets');
        } else if (isset($data['edit_ticket_status'])) {
            // Neither edit branch used to return anything, so the caller's
            // "!add_ticket($data)" check (Tickets::edit_ticket_status()) was always true
            // regardless of whether the update actually succeeded - the "it failed" branch
            // there was dead code, and the "it succeeded" branch (which sends the status-change
            // notification) ran unconditionally even on a failed update.
            return $this->db->set($ticket_data)->where('id', $data['edit_ticket_status'])->update('tickets');
        } else {
            $this->db->insert('tickets', $ticket_data);
            $insert_id = $this->db->insert_id();
            if (!empty($insert_id)) {
                return  $insert_id;
            } else {
                return false;
            }
        }
    }
    function add_ticket_type($data)
    {
        $data = escape_array($data);

        $ticket_data = [
            'title' => $data['title'],
        ];
        if (isset($data['edit_ticket_type'])) {
            $this->db->set($ticket_data)->where('id', $data['edit_ticket_type'])->update('ticket_types');
        } else {
            $this->db->insert('ticket_types', $ticket_data);
            $insert_id = $this->db->insert_id();
            if (!empty($insert_id)) {
                return  $insert_id;
            } else {
                return false;
            }
        }
    }

    function add_ticket_message($data)
    {
        $data = escape_array($data);

        $ticket_msg_data = [
            'user_type' => $data['user_type'],
            'user_id' => $data['user_id'],
            'ticket_id' => $data['ticket_id'],
            'message' => $data['message']
        ];
        if (isset($data['attachments']) && !empty($data['attachments'])) {
            $ticket_msg_data['attachments'] = json_encode($data['attachments']);
        }

        $this->db->insert('ticket_messages', $ticket_msg_data);
        $insert_id = $this->db->insert_id();
        if (!empty($insert_id)) {
            return  $insert_id;
        } else {
            return false;
        }
    }

    function get_ticket_list()
    {
        $offset = 0;
        $limit = 10;
        $sort = 't.id';
        $order = 'ASC';
        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['t.id', 'id', 't.subject', 't.status', 't.date_created', 't.last_updated'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = ($_GET['sort'] === 'id') ? 't.id' : $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'DESC';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = [
                '`u.id`' => $search, '`u.username`' => $search, '`u.email`' => $search, '`u.mobile`' => $search, '`t.subject`' => $search, '`t.email`' => $search, '`t.description`' => $search, '`tty.title`' => $search
            ];
        }

        // COUNT(u.id) undercounts here: u.id comes from a LEFT JOIN, so a ticket whose user
        // account was since deleted has a NULL u.id, and COUNT() ignores NULLs - that ticket
        // would be in the data results but missing from the total. t.id (the tickets table's
        // own primary key) is never null.
        $count_res = $this->db->select(' COUNT(t.id) as `total`')->join('ticket_types tty', 'tty.id=t.ticket_type_id', 'left')->join('users u', 'u.id=t.user_id', 'left');

        // Was or_where() here (exact match) while the data query below uses or_like() (partial
        // match) - a partial search term matched real rows in the data query but zero rows in
        // the count query, breaking the pagination footer.
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $cat_count = $count_res->get('tickets t')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('t.*,tty.title,u.username')->join('ticket_types tty', 'tty.id=t.ticket_type_id', 'left')->join('users u', 'u.id=t.user_id', 'left');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('tickets t')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $status = "";
        $tempRow = array();
        foreach ($cat_search_res as $row) {
            $row = output_escaping($row);
            // The data-* attributes below used to be entirely unquoted (data-username=Foo
            // instead of data-username="Foo"), and output_escaping() doesn't HTML-encode
            // anything - both together meant a subject/username containing a space or quote
            // broke the markup, and one containing HTML was a stored-XSS route straight into
            // the ticket-view modal's JS (which reads these via .data()/.html()).
            $operate = '<a href="javascript:void(0)" class="view_ticket btn btn-success action-btn btn-xs mr-1 mb-1 ml-1" data-id="' . $row['id'] . '" data-username="' . html_escape($row['username']) . '" data-date_created="' . html_escape($row['date_created']) . '" data-subject="' . html_escape($row['subject']) . '" data-status="' . html_escape($row['status']) . '" data-ticket_type="' . html_escape($row['title']) . '" title="View" data-target="#ticket_modal" data-toggle="modal" ><i class="fa fa-eye"></i></a>';
            $operate .= ' <a href="javascript:void(0)" id="delete-ticket" data-id="' . $row['id'] . '" class="btn btn-danger action-btn mr-1 mb-1 ml-1 btn-xs"><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row['id'];
            $tempRow['ticket_type_id'] = $row['ticket_type_id'];
            $tempRow['user_id'] = $row['user_id'];
            $tempRow['subject'] = html_escape($row['subject']);
            $tempRow['email'] = html_escape($row['email']);
            $tempRow['description'] = html_escape($row['description']);
            if ($row['status'] == "1") {
                $status = '<label class="badge badge-secondary">PENDING</label>';
            } else if ($row['status'] == "2") {
                $status = '<label class="badge badge-info">OPENED</label>';
            } else if ($row['status'] == "3") {
                $status = '<label class="badge badge-success">RESOLVED</label>';
            } else if ($row['status'] == "4") {
                $status = '<label class="badge badge-danger">CLOSED</label>';
            } else if ($row['status'] == "5") {
                $status = '<label class="badge badge-warning">REOPENED</label>';
            }
            $tempRow['status'] = $status;
            $tempRow['last_updated'] = $row['last_updated'];
            $tempRow['date_created'] = $row['date_created'];
            $tempRow['username'] = html_escape($row['username']);
            $tempRow['ticket_type'] = html_escape($row['title']);
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
    function get_message_list($ticket_id = "", $user_id = "", $search = "", $offset = 0, $limit = 50, $sort = "tm.id", $order = "DESC", $data = array(), $msg_id = "")
    {
        $multipleWhere = '';

        // This function has exactly one caller (Tickets::get_ticket_messages()), which already
        // validates/sanitizes every one of these before calling in - re-reading raw $_GET here
        // discarded that validation entirely (including the numeric checks on ticket_id/user_id/
        // limit/offset) in favor of whatever was in the querystring.
        if ($sort === 'id') {
            $sort = 'tm.id';
        }
        $allowed_sort_columns = ['tm.id', 'tm.date_created', 't.subject'];
        if (!in_array($sort, $allowed_sort_columns, true)) {
            $sort = 'tm.id';
        }
        $order = (strtolower((string) $order) === 'asc') ? 'ASC' : 'DESC';

        if (!empty($search)) {
            $multipleWhere = [
                '`u.id`' => $search, '`u.username`' => $search, '`t.subject`' => $search, '`tm.message`' => $search
            ];
        }

        if (!empty($ticket_id)) {
            $where['tm.ticket_id'] = $ticket_id;
        }

        if (!empty($user_id)) {
            $where['tm.user_id'] = $user_id;
        }
        if (!empty($msg_id)) {
            $where['tm.id'] = $msg_id;
        }

        // Was or_where() (exact match) while the data query below uses or_like() (partial
        // match), corrupting the pagination total on a partial search the same as the ticket
        // list's own count query.
        $count_res = $this->db->select(' COUNT(tm.id) as `total`')->join('tickets t', 't.id=tm.ticket_id', 'left')->join('users u', 'u.id=tm.user_id', 'left');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $cat_count = $count_res->get('ticket_messages tm')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }
        $search_res = $this->db->select('tm.*,t.subject,u.username')->join('tickets t', 't.id=tm.ticket_id', 'left')->join('users u', 'u.id=tm.user_id', 'left');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('ticket_messages tm')->result_array();
        $rows = $tempRow = $bulkData = array();
        $bulkData['total'] = $total;
        $bulkData['error'] = (empty($cat_search_res)) ? true : false;
        $bulkData['message'] = (empty($cat_search_res)) ? 'Ticket Message(s) does not exist' : 'Message retrieved successfully';
        $bulkData['total'] = (empty($cat_search_res)) ? 0 : $total;
        if (!empty($cat_search_res)) {
            $data = $this->config->item('type');
            foreach ($cat_search_res as $row) {
                $row = output_escaping($row);
                $tempRow['id'] = $row['id'];
                $tempRow['user_type'] = $row['user_type'];
                $tempRow['user_id'] = $row['user_id'];
                $tempRow['ticket_id'] = $row['ticket_id'];
                // output_escaping() doesn't HTML-encode - message is free text and the JS
                // renders it via .html(), so this was a stored-XSS route in the ticket thread.
                $tempRow['message'] = (!empty($row['message'])) ? html_escape($row['message']) : "";
                $tempRow['name'] = html_escape($row['username']);
                if (!empty($row['attachments'])) {
                    $attachments = json_decode($row['attachments'], 1);
                    $counter = 0;
                    foreach ($attachments as $row1) {
                        $tmpRow['media'] = get_image_url($row1);
                        $file = new SplFileInfo($row1);
                        $ext  = $file->getExtension();
                        if (in_array($ext, $data['image']['types'])) {
                            $tmpRow['type'] = "image";
                        } else if (in_array($ext, $data['video']['types'])) {
                            $tmpRow['type'] = "video";
                        } else if (in_array($ext, $data['document']['types'])) {
                            $tmpRow['type'] = "document";
                        } else if (in_array($ext, $data['archive']['types'])) {
                            $tmpRow['type'] = "archive";
                        }
                        $attachments[$counter] = $tmpRow;
                        $counter++;
                    }
                } else {
                    $attachments = array();
                }
                $tempRow['attachments'] = $attachments;
                $tempRow['subject'] = html_escape($row['subject']);
                $tempRow['last_updated'] = $row['last_updated'];
                $tempRow['date_created'] = $row['date_created'];
                $rows[] = $tempRow;
            }
            $bulkData['data'] = $rows;
        } else {
            $bulkData['data'] = [];
        }

        print_r(json_encode($bulkData));
    }

    function get_tickets($ticket_id = "", $ticket_type_id = "", $user_id = "", $status = "", $search = "", $offset = "", $limit = "1", $sort = "", $order = "")
    {

        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                '`u.id`' => $search, '`u.username`' => $search, '`u.email`' => $search, '`u.mobile`' => $search, '`t.subject`' => $search, '`t.email`' => $search, '`t.description`' => $search, '`tty.title`' => $search
            ];
        }
        if (!empty($ticket_id)) {
            $where['t.id'] = $ticket_id;
        }
        if (!empty($ticket_type_id)) {
            $where['t.ticket_type_id'] = $ticket_type_id;
        }
        if (!empty($user_id)) {
            $where['t.user_id'] = $user_id;
        }
        if (!empty($status)) {
            $where['t.status'] = $status;
        }
        $count_res = $this->db->select(' COUNT(u.id) as `total`')->join('ticket_types tty', 'tty.id=t.ticket_type_id', 'left')->join('users u', 'u.id=t.user_id', 'left');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $cat_count = $count_res->get('tickets t')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('t.*,tty.title,u.username')->join('ticket_types tty', 'tty.id=t.ticket_type_id', 'left')->join('users u', 'u.id=t.user_id', 'left');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('tickets t')->result_array();
        $rows = $tempRow = $bulkData = array();
        $bulkData['error'] = (empty($cat_search_res)) ? true : false;
        $bulkData['message'] = (empty($cat_search_res)) ? 'Ticket(s) does not exist' : 'Tickets retrieved successfully';
        $bulkData['total'] = (empty($cat_search_res)) ? 0 : $total;
        if (!empty($cat_search_res)) {
            foreach ($cat_search_res as $row) {
                $row = output_escaping($row);
                $tempRow['id'] = $row['id'];
                $tempRow['ticket_type_id'] = $row['ticket_type_id'];
                $tempRow['user_id'] = $row['user_id'];
                $tempRow['subject'] = html_escape($row['subject']);
                $tempRow['email'] = $row['email'];
                $tempRow['description'] = $row['description'];
                $tempRow['status'] = $row['status'];
                $tempRow['last_updated'] = $row['last_updated'];
                $tempRow['date_created'] = $row['date_created'];
                $tempRow['name'] = html_escape($row['username']);
                $tempRow['ticket_type'] = html_escape($row['title']);
                $rows[] = $tempRow;
            }
            $bulkData['data'] = $rows;
        } else {
            $bulkData['data'] = [];
        }
        return $bulkData;
    }

    function get_messages($ticket_id = "", $user_id = "", $search = "", $offset = "", $limit = "", $sort = "", $order = "", $data = array(), $msg_id = "")
    {

        $multipleWhere = '';
        $where = array();
        if (!empty($search)) {
            $multipleWhere = [
                '`u.id`' => $search, '`u.username`' => $search, '`t.subject`' => $search, '`tm.message`' => $search
            ];
        }
        if (!empty($ticket_id)) {
            $where['tm.ticket_id'] = $ticket_id;
        }

        if (!empty($user_id)) {
            $where['tm.user_id'] = $user_id;
        }
        if (!empty($msg_id)) {
            $where['tm.id'] = $msg_id;
        }

        $count_res = $this->db->select(' COUNT(tm.id) as `total`')->join('tickets t', 't.id=tm.ticket_id', 'left')->join('users u', 'u.id=tm.user_id', 'left');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->group_start();
            $count_res->or_like($multipleWhere);
            $count_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $cat_count = $count_res->get('ticket_messages tm')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }
        $search_res = $this->db->select('tm.*,t.subject,u.username')->join('tickets t', 't.id=tm.ticket_id', 'left')->join('users u', 'u.id=tm.user_id', 'left');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->group_start();
            $search_res->or_like($multipleWhere);
            $search_res->group_end();
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('ticket_messages tm')->result_array();
        $rows = $tempRow = $bulkData = $tmpRow = array();
        $bulkData['error'] = (empty($cat_search_res)) ? true : false;
        $bulkData['message'] = (empty($cat_search_res)) ? 'Ticket Message(s) does not exist' : 'Message retrieved successfully';
        $bulkData['total'] = (empty($cat_search_res)) ? 0 : $total;
        if (!empty($cat_search_res)) {
            foreach ($cat_search_res as $row) {
                $row = output_escaping($row);
                $tempRow['id'] = $row['id'];
                $tempRow['user_type'] = $row['user_type'];
                $tempRow['user_id'] = $row['user_id'];
                $tempRow['ticket_id'] = $row['ticket_id'];
                // output_escaping() doesn't HTML-encode - message is free text and the JS
                // renders it via .html(), so this was a stored-XSS route in the ticket thread.
                $tempRow['message'] = (!empty($row['message'])) ? html_escape($row['message']) : "";
                $tempRow['name'] = html_escape($row['username']);
                if (!empty($row['attachments'])) {
                    $attachments = json_decode($row['attachments'], 1);
                    $counter = 0;
                    foreach ($attachments as $row1) {
                        $tmpRow['media'] = get_image_url($row1);
                        $file = new SplFileInfo($row1);
                        $ext  = $file->getExtension();
                        if (in_array($ext, $data['image']['types'])) {
                            $tmpRow['type'] = "image";
                        } else if (in_array($ext, $data['video']['types'])) {
                            $tmpRow['type'] = "video";
                        } else if (in_array($ext, $data['document']['types'])) {
                            $tmpRow['type'] = "document";
                        } else if (in_array($ext, $data['archive']['types'])) {
                            $tmpRow['type'] = "archive";
                        }
                        $attachments[$counter] = $tmpRow;
                        $counter++;
                    }
                } else {
                    $attachments = array();
                }
                $tempRow['attachments'] = $attachments;
                $tempRow['subject'] = html_escape($row['subject']);
                $tempRow['last_updated'] = $row['last_updated'];
                $tempRow['date_created'] = $row['date_created'];
                $rows[] = $tempRow;
            }
            $bulkData['data'] = $rows;
        } else {
            $bulkData['data'] = [];
        }
        return $bulkData;
    }

    function delete_ticket($ticket_id)
    {
        if (delete_details(['id' => $ticket_id], 'tickets') == TRUE) {
            if (delete_details(['ticket_id' => $ticket_id], 'ticket_messages') == TRUE) {
                return true;
            }
        } else {
            return false;
        }
    }

    function get_ticket_type_list()
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

        // Sort column was passed straight into order_by() with no whitelist - an injection
        // route the same as already fixed on other list pages.
        $allowed_sort_columns = ['id', 'title', 'date_created'];
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns, true)) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') {
            $order = 'DESC';
        }

        if (isset($_GET['search']) and $_GET['search'] != '') {
            $search = $_GET['search'];
            $multipleWhere = [
                '`id`' => $search, '`title`' => $search
            ];
        }

        $count_res = $this->db->select(' COUNT(id) as `total`');

        // Was or_where() here (exact match) while the data query below uses or_like() (partial
        // match), corrupting the pagination total on a partial search.
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $count_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }

        $cat_count = $count_res->get('ticket_types')->result_array();
        foreach ($cat_count as $row) {
            $total = $row['total'];
        }

        $search_res = $this->db->select('*');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $search_res->or_like($multipleWhere);
        }
        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }

        $cat_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('ticket_types')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $status = "";
        $tempRow = array();
        foreach ($cat_search_res as $row) {
            $row = output_escaping($row);
            $operate = ' <a href="javascript:void(0)" class="edit_btn action-btn btn btn-success btn-xs ml-1 mr-1 mb-1" title="Edit" data-id="' . $row['id'] . '" data-url="admin/tickets/manage_ticket_types/"><i class="fa fa-pen"></i></a>';
            $operate .= '<a class="delete-ticket-type btn btn-danger action-btn btn-xs ml-1 mr-1 mb-1" title="Delete" href="javascript:void(0)" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row['id'];
            $tempRow['title'] = html_escape($row['title']);
            $tempRow['date_created'] = $row['date_created'];
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
