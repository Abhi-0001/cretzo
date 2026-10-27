<?php
/*
 * Admin > Zero Commission Offer. Data: $offer (Commission_offer::state()), $table_ready, $can_edit.
 * Rendered client-side from one state object so every action re-renders the whole page from
 * the server's answer.
 */
?>
<div class="content-wrapper zco-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-gift mr-2 text-primary-theme"></i>Zero Commission Offer</h4>
                    <p class="text-muted mb-0 small">Launch promotion: the first sellers pay no commission on their first orders.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/category/commission') ?>">Commission</a></li>
                        <li class="breadcrumb-item active">Zero Commission Offer</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <?php if (!$table_ready) { ?>
                <div class="alert alert-warning">The offer tables are not installed yet. Run <a href="<?= base_url('admin/migrate') ?>">admin/migrate</a> once.</div>
            <?php } else { ?>

            <div class="row" id="zco-stats"></div>

            <div class="row">
                <div class="col-lg-4 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set"><i class="fas fa-sliders-h"></i></span>
                            <h5 class="mb-0">Offer settings</h5>
                        </div>
                        <div class="card-body">
                            <form id="zco-settings" autocomplete="off">
                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" class="custom-control-input" id="zco-enabled" name="enabled" value="1" <?= $can_edit ? '' : 'disabled' ?>>
                                    <label class="custom-control-label font-weight-bold" for="zco-enabled">Offer is open to new sellers</label>
                                    <small class="form-text text-muted">Switching it off stops new sign-ups joining. Sellers already enrolled keep their free orders.</small>
                                </div>
                                <div class="form-group">
                                    <label for="zco-seller-limit" class="font-weight-bold mb-1">Number of sellers</label>
                                    <input type="number" class="form-control" id="zco-seller-limit" name="seller_limit" min="0" step="1" <?= $can_edit ? '' : 'disabled' ?>>
                                    <small class="form-text text-muted">How many sellers can hold a place (first come, first served at sign-up).</small>
                                </div>
                                <div class="form-group">
                                    <label for="zco-orders" class="font-weight-bold mb-1">Commission-free orders per seller</label>
                                    <input type="number" class="form-control" id="zco-orders" name="orders_per_seller" min="0" step="1" <?= $can_edit ? '' : 'disabled' ?>>
                                    <small class="form-text text-muted">Applies to sellers who join from now on. Every item in each of these orders is charged 0%.</small>
                                </div>
                                <?php if ($can_edit) { ?>
                                    <button type="submit" class="btn btn-primary-theme btn-block" id="zco-save"><i class="fas fa-save mr-1"></i>Save settings</button>
                                <?php } ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-3">
                    <div class="card attribute-card h-100">
                        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set"><i class="fas fa-users"></i></span>
                                <h5 class="mb-0">Enrolled sellers</h5>
                            </div>
                            <?php if ($can_edit) { ?>
                                <div class="d-flex align-items-center mt-2 mt-md-0">
                                    <select class="form-control form-control-sm mr-2" id="zco-candidate" style="min-width: 220px;"></select>
                                    <button type="button" class="btn btn-sm btn-outline-primary-theme text-nowrap" id="zco-enrol"><i class="fas fa-user-plus mr-1"></i>Enrol seller</button>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 zco-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Seller</th>
                                            <th>Joined</th>
                                            <th style="min-width:170px">Free orders used</th>
                                            <th class="text-right">Sold at 0%</th>
                                            <th>Status</th>
                                            <?php if ($can_edit) { ?><th></th><?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody id="zco-rows"></tbody>
                                </table>
                            </div>
                            <p class="text-muted small px-3 py-2 mb-0 border-top">
                                A slot is used when an order is placed. If that order is cancelled or returned, the slot is given back.
                                Order lines keep the rate they were placed with, so removing a seller never re-prices orders already placed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </section>
</div>

<style>
    .zco-page .text-primary-theme { color: var(--color-orange, #f28c38); }
    .zco-stat { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 10px; padding: 14px 16px; height: 100%; }
    .zco-stat small { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }
    .zco-stat b { display: block; font-size: 26px; line-height: 1.3; }
    .zco-stat span { font-size: 12.5px; color: #6c757d; }
    .zco-pill { display: inline-block; border-radius: 10px; padding: 2px 9px; font-size: 12px; font-weight: 600; }
    .zco-pill.on { background: #e6f6ec; color: #1e7b45; }
    .zco-pill.off { background: #fdecec; color: #b42318; }
    .zco-pill.done { background: #eef0f3; color: #4b5563; }
    .zco-bar { height: 6px; background: #eee; border-radius: 3px; overflow: hidden; margin-top: 4px; }
    .zco-bar i { display: block; height: 100%; background: var(--color-orange, #f28c38); }
    .zco-table td, .zco-table th { vertical-align: middle; }
    .zco-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }
</style>

<?php if ($table_ready) { ?>
<script>
(function () {
    var state = <?= json_encode($offer) ?>;
    var canEdit = <?= $can_edit ? 'true' : 'false' ?>;
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function inr(n) { return '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 }); }
    function fmtDate(s) { if (!s) return ''; var d = new Date(s.replace(' ', 'T')); return isNaN(d) ? esc(s) : d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }); }

    function render() {
        var s = state.settings;
        $('#zco-enabled').prop('checked', !!s.enabled);
        $('#zco-seller-limit').val(s.seller_limit);
        $('#zco-orders').val(s.orders_per_seller);

        var usedOrders = 0;
        state.rows.forEach(function (r) { if (Number(r.status) === 1) usedOrders += Number(r.used); });
        $('#zco-stats').html(
            '<div class="col-md-3 col-6 mb-3"><div class="zco-stat"><small>Offer</small><b>' + (state.is_open ? '<span class="zco-pill on">Open</span>' : '<span class="zco-pill off">Closed</span>') + '</b><span>' +
                (state.is_open ? 'New sellers are enrolled at sign-up' : (s.enabled ? 'All places are taken' : 'Switched off for new sellers')) + '</span></div></div>' +
            '<div class="col-md-3 col-6 mb-3"><div class="zco-stat"><small>Places taken</small><b>' + state.enrolled + ' / ' + s.seller_limit + '</b><span>' + state.slots_left + ' left</span></div></div>' +
            '<div class="col-md-3 col-6 mb-3"><div class="zco-stat"><small>Free orders per seller</small><b>' + s.orders_per_seller + '</b><span>every item at 0% commission</span></div></div>' +
            '<div class="col-md-3 col-6 mb-3"><div class="zco-stat"><small>Free orders used</small><b>' + usedOrders + '</b><span>across enrolled sellers</span></div></div>'
        );

        var html = '';
        state.rows.forEach(function (r, i) {
            var active = Number(r.status) === 1;
            var limit = Number(r.orders_limit), used = Number(r.used);
            var pct = limit > 0 ? Math.round(used / limit * 100) : 100;
            var pill = !active ? '<span class="zco-pill off">Removed</span>' : (used >= limit ? '<span class="zco-pill done">Used up</span>' : '<span class="zco-pill on">Active</span>');
            html += '<tr' + (active ? '' : ' class="text-muted"') + '>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><a href="<?= base_url('admin/sellers/manage_seller?edit_id=') ?>' + Number(r.seller_id) + '"><b>' + esc(r.store || r.username || ('Seller #' + r.seller_id)) + '</b></a><br><small class="text-muted">' + esc(r.username || '') + (r.mobile ? ' &middot; ' + esc(r.mobile) : '') + ' &middot; ID ' + Number(r.seller_id) + '</small></td>' +
                '<td><small>' + fmtDate(r.enrolled_at) + '<br>' + (r.source === 'admin' ? 'by admin' : 'at sign-up') + '</small></td>' +
                '<td><b>' + used + '</b> of ' + limit + ' &middot; ' + Math.max(0, limit - used) + ' left<div class="zco-bar"><i style="width:' + pct + '%"></i></div></td>' +
                '<td class="text-right">' + inr(r.offer_sales) + '</td>' +
                '<td>' + pill + '</td>' +
                (canEdit ? '<td class="text-right">' + (active
                    ? '<button class="btn btn-xs btn-outline-danger zco-revoke" data-id="' + Number(r.seller_id) + '">Remove</button>'
                    : '<button class="btn btn-xs btn-outline-primary-theme zco-reinstate" data-id="' + Number(r.seller_id) + '">Reinstate</button>') + '</td>' : '') +
                '</tr>';
        });
        $('#zco-rows').html(html || '<tr><td colspan="7" class="text-center text-muted py-4">No sellers enrolled yet. New sellers join automatically when they sign up.</td></tr>');

        var opts = '<option value="">Choose a seller to enrol…</option>';
        state.candidates.forEach(function (c) {
            opts += '<option value="' + Number(c.id) + '">' + esc((c.store || c.username || 'Seller') + ' (ID ' + c.id + (c.mobile ? ', ' + c.mobile : '') + ')') + '</option>';
        });
        $('#zco-candidate').html(opts);
    }

    function post(url, data, $btn) {
        data[csrfName] = csrfHash;
        var original = $btn ? $btn.html() : null;
        if ($btn) $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        return $.ajax({ url: url, type: 'POST', data: data, dataType: 'json' })
            .done(function (res) {
                if (res.csrfName) { csrfName = res.csrfName; csrfHash = res.csrfHash; }
                if (res.state) { state = res.state; render(); }
                // Call as a method: iziToast's helpers use `this`, so a detached reference throws
                // inside this callback and the .always() below never restores the button.
                iziToast[res.error ? 'error' : 'success']({ message: res.message });
            })
            .fail(function () { iziToast.error({ message: 'Something went wrong. Please try again.' }); })
            .always(function () { if ($btn) $btn.prop('disabled', false).html(original); });
    }

    $('#zco-settings').on('submit', function (e) {
        e.preventDefault();
        post('<?= base_url('admin/commission_offer/save_settings') ?>', {
            enabled: $('#zco-enabled').is(':checked') ? 1 : 0,
            seller_limit: $('#zco-seller-limit').val(),
            orders_per_seller: $('#zco-orders').val()
        }, $('#zco-save'));
    });
    $('#zco-enrol').on('click', function () {
        var id = $('#zco-candidate').val();
        if (!id) { iziToast.error({ message: 'Choose a seller first.' }); return; }
        post('<?= base_url('admin/commission_offer/enrol') ?>', { seller_id: id }, $(this));
    });
    $(document).on('click', '.zco-revoke', function () {
        if (!confirm('Remove this seller from the zero-commission offer? Their remaining free orders will be charged the normal category rate.')) return;
        post('<?= base_url('admin/commission_offer/revoke') ?>', { seller_id: $(this).data('id') }, $(this));
    });
    $(document).on('click', '.zco-reinstate', function () {
        post('<?= base_url('admin/commission_offer/enrol') ?>', { seller_id: $(this).data('id') }, $(this));
    });

    render();
})();
</script>
<?php } ?>
