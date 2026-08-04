<div class="content-wrapper admin-manage-area-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0"><i class="fas fa-draw-polygon mr-2 text-primary-theme"></i>Manage Area</h4>
                    <p class="text-muted mb-0 small">Delivery areas, each tied to a city and zipcode with its own free-delivery threshold and delivery charge.</p>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Area</li>
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
                                <h5 class="mb-0"><?= (isset($fetched_data[0]['id'])) ? 'Edit Area' : 'Add Area' ?></h5>
                            </div>
                            <button type="button" class="btn btn-primary-theme btn-sm" data-toggle="modal" data-target="#bulk_update"><i class="fas fa-layer-group mr-1"></i>Bulk Update</button>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/area/add_area'); ?>" method="POST" id="add_area_form" enctype="multipart/form-data">
                            <?php
                            if (isset($fetched_data[0]['id'])) {
                            ?>
                                <input type="hidden" id="edit_area" name="edit_area" value="<?= @$fetched_data[0]['id'] ?>">
                                <input type="hidden" id="update_id" name="update_id" value="1">
                            <?php
                            }
                            ?>
                            <div class="card-body pt-3">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="area_name" class="control-label font-weight-bold mb-1">Area Name <span class='text-danger text-xs'>*</span></label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" name="area_name" id="area_name" value="<?= (isset($fetched_data[0]['name']) ? html_escape($fetched_data[0]['name']) : '') ?>">
                                    </div>
                                </div>
                                <div class="row city_list_select">
                                    <div class="form-group col-md-4">
                                        <label for="city_list" class="control-label font-weight-bold mb-1">City <span class='text-danger text-xs'>*</span></label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <select class="form-control" name="city" id="city_list">
                                            <option value=" ">Select City</option>
                                            <!-- cities' actual PK/name columns are city_id/city_name, not id/name -
                                                 this dropdown previously rendered every option with an undefined-array-key
                                                 warning, an empty value, and no visible city name. -->
                                            <?php foreach ($city as $row) { ?>
                                                <option value="<?= $row['city_id'] ?>" <?= (isset($fetched_data[0]['city_id']) && $row['city_id'] == $fetched_data[0]['city_id']) ? 'selected' : ' ' ?>><?= html_escape($row['city_name']) ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row zipcode_list_select">
                                    <div class="form-group col-md-4">
                                        <label for="zipcode_list" class="control-label font-weight-bold mb-1">Zipcode <span class='text-danger text-xs'>*</span></label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <select class="form-control" name="zipcode" id="zipcode_list">
                                            <option value=" ">Select Pincode</option>
                                            <?php foreach ($zipcodes as $zipcode) { ?>
                                                <option value="<?= $zipcode['id'] ?>" <?= (isset($fetched_data[0]['zipcode_id']) && $zipcode['id'] == $fetched_data[0]['zipcode_id']) ? 'selected' : ' ' ?>><?= html_escape($zipcode['zipcode']) ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="minimum_free_delivery_order_amount" class="control-label font-weight-bold mb-1">Minimum Free Delivery Order Amount <span class='text-danger text-xs'>*</span></label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="number" class="form-control" name="minimum_free_delivery_order_amount" id="minimum_free_delivery_order_amount" min="0" value="<?= (isset($fetched_data[0]['minimum_free_delivery_order_amount']) ? $fetched_data[0]['minimum_free_delivery_order_amount'] : '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="delivery_charges" class="control-label font-weight-bold mb-1">Delivery Charges <span class='text-danger text-xs'>*</span></label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="number" class="form-control" name="delivery_charges" id="delivery_charges" min="0" value="<?= (isset($fetched_data[0]['delivery_charges']) ? $fetched_data[0]['delivery_charges'] : '') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Area' : 'Add Area' ?></button>
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
                                <h5 class="modal-title" id="exampleModalLongTitle">Edit Area</h5>
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
                                <h5 class="mb-0">Area Details</h5>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/area/view_area') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="name" data-sortable="true">Name</th>
                                        <th data-field="city_name" data-sortable="true">City Name</th>
                                        <th data-field="zipcode" data-sortable="true">Zipcode</th>
                                        <th data-field="minimum_free_delivery_order_amount" data-sortable="true">Minimum Free Delivery Order Amount</th>
                                        <th data-field="delivery_charges" data-sortable="true">Delivery Charges</th>
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
<div class="modal fade" id="bulk_update">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Bulk Update</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Modal body -->
            <div class="modal-body">
                <form method="post" action='<?= base_url('admin/area/bulk_update') ?>' id="bulk_area_update_form">
                    <div class="form-group">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                    </div>
                    <div class="col-md-12">
                        <select class="form-control mb-2" name="city">
                            <option value=" ">-------------------Select City------------------</option>
                            <!-- cities' actual PK/name columns are city_id/city_name, not id/name - this
                                 dropdown previously rendered every option with an undefined-array-key
                                 warning, an empty value, and no visible city name, making the Bulk
                                 Update feature's own city picker unusable. -->
                            <?php foreach ($city as $row) { ?>
                                <option value="<?= $row['city_id'] ?>"><?= html_escape($row['city_name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group row">
                        <label for="bulk_update_minimum_free_delivery_order_amount" class="control-label col-md-12">Minimum Free Delivery Order Amount <span class='text-danger text-xs'>*</span></label>
                        <div class="col-md-12">
                            <input type="number" class="form-control" name="bulk_update_minimum_free_delivery_order_amount" id="bulk_update_minimum_free_delivery_order_amount" min="0">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="bulk_update_delivery_charges" class="control-label col-md-12">Delivery Charges <span class='text-danger text-xs'>*</span></label>
                        <div class="col-md-12">
                            <input type="number" class="form-control" name="bulk_update_delivery_charges" id="bulk_update_delivery_charges" min="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="save-areas-result-btn" name="bulk_update" value="Save">Update</button>
                    <div class="mt-3">
                        <div id="save-areas-result"></div>
                    </div>
                </form>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-manage-area-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-area-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-area-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-area-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-manage-area-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }

    .admin-manage-area-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-area-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-area-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-area-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-area-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 0.2rem rgba(230,126,34,0.15); }

    .admin-manage-area-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-area-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-area-page .fixed-table-toolbar .btn-group > .btn,
    .admin-manage-area-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-manage-area-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-manage-area-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-manage-area-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-area-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-area-page .fixed-table-toolbar .columns .btn,
    .admin-manage-area-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-manage-area-page .fixed-table-toolbar .columns .btn:hover,
    .admin-manage-area-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-manage-area-page .fixed-table-container { border: none; }
    .admin-manage-area-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-manage-area-page table.table thead th {
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
    .admin-manage-area-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-area-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-area-page .action-btn { border-radius: 6px; }

    .admin-manage-area-page .fixed-table-pagination { margin-top: 12px; }
    .admin-manage-area-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-manage-area-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-manage-area-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>
