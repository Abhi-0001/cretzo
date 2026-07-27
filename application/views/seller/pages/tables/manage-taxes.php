<div class="content-wrapper taxes-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-percentage mr-2 text-primary-theme"></i>Taxes</h4>
                    <p class="text-muted mb-0 small">Tax rates applied to your products at checkout.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Taxes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card mb-4">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-percentage"></i></span>
                    <h5 class="mb-0">Tax List</h5>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped'
                        data-toggle="table"
                        data-url="<?= base_url('seller/taxes/get_tax_list') ?>"
                        data-click-to-select="true"
                        data-side-pagination="server"
                        data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true"
                        data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc"
                        data-mobile-responsive="true"
                        data-toolbar="" data-show-export="true"
                        data-maintain-selected="true"
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="title" data-sortable="false">Title</th>
                                <th data-field="percentage" data-sortable="true" data-formatter="taxPercentageFormatter">Percentage</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .taxes-page .text-primary-theme { color: var(--color-orange); }

    .taxes-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .taxes-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .taxes-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .taxes-page .header-icon.bg-set { background: var(--color-orange); }

    .taxes-page .tax-badge {
        display: inline-block;
        background: var(--color-orange-light);
        color: var(--color-orange-dark);
        font-weight: 600;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 13px;
    }

    /* --- simplified bootstrap-table look --- */
    .taxes-page .fixed-table-toolbar {
        margin-bottom: 10px;
    }
    .taxes-page .fixed-table-toolbar > div {
        margin-left: 10px !important;
    }
    .taxes-page .fixed-table-toolbar .btn-group > .btn,
    .taxes-page .fixed-table-toolbar .btn-group > .keep-open {
        margin-left: 8px !important;
    }
    .taxes-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .taxes-page .fixed-table-toolbar .btn-group > .keep-open:first-child {
        margin-left: 0 !important;
    }
    .taxes-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .taxes-page .fixed-table-toolbar .search input:focus {
        border-color: var(--color-orange);
    }
    .taxes-page .fixed-table-toolbar .columns .btn,
    .taxes-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .taxes-page .fixed-table-toolbar .columns .btn:hover,
    .taxes-page .fixed-table-toolbar .export .btn:hover {
        border-color: var(--color-orange);
        color: var(--color-orange);
    }
    .taxes-page .fixed-table-container {
        border: none;
    }
    .taxes-page table.table {
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }
    .taxes-page table.table thead th {
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
    .taxes-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .taxes-page table.table tbody tr:hover {
        background-color: var(--color-orange-light);
    }
    .taxes-page .fixed-table-pagination {
        margin-top: 12px;
    }
    .taxes-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .taxes-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .taxes-page .fixed-table-pagination .page-list .btn {
        border-radius: 20px;
    }
</style>

<script>
    function taxPercentageFormatter(value) {
        if (value === null || value === undefined || value === '') return '';
        return '<span class="tax-badge">' + value + '%</span>';
    }
</script>
