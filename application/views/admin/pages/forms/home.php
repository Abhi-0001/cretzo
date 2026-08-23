<?php
$settings = get_settings('system_settings', true);
$currency = (isset($settings['currency']) && !empty($settings['currency'])) ? $settings['currency'] : (isset($curreny) ? $curreny : '');
$low_stock_limit = isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : '5';
?>
<div class="content-wrapper admin-dashboard-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-th-large mr-2 text-primary-theme"></i>Dashboard</h4>
                    <p class="text-muted mb-0 small">Overview of orders, earnings, sellers and stock across the marketplace.</p>
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

            <!-- ============ CONFIGURATION HEALTH ============ -->
            <?php if (!empty($configuration_problems)) { ?>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="config-health-card">
                            <div class="config-health-head">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Store configuration needs attention</span>
                            </div>
                            <ul class="config-health-list">
                                <?php foreach ($configuration_problems as $problem) { ?>
                                    <li class="config-health-item config-health-<?= html_escape($problem['severity']) ?>">
                                        <div class="config-health-title">
                                            <span class="config-health-badge"><?= $problem['severity'] === 'critical' ? 'Blocks orders' : 'Check' ?></span>
                                            <?= html_escape($problem['title']) ?>
                                        </div>
                                        <div class="config-health-text"><?= html_escape($problem['message']) ?></div>
                                        <?php if (!empty($problem['url'])) { ?>
                                            <a class="config-health-link" href="<?= $problem['url'] ?>"><?= html_escape($problem['link']) ?> &rarr;</a>
                                        <?php } ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <!-- ============ KEY COUNTS ============ -->
            <div class="row">
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <a href="<?= base_url('admin/orders') ?>" class="stat-card">
                        <span class="stat-icon" style="background:#e8532e"><i class="fas fa-shopping-cart"></i></span>
                        <div class="stat-body">
                            <h6>Orders</h6>
                            <h3><?= (int) $order_counter ?></h3>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <a href="<?= base_url('admin/customer') ?>" class="stat-card">
                        <span class="stat-icon" style="background:#2e93e8"><i class="fas fa-users"></i></span>
                        <div class="stat-body">
                            <h6>Customers</h6>
                            <h3><?= (int) $user_counter ?></h3>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <a href="<?= base_url('admin/delivery_boys') ?>" class="stat-card">
                        <span class="stat-icon" style="background:#3fa25c"><i class="fas fa-motorcycle"></i></span>
                        <div class="stat-body">
                            <h6>Delivery Boys</h6>
                            <h3><?= (int) $delivery_boy_counter ?></h3>
                        </div>
                    </a>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
                    <a href="<?= base_url('admin/product') ?>" class="stat-card">
                        <span class="stat-icon bg-set"><i class="fas fa-box-open"></i></span>
                        <div class="stat-body">
                            <h6>Products</h6>
                            <h3><?= (int) $product_counter ?></h3>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ============ EARNINGS ============ -->
            <div class="row">
                <div class="col-md-4 col-sm-6 col-12 mb-3">
                    <div class="earning-card earning-card-total">
                        <span class="earning-icon"><i class="fas fa-coins"></i></span>
                        <div>
                            <h6>Total Earnings (<?= html_escape($currency) ?>)</h6>
                            <h3><?= number_format((float) $total_earnings, 2) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 col-12 mb-3">
                    <div class="earning-card earning-card-admin">
                        <span class="earning-icon"><i class="fas fa-user-shield"></i></span>
                        <div>
                            <h6>Admin Earnings (<?= html_escape($currency) ?>)</h6>
                            <h3><?= number_format((float) $admin_earnings, 2) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 col-12 mb-3">
                    <div class="earning-card earning-card-seller">
                        <span class="earning-icon"><i class="fas fa-store"></i></span>
                        <div>
                            <h6>Seller Earnings (<?= html_escape($currency) ?>)</h6>
                            <h3><?= number_format((float) $seller_earnings, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ CHARTS ============ -->
            <div class="row">
                <div class="col-xl-6 col-12 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-chart-line"></i></span>
                                <h5 class="mb-0">Product Sales</h5>
                            </div>
                            <ul class="nav nav-pills nav-pills-rounded chart-action" role="group" id="adminSalesChartView">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#adminScoreLineToDay">Day</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#adminScoreLineToWeek">Week</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#adminScoreLineToMonth">Month</a></li>
                            </ul>
                        </div>
                        <div class="card-body tab-content">
                            <div class="ct-chart tab-pane active" id="adminScoreLineToDay"></div>
                            <div class="ct-chart tab-pane" id="adminScoreLineToWeek"></div>
                            <div class="ct-chart tab-pane" id="adminScoreLineToMonth"></div>
                            <div class="chart-empty d-none" id="adminSalesEmpty">
                                <i class="fas fa-chart-area"></i>
                                <p>No sales recorded for this period yet.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-12 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-chart-pie"></i></span>
                            <h5 class="mb-0">Category-wise Product Count</h5>
                        </div>
                        <div class="card-body">
                            <div id="admin_piechart" class="piechart-height"></div>
                            <div class="chart-empty d-none" id="adminPieEmpty">
                                <i class="fas fa-folder-open"></i>
                                <p>No active products are assigned to a category yet.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ STOCK NOTICES ============ -->
            <div class="row">
                <div class="col-md-6 col-12 mb-3">
                    <div class="dash-notice dash-notice-danger">
                        <h6><i class="fas fa-exclamation-circle mr-1"></i><?= (int) $count_products_availability_status ?> Product(s) sold out!</h6>
                        <a href="<?= base_url('admin/product/?flag=sold') ?>">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-12 mb-3">
                    <div class="dash-notice dash-notice-warning">
                        <h6><i class="fas fa-exclamation-triangle mr-1"></i><?= (int) $count_products_low_status ?> Product(s) low in stock! <small>(Low stock limit <?= html_escape($low_stock_limit) ?>)</small></h6>
                        <a href="<?= base_url('admin/product/?flag=low') ?>">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- ============ SELLER DETAILS ============ -->
            <h5 class="section-title">Seller Details</h5>
            <div class="row">
                <div class="col-md-4 col-12 mb-3">
                    <button type="button" class="seller-stat-box" style="background:#3fa25c" data-toggle="modal" data-target="#approved_sellers">
                        <div>
                            <h3><?= (int) $count_approved_sellers ?></h3>
                            <p>Approved Sellers</p>
                        </div>
                        <i class="fas fa-check-circle"></i>
                    </button>
                </div>
                <div class="col-md-4 col-12 mb-3">
                    <button type="button" class="seller-stat-box" style="background:#2e93e8" data-toggle="modal" data-target="#not_approved_sellers">
                        <div>
                            <h3><?= (int) $count_not_approved_sellers ?></h3>
                            <p>Pending Approval</p>
                        </div>
                        <i class="fas fa-pause-circle"></i>
                    </button>
                </div>
                <div class="col-md-4 col-12 mb-3">
                    <button type="button" class="seller-stat-box" style="background:#c1443a" data-toggle="modal" data-target="#deactive_sellers">
                        <div>
                            <h3><?= (int) $count_deactive_sellers ?></h3>
                            <p>Deactivated Sellers</p>
                        </div>
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <!-- ============ TOP SELLERS / TOP CATEGORIES ============ -->
            <!-- Cell values arrive already HTML-escaped from the model, as everywhere else in
                 this panel. Do not add data-escape="true" here: bootstrap-table would escape
                 them a second time and a name like Women's Wear renders as Women&amp;#039;s Wear. -->
            <div class="row">
                <div class="col-lg-6 col-12 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-trophy"></i></span>
                            <h5 class="mb-0">Top Sellers</h5>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' id='top_sellers_table' data-toggle="table"
                                data-url="<?= base_url('admin/sellers/top_seller') ?>"
                                data-side-pagination="server" data-pagination="false" data-search="false"
                                data-show-columns="false" data-show-refresh="true" data-mobile-responsive="true"
                                data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="seller_id">ID</th>
                                        <th data-field="seller_name">Seller Name</th>
                                        <th data-field="store_name">Store Name</th>
                                        <th data-field="total" data-formatter="dashCurrencyFormatter">Total (<?= html_escape($currency) ?>)</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-12 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-layer-group"></i></span>
                            <h5 class="mb-0">Top Categories</h5>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' id='top_categories_table' data-toggle="table"
                                data-url="<?= base_url('admin/Category/top_category') ?>"
                                data-side-pagination="server" data-pagination="false" data-search="false"
                                data-show-columns="false" data-show-refresh="true" data-mobile-responsive="true"
                                data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id">ID</th>
                                        <th data-field="name">Category Name</th>
                                        <th data-field="clicks">Clicks</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ ORDER OUTLINES ============ -->
            <h5 class="section-title">Order Outlines</h5>
            <div class="row">
                <div class="col-6 col-md-4 col-xl mb-3">
                    <a href="<?= base_url('admin/orders?status=awaiting') ?>" class="order-outline-box" style="background:#8e6bbf">
                        <div><h3><?= (int) $status_counts['awaiting'] ?></h3><p>Awaiting</p></div>
                        <i class="fas fa-history"></i>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl mb-3">
                    <a href="<?= base_url('admin/orders?status=received') ?>" class="order-outline-box" style="background:#2e93e8">
                        <div><h3><?= (int) $status_counts['received'] ?></h3><p>Received</p></div>
                        <i class="fas fa-level-down-alt"></i>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl mb-3">
                    <a href="<?= base_url('admin/orders?status=processed') ?>" class="order-outline-box" style="background:#17a2b8">
                        <div><h3><?= (int) $status_counts['processed'] ?></h3><p>Processed</p></div>
                        <i class="fas fa-people-carry"></i>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl mb-3">
                    <a href="<?= base_url('admin/orders?status=shipped') ?>" class="order-outline-box bg-set">
                        <div><h3><?= (int) $status_counts['shipped'] ?></h3><p>Shipped</p></div>
                        <i class="fas fa-shipping-fast"></i>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl mb-3">
                    <a href="<?= base_url('admin/orders?status=delivered') ?>" class="order-outline-box" style="background:#3fa25c">
                        <div><h3><?= (int) $status_counts['delivered'] ?></h3><p>Delivered</p></div>
                        <i class="fas fa-user-check"></i>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl mb-3">
                    <a href="<?= base_url('admin/orders?status=cancelled') ?>" class="order-outline-box" style="background:#c1443a">
                        <div><h3><?= (int) $status_counts['cancelled'] ?></h3><p>Cancelled</p></div>
                        <i class="fas fa-times-circle"></i>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl mb-3">
                    <a href="<?= base_url('admin/orders?status=returned') ?>" class="order-outline-box" style="background:#6c757d">
                        <div><h3><?= (int) $status_counts['returned'] ?></h3><p>Returned</p></div>
                        <i class="fas fa-level-up-alt"></i>
                    </a>
                </div>
            </div>

            <!-- ============ RECENT ORDERS ============ -->
            <div class="card attribute-card mb-3">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set mr-2"><i class="fas fa-receipt"></i></span>
                    <h5 class="mb-0">Recent Orders</h5>
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
                                <option value="awaiting">Awaiting</option>
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
                            <button type="button" class="btn btn-outline-primary-theme btn-block" onclick="admin_home_reset_filters()">
                                <i class="fas fa-undo mr-1"></i>Reset
                            </button>
                        </div>
                    </div>

                    <table class='table-striped fixed-row-height' id="admin-orders-table" data-toggle="table"
                        data-url="<?= base_url('admin/orders/view_orders') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "order-list", "ignoreColumn": ["state"]}'
                        data-query-params="admin_home_query_params">
                        <thead>
                            <tr>
                                <th data-field="operate">Action</th>
                                <th data-field="id" data-sortable='true' data-footer-formatter="totalFormatter">Order ID</th>
                                <th data-field="user_id" data-sortable='true' data-visible="false">User ID</th>
                                <th data-field="sellers" data-sortable='false'>Sellers</th>
                                <th data-field="qty" data-sortable='false' data-visible="false">Qty</th>
                                <th data-field="name" data-sortable='false'>User Name</th>
                                <th data-field="mobile" data-sortable='false' data-visible="false">Mobile</th>
                                <th data-field="items" data-sortable='false' data-visible="false">Items</th>
                                <th data-field="total" data-sortable='true' data-visible="true">Total(<?= html_escape($currency) ?>)</th>
                                <th data-field="delivery_charge" data-sortable='true' data-footer-formatter="delivery_chargeFormatter" data-visible="true">D.Charge</th>
                                <th data-field="wallet_balance" data-sortable='true' data-visible="true">Wallet Used(<?= html_escape($currency) ?>)</th>
                                <th data-field="promo_code" data-sortable='true' data-visible="false">Promo Code</th>
                                <th data-field="promo_discount" data-sortable='true' data-visible="true">Promo disc.(<?= html_escape($currency) ?>)</th>
                                <th data-field="final_total" data-sortable='true'>Final Total(<?= html_escape($currency) ?>)</th>
                                <th data-field="deliver_by" data-sortable='false' data-visible='false'>Deliver By</th>
                                <th data-field="payment_method" data-sortable='true' data-visible="true">Payment Method</th>
                                <th data-field="address" data-sortable='false'>Address</th>
                                <th data-field="delivery_date" data-sortable='true' data-visible='false'>Delivery Date</th>
                                <th data-field="delivery_time" data-sortable='false' data-visible='false'>Delivery Time</th>
                                <th data-field="notes" data-sortable='false' data-visible='false'>O. Notes</th>
                                <th data-field="date_added" data-sortable='true'>Order Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>

    <!-- ============ ORDER TRACKING MODAL ============ -->
    <div class="modal fade" id="order-tracking-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Order Tracking</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="order_id">
                    <table class='table-striped' id="order_tracking_table" data-toggle="table"
                        data-url="<?= base_url('admin/orders/get-order-tracking') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-query-params="order_tracking_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="order_id" data-sortable="true">Order ID</th>
                                <th data-field="order_item_id" data-sortable="false">Order Item ID</th>
                                <th data-field="courier_agency" data-sortable="false">Courier Agency</th>
                                <th data-field="tracking_id" data-sortable="false">Tracking ID</th>
                                <th data-field="url" data-sortable="false">URL</th>
                                <th data-field="date" data-sortable="false">Date</th>
                                <th data-field="operate" data-sortable="false">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ SELLER LIST MODALS ============ -->
<div class="modal fade" id="approved_sellers" tabindex="-1" role="dialog" aria-labelledby="approved_sellers_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approved_sellers_label">Approved Sellers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class='table-striped' id='approved_sellers_table' data-toggle="table"
                    data-url="<?= base_url('admin/sellers/approved_sellers') ?>" data-click-to-select="true"
                    data-side-pagination="server" data-pagination="true" data-page-list="[5,10,25,50]"
                    data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                    data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]'
                    data-query-params="queryParams">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">ID</th>
                            <th data-field="name" data-sortable="false">Name</th>
                            <th data-field="mobile" data-sortable="false">Mobile No</th>
                            <th data-field="date" data-sortable="false">Date</th>
                            <th data-field="operate">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="not_approved_sellers" tabindex="-1" role="dialog" aria-labelledby="not_approved_sellers_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="not_approved_sellers_label">Sellers Pending Approval</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class='table-striped' id='not_approved_sellers_table' data-toggle="table"
                    data-url="<?= base_url('admin/sellers/not_approved_sellers') ?>" data-click-to-select="true"
                    data-side-pagination="server" data-pagination="true" data-page-list="[5,10,25,50]"
                    data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                    data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]'
                    data-query-params="queryParams">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">ID</th>
                            <th data-field="name" data-sortable="false">Name</th>
                            <th data-field="mobile" data-sortable="false">Mobile No</th>
                            <th data-field="date" data-sortable="false">Date</th>
                            <th data-field="operate">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deactive_sellers" tabindex="-1" role="dialog" aria-labelledby="deactive_sellers_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deactive_sellers_label">Deactivated Sellers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class='table-striped' id='deactive_sellers_table' data-toggle="table"
                    data-url="<?= base_url('admin/sellers/deactive_sellers') ?>" data-click-to-select="true"
                    data-side-pagination="server" data-pagination="true" data-page-list="[5,10,25,50]"
                    data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                    data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]'
                    data-query-params="queryParams">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">ID</th>
                            <th data-field="name" data-sortable="false">Name</th>
                            <th data-field="mobile" data-sortable="false">Mobile No</th>
                            <th data-field="date" data-sortable="false">Date</th>
                            <th data-field="operate">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-dashboard-page .config-health-card {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        border-left: 4px solid #e8532e;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .admin-dashboard-page .config-health-head {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px;
        font-weight: 600;
        color: #b23c1d;
        background: #fff6f3;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .admin-dashboard-page .config-health-list { list-style: none; margin: 0; padding: 0; }
    .admin-dashboard-page .config-health-item {
        padding: 12px 16px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .admin-dashboard-page .config-health-item:last-child { border-bottom: none; }
    .admin-dashboard-page .config-health-title {
        font-weight: 600; color: #333; margin-bottom: 3px;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .admin-dashboard-page .config-health-badge {
        font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px;
        padding: 2px 8px; border-radius: 20px; color: #fff; background: #6c757d;
    }
    .admin-dashboard-page .config-health-critical .config-health-badge { background: #dc3545; }
    .admin-dashboard-page .config-health-warning .config-health-badge { background: #f0ad4e; }
    .admin-dashboard-page .config-health-text { color: #666; font-size: 13px; line-height: 1.5; }
    .admin-dashboard-page .config-health-link {
        display: inline-block; margin-top: 6px; font-size: 13px; font-weight: 600;
        color: #e8532e; text-decoration: none;
    }
    .admin-dashboard-page .config-health-link:hover { text-decoration: underline; }
    .admin-dashboard-page .text-primary-theme { color: var(--color-orange); }
    .admin-dashboard-page .section-title { margin: 6px 0 14px; font-weight: 600; }

    .admin-dashboard-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-dashboard-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .admin-dashboard-page .btn-outline-primary-theme {
        border: 1px solid var(--color-orange);
        color: var(--color-orange-dark);
        font-weight: 600;
        background: #fff;
    }
    .admin-dashboard-page .btn-outline-primary-theme:hover { background: var(--color-orange); color: #fff; }

    .admin-dashboard-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-dashboard-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-dashboard-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-dashboard-page .header-icon.bg-set,
    .admin-dashboard-page .stat-icon.bg-set,
    .admin-dashboard-page .order-outline-box.bg-set { background: var(--color-orange); }

    /* ---- stat cards ---- */
    .admin-dashboard-page .stat-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        height: 100%;
        color: inherit;
        text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .admin-dashboard-page .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,0.10);
        color: inherit;
        text-decoration: none;
    }
    .admin-dashboard-page .stat-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px; flex: none;
    }
    .admin-dashboard-page .stat-body h6 { margin: 0; color: var(--color-grey); font-size: 13px; text-transform: uppercase; letter-spacing: .3px; font-weight: 600; }
    .admin-dashboard-page .stat-body h3 { margin: 2px 0 0; font-weight: 700; }

    /* ---- earnings ---- */
    .admin-dashboard-page .earning-card {
        border-radius: 10px;
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        height: 100%;
        color: #fff;
    }
    .admin-dashboard-page .earning-card h6 { margin: 0; font-size: 13px; text-transform: uppercase; letter-spacing: .3px; font-weight: 600; opacity: .9; }
    .admin-dashboard-page .earning-card h3 { margin: 2px 0 0; font-weight: 700; }
    .admin-dashboard-page .earning-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,0.2); font-size: 20px; flex: none;
    }
    .admin-dashboard-page .earning-card-total  { background: linear-gradient(135deg, #F2822E, #db7323); }
    .admin-dashboard-page .earning-card-admin  { background: linear-gradient(135deg, #2e93e8, #1c6fb8); }
    .admin-dashboard-page .earning-card-seller { background: linear-gradient(135deg, #3fa25c, #2c7a43); }

    /* ---- charts ---- */
    .admin-dashboard-page .chart-action { margin: 0; }
    .admin-dashboard-page .chart-action .nav-link { color: var(--color-grey); border-radius: 20px; padding: 4px 14px; font-size: 13px; }
    .admin-dashboard-page .chart-action .nav-link.active { background: var(--color-orange); color: #fff; }
    .admin-dashboard-page .ct-chart { height: 260px; }
    .admin-dashboard-page .piechart-height { height: 260px; }
    .admin-dashboard-page .chart-empty {
        height: 260px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        color: var(--color-grey);
    }
    .admin-dashboard-page .chart-empty i { font-size: 34px; opacity: .35; margin-bottom: 10px; }
    .admin-dashboard-page .chart-empty p { margin: 0; font-size: 14px; }
    .admin-dashboard-page .ct-series-a .ct-line,
    .admin-dashboard-page .ct-series-a .ct-point { stroke: var(--color-orange); }
    .admin-dashboard-page .ct-series-a .ct-area { fill: var(--color-orange); }

    /* ---- notices ---- */
    .admin-dashboard-page .dash-notice {
        border-radius: 10px;
        padding: 14px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        height: 100%;
    }
    .admin-dashboard-page .dash-notice h6 { margin: 0; font-weight: 600; }
    .admin-dashboard-page .dash-notice a { color: inherit; text-decoration: none; font-size: 13px; white-space: nowrap; }
    .admin-dashboard-page .dash-notice a:hover { text-decoration: underline; }
    .admin-dashboard-page .dash-notice-danger { background: #FBEAE8; color: #8a2f27; }
    .admin-dashboard-page .dash-notice-warning { background: #FBF3DC; color: #6b5100; }

    /* ---- seller stat boxes ---- */
    .admin-dashboard-page .seller-stat-box {
        width: 100%;
        border: none;
        border-radius: 10px;
        color: #fff;
        padding: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .admin-dashboard-page .seller-stat-box:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.15); }
    .admin-dashboard-page .seller-stat-box h3 { margin: 0; font-weight: 700; }
    .admin-dashboard-page .seller-stat-box p { margin: 0; font-size: 13px; opacity: .9; }
    .admin-dashboard-page .seller-stat-box i { font-size: 26px; opacity: .85; }

    /* ---- order outlines ---- */
    .admin-dashboard-page .order-outline-box {
        border-radius: 10px;
        color: #fff;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
        text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .admin-dashboard-page .order-outline-box:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.15); color: #fff; text-decoration: none; }
    .admin-dashboard-page .order-outline-box h3 { margin: 0; font-weight: 700; }
    .admin-dashboard-page .order-outline-box p { margin: 0; font-size: 13px; opacity: .9; }
    .admin-dashboard-page .order-outline-box i { font-size: 22px; opacity: .85; }

    /* ---- tables ---- */
    .admin-dashboard-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-dashboard-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-dashboard-page .fixed-table-toolbar .btn-group > .btn,
    .admin-dashboard-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-dashboard-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-dashboard-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-dashboard-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-dashboard-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-dashboard-page table.table thead th {
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
    .admin-dashboard-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-dashboard-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-dashboard-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-dashboard-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .modal .fixed-table-toolbar .search input { border-radius: 20px; }
</style>

<!-- Google Charts loader.
     The pie chart on this page called google.charts.* from assets/admin/custom/custom.js,
     but the loader script was never included anywhere in the admin layout, so
     "google is not defined" was thrown on every dashboard load and the chart area
     stayed permanently blank. Loading it here fixes that. -->
<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    // NOTE ON IDS
    // The legacy dashboard block in assets/admin/custom/custom.js is gated on
    // document.getElementById('piechart_3d') and #ecommerceChartView. Those ids are
    // deliberately not reused here (they are now #admin_piechart / #adminSalesChartView)
    // so the old, broken block short-circuits instead of firing a second set of AJAX
    // calls and then throwing. Everything the dashboard needs is self-contained below.

    function dashCurrencyFormatter(value) {
        if (value === null || value === undefined || value === '') return '0.00';
        var n = parseFloat(value);
        return isNaN(n) ? '0.00' : n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Refresh ONLY the orders table. The shared status_date_wise_search() in custom.js
    // does $('.table-striped').bootstrapTable('refresh'), which on this page matched
    // seven tables (orders, top sellers, top categories, order tracking and the three
    // seller modals) and fired seven simultaneous AJAX requests per filter click.
    function admin_home_filter() {
        $('#admin-orders-table').bootstrapTable('refresh');
    }

    function admin_home_reset_filters() {
        $('#datepicker').val('');
        $('#start_date').val('');
        $('#end_date').val('');
        $('#order_status').val('');
        $('#payment_method').val('');
        admin_home_filter();
    }

    function admin_home_query_params(p) {
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
        // Selecting a status/payment method applies the filter immediately rather than
        // needing a separate button click, matching the seller dashboard behaviour.
        $('#order_status, #payment_method').on('change', admin_home_filter);

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

        $('#datepicker').on('apply.daterangepicker', function (ev, picker) {
            $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
            $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
            admin_home_filter();
        });

        $('#datepicker').on('cancel.daterangepicker', function () {
            $(this).val('');
            $('#start_date').val('');
            $('#end_date').val('');
            admin_home_filter();
        });

        // The seller list tables live inside modals, which have zero width while hidden;
        // bootstrap-table then lays their columns out wrong. Resetting the view once the
        // modal is fully shown fixes the collapsed/misaligned columns.
        $('#approved_sellers, #not_approved_sellers, #deactive_sellers').on('shown.bs.modal', function () {
            $(this).find('table[data-toggle="table"]').bootstrapTable('resetView');
        });

        // ---- Category-wise product count (pie) ----
        if (document.getElementById('admin_piechart')) {
            $.ajax({
                url: "<?= base_url('admin/home/category_wise_product_count') ?>",
                type: 'GET',
                dataType: 'json',
                success: function (result) {
                    // result[0] is the header row. With no categories the chart library
                    // throws on a header-only table, so show an empty state instead.
                    if (!result || result.length < 2) {
                        $('#admin_piechart').addClass('d-none');
                        $('#adminPieEmpty').removeClass('d-none');
                        return;
                    }
                    google.charts.load('current', { packages: ['corechart'] });
                    google.charts.setOnLoadCallback(function () {
                        var data = google.visualization.arrayToDataTable(result);
                        var chart = new google.visualization.PieChart(document.getElementById('admin_piechart'));
                        chart.draw(data, {
                            title: '',
                            is3D: true,
                            chartArea: { width: '90%', height: '80%' },
                            legend: { position: 'right' }
                        });
                    });
                },
                error: function () {
                    $('#admin_piechart').addClass('d-none');
                    $('#adminPieEmpty').removeClass('d-none');
                }
            });
        }

        // ---- Product sales (Day / Week / Month) ----
        if (document.getElementById('adminSalesChartView')) {
            $.ajax({
                url: "<?= base_url('admin/home/fetch_sales') ?>",
                type: 'GET',
                dataType: 'json',
                success: function (result) {
                    var series = {
                        day:   { labels: (result[2] && result[2].day) || [],        data: (result[2] && result[2].total_sale) || [] },
                        week:  { labels: (result[1] && result[1].week) || [],       data: (result[1] && result[1].total_sale) || [] },
                        month: { labels: (result[0] && result[0].month_name) || [], data: (result[0] && result[0].total_sale) || [] }
                    };

                    if (!series.day.data.length && !series.week.data.length && !series.month.data.length) {
                        $('#adminSalesChartView').addClass('d-none');
                        $('#adminScoreLineToDay, #adminScoreLineToWeek, #adminScoreLineToMonth').addClass('d-none');
                        $('#adminSalesEmpty').removeClass('d-none');
                        return;
                    }

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
                            low: 0,
                            showArea: true
                        });
                        rendered[key] = true;
                    }

                    renderChart('day', 'adminScoreLineToDay');

                    // Render lazily on tab change. Chartist cannot measure a hidden pane,
                    // so drawing all three up-front produced squashed week/month charts.
                    $('#adminSalesChartView a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                        var map = {
                            '#adminScoreLineToDay': 'day',
                            '#adminScoreLineToWeek': 'week',
                            '#adminScoreLineToMonth': 'month'
                        };
                        var key = map[$(e.target).attr('href')];
                        if (key && !rendered[key]) {
                            renderChart(key, $(e.target).attr('href').substring(1));
                        }
                    });
                },
                error: function () {
                    $('#adminSalesChartView').addClass('d-none');
                    $('#adminScoreLineToDay, #adminScoreLineToWeek, #adminScoreLineToMonth').addClass('d-none');
                    $('#adminSalesEmpty').removeClass('d-none');
                }
            });
        }
    });
</script>
