<?php
/**
 * Seller > Price Calculator.
 *
 * Inputs left, the answer right. Every rate on this screen is resolved by the backend from
 * the seller's real plan, their real GST band and their own PAN/GSTIN - the page itself does
 * no arithmetic beyond formatting, deliberately. A JavaScript copy of the deduction ladder
 * would give faster feedback and would drift from the settlement engine within a sprint,
 * which is the one thing this feature exists to prevent.
 *
 * Two fields the brief asked for are NOT here, because the data behind them does not exist:
 *
 *   - HSN code / category as the GST source. `products`.`hsn_code` is free text that drives
 *     nothing and `categories` has no tax column, so the field would be guessing. The GST band
 *     dropdown reads `taxes`, which is where the platform's rates actually live.
 *   - An "your average shipping is X" default. Freight is only recorded when Shiprocket
 *     assigns an AWB, and where that has not been happening there is nothing to average. The
 *     hint appears only when the seller genuinely has recovered freight on record.
 */
$currency = isset($currency) && $currency !== '' ? $currency : '₹';
$plan_id_now = !empty($current_plan['id']) ? (int) $current_plan['id'] : 0;

/* Default the GST band to the standard rate if it is present, so the page opens on the band
 * most of the catalogue actually uses rather than on "GST Zero". */
$default_tax_id = 0;
foreach ($tax_bands as $band) {
    if ((float) $band['percentage'] == 18) {
        $default_tax_id = (int) $band['id'];
        break;
    }
}
if (!$default_tax_id && !empty($tax_bands)) {
    $default_tax_id = (int) $tax_bands[0]['id'];
}
?>
<link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/price-calculator.css') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/css/cretzo/price-calculator.css') ?: time() ?>">

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Price Calculator</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Price Calculator</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="czpc" id="czpc">

                <div class="czpc-head">
                    <h1>What should I charge?</h1>
                    <p>Enter what a product costs you and the margin you want. This uses the same fee rules
                        that settle your orders, so what you see here is what you will be paid.</p>
                </div>

                <?php if (empty($tax_bands)) { ?>
                    <div class="czpc-alert czpc-alert-error">
                        No GST rates have been set up on the platform yet, so nothing can be priced.
                        Ask an administrator to add them under Taxes.
                    </div>
                <?php } else { ?>

                <div class="czpc-grid">

                    <!-- ============================= INPUTS ============================= -->
                    <form class="czpc-card" id="czpc-form" autocomplete="off">
                        <p class="czpc-legend">Your costs</p>

                        <div class="czpc-field">
                            <label class="czpc-label" for="czpc-tax">GST rate on these goods</label>
                            <select class="czpc-select" id="czpc-tax" name="tax_id">
                                <?php foreach ($tax_bands as $band) { ?>
                                    <option value="<?= (int) $band['id'] ?>" <?= ((int) $band['id'] === $default_tax_id) ? 'selected' : '' ?>>
                                        <?= html_escape($band['title']) ?> &mdash; <?= html_escape(rtrim(rtrim(number_format((float) $band['percentage'], 2, '.', ''), '0'), '.')) ?>%
                                    </option>
                                <?php } ?>
                            </select>
                            <p class="czpc-hint">The same band you pick on the product form. Not sure which one
                                applies? Check the HSN code for your goods with your accountant &mdash; the rate
                                follows the goods, not the shop.</p>
                        </div>

                        <div class="czpc-field">
                            <label class="czpc-label" for="czpc-plan">Subscription plan</label>
                            <select class="czpc-select" id="czpc-plan" name="plan_id">
                                <?php foreach ($plans as $plan) { ?>
                                    <option value="<?= (int) $plan['id'] ?>" <?= ((int) $plan['id'] === $plan_id_now) ? 'selected' : '' ?>>
                                        <?= html_escape($plan['name']) ?>
                                        <?= ($plan_id_now && (int) $plan['id'] === $plan_id_now) ? ' (your plan)' : '' ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <p class="czpc-hint">Switch plans here to see what a different one would earn you before you buy it.</p>
                        </div>

                        <div class="czpc-field">
                            <label class="czpc-label">Commission</label>
                            <div class="czpc-derived">
                                <span id="czpc-commission">&mdash;</span>
                                <span class="czpc-src" id="czpc-commission-src"></span>
                            </div>
                            <div class="czpc-slabs" id="czpc-slabs"></div>
                            <p class="czpc-hint" id="czpc-slab-note"></p>
                        </div>

                        <div class="czpc-field">
                            <label class="czpc-label" for="czpc-cost">What the product costs you</label>
                            <div class="czpc-adorn">
                                <span class="czpc-sign"><?= html_escape($currency) ?></span>
                                <input class="czpc-input" type="number" id="czpc-cost" name="product_cost"
                                       min="0" step="0.01" value="600" inputmode="decimal">
                            </div>
                        </div>

                        <div class="czpc-field">
                            <label class="czpc-label">Does that cost include GST?</label>
                            <div class="czpc-seg">
                                <input type="radio" name="cost_includes_gst" id="czpc-gst-yes" value="1">
                                <label for="czpc-gst-yes">Yes</label>
                                <input type="radio" name="cost_includes_gst" id="czpc-gst-no" value="0" checked>
                                <label for="czpc-gst-no">No</label>
                            </div>
                            <p class="czpc-hint" id="czpc-itc-note">If your purchase price includes GST and you are
                                GST&#8209;registered, you can claim that tax back &mdash; so we treat your real cost as
                                the amount without it.</p>
                        </div>

                        <div class="czpc-field">
                            <label class="czpc-label" for="czpc-ship">
                                What one parcel costs you to ship
                                <span class="czpc-opt">&mdash; your estimate</span>
                            </label>
                            <div class="czpc-adorn">
                                <span class="czpc-sign"><?= html_escape($currency) ?></span>
                                <input class="czpc-input" type="number" id="czpc-ship" name="shipping"
                                       min="0" step="0.01" value="<?= isset($median_shipping) && $median_shipping !== null ? html_escape($median_shipping) : '70' ?>" inputmode="decimal">
                            </div>
                            <?php if (isset($median_shipping) && $median_shipping !== null) { ?>
                                <p class="czpc-hint">Your parcels have averaged <?= html_escape($currency) ?><?= html_escape(number_format($median_shipping, 2)) ?>
                                    over the last 90 days. Courier charges are billed on the real weight and
                                    destination, so treat this as a working figure.</p>
                            <?php } else { ?>
                                <p class="czpc-hint">Buyers are not charged for delivery &mdash; the courier's bill is
                                    deducted from your settlement instead. We have no shipped parcels on record for you
                                    yet, so put in what you expect a parcel to cost.</p>
                            <?php } ?>
                        </div>

                        <div class="czpc-field">
                            <label class="czpc-label" for="czpc-margin">Margin you want to make</label>
                            <div class="czpc-adorn is-suffix">
                                <span class="czpc-sign">%</span>
                                <input class="czpc-input" type="number" id="czpc-margin" name="target_margin"
                                       min="0" max="99" step="0.5" value="20" inputmode="decimal">
                            </div>
                            <p class="czpc-hint">Your profit as a share of the selling price, after every deduction
                                and after the GST you owe on the sale.</p>
                        </div>

                        <div class="czpc-field">
                            <label class="czpc-label" for="czpc-price">
                                Already have a price in mind?
                                <span class="czpc-opt">&mdash; optional</span>
                            </label>
                            <div class="czpc-adorn">
                                <span class="czpc-sign"><?= html_escape($currency) ?></span>
                                <input class="czpc-input" type="number" id="czpc-price" name="selling_price"
                                       min="0" step="0.01" placeholder="Leave blank to get a recommendation" inputmode="decimal">
                            </div>
                            <p class="czpc-hint">Fill this in and we will work out what that price actually earns you
                                instead of suggesting one.</p>
                        </div>

                        <div class="czpc-actions">
                            <button type="button" class="czpc-btn czpc-btn-ghost" id="czpc-reset">Reset</button>
                            <button type="submit" class="czpc-btn czpc-btn-primary" id="czpc-go">Calculate Price</button>
                        </div>
                    </form>

                    <!-- ============================ SUMMARY ============================= -->
                    <div class="czpc-card is-summary">
                        <p class="czpc-legend">Price summary</p>

                        <div class="czpc-alert czpc-alert-error" id="czpc-error" hidden></div>
                        <div class="czpc-alert czpc-alert-info" id="czpc-plan-note" hidden></div>

                        <div class="czpc-summary-body" id="czpc-summary">
                            <div class="czpc-hero">
                                <small id="czpc-hero-label">Recommended selling price</small>
                                <strong id="czpc-price-out">&mdash;</strong>
                                <span id="czpc-price-note">including GST, as the buyer sees it</span>
                            </div>

                            <div class="czpc-pair">
                                <div class="czpc-stat" id="czpc-profit-tile">
                                    <small>Your profit</small>
                                    <strong id="czpc-profit">&mdash;</strong>
                                </div>
                                <div class="czpc-stat" id="czpc-margin-tile">
                                    <small>Margin</small>
                                    <strong id="czpc-margin-out">&mdash;</strong>
                                </div>
                            </div>

                            <div class="czpc-lines" id="czpc-lines"></div>

                            <p class="czpc-foot" id="czpc-foot"></p>
                        </div>
                    </div>

                </div>
                <?php } ?>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    'use strict';

    var root = document.getElementById('czpc');
    if (!root) { return; }

    var form = document.getElementById('czpc-form');
    if (!form) { return; }

    var CURRENCY = <?= json_encode($currency) ?>;
    var ENDPOINT = <?= json_encode(base_url('seller/price_calculator/calculate')) ?>;

    /* Indian grouping - 1,17,049.00, not 117,049.00. */
    var inrFormat = new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    function inr(value) {
        var n = Number(value) || 0;
        return (n < 0 ? '-' : '') + CURRENCY + inrFormat.format(Math.abs(n));
    }
    function pct(value) {
        var n = Number(value) || 0;
        return (Math.round(n * 100) / 100).toString().replace(/\.00$/, '') + '%';
    }

    var el = {
        error:      document.getElementById('czpc-error'),
        planNote:   document.getElementById('czpc-plan-note'),
        summary:    document.getElementById('czpc-summary'),
        heroLabel:  document.getElementById('czpc-hero-label'),
        price:      document.getElementById('czpc-price-out'),
        priceNote:  document.getElementById('czpc-price-note'),
        profit:     document.getElementById('czpc-profit'),
        profitTile: document.getElementById('czpc-profit-tile'),
        margin:     document.getElementById('czpc-margin-out'),
        marginTile: document.getElementById('czpc-margin-tile'),
        lines:      document.getElementById('czpc-lines'),
        foot:       document.getElementById('czpc-foot'),
        commission: document.getElementById('czpc-commission'),
        commSrc:    document.getElementById('czpc-commission-src'),
        slabs:      document.getElementById('czpc-slabs'),
        slabNote:   document.getElementById('czpc-slab-note'),
        itcNote:    document.getElementById('czpc-itc-note'),
        go:         document.getElementById('czpc-go')
    };

    function line(label, value, options) {
        options = options || {};
        var row = document.createElement('div');
        row.className = 'czpc-line' + (options.minus ? ' is-minus' : '') + (options.total ? ' is-total' : '');

        var left = document.createElement('span');
        left.appendChild(document.createTextNode(label));
        if (options.note) {
            var note = document.createElement('span');
            note.className = 'czpc-note';
            note.textContent = options.note;
            left.appendChild(note);
        }

        var right = document.createElement('b');
        right.textContent = (options.minus ? '-' : '') + inr(Math.abs(value));

        row.appendChild(left);
        row.appendChild(right);
        el.lines.appendChild(row);
    }

    function renderSlabs(rates) {
        el.slabs.innerHTML = '';
        var plan = rates.plan || {};
        var slabs = [
            { key: 'first50',  label: 'Orders 1-50',  value: plan.commission_first50 },
            { key: '51_100',   label: 'Orders 51-100', value: plan.commission_51_100 },
            { key: 'after100', label: 'Orders 101+',   value: plan.commission_after100 }
        ];
        slabs.forEach(function (slab) {
            var box = document.createElement('div');
            box.className = 'czpc-slab' + (rates.slab === slab.key ? ' is-here' : '');
            var small = document.createElement('small');
            small.textContent = slab.label;
            var b = document.createElement('b');
            b.textContent = (slab.value === null || slab.value === '' || typeof slab.value === 'undefined')
                ? 'not set' : pct(slab.value);
            box.appendChild(small);
            box.appendChild(b);
            el.slabs.appendChild(box);
        });

        el.slabNote.textContent = 'You have completed ' + rates.orders_done +
            (rates.orders_done === 1 ? ' order' : ' orders') +
            ', so this is priced at the rate for your next one.';
    }

    function render(data) {
        var r = data.rates, b = data.breakdown, e = data.earnings;

        el.commission.textContent = pct(r.commission_percent);
        if (r.commission_source !== 'plan_slab') {
            el.commSrc.textContent = 'PLATFORM DEFAULT';
        } else {
            el.commSrc.textContent = r.is_current_plan ? 'FROM YOUR PLAN' : 'ON ' + String(r.plan.name || '').toUpperCase();
        }
        renderSlabs(r);

        /* A seller comparing plans is looking at numbers they cannot get today. Saying so is
         * the difference between a comparison and a false promise. */
        if (r.plan && !r.is_current_plan) {
            el.planNote.hidden = false;
            el.planNote.textContent = 'This is what the ' + (r.plan.name || 'selected') +
                ' plan would earn you. You are not on it — these figures apply once you upgrade.';
        } else {
            el.planNote.hidden = true;
        }

        var asked = form.selling_price.value !== '' && Number(form.selling_price.value) > 0;
        el.heroLabel.textContent = asked ? 'Your selling price' : 'Recommended selling price';
        el.price.textContent = inr(data.price);
        el.priceNote.textContent = 'including ' + pct(r.gst_percent) + ' GST, as the buyer sees it';

        el.profit.textContent = inr(e.profit);
        el.margin.textContent = pct(e.margin_percent);
        el.profitTile.classList.toggle('is-loss', e.profit < 0);
        el.marginTile.classList.toggle('is-loss', e.profit < 0);

        el.lines.innerHTML = '';
        line('Selling price', b.order_amount);
        line('GST on the sale (' + pct(r.gst_percent) + ')', b.product_tax_amount, {
            note: 'collected from the buyer, remitted by you'
        });
        line('Commission (' + pct(b.commission_percent) + ')', b.commission_amount, { minus: true });
        line('GST on commission (' + pct(r.commission_gst) + ')', b.commission_gst_amount, { minus: true });

        if (Number(b.tcs_amount) > 0) {
            line('TCS (' + pct(b.tcs_percent) + ')', b.tcs_amount, { minus: true, note: 'GST section 52' });
        }
        if (Number(b.tds_amount) > 0) {
            line('TDS (' + pct(b.tds_percent) + ')', b.tds_amount, { minus: true, note: 'income tax section 194-O' });
        }
        line('Shipping', b.shipping_deduction, { minus: true, note: 'the courier’s bill, deducted at settlement' });

        /* Only shown when a gateway fee is actually configured. A zero line here while the
         * settlement engine deducts nothing would just be noise. */
        if (Number(b.gateway_fee) > 0) {
            line('Payment gateway fee', b.gateway_fee, { minus: true });
        }

        line('Credited to your wallet', b.net_payable, { total: true });
        line('GST you remit', b.product_tax_amount, { minus: true });
        line('Cost of the product', data.cost.effective, {
            minus: true,
            note: data.cost.itc_applied ? ('after claiming back ' + inr(data.cost.input_tax_credit) + ' GST') : null
        });
        line('Your profit', e.profit, { total: true });

        var notes = [];
        if (Number(b.tcs_amount) === 0 && Number(b.tds_amount) === 0) {
            notes.push('No TCS or TDS applies to you at the moment.');
        } else if (b.tds_basis === 'threshold_exempt') {
            notes.push('No TDS while your sales this year stay under the section 194-O threshold.');
        }
        if (!r.gst_registered) {
            notes.push('You are not recorded as GST-registered, so no TCS is collected and you cannot claim GST back on purchases.');
        }
        notes.push('Shipping is your own estimate — your settlement uses whatever the courier actually bills.');
        notes.push('Rates are today’s. A plan change or a new order slab will move them.');
        el.foot.textContent = notes.join(' ');

        el.error.hidden = true;
    }

    function fail(message) {
        el.planNote.hidden = true;
        el.error.hidden = false;
        el.error.textContent = message || 'Something went wrong working that out. Try again.';
    }

    function calculate() {
        var body = new FormData(form);
        if (window.csrfName && window.csrfHash) {
            body.append(window.csrfName, window.csrfHash);
        }

        root.classList.add('is-busy');
        el.go.disabled = true;
        el.go.textContent = 'Calculating…';

        fetch(ENDPOINT, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (response) { return response.text(); })
            .then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    fail('The calculator did not answer. Reload the page and try again.');
                    return;
                }
                if (data.csrfName && data.csrfHash) {
                    window.csrfName = data.csrfName;
                    window.csrfHash = data.csrfHash;
                }
                if (data.error) {
                    fail(String(data.message).replace(/<[^>]*>/g, '').trim());
                    return;
                }
                render(data);
            })
            .catch(function () {
                fail('Could not reach the calculator. Check your connection and try again.');
            })
            .then(function () {
                root.classList.remove('is-busy');
                el.go.disabled = false;
                el.go.textContent = 'Calculate Price';
            });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        calculate();
    });

    /* Changing the plan or the GST band changes the rates, so recalculate straight away -
     * comparing plans is the reason the dropdown is there and making it a two-step action
     * would hide the comparison. */
    document.getElementById('czpc-plan').addEventListener('change', calculate);
    document.getElementById('czpc-tax').addEventListener('change', calculate);

    document.getElementById('czpc-reset').addEventListener('click', function () {
        form.reset();
        calculate();
    });

    /* Open on a worked example rather than an empty form: the statement below is how a seller
     * learns what comes off a sale, and a blank panel teaches nothing. */
    calculate();
}());
</script>
