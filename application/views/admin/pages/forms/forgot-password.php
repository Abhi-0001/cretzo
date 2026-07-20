<div class="login-box cz-login">
  <!-- Brand / left panel -->
  <div class="cz-brand">
    <div class="cz-logo">
      <a href="<?= base_url('admin') ?>"><img src="<?= get_image_url($logo, 'thumb', 'sm'); ?>" alt="Cretzo logo"></a>
      <p class="cz-tagline">Welcome to the zone of creativity</p>
    </div>
    <div class="cz-illustration">
      <img src="<?= base_url('assets/admin/images/eshop_img.jpg') ?>" alt="Cretzo admin">
    </div>
    <h2 class="cz-mission">Manage your marketplace with ease</h2>
  </div>

  <!-- Form / right panel -->
  <div class="cz-form">
    <h2 class="cz-form-title"><?= !empty($this->lang->line('forgot_password')) ? $this->lang->line('forgot_password') : 'Forgot Password' ?></h2>
    <p class="cz-subtitle">Enter your mobile number to receive an OTP</p>

    <form id="send_forgot_password_otp_form" method="POST" action="#">
      <div class="cz-field">
        <label for="forgot_password_number">Mobile Number</label>
        <input type="text" class="form-control form-input" name="mobile_number" id="forgot_password_number" placeholder="Enter Mobile number" value="">
      </div>
      <div class="cz-field d-flex justify-content-center">
        <div id="recaptcha-container-2"></div>
      </div>
      <button type="submit" id="forgot_password_send_otp_btn" class="btn btn-block btn-signin"><?= !empty($this->lang->line('send_otp')) ? $this->lang->line('send_otp') : 'Send OTP' ?></button>

      <p class="cz-back-login">
        <a href="<?= base_url('admin/login') ?>">&larr; Back to Login</a>
      </p>
      <div class="cz-error" id="forgot_pass_error_box"></div>
    </form>

    <form id="verify_forgot_password_otp_form" class="d-none" method="post" action="#">
      <div class="cz-field">
        <label for="forgot_password_otp">OTP</label>
        <input type="text" id="forgot_password_otp" class="form-control form-input" name="otp" placeholder="Enter OTP" value="" autocomplete="off" required>
      </div>
      <div class="cz-field">
        <label for="new_password">New Password</label>
        <input type="password" class="form-control form-input" name="new_password" id="new_password" placeholder="Enter New Password" value="" required>
      </div>
      <button type="submit" class="btn btn-block btn-signin" id="reset_password_submit_btn"><?= !empty($this->lang->line('submit')) ? $this->lang->line('submit') : 'Submit' ?></button>

      <div class="cz-error" id="set_password_error_box"></div>
    </form>
  </div>
</div>
<!-- /.login-box -->
