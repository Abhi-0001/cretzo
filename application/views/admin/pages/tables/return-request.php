<div class="content-wrapper admin-return-request-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-undo-alt mr-2 text-primary-theme"></i>Return Request</h4>
                    <p class="text-muted mb-0 small">Review and action customer return requests.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Return Request</li>
                    </ol>
                </div>

            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="request_rating_modal">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-undo-alt mr-2 text-primary-theme"></i>Update Return Request</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form class="form-horizontal form-submit-event" action="<?= base_url('admin/return_request/update-return-request'); ?>" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="return_request_id" id="return_request_id">
                                    <input type="hidden" name="user_id" id="user_id">
                                    <input type="hidden" name="order_item_id" id="order_item_id">

                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Status <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-md-7 col-sm-6 col-xs-12">
                                            <div id="status" class="btn-group">
                                                <label class="btn btn-warning" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                    <input type="radio" name="status" value="0" class='pending'> Pending
                                                </label>
                                                <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                    <input type="radio" name="status" value="1" class='approved'> Approved
                                                </label>
                                                <label class="btn btn-danger" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                    <input type="radio" name="status" value="2" class='rejected'> Rejected
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 d-none" id="return_request_delivery_by">

                                        <select id='deliver_by' name='deliver_by' class='form-control'>
                                            <option value=''>Select Delivery Boy</option>
                                            <?php foreach ($delivery_res as $row) { ?>
                                                <option value="<?= $row['user_id'] ?>"><?= $row['username'] ?></option>
                                            <?php  } ?>
                                        </select>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label class="" for="">Remark</label>
                                        <textarea id="update_remarks" name="update_remarks" class="form-control col-12 "></textarea>
                                    </div>
                                    <input type="hidden" id="id" name="id">
                                    <div class="ln_solid"></div>
                                    <div class="form-group">
                                        <button type="reset" class="btn btn-warning">Reset</button>
                                        <button type="submit" class="btn btn-primary-theme" id="submit_btn">Update</button>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <div class="form-group" id="error_box">
                                        </div>
                                    </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 main-content">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-undo-alt"></i></span>
                        <h5 class="mb-0">Return Requests</h5>
                    </div>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='return_request_table' data-toggle="table" data-url="<?= base_url('admin/return_request/view_return_request_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="order_id" data-sortable="true">Order ID</th>
                                <th data-field="order_item_id" data-sortable="true">Order Item ID</th>
                                <th data-field="user_name" data-sortable="false">Username</th>
                                <th data-field="product_name" data-sortable="false">Product Name</th>
                                <th data-field="price" data-sortable="false">Price</th>
                                <th data-field="discounted_price" data-sortable="false" data-visible="false">Discounted Price</th>
                                <th data-field="quantity" data-sortable="false">Quantity</th>
                                <th data-field="sub_total" data-sortable="false">Sub Total</th>
                                <th data-field="seller_name" data-sortable="false">Seller</th>
                                <th data-field="status" data-sortable="false">Status</th>
                                <th data-field="item_status" data-sortable="false" data-visible="false">Item Status</th>
                                <th data-field="refund" data-sortable="false">Refunded</th>
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
    .admin-return-request-page .text-primary-theme { color: var(--color-orange); }

    .admin-return-request-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-return-request-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-return-request-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-return-request-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-return-request-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-return-request-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-return-request-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-return-request-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-return-request-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-return-request-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-return-request-page table.table thead th {
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
    .admin-return-request-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-return-request-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-return-request-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-return-request-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-return-request-page td:has(.action-btn) { white-space: nowrap; }
    .admin-return-request-page .action-btn { display: inline-block; vertical-align: middle; }
    .admin-return-request-page #status .btn { border-radius: 20px; margin-right: 4px; }
</style>
