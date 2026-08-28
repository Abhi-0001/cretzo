<?php 
// Safe defaults
$cart_count = isset($cart[0]['cart_count']) && !empty($cart[0]['cart_count']) ? $cart[0]['cart_count'] : 0;
$is_logged_in = isset($is_logged_in) ? (int)$is_logged_in : 0;
$logo = get_settings('web_logo');
?>


<input type="hidden" id="input_cart_count" value="<?= $cart_count ?>">
<input type="hidden" name="promo_set" id="promo_set" value="" />
<input type="hidden" name="promo_code_amount" id="promo_code_amount" value="0" />


<!-- main container starts -->
<section class="cart-container">
    <!-- <h1 class="heading-n ta-c">Cart</h1> -->

    <!-- card indicator -->
    <div class="cart-indicator-container">
        <div class="company-logo">
            <?php $logo = get_settings('web_logo'); ?>
            <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="main-logo" alt="site-logo image"></a>
        </div>
        <div class="cart-indicator cart-indicator-active rounded-end">
            <p style="font-size: 18px;">Cart</p>
        </div>
        <div class="completion-line active"></div>
        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart/checkout') ?>">
                <p style="font-size: 18px;">Address</p>
            </a>
        </div>
        <div class="completion-line"></div>
        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart/checkout') ?>">
                <p style="font-size: 18px;">Payment</p>
            </a>
        </div>
        <div class="quality-assured-container">
            <img class="quality-assured-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/orange-tick.png') ?>">
            <span class="text-s quality-assured-text">100% Quality Assured</p>
        </div>
    </div>

    <!-- cart -->
    <div class="cart">
        <div class="cart-left">

            <a href="<?= base_url('products') ?>" class="cretzo small-btn text-decoration-none px-0 text-s btn-nav-prev"><i class="fa fa-arrow-left mr-1"></i>Find more products</a>

            <div class="cart-left-two">
                <p class="text-n">Available Offers</p>
                <p class="text-s">20% instant discount on Kotak Credit Card EMI transaction on min spend of ₹3,500.</p>
                <p class="text-s c-p show-more-text">Show More<img class="show-more-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/orange-arrow.png') ?>"></p>
            </div>

            <div class="cart-left-three">
                <?php $cart_empty = $cart_count <= 0 ?>
                <div>
                    <input type="checkbox" id="select-all-checkbox" <?php echo $cart_empty ? 'disabled' : '' ?> >
                    <label class="text-n"><span class="cart-count-checked">0</span>/<span class="cart-count"><?=$cart_count?></span> Items selected</label>
                </div>
                <div>
                    <a href="javascript:void(0)" id="clear_cart" class="selected-items-action-btn disabled">
                        <span class="text-s cart-left-three-btn">Remove</span>
                    </a>
                    <span class="text-n spacer">|</span>
                    <a href="javascript:void(0)" id="wishlist-all" class="selected-items-action-btn disabled">
                        <span class="text-s cart-left-three-btn">Add to wishlist</span>
                    </a>
                </div>
            </div>

            <ul class="cart-items-container">
                <!-- <li class="cart-item">
                    <div class="cart-item-img-container">
                        <img class="cart-item-img" src="../images/cart-item1.png">
                    </div>
                    <div class="cart-item-detail-container">
                        <h1 class="text-n">Ray-Ban Junior</h1>
                        <p class="text-s">Lorem ipsum dolor sit amet consectetur adipisicing elit. Eum dolore nostrum velit.</p>
                        <p class="text-es">Lorem ipsum dolor sit.</p>
                        <div class="cart-item-detail-dropdown-container">
                            <select class="cart-item-detail-dropdown"> name="Size:">
                                <option>Size:</option>
                                <option>M</option>
                                <option>L</option>
                                <option>Xl</option>
                            </select>
                            <select class="cart-item-detail-dropdown" name="Qty:">
                                <option>Qty:</option>
                                <option>1</option>
                                <option>2</option>
                            </select>
                        </div>
                        <p class="text-b"><span class="actual-price">₹7080</span><span class="discounted-price"> ₹3000 </span><span class="discount">40% OFF</span></p>
                    </div>
                    <img class="cross-icon" src="../images/cross-icon1.png">
                </li> -->

                <?php
                    // $total_mrp = 0;
                    // $total_discount_on_mrp = 0;
                    // Tracks whether any cart line is out of stock, so we can block checkout below.
                    $has_out_of_stock = false;

                    foreach ($cart as $key => $row) {

                    if (isset($row['qty']) && $row['qty'] != 0) {
                        $price = $row['special_price'] != '' && $row['special_price'] != null && $row['special_price'] > 0 ? $row['special_price'] : $row['price'];
                        $row_stock_status = validate_stock([$row['id']], [$row['qty']]);
                        if (isset($row_stock_status['error']) && $row_stock_status['error'] == TRUE) {
                            $has_out_of_stock = true;
                        }
                ?>

                        <li class="cart-item cart-product" data-product-id="<?= $row['product_id']; ?>">
                            <div class="cart-item-img-container">
                                <a href="<?= base_url('products/details/' . $row['slug']) ?>">
                                    <img class="cart-item-img" src="<?= $row['product_variants'][0]['variant_image'] ?? $row['image'] ?>" alt="<?= $row['name']; ?>" style="object-fit: cover;" />
                                </a>
                                <!-- <img class="cart-item-img" src="../images/cart-item1.png"> -->
                            </div>
                            <div class="id">
                                <input type="hidden" name="<?= 'id[' . $key . ']' ?>" id="id" value="<?= $row['id'] ?>">
                            </div>
                            <div class="cart-item-detail-container">
                                <h1 class="text-n">
                                    <a class="text-decoration-none text-dark" href="<?= base_url('products/details/' . $row['slug']) ?>" target="_blank">
                                        <?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])); ?>
                                    </a>
                                </h1>
                                <!-- <p class="text-s"><?= preg_replace('/((\w+\W*){'.(20-1).'}(\w+))(.*)/', '${1}', (output_escaping(str_replace('\r\n', '&#13;&#10;', $row['short_description'])))); ?></p> -->
                                
                                <?php
                                    if(isset($row['product_variants'][0]['attr_name']) && isset($row['product_variants'][0]['variant_values'])){
                                        $attr_names = explode(',', $row['product_variants'][0]['attr_name']);
                                        $attr_values = explode(',', $row['product_variants'][0]['variant_values']);
                                        echo '<p class="text-s product-variant-detail px-1">';
                                        for($i = 0; $i < sizeof($attr_names); $i++){
                                            echo '<span class="mr-1">';
                                            echo $attr_names[$i].': ';
                                            echo $attr_values[$i];
                                            echo '</span>';
                                        }
                                        echo '</p>';
                                    }
                                ?>

                                <p class="text-s mt-1"><?= word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', $row['short_description'])), 100); ?></p>
                                <p class="text-es"><?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['store_name'])) ?></p>

                                <div class="cart-item-detail-span mt-1">

                                    <!-- <td class="item-quantity"> -->
                                        <!-- <div class="num-block skin-2 product-quantity">
                                            <?php $check_current_stock_status = validate_stock([$row['id']], [$row['qty']]); ?>
                                            <?php if (isset($check_current_stock_status['error'])  && $check_current_stock_status['error'] == TRUE) { ?>
                                                <div><span class='text text-danger'> Out of Stock </span></div>
                                            <?php } else { ?>
                                                <div class="num-in form-control d-flex align-items-center">
                                                    <?php $price = $row['special_price'] != '' && $row['special_price'] != null && $row['special_price'] > 0 ? $row['special_price'] : $row['price']; ?>
                                                    <span class="minus dis" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>"></span>
                                                    <input type="text" class="in-num itemQty" data-page="cart" data-id="<?= $row['id']; ?>" value="<?= $row['qty'] ?>" data-price="<?= $price ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '' ?>">
                                                    <span class="plus" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '0' ?> " data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>"></span>
                                                </div>
                                            <?php } ?>
                                        </div> -->

                                        <div class="num-block skin-2 product-quantity">
                                            <?php $check_current_stock_status = validate_stock([$row['id']], [$row['qty']]); ?>
                                            <?php if (isset($check_current_stock_status['error'])  && $check_current_stock_status['error'] == TRUE) { ?>
                                                <div><span class='text text-danger'> Out of Stock </span></div>
                                            <?php } else { ?>
                                                <div class="num-in form-control d-flex align-items-center mr-2">
                                                    <?php $price = $row['special_price'] != '' && $row['special_price'] != null && $row['special_price'] > 0 ? $row['special_price'] : $row['price']; ?>
                                                    <?php $original_price = $row['price']; ?>

                                                    <!-- <span class="minus dis" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>"></span>
                                                    <input type="text" class="in-num itemQty" data-page="cart" data-id="<?= $row['id']; ?>" value="<?= $row['qty'] ?>" data-price="<?= $price ?>" data-original-price="<?= $original_price ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '' ?>">
                                                    <span class="plus" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '0' ?> " data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>"></span> -->

                                                    <span class="minus dis" style="background-image: url('<?= base_url('assets/front_end/cretzo/img/new_cretzo/minus.png') ?>');" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>">
                                                        <!-- <i class="fa fa-minus"></i> -->
                                                    </span>
                                                    <input type="text" class="in-num itemQty" data-page="cart" data-id="<?= $row['id']; ?>" value="<?= $row['qty'] ?>" data-price="<?= $price ?>" data-original-price="<?= $original_price ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '' ?>">
                                                    <span class="plus" style="background-image: url('<?= base_url('assets/front_end/cretzo/img/new_cretzo/plus.png') ?>');" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '0' ?> " data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>">
                                                        <!-- <i class="fa fa-plus"></i> -->
                                                    </span>
                                                    
                                                    <!-- <span class="minus dis" data-min="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['minimum_order_quantity'])) ? $product['product'][0]['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['quantity_step_size'])) ? $product['product'][0]['quantity_step_size'] : 1 ?>"></span>
                                                    <input type="text" name="qty" class="in-num" value="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['minimum_order_quantity'])) ? $product['product'][0]['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['quantity_step_size'])) ? $product['product'][0]['quantity_step_size'] : 1 ?>" data-min="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['minimum_order_quantity'])) ? $product['product'][0]['minimum_order_quantity'] : 1 ?>" data-max="<?= (isset($product['product'][0]['total_allowed_quantity']) && !empty($product['product'][0]['total_allowed_quantity'])) ? $product['product'][0]['total_allowed_quantity'] : '' ?>">
                                                    <span class="plus" data-max="<?= (isset($product['product'][0]['total_allowed_quantity']) && !empty($product['product'][0]['total_allowed_quantity'])) ? $product['product'][0]['total_allowed_quantity'] : '' ?> " data-step="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['quantity_step_size'])) ? $product['product'][0]['quantity_step_size'] : 1 ?>"></span> -->
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <!-- </td> -->

                                    <?php 
                                        $original_price = $row['price'];
                                        $is_special_price = $row['special_price'] != '' && $row['special_price'] != null && $row['special_price'] > 0;
                                        $price = $is_special_price ? $row['special_price'] : $original_price; 
                                        
                                        // add up Total Discount on MRP
                                        // $discount_on_mrp = ($original_price * $row['qty']) - ($price * $row['qty']);
                                        // $total_discount_on_mrp += $discount_on_mrp;

                                        // add up Total MRP
                                        // $total_mrp += ($original_price * $row['qty']);
                                    ?>



                                    <?php if($price == $original_price){?>
                                        <p class="text-b total-price"><span class="actual-price product-line-price"><?= $settings['currency'] . '' . number_format(($row['qty'] * $price), 0) ?></span></p>
                                    <?php }
                                    else{ ?>
                                        <p class="text-b total-price"><span class="discounted-price product-line-price"><?= $settings['currency'] . '' . number_format(($row['qty'] * $price), 0) ?></span><span class="actual-price product-line-price"><?= $settings['currency'] . '' . number_format(($row['qty'] * $original_price), 0) ?></span><span class="discount"><?= number_format((($original_price - $price)/$original_price)*100, 0); ?>% OFF</span></p>
                                    <?php } ?>
                                    
                                    <!-- <p class="text-b"><span class="actual-price">₹7080</span><span class="discounted-price"> ₹3000 </span><span class="discount">40% OFF</span></p> -->

                                </div>

                            </div>
                            <a class="product-removal link_cursor">
                                <i class="remove-product" name="remove_inventory" id="remove_inventory" data-id="<?= $row['id']; ?>" title="Remove from Cart">
                                    <img class="cross-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/cross-icon1.png') ?>">
                                </i>
                            </a>
                        </li>

                        <tr class="cart-product-desc-list">
                            <td class="option text-start d-flex flex-row align-items-center ps-0" title="<?= $row['name']; ?>">
                                <!-- <figure class="rounded cart-img">
                                    <a href="<?= base_url('products/details/' . $row['slug']) ?>">
                                        <img src="<?= $row['image'] ?>" alt="<?= $row['name']; ?>" style="object-fit: cover;" /></a>
                                </figure> -->
                                
                                <!-- <div class="id">
                                    <input type="hidden" name="<?= 'id[' . $key . ']' ?>" id="id" value="<?= $row['id'] ?>">
                                </div> -->

                                <!-- <div class="w-100 ms-4">
                                    <h3 class="post-title h6 lh-xs mb-1" title="<?= $row['name']; ?>">
                                        <a class="text-decoration-none text-dark" href="<?= base_url('products/details/' . $row['slug']) ?>" target="_blank">
                                            <?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])); ?>
                                        </a>
                                        <?php if (!empty($row['product_variants'])) { ?>
                                            <br><?= str_replace(',', ' | ', $row['product_variants'][0]['variant_values']) ?>
                                        <?php } ?>
                                    </h3>
                                </div> -->

                            </td>
                            <!-- <td>
                                <p class="price"><span class="amount"><?= $settings['currency'] . '' . number_format($price, 2) ?></span></p>
                            </td> -->
                            <!-- <td>
                                <?= isset($row['tax_percentage']) && !empty($row['tax_percentage']) ? $row['tax_percentage'] : '-' ?>
                            </td>
                            <td class="item-quantity">
                                <div class="num-block skin-2 product-quantity">
                                    <?php $check_current_stock_status = validate_stock([$row['id']], [$row['qty']]); ?>
                                    <?php if (isset($check_current_stock_status['error'])  && $check_current_stock_status['error'] == TRUE) { ?>
                                        <div><span class='text text-danger'> Out of Stock </span></div>
                                    <?php } else { ?>
                                        <div class="num-in form-control d-flex align-items-center">
                                            <?php $price = $row['special_price'] != '' && $row['special_price'] != null && $row['special_price'] > 0 ? $row['special_price'] : $row['price']; ?>
                                            <span class="minus dis" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>"></span>
                                            <input type="text" class="in-num itemQty" data-page="cart" data-id="<?= $row['id']; ?>" value="<?= $row['qty'] ?>" data-price="<?= $price ?>" data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>" data-min="<?= (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1 ?>" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '' ?>">
                                            <span class="plus" data-max="<?= (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : '0' ?> " data-step="<?= (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1 ?>"></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </td> -->
                            <!-- <td class="text-center p-0 total-price"><span class="product-line-price"> <?= $settings['currency'] . '' . number_format(($row['qty'] * $price), 2) ?></span></td> -->
                            <!-- <td class="d-flex gap-2 align-items-center border-0">
                                <a class="product-removal link_cursor">
                                    <i class="remove-product uil uil-trash-alt fs-23 text-danger" name="remove_inventory" id="remove_inventory" data-id="<?= $row['id']; ?>" title="Remove from Cart"></i>
                                </a>
                                <a class="save-for-later remove-product link_cursor" data-id="<?= $row['id']; ?>">
                                    <i class="uil uil-bag-alt fs-23 text-blue" title="Save for Later"></i>
                                </a>
                            </td> -->
                        </tr>
                <?php }
                } ?>
            </ul>
        </div>
        <div class="cart-right">
             <?php  if ($is_logged_in) { ?>
            <div class="cart-right-one">
                <h1 class="text-b">COUPONS</h1>
                <div id="coupon-widget-default">
                    <p class="flex-1" style="font-size: 18px;"><img class="coupon-tag-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/tag-icon.png') ?>">Apply Coupons</p>
                    <button class="cretzo btn btn-light text-n apply-btn" data-bs-toggle="modal" data-bs-target="#promo-code-modal">APPLY</button>
                </div>
                <div id="coupon-widget-applied" class="d-none">
                    <p class="flex-1"  style="font-size: 18px;"><img class="coupon-tag-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/tag-icon.png') ?>">1 Coupon Applied</p>
                    <button class="cretzo btn btn-light text-n edit-btn" data-bs-toggle="modal" data-bs-target="#promo-code-modal">EDIT</button>
                </div>
                <p class="text-s" id="coupon-widget-subtext">Show Your Support For Our Artisans By Purchasing Their Handcrafted Artworks.</p>
            </div>
            <?php } ?>
            <div class="cart-right-two">
                <h1 class="text-b"><img class="delivery-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/delivery-icon.png') ?>"> Delivery Estimates</h1>
                <ul class="list delivery-list pt-2" id="delivery-list">
                    <?php
                    // server side: show for logged-in users
                    if ($is_logged_in) {
                        foreach ($cart as $key => $row) {
                            if (!isset($row['qty']) || $row['qty'] == 0) continue;
                            $img = $row['product_variants'][0]['variant_image'] ?? $row['image'];
                            // server side date (7-14 days)
                            $start = (new DateTime())->modify('+7 day')->format('j M Y');
                            $end = (new DateTime())->modify('+14 day')->format('j M Y');
                            $not_deliverable = isset($delivery_availability[$row['product_id']]) && $delivery_availability[$row['product_id']]['is_deliverable'] == false;
                            ?>
                            <li class="delivery-list-item<?= $not_deliverable ? ' not-deliverable' : '' ?>" id="delivery-p-<?= $row['id'] ?>">
                                <div><img class="delivery-list-item-img" src="<?= $img ?>"></div>
                                <?php if ($not_deliverable) { ?>
                                    <div><p class="text-n text-danger">Not deliverable to your address<?= !empty($delivery_availability[$row['product_id']]['message']) ? ' — ' . html_escape($delivery_availability[$row['product_id']]['message']) : '' ?></p></div>
                                <?php } else { ?>
                                    <div><p class="text-n">Estimated delivery: <span class="fw-b"><?= $start ?> - <?= $end ?></span></p></div>
                                <?php } ?>
                            </li>
                        <?php
                        }
                    }
                    ?>
                </ul>
            </div>

            <?php
            // server side totals if available
            $delivery_charge = !empty($cart['sub_total']) ? ($cart['delivery_charge'] ?? 0) : 0;
            $subtotal = !empty($cart['sub_total']) ? ($cart['overall_amount'] - $delivery_charge) : 0;
            $total = !empty($cart['sub_total']) ? ($cart['overall_amount'] ?? 0) : 0;
            $total_mrp = !empty($cart['total_mrp']) ? $cart['total_mrp'] : 0;
            $total_discount_on_mrp = !empty($cart['discount_on_mrp']) ? $cart['discount_on_mrp'] : 0;
            $total = $total - $delivery_charge;
            // Seller-paid shipping. Stated on the cart as well as at checkout - it is a reason
            // to carry on to checkout, and the cart had no delivery line at all before, so a
            // customer had no way to know whether one was coming.
            $is_free_delivery = !empty($cart['is_free_delivery']);
            ?>

            <div class="cart-right-three">
                <h1 class="text-b">PRICE DETAIL (<span class="fw-n cart-count"><?= $cart_count ?></span> Items)</h1>
                <table class="bill-container">
                    <tr class="bill-row"><td class="text-s bill-column">Total MRP</td><td class="text-s bill-column" id="final_total_mrp">₹<?= moneyFormatIndia(round($total_mrp)) ?></td></tr>
                    <tr class="bill-row"><td class="text-s bill-column">Discount on MRP</td><td class="text-s bill-column" id="final_discount_mrp">- ₹<?= moneyFormatIndia(round($total_discount_on_mrp)) ?></td></tr>
                    <tr class="bill-row"><td class="text-s bill-column">Subtotal</td><td class="text-s bill-column" id="final_subtotal">₹<?= moneyFormatIndia(round($subtotal)) ?></td></tr>
                    <tr class="bill-row"><td class="text-s bill-column">Coupon Discount</td>
                        <td class="text-s bill-column">
                            <span class="final-promocode-amount d-none"></span>
                            <span class="final-promocode d-none">₹<?= moneyFormatIndia(0) ?></span>
                            <a href="#" class="see-offers-btn" data-bs-toggle="modal" data-bs-target="#promo-code-modal" style="color: var(--color-orange) !important;">See Offers</a>
                        </td>
                    </tr>
                    <tr class="bill-row"><td class="text-s bill-column">Platform Free</td><td class="text-s bill-column">FREE</td></tr>
                    <?php if ($is_free_delivery) { ?>
                        <tr class="bill-row"><td class="text-s bill-column">Delivery Fee</td><td class="text-s bill-column fw-b" style="color: var(--color-success);">FREE</td></tr>
                    <?php } ?>
                    <tr class="bill-row bill-row-last"><td class="text-n bill-column fw-b">Total Amount</td><td class="text-n bill-column fw-b" id="final_total">₹<?= moneyFormatIndia(round($total)) ?></td></tr>
                </table>

            <?php if($is_logged_in){ ?>
                <div class="input-group my-4 mt-0">
                    <input type="text" class="form-control promocode_input" placeholder="Promo code">
                    <div class="input-group-append">
                        <button class="cretzo btn btn-dark btn-primary rounded-end py-1 redeem_btn"><?= !empty($this->lang->line('redeem')) ? $this->lang->line('redeem') : 'Redeem' ?></button>
                        <button class="cretzo btn btn-dark btn-danger d-none py-1 clear_promo_btn"><?= !empty($this->lang->line('clear')) ? $this->lang->line('clear') : 'Clear' ?></button>
                    </div>
                </div>
            <?php } ?>

            <?php if($is_logged_in){ ?>
                <button class="cretzo btn btn-dark w-100" id="place-order-btn" data-url="<?= base_url('cart/checkout') ?>" data-out-of-stock="<?= $has_out_of_stock ? '1' : '0' ?>" <?= $has_out_of_stock ? 'disabled' : '' ?>>Go To Checkout</button>
                <?php if($has_out_of_stock){ ?>
                    <p class="text text-danger text-center mt-2 mb-0" style="font-size: 13px;">Some item(s) in your cart are out of stock. Please remove them or save them for later to continue.</p>
                <?php } ?>
            <?php }else{ ?>
                <button class="cretzo btn btn-dark w-100" data-bs-toggle="modal" data-bs-target="#modal-signin" >Go To Checkout</button>
            <?php } ?>
            </div>
        </div>
    </div>
</section>
<!-- main container ends -->

<!-- Modal for Coupon Codes -->
<div class="modal fade" id="promo-code-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title w-100 text-start m-0"><?= !empty($this->lang->line('promocodes')) ? $this->lang->line('promocodes') : 'Apply Coupon' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="promo-modal-head">
                <div class="promo-check-row">
                    <input type="text" class="promo-check-input" id="promo_modal_input" placeholder="Enter coupon code" autocomplete="off">
                    <button type="button" class="promo-check-btn" id="promo_modal_check_btn">CHECK</button>
                </div>

                <div id="promo-modal-toast" class="promo-modal-toast d-none"></div>
            </div>
            <div class="modal-body">
                <section id="promo_code_form">
                    <ul id="promocode-list" class="promo-list"></ul>
                </section>
            </div>
            <div class="promo-modal-footer">
                <div class="promo-modal-savings">
                    <span class="text-s d-block">Maximum savings:</span>
                    <span class="fw-b" id="promo_modal_max_savings">₹0</span>
                </div>
                <button type="button" class="promo-modal-apply-btn" id="promo_modal_apply_btn" disabled>APPLY</button>
            </div>
            <!--/.modal-content -->
        </div>
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->

<style>
    #promo-code-modal .modal-content {
        border-radius: 5px;
        overflow: hidden;
        padding-bottom: 0;
    }
    #promo-code-modal .modal-title {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: .3px;
    }
    #promo-code-modal .modal-body {
        max-height: 60vh;
        overflow-y: auto;
        padding: 5px 25px 25px;
    }

    /* Fixed (non-scrolling) coupon "check" input row */
    #promo-code-modal .promo-modal-head {
        flex: 0 0 auto;
        padding: 10px 25px 0;
        background: #fff;
    }

    /* Coupon "check" input row */
    #promo-code-modal .promo-check-row {
        display: flex;
        align-items: center;
        border: 1px solid #e2e2e2;
        border-radius: 8px;
        margin-bottom: 16px;
        overflow: hidden;
        background: #fff;
    }
    #promo-code-modal .promo-check-input {
        flex: 1 1 auto;
        min-width: 0;
        border: 0;
        outline: none;
        padding: 12px 14px;
        font-size: 14px;
        background: transparent;
    }
    #promo-code-modal .promo-check-btn {
        flex-shrink: 0;
        border: 0;
        background: transparent;
        color: var(--color-orange, #f26722);
        font-weight: 700;
        letter-spacing: .3px;
        font-size: 13px;
        padding: 0 16px;
        cursor: pointer;
    }
    #promo-code-modal .promo-check-btn:disabled {
        opacity: .5;
        cursor: default;
    }

    /* Inline toast, rendered INSIDE the modal so it can never end up
       hidden behind the modal backdrop / fade transition */
    #promo-code-modal .promo-modal-toast {
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        margin-bottom: 14px;
    }
    #promo-code-modal .promo-modal-toast.is-success {
        background: #eaf7ee;
        color: #1e7b34;
        border: 1px solid #cdeed7;
    }
    #promo-code-modal .promo-modal-toast.is-error {
        background: #fdecec;
        color: #c62828;
        border: 1px solid #f6cccc;
    }

    #promo-code-modal .promo-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    #promo-code-modal .promo-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
    }
    #promo-code-modal .promo-card:last-child {
        border-bottom: 0;
    }
    #promo-code-modal .promo-card-select {
        flex-shrink: 0;
        padding-top: 2px;
    }
    #promo-code-modal .promo-card-select input {
        display: none;
    }
    #promo-code-modal .promo-card-checkmark {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 2px solid #d3d3d3;
        background: #fff;
        color: #fff;
        font-size: 12px;
        transition: background .15s ease, border-color .15s ease;
    }
    #promo-code-modal .promo-card-select input:checked + .promo-card-checkmark {
        background: var(--color-orange, #f26722);
        border-color: var(--color-orange, #f26722);
    }
    #promo-code-modal .promo-card-select input:checked + .promo-card-checkmark::after {
        content: "\2713";
    }
    #promo-code-modal .promo-card-body {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    #promo-code-modal .promo-card-code-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        align-self: flex-start;
        border: 1px dashed var(--color-orange, #f26722);
        border-radius: 6px;
        padding: 6px 12px;
        color: var(--color-orange, #f26722);
        font-weight: 700;
        letter-spacing: .5px;
        font-size: 14px;
    }
    #promo-code-modal .promo-card-save {
        margin: 0;
        font-weight: 700;
        color: #1a1a1a;
        font-size: 14px;
    }
    #promo-code-modal .promo-card-off {
        margin: 0;
        color: #1a1a1a;
        font-size: 13px;
    }
    #promo-code-modal .promo-card-expiry {
        margin: 0;
        color: #8a8a8a;
        font-size: 12px;
    }
    #promo-code-modal .promo-card-desc {
        margin: 2px 0 0;
        color: #6b6b6b;
        font-size: 12px;
        line-height: 1.5;
        text-align: left;
    }
    #promo-code-modal .promo-empty {
        list-style: none;
        text-align: center;
        color: #6b6b6b;
        padding: 24px 0;
    }

    /* Sticky apply bar */
    #promo-code-modal .promo-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 24px;
        background: #f5f5f5;
        border-top: 1px solid #ececec;
    }
    #promo-code-modal .promo-modal-savings {
        font-size: 13px;
        color: #333;
        line-height: 1.4;
    }
    #promo-code-modal .promo-modal-apply-btn {
        border: 0;
        border-radius: 8px;
        background: var(--color-orange, #f26722);
        color: #fff;
        font-weight: 700;
        letter-spacing: .5px;
        padding: 12px 40px;
        cursor: pointer;
        transition: opacity .15s ease;
    }
    #promo-code-modal .promo-modal-apply-btn:disabled {
        opacity: .5;
        cursor: default;
    }
</style>

<!-- CLIENT-SIDE CART MANAGER (localStorage) -->
<script>
/*
  Assumptions for localStorage cart item structure:
  {
    id: <cart item id or product id unique>,
    product_id: <product id>,
    variant_id: <variant id or null>,
    name: "...",
    image: "...",
    price: <selling price>,
    mrp: <mrp or null>,
    quantity: <integer>
  }
*/

(function($){
    const isLoggedIn = <?= $is_logged_in ?> === 1;
    const $itemsContainer = $('.cart-items-container');
    const $cartCountEls = $('.cart-count, .cart-count-checked, .cart-count-checked');
    const $selectAll = $('#select-all-checkbox');
    const $clearCartBtn = $('#clear_cart');
    const $wishlistBtn = $('#wishlist-all');
    const $deliveryList = $('#delivery-list');
    const keys = {
        storage: 'cart'
    };

    // UTILS
    function readCartFromStorage(){
        try {
            const raw = localStorage.getItem(keys.storage);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            console.error('Invalid cart JSON', e);
            return [];
        }
    }
    function writeCartToStorage(cart){
        localStorage.setItem(keys.storage, JSON.stringify(cart));
    }

    function mergeCartItems(cart){
        // Merge duplicates by product_id + variant_id
        const map = {};
        cart.forEach(item => {
            const key = `${item.product_id || item.id}_${item.variant_id || ''}`;
            if (!map[key]) {
                map[key] = Object.assign({}, item);
                map[key].quantity = parseInt(map[key].quantity || map[key].qty || 0);
            } else {
                map[key].quantity = parseInt(map[key].quantity || 0) + parseInt(item.quantity || item.qty || 0);
            }
        });
        return Object.values(map);
    }

    function formatCurrency(n){
        // simple formatting (no locale dependence)
        return '₹' + parseFloat(n || 0).toFixed(2);
    }

    function computeTotals(cart){
        let subtotal = 0, totalMrp = 0, totalDiscount = 0;
        cart.forEach(item => {
            const qty = parseInt(item.quantity || 0);
            const price = parseFloat(item.price || 0);
            const mrp = item.mrp ? parseFloat(item.mrp) : null;
            subtotal += price * qty;
            if (mrp) {
                totalMrp += mrp * qty;
                totalDiscount += (mrp - price) * qty;
            } else {
                totalMrp += price * qty;
            }
        });
        return { subtotal, totalMrp, totalDiscount, totalAmount: subtotal };
    }

    function createItemHtml(item, index){
        const price = parseFloat(item.price || 0).toFixed(2);
        const mrp = item.mrp ? parseFloat(item.mrp).toFixed(2) : null;
        const qty = parseInt(item.quantity || 0);
        const itemTotal = (parseFloat(item.price || 0) * qty).toFixed(2);
        return `

        <li class="cart-item cart-product" data-index="${index}" data-product-id="${item.product_id || ''}" data-variant-id="${item.variant_id || ''}" data-id="${item.id || ''}">
                            <div class="cart-item-img-container">
                                <a href="#">
                                    <img class="cart-item-img" src="${item.image || ''}" alt="${(item.name || '').replace(/"/g,'&quot;')}" alt="RTX 4090" style="object-fit: cover;">
                                </a>
                                
                            </div>
                            <div class="id">
                                <input type="hidden" name="id[0]" id="id" value="22">
                            </div>
                            <div class="cart-item-detail-container">
                                <h1 class="text-n">
                                    <a class="text-decoration-none text-dark" href="#" target="_blank">
                                       ${item.name || ''}                                 </a>
                                </h1>
                                
                                
                                <p class="text-s mt-1">${item.variant_name || ''}</p>
                              

                                <div class="cart-item-detail-span mt-1">


                                        <div class="num-block skin-2 product-quantity">
                                                 <div class="num-in form-control d-flex align-items-center mr-2">
                                                                                                        
                                                

                                                    <span class="minus dis quantity-btn minus" style="background-image: url('https://www.cretzo.com/assets/front_end/cretzo/img/new_cretzo/minus.png');" data-min="1" data-index="${index}">
                                                     
                                                    </span>
                                                    <input type="text" class="in-num itemQty quantity-input" min="1"  data-page="cart"  data-index="${index}" data-max="" value="${qty}">
                                                    <span class="plus quantity-btn plus" data-index="${index}" style="background-image: url('https://www.cretzo.com/assets/front_end/cretzo/img/new_cretzo/plus.png');">
                                                       
                                                    </span>
                                                    
                                                  
                                                </div>
                                                                                    </div>
                                  

                                    


                                                                           <p class="text-b total-price">
                                                                                <span class="discounted-price product-line-price">₹${price}</span>
                                                                                ${mrp ? `
                                                                                    <span class="actual-price product-line-price">₹${mrp}</span>
                                                                                    <span class="discount">${Math.round(((mrp - price) / mrp) * 100)}% OFF</span>
                                                                                ` : ''}
                                                                            </p>

                                                                        
                                   

                                </div>

                            </div>
                            <a class="remove-item product-removal link_cursor">
                                <i class="remove-product remove-item" name="remove_inventory" id="remove_inventory" data-index="${index}" title="Remove from Cart">
                                    <img class="cross-icon" src="https://www.cretzo.com/assets/front_end/cretzo/img/new_cretzo/cross-icon1.png">
                                </i>
                            </a>
                        </li>

           
        `;
   
        
    }

    function renderCartUi(cart){
        // merge duplicates for presentation
        const merged = mergeCartItems(cart);
        // render items
        if (merged.length === 0){
            $itemsContainer.html('<div class="text-center py-5"><h4>Your cart is empty</h4><p>Add some items to get started</p><a href="<?= base_url('products') ?>" class="btn btn-info">Continue Shopping</a></div>');
            $('.cart-right').hide();
            $('.cart-count').text(0);
            $('.cart-count-checked').text(0);
            $clearCartBtn.addClass('disabled'); $wishlistBtn.addClass('disabled'); $selectAll.prop('checked', false).prop('disabled', true);
            return;
        }

        let html = '';
        merged.forEach((item, idx)=> html += createItemHtml(item, idx));
        $itemsContainer.html(html);
        $('.cart-right').show();

        // totals
        const totals = computeTotals(merged);
        $('#final_subtotal').text(formatCurrency(totals.subtotal));
        $('#final_total_mrp').text(formatCurrency(totals.totalMrp));
        $('#final_discount_mrp').text('- ' + formatCurrency(totals.totalDiscount));
        $('#final_total').text(formatCurrency(totals.totalAmount));
        $('.cart-count').text(merged.length);
        $('.cart-count-checked').text(0);
        $clearCartBtn.removeClass('disabled'); $wishlistBtn.removeClass('disabled'); $selectAll.prop('disabled', false);

        // delivery list: 7-14 days from today
        let deliveryHtml = '';
        merged.forEach(item => {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            start.setDate(start.getDate() + 7);
            const end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            end.setDate(end.getDate() + 14);
            const startFmt = start.toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            const endFmt = end.toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

            deliveryHtml += `<li class="delivery-list-item">
                                <div><img class="delivery-list-item-img" src="${item.image || ''}"></div>
                                <div><p class="text-n">Estimated delivery: <span class="fw-b">${startFmt} - ${endFmt}</span></p></div>
                             </li>`;
        });
        $deliveryList.html(deliveryHtml);
    }

    // initialize UI for non-logged in
    function initClientCart(){
        // read & merge duplicates on load
        let cart = readCartFromStorage();
        cart = mergeCartItems(cart);
        writeCartToStorage(cart);
        renderCartUi(cart);
    }

    // update one item qty in storage by index (merged index)
    function updateQtyByIndex(index, quantity){
        let cart = readCartFromStorage();
        cart = mergeCartItems(cart);
        if (!cart[index]) return;
        cart[index].quantity = Math.max(1, parseInt(quantity || 1));
        writeCartToStorage(cart);
        renderCartUi(cart);
    }

    // remove by index (merged index)
    function removeByIndex(index){
        let cart = readCartFromStorage();
        cart = mergeCartItems(cart);
        if (!cart[index]) return;
        cart.splice(index, 1);
        writeCartToStorage(cart);
        renderCartUi(cart);
    }

    function clearCart(){
        localStorage.removeItem(keys.storage);
        renderCartUi([]);
    }

    // wire events (delegated)
    $(document).on('click', '.quantity-btn', function(){
        const idx = parseInt($(this).data('index'));
        const isPlus = $(this).hasClass('plus');
        const $input = $(`.quantity-input[data-index="${idx}"]`);
        let cur = parseInt($input.val() || 1);
        if (isPlus) cur++;
        else if (cur > 1) cur--;
        $input.val(cur);
        updateQtyByIndex(idx, cur);
    });

    $(document).on('change', '.quantity-input', function(){
        const idx = parseInt($(this).data('index'));
        let qty = parseInt($(this).val() || 1);
        if (qty < 1) qty = 1;
        $(this).val(qty);
        updateQtyByIndex(idx, qty);
    });

    $(document).on('click', '.remove-item', function(){
        const idx = parseInt($(this).data('index'));
        removeByIndex(idx);
    });

    $clearCartBtn.on('click', function(){
        if ($(this).hasClass('disabled')) return;
        if (!confirm('Are you sure you want to clear the cart?')) return;
        clearCart();
    });

    $selectAll.on('change', function(){
        // placeholder: simply toggle visual checked count
        const checked = $(this).is(':checked');
        $('.cart-count-checked').text(checked ? $('.cart-item').length : 0);
    });

    // When "Go to Checkout" clicked for guest: redirect to checkout page (server will read cookie/localStorage if integrated)
    $('#place-order-btn').on('click', function(){
        const url = $(this).data('url');
        // if cart empty, block
        const merged = mergeCartItems(readCartFromStorage());
        if (merged.length === 0){
            // alert('Cart is empty');
            return;
        }
        // proceed to checkout
        window.location.href = url;
    });

    // init only if not logged in (when logged-in, server renders and server-side actions handle updates)
    if (!isLoggedIn) {
        initClientCart();
    }
    /* No logged-in branch here on purpose. There used to be a second '.remove-product'
       handler that only removed the row from the DOM - no server call - so it raced the
       real handler in custom.js: the line disappeared even when the server rejected the
       removal, and came back on the next page load. custom.js owns removal for
       logged-in users. */

})(jQuery);
</script>



<div class="modal fade" id="modal-signin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <!-- <div class="modal-content text-center"> -->
            <!-- <div class="modal-body"> -->

            <section class="modal-content modal-body">

                <section id="login_div" class="login-container">
                    
                    
                    <!-- <h2 class="mb-3 text-start">Welcome Back</h2> -->
                    <!-- <p class="lead mb-6 text-start">Fill your email and password to sign in.</p> -->
                    
                    <form action="<?= base_url('home/login') ?>" class='form-submit-event' id="login_form" method="post">
                        
                        <input type="hidden" class="form-control" name="type" value="phone">

                        <!-- login -->
                        <div class="login rounded-1">

                            <div style="position: absolute; width: 100%; height: 100%; pointer-events: none;">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="login-left">
                                <h1 class="heading-n ta-c">Login</h1>
                                <p class="text-n ta-c op-6">Fill your email/phone and password to sign in!</p>
                                
                                <div class="field-container">
                                    
                                    <!-- <input class="input ta-c" type="text" placeholder="Phone Number / Email ID" > -->

                                    <input class="form-control input ta-c" type="text" name="identity" placeholder="Phone Number / Email ID" id="loginEmail" value="<?= (ALLOW_MODIFICATION == 0) ? '1212121212' : '' ?>">
                                    <!-- <label for="loginEmail">Enter Mobile Number / Email</label> -->

                                    <!-- <br> -->
                                    <!-- <input class="input ta-c" type="password" placeholder="Enter your Password"> -->

                                    <?php /* password-field is what theme.js passVisibility() binds the eye
                                             icon to; password-container only carries the existing login.css
                                             styling. Without the former the toggle rendered but did nothing. */ ?>
                                    <div class="password-container password-field">
                                        <input class="form-control input ta-c" type="password" name="password" placeholder="Password" id="loginPassword" value="<?= (ALLOW_MODIFICATION == 0) ? '12345678' : '' ?>">
                                        <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                        <!-- <label for="loginPassword">Password</label> -->
                                    </div>

                                    <div class="flex mt-2">
                                        <div>
                                            <input class="checkbox" type="checkbox">
                                            <label class="label text-s">Remember Me</label>
                                        </div>
                                        <div class="flex-1"></div>
                                        <!-- <a class="link text-n orange">Forgot Password?</a> -->
                                        <a href="<?= base_url() ?>" id="forgot_password_link" class="link text-s orange hover"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password?' ?> ?</a>
                                    </div>

                                    <div class="ta-c btn-container">
                                        <button type="submit" class="submit_btn cretzo btn btn-dark"><?= !empty($this->lang->line('login')) ? $this->lang->line('login') : 'Login' ?></button>
                                    </div>
                                    
                                </div>

                                
                                <div class="form-group ta-c" id="error_box"></div>
                                <!-- <div class="d-flex justify-content-center d-none">
                                    <div class="form-group" id="error_box"></div>
                                </div> -->

                                <?php if ((true || !empty($system_settings['google_login']) && $system_settings['google_login'] == 1) || (!empty($system_settings['facebook_login']) && $system_settings['facebook_login'] == 1)) { ?>
                                    <div class="login-with-container">
                                        <?php if (true || !empty($system_settings['google_login']) && ($system_settings['google_login'] == 1 || $system_settings['google_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="google">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/google-icon.jpg') ?>">
                                                    <p class="text-s">Sign in with Google</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                        <?php if (true || !empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="facebook">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/facebook-icon.jpg') ?>">
                                                    <p class="text-s">Login with Facebook</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <p class="text-n ta-c">By continuing you agree to <strong><a class="text-decoration-none text-underline c-p text-s" style="text-decoration: underline !important;" href="<?= base_url('home/terms-and-conditions') ?>">Terms of use</a></strong> and <strong><a class="text-decoration-none text-underline c-p text-s" style="text-decoration: underline !important;" href="<?= base_url('home/privacy-policy') ?>">Privacy Policy</a></strong></p>

                                <p class="text-n mb-0 ta-c mt-2">Don't have an account? <a class="text-decoration-none text-blue hover text-underline c-p fw-bold" href="#" data-bs-target="#modal-signup" data-bs-toggle="modal" data-bs-dismiss="modal" class="hover" style="color: var(--color-orange) !important;">Sign Up</a></p>
                                
                                <!-- <div class="ta-c btn-container">
                                    <button class="cretzo btn btn-dark">Login</button>
                                </div> -->

                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);"></div>
                        </div>
                        <!-- /login -->
                    </form>
                    
                </section>
                <!-- login section complete -->


                <section class="d-none" id="forgot_password_div">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center h5"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password' ?></div>
                    <hr class="mt-0 mb-5">
                    <form id="send_forgot_password_otp_form" method="POST" action="#">
                        <div class="input-group">
                            <input type="text" class="form-control" name="mobile_number" id="forgot_password_number" placeholder="Mobile number" value="">
                        </div>
                        <div class="col-12 d-flex justify-content-center pb-4 mt-3">
                            <div id="recaptcha-container-2"></div>
                        </div>
                        <footer>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-soft-dark btn-sm rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type="submit" id="forgot_password_send_otp_btn" class="submit_btn btn btn-primary btn-sm rounded-pill"><?= !empty($this->lang->line('send_otp')) ? $this->lang->line('send_otp') : 'Send OTP' ?></button>
                        </footer>
                        <br>
                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="forgot_pass_error_box"></div>
                        </div>
                    </form>
                    <form id="verify_forgot_password_otp_form" class="d-none" method="post" action="#">
                        <div class="input-group mb-3">
                            <input type="text" id="forgot_password_otp" class="form-control" name="otp" placeholder="OTP" value="" autocomplete="off" required>
                        </div>
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" name="new_password" placeholder="New Password" value="" required>
                        </div>
                        <footer>
                            <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal" aria-label="Close"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill submit_btn" id="reset_password_submit_btn"><?= !empty($this->lang->line('submit')) ? $this->lang->line('submit') : 'Submit' ?></button>
                        </footer>
                        <br>
                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="set_password_error_box"></div>
                        </div>
                    </form>
                </section>

            </section>

            <!-- </div> -->

            <!--/.modal-content -->
        <!-- </div> -->
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->


<div class="modal fade" id="modal-signup" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <!-- <div class="modal-content text-center"> -->

            <!-- <div class="modal-body"> -->
                
                
                <!-- signup container -->
                <section id="register_div" class="login-container modal-content modal-body">

                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->

                    <form id='send-otp-form' class='send-otp-form' action='#'>
                        <!-- signup 1 -->
                        <div id="signupone" class="login rounded-1">

                            <div style="position: absolute; width: 100%; height: 100%; pointer-events: none;">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="login-left pb-4">
                                <h1 class="heading-n ta-c">Sign Up</h1>
                                <p class="text-n ta-c op-6">Registration takes less than a minute.</p>
                                

                                <!-- <div class="row sign-up-verify-number">
                                    <div class="col-12 d-flex justify-content-center pb-4">
                                        <input type="text" class='form-input form-control' placeholder="Enter Mobile Number" id="phone-number" required>
                                    </div>
                                </div> -->
                        
                                <div class="field-container ta-c">
                                    <input id="phone-number" class="form-input form-control input ta-c" type="text" placeholder="Enter your Phone Number" required>
                                    <!-- <!- <br> -> -->
                                </div>

                                <div class="hide text-danger ta-c" id="error-msg"></div>

                                <div id="recaptcha-container" class="ta-c d-flex justify-content-center mb-3"></div>

                                <div id='is-user-exist-error' class='text-center text-danger ta-c'></div>
                                
                                <div class="ta-c btn-container">
                                    <button id="send-otp-button" class="cretzo btn btn-dark">Send OTP</button>
                                </div>

                                <p class="text-n mb-0 ta-c mt-3">Already have an account? <a class="text-decoration-none text-blue hover text-underline c-p fw-bold" href="#" data-bs-target="#modal-signin" data-bs-toggle="modal" data-bs-dismiss="modal" class="hover" style="color: var(--color-orange) !important;">Sign In</a></p>

                                <?php if ((true || !empty($system_settings['google_login']) && $system_settings['google_login'] == 1) || (!empty($system_settings['facebook_login']) && $system_settings['facebook_login'] == 1)) { ?>
                                    <div class="login-with-container mt-3">
                                        <?php if (true || !empty($system_settings['google_login']) && ($system_settings['google_login'] == 1 || $system_settings['google_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="google">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/google-icon.jpg') ?>">
                                                    <p class="text-s">Sign in with Google</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                        <?php if (true || !empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="facebook">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/facebook-icon.jpg') ?>">
                                                    <p class="text-s">Login with Facebook</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <div class="page-icon-container">
                                    <div class="page-icon page-icon-active"></div>
                                    <div class="page-icon"></div>
                                </div>
                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);">
                            </div>
                        </div>
                        <!-- /signup 1 -->
                    </form>
                    
                    <form id='verify-otp-form' class='verify-otp-form d-none rounded-1' action='<?= base_url('auth/register-user') ?>' method="POST">
                        <!-- signup 2 -->
                        <div id="signuptwo" class="login rounded-1">

                            <div style="position: absolute; width: 100%; height: 100%; pointer-events: none;">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="login-left pb-4">
                                <h1 class="heading-n ta-c">Sign Up</h1>
                                <p class="text-n ta-c op-6">Almost there...</p>
                                
                                <input type="hidden" class='form-input form-control' id="type" name="type" value="phone" autocomplete="off">

                                <div class="field-container">
                                    <input type="text" class='form-input form-control input ta-c' placeholder="Enter OTP" id="otp" name="otp" autocomplete="off">
                                    <!-- <br> -->
                                    <input type="text" class='form-input form-control input ta-c' placeholder="Enter Username" id="name" name="name">
                                    <!-- <br> -->
                                    <input type="email" class='form-input form-control input ta-c' placeholder="Enter your Email" id="email" name="email">
                                    <!-- <br> -->
                                    <?php /* Each password gets its own .password-field wrapper and its own
                                             toggle. theme.js passVisibility() binds per .password-field, so
                                             a single shared .password-toggle inside one .password-container
                                             would only ever bind (and toggle) the first input. */ ?>
                                    <div class="password-field">
                                        <input type="password" class='form-input form-control input ta-c' placeholder="Enter Password" id="password" name="password">
                                        <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                    </div>
                                    <div class="password-field">
                                        <input class="form-input form-control input ta-c" type="password" placeholder="Re-enter Password" id="confirm-password">
                                        <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                    </div>

                                    <!-- <div class="col-12 d-flex justify-content-center pb-4">
                                        <div id='registration-error' class='text-center p-3 text-danger'></div>
                                    </div> -->
                                    <div id='registration-error' class='text-center p-3 text-danger'></div>
                                    
                                    <div>
                                        <div>
                                            <input class="checkbox" type="checkbox">
                                            <label class="label text-n">Remember Me</label>
                                        </div>
                                        <div>
                                            <input class="checkbox" type="checkbox">
                                            <label class="label text-n">Terms & Conditions</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ta-c btn-container">
                                    <button type="submit" id='register_submit_btn' class="cretzo btn btn-dark">Register Now</button>
                                </div>
                                <div class="page-icon-container">
                                    <div class="page-icon"></div>
                                    <div class="page-icon page-icon-active"></div>
                                </div>
                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);">
                            </div>
                        </div>
                        <!-- /signup 2 -->
                    </form>

                    <form id='sign-up-form' class='sign-up-form collapse rounded-1' action='#'>
                        <input type="text" placeholder="Username" name='username' class='form-input form-control' required>
                        <input type="text" placeholder="email" name='email' class='form-input form-control' required>
                        <input type="password" placeholder="Password" name='password' class='form-input form-control' required>
                        <div id='sign-up-error' class='text-center p-3'></div>
                        <footer>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-soft-dark btn-sm rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type='submit' class="btn btn-primary btn-sm rounded-pill"><?= !empty($this->lang->line('register')) ? $this->lang->line('register') : 'Register' ?></button>
                        </footer>
                    </form>

                </section>
                <!-- /signup container -->

            <!-- </div> -->
        
            
            <!--/.modal-content -->
        <!-- </div> -->
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->

<!-- quick view -->
<div id="quick-view" data-iziModal-group="grupo3" class='product-page-content'>
    <button data-izimodal-close="" class="icon-close btn btn-circle bg-soft-primary" style="top: 9px;right: 9px;">
        <i class="fa fa-close fs-18 text-dark"></i>
    </button>
    <div class="row p-4">

        <!-- /.swiper-container -->
        <div class="col-12 col-sm-6 product-preview-image-section-md swiper-thumbs-container">
            <div class="swiper-container gallery-top overflow-hidden">
                <div class="swiper-wrapper-main swiper-wrapper"></div>
            </div>
            <div class="swiper-container gallery-thumbs overflow-hidden mt-10">
                <div class="swiper-wrapper-thumbs swiper-wrapper"></div>
            </div>
        </div>
        <!-- Mobile Product Image Slider -->
        <div class="col-12 col-sm-6 product-preview-image-section-sm">
            <div class="swiper-container mobile-image-swiper">
                <div class="mobile-swiper swiper-wrapper-mobile swiper-wrapper"></div>
                <!-- <div class="swiper-pagination mobile-image-swiper-pagination text-center"></div> -->
            </div>
        </div>

        <div class="col-12 col-sm-6 product-page-details">
            <h3 class="my-3 product-title" id="modal-product-title"></h3>
            <div id="modal-product-sellers"></div>
            <div id="modal-product-statistics"></div>
            <div id="modal-product-brand" class="d-flex gap-1"></div>
            <p id="modal-product-short-description"></p>
            <hr class="mb-2 mt-2">

            <input type="text" id="modal-product-rating" class="d-none" data-size="xs" value="0" data-show-clear="false" data-show-caption="false" readonly>
            (<span class="rating-status" id="modal-product-no-of-ratings">1203</span> <?= !empty($this->lang->line('reviews')) ? $this->lang->line('reviews') : 'reviews' ?> )
            <!-- </div> -->
            <p class="mb-0 price">
                <span id="modal-product-price"></span>
                <sup>
                    <span class="striped-price text-danger" id="modal-product-special-price-div">
                        <s id="modal-product-special-price"></s>
                    </span>
                </sup>
            </p>
            <div id="modal-product-variant-attributes"></div>
            <div id="modal-product-variants-div"></div>
            <div class="num-block skin-2 py-2 pt-4 pb-4 mt-2">
                <div class="num-in form-control d-flex align-items-center">
                    <span class="minus dis"></span>
                    <input type="text" class="in-num" id="modal-product-quantity">
                    <span class="plus"></span>
                </div>
            </div>
            <div class="d-flex mb-3 mt-2 text-center text-md-left gap-2">
                <!-- <div> -->
                <button class="m-0 add_to_cart mt-1 btn btn-sm btn-yellow rounded-pill w-100" id="modal-add-to-cart-button">&nbsp;<i class="uil uil-shopping-bag fs-16"></i> <?= !empty($this->lang->line('add_to_cart')) ? $this->lang->line('add_to_cart') : 'Add To Cart' ?></button>
                <!-- </div>
                <div> -->
                <?php // .compare-soon-btn, NOT .compare: this quick-view button was the last one
                      // still firing the real compare AJAX, which added the product to a compare
                      // list whose page is now hidden. Shows the same "coming soon" dialog the
                      // product page and the main quick-view already show. ?>
                <button type="button" name="compare" class="btn btn-sm btn-outline-blue rounded-pill h-9 m-0 mt-1 compare-soon-btn" id="compare" aria-label="Compare product" title="Compare"><i class="uil uil-exchange-alt fs-20"></i></button>
                <!-- </div>
                <div> -->
                <button class="btn btn-sm btn-outline-red rounded-pill h-9 m-0 add-fav mt-1" id="add_to_favorite_btn"><i class="fa fa-heart fs-20"></i></button>
                <!-- </div> -->
            </div>

            <div class="mt-2">
                <span>
                    <div id="modal-product-tags"></div>
                </span>
            </div>
        </div>
    </div>
</div>


<?php if (ALLOW_MODIFICATION == 0) { ?>
    <div class="buy-now-btn">
        <a href="https://codecanyon.net/item/eshop-web-multi-vendor-ecommerce-marketplace-cms/34380052" target="_blank" class="btn btn-danger btn-sm rounded-pill"> <i class="fa fa-shopping-cart"></i>&nbsp; <?= !empty($this->lang->line('buy_now')) ? $this->lang->line('buy_now') : 'Buy Now' ?></a>
    </div>
<?php } ?>

<div class="fixed-icon">
    <?php /* This page does not include footer.php, so it carries its own copy of the widget.
             It used to be gated on logged_in(), meaning a guest with items in the cart - exactly
             the person most likely to have a question - had no chat at all here, unlike every
             other page. The assistant answers guests too, so the gate is gone and the markup
             now matches the footer's. */ ?>
    <button type="button" id="chat-button" aria-label="Open support chat" aria-expanded="false" aria-controls="chat-iframe">
        <img src="<?= base_url('assets/front_end/cretzo/img/chat-fab-icon.png') ?>" alt="" id="chat-button-icon">
        <i class="uil uil-times" id="chat-button-close-icon" aria-hidden="true"></i>
    </button>
    <!-- Floating chat iframe -->
    <iframe src="<?= base_url('my-account/floating_chat_modern') ?>" id="chat-iframe" title="Support chat" loading="lazy"></iframe>
    <div class="progress-wrap mt-2">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>
</div>
<!-- end -->
<!-- main content ends -->
<script>
$(document).ready(function () {

    $(".search_field").on("keyup", function () {
        let search = $(this).val().trim();

        if (search.length < 1) {
            $("#append_desktop_search").html("");
            return;
        }

        $.ajax({
            url: "/cretzo/search/search_data",
            type: "GET",
            data: { search: search },
            dataType: "json",
            success: function (response) {

                let html = "";

                // Ensure response is parsed JSON
                if (typeof response === "string") {
                    response = JSON.parse(response);
                }

                if (response.data && response.data.length > 0) {
                    html += '<div class="list-group">';

                    $.each(response.data, function (index, item) {

                        // Proper escaping
                        let safeName = item.name.replace(/"/g, '&quot;');

                        html += `
                            <div class="search-item" 
                                 onclick="selectSuggestion(&quot;${safeName}&quot;)">
                                ${item.name}
                            </div>
                        `;
                    });

                    html += '</div>';

                } else {
                    html = '<div class="search-item">No results found</div>';
                }

                $("#append_desktop_search").html(html);
            },
            error: function () {
                $("#append_desktop_search").html(
                    '<div class="search-item">Error fetching data</div>'
                );
            }
        });

    });

});

// Fill value into search box
function selectSuggestion(name) {
    $(".search_field").val(name);
    $("#append_desktop_search").html("");
}

// Hide suggestions when clicking outside
$(document).click(function (e) {
    if (!$(e.target).closest('.search-container-m').length) {
        $("#append_desktop_search").html("");
    }
});
</script>
