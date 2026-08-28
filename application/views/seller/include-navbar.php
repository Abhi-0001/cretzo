<?php
// No version pill here: get_current_version() returns MAX(version) from the
// `updates` table - the eKart script build the admin updater last applied.
// It is a platform-maintenance number, meaningless to a seller, and it was
// the only thing sitting next to the hamburger. Admin keeps it (admin/
// include-navbar.php), where the updater screen makes it actionable.
$is_admin_view   = $this->ion_auth->is_admin();
$navbar_user     = $this->ion_auth->user()->row();
$navbar_name     = ucfirst($navbar_user->username);
$navbar_initial  = strtoupper(mb_substr(trim($navbar_name) !== '' ? $navbar_name : 'S', 0, 1));
$navbar_base     = $is_admin_view ? 'admin/home' : 'seller/home';
?>
<nav class="main-header navbar navbar-expand czp-navbar">
    <!-- Left navbar links -->
    <ul class="navbar-nav align-items-center">
        <li class="nav-item">
            <a class="nav-link czp-nav-icon" data-widget="pushmenu" href="#" role="button" title="Toggle menu">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto align-items-center">
        <?php
        if (!$is_admin_view) {
            // The seller panel had no notification surface at all: order, settlement and
            // ticket notifications were push-only, and push is unconfigured here.
            $seller_unread = user_unread_notification_count($this->session->userdata('user_id'), 'seller');
        ?>
            <li class="nav-item">
                <a class="nav-link czp-nav-icon position-relative" href="<?= base_url('seller/notifications') ?>" title="Notifications">
                    <i class="far fa-bell"></i>
                    <span class="badge czp-nav-badge" id="seller-notif-badge"
                          style="<?= $seller_unread > 0 ? '' : 'display:none;' ?>"><?= $seller_unread > 99 ? '99+' : $seller_unread ?></span>
                </a>
            </li>
            <?php // No Support link here on purpose: the sidebar's "Support Tickets" entry is the
                  // one way in, so the header does not offer a second route to the same page. ?>
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
        //
        // The control-sidebar toggle (fa-th-large) that used to sit here was only rendered
        // when ALLOW_MODIFICATION == 0, and the aside it opens (include-script.php) has no
        // content - so it was a dead button in demo mode and invisible otherwise.
        ?>
        <li class="nav-item dropdown czp-user-menu">
            <a class="nav-link czp-user-toggle" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false">
                <span class="czp-avatar"><?= $navbar_initial ?></span>
                <span class="czp-user-name d-none d-md-inline"><?= $navbar_name ?></span>
                <i class="fas fa-chevron-down czp-user-caret"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right czp-user-dropdown">
                <div class="czp-user-head">
                    <span class="czp-avatar czp-avatar-lg"><?= $navbar_initial ?></span>
                    <div class="czp-user-head-text">
                        <strong><?= $navbar_name ?></strong>
                        <small><?= $is_admin_view ? 'Administrator' : 'Seller account' ?></small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="<?= base_url($navbar_base . '/profile') ?>" class="dropdown-item czp-user-item">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="<?= base_url($navbar_base . '/logout') ?>" class="dropdown-item czp-user-item czp-user-item-danger">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </a>
            </div>
        </li>
    </ul>
</nav>
