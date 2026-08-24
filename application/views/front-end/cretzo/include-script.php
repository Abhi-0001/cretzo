<!-- plugins -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/plugins.js') ?>"></script>
<?php // jQuery is already loaded from include-css.php in the <head>, immediately before
      // assets/csrf-guard.js hooks it. Loading it a SECOND time here replaced window.jQuery
      // with a fresh object that carried no CSRF prefilter, so every jQuery POST fired by a
      // page script (e.g. My Account > Notifications "Mark all as read") was rejected with a
      // 403 "The action you have requested is not allowed". Do not re-add it. ?>
<!-- theme -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/theme.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/theme.min.js') ?>"></script>

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
<!-- Bootstrap Tabs -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/bootstrap-tabs-x.min.js') ?>"></script>

<!-- ElevateZoom -->
<!-- <script src="<?= add_ver(THEME_ASSETS_URL . 'js/jquery.ez-plus.js') ?>"></script> -->
<!-- <script src="<? //= add_ver(THEME_ASSETS_URL . 'js/jquery.ez-plus.js') 
                ?>"></script> -->

<!-- Bootstrap Table -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/bootstrap-table.min.js') ?>"></script>
<!-- blockUI -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/jquery.blockUI.js') ?>"></script>
<!-- Sweeta Alert 2 -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/sweetalert2.min.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/darkmode-min.js') ?>"></script>
<!-- Star rating -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/star-rating.min.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/theme.min.js') ?>"></script>
<!-- Modernizr-custom.js -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/modernizr-custom.js') ?>"></script>
<!-- Lazy-Load.js -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/lazyload.min.js') ?>"></script>

<!-- jsSocial -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/jquery.jssocials.min.js') ?>"></script>
<!-- MDB perfect scrollbar -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/perfect-scrollbar.min.js') ?>"></script>



<!-- intlTelInput -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/intlTelInput.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/lightbox.js') ?>"></script>
<!-- Dropzone -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/dropzone.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/stisla.js') ?>"></script>

<!-- Markdown -->
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/Markdown.Converter.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/Markdown.Sanitizer.js') ?>"></script>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/Markdown.Editor.js') ?>"></script>


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
<!-- <script src="<?//= add_ver(base_url('assets/front_end/classic/js/custom.js')) ?>"></script> -->
<?php if ($this->session->flashdata('message')) { ?>
    <script>
        Toast.fire({
            icon: '<?= $this->session->flashdata('message_type'); ?>',
            title: "<?= $this->session->flashdata('message'); ?>"
        });
    </script>
<?php } ?>