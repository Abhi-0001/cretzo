    <div class="content-wrapper admin-seller-subscriptions-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0"><i class="fas fa-user-tag mr-2 text-primary-theme"></i>Seller Subscriptions</h4>
                    <p class="text-muted mb-0 small">View and manage each seller's current plan, listing usage, and expiry.</p>
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
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-list"></i></span>
                            <h5 class="mb-0">Sellers</h5>
                        </div>
                        <div class="card-body">
                            <table class='table-striped fixed-row-height' data-toggle="table" data-url="<?= base_url('admin/subscription/view_seller_subscriptions') ?>" data-side-pagination="client" data-pagination="true" data-page-list="[10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-mobile-responsive="true">
                                <thead>
                                    <tr>
                                        <th data-field="seller_id" data-sortable="true">ID</th>
                                        <th data-field="shop_name" data-sortable="true">Seller</th>
                                        <th data-field="plan_name" data-sortable="true">Plan</th>
                                        <th data-field="status" data-sortable="true">Status</th>
                                        <th data-field="used" data-sortable="true">Listings Used</th>
                                        <th data-field="limit" data-sortable="true">Listing Limit</th>
                                        <th data-field="expiry" data-sortable="true">Expiry</th>
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Subscription - <span id="mss-shop-name"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mss-seller-id" value="">

                <div class="form-group">
                    <label>Assign Plan</label>
                    <div class="input-group">
                        <select class="form-control" id="mss-plan-select">
                            <?php foreach ($plans as $plan) : ?>
                                <option value="<?= $plan['id'] ?>"><?= html_escape($plan['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-success" id="mss-assign-btn">Assign</button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Takes effect immediately, starting a fresh validity period on the new plan.</small>
                </div>

                <div class="form-group">
                    <label>Extend Current Subscription</label>
                    <div class="input-group">
                        <input type="number" min="1" class="form-control" id="mss-extend-days" placeholder="Days">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" id="mss-extend-btn">Extend</button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="button" class="btn btn-danger btn-block" id="mss-cancel-btn">Cancel Current Subscription</button>
                </div>

                <div id="mss-error-box" class="text-danger"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-seller-subscriptions-page .text-primary-theme { color: var(--color-orange); }
    .admin-seller-subscriptions-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
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
$(document).ready(function () {
    $(document).on('click', '.manage-subscription-btn', function () {
        $('#mss-seller-id').val($(this).data('seller-id'));
        $('#mss-shop-name').text($(this).data('shop-name'));
        $('#mss-extend-days').val('');
        $('#mss-error-box').text('');
        $('#manage-seller-subscription-modal').modal('show');
    });

    function mssRefreshTable() {
        $('table[data-toggle="table"]').bootstrapTable('refresh');
    }

    $('#mss-assign-btn').on('click', function () {
        $('#mss-error-box').text('');
        $.post('<?= base_url('admin/subscription/assign_seller_subscription') ?>', {
            seller_id: $('#mss-seller-id').val(),
            subscription_id: $('#mss-plan-select').val()
        }, function (res) {
            if (res.error) { $('#mss-error-box').text(res.message); }
            else { $('#manage-seller-subscription-modal').modal('hide'); mssRefreshTable(); }
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
