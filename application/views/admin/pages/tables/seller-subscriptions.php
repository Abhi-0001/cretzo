    <div class="content-wrapper admin-seller-subscriptions-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0"><i class="fas fa-user-tag mr-2 text-primary-theme"></i>Seller Subscriptions</h4>
                    <p class="text-muted mb-0 small">Plan, status, payment, listing usage, expiry and history for every seller.</p>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/subscription/manage_subscriptions') ?>">Subscriptions</a></li>
                        <li class="breadcrumb-item active">Seller Subscriptions</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <?php
            // Free-listing eligibility: the launch promotion is auto-granted to the first
            // LAUNCH_OFFER_SELLER_CAP vendors at sign-up. Admin had no way to see how many
            // slots were left, or that the promotion had lapsed at all.
            $lo_claimed = (int) $launch_offer['claimed'];
            $lo_cap     = (int) $launch_offer['cap'];
            $lo_pct     = $lo_cap > 0 ? min(100, round(($lo_claimed / $lo_cap) * 100)) : 0;
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card mss-summary-card mb-3">
                        <div class="card-body py-3">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <h6 class="mb-1"><i class="fas fa-gift mr-2 text-primary-theme"></i>Free-listing offer (first <?= $lo_cap ?> vendors)</h6>
                                    <p class="text-muted small mb-0">
                                        <?php if ($launch_offer['active']) : ?>
                                            <span class="badge badge-success">Open</span>
                                            <?= (int) $launch_offer['remaining'] ?> of <?= $lo_cap ?> slots still available.
                                        <?php else : ?>
                                            <span class="badge badge-secondary">Closed</span>
                                            All <?= $lo_cap ?> slots have been claimed.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-7">
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 mr-3" style="height: 10px;">
                                            <div class="progress-bar bg-orange" role="progressbar" style="width: <?= $lo_pct ?>%"></div>
                                        </div>
                                        <span class="small text-muted text-nowrap"><?= $lo_claimed ?> / <?= $lo_cap ?> claimed</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-list"></i></span>
                            <h5 class="mb-0">Sellers</h5>
                            <div class="ml-auto d-flex align-items-center">
                                <label class="mb-0 mr-2 small text-muted">Status</label>
                                <select class="form-control form-control-sm" id="mss-status-filter" style="width: auto;">
                                    <option value="">All</option>
                                    <option value="Active">Active</option>
                                    <option value="Expired">Expired</option>
                                    <option value="None">No subscription</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="mss-table" class='table-striped fixed-row-height' data-toggle="table"
                                data-url="<?= base_url('admin/subscription/view_seller_subscriptions') ?>"
                                data-query-params="mssQueryParams"
                                data-side-pagination="client" data-pagination="true"
                                data-page-list="[10, 20, 50, 100, 200]" data-search="true"
                                data-show-columns="true" data-show-refresh="true" data-mobile-responsive="true">
                                <thead>
                                    <tr>
                                        <th data-field="seller_id" data-sortable="true">ID</th>
                                        <th data-field="shop_name" data-sortable="true">Seller</th>
                                        <th data-field="email" data-sortable="true" data-visible="false">Email</th>
                                        <th data-field="mobile" data-sortable="true" data-visible="false">Mobile</th>
                                        <th data-field="plan_name" data-sortable="true">Plan</th>
                                        <th data-field="plan_type" data-sortable="true">Type</th>
                                        <th data-field="price" data-sortable="true">Plan Price</th>
                                        <th data-field="status" data-sortable="true">Status</th>
                                        <th data-field="usage" data-sortable="true">Listings Used</th>
                                        <th data-field="remaining" data-sortable="true" data-visible="false">Listings Left</th>
                                        <th data-field="launch_offer" data-sortable="true" data-visible="false">Free Offer</th>
                                        <th data-field="start_date" data-sortable="true" data-visible="false">Started</th>
                                        <th data-field="expiry" data-sortable="true">Expiry</th>
                                        <th data-field="days_left" data-sortable="true">Days Left</th>
                                        <th data-field="last_payment" data-sortable="true">Last Payment</th>
                                        <th data-field="operate" data-sortable="false">Manage</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="manage-seller-subscription-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Subscription - <span id="mss-shop-name"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mss-seller-id" value="">

                <div id="mss-current-summary" class="alert alert-light border mb-3 small mb-0"></div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#mss-tab-actions" role="tab">Actions</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mss-tab-history" role="tab">Subscription History</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mss-tab-payments" role="tab">Payments</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="mss-tab-actions" role="tabpanel">
                        <div class="form-group">
                            <label>Assign / Change Plan</label>
                            <div class="input-group">
                                <select class="form-control" id="mss-plan-select">
                                    <?php foreach ($plans as $plan) : ?>
                                        <option value="<?= $plan['id'] ?>"><?= html_escape($plan['name']) ?><?= ($plan['price'] !== null && $plan['price'] !== '') ? ' (' . html_escape($plan['price']) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-success" id="mss-assign-btn">Assign</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Takes effect immediately. Switching to a different plan (upgrade or downgrade) starts a
                                fresh validity period; re-assigning the plan the seller is already on renews it and
                                carries their unused days forward.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Extend Current Subscription</label>
                            <div class="input-group">
                                <input type="number" min="1" class="form-control" id="mss-extend-days" placeholder="Days">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" id="mss-extend-btn">Extend</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Only applies to an active plan that has an expiry date.</small>
                        </div>

                        <div class="form-group mb-0">
                            <button type="button" class="btn btn-danger btn-block" id="mss-cancel-btn">Cancel Current Subscription</button>
                            <small class="form-text text-muted">Keeps the history, but the seller can no longer add new listings.</small>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="mss-tab-history" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr><th>Plan</th><th>Price</th><th>Limit</th><th>Started</th><th>Ends</th><th>State</th></tr>
                                </thead>
                                <tbody id="mss-history-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="mss-tab-payments" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr><th>Date</th><th>Amount</th><th>Gateway</th><th>Transaction ID</th></tr>
                                </thead>
                                <tbody id="mss-payments-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="mss-error-box" class="text-danger mt-3"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-seller-subscriptions-page .text-primary-theme { color: var(--color-orange); }
    .admin-seller-subscriptions-page .bg-orange { background-color: var(--color-orange); }
    .admin-seller-subscriptions-page .attribute-card,
    .admin-seller-subscriptions-page .mss-summary-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-seller-subscriptions-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .admin-seller-subscriptions-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-seller-subscriptions-page .header-icon.bg-set { background: var(--color-orange); }
</style>

<script>
// Bootstrap-table reads this by name off window to build the request query string.
function mssQueryParams(params) {
    params.status_filter = $('#mss-status-filter').val() || '';
    return params;
}

$(document).ready(function () {
    function mssEsc(value) {
        return $('<div>').text(value === null || value === undefined ? '' : value).html();
    }

    function mssRefreshTable() {
        $('#mss-table').bootstrapTable('refresh');
    }

    $('#mss-status-filter').on('change', mssRefreshTable);

    $(document).on('click', '.manage-subscription-btn', function () {
        var sellerId = $(this).data('seller-id');

        $('#mss-seller-id').val(sellerId);
        $('#mss-shop-name').text($(this).data('shop-name'));
        $('#mss-extend-days').val('');
        $('#mss-error-box').text('');
        $('#mss-current-summary').html('<span class="text-muted">Loading...</span>');
        $('#mss-history-body').empty();
        $('#mss-payments-body').empty();
        $('a[href="#mss-tab-actions"]').tab('show');
        $('#manage-seller-subscription-modal').modal('show');

        $.getJSON('<?= base_url('admin/subscription/seller_subscription_history') ?>', { seller_id: sellerId }, function (res) {
            if (res.error) {
                $('#mss-current-summary').html('<span class="text-danger">' + mssEsc(res.message) + '</span>');
                return;
            }

            var q = res.quota || {};
            var limitText = (q.limit === null || q.limit === undefined) ? 'Unlimited' : q.limit;
            var statusText = q.status === 'active' ? '<span class="badge badge-success">Active</span>'
                : (q.status === 'expired' ? '<span class="badge badge-danger">Expired</span>'
                : '<span class="badge badge-secondary">No subscription</span>');

            $('#mss-current-summary').html(
                statusText +
                ' &nbsp;<strong>Plan:</strong> ' + mssEsc(q.plan_name || 'None') +
                ' &nbsp;<strong>Listings:</strong> ' + mssEsc(q.used) + ' / ' + mssEsc(limitText) +
                ' &nbsp;<strong>Can add listings:</strong> ' + (q.allowed ? 'Yes' : 'No')
            );

            var history = res.history || [];
            if (!history.length) {
                $('#mss-history-body').html('<tr><td colspan="6" class="text-center text-muted">No subscription history.</td></tr>');
            } else {
                history.forEach(function (h) {
                    var live = (parseInt(h.is_active, 10) === 1);
                    var ended = h.end_date && (new Date(h.end_date) < new Date());
                    var state = !live ? '<span class="badge badge-secondary">Ended</span>'
                        : (ended ? '<span class="badge badge-danger">Expired</span>' : '<span class="badge badge-success">Active</span>');
                    $('#mss-history-body').append(
                        '<tr><td>' + mssEsc(h.plan_name || '(deleted plan)') + '</td>' +
                        '<td>' + mssEsc(h.price) + '</td>' +
                        '<td>' + mssEsc(h.listings_limit) + '</td>' +
                        '<td>' + mssEsc(h.start_date) + '</td>' +
                        '<td>' + mssEsc(h.end_date || 'Never') + '</td>' +
                        '<td>' + state + '</td></tr>'
                    );
                });
            }

            var payments = res.payments || [];
            if (!payments.length) {
                $('#mss-payments-body').html('<tr><td colspan="4" class="text-center text-muted">No subscription payments recorded.</td></tr>');
            } else {
                payments.forEach(function (p) {
                    $('#mss-payments-body').append(
                        '<tr><td>' + mssEsc(p.date_created) + '</td>' +
                        '<td>' + mssEsc(p.amount) + '</td>' +
                        '<td>' + mssEsc(p.type) + '</td>' +
                        '<td><small>' + mssEsc(p.txn_id) + '</small></td></tr>'
                    );
                });
            }
        }).fail(function () {
            $('#mss-current-summary').html('<span class="text-danger">Could not load subscription details.</span>');
        });
    });

    $('#mss-assign-btn').on('click', function () {
        $('#mss-error-box').text('');
        $.post('<?= base_url('admin/subscription/assign_seller_subscription') ?>', {
            seller_id: $('#mss-seller-id').val(),
            subscription_id: $('#mss-plan-select').val()
        }, function (res) {
            if (res.error) { $('#mss-error-box').text(res.message); }
            else { $('#manage-seller-subscription-modal').modal('hide'); mssRefreshTable(); Swal.fire('Done', res.message, 'success'); }
        }, 'json');
    });

    $('#mss-extend-btn').on('click', function () {
        $('#mss-error-box').text('');
        $.post('<?= base_url('admin/subscription/extend_seller_subscription') ?>', {
            seller_id: $('#mss-seller-id').val(),
            days: $('#mss-extend-days').val()
        }, function (res) {
            if (res.error) { $('#mss-error-box').text(res.message); }
            else { $('#manage-seller-subscription-modal').modal('hide'); mssRefreshTable(); }
        }, 'json');
    });

    $('#mss-cancel-btn').on('click', function () {
        $('#mss-error-box').text('');
        $.post('<?= base_url('admin/subscription/cancel_seller_subscription') ?>', {
            seller_id: $('#mss-seller-id').val()
        }, function (res) {
            if (res.error) { $('#mss-error-box').text(res.message); }
            else { $('#manage-seller-subscription-modal').modal('hide'); mssRefreshTable(); }
        }, 'json');
    });
});
</script>
