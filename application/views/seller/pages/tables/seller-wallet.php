<div class="content-wrapper seller-wallet-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-rupee-sign mr-2 text-primary-theme"></i>Wallet Transactions</h4>
                    <p class="text-muted mb-0 small">Commission credits and other wallet activity on your account.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Wallet Transactions</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-rupee-sign"></i></span>
                    <h5 class="mb-0">Transactions</h5>
                </div>
                <div class="card-body">
                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/transaction/view_transactions') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="seller_wallet_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="name" data-sortable="false">User Name</th>
                                <th data-field="type" data-sortable="false">Type</th>
                                <th data-field="amount" data-sortable="false">Amount</th>
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
    .seller-wallet-page .text-primary-theme { color: var(--color-orange); }

    .seller-wallet-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .seller-wallet-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .seller-wallet-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .seller-wallet-page .header-icon.bg-set { background: var(--color-orange); }

    .seller-wallet-page .fixed-table-toolbar { margin-bottom: 10px; }
    .seller-wallet-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .seller-wallet-page .fixed-table-toolbar .btn-group > .btn,
    .seller-wallet-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .seller-wallet-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .seller-wallet-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .seller-wallet-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .seller-wallet-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .seller-wallet-page table.table thead th {
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
    .seller-wallet-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .seller-wallet-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .seller-wallet-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .seller-wallet-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>

<script>
    // data-query-params="seller_wallet_query_params" only ever shipped in the admin JS
    // bundle, which isn't loaded on seller pages — without this function the table would
    // still load (bootstrap-table just skips an unresolvable query-params name) but
    // without the transaction_type=wallet filter, so it would mix in raw order-payment
    // transactions alongside actual wallet credits.
    function seller_wallet_query_params(p) {
        return {
            transaction_type: 'wallet',
            user_type: 'seller',
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>
