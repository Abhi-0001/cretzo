<?php
// No version pill in the header: get_current_version() returns MAX(version) from the
// `updates` table - the build the admin updater last applied. It is available on the
// System Update screen, which is where it is actionable; in the header it was just a
// number nobody acts on. (Same reasoning as the seller header.)
$is_admin_view   = $this->ion_auth->is_admin();
$navbar_user     = $this->ion_auth->user()->row();
$navbar_name     = ucfirst($navbar_user->username);
$navbar_initial  = strtoupper(mb_substr(trim($navbar_name) !== '' ? $navbar_name : 'A', 0, 1));
$navbar_base     = $is_admin_view ? 'admin/home' : 'delivery_boy/home';
$is_demo_mode    = (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0);

// Unread count only. The list itself is loaded by custom.js when the panel opens, so the
// three-row fetch_details('system_notification', ...) that used to sit here was a query run
// on every admin page whose result was never printed.
$count_noti = fetch_details('system_notification',  ["read_by" => 0],  'count(id) as total');
$noti_total = isset($count_noti[0]['total']) ? (int) $count_noti[0]['total'] : 0;
?>
<nav class="main-header navbar navbar-expand czp-navbar">
    <!-- Left navbar links -->
    <ul class="navbar-nav align-items-center">
        <li class="nav-item">
            <a class="nav-link czp-nav-icon" data-widget="pushmenu" href="#" role="button" title="Toggle menu">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <?php if ($is_demo_mode) { ?>
            <li class="nav-item d-none d-sm-block">
                <span class="czp-demo-pill">Demo mode</span>
            </li>
        <?php } ?>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto align-items-center">
        <?php
        /*
         * Language picker.
         *
         * #google_translate_element is still the real control - googleTranslateElementInit()
         * in custom.js mounts Google's widget into it and only Google's own <select> can
         * actually switch the page language. But that select is a NATIVE one: its popup is
         * drawn by the OS, so it cannot be styled, and Google feeds it ~250 unsorted
         * languages - an unusable wall of names in a header this size.
         *
         * So the widget is kept in the DOM (hidden) and driven from the Bootstrap dropdown
         * below, which custom.js fills from that select's own <option> list. Nothing is
         * hardcoded here: whatever languages Google offers are what appear, plus a search
         * box to get to one in a couple of keystrokes.
         */
        ?>
        <li class="nav-item dropdown czp-lang d-none d-md-block">
            <a class="nav-link czp-lang-toggle" href="#" id="czp-lang-toggle" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false" title="Change language">
                <i class="fas fa-globe"></i>
                <span class="czp-lang-current" id="czp-lang-current">English</span>
                <i class="fas fa-chevron-down czp-lang-caret"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right czp-lang-menu" aria-labelledby="czp-lang-toggle">
                <div class="czp-lang-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="czp-lang-search" class="form-control" placeholder="Search language"
                           autocomplete="off" aria-label="Search language">
                </div>
                <div class="czp-lang-list" id="czp-lang-list">
                    <div class="czp-lang-empty">Loading languages&hellip;</div>
                </div>
            </div>
            <?php // Hidden, not removed: this is what actually performs the translation. ?>
            <div id="google_translate_element" class="czp-translate-host"></div>
        </li>

        <?php if ($is_demo_mode) { ?>
            <li class="nav-item">
                <a class="nav-link czp-nav-icon" data-widget="control-sidebar" data-slide="true" href="#" role="button" title="Panel options">
                    <i class="fas fa-sliders-h"></i>
                </a>
            </li>
        <?php } ?>

        <?php
        /*
         * The bell and its panel have to live inside ONE element carrying .dropdown, and the
         * panel has to be a child of the toggle's parent - that is how Bootstrap finds the menu
         * belonging to a [data-toggle="dropdown"]. Previously the toggle sat in its own
         * <div id="refresh_notification"> with the panel as a SIBLING of that div, so Bootstrap
         * could never manage this dropdown: it did not close on an outside click or on Escape,
         * and custom.js had to show/hide it by hand. The panel is now where Bootstrap expects
         * it, so all of that behaviour comes from the framework.
         *
         * #refresh_notification, #notification_count, #list and .order_notification are all
         * addressed by custom.js (30s count refresh, show.bs.dropdown loader, close button) -
         * keep those hooks if this markup is restyled again.
         */
        ?>
        <li class="nav-item dropdown" id="refresh_notification">
            <a href="javascript:void(0);" id="notification_count" data-toggle="dropdown"
               class="nav-link czp-nav-icon position-relative notification-toggle" title="Notifications">
                <i class="far fa-bell"></i>
                <span class="badge czp-nav-badge order_notification"
                      style="<?= $noti_total > 0 ? '' : 'display:none;' ?>"><?= $noti_total > 99 ? '99+' : $noti_total ?></span>
            </a>
            <div id="list" class="dropdown-menu dropdown-menu-lg dropdown-menu-right czp-notif-dropdown"></div>
        </li>

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
                        <small><?= $is_admin_view ? 'Administrator' : 'Delivery partner' ?></small>
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
