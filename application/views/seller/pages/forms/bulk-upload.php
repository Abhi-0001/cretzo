<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Bulk upload</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Products</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <ul>
                            <li>Read and follow instructions carefully while preparing data</li>
                            <li>Download and save the sample file to reduce errors</li>
                            <li>For adding bulk products file should be .csv format</li>
                            <li>You can copy image path from media section</li>
                            <li><b>Make sure you entered valid data as per instructions before proceed</b></li>
                        </ul>
                    </div>
                    <div class="card card-info">

                        <!-- form start -->
                        <form class="form-horizontal" action="<?= base_url('seller/product/process_bulk_upload'); ?>" method="POST" id="bulk_upload_form" enctype="multipart/form-data">
                            <div class="card-body">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type" class="col-form-label">Type <small>[upload/update]</small> <span class='text-danger text-sm'>*</span></label></label>
                                        <select class='form-control' name='type' id='type'>
                                            <option value=''>Select</option>
                                            <option value='upload'>Upload</option>
                                            <option value='update'>Update</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="file">File <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-4">
                                        <input type="file" name="upload_file" class="form-control" accept=".csv" />
                                    </div>

                                </div>
                                <div class="form-group row">
                                    <div class="card-body pad">
                                        <div class="form-group">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-success" id="submit_btn">Submit</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="card-body pad">
                                        <div class="form-group">
                                        <a id="sample_file_link" href="<?= base_url('uploads/seller-product-bulk-upload-sample.csv') ?>" class="btn btn-info" download="seller-product-bulk-upload-sample.csv">Bulk upload sample file <i class="fas fa-download"></i></a>
                                            <a id="instructions_file_link" href="<?= base_url('uploads/seller-bulk-upload-instructions.txt') ?>" class="btn btn-primary" download="seller-bulk-upload-instructions.txt">Bulk upload instructions <i class="fas fa-download"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="card-body pad pt-0">
                                        <div class="alert alert-light border mb-0">
                                            <strong>Required details to verify before uploading:</strong>
                                            <ul class="mb-0 mt-2 pl-3" id="csv_required_fields_list"></ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center form-group">
                                    <div id="upload_result" class="p-3"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!--/.card-->
                </div>
                <!--/.col-md-12-->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

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
            .html(config.sampleLabel + ' <i class="fas fa-download"></i>');

        $('#instructions_file_link')
            .attr('href', config.instructionsUrl)
            .attr('download', config.instructionsName)
            .html(config.instructionsLabel + ' <i class="fas fa-download"></i>');

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
                $('#submit_btn').html('Submit').attr('disabled', false);
            }
        })
    } else {
        iziToast.error({
            message: 'Please select type',
        });
    }

});
</script>