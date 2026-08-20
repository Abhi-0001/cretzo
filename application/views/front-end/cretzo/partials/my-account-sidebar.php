<link rel="stylesheet" href="<?= base_url('assets/front_end/cretzo/css/cretzo/my-account-sidebar.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/front_end/cretzo/css/cretzo/my-account-sidebar-override.css'); ?>">

<div class="overview-left">
    <div class="overview-sidemenu">
        <h1 class="text-b list-item">
            <a href="<?= base_url('my-account') ?>" class="text-decoration-none cretzo-link">Dashboard</a>
        </h1>
    </div>

    <div class="overview-sidemenu">
        <p class="text-n heading">Orders</p>
        
        <h1 class="text-b list-item <?= $active_menu == 'orders' ? 'selected' : '' ?>">
            <a href="<?= base_url('my-account/orders') ?>" class="text-decoration-none cretzo-link">Orders & Returns</a>
        </h1>
    </div>

    <div class="overview-sidemenu">
        <p class="text-n heading">CREDITS</p>
        <h1 class="text-b list-item <?= $active_menu == 'wallet' ? 'selected' : '' ?>">
            <a href="<?= base_url('my-account/wallet') ?>" class="text-decoration-none cretzo-link">Wallet</a>
        </h1>
        <!-- <h1 class="text-b list-item">Coupons</h1> -->
    </div>

    <div class="overview-sidemenu">
        <p class="text-n heading">ACCOUNT</p>
        <h1 class="text-b list-item <?= $active_menu == 'profile' ? 'selected' : '' ?>">
            <a href="<?= base_url('my-account/profile') ?>" class="text-decoration-none cretzo-link">Profile</a>
        </h1>
        <!-- <h1 class="text-b list-item">Saved Cards</h1> -->
        <!-- <h1 class="text-b list-item">Saved UPI</h1> -->
        <!-- <h1 class="text-b list-item">Saved Wallet</h1> -->
        <h1 class="text-b list-item <?= $active_menu == 'address' ? 'selected' : '' ?>">
            <a href="<?= base_url('my-account/manage-address') ?>" class="text-decoration-none cretzo-link">Addresses</a>
        </h1>
        <h1 class="text-b list-item <?= $active_menu == 'favorites' ? 'selected' : '' ?>">
            <a href="<?= base_url('my-account/favorites') ?>" class="text-decoration-none cretzo-link">Wishlist</a>
        </h1>
        <!-- <h1 class="text-b list-item">Delete Account</h1> -->
    </div>

    <div class="overview-sidemenu">
        <p class="text-n heading">LEGAL</p>
        <h1 class="text-b list-item">
            <a href="<?= base_url('terms-and-conditions') ?>" class="text-decoration-none cretzo-link">Terms of Use</a>
        </h1>
        <h1 class="text-b list-item">
            <a href="<?= base_url('privacy-policy') ?>" class="text-decoration-none cretzo-link">Privacy Policy</a>
        </h1>
        <h1 class="text-b list-item <?= $active_menu == 'chat' ? 'selected' : '' ?>">
            <a href="<?= base_url('my-account/chat') ?>" class="text-decoration-none cretzo-link">Chat with us</a>
        </h1>

        <?php
        /* The support-ticket system had no entry point on the website at all - it existed only
         * in the admin panel and the mobile app, so a web customer could not raise or read a
         * ticket. */
        ?>
        <h1 class="text-b list-item <?= $active_menu == 'support' ? 'selected' : '' ?>">
            <a href="<?= base_url('my-account/support') ?>" class="text-decoration-none cretzo-link">Support tickets</a>
        </h1>

        <?php
        /* Was hardcoded to https://cretzo.com/login/logout, so logging out of any non-production
         * environment (local, staging, or a site served on a different domain) bounced the user
         * to production instead of ending their session here. */
        ?>
        <a class="rounded text-decoration-none d-flex gap-1 align-items-center mt-4" id="logout_btn" href="<?= base_url('login/logout') ?>">
            <div>
                <i class="uil uil-signout fs-22" style="color: red;"></i>
            </div>
            <p class="mb-0" style="color: red;">Logout</p>
        </a>
    </div>
</div>