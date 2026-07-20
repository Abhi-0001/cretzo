<!-- breadcrumb -->
<div class="content-wrapper">
    <section class="wrapper bg-soft-grape">
        <div class="container py-3 py-md-5">
            <nav class="d-inline-block" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= base_url() ?>" class="text-decoration-none"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a></li>
                    <?php if (isset($right_breadcrumb) && !empty($right_breadcrumb)) {
                        foreach ($right_breadcrumb as $row) {
                    ?>
                            <li class="breadcrumb-item"><?= $row ?></li>
                    <?php }
                    } ?>
                    <li class="breadcrumb-item active text-muted" aria-current="page"><?= !empty($this->lang->line('checkout')) ? $this->lang->line('checkout') : 'Checkout' ?></li>
                </ol>
            </nav>
            <!-- /nav -->
        </div>
        <!-- /.container -->
    </section>
</div>
<!-- end breadcrumb -->


<section class="wrapper bg-light">
    <div class="container pt-12 pt-md-14 mb-15">
        <form class="needs-validation" id="checkout_form" method="POST" action="<?= base_url('cart/place-order') ?>">
            <div class="row gx-md-8 gx-xl-12 gy-12">
                <div class="col-lg-8">
                    <h3 class="checkout-form-title">
                        <?= !empty($this->lang->line('billing_details')) ? $this->lang->line('billing_details') : 'Billing Details' ?>
                    </h3>
                    <hr class="mt-7 mb-6">
                    <div class="bg-white mt-5">
                        <div class="ship-details-wrapper">
                            <?php
                            // echo "<pre>";
                            // print_r($cart);
                            // die; 
                            ?>
                            <input type="hidden" name="product_type" id="product_type" value="<?= $cart[0]['type']; ?>">
                            <input type="hidden" name="download_allowed" value="<?= in_array(0, $cart['download_allowed']) ? 0 : 1 ?>">
                            <?php if ($cart[0]['type'] != 'digital_product') { ?>
                                <!-- <div class="align-item-center ship-title-details justify-content-between user-add d-flex">
                                    <h5 class="pb-3"><?= !empty($this->lang->line('shipping_address')) ? $this->lang->line('shipping_address') : 'Shipping Address' ?></h5>
                                    <a href="#" data-izimodal-open=".address-modal"><i class="fas fa-edit edit-icon"></i></a>
                                </div> -->
                                <div class="align-item-center ship-title-details justify-content-between user-add d-flex">
                                    <h5 class="pb-3"><?= !empty($this->lang->line('shipping_address')) ? $this->lang->line('shipping_address') : 'Shipping Address' ?></h5>
                                    <a href="#" class="float-end text-decoration-none" data-bs-toggle="modal" data-bs-target="#address-modal">
                                        <i class="edit-icon uil uil-edit fs-16"></i>
                                    </a>

                                </div>
                                <hr class="mt-1 mb-2">

                                <div class="shipped-details mt-3">
                                    <p class="text-muted m-0" id="address-name-type"><?= isset($default_address) && !empty($default_address) ? $default_address[0]['name'] . ' - ' . ucfirst($default_address[0]['type']) : '' ?></p>
                                    <p class="text-muted m-0" id="address-full"><?= isset($default_address) && !empty($default_address) ? $default_address[0]['area'] . ' , ' . $default_address[0]['city'] : '' ?></p>
                                    <p class="text-muted m-0" id="address-country"><?= isset($default_address) && !empty($default_address) ? $default_address[0]['state'] . ' , ' . $default_address[0]['country'] . ' - ' . $default_address[0]['pincode'] : '' ?></p>
                                    <p class="text-muted m-0" id="address-mobile"><?= isset($default_address) && !empty($default_address) ? $default_address[0]['mobile'] : '' ?></p>
                                </div>

                                </br>
                                <!-- checking product deliverable or not  -->
                                <div id="deliverable_status">
                                    <?php
                                    $product_not_delivarable = array();
                                    if (isset($default_address) && !empty($default_address)) {
                                        $zipcode_id = fetch_details('zipcodes', ['zipcode' => $default_address[0]['pincode']], 'id')[0];
                                        $product_delivarable = check_cart_products_delivarable($cart[0]['user_id'], $default_address[0]['area_id'], $default_address[0]['pincode'], $zipcode_id);
                                        if (!empty($product_delivarable)) {
                                            $product_not_delivarable = array_filter($product_delivarable, function ($var) {
                                                return ($var['is_deliverable'] == false);
                                            });
                                            $product_not_delivarable = array_values($product_not_delivarable);
                                            $deliverable_error_msg = "";
                                            foreach ($product_not_delivarable as $p_id) {
                                                if (!empty($p_id['product_id'])) {
                                                    $deliverable_error_msg = (!empty($this->lang->line('product_not_delivarable_msg'))) ? $this->lang->line('product_not_delivarable_msg') : "Some of the item(s) are not delivarable on selected address. Try changing address or modify your cart items.";
                                                    continue;
                                                }
                                            }
                                    ?>
                                            <b class="text-danger"><?= $deliverable_error_msg ?></b>
                                    <?php }
                                    } ?>
                                </div>
                            <?php } ?>
                            <?php if (in_array(0, $cart['download_allowed']) && $cart[0]['type'] == 'digital_product') { ?>
                                <div class="mt-3">
                                    <label for="mail" class="form-label fw-bold text-dark">Please Enter Yor mail ID here for recive your order </label>
                                    <input name="email" type="text" id="mail" class="form-control" placeholder="Please enter your email ID ">
                                </div>
                            <?php } ?>

                            <input type="hidden" name="address_id" id="address_id" value="<?= isset($default_address) && !empty($default_address) && $cart[0]['type'] != 'digital_product' ? $default_address[0]['id']  : '' ?>" />
                            <input type="hidden" name="mobile" id="mobile" value="<?= isset($default_address) && !empty($default_address) ? $default_address[0]['mobile'] : $wallet_balance[0]['mobile'] ?>" />
                        </div>
                        <hr class="mt-4 mb-4">
                        <input type="hidden" name="total" value="<?= number_format($cart['sub_total'], 2) ?>">
                        <input type="hidden" id="temp_total" name="temp_total" value="<?= $cart['total_arr'] ?>">
                        <input type="hidden" name="product_variant_id" value="<?= implode(',', array_column($cart, 'id')) ?>">
                        <input type="hidden" name="quantity" value="<?= implode(',', array_column($cart, 'qty')) ?>">
                        <input type="hidden" id="current_wallet_balance" value="<?= number_format($wallet_balance[0]['balance'], 2) ?>">
                        <input type="hidden" id="wallet_used" name="wallet_used">
                        <input type="hidden" name="is_time_slots_enabled" id="is_time_slots_enabled" value="<?= (isset($time_slot_config['is_time_slots_enabled']) && $time_slot_config['is_time_slots_enabled'] == 1) ? 1 : 0 ?>">
                        <input type="hidden" name="product_type" id="product_type" value="<?= $cart[0]['type'] ?>">
                        <?php if (isset($time_slot_config['is_time_slots_enabled']) && $time_slot_config['is_time_slots_enabled'] == 1 && $cart[0]['type'] != 'digital_product') {
                            //If Time Slot is Enabled
                        ?>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Special Note for Order" name="order_note" id="order_note">
                            </div>
                            <hr class="mt-4 mb-4">
                            <h4 class="mt-3"><?= !empty($this->lang->line('preferred_delivery_date_time')) ? $this->lang->line('preferred_delivery_date_time') : 'Preferred Delivery Date / Time' ?></h4>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-clock"></i></span>
                                </div>
                                <input type="text" class="form-control float-right" id="datepicker">
                                <input type="hidden" id="start_date" class="form-control float-right">
                            </div>
                            <div class="mt-3" id="time_slots">
                                <?php foreach ($time_slots as $row) { ?>
                                    <div class="custom-control custom-radio">
                                        <input id="<?= $row['id'] ?>" name="delivery_time" type="radio" class="time-slot-inputs form-check-input" data-last_order_time="<?= $row['last_order_time'] ?>" value="<?= $row['title'] ?>" required>
                                        <label class="form-check-label" for="<?= $row['id'] ?>"><?= $row['title'] ?></label>
                                    </div>
                                <?php } ?>
                            </div>


                        <?php }
                        $settings = get_settings('system_settings', true);
                        $upload_attachments = isset($settings['allow_order_attachments']) ? $settings['allow_order_attachments'] : '';
                        $upload_limit = isset($settings['upload_limit']) ? $settings['upload_limit'] : '';

                        ?>

                        <?php
                        foreach ($cart as $row) {
                            // echo "<pre>";
                            // print_r($row);
                            // print_r($upload_attachments);
                            // print_r($upload_limit);
                            // die;
                            if (isset($row['is_attachment_required']) && $row['is_attachment_required'] == 1 && $cart[0]['type'] != 'digital_product') {

                                //If Allow Upload Attachment On
                        ?>
                                <h5 class="pt-3">Upload Order Attachments if you have any(Only images and docs are allowded)</h5>
                                <div class="input-group">
                                    <input type="file" class="form-controls" name="documents[]" multiple id="documents">
                                </div>
                                <span class="text-danger">Attachments is required</span>
                        <?php }
                        } ?>
                        <hr class="mt-4 mb-4">
                        <input type="hidden" name="delivery_date" id="delivery_date" class="form-control float-right">
                        <div class="align-item-center ship-title-details justify-content-between d-flex">
                            <h5><?= !empty($this->lang->line('wallet_balance')) ? $this->lang->line('wallet_balance') : 'Use wallet balance' ?></h5>
                        </div>
                        <?php $disabled = $wallet_balance[0]['balance'] == 0 ? 'disabled' : '' ?>
                        <div class="form-check d-flex">
                            <input class="form-check-input" type="checkbox" value="" id="wallet_balance" <?= $disabled ?>>
                            <label class="form-check-label d-flex" for="wallet_balance">
                                <?= !empty($this->lang->line('available_balance')) ? $this->lang->line('available_balance') : 'Available balance' ?> : <?= $currency . '<span id="available_balance">' . number_format($wallet_balance[0]['balance'], 2) . '</span>' ?>
                            </label>
                        </div>

                        <div class="ship-details-wrapper mt-3 payment-methods">
                            <div class="align-item-center ship-title-details justify-content-between d-flex">
                                <h5><?= !empty($this->lang->line('payment_method')) ? $this->lang->line('payment_method') : 'Payment Method' ?></h5>
                            </div>
                            <div class="shipped-details mt-3 col-md-6">
                                <table class="table table-step-shipping">
                                    <tbody>
                                        <?php if (isset($payment_methods['cod_method']) && $payment_methods['cod_method'] == 1) { ?>
                                            <tr>
                                                <label for="cod">
                                                    <td>
                                                        <label for="cod">
                                                            <input id="cod" class="form-check-input" title="<?= isset($cart[0]['is_cod_allowed']) && $cart[0]['is_cod_allowed'] == 0 ? 'Cash on delivery is not allowed for one of the item in your cart' : 'Please select one of this options.' ?>" name="payment_method" type="radio" value="COD" <?= isset($cart[0]['is_cod_allowed']) && $cart[0]['is_cod_allowed'] == 0 ? 'disabled' : '' ?>>
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <label for="cod">
                                                            <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/cod.png' ?>" class="payment-gateway-images" alt="COD" style="height: 30px;">
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <label for="cod">
                                                            <?= !empty($this->lang->line('cash_on_delivery')) ? $this->lang->line('cash_on_delivery') : 'Cash On Delivery' ?>
                                                        </label>
                                                    </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['paypal_payment_method']) && $payment_methods['paypal_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="paypal">
                                                        <input id="paypal" class="form-check-input" name="payment_method" type="radio" value="Paypal">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="paypal">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/paypal.png' ?>" class="payment-gateway-images" alt="Paypal" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="paypal">
                                                        Paypal
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['razorpay_payment_method']) && $payment_methods['razorpay_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="razorpay">
                                                        <input id="razorpay" class="form-check-input" name="payment_method" type="radio" value="Razorpay">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="razorpay">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/razorpay.png' ?>" class="payment-gateway-images" alt="Razorpay" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="razorpay">
                                                        RazorPay
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>





                                        <?php if (isset($payment_methods['paystack_payment_method']) && $payment_methods['paystack_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="paystack">
                                                        <input id="paystack" class="form-check-input" name="payment_method" type="radio" value="Paystack">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="paystack">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/paystack.png' ?>" class="payment-gateway-images" alt="Paystack" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="paystack">
                                                        Paystack
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['payumoney_payment_method']) && $payment_methods['payumoney_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="payumoney">
                                                        <input id="payumoney" class="form-check-input" name="payment_method" type="radio" value="Payumoney">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="payumoney">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'images/payumoney.png' ?>" class="payment-gateway-images" alt="Payumoney" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="payumoney">
                                                        Payumoney
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['flutterwave_payment_method']) && $payment_methods['flutterwave_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="flutterwave">
                                                        <input id="flutterwave" class="form-check-input" name="payment_method" type="radio" value="Flutterwave">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="flutterwave">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/flutterwave.png' ?>" class="payment-gateway-images" alt="Flutterwave" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="flutterwave">
                                                        Flutterwave
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['paytm_payment_method']) && $payment_methods['paytm_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="paytm">
                                                        <input id="paytm" class="form-check-input" name="payment_method" type="radio" value="Paytm">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="paytm">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/paytm.png' ?>" class="payment-gateway-images" alt="Paytm" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="paytm">
                                                        Paytm
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['stripe_payment_method']) && $payment_methods['stripe_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="stripe">
                                                        <input id="stripe" class="form-check-input" name="payment_method" type="radio" value="Stripe">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="stripe">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/stripe.png' ?>" class="payment-gateway-images" alt="Stripe" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="stripe">
                                                        Stripe
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>


                                        <?php if (isset($payment_methods['direct_bank_transfer']) && $payment_methods['direct_bank_transfer'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="bank_transfer">
                                                        <input id="bank_transfer" class="form-check-input" name="payment_method" type="radio" value="<?= BANK_TRANSFER ?>">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="bank_transfer">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/bank_transfer_!.png' ?>" class="payment-gateway-images" alt="Direct Bank Transfers" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="bank_transfer">
                                                        <?= !empty($this->lang->line('direct_bank_transfers')) ? $this->lang->line('direct_bank_transfers') : 'Direct Bank Transfers' ?>
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <!-- Midtrans -->
                                        <?php if (isset($payment_methods['midtrans_payment_method']) && $payment_methods['midtrans_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="midtrans">
                                                        <input id="midtrans" class="form-check-input" name="payment_method" type="radio" value="Midtrans">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="midtrans">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/midtrans.jpg' ?>" class="payment-gateway-images" alt="Midtrans" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="midtrans">
                                                        Midtrans
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <?php if (isset($payment_methods['myfaoorah_payment_method']) && $payment_methods['myfaoorah_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="my_fatoorah">
                                                        <input id="my_fatoorah" class="form-check-input" name="payment_method" type="radio" value="my_fatoorah">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="my_fatoorah">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/myfatoorah.jpg' ?>" class="payment-gateway-images" alt="myfatoorah" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="my_fatoorah">
                                                        My Fatoorah
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['instamojo_payment_method']) && $payment_methods['instamojo_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="instamojo">
                                                        <input id="instamojo" class="form-check-input" name="payment_method" type="radio" value="instamojo">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="instamojo">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/instamojo.png' ?>" class="payment-gateway-images" alt="instamojo" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="instamojo">
                                                        Instamojo
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (isset($payment_methods['phonepe_payment_method']) && $payment_methods['phonepe_payment_method'] == 1) { ?>
                                            <tr>
                                                <td>
                                                    <label for="phonepe">
                                                        <input id="phonepe" class="form-check-input" name="payment_method" type="radio" value="phonepe">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="phonepe">
                                                        <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/phonepay-logo.png' ?>" class="payment-gateway-images" alt="phonepe" style="height: 30px;">
                                                    </label>
                                                </td>
                                                <td>
                                                    <label for="phonepe">
                                                        PhonePe
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="stripe_div">
                            <div id="stripe-card-element">
                                <!--Stripe.js injects the Card Element-->
                            </div>
                            <p id="card-error" role="alert"></p>
                            <p class="result-message hidden"></p>
                        </div>

                        <div id="my_fatoorah_div">
                            <div id="card-element">
                                <!--Stripe.js injects the Card Element-->
                            </div>
                            <p id="card-error" role="alert"></p>
                            <p class="result-message hidden"></p>
                        </div>


                        <div id="bank_transfer_slide">
                            <div id="account_data" style="display: none;">
                                <?php if (isset($payment_methods['direct_bank_transfer']) && $payment_methods['direct_bank_transfer'] == 1) { ?>
                                    <div class="row">
                                        <div class="alert alert-warning">
                                            <strong><?= !empty($this->lang->line('edit_address')) ? $this->lang->line('edit_address') : 'Instructions! Make your payment directly into our account. Your order will not further proceed until the funds have cleared in our account. <br> You have to send your payment receipt from order details page then admin will verify that.' ?> </strong>
                                        </div>
                                        <div class="alert alert-info col-md-12">
                                            <strong><?= !empty($this->lang->line('account_details')) ? $this->lang->line('account_details') : 'Account Details!' ?> </strong> <br><br>
                                            <ul>
                                                <li><?= !empty($this->lang->line('account_name')) ? $this->lang->line('account_name') : 'Account Name' ?>: <?= (isset($payment_methods['account_name'])) ? $payment_methods['account_name'] : "" ?></li>
                                                <li><?= !empty($this->lang->line('account_number')) ? $this->lang->line('account_number') : 'Account Number' ?>: <?= (isset($payment_methods['account_number'])) ? $payment_methods['account_number'] : "" ?></li>
                                                <li><?= !empty($this->lang->line('bank_name')) ? $this->lang->line('bank_name') : 'Bank Name' ?>: <?= (isset($payment_methods['bank_name'])) ? $payment_methods['bank_name'] : "" ?></li>
                                                <li><?= !empty($this->lang->line('bank_code')) ? $this->lang->line('bank_code') : 'Bank Code' ?>: <?= (isset($payment_methods['bank_code'])) ? $payment_methods['bank_code'] : "" ?></li>
                                            </ul>
                                        </div>
                                        <div class="alert alert-info col-md-12">
                                            <strong><?= !empty($this->lang->line('extra_details')) ? $this->lang->line('extra_details') : 'Extra Details' ?>! </strong> <br><br>
                                            <p><?= (isset($payment_methods['notes'])) ? $payment_methods['notes'] : "" ?></p>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <hr class="mb-2 mt-2">
                        <input type="hidden" name="app_name" id="app_name" value="<?= $settings['app_name'] ?>" />
                        <input type="hidden" name="username" id="username" value="<?= $user->username ?>" />
                        <input type="hidden" id="user_id" value="<?= $user->id ?>" />
                        <input type="hidden" name="user_email" id="user_email" value="<?= isset($user->email) && !empty($user->email) ? $user->email : $support_email ?>" />
                        <input type="hidden" name="user_contact" id="user_contact" value="<?= $user->mobile ?>" />
                        <input type="hidden" name="logo" id="logo" value="<?= base_url(get_settings('web_logo')) ?>" />
                        <input type="hidden" name="order_amount" id="amount" value="" />
                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="" />
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="" />
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="" />

                        <input type="hidden" name="midtrans_order_id" id="midtrans_order_id" value="" />
                        <input type="hidden" name="midtrans_transaction_token" id="midtrans_transaction_token" value="" />

                        <input type="hidden" id="paytm_transaction_token" name="paytm_transaction_token" value="" />
                        <input type="hidden" id="paytm_order_id" name="paytm_order_id" value="" />

                        <input type="hidden" name="paystack_reference" id="paystack_reference" value="" />

                        <input type="hidden" name="stripe_client_secret" id="stripe_client_secret" value="" />
                        <input type="hidden" name="stripe_payment_id" id="stripe_payment_id" value="" />

                        <input type="hidden" name="my_fatoorah_order_id" id="my_fatoorah_order_id" value="" />
                        <input type="hidden" name="my_fatoorah_session_id" id="my_fatoorah_session_id" value="" />
                        <input type="hidden" name="my_fatoorah_payment_id" id="my_fatoorah_payment_id" value="" />

                        <input type="hidden" name="flutterwave_public_key" id="flutterwave_public_key" value="<?= $payment_methods['flutterwave_public_key'] ?>" />
                        <input type="hidden" id="flutterwave_currency" value="<?= $payment_methods['flutterwave_currency_code'] ?>" />
                        <input type="hidden" name="flutterwave_transaction_id" id="flutterwave_transaction_id" value="" />
                        <input type="hidden" name="flutterwave_transaction_ref" id="flutterwave_transaction_ref" value="" />
                        <input type="hidden" name="promo_set" id="promo_set" value="" />

                        <input type="hidden" name="instamojo_order_id" id="instamojo_order_id" value="" />
                        <input type="hidden" name="instamojo_payment_id" id="instamojo_payment_id" value="" />
                    </div>

                </div>
                <!-- /column -->
                <div class="col-xl-4 mt-5">
                    <div class="checkout-order-wrapper">
                        <div class="checkout-title">
                            <h1><?= !empty($this->lang->line('order_summary')) ? $this->lang->line('order_summary') : 'Order Summary' ?></h1>
                        </div>
                        <div class="order-details">
                            <div class="product-checkout-wrapper">
                                <div class="product-checkout-title">
                                    <h2 class="clearfix mb-0 text-muted fs-16">
                                        <a class="#"><?= isset($cart[0]['cart_count']) && !empty($cart[0]['cart_count']) ? $cart[0]['cart_count'] : 0 ?> Item(s) in Cart</a>
                                    </h2>
                                </div>
                                <div>
                                    <div class="product-checkout mt-4">
                                        <?php if (isset($cart) && !empty($cart)) {
                                            if ($cart[0]['type'] != 'digital_product') {
                                                $product_not_delivarable = array_column($product_not_delivarable, "product_id");
                                            }

                                            foreach ($cart as $row) {
                                                if (isset($row['qty']) && $row['qty'] != 0) {
                                                    $price = $row['special_price'] != '' && $row['special_price'] != null && $row['special_price'] > 0 && $row['special_price'] < $row['price'] ? $row['special_price'] : $row['price'];
                                                    $amount = $row['qty'] * $price;
                                        ?>
                                                    <div class="shopping-cart-item d-flex justify-content-between mb-4 gap-4">
                                                        <div class="d-flex flex-row d-flex align-items-center gap-4" title="<?= $row['name'] ?>">
                                                            <figure class="rounded checkout-img">
                                                                <a href="<?= base_url("products/details/" . $row['slug']) ?>">
                                                                    <img loading="lazy" src="<?= $row['image_sm'] ?>" alt="" style="object-fit: cover;" />
                                                                </a>
                                                            </figure>
                                                            <div class="w-100 ms-4">
                                                                <h6 class="post-title fs-15 lh-xs mb-1">
                                                                    <a href="<?= base_url("products/details/" . $row['slug']) ?>" class="link-dark text-decoration-none"><?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])) ?></a>
                                                                </h6>
                                                                <div class="small"><?php if ($cart[0]['type'] != 'digital_product') { ?>
                                                                        <div id="p_<?= $row['product_id'] ?>" class="text-danger deliverable_status"><?= (isset($default_address) && !empty($default_address) && in_array($row['product_id'], $product_not_delivarable)) ? "Not deliverable" : "" ?></div>
                                                                    <?php } ?>
                                                                    <?php if (!empty($row['product_variants'])) { ?>
                                                                        <?= str_replace(',', ' | ', $row['product_variants'][0]['variant_values']) ?>
                                                                    <?php } ?>
                                                                    <div class="qty">
                                                                        <span class="text-muted"><?= !empty($this->lang->line('qty')) ? $this->lang->line('qty') : 'Qty' ?> :</span>
                                                                        <span class="text-muted"><?= $row['qty'] ?></span>
                                                                    </div>
                                                                    <?php if (isset($row['item_tax_percentage']) && !empty($row['item_tax_percentage'])) { ?>
                                                                        <div>
                                                                            <span class="text-muted"><?= !empty($this->lang->line('net_amountD')) ? $this->lang->line('net_amount') : 'Net Amount' ?> :<?= $settings['currency'] ?><?= number_format((($amount) - (calculate_tax_inclusive(($amount), $row['item_tax_percentage']))), 2) ?></i></span>
                                                                        </div>
                                                                        <div>
                                                                            <span class="text-muted"><?= !empty($row['tax_title']) ? $row['tax_title'] : 'Tax' ?> :</span>
                                                                            <span class="text-muted"><?= $settings['currency'] ?><?= number_format(calculate_tax_inclusive(($amount), $row['item_tax_percentage']), 2) ?></span>

                                                                        </div>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="ms-2 d-flex align-items-center">
                                                            <p class="">
                                                                <span class="amount">
                                                                    <?= $settings['currency'] ?><?= number_format($row['qty'] * $price, 2) ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                        <?php }
                                            }
                                        } ?>
                                    </div>
                                </div>
                                <input type="hidden" id="sub_total" value="<?= $cart['sub_total'] ?>">
                                <div class="cart-total-price">
                                    <table class="table cart-products-table">
                                        <tbody>
                                            <tr>
                                                <td><strong class="text-dark"><?= !empty($this->lang->line('subtotal')) ? $this->lang->line('subtotal') : 'Subtotal' ?></strong></td>
                                                <td class="text-end">
                                                    <p class="price d-flex gap-1"><?= $settings['currency'] . ' <span class="sub_total">' . number_format($cart['sub_total'], 2) . '</span>' ?></p>
                                                </td>
                                            </tr>

                                            <?php if (!empty($cart['tax_percentage'])) { ?>
                                                <tr class="cart-product-tax d-none">
                                                    <td class="text-muted"><?= !empty($this->lang->line('tax')) ? $this->lang->line('tax') : 'Tax' ?> (<?= $cart['tax_percentage'] ?>%)</td>
                                                    <td class="text-muted"><?= $settings['currency'] . ' ' . number_format($cart['tax_amount'], 2) ?></td>
                                                </tr>
                                            <?php } ?>
                                            <?php
                                            $shiprocket_settings = get_settings('shipping_method', true);
                                            if (isset($shiprocket_settings['shiprocket_shipping_method']) && $shiprocket_settings['shiprocket_shipping_method'] == 1 && $cart[0]['type'] != 'digital_product') {
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="row ">
                                                            <?php if ($cart[0]['type'] != 'digital_product') { ?>
                                                                <div class="column delivery_charge">
                                                                    <h3 class="text-success"><?= !empty($this->lang->line('delivery_charge')) ? $this->lang->line('delivery_charge') : 'Delivery Charges' ?></h3>
                                                                </div>

                                                        </div>

                                                        <div class="d-flex d-none">
                                                            <div class="delivery_charge">
                                                                <h6 class="fs-15">
                                                                    <?= !empty($this->lang->line('shipping_method')) ? $this->lang->line('shipping_method') : 'shipping method' ?>
                                                                </h6>
                                                            </div>
                                                            <div class="deliverycharge_currency ms-2 d-flex gap-1">
                                                                <?= $settings['currency'] . ' ' ?><span class="shipping_method"></span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex">
                                                            <div class="delivery_charge">
                                                                <h6 class="fs-15">
                                                                    <?= !empty($this->lang->line('delivery_charge_with_cod')) ? $this->lang->line('delivery_charge_with_cod') : 'Delivery Charge with COD :' ?>
                                                                </h6>
                                                            </div>
                                                            <div class="deliverycharge_currency ms-2 d-flex gap-1">
                                                                <?= $settings['currency'] . ' ' ?><span class="delivery_charge_with_cod"></span>
                                                                <input type="hidden" name="delivery_charge_with_cod" class="delivery_charge_with_cod" value="" />

                                                            </div>
                                                        </div>
                                                        <div class="d-flex">
                                                            <div class="delivery_charge">
                                                                <h6 class="fs-15">
                                                                    <?= !empty($this->lang->line('delivery_charge_without_cod')) ? $this->lang->line('delivery_charge_without_cod') : 'Delivery Charge without COD :' ?>
                                                                </h6>
                                                            </div>
                                                            <div class="deliverycharge_currency ms-2 d-flex gap-1">
                                                                <?= $settings['currency'] . ' ' ?><span class="delivery_charge_without_cod"></span>
                                                                <input type="hidden" name="delivery_charge_without_cod" class="delivery_charge_without_cod" value="" />
                                                            </div>
                                                        </div>
                                                        <div class="d-flex">
                                                            <div class="delivery_charge">
                                                                <h6 class="fs-15">
                                                                    <?= !empty($this->lang->line('estimate_date')) ? $this->lang->line('estimate_date') : 'Estimated Delivery Date :' ?>
                                                                </h6>
                                                            </div>
                                                            <div class="text-muted">
                                                                <h3 class="estimate_date"></h3>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } else { ?>
                                                <?php if ($cart[0]['type'] != 'digital_product') { ?>

                                                    <tr>
                                                        <td>
                                                            <div class="row ">
                                                                <div class="column delivery_charge">
                                                                    <h3 class="text-success"><?= !empty($this->lang->line('delivery_charge')) ? $this->lang->line('delivery_charge') : 'Delivery Charges' ?></h3>
                                                                </div>

                                                            </div>
                                                            <div class="d-flex">
                                                                <div class="delivery_charge">
                                                                    <h6 class="fs-15">
                                                                        <?= !empty($this->lang->line('delivery_charge_with_cod')) ? $this->lang->line('delivery_charge_with_cod') : 'Delivery Charge with COD :' ?>
                                                                    </h6>
                                                                </div>
                                                                <div class="deliverycharge_currency ms-2 d-flex gap-1">
                                                                    <?= $settings['currency'] . ' ' ?><span class="delivery_charge_with_cod"></span>
                                                                    <input type="hidden" name="delivery_charge_with_cod" class="delivery_charge_with_cod" value="" />

                                                                </div>
                                                            </div>
                                                            <div class="d-flex">
                                                                <div class="delivery_charge">
                                                                    <h6 class="fs-15">
                                                                        <?= !empty($this->lang->line('delivery_charge_without_cod')) ? $this->lang->line('delivery_charge_without_cod') : 'Delivery Charge without COD :' ?>
                                                                    </h6>
                                                                </div>
                                                                <div class="deliverycharge_currency ms-2 d-flex gap-1">
                                                                    <?= $settings['currency'] . ' ' ?><span class="delivery_charge_without_cod"></span>
                                                                    <input type="hidden" name="delivery_charge_without_cod" class="delivery_charge_without_cod" value="" />
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } ?>


                                            <tr>
                                                <td class="text-muted"><?= !empty($this->lang->line('wallet')) ? $this->lang->line('wallet') : 'Wallet' ?></td>
                                                <td class="text-muted text-end d-flex gap-1"><?= $settings['currency'] ?> <span class="wallet_used">0.00<span></td>

                                            </tr>
                                            <tr id="promocode_div" class="d-none">
                                                <td class="text-muted"><?= !empty($this->lang->line('promocode')) ? $this->lang->line('promocode') : 'Promocode' ?> <span id="promocode"></span></td>
                                                <td class="text-muted text-end"> <i><?= $settings['currency'] ?></i> <span id="promocode_amount"></span></td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                            <tr class="total-price">
                                                <td><strong class="text-dark"><?= !empty($this->lang->line('total')) ? $this->lang->line('total') : 'Total' ?></strong></td>
                                                <td class="d-flex gap-1 fw-bold">
                                                    <?= $settings['currency'] ?><span id="final_total"><?= number_format($cart['sub_total'], 2) ?> </span></p>
                                                </td>
                                            </tr>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="input-group">
                                    <a href="#" class="mb-4 pl-3 text-decoration-none text-blue fw-bold" data-bs-toggle="modal" data-bs-target="#promo-code-modal">
                                        <?= !empty($this->lang->line('see_all_offers')) ? $this->lang->line('see_all_offers') : 'See All Offers' ?>(%)</i>
                                    </a>

                                    <!-- <a href="#" data-izimodal-open=".promo_code_modal" class="mb-4 pl-3 text-decoration-none text-blue fw-bold"><?= !empty($this->lang->line('see_all_offers')) ? $this->lang->line('see_all_offers') : 'See All Offers' ?>(%)</i></a> -->
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Promo code" id="promocode_input">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary rounded-end" id="redeem_btn"><?= !empty($this->lang->line('redeem')) ? $this->lang->line('redeem') : 'Redeem' ?></button>
                                        <button class="btn btn-danger d-none" id="clear_promo_btn"><?= !empty($this->lang->line('clear')) ? $this->lang->line('clear') : 'Clear' ?></button>
                                    </div>
                                </div>
                                <?php $is_disabled = false;
                                foreach ($product_not_delivarable as $p_id) {
                                    if (!empty($p_id['product_id'])) {
                                        $is_disabled = true;
                                        continue;
                                    }
                                } ?>
                                <button class="block btn btn-primary rounded-pill w-100 mt-4" id="place_order_btn" type="submit" <?= ($is_disabled) ? "disabled" : ""; ?>><?= !empty($this->lang->line('place_order')) ? $this->lang->line('place_order') : 'Place Order' ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /column -->
            </div>
        </form>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>


<form action="<?= base_url('payment/paypal') ?>" id="paypal_form" method="POST">
    <input type="hidden" id="csrf_token" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <input type="hidden" name="order_id" id="paypal_order_id" value="" />
</form>
<input type="hidden" name="stripe_key_id" id="stripe_key_id" value="<?= $payment_methods['stripe_publishable_key'] ?>" />
<input type="hidden" name="razorpay_key_id" id="razorpay_key_id" value="<?= $payment_methods['razorpay_key_id'] ?>" />
<input type="hidden" name="paystack_key_id" id="paystack_key_id" value="<?= $payment_methods['paystack_key_id'] ?>" />
<input type="hidden" id="delivery_starts_from" value="<?= (isset($time_slot_config['delivery_starts_from']) ? $time_slot_config['delivery_starts_from'] : '') ?>">
<input type="hidden" id="delivery_ends_in" value="<?= (isset($time_slot_config['allowed_days']) ? $time_slot_config['allowed_days'] : '') ?>">

<div class="modal fade address_modal" id="address-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <section id="address_form">
                    <div class="h4"><?= !empty($this->lang->line('shipping_address')) ? $this->lang->line('shipping_address') : 'Shipping Address' ?></div>
                    <ul id="address-list"></ul>
                    <div class="col-12 text-right mt-2">
                        <a target="_blank" href="<?= base_url('my-account/manage-address') ?>"><?= !empty($this->lang->line('create_a_new_address')) ? $this->lang->line('create_a_new_address') : 'Create a New Address' ?></a>
                    </div>
                    <footer class="mt-4">
                        <button data-bs-dismiss="modal" class="btn btn-sm btn-soft-dark rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                        <button class="submit btn btn-sm btn-primary rounded-pill" id="select_address"><?= !empty($this->lang->line('save')) ? $this->lang->line('save') : 'Save' ?></button>
                    </footer>
                </section>
            </div>
            <!--/.modal-content -->
        </div>
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->


<div class="modal fade" id="promo-code-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <section id="promo_code_form">
                    <div class="h4"><?= !empty($this->lang->line('promocodes')) ? $this->lang->line('promocodes') : 'Promocodes' ?></div>
                    <ul id="promocode-list" class="p-0"></ul>
                </section>
            </div>
            <!--/.modal-content -->
        </div>
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->


<?php if (isset($payment_methods['paytm_payment_method']) && $payment_methods['paytm_payment_method'] == 1) {
    $url = ($payment_methods['paytm_payment_mode'] == "production") ? "https://securegw.paytm.in/" : "https://securegw-stage.paytm.in/";
?>
    <script type="application/javascript" src="<?= $url ?>merchantpgpui/checkoutjs/merchants/<?= $payment_methods['paytm_merchant_id'] ?>.js"></script>
<?php } ?>

<script src="https://checkout.flutterwave.com/v3.js"></script>
<script src="https://js.stripe.com/v3/"></script>
<script src="https://demo.myfatoorah.com/cardview/v2/session.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://js.instamojo.com/v1/checkout.js"></script>
<?php
$midtrans_url = $midtrans_client_key = "";

if (isset($payment_methods['midtrans_payment_mode']) && $payment_methods['midtrans_payment_mode'] != '') {
    $midtrans_url = (isset($payment_methods['midtrans_payment_mode']) && $payment_methods['midtrans_payment_mode'] == "sandbox") ? "https://app.sandbox.midtrans.com/snap/snap.js" : "https://app.midtrans.com/snap/snap.js";
    // $midtrans_client_key = $payment_methods['midtrans_client_key'];
    $midtrans_client_key = ($payment_methods['midtrans_client_key'] != "") ? $payment_methods['midtrans_client_key'] : 'SB-Mid-client-x5KX3W5PHEsEDMQ4'; ?>

    <script type="text/javascript" src="<?= $midtrans_url ?>" data-client-key="<?= $midtrans_client_key ?>"></script>
<?php
}
?>
<script src="<?= THEME_ASSETS_URL . '/js/checkout.js' ?>"></script>