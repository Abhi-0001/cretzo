<!-- IziModal -->
<script src="<?= THEME_ASSETS_URL . 'js/iziModal.min.js' ?>"></script>
<!-- Popper -->
<script src="<?= THEME_ASSETS_URL . 'js/popper.min.js' ?>"></script>
<!-- Bootstrap -->
<script src="<?= THEME_ASSETS_URL . 'js/bootstrap.min.js' ?>"></script>
<!-- Swiper JS -->
<script src="<?= THEME_ASSETS_URL . 'js/swiper-bundle.min.js' ?>"></script>
<!-- Select -->
<script src="<?= THEME_ASSETS_URL . 'js/select2.full.min.js' ?>"></script>
<!-- Bootstrap Tabs -->
<script src="<?= THEME_ASSETS_URL . 'js/bootstrap-tabs-x.min.js' ?>"></script>
<!-- ElevateZoom -->
<script src="<?= THEME_ASSETS_URL . 'js/jquery.ez-plus.js' ?>"></script>
<!-- Bootstrap Table -->
<script src="<?= THEME_ASSETS_URL . 'js/bootstrap-table.min.js' ?>"></script>
<!-- blockUI -->
<script src="<?= THEME_ASSETS_URL . 'js/jquery.blockUI.js' ?>"></script>
<!-- Sweeta Alert 2 -->
<script src="<?= THEME_ASSETS_URL . 'js/sweetalert2.min.js' ?>"></script>
<!-- slick-carusel -->
<script src="<?= THEME_ASSETS_URL . 'js/slick.min.js' ?>"></script>
<!-- Modernizr-custom.js -->
<script src="<?= THEME_ASSETS_URL . 'js/modernizr-custom.js' ?>"></script>
<!-- Lazy-Load.js -->
<script src="<?= THEME_ASSETS_URL . 'js/lazyload.min.js' ?>"></script>

<!-- daterangepicker -->
<script src="<?= THEME_ASSETS_URL . 'js/daterangepicker.js' ?>"></script>
<!-- intlTelInput -->
<script src="<?= THEME_ASSETS_URL . 'js/intlTelInput.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/lightbox.js' ?>"></script>
<!-- jssocials -->
<script src="<?= THEME_ASSETS_URL . 'js/jquery.jssocials.min.js' ?>"></script>
<!-- Dropzone -->
<script src="<?= THEME_ASSETS_URL . 'js/dropzone.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/stisla.js' ?>"></script>
<!-- Markdown -->
<script src="<?= THEME_ASSETS_URL . 'js/Markdown.Converter.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/Markdown.Sanitizer.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/Markdown.Editor.js' ?>"></script>


<!-- Firebase.js -->
<script src="<?= THEME_ASSETS_URL . 'js/firebase-app.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/firebase-auth.js' ?>"></script>
<script src="<?= THEME_ASSETS_URL . 'js/firebase-firestore.js' ?>"></script>
<!-- <script src="<?= THEME_ASSETS_URL . 'js/firebase-messaging.js' ?>"></script> -->
<script src="<?= base_url('firebase-config.js') ?>"></script>

<!-- Custom -->
<script src="<?= THEME_ASSETS_URL . 'js/custom.js' ?>"></script>
<?php if ($this->session->flashdata('message')) { ?>
    <script>
        Toast.fire({
            icon: '<?= $this->session->flashdata('message_type'); ?>',
            title: "<?= $this->session->flashdata('message'); ?>"
        });
    </script>
<?php } ?>

<!-- Dark mode -->
<?php if (current_url() != base_url("my-account/floating_chat")) { ?>
    <script src="<?= THEME_ASSETS_URL . 'js/darkmode-min.js' ?>"></script>
<?php } ?>