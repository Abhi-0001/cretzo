<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cretzo - Seller Login</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #fef8e8;
            /* Matching the soft cream background */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            width: 900px;
            display: flex;
            padding: 60px;
            border-radius: 8px;
            /* Optional shadow for depth */
            /* box-shadow: 0 10px 30px rgba(0,0,0,0.05); */
        }

        /* LEFT SECTION */
        .brand-section {
            flex: 1;
            text-align: center;
            padding-right: 40px;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            letter-spacing: -2px;
        }

        .logo span {
            color: #E07A48;
        }

        .tagline {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            margin-bottom: 30px;
        }

        .illustration img {
            width: 100%;
            max-width: 300px;
            margin-bottom: 20px;
        }

        .mission-text {
            font-family: 'Playfair Display', serif;
            color: #5D6D5E;
            /* Muted olive/grey tone */
            font-size: 24px;
            line-height: 1.2;
        }

        /* RIGHT SECTION */
        .form-section {
            flex: 1;
            padding-left: 40px;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            font-family: 'Playfair Display', serif;
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #333;
            border-radius: 6px;
            background: transparent;
        }

        .forgot-password {
            text-align: right;
            margin-top: 5px;
        }

        .forgot-password a {
            font-size: 13px;
            color: #333;
            text-decoration: none;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #E07A48;
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .signup-prompt {
            text-align: center;
            margin-top: 20px;
        }

        .signup-prompt p {
            font-size: 14px;
            margin-bottom: 15px;
        }

        .btn-create {
            width: 100%;
            padding: 10px;
            background: white;
            border: 1.5px solid #333;
            color: #E07A48;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
   

        .login-container {
            width: 900px;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            display: flex;
        }

        .form-section { width: 100%; }

        .input-group {
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #333;
            border-radius: 6px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #E07A48;
            border: none;
            color: #fff;
            font-weight: bold;
            border-radius: 25px;
            cursor: pointer;
        }

        .send-otp {
            font-size: 13px;
            color: #E07A48;
            cursor: pointer;
            display: inline-block;
            margin-top: 5px;
        }

        .error-message {
            color: red;
            font-size: 12px;
            display: none;
        }

        .success-message {
            color: green;
            font-size: 12px;
            display: none;
        }

        .step1, .step2 { display: none; }
        .step1.active, .step2.active { display: block; }
    </style>
</head>

<body style="background:#fef8e8">

    <div class="login-container">
        <div class="brand-section">
            <div class="logo-area">
                <a href="<?= base_url() . 'seller/login' ?>"><img src="<?= base_url() . $logo ?>"
                        style="width: 340px;"></a>
                <p class="tagline">Welcome to the zone of creativity</p>
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
                <p>Already have an account? <a href="<?= base_url('seller/auth/login') ?>" style="color: #ff9900ff; text-decoration: none;">Login</a></p>
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
            $(".step1").removeClass("active");
            $(".step2").addClass("active");
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