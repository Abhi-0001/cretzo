<div class="content-wrapper admin-order-tracking-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-map-marker-alt mr-2 text-primary-theme"></i>Order Tracking</h4>
                    <p class="text-muted mb-0 small">Courier and tracking details assigned to every order.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Order Tracking</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-map-marker-alt"></i></span>
                    <h5 class="mb-0">All Tracking Records</h5>
                </div>
                <div class="card-body">
                    <table class='table-striped' data-toggle="table"
                        data-url="<?= base_url('admin/orders/get-order-tracking') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "order-tracking-list", "ignoreColumn": ["operate"]}'
                        data-query-params="order_tracking_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="order_id" data-sortable="true" data-align='center'>Order ID</th>
                                <th data-field="order_item_id" data-sortable="false" data-align='center'>Order Item ID</th>
                                <th data-field="courier_agency" data-sortable="false" data-align='center'>Courier Agency</th>
                                <th data-field="tracking_id" data-sortable="false" data-align='center'>Tracking ID</th>
                                <th data-field="url" data-sortable="false" data-align='center'>URL</th>
                                <th data-field="date" data-sortable="false" data-align='center'>Date</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-order-tracking-page .text-primary-theme { color: var(--color-orange); }

    .admin-order-tracking-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-order-tracking-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-order-tracking-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-order-tracking-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-order-tracking-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-order-tracking-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-order-tracking-page .fixed-table-toolbar .btn-group > .btn,
    .admin-order-tracking-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-order-tracking-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-order-tracking-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-order-tracking-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-order-tracking-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-order-tracking-page table.table thead th {
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
    .admin-order-tracking-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-order-tracking-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-order-tracking-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-order-tracking-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    /* Same action-button wrapping fix applied to Manage Orders - these rows carry the same
       .action-btn icons and would otherwise stack vertically instead of sitting in a row. */
    .admin-order-tracking-page td:has(.action-btn) { white-space: nowrap; }
    .admin-order-tracking-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
