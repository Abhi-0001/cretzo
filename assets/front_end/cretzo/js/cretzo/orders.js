/* ==========================================================================
 * My Account > Orders & returns.
 *
 * Search box, and the cancel/return popup.
 *
 * The cancel/return flow used to be custom.js's `.update-order` handler: a bare
 * window.confirm("Are you sure you want to Cancel this order ?") that named
 * neither the item nor the order. That handler is still bound to the
 * `.update-order` class, which this view no longer renders - the buttons here
 * are `.czap-order-action` and go through the popup below, which names the
 * product, shows the order number and states what the action does.
 *
 * csrf-guard.js stamps the token onto every same-origin non-GET jQuery request
 * and rotates it from each response, so nothing below handles CSRF explicitly -
 * but window.csrfName/csrfHash are still refreshed from the payload, because
 * other handlers on the page read those globals directly.
 * ========================================================================== */
$(function () {

    /* ------------------------------ search ------------------------------ */
    var $search = $('#czap-order-search');
    var $searchBtn = $('#czap-order-search-btn');

    function runSearch() {
        var query = $search.val().trim();
        /* An empty search returns to the unfiltered list rather than doing
           nothing, which is what the old handler did - it had `if (query !== "")`
           and so the Search button was dead once you cleared the box. The status
           filter is preserved so the two controls compose. */
        var status = new URLSearchParams(window.location.search).get('status');
        var params = new URLSearchParams();
        if (status) {
            params.set('status', status);
        }
        if (query !== '') {
            params.set('search', query);
        }
        var qs = params.toString();
        window.location.href = window.location.pathname + (qs ? '?' + qs : '');
    }

    if ($search.length) {
        $searchBtn.on('click', runSearch);
        $search.on('keydown', function (e) {
            if (e.key === 'Enter' || e.which === 13) {
                e.preventDefault();
                runSearch();
            }
        });
    }

    /* -------------------------- cancel / return -------------------------- */
    var $modal = $('#czap-order-modal');
    if (!$modal.length || !window.CzAccount) {
        return;
    }

    var pending = null;
    var $confirmBtn = $('#czap-order-confirm');
    var $msg = $('#czap-order-msg');

    var COPY = {
        cancelled: {
            heading: 'Cancel order',
            sub: 'This cannot be undone.',
            warning: 'Cancelling releases the item back to the seller. Any amount already paid is refunded to your Cretzo wallet.',
            confirm: 'Yes, cancel it',
            reason: false
        },
        returned: {
            heading: 'Return order',
            sub: 'We will arrange a pickup.',
            warning: 'The item must be unused and in its original packaging. The refund is issued once the seller has received it back.',
            confirm: 'Request return',
            reason: true,
            /* A return without a reason is a support ticket waiting to happen -
               the seller cannot prepare for the pickup and nobody can tell
               sizing problems from damage in transit. Asked for, and required. */
            reasonRequired: true
        }
    };

    function say(message, ok) {
        $msg
            .attr('class', 'czap-alert ' + (ok ? 'czap-alert--ok' : 'czap-alert--bad'))
            .html('<i class="uil ' + (ok ? 'uil-check-circle' : 'uil-exclamation-circle') + '"></i><span>' + message + '</span>')
            .show();
    }

    $(document).on('click', '.czap-order-action', function () {
        var $btn = $(this);
        var status = $btn.data('status');
        var copy = COPY[status];
        if (!copy) {
            return;
        }

        pending = {
            orderId: $btn.data('order-id'),
            status: status
        };

        $('#czap-order-heading').text(copy.heading);
        $('#czap-order-sub').text(copy.sub);
        $('#czap-order-warning').text(copy.warning);
        $('#czap-order-product').text($btn.data('product') || 'This item');
        $('#czap-order-number').text($btn.data('order-id'));
        $('#czap-order-reason-wrap').prop('hidden', !copy.reason);
        $('#czap-order-reason').val('');
        $confirmBtn.find('span').text(copy.confirm);
        $confirmBtn.prop('disabled', false).removeClass('is-busy');
        $msg.hide();

        CzAccount.open('#czap-order-modal');
    });

    $confirmBtn.on('click', function () {
        if (!pending) {
            return;
        }

        var copy = COPY[pending.status];
        var reason = $('#czap-order-reason').val() || '';

        if (copy.reasonRequired && reason === '') {
            say('Please pick a reason so we can arrange the right pickup.', false);
            $('#czap-order-reason').focus();
            return;
        }

        var payload = new FormData();
        payload.append('order_id', pending.orderId);
        payload.append('status', pending.status);
        /* Stored against every item of the order by My_account::update_order()
           (column added in migration 076), so returns can be grouped by product
           and by seller instead of only counted. */
        if (reason !== '') {
            payload.append('reason', reason);
        }
        /* csrf-guard.js cannot reach a FormData body, so the pair goes in by
           hand - the same way every other FormData POST in this theme does it. */
        payload.append(window.csrfName, window.csrfHash);

        var idle = $confirmBtn.html();

        $.ajax({
            type: 'POST',
            url: base_url + 'my-account/update-order',
            data: payload,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $msg.hide();
                $confirmBtn.html('<i class="uil uil-spinner-alt"></i> Please wait...')
                    .prop('disabled', true).addClass('is-busy');
            },
            success: function (res) {
                if (res && res.csrfName) {
                    window.csrfName = res.csrfName;
                    window.csrfHash = res.csrfHash;
                }

                if (!res || res.error) {
                    say((res && res.message) ? res.message : 'Could not update this order.', false);
                    $confirmBtn.html(idle).prop('disabled', false).removeClass('is-busy');
                    return;
                }

                say(res.message || 'Done. Reloading your orders...', true);
                setTimeout(function () {
                    window.location.reload();
                }, 1400);
            },
            /* The failure branch matters: without it a rejected POST - a 403 from
               an expired session, a 500 - left the button reading "Please wait..."
               for ever with nothing said. */
            error: function (xhr) {
                say(xhr.status === 403
                    ? 'Your session expired. Please reload the page and try again.'
                    : 'Something went wrong. Please try again.', false);
                $confirmBtn.html(idle).prop('disabled', false).removeClass('is-busy');
            }
        });
    });
});
