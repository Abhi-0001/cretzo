<?php
/*
 * Storefront support widget, rendered inside the #chat-iframe in the theme footer.
 *
 * This page used to pull in the theme's whole include-css / include-script bundle - the
 * entire storefront CSS and JS, jQuery included - just to draw a 450px panel, and then laid
 * the panel out as a static page: a 200px-tall message box with a permanent wall of buttons
 * below it, no way to close it from inside, and a fixed pixel width that overflowed a phone
 * screen. It is now self-contained (no theme assets, no jQuery) and built as an actual chat
 * surface: messages fill the height, options arrive as chips attached to the reply that
 * offered them, and the transcript is restored from chat/history when it is re-opened.
 */
$settings      = function_exists('get_settings') ? get_settings('system_settings', true) : [];
$store_name    = !empty($settings['app_name']) ? stripcslashes($settings['app_name']) : 'Support';
$whatsapp_link = function_exists('whatsapp_support_link') ? whatsapp_support_link() : '';
$logged_in     = isset($is_logged_in) ? (bool) $is_logged_in : (isset($this->ion_auth) && $this->ion_auth->logged_in());
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= html_escape($store_name) ?> Assistant</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ---------------------------------------------------------------- tokens */
:root {
    /* Terracotta is the storefront's accent (--color-orange / #e07b39). */
    --brand: #e07b39;
    --brand-dark: #c96820;
    --brand-deep: #a8531a;
    --brand-tint: #fff3e6;
    /* The header and the visitor's own bubbles used to be a deep terracotta gradient carrying
       white text, which measured 2.2-3.0:1 against white - under the 4.5:1 readable minimum,
       and lightening it with white text on top would only have made it worse. They are now a
       soft cream-terracotta with dark terracotta text: lighter than before AND 5.9:1. */
    --surface-warm: #ffe3cc;
    --surface-warm-edge: #f7d3b6;
    --ink-warm: #8a4418;
    --ink-warm-soft: #a35a28;
    --ink: #1f2126;
    --ink-soft: #5c6068;
    --ink-faint: #8f949c;
    --line: #ecedef;
    --surface: #ffffff;
    --canvas: #f7f5f2;
    --radius: 18px;
    --shadow-sm: 0 1px 2px rgba(24, 26, 30, .06);
    --shadow-md: 0 6px 18px rgba(24, 26, 30, .10);
}

* { box-sizing: border-box; }

html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    overflow: hidden;
}

body {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.5;
    color: var(--ink);
    background: transparent;
    -webkit-font-smoothing: antialiased;
}

/* ---------------------------------------------------------------- shell */
.cw {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: var(--canvas);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: 0 18px 48px rgba(24, 26, 30, .18);
}

/* ---------------------------------------------------------------- header */
.cw-head {
    flex: 0 0 auto;
    position: relative;
    z-index: 2;
    padding: 16px 16px 18px;
    background: linear-gradient(135deg, #fff0e2 0%, var(--surface-warm) 100%);
    color: var(--ink-warm);
    border-bottom: 1px solid var(--surface-warm-edge);
    overflow: hidden;
    transition: box-shadow .2s ease;
}

/* Without this, a message scrolled under the header just vanished with no seam, so it was
   not obvious there was anything above the fold. */
.cw-head.scrolled { box-shadow: 0 4px 14px rgba(138, 68, 24, .16); }

/* Soft bloom so the header does not read as a flat block. White still reads on the cream. */
.cw-head::after {
    content: '';
    position: absolute;
    top: -70px;
    right: -40px;
    width: 190px;
    height: 190px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .5);
    pointer-events: none;
    /* ::after paints ABOVE the element's children, so without this the bloom sat on top of
       the title and buttons and washed them out. */
    z-index: 0;
}

.cw-head-row {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 12px;
}

.cw-avatar {
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #fff;
    border: 1.5px solid rgba(138, 68, 24, .14);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    overflow: hidden;
}

.cw-avatar img { width: 100%; height: 100%; object-fit: cover; }

.cw-id { flex: 1 1 auto; min-width: 0; }

.cw-title {
    font-weight: 700;
    font-size: 15px;
    letter-spacing: -.1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cw-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--ink-warm-soft);
    margin-top: 1px;
}

.cw-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #15a34a;
    box-shadow: 0 0 0 0 rgba(21, 163, 74, .55);
    animation: pulse 2.4s infinite;
}

@keyframes pulse {
    0%   { box-shadow: 0 0 0 0 rgba(21, 163, 74, .55); }
    70%  { box-shadow: 0 0 0 7px rgba(21, 163, 74, 0); }
    100% { box-shadow: 0 0 0 0 rgba(21, 163, 74, 0); }
}

.cw-tools { display: flex; gap: 4px; flex: 0 0 auto; }

.cw-icon-btn {
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 9px;
    background: rgba(255, 255, 255, .7);
    border: 1px solid rgba(138, 68, 24, .16);
    color: var(--ink-warm);
    font-size: 15px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .18s ease, transform .18s ease;
}

.cw-icon-btn:hover { background: #fff; border-color: rgba(138, 68, 24, .34); transform: translateY(-1px); }
.cw-icon-btn:focus-visible { outline: 2px solid var(--ink-warm); outline-offset: 1px; }

/* ---------------------------------------------------------------- messages */
.cw-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 16px 14px 6px;
    scroll-behavior: smooth;
}

.cw-body::-webkit-scrollbar { width: 6px; }
.cw-body::-webkit-scrollbar-thumb { background: #d8d6d1; border-radius: 3px; }
.cw-body::-webkit-scrollbar-track { background: transparent; }

.cw-day {
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-faint);
    text-transform: uppercase;
    letter-spacing: .6px;
    margin: 2px 0 14px;
}

.cw-turn {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
    animation: rise .28s cubic-bezier(.22, 1, .36, 1) both;
}

@keyframes rise {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: none; }
}

.cw-turn.me { flex-direction: row-reverse; }

.cw-face {
    flex: 0 0 auto;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fff;
    color: #fff;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
    overflow: hidden;
    box-shadow: 0 0 0 1px var(--line);
}

.cw-face img { width: 100%; height: 100%; object-fit: cover; }

.cw-turn.me .cw-face { background: #33363c; box-shadow: none; }
.cw-turn.me .cw-face svg { width: 15px; height: 15px; }

.cw-stack { min-width: 0; max-width: calc(100% - 40px); }
.cw-turn.me .cw-stack { display: flex; flex-direction: column; align-items: flex-end; }

.cw-bubble {
    padding: 10px 13px;
    border-radius: 14px;
    background: var(--surface);
    box-shadow: var(--shadow-sm);
    border-top-left-radius: 4px;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
}

.cw-turn.me .cw-bubble {
    background: var(--surface-warm);
    color: var(--ink-warm);
    border: 1px solid var(--surface-warm-edge);
    border-top-left-radius: 14px;
    border-top-right-radius: 4px;
    box-shadow: 0 1px 3px rgba(138, 68, 24, .10);
}

.cw-time {
    font-size: 10.5px;
    color: var(--ink-faint);
    margin: 4px 4px 0;
}

/* Links inside a bot bubble. */
.cw-bubble a { color: var(--brand-dark); font-weight: 600; }

/* ---------------------------------------------------------------- typing */
.cw-typing {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    padding: 13px 15px;
}

.cw-typing i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--ink-faint);
    display: block;
    animation: bounce 1.3s infinite ease-in-out;
}

.cw-typing i:nth-child(2) { animation-delay: .18s; }
.cw-typing i:nth-child(3) { animation-delay: .36s; }

@keyframes bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: .45; }
    30%           { transform: translateY(-5px); opacity: 1; }
}

/* ---------------------------------------------------------------- cards */
.cw-cards { display: grid; gap: 8px; margin-top: 8px; }

.cw-card {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 9px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 13px;
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow-sm);
    transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
}

.cw-card:hover {
    border-color: var(--brand);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.cw-card-img {
    flex: 0 0 auto;
    width: 46px;
    height: 46px;
    border-radius: 10px;
    object-fit: cover;
    background: var(--brand-tint);
}

.cw-card-glyph {
    flex: 0 0 auto;
    width: 46px;
    height: 46px;
    border-radius: 10px;
    background: var(--brand-tint);
    color: var(--brand-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.cw-card-txt { min-width: 0; flex: 1 1 auto; }

.cw-card-title {
    font-weight: 600;
    font-size: 13px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.cw-card-body {
    font-size: 11.5px;
    color: var(--ink-soft);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cw-card-price {
    font-weight: 700;
    font-size: 13px;
    color: var(--brand-dark);
    margin-top: 1px;
}

.cw-card-go {
    flex: 0 0 auto;
    color: var(--ink-faint);
    font-size: 16px;
    padding-right: 2px;
}

/* A card pointing at the page the visitor is already on. It used to render as an ordinary
   link, so clicking it re-navigated to the same URL: the page did not visibly change and the
   iframe reloaded, which read as a dead button. It is now inert and says so. */
.cw-card.is-here {
    cursor: default;
    background: var(--brand-tint);
    border-color: #e4d8cc;
    box-shadow: none;
}

.cw-card.is-here:hover { transform: none; box-shadow: none; border-color: #e4d8cc; }

.cw-card.is-here .cw-card-go {
    font-size: 11px;
    font-weight: 600;
    color: var(--brand-deep);
    white-space: nowrap;
    letter-spacing: .2px;
}

/* ---------------------------------------------------------------- chips */
.cw-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 9px;
}

.cw-chip {
    border: 1px solid #e4d8cc;
    background: var(--surface);
    color: var(--brand-deep);
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    padding: 7px 12px;
    border-radius: 999px;
    cursor: pointer;
    white-space: nowrap;
    transition: background .18s ease, border-color .18s ease, transform .18s ease, box-shadow .18s ease;
}

.cw-chip:hover {
    background: var(--brand-tint);
    border-color: var(--brand);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(224, 123, 57, .18);
}

.cw-chip:focus-visible { outline: 2px solid var(--brand); outline-offset: 1px; }
.cw-chip[disabled] { opacity: .45; cursor: default; transform: none; box-shadow: none; }

/* ---------------------------------------------------------------- composer */
.cw-foot {
    flex: 0 0 auto;
    padding: 10px 12px 12px;
    background: var(--surface);
    border-top: 1px solid var(--line);
}

.cw-form {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}

.cw-input {
    flex: 1 1 auto;
    min-width: 0;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.4;
    color: var(--ink);
    background: var(--canvas);
    border: 1.5px solid var(--line);
    border-radius: 20px;
    padding: 10px 14px;
    resize: none;
    max-height: 96px;
    transition: border-color .18s ease, background .18s ease;
}

.cw-input:focus {
    outline: none;
    background: #fff;
    border-color: var(--brand);
}

.cw-input::placeholder { color: var(--ink-faint); }

.cw-send {
    flex: 0 0 auto;
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(224, 123, 57, .35);
    transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
}

.cw-send:hover:not([disabled]) { transform: translateY(-1px) scale(1.04); box-shadow: 0 6px 16px rgba(224, 123, 57, .45); }
.cw-send:focus-visible { outline: 2px solid var(--brand-deep); outline-offset: 2px; }
.cw-send[disabled] { opacity: .45; cursor: default; box-shadow: none; }

.cw-legal {
    text-align: center;
    font-size: 10.5px;
    color: var(--ink-faint);
    margin: 8px 0 0;
}

.cw-legal a { color: var(--ink-soft); }

/* Screen-reader-only live region. */
.cw-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

/* ---------------------------------------------------------------- small screens */
@media (max-width: 480px) {
    .cw { border-radius: 0; box-shadow: none; }
    .cw-body { padding: 14px 11px 4px; }
    .cw-stack { max-width: calc(100% - 34px); }
}

@media (prefers-reduced-motion: reduce) {
    * { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
}
</style>
</head>

<body>

<div class="cw">

    <header class="cw-head">
        <div class="cw-head-row">
            <div class="cw-avatar" aria-hidden="true"><img src="<?= base_url('assets/front_end/cretzo/img/chat-fab-icon.png') ?>" alt=""></div>
            <div class="cw-id">
                <div class="cw-title"><?= html_escape($store_name) ?> Assistant</div>
                <div class="cw-status"><span class="cw-dot" aria-hidden="true"></span> Online · replies instantly</div>
            </div>
            <div class="cw-tools">
                <button type="button" class="cw-icon-btn" id="cw-restart" title="Start a new conversation" aria-label="Start a new conversation">⟳</button>
                <button type="button" class="cw-icon-btn" id="cw-close" title="Close chat" aria-label="Close chat">✕</button>
            </div>
        </div>
    </header>

    <main class="cw-body" id="cw-body" role="log" aria-live="polite" aria-label="Conversation"></main>

    <footer class="cw-foot">
        <form class="cw-form" id="cw-form" autocomplete="off">
            <label class="cw-sr" for="cw-input">Type your message</label>
            <textarea class="cw-input" id="cw-input" rows="1" placeholder="Type your message…" maxlength="1000"></textarea>
            <button class="cw-send" id="cw-send" type="submit" aria-label="Send message" disabled>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 12L20 4l-3.2 8L20 20 4 12z" fill="currentColor"/>
                </svg>
            </button>
        </form>
        <p class="cw-legal">
            Automated assistant · need a person?
            <?php if (!empty($whatsapp_link)) { ?>
                <a href="<?= html_escape($whatsapp_link) ?>" target="_blank" rel="noopener">WhatsApp us</a>
            <?php } else { ?>
                <a href="<?= base_url($logged_in ? 'my-account/support' : 'login') ?>" target="_top">Raise a ticket</a>
            <?php } ?>
        </p>
    </footer>

</div>

<script>
(function () {
    'use strict';

    /* Every message the widget sent used to come back 403 Forbidden: `chat/send` is a POST and
     * CSRF protection is on globally, but the old fetch() carried no token and the URI is not in
     * csrf_exclude_uris. The token is emitted here and refreshed from each reply. */
    var CSRF_NAME  = <?= json_encode($this->security->get_csrf_token_name()) ?>;
    var CSRF_HASH  = <?= json_encode($this->security->get_csrf_hash()) ?>;
    var SEND_URL   = <?= json_encode(base_url('chat/send')) ?>;
    var HIST_URL   = <?= json_encode(base_url('chat/history')) ?>;
    var BOT_AVATAR = <?= json_encode(base_url('assets/front_end/cretzo/img/chat-fab-icon.png')) ?>;
    var USER_AVATAR_SVG = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
        '<circle cx="12" cy="8" r="3.6" fill="currentColor"/>' +
        '<path d="M4.5 19.2c1.2-3.6 4.2-5.6 7.5-5.6s6.3 2 7.5 5.6c.25.75-.3 1.3-1 1.3H5.5c-.7 0-1.25-.55-1-1.3z" fill="currentColor"/>' +
        '</svg>';

    var body    = document.getElementById('cw-body');
    var form    = document.getElementById('cw-form');
    var input   = document.getElementById('cw-input');
    var sendBtn = document.getElementById('cw-send');

    var busy = false;

    /* -------------------------------------------------- rendering */

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) { node.className = cls; }
        if (text !== undefined && text !== null) { node.textContent = text; }
        return node;
    }

    /**
     * The face avatar used to be a raw emoji, which renders as a different (and on some
     * platforms mismatched) glyph per OS/browser. The assistant now always shows the actual
     * Cretzo mascot; the visitor gets a plain person silhouette instead of a smiley.
     */
    function buildFace(who) {
        var face = el('div', 'cw-face');
        face.setAttribute('aria-hidden', 'true');
        if (who === 'me') {
            face.innerHTML = USER_AVATAR_SVG;
        } else {
            var img = el('img');
            img.src = BOT_AVATAR;
            img.alt = '';
            face.appendChild(img);
        }
        return face;
    }

    /* The page hosting the widget. Same-origin, so this is readable, but a browser or a
     * future embed could still refuse - in which case no card is ever treated as "here",
     * which is the safe direction to fail. */
    var parentUrl = '';
    try {
        parentUrl = window.parent.location.href;
    } catch (e) {
        parentUrl = '';
    }

    /**
     * True when a card's destination is the page the visitor is already looking at.
     *
     * Origin and path only - the query string is deliberately ignored. Every card here is a
     * "go to this section" link (products, cart, my orders, support), and the pages they point
     * at rewrite their own URL as you use them: the product listing sits on
     * `/products?page=1&per-page=20` the moment it renders. Comparing the query string meant
     * the guard never fired on exactly the pages that carry one.
     */
    function isCurrentPage(href) {
        if (!href || !parentUrl) { return false; }
        try {
            var to = new URL(href, parentUrl);
            var here = new URL(parentUrl);
            return to.origin === here.origin
                && to.pathname.replace(/\/+$/, '') === here.pathname.replace(/\/+$/, '');
        } catch (e) {
            return false;
        }
    }

    function atBottom() {
        return body.scrollHeight - body.scrollTop - body.clientHeight < 60;
    }

    function scrollDown(force) {
        if (!force && !atBottom()) { return; }
        // scroll-behavior:smooth would animate this, and a second jump arriving mid-animation
        // cancels the first - so pin instantly and let CSS smoothing apply to user scrolling.
        body.scrollTo({ top: body.scrollHeight, behavior: 'auto' });
    }

    function dayLabel(text) {
        body.appendChild(el('div', 'cw-day', text));
    }

    /**
     * One conversation turn. Text is always set via textContent, so a reply that echoes
     * something the visitor typed can never inject markup into the widget.
     */
    function addTurn(who, text, time) {
        var turn = el('div', 'cw-turn' + (who === 'me' ? ' me' : ''));
        var face = buildFace(who);
        var stack = el('div', 'cw-stack');
        var bubble = el('div', 'cw-bubble', text);

        stack.appendChild(bubble);
        if (time) { stack.appendChild(el('div', 'cw-time', time)); }
        turn.appendChild(face);
        turn.appendChild(stack);
        body.appendChild(turn);
        scrollDown(who === 'me');
        return stack;
    }

    function addCards(stack, cards) {
        if (!cards || !cards.length) { return; }

        var wrap = el('div', 'cw-cards');

        cards.forEach(function (card) {
            var here = isCurrentPage(card.url);
            // Rendered as a <div>, not a disabled <a>: a link that goes nowhere is worse for a
            // screen reader than something that was never announced as a link.
            var a = el(here ? 'div' : 'a', 'cw-card' + (here ? ' is-here' : ''));

            if (here) {
                a.setAttribute('aria-current', 'page');
            } else {
                a.href = card.url || '#';
                // The widget lives in an iframe: a product link must open the parent window,
                // not navigate the 400px panel to a full storefront page.
                a.target = '_top';
                a.rel = 'noopener';
            }

            if (card.image) {
                var img = el('img', 'cw-card-img');
                img.src = card.image;
                img.alt = '';
                img.loading = 'lazy';
                img.onerror = function () { this.remove(); };
                // The image arrives after layout and makes the card taller, which would
                // otherwise push the newest reply back out of view.
                img.onload = function () { scrollDown(); };
                a.appendChild(img);
            } else {
                a.appendChild(el('div', 'cw-card-glyph', card.type === 'product' ? '🛍️' : '↗'));
            }

            var txt = el('div', 'cw-card-txt');
            txt.appendChild(el('div', 'cw-card-title', card.title || ''));
            if (card.price) {
                txt.appendChild(el('div', 'cw-card-price', card.price));
            }
            if (card.body) {
                txt.appendChild(el('div', 'cw-card-body', card.body));
            }
            a.appendChild(txt);
            a.appendChild(el('span', 'cw-card-go', here ? "You're here" : '›'));
            wrap.appendChild(a);
        });

        stack.appendChild(wrap);
        scrollDown();
    }

    /**
     * Chips belong to the message that offered them, and are retired once used - the old
     * widget kept one permanent grid of buttons that stayed valid-looking even when the
     * conversation had moved on.
     */
    function addChips(stack, chips) {
        if (!chips || !chips.length) { return; }

        var wrap = el('div', 'cw-chips');

        chips.forEach(function (chip) {
            var btn = el('button', 'cw-chip', chip.label || chip.message || '');
            btn.type = 'button';
            btn.addEventListener('click', function () {
                if (busy) { return; }
                retire(wrap);
                send(chip.message || chip.label || '', chip.action || '');
            });
            wrap.appendChild(btn);
        });

        stack.appendChild(wrap);
        scrollDown();
    }

    function retire(wrap) {
        wrap.querySelectorAll('.cw-chip').forEach(function (b) { b.disabled = true; });
    }

    function showTyping() {
        var turn = el('div', 'cw-turn');
        var face = buildFace('bot');
        var bubble = el('div', 'cw-bubble cw-typing');
        bubble.setAttribute('aria-label', 'Assistant is typing');
        bubble.appendChild(el('i'));
        bubble.appendChild(el('i'));
        bubble.appendChild(el('i'));
        var stack = el('div', 'cw-stack');
        stack.appendChild(bubble);
        turn.appendChild(face);
        turn.appendChild(stack);
        body.appendChild(turn);
        scrollDown(true);
        return turn;
    }

    function setBusy(state) {
        busy = state;
        sendBtn.disabled = state || input.value.trim() === '';
        input.disabled = state;
    }

    /* -------------------------------------------------- transport */

    function post(url, fields) {
        var params = new URLSearchParams();
        Object.keys(fields).forEach(function (key) {
            if (fields[key] !== undefined && fields[key] !== null) {
                params.append(key, fields[key]);
            }
        });
        params.append(CSRF_NAME, CSRF_HASH);

        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: params.toString()
        }).then(function (res) {
            return res.text().then(function (raw) {
                var json;
                try {
                    json = JSON.parse(raw);
                } catch (e) {
                    // A PHP notice or an error page prepended to the JSON used to surface as a
                    // bare "Server error" with no clue in it; keep the detail in the console.
                    console.error('chat: unparseable response', res.status, raw.slice(0, 500));
                    throw new Error('bad-json');
                }
                if (!res.ok) { throw new Error('http-' + res.status); }
                // csrf_regenerate is off today, but adopting the hash the server hands back
                // keeps the widget working if that ever changes or the token expires.
                if (json.csrfHash) { CSRF_HASH = json.csrfHash; }
                if (json.csrfName) { CSRF_NAME = json.csrfName; }
                return json;
            });
        });
    }

    function send(text, action) {
        text = (text || '').trim();
        action = action || '';
        if (busy || (!text && !action)) { return; }

        addTurn('me', text || action.replace(/_/g, ' '), timeNow());
        setBusy(true);
        var typing = showTyping();

        post(SEND_URL, { message: text, action: action })
            .then(function (json) {
                typing.remove();
                var stack = addTurn('bot', json.reply || 'Sorry, I have nothing to add there.', json.time || timeNow());
                addCards(stack, json.cards);
                addChips(stack, json.quick_replies);
                // A reply carrying five product cards is tall enough that the incremental
                // "only scroll if already near the bottom" checks give up part-way through it,
                // leaving the cards below the fold. The reply is complete here, so commit.
                scrollDown(true);
            })
            .catch(function (err) {
                typing.remove();
                var stack = addTurn(
                    'bot',
                    err && err.message === 'bad-json'
                        ? 'Something went wrong on our side. Please try again, or reach us on WhatsApp.'
                        : 'I could not reach the server. Check your connection and try again.',
                    timeNow()
                );
                addChips(stack, [{ label: '↻ Try again', message: text, action: action }]);
            })
            .then(function () {
                setBusy(false);
                input.focus();
            });
    }

    function timeNow() {
        try {
            return new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        } catch (e) {
            return '';
        }
    }

    /* -------------------------------------------------- boot */

    function greet(greeting, returning) {
        // Replaying the full "I'm the assistant, I can do X, Y, Z" introduction on every page
        // navigation reads like the bot has forgotten the conversation directly above it.
        var text = returning
            ? 'Anything else I can help with?'
            : ((greeting && greeting.text) || 'Hi 👋 How can I help you today?');
        var stack = addTurn('bot', text, timeNow());
        addChips(stack, greeting && greeting.quick_replies);
    }

    function boot() {
        // The transcript was already being written to chat_messages on every message, but
        // nothing read it back, so re-opening the widget always looked like a cold start.
        fetch(HIST_URL, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                var messages = (json && json.messages) || [];
                if (messages.length) {
                    dayLabel('Earlier');
                    messages.forEach(function (m) {
                        addTurn(m.sender === 'agent' ? 'bot' : 'me', m.text, m.time);
                    });
                    dayLabel('Today');
                }
                greet(json && json.greeting, messages.length > 0);
                scrollDown(true);
            })
            .catch(function () {
                greet(null);
            });
    }

    /* -------------------------------------------------- composer wiring */

    var head = document.querySelector('.cw-head');
    body.addEventListener('scroll', function () {
        head.classList.toggle('scrolled', body.scrollTop > 4);
    }, { passive: true });

    input.addEventListener('input', function () {
        sendBtn.disabled = busy || input.value.trim() === '';
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 96) + 'px';
    });

    input.addEventListener('keydown', function (e) {
        // Enter sends; Shift+Enter is a newline. The old widget bound `keypress` and had no
        // newline escape at all.
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text) { return; }
        input.value = '';
        input.style.height = 'auto';
        send(text, '');
    });

    document.getElementById('cw-restart').addEventListener('click', function () {
        if (busy) { return; }
        body.innerHTML = '';
        setBusy(true);
        post(SEND_URL, { message: '', action: 'reset' })
            .then(function (json) {
                greet({ text: json.reply, quick_replies: json.quick_replies });
            })
            .catch(function () { greet(null); })
            .then(function () { setBusy(false); });
    });

    // There was no way to dismiss the panel from inside it; the parent page owns the iframe,
    // so ask it to close. The FAB handler in custom.js listens for this.
    document.getElementById('cw-close').addEventListener('click', function () {
        try {
            window.parent.postMessage({ cretzoChat: 'close' }, window.location.origin);
        } catch (e) {
            console.warn('chat: parent did not accept close', e);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.getElementById('cw-close').click();
        }
    });

    boot();
    input.focus();
})();
</script>

</body>
</html>
