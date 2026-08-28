<?php
/**
 * Language labels editor.
 *
 * The label set used to be one flat wall of ~170 inputs (with a handful of
 * duplicated keys). It is now driven by this single map so the markup lives in
 * one place: group => [title, icon, labels => [post_key => [caption, default]]].
 */
$label_groups = [
    'navigation' => [
        'title' => 'Navigation & Menu',
        'icon'  => 'fa-compass',
        'labels' => [
            'menu' => ['Menu', 'Menu'],
            'home' => ['Home', 'Home'],
            'pages' => ['Pages', 'Pages'],
            'products' => ['Products', 'Products'],
            'product_listing' => ['Product Listing', 'Product Listing'],
            'category' => ['Category', 'Category'],
            'amazing_categories' => ['Amazing Categories', 'Amazing Categories'],
            'sellers' => ['Sellers', 'Sellers'],
            'blogs' => ['Blogs', 'Blogs'],
            'become_a_seller' => ['Become a Seller', 'Become a Seller'],
            'about_us' => ['About Us', 'About Us'],
            'contact_us' => ['Contact Us', 'Contact Us'],
            'faq' => ['FAQs', 'FAQs'],
            'mobile_app' => ['Mobile App', 'Mobile App'],
            'language' => ['Language', 'Language'],
            'view_more' => ['View More', 'View More'],
            'see_all' => ['See All', 'See All'],
            'go_to_shop' => ['Go to Shop', 'Go to Shop'],
            'back_to_top' => ['Back to Top', 'Back to top'],
        ],
    ],
    'account' => [
        'title' => 'Account & Authentication',
        'icon'  => 'fa-user-circle',
        'labels' => [
            'login' => ['Login', 'Login'],
            'register' => ['Register', 'Register'],
            'logout' => ['Logout', 'Logout'],
            'my_account' => ['My Account', 'My Account'],
            'dashboard' => ['Dashboard', 'Dashboard'],
            'profile' => ['Profile', 'Profile'],
            'update_profile' => ['Update Profile', 'Update Profile'],
            'username' => ['Username', 'Username'],
            'notification' => ['Notification', 'Notification'],
            'old_password' => ['Old Password', 'Old Password'],
            'new_password' => ['New Password', 'New Password'],
            'confirm_new_password' => ['Confirm New Password', 'Confirm New Password'],
            'forgot_password' => ['Forgot Password', 'Forgot Password'],
            'send_otp' => ['Send OTP', 'Send OTP'],
            'enter_valid_number' => ['Enter Valid Number', 'Enter Valid Number'],
        ],
    ],
    'catalog' => [
        'title' => 'Product & Catalogue',
        'icon'  => 'fa-box-open',
        'labels' => [
            'product' => ['Product', 'Product'],
            'image' => ['Image', 'Image'],
            'price' => ['Price', 'Price'],
            'details' => ['Details', 'Details'],
            'view' => ['View', 'View'],
            'view_details' => ['View Details', 'View Details'],
            'specification' => ['Specifications', 'Specifications'],
            'related_products' => ['Related Products', 'Related Products'],
            'reviews' => ['Reviews', 'Reviews'],
            'rating' => ['Rating', 'Rating'],
            'sale' => ['Sale', 'Sale'],
            'compare' => ['Compare', 'Compare'],
            'pick_your_favorite_color' => ['Pick Your Favorite Color', 'Pick Your Favorite Color'],
            'favorite' => ['Favorite', 'Favorite'],
            'add_to_favorite' => ['Add to Favorite', 'Add to Favorite'],
            'remove_from_favorite' => ['Remove from Favorite', 'Remove from Favorite'],
            'no_favorite_product_message' => ['No Favorites - Empty Message', 'No Favorite Products Found'],
        ],
    ],
    'filters' => [
        'title' => 'Filters & Sorting',
        'icon'  => 'fa-sliders-h',
        'labels' => [
            'filter' => ['Filter', 'Filter'],
            'sort_by' => ['Sort By', 'Sort By'],
            'show' => ['Show', 'Show'],
            'relevance' => ['Relevance', 'Relevance'],
            'top_rated' => ['Top Rated', 'Top Rated'],
            'newest_first' => ['Newest First', 'Newest First'],
            'oldest_first' => ['Oldest First', 'Oldest First'],
            'price_low_to_high' => ['Price - Low To High', 'Price - Low To High'],
            'price_high_to_low' => ['Price - High To Low', 'Price - High To Low'],
        ],
    ],
    'cart' => [
        'title' => 'Cart',
        'icon'  => 'fa-shopping-cart',
        'labels' => [
            'cart' => ['Cart', 'Cart'],
            'your_cart' => ['Your Cart', 'Your cart'],
            'shopping_cart' => ['Shopping Cart', 'Shopping Cart'],
            'view_cart' => ['View Cart', 'View Cart'],
            'add_to_cart' => ['Add to Cart', 'Add to Cart'],
            'buy_now' => ['Buy Now', 'Buy Now'],
            'move_to_cart' => ['Move to Cart', 'Move to cart'],
            'save_for_later' => ['Save For Later', 'Save For Later'],
            'clear_cart' => ['Clear Cart', 'Clear Cart'],
            'cart_total' => ['Cart Total', 'Cart Total'],
            'empty_cart_message' => ['Empty Cart Message', 'Your Cart Is Empty'],
            'return_to_shop' => ['Return To Shop', 'Return To Shop'],
            'continue_shopping' => ['Continue Shopping', 'Continue Shopping'],
            'quantity' => ['Quantity', 'Quantity'],
            'qty' => ['Qty (short form)', 'Qty'],
            'subtotal' => ['Subtotal', 'Subtotal'],
            'total' => ['Total', 'Total'],
            'grand_total' => ['Grand Total', 'Grand Total'],
            'promo_code' => ['Promo Code (cart)', 'Promo code'],
            'promocode' => ['Promo Code', 'Promo Code'],
            'redeem' => ['Redeem', 'Redeem'],
            'see_all_offers' => ['See All Offers', 'See All Offers'],
        ],
    ],
    'checkout' => [
        'title' => 'Checkout & Payment',
        'icon'  => 'fa-credit-card',
        'labels' => [
            'checkout' => ['Checkout', 'Checkout'],
            'go_to_checkout' => ['Go To Checkout', 'Go To Checkout'],
            'place_order' => ['Place Order', 'Place Order'],
            'order_summary' => ['Order Summary', 'Order Summary'],
            'billing_details' => ['Billing Details', 'Billing Details'],
            'billing_address' => ['Billing Address', 'Billing address'],
            'shipping_address' => ['Shipping Address', 'Shipping Address'],
            'preferred_delivery_date_time' => ['Preferred Delivery Date / Time', 'Preferred Delivery Date / Time'],
            'select_payment_method' => ['Select Payment Method', 'Select Payment Method'],
            'payment_method' => ['Payment Method', 'Payment Method'],
            'cash_on_delivery' => ['Cash On Delivery', 'Cash On Delivery'],
            'delivery_charge' => ['Delivery Charge', 'Delivery Charge'],
            'tax' => ['Tax', 'Tax'],
            'promocode_discount' => ['Promocode Discount', 'Promocode Discount'],
            'wallet_used' => ['Wallet Used', 'Wallet Used'],
            'total_order_price' => ['Total Order Price', 'Total Order Price'],
            'final_total' => ['Final Total', 'Final Total'],
            'payment_completed' => ['Payment Complete', 'Payment Complete'],
            'payment_completed_message' => ['Payment Complete - Message', 'Payment Completed Successfully'],
            'payment_cancelled' => ['Payment Cancelled / Failed', 'Payment Cancelled / Failed'],
            'payment_cancelled_message' => ['Payment Cancelled - Message', 'It seems like payment process is failed or cancelled.Please Try again.'],
            'payment_cancelled_description' => ['Payment Cancelled - Description', 'Payment Cancelled Description'],
            'thank_you_for_shopping' => ['Thank You For Shopping', 'Thank You For Shopping'],
            'thank_you_for_shopping_with_us' => ['Thank You For Shopping With Us', 'Thank you for Shopping with Us'],
        ],
    ],
    'orders' => [
        'title' => 'Orders',
        'icon'  => 'fa-receipt',
        'labels' => [
            'orders' => ['Orders', 'Orders'],
            'my_orders' => ['My Orders', 'My Orders'],
            'order_id' => ['Order ID', 'Order ID'],
            'place_on' => ['Placed On', 'Place On'],
            'invoice' => ['Invoice', 'Invoice'],
            'shipping_details' => ['Shipping Details', 'Shipping Details'],
            'return' => ['Return', 'Return'],
            'back_to_list' => ['Back to List', 'Back to List'],
        ],
    ],
    'address' => [
        'title' => 'Address & Contact Fields',
        'icon'  => 'fa-map-marker-alt',
        'labels' => [
            'address' => ['Address', 'Address'],
            'create_a_new_address' => ['Create a New Address', 'Create a New Address'],
            'edit_address' => ['Edit Address', 'Edit Address'],
            'name' => ['Name', 'Name'],
            'mobile_number' => ['Mobile Number', 'Mobile Number'],
            'alternate_mobile' => ['Alternate Mobile', 'Alternate Mobile'],
            'city' => ['City', 'City'],
            'select_city' => ['Select City', 'Select City'],
            'area' => ['Area', 'Area'],
            'select_area' => ['Select Area', 'Select Area'],
            'landmark' => ['Landmark', 'Landmark'],
            'pincode' => ['Pincode', 'Pincode'],
            'state' => ['State', 'State'],
            'country' => ['Country', 'Country'],
            'type' => ['Type', 'Type'],
            'office' => ['Office', 'Office'],
            'other' => ['Other', 'Other'],
        ],
    ],
    'wallet' => [
        'title' => 'Wallet & Bank Details',
        'icon'  => 'fa-wallet',
        'labels' => [
            'wallet' => ['Wallet', 'Wallet'],
            'wallet_balance' => ['Wallet Balance', 'Wallet Balance'],
            'available_balance' => ['Available Balance', 'Available Balance'],
            'balance' => ['Balance', 'Balance'],
            'transaction' => ['Transaction', 'Transaction'],
            'transactions' => ['Transactions', 'Transactions'],
            'account_details' => ['Account Details', 'Account Details'],
            'account_name' => ['Account Name', 'Account Name'],
            'account_number' => ['Account Number', 'Account Number'],
            'bank_name' => ['Bank Name', 'Bank Name'],
            'bank_code' => ['Bank Code', 'Bank Code'],
            'extra_details' => ['Extra Details', 'Extra Details'],
        ],
    ],
    'footer' => [
        'title' => 'Footer, Contact & Policies',
        'icon'  => 'fa-shoe-prints',
        'labels' => [
            'newsletter' => ['Newsletter', 'Newsletter'],
            'subscribe' => ['Subscribe', 'Subscribe'],
            'useful_links' => ['Useful Links', 'Useful Links'],
            'social_media' => ['Social Media', 'Social Media'],
            'follow_us' => ['Follow Us', 'Follow us'],
            'find_us' => ['Find Us', 'Find Us'],
            'call_us' => ['Call Us', 'Call Us'],
            'mail_us' => ['Mail Us', 'Mail Us'],
            'email_us' => ['Email Us', 'Email Us'],
            'email' => ['Email', 'Email'],
            'subject' => ['Subject', 'Subject'],
            'message' => ['Message', 'Message'],
            'send_message' => ['Send Message', 'Send Message'],
            'terms_and_condition' => ['Terms & Condition', 'Terms & Condition'],
            'privacy_policy' => ['Privacy Policy', 'Privacy Policy'],
            'shipping_policy' => ['Shipping Policy', 'Shipping Policy'],
            'return_policy' => ['Return Policy', 'Return Policy'],
        ],
    ],
    'common' => [
        'title' => 'Common Buttons & Actions',
        'icon'  => 'fa-mouse-pointer',
        'labels' => [
            'submit' => ['Submit', 'Submit'],
            'save' => ['Save', 'Save'],
            'cancel' => ['Cancel', 'Cancel'],
            'close' => ['Close', 'Close'],
            'clear' => ['Clear', 'Clear'],
            'reset' => ['Reset', 'Reset'],
            'remove' => ['Remove', 'Remove'],
            'action' => ['Action', 'Action'],
        ],
    ],
];

$total_labels = 0;
foreach ($label_groups as $group) {
    $total_labels += count($group['labels']);
}
?>
<div class="content-wrapper admin-languages-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-language mr-2 text-primary-theme"></i>Language Management</h4>
                    <p class="text-muted mb-0 small">Translate the labels shown across the storefront.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Languages</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form class="form-horizontal" id="update-language-form" action="<?= base_url('admin/language/save'); ?>" method="POST">
                <input type="hidden" id="id" name="language_id" value="<?= html_escape($language['id']) ?>">

                <!-- Toolbar: language picker, label search, RTL and save all live here -->
                <div class="lang-toolbar">
                    <div class="lang-toolbar-item">
                        <label for="selected_language">Language</label>
                        <select name="selected_language" id="selected_language" class="form-control">
                            <?php foreach ($languages as $row) { ?>
                                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $language['id']) ? 'selected' : '' ?>><?= ucfirst($row['language']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="lang-toolbar-item lang-toolbar-search">
                        <label for="label-search">Search labels</label>
                        <div class="lang-search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" id="label-search" class="form-control" placeholder="Filter <?= $total_labels ?> labels..." autocomplete="off">
                        </div>
                    </div>
                    <div class="lang-toolbar-item lang-toolbar-rtl">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_rtl" id="is_rtl" value="1" <?= (!empty($language['is_rtl'])) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="is_rtl">Right-to-left (RTL)</label>
                        </div>
                    </div>
                    <div class="lang-toolbar-actions">
                        <a href="#" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#language-modal">
                            <i class="fas fa-plus mr-1"></i>Add Language
                        </a>
                        <button type="submit" class="btn btn-primary-theme btn-sm" id="update_btn">
                            <i class="fas fa-save mr-1"></i>Save Changes
                        </button>
                    </div>
                </div>

                <div id="update-result" class="p-3 mb-3" style="display:none;"></div>
                <div id="label-search-empty" class="lang-empty" style="display:none;">
                    <i class="fas fa-search mb-2"></i>
                    <p class="mb-0">No labels match your search.</p>
                </div>

                <?php foreach ($label_groups as $group_key => $group) { ?>
                    <div class="card lang-card" data-group="<?= $group_key ?>">
                        <div class="card-header lang-card-header" data-toggle="collapse" data-target="#group-<?= $group_key ?>" role="button" aria-expanded="true">
                            <span class="header-icon"><i class="fas <?= $group['icon'] ?>"></i></span>
                            <h5 class="mb-0"><?= $group['title'] ?></h5>
                            <span class="lang-count badge"><?= count($group['labels']) ?></span>
                            <i class="fas fa-chevron-up lang-caret ml-auto"></i>
                        </div>
                        <div class="collapse show" id="group-<?= $group_key ?>">
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($group['labels'] as $key => $meta) {
                                        $caption = $meta[0];
                                        $value = (isset($lang_labels[$key]) && $lang_labels[$key] !== '') ? $lang_labels[$key] : $meta[1];
                                    ?>
                                        <div class="col-md-4 col-sm-6 lang-field" data-search="<?= html_escape(strtolower($caption . ' ' . $key)) ?>">
                                            <div class="form-group">
                                                <label for="lbl-<?= $key ?>"><?= html_escape($caption) ?></label>
                                                <input type="text" id="lbl-<?= $key ?>" name="<?= $key ?>" class="form-control" value="<?= html_escape($value) ?>" />
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="text-right mb-4">
                    <button type="submit" class="btn btn-primary-theme"><i class="fas fa-save mr-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </section>
</div>

<div class="modal fade" id="language-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Language</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" id="add-new-language-form" action="<?= base_url('admin/language/create'); ?>" method="POST">
                    <div class="form-group">
                        <label for="language">Name <small class="text-muted">(in English, letters only)</small></label>
                        <input type="text" name="language" id="language" class="form-control" placeholder="Ex. English, Hindi" />
                    </div>
                    <div class="form-group">
                        <label for="code">Code</label>
                        <input type="text" name="code" id="code" class="form-control" placeholder="Ex. en, hi" />
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="is_rtl" id="is_rtl_create" value="1" />
                            <label class="custom-control-label" for="is_rtl_create">Right-to-left (RTL)</label>
                        </div>
                    </div>
                    <div id="result" class="mb-2"></div>
                    <button type="submit" class="btn btn-primary-theme" id="submit_btn">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-languages-page .text-primary-theme { color: var(--color-orange); }
    .admin-languages-page .btn-primary-theme,
    #language-modal .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-languages-page .btn-primary-theme:hover,
    #language-modal .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-languages-page .lang-toolbar {
        position: sticky;
        top: 0;
        z-index: 20;
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 14px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.08);
        padding: 14px 16px;
        margin-bottom: 1.25rem;
    }
    .admin-languages-page .lang-toolbar-item { min-width: 180px; }
    .admin-languages-page .lang-toolbar-search { flex: 1 1 260px; }
    .admin-languages-page .lang-toolbar > div > label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; color: #6c757d; margin-bottom: 4px; }
    .admin-languages-page .lang-toolbar-rtl { display: flex; align-items: center; padding-bottom: 8px; }
    .admin-languages-page .lang-toolbar-rtl label { text-transform: none; font-size: 13px; color: #495057; margin-bottom: 0; }
    .admin-languages-page .lang-toolbar-actions { margin-left: auto; display: flex; gap: 8px; padding-bottom: 2px; }
    .admin-languages-page .lang-search-wrap { position: relative; }
    .admin-languages-page .lang-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: 13px; }
    .admin-languages-page .lang-search-wrap .form-control { padding-left: 32px; }

    .admin-languages-page .lang-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 1.25rem; }
    .admin-languages-page .lang-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px 10px 0 0;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    .admin-languages-page .lang-card-header h5 { font-size: 15px; font-weight: 600; }
    .admin-languages-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        background: var(--color-orange); color: #fff; font-size: 14px; flex: none;
    }
    .admin-languages-page .lang-count { background: #f1f3f5; color: #6c757d; font-weight: 600; }
    .admin-languages-page .lang-caret { color: #adb5bd; transition: transform .2s ease; }
    .admin-languages-page .lang-card-header.collapsed .lang-caret { transform: rotate(180deg); }

    .admin-languages-page .lang-field label { font-size: 13px; font-weight: 600; color: #343a40; display: block; margin-bottom: 4px; }
    .admin-languages-page .lang-field .form-control { font-size: 14px; }

    .admin-languages-page .lang-empty { text-align: center; color: #868e96; padding: 40px 0; }
    .admin-languages-page .lang-empty i { font-size: 26px; display: block; }

    @media (max-width: 767px) {
        .admin-languages-page .lang-toolbar { position: static; }
        .admin-languages-page .lang-toolbar-actions { margin-left: 0; width: 100%; }
    }
</style>

<script>
    (function () {
        var search = document.getElementById('label-search');
        if (!search) {
            return;
        }
        var fields = [].slice.call(document.querySelectorAll('.lang-field'));
        var cards = [].slice.call(document.querySelectorAll('.lang-card'));
        var empty = document.getElementById('label-search-empty');

        search.addEventListener('input', function () {
            var term = this.value.trim().toLowerCase();
            fields.forEach(function (field) {
                var hit = !term || field.getAttribute('data-search').indexOf(term) !== -1;
                field.style.display = hit ? '' : 'none';
                field.setAttribute('data-hit', hit ? '1' : '0');
            });
            var visible = 0;
            cards.forEach(function (card) {
                var shown = card.querySelectorAll('.lang-field[data-hit="1"]').length;
                if (!term) {
                    shown = card.querySelectorAll('.lang-field').length;
                }
                card.style.display = shown ? '' : 'none';
                visible += shown;
            });
            empty.style.display = (term && !visible) ? 'block' : 'none';
        });
    })();
</script>
