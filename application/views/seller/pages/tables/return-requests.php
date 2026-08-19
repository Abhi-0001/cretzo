<div class="content-wrapper return-requests-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-undo mr-2 text-primary-theme"></i>Return Requests</h4>
                    <p class="text-muted mb-0 small">Returns raised by customers against your products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Return Requests</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card stat-card-accent">
                        <span class="stat-label">Awaiting Decision</span>
                        <span class="stat-value"><?= (int) $summary['pending'] ?></span>
                        <span class="stat-sub">With the admin</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <span class="stat-label">Approved</span>
                        <span class="stat-value"><?= (int) $summary['approved'] ?></span>
                        <span class="stat-sub">Customer refunded</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <span class="stat-label">Rejected</span>
                        <span class="stat-value"><?= (int) $summary['rejected'] ?></span>
                        <span class="stat-sub">Item stays sold</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <span class="stat-label">Total Requests</span>
                        <span class="stat-value"><?= (int) $summary['total'] ?></span>
                        <span class="stat-sub">All time</span>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                This page is for information only. A customer raises the return, an admin approves
                or rejects it, and the courier's reverse pickup marks the item returned when the
                parcel is back with you. When a return is approved the customer is refunded, the
                stock goes back to your inventory and the commission on that sale is reversed.
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-undo"></i></span>
                        <h5 class="mb-0">Your Return Requests</h5>
                    </div>
                    <div>
                        <select id="return_status_filter" class="form-control form-control-sm">
                            <option value="">All statuses</option>
                            <option value="0">Awaiting decision</option>
                            <option value="1">Approved</option>
                            <option value="2">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='return_requests_table' data-toggle="table"
                        data-url="<?= base_url('seller/return-request/view-return-request-list') ?>"
                        data-side-pagination="server" data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                        data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                        data-query-params="returnRequestQueryParams" data-show-export="true">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="order_id" data-sortable="true">Order ID</th>
                                <th data-field="order_item_id" data-sortable="false" data-visible="false">Item ID</th>
                                <th data-field="product_name" data-sortable="true">Product</th>
                                <th data-field="customer" data-sortable="false">Customer</th>
                                <th data-field="quantity" data-sortable="true">Qty</th>
                                <th data-field="sub_total" data-sortable="true">Item Total</th>
                                <th data-field="status" data-sortable="true">Request Status</th>
                                <th data-field="item_status" data-sortable="false">Item Status</th>
                                <th data-field="refund" data-sortable="false">Refunded</th>
                                <th data-field="remarks" data-sortable="false" data-visible="false">Admin Remarks</th>
                                <th data-field="date_created" data-sortable="true">Requested On</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    // Carries the status filter through to the server on every page/sort/search request -
    // bootstrap-table only sends its own built-in params otherwise.
    function returnRequestQueryParams(params) {
        params.status = $('#return_status_filter').val();
        return params;
    }
    $(document).on('change', '#return_status_filter', function() {
        $('#return_requests_table').bootstrapTable('refresh');
    });
</script>

<style>
    .return-requests-page .text-primary-theme { color: var(--color-orange); }

    .return-requests-page .stat-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
        padding: 16px 18px;
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        border-left: 4px solid rgba(0, 0, 0, 0.08);
    }
    .return-requests-page .stat-card-accent { border-left-color: var(--color-orange); }
    .return-requests-page .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--color-grey);
        font-weight: 600;
    }
    .return-requests-page .stat-value { font-size: 22px; font-weight: 700; margin-top: 4px; }
    .return-requests-page .stat-sub { font-size: 12px; color: #8a8a8a; margin-top: 2px; }

    .return-requests-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
    }
    .return-requests-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 10px 10px 0 0;
    }
    .return-requests-page .header-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        margin-right: 10px;
        color: #fff;
    }
    .return-requests-page .header-icon.bg-set { background: var(--color-orange); }
    .return-requests-page #return_status_filter { min-width: 180px; }
</style>
