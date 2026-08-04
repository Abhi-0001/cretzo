<div class="content-wrapper admin-notification-settings-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-bell mr-2 text-primary-theme"></i>Notification Settings (FCM/VAPID)</h4>
                    <p class="text-muted mb-0 small">Push notification keys used to send alerts to app and web users.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Notification Settings</li>
                    </ol>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-key"></i></span>
                                <h5 class="mb-0">Push Notification Keys</h5>
                            </div>
                        </div>
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/Notification_settings/update_notification_settings'); ?>" method="POST" id="payment_setting_form" enctype="multipart/form-data">
                            <div class="card-body pt-3">
                                <div class="form-group">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="fcm_server_key">FCM Server Key : </label>
                                            <textarea class="form-control" name="fcm_server_key" placeholder='FCM Server Key' rows="5"><?= html_escape($fcm_server_key) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="vap_id_Key">Vap Id Key : </label>
                                            <textarea class="form-control" name="vap_id_Key" placeholder='Vap Id Key ' rows="5"><?= html_escape($vap_id_Key) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">
                                        <div class="card text-white d-none mb-3">
                                            <div class="card-header"></div>
                                            <div class="card-body"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn">Update Notification Settings</button>
                                </div>

                                <div class="d-flex justify-content-center ">
                                    <div id="error_box">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!--/.card-->
                </div>
                <!--/.col-md-12-->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-notification-settings-page .text-primary-theme { color: var(--color-orange); }

    .admin-notification-settings-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-notification-settings-page .btn-success:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }

    .admin-notification-settings-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-notification-settings-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-notification-settings-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-notification-settings-page .header-icon.bg-set { background: var(--color-orange); }
</style>