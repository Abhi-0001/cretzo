<?php
/*
 * Category commission rates.
 *
 * Every category in tree order with its own rate (editable) and its EFFECTIVE rate - what a
 * product listed in it is actually charged. A blank own rate inherits from the nearest parent
 * that has one; a chain with nothing set uses the platform default. The rate is locked onto
 * each order line when the order is placed, so edits here only re-price future orders.
 *
 * Data: $commission_rows (Category_model::commission_overview()), $commission_default,
 * $can_edit_commission. Saves go to admin/category/update_commission as one batch.
 */
$fmt_rate = function ($n) {
    return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
};
$own_set = 0;
foreach ($commission_rows as $r) {
    if ($r['commission_percent'] !== null) {
        $own_set++;
    }
}
?>
<div class="content-wrapper admin-category-commission-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-percentage mr-2 text-primary-theme"></i>Category Commission</h4>
                    <p class="text-muted mb-0 small">The commission Cretzo charges on each sale is set by the product's category.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/category') ?>">Categories</a></li>
                        <li class="breadcrumb-item active">Commission</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="ccm-stat">
                        <span class="ccm-stat-label">Categories with their own rate</span>
                        <span class="ccm-stat-value" id="ccm-own-count"><?= (int) $own_set ?></span>
                        <span class="ccm-stat-sub">of <?= count($commission_rows) ?> categories</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="ccm-stat">
                        <span class="ccm-stat-label">Platform default</span>
                        <span class="ccm-stat-value"><?= $fmt_rate($commission_default) ?>%</span>
                        <span class="ccm-stat-sub">used when no category in the chain has a rate</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="ccm-stat">
                        <span class="ccm-stat-label">How it applies</span>
                        <span class="ccm-stat-sub mt-1">Charged on the ex-GST value of each item. The rate is fixed on the order when it is placed &mdash; changing it here only affects new orders. Sellers in the <a href="<?= base_url('admin/commission_offer') ?>">Zero Commission Offer</a> pay 0% on their first free orders instead.</span>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <span class="header-icon bg-set"><i class="fas fa-sitemap"></i></span>
                        <h5 class="mb-0">Rates by category</h5>
                    </div>
                    <div class="d-flex align-items-center flex-wrap">
                        <input type="search" class="form-control form-control-sm mr-2 mb-2 mb-md-0" id="ccm-search" placeholder="Filter categories" style="width: 200px;">
                        <div class="custom-control custom-switch mr-3 mb-2 mb-md-0">
                            <input type="checkbox" class="custom-control-input" id="ccm-show-inactive">
                            <label class="custom-control-label small" for="ccm-show-inactive">Show inactive</label>
                        </div>
                        <?php if ($can_edit_commission) { ?>
                            <button type="button" class="btn btn-primary-theme btn-sm mb-2 mb-md-0" id="ccm-save" disabled>
                                <i class="fas fa-save mr-1"></i>Save changes <span class="badge badge-light ml-1 d-none" id="ccm-dirty-count">0</span>
                            </button>
                        <?php } ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 ccm-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th style="width: 190px;">Own rate</th>
                                    <th style="width: 120px;" class="text-center">Charged</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody id="ccm-body"></tbody>
                        </table>
                    </div>
                    <p class="text-muted small px-3 py-2 mb-0 border-top">
                        Leave <strong>Own rate</strong> blank to inherit from the parent category. Setting a rate on a top-level category applies it to every subcategory that has no rate of its own.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .admin-category-commission-page .ccm-stat {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 10px;
        padding: 14px 16px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .ccm-stat-label { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }
    .ccm-stat-value { font-size: 26px; font-weight: 700; line-height: 1.3; }
    .ccm-stat-sub { font-size: 12.5px; color: #6c757d; }
    .ccm-table td, .ccm-table th { vertical-align: middle; }
    .ccm-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }
    .ccm-name { display: inline-flex; align-items: center; }
    .ccm-name .ccm-branch { color: #c4c4c4; margin-right: 6px; font-family: monospace; }
    .ccm-root { font-weight: 600; }
    .ccm-inactive { opacity: .55; }
    .ccm-row-dirty { background: #fff8e6 !important; }
    .ccm-rate-input { max-width: 110px; }
    .ccm-clear { color: #6c757d; padding: 0 6px; }
    .ccm-eff { font-weight: 700; font-size: 15px; }
    .ccm-src { font-size: 12.5px; color: #6c757d; }
    .ccm-src .badge { font-weight: 500; }
</style>

<script>
    (function () {
        var rows = <?= json_encode($commission_rows) ?>;
        var platformDefault = <?= json_encode((float) $commission_default) ?>;
        var canEdit = <?= $can_edit_commission ? 'true' : 'false' ?>;
        var dirty = {};

        function fmt(n) {
            return (Math.round(parseFloat(n) * 100) / 100).toString();
        }
        function esc(s) {
            return $('<div>').text(s == null ? '' : String(s)).html();
        }

        // Effective rate as it WOULD be with the unsaved edits applied, so the "Charged" column
        // previews inheritance live while the admin types.
        function preview() {
            var byId = {};
            rows.forEach(function (r) { byId[r.id] = r; });
            function own(r) {
                if (Object.prototype.hasOwnProperty.call(dirty, r.id)) {
                    return dirty[r.id] === '' ? null : parseFloat(dirty[r.id]);
                }
                return r.commission_percent;
            }
            var out = {};
            rows.forEach(function (r) {
                var cur = r, seen = {}, hops = 0;
                while (cur && !seen[cur.id] && hops < 50) {
                    seen[cur.id] = true;
                    hops++;
                    var v = own(cur);
                    if (v !== null && v !== undefined && !isNaN(v)) {
                        out[r.id] = { rate: v, source: cur.id === r.id ? 'category' : 'category_inherited', from: cur.name };
                        return;
                    }
                    cur = byId[cur.parent_id];
                }
                out[r.id] = { rate: platformDefault, source: 'platform_default', from: null };
            });
            return out;
        }

        function render() {
            var eff = preview();
            var q = $.trim($('#ccm-search').val()).toLowerCase();
            var showInactive = $('#ccm-show-inactive').is(':checked');
            var html = '';
            rows.forEach(function (r) {
                if (!showInactive && r.status !== 1) return;
                if (q && r.name.toLowerCase().indexOf(q) === -1) return;
                var e = eff[r.id];
                var ownVal = Object.prototype.hasOwnProperty.call(dirty, r.id)
                    ? dirty[r.id]
                    : (r.commission_percent === null ? '' : fmt(r.commission_percent));
                var src;
                if (e.source === 'category') {
                    src = '<span class="badge badge-info">Own rate</span>';
                } else if (e.source === 'category_inherited') {
                    src = 'Inherited from <strong>' + esc(e.from) + '</strong>';
                } else {
                    src = '<span class="badge badge-light border">Platform default</span>';
                }
                var indent = r.depth > 0 ? '<span class="ccm-branch">' + new Array(r.depth).join('&nbsp;&nbsp;&nbsp;') + '&#9492;</span>' : '';
                var input = canEdit
                    ? '<div class="input-group input-group-sm ccm-rate-input">' +
                        '<input type="number" class="form-control ccm-rate" data-id="' + r.id + '" min="0" max="100" step="0.01" placeholder="Inherit" value="' + esc(ownVal) + '">' +
                        '<div class="input-group-append"><span class="input-group-text">%</span></div>' +
                      '</div>' +
                      (ownVal !== '' ? '<button type="button" class="btn btn-link btn-sm ccm-clear" data-id="' + r.id + '" title="Clear - inherit from parent"><i class="fas fa-times"></i></button>' : '')
                    : (ownVal === '' ? '<span class="text-muted">&mdash;</span>' : esc(ownVal) + '%');
                html += '<tr class="' + (r.status !== 1 ? 'ccm-inactive ' : '') + (Object.prototype.hasOwnProperty.call(dirty, r.id) ? 'ccm-row-dirty' : '') + '">' +
                    '<td><span class="ccm-name ' + (r.depth === 0 ? 'ccm-root' : '') + '" style="padding-left:' + (r.depth * 18) + 'px">' + indent + esc(r.name) + '</span>' +
                    (r.status !== 1 ? ' <span class="badge badge-secondary ml-1">Inactive</span>' : '') + '</td>' +
                    '<td><div class="d-flex align-items-center">' + input + '</div></td>' +
                    '<td class="text-center"><span class="ccm-eff">' + fmt(e.rate) + '%</span></td>' +
                    '<td class="ccm-src">' + src + '</td>' +
                    '</tr>';
            });
            $('#ccm-body').html(html || '<tr><td colspan="4" class="text-center text-muted py-4">No categories match.</td></tr>');
            var n = Object.keys(dirty).length;
            $('#ccm-save').prop('disabled', n === 0);
            $('#ccm-dirty-count').text(n).toggleClass('d-none', n === 0);
        }

        function setDirty(id, value) {
            var r = rows.filter(function (x) { return x.id === id; })[0];
            var original = (r.commission_percent === null) ? '' : fmt(r.commission_percent);
            var v = $.trim(value);
            if (v !== '' && !isNaN(parseFloat(v))) v = fmt(v);
            if (v === original) {
                delete dirty[id];
            } else {
                dirty[id] = v;
            }
        }

        // Re-render on blur/change rather than on every keystroke, so the input keeps focus
        // while the admin types; the Save button state still updates as they type.
        $(document).on('input', '.ccm-rate', function () {
            setDirty(parseInt($(this).data('id'), 10), $(this).val());
            var n = Object.keys(dirty).length;
            $('#ccm-save').prop('disabled', n === 0);
            $('#ccm-dirty-count').text(n).toggleClass('d-none', n === 0);
        });
        $(document).on('change', '.ccm-rate', render);
        $(document).on('click', '.ccm-clear', function () {
            setDirty(parseInt($(this).data('id'), 10), '');
            render();
        });
        $('#ccm-search').on('input', render);
        $('#ccm-show-inactive').on('change', render);

        $('#ccm-save').on('click', function () {
            var bad = Object.keys(dirty).filter(function (id) {
                var v = dirty[id];
                return v !== '' && (isNaN(parseFloat(v)) || parseFloat(v) < 0 || parseFloat(v) > 100);
            });
            if (bad.length) {
                iziToast.error({ message: 'Rates must be between 0 and 100, or blank to inherit.' });
                return;
            }
            var $btn = $(this);
            var original = $btn.html();
            var data = { rates: JSON.stringify(dirty) };
            data[csrfName] = csrfHash;
            $.ajax({
                url: '<?= base_url('admin/category/update_commission') ?>',
                type: 'POST',
                data: data,
                dataType: 'json',
                beforeSend: function () {
                    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Saving...');
                }
            }).done(function (res) {
                if (res.csrfName) { csrfName = res.csrfName; csrfHash = res.csrfHash; }
                if (res.error) {
                    iziToast.error({ message: res.message || 'Could not save.' });
                    return;
                }
                rows = res.rows;
                dirty = {};
                $('#ccm-own-count').text(rows.filter(function (r) { return r.commission_percent !== null; }).length);
                iziToast.success({ message: res.message });
            }).fail(function () {
                iziToast.error({ message: 'Something went wrong. Please try again.' });
            }).always(function () {
                $btn.html(original);
                render();
            });
        });

        window.addEventListener('beforeunload', function (e) {
            if (Object.keys(dirty).length) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        render();
    })();
</script>
