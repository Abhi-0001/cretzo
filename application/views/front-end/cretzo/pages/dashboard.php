<?php
/**
 * My Account > Dashboard.
 *
 * Rebuilt on the shared account shell. The hero (name, contact, wallet chip,
 * avatar, logout) now lives in partials/account-layout.php, so this page is
 * only what is specific to the dashboard: the numbers, the most recent orders,
 * and the quick-action grid.
 */

$currency = isset($settings['currency']) ? $settings['currency'] : '';
$balance  = isset($users->balance) ? (float) $users->balance : 0;

$stat_orders    = isset($stat_orders) ? (int) $stat_orders : 0;
$stat_wishlist  = isset($stat_wishlist) ? (int) $stat_wishlist : 0;
$stat_addresses = isset($stat_addresses) ? (int) $stat_addresses : 0;
$stat_unread    = isset($stat_unread) ? (int) $stat_unread : 0;
$recent_orders  = isset($recent_orders) ? $recent_orders : [];

$tiles = [
    [
        'url'  => base_url('my-account/orders'),
        'icon' => 'uil-box',
        'title' => 'Orders &amp; returns',
        'text' => 'Track a delivery, cancel an item or start a return.',
    ],
    [
        'url'  => base_url('my-account/favorites'),
        'icon' => 'uil-heart',
        'title' => 'My wishlist',
        'text' => 'Everything you saved for later, ready to move to the bag.',
    ],
    [
        'url'  => base_url('my-account/manage-address'),
        'icon' => 'uil-map-marker',
        'title' => 'Addresses',
        'text' => 'Save addresses now for a one-tap checkout later.',
    ],
    [
        'url'  => base_url('my-account/wallet'),
        'icon' => 'uil-wallet',
        'title' => 'My wallet',
        'text' => 'Top up, and spend your Cretzo cash at checkout.',
    ],
    [
        'url'  => base_url('my-account/support'),
        'icon' => 'uil-ticket',
        'title' => 'Support tickets',
        'text' => 'Raise a ticket and keep a written trail of our replies.',
    ],
    [
        'url'  => base_url('contact-us'),
        'icon' => 'uil-life-ring',
        'title' => 'Help centre',
        'text' => 'Answers to the questions we get asked the most.',
    ],
];

ob_start(); ?>

<!-- ============================== stat row ============================== -->
<section class="czap-card">
    <div class="czap-card__head">
        <div class="czap-card__titles">
            <h2 class="czap-card__title"><i class="uil uil-apps"></i> Overview</h2>
            <p class="czap-card__sub">Your account at a glance</p>
        </div>
    </div>
    <div class="czap-card__body">
        <div class="czap-stats">
            <a class="czap-stat" href="<?= base_url('my-account/orders') ?>">
                <span class="czap-stat__icon"><i class="uil uil-box"></i></span>
                <span>
                    <span class="czap-stat__value"><?= $stat_orders ?></span>
                    <span class="czap-stat__label"><?= $stat_orders === 1 ? 'Order placed' : 'Orders placed' ?></span>
                </span>
            </a>
            <a class="czap-stat" href="<?= base_url('my-account/wallet') ?>">
                <span class="czap-stat__icon czap-stat__icon--ok"><i class="uil uil-wallet"></i></span>
                <span>
                    <span class="czap-stat__value"><?= html_escape($currency) ?><?= number_format($balance, 2) ?></span>
                    <span class="czap-stat__label">Wallet balance</span>
                </span>
            </a>
            <a class="czap-stat" href="<?= base_url('my-account/favorites') ?>">
                <span class="czap-stat__icon czap-stat__icon--warn"><i class="uil uil-heart"></i></span>
                <span>
                    <span class="czap-stat__value"><?= $stat_wishlist ?></span>
                    <span class="czap-stat__label"><?= $stat_wishlist === 1 ? 'Wishlisted item' : 'Wishlisted items' ?></span>
                </span>
            </a>
            <a class="czap-stat" href="<?= base_url('my-account/notifications') ?>">
                <span class="czap-stat__icon czap-stat__icon--info"><i class="uil uil-bell"></i></span>
                <span>
                    <span class="czap-stat__value"><?= $stat_unread ?></span>
                    <span class="czap-stat__label">Unread updates</span>
                </span>
            </a>
        </div>
    </div>
</section>

<!-- ============================ recent orders ============================ -->
<section class="czap-card">
    <div class="czap-card__head">
        <div class="czap-card__titles">
            <h2 class="czap-card__title"><i class="uil uil-history"></i> Recent orders</h2>
            <p class="czap-card__sub">Your three most recent items</p>
        </div>
        <?php if (!empty($recent_orders)) { ?>
            <div class="czap-card__actions">
                <a class="czap-btn czap-btn--ghost czap-btn--sm" href="<?= base_url('my-account/orders') ?>">
                    View all <i class="uil uil-arrow-right"></i>
                </a>
            </div>
        <?php } ?>
    </div>
    <div class="czap-card__body">
        <?php if (empty($recent_orders)) { ?>
            <div class="czap-empty" style="padding:34px 20px">
                <div class="czap-empty__icon"><i class="uil uil-shopping-bag"></i></div>
                <h3 class="czap-empty__title">No orders yet</h3>
                <p class="czap-empty__text">When you place your first order it will show up here with its live status.</p>
                <a class="czap-btn czap-btn--primary" href="<?= base_url('products') ?>">
                    <i class="uil uil-shopping-cart"></i> Start shopping
                </a>
            </div>
        <?php } else { ?>
            <ul class="czap-list">
                <?php
                $printed = 0;
                foreach ($recent_orders as $order) {
                    foreach ($order['order_items'] as $item) {
                        if ($printed >= 3) {
                            break 2;
                        }
                        $printed++;
                        $tone  = order_status_tone($item['active_status']);
                        $image = (!empty($item['variant_image'])) ? $item['variant_image'] : $item['image'];
                        $when  = isset($item['status'][array_key_last($item['status'])][1])
                            ? orderStatusTimeToHumanReadableString($item['status'][array_key_last($item['status'])][1])
                            : '';
                        ?>
                        <li class="czap-item">
                            <div class="czap-item__bar">
                                <span class="czap-badge czap-badge--<?= $tone ?>"><?= html_escape(order_status_label($item['active_status'])) ?></span>
                                <?php if ($when !== '') { ?>
                                    <span class="czap-muted">On <?= html_escape($when) ?></span>
                                <?php } ?>
                                <span class="czap-item__spacer"></span>
                                <span class="czap-muted">Order #<?= (int) $order['id'] ?></span>
                            </div>
                            <div class="czap-item__body">
                                <div class="czap-media">
                                    <img class="czap-media__img" src="<?= $image ?>" alt="<?= html_escape($item['product_name']) ?>">
                                    <div class="czap-media__body">
                                        <h3 class="czap-media__title">
                                            <a href="<?= base_url('my-account/order-details/' . $order['id'] . '/' . $item['id']) ?>">
                                                <?= html_escape($item['product_name']) ?>
                                            </a>
                                        </h3>
                                        <p class="czap-media__text"><?= html_escape(strip_tags($item['short_description'])) ?></p>
                                        <p style="margin:0">
                                            <span class="czap-money"><?= html_escape($currency) ?><?= number_format($item['sub_total'], 2) ?></span>
                                            <span class="czap-muted">&nbsp;&middot;&nbsp;Qty <?= (int) $item['quantity'] ?></span>
                                        </p>
                                    </div>
                                    <a class="czap-btn czap-btn--ghost czap-btn--sm czap-media__action"
                                       href="<?= base_url('my-account/order-details/' . $order['id'] . '/' . $item['id']) ?>">
                                        Details <i class="uil uil-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php }
                } ?>
            </ul>
        <?php } ?>
    </div>
</section>

<!-- ============================ quick actions ============================ -->
<section class="czap-card">
    <div class="czap-card__head">
        <div class="czap-card__titles">
            <h2 class="czap-card__title"><i class="uil uil-th"></i> Everything in your account</h2>
            <p class="czap-card__sub">Jump straight to what you came for</p>
        </div>
    </div>
    <div class="czap-card__body">
        <div class="czap-tiles">
            <?php foreach ($tiles as $tile) { ?>
                <a class="czap-tile" href="<?= $tile['url'] ?>">
                    <span class="czap-tile__go"><i class="uil uil-arrow-right"></i></span>
                    <span class="czap-tile__icon"><i class="uil <?= $tile['icon'] ?>"></i></span>
                    <h3 class="czap-tile__title"><?= $tile['title'] ?></h3>
                    <p class="czap-tile__text"><?= $tile['text'] ?></p>
                </a>
            <?php } ?>
        </div>
    </div>
</section>

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Dashboard',
    'page_content' => $page_content,
    'page_card'    => false,
]);
