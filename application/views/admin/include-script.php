<?php
/*
 * PERFORMANCE - AdminLTE demo widgets and unused editors removed from the ADMIN panel.
 *
 * Same audit as the seller panel: every library below was checked against its own API
 * across all admin views plus the scripts this page loads (custom/custom.js, custom/pos.js,
 * dist/js/adminlte.js, js/stisla.js, js/loader.js, js/tooltip.js). None is referenced.
 *
 *   chart.js/Chart.min.js (168 KB) - there is no `new Chart(` anywhere in this codebase.
 *       The dashboard's charts are drawn by Chartist and by google.visualization; Chart.js
 *       was never used by either panel. (An earlier pass kept it on the seller dashboard on
 *       the strength of a grep for `Chart(`, which matches google.visualization.PieChart( -
 *       a false positive, now corrected on both sides.)
 *   jquery.vmap + jquery.vmap.usa (66 KB) - AdminLTE's demo "vector map of the USA".
 *   dist/js/demo.js (12 KB)        - AdminLTE's theme-settings demo panel, not for production.
 *   sparkline.js, jquery.knob.min.js - demo dashboard widgets.
 *   tempusdominus (55 KB + 9 KB css) - no .datetimepicker() call exists.
 *   jquery.fancybox (66 KB + 16 KB css) - a third lightbox; ekko-lightbox and lightbox.js
 *                                    are the ones actually used.
 *   jquery.validate.min.js (23 KB) - no .validate() call.
 *   intlTelInput (69 KB + 26 KB css) - never initialised.
 *   Markdown.Converter/Sanitizer/Editor (161 KB) - the pagedown editor; no wmd- element.
 *
 * Plus the two gstatic Firebase 7.20.0 tags removed from include-head.php - see the note
 * there; they duplicated the local 8.0.1 SDK that actually serves firebase.auth().
 *
 * Total: 698 KB and 17 local requests, plus 2 cross-origin requests, off every admin page.
 *
 * Kept, because they ARE used: jquery.overlayScrollbars (adminlte.js drives the sidebar
 * with it), chartist, jstree, tinymce, ekko-lightbox, lightbox.js, bootstrap-switch,
 * bootstrap-table, tableExport, select2, iziToast, dropzone, tagify, blockUI, sortable.
 */
?>
<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<!-- Bootstrap 4 -->

<!-- google translate library -->
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script src="<?= add_ver(base_url('assets/admin/js/bootstrap.bundle.min.js')) ?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?= add_ver(base_url('assets/admin/jquery-ui/jquery-ui.min.js')) ?>"></script>
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->

<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Ekko Lightbox -->
<script src=<?= add_ver(base_url('assets/admin/ekko-lightbox/ekko-lightbox.min.js')) ?>></script>
<!-- ChartJS -->
<!-- Sparkline -->
<!-- JQVMap -->
<!-- jQuery Knob Chart -->
<!-- daterangepicker -->
<script src="<?= add_ver(base_url('assets/admin/js/moment.min.js')) ?>"></script>
<script src="<?= add_ver(base_url('assets/admin/js/daterangepicker.js')) ?>"></script>
<!-- Tempusdominus Bootstrap 4 -->
<!-- Toastr -->
<script src="<?= add_ver(base_url('assets/admin/js/iziToast.min.js')) ?>"></script>
<!-- Select -->
<script src="<?= add_ver(base_url('assets/admin/js/select2.full.min.js')) ?>"></script>
<!-- overlayScrollbars -->
<script src="<?= add_ver(base_url('assets/admin/js/jquery.overlayScrollbars.min.js')) ?>"></script>
<!-- AdminLTE App -->
<script src="<?= add_ver(base_url('assets/admin/dist/js/adminlte.js')) ?>"></script>
<!-- Bootstrap Switch -->
<script src="<?= add_ver(base_url('assets/admin/js/bootstrap-switch.min.js')) ?>"></script>
<!-- Bootstrap Table -->
<script src="<?= add_ver(base_url('assets/admin/js/bootstrap-table.min.js')) ?>"></script>
<script src="<?= add_ver(base_url('assets/admin/js/tableExport.js')) ?>"></script>
<script src="<?= base_url('assets/admin/js//bootstrap-table-export.min.js"') ?>"></script>
<!-- Jquery Fancybox -->
<!-- Sweeta Alert 2 -->
<script src="<?= add_ver(base_url('assets/admin/js/sweetalert2.min.js')) ?>"></script>
<!-- Block UI -->
<script src="<?= add_ver(base_url('assets/admin/js/jquery.blockUI.js')) ?>"></script>
<!-- JS tree -->
<script src="<?= add_ver(base_url('assets/admin/js/jstree.min.js')) ?>"></script>
<!-- Chartist -->
<script src="<?= add_ver(base_url('assets/admin/js/chartist.js')) ?>"></script>
<!-- Tool Tip -->
<script src="<?= add_ver(base_url('assets/admin/js/tooltip.js')) ?>"></script>
<!-- Loader Js -->
<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/loader.js')) ?>"></script>
<!-- Dropzone -->
<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/dropzone.js')) ?>"></script>
<!-- Sortable.JS -->
<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/sortable.js')) ?>"></script>
<!-- Sortable.min.js -->
<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/jquery-sortable.js')) ?>"></script>

<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/tagify.min.js')) ?>"></script>
<!-- Markdown -->



<!-- intlTelInput -->
<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/lightbox.js')) ?>"></script>

<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/stisla.js')); ?>"></script>


<!-- Firebase.js -->
<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/firebase-app.js')) ?>"></script>
<script type="text/javascript" src="<?= add_ver(base_url('assets/admin/js/firebase-auth.js')) ?>"></script>
<script type="text/javascript" src="<?= add_ver(base_url('firebase-config.js')) ?>"></script>


<!-- Custom -->
<script src="<?= add_ver(base_url('assets/admin/custom/custom.js')) ?>"></script>

<!-- Demo -->

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