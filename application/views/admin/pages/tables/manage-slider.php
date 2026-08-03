<div class="content-wrapper admin-manage-slider-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-images mr-2 text-primary-theme"></i>Manage Sliders</h4>
                    <p class="text-muted mb-0 small">Homepage banners shown to every storefront visitor.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Slider</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-images"></i></span>
                        <h5 class="mb-0">Sliders</h5>
                    </div>
                    <a href="<?= base_url('admin/slider') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Slider
                    </a>
                </div>
                <div class="card-body">
                    <table class='table-striped' data-toggle="table"
                        data-url="<?= base_url('admin/slider/view_slider') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true"
                        data-export-options='{"fileName": "slider-list", "ignoreColumn": ["operate"]}'
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="type" data-sortable="true" data-align='center'>Type</th>
                                <th data-field="type_id" data-sortable="true" data-align='center'>Type ID</th>
                                <th data-field="image" data-sortable="true" class="col-md-6" data-align='center'>Image</th>
                                <th data-field="link" data-sortable="true" data-align='center'>Link</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-manage-slider-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-slider-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-slider-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-slider-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-slider-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-slider-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-slider-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-slider-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-slider-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-slider-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-slider-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-slider-page table.table thead th {
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
    .admin-manage-slider-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-slider-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-slider-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-slider-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-slider-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-slider-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
