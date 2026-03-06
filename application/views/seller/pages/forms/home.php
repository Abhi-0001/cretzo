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
                            <a href="<?= base_url('seller/subscription/manage_subscriptions'); ?>" class="btn btn-sm btn-outline-dark">
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
                            <button type="button" class="btn btn-sm btn-light" data-toggle="modal" data-target="#subscription_modal">
                                Renew / Upgrade
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

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

    <?php if (isset($show_subscription_popup) && $show_subscription_popup && !empty($subscription_plans)) : ?>
        <div class="modal fade" id="subscription_modal" tabindex="-1" role="dialog" aria-labelledby="subscriptionModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="subscriptionModalLabel">
                            <?php if (isset($subscription_status) && $subscription_status === 'expired') : ?>
                                Your subscription has expired
                            <?php else : ?>
                                Choose a Subscription Plan
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p class="mb-4">
                            <?php if (isset($subscription_status) && $subscription_status === 'expired') : ?>
                                Your previous subscription is no longer active. Please renew your current plan or upgrade to continue using your seller dashboard.
                            <?php else : ?>
                                You currently do not have an active subscription. Please select a plan to continue using your seller dashboard.
                            <?php endif; ?>
                        </p>
                        <div class="row">
                            <?php
                            $current_price_value = 0;
                            if (!empty($current_subscription_plan['price'])) {
                                $clean = preg_replace('/[^\d\.]/', '', $current_subscription_plan['price']);
                                $current_price_value = is_numeric($clean) ? (float) $clean : 0;
                            }
                            foreach ($subscription_plans as $plan) :
                                $is_current = isset($current_subscription_plan['id']) && (int) $current_subscription_plan['id'] === (int) $plan['id'];
                                $plan_price_value = 0;
                                if (!empty($plan['price'])) {
                                    $p_clean = preg_replace('/[^\d\.]/', '', $plan['price']);
                                    $plan_price_value = is_numeric($p_clean) ? (float) $p_clean : 0;
                                }

                                $btn_label = 'Choose Plan';
                                $btn_class = 'btn-warning';
                                $btn_disabled = false;

                                if (isset($subscription_status) && $subscription_status === 'active') {
                                    if ($is_current) {
                                        $btn_label = 'Current Plan';
                                        $btn_class = 'btn-secondary';
                                        $btn_disabled = true;
                                    } elseif ($current_price_value > 0 && $plan_price_value > 0 && $plan_price_value < $current_price_value) {
                                        $btn_label = 'Downgrade Not Allowed';
                                        $btn_class = 'btn-outline-secondary';
                                        $btn_disabled = true;
                                    } else {
                                        $btn_label = 'Upgrade';
                                        $btn_class = 'btn-warning';
                                        $btn_disabled = false;
                                    }
                                } elseif (isset($subscription_status) && $subscription_status === 'expired' && $is_current) {
                                    $btn_label = 'Renew Plan';
                                    $btn_class = 'btn-warning';
                                    $btn_disabled = false;
                                }
                            ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 border-warning">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-warning"><?= html_escape($plan['name']); ?></h5>
                                            <?php if (!empty($plan['price'])) : ?>
                                                <h4 class="font-weight-bold mb-2"><?= html_escape($plan['price']); ?></h4>
                                            <?php endif; ?>
                                            <?php if (!empty($plan['listings_limit'])) : ?>
                                                <p class="mb-1"><strong>Listings:</strong> <?= html_escape($plan['listings_limit']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($plan['validity'])) : ?>
                                                <p class="mb-3"><strong>Validity:</strong> <?= html_escape($plan['validity']); ?></p>
                                            <?php endif; ?>
                                            <button type="button"
                                                    class="btn <?= $btn_class; ?> btn-sm purchase-plan-btn"
                                                    data-id="<?= (int) $plan['id']; ?>"
                                                    <?= $btn_disabled ? 'disabled' : ''; ?>>
                                                <?= $btn_label; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="subscription_error_box" class="text-danger text-center mt-2"></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                $('#subscription_modal').modal('show');

                $('.purchase-plan-btn').on('click', function() {
                    var planId = $(this).data('id');
                    var $btn = $(this);
                    $('#subscription_error_box').text('');
                    $btn.prop('disabled', true);

                    $.ajax({
                        url: '<?= base_url('seller/subscription/purchase'); ?>',
                        type: 'POST',
                        data: {
                            subscription_id: planId,
                            '<?= $this->security->get_csrf_token_name(); ?>': '<?= $this->security->get_csrf_hash(); ?>'
                        },
                        success: function(response) {
                            $btn.prop('disabled', false);
                            try {
                                var res = (typeof response === 'string') ? JSON.parse(response) : response;
                                if (res.error === false) {
                                    $('#subscription_modal').modal('hide');
                                    location.reload();
                                } else {
                                    $('#subscription_error_box').text(res.message || 'Unable to purchase subscription.');
                                }
                            } catch (e) {
                                $('#subscription_error_box').text('Unexpected server response.');
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false);
                            $('#subscription_error_box').text('Failed to contact server. Please try again.');
                        }
                    });
                });
            });
        </script>
    <?php endif; ?>

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