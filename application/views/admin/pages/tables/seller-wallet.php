<div class="content-wrapper admin-seller-wallet-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-wallet mr-2 text-primary-theme"></i>Seller Wallet Transactions</h4>
                    <p class="text-muted mb-0 small">Every credit, debit and refund posted to a seller's wallet.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Seller Wallet</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex align-items-center">
                    <span class="header-icon bg-set"><i class="fas fa-wallet"></i></span>
                    <h5 class="mb-0">Transactions</h5>
                </div>
                <div class="card-body">
                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/transaction/view_transactions') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="seller_wallet_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="name" data-sortable="false">User Name</th>
                                <th data-field="type" data-sortable="false" data-formatter="walletTypeFormatter">Type</th>
                                <th data-field="amount" data-sortable="false" data-formatter="walletAmountFormatter">Amount</th>
                                <th data-field="status" data-sortable="false">Status</th>
                                <th data-field="message" data-sortable="false">Message</th>
                                <th data-field="date" data-sortable="false">Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-seller-wallet-page .text-primary-theme { color: var(--color-orange); }

    .admin-seller-wallet-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-seller-wallet-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-seller-wallet-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-seller-wallet-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-seller-wallet-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-seller-wallet-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-seller-wallet-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-seller-wallet-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-seller-wallet-page table.table thead th {
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
    .admin-seller-wallet-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-seller-wallet-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-seller-wallet-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-seller-wallet-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-seller-wallet-page .wallet-type-credit,
    .admin-seller-wallet-page .wallet-type-refund { color: #1e6b33; font-weight: 600; }
    .admin-seller-wallet-page .wallet-type-debit { color: #8a2f27; font-weight: 600; }
    .admin-seller-wallet-page .wallet-amount-credit,
    .admin-seller-wallet-page .wallet-amount-refund { color: #1e6b33; font-weight: 600; }
    .admin-seller-wallet-page .wallet-amount-debit { color: #8a2f27; font-weight: 600; }
</style>

<script>
    // Purely cosmetic - the values themselves already come escaped from the server; this just
    // colour-codes credits/refunds vs debits so the direction of money movement is scannable at
    // a glance, which the table previously rendered as plain unstyled numbers either way.
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
