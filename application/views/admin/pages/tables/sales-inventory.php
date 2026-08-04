<div class="content-wrapper admin-sales-inventory-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-boxes mr-2 text-primary-theme"></i>Sales Inventory</h4>
                    <p class="text-muted mb-0 small">Stock levels for every product/variant sold, filterable by seller.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Sales Inventory Reports</li>
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
                        <span class="header-icon bg-set mr-2"><i class="fas fa-boxes"></i></span>
                        <h5 class="mb-0">Sales Inventory</h5>
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
                                            <select class='form-control' name='seller_ids' id="seller_ids">
                                                <option value="">Select Seller </option>
                                                <?php foreach ($sellers as $seller) { ?>
                                                    <option value="<?= $seller['seller_id'] ?>"><?= $seller['seller_name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 d-flex align-items-center pt-4">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="status_date_wise_search()">Filter</button>
                                    </div>
                                </div>
                            </div>
                        <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/Sales_inventory/get_sales_inventory_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="sales_inventory_report_query_params">
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

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-sales-inventory-page .text-primary-theme { color: var(--color-orange); }

    .admin-sales-inventory-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-sales-inventory-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-sales-inventory-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-sales-inventory-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-sales-inventory-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-sales-inventory-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-sales-inventory-page select.form-control {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.12);
        box-shadow: none;
    }
    .admin-sales-inventory-page select.form-control:focus {
        border-color: var(--color-orange);
        box-shadow: none;
        outline: none;
    }

    .admin-sales-inventory-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-sales-inventory-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-sales-inventory-page .fixed-table-toolbar .btn-group > .btn,
    .admin-sales-inventory-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-sales-inventory-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-sales-inventory-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-sales-inventory-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-sales-inventory-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-sales-inventory-page .fixed-table-toolbar .columns .btn,
    .admin-sales-inventory-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-sales-inventory-page .fixed-table-toolbar .columns .btn:hover,
    .admin-sales-inventory-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-sales-inventory-page .fixed-table-container { border: none; }
    .admin-sales-inventory-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-sales-inventory-page table.table thead th {
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
    .admin-sales-inventory-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-sales-inventory-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-sales-inventory-page .action-btn { border-radius: 6px; }
    .admin-sales-inventory-page .badge { font-size: 12px; padding: 5px 10px; border-radius: 20px; font-weight: 600; }

    .admin-sales-inventory-page .fixed-table-pagination { margin-top: 12px; }
    .admin-sales-inventory-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-sales-inventory-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-sales-inventory-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>