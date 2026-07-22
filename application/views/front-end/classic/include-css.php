<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<!-- Izimodal -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/iziModal.min.css' ?>" />
<!-- Favicon -->
<?php $favicon = get_settings('web_favicon');

$path = ($is_rtl == 1) ? 'rtl/' : "";
?>
<link rel="icon" href="<?= base_url($favicon) ?>" type="image/gif" sizes="16x16">
<!-- intlTelInput -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/intlTelInput.css' ?>" />
<!-- Bootstrap -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/' . $path . 'bootstrap.min.css' ?>">
<!-- FontAwesome -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/all.min.css' ?>" />
<!-- Swiper css -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/swiper-bundle.min.css' ?>" />
<!-- Bootstrap Tabs -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/bootstrap-tabs-x.min.css' ?>" />
<!-- Sweet Alert -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/sweetalert2.min.css' ?>">
 <!-- Tinymce -->
    <script src="<?= THEME_ASSETS_URL . 'js/tinymce.min.js' ?>"></script>
<!-- Select2 -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/select2.min.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/select2-bootstrap4.min.css' ?>">
<!-- jssocials -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/jquery.jssocials-theme-flat.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/jquery.jssocials.css' ?>">
<!-- Star rating CSS -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/star-rating.min.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/theme.css' ?>">

<!-- chat -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/components.css' ?>">

<!-- Custom Stle css -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/' . $path . 'style.css' ?>" />
<!-- Custom Product css -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/' . $path . 'products.css' ?>" />
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/daterangepicker.css' ?>" />
<!-- Color CSS -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/colors/peach.css' ?>" id="color-switcher">
<!-- Bootstrap -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/bootstrap-table.min.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/lightbox.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/lightbox.css' ?>">

<!-- slick-carousel -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/slick.min.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/slick-theme.min.css' ?>">

<!-- Jquery -->
<script src="<?= THEME_ASSETS_URL . 'js/jquery.min.js' ?>"></script>
<!-- Date Range Picker -->
<script src="<?= THEME_ASSETS_URL . 'js/moment.min.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/daterangepicker.js' ?>"></script>
<!-- Star rating js -->
<script type="text/javascript" src="<?= THEME_ASSETS_URL . 'js/star-rating.js' ?>"></script>
<script type="text/javascript" src="<?= THEME_ASSETS_URL . 'js/theme.min.js' ?>"></script>
<script type="text/javascript">
    base_url = "<?= base_url() ?>";
    currency = "<?= $settings['currency'] ?>";
    csrfName = "<?= $this->security->get_csrf_token_name() ?>";
    csrfHash = "<?= $this->security->get_csrf_hash() ?>";
</script>
