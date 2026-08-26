<?php
/*
 * Seller support tickets.
 *
 * The seller panel had no support channel at all: "Chat" is seller <-> customer messaging, not a
 * line to the platform team. This page is the seller half of the same ticket system the admin
 * panel and the customer my-account page already use.
 *
 * Two views live in one page - the list and one thread - swapped client-side so opening a ticket
 * does not lose the list's paging/filter state.
 */
$has_types = !empty($ticket_types);
// Tickets are the written trail; WhatsApp is the fast lane to the platform team, and it is the
// channel the store actually answers on. whatsapp_support_link() resolves the number from
// settings, falling back to the owner-confirmed one.
$support_whatsapp = whatsapp_support_link('Hello Cretzo Support, I need help with my seller account.');
?>

<div class="content-wrapper seller-support-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-life-ring mr-2 text-primary-theme"></i>Support</h4>
                    <p class="text-muted mb-0 small">Raise a ticket with the <?= html_escape(get_settings('system_settings', true)['app_name']) ?> team and track our replies.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Support</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid"
             id="seller-support"
             data-open-ticket="<?= (int) $open_ticket_id ?>"
             data-list-url="<?= base_url('seller/support/get_my_tickets') ?>"
             data-thread-url="<?= base_url('seller/support/get_ticket_thread') ?>"
             data-create-url="<?= base_url('seller/support/create_ticket') ?>"
             data-reply-url="<?= base_url('seller/support/reply_ticket') ?>"
             data-status-url="<?= base_url('seller/support/update_ticket_status') ?>">

            <?php if (!$has_types) { ?>
                <div class="alert alert-warning">
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

            <!-- ============================== LIST VIEW ============================== -->
            <div id="ss-list-view">
                <div class="card attribute-card">
                    <div class="card-header attribute-card-header">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-life-ring"></i></span>
                        <h5 class="mb-0">My Tickets</h5>
                        <div class="ml-auto">
                            <?php if (!empty($support_whatsapp)) { ?>
                                <a href="<?= html_escape($support_whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success mr-1">
                                    <i class="fab fa-whatsapp mr-1"></i> WhatsApp Support
                                </a>
                            <?php } ?>
                            <button type="button" class="btn btn-sm btn-theme" id="ss-new-btn" <?= $has_types ? '' : 'disabled' ?>>
                                <i class="fas fa-plus mr-1"></i> New Ticket
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <select class="form-control form-control-sm" id="ss-status-filter">
                                    <option value="">All statuses</option>
                                    <?php foreach ($status_labels as $value => $meta) { ?>
                                        <option value="<?= html_escape($value) ?>"><?= html_escape($meta['label']) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6 mb-2">
                                <input type="text" class="form-control form-control-sm" id="ss-search" placeholder="Search your tickets...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped mb-0" id="ss-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category</th>
                                        <th>Subject</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Replies</th>
                                        <th>Last Update</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="ss-tbody">
                                    <tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted" id="ss-count"></small>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="ss-prev" disabled>Previous</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="ss-next" disabled>Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================= THREAD VIEW ============================= -->
            <div id="ss-thread-view" style="display:none;">
                <div class="card attribute-card">
                    <div class="card-header attribute-card-header">
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-3" id="ss-back-btn">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div>
                            <h5 class="mb-0" id="ss-thread-subject"></h5>
                            <small class="text-muted">
                                <span id="ss-thread-meta"></span>
                                <span id="ss-thread-status"></span>
                            </small>
                        </div>
                        <div class="ml-auto" id="ss-thread-actions"></div>
                    </div>
                    <div class="card-body">
                        <div id="ss-thread-first" class="ss-first-message"></div>
                        <div id="ss-thread-messages" class="ss-messages"></div>

                        <form id="ss-reply-form" class="mt-3" enctype="multipart/form-data" style="display:none;">
                            <input type="hidden" name="ticket_id" id="ss-reply-ticket-id">
                            <div class="form-group">
                                <textarea class="form-control" name="message" id="ss-reply-message" rows="3" placeholder="Type your reply..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="small text-muted mb-1" for="ss-reply-files">Attachments (optional, up to 3 &mdash; images, PDF, DOC, TXT; 5 MB each)</label>
                                <input type="file" class="form-control-file" name="attachments[]" id="ss-reply-files" multiple>
                            </div>
                            <button type="submit" class="btn btn-theme" id="ss-reply-submit">
                                <i class="fas fa-paper-plane mr-1"></i> Send Reply
                            </button>
                        </form>
                        <div class="alert alert-secondary mb-0 mt-3" id="ss-closed-notice" style="display:none;">
                            This ticket is closed. Please raise a new ticket if you still need help.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- ============================== NEW TICKET MODAL ============================== -->
<div class="modal fade" id="ss-new-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="ss-new-form">
                <div class="modal-header">
                    <h5 class="modal-title">Raise a Support Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="ss-new-type">Category <span class="text-danger">*</span></label>
                        <select class="form-control" name="ticket_type_id" id="ss-new-type" required>
                            <option value="">Select a category</option>
                            <?php foreach ($ticket_types as $type) { ?>
                                <option value="<?= (int) $type['id'] ?>"><?= html_escape($type['title']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ss-new-subject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject" id="ss-new-subject" maxlength="190" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="ss-new-description">Describe the issue <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="description" id="ss-new-description" rows="5" maxlength="5000" required></textarea>
                        <small class="text-muted">Include order IDs, product names or dates where relevant &mdash; it gets your ticket answered faster.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme" id="ss-new-submit">Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    /*
     * All requests go through jQuery, so assets/csrf-guard.js (loaded in seller/include-head)
     * stamps the CSRF token and rotates it from each response by itself - nothing to do here.
     */
    $(function () {
        var root = $('#seller-support');
        if (!root.length) {
            return;
        }

        var urls = {
            list:   root.data('list-url'),
            thread: root.data('thread-url'),
            create: root.data('create-url'),
            reply:  root.data('reply-url'),
            status: root.data('status-url')
        };

        var LIMIT = 10;
        var offset = 0;
        var total = 0;
        var currentTicket = null;
        var searchTimer = null;

        function toast(message, isError) {
            if (typeof iziToast !== 'undefined') {
                iziToast[isError ? 'error' : 'success']({ title: isError ? 'Error' : 'Success', message: message, position: 'topRight' });
            } else {
                alert(message);
            }
        }

        function esc(value) {
            // Server-side values already arrive html_escape()d; this guards the few strings
            // built locally (dates, counts) so nothing can be double-handled unsafely.
            return $('<div>').text(value == null ? '' : value).html();
        }

        /* ------------------------------- list ------------------------------- */
        function loadList() {
            $.ajax({
                url: urls.list,
                type: 'GET',
                dataType: 'json',
                data: {
                    limit: LIMIT,
                    offset: offset,
                    search: $('#ss-search').val(),
                    status: $('#ss-status-filter').val()
                }
            }).done(function (res) {
                if (!res || res.error) {
                    $('#ss-tbody').html('<tr><td colspan="7" class="text-center text-muted py-4">' + esc(res && res.message ? res.message : 'Could not load your tickets.') + '</td></tr>');
                    return;
                }
                total = res.total || 0;
                renderList(res.rows || []);
            }).fail(function () {
                $('#ss-tbody').html('<tr><td colspan="7" class="text-center text-muted py-4">Could not load your tickets.</td></tr>');
            });
        }

        function renderList(rows) {
            if (!rows.length) {
                $('#ss-tbody').html('<tr><td colspan="7" class="text-center text-muted py-4">No tickets yet. Use <b>New Ticket</b> to raise one.</td></tr>');
            } else {
                var html = '';
                rows.forEach(function (r) {
                    html += '<tr>'
                        + '<td>#' + r.id + '</td>'
                        + '<td>' + (r.ticket_type || '<span class="text-muted">&mdash;</span>') + '</td>'
                        + '<td>' + r.subject
                        + (r.unread > 0 ? ' <span class="badge badge-danger ml-1">' + r.unread + ' new</span>' : '')
                        + '</td>'
                        + '<td class="text-center"><span class="badge badge-' + esc(r.status_class) + '">' + esc(r.status_label) + '</span></td>'
                        + '<td class="text-center">' + r.replies + '</td>'
                        + '<td>' + esc(r.last_updated) + '</td>'
                        + '<td class="text-center"><button type="button" class="btn btn-xs btn-theme ss-open" data-id="' + r.id + '"><i class="fas fa-eye"></i> View</button></td>'
                        + '</tr>';
                });
                $('#ss-tbody').html(html);
            }

            var from = total ? offset + 1 : 0;
            var to = Math.min(offset + LIMIT, total);
            $('#ss-count').text(total ? ('Showing ' + from + '-' + to + ' of ' + total) : '');
            $('#ss-prev').prop('disabled', offset <= 0);
            $('#ss-next').prop('disabled', offset + LIMIT >= total);
        }

        $('#ss-prev').on('click', function () {
            offset = Math.max(0, offset - LIMIT);
            loadList();
        });
        $('#ss-next').on('click', function () {
            if (offset + LIMIT < total) {
                offset += LIMIT;
                loadList();
            }
        });
        $('#ss-status-filter').on('change', function () {
            offset = 0;
            loadList();
        });
        $('#ss-search').on('input', function () {
            // Debounced: one request per pause, not one per keystroke.
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                offset = 0;
                loadList();
            }, 350);
        });

        /* ------------------------------ thread ------------------------------ */
        $(document).on('click', '.ss-open', function () {
            openThread($(this).data('id'));
        });

        $('#ss-back-btn').on('click', function () {
            currentTicket = null;
            $('#ss-thread-view').hide();
            $('#ss-list-view').show();
            loadList();
        });

        function openThread(ticketId) {
            $.ajax({ url: urls.thread, type: 'GET', dataType: 'json', data: { ticket_id: ticketId } })
                .done(function (res) {
                    if (!res || res.error) {
                        toast(res && res.message ? res.message : 'Could not open the ticket.', true);
                        return;
                    }
                    currentTicket = res.ticket;
                    renderThread(res.ticket, res.data || []);
                    $('#ss-list-view').hide();
                    $('#ss-thread-view').show();
                })
                .fail(function () { toast('Could not open the ticket.', true); });
        }

        function renderThread(ticket, messages) {
            $('#ss-thread-subject').html('#' + ticket.id + ' &middot; ' + ticket.subject);
            $('#ss-thread-meta').html((ticket.ticket_type || 'Uncategorised') + ' &middot; raised ' + esc(ticket.date_created) + ' &middot; ');
            $('#ss-thread-status').html('<span class="badge badge-' + esc(ticket.status_class) + '">' + esc(ticket.status_label) + '</span>');

            var actions = '';
            if (ticket.can_close) {
                actions += '<button type="button" class="btn btn-sm btn-outline-success ss-status" data-action="resolve">Mark Resolved</button>';
            }
            if (ticket.can_reopen) {
                actions += '<button type="button" class="btn btn-sm btn-outline-warning ss-status" data-action="reopen">Reopen</button>';
            }
            $('#ss-thread-actions').html(actions);

            $('#ss-thread-first').html(
                '<div class="ss-msg ss-msg--mine">'
                + '<div class="ss-msg__head">You <span class="text-muted">&middot; ' + esc(ticket.date_created) + '</span></div>'
                + '<div class="ss-msg__body">' + ticket.description + '</div>'
                + '</div>'
            );

            var html = '';
            messages.forEach(function (m) {
                var attachments = '';
                (m.attachments || []).forEach(function (a) {
                    if (a.type === 'image') {
                        attachments += '<a href="' + esc(a.url) + '" target="_blank" rel="noopener"><img src="' + esc(a.url) + '" class="ss-msg__img" alt=""></a>';
                    } else {
                        attachments += '<a href="' + esc(a.url) + '" target="_blank" rel="noopener" class="badge badge-light border mr-1"><i class="fas fa-paperclip"></i> ' + esc(a.name) + '</a>';
                    }
                });
                html += '<div class="ss-msg ' + (m.from_support ? 'ss-msg--support' : 'ss-msg--mine') + '">'
                    + '<div class="ss-msg__head">' + (m.from_support ? 'Support Team' : 'You') + ' <span class="text-muted">&middot; ' + esc(m.date_created) + '</span></div>'
                    + '<div class="ss-msg__body">' + m.message + (attachments ? '<div class="mt-2">' + attachments + '</div>' : '') + '</div>'
                    + '</div>';
            });
            $('#ss-thread-messages').html(html);

            $('#ss-reply-ticket-id').val(ticket.id);
            $('#ss-reply-message').val('');
            $('#ss-reply-files').val('');
            $('#ss-reply-form').toggle(!!ticket.can_reply);
            $('#ss-closed-notice').toggle(!ticket.can_reply);
        }

        /* ------------------------------- create ------------------------------- */
        $('#ss-new-btn').on('click', function () {
            $('#ss-new-form')[0].reset();
            $('#ss-new-modal').modal('show');
        });

        $('#ss-new-form').on('submit', function (e) {
            e.preventDefault();
            var btn = $('#ss-new-submit').prop('disabled', true).text('Creating...');
            $.ajax({ url: urls.create, type: 'POST', dataType: 'json', data: $(this).serialize() })
                .done(function (res) {
                    if (!res || res.error) {
                        toast(res && res.message ? res.message : 'Could not create the ticket.', true);
                        return;
                    }
                    $('#ss-new-modal').modal('hide');
                    toast(res.message, false);
                    offset = 0;
                    loadList();
                    if (res.ticket_id) {
                        openThread(res.ticket_id);
                    }
                })
                .fail(function () { toast('Could not create the ticket.', true); })
                // always: a failed submit must not leave the button stuck on "Creating...".
                .always(function () { btn.prop('disabled', false).text('Create Ticket'); });
        });

        /* ------------------------------- reply ------------------------------- */
        $('#ss-reply-form').on('submit', function (e) {
            e.preventDefault();
            if (!currentTicket) {
                return;
            }
            var files = $('#ss-reply-files')[0].files;
            if (files && files.length > 3) {
                toast('You can attach at most 3 files.', true);
                return;
            }
            var data = new FormData(this);
            var btn = $('#ss-reply-submit').prop('disabled', true);
            $.ajax({ url: urls.reply, type: 'POST', dataType: 'json', data: data, processData: false, contentType: false })
                .done(function (res) {
                    if (!res || res.error) {
                        toast(res && res.message ? res.message : 'Could not send your reply.', true);
                        return;
                    }
                    toast(res.message, false);
                    // Re-fetch rather than appending locally: a reply can also change the
                    // ticket's status (PENDING -> OPENED, RESOLVED -> REOPENED), and the
                    // header/actions have to follow it.
                    openThread(currentTicket.id);
                })
                .fail(function () { toast('Could not send your reply.', true); })
                .always(function () { btn.prop('disabled', false); });
        });

        /* ------------------------------- status ------------------------------- */
        $(document).on('click', '.ss-status', function () {
            if (!currentTicket) {
                return;
            }
            var action = $(this).data('action');
            var btn = $(this).prop('disabled', true);
            $.ajax({ url: urls.status, type: 'POST', dataType: 'json', data: { ticket_id: currentTicket.id, action: action } })
                .done(function (res) {
                    if (!res || res.error) {
                        toast(res && res.message ? res.message : 'Could not update the ticket.', true);
                        return;
                    }
                    toast(res.message, false);
                    openThread(currentTicket.id);
                })
                .fail(function () { toast('Could not update the ticket.', true); })
                .always(function () { btn.prop('disabled', false); });
        });

        /* Deep link from a notification email: ?ticket_id=N opens that thread directly. */
        loadList();
        var openId = parseInt(root.data('open-ticket'), 10);
        if (openId > 0) {
            openThread(openId);
        }
    });
</script>

<style>
    .seller-support-page .text-primary-theme { color: var(--color-orange); }

    .seller-support-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .seller-support-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .seller-support-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
        background: var(--color-orange);
    }

    .seller-support-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .seller-support-page table.table tbody td { vertical-align: middle; font-size: 14px; }
    .seller-support-page table.table tbody tr:hover { background-color: var(--color-orange-light); }

    .btn-theme, .seller-support-page .btn-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
    }
    .btn-theme:hover, .seller-support-page .btn-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }
    .seller-support-page .btn-xs { padding: 2px 8px; font-size: 12px; }

    /* Conversation: the seller's own messages on the right, support on the left, so a long
       thread can be scanned without reading every header. */
    .seller-support-page .ss-messages { display: flex; flex-direction: column; }
    .seller-support-page .ss-msg {
        max-width: 78%;
        margin-bottom: 12px;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.5;
        word-break: break-word;
    }
    .seller-support-page .ss-msg--mine { align-self: flex-end; background: var(--color-orange-light); border: 1px solid rgba(0,0,0,0.05); }
    .seller-support-page .ss-msg--support { align-self: flex-start; background: #f4f6f9; border: 1px solid rgba(0,0,0,0.05); }
    .seller-support-page .ss-msg__head { font-size: 12px; font-weight: 600; margin-bottom: 4px; }
    .seller-support-page .ss-msg__img { max-width: 160px; max-height: 160px; border-radius: 8px; margin-right: 6px; }
    .seller-support-page .ss-first-message .ss-msg { max-width: 100%; align-self: stretch; }
    .seller-support-page #ss-thread-actions .btn { margin-left: 6px; }
</style>
