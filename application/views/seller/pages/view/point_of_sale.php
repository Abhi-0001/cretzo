<div class="content-wrapper pos-page">
    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-0"><i class="fas fa-cash-register mr-2 text-primary-theme"></i>Point of Sale</h4>
                    <p class="text-muted mb-0 small">Ring up an in-person or phone sale for your own products.</p>
                </div>
            </div>

            <nav class="navbar navbar-expand-sm mt-2 pos-filter-bar flex-column flex-md-row">
                <ul class="navbar-nav flex-row d-md-flex align-items-md-center">
                    <li class="nav-item mr-3">
                        <span class="pos-filter-label">All Products</span>
                    </li>
                    <li id="get_categories" class="nav-item dropdown mr-3">
                        <select class="form-control" id="product_categories" name="category_parent">
                            <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'Select Categories' ?>
                            </option>
                            <?php
                            echo get_categories_option_html($categories);
                            ?>
                        </select>
                    </li>
                    <li class="nav-item">
                        <input type="search" name="search_products" class="form-control" id="search_products" value="" placeholder="Search Products">
                    </li>
                </ul>
            </nav>

            <div class="mt-3">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card attribute-card">
                            <input type="hidden" name="session_user_id" id="session_user_id" value="<?= $_SESSION['user_id'] ?>" />
                            <input type="hidden" name="limit" id="limit" value="15" />
                            <input type="hidden" name="offset" id="offset" value="0" />
                            <input type="hidden" name="total" id="total_products" />
                            <input type="hidden" name="current_page" id="current_page" value="0" />
                            <div class="pos-product-grid" id="get_products">
                                <!-- product display in this container -->
                            </div>
                            <div class="pagination-container"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form id="pos_form" method="post" action='<?= base_url('seller/point_of_sale/place_order') ?>'>
                            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                            <input type="hidden" name="product_variant_id" value="">
                            <input type="hidden" name="quantity" value="">
                            <input type="hidden" name="total" value="">

                            <div class="card attribute-card mb-3">
                                <div class="card-header attribute-card-header">
                                    <span class="header-icon bg-set"><i class="fas fa-user"></i></span>
                                    <h5 class="mb-0">Customer Details</h5>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="form-group">
                                        <label class="pos-field-label" for="customer_name">Customer Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Enter customer name">
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="pos-field-label" for="customer_mobile">Mobile Number</label>
                                        <input type="text" class="form-control" id="customer_mobile" name="customer_mobile" placeholder="Enter mobile number" maxlength="15" inputmode="numeric">
                                    </div>
                                    <small class="text-muted">Used only to print this bill - not saved as a website account.</small>
                                </div>
                            </div>

                            <div class="card attribute-card mb-3">
                                <div class="card-header attribute-card-header">
                                    <span class="header-icon bg-set"><i class="fas fa-shopping-cart"></i></span>
                                    <h5 class="mb-0">Cart</h5>
                                </div>
                                <div class="pos-cart-list cart-items">
                                </div>
                                <div class="card-body pos-cart-summary">
                                    <?php $settings = get_settings('system_settings', true); ?>
                                    <div class="pos-summary-row">
                                        <span><?= labels('subtotal', 'Subtotal') ?></span>
                                        <span class="cart-total-price" id="cart-total-price" data-currency="<?= (isset($settings['currency']) && !empty($settings['currency'])) ?   $settings['currency'] : '';   ?>"></span>
                                    </div>

                                    <div class="form-group mt-3 mb-2">
                                        <label for="delivery_charge_service" class="pos-field-label"><?= labels('shipping_charge', 'Shipping charge') ?></label>
                                        <input type="number" class="delivery_charge_service form-control" id="delivery_charge_service" value="" placeholder="0.00" name="delivery_charge" min="0.00">
                                    </div>

                                    <div class="form-group mb-0">
                                        <label for="discount_service" class="pos-field-label"><?= labels('discount', 'Discount') ?> <small class="text-muted">(<?= labels('if_any', 'if any') ?>)</small></label>
                                        <input type="number" class="discount_service form-control" id="discount_service" value="" placeholder="0.00" name="discount" min="0.00">
                                    </div>

                                    <hr>
                                    <div class="pos-summary-row pos-summary-total">
                                        <span><?= labels('total', 'Total') ?></span>
                                        <span class="final_total" id="final_total" data-currency="<?= (isset($settings['currency']) && !empty($settings['currency'])) ?   $settings['currency'] : '';   ?>"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card attribute-card mb-3">
                                <div class="card-header attribute-card-header">
                                    <span class="header-icon bg-set"><i class="fas fa-credit-card"></i></span>
                                    <h5 class="mb-0">Payment Method</h5>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="pos-payment-options">
                                        <label class="pos-payment-chip cash_payment" for="cod">
                                            <input id="cod" type="radio" name="payment_method[]" value="COD" class="payment_method" />
                                            <i class="fas fa-money-bill-wave"></i> Cash
                                        </label>
                                        <label class="pos-payment-chip card_payment" for="card_payment">
                                            <input id="card_payment" type="radio" name="payment_method[]" value="card_payment" class="payment_method">
                                            <i class="fas fa-credit-card"></i> Card Payment
                                        </label>
                                        <label class="pos-payment-chip bar_code" for="bar_code">
                                            <input id="bar_code" type="radio" name="payment_method[]" value="bar_code" class="payment_method">
                                            <i class="fas fa-qrcode"></i> Bar / QR Code
                                        </label>
                                        <label class="pos-payment-chip net_banking" for="net_banking">
                                            <input id="net_banking" type="radio" name="payment_method[]" value="net_banking" class="payment_method">
                                            <i class="fas fa-university"></i> Net Banking
                                        </label>
                                        <label class="pos-payment-chip online_payment" for="online_payment">
                                            <input id="online_payment" type="radio" name="payment_method[]" value="online_payment" class="payment_method">
                                            <i class="fas fa-mobile-alt"></i> Online Payment
                                        </label>
                                        <label class="pos-payment-chip other" for="other">
                                            <input id="other" type="radio" name="payment_method[]" value="other" class="payment_method">
                                            <i class="fas fa-ellipsis-h"></i> Other
                                        </label>
                                    </div>

                                    <div class="payment_method_name mt-3">
                                        <label class="pos-field-label">Payment method name</label>
                                        <input type="text" class="form-control" name="payment_method_name" id="payment_method_name">
                                    </div>
                                    <div class="transaction_id mt-3">
                                        <label class="pos-field-label">Transaction ID</label>
                                        <input type="text" class="form-control" name="transaction_id" id="transaction_id">
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button class="btn btn-sm btn-clear_cart btn-outline-danger mb-2 mx-2" type="button" id="clear_cart_btn">Clear Cart</button>
                                <button class="btn btn-sm btn-purchase btn-primary-theme mb-2" type="submit" id="place_order_btn">Place Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .pos-page .text-primary-theme { color: var(--color-orange); }

    .pos-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }

    .pos-page .pos-filter-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: .75rem 1rem;
    }
    .pos-page .pos-filter-label {
        font-weight: 600;
        color: #2b2f33;
    }

    .pos-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .pos-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .pos-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .pos-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .pos-page .header-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 13px;
        flex: none;
    }
    .pos-page .header-icon.bg-set { background: var(--color-orange); }

    .pos-page .pos-field-label {
        font-weight: 600;
        font-size: 12.5px;
        color: var(--color-grey);
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-bottom: 6px;
        display: block;
    }

    /* --- product grid --- */
    .pos-page .pos-product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 16px;
        padding: 1rem;
    }
    .pos-page .shop-item {
        border: 1px solid rgba(0,0,0,0.07);
        border-radius: 12px;
        padding: 1rem;
        background: #fff;
        display: flex;
        flex-direction: column;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .pos-page .shop-item:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .pos-page .shop-item-image {
        width: 100%;
        height: 140px;
        border-radius: 8px;
        overflow: hidden;
        background: #fafafa;
        margin-bottom: .75rem;
    }
    .pos-page .shop-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .pos-page .shop-item-title {
        display: block;
        font-weight: 600;
        font-size: 14px;
        color: #2b2f33;
        margin-bottom: .5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .pos-page .shop-item-title:hover { color: var(--color-orange); }
    .pos-page .shop-item-body { flex: 1 1 auto; }

    .pos-page .pos-variant-select {
        width: 100%;
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--color-orange-dark);
        background-color: #fff9f4;
    }
    .pos-page .pos-variant-select:focus {
        outline: none;
        border-color: var(--color-orange);
    }
    /* single-variant products have nothing to pick - a plain price tag, not a dropdown */
    .pos-page .pos-price-tag {
        font-size: 16px;
        font-weight: 700;
        color: var(--color-orange-dark);
    }

    .pos-page .pos-add-cart-btn {
        margin-top: .75rem;
        width: 100%;
        min-height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: var(--color-orange);
        border: 1px solid var(--color-orange);
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
        padding: .5rem;
        font-size: 13px;
    }
    .pos-page .pos-add-cart-btn:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    /* --- cart list --- */
    .pos-page .pos-cart-list {
        max-height: 320px;
        overflow-y: auto;
        padding: 0 1rem;
    }
    .pos-page .pos-cart-empty {
        text-align: center;
        color: var(--color-orange);
        padding: 1.5rem 0;
        font-weight: 600;
    }
    .pos-page .pos-cart-item {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .6rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .pos-page .pos-cart-item:last-child { border-bottom: none; }
    .pos-page .pos-cart-item-image {
        width: 44px;
        height: 44px;
        border-radius: 6px;
        object-fit: cover;
        flex: none;
        background: #fafafa;
    }
    .pos-page .pos-cart-item-info { flex: 1 1 auto; min-width: 0; }
    .pos-page .pos-cart-item-title {
        font-size: 13px;
        font-weight: 600;
        color: #2b2f33;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .pos-page .pos-cart-item-price {
        font-size: 12.5px;
        color: var(--color-orange-dark);
        font-weight: 600;
    }
    .pos-page .pos-cart-item-qty {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: none;
    }
    .pos-page .pos-cart-item-qty .cart-quantity-input {
        width: 20px;
        height: 30px;
        padding: 0;
        line-height: 1;
        border-radius: 3px;
    }
    .pos-page .pos-cart-item-qty .cart-quantity-input-new {
        width: 30px;
        height: 30px;
        padding: 2px;
        font-size: 12px;
    }
    .pos-page .pos-cart-item-remove {
        flex: none;
        width: 26px;
        height: 26px;
        padding: 0;
        border-radius: 6px;
    }

    .pos-page .pos-cart-summary { border-top: 1px solid rgba(0,0,0,0.06); }
    .pos-page .pos-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        color: #2b2f33;
    }
    .pos-page .pos-summary-total {
        font-size: 16px;
        font-weight: 700;
        color: var(--color-orange-dark);
    }

    /* --- payment methods --- */
    .pos-page .pos-payment-options {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .pos-page .pos-payment-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--color-grey);
        cursor: pointer;
        margin-bottom: 0;
        transition: all .15s ease;
    }
    .pos-page .pos-payment-chip input { margin-right: 2px; }
    .pos-page .pos-payment-chip:hover {
        border-color: var(--color-orange);
        color: var(--color-orange-dark);
    }
    .pos-page .pos-payment-chip:has(input:checked),
    .pos-page .pos-payment-chip.checked {
        background: var(--color-orange-light);
        border-color: var(--color-orange);
        color: var(--color-orange-dark);
    }
</style>
