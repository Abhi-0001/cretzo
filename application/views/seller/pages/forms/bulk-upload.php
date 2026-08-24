<div class="content-wrapper bulk-upload-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-file-csv mr-2 text-primary-theme"></i>Bulk Upload</h4>
                    <p class="text-muted mb-0 small">Add or update many products at once using a CSV file.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Bulk Upload</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="alert bulk-info-alert mb-4">
                <strong><i class="fas fa-info-circle mr-1"></i>Before you upload</strong>
                <ul class="mb-0 mt-2">
                    <li>Read and follow the instructions carefully while preparing your data</li>
                    <li>Download and save the sample file to reduce errors</li>
                    <li>File must be in <strong>.csv</strong> format</li>
                    <li>You can copy an image path from the Media section</li>
                    <li>Make sure your data is valid as per the instructions before you proceed</li>
                </ul>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-upload"></i></span>
                    <h5 class="mb-0">Upload CSV File</h5>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('seller/product/process_bulk_upload'); ?>" method="POST" id="bulk_upload_form" enctype="multipart/form-data">

                        <div class="form-row">
                            <div class="col-md-4 form-group">
                                <label for="type">Type <small class="text-muted">[upload/update]</small> <span class='text-danger text-sm'>*</span></label>
                                <select class='form-control' name='type' id='type'>
                                    <option value=''>Select</option>
                                    <option value='upload'>Upload</option>
                                    <option value='update'>Update</option>
                                </select>
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="file">CSV File <span class='text-danger text-sm'>*</span></label>
                                <input type="file" name="upload_file" class="form-control-file" accept=".csv" />
                            </div>
                        </div>

                        <?php // These six settings used to be numeric-coded columns in the sheet
                              // (cod_allowed, is_prices_inclusive_tax, is_returnable,
                              // is_cancelable + cancelable_till, indicator, deliverable_type). They
                              // are the same for every product in a real upload, so they are asked
                              // for once here in words instead. Only shown for Upload - the Update
                              // sheet still carries its own columns. ?>
                        <div id="upload_defaults" class="bulk-defaults mb-3" style="display:none;">
                            <strong><i class="fas fa-sliders-h mr-1"></i>Settings applied to every product in this file</strong>
                            <p class="text-muted small mb-3">These used to be number codes in the CSV. Set them once here and leave them out of your file.</p>

                            <div class="form-row">
                                <div class="col-md-4 form-group">
                                    <label for="default_cod_allowed">Cash on delivery</label>
                                    <select class="form-control" name="default_cod_allowed" id="default_cod_allowed">
                                        <option value="1" selected>Allowed</option>
                                        <option value="0">Not allowed</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="default_prices_inclusive_tax">Prices in your file</label>
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
                                    <label for="default_deliverable_type">Where you deliver</label>
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

                            <?php // Downloading a template built from the choices above beats handing
                                  // the seller a blank sample: the settings columns come back already
                                  // filled in, so the only cells left to type are the ones only the
                                  // seller knows. Posted rather than linked because the template has
                                  // to carry the current form values. ?>
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

                        <div class="bulk-file-links mb-3">
                            <a id="sample_file_link" href="<?= base_url('uploads/seller-product-bulk-upload-sample.csv') ?>" class="btn btn-outline-primary" download="seller-product-bulk-upload-sample.csv">
                                <i class="fas fa-download"></i> Bulk upload sample file
                            </a>
                            <a id="instructions_file_link" href="<?= base_url('uploads/seller-bulk-upload-instructions.txt') ?>" class="btn btn-outline-secondary" download="seller-bulk-upload-instructions.txt">
                                <i class="fas fa-download"></i> Bulk upload instructions
                            </a>
                        </div>

                        <?php // Was a one-per-line <ul> of every column in the sheet, which ran to
                              // about 30 bullets and pushed the Submit button off the screen. The
                              // same information fits in a few wrapped lines as chips, and it is
                              // reference material rather than something to read on every visit, so
                              // it collapses. <details> is used so it works without JavaScript. ?>
                        <details class="bulk-required-fields mb-3">
                            <summary>
                                <i class="fas fa-clipboard-check mr-1"></i>Columns in your CSV file
                                <span class="badge badge-light border ml-1" id="csv_required_count"></span>
                                <small class="text-muted ml-1">- the ones you must fill in are highlighted in orange</small>
                            </summary>
                            <div class="field-chips mt-2" id="csv_required_fields_list"></div>
                        </details>

                        <div id="upload_result" class="mb-3"></div>

                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary-theme mr-2" id="submit_btn"><i class="fas fa-cloud-upload-alt mr-1"></i>Submit</button>
                            <button type="reset" class="btn btn-light border">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .bulk-upload-page .text-primary-theme { color: var(--color-orange); }

    .bulk-upload-page .bulk-info-alert {
        background: var(--color-secondary);
        border: none;
        border-radius: 10px;
        color: #6b4a13;
        padding: 14px 18px;
    }
    .bulk-upload-page .bulk-info-alert ul {
        padding-left: 1.2rem;
        margin-bottom: 0;
    }

    .bulk-upload-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .bulk-upload-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .bulk-upload-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .bulk-upload-page .header-icon.bg-set { background: var(--color-orange); }

    .bulk-upload-page .form-control:focus,
    .bulk-upload-page .form-control-file:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .bulk-upload-page .bulk-file-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .bulk-upload-page .btn-outline-primary {
        color: var(--color-orange-dark);
        border-color: var(--color-orange);
    }
    .bulk-upload-page .btn-outline-primary:hover {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
    }
    .bulk-upload-page .btn-outline-secondary {
        color: var(--color-grey);
        border-color: rgba(0,0,0,0.15);
    }
    .bulk-upload-page .btn-outline-secondary:hover {
        background: #495057;
        border-color: #495057;
        color: #fff;
    }

    .bulk-upload-page .bulk-defaults {
        background: var(--color-orange-light, #fff6ef);
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 16px 18px 4px;
    }

    .bulk-upload-page .bulk-required-fields {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 12px 16px;
    }
    .bulk-upload-page .bulk-required-fields summary {
        cursor: pointer;
        font-weight: 600;
        outline: none;
    }
    .bulk-upload-page .bulk-required-fields summary small {
        font-weight: 400;
    }
    .bulk-upload-page .field-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .bulk-upload-page .field-chip {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 20px;
        padding: 2px 10px;
        font-size: .8125rem;
        color: #555;
        white-space: nowrap;
    }
    .bulk-upload-page .field-chip.is-required {
        border-color: var(--color-orange);
        color: var(--color-orange-dark, #c2410c);
        font-weight: 600;
    }

    .bulk-upload-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
        border-radius: 6px;
        padding: .5rem 1.5rem;
    }
    .bulk-upload-page .btn-primary-theme:hover:not(:disabled) {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .bulk-upload-page #upload_result:empty {
        display: none;
    }
    .bulk-upload-page #upload_result {
        border-radius: 8px;
        padding: 10px 14px;
    }
</style>

<script>
    const bulkCsvConfig = {
        upload: {
            sampleUrl: "<?= base_url('uploads/seller-product-bulk-upload-sample.csv') ?>",
            sampleName: "seller-product-bulk-upload-sample.csv",
            sampleLabel: "Bulk upload sample file",
            instructionsUrl: "<?= base_url('uploads/seller-bulk-upload-instructions.txt') ?>",
            instructionsName: "seller-bulk-upload-instructions.txt",
            instructionsLabel: "Bulk upload instructions"
        },
        update: {
            sampleUrl: "<?= base_url('uploads/seller-product-bulk-update-sample.csv') ?>",
            sampleName: "seller-product-bulk-update-sample.csv",
            sampleLabel: "Bulk update sample file",
            instructionsUrl: "<?= base_url('uploads/seller-bulk-update-instructions.txt') ?>",
            instructionsName: "seller-bulk-update-instructions.txt",
            instructionsLabel: "Bulk update instructions"
        }
    };

    function parseCsvHeader(headerLine) {
        const columns = [];
        let value = '';
        let inQuotes = false;

        for (let i = 0; i < headerLine.length; i++) {
            const char = headerLine[i];
            if (char === '"') {
                if (inQuotes && headerLine[i + 1] === '"') {
                    value += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                columns.push(value.trim());
                value = '';
            } else {
                value += char;
            }
        }
        columns.push(value.trim());
        return columns.filter(Boolean);
    }

    // The columns a row cannot be imported without. Everything else may be left blank, so
    // highlighting these is the difference between a 30-item list and a five-item instruction.
    const MANDATORY_COLUMNS = ['category_id', 'product_type', 'type', 'name', 'image', 'price', 'product id'];

    function renderRequiredFields(type) {
        const config = bulkCsvConfig[type] || bulkCsvConfig.upload;
        const listElement = $('#csv_required_fields_list');
        const countElement = $('#csv_required_count');
        listElement.html('<span class="field-chip">Loading columns from the sample file...</span>');
        countElement.text('');

        // Cache-busted: the sample file is a static path, so a browser that had cached an earlier
        // version of it went on listing the old column names here long after the file changed.
        fetch(config.sampleUrl + (config.sampleUrl.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now())
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to access sample CSV.');
                }
                return response.text();
            })
            .then(function (csvContent) {
                // Strip a byte-order mark, otherwise it is glued to the first column's name.
                const firstLine = (csvContent.split(/\r?\n/)[0] || '').replace(/^\uFEFF/, '');
                const headers = parseCsvHeader(firstLine);
                if (!headers.length) {
                    throw new Error('Sample CSV headers are empty.');
                }
                const chips = headers.map(function (column) {
                    // Variant blocks are numbered (price_1, price_2), so compare on the stem.
                    const stem = column.replace(/_\d+$/, '').toLowerCase();
                    const required = MANDATORY_COLUMNS.indexOf(stem) !== -1;
                    return '<span class="field-chip' + (required ? ' is-required' : '') + '">'
                        + $('<div>').text(column).html() + '</span>';
                }).join('');
                listElement.html(chips);
                countElement.text(headers.length);
            })
            .catch(function () {
                listElement.html('<span class="field-chip">Unable to load the column list. Please use the sample file and instructions before uploading.</span>');
            });
    }

    function setBulkFileLinks(type) {
        const config = bulkCsvConfig[type] || bulkCsvConfig.upload;
        $('#sample_file_link')
            .attr('href', config.sampleUrl)
            .attr('download', config.sampleName)
            .html('<i class="fas fa-download"></i> ' + config.sampleLabel);

        $('#instructions_file_link')
            .attr('href', config.instructionsUrl)
            .attr('download', config.instructionsName)
            .html('<i class="fas fa-download"></i> ' + config.instructionsLabel);

        renderRequiredFields(type);
        // The defaults panel replaces columns that only exist in the upload sheet, so it has
        // nothing to set when the seller is updating existing products.
        $('#upload_defaults').toggle(type === 'upload');
    }

    // 2 = only these pincodes, 3 = everywhere except these. Both need a list; the other two
    // choices ("everywhere" / "not deliverable yet") would only make an empty box look required.
    function syncZipcodeVisibility() {
        var choice = $('#default_deliverable_type').val();
        $('#default_zipcodes_wrap').toggle(choice === '2' || choice === '3');
    }

    $('#default_deliverable_type').on('change', syncZipcodeVisibility);
    syncZipcodeVisibility();

    // Submitted as a throwaway form rather than fetched: the response is a file download, and a
    // real form POST lets the browser handle Content-Disposition itself. It also has to be a
    // separate form from #bulk_upload_form, whose submit handler is an AJAX upload.
    $('#download_template_btn').on('click', function () {
        var fields = ['default_cod_allowed', 'default_prices_inclusive_tax', 'default_is_returnable',
                      'default_cancelable_till', 'default_indicator', 'default_deliverable_type',
                      'default_deliverable_zipcodes', 'template_rows', 'template_variants'];
        var form = $('<form>', {
            method: 'POST',
            action: '<?= base_url('seller/product/bulk_upload_template') ?>'
        }).appendTo('body');
        $('<input>', {type: 'hidden', name: csrfName, value: csrfHash}).appendTo(form);
        fields.forEach(function (id) {
            $('<input>', {type: 'hidden', name: id, value: $('#' + id).val()}).appendTo(form);
        });
        form.trigger('submit').remove();
    });

    $('#type').on('change', function () {
        setBulkFileLinks($(this).val());
    });

    setBulkFileLinks('upload')

    // The endpoint echoes JSON, but a PHP warning can be printed in front of it. Take the last
    // JSON object in the body so the real message still reaches the seller.
    function parseUploadResponse(raw) {
        if (raw === null || raw === undefined) { return null; }
        var text = String(raw).trim();
        try {
            return JSON.parse(text);
        } catch (e) { /* fall through to the salvage below */ }

        var start = text.lastIndexOf('{');
        while (start !== -1) {
            var candidate = text.slice(start);
            try {
                return JSON.parse(candidate);
            } catch (e2) {
                start = text.lastIndexOf('{', start - 1);
            }
        }
        return null;
    }

    $('#bulk_upload_form').on('submit', function (e) {
    e.preventDefault();
    var type = $('#type').val();
    if (type != '') {
        var formdata = new FormData(this);
        formdata.append(csrfName, csrfHash);
        $.ajax({
            type: 'POST',
            data: formdata,
            url: $(this).attr('action'),
            // Deliberately 'text', not 'json'. A stray PHP notice printed ahead of the JSON
            // body used to fail jQuery's parse, sending every such upload to the error handler
            // and hiding the real per-row reason behind "Something went wrong".
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('#submit_btn').html('Please Wait...').attr('disabled', true);
            },
            success: function (raw) {
                var result = parseUploadResponse(raw);
                if (!result) {
                    $('#upload_result').show().removeClass('msg_success').addClass('msg_error').html('Upload failed and the server response could not be read. Please try again or contact support.');
                    $('#submit_btn').html('<i class="fas fa-cloud-upload-alt mr-1"></i>Submit').attr('disabled', false);
                    return;
                }
                if (result.csrfName) { csrfName = result.csrfName; }
                if (result.csrfHash) { csrfHash = result.csrfHash; }
                if (result.error == false) {
                    $('#upload_result').show().removeClass('msg_error').addClass('msg_success').html(result.message).delay(3000).fadeOut();
                } else {
                    $('#upload_result').show().removeClass('msg_success').addClass('msg_error').html(result.message).delay(3000).fadeOut();
                }
                $('#submit_btn').html('<i class="fas fa-cloud-upload-alt mr-1"></i>Submit').attr('disabled', false);
            },
            error: function () {
                $('#upload_result').show().removeClass('msg_success').addClass('msg_error').html('Something went wrong while uploading. Please try again.');
                $('#submit_btn').html('<i class="fas fa-cloud-upload-alt mr-1"></i>Submit').attr('disabled', false);
            }
        })
    } else {
        iziToast.error({
            message: 'Please select type',
        });
    }

});
</script>
