<?php
/* print_r($users);
die(); */
?>

<!-- my account / profile overview starts -->
<section class="accounts-container">

    <div class="overview-container">

        <!-- profile header -->
        <div class="overview-upper">
            <div class="profile-info">
                <div class="profile-avatar">
                    <img src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/user.png') ?>" alt="profile">
                </div>
                <div class="profile-meta">
                    <h1 class="heading-b profile-name"><?= $users->username ?></h1>
                    <p class="text-b profile-detail">
                        <i class="uil uil-phone"></i> +91 <?= $users->mobile ?>
                    </p>
                    <p class="text-b profile-detail">
                        <i class="uil uil-envelope"></i> <?= $users->email ?>
                    </p>
                </div>
            </div>
            <div class="hero-actions">
                <a href='<?= base_url('my-account/profile') ?>' class="text-decoration-none">
                    <button class="cretzo btn hero-btn edit-profile-btn">
                        <i class="uil uil-edit"></i> Edit Profile
                    </button>
                </a>
                <a href="<?= base_url('login/logout') ?>" class="text-decoration-none">
                    <button class="cretzo btn hero-btn logout-btn">
                        <i class="uil uil-signout"></i> Logout
                    </button>
                </a>
            </div>
        </div>

        <!-- quick action cards -->
        <div class="overview-lower">
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/orders') ?>';">
                <div class="overview-box-icon-wrap">
                    <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/myorder-icon.png') ?>" alt="orders">
                </div>
                <h1 class="heading-n">Orders</h1>
                <p class="text-n">Check your order status</p>
                <span class="overview-box-arrow"><i class="uil uil-arrow-right"></i></span>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/favorites') ?>';">
                <div class="overview-box-icon-wrap">
                    <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/mywishlist-icon.png') ?>" alt="wishlist">
                </div>
                <h1 class="heading-n">My Wishlist</h1>
                <p class="text-n">All your wishlisted products</p>
                <span class="overview-box-arrow"><i class="uil uil-arrow-right"></i></span>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/manage-address') ?>';">
                <div class="overview-box-icon-wrap">
                    <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/address-icon.png') ?>" alt="address">
                </div>
                <h1 class="heading-n">Address</h1>
                <p class="text-n">Save addresses for a hassle-free checkout</p>
                <span class="overview-box-arrow"><i class="uil uil-arrow-right"></i></span>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/wallet') ?>';">
                <div class="overview-box-icon-wrap">
                    <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/my-wallet.png') ?>" alt="wallet">
                </div>
                <h1 class="heading-n">My Wallet</h1>
                <p class="text-n">Earn cretzo cash as you shop and use them during checkout</p>
                <span class="overview-box-arrow"><i class="uil uil-arrow-right"></i></span>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('my-account/chat') ?>';">
                <div class="overview-box-icon-wrap">
                    <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/chatwithus-icon.png') ?>" alt="chat">
                </div>
                <h1 class="heading-n">Chat with Us</h1>
                <p class="text-n">Contact us over chat for a quick resolution to your problem</p>
                <span class="overview-box-arrow"><i class="uil uil-arrow-right"></i></span>
            </div>
            <div class="overview-box" onclick="location.href='<?= base_url('contact-us') ?>';">
                <div class="overview-box-icon-wrap">
                    <img class="overview-box-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/help-center-icon.png') ?>" alt="help center">
                </div>
                <h1 class="heading-n">Help Center</h1>
                <p class="text-n">Need help ? Visit our help center.</p>
                <span class="overview-box-arrow"><i class="uil uil-arrow-right"></i></span>
            </div>
        </div>
    </div>

</section>
<!-- my account / profile overview ends -->
