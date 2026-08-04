<div class="content-wrapper admin-purchase-code-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-key mr-2 text-primary-theme"></i>Purchase Code / System Registration</h4>
                    <p class="text-muted mb-0 small">Register your web and app purchase codes to activate this system.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Purchase Code</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-key"></i></span>
                                <h5 class="mb-0">System Registration</h5>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/purchase-code/validator'); ?>" method="POST" enctype="multipart/form-data">
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="purchase_code" class="col-sm-2 col-form-label">eShop Purchase Code for web<span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="purchase_code" placeholder="Enter your purchase code here" name="web_purchase_code" value="">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="app_purchase_code" class="col-sm-2 col-form-label">eShop Purchase Code for app<span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="app_purchase_code" placeholder="Enter your purchase code here" name="app_purchase_code" value="">
                                    </div>
                                </div>
                                <div class="form-group mt-3">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn">Register Now</button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
                                </div>
                            </div>
                        </form>
                        <?php $doctor_brown = get_settings('doctor_brown', true);
                        if (!empty($doctor_brown) && isset($doctor_brown['code_bravo'])) { ?>
                            <div class="alert alert-success m-2">
                                Your system is successfully registered with us! Enjoy selling online!
                            </div>
                        <?php } ?>
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
    .admin-purchase-code-page .text-primary-theme { color: var(--color-orange); }

    .admin-purchase-code-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-purchase-code-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-purchase-code-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-purchase-code-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-purchase-code-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-purchase-code-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-purchase-code-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-purchase-code-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }
</style>