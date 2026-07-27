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

                        <div class="bulk-file-links mb-3">
                            <a id="sample_file_link" href="<?= base_url('uploads/seller-product-bulk-upload-sample.csv') ?>" class="btn btn-outline-primary" download="seller-product-bulk-upload-sample.csv">
                                <i class="fas fa-download"></i> Bulk upload sample file
                            </a>
                            <a id="instructions_file_link" href="<?= base_url('uploads/seller-bulk-upload-instructions.txt') ?>" class="btn btn-outline-secondary" download="seller-bulk-upload-instructions.txt">
                                <i class="fas fa-download"></i> Bulk upload instructions
                            </a>
                        </div>

                        <div class="bulk-required-fields mb-3">
                            <strong><i class="fas fa-clipboard-check mr-1"></i>Required details to verify before uploading:</strong>
                            <ul class="mb-0 mt-2" id="csv_required_fields_list"></ul>
                        </div>

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

    .bulk-upload-page .bulk-required-fields {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 14px 18px;
    }
    .bulk-upload-page .bulk-required-fields ul {
        padding-left: 1.2rem;
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

    function renderRequiredFields(type) {
        const config = bulkCsvConfig[type] || bulkCsvConfig.upload;
        const listElement = $('#csv_required_fields_list');
        listElement.html('<li>Loading required fields from sample CSV...</li>');

        fetch(config.sampleUrl)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to access sample CSV.');
                }
                return response.text();
            })
            .then(function (csvContent) {
                const firstLine = csvContent.split(/\r?\n/)[0] || '';
                const headers = parseCsvHeader(firstLine);
                if (!headers.length) {
                    throw new Error('Sample CSV headers are empty.');
                }
                const listItems = headers.map(function (column) {
                    return '<li>' + $('<div>').text(column).html() + '</li>';
                }).join('');
                listElement.html(listItems);
            })
            .catch(function () {
                listElement.html('<li>Unable to load CSV headers. Please use the sample file and instructions before upload.</li>');
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
    }

    $('#type').on('change', function () {
        setBulkFileLinks($(this).val());
    });

    setBulkFileLinks('upload')

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
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('#submit_btn').html('Please Wait...').attr('disabled', true);
            },
            success: function (result) {
                csrfName = result.csrfName;
                csrfHash = result.csrfHash;
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
