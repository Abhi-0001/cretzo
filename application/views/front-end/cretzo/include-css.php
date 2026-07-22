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
<!-- Date Range Picker -->
<script src="<?= THEME_ASSETS_URL . 'js/moment.min.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/daterangepicker.js' ?>"></script>
<script type="text/javascript">
    base_url = "<?= base_url() ?>";
    currency = "<?= isset($settings['currency'])? $settings['currency'] : '$' ?>";
    csrfName = "<?= $this->security->get_csrf_token_name() ?>";
    csrfHash = "<?= $this->security->get_csrf_hash() ?>";
</script>

<!-- Range Slider -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . 'range-slider/' . $path . 'range-slider.min.css' ?>">

<!-- for Cretzo theme -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'cretzo-global.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'cretzo.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'cretzo-override.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'header-footer.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'signup.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'login.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'navbar.css' ?>">



<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- Some stylesheets that might be required but we will load before the final main page stylesheets to prevent overrides -->

<!-- We need a common stylesheet for some my-account pages, so we include it before the main page -->
<?php
    if((str_contains(strtolower(current_url()), 'my-account') || str_contains(strtolower(current_url()), 'my_account')) && $main_page != 'dashboard'){
?>
        <link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'my-account.css' ?>">
        <link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'my-account-override.css' ?>">
<?php
    }
?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- We need a common stylesheet for seller_details page, so we include it before the main page -->
<?php
    if((str_contains(strtolower(current_url()), 'seller_details') || str_contains(strtolower(current_url()), 'seller-details'))){
?>
        <link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'product-page.css' ?>">
        <link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'product-page-override.css' ?>">
<?php
    }
?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->



<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- Finally include the main page's stylesheets at the end -->
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . $main_page . '.css' ?>">
<link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . $main_page . '-override.css' ?>">
<!-- --------------------------------------------------------------------------------------------------------------------- -->



<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- Some stylesheets might be required after loading the main page stylesheet for overriding certain styles -->

<!-- In case 'mainpage == checkout' page, we need to include 2 different stylesheets (checkout.css + address.css) -->
<?php
    if($main_page === "checkout"){
?>
        <link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'checkout-address.css' ?>">
        <link rel="stylesheet" href="<?= THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'checkout-address-override.css' ?>">
<?php
    }
?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->