<!DOCTYPE html>
<html>
<?php $this->load->view('admin/include-head.php'); ?>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/admin/css/admin-auth.css') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/css/admin-auth.css') ?>">

<body class="hold-transition login-page bg-admin">
    <?php $this->load->view('admin/pages/' . $main_page); ?>
    <!-- Footer -->
    <?php $this->load->view('admin/include-script.php'); ?>
</body>

</html>
