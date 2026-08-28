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
?>

<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <p class="text-n"><?= (is_object($users) && isset($users->username)) ? html_escape($users->username) : '' ?></p>
    <div class="overview-container">

        <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>

        <div class="overview-right">

            <h1 class="heading-n overview-right-heading">Support
                <br><span class="text-s op-6">Raise a ticket and track our replies</span>
            </h1>

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
                        <div class="cs-support__bar-actions">
                            <?php if (!empty($support_whatsapp)) { ?>
                                <a class="cs-support__whatsapp" href="<?= html_escape($support_whatsapp) ?>" target="_blank" rel="noopener">
                                    <i class="uil uil-whatsapp"></i> WhatsApp support
                                </a>
                            <?php } ?>
                            <button type="button" class="cs-support__primary" id="cs-new-btn" <?= $has_types ? '' : 'disabled' ?>>
                                <i class="uil uil-plus"></i> New ticket
                            </button>
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

                <!-- ============================= NEW TICKET ============================= -->
                <div id="cs-new-view" hidden>
                    <button type="button" class="cs-support__back" data-back>&larr; Back to my tickets</button>
                    <h2 class="cs-support__h2">Raise a new ticket</h2>

                    <form id="cs-new-form" class="cs-support__form" novalidate>
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

                        <div class="cs-support__actions">
                            <button type="submit" class="cs-support__primary" id="cs-create-submit">Create ticket</button>
                            <button type="button" class="cs-support__ghost" data-back>Cancel</button>
                        </div>
                        <p class="cs-support__error" id="cs-new-error" hidden></p>
                    </form>
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
        </div>
    </div>
</div>

<style>
    .cs-support { margin-top: 8px; }
    .cs-support__bar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .cs-support__filters { display: flex; gap: 10px; flex-wrap: wrap; }
    .cs-support__select, .cs-support__search, .cs-support__input, .cs-support__textarea {
        padding: 10px 14px; border: 1px solid #e3e3e3; border-radius: 10px; font-size: 14px;
        background: #fff; color: #1f2937; outline: none;
    }
    .cs-support__select--block, .cs-support__input, .cs-support__textarea { width: 100%; display: block; }
    .cs-support__select:focus, .cs-support__search:focus, .cs-support__input:focus, .cs-support__textarea:focus { border-color: #f4a742; }
    .cs-support__textarea { resize: vertical; font-family: inherit; }
    .cs-support__primary {
        padding: 10px 20px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600;
        color: #fff; background: linear-gradient(135deg, #ff9a3d 0%, #ff7a1a 100%);
    }
    .cs-support__primary:disabled { opacity: .5; cursor: not-allowed; }
    .cs-support__bar-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .cs-support__whatsapp {
        display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 10px;
        font-size: 14px; font-weight: 600; color: #fff; background: #25d366; text-decoration: none;
    }
    .cs-support__whatsapp:hover, .cs-support__whatsapp:focus { color: #fff; background: #1fbe5b; text-decoration: none; }
    /* Matches the numbered pager in cretzo-fixes.css (FIX 11). */
    .cs-support__ghost {
        min-height: 40px; padding: 0 16px; border: 1px solid #e4e6ea; border-radius: 10px;
        background: #fff; cursor: pointer; font-size: 14px; font-weight: 600; color: #333;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }
    .cs-support__ghost:hover:not(:disabled), .cs-support__ghost:focus-visible:not(:disabled) {
        border-color: var(--color-orange, #F2822E);
        background: var(--color-orange-light, #fff4ea);
        color: var(--color-orange-dark, #d96f1d);
        outline: none;
    }
    .cs-support__ghost:disabled {
        border-color: #ececef; background: #f7f7f8; color: #c3c6cc;
        box-shadow: none; cursor: default;
    }
    .cs-support__back { border: none; background: none; padding: 0 0 14px; color: #b96d00; cursor: pointer; font-size: 14px; }
    .cs-support__h2 { font-size: 20px; font-weight: 700; color: #1f2937; margin: 0 0 6px; }
    .cs-support__label { display: block; margin: 16px 0 6px; font-size: 13px; font-weight: 600; color: #374151; }
    .cs-support__form { max-width: 620px; }
    .cs-support__actions { display: flex; gap: 10px; margin-top: 20px; }
    .cs-support__list { display: flex; flex-direction: column; gap: 12px; }
    .cs-support__loading, .cs-support__empty { padding: 28px 0; text-align: center; color: #6b7280; font-size: 14px; }
    .cs-support__card {
        display: flex; gap: 14px; align-items: flex-start; justify-content: space-between;
        padding: 16px 18px; border: 1px solid #eee; border-radius: 12px; background: #fff; cursor: pointer;
        transition: box-shadow .2s ease, border-color .2s ease;
    }
    .cs-support__card:hover { border-color: #f4a742; box-shadow: 0 6px 18px rgba(244,167,66,.18); }
    .cs-support__cardmain { min-width: 0; }
    .cs-support__cardtitle { font-size: 15px; font-weight: 600; color: #1f2937; margin: 0 0 4px; word-break: break-word; }
    .cs-support__meta { font-size: 12.5px; color: #6b7280; margin: 0; }
    .cs-support__cardside { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
    .cs-support__badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .cs-support__badge--secondary { background: #f1f5f9; color: #475569; }
    .cs-support__badge--info { background: #e0f2fe; color: #0369a1; }
    .cs-support__badge--success { background: #dcfce7; color: #15803d; }
    .cs-support__badge--danger { background: #fee2e2; color: #b91c1c; }
    .cs-support__badge--warning { background: #fef3c7; color: #b45309; }
    .cs-support__unread { background: #ff7a1a; color: #fff; border-radius: 999px; font-size: 11px; font-weight: 700; padding: 2px 8px; }
    .cs-support__pager { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 20px; }
    .cs-support__pageinfo { font-size: 13px; color: #6b7280; }
    .cs-support__threadhead { display: flex; gap: 14px; align-items: flex-start; justify-content: space-between; padding-bottom: 14px; border-bottom: 1px solid #eee; }
    .cs-support__messages { display: flex; flex-direction: column; gap: 12px; padding: 18px 0; max-height: 460px; overflow-y: auto; }
    .cs-support__msg { max-width: 78%; padding: 12px 14px; border-radius: 12px; font-size: 14px; line-height: 1.6; word-break: break-word; }
    .cs-support__msg--mine { margin-left: auto; background: #ffe9cc; color: #3f2d16; }
    .cs-support__msg--support { background: #f6f7f9; color: #1f2937; }
    .cs-support__msgauthor { display: block; font-size: 11.5px; font-weight: 700; opacity: .65; margin-bottom: 4px; }
    .cs-support__msgtime { display: block; font-size: 11px; opacity: .55; margin-top: 6px; }
    .cs-support__files { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .cs-support__files img { max-width: 130px; max-height: 130px; border-radius: 8px; display: block; }
    .cs-support__filelink { font-size: 12.5px; color: #b96d00; text-decoration: underline; }
    .cs-support__replyform { border-top: 1px solid #eee; padding-top: 16px; }
    .cs-support__replyrow { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-top: 10px; }
    .cs-support__replybtns { display: flex; gap: 10px; flex-wrap: wrap; }
    .cs-support__attach { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13.5px; color: #374151; }
    .cs-support__hint { font-size: 12px; color: #9ca3af; margin: 8px 0 0; }
    .cs-support__error { color: #b91c1c; font-size: 13px; margin: 10px 0 0; }
    .cs-support__notice { padding: 14px 16px; border-radius: 10px; background: #fff7ed; color: #92400e; font-size: 13.5px; margin: 14px 0; }
    @media (max-width: 575px) {
        .cs-support__msg { max-width: 92%; }
        .cs-support__card { flex-direction: column; }
        .cs-support__cardside { align-items: flex-start; flex-direction: row; }
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
        create: document.getElementById('cs-new-view'),
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

    function show(which) {
        Object.keys(views).forEach(function (key) {
            if (views[key]) { views[key].hidden = (key !== which); }
        });
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

    root.querySelectorAll('[data-back]').forEach(function (btn) {
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

    /* Deep link from the notification email / bot widget: ?ticket_id=N opens that thread. */
    var initial = parseInt(root.dataset.openTicket, 10);
    if (initial > 0) {
        openThread(initial);
    } else {
        loadList();
    }
})();
</script>
