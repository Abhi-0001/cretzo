<?php

/* echo '<pre>';
print_r(var_dump($order));
die; */

$order_item = '';
$url = $_SERVER['REQUEST_URI']; // Get the current URL
$url_parts = explode('/', $url); // Split the URL by '/'
$last_part = end($url_parts); // Get the last part of the URL

// Find the order item with the matching ID
foreach ($order['order_items'] as $item) {
    if ($item['id'] == $last_part) {
        $order_item = $item;
        break; 
    }
}

/* echo '<pre>';
print_r(var_dump($order_item));
die; */

if(empty($order_item)){
    echo '<pre>';
    echo 'Something broke :( <br>';
    echo 'Please contact support.';
    die;
}

?>

<!-- products starts -->
<section class="accounts-container">
    <!-- order detail -->
    <div class="overview-side-container">
        <h1 class="heading-b">Account</h1>
        <p class="text-n"><?= $users->username ?></p>
        <div class="overview-container">

            <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>

            <div class="overview-right">
                
                <div class="d-flex flex-col my-auto">
                    <h6 class="mr-3 mb-2">
                        <a href="<?= base_url('my-account/orders/') ?>" class='btn btn-sm btn-outline-primary'> <i class="uil uil-arrow-left fs-20" style="margin: -100px 0px;"></i> Back to List </a>
                        <a target="_blank" href="<?= base_url('my-account/order-invoice/' . $order['id']) ?>" class='btn btn-sm btn-outline-primary'> <i class="uil uil-invoice fs-20 mr-1" style="margin: -100px 0px;"></i> Get Invoice </a>
                    </h6>
                </div>

                <div class="order-details-container">
                    <div class="order-details-one">
                        <p class="text-n help-text flex"><span class="flex-1"></span><span class="c-p">Help <img class="help-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/help-center-icon.png') ?>"></span></p>
                    </div>
                    <div class="order-details-two">
                        <img class="product-img" src="<?= isset($order_item['variant_image']) && !empty($order_item['variant_image']) ? $order_item['variant_image'] : $order_item['image'] ?>">
                        
                        <h1 class="sub-heading mt-2"><?= $item['product_name'] ?></h1>
                        <p class="text-n"><?= $item['short_description'] ?></p>

                        <!-- Print product attributes -->
                        <?php
                            if(!empty($item['attr_name'])){
                                $vari_names = explode(", ", $item['attr_name']);
                                $vari_values = explode(", ", $item['variant_values']);
                                $product_attributes = array_combine($vari_names, $vari_values);
                                
                                foreach ($product_attributes as $name => $value) { ?>
                                    <p class="text-n"><?= htmlspecialchars($name) ?>: <?= htmlspecialchars($value) ?></p>
                                <?php }
                            }
                        ?>

                        <p class="text-muted"> <?= !empty($this->lang->line('quantity')) ? $this->lang->line('quantity') : 'Quantity' ?> : <?= $item['quantity'] ?></p>
                        
                        <p class="mb-1"></p>

                        <div class="delivery-date <?=$order_item['active_status']?>">
                            <img class="delivered-icon" src="<?= base_url("assets/front_end/cretzo/img/new_cretzo/{$order_item['active_status']}.png") ?>">
                            <div class="ml-2" style="text-align: start;">
                                <p class="text-s"><?= ucfirst($order_item['active_status']) ?></p>
                                <!-- <p class="sub-heading delivered-text"><?= ucwords($order_item['active_status']) ?></h1>                                     -->
                                <p class="text-es delivered-date">On <?= orderStatusTimeToHumanReadableString($order_item['status'][array_key_last($order_item['status'])][1]) ?></p>
                            </div>
                            
                            <a class="cretzo link ml-auto mr-2 mt-1" data-bs-toggle="modal" data-bs-target="#orderStatusModal">
                                <p class="text-s">View Details</p>
                            </a>
                        </div>
                    </div>
                    
                    
                    <div class="order-details-three">
                        <ul>
                            <?php
                            $delivered_index = array_search('delivered', $order_item['status']);
                            if($delivered_index !== false){
                                $delivered_date = $order_item['status'][$delivered_index][1];
                            }

                            if (!$order_item['is_returnable'] || $order_item['type'] == 'digital_product') { ?>
                                <li class="text-n">Return not available for this product.</li>
                            <?php }
                            else if ($order_item['active_status'] == 'cancelled' || $order_item['active_status'] == 'returned') { ?>
                                <li class="text-n">Order has been <?=$order_item['active_status']?>.</li>
                            <?php }
                            else if (isset($delivered_date) && !empty($delivered_date) /* && !$order_item['is_already_returned'] */) { ?>
                                <?php
                                $settings = get_settings('system_settings', true);
                                $today = date('d-m-Y');
                                $return_till = date('d-m-Y', strtotime($delivered_date . ' + ' . $settings['max_product_return_days'] . ' days'));
                                if ($today < $return_till) { ?>
                                    <li class="text-n">Return window will close on <?= $return_till ?></li>
                                <?php 
                                } else { ?>
                                    <li class="text-n">Return window closed on <?= $return_till ?></li>
                                <?php 
                                }
                            }
                            else { 
                                $settings = get_settings('system_settings', true);
                            ?>
                                <li class="text-n">Return window will be open for <?=$settings['max_product_return_days']?> day/s from the day of delivery.</li>
                            <?php } ?>
                            
                        </ul>
                    </div>

                    <div class="order-details-four">
                        <img class="product-img" src="<?= isset($order_item['variant_image']) && !empty($order_item['variant_image']) ? $order_item['variant_image'] : $order_item['image'] ?>">
                        <div>
                            <h1 class="text-n">Rate the Product</h1>

                            <div class="ml-0 pl-0 product-rating-small mt-1" dir="ltr">
                                <input id="input" name="rating" class="rating rating-loading d-none" data-size="xs" value="<?= $order_item['product_rating'] ?>" data-show-clear="false" data-show-caption="false" readonly>
                            </div>
                            <!-- <p class="text-s">
                                <?php 
                                for ($i = 0; $i < 5; $i++) {
                                    if($i < $order_item['product_rating']) { ?>
                                        <img class="star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>">
                                <?php 
                                    } else { ?>
                                        <img class="star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star-grey.png') ?>">
                                <?php
                                    }
                                } ?>
                            </p> -->
                            
                        </div>
                    </div>
                    <div class="order-details-five">
                        <h1 class="text-n heading">Delivery Address</h1>
                        <h1 class="text-n"><?= $order['order_recipient_person'] ?> : <?= $order['mobile'] ?></h1>
                        <p class="text-n address-text"><?= $order['address'] ?></p>
                    </div>
                    <div class="order-details-six">
                        <div class="order-details-six-left">
                            <h1 class="text-n heading">Total Order Price</h1>
                            <p class="text-n">You saved <span class="green">₹ <?= $order_item['main_price'] - $order_item['special_price'] ?></span> on this order</p>
                        </div>
                        <h1 class="sub-heading order-details-six-right"><h4 class="mt-1 bold"> <span class="mt-5"><i><?= $settings['currency'] ?></i></span> <?= number_format($order_item['sub_total'], 2) ?> <span class="small text-muted"> <?= !empty($this->lang->line('via')) ? $this->lang->line('via') : 'via' ?> (<?= $order['payment_method'] ?>) </span></h4></h1>
                    </div>
                    <div class="order-details-seven">
                        <!-- <img class="visa-icon-two" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/visa-icon2.png') ?>">
                        <p class="text-n flex-1">Paid By ICICI card ending in **** **** 0003</p> -->
                        <p class="text-n">Item sold by: <span class="orange fw-bold"><?= $order_item['store_name'] ?></span> </p>
                        <a target="_blank" href="<?= base_url('my-account/order-invoice/' . $order['id']) ?>" class='text-decoration-none w-100 mt-2'> <button class="cretzo btn light-btn w-100 get-invoice">Get Invoice</button> </a>
                    </div>
                    <div class="order-details-five">
                        <h1 class="text-n heading">Courier Details</h1>
                        <!-- <h1 class="text-n"><?= $order['order_recipient_person'] ?> : <?= $order['mobile'] ?></h1>
                        <p class="text-n address-text"><?= $order['address'] ?></p> -->
                        <ul class="mb-0">
                            <?php if (isset($order_item['courier_agency']) && !empty($order_item['courier_agency'])) { ?>
                                <p> <span class="text-muted"> <?= !empty($this->lang->line('courier_agency')) ? $this->lang->line('courier_agency') : 'Courier Agency' ?> : </span><a href="<?= $order_item['url'] ?>" title="click here to trace the order"><?= $order_item['courier_agency'] ?></a> </p>
                                <p class="text-muted" data-toggle="tooltip" data-placement="top" title="Copy this Tracking ID and trace your order with Courier Agency."> <?= !empty($this->lang->line('tracking_id')) ? $this->lang->line('tracking_id') : 'Tracking ID' ?> <span class="font-weight-bold text-dark"> : <?= $order_item['tracking_id'] ?></span> </p>
                            <?php } else { ?>
                                <p class="text-muted"> Tracking will be available once your order has been shipped </p>
                            <?php } ?>
                        </ul>
                    </div>
                    <div class="order-details-eight">
                        <h1 class="text-n heading">Updates sent to</h1>
                        <p class="text-n"><i class="uil uil-phone"></i> +91 <?= $order['mobile'] ?></p>
                        <p class="text-n"><i class="uil uil-envelope-alt"></i> <?= $order['email'] ?></p>
                    </div>
                    <div class="order-details-nine">
                        <p class="text-n">Order Id: <span class="font-weight-bold text-dark"><?= $order['id'] ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- products ends -->

<hr> 
<div style="display: none;"> 
    <!-- Demo header-->
    <section class="header settings-tab">
        <div class="container pb-15">
            <div class="row">
                <div class="col-md-12 orders-section settings-tab-content">
                    <div class="mb-4 border-0 shadow-xl p-10">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between">
                                <div class="col">
                                    <p class="text-muted"> <?= !empty($this->lang->line('order_id')) ? $this->lang->line('order_id') : 'Order ID' ?><span class="font-weight-bold text-dark"> : <?= $order['id'] ?></span></p>
                                    <p class="text-muted"> <?= !empty($this->lang->line('place_on')) ? $this->lang->line('place_on') : 'Place On' ?><span class="font-weight-bold text-dark"> : <?= $order['date_added'] ?></span> </p>
                                </div>

                                <div class="flex-col my-auto">
                                    <h6 class="ml-auto mr-3">
                                        <a target="_blank" href="<?= base_url('my-account/order-invoice/' . $order['id']) ?>" class='btn btn-sm btn-outline-primary'><?= !empty($this->lang->line('invoice')) ? $this->lang->line('invoice') : 'Invoice' ?></a>
                                        <a href="<?= base_url('my-account/orders/') ?>" class='btn btn-sm btn-outline-danger'><?= !empty($this->lang->line('back_to_list')) ? $this->lang->line('back_to_list') : 'Back to List' ?></a>
                                    </h6>
                                </div>
                            </div>
                            <br>
                        </div>
                        <div class="card-body">
                            <?php foreach ($order['order_items'] as $key => $item) { ?>

                                <div class="media flex-column flex-sm-row">
                                    <div class="media-body ">

                                        <a href="<?= base_url('products/details/' . $item['slug']) ?>" class="text-decoration-none">
                                            <h5 class="bold"><?= ($key + 1) . '. ' . $item['name'] ?></h5>
                                        </a>
                                        <p class="text-muted"> <?= !empty($this->lang->line('quantity')) ? $this->lang->line('quantity') : 'Quantity' ?> : <?= $item['quantity'] ?></p>
                                        <?php if ($item['otp'] != 0) { ?>
                                            <p class="text-muted"> <?= !empty($this->lang->line('otp')) ? $this->lang->line('otp') : 'OTP' ?> <span class="font-weight-bold text-dark"> : <?= $item['otp'] ?></span> </p>
                                        <?php } ?>
                                        <?php if (isset($item['courier_agency']) && !empty($item['courier_agency'])) { ?>
                                            <p> <span class="text-muted"> <?= !empty($this->lang->line('courier_agency')) ? $this->lang->line('courier_agency') : 'Courier Agency' ?> : </span><a href="<?= $item['url'] ?>" title="click here to trace the order"><?= $item['courier_agency'] ?></a> </p>
                                            <p class="text-muted" data-toggle="tooltip" data-placement="top" title="Copy this Tracking ID and trace your order with Courier Agency."> <?= !empty($this->lang->line('tracking_id')) ? $this->lang->line('tracking_id') : 'Tracking ID' ?> <span class="font-weight-bold text-dark"> : <?= $item['tracking_id'] ?></span> </p>
                                        <?php } ?>
                                        <h4 class="mt-3 mb-2 bold"> <span class="mt-5"><i><?= $settings['currency'] ?></i></span> <?= number_format(($item['price'] * $item['quantity']), 2) ?> <span class="small text-muted"></span></h4>
                                        <?php
                                        $status = ["awaiting", "received", "processed", "shipped", "delivered", "cancelled", "returned"];
                                        $cancelable_till = $item['cancelable_till'];
                                        $active_status = $item['active_status'];
                                        $cancellable_index = array_search($cancelable_till, $status);
                                        $active_index = array_search($active_status, $status);
                                        if (!$item['is_already_cancelled'] && $item['is_cancelable'] && $active_index <= $cancellable_index && $item['type'] != 'digital_product') { ?>
                                            <button class="btn btn-danger btn-sm update-order-item" data-status="cancelled" data-item-id="<?= $item['id'] ?>"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                                        <?php } ?>
                                        <?php $order_date = $order['order_items'][0]['status'][3][1];
                                        if ($order['is_returnable'] && !$order['is_already_returned'] && isset($order_date) && !empty($order_date)) { ?>
                                            <?php
                                            $settings = get_settings('system_settings', true);
                                            $timestemp = strtotime($order_date);
                                            $date = date('Y-m-d', $timestemp);
                                            $today = date('Y-m-d');
                                            $return_till = date('Y-m-d', strtotime($order_date . ' + ' . $settings['max_product_return_days'] . ' days'));
                                            echo "<br>";
                                            if ($today < $return_till && $item['type'] != 'digital_product') { ?>
                                                <div class="col my-auto ">
                                                    <a class="update-order block btn btn-sm btn-danger text-white mt-3 m-0" data-status="returned" data-order-id="<?= $order['id'] ?>"><?= !empty($this->lang->line('return')) ? $this->lang->line('return') : 'Return' ?></a>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if ($item['type'] == 'digital_product' &&  $item['download_allowed'] == 1 && ($item['active_status'] == 'received' || $item['active_status'] == 'delivered')) {
                                            $download_link = $item['hash_link'];
                                        ?>
                                            <div class="media-body mt-3">
                                                <a href="<?= base_url('products/download_link_hash/' . $item['id']) ?>" title="Download Product" class="btn btn-outline-info"><i class="uil uil-download-alt"></i> Download</a>
                                            </div>
                                        <?php }
                                        if ($item['type'] == 'digital_product' &&  $item['download_allowed'] == 0) { ?>
                                            <div class="media-body mt-3">
                                                <span class="text-danger">You will receive this item from seller via email.</span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="align-self-center img-fluid">
                                        <a href="<?= base_url('products/details/' . $item['slug']) ?>"><img src="<?= $item['image_sm'] ?>" width="180 " height="180" style="object-fit: cover;"></a>
                                    </div>
                                </div>

                                <?php if ($item['type'] != 'digital_product') { ?>
                                    <section class="wrapper bg-light">
                                        <div class="container py-14 py-md-16">
                                            <div class="row gx-lg-8 gx-xl-12 gy-6 process-wrapper line" id="progressbar">
                                                <?php
                                                $status = array('received', 'processed', 'shipped', 'delivered');
                                                $i = 1;
                                                // echo "<pre>";
                                                // print_R($item['status']);
                                                foreach ($item['status'] as $value) { ?>
                                                    <?php
                                                    $class = '';
                                                    if ($value[0] == "cancelled" || $value[0] == "returned" || $value[0] == "return_request_pending") {
                                                        $class = 'cancel';
                                                        $status = array();
                                                    } elseif (($ar_key = array_search($value[0], $status)) !== false) {
                                                        unset($status[$ar_key]);
                                                    }
                                                    ?>
                                                    <div class="col-md-6 col-lg-3 active <?= $class ?>">
                                                        <span class="icon btn btn-circle btn-lg btn-primary pe-none mb-4">
                                                            <span class="number" id="step<?= $i ?>"></span>
                                                        </span>
                                                        <h4 class="mb-1"><?= str_replace('_', ' ', strtoupper($value[0]))  ?></h4>
                                                        <p class="mb-0"><?= $value[1] ?></p>
                                                    </div>
                                                    <!--/column -->
                                                <?php
                                                    $i++;
                                                } ?>

                                                <?php

                                                foreach ($status as $value) { ?>
                                                    <div class="col-md-6 col-lg-3">
                                                        <span class="icon btn btn-circle btn-lg btn-soft-primary pe-none mb-4">
                                                            <span class="number" id="step<?= $i ?>"></span>
                                                        </span>
                                                        <p class="mb-0"><?= str_replace('_', ' ', strtoupper($value)) ?></p>
                                                    </div>
                                                <?php $i++;
                                                } ?>

                                            </div>
                                            <!--/.row -->
                                        </div>
                                        <!-- /.container -->
                                    </section>
                                    <!-- /section -->
                                <?php } ?>
                                <hr class="mt-5 mb-5">
                            <?php  } ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="h5"><?= !empty($this->lang->line('shipping_details')) ? $this->lang->line('shipping_details') : 'Shipping Details' ?></h6>
                                    <hr class="mt-5 mb-5">
                                    <span><?= $order['username'] ?></span> <br>
                                    <span><?= $order['address'] ?></span> <br>
                                    <span><?= $order['mobile'] ?></span> <br>
                                    <span><?= $order['delivery_time'] ?></span> <br>
                                    <span><?= $order['delivery_date'] ?></span> <br>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="h5"><?= !empty($this->lang->line('price_details')) ? $this->lang->line('price_details') : 'Price Details' ?></h6>
                                    <hr class="mt-5 mb-5">
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th><?= !empty($this->lang->line('total_order_price')) ? $this->lang->line('total_order_price') : 'Total Order Price' ?></th>
                                                    <td>+ <?= $settings['currency'] . ' ' . number_format($order['total'], 2) ?></td>
                                                </tr>
                                                <?php if ($item['type'] != 'digital_product') { ?>
                                                    <tr>
                                                        <th><?= !empty($this->lang->line('delivery_charge')) ? $this->lang->line('delivery_charge') : 'Delivery Charge' ?></th>
                                                        <td>+ <?= $settings['currency'] . ' ' . number_format($order['delivery_charge'], 2) ?></td>
                                                    </tr>
                                                <?php } ?>
                                                <tr class="d-none">
                                                    <th><?= !empty($this->lang->line('tax')) ? $this->lang->line('tax') : 'Tax' ?> - (<?= $order['total_tax_percent'] ?>%)</th>
                                                    <td>+ <?= $settings['currency'] . ' ' . number_format($order['total_tax_amount'], 2) ?></td>
                                                </tr>
                                                <?php if (!empty($order['promo_code']) && !empty($order['promo_discount'])) { ?>
                                                    <tr>
                                                        <th><?= !empty($this->lang->line('promocode_discount')) ? $this->lang->line('promocode_discount') : 'Promocode Discount' ?> - (<?= $order['promo_code'] ?>)
                                                        </th>
                                                        <td>- <?= $settings['currency'] . ' ' . number_format($order['promo_discount'], 2) ?></td>
                                                    </tr>
                                                <?php } ?>
                                                <tr>
                                                    <th><?= !empty($this->lang->line('wallet_used')) ? $this->lang->line('wallet_used') : 'Wallet Used' ?></th>
                                                    <td>- <?= $settings['currency'] . ' ' . number_format($order['wallet_balance'], 2) ?></td>
                                                </tr>
                                                <tr class="h6">
                                                    <th><?= !empty($this->lang->line('final_total')) ? $this->lang->line('final_total') : 'Final Total' ?></th>
                                                    <td><?= $settings['currency'] . ' ' . number_format($order['final_total'], 2) ?><span class="small text-muted"> <?= !empty($this->lang->line('via')) ? $this->lang->line('via') : 'via' ?> (<?= $order['payment_method'] ?>) </span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <!-- /.col -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white px-sm-3 pt-sm-4 px-0">
                            <div class="row text-center ">
                                <?php
                                $status = ["awaiting", "received", "processed", "shipped", "delivered", "cancelled", "returned"];
                                $cancelable_till = $item['cancelable_till'];
                                $active_status = $item['active_status'];
                                $cancellable_index = array_search($cancelable_till, $status);
                                $active_index = array_search($active_status, $status);
                                if (!$item['is_already_cancelled'] && $item['is_cancelable'] && $active_index <= $cancellable_index) { ?>
                                    <div class="col my-auto">
                                        <a class="update-order block btn-sm btn btn-danger text-white mt-3 m-0" data-status="cancelled" data-order-id="<?= $order['id'] ?>"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></a>
                                    </div>
                                <?php } ?>
                                <?php
                                $order_date = $order['order_items'][0]['status'][3][1];
                                if ($order['is_returnable'] && !$order['is_already_returned'] && isset($order_date) && !empty($order_date)) { ?>
                                    <?php
                                    $settings = get_settings('system_settings', true);

                                    $timestemp = strtotime($order_date);
                                    $date = date('Y-m-d', $timestemp);
                                    $today = date('Y-m-d');
                                    $return_till = date('Y-m-d', strtotime($order_date . ' + ' . $settings['max_product_return_days'] . ' days'));
                                    echo "<br>";
                                    if ($today < $return_till) { ?>
                                        <div class="col my-auto ">
                                            <a class="update-order block buttons button-sm btn-6-3 mt-3 m-0" data-status="returned" data-order-id="<?= $order['id'] ?>"><?= !empty($this->lang->line('return')) ? $this->lang->line('return') : 'Return' ?></a>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="orderStatusModal" tabindex="-1" aria-labelledby="orderStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header px-8 py-4">
                <h5 class="modal-title" id="orderStatusModalLabel">
                    <i class="uil uil-info-circle"></i> Order Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-8 py-1">
                <?php if (!empty($order_item['status'])): ?>
                    <ul class="list-group">
                        <?php foreach ($order_item['status'] as $status): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="uil uil-check-circle text-success me-2"></i>
                                    <?= htmlspecialchars(ucfirst($status[0])); ?>
                                </span>
                                <!-- <small class="text-muted"><?= htmlspecialchars($status[1]); ?></small> -->
                                <small class="text-muted"><?= orderStatusTimeToHumanReadableString($status[1]) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-center text-muted">No status updates available for this order.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer px-8 py-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>