<div class="content-wrapper admin-manage-notifications-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-paper-plane mr-2 text-primary-theme"></i>Send Notification</h4>
                    <p class="text-muted mb-0 small">Push a notification out to all users or a specific one.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Send Notification</li>
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
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set"><i class="fas fa-paper-plane"></i></span>
                            <h5 class="mb-0">Compose Notification</h5>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/Notification_settings/send_notifications'); ?>" method="POST" id="add_product_form" enctype="multipart/form-data">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="type" class="control-label">Send to <span class='text-danger text-sm'>*</span></label>
                                    <?php // "All Users" resolved to the `members` group only, so there was no way
                                          // to notify sellers as a group at all. The two role audiences are new. ?>
                                    <select name="send_to" id="send_to" class="form-control type_event_trigger" required="">
                                        <option value="all_users">All Users (customers &amp; sellers)</option>
                                        <option value="all_customers">All Customers</option>
                                        <option value="all_sellers">All Sellers</option>
                                        <option value="specific_user">Specific User</option>
                                    </select>
                                </div>
                                <!-- for users -->
                                <div class="form-group row notification-users d-none">
                                    <label for="user_id" class="control-label"> Users <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-12">
                                        <input type="hidden" name="user_id" id="noti_user_id" value="">
                                        <select name="select_user_id[]" class="search_user w-100" multiple data-placeholder=" Type to search and select users" onload="multiselect()">
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="type" class="control-label">Type <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-12">
                                        <select name="type" id="type" class="form-control type_event_trigger" required="">
                                            <option value=" ">Select Type</option>
                                            <option value="default">Default</option>
                                            <option value="categories">Category</option>
                                            <option value="products">Product</option>
                                            <option value="notification_url">Notification URL</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="type_add_html">
                                    <!-- for category -->
                                    <div class="form-group notification-categories d-none">
                                        <label for="category_id"> Categories <span class='text-danger text-sm'>*</span></label>
                                        <select name="category_id" class="form-control">
                                            <option value="">Select category </option>
                                            <?php
                                            if (!empty($categories)) {
                                                foreach ($categories as $row) {
                                            ?>
                                                    <option value="<?= $row['id'] ?>"> <?= html_escape($row['name']) ?></option>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <!-- for notification url -->
                                    <div class="form-group notification-url d-none">

                                        <label for="notification_url"> Link <span class='text-danger text-sm'>*</span></label>
                                        <input type="text" class="form-control" placeholder="https://example.com" name="link" value="">
                                    </div>
                                    <!-- for products -->
                                    <div class="form-group row notification-products d-none">
                                        <label for="product_id" class="control-label">Products <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-md-12">
                                            <select name="product_id" class="search_admin_product w-100" data-placeholder=" Type to search and select products" onload="multiselect()">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="title" class="control-label ">Title <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="title" id="title" value="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="message" class="control-label">Message <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-12">
                                        <textarea name='message' class="form-control"></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <input type="checkbox" name="image_checkbox" id="image_checkbox">
                                        <span>Include Image</span>
                                    </div>
                                    <div class="col-md-12 d-none include_image">
                                        <label for="message" class="control-label">Image <small>(Recommended Size : 80 x 80 pixels)</small></label>
                                        <div class="col-sm-10">
                                            <div class='col-md-3'><a class="uploadFile img btn btn-primary-theme text-white btn-sm" data-input='image' data-isremovable='1' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                            <div class="container-fluid row image-upload-section">
                                                <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-primary-theme" id="submit_btn">Send Notification</button>
                                </div>

                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">
                                    </div>
                                </div>
                        </form>
                    </div>
                    <!--/.card-->
                </div>
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set"><i class="fas fa-history"></i></span>
                            <h5 class="mb-0">Notification History</h5>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/Notification_settings/get_notification_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="title" data-sortable="true">Title</th>
                                        <th data-field="type" data-sortable="true">Type</th>
                                        <th data-field="image" data-sortable="false" class="col-md-5">Image</th>
                                        <th data-field="link" data-sortable="false" class="col-md-5">Link</th>
                                        <th data-field="message" data-sortable="true">Message</th>
                                        <th data-field="send_to" data-sortable="true">Send to</th>
                                        <th data-field="users_id" data-sortable="false">users id</th>
                                        <th data-field="operate" data-sortable="false">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div><!-- .card-body -->
                    </div><!-- .card -->
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-manage-notifications-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-notifications-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-notifications-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-notifications-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-notifications-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-notifications-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-notifications-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-notifications-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-notifications-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-notifications-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-notifications-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-notifications-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-manage-notifications-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-notifications-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-notifications-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-notifications-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-notifications-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-notifications-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
