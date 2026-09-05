<?php
/**
 * ============================================================================
 * My Account - shared page shell.
 * ============================================================================
 *
 * Every page under /my-account renders through this partial, so the hero, the
 * navigation, the breadcrumb and the content card are defined once instead of
 * being copy-pasted (and drifting) across eleven views.
 *
 * A page uses it by buffering its own markup and handing it over:
 *
 *     <?php ob_start(); ?>
 *         ... page body ...
 *     <?php $page_content = ob_get_clean();
 *     $this->load->view('front-end/' . THEME . '/partials/account-layout', [
 *         'active_menu'  => $main_page,
 *         'page_title'   => 'Saved addresses',
 *         'page_sub'     => 'Where we deliver your orders',
 *         'page_icon'    => 'uil-map-marker',
 *         'page_actions' => $actions_html,   // optional
 *         'page_content' => $page_content,
 *         'page_card'    => true,            // optional, default true
 *     ]); ?>
 *
 * With `page_card => false` the content is dropped straight into the main
 * column, for pages that render several cards of their own (dashboard, order
 * details). Popups belong AFTER this call, outside the shell, so they are
 * direct children of <body> and no ancestor's overflow or transform can clip
 * them.
 *
 * The user is resolved here rather than taken from the caller on purpose: the
 * controller sets $users on some actions but not on others (favorites() and
 * notifications() never do, and chat() sets $users to an ARRAY of chat
 * contacts, which is what used to throw "Attempt to read property username on
 * array"). Reading ion_auth directly is the one source that is right on every
 * action.
 */

$czap_user = $this->ion_auth->logged_in() ? $this->ion_auth->user()->row() : null;

$czap_name  = ($czap_user && !empty($czap_user->username)) ? $czap_user->username : 'My account';
$czap_email = ($czap_user && !empty($czap_user->email)) ? $czap_user->email : '';
$czap_phone = ($czap_user && !empty($czap_user->mobile)) ? $czap_user->mobile : '';

/* `users`.`image` holds a bare file name inside USER_IMG_PATH, so a row can
 * legitimately name a file that is no longer on disk - get_user_avatar_url()
 * returns '' then, and we fall back to the theme icon rather than rendering a
 * broken image. The placeholder is a full-bleed line drawing, so it is flagged
 * for the CSS to letterbox instead of crop (see .czap-avatar img.is-placeholder). */
$czap_photo = get_user_avatar_url(($czap_user && isset($czap_user->image)) ? $czap_user->image : '');
$czap_is_placeholder = ($czap_photo === '');
if ($czap_is_placeholder) {
    $czap_photo = base_url('assets/front_end/cretzo/img/new_cretzo/user.png');
}

$czap_settings = get_settings('system_settings', true);
$czap_currency = isset($czap_settings['currency']) ? $czap_settings['currency'] : '';
$czap_balance  = ($czap_user && isset($czap_user->balance)) ? (float) $czap_user->balance : 0;

/* Same helper the header bells use, so the sidebar badge and the bell can never
 * disagree about the number. */
$czap_unread = ($czap_user) ? (int) user_unread_notification_count($czap_user->id) : 0;

$czap_member_since = ($czap_user && !empty($czap_user->created_at))
    ? date('M Y', is_numeric($czap_user->created_at) ? (int) $czap_user->created_at : strtotime($czap_user->created_at))
    : '';

$active_menu  = isset($active_menu) ? $active_menu : '';
$page_title   = isset($page_title) ? $page_title : 'My account';
$page_sub     = isset($page_sub) ? $page_sub : '';
$page_icon    = isset($page_icon) ? $page_icon : '';
$page_actions = isset($page_actions) ? $page_actions : '';
$page_content = isset($page_content) ? $page_content : '';
$page_card    = isset($page_card) ? (bool) $page_card : true;
$page_body_class = isset($page_body_class) ? $page_body_class : '';

/*
 * Navigation. `match` lists every $main_page that should light this item up -
 * order-details belongs to Orders, and transactions belongs to Wallet, so the
 * user is never left looking at a page with nothing selected.
 */
$czap_nav = [
    [
        'label' => '',
        'items' => [
            ['url' => 'my-account', 'text' => 'Dashboard', 'icon' => 'uil-apps', 'match' => ['dashboard']],
        ],
    ],
    [
        'label' => 'Orders',
        'items' => [
            ['url' => 'my-account/orders', 'text' => 'Orders & returns', 'icon' => 'uil-box', 'match' => ['orders', 'order-details']],
            ['url' => 'my-account/favorites', 'text' => 'Wishlist', 'icon' => 'uil-heart', 'match' => ['favorites', 'wishlist']],
        ],
    ],
    [
        'label' => 'Credits',
        'items' => [
            ['url' => 'my-account/wallet', 'text' => 'Wallet', 'icon' => 'uil-wallet', 'match' => ['wallet']],
            ['url' => 'my-account/transactions', 'text' => 'Transactions', 'icon' => 'uil-receipt-alt', 'match' => ['transactions']],
            ['url' => 'my-account/refer-and-earn', 'text' => 'Refer & Earn', 'icon' => 'uil-share-alt', 'match' => ['refer-and-earn']],
        ],
    ],
    [
        'label' => 'Account',
        'items' => [
            ['url' => 'my-account/profile', 'text' => 'Profile', 'icon' => 'uil-user', 'match' => ['profile']],
            ['url' => 'my-account/manage-address', 'text' => 'Addresses', 'icon' => 'uil-map-marker', 'match' => ['address']],
            ['url' => 'my-account/notifications', 'text' => 'Notifications', 'icon' => 'uil-bell', 'match' => ['notifications'], 'count' => $czap_unread],
        ],
    ],
    [
        'label' => 'Help',
        'items' => [
            ['url' => 'my-account/support', 'text' => 'Support tickets', 'icon' => 'uil-ticket', 'match' => ['support']],
            ['url' => 'my-account/chat', 'text' => 'Chat with us', 'icon' => 'uil-comments-alt', 'match' => ['chat']],
            /* Contact Us keeps its own public url (the footer and search both point
             * at it) but renders inside this shell for a signed-in customer, so it
             * behaves like any other section here. See pages/contact-us.php. */
            ['url' => 'contact-us', 'text' => 'Contact us', 'icon' => 'uil-envelope', 'match' => ['contact-us']],
            ['url' => 'terms-and-conditions', 'text' => 'Terms of use', 'icon' => 'uil-file-alt', 'match' => []],
            ['url' => 'privacy-policy', 'text' => 'Privacy policy', 'icon' => 'uil-shield-check', 'match' => []],
        ],
    ],
];
?>
<script>
    /* The account pages need a tinted page ground, and template.php's <body> tag
       is shared with the whole storefront - so the class is added here instead
       of being baked into every page's markup. */
    document.body.classList.add('czap-body');
</script>

<div class="czap <?= html_escape($page_body_class) ?>">

    <nav class="czap-crumbs" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>">Home</a>
        <span class="czap-crumbs__sep">/</span>
        <?php if ($active_menu === 'dashboard') { ?>
            <span class="czap-crumbs__now">My account</span>
        <?php } else { ?>
            <a href="<?= base_url('my-account') ?>">My account</a>
            <span class="czap-crumbs__sep">/</span>
            <span class="czap-crumbs__now"><?= html_escape($page_title) ?></span>
        <?php } ?>
    </nav>

    <!-- ============================== hero ============================== -->
    <header class="czap-hero">
        <div class="czap-hero__id">
            <div class="czap-avatar">
                <img src="<?= $czap_photo ?>" alt="<?= html_escape($czap_name) ?>"
                     class="<?= $czap_is_placeholder ? 'is-placeholder' : '' ?>">
            </div>
            <div class="czap-hero__meta">
                <h1 class="czap-hero__name"><?= html_escape($czap_name) ?></h1>
                <p class="czap-hero__contact">
                    <?php // Social signups have no phone number at all - show nothing rather than
                          // a bare "+91" or an invented one (see migration 061). ?>
                    <?php if ($czap_phone !== '') { ?>
                        <span><i class="uil uil-phone"></i> <b>+91 <?= html_escape($czap_phone) ?></b></span>
                    <?php } ?>
                    <?php if ($czap_email !== '') { ?>
                        <span><i class="uil uil-envelope"></i> <b><?= html_escape($czap_email) ?></b></span>
                    <?php } ?>
                </p>
                <div class="czap-hero__chips">
                    <span class="czap-chip czap-chip--brand">
                        <i class="uil uil-wallet"></i>
                        <?= html_escape($czap_currency) ?><?= number_format($czap_balance, 2) ?> in wallet
                    </span>
                    <?php if ($czap_member_since !== '') { ?>
                        <span class="czap-chip"><i class="uil uil-award"></i> Member since <?= html_escape($czap_member_since) ?></span>
                    <?php } ?>
                    <?php if ($czap_unread > 0) { ?>
                        <a class="czap-chip" href="<?= base_url('my-account/notifications') ?>">
                            <i class="uil uil-bell"></i> <?= $czap_unread ?> unread
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="czap-hero__side">
            <a href="<?= base_url('my-account/profile') ?>" class="czap-btn czap-btn--ghost">
                <i class="uil uil-edit"></i> Edit profile
            </a>
            <?php /* Was hardcoded to https://cretzo.com/login/logout, so logging out of any
                     non-production environment bounced the user to production instead of
                     ending their session here. */ ?>
            <button type="button" class="czap-btn czap-btn--danger" id="czap-logout"
                    data-logout-url="<?= base_url('login/logout') ?>">
                <i class="uil uil-signout"></i> Logout
            </button>
        </div>
    </header>

    <div class="czap-shell">

        <!-- ============================ sidebar ============================ -->
        <aside class="czap-nav" aria-label="Account sections">
            <?php foreach ($czap_nav as $group) { ?>
                <div class="czap-nav__group">
                    <?php if ($group['label'] !== '') { ?>
                        <p class="czap-nav__label"><?= html_escape($group['label']) ?></p>
                    <?php } ?>
                    <?php foreach ($group['items'] as $item) {
                        $is_active = in_array($active_menu, $item['match'], true); ?>
                        <a class="czap-nav__link <?= $is_active ? 'is-active' : '' ?>"
                           href="<?= base_url($item['url']) ?>"
                           <?= $is_active ? 'aria-current="page"' : '' ?>>
                            <i class="uil <?= $item['icon'] ?>"></i>
                            <span><?= html_escape($item['text']) ?></span>
                            <?php if (!empty($item['count'])) { ?>
                                <span class="czap-nav__count" data-count="<?= (int) $item['count'] ?>"><?= $item['count'] > 99 ? '99+' : (int) $item['count'] ?></span>
                            <?php } ?>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="czap-nav__group">
                <a class="czap-nav__link czap-nav__link--danger" href="<?= base_url('login/logout') ?>" id="logout_btn">
                    <i class="uil uil-signout"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- ============================= content ============================= -->
        <main class="czap-main">
            <?php if ($page_card) { ?>
                <section class="czap-card">
                    <div class="czap-card__head">
                        <div class="czap-card__titles">
                            <h2 class="czap-card__title">
                                <?php if ($page_icon !== '') { ?><i class="uil <?= $page_icon ?>"></i><?php } ?>
                                <?= html_escape($page_title) ?>
                            </h2>
                            <?php if ($page_sub !== '') { ?>
                                <p class="czap-card__sub"><?= $page_sub ?></p>
                            <?php } ?>
                        </div>
                        <?php if ($page_actions !== '') { ?>
                            <div class="czap-card__actions"><?= $page_actions ?></div>
                        <?php } ?>
                    </div>
                    <div class="czap-card__body"><?= $page_content ?></div>
                </section>
            <?php } else { ?>
                <?= $page_content ?>
            <?php } ?>
        </main>
    </div>
</div>

<script>
    /* Logging out is destructive from the user's point of view (an unsaved form,
       a half-filled cart), so confirm it. The sidebar's plain link stays as the
       no-JS path. */
    (function () {
        var btn = document.getElementById('czap-logout');
        if (!btn || !window.CzAccount) {
            return;
        }
        btn.addEventListener('click', function () {
            CzAccount.confirm({
                title: 'Log out of Cretzo?',
                text: 'You will need to sign in again to see your orders and wallet.',
                confirmText: 'Log out',
                cancelText: 'Stay signed in',
                tone: 'danger',
                icon: 'uil-signout'
            }).then(function (ok) {
                if (ok) {
                    window.location.href = btn.getAttribute('data-logout-url');
                }
            });
        });
    })();
</script>
