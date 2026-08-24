<?php
/*
 * Seller notifications.
 *
 * The seller panel had no notification surface at all. Reads the same `notifications` table as
 * the customer side, scoped by Notification_model::get_user_inbox() to broadcasts this seller is
 * an audience for plus anything addressed to them by id.
 */
?>
<div class="content-wrapper seller-notif-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-bell mr-2 text-primary-theme"></i>Notifications</h4>
                    <p class="text-muted mb-0 small">Announcements from the platform team, plus your order and ticket updates.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Notifications</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid"
             id="seller-notif"
             data-list-url="<?= base_url('seller/notifications/get_notifications') ?>"
             data-read-url="<?= base_url('seller/notifications/mark_read') ?>">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set mr-2"><i class="fas fa-bell"></i></span>
                    <h5 class="mb-0">My Notifications</h5>
                    <div class="ml-auto d-flex align-items-center" style="gap:8px;">
                        <select class="form-control form-control-sm" id="sn-filter" style="width:auto;">
                            <option value="">All</option>
                            <option value="1">Unread only</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-theme" id="sn-read-all">
                            <i class="fas fa-check-double mr-1"></i> Mark all as read
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="sn-list">
                        <div class="sn-empty">Loading...</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted" id="sn-count"></small>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="sn-prev" disabled>Previous</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="sn-next" disabled>Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    $(function () {
        var root = $('#seller-notif');
        if (!root.length) {
            return;
        }
        var listUrl = root.data('list-url');
        var readUrl = root.data('read-url');
        var LIMIT = 10;
        var offset = 0;
        var total = 0;

        function esc(v) {
            return $('<div>').text(v == null ? '' : v).html();
        }

        function load() {
            $.ajax({
                url: listUrl, type: 'GET', dataType: 'json',
                data: { limit: LIMIT, offset: offset, unread: $('#sn-filter').val() }
            }).done(function (res) {
                if (!res || res.error) {
                    $('#sn-list').html('<div class="sn-empty">' + esc(res && res.message ? res.message : 'Could not load your notifications.') + '</div>');
                    return;
                }
                total = res.total || 0;
                render(res.rows || []);
                setBell(res.unread);
            }).fail(function () {
                $('#sn-list').html('<div class="sn-empty">Could not load your notifications.</div>');
            });
        }

        function render(rows) {
            if (!rows.length) {
                $('#sn-list').html('<div class="sn-empty">No notifications yet.</div>');
            } else {
                var html = '';
                rows.forEach(function (n) {
                    // title/message arrive html_escape()d from the server.
                    var img = n.image ? '<img class="sn-img" src="' + esc(n.image) + '" alt="">' : '';
                    var open = n.link ? '<a class="sn-link" href="' + esc(n.link) + '">View <i class="fas fa-arrow-right"></i></a>' : '';
                    html += '<div class="sn-item' + (n.is_read ? '' : ' sn-item--unread') + '" data-id="' + n.id + '">'
                        + img
                        + '<div class="sn-body">'
                        + '<div class="sn-head"><span class="sn-title">' + n.title + '</span>'
                        + (n.is_read ? '' : '<span class="badge badge-danger ml-2">NEW</span>') + '</div>'
                        + '<p class="sn-msg">' + n.message + '</p>'
                        + '<div class="sn-meta">'
                        + '<span class="sn-type">' + n.type_label + '</span>'
                        + '<span>' + esc(n.date_sent) + '</span>'
                        + open
                        + (n.is_read ? '' : '<button type="button" class="btn btn-xs btn-outline-secondary sn-mark" data-id="' + n.id + '">Mark read</button>')
                        + '</div></div></div>';
                });
                $('#sn-list').html(html);
            }

            var from = total ? offset + 1 : 0;
            var to = Math.min(offset + LIMIT, total);
            $('#sn-count').text(total ? ('Showing ' + from + '-' + to + ' of ' + total) : '');
            $('#sn-prev').prop('disabled', offset <= 0);
            $('#sn-next').prop('disabled', offset + LIMIT >= total);
        }

        // Keeps the navbar bell in step without reloading the panel.
        function setBell(unread) {
            if (typeof unread === 'undefined') {
                return;
            }
            var badge = $('#seller-notif-badge');
            badge.text(unread > 99 ? '99+' : unread);
            badge.toggle(unread > 0);
        }

        function markRead(id) {
            $.ajax({ url: readUrl, type: 'POST', dataType: 'json', data: id ? { notification_id: id } : {} })
                .done(function (res) {
                    if (res && !res.error) {
                        setBell(res.unread);
                        load();
                    }
                });
        }

        $(document).on('click', '.sn-mark', function () { markRead($(this).data('id')); });
        $('#sn-read-all').on('click', function () { markRead(null); });
        $('#sn-filter').on('change', function () { offset = 0; load(); });
        $('#sn-prev').on('click', function () { offset = Math.max(0, offset - LIMIT); load(); });
        $('#sn-next').on('click', function () { if (offset + LIMIT < total) { offset += LIMIT; load(); } });

        // Opening a notification counts as reading it; fire and let the link follow.
        $(document).on('click', '.sn-link', function () {
            var id = $(this).closest('.sn-item').data('id');
            if (id) {
                $.ajax({ url: readUrl, type: 'POST', dataType: 'json', data: { notification_id: id } });
            }
        });

        load();
    });
</script>

<style>
    .seller-notif-page .text-primary-theme { color: var(--color-orange); }
    .seller-notif-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .seller-notif-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .seller-notif-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
        background: var(--color-orange);
    }
    .seller-notif-page .btn-theme { background: var(--color-orange); border-color: var(--color-orange); color: #fff; }
    .seller-notif-page .btn-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .seller-notif-page .btn-xs { padding: 2px 8px; font-size: 12px; }

    .seller-notif-page .sn-item {
        display: flex;
        gap: 12px;
        padding: 14px;
        border: 1px solid rgba(0,0,0,.07);
        border-radius: 10px;
        margin-bottom: 10px;
        background: #fff;
    }
    /* Unread is carried by a left border AND a NEW badge, not colour alone. */
    .seller-notif-page .sn-item--unread { border-left: 3px solid var(--color-orange); background: var(--color-orange-light); }
    .seller-notif-page .sn-img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; flex: none; }
    .seller-notif-page .sn-body { flex: 1; min-width: 0; }
    .seller-notif-page .sn-title { font-weight: 600; font-size: 15px; }
    .seller-notif-page .sn-msg { margin: 4px 0 8px; font-size: 14px; line-height: 1.5; word-break: break-word; }
    .seller-notif-page .sn-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; font-size: 12px; color: #6c757d; }
    .seller-notif-page .sn-type {
        text-transform: uppercase;
        letter-spacing: .3px;
        font-weight: 600;
        background: rgba(0,0,0,.05);
        border-radius: 20px;
        padding: 2px 10px;
    }
    .seller-notif-page .sn-link { color: var(--color-orange); text-decoration: none; font-weight: 600; }
    .seller-notif-page .sn-empty { padding: 40px 10px; text-align: center; color: #6c757d; font-size: 14px; }
</style>
