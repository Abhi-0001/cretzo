var base_url = "<?= base_url() ?>";
var csrfName = "<?= $this->security->get_csrf_token_name() ?>";
var csrfHash = "<?= $this->security->get_csrf_hash() ?>";


<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<!-- Bootstrap 4 -->
<!-- google translate library -->
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script src="<?= base_url('assets/admin/js/bootstrap.bundle.min.js') ?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?= base_url('assets/admin/jquery-ui/jquery-ui.min.js') ?>"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->

<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Ekko Lightbox -->

<script src=<?= base_url('assets/admin/ekko-lightbox/ekko-lightbox.min.js') ?>></script>

<!-- ChartJS -->
<script src="<?= base_url('assets/admin/chart.js/Chart.min.js') ?>"></script>
<!-- Sparkline -->
<script src="<?= base_url('assets/admin/js/sparkline.js') ?>"></script>
<!-- JQVMap -->
<script src="<?= base_url('assets/admin/js/jquery.vmap.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/jquery.vmap.usa.js') ?>"></script>
<!-- jQuery Knob Chart -->
<script src="<?= base_url('assets/admin/js/jquery.knob.min.js') ?>"></script>
<!-- daterangepicker -->
<script src="<?= base_url('assets/admin/js/moment.min.js') ?>"></script>
<script src="<?= base_url('assets/admin/js/daterangepicker.js') ?>"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="<?= base_url('assets/admin/js/tempusdominus-bootstrap-4.min.js') ?>"></script>
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
<script src="<?= base_url('assets/admin/js//bootstrap-table-export.min.js"') ?>"></script>
<!-- Jquery Fancybox -->
<script src="<?= base_url('assets/admin/js/jquery.fancybox.min.js') ?>"></script>
<!-- Sweeta Alert 2 -->
<script src="<?= base_url('assets/admin/js/sweetalert2.min.js') ?>"></script>
<!-- Block UI -->
<script src="<?= base_url('assets/admin/js/jquery.blockUI.js') ?>"></script>
<!-- JS tree -->
<script src="<?= base_url('assets/admin/js/jstree.min.js') ?>"></script>
<!-- Chartist -->
<script src="<?= base_url('assets/admin/js/chartist.js') ?>"></script>
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
<script type="text/javascript" src="<?= base_url('assets/admin/js/jquery.validate.min.js') ?>"></script>
<!-- Markdown -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/Markdown.Converter.js'); ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/Markdown.Sanitizer.js'); ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/Markdown.Editor.js'); ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/stisla.js'); ?>"></script>

<!-- Firebase.js -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/firebase-app.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/firebase-auth.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('firebase-config.js') ?>"></script>
<!-- intlTelInput -->
<script type="text/javascript" src="<?= base_url('assets/admin/js/intlTelInput.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/admin/js/lightbox.js') ?>"></script>
<!-- Custom -->
<script src="<?= base_url('assets/admin/custom/custom.js') ?>?v=<?= time(); ?>"></script>
<script src="<?= base_url('assets/admin/custom/pos.js') ?>"></script>
<!-- Demo -->
<script src="<?= base_url('assets/admin/dist/js/demo.js') ?>"></script>

<script>

// Main Image upload — uses same endpoint as media modal
$(document).on('click', '.seller-image-upload', function(e) {
    e.preventDefault();

    var $btn = $(this);
    var inputName = $btn.data('input');
    var isMultiple = $btn.data('is-multiple-uploads-allowed') == '1';
    var fileInput = document.getElementById('seller-image-file-input');
    if (!fileInput) return;

    if (isMultiple) {
        fileInput.setAttribute('multiple', 'multiple');
    } else {
        fileInput.removeAttribute('multiple');
    }
    fileInput.setAttribute('accept', 'image/*');
    fileInput.setAttribute('data-target-input', inputName);
    fileInput.setAttribute('data-is-multiple', isMultiple ? '1' : '0');

    $(fileInput).off('change.sellerupload').on('change.sellerupload', function() {
        var files = this.files;
        if (!files.length) return;

        var $section = $btn.closest('.col-sm-10, .col-sm-12, .col-md-12').find('.image-upload-section');
        var isMultipleUpload = fileInput.getAttribute('data-is-multiple') == '1';

        // Clear section if single upload
        if (!isMultipleUpload) {
            $section.html('');
        }

        var uploadCount = 0;
        var totalFiles = files.length;

        $btn.html('<i class="fa fa-spinner fa-spin"></i> Uploading...').addClass('disabled');

        for (var f = 0; f < files.length; f++) {
            (function(file) {
                var formData = new FormData();
                formData.append('documents[]', file, file.name);
                formData.append(csrfName, csrfHash);

                $.ajax({
                    url: base_url + 'seller/media/upload',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        if (typeof res === 'string') {
                            try { res = JSON.parse(res); } catch(e) { return; }
                        }
                        csrfName = res.csrfName;
                        csrfHash = res.csrfHash;

                        uploadCount++;
                        if (uploadCount >= totalFiles) {
                            $btn.html('<i class="fa fa-upload"></i> Upload').removeClass('disabled');
                        }

                        if (res.error == false && res.files && res.files.length > 0) {
                            var uploaded = res.files[0];
                            var imgPath = uploaded.sub_directory + uploaded.name;
                            var imgUrl  = uploaded.url;
                            var isRemovable = $btn.data('isremovable') == '1';
                        
                            var html = '<div class="col-md-3 col-sm-12 shadow p-3 mb-3 bg-white rounded m-2 text-center grow image">' +
                                '<div class="image-upload-div"><img class="img-fluid mb-2" src="' + imgUrl + '" alt="Uploaded" style="max-height:120px;object-fit:contain;"></div>' +
                                (isRemovable ? '<a href="javascript:void(0)" class="btn btn-block bg-gradient-danger btn-xs mt-1 remove-uploaded-img"><i class="far fa-trash-alt"></i> Remove</a>' : '') +
                                '<input type="hidden" name="' + inputName + '" value="' + imgPath + '">' +
                                '</div>';
                        
                            $section.append(html);
                        
                            var $toast = $('<div style="position:fixed;top:20px;right:20px;background:#28a745;color:#fff;padding:10px 20px;border-radius:6px;z-index:9999;font-weight:bold;">✅ Image uploaded successfully!</div>');
                            $('body').append($toast);
                            setTimeout(function() { $toast.fadeOut(function() { $(this).remove(); }); }, 3000);
                        }
                        else {
                            if (typeof Toast !== 'undefined') {
                                Toast.fire({ icon: 'error', title: res.message || 'Upload failed' });
                            } else {
                                alert('Upload failed: ' + (res.message || 'Unknown error'));
                            }
                        }
                        
                    },
                    error: function() {
                        uploadCount++;
                        if (uploadCount >= totalFiles) {
                            $btn.html('<i class="fa fa-upload"></i> Upload').removeClass('disabled');
                        }
                        alert('Upload failed. Please try again.');
                    }
                });
            })(files[f]);
        }

        $(this).val('');
    });

    fileInput.click();
});

// Remove uploaded image preview
$(document).on('click', '.remove-uploaded-img', function() {
    $(this).closest('.col-md-3').remove();
});

</script>

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