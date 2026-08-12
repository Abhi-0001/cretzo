/*
 * Firebase phone-OTP password reset, shared by the customer, seller and admin portals.
 *
 * WHY THIS EXISTS: this site has no server-side SMS gateway - settings.sms_gateway_settings
 * is '{}' and authentication_method is "firebase", so OTP texts are sent and confirmed by
 * Firebase in the browser (which is what registration already does). The password-reset
 * screens only knew about the server-side send_sms() path plus an email fallback, so on
 * this configuration they could never deliver anything and always ended in
 * "We could not deliver your OTP right now".
 *
 * All three portals happen to use the same element ids, so one implementation drives them
 * all; only the endpoint URLs differ. The page supplies those via window.FIREBASE_RESET_CONFIG:
 *
 *   { checkUrl, resetUrl, redirectUrl, recaptchaId, defaultDialCode }
 *
 * The server re-verifies the resulting ID token (signature, audience, expiry, that the
 * sign-in provider really was 'phone', and that the token's phone claim matches the account
 * being reset) - nothing asserted here is trusted.
 */
(function (window, document) {
    'use strict';

    var cfg = window.FIREBASE_RESET_CONFIG;
    if (!cfg || !cfg.resetUrl || !window.jQuery) {
        return;
    }

    var $ = window.jQuery;
    var confirmationResult = null;

    function digits(raw) {
        var d = String(raw || '').replace(/\D+/g, '');
        return d.length > 10 ? d.slice(-10) : d;
    }

    /**
     * E.164 number to hand Firebase. The customer modal decorates the input with
     * intl-tel-input, so honour the country the user actually picked instead of assuming
     * India; everything else falls back to the configured default dial code.
     */
    function e164() {
        var $input = $('#forgot_password_number');

        if (window.intlTelInputGlobals && typeof window.intlTelInputGlobals.getInstance === 'function') {
            var iti = window.intlTelInputGlobals.getInstance($input[0]);
            if (iti && typeof iti.getNumber === 'function') {
                var full = iti.getNumber();
                if (full) {
                    return full;
                }
            }
        }

        return (cfg.defaultDialCode || '+91') + digits($input.val());
    }

    function setMsg(sel, text, ok) {
        $(sel).removeClass('text-danger text-success')
              .addClass(ok ? 'text-success' : 'text-danger')
              .html($('<div>').text(text).html())
              .show();
    }

    function recaptcha() {
        if (window.recaptchaVerifier) {
            return window.recaptchaVerifier;
        }
        window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier(
            cfg.recaptchaId || 'recaptcha-password-reset',
            { size: 'invisible' }
        );
        return window.recaptchaVerifier;
    }

    function resetRecaptcha() {
        // Without clearing it, a second attempt silently no-ops.
        try {
            if (window.recaptchaVerifier && window.recaptchaVerifier.clear) {
                window.recaptchaVerifier.clear();
            }
        } catch (e) { /* already torn down */ }
        window.recaptchaVerifier = null;
    }

    /* ---------------------------------------------------------------- send OTP */

    $(document).on('submit', '#send_forgot_password_otp_form', function (e) {
        e.preventDefault();
        // Stops the legacy server-side-OTP handler in the theme bundle / page script from
        // also firing. This file is loaded before those, so it is bound first and this
        // call prevents the rest.
        e.stopImmediatePropagation();

        var $btn = $('#forgot_password_send_otp_btn');
        var label = $btn.html();
        var mobile = digits($('#forgot_password_number').val());

        setMsg('#forgot_pass_error_box', '', false);

        if (mobile.length !== 10) {
            setMsg('#forgot_pass_error_box', 'Please enter a valid 10-digit mobile number.', false);
            return;
        }

        $btn.html('Please Wait...').attr('disabled', true);

        // Check the account exists BEFORE spending a Firebase SMS (they are metered and
        // rate-limited per number), and so the "that's a seller/admin account, reset it
        // over there" guidance still reaches the user.
        $.post(cfg.checkUrl, { mobile_number: mobile }, function (pre) {
            if (pre.error) {
                $btn.html(label).attr('disabled', false);
                setMsg('#forgot_pass_error_box', pre.message, false);
                return;
            }

            firebase.auth().signInWithPhoneNumber(e164(), recaptcha())
                .then(function (result) {
                    confirmationResult = result;
                    $btn.html(label).attr('disabled', false);
                    setMsg('#forgot_pass_error_box', 'OTP sent to ' + e164() + '.', true);
                    $('#verify_forgot_password_otp_form').removeClass('d-none');
                    $('#send_forgot_password_otp_form').hide();
                })
                .catch(function (err) {
                    $btn.html(label).attr('disabled', false);
                    resetRecaptcha();
                    setMsg('#forgot_pass_error_box',
                        (err && err.message) ? err.message : 'Could not send the OTP. Please try again.', false);
                });
        }, 'json').fail(function () {
            $btn.html(label).attr('disabled', false);
            setMsg('#forgot_pass_error_box', 'Something went wrong. Please try again.', false);
        });
    });

    /* ------------------------------------------------------- verify + set password */

    $(document).on('submit', '#verify_forgot_password_otp_form', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $btn = $('#reset_password_submit_btn');
        var label = $btn.html();
        var otp = $('#forgot_password_otp').val();
        var newPassword = $('#verify_forgot_password_otp_form input[name="new_password"]').val();

        setMsg('#set_password_error_box', '', false);

        if (!confirmationResult) {
            setMsg('#set_password_error_box', 'Please request an OTP first.', false);
            return;
        }
        if (!newPassword || newPassword.length < 6) {
            setMsg('#set_password_error_box', 'Password must be at least 6 characters.', false);
            return;
        }

        $btn.html('Please Wait...').attr('disabled', true);

        confirmationResult.confirm(otp).then(function (result) {
            return result.user.getIdToken();
        }).then(function (idToken) {
            $.post(cfg.resetUrl, {
                mobile_number: digits($('#forgot_password_number').val()),
                id_token: idToken,
                new_password: newPassword
            }, function (res) {
                $btn.html(label).attr('disabled', false);
                setMsg('#set_password_error_box', res.message, !res.error);
                if (!res.error) {
                    setTimeout(function () {
                        if (cfg.redirectUrl) {
                            window.location.href = cfg.redirectUrl;
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                }
            }, 'json').fail(function () {
                $btn.html(label).attr('disabled', false);
                setMsg('#set_password_error_box', 'Something went wrong. Please try again.', false);
            });
        }).catch(function (err) {
            $btn.html(label).attr('disabled', false);
            setMsg('#set_password_error_box',
                (err && err.message) ? err.message : 'That OTP is not valid. Please check and try again.', false);
        });
    });
})(window, document);
