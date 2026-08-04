<div class="content-wrapper admin-time-slots-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-clock mr-2 text-primary-theme"></i>Time Slots</h4>
                    <p class="text-muted mb-0 small">Configure delivery time slots and windows for orders.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Time Slots</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <?php if (!isset($fetched_data[0]['id'])) { ?>
                    <div class="col-md-12">
                        <div class="card attribute-card">
                            <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center">
                                    <span class="header-icon bg-set mr-2"><i class="fas fa-sliders-h"></i></span>
                                    <h5 class="mb-0">Time Slots Configuration</h5>
                                </div>
                            </div>
                            <!-- form start -->
                            <form class="form-horizontal form-submit-event" action="<?= base_url('admin/Time_slots/update_time_slots_config'); ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" id="time_slot_config" name="time_slot_config" required="" value="1" aria-required="true">
                                <div class="card-body pt-3">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="">Enable / Disable Time Slots</label>
                                        </div>
                                        <div class="form-group col-md-8">
                                            <input type="checkbox" name="is_time_slots_enabled" <?= (@$time_slot_config['is_time_slots_enabled']) == '1' ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="">Delivery Starts From ?</label>
                                        </div>
                                        <div class="form-group col-md-8">
                                            <select class="form-control" name="delivery_starts_from">
                                                <option value="">Select</option>
                                                <option value="1" <?= (isset($time_slot_config['delivery_starts_from']) && $time_slot_config['delivery_starts_from'] == '1') ? 'selected' : '' ?>>Today</option>
                                                <option value="2" <?= (isset($time_slot_config['delivery_starts_from']) && $time_slot_config['delivery_starts_from'] == '2') ? 'selected' : '' ?>>Tomorrow</option>
                                                <option value="3" <?= (isset($time_slot_config['delivery_starts_from']) && $time_slot_config['delivery_starts_from'] == '3') ? 'selected' : '' ?>>Third Day</option>
                                                <option value="4" <?= (isset($time_slot_config['delivery_starts_from']) && $time_slot_config['delivery_starts_from'] == '4') ? 'selected' : '' ?>>Fourth Day</option>
                                                <option value="5" <?= (isset($time_slot_config['delivery_starts_from']) && $time_slot_config['delivery_starts_from'] == '5') ? 'selected' : '' ?>>Fifth Day</option>
                                                <option value="6" <?= (isset($time_slot_config['delivery_starts_from']) && $time_slot_config['delivery_starts_from'] == '6') ? 'selected' : '' ?>>Sixth Day</option>
                                                <option value="7" <?= (isset($time_slot_config['delivery_starts_from']) && $time_slot_config['delivery_starts_from'] == '7') ? 'selected' : '' ?>>Seventh Day</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="">How many days you want to allow ?</label>
                                        </div>
                                        <div class="form-group col-md-8">
                                            <select class="form-control" name="allowed_days">
                                                <option value="">Select</option>
                                                <option value="1" <?= (isset($time_slot_config['allowed_days']) && $time_slot_config['allowed_days'] == '1') ? 'selected' : '' ?>>1</option>
                                                <option value="7" <?= (isset($time_slot_config['allowed_days']) && $time_slot_config['allowed_days'] == '7') ? 'selected' : '' ?>>7</option>
                                                <option value="15" <?= (isset($time_slot_config['allowed_days']) && $time_slot_config['allowed_days'] == '15') ? 'selected' : '' ?>>15</option>
                                                <option value="30" <?= (isset($time_slot_config['allowed_days']) && $time_slot_config['allowed_days'] == '30') ? 'selected' : '' ?>>30</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-success" id="submit_btn">Save</button>
                                        </div>
                                    </div><!-- /.box-body -->
                                    <div class="d-flex justify-content-center form-group">
                                        <div id="error_box"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!--/.card-->
                    </div>
                <?php } ?>
                <!--/.col-md-12-->
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-clock"></i></span>
                                <h5 class="mb-0"><?= (isset($fetched_data[0]['id'])) ? 'Edit Time Slot' : 'Add Time Slot' ?></h5>
                            </div>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/Time_slots/update_time_slots'); ?>" method="POST" enctype="multipart/form-data">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" id="edit_time_slot" name="edit_time_slot" value="<?= @$fetched_data[0]['id'] ?>">
                            <?php } else { ?>
                                <input type="hidden" id="add_time_slot" name="add_time_slot" required="" value="1" aria-required="true">
                            <?php } ?>
                            <div class="card-body pt-3">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="">Title</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" name="title" value="<?= (isset($fetched_data[0]['title']) ? $fetched_data[0]['title'] : '') ?>" placeholder="Morning 9AM to 12PM">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="">From Time</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="time" class="form-control" name="from_time" value="<?= (isset($fetched_data[0]['from_time']) ? $fetched_data[0]['from_time'] : '') ?>" placeholder="09:00:00">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="">To Time</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="time" class="form-control" name="to_time" value="<?= (isset($fetched_data[0]['to_time']) ? $fetched_data[0]['to_time'] : '') ?>" placeholder="12:00:00">
                                    </div>
                                </div>
                                <div class="row">
                                <div class="form-group col-md-4">
                                        <label for="">Last Order Time</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="time" class="form-control" name="last_order_time" value="<?= (isset($fetched_data[0]['last_order_time']) ? $fetched_data[0]['last_order_time'] : '') ?>" placeholder="11:00:00">
                                    </div>
                                </div>
                                <div class="row">
                                <div class="form-group col-md-4">
                                        <label for="">Status</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <select name="status" class="form-control">
                                            <option value="">Select</option>
                                            <option value="1" <?= (isset($fetched_data[0]['status']) && $fetched_data[0]['status'] == 1) ? 'Selected' : '' ?>>Active</option>
                                            <option value="0" <?= (isset($fetched_data[0]['status']) && $fetched_data[0]['status'] == 0) ? 'Selected' : '' ?>>Deactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Time Slots' : 'Add Time Slots' ?></button>
                                </div>

                                <div class="d-flex justify-content-center ">
                                    <div id="error_box">
                                    </div>
                                </div>
                            </div><!-- /.box-body -->

                        </form>
                    </div>
                    <!--/.card-->
                    <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Fetured Section Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/.col-md-12-->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-list"></i></span>
                                <h5 class="mb-0">Time Slots List</h5>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/Time_slots/view_time_slots') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="title" data-sortable="false">Title</th>
                                        <th data-field="from_time" data-sortable="true">From Time</th>
                                        <th data-field="to_time" data-sortable="true">To Time</th>
                                        <th data-field="last_order_time" data-sortable="true">Last Order Time</th>
                                        <th data-field="status" data-sortable="true">Status</th>
                                        <th data-field="operate" data-sortable="true">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div><!-- .card-body -->
                    </div><!-- .card -->
                </div>
            </div> <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-time-slots-page .text-primary-theme { color: var(--color-orange); }

    .admin-time-slots-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-time-slots-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-time-slots-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-time-slots-page .btn-success:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }

    .admin-time-slots-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }
    .admin-time-slots-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-time-slots-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-time-slots-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-time-slots-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-time-slots-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-time-slots-page .fixed-table-toolbar .btn-group > .btn,
    .admin-time-slots-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-time-slots-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-time-slots-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-time-slots-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-time-slots-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-time-slots-page .fixed-table-toolbar .columns .btn,
    .admin-time-slots-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-time-slots-page .fixed-table-toolbar .columns .btn:hover,
    .admin-time-slots-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-time-slots-page .fixed-table-container { border: none; }
    .admin-time-slots-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-time-slots-page table.table thead th {
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
    .admin-time-slots-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-time-slots-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-time-slots-page .action-btn { border-radius: 6px; }
    .admin-time-slots-page .badge { font-size: 12px; padding: 5px 10px; border-radius: 20px; font-weight: 600; }

    .admin-time-slots-page .fixed-table-pagination { margin-top: 12px; }
    .admin-time-slots-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-time-slots-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-time-slots-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>