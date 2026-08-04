<div class="content-wrapper admin-firebase-settings-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-fire mr-2 text-primary-theme"></i>Firebase Settings</h4>
                    <p class="text-muted mb-0 small">Configure the Firebase project credentials used by the app.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Firebase Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-fire"></i></span>
                                <h5 class="mb-0">Firebase Credentials</h5>
                            </div>
                        </div>
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/web-setting/store_firebase') ?>" method="POST" id="system_setting_form" enctype="multipart/form-data">
                            <div class="card-body pt-3">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="apiKey">apiKey <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="apiKey" value="<?= (isset($firebase_settings['apiKey'])) ? html_escape($firebase_settings['apiKey']) : '' ?>" placeholder="apiKey" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="authDomain">authDomain <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="authDomain" value="<?= (isset($firebase_settings['authDomain'])) ? html_escape($firebase_settings['authDomain']) : '' ?>" placeholder="authDomain" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="databaseURL">databaseURL <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="databaseURL" value="<?= (isset($firebase_settings['databaseURL'])) ? html_escape($firebase_settings['databaseURL']) : '' ?>" placeholder="databaseURL" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="projectId">projectId <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="projectId" value="<?= (isset($firebase_settings['projectId'])) ? html_escape($firebase_settings['projectId']) : '' ?>" placeholder="projectId" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="storageBucket">storageBucket <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="storageBucket" value="<?= (isset($firebase_settings['storageBucket'])) ? html_escape($firebase_settings['storageBucket']) : '' ?>" placeholder="storageBucket" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="messagingSenderId">messagingSenderId <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="messagingSenderId" value="<?= (isset($firebase_settings['messagingSenderId'])) ? html_escape($firebase_settings['messagingSenderId']) : '' ?>" placeholder="messagingSenderId" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="appId">appId <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="appId" value="<?= (isset($firebase_settings['appId'])) ? html_escape($firebase_settings['appId']) : '' ?>" placeholder="appId" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="measurementId">measurementId <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="measurementId" value="<?= (isset($firebase_settings['measurementId'])) ? html_escape($firebase_settings['measurementId']) : '' ?>" placeholder="measurementId" />
                                    </div>
                                    <div class="form-group">
                                        <button type="reset" class="btn btn-warning">Reset</button>
                                        <button type="submit" class="btn btn-success" id="submit_btn">Update Settings</button>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-firebase-settings-page .text-primary-theme { color: var(--color-orange); }

    .admin-firebase-settings-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-firebase-settings-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-firebase-settings-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-firebase-settings-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-firebase-settings-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-firebase-settings-page .btn-success:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
</style>