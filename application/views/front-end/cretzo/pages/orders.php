<?php
/**
 * My Account > Orders & returns.
 *
 * Rebuilt on the shared account shell. Two things changed beyond the styling:
 *
 *  - Cancel and Return no longer fire on a bare window.confirm(). They open a
 *    popup that names the item, states the consequence, and (for a return) asks
 *    why - so the customer can see what they are about to do to which item.
 *  - A real status filter, applied in SQL by the controller, so the tab counts,
 *    the pager and the rows can never disagree.
 */

$currency = isset($settings['currency']) ? $settings['currency'] : '';
$search_value = isset($_GET['search']) ? (string) $_GET['search'] : '';
$status_key = isset($status_key) ? $status_key : '';
$order_rows = (isset($orders['order_data']) && is_array($orders['order_data'])) ? $orders['order_data'] : [];
$total_orders = isset($total_orders) ? (int) $total_orders : 0;

/* max_product_return_days is read once - the old view called
 * get_settings('system_settings') inside the loop, once per order item, and
 * reassigned $settings while doing it (which is also what left $settings
 * pointing at system_settings rather than the view's own copy). */
$return_days = isset($settings['max_product_return_days']) ? (int) $settings['max_product_return_days'] : 0;

$status_tabs = [
    ''          => ['label' => 'All', 'icon' => 'uil-list-ul'],
    'active'    => ['label' => 'In progress', 'icon' => 'uil-truck'],
    'delivered' => ['label' => 'Delivered', 'icon' => 'uil-check-circle'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => 'uil-times-circle'],
    'returned'  => ['label' => 'Returns', 'icon' => 'uil-history-alt'],
];

/* Preserves the search term when a status tab is clicked, and vice versa. */
function czap_orders_url($status, $search)
{
    $query = [];
    if ($status !== '') {
        $query['status'] = $status;
    }
    if ($search !== '') {
        $query['search'] = $search;
    }
    return base_url('my-account/orders') . (empty($query) ? '' : '?' . http_build_query($query));
}

/* --------------------------------------------------------------- toolbar -- */
ob_start(); ?>

<div class="czap-toolbar">
    <div class="czap-search">
        <i class="uil uil-search"></i>
        <input class="czap-input" type="search" id="czap-order-search"
               placeholder="Search by product, order id or status..."
               value="<?= html_escape($search_value) ?>"
               aria-label="Search your orders">
        <button type="button" class="czap-search__clear" id="czap-order-search-clear"
                data-czap-clear-reload aria-label="Clear search">&times;</button>
    </div>
    <button type="button" class="czap-btn czap-btn--primary" id="czap-order-search-btn">
        <i class="uil uil-search"></i> Search
    </button>
</div>

<div class="czap-radios" style="margin-bottom:20px" role="tablist" aria-label="Filter orders by status">
    <?php foreach ($status_tabs as $key => $tab) { ?>
        <a class="czap-radio <?= $status_key === $key ? 'is-checked' : '' ?>"
           href="<?= czap_orders_url($key, $search_value) ?>"
           role="tab" aria-selected="<?= $status_key === $key ? 'true' : 'false' ?>">
            <i class="uil <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
        </a>
    <?php } ?>
</div>

<?php if ($search_value !== '' || $status_key !== '') { ?>
    <p class="czap-help" style="margin:-8px 0 18px">
        <?= $total_orders ?> <?= $total_orders === 1 ? 'order' : 'orders' ?> match
        <?php if ($search_value !== '') { ?>&ldquo;<strong><?= html_escape($search_value) ?></strong>&rdquo;<?php } ?>
        <?php if ($status_key !== '') { ?>
            <?= $search_value !== '' ? ' in ' : ' the ' ?><strong><?= $status_tabs[$status_key]['label'] ?></strong> filter
        <?php } ?>
        &middot; <a href="<?= base_url('my-account/orders') ?>">clear</a>
    </p>
<?php } ?>

<?php if (empty($order_rows)) { ?>
    <div class="czap-empty">
        <div class="czap-empty__icon"><i class="uil uil-box"></i></div>
        <h3 class="czap-empty__title">
            <?php if ($search_value !== '' || $status_key !== '') { ?>
                Nothing matches that filter
            <?php } else { ?>
                <?= !empty($this->lang->line('no_orders_found')) ? $this->lang->line('no_orders_found') : 'No orders placed yet' ?>
            <?php } ?>
        </h3>
        <p class="czap-empty__text">
            <?php if ($search_value !== '' || $status_key !== '') { ?>
                Try a different search term, or look at all of your orders.
            <?php } else { ?>
                Once you place an order you will be able to track it, download its invoice and start a return from here.
            <?php } ?>
        </p>
        <?php if ($search_value !== '' || $status_key !== '') { ?>
            <a class="czap-btn czap-btn--ghost" href="<?= base_url('my-account/orders') ?>">
                <i class="uil uil-list-ul"></i> Show all orders
            </a>
        <?php } else { ?>
            <a class="czap-btn czap-btn--primary" href="<?= base_url('products') ?>">
                <i class="uil uil-shopping-cart"></i> Start shopping
            </a>
        <?php } ?>
    </div>
<?php } else { ?>

    <ul class="czap-list">
        <?php foreach ($order_rows as $row) {
            foreach ($row['order_items'] as $item) {

                $tone   = order_status_tone($item['active_status']);
                $label  = order_status_label($item['active_status']);
                $image  = (!empty($item['variant_image'])) ? $item['variant_image'] : $item['image'];
                $detail = base_url('my-account/order-details/' . $row['id'] . '/' . $item['id']);
                $when   = isset($item['status'][array_key_last($item['status'])][1])
                    ? orderStatusTimeToHumanReadableString($item['status'][array_key_last($item['status'])][1])
                    : '';

                /* ---- can this item still be cancelled? ----
                 * `cancelable_till` names the furthest step at which cancelling is
                 * still allowed, so compare positions in the status ladder. */
                $ladder = ['awaiting', 'received', 'processed', 'shipped', 'delivered', 'cancelled', 'returned'];
                $cancel_index = array_search($item['cancelable_till'], $ladder, true);
                $active_index = array_search($item['active_status'], $ladder, true);
                $can_cancel = !$item['is_already_cancelled']
                    && $item['is_cancelable']
                    && $cancel_index !== false
                    && $active_index !== false
                    && $active_index <= $cancel_index;

                /* ---- can this item still be returned? ----
                 * order_status_history_date() returns '' until the item has actually
                 * been DELIVERED. The array_search() it replaced searched a list of
                 * [status, timestamp] PAIRS for the bare string 'delivered', which can
                 * never match - and its false read as index 0, so the return window ran
                 * from the ORDER date and "Return order" showed on items that had never
                 * been delivered. */
                $delivered_date = order_status_history_date($item['status'], 'delivered');
                $can_return = false;
                $return_till = '';
                if ($row['is_returnable'] && !$row['is_already_returned'] && $delivered_date !== '' && $row['type'] !== 'digital_product') {
                    $return_till = date('Y-m-d', strtotime($delivered_date . ' + ' . $return_days . ' days'));
                    $can_return = (date('Y-m-d') < $return_till);
                }

                /* Product attributes ("Size: M, Colour: Blue") arrive as two
                 * parallel comma-joined strings. array_combine() fatals if the two
                 * do not have the same number of parts, so they are checked first. */
                $attributes = [];
                if (!empty($item['attr_name'])) {
                    $names = explode(', ', $item['attr_name']);
                    $values = explode(', ', $item['variant_values']);
                    if (count($names) === count($values)) {
                        $attributes = array_combine($names, $values);
                    }
                }
                /* Rate & review.
                   The page showed a buyer's existing star rating but offered no way to
                   CREATE one, and nothing anywhere else in the account links to the
                   review form - so after buying something there was no route to it at
                   all. save_rating() accepts a review for any item the customer owns
                   that was not returned, so the link is offered on the same basis. */
                $can_review_item = !empty($item['slug'])
                    && !in_array($item['active_status'], ['returned', 'cancelled'], true);
                $review_url = $can_review_item
                    ? base_url('products/details/' . $item['slug']) . '#review-section'
                    : '';
                ?>
                <li class="czap-item">
                    <div class="czap-item__bar">
                        <span class="czap-badge czap-badge--<?= $tone ?>"><?= html_escape($label) ?></span>
                        <?php if ($when !== '') { ?>
                            <span class="czap-muted">On <?= html_escape($when) ?></span>
                        <?php } ?>
                        <span class="czap-item__spacer"></span>
                        <span class="czap-muted">Order #<?= (int) $row['id'] ?></span>
                        <a class="czap-btn czap-btn--quiet czap-btn--sm" href="<?= $detail ?>">
                            Details <i class="uil uil-angle-right"></i>
                        </a>
                    </div>

                    <div class="czap-item__body">
                        <div class="czap-media">
                            <a href="<?= $detail ?>" style="flex:none">
                                <img class="czap-media__img" src="<?= $image ?>" alt="<?= html_escape($item['product_name']) ?>">
                            </a>
                            <div class="czap-media__body">
                                <h3 class="czap-media__title">
                                    <a href="<?= $detail ?>"><?= html_escape($item['product_name']) ?></a>
                                </h3>

                                <?php if (!empty($item['short_description'])) { ?>
                                    <p class="czap-media__text"><?= html_escape(strip_tags($item['short_description'])) ?></p>
                                <?php } ?>

                                <?php if (!empty($attributes)) { ?>
                                    <ul class="czap-attrs">
                                        <?php foreach ($attributes as $name => $value) { ?>
                                            <li><?= html_escape($name) ?>: <b><?= html_escape($value) ?></b></li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>

                                <p style="margin:0 0 6px">
                                    <span class="czap-money"><?= html_escape($currency) ?><?= number_format($item['sub_total'], 2) ?></span>
                                    <span class="czap-muted">
                                        &nbsp;&middot;&nbsp;<?= !empty($this->lang->line('quantity')) ? $this->lang->line('quantity') : 'Qty' ?> <?= (int) $item['quantity'] ?>
                                        &nbsp;&middot;&nbsp;<?= !empty($this->lang->line('via')) ? $this->lang->line('via') : 'via' ?> <?= html_escape($row['payment_method']) ?>
                                    </span>
                                </p>

                                <?php if ($item['product_rating'] > 0) { ?>
                                    <div class="product-rating-small" dir="ltr">
                                        <input name="rating" class="rating rating-loading d-none" data-size="xs"
                                               value="<?= $item['product_rating'] ?>" data-show-clear="false"
                                               data-show-caption="false" readonly>
                                    </div>
                                <?php } ?>

                                <?php if ($can_return && $return_till !== '') { ?>
                                    <p class="czap-help">
                                        <i class="uil uil-info-circle"></i>
                                        Return window closes on <?= html_escape(date('d M Y', strtotime($return_till))) ?>.
                                    </p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($can_cancel || $can_return || $can_review_item) { ?>
                        <div class="czap-item__foot">
                            <?php if ($can_review_item) { ?>
                                <a class="czap-btn czap-btn--ghost czap-btn--sm" href="<?= $review_url ?>">
                                    <i class="uil uil-star"></i>
                                    <?= $item['product_rating'] > 0 ? 'Edit your review' : 'Rate &amp; review' ?>
                                </a>
                            <?php } ?>
                            <?php if ($can_cancel) { ?>
                                <?php /* data-czap-order carries everything the popup needs to name the
                                         item, so it does not have to scrape the DOM back out again. */ ?>
                                <button type="button" class="czap-btn czap-btn--danger czap-btn--sm czap-order-action"
                                        data-status="cancelled"
                                        data-order-id="<?= (int) $row['id'] ?>"
                                        data-product="<?= html_escape($item['product_name']) ?>">
                                    <i class="uil uil-times-circle"></i> Cancel order
                                </button>
                            <?php } ?>
                            <?php if ($can_return) { ?>
                                <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm czap-order-action"
                                        data-status="returned"
                                        data-order-id="<?= (int) $row['id'] ?>"
                                        data-product="<?= html_escape($item['product_name']) ?>">
                                    <i class="uil uil-history-alt"></i> Return order
                                </button>
                            <?php } ?>
                            <span class="czap-item__spacer"></span>
                            <a class="czap-btn czap-btn--quiet czap-btn--sm" target="_blank" rel="noopener"
                               href="<?= base_url('my-account/order-invoice/' . $row['id']) ?>">
                                <i class="uil uil-file-download-alt"></i> Invoice
                            </a>
                        </div>
                    <?php } ?>
                </li>
            <?php }
        } ?>
    </ul>

    <?php /* My_account::orders() has always built these links; the old view never
             printed them, so a buyer with more than 10 orders could not reach the
             older ones at all. */ ?>
    <?php if (!empty($links)) { ?>
        <nav class="cz-pager-nav" aria-label="<?= storefront_pagination_label('your orders') ?>">
            <?= $links ?>
        </nav>
    <?php } ?>

<?php }

$page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Orders & returns',
    'page_sub'     => 'Track a delivery, cancel an item or start a return',
    'page_icon'    => 'uil-box',
    'page_content' => $page_content,
]);
?>

<!-- ==================== POPUP: cancel / return an order ==================== -->
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

            <?php /* Posted as `reason` and stored on every item of the order
                     (order_items.return_reason, migration 076), so returns can be grouped
                     by product and by seller rather than only counted. Required for a
                     return - orders.js blocks the submit without it. */ ?>
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
