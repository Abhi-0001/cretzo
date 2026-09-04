<?php
/**
 * My Account > Notifications.
 *
 * Rebuilt on the shared account shell. The data path is unchanged - it still
 * reads my-account/get-notifications and posts to my-account/mark-notification-read,
 * the customer's own endpoints with real per-user read state.
 *
 * (For the record of where this came from: the version before that fed a
 * bootstrap-table from `admin/Notification_settings/get_notification_list` - an
 * ADMIN url - rendered one combined "full_notification" HTML blob per row, had
 * no read state and no click-through, and could not be reached at all because
 * the header bell was a static image with a hardcoded 0.)
 *
 * New here: a notification opens in a popup rather than only linking away, so a
 * long message is readable without leaving the page, and opening one marks it
 * read.
 */

/* --------------------------------------------------------------- content -- */
ob_start(); ?>

<div class="czap-notif" id="cs-notif"
     data-list-url="<?= base_url('my-account/get-notifications') ?>"
     data-read-url="<?= base_url('my-account/mark-notification-read') ?>">

    <div class="czap-toolbar">
        <div class="czap-radios" role="tablist" aria-label="Filter notifications">
            <label class="czap-radio is-checked">
                <input type="radio" name="cs-notif-scope" value="" checked>
                <i class="uil uil-list-ul"></i> All
            </label>
            <label class="czap-radio">
                <input type="radio" name="cs-notif-scope" value="1">
                <i class="uil uil-envelope-dot"></i> Unread only
            </label>
        </div>
        <span style="margin-left:auto"></span>
        <button type="button" class="czap-btn czap-btn--ghost" id="cs-notif-read-all">
            <i class="uil uil-check-circle"></i> Mark all as read
        </button>
    </div>

    <div id="cs-notif-list" aria-live="polite">
        <div class="czap-empty" style="padding:40px 20px">
            <p class="czap-empty__text" style="margin:0">Loading your notifications...</p>
        </div>
    </div>

    <div class="czap-pager">
        <span class="czap-pager__info" id="cs-notif-count"></span>
        <div class="czap-pager__btns">
            <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm" id="cs-notif-prev" disabled>
                <i class="uil uil-angle-left-b"></i> Previous
            </button>
            <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm" id="cs-notif-next" disabled>
                Next <i class="uil uil-angle-right-b"></i>
            </button>
        </div>
    </div>
</div>

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Notifications',
    'page_sub'     => 'Order updates, support replies and offers',
    'page_icon'    => 'uil-bell',
    'page_content' => $page_content,
]);
?>

<!-- ==================== POPUP: read one notification ==================== -->
<div class="czap-modal" id="czap-notif-modal" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="czap-notif-modal-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">
        <div class="czap-modal__head">
            <div style="min-width:0">
                <h2 class="czap-modal__title" id="czap-notif-modal-title">
                    <i class="uil uil-bell"></i> <span id="czap-notif-title"></span>
                </h2>
                <p class="czap-modal__sub" id="czap-notif-meta"></p>
            </div>
            <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
        </div>
        <div class="czap-modal__body">
            <img id="czap-notif-image" src="" alt=""
                 style="display:none;width:100%;max-height:260px;object-fit:cover;border-radius:12px;margin-bottom:16px">
            <div id="czap-notif-body" style="font-size:15px;line-height:1.7;word-break:break-word"></div>
        </div>
        <div class="czap-modal__foot">
            <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Close</button>
            <a class="czap-btn czap-btn--primary" id="czap-notif-link" href="#" style="display:none">
                Open <i class="uil uil-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<style>
    /* Notification rows. Everything else on the page comes from
       account-suite.css; these are the only shapes specific to this list. */
    .czap-notif__item {
        display: flex;
        gap: 14px;
        padding: 16px;
        border: 1px solid var(--czap-line);
        border-radius: var(--czap-r);
        margin-bottom: 10px;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .czap-notif__item:hover {
        border-color: var(--czap-orange-line);
        box-shadow: var(--czap-shadow);
    }

    /* Unread is carried by the left bar AND the dot AND the weight, never by
       colour alone. */
    .czap-notif__item--unread {
        border-left: 3px solid var(--czap-orange);
        background: var(--czap-orange-soft);
    }

    .czap-notif__img {
        width: 52px;
        height: 52px;
        object-fit: cover;
        border-radius: 10px;
        flex: none;
    }

    .czap-notif__icon {
        width: 44px;
        height: 44px;
        flex: none;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: var(--czap-line-2);
        color: var(--czap-ink-3);
        font-size: 20px;
    }

    .czap-notif__item--unread .czap-notif__icon {
        background: #fff;
        color: var(--czap-orange);
    }

    .czap-notif__body { flex: 1; min-width: 0; }

    .czap-notif__head {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .czap-notif__title {
        font-weight: 600;
        font-size: 15.5px;
        color: var(--czap-ink);
        min-width: 0;
    }

    .czap-notif__item--unread .czap-notif__title { font-weight: 700; }

    .czap-notif__dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--czap-orange);
        flex: none;
    }

    .czap-notif__msg {
        margin: 4px 0 10px;
        font-size: 14px;
        line-height: 1.55;
        color: var(--czap-ink-2);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .czap-notif__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: var(--czap-ink-3);
    }

    .czap-notif__type {
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        background: rgba(0, 0, 0, .05);
        border-radius: var(--czap-r-pill);
        padding: 3px 10px;
    }
</style>

<script>
    /* csrf-guard.js stamps the token onto every same-origin non-GET jQuery request
       and rotates it from each response, so nothing here handles CSRF explicitly. */
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
        var rowsById = {};

        function esc(v) {
            return $('<div>').text(v == null ? '' : v).html();
        }

        function load() {
            $.ajax({
                url: listUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    limit: LIMIT,
                    offset: offset,
                    unread: $('input[name="cs-notif-scope"]:checked').val()
                }
            }).done(function (res) {
                if (!res || res.error) {
                    fail(res && res.message ? res.message : 'Could not load your notifications.');
                    return;
                }
                total = res.total || 0;
                render(res.rows || []);
                updateBell(res.unread);
            }).fail(function () {
                fail('Could not load your notifications.');
            });
        }

        function fail(message) {
            $('#cs-notif-list').html(
                '<div class="czap-alert czap-alert--bad">' +
                '<i class="uil uil-exclamation-circle"></i><span>' + esc(message) + '</span></div>'
            );
        }

        function emptyState() {
            var unreadOnly = $('input[name="cs-notif-scope"]:checked').val() === '1';
            return '<div class="czap-empty">' +
                '<div class="czap-empty__icon"><i class="uil ' + (unreadOnly ? 'uil-check-circle' : 'uil-bell-slash') + '"></i></div>' +
                '<h3 class="czap-empty__title">' + (unreadOnly ? 'Nothing unread' : 'No notifications yet') + '</h3>' +
                '<p class="czap-empty__text">' + (unreadOnly
                    ? 'You are all caught up. Switch to All to see the ones you have already read.'
                    : 'Order updates, replies to your support tickets and offers will appear here.') +
                '</p></div>';
        }

        function render(rows) {
            rowsById = {};

            if (!rows.length) {
                $('#cs-notif-list').html(emptyState());
            } else {
                var html = '';
                rows.forEach(function (n) {
                    rowsById[n.id] = n;
                    /* title/message are already html_escape()d server-side, so they
                       are inserted as-is; everything else is escaped here. */
                    var thumb = n.image
                        ? '<img class="czap-notif__img" src="' + esc(n.image) + '" alt="">'
                        : '<div class="czap-notif__icon"><i class="uil uil-bell"></i></div>';

                    html += '<div class="czap-notif__item' + (n.is_read ? '' : ' czap-notif__item--unread') + '"' +
                        ' data-id="' + n.id + '" role="button" tabindex="0">' +
                        thumb +
                        '<div class="czap-notif__body">' +
                        '<div class="czap-notif__head">' +
                        '<span class="czap-notif__title">' + n.title + '</span>' +
                        (n.is_read ? '' : '<span class="czap-notif__dot" title="Unread"></span>') +
                        '</div>' +
                        '<p class="czap-notif__msg">' + n.message + '</p>' +
                        '<div class="czap-notif__meta">' +
                        '<span class="czap-notif__type">' + esc(n.type_label) + '</span>' +
                        '<span>' + esc(n.date_sent) + '</span>' +
                        '<span style="margin-left:auto;color:var(--czap-orange);font-weight:600">Read <i class="uil uil-angle-right"></i></span>' +
                        '</div></div></div>';
                });
                $('#cs-notif-list').html(html);
            }

            var from = total ? offset + 1 : 0;
            var to = Math.min(offset + LIMIT, total);
            $('#cs-notif-count').text(total ? ('Showing ' + from + '-' + to + ' of ' + total) : '');
            $('#cs-notif-prev').prop('disabled', offset <= 0);
            $('#cs-notif-next').prop('disabled', offset + LIMIT >= total);
        }

        /* Keeps the header bell in step without a page reload. */
        function updateBell(unread) {
            if (typeof unread === 'undefined') {
                return;
            }
            $('.js-notif-count').text(unread > 99 ? '99+' : unread);
            $('.czap-nav__count').attr('data-count', unread).text(unread > 99 ? '99+' : unread);
        }

        /* The failure branches matter: with no .fail() a rejected POST (a 403 from
           a missing CSRF token, an expired session) left the button looking dead -
           nothing changed and nothing was said. */
        function markRead(id, then) {
            /* CSRF protection is on site-wide and this url is not excluded, so a
               post without the token is rejected with a 403 before it reaches the
               controller - which is exactly what made "Mark all as read" dead. The
               token pair is published globally by include-css.php and refreshed from
               every response, the same way the cart/address posts do it. */
            var payload = id ? { notification_id: id } : {};
            if (typeof csrfName !== 'undefined' && csrfName) {
                payload[csrfName] = csrfHash;
            }

            $.ajax({ url: readUrl, type: 'POST', dataType: 'json', data: payload })
                .done(function (res) {
                    if (res && res.csrfHash) {
                        csrfName = res.csrfName;
                        csrfHash = res.csrfHash;
                    }
                    if (res && !res.error) {
                        updateBell(res.unread);
                        if (then) {
                            then();
                        } else {
                            load();
                        }
                    } else {
                        CzAccount.toast((res && res.message) ? res.message : 'Could not update the notification.', 'error');
                    }
                })
                .fail(function (xhr) {
                    CzAccount.toast(xhr.status === 403
                        ? 'Your session expired. Please reload the page and try again.'
                        : 'Could not update the notification. Please try again.', 'error');
                });
        }

        /* ---- open one in the popup ---- */
        function openNotification(id) {
            var n = rowsById[id];
            if (!n) {
                return;
            }

            $('#czap-notif-title').html(n.title);
            $('#czap-notif-meta').text(n.type_label + ' · ' + n.date_sent);
            $('#czap-notif-body').html(n.message);

            var $img = $('#czap-notif-image');
            if (n.image) {
                $img.attr('src', n.image).show();
            } else {
                $img.hide().attr('src', '');
            }

            var $link = $('#czap-notif-link');
            if (n.link) {
                $link.attr('href', n.link).show();
            } else {
                $link.hide();
            }

            CzAccount.open('#czap-notif-modal');

            /* Opening it counts as reading it. The list is refreshed on close
               rather than immediately, so the row does not shift under the popup. */
            if (!n.is_read) {
                markRead(id, function () {
                    n.is_read = 1;
                    $('.czap-notif__item[data-id="' + id + '"]')
                        .removeClass('czap-notif__item--unread')
                        .find('.czap-notif__dot').remove();
                });
            }
        }

        $(document).on('click', '.czap-notif__item', function () {
            openNotification($(this).data('id'));
        });

        /* The rows are role="button", so they have to answer the keyboard too. */
        $(document).on('keydown', '.czap-notif__item', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.which === 13 || e.which === 32) {
                e.preventDefault();
                openNotification($(this).data('id'));
            }
        });

        $('#cs-notif-read-all').on('click', function () {
            CzAccount.confirm({
                title: 'Mark everything as read?',
                text: 'This clears the unread badge on all of your notifications.',
                confirmText: 'Mark all read',
                icon: 'uil-check-circle'
            }).then(function (ok) {
                if (ok) {
                    markRead(null);
                }
            });
        });

        $('input[name="cs-notif-scope"]').on('change', function () {
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

        load();
    });
</script>
