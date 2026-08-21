<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Faq_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'language', 'function_helper']);
    }

    function add_faq($data)
    {
        // NOT escape_array()'d. escape_array() runs db->escape_str() over every value, and the
        // query builder below then escapes the already-escaped string a SECOND time, so every
        // save adds another layer of backslashes to the same text. Because the edit form is
        // populated from the stored value, editing an FAQ repeatedly compounds the damage -
        // reproduced live on this database: "it's" became "it\'s", then "it\\\'s", then
        // "it\\\\\\\'s" over three consecutive saves of an unchanged answer. The public FAQ
        // page hid the first layer (Faq_model::get_faqs() -> output_escaping() -> stripcslashes)
        // which is why this went unnoticed, but the admin list showed it immediately and two
        // edits were enough for the backslashes to reach customers.
        // The query builder parameter-escapes these values correctly on its own.
        $faq_data = [
            'question' => $data['question'],
            'answer' => $data['answer']
        ];
        if (isset($data['edit_faq'])) {
            $this->db->set($faq_data)->where('id', $data['edit_faq'])->update('faqs');
        } else {
            $this->db->insert('faqs', $faq_data);
        }
    }

    function get_faqs($offset, $limit, $sort, $order)
    {
        // Whitelist against the actual selected columns - $sort/$order are passed straight
        // into order_by() unchecked by both callers (the public FAQ page and the mobile API's
        // get_faqs endpoint), an injection-shaped route reachable by any logged-in app user.
        $allowed_sort_columns = ['id', 'question', 'answer', 'status'];
        $sort = in_array((string) $sort, $allowed_sort_columns, true) ? (string) $sort : 'id';
        $order = (strtolower((string) $order) === 'desc') ? 'desc' : 'asc';
        $faqs_data = [];
        $count_res = $this->db->select(' COUNT(id) as `total` ')->where('status', '1')->get('faqs')->result_array();
        $search_res = $this->db->select(' * ')->where('status', '1')->order_by($sort, $order)->limit($limit, $offset)->get('faqs')->result_array();
        // output_escaping() (which is really stripcslashes()) used to run over every row here.
        // It existed only to undo ONE layer of the double-escaping that add_faq() was doing on
        // write - now that add_faq() stores the text verbatim, it has nothing to undo and is
        // purely destructive: stripcslashes() interprets C-style escapes, so a legitimate
        // answer mentioning a Windows path like C:\temp was served to customers as
        // "C:<tab>emp" (reproduced live). Both consumers escape for their own context already -
        // the storefront view html_escape()s question and answer, and the mobile API
        // json_encode()s them.
        $faqs_data['total'] = $count_res[0]['total'];
        $faqs_data['data'] = $search_res;
        return  $faqs_data;
    }
}