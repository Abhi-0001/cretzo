<?php


defined('BASEPATH') or exit('No direct script access allowed');


class Product_faqs_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function edit_product_faqs($data, $id)
    {
        $data = escape_array($data);
        $this->db->set($data)->where('id', $id)->update('product_faqs');
    }

    function add_product_faqs($data)
    {
        $answered_by = fetch_details('users', 'id=' . $_SESSION['user_id'], 'username');
        $data = escape_array($data);
        $faq_data = [
            'product_id' => $data['product_id'],
            'user_id' => isset($data['user_id']) && !empty($data['user_id']) ? $data['user_id'] : $_SESSION['user_id'],
            'seller_id' => isset($data['seller_id']) && !empty($data['seller_id']) ? $data['seller_id'] : 0,
            'question' => $data['question'],
            'answer' => isset($data['answer']) && !empty($data['answer']) ? $data['answer'] : "",
            'answered_by' => isset($data['answer']) && !empty($data['answer']) ? $_SESSION['user_id'] : 0,
        ];
        $this->db->insert('product_faqs', $faq_data);
        return $this->db->insert_id();
    }


    public function delete_faq($faq_id)
    {
        $faq_id = escape_array($faq_id);
        $this->db->delete('product_faqs', ['id' => $faq_id]);
    }
    public function get_faqs($seller_id = null)
    {
        $offset = 0;
        $limit = 10;

        $multipleWhere = '';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        // $_GET['sort'] was passed straight into order_by(), and $_GET['order'] was never read
        // at all - $order stayed hardcoded at 'DESC' regardless of which direction the column
        // header actually requested. Confirmed live: requesting order=asc and order=desc against
        // this exact endpoint returned identical row order both times. Both are now resolved
        // through a fixed whitelist.
        $sortable = [
            'id' => 'pf.id', 'user_id' => 'pf.user_id', 'product_id' => 'pf.product_id',
            'question' => 'pf.question', 'answer' => 'pf.answer', 'answered_by' => 'pf.answered_by',
            'username' => 'u.username', 'date_added' => 'pf.date_added',
        ];
        $sort = (isset($_GET['sort']) && isset($sortable[$_GET['sort']])) ? $sortable[$_GET['sort']] : 'pf.id';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

        if (isset($_GET['product_id']) && $_GET['product_id'] != null) {
            $where['product_id'] = $_GET['product_id'];
        }
        $count_res = $this->db->select(' COUNT(pf.id) as total  ')->join('users u', 'u.id=pf.user_id');
        if (isset($_GET['search']) && trim($_GET['search'])) {
            $search = trim($_GET['search']);
            $multipleWhere = ['pf.id' => $search, 'pf.product_id' => $search, 'pf.user_id' => $search, 'pf.question' => $search, 'pf.answer' => $search];
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_start();
            $count_res->or_like($multipleWhere);
            $this->db->group_end();
        }
        if (isset($where) && !empty($where)) {
            $count_res->where($where);
        }
        // Scope to the requesting seller's own products only. This is joined against the
        // products table (rather than trusting product_faqs.seller_id, which historically
        // wasn't always set correctly) so a seller can never see another seller's FAQs.
        if (!empty($seller_id)) {
            $count_res->join('products p', 'p.id = pf.product_id')->where('p.seller_id', $seller_id);
        }

        $rating_count = $count_res->get('product_faqs pf')->result_array();
        foreach ($rating_count as $row) {
            $total = $row['total'];
        }

        // Was select('pf.*, u.username') plus a separate fetch_details() call inside the row
        // loop below to resolve answered_by's username - one extra query per row on every page
        // load. Joined once instead.
        $search_res = $this->db->select('pf.*, u.username as user_name, ab.username as answered_by_name')
            ->join('users u', 'u.id=pf.user_id')
            ->join('users ab', 'ab.id=pf.answered_by', 'left');

        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $this->db->group_start();
            $search_res->or_like($multipleWhere);
            $this->db->group_end();
        }

        if (isset($where) && !empty($where)) {
            $search_res->where($where);
        }
        if (!empty($seller_id)) {
            $search_res->join('products p', 'p.id = pf.product_id')->where('p.seller_id', $seller_id);
        }

        $rating_search_res = $search_res->order_by($sort, $order)->limit($limit, $offset)->get('product_faqs pf')->result_array();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();

        foreach ($rating_search_res as $row) {

            $row = output_escaping($row);
            $date = new DateTime($row['date_added']);

            if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
                $operate = ' <a href="javascript:void(0)" class="edit_btn action-btn btn btn-success btn-xs mr-1 mb-1 ml-1" title="View" data-id="' . $row['id'] . '" data-url="admin/product_faqs/"><i class="fa fa-edit"></i></a>';
                $operate .= '<a class="btn btn-danger btn-xs mr-1 mb-1 ml-1 action-btn delete-product-faq" href="javascript:void(0)" title="Delete" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            } else {
                $operate = ' <a href="javascript:void(0)" class="edit_btn action-btn btn btn-success btn-xs mr-1 mb-1 ml-1" title="View" data-id="' . $row['id'] . '" data-url="seller/product_faqs/"><i class="fa fa-edit"></i></a>';
                $operate .= '<a class="btn btn-danger btn-xs mr-1 mb-1 ml-1 action-btn delete-seller-product-faq" href="javascript:void(0)" title="Delete" data-id="' . $row['id'] . '" ><i class="fa fa-trash"></i></a>';
            }

            // Questions come from customers and answers from sellers/admins, both rendered here
            // by bootstrap-table, which does not escape cell values by default - html_escape()
            // added so neither can execute script in an admin's browser.
            $tempRow = array();
            $tempRow['id'] = $row['id'];
            $tempRow['user_id'] = $row['user_id'];
            $tempRow['product_id'] = $row['product_id'];
            $tempRow['votes'] = $row['votes'];
            $tempRow['question'] = html_escape((string) $row['question']);
            $tempRow['answer'] = html_escape((string) $row['answer']);
            $tempRow['answered_by'] = $row['answered_by'];
            $tempRow['answered_by_name'] = html_escape((string) ($row['answered_by_name'] ?? ''));
            $tempRow['username'] = html_escape((string) $row['user_name']);
            $tempRow['date_added'] = $date->format('d-M-Y');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        print_r(json_encode($bulkData));
    }
}
