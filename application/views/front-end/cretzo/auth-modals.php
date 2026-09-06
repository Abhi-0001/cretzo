<?php
/*
 * Sign in / sign up modals.
 *
 * Extracted out of footer.php so there is ONE copy of this markup. The cart and
 * checkout pages render with $hide_header_footer set, so footer.php never loads
 * there - cart.php used to carry its own duplicate of these modals, which meant
 * duplicate element IDs on any page that had both and two versions of the signup
 * form to keep in sync. Both places now include this partial instead.
 */
?>
<div class="modal fade" id="modal-signin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <!-- <div class="modal-content text-center"> -->
            <!-- <div class="modal-body"> -->

            <section class="modal-content modal-body">

                <section id="login_div" class="login-container">
                    
                    
                    <!-- <h2 class="mb-3 text-start">Welcome Back</h2> -->
                    <!-- <p class="lead mb-6 text-start">Fill your email and password to sign in.</p> -->
                    
                    <form action="<?= base_url('home/login') ?>" class='form-submit-event' id="login_form" method="post">
                        
                        <input type="hidden" class="form-control" name="type" value="phone">

                        <!-- login -->
                        <div class="login rounded-1">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                            <div class="login-left">
                                <h1 class="heading-n ta-c">Login</h1>
                                <p class="text-n ta-c op-6">Fill your email/phone and password to sign in!</p>
                                
                                <div class="field-container">
                                    
                                    <!-- <input class="input ta-c" type="text" placeholder="Phone Number / Email ID" > -->

                                    <input class="form-control input ta-c" type="text" name="identity" placeholder="Phone Number / Email ID" id="loginEmail" value="<?= (ALLOW_MODIFICATION == 0) ? '1212121212' : '' ?>">
                                    <!-- <label for="loginEmail">Enter Mobile Number / Email</label> -->

                                    <!-- <br> -->
                                    <!-- <input class="input ta-c" type="password" placeholder="Enter your Password"> -->

                                    <?php /* password-field is what theme.js passVisibility() binds the eye
                                             icon to; password-container only carries the existing login.css
                                             styling. Without the former the toggle rendered but did nothing. */ ?>
                                    <div class="password-container password-field">
                                        <input class="form-control input ta-c" type="password" name="password" placeholder="Password" id="loginPassword" value="<?= (ALLOW_MODIFICATION == 0) ? '12345678' : '' ?>">
                                        <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                        <!-- <label for="loginPassword">Password</label> -->
                                    </div>

                                    <div class="flex mt-2">
                                        <div>
                                            <input class="checkbox" type="checkbox">
                                            <label class="label text-s">Remember Me</label>
                                        </div>
                                        <div class="flex-1"></div>
                                        <!-- <a class="link text-n orange">Forgot Password?</a> -->
                                        <a href="<?= base_url() ?>" id="forgot_password_link" class="link text-s orange hover"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password?' ?> ?</a>
                                    </div>

                                    <div class="ta-c btn-container">
                                        <button type="submit" class="submit_btn cretzo btn btn-dark"><?= !empty($this->lang->line('login')) ? $this->lang->line('login') : 'Login' ?></button>
                                    </div>
                                    
                                </div>

                                
                                <div class="form-group ta-c" id="error_box"></div>
                                <!-- <div class="d-flex justify-content-center d-none">
                                    <div class="form-group" id="error_box"></div>
                                </div> -->

                                <?php if ((true || !empty($system_settings['google_login']) && $system_settings['google_login'] == 1) || (!empty($system_settings['facebook_login']) && $system_settings['facebook_login'] == 1)) { ?>
                                    <div class="login-with-container">
                                        <?php if (true || !empty($system_settings['google_login']) && ($system_settings['google_login'] == 1 || $system_settings['google_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="google">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/google-icon.jpg') ?>">
                                                    <p class="text-s">Google</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                        <?php if (true || !empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="facebook">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/facebook-icon.jpg') ?>">
                                                    <p class="text-s">Facebook</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <p class="text-n ta-c">By continuing you agree to <strong><a class="text-decoration-none text-underline c-p text-s" style="text-decoration: underline !important;" href="<?= base_url('home/terms-and-conditions') ?>">Terms of use</a></strong> and <strong><a class="text-decoration-none text-underline c-p text-s" style="text-decoration: underline !important;" href="<?= base_url('home/privacy-policy') ?>">Privacy Policy</a></strong></p>

                                <p class="text-n mb-0 ta-c mt-2">Don't have an account? <a class="text-decoration-none text-blue hover text-underline c-p fw-bold" href="#" data-bs-target="#modal-signup" data-bs-toggle="modal" data-bs-dismiss="modal" class="hover" style="color: var(--color-orange) !important;">Sign Up</a></p>
                                
                                <!-- <div class="ta-c btn-container">
                                    <button class="cretzo btn btn-dark">Login</button>
                                </div> -->

                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);"></div>
                        </div>
                        <!-- /login -->
                    </form>
                    
                </section>
                <!-- login section complete -->


                <section class="d-none login-container" id="forgot_password_div">

                    <form id="send_forgot_password_otp_form" method="POST" action="#">
                        <div class="login rounded-1">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <div class="login-left fp-panel">
                                <h1 class="heading-n ta-c"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password' ?></h1>
                                <p class="text-n ta-c op-6">Enter your registered mobile number to receive an OTP.</p>
                                <div class="field-container">
                                    <input type="text" class="form-control input ta-c" name="mobile_number" id="forgot_password_number" placeholder="Mobile Number" value="">
                                </div>
                                <div class="form-group ta-c" id="forgot_pass_error_box"></div>
                                <?php // Firebase phone auth renders its invisible reCAPTCHA here. ?>
                                <div id="recaptcha-password-reset"></div>
                                <div class="ta-c btn-container">
                                    <button type="submit" id="forgot_password_send_otp_btn" class="submit_btn cretzo btn btn-dark"><?= !empty($this->lang->line('send_otp')) ? $this->lang->line('send_otp') : 'Send OTP' ?></button>
                                </div>
                                <?php /* There was no way back other than closing the modal, which
                                         also threw away whatever had been typed on the login form. */ ?>
                                <p class="fp-back ta-c"><a href="#" class="back-to-login-link">&larr; Back to login</a></p>
                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);"></div>
                        </div>
                    </form>

                    <form id="verify_forgot_password_otp_form" class="d-none" method="post" action="#">
                        <div class="login rounded-1">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                            <div class="login-left fp-panel">
                                <h1 class="heading-n ta-c"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password' ?></h1>
                                <p class="text-n ta-c op-6">Enter the OTP and set a new password.</p>
                                <div class="field-container">
                                    <input type="text" id="forgot_password_otp" class="form-control input ta-c" name="otp" placeholder="OTP" value="" autocomplete="off" required>
                                    <?php /* password-field is what theme.js passVisibility() binds the eye
                                             icon to; password-container only carries the existing login.css
                                             styling. Without the former the toggle rendered but did nothing. */ ?>
                                    <div class="password-container password-field">
                                        <input type="password" id="forgot_password_new_password" class="form-control input ta-c" name="new_password" placeholder="New Password" value="" required>
                                        <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                    </div>
                                </div>
                                <div class="form-group ta-c" id="set_password_error_box"></div>
                                <div class="ta-c btn-container">
                                    <button type="submit" class="submit_btn cretzo btn btn-dark" id="reset_password_submit_btn"><?= !empty($this->lang->line('submit')) ? $this->lang->line('submit') : 'Submit' ?></button>
                                </div>
                                <p class="fp-back ta-c"><a href="#" class="back-to-login-link">&larr; Back to login</a></p>
                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);"></div>
                        </div>
                    </form>

                </section>

            </section>

            <!-- </div> -->

            <!--/.modal-content -->
        <!-- </div> -->
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->


<div class="modal fade" id="modal-signup" tabindex="-1">
    <?php /* modal-lg, not modal-sm: .login is 800px wide, so a 300px modal-sm dialog
             left it overflowing its own centred container and rendering off-centre. */ ?>
    <div class="modal-dialog modal-dialog-centered modal-lg signup-dialog">
        <!-- <div class="modal-content text-center"> -->

            <!-- <div class="modal-body"> -->
                
                
                <!-- signup container -->
                <section id="register_div" class="login-container modal-content modal-body">

                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->

                    <form id='send-otp-form' class='send-otp-form' action='#'>
                        <!-- signup 1 -->
                        <div id="signupone" class="login rounded-1">

                            <?php /* Placed directly inside .login (which is position:relative) and
                                     pinned top-right by CSS - the same pattern the login modal uses.
                                     The old full-size pointer-events:none wrapper left the button in
                                     the top-LEFT corner, since align-self only applies in a flex
                                     container and that wrapper was a plain block. */ ?>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                            <div class="login-left pb-4">
                                <h1 class="heading-n ta-c">Sign Up</h1>
                                <p class="text-n ta-c op-6">Registration takes less than a minute.</p>

                                <?php /* Shared 3-step indicator, same Details -> Verify -> Password
                                         flow the seller registration page uses. Rendered once per
                                         panel with a different active step. */ ?>
                                <div class="signup-steps" aria-hidden="true">
                                    <div class="signup-step active"><span>1</span><label>Details</label></div>
                                    <div class="signup-step-line"></div>
                                    <div class="signup-step "><span>2</span><label>Verify</label></div>
                                    <div class="signup-step-line"></div>
                                    <div class="signup-step "><span>3</span><label>Password</label></div>
                                </div>

                                <div class="field-container">
                                    <input id="signup-name" name="name" class="form-input form-control input" type="text" placeholder="Full Name" autocomplete="name" required>
                                    <input id="signup-email" name="email" class="form-input form-control input" type="email" placeholder="Email Address (optional)" autocomplete="email">
                                    <input id="phone-number" class="form-input form-control input" type="text" placeholder="Mobile Number" required>

                                    <?php /* Referral code. Collapsed behind a link because it is optional
                                             and most signups have no code - a permanently visible field
                                             invites people to hunt for one. custom.js opens the row and
                                             fills it in automatically when the visitor arrived on a
                                             ?ref= link, which is how most codes actually travel. */ ?>
                                    <div class="referral-row">
                                        <button type="button" id="referral-toggle" class="referral-toggle">
                                            <i class="uil uil-gift"></i>
                                            <span>Have a referral code?</span>
                                        </button>
                                        <div id="referral-field" class="referral-field d-none">
                                            <?php /* The toggle hides itself once the field is open, and a code that
                                                     arrived on a share link or a scanned QR fills the input - which
                                                     hides the placeholder too. Without this label the commonest path
                                                     ends with an unexplained box of eight characters above the
                                                     signup button. */ ?>
                                            <label class="referral-label" for="signup-referral">
                                                <i class="uil uil-gift"></i> Referral code
                                            </label>
                                            <input id="signup-referral" name="friends_code" class="form-input form-control input" type="text"
                                                   placeholder="Referral code" autocomplete="off" spellcheck="false" maxlength="16">
                                            <p id="referral-feedback" class="referral-feedback"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="hide text-danger ta-c" id="error-msg"></div>

                                <?php /* Anchor for the invisible reCAPTCHA that Firebase phone auth requires.
                                         It renders nothing visible, so it carries no margin. */ ?>
                                <div id="recaptcha-container" class="ta-c d-flex justify-content-center"></div>

                                <div id='is-user-exist-error' class='text-center text-danger ta-c'></div>
                                
                                <div class="ta-c btn-container">
                                    <button id="send-otp-button" class="cretzo btn btn-dark">Send OTP</button>
                                </div>

                                <p class="text-n mb-0 ta-c mt-3">Already have an account? <a class="text-decoration-none text-blue hover text-underline c-p fw-bold" href="#" data-bs-target="#modal-signin" data-bs-toggle="modal" data-bs-dismiss="modal" class="hover" style="color: var(--color-orange) !important;">Sign In</a></p>

                                <?php if ((true || !empty($system_settings['google_login']) && $system_settings['google_login'] == 1) || (!empty($system_settings['facebook_login']) && $system_settings['facebook_login'] == 1)) { ?>
                                    <div class="or-divider"><span>or</span></div>
                                    <div class="login-with-container">
                                        <?php if (true || !empty($system_settings['google_login']) && ($system_settings['google_login'] == 1 || $system_settings['google_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="google">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/google-icon.jpg') ?>">
                                                    <p class="text-s mb-0">Google</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                        <?php if (true || !empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                            <a href="#" class="text-decoration-none social-auth-link" data-auth-provider="facebook">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/facebook-icon.jpg') ?>">
                                                    <p class="text-s mb-0">Facebook</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);">
                            </div>
                        </div>
                        <!-- /signup 1 -->
                    </form>
                    
                    <form id='verify-otp-form' class='verify-otp-form d-none rounded-1' action='<?= base_url('auth/register-user') ?>' method="POST">
                        <!-- signup 2 -->
                        <div id="signuptwo" class="login rounded-1">

                            <?php /* Placed directly inside .login (which is position:relative) and
                                     pinned top-right by CSS - the same pattern the login modal uses.
                                     The old full-size pointer-events:none wrapper left the button in
                                     the top-LEFT corner, since align-self only applies in a flex
                                     container and that wrapper was a plain block. */ ?>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                            <div class="login-left pb-4">
                                <input type="hidden" class='form-input form-control' id="type" name="type" value="phone" autocomplete="off">

                                <?php /* Step 2 - OTP only. custom.js verifies it here (Firebase
                                         confirm()) before step 3 is revealed, so a wrong code is
                                         caught before the user picks a password. */ ?>
                                <div id="signup-step-2" class="signup-panel">
                                    <h1 class="heading-n ta-c">Verify your mobile</h1>
                                    <p class="text-n ta-c op-6">We sent a 6-digit code to <strong id="signup-otp-mobile"></strong></p>

                                <?php /* Shared 3-step indicator, same Details -> Verify -> Password
                                         flow the seller registration page uses. Rendered once per
                                         panel with a different active step. */ ?>
                                <div class="signup-steps" aria-hidden="true">
                                    <div class="signup-step done"><span>1</span><label>Details</label></div>
                                    <div class="signup-step-line"></div>
                                    <div class="signup-step active"><span>2</span><label>Verify</label></div>
                                    <div class="signup-step-line"></div>
                                    <div class="signup-step "><span>3</span><label>Password</label></div>
                                </div>

                                    <?php /* Six single-character boxes, the same control the seller
                                             registration screen uses. #otp stays in the DOM as a hidden
                                             field rather than being replaced: it is what name="otp" posts
                                             to auth/register-user, and what every check in custom.js
                                             reads. The boxes are only an input surface that writes into
                                             it, so nothing downstream had to learn about them. */ ?>
                                    <div class="otp-boxes" id="signup-otp-boxes">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" placeholder=" " autocomplete="one-time-code" aria-label="OTP digit 1">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" placeholder=" " aria-label="OTP digit 2">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" placeholder=" " aria-label="OTP digit 3">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" placeholder=" " aria-label="OTP digit 4">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" placeholder=" " aria-label="OTP digit 5">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" placeholder=" " aria-label="OTP digit 6">
                                    </div>
                                    <input type="hidden" id="otp" name="otp" value="">

                                    <div id="otp-error" class="text-center text-danger reg-error"></div>
                                    <div id="otp-notice" class="otp-notice ta-c"></div>

                                    <div class="ta-c btn-container">
                                        <button type="button" id="verify-otp-button" class="cretzo btn btn-dark">Verify OTP</button>
                                    </div>

                                    <?php /* A code that never arrives is the commonest way this screen
                                             dead-ends. The seller registration screen has carried a
                                             resend since its redesign; this one only offered "change
                                             mobile number", which throws away the number and starts
                                             again. Cooled down for 30s so a stuck user cannot burn the
                                             hourly per-number OTP allowance in ten seconds. */ ?>
                                    <div class="otp-resend-row ta-c">
                                        <span class="otp-resend-q">Didn't get the code?</span>
                                        <button type="button" class="otp-resend" id="signup-resend-otp">Resend OTP</button>
                                        <span class="otp-resend-timer" id="signup-resend-timer"></span>
                                    </div>

                                    <p class="text-n mb-0 ta-c mt-2"><a href="#" id="signup-back-to-details" class="signup-back-link">&larr; Change mobile number</a></p>
                                </div>

                                <?php /* Step 3 - password + terms. Submitting this form is what
                                         actually registers the account. */ ?>
                                <div id="signup-step-3" class="signup-panel d-none">
                                    <h1 class="heading-n ta-c">Create a password</h1>
                                    <p class="text-n ta-c op-6">Last step - pick a password for your account.</p>

                                <?php /* Shared 3-step indicator, same Details -> Verify -> Password
                                         flow the seller registration page uses. Rendered once per
                                         panel with a different active step. */ ?>
                                <div class="signup-steps" aria-hidden="true">
                                    <div class="signup-step done"><span>1</span><label>Details</label></div>
                                    <div class="signup-step-line"></div>
                                    <div class="signup-step done"><span>2</span><label>Verify</label></div>
                                    <div class="signup-step-line"></div>
                                    <div class="signup-step active"><span>3</span><label>Password</label></div>
                                </div>

                                    <div class="field-container">
                                        <?php /* Each password gets its own .password-field wrapper and its own
                                                 toggle. theme.js passVisibility() binds per .password-field, so
                                                 a single shared toggle would never be bound at all. */ ?>
                                        <div class="password-field">
                                            <input type="password" class='form-input form-control input' placeholder="Enter Password" id="password" name="password" autocomplete="new-password">
                                            <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                        </div>
                                        <div class="password-field">
                                            <input type="password" class="form-input form-control input" placeholder="Re-enter Password" id="confirm-password" autocomplete="new-password">
                                            <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                        </div>

                                        <div id='registration-error' class='text-center text-danger reg-error'></div>

                                        <div class="signup-checks">
                                            <div class="form-check-row">
                                                <input class="checkbox" type="checkbox" id="signup-remember-me">
                                                <label class="label text-n" for="signup-remember-me">Remember Me</label>
                                            </div>
                                            <div class="form-check-row">
                                                <input class="checkbox" type="checkbox" id="signup-terms">
                                                <label class="label text-n" for="signup-terms">I agree to the
                                                    <a href="<?= base_url('terms-and-conditions') ?>" target="_blank" rel="noopener" class="terms-link">Terms &amp; Conditions</a>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ta-c btn-container">
                                        <button type="submit" id='register_submit_btn' class="cretzo btn btn-dark">Register Now</button>
                                    </div>
                                </div>
                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);">
                            </div>
                        </div>
                        <!-- /signup 2 -->
                    </form>

                    <form id='sign-up-form' class='sign-up-form collapse rounded-1' action='#'>
                        <input type="text" placeholder="Username" name='username' class='form-input form-control' required>
                        <input type="text" placeholder="email" name='email' class='form-input form-control' required>
                        <input type="password" placeholder="Password" name='password' class='form-input form-control' required>
                        <div id='sign-up-error' class='text-center p-3'></div>
                        <footer>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-soft-dark btn-sm rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type='submit' class="btn btn-primary btn-sm rounded-pill"><?= !empty($this->lang->line('register')) ? $this->lang->line('register') : 'Register' ?></button>
                        </footer>
                    </form>

                </section>
                <!-- /signup container -->

            <!-- </div> -->
        
            
            <!--/.modal-content -->
        <!-- </div> -->
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->
