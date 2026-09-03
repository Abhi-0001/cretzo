<?php
/**
 * ============================================================================
 * Shared layout for the four policy documents.
 * ============================================================================
 *
 * Terms & Conditions, Privacy Policy, Return & Refund Policy and Shipping
 * Policy were four copy-pasted views differing only in a lang key and a
 * variable name - which is how half of them ended up printing a second <h1>
 * over the one already inside the stored document. They all render through here
 * now.
 *
 * A page uses it as:
 *     $this->load->view('front-end/' . THEME . '/partials/legal-page', [
 *         'legal_key'   => 'terms',            // which sibling is current
 *         'legal_title' => 'Terms & Conditions',
 *         'legal_body'  => $terms_and_conditions,   // the raw stored HTML
 *     ]);
 *
 * legal_page_prepare() does the work on the blob: strips the document's own
 * <h1>, lifts its "Last Updated" line out of the prose, and puts an id on every
 * <h2> so the contents list can link to it and support can send a customer
 * straight to a clause.
 */

$legal_key   = isset($legal_key) ? $legal_key : '';
$legal_title = isset($legal_title) ? $legal_title : 'Policy';
$prepared    = legal_page_prepare(isset($legal_body) ? $legal_body : '');

$legal_intro = [
    'terms'    => 'The agreement between you and Cretzo when you buy, sell or browse on the platform.',
    'privacy'  => 'What we collect, why we collect it, and the control you have over it.',
    'return'   => 'When an item can be returned, how refunds are issued, and how long each step takes.',
    'shipping' => 'How orders are packed, dispatched, tracked and delivered.',
];

/* The sibling documents. People arrive on the wrong one constantly - a customer
 * looking for the refund window opens Terms - so each page links to the other
 * three rather than dead-ending. */
$legal_siblings = [
    'terms'    => ['url' => 'terms-and-conditions', 'label' => 'Terms of Use',      'icon' => 'uil-file-alt'],
    'privacy'  => ['url' => 'privacy-policy',       'label' => 'Privacy Policy',    'icon' => 'uil-shield-check'],
    'return'   => ['url' => 'return-policy',        'label' => 'Returns & Refunds', 'icon' => 'uil-history-alt'],
    'shipping' => ['url' => 'shipping-policy',      'label' => 'Shipping Policy',   'icon' => 'uil-truck'],
];

$support_settings = get_settings('system_settings', true);
$support_email = isset($support_settings['support_email']) ? trim($support_settings['support_email']) : '';
?>

<div class="czlegal">

    <nav class="czlegal__crumbs" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a>
        <span aria-hidden="true">/</span>
        <span class="czlegal__crumbs-now"><?= html_escape($legal_title) ?></span>
    </nav>

    <header class="czlegal__hero">
        <div class="czlegal__hero-text">
            <?php // The ONE <h1> on the page. legal_page_prepare() removed the
                  // document's own, which used to sit right under this one. ?>
            <h1 class="czlegal__title"><?= html_escape($legal_title) ?></h1>
            <?php if (isset($legal_intro[$legal_key])) { ?>
                <p class="czlegal__intro"><?= $legal_intro[$legal_key] ?></p>
            <?php } ?>
            <div class="czlegal__meta">
                <?php if ($prepared['updated'] !== '') { ?>
                    <span class="czlegal__chip">
                        <i class="uil uil-history"></i> Last updated <?= html_escape($prepared['updated']) ?>
                    </span>
                <?php } ?>
                <?php if (!empty($prepared['toc'])) { ?>
                    <span class="czlegal__chip">
                        <i class="uil uil-list-ul"></i> <?= count($prepared['toc']) ?> sections
                    </span>
                <?php } ?>
                <?php // Reading the whole thing is the exception; knowing how long
                      // it is up front is what stops people bouncing off it. ?>
                <button type="button" class="czlegal__chip czlegal__chip--btn" id="czlegal-print">
                    <i class="uil uil-print"></i> Print or save as PDF
                </button>
            </div>
        </div>
    </header>

    <div class="czlegal__shell">

        <?php if (!empty($prepared['toc'])) { ?>
            <aside class="czlegal__aside" aria-label="On this page">
                <nav class="czlegal__toc" id="czlegal-toc">
                    <p class="czlegal__toc-label">On this page</p>
                    <ol class="czlegal__toc-list">
                        <?php foreach ($prepared['toc'] as $entry) { ?>
                            <li>
                                <a href="#<?= html_escape($entry['id']) ?>"><?= html_escape($entry['text']) ?></a>
                            </li>
                        <?php } ?>
                    </ol>
                </nav>

                <div class="czlegal__aside-links">
                    <p class="czlegal__toc-label">Related</p>
                    <?php foreach ($legal_siblings as $key => $sibling) {
                        if ($key === $legal_key) {
                            continue;
                        } ?>
                        <a class="czlegal__side-link" href="<?= base_url($sibling['url']) ?>">
                            <i class="uil <?= $sibling['icon'] ?>"></i> <?= $sibling['label'] ?>
                        </a>
                    <?php } ?>
                </div>
            </aside>
        <?php } ?>

        <main class="czlegal__main">
            <?php if (trim($prepared['html']) === '') { ?>
                <?php // An unconfigured document used to render as a blank page under
                      // a heading, which reads as a broken site rather than as
                      // missing content. ?>
                <div class="czlegal__empty">
                    <i class="uil uil-file-info-alt"></i>
                    <h2>This document is not published yet</h2>
                    <p>
                        We are still preparing our <?= html_escape(strtolower($legal_title)) ?>.
                        <?php if ($support_email !== '') { ?>
                            In the meantime, email <a href="mailto:<?= html_escape($support_email) ?>"><?= html_escape($support_email) ?></a>
                            and we will answer any question directly.
                        <?php } ?>
                    </p>
                </div>
            <?php } else { ?>
                <?php /* The stored document is admin-authored HTML and is printed as
                         HTML on purpose - that is the whole point of the WYSIWYG in
                         the admin panel. It is NOT user input. */ ?>
                <article class="czlegal__doc" id="czlegal-doc">
                    <?= $prepared['html'] ?>
                </article>

                <footer class="czlegal__footer">
                    <?php /* The prose is wrapped in ONE <span>. .czlegal__footer-note is a
                             flex row (icon beside text), and in a flex container each run of
                             loose text becomes its own anonymous flex item - so the sentence
                             was laid out as half a dozen items in a single non-wrapping row,
                             overflowing the card and giving the whole page a 25px horizontal
                             scroll on a phone. One element = one flex item = normal wrapping. */ ?>
                    <p class="czlegal__footer-note">
                        <i class="uil uil-info-circle"></i>
                        <span>
                            Questions about this document?
                            <?php if ($support_email !== '') { ?>
                                Email <a href="mailto:<?= html_escape($support_email) ?>"><?= html_escape($support_email) ?></a> or
                            <?php } ?>
                            <a href="<?= base_url('my-account/support') ?>">raise a support ticket</a> and a person will reply.
                        </span>
                    </p>

                    <div class="czlegal__footer-links">
                        <?php foreach ($legal_siblings as $key => $sibling) {
                            if ($key === $legal_key) {
                                continue;
                            } ?>
                            <a class="czlegal__pill" href="<?= base_url($sibling['url']) ?>">
                                <i class="uil <?= $sibling['icon'] ?>"></i> <?= $sibling['label'] ?>
                            </a>
                        <?php } ?>
                    </div>
                </footer>
            <?php } ?>
        </main>
    </div>

    <button type="button" class="czlegal__top" id="czlegal-top" hidden aria-label="Back to top">
        <i class="uil uil-arrow-up"></i>
    </button>
</div>
