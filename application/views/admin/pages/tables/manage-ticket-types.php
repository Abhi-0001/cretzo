<div class="content-wrapper admin-manage-ticket-types-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-tags mr-2 text-primary-theme"></i>Manage Ticket Types</h4>
                    <p class="text-muted mb-0 small">Categories customers pick from when raising a support ticket.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/tickets') ?>">Tickets</a></li>
                        <li class="breadcrumb-item active">Ticket Types</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <!-- Dead leftover markup: nothing on this page ever opens this modal (the edit link
                 below navigates to a real page instead). -->
            <div id="attribute_value_id" class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="editTicketTypeLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editTicketTypeLabel">Edit Ticket Type</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-0"></div>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-tags"></i></span>
                        <h5 class="mb-0">Ticket Types</h5>
                    </div>
                    <a href="<?= base_url('admin/tickets/manage_ticket_types') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Ticket Type
                    </a>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='category_table' data-toggle="table"
                        data-url="<?= base_url('admin/tickets/view_ticket_type_list') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]'
                        data-export-options='{"fileName": "ticket-type-list", "ignoreColumn": ["operate"]}'
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="title" data-sortable="true" data-align='center'>Title</th>
                                <th data-field="date_created" data-sortable="true" data-align='center'>Created</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-manage-ticket-types-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-ticket-types-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-ticket-types-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-ticket-types-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-ticket-types-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-ticket-types-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-ticket-types-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-ticket-types-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-ticket-types-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-ticket-types-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-ticket-types-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-ticket-types-page table.table thead th {
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
    .admin-manage-ticket-types-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-ticket-types-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-ticket-types-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-ticket-types-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-ticket-types-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-ticket-types-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
