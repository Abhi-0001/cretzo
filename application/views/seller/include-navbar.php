<?php $current_version = get_current_version(); ?>
<nav class="main-header navbar navbar-expand">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item my-auto">
            <span class="badge badge-success">v <?= (isset($current_version) && !empty($current_version)) ? $current_version : '1.0' ?></span>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <?php
        if (!$this->ion_auth->is_admin()) {
            // The seller panel had no notification surface at all: order, settlement and
            // ticket notifications were push-only, and push is unconfigured here.
            $seller_unread = user_unread_notification_count($this->session->userdata('user_id'), 'seller');
        ?>
            <li class="nav-item">
                <a class="nav-link position-relative" href="<?= base_url('seller/notifications') ?>" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="badge badge-danger navbar-badge" id="seller-notif-badge"
                          style="<?= $seller_unread > 0 ? '' : 'display:none;' ?>"><?= $seller_unread > 99 ? '99+' : $seller_unread ?></span>
                </a>
            </li>
            <?php // No Support link here on purpose: the sidebar's "Support Tickets" entry is the
                  // one way in, so the header does not offer a second route to the same page. ?>
        <?php } ?>
        <?php if (ALLOW_MODIFICATION == 0) { ?>
            <li class="nav-item">
                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                    <i class="fas fa-th-large"></i>
                </a>
            </li>
        <?php } ?>
        <?php
        // The Google Translate mount point and its loader script used to sit here and in
        // include-script.php, but the callback that fills them (googleTranslateElementInit)
        // only ever existed in the admin-only custom.js - so on seller pages this div stayed
        // empty and the third-party script was loaded for nothing. It first appeared as a
        // real dropdown when the product form started loading custom.js, and only on that
        // one page. Removed rather than left inert, so the seller panel looks the same on
        // every page. (To give sellers translation properly, put both back and load the
        // init on all seller pages, not just this one.)
        ?>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fa fa-user fa-2x"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <div class="dropdown-divider"></div>
                <div class="dropdown-divider">

                </div>
                <div class="dropdown-divider"></div>

                <?php if ($this->ion_auth->is_admin()) { ?>
                    <a href="#" class="dropdown-item">Welcome <b><?= ucfirst($this->ion_auth->user()->row()->username) ?> </b> ! </a>
                    <a href="<?= base_url('admin/home/profile') ?>" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="<?= base_url('admin/home/logout') ?>" class="dropdown-item">
                        <i class="fa fa-sign-out-alt mr-2"></i> Log Out
                    </a>
                <?php } else { ?>
                    <a href="#" class="dropdown-item">Welcome <b><?= ucfirst($this->ion_auth->user()->row()->username) ?> </b>! </a>
                    <a href="<?= base_url('seller/home/profile') ?>" class="dropdown-item"><i class="fas fa-user mr-2"></i> Profile </a>
                    <a href="<?= base_url('seller/home/logout') ?>" class="dropdown-item "><i class="fa fa-sign-out-alt mr-2"></i> Log Out </a>
                <?php } ?>
            </div>
        </li>
    </ul>
</nav>