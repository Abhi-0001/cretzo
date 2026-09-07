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
    // Set once the code has been confirmed on the stepped UI, so step 3 can mint a
    // fresh ID token without re-confirming (a confirmationResult is single-use).
    var verifiedUser = null;

    /*
     * The customer modal runs the three-screen flow (Mobile -> Verify -> Password) and
     * supplies showForgotStep()/enterForgotOtpStep() from custom.js along with the
     * markup for it. The seller and admin reset screens still use the original two
     * screens, where the OTP and the new password are submitted together. Both live
     * here, chosen by whether the stepped UI is actually on the page - this file is
     * shared by all three portals, so it cannot assume either.
     */
    function steppedUi() {
        return typeof window.showForgotStep === 'function' && $('#forgot_password_verify_btn').length > 0;
    }

    // Sends (or resends) the code and hands back the promise, so the submit handler and
    // the Resend button cannot drift apart.
    function sendFirebaseOtp() {
        return firebase.auth().signInWithPhoneNumber(e164(), recaptcha());
    }

    // The last leg, shared by both flows: swap the verified token for a new password.
    function postNewPassword(tokenPromise, newPassword, $btn, label) {
        Promise.resolve(tokenPromise).then(function (idToken) {
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
                (err && err.message) ? err.message : 'Could not reset your password. Please try again.', false);
        });
    }

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

            sendFirebaseOtp()
                .then(function (result) {
                    confirmationResult = result;
                    verifiedUser = null;
                    $btn.html(label).attr('disabled', false);

                    if (steppedUi()) {
                        // Step 2 owns the "we sent it to X" line, the six boxes and the
                        // resend cooldown; enterForgotOtpStep() sets all three up.
                        window.enterForgotOtpStep(e164());
                        return;
                    }

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

    /* ----------------------------------------------------------------- resend OTP */

    // custom.js has its own Resend handler that asks the SERVER for a code; on this
    // configuration there is no SMS gateway, so it must not be the one that runs.
    // Bound here (this file loads first) and suppressed there, exactly as with the
    // handlers around it.
    $(document).on('click', '#forgot-resend-otp', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true).text('Sending...');

        sendFirebaseOtp().then(function (result) {
            confirmationResult = result;
            verifiedUser = null;
            $btn.text('Resend OTP');
            if (typeof window.resetOtpGroup === 'function') {
                window.resetOtpGroup('#forgot-otp-boxes');
            }
            if (typeof window.showForgotOtpNotice === 'function') {
                window.showForgotOtpNotice('A new code is on its way to ' + e164() + '.');
            }
            if (window.forgotResendCooldown) {
                window.forgotResendCooldown.start(30);
            }
        }).catch(function (err) {
            $btn.prop('disabled', false).text('Resend OTP');
            resetRecaptcha();
            if (window.forgotResendCooldown) {
                window.forgotResendCooldown.stop();
            }
            if (typeof window.showForgotOtpError === 'function') {
                window.showForgotOtpError((err && err.message) ? err.message : 'Could not resend the code. Please try again.');
            }
        });
    });

    // Changing the number invalidates the code that was sent to the old one.
    $(document).on('click', '#forgot-back-to-mobile', function () {
        confirmationResult = null;
        verifiedUser = null;
    });

    /* ------------------------------------------------------------ verify the code */

    // Step 2 of the stepped UI. Confirming here rather than at the end is the whole
    // point of the extra screen: a wrong digit is reported before a password has been
    // chosen. custom.js has a handler on this button too, for the non-Firebase
    // configuration, which this suppresses.
    $(document).on('click', '#forgot_password_verify_btn', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $btn = $(this);
        var label = $btn.html();
        var otp = (typeof window.syncOtpGroup === 'function')
            ? window.syncOtpGroup('#forgot-otp-boxes')
            : $('#forgot_password_otp').val();

        function fail(message) {
            if (typeof window.showForgotOtpError === 'function') {
                window.showForgotOtpError(message);
            } else {
                setMsg('#forgot_otp_error_box', message, false);
            }
        }

        if (!confirmationResult) { fail('Please request an OTP first.'); return; }
        if (!otp) { fail('Please enter the OTP we sent you.'); return; }
        if (otp.length < 6) { fail('Please enter all 6 digits of the code.'); return; }

        $btn.html('Please Wait...').attr('disabled', true);

        confirmationResult.confirm(otp).then(function (result) {
            verifiedUser = result.user;
            $btn.html(label).attr('disabled', false);
            $('#forgot_otp_error_box, #set_password_error_box').html('');
            if (window.forgotResendCooldown) {
                window.forgotResendCooldown.stop();
            }
            window.showForgotStep(3);
            setTimeout(function () { $('#forgot_password_new_password').trigger('focus'); }, 50);
        }).catch(function (err) {
            $btn.html(label).attr('disabled', false);
            fail((err && err.message) ? err.message : 'That OTP is not valid. Please check and try again.');
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

        // On the stepped UI the code was already confirmed on step 2, so this screen
        // only has to check the two password boxes and mint a fresh token from the user
        // that confirmation produced - a confirmationResult cannot be confirmed twice.
        if (steppedUi()) {
            var confirmPassword = $('#forgot_password_confirm_password').val();

            if (!verifiedUser) {
                setMsg('#set_password_error_box', 'Please verify the OTP first.', false);
                return;
            }
            if (!newPassword || newPassword.length < 6) {
                setMsg('#set_password_error_box', 'Password must be at least 6 characters.', false);
                return;
            }
            if (newPassword !== confirmPassword) {
                setMsg('#set_password_error_box', 'Passwords do not match !', false);
                return;
            }

            $btn.html('Please Wait...').attr('disabled', true);
            postNewPassword(verifiedUser.getIdToken(), newPassword, $btn, label);
            return;
        }

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
