<div class="content-wrapper admin-view-transaction-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-money-bill-wave mr-2 text-primary-theme"></i>View Transaction</h4>
                    <p class="text-muted mb-0 small">Every payment transaction recorded against an order.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Transaction</li>
                    </ol>
                </div>
            </div>
            <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="transaction_modal" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="user_name"></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <!-- form start -->
                                    <form class="form-horizontal " id="edit_transaction_form" action="<?= base_url('admin/transaction/edit-transactions/'); ?>" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="id" id="id">

                                        <div class="form-group ">
                                            <label for="transaction"> Update Transaction </label>
                                            <select class="form-control" name="status" id="t_status">
                                                <option value="awaiting"> Awaiting </option>
                                                <option value="Success"> Success </option>
                                                <option value="Failed"> Failed </option>
                                            </select>
                                        </div>
                                        <div class="form-group ">
                                            <label for="txn_id">Txn_id</label>
                                            <input type="text" class="form-control" name="txn_id" id="txn_id" placeholder="txn_id" />
                                        </div>
                                        <div class="form-group ">
                                            <label for="message">Message</label>
                                            <input type="text" class="form-control" name="message" id="message" placeholder="Message" />
                                        </div>
                                        <div class="form-group">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-primary-theme" id="submit_btn">Update Transaction</button>
                                        </div>
                                        <div class="d-flex justify-content-center">
                                            <div class="form-group" id="error_box">
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!--/.col-md-12-->
                            </div>
                            <!-- /.row -->
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set"><i class="fas fa-money-bill-wave"></i></span>
                            <h5 class="mb-0">Transactions</h5>
                        </div>
                        <div class="card-body">
                            <input type='hidden' id='transaction_user_id' value='<?= (isset($_GET['user_id']) && !empty($_GET['user_id'])) ? $_GET['user_id'] : '' ?>'>
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/transaction/view_transactions') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="transaction_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">Id</th>
                                        <th data-field="name" data-sortable="false">User Name</th>
                                        <th data-field="order_id" data-sortable="false">Order Id</th>
                                        <th data-field="txn_id" data-sortable="false">Transaction Id</th>
                                        <th data-field="type" data-sortable="false" data-formatter="walletTypeFormatter">Transaction type</th>
                                        <th data-field="payu_txn_id" data-sortable="false" data-visible="false">Pay Transaction Id</th>
                                        <th data-field="amount" data-sortable="true" data-formatter="walletAmountFormatter">Amount</th>
                                        <th data-field="status" data-sortable="false">Status</th>
                                        <th data-field="message" data-sortable="false" data-visible="false">Message</th>
                                        <th data-field="txn_date" data-sortable="false">Date</th>
                                        <th data-field="operate" data-sortable="false">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div><!-- .card-body -->
                    </div><!-- .card -->
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-view-transaction-page .text-primary-theme { color: var(--color-orange); }

    .admin-view-transaction-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-view-transaction-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-view-transaction-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-view-transaction-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-view-transaction-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-view-transaction-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-view-transaction-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-view-transaction-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-view-transaction-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-view-transaction-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-view-transaction-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-view-transaction-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-view-transaction-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-view-transaction-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-view-transaction-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-view-transaction-page td:has(.action-btn) { white-space: nowrap; }
    .admin-view-transaction-page .action-btn { display: inline-block; vertical-align: middle; }

    .admin-view-transaction-page .wallet-type-credit,
    .admin-view-transaction-page .wallet-type-refund { color: #1e6b33; font-weight: 600; }
    .admin-view-transaction-page .wallet-type-debit { color: #8a2f27; font-weight: 600; }
    .admin-view-transaction-page .wallet-amount-credit,
    .admin-view-transaction-page .wallet-amount-refund { color: #1e6b33; font-weight: 600; }
    .admin-view-transaction-page .wallet-amount-debit { color: #8a2f27; font-weight: 600; }
</style>

<script>
    // Purely cosmetic - the values themselves already come escaped from the server; this just
    // colour-codes credits/refunds vs debits so the direction of money movement is scannable at
    // a glance, matching the same formatter already added to Seller Wallet Transactions.
    function walletTypeFormatter(value) {
        if (value === 'debit') {
            return '<span class="wallet-type-debit">Debit</span>';
        }
        if (value === 'credit') {
            return '<span class="wallet-type-credit">Credit</span>';
        }
        if (value === 'refund') {
            return '<span class="wallet-type-refund">Refund</span>';
        }
        return $('<div>').text(value || '').html();
    }

    function walletAmountFormatter(value, row) {
        var amount = $('<div>').text(value == null ? '' : value).html();
        if (row.type === 'debit') {
            return '<span class="wallet-amount-debit">- ' + amount + '</span>';
        }
        if (row.type === 'credit' || row.type === 'refund') {
            return '<span class="wallet-amount-credit">+ ' + amount + '</span>';
        }
        return amount;
    }
</script>
