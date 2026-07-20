<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <p class="text-n"><?= $users->username ?></p>
    <div class="overview-container">

        <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>

        <div class="overview-right">
            <h1 class="heading-n overview-right-heading"><?= !empty($this->lang->line('transactions')) ? $this->lang->line('transactions') : 'Transactions' ?></h1>

            <hr class="mt-4 mb-4">

            <div class="card-body p-0">
                <table class='' data-toggle="table" data-url="<?= base_url('my-account/get-transactions') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="transaction_query_params">
                    <thead class="thead-light">
                        <tr>
                            <th data-field="id" data-sortable="true"><?= !empty($this->lang->line('id')) ? $this->lang->line('id') : 'ID' ?></th>
                            <th data-field="name" data-sortable="false"><?= !empty($this->lang->line('username')) ? $this->lang->line('username') : 'Username' ?></th>
                            <th data-field="order_id" data-sortable="false"><?= !empty($this->lang->line('order_id')) ? $this->lang->line('order_id') : 'Order ID' ?></th>
                            <th data-field="txn_id" data-sortable="false"><?= !empty($this->lang->line('transaction_id')) ? $this->lang->line('transaction_id') : 'Transaction ID' ?></th>
                            <th data-field="payu_txn_id" data-sortable="false" data-visible="false"><?= !empty($this->lang->line('pay_transaction_id')) ? $this->lang->line('pay_transaction_id') : 'Payment Transaction ID' ?></th>
                            <th data-field="amount" data-sortable="false"><?= !empty($this->lang->line('amount')) ? $this->lang->line('amount') : 'Amount' ?></th>
                            <th data-field="status" data-sortable="false"><?= !empty($this->lang->line('status')) ? $this->lang->line('status') : 'Status' ?></th>
                            <th data-field="message" data-sortable="false" data-visible="false"><?= !empty($this->lang->line('message')) ? $this->lang->line('message') : 'Message' ?></th>
                            <th data-field="txn_date" data-sortable="false"><?= !empty($this->lang->line('date')) ? $this->lang->line('date') : 'Date' ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>