<?php
/**
 * Contact Us.
 *
 * ONE url, two shells. Reached from the account sidebar (or the dashboard's Help
 * tile) by a signed-in customer it renders inside the My Account shell, so it
 * reads as another account section instead of ejecting the customer to an
 * unrelated-looking page. A visitor who is not signed in gets the same cards and
 * the same design language without the account sidebar, which they have no use
 * for. Keeping one url matters: the footer links here, and so does search.
 *
 * Kept deliberately unchanged: the form id (#contact-us-form), the submit button
 * id (#contact-us-submit-btn), the action, and the four field NAMES (username,
 * email, subject, message). The AJAX handler in cretzo/js/custom.js binds to
 * those ids and posts a FormData of this form, and
 * Home::send_contact_us_email() validates those exact names - renaming any of
 * them would silently break the send.
 */

$is_customer = !empty($is_customer);

$contact_address  = (isset($web_settings['address']) && !empty($web_settings['address'])) ? $web_settings['address'] : '';
$contact_number   = (isset($web_settings['support_number']) && !empty($web_settings['support_number'])) ? $web_settings['support_number'] : '';
$contact_email    = (isset($web_settings['support_email']) && !empty($web_settings['support_email'])) ? $web_settings['support_email'] : '';
$contact_map      = (isset($web_settings['map_iframe']) && !empty($web_settings['map_iframe'])) ? $web_settings['map_iframe'] : '';

/*
 * The `map_iframe` setting on this install still holds the example value that
 * ships with the admin panel - a Google embed whose src is the literal
 * "…/maps/embed?pb=..." placeholder. Rendering it produced a 550px empty grey
 * box on the page, which reads as a broken map rather than as an unset setting.
 *
 * So the value is checked for a usable src before it is trusted. When it is not
 * usable the embed is skipped and a directions link built from the postal
 * address takes its place - which always works, and is what someone looking at
 * a map on a contact page actually wants. Paste a real embed into the admin
 * panel and the map comes back on its own.
 */
$contact_map_html = ($contact_map !== '') ? html_entity_decode(stripcslashes($contact_map)) : '';
$map_is_usable = false;
if ($contact_map_html !== '' && preg_match('/<iframe[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/is', $contact_map_html, $m)) {
    $src = trim($m[2]);
    $map_is_usable = ($src !== '' && $src !== '#'
        // "pb=..." / "?..." is the placeholder, not a real embed token.
        && strpos($src, '...') === false
        && preg_match('#^https?://#i', $src) === 1);
}

$directions_url = ($contact_address !== '')
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim(strip_tags(str_replace(['\r\n', '<br>', '<br/>'], ' ', $contact_address))))
    : '';
// WhatsApp is the channel the store actually answers on, but this page used to
// offer only an address, a phone number and a form.
$contact_whatsapp = whatsapp_support_link();

/*
 * Prefill for a signed-in customer. Retyping the name and email that the account
 * already holds is pure friction, and a mistyped address is a reply that never
 * arrives. Both stay editable - someone may want a reply somewhere else.
 */
$prefill_name  = ($is_customer && isset($user->username)) ? $user->username : '';
$prefill_email = ($is_customer && isset($user->email)) ? $user->email : '';

$channels = [];
if ($contact_whatsapp !== '') {
    $channels[] = [
        'icon'  => 'uil-whatsapp',
        'label' => 'WhatsApp',
        'value' => 'Chat on WhatsApp',
        'href'  => $contact_whatsapp,
        'blank' => true,
        'note'  => 'Fastest reply during business hours',
    ];
}
if ($contact_number !== '') {
    $channels[] = [
        'icon'  => 'uil-phone-volume',
        'label' => !empty($this->lang->line('call_us')) ? $this->lang->line('call_us') : 'Call us',
        'value' => $contact_number,
        // tel: so the number is tappable on a phone - punctuation stripped for
        // the href, the number shown exactly as configured.
        'href'  => 'tel:' . preg_replace('/[^0-9+]/', '', $contact_number),
        'blank' => false,
        'note'  => '',
    ];
}
if ($contact_email !== '') {
    $channels[] = [
        'icon'  => 'uil-envelope',
        'label' => !empty($this->lang->line('email_us')) ? $this->lang->line('email_us') : 'Email us',
        'value' => $contact_email,
        // The original markup hardcoded mailto:sandbox@email.com from the
        // purchased template while DISPLAYING the real support address, so
        // clicking it opened a mail to the template's placeholder.
        'href'  => 'mailto:' . $contact_email,
        'blank' => false,
        'note'  => '',
    ];
}

/* ---------------------------------------------------------------- content -- */
ob_start(); ?>

<?php if (!empty($channels)) { ?>
    <p class="czap-sec">Reach us directly</p>
    <div class="czap-cols" style="margin-bottom:8px">
        <?php foreach ($channels as $channel) { ?>
            <a class="czap-stat" href="<?= html_escape($channel['href']) ?>"
               <?= $channel['blank'] ? 'target="_blank" rel="noopener"' : '' ?>>
                <span class="czap-stat__icon"><i class="uil <?= $channel['icon'] ?>"></i></span>
                <span style="min-width:0">
                    <span class="czap-stat__value" style="font-size:15.5px;overflow-wrap:anywhere"><?= html_escape($channel['value']) ?></span>
                    <span class="czap-stat__label"><?= html_escape($channel['note'] !== '' ? $channel['note'] : $channel['label']) ?></span>
                </span>
            </a>
        <?php } ?>
    </div>
<?php } ?>

<?php if ($is_customer) { ?>
    <?php /* A customer writing in about a specific order is far better served by a
             ticket - it is attached to their account, it keeps a thread, and it
             cannot be lost in a mailbox. Say so here rather than letting them use
             the anonymous form and wonder where the reply went. */ ?>
    <div class="czap-alert czap-alert--info" style="margin:22px 0 0">
        <i class="uil uil-ticket"></i>
        <span>
            Writing about a specific order? <a href="<?= base_url('my-account/support') ?>">Raise a support ticket</a>
            instead - it is attached to your account, keeps the whole conversation in one place, and you can
            track the reply from <a href="<?= base_url('my-account/notifications') ?>">your notifications</a>.
        </span>
    </div>
<?php } ?>

<hr class="czap-hr">

<div class="czap-grid" style="align-items:start">

    <div class="czap-field czap-span-2">
        <p class="czap-sec" style="margin-top:0">Send us a message</p>

        <form id="contact-us-form" action="<?= base_url('home/send-contact-us-email') ?>" method="POST" novalidate>
            <div class="czap-grid">
                <div class="czap-field">
                    <?php /* Labelled "Name", not "Username": the POST field has to stay
                             `username` for Home::send_contact_us_email(), but a person on a
                             contact form is giving their name, not an account username. */ ?>
                    <label class="czap-field__label" for="contact_username">
                        <?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Name' ?><span class="czap-req">*</span>
                    </label>
                    <input class="czap-input" type="text" id="contact_username" name="username"
                           placeholder="Your name" value="<?= html_escape($prefill_name) ?>" required>
                </div>

                <div class="czap-field">
                    <label class="czap-field__label" for="contact_email">
                        <?= !empty($this->lang->line('email')) ? $this->lang->line('email') : 'Email' ?><span class="czap-req">*</span>
                    </label>
                    <input class="czap-input" type="email" id="contact_email" name="email"
                           placeholder="you@example.com" value="<?= html_escape($prefill_email) ?>" required>
                    <p class="czap-help">We reply to this address.</p>
                </div>

                <div class="czap-field czap-span-2">
                    <?php /* Subject is `required` server-side, but the old form left it optional
                             here - so submitting without one failed validation with no hint as
                             to which field was at fault. */ ?>
                    <label class="czap-field__label" for="contact_subject">
                        <?= !empty($this->lang->line('subject')) ? $this->lang->line('subject') : 'Subject' ?><span class="czap-req">*</span>
                    </label>
                    <input class="czap-input" type="text" id="contact_subject" name="subject"
                           placeholder="What is this about?" required>
                </div>

                <div class="czap-field czap-span-2">
                    <label class="czap-field__label" for="contact_message">
                        <?= !empty($this->lang->line('message')) ? $this->lang->line('message') : 'Message' ?><span class="czap-req">*</span>
                    </label>
                    <textarea class="czap-textarea" id="contact_message" name="message" rows="6"
                              placeholder="Tell us a bit more..." required></textarea>
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-top:18px">
                <button id="contact-us-submit-btn" type="submit" class="czap-btn czap-btn--primary">
                    <i class="uil uil-message"></i>
                    <?= !empty($this->lang->line('send_message')) ? $this->lang->line('send_message') : 'Send message' ?>
                </button>
                <span class="czap-help" style="margin:0">We usually reply within one working day.</span>
            </div>
        </form>
    </div>

    <?php if ($map_is_usable || $contact_address !== '') { ?>
        <div class="czap-field czap-span-2">
            <p class="czap-sec" style="margin-top:0">
                <?= !empty($this->lang->line('find_us')) ? $this->lang->line('find_us') : 'Find us' ?>
            </p>

            <?php if ($contact_address !== '') { ?>
                <div class="czap-panel czap-panel--soft" style="margin-bottom:14px">
                    <p class="czap-panel__title"><i class="uil uil-location-pin-alt"></i> Our address</p>
                    <address class="czap-addr__lines" style="margin:0 0 12px;font-style:normal">
                        <?= output_escaping(str_replace('\r\n', '<br>', $contact_address)) ?>
                    </address>
                    <?php if ($directions_url !== '') { ?>
                        <a class="czap-btn czap-btn--ghost czap-btn--sm" href="<?= html_escape($directions_url) ?>"
                           target="_blank" rel="noopener">
                            <i class="uil uil-directions"></i> Get directions
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($map_is_usable) { ?>
                <?php /* The stored value is an <iframe> pasted into the admin panel, hence the
                         decode. The wrapper forces an aspect ratio so the embed cannot collapse
                         to zero height or stick out of the card, whatever width/height
                         attributes came with it. Only rendered when the src is real - see the
                         $map_is_usable note at the top of this file. */ ?>
                <div class="czcontact-map">
                    <?= $contact_map_html ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<?php $page_content = ob_get_clean();

/* ---------------------------------------------------------------- actions -- */
ob_start(); ?>
<?php if ($contact_whatsapp !== '') { ?>
    <a class="czap-btn czap-btn--wa" href="<?= html_escape($contact_whatsapp) ?>" target="_blank" rel="noopener">
        <i class="uil uil-whatsapp"></i> WhatsApp
    </a>
<?php } ?>
<?php if ($is_customer) { ?>
    <a class="czap-btn czap-btn--ghost" href="<?= base_url('my-account/support') ?>">
        <i class="uil uil-ticket"></i> Support tickets
    </a>
<?php } ?>
<?php $page_actions = ob_get_clean();

$page_title = !empty($this->lang->line('contact_us')) ? $this->lang->line('contact_us') : 'Contact us';
$page_sub   = 'Questions about an order, a product, or selling with us';

if ($is_customer) {
    /* Inside the account shell: same hero, same sidebar, same card as every
       other account section. */
    $this->load->view('front-end/' . THEME . '/partials/account-layout', [
        'active_menu'  => 'contact-us',
        'page_title'   => $page_title,
        'page_sub'     => $page_sub,
        'page_icon'    => 'uil-envelope',
        'page_actions' => $page_actions,
        'page_content' => $page_content,
    ]);
} else {
    /* Signed out: the same design language, minus a sidebar full of links that
       would only bounce a guest to the login modal. */ ?>
    <div class="czap">
        <nav class="czap-crumbs" aria-label="Breadcrumb">
            <a href="<?= base_url() ?>"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a>
            <span class="czap-crumbs__sep">/</span>
            <span class="czap-crumbs__now"><?= html_escape($page_title) ?></span>
        </nav>

        <div class="czap-shell czcontact-shell">
            <main class="czap-main">
                <section class="czap-card">
                    <div class="czap-card__head">
                        <div class="czap-card__titles">
                            <h1 class="czap-card__title"><i class="uil uil-envelope"></i> <?= html_escape($page_title) ?></h1>
                            <p class="czap-card__sub"><?= html_escape($page_sub) ?></p>
                        </div>
                        <?php if ($page_actions !== '') { ?>
                            <div class="czap-card__actions"><?= $page_actions ?></div>
                        <?php } ?>
                    </div>
                    <div class="czap-card__body"><?= $page_content ?></div>
                </section>
            </main>
        </div>
    </div>
<?php } ?>

<style>
    /* Signed out there is no sidebar, so the shell collapses to one column. */
    .czcontact-shell {
        grid-template-columns: minmax(0, 1fr);
    }

    /* The admin pastes a raw <iframe> whose width/height attributes are whatever
       Google handed them. Forcing a ratio stops it collapsing to nothing or
       sticking out of the card. */
    .czcontact-map {
        position: relative;
        aspect-ratio: 4 / 3;
        min-height: 240px;
        overflow: hidden;
        border-radius: var(--czap-r, 14px);
        border: 1px solid var(--czap-line, #e9eaee);
        background: var(--czap-line-2, #f2f3f5);
    }

    .czcontact-map iframe,
    .czcontact-map > * {
        position: absolute;
        inset: 0;
        width: 100% !important;
        height: 100% !important;
        border: 0;
        display: block;
    }
</style>
