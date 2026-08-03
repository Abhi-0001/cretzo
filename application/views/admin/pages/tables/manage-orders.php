<div class="content-wrapper admin-manage-orders-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-receipt mr-2 text-primary-theme"></i>Manage Orders</h4>
                    <p class="text-muted mb-0 small">Every order across the marketplace, its items, and their fulfilment.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Orders</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <!-- ===== Digital order mails modal ===== -->
            <div id="digital-order-mails" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="digitalOrderMailsLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="digitalOrderMailsLabel">Digital Order Mails</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="order_id" id="order_id">
                            <input type="hidden" name="order_item_id" id="order_item_id">
                            <table class='table-striped' id="digital_order_mail_table" data-toggle="table"
                                data-url="<?= base_url('admin/orders/get-digital-order-mails') ?>" data-click-to-select="true"
                                data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                                data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                                data-show-export="true" data-maintain-selected="true" data-query-params="digital_order_mails_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="order_id" data-sortable="true">Order ID</th>
                                        <th data-field="order_item_id" data-sortable="false">Order Item ID</th>
                                        <th data-field="subject" data-sortable="false">Subject</th>
                                        <th data-field="message" data-sortable="false" data-visible="false">Message</th>
                                        <th data-field="file_url" data-sortable="false">URL</th>
                                        <th data-field="date_added" data-sortable="false" data-visible="false">Date</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Send digital product modal ===== -->
            <div id="ManageOrderSendMailModal" class="modal fade editSendMail" tabindex="-1" role="dialog" aria-labelledby="sendMailLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="sendMailLabel">Send Digital Product</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form class="form-horizontal form-submit-event" action="<?= base_url('admin/orders/send_digital_product'); ?>" method="POST" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php
                                    // These three fields used to be pre-filled here from $order_item_data /
                                    // $user_data, which only ever existed when the page URL happened to contain
                                    // ?edit_id=... - something nothing on this page ever set. In practice this
                                    // meant order_id and order_item_id were always blank: submitting "Send Mail"
                                    // updated no order's status, recorded a mail-sent row linked to no real
                                    // order, and greeted the customer with an empty name, all while reporting
                                    // "Mail sent successfully." Every visit to this page also raised five raw
                                    // PHP warnings ("Undefined variable $order_item_data" / "$user_data") from
                                    // these exact lines - invisible only because Order_model disabled PHP error
                                    // reporting for the rest of the request (see the top of that file).
                                    // The .sendMailBtn click handler now fills these three fields correctly from
                                    // the specific row that was actually clicked.
                                    ?>
                                    <input type="hidden" name="order_id" id="send_mail_order_id" value="">
                                    <input type="hidden" name="order_item_id" id="send_mail_order_item_id" value="">
                                    <input type="hidden" name="username" id="send_mail_username" value="">
                                    <div class="form-group">
                                        <label>Customer Email-ID</label>
                                        <input type="text" class="form-control ManageOrderEmail" id="email" name="email" value="" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Subject</label>
                                        <input type="text" class="form-control" id="subject" placeholder="Enter Subject for email" name="subject" value="">
                                    </div>
                                    <div class="form-group">
                                        <label>Message</label>
                                        <textarea class="textarea form-control" placeholder="Message for Email" name="message"></textarea>
                                    </div>
                                    <div class="form-group mb-0" id="digital_media_container">
                                        <label>File <span class='text-danger text-sm'>*</span></label>
                                        <div><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='pro_input_file' data-isremovable='1' data-media_type='archive,document' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                        <div class="container-fluid row image-upload-section">
                                            <div class="col-md-6 col-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none"></div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary-theme mt-3" id="submit_btn"><?= labels('send_mail', 'Send Mail') ?></button>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Assign order tracking modal ===== -->
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
                            <form class="form-horizontal" id="order_tracking_form" action="<?= base_url('admin/orders/update-order-tracking/'); ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" id="order_id">
                                <input type="hidden" name="order_item_id" id="order_item_id">
                                <input type="hidden" name="seller_id" id="seller_id">
                                <div class="card-body pad">
                                    <div class="form-group">
                                        <label for="courier_agency">Courier Agency</label>
                                        <input type="text" class="form-control" name="courier_agency" id="courier_agency" placeholder="Courier Agency" />
                                    </div>
                                    <div class="form-group">
                                        <label for="tracking_id">Tracking Id</label>
                                        <input type="text" class="form-control" name="tracking_id" id="tracking_id" placeholder="Tracking Id" />
                                    </div>
                                    <div class="form-group">
                                        <label for="url">URL</label>
                                        <input type="text" class="form-control" name="url" id="url" placeholder="URL" />
                                    </div>
                                    <div class="form-group mb-0">
                                        <button type="reset" class="btn btn-light border">Reset</button>
                                        <button type="submit" class="btn btn-primary-theme" id="submit_btn">Save</button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== View order tracking modal ===== -->
            <div class="modal fade" id="order-tracking-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl">
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
                                data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
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

            <!-- ===== Order Outlines ===== -->
            <h5 class="section-title">Order Outlines</h5>
            <div class="row mb-2">
                <div class="col-6 col-md-2 mb-3">
                    <div class="order-outline-box" style="background:#8e6bbf">
                        <div><h3><?= $status_counts['awaiting'] ?></h3><p>Awaiting</p></div>
                        <i class="fas fa-history"></i>
                    </div>
                </div>
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

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-receipt"></i></span>
                        <h5 class="mb-0">Orders</h5>
                    </div>
                    <a href="#" class="btn btn-outline-primary-theme btn-sm add_promo_code_discount" title="If you found Promo Code Discount not crediting using cron job you can update Promo Code Discount from here!">
                        <i class="fas fa-hand-holding-usd mr-1"></i>Settle Promo Code Discount
                    </a>
                </div>
                <div class="card-body">
                    <div class="product-filters-bar row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="filter-label"><i class="far fa-clock mr-1"></i>Date Range</label>
                            <input type="text" class="form-control" id="datepicker" placeholder="Select Date Range To Filter" autocomplete="off">
                            <input type="hidden" id="start_date">
                            <input type="hidden" id="end_date">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="filter-label"><i class="fas fa-toggle-on mr-1"></i>Status</label>
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
                        <div class="col-md-3 mb-2">
                            <label class="filter-label"><i class="fas fa-credit-card mr-1"></i>Payment Method</label>
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
                                <option value="midtrans">Midtrans</option>
                                <option value="my_fatoorah">My Fatoorah</option>
                                <option value="instamojo">Instamojo</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="filter-label"><i class="fas fa-tag mr-1"></i>Product Type</label>
                            <select id="order_type" name="order_type" class="form-control">
                                <option value="">All Orders</option>
                                <option value="physical_order">Physical Orders</option>
                                <option value="digital_order">Digital Orders</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="button" class="btn btn-primary-theme btn-block" onclick="status_date_wise_search()">Filter</button>
                        </div>
                    </div>

                    <input type='hidden' id='order_user_id' value='<?= (isset($_GET['user_id']) && !empty($_GET['user_id'])) ? html_escape($_GET['user_id']) : '' ?>'>
                    <input type='hidden' id='order_seller_id' value='<?= (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) ? html_escape($_GET['seller_id']) : '' ?>'>

                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#orders_table">Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#order_items_table">Order Items</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div id="orders_table" class="tab-pane active pt-3">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/orders/view_orders') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="o.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "orders-list","ignoreColumn": ["state"] }' data-query-params="orders_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable='true' data-footer-formatter="totalFormatter">Order ID</th>
                                        <th data-field="user_id" data-sortable='true' data-visible="false">User ID</th>
                                        <th data-field="qty" data-sortable='true' data-visible="false">Qty</th>
                                        <th data-field="name" data-sortable='true'>User Name</th>
                                        <th data-field="sellers" data-sortable='true'>Sellers</th>
                                        <th data-field="mobile" data-sortable='true' data-visible='false'>Mobile</th>
                                        <th data-field="notes" data-sortable='false' data-visible='true'>O. Notes</th>
                                        <th data-field="items" data-sortable='true' data-visible="false">Items</th>
                                        <th data-field="total" data-sortable='true' data-visible="true">Total(<?= $curreny ?>)</th>
                                        <th data-field="delivery_charge" data-sortable='true' data-footer-formatter="delivery_chargeFormatter">D.Charge</th>
                                        <th data-field="wallet_balance" data-sortable='true' data-visible="true">Wallet Used(<?= $curreny ?>)</th>
                                        <th data-field="promo_code" data-sortable='true' data-visible="false">Promo Code</th>
                                        <th data-field="promo_discount" data-sortable='true' data-visible="true">Promo disc.(<?= $curreny ?>)</th>
                                        <th data-field="final_total" data-sortable='true'>Final Total(<?= $curreny ?>)</th>
                                        <th data-field="payment_method" data-sortable='true' data-visible="true">Payment Method</th>
                                        <th data-field="address" data-sortable='true' data-visible='false'>Address</th>
                                        <th data-field="delivery_date" data-sortable='true' data-visible='false'>Delivery Date</th>
                                        <th data-field="delivery_time" data-sortable='true' data-visible='false'>Delivery Time</th>
                                        <th data-field="date_added" data-sortable='true'>Order Date</th>
                                        <th data-field="operate">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div id="order_items_table" class="tab-pane fade pt-3">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/orders/view_order_items') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="oi.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "order-item-list","ignoreColumn": ["state"] }' data-query-params="orders_query_params">
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
                                        <th data-field="mobile" data-sortable='true' data-visible='false'>Mobile</th>
                                        <th data-field="sub_total" data-sortable='true' data-visible="true">Total(<?= $curreny ?>)</th>
                                        <th data-field="delivery_boy" data-sortable='true' data-visible='false'>Deliver By</th>
                                        <th data-field="delivery_boy_id" data-sortable='true' data-visible='false'>Delivery Boy Id</th>
                                        <th data-field="product_variant_id" data-sortable='true' data-visible='false'>Product Variant Id</th>
                                        <th data-field="delivery_date" data-sortable='true' data-visible='false'>Delivery Date</th>
                                        <th data-field="delivery_time" data-sortable='true' data-visible='false'>Delivery Time</th>
                                        <th data-field="updated_by" data-sortable='true' data-visible="false">Updated by</th>
                                        <th data-field="status" data-sortable='true' data-visible='false'>Status</th>
                                        <th data-field="active_status" data-sortable='true' data-visible='true'>Active Status</th>
                                        <th data-field="transaction_status" data-sortable='true' data-visible='false'>Transaction Status</th>
                                        <th data-field="date_added" data-sortable='true'>Order Date</th>
                                        <th data-field="operate">Action</th>
                                        <th data-field="mail_status">Mail Status</th>
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

<style>
    .admin-manage-orders-page .text-primary-theme { color: var(--color-orange); }
    .admin-manage-orders-page .section-title { margin: 6px 0 14px; font-weight: 600; }

    .admin-manage-orders-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
        font-size: 14px;
    }
    .admin-manage-orders-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .admin-manage-orders-page .btn-outline-primary-theme {
        border: 1px solid var(--color-orange);
        color: var(--color-orange-dark);
        font-weight: 600;
        background: #fff;
    }
    .admin-manage-orders-page .btn-outline-primary-theme:hover { background: var(--color-orange); color: #fff; }

    .admin-manage-orders-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-orders-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-orders-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-orders-page .header-icon.bg-set,
    .admin-manage-orders-page .order-outline-box.bg-set { background: var(--color-orange); }

    .admin-manage-orders-page .order-outline-box {
        border-radius: 10px;
        color: #fff;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
    }
    .admin-manage-orders-page .order-outline-box h3 { margin: 0; font-weight: 700; }
    .admin-manage-orders-page .order-outline-box p { margin: 0; font-size: 13px; opacity: .9; }
    .admin-manage-orders-page .order-outline-box i { font-size: 22px; opacity: .85; }

    .admin-manage-orders-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .admin-manage-orders-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .admin-manage-orders-page .filter-label i { color: var(--color-orange); }
    .admin-manage-orders-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }

    .admin-manage-orders-page .nav-tabs .nav-link.active {
        color: var(--color-orange-dark);
        border-bottom: 2px solid var(--color-orange);
        font-weight: 600;
    }

    .admin-manage-orders-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-orders-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-orders-page .fixed-table-toolbar .btn-group > .btn,
    .admin-manage-orders-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-manage-orders-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-manage-orders-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-manage-orders-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-orders-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-orders-page table.table thead th {
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
    .admin-manage-orders-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-orders-page table.table tbody tr:hover { background-color: var(--color-orange-light); }

    /* bootstrap-table doesn't stamp data-field onto the rendered <td>, only onto the <th>, so
       :has(.action-btn) targets the operate cell regardless of column position - every action
       button rendered by this page carries that class. Without this, the action icons (plain
       inline links) wrapped onto their own line each once there wasn't quite enough width for
       all of them side by side, stacking vertically instead of sitting in a row. */
    .admin-manage-orders-page td:has(.action-btn) {
        white-space: nowrap;
    }
    .admin-manage-orders-page .action-btn {
        display: inline-block;
        vertical-align: middle;
    }
    .admin-manage-orders-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-orders-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-orders-page .modal-header { border-bottom: 2px solid var(--color-secondary); }
</style>
