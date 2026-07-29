<div class="content-wrapper manage-orders-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-receipt mr-2 text-primary-theme"></i>Manage Orders</h4>
                    <p class="text-muted mb-0 small">Track, filter, and fulfil orders for your products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Orders</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <!-- modal for show digital order mails -->
            <div id="digital-order-mails" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="digitalOrderMailsModalTitle">Digital Order Mails</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body ">
                            <input type="hidden" name="order_id" id="digital_mails_order_id">
                            <input type="hidden" name="order_item_id" id="digital_mails_order_item_id">
                            <table class='table-striped' id="digital_order_mail_table" data-toggle="table" data-url="<?= base_url('seller/orders/get-digital-order-mails') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="digital_order_mails_query_params">
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
                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="digitalOrderMailsErrorBox">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- modal for send mail for digital orders -->
            <div id="ManageOrderSendMailModal" class="modal fade editSendMail " tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="manageDigitalProductModalTitle">Manage Digital Product</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body ">
                            <form class="form-horizontal" id="digital_product_management" action="<?= base_url('seller/orders/send_digital_product'); ?>" method="POST" enctype="multipart/form-data">
                                <div class="card-body">
                                    <input type="hidden" name="order_id" value="<?= $order_item_data[0]['order_id'] ?? '' ?>">
                                    <input type="hidden" name="order_item_id" id="send_mail_order_item_id" value="<?= $this->input->get('edit_id') ?>">
                                    <input type="hidden" name="username" value="<?= $user_data['username'] ?? '' ?>">
                                    <div class="row form-group">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="product_name">Customer Email-ID </label>
                                                <input type="text" class="form-control ManageOrderEmail" id="email" name="email" value="" readonly>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="product_name">Subject </label>
                                                <input type="text" class="form-control" id="subject" placeholder="Enter Subject for email" name="subject" value="">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="product_name">Message </label>
                                                <textarea type="text" class="form-control textarea" rows="6" id="message" placeholder="Message for Email" name="message"><?= isset($product_details[0]['short_description']) ? output_escaping(str_replace('\r\n', '&#13;&#10;', $product_details[0]['short_description'])) : ""; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-2" id="digital_media_container">
                                            <label for="image" class="ml-2">File <span class='text-danger text-sm'>*</span></label>
                                            <div class='col-md-6'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='pro_input_file' data-isremovable='1' data-media_type='archive,document' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                            <div class="container-fluid row image-upload-section">
                                                <div class="col-md-6 col-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary-theme mt-3" id="send_mail_submit_btn"><?= labels('send_mail', 'Send Mail') ?></button>
                                </div>
                            </form>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="manageDigitalProductErrorBox">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- modal for assign tracking data for order -->
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
                            <form class="form-horizontal" id="order_tracking_form" action="<?= base_url('seller/orders/update_order_tracking'); ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" id="tracking_order_id">
                                <input type="hidden" name="order_item_id" id="tracking_order_item_id">
                                <input type="hidden" name="seller_id" id="tracking_seller_id">
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
                                        <button type="reset" class="btn btn-light border">Reset</button>
                                        <button type="submit" class="btn btn-primary-theme" id="tracking_submit_btn">Save</button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="orderTrackingErrorBox">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-receipt"></i></span>
                    <h5 class="mb-0">Orders</h5>
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

                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/orders/view_order_items') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="o.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "orders-list","ignoreColumn": ["state"] }' data-query-params="orders_query_params">
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
                                <th data-field="seller_name" data-sortable='true' data-visible="false">Seller Name</th>
                                <th data-field="product_name" data-sortable='true'>Product Name</th>
                                <th data-field="mobile" data-sortable='true' data-visible='false'>Mobile</th>
                                <th data-field="notes" data-sortable='true' data-visible='false'>Order Note</th>
                                <th data-field="sub_total" data-sortable='true' data-visible="true">Total(<?= $curreny ?>)</th>
                                <th data-field="payment_method" data-sortable='true' data-visible='false'>Payment Method</th>
                                <th data-field="delivery_boy" data-sortable='true' data-visible='false'>Deliver By</th>
                                <th data-field="delivery_boy_id" data-sortable='true' data-visible='false'>Delivery Boy Id</th>
                                <th data-field="product_variant_id" data-sortable='true' data-visible='false'>Product Variant Id</th>
                                <th data-field="delivery_date" data-sortable='true' data-visible='false'>Delivery Date</th>
                                <th data-field="delivery_time" data-sortable='true' data-visible='false'>Delivery Time</th>
                                <th data-field="updated_by" data-sortable='true' data-visible="true">Updated by</th>
                                <th data-field="status" data-sortable='true' data-visible='false'>Status</th>
                                <th data-field="active_status" data-sortable='true' data-visible='true'>Active Status</th>
                                <th data-field="transaction_status" data-sortable='true' data-visible='true'>Transaction Status</th>
                                <th data-field="date_added" data-sortable='true'>Order Date</th>
                                <th data-field="operate">Action</th>
                                <th data-field="mail_status">Mail Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .manage-orders-page .text-primary-theme { color: var(--color-orange); }

    .manage-orders-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .manage-orders-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .manage-orders-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .manage-orders-page .header-icon.bg-set { background: var(--color-orange); }

    .manage-orders-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .manage-orders-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .manage-orders-page .filter-label i { color: var(--color-orange); }

    .manage-orders-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .manage-orders-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
        border-radius: 6px;
    }
    .manage-orders-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .manage-orders-page .fixed-table-toolbar { margin-bottom: 10px; }
    .manage-orders-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .manage-orders-page .fixed-table-toolbar .btn-group > .btn,
    .manage-orders-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .manage-orders-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .manage-orders-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .manage-orders-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .manage-orders-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .manage-orders-page table.table thead th {
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
    .manage-orders-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .manage-orders-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .manage-orders-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .manage-orders-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .manage-orders-page .modal-header { border-bottom: 2px solid var(--color-secondary); }
</style>

<script>
    // None of this page's filter bar, tracking modal, send-mail modal, or digital-mails
    // modal were wired up for sellers before now — the JS that implements them
    // (status_date_wise_search / orders_query_params / .edit_order_tracking /
    // .sendMailBtn / .edit_digital_order_mails / the daterangepicker init) only ever
    // shipped in the admin bundle, which isn't loaded on seller pages. The order-tracking
    // form in particular had NO submit handler at all — submitting it did a full,
    // non-AJAX page reload that dumped raw JSON to the screen. Everything below is
    // self-contained to this page.

    // moment/daterangepicker are loaded by seller/include-script.php at the very bottom of
    // <body>, after this inline script — calling .daterangepicker() immediately throws
    // "moment is not defined". Deferred to document.ready so it runs after they exist.
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

    function status_date_wise_search() {
        $('.manage-orders-page .table-striped').bootstrapTable('refresh');
    }

    function orders_query_params(p) {
        return {
            "start_date": $('#start_date').val(),
            "end_date": $('#end_date').val(),
            "order_status": $('#order_status').val(),
            "payment_method": $('#payment_method').val(),
            "order_type": $('#order_type').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    function digital_order_mails_query_params(p) {
        return {
            "order_item_id": $('#digital_mails_order_item_id').val(),
            "order_id": $('#digital_mails_order_id').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    $(document).on('click', '.edit_order_tracking', function () {
        $('#tracking_order_id').val($(this).data('order_id'));
        $('#tracking_order_item_id').val($(this).data('order_item_id'));
        $('#tracking_seller_id').val($(this).data('seller_id'));
        $('#courier_agency').val($(this).data('courier_agency'));
        $('#tracking_id').val($(this).data('tracking_id'));
        $('#url').val($(this).data('url'));
    });

    $(document).on('submit', '#order_tracking_form', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.append(csrfName, csrfHash);
        var submitBtn = $('#tracking_submit_btn');
        var originalText = submitBtn.html();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            beforeSend: function () {
                submitBtn.html('Please Wait...').prop('disabled', true);
            },
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName;
                    csrfHash = result.csrfHash;
                }
                if (result.error) {
                    iziToast.error({ message: result.message });
                } else {
                    iziToast.success({ message: result.message });
                    $('#transaction_modal').modal('hide');
                    $('.manage-orders-page .table-striped').bootstrapTable('refresh');
                }
            },
            error: function () {
                iziToast.error({ message: 'Something went wrong. Please try again.' });
            },
            complete: function () {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.sendMailBtn', function () {
        $('.ManageOrderEmail').val($(this).data('email'));
        $('#send_mail_order_item_id').val($(this).data('id'));
    });

    $(document).on('submit', '#digital_product_management', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.append(csrfName, csrfHash);
        var submitBtn = $('#send_mail_submit_btn');
        var originalText = submitBtn.html();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            beforeSend: function () {
                submitBtn.html('Please Wait...').prop('disabled', true);
            },
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                if (result.error) {
                    iziToast.error({ message: result.message });
                } else {
                    iziToast.success({ message: result.message });
                    $('#ManageOrderSendMailModal').modal('hide');
                    $('.manage-orders-page .table-striped').bootstrapTable('refresh');
                }
            },
            error: function () {
                iziToast.error({ message: 'Something went wrong. Please try again.' });
            },
            complete: function () {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.edit_digital_order_mails', function () {
        $('#digital_mails_order_item_id').val($(this).data('order_item_id'));
        $('#digital_mails_order_id').val($(this).data('order_id'));
        $('#digital_order_mail_table').bootstrapTable('refresh');
    });
</script>
