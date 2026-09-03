<?php
/**
 * FAQ.
 *
 * Rebuilt onto its own `czfaq` design system, matching the czlegal policy pages
 * (same tokens, same shell: hero -> sidebar -> content -> help footer).
 *
 * What the old page did badly: every question was a Bootstrap accordion item in
 * a single flat column beside a decorative image, with no way to find anything.
 * This install has 17 live FAQs and the ones people actually arrive for
 * (returns, tracking, payment) sat below the fold with no search and no
 * grouping.
 *
 * The `faqs` table has only question/answer/status - there is no category
 * column - so topics are derived here by keyword. That is presentation only:
 * an unmatched question still renders, under "About Cretzo". If a category
 * column is ever added, replace $czfaq_topics with it and nothing else changes.
 *
 * The filtering and search are progressive enhancement (faq.js). With no JS
 * every group and every answer is still in the DOM and reachable; the accordion
 * uses <details>, which needs no script at all.
 */

$czfaq_rows = (isset($faq['data']) && is_array($faq['data'])) ? $faq['data'] : [];

/*
 * Topic buckets, in the order they are shown. The first bucket whose keywords
 * appear in the question wins, so the more specific ones are listed first -
 * "return policy" must land in Returns, not in Orders because it says "order".
 */
$czfaq_topics = [
    'returns'  => ['label' => 'Returns & Refunds', 'icon' => 'uil-history-alt', 'keywords' => ['return', 'refund', 'replace', 'exchange', 'damaged']],
    'orders'   => ['label' => 'Orders & Delivery', 'icon' => 'uil-box',         'keywords' => ['order', 'track', 'cancel', 'deliver', 'ship', 'cod', 'cash on delivery', 'international']],
    'payments' => ['label' => 'Payments',          'icon' => 'uil-credit-card', 'keywords' => ['payment', 'wallet', 'gateway', 'upi', 'card', 'razorpay', 'safe']],
    'selling'  => ['label' => 'Selling on Cretzo', 'icon' => 'uil-store',       'keywords' => ['seller', 'sell', 'vendor', 'commission', 'raw material']],
    'account'  => ['label' => 'Account & Support', 'icon' => 'uil-user-circle', 'keywords' => ['account', 'sign up', 'signup', 'login', 'password', 'support', 'contact', 'complaint']],
    'general'  => ['label' => 'About Cretzo',      'icon' => 'uil-info-circle', 'keywords' => []],
];

/* Bucket the rows. Ordering inside a bucket is the model's (id asc), which is
 * the order an admin entered them in - deliberately left alone. */
$czfaq_groups = [];
foreach ($czfaq_topics as $key => $topic) {
    $czfaq_groups[$key] = [];
}
foreach ($czfaq_rows as $row) {
    $haystack = ' ' . strtolower(strip_tags((string) $row['question'] . ' ' . (string) $row['answer'])) . ' ';
    $matched = 'general';
    foreach ($czfaq_topics as $key => $topic) {
        foreach ($topic['keywords'] as $needle) {
            if (strpos($haystack, $needle) !== false) {
                $matched = $key;
                break 2;
            }
        }
    }
    $czfaq_groups[$matched][] = $row;
}
/* Empty buckets must not print an empty heading or a filter that leads nowhere -
 * a store with three FAQs would otherwise show six sections. */
$czfaq_groups = array_filter($czfaq_groups, function ($rows) {
    return !empty($rows);
});

$czfaq_settings = get_settings('system_settings', true);
$czfaq_email    = isset($czfaq_settings['support_email']) ? trim($czfaq_settings['support_email']) : '';
$czfaq_whatsapp = function_exists('whatsapp_support_link') ? whatsapp_support_link() : '';
?>

<div class="czfaq">

    <nav class="czfaq__crumbs" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a>
        <span aria-hidden="true">/</span>
        <span class="czfaq__crumbs-now"><?= !empty($this->lang->line('faq')) ? $this->lang->line('faq') : 'FAQ' ?></span>
    </nav>

    <header class="czfaq__hero">
        <p class="czfaq__eyebrow"><i class="uil uil-comment-question"></i> Help Centre</p>
        <h1 class="czfaq__title">How can we help?</h1>
        <p class="czfaq__intro">Answers to the questions we are asked most - about orders, delivery, returns, payments and selling on Cretzo.</p>

        <?php if (!empty($czfaq_groups)) { ?>
            <div class="czfaq__search">
                <i class="uil uil-search" aria-hidden="true"></i>
                <?php /* type="search" gives mobile keyboards the right action key; the
                         JS reads the value either way. */ ?>
                <input type="search" id="czfaq-search" class="czfaq__search-input"
                       placeholder="Search a question, e.g. track my order"
                       autocomplete="off" aria-label="Search the FAQs">
                <button type="button" class="czfaq__search-clear" id="czfaq-search-clear" hidden aria-label="Clear search">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <p class="czfaq__count" id="czfaq-count" role="status" aria-live="polite"></p>
        <?php } ?>
    </header>

    <?php if (empty($czfaq_groups)) { ?>

        <div class="czfaq__empty">
            <i class="uil uil-comment-question"></i>
            <h2><?= !empty($this->lang->line('no_faqs_found')) ? $this->lang->line('no_faqs_found') : 'No FAQs Found.' ?></h2>
            <p>We have not published any answers yet. Our team is happy to help you directly.</p>
            <a class="czfaq__btn czfaq__btn--primary" href="<?= base_url('contact-us') ?>">
                <i class="uil uil-envelope"></i> Contact support
            </a>
        </div>

    <?php } else { ?>

        <div class="czfaq__shell">

            <aside class="czfaq__aside" aria-label="Topics">
                <nav class="czfaq__topics" id="czfaq-topics">
                    <p class="czfaq__aside-label">Topics</p>
                    <button type="button" class="czfaq__topic is-active" data-topic="all">
                        <span><i class="uil uil-apps"></i> All questions</span>
                        <span class="czfaq__topic-n"><?= count($czfaq_rows) ?></span>
                    </button>
                    <?php foreach ($czfaq_groups as $key => $rows) { ?>
                        <button type="button" class="czfaq__topic" data-topic="<?= html_escape($key) ?>">
                            <span><i class="uil <?= html_escape($czfaq_topics[$key]['icon']) ?>"></i> <?= html_escape($czfaq_topics[$key]['label']) ?></span>
                            <span class="czfaq__topic-n"><?= count($rows) ?></span>
                        </button>
                    <?php } ?>
                </nav>

                <div class="czfaq__aside-links">
                    <p class="czfaq__aside-label">Policies</p>
                    <a class="czfaq__side-link" href="<?= base_url('return-policy') ?>"><i class="uil uil-history-alt"></i> Returns &amp; Refunds</a>
                    <a class="czfaq__side-link" href="<?= base_url('shipping-policy') ?>"><i class="uil uil-truck"></i> Shipping Policy</a>
                    <a class="czfaq__side-link" href="<?= base_url('terms-and-conditions') ?>"><i class="uil uil-file-alt"></i> Terms of Use</a>
                    <a class="czfaq__side-link" href="<?= base_url('privacy-policy') ?>"><i class="uil uil-shield-check"></i> Privacy Policy</a>
                </div>
            </aside>

            <main class="czfaq__main">

                <?php /* No result for a search term is a dead end unless the page says
                         so and offers the next step. Hidden until the JS needs it. */ ?>
                <div class="czfaq__noresult" id="czfaq-noresult" hidden>
                    <i class="uil uil-search"></i>
                    <h2>No answer matched <span id="czfaq-noresult-term"></span></h2>
                    <p>Try a shorter word, or ask us directly - we usually reply the same day.</p>
                    <div class="czfaq__noresult-actions">
                        <a class="czfaq__btn czfaq__btn--primary" href="<?= base_url('contact-us') ?>"><i class="uil uil-envelope"></i> Ask support</a>
                        <?php if ($czfaq_whatsapp !== '') { ?>
                            <a class="czfaq__btn czfaq__btn--wa" href="<?= html_escape($czfaq_whatsapp) ?>" target="_blank" rel="noopener"><i class="uil uil-whatsapp"></i> WhatsApp</a>
                        <?php } ?>
                    </div>
                </div>

                <?php foreach ($czfaq_groups as $key => $rows) { ?>
                    <section class="czfaq__group" data-topic="<?= html_escape($key) ?>" id="topic-<?= html_escape($key) ?>">
                        <h2 class="czfaq__group-title">
                            <i class="uil <?= html_escape($czfaq_topics[$key]['icon']) ?>"></i>
                            <?= html_escape($czfaq_topics[$key]['label']) ?>
                        </h2>

                        <div class="czfaq__list">
                            <?php foreach ($rows as $row) { ?>
                                <?php /* <details>/<summary>: opens and closes with zero JS, and
                                         is what find-in-page and screen readers already
                                         understand. The old markup needed Bootstrap collapse. */ ?>
                                <details class="czfaq__item" id="faq-<?= (int) $row['id'] ?>">
                                    <summary class="czfaq__q">
                                        <span class="czfaq__q-text"><?= html_escape($row['question']) ?></span>
                                        <span class="czfaq__q-mark" aria-hidden="true"><i class="uil uil-angle-down"></i></span>
                                    </summary>
                                    <div class="czfaq__a">
                                        <?php /* Answers are admin-authored plain text. nl2br over an
                                                 escaped string keeps the paragraph breaks an admin
                                                 typed without opening the field up to HTML. */ ?>
                                        <p><?= nl2br(html_escape($row['answer'])) ?></p>
                                    </div>
                                </details>
                            <?php } ?>
                        </div>
                    </section>
                <?php } ?>

                <section class="czfaq__help">
                    <div class="czfaq__help-text">
                        <h2>Still stuck?</h2>
                        <p>If your question is not here, reach us directly - having your order number to hand makes it quicker.</p>
                    </div>
                    <div class="czfaq__help-actions">
                        <a class="czfaq__btn czfaq__btn--primary" href="<?= base_url('contact-us') ?>"><i class="uil uil-envelope"></i> Contact us</a>
                        <?php if ($czfaq_whatsapp !== '') { ?>
                            <a class="czfaq__btn czfaq__btn--wa" href="<?= html_escape($czfaq_whatsapp) ?>" target="_blank" rel="noopener"><i class="uil uil-whatsapp"></i> WhatsApp</a>
                        <?php } ?>
                        <?php if ($czfaq_email !== '') { ?>
                            <a class="czfaq__btn czfaq__btn--ghost" href="mailto:<?= html_escape($czfaq_email) ?>"><i class="uil uil-at"></i> <?= html_escape($czfaq_email) ?></a>
                        <?php } ?>
                    </div>
                </section>

            </main>
        </div>

    <?php } ?>
</div>
