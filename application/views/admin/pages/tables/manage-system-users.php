<div class="content-wrapper admin-system-users-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-users-cog mr-2 text-primary-theme"></i>System Users</h4>
                    <p class="text-muted mb-0 small">Admin panel users and their role-based access permissions.</p>
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

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-users-cog"></i></span>
                        <h5 class="mb-0">System Users</h5>
                    </div>
                    <a href="<?= base_url() . 'admin/system_users/add-system-users' ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add User
                    </a>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/system_users/view_system_users') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="username" data-sortable="false">Username</th>
                                <th data-field="mobile" data-sortable="false">Mobile</th>
                                <th data-field="email" data-sortable="false">Email</th>
                                <th data-field="role" data-sortable="false">Role</th>
                                <th data-field="operate" data-sortable="false">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </section>

    <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit System User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-system-users-page .text-primary-theme { color: var(--color-orange); }

    .admin-system-users-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-system-users-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-system-users-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-system-users-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-system-users-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-system-users-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-system-users-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-system-users-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-system-users-page .fixed-table-toolbar .btn-group > .btn,
    .admin-system-users-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-system-users-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-system-users-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-system-users-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-system-users-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-system-users-page .fixed-table-toolbar .columns .btn,
    .admin-system-users-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-system-users-page .fixed-table-toolbar .columns .btn:hover,
    .admin-system-users-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-system-users-page .fixed-table-container { border: none; }
    .admin-system-users-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-system-users-page table.table thead th {
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
    .admin-system-users-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-system-users-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-system-users-page .action-btn { border-radius: 6px; }
    .admin-system-users-page .badge { font-size: 12px; padding: 5px 10px; border-radius: 20px; font-weight: 600; }

    .admin-system-users-page .fixed-table-pagination { margin-top: 12px; }
    .admin-system-users-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-system-users-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-system-users-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>
