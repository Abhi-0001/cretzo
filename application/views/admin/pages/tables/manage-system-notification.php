<div class="content-wrapper admin-system-notification-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-bell mr-2 text-primary-theme"></i>System Notifications</h4>
                    <p class="text-muted mb-0 small">Events raised for admin attention - new orders, seller verification requests, and more.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">System Notifications</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-bell"></i></span>
                    <h5 class="mb-0">All Notifications</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="filter-label"><i class="fas fa-filter mr-1"></i>Status</label>
                            <select id="message_type" name="message_type" class="form-control">
                                <option value="">All Messages</option>
                                <option value="1">Read</option>
                                <option value="0">Un-Read</option>
                            </select>
                        </div>
                    </div>

                    <table class='table-striped' id='system_notofication_table' data-toggle="table"
                        data-url="<?= base_url('admin/Notification_settings/get_notifications_data') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="read_by" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]'
                        data-export-options='{"fileName": "system-notifications", "ignoreColumn": ["operate"]}'
                        data-query-params="noti_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="title" data-sortable="true" data-align='center'>Title</th>
                                <th data-field="message" data-sortable="false" data-align='center'>Message</th>
                                <th data-field="type" data-sortable="true" data-align='center'>Type</th>
                                <th data-field="type_id" data-sortable="false" data-align='center' data-visible='false'>Type Id</th>
                                <th data-field="read_by" data-sortable="true" data-align='center'>Status</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-system-notification-page .text-primary-theme { color: var(--color-orange); }

    .admin-system-notification-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-system-notification-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-system-notification-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-system-notification-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-system-notification-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .admin-system-notification-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }

    .admin-system-notification-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-system-notification-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-system-notification-page .fixed-table-toolbar .btn-group > .btn,
    .admin-system-notification-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-system-notification-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-system-notification-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-system-notification-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-system-notification-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-system-notification-page table.table thead th {
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
    .admin-system-notification-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-system-notification-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-system-notification-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-system-notification-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    /* Same action-button wrapping fix applied elsewhere - these rows can carry up to three
       icons (Mark as Read / View / Delete) and would otherwise stack vertically. */
    .admin-system-notification-page td:has(.action-btn) { white-space: nowrap; }
    .admin-system-notification-page .action-btn { display: inline-block; vertical-align: middle; }
</style>

<script>
    // The status filter is already correctly wired to noti_query_params in custom.js
    // (reads #message_type and passes it through) - it just never actually re-queried the
    // table when changed, requiring a full page refresh to apply the filter at all.
    $(document).on('change', '#message_type', function () {
        $('#system_notofication_table').bootstrapTable('refresh');
    });
</script>
