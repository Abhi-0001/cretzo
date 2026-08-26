<?php
/*
 * Contact Us, rebuilt in the cretzo design language.
 *
 * The previous version was the untouched purchased-template layout (bootstrap `form-floating`
 * inputs, `display-4` headings, a `card` with the map welded to one half of it), so it did not
 * look like the rest of this storefront. Styling lives in cretzo/contact-us.css, which
 * include-css.php picks up automatically from $main_page === 'contact-us'.
 *
 * Kept deliberately unchanged: the form id (#contact-us-form), the submit button id
 * (#contact-us-submit-btn), the action, and the four field names (username, email, subject,
 * message). The AJAX handler in cretzo/js/custom.js binds to those ids and posts a FormData of
 * this form, and Home::send_contact_us_email() validates those exact names - renaming any of
 * them would silently break the send.
 */
$contact_address = (isset($web_settings['address']) && !empty($web_settings['address'])) ? $web_settings['address'] : '';
$contact_number  = (isset($web_settings['support_number']) && !empty($web_settings['support_number'])) ? $web_settings['support_number'] : '';
$contact_email   = (isset($web_settings['support_email']) && !empty($web_settings['support_email'])) ? $web_settings['support_email'] : '';
$contact_map     = (isset($web_settings['map_iframe']) && !empty($web_settings['map_iframe'])) ? $web_settings['map_iframe'] : '';
// WhatsApp is the channel the store actually answers on, but this page only offered an address,
// a phone number and a form. whatsapp_support_link() resolves the number from settings.
$contact_whatsapp = whatsapp_support_link();
?>

<!-- breadcrumb -->
<div class="content-wrapper">
    <section class="wrapper bg-soft-grape">
        <div class="container py-3 py-md-5">
            <nav class="d-inline-block" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= base_url() ?>" class="text-decoration-none"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a></li>
                    <?php if (isset($right_breadcrumb) && !empty($right_breadcrumb)) {
                        foreach ($right_breadcrumb as $row) {
                    ?>
                            <li class="breadcrumb-item"><?= $row ?></li>
                    <?php }
                    } ?>
                    <li class="breadcrumb-item active text-muted" aria-current="page"><?= !empty($this->lang->line('contact_us')) ? $this->lang->line('contact_us') : 'Contact Us' ?></li>
                </ol>
            </nav>
        </div>
    </section>
</div>
<!-- end breadcrumb -->

<section class="contact-page">

    <div class="contact-intro">
        <h1 class="heading-b"><?= !empty($this->lang->line('contact_us')) ? $this->lang->line('contact_us') : 'Contact Us' ?></h1>
        <p class="text-n op-8">Questions about an order, a product or selling with us? Send us a message and we'll get back to you shortly.</p>
    </div>

    <?php if ($contact_address !== '' || $contact_number !== '' || $contact_email !== '' || $contact_whatsapp !== '') { ?>
        <div class="contact-tiles">

            <?php if ($contact_address !== '') { ?>
                <div class="contact-tile">
                    <span class="contact-tile-icon" aria-hidden="true"><i class="uil uil-location-pin-alt"></i></span>
                    <h2 class="text-b fw-b"><?= !empty($this->lang->line('find_us')) ? $this->lang->line('find_us') : 'Find Us' ?></h2>
                    <address class="text-n op-8 mb-0"><?= output_escaping(str_replace('\r\n', '<br>', $contact_address)) ?></address>
                </div>
            <?php } ?>

            <?php if ($contact_number !== '') { ?>
                <div class="contact-tile">
                    <span class="contact-tile-icon" aria-hidden="true"><i class="uil uil-phone-volume"></i></span>
                    <h2 class="text-b fw-b"><?= !empty($this->lang->line('call_us')) ? $this->lang->line('call_us') : 'Call Us' ?></h2>
                    <?php // tel: link so the number is tappable on a phone; strip spaces and
                          // punctuation for the href but show the number as configured. ?>
                    <p class="text-n op-8 mb-0">
                        <a class="contact-link" href="tel:<?= preg_replace('/[^0-9+]/', '', $contact_number) ?>"><?= html_escape($contact_number) ?></a>
                    </p>
                </div>
            <?php } ?>

            <?php if ($contact_whatsapp !== '') { ?>
                <div class="contact-tile">
                    <span class="contact-tile-icon" aria-hidden="true"><i class="uil uil-whatsapp"></i></span>
                    <h2 class="text-b fw-b">WhatsApp Us</h2>
                    <p class="text-n op-8 mb-0">
                        <a class="contact-link" href="<?= html_escape($contact_whatsapp) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
                    </p>
                </div>
            <?php } ?>

            <?php if ($contact_email !== '') { ?>
                <div class="contact-tile">
                    <span class="contact-tile-icon" aria-hidden="true"><i class="uil uil-envelope"></i></span>
                    <h2 class="text-b fw-b"><?= !empty($this->lang->line('email_us')) ? $this->lang->line('email_us') : 'Email Us' ?></h2>
                    <?php // The old markup hardcoded mailto:sandbox@email.com from the template while
                          // DISPLAYING the real support address, so clicking it opened a mail to the
                          // template's placeholder. ?>
                    <p class="text-n op-8 mb-0">
                        <a class="contact-link" href="mailto:<?= html_escape($contact_email) ?>"><?= html_escape($contact_email) ?></a>
                    </p>
                </div>
            <?php } ?>

        </div>
    <?php } ?>

    <div class="contact-body<?= ($contact_map === '') ? ' contact-body-single' : '' ?>">

        <div class="contact-card contact-form-card">
            <h2 class="heading-n fw-b">Send us a message</h2>
            <p class="text-s op-6 contact-card-sub">We usually reply within one working day.</p>

            <form id="contact-us-form" class="contact-form" action="<?= base_url('home/send-contact-us-email') ?>" method="POST" novalidate>

                <div class="contact-field-row">
                    <div class="contact-field">
                        <?php // 'name', not 'username': the POST field has to stay `username` for
                              // Home::send_contact_us_email(), but a visitor on a public contact form is
                              // giving their name, not an account username. ?>
                        <label class="text-s fw-b" for="contact_username"><?= !empty($this->lang->line('name')) ? $this->lang->line('name') : 'Name' ?> <span class="contact-req">*</span></label>
                        <input class="input" type="text" id="contact_username" name="username" placeholder="Your name" required>
                    </div>
                    <div class="contact-field">
                        <label class="text-s fw-b" for="contact_email"><?= !empty($this->lang->line('email')) ? $this->lang->line('email') : 'Email' ?> <span class="contact-req">*</span></label>
                        <input class="input" type="email" id="contact_email" name="email" placeholder="you@example.com" required>
                    </div>
                </div>

                <div class="contact-field">
                    <?php // Subject is `required` server-side in Home::send_contact_us_email(), but the
                          // old form left it optional here - so submitting without one failed
                          // validation with no hint as to which field was at fault. ?>
                    <label class="text-s fw-b" for="contact_subject"><?= !empty($this->lang->line('subject')) ? $this->lang->line('subject') : 'Subject' ?> <span class="contact-req">*</span></label>
                    <input class="input" type="text" id="contact_subject" name="subject" placeholder="What is this about?" required>
                </div>

                <div class="contact-field">
                    <label class="text-s fw-b" for="contact_message"><?= !empty($this->lang->line('message')) ? $this->lang->line('message') : 'Message' ?> <span class="contact-req">*</span></label>
                    <textarea class="input contact-textarea" id="contact_message" name="message" placeholder="Tell us a bit more..." required></textarea>
                </div>

                <button id="contact-us-submit-btn" type="submit" class="cretzo btn btn-dark contact-submit"><?= !empty($this->lang->line('send_message')) ? $this->lang->line('send_message') : 'Send Message' ?></button>
            </form>
        </div>

        <?php if ($contact_map !== '') { ?>
            <div class="contact-card contact-map-card">
                <?php // The stored value is an <iframe> pasted into the admin panel, hence the
                      // decode - carried over from the previous version of this page unchanged. ?>
                <div class="contact-map">
                    <?= html_entity_decode(stripcslashes($contact_map)) ?>
                </div>
            </div>
        <?php } ?>

    </div>

</section>
