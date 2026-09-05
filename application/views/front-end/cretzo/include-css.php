<?php
/*
 * PERFORMANCE - connection warm-up hints.
 *
 * This page pulls render-blocking CSS and JS from three third-party origins:
 * cdnjs (font-awesome), jsdelivr (the star-rating theme) and, in the footer,
 * both again for toastr and sweetalert2. Each one costs a fresh DNS lookup, TCP
 * handshake and TLS negotiation before a single byte of the asset arrives -
 * typically 100-300ms on a mobile connection, serialised behind the HTML parse
 * that discovers the tag.
 *
 * preconnect starts that handshake as soon as the head is parsed, in parallel
 * with everything else, so the connection is already open when the tag is
 * reached. It is a pure hint: it adds no script, changes no styling, and if the
 * browser ignores it nothing behaves differently.
 *
 * Razorpay is included because checkout loads its script, and warming that
 * connection early shortens the pay flow. `crossorigin` is required on the
 * font/asset origins because those are fetched in CORS mode - without it the
 * browser opens a second, unshared connection and the hint is wasted.
 */
?>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
<link rel="dns-prefetch" href="https://checkout.razorpay.com">

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
<?php /* bootstrap-tabs-x.min.css removed with its script - see the note at the top of
          include-script.php. Nothing on the storefront uses .nav-tabs-x / .tab-content-x. */ ?>
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
<?php
/*
 * PERFORMANCE: this pulled the Krajee SVG star-rating theme (v4.0.7) from jsdelivr,
 * and the very next line loads the SAME theme from disk (css/theme.min.css is
 * "Krajee SVG Theme styling for bootstrap-star-rating", v4.1.2). The local, newer copy
 * wins by cascade order, so the CDN request was a render-blocking round-trip to a third
 * party for a stylesheet that was immediately overridden.
 *
 * With this and the duplicate sweetalert2 in footer.php gone, nothing on the storefront
 * loads from jsdelivr any more, so its preconnect hints go too.
 */
?>
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/'. $path .'theme.min.css') ?>">
<?php
/*
 * PERFORMANCE: moment.js (52 KB) + daterangepicker (67 KB JS, 7 KB CSS) were global.
 * The only code that touches either is assets/front_end/cretzo/js/checkout.js, which
 * binds apply/cancel.daterangepicker on #datepicker and calls moment() for the
 * delivery-slot times - and that file is loaded by pages/checkout.php alone.
 * $storefront_needs_daterangepicker is reused by include-script.php below.
 */
$storefront_needs_daterangepicker = ($main_page === 'checkout');
if ($storefront_needs_daterangepicker) { ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/daterangepicker.css') ?>">
<?php } ?>

<?php
/*
 * PERFORMANCE: bootstrap-table (139 KB of JS + 10 KB of CSS) was loaded on every
 * storefront page. Only four pages can actually use it:
 *   transactions, wallet - they carry the data-toggle="table" markup
 *   address, checkout    - custom.js's add/edit-address handlers call
 *                          $("#address_list_table").bootstrapTable("refresh") in their
 *                          AJAX callbacks. That selector matches nothing in this theme,
 *                          but an undefined .bootstrapTable would still throw a
 *                          TypeError and abort the rest of the callback, so the library
 *                          has to be present wherever those forms are.
 * ($("#send_bank_receipt_form"), the other bootstrapTable caller in custom.js, has no
 *  markup anywhere in this theme, so that handler never binds.)
 *
 * $storefront_needs_bootstrap_table is reused by include-script.php for the matching
 * <script> tag - keep the two in step.
 */
$storefront_needs_bootstrap_table = in_array($main_page, ['transactions', 'wallet', 'address', 'checkout'], true);
if ($storefront_needs_bootstrap_table) { ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/bootstrap-table.min.css') ?>">
<?php } ?>
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/lightbox.css') ?>">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<?php /* perfect-scrollbar.css removed with its script - no .ps-container markup exists. */ ?>

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
<?php /*
 * theme.min.css is already linked above, as 'css/' . $path . 'theme.min.css'. In
 * LTR $path is "", so this was the identical file downloaded and parsed a second
 * time; in RTL it was worse than redundant, because this copy hardcodes the LTR
 * path and so re-applied LTR rules over the RTL sheet. The $path-aware link above
 * is the correct one, so this duplicate is removed.
 */ ?>



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
<!-- Date Range Picker - checkout only, see note above -->
<?php if ($storefront_needs_daterangepicker) { ?>
    <script src="<?= add_ver(THEME_ASSETS_URL . 'js/moment.min.js') ?>"></script>
    <script src="<?= add_ver(THEME_ASSETS_URL . 'js/daterangepicker.js') ?>"></script>
<?php } ?>
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



<?php if (isset($main_page) && $main_page === 'refer-and-earn') { ?>
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/referral-qr.css')) ?>">
<?php } ?>
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

    /*
     * Every page under /my-account, dashboard included. Reused twice below (for
     * account-suite.css) and again by include-script.php for account-suite.js -
     * keep the three in step.
     */
    $storefront_is_account_page = (strpos($currentUrl, 'my-account') !== false
        || strpos($currentUrl, 'my_account') !== false
        /* Contact Us is built on the account design system and renders inside the
         * account shell for a signed-in customer, so it needs the same sheet -
         * and the same script, for the logout confirm in that shell. */
        || $main_page === 'contact-us');

    /*
     * my-account.css / my-account-override.css style the OLD `.overview-*`
     * markup. The account views were rewritten onto the `czap-` design system
     * (account-suite.css) and no longer render those classes, so these two
     * sheets now match nothing - they are kept only because pages outside
     * /my-account that reuse `.accounts-container` would otherwise change, and
     * they are harmless where they load. account-suite.css is emitted AFTER the
     * per-page stylesheets further down, so it always wins.
     */
    if ($storefront_is_account_page && $main_page != 'dashboard') {
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

<?php
/*
 * The rebuilt site footer (`czfoot`). Emitted after header-footer.css - which
 * still holds the OLD .footer-* rules footer.php no longer renders - so this
 * sheet wins without needing !important. It is on every storefront page, like
 * the footer itself.
 */
?>
<link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/' . $path . THEME . '/' . $path . 'site-footer.css') ?>">

<?php
/*
 * My Account design system. Emitted LAST of the account stylesheets on purpose:
 * the per-page sheets above (orders.css, address.css, wallet-override.css, ...)
 * predate it, and although its selectors are all `czap-`-prefixed and so cannot
 * collide, loading it here means a future rule in it wins without needing
 * !important. Covers the dashboard too, which the my-account block above
 * deliberately skips.
 */
if (!empty($storefront_is_account_page)) { ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/' . $path . THEME . '/' . $path . 'account-suite.css') ?>">
<?php }

/*
 * The four policy documents (Terms, Privacy, Returns, Shipping) share one
 * layout and one stylesheet. Emitted here rather than relying on the per-page
 * convention above, because that names the file after $main_page and these are
 * four different pages using the same sheet.
 * $storefront_is_legal_page is reused by include-script.php - keep them in step.
 */
$storefront_is_legal_page = in_array($main_page, [
    'terms-and-conditions',
    'privacy-policy',
    'return-policy',
    'shipping-policy',
], true);
if ($storefront_is_legal_page) { ?>
    <link rel="stylesheet" href="<?= add_ver(THEME_ASSETS_URL . 'css/' . $path . THEME . '/' . $path . 'legal-page.css') ?>">
<?php } ?>


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