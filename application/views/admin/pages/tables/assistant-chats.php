<?php
/*
 * Admin view of the storefront support assistant's conversations.
 *
 * `chat_messages` has been collecting every message the floating chat widget exchanges since
 * the widget shipped, and nothing in the app ever read it - so no one on the team could see
 * what customers were asking the bot, or which questions it was failing to answer. This page
 * is that missing half: the threads, the transcripts, and the one number that matters (how
 * often the bot falls back to "I did not quite get that").
 *
 * The list is read-only apart from deletion. Replying here would imply a live agent handoff,
 * and the real handoff is the support-ticket system, which already notifies both sides -
 * but transcripts do need clearing out, since the table is otherwise append-only forever.
 *
 * Layout note: the whole page is a fixed-height column (header / stats / table card) and the
 * table body is the only thing that scrolls. Before this, a 100-row page pushed the browser
 * scrollbar metres long and the toolbar and pager both fell off the screen.
 */
$stats = isset($assistant_stats) ? $assistant_stats : ['messages' => 0, 'threads' => 0, 'week_messages' => 0, 'guest_messages' => 0];
$fallback = isset($assistant_fallback) ? $assistant_fallback : ['replies' => 0, 'missed' => 0, 'percent' => 0];
?>
<style>
/* Everything here is scoped to .asst-page so nothing can reach the rest of the admin theme. */
.asst-page {
    --asst-ink: #1d2026;
    --asst-muted: #7b818c;
    --asst-line: #e8eaee;
    --asst-brand: #e07b39;
    --asst-brand-dark: #c96820;
}

/* ------------------------------------------------------------------ page frame */
/* Fill the viewport under the admin navbar and let only the table scroll. */
.asst-page {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 57px);
    min-height: 520px;
    overflow: hidden;
}

.asst-page .asst-top { flex: 0 0 auto; padding: 14px 18px 0; }
.asst-page .asst-main { flex: 1 1 auto; min-height: 0; padding: 0 18px 16px; display: flex; }

.asst-page .asst-title {
    font-size: 19px;
    font-weight: 700;
    color: var(--asst-ink);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 9px;
}

.asst-page .asst-title i {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--asst-brand) 0%, var(--asst-brand-dark) 100%);
    color: #fff;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.asst-page .asst-sub { font-size: 12.5px; color: var(--asst-muted); margin: 4px 0 0 41px; }

.asst-page .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: 12.5px; }

/* ------------------------------------------------------------------ stat strip */
.asst-page .asst-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin: 14px 0 12px;
}

@media (max-width: 991px) {
    .asst-page { height: auto; overflow: visible; }
    .asst-page .asst-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 575px) {
    .asst-page .asst-stats { grid-template-columns: 1fr; }
}

.asst-page .asst-stat {
    position: relative;
    border: 1px solid var(--asst-line);
    border-radius: 12px;
    background: #fff;
    padding: 13px 15px 13px 17px;
    overflow: hidden;
}

/* The coloured spine is what separates the four cards at a glance. */
.asst-page .asst-stat::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--asst-brand);
}

.asst-page .asst-stat--warn::before { background: #d64545; }
.asst-page .asst-stat--ok::before { background: #2e9e5b; }

.asst-page .asst-stat-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: var(--asst-muted);
    margin-bottom: 3px;
}

.asst-page .asst-stat-value {
    font-size: 25px;
    font-weight: 700;
    line-height: 1.05;
    color: var(--asst-ink);
}

.asst-page .asst-stat-note { font-size: 11.5px; color: var(--asst-muted); margin-top: 2px; }
.asst-page .asst-stat--warn .asst-stat-value { color: #d64545; }
.asst-page .asst-stat--ok .asst-stat-value { color: #2e9e5b; }

.asst-page .asst-alert {
    border: 1px solid #f3d9a6;
    background: #fdf6e8;
    color: #7a5a12;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 12px;
}

/* ------------------------------------------------------------------ table card */
.asst-page .asst-card {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    width: 100%;
    border: 1px solid var(--asst-line);
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
}

.asst-page .asst-card-head {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 11px 15px;
    border-bottom: 1px solid var(--asst-line);
    background: #fbfbfc;
}

.asst-page .asst-card-title {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--asst-ink);
    margin: 0;
    margin-right: auto;
}

.asst-page .asst-card-title small { font-weight: 500; color: var(--asst-muted); }

.asst-page .asst-card-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    padding: 0 4px;
}

/* bootstrap-table renders its own wrapper; make that the flex column so the fixed-height
 * scroll lands on the rows and not on the toolbar or the pager. */
.asst-page .bootstrap-table,
.asst-page .fixed-table-container {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    border: 0;
    padding-bottom: 0 !important;
}

.asst-page .fixed-table-toolbar { flex: 0 0 auto; padding: 8px 10px 4px; }
.asst-page .fixed-table-pagination { flex: 0 0 auto; padding: 6px 10px; border-top: 1px solid var(--asst-line); }

.asst-page .fixed-table-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

/* Sticky header, so the columns stay readable while the rows scroll under them. */
.asst-page .fixed-table-body thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f5f6f8;
    border-top: 0;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    color: #5f6570;
    white-space: nowrap;
}

/* Compact rows: the default cell padding made ten rows taller than the viewport. */
.asst-page .fixed-table-body td {
    padding: 7px 10px;
    font-size: 12.8px;
    vertical-align: middle;
    border-top: 1px solid #f1f2f4;
}

.asst-page .fixed-table-body th { padding: 8px 10px; }
.asst-page .fixed-table-body tbody tr:hover td { background: #fcf8f4; }

/* The preview column is the one that wraps to three lines and blows the row height up. */
.asst-page .fixed-table-body td:nth-child(4) {
    max-width: 340px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.asst-page .action-btn { margin-right: 3px; }

/* ---------------------------------------------------------------- transcript modal */
#assistant_chat_modal .modal-dialog { max-width: 620px; }

#assistant_chat_modal .asst-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #e07b39 0%, #c96820 100%);
    color: #fff;
    border-radius: .3rem .3rem 0 0;
}

#assistant_chat_modal .asst-head-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .22);
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

#assistant_chat_modal .asst-head-text { flex: 1 1 auto; min-width: 0; }
#assistant_chat_modal .asst-head-name { font-weight: 700; margin: 0; font-size: 15px; }
#assistant_chat_modal .asst-head-sub { margin: 0; font-size: 12px; opacity: .9; }

#assistant_chat_modal .asst-close {
    background: rgba(255, 255, 255, .18);
    border: 0;
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    line-height: 1;
    cursor: pointer;
}

#assistant_chat_modal .asst-body {
    max-height: 60vh;
    overflow-y: auto;
    padding: 16px;
    background: #f7f5f2;
}

#assistant_chat_modal .asst-turn {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

#assistant_chat_modal .asst-turn.is-customer { flex-direction: row-reverse; }

#assistant_chat_modal .asst-face {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #fff;
    background: #c96820;
    margin-top: 2px;
}

#assistant_chat_modal .asst-turn.is-customer .asst-face { background: #33363c; }

#assistant_chat_modal .asst-stack { max-width: calc(100% - 40px); min-width: 0; }
#assistant_chat_modal .asst-turn.is-customer .asst-stack { text-align: right; }

#assistant_chat_modal .asst-bubble {
    display: inline-block;
    text-align: left;
    padding: 9px 12px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #ecedef;
    /* Bot replies are multi-line; without this they collapse into one run-on paragraph. */
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    font-size: 13.5px;
}

#assistant_chat_modal .asst-turn.is-customer .asst-bubble {
    background: #e07b39;
    border-color: #c96820;
    color: #fff;
}

#assistant_chat_modal .asst-time {
    font-size: 10.5px;
    color: #9aa0a6;
    margin-top: 3px;
}

#assistant_chat_modal .asst-empty {
    text-align: center;
    color: #8a8f98;
    padding: 28px 12px;
}

/* ---------------------------------------------------------------- delete modal */
#assistant_delete_modal .modal-dialog { max-width: 420px; }

#assistant_delete_modal .asst-del-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #fdeaea;
    color: #d64545;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin: 0 auto 12px;
}
</style>

<div class="content-wrapper asst-page">
    <div class="asst-top">
        <div class="d-flex align-items-start flex-wrap">
            <div class="mr-auto">
                <h4 class="asst-title"><i class="fas fa-robot"></i>Assistant Chats</h4>
                <p class="asst-sub">What customers asked the storefront support assistant, and how it answered.</p>
            </div>
            <ol class="breadcrumb mt-1">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('admin/chat') ?>">Chat</a></li>
                <li class="breadcrumb-item active">Assistant Chats</li>
            </ol>
        </div>

        <div class="asst-stats">
            <div class="asst-stat">
                <div class="asst-stat-label">Conversations</div>
                <div class="asst-stat-value"><?= number_format($stats['threads']) ?></div>
                <div class="asst-stat-note"><?= number_format($stats['messages']) ?> messages in total</div>
            </div>
            <div class="asst-stat">
                <div class="asst-stat-label">Last 7 days</div>
                <div class="asst-stat-value"><?= number_format($stats['week_messages']) ?></div>
                <div class="asst-stat-note">messages exchanged</div>
            </div>
            <div class="asst-stat">
                <div class="asst-stat-label">From guests</div>
                <div class="asst-stat-value"><?= number_format($stats['guest_messages']) ?></div>
                <div class="asst-stat-note">not signed in when they asked</div>
            </div>
            <?php
            /* The single most useful number on the page: a high fallback rate means the bot is
             * being asked things it has no answer for, which is exactly the list of intents
             * worth adding next. Anything over 25% is worth acting on. */
            $fallback_class = ($fallback['percent'] >= 25) ? 'asst-stat--warn' : (($fallback['replies'] > 0) ? 'asst-stat--ok' : '');
            ?>
            <div class="asst-stat <?= $fallback_class ?>">
                <div class="asst-stat-label">Unanswered (30 days)</div>
                <div class="asst-stat-value"><?= $fallback['percent'] ?>%</div>
                <div class="asst-stat-note"><?= number_format($fallback['missed']) ?> of <?= number_format($fallback['replies']) ?> replies fell back</div>
            </div>
        </div>

        <?php if ($fallback['percent'] >= 25 && $fallback['replies'] >= 20) { ?>
            <div class="asst-alert">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                The assistant could not answer <strong><?= $fallback['percent'] ?>%</strong> of messages in the last 30 days.
                Open a few of those conversations below — the questions it missed are the ones worth teaching it next.
            </div>
        <?php } ?>
    </div>

    <div class="asst-main">
        <div class="asst-card">
            <div class="asst-card-head">
                <h5 class="asst-card-title">Conversations <small>&mdash; newest activity first</small></h5>
                <div class="form-inline">
                    <label class="mr-2 mb-0 small text-muted" for="assistant_audience">Show</label>
                    <select class="form-control form-control-sm" id="assistant_audience">
                        <option value="">Everyone</option>
                        <option value="customer">Signed-in customers</option>
                        <option value="guest">Guests only</option>
                    </select>
                </div>
            </div>
            <div class="asst-card-body">
                <table class="table-striped" id="assistant_chat_table" data-toggle="table"
                    data-url="<?= base_url('admin/chat/assistant-list') ?>"
                    data-side-pagination="server" data-pagination="true"
                    data-page-size="10" data-page-list="[10, 25, 50, 100]" data-search="true"
                    data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                    data-sort-name="last_activity" data-sort-order="desc"
                    data-mobile-responsive="true" data-toolbar="" data-show-export="true"
                    data-export-types='["txt","excel"]'
                    data-query-params="assistant_queryParams">
                    <thead>
                        <tr>
                            <th data-field="username" data-sortable="true">Customer</th>
                            <th data-field="audience" data-sortable="false">Type</th>
                            <th data-field="messages" data-sortable="true">Messages</th>
                            <th data-field="last_message" data-sortable="false">Last message</th>
                            <th data-field="started" data-sortable="true">Started</th>
                            <th data-field="last_activity" data-sortable="true">Last activity</th>
                            <th data-field="thread" data-sortable="false">Thread</th>
                            <th data-field="operate" data-sortable="false">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assistant_chat_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="asst-head">
                <div class="asst-head-avatar" aria-hidden="true"><i class="fas fa-user"></i></div>
                <div class="asst-head-text">
                    <p class="asst-head-name" id="assistant_modal_customer">Conversation</p>
                    <p class="asst-head-sub" id="assistant_modal_meta">Support assistant transcript</p>
                </div>
                <button type="button" class="asst-close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="asst-body" id="assistant_modal_body">
                <div class="asst-empty">Loading…</div>
            </div>
            <div class="modal-footer py-2">
                <small class="text-muted mr-auto">Read-only. To reply to this customer, use Support Tickets.</small>
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assistant_delete_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center pt-4">
                <div class="asst-del-icon" aria-hidden="true"><i class="fas fa-trash"></i></div>
                <h5 class="mb-2">Delete this conversation?</h5>
                <p class="text-muted small mb-0" id="assistant_delete_text">
                    Every message in this thread will be removed permanently.
                </p>
                <p class="text-danger small mt-2 mb-0" id="assistant_delete_error"></p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="assistant_delete_confirm">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* Kept on the page rather than in assets/admin/custom/custom.js: that file is loaded on every
 * admin screen, and this behaviour belongs only to this one. */
(function ($) {
    'use strict';

    var THREAD_URL = <?= json_encode(base_url('admin/chat/assistant-thread')) ?>;
    var DELETE_URL = <?= json_encode(base_url('admin/chat/assistant-delete')) ?>;
    var CSRF_NAME = <?= json_encode($this->security->get_csrf_token_name()) ?>;
    var CSRF_HASH = <?= json_encode($this->security->get_csrf_hash()) ?>;

    // bootstrap-table calls this to build the query; the audience filter rides along with it.
    window.assistant_queryParams = function (params) {
        params.audience = $('#assistant_audience').val() || '';
        return params;
    };

    $('#assistant_audience').on('change', function () {
        $('#assistant_chat_table').bootstrapTable('refresh');
    });

    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : value).html();
    }

    function formatStamp(stamp) {
        if (!stamp) { return ''; }
        // MySQL hands back "YYYY-MM-DD HH:MM:SS"; Safari will not parse that with the space.
        var date = new Date(String(stamp).replace(' ', 'T'));
        if (isNaN(date.getTime())) { return escapeHtml(stamp); }
        return escapeHtml(date.toLocaleString());
    }

    function renderTranscript(messages) {
        if (!messages || !messages.length) {
            return '<div class="asst-empty">This conversation has no messages left in it.</div>';
        }

        var html = '';
        messages.forEach(function (message) {
            var mine = message.sender === 'user';
            html += '<div class="asst-turn ' + (mine ? 'is-customer' : '') + '">'
                + '<div class="asst-face" aria-hidden="true"><i class="fas fa-' + (mine ? 'user' : 'robot') + '"></i></div>'
                + '<div class="asst-stack">'
                + '<div class="asst-bubble">' + escapeHtml(message.message) + '</div>'
                + '<div class="asst-time">' + formatStamp(message.created_at) + '</div>'
                + '</div></div>';
        });
        return html;
    }

    $(document).on('click', '.view_assistant_chat', function () {
        var $btn = $(this);
        var thread = $btn.data('thread');
        var userId = $btn.data('user_id');
        var customer = $btn.data('customer');
        var $body = $('#assistant_modal_body');

        $('#assistant_modal_customer').text(customer || 'Conversation');
        $('#assistant_modal_meta').text('Support assistant transcript');
        $body.html('<div class="asst-empty">Loading…</div>');

        $.ajax({
            url: THREAD_URL,
            type: 'GET',
            data: { thread: thread === undefined ? '' : thread, user_id: userId || 0 },
            dataType: 'json'
        }).done(function (response) {
            if (!response || response.error) {
                $body.html('<div class="asst-empty">' + escapeHtml((response && response.message) || 'Could not load this conversation.') + '</div>');
                return;
            }
            $body.html(renderTranscript(response.messages));
            $('#assistant_modal_meta').text(response.messages.length + ' messages');
            // Open on the latest message, the way the conversation was actually left.
            $body.scrollTop($body.prop('scrollHeight'));
        }).fail(function () {
            $body.html('<div class="asst-empty">Could not load this conversation. Please try again.</div>');
        });
    });

    /* ---------------------------------------------------------------- deletion.
     * Confirmed in a modal rather than deleted on click: a transcript is the only record of
     * what a customer asked, and there is no undo behind this. */
    var pending = null;

    $(document).on('click', '.delete_assistant_chat', function () {
        var $btn = $(this);
        pending = {
            thread: $btn.data('thread') === undefined ? '' : String($btn.data('thread')),
            user_id: $btn.data('user_id') || 0
        };

        var count = parseInt($btn.data('messages'), 10) || 0;
        $('#assistant_delete_text').text(
            'All ' + count + ' message' + (count === 1 ? '' : 's') + ' from ' +
            ($btn.data('customer') || 'this conversation') + ' will be removed permanently.'
        );
        $('#assistant_delete_error').text('');
        $('#assistant_delete_modal').modal('show');
    });

    $('#assistant_delete_confirm').on('click', function () {
        if (!pending) { return; }

        var $btn = $(this);
        var payload = { thread: pending.thread, user_id: pending.user_id };
        payload[CSRF_NAME] = CSRF_HASH;

        $.ajax({
            url: DELETE_URL,
            type: 'POST',
            data: payload,
            dataType: 'json',
            beforeSend: function () {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...');
            }
        }).done(function (response) {
            if (!response || response.error) {
                $('#assistant_delete_error').text((response && response.message) || 'Could not delete this conversation.');
                return;
            }
            pending = null;
            $('#assistant_delete_modal').modal('hide');
            $('#assistant_chat_table').bootstrapTable('refresh');
            if (window.iziToast) {
                iziToast.success({ message: response.message });
            }
        }).fail(function () {
            $('#assistant_delete_error').text('Could not delete this conversation. Please try again.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i>Delete');
        });
    });
})(jQuery);
</script>
