<?php
/**
 * My Account > Transactions.
 *
 * Rebuilt on the shared account shell. The table itself is still
 * bootstrap-table against my-account/get-transactions (that endpoint pages,
 * sorts and searches server-side), skinned by `.czap-table` in
 * account-suite.css.
 *
 * The Username column is dropped: the endpoint scopes every row to the
 * logged-in customer, so it printed the same name on every line and cost the
 * narrow columns their width on a phone.
 */

/* --------------------------------------------------------------- actions -- */
ob_start(); ?>
<a class="czap-btn czap-btn--ghost" href="<?= base_url('my-account/wallet') ?>">
    <i class="uil uil-wallet"></i> Wallet history
</a>
<?php $page_actions = ob_get_clean();

/* --------------------------------------------------------------- content -- */
ob_start(); ?>

<div class="czap-alert czap-alert--info">
    <i class="uil uil-info-circle"></i>
    <span>
        Every payment you have made to Cretzo, whichever method it used. For wallet top-ups,
        refunds and Cretzo cash spent at checkout, see your
        <a href="<?= base_url('my-account/wallet') ?>">wallet history</a>.
    </span>
</div>

<div class="czap-table czap-scroll">
    <table data-toggle="table"
           data-url="<?= base_url('my-account/get-transactions') ?>"
           data-side-pagination="server" data-pagination="true"
           data-page-list="[5, 10, 20, 50, 100]" data-page-size="10"
           data-search="true" data-trim-on-search="false"
           data-show-refresh="true" data-show-columns="true" data-show-export="true"
           data-sort-name="id" data-sort-order="desc"
           data-mobile-responsive="true"
           data-query-params="transaction_query_params">
        <thead>
            <tr>
                <th data-field="id" data-sortable="true"><?= !empty($this->lang->line('id')) ? $this->lang->line('id') : 'ID' ?></th>
                <th data-field="order_id" data-sortable="false"><?= !empty($this->lang->line('order_id')) ? $this->lang->line('order_id') : 'Order ID' ?></th>
                <th data-field="txn_id" data-sortable="false"><?= !empty($this->lang->line('transaction_id')) ? $this->lang->line('transaction_id') : 'Transaction ID' ?></th>
                <th data-field="payu_txn_id" data-sortable="false" data-visible="false"><?= !empty($this->lang->line('pay_transaction_id')) ? $this->lang->line('pay_transaction_id') : 'Gateway reference' ?></th>
                <th data-field="amount" data-sortable="false"><?= !empty($this->lang->line('amount')) ? $this->lang->line('amount') : 'Amount' ?></th>
                <th data-field="status" data-sortable="false"><?= !empty($this->lang->line('status')) ? $this->lang->line('status') : 'Status' ?></th>
                <th data-field="message" data-sortable="false" data-visible="false"><?= !empty($this->lang->line('message')) ? $this->lang->line('message') : 'Message' ?></th>
                <th data-field="txn_date" data-sortable="false"><?= !empty($this->lang->line('date')) ? $this->lang->line('date') : 'Date' ?></th>
            </tr>
        </thead>
    </table>
</div>

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => !empty($this->lang->line('transactions')) ? $this->lang->line('transactions') : 'Transactions',
    'page_sub'     => 'Every payment on your account',
    'page_icon'    => 'uil-receipt-alt',
    'page_actions' => $page_actions,
    'page_content' => $page_content,
]);
