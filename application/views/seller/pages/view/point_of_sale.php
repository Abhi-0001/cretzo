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
                            <div class="row d-flex justify-content-center p-2 align-content-center" id="get_products">
                                <!-- product display in this container -->
                            </div>
                            <div class="pagination-container"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card attribute-card">
                            <section class="container content-section p-3">
                                <form id="pos_form" method="post" action='<?= base_url('seller/point_of_sale/place_order') ?>'>
                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                        <span>Already Registered?</span>
                                        <input type="button" class="btn btn-xs btn-light border" id="clear_user_search" value="Clear">
                                    </div>
                                    <!-- select user -->
                                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                                    <input type="hidden" name="user_id" id="pos_user_id" value="">
                                    <input type="hidden" name="product_variant_id" value="">
                                    <input type="hidden" name="quantity" value="">
                                    <input type="hidden" name="total" value="">
                                    <div class="mt-2 text-center">
                                        <select class="select_user form-control" id="select_user_id">
                                            <!-- user   name display here  -->
                                        </select>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <span class="small">Don't have an account?</span>
                                        <div><a href="javascript:void(0)" class="btn btn-sm btn-primary-theme btn-rounded mt-2" data-toggle="modal" data-target="#register">Register</a></div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="h5 mt-3 text-center">Cart</p>
                                    </div>
                                    <div class="row cart-row mt-3 mb-2 pos-cart-header">
                                        <div class="col-md-4">
                                            <p class="cart-item h6">Item</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="cart-item h6">Price</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="cart-item h6">Quantity</p>
                                        </div>
                                        <div class="col-md-2">
                                            <p class="cart-item h6"><i class="fas fa-edit"></i></p>
                                        </div>
                                    </div>
                                    <div class="cart-items">
                                    </div>
                                    <div class="d-flex justify-content-end mb-2 mt-3">
                                        <div class="col-lg-6 text-right">
                                            <div class="invoice-detail-item">
                                                <p class="cart-total"><?= labels('subtotal', 'Subtotal') ?></p>
                                                <?php $settings = get_settings('system_settings', true); ?>
                                                <p class="cart-total-price h6 m-1 px-2" id="cart-total-price" data-currency="<?= (isset($settings['currency']) && !empty($settings['currency'])) ?   $settings['currency'] : '';   ?>"></p>
                                            </div>
                                            <label for="delivery_charge_service"><?= labels('shipping_charge', 'Shipping charge') ?></label>
                                            <input type="number" class="delivery_charge_service form-control" id="delivery_charge_service" value="" placeholder="0.00" name="delivery_charge" min="0.00">

                                            <label for="discount_service" class="mt-2"><?= labels('discount', 'Discount') ?></label> <small>(<?= labels('if_any', 'if any') ?>)</small>
                                            <input type="number" class="discount_service form-control" id="discount_service" value="" placeholder="0.00" name="discount" min="0.00">

                                            <hr class="mt-2 mb-2">
                                            <div class="invoice-detail-item">
                                                <div class="cart-total"><?= labels('total', 'Total') ?></div>
                                                <p class="final_total h6 m-1 px-2" id="final_total" data-currency="<?= $currency ?>"></p>
                                            </div>
                                            <hr>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="h6 text-primary-theme">Payment Methods</p>
                                        <div class="mt-3 cash_payment">
                                            <label for="cod">
                                                <input id="cod" type="radio" name="payment_method[]" value="COD" class="payment_method" />
                                                Cash
                                            </label>
                                        </div>
                                        <div class="card_payment">
                                            <label for="card_payment">
                                                <input id="card_payment" type="radio" name="payment_method[]" value="card_payment" class="payment_method">
                                                Card Payment
                                            </label>
                                        </div>
                                        <div class="bar_code">
                                            <label for="bar_code">
                                                <input id="bar_code" type="radio" name="payment_method[]" value="bar_code" class="payment_method">
                                                Bar Code / QR Code Scan
                                            </label>
                                        </div>
                                        <div class="net_banking">
                                            <label for="net_banking">
                                                <input id="net_banking" type="radio" name="payment_method[]" value="net_banking" class="payment_method">
                                                Net Banking
                                            </label>
                                        </div>
                                        <div class="online_payment">
                                            <label for="online_payment">
                                                <input id="online_payment" type="radio" name="payment_method[]" value="online_payment" class="payment_method">
                                                Online Payment
                                            </label>
                                        </div>
                                        <div class="other">
                                            <label for="other">
                                                <input id="other" type="radio" name="payment_method[]" value="other" class="payment_method">
                                                Other
                                            </label>
                                        </div>
                                        <div class="payment_method_name mt-3">
                                            <p>Enter Payment method Name <input type="text" class="form-control" name="payment_method_name" id="payment_method_name"></p>
                                        </div>
                                        <div class="transaction_id mt-3">
                                            <p>Enter Transaction ID <input type="text" class="form-control" name="transaction_id" id="transaction_id"></p>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button class="btn btn-sm btn-clear_cart btn-outline-danger mb-2 mx-2" type="button" id="clear_cart_btn">Clear Cart</button>
                                        <button class="btn btn-sm btn-purchase btn-primary-theme mb-2" type="submit" id="place_order_btn">Place Order</button>
                                    </div>
                                </form>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<div class="modal fade" id="register">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Register User</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Modal body -->
            <div class="modal-body">
                <form method="post" action='<?= base_url('seller/point_of_sale/register_user') ?>' id="register_form">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                        <input type="text" class="form-control" id="name" placeholder="Enter Your Name" name="name">
                    </div>
                    <div class="form-group">
                        <label for="mobile">Mobile:</label>
                        <input type="text" class="form-control" id="mobile" placeholder="Enter Your Mobile Number" name="mobile">
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="text" class="form-control" id="password" value="" placeholder="Enter Password" name="password">
                    </div>
                    <button type="submit" class="btn btn-primary-theme" id="save-register-result-btn" name="register" value="Save">Register</button>
                    <div class="mt-3">
                        <div id="save-register-result"></div>
                    </div>
                </form>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
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

    .pos-page .pos-cart-header {
        border-bottom: 2px solid var(--color-secondary);
    }

    .pos-page .shop-item {
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: .75rem;
        transition: box-shadow .15s ease;
    }
    .pos-page .shop-item:hover {
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
</style>
