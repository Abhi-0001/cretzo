<div class="content-wrapper admin-manage-address-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="far fa-address-book mr-2 text-primary-theme"></i>Customer Address</h4>
                    <p class="text-muted mb-0 small">Every saved address across all customers.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Customer Address</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content address-section">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set"><i class="far fa-address-book"></i></span>
                                <h5 class="mb-0">Addresses</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type='hidden' id='address_user_id' value='<?=(isset($view_id) && !empty($view_id)) ? $view_id : '' ?>'>
                            <table class='table-striped' id='customer-address-table' data-toggle="table" data-url="<?= base_url('admin/customer/get_address') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="address_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">Id</th>
                                        <th data-field="name" data-sortable="true">User Name</th>
                                        <th data-field="type" data-sortable="true">Type</th>
                                        <th data-field="mobile" data-sortable="true">mobile</th>
                                        <th data-field="alternate_mobile" data-sortable="true">Alternate mobile</th>
                                        <th data-field="address" data-sortable="false" data-visible="false">Address</th>
                                        <th data-field="landmark" data-sortable="true">Landmark</th>
                                        <th data-field="area" data-sortable="true">Area</th>
                                        <th data-field="city" data-sortable="true">City</th>
                                        <th data-field="state" data-sortable="true">State</th>
                                        <th data-field="pincode" data-sortable="true">Pincode</th>
                                        <th data-field="country" data-sortable="true">Country</th>
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
    .admin-manage-address-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-address-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-address-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-address-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-address-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-address-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-address-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-address-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-address-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-address-page table.table thead th {
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
    .admin-manage-address-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-address-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-address-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-address-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }
</style>
