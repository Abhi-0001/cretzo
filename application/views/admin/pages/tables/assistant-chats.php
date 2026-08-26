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
 * Read-only on purpose. Replying here would imply a live agent handoff, and the real handoff
 * is the support-ticket system, which already notifies both sides.
 */
$stats = isset($assistant_stats) ? $assistant_stats : ['messages' => 0, 'threads' => 0, 'week_messages' => 0, 'guest_messages' => 0];
$fallback = isset($assistant_fallback) ? $assistant_fallback : ['replies' => 0, 'missed' => 0, 'percent' => 0];
?>
<style>
/* Scoped to this page so nothing here can reach the rest of the admin theme. */
.asst-page .asst-stat {
    border: 1px solid #e6e8ec;
    border-radius: 10px;
    background: #fff;
    padding: 14px 16px;
    height: 100%;
}

.asst-page .asst-stat-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .5px;
    text-transform: uppercase;
    color: #8a8f98;
    margin-bottom: 4px;
}

.asst-page .asst-stat-value {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.1;
    color: #23262b;
}

.asst-page .asst-stat-note {
    font-size: 12px;
    color: #8a8f98;
    margin-top: 2px;
}

.asst-page .asst-stat--warn .asst-stat-value { color: #c62828; }
.asst-page .asst-stat--ok .asst-stat-value { color: #2e7d32; }

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
</style>

<div class="content-wrapper asst-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-robot mr-2 text-primary-theme"></i>Assistant Chats</h4>
                    <p class="text-muted mb-0 small">What customers asked the storefront support assistant, and how it answered.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/chat') ?>">Chat</a></li>
                        <li class="breadcrumb-item active">Assistant Chats</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row mb-3">
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="asst-stat">
                        <div class="asst-stat-label">Conversations</div>
                        <div class="asst-stat-value"><?= number_format($stats['threads']) ?></div>
                        <div class="asst-stat-note"><?= number_format($stats['messages']) ?> messages in total</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="asst-stat">
                        <div class="asst-stat-label">Last 7 days</div>
                        <div class="asst-stat-value"><?= number_format($stats['week_messages']) ?></div>
                        <div class="asst-stat-note">messages exchanged</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="asst-stat">
                        <div class="asst-stat-label">From guests</div>
                        <div class="asst-stat-value"><?= number_format($stats['guest_messages']) ?></div>
                        <div class="asst-stat-note">not signed in when they asked</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <?php
                    /* The single most useful number on the page: a high fallback rate means the
                     * bot is being asked things it has no answer for, which is exactly the list
                     * of intents worth adding next. Anything over 25% is worth acting on. */
                    $fallback_class = ($fallback['percent'] >= 25) ? 'asst-stat--warn' : (($fallback['replies'] > 0) ? 'asst-stat--ok' : '');
                    ?>
                    <div class="asst-stat <?= $fallback_class ?>">
                        <div class="asst-stat-label">Unanswered (30 days)</div>
                        <div class="asst-stat-value"><?= $fallback['percent'] ?>%</div>
                        <div class="asst-stat-note"><?= number_format($fallback['missed']) ?> of <?= number_format($fallback['replies']) ?> replies fell back</div>
                    </div>
                </div>
            </div>

            <?php if ($fallback['percent'] >= 25 && $fallback['replies'] >= 20) { ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    The assistant could not answer <strong><?= $fallback['percent'] ?>%</strong> of messages in the last 30 days.
                    Open a few of those conversations below — the questions it missed are the ones worth teaching it next.
                </div>
            <?php } ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center flex-wrap">
                                <span class="header-icon bg-set mr-2"><i class="fas fa-comments"></i></span>
                                <h5 class="mb-0 mr-auto">Conversations</h5>
                                <div class="form-inline mt-2 mt-sm-0">
                                    <label class="mr-2 mb-0 small text-muted" for="assistant_audience">Show</label>
                                    <select class="form-control form-control-sm" id="assistant_audience">
                                        <option value="">Everyone</option>
                                        <option value="customer">Signed-in customers</option>
                                        <option value="guest">Guests only</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <table class="table-striped" id="assistant_chat_table" data-toggle="table"
                                data-url="<?= base_url('admin/chat/assistant-list') ?>"
                                data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
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

        </div>
    </section>
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

<script>
/* Kept on the page rather than in assets/admin/custom/custom.js: that file is loaded on every
 * admin screen, and this behaviour belongs only to this one. */
(function ($) {
    'use strict';

    var THREAD_URL = <?= json_encode(base_url('admin/chat/assistant-thread')) ?>;

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
})(jQuery);
</script>
