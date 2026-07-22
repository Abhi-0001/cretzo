<?php $web_settings = get_settings('web_settings', true);
$system_settings = get_settings('system_settings', true); ?>

<!-- footer starts -->
<section class="footer-container">
    <ul class="footer-list footer-list-1">
        <?php $logo = get_settings('web_logo'); ?>
        <a href="<?= base_url() ?>"> <img class="main-logo-footer lazy" src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>"> </a>

        <!-- <li class="op-6">Welcome to cretzo, the one-stop shop for jewellery!</li> -->
        
        <?php if (isset($web_settings['app_short_description'])) { ?>
            <p><?= $web_settings['app_short_description'] ?></p>
        <?php } else { ?>
            <li class="op-6">Welcome to cretzo, the one-stop shop for jewellery!</li>
        <?php } ?>

       

        <!-- <img class="handicraft-logo lazy" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/100-handicraft.png') ?>" data-src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/100-handicraft.png') ?>"> -->
                
    </ul>

    <ul class="footer-list footer-list-2">
        <li class="footer-heading">Information</li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('about-us') ?>"> About Us </a></li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('privacy-policy') ?>"> Privacy Policy </a></li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('contact-us') ?>"> Contact Us </a></li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('home/products') ?>"> Shop </a></li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('home/categories') ?>"> Catagories </a></li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('terms-and-conditions') ?>"> Terms & Conditions </a></li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('return-policy') ?>"> Return Policy </a></li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('shipping-policy') ?>"> Shipping Policy </a></li>
    </ul>

    <ul class="footer-list footer-list-3">
        <li class="footer-heading">Quick Links</li>

        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('seller') ?>"> Sell with Cretzo </a> </li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('cart') ?>"> Cart </a> </li>

        <?php $logged_in = $this->ion_auth->logged_in() ?>
            
        <?php if($logged_in && !$this->ion_auth->is_seller() && !$this->ion_auth->is_delivery_boy() && !$this->ion_auth->is_admin()) { ?>
            <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('my-account/orders') ?>"> Orders </a> </li>
            <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('my-account/favorites') ?>"> Wishlist </a> </li>
            <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('my-account') ?>"> My Account </a> </li>
        <?php }
        else { ?>
            <li class="footer-list-item"> <a class="text-decoration-none hover" href="#" data-bs-toggle="modal" data-bs-target="#modal-signin"> Orders </a> </li>
            <li class="footer-list-item"> <a class="text-decoration-none hover" href="#" data-bs-toggle="modal" data-bs-target="#modal-signin"> Wishlist </a> </li>
            <li class="footer-list-item"> <a class="text-decoration-none hover" href="#" data-bs-toggle="modal" data-bs-target="#modal-signin"> My Account </a> </li>
        <?php } ?>
        
        <!-- <li class="footer-list-item"> <a class="text-decoration-none hover" href="#"> Career </a> </li> -->
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('compare') ?>"> Compare </a> </li>
        <li class="footer-list-item"> <a class="text-decoration-none hover" href="<?= base_url('home/faq') ?>"> FAQ's </a> </li>
    </ul>

    <ul class="footer-list footer-list-4">
        <li class="footer-heading">Contact Us</li>

        <?php if (isset($web_settings['support_email']) && !empty($web_settings['support_email'])) { ?>
            <li class="footer-list-item"> 
                <a href="mailto:<?= $web_settings['support_email'] ?>" class="text-decoration-none hover">
                    <?= $web_settings['support_email'] ?> 
                </a>
            </li>
        <?php } ?>

        <?php if (isset($web_settings['support_number']) && !empty($web_settings['support_number'])) { ?>
            <li class="footer-list-item">
                <a href="tel:<?= $web_settings['support_number'] ?>"  class="text-decoration-none hover">
                    <?= $web_settings['support_number'] ?>
                </a>
            </li>
        <?php } ?>

        <li class="footer-heading mt-4">Keep in touch</li>
        <li class="media-icon-container social">

        
            <!-- <?php if (isset($web_settings['twitter_link']) && !empty($web_settings['twitter_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['twitter_link'] ?>" target="_blank" aria-label="twitter-link" class="text-decoration-none"><i class="uil uil-twitter"></i></a>
            <?php } ?>
            <?php if (isset($web_settings['facebook_link']) &&  !empty($web_settings['facebook_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['facebook_link'] ?>" target="_blank" aria-label="facebook link" class="text-decoration-none"><i class="uil uil-facebook-f"></i></a>
            <?php } ?>
            <?php if (isset($web_settings['instagram_link']) &&  !empty($web_settings['instagram_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['instagram_link'] ?>" target="_blank" aria-label="instragram link" class="text-decoration-none"><i class="uil uil-instagram"></i></a>
            <?php } ?>
            <?php if (isset($web_settings['youtube_link']) &&  !empty($web_settings['youtube_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['youtube_link'] ?>" target="_blank" aria-label="youtube-link" class="text-decoration-none"><i class="uil uil-youtube"></i></a>
            <?php } ?> -->



            <?php if (isset($web_settings['twitter_link']) && !empty($web_settings['twitter_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['twitter_link'] ?>" target="_blank" aria-label="twitter-link" class="text-decoration-none">
                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/twitter-icon.png') ?>">
                </a>
            <?php } ?>
            <?php if (isset($web_settings['facebook_link']) &&  !empty($web_settings['facebook_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['facebook_link'] ?>" target="_blank" aria-label="facebook link" class="text-decoration-none">
                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/Facebook_icon.png') ?>">
                </a>
            <?php } ?>
            <?php if (isset($web_settings['instagram_link']) &&  !empty($web_settings['instagram_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['instagram_link'] ?>" target="_blank" aria-label="instragram link" class="text-decoration-none">
                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/Instagram-Icon.png') ?>">
                </a>
            <?php } ?>
            <?php if (isset($web_settings['youtube_link']) &&  !empty($web_settings['youtube_link'])) { ?>
                <a class="media-icon" href="<?= $web_settings['youtube_link'] ?>" target="_blank" aria-label="youtube-link" class="text-decoration-none">
                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/youtube-icon.png') ?>">
                </a>
            <?php } ?>

            <!-- <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/Facebook_icon.png') ?>">
            <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/twitter-icon.png') ?>">
            <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/Instagram-Icon.png') ?>">
            <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/youtube-icon.png') ?>"> -->
        </li>
    </ul>
</section>
<!-- footer ends -->



<?php if (ALLOW_MODIFICATION == 0) { ?>

    <!-- color switcher -->
    <div id="colors-switcher">
        <div>
            <h6>Pick Your Theme</h6>
            <ul class="px-2 text-center">
                <li class="list-item-inline mb-3">
                    <a class="text-decoration-none text-dark" href="<?= base_url("themes/switch/modern") ?>">
                        <p class="m-0">Modern Theme</p>
                        <img src="<?= base_url("/assets/front_end/modern/preview-image/modern.png") ?>" alt="Modern image" class="w-75">

                    </a>
                </li>
                <li class="list-item-inline mb-3">
                    <a class="text-decoration-none text-dark" href="<?= base_url("themes/switch/classic") ?>">
                        <p class="m-0">Classic Theme</p>
                        <img src="<?= base_url("/assets/front_end/classic/preview-image/classic.jpg") ?>" alt="classic image" class="w-75">
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <h6><?= !empty($this->lang->line('pick_your_favorite_color')) ? $this->lang->line('pick_your_favorite_color') : 'Pick Your Favorite Color' ?></h6>
            <ul class="color-style text-center mb-2">
                <li class="list-item-inline">
                    <a href="#" class="color-switcher orange" aria-label="orange-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/orange.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/orange.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher blue" aria-label="blue-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/blue.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/dark-blue.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher aqua" aria-label="aqua-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/aqua.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/aqua.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher fuchsia" aria-label="fuchsia-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/fuchsia.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/fuchsia.png") ?>"></a>
                </li>

                <li class="list-item-inline">
                    <a href="#" class="color-switcher grape" aria-label="grape-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/grape.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/grape.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher green" aria-label="green-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/green.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/green.png") ?>"></a>
                </li>

                <li class="list-item-inline">
                    <a href="#" class="color-switcher leaf" aria-label="leaf-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/leaf.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/leaf.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher navy" aria-label="navy-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/navy.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/navy.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher pink" aria-label="pink-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/pink.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/pink.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher purple" aria-label="purple-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/purple.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/purple.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher red" aria-label="red-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/red.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/red.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher sky" aria-label="sky-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/sky.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/sky.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher violet" aria-label="violet-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/violet.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/violet.png") ?>"></a>
                </li>

            </ul>
            <div class="color-bottom">
                <a href="#" aria-label="color-switcher" class="settings bg-white d-block"><i class="fa fa-cog fa-lg fa-spin setting-icon"></i></a>
            </div>
        </div>
    </div> <!-- end color switcher -->
<?php } ?>


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

                            <div style="position: absolute; width: 100%; height: 100%; pointer-events: none;">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="login-left">
                                <h1 class="heading-n ta-c">Login</h1>
                                <p class="text-n ta-c op-6">Fill your email/phone and password to sign in!</p>
                                
                                <div class="field-container">
                                    
                                    <!-- <input class="input ta-c" type="text" placeholder="Phone Number / Email ID" > -->

                                    <input class="form-control input ta-c" type="text" name="identity" placeholder="Phone Number / Email ID" id="loginEmail" value="<?= (ALLOW_MODIFICATION == 0) ? '1212121212' : '' ?>">
                                    <!-- <label for="loginEmail">Enter Mobile Number / Email</label> -->

                                    <!-- <br> -->
                                    <!-- <input class="input ta-c" type="password" placeholder="Enter your Password"> -->

                                    <div class="password-container">
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
                                            <a href="#" id="googleLogin" class="text-decoration-none">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/google-icon.jpg') ?>">
                                                    <p class="text-s">Sign in with Google</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                        <?php if (true || !empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                            <a href="#" id="facebookLogin" class="text-decoration-none">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/facebook-icon.jpg') ?>">
                                                    <p class="text-s">Login with Facebook</p>
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


                <section class="d-none" id="forgot_password_div">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center h5"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password' ?></div>
                    <hr class="mt-0 mb-5">
                    <form id="send_forgot_password_otp_form" method="POST" action="#">
                        <div class="input-group">
                            <input type="text" class="form-control" name="mobile_number" id="forgot_password_number" placeholder="Mobile number" value="">
                        </div>
                        <div class="col-12 d-flex justify-content-center pb-4 mt-3">
                            <div id="recaptcha-container-2"></div>
                        </div>
                        <footer>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-soft-dark btn-sm rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type="submit" id="forgot_password_send_otp_btn" class="submit_btn btn btn-primary btn-sm rounded-pill"><?= !empty($this->lang->line('send_otp')) ? $this->lang->line('send_otp') : 'Send OTP' ?></button>
                        </footer>
                        <br>
                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="forgot_pass_error_box"></div>
                        </div>
                    </form>
                    <form id="verify_forgot_password_otp_form" class="d-none" method="post" action="#">
                        <div class="input-group mb-3">
                            <input type="text" id="forgot_password_otp" class="form-control" name="otp" placeholder="OTP" value="" autocomplete="off" required>
                        </div>
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" name="new_password" placeholder="New Password" value="" required>
                        </div>
                        <footer>
                            <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal" aria-label="Close"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill submit_btn" id="reset_password_submit_btn"><?= !empty($this->lang->line('submit')) ? $this->lang->line('submit') : 'Submit' ?></button>
                        </footer>
                        <br>
                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="set_password_error_box"></div>
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
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <!-- <div class="modal-content text-center"> -->

            <!-- <div class="modal-body"> -->
                
                
                <!-- signup container -->
                <section id="register_div" class="login-container modal-content modal-body">

                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->

                    <form id='send-otp-form' class='send-otp-form' action='#'>
                        <!-- signup 1 -->
                        <div id="signupone" class="login rounded-1">

                            <div style="position: absolute; width: 100%; height: 100%; pointer-events: none;">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="login-left pb-4">
                                <h1 class="heading-n ta-c">Sign Up</h1>
                                <p class="text-n ta-c op-6">Registration takes less than a minute.</p>
                                

                                <!-- <div class="row sign-up-verify-number">
                                    <div class="col-12 d-flex justify-content-center pb-4">
                                        <input type="text" class='form-input form-control' placeholder="Enter Mobile Number" id="phone-number" required>
                                    </div>
                                </div> -->
                        
                                <div class="field-container ta-c">
                                    <input id="phone-number" class="form-input form-control input ta-c" type="text" placeholder="Enter your Phone Number" required>
                                    <!-- <!- <br> -> -->
                                </div>

                                <div class="hide text-danger ta-c" id="error-msg"></div>

                                <div id="recaptcha-container" class="ta-c d-flex justify-content-center mb-3"></div>

                                <div id='is-user-exist-error' class='text-center text-danger ta-c'></div>
                                
                                <div class="ta-c btn-container">
                                    <button id="send-otp-button" class="cretzo btn btn-dark">Send OTP</button>
                                </div>

                                <p class="text-n mb-0 ta-c mt-3">Already have an account? <a class="text-decoration-none text-blue hover text-underline c-p fw-bold" href="#" data-bs-target="#modal-signin" data-bs-toggle="modal" data-bs-dismiss="modal" class="hover" style="color: var(--color-orange) !important;">Sign In</a></p>

                                <?php if ((true || !empty($system_settings['google_login']) && $system_settings['google_login'] == 1) || (!empty($system_settings['facebook_login']) && $system_settings['facebook_login'] == 1)) { ?>
                                    <div class="login-with-container mt-3">
                                        <?php if (true || !empty($system_settings['google_login']) && ($system_settings['google_login'] == 1 || $system_settings['google_login'] == '1')) { ?>
                                            <a href="#" id="googleLogin" class="text-decoration-none">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/google-icon.jpg') ?>">
                                                    <p class="text-s">Signup in with Google</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                        <?php if (true || !empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                            <a href="#" id="facebookLogin" class="text-decoration-none">
                                                <div class="media-container">
                                                    <img class="media-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/facebook-icon.jpg') ?>">
                                                    <p class="text-s">Signup with Facebook</p>
                                                </div>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <div class="page-icon-container">
                                    <div class="page-icon page-icon-active"></div>
                                    <div class="page-icon"></div>
                                </div>
                            </div>
                            <div class="login-right" style="background-image: url(<?= base_url('assets/front_end/cretzo/img/new_cretzo/login-img.png') ?>);">
                            </div>
                        </div>
                        <!-- /signup 1 -->
                    </form>
                    
                    <form id='verify-otp-form' class='verify-otp-form d-none rounded-1' action='<?= base_url('auth/register-user') ?>' method="POST">
                        <!-- signup 2 -->
                        <div id="signuptwo" class="login rounded-1">

                            <div style="position: absolute; width: 100%; height: 100%; pointer-events: none;">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="login-left pb-4">
                                <h1 class="heading-n ta-c">Sign Up</h1>
                                <p class="text-n ta-c op-6">Almost there...</p>
                                
                                <input type="hidden" class='form-input form-control' id="type" name="type" value="phone" autocomplete="off">

                                <div class="field-container">
                                    <input type="text" class='form-input form-control input ta-c' placeholder="Enter OTP" id="otp" name="otp" autocomplete="off">
                                    <!-- <br> -->
                                    <input type="text" class='form-input form-control input ta-c' placeholder="Enter Username" id="name" name="name">
                                    <!-- <br> -->
                                    <input type="email" class='form-input form-control input ta-c' placeholder="Enter your Email" id="email" name="email">
                                    <!-- <br> -->
                                    <div class="password-container">
                                        <input type="password" class='form-input form-control input ta-c' placeholder="Enter Password" id="password" name="password">
                                        <input class="form-input form-control input ta-c" type="password" placeholder="Re-enter Password" id="confirm-password">
                                        <!-- <span class="password-toggle d-flex"><i class="uil uil-eye mb-4 mr-2"></i></span> -->
                                        <span class="password-toggle"><i class="uil uil-eye"></i></span>
                                    </div>

                                    <!-- <div class="col-12 d-flex justify-content-center pb-4">
                                        <div id='registration-error' class='text-center p-3 text-danger'></div>
                                    </div> -->
                                    <div id='registration-error' class='text-center p-3 text-danger'></div>
                                    
                                    <div>
                                        <div>
                                            <input class="checkbox" type="checkbox">
                                            <label class="label text-n">Remember Me</label>
                                        </div>
                                        <div>
                                            <input class="checkbox" type="checkbox">
                                            <label class="label text-n">Terms & Conditions</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ta-c btn-container">
                                    <button type="submit" id='register_submit_btn' class="cretzo btn btn-dark">Register Now</button>
                                </div>
                                <div class="page-icon-container">
                                    <div class="page-icon"></div>
                                    <div class="page-icon page-icon-active"></div>
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

<!-- quick view -->
<div id="quick-view" data-iziModal-group="grupo3" class='product-page-content'>
    <button data-izimodal-close="" class="icon-close btn btn-circle bg-soft-primary" style="top: 9px;right: 9px;">
        <i class="fa fa-close fs-18 text-dark"></i>
    </button>
    <div class="row p-4">

        <!-- /.swiper-container -->
        <div class="col-12 col-sm-6 product-preview-image-section-md swiper-thumbs-container">
            <div class="swiper-container gallery-top overflow-hidden">
                <div class="swiper-wrapper-main swiper-wrapper"></div>
            </div>
            <div class="swiper-container gallery-thumbs overflow-hidden mt-10">
                <div class="swiper-wrapper-thumbs swiper-wrapper"></div>
            </div>
        </div>
        <!-- Mobile Product Image Slider -->
        <div class="col-12 col-sm-6 product-preview-image-section-sm">
            <div class="swiper-container mobile-image-swiper">
                <div class="mobile-swiper swiper-wrapper-mobile swiper-wrapper"></div>
                <!-- <div class="swiper-pagination mobile-image-swiper-pagination text-center"></div> -->
            </div>
        </div>

        <div class="col-12 col-sm-6 product-page-details">
            <h3 class="my-3 product-title" id="modal-product-title"></h3>
            <div id="modal-product-sellers"></div>
            <div id="modal-product-statistics"></div>
            <div id="modal-product-brand" class="d-flex gap-1"></div>
            <p id="modal-product-short-description"></p>
            <hr class="mb-2 mt-2">

            <input type="text" id="modal-product-rating" class="d-none" data-size="xs" value="0" data-show-clear="false" data-show-caption="false" readonly>
            (<span class="rating-status" id="modal-product-no-of-ratings">1203</span> <?= !empty($this->lang->line('reviews')) ? $this->lang->line('reviews') : 'reviews' ?> )
            <!-- </div> -->
            <p class="mb-0 price">
                <span id="modal-product-price"></span>
                <sup>
                    <span class="striped-price text-danger" id="modal-product-special-price-div">
                        <s id="modal-product-special-price"></s>
                    </span>
                </sup>
            </p>
            <div id="modal-product-variant-attributes"></div>
            <div id="modal-product-variants-div"></div>
            <div class="num-block skin-2 py-2 pt-4 pb-4 mt-2">
                <div class="num-in form-control d-flex align-items-center">
                    <span class="minus dis"></span>
                    <input type="text" class="in-num" id="modal-product-quantity">
                    <span class="plus"></span>
                </div>
            </div>
            <div class="d-flex mb-3 mt-2 text-center text-md-left gap-2">
                <!-- <div> -->
                <button class="m-0 add_to_cart mt-1 btn btn-sm btn-yellow rounded-pill w-100" id="modal-add-to-cart-button">&nbsp;<i class="uil uil-shopping-bag fs-16"></i> <?= !empty($this->lang->line('add_to_cart')) ? $this->lang->line('add_to_cart') : 'Add To Cart' ?></button>
                <!-- </div>
                <div> -->
                <button type="button" name="compare" class="btn btn-sm btn-outline-blue rounded-pill h-9 m-0 mt-1 compare" id="compare"><i class="uil uil-exchange-alt fs-20"></i></button>
                <!-- </div>
                <div> -->
                <button class="btn btn-sm btn-outline-red rounded-pill h-9 m-0 add-fav mt-1" id="add_to_favorite_btn"><i class="fa fa-heart fs-20"></i></button>
                <!-- </div> -->
            </div>

            <div class="mt-2">
                <span>
                    <div id="modal-product-tags"></div>
                </span>
            </div>
        </div>
    </div>
</div>

<?php if (isset($system_settings['whatsapp_number']) && !empty($system_settings['whatsapp_number'])) { ?>
    <div class="whatsapp-icon">
        <a href="https://api.whatsapp.com/send?phone=<?= $system_settings['whatsapp_number'] ?>&text&type=phone_number&app_absent=0" target="_blank" class="btn"><img src="<?= base_url('assets/logo/whatsapp_icon.png') ?>" alt="whatsapp"></a>
    </div>
<?php } ?>

<?php if (ALLOW_MODIFICATION == 0) { ?>
    <div class="buy-now-btn">
        <a href="https://codecanyon.net/item/eshop-web-multi-vendor-ecommerce-marketplace-cms/34380052" target="_blank" class="btn btn-danger btn-sm rounded-pill"> <i class="fa fa-shopping-cart"></i>&nbsp; <?= !empty($this->lang->line('buy_now')) ? $this->lang->line('buy_now') : 'Buy Now' ?></a>
    </div>
<?php } ?>

<div class="fixed-icon">
    <?php if ($this->ion_auth->logged_in()) { ?>
        <div id="chat-button"><i class="uil uil-comments"></i></div>
        <!-- Floating chat iframe -->
        <iframe src="<?= base_url('my-account/floating_chat_modern') ?>" id="chat-iframe" style="display: none; position: fixed; bottom: 80px; right: 20px; width: 450px; height: 600px; border: none;z-index:999;"></iframe>
    <?php } ?>
    <div class="progress-wrap mt-2">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>
</div>
<!-- end -->
<!-- main content ends -->
<script>
$(document).ready(function () {

    $(".search_field").on("keyup", function () {
        let search = $(this).val().trim();

        if (search.length < 1) {
            $("#append_desktop_search").html("");
            $("#append_mobile_search").html("");
            return;
        }

        $.ajax({
            url: base_url+"/search/search_data",
            type: "GET",
            data: { search: search },
            dataType: "json",
            success: function (response) {

                let html = "";

                // Ensure response is parsed JSON
                if (typeof response === "string") {
                    response = JSON.parse(response);
                }

                if (response.data && response.data.length > 0) {
                    html += '<div class="list-group">';

                    $.each(response.data, function (index, item) {

                        // Proper escaping
                        let safeName = item.name.replace(/"/g, '&quot;');

                        html += `
                            <div class="search-item text-n mega-list-item" 
                                 onclick="selectSuggestion(&quot;${safeName}&quot;)">
                                ${item.name}
                            </div>
                        `;
                    });

                    html += '</div>';

                } else {
                    html = '<div class="search-item text-n mega-list-item">No results found</div>';
                }

                $("#append_desktop_search").html(html);
                $("#append_mobile_search").html(html);
            },
            error: function () {
                $("#append_desktop_search").html(
                    '<div class="search-item">Error fetching data</div>'
                );
            }
        });

    });

});

// Fill value into search box
function selectSuggestion(name) {
    $(".search_field").val('');
    window.location.href = base_url + '/products/search?q=' + encodeURIComponent(name);
        
    // $(".search_field").val(name);
    // $("#append_desktop_search").html("");
    // $("#append_mobile_search").html("");
}

// Hide suggestions when clicking outside
$(document).click(function (e) {
    if (!$(e.target).closest('.search-container-m').length) {
        $("#append_desktop_search").html("");
        $("#append_mobile_search").html("");
    }
});
</script>

<link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<style>
/* Target the success toast specifically */
#toast-container > .toast-success {
    background-color: #f59b58 !important; /* Your Orange */
    color: #fff !important;
    opacity: 1;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    
    /* Making it smaller */
    width: 200px !important; 
    min-height: 40px !important;
    padding: 10px 15px 10px 45px !important; /* Adjusted for icon space */
    font-size: 13px;
}

/* Adjusting the progress bar to match */
#toast-container > .toast-success .toast-progress {
    background-color: #fff;
    opacity: 0.4;
}

/* Ensure the icon stays white */
.toast-success {
    background-image: none !important; /* Optional: removes default icon if it looks crowded */
}
</style>

<script>
function addtocartMessage() {
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "showDuration": "300",
        "hideDuration": "300",
        "timeOut": "2000", // 2 seconds
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    toastr.success("Added to Cart");
}
</script>
<script>
    $(window).on('scroll', function () {
    if ($(window).scrollTop() > 100) {
        $('#append_desktop_search').css('display', 'none');
    }else{
        $('#append_desktop_search').css('display', 'block');
    }
});

</script>

