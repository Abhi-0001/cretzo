<div class="content-wrapper admin-payment-request-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-money-check-alt mr-2 text-primary-theme"></i>Payment Request</h4>
                    <p class="text-muted mb-0 small">Approve or reject seller and delivery-boy withdrawal requests.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Payment Request</li>
                    </ol>
                </div>
            </div>
            <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="payment_request_modal">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-money-check-alt mr-2 text-primary-theme"></i>Update Payment Request</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form class="form-horizontal form-submit-event" action="<?= base_url('admin/payment-request/update-payment-request'); ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="payment_request_id" id="payment_request_id">
                                <!-- Read-only context, filled from the clicked row, so the payout
                                     decision is made against the actual amount and destination
                                     rather than from memory of the table behind the modal. -->
                                <dl class="row small text-muted mb-3 pr-summary">
                                    <dt class="col-sm-3">Seller</dt>
                                    <dd class="col-sm-9" id="pr_summary_user">&mdash;</dd>
                                    <dt class="col-sm-3">Amount</dt>
                                    <dd class="col-sm-9" id="pr_summary_amount">&mdash;</dd>
                                    <dt class="col-sm-3">Pay To</dt>
                                    <dd class="col-sm-9" id="pr_summary_address">&mdash;</dd>
                                </dl>
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
                                <div class="form-group" id="payment_reference_group" style="display:none;">
                                    <label for="payment_reference">Payout Reference (UTR / UPI ref / cheque no.) <span class='text-danger text-sm'>*</span></label>
                                    <input type="text" id="payment_reference" name="payment_reference" class="form-control col-12" maxlength="128" placeholder="Reference of the payout you have made to the seller">
                                    <small class="form-text text-muted">Approving marks the amount as paid out. Record the reference so the seller can trace it.</small>
                                </div>
                                <div class="form-group">
                                    <label class="" for="">Remark</label>
                                    <textarea id="update_remarks" name="update_remarks" class="form-control col-12 "></textarea>
                                </div>
                                <div class="alert alert-warning py-2 small mb-3">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Rejecting returns the amount to the seller's wallet. Approving does not &mdash; make the transfer yourself first. Either way the decision is final.
                                </div>
                                <div class="ln_solid"></div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-primary-theme" id="submit_btn">Update</button>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set"><i class="fas fa-money-check-alt"></i></span>
                                <h5 class="mb-0">Payment Requests</h5>
                            </div>
                            <div class="d-flex align-items-center">
                                <label for="user_filter" class="mb-0 mr-2 small text-muted">Filter By User</label>
                                <select class='form-control form-control-sm' name='user_filter' id="user_filter" style="width:auto;">
                                    <option value=''>All</option>
                                    <option value='customer'>Customer</option>
                                    <option value='seller'>Seller</option>
                                    <option value='delivery_boy'>Delivery Boy</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' id='payment_request_table' data-toggle="table" data-url="<?= base_url('admin/payment-request/view-payment-request-list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="payment_request_queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="user_name" data-sortable="true">Username</th>
                                        <th data-field="payment_type" data-sortable="true">Type</th>
                                        <th data-field="payment_address" data-sortable="false">Payment Address</th>
                                        <th data-field="amount_requested" data-sortable="true">Amount Requested</th>
                                        <th data-field="remarks" data-sortable="false">Remarks</th>
                                        <th data-field="status" data-sortable="false">Status</th>
                                        <th data-field="payment_reference" data-sortable="false">Payout Ref.</th>
                                        <th data-field="processed_at" data-sortable="false">Processed On</th>
                                        <th data-field="date_created" data-sortable="true">Date Created</th>
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
    .admin-payment-request-page .text-primary-theme { color: var(--color-orange); }

    .admin-payment-request-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-payment-request-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-payment-request-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-payment-request-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-payment-request-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-payment-request-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-payment-request-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-payment-request-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-payment-request-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-payment-request-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-payment-request-page table.table thead th {
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
    .admin-payment-request-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-payment-request-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-payment-request-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-payment-request-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-payment-request-page td:has(.action-btn) { white-space: nowrap; }
    .admin-payment-request-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
