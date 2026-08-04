<div class="content-wrapper admin-location-bulk-upload-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-file-upload mr-2 text-primary-theme"></i>Location Bulk Upload</h4>
                    <p class="text-muted mb-0 small">Bulk create or update Zipcodes, Cities, or Areas from a CSV file.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Location</li>
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
                            <!-- Was "For adding bulk products file should be .csv format" / "You
                                 can copy image path from media section" - copy-pasted boilerplate
                                 from the Product bulk-upload page; this page uploads Zipcodes,
                                 Cities, and Areas, none of which have an image field at all. -->
                            <li>For bulk Zipcode/City/Area uploads, the file should be .csv format</li>
                            <li>Choose "Upload" to create new rows, or "Update" to edit existing rows by id</li>
                            <li><b>Make sure you entered valid data as per instructions before proceed</b></li>
                        </ul>
                    </div>
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-file-csv"></i></span>
                                <h5 class="mb-0">Upload File</h5>
                            </div>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal" action="<?= base_url('admin/area/process_bulk_upload'); ?>" method="POST" id="location_bulk_upload_form">
                            <div class="card-body pt-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type" class="col-form-label font-weight-bold">Type <small>[upload/update]</small> <span class='text-danger text-sm'>*</span></label>
                                        <select class='form-control' name='type' id='type'>
                                            <option value=''>Select</option>
                                            <option value='upload'>Upload</option>
                                            <option value='update'>Update</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="location_type" class="col-form-label font-weight-bold">Location Type <small>[Zipcodes/Cities/Areas]</small> <span class='text-danger text-sm'>*</span></label>
                                        <select class='form-control' name='location_type' id='location_type'>
                                            <option value=''>Select</option>
                                            <option value='zipcode'>Zipcodes</option>
                                            <option value='city'>Cities</option>
                                            <option value='area'>Areas</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="upload_file" class="font-weight-bold">File <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-4">
                                        <input type="file" name="upload_file" id="upload_file" class="form-control" accept=".csv" />
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
                                        <div class="form-group zipcode_samples">
                                            <a href="<?= base_url('uploads/zipcodes-bulk-upload-sample.csv') ?>" class="btn btn-info mt-1 mb-1 col-md-3" download="zipcodes-bulk-upload-sample.csv">Zipcode Bulk upload sample file <i class="fas fa-download"></i></a>
                                            <a href="<?= base_url('uploads/zipcodes-bulk-update-sample.csv') ?>" class="btn btn-info mt-1 mb-1 col-md-3" download="zipcodes-bulk-update-sample.csv">Zipcode Bulk update sample file <i class="fas fa-download"></i></a>
                                        </div>
                                        <div class="form-group city_samples">
                                            <a href="<?= base_url('uploads/cities-bulk-upload-sample.csv') ?>" class="btn btn-info  mt-1 mb-1 col-md-3" download="cities-bulk-upload-sample.csv">Cities Bulk upload sample file <i class="fas fa-download"></i></a>
                                            <a href="<?= base_url('uploads/cities-bulk-update-sample.csv') ?>" class="btn btn-info  mt-1 mb-1 col-md-3" download="cities-bulk-update-sample.csv">Cities Bulk update sample file <i class="fas fa-download"></i></a>
                                        </div>
                                        <div class="form-group area_samples">
                                            <a href="<?= base_url('uploads/areas-bulk-upload-sample.csv') ?>" class="btn btn-info mt-1 mb-1 col-md-3" download="areas-bulk-upload-sample.csv">Area Bulk upload sample file <i class="fas fa-download"></i></a>
                                            <a href="<?= base_url('uploads/areas-bulk-update-sample.csv') ?>" class="btn btn-info  mt-1 mb-1 col-md-3" download="areas-bulk-update-sample.csv">Area Bulk update sample file <i class="fas fa-download"></i></a>
                                        </div>
                                        <div class="form-group">
                                            <a href="<?= base_url('uploads/location-bulk-instructions.txt') ?>" class="btn btn-primary" download="location-bulk-instructions.txt">Location Bulk instructions <i class="fas fa-download"></i></a>
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

<style>
    .admin-location-bulk-upload-page .text-primary-theme { color: var(--color-orange); }

    .admin-location-bulk-upload-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-location-bulk-upload-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }

    .admin-location-bulk-upload-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-location-bulk-upload-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-location-bulk-upload-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-location-bulk-upload-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-location-bulk-upload-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 0.2rem rgba(230,126,34,0.15); }
</style>
