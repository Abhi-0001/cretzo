<?php 
    $cart_count = isset($cart[0]['cart_count']) && !empty($cart[0]['cart_count']) ? $cart[0]['cart_count'] : 0;
    $delivery_charge = !empty($cart['sub_total']) ? $cart['delivery_charge'] : 0;
    $subtotal = !empty($cart['sub_total']) ? ($cart['overall_amount'] - $cart['delivery_charge']) : 0;
    $total = !empty($cart['sub_total']) ? $cart['overall_amount'] : 0;
    $total_mrp = !empty($cart['total_mrp']) ? $cart['total_mrp'] : 0;
    $total_discount_on_mrp = !empty($cart['discount_on_mrp']) ? $cart['discount_on_mrp'] : 0;

    /* Re-arrange addresses array to put the default address (if any) at first index */
    if (!empty($addresses['rows'])) {

        // Set first address as selected by default.
        $selected_address = $addresses['rows'][0];
        $selected_address_id = $addresses['rows'][0]['id'];

        // Find the default address and place it in the first position.
        foreach ($addresses['rows'] as $key => $row) {
            if (isset($row['is_default']) && $row['is_default'] == 1) {
                // Remove the element from the array and save it
                $defaultRow = $addresses['rows'][$key];
                unset($addresses['rows'][$key]); // Remove it from its current position
    
                // Add it to the beginning of the array
                array_unshift($addresses['rows'], $defaultRow);

                $selected_address = $row;
                $selected_address_id = $row['id'];

                break; // Stop after finding the first default item
            }
        }
    }

    // Check if an 'id' is provided in the query parameter and find and set the selected address to the matching id 
    if (!empty($_GET['id'])) {
        $query_id = (int)$_GET['id']; // Sanitize input to avoid issues

        // Search for the matching address ID
        foreach ($addresses['rows'] as $row) {
            if ($row['id'] == $query_id) {
                $selected_address = $row;
                $selected_address_id = $query_id;
                break;
            }
        }
    }

    /* echo "<pre>";
    print_r($addresses);
    print_r($selected_address['mobile']);
    print_r($wallet_balance[0]['mobile']);
    die; */
?>

<!-- INPUTS TO STORE VALUES TO BE USED IN JS -->
<form id="checkout_form">
    <input type="hidden" id="input_sub_total" value="<?= $subtotal ?>">
    <input type="hidden" id="input_shipping" value="<?= $delivery_charge ?>">
    <input type="hidden" id="input_total" value="<?= $total ?>">

    <input type="hidden" id="input_address_id" value="<?= $cart[0]['type'] != 'digital_product' ? $selected_address_id : '' ?>">
    <input type="hidden" name="mobile" id="mobile" value="<?= isset($selected_address) && !empty($selected_address) ? $selected_address['mobile'] : $wallet_balance[0]['mobile'] ?>" />

    <input type="hidden" name="delivery_charge_without_cod" id="delivery_charge_without_cod" value="0">
    <input type="hidden" name="delivery_charge_with_cod" id="delivery_charge_with_cod" value="0">

    <input type="hidden" name="product_variant_id" value="<?= implode(',', array_column($cart, 'id')) ?>">
    <input type="hidden" name="quantity" value="<?= implode(',', array_column($cart, 'qty')) ?>">

    <input type="hidden" name="wallet_used" id="wallet_used">

    <input type="hidden" id="current_wallet_balance" value="<?= number_format($wallet_balance[0]['balance'], 2) ?>">

    <input type="hidden" name="product_type" id="product_type" value="<?= $cart[0]['type']; ?>">
    <input type="hidden" name="download_allowed" value="<?= in_array(0, $cart['download_allowed']) ? 0 : 1 ?>">

    <input type="hidden" name="app_name" id="app_name" value="<?= $settings['app_name'] ?>" />
    <input type="hidden" name="username" id="username" value="<?= $user->username ?>" />
    <input type="hidden" id="user_id" value="<?= $user->id ?>" />
    <input type="hidden" name="user_email" id="user_email" value="<?= isset($user->email) && !empty($user->email) ? $user->email : $support_email ?>" />
    <input type="hidden" name="user_contact" id="user_contact" value="<?= $user->mobile ?>" />
    <input type="hidden" name="logo" id="logo" value="<?= base_url(get_settings('web_logo')) ?>" />
    <input type="hidden" name="order_amount" id="amount" value="" />
    <input type="hidden" name="razorpay_key_id" id="razorpay_key_id" value="<?= $payment_methods['razorpay_key_id'] ?>" />
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="" />
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="" />
    <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="" />

    <input type="hidden" name="promo_set" id="promo_set" value="" />
    <input type="hidden" name="promo_code_amount" id="promo_code_amount" value="0" />

    <input type="hidden" name="theme_color" id="theme_color" style="color: var(--color-main-theme);" />

    <!-- <input type="text" class="form-control" placeholder="Special Note for Order" name="order_note" id="order_note"> -->
</form>

<!-- main container starts address -->
<section class="cart-container address">
    <!-- <h1 class="heading-n ta-c">Address</h1> -->

    <!-- card indicator -->
    <div class="cart-indicator-container">
        <div class="company-logo">
            <?php $logo = get_settings('web_logo'); ?>
            <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="main-logo" alt="site-logo image"></a>
        </div>
        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart') ?>"><p class="text-n pl-3">Cart</p></a>
        </div>
        <div class="cart-indicator cart-indicator-active">
            <div class="completion-line active"></div>
        </div>
        <div class="cart-indicator cart-indicator-active rounded-end">
            <p class="text-n pr-1">Address</p>
        </div>
        <div class="cart-indicator">
            <div class="completion-line"></div>
        </div>
        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart/checkout') ?>"><p class="text-n pr-3">Payment</p></a>
        </div>
        <div class="quality-assured-container">
            <img class="quality-assured-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/orange-tick.png') ?>">
            <span class="text-s quality-assured-text">100% Quality Assured</p>
        </div>
    </div>

    <!-- cart -->
    <div class="cart">

        <div class="cart-left">

            <div class="cart-left-one">
                <a href="<?= base_url('cart') ?>" class="cretzo small-btn text-decoration-none px-0 text-s btn-nav-prev"><i class="fa fa-arrow-left mr-1"></i>Back to cart</a>
                <h1 class="text-b mt-2">Select Delivery Address</p>
            </div>

            <div class="cart-left-two">

                <?php 
                    if (!empty($addresses['rows'])) {
                        $display_default_header = false;
                        foreach ($addresses['rows'] as $key => $row) {
                            if($key == 0 && $row['is_default'] == 1){
                                echo '<h1 class="text-s">DEFAULT ADDRESS</h1>';
                                $display_default_header = true;
                            }
                            else if($key == 1){
                                if($display_default_header){
                                    echo '<h1 class="text-s">OTHER ADDRESS</h1>';
                                }
                            }

                            $is_selected_address = $row['id'] == $selected_address_id;
                ?>
                            <ul class="list cart-left-two-left <?= $row['is_default'] == 1 ? 'cart-left-two-left-upper' : '';?>">
                                <li class="address-container <?=$is_selected_address ? 'selected-address' : ''?>" data-row="<?= htmlspecialchars(json_encode($row)) ?>">
                                    <h1 class="text-n address-name"><?=$row['name']?> <span class="address-type <?=$row['type']?>-address"><?=$row['type']?></span></h1>
                                    <p class="text-n address-text"><?=$row['address']?></p>
                                    <!-- <p class="text-n address-text">Mobile: <strong><?=$row['mobile']?></strong></p> -->
                                    <p class="text-n address-text">Mobile: <?=$row['mobile']?> </p>
                                    <?=
                                        (isset($row['alternate_mobile']) && !empty($row['alternate_mobile'])) ? '<p class="text-n address-text">Alternate Mobile: <strong>' . $row['alternate_mobile'] . '</strong></p>' : '';
                                    ?>
                                    <!-- <p class="text-n address-text">Pay on Delivery Available</p> -->
                                    <div>
                                        <button class="cretzo btn btn-dark address-action-btn address-action-btn-select" style="<?=$is_selected_address ? 'display: none;' : ''?>" <?=$is_selected_address ? 'disabled' : ''?>><?=$is_selected_address ? 'SELECTED' : 'SELECT'?></button>
                                        <a href="<?= base_url('my-account/manage-address') ?>">
                                            <button class="cretzo btn btn-light address-action-btn">REMOVE</button>
                                        </a>
                                        <!-- <button class="cretzo btn btn-light address-action-btn address-action-btn-edit" data-row="<?= htmlspecialchars(json_encode($row)) ?>" data-id="<?=$row['id']?>">EDIT</button> -->
                                        <a href="<?= base_url('my-account/manage-address') ?>?redirect=checkout&id=<?=$row['id']?>">
                                            <button class="cretzo btn btn-light address-action-btn address-action-btn-edit">EDIT</button>
                                        </a>
                                    </div>
                                </li>
                            </ul>
                <?php   }
                    }
                ?>
            </div>

            <a class="text-decoration-none" href="<?= base_url('my-account/manage-address') ?>?redirect=checkout&action=add">
                <div class="cart-left-three c-p add-address-container">
                    <p class="text-n">+ Add New Address</p>
                </div>
            </a>
        </div>

        <div class="cart-right">

            <div class="cart-right-three">
                <h1 class="text-b"><img class="delivery-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/delivery-icon.png') ?>"> Delivery Estimates</h1>
                <ul class="estimate-list pt-2">
                
                <?php 
                    foreach ($cart as $key => $row) {
                        if (isset($row['qty']) && $row['qty'] != 0) {

                            // Calculate the current date and add 7 days to it
                            $currentDate = new DateTime(); // Gets the current date
                            $startDate = $currentDate->modify('+7 day')->format('j M Y'); // 7 days from now
                            $endDate = $currentDate->modify('+7 day')->format('j M Y'); // 14 days from now
                        ?>
                        
                        <li class="estimate-list-item" id="p_<?=$row['product_id']?>">
                            <div>
                                <img class="estimate-list-item-img" src="<?= $row['image'] ?>">
                            </div>
                            <div>
                                <!-- <p class="text-n">Estimated delivery by <span class="fw-b">1 Sep 2024</span></p> -->
                                <p class="text-n estimate-list-item-text">Estimated delivery: <span class="fw-b"><?= $startDate ?> - <?= $endDate ?></span></p>
                            </div>
                        </li>

                <?php   }
                    } ?>
                </ul>
            </div>

            <div class="cart-right-three">
                <h1 class="text-b">PRICE DETAIL (<span class="fw-n cart-count"><?= $cart_count ?></span> Items)</h1>
                <table class="bill-container">
                    <tr class="bill-row">
                        <td class="text-s bill-column">Total MRP</td>
                        <td class="text-s bill-column">₹<?= moneyFormatIndia(round($total_mrp)) ?></td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Discount on MRP</td>
                        <td class="text-s bill-column" style="color: var(--color-success);">- ₹<?= moneyFormatIndia(round($total_discount_on_mrp)) ?></td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Subtotal</td>
                        <td class="text-s bill-column fw-b final-subtotal" style="color: black;">₹<?= moneyFormatIndia(round($subtotal)) ?></td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Coupon Discount</td>
                        <td class="text-s bill-column">
                            <span class="final-promocode-amount d-none" style="color: var(--color-success);"></span>
                            <span class="final-promocode d-none" style="color: var(--color-success);">₹<?= moneyFormatIndia(0) ?></span>
                            <a href="#" class="mb-4 pl-3 text-decoration-none text-blue fw-bold see-offers-btn" data-bs-toggle="modal" data-bs-target="#promo-code-modal" style="color: var(--color-orange) !important;">
                                See Offers
                            </a>
                        </td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Platform Free</td>
                        <td class="text-s bill-column">FREE</td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Shipping Fee<span class="final-shipping-title-cod-tag fw-bold d-none"> (Cash On Delivery)</span></td>
                        <td class="text-s bill-column fw-b final-shipping" style="color: black;">₹<?= moneyFormatIndia(round($delivery_charge)) ?></td>
                    </tr>
                    <tr class="bill-row bill-row-last">
                        <td class="text-n bill-column fw-b">Total Amount</td>
                        <td class="text-n bill-column fw-b final-total">₹<?= moneyFormatIndia(round($total)) ?></td>
                    </tr>
                </table>

                <!-- <div class="d-flex">
                    <div class="delivery_charge">
                        <h6 class="fs-15">
                            <?= !empty($this->lang->line('estimate_date')) ? $this->lang->line('estimate_date') : 'Estimated Delivery Date :' ?>
                        </h6>
                    </div>
                    <div class="text-muted">
                        <h3 class="estimate_date"></h3>
                    </div>
                </div> -->

                <p class="text-n mb-2">Estimated Delivery Date : <span class="estimate_date"></span></p>
                
                <div class="input-group my-4">
                    <input type="text" class="form-control promocode_input" placeholder="Promo code">
                    <div class="input-group-append">
                        <button class="cretzo btn btn-dark btn-primary rounded-end py-1 redeem_btn"><?= !empty($this->lang->line('redeem')) ? $this->lang->line('redeem') : 'Redeem' ?></button>
                        <button class="cretzo btn btn-dark btn-danger d-none py-1 clear_promo_btn" style="border-color: var(--color-fail); background-color: var(--color-fail);"><?= !empty($this->lang->line('clear')) ? $this->lang->line('clear') : 'Clear' ?></button>
                    </div>
                </div>
                                                        
                <button class="cretzo btn btn-dark go-to-payment-btn">Continue</button>
            </div>

            <p class="text-s ta-c address-error-msg mb-2"></p>
            
            <a class="text-decoration-none cretzo-link" href="<?= base_url('/home/contact-us') ?>">
                <p class="text-s ta-c c-p">Need Help? Contact Us</p>
            </a>
        </div>
    </div>
</section>
<!-- main container ends address -->

<!-- main container starts payment -->
<section class="cart-container payment d-none">
    <!-- <h1 class="heading-n ta-c">Payment</h1> -->

    <!-- card indicator -->
    <div class="cart-indicator-container">
        <div class="company-logo">
            <?php $logo = get_settings('web_logo'); ?>
            <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="main-logo" alt="site-logo image"></a>
        </div>
        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart') ?>"><p class="text-n pl-3">Cart</p></a>
        </div>
        <div class="cart-indicator">
            <div class="completion-line active"></div>
        </div>
        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart/checkout') ?>"><p class="text-n">Address</p></a>
        </div>
        <div class="cart-indicator cart-indicator-active">
            <div class="completion-line active"></div>
        </div>
        <div class="cart-indicator cart-indicator-active">
            <p class="text-n pr-3">Payment</p>
        </div>
        <div class="quality-assured-container">
            <img class="quality-assured-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/orange-tick.png') ?>">
            <span class="text-s quality-assured-text">100% Quality Assured</p>
        </div>
    </div>

    <!-- cart -->
    <div class="cart">

        <div class="cart-left">

            <div class="cart-left-one">
            <a href="<?= base_url('cart/checkout') ?>" class="cretzo small-btn text-decoration-none px-0 text-s btn-nav-prev"><i class="fa fa-arrow-left mr-1"></i>Change address</a>
                <h1 class="text-b mt-2">Choose Payment Mode</p>
            </div>

            <div class="payment-methods">

                <?php if (isset($payment_methods['razorpay_payment_method']) && $payment_methods['razorpay_payment_method'] == 1) { ?>
                    <div class="m-4 pl-1">
                        <tr>
                            <td>
                                <label for="razorpay" class="pb-1">
                                    <input id="razorpay" class="form-check-input" name="payment_method" type="radio" value="Razorpay" checked>
                                </label>
                            </td>
                            <td>
                                <label for="razorpay">
                                    <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/razorpay.png' ?>" class="payment-gateway-images" alt="Razorpay" style="height: 30px;">
                                </label>
                            </td>
                            <td>
                                <label for="razorpay">
                                    RazorPay (Card, Wallet, NetBanking)
                                </label>
                            </td>
                        </tr>
                    </div>
                <?php } ?>

                <?php if (isset($payment_methods['cod_method']) && $payment_methods['cod_method'] == 1) { ?>
                    <div class="m-4 pl-1">
                        <tr>
                            <td>
                                <label for="cod" class="pb-1">
                                    <input id="cod" class="form-check-input" title="<?= isset($cart[0]['is_cod_allowed']) && $cart[0]['is_cod_allowed'] == 0 ? 'Cash on delivery is not allowed for one of the item in your cart' : 'Please select one of this options.' ?>" name="payment_method" type="radio" value="COD" <?= isset($cart[0]['is_cod_allowed']) && $cart[0]['is_cod_allowed'] == -1 ? 'disabled' : '' ?>>
                                </label>
                            </td>
                            <td>
                                <label for="cod">
                                    <img loading="lazy" src="<?= THEME_ASSETS_URL . 'img/payments/cod.png' ?>" class="payment-gateway-images" alt="COD" style="height: 30px;">
                                </label>
                            </td>
                            <td>
                                <label for="cod">
                                    Cash On Delivery <?= isset($cart[0]['is_cod_allowed']) && $cart[0]['is_cod_allowed'] == 0 ? '<span style="color: red; font-size: 0.85em; margin-left: 8px;">COD Not Available</span>' : '' ?>
                                </label>
                            </td>
                        </tr>
                    </div>
                <?php } ?>

                <label for="wallet" style="display: none;">
                    <input id="wallet" class="form-check-input" name="payment_method" type="radio" value="Wallet">
                </label>

            </div>

            <!-- <div class="cart-left-two">
                <ul class="list cart-left-two-left">
                    <li class="text-s">
                        <img src="../images/star-icon.png">
                        <span>Recommended</span>
                    </li>
                    <li class="text-s">
                        <img src="../images/cod-icon.png">
                        <span>Cash on Delivery</span>
                        <span class="cart-offer">4 Offers</span>
                    </li>
                    <li class="text-s">
                        <img src="../images/dccard-icon.png">
                        <span>Credit/Debit Card</span>
                        <span class="cart-offer">4 Offers</span>
                    </li>
                    <li class="text-s">
                        <img src="../images/wallets.png">
                        <span>Wallets</span>
                        <span class="cart-offer">4 Offers</span>
                    </li>
                    <li class="text-s">
                        <img src="../images/netbanking-icon.png">
                        <span>Net Banking</span>
                    </li>
                </ul>
                <ul class="list cart-left-two-right">
                    <li class="text-n fw-b">Recommended Payment Options</li>
                    <li class="text-s">
                        <input type="radio" name="payment-type">
                        <label>Cash on Delivery</label>
                    </li>
                    <li class="text-s">
                        <input type="radio" name="payment-type">
                        <label>Google Pay</label>
                        <span class="cart-offer">4 Offers</span>
                    </li>
                    <li class="text-s">
                        <input type="radio" name="payment-type">
                        <label>ICICI Cridit Card</label>
                    </li>
                </ul>
            </div> -->

            <!-- <div class="cart-left-three flex">
                <p class="text-s flex-1">Have a Gift Card?</p>
                <p class="text-n">APPLY GIFT CARD</p>
            </div> -->

            <div class="cart-left-three">

                <div class="text-n  mr-2">
                    <?= !empty($this->lang->line('wallet_balance')) ? $this->lang->line('wallet_balance') : 'Use wallet balance' ?>
                </div>
                <?php $disabled = $wallet_balance[0]['balance'] == 0 ? 'disabled' : '' ?>
                <div class="form-check d-flex">
                    <input class="form-check-input" type="checkbox" value="" id="wallet_balance" <?= $disabled ?>>
                    <label class="form-check-label d-flex" for="wallet_balance">
                        <?= !empty($this->lang->line('available_balance')) ? $this->lang->line('available_balance') : 'Available balance' ?> : <?= $currency . '<span id="available_balance">' . number_format($wallet_balance[0]['balance'], 2) . '</span>' ?>
                    </label>
                </div>

                <!-- <p class="text-s flex-1">Have a Gift Card?</p>
                <p class="text-n">APPLY GIFT CARD</p> -->
            </div>
        </div>

        <div class="cart-right">

            <div class="cart-right-three">
                <h1 class="text-b">PRICE DETAIL (<span class="fw-n cart-count"><?= $cart_count ?></span> Items)</h1>
                <table class="bill-container">
                    <tr class="bill-row">
                        <td class="text-s bill-column">Total MRP</td>
                        <td class="text-s bill-column">₹<?= moneyFormatIndia(round($total_mrp)) ?></td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Discount on MRP</td>
                        <td class="text-s bill-column" style="color: var(--color-success);">- ₹<?= moneyFormatIndia(round($total_discount_on_mrp)) ?></td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Subtotal</td>
                        <td class="text-s bill-column fw-b final-subtotal" style="color: black;">₹<?= moneyFormatIndia(round($subtotal)) ?></td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Coupon Discount</td>
                        <td class="text-s bill-column">
                            <span class="final-promocode-amount d-none" style="color: var(--color-success);"></span>
                            <span class="final-promocode d-none" style="color: var(--color-success);">₹<?= moneyFormatIndia(0) ?></span>
                            <a href="#" class="mb-4 pl-3 text-decoration-none text-blue fw-bold see-offers-btn" data-bs-toggle="modal" data-bs-target="#promo-code-modal" style="color: var(--color-orange) !important;">
                                See Offers
                            </a>
                        </td>
                    </tr>
                    <tr class="bill-row wallet-section d-none">
                        <td class="text-s bill-column"><?= !empty($this->lang->line('wallet')) ? $this->lang->line('wallet') : 'Wallet' ?></td>
                        <td class="text-s bill-column" style="color: var(--color-success);">- <?= $settings['currency'] ?> <span class="wallet_used">0.00<span></td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Platform Free</td>
                        <td class="text-s bill-column">FREE</td>
                    </tr>
                    <tr class="bill-row">
                        <td class="text-s bill-column">Shipping Fee<span class="final-shipping-title-cod-tag fw-bold d-none"> (Cash On Delivery)</span></td>
                        <td class="text-s bill-column fw-b final-shipping" style="color: black;">₹<?= moneyFormatIndia(round($delivery_charge)) ?></td>
                    </tr>
                    <tr class="bill-row bill-row-last">
                        <td class="text-n bill-column fw-b">Total Amount</td>
                        <td class="text-n bill-column fw-b">₹<span class="final-total"><?= moneyFormatIndia(round($total)) ?></span></td>
                    </tr>
                </table>

                <!-- <div class="d-flex">
                    <div class="delivery_charge">
                        <h6 class="fs-15">
                            <?= !empty($this->lang->line('estimate_date')) ? $this->lang->line('estimate_date') : 'Estimated Delivery Date :' ?>
                        </h6>
                    </div>
                    <div class="text-muted">
                        <h3 class="estimate_date"></h3>
                    </div>
                </div> -->

                <p class="text-n mb-2">Estimated Delivery Date : <span class="estimate_date"></span></p>

                <div class="input-group my-4">
                    <input type="text" class="form-control promocode_input" placeholder="Promo code">
                    <div class="input-group-append">
                        <button class="cretzo btn btn-dark btn-primary rounded-end py-1 redeem_btn"><?= !empty($this->lang->line('redeem')) ? $this->lang->line('redeem') : 'Redeem' ?></button>
                        <button class="cretzo btn btn-dark btn-danger d-none py-1 clear_promo_btn" style="border-color: var(--color-fail); background-color: var(--color-fail);"><?= !empty($this->lang->line('clear')) ? $this->lang->line('clear') : 'Clear' ?></button>
                    </div>
                </div>
                                                        
                <button class="cretzo btn btn-dark place-order-btn" id="place-order-btn">PLACE ORDER</button>
            </div>
            <a class="text-decoration-none cretzo-link" href="<?= base_url('/home/contact-us') ?>">
                <p class="text-s ta-c c-p">Need Help? Contact Us</p>
            </a>
        </div>
    </div>
</section>
<!-- main container ends payment -->

<!-- Modal for Coupon Codes -->
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

<!-- Modal for Editing Address -->
<!-- <div class="modal fade edit-modal-lg" id="address-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle"><?= !empty($this->lang->line('edit_address')) ? $this->lang->line('edit_address') : 'Edit Address' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ps-10 pt-0">
                <form action="<?= base_url('my-account/edit-address') ?>" method="POST" id="edit-address-form" class="mt-4">
                    <input type="hidden" name="id" id="address_id" value="" />
                    <div class="row">
                        <div class="col-md-4 col-sm-12 col-xs-12 form-group">
                            <label for="name" class="form-check-label"><?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Name' ?></label>
                            <input type="text" class="form-control" id="edit_name" name="name" placeholder="Name" />
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12 form-group">
                            <label for="mobile_number" class="form-check-label"><?= !empty($this->lang->line('mobile_number')) ? $this->lang->line('mobile_number') : 'Mobile Number' ?></label>
                            <input type="text" class="form-control" id="edit_mobile" name="mobile" placeholder="Mobile Number" />
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12 form-group">
                            <label for="address" class="form-check-label"><?= !empty($this->lang->line('address')) ? $this->lang->line('address') : 'Address' ?></label>
                            <input type="text" class="form-control" name="address" id="edit_address" placeholder="Address" />
                        </div>
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group edit_city">

                            <label for="edit_city" class="form-check-label"><?= !empty($this->lang->line('city')) ? $this->lang->line('city') : 'City' ?></label>
                            <select name="city_id" id="edit_city" class="form-control">
                                <option value><?= !empty($this->lang->line('select_city')) ? $this->lang->line('select_city') : '--Select City--' ?></option>
                                <option value="0"><?= !empty($this->lang->line('other')) ? $this->lang->line('other') : 'other' ?></option>
                                <?php foreach ($cities as $row) { ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <!- <input type="text" name="other_city" id="other_city" class="d-none"> ->
                        <!- <div class="col-md-6 col-sm-12 col-xs-12 form-group edit_area">
                            <label for="area" class="form-check-label"><? //= !empty($this->lang->line('area')) ? $this->lang->line('area') : 'Area' 
                                                                        ?></label>
                            <select name="area_id" id="edit_area" class="form-control">
                                <option value=""><? //= !empty($this->lang->line('select_area')) ? $this->lang->line('select_area') : '--Select Area--' 
                                                    ?></option>
                            </select>
                        </div> ->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group area">
                            <label for="area" class="control-label">Area</label>
                            <input type="text" class="form-control" id="edit_area" name="edit_general_area_name" placeholder="Area Name" />
                        </div>
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group other_city d-none">
                            <label for="city" class="form-check-label"><?= !empty($this->lang->line('city')) ? $this->lang->line('city') : 'City Name' ?></label>
                            <input type="text" class="form-control" id="other_city_value" name="other_city" placeholder="City" />
                        </div>
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group other_areas d-none">
                            <label for="area" class="form-check-label">Area</label>
                            <input type="text" class="form-control" id="other_areas_value" name="other_areas" placeholder="Area Name" />
                        </div>
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group other_pincode d-none">
                            <label for="area" class="form-check-label">Pincode</label>
                            <input type="text" class="form-control " id="other_pincode_value" name="pincode_name" placeholder="Zipcode" />
                        </div>
                        <!- <input type="text" name="other_areas" id="other_areas" class="d-none"> ->
                        <!- <div class="col-md-4 col-sm-12 col-xs-12 form-group edit_pincode">
                            <label for="pincode" class="form-check-label"><? //= !empty($this->lang->line('pincode')) ? $this->lang->line('pincode') : 'Zipcode' 
                                                                            ?></label>
                            <input type="text" class="form-control" id="edit_pincode" name="pincode" placeholder="Name" readonly />
                        </div> ->
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group area">
                            <label for="pincode" class="control-label"><?= !empty($this->lang->line('pincode')) ? $this->lang->line('pincode') : 'Zipcode' ?></label>
                            <select name="pincode" id="edit_pincode" class="form-control">
                            </select>
                        </div>
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group">
                            <label for="state" class="form-check-label"><?= !empty($this->lang->line('state')) ? $this->lang->line('state') : 'State' ?></label>
                            <input type="text" class="form-control" id="edit_state" name="state" placeholder="State" />
                        </div>
                        <div class="col-md-6 col-sm-12 col-xs-12 form-group">
                            <label for="country" class="form-check-label"><?= !empty($this->lang->line('country')) ? $this->lang->line('country') : 'Country' ?></label>
                            <input type="text" class="form-control" name="country" id="edit_country" placeholder="Country" />
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12 form-group">
                            <label for="country" class="form-check-label"><?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Type : ' ?></label>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="edit_home" value="home" />
                                <label for="home" class="form-check-label text-dark"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="edit_office" value="office" placeholder="Office" />
                                <label for="office" class="form-check-label text-dark"><?= !empty($this->lang->line('office')) ? $this->lang->line('office') : 'Office' ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="type" id="edit_other" value="other" placeholder="Other" />
                                <label for="other" class="form-check-label text-dark"><?= !empty($this->lang->line('other')) ? $this->lang->line('other') : 'Other' ?></label>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                            <input type="submit" class="btn btn-primary btn-sm" id="edit-address-submit-btn" value="Save" />
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12 text-center mt-2">
                            <div id="edit-address-result"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> -->

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="<?= THEME_ASSETS_URL . '/js/checkout.js' ?>"></script>