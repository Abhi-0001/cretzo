<div class="content-wrapper admin-sales-report-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-chart-line mr-2 text-primary-theme"></i>Sales Report</h4>
                    <p class="text-muted mb-0 small">Order-level sales figures across the marketplace, filterable by seller and date range.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Sales Reports</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">

            <?php // Sales_report_model::get_sales_list() has always returned final_total_amount,
                  // total_amount and total_delivery_charge alongside the rows, and nothing ever
                  // displayed them. They are the totals for the CURRENT filter, so they belong
                  // above the table where the filter is. Filled by srResponseHandler() below. ?>
            <div class="row sr-summary">
                <div class="col-sm-4">
                    <div class="sr-stat">
                        <span class="sr-stat-label">Sales (filtered)</span>
                        <span class="sr-stat-value" id="sr_final_total">&mdash;</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="sr-stat">
                        <span class="sr-stat-label">Item subtotal</span>
                        <span class="sr-stat-value" id="sr_total">&mdash;</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="sr-stat">
                        <span class="sr-stat-label">Delivery charges</span>
                        <span class="sr-stat-value" id="sr_delivery">&mdash;</span>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-chart-line"></i></span>
                        <h5 class="mb-0">Sales Report</h5>
                    </div>
                    <span class="sr-hint"><i class="fas fa-info-circle mr-1"></i>Long product names and addresses are trimmed to two lines &mdash; open the <b>+</b> row for the full text.</span>
                </div>
                <div class="card-body pt-3">
                    <div class="row sr-filters">
                        <div class="form-group col-md-4">
                            <label>From &amp; To Date</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" class="form-control" id="datepicker">
                                <input type="hidden" id="start_date">
                                <input type="hidden" id="end_date">
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Seller Name</label>
                            <select class='form-control' name='seller_id' id="seller_id">
                                <option value="">All sellers</option>
                                <?php foreach ($sellers as $seller) { ?>
                                    <option value="<?= $seller['seller_id'] ?>" <?= (isset($product_details[0]['seller_id']) && $product_details[0]['seller_id'] == $seller['seller_id']) ? 'selected' : "" ?>><?= $seller['seller_name'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary-theme btn-sm mr-2" onclick="status_date_wise_search()">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="srClearFilters()">Clear</button>
                        </div>
                    </div>

                    <?php /*
                     * Column widths are declared per column (data-width) instead of being left to
                     * table-layout:fixed to divide equally. Equal widths gave the always-short
                     * Order/User ID columns as much room as the product name, so every header
                     * read "ORDER IT…", "PRODUCT…", "FINAL TOT…" and the one cell that needed
                     * space had none - a long product title then wrapped to twenty lines and the
                     * row grew to match.
                     *
                     * The text columns are rendered through srClampCell(), which trims to two
                     * lines and keeps the full value in the title attribute and in the expanded
                     * (+) row. That is what caps the row height.
                     *
                     * data-field names are untouched: Sales_report_model whitelists them for
                     * sorting and searching.
                     */ ?>
                    <table class="table table-striped sr-table"
                           data-detail-view="true"
                           data-detail-formatter="srDetailFormatter"
                           data-auto-refresh="true"
                           data-toggle="table"
                           data-url="<?= base_url('admin/Sales_report/get_sales_report_list') ?>"
                           data-side-pagination="server"
                           data-response-handler="srResponseHandler"
                           data-pagination="true"
                           data-page-list="[5, 10, 25, 50, 100, 200, All]"
                           data-search="true"
                           data-show-columns="true"
                           data-show-columns-search="true"
                           data-show-refresh="true"
                           data-sort-name="id"
                           data-sort-order="DESC"
                           data-query-params="sales_report_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-width="90" data-formatter="srIdCell"><?= labels('id', 'Order') ?></th>
                                <th data-field="user_id" data-sortable="true" data-width="90" data-align="center"><?= labels('user_id', 'User ID') ?></th>
                                <th data-field="name" data-sortable="true" data-width="150" data-formatter="srClampCell"><?= labels('name', 'Customer') ?></th>
                                <th data-field="product_name" data-sortable="true" data-width="300" data-formatter="srClampCell"><?= labels('product_name', 'Product') ?></th>
                                <?php // The attribute here used to read data-visiable="false" - a typo, so the
                                      // column it meant to hide was shown anyway. Left visible deliberately
                                      // (an admin chasing an order needs the number) and the dead attribute
                                      // removed rather than left looking like it does something. ?>
                                <th data-field="mobile" data-sortable="true" data-width="130" data-formatter="srMonoCell"><?= labels('mobile', 'Mobile') ?></th>
                                <th data-field="address" data-sortable="true" data-width="240" data-formatter="srClampCell"><?= labels('address', 'Address') ?></th>
                                <th data-field="final_total" data-sortable="true" data-width="120" data-align="right" data-formatter="srMoneyCell"><?= labels('final_total', 'Total') ?></th>
                                <th data-field="date_added" data-sortable="true" data-width="130" data-formatter="srDateCell"><?= labels('date_added', 'Order Date') ?></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<script>
    /*
     * Cell renderers for the sales report.
     *
     * Every one of them escapes its value. The rows arrive as an HTML payload that
     * bootstrap-table injects with .html(), and product_name / address / name come
     * straight out of the database unescaped, so a product called
     * `<img src=x onerror=...>` used to execute here.
     */
    function srEscape(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
    }

    function srIdCell(value) {
        if (!value) return '<span class="sr-muted">&mdash;</span>';
        return '<span class="sr-id">#' + srEscape(value) + '</span>';
    }

    // Two lines, then an ellipsis. The full text stays in the tooltip and in the
    // expanded row, so nothing is lost - but a 40-word product title can no longer
    // decide how tall the row is.
    function srClampCell(value) {
        var text = String(value === null || value === undefined ? '' : value).trim();
        if (text === '') return '<span class="sr-muted">&mdash;</span>';
        return '<div class="sr-clamp" title="' + srEscape(text) + '">' + srEscape(text) + '</div>';
    }

    function srMonoCell(value) {
        var text = String(value === null || value === undefined ? '' : value).trim();
        if (text === '') return '<span class="sr-muted">&mdash;</span>';
        return '<span class="sr-mono">' + srEscape(text) + '</span>';
    }

    function srFormatMoney(value) {
        var amount = parseFloat(value);
        if (isNaN(amount)) return null;
        return '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function srMoneyCell(value) {
        var money = srFormatMoney(value);
        if (money === null) return '<span class="sr-muted">&mdash;</span>';
        return '<span class="sr-amount">' + money + '</span>';
    }

    // "2026-08-26 13:22:13" on one line was the widest thing in its column and forced
    // the header to truncate. Date over time reads faster and fits 130px.
    function srDateCell(value) {
        var text = String(value === null || value === undefined ? '' : value).trim();
        if (text === '') return '<span class="sr-muted">&mdash;</span>';
        var parts = text.split(' ');
        var date = parts[0] || text;
        var time = parts[1] || '';
        return '<div class="sr-date"><span>' + srEscape(date) + '</span>' +
            (time ? '<small>' + srEscape(time) + '</small>' : '') + '</div>';
    }

    /*
     * The expanded (+) row. Deliberately NOT called salesReport(): that name is a global
     * in assets/admin/custom/custom.js which loads AFTER this view's script, so it would
     * overwrite this one - and the seller panel's own sales report still points at it.
     *
     * It replaces that generic formatter here, which
     * walked the row object and read its labels from `$("th:eq(" + (index + 1) + ")")` -
     * an unscoped, off-by-one lookup against every <th> on the page, so it printed
     * values under the wrong headings. This one names the fields it shows and is where
     * the untrimmed product name and address live.
     */
    function srDetailFormatter(index, row) {
        function line(label, value, extraClass) {
            var text = String(value === null || value === undefined ? '' : value).trim();
            return '<div class="sr-detail-item">' +
                '<span class="sr-detail-label">' + srEscape(label) + '</span>' +
                '<span class="sr-detail-value ' + (extraClass || '') + '">' +
                (text === '' ? '&mdash;' : srEscape(text)) + '</span></div>';
        }

        return '<div class="sr-detail">' +
            line('Order', row.id ? '#' + row.id : '') +
            line('Customer', row.name) +
            line('User ID', row.user_id) +
            line('Mobile', row.mobile, 'sr-mono') +
            line('Order date', row.date_added) +
            '<div class="sr-detail-item sr-detail-wide">' +
                '<span class="sr-detail-label">Product</span>' +
                '<span class="sr-detail-value">' + srEscape(row.product_name || '—') + '</span></div>' +
            '<div class="sr-detail-item sr-detail-wide">' +
                '<span class="sr-detail-label">Address</span>' +
                '<span class="sr-detail-value">' + srEscape(row.address || '—') + '</span></div>' +
            '<div class="sr-detail-item">' +
                '<span class="sr-detail-label">Order total</span>' +
                '<span class="sr-detail-value sr-amount">' + (srFormatMoney(row.final_total) || '—') + '</span></div>' +
            '</div>';
    }

    // Every reply carries the totals for the current filter; show them and hand the
    // rows straight back to bootstrap-table.
    function srResponseHandler(res) {
        try {
            var set = function (id, value) {
                var el = document.getElementById(id);
                if (!el) return;
                var money = srFormatMoney(value);
                el.textContent = (money === null) ? '—' : money;
            };
            set('sr_final_total', res.final_total_amount);
            set('sr_total', res.total_amount);
            set('sr_delivery', res.total_delivery_charge);
        } catch (e) { /* totals are a nicety; never block the table on them */ }
        return res;
    }

    function srClearFilters() {
        var picker = document.getElementById('datepicker');
        if (picker) picker.value = '';
        ['start_date', 'end_date'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        var seller = document.getElementById('seller_id');
        if (seller) seller.value = '';
        if (typeof status_date_wise_search === 'function') status_date_wise_search();
    }
</script>

<style>
    .admin-sales-report-page .text-primary-theme { color: var(--color-orange); }

    .admin-sales-report-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-sales-report-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-sales-report-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-sales-report-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-sales-report-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-sales-report-page .header-icon.bg-set { background: var(--color-orange); }
    .admin-sales-report-page .sr-hint { font-size: 12px; color: var(--color-grey); }

    /* ---- Totals strip ------------------------------------------------- */
    .admin-sales-report-page .sr-summary { margin-bottom: 14px; }
    .admin-sales-report-page .sr-stat {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        border-left: 3px solid var(--color-orange);
        border-radius: 10px;
        padding: 12px 16px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.05);
    }
    .admin-sales-report-page .sr-stat-label {
        display: block;
        font-size: 11.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--color-grey);
    }
    .admin-sales-report-page .sr-stat-value {
        display: block;
        margin-top: 2px;
        font-size: 20px;
        font-weight: 700;
        color: #23282e;
    }

    /* ---- Filters ------------------------------------------------------ */
    .admin-sales-report-page .sr-filters label { font-size: 12.5px; font-weight: 600; margin-bottom: 4px; }
    .admin-sales-report-page .sr-filters .form-control,
    .admin-sales-report-page .sr-filters .input-group-text {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.12);
        box-shadow: none;
    }
    .admin-sales-report-page .sr-filters .input-group > .form-control { border-radius: 0 8px 8px 0; }
    .admin-sales-report-page .sr-filters .input-group-text { border-radius: 8px 0 0 8px; background: #fafafa; }
    .admin-sales-report-page .sr-filters .form-control:focus { border-color: var(--color-orange); box-shadow: none; outline: none; }

    /* ---- Toolbar ------------------------------------------------------ */
    .admin-sales-report-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-sales-report-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .btn,
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-sales-report-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-sales-report-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-sales-report-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-sales-report-page .fixed-table-toolbar .columns .btn,
    .admin-sales-report-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-sales-report-page .fixed-table-toolbar .columns .btn:hover,
    .admin-sales-report-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    /* ---- Table -------------------------------------------------------- */
    .admin-sales-report-page .fixed-table-container { border: none; }
    /* The widths come from each column's data-width, so the table must be allowed to
       use them: table-layout:fixed with no widths is what divided the row equally
       between eight columns regardless of what each one holds. A horizontal scroll
       inside the card is better than squeezing every column below its content. */
    .admin-sales-report-page .fixed-table-body { overflow-x: auto; }
    .admin-sales-report-page table.sr-table {
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
        min-width: 1080px;
    }
    .admin-sales-report-page table.sr-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
        padding: 10px 12px;
    }
    .admin-sales-report-page table.sr-table tbody td {
        vertical-align: middle;
        font-size: 13.5px;
        padding: 10px 12px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .admin-sales-report-page table.sr-table tbody tr:hover { background-color: var(--color-orange-light); }

    /* This is the row-height cap: two lines of text, then an ellipsis. */
    .admin-sales-report-page .sr-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.35;
        max-height: 2.7em;
        overflow-wrap: anywhere;
    }
    .admin-sales-report-page .sr-id {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 6px;
        background: #f1f3f6;
        color: #40474f;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12.5px;
        font-weight: 600;
    }
    .admin-sales-report-page .sr-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12.5px;
        white-space: nowrap;
    }
    .admin-sales-report-page .sr-amount { font-weight: 700; white-space: nowrap; }
    .admin-sales-report-page .sr-muted { color: #b6bcc4; }
    .admin-sales-report-page .sr-date { line-height: 1.3; white-space: nowrap; }
    .admin-sales-report-page .sr-date small { display: block; color: var(--color-grey); font-size: 11.5px; }

    /* ---- Expanded (+) row --------------------------------------------- */
    .admin-sales-report-page .sr-detail {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px 24px;
        padding: 4px 2px;
    }
    .admin-sales-report-page .sr-detail-wide { grid-column: 1 / -1; }
    .admin-sales-report-page .sr-detail-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--color-grey);
    }
    .admin-sales-report-page .sr-detail-value {
        display: block;
        font-size: 13.5px;
        color: #23282e;
        overflow-wrap: anywhere;
    }

    /* ---- Pagination --------------------------------------------------- */
    .admin-sales-report-page .fixed-table-pagination { margin-top: 12px; }
    .admin-sales-report-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-sales-report-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-sales-report-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }

    @media screen and (max-width: 767px) {
        .admin-sales-report-page .sr-summary .col-sm-4 + .col-sm-4 { margin-top: 10px; }
        .admin-sales-report-page .sr-hint { display: none; }
    }
</style>
