<?php
/**
 * My Account > Refer & Earn.
 *
 * Built on the shared account shell (czap), like every other page under
 * /my-account, so the hero, nav and card chrome are not reinvented here.
 *
 * The page answers three questions in the order a referrer actually has them:
 *
 *   1. what is my code, and how do I send it
 *   2. what have I earned, and what is still coming
 *   3. why is that one still pending
 *
 * Point 3 is why the per-referral list names the milestone and the date instead
 * of showing a status word. "Pending" with no explanation is what generates
 * support tickets; "Reward due 12 Sep" does not.
 *
 * The tier display is one connected rail rather than three separate progress
 * bars: the tiers are a single journey with three stops, and three bars that
 * each fill independently tell that story wrongly - somebody at 6 referrals is
 * not "100% of Starter and 60% of Champion", they are six stops along one road.
 */

$currency = isset($settings['currency']) ? $settings['currency'] : '';

$code = isset($referral['code']) ? $referral['code'] : '';
$earned = isset($referral['earned']) ? (float) $referral['earned'] : 0;
$pending = isset($referral['pending_rewards']) ? (float) $referral['pending_rewards'] : 0;
$total = isset($referral['total']) ? (int) $referral['total'] : 0;
$qualified = isset($qualified_count) ? (int) $qualified_count : 0;

$share_link = referral_share_link($code);
/* The QR encodes the same destination with src=qr on it, which is the only way
 * the ledger can later tell a scanned card from a forwarded message. */
$qr_link = referral_qr_link($code);
$min_order = (float) (isset($referral_policy['min_order_amount']) ? $referral_policy['min_order_amount'] : 499);
$reward_amount = (float) (isset($referral_policy['promo_discount']) ? $referral_policy['promo_discount'] : 100);

/* Written once and reused by every channel, so WhatsApp, a copied link and a
 * pasted message all say the same thing. */
$share_text = "I'm shopping handmade on Cretzo - use my code " . $code . " when you sign up and we both get "
    . $currency . number_format($reward_amount, 0) . ". " . $share_link;

/* Tier thresholds are parsed from the milestone code, the same way the engine
 * reads them, so this rail cannot drift from what actually pays. */
$tiers = [];
$tier_max = 0;
foreach ((array) $referral_tiers as $tier) {
    if (!preg_match('/(\d+)$/', $tier['code'], $m)) {
        continue;
    }
    $tiers[] = [
        'at'     => (int) $m[1],
        'name'   => trim(strtok($tier['name'], '-')),
        'amount' => (float) $tier['referrer_amount'],
    ];
    $tier_max = max($tier_max, (int) $m[1]);
}
$rail_pct = ($tier_max > 0) ? min(100, ($qualified / $tier_max) * 100) : 0;

/* --------------------------------------------------------------- content -- */
ob_start(); ?>

<div class="czref">

    <!-- 1. THE CODE ------------------------------------------------------ -->
    <section class="czref__invite">
        <div class="czref__invite-body">
            <p class="czref__eyebrow">Your referral code</p>

            <?php /* Ticket shape: the notches are pseudo-elements on the chip, so the
                     "torn coupon" reading survives any width without an image. */ ?>
            <div class="czref__ticket">
                <span class="czref__ticket-code"><?= html_escape($code) ?></span>
                <button type="button" class="czref__ticket-copy" data-copy="<?= html_escape($code) ?>" aria-label="Copy referral code">
                    <i class="uil uil-copy"></i><span>Copy</span>
                </button>
            </div>

            <p class="czref__invite-line">
                They get <strong><?= $currency ?><?= number_format($reward_amount, 0) ?></strong> off their first order.
                You get <strong><?= $currency ?><?= number_format($reward_amount, 0) ?></strong> when it is delivered.
            </p>

            <div class="czref__share">
                <a class="czref__btn czref__btn--wa" target="_blank" rel="noopener"
                   href="https://wa.me/?text=<?= rawurlencode($share_text) ?>">
                    <i class="uil uil-whatsapp"></i> Share on WhatsApp
                </a>
                <button type="button" class="czref__btn czref__btn--ghost" data-copy="<?= html_escape($share_link) ?>">
                    <i class="uil uil-link"></i> Copy link
                </button>
            </div>
        </div>

        <?php /* The QR replaces what was a decorative gift mark. It is the same link
                 as the buttons on the left, in the form you can hold up to somebody
                 standing in front of you - which is the one thing a copied link
                 cannot do. Enlarge is the primary action, not download: showing it
                 on screen is how a buyer actually uses this. */ ?>
        <div class="czref__qr">
            <div class="czref__qr-frame" id="czref-qr"
                 data-referral-qr="<?= html_escape($qr_link) ?>"
                 data-qr-size="168"
                 data-qr-filename="cretzo-referral-<?= html_escape($code) ?>"></div>

            <div class="czref__qr-actions">
                <button type="button" class="czref__qr-link" data-qr-zoom="#czref-qr">
                    <i class="uil uil-focus"></i> Enlarge
                </button>
                <button type="button" class="czref__qr-link" data-qr-save="#czref-qr">
                    <i class="uil uil-download-alt"></i> Save
                </button>
            </div>
        </div>
    </section>

    <!-- 2. HOW IT WORKS -------------------------------------------------- -->
    <section class="czref__steps" aria-label="How referring works">
        <div class="czref__step">
            <span class="czref__step-num">1</span>
            <p class="czref__step-title">Send your code</p>
            <p class="czref__step-sub">Send the link, or let them scan the code &mdash; both carry the same invite.</p>
        </div>
        <div class="czref__step">
            <span class="czref__step-num">2</span>
            <p class="czref__step-title">They order</p>
            <p class="czref__step-sub"><?= $currency ?><?= number_format($min_order, 0) ?> or more, using your code at sign-up.</p>
        </div>
        <div class="czref__step">
            <span class="czref__step-num">3</span>
            <p class="czref__step-title">You both earn</p>
            <p class="czref__step-sub">Credited once their order is delivered and the return window closes.</p>
        </div>
    </section>

    <!-- 3. THE MONEY ----------------------------------------------------- -->
    <section class="czref__stats">
        <div class="czref__stat czref__stat--hero">
            <span class="czref__stat-label">Earned so far</span>
            <span class="czref__stat-value"><?= $currency ?><?= number_format($earned, 2) ?></span>
        </div>
        <div class="czref__stat">
            <span class="czref__stat-label">On the way</span>
            <span class="czref__stat-value"><?= $currency ?><?= number_format($pending, 2) ?></span>
        </div>
        <div class="czref__stat">
            <span class="czref__stat-label">People joined</span>
            <span class="czref__stat-value"><?= $total ?></span>
        </div>
        <div class="czref__stat">
            <span class="czref__stat-label">Counted for a tier</span>
            <span class="czref__stat-value"><?= $qualified ?></span>
        </div>
    </section>

    <?php if ($earned > 0 || $pending > 0) { ?>
        <p class="czref__note">
            <i class="uil uil-info-circle"></i>
            <span>
                Referral money is spending credit on Cretzo - it is not withdrawable to a bank account -
                and expires <?= (int) (isset($referral_policy['credit_expiry_months']) ? $referral_policy['credit_expiry_months'] : 12) ?> months after it is credited.
            </span>
        </p>
    <?php } ?>

    <!-- 4. AMBASSADOR RAIL ----------------------------------------------- -->
    <?php if (!empty($tiers)) { ?>
        <section class="czref__block">
            <div class="czref__block-head">
                <h3 class="czref__block-title">Ambassador tiers</h3>
                <p class="czref__block-sub">A referral counts once it has actually paid out. Each tier is paid once, as you pass it.</p>
            </div>

            <div class="czref__rail">
                <?php /* The stops are half a label wide on each side of their dot, so the
                         track is inset by exactly that much - otherwise the last stop's
                         label hangs off the right edge and gets clipped. */ ?>
                <div class="czref__rail-inner">
                    <div class="czref__rail-track">
                        <span class="czref__rail-fill" style="width: <?= $rail_pct ?>%"></span>
                    </div>

                    <ol class="czref__rail-stops">
                    <?php foreach ($tiers as $tier) {
                        $reached = ($qualified >= $tier['at']);
                        $left = ($tier_max > 0) ? ($tier['at'] / $tier_max) * 100 : 0;
                        ?>
                        <li class="czref__stop <?= $reached ? 'is-reached' : '' ?>" style="left: <?= $left ?>%">
                            <span class="czref__stop-dot">
                                <?php if ($reached) { ?><i class="uil uil-check"></i><?php } ?>
                            </span>
                            <span class="czref__stop-label">
                                <strong><?= html_escape($tier['name']) ?></strong>
                                <span><?= $tier['at'] ?> referrals &middot; <?= $currency ?><?= number_format($tier['amount'], 0) ?></span>
                            </span>
                            </li>
                        <?php } ?>
                    </ol>
                </div>
            </div>

            <p class="czref__rail-status">
                <?php if ($qualified >= $tier_max) { ?>
                    Every tier reached. <?= $qualified ?> referrals have paid out.
                <?php } else {
                    $next = null;
                    foreach ($tiers as $tier) {
                        if ($qualified < $tier['at']) {
                            $next = $tier;
                            break;
                        }
                    }
                    ?>
                    <strong><?= $qualified ?></strong> of <strong><?= (int) $next['at'] ?></strong> referrals toward
                    <?= html_escape($next['name']) ?>
                    &mdash; <?= (int) $next['at'] - $qualified ?> to go.
                <?php } ?>
            </p>
        </section>
    <?php } ?>

    <!-- 5. WHO JOINED ----------------------------------------------------- -->
    <section class="czref__block">
        <div class="czref__block-head">
            <h3 class="czref__block-title">Your referrals</h3>
            <?php if (!empty($referral_rows)) { ?>
                <p class="czref__block-sub"><?= count($referral_rows) ?> <?= count($referral_rows) === 1 ? 'person has' : 'people have' ?> used your code.</p>
            <?php } ?>
        </div>

        <?php if (empty($referral_rows)) { ?>
            <div class="czref__empty">
                <span class="czref__empty-icon"><i class="uil uil-users-alt"></i></span>
                <p class="czref__empty-title">No one has used your code yet</p>
                <p class="czref__empty-sub">Share it above &mdash; everyone who joins with it will appear here, with what they have earned you.</p>
            </div>
        <?php } else { ?>
            <ul class="czref__people">
                <?php foreach ($referral_rows as $row) {
                    $initial = mb_strtoupper(mb_substr(trim((string) $row['referee_name']), 0, 1));
                    ?>
                    <li class="czref__person">
                        <span class="czref__avatar" aria-hidden="true"><?= html_escape($initial !== '' ? $initial : '?') ?></span>

                        <span class="czref__person-main">
                            <span class="czref__person-name"><?= html_escape($row['referee_name']) ?></span>
                            <span class="czref__person-date">joined <?= date('d M Y', strtotime($row['created_at'])) ?></span>
                        </span>

                        <span class="czref__pill czref__pill--<?= $row['state'] ?>">
                            <?= html_escape($row['state_text']) ?>
                        </span>

                        <span class="czref__person-amount">
                            <?php if ((float) $row['earned'] > 0) { ?>
                                <strong><?= $currency ?><?= number_format((float) $row['earned'], 2) ?></strong>
                            <?php } elseif ((float) $row['pending'] > 0) { ?>
                                <span class="czref__person-soon"><?= $currency ?><?= number_format((float) $row['pending'], 2) ?></span>
                            <?php } else { ?>
                                <span class="czref__person-soon">&mdash;</span>
                            <?php } ?>
                        </span>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </section>
</div>

<style>
    /* Everything is scoped to .czref - the account shell owns the page outside it.
       Colours come from the storefront's own tokens with literal fallbacks, so this
       block does not depend on load order. */
    .czref {
        --czr-ink: #241d14;
        --czr-ink-2: #5f5648;
        --czr-ink-3: #8b8071;
        --czr-orange: var(--color-orange, #F2822E);
        --czr-orange-dark: #d2691e;
        --czr-cream: var(--color-secondary, #fbe3bf);
        --czr-line: rgba(36, 29, 20, .12);
        --czr-good: #1a7f4b;
        --czr-wait: #b7791f;

        display: flex;
        flex-direction: column;
        gap: 26px;
    }

    /* ------------------------------------------------------------ invite -- */
    .czref__invite {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 26px 28px;
        border-radius: 18px;
        overflow: hidden;
        background:
            radial-gradient(120% 140% at 100% 0%, rgba(242, 130, 46, .22), transparent 60%),
            linear-gradient(135deg, var(--czr-cream), #fff8ee);
        border: 1px solid rgba(242, 130, 46, .35);
    }

    .czref__eyebrow {
        margin: 0 0 10px;
        color: var(--czr-ink-3);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    /* The code as a torn ticket: two notches cut out of the sides. */
    .czref__ticket {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 14px;
        padding: 12px 20px;
        border: 2px dashed rgba(36, 29, 20, .3);
        border-radius: 12px;
        background: #fff;
    }
    .czref__ticket::before,
    .czref__ticket::after {
        content: "";
        position: absolute;
        top: 50%;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #fdf3e6;
        transform: translateY(-50%);
    }
    .czref__ticket::before { left: -9px; }
    .czref__ticket::after { right: -9px; }

    .czref__ticket-code {
        font-size: clamp(22px, 4.4vw, 30px);
        font-weight: 800;
        letter-spacing: .18em;
        color: var(--czr-ink);
        font-variant-numeric: tabular-nums;
    }

    .czref__ticket-copy {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border: 1px solid var(--czr-line);
        border-radius: 999px;
        background: #fff;
        color: var(--czr-ink-2);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .czref__ticket-copy:hover {
        border-color: transparent;
        background: var(--czr-orange);
        color: #fff;
    }

    .czref__invite-line {
        margin: 14px 0 16px;
        max-width: 46ch;
        color: var(--czr-ink-2);
        font-size: 14.5px;
    }
    .czref__invite-line strong { color: var(--czr-ink); }

    .czref__share { display: flex; flex-wrap: wrap; gap: 10px; }

    .czref__btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: transform .12s ease, background .15s ease, color .15s ease, border-color .15s ease;
    }
    .czref__btn:hover { transform: translateY(-1px); }
    /* The theme decorates every <a> with an ::before underline. */
    .czref__btn::before { content: none; display: none; }

    .czref__btn--wa { background: #25d366; color: #fff; }
    .czref__btn--wa:hover { background: #1fb855; color: #fff; }
    .czref__btn--ghost { background: #fff; border-color: var(--czr-line); color: var(--czr-ink); }
    .czref__btn--ghost:hover { border-color: var(--czr-orange); color: var(--czr-orange-dark); }

    .czref__qr {
        flex: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .czref__qr-frame {
        width: 168px;
        padding: 10px;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(36, 29, 20, .1);
    }
    .czref__qr-actions { display: flex; gap: 6px; }
    .czref__qr-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border: 1px solid var(--czr-line);
        border-radius: 999px;
        background: rgba(255, 255, 255, .8);
        color: var(--czr-ink-2);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .czref__qr-link:hover {
        border-color: transparent;
        background: var(--czr-ink);
        color: #fff;
    }

    /* ------------------------------------------------------------- steps -- */
    .czref__steps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .czref__step {
        position: relative;
        padding: 16px 16px 16px 52px;
        border: 1px solid var(--czr-line);
        border-radius: 14px;
        background: #fff;
    }
    .czref__step-num {
        position: absolute;
        left: 16px;
        top: 16px;
        display: grid;
        place-items: center;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--czr-orange);
        color: #fff;
        font-size: 13px;
        font-weight: 700;
    }
    .czref__step-title { margin: 0 0 2px; font-weight: 700; color: var(--czr-ink); font-size: 14.5px; }
    .czref__step-sub { margin: 0; color: var(--czr-ink-3); font-size: 13px; }

    /* ------------------------------------------------------------- stats -- */
    .czref__stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }
    .czref__stat {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 16px;
        border: 1px solid var(--czr-line);
        border-radius: 14px;
        background: #fff;
    }
    /* Only the figure people actually came to see gets the emphasis. */
    .czref__stat--hero {
        background: var(--czr-ink);
        border-color: var(--czr-ink);
    }
    .czref__stat--hero .czref__stat-label { color: rgba(255, 255, 255, .7); }
    .czref__stat--hero .czref__stat-value { color: #fff; }

    .czref__stat-label {
        font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
        color: var(--czr-ink-3);
    }
    .czref__stat-value {
        font-size: 22px; font-weight: 700; color: var(--czr-ink);
        font-variant-numeric: tabular-nums;
    }

    .czref__note {
        display: flex; align-items: flex-start; gap: 8px;
        margin: 0; padding: 12px 14px;
        border-radius: 12px;
        background: rgba(36, 29, 20, .045);
        color: var(--czr-ink-2);
        font-size: 13.5px;
    }
    .czref__note i { color: var(--czr-orange); font-size: 17px; line-height: 1.4; }

    /* ------------------------------------------------------------ blocks -- */
    .czref__block-head { margin-bottom: 14px; }
    .czref__block-title { margin: 0 0 3px; font-size: 17px; font-weight: 700; color: var(--czr-ink); }
    .czref__block-sub { margin: 0; color: var(--czr-ink-3); font-size: 13.5px; }

    /* -------------------------------------------------------------- rail -- */
    /* One road with three stops, not three independent bars. */
    .czref__rail { padding: 8px 0 74px; }
    /* Inset by half a stop label (60px of the 120px width), so a stop sitting at
       0% or 100% still has room for its text. */
    .czref__rail-inner { position: relative; margin: 0 60px; }
    .czref__rail-track {
        position: relative;
        height: 6px;
        border-radius: 999px;
        background: rgba(36, 29, 20, .1);
    }
    .czref__rail-fill {
        display: block; height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--czr-orange), #f0a45c);
        transition: width .4s ease;
    }
    .czref__rail-stops { list-style: none; margin: 0; padding: 0; }
    .czref__stop {
        position: absolute;
        top: 8px;
        transform: translateX(-50%);
        text-align: center;
        width: 120px;
    }
    .czref__stop-dot {
        display: grid; place-items: center;
        width: 22px; height: 22px; margin: -8px auto 8px;
        border-radius: 50%;
        border: 2px solid rgba(36, 29, 20, .18);
        background: #fff;
        color: #fff;
        font-size: 12px;
    }
    .czref__stop.is-reached .czref__stop-dot {
        border-color: var(--czr-good);
        background: var(--czr-good);
    }
    .czref__stop-label { display: block; font-size: 12px; color: var(--czr-ink-3); line-height: 1.35; }
    .czref__stop-label strong { display: block; color: var(--czr-ink); font-size: 13px; }
    .czref__stop.is-reached .czref__stop-label strong { color: var(--czr-good); }

    .czref__rail-status { margin: 0; color: var(--czr-ink-2); font-size: 13.5px; }

    /* ------------------------------------------------------------ people -- */
    .czref__people { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
    .czref__person {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid var(--czr-line);
        border-radius: 14px;
        background: #fff;
    }
    .czref__avatar {
        display: grid; place-items: center;
        width: 38px; height: 38px;
        border-radius: 50%;
        background: var(--czr-cream);
        color: var(--czr-ink);
        font-weight: 700;
    }
    .czref__person-name { display: block; font-weight: 600; color: var(--czr-ink); }
    .czref__person-date { display: block; font-size: 12.5px; color: var(--czr-ink-3); }

    .czref__pill {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .czref__pill--earned { background: rgba(26, 127, 75, .1); color: var(--czr-good); }
    .czref__pill--pending { background: rgba(183, 121, 31, .12); color: var(--czr-wait); }
    .czref__pill--waiting { background: rgba(36, 29, 20, .06); color: var(--czr-ink-3); }

    .czref__person-amount { font-size: 15px; color: var(--czr-ink); font-variant-numeric: tabular-nums; }
    .czref__person-soon { color: var(--czr-ink-3); }

    .czref__empty {
        padding: 34px 24px;
        text-align: center;
        border: 1px dashed rgba(36, 29, 20, .18);
        border-radius: 14px;
        background: #fff;
    }
    .czref__empty-icon {
        display: grid; place-items: center;
        width: 54px; height: 54px; margin: 0 auto 12px;
        border-radius: 50%;
        background: var(--czr-cream);
        color: var(--czr-orange);
        font-size: 26px;
    }
    .czref__empty-title { margin: 0 0 4px; font-weight: 700; color: var(--czr-ink); }
    .czref__empty-sub { margin: 0 auto; max-width: 44ch; color: var(--czr-ink-3); font-size: 13.5px; }

    /* -------------------------------------------------------- responsive -- */
    @media (max-width: 991px) {
        .czref__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .czref__steps { grid-template-columns: minmax(0, 1fr); }
        .czref__invite { flex-direction: column; align-items: flex-start; }
        .czref__qr { align-self: center; }
    }

    @media (max-width: 767px) {
        .czref__invite { padding: 22px 18px; }
        /* The rail's absolutely-positioned stops cannot survive a narrow column,
           so on phones the tiers become a plain stacked list. */
        .czref__rail { padding: 0; }
        .czref__rail-inner { margin: 0; }
        .czref__rail-track { display: none; }
        .czref__rail-stops { display: flex; flex-direction: column; gap: 10px; }
        .czref__stop {
            position: static;
            transform: none;
            width: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
            padding: 10px 12px;
            border: 1px solid var(--czr-line);
            border-radius: 12px;
            background: #fff;
        }
        .czref__stop.is-reached { border-color: var(--czr-good); }
        .czref__stop-dot { margin: 0; }
        .czref__person { grid-template-columns: 38px minmax(0, 1fr) auto; }
        .czref__pill { grid-column: 2 / -1; justify-self: start; }
    }
</style>

<script>
    // Copy without a library. navigator.clipboard is unavailable on an insecure
    // origin (this site is served over http in development), so a hidden textarea
    // is the fallback rather than a button that silently does nothing.
    $(function () {
        $(document).on('click', '[data-copy]', function () {
            var text = $(this).data('copy');
            var $btn = $(this);
            var original = $btn.html();
            var done = function () {
                $btn.html('<i class="uil uil-check"></i><span>Copied</span>');
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

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Refer & Earn',
    'page_sub'     => 'Invite friends, earn credit on Cretzo',
    'page_icon'    => 'uil-share-alt',
    'page_content' => $page_content,
]); ?>
