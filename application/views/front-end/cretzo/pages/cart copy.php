<?php 
// Safe defaults
$cart_count = isset($cart[0]['cart_count']) && !empty($cart[0]['cart_count']) ? $cart[0]['cart_count'] : 0;
$is_logged_in = isset($is_logged_in) ? (int)$is_logged_in : 0;
$logo = get_settings('web_logo');
?>
<input type="hidden" id="input_cart_count" value="<?= $cart_count ?>">
<input type="hidden" name="promo_set" id="promo_set" value="" />
<input type="hidden" name="promo_code_amount" id="promo_code_amount" value="0" />

<section class="cart-container">
    <div class="cart-indicator-container">
        <div class="company-logo">
            <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" class="main-logo" alt="site-logo image"></a>
        </div>

        <div class="cart-indicator cart-indicator-active rounded-end"><p style="font-size: 18px;">Cart</p></div>
        <div class="completion-line active"></div>

        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart/checkout') ?>"><p style="font-size: 18px;">Address</p></a>
        </div>
        <div class="completion-line"></div>

        <div class="cart-indicator">
            <a class="text-decoration-none" href="<?= base_url('cart/checkout') ?>"><p style="font-size: 18px;">Payment</p></a>
        </div>

        <div class="quality-assured-container">
            <img class="quality-assured-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/orange-tick.png') ?>">
            <span class="text-s quality-assured-text">100% Quality Assured</span>
        </div>
    </div>

    <div class="cart">
        <div class="cart-left">
            <a href="<?= base_url('products') ?>" class="cretzo small-btn text-decoration-none px-0 text-s btn-nav-prev">
                <i class="fa fa-arrow-left mr-1"></i>Find more products
            </a>

            <div class="cart-left-one mt-2"><p class="text-n">Check delivery time & services</p></div>

            <div class="cart-left-two">
                <p class="text-n">Available Offers</p>
                <p class="text-s">20% instant discount on Kotak Credit Card EMI transaction on min spend of ₹3,500.</p>
                <p class="text-s c-p show-more-text">Show More <img class="show-more-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/orange-arrow.png') ?>"></p>
            </div>

            <div class="cart-left-three">
                <?php $cart_empty = $cart_count <= 0; ?>
                <div>
                    <input type="checkbox" id="select-all-checkbox" <?= $cart_empty ? 'disabled' : '' ?> >
                    <label class="text-n"><span class="cart-count-checked">0</span>/<span class="cart-count"><?= $cart_count ?></span> Items selected</label>
                </div>
                <div>
                    <a href="javascript:void(0)" id="clear_cart" class="selected-items-action-btn disabled"><span class="text-s cart-left-three-btn">Remove</span></a>
                    <span class="text-n spacer">|</span>
                    <a href="javascript:void(0)" id="wishlist-all" class="selected-items-action-btn disabled"><span class="text-s cart-left-three-btn">Add to wishlist</span></a>
                </div>
            </div>

            <!-- container for JS-rendered or server-rendered items -->
            <ul class="cart-items-container">
                <?php
                // Server-side render as fallback for logged-in users (your original loop, cleaned)
                if ($is_logged_in) {
                    foreach ($cart as $key => $row) {
                        if (!isset($row['qty']) || $row['qty'] == 0) continue;
                        $price = (isset($row['special_price']) && $row['special_price'] > 0) ? $row['special_price'] : $row['price'];
                        $img = $row['product_variants'][0]['variant_image'] ?? $row['image'];
                        $original_price = $row['price'];
                        ?>
                        <li class="cart-item cart-product" data-product-id="<?= $row['product_id'] ?>" data-id="<?= $row['id'] ?>">
                            <div class="cart-item-img-container">
                                <a href="<?= base_url('products/details/' . $row['slug']) ?>"><img class="cart-item-img" src="<?= $img ?>" alt="<?= $row['name'] ?>"></a>
                            </div>

                            <div class="cart-item-detail-container">
                                <h1 class="text-n">
                                    <a href="<?= base_url('products/details/' . $row['slug']) ?>"><?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])); ?></a>
                                </h1>

                                <?php if (!empty($row['product_variants'])): 
                                    $attr_names = explode(',', $row['product_variants'][0]['attr_name'] ?? '');
                                    $attr_values = explode(',', $row['product_variants'][0]['variant_values'] ?? '');
                                ?>
                                    <p class="text-s product-variant-detail px-1">
                                    <?php for($i=0;$i<count($attr_names);$i++): ?>
                                        <span class="mr-1"><?= trim($attr_names[$i]) ?>: <?= trim($attr_values[$i] ?? '') ?></span>
                                    <?php endfor; ?>
                                    </p>
                                <?php endif; ?>

                                <div class="num-block skin-2 product-quantity">
                                    <span class="minus dis" data-id="<?= $row['id'] ?>">-</span>
                                    <input type="text" class="in-num itemQty" data-id="<?= $row['id'] ?>" value="<?= $row['qty'] ?>" data-price="<?= $price ?>" data-original-price="<?= $original_price ?>">
                                    <span class="plus" data-id="<?= $row['id'] ?>">+</span>
                                </div>

                                <p class="text-b total-price">
                                    <?php if ($price != $original_price): ?>
                                        <span class="discounted-price product-line-price"><?= $settings['currency'] . '' . number_format($price * $row['qty'], 0) ?></span>
                                        <span class="actual-price product-line-price"><?= $settings['currency'] . '' . number_format($original_price * $row['qty'], 0) ?></span>
                                        <span class="discount"><?= number_format((($original_price - $price)/$original_price)*100, 0); ?>% OFF</span>
                                    <?php else: ?>
                                        <span class="actual-price product-line-price"><?= $settings['currency'] . '' . number_format($price * $row['qty'], 0) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <a class="product-removal link_cursor"><i class="remove-product" data-id="<?= $row['id'] ?>"><img class="cross-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/cross-icon1.png') ?>"></i></a>
                        </li>
                    <?php
                    } // foreach
                } // if logged in
                ?>
            </ul>
        </div>

        <div class="cart-right">
            <div class="cart-right-one">
                <h1 class="text-b">COUPONS</h1>
                <div>
                    <p class="flex-1" style="font-size: 18px;"><img class="coupon-tag-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/tag-icon.png') ?>">Apply Coupons</p>
                    <button class="cretzo btn btn-light text-n apply-btn" data-bs-toggle="modal" data-bs-target="#promo-code-modal">APPLY</button>
                </div>
                <p class="text-s">Show Your Support For Our Artisans By Purchasing Their Handcrafted Artworks.</p>
            </div>

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
                            ?>
                            <li class="delivery-list-item" id="delivery-p-<?= $row['id'] ?>">
                                <div><img class="delivery-list-item-img" src="<?= $img ?>"></div>
                                <div><p class="text-n">Estimated delivery: <span class="fw-b"><?= $start ?> - <?= $end ?></span></p></div>
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
                    <tr class="bill-row bill-row-last"><td class="text-n bill-column fw-b">Total Amount</td><td class="text-n bill-column fw-b" id="final_total">₹<?= moneyFormatIndia(round($total)) ?></td></tr>
                </table>

                <div class="input-group my-4 mt-0">
                    <input type="text" class="form-control promocode_input" placeholder="Promo code">
                    <div class="input-group-append">
                        <button class="cretzo btn btn-dark btn-primary rounded-end py-1 redeem_btn"><?= !empty($this->lang->line('redeem')) ? $this->lang->line('redeem') : 'Redeem' ?></button>
                        <button class="cretzo btn btn-dark btn-danger d-none py-1 clear_promo_btn"><?= !empty($this->lang->line('clear')) ? $this->lang->line('clear') : 'Clear' ?></button>
                    </div>
                </div>

                <button class="cretzo btn btn-dark w-100" id="place-order-btn" data-url="<?= base_url('cart/checkout') ?>">Go To Checkout</button>
            </div>
        </div>
    </div>
</section>

<!-- Promo modal -->
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
        </div>
    </div>
</div>

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
            <li class="cart-item" data-index="${index}" data-product-id="${item.product_id || ''}" data-variant-id="${item.variant_id || ''}" data-id="${item.id || ''}">
                <div class="cart-item-img-container">
                    <img class="cart-item-img" src="${item.image || ''}" alt="${(item.name || '').replace(/"/g,'&quot;')}">
                </div>
                <div class="cart-item-detail-container">
                    <h1 class="text-n">${item.name || ''}</h1>
                    <p class="text-s variant-name">${item.variant_name || ''}</p>

                    <div class="cart-item-detail-dropdown-container">
                        <div class="quantity-selector">
                            <button class="quantity-btn minus" data-index="${index}">-</button>
                            <input type="number" class="quantity-input" min="1" value="${qty}" data-index="${index}">
                            <button class="quantity-btn plus" data-index="${index}">+</button>
                        </div>
                    </div>

                    <p class="text-b price-row">
                        <span class="actual-price">₹${price}</span>
                        ${mrp ? `<span class="discounted-price">₹${mrp}</span>` : ''}
                        ${mrp ? `<span class="discount">${Math.round(((mrp - price) / mrp) * 100)}% OFF</span>` : ''}
                    </p>

                    <p class="text-b item-total">Total: ₹${itemTotal}</p>
                    <button class="remove-item btn btn-link text-danger" data-index="${index}">Remove</button>
                </div>
            </li>
        `;
    }

    function renderCartUi(cart){
        // merge duplicates for presentation
        const merged = mergeCartItems(cart);
        // render items
        if (merged.length === 0){
            $itemsContainer.html('<div class="text-center py-5"><h4>Your cart is empty</h4><p>Add some items to get started</p><a href="<?= base_url('products') ?>" class="btn btn-primary">Continue Shopping</a></div>');
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
            alert('Cart is empty');
            return;
        }
        // proceed to checkout
        window.location.href = url;
    });

    // init only if not logged in (when logged-in, server renders and server-side actions handle updates)
    if (!isLoggedIn) {
        initClientCart();
    } else {
        // still wire client removal/qty for server-side rendered items (we call server endpoints in real app - but here we just wire UI)
        $(document).on('click', '.remove-product', function(){
            // ideally make an AJAX call to remove on server; fallback: remove element visually
            const id = $(this).data('id');
            $(this).closest('.cart-item').remove();
        });
    }

})(jQuery);
</script>
