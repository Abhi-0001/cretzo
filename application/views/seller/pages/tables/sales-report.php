<div class="content-wrapper sales-report-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-chart-line mr-2 text-primary-theme"></i>Sales Report</h4>
                    <p class="text-muted mb-0 small">Every order item you've sold, filterable by date.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Sales Reports</li>
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
                    <h5 class="mb-0">Sales</h5>
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

                    <table class="table table-striped" data-detail-view="true" data-detail-formatter="salesReport" data-auto-refresh="true" data-toggle="table" data-url="<?= base_url('seller/Sales_report/get_seller_sales_report_list') ?>" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 25, 50, 100, 200, All]" data-search="true" data-trim-on-search="false" data-show-columns="true" data-show-columns-search="true" data-show-refresh="true" data-mobile-responsive="true" data-sort-name="id" data-sort-order="DESC" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="sales_report_query_params" data-export-types='["txt","excel"]'>
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable='true'> <?= labels('id', 'Order item ID') ?></th>
                                <th data-field="product_name" data-sortable='true'><?= labels('product_name', 'Product name') ?></th>
                                <th data-field="final_total" data-sortable='true'><?= labels('final_total', 'Final Total') ?></th>
                                <th data-field="payment_method" data-sortable='true'><?= labels('payment_method', 'Payment Method') ?></th>
                                <th data-field="store_name" data-sortable='true'><?= labels('store_name', 'Store Name') ?></th>
                                <th data-field="seller_name" data-sortable='true'><?= labels('seller_name', 'Sales Representative') ?></th>
                                <th data-field="date_added" data-sortable='true'><?= labels('date_added', 'Order Date') ?></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .sales-report-page .text-primary-theme { color: var(--color-orange); }

    .sales-report-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .sales-report-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .sales-report-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .sales-report-page .header-icon.bg-set { background: var(--color-orange); }

    .sales-report-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .sales-report-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .sales-report-page .filter-label i { color: var(--color-orange); }
    .sales-report-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .sales-report-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .sales-report-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .sales-report-page .fixed-table-toolbar { margin-bottom: 10px; }
    .sales-report-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .sales-report-page .fixed-table-toolbar .btn-group > .btn,
    .sales-report-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .sales-report-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .sales-report-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .sales-report-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .sales-report-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .sales-report-page table.table thead th {
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
    .sales-report-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .sales-report-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .sales-report-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .sales-report-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>

<script>
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
        $('.sales-report-page .table-striped').bootstrapTable('refresh');
    }

    function sales_report_query_params(p) {
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

    function salesReport(index, row) {
        var html = [];
        var indexs = 0;
        $.each(row, function (key, value) {
            var columns = $('.sales-report-page th:eq(' + (indexs + 1) + ')').data('field');
            if (columns != undefined) {
                html.push('<p><b>' + columns + ' :</b> ' + row[columns] + '</p>');
                indexs++;
            }
        });
        return html;
    }
</script>
