<?php
/**
 * My Account > Chat with us.
 *
 * Live chat has not shipped, so this page's job is to hand the customer the
 * channels that DO work rather than to announce a feature. It used to be a dead
 * end: it said "Coming Soon" and offered no way to reach anybody at all.
 *
 * Note on the user variable: every other my-account page gets $users as the
 * logged-in user OBJECT, but My_account::chat() sets it to an ARRAY of chat
 * contacts - which is what used to throw "Attempt to read property username on
 * array" here. The shared shell resolves the user from ion_auth itself, so this
 * view no longer touches either variable.
 */

/* whatsapp_support_link() resolves the number from settings, falling back to the
 * confirmed one. It returns '' if neither is usable, so every use is guarded. */
$whatsapp_link = whatsapp_support_link('Hello Cretzo Support, I need help with my order.');
$support_email = get_settings('system_settings', true);
$support_email = isset($support_email['support_email']) ? trim($support_email['support_email']) : '';

$channels = [];

if (!empty($whatsapp_link)) {
    $channels[] = [
        'icon'  => 'uil-whatsapp',
        'title' => 'WhatsApp',
        'text'  => 'The fastest way to reach a person. Our team replies during business hours.',
        'cta'   => 'Chat on WhatsApp',
        'url'   => $whatsapp_link,
        'class' => 'czap-btn--wa',
        'blank' => true,
    ];
}

$channels[] = [
    'icon'  => 'uil-ticket',
    'title' => 'Support ticket',
    'text'  => 'Best for anything about a specific order - it keeps a written trail you and we can both read back.',
    'cta'   => 'Raise a ticket',
    'url'   => base_url('my-account/support'),
    'class' => 'czap-btn--primary',
    'blank' => false,
];

if ($support_email !== '') {
    $channels[] = [
        'icon'  => 'uil-envelope',
        'title' => 'Email',
        'text'  => 'Write to ' . $support_email . ' if you would rather use your own mail client.',
        'cta'   => 'Send an email',
        'url'   => 'mailto:' . $support_email,
        'class' => 'czap-btn--ghost',
        'blank' => false,
    ];
}

/* --------------------------------------------------------------- content -- */
ob_start(); ?>

<div class="czap-alert czap-alert--info">
    <i class="uil uil-comments-alt"></i>
    <span>
        <strong>Live chat is on its way.</strong>
        We are building in-app chat so you can talk to us without leaving the page. Until it lands,
        every channel below reaches the same team.
    </span>
</div>

<div class="czap-cols" style="margin-top:20px">
    <?php foreach ($channels as $channel) { ?>
        <div class="czap-panel" style="display:flex;flex-direction:column;gap:10px">
            <span class="czap-tile__icon"><i class="uil <?= $channel['icon'] ?>"></i></span>
            <h3 class="czap-tile__title"><?= html_escape($channel['title']) ?></h3>
            <p class="czap-tile__text" style="flex:1"><?= html_escape($channel['text']) ?></p>
            <a class="czap-btn <?= $channel['class'] ?>" href="<?= html_escape($channel['url']) ?>"
               <?= $channel['blank'] ? 'target="_blank" rel="noopener"' : '' ?>>
                <i class="uil <?= $channel['icon'] ?>"></i> <?= html_escape($channel['cta']) ?>
            </a>
        </div>
    <?php } ?>
</div>

<hr class="czap-hr">

<p class="czap-sec">Before you write in</p>
<div class="czap-dl">
    <div class="czap-dl__row">
        <span>Where is my order?</span>
        <span><a href="<?= base_url('my-account/orders') ?>">Track it here</a></span>
    </div>
    <div class="czap-dl__row">
        <span>I want to cancel or return an item</span>
        <span><a href="<?= base_url('my-account/orders') ?>">Do it from your orders</a></span>
    </div>
    <div class="czap-dl__row">
        <span>Where is my refund?</span>
        <span><a href="<?= base_url('my-account/wallet') ?>">Check your wallet</a></span>
    </div>
    <div class="czap-dl__row">
        <span>Anything else</span>
        <span><a href="<?= base_url('home/faq') ?>">Read the FAQs</a></span>
    </div>
</div>

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Chat with us',
    'page_sub'     => 'Pick the channel that suits you',
    'page_icon'    => 'uil-comments-alt',
    'page_content' => $page_content,
]);
