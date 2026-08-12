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
      <button type="submit" id="forgot_password_send_otp_btn" class="btn btn-block btn-signin"><?= !empty($this->lang->line('send_otp')) ? $this->lang->line('send_otp') : 'Send OTP' ?></button>

      <p class="cz-back-login">
        <a href="<?= base_url('admin/login') ?>">&larr; Back to Login</a>
      </p>
      <div class="cz-error" id="forgot_pass_error_box"></div>
      <?php // Firebase phone auth renders its invisible reCAPTCHA here. ?>
      <div id="recaptcha-password-reset"></div>
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

<?php
// This site's authentication_method is "firebase" and no SMS gateway is configured, so the
// OTP text is sent and confirmed by Firebase in the browser. The legacy server-side-OTP
// handlers below still load and still work if an SMS gateway is ever configured -
// firebase-password-reset.js suppresses them via stopImmediatePropagation() when it is active.
$fb_use = (!empty($authentication_method) && $authentication_method === 'firebase' && !empty($firebase_settings['apiKey']));
if ($fb_use) :
?>
  <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
  <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-auth.js"></script>
  <script src="<?= base_url() ?>firebase-config.js"></script>
  <script>
      window.FIREBASE_RESET_CONFIG = {
          checkUrl: "<?= base_url('admin/login/check_reset_account') ?>",
          resetUrl: "<?= base_url('admin/login/reset_password_firebase') ?>",
          redirectUrl: "<?= base_url('admin/login') ?>",
          recaptchaId: "recaptcha-password-reset",
          defaultDialCode: "+91"
      };
  </script>
  <script src="<?= base_url('assets/firebase-password-reset.js') ?>"></script>
<?php endif; ?>

<script>
    $(document).on("submit", "#send_forgot_password_otp_form", function(e) {
        e.preventDefault();
        var btn = $("#forgot_password_send_otp_btn"), t = btn.html();
        btn.html("Please Wait...").attr("disabled", true);
        $.ajax({
            type: "POST",
            url: "<?= base_url('admin/login/send_reset_otp') ?>",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                btn.html(t).attr("disabled", false);
                $("#forgot_pass_error_box").text(res.message);
                if (!res.error) {
                    $("#verify_forgot_password_otp_form").removeClass("d-none");
                    $("#send_forgot_password_otp_form").hide();
                }
            },
            error: function() {
                btn.html(t).attr("disabled", false);
                $("#forgot_pass_error_box").text("Something went wrong. Please try again.");
            }
        });
    });
    $(document).on("submit", "#verify_forgot_password_otp_form", function(e) {
        e.preventDefault();
        var btn = $("#reset_password_submit_btn"), t = btn.html();
        var data = $(this).serialize() + "&mobile_number=" + encodeURIComponent($("#forgot_password_number").val());
        btn.html("Please Wait...").attr("disabled", true);
        $.ajax({
            type: "POST",
            url: "<?= base_url('admin/login/reset_password') ?>",
            data: data,
            dataType: "json",
            success: function(res) {
                btn.html(t).attr("disabled", false);
                $("#set_password_error_box").text(res.message);
                if (!res.error) {
                    setTimeout(function() { window.location.href = "<?= base_url('admin/login') ?>"; }, 2000);
                }
            },
            error: function() {
                btn.html(t).attr("disabled", false);
                $("#set_password_error_box").text("Something went wrong. Please try again.");
            }
        });
    });
</script>
