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
        <?php
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        ?>
            <li class="nav-item my-auto ml-2">
                <span class="badge badge-danger">Demo mode</span>
            </li>
        <?php } ?>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

        <!-- google translate  -->
        <div id="google_translate_element"></div>

        <?php if (ALLOW_MODIFICATION == 0) { ?>
            <li class="nav-item">
                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                    <i class="fas fa-th-large"></i>
                </a>
            </li>
        <?php } ?>

        <!-- start send admin notification  -->
        <?php
        $notifications = fetch_details('system_notification',  NULL,  '*',  '3', '0',  'read_by', 'ASC',  '',  '');
        $count_noti = fetch_details('system_notification',  ["read_by" => 0],  'count(id) as total');
        ?>

        <?php
        /*
         * The bell and its panel have to live inside ONE element carrying .dropdown, and the
         * panel has to be a child of the toggle's parent - that is how Bootstrap finds the menu
         * belonging to a [data-toggle="dropdown"]. Previously the toggle sat in its own
         * <div id="refresh_notification"> with the panel as a SIBLING of that div, so Bootstrap
         * could never manage this dropdown: it did not close on an outside click or on Escape,
         * and custom.js had to show/hide it by hand. The panel is now where Bootstrap expects
         * it, so all of that behaviour comes from the framework.
         */
        ?>
        <li class="nav-item dropdown" id="refresh_notification">
            <a href="javascript:void(0);" id="notification_count" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg">
                <i class="fas fa-bell fa-2x"></i>
                <span class="badge badge-danger navbar-badge order_notification mt-1"><?= $count_noti[0]['total'] ?></span>
            </a>
            <div id="list" class="dropdown-menu dropdown-menu-lg dropdown-menu-right"></div>
        </li>

        <!-- end send admin notification  -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fa fa-user fa-2x"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
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
                    <a href="<?= base_url('delivery_boy/home/profile') ?>" class="dropdown-item"><i class="fas fa-user mr-2"></i> Profile </a>
                    <a href="<?= base_url('delivery_boy/home/logout') ?>" class="dropdown-item "><i class="fa fa-sign-out-alt mr-2"></i> Log Out </a>
                <?php } ?>
            </div>
        </li>
    </ul>
</nav>