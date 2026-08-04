<div class="content-wrapper admin-updater-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-cloud-upload-alt mr-2 text-primary-theme"></i>Auto Update (Version <?= $system['db_current_version'] ?>)</h4>
                    <p class="text-muted mb-0 small">Upload official update packages to upgrade this system.</p>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="alert update-warning-callout">
                <div class="alert-title"><i class="fas fa-exclamation-triangle mr-1"></i>NOTE:</div>
                Make sure you update system in sequence. Like if you have current version 1.0 and you want to update this version to 1.5 then you can't update it directly. You must have to update in sequence like first update version 1.2 then 1.3 and 1.4 so on.
            </div>
            <?php if ($system['file_current_version'] == false) { ?>

            <?php } elseif ($system['is_updatable'] == false) {  ?>

            <?php } else { ?>

            <?php } ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-cloud-upload-alt"></i></span>
                                <h5 class="mb-0">Upload Update Package</h5>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/updater/upload_update_file'); ?>" method="POST" enctype="multipart/form-data">
                            <div class="card-body">
                                <div class="dropzone" id="system-update-dropzone">
                                </div>
                                <div class="form-group pt-3">
                                    <button class="btn btn-success" id="system_update_btn">Update The System</button>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">
                                    </div>
                                </div>
                            </div>
                        </form>
                        </div>
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
    .admin-updater-page .text-primary-theme { color: var(--color-orange); }

    .admin-updater-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-updater-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-updater-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-updater-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-updater-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-updater-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-updater-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-updater-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }

    .admin-updater-page .update-warning-callout {
        background: var(--color-orange-light, #fff3e0);
        border: 1px solid var(--color-orange);
        border-left: 4px solid var(--color-orange);
        color: #7a4a00;
        border-radius: 8px;
    }
    .admin-updater-page .update-warning-callout .alert-title {
        font-weight: 700;
        color: var(--color-orange-dark, #b35c00);
        margin-bottom: 4px;
    }
</style>