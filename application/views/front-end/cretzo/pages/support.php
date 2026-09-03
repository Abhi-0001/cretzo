<?php
/*
 * Customer-facing support tickets.
 *
 * The platform's ticket system was admin-panel + mobile-API only: a website customer had no
 * way to raise a ticket, read a reply, or see a status. "Chat with us" is a Coming Soon
 * placeholder and the floating widget is a scripted FAQ bot, so the only human channel on the
 * web was WhatsApp. This page is the missing customer half - it drives the same tickets /
 * ticket_messages tables, the same PENDING..REOPEN statuses and the same notification triggers
 * the admin side already uses.
 */
$has_types = !empty($ticket_types);
// A ticket is the written trail; WhatsApp is the fast lane. Offer both, and keep offering
// WhatsApp when tickets cannot be raised at all (no categories configured).
$support_whatsapp = whatsapp_support_link('Hello Cretzo Support, I need help with my order.');

/* --------------------------------------------------------------- content --
 *
 * Rebuilt on the shared account shell (partials/account-layout.php). The list
 * view, the thread view and all of the JS below are unchanged in behaviour; the
 * "Raise a new ticket" form moved from a third in-page view into a popup, and
 * the cs-support__* styles now read from the account design system's tokens so
 * this page stops looking like a different product from the rest of My Account.
 */
ob_start(); ?>
            <div class="cs-support" id="cs-support"
                 data-open-ticket="<?= (int) $open_ticket_id ?>"
                 data-list-url="<?= base_url('my-account/get-my-tickets') ?>"
                 data-thread-url="<?= base_url('my-account/get-ticket-thread') ?>"
                 data-create-url="<?= base_url('my-account/create-ticket') ?>"
                 data-reply-url="<?= base_url('my-account/reply-ticket') ?>"
                 data-status-url="<?= base_url('my-account/update-ticket-status') ?>">

                <!-- ============================= LIST VIEW ============================= -->
                <div id="cs-list-view">
                    <div class="cs-support__bar">
                        <div class="cs-support__filters">
                            <select id="cs-status-filter" class="cs-support__select" aria-label="Filter by status">
                                <option value="">All statuses</option>
                                <?php foreach ($status_labels as $value => $meta) { ?>
                                    <option value="<?= html_escape($value) ?>"><?= html_escape($meta['label']) ?></option>
                                <?php } ?>
                            </select>
                            <input type="text" id="cs-search" class="cs-support__search" placeholder="Search your tickets...">
                        </div>
                    </div>

                    <?php if (!$has_types) { ?>
                        <div class="cs-support__notice">
                            Support categories have not been set up yet, so new tickets cannot be raised right now.
                            <?php if (!empty($support_whatsapp)) { ?>
                                Please <a href="<?= html_escape($support_whatsapp) ?>" target="_blank" rel="noopener">message us on WhatsApp</a>
                            <?php } ?>
                            <?php if (!empty($support_email)) { ?>
                                <?= !empty($support_whatsapp) ? 'or email' : 'Please email' ?> <a href="mailto:<?= html_escape($support_email) ?>"><?= html_escape($support_email) ?></a>
                            <?php } ?>
                            in the meantime.
                        </div>
                    <?php } ?>

                    <div id="cs-ticket-list" class="cs-support__list" aria-live="polite">
                        <div class="cs-support__loading">Loading your tickets...</div>
                    </div>

                    <div class="cs-support__pager" id="cs-pager" hidden>
                        <button type="button" class="cs-support__ghost" id="cs-prev">Previous</button>
                        <span class="cs-support__pageinfo" id="cs-pageinfo"></span>
                        <button type="button" class="cs-support__ghost" id="cs-next">Next</button>
                    </div>
                </div>

                <!-- ============================= THREAD ============================= -->
                <div id="cs-thread-view" hidden>
                    <button type="button" class="cs-support__back" data-back>&larr; Back to my tickets</button>

                    <div class="cs-support__threadhead">
                        <div>
                            <h2 class="cs-support__h2" id="cs-thread-subject"></h2>
                            <p class="cs-support__meta" id="cs-thread-meta"></p>
                        </div>
                        <span class="cs-support__badge" id="cs-thread-status"></span>
                    </div>

                    <div class="cs-support__messages" id="cs-thread-messages" aria-live="polite"></div>

                    <form id="cs-reply-form" class="cs-support__replyform" enctype="multipart/form-data">
                        <input type="hidden" name="ticket_id" id="cs-reply-ticket-id" value="">
                        <textarea name="message" id="cs-reply-message" class="cs-support__textarea" rows="3"
                                  maxlength="5000" placeholder="Write a reply..."></textarea>
                        <div class="cs-support__replyrow">
                            <label class="cs-support__attach">
                                <i class="uil uil-paperclip"></i>
                                <span id="cs-attach-label">Attach files</span>
                                <input type="file" name="attachments[]" id="cs-attachments" multiple
                                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt" hidden>
                            </label>
                            <div class="cs-support__replybtns">
                                <button type="button" class="cs-support__ghost" id="cs-resolve-btn" hidden>Mark resolved</button>
                                <button type="button" class="cs-support__ghost" id="cs-reopen-btn" hidden>Reopen ticket</button>
                                <button type="submit" class="cs-support__primary" id="cs-reply-submit">Send reply</button>
                            </div>
                        </div>
                        <p class="cs-support__hint">Up to 3 files, 5 MB each (images, PDF, DOC, TXT).</p>
                        <p class="cs-support__error" id="cs-reply-error" hidden></p>
                    </form>

                    <div class="cs-support__notice" id="cs-closed-notice" hidden>
                        This ticket is closed. Reopen it or raise a new ticket if you still need help.
                    </div>
                </div>

            </div>

<?php $page_content = ob_get_clean();

/* --------------------------------------------------------------- actions -- */
ob_start(); ?>
<?php if (!empty($support_whatsapp)) { ?>
    <a class="czap-btn czap-btn--wa" href="<?= html_escape($support_whatsapp) ?>" target="_blank" rel="noopener">
        <i class="uil uil-whatsapp"></i> WhatsApp
    </a>
<?php } ?>
<button type="button" class="czap-btn czap-btn--primary" id="cs-new-btn" <?= $has_types ? '' : 'disabled' ?>>
    <i class="uil uil-plus"></i> New ticket
</button>
<?php $page_actions = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Support tickets',
    'page_sub'     => 'Raise a ticket and track our replies',
    'page_icon'    => 'uil-ticket',
    'page_actions' => $page_actions,
    'page_content' => $page_content,
]);
?>

<!-- ====================== POPUP: raise a new ticket ====================== -->
<div class="czap-modal czap-modal--lg" id="czap-ticket-modal" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="czap-ticket-modal-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">

        <?php /* Same #cs-new-form the script below already binds - only its container
                 changed, from a third full-page view to this popup. */ ?>
        <form id="cs-new-form" class="cs-support__form" novalidate>

            <div class="czap-modal__head">
                <div>
                    <h2 class="czap-modal__title" id="czap-ticket-modal-title">
                        <i class="uil uil-ticket"></i> Raise a new ticket
                    </h2>
                    <p class="czap-modal__sub">We reply by email and in your notifications.</p>
                </div>
                <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
            </div>

            <div class="czap-modal__body">
                        <label class="cs-support__label" for="cs-type">What is this about?</label>
                        <select id="cs-type" name="ticket_type_id" class="cs-support__select cs-support__select--block" required>
                            <option value="">Select a category</option>
                            <?php foreach ($ticket_types as $type) { ?>
                                <option value="<?= (int) $type['id'] ?>"><?= html_escape($type['title']) ?></option>
                            <?php } ?>
                        </select>

                        <label class="cs-support__label" for="cs-subject">Subject</label>
                        <input type="text" id="cs-subject" name="subject" class="cs-support__input" maxlength="190"
                               placeholder="Short summary, e.g. Order #123 not delivered" required>

                        <label class="cs-support__label" for="cs-description">Describe the issue</label>
                        <textarea id="cs-description" name="description" class="cs-support__textarea" rows="6" maxlength="5000"
                                  placeholder="Include the order id and anything that helps us find it faster." required></textarea>

                <p class="cs-support__error" id="cs-new-error" hidden></p>
            </div>

            <div class="czap-modal__foot">
                <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Cancel</button>
                <button type="submit" class="czap-btn czap-btn--primary" id="cs-create-submit">
                    <i class="uil uil-check"></i> Create ticket
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /*
     * These class names are the ones the script below generates, so they stay -
     * but the values are the account design system's tokens (account-suite.css)
     * rather than a second, slightly-different palette. Before this the support
     * page had its own greys, its own radii and its own orange gradient, so it
     * read as a different product from the page it sits inside.
     */
    .cs-support__bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .cs-support__filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        flex: 1 1 260px;
    }

    .cs-support__select,
    .cs-support__search,
    .cs-support__input,
    .cs-support__textarea {
        min-height: 46px;
        padding: 11px 14px;
        border: 1px solid var(--czap-line);
        border-radius: var(--czap-r-sm);
        background: #fff;
        font: inherit;
        font-size: 15px;
        color: var(--czap-ink);
        outline: none;
    }

    .cs-support__search { flex: 1 1 180px; }

    .cs-support__select--block,
    .cs-support__input,
    .cs-support__textarea {
        width: 100%;
        display: block;
    }

    .cs-support__select:focus,
    .cs-support__search:focus,
    .cs-support__input:focus,
    .cs-support__textarea:focus {
        border-color: var(--czap-orange);
        box-shadow: 0 0 0 3px rgba(242, 130, 46, .16);
    }

    .cs-support__textarea {
        resize: vertical;
        font-family: inherit;
        line-height: 1.6;
        min-height: 120px;
    }

    /* Same caret the account design system draws on .czap-select, so the two
       kinds of dropdown on an account page look like one control. */
    .cs-support__select {
        -webkit-appearance: none;
        appearance: none;
        padding-right: 40px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%238b919e' d='M1.4 0 6 4.6 10.6 0 12 1.4 6 7.4 0 1.4z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
    }

    /* .cs-support__primary / __ghost are generated by the script for the reply
       and status buttons; aliased to the shared button shape so there is one
       button on the page rather than two families of them. */
    .cs-support__primary,
    .cs-support__ghost {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 20px;
        border: 1px solid transparent;
        border-radius: var(--czap-r-pill);
        font: inherit;
        font-size: 14.5px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        white-space: nowrap;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    .cs-support__primary {
        background: linear-gradient(180deg, #f78e3c 0%, var(--czap-orange) 100%);
        color: #fff;
        box-shadow: 0 8px 18px -10px rgba(242, 130, 46, .85);
    }

    .cs-support__primary:hover:not(:disabled) { background: var(--czap-orange-dark); }

    .cs-support__ghost {
        background: #fff;
        border-color: var(--czap-line);
        color: var(--czap-ink);
    }

    .cs-support__ghost:hover:not(:disabled),
    .cs-support__ghost:focus-visible:not(:disabled) {
        border-color: var(--czap-orange);
        background: var(--czap-orange-soft);
        color: var(--czap-orange-dark);
        outline: none;
    }

    .cs-support__primary:disabled,
    .cs-support__ghost:disabled {
        opacity: .55;
        cursor: not-allowed;
        box-shadow: none;
    }


    .cs-support__back {
        border: 0;
        background: none;
        padding: 0 0 14px;
        color: var(--czap-orange-dark);
        cursor: pointer;
        font: inherit;
        font-size: 14px;
        font-weight: 600;
    }

    .cs-support__h2 { font-size: 20px; font-weight: 700; color: var(--czap-ink); margin: 0 0 6px; }

    .cs-support__label {
        display: block;
        margin: 16px 0 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--czap-ink-2);
    }

    .cs-support__label:first-child { margin-top: 0; }

    .cs-support__form { max-width: none; }
    .cs-support__actions { display: flex; gap: 10px; margin-top: 20px; }
    .cs-support__list { display: flex; flex-direction: column; gap: 12px; }

    .cs-support__loading,
    .cs-support__empty {
        padding: 44px 10px;
        text-align: center;
        color: var(--czap-ink-3);
        font-size: 14.5px;
    }

    /* Ticket row - same shape as .czap-item elsewhere in the account. */
    .cs-support__card {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        justify-content: space-between;
        padding: 16px 18px;
        border: 1px solid var(--czap-line);
        border-radius: var(--czap-r);
        background: #fff;
        cursor: pointer;
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .cs-support__card:hover,
    .cs-support__card:focus-visible {
        border-color: var(--czap-orange-line);
        box-shadow: var(--czap-shadow);
        outline: none;
    }

    .cs-support__cardmain { min-width: 0; }

    .cs-support__cardtitle {
        font-size: 15.5px;
        font-weight: 600;
        color: var(--czap-ink);
        margin: 0 0 4px;
        word-break: break-word;
    }

    .cs-support__meta { font-size: 13px; color: var(--czap-ink-3); margin: 0; }

    .cs-support__cardside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        flex-shrink: 0;
    }

    .cs-support__badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: var(--czap-r-pill);
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    /* status_class comes from the controller: secondary | info | success | danger | warning */
    .cs-support__badge--secondary { background: var(--czap-line-2); color: var(--czap-ink-2); }
    .cs-support__badge--info { background: var(--czap-info-soft); color: var(--czap-info); }
    .cs-support__badge--success { background: var(--czap-ok-soft); color: var(--czap-ok); }
    .cs-support__badge--danger { background: var(--czap-bad-soft); color: var(--czap-bad); }
    .cs-support__badge--warning { background: var(--czap-warn-soft); color: var(--czap-warn); }

    .cs-support__unread {
        background: var(--czap-orange);
        color: #fff;
        border-radius: var(--czap-r-pill);
        font-size: 11px;
        font-weight: 700;
        padding: 3px 9px;
        white-space: nowrap;
    }

    .cs-support__pager {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        margin-top: 22px;
    }

    .cs-support__pageinfo { font-size: 13px; color: var(--czap-ink-3); }

    .cs-support__threadhead {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        justify-content: space-between;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--czap-line-2);
    }

    .cs-support__messages {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 20px 0;
        max-height: 460px;
        overflow-y: auto;
    }

    .cs-support__msg {
        max-width: 78%;
        padding: 13px 15px;
        border-radius: var(--czap-r);
        font-size: 14.5px;
        line-height: 1.6;
        word-break: break-word;
    }

    /* The customer's own messages sit right and warm; ours sit left and neutral -
       side AND colour, so the two are distinguishable either way. */
    .cs-support__msg--mine {
        margin-left: auto;
        background: var(--czap-orange-soft);
        border: 1px solid var(--czap-orange-line);
        color: #40301c;
    }

    .cs-support__msg--support {
        background: #f7f8fa;
        border: 1px solid var(--czap-line);
        color: var(--czap-ink);
    }

    .cs-support__msgauthor {
        display: block;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        opacity: .6;
        margin-bottom: 4px;
    }

    .cs-support__msgtime { display: block; font-size: 11.5px; opacity: .55; margin-top: 6px; }

    .cs-support__files { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .cs-support__files img { max-width: 130px; max-height: 130px; border-radius: var(--czap-r-sm); display: block; }
    .cs-support__filelink { font-size: 12.5px; color: var(--czap-orange-dark); text-decoration: underline; }

    .cs-support__replyform { border-top: 1px solid var(--czap-line-2); padding-top: 18px; }

    .cs-support__replyrow {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
    }

    .cs-support__replybtns { display: flex; gap: 10px; flex-wrap: wrap; }

    .cs-support__attach {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: var(--czap-ink-2);
        padding: 9px 16px;
        border: 1px solid var(--czap-line);
        border-radius: var(--czap-r-pill);
    }

    .cs-support__attach:hover { border-color: var(--czap-orange); color: var(--czap-orange-dark); }

    .cs-support__hint { font-size: 12.5px; color: var(--czap-ink-3); margin: 10px 0 0; }
    .cs-support__error { color: var(--czap-bad); font-size: 13.5px; margin: 12px 0 0; font-weight: 600; }

    .cs-support__notice {
        padding: 13px 16px;
        border-radius: var(--czap-r-sm);
        background: var(--czap-warn-soft);
        color: #8d5c0f;
        font-size: 14px;
        line-height: 1.55;
        margin: 14px 0;
    }

    .cs-support__notice a { color: inherit; font-weight: 700; }

    @media (max-width: 575px) {
        .cs-support__msg { max-width: 92%; }
        .cs-support__card { flex-direction: column; }
        .cs-support__cardside { align-items: flex-start; flex-direction: row; }
        .cs-support__replybtns { width: 100%; }
        .cs-support__replybtns .cs-support__primary,
        .cs-support__replybtns .cs-support__ghost { flex: 1 1 auto; }
    }
</style>

<script>
(function () {
    'use strict';

    var root = document.getElementById('cs-support');
    if (!root) { return; }

    var urls = {
        list:   root.dataset.listUrl,
        thread: root.dataset.threadUrl,
        create: root.dataset.createUrl,
        reply:  root.dataset.replyUrl,
        status: root.dataset.statusUrl
    };

    var views = {
        list:   document.getElementById('cs-list-view'),
        create: document.getElementById('czap-ticket-modal'),
        thread: document.getElementById('cs-thread-view')
    };

    var PAGE_SIZE = 10;
    var state = { offset: 0, total: 0, ticketId: 0, searchTimer: null };

    /* csrf-guard.js patches fetch() to append the token to same-origin non-GET requests, so
     * these calls do not have to carry it by hand - but read the live value anyway for the
     * FormData path, and adopt any rotated hash the server hands back. */
    function csrfPair() {
        if (window.CSRF && typeof window.CSRF.name === 'function') {
            return { name: window.CSRF.name(), hash: window.CSRF.hash() };
        }
        var nameEl = document.querySelector('meta[name="csrf-token-name"]');
        var hashEl = document.querySelector('meta[name="csrf-token-hash"]');
        return {
            name: nameEl ? nameEl.getAttribute('content') : '',
            hash: hashEl ? hashEl.getAttribute('content') : ''
        };
    }

    function adoptCsrf(json) {
        if (json && json.csrfHash && window.CSRF && typeof window.CSRF.update === 'function') {
            window.CSRF.update(json.csrfName, json.csrfHash);
        }
    }

    function post(url, body) {
        var pair = csrfPair();
        if (body instanceof FormData) {
            if (pair.name && !body.has(pair.name)) { body.append(pair.name, pair.hash); }
        } else {
            if (pair.name && !body.has(pair.name)) { body.append(pair.name, pair.hash); }
        }
        return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (json) { adoptCsrf(json); return json; });
    }

    function get(url) {
        return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (json) { adoptCsrf(json); return json; });
    }

    /*
     * 'create' is a POPUP now, not a third in-page view - so it is layered over
     * the list rather than replacing it, and every other show() call closes it.
     * views.create is the <form>'s popup container; keeping the same three keys
     * means the rest of the script did not have to change.
     */
    /*
     * window.CzAccount comes from account-suite.js, which include-script.php emits in the
     * page FOOTER - i.e. AFTER this inline script has already run. Calling it unguarded
     * threw "CzAccount is undefined" out of show(), and because show('thread') is the
     * FIRST thing openThread() does, the throw happened before the list was hidden and
     * before loadList() could run: the ?ticket_id= deep link from a ticket notification
     * left the page frozen on its static "Loading your tickets..." placeholder forever.
     * (Clicking a ticket by hand was fine - by then the footer script had loaded.)
     * The popup is optional chrome, so a missing controller must not break the views.
     */
    function modal(action) {
        if (window.CzAccount && typeof window.CzAccount[action] === 'function') {
            window.CzAccount[action]('#czap-ticket-modal');
            return true;
        }
        return false;
    }

    function show(which) {
        if (which === 'create') {
            if (views.list) { views.list.hidden = false; }
            if (views.thread) { views.thread.hidden = true; }
            if (!modal('open') && views.create) {
                // Bare fallback so "New ticket" still does something without the controller.
                views.create.hidden = false;
                views.create.setAttribute('aria-hidden', 'false');
            }
            return;
        }
        if (!modal('close') && views.create) {
            views.create.hidden = true;
            views.create.setAttribute('aria-hidden', 'true');
        }
        if (views.list) { views.list.hidden = (which !== 'list'); }
        if (views.thread) { views.thread.hidden = (which !== 'thread'); }
    }

    function esc(value) {
        var d = document.createElement('div');
        d.textContent = value == null ? '' : String(value);
        return d.innerHTML;
    }

    /* Server sends already-HTML-escaped strings (html_escape), so decode once for textContent
     * use rather than double-escaping entities into the visible text. */
    function decode(value) {
        var d = document.createElement('textarea');
        d.innerHTML = value == null ? '' : String(value);
        return d.value;
    }

    function fmtDate(value) {
        if (!value) { return ''; }
        var d = new Date(String(value).replace(' ', 'T'));
        if (isNaN(d.getTime())) { return value; }
        return d.toLocaleString();
    }

    function setError(el, message) {
        if (!el) { return; }
        if (message) {
            el.textContent = message;
            el.hidden = false;
        } else {
            el.textContent = '';
            el.hidden = true;
        }
    }

    /* ------------------------------------------------------------------ list */
    function loadList() {
        var listEl = document.getElementById('cs-ticket-list');
        var status = document.getElementById('cs-status-filter').value;
        var search = document.getElementById('cs-search').value.trim();

        listEl.innerHTML = '<div class="cs-support__loading">Loading your tickets...</div>';

        var qs = '?limit=' + PAGE_SIZE + '&offset=' + state.offset;
        if (status) { qs += '&status=' + encodeURIComponent(status); }
        if (search) { qs += '&search=' + encodeURIComponent(search); }

        get(urls.list + qs).then(function (json) {
            if (json.error) {
                listEl.innerHTML = '<div class="cs-support__empty">' + esc(json.message || 'Could not load your tickets.') + '</div>';
                return;
            }
            state.total = json.total || 0;
            renderList(json.rows || []);
            renderPager();
        }).catch(function () {
            listEl.innerHTML = '<div class="cs-support__empty">Could not load your tickets. Please refresh.</div>';
        });
    }

    function renderList(rows) {
        var listEl = document.getElementById('cs-ticket-list');
        if (!rows.length) {
            listEl.innerHTML = '<div class="cs-support__empty">You have not raised any tickets yet.</div>';
            return;
        }
        listEl.innerHTML = rows.map(function (row) {
            var unread = row.unread > 0
                ? '<span class="cs-support__unread">' + row.unread + ' new</span>'
                : '';
            return '<div class="cs-support__card" role="button" tabindex="0" data-ticket-id="' + row.id + '">'
                 +   '<div class="cs-support__cardmain">'
                 +     '<p class="cs-support__cardtitle">#' + row.id + ' &middot; ' + esc(decode(row.subject)) + '</p>'
                 +     '<p class="cs-support__meta">' + esc(decode(row.ticket_type) || 'Support') + ' &middot; '
                 +       row.replies + ' message' + (row.replies === 1 ? '' : 's')
                 +       ' &middot; updated ' + esc(fmtDate(row.last_updated)) + '</p>'
                 +   '</div>'
                 +   '<div class="cs-support__cardside">'
                 +     '<span class="cs-support__badge cs-support__badge--' + esc(row.status_class) + '">' + esc(row.status_label) + '</span>'
                 +     unread
                 +   '</div>'
                 + '</div>';
        }).join('');
    }

    function renderPager() {
        var pager = document.getElementById('cs-pager');
        var info = document.getElementById('cs-pageinfo');
        if (state.total <= PAGE_SIZE) {
            pager.hidden = true;
            return;
        }
        pager.hidden = false;
        var from = state.total === 0 ? 0 : state.offset + 1;
        var to = Math.min(state.offset + PAGE_SIZE, state.total);
        info.textContent = from + '-' + to + ' of ' + state.total;
        document.getElementById('cs-prev').disabled = state.offset <= 0;
        document.getElementById('cs-next').disabled = (state.offset + PAGE_SIZE) >= state.total;
    }

    /* ------------------------------------------------------------------ thread */
    function openThread(ticketId) {
        state.ticketId = ticketId;
        show('thread');
        setError(document.getElementById('cs-reply-error'), '');
        document.getElementById('cs-thread-messages').innerHTML = '<div class="cs-support__loading">Loading conversation...</div>';

        get(urls.thread + '?ticket_id=' + encodeURIComponent(ticketId)).then(function (json) {
            if (json.error || !json.ticket) {
                document.getElementById('cs-thread-messages').innerHTML =
                    '<div class="cs-support__empty">' + esc(json.message || 'Ticket not found.') + '</div>';
                return;
            }
            renderThread(json.ticket, json.data || []);
        }).catch(function () {
            document.getElementById('cs-thread-messages').innerHTML =
                '<div class="cs-support__empty">Could not load the conversation. Please refresh.</div>';
        });
    }

    function renderThread(ticket, messages) {
        document.getElementById('cs-thread-subject').textContent = '#' + ticket.id + ' · ' + decode(ticket.subject);
        document.getElementById('cs-thread-meta').textContent =
            (decode(ticket.ticket_type) || 'Support') + ' · raised ' + fmtDate(ticket.date_created);

        var badge = document.getElementById('cs-thread-status');
        badge.className = 'cs-support__badge cs-support__badge--' + ticket.status_class;
        badge.textContent = ticket.status_label;

        document.getElementById('cs-reply-ticket-id').value = ticket.id;

        // The opening description is the first thing the customer wrote; show it as message 1
        // so a brand-new ticket is not an empty thread.
        var blocks = [renderMsg({
            from_support: false,
            author: 'You',
            message: ticket.description,
            attachments: [],
            date_created: ticket.date_created
        })];

        messages.forEach(function (msg) { blocks.push(renderMsg(msg)); });

        var box = document.getElementById('cs-thread-messages');
        box.innerHTML = blocks.join('');
        box.scrollTop = box.scrollHeight;

        document.getElementById('cs-reply-form').hidden = !ticket.can_reply;
        document.getElementById('cs-closed-notice').hidden = ticket.can_reply;
        document.getElementById('cs-resolve-btn').hidden = !ticket.can_close;
        document.getElementById('cs-reopen-btn').hidden = !ticket.can_reopen;
    }

    function renderMsg(msg) {
        var side = msg.from_support ? 'support' : 'mine';
        var files = (msg.attachments || []).map(function (file) {
            if (file.type === 'image') {
                return '<a href="' + esc(file.url) + '" target="_blank" rel="noopener">'
                     + '<img src="' + esc(file.url) + '" alt="' + esc(file.name) + '"></a>';
            }
            return '<a class="cs-support__filelink" href="' + esc(file.url) + '" target="_blank" rel="noopener">'
                 + esc(file.name) + '</a>';
        }).join('');

        return '<div class="cs-support__msg cs-support__msg--' + side + '">'
             +   '<span class="cs-support__msgauthor">' + esc(decode(msg.author)) + '</span>'
             +   esc(decode(msg.message)).replace(/\n/g, '<br>')
             +   (files ? '<div class="cs-support__files">' + files + '</div>' : '')
             +   '<span class="cs-support__msgtime">' + esc(fmtDate(msg.date_created)) + '</span>'
             + '</div>';
    }

    /* ------------------------------------------------------------------ wiring */
    document.getElementById('cs-new-btn').addEventListener('click', function () {
        setError(document.getElementById('cs-new-error'), '');
        document.getElementById('cs-new-form').reset();
        show('create');
    });

    document.querySelectorAll('[data-back]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            show('list');
            loadList();
        });
    });

    document.getElementById('cs-ticket-list').addEventListener('click', function (e) {
        var card = e.target.closest('[data-ticket-id]');
        if (card) { openThread(parseInt(card.dataset.ticketId, 10)); }
    });

    document.getElementById('cs-ticket-list').addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') { return; }
        var card = e.target.closest('[data-ticket-id]');
        if (card) {
            e.preventDefault();
            openThread(parseInt(card.dataset.ticketId, 10));
        }
    });

    document.getElementById('cs-status-filter').addEventListener('change', function () {
        state.offset = 0;
        loadList();
    });

    document.getElementById('cs-search').addEventListener('input', function () {
        clearTimeout(state.searchTimer);
        state.searchTimer = setTimeout(function () {
            state.offset = 0;
            loadList();
        }, 350);
    });

    document.getElementById('cs-prev').addEventListener('click', function () {
        state.offset = Math.max(0, state.offset - PAGE_SIZE);
        loadList();
    });

    document.getElementById('cs-next').addEventListener('click', function () {
        if (state.offset + PAGE_SIZE < state.total) {
            state.offset += PAGE_SIZE;
            loadList();
        }
    });

    document.getElementById('cs-new-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var errEl = document.getElementById('cs-new-error');
        var btn = document.getElementById('cs-create-submit');
        setError(errEl, '');

        var body = new FormData();
        body.append('ticket_type_id', document.getElementById('cs-type').value);
        body.append('subject', document.getElementById('cs-subject').value);
        body.append('description', document.getElementById('cs-description').value);

        btn.disabled = true;
        post(urls.create, body).then(function (json) {
            btn.disabled = false;
            if (json.error) {
                setError(errEl, json.message || 'Could not create the ticket.');
                return;
            }
            openThread(json.ticket_id);
        }).catch(function () {
            btn.disabled = false;
            setError(errEl, 'Could not create the ticket. Please try again.');
        });
    });

    document.getElementById('cs-attachments').addEventListener('change', function () {
        var label = document.getElementById('cs-attach-label');
        label.textContent = this.files.length
            ? this.files.length + ' file' + (this.files.length === 1 ? '' : 's') + ' selected'
            : 'Attach files';
    });

    document.getElementById('cs-reply-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var errEl = document.getElementById('cs-reply-error');
        var btn = document.getElementById('cs-reply-submit');
        var textarea = document.getElementById('cs-reply-message');
        var fileInput = document.getElementById('cs-attachments');
        setError(errEl, '');

        if (!textarea.value.trim() && fileInput.files.length === 0) {
            setError(errEl, 'Please type a message or attach a file.');
            return;
        }

        var body = new FormData();
        body.append('ticket_id', document.getElementById('cs-reply-ticket-id').value);
        body.append('message', textarea.value);
        for (var i = 0; i < fileInput.files.length; i++) {
            body.append('attachments[]', fileInput.files[i]);
        }

        btn.disabled = true;
        post(urls.reply, body).then(function (json) {
            btn.disabled = false;
            if (json.error) {
                setError(errEl, json.message || 'Could not send your reply.');
                return;
            }
            textarea.value = '';
            fileInput.value = '';
            document.getElementById('cs-attach-label').textContent = 'Attach files';
            openThread(state.ticketId);
        }).catch(function () {
            btn.disabled = false;
            setError(errEl, 'Could not send your reply. Please try again.');
        });
    });

    function changeStatus(action) {
        var errEl = document.getElementById('cs-reply-error');
        setError(errEl, '');
        var body = new FormData();
        body.append('ticket_id', state.ticketId);
        body.append('action', action);
        post(urls.status, body).then(function (json) {
            if (json.error) {
                setError(errEl, json.message || 'Could not update the ticket.');
                return;
            }
            openThread(state.ticketId);
        }).catch(function () {
            setError(errEl, 'Could not update the ticket. Please try again.');
        });
    }

    document.getElementById('cs-resolve-btn').addEventListener('click', function () { changeStatus('resolve'); });
    document.getElementById('cs-reopen-btn').addEventListener('click', function () { changeStatus('reopen'); });

    /* Deep link from a ticket notification / the bot widget: ?ticket_id=N opens that thread.
     *
     * Deferred to DOMContentLoaded so account-suite.js (footer) has defined CzAccount by the
     * time the first show() runs. show() no longer depends on it, but the popup controller
     * being ready before the first view switch is the behaviour the rest of My Account has.
     *
     * The list is loaded alongside the thread on a deep link. It used to be skipped
     * entirely, which left "Back to my tickets" fetching from scratch, and meant one failed
     * thread request stranded the customer on a page with no way to reach their other
     * tickets. */
    function init() {
        var initial = parseInt(root.dataset.openTicket, 10);
        if (initial > 0) {
            loadList();
            openThread(initial);
        } else {
            loadList();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
