<?php
// Fetched here rather than relying on a $settings the admin template does not pass down.
$settings = get_settings('system_settings', true);
$currency = isset($settings['currency']) ? $settings['currency'] : '';
$money = function ($amount) use ($currency) {
    return $currency . number_format((float) $amount, 2);
};
?>
<div class="content-wrapper admin-settlements-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-percentage mr-2 text-primary-theme"></i>Commission &amp; Settlements</h4>
                    <p class="text-muted mb-0 small">Order-wise commission taken and net amount credited to sellers.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Commission &amp; Settlements</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <span class="stat-label">Gross Order Value Settled</span>
                        <span class="stat-value"><?= $money($summary['gross_amount']) ?></span>
                        <span class="stat-sub"><?= (int) $summary['total_settlements'] ?> settled order item(s)</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card stat-card-accent">
                        <span class="stat-label">Commission Earned</span>
                        <span class="stat-value"><?= $money($summary['commission_amount']) ?></span>
                        <span class="stat-sub">Platform share of the above</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <span class="stat-label">Credited To Sellers</span>
                        <span class="stat-value"><?= $money($summary['net_payable']) ?></span>
                        <span class="stat-sub">Net payable after commission</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card <?= ($summary['failed_count'] > 0) ? 'stat-card-danger' : '' ?>">
                        <span class="stat-label">Failed Settlements</span>
                        <span class="stat-value"><?= (int) $summary['failed_count'] ?></span>
                        <span class="stat-sub">Retried on the next settlement run</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <span class="stat-label">Reversed (Returns / Cancels)</span>
                        <span class="stat-value"><?= $money($summary['reversed_amount']) ?></span>
                        <span class="stat-sub"><?= (int) $summary['reversed_count'] ?> clawed back from sellers</span>
                    </div>
                </div>
            </div>

            <?php if ($unsettled['pending_items'] > 0) { ?>
                <div class="alert alert-info d-flex align-items-start">
                    <i class="fas fa-hourglass-half mr-2 mt-1"></i>
                    <div>
                        <strong><?= (int) $unsettled['pending_items'] ?> delivered order item(s)</strong>
                        worth <?= $money($unsettled['pending_amount']) ?> are delivered but not yet credited.
                        These settle automatically once their return window closes.
                        <?php if ($unsettled['blocked_items'] > 0) { ?>
                            <div class="mt-2 text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong><?= (int) $unsettled['blocked_items'] ?></strong> of them
                                (<?= $money($unsettled['blocked_amount']) ?>) belong to sellers with no
                                subscription plan. They settle at the platform default commission rate
                                (config/commission.php) rather than being held back.
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php if (!empty($reconciliation)) { ?>
                <div class="card attribute-card mb-4">
                    <div class="card-header attribute-card-header d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-balance-scale"></i></span>
                        <h5 class="mb-0">Wallet Reconciliation</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Sellers whose wallet balance does not match the sum of their wallet transactions.
                            A seller with <strong>no ledger rows</strong> simply had a balance set directly and is
                            not necessarily a fault; a mismatch with a full ledger is worth investigating.
                        </p>
                        <div style="overflow-x:auto;">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Seller</th>
                                        <th class="text-right">Wallet Balance</th>
                                        <th class="text-right">Ledger Total</th>
                                        <th class="text-right">Difference</th>
                                        <th class="text-right">Ledger Rows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reconciliation as $r) { ?>
                                        <tr>
                                            <td><?= html_escape($r['username']) ?> <span class="text-muted small">#<?= (int) $r['user_id'] ?></span></td>
                                            <td class="text-right"><?= $money($r['balance']) ?></td>
                                            <td class="text-right"><?= $money($r['ledger']) ?></td>
                                            <td class="text-right <?= ($r['difference'] < 0) ? 'text-danger' : 'text-muted' ?>">
                                                <?= ($r['difference'] > 0 ? '+' : '') . $money($r['difference']) ?>
                                            </td>
                                            <td class="text-right"><?= (int) $r['ledger_rows'] ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-percentage"></i></span>
                        <h5 class="mb-0">Settlement Records</h5>
                    </div>
                    <div class="d-flex align-items-center flex-wrap">
                        <label for="seller_filter" class="mb-0 mr-2 small text-muted">Seller</label>
                        <select class="form-control form-control-sm mr-3" name="seller_filter" id="seller_filter" style="width:auto;">
                            <option value="">All</option>
                            <?php foreach ($sellers as $seller) { ?>
                                <option value="<?= (int) $seller['id'] ?>"><?= html_escape($seller['username']) ?></option>
                            <?php } ?>
                        </select>
                        <label for="status_filter" class="mb-0 mr-2 small text-muted">Status</label>
                        <select class="form-control form-control-sm" name="status_filter" id="status_filter" style="width:auto;">
                            <option value="">All</option>
                            <option value="settled">Settled</option>
                            <option value="failed">Failed</option>
                            <option value="reversed">Reversed</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table-striped" id="settlement_table" data-toggle="table"
                        data-url="<?= base_url('admin/settlement/view-settlement-list') ?>"
                        data-side-pagination="server" data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                        data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                        data-mobile-responsive="true" data-show-export="true"
                        data-query-params="settlement_queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="seller_name" data-sortable="true">Seller</th>
                                <th data-field="order_id" data-sortable="true">Order ID</th>
                                <th data-field="order_amount" data-sortable="true">Gross Order Amount</th>
                                <th data-field="product_tax_amount" data-sortable="false" data-visible="false">Product Tax</th>
                                <th data-field="taxable_value" data-sortable="true">Taxable Value</th>
                                <th data-field="commission_percent" data-sortable="true">Commission %</th>
                                <th data-field="commission_amount" data-sortable="true">Commission (Deduction)</th>
                                <th data-field="commission_gst_amount" data-sortable="false" data-visible="false">GST on Commission</th>
                                <th data-field="tcs_amount" data-sortable="false" data-visible="false">TCS</th>
                                <th data-field="tds_amount" data-sortable="false" data-visible="false">TDS</th>
                                <th data-field="net_payable" data-sortable="true">Net Seller Amount</th>
                                <th data-field="settlement_status" data-sortable="true">Status</th>
                                <th data-field="created_at" data-sortable="true">Settled On</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .admin-settlements-page .text-primary-theme { color: var(--color-orange); }

    .admin-settlements-page .stat-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
        padding: 16px 18px;
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        border-left: 4px solid rgba(0, 0, 0, 0.08);
    }
    .admin-settlements-page .stat-card-accent { border-left-color: var(--color-orange); }
    .admin-settlements-page .stat-card-danger { border-left-color: #dc3545; }
    .admin-settlements-page .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--color-grey);
        font-weight: 600;
    }
    .admin-settlements-page .stat-value { font-size: 22px; font-weight: 700; margin-top: 4px; }
    .admin-settlements-page .stat-sub { font-size: 12px; color: #8a8a8a; margin-top: 2px; }

    .admin-settlements-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06); }
    .admin-settlements-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-settlements-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none; margin-right: 10px;
    }
    .admin-settlements-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-settlements-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-settlements-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-settlements-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0, 0, 0, 0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-settlements-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-settlements-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0, 0, 0, 0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-settlements-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0, 0, 0, 0.05); }
    .admin-settlements-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-settlements-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-settlements-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0, 0, 0, 0.08);
    }
</style>

<script>
    function settlement_queryParams(p) {
        return {
            seller_filter: $('#seller_filter').val(),
            status_filter: $('#status_filter').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    $(document).on('change', '#seller_filter, #status_filter', function () {
        $('#settlement_table').bootstrapTable('refresh');
    });
</script>
