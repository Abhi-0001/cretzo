
<?php if (basename($_SERVER['PHP_SELF']) != 'forgot_password') { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cretzo - Seller Login</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background-color: #fef8e8; /* Matching the soft cream background */
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
    color: #5D6D5E; /* Muted olive/grey tone */
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
</style>
</head>
<body style="background:#fef8e8">

    <div class="login-container">
        <div class="brand-section">
            <div class="logo-area">
                <a href="<?= base_url() . 'seller/login' ?>"><img src="<?= base_url() . $logo ?>" style="width: 340px;"></a>
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
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter Your Password" value="<?= (ALLOW_MODIFICATION == 0) ? '12345678' : '' ?>"	required>
                    <span style="color:red">
                        <?php if (isset($_GET['error']) && $_GET['error'] === 'true') { ?>
                            Invalid Credentials
                        <?php } ?>

                        </span>
                    <div class="forgot-password">
                        <a href="<?= base_url('/seller/login/forgot_password') ?>">Forgot Password ?</a>
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
<script>
	function signupPage() {
		window.location.href = "<?= base_url('seller/auth/sign_up') ?>";
	}
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