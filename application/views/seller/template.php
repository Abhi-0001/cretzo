<!DOCTYPE html>
<html>
<?php $this->load->view('seller/include-head.php'); ?>

<!-- <div id="loading">
    <div class="lds-ring">
        <div></div>
    </div>
</div> -->

<body class="hold-transition sidebar-mini layout-fixed ">
    <div class=" wrapper ">
        <?php $this->load->view('seller/include-navbar.php'); ?>
        <?php $this->load->view('seller/include-sidebar.php'); ?>
        <?php
        $resolved_main_page = isset($main_page) && trim((string) $main_page) !== '' ? $main_page : FORMS . 'home';
        $resolved_view_path = APPPATH . 'views/seller/pages/' . $resolved_main_page . '.php';

        if (file_exists($resolved_view_path)) {
            $this->load->view('seller/pages/' . $resolved_main_page);
        } else {
            $this->load->view('seller/pages/' . FORMS . 'home');
        }
        ?>
        <?php $this->load->view('seller/include-footer.php'); ?>
    </div>
    <?php $this->load->view('seller/include-script.php'); ?>
</body>

</html> 