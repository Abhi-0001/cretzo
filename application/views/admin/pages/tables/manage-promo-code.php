<div class="content-wrapper admin-manage-promo-code-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-tags mr-2 text-primary-theme"></i>Manage Promo Code</h4>
                    <p class="text-muted mb-0 small">Create and track discount codes offered to customers.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Manage Promo Code</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Manage Promo Code</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set"><i class="fas fa-tags"></i></span>
                                <h5 class="mb-0">Promo Codes</h5>
                            </div>
                            <a href="<?= base_url() . 'admin/promo-code/' ?>" class="btn btn-primary-theme btn-sm">
                                <i class="fas fa-plus mr-1"></i>Add Promo Code
                            </a>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/promo_code/view_promo_code') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-export-options='{
                            "fileName": "promocode-list",
                            "ignoreColumn": ["state"]
                            }' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                        <th data-field="promo_code" data-sortable="false" data-align='center'>Promo Code</th>
                                        <th data-field="message" data-sortable="true" data-align='center'>Message</th>
                                        <th data-field="start_date" data-sortable="true" data-align='center'>Start Date</th>
                                        <th data-field="end_date" data-sortable="true" data-align='center'>End Date</th>
                                        <th data-field="no_of_users" data-sortable="true" data-visible='false' data-align='center'>No .of users</th>
                                        <th data-field="min_order_amt" data-sortable="true" data-visible='false' data-align='center'>Minimum order amount</th>
                                        <th data-field="discount" data-sortable="true" data-align='center'>Discount</th>
                                        <th data-field="discount_type" data-sortable="true" data-align='center'>Discount type</th>
                                        <th data-field="max_discount_amt" data-sortable="true" data-visible='false' data-align='center'>Max discount amount</th>
                                        <th data-field="repeat_usage" data-sortable="true" data-visible='false' data-align='center'>Repeat usage</th>
                                        <th data-field="no_of_repeat_usage" data-sortable="true" data-visible='false' data-align='center'>No of repeat usage</th>
                                        <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                        <th data-field="is_cashback" data-sortable="true" data-align='center'>Is Cashback</th>
                                        <th data-field="list_promocode" data-sortable="true" data-align='center'>View Promocode</th>
                                        <th data-field="operate" data-sortable="false" data-align='center'>Actions</th>
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
    .admin-manage-promo-code-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-promo-code-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-promo-code-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-promo-code-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-promo-code-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-promo-code-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-promo-code-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-promo-code-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-promo-code-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-promo-code-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-promo-code-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-promo-code-page table.table thead th {
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
    .admin-manage-promo-code-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-promo-code-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-promo-code-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-promo-code-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-promo-code-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-promo-code-page .action-btn { display: inline-block; vertical-align: middle; }

    /* Message is the longest free-text column - give it real width so it wraps across
       fewer lines instead of stacking tall in a narrow column. */
    .admin-manage-promo-code-page table.table th:nth-child(3),
    .admin-manage-promo-code-page table.table td:nth-child(3) {
        min-width: 320px;
        white-space: normal;
        text-align: left;
    }
</style>
