<div class="content-wrapper admin-view-order-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-receipt mr-2 text-primary-theme"></i>View Order #<?= (int) $order_detls[0]['id'] ?></h4>
                    <p class="text-muted mb-0 small">Order status, courier tracking and item details for this order.</p>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/orders') ?>">Orders</a></li>
                        <li class="breadcrumb-item active">View Order</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- modal for send digital product -->
                <div id="sendMailModal" class="modal fade editSendMail" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLongTitle">Manage Digital Product</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body ">
                                <form class="form-horizontal form-submit-event" action="<?= base_url('admin/orders/send_digital_product'); ?>" method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <input type="hidden" name="order_id" value="<?= html_escape($order_detls[0]['order_id']) ?>">
                                        <input type="hidden" name="order_item_id" value="<?= html_escape($this->input->get('edit_id')) ?>">
                                        <input type="hidden" name="username" value="<?= html_escape($order_detls[0]['uname']) ?>">
                                        <div class="row form-group">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="product_name">Customer Email-ID </label>
                                                    <input type="text" class="form-control" id="email" name="email" value="<?= html_escape($order_detls[0]['user_email']) ?>" readonly>
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
                                                    <label for="product_name">Message</label>
                                                    <textarea class="textarea" id="mail_msg" placeholder="Message for Email" name="message"></textarea>
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
                                        <button type="submit" class="btn btn-success mt-3" id="submit_btn" value="Save"><?= labels('send_mail', 'Send Mail') ?></button>
                                    </div>
                                </form>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- modal for order tracking -->
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
                                            <form class="form-horizontal " id="order_tracking_form" action="<?= base_url('admin/orders/update-order-tracking/'); ?>" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="order_id" id="order_id">
                                                <input type="hidden" name="order_item_id" id="order_item_id">
                                                <input type="hidden" name="seller_id" id="seller_id">
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

                <!-- modal for create shiprocket order -->
                <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="order_parcel_modal" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Create Shipprocket Order Parcel</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card card-info">
                                            <!-- form start -->
                                            <form class="form-horizontal " id="shiprocket_order_parcel_form" action="" method="POST">

                                                <?php
                                                $total_items = count($items);
                                                ?>
                                                <div class="card-body pad">
                                                    <div class="form-group">
                                                        <input type="hidden" name=" <?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                                                        <input type="hidden" id="order_id" name="order_id" value="<?php print_r($order_detls[0]['id']); ?>" />
                                                        <input type="hidden" name="user_id" id="user_id" value="<?php echo $order_detls[0]['user_id']; ?>" />
                                                        <input type="hidden" name="total_order_items" id="total_order_items" value="<?php echo $total_items; ?>" />
                                                        <input type="hidden" name="shiprocket_seller_id" value="" />
                                                        <input type="hidden" name="fromadmin" value="1" id="fromadmin" />
                                                        <textarea id="order_items" name="order_items[]" hidden><?= json_encode($items, JSON_FORCE_OBJECT); ?></textarea>
                                                    </div>
                                                    <div class="mt-1 p-2 bg-danger text-white rounded">
                                                        <p><b>Note:</b> Make your pickup location associated with the order is verified from <a href="https://app.shiprocket.in/company-pickup-location?redirect_url=" target="_blank" style="text-decoration: underline;color: white;"> Shiprocket Dashboard </a> and then in <a href="<?php base_url('admin/Pickup_location/manage-pickup-locations'); ?>" target="_blank" style="text-decoration: underline;color: white;"> admin panel </a>. If it is not verified you will not be able to generate AWB later on.</p>
                                                    </div>
                                                    <div class="form-group row mt-4">
                                                        <div class="col-4">
                                                            <label for="txn_amount">Pickup location</label>
                                                        </div>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="pickup_location" id="pickup_location" placeholder="Pickup Location" value="" readonly />
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mt-3">
                                                        <div class="col-md-6">
                                                            <label for="txn_amount">Total Weight of Box</label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mt-4">
                                                        <div class="col-3">
                                                            <label for="parcel_weight" class="control-label col-md-12">Weight <small>(kg)</small> <span class='text-danger text-xs'>*</span></label>
                                                            <input type="number" class="form-control" name="parcel_weight" placeholder="Parcel Weight" id="parcel_weight" value="" step=".01">
                                                        </div>
                                                        <div class="col-3">
                                                            <label for="parcel_height" class="control-label col-md-12">Height <small>(cms)</small> <span class='text-danger text-xs'>*</span></label>
                                                            <input type="number" class="form-control" name="parcel_height" placeholder="Parcel Height" id="parcel_height" value="" min="1">
                                                        </div>
                                                        <div class="col-3">
                                                            <label for="parcel_breadth" class="control-label col-md-12">Breadth <small>(cms)</small> <span class='text-danger text-xs'>*</span></label>
                                                            <input type="number" class="form-control" name="parcel_breadth" placeholder="Parcel Breadth" id="parcel_breadth" value="" min="1">
                                                        </div>
                                                        <div class="col-3">
                                                            <label for="parcel_length" class="control-label col-md-12">Length <small>(cms)</small> <span class='text-danger text-xs'>*</span></label>
                                                            <input type="number" class="form-control" name="parcel_length" placeholder="Parcel Length" id="parcel_length" value="" min="1">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-success create_shiprocket_parcel">Create Order</button>
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
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set"><i class="fas fa-receipt"></i></span>
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <table class="table order-detail-table">
                                <?php
                                $mobile_data = fetch_details('addresses', ['id' => $order_detls[0]['address_id']], 'mobile');
                                ?>
                                <tr>
                                    <input type="hidden" name="hidden" id="order_id" value="<?php echo $order_detls[0]['id']; ?>">
                                    <th class="w-10px">ID</th>
                                    <td><?php echo $order_detls[0]['id']; ?></td>
                                </tr>
                                <tr>
                                    <th class="w-10px">Name</th>
                                    <td><?php echo "Account Holder Person : " . $order_detls[0]['uname'] . " | Order Recipient Person :  " . $order_detls[0]['user_name']; ?></td>
                                </tr>
                                <tr>
                                    <th class="w-10px">Email</th>
                                    <td><?= (!defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) ? str_repeat("X", strlen($order_detls[0]['email']) - 3) . substr($order_detls[0]['email'], -3) : $order_detls[0]['email']; ?></td>
                                </tr>
                                <?php if ($order_detls[0]['mobile'] != '' && isset($order_detls[0]['mobile'])) {
                                ?>
                                    <tr>
                                        <th class="w-10px">Contact</th>
                                        <td><?= (!defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)  ? str_repeat("X", strlen($order_detls[0]['mobile']) - 3) . substr($order_detls[0]['mobile'], -3) : $order_detls[0]['mobile']; ?>
                                        </td>
                                    </tr>

                                <?php  } else {
                                ?>
                                    <tr>
                                        <th class="w-10px">Contact</th>
                                        <td><?= (!defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)  ? str_repeat("X", strlen($mobile_data[0]['mobile']) - 3) . substr($mobile_data[0]['mobile'], -3) : $mobile_data[0]['mobile']; ?>
                                        </td>
                                    </tr>
                                <?php
                                } ?>
                                <?php if (!empty($order_detls[0]['notes'])) { ?>
                                    <tr>
                                        <th class="w-10px">Order note</th>
                                        <td><?php echo  $order_detls[0]['notes']; ?></td>
                                    </tr>
                                <?php } ?>

                                <?php $sellers = array_values(array_unique(array_column($order_detls, "seller_id")));
                                // Initialised out here as well as inside the per-seller block:
                                // the order-summary row near the bottom of this page prints
                                // $tax_amount unconditionally, but the block that sets it only
                                // runs for sellers that have a matching pickup location, so an
                                // order without one printed an undefined variable.
                                $total = 0;
                                $tax_amount = 0; ?>
                                <tr>
                                    <td colspan="2">

                                        <?php if (isset($items[0]['product_type']) && $items[0]['product_type'] == 'digital_product') { ?>
                                            <p>
                                                <lable class="badge badge-success" style="font-size:13px;">Select status and radio button of seller which you want to update</lable>
                                            </p>
                                            <div class="row">
                                                <div class="col-md-4 ">
                                                    <select name="status" class="form-control status">
                                                        <option value=''>Select Status</option>
                                                        <option value="delivered">Delivered</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <a href="javascript:void(0);" title="Bulk Update" class="btn btn-primary col-sm-12 col-md-12 update_status_admin_bulk mr-1">
                                                        Update
                                                    </a>
                                                </div>
                                            </div>
                                            <p>
                                                <lable class="badge badge-warning mt-4" style="font-size:13px;">Note : Select square box of item only when you want to update it as cancelled or returned.</lable>
                                            </p>

                                        <?php } else {

                                        ?>

                                            <p>
                                                <lable class="badge badge-success " style="font-size:13px;">Select status, delivery boy and radio button of seller which you want to update</lable>
                                            </p>
                                            <div class="row delivery_boy ">

                                                <div class="col-md-3">
                                                    <select name="status" class="form-control status">
                                                        <option value=''>Select Status</option>
                                                        <option value="processed">Processed</option>
                                                        <option value="shipped">Shipped</option>
                                                        <option value="delivered">Delivered</option>
                                                        <option value="cancelled">Cancel</option>
                                                        <option value="returned">Returned</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <select id='deliver_by' name='deliver_by' class='form-control' required>
                                                        <option value=''>Select Delivery Boy</option>
                                                        <?php foreach ($delivery_res as $row) { ?>
                                                            <option value="<?= $row['user_id'] ?>"><?= $row['username'] ?></option>
                                                        <?php  } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php
                                                    // A leftover, HTML-commented copy of the line below it - PHP still evaluates
                                                    // short-echo tags inside an HTML comment, so this raised "Undefined variable $i" /
                                                    // "Undefined array key ''" on every load ($i isn't defined until the per-seller
                                                    // loop starts further down) despite never actually rendering anything.
                                                    ?>
                                                    <a href="javascript:void(0)" class="edit_order_tracking btn btn-success btn-xl " title="Order Tracking" data-order_id=' <?= $order_detls[0]['id']; ?>' data-target="#transaction_modal" data-toggle="modal" style="height:35px;width:38px;"><i class="fa fa-map-marker-alt"></i></a>
                                                    <?php
                                                    // data-id here relied on $sellers[$i], but $i isn't defined until the per-seller
                                                    // loop below starts - this button renders before it, on the seller-agnostic
                                                    // "bulk update" row. The JS handler (update_status_admin_bulk in custom.js)
                                                    // already treats data-id as a last-resort fallback behind the actual seller
                                                    // radio button (input[name="seller_id"]:checked, rendered correctly per-seller
                                                    // further down) - removing this broken attribute doesn't change how seller
                                                    // selection actually works, it just stops raising a warning for a value
                                                    // nothing meaningfully depended on.
                                                    ?>
                                                    <a href="javascript:void(0);" title="Bulk Update" class="btn btn-primary ml-3 col-md-4 update_status_admin_bulk">
                                                        Update
                                                    </a>
                                                    <?php if ($shipping_method['shiprocket_shipping_method'] == 1) { ?>
                                                        <button type="button" disabled class="btn btn-primary ml-3 col-md-6 create_shiprocket_order float-right" data-target="#order_parcel_modal" data-toggle="modal"> Create Shiprocket Order</button>
                                                    <?php } ?>

                                                </div>
                                            </div>
                                            <p>
                                                <lable class="badge badge-warning mt-4 " style="font-size:13px;">Note : Select square box of item only when you want to update it as cancelled or returned.</lable>
                                            </p>
                                            <?php if ($shipping_method['shiprocket_shipping_method'] == 1) { ?>
                                                <p>
                                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#ShiprocketOrderFlow">How to manage shiprocket order </button>
                                                </p>
                                            <?php } ?>

                                        <?php } ?>
                                    </td>
                                </tr>
                                <div class="row">
                                    <?php
                                    // This form's class attribute referenced the per-seller $sellers[$i] value before the
                                    // per-seller loop below defines it, and nothing in custom.js ever selects this form by
                                    // a per-seller class anyway - it's targeted throughout by #update_form (an id, already
                                    // unique and correct).
                                    ?>
                                    <form id="update_form">
                                        <?php
                                        for ($i = 0; $i < count($sellers); $i++) {
                                            $seller_data = fetch_details('users', ['id' => $sellers[$i]], 'username,fcm_id,email,mobile');
                                            // fetch_details(...)[0]['otp'] indexed straight into the result with no guard - an
                                            // order whose items for this seller have no otp recorded raised "Undefined array key 0".
                                            $seller_otp_rows = fetch_details('order_items', ['order_id' => $order_detls[0]['order_id'], 'seller_id' => $sellers[$i]], 'otp');
                                            $seller_otp = !empty($seller_otp_rows[0]['otp']) ? $seller_otp_rows[0]['otp'] : '';
                                            $order_caharges_data = fetch_details('order_charges', ['order_id' => $order_detls[0]['order_id'], 'seller_id' => $sellers[$i]]);
                                            $this->load->model('Order_model');
                                            $seller_order = $this->Order_model->get_order_details(['o.id' => $order_detls[0]['order_id'], 'oi.seller_id' => $sellers[$i]]);
                                            $pickup_location = array_values(array_unique(array_column($seller_order, "pickup_location")));

                                        ?>
                                            <tr>
                                                <td colspan="2">
                                                    <div class="card card-info mb-3 mt-2 ">
                                                        <div class="card-body">
                                                            <div class="col-md-6 m-2 text-left">
                                                                <input type="radio" name="seller_id" value="<?= $sellers[$i] ?>" style="height:15px;width:15px;">
                                                                <strong>
                                                                    <p class="mb-0">Seller :
                                                                </strong>
                                                                <?= !empty($seller_data[0]['username']) ? ucfirst($seller_data[0]['username']) : 'N/A' ?></p>
                                                                <?php if ($items[0]['product_type'] != 'digital_product') { ?>
                                                                    <strong>
                                                                        <p>OTP :
                                                                    </strong>
                                                                    <span class="badge badge-danger"><?= isset($order_caharges_data[0]['otp']) ? $order_caharges_data[0]['otp'] : $seller_otp ?></span></p>
                                                                <?php } ?>
                                                            </div>
                                                            <?php for ($j = 0; $j < count($pickup_location); $j++) {
                                                                $ids = "";
                                                                foreach ($seller_order as $row) {

                                                                    if ($row['pickup_location'] == $pickup_location[$j]) {
                                                                        $ids .= $row['order_item_id'] . ',';
                                                                    }
                                                                }
                                                                $order_item_ids = explode(',', trim($ids, ','));
                                                                $order_tracking_data = get_shipment_id($order_item_ids[0], $order_detls[0]['order_id']);
                                                                // get_shipment_id() returns false when this pickup location has never actually
                                                                // been shipped via Shiprocket - true of every order in this database, and true
                                                                // of any order on a site that doesn't use Shiprocket at all. The code below
                                                                // used to call the live Shiprocket API with a null order id in that case
                                                                // ($order_tracking_data[0][...] on a bool - "Trying to access array offset on
                                                                // value of type bool"), then read ['data']['status'] off of whatever came back
                                                                // from that meaningless call, cascading into "Undefined array key" warnings on
                                                                // every status check and every action button below. Both are skipped now
                                                                // unless there is an actual shipment on record, and $shiprocket_order always
                                                                // has a 'data' array (even if empty) so every ['data'][...] read below stays
                                                                // safe either way.
                                                                $shiprocket_order = ['data' => []];
                                                                if (!empty($order_tracking_data) && !empty($order_tracking_data[0]['shiprocket_order_id'])) {
                                                                    $fetched_shiprocket_order = get_shiprocket_order($order_tracking_data[0]['shiprocket_order_id']);
                                                                    if (is_array($fetched_shiprocket_order) && !empty($fetched_shiprocket_order['data'])) {
                                                                        $shiprocket_order = $fetched_shiprocket_order;
                                                                    }
                                                                }
                                                                $shiprocket_status = isset($shiprocket_order['data']['status']) ? $shiprocket_order['data']['status'] : '';
                                                                $shiprocket_status_code = isset($shiprocket_order['data']['status_code']) ? $shiprocket_order['data']['status_code'] : null;
                                                                // Status changes go through the same shared helper the Shiprocket
                                                                // webhook uses instead of being reimplemented here. The old inline
                                                                // version knew only four Shiprocket statuses, and it set $type inside
                                                                // the item loop without ever resetting it - so once one item changed
                                                                // status, every remaining item in the loop re-sent that same
                                                                // customer/seller notification even though nothing about it changed.
                                                                if (!empty($order_tracking_data) && !empty($order_tracking_data[0]['shiprocket_order_id'])) {
                                                                    sync_shiprocket_shipment_status($order_tracking_data[0], $shiprocket_status);
                                                                }
                                                            ?>
                                                                <?php if ($shipping_method['shiprocket_shipping_method'] == 1 && isset($pickup_location[$j]) && !empty($pickup_location[$j]) && $pickup_location[$j] != 'NULL') { ?>
                                                                    <div class="row">
                                                                        <div class="col-sm-0 ml-4 m-2 text-left mt-3 ">
                                                                            <?php // $item isn't defined yet here (only set later by the foreach($items as $item)
                                                                            // loop below) - $items[0] is the same fallback the rest of this page already
                                                                            // uses before that loop runs. ?>
                                                                            <?php if ($items[0]['product_type'] != 'digital_product' && empty($order_tracking_data[0]['shipment_id'])) { ?>
                                                                                <input type="radio" name="pickup_location" class="check_create_order" data-id="<?= $sellers[$i] ?>" id="<?php print_r($pickup_location[$j]); ?>" />
                                                                            <?php } ?>
                                                                        </div>
                                                                        <?php if (isset($pickup_location[$j]) && !empty($pickup_location[$j]) && $pickup_location[$j] != '') { ?>
                                                                            <div class="col-md-6 m-2 text-left mt-3">
                                                                                <strong>

                                                                                    <p class="mb-0">Pickup Location :
                                                                                </strong>
                                                                                <?= ucfirst($pickup_location[$j]) ?></p>
                                                                            </div>
                                                                        <?php } ?>
                                                                    </div>
                                                                    <div class="row m-2 ml-6">
                                                                        <div class="col-sm-0 ml-4 m-2"></div>
                                                                        <?php if (isset($order_tracking_data[0]['shipment_id']) && !empty($order_tracking_data[0]['shipment_id']) && empty($order_tracking_data[0]['is_canceled']) && $order_tracking_data[0]['is_canceled'] != 1 && $shiprocket_status != 'CANCELED') { ?>
                                                                            <div class="col-md-1">
                                                                                <span class="badge bg-success ml-1">Order created</span>
                                                                            </div>
                                                                        <?php } ?>
                                                                        <?php if (isset($items[0]['product_type']) && ($items[0]['product_type'] != 'digital_product')) {  ?>
                                                                            <?php if (empty($order_tracking_data[0]['shipment_id'])) { ?>
                                                                                <div class="col-md-1">
                                                                                    <span class="badge bg-primary ml-1">Order not created</span>
                                                                                </div>
                                                                        <?php }
                                                                        } ?>

                                                                        <?php if ((isset($order_tracking_data[0]['is_canceled']) && $order_tracking_data[0]['is_canceled'] != 0) || $shiprocket_status == 'CANCELED') { ?>
                                                                            <div class="col-md-1">
                                                                                <span class="badge bg-danger ml-1">Order cancelled</span>
                                                                            </div>
                                                                        <?php  } ?>
                                                                        <div class="col-md-5">
                                                                            <?php if (isset($order_tracking_data[0])) { ?>
                                                                                <?php if (isset($order_tracking_data[0]['shipment_id']) && (empty($order_tracking_data[0]['awb_code']) || $order_tracking_data[0]['awb_code'] == 'NULL') && $shiprocket_status != 'CANCELED') { ?>
                                                                                    <a href="" title="Generate AWB" class="btn btn-primary btn-xs mr-1 generate_awb" data-fromadmin="1" id=<?php print_r($order_tracking_data[0]['shipment_id']); ?>>AWB</a>
                                                                                <?php } else { ?>
                                                                                    <?php if (empty($order_tracking_data[0]['pickup_scheduled_date']) && ($shiprocket_status_code != 4 || $shiprocket_status != 'PICKUP SCHEDULED') && $shiprocket_status != 'CANCELED' && $shiprocket_status != 'CANCELLATION REQUESTED') { ?>
                                                                                        <a href="" title="Send Pickup Request" class="btn btn-primary btn-xs mr-1 send_pickup_request" data-fromadmin="1" name=<?php print_r($order_tracking_data[0]['shipment_id']); ?>><i class="fas fa-shipping-fast "></i></a>
                                                                                    <?php }
                                                                                    if (isset($order_tracking_data[0]['is_canceled']) && $order_tracking_data[0]['is_canceled'] == 0) { ?>
                                                                                        <a href="" title="Cancel Order" class="btn btn-primary btn-xs mr-1 cancel_shiprocket_order" data-fromadmin="1" name=<?php print_r($order_tracking_data[0]['shiprocket_order_id']); ?>><i class="fas fa-redo-alt"></i></a>
                                                                                    <?php } ?>

                                                                                    <?php if (isset($order_tracking_data[0]['label_url']) && !empty($order_tracking_data[0]['label_url'])) { ?>
                                                                                        <a href="<?php print_r($order_tracking_data[0]['label_url']); ?>" title="Download Label" data-fromadmin="1" class="btn btn-primary btn-xs mr-1 download_label"><i class="fas fa-download"></i> Label</a>
                                                                                    <?php } else { ?>
                                                                                        <a href="" title="Generate Label" class="btn btn-primary btn-xs mr-1 generate_label" data-fromadmin="1" name=<?php print_r($order_tracking_data[0]['shipment_id']); ?>><i class="fas fa-tags"></i></a>
                                                                                    <?php } ?>

                                                                                    <?php if (isset($order_tracking_data[0]['invoice_url']) && !empty($order_tracking_data[0]['invoice_url'])) { ?>
                                                                                        <a href="<?php print_r($order_tracking_data[0]['invoice_url']); ?>" title="Download Invoice" data-fromadmin="1" class="btn btn-primary  btn-xs mr-1 download_invoice"><i class="fas fa-download"></i> Invoice</a>
                                                                                    <?php } else { ?>
                                                                                        <a href="" title="Generate Invoice" class="btn btn-primary btn-xs mr-1 generate_invoice" data-fromadmin="1" name=<?php print_r($order_tracking_data[0]['shiprocket_order_id']); ?>><i class="far fa-money-bill-alt"></i></a>
                                                                                    <?php }
                                                                                    if (isset($order_tracking_data[0]['awb_code']) && !empty($order_tracking_data[0]['awb_code'])) { ?>
                                                                                        <a href="https://shiprocket.co/tracking/<?php echo $order_tracking_data[0]['awb_code']; ?>" target=" _blank" title="Track Order" class="btn btn-primary action-btn btn-xs mr-1 track_order" name=<?php print_r($order_tracking_data[0]['shiprocket_order_id']); ?>><i class="fas fa-map-marker-alt"></i></a>
                                                                                    <?php } ?>
                                                                                <?php } ?>
                                                                            <?php } ?>
                                                                        </div>
                                                                    </div>
                                                                <?php } ?>

                                                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                                                                <input type="hidden" name="order_id" value="<?= html_escape($order_detls[0]['order_id']) ?>">

                                                                <?php $total = 0;
                                                                $tax_amount = 0; ?>
                                                                <div class="container-fluid">

                                                                    <?php
                                                                    echo '<div class="order-item-grid">';


                                                                    foreach ($items as $item) {
                                                                        $selected = "";
                                                                        $item['discounted_price'] = ($item['discounted_price'] == '') ? 0 : $item['discounted_price'];
                                                                        $total += $subtotal = ($item['quantity'] != 0 && ($item['discounted_price'] != '' && $item['discounted_price'] > 0) && $item['price'] > $item['discounted_price']) ? ($item['price'] - $item['discounted_price']) : ($item['price'] * $item['quantity']);
                                                                        $tax_amount += $item['tax_amount'];
                                                                        $total += $subtotal = $tax_amount;
                                                                    ?>
                                                                        <?php if ($sellers[$i] == $item['seller_id']) {
                                                                            if ($pickup_location[$j] == $item['pickup_location']) {
                                                                                $order_tracking_data = get_shipment_id($item['id'], $order_detls[0]['id']); ?>
                                                                                <div class="order-item-card">
                                                                                    <?php
                                                                                    $badges = ["awaiting" => "secondary", "received" => "primary", "processed" => "info", "shipped" => "warning", "delivered" => "success", "returned" => "danger", "cancelled" => "danger", "return_request_approved" => "success", "return_request_decline" => "danger", "return_request_pending" => "warning"];
                                                                                    // Refund state lives on the order item (migration 044). The old lookup
                                                                                    // read `transactions` by order_item_id hoping to find the gateway
                                                                                    // payment, but payments are recorded against the ORDER with a NULL
                                                                                    // order_item_id - it only ever matched the wallet refund row, whose
                                                                                    // txn_id is empty, so the button posted a blank transaction id and the
                                                                                    // request died in validation. The controller now resolves the payment
                                                                                    // itself; the view only needs to know what is refundable.
                                                                                    $item_refund = fetch_details('order_items', ['id' => $item['id']], 'refunded_at,refund_amount,refund_mode');
                                                                                    $item_refund = !empty($item_refund) ? $item_refund[0] : ['refunded_at' => null, 'refund_amount' => null, 'refund_mode' => null];
                                                                                    // Operator precedence bug: "A || B || C) && D || E" parses as
                                                                                    // "((A||B||C) && D) || E" - so ANY item with active_status=='returned'
                                                                                    // showed a refund button regardless of payment method, including COD and
                                                                                    // bank transfer orders that were never charged through Razorpay at all.
                                                                                    $is_razorpay_payment = in_array($order_detls[0]['payment_method'], ['RazorPay', 'razorpay', 'Razorpay'], true);
                                                                                    $is_refundable_status = in_array($item['active_status'], ['cancelled', 'returned'], true);
                                                                                    // Already settled one way or the other - offering the button again can
                                                                                    // only lead to paying twice.
                                                                                    $is_already_refunded = !empty($item_refund['refunded_at']) && (float) $item_refund['refund_amount'] > 0;
                                                                                    ?>
                                                                                    <div class="order-item-card-top">
                                                                                        <label class="order-item-select" title="Select to mark as cancelled/returned">
                                                                                            <input type="checkbox" id="<?= $sellers[$i] ?>" name="order_item_id" value=' <?= $item['id'] ?> ' disabled>
                                                                                        </label>
                                                                                        <span class="badge badge-<?= $badges[$item['active_status']] ?>"><?= str_replace('_', ' ', $item['active_status']) ?></span>
                                                                                    </div>
                                                                                    <div class="order-item-media">
                                                                                        <a href='<?= base_url() . $item['product_image'] ?>' data-toggle='lightbox' data-gallery='order-images'> <img src='<?= base_url() . $item['product_image'] ?>'></a>
                                                                                    </div>
                                                                                    <div class="order-item-name"><?= $item['pname'] ?></div>
                                                                                    <div class="order-item-meta">
                                                                                        <div class="oi-row"><span class="oi-label">Type</span><span class="oi-value"><?= ucwords(str_replace('_', ' ', $item['product_type'])); ?></span></div>
                                                                                        <div class="oi-row"><span class="oi-label">Variant ID</span><span class="oi-value"><?= $item['product_variant_id'] ?></span></div>
                                                                                        <?php if (isset($item['product_variants']) && !empty($item['product_variants'])) { ?>
                                                                                            <div class="oi-row"><span class="oi-label">Variants</span><span class="oi-value"><?= str_replace(',', ' | ', $item['product_variants'][0]['variant_values']) ?></span></div>
                                                                                        <?php } ?>
                                                                                        <div class="oi-row"><span class="oi-label">Quantity</span><span class="oi-value"><?= $item['quantity'] ?></span></div>
                                                                                        <div class="oi-row"><span class="oi-label">Price</span><span class="oi-value"><?= $item['price'] ?></span></div>
                                                                                        <div class="oi-row"><span class="oi-label">Discounted Price</span><span class="oi-value"><?= $item['discounted_price'] ?></span></div>
                                                                                        <div class="oi-row"><span class="oi-label">Subtotal</span><span class="oi-value"><?= $item['price'] * $item['quantity'] ?></span></div>
                                                                                        <?php if (isset($item['product_type']) && ($item['product_type'] != 'digital_product')) { ?>
                                                                                            <div class="oi-row"><span class="oi-label">Pickup Location</span><span class="oi-value"><?= $item['pickup_location'] ?></span></div>
                                                                                            <?php if (isset($order_tracking_data[0]['shipment_id']) && !empty($order_tracking_data[0]['shipment_id'])) { ?>
                                                                                                <div class="oi-row"><span class="oi-label">Shipment Id</span><span class="oi-value"><?= $order_tracking_data[0]['shipment_id'] ?></span></div>
                                                                                        <?php  }
                                                                                        } ?>
                                                                                        <?php if (isset($item['updated_by']) && !empty($item['updated_by'])) { ?>
                                                                                            <div class="oi-row"><span class="oi-label">Updated By</span><span class="oi-value"><?= $item['updated_by'] ?></span></div>
                                                                                        <?php } ?>
                                                                                        <?php if (isset($item['deliver_by']) && !empty($item['deliver_by'])) { ?>
                                                                                            <div class="oi-row"><span class="oi-label">Deliver By</span><span class="oi-value"><?= $item['deliver_by'] ?></span></div>
                                                                                        <?php } ?>
                                                                                    </div>

                                                                                    <?php if ($item['product_type'] == "digital_product" && $item['download_allowed'] == 0 && $item['is_sent'] == 0) { ?>
                                                                                        <div class="order-item-mail-status order_item_mail_status">
                                                                                            <select class="form-control form-control-sm">
                                                                                                <option value="1">Mail Sent</option>
                                                                                            </select>
                                                                                            <a href="javascript:void(0);" title="Update status" data-id=' <?= $item['id'] ?> ' class="btn btn-primary btn-xs action-btn update_mail_status_admin">
                                                                                                <i class="far fa-arrow-alt-circle-up"></i>
                                                                                            </a>
                                                                                            <a href="javascript:void(0)" class="btn btn-warning btn-xs action-btn" data-target="#sendMailModal" data-toggle="modal" title="Edit" data-id="<?= $item['id'] ?>" data-url="admin/orders/">
                                                                                                <i class="fas fa-paper-plane"></i>
                                                                                            </a>
                                                                                            <a href="https://mail.google.com/mail/?view=cm&fs=1&tf=1&to=<?= $item['user_email'] ?>" class="btn btn-danger btn-xs action-btn" target="_blank">
                                                                                                <i class="fab fa-google"></i>
                                                                                            </a>
                                                                                        </div>
                                                                                    <?php } ?>

                                                                                    <div class="order-item-actions">
                                                                                        <a href=" <?= BASE_URL('admin/product/view-product?edit_id=' . $item['product_id'] . '') ?> " title="View Product" class="btn btn-primary btn-xs">
                                                                                            <i class="fa fa-eye"></i> View Product
                                                                                        </a>
                                                                                        <?php
                                                                                        // Two dead sections used to sit here, wrapped in HTML comments so nothing
                                                                                        // rendered - but PHP evaluates short-echo tags regardless of the surrounding
                                                                                        // HTML comment syntax, so both still executed on every page load. The
                                                                                        // "order_item_status" section (per-item status change) is superseded by
                                                                                        // the seller-level bulk update control above and is dropped entirely. The
                                                                                        // "delivery_boy" section also contained the ONLY trigger anywhere on this
                                                                                        // page for the Refund button - #refund_modal and its working
                                                                                        // admin/orders/refund_payment endpoint both still exist, but with this
                                                                                        // commented out there was no way to open that modal at all. Restored,
                                                                                        // fixed, and pared down to just that: the per-item delivery-boy and
                                                                                        // tracking controls it also contained are dropped as duplicates of the
                                                                                        // bulk section and the seller-level tracking button above.
                                                                                        if ($is_razorpay_payment && $is_refundable_status && !$is_already_refunded) { ?>
                                                                                            <a href="javascript:void(0)" class="edit_order_refund btn btn-outline-danger btn-xs" title="Refund"
                                                                                                data-order_id="<?= (int) $order_detls[0]['id'] ?>"
                                                                                                data-order_item_id="<?= (int) $item['id'] ?>"
                                                                                                data-txn_amount="<?= html_escape($item['sub_total'] ?? '') ?>"
                                                                                                data-target="#refund_modal" data-toggle="modal"><i class="fa fa-undo"></i> Refund</a>
                                                                                        <?php } elseif ($is_already_refunded) { ?>
                                                                                            <span class="badge badge-secondary" title="Refunded <?= html_escape($item_refund['refunded_at']) ?>">
                                                                                                Refunded <?= html_escape($item_refund['refund_amount']) ?> (<?= html_escape($item_refund['refund_mode']) ?>)
                                                                                            </span>
                                                                                        <?php } ?>
                                                                                    </div>
                                                                                </div>
                                                                    <?php
                                                                            }
                                                                        }
                                                                    }
                                                                    echo '</div>';
                                                                    ?>
                                                                    <div>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php
                                        } ?>
                                    </form>
                                </div>
                                <tr>
                                    <th class="w-10px">Total(<?= $settings['currency'] ?>)</th>
                                    <td id=' amount'><?php echo $order_detls[0]['order_total'];
                                                        $total = $order_detls[0]['order_total'];
                                                        ?></td>
                                </tr>

                                <tr class="d-none">
                                    <th class="w-10px">Tax(<?= $settings['currency'] ?>)</th>
                                    <td id='amount'><?php echo $tax_amount;
                                                    ?></td>
                                </tr>
                                <?php if (isset($items[0]['product_type']) && $items[0]['product_type'] != 'digital_product') { ?>
                                    <tr>
                                        <th class="w-10px">Delivery Charge(<?= $settings['currency'] ?>)</th>
                                        <td id='delivery_charge'>
                                            <?php echo $order_detls[0]['delivery_charge'];
                                            $total = $total + $order_detls[0]['delivery_charge']; ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <tr>
                                    <th class="w-10px">Wallet Balance(<?= $settings['currency'] ?>)</th>
                                    <td><?php echo $order_detls[0]['wallet_balance'];
                                        $total = $total - $order_detls[0]['wallet_balance']; ?></td>
                                </tr>

                                <input type="hidden" name="total_amount" id="total_amount" value="<?php echo $order_detls[0]['order_total'] + $order_detls[0]['delivery_charge'] ?>">
                                <input type="hidden" name="final_amount" id="final_amount" value="<?php echo $order_detls[0]['final_total']; ?>">

                                <tr>
                                    <th class="w-10px">Promo Code Discount (<?= $settings['currency'] ?>)</th>
                                    <td><?php echo $order_detls[0]['promo_discount'];
                                        $total = floatval($total -
                                            $order_detls[0]['promo_discount']); ?></td>
                                </tr>
                                <?php
                                if (isset($order_detls[0]['discount']) && $order_detls[0]['discount'] > 0) {
                                    $discount = $order_detls[0]['total_payable']  *  ($order_detls[0]['discount'] / 100);
                                    $total = round($order_detls[0]['total_payable'] - $discount, 2);
                                }
                                ?>
                                <tr>
                                    <th class="w-10px">Payable Total(<?= $settings['currency'] ?>)</th>
                                    <td><input type="text" class="form-control" id="final_total" name="final_total" value="<?= html_escape($total) ?>" disabled></td>
                                </tr>
                                <tr>
                                    <th class="w-10px">Payment Method</th>
                                    <td><?php echo $order_detls[0]['payment_method']; ?></td>
                                </tr>
                                <?php
                                if (!empty($bank_transfer)) { ?>
                                    <tr>
                                        <th class="w-10px">Bank Transfers</th>
                                        <td>
                                            <div class="col-md-6">
                                                <?php $status = ["history", "ban", "check"]; ?>
                                                <a class="btn btn-primary btn-xs mr-1 mb-1 " title="Current Status" href="javascript:void(0)" data-id="<?= $order_detls[0]['id']; ?>"><i class="fa fa-<?= $status[$bank_transfer[0]['status']] ?>"></i></a>
                                                <?php $i = 1;
                                                foreach ($bank_transfer as $row1) { ?>
                                                    <small>[<a href="<?= base_url() . $row1['attachments'] ?>" target="_blank">Attachment <?= $i ?> </a>] </small>
                                                    <?php if ($row1['status'] == 0) { ?>
                                                        <label class="badge badge-warning"><?= !empty($this->lang->line('pending')) ? $this->lang->line('pending') : 'Pending' ?></label>
                                                    <?php } else if ($row1['status'] == 1) { ?>
                                                        <label class="badge badge-danger"><?= !empty($this->lang->line('rejected')) ? $this->lang->line('rejected') : 'Rejected' ?></label>
                                                    <?php } else if ($row1['status'] == 2) { ?>
                                                        <label class="badge badge-primary"><?= !empty($this->lang->line('accepted')) ? $this->lang->line('accepted') : 'Accepted' ?></label>
                                                    <?php } else { ?>
                                                        <label class="badge badge-danger"><?= !empty($this->lang->line('invalid_value')) ? $this->lang->line('invalid_value') : 'Invalid Value' ?></label>
                                                    <?php } ?>
                                                    <a class="delete-receipt btn btn-danger btn-xs mr-1 mb-1" title="Delete" href="javascript:void(0)" data-id="<?= $row1['id']; ?>"><i class="fa fa-trash"></i></a>
                                                <?php $i++;
                                                } ?>
                                                <select name="update_receipt_status" id="update_receipt_status" class="form-control status" data-id="<?= $order_detls[0]['id']; ?>" data-user_id="<?= $order_detls[0]['user_id']; ?>">
                                                    <option value=''>Select Status</option>
                                                    <option value="1" <?= (isset($bank_transfer[0]['status']) && $bank_transfer[0]['status'] == 1) ? "selected" : ""; ?>>Rejected</option>
                                                    <option value="2" <?= (isset($bank_transfer[0]['status']) && $bank_transfer[0]['status'] == 2) ? "selected" : ""; ?>>Accepted</option>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (isset($items[0]['product_type']) && $items[0]['product_type'] != 'digital_product') {
                                    $address_number = fetch_details('addresses', 'id =' . $order_detls[0]['address_id'], 'mobile');
                                ?>
                                    <tr>
                                        <th class="w-10px">Address</th>
                                        <td><?php echo $order_detls[0]['address'] .  ' ,Mobile- ' . (!empty($address_number[0]['mobile']) ? $address_number[0]['mobile'] : ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="w-10px">Delivery Date & Time</th>
                                        <td><?php echo (!empty($order_detls[0]['delivery_date']) && $order_detls[0]['delivery_date'] != NUll) ? date('d-M-Y', strtotime($order_detls[0]['delivery_date'])) . " - " . $order_detls[0]['delivery_time'] : "Anytime"; ?></td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <th class="w-10px">Order Date</th>
                                    <td><?php echo date('d-M-Y', strtotime($order_detls[0]['date_added'])); ?></td>
                                </tr>
                            </table>
                            <?php 
                            // echo "<pre>";
                            // print_R($order_detls) ?>
                            <?//= ($order_detls[0]['mobile'] != '' && isset($order_detls[0]['mobile'])) ? $order_detls[0]['mobile'] : ((!defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)  ? str_repeat("X", strlen($mobile_data[0]['mobile']) - 3) . substr($mobile_data[0]['mobile'], -3) : $mobile_data[0]['mobile'])   ?>


                            <a href="https://api.whatsapp.com/send?phone=<?= ($order_detls[0]['country_code'])?><?= ($order_detls[0]['mobile'] != '' && isset($order_detls[0]['mobile'])) ? $order_detls[0]['mobile'] : ((!defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)  ? str_repeat("X", strlen($mobile_data[0]['mobile']) - 3) . substr($mobile_data[0]['mobile'], -3) : $mobile_data[0]['mobile'])   ?>&amp;text=Hello <?= $order_detls[0]['uname'] ?>, Your order with ID : <?= $order_detls[0]['order_id'] ?> and is <?= $order_detls[0]['oi_active_status'] ?>. Please take a note of it. If you have further queries feel free to contact us. Thank you." target="_blank" title="Send Whatsapp Notification For Order" class="btn btn-success"><i class="fa fa-whatsapp"></i> Send Whatsapp Notification</a>
                        </div>
                    </div>
                    <!--/.card-->
                </div>
                <!--/.col-md-12-->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="refund_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="user_name">Payment Refund</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-info">
                            <!-- form start -->
                            <form class="form-horizontal " id="refund_form" action="<?= base_url('admin/orders/refund_payment'); ?>" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <input type="hidden" name=" <?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                                    <input type="hidden" name="item_id" id="item_id">
                                </div>
                                <div class="card-body pad">
                                    <div class="form-group ">
                                        <label for="txn_amount">Refund amount</label>
                                        <!-- Was `disabled`, which also meant the value was never submitted with
                                             the form; the JS read it with .val() so it happened to work, but the
                                             admin could not correct a part-refund either. Editable and bounded
                                             server-side by the line total and by what was actually paid. -->
                                        <input type="number" step="0.01" min="0" class="form-control" name="txn_amount" id="txn_amount" placeholder="Amount" />
                                        <small class="form-text text-muted">
                                            Defaults to this item's line total. Sent back to the original payment method.
                                            An item already refunded to the customer's wallet cannot be refunded again here.
                                        </small>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-secondary" id="submit_btn">Refund</button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group text-danger" id="refund_error"></div>
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
<div class="modal fade" id="ShiprocketOrderFlow" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">How to manage shiprocket order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ">
                <h6><b>Steps:</b></h6>
                <ol>
                    <li> Select Pickup Location for which you want to create parcel and click on <b>Create Shiprocket Order</b> button.</li>
                    <img src="<?= BASE_URL("assets/admin/images/create_order.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <li> After create order generate AWB code(its unique number use for identify order) like this.</li>
                    <img src="<?= BASE_URL("assets/admin/images/generate_awb.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <li> After generate AWB Send pickup request for scheduled you shipping.</li>
                    <img src="<?= BASE_URL("assets/admin/images/send_pickup_request.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <li> Generate and download Label.</li>
                    <img src="<?= BASE_URL("assets/admin/images/generate_label.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <img src="<?= BASE_URL("assets/admin/images/download_label.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <li> Generate and download Invoice.</li>
                    <img src="<?= BASE_URL("assets/admin/images/generate_invoice.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <img src="<?= BASE_URL("assets/admin/images/download_invoice.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <li> Cancel shiprocket order.</li>
                    <img src="<?= BASE_URL("assets/admin/images/cancel_order.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                    <li> shiprocket order traking.</li>
                    <img src="<?= BASE_URL("assets/admin/images/order_tracking.png") ?>" class="img-fluid" alt="Responsive image"><br><br>
                </ol>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-view-order-page .text-primary-theme { color: var(--color-orange); }

    .admin-view-order-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-view-order-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-view-order-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-view-order-page .header-icon.bg-set { background: var(--color-orange); }

    /* The order summary is a label/value table, not a header+rows list table, so it gets its
       own treatment rather than the uppercase-header style used on this page's list tables. */
    .admin-view-order-page .order-detail-table th {
        border-top: none;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        color: var(--color-grey);
        font-weight: 600;
        width: 220px;
        white-space: nowrap;
        vertical-align: top;
        padding-top: 14px;
    }
    .admin-view-order-page .order-detail-table td {
        border-top: none;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        vertical-align: top;
        padding-top: 14px;
    }
    .admin-view-order-page .order-detail-table tr:last-child th,
    .admin-view-order-page .order-detail-table tr:last-child td { border-bottom: none; }

    /* Per-seller and per-item cards used the default AdminLTE "info" skin (a blue accent bar) -
       restyled to the same soft shadow/rounded look used everywhere else in the redesigned panel. */
    .admin-view-order-page .card-info {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .admin-view-order-page .card-info.card-outline { border-top: 3px solid var(--color-orange); }

    .admin-view-order-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }

    /* Same action-button spacing fix applied on every other page this engagement - these rows
       carry several icons (Order Tracking / View Product / Refund / Send Mail) that would
       otherwise crowd or wrap unpredictably. */
    .admin-view-order-page .action-btn { display: inline-block; vertical-align: middle; }
    .admin-view-order-page .grow { transition: box-shadow .15s ease; }
    .admin-view-order-page .grow:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.08); }

    /* Per-item cards. Deliberately NOT using Bootstrap's .card class here - .card is a flex
       container by default, so a stray direct-child link (the old Refund button) stretched to
       fill the full card width instead of sizing to its own content. */
    .admin-view-order-page .order-item-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin: 0;
    }
    .admin-view-order-page .order-item-card {
        display: flex;
        flex-direction: column;
        width: 260px;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        padding: 14px;
        transition: box-shadow .15s ease;
    }
    .admin-view-order-page .order-item-card:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .admin-view-order-page .order-item-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .admin-view-order-page .order-item-select input { width: 16px; height: 16px; margin: 0; cursor: pointer; }
    .admin-view-order-page .order-item-media {
        text-align: center;
        background: #fafafa;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 10px;
    }
    .admin-view-order-page .order-item-media img { max-height: 110px; max-width: 100%; object-fit: contain; }
    .admin-view-order-page .order-item-name {
        font-weight: 600;
        font-size: 14px;
        color: #2b2f33;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    .admin-view-order-page .order-item-meta { margin-bottom: 10px; }
    .admin-view-order-page .oi-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        padding: 4px 0;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        font-size: 13px;
    }
    .admin-view-order-page .oi-row:last-child { border-bottom: none; }
    .admin-view-order-page .oi-label { color: var(--color-grey); }
    .admin-view-order-page .oi-value { color: #2b2f33; font-weight: 500; text-align: right; }
    .admin-view-order-page .order-item-mail-status {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
    }
    .admin-view-order-page .order-item-mail-status select { flex: 1; }
    .admin-view-order-page .order-item-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .admin-view-order-page .order-item-actions .btn { white-space: nowrap; }
</style>