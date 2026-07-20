<style>
    /* /Issue Seller Dashboard horizontal animateProgressBar 
    Pulse animation for the progress bar container */
    @keyframes barPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(242, 140, 40, 0.30); }
        50% { box-shadow: 0 0 0 8px rgba(242, 140, 40, 0); }
    }

    .progress {
        height: 7px;
        border-radius: 10px;
        background-color: #e9ecef;
        overflow: visible; /* Allows the pulse shadow to be seen */
        animation: barPulse 1.8s ease-in-out infinite;
    }

    .progress-bar {
        border-radius: 10px;
        transition: none;
        background-color: #f28c28 !important;
        position: relative;
    }

    /* Optional: adds a shine effect to the bar */
    .progress-bar::after {
        content: '';
        position: absolute;
        top: 0; left: 0; bottom: 0; right: 0;
        background-image: linear-gradient(
            45deg, 
            rgba(255,255,255,.15) 25%, 
            transparent 25%, 
            transparent 50%, 
            rgba(255,255,255,.15) 50%, 
            rgba(255,255,255,.15) 75%, 
            transparent 75%, 
            transparent
        );
        background-size: 1rem 1rem;
    }

    .profile-completion-content {
        flex: 1;
        min-width: 260px;
    }
</style>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid p-3">
            <div class="row pt-4">
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <div class="card pull-up">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="media d-flex">
                                    <div class="align-self-center text-danger">
                                    <i class="ion-ios-cart-outline display-4"></i>
                                    </div>
                                    <div class="media-body text-right">
                                        <h5 class="text-muted text-bold-500">Orders</h5>
                                        <h3 class="text-bold-600"><?= $order_counter ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <div class="card pull-up">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="media d-flex">
                                    <div class="align-self-center text-info">
                                        <i class="ion-ios-albums-outline display-4 display-4"></i>
                                    </div>
                                    <div class="media-body text-right">
                                        <h5 class="text-muted text-bold-500">Products</h5>
                                        <h3 class="text-bold-600"><?= $products ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <div class="card pull-up">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="media d-flex">
                                    <div class="align-self-center text-warning">
                                        <i class="ion-ios-star-outline display-4 display-4"></i>
                                    </div>
                                    <div class="media-body text-right">
                                        <h5 class="text-muted text-bold-500">Rating</h5>
                                        <h3 class="text-bold-600"><?= intval($ratings[0]['rating']) . "/" . $ratings[0]['no_of_ratings']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <div class="card pull-up">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="media d-flex">
                                    <div class="align-self-center text-success">
                                        <i class="ion-cash display-4"></i>
                                    </div>
                                    <div class="media-body text-right">
                                        <h5 class="text-muted text-bold-500">Balance (<?= $curreny?>)</h5>
                                        <h3 class="text-bold-600"><?= number_format($balance, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                  <?php if (isset($subscription_status) && $subscription_status === 'active' && !empty($current_subscription_plan)) : ?>
                    <div class="col-12">
                        <div class="alert alert-warning d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Current Plan:</strong>
                                <?= html_escape($current_subscription_plan['name']); ?>
                                <?php if (!empty($current_subscription_plan['validity'])) : ?>
                                    <span class="ml-2 badge badge-light"><?= html_escape($current_subscription_plan['validity']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($active_subscription['end_date'])) : ?>
                                    <span class="ml-2 text-sm text-muted">
                                        (Valid till <?= date('d M Y', strtotime($active_subscription['end_date'])); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <a href="<?= base_url('seller/subscription/manage_subscriptions'); ?>" class="btn btn-sm btn-dark">
                                Manage / Upgrade Plan
                            </a>
                        </div>
                    </div>
                <?php elseif (isset($subscription_status) && $subscription_status === 'expired' && !empty($current_subscription_plan)) : ?>
                    <div class="col-12">
                        <div class="alert alert-danger d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Subscription expired:</strong>
                                <?= html_escape($current_subscription_plan['name']); ?>
                                <?php if (!empty($subscription_expired_on)) : ?>
                                    <span class="ml-2">
                                        (Expired on <?= html_escape($subscription_expired_on); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <a href="<?= base_url('seller/subscription/manage_subscriptions'); ?>" class="btn btn-sm btn-light">
                                Renew / Upgrade
                            </a>
                        </div>
                    </div>
                <?php elseif (isset($subscription_status) && $subscription_status === 'none') : ?>
                    <div class="col-12">
                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                            <div>
                                <strong>No active subscription:</strong>
                                You don't have a plan yet. Choose a subscription to start selling.
                            </div>
                            <a href="<?= base_url('seller/subscription/manage_subscriptions'); ?>" class="btn btn-sm btn-dark">
                                Choose a Plan
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <div class="card pull-up">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="text-muted text-bold-500 mb-0">
                                            Profile Completion: <span id="percent-text">0</span>%
                                        </h5>
                                        <a href="<?= base_url('seller/home/profile') ?>" class="btn btn-sm btn-outline-primary">Update Profile</a>
                                    </div>
                                    <div class="progress">
                                        <div id="profile-bar" 
                                             class="progress-bar" 
                                             role="progressbar" 
                                             style="width: 0%;"
                                             data-target-width="<?= (int)($profile_completion['percentage'] ?? 0) ?>"
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                          
                        <?php if (!empty($profile_completion['missing_sections'])): ?>
                            <hr class="mt-2 mb-3">
                            <p class="mb-2 text-muted small">Complete your profile to start selling.</p>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($profile_completion['missing_sections'] as $section): ?>
                                    <li class="d-flex justify-content-between align-items-center mb-2">
                                        <span><?= $section['label'] ?></span>
                                        <a href="<?= $section['link'] ?>" class="btn btn-sm btn-primary">Complete</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mb-0 text-success"><i class="fa fa-check-circle"></i> Your seller profile is complete.</p>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-12" id="ecommerceChartView">
                    <div class="card card-shadow chart-height">
                        <div class="m-3">Product Sales</div>
                        <div class="card-header card-header-transparent py-20 border-0">
                            <ul class="nav nav-pills nav-pills-rounded chart-action float-right btn-group sales-tab" role="group">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#scoreLineToDay">Day</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#scoreLineToWeek">Week</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#scoreLineToMonth">Month</a></li>
                            </ul>
                        </div>
                        <div class="widget-content tab-content bg-white p-20">
                            <div class="ct-chart tab-pane active scoreLineShadow" id="scoreLineToDay"></div>
                            <div class="ct-chart tab-pane scoreLineShadow" id="scoreLineToWeek"></div>
                            <div class="ct-chart tab-pane scoreLineShadow" id="scoreLineToMonth"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Category Wise Product's Sales -->
                    <div class="card ">
                        <h3 class="card-title m-3">Category Wise Product's Count</h3>
                        <div class="card-body">
                            <div id="piechart_3d" class='piechat_height'></div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card --> 
                </div>
                <div class="col-md-6 col-xs-12">
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h6><i class="icon fa fa-info"></i> <?= $count_products_availability_status ?> Product(s) sold out!</h6>
                        <a href="<?= base_url('seller/product/?flag=sold') ?>" class="text-decoration-none small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <?php $settings = get_settings('system_settings', true); ?>
                <div class="col-md-6 col-xs-12">
                    <div class="alert alert-primary alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h6><i class="icon fa fa-info"></i> <?= $count_products_low_status ?> Product(s) low in stock!<small> (Low stock limit <?= isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : '5' ?>)</small></h6>
                        <a href="<?= base_url('seller/product/?flag=low') ?>" class="text-decoration-none small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <h5 class="col">Order Outlines</h5>
                <div class="row col-12 d-flex">
                    <div class="col-3">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3><?= $status_counts['received'] ?></h3>
                                <p>Received</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-xs fa-level-down-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= $status_counts['processed'] ?></h3>
                                <p>Processed</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-xs fa-people-carry"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="small-box details-box">
                            <div class="inner">
                                <h3><?= $status_counts['shipped'] ?></h3>
                                <p>Shipped</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-xs fa-shipping-fast"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= $status_counts['delivered'] ?></h3>
                                <p>Delivered</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-xs fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?= $status_counts['cancelled'] ?></h3>
                                <p>Cancelled</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-xs fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3><?= $status_counts['returned'] ?></h3>
                                <p>Returned</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-xs fa-level-up-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 main-content">
                    <div class="card content-area p-4">
                        <div class="card-innr">
                            <div class="gaps-1-5x row d-flex adjust-items-center">
                                <div class="row col-md-12">
                                    <div class="form-group col-md-4">
                                        <label>Date range:</label>
                                        <div class="input-group col-md-12">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                            <input type="text" class="form-control float-right" id="datepicker">
                                            <input type="hidden" id="start_date" class="form-control float-right">
                                            <input type="hidden" id="end_date" class="form-control float-right">
                                        </div>
                                        <!-- /.input group -->
                                    </div>
                                    <div class="form-group col-md-4">
                                        <div>
                                            <label>Filter By status</label>
                                            <select id="order_status" name="order_status" placeholder="Select Status" required="" class="form-control">
                                                <option value="">All Orders</option>
                                                <option value="received">Received</option>
                                                <option value="processed">Processed</option>
                                                <option value="shipped">Shipped</option>
                                                <option value="delivered">Delivered</option>
                                                <option value="cancelled">Cancelled</option>
                                                <option value="returned">Returned</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filter By payment  -->
                                    <div class="form-group col-md-3">
                                        <div>
                                            <label>Filter By Payment Method</label>
                                            <select id="payment_method" name="payment_method" placeholder="Select Payment Method" required="" class="form-control">
                                                <option value="">All Payment Methods</option>
                                                <option value="COD">Cash On Delivery</option>
                                                <option value="Paypal">Paypal</option>
                                                <option value="RazorPay">RazorPay</option>
                                                <option value="Paystack">Paystack</option>
                                                <option value="Flutterwave">Flutterwave</option>`
                                                <option value="Paytm">Paytm</option>
                                                <option value="Stripe">Stripe</option>
                                                <option value="bank_transfer">Direct Bank Transfers</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-1 d-flex align-items-center pt-4">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="status_date_wise_search()">Filter</button>
                                    </div>
                                </div>
                            </div>
                            <table class='table-striped fixed-row-height' data-toggle="table" data-url="<?= base_url('seller/orders/view_order_items') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="o.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "order-items-list","ignoreColumn": ["state"] }' data-query-params="orders_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="operate">Action</th>
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
                        </div><!-- .card-innr -->
                    </div><!-- .card -->
                </div>
            </div>
        </div>
    </section>
    <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="transaction_modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="user_name">Order Tracking</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-info">
                                <!-- form start -->
                                <form class="form-horizontal " id="order_tracking_form" action="<?= base_url('seller/orders/update-order-tracking/'); ?>" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="order_id" id="order_id">
                                    <input type="hidden" name="order_item_id" id="order_item_id">
                                    <div class="card-body pad">
                                        <div class="form-group ">
                                            <label for="courier_agency">Courier Agency</label>
                                            <input type="text" class="form-control" name="courier_agency" id="courier_agency" placeholder="Courier Agency" />
                                        </div>
                                        <div class="form-group ">
                                            <label for="tracking_id">Tracking Id</label>
                                            <input type="text" class="form-control" name="tracking_id" id="tracking_id" placeholder="Tracking Id" />
                                        </div>
                                        <div class="form-group ">
                                            <label for="url">URL</label>
                                            <input type="text" class="form-control" name="url" id="url" placeholder="URL" />
                                        </div>
                                        <div class="form-group">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-success" id="submit_btn">Save</button>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <div class="form-group" id="error_box">
                                        </div>
                                    </div>
                                    <!-- /.card-body -->
                                </form>
                            </div>
                            <!--/.card-->
                        </div>
                        <!--/.col-md-12-->
                    </div>
                    <!-- /.row -->

                </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var progressBar = document.getElementById('profile-bar');
    var percentText = document.getElementById('percent-text');

    if (!progressBar) return;

    var target = parseInt(progressBar.getAttribute('data-target-width') || '0', 10);
    target = Math.max(0, Math.min(100, target));

    var duration = 1000; // 2 seconds
    var startTime = null;

    function animate(timestamp) {
        if (!startTime) startTime = timestamp;

        var progress = Math.min((timestamp - startTime) / duration, 1);

        var current = Math.floor(progress * target);

        progressBar.style.width = current + '%';
        percentText.textContent = current;

        if (current > 70) {
            progressBar.style.backgroundColor = '#28a745';
        } else if (current > 40) {
            progressBar.style.backgroundColor = '#ffc107';
        }

        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    }

    requestAnimationFrame(animate);

})();
</script>