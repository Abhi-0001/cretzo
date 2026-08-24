<?php
$max_upload = ini_get('upload_max_filesize');
$max_post   = ini_get('post_max_size');
?>
<div class="content-wrapper admin-bulk-upload-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-file-csv mr-2 text-primary-theme"></i>Bulk Upload</h4>
                    <p class="text-muted mb-0 small">Add or update many products at once from a CSV file.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/product') ?>">Products</a></li>
                        <li class="breadcrumb-item active">Bulk Upload</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- ===== Upload form ===== -->
                <div class="col-lg-7 col-12 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-upload"></i></span>
                            <h5 class="mb-0">Upload File</h5>
                        </div>
                        <div class="card-body">
                            <!-- enctype was missing. The submit handler builds a FormData object so
                                 the AJAX path worked regardless, but any fallback to a normal form
                                 post would have submitted the form without the file attached. -->
                            <form class="form-horizontal" action="<?= base_url('admin/product/process_bulk_upload'); ?>"
                                method="POST" enctype="multipart/form-data" id="bulk_upload_form">

                                <div class="form-group">
                                    <label for="type">Action <span class='text-danger'>*</span></label>
                                    <select class='form-control' name='type' id='type'>
                                        <option value=''>Select an action</option>
                                        <option value='upload'>Upload &mdash; add new products</option>
                                        <option value='update'>Update &mdash; change existing products</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Each action uses a different CSV layout. Download the matching sample file below.
                                    </small>
                                </div>

                                <?php // The seven settings that used to be numeric-coded columns in
                                      // the sheet. They are the same for every row of a real import,
                                      // so they are chosen here in words and written into the
                                      // template as words - the same design as the seller page. The
                                      // importer reads either the cell or, when it is blank, these.
                                      // Only shown for Upload; the Update sheet has its own columns. ?>
                                <div id="upload_defaults" class="bulk-defaults form-group" style="display:none;">
                                    <label class="mb-1"><i class="fas fa-sliders-h mr-1"></i>Settings applied to every product in this file</label>
                                    <p class="text-muted small mb-3">These used to be number codes in the CSV. Set them once here, then download a template with them already filled in.</p>

                                    <div class="form-row">
                                        <div class="col-md-4 form-group">
                                            <label for="default_cod_allowed">Cash on delivery</label>
                                            <select class="form-control" name="default_cod_allowed" id="default_cod_allowed">
                                                <option value="1" selected>Allowed</option>
                                                <option value="0">Not allowed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="default_prices_inclusive_tax">Prices in the file</label>
                                            <select class="form-control" name="default_prices_inclusive_tax" id="default_prices_inclusive_tax">
                                                <option value="0" selected>Do not include tax</option>
                                                <option value="1">Already include tax</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="default_is_returnable">Returns</label>
                                            <select class="form-control" name="default_is_returnable" id="default_is_returnable">
                                                <option value="0" selected>Not returnable</option>
                                                <option value="1">Returnable</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-4 form-group">
                                            <label for="default_cancelable_till">Cancellation</label>
                                            <select class="form-control" name="default_cancelable_till" id="default_cancelable_till">
                                                <option value="" selected>Cannot be cancelled</option>
                                                <option value="received">Until the order is received</option>
                                                <option value="processed">Until the order is processed</option>
                                                <option value="shipped">Until the order is shipped</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="default_indicator">Food type marking</label>
                                            <select class="form-control" name="default_indicator" id="default_indicator">
                                                <option value="0" selected>Not a food product</option>
                                                <option value="1">Vegetarian</option>
                                                <option value="2">Non-vegetarian</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="default_deliverable_type">Delivery area</label>
                                            <select class="form-control" name="default_deliverable_type" id="default_deliverable_type">
                                                <option value="1" selected>Everywhere we ship</option>
                                                <option value="2">Only these pincodes</option>
                                                <option value="3">Everywhere except these pincodes</option>
                                                <option value="0">Not available for delivery yet</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row" id="default_zipcodes_wrap" style="display:none;">
                                        <div class="col-12 form-group">
                                            <label for="default_deliverable_zipcodes">Pincodes</label>
                                            <textarea class="form-control" name="default_deliverable_zipcodes" id="default_deliverable_zipcodes" rows="2" placeholder="110001, 400001, 560001"></textarea>
                                            <small class="text-muted">Separate them with commas or spaces.</small>
                                        </div>
                                    </div>

                                    <div class="form-row align-items-end">
                                        <div class="col-md-3 form-group">
                                            <label for="template_rows">Rows in template</label>
                                            <input type="number" class="form-control" name="template_rows" id="template_rows" value="10" min="1" max="200">
                                        </div>
                                        <div class="col-md-3 form-group">
                                            <label for="template_variants">Variants per product</label>
                                            <input type="number" class="form-control" name="template_variants" id="template_variants" value="1" min="1" max="10">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <button type="button" class="btn btn-primary-theme btn-block" id="download_template_btn">
                                                <i class="fas fa-file-download mr-1"></i>Download template with these settings
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="upload_file">CSV File <span class='text-danger'>*</span></label>
                                    <div class="file-drop" id="file_drop">
                                        <input type="file" name="upload_file" id="upload_file" accept=".csv,text/csv" />
                                        <div class="file-drop-inner">
                                            <i class="fas fa-file-csv"></i>
                                            <p class="mb-1"><strong>Choose a CSV file</strong></p>
                                            <p class="text-muted small mb-0" id="file_drop_hint">
                                                Maximum size <?= html_escape($max_upload) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary-theme" id="submit_btn">
                                        <i class="fas fa-play mr-1"></i>Start Import
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary" id="reset_btn">Reset</button>
                                </div>

                                <div id="upload_result" class="upload-result mt-3"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ===== Guidance and downloads ===== -->
                <div class="col-lg-5 col-12 mb-3">
                    <div class="card attribute-card mb-3">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-info-soft mr-2"><i class="fas fa-info-circle"></i></span>
                            <h5 class="mb-0">Before You Start</h5>
                        </div>
                        <div class="card-body">
                            <ul class="guidance-list mb-0">
                                <li>For a new import, set the settings on the left and press <strong>Download template with these settings</strong> &mdash; those columns come back already filled in.</li>
                                <li>Otherwise download the sample file for your chosen action and keep its column order unchanged.</li>
                                <li>Read the instructions file &mdash; it explains every column and its accepted values.</li>
                                <li>The file must be in <strong>.csv</strong> format.</li>
                                <li>Image paths can be copied from the <a href="<?= base_url('admin/media') ?>">Media</a> section.</li>
                                <li>The whole file is checked before anything is saved. If any row is invalid, nothing is imported and the offending row number is reported.</li>
                                <li>Very large files may exceed the server limit of <?= html_escape($max_post) ?>. Split them into smaller batches.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-value mr-2"><i class="fas fa-download"></i></span>
                            <h5 class="mb-0">Templates &amp; Instructions</h5>
                        </div>
                        <div class="card-body">
                            <div class="download-group mb-3" data-for="upload">
                                <h6 class="download-title">For adding new products</h6>
                                <a href="<?= base_url('uploads/product-bulk-upload-sample.csv') ?>" class="btn btn-outline-primary-theme btn-sm mb-1" download>
                                    <i class="fas fa-file-csv mr-1"></i>Sample file
                                </a>
                                <a href="<?= base_url('uploads/bulk-upload-instructions.txt') ?>" class="btn btn-outline-secondary btn-sm mb-1" download>
                                    <i class="fas fa-book mr-1"></i>Instructions
                                </a>
                            </div>
                            <div class="download-group" data-for="update">
                                <h6 class="download-title">For updating existing products</h6>
                                <a href="<?= base_url('uploads/product-bulk-update-sample.csv') ?>" class="btn btn-outline-primary-theme btn-sm mb-1" download>
                                    <i class="fas fa-file-csv mr-1"></i>Sample file
                                </a>
                                <a href="<?= base_url('uploads/bulk-update-instructions.txt') ?>" class="btn btn-outline-secondary btn-sm mb-1" download>
                                    <i class="fas fa-book mr-1"></i>Instructions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<style>
    .admin-bulk-upload-page .text-primary-theme { color: var(--color-orange); }

    .admin-bulk-upload-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-bulk-upload-page .btn-primary-theme:hover:not(:disabled) { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .admin-bulk-upload-page .btn-primary-theme:disabled { opacity: .65; }
    .admin-bulk-upload-page .btn-outline-primary-theme {
        border: 1px solid var(--color-orange);
        color: var(--color-orange-dark);
        font-weight: 600;
        background: #fff;
    }
    .admin-bulk-upload-page .btn-outline-primary-theme:hover { background: var(--color-orange); color: #fff; }

    .admin-bulk-upload-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-bulk-upload-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-bulk-upload-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-bulk-upload-page .header-icon.bg-set { background: var(--color-orange); }
    .admin-bulk-upload-page .header-icon.bg-value { background: var(--color-orange-dark); }
    .admin-bulk-upload-page .header-icon.bg-info-soft { background: #2e93e8; }

    /* ---- file field ---- */
    .admin-bulk-upload-page .file-drop {
        position: relative;
        border: 2px dashed rgba(0,0,0,0.15);
        border-radius: 10px;
        background: #fbfbfb;
        transition: border-color .15s ease, background .15s ease;
    }
    .admin-bulk-upload-page .file-drop:hover,
    .admin-bulk-upload-page .file-drop.has-file { border-color: var(--color-orange); background: var(--color-orange-light); }
    .admin-bulk-upload-page .file-drop input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .admin-bulk-upload-page .file-drop-inner {
        padding: 26px 16px;
        text-align: center;
        pointer-events: none;
    }
    .admin-bulk-upload-page .file-drop-inner i { font-size: 30px; color: var(--color-orange); margin-bottom: 8px; display: block; }

    /* ---- result panel ---- */
    .admin-bulk-upload-page .upload-result {
        display: none;
        padding: 14px 16px;
        border-radius: 10px;
        font-size: 14px;
        line-height: 1.5;
        word-break: break-word;
    }
    .admin-bulk-upload-page .upload-result.msg_success { display: block; background: #E6F4EA; color: #1e6b33; border: 1px solid rgba(63,162,92,0.3); }
    .admin-bulk-upload-page .upload-result.msg_error { display: block; background: #FBEAE8; color: #8a2f27; border: 1px solid rgba(193,68,58,0.3); }

    .admin-bulk-upload-page .guidance-list { padding-left: 18px; margin: 0; }
    .admin-bulk-upload-page .guidance-list li { margin-bottom: 8px; font-size: 14px; }
    .admin-bulk-upload-page .guidance-list li:last-child { margin-bottom: 0; }

    .admin-bulk-upload-page .download-title {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        font-weight: 600;
        margin-bottom: 8px;
    }
    .admin-bulk-upload-page .download-group.is-dimmed { opacity: .45; }
    .admin-bulk-upload-page .bulk-defaults {
        background: var(--color-orange-light, #fff6ef);
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 14px 16px 0;
    }
</style>

<script>
    $(function () {
        var $input = $('#upload_file');
        var $drop = $('#file_drop');
        var $hint = $('#file_drop_hint');
        var defaultHint = $hint.text();

        $input.on('change', function () {
            if (this.files && this.files.length) {
                var file = this.files[0];
                var kb = file.size / 1024;
                var size = kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.max(1, Math.round(kb)) + ' KB';
                $hint.html('<strong>' + $('<div>').text(file.name).html() + '</strong> &middot; ' + size);
                $drop.addClass('has-file');
            } else {
                $hint.text(defaultHint);
                $drop.removeClass('has-file');
            }
        });

        $('#reset_btn').on('click', function () {
            $hint.text(defaultHint);
            $drop.removeClass('has-file');
            $('#upload_result').hide().removeClass('msg_success msg_error').empty();
        });

        // Dim whichever set of templates does not apply to the selected action, so the wrong
        // sample file is less likely to be downloaded and filled in.
        // 2 = only these pincodes, 3 = everywhere except these. The other two choices need no
        // list, so showing the box for them would only make an empty field look required.
        function syncZipcodeVisibility() {
            var choice = $('#default_deliverable_type').val();
            $('#default_zipcodes_wrap').toggle(choice === '2' || choice === '3');
        }
        $('#default_deliverable_type').on('change', syncZipcodeVisibility);
        syncZipcodeVisibility();

        // Submitted as a throwaway form rather than fetched: the response is a file download, so
        // the browser handles Content-Disposition itself. It has to be a form separate from
        // #bulk_upload_form, whose own submit handler is the AJAX import.
        $('#download_template_btn').on('click', function () {
            var fields = ['default_cod_allowed', 'default_prices_inclusive_tax', 'default_is_returnable',
                          'default_cancelable_till', 'default_indicator', 'default_deliverable_type',
                          'default_deliverable_zipcodes', 'template_rows', 'template_variants'];
            var form = $('<form>', {
                method: 'POST',
                action: '<?= base_url('admin/product/bulk_upload_template') ?>'
            }).appendTo('body');
            $('<input>', {type: 'hidden', name: csrfName, value: csrfHash}).appendTo(form);
            fields.forEach(function (id) {
                $('<input>', {type: 'hidden', name: id, value: $('#' + id).val()}).appendTo(form);
            });
            form.trigger('submit').remove();
        });

        $('#type').on('change', function () {
            var type = $(this).val();
            $('.download-group').each(function () {
                $(this).toggleClass('is-dimmed', type !== '' && $(this).data('for') !== type);
            });
            // The defaults panel replaces columns that only exist in the upload sheet.
            $('#upload_defaults').toggle(type === 'upload');
        });
    });
</script>
