<?php
/**
 * My Account > Order details (one order item).
 *
 * Rebuilt on the shared account shell. Beyond the styling, this rewrite fixes
 * two real defects in the page it replaces:
 *
 *  1. WRONG PRODUCT SHOWN. The old view found the requested item and stored it
 *     in $order_item, then rendered the product name, short description,
 *     attributes and quantity from `$item` - the leftover variable from the
 *     `foreach ($order['order_items'] as $item)` loop used to FIND it. On a
 *     multi-item order that loop's last value is whichever item happened to be
 *     last in the array, so the page showed one item's photo, status and price
 *     next to a different item's name, description and quantity.
 *
 *  2. A 500-page in place of a redirect. When the id in the URL matched no item
 *     the view `echo`'d "Something broke :(" and `die`'d mid-document, leaving a
 *     half-rendered page with no header or footer. It redirects to the orders
 *     list now, which is what the controller already does for an unknown order.
 *
 * It also drops ~200 lines of `display:none` dead markup that duplicated the
 * whole page inside a hidden <div> - a second, older layout that was never
 * shown but was still executed, still queried settings, and still contained the
 * `$item['cancelable_till']` reads that fataled when $item was unset.
 */

$currency = isset($settings['currency']) ? $settings['currency'] : '';

/*
 * Which item of the order is being viewed. The id is the LAST url segment:
 * my-account/order-details/{order_id}/{order_item_id}. Read from the CI uri
 * object rather than by exploding $_SERVER['REQUEST_URI'], which included the
 * query string and so failed on any url with one.
 */
$requested_item_id = (int) $this->uri->segment(4);

$order_item = null;
foreach ($order['order_items'] as $candidate) {
    if ((int) $candidate['id'] === $requested_item_id) {
        $order_item = $candidate;
        break;
    }
}

/* An unknown or missing item id means a hand-edited url or a deleted item.
 * order_details() already redirects for an unknown ORDER; do the same here
 * rather than rendering a broken page. */
if ($order_item === null) {
    redirect(base_url('my-account/orders'), 'refresh');
    return;
}

$image = (!empty($order_item['variant_image'])) ? $order_item['variant_image'] : $order_item['image'];
$tone  = order_status_tone($order_item['active_status']);
$label = order_status_label($order_item['active_status']);

$history = isset($order_item['status']) && is_array($order_item['status']) ? $order_item['status'] : [];
$last_update = !empty($history) ? orderStatusTimeToHumanReadableString($history[array_key_last($history)][1]) : '';

/* Product attributes ("Size: M, Colour: Blue") arrive as two parallel
 * comma-joined strings. array_combine() fatals when the two do not have the
 * same number of parts, so the counts are checked first. */
$attributes = [];
if (!empty($order_item['attr_name'])) {
    $names = explode(', ', $order_item['attr_name']);
    $values = explode(', ', $order_item['variant_values']);
    if (count($names) === count($values)) {
        $attributes = array_combine($names, $values);
    }
}

/* ---- return window ----
 * order_status_history_date() returns '' until the item has actually been
 * DELIVERED. (The array_search() it replaced searched a list of
 * [status, timestamp] PAIRS for the bare string 'delivered', which can never
 * match, so this block could not render for any order at all.) */
$return_days = isset($settings['max_product_return_days']) ? (int) $settings['max_product_return_days'] : 0;
$delivered_date = order_status_history_date($history, 'delivered');
$return_till = ($delivered_date !== '')
    ? date('Y-m-d', strtotime($delivered_date . ' + ' . $return_days . ' days'))
    : '';

/* ---- can this item still be cancelled? ---- */
$ladder = ['awaiting', 'received', 'processed', 'shipped', 'delivered', 'cancelled', 'returned'];
$cancel_index = array_search($order_item['cancelable_till'], $ladder, true);
$active_index = array_search($order_item['active_status'], $ladder, true);
$can_cancel = !$order_item['is_already_cancelled']
    && $order_item['is_cancelable']
    && $cancel_index !== false
    && $active_index !== false
    && $active_index <= $cancel_index
    && $order_item['type'] !== 'digital_product';

$can_return = ($order_item['is_returnable']
    && !$order_item['is_already_returned']
    && $order_item['type'] !== 'digital_product'
    && $return_till !== ''
    && date('Y-m-d') < $return_till);

/* ---- money ----
 * `main_price` is what the product lists at, `special_price` what it sold for. */
$main_price = is_numeric($order_item['main_price'] ?? null) ? (float) $order_item['main_price'] : 0;
$special_price = is_numeric($order_item['special_price'] ?? null) ? (float) $order_item['special_price'] : 0;
$saved = ($main_price > $special_price && $special_price > 0)
    ? ($main_price - $special_price) * (int) $order_item['quantity']
    : 0;

$is_digital = ($order_item['type'] === 'digital_product');
$can_download = ($is_digital
    && $order_item['download_allowed'] == 1
    && in_array($order_item['active_status'], ['received', 'delivered'], true));

/* ---- delivery timeline ----
 * The recorded history, then the steps still to come. A cancelled or returned
 * item has no "still to come": the ladder stopped there. */
$done_statuses = array_map(function ($row) {
    return $row[0];
}, $history);
$is_stopped = (bool) array_intersect($done_statuses, ['cancelled', 'returned', 'return_request_pending']);
$upcoming = $is_stopped || $is_digital
    ? []
    : array_values(array_diff(['received', 'processed', 'shipped', 'delivered'], $done_statuses));

/* --------------------------------------------------------------- actions -- */
ob_start(); ?>
<a class="czap-btn czap-btn--ghost czap-btn--sm" href="<?= base_url('my-account/orders') ?>">
    <i class="uil uil-arrow-left"></i> All orders
</a>
<a class="czap-btn czap-btn--ghost czap-btn--sm" target="_blank" rel="noopener"
   href="<?= base_url('my-account/order-invoice/' . $order['id']) ?>">
    <i class="uil uil-file-download-alt"></i> Invoice
</a>
<?php $page_actions = ob_get_clean();

/* --------------------------------------------------------------- content -- */
ob_start(); ?>

<section class="czap-card">
    <div class="czap-card__head">
        <div class="czap-card__titles">
            <h2 class="czap-card__title"><i class="uil uil-box"></i> Order #<?= (int) $order['id'] ?></h2>
            <p class="czap-card__sub">
                Placed <?= html_escape(date('d M Y', strtotime($order['date_added']))) ?>
                &middot; <?= html_escape($order['payment_method']) ?>
            </p>
        </div>
        <div class="czap-card__actions"><?= $page_actions ?></div>
    </div>

    <div class="czap-card__body">

        <!-- ---------------------------- the item ---------------------------- -->
        <div class="czap-media" style="margin-bottom:20px">
            <?php /* Every field below reads from $order_item. The old view read the
                     name, description, attributes and quantity from `$item`, the
                     leftover loop variable - see the note at the top of this file. */ ?>
            <a href="<?= base_url('products/details/' . $order_item['slug']) ?>" style="flex:none">
                <img class="czap-media__img" style="width:120px;height:120px" src="<?= $image ?>"
                     alt="<?= html_escape($order_item['product_name']) ?>">
            </a>
            <div class="czap-media__body">
                <h3 class="czap-media__title" style="font-size:19px">
                    <a href="<?= base_url('products/details/' . $order_item['slug']) ?>">
                        <?= html_escape($order_item['product_name']) ?>
                    </a>
                </h3>

                <?php if (!empty($order_item['short_description'])) { ?>
                    <p class="czap-media__text"><?= html_escape(strip_tags($order_item['short_description'])) ?></p>
                <?php } ?>

                <?php if (!empty($attributes)) { ?>
                    <ul class="czap-attrs">
                        <?php foreach ($attributes as $name => $value) { ?>
                            <li><?= html_escape($name) ?>: <b><?= html_escape($value) ?></b></li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <p style="margin:0 0 8px">
                    <span class="czap-money" style="font-size:19px"><?= html_escape($currency) ?><?= number_format($order_item['sub_total'], 2) ?></span>
                    <span class="czap-muted">
                        &nbsp;&middot;&nbsp;<?= !empty($this->lang->line('quantity')) ? $this->lang->line('quantity') : 'Qty' ?> <?= (int) $order_item['quantity'] ?>
                    </span>
                </p>

                <?php if ($saved > 0) { ?>
                    <p class="czap-help">
                        <i class="uil uil-tag-alt"></i>
                        You saved <span class="czap-save"><?= html_escape($currency) ?><?= number_format($saved, 2) ?></span> on this item.
                    </p>
                <?php } ?>

                <p style="margin:10px 0 0">
                    <span class="czap-muted">Sold by</span>
                    <strong style="color:var(--czap-orange-dark)"><?= html_escape($order_item['store_name']) ?></strong>
                </p>
            </div>
        </div>

        <!-- --------------------------- status strip --------------------------- -->
        <div class="czap-panel czap-panel--soft" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
            <img src="<?= order_status_icon_url($order_item['active_status']) ?>" alt="" height="34" style="flex:none">
            <div style="min-width:0">
                <p style="margin:0;font-size:16px;font-weight:700"><?= html_escape($label) ?></p>
                <?php if ($last_update !== '') { ?>
                    <p class="czap-help" style="margin:2px 0 0">Last updated <?= html_escape($last_update) ?></p>
                <?php } ?>
            </div>
            <span class="czap-badge czap-badge--<?= $tone ?>" style="margin-left:auto"><?= html_escape($label) ?></span>
            <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm" data-czap-open="#czap-track-modal">
                <i class="uil uil-map-marker-info"></i> Track
            </button>
        </div>

        <?php if ($can_download) { ?>
            <div class="czap-alert czap-alert--ok" style="margin:18px 0 0">
                <i class="uil uil-download-alt"></i>
                <span>
                    This is a digital product and it is ready.
                    <a href="<?= base_url('products/download_link_hash/' . $order_item['id']) ?>">Download it here</a>.
                </span>
            </div>
        <?php } elseif ($is_digital && $order_item['download_allowed'] == 0) { ?>
            <div class="czap-alert czap-alert--info" style="margin:18px 0 0">
                <i class="uil uil-envelope"></i>
                <span>This is a digital product - the seller will email it to you directly.</span>
            </div>
        <?php } ?>

        <?php if ($can_cancel || $can_return) { ?>
            <div class="czap-item__foot" style="padding:18px 0 0">
                <?php if ($can_cancel) { ?>
                    <button type="button" class="czap-btn czap-btn--danger czap-order-action"
                            data-status="cancelled"
                            data-order-id="<?= (int) $order['id'] ?>"
                            data-product="<?= html_escape($order_item['product_name']) ?>">
                        <i class="uil uil-times-circle"></i> Cancel this order
                    </button>
                <?php } ?>
                <?php if ($can_return) { ?>
                    <button type="button" class="czap-btn czap-btn--ghost czap-order-action"
                            data-status="returned"
                            data-order-id="<?= (int) $order['id'] ?>"
                            data-product="<?= html_escape($order_item['product_name']) ?>">
                        <i class="uil uil-history-alt"></i> Return this order
                    </button>
                <?php } ?>
            </div>
        <?php } ?>

        <hr class="czap-hr">

        <!-- ---------------------------- return window ---------------------------- -->
        <?php
        if (!$order_item['is_returnable'] || $is_digital) {
            $return_note = ['info', 'uil-info-circle', 'Returns are not available for this product.'];
        } elseif (in_array($order_item['active_status'], ['cancelled', 'returned'], true)) {
            $return_note = ['warn', 'uil-info-circle', 'This order has been ' . $order_item['active_status'] . '.'];
        } elseif ($return_till !== '') {
            $return_note = (date('Y-m-d') < $return_till)
                ? ['ok', 'uil-shield-check', 'You can return this item until ' . date('d M Y', strtotime($return_till)) . '.']
                : ['info', 'uil-info-circle', 'The return window closed on ' . date('d M Y', strtotime($return_till)) . '.'];
        } else {
            $return_note = ['info', 'uil-shield-check', 'Returns will be open for ' . $return_days . ' day' . ($return_days === 1 ? '' : 's') . ' from the day this is delivered.'];
        }
        ?>
        <div class="czap-alert czap-alert--<?= $return_note[0] ?>" style="margin-bottom:0">
            <i class="uil <?= $return_note[1] ?>"></i>
            <span><?= html_escape($return_note[2]) ?></span>
        </div>
    </div>
</section>

<!-- ======================= delivery / courier / totals ======================= -->
<section class="czap-card">
    <div class="czap-card__head">
        <div class="czap-card__titles">
            <h2 class="czap-card__title"><i class="uil uil-truck"></i> Delivery &amp; payment</h2>
        </div>
    </div>
    <div class="czap-card__body">
        <div class="czap-cols">

            <div class="czap-panel">
                <p class="czap-panel__title"><i class="uil uil-map-marker"></i> Delivery address</p>
                <p class="czap-addr__lines">
                    <strong><?= html_escape($order['order_recipient_person']) ?></strong><br>
                    <?= html_escape($order['address']) ?><br>
                    <span class="czap-muted">Mobile:</span> <?= html_escape($order['mobile']) ?>
                </p>
            </div>

            <div class="czap-panel">
                <p class="czap-panel__title"><i class="uil uil-shipping-fast"></i> Courier</p>
                <?php if (!empty($order_item['courier_agency'])) { ?>
                    <div class="czap-dl">
                        <div class="czap-dl__row">
                            <span><?= !empty($this->lang->line('courier_agency')) ? $this->lang->line('courier_agency') : 'Agency' ?></span>
                            <span>
                                <?php if (!empty($order_item['url'])) { ?>
                                    <a href="<?= html_escape($order_item['url']) ?>" target="_blank" rel="noopener"
                                       title="Trace this order with the courier"><?= html_escape($order_item['courier_agency']) ?></a>
                                <?php } else { ?>
                                    <?= html_escape($order_item['courier_agency']) ?>
                                <?php } ?>
                            </span>
                        </div>
                        <?php if (!empty($order_item['tracking_id'])) { ?>
                            <div class="czap-dl__row">
                                <span><?= !empty($this->lang->line('tracking_id')) ? $this->lang->line('tracking_id') : 'Tracking ID' ?></span>
                                <span>
                                    <?= html_escape($order_item['tracking_id']) ?>
                                    <?php /* A tracking id is only useful pasted into the courier's own
                                             site, so give the customer a way to copy it - it used to be
                                             plain text with a tooltip that said "Copy this Tracking ID". */ ?>
                                    <button type="button" class="czap-btn czap-btn--quiet czap-btn--sm"
                                            data-czap-copy="<?= html_escape($order_item['tracking_id']) ?>"
                                            title="Copy tracking ID" style="min-height:26px;padding:0 8px">
                                        <i class="uil uil-copy"></i>
                                    </button>
                                </span>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <p class="czap-help" style="margin:0">
                        Tracking appears here once your order has been shipped.
                    </p>
                <?php } ?>
            </div>

            <div class="czap-panel">
                <p class="czap-panel__title"><i class="uil uil-bell"></i> Updates sent to</p>
                <p class="czap-addr__lines">
                    <?php if (!empty($order['mobile'])) { ?>
                        <i class="uil uil-phone" style="color:var(--czap-orange)"></i> +91 <?= html_escape($order['mobile']) ?><br>
                    <?php } ?>
                    <?php if (!empty($order['email'])) { ?>
                        <i class="uil uil-envelope-alt" style="color:var(--czap-orange)"></i> <?= html_escape($order['email']) ?>
                    <?php } ?>
                </p>
            </div>

            <div class="czap-panel">
                <p class="czap-panel__title"><i class="uil uil-receipt"></i> Order total</p>
                <div class="czap-dl">
                    <div class="czap-dl__row">
                        <span><?= !empty($this->lang->line('total_order_price')) ? $this->lang->line('total_order_price') : 'Items' ?></span>
                        <span><?= html_escape($currency) ?><?= number_format($order['total'], 2) ?></span>
                    </div>
                    <?php if (!$is_digital) { ?>
                        <?php /* Read off the ORDER, not off the current setting: an order placed
                                 while customers were still charged freight must keep showing what
                                 it charged. Only a genuinely uncharged order is labelled FREE. */ ?>
                        <div class="czap-dl__row <?= round((float) $order['delivery_charge'], 2) == 0 ? 'czap-dl__row--free' : '' ?>">
                            <span><?= !empty($this->lang->line('delivery_charge')) ? $this->lang->line('delivery_charge') : 'Delivery' ?></span>
                            <span><?= round((float) $order['delivery_charge'], 2) == 0
                                ? 'FREE'
                                : html_escape($currency) . number_format($order['delivery_charge'], 2) ?></span>
                        </div>
                    <?php } ?>
                    <?php if (!empty($order['promo_code']) && !empty($order['promo_discount'])) { ?>
                        <div class="czap-dl__row czap-dl__row--free">
                            <span><?= !empty($this->lang->line('promocode_discount')) ? $this->lang->line('promocode_discount') : 'Promo' ?> (<?= html_escape($order['promo_code']) ?>)</span>
                            <span>- <?= html_escape($currency) ?><?= number_format($order['promo_discount'], 2) ?></span>
                        </div>
                    <?php } ?>
                    <?php if (round((float) $order['wallet_balance'], 2) > 0) { ?>
                        <div class="czap-dl__row czap-dl__row--free">
                            <span><?= !empty($this->lang->line('wallet_used')) ? $this->lang->line('wallet_used') : 'Wallet used' ?></span>
                            <span>- <?= html_escape($currency) ?><?= number_format($order['wallet_balance'], 2) ?></span>
                        </div>
                    <?php } ?>
                    <div class="czap-dl__row czap-dl__row--total">
                        <span><?= !empty($this->lang->line('final_total')) ? $this->lang->line('final_total') : 'Paid' ?></span>
                        <span><?= html_escape($currency) ?><?= number_format($order['final_total'], 2) ?></span>
                    </div>
                </div>
                <p class="czap-help">
                    <?= !empty($this->lang->line('via')) ? $this->lang->line('via') : 'via' ?>
                    <?= html_escape($order['payment_method']) ?>
                </p>
            </div>
        </div>
    </div>
</section>

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Order #' . (int) $order['id'],
    'page_content' => $page_content,
    'page_card'    => false,
]);
?>

<!-- ==================== POPUP: full delivery timeline ==================== -->
<div class="czap-modal" id="czap-track-modal" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="czap-track-modal-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">
        <div class="czap-modal__head">
            <div>
                <h2 class="czap-modal__title" id="czap-track-modal-title">
                    <i class="uil uil-map-marker-info"></i> Delivery timeline
                </h2>
                <p class="czap-modal__sub">Order #<?= (int) $order['id'] ?> &middot; <?= html_escape($order_item['product_name']) ?></p>
            </div>
            <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
        </div>
        <div class="czap-modal__body">
            <?php if (empty($history)) { ?>
                <p class="czap-help" style="text-align:center;margin:0">
                    No status updates have been recorded for this item yet.
                </p>
            <?php } else { ?>
                <ol class="czap-track">
                    <?php $last = array_key_last($history);
                    foreach ($history as $key => $step) {
                        $name = $step[0];
                        $step_class = 'is-done';
                        if (in_array($name, ['cancelled', 'returned', 'return_request_decline'], true)) {
                            $step_class = 'is-bad';
                        } elseif ($key === $last) {
                            $step_class = 'is-now';
                        }
                        ?>
                        <li class="czap-track__step <?= $step_class ?>">
                            <p class="czap-track__name"><?= html_escape(order_status_label($name)) ?></p>
                            <p class="czap-track__when"><?= html_escape(orderStatusTimeToHumanReadableString($step[1])) ?></p>
                        </li>
                    <?php } ?>

                    <?php /* The steps still to come, greyed out - so the customer can see how
                             many stages are left, not just where the item is now. */ ?>
                    <?php foreach ($upcoming as $step) { ?>
                        <li class="czap-track__step is-todo">
                            <p class="czap-track__name"><?= html_escape(order_status_label($step)) ?></p>
                            <p class="czap-track__when">Pending</p>
                        </li>
                    <?php } ?>
                </ol>
            <?php } ?>

            <?php if (!empty($order_item['courier_agency']) && !empty($order_item['url'])) { ?>
                <div class="czap-alert czap-alert--info" style="margin:18px 0 0">
                    <i class="uil uil-shipping-fast"></i>
                    <span>
                        For live courier scans, track it with
                        <a href="<?= html_escape($order_item['url']) ?>" target="_blank" rel="noopener"><?= html_escape($order_item['courier_agency']) ?></a>.
                    </span>
                </div>
            <?php } ?>
        </div>
        <div class="czap-modal__foot">
            <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Close</button>
            <a class="czap-btn czap-btn--ghost" target="_blank" rel="noopener"
               href="<?= base_url('my-account/order-invoice/' . $order['id']) ?>">
                <i class="uil uil-file-download-alt"></i> Get invoice
            </a>
        </div>
    </div>
</div>

<?php /* The cancel/return popup is the orders list's, reused verbatim so the two
         screens cannot offer the same action with different wording. Its
         behaviour lives in js/cretzo/orders.js, which this page also needs -
         include-script.php only auto-loads the script named after $main_page
         ('order-details'), so it is pulled in explicitly here. */ ?>
<div class="czap-modal" id="czap-order-modal" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="czap-order-modal-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">
        <div class="czap-modal__head">
            <div>
                <h2 class="czap-modal__title" id="czap-order-modal-title">
                    <i class="uil uil-exclamation-circle"></i> <span id="czap-order-heading">Cancel order</span>
                </h2>
                <p class="czap-modal__sub" id="czap-order-sub"></p>
            </div>
            <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
        </div>

        <div class="czap-modal__body">
            <div class="czap-panel czap-panel--soft" style="margin-bottom:18px">
                <p class="czap-panel__title" style="margin:0"><i class="uil uil-box"></i> <span id="czap-order-product"></span></p>
                <p class="czap-help" style="margin-top:6px">Order #<span id="czap-order-number"></span></p>
            </div>

            <div id="czap-order-reason-wrap" hidden>
                <label class="czap-field__label" for="czap-order-reason">Why are you returning this?<span class="czap-req">*</span></label>
                <select class="czap-select" id="czap-order-reason">
                    <option value="">Select a reason</option>
                    <option>Wrong size or fit</option>
                    <option>Item damaged or defective</option>
                    <option>Not what I expected</option>
                    <option>Received the wrong item</option>
                    <option>Changed my mind</option>
                </select>
                <p class="czap-help">
                    This helps us send the right pickup and sort the refund faster. Anything else we
                    should know? <a href="<?= base_url('my-account/support') ?>">Raise a support ticket</a>.
                </p>
            </div>

            <div class="czap-alert czap-alert--warn" style="margin:18px 0 0">
                <i class="uil uil-exclamation-triangle"></i>
                <span id="czap-order-warning"></span>
            </div>

            <div id="czap-order-msg" style="display:none;margin:16px 0 0"></div>
        </div>

        <div class="czap-modal__foot">
            <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Keep my order</button>
            <button type="button" class="czap-btn czap-btn--solid-danger" id="czap-order-confirm">
                <i class="uil uil-check"></i> <span>Confirm</span>
            </button>
        </div>
    </div>
</div>

<script src="<?= add_ver(THEME_ASSETS_URL . 'js/' . THEME . '/orders.js') ?>"></script>
