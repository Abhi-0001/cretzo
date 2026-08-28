<div class="content-wrapper seller-dashboard-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-th-large mr-2 text-primary-theme"></i>Dashboard</h4>
                    <p class="text-muted mb-0 small">Welcome back, <?= html_escape($username) ?>. Here's how your store is doing.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Home</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <div class="stat-card">
                        <span class="stat-icon" style="background:#e8532e"><i class="fas fa-shopping-cart"></i></span>
                        <div class="stat-body">
                            <h6>Orders</h6>
                            <h3><?= $order_counter ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <div class="stat-card">
                        <span class="stat-icon" style="background:#2e93e8"><i class="fas fa-box-open"></i></span>
                        <div class="stat-body">
                            <h6>Products</h6>
                            <h3><?= $products ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <div class="stat-card">
                        <span class="stat-icon" style="background:#e8a12e"><i class="fas fa-star"></i></span>
                        <div class="stat-body">
                            <h6>Rating</h6>
                            <h3><?= intval($ratings[0]['rating'] ?? 0) . "/" . ($ratings[0]['no_of_ratings'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <div class="stat-card">
                        <span class="stat-icon bg-set"><i class="fas fa-wallet"></i></span>
                        <div class="stat-body">
                            <h6>Balance (<?= $curreny ?>)</h6>
                            <h3><?= number_format($balance, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($subscription_status) && $subscription_status === 'active' && !empty($current_subscription_plan)) : ?>
                <div class="alert-banner alert-banner-active mb-3">
                    <div>
                        <strong>Current Plan:</strong>
                        <?= html_escape($current_subscription_plan['name']); ?>
                        <span class="ml-2 badge badge-light"><?= html_escape(plan_validity_text(isset($current_subscription_plan['validity']) ? $current_subscription_plan['validity'] : '')); ?></span>
                        <?php if (!empty($active_subscription['end_date'])) :
                            // Countdown, not just a date: the seller needs to notice the plan is
                            // about to lapse while they can still act on it. A plan with no
                            // end_date is genuinely unlimited and says so, rather than rendering
                            // nothing at all like it used to.
                            $days_left = (int) floor((strtotime($active_subscription['end_date']) - strtotime(date('Y-m-d'))) / 86400);
                        ?>
                            <span class="ml-2 text-sm <?= $days_left <= 7 ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                                (Valid till <?= date('d M Y', strtotime($active_subscription['end_date'])); ?>
                                &mdash; <?= $days_left <= 0 ? 'expires today' : $days_left . ' day' . ($days_left === 1 ? '' : 's') . ' left' ?>)
                            </span>
                        <?php else : ?>
                            <span class="ml-2 text-sm text-muted">(No expiry)</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= base_url('seller/subscription/manage_subscriptions'); ?>" class="btn btn-sm btn-primary-theme">Manage / Upgrade Plan</a>
                </div>
            <?php elseif (isset($subscription_status) && $subscription_status === 'expired' && !empty($current_subscription_plan)) : ?>
                <div class="alert-banner alert-banner-expired mb-3">
                    <div>
                        <strong>Subscription expired:</strong>
                        <?= html_escape($current_subscription_plan['name']); ?>
                        <?php if (!empty($subscription_expired_on)) : ?>
                            <span class="ml-2">(Expired on <?= html_escape($subscription_expired_on); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= base_url('seller/subscription/manage_subscriptions'); ?>" class="btn btn-sm btn-primary-theme">Renew / Upgrade</a>
                </div>
            <?php elseif (isset($subscription_status) && $subscription_status === 'none') : ?>
                <div class="alert-banner alert-banner-none mb-3">
                    <div>
                        <strong>No active subscription:</strong>
                        You don't have a plan yet. Choose a subscription to start selling.
                    </div>
                    <a href="<?= base_url('seller/subscription/manage_subscriptions'); ?>" class="btn btn-sm btn-primary-theme">Choose a Plan</a>
                </div>
            <?php endif; ?>

            <div class="card attribute-card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="text-muted mb-0">Profile Completion: <span id="percent-text">0</span>%</h5>
                                <a href="<?= base_url('seller/home/profile') ?>" class="btn btn-sm btn-outline-primary-theme">Update Profile</a>
                            </div>
                            <div class="dash-progress">
                                <div id="profile-bar" class="dash-progress-bar" role="progressbar" style="width: 0%;" data-target-width="<?= (int)($profile_completion['percentage'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($profile_completion['missing_sections'])): ?>
                        <hr class="mt-2 mb-3">
                        <p class="mb-2 text-muted small">Complete your profile to start selling.</p>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($profile_completion['missing_sections'] as $section): ?>
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?= html_escape($section['label']) ?></span>
                                    <a href="<?= $section['link'] ?>" class="btn btn-sm btn-primary-theme">Complete</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mb-0 text-success"><i class="fa fa-check-circle"></i> Your seller profile is complete.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6 col-12 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-chart-line"></i></span>
                                <h5 class="mb-0">Product Sales</h5>
                            </div>
                            <ul class="nav nav-pills nav-pills-rounded chart-action" role="group" id="ecommerceChartView">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#scoreLineToDay">Day</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#scoreLineToWeek">Week</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#scoreLineToMonth">Month</a></li>
                            </ul>
                        </div>
                        <div class="card-body tab-content">
                            <div class="ct-chart tab-pane active" id="scoreLineToDay"></div>
                            <div class="ct-chart tab-pane" id="scoreLineToWeek"></div>
                            <div class="ct-chart tab-pane" id="scoreLineToMonth"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-12 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set"><i class="fas fa-chart-pie"></i></span>
                            <h5 class="mb-0">Category-wise Product Count</h5>
                        </div>
                        <div class="card-body">
                            <div id="piechart_3d" class="piechart-height"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 col-12 mb-3">
                    <div class="dash-notice dash-notice-danger">
                        <h6><i class="fas fa-exclamation-circle mr-1"></i><?= $count_products_availability_status ?> Product(s) sold out!</h6>
                        <a href="<?= base_url('seller/product/?flag=sold') ?>">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <?php $settings = get_settings('system_settings', true); ?>
                <div class="col-md-6 col-12 mb-3">
                    <div class="dash-notice dash-notice-warning">
                        <h6><i class="fas fa-exclamation-triangle mr-1"></i><?= $count_products_low_status ?> Product(s) low in stock! <small>(Low stock limit <?= isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : '5' ?>)</small></h6>
                        <a href="<?= base_url('seller/product/?flag=low') ?>">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <h5 class="mb-3">Order Outlines</h5>
            <div class="row mb-2">
                <div class="col-6 col-md-2 mb-3">
                    <div class="order-outline-box" style="background:#2e93e8">
                        <div><h3><?= $status_counts['received'] ?></h3><p>Received</p></div>
                        <i class="fas fa-level-down-alt"></i>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-3">
                    <div class="order-outline-box" style="background:#17a2b8">
                        <div><h3><?= $status_counts['processed'] ?></h3><p>Processed</p></div>
                        <i class="fas fa-people-carry"></i>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-3">
                    <div class="order-outline-box bg-set">
                        <div><h3><?= $status_counts['shipped'] ?></h3><p>Shipped</p></div>
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-3">
                    <div class="order-outline-box" style="background:#3fa25c">
                        <div><h3><?= $status_counts['delivered'] ?></h3><p>Delivered</p></div>
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-3">
                    <div class="order-outline-box" style="background:#c1443a">
                        <div><h3><?= $status_counts['cancelled'] ?></h3><p>Cancelled</p></div>
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-3">
                    <div class="order-outline-box" style="background:#6c757d">
                        <div><h3><?= $status_counts['returned'] ?></h3><p>Returned</p></div>
                        <i class="fas fa-level-up-alt"></i>
                    </div>
                </div>
            </div>

            <div class="card attribute-card mb-3">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-receipt"></i></span>
                    <h5 class="mb-0">Recent Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="form-group col-md-4">
                            <label>Date range</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" class="form-control" id="datepicker" placeholder="Select Date Range" autocomplete="off">
                                <input type="hidden" id="start_date">
                                <input type="hidden" id="end_date">
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Filter by status</label>
                            <select id="order_status" name="order_status" class="form-control">
                                <option value="">All Orders</option>
                                <option value="received">Received</option>
                                <option value="processed">Processed</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Filter by payment method</label>
                            <select id="payment_method" name="payment_method" class="form-control">
                                <option value="">All Payment Methods</option>
                                <option value="COD">Cash On Delivery</option>
                                <option value="Paypal">Paypal</option>
                                <option value="RazorPay">RazorPay</option>
                                <option value="Paystack">Paystack</option>
                                <option value="Flutterwave">Flutterwave</option>
                                <option value="Paytm">Paytm</option>
                                <option value="Stripe">Stripe</option>
                                <option value="bank_transfer">Direct Bank Transfers</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary-theme btn-block" onclick="status_date_wise_search()">Filter</button>
                        </div>
                    </div>

                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/orders/view_order_items') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="o.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "order-items-list","ignoreColumn": ["state"] }' data-query-params="orders_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable='true' data-footer-formatter="totalFormatter">ID</th>
                                <th data-field="order_item_id" data-sortable='true'>Order Item ID</th>
                                <th data-field="order_id" data-sortable='true'>Order ID</th>
                                <th data-field="user_id" data-sortable='true' data-visible="false">User ID</th>
                                <th data-field="seller_id" data-sortable='true' data-visible="false">Seller ID</th>
                                <th data-field="is_credited" data-sortable='true' data-visible="false">Commission</th>
                                <th data-field="quantity" data-sortable='true' data-visible="false">Quantity</th>
                                <th data-field="username" data-sortable='true'>User Name</th>
                                <th data-field="seller_name" data-sortable='true'>Seller Name</th>
                                <th data-field="product_name" data-sortable='true'>Product Name</th>
                                <th data-field="mobile" data-sortable='true'>Mobile</th>
                                <th data-field="sub_total" data-sortable='true' data-visible="true">Total(<?= $curreny ?>)</th>
                                <th data-field="payment_method" data-sortable='true' data-visible='false'>Payment Method</th>
                                <th data-field="delivery_boy" data-sortable='true' data-visible='false'>Deliver By</th>
                                <th data-field="delivery_boy_id" data-sortable='true' data-visible='false'>Delivery Boy Id</th>
                                <th data-field="product_variant_id" data-sortable='true' data-visible='false'>Product Variant Id</th>
                                <th data-field="delivery_date" data-sortable='true' data-visible='false'>Delivery Date</th>
                                <th data-field="delivery_time" data-sortable='true' data-visible='false'>Delivery Time</th>
                                <th data-field="status" data-sortable='true' data-visible='false'>Status</th>
                                <th data-field="active_status" data-sortable='true' data-visible='true'>Active Status</th>
                                <th data-field="date_added" data-sortable='true'>Order Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<?php
// Approval-gate popups: nags until the profile is submitted for review, reassures while it
// is pending, and congratulates exactly once after the admin approves. See
// seller_approval_state().
$this->load->view('seller/include-approval-modals', ['approval_modal_mode' => 'dashboard']);
?>

<style>
    .seller-dashboard-page .text-primary-theme { color: var(--color-orange); }
    .seller-dashboard-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .seller-dashboard-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .seller-dashboard-page .btn-outline-primary-theme {
        border: 1px solid var(--color-orange);
        color: var(--color-orange-dark);
        font-weight: 600;
        background: #fff;
    }
    .seller-dashboard-page .btn-outline-primary-theme:hover { background: var(--color-orange); color: #fff; }

    .seller-dashboard-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .seller-dashboard-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        /* justify-content: space-between; */
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .seller-dashboard-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px;
    }
    .seller-dashboard-page .header-icon.bg-set,
    .seller-dashboard-page .stat-icon.bg-set,
    .seller-dashboard-page .order-outline-box.bg-set { background: var(--color-orange); }

    .seller-dashboard-page .stat-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        height: 100%;
    }
    .seller-dashboard-page .stat-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px; flex: none;
    }
    .seller-dashboard-page .stat-body h6 { margin: 0; color: var(--color-grey); font-size: 13px; text-transform: uppercase; letter-spacing: .3px; font-weight: 600; }
    .seller-dashboard-page .stat-body h3 { margin: 2px 0 0; font-weight: 700; }

    .seller-dashboard-page .alert-banner {
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        flex-wrap: wrap;
        padding: 14px 18px;
        border-radius: 10px;
        border: 1px solid transparent;
    }
    .seller-dashboard-page .alert-banner-active { background: var(--color-secondary); border-color: rgba(242,130,46,0.35); }
    .seller-dashboard-page .alert-banner-expired { background: #FBEAE8; border-color: rgba(193,68,58,0.3); color: #8a2f27; }
    .seller-dashboard-page .alert-banner-none { background: #EAF2FB; border-color: rgba(46,120,232,0.25); color: #1c4a80; }

    .seller-dashboard-page .dash-progress { height: 8px; border-radius: 10px; background: #eee; overflow: hidden; }
    .seller-dashboard-page .dash-progress-bar { height: 100%; border-radius: 10px; background: var(--color-orange); transition: width .3s ease; }

    .seller-dashboard-page .chart-action { margin: 0; }
    .seller-dashboard-page .chart-action .nav-link { color: var(--color-grey); border-radius: 20px; padding: 4px 14px; font-size: 13px; }
    .seller-dashboard-page .chart-action .nav-link.active { background: var(--color-orange); color: #fff; }
    .seller-dashboard-page .ct-chart { height: 260px; }
    .seller-dashboard-page .piechart-height { height: 260px; }

    .seller-dashboard-page .dash-notice {
        border-radius: 10px;
        padding: 14px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .seller-dashboard-page .dash-notice h6 { margin: 0; font-weight: 600; }
    .seller-dashboard-page .dash-notice a { color: inherit; text-decoration: none; font-size: 13px; white-space: nowrap; }
    .seller-dashboard-page .dash-notice-danger { background: #FBEAE8; color: #8a2f27; }
    .seller-dashboard-page .dash-notice-warning { background: #FBF3DC; color: #6b5100; }

    .seller-dashboard-page .order-outline-box {
        border-radius: 10px;
        color: #fff;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
    }
    .seller-dashboard-page .order-outline-box h3 { margin: 0; font-weight: 700; }
    .seller-dashboard-page .order-outline-box p { margin: 0; font-size: 13px; opacity: .9; }
    .seller-dashboard-page .order-outline-box i { font-size: 22px; opacity: .85; }

    .seller-dashboard-page .fixed-table-toolbar { margin-bottom: 10px; }
    .seller-dashboard-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .seller-dashboard-page .fixed-table-toolbar .btn-group > .btn,
    .seller-dashboard-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .seller-dashboard-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .seller-dashboard-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .seller-dashboard-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .seller-dashboard-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .seller-dashboard-page table.table thead th {
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
    .seller-dashboard-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .seller-dashboard-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .seller-dashboard-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .seller-dashboard-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }
</style>

<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    // The profile-completion progress bar, order table filters (date range/status/payment),
    // the sales chart and the category pie chart all only ever shipped in the admin JS bundle
    // (assets/admin/custom/custom.js), which isn't loaded on seller pages — so on this page,
    // before this fix, none of them worked at all. Everything below is a self-contained
    // replacement scoped to just this page.
    (function () {
        var progressBar = document.getElementById('profile-bar');
        var percentText = document.getElementById('percent-text');
        if (!progressBar) return;
        var target = Math.max(0, Math.min(100, parseInt(progressBar.getAttribute('data-target-width') || '0', 10)));
        var duration = 900;
        var startTime = null;
        function animate(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var current = Math.floor(progress * target);
            progressBar.style.width = current + '%';
            percentText.textContent = current;
            if (progress < 1) requestAnimationFrame(animate);
        }
        requestAnimationFrame(animate);
    })();

    function status_date_wise_search() {
        $('.seller-dashboard-page .table-striped').bootstrapTable('refresh');
    }

    // Selecting a status/payment method applies the filter immediately, instead of
    // requiring a separate click on "Filter" — the dropdowns alone doing nothing until
    // that button is clicked is what made this look broken.
    $('#order_status, #payment_method').on('change', status_date_wise_search);

    function orders_query_params(p) {
        return {
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            order_status: $('#order_status').val(),
            payment_method: $('#payment_method').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    $(document).ready(function () {
        $('#datepicker').daterangepicker({
            showDropdowns: true,
            alwaysShowCalendars: true,
            autoUpdateInput: false,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
    });
    $('#datepicker').on('apply.daterangepicker', function (ev, picker) {
        $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
    $('#datepicker').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#start_date').val('');
        $('#end_date').val('');
    });

    // Category-wise product count pie chart
    if (document.getElementById('piechart_3d')) {
        $.ajax({
            url: "<?= base_url('seller/home/category_wise_product_count') ?>",
            type: 'GET',
            dataType: 'json',
            success: function (result) {
                google.charts.load('current', { packages: ['corechart'] });
                google.charts.setOnLoadCallback(function () {
                    var data = google.visualization.arrayToDataTable(result);
                    var chart = new google.visualization.PieChart(document.getElementById('piechart_3d'));
                    chart.draw(data, { title: '', is3D: true, chartArea: { width: '90%', height: '80%' } });
                });
            }
        });
    }

    // Product sales chart (Day / Week / Month tabs). Deferred to document-ready:
    // Chartist.js itself only loads at the very bottom of <body> (seller/include-script.php),
    // and on a fast local response this ajax call's success callback can otherwise fire
    // before that script has executed, throwing "Chartist is not defined" — the same class
    // of load-order bug as the moment/daterangepicker issue found elsewhere in this panel.
    $(document).ready(function () {
    if (document.getElementById('ecommerceChartView')) {
        $.ajax({
            url: "<?= base_url('seller/home/fetch_sales') ?>",
            type: 'GET',
            dataType: 'json',
            success: function (result) {
                var series = {
                    day: { labels: result[2].day || [], data: result[2].total_sale || [] },
                    week: { labels: result[1].week || [], data: result[1].total_sale || [] },
                    month: { labels: result[0].month_name || [], data: result[0].total_sale || [] }
                };
                var rendered = {};

                function renderChart(key, elId) {
                    document.getElementById(elId).innerHTML = '';
                    new Chartist.Line('#' + elId, {
                        labels: series[key].labels,
                        series: [series[key].data]
                    }, {
                        lineSmooth: Chartist.Interpolation.simple({ divisor: 2 }),
                        fullWidth: true,
                        chartPadding: { right: 25 },
                        axisY: { labelInterpolationFnc: function (v) { return v; } },
                        low: 0,
                        showArea: true
                    });
                    rendered[key] = true;
                }

                renderChart('day', 'scoreLineToDay');

                $('#ecommerceChartView a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                    var target = $(e.target).attr('href');
                    var map = { '#scoreLineToDay': 'day', '#scoreLineToWeek': 'week', '#scoreLineToMonth': 'month' };
                    var key = map[target];
                    if (key && !rendered[key]) {
                        renderChart(key, target.substring(1));
                    }
                });
            }
        });
    }
    });
</script>
