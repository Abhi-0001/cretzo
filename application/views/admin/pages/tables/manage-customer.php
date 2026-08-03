<div class="content-wrapper admin-manage-customer-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-users mr-2 text-primary-theme"></i>View Customers</h4>
                    <p class="text-muted mb-0 small">Everyone registered as a customer on the storefront.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Customers</li>
                    </ol>
                </div>
            </div>
            <div class="modal fade " tabindex="-1" role="dialog" aria-hidden="true" id='customer-address-modal'>
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="far fa-address-book mr-2 text-primary-theme"></i>View Address Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <table class='table-striped' id='customer-address-table' data-toggle="table" data-url="<?= base_url('admin/customer/get_address') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="queryParams">
                                        <thead>
                                            <tr>
                                                <th data-field="id" data-sortable="true" data-align='center'>Id</th>
                                                <th data-field="name" data-sortable="true" data-align='center'>User Name</th>
                                                <th data-field="type" data-sortable="true" data-align='center'>Type</th>
                                                <th data-field="mobile" data-sortable="true" data-align='center'>mobile</th>
                                                <th data-field="alternate_mobile" data-sortable="true" data-align='center'>Alternate mobile</th>
                                                <th data-field="address" data-sortable="false" data-visible="false" data-align='center'>Address</th>
                                                <th data-field="landmark" data-sortable="true" data-align='center'>Landmark</th>
                                                <th data-field="area" data-sortable="true" data-align='center'>Area</th>
                                                <th data-field="city" data-sortable="true" data-align='center'>City</th>
                                                <th data-field="state" data-sortable="true" data-align='center'>State</th>
                                                <th data-field="pincode" data-sortable="true" data-align='center'>Pincode</th>
                                                <th data-field="country" data-sortable="true" data-align='center'>Country</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set"><i class="fas fa-users"></i></span>
                                <h5 class="mb-0">Customers</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/customer/view_customer') ?>" data-side-pagination="server" data-click-to-select="true" data-pagination="true" data-id-field="id" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="#toolbar" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="name" data-sortable="true">Name</th>
                                        <th data-field="email" data-sortable="true">Email</th>
                                        <th data-field="mobile" data-sortable="true">Mobile No</th>
                                        <th data-field="balance" data-sortable="true">Balance</th>
                                        <th data-field="street" data-sortable="true">Street</th>
                                        <th data-field="area" data-sortable="true">Area</th>
                                        <th data-field="city" data-sortable="true">City</th>
                                        <th data-field="date" data-sortable="true">Date</th>
                                        <th data-field="status" data-sortable="true">Status</th>
                                        <th data-field="actions" data-sortable="false">Actions</th>
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
    .admin-manage-customer-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-customer-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-customer-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-customer-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-customer-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-customer-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-customer-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-customer-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-customer-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-customer-page table.table thead th {
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
    .admin-manage-customer-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-customer-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-customer-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-customer-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-customer-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-customer-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
