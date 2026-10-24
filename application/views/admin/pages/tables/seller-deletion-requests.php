<?php
$settings = get_settings('system_settings', true);
$currency = isset($settings['currency']) ? $settings['currency'] : '';
$fmt_time = function ($t) {
    return $t ? date('d M Y, h:i A', strtotime($t)) : '';
};
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-user-times mr-2 text-primary-theme"></i>Seller Deletion Requests</h4>
                    <p class="text-muted mb-0 small">Sellers cannot delete their own accounts. They ask here, with a note, and only you can delete.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/sellers') ?>">Sellers</a></li>
                        <li class="breadcrumb-item active">Deletion Requests</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (empty($table_ready)) : ?>
                <div class="alert alert-warning">The deletion-request table does not exist yet. Run the database migration (<code>admin/migrate</code>) first.</div>
            <?php elseif (empty($pending)) : ?>
                <div class="alert alert-success mb-4"><i class="fas fa-check-circle mr-1"></i> No deletion requests are waiting.</div>
            <?php endif; ?>

            <?php foreach ($pending as $req) : $imp = $req['impact']; ?>
                <div class="card mb-3">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <strong><?= html_escape($req['display_name']) ?></strong>
                            <?php if ($req['display_shop'] !== '') : ?><span class="text-muted"> &middot; <?= html_escape($req['display_shop']) ?></span><?php endif; ?>
                            <?php if ($req['display_mobile'] !== '') : ?><span class="text-muted"> &middot; <?= html_escape($req['display_mobile']) ?></span><?php endif; ?>
                            <div class="small text-muted">Requested <?= html_escape($fmt_time($req['requested_at'])) ?> &middot;
                                <a href="<?= base_url('admin/sellers/manage-seller?edit_id=' . (int) $req['seller_id']) ?>">Open seller profile</a></div>
                        </div>
                        <div class="mt-2 mt-sm-0">
                            <button type="button" class="btn btn-danger btn-sm mr-1 deletion-delete"
                                    data-id="<?= (int) $req['id'] ?>" data-seller="<?= (int) $req['seller_id'] ?>"
                                    data-name="<?= html_escape($req['display_name']) ?>">
                                <i class="fas fa-trash mr-1"></i>Delete account
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm deletion-decline" data-id="<?= (int) $req['id'] ?>">
                                <i class="fas fa-times mr-1"></i>Decline
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small mb-1">Seller's note</h6>
                        <blockquote class="mb-3 pl-3" style="border-left:3px solid #ddd"><?= nl2br(html_escape($req['note'])) ?></blockquote>

                        <h6 class="text-muted text-uppercase small mb-1">Deleting this account will remove</h6>
                        <ul class="mb-2">
                            <li><strong><?= (int) $imp['products'] ?></strong> product(s)</li>
                            <li><strong><?= (int) $imp['order_lines'] ?></strong> order line(s) <?= $imp['open_orders'] > 0 ? '<span class="badge badge-danger">' . (int) $imp['open_orders'] . ' still open</span>' : '' ?></li>
                            <li>Wallet balance: <strong><?= html_escape($currency . number_format($imp['wallet'], 2)) ?></strong>
                                <?= $imp['wallet'] > 0 ? '<span class="badge badge-warning">pay out first</span>' : '' ?></li>
                        </ul>
                        <?php if ($imp['open_orders'] > 0 || $imp['wallet'] > 0) : ?>
                            <div class="small text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Settle open orders and any wallet balance before deleting &mdash; they are lost with the account.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($closed)) : ?>
                <h6 class="text-muted mt-4">Recently closed</h6>
                <div class="card">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($closed as $req) : $done = $req['status'] === 'completed'; ?>
                            <li class="list-group-item small">
                                <span class="badge badge-<?= $done ? 'secondary' : 'info' ?> mr-2"><?= $done ? 'Deleted' : 'Declined' ?></span>
                                <strong><?= html_escape($req['display_name']) ?></strong>
                                <?php if ($req['display_shop'] !== '') : ?> &mdash; <?= html_escape($req['display_shop']) ?><?php endif; ?>
                                <span class="text-muted">(<?= html_escape($fmt_time($req['reviewed_at'])) ?>)</span>
                                <?php if (!empty($req['admin_note'])) : ?><div class="text-muted">Admin note: <?= html_escape($req['admin_note']) ?></div><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
    (function () {
        function disable(id, on) { $('.deletion-delete[data-id="' + id + '"], .deletion-decline[data-id="' + id + '"]').prop('disabled', on); }

        function review(id, action, note) {
            var data = { id: id, action: action, note: note || '' };
            data[csrfName] = csrfHash;
            return $.ajax({ url: base_url + 'admin/seller_deletions/review', type: 'POST', data: data, dataType: 'json' })
                .then(function (res) { if (res.csrfName) { csrfName = res.csrfName; csrfHash = res.csrfHash; } return res; });
        }

        $(document).on('click', '.deletion-decline', function () {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Decline this request?',
                text: 'The account stays active. Tell the seller why:',
                input: 'textarea',
                inputPlaceholder: 'Reason (shown to the seller)',
                showCancelButton: true,
                confirmButtonText: 'Decline'
            }).then(function (r) {
                if (r.dismiss) return;
                disable(id, true);
                review(id, 'decline', r.value).done(function (res) {
                    if (res.error) { disable(id, false); Swal.fire('Oops...', res.message, 'warning'); return; }
                    Swal.fire('Done', res.message, 'success').then(function () { window.location.reload(); });
                }).fail(function () { disable(id, false); Swal.fire('Oops...', 'Something went wrong. Please try again.', 'error'); });
            });
        });

        $(document).on('click', '.deletion-delete', function () {
            var $b = $(this), id = $b.data('id'), seller = $b.data('seller'), name = $b.data('name');
            Swal.fire({
                title: 'Delete ' + name + '?',
                text: 'This permanently deletes the seller account, their products and order lines, and frees their mobile number. It cannot be undone.',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete the account',
                confirmButtonColor: '#d33'
            }).then(function (r) {
                if (r.dismiss) return;
                disable(id, true);
                // The existing admin delete does the work; the request is closed afterwards.
                $.ajax({ url: base_url + 'admin/sellers/delete_sellers', type: 'GET', data: { id: seller } })
                    .done(function (raw) {
                        var res = raw;
                        if (typeof raw === 'string') {
                            var at = raw.indexOf('{');
                            try { res = JSON.parse(at >= 0 ? raw.slice(at) : raw); } catch (e) { res = null; }
                        }
                        if (!res || res.error) {
                            disable(id, false);
                            Swal.fire('Not deleted', (res && res.message) ? res.message : 'The account could not be deleted.', 'warning');
                            return;
                        }
                        review(id, 'complete').always(function () {
                            Swal.fire('Deleted', 'The seller account has been deleted.', 'success').then(function () { window.location.reload(); });
                        });
                    })
                    .fail(function () { disable(id, false); Swal.fire('Oops...', 'Something went wrong. Please try again.', 'error'); });
            });
        });
    })();
</script>
