<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php /* html_escape(): $title is assembled from settings and, on some pages,
         from record names - all of which are user-editable somewhere. Escaping a
         static string costs nothing, and it means no future page that puts a
         product, seller or customer name in the title reopens an injection. */ ?>
    <title><?= html_escape($title) ?></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= base_url() . get_settings('favicon') ?>" type="image/gif" sizes="16x16">
    <?php $np = isset($main_page) ? $main_page : ''; // page key for seller_page_needs() - see function_helper.php ?>
    <?php if (seller_page_needs('switch', $np)) { ?>
    <!-- Bootstrap Switch -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/bootstrap-switch.min.css') ?>">
    <?php } ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/all.min.css') ?>">
    <?php /* PERFORMANCE: ionicons.min.css (63 KB) and icheck-bootstrap.min.css (12 KB) were loaded on every
              page, but no seller view, pos.js or custom.js uses an ion-* icon or an icheck-* class. */ ?>
    <!-- Tempusdominus Bbootstrap 4 -->
    <?php /* PERFORMANCE: tempusdominus (the AdminLTE date/time picker) is not used anywhere
              in the seller panel - no .datetimepicker() call and no tempusdominus markup in any
              seller view or in the shared custom.js. Its 55 KB script went with it. */ ?>
    <!-- Dropzone -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/dropzone.css') ?>">
    <!-- JQVMap -->
    <?php /* PERFORMANCE: jqvmap is AdminLTE's demo "vector map of the USA" dashboard widget.
              Nothing in the seller panel references it. Removed with its two scripts (66 KB). */ ?>
    <?php if (seller_page_needs('lightbox', $np)) { ?>
    <!-- Ekko Lightbox -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/ekko-lightbox/ekko-lightbox.css') ?>">
    <?php } ?>
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/dist/css/adminlte.min.css') ?>">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/OverlayScrollbars.min.css') ?>">
    <?php if (seller_page_needs('daterange', $np)) { ?>
    <!-- Daterange picker -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/daterangepicker.css') ?>">
    <?php } ?>
    <!-- Tinymce -->
    <?php
    /*
     * PERFORMANCE: TinyMCE is 398 KB and sat in the <head>, so it blocked rendering of
     * EVERY seller page - including pages with no rich-text field anywhere on them.
     *
     * custom.js only ever initialises it behind element-presence guards:
     *     if ($(".sendMail").length > 0)          { tinymce.init(...) }
     *     if ($(".editSendMailOrders").length > 0){ tinymce.init(...) }
     *     if ($(".textarea").length > 0)          { tinymce.init(...) }
     * so on a page carrying none of those classes the editor was downloaded, parsed and
     * then never touched. Only three seller views use any of them, all via .textarea:
     * the product form, the order edit form and the orders table. (.sendMail and
     * .editSendMailOrders exist only in the ADMIN panel, which loads its own copy.)
     *
     * $seller_needs_tinymce is reused by include-script.php - keep the two in step.
     */
    /*
     * Mirror template.php exactly: the dashboard controller does not set $main_page at
     * all, and template.php falls back to FORMS.'home' when it is unset or blank. Reading
     * it as '' here instead would have silently starved the dashboard of its own charts.
     */
    $seller_page = (isset($main_page) && trim((string) $main_page) !== '') ? (string) $main_page : FORMS . 'home';
    $seller_needs_tinymce = in_array($seller_page, [FORMS . 'product', FORMS . 'edit-orders', TABLES . 'manage-orders'], true);
    if ($seller_needs_tinymce) { ?>
        <script src="<?= base_url('assets/admin/js/tinymce.min.js') ?>"></script>
    <?php } ?>
    <!-- Toastr -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/iziToast.min.css') ?>">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/select2.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/select2-bootstrap4.min.css') ?>">
    <!-- Sweet Alert -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/sweetalert2.min.css') ?>">
    <!-- Chartist -->
    <?php
    /*
     * PERFORMANCE: the charting stack (Chart.js 168 KB + chartist 213 KB + this
     * stylesheet) is only ever exercised by the dashboard. Every Chartist and Chart
     * call in custom.js lives inside one block - lines 172-321 - opened by
     *     if (document.getElementById('piechart_3d')) { ... }
     * and #piechart_3d exists in exactly one seller view, forms/home.php. On every
     * other seller page the whole stack was downloaded and never entered.
     *
     * Gating it also stops the two dashboard AJAX calls that block makes
     * (category_wise_product_count and fetch_sales) from being set up elsewhere.
     */
    $seller_needs_charts = ($seller_page === FORMS . 'home');
    if ($seller_needs_charts) { ?>
        <link rel="stylesheet" href="<?= base_url('assets/admin/css/chartist.css') ?>">
    <?php } ?>
    <?php if (seller_page_needs('jstree', $np)) { ?>
    <!-- JS tree -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/style.min.css') ?>">
    <?php } ?>
    <?php if (seller_page_needs('rating', $np)) { ?>
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/star-rating.min.css') ?>">
    <?php } ?>
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/theme.css') ?>">
    <!-- chat -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/components.css') ?>" />
    <?php /* PERFORMANCE: both font stylesheets are render-blocking and cross-origin. Warm the two hosts up
              first, and give Source Sans Pro the display=swap the Poppins request already had, so text
              paints in the fallback face instead of staying invisible until Google answers. */ ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;1,100;1,200;1,300&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=swap" rel="stylesheet">

    <?php /* PERFORMANCE: bootstrap-table's stylesheet came from cdnjs (a third DNS + TLS handshake on the
              critical path) although the matching script is served locally. Same library, own origin. */ ?>
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/bootstrap-table.min.css') ?>">
    <?php /* PERFORMANCE: fancybox's stylesheet, pulled cross-origin from cdnjs, for a
              library whose script was removed as unused - there is no data-fancybox
              attribute and no .fancybox class anywhere in the seller panel, and nothing
              calls .fancybox(). Removing it also drops a third-party DNS + TLS handshake
              from the critical path of every seller page. */ ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/admin/custom/custom.css') ?>">

    <!-- for Cretzo theme -->
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/cretzo/cretzo.css')) ?>">

    <!-- form styles for seller style -->
    


    <!-- jQuery -->
    <script src="<?= base_url('assets/admin/js/jquery.min.js') ?>"></script>
    <?php // See admin/include-head.php - stamps the CSRF token onto every same-origin POST. ?>
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name() ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash() ?>">
    <script src="<?= base_url('assets/csrf-guard.js') ?>"></script>
    <!-- Star rating js -->
    <?php if (seller_page_needs('rating', $np)) { ?>
    <script type="text/javascript" src="<?= base_url('assets/admin/js/star-rating.js') ?>"></script>
    <?php } ?>
    <?php if (seller_page_needs('rating', $np)) { // theme.min.js is star-rating's Krajee theme and must follow star-rating.js ?>
    <script type="text/javascript" src="<?= base_url('assets/admin/js/theme.min.js') ?>"></script>
    <?php } ?>
    <?php if (seller_page_needs('tagify', $np)) { ?>
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/tagify.min.css') ?>">
    <?php } ?>

    <script type="text/javascript">
        base_url = "<?= base_url() ?>";
        csrfName = "<?= $this->security->get_csrf_token_name() ?>";
        csrfHash = "<?= $this->security->get_csrf_hash() ?>";

        form_name = '<?= '#' . (isset($main_page) ? $main_page : '') . '_form' ?>';
    </script>
    
</head>