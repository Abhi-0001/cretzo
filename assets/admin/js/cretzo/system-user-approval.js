/*
 * Two-step approval on the Add/Edit System User form.
 *
 * The form is submitted normally first. When the server decides the change needs approval it
 * replies { otp_required: true } and has already emailed a code to the approver. This script
 * then asks Firebase to text a second code to the approver's mobile, shows both inputs, and
 * resubmits the same form with the email code and the Firebase ID token attached. The server
 * re-verifies both (the token's phone claim must equal the approver's number), so nothing
 * here is trusted.
 *
 * Bound on the form itself with stopPropagation() so custom.js's generic delegated
 * .form-submit-event handler does not also post the form.
 */
window.addEventListener('load', function () {
    'use strict';

    var $ = window.jQuery;
    var form = document.getElementById('add_product_form');
    if (!$ || !form || !document.getElementById('su_approval_modal')) {
        return;
    }

    var $modal = $('#su_approval_modal');
    var confirmation = null;
    var idToken = '';
    var approverMobile = '';
    var recaptcha = null;

    function msg(text, ok) {
        $('#su_approval_msg').removeClass('text-danger text-success')
            .addClass(ok ? 'text-success' : 'text-danger').text(text || '');
    }

    function setCsrf(res) {
        if (res && res.csrfName) {
            window.csrfName = res.csrfName;
            window.csrfHash = res.csrfHash;
        }
    }

    function resetRecaptcha() {
        try { if (recaptcha && recaptcha.clear) { recaptcha.clear(); } } catch (e) { /* already gone */ }
        recaptcha = null;
        $('#su_recaptcha').empty();
    }

    function sendSms(keepMessage) {
        idToken = '';
        confirmation = null;
        resetRecaptcha();
        recaptcha = new firebase.auth.RecaptchaVerifier('su_recaptcha', { size: 'invisible' });
        return firebase.auth().signInWithPhoneNumber(approverMobile, recaptcha).then(function (c) {
            confirmation = c;
            if (!keepMessage) { msg('Codes sent. Enter both below.', true); }
        }).catch(function (err) {
            resetRecaptcha();
            msg('Could not send the SMS code: ' + ((err && err.message) || 'unknown error'), false);
        });
    }

    function post(withCodes, $btn, label) {
        var fd = new FormData(form);
        fd.append(window.csrfName, window.csrfHash);
        if (withCodes) {
            fd.append('email_otp', $.trim($('#su_email_otp').val()));
            fd.append('approval_id_token', idToken);
        }
        if ($btn) { $btn.prop('disabled', true).text('Please wait...'); }

        return $.ajax({
            type: 'POST', url: form.action, data: fd, dataType: 'json',
            cache: false, contentType: false, processData: false
        }).done(function (res) {
            setCsrf(res);
            if (!res.error) {
                $modal.modal('hide');
                iziToast.success({ message: res.message });
                setTimeout(function () { window.location.href = window.base_url + 'admin/system_users'; }, 900);
                return;
            }
            if (res.otp_required) {
                approverMobile = res.approver_mobile || approverMobile;
                $('#su_approver_mobile').text(approverMobile);
                $('#su_approver_email').text(res.approver_email_masked || '');
                if (!withCodes) {
                    $modal.modal({ backdrop: 'static', keyboard: false });
                    msg(res.message, true);
                    if (!confirmation) { sendSms(); }
                } else if (res.sms_failed) {
                    // The server refused that SMS verification, so a fresh code is needed.
                    msg(res.message + ' Sending a new SMS code...', false);
                    sendSms(true);
                } else {
                    msg(res.message, false);
                }
            } else {
                iziToast.error({ message: res.message });
                if (withCodes) { msg(res.message, false); }
            }
        }).fail(function (xhr) {
            var m = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
            iziToast.error({ message: m });
        }).always(function () {
            if ($btn) { $btn.prop('disabled', false).text(label); }
        });
    }

    $(form).on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        confirmation = null;
        idToken = '';
        $('#su_email_otp, #su_sms_otp').val('');
        post(false, $('#submit_btn'), $('#submit_btn').text());
    });

    $('#su_verify_btn').on('click', function () {
        var $btn = $(this);
        var label = $btn.text();
        var email = $.trim($('#su_email_otp').val());
        var sms = $.trim($('#su_sms_otp').val());
        if (!email || !sms) {
            msg('Enter both the email code and the SMS code.', false);
            return;
        }
        if (idToken) {
            post(true, $btn, label);
            return;
        }
        if (!confirmation) {
            msg('The SMS code has not been sent yet. Use Resend.', false);
            return;
        }
        $btn.prop('disabled', true).text('Please wait...');
        confirmation.confirm(sms).then(function (cred) {
            return cred.user.getIdToken();
        }).then(function (token) {
            idToken = token;
            return post(true, $btn, label);
        }).catch(function (err) {
            $btn.prop('disabled', false).text(label);
            msg('The SMS code is incorrect or expired. ' + ((err && err.message) || ''), false);
        });
    });

    $('#su_resend_btn').on('click', function () {
        $('#su_email_otp, #su_sms_otp').val('');
        msg('Sending new codes...', true);
        // Re-posting the form makes the server issue a fresh email code (it keeps the current
        // one if it was sent under 30s ago); clearing `confirmation` makes the reply re-send the SMS.
        confirmation = null;
        idToken = '';
        post(false, null, '');
    });

    $('#su_cancel_btn').on('click', function () {
        resetRecaptcha();
        confirmation = null;
        idToken = '';
        $modal.modal('hide');
    });
});
