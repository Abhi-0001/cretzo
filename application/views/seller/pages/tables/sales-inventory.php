<div class="content-wrapper sales-inventory-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-chart-line mr-2 text-primary-theme"></i>Sales Inventory Report</h4>
                    <p class="text-muted mb-0 small">Stock remaining vs. units sold, per product.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Sales Inventory Reports</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-chart-line"></i></span>
                    <h5 class="mb-0">Inventory vs. Sales</h5>
                </div>
                <div class="card-body">
                    <div class="product-filters-bar row align-items-end">
                        <div class="col-md-4 mb-2">
                            <label class="filter-label"><i class="far fa-clock mr-1"></i>Date Range</label>
                            <input type="text" class="form-control" id="datepicker" placeholder="Select Date Range To Filter" autocomplete="off">
                            <input type="hidden" id="start_date">
                            <input type="hidden" id="end_date">
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-primary-theme btn-block" onclick="status_date_wise_search()">Filter</button>
                        </div>
                    </div>

                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/Sales_inventory/get_seller_sales_inventory_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="sales_inventory_report_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable='true'>Order Item ID</th>
                                <th data-field="name" data-sortable='true'>Product name</th>
                                <th data-field="stock" data-sortable='true'>Stock</th>
                                <th data-field="qty" data-sortable='true'>Sales Order</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .sales-inventory-page .text-primary-theme { color: var(--color-orange); }

    .sales-inventory-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .sales-inventory-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .sales-inventory-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .sales-inventory-page .header-icon.bg-set { background: var(--color-orange); }

    .sales-inventory-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .sales-inventory-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .sales-inventory-page .filter-label i { color: var(--color-orange); }
    .sales-inventory-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .sales-inventory-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .sales-inventory-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .sales-inventory-page .fixed-table-toolbar { margin-bottom: 10px; }
    .sales-inventory-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .sales-inventory-page .fixed-table-toolbar .btn-group > .btn,
    .sales-inventory-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .sales-inventory-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .sales-inventory-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .sales-inventory-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .sales-inventory-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .sales-inventory-page table.table thead th {
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
    .sales-inventory-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .sales-inventory-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .sales-inventory-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .sales-inventory-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>

<script>
    // Date range picker, the filter button, and the query-params function only ever
    // shipped in the admin JS bundle, which isn't loaded on seller pages. moment/
    // daterangepicker also load at the very bottom of <body>, after this inline script,
    // so the .daterangepicker() init itself must be deferred to document.ready — calling
    // it immediately throws "moment is not defined".
    $(document).ready(function () {
        $('#datepicker').daterangepicker({
            showDropdowns: true,
            alwaysShowCalendars: true,
            autoUpdateInput: false,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
    });
    $('#datepicker').on('apply.daterangepicker', function (ev, picker) {
        $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
    $('#datepicker').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#start_date').val('');
        $('#end_date').val('');
    });

    function status_date_wise_search() {
        $('.sales-inventory-page .table-striped').bootstrapTable('refresh');
    }

    function sales_inventory_report_query_params(p) {
        return {
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>
