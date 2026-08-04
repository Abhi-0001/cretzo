<div class="content-wrapper admin-manage-countries-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-globe-americas mr-2 text-primary-theme"></i>Country List</h4>
                    <p class="text-muted mb-0 small">Reference list of countries with their currency and dialing details.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Countries </li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-globe-americas"></i></span>
                                <h5 class="mb-0">Countries</h5>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <table class='table-striped' id='countries_table' data-toggle="table" data-url="<?= base_url('admin/area/country_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{
                        "fileName": "countries-list",
                        "ignoreColumn": ["state"]
                        }' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="numeric_code" data-sortable="true">Numeric Code</th>
                                        <th data-field="name" data-sortable="true">Name</th>
                                        <th data-field="capital" data-sortable="true" data-visible="false">Capital</th>
                                        <th data-field="phonecode" data-sortable="true">Phonecode</th>
                                        <th data-field="currency" data-sortable="true">Currency</th>
                                        <th data-field="currency_name" data-sortable="true" data-visible="false">Currency Name</th>
                                        <th data-field="currency_symbol" data-sortable="true" data-visible="false">Currency Symbol</th>
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
    .admin-manage-countries-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-countries-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-countries-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-countries-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-countries-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-countries-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-countries-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-countries-page .fixed-table-toolbar .btn-group > .btn,
    .admin-manage-countries-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-manage-countries-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-manage-countries-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-manage-countries-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-countries-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-countries-page .fixed-table-toolbar .columns .btn,
    .admin-manage-countries-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-manage-countries-page .fixed-table-toolbar .columns .btn:hover,
    .admin-manage-countries-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-manage-countries-page .fixed-table-container { border: none; }
    .admin-manage-countries-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-manage-countries-page table.table thead th {
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
    .admin-manage-countries-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-countries-page table.table tbody tr:hover { background-color: var(--color-orange-light); }

    .admin-manage-countries-page .fixed-table-pagination { margin-top: 12px; }
    .admin-manage-countries-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-manage-countries-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-manage-countries-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>
