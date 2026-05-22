<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cretzo - Seller Signup</title>

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
        </div>

        <div class="form-section">
            <h2 class="form-title">Create Seller Account </h2>

            <form class="form-submit-event">

            <!-- STEP 1 -->
            <div class="step1 active">

                <div class="input-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Full Name">
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Email Address">
                </div>

                <div class="input-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="tel" name="mobile" id="mobile" placeholder="Mobile Number" maxlength="10" pattern="[0-9]*" inputmode="numeric">
                    <span class="send-otp" id="send_otp">Send OTP</span>
                    <span class="error-message error_mobile"></span>
                </div>

                <div class="input-group" style="display:none;" id="div_otp">
                    <label for="otp">Enter OTP</label>
                    <input type="text" id="otp" placeholder="Enter OTP" >
                    <span class="error-message error_otp"></span>
                    <span class="success-message success_otp"></span>
                </div>

                    <input type="hidden" name="phone_verified" id="phone_verified" value="0">
                    <input type="hidden" name="firebase_uid" id="firebase_uid" value="">
                    <input type="hidden" name="firebase_phone" id="firebase_phone" value="">
                    <div id="recaptcha-registration"></div>
                <button type="button" class="btn" id="verify_otp">Next</button>
            </div>

            <!-- STEP 2 -->
            <div class="step2">

                <div class="input-group">
                    <label for="password">Create Password</label>
                    <input type="password" name="password" id="password" placeholder="Create Password">
                    <span class="error-message" id="password-error"></span>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password">
                    <span class="error-message" id="confirm-password-error"></span>
                </div>

                <button type="submit" class="btn">Sign Up</button>
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

<script>
$(document).ready(function () {

    const base_url = "<?= base_url() ?>";
    const isLocalHost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

    if (isLocalHost && firebase && firebase.auth && firebase.auth().settings) {
        firebase.auth().settings.appVerificationDisabledForTesting = true;
    }

    // debug: ensure firebase initialized with correct config
    if (firebase && firebase.apps && firebase.apps.length) {
        console.log('Firebase app options:', firebase.app().options);
    } else {
        console.warn('Firebase not initialized');
    }


    /* restrict mobile field to digits on keypress */
    $("#mobile").on('keydown', function(e) {
        // allow: backspace, delete, tab, escape, enter, arrow keys
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            (e.keyCode >= 35 && e.keyCode <= 40)) {
            return;
        }
        // allow ctrl/cmd+A, C, V, X
        if ((e.ctrlKey || e.metaKey) &&
            (e.keyCode === 65 || e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88)) {
            return;
        }
        // ensure digit
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) &&
            (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    /* Helper to (re)create and render recaptcha verifier before each use */
    function createRecaptcha() {
        // always create a fresh verifier; old tokens expire quickly
        // clear any previous DOM / object state
        $('#recaptcha-registration').html('');
        if (window.recaptchaVerifier) {
            try { 
                window.recaptchaVerifier.clear(); 
            } catch(e) { /* ignore */ }
            window.recaptchaVerifier = null;
        }
        // reset grecaptcha widget state if it exists
        if (window.grecaptcha && window.recaptchaWidgetId !== undefined) {
            try { 
                grecaptcha.reset(window.recaptchaWidgetId); 
            } catch(ex) { /* ignore */ }
        }
        window.recaptchaWidgetId = undefined;

        function build() {
            window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-registration', {
                'size': 'invisible',
                'callback': function(response) {
                    // recaptcha solved, will proceed with signInWithPhoneNumber
                }
            });
            return window.recaptchaVerifier.render().then(function(widgetId) {
                window.recaptchaWidgetId = widgetId;
                if (window.grecaptcha && typeof grecaptcha.reset === 'function') {
                    try { grecaptcha.reset(widgetId); } catch (ex) { /* ignore */ }
                }
                return window.recaptchaVerifier;
            });
        }

        return new Promise(function(resolve, reject) {
            // small delay to ensure DOM is fully cleared
            setTimeout(function() {
                build().then(resolve).catch(function(err) {
                    // retry once on render conflict
                    if (err && err.message && err.message.indexOf('already been rendered') !== -1) {
                        console.warn('Recaptcha render conflict, wiping container and retrying');
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

    /* SEND OTP using Firebase (create recaptcha each time to avoid expired verifier) */
    $("#send_otp").click(function () {
        let name = $("#name").val().trim();
        let email = $("#email").val().trim();
        let mobile = $("#mobile").val().trim().replace(/\D/g, '');
        $("#mobile").val(mobile);
        $(".error-message").hide();

        // name non-empty
        if (name.length === 0) {
            $("#name").focus();
            $(".error_mobile").text("Please enter your name").show();
            return;
        }
        // simple email regex
        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRe.test(email)) {
            $("#email").focus();
            $(".error_mobile").text("Enter valid email address").show();
            return;
        }
        if (!/^[6-9]\d{9}$/.test(mobile)) {
            $(".error_mobile").text("Enter valid 10 digit mobile number").show();
            return;
        }

        var phoneNumber = '+91' + mobile;
        
        // for resend: sign out first to reset auth state
        var isResend = $("#send_otp").text() === 'Resend OTP';
        var authResetPromise = isResend ? firebase.auth().signOut() : Promise.resolve();

        authResetPromise.then(function() {
            return createRecaptcha().then(function(appVerifier) {
                return firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier);
            });
        }).then(function (confirmationResult) {
            window.confirmationResult = confirmationResult;
            $("#div_otp").show();
            $("#send_otp").text("Resend OTP");
            $(".success_otp").text("OTP sent to " + phoneNumber).show();
        }).catch(function (error) {
            // handle Firebase rate-limit / unusual activity errors specially
            var code = error && error.code;
            var msg = error && error.message ? error.message : 'Failed to send OTP';
            if (code === 'auth/too-many-requests' || msg.toLowerCase().includes('unusual activity')) {
                var message = "We have temporarily blocked requests from this device due to unusual activity. Please wait a few minutes and try again.";
                $(".error_mobile").text(message).show();
                // also alert and disable button briefly
                alert(message);
                $("#send_otp").prop('disabled', true).text('Please wait');
                setTimeout(function(){
                    $("#send_otp").prop('disabled', false).text('Send OTP');
                }, 2 * 60 * 1000); // 2 minutes
                return;
            }
            // If verifier invalid/expired, try recreating once
            if (msg && msg.toLowerCase().indexOf('invalid application verifier') !== -1) {
                createRecaptcha().then(function(appVerifier) {
                    return firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier);
                }).then(function(confirmationResult) {
                    window.confirmationResult = confirmationResult;
                    $("#div_otp").show();
                    $("#send_otp").text("Resend OTP");
                    $(".success_otp").text("OTP sent to " + phoneNumber).show();
                }).catch(function(err2){
                    $(".error_mobile").text(err2.message || msg).show();
                });
            } else {
                $(".error_mobile").text(msg).show();
            }
        });
    });

    /* VERIFY OTP using Firebase */
    $("#verify_otp").click(function () {
        let otp = $("#otp").val().trim().replace(/\D/g, '');
        $("#otp").val(otp);
        $(".error_otp").hide();

        if (otp.length !== 6) {
            $(".error_otp").text("Enter valid 6 digit OTP").show();
            return;
        }

        if (!window.confirmationResult) {
            $(".error_otp").text("Please request OTP first").show();
            return;
        }

        window.confirmationResult.confirm(otp).then(function (result) {
            var user = result.user;
            $("#phone_verified").val('1');
            $("#firebase_uid").val(user.uid || '');
            $("#firebase_phone").val(user.phoneNumber || $('#mobile').val());
            $(".error-message").hide();
            $(".step1").removeClass("active");
            $(".step2").addClass("active");
            $("#password").focus();
        }).catch(function (error) {
            $(".error_otp").text(error.message || 'Invalid OTP').show();
        });
    });

    /* FINAL SUBMIT */
    $(".form-submit-event").submit(function (e) {
        e.preventDefault();

        let name = $("#name").val().trim();
        let email = $("#email").val().trim();
        let mobile = $("#mobile").val().trim();
        let password = $("#password").val();
        let confirm  = $("#confirm_password").val();

        $(".error-message").hide();

        if (name.length === 0) {
            $("#name").focus();
            $(".error_mobile").text("Please enter your name").show();
            return;
        }
        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRe.test(email)) {
            $("#email").focus();
            $(".error_mobile").text("Enter valid email address").show();
            return;
        }
        if (!/^[6-9]\d{9}$/.test(mobile)) {
            $(".error_mobile").text("Enter valid 10 digit mobile number").show();
            return;
        }
        if (password.length < 6) {
            $("#password-error").text("Minimum 6 characters required").show();
            return;
        }

        if (password !== confirm) {
            $("#confirm-password-error").text("Passwords do not match").show();
            return;
        }

        $.post(base_url + "seller/auth/ajax_signup", $(this).serialize(), function (res) {
            if (res.status === "success") {
                window.location.href = base_url + "seller/home";
            } else {
                alert(res.message);
            }
        }, "json");
    });

});
</script>
</html>