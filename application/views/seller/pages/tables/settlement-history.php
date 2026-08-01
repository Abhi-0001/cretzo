<div class="content-wrapper settlement-history-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2 text-primary-theme"></i>Settlement History</h4>
                    <p class="text-muted mb-0 small">Commission deducted and net amount credited for each of your completed orders.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Settlement History</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-file-invoice-dollar"></i></span>
                        <h5 class="mb-0">Your Settlements</h5>
                    </div>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='settlement_history_table' data-toggle="table" data-url="<?= base_url('seller/settlement/view-settlement-history-list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="order_id" data-sortable="true">Order ID</th>
                                <th data-field="order_amount" data-sortable="false">Order Amount</th>
                                <th data-field="commission_percent" data-sortable="false">Commission %</th>
                                <th data-field="commission_amount" data-sortable="false">Commission Amount</th>
                                <th data-field="net_payable" data-sortable="false">Net Payable</th>
                                <th data-field="settlement_status" data-sortable="false">Settlement Status</th>
                                <th data-field="created_at" data-sortable="true">Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .settlement-history-page .text-primary-theme { color: var(--color-orange); }

    .settlement-history-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .settlement-history-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .settlement-history-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        margin-right: 10px;
    }
    .settlement-history-page .header-icon.bg-set { background: var(--color-orange); }

    .settlement-history-page .fixed-table-toolbar { margin-bottom: 10px; }
    .settlement-history-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .settlement-history-page .fixed-table-toolbar .btn-group > .btn,
    .settlement-history-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .settlement-history-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .settlement-history-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .settlement-history-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .settlement-history-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .settlement-history-page table.table thead th {
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
    .settlement-history-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .settlement-history-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .settlement-history-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .settlement-history-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>
