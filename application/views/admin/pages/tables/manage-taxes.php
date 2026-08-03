<div class="content-wrapper admin-taxes-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-percentage mr-2 text-primary-theme"></i>Manage Taxes</h4>
                    <p class="text-muted mb-0 small">Tax rates available to assign to products across the marketplace.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Taxes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-percentage"></i></span>
                        <h5 class="mb-0">Tax Rates</h5>
                    </div>
                    <a href="<?= base_url('admin/taxes/') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Tax
                    </a>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' id="taxes_table" data-toggle="table"
                        data-url="<?= base_url('admin/taxes/get_tax_list') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "tax-list", "ignoreColumn": ["operate"]}'
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="title" data-sortable="true">Title</th>
                                <th data-field="percentage" data-sortable="true" data-align='center'>Percentage</th>
                                <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>

    <!-- Edit modal. The shared edit_btn handler in custom.js loads the edit form into
         .edit-modal-lg .modal-body, so this markup has to stay on the page. -->
    <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tax</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-taxes-page .text-primary-theme { color: var(--color-orange); }

    .admin-taxes-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-taxes-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-taxes-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-taxes-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-taxes-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-taxes-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-taxes-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-taxes-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-taxes-page .fixed-table-toolbar .btn-group > .btn,
    .admin-taxes-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-taxes-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-taxes-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-taxes-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-taxes-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-taxes-page .fixed-table-toolbar .columns .btn,
    .admin-taxes-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-taxes-page .fixed-table-toolbar .columns .btn:hover,
    .admin-taxes-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-taxes-page .fixed-table-container { border: none; }
    .admin-taxes-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-taxes-page table.table thead th {
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
    .admin-taxes-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-taxes-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-taxes-page .action-btn { border-radius: 6px; }
    .admin-taxes-page .badge { font-size: 12px; padding: 5px 10px; border-radius: 20px; font-weight: 600; }

    .admin-taxes-page .fixed-table-pagination { margin-top: 12px; }
    .admin-taxes-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-taxes-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-taxes-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>
