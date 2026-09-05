<?php
/**
 * Seller > Refer & Grow.
 *
 * One code, two programmes. A seller invites both other sellers and customers
 * with the same code - which programme a referral falls under is decided by what
 * the person who signed up turns out to be, not by which link they clicked. So
 * the page explains both rather than pretending there are two codes.
 *
 * The list at the bottom is the point of the page: a referring seller who cannot
 * see WHY an invited shop has not paid out assumes the programme is broken. Each
 * row names the milestone it is waiting on.
 */
$currency = get_settings('currency');
$code = isset($referral['code']) ? $referral['code'] : '';
$earned = isset($referral['earned']) ? (float) $referral['earned'] : 0;
$pending = isset($referral['pending_rewards']) ? (float) $referral['pending_rewards'] : 0;
$share_link = referral_share_link($code);
/* Same destination, marked as a scan, so the ledger can separate the printed
 * cards from the forwarded messages. */
$qr_link = referral_qr_link($code);

/* Grouped so each programme can be described in its own words with its real,
 * admin-editable amounts rather than hard-coded copy. */
$grouped = [];
foreach ($programs as $row) {
    $grouped[$row['code']]['name'] = $row['name'];
    $grouped[$row['code']]['status'] = $row['status'];
    if (!empty($row['milestone_code'])) {
        $grouped[$row['code']]['milestones'][] = $row;
    }
}

$share_text = "Sell your handmade work on Cretzo. Use my code " . $code . " when you register. " . $share_link;
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Refer &amp; Grow</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Refer &amp; Grow</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="czrg-hero">
                <div class="czrg-hero__main">
                    <p class="czrg-hero__eyebrow">Your referral code</p>

                    <?php /* Ticket shape: notches cut out of the sides with pseudo-elements,
                             so the coupon reading survives any width without an image. */ ?>
                    <div class="czrg-ticket">
                        <span class="czrg-ticket__code"><?= html_escape($code) ?></span>
                        <button type="button" class="czrg-ticket__copy" data-copy="<?= html_escape($code) ?>">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>

                    <p class="czrg-hero__line">
                        One code, two programmes &mdash; whoever signs up with it is matched to the right
                        one automatically, whether they come to sell or to shop.
                    </p>

                    <div class="czrg-hero__actions">
                        <a class="czrg-btn czrg-btn--wa" target="_blank" rel="noopener"
                           href="https://wa.me/?text=<?= rawurlencode($share_text) ?>">
                            <i class="fab fa-whatsapp"></i> Share on WhatsApp
                        </a>
                        <button type="button" class="czrg-btn czrg-btn--ghost" data-copy="<?= html_escape($share_link) ?>">
                            <i class="fas fa-link"></i> Copy link
                        </button>
                    </div>
                </div>

                <?php /* The QR is the seller half of this feature. A seller meets
                         customers in person - fairs, studio visits - and posts parcels,
                         and a printed code is the only referral channel that works where
                         there is no link to click. Hence "Print card" sitting next to it
                         rather than buried elsewhere. */ ?>
                <div class="czrg-hero__qr">
                    <div class="czrg-qr-frame" id="czrg-qr"
                         data-referral-qr="<?= html_escape($qr_link) ?>"
                         data-qr-size="150"
                         data-qr-filename="cretzo-referral-<?= html_escape($code) ?>"></div>

                    <div class="czrg-qr-actions">
                        <button type="button" class="czrg-qr-link" data-qr-zoom="#czrg-qr">
                            <i class="fas fa-expand"></i> Enlarge
                        </button>
                        <button type="button" class="czrg-qr-link" data-qr-save="#czrg-qr">
                            <i class="fas fa-download"></i> Save
                        </button>
                    </div>

                    <a class="czrg-btn czrg-btn--solid czrg-qr-print" href="<?= base_url('seller/refer/card') ?>" target="_blank" rel="noopener">
                        <i class="fas fa-print"></i> Print card
                    </a>
                </div>

                <div class="czrg-hero__wallet">
                    <div class="czrg-figures">
                        <div>
                            <span class="czrg-figure"><?= $currency . number_format($earned, 2) ?></span>
                            <span class="czrg-figure__label">Earned</span>
                        </div>
                        <div>
                            <span class="czrg-figure"><?= $currency . number_format($pending, 2) ?></span>
                            <span class="czrg-figure__label">On the way</span>
                        </div>
                    </div>

                    <?php /* The split between spendable and withdrawable is the thing sellers
                             get wrong, so it is stated here rather than left to the wallet page. */ ?>
                    <div class="czrg-credit">
                        <div class="czrg-credit__row">
                            <span>Referral credit</span>
                            <strong><?= $currency . number_format((float) $referral_credit, 2) ?></strong>
                        </div>
                        <div class="czrg-credit__row czrg-credit__row--muted">
                            <span>Wallet total</span>
                            <strong><?= $currency . number_format((float) $wallet_balance, 2) ?></strong>
                        </div>
                        <p class="czrg-credit__note">
                            Referral credit pays for your subscription and listings. It cannot be withdrawn to a bank account.
                        </p>
                        <a href="<?= base_url('seller/subscription/manage_subscriptions') ?>" class="czrg-btn czrg-btn--solid">
                            Use it on a plan
                        </a>
                    </div>
                </div>
            </div>

            <!-- what each invite is worth ------------------------------------- -->
            <div class="czrg-programs">
                <?php foreach ([
                    'seller_seller'   => ['title' => 'Invite a seller', 'icon' => 'fa-store', 'sub' => 'Someone who wants to sell their work'],
                    'seller_customer' => ['title' => 'Invite a customer', 'icon' => 'fa-shopping-bag', 'sub' => 'Someone who wants to buy handmade'],
                ] as $key => $meta) {
                    if (empty($grouped[$key])) {
                        continue;
                    }
                    $program = $grouped[$key];
                    ?>
                    <div class="czrg-program <?= empty($program['status']) ? 'is-off' : '' ?>">
                        <div class="czrg-program__head">
                            <span class="czrg-program__icon"><i class="fas <?= $meta['icon'] ?>"></i></span>
                            <div>
                                <h3 class="czrg-program__title"><?= $meta['title'] ?></h3>
                                <p class="czrg-program__sub"><?= $meta['sub'] ?></p>
                            </div>
                            <?php if (empty($program['status'])) { ?>
                                <span class="czrg-tag">Not running yet</span>
                            <?php } ?>
                        </div>

                        <?php if (empty($program['milestones'])) { ?>
                            <p class="czrg-program__empty">No rewards are configured for this yet.</p>
                        <?php } else { ?>
                            <ul class="czrg-milestones">
                                <?php foreach ($program['milestones'] as $milestone) { ?>
                                    <li class="czrg-milestone">
                                        <span class="czrg-milestone__text">
                                            <?= html_escape($milestone['milestone_name']) ?>
                                            <?php if ($milestone['referee_benefit_type'] !== 'none' && (float) $milestone['referee_benefit_value'] > 0) { ?>
                                                <small>
                                                    They get
                                                    <?php if ($milestone['referee_benefit_type'] === 'promo_code') { ?>
                                                        <?= $currency . number_format((float) $milestone['referee_benefit_value'], 0) ?> off their first order
                                                    <?php } elseif ($milestone['referee_benefit_type'] === 'wallet') { ?>
                                                        <?= $currency . number_format((float) $milestone['referee_benefit_value'], 0) ?> wallet credit
                                                    <?php } else { ?>
                                                        <?= (int) $milestone['referee_benefit_value'] ?> extra listings
                                                    <?php } ?>
                                                </small>
                                            <?php } ?>
                                        </span>
                                        <span class="czrg-milestone__amount"><?= $currency . number_format((float) $milestone['referrer_amount'], 0) ?></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <!-- who you invited ----------------------------------------------- -->
            <div class="czrg-panel">
                <div class="czrg-panel__head">
                    <h3>People you invited</h3>
                    <?php if (!empty($invited)) { ?>
                        <span class="czrg-panel__count"><?= count($invited) ?></span>
                    <?php } ?>
                </div>

                <?php if (empty($invited)) { ?>
                    <div class="czrg-empty">
                        <span class="czrg-empty__icon"><i class="fas fa-user-friends"></i></span>
                        <p class="czrg-empty__title">Nobody has used your code yet</p>
                        <p class="czrg-empty__sub">Share it above &mdash; everyone who joins with it appears here, with the milestone they are waiting on.</p>
                    </div>
                <?php } else { ?>
                    <ul class="czrg-people">
                        <?php foreach ($invited as $row) {
                            $initial = mb_strtoupper(mb_substr(trim((string) $row['name']), 0, 1));
                            ?>
                            <li class="czrg-person">
                                <span class="czrg-avatar"><?= html_escape($initial !== '' ? $initial : '?') ?></span>
                                <span class="czrg-person__main">
                                    <span class="czrg-person__name"><?= html_escape($row['name']) ?></span>
                                    <span class="czrg-person__meta">
                                        <?= $row['is_seller'] ? 'Seller' : 'Customer' ?>
                                        &middot; joined <?= date('d M Y', strtotime($row['created_at'])) ?>
                                    </span>
                                </span>
                                <span class="czrg-pill czrg-pill--<?= $row['state'] ?>"><?= html_escape($row['state_text']) ?></span>
                                <span class="czrg-person__amount">
                                    <?php if ((float) $row['earned'] > 0) { ?>
                                        <strong><?= $currency . number_format((float) $row['earned'], 2) ?></strong>
                                    <?php } elseif ((float) $row['pending'] > 0) { ?>
                                        <span class="czrg-soon"><?= $currency . number_format((float) $row['pending'], 2) ?></span>
                                    <?php } else { ?>
                                        <span class="czrg-soon">&mdash;</span>
                                    <?php } ?>
                                </span>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>

        </div>
    </section>
</div>

<style>
    /* Scoped to czrg-. AdminLTE owns everything outside these blocks; this page
       only needs a warmer, less form-like treatment than the panel's defaults,
       because it is something a seller reads rather than fills in. */
    .czrg-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) auto minmax(0, .9fr);
        gap: 18px;
        margin-bottom: 18px;
    }

    .czrg-hero__qr {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 18px;
        border-radius: 16px;
        border: 1px solid rgba(36, 29, 20, .12);
        background: #fff;
    }
    .czrg-qr-frame { width: 150px; }
    .czrg-qr-actions { display: flex; gap: 6px; }
    .czrg-qr-link {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 11px;
        border: 1px solid rgba(36, 29, 20, .12); border-radius: 999px;
        background: #fff; color: #5f5648; font-size: 12px; font-weight: 600; cursor: pointer;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .czrg-qr-link:hover { border-color: transparent; background: #241d14; color: #fff; }
    .czrg-qr-print { margin-top: 2px; }

    .czrg-hero__main {
        padding: 26px 28px;
        border-radius: 16px;
        background:
            radial-gradient(120% 140% at 100% 0%, rgba(242, 130, 46, .2), transparent 60%),
            linear-gradient(135deg, #fbe3bf, #fff8ee);
        border: 1px solid rgba(242, 130, 46, .35);
    }

    .czrg-hero__eyebrow {
        margin: 0 0 10px;
        color: #8b8071;
        font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase;
    }

    .czrg-ticket {
        position: relative;
        display: inline-flex; align-items: center; gap: 14px;
        padding: 12px 20px;
        border: 2px dashed rgba(36, 29, 20, .3);
        border-radius: 12px;
        background: #fff;
    }
    .czrg-ticket::before, .czrg-ticket::after {
        content: ""; position: absolute; top: 50%;
        width: 16px; height: 16px; border-radius: 50%;
        background: #fdf3e6; transform: translateY(-50%);
    }
    .czrg-ticket::before { left: -9px; }
    .czrg-ticket::after { right: -9px; }

    .czrg-ticket__code {
        font-size: 28px; font-weight: 800; letter-spacing: .18em; color: #241d14;
    }
    .czrg-ticket__copy {
        padding: 6px 12px;
        border: 1px solid rgba(36, 29, 20, .12); border-radius: 999px;
        background: #fff; color: #5f5648; font-size: 12.5px; font-weight: 600; cursor: pointer;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .czrg-ticket__copy:hover { border-color: transparent; background: #F2822E; color: #fff; }

    .czrg-hero__line { margin: 14px 0 16px; max-width: 52ch; color: #5f5648; font-size: 14px; }
    .czrg-hero__actions { display: flex; flex-wrap: wrap; gap: 10px; }

    .czrg-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 18px; border-radius: 999px; border: 1px solid transparent;
        font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer;
        transition: transform .12s ease, background .15s ease, color .15s ease, border-color .15s ease;
    }
    .czrg-btn:hover { transform: translateY(-1px); text-decoration: none; }
    .czrg-btn--wa { background: #25d366; color: #fff; }
    .czrg-btn--wa:hover { background: #1fb855; color: #fff; }
    .czrg-btn--ghost { background: #fff; border-color: rgba(36, 29, 20, .12); color: #241d14; }
    .czrg-btn--ghost:hover { border-color: #F2822E; color: #d2691e; }
    .czrg-btn--solid { background: #241d14; color: #fff; padding: 8px 16px; font-size: 13px; }
    .czrg-btn--solid:hover { background: #3b3123; color: #fff; }

    .czrg-hero__wallet {
        display: flex; flex-direction: column; gap: 14px;
        padding: 22px;
        border-radius: 16px; border: 1px solid rgba(36, 29, 20, .12); background: #fff;
    }
    .czrg-figures { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .czrg-figure { display: block; font-size: 22px; font-weight: 700; color: #241d14; }
    .czrg-figure__label { display: block; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #8b8071; }

    .czrg-credit { padding-top: 12px; border-top: 1px solid rgba(36, 29, 20, .1); }
    .czrg-credit__row { display: flex; justify-content: space-between; align-items: baseline; font-size: 14px; color: #241d14; }
    .czrg-credit__row--muted { color: #8b8071; font-size: 13px; }
    .czrg-credit__note { margin: 8px 0 12px; color: #8b8071; font-size: 12.5px; }

    /* ------------------------------------------------------------ programmes */
    .czrg-programs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 18px; }
    .czrg-program {
        padding: 20px; border-radius: 16px; border: 1px solid rgba(36, 29, 20, .12); background: #fff;
    }
    .czrg-program.is-off { background: #fbfbfa; }
    .czrg-program__head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
    .czrg-program__icon {
        flex: none; display: grid; place-items: center;
        width: 40px; height: 40px; border-radius: 12px;
        background: #fbe3bf; color: #d2691e; font-size: 17px;
    }
    .czrg-program__title { margin: 0; font-size: 16px; font-weight: 700; color: #241d14; }
    .czrg-program__sub { margin: 0; font-size: 12.5px; color: #8b8071; }
    .czrg-program__empty { margin: 0; color: #8b8071; font-size: 13.5px; }
    .czrg-tag {
        margin-left: auto; padding: 3px 9px; border-radius: 999px;
        background: rgba(36, 29, 20, .06); color: #8b8071;
        font-size: 11px; font-weight: 600; white-space: nowrap;
    }

    .czrg-milestones { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
    .czrg-milestone {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
        padding: 10px 12px; border-radius: 10px; background: rgba(36, 29, 20, .035);
    }
    .czrg-milestone__text { font-size: 13.5px; color: #241d14; }
    .czrg-milestone__text small { display: block; color: #8b8071; font-size: 12px; }
    .czrg-milestone__amount { font-weight: 700; color: #1a7f4b; white-space: nowrap; }

    /* ---------------------------------------------------------------- people */
    .czrg-panel { border-radius: 16px; border: 1px solid rgba(36, 29, 20, .12); background: #fff; padding: 20px; }
    .czrg-panel__head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .czrg-panel__head h3 { margin: 0; font-size: 16px; font-weight: 700; color: #241d14; }
    .czrg-panel__count {
        padding: 2px 9px; border-radius: 999px; background: #fbe3bf; color: #241d14;
        font-size: 12px; font-weight: 700;
    }

    .czrg-people { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
    .czrg-person {
        display: grid; grid-template-columns: 38px minmax(0, 1fr) auto auto;
        align-items: center; gap: 12px;
        padding: 12px; border-radius: 12px; border: 1px solid rgba(36, 29, 20, .1);
    }
    .czrg-avatar {
        display: grid; place-items: center; width: 38px; height: 38px;
        border-radius: 50%; background: #fbe3bf; color: #241d14; font-weight: 700;
    }
    .czrg-person__name { display: block; font-weight: 600; color: #241d14; }
    .czrg-person__meta { display: block; font-size: 12.5px; color: #8b8071; }
    .czrg-person__amount { font-size: 15px; color: #241d14; }
    .czrg-soon { color: #8b8071; }

    .czrg-pill { padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .czrg-pill--earned { background: rgba(26, 127, 75, .1); color: #1a7f4b; }
    .czrg-pill--pending { background: rgba(183, 121, 31, .12); color: #b7791f; }
    .czrg-pill--waiting { background: rgba(36, 29, 20, .06); color: #8b8071; }

    .czrg-empty { padding: 32px 20px; text-align: center; }
    .czrg-empty__icon {
        display: grid; place-items: center; width: 54px; height: 54px; margin: 0 auto 12px;
        border-radius: 50%; background: #fbe3bf; color: #d2691e; font-size: 24px;
    }
    .czrg-empty__title { margin: 0 0 4px; font-weight: 700; color: #241d14; }
    .czrg-empty__sub { margin: 0 auto; max-width: 46ch; color: #8b8071; font-size: 13.5px; }

    @media (max-width: 1199px) {
        .czrg-hero { grid-template-columns: minmax(0, 1fr) auto; }
        .czrg-hero__wallet { grid-column: 1 / -1; }
    }
    @media (max-width: 991px) {
        .czrg-hero, .czrg-programs { grid-template-columns: minmax(0, 1fr); }
        .czrg-hero__qr { align-self: start; }
    }
    @media (max-width: 575px) {
        .czrg-person { grid-template-columns: 38px minmax(0, 1fr) auto; }
        .czrg-pill { grid-column: 2 / -1; justify-self: start; }
    }
</style>

<script>
    $(function () {
        // navigator.clipboard is unavailable on an insecure origin, so a hidden
        // textarea is the fallback rather than a button that silently does nothing.
        $(document).on('click', '[data-copy]', function () {
            var text = $(this).data('copy');
            var $btn = $(this);
            var done = function () {
                var original = $btn.html();
                $btn.html('<i class="fas fa-check"></i> Copied');
                setTimeout(function () { $btn.html(original); }, 1600);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done);
                return;
            }

            var $tmp = $('<textarea>').val(text).css({ position: 'fixed', opacity: 0 }).appendTo('body');
            $tmp[0].select();
            try { document.execCommand('copy'); done(); } catch (e) {}
            $tmp.remove();
        });
    });
</script>
