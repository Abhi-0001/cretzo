<!-- <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"> -->


<!-- Izimodal -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/iziModal.min.css') ?>" />
<!-- Favicon -->
<?php $favicon = get_settings('web_favicon');

/*
 * template.php hands $is_rtl and $main_page in, but this file is also loaded directly by
 * pages that render outside that template - pages/floating_chat.php - which supplies
 * neither. Both were read unguarded, so every floating-chat request warned about them.
 * Work $is_rtl out ourselves when it is missing, and treat a missing $main_page as
 * "no per-page stylesheet" rather than building a path with an empty filename in it.
 */
$is_rtl = isset($is_rtl) ? $is_rtl : is_rtl_language();
$main_page = isset($main_page) ? $main_page : '';

$path = ($is_rtl == 1) ? 'rtl/' : "";
?>
<link rel="icon" href="<?= base_url($favicon) ?>" type="image/gif" sizes="16x16">

<!-- intlTelInput -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/intlTelInput.css') ?>" />
<!-- Bootstrap -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/' . $path . 'bootstrap.min.css') ?>">
<!-- FontAwesome -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/all.min.css') ?>" />
<!-- Swiper css -->

<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/swiper-bundle.min.css') ?>" />
<!-- Bootstrap Tabs -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/bootstrap-tabs-x.min.css') ?>" />
<!-- Sweet Alert -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/sweetalert2.min.css') ?>">
<!-- Select2 -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/select2-bootstrap4.min.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/select2.min.css') ?>">
<!-- jssocials -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/jquery.jssocials-theme-flat.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/jquery.jssocials.css') ?>">
<!-- Star rating CSS -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/'. $path .'star-rating.min.css') ?>">
<link href="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-star-rating@4.0.7/themes/krajee-svg/theme.css" media="all" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/'. $path .'theme.min.css') ?>">
<!-- daterangepiker -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/daterangepicker.css') ?>">

<!-- Bootstrap -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/bootstrap-table.min.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/lightbox.css') ?>">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- MDB perfect scrollbar -->
<link href="<?= add_ver(THEME_ASSETS_URL . 'css/perfect-scrollbar.css') ?>" rel="stylesheet" />

<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/style.css') ?>">

<!-- chat -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/components.css') ?>">

<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL .  'css/'. $path .'products.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path .'custom.css') ?>">


<?php if (ALLOW_MODIFICATION == 0) { ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/colors/orange.css') ?>" id="color-switcher">
<?php } else { ?>
    <?php
    $settings = get_settings('web_settings', true);
    $modern_theme_color = (isset($settings['modern_theme_color']) && !empty($settings['modern_theme_color'])) ? $settings['modern_theme_color'] : 'orange'; ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/colors/' . $modern_theme_color . '.css') ?>">

<?php } ?>


<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/plugins.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/theme.min.css') ?>">



<!-- Jquery -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/jquery.min.js') ?>"></script>
<?php // See admin/include-head.php - stamps the CSRF token onto every same-origin POST. ?>
<meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name() ?>">
<meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash() ?>">
<script src="<?= add_ver(base_url('assets/csrf-guard.js')) ?>"></script>

<?php
// Firebase phone-OTP password reset. This site has no SMS gateway configured, so the
// storefront's "Forgot Password" modal could never deliver an OTP through the old
// server-side path. Loaded HERE, in the head, on purpose: it must bind its submit
// handlers before the theme bundle binds the legacy ones, so its
// stopImmediatePropagation() can suppress them.
$fb_auth = get_settings('authentication_settings', true);
$fb_conf = get_settings('firebase_settings', true);
if (!empty($fb_auth['authentication_method']) && $fb_auth['authentication_method'] === 'firebase' && !empty($fb_conf['apiKey'])) :
?>
    <?php // NO Firebase SDK tags here - the theme's include-script.php already loads its
          // auth bundle (@firebase/auth 0.15.1) and firebase-config.js, and firebase-app is
          // now loaded alongside them there. Adding 8.10.0 here put two major SDK versions
          // on the page and ran initializeApp() twice, which breaks the reCAPTCHA token. ?>
    <script>
        window.FIREBASE_RESET_CONFIG = {
            checkUrl: "<?= base_url('home/check_reset_account') ?>",
            resetUrl: "<?= base_url('home/reset_password_firebase') ?>",
            redirectUrl: "",
            recaptchaId: "recaptcha-password-reset",
            defaultDialCode: "+91"
        };
    </script>
    <script src="<?= add_ver(base_url('assets/firebase-password-reset.js')) ?>"></script>
<?php endif; ?>
<!-- Date Range Picker -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/moment.min.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/daterangepicker.js') ?>"></script>
<script type="text/javascript">
    base_url = "<?= base_url() ?>";
    currency = "<?= isset($settings['currency'])? $settings['currency'] : '$' ?>";
    csrfName = "<?= $this->security->get_csrf_token_name() ?>";
    csrfHash = "<?= $this->security->get_csrf_hash() ?>";
</script>

<!-- Range Slider -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . 'range-slider/' . $path . 'range-slider.min.css') ?>">

<!-- for Cretzo theme -->
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'cretzo-global.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'cretzo.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'cretzo-override.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'header-footer.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'signup.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'login.css') ?>">
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'navbar.css') ?>">



<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- Some stylesheets that might be required but we will load before the final main page stylesheets to prevent overrides -->

<!-- We need a common stylesheet for some my-account pages, so we include it before the main page -->
<?php
   // if((str_contains(strtolower(current_url()), 'my-account') || str_contains(strtolower(current_url()), 'my_account')) && $main_page != 'dashboard'){
?>
        <!-- <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'my-account.css') ?>">
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'my-account-override.css') ?>"> -->
<?php
   // }
?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- We need a common stylesheet for seller_details page, so we include it before the main page -->
<?php
   // if((str_contains(strtolower(current_url()), 'seller_details') || str_contains(strtolower(current_url()), 'seller-details'))){
?>
        <!-- <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'product-page.css') ?>">
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'product-page-override.css') ?>"> -->
<?php
   // }
?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->

<?php
    $currentUrl = strtolower(current_url());

    if (
        (strpos($currentUrl, 'my-account') !== false || strpos($currentUrl, 'my_account') !== false)
        && $main_page != 'dashboard'
    ){
?>
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'my-account.css') ?>">
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'my-account-override.css') ?>">
<?php
    }
?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- We need a common stylesheet for seller_details page, so we include it before the main page -->
<?php
    if (
        (strpos($currentUrl, 'seller_details') !== false || strpos($currentUrl, 'seller-details') !== false)
    ){
?>
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'product-page.css') ?>">
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'product-page-override.css') ?>">
<?php
    }
?>


<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- Finally include the main page's stylesheets at the end -->
<?php
/*
 * Same problem the per-page <script> tag in include-script.php already guards against: only
 * about half the theme's pages ship a matching stylesheet, and because $route['404_override']
 * is set, a missing .css does NOT 404 - Apache/CI answer with the themed "Page Not Found" HTML
 * at HTTP 200 and content-type text/html. So pages like notifications, transactions, profile,
 * chat and support each fired two pointless full-page requests on every load, each returning a
 * whole HTML document the browser then discarded. Emit each tag only when the file is there.
 */
$page_css_rel     = 'assets/front_end/' . THEME . '/css/' . $path . THEME . '/' . $path . $main_page . '.css';
$page_css_ovr_rel = 'assets/front_end/' . THEME . '/css/' . $path . THEME . '/' . $path . $main_page . '-override.css';
if ($main_page !== '' && is_file(FCPATH . $page_css_rel)) { ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . $main_page . '.css') ?>">
<?php }
if ($main_page !== '' && is_file(FCPATH . $page_css_ovr_rel)) { ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . $main_page . '-override.css') ?>">
<?php } ?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->
<link rel="stylesheet" href="<?= add_ver(base_url('assets/front_end/cretzo/css/cretzo-fixes.css')) ?>">
<link rel="stylesheet" href="<?= add_ver(base_url('assets/front_end/cretzo/css/mini-cart-compact.css')) ?>">


<!-- --------------------------------------------------------------------------------------------------------------------- -->
<!-- Some stylesheets might be required after loading the main page stylesheet for overriding certain styles -->

<!-- In case 'mainpage == checkout' page, we need to include 2 different stylesheets (checkout.css + address.css) -->
<?php
    if($main_page === "checkout"){
?>
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'checkout-address.css') ?>">
        <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL  .  'css/'. $path . THEME . '/' . $path . 'checkout-address-override.css') ?>">
<?php
    }
?>
<!-- --------------------------------------------------------------------------------------------------------------------- -->