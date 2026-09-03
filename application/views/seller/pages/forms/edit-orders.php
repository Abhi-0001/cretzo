<?php
/*
| This page is the seller's copy of the admin order-detail screen and now shares its look:
| the same header treatment, the same "Order Summary" card and the same per-item cards, via
| the .view-order-page rules in views/shared/view-order-styles.php. It was still stock
| AdminLTE while the admin copy had been redesigned, so the two screens showed identical
| information in two different visual languages.
|
| Two controls are deliberately NOT carried over from the admin copy:
|   * the "Select Delivery Boy" dropdown - this store ships through Shiprocket and has no
|     delivery-boy accounts, so it could only ever offer an empty list;
|   * the "Create Shiprocket Order" button and its parcel modal - booking a shipment is not
|     the seller's to do. Orders are booked automatically at checkout
|     (create_shiprocket_forward_shipment()) and the admin panel keeps the manual control. The
|     seller updates the order status and works the shipment that already exists (AWB, label,
|     invoice, tracking).
*/
?>
<div class="content-wrapper seller-view-order-page view-order-page">
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
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/orders/manage-orders') ?>">Orders</a></li>
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
                    <div class="modal-dialog modal-lg ">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLongTitle">Manage Digital Product</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body ">
                                <form class="form-horizontal form-submit-event" id="digital_product_management" action="<?= base_url('seller/orders/send_digital_product'); ?>" method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <input type="hidden" name="order_id" value="<?= $order_detls[0]['order_id'] ?>">
                                        <input type="hidden" name="order_item_id" value="<?= $this->input->get('edit_id') ?>">
                                        <input type="hidden" name="username" value="<?= $order_detls[0]['uname']  ?>">
                                        <div class="row form-group">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="product_name">Customer Email-ID </label>
                                                    <input type="text" class="form-control" id="email" name="email" value="<?= $order_detls[0]['user_email'] ?>" readonly>
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
                                        <button type="submit" class="btn btn-success mt-3" value="Save"><?= labels('send_mail', 'Send Mail') ?></button>
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
                                            <form class="form-horizontal " id="order_tracking_form" action="<?= base_url('seller/orders/update-order-tracking/'); ?>" method="POST" enctype="multipart/form-data">
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

                <?php
                /* The "Create Shipprocket Order Parcel" modal used to sit here. Sellers no longer
                   book shipments - see the note at the top of this file - so the modal, its
                   #shiprocket_order_parcel_form and the parcel weight/dimension fields are gone
                   along with the button that opened it. The admin panel keeps all of it. */
                ?>
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
                                <?php $this->load->model('Order_model'); ?>
                                <tr>
                                    <input type="hidden" name="hidden" id="order_id" value="<?php echo $order_detls[0]['id']; ?>">

                                    <th class="w-10px">ID</th>
                                    <td><?php echo $order_detls[0]['id']; ?></td>
                                </tr>
                                <tr>
                                    <th class="w-10px">Name</th>
                                    <td><?php echo $order_detls[0]['uname']; ?></td>
                                </tr>
                                <tr>
                                    <th class="w-10px">Email</th>
                                    <td>
                                        <?php if (isset($order_detls[0]['email']) && !empty($order_detls[0]['email']) && $order_detls[0]['email'] != "" && $order_detls[0]['email'] != " ") {
                                            echo ((!defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) || ($this->ion_auth->is_seller() && get_seller_permission($seller_id, 'customer_privacy') == false)) ? str_repeat("X", strlen($order_detls[0]['email']) - 3) . substr($order_detls[0]['email'], -3) : $order_detls[0]['email'];
                                        } ?>
                                    </td>
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
                                        <th class="w-15px">Order note</th>
                                        <td><?php echo  $order_detls[0]['notes']; ?></td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <th class="w-10px">Items</th>

                                    <td>
                                        <form id="update_form">
                                            <input type="hidden" name="order_id" value="<?= $order_detls[0]['order_id'] ?>">
                                            <!-- <input type="hidden" name="seller_id" value="<?= $items[0]['seller_id'] ?>"> -->
                                            <?php if (isset($items[0]['product_type']) && $items[0]['product_type'] == 'digital_product') { ?>
                                                <div class="row">
                                                    <div class="col-md-12 mb-2">
                                                        <lable class="badge badge-success">Select status which you want to update</lable>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="status" class="form-control status">
                                                            <option value=''>Select Status</option>
                                                            <option value="delivered">Delivered</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <a href="javascript:void(0);" title="Bulk Update" data-seller_id="<?= $items[0]['seller_id'] ?>" class="btn btn-primary col-sm-12 col-md-12 update_status_admin_bulk mr-1">
                                                            Update
                                                        </a>
                                                    </div>
                                                </div>
                                                <p>
                                                    <lable class="badge badge-warning mt-2" style="font-size:13px;">Note : Select square box of item only when you want to cancel it. Returns are handled through the customer's return request and the courier pickup.</lable>
                                                </p>
                                            <?php } else { ?>
                                                <?php
                                                /*
                                                 * "Shipped" is no longer gated on the assign_delivery_boy permission.
                                                 *
                                                 * That gate existed because marking an order shipped used to mean handing
                                                 * it to a delivery boy. Here the courier is Shiprocket, so a seller with
                                                 * that permission off could not reach "Shipped" at all and had to jump
                                                 * straight from Processed to Delivered - which silently skips the "your
                                                 * order has shipped" notification to the customer. The endpoint agrees:
                                                 * its own "pick a delivery boy before shipping" rule already lifts itself
                                                 * when the store does not run its own delivery staff.
                                                 */
                                                ?>
                                                <div class="row">
                                                    <div class="col-md-12 mb-2">
                                                        <lable class="badge badge-success">Select the status you want to update</lable>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select name="status" class="form-control status">
                                                            <option value=''>Select Status</option>
                                                            <option value="received">Received</option>
                                                            <option value="processed">Processed</option>
                                                            <option value="shipped">Shipped</option>
                                                            <?php if (get_seller_permission($seller_id, 'view_order_otp') == true) { ?>
                                                                <option value="delivered">Delivered</option>
                                                            <?php } ?>
                                                            <option value="cancelled">Cancel</option>
                                                            <!-- No "Returned" option: a return runs through the customer's
                                                                 return request, an admin decision and the courier's reverse
                                                                 pickup, which is what sets this status. The seller sees the
                                                                 result in the item's status badge. -->
                                                        </select>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <a href="javascript:void(0)" class="edit_order_tracking btn btn-success btn-xl" title="Order Tracking" data-order_id=' <?= $order_detls[0]['id']; ?>' data-seller_id="<?= $items[0]['seller_id'] ?>" data-target="#transaction_modal" data-toggle="modal" style="height:35px;width:38px;"><i class="fa fa-map-marker-alt"></i></a>
                                                        <a href="javascript:void(0);" title="Bulk Update" data-seller_id="<?= $items[0]['seller_id'] ?>" class="btn btn-primary ml-3 col-md-4 update_status_admin_bulk">
                                                            Update
                                                        </a>
                                                    </div>
                                                </div>
                                                <p>
                                                    <lable class="badge badge-warning mt-4" style="font-size:13px;">Note : Select square box of item only when you want to cancel it. Returns are handled through the customer's return request and the courier pickup.</lable>
                                                </p>
                                                <?php
                                                /* The "How to manage shiprocket order" walkthrough is not shown here any
                                                   more: every step in it starts from creating the Shiprocket order, which
                                                   is not something the seller does. The modal markup is left in place at
                                                   the bottom of this file so the guide can be re-linked if that changes. */
                                                ?>

                                                <?php
                                                if (get_seller_permission($seller_id, 'view_order_otp') == true) {
                                                    if ($items[0]['item_otp'] != 0) { ?>
                                                        <p><span class="text-bold">Item OTP : </span><span class="badge badge-warning"><?= $items[0]['item_otp']; ?></span></p>
                                                    <?php } elseif ($items[0]['seller_otp'] != 0) { ?>
                                                        <p><span class="text-bold">Item OTP : </span><span class="badge badge-warning"><?= $items[0]['seller_otp']; ?></span></p>
                                                <?php }
                                                } ?>
                                            <?php } ?>
                                            <?php

                                            $seller_order = $this->Order_model->get_order_details(['o.id' => $order_detls[0]['order_id'], 'oi.seller_id' => $this->session->userdata('user_id')]);

                                            /*
                                             * Resolve each row's pickup location before grouping.
                                             *
                                             * This list came straight from `products`.`pickup_location`, which is BLANK on
                                             * 278 of the 290 products on this store - and the radio below is only rendered
                                             * for a non-empty value, so those items offered no pickup option at all and
                                             * could not be shipped from this screen. The 12 that do carry a name all name
                                             * the one address Shiprocket has never confirmed.
                                             *
                                             * resolve_seller_pickup_location() is what the booking endpoint already uses to
                                             * match items, and it prefers Shiprocket-confirmed addresses. Rewriting the row
                                             * value here means this list, the $ids grouping below and the per-item
                                             * comparison further down all agree with what will actually be booked.
                                             */
                                            foreach ($seller_order as $k => $so_row) {
                                                $resolved_pickup = resolve_seller_pickup_location(
                                                    isset($so_row['pickup_location']) ? $so_row['pickup_location'] : '',
                                                    isset($so_row['seller_id']) ? $so_row['seller_id'] : $this->session->userdata('user_id')
                                                );
                                                if (!empty($resolved_pickup['pickup_location'])) {
                                                    $seller_order[$k]['pickup_location'] = $resolved_pickup['pickup_location'];
                                                }
                                            }

                                            $pickup_location = array_values(array_unique(array_column($seller_order, "pickup_location")));

                                            for ($j = 0; $j < count($pickup_location); $j++) {

                                                $ids = "";
                                                foreach ($seller_order as $row) {

                                                    if ($row['pickup_location'] == $pickup_location[$j]) {
                                                        $ids .= $row['order_item_id'] . ',';
                                                    }
                                                }
                                                $order_item_ids = explode(',', trim($ids, ','));

                                                // get_shipment_id() returns FALSE when this pickup location has no
                                                // shipment yet. That was read as $order_tracking_data[0][...] regardless,
                                                // which then called Shiprocket with an empty order id - two live API
                                                // calls (auth + fetch) on every render of this page for an order that
                                                // has no shipment at all. Only ask Shiprocket about shipments that exist.
                                                $order_tracking_data = get_shipment_id($order_item_ids[0], $order_detls[0]['order_id']);
                                                $order_tracking_data = (!empty($order_tracking_data)) ? $order_tracking_data : [];

                                                // Default shape so the ~10 $shiprocket_order['data']['status'] reads
                                                // further down this page stay warning-free when there is no shipment
                                                // or when the Shiprocket call fails/times out.
                                                $shiprocket_order = ['data' => ['status' => '', 'status_code' => 0]];
                                                if (!empty($order_tracking_data[0]['shiprocket_order_id'])) {
                                                    $fetched_shiprocket_order = get_shiprocket_order($order_tracking_data[0]['shiprocket_order_id']);
                                                    if (!empty($fetched_shiprocket_order['data'])) {
                                                        $shiprocket_order = $fetched_shiprocket_order;
                                                        $shiprocket_order['data'] += ['status' => '', 'status_code' => 0];
                                                    }

                                                    // Status changes now go through the same shared helper the Shiprocket
                                                    // webhook uses, instead of being reimplemented here. The old inline
                                                    // version only knew four Shiprocket statuses, and it set $type inside
                                                    // the item loop without ever resetting it - so once one item changed
                                                    // status, every later item in the loop re-sent the same customer
                                                    // notification even though nothing about it had changed.
                                                    sync_shiprocket_shipment_status($order_tracking_data[0], $shiprocket_order['data']['status']);
                                                }

                                            ?>
                                                <?php if ($shipping_method['shiprocket_shipping_method'] == 1 && isset($pickup_location[$j]) && !empty($pickup_location[$j]) && $pickup_location[$j] != 'NULL') { ?>
                                                    <div class="row">
                                                        <?php
                                                        /* The pickup-location radio button that used to sit here existed only to
                                                           choose which parcel to book with Shiprocket. Sellers no longer book
                                                           shipments, so the location is shown as plain text. */
                                                        ?>
                                                        <div class="col-md-6 m-2 text-left mt-3">
                                                            <strong>

                                                                <p class="mb-0">Pickup Location :
                                                            </strong>
                                                            <?= ucfirst($pickup_location[$j]) ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="row m-2 ml-6">
                                                        <div class="col-sm-0 ml-4 m-2"></div>
                                                        <?php if (isset($order_tracking_data[0]['shipment_id']) && !empty($order_tracking_data[0]['shipment_id']) && empty($order_tracking_data[0]['is_canceled']) && $order_tracking_data[0]['is_canceled'] != 1 && $shiprocket_order['data']['status'] != 'CANCELED') { ?>
                                                            <div class="col-md-1">
                                                                <span class="badge bg-success ml-1">Order created</span>
                                                            </div>
                                                        <?php } ?>
                                                        <?php // Same as above - $item is not in scope here. */ ?>
                                                        <?php if (isset($items[0]['product_type']) && ($items[0]['product_type'] != 'digital_product')) {  ?>
                                                            <?php if (!isset($order_tracking_data[0]['shipment_id']) && empty($order_tracking_data[0]['shipment_id'])) { ?>
                                                                <div class="col-md-1">
                                                                    <span class="badge bg-primary ml-1">Order not created</span>
                                                                </div>
                                                        <?php }
                                                        } ?>

                                                        <?php if ((isset($order_tracking_data[0]['is_canceled']) && $order_tracking_data[0]['is_canceled'] != 0) || $shiprocket_order['data']['status'] == 'CANCELED') { ?>
                                                            <div class="col-md-1">
                                                                <span class="badge bg-danger ml-1">Order cancelled</span>
                                                            </div>
                                                        <?php  } ?>
                                                        <div class="col-md-5">
                                                            <?php if (isset($order_tracking_data[0])) { ?>
                                                                <?php if (isset($order_tracking_data[0]['shipment_id']) && (empty($order_tracking_data[0]['awb_code']) || $order_tracking_data[0]['awb_code'] == 'NULL') && $shiprocket_order['data']['status'] != 'CANCELED') { ?>
                                                                    <a href="" title="Generate AWB" class="btn btn-primary btn-xs mr-1 generate_awb" data-fromseller="1" id=<?php print_r($order_tracking_data[0]['shipment_id']); ?>>AWB</a>
                                                                <?php } else { ?>
                                                                    <?php if (empty($order_tracking_data[0]['pickup_scheduled_date']) && ($shiprocket_order['data']['status_code'] != 4 || $shiprocket_order['data']['status'] != 'PICKUP SCHEDULED') && $shiprocket_order['data']['status'] != 'CANCELED' && $shiprocket_order['data']['status'] != 'CANCELLATION REQUESTED') { ?>
                                                                        <a href="" title="Send Pickup Request" class="btn btn-primary btn-xs mr-1 send_pickup_request" data-fromseller="1" name=<?php print_r($order_tracking_data[0]['shipment_id']); ?>><i class="fas fa-shipping-fast "></i></a>
                                                                    <?php }
                                                                    if (isset($order_tracking_data[0]['is_canceled']) && $order_tracking_data[0]['is_canceled'] == 0) { ?>
                                                                        <a href="" title="Cancel Order" class="btn btn-primary btn-xs mr-1 cancel_shiprocket_order" data-fromseller="1" name=<?php print_r($order_tracking_data[0]['shiprocket_order_id']); ?>><i class="fas fa-redo-alt"></i></a>
                                                                    <?php } ?>

                                                                    <?php if (isset($order_tracking_data[0]['label_url']) && !empty($order_tracking_data[0]['label_url'])) { ?>
                                                                        <a href="<?php print_r($order_tracking_data[0]['label_url']); ?>" title="Download Label" data-fromseller="1" class="btn btn-primary btn-xs mr-1 download_label"><i class="fas fa-download"></i> Label</a>
                                                                    <?php } else { ?>
                                                                        <a href="" title="Generate Label" class="btn btn-primary btn-xs mr-1 generate_label" data-fromseller="1" name=<?php print_r($order_tracking_data[0]['shipment_id']); ?>><i class="fas fa-tags"></i></a>
                                                                    <?php } ?>

                                                                    <?php if (isset($order_tracking_data[0]['invoice_url']) && !empty($order_tracking_data[0]['invoice_url'])) { ?>
                                                                        <a href="<?php print_r($order_tracking_data[0]['invoice_url']); ?>" data-fromseller="1" title="Download Invoice" class="btn btn-primary  btn-xs mr-1 download_invoice"><i class="fas fa-download"></i> Invoice</a>
                                                                    <?php } else { ?>
                                                                        <a href="" title="Generate Invoice" class="btn btn-primary btn-xs mr-1 generate_invoice" data-fromseller="1" name=<?php print_r($order_tracking_data[0]['shiprocket_order_id']); ?>><i class="far fa-money-bill-alt"></i></a>
                                                                    <?php }
                                                                    if (isset($order_tracking_data[0]['awb_code']) && !empty($order_tracking_data[0]['awb_code'])) { ?>
                                                                        <a href="https://shiprocket.co/tracking/<?php echo $order_tracking_data[0]['awb_code']; ?>" target=" _blank" title="Track Order" class="btn btn-primary action-btn btn-xs mr-1 track_order" name=<?php print_r($order_tracking_data[0]['shiprocket_order_id']); ?>><i class="fas fa-map-marker-alt"></i></a>
                                                                    <?php } ?>
                                                                <?php } ?>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <?php

                                                $total = 0;
                                                $tax_amount = 0;
                                                echo '<div class="order-item-grid">';
                                                foreach ($items as $item) {

                                                    $selected = "";
                                                    $item['discounted_price'] = ($item['discounted_price'] == '') ? 0 : $item['discounted_price'];
                                                    $total += $subtotal = ($item['quantity'] != 0 && ($item['discounted_price'] != '' && $item['discounted_price'] > 0) && $item['price'] > $item['discounted_price']) ? ($item['price'] - $item['discounted_price']) : ($item['price'] * $item['quantity']);
                                                    $tax_amount += $item['tax_amount'];
                                                    $total += $subtotal = $tax_amount;
                                                    // $total += $subtotal;

                                                    /*
                                                     * Compare on the RESOLVED pickup location, not the raw column.
                                                     *
                                                     * $pickup_location is built from $seller_order AFTER the loop above
                                                     * rewrites each row to its resolved address, but $items is a separate
                                                     * array from the controller that still carries the raw
                                                     * products.pickup_location - blank on 278 of the 290 products here. So
                                                     * the two sides could never match and this whole loop rendered nothing:
                                                     * the seller saw an EMPTY item grid on every order, with no way to see
                                                     * what they had sold or to tick an item for cancellation. Verified
                                                     * against the admin copy of this page, which does not rewrite its list
                                                     * and renders all three cards for the same order.
                                                     *
                                                     * The raw comparison is kept as an alternative rather than replaced, so
                                                     * an item whose column already names the right address still matches even
                                                     * if resolution comes back empty (Shiprocket off, no pickup rows).
                                                     */
                                                    $item_pickup = resolve_seller_pickup_location(
                                                        isset($item['pickup_location']) ? $item['pickup_location'] : '',
                                                        isset($item['seller_id']) ? $item['seller_id'] : $seller_id
                                                    );
                                                    $item_pickup_name = isset($item_pickup['pickup_location']) ? $item_pickup['pickup_location'] : '';
                                                    if ($pickup_location[$j] == $item_pickup_name || $pickup_location[$j] == $item['pickup_location']) {
                                                ?>
                                                        <?php
                                                        $badges = ["awaiting" => "secondary", "received" => "primary", "processed" => "info", "shipped" => "warning", "delivered" => "success", "returned" => "danger", "cancelled" => "danger", "return_request_approved" => "success", "return_request_decline" => "danger", "return_request_pending" => "warning"];
                                                        $item_badge = isset($badges[$item['active_status']]) ? $badges[$item['active_status']] : 'secondary';
                                                        ?>
                                                        <div class="order-item-card">
                                                            <div class="order-item-card-top">
                                                                <?php // $sellers/$i exist only in the admin copy of this page, where the
                                                                // outer loop walks every seller on the order. In the seller panel there
                                                                // is exactly one seller - the logged-in one. ?>
                                                                <label class="order-item-select" title="Select to mark as cancelled">
                                                                    <input type="checkbox" id="<?= $seller_id ?>" name="order_item_id" value=' <?= $item['id'] ?> '>
                                                                </label>
                                                                <span class="badge badge-<?= $item_badge ?>"><?= str_replace('_', ' ', $item['active_status']) ?></span>
                                                            </div>
                                                            <div class="order-item-media">
                                                                <a href='<?= base_url() . $item['product_image'] ?>' data-toggle='lightbox' data-gallery='order-images'> <img src='<?= base_url() . $item['product_image'] ?>'></a>
                                                            </div>
                                                            <div class="order-item-name"><?= $item['pname'] ?></div>

                                                            <!-- <?php if (isset($item['product_type']) && $item['product_type'] != 'digital_product') {
                                                                        if (get_seller_permission($seller_id, 'view_order_otp') == true) {
                                                                            if ($item['item_otp'] != 0) { ?>
                                                                <div><span class="text-bold">Item OTP : </span><span class="badge badge-warning"><?= $item['item_otp']; ?></span></div>
                                                            <?php } elseif ($item['seller_otp'] != 0) { ?>
                                                                <div><span class="text-bold">Item OTP : </span><span class="badge badge-warning"><?= $item['seller_otp']; ?></span></div>
                                                    <?php }
                                                                        }
                                                                    } ?> -->
                                                            <div class="order-item-meta">
                                                                <div class="oi-row"><span class="oi-label">Type</span><span class="oi-value"><?= ucwords(str_replace('_', ' ', $item['product_type'])); ?></span></div>
                                                                <div class="oi-row"><span class="oi-label">Variant ID</span><span class="oi-value"><?= $item['product_variant_id'] ?></span></div>
                                                                <?php if (isset($item['product_variants']) && !empty($item['product_variants'])) { ?>
                                                                    <div class="oi-row"><span class="oi-label">Variants</span><span class="oi-value"><?= str_replace(',', ' | ', $item['product_variants'][0]['variant_values']) ?></span></div>
                                                                <?php } ?>
                                                                <div class="oi-row"><span class="oi-label">Quantity</span><span class="oi-value"><?= $item['quantity'] ?></span></div>
                                                                <?php
                                                                    /* Why the customer cancelled or returned it. Collected by the storefront's
                                                                     * My Account > Orders popup and stored on the item (migration 076); the old
                                                                     * flow asked nothing at all, so a return could only ever be counted, never
                                                                     * explained. This is the seller's copy of the same row the admin order page
                                                                     * shows - the seller is the one who has to act on it. */
                                                                    if (!empty($item['return_reason'])) { ?>
                                                                    <div class="oi-row">
                                                                        <span class="oi-label">Customer reason</span>
                                                                        <span class="oi-value" style="font-weight:600">
                                                                            <?= html_escape($item['return_reason']) ?>
                                                                            <?php if (!empty($item['return_reason_at'])) { ?>
                                                                                <small class="text-muted d-block"><?= date('d M Y, g:i a', strtotime($item['return_reason_at'])) ?></small>
                                                                            <?php } ?>
                                                                        </span>
                                                                    </div>
                                                                <?php } ?>
                                                                <div class="oi-row"><span class="oi-label">Price</span><span class="oi-value"><?= $item['price'] + $item['tax_amount'] ?></span></div>
                                                                <div class="oi-row"><span class="oi-label">Discounted Price</span><span class="oi-value"><?= $item['discounted_price'] ?></span></div>
                                                                <div class="oi-row"><span class="oi-label">Subtotal</span><span class="oi-value"><?= $item['price'] * $item['quantity'] ?></span></div>
                                                                <?php if (isset($item['product_type']) && $item['product_type'] != 'digital_product') { ?>
                                                                    <div class="oi-row"><span class="oi-label">Pickup Location</span><span class="oi-value"><?= $item['pickup_location'] ?></span></div>
                                                                    <?php if (!empty($order_tracking_data[0]['shipment_id'])) { ?>
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
                                                                    <a href="javascript:void(0)" class="btn btn-warning btn-xs action-btn" data-target="#sendMailModal" data-toggle="modal" title="Edit" data-id="<?= $item['id'] ?>" data-url="seller/orders/">
                                                                        <i class="fas fa-paper-plane"></i>
                                                                    </a>
                                                                    <a href="https://mail.google.com/mail/?view=cm&fs=1&tf=1&to=<?= $item['user_email'] ?>" class="btn btn-danger btn-xs action-btn" target="_blank">
                                                                        <i class="fab fa-google"></i>
                                                                    </a>
                                                                </div>
                                                            <?php } ?>

                                                            <div class="order-item-actions">
                                                                <a href=" <?= BASE_URL('seller/product/view-product?edit_id=' . $item['product_id'] . '') ?> " title="View Product" class="btn btn-primary btn-xs">
                                                                    <i class="fa fa-eye"></i> View Product
                                                                </a>
                                                            </div>
                                                        </div>
                                            <?php
                                                    }
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                            <div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="w-10px">Total(<?= $settings['currency'] ?>)</th>
                                    <td id=' amount'><?php echo $total; ?></td>
                                </tr>

                                <tr class="d-none">
                                    <th class="w-10px">Tax(<?= $settings['currency'] ?>)</th>
                                    <td id='amount'><?php echo $tax_amount; ?></td>
                                </tr>
                                <?php if (isset($items[0]['product_type']) && $items[0]['product_type'] != 'digital_product') { ?>
                                    <tr>
                                        <th class="w-10px">Delivery Charge(<?= $settings['currency'] ?>)</th>
                                        <td id='delivery_charge'>
                                            <?php echo $items[0]['seller_delivery_charge'];
                                            $total = $total + $order_detls[0]['delivery_charge']; ?>
                                        </td>
                                    </tr>
                                    <?php
                                    /*
                                     * The courier's actual freight for this parcel, which under the
                                     * seller-paid shipping model is deducted from this seller's
                                     * settlement. Distinct from "Delivery Charge" above, which is what
                                     * the CUSTOMER was billed - and is 0 under this model. Shown only
                                     * once a figure has been captured from Shiprocket (at AWB
                                     * assignment, or by the reconciliation cron): a 0 here before then
                                     * means "not yet known", not "free", and stating it as a number
                                     * would misrepresent the payout.
                                     */
                                    $seller_freight = isset($items[0]['seller_freight_charge']) ? (float) $items[0]['seller_freight_charge'] : 0;
                                    if ($seller_freight > 0) { ?>
                                        <tr>
                                            <th class="w-10px">Shipping Freight(<?= $settings['currency'] ?>) <small class="text-muted">(deducted from your settlement)</small></th>
                                            <td id='freight_charge'>- <?= number_format($seller_freight, 2) ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                                <!-- <tr>
                                    <th class="w-10px">Wallet Balance(<?= $settings['currency'] ?>)</th>
                                    <td><?php echo $order_detls[0]['wallet_balance'];
                                        // $total = $total - $order_detls[0]['wallet_balance']; 
                                        ?></td>
                                </tr> -->
                                <input type="hidden" name="total_amount" id="total_amount" value="<?php echo $order_detls[0]['order_total'] + $order_detls[0]['delivery_charge'] ?>">
                                <input type="hidden" name="final_amount" id="final_amount" value="<?php echo $order_detls[0]['final_total']; ?>">
                                <tr>
                                    <th class="w-10px">Promo Code Discount (<?= $settings['currency'] ?>)</th>
                                    <td><?php echo $items[0]['seller_promo_discount'];
                                        $total = floatval($total -
                                            $order_detls[0]['promo_discount']); ?></td>
                                </tr>
                                <?php
                                if (isset($order_detls[0]['discount']) && $order_detls[0]['discount'] > 0) {
                                    $discount = $order_detls[0]['total_payable']  *  ($order_detls[0]['discount'] / 100);
                                    $total = round($order_detls[0]['total_payable'] - $discount, 2);
                                }
                                ?>
                                <!-- <tr>
                                    <th class="w-10px">Payable Total(<?= $settings['currency'] ?>)</th>
                                    <td><input type="text" class="form-control" id="final_total" name="final_total" value="<?= $total; ?>" disabled></td>
                                </tr> -->
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
                                                <?php $i = 1;
                                                foreach ($bank_transfer as $row1) { ?>
                                                    <small>[<a href="<?= base_url() . $row1['attachments'] ?>" target="_blank">Attachment <?= $i ?> </a>] </small>
                                                    <a class="delete-receipt btn btn-danger btn-xs mr-1 mb-1" title="Delete" href="javascript:void(0)" data-id="<?= $row1['id']; ?>"><i class="fa fa-trash"></i></a>
                                                <?php $i++;
                                                } ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (isset($items[0]['product_type']) && $items[0]['product_type'] != 'digital_product') { ?>
                                    <tr>
                                        <th class="w-10px">Address</th>
                                        <td><?php echo $order_detls[0]['address']; ?></td>
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
<?php $this->load->view('shared/view-order-styles'); ?>
<?php
/* The "How to manage shiprocket order" walkthrough modal used to sit here.

   Every step in it starts from clicking "Create Shiprocket Order", which sellers no longer
   do - so with the button gone the guide was both unreachable (nothing opened it) and wrong
   (it described a control that is not on the page). The admin copy of this page still has
   the guide, and that is where booking now happens. */
?>
