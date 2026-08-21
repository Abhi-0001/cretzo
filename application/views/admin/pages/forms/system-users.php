<div class="content-wrapper admin-system-user-form-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-user-shield mr-2 text-primary-theme"></i>Add/Edit System User</h4>
                    <p class="text-muted mb-0 small">Create or update an admin panel user and their module permissions.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">System Users</li>
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
                            <span class="header-icon bg-set mr-2"><i class="fas fa-user-shield"></i></span>
                            <h5 class="mb-0">System User Details</h5>
                        </div>
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/system_users/update_system_user'); ?>" method="POST" id="add_product_form" enctype="multipart/form-data">
                            <div class="card-body row">

                                <?php
                                if (isset($fetched_data[0]['id'])) { ?>
                                    <input type='hidden' name='edit_system_user' value="<?= html_escape($fetched_data[0]['id']) ?>">
                                <?php    }
                                ?>

                                <div class="<?= (isset($fetched_data[0]['id'])) ? 'col-md-12' : 'col-md-4' ?>">

                                    <!-- form start -->
                                    <div class="form-group">
                                        <label for="username" class="control-label">Username <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-md-12">
                                            <input type="text" class="form-control" name="username" id="username" value="<?= html_escape((isset($fetched_data[0]['username'])) ?  $fetched_data[0]['username'] : ' ') ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="mobile" class="control-label">Mobile <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-md-12">
                                            <input type="number" class="form-control" name="mobile" id="mobile" value="<?= html_escape((isset($fetched_data[0]['mobile'])) ?  $fetched_data[0]['mobile'] : ' ') ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="email" class="control-label">Email <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-md-12">
                                            <input type="email" class="form-control" name="email" id="email" value="<?= html_escape((isset($fetched_data[0]['email'])) ?  $fetched_data[0]['email'] : ' ') ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="password" class="control-label">Password <span class='text-danger text-sm'>*</span></label>
                                        <?php if (isset($fetched_data[0]['id'])) { ?>
                                            <span class='text-danger'>*Leave blank if there is no change</span>
                                        <?php } ?>
                                        <div class="col-md-12">
                                            <input type="password" class="form-control" name="password" id="password">
                                        </div>
                                    </div>
                                    <?php if (!isset($fetched_data[0]['id'])) { ?>
                                        <div class="form-group">
                                            <label for="confirm_password" class="control-label">Confirm Password <span class='text-danger text-sm'>*</span></label>
                                            <div class="col-md-12">
                                                <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="form-group">
                                        <label for="role" class="control-label">Role <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-md-12">
                                            <select class="form-control system-user-role" name="role">
                                                <option value=" ">---Select role---</option>
                                                <?php
                                                foreach ($user_roles as $key => $value) { ?>
                                                    <option value="<?= $key ?>" <?= (isset($fetched_data[0]['role']) &&  $fetched_data[0]['role'] == $key) ? "Selected" : "" ?>><?= ucwords(str_replace('_', ' ', $value)) ?></option>
                                                <?php
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php if (!isset($fetched_data[0]['id'])) { ?>
                                        <div class="d-flex justify-content-center">
                                            <div class="form-group" id="error_box">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-success" id="submit_btn">Add User</button>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class=" <?= (isset($fetched_data[0]['id'])) ? 'col-md-12' : 'col-md-8' ?> ">
                                    <?php

                                    if (isset($fetched_data[0]['id'])) {
                                        $user_permissions = json_decode((string)$fetched_data[0]['permissions'], 1);
                                    }

                                    $actions = [
                                        'create',
                                        'read',
                                        'update',
                                        'delete'
                                    ];
                                    ?>
                                    <table class="table table-responsive permission-table permission-table-styled <?= (isset($fetched_data[0]['role']) && $fetched_data[0]['role'] == 0) ? 'd-none' : '' ?>">
                                        <tr>
                                            <th>Module/Permissions</th>
                                            <?php foreach ($actions as $row) { ?>
                                                <th><?= ucfirst($row) ?></th>
                                            <?php }
                                            ?>
                                        </tr>
                                        <tbody>
                                            <?php
                                            foreach ($system_modules as $key => $value) {
                                                $flag = 0;
                                            ?>
                                                <tr>
                                                    <td><?= $key ?></td>
                                                    <?php for ($i = 0; $i < count($actions); $i++) {
                                                        //create,update,delete
                                                        $index = array_search($actions[$i], $value);
                                                        if ($index !== false) {
                                                            $checked = '';
                                                            if (isset($user_permissions)) {
                                                                if (isset($user_permissions[$key][$value[$index]])) {
                                                                    $checked = 'checked';
                                                                } else {
                                                                    $checked = '';
                                                                }
                                                            } else {
                                                                $checked = 'checked';
                                                            }
                                                    ?>
                                                            <td> <input type="checkbox" name="<?= 'permissions[' . $key . '][' . $value[$index] . ']' ?>" data-bootstrap-switch data-off-color="danger" class='system-users-switch' data-on-color="success" <?= $checked ?>></td>
                                                        <?php
                                                        } else { ?>
                                                            <td></td>
                                                        <?php   }
                                                        ?>
                                                    <?php } ?>
                                                </tr>
                                            <?php

                                            }

                                            ?>

                                        </tbody>
                                    </table>

                                    <?php if (isset($fetched_data[0]['id'])) { ?>
                                        <div class="d-flex justify-content-center">
                                            <div class="form-group" id="error_box">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-success" id="submit_btn">Update User</button>
                                        </div>
                                    <?php } ?>

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
    .admin-system-user-form-page .text-primary-theme { color: var(--color-orange); }

    .admin-system-user-form-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-system-user-form-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-system-user-form-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-system-user-form-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-system-user-form-page .permission-table-styled {
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
    }
    .admin-system-user-form-page .permission-table-styled tr:first-child th {
        background: #fafafa;
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        padding: 10px 12px;
        border-bottom: 2px solid rgba(0,0,0,0.06);
    }
    .admin-system-user-form-page .permission-table-styled tbody tr {
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .admin-system-user-form-page .permission-table-styled tbody tr:hover {
        background-color: var(--color-orange-light);
    }
    .admin-system-user-form-page .permission-table-styled td,
    .admin-system-user-form-page .permission-table-styled th {
        padding: 8px 12px;
        vertical-align: middle;
    }
    .admin-system-user-form-page .permission-table-styled td:first-child {
        font-weight: 600;
        text-transform: capitalize;
        color: var(--color-black, #333);
    }
</style>