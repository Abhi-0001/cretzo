<div class="content-wrapper admin-sales-report-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-chart-line mr-2 text-primary-theme"></i>Sales Report</h4>
                    <p class="text-muted mb-0 small">Order-level sales figures across the marketplace, filterable by seller and date range.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Sales Reports</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-chart-line"></i></span>
                        <h5 class="mb-0">Sales Report</h5>
                    </div>
                </div>
                <div class="card-body pt-3">
                            <div class="gaps-1-5x row d-flex adjust-items-center">
                                <div class="row col-md-12">
                                    <div class="form-group col-md-4">
                                        <label>From & To Date</label>
                                        <div class="input-group col-md-12">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                            <input type="text" class="form-control float-right" id="datepicker">
                                            <input type="hidden" id="start_date" class="form-control float-right">
                                            <input type="hidden" id="end_date" class="form-control float-right">
                                        </div>
                                        <!-- /.input group -->
                                    </div>
                                    <div class="form-group col-md-4">
                                        <div>
                                            <label>Seller Name</label>
                                            <select class='form-control' name='seller_id' id="seller_id">
                                                <option value="">Select Seller </option>
                                                <?php foreach ($sellers as $seller) { ?>
                                                    <option value="<?= $seller['seller_id'] ?>" <?= (isset($product_details[0]['seller_id']) && $product_details[0]['seller_id'] == $seller['seller_id']) ? 'selected' : "" ?>><?= $seller['seller_name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 d-flex align-items-center pt-4">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="status_date_wise_search()">Filter</button>
                                    </div>
                                </div>
                            </div>
                        <table class="table table-striped" data-detail-view="true" data-detail-formatter="salesReport" data-auto-refresh="true" data-toggle="table" data-url="<?= base_url('admin/Sales_report/get_sales_report_list') ?>" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 25, 50, 100, 200, All]" data-search="true" data-show-columns="true" data-show-columns-search="true" data-show-refresh="true" data-sort-name="id" data-sort-order="DESC" data-query-params="sales_report_query_params">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable='true'><?= labels('id', 'Order item ID') ?></th>
                                    <th data-field="user_id" data-sortable='true' ><?= labels('user_id', 'User ID') ?></th>
                                    <th data-field="name" data-sortable='true'><?= labels('name', 'User Name') ?></th>
                                    <th data-field="product_name" data-sortable='true'><?= labels('product_name', 'Product name') ?></th>
                                    <th data-field="mobile" data-visiable="false" data-sortable='true'><?= labels('mobile', ' Mobile') ?></th>
                                    <th data-field="address" data-sortable='true'><?= labels('address', 'Address') ?></th>
                                    <th data-field="final_total" data-sortable='true'><?= labels('final_total', 'Final Total') ?></th>
                                    <th data-field="date_added" data-sortable='true'><?= labels('date_added', 'Order Date') ?></th>
                                </tr>
                            </thead>
                        </table>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-sales-report-page .text-primary-theme { color: var(--color-orange); }

    .admin-sales-report-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-sales-report-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-sales-report-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-sales-report-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-sales-report-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-sales-report-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-sales-report-page select.form-control {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.12);
        box-shadow: none;
    }
    .admin-sales-report-page select.form-control:focus {
        border-color: var(--color-orange);
        box-shadow: none;
        outline: none;
    }

    .admin-sales-report-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-sales-report-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .btn,
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-sales-report-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-sales-report-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-sales-report-page .fixed-table-toolbar .columns .btn,
    .admin-sales-report-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-sales-report-page .fixed-table-toolbar .columns .btn:hover,
    .admin-sales-report-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-sales-report-page .fixed-table-container { border: none; }
    .admin-sales-report-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-sales-report-page table.table thead th {
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
    .admin-sales-report-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-sales-report-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-sales-report-page .action-btn { border-radius: 6px; }
    .admin-sales-report-page .badge { font-size: 12px; padding: 5px 10px; border-radius: 20px; font-weight: 600; }

    .admin-sales-report-page .fixed-table-pagination { margin-top: 12px; }
    .admin-sales-report-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-sales-report-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-sales-report-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>