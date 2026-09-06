<?php
/**
 * Referral programme configuration.
 *
 * The one screen in this section that commits money: switching a programme on
 * here is what makes the platform start paying people. So each programme saves
 * on its own - there is no "save all" that could flip four programmes live in
 * one click - and the live ones are visibly marked.
 *
 * Amounts sit on milestones, policy that spans every programme (budget, caps,
 * expiry) sits in the panel at the bottom, which writes the `referral_settings`
 * row the engine reads.
 */
$currency = get_settings('currency');
$return_days = (int) (get_settings('system_settings', true)['max_product_return_days'] ?? 0);

$live_count = 0;
foreach ($programs as $p) {
    $live_count += !empty($p['status']) ? 1 : 0;
}

$benefit_types = [
    'none'          => 'Nothing',
    'wallet'        => 'Wallet credit',
    'promo_code'    => 'Discount code',
    'listing_bonus' => 'Extra listings',
];
?>
<div class="content-wrapper czr-page">

    <?php $this->load->view('admin/pages/view/referral-head', [
        /* Plain text, not markup: referral-head.php runs html_escape() on the
           title, so a pre-escaped "&amp;" was escaped a second time and the page
           rendered the entity itself - "Refer &amp; Earn Programs". */
        'czr_title' => 'Refer & Earn Programs',
        'czr_crumb' => 'Programs',
        'czr_sub'   => 'Switching a program on here is what makes the platform start paying people, so each one saves on its own.',
        'czr_actions' => '<span class="czr-pill ' . ($live_count > 0 ? 'czr-pill--live' : 'czr-pill--off') . '">'
            . ($live_count > 0 ? $live_count . ' live' : 'None live') . '</span>',
    ]); ?>

    <?php
    /* One mark per programme, keyed on the programme code. Four near-identical
       white cards down a page are hard to navigate by title alone; a shape and a
       tint give each one something to recognise it by. Drawn inline rather than
       loaded as icons because there are four of them and they never change. */
    $czr_marks = [
        'customer_customer' => ['tint' => 'blue',   'path' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17.5" cy="9.5" r="2.5"/><path d="M15.5 15.2A5 5 0 0 1 22 20"/>'],
        'seller_seller'     => ['tint' => 'amber',  'path' => '<path d="M4 9h16l-1 11H5L4 9z"/><path d="M4 9l1.5-5h13L20 9"/><path d="M9 13h6"/>'],
        'seller_customer'   => ['tint' => 'violet', 'path' => '<path d="M3 9h10l-.8 11H3.8L3 9z"/><path d="M3 9l1-4h8l1 4"/><circle cx="18" cy="9" r="2.5"/><path d="M14.5 20c0-2.5 1.6-4.5 3.5-4.5s3.5 2 3.5 4.5"/>'],
        'ambassador'        => ['tint' => 'green',  'path' => '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L7 22l5-2.5L17 22l-1.5-8.5"/>'],
    ];
    ?>

    <section class="content">
        <div class="container-fluid">

            <?php $this->load->view('admin/pages/view/referral-stat-row'); ?>

            <?php /* Three separate saves live on this page and only one of them flips
                     the switch that commits money, so the order is spelled out rather
                     than left to be inferred from the layout. */ ?>
            <div class="czr-card czr-howto">
                <div class="czr-card__head">
                    <div>
                        <h2 class="czr-card__title">How to make a program live</h2>
                        <p class="czr-card__sub">
                            Each program below has its own <b>Save program</b> button, and each milestone
                            inside it has its own <b>Save</b>. Nothing on this page saves anything else.
                        </p>
                    </div>
                    <span class="czr-pill <?= $live_count > 0 ? 'czr-pill--live' : 'czr-pill--warn' ?>">
                        <?= $live_count > 0 ? $live_count . ' live now' : 'Nothing is live' ?>
                    </span>
                </div>

                <div class="czr-card__body">
                    <ol class="czr-howto__list">
                        <li><b>Set what it pays</b>
                            In the milestone row on the right, fill in <i>Referrer gets</i>, <i>New user gets</i>,
                            <i>Value</i> and <i>Min order</i>, leave <i>Active</i> on, and press that row&rsquo;s
                            <b>Save</b>. A milestone that is off pays nothing even in a live program.</li>
                        <li><b>Set the limit and window</b>
                            On the left, put a <i>Program budget cap</i> (or leave it blank for no per-program cap)
                            and, if the offer should only run for a period, the <i>Starts</i> and <i>Ends</i> dates.
                            Blank dates mean it runs until you switch it off.</li>
                        <li><b>Turn on &ldquo;Program is live&rdquo;</b>
                            Click the switch at the top of the left panel &mdash; anywhere on that row works. The chip
                            on it changes to <b>On</b> and the row turns green. Nothing is saved yet.</li>
                        <li><b>Press &ldquo;Save program&rdquo;</b>
                            This is the step that commits it. The page reloads and the badge on the program reads
                            <b>Live</b>. Confirm the header above says <?= $live_count > 0 ? 'the right number of programs live' : '1 live' ?>.</li>
                    </ol>

                    <p class="czr-howto__foot">
                        <?php if ($live_count === 0) { ?>
                            <b>Right now no program is switched on</b>, so no rewards are being created. Referrals
                            are still recorded and appear in the ledger &mdash; they simply earn nothing until a
                            program is made live.
                        <?php } else { ?>
                            Also check <b>Program settings</b> at the bottom of this page: the monthly budget applies
                            across every program, and a reward stops at that cap however a program is configured.
                        <?php } ?>
                    </p>
                </div>
            </div>

            <!-- ------------------------------------------------------- programmes -->
            <div class="czr-programs">
                <?php foreach ($programs as $program) { ?>
                    <div class="czr-prog <?= !empty($program['status']) ? 'czr-prog--live' : '' ?>">

                        <?php $mark = isset($czr_marks[$program['code']]) ? $czr_marks[$program['code']] : ['tint' => 'blue', 'path' => '<circle cx="12" cy="12" r="8"/>']; ?>
                        <div class="czr-prog__head">
                            <span class="czr-mark czr-mark--<?= $mark['tint'] ?>" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $mark['path'] ?></svg>
                            </span>

                            <div class="czr-prog__ident">
                                <h2 class="czr-prog__name"><?= html_escape($program['name']) ?></h2>
                                <p class="czr-prog__meta">
                                    <span class="czr-flow"><?= html_escape($program['referrer_role']) ?> <b>&rarr;</b> <?= html_escape($program['referee_role']) ?></span>
                                    <span class="czr-code"><?= html_escape($program['code']) ?></span>
                                    <span class="czr-prog__count"><?= count($program['milestones']) ?> milestone<?= count($program['milestones']) === 1 ? '' : 's' ?></span>
                                </p>
                            </div>

                            <?php
                            /* "Live" on its own is a half-truth for a programme whose
                               window has not opened or has already closed: the switch is
                               on, the engine checks the dates too, and nothing pays. The
                               badge says which of the three it actually is - the same
                               comparison Referral_engine::active_referral_for() makes. */
                            $czr_now = date('Y-m-d H:i:s');
                            $czr_pending = !empty($program['starts_at']) && $program['starts_at'] > $czr_now;
                            $czr_expired = !empty($program['ends_at']) && $program['ends_at'] < $czr_now;

                            if (empty($program['status'])) {
                                $czr_state = ['czr-pill--off', 'Off', ''];
                            } elseif ($czr_pending) {
                                $czr_state = ['czr-pill--warn', 'Scheduled',
                                    'On, but not paying yet: it starts ' . date('d M Y', strtotime($program['starts_at'])) . '.'];
                            } elseif ($czr_expired) {
                                $czr_state = ['czr-pill--warn', 'Ended',
                                    'On, but no longer paying: it ended ' . date('d M Y', strtotime($program['ends_at'])) . '. Clear the end date to run it again.'];
                            } else {
                                $czr_state = ['czr-pill--live', 'Live', ''];
                            }
                            ?>
                            <span class="czr-pill <?= $czr_state[0] ?>"><?= $czr_state[1] ?></span>
                        </div>

                        <div class="czr-prog__body">
                            <div class="czr-prog__settings">
                            <form class="referral-program-form" data-id="<?= (int) $program['id'] ?>">

                                <?php /* First, not last: switching a programme on is what starts the
                                         platform paying people, and it was previously below four
                                         fields most admins never touch.

                                         The whole row is the <label>, so a click anywhere on it -
                                         knob, title or note - toggles the switch. It used to be a
                                         <div> holding an invisible input and a decorative track
                                         <span>, which left the title text as the only dependable
                                         hit target: clicking the knob itself did nothing. */ ?>
                                <?php if ($czr_state[2] !== '') { ?>
                                    <p class="czr-window-warn"><?= $czr_state[2] ?></p>
                                <?php } ?>

                                <label class="czr-switch czr-switch--panel">
                                    <input type="checkbox" class="czr-switch__input" id="prog-status-<?= (int) $program['id'] ?>"
                                           name="status" value="1" <?= !empty($program['status']) ? 'checked' : '' ?>>
                                    <span class="czr-switch__copy">
                                        <span class="czr-switch__title">Program is live</span>
                                        <span class="czr-switch__note">Click to change, then press Save program below.</span>
                                    </span>
                                    <span class="czr-switch__state" aria-hidden="true"></span>
                                </label>

                                <p class="czr-sublabel czr-sublabel--tight">Limits and window</p>

                                <div class="czr-grid czr-grid--settings">
                                    <div class="czr-field">
                                        <label class="czr-label" for="prog-budget-<?= (int) $program['id'] ?>">Program budget cap (<?= $currency ?>)</label>
                                        <input type="number" step="0.01" min="0" class="czr-input"
                                               id="prog-budget-<?= (int) $program['id'] ?>" name="budget_cap"
                                               value="<?= $program['budget_cap'] !== null ? html_escape($program['budget_cap']) : '' ?>"
                                               placeholder="no cap">
                                        <p class="czr-hint">Blank = only the overall monthly budget applies.</p>
                                    </div>
                                    <div class="czr-field">
                                        <label class="czr-label" for="prog-spent-<?= (int) $program['id'] ?>">Spent to date</label>
                                        <input type="text" class="czr-input" id="prog-spent-<?= (int) $program['id'] ?>"
                                               value="<?= $currency . number_format((float) $program['spent_to_date'], 2) ?>" readonly>
                                    </div>

                                    <?php /* Dates, not datetimes. A datetime-local field is wider than
                                             half this panel, so the time part was cut off mid-value and
                                             took the date down with it - and nobody schedules a referral
                                             offer to the minute. The controller pins the saved times to
                                             the start and the end of the chosen days. */ ?>
                                    <div class="czr-field czr-field--span2">
                                        <label class="czr-label" for="prog-start-<?= (int) $program['id'] ?>">Runs between (optional)</label>
                                        <div class="czr-window">
                                            <input type="date" class="czr-input" id="prog-start-<?= (int) $program['id'] ?>" name="starts_at"
                                                   aria-label="First day the program runs"
                                                   value="<?= !empty($program['starts_at']) ? date('Y-m-d', strtotime($program['starts_at'])) : '' ?>">
                                            <input type="date" class="czr-input" id="prog-end-<?= (int) $program['id'] ?>" name="ends_at"
                                                   aria-label="Last day the program runs"
                                                   value="<?= !empty($program['ends_at']) ? date('Y-m-d', strtotime($program['ends_at'])) : '' ?>">
                                        </div>
                                        <p class="czr-hint">
                                            First day &rarr; last day, both included. Leave both blank to run until you
                                            switch the program off.
                                        </p>
                                    </div>
                                </div>

                                <div style="margin-top:1rem;">
                                    <button type="submit" class="czr-btn czr-btn--primary czr-btn--sm">Save program</button>
                                </div>
                            </form>
                            </div>

                            <div class="czr-prog__milestones">
                            <p class="czr-sublabel">Milestones &mdash; what each one pays, and when</p>

                            <div class="czr-ms-list">
                            <?php foreach ($program['milestones'] as $milestone) { ?>
                                <form class="referral-milestone-form czr-ms" data-id="<?= (int) $milestone['id'] ?>">

                                    <div class="czr-ms__head">
                                        <span class="czr-ms__step" aria-hidden="true"><?= (int) $milestone['sequence'] ?></span>
                                        <span class="czr-ms__name"><?= html_escape($milestone['name']) ?></span>
                                        <span class="czr-code"><?= html_escape($milestone['code']) ?></span>

                                        <?php /* Active and Save live in the row's own header, so every
                                                 milestone row is the same height whatever is in it. */ ?>
                                        <span class="czr-ms__controls">
                                            <label class="czr-switch czr-switch--inline">
                                                <input type="checkbox" class="czr-switch__input" id="ms-status-<?= (int) $milestone['id'] ?>"
                                                       name="status" value="1" <?= !empty($milestone['status']) ? 'checked' : '' ?>>
                                                <span class="czr-switch__copy">
                                                    <span class="czr-switch__title">Active</span>
                                                </span>
                                            </label>
                                            <button type="submit" class="czr-btn czr-btn--ghost czr-btn--sm">Save</button>
                                        </span>
                                    </div>

                                    <div class="czr-grid czr-grid--ms">
                                        <div class="czr-field">
                                            <label class="czr-label" for="ms-ref-<?= (int) $milestone['id'] ?>">Referrer gets (<?= $currency ?>)<span class="czr-req" title="Required">*</span></label>
                                            <input type="number" step="0.01" min="0" class="czr-input"
                                                   id="ms-ref-<?= (int) $milestone['id'] ?>" name="referrer_amount" required
                                                   value="<?= html_escape($milestone['referrer_amount']) ?>">
                                        </div>
                                        <div class="czr-field">
                                            <label class="czr-label" for="ms-type-<?= (int) $milestone['id'] ?>">New user gets</label>
                                            <select class="czr-select" id="ms-type-<?= (int) $milestone['id'] ?>" name="referee_benefit_type">
                                                <?php foreach ($benefit_types as $value => $label) { ?>
                                                    <option value="<?= $value ?>" <?= $milestone['referee_benefit_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="czr-field">
                                            <label class="czr-label" for="ms-val-<?= (int) $milestone['id'] ?>">Value<span class="czr-req" title="Required">*</span></label>
                                            <input type="number" step="0.01" min="0" class="czr-input"
                                                   id="ms-val-<?= (int) $milestone['id'] ?>" name="referee_benefit_value" required
                                                   value="<?= html_escape($milestone['referee_benefit_value']) ?>">
                                        </div>
                                        <div class="czr-field">
                                            <label class="czr-label" for="ms-min-<?= (int) $milestone['id'] ?>">Min order (<?= $currency ?>)<span class="czr-req" title="Required">*</span></label>
                                            <input type="number" step="0.01" min="0" class="czr-input"
                                                   id="ms-min-<?= (int) $milestone['id'] ?>" name="min_order_amount" required
                                                   value="<?= html_escape($milestone['min_order_amount']) ?>">
                                        </div>
                                        <div class="czr-field">
                                            <label class="czr-label" for="ms-hold-<?= (int) $milestone['id'] ?>">Hold days</label>
                                            <input type="number" min="0" class="czr-input"
                                                   id="ms-hold-<?= (int) $milestone['id'] ?>" name="hold_days"
                                                   value="<?= $milestone['hold_days'] !== null ? html_escape($milestone['hold_days']) : '' ?>"
                                                   placeholder="auto">
                                        </div>
                                    </div>

                                    <p class="czr-hint czr-ms__hint">
                                        Hold days blank = follow the store&rsquo;s return window (<?= $return_days ?> days)
                                        plus <?= (int) $policy['hold_days_after_return_window'] ?>.
                                    </p>
                                </form>
                            <?php } ?>
                            </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- ------------------------------------------------ programme-wide policy -->
            <div class="czr-card">
                <div class="czr-card__head">
                    <div>
                        <h2 class="czr-card__title">Program settings</h2>
                        <p class="czr-card__sub">These apply across every program.</p>
                    </div>
                </div>

                <div class="czr-card__body">
                    <form id="referral-policy-form">

                        <p class="czr-formkey"><span class="czr-req">*</span> Required &mdash; a blank here is read as zero, which stops payouts rather than removing a limit.</p>

                        <p class="czr-sublabel" style="margin-top:0;">Budget and caps</p>
                        <div class="czr-grid">
                            <div class="czr-field">
                                <label class="czr-label" for="pol-budget">Monthly budget, all programs (<?= $currency ?>)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" step="0.01" min="0" class="czr-input" id="pol-budget"
                                       name="monthly_budget_cap" required value="<?= html_escape($policy['monthly_budget_cap']) ?>">
                            </div>
                            <div class="czr-field">
                                <label class="czr-label" for="pol-perref">Per referrer, per month (<?= $currency ?>)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" step="0.01" min="0" class="czr-input" id="pol-perref"
                                       name="per_referrer_monthly_cap" required value="<?= html_escape($policy['per_referrer_monthly_cap']) ?>">
                            </div>
                            <div class="czr-field">
                                <label class="czr-label" for="pol-minorder">Minimum qualifying order (<?= $currency ?>)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" step="0.01" min="0" class="czr-input" id="pol-minorder"
                                       name="min_order_amount" required value="<?= html_escape($policy['min_order_amount']) ?>">
                            </div>
                            <div class="czr-field">
                                <label class="czr-label" for="pol-holddays">Days after return window<span class="czr-req" title="Required">*</span></label>
                                <input type="number" min="0" class="czr-input" id="pol-holddays"
                                       name="hold_days_after_return_window" required value="<?= html_escape($policy['hold_days_after_return_window']) ?>">
                            </div>
                        </div>

                        <p class="czr-sublabel">Timing and expiry</p>
                        <div class="czr-grid">
                            <div class="czr-field">
                                <label class="czr-label" for="pol-review">Flagged reward review window (hours)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" min="0" class="czr-input" id="pol-review"
                                       name="flag_review_hold_hours" required value="<?= html_escape($policy['flag_review_hold_hours']) ?>">
                                <p class="czr-hint">After this, an unreviewed flagged reward pays itself if the order is clean.</p>
                            </div>
                            <div class="czr-field">
                                <label class="czr-label" for="pol-expiry">Credit expires after (months)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" min="0" class="czr-input" id="pol-expiry"
                                       name="credit_expiry_months" required value="<?= html_escape($policy['credit_expiry_months']) ?>">
                            </div>
                            <div class="czr-field">
                                <label class="czr-label" for="pol-notice">Expiry notice (days before)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" min="0" class="czr-input" id="pol-notice"
                                       name="expiry_notice_days" required value="<?= html_escape(isset($policy['expiry_notice_days']) ? $policy['expiry_notice_days'] : 30) ?>">
                            </div>
                        </div>

                        <p class="czr-sublabel">New-customer discount</p>
                        <div class="czr-grid">
                            <div class="czr-field">
                                <label class="czr-label" for="pol-discount">First-order discount (<?= $currency ?>)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" step="0.01" min="0" class="czr-input" id="pol-discount"
                                       name="promo_discount" required value="<?= html_escape(isset($policy['promo_discount']) ? $policy['promo_discount'] : 100) ?>">
                            </div>
                            <div class="czr-field">
                                <label class="czr-label" for="pol-mincart">Discount minimum cart (<?= $currency ?>)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" step="0.01" min="0" class="czr-input" id="pol-mincart"
                                       name="promo_min_cart" required value="<?= html_escape(isset($policy['promo_min_cart']) ? $policy['promo_min_cart'] : 499) ?>">
                            </div>
                            <div class="czr-field">
                                <label class="czr-label" for="pol-validity">Discount valid for (days)<span class="czr-req" title="Required">*</span></label>
                                <input type="number" min="0" class="czr-input" id="pol-validity"
                                       name="promo_validity_days" required value="<?= html_escape(isset($policy['promo_validity_days']) ? $policy['promo_validity_days'] : 30) ?>">
                            </div>
                        </div>

                        <hr class="czr-rule">

                        <label class="czr-switch">
                            <input type="checkbox" class="czr-switch__input" id="policy-wallet-orders"
                                   name="wallet_orders_qualify" value="1" <?= $policy['wallet_orders_qualify'] == '1' ? 'checked' : '' ?>>
                            <span class="czr-switch__copy">
                                <span class="czr-switch__title">Orders paid with wallet balance still earn a referral reward</span>
                                <span class="czr-switch__note">
                                    Off closes the loop where referral credit buys an order that earns more referral
                                    credit. Turn it off if reward farming appears.
                                </span>
                            </span>
                            <span class="czr-switch__state" aria-hidden="true"></span>
                        </label>

                        <label class="czr-switch">
                            <input type="checkbox" class="czr-switch__input" id="policy-negative"
                                   name="allow_negative_on_reversal" value="1" <?= $policy['allow_negative_on_reversal'] == '1' ? 'checked' : '' ?>>
                            <span class="czr-switch__copy">
                                <span class="czr-switch__title">A reversal may push a wallet below zero</span>
                                <span class="czr-switch__note">
                                    Off (the current policy): only what is in the wallet is recovered, and the rest is
                                    taken out of that user&rsquo;s next referral reward.
                                </span>
                            </span>
                            <span class="czr-switch__state" aria-hidden="true"></span>
                        </label>

                        <label class="czr-switch">
                            <input type="checkbox" class="czr-switch__input" id="policy-withdrawable"
                                   name="withdrawable" value="1" <?= $policy['withdrawable'] == '1' ? 'checked' : '' ?>>
                            <span class="czr-switch__copy">
                                <span class="czr-switch__title">Referral credit can be withdrawn as cash</span>
                                <span class="czr-switch__note czr-switch__note--warn">
                                    Off (the current policy): referral money can be spent on the platform but never
                                    paid out. Turning this on makes the program a cash-out target and changes its tax
                                    position.
                                </span>
                            </span>
                            <span class="czr-switch__state" aria-hidden="true"></span>
                        </label>

                        <div style="margin-top:1.15rem;">
                            <button type="submit" class="czr-btn czr-btn--primary">Save program settings</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
    $(function () {
        // One helper for all three forms: they differ only in endpoint and payload.
        function postForm($form, url, extra) {
            var data = $form.serializeArray();

            // serializeArray() omits UNCHECKED checkboxes entirely, so "off" would be
            // indistinguishable from "not on the form" and a switch could never be
            // turned back off. Every checkbox is sent explicitly.
            $form.find('input[type=checkbox]').each(function () {
                data = data.filter(function (field) { return field.name !== this.name; }, this);
                data.push({ name: this.name, value: this.checked ? '1' : '0' });
            });

            $.each(extra || {}, function (k, v) { data.push({ name: k, value: v }); });
            data.push({ name: csrfName, value: csrfHash });

            return $.ajax({ type: 'POST', url: url, data: $.param(data), dataType: 'json' })
                .done(function (res) {
                    csrfName = res.csrfName || csrfName;
                    csrfHash = res.csrfHash || csrfHash;
                    if (res.error) {
                        iziToast.error({ message: res.message });
                    } else {
                        iziToast.success({ message: res.message });
                    }
                })
                .fail(function () {
                    iziToast.error({ message: 'Could not save. Please try again.' });
                });
        }

        $('.referral-program-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            postForm($form, '<?= base_url('admin/referral/save_program') ?>', { id: $form.data('id') })
                .done(function (res) {
                    // The LIVE/Off badge is the whole point of this screen, so it is
                    // repainted from what the server actually saved rather than from
                    // the checkbox that was clicked.
                    if (!res.error) { location.reload(); }
                });
        });

        $('.referral-milestone-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            postForm($form, '<?= base_url('admin/referral/save_milestone') ?>', { id: $form.data('id') });
        });

        $('#referral-policy-form').on('submit', function (e) {
            e.preventDefault();
            postForm($(this), '<?= base_url('admin/referral/save_policy') ?>', {});
        });
    });
</script>
