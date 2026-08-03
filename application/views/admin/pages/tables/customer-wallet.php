<div class="content-wrapper admin-customer-wallet-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-wallet mr-2 text-primary-theme"></i>Customer Wallet Transactions</h4>
                    <p class="text-muted mb-0 small">Manually credit or debit a customer's wallet, and review their history.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Customer Wallet</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set"><i class="fas fa-hand-holding-usd"></i></span>
                            <h5 class="mb-0">Credit / Debit Wallet</h5>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" method="POST" action="<?= base_url('admin/customer/update_customer_wallet') ?>" enctype="multipart/form-data">
                            <div class="card-body">
                                <input type="hidden" id='user_id' name='user_id'>
                                <div class="form-group row">
                                    <label for="customer" class="col-sm-4 col-form-label">Customer</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="customer_dtls" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="type" class="col-sm-4 col-form-label">Select Type</label>
                                    <div class="col-sm-8">
                                        <select name="type" class='form-control'>
                                            <option value="credit">Credit </option>
                                            <option value="debit">Debit</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="amount" class="col-sm-4 col-form-label">Amount</label>
                                    <div class="col-sm-8">
                                        <input type="number" class="form-control" id="amount" placeholder="Enter Amount" name="amount" min="0.01" step="0.01">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="message" class="col-sm-4 col-form-label">Message</label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" id="message" placeholder="Enter Message Here" name="message"></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-primary-theme" id="submit_btn">Submit</button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set"><i class="fas fa-user-check"></i></span>
                            <h5 class="mb-0">Select User</h5>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' id='customers' data-toggle="table" data-url="<?= base_url('admin/customer/view_customer') ?>" data-side-pagination="server" data-click-to-select="true" data-pagination="true" data-id-field="id" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="#toolbar" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="state" data-radio='true'></th>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="name" data-sortable="true">Name</th>
                                        <th data-field="email" data-sortable="true">Email</th>
                                        <th data-field="balance" data-sortable="true">Balance</th>
                                    </tr>
                                </thead>
                            </table>
                        </div><!-- .card-body -->
                    </div><!-- .card -->
                </div>
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set"><i class="fas fa-history"></i></span>
                            <h5 class="mb-0">Customer Wallet Transactions</h5>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/transaction/view_transactions') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="customer_wallet_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="name" data-sortable="false">User Name</th>
                                        <th data-field="type" data-sortable="false" data-formatter="walletTypeFormatter">Type</th>
                                        <th data-field="amount" data-sortable="true" data-formatter="walletAmountFormatter">Amount</th>
                                        <th data-field="status" data-sortable="false">Status</th>
                                        <th data-field="message" data-sortable="false">Message</th>
                                        <th data-field="date" data-sortable="false">Date</th>
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
    .admin-customer-wallet-page .text-primary-theme { color: var(--color-orange); }

    .admin-customer-wallet-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-customer-wallet-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-customer-wallet-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-customer-wallet-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-customer-wallet-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-customer-wallet-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-customer-wallet-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-customer-wallet-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-customer-wallet-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-customer-wallet-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-customer-wallet-page table.table thead th {
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
    .admin-customer-wallet-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-customer-wallet-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-customer-wallet-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-customer-wallet-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-customer-wallet-page .wallet-type-credit,
    .admin-customer-wallet-page .wallet-type-refund { color: #1e6b33; font-weight: 600; }
    .admin-customer-wallet-page .wallet-type-debit { color: #8a2f27; font-weight: 600; }
    .admin-customer-wallet-page .wallet-amount-credit,
    .admin-customer-wallet-page .wallet-amount-refund { color: #1e6b33; font-weight: 600; }
    .admin-customer-wallet-page .wallet-amount-debit { color: #8a2f27; font-weight: 600; }
</style>

<script>
    // Purely cosmetic - colour-codes credits/refunds vs debits so the direction of money
    // movement is scannable at a glance, matching the same formatter already added to Seller
    // Wallet Transactions and View Transaction.
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
