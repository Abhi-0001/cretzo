/*
 * Two-step approval on the Add/Edit System User form.
 *
 * The form is submitted normally first. When the server decides the change needs approval it
 * replies { otp_required: true }. This script then asks Firebase to text a code to the approver's
 * mobile, shows the code box, and resubmits the same form with the Firebase ID token attached.
 * The server re-verifies the token (its phone claim must equal the approver's number), so nothing
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

    var cooldownTimer = null;
    function startCooldown() {
        var left = 30;
        var $b = $('#su_resend_btn').prop('disabled', true);
        clearInterval(cooldownTimer);
        $b.text('Resend in ' + left + 's');
        cooldownTimer = setInterval(function () {
            left--;
            if (left <= 0) {
                clearInterval(cooldownTimer);
                $b.prop('disabled', false).text('Resend code');
            } else {
                $b.text('Resend in ' + left + 's');
            }
        }, 1000);
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
            startCooldown();
            if (!keepMessage) { msg('Code sent. Enter it below.', true); }
        }).catch(function (err) {
            resetRecaptcha();
            msg('Could not send the SMS code: ' + ((err && err.message) || 'unknown error'), false);
        });
    }

    function post(withCodes, $btn, label) {
        var fd = new FormData(form);
        fd.append(window.csrfName, window.csrfHash);
        if (withCodes) {
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
        $('#su_sms_otp').val('');
        post(false, $('#submit_btn'), $('#submit_btn').text());
    });

    $('#su_verify_btn').on('click', function () {
        var $btn = $(this);
        var label = $btn.text();
        var sms = $.trim($('#su_sms_otp').val());
        if (!sms) {
            msg('Enter the SMS code.', false);
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
        $('#su_sms_otp').val('');
        msg('Sending a new code...', true);
        sendSms();
    });

    $modal.on('shown.bs.modal', function () { $('#su_sms_otp').trigger('focus'); });
    $('#su_sms_otp').on('input', function () { this.value = this.value.replace(/\D+/g, ''); })
        .on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#su_verify_btn').trigger('click'); } });

    $('#su_cancel_btn').on('click', function () {
        resetRecaptcha();
        confirmation = null;
        idToken = '';
        $modal.modal('hide');
    });
});
