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
    <script src="<?= base_url('assets/csrf-guard.js') ?>"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/seller-auth.css') ?>">
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

            <form class="form-submit-event" action="<?= base_url('seller/auth/ajax_signup') ?>" method="post">
            <input type='hidden' name='<?= $this->security->get_csrf_token_name() ?>' value='<?= $this->security->get_csrf_hash() ?>'>

            <!-- STEP 1 -->
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
                    <div style="display: flex; gap: 10px; margin-top: 8px;">
                        <span class="send-otp" id="send_otp" style="flex: 1;">Send OTP</span>
                    </div>
                    <span class="error-message error_mobile"></span>
                    <span class="success-message success_mobile"></span>
                </div>

                <div class="input-group" style="display:none;" id="div_otp">
                    <label for="otp">Enter OTP</label>
                    <input type="text" name="otp" id="otp" placeholder="Enter OTP" maxlength="6" pattern="[0-9]*" inputmode="numeric">
                    <span class="error-message error_otp"></span>
                    <span class="success-message success_otp"></span>
                </div>

                    <input type="hidden" name="phone_verified" id="phone_verified" value="0">
                    <input type="hidden" name="firebase_uid" id="firebase_uid" value="">
                    <?php // Server-verifiable proof of phone ownership - see _owns_existing_account(). ?>
                    <input type="hidden" name="firebase_id_token" id="firebase_id_token" value="">
                    <input type="hidden" name="firebase_phone" id="firebase_phone" value="">
                    <div id="recaptcha-registration"></div>
                <button type="button" class="btn" id="verify_otp" style="margin-top: 15px;">Next</button>
            </div>

            <!-- STEP 2 -->
            <div class="step2">

                <div class="input-group">
                    <label for="password">Create Password</label>
                    <input type="password" name="password" id="password" placeholder="Create Password">
                    <span class="error-message error_password"></span>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password">
                    <span class="error-message error_confirm_password"></span>
                </div>

                <button type="submit" class="btn" style="margin-top: 15px;">Sign Up</button>
            </div>

        </form>

            <div class="signup-prompt">
                <p>Already have an account? <a href="<?= base_url('seller/auth/login') ?>">Login</a></p>
            </div>
        </div>


    </div>

</body>
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

    /* SEND OTP via Firebase */
    $("#send_otp").click(function (e) {
        e.preventDefault();
        
        if (!validateStep1()) return;

        let mobile = $("#mobile").val().trim().replace(/\D/g, '');
        let phoneNumber = '+91' + mobile;
        
        $(this).prop('disabled', true).text('Sending...');

        var isResend = $("#send_otp").text().includes('Resend');
        var authResetPromise = isResend ? firebase.auth().signOut() : Promise.resolve();

        authResetPromise.then(function() {
            return createRecaptcha().then(function(appVerifier) {
                return firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier);
            });
        }).then(function (confirmationResult) {
            window.confirmationResult = confirmationResult;
            $("#div_otp").show();
            $("#send_otp").text("Resend OTP").prop('disabled', false);
            $(".success_mobile").addClass('show').text("OTP sent to " + phoneNumber);
            $("#otp").focus();
        }).catch(function (error) {
            $("#send_otp").prop('disabled', false).text('Send OTP');
            var code = error && error.code;
            var msg = error && error.message ? error.message : 'Failed to send OTP';
            
            if (code === 'auth/too-many-requests' || msg.toLowerCase().includes('unusual activity')) {
                var message = "Too many attempts. Please wait a few minutes.";
                $(".error_mobile").addClass('show').text(message);
                $("#send_otp").prop('disabled', true).text('Please wait');
                setTimeout(function(){
                    $("#send_otp").prop('disabled', false).text('Send OTP');
                }, 60000);
                return;
            }

            if (msg && msg.toLowerCase().indexOf('invalid application verifier') !== -1) {
                createRecaptcha().then(function(appVerifier) {
                    return firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier);
                }).then(function(confirmationResult) {
                    window.confirmationResult = confirmationResult;
                    $("#div_otp").show();
                    $("#send_otp").text("Resend OTP").prop('disabled', false);
                    $(".success_mobile").addClass('show').text("OTP sent to " + phoneNumber);
                    $("#otp").focus();
                }).catch(function(err2){
                    $(".error_mobile").addClass('show').text(err2.message || msg);
                });
            } else {
                $(".error_mobile").addClass('show').text(msg);
            }
        });
    });

    /* VERIFY OTP and Move to Step 2 */
    $("#verify_otp").click(function (e) {
        e.preventDefault();
        clearErrors();

        let otp = $("#otp").val().trim().replace(/\D/g, '');
        $("#otp").val(otp);

        if (otp.length !== 6) {
            $(".error_otp").addClass('show').text("Enter valid 6-digit OTP");
            $("#otp").focus();
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


            $(".step1").removeClass("active");
            $(".step2").addClass("active");
            $("#verify_otp").prop('disabled', false).text('Next');
            $("#password").focus();
        }).catch(function (error) {
            $("#verify_otp").prop('disabled', false).text('Next');
            $(".error_otp").addClass('show').text(error.message || 'Invalid OTP');
        });
    });

    /* Validate Step 2 Fields */
    function validateStep2() {
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

        if (!validateStep2()) return;

        let $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Creating Account...');

        $.post(base_url + "seller/auth/ajax_signup", $(this).serialize(), function (res) {
            try {
                if (res.status === "success") {
                    $(".success_mobile").addClass('show').text("Account created successfully! Redirecting...");
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