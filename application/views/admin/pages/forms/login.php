<?php if (ALLOW_MODIFICATION == 0) { ?>
    <div class="alert alert-warning">
        Note: If you cannot login here, please close the codecanyon frame by clicking on x Remove Frame button from top right corner on the page or <a href="<?= base_url('/admin') ?>" target="_blank" class="text-danger">>> Click here << </a>
    </div>
<?php } ?>
<div class="login-box cz-login">
    <!-- Brand / left panel -->
    <div class="cz-brand">
        <div class="cz-logo">
            <a href="<?= base_url() . 'admin/login' ?>"><img src="<?= get_image_url($logo, 'thumb', 'sm'); ?>" alt="Cretzo logo"></a>
            <p class="cz-tagline">Welcome to the zone of creativity</p>
        </div>
        <div class="cz-illustration">
            <img src="<?= base_url('assets/admin/images/eshop_img.jpg') ?>" alt="Cretzo admin">
        </div>
        <h2 class="cz-mission">Manage your marketplace with ease</h2>
    </div>

    <!-- Form / right panel -->
    <div class="cz-form">
        <h2 class="cz-form-title">Welcome Back!</h2>
        <p class="cz-subtitle">Please login to your account</p>

        <form action="<?= base_url('auth/login') ?>" class='form-submit-event' method="post">
            <input type='hidden' name='<?= $this->security->get_csrf_token_name() ?>' value='<?= $this->security->get_csrf_hash() ?>'>
            <div class="cz-field">
                <label for="mobile">Mobile</label>
                <input type="<?= $identity_column ?>" class="form-control form-input" name="identity" id="mobile" placeholder="Enter Your <?= ucfirst($identity_column)  ?>" value="<?= (ALLOW_MODIFICATION == 0) ? '9876543210' : '' ?>">
            </div>
            <div class="cz-field">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" class="form-control form-input" name="password" id="password" placeholder="Enter Your Password" value="<?= (ALLOW_MODIFICATION == 0) ? '12345678' : '' ?>">
                    <button type="button" class="toggle-password" aria-label="Show password" onclick="togglePassword(this)">
                        <svg class="eye-on" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div class="cz-row">
                <label class="cz-remember" for="remember">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Remember Me</span>
                </label>
                <a href="<?= base_url('/admin/login/forgot_password') ?>" class="cz-forgot"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password' ?>?</a>
            </div>

            <button type="submit" id="submit_btn" class="btn btn-block btn-signin">Sign In</button>

            <div class="cz-error" id="error_box"></div>
        </form>
    </div>
</div>
<!-- /.login-box -->
<script>
    function togglePassword(btn) {
        var input = btn.parentElement.querySelector('input');
        var showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        btn.classList.toggle('is-visible', showing);
        btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
    }
</script>
