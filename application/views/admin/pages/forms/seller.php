<?php
/**
 * Admin > Sellers > Add / Update Seller.
 *
 * Rebuilt on the SAME design system as the seller's own profile form
 * (application/views/seller/pages/forms/profile.php +
 * assets/seller/css/cretzo/seller-profile.css): one card, five steps toggled
 * with [hidden], a clickable step rail, a 12-column field grid, drop-zone
 * uploads, and a [data-czp-error] slot inside every field's own grid cell so a
 * validation message can only ever render beside the field it belongs to.
 * Before this, the two screens for the same 60-odd columns looked and behaved
 * nothing alike - a 1,300-line horizontal AdminLTE form here, a guided wizard
 * there - which is how they kept drifting apart.
 *
 * What is deliberately NOT copied from the seller side:
 *
 *   - Navigation is never gated. An admin opening a seller to flip Status to
 *     Approved cannot be blocked by a document that seller never uploaded, so
 *     the rail and Back/Next always move and Save (sticky, reachable from
 *     every step) is what validates everything.
 *   - The admin-only step: Status, subscription/commission, seller
 *     permissions, and the verification request panel.
 *   - The seller's "we are not a registered entity" declaration, which is a
 *     consent the seller gives, not something an admin ticks for them.
 *
 * FIELD NAMES ARE UNCHANGED. admin/Sellers::add_seller() reads them, together
 * with the old_<name> hidden inputs that keep an already-uploaded document
 * when the form is saved again without picking a new file.
 *
 * The .form-submit-event class is gone on purpose: the save is handled by
 * assets/admin/js/cretzo/admin-seller-form.js, which routes a server
 * rejection back to the field it names. Leaving the class on would make
 * custom.js's delegated handler post the whole form a second time.
 */

// Add-Seller mode has no record to prefill from.
$fetched_data = isset($fetched_data) && is_array($fetched_data) ? $fetched_data : [];
$d = (isset($fetched_data[0]) && is_array($fetched_data[0])) ? $fetched_data[0] : [];

$is_edit       = isset($d['id']);
$edit_user_id  = isset($d['user_id']) ? (int) $d['user_id'] : 0;

// Rows written before the entity type became a fixed list hold display strings
// ("Individual", "Partenership Firm" - the typo is in the data). Matched
// literally, none of them equals an <option> value, so the select fell back to
// the first option and a save silently rewrote a Partnership Firm as an
// Individual - which also changes which documents are demanded. Normalise the
// legacy spellings instead.
$entity_type = strtolower(str_replace([' ', '-'], '_', trim((string) ($d['entity_type'] ?? 'individual'))));
$entity_aliases = [
    'partenership_firm' => 'partnership_firm',
    'partnership'       => 'partnership_firm',
    'sole_proprietor'   => 'sole_proprietorship',
    'proprietorship'    => 'sole_proprietorship',
    ''                  => 'individual',
];
$entity_type = $entity_aliases[$entity_type] ?? $entity_type;
if (!in_array($entity_type, ['individual', 'sole_proprietorship', 'partnership_firm'], true)) {
    $entity_type = 'individual';
}
$is_non_gst    = isset($d['is_gst_registered']) && (string) $d['is_gst_registered'] === '0';
$status_value  = isset($d['status']) ? (string) $d['status'] : '2';
$all_categories = isset($all_categories) && is_array($all_categories) ? $all_categories : [];
$indian_banks   = isset($indian_banks) && is_array($indian_banks) ? $indian_banks : [];

$permit = [];
if (!empty($d['permissions'])) {
    $decoded = json_decode($d['permissions'], true);
    if (is_array($decoded)) {
        $permit = $decoded;
    }
}

if (!function_exists('asf_attr')) {
    /**
     * Escape once, for an attribute.
     *
     * The old view ran html_escape(htmlspecialchars($value)) on most fields.
     * Sellers::manage_seller() already passes the row through
     * output_escaping(), which only strips slashes - it does not HTML-encode -
     * so the double call turned a shop name like "Ram & Sons" into
     * "Ram &amp;amp; Sons" in the input box, and saving it again persisted
     * that. One pass is correct.
     */
    function asf_attr($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** One text / textarea / select field with its own error slot. */
    function asf_field(array $o)
    {
        $name     = $o['name'];
        $id       = $o['id'] ?? ('f_' . $name);
        $col      = isset($o['col']) ? (int) $o['col'] : 6;
        $type     = $o['type'] ?? 'text';
        $required = !empty($o['required']);
        $value    = isset($o['value']) ? (string) $o['value'] : '';
        ?>
        <div class="czp-field czp-col-<?= $col ?>" data-czp-field="<?= asf_attr($name) ?>"<?= !empty($o['hidden']) ? ' hidden' : '' ?>>
          <label class="czp-label" for="<?= asf_attr($id) ?>"<?= isset($o['label_id']) ? ' id="' . asf_attr($o['label_id']) . '"' : '' ?>>
            <?= $o['label'] ?><?= $required ? ' <i class="czp-req">*</i>' : '' ?>
          </label>
          <?php if ($type === 'textarea'): ?>
            <textarea class="czp-input" id="<?= asf_attr($id) ?>" name="<?= asf_attr($name) ?>"
                      rows="<?= isset($o['rows']) ? (int) $o['rows'] : 3 ?>"
                      placeholder="<?= asf_attr($o['placeholder'] ?? '') ?>"><?= asf_attr($value) ?></textarea>
          <?php elseif ($type === 'select'): ?>
            <select class="czp-input" id="<?= asf_attr($id) ?>" name="<?= asf_attr($name) ?>">
              <?php foreach ($o['options'] as $opt_value => $opt_label): ?>
                <option value="<?= asf_attr($opt_value) ?>" <?= ((string) $opt_value === (string) $value) ? 'selected' : '' ?>>
                  <?= asf_attr($opt_label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input class="czp-input" type="<?= asf_attr($type) ?>"
                   id="<?= asf_attr($id) ?>" name="<?= asf_attr($name) ?>"
                   value="<?= asf_attr($value) ?>"
                   placeholder="<?= asf_attr($o['placeholder'] ?? '') ?>"
                   <?= isset($o['maxlength']) ? 'maxlength="' . (int) $o['maxlength'] . '"' : '' ?>
                   <?= isset($o['digits']) ? 'data-czp-digits="' . (int) $o['digits'] . '"' : '' ?>
                   <?= isset($o['contact']) ? 'data-czp-contact="' . asf_attr($o['contact']) . '"' : '' ?>
                   <?= isset($o['autocomplete']) ? 'autocomplete="' . asf_attr($o['autocomplete']) . '"' : '' ?>>
          <?php endif; ?>
          <?php if (!empty($o['hint'])): ?>
            <small class="czp-hint"><?= $o['hint'] ?></small>
          <?php endif; ?>
          <?php if (!empty($o['status'])): ?>
            <span class="czp-status" id="<?= asf_attr($o['status']) ?>"></span>
          <?php endif; ?>
          <span class="czp-error" data-czp-error></span>
        </div>
        <?php
    }

    /**
     * One document upload: drop zone, the old_<name> keeper, and a chip for
     * whatever is already on record (images open in the admin panel's
     * ekko-lightbox, PDFs open in a new tab).
     */
    function asf_file(array $o)
    {
        $name     = $o['name'];
        $id       = $name . '_input';
        $col      = isset($o['col']) ? (int) $o['col'] : 6;
        $required = !empty($o['required']);
        $value    = isset($o['value']) ? (string) $o['value'] : '';
        $has      = $value !== '';
        $is_pdf   = $has && preg_match('/\.pdf$/i', $value);
        $url      = $has ? base_url($value) : '';
        $accept   = !empty($o['images_only']) ? '.jpg,.jpeg,.png,.gif' : '.jpg,.jpeg,.png,.gif,.pdf';
        $types    = !empty($o['images_only']) ? 'JPG, PNG or GIF' : 'JPG, PNG, GIF or PDF';
        ?>
        <div class="czp-field czp-col-<?= $col ?>"
             id="<?= asf_attr($name) ?>_field"
             data-czp-field="<?= asf_attr($name) ?>"
             data-czp-file-label="<?= asf_attr($o['label']) ?>"
             <?= $required ? 'data-czp-file-required' : '' ?>
             <?= !empty($o['images_only']) ? 'data-czp-file-images' : '' ?>
             <?= !empty($o['hidden']) ? 'hidden' : '' ?>>
          <label class="czp-label" for="<?= asf_attr($id) ?>">
            <?= $o['label'] ?><?= $required ? ' <i class="czp-req">*</i>' : '' ?>
          </label>
          <small class="czp-hint"><?= $types ?> &middot; up to 8 MB<?= !empty($o['hint']) ? ' &middot; ' . $o['hint'] : '' ?></small>
          <?php if (!empty($o['extra_hint_id'])): ?>
            <small class="czp-hint" id="<?= asf_attr($o['extra_hint_id']) ?>" hidden></small>
          <?php endif; ?>

          <input type="file" class="czp-file" id="<?= asf_attr($id) ?>" name="<?= asf_attr($name) ?>" accept="<?= $accept ?>">
          <label class="czp-drop" for="<?= asf_attr($id) ?>">
            <span class="czp-drop-icon" aria-hidden="true">&#8679;</span>
            <span class="czp-drop-text" data-czp-drop-text>Choose a file <em>or drop it here</em></span>
          </label>

          <?php // Keeps what is already on record when the form is saved again
                // without picking a new file. The x button clears it, so
                // removing a required document without replacing it fails. ?>
          <input type="hidden" name="old_<?= asf_attr($name) ?>" value="<?= asf_attr($value) ?>">

          <div class="czp-doc" data-czp-doc <?= $has ? '' : 'hidden' ?>>
            <a data-czp-doc-link target="_blank" rel="noopener"
               <?= $has ? 'href="' . asf_attr($url) . '"' : '' ?>
               <?= ($has && !$is_pdf) ? 'data-toggle="lightbox" data-gallery="seller_documents" data-title="' . asf_attr(strip_tags($o['label'])) . '"' : '' ?>>
              <img class="czp-doc-thumb" data-czp-doc-thumb alt=""
                   <?= ($has && !$is_pdf) ? 'src="' . asf_attr($url) . '"' : '' ?>
                   <?= ($has && !$is_pdf) ? '' : 'hidden' ?>>
            </a>
            <span class="czp-doc-file" data-czp-doc-file <?= $is_pdf ? '' : 'hidden' ?>>PDF</span>
            <span class="czp-doc-name" data-czp-doc-name><?= $has ? asf_attr(basename($value)) : '' ?></span>
            <button type="button" class="czp-doc-remove" data-czp-doc-remove
                    aria-label="Remove <?= asf_attr(strip_tags($o['label'])) ?>">&times;</button>
          </div>
          <small class="czp-hint" data-czp-doc-hint <?= $has ? '' : 'hidden' ?>>Already on record - leave blank to keep it.</small>
          <span class="czp-error" data-czp-error></span>
        </div>
        <?php
    }
}

// Which step each incomplete profile section belongs to, for the "Fix this"
// jumps in the verification panel. seller_profile_sections() keys the bank
// section 'account'; the step is called 'bank' here.
$section_step = ['personal' => 'personal', 'store' => 'store', 'account' => 'bank'];

$seller_missing = (isset($seller_missing_sections) && is_array($seller_missing_sections)) ? $seller_missing_sections : [];
$verification_requested_at = !empty($d['verification_request_at']) ? $d['verification_request_at'] : '';
$seller_is_approved = ($status_value === '1');

$seller_photo_url = get_user_avatar_url($d['image'] ?? '');
$display_name = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
if ($display_name === '') {
    $display_name = $d['shop_name'] ?? 'Seller';
}

// The seller's current subscription plan is where commission actually comes
// from (Seller_model::settle_seller_commission() reads the plan slabs, never
// seller_data.commission), so the Admin step links to it rather than offering
// a rate box that no calculation would consult.
$plan_row = null;
if ($edit_user_id > 0) {
    $CI = &get_instance();
    $CI->load->model('Seller_subscription_model');
    $plan_row = $CI->Seller_subscription_model->get_current_plan($edit_user_id);
}

// Deep link: ?edit_id=73&section=admin opens straight on Admin Controls, which
// is what the "Review & approve" button in the header links to. Anything else
// starts at the top, so the page stays predictable for an ordinary edit.
$step_keys = ['personal', 'store', 'business', 'bank', 'admin'];
$requested_section = isset($_GET['section']) ? (string) $_GET['section'] : '';
$initial_step = in_array($requested_section, $step_keys, true) ? $requested_section : 'personal';

$rail = [
    ['personal', 'Personal &amp; Account', 'Name, login, ID proof'],
    ['store',    'Store Details',          'Shop, pickup, categories'],
    ['business', 'Business &amp; Legal',   'Entity, PAN &amp; GST'],
    ['bank',     'Bank Account',           'Where payouts land'],
    ['admin',    'Admin Controls',         'Status &amp; permissions'],
];
?>

<?php // The seller form's stylesheet IS the design system for both screens -
      // loaded first, then only the admin-specific pieces on top of it. ?>
<link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/seller-profile.css') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/css/cretzo/seller-profile.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/admin/css/cretzo/admin-seller-form.css') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/css/cretzo/admin-seller-form.css') ?: time() ?>">

<div class="content-wrapper czp-page">
  <section class="content">
    <div class="container-fluid">
      <div class="czp">

        <?php // Inside .czp on purpose: the colour tokens this toast uses are
              // declared there, and a fixed-position element styles the same
              // wherever it sits in the tree. ?>
        <div id="czp_toast" class="czp-toast" role="status" aria-live="polite"></div>

        <?php if ($is_edit): ?>
          <div class="czp-idcard">
            <div class="czp-idcard-avatar">
              <?php if ($seller_photo_url !== ''): ?>
                <img src="<?= asf_attr($seller_photo_url) ?>" alt="<?= asf_attr($display_name) ?>">
              <?php else: ?>
                <?= asf_attr(strtoupper(mb_substr($display_name, 0, 1))) ?>
              <?php endif; ?>
            </div>
            <div class="czp-idcard-main">
              <h1><?= asf_attr($display_name) ?></h1>
              <div class="czp-idcard-meta">
                <span>#<?= $edit_user_id ?></span>
                <?php if (!empty($d['shop_name'])): ?><span><?= asf_attr($d['shop_name']) ?></span><?php endif; ?>
                <?php if (!empty($d['email'])): ?><span><?= asf_attr($d['email']) ?></span><?php endif; ?>
                <?php if (!empty($d['phone'])): ?><span><?= asf_attr($d['phone']) ?></span><?php endif; ?>
              </div>
            </div>
            <div class="czp-idcard-side">
              <?php if ($status_value === '1'): ?>
                <span class="czp-badge czp-badge-green">Approved</span>
              <?php elseif ($status_value === '0'): ?>
                <span class="czp-badge czp-badge-grey">Deactivated</span>
              <?php elseif ($status_value === '7'): ?>
                <span class="czp-badge czp-badge-red">Removed</span>
              <?php else: ?>
                <span class="czp-badge czp-badge-amber">Not approved</span>
              <?php endif; ?>
              <?php if (!empty($plan_row['name'])): ?>
                <span class="czp-badge czp-badge-grey"><?= asf_attr($plan_row['name']) ?></span>
              <?php endif; ?>
              <button type="button" class="czp-btn czp-btn-ghost czp-btn-sm"
                      onclick="openSellerFormSection('admin')">Review &amp; approve</button>
              <?php if ($seller_is_approved && (!empty($d['slug']) || $edit_user_id > 0)): ?>
                <?php // seller_profile_url(), the same helper the storefront's seller cards
                      // use. The public store lives at sellers/seller_details/<slug>;
                      // sellers/<slug> matches no route ($route['sellers/(:num)'] only
                      // covers the numeric pagination) and 404s. It also falls back to
                      // the user id when a seller has no slug yet. ?>
                <a class="czp-btn czp-btn-ghost czp-btn-sm" target="_blank" rel="noopener"
                   href="<?= seller_profile_url($d['slug'] ?? '', $edit_user_id) ?>">View storefront</a>
              <?php endif; ?>
              <a class="czp-btn czp-btn-ghost czp-btn-sm" href="<?= base_url('admin/sellers') ?>">All sellers</a>
            </div>
          </div>
        <?php else: ?>
          <header class="czp-head">
            <h1>Add a Seller</h1>
            <p>The same five sections the seller sees on their own profile. Everything except the login password can be edited later.</p>
          </header>
        <?php endif; ?>

        <nav class="czp-rail" aria-label="Seller form sections">
          <?php foreach ($rail as $i => $r): ?>
            <button type="button" class="czp-rail-item" data-czp-goto="<?= $i ?>">
              <span class="czp-rail-num"><span><?= $i + 1 ?></span></span>
              <span class="czp-rail-txt"><b><?= $r[1] ?></b><small><?= $r[2] ?></small></span>
            </button>
          <?php endforeach; ?>
        </nav>

        <div class="czp-card">
          <form id="seller_admin_form" class="czp-form" method="POST"
                action="<?= base_url('admin/sellers/add_seller') ?>"
                enctype="multipart/form-data" novalidate>

            <?php if ($is_edit): ?>
              <input type="hidden" name="edit_seller" value="<?= (int) $d['user_id'] ?>">
              <input type="hidden" name="edit_seller_data_id" value="<?= (int) $d['id'] ?>">
            <?php endif; ?>

            <!-- ==================== STEP 1: PERSONAL / ACCOUNT ============== -->
            <section class="czp-step" data-czp-step="personal" data-czp-label="Personal &amp; Account" hidden>
              <div class="czp-step-head">
                <h2>Personal &amp; Account Details</h2>
                <p>Who the seller is, how the platform reaches them, and the two identity documents that get verified.</p>
              </div>
              <div class="czp-alert" data-czp-alert hidden></div>

              <div class="czp-avatar-row czp-field" data-czp-field="seller_photo"
                   data-czp-file-label="Seller photo" data-czp-file-images>
                <input type="file" class="czp-file" id="seller_photo_input" name="seller_photo" accept=".jpg,.jpeg,.png,.gif">
                <input type="hidden" name="old_seller_photo" value="<?= asf_attr($d['image'] ?? '') ?>">
                <div class="czp-avatar" data-czp-avatar tabindex="0" role="button" aria-label="Upload seller photo">
                  <svg data-czp-avatar-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" <?= $seller_photo_url !== '' ? 'hidden' : '' ?>>
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                  </svg>
                  <img data-czp-avatar-img alt=""
                       <?= $seller_photo_url !== '' ? 'src="' . asf_attr($seller_photo_url) . '"' : '' ?>
                       <?= $seller_photo_url !== '' ? '' : 'hidden' ?>>
                </div>
                <div class="czp-avatar-copy">
                  <b>Seller photo</b>
                  <small class="czp-hint">Optional. JPG, PNG or GIF, up to 8 MB.</small>
                  <span class="czp-error" data-czp-error></span>
                </div>
                <label class="czp-btn czp-btn-ghost czp-btn-sm" for="seller_photo_input">Choose photo</label>
              </div>

              <fieldset class="czp-group">
                <legend>Name</legend>
                <div class="czp-grid">
                  <?php
                  asf_field(['name' => 'first_name', 'label' => 'First Name', 'value' => $d['first_name'] ?? '', 'required' => true, 'col' => 4, 'id' => 'first_name', 'placeholder' => 'First name', 'maxlength' => 50]);
                  asf_field(['name' => 'middle_name', 'label' => 'Middle Name', 'value' => $d['middle_name'] ?? '', 'col' => 4, 'id' => 'middle_name', 'placeholder' => 'Optional', 'maxlength' => 50]);
                  asf_field(['name' => 'last_name', 'label' => 'Last Name', 'value' => $d['last_name'] ?? '', 'required' => true, 'col' => 4, 'id' => 'last_name', 'placeholder' => 'Last name', 'maxlength' => 50]);
                  ?>
                </div>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Login &amp; contact</legend>
                <div class="czp-grid">
                  <?php
                  asf_field([
                      'name' => 'phone', 'label' => 'Phone Number', 'value' => $d['phone'] ?? '', 'required' => true,
                      'id' => 'phone', 'placeholder' => '10-digit mobile number', 'digits' => 10, 'contact' => 'phone',
                      'hint' => 'The seller signs in with this number, and order/payout alerts go to it.',
                  ]);
                  asf_field([
                      'name' => 'email', 'label' => 'Email', 'value' => $d['email'] ?? '', 'required' => true,
                      'id' => 'email', 'type' => 'email', 'placeholder' => 'name@example.com', 'maxlength' => 254, 'contact' => 'email',
                  ]);
                  if (!$is_edit) {
                      asf_field([
                          'name' => 'password', 'label' => 'Password', 'required' => true, 'type' => 'password',
                          'id' => 'password', 'placeholder' => 'Set a password', 'autocomplete' => 'new-password',
                          'hint' => 'Eight characters or more is recommended. The seller can change it from their own profile.',
                      ]);
                      asf_field([
                          'name' => 'confirm_password', 'label' => 'Confirm Password', 'required' => true, 'type' => 'password',
                          'id' => 'confirm_password', 'placeholder' => 'Re-type the password', 'autocomplete' => 'new-password',
                      ]);
                  }
                  ?>
                </div>
                <?php if ($is_edit): ?>
                  <small class="czp-hint">The password is not shown or changed here. The seller resets it from their own account.</small>
                <?php endif; ?>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Address</legend>
                <div class="czp-grid">
                  <?php
                  asf_field(['name' => 'address1', 'label' => 'Address', 'value' => $d['address1'] ?? '', 'required' => true, 'col' => 12, 'id' => 'address1', 'placeholder' => 'House / street']);
                  asf_field([
                      'name' => 'pin', 'label' => 'PIN Code', 'value' => $d['pin'] ?? '', 'required' => true,
                      'id' => 'pin', 'placeholder' => '6-digit PIN code', 'digits' => 6, 'status' => 'pin_status',
                  ]);
                  asf_field(['name' => 'state', 'label' => 'State', 'value' => $d['state'] ?? '', 'required' => true, 'id' => 'state', 'placeholder' => 'State']);
                  asf_field(['name' => 'district', 'label' => 'District', 'value' => $d['district'] ?? '', 'required' => true, 'id' => 'district', 'placeholder' => 'District']);
                  // id="seller_city", not "city": custom.js binds a stray
                  // $('#city').on('change') left over from the customer address book,
                  // which POSTs to my-account/get-areas and then blows up on an
                  // undefined `Toast` - which it did every time an admin edited this
                  // field on the old form. The name attribute is what the server
                  // reads, so renaming the id changes nothing else.
                  asf_field(['name' => 'city', 'label' => 'City/Village/Town', 'value' => $d['city'] ?? '', 'required' => true, 'id' => 'seller_city', 'placeholder' => 'City, village or town']);
                  ?>
                </div>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Identity documents</legend>
                <div class="czp-grid">
                  <?php
                  asf_file(['name' => 'national_identity_card', 'label' => 'Identity Proof', 'value' => $d['national_identity_card'] ?? '', 'required' => true, 'hint' => 'Aadhaar, passport, voter ID or driving licence']);
                  asf_file(['name' => 'authorized_signature', 'label' => 'Authorized Signatory', 'value' => $d['authorized_signature'] ?? '', 'required' => true, 'hint' => 'A clear scan of the signature']);
                  ?>
                </div>
              </fieldset>
            </section>

            <!-- ==================== STEP 2: STORE ========================== -->
            <section class="czp-step" data-czp-step="store" data-czp-label="Store Details" hidden>
              <div class="czp-step-head">
                <h2>Store Details</h2>
                <p>How buyers see the shop, where the courier collects, and what the seller is allowed to list.</p>
              </div>
              <div class="czp-alert" data-czp-alert hidden></div>

              <?php // store_logo lands in seller_data.logo (Seller_model::add_seller()
                    // remaps the key), which is why old_store_logo is fed from `logo` -
                    // reading it from `store_logo` would post back empty and wipe the
                    // logo on every save that did not re-upload one. ?>
              <div class="czp-avatar-row czp-field" data-czp-field="store_logo"
                   data-czp-file-label="Store logo" data-czp-file-images>
                <input type="file" class="czp-file" id="store_logo_input" name="store_logo" accept=".jpg,.jpeg,.png,.gif">
                <input type="hidden" name="old_store_logo" value="<?= asf_attr($d['logo'] ?? '') ?>">
                <div class="czp-avatar is-square" data-czp-avatar tabindex="0" role="button" aria-label="Upload store logo">
                  <svg data-czp-avatar-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" <?= !empty($d['logo']) ? 'hidden' : '' ?>>
                    <path d="M14.5 3h-13a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5M2 4h12v5.5l-2.6-2.1a.5.5 0 0 0-.63 0L7.5 10 5.6 8.6a.5.5 0 0 0-.6 0L2 10.8zm3.5 1.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                  </svg>
                  <img data-czp-avatar-img alt=""
                       <?= !empty($d['logo']) ? 'src="' . asf_attr(base_url($d['logo'])) . '"' : '' ?>
                       <?= !empty($d['logo']) ? '' : 'hidden' ?>>
                </div>
                <div class="czp-avatar-copy">
                  <b>Store logo</b>
                  <small class="czp-hint">Optional. JPG, PNG or GIF, up to 8 MB.</small>
                  <span class="czp-error" data-czp-error></span>
                </div>
                <label class="czp-btn czp-btn-ghost czp-btn-sm" for="store_logo_input">Choose logo</label>
              </div>

              <fieldset class="czp-group">
                <legend>Shop identity</legend>
                <div class="czp-grid">
                  <?php
                  asf_field(['name' => 'shop_name', 'label' => 'Shop Name', 'value' => $d['shop_name'] ?? '', 'required' => true, 'id' => 'shop_name', 'placeholder' => 'Shop name', 'maxlength' => 100, 'hint' => 'Must be unique across Cretzo.']);
                  asf_field([
                      'name' => 'slug', 'label' => 'Store URL', 'value' => $d['slug'] ?? '', 'id' => 'slug_input', 'maxlength' => 255,
                      'placeholder' => !empty($d['shop_name']) ? $d['shop_name'] : 'your-shop-name',
                      'hint' => 'Leave blank and it is built from the shop name.',
                  ]);
                  asf_field([
                      'name' => 'shop_phone', 'label' => 'Shop Phone Number', 'value' => $d['shop_phone'] ?? '', 'required' => true,
                      'id' => 'shop_phone', 'placeholder' => '10-digit mobile number', 'digits' => 10, 'contact' => 'shop_phone',
                      'hint' => 'May be the same as the personal number above.',
                  ]);
                  asf_field(['name' => 'social', 'label' => 'Social Media Handle', 'value' => $d['social'] ?? '', 'id' => 'social', 'placeholder' => 'Instagram, Facebook or website']);
                  asf_field(['name' => 'store_description', 'label' => 'Store Description', 'value' => $d['store_description'] ?? '', 'col' => 12, 'type' => 'textarea', 'id' => 'store_description', 'placeholder' => 'Tell customers about the store...']);
                  ?>
                </div>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Pickup address</legend>
                <div class="czp-grid">
                  <?php
                  asf_field(['name' => 'pickup_address1', 'label' => 'Pickup Address Line 1', 'value' => $d['pickup_address1'] ?? '', 'required' => true, 'id' => 'pickup_address1', 'placeholder' => 'Address line 1']);
                  asf_field(['name' => 'pickup_address2', 'label' => 'Pickup Address Line 2', 'value' => $d['pickup_address2'] ?? '', 'id' => 'pickup_address2', 'placeholder' => 'Address line 2 (optional)']);
                  asf_field([
                      'name' => 'pickup_pin', 'label' => 'PIN Code', 'value' => $d['pickup_pin'] ?? '', 'required' => true,
                      'id' => 'pickup_pin', 'placeholder' => '6-digit PIN code', 'digits' => 6, 'status' => 'pickup_pin_status',
                  ]);
                  asf_field(['name' => 'pickup_state', 'label' => 'State', 'value' => $d['pickup_state'] ?? '', 'required' => true, 'id' => 'pickup_state', 'placeholder' => 'State']);
                  asf_field(['name' => 'pickup_district', 'label' => 'District', 'value' => $d['pickup_district'] ?? '', 'required' => true, 'id' => 'pickup_district', 'placeholder' => 'District']);
                  asf_field(['name' => 'pickup_city', 'label' => 'City', 'value' => $d['pickup_city'] ?? '', 'required' => true, 'id' => 'pickup_city', 'placeholder' => 'City']);
                  ?>
                </div>
                <small class="czp-hint">Shiprocket collects from this address, so State, District and City all have to be filled in.</small>
              </fieldset>

              <fieldset class="czp-group">
                <legend>What this seller sells</legend>
                <div class="czp-grid">
                  <?php
                  $primary_options = ['' => 'Select a category'];
                  foreach ($all_categories as $cat) {
                      if ((int) $cat['parent_id'] !== 0) continue; // top-level only
                      $primary_options[$cat['id']] = $cat['name'];
                  }
                  asf_field([
                      'name' => 'primary_category_id', 'label' => 'Primary Product Category', 'required' => true,
                      'type' => 'select', 'id' => 'primary_category_id', 'options' => $primary_options,
                      'value' => $d['primary_category_id'] ?? '',
                  ]);
                  ?>
                  <div class="czp-field czp-col-6" data-czp-field="secondary_category_ids">
                    <label class="czp-label">Secondary Categories <small class="czp-hint" style="display:inline">(optional)</small></label>
                    <small class="czp-hint">Only sub-categories of the primary category can be added.</small>
                    <div class="czp-pills" id="category_pills"></div>
                    <input type="hidden" name="secondary_category_ids" id="secondary_category_ids" value="<?= asf_attr($d['category_ids'] ?? '') ?>">
                    <button type="button" class="czp-btn czp-btn-ghost czp-btn-sm" id="category_open">+ Add categories</button>
                    <span class="czp-error" data-czp-error></span>
                  </div>
                </div>
              </fieldset>
            </section>

            <!-- ==================== STEP 3: BUSINESS ======================= -->
            <section class="czp-step" data-czp-step="business" data-czp-label="Business &amp; Legal" hidden>
              <div class="czp-step-head">
                <h2>Business &amp; Legal Details</h2>
                <p>The entity type decides which documents apply - the form shows only the ones this seller needs, and the save enforces exactly the same set.</p>
              </div>
              <div class="czp-alert" data-czp-alert hidden></div>

              <fieldset class="czp-group">
                <legend>Entity</legend>
                <div class="czp-grid">
                  <?php
                  asf_field([
                      'name' => 'entity_type', 'label' => 'Entity Type', 'required' => true, 'col' => 4,
                      'type' => 'select', 'id' => 'entity_type', 'value' => $entity_type,
                      'options' => [
                          'individual' => 'Individual',
                          'sole_proprietorship' => 'Sole Proprietorship',
                          'partnership_firm' => 'Partnership Firm',
                      ],
                  ]);
                  asf_field([
                      'name' => 'legal_business_name', 'label' => 'Legal Business Name', 'col' => 4,
                      'label_id' => 'legal_business_name_label', 'id' => 'legal_business_name_input',
                      'value' => $d['legal_business_name'] ?? '', 'maxlength' => 255, 'placeholder' => 'Legal business name',
                      'hint' => 'For an individual this is their own name - filled in automatically.',
                  ]);
                  asf_field([
                      'name' => 'pan', 'label' => 'PAN Number', 'required' => true, 'col' => 4,
                      'label_id' => 'pan_label', 'id' => 'pan', 'value' => $d['pan'] ?? '',
                      'maxlength' => 10, 'placeholder' => 'ABCDE1234F',
                      'hint' => 'The statutory TDS rate is picked from this, so a malformed PAN costs the seller money.',
                  ]);
                  ?>
                </div>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Business address</legend>
                <div class="czp-grid">
                  <?php
                  asf_field(['name' => 'business_address1', 'label' => 'Address Line 1', 'value' => $d['business_address1'] ?? '', 'required' => true, 'id' => 'business_address1', 'placeholder' => 'Street 1']);
                  asf_field(['name' => 'business_address2', 'label' => 'Address Line 2', 'value' => $d['business_address2'] ?? '', 'id' => 'business_address2', 'placeholder' => 'Street 2 (optional)']);
                  asf_field([
                      'name' => 'business_pin', 'label' => 'PIN Code', 'value' => $d['business_pin'] ?? '', 'required' => true,
                      'id' => 'business_pin', 'placeholder' => '6-digit PIN code', 'digits' => 6, 'status' => 'business_pin_status',
                  ]);
                  asf_field(['name' => 'business_state', 'label' => 'State', 'value' => $d['business_state'] ?? '', 'required' => true, 'id' => 'business_state', 'placeholder' => 'State']);
                  asf_field(['name' => 'business_district', 'label' => 'District', 'value' => $d['business_district'] ?? '', 'required' => true, 'id' => 'business_district', 'placeholder' => 'District']);
                  asf_field(['name' => 'business_city', 'label' => 'City/Village/Town', 'value' => $d['business_city'] ?? '', 'required' => true, 'id' => 'business_city', 'placeholder' => 'City, village or town']);
                  ?>
                </div>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Tax registration</legend>
                <div class="czp-grid">
                  <div class="czp-col-12">
                    <div class="czp-check">
                      <input type="checkbox" id="gst_check" name="gst_check" value="1" <?= $is_non_gst ? 'checked' : '' ?>>
                      <div class="czp-check-body">
                        <label for="gst_check">This seller is not GST registered</label>
                        <small class="czp-hint">Asks for a GST Enrollment ID instead of a GSTIN. Without a valid GSTIN no TCS is ever collected from them.</small>
                      </div>
                    </div>
                  </div>
                  <?php
                  asf_field([
                      'name' => 'gst', 'label' => 'GST Number', 'value' => $d['gst'] ?? '', 'required' => true,
                      'id' => 'gst', 'maxlength' => 15, 'placeholder' => '22ABCDE0000A1Z5', 'hidden' => $is_non_gst,
                  ]);
                  asf_field([
                      'name' => 'gst_enrollment_number', 'label' => 'GST Enrollment ID', 'value' => $d['gst_enrollment_number'] ?? '', 'required' => true,
                      'id' => 'gst_enrollment_number', 'maxlength' => 64, 'placeholder' => 'Enrollment ID', 'hidden' => !$is_non_gst,
                      'hint' => 'Such a seller can sell only within their own state (government regulation).',
                  ]);
                  ?>
                </div>
              </fieldset>

              <?php // All of this step's uploads in one group: a grid row is as tall as
                    // its tallest cell, so pairing a plain text field with an upload
                    // (label + hint + picker + file chip) leaves a tall blank gap under
                    // the text one. PAN Card is always required, so the group can never
                    // be empty. ?>
              <fieldset class="czp-group">
                <legend>Documents</legend>
                <div class="czp-grid">
                  <?php
                  $wants_proof = $is_non_gst && !in_array($entity_type, ['individual', ''], true);
                  asf_file(['name' => 'pan_card_document', 'label' => 'PAN Card', 'value' => $d['pan_card_document'] ?? '', 'required' => true]);
                  asf_file(['name' => 'gstin_document', 'label' => 'GSTIN Document', 'value' => $d['gstin_document'] ?? '', 'required' => true, 'hidden' => $is_non_gst]);
                  asf_file(['name' => 'gst_enrollment_ack_document', 'label' => 'GST Enrollment Acknowledgement Slip', 'value' => $d['gst_enrollment_ack_document'] ?? '', 'required' => true, 'hidden' => !$is_non_gst]);
                  asf_file(['name' => 'partnership_deed_document', 'label' => 'Partnership Deed', 'value' => $d['partnership_deed_document'] ?? '', 'required' => true, 'hidden' => ($entity_type !== 'partnership_firm')]);
                  asf_file(['name' => 'business_proof_document', 'label' => 'Business Proof', 'value' => $d['business_proof_document'] ?? '', 'required' => true, 'hidden' => !$wants_proof, 'extra_hint_id' => 'business_proof_document_hint_extra']);
                  asf_file(['name' => 'business_address_proof_document', 'label' => 'Business Address Proof', 'value' => $d['business_address_proof_document'] ?? '', 'required' => true, 'hidden' => !$wants_proof, 'hint' => 'Electricity bill, rent/lease agreement or bank statement']);
                  ?>
                </div>
              </fieldset>
            </section>

            <!-- ==================== STEP 4: BANK =========================== -->
            <section class="czp-step" data-czp-step="bank" data-czp-label="Bank Account" hidden>
              <div class="czp-step-head">
                <h2>Bank Account</h2>
                <p>Where this seller's payouts land. A wrong digit in the account number sends settlement money to the wrong place.</p>
              </div>
              <div class="czp-alert" data-czp-alert hidden></div>

              <fieldset class="czp-group">
                <legend>Account</legend>
                <div class="czp-grid">
                  <?php
                  asf_field(['name' => 'account_number', 'label' => 'Account Number', 'value' => $d['account_number'] ?? '', 'required' => true, 'id' => 'account_number', 'digits' => 18, 'placeholder' => 'Account number']);
                  asf_field(['name' => 'confirm_account_number', 'label' => 'Confirm Account Number', 'value' => $d['account_number'] ?? '', 'required' => true, 'id' => 'confirm_account_number', 'digits' => 18, 'placeholder' => 'Re-type the account number']);
                  asf_field(['name' => 'account_holder_name', 'label' => "Account Holder's Name", 'value' => $d['account_holder_name'] ?? '', 'required' => true, 'id' => 'account_holder_name', 'placeholder' => 'Name exactly as on the account']);
                  asf_field(['name' => 'ifsc', 'label' => 'IFSC Code', 'value' => $d['ifsc'] ?? '', 'required' => true, 'id' => 'ifsc', 'maxlength' => 11, 'placeholder' => 'SBIN0001234']);
                  asf_field(['name' => 'branch', 'label' => 'Branch Name', 'value' => $d['branch'] ?? '', 'required' => true, 'id' => 'branch', 'placeholder' => 'Branch']);
                  ?>

                  <div class="czp-field czp-col-6" data-czp-field="bank_name">
                    <label class="czp-label" for="bank_search">Bank Name <i class="czp-req">*</i></label>
                    <?php if (!empty($indian_banks)): ?>
                      <div class="czp-combo">
                        <input type="text" class="czp-input" id="bank_search" placeholder="Start typing the bank name..." autocomplete="off">
                        <div class="czp-combo-list" id="bank_dropdown"></div>
                      </div>
                      <?php // The hidden input is what gets submitted and what the
                            // validator reads - the visible box is only the search field. ?>
                      <input type="hidden" name="bank_name" id="bank_name_hidden" data-czp-control value="<?= asf_attr($d['bank_name'] ?? '') ?>">
                      <small class="czp-hint">Not in the list? Type it in - whatever is typed is saved.</small>
                    <?php else: ?>
                      <input type="text" class="czp-input" name="bank_name" id="bank_name_hidden" data-czp-control
                             value="<?= asf_attr($d['bank_name'] ?? '') ?>" placeholder="Bank name">
                    <?php endif; ?>
                    <span class="czp-error" data-czp-error></span>
                  </div>

                  <?php asf_file(['name' => 'bank_account_proof_document', 'label' => 'Bank Account Proof', 'value' => $d['bank_account_proof_document'] ?? '', 'required' => false, 'hint' => 'Passbook, statement or cancelled cheque']); ?>
                </div>
              </fieldset>
            </section>

            <!-- ==================== STEP 5: ADMIN CONTROLS ================= -->
            <section class="czp-step" data-czp-step="admin" data-czp-label="Admin Controls" hidden>
              <div class="czp-step-head">
                <h2>Admin Controls</h2>
                <p>Approval, what this seller is allowed to do, and where their commission rate actually comes from.</p>
              </div>
              <div class="czp-alert" data-czp-alert hidden></div>

              <?php if ($is_edit): ?>
                <?php
                // Read-only on purpose. The admin's own "verification note"
                // textarea that used to live here was write-only - nothing ever
                // showed it to the seller, and its column is a varchar(40) that
                // truncated anything worth writing. What is needed here is
                // whether there is a complete profile waiting to be reviewed.
                $missing_labels = array_unique(array_column($seller_missing, 'label'));
                ?>
                <?php if ($seller_is_approved): ?>
                  <div class="czp-alert czp-alert-success">
                    <b>This seller is approved</b>
                    They can list products and take orders. Setting Status back to Not-Approved or Deactive stops that.
                  </div>
                <?php elseif ($verification_requested_at !== ''): ?>
                  <div class="czp-alert czp-alert-warn">
                    <b>Awaiting your review since <?= asf_attr(date('d M Y, h:i A', strtotime($verification_requested_at))) ?></b>
                    Set Status to Approved below and save to verify this seller.
                  </div>
                <?php else: ?>
                  <div class="czp-alert czp-alert-info">
                    <b>Not submitted for verification yet</b>
                    A seller's profile arrives here automatically once they have filled in every section. You can still approve them by hand.
                  </div>
                <?php endif; ?>

                <fieldset class="czp-group">
                  <legend>Profile completeness</legend>
                  <?php if (empty($seller_missing)): ?>
                    <p class="czp-hint" style="color:var(--czp-green);font-weight:600">
                      All profile sections are complete (personal, store and bank details).
                    </p>
                  <?php else: ?>
                    <ul class="czp-review-list">
                      <?php foreach ($seller_missing as $section): ?>
                        <li class="is-todo">
                          <span class="czp-dot" aria-hidden="true">!</span>
                          <span><?= asf_attr($section['label']) ?> is incomplete</span>
                          <?php $step_key = $section_step[$section['key']] ?? 'personal'; ?>
                          <button type="button" class="czp-btn czp-btn-ghost czp-btn-sm"
                                  onclick="openSellerFormSection('<?= asf_attr($step_key) ?>')">Fill this in</button>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                    <small class="czp-hint">
                      Missing: <?= asf_attr(implode(', ', $missing_labels)) ?>. You can approve regardless, but the
                      seller's own dashboard keeps prompting them until these are filled in.
                    </small>
                  <?php endif; ?>
                </fieldset>
              <?php endif; ?>

              <fieldset class="czp-group">
                <legend>Status</legend>
                <div class="czp-field" data-czp-field="status" data-czp-radio>
                  <div class="czp-seg">
                    <label class="czp-seg-item is-approve">
                      <input type="radio" name="status" value="1" <?= $status_value === '1' ? 'checked' : '' ?>>
                      <span class="czp-seg-copy">
                        <b>Approved</b>
                        <small>Verified. Can list products, subscribe and receive orders.</small>
                      </span>
                    </label>
                    <label class="czp-seg-item">
                      <input type="radio" name="status" value="2" <?= $status_value === '2' ? 'checked' : '' ?>>
                      <span class="czp-seg-copy">
                        <b>Not approved</b>
                        <small>Can sign in and complete their profile, but not sell yet.</small>
                      </span>
                    </label>
                    <label class="czp-seg-item is-block">
                      <input type="radio" name="status" value="0" <?= $status_value === '0' ? 'checked' : '' ?>>
                      <span class="czp-seg-copy">
                        <b>Deactive</b>
                        <small>Blocked from the seller panel. Existing listings stop showing.</small>
                      </span>
                    </label>
                  </div>
                  <span class="czp-error" data-czp-error></span>
                </div>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Commission</legend>
                <?php
                // The global "Commission(%)" input and the per-category commission
                // picker that used to sit here wrote to seller_data.commission and the
                // seller_commission table - neither of which is read by anything that
                // computes money any more. Commission comes from the seller's
                // SUBSCRIPTION PLAN slabs (subscriptions.commission_first50 /
                // commission_51_100 / commission_after100), see
                // Seller_model::settle_seller_commission(). Leaving the old fields here
                // meant an admin could carefully set a rate, see it saved, and have
                // every settlement quietly ignore it.
                ?>
                <?php if (!empty($plan_row)): ?>
                  <div class="czp-alert czp-alert-info">
                    <b>Set by this seller's subscription plan: <?= asf_attr($plan_row['name']) ?></b>
                    <div class="czp-kv">
                      <div class="czp-kv-row">
                        <span class="czp-badge czp-badge-grey">First 50 orders: <?= asf_attr($plan_row['commission_first50']) ?>%</span>
                        <span class="czp-badge czp-badge-grey">Orders 51&ndash;100: <?= asf_attr($plan_row['commission_51_100']) ?>%</span>
                        <span class="czp-badge czp-badge-grey">After 100: <?= asf_attr($plan_row['commission_after100']) ?>%</span>
                      </div>
                      <div class="czp-kv-row">
                        Change the rate on the <a href="<?= base_url('admin/subscription') ?>">subscription plan</a>,
                        or see this seller's <a href="<?= base_url('admin/settlement') ?>">settlement records</a>.
                      </div>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="czp-alert czp-alert-warn">
                    <b>No subscription plan</b>
                    There is no commission slab to settle against, so delivered orders stay uncredited until
                    this seller is on a plan &mdash; see <a href="<?= base_url('admin/settlement') ?>">Commission &amp; Settlements</a>.
                  </div>
                <?php endif; ?>
              </fieldset>

              <fieldset class="czp-group">
                <legend>Seller permissions</legend>
                <div class="czp-switches">
                  <label class="czp-switch">
                    <span class="czp-switch-copy">
                      <b>Products need approval</b>
                      <small>New and edited listings wait for an admin before going live.</small>
                    </span>
                    <input type="checkbox" name="require_products_approval" value="1"
                           <?= (isset($permit['require_products_approval']) && $permit['require_products_approval'] == '1') ? 'checked' : '' ?>>
                  </label>
                  <label class="czp-switch">
                    <span class="czp-switch-copy">
                      <b>Can see customer details</b>
                      <small>Buyer name, phone and address on their own orders.</small>
                    </span>
                    <input type="checkbox" name="customer_privacy" value="1"
                           <?= (isset($permit['customer_privacy']) && $permit['customer_privacy'] == '1') ? 'checked' : '' ?>>
                  </label>
                  <label class="czp-switch">
                    <span class="czp-switch-copy">
                      <b>Can see order OTP &amp; set delivery status</b>
                      <small>Lets the seller move their own orders through to delivered.</small>
                    </span>
                    <input type="checkbox" name="view_order_otp" value="1"
                           <?= (isset($permit['view_order_otp']) && $permit['view_order_otp'] == '1') ? 'checked' : '' ?>>
                  </label>
                  <label class="czp-switch">
                    <span class="czp-switch-copy">
                      <b>Can assign a delivery boy</b>
                      <small>Legacy: Cretzo ships through Shiprocket, so there are no delivery boy accounts to assign.</small>
                    </span>
                    <input type="checkbox" name="assign_delivery_boy" value="1"
                           <?= (isset($permit['assign_delivery_boy']) && $permit['assign_delivery_boy'] == '1') ? 'checked' : '' ?>>
                  </label>
                </div>
              </fieldset>
            </section>

            <!-- ==================== ACTION BAR ============================= -->
            <?php // Inside the form so the button is a real submit, and sticky so an
                  // admin who only came to change Status never has to hunt for it. ?>
            <footer class="czp-actionbar">
              <button type="button" class="czp-btn czp-btn-ghost czp-btn-sm" data-czp-back>Back</button>
              <button type="button" class="czp-btn czp-btn-ghost czp-btn-sm" data-czp-next>Next</button>
              <span class="czp-actionbar-count" data-czp-step-count></span>
              <span class="czp-actionbar-spacer"></span>
              <?php if ($is_edit): ?>
                <button type="button" class="czp-btn czp-btn-ghost" data-czp-discard>Discard changes</button>
              <?php endif; ?>
              <button type="submit" class="czp-btn czp-btn-primary" id="submit_btn">
                <?= $is_edit ? 'Update Seller' : 'Add Seller' ?>
              </button>
            </footer>

            <div id="error_box"></div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php // The picker lives outside the form: it is a chooser, not submitted data.
      // Its checkboxes carry no name - the chosen ids are synced into the
      // secondary_category_ids hidden input inside the form. ?>
<div class="czp-modal czp" id="category_modal" role="dialog" aria-modal="true" aria-label="Select secondary categories">
  <div class="czp-modal-box">
    <div class="czp-modal-head">
      <strong>Select secondary categories</strong>
      <button type="button" class="czp-modal-close" data-czp-modal-close aria-label="Close">&times;</button>
    </div>
    <div class="czp-modal-body">
      <?php foreach ($all_categories as $cat): ?>
        <?php if ((int) $cat['parent_id'] === 0) continue; // sub-categories only ?>
        <label class="czp-pick" data-parent="<?= (int) $cat['parent_id'] ?>" data-label="<?= asf_attr($cat['name']) ?>" hidden>
          <input type="checkbox" value="<?= (int) $cat['id'] ?>">
          <span><?= asf_attr($cat['name']) ?></span>
        </label>
      <?php endforeach; ?>
      <p class="czp-hint" id="category_empty">Choose a Primary Product Category first.</p>
    </div>
    <div class="czp-modal-foot">
      <button type="button" class="czp-btn czp-btn-ghost" data-czp-modal-close>Cancel</button>
      <button type="button" class="czp-btn czp-btn-primary" id="category_done">Done</button>
    </div>
  </div>
</div>

<script>
window.ASF_CONFIG = {
  checkContactUrl: <?= json_encode(base_url('admin/sellers/check_contact')) ?>,
  listUrl: <?= json_encode(base_url('admin/sellers')) ?>,
  isEdit: <?= $is_edit ? 'true' : 'false' ?>,
  editSellerId: <?= $edit_user_id ?>,
  initialStep: <?= json_encode($initial_step) ?>,
  banks: <?= json_encode(array_column($indian_banks, 'bank_name')) ?>
};
</script>
<script src="<?= base_url('assets/admin/js/cretzo/admin-seller-form.js') ?>?v=<?= @filemtime(FCPATH . 'assets/admin/js/cretzo/admin-seller-form.js') ?: time() ?>"></script>
