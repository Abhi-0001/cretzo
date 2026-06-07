<?php include 'header.php'; ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 bg-white shadow" style="border-radius:30px">
            <div class="row">
                <div class="col-lg-8 content-center">
                    <div class="">
                        <!-- Alert Container -->
                        <div id="alert-container"></div>
                        
                        <h2 class="text-center mb-4">Sign Up</h2>
                        
                        <!-- Email/Password Signup Form -->
                        <form id="signup-form">
                            <div class="my-3">
                                <input type="text" class="form-control new-form" name="name" placeholder="Enter your Full Name" id="signup-name" required>
                            </div>
                            <div class="my-3">
                                <input type="email" class="form-control new-form" name="email" placeholder="Enter Your Email" id="signup-email" required>
                            </div>
                            <div class="my-3">
                                <input type="tel" class="form-control new-form" name="phone" placeholder="Enter Your Phone Number" id="signup-phone" required>
                            </div>
                            <div class="my-3">
                                <input type="password" class="form-control new-form" name="password" placeholder="Enter Your Password" id="signup-password" required>
                            </div>
                            <div class="my-3">
                                <input type="password" class="form-control new-form" name="password_confirm" placeholder="Re-Enter Your Password" id="signup-password-confirm" required>
                            </div>
                            <div class="my-3 d-flex">
                                <div>
                                    <input type="checkbox" id="agree-terms" required> 
                                    <span style="font-size:small">I agree to <a href="#" class="active">Terms of Use</a> and <a href="#" class="active">Privacy Policy</a></span>
                                </div>
                            </div>
                            <div class="my-3 d-grid">
                                <button type="submit" class="btnAddToCard text-white">Sign Up</button>
                            </div>
                        </form>

                        <!-- Divider -->
                        <div class="my-3 text-center">
                            <span style="color: #999;">OR</span>
                        </div>

                        <!-- Social Signup Options -->
                        <div class="my-3">
                            <div class="text-center">
                                    <a href="#" id="facebook-login-btn" class="btn btn-primary w-100 shadow-sm mb-3" style="border-radius: 8px; display:inline-block; padding:0.5rem 1rem; text-align:center;">
                                        <i class="bi bi-facebook"></i> Create account with Facebook
                                    </a>
                            </div>
                            <div class="text-center">
                                <button type="button" id="google-login-btn" class="btn btn-light w-100 shadow-sm" style="border-radius: 8px; border: 1px solid #ddd;">
                                    <img src="assets/img/icons/Google.png" alt="Google" style="height: 20px; margin-right: 8px;"> Sign Up With Google
                                </button>
                            </div>
                        </div>

                        <p class="text-center my-3">Already have an account? <a href="login.php" class="active">Login</a></p>
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