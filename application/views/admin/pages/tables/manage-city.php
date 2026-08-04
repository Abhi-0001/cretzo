<div class="content-wrapper admin-manage-city-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-city mr-2 text-primary-theme"></i>Manage City</h4>
                    <p class="text-muted mb-0 small">Cities available to assign to zipcodes and areas across the marketplace.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">City</li>
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
                                <span class="header-icon bg-set mr-2"><i class="fas fa-plus"></i></span>
                                <h5 class="mb-0"><?= (isset($fetched_data[0]['city_id'])) ? 'Edit City' : 'Add City' ?></h5>
                            </div>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/area/add_city'); ?>" method="POST" id="add_city_form" enctype="multipart/form-data">
                            <?php
                            if (isset($fetched_data[0]['city_id'])) {
                            ?>
                                <!-- cities' real PK column is city_id, not id - this previously fed
                                     the wrong (undefined) key into this hidden field. -->
                                <input type="hidden" id="edit_city" name="edit_city" value="<?= @$fetched_data[0]['city_id'] ?>">
                                <input type="hidden" id="update_id" name="update_id" value="1">
                            <?php
                            }
                            ?>
                            <div class="card-body pt-3">
                                <div class="form-group">
                                    <label for="city_name" class="font-weight-bold mb-1">City Name <span class='text-danger text-sm'>*</span></label>
                                    <input type="text" class="form-control" name="city_name" id="city_name" value="<?= (isset($fetched_data[0]['city_name']) ? html_escape($fetched_data[0]['city_name']) : '') ?>">
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['city_id'])) ? 'Update City' : 'Add City' ?></button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
                                </div>
                            </div>
                        </form>
                    </div>
                    <!--/.card-->
                </div>
                <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLongTitle">Edit City</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 main-content mt-3">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-list"></i></span>
                                <h5 class="mb-0">City Details</h5>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/area/view_city') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="name" data-sortable="true">Name</th>
                                        <th data-field="operate" data-sortable="false">Actions</th>
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
    .admin-manage-city-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-city-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-city-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-city-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-manage-city-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }

    .admin-manage-city-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-city-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-city-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-city-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-city-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 0.2rem rgba(230,126,34,0.15); }

    .admin-manage-city-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-city-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-city-page .fixed-table-toolbar .btn-group > .btn,
    .admin-manage-city-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-manage-city-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-manage-city-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-manage-city-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-city-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-city-page .fixed-table-toolbar .columns .btn,
    .admin-manage-city-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-manage-city-page .fixed-table-toolbar .columns .btn:hover,
    .admin-manage-city-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-manage-city-page .fixed-table-container { border: none; }
    .admin-manage-city-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-manage-city-page table.table thead th {
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
    .admin-manage-city-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-city-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-city-page .action-btn { border-radius: 6px; }

    .admin-manage-city-page .fixed-table-pagination { margin-top: 12px; }
    .admin-manage-city-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-manage-city-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-manage-city-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>
