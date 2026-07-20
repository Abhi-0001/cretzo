<?php $web_settings = get_settings('web_settings', true);
$system_settings = get_settings('system_settings', true); ?>
<!-- footer starts -->

<footer class="text-inverse">
    <section class="angled bg-navy pt-1 upper-end wrapper">
        <div class="container pb-4 pt-4">
            <!-- <hr class="mt-10 mb-10" /> -->
            <div class="row gy-6 gy-lg-0">
                <div class="col-md-4 col-lg-3">
                    <div class="widget">
                        <div class="footer-logo-footer">
                            <?php if (ALLOW_MODIFICATION == 0) { ?>
                                <img src="<?= base_url("assets/front_end/modern/img/logo/orange.png") ?>" class="brand-logo-link logo-img" alt="site-logo image">
                            <?php } else { ?>
                                <?php $logo = get_settings('web_logo'); ?>
                                <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" alt="logo"></a>
                            <?php } ?>
                        </div>
                        <?php if (isset($web_settings['address']) && !empty($web_settings['address'])) { ?>
                            <div class="pe-xl-15 pe-xxl-17">
                                <div class="single-cta">
                                    <div class="cta-text">
                                        <p><?= output_escaping(str_replace('\r\n', '</br>', $web_settings['address'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php $company_name = get_settings('web_settings', true);
                        if (isset($company_name['copyright_details']) && !empty($company_name['copyright_details'])) {
                        ?>
                            <p> <?= (isset($company_name['copyright_details']) && !empty($company_name['copyright_details'])) ? output_escaping(str_replace('\r\n', '&#13;&#10;', $company_name['copyright_details'])) : " " ?> </p>
                        <?php } else { ?>
                            <p>Copyright &copy; <?= date('Y') - 1  ?> - <?= date('Y') ?>, All Right Reserved <a target="_blank" href="https://www.wrteam.in/">WRTeam</a></p>
                        <?php } ?>
                        <nav class="nav social social-white">
                            <?php if (isset($web_settings['twitter_link']) && !empty($web_settings['twitter_link'])) { ?>
                                <a href="<?= $web_settings['twitter_link'] ?>" target="_blank" aria-label="twitter-link" class="text-decoration-none"><i class="uil uil-twitter"></i></a>
                            <?php } ?>
                            <?php if (isset($web_settings['facebook_link']) &&  !empty($web_settings['facebook_link'])) { ?>
                                <a href="<?= $web_settings['facebook_link'] ?>" target="_blank" aria-label="facebook link" class="text-decoration-none"><i class="uil uil-facebook-f"></i></a>
                            <?php } ?>
                            <?php if (isset($web_settings['instagram_link']) &&  !empty($web_settings['instagram_link'])) { ?>
                                <a href="<?= $web_settings['instagram_link'] ?>" target="_blank" aria-label="instragram link" class="text-decoration-none"><i class="uil uil-instagram"></i></a>
                            <?php } ?>
                            <?php if (isset($web_settings['youtube_link']) &&  !empty($web_settings['youtube_link'])) { ?>
                                <a href="<?= $web_settings['youtube_link'] ?>" target="_blank" aria-label="youtube-link" class="text-decoration-none"><i class="uil uil-youtube"></i></a>
                            <?php } ?>
                        </nav>
                        <!-- /.social -->
                    </div>
                    <!-- /.widget -->
                </div>
                <!-- /column -->
                <div class="col-md-12 col-lg-3">
                    <div class="widget">
                        <!-- <h4 class="widget-title text-white mb-3"><?= !empty($this->lang->line('find_us')) ? $this->lang->line('find_us') : 'Find us' ?></h4>
                        <?php if (isset($web_settings['address']) && !empty($web_settings['address'])) { ?>
                            <div class="pe-xl-15 pe-xxl-17">
                                <div class="single-cta">
                                    <div class="cta-text">
                                        <p><?= output_escaping(str_replace('\r\n', '</br>', $web_settings['address'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?> -->
                        <?php if (isset($web_settings['support_number']) && !empty($web_settings['support_number'])) { ?>
                            <a href="tel:<?= $web_settings['support_number'] ?>">
                                <div class="single-cta">
                                    <div class="cta-text">
                                        <h4 class="widget-title text-white"><?= !empty($this->lang->line('call_us')) ? $this->lang->line('call_us') : 'Call us' ?></h4>
                                        <p><?= $web_settings['support_number'] ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if (isset($web_settings['support_email']) && !empty($web_settings['support_email'])) { ?>
                            <a href="mailto:<?= $web_settings['support_email'] ?>" class="text-decoration-none">
                                <div class="single-cta">
                                    <div class="cta-text">
                                        <h4 class="widget-title text-white"><?= !empty($this->lang->line('mail_us')) ? $this->lang->line('mail_us') : 'Mail us' ?></h4>
                                        <p><?= $web_settings['support_email'] ?></p>
                                    </div>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                </div>
                <!-- /column -->
                <div class="col-md-4 col-lg-3">
                    <div class="widget">
                        <h4 class="widget-title text-white mb-3">Useful Link</h4>
                        <ul class="list-unstyled  mb-0">
                            <li><a href="<?= base_url('seller/auth/sign_up') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('become_a_seller')) ? $this->lang->line('become_a_seller') : 'Become a Seller' ?></a></li>
                            <li><a href="<?= base_url('home/return-policy') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('return_policy')) ? $this->lang->line('return_policy') : 'Return Policy' ?></a></li>
                            <li><a href="<?= base_url('home/shipping-policy') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('shipping_policy')) ? $this->lang->line('shipping_policy') : 'Shipping Policy' ?></a></li>
                            <li><a href="<?= base_url('products') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('products')) ? $this->lang->line('products') : 'Products' ?></a></li>
                            <li><a href="<?= base_url('home/terms-and-conditions') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('terms_and_condition')) ? $this->lang->line('terms_and_condition') : 'Terms & Conditions' ?></a></li>
                            <li><a href="<?= base_url('home/privacy-policy') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('privacy_policy')) ? $this->lang->line('privacy_policy') : 'Privacy Policy' ?></a></li>
                            <li><a href="<?= base_url('home/about-us') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('about_us')) ? $this->lang->line('about_us') : 'About Us' ?></a></li>
                            <li><a href="<?= base_url('home/contact-us') ?>" class="text-decoration-none hover"><?= !empty($this->lang->line('contact_us')) ? $this->lang->line('contact_us') : 'Contact Us' ?></a></li>
                        </ul>
                    </div>
                    <!-- /.widget -->
                </div>
                <!-- /column -->
                <div class="col-md-4 col-lg-3">
                    <div class="widget">
                        <!-- <div class="footer-widget"> -->
                        <div class="footer-widget-heading">
                            <h4 class="widget-title text-white mb-3"><?= !empty($this->lang->line('about_us')) ? $this->lang->line('about_us') : 'About Us' ?></h4>
                        </div>
                        <div class="footer-text">
                            <?php if (isset($web_settings['app_short_description'])) { ?>
                                <p><?= $web_settings['app_short_description'] ?></p>
                            <?php } ?>
                        </div>
                        <!-- </div> -->
                    </div>
                    <!-- /.widget -->
                </div>
                <!-- /column -->
            </div>
            <!--/.row -->
        </div>
        <!-- /.container -->
    </section>
</footer>

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
        <div class="modal-content text-center">
            <div class="modal-body">
                <section id="login_div">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h2 class="mb-3 text-start">Welcome Back</h2>
                    <p class="lead mb-6 text-start">Fill your email and password to sign in.</p>
                    <form action="<?= base_url('home/login') ?>" class='form-submit-event' id="login_form" method="post">
                        <input type="hidden" class="form-control" name="type" value="phone">
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" name="identity" placeholder="Enter Mobile Number / Email" id="loginEmail" value="<?= (ALLOW_MODIFICATION == 0) ? '1212121212' : '' ?>">
                            <label for="loginEmail">Enter Mobile Number / Email</label>
                        </div>
                        <div class="form-floating password-field mb-4">
                            <input type="password" class="form-control" name="password" placeholder="Password" id="loginPassword" value="<?= (ALLOW_MODIFICATION == 0) ? '12345678' : '' ?>">
                            <span class="password-toggle"><i class="uil uil-eye"></i></span>
                            <label for="loginPassword">Password</label>
                        </div>
                        <footer>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-soft-dark btn-sm rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type="submit" class="submit_btn btn btn-primary btn-sm rounded-pill"><?= !empty($this->lang->line('login')) ? $this->lang->line('login') : 'Login' ?></button>
                        </footer>
                        <br>

                        <p class="mb-1">
                            <a href="<?= base_url() ?>" id="forgot_password_link" class="text-decoration-none text-blue fs-15 hover"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password' ?> ?</a>
                        </p>
                        <p class="mb-0">Don't have an account? <a class="text-decoration-none text-blue fs-15 hover" href="#" data-bs-target="#modal-signup" data-bs-toggle="modal" data-bs-dismiss="modal" class="hover">Sign up</a></p>

                        <?php if ((!empty($system_settings['google_login']) && $system_settings['google_login'] == 1) || (!empty($system_settings['facebook_login']) && $system_settings['facebook_login'] == 1)) { ?>
                            <div class="divider-icon my-4">or</div>
                            <div class="row">
                                <div class="social-login col-md-12 text-center mt-3">
                                    <?php if (!empty($system_settings['google_login']) && ($system_settings['google_login'] == 1 || $system_settings['google_login'] == '1')) { ?>
                                        <a href="#" id="googleLogin" class="btn btn-circle btn-sm btn-google btn-red">
                                            <i class="uil uil-google"></i></a>
                                    <?php } ?>
                                    <?php if (!empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                        <a href="#" id="facebookLogin" class="btn btn-circle btn-sm btn-facebook-f btn-blue ms-2">
                                            <i class="uil uil-facebook-f"></i>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="d-flex justify-content-center">
                            <div class="form-group" id="error_box"></div>
                        </div>
                    </form>
                </section>
                <!-- login section complete -->


                <section class="hide pt-0" id="forgot_password_div">
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
            </div>
            <!--/.modal-content -->
        </div>
        <!--/.modal-body -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->


<div class="modal fade" id="modal-signup" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <h2 class="mb-3 text-start">Sign up Here</h2>
                <p class="lead mb-6 text-start">Registration takes less than a minute.</p>
                <section id="register_div">
                    <form id='send-otp-form' class='send-otp-form' action='#'>
                        <div class="row sign-up-verify-number">
                            <div class="col-12 d-flex justify-content-center pb-4">
                                <input type="text" class='form-input form-control' placeholder="Enter Mobile Number" id="phone-number" required>
                            </div>
                            <div class="col-12 d-flex justify-content-center pb-4">
                                <div id="error-msg" class="hide text-danger"><?= !empty($this->lang->line('enter_valid_number')) ? $this->lang->line('enter_valid_number') : 'Enter a valid number' ?></div>
                            </div>
                            <div class="col-12 d-flex justify-content-center">
                                <div id="recaptcha-container"></div>
                            </div>
                            <div class="col-12 d-flex justify-content-center pb-4">
                                <div id='is-user-exist-error' class='text-center p-3 text-danger'></div>
                            </div>
                        </div>
                        <footer>
                            <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-soft-dark btn-sm rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button id='send-otp-button' class="btn btn-primary btn-sm rounded-pill"><?= !empty($this->lang->line('send_otp')) ? $this->lang->line('send_otp') : 'Send OTP' ?></button>
                        </footer>
                        <p class="mb-0 mt-6">Already have an account? <a class="text-decoration-none text-blue fs-15 hover" href="#" data-bs-target="#modal-signin" data-bs-toggle="modal" data-bs-dismiss="modal" class="hover">Sign in</a></p>

                        <?php if ((!empty($system_settings['google_login']) && $system_settings['google_login'] == 1) || (!empty($system_settings['facebook_login']) && $system_settings['facebook_login'] == 1)) { ?>
                            <br>
                            <div class="divider-icon mt-0 mb-3">or</div>
                            <div class="row">
                                <div class="social-login col-md-12 text-center mt-3">
                                    <?php if (!empty($system_settings['google_login']) && ($system_settings['google_login'] == 1 || $system_settings['google_login'] == '1')) { ?>
                                        <a href="#" id="googleLogin" class="btn btn-circle btn-sm btn-google btn-red">
                                            <i class="uil uil-google"></i></a>
                                    <?php } ?>
                                    <?php if (!empty($system_settings['facebook_login']) && ($system_settings['facebook_login'] == 1 || $system_settings['facebook_login'] == '1')) { ?>
                                        <a href="#" id="facebookLogin" class="btn btn-circle btn-sm btn-facebook-f btn-blue ms-2">
                                            <i class="uil uil-facebook-f"></i>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </form>
                    <form id='verify-otp-form' class='verify-otp-form d-none' action='<?= base_url('auth/register-user') ?>' method="POST">
                        <div class="row sign-up-verify-number">
                            <div class="col-12 d-flex justify-content-center pb-4">
                                <input type="hidden" class='form-input form-control' id="type" name="type" value="phone" autocomplete="off">
                            </div>
                            <div class="col-12 d-flex justify-content-center pb-4 form-floating">
                                <input type="text" class='form-input form-control' placeholder="OTP" id="otp" name="otp" autocomplete="off">
                                <label for="otp">OTP</label>
                            </div>
                            <div class="col-12 d-flex justify-content-center pb-4 form-floating">
                                <input type="text" class='form-input form-control' placeholder="Username" id="name" name="name">
                                <label for="name">Username</label>
                            </div>
                            <div class="col-12 d-flex justify-content-center pb-4 form-floating">
                                <input type="email" class='form-input form-control' placeholder="Email" id="email" name="email">
                                <label for="email">Email</label>
                            </div>
                            <div class="col-12 d-flex justify-content-center pb-4 form-floating password-field">
                                <input type="password" class='form-input form-control' placeholder="Password" id="password" name="password">
                                <span class="password-toggle d-flex"><i class="uil uil-eye mb-4 mr-2"></i></span>
                                <label for="password">Password</label>
                            </div>
                            <div class="col-12 d-flex justify-content-center pb-4">
                                <div id='registration-error' class='text-center p-3 text-danger'></div>
                            </div>
                        </div>
                        <footer>
                            <button data-bs-dismiss="modal" aria-label="Close" class="btn btn-soft-dark btn-sm rounded-pill"><?= !empty($this->lang->line('cancel')) ? $this->lang->line('cancel') : 'Cancel' ?></button>
                            <button type="submit" id='register_submit_btn' class="btn btn-primary btn-sm rounded-pill"><?= !empty($this->lang->line('submit')) ? $this->lang->line('submit') : 'Submit' ?></button>
                        </footer>
                    </form>
                    <form id='sign-up-form' class='sign-up-form collapse' action='#'>
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
            </div>
            <!--/.modal-content -->
        </div>
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
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>
</div>
<!-- end -->
<!-- main content ends -->