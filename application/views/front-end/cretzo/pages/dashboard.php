<?php
/* print_r($users);
die(); */
?>

<!-- products starts -->
<section class="accounts-container">

    <!-- my profile -->
    <div class="overview-container">
        <div class="overview-upper">
            <img class="profile-icon-big" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/profile-icon.png') ?>">
            <div>
                <h1 class="heading-b"><?= $users->username ?></h1>
                <p class="text-b">+91 <?= $users->mobile ?></p>
                <p class="text-b"><?= $users->email ?></p>
            </div>
            <a href='<?= base_url('my-account/profile') ?>' class="link-color text-decoration-none">
                <button class="cretzo btn btn-light">EDIT PROFILE</button>
            </a>
        </div>
        <div class="overview-lower">
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/orders') ?>';">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/myorder-icon.png') ?>">
                <h1 class="heading-n">Orders</h1>
                <p class="text-n">Check your order status</p>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/favorites') ?>';">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/mywishlist-icon.png') ?>">
                <h1 class="heading-n">My Wishlist</h1>
                <p class="text-n">All your wishlisted products</p>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/manage-address') ?>';">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/address-icon.png') ?>">
                <h1 class="heading-n">Address</h1>
                <p class="text-n">Save addresses for a hassle-free checkout</p>
            </div>
            <!-- <div class="overview-box">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/saved-cards.png') ?>">
                <h1 class="heading-n">Saved Cards</h1>
                <p class="text-n">Save your cards for faster checkout</p>
            </div>
            <div class="overview-box">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/coupons-icon.png') ?>">
                <h1 class="heading-n">Coupons</h1>
                <p class="text-n">Manage coupons for additional discounts</p>
            </div> -->
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/wallet') ?>';">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/my-wallet.png') ?>">
                <h1 class="heading-n">My Wallet</h1>
                <p class="text-n">Earn cretzo cash as you shop and use them during checkout</p>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/chat') ?>';">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/chatwithus-icon.png') ?>">
                <h1 class="heading-n">Chat with Us</h1>
                <p class="text-n">Contact us over chat for a quick resolution to your problem</p>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('contact-us') ?>';">
                <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/help-center-icon.png') ?>">
                <h1 class="heading-n">Help Center</h1>
                <p class="text-n">Need help ? Visit our help center.</p>
            </div>
        </div>
        <div class="logout-btn-container">
            <button class="cretzo btn btn-dark logout-btn">LOGOUT</button>
        </div>
    </div>

</section>