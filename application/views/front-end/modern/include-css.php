<!-- <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"> -->


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
<!-- Select2 -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/select2-bootstrap4.min.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/select2.min.css' ?>">
<!-- jssocials -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/jquery.jssocials-theme-flat.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/jquery.jssocials.css' ?>">
<!-- Star rating CSS -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/'. $path .'star-rating.min.css' ?>">
<link href="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-star-rating@4.0.7/themes/krajee-svg/theme.css" media="all" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/'. $path .'theme.min.css' ?>">
<!-- daterangepiker -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/daterangepicker.css' ?>">

<!-- Bootstrap -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/bootstrap-table.min.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/lightbox.css' ?>">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- MDB perfect scrollbar -->
<link href="<?= THEME_ASSETS_URL . 'css/perfect-scrollbar.css' ?>" rel="stylesheet" />

<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/style.css' ?>">

<!-- chat -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/components.css' ?>">

<link rel="stylesheet" href="<?= THEME_ASSETS_URL .  'css/'. $path .'products.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path .'custom.css' ?>">


<?php if (ALLOW_MODIFICATION == 0) { ?>
    <link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/colors/orange.css' ?>" id="color-switcher">
<?php } else { ?>
    <?php
    $settings = get_settings('web_settings', true);
    $modern_theme_color = (isset($settings['modern_theme_color']) && !empty($settings['modern_theme_color'])) ? $settings['modern_theme_color'] : 'orange'; ?>
    <link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/colors/' . $modern_theme_color . '.css' ?>">

<?php } ?>


<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/plugins.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL . 'css/theme.min.css' ?>">






<!-- Jquery -->
<script src="<?= THEME_ASSETS_URL . 'js/jquery.min.js' ?>"></script>
<?php // See admin/include-head.php - stamps the CSRF token onto every same-origin POST. ?>
<meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name() ?>">
<meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash() ?>">
<script src="<?= base_url('assets/csrf-guard.js') ?>"></script>
<!-- Date Range Picker -->
<script src="<?= THEME_ASSETS_URL . 'js/moment.min.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/daterangepicker.js' ?>"></script>
<script type="text/javascript">
    base_url = "<?= base_url() ?>";
    currency = "<?= isset($settings['currency'])? $settings['currency'] : '$' ?>";
    csrfName = "<?= $this->security->get_csrf_token_name() ?>";
    csrfHash = "<?= $this->security->get_csrf_hash() ?>";
</script>