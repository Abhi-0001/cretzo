<?php
/*
 * PERFORMANCE - libraries removed from the storefront (Phase 2).
 *
 * These nine files were loaded on EVERY buyer-facing page and not one of them was
 * referenced by any code that actually runs on the storefront. Verified by grepping
 * every live view under views/front-end/cretzo plus every script this file loads
 * (plugins.js, theme.js, custom.js, cretzo-fixes.js, js/cretzo/*.js, js/checkout.js)
 * for each library's own API - not just its filename:
 *
 *   dropzone.js (149 KB)        - admin media uploads. No .dropzone anywhere here.
 *   Markdown.Converter.js (73 KB)
 *   Markdown.Editor.js    (85 KB)
 *   Markdown.Sanitizer.js  (3 KB) - the admin pagedown editor. No wmd- element exists.
 *   stisla.js (14 KB)           - the ADMIN theme's own script, on the shop front.
 *   perfect-scrollbar.min.js    - no PerfectScrollbar call, no .ps-container markup.
 *   modernizr-custom.js         - no Modernizr reference.
 *   bootstrap-tabs-x.min.js     - no tabsX call, no .nav-tabs-x markup.
 *   darkmode-min.js             - the theme switcher removed in f507e4d.
 *
 * The only matches for Modernizr / tabsX / ezPlus were inside eshop-bundle-js.js,
 * which this theme does not load at all. Their two stylesheets
 * (perfect-scrollbar.css, bootstrap-tabs-x.min.css) went with them from
 * include-css.php - no markup uses their classes either.
 *
 * Together: 358 KB and 11 render-blocking requests off every single page.
 *
 * Kept deliberately, because they ARE used and the greps prove it:
 *   jquery.blockUI.js - custom.js:166 calls .unblock() in the OTP sign-in flow
 *   select2, intlTelInput, jssocials, lightbox, iziModal, swiper, star-rating
 */
?>
<!-- plugins -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/plugins.js') ?>"></script>
<?php // jQuery is already loaded from include-css.php in the <head>, immediately before
      // assets/csrf-guard.js hooks it. Loading it a SECOND time here replaced window.jQuery
      // with a fresh object that carried no CSRF prefilter, so every jQuery POST fired by a
      // page script (e.g. My Account > Notifications "Mark all as read") was rejected with a
      // 403 "The action you have requested is not allowed". Do not re-add it. ?>
<!-- theme -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/theme.js') ?>"></script>
<?php /*
 * theme.min.js was loaded here as well as at line ~38 below, so it was fetched,
 * parsed and executed twice on every page.
 *
 * Despite the name it is NOT a minified theme.js (that file is 33 KB of the theme's
 * own component bootstrap; this one is 870 bytes). It is the Krajee SVG theme
 * configuration for bootstrap-star-rating, and its own header says it "must be
 * loaded after 'star-rating.js'". Here it ran BEFORE star-rating.min.js, so this
 * copy could never have been the one doing the work - the later one is. Removed.
 */ ?>

<!-- IziModal -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/iziModal.min.js') ?>"></script>
<!-- Popper -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/popper.min.js') ?>"></script>
<!-- Bootstrap -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/bootstrap.min.js') ?>"></script>
<!-- Swiper JS -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/swiper-bundle.min.js') ?>"></script>
<!-- Select -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/select2.full.min.js') ?>"></script>

<!-- ElevateZoom -->
<!-- <script src="<?= add_ver(THEME_ASSETS_URL . 'js/jquery.ez-plus.js') ?>"></script> -->
<!-- <script src="<? //= add_ver(THEME_ASSETS_URL . 'js/jquery.ez-plus.js') 
                ?>"></script> -->

<!-- Bootstrap Table - four pages only, see the note in include-css.php -->
<?php
/* include-css.php runs first (template.php loads it in the <head>) and sets this.
 * Recomputed defensively for the pages that pull this file in directly. */
$storefront_needs_bootstrap_table = isset($storefront_needs_bootstrap_table)
    ? $storefront_needs_bootstrap_table
    : in_array(isset($main_page) ? $main_page : '', ['transactions', 'wallet', 'address', 'checkout'], true);
if ($storefront_needs_bootstrap_table) { ?>
    <script src="<?= add_ver(THEME_ASSETS_URL . 'js/bootstrap-table.min.js') ?>"></script>
<?php } ?>
<!-- blockUI -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/jquery.blockUI.js') ?>"></script>
<!-- Sweeta Alert 2 -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/sweetalert2.min.js') ?>"></script>
<!-- Star rating -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/star-rating.min.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/theme.min.js') ?>"></script>
<!-- Lazy-Load.js -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/lazyload.min.js') ?>"></script>

<!-- jsSocial -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/jquery.jssocials.min.js') ?>"></script>



<!-- intlTelInput -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/intlTelInput.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/lightbox.js') ?>"></script>



<!-- Firebase.js -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/firebase-app.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/firebase-auth.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/firebase-firestore.js') ?>"></script>
<!-- <script src="<?= add_ver(THEME_ASSETS_URL . 'js/firebase-messaging.js') ?>"></script> -->
<script src="<?= add_ver(base_url('firebase-config.js')) ?>"></script>

<!-- Custom -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/custom.js') ?>"></script>

<!-- ScrollMagic and TweenMax (optional for smooth animations) -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/scroll_magic/ScrollMagic.min.js') ?>"></script>
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.7/ScrollMagic.min.js"></script> -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.2/gsap.min.js"></script> -->

<!-- Range Slider -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/range-slider/range-slider.min.js') ?>"></script>

<!-- for Cretzo theme -->
<?php
/* Same as include-css.php: pages/floating_chat.php loads this file without going through
 * template.php, so neither $is_rtl nor $main_page is set on that request. */
$is_rtl = isset($is_rtl) ? $is_rtl : is_rtl_language();
$main_page = isset($main_page) ? $main_page : '';
$path = ($is_rtl == 1) ? 'rtl/' : "";
?>

<script src="<?= add_ver(THEME_ASSETS_URL . 'js/' . $path . THEME . '/' . $path . 'cretzo.js') ?>"></script>

<script src="<?= add_ver(THEME_ASSETS_URL . 'js/' . $path . THEME . '/' . $path . 'header-footer.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/' . $path . THEME . '/' . $path . 'navbar.js') ?>"></script>
<?php
/*
 * My Account shared behaviour: the popup controller (window.CzAccount) plus the
 * small field behaviours the account views declare with data- attributes.
 * BEFORE the per-page script below, because address.js, orders.js, wallet.js and
 * profile.js all call CzAccount at DOM-ready.
 *
 * include-css.php runs first (template.php loads it in the <head>) and sets this;
 * recomputed defensively for pages that pull this file in directly.
 */
$storefront_is_account_page = isset($storefront_is_account_page)
    ? $storefront_is_account_page
    : (strpos(strtolower(current_url()), 'my-account') !== false
        || strpos(strtolower(current_url()), 'my_account') !== false
        || $main_page === 'contact-us');
if ($storefront_is_account_page) { ?>
    <script src="<?= add_ver(THEME_ASSETS_URL . 'js/' . $path . THEME . '/' . $path . 'account-suite.js') ?>"></script>
<?php }

/* The four policy documents share one script - see the matching block in
 * include-css.php. Everything it does is progressive enhancement; the contents
 * list and its anchors are built server-side and work without it. */
$storefront_is_legal_page = isset($storefront_is_legal_page)
    ? $storefront_is_legal_page
    : in_array($main_page, ['terms-and-conditions', 'privacy-policy', 'return-policy', 'shipping-policy'], true);
if ($storefront_is_legal_page) { ?>
    <script src="<?= add_ver(THEME_ASSETS_URL . 'js/' . $path . THEME . '/' . $path . 'legal-page.js') ?>"></script>
<?php }

/*
 * Per-page script, named after $main_page. This was emitted unconditionally, but only
 * 12 of the theme's pages actually have a matching file - and because this app sets
 * $route['404_override'] = 'error_404', a missing .js does NOT 404: Apache/CI answer with
 * the themed "Page Not Found" HTML page at HTTP 200 and content-type text/html. The
 * browser then tries to parse that HTML as JavaScript and throws
 * "Uncaught SyntaxError: Unexpected token '<'" on every such page (e.g. the floating-chat
 * iframe, whose $main_page is 'floating_chat'). Emit the tag only when the file exists.
 */
$page_script_rel = 'assets/front_end/' . THEME . '/js/' . $path . THEME . '/' . $path . $main_page . '.js';
if ($main_page !== '' && is_file(FCPATH . $page_script_rel)) { ?>
    <script src="<?= add_ver(THEME_ASSETS_URL . 'js/' . $path . THEME . '/' . $path . $main_page . '.js') ?>"></script>
<?php } ?>
<script src="<?= add_ver(base_url('assets/front_end/cretzo/js/cretzo-fixes.js')) ?>"></script>
<?php
/* Referral QR: the generator and its renderer, on the one storefront page that
 * draws a code. Vendored rather than loaded from a CDN, and rendered in the
 * browser rather than by an image service, so a user's referral code never
 * leaves their machine to become a picture. */
if (isset($main_page) && $main_page === 'refer-and-earn') { ?>
    <script src="<?= add_ver(base_url('assets/vendor/qrcode.min.js')) ?>"></script>
    <script src="<?= add_ver(base_url('assets/referral-qr.js')) ?>"></script>
<?php } ?>
<!-- <script src="<?//= add_ver(base_url('assets/front_end/classic/js/custom.js')) ?>"></script> -->
<?php if ($this->session->flashdata('message')) { ?>
    <script>
        Toast.fire({
            icon: '<?= $this->session->flashdata('message_type'); ?>',
            title: "<?= $this->session->flashdata('message'); ?>"
        });
    </script>
<?php } ?>