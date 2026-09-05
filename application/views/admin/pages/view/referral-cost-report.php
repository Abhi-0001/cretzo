<?php
/**
 * What the programme costs, by programme and month.
 *
 * This is the number that decides whether the programme continues, so it counts
 * credited money only: not rewards still pending (not yet spent) and not
 * reversals (money that came back). The monthly rows are compared against the
 * budget so an owner can see how close the current month is running.
 */
$currency = get_settings('currency');
$budget = (float) $policy['monthly_budget_cap'];

/* Rows arrive as one line per (month, programme). Grouping them here keeps the
 * SQL simple and the month totals honest - they are summed from the same rows
 * the table prints, so the two can never disagree. */
$by_month = [];
foreach ($cost_rows as $row) {
    $month = $row['month'];
    if (!isset($by_month[$month])) {
        $by_month[$month] = ['total' => 0, 'rewards' => 0, 'programs' => []];
    }
    $by_month[$month]['total'] += (float) $row['spent'];
    $by_month[$month]['rewards'] += (int) $row['rewards'];
    $by_month[$month]['programs'][] = $row;
}
?>
<div class="content-wrapper czr-page">

    <?php $this->load->view('admin/pages/view/referral-head', [
        'czr_title' => 'Referral Cost Report',
        'czr_crumb' => 'Cost report',
        'czr_sub'   => 'What the programme has actually cost, by month and by programme. Credited money only &mdash; pending rewards are a liability, not yet a cost.',
    ]); ?>

    <section class="content">
        <div class="container-fluid">

            <?php $this->load->view('admin/pages/view/referral-stat-row'); ?>

            <div class="czr-card">
                <div class="czr-card__head">
                    <div>
                        <h2 class="czr-card__title">Spend by month</h2>
                        <p class="czr-card__sub">
                            Credited rewards only, last 6 months. Budget:
                            <strong><?= $currency . number_format($budget, 2) ?></strong> a month.
                        </p>
                    </div>
                </div>

                <div class="czr-card__body czr-card__body--flush">
                    <?php if (empty($by_month)) { ?>
                        <?php
                        $live = 0;
                        foreach ($programs as $program) {
                            $live += !empty($program['status']) ? 1 : 0;
                        }
                        ?>
                        <div class="czr-empty">
                            <div class="czr-empty__icon" aria-hidden="true">&#8377;</div>
                            <p class="czr-empty__title">No rewards have been paid yet</p>
                            <p class="czr-empty__text">
                                <?php if ($live === 0) { ?>
                                    No programme is live, so nothing can be earned.
                                    <a href="<?= base_url('admin/referral/programs') ?>">Switch one on &rarr;</a>
                                <?php } else { ?>
                                    A programme is live, so this fills in as referrals qualify and their rewards
                                    are credited.
                                <?php } ?>
                            </p>
                        </div>
                    <?php } else { ?>
                        <div class="czr-tablewrap">
                            <table class="czr-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Program</th>
                                        <th class="czr-num">Rewards</th>
                                        <th class="czr-num">Spent</th>
                                        <th style="width: 26%;">Against budget</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($by_month as $month => $data) {
                                        $pct = ($budget > 0) ? min(100, round(($data['total'] / $budget) * 100)) : 0;
                                        ?>
                                        <tr class="czr-row-month">
                                            <td><?= date('M Y', strtotime($month . '-01')) ?></td>
                                            <td>All programs</td>
                                            <td class="czr-num"><?= (int) $data['rewards'] ?></td>
                                            <td class="czr-num"><?= $currency . number_format($data['total'], 2) ?></td>
                                            <td>
                                                <?php if ($budget > 0) { ?>
                                                    <div class="czr-meter" role="img"
                                                         aria-label="<?= $pct ?> percent of budget used in <?= date('M Y', strtotime($month . '-01')) ?>">
                                                        <div class="czr-meter__fill <?= $pct >= 100 ? 'czr-meter__fill--danger' : ($pct >= 80 ? 'czr-meter__fill--warn' : '') ?>"
                                                             style="width: <?= (int) $pct ?>%;"></div>
                                                    </div>
                                                    <span class="czr-stat__note"><?= $pct ?>% of budget</span>
                                                <?php } else { ?>
                                                    <span class="czr-faint">No cap set</span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php foreach ($data['programs'] as $row) { ?>
                                            <tr class="czr-row-sub">
                                                <td></td>
                                                <td class="czr-muted"><?= html_escape(!empty($row['program']) ? $row['program'] : 'Unmatched') ?></td>
                                                <td class="czr-num czr-muted"><?= (int) $row['rewards'] ?></td>
                                                <td class="czr-num czr-muted"><?= $currency . number_format((float) $row['spent'], 2) ?></td>
                                                <td class="czr-faint">
                                                    <?= $data['total'] > 0 ? round(((float) $row['spent'] / $data['total']) * 100) : 0 ?>% of the month
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="czr-card">
                <div class="czr-card__head">
                    <div>
                        <h2 class="czr-card__title">Committed but not yet paid</h2>
                        <p class="czr-card__sub">
                            Rewards already earned that will be credited when their hold ends. This is a
                            liability, not a cost &mdash; yet.
                        </p>
                    </div>
                </div>
                <div class="czr-card__body">
                    <p class="czr-stat__value czr-stat__value--sm" style="margin:0;">
                        <?= $currency . number_format((float) ($totals['pending'] ?? 0), 2) ?>
                    </p>
                    <?php if ((float) ($totals['shortfall'] ?? 0) > 0) { ?>
                        <p class="czr-stat__note" style="color: var(--czr-red); margin-top:.5rem;">
                            <?= $currency . number_format((float) $totals['shortfall'], 2) ?> could not be recovered
                            from reversals and will be deducted from those users&rsquo; future rewards.
                        </p>
                    <?php } ?>
                </div>
            </div>

        </div>
    </section>
</div>
