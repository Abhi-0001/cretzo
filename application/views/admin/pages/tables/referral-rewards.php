<?php
/**
 * Referral rewards - the money, and the review queue.
 *
 * One table, filtered by status. "Needs review" is the default view because it
 * is the only tab that represents work: flagged rewards a human has to decide
 * about before the automatic release window runs out and pays them anyway.
 *
 * The three actions all post to Referral::review(), which calls the engine -
 * approving, rejecting and reversing here use exactly the same code as the
 * nightly run, including the caps and the never-negative-wallet rule.
 */
$status = $this->input->get('status', true);
$status = in_array($status, ['queue', 'pending', 'credited', 'reversed', 'rejected', 'all'], true) ? $status : 'queue';

$tabs = [
    'queue'    => 'Needs review',
    'pending'  => 'Pending',
    'credited' => 'Paid',
    'reversed' => 'Reversed',
    'rejected' => 'Rejected',
    'all'      => 'All',
];

/* Only the review queue carries a count: it is the one tab that represents
 * work, and a number beside the others would be a query per tab for something
 * nobody acts on. */
$queue_count = isset($totals['flagged_count']) ? (int) $totals['flagged_count'] : 0;
?>
<div class="content-wrapper czr-page">

    <?php $this->load->view('admin/pages/view/referral-head', [
        'czr_title' => 'Referral Rewards',
        'czr_crumb' => 'Rewards',
        'czr_sub'   => 'Every reward the engine has raised, and the queue of the ones a human still has to decide about.',
    ]); ?>

    <section class="content">
        <div class="container-fluid">

            <?php $this->load->view('admin/pages/view/referral-stat-row'); ?>

            <div class="czr-card">

                <nav class="czr-tabs" aria-label="Filter rewards by status">
                    <?php foreach ($tabs as $key => $label) { ?>
                        <a class="czr-tab <?= $status === $key ? 'is-active' : '' ?>"
                           href="<?= base_url('admin/referral/queue?status=' . $key) ?>">
                            <?= $label ?>
                            <?php if ($key === 'queue' && $queue_count > 0) { ?>
                                <span class="czr-tab__count"><?= $queue_count ?></span>
                            <?php } ?>
                        </a>
                    <?php } ?>
                </nav>

                <?php if ($status === 'queue') { ?>
                    <div class="czr-card__head" style="border-bottom:0; padding-bottom:0;">
                        <p class="czr-card__sub" style="margin:0;">
                            These rewards were flagged when the referral was created &mdash; usually several signups
                            from one address or device, which honest referrals trip too. Unreviewed rewards release
                            themselves after the review window set on the Programs screen, so an empty queue means
                            nothing is stuck.
                        </p>
                    </div>
                <?php } ?>

                <div class="czr-card__body czr-card__body--flush">
                    <div class="czr-tablewrap">
                        <table class="table"
                               id="referral-rewards-table"
                               data-toggle="table"
                               data-url="<?= base_url('admin/referral/rewards_list') ?>"
                               data-side-pagination="server"
                               data-pagination="true"
                               data-page-list="[10, 25, 50, 100]"
                               data-search="true"
                               data-show-refresh="true"
                               data-trim-on-search="false"
                               data-sort-name="id"
                               data-sort-order="desc"
                               data-mobile-responsive="true"
                               data-query-params="referralRewardParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="beneficiary">Paid to</th>
                                    <th data-field="program">Program</th>
                                    <th data-field="milestone">Milestone</th>
                                    <th data-field="amount" data-sortable="true">Amount</th>
                                    <th data-field="status">Status</th>
                                    <th data-field="order">Order</th>
                                    <th data-field="due" data-sortable="true">Payable from</th>
                                    <th data-field="note">Note</th>
                                    <th data-field="operate">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
    // The status filter travels with every page/sort/search request, so paging
    // through "Needs review" cannot silently fall back to showing everything.
    function referralRewardParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search,
            status: '<?= $status ?>'
        };
    }

    $(function () {
        $(document).on('click', '.referral-review', function () {
            var $btn = $(this);
            var id = $btn.data('id');
            var action = $btn.data('action');

            var copy = {
                approve: {
                    title: 'Approve this reward?',
                    text: 'It will be credited as soon as the hold on the order ends. Program caps still apply.',
                    confirm: 'Approve',
                    needsNote: false
                },
                reject: {
                    title: 'Reject this reward?',
                    text: 'Nothing is paid. The referral stays on record.',
                    confirm: 'Reject',
                    needsNote: true
                },
                reverse: {
                    title: 'Reverse this paid reward?',
                    text: 'The money is taken back from the wallet. If it has been spent, the shortfall comes out of that user\'s next reward - the wallet is never pushed below zero.',
                    confirm: 'Reverse',
                    needsNote: true
                }
            }[action];

            Swal.fire({
                title: copy.title,
                text: copy.text,
                icon: action === 'approve' ? 'question' : 'warning',
                input: copy.needsNote ? 'text' : undefined,
                inputPlaceholder: 'Reason (shown in the reward history)',
                showCancelButton: true,
                confirmButtonText: copy.confirm
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $btn.prop('disabled', true);

                var payload = { id: id, action: action, note: result.value || '' };
                payload[csrfName] = csrfHash;

                $.ajax({
                    type: 'POST',
                    url: '<?= base_url('admin/referral/review') ?>',
                    data: payload,
                    dataType: 'json'
                }).done(function (res) {
                    csrfName = res.csrfName || csrfName;
                    csrfHash = res.csrfHash || csrfHash;

                    if (res.error) {
                        iziToast.error({ message: res.message, timeout: 6000 });
                    } else {
                        iziToast.success({ message: res.message, timeout: 6000 });
                    }
                    $('#referral-rewards-table').bootstrapTable('refresh');
                }).fail(function () {
                    iziToast.error({ message: 'Could not complete that. Please try again.' });
                }).always(function () {
                    $btn.prop('disabled', false);
                });
            });
        });
    });
</script>
