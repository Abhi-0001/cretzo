<div class="content-wrapper admin-themes-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-palette mr-2 text-primary-theme"></i>Themes</h4>
                    <p class="text-muted mb-0 small">Storefront themes available for your marketplace.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Themes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content address-section">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-palette"></i></span>
                                <h5 class="mb-0">Available Themes</h5>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <table class='table-striped' id='customer-address-table' data-toggle="table" data-url="<?= base_url('admin/setting/get-themes') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="address_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">Id</th>
                                        <th data-field="name" data-sortable="false">Name</th>
                                        <th data-field="image" data-sortable="false">Image</th>
                                        <th data-field="is_default" data-sortable="false">Default</th>
                                        <th data-field="status" data-sortable="false">Status</th>
                                        <th data-field="created_on" data-sortable="false" data-visible="false">Created On</th>
                                        <th data-field="operate" data-sortable="true">Action</th>
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
    .admin-themes-page .text-primary-theme { color: var(--color-orange); }

    .admin-themes-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-themes-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-themes-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-themes-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-themes-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-themes-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-themes-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-themes-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }
</style>