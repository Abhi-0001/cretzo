/* ==========================================================================
 * My Account > Profile.
 *
 * The "Edit details" popup is submitted by custom.js's delegated
 * `.form-submit-event` handler - it already appends the CSRF pair, swaps the
 * button label, renders the JSON message into #error_box and reloads on
 * success, and reusing it means the endpoint and its validation are untouched.
 *
 * The "Change password" popup is submitted HERE instead, because that shared
 * handler is bound to a single #error_box and calls
 * $(".form-submit-event")[0].reset() - which would reach the details form, not
 * this one. csrf-guard.js stamps the token onto every same-origin non-GET
 * jQuery request and rotates it from each response, so nothing below handles
 * CSRF explicitly.
 * ========================================================================== */
$(function () {
    /* ?edit=1 opens the details popup straight away, so a link from anywhere
       else ("Edit profile", "Add a photo") can land the user in the form rather
       than on the summary they then have to click through. */
    var params = new URLSearchParams(window.location.search);
    if (params.get('edit') === '1' && window.CzAccount) {
        CzAccount.open('#czap-profile-modal');
    }

    var $form = $('#czap-password-form');
    if (!$form.length) {
        return;
    }

    var $msg = $('#czap-password-msg');
    var $btn = $('#czap-password-submit');
    var busyLabel = '<i class="uil uil-spinner-alt"></i> Updating...';
    var idleLabel = $btn.html();

    function say(message, ok) {
        $msg
            .removeClass('czap-alert--ok czap-alert--bad')
            .addClass('czap-alert ' + (ok ? 'czap-alert--ok' : 'czap-alert--bad'))
            .html('<i class="uil ' + (ok ? 'uil-check-circle' : 'uil-exclamation-circle') + '"></i><span>' + message + '</span>')
            .show();
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        var oldPass = $('#old').val();
        var newPass = $('#new').val();
        var confirmPass = $('#new_confirm').val();

        /* Checked here as well as on the server so the obvious mistakes cost no
           round trip. Login::update_user() re-validates all three regardless -
           it is the only thing that can check the CURRENT password. */
        if (!oldPass || !newPass || !confirmPass) {
            say('Please fill in all three password fields.', false);
            return;
        }
        if (newPass !== confirmPass) {
            say('The new password and its confirmation do not match.', false);
            return;
        }
        if (newPass === oldPass) {
            say('Your new password must be different from the current one.', false);
            return;
        }

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: new FormData(this),
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $msg.hide();
                $btn.html(busyLabel).prop('disabled', true).addClass('is-busy');
            },
            success: function (res) {
                /* The endpoint echoes fresh CSRF values on every response and the
                   theme keeps them in these globals; other handlers on the page
                   (address, cancel-order) read them, so they have to be updated
                   here too or the next non-jQuery-guarded POST would 403. */
                if (res && res.csrfName) {
                    window.csrfName = res.csrfName;
                    window.csrfHash = res.csrfHash;
                }

                if (!res || res.error) {
                    /* validation_errors() returns markup, and ion_auth->errors()
                       returns a plain sentence - both are safe to inject, and both
                       are already the message the shared profile form displays. */
                    say((res && res.message) ? res.message : 'Could not update your password.', false);
                    $btn.html(idleLabel).prop('disabled', false).removeClass('is-busy');
                    return;
                }

                say('Password updated. Reloading...', true);
                $form[0].reset();
                setTimeout(function () {
                    window.location.reload();
                }, 1200);
            },
            /* Without this a rejected POST - a 403 from an expired session, a 500 -
               left the button reading "Updating..." for ever with nothing said. */
            error: function (xhr) {
                say(xhr.status === 403
                    ? 'Your session expired. Please reload the page and try again.'
                    : 'Something went wrong. Please try again.', false);
                $btn.html(idleLabel).prop('disabled', false).removeClass('is-busy');
            }
        });
    });
});
