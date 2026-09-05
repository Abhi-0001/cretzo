<?php
/**
 * Headline figures, shown at the top of every referral screen.
 *
 * The four numbers an owner asks first: what has this cost, what is committed
 * but not yet paid, how much of this month's budget is left, and is anything
 * waiting on me. `$totals` is set by Referral::page() for all four screens.
 *
 * State is carried on the accent rail and the meter rather than by colouring
 * the whole tile. The previous version swapped in bg-danger / bg-warning, which
 * turned the card red or amber and took the text contrast down with it - the
 * one moment the number matters most is the moment it became hardest to read.
 */
$currency = get_settings('currency');
$totals = isset($totals) ? $totals : [];
$flagged = isset($totals['flagged_count']) ? (int) $totals['flagged_count'] : 0;
$budget = isset($totals['budget']) ? (float) $totals['budget'] : 0;
$this_month = isset($totals['this_month']) ? (float) $totals['this_month'] : 0;
$used_pct = ($budget > 0) ? min(100, round(($this_month / $budget) * 100)) : 0;

$budget_state = ($budget <= 0) ? '' : ($used_pct >= 100 ? 'danger' : ($used_pct >= 80 ? 'warn' : 'good'));
?>
<div class="czr-stats">

    <div class="czr-stat">
        <p class="czr-stat__label">Paid out, all time</p>
        <p class="czr-stat__value"><?= $currency . number_format((float) ($totals['credited'] ?? 0), 2) ?></p>
        <p class="czr-stat__note">Credited to wallets</p>
    </div>

    <div class="czr-stat">
        <p class="czr-stat__label">Earned, not yet paid</p>
        <p class="czr-stat__value"><?= $currency . number_format((float) ($totals['pending'] ?? 0), 2) ?></p>
        <p class="czr-stat__note">Committed, awaiting release</p>
    </div>

    <div class="czr-stat <?= $budget_state ? 'czr-stat--' . $budget_state : '' ?>">
        <p class="czr-stat__label">Budget left this month</p>
        <p class="czr-stat__value"><?= $currency . number_format((float) ($totals['budget_left'] ?? 0), 2) ?></p>
        <?php if ($budget > 0) { ?>
            <div class="czr-meter" role="img"
                 aria-label="<?= $used_pct ?> percent of this month's budget used">
                <div class="czr-meter__fill <?= $used_pct >= 100 ? 'czr-meter__fill--danger' : ($used_pct >= 80 ? 'czr-meter__fill--warn' : '') ?>"
                     style="width: <?= (int) $used_pct ?>%;"></div>
            </div>
            <p class="czr-stat__note"><?= $used_pct ?>% of <?= $currency . number_format($budget, 0) ?> used</p>
        <?php } else { ?>
            <p class="czr-stat__note">No monthly cap set</p>
        <?php } ?>
    </div>

    <div class="czr-stat <?= $flagged > 0 ? 'czr-stat--warn' : '' ?>">
        <p class="czr-stat__label">Waiting for review</p>
        <p class="czr-stat__value"><?= $flagged ?></p>
        <p class="czr-stat__note">
            <?php if ($flagged > 0) { ?>
                <a href="<?= base_url('admin/referral/queue?status=queue') ?>">Open the queue &rarr;</a>
            <?php } else { ?>
                Nothing needs a decision
            <?php } ?>
        </p>
    </div>

</div>
