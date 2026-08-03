<?php
$max_upload = ini_get('upload_max_filesize');
$max_post   = ini_get('post_max_size');
?>
<div class="content-wrapper admin-bulk-upload-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-file-csv mr-2 text-primary-theme"></i>Brand Bulk Upload</h4>
                    <p class="text-muted mb-0 small">Add or update many brands at once from a CSV file.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/brand') ?>">Brand</a></li>
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
                            <form class="form-horizontal" action="<?= base_url('admin/brand/process_bulk_upload'); ?>"
                                method="POST" enctype="multipart/form-data" id="bulk_upload_form">

                                <div class="form-group">
                                    <label for="type">Action <span class='text-danger'>*</span></label>
                                    <select class='form-control' name='type' id='type'>
                                        <option value=''>Select an action</option>
                                        <option value='upload'>Upload &mdash; add new brands</option>
                                        <option value='update'>Update &mdash; change existing brands</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Each action uses a different CSV layout. Download the matching sample file below.
                                    </small>
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
                                <li>Download the sample file for your chosen action and keep its column order unchanged.</li>
                                <li><strong>Upload</strong> columns: <code>name, image</code>. <strong>Update</strong> columns: <code>brand id, name, image</code> &mdash; using the wrong file for the selected action is rejected rather than silently importing garbage.</li>
                                <li>The file must be in <strong>.csv</strong> format.</li>
                                <li>Image paths can be copied from the <a href="<?= base_url('admin/media') ?>">Media</a> section.</li>
                                <li>The whole file is checked before anything is saved. If any row is invalid, nothing is imported and the offending row number is reported.</li>
                                <li>Two rows in the same file with the identical brand name are also rejected, not just a name that already exists.</li>
                                <li>Very large files may exceed the server limit of <?= html_escape($max_post) ?>. Split them into smaller batches.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-value mr-2"><i class="fas fa-download"></i></span>
                            <h5 class="mb-0">Sample Files</h5>
                        </div>
                        <div class="card-body">
                            <div class="download-group mb-3" data-for="upload">
                                <h6 class="download-title">For adding new brands</h6>
                                <a href="<?= base_url('uploads/brand-bulk-upload-sample.csv') ?>" class="btn btn-outline-primary-theme btn-sm mb-1" download="brand-bulk-upload-sample.csv">
                                    <i class="fas fa-file-csv mr-1"></i>Sample file
                                </a>
                            </div>
                            <div class="download-group" data-for="update">
                                <h6 class="download-title">For updating existing brands</h6>
                                <a href="<?= base_url('uploads/brand-bulk-update-sample.csv') ?>" class="btn btn-outline-primary-theme btn-sm mb-1" download="brand-bulk-update-sample.csv">
                                    <i class="fas fa-file-csv mr-1"></i>Sample file
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
        $('#type').on('change', function () {
            var type = $(this).val();
            $('.download-group').each(function () {
                $(this).toggleClass('is-dimmed', type !== '' && $(this).data('for') !== type);
            });
        });
    });
</script>
