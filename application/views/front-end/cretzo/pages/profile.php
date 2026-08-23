<?php
/*
 * `login_with_email` is not a key in the `system_settings` row on this install, but it is read
 * unguarded three times below to decide whether the mobile and email inputs are readonly - so
 * every visit to My Account > Profile emitted three "Undefined array key login_with_email"
 * warnings, and in development those print into the page body.
 *
 * Defaulted to 0, which preserves today's behaviour exactly: config/ion_auth.php sets
 * $config['identity'] = 'mobile', so the mobile number IS the login identity and must stay
 * readonly, while the email address remains editable.
 */
$login_with_email = isset($system_settings['login_with_email']) ? $system_settings['login_with_email'] : 0;

/*
 * Avatar to show in the form. `users`.`image` holds a bare file name inside USER_IMG_PATH
 * (the mobile app's update_user_profile writes it the same way), so a row can legitimately
 * name a file that is no longer on disk - get_user_avatar_url() returns '' in that case and
 * we fall back to the theme icon rather than rendering a broken image.
 */
$profile_image = get_user_avatar_url(isset($users->image) ? $users->image : '');
if ($profile_image === '') {
    // Same placeholder the header and dashboard use. NOT the NO_USER_IMAGE constant - that
    // points at assets/no-user-img.png, which does not exist in this install.
    $profile_image = base_url('assets/front_end/cretzo/img/new_cretzo/user.png');
}

$selected_gender = isset($users->gender) ? strtolower((string) $users->gender) : '';
$dob_value = isset($users->dob) ? (string) $users->dob : '';
?>
<!-- edit profile -->
<div class="overview-side-container">
            <h1 class="heading-b">Account</h1>
            <p class="text-n"><?= $users->username ?></p>
            <div class="overview-container">
                
                <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>

                <div class="overview-right">
                    
                    <div class="card p-7 rounded-0">

                        <h1 class="heading-n overview-right-heading">Edit Details</h1>

                        <hr class="mt-5 mb-5">

                        <form class="form-submit-event" method="POST" action="<?= base_url('login/update_user') ?>" enctype="multipart/form-data">

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label class="col-sm-12 col-form-label">Profile Photo</label>
                                    <div class="profile-photo-row">
                                        <img src="<?= $profile_image ?>" alt="Profile photo" id="profile_image_preview" class="profile-photo-preview">
                                        <div class="profile-photo-actions">
                                            <label for="image" class="cretzo btn btn-light profile-photo-btn mb-0">Choose Photo</label>
                                            <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/gif" class="d-none">
                                            <p class="text-s profile-photo-hint" id="profile_image_name">JPG, PNG or GIF. Optional.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">

                                <div class="form-group col-md-6">
                                    <label for="username" class="col-sm-12 col-form-label"><?= !empty($this->lang->line('username')) ? $this->lang->line('username') : 'Username' ?>*</label>
                                    <input type="text" class="form-control" id="username" placeholder="Type Username here" name="username" value="<?= $users->username ?>">
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="mobile" class="col-sm-12 col-form-label"><?= !empty($this->lang->line('mobile')) ? $this->lang->line('mobile') : 'Mobile' ?>*</label>
                                    <div>
                                        <?php // 10 digits, digits only - matches seller/customer signup and the Firebase OTP step.
                                              // type="phone" is not a real input type; "tel" is, and it brings up the
                                              // numeric keypad on mobile. Server side enforces the same rule. ?>
                                        <input type="tel" class="form-control" id="mobile" placeholder="Type 10 digit Mobile No. here" name="mobile" value="<?= $users->mobile ?>" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" title="Enter a 10 digit mobile number" <?= isset($users->type) && ($users->type == 'phone' || $users->type == '') && ($login_with_email == 0 || $login_with_email == '0') ? 'readonly' : '' ?>>
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="email" class="col-sm-12 col-form-label"><?= !empty($this->lang->line('email')) ? $this->lang->line('email') : 'Email' ?>*</label>
                                    <input type="text" class="form-control" id="email" placeholder="Type Email here" name="email" value="<?= $users->email ?>" <?= (isset($users->type) && !empty($users->type) && ($users->type == 'google' || ($users->type == 'facebook') && $users->type != '' && !empty($users->email))) || ($login_with_email == 1 || $login_with_email == '1') ? 'readonly' : '' ?>>
                                </div>

                                <?php // Gender and DOB are OPTIONAL - no asterisk, and an unanswered value
                                      // stays unanswered rather than being defaulted to anything. ?>
                                <div class="form-group col-md-6">
                                    <label for="gender" class="col-sm-12 col-form-label">Gender</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Prefer not to say</option>
                                        <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label) { ?>
                                            <option value="<?= $value ?>" <?= $selected_gender === $value ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="dob" class="col-sm-12 col-form-label">Date of Birth</label>
                                    <?php // max=today: a date of birth in the future is never valid, and blocking it in
                                          // the picker saves a round trip. The server re-checks it either way. ?>
                                    <input type="date" class="form-control" id="dob" name="dob" value="<?= $dob_value ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="form-group <?= isset($users->type) && !empty($users->type) && $users->type != 'phone' ? 'd-none' : '' ?>">
                                <label for="old" class="col-sm-12 col-form-label"><?= !empty($this->lang->line('old_password')) ? $this->lang->line('old_password') : 'Old Password' ?></label>
                                <input type="password" class="form-control" id="old" placeholder="Type Old Password here" name="old">
                            </div>
                            <div class="form-row <?= isset($users->type) && !empty($users->type) && $users->type != 'phone' ? 'd-none' : '' ?>">
                                <div class="form-group col-md-6">
                                    <label for="new" class="col-sm-12 col-form-label"><?= !empty($this->lang->line('new_password')) ? $this->lang->line('new_password') : 'New Password' ?></label>
                                    <input type="password" class="form-control" id="new" placeholder="Type New Password here" name="new">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="new_confirm" class="col-sm-12 col-form-label"><?= !empty($this->lang->line('confirm_new_password')) ? $this->lang->line('confirm_new_password') : 'Confirm New Password' ?></label>
                                    <input type="password" class="form-control" id="new_confirm" placeholder="Type Confirm Password here" name="new_confirm">
                                </div>
                            </div>
                            
                            <div class="logout-btn-container">
                                <button type="submit" class="cretzo btn btn-dark logout-btn submit_btn">Save Details</button>
                            </div>

                            <div class="d-flex justify-content-center mt-3">
                                <div class="form-group" id="error_box">
                                </div>
                            </div>
                        </form>
                        <!--end profile -->
                        <div>
                        </div>
                        <!--end col-->
                    </div>
                    
                </div>
            </div>
        </div>

<style>
    /* Scoped to the profile photo control; the theme has no avatar styles of its own. */
    .profile-photo-row {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 0 15px;
    }

    .profile-photo-preview {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e0e0e0;
        background: #fafafa;
        flex-shrink: 0;
    }

    .profile-photo-btn {
        cursor: pointer;
    }

    .profile-photo-hint {
        margin: 6px 0 0;
        color: #888;
    }
</style>

<script>
    (function () {
        var fileInput = document.getElementById('image');
        var preview = document.getElementById('profile_image_preview');
        var hint = document.getElementById('profile_image_name');
        var mobile = document.getElementById('mobile');

        // Show the picked file straight away - without this the only feedback that a photo
        // was chosen is the page reloading after a successful save.
        if (fileInput && preview) {
            fileInput.addEventListener('change', function () {
                var file = this.files && this.files[0];
                if (!file) {
                    return;
                }
                if (hint) {
                    hint.textContent = file.name;
                }
                preview.src = URL.createObjectURL(file);
            });
        }

        // The mobile field previously accepted any number of any characters. maxlength alone
        // does not stop a paste of letters, so strip anything that is not a digit as it is
        // typed; the server re-validates regardless.
        if (mobile && !mobile.hasAttribute('readonly')) {
            mobile.addEventListener('input', function () {
                var digits = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                if (this.value !== digits) {
                    this.value = digits;
                }
            });
        }
    })();
</script>