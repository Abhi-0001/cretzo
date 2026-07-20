
<?php if (basename($_SERVER['PHP_SELF']) != 'forgot_password') { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cretzo - Seller Login</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/seller-auth.css') ?>">
</head>
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
            <h2 class="form-title">Seller Login</h2>
            
            <form action="<?= base_url('/seller/auth/login') ?>" class='form-submit-event' method="post">
            <input type='hidden' name='<?= $this->security->get_csrf_token_name() ?>' value='<?= $this->security->get_csrf_hash() ?>'>   
			<div class="input-group">
                    <label>Email and Mobile</label>
                    <input type="<?= $identity_column ?>" name="identity" id="mobile" placeholder="Enter Your <?= ucfirst($identity_column)  ?>" value="<?= (ALLOW_MODIFICATION == 0) ? '9988776655' : '' ?>" required>
                    <span class="error-message error_identity"></span>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter Your Password" value="<?= (ALLOW_MODIFICATION == 0) ? '12345678' : '' ?>" required>
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
        </div>
		

    </div>

</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
</html>

<?php } else{ ?>

	<!DOCTYPE html>
<html>
<?php $this->load->view('admin/include-head.php'); ?>

<body class="hold-transition login-page  bg-admin">
    <img src="<?= base_url('assets/admin/images/eshop_img.jpg') ?>" class="h-100 w-100">
    <div class="overlay"></div>
	<?php $this->load->view('seller/pages/' . $main_page); ?>
	<!-- Footer -->
	<?php $this->load->view('admin/include-script.php'); ?>

	<script>

	</script>
</body>

</html>

	<?php } ?>