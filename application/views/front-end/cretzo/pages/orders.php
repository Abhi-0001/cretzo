<?php

/* $order_status_image = [
    'received' => 'received.png',
    'processed' => 'processed.png',
    'shipped' => 'shipped.png',
    'delivered' => 'delivered.png',
    'cancelled' => 'cancelled.png',
    'returned' => 'returned.png',
]; */

/* echo '<pre>';
print_r(var_dump($orders['order_data'][3]['order_items'][0]));
die; */

?>

<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <p class="text-n"><?= $users->username ?></p>
    <div class="overview-container">

        <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>
        
        <div class="overview-right">

            <h1 class="heading-n overview-right-heading">All Orders <br> <span class="text-s op-6">Orders that you have placed</span></h1>

            <!-- <div class="search-filter-container">
                <input class="input" type="text">
                <button class="cretzo btn light-btn"><img class="filter-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/filter-icon.png') ?>">FILTER</button>
            </div> -->

            <div class="search-filter-container">
                <div class="search-input-wrapper">
                    <input class="input" type="text" id="search-input" placeholder="Search orders..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <span id="clear-search" style="display: none; cursor: pointer;">✕</span>
                </div>
                <button class="cretzo btn light-btn" id="search-btn">
                    <img class="filter-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/filter-icon.png') ?>">FILTER
                </button>
            </div>


            <?php if (!isset($orders['order_data']) && empty($orders['order_data']) || $orders['order_data'] == []) { ?>
                <div class="align-items-center d-flex flex-column">
                    <img src="<?= base_url('assets/front_end/modern/img/empty-orders.webp') ?>" alt="Empty Orders" class="w-23" />
                    <h1 class="h2 text-center"><?= !empty($this->lang->line('no_orders_found')) ? $this->lang->line('no_orders_found') : 'No Order placed Yet.' ?></h1>
                </div>
            <?php } ?>

            <hr class="mt-4 mb-4">

            <ul class="list ordered-product-list">
                <?php foreach ($orders['order_data'] as $row) {
                    foreach ($row['order_items'] as $key => $item) { ?>

                        <li class="ordered-product-list-item">
                            <a href="<?= base_url('my-account/order-details/' . $row['id'] . '/' . $item['id']) ?>" class='card-url text-decoration-none'></a>
                            <div class="delivered-icon-container">
                                <img class="delivered-icon" src="<?= base_url("assets/front_end/cretzo/img/new_cretzo/{$item['active_status']}.png") ?>">
                                <div>

                                    <!-- <h1 class="sub-heading delivered-text"><?= ucwords($item['status'][array_key_last($item['status'])][0]) ?></h1> -->
                                    <h1 class="sub-heading delivered-text"><?= ucwords($item['active_status']) ?></h1>
                                    
                                    <p class="text-s delivered-date">On <?= orderStatusTimeToHumanReadableString($item['status'][array_key_last($item['status'])][1]) ?></p>
                                </div>
                                
                                <div class="order-item-indicator" style="margin-left: auto;">
                                    <i class="uil uil-angle-right fs-28"></i>
                                </div>
                            </div>
                            <div class="delivered-order-details">
                                <div class="delivered-order-details-upper">
                                    <img class="order-img" src="<?= isset($item['variant_image']) && !empty($item['variant_image']) ? $item['variant_image'] : $item['image'] ?>">
                                    <div class="order-details">
                                        <h1 class="sub-heading"><?= $item['product_name'] ?></h1>
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

                                    </div>
                                </div>
                                <!-- <ul>
                                    <li class="text-n">Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia maxime inventore voluptate.</li>
                                </ul> -->

                                <div class="ml-0 pl-0 product-rating-small mt-2" dir="ltr">
                                    <input id="input" name="rating" class="rating rating-loading d-none" data-size="xs" value="<?= $item['product_rating'] ?>" data-show-clear="false" data-show-caption="false" readonly>
                                </div>
                                <ul>
                                    <li>
                                        <p class="text-muted"> <?= !empty($this->lang->line('quantity')) ? $this->lang->line('quantity') : 'Quantity' ?> : <?= $item['quantity'] ?></p>
                                    </li>
                                </ul>
                                <h4 class="mt-1 bold"> <span class="mt-5"><i><?= $settings['currency'] ?></i></span> <?= number_format($item['sub_total'], 2) ?> <span class="small text-muted"> <?= !empty($this->lang->line('via')) ? $this->lang->line('via') : 'via' ?> (<?= $row['payment_method'] ?>) </span></h4>

                                <div>
                                    <?php
                                        $status = ["awaiting", "received", "processed", "shipped", "delivered", "cancelled", "returned"];
                                        $cancelable_till = $item['cancelable_till'];
                                        $active_status = $item['active_status'];
                                        $cancellable_index = array_search($cancelable_till, $status);
                                        $active_index = array_search($active_status, $status);
                                        if (!$item['is_already_cancelled'] && $item['is_cancelable'] && $active_index <= $cancellable_index) { ?>
                                            <a class="update-order" data-status="cancelled" data-order-id="<?= $order['id'] ?>">
                                                <button class="cretzo btn btn-dark btn-cancel-order mt-3">Cancel</button>
                                            </a>
                                    <?php } ?>

                                    <?php
                                        $delivered_index = array_search('delivered', $item['status']);
                                        $delivered_date = $item['status'][$delivered_index][1];
                                        
                                        if ($row['is_returnable'] && !$row['is_already_returned'] && isset($delivered_date) && !empty($delivered_date)) { ?>
                                            <?php
                                            $settings = get_settings('system_settings', true);
                                            $today = date('Y-m-d');
                                            $return_till = date('Y-m-d', strtotime($delivered_date . ' + ' . $settings['max_product_return_days'] . ' days'));
                                            if ($today < $return_till && $row['type'] != 'digital_product') { ?>
                                                <a class="update-order" data-status="returned" data-order-id="<?= $row['id'] ?>">
                                                    <button class="cretzo btn btn-light btn-return-order mt-3">Return Order</button>
                                                </a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </li>
                <?php }
                } ?>
            </ul>

        </div>
    </div>
</div>