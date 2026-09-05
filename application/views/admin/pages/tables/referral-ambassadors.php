<?php
/**
 * Ambassador roster.
 *
 * Ranked by referrals that actually PAID, not by signups: signups are free to
 * manufacture, and a list topped by whoever shared the most links is not a list
 * anyone can act on.
 *
 * Rendered server-side rather than through bootstrap-table like the other two
 * lists - this one is a leaderboard of at most a hundred rows that people read
 * top-down, not a searchable ledger, and the tier badge is the point of it.
 */
$currency = get_settings('currency');

/* tier_5 -> 5, so a badge can be labelled with the threshold the user reached
 * without hard-coding the three tiers here. */
$tier_names = [];
foreach ($tiers as $tier) {
    if (preg_match('/(\d+)$/', $tier['code'], $m)) {
        $tier_names[(int) $m[1]] = strtok($tier['name'], ' -');
    }
}
?>
<div class="content-wrapper czr-page">

    <?php $this->load->view('admin/pages/view/referral-head', [
        'czr_title' => 'Ambassador Roster',
        'czr_crumb' => 'Ambassadors',
        'czr_sub'   => 'Your best referrers, ranked by referrals that actually paid out rather than by links shared.',
    ]); ?>

    <section class="content">
        <div class="container-fluid">

            <?php $this->load->view('admin/pages/view/referral-stat-row'); ?>

            <div class="czr-card">
                <div class="czr-card__head">
                    <div>
                        <h2 class="czr-card__title">Top referrers</h2>
                        <p class="czr-card__sub">
                            Ranked by referrals that have actually paid out. Tier bonuses are paid once, as each
                            tier is passed.
                        </p>
                    </div>
                </div>

                <div class="czr-card__body czr-card__body--flush">
                    <?php if (empty($roster)) { ?>
                        <div class="czr-empty">
                            <div class="czr-empty__icon" aria-hidden="true">&#9733;</div>
                            <p class="czr-empty__title">Nobody has referred anyone yet</p>
                            <p class="czr-empty__text">
                                Once a customer or seller invites someone who qualifies, they will appear here
                                ranked by what they have earned.
                            </p>
                        </div>
                    <?php } else { ?>
                        <div class="czr-tablewrap">
                            <table class="czr-table">
                                <thead>
                                    <tr>
                                        <th style="width:64px;">#</th>
                                        <th>Referrer</th>
                                        <th>Tier</th>
                                        <th class="czr-num">Qualified</th>
                                        <th class="czr-num">Total invited</th>
                                        <th class="czr-num">Earned</th>
                                        <th class="czr-num">Of which tier bonuses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roster as $index => $row) { ?>
                                        <?php $rank = $index + 1; ?>
                                        <tr>
                                            <td>
                                                <span class="czr-rank <?= $rank <= 3 ? 'czr-rank--' . $rank : '' ?>"><?= $rank ?></span>
                                            </td>
                                            <td>
                                                <span class="czr-person__name"><?= html_escape($row['username']) ?></span>
                                                <?php if (!empty($row['mobile'])) { ?>
                                                    <span class="czr-person__meta"><?= html_escape($row['mobile']) ?></span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php $tier = (int) $row['ambassador_tier']; ?>
                                                <?php if ($tier > 0) { ?>
                                                    <span class="czr-pill czr-pill--tier">
                                                        <?= html_escape(isset($tier_names[$tier]) ? $tier_names[$tier] : 'Tier ' . $tier) ?>
                                                    </span>
                                                <?php } else { ?>
                                                    <span class="czr-faint">&mdash;</span>
                                                <?php } ?>
                                            </td>
                                            <td class="czr-num czr-strong"><?= (int) $row['qualified'] ?></td>
                                            <td class="czr-num czr-muted"><?= (int) $row['referrals'] ?></td>
                                            <td class="czr-num czr-strong"><?= $currency . number_format((float) $row['earned'], 2) ?></td>
                                            <td class="czr-num czr-muted"><?= $currency . number_format((float) $row['tier_bonuses'], 2) ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </section>
</div>
