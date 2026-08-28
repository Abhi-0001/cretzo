<?php
/*
 * PERFORMANCE - AdminLTE demo widgets and unused editors removed from the SELLER panel.
 *
 * The seller panel inherited the stock AdminLTE dashboard script list wholesale, so
 * every page a seller opened downloaded the template's demo widgets. None of these is
 * referenced by any view under application/views/seller, nor by the shared scripts the
 * seller pages load (assets/admin/custom/custom.js, custom/pos.js, dist/js/adminlte.js,
 * js/stisla.js, js/loader.js, js/tooltip.js) - checked against each library's own API,
 * not just its filename:
 *
 *   jquery.vmap.min.js + jquery.vmap.usa.js (66 KB) - a clickable vector map OF THE USA,
 *       the AdminLTE demo dashboard widget. This is an India-based marketplace.
 *   dist/js/demo.js (12 KB)          - AdminLTE's own "theme settings" demo panel, which
 *                                      is explicitly not meant to ship to production.
 *   sparkline.js (7 KB)              - demo dashboard sparklines. (The one "sparkline"
 *                                      match elsewhere is in Google Charts' loader.js,
 *                                      where it is the name of a CHART TYPE - unrelated.)
 *   jquery.knob.min.js (10 KB)       - demo dial gauges.
 *   tempusdominus (55 KB + 9 KB css) - date/time picker; no .datetimepicker() call exists.
 *   jquery.fancybox.min.js (66 KB)   - a THIRD lightbox, alongside ekko-lightbox and
 *                                      lightbox.js which ARE used. Nothing calls fancybox.
 *   jquery.validate.min.js (23 KB)   - no .validate() call anywhere in the seller panel.
 *   intlTelInput.js (69 KB)          - no intlTelInput() call in any seller view.
 *   Markdown.Converter/Sanitizer/Editor.js (161 KB) - the pagedown editor; no wmd- element.
 *
 * Total: 486 KB and 14 render-blocking requests off every seller page.
 *
 * Kept deliberately, because they ARE used:
 *   jquery.overlayScrollbars - adminlte.js drives the sidebar scrollbar with it
 *   ekko-lightbox, lightbox.js, chartist, Chart.js, jstree, bootstrap-switch,
 *   select2, iziToast, dropzone, tagify, tableExport, blockUI
 */
?>
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<!-- Bootstrap 4 -->
<?php // Google Translate loader removed with its mount point — see include-navbar.php. ?>
<script src="<?= base_url('assets/admin/js/bootstrap.bundle.min.js') ?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?= base_url('assets/admin/jquery-ui/jquery-ui.min.js') ?>"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->

<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Ekko Lightbox -->

<script src=<?= base_url('assets/admin/ekko-lightbox/ekko-lightbox.min.js') ?>></script>

<?php
/*
 * Chart.min.js (168 KB) is gone entirely rather than gated: there is no `new Chart(`
 * anywhere in this codebase. An earlier pass kept it on the dashboard on the strength of
 * a grep for `Chart(`, which also matches google.visualization.PieChart( - a false
 * positive. The dashboard's charts are drawn by Chartist and google.visualization.
 *
 * $seller_needs_charts is still computed here because chartist.js below is genuinely
 * dashboard-only; include-head.php normally sets it first.
 */
$seller_needs_charts = isset($seller_needs_charts)
    ? $seller_needs_charts
    : ((isset($main_page) && trim((string) $main_page) !== '' ? $main_page : FORMS . 'home') === FORMS . 'home');
?>
<!-- daterangepicker -->
<script src="<?= base_url('assets/admin/js/moment.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/daterangepicker.js') ?>"></script>
<!-- Toastr -->
<script src="<?= base_url('assets/admin/js/iziToast.min.js') ?>"></script>
<!-- Select -->
<script src="<?= base_url('assets/admin/js/select2.full.min.js') ?>"></script>
<!-- overlayScrollbars -->
<script src="<?= base_url('assets/admin/js/jquery.overlayScrollbars.min.js') ?>"></script>
<!-- AdminLTE App -->
<script src="<?= base_url('assets/admin/dist/js/adminlte.js') ?>"></script>
<!-- Bootstrap Switch -->
<script src="<?= base_url('assets/admin/js/bootstrap-switch.min.js') ?>"></script>
<!-- Bootstrap Table -->
<script src="<?= base_url('assets/admin/js/bootstrap-table.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/tableExport.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/bootstrap-table-export.min.js') ?>"></script>
<!-- Sweeta Alert 2 -->
<script src="<?= base_url('assets/admin/js/sweetalert2.min.js') ?>"></script>
<!-- Block UI -->
<script src="<?= base_url('assets/admin/js/jquery.blockUI.js') ?>"></script>
<!-- JS tree -->
<script src="<?= base_url('assets/admin/js/jstree.min.js') ?>"></script>
<!-- Chartist -->
<?php if ($seller_needs_charts) { ?>
    <script src="<?= base_url('assets/admin/js/chartist.js') ?>"></script>
<?php } ?>
<!-- Tool Tip -->
<script src="<?= base_url('assets/admin/js/tooltip.js') ?>"></script>
<!-- Loader Js -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/loader.js') ?>"></script>
<!-- Dropzone -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/dropzone.js') ?>"></script>
<!-- Sortable.JS -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/sortable.js') ?>"></script>
<!-- Sortable.min.js -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/jquery-sortable.js') ?>"></script>

<script type="text/javascript" src="<?= base_url('assets/admin/js/tagify.min.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/stisla.js'); ?>"></script>

<!-- Firebase.js -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/firebase-app.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/firebase-auth.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('firebase-config.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/lightbox.js') ?>"></script>
<!-- Custom -->
 
<script src="<?= add_ver(base_url('assets/admin/custom/pos.js')) ?>"></script>
<?php
// The product form is a port of the admin one and is driven by the same script.
// custom.js decides which panel's endpoints to call from the URL ("seller/" in the
// path => seller/product/..., seller/category/..., seller/area/...), so it needs no
// changes to run here - but it must load AFTER the plugin bundle above, since it
// calls select2/sortable at parse time. It is scoped to this one page deliberately:
// every other seller page has its own hand-written equivalents of these handlers and
// would end up with two of each.
if (isset($main_page) && $main_page === FORMS . 'product') { ?>
    <script src="<?= add_ver(base_url('assets/admin/custom/custom.js')) ?>"></script>
<?php } ?>

<?php if ($this->session->flashdata('message')) { ?>
    <script>
        Swal.fire('<?= $this->session->flashdata('message_type') ?>', "<?= $this->session->flashdata('message') ?>", '<?= $this->session->flashdata('message_type') ?>');
    </script>
<?php } ?>


<?php
if ($this->session->flashdata('authorize_flag')) { ?>
    <script>
        Swal.fire('Warning', "<?= $this->session->flashdata('authorize_flag') ?>", 'warning');
    </script>
<?php }
$this->session->set_flashdata('authorize_flag', "");

?>