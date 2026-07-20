
<?php
$is_forgot_password = (isset($main_page) && strpos($main_page, 'forgot-password') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<?php if ($is_forgot_password) { ?>
    <?php $this->load->view('admin/include-head.php'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/seller-auth.css') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/css/seller-auth.css') ?>">
    <style>
        body {
            background-color: #fef8e8 !important;
            display: flex !important;
            justify-content: center !important;
            align-items: flex-start !important;
            padding: 50px 0 !important;
            min-height: 100vh !important;
            height: auto !important;
        }
        .intl-tel-input {
            width: 100% !important;
        }
        .intl-tel-input input {
            width: 100% !important;
            padding: 12px 12px 12px 50px !important;
            border: 1.5px solid #333 !important;
            border-radius: 6px !important;
            background: transparent !important;
            box-sizing: border-box !important;
            height: auto !important;
        }
        .intl-tel-input .flag-container {
            padding: 2px !important;
        }
    </style>
<?php } else { ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cretzo - Seller Login</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/seller-auth.css') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/css/seller-auth.css') ?>">
</head>
<?php } ?>
<body>

    <div class="login-container">
        <div class="brand-section">
            <div class="logo-area">
                <a href="/"><img src="<?= base_url() . $logo ?>" alt="Cretzo logo"></a>
                <p class="tagline">Welcome to the zone of creativity</p>
            </div>
            
            <div class="illustration">
                <img src="<?= base_url()?>/assets/logo/handloon.png" alt="Handmade Artist">
            </div>

            <h2 class="mission-text">Empowering Handmade Artist Worldwide</h2>
        </div>
		
        <div class="form-section">
            <?php if ($is_forgot_password) { ?>
                <h2 class="form-title">Forgot Password</h2>
                
                <form id="send_forgot_password_otp_form" method="POST" action="#">
                    <div class="input-group">
                        <label>Mobile Number</label>
                        <input type="text" name="mobile_number" id="forgot_password_number" placeholder="Enter Mobile number" required>
                        <span class="error-message error_forgot_mobile"></span>
                    </div>
                    <div class="input-group d-flex justify-content-center pb-4 mt-3" style="display: flex; justify-content: center;">
                        <div id="recaptcha-container-2"></div>
                    </div>
                    <button type="submit" id="forgot_password_send_otp_btn" class="btn-login"><?= !empty($this->lang->line('send_otp')) ? $this->lang->line('send_otp') : 'Send OTP' ?></button>
                    
                    <div class="signup-prompt">
                        <p>Remembered password?</p>
                        <button onclick="window.location.href='<?= base_url('seller/home/') ?>'" class="btn-create" type="button">Back to Login</button>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-3">
                        <div class="form-group text-danger" id="forgot_pass_error_box"></div>
                    </div>
                </form>

                <form id="verify_forgot_password_otp_form" class="d-none" method="post" action="#">
                    <div class="input-group">
                        <label>OTP</label>
                        <input type="text" id="forgot_password_otp" name="otp" placeholder="Enter OTP" autocomplete="off" required>
                        <span class="error-message error_forgot_otp"></span>
                    </div>
                    <div class="input-group">
                        <label>New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" placeholder="Enter New Password" required>
                            <button type="button" class="toggle-password" aria-label="Show password" onclick="togglePassword(this)">
                                <svg class="eye-on" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <span class="error-message error_forgot_password"></span>
                    </div>
                    <button type="submit" class="btn-login" id="reset_password_submit_btn"><?= !empty($this->lang->line('submit')) ? $this->lang->line('submit') : 'Submit' ?></button>
                    
                    <div class="d-flex justify-content-center mt-3">
                        <div class="form-group text-danger" id="set_password_error_box"></div>
                    </div>
                </form>
            <?php } else { ?>
                <h2 class="form-title">Seller Login</h2>

                <?php if (!empty($launch_offer_active)) : ?>
                <div class="launch-offer-banner" role="note">
                    <span class="lo-icon" aria-hidden="true">&#127881;</span>
                    <span class="lo-text"><strong>Launch Offer</strong>First 100 vendors get 50 free listings for 1 year</span>
                </div>
                <?php endif; ?>

                <form action="<?= base_url('/seller/auth/login') ?>" class='form-submit-event' method="post">
                <input type='hidden' name='<?= $this->security->get_csrf_token_name() ?>' value='<?= $this->security->get_csrf_hash() ?>'>   
                <div class="input-group">
                        <label>Email and Mobile</label>
                        <input type="<?= $identity_column ?>" name="identity" id="mobile" placeholder="Enter Your <?= ucfirst($identity_column)  ?>" value="<?= (ALLOW_MODIFICATION == 0) ? '9988776655' : '' ?>" required>
                        <span class="error-message error_identity"></span>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" placeholder="Enter Your Password" value="<?= (ALLOW_MODIFICATION == 0) ? '12345678' : '' ?>" required>
                            <button type="button" class="toggle-password" aria-label="Show password" onclick="togglePassword(this)">
                                <svg class="eye-on" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <span class="error-message error_password"></span>
                        <div class="forgot-password">
                            <a href="<?= base_url('/seller/login/forgot_password') ?>">Forgot Password?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Log In</button>
                </form>

                <div class="signup-prompt">
                    <p>New to Cretzo?</p>
                    <button onclick="signupPage()" class="btn-create">Create Seller Account</button>
                </div>
            <?php } ?>
        </div>
    </div>

</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function togglePassword(btn) {
        var input = btn.parentElement.querySelector('input');
        var showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        btn.classList.toggle('is-visible', showing);
        btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
    }
</script>
<?php if ($is_forgot_password) { ?>
    <!-- Load base scripts needed for forgot password page -->
    <?php $this->load->view('admin/include-script.php'); ?>
<?php } else { ?>
<script>
    function signupPage() {
        window.location.href = "<?= base_url('seller/auth/sign_up') ?>";
    }

    $(document).ready(function() {
        // Show error from URL parameter
        <?php if (isset($_GET['error']) && $_GET['error'] === 'true') { ?>
            $(".error_identity").addClass('show').text("Invalid credentials. Please try again.");
            $("#mobile").focus();
        <?php } ?>

        // Clear errors on input
        $("#mobile, #password").on('input', function() {
            $(".error-message").removeClass('show').text('');
        });

        // Form validation
        $(".form-submit-event").submit(function(e) {
            let identity = $("#mobile").val().trim();
            let password = $("#password").val().trim();
            let hasError = false;

            $(".error-message").removeClass('show').text('');

            if (identity.length === 0) {
                $(".error_identity").addClass('show').text("Mobile number or email is required");
                $("#mobile").focus();
                hasError = true;
            }

            if (password.length === 0) {
                $(".error_password").addClass('show').text("Password is required");
                if (!hasError) $("#password").focus();
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
<?php } ?>
</html>