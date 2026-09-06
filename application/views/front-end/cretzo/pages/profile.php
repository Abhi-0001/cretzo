<?php
/**
 * My Account > Profile.
 *
 * Restructured around the shared account shell (partials/account-layout.php):
 * the page now READS as a profile - a summary of what is on file - and editing
 * happens in popups, so nothing is presented as an input until the user has
 * asked to change it.
 *
 * `login_with_email` is not a key in the `system_settings` row on this install
 * but is read to decide whether the mobile and email inputs are readonly, so it
 * is defaulted here rather than emitting an "Undefined array key" warning on
 * every visit. 0 preserves today's behaviour exactly: config/ion_auth.php sets
 * $config['identity'] = 'mobile', so the mobile number IS the login identity
 * and must stay readonly, while the email address remains editable.
 */
$login_with_email = isset($system_settings['login_with_email']) ? $system_settings['login_with_email'] : 0;

/* `users`.`image` holds a bare file name inside USER_IMG_PATH (the mobile app's
 * update_user_profile writes it the same way), so a row can legitimately name a
 * file that is no longer on disk - get_user_avatar_url() returns '' in that
 * case and we fall back to the theme icon rather than rendering a broken image. */
$profile_image = get_user_avatar_url(isset($users->image) ? $users->image : '');
$profile_image_is_placeholder = ($profile_image === '');
if ($profile_image_is_placeholder) {
    // NOT the NO_USER_IMAGE constant - that points at assets/no-user-img.png,
    // which does not exist in this install.
    // add_ver() because .htaccess marks every asset immutable for a year: without
    // the ?v=filemtime, a browser that cached one version of this file never
    // fetches another (see the same note in partials/account-layout.php).
    $profile_image = add_ver(base_url('assets/front_end/cretzo/img/new_cretzo/user-avatar-placeholder.svg'));
}

$gender_options = ['' => 'Prefer not to say', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
$selected_gender = isset($users->gender) ? strtolower((string) $users->gender) : '';
if (!isset($gender_options[$selected_gender])) {
    $selected_gender = '';
}
$dob_value = isset($users->dob) ? (string) $users->dob : '';

$account_type = isset($users->type) ? $users->type : '';
$is_social = in_array($account_type, ['google', 'facebook'], true);

/* Mobile is the login identity unless the install is configured otherwise. */
$mobile_readonly = ($account_type === 'phone' || $account_type === '')
    && ($login_with_email == 0 || $login_with_email === '0');
$email_readonly = ($is_social && !empty($users->email)) || ($login_with_email == 1 || $login_with_email === '1');

/* A social account has no local password to change, so the whole security card
 * offers the reset path instead of a change-password form it cannot honour. */
$can_change_password = ($account_type === 'phone' || $account_type === '');

$sign_in_label = $is_social
    ? ucfirst($account_type) . ' account'
    : (($account_type === 'phone' || $account_type === '') ? 'Mobile number & password' : ucfirst($account_type));

/* Small helper so an unanswered optional field reads as "Not added" rather than
 * as an empty gap the user cannot tell apart from a rendering fault. */
function czap_value_or_blank($value, $blank = 'Not added')
{
    $value = trim((string) $value);
    return ($value === '')
        ? '<span class="czap-muted">' . $blank . '</span>'
        : html_escape($value);
}

/* ---------------------------------------------------------------- content --
 * Two cards, so `page_card => false` and this view lays them out itself. */
ob_start(); ?>

<section class="czap-card">
    <div class="czap-card__head">
        <div class="czap-card__titles">
            <h2 class="czap-card__title"><i class="uil uil-user"></i> Profile</h2>
            <p class="czap-card__sub">The details we use for your orders and updates</p>
        </div>
        <div class="czap-card__actions">
            <button type="button" class="czap-btn czap-btn--primary" data-czap-open="#czap-profile-modal">
                <i class="uil uil-edit"></i> Edit details
            </button>
        </div>
    </div>
    <div class="czap-card__body">

<div class="czap-photo">
    <img src="<?= $profile_image ?>" alt="Profile photo"
         class="czap-photo__preview<?= $profile_image_is_placeholder ? ' is-placeholder' : '' ?>">
    <div class="czap-photo__actions">
        <p class="czap-panel__title" style="margin-bottom:6px"><i class="uil uil-camera"></i> Profile photo</p>
        <p class="czap-help" style="margin:0 0 10px">
            <?= $profile_image_is_placeholder
                ? 'You have not added a photo yet. JPG, PNG or GIF.'
                : 'JPG, PNG or GIF. Replace it any time.' ?>
        </p>
        <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm" data-czap-open="#czap-profile-modal">
            <i class="uil uil-image-upload"></i> <?= $profile_image_is_placeholder ? 'Add a photo' : 'Change photo' ?>
        </button>
    </div>
</div>

<hr class="czap-hr">

<p class="czap-sec">Personal details</p>
<div class="czap-grid">
    <div class="czap-panel czap-panel--soft">
        <p class="czap-panel__title"><i class="uil uil-user"></i> Username</p>
        <p style="margin:0;font-size:15.5px;font-weight:600"><?= html_escape($users->username) ?></p>
    </div>
    <div class="czap-panel czap-panel--soft">
        <p class="czap-panel__title"><i class="uil uil-phone"></i> Mobile</p>
        <p style="margin:0;font-size:15.5px;font-weight:600">
            <?= !empty($users->mobile) ? '+91 ' . html_escape($users->mobile) : '<span class="czap-muted">Not added</span>' ?>
        </p>
        <?php if ($mobile_readonly && !empty($users->mobile)) { ?>
            <p class="czap-help">This is your login number, so it cannot be changed here.</p>
        <?php } ?>
    </div>
    <div class="czap-panel czap-panel--soft czap-span-2">
        <p class="czap-panel__title"><i class="uil uil-envelope"></i> Email</p>
        <p style="margin:0;font-size:15.5px;font-weight:600;overflow-wrap:anywhere">
            <?= czap_value_or_blank(isset($users->email) ? $users->email : '') ?>
        </p>
        <?php if ($email_readonly && !empty($users->email)) { ?>
            <p class="czap-help">Verified through your <?= html_escape($sign_in_label) ?>, so it cannot be edited.</p>
        <?php } ?>
    </div>
    <div class="czap-panel czap-panel--soft">
        <p class="czap-panel__title"><i class="uil uil-venus"></i> Gender</p>
        <p style="margin:0;font-size:15.5px;font-weight:600">
            <?= $selected_gender !== ''
                ? html_escape($gender_options[$selected_gender])
                : '<span class="czap-muted">Prefer not to say</span>' ?>
        </p>
    </div>
    <div class="czap-panel czap-panel--soft">
        <p class="czap-panel__title"><i class="uil uil-calendar-alt"></i> Date of birth</p>
        <p style="margin:0;font-size:15.5px;font-weight:600">
            <?= $dob_value !== '' ? html_escape(date('d M Y', strtotime($dob_value))) : '<span class="czap-muted">Not added</span>' ?>
        </p>
    </div>
</div>

    </div>
</section>

<!-- ======================== sign-in & security card ======================== -->
<section class="czap-card">
    <div class="czap-card__head">
        <div class="czap-card__titles">
            <h2 class="czap-card__title"><i class="uil uil-shield-check"></i> Sign-in &amp; security</h2>
            <p class="czap-card__sub">How you get into your Cretzo account</p>
        </div>
        <?php if ($can_change_password) { ?>
            <div class="czap-card__actions">
                <button type="button" class="czap-btn czap-btn--ghost" data-czap-open="#czap-password-modal">
                    <i class="uil uil-lock-alt"></i> Change password
                </button>
            </div>
        <?php } ?>
    </div>
    <div class="czap-card__body">
        <div class="czap-dl">
            <div class="czap-dl__row">
                <span>Sign-in method</span>
                <span><?= html_escape($sign_in_label) ?></span>
            </div>
            <?php if (!empty($users->last_login)) { ?>
                <div class="czap-dl__row">
                    <span>Last signed in</span>
                    <span><?= html_escape(date('d M Y, g:i a', is_numeric($users->last_login) ? (int) $users->last_login : strtotime($users->last_login))) ?></span>
                </div>
            <?php } ?>
        </div>

        <?php if (!$can_change_password) { ?>
            <div class="czap-alert czap-alert--info" style="margin:18px 0 0">
                <i class="uil uil-info-circle"></i>
                <span>You sign in with <?= html_escape($sign_in_label) ?>, so there is no Cretzo password to change. Manage it with your provider.</span>
            </div>
        <?php } ?>
    </div>
</section>

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Profile',
    'page_content' => $page_content,
    'page_card'    => false,
]);
?>

<!-- =========================== POPUP: edit details =========================== -->
<div class="czap-modal czap-modal--lg" id="czap-profile-modal" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="czap-profile-modal-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">

        <?php /* The theme's delegated .form-submit-event handler in custom.js owns this
                 submit: it appends the CSRF pair, swaps the .submit_btn label, renders the
                 JSON result into #error_box and reloads on success. Keeping those three
                 hooks (.form-submit-event, .submit_btn, #error_box) is what lets the popup
                 use the existing, already-working endpoint untouched. */ ?>
        <form class="form-submit-event" method="POST" action="<?= base_url('login/update_user') ?>"
              enctype="multipart/form-data" id="czap-profile-form">

            <div class="czap-modal__head">
                <div>
                    <h2 class="czap-modal__title" id="czap-profile-modal-title">
                        <i class="uil uil-edit"></i> Edit details
                    </h2>
                    <p class="czap-modal__sub">Changes apply to new orders and account emails.</p>
                </div>
                <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
            </div>

            <div class="czap-modal__body">

                <div class="czap-photo" style="margin-bottom:22px">
                    <img src="<?= $profile_image ?>" alt="Profile photo" id="czap-photo-preview"
                         class="czap-photo__preview<?= $profile_image_is_placeholder ? ' is-placeholder' : '' ?>">
                    <div class="czap-photo__actions">
                        <label for="czap-image" class="czap-btn czap-btn--ghost czap-btn--sm" style="margin:0">
                            <i class="uil uil-image-upload"></i> Choose photo
                        </label>
                        <input type="file" id="czap-image" name="image" hidden
                               accept="image/png,image/jpeg,image/gif"
                               data-czap-file="#czap-photo-preview" data-czap-file-name="#czap-photo-name">
                        <p class="czap-help" id="czap-photo-name">JPG, PNG or GIF. Optional.</p>
                    </div>
                </div>

                <div class="czap-grid">
                    <div class="czap-field">
                        <label class="czap-field__label" for="username">
                            <?= !empty($this->lang->line('username')) ? $this->lang->line('username') : 'Username' ?><span class="czap-req">*</span>
                        </label>
                        <input type="text" class="czap-input" id="username" name="username"
                               value="<?= html_escape($users->username) ?>" placeholder="Your name"
                               required data-czap-autofocus>
                    </div>

                    <div class="czap-field">
                        <label class="czap-field__label" for="mobile">
                            <?= !empty($this->lang->line('mobile')) ? $this->lang->line('mobile') : 'Mobile' ?><span class="czap-req">*</span>
                        </label>
                        <?php /* 10 digits, digits only - matches seller/customer signup and the
                                 Firebase OTP step, and Login::update_user enforces the same regex.
                                 type="tel" (not the non-existent type="phone" this used to carry)
                                 brings up the numeric keypad on mobile. */ ?>
                        <input type="tel" class="czap-input" id="mobile" name="mobile"
                               value="<?= html_escape($users->mobile) ?>" placeholder="10 digit mobile number"
                               maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric"
                               title="Enter a 10 digit mobile number" data-czap-digits
                               <?= $mobile_readonly ? 'readonly' : '' ?>>
                        <?php if ($mobile_readonly) { ?>
                            <p class="czap-help">This is your login number and cannot be changed here.</p>
                        <?php } ?>
                    </div>

                    <div class="czap-field czap-span-2">
                        <label class="czap-field__label" for="email">
                            <?= !empty($this->lang->line('email')) ? $this->lang->line('email') : 'Email' ?><span class="czap-req">*</span>
                        </label>
                        <input type="email" class="czap-input" id="email" name="email"
                               value="<?= html_escape($users->email) ?>" placeholder="you@example.com"
                               <?= $email_readonly ? 'readonly' : '' ?>>
                        <?php if ($email_readonly) { ?>
                            <p class="czap-help">Verified through your <?= html_escape($sign_in_label) ?>.</p>
                        <?php } else { ?>
                            <p class="czap-help">Order confirmations and support replies go here.</p>
                        <?php } ?>
                    </div>

                    <?php /* Gender and DOB are OPTIONAL - no asterisk, and an unanswered value
                             stays unanswered rather than being defaulted to anything. */ ?>
                    <div class="czap-field">
                        <label class="czap-field__label" for="gender">Gender</label>
                        <select class="czap-select" id="gender" name="gender">
                            <?php foreach ($gender_options as $value => $label) { ?>
                                <option value="<?= $value ?>" <?= $selected_gender === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="czap-field">
                        <label class="czap-field__label" for="dob">Date of birth</label>
                        <?php // max=today: a date of birth in the future is never valid, and
                              // blocking it in the picker saves a round trip. The server
                              // re-checks it either way (callback_valid_dob). ?>
                        <input type="date" class="czap-input" id="dob" name="dob"
                               value="<?= html_escape($dob_value) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <?php /* custom.js's handler adds Bootstrap's own `alert alert-danger` /
                         `alert-success` classes to this box and calls .show()/.fadeOut() on
                         it, so it is left unstyled here - a .czap-alert class as well would
                         put two competing paddings and backgrounds on the same element. */ ?>
                <div id="error_box" style="display:none;margin:18px 0 0"></div>
            </div>

            <div class="czap-modal__foot">
                <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Cancel</button>
                <button type="submit" class="czap-btn czap-btn--primary submit_btn">
                    <i class="uil uil-check"></i> Save details
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($can_change_password) { ?>
<!-- ========================= POPUP: change password ========================= -->
<div class="czap-modal" id="czap-password-modal" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="czap-password-modal-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">

        <?php /* NOT .form-submit-event: that handler is bound to a single #error_box and
                 calls $(".form-submit-event")[0].reset(), which would reach the details
                 form above instead of this one. profile.js submits this form itself.

                 Login::update_user() validates username/email/mobile as `required` even
                 when only the password is changing, so the current values ride along as
                 hidden fields - a password-only POST would otherwise fail validation. */ ?>
        <form method="POST" action="<?= base_url('login/update_user') ?>" id="czap-password-form">
            <input type="hidden" name="username" value="<?= html_escape($users->username) ?>">
            <input type="hidden" name="email" value="<?= html_escape($users->email) ?>">
            <input type="hidden" name="mobile" value="<?= html_escape($users->mobile) ?>">
            <input type="hidden" name="gender" value="<?= html_escape($selected_gender) ?>">
            <input type="hidden" name="dob" value="<?= html_escape($dob_value) ?>">

            <div class="czap-modal__head">
                <div>
                    <h2 class="czap-modal__title" id="czap-password-modal-title">
                        <i class="uil uil-lock-alt"></i> Change password
                    </h2>
                    <p class="czap-modal__sub">You stay signed in on this device.</p>
                </div>
                <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
            </div>

            <div class="czap-modal__body">
                <div class="czap-grid czap-grid--1">
                    <div class="czap-field">
                        <label class="czap-field__label" for="old">
                            <?= !empty($this->lang->line('old_password')) ? $this->lang->line('old_password') : 'Current password' ?><span class="czap-req">*</span>
                        </label>
                        <input type="password" class="czap-input" id="old" name="old"
                               autocomplete="current-password" placeholder="Your current password" data-czap-autofocus>
                    </div>
                    <div class="czap-field">
                        <label class="czap-field__label" for="new">
                            <?= !empty($this->lang->line('new_password')) ? $this->lang->line('new_password') : 'New password' ?><span class="czap-req">*</span>
                        </label>
                        <input type="password" class="czap-input" id="new" name="new"
                               autocomplete="new-password" placeholder="At least <?= (int) $this->config->item('min_password_length', 'ion_auth') ?> characters">
                    </div>
                    <div class="czap-field">
                        <label class="czap-field__label" for="new_confirm">
                            <?= !empty($this->lang->line('confirm_new_password')) ? $this->lang->line('confirm_new_password') : 'Confirm new password' ?><span class="czap-req">*</span>
                        </label>
                        <input type="password" class="czap-input" id="new_confirm" name="new_confirm"
                               autocomplete="new-password" placeholder="Repeat the new password">
                    </div>
                </div>

                <div id="czap-password-msg" class="czap-alert" style="display:none;margin:18px 0 0"></div>
            </div>

            <div class="czap-modal__foot">
                <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Cancel</button>
                <button type="submit" class="czap-btn czap-btn--primary" id="czap-password-submit">
                    <i class="uil uil-check"></i> Update password
                </button>
            </div>
        </form>
    </div>
</div>
<?php } ?>
