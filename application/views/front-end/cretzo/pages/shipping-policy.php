<?php
/**
 * Shipping Policy.
 *
 * All four policy documents render through partials/legal-page.php. They used
 * to be four copy-pasted views differing only in a lang key and a variable
 * name, which is how half of them ended up printing a second <h1> on top of the
 * one already inside the stored document, and why none of them had any way to
 * navigate 25 numbered clauses.
 *
 * The stored blob is passed in raw; legal_page_prepare() (helpers/function_helper.php)
 * strips that duplicate <h1>, lifts the "Last Updated" line out of the prose,
 * and puts a linkable id on every clause.
 */
$this->load->view('front-end/' . THEME . '/partials/legal-page', [
    'legal_key'   => 'shipping',
    'legal_title' => !empty($this->lang->line('shipping_policy')) ? $this->lang->line('shipping_policy') : 'Shipping Policy',
    'legal_body'  => isset($shipping_policy) ? $shipping_policy : '',
]);
