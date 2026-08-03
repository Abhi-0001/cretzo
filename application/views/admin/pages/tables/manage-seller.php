<div class="content-wrapper admin-manage-seller-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-store mr-2 text-primary-theme"></i>Manage Sellers</h4>
                    <p class="text-muted mb-0 small">Everyone registered as a seller across the marketplace.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Seller</li>
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
                        <span class="header-icon bg-set"><i class="fas fa-store"></i></span>
                        <h5 class="mb-0">Sellers</h5>
                    </div>
                    <div class="d-flex align-items-center flex-wrap seller-header-actions">
                        <a href="javascript:void(0)" class="btn btn-outline-primary-theme btn-sm mb-2 update-seller-commission" title="If you found seller commission not crediting using cron job you can update seller commission from here!">
                            <i class="fas fa-sync-alt mr-1"></i>Update Seller Commission
                        </a>
                        <a href="<?= base_url('admin/sellers/manage-seller') ?>" class="btn btn-primary-theme btn-sm mb-2">
                            <i class="fas fa-plus mr-1"></i>Add Seller
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='seller_table' data-toggle="table" data-url="<?= base_url('admin/sellers/view_sellers') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="u.id" data-sort-order="DESC" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="name" data-sortable="false">Name</th>
                                <th data-field="email" data-sortable="false">Email</th>
                                <th data-field="mobile" data-sortable="true">Mobile No</th>
                                <th data-field="address" data-sortable="true" data-visible="false">Address</th>
                                <th data-field="balance" data-sortable="true">Balance</th>
                                <th data-field="rating" data-sortable="true">Rating</th>
                                <th data-field="store_name" data-sortable="true">Store Name</th>
                                <th data-field="store_url" data-sortable="true" data-visible="false">Store URL</th>
                                <th data-field="store_description" data-sortable="true" data-visible="false">Store Description</th>
                                <th data-field="account_number" data-sortable="true" data-visible="false">Account Number</th>
                                <th data-field="account_name" data-sortable="true" data-visible="false">Account Name</th>
                                <th data-field="bank_code" data-sortable="true" data-visible="false">Bank Code</th>
                                <th data-field="bank_name" data-sortable="true" data-visible="false">Bank Name</th>
                                <th data-field="latitude" data-sortable="true" data-visible="false">Latitude</th>
                                <th data-field="longitude" data-sortable="true" data-visible="false">Longitude</th>
                                <th data-field="tax_name" data-sortable="true" data-visible="false">Tax Name</th>
                                <th data-field="tax_number" data-sortable="true" data-visible="false">Tax Number</th>
                                <th data-field="pan_number" data-sortable="true" data-visible="false">Pan Number</th>
                                <th data-field="status" data-sortable="true">Status</th>
                                <th data-field="category_ids" data-sortable="true" data-visible="false">Category Ids</th>
                                <th data-field="logo" data-sortable="true">Logo</th>
                                <th data-field="national_identity_card" data-sortable="true" data-visible="false">National Identity Card</th>
                                <th data-field="address_proof" data-sortable="true" data-visible="false">Address Proof</th>
                                <th data-field="permissions" data-sortable="true" data-visible="false">Permissions</th>
                                <th data-field="date" data-sortable="true" data-visible="false">Date</th>
                                <th data-field="operate">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-manage-seller-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-seller-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-seller-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .admin-manage-seller-page .btn-outline-primary-theme {
        border: 1px solid var(--color-orange);
        color: var(--color-orange-dark);
        font-weight: 600;
        background: #fff;
    }
    .admin-manage-seller-page .btn-outline-primary-theme:hover { background: var(--color-orange); color: #fff; }

    .admin-manage-seller-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-seller-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-seller-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-seller-page .header-icon.bg-set { background: var(--color-orange); }
    .admin-manage-seller-page .seller-header-actions { gap: 8px; }

    .admin-manage-seller-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-seller-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-seller-page .fixed-table-toolbar .btn-group > .btn,
    .admin-manage-seller-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-manage-seller-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-manage-seller-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-manage-seller-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-seller-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-seller-page table.table thead th {
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
    .admin-manage-seller-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-seller-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-seller-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-seller-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-seller-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-seller-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
