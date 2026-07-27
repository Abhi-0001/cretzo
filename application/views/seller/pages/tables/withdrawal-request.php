<div class="content-wrapper withdrawal-request-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-money-bill-wave mr-2 text-primary-theme"></i>Withdrawal Requests</h4>
                    <p class="text-muted mb-0 small">Your wallet withdrawal requests and their approval status.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Withdrawal Requests</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-money-bill-wave"></i></span>
                        <h5 class="mb-0">Your Requests</h5>
                    </div>
                    <a href="<?= base_url() . 'seller/payment-request/send-withdrawal-request' ?>" class="btn btn-primary-theme btn-sm"><i class="fas fa-plus mr-1"></i>Send Withdrawal Request</a>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='payment_request_table' data-toggle="table" data-url="<?= base_url('seller/payment-request/view_withdrawal_request_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="pr.id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="user_name" data-sortable="false">Username</th>
                                <th data-field="payment_type" data-sortable="true">Type</th>
                                <th data-field="payment_address" data-sortable="false">Payment Address</th>
                                <th data-field="amount_requested" data-sortable="false">Amount Requested</th>
                                <th data-field="remarks" data-sortable="false">Remarks</th>
                                <th data-field="status" data-sortable="false">Status</th>
                                <th data-field="date_created" data-sortable="false">Date Created</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .withdrawal-request-page .text-primary-theme { color: var(--color-orange); }

    .withdrawal-request-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .withdrawal-request-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .withdrawal-request-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        margin-right: 10px;
    }
    .withdrawal-request-page .header-icon.bg-set { background: var(--color-orange); }

    .withdrawal-request-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .withdrawal-request-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .withdrawal-request-page .fixed-table-toolbar { margin-bottom: 10px; }
    .withdrawal-request-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .withdrawal-request-page .fixed-table-toolbar .btn-group > .btn,
    .withdrawal-request-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .withdrawal-request-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .withdrawal-request-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .withdrawal-request-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .withdrawal-request-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .withdrawal-request-page table.table thead th {
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
    .withdrawal-request-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .withdrawal-request-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .withdrawal-request-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;    
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .withdrawal-request-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>
