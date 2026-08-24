<?php
/*
 * Customer notifications.
 *
 * The previous version of this page fed a bootstrap-table from
 * `admin/Notification_settings/get_notification_list` - an ADMIN endpoint - and rendered a single
 * combined "full_notification" HTML blob per row, with no read state, no click-through and no way
 * to reach the page in the first place (the header bell was a static image with a hardcoded 0).
 * It now reads the customer's own endpoints and uses the same account layout as the rest of
 * My Account.
 */
?>
<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <p class="text-n"><?= (isset($user) && is_object($user) && isset($user->username)) ? html_escape($user->username) : '' ?></p>
    <div class="overview-container">

        <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>

        <div class="overview-right">

            <h1 class="heading-n overview-right-heading">Notifications
                <br><span class="text-s op-6">Order updates, support replies and offers</span>
            </h1>

            <div class="cs-notif" id="cs-notif"
                 data-list-url="<?= base_url('my-account/get-notifications') ?>"
                 data-read-url="<?= base_url('my-account/mark-notification-read') ?>">

                <div class="cs-notif__bar">
                    <div class="cs-notif__filters">
                        <select id="cs-notif-filter" class="cs-notif__select" aria-label="Filter notifications">
                            <option value="">All notifications</option>
                            <option value="1">Unread only</option>
                        </select>
                    </div>
                    <button type="button" class="cs-notif__primary" id="cs-notif-read-all">
                        <i class="uil uil-check-circle"></i> Mark all as read
                    </button>
                </div>

                <div id="cs-notif-list">
                    <div class="cs-notif__empty">Loading...</div>
                </div>

                <div class="cs-notif__pager">
                    <small class="op-6" id="cs-notif-count"></small>
                    <div>
                        <button type="button" class="cs-notif__page" id="cs-notif-prev" disabled>Previous</button>
                        <button type="button" class="cs-notif__page" id="cs-notif-next" disabled>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    /* csrf-guard.js stamps the token onto every same-origin non-GET jQuery request and rotates
       it from each response, so nothing here handles CSRF explicitly. */
    $(function () {
        var root = $('#cs-notif');
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
                url: listUrl,
                type: 'GET',
                dataType: 'json',
                data: { limit: LIMIT, offset: offset, unread: $('#cs-notif-filter').val() }
            }).done(function (res) {
                if (!res || res.error) {
                    $('#cs-notif-list').html('<div class="cs-notif__empty">' + esc(res && res.message ? res.message : 'Could not load your notifications.') + '</div>');
                    return;
                }
                total = res.total || 0;
                render(res.rows || []);
                updateBell(res.unread);
            }).fail(function () {
                $('#cs-notif-list').html('<div class="cs-notif__empty">Could not load your notifications.</div>');
            });
        }

        function render(rows) {
            if (!rows.length) {
                $('#cs-notif-list').html('<div class="cs-notif__empty">You have no notifications yet.</div>');
            } else {
                var html = '';
                rows.forEach(function (n) {
                    // title/message are already html_escape()d server-side.
                    var img = n.image ? '<img class="cs-notif__img" src="' + esc(n.image) + '" alt="">' : '';
                    var open = n.link
                        ? '<a class="cs-notif__link" href="' + esc(n.link) + '">View <i class="uil uil-arrow-right"></i></a>'
                        : '';
                    html += '<div class="cs-notif__item' + (n.is_read ? '' : ' cs-notif__item--unread') + '" data-id="' + n.id + '">'
                        + img
                        + '<div class="cs-notif__body">'
                        + '<div class="cs-notif__head">'
                        + '<span class="cs-notif__title">' + n.title + '</span>'
                        + (n.is_read ? '' : '<span class="cs-notif__dot" title="Unread"></span>')
                        + '</div>'
                        + '<p class="cs-notif__msg">' + n.message + '</p>'
                        + '<div class="cs-notif__meta">'
                        + '<span class="cs-notif__type">' + n.type_label + '</span>'
                        + '<span class="cs-notif__date">' + esc(n.date_sent) + '</span>'
                        + open
                        + (n.is_read ? '' : '<button type="button" class="cs-notif__mark" data-id="' + n.id + '">Mark read</button>')
                        + '</div>'
                        + '</div>'
                        + '</div>';
                });
                $('#cs-notif-list').html(html);
            }

            var from = total ? offset + 1 : 0;
            var to = Math.min(offset + LIMIT, total);
            $('#cs-notif-count').text(total ? ('Showing ' + from + '-' + to + ' of ' + total) : '');
            $('#cs-notif-prev').prop('disabled', offset <= 0);
            $('#cs-notif-next').prop('disabled', offset + LIMIT >= total);
        }

        // Keeps the header bell in step without a page reload.
        function updateBell(unread) {
            if (typeof unread === 'undefined') {
                return;
            }
            $('.js-notif-count').text(unread > 99 ? '99+' : unread);
        }

        // The failure branches matter: with no .fail() a rejected POST (a 403 from a missing
        // CSRF token, an expired session) left the button looking dead - nothing changed and
        // nothing was said. Surface it instead.
        function notifError(msg) {
            var box = $('#cs-notif-error');
            if (!box.length) {
                box = $('<div class="cs-notif__error" id="cs-notif-error"></div>').prependTo('#cs-notif-list');
            }
            box.text(msg).show();
        }

        function markRead(id) {
            $.ajax({ url: readUrl, type: 'POST', dataType: 'json', data: id ? { notification_id: id } : {} })
                .done(function (res) {
                    if (res && !res.error) {
                        updateBell(res.unread);
                        load();
                    } else {
                        notifError(res && res.message ? res.message : 'Could not update the notification.');
                    }
                })
                .fail(function (xhr) {
                    notifError(xhr.status === 403
                        ? 'Your session expired. Please reload the page and try again.'
                        : 'Could not update the notification. Please try again.');
                });
        }

        $(document).on('click', '.cs-notif__mark', function () {
            markRead($(this).data('id'));
        });
        $('#cs-notif-read-all').on('click', function () {
            markRead(null);
        });
        $('#cs-notif-filter').on('change', function () {
            offset = 0;
            load();
        });
        $('#cs-notif-prev').on('click', function () {
            offset = Math.max(0, offset - LIMIT);
            load();
        });
        $('#cs-notif-next').on('click', function () {
            if (offset + LIMIT < total) {
                offset += LIMIT;
                load();
            }
        });

        // Opening a notification counts as reading it, but the click must not be swallowed -
        // fire the mark and let the browser follow the link.
        $(document).on('click', '.cs-notif__link', function () {
            var id = $(this).closest('.cs-notif__item').data('id');
            if (id) {
                $.ajax({ url: readUrl, type: 'POST', dataType: 'json', data: { notification_id: id } });
            }
        });

        load();
    });
</script>

<style>
    .cs-notif__bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .cs-notif__select {
        padding: 8px 12px;
        border: 1px solid rgba(0, 0, 0, .12);
        border-radius: 8px;
        background: #fff;
        font-size: 14px;
    }
    .cs-notif__primary {
        border: none;
        border-radius: 8px;
        padding: 9px 16px;
        background: var(--color-orange);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
    }
    .cs-notif__primary:hover { background: var(--color-orange-dark); }

    .cs-notif__error {
        border: 1px solid rgba(220, 53, 69, .35);
        background: rgba(220, 53, 69, .07);
        color: #b02a37;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .cs-notif__item {
        display: flex;
        gap: 12px;
        padding: 14px;
        border: 1px solid rgba(0, 0, 0, .07);
        border-radius: 10px;
        margin-bottom: 10px;
        background: #fff;
    }
    /* Unread is carried by a left border AND a dot, not colour alone. */
    .cs-notif__item--unread {
        border-left: 3px solid var(--color-orange);
        background: var(--color-orange-light);
    }
    .cs-notif__img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; flex: none; }
    .cs-notif__body { flex: 1; min-width: 0; }
    .cs-notif__head { display: flex; align-items: center; gap: 8px; }
    .cs-notif__title { font-weight: 600; font-size: 15px; }
    .cs-notif__dot { width: 8px; height: 8px; border-radius: 50%; background: var(--color-orange); flex: none; }
    .cs-notif__msg { margin: 4px 0 8px; font-size: 14px; line-height: 1.5; word-break: break-word; }
    .cs-notif__meta { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; font-size: 12px; opacity: .75; }
    .cs-notif__type {
        text-transform: uppercase;
        letter-spacing: .3px;
        font-weight: 600;
        background: rgba(0, 0, 0, .05);
        border-radius: 20px;
        padding: 2px 10px;
    }
    .cs-notif__link { color: var(--color-orange); text-decoration: none; font-weight: 600; }
    .cs-notif__mark {
        border: 1px solid rgba(0, 0, 0, .15);
        background: #fff;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 12px;
        cursor: pointer;
    }
    .cs-notif__empty { padding: 40px 10px; text-align: center; opacity: .6; font-size: 14px; }
    .cs-notif__pager { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; }
    .cs-notif__page {
        border: 1px solid rgba(0, 0, 0, .15);
        background: #fff;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 13px;
        cursor: pointer;
    }
    .cs-notif__page:disabled { opacity: .45; cursor: default; }
</style>
