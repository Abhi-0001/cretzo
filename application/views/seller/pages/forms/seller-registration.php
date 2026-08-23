<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cretzo - Seller Signup</title>

    <?php // This page builds its own <head> rather than including the shared one, so the
          // CSRF token has to be emitted here too. The signup form carries a hidden token
          // field of its own, but the page's other AJAX calls (send_otp/verify_otp) do not -
          // csrf-guard.js stamps those. ?>
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name() ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash() ?>">
    <script src="<?= add_ver(base_url('assets/csrf-guard.js')) ?>"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400&display=swap"
        rel="stylesheet">
    <?php // add_ver() appends ?v=<filemtime>. WITHOUT it the browser kept serving the copy of
          // seller-auth.css it had cached before the multi-step redesign - which already
          // carried the card and button styling, so the page looked fine while the rules that
          // arrived with the redesign silently did nothing: .step1/.step2/.step3 {display:none}
          // and .step-indicator {display:flex}. Every step rendered at once and the indicator
          // came out as the bare text "1Details 2Verify 3Password". seller/login.php was
          // already cache-busting this same file; this page was the one that wasn't. ?>
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/admin/css/seller-auth.css')) ?>">
</head>

<body>

    <div class="login-container">
        <div class="brand-section">
            <div class="logo-area">
                <a href="/"><img src="<?= base_url() . $logo ?>" alt="Cretzo logo"></a>
            </div>

            <div class="illustration">
                <img src="<?= base_url() ?>/assets/logo/handloon.png" alt="Handmade Artist">
            </div>

            <h2 class="mission-text">Empowering Handmade Artist Worldwide</h2>

            <?php if (!empty($launch_offer_active)) : ?>
            <div class="launch-offer-banner" role="note">
                <span class="lo-icon" aria-hidden="true">&#127881;</span>
                <span class="lo-text"><strong>Launch Offer</strong>First 100 vendors get 50 free listings for 1 year</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-section">
            <h2 class="form-title">Create Seller Account </h2>

            <div class="step-indicator" aria-hidden="true">
                <div class="step-dot active" id="step_dot_1" data-step="1"><span>1</span><label>Details</label></div>
                <div class="step-line"></div>
                <div class="step-dot" id="step_dot_2" data-step="2"><span>2</span><label>Verify</label></div>
                <div class="step-line"></div>
                <div class="step-dot" id="step_dot_3" data-step="3"><span>3</span><label>Password</label></div>
            </div>

            <form class="form-submit-event" action="<?= base_url('seller/auth/ajax_signup') ?>" method="post">
            <input type='hidden' name='<?= $this->security->get_csrf_token_name() ?>' value='<?= $this->security->get_csrf_hash() ?>'>

            <!-- STEP 1: account details -->
            <div class="step1 active">

                <div class="input-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Full Name">
                    <span class="error-message error_name"></span>
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Email Address">
                    <span class="error-message error_email"></span>
                </div>

                <div class="input-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="tel" name="mobile" id="mobile" placeholder="Mobile Number" maxlength="10" pattern="[0-9]*" inputmode="numeric">
                    <span class="error-message error_mobile"></span>
                </div>

                <input type="hidden" name="phone_verified" id="phone_verified" value="0">
                <input type="hidden" name="firebase_uid" id="firebase_uid" value="">
                <?php // Server-verifiable proof of phone ownership - see _owns_existing_account(). ?>
                <input type="hidden" name="firebase_id_token" id="firebase_id_token" value="">
                <input type="hidden" name="firebase_phone" id="firebase_phone" value="">
                <div id="recaptcha-registration"></div>

                <button type="button" class="btn" id="send_otp" style="margin-top: 15px;">Send OTP</button>
            </div>

            <!-- STEP 2: OTP verification -->
            <div class="step2">

                <div class="otp-visual" aria-hidden="true">&#128241;</div>
                <h3 class="otp-heading">Verify your mobile number</h3>
                <p class="otp-subtext">We've sent a 6-digit code to <strong id="otp_mobile_display"></strong></p>

                <div class="otp-boxes" id="otp_boxes">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                </div>
                <input type="hidden" name="otp" id="otp">

                <span class="error-message error_otp" style="text-align:center;"></span>
                <span class="success-message success_otp" style="text-align:center;"></span>

                <div class="otp-resend-row">
                    <span class="send-otp" id="resend_otp">Resend OTP</span>
                    <span class="resend-timer" id="resend_timer"></span>
                </div>

                <button type="button" class="btn" id="verify_otp" style="margin-top: 15px;">Verify OTP</button>

                <p class="step-back"><a href="#" id="back_to_step1">&larr; Change mobile number</a></p>
            </div>

            <!-- STEP 3: password -->
            <div class="step3">

                <div class="input-group">
                    <label for="password">Create Password</label>
                    <?php // .password-wrapper / .toggle-password are styled by seller-auth.css, which
                          // this page already loads - same show/hide control as the login form. ?>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Create Password">
                        <button type="button" class="toggle-password" aria-label="Show password" onclick="togglePassword(this)">
                            <svg class="eye-on" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <span class="error-message error_password"></span>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password">
                        <button type="button" class="toggle-password" aria-label="Show password" onclick="togglePassword(this)">
                            <svg class="eye-on" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <span class="error-message error_confirm_password"></span>
                </div>

                <span class="success-message success_signup" style="text-align:center;"></span>
                <button type="submit" class="btn" style="margin-top: 15px;">Sign Up</button>
            </div>

        </form>

            <div class="signup-prompt">
                <p>Already have an account? <a href="<?= base_url('seller/auth/login') ?>">Login</a></p>
            </div>
        </div>


    </div>

</body>
    <script>
        function togglePassword(btn) {
            var input = btn.parentElement.querySelector('input');
            var showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            btn.classList.toggle('is-visible', showing);
            btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
        }
    </script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-auth.js"></script>
    <script src="<?= base_url() ?>firebase-config.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function () {

    const base_url = "<?= base_url() ?>";
    const isLocalHost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

    if (isLocalHost && firebase && firebase.auth && firebase.auth().settings) {
        firebase.auth().settings.appVerificationDisabledForTesting = true;
    }

    if (firebase && firebase.apps && firebase.apps.length) {
        console.log('Firebase app options:', firebase.app().options);
    } else {
        console.warn('Firebase not initialized');
    }

    var resendTimerInterval = null;

    /* Restrict mobile field to digits */
    $("#mobile").on('keydown', function(e) {
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            (e.keyCode >= 35 && e.keyCode <= 40)) {
            return;
        }
        if ((e.ctrlKey || e.metaKey) &&
            (e.keyCode === 65 || e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88)) {
            return;
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) &&
            (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    /* Clear previous errors */
    function clearErrors() {
        $(".error-message").removeClass('show').text('');
        $(".success-message").removeClass('show').text('');
    }

    /* Create/Re-create Recaptcha Verifier */
    function createRecaptcha() {
        $('#recaptcha-registration').html('');
        if (window.recaptchaVerifier) {
            try {
                window.recaptchaVerifier.clear();
            } catch(e) { }
            window.recaptchaVerifier = null;
        }
        if (window.grecaptcha && window.recaptchaWidgetId !== undefined) {
            try {
                grecaptcha.reset(window.recaptchaWidgetId);
            } catch(ex) { }
        }
        window.recaptchaWidgetId = undefined;

        function build() {
            window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-registration', {
                'size': 'invisible',
                'callback': function(response) {}
            });
            return window.recaptchaVerifier.render().then(function(widgetId) {
                window.recaptchaWidgetId = widgetId;
                if (window.grecaptcha && typeof grecaptcha.reset === 'function') {
                    try { grecaptcha.reset(widgetId); } catch (ex) { }
                }
                return window.recaptchaVerifier;
            });
        }

        return new Promise(function(resolve, reject) {
            setTimeout(function() {
                build().then(resolve).catch(function(err) {
                    if (err && err.message && err.message.indexOf('already been rendered') !== -1) {
                        console.warn('Recaptcha render conflict');
                        $('#recaptcha-registration').html('');
                        try { window.recaptchaVerifier.clear(); } catch(e){ }
                        window.recaptchaVerifier = null;
                        window.recaptchaWidgetId = undefined;
                        setTimeout(function() {
                            build().then(resolve).catch(reject);
                        }, 100);
                    } else {
                        reject(err);
                    }
                });
            }, 50);
        });
    }

    /* Validate Step 1 Fields */
    function validateStep1() {
        clearErrors();
        let name = $("#name").val().trim();
        let email = $("#email").val().trim();
        let mobile = $("#mobile").val().trim().replace(/\D/g, '');
        let hasError = false;

        if (name.length === 0) {
            $(".error_name").addClass('show').text("Full name is required");
            $("#name").focus();
            hasError = true;
        }

        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRe.test(email)) {
            $(".error_email").addClass('show').text("Valid email address required");
            if (!hasError) $("#email").focus();
            hasError = true;
        }

        if (!/^[6-9]\d{9}$/.test(mobile)) {
            $(".error_mobile").addClass('show').text("Valid 10-digit mobile number required");
            if (!hasError) $("#mobile").focus();
            hasError = true;
        }

        return !hasError;
    }

    /* Mask a 10-digit mobile number for display: 98******76 */
    function maskMobile(mobile) {
        if (mobile.length !== 10) return '+91 ' + mobile;
        return '+91 ' + mobile.slice(0, 2) + '••••••' + mobile.slice(-2);
    }

    function setActiveStep(step) {
        $('.step-dot').each(function () {
            var dotStep = parseInt($(this).data('step'), 10);
            $(this).toggleClass('active', dotStep === step).toggleClass('done', dotStep < step);
        });
    }

    function resetOtpBoxes() {
        $('.otp-box').val('');
        $('#otp').val('');
    }

    function syncOtpHidden() {
        var otp = '';
        $('.otp-box').each(function () { otp += $(this).val(); });
        $('#otp').val(otp);
    }

    function startResendCooldown(seconds) {
        seconds = seconds || 30;
        var $resend = $("#resend_otp");
        $resend.addClass('disabled').text('Resend OTP');
        if (resendTimerInterval) clearInterval(resendTimerInterval);
        var remaining = seconds;
        $("#resend_timer").text('Resend available in ' + remaining + 's');
        resendTimerInterval = setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                clearInterval(resendTimerInterval);
                resendTimerInterval = null;
                $("#resend_timer").text('');
                $resend.removeClass('disabled');
            } else {
                $("#resend_timer").text('Resend available in ' + remaining + 's');
            }
        }, 1000);
    }

    /* Request an OTP via Firebase, retrying once on a stale recaptcha verifier */
    function requestOtp(phoneNumber, isResend) {
        var authResetPromise = isResend ? firebase.auth().signOut() : Promise.resolve();

        return authResetPromise.then(function () {
            return createRecaptcha().then(function (appVerifier) {
                return firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier);
            });
        }).catch(function (error) {
            var msg = error && error.message ? error.message : '';
            if (msg.toLowerCase().indexOf('invalid application verifier') !== -1) {
                return createRecaptcha().then(function (appVerifier) {
                    return firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier);
                });
            }
            throw error;
        }).then(function (confirmationResult) {
            window.confirmationResult = confirmationResult;
            return confirmationResult;
        });
    }

    /* Surface a send-OTP failure next to the given error element */
    function showOtpSendError($errorTarget, error, $btn, btnDefaultText) {
        var code = error && error.code;
        var msg = error && error.message ? error.message : 'Failed to send OTP';

        if (code === 'auth/too-many-requests' || msg.toLowerCase().indexOf('unusual activity') !== -1) {
            $errorTarget.addClass('show').text("Too many attempts. Please wait a few minutes.");
            if ($btn) {
                $btn.prop('disabled', true).text('Please wait');
                setTimeout(function () {
                    $btn.prop('disabled', false).text(btnDefaultText);
                }, 60000);
            }
            return;
        }

        $errorTarget.addClass('show').text(msg);
    }

    /* STEP 1 -> STEP 2: send OTP, then move to the verification screen */
    $("#send_otp").click(function (e) {
        e.preventDefault();

        if (!validateStep1()) return;

        let mobile = $("#mobile").val().trim().replace(/\D/g, '');
        let phoneNumber = '+91' + mobile;
        let $btn = $(this);

        $btn.prop('disabled', true).text('Sending...');

        requestOtp(phoneNumber, false).then(function () {
            $btn.prop('disabled', false).text('Send OTP');
            clearErrors();
            resetOtpBoxes();
            $("#otp_mobile_display").text(maskMobile(mobile));

            $(".step1").removeClass("active");
            $(".step2").addClass("active");
            setActiveStep(2);
            startResendCooldown();
            setTimeout(function () { $('.otp-box').first().focus(); }, 50);
        }).catch(function (error) {
            $btn.prop('disabled', false).text('Send OTP');
            showOtpSendError($(".error_mobile"), error, $btn, 'Send OTP');
        });
    });

    /* Resend OTP from the verification screen */
    $("#resend_otp").click(function (e) {
        e.preventDefault();
        if ($(this).hasClass('disabled')) return;

        let mobile = $("#mobile").val().trim().replace(/\D/g, '');
        let phoneNumber = '+91' + mobile;
        let $resend = $(this);

        $(".error_otp, .success_otp").removeClass('show').text('');
        $resend.addClass('disabled').text('Sending...');

        requestOtp(phoneNumber, true).then(function () {
            $resend.text('Resend OTP');
            resetOtpBoxes();
            $(".success_otp").addClass('show').text('OTP resent to ' + phoneNumber);
            startResendCooldown();
            $('.otp-box').first().focus();
        }).catch(function (error) {
            $resend.removeClass('disabled').text('Resend OTP');
            showOtpSendError($(".error_otp"), error);
        });
    });

    /* OTP box behaviour: auto-advance, backspace to previous box, paste-to-fill */
    $(document).on('input', '.otp-box', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 1);
        if (this.value) {
            $(this).next('.otp-box').focus();
        }
        syncOtpHidden();
    });

    $(document).on('keydown', '.otp-box', function (e) {
        if (e.key === 'Backspace' && !this.value) {
            $(this).prev('.otp-box').focus();
        }
    });

    $(document).on('paste', '.otp-box', function (e) {
        var clipboard = e.originalEvent && e.originalEvent.clipboardData ? e.originalEvent.clipboardData : window.clipboardData;
        var text = clipboard ? clipboard.getData('text').replace(/\D/g, '') : '';
        if (!text) return;
        e.preventDefault();

        var $boxes = $('.otp-box');
        $boxes.each(function (i) { $(this).val(text[i] || ''); });
        syncOtpHidden();
        $boxes.eq(Math.min(text.length, $boxes.length - 1)).focus();
    });

    /* Change mobile number: back to step 1 */
    $("#back_to_step1").click(function (e) {
        e.preventDefault();
        clearErrors();
        if (resendTimerInterval) { clearInterval(resendTimerInterval); resendTimerInterval = null; }
        $("#resend_timer").text('');
        $("#resend_otp").removeClass('disabled').text('Resend OTP');
        window.confirmationResult = null;

        $(".step2").removeClass("active");
        $(".step1").addClass("active");
        setActiveStep(1);
        $("#mobile").focus();
    });

    /* STEP 2 -> STEP 3: verify OTP */
    $("#verify_otp").click(function (e) {
        e.preventDefault();
        $(".error_otp, .success_otp").removeClass('show').text('');
        syncOtpHidden();

        let otp = $("#otp").val().trim();

        if (otp.length !== 6) {
            $(".error_otp").addClass('show').text("Enter valid 6-digit OTP");
            $('.otp-box').filter(function () { return !this.value; }).first().focus();
            return;
        }

        if (!window.confirmationResult) {
            $(".error_otp").addClass('show').text("Please request OTP first");
            return;
        }

        $(this).prop('disabled', true).text('Verifying...');

        window.confirmationResult.confirm(otp).then(function (result) {
            var user = result.user;
            $("#phone_verified").val('1');
            $("#firebase_uid").val(user.uid || '');
            $("#firebase_phone").val(user.phoneNumber || $('#mobile').val());

            // Carry the signed ID token through to the server. phone_verified/firebase_uid
            // above are plain hidden fields the server cannot trust; this token is verified
            // server-side and is what lets an existing buyer add selling to their account
            // without re-entering their old password.
            user.getIdToken().then(function (idToken) {
                $("#firebase_id_token").val(idToken);
            }).catch(function () { /* password fallback still applies */ });

            if (resendTimerInterval) { clearInterval(resendTimerInterval); resendTimerInterval = null; }
            $("#resend_timer").text('');

            $(".step2").removeClass("active");
            $(".step3").addClass("active");
            setActiveStep(3);
            $("#verify_otp").prop('disabled', false).text('Verify OTP');
            $("#password").focus();
        }).catch(function (error) {
            $("#verify_otp").prop('disabled', false).text('Verify OTP');
            $(".error_otp").addClass('show').text(error.message || 'Invalid OTP');
        });
    });

    /* Validate Step 3 (password) fields */
    function validateStep3() {
        clearErrors();
        let password = $("#password").val();
        let confirm = $("#confirm_password").val();
        let hasError = false;

        if (password.length < 6) {
            $(".error_password").addClass('show').text("Minimum 6 characters required");
            $("#password").focus();
            hasError = true;
        }

        if (password !== confirm || confirm.length === 0) {
            $(".error_confirm_password").addClass('show').text("Passwords must match");
            if (!hasError) $("#confirm_password").focus();
            hasError = true;
        }

        return !hasError;
    }

    /* FINAL SUBMIT */
    $(".form-submit-event").submit(function (e) {
        e.preventDefault();

        if (!validateStep3()) return;

        let $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Creating Account...');

        $.post(base_url + "seller/auth/ajax_signup", $(this).serialize(), function (res) {
            try {
                if (res.status === "success") {
                    $(".success_signup").addClass('show').text("Account created successfully! Redirecting...");
                    setTimeout(function() {
                        window.location.href = base_url + "seller/home";
                    }, 1500);
                } else {
                    $btn.prop('disabled', false).text('Sign Up');
                    let errorMsg = res.message || 'Registration failed. Please try again.';
                    alert(errorMsg);
                }
            } catch(e) {
                $btn.prop('disabled', false).text('Sign Up');
                alert('An error occurred. Please try again.');
            }
        }, "json").fail(function(jqXHR, textStatus, errorThrown) {
            $btn.prop('disabled', false).text('Sign Up');
            console.error('Server error:', jqXHR, textStatus, errorThrown);
            alert('Server error: ' + (jqXHR.responseJSON?.message || errorThrown || 'Please try again'));
        });
    });

});
</script>
</html>
