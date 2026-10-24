<?php
$fmt_time = function ($t) {
    return $t ? date('d M Y, h:i A', strtotime($t)) : '';
};
$is_file = function ($field) {
    return in_array($field, Seller_profile_change_model::FILE_FIELDS, true);
};
/** One value cell: a file becomes a link, text is escaped, empty says so. */
$cell = function ($field, $value) use ($is_file) {
    $value = (string) $value;
    if ($value === '') {
        return '<span class="text-muted">&mdash; (empty)</span>';
    }
    if ($is_file($field)) {
        return '<a href="' . html_escape(base_url($value)) . '" target="_blank" rel="noopener"><i class="fas fa-file mr-1"></i>View document</a>';
    }
    if ($field === 'is_gst_registered') {
        return $value === '1' ? 'GST registered' : 'Not GST registered';
    }
    return html_escape($value);
};
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-user-check mr-2 text-primary-theme"></i>Seller Profile Changes</h4>
                    <p class="text-muted mb-0 small">Major edits approved sellers made to personal, business, bank and document details. Nothing here is live until you approve it.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/sellers') ?>">Sellers</a></li>
                        <li class="breadcrumb-item active">Profile Changes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <?php if (empty($table_ready)) : ?>
                <div class="alert alert-warning">The change-request table does not exist yet. Run the database migration (<code>admin/migrate</code>) first.</div>
            <?php elseif (empty($pending)) : ?>
                <div class="alert alert-success mb-4"><i class="fas fa-check-circle mr-1"></i> No changes are waiting for approval.</div>
            <?php endif; ?>

            <?php foreach ($pending as $req) : ?>
                <div class="card mb-3" id="req-<?= (int) $req['id'] ?>">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <strong><?= html_escape($req['username'] ?: ('Seller #' . $req['seller_id'])) ?></strong>
                            <?php if (!empty($req['shop_name'])) : ?><span class="text-muted"> &middot; <?= html_escape($req['shop_name']) ?></span><?php endif; ?>
                            <?php if (!empty($req['mobile'])) : ?><span class="text-muted"> &middot; <?= html_escape($req['mobile']) ?></span><?php endif; ?>
                            <div class="small text-muted">Requested <?= html_escape($fmt_time($req['requested_at'])) ?> &middot;
                                <a href="<?= base_url('admin/sellers/manage-seller?edit_id=' . (int) $req['seller_id']) ?>">Open seller profile</a></div>
                        </div>
                        <div class="mt-2 mt-sm-0">
                            <button type="button" class="btn btn-success btn-sm mr-1 review-change" data-id="<?= (int) $req['id'] ?>" data-action="approve"><i class="fas fa-check mr-1"></i>Approve</button>
                            <button type="button" class="btn btn-outline-danger btn-sm review-change" data-id="<?= (int) $req['id'] ?>" data-action="reject"><i class="fas fa-times mr-1"></i>Reject</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                    <tr><th style="width:26%">Detail</th><th style="width:37%">Currently live</th><th style="width:37%">Requested</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($req['changes'] as $field => $c) : ?>
                                        <tr>
                                            <td><?= html_escape($c['label']) ?></td>
                                            <td><?= $cell($field, $c['old']) ?></td>
                                            <td class="font-weight-bold"><?= $cell($field, $c['new']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ([['Recently approved', $approved, 'success'], ['Recently rejected', $rejected, 'danger']] as $group) :
                if (empty($group[1])) continue; ?>
                <h6 class="text-muted mt-4"><?= $group[0] ?></h6>
                <div class="card">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($group[1] as $req) : ?>
                            <li class="list-group-item small">
                                <span class="badge badge-<?= $group[2] ?> mr-2"><?= $group[2] === 'success' ? 'Approved' : 'Rejected' ?></span>
                                <strong><?= html_escape($req['username'] ?: ('Seller #' . $req['seller_id'])) ?></strong>
                                &mdash; <?= html_escape(implode(', ', array_column($req['changes'], 'label'))) ?>
                                <span class="text-muted">(<?= html_escape($fmt_time($req['reviewed_at'])) ?>)</span>
                                <?php if (!empty($req['reject_reason'])) : ?><div class="text-muted">Reason: <?= html_escape($req['reject_reason']) ?></div><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

        </div>
    </section>
</div>

<script>
    $(document).on('click', '.review-change', function () {
        var $btn = $(this), id = $btn.data('id'), action = $btn.data('action');

        function send(reason) {
            var data = { id: id, action: action, reason: reason || '' };
            data[csrfName] = csrfHash;
            $('.review-change[data-id="' + id + '"]').prop('disabled', true);
            $.ajax({ url: base_url + 'admin/seller_changes/review', type: 'POST', data: data, dataType: 'json' })
                .done(function (res) {
                    if (res.csrfName) { csrfName = res.csrfName; csrfHash = res.csrfHash; }
                    if (res.error) {
                        $('.review-change[data-id="' + id + '"]').prop('disabled', false);
                        Swal.fire('Oops...', res.message, 'warning');
                        return;
                    }
                    Swal.fire('Done', res.message, 'success').then(function () { window.location.reload(); });
                })
                .fail(function () {
                    $('.review-change[data-id="' + id + '"]').prop('disabled', false);
                    Swal.fire('Oops...', 'Something went wrong. Please try again.', 'error');
                });
        }

        if (action === 'reject') {
            Swal.fire({
                title: 'Reject these changes?',
                text: 'The seller keeps their current details. Tell them why:',
                input: 'textarea',
                inputPlaceholder: 'Reason (shown to the seller)',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#d33'
            }).then(function (res) {
                if (res.dismiss) return;
                send(res.value);
            });
        } else {
            Swal.fire({
                title: 'Approve these changes?',
                text: 'They go live on the seller profile immediately.',
                showCancelButton: true,
                confirmButtonText: 'Approve'
            }).then(function (res) {
                if (res.dismiss) return;
                send('');
            });
        }
    });
</script>
