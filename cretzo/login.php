<?php include 'header.php'; ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 bg-white shadow" style="border-radius:30px">
            <div class="row">
                <div class="col-lg-8 content-center">
                    <div class="my-5">
                        <!-- Alert Container -->
                        <div id="alert-container"></div>
                        
                        <h2 class="text-center">Login</h2>
                        
                        <!-- Email/Password Login Form -->
                        <form id="email-login-form">
                            <div class="my-4">
                                <input type="email" class="form-control new-form" name="email" placeholder="Email Address" id="login-email" required>
                            </div>
                            <div class="my-4">
                                <input type="password" class="form-control new-form" name="password" placeholder="Enter your Password" id="login-password" required>
                            </div>
                            <div class="my-3 d-flex justify-content-between">
                                <div>
                                    <input type="checkbox" id="remember-me"> <span style="font-size:small">Remember Me</span>
                                </div>
                                <div class="">
                                    <a href="forgot-password.php" class="active" style="font-size:small">Forget Password</a>
                                </div>
                            </div>
                            <div class="my-3 d-grid">
                                <button type="submit" class="btnAddToCard text-white">Login</button>
                            </div>
                        </form>

                        <!-- Divider -->
                        <div class="my-3 text-center">
                            <span style="color: #999;">OR</span>
                        </div>

                        <!-- Social Login Options -->
                        <div class="my-3">
                            <div class="text-center">
                                    <a href="index.php/auth/facebook_login" class="btn btn-primary w-100 shadow-sm mb-3" style="border-radius: 8px; display:inline-block; padding:0.5rem 1rem; text-align:center;">
                                        <i class="bi bi-facebook"></i> Login with Facebook
                                    </a>
                                </div>
                            <div class="text-center">
                                <button type="button" id="google-login-btn" class="btn btn-light w-100 shadow-sm" style="border-radius: 8px; border: 1px solid #ddd;">
                                    <img src="assets/img/icons/Google.png" alt="Google" style="height: 20px; margin-right: 8px;"> Sign in With Google
                                </button>
                            </div>
                        </div>

                        <p class="text-center my-3">By Continuing, you agree to <span><a href="#" class="active">Terms of Use</a></span> and <span><a href="#" class="active">Privacy Policy</a></span> </p>

                        <p class="text-center">Don't have an account? <a href="signup.php" class="active">Sign Up</a></p>
                    </div>
                </div>
                <div class="col-lg-4 D-none">
                    <img src="assets/img/login.png" class="" style="border-top-right-radius:30px;border-bottom-right-radius:30px;" alt="">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Firebase Scripts -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>
<script src="../firebase-config.js"></script>
<script src="assets/auth.js"></script>

<?php include 'footer.php'; ?>