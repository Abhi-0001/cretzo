<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $title ?></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= base_url() . get_settings('favicon') ?>" type="image/gif" sizes="16x16">
    <!-- Bootstrap Switch -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/bootstrap-switch.min.css')) ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/all.min.css')) ?>">
    <!-- Ionicons -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/ionicons.min.css')) ?>">
    <!-- Tempusdominus Bbootstrap 4 -->
    <?php /* PERFORMANCE: tempusdominus is not used in the admin panel - no .datetimepicker()
              call and no tempusdominus markup in any admin view or in custom.js. Its 55 KB
              script went too; see the note in include-script.php. */ ?>
    <!-- iCheck -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/icheck-bootstrap.min.css')) ?>">
    <!-- Dropzone -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/dropzone.css')) ?>">
    <!-- JQVMap -->
    <?php /* PERFORMANCE: jqvmap is AdminLTE's demo "vector map of the USA" widget. Nothing
              in the admin panel references it. Removed with its two scripts (66 KB). */ ?>
    <!-- Ekko Lightbox -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/ekko-lightbox/ekko-lightbox.css')) ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/dist/css/adminlte.min.css')) ?>">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/OverlayScrollbars.min.css')) ?>">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/daterangepicker.css')) ?>">
    <!-- Tinymce -->
    <script src="<?= add_ver(base_url('assets/admin/js/tinymce.min.js')) ?>"></script>
    <!-- Toastr -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/iziToast.min.css')) ?>">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/select2.min.css')) ?>">
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/select2-bootstrap4.min.css')) ?>">
    <!-- Sweet Alert -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/sweetalert2.min.css')) ?>">
    <!-- Chartist -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/chartist.css')) ?>">
    <!-- JS tree -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/style.min.css')) ?>">
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/star-rating.min.css')) ?>">
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/theme.css')) ?>">
    <!-- intlTelInput -->
    <?php /* PERFORMANCE: intlTelInput is never initialised anywhere in the admin panel. */ ?>
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/lightbox.css')) ?>">

    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/bootstrap-table.min.css')) ?>">
    <?php /* PERFORMANCE: fancybox is a THIRD lightbox alongside ekko-lightbox and lightbox.js,
              which ARE used. Nothing calls .fancybox() and no data-fancybox markup exists. */ ?>
    <!-- chat -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/components.css')) ?>" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/custom/custom.css')) ?>">

    <!-- for Cretzo theme -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/cretzo/cretzo.css')) ?>">

    <!-- jQuery -->
    <script src="<?= add_ver(base_url('assets/admin/js/jquery.min.js')) ?>"></script>
    <?php // CSRF: token for assets/csrf-guard.js, which stamps it onto every same-origin
          // POST (raw forms, $.ajax, FormData uploads, fetch, XHR) so enabling
          // csrf_protection doesn't require editing hundreds of existing call sites.
          // Must load immediately after jQuery so its ajaxPrefilter is registered before
          // any page script fires a request. ?>
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name() ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash() ?>">
    <script src="<?= add_ver(base_url('assets/csrf-guard.js')) ?>"></script>
    <!-- Star rating js -->
    <script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/star-rating.js')) ?>"></script>
    <script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/theme.min.js')) ?>"></script> 
    <?php
    /*
     * PERFORMANCE + CORRECTNESS: these pulled Firebase 7.20.0 from gstatic, while
     * include-script.php loads the LOCAL firebase-app 8.0.1 and firebase-auth further down
     * the same page. Two major versions of one SDK were on every admin page, and because
     * the local pair loads later it replaced window.firebase wholesale - the same dual-SDK
     * hazard the storefront's include-css.php already warns about.
     *
     * Nothing is lost by dropping them:
     *   - firebase.messaging() is never called anywhere in the admin panel, so the
     *     messaging SDK was downloaded and never touched.
     *   - the only Firebase calls are firebase.initializeApp() and firebase.auth(), both in
     *     firebase-config.js, which loads at the END of include-script.php - after the local
     *     8.0.1 SDK - so they already ran against 8.0.1, not against this copy.
     *
     * Also drops two cross-origin requests from the critical path.
     */
    ?>
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/tagify.min.css')) ?>">
    <script type="text/javascript">
      // missing of var caused the stuck 
      var  base_url = "<?= base_url() ?>";
      var  csrfName = "<?= $this->security->get_csrf_token_name() ?>";
      var  csrfHash = "<?= $this->security->get_csrf_hash() ?>";
      var  form_name = '<?= '#' . $main_page . '_form' ?>';
    </script>

</head>