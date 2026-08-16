<?php $settings = get_settings('system_settings', true);
$doctor_brown_for_app = get_settings('doctor_brown');

$authentication_settings = get_settings('authentication_settings');
$sms_gateway_settings = get_settings('sms_gateway_settings');
// print_r($authentication_settings);

if ($sms_gateway_settings !== null && is_string($sms_gateway_settings)) {
    $sms_gateway_data = get_settings('sms_gateway_settings');
}else{
    $sms_gateway_data = [];
}

// print_r($sms_gateway_data);

if ($authentication_settings !== null && is_string($authentication_settings)) {
    $authentication = json_decode(get_settings('authentication_settings'), true);
}else{
    $authentication = [];
}
?>
<input type="hidden" id="sms_gateway_data" value='<?= isset($sms_gateway_data) ? ($sms_gateway_data) : [] ?>' />
<aside class="main-sidebar sidebar-light-primary elevation-2 sidebar-no-expand">
    <!-- Brand Logo -->
    <a href="<?= base_url('admin/home') ?>" class="brand-link">
        <img src="<?= base_url()  . get_settings('favicon') ?>" alt="<?= $settings['app_name']; ?>" title="<?= $settings['app_name']; ?>" class="img-fluid" style="max-height:40px; width:auto; margin-left:0;">
        <!-- <span class="brand-text font-weight-light small"><?= $settings['app_name']; ?></span> -->
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-flat" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->


                <li class="nav-item has-treeview">
                    <a href="<?= base_url('/admin/home') ?>" class="nav-link">
                        <i class="nav-icon  fas fa-th-large text-primary"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>

                <?php if (has_permissions('read', 'product') || has_permissions('read', 'attribute') || has_permissions('read', 'attribute_set') || has_permissions('read', 'attribute_value') || has_permissions('read', 'tax') || has_permissions('read', 'product_order')) { ?>
                    <li class="nav-item has-treeview ">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-cubes text-primary"></i>
                            <p>
                                Products
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <!-- <?php if (has_permissions('read', 'attribute_set')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/attribute_set/manage-attribute-set') ?>" class="nav-link">
                                        <i class="fa fa-cogs nav-icon"></i>
                                        <p>Attribute Sets</p>
                                    </a>
                                </li>
                            <?php } ?>  -->

                            <?php if (has_permissions('read', 'attribute')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/attributes/manage-attribute') ?>" class="nav-link">
                                        <i class="fas fa-sliders-h nav-icon"></i>
                                        <p>Attributes</p>
                                    </a>
                                </li>
                            <?php } ?>


                            <!-- <?php if (has_permissions('read', 'attribute_value')) { ?>

                                <li class="nav-item">
                                    <a href="<?= base_url('admin/attribute_value/manage-attribute-value') ?>" class="nav-link">
                                        <i class="fas fa-filter nav-icon"></i>
                                        <p>Attribute Values</p>
                                    </a>
                                </li>
                            <?php } ?> -->

                            <?php if (has_permissions('read', 'tax')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/taxes/manage-taxes') ?>" class="nav-link">
                                        <i class="fas fa-percentage nav-icon"></i>
                                        <p>Tax</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'product')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/product/create-product') ?>" class="nav-link">
                                        <i class="fas fa-plus-square nav-icon"></i>
                                        <p>Add Products</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'product')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/product/bulk-upload') ?>" class="nav-link">
                                        <i class="fas fa-upload nav-icon"></i>
                                        <p>Bulk upload</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'product')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/product/') ?>" class="nav-link">
                                        <i class="fas fa-boxes nav-icon"></i>
                                        <p>Manage Products</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php
                            // 'product_faqs' is not a registered module in the system_modules
                            // permission list, so this link was permanently hidden from every
                            // restricted admin account - only the primary administrator (who
                            // bypasses permission checks entirely) could ever see it, matching
                            // the same fix applied to Product_faqs::__construct().
                            if (has_permissions('read', 'product')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/product_faqs/') ?>" class="nav-link">
                                        <i class="fas fa-question-circle nav-icon"></i>
                                        <p>Product FAQs</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'product_order')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/product/product-order') ?>" class="nav-link">
                                        <i class="fa fa-bars nav-icon"></i>
                                        <p>Products Order</p>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>

                <?php if (has_permissions('read', 'orders')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-shopping-cart text-warning"></i>
                            <p>
                                Orders
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (has_permissions('read', 'orders')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/orders/') ?>" class="nav-link">
                                        <i class="fa fa-shopping-cart nav-icon"></i>
                                        <p>Orders</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'orders')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/orders/order-tracking') ?>" class="nav-link">
                                        <i class="fa fa-map-marker-alt nav-icon"></i>
                                        <p>Order Tracking</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'orders')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/notification_settings/manage_system_notifications') ?>" class="nav-link">
                                        <i class="fas fa-bell nav-icon"></i>
                                        <p>System Notifications</p>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>

                <?php if (has_permissions('read', 'categories')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-bullseye text-success"></i>
                            <p>
                                Categories
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (has_permissions('read', 'categories')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/category/') ?>" class="nav-link">
                                        <i class="fa fa-bullseye nav-icon"></i>
                                        <p>Categories</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'category_order')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/category/category-order') ?>" class="nav-link">
                                        <i class="fa fa-bars nav-icon"></i>
                                        <p>Category Order</p>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>

                <?php if (has_permissions('read', 'brands')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fab fa-adversal text-primary"></i>
                            <p>
                                Brands
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (has_permissions('read', 'brands')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/brand/') ?>" class="nav-link">
                                        <i class="fab fa-adversal nav-icon"></i>
                                        <p>brands</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= base_url('admin/brand/bulk-upload') ?>" class="nav-link">
                                        <i class="fas fa-upload nav-icon"></i>
                                        <p>Bulk upload</p>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>

                <?php if (has_permissions('read', 'seller')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-store text-danger"></i>
                            <p>
                                Sellers
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (has_permissions('read', 'seller')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/sellers/') ?>" class="nav-link">
                                        <i class="fa fa-store nav-icon"></i>
                                        <p>Sellers</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'seller')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/transaction/wallet-transactions') ?>" class="nav-link">
                                        <i class="fa fa-wallet nav-icon"></i>
                                        <p>Wallet Transactions</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php // Both screens are gated on the 'subscription' module in
                                  // admin/Subscription.php, so hide the links from roles that
                                  // don't hold it rather than showing a link that bounces
                                  // straight back to the dashboard. ?>
                            <?php if (has_permissions('read', 'subscription')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/subscription/manage_subscriptions') ?>" class="nav-link">
                                        <i class="fas fa-file-invoice-dollar nav-icon"></i>
                                        <p>
                                            Subscriptions
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/subscription/seller_subscriptions') ?>" class="nav-link">
                                        <i class="fas fa-user-tag nav-icon"></i>
                                        <p>
                                            Seller Subscriptions
                                        </p>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-blog text-warning"></i>
                        <p>
                            Blogs
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="<?= base_url('admin/blogs') ?>" class="nav-link">
                                <i class="fas fa-bullseye nav-icon"></i>
                                <p>Blog Categories</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('admin/blogs/manage_blogs') ?>" class="nav-link">
                                <i class="fas fa-upload nav-icon"></i>
                                <p>Create blog</p>
                            </a>
                        </li>

                    </ul>
                </li>


                <?php if (has_permissions('read', 'media')) { ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/media/') ?>" class="nav-link">
                            <i class="nav-icon fas fa-icons text-danger"></i>
                            <p>
                                Media
                            </p>
                        </a>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'home_slider_images')) { ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/slider/manage-slider') ?>" class="nav-link">
                            <i class="nav-icon far fa-image text-success"></i>
                            <p>
                                Sliders
                            </p>
                        </a>
                    </li>
                <?php } ?>

                <?php if (has_permissions('read', 'new_offer_images')) { ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/offer/manage-offer') ?>" class="nav-link">
                            <i class="nav-icon fa fa-gift text-primary"></i>
                            <p>
                                Offers
                            </p>
                        </a>
                    </li>
                <?php } ?>

                <!-- manage stock -->
                <?php if (has_permissions('read', 'manage_stock')) { ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/manage_stock') ?>" class="nav-link">
                            <i class="nav-icon fa fa-cube text-success"></i>
                            <p>
                                Manage Stock
                            </p>
                        </a>
                    </li>
                <?php } ?>

                <?php if (has_permissions('read', 'chat')) { ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/chat') ?>" class="nav-link">
                            <i class="nav-icon 	far fa-comments text-warning"></i>
                            <p>
                                Chat
                            </p>
                        </a>
                    </li>
                <?php } ?>


                <?php if (has_permissions('read', 'support_tickets')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-ticket-alt text-danger"></i>
                            <p>
                                Support Tickets
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/tickets/ticket-types') ?>" class="nav-link">
                                    <i class="fas fa-money-bill-wave nav-icon"></i>
                                    <p>Ticket Types</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/tickets') ?>" class="nav-link">
                                    <i class="fas fa-ticket-alt nav-icon"></i>
                                    <p>Tickets</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'promo_code')) { ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/promo-code/manage-promo-code') ?>" class="nav-link">
                            <i class="nav-icon fa fa-puzzle-piece text-warning"></i>
                            <p>
                                Promo code
                            </p>
                        </a>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'featured_section')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-layer-group text-danger"></i>
                            <p>
                                Featured Sections
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/featured-sections/') ?>" class="nav-link">
                                    <i class="fas fa-folder-plus nav-icon"></i>
                                    <p>Manage Sections</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/featured-sections/section-order') ?>" class="nav-link">
                                    <i class="fa fa-bars nav-icon"></i>
                                    <p>Sections Order</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'customers')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fa fa-user text-success"></i>
                            <p>
                                Customer
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/customer/') ?>" class="nav-link">
                                    <i class="fas fa-users nav-icon"></i>
                                    <p> View Customers </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/customer/addresses') ?>" class="nav-link">
                                    <i class="far fa-address-book nav-icon"></i>
                                    <p> Addresses </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/transaction/view-transaction') ?>" class="nav-link">
                                    <i class="fas fa-money-bill-wave nav-icon "></i>
                                    <p> Transactions </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/transaction/customer-wallet') ?>" class="nav-link">
                                    <i class="fas fa-wallet nav-icon "></i>
                                    <p>Wallet Transactions</p>
                                </a>
                            </li>

                        </ul>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'return_request')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="<?= base_url('admin/return-request') ?>" class="nav-link">
                            <i class="nav-icon fas fa-undo text-warning"></i>
                            <p>
                                Return Requests
                            </p>
                        </a>
                    </li>
                <?php } ?>
                <?php /* Delivery Boys menu hidden for now — re-enable by removing the `false &&` below.
                <?php if (false && (has_permissions('read', 'delivery_boy') || has_permissions('read', 'fund_transfer'))) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-id-card-alt text-info"></i>
                            <p>
                                Delivery Boys
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (has_permissions('read', 'delivery_boy')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/delivery-boys/manage-delivery-boy') ?>" class="nav-link text-sm">
                                        <i class="fas fa-user-cog nav-icon "></i>
                                        <p> Manage Delivery Boys </p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'fund_transfer')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/fund-transfer/') ?>" class="nav-link">
                                        <i class="fa fa-rupee-sign nav-icon "></i>
                                        <p>Fund Transfer</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'delivery_boy')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/delivery-boys/manage-cash') ?>" class="nav-link text-sm">
                                        <i class="fas fa-money-bill-alt nav-icon "></i>
                                        <p> Manage Cash Collection </p>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
                */ ?>

                <?php // Was gated on 'return_request' while admin/Payment_request's constructor
                      // requires read on 'payment_request'. A role granted return_request but not
                      // payment_request saw this link and was bounced straight back to the
                      // dashboard by the permission error on every click.
                if (has_permissions('read', 'payment_request')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="<?= base_url('admin/payment-request') ?>" class="nav-link">
                            <i class="nav-icon fas fa-money-bill-wave text-danger"></i>
                            <p>Payment Request</p>
                        </a>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'seller')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="<?= base_url('admin/settlement') ?>" class="nav-link">
                            <i class="nav-icon fas fa-percentage text-info"></i>
                            <p>Commission &amp; Settlements</p>
                        </a>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'send_notification')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="<?= base_url('admin/Notification-settings/manage-notifications') ?>" class="nav-link">
                            <i class="nav-icon fas fa-paper-plane text-success"></i>
                            <p>
                                Send Notification
                            </p>
                        </a>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'settings')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="<?= base_url('admin/custom_notification') ?>" class="nav-link">
                            <i class="nav-icon fas fa-bell text-info"></i>
                            <p>
                                Custom message
                            </p>
                        </a>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'settings')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fa fa-wrench text-primary"></i>
                            <p>
                                System
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/setting') ?>" class="nav-link">
                                    <i class="fas fa-store nav-icon "></i>
                                    <p>Store Settings</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/email-settings') ?>" class="nav-link">
                                    <i class="fas fa-envelope-open-text nav-icon "></i>
                                    <p>Email Settings</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/payment-settings') ?>" class="nav-link">
                                    <i class="fas fa-rupee-sign nav-icon "></i>
                                    <p>Payment Methods</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/shipping-settings') ?>" class="nav-link">
                                    <i class="fas fa-rocket nav-icon "></i>
                                    <p>Shipping Methods</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/time-slots') ?>" class="nav-link">
                                    <i class="fas fa-calendar-alt nav-icon "></i>
                                    <p>Time Slots</p>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a href="<?//= base_url('admin/authentication-settings') ?>" class="nav-link">
                                    <i class="fa fa-bell nav-icon "></i>
                                    <p>Authentication Mode</p>
                                </a>
                            </li> -->
                            <li class="nav-item">
                                <a href="<?= base_url('admin/notification-settings') ?>" class="nav-link">
                                    <i class="fa fa-bell nav-icon "></i>
                                    <p>Notification Settings</p>
                                </a>
                            </li>
                            <?//php if (isset($authentication['authentication_method']) && $authentication['authentication_method'] == 'sms') { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/sms-gateway-settings') ?>" class="nav-link">
                                        <i class="fa fa-sms nav-icon "></i>
                                        <p>SMS Gateway Settings</p>
                                    </a>
                                </li>
                            <?//php } ?>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/contact-us') ?>" class="nav-link">
                                    <i class="fa fa-phone-alt nav-icon "></i>
                                    <p>Contact Us</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/about-us') ?>" class="nav-link">
                                    <i class="fas fa-info-circle nav-icon "></i>
                                    <p>About Us</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/privacy-policy') ?>" class="nav-link">
                                    <i class="fa fa-user-secret nav-icon "></i>
                                    <p>Privacy Policy</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/privacy-policy/shipping-policy') ?>" class="nav-link">
                                    <i class="fa fa-shipping-fast nav-icon "></i>
                                    <p>Shipping Policy</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/privacy-policy/return-policy') ?>" class="nav-link">
                                    <i class="fa fa-undo nav-icon "></i>
                                    <p>Return Policy</p>
                                </a>
                            </li>
                            <li class="nav-item text-sm">
                                <a href="<?= base_url('admin/admin-privacy-policy') ?>" class="nav-link">
                                    <i class="fa fa-exclamation-triangle nav-icon  "></i>
                                    <p>Admin Policies</p>
                                </a>
                            </li>
                            <li class="nav-item text-sm">
                                <a href="<?= base_url('admin/delivery-boy-privacy-policy') ?>" class="nav-link">
                                    <i class="fa fa-exclamation-triangle nav-icon  "></i>
                                    <p>Delivery Boy Policies</p>
                                </a>
                            </li>
                            <li class="nav-item text-sm">
                                <a href="<?= base_url('admin/seller-privacy-policy') ?>" class="nav-link">
                                    <i class="fa fa-exclamation-triangle nav-icon  "></i>
                                    <p>Seller Policies</p>
                                </a>
                            </li>
                            <?php if (isset($doctor_brown_for_app) && !empty($doctor_brown_for_app)) {
                            ?>
                                <li class="nav-item text-sm">
                                    <a href="<?= base_url('admin/client-api-keys/') ?>" class="nav-link">
                                        <i class="fa fa-key nav-icon  "></i>
                                        <p>Client Api Keys</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/updater') ?>" class="nav-link">
                                    <i class="fas fa-sync nav-icon "></i>
                                    <p>System Updater</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/purchase-code') ?>" class="nav-link">
                                    <i class="fas fa-check nav-icon"></i>
                                    <p>System Registration</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fa fa-globe-asia text-warning"></i>
                            <p>
                                Web Settings
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/web-setting') ?>" class="nav-link">
                                    <i class="fa fa-laptop nav-icon "></i>
                                    <p>General Settings</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/themes') ?>" class="nav-link">
                                    <i class="fa fa-palette nav-icon "></i>
                                    <p>Themes</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/language') ?>" class="nav-link">
                                    <i class="fa fa-language nav-icon "></i>
                                    <p>Languages</p>
                                </a>
                            </li>
                            <?//php if (isset($authentication['authentication_method']) && $authentication['authentication_method'] == 'firebase') { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/web-setting/firebase') ?>" class="nav-link">
                                        <i class="bx bxl-firebase nav-icon "></i>
                                        <p>Firebase</p>
                                    </a>
                                </li>
                            <?//php } ?>
                        </ul>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'pickup_location')) { ?>
                    <li class="nav-item has-treeview">
                        <a href="<?= base_url('admin/Pickup_location/manage-pickup-locations') ?>" class="nav-link">
                            <i class="nav-icon fas fa-shipping-fast text-success"></i>
                            <p>
                                Pickup Location
                            </p>
                        </a>
                    </li>
                <?php } ?>
                <?php if (has_permissions('read', 'area') || has_permissions('read', 'city') || has_permissions('read', 'zipcodes')) { ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-map-marked-alt text-danger"></i>
                            <p>
                                Location
                                <i class="right fas fa-angle-left "></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (has_permissions('read', 'zipcodes')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/area/manage-zipcodes') ?>" class="nav-link">
                                        <i class="fa fa-map-pin nav-icon "></i>
                                        <p>Zipcodes</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'city')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/area/manage-cities') ?>" class="nav-link">
                                        <i class="fa fa-location-arrow nav-icon "></i>
                                        <p>City</p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'area')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/area/manage-areas') ?>" class="nav-link">
                                        <i class="fas fa-street-view nav-icon "></i>
                                        <p>
                                            Areas
                                        </p>
                                    </a>
                                </li>
                            <?php } ?>
                            
                            <?php if (has_permissions('read', 'area')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/area/manage_countries') ?>" class="nav-link">
                                        <i class="fas fa-solid fa-globe nav-icon "></i>
                                        <p>
                                            Countries
                                        </p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if (has_permissions('read', 'area') && has_permissions('read', 'city') && has_permissions('read', 'zipcodes')) { ?>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/area/location-bulk-upload') ?>" class="nav-link">
                                        <i class="fas fa-upload nav-icon"></i>
                                        <p>Bulk upload</p>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-pie nav-icon text-primary"></i>
                        <p>Reports
                            <i class="right fas fa-angle-left "></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= base_url('admin/sales-report') ?>" class="nav-link">
                                <i class="fa fa-chart-line nav-icon "></i>
                                <p>Sales Report</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('admin/sales-inventory') ?>" class="nav-link">
                                <i class="fa fa-chart-line nav-icon "></i>
                                <p>Sale Inventory Reports</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <?php if (has_permissions('read', 'faq')) { ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/faq/') ?>" class="nav-link">
                            <i class="nav-icon fas fa-question-circle text-warning"></i>
                            <p class="text">FAQ</p>
                        </a>
                    </li>
                    <?php }
                $userData = get_user_permissions($this->session->userdata('user_id'));
                if (!empty($userData)) {
                    if ($userData[0]['role'] == 0 || $userData[0]['role'] == 1) {
                    ?>
                        <li class="nav-item mb-4">
                            <a href="<?= base_url('admin/system-users/') ?>" class="nav-link">
                                <i class="nav-icon fas fa-user-tie text-danger"></i>
                                <p class="text">System Users</p>
                            </a>
                        </li>
                <?php
                    }
                } ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<script>
    // No link in this sidebar was ever marked active for the page currently being viewed -
    // every single admin page rendered every nav-link identically, with no indication at all
    // of where you are. Matches each link's own URL against the current page and highlights it,
    // opening its parent submenu (if nested) the same way AdminLTE's own treeview widget does
    // when a user clicks to expand a section (adding "menu-open" to the parent <li>, not the
    // <a> - a few submenu toggles in this file had "menu-open" on the <a> instead, which matches
    // neither this widget's own convention nor the CSS that depends on it).
    $(function () {
        var currentPath = window.location.pathname.replace(/\/+$/, '');

        $('.main-sidebar .nav-link[href]').each(function () {
            var rawHref = $(this).attr('href');
            // Section toggles (Products / Orders / Categories / ...) use href="#" to expand
            // their own submenu rather than navigate anywhere. "#" resolves its .pathname to
            // the CURRENT page (a same-page anchor), so every one of these toggles matched the
            // "current page" check below and got marked active - which is why every section
            // rendered open, and its heading solid orange, on every single page load regardless
            // of which page was actually open. Real page links only past this point.
            if (!rawHref || rawHref === '#' || rawHref.charAt(0) === '#' || rawHref.indexOf('javascript:') === 0) {
                return;
            }

            var linkPath = this.pathname.replace(/\/+$/, '');
            if (!linkPath || linkPath !== currentPath) {
                return;
            }

            $(this).addClass('active');
            $(this).parents('li.nav-item.has-treeview').addClass('menu-open');
        });
    });
</script>