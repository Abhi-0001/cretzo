<?php
/**
 * Seller Profile - multi-step form.
 *
 * This view is loaded INSIDE seller/template.php, which already opened
 * <!DOCTYPE html>/<html>/<head>/<body>. The previous version of this file
 * opened a second full document of its own; the browser discarded the stray
 * tags, but the markup was invalid and the stylesheet ended up in <body>.
 * Only the page fragment is emitted here.
 *
 * Structure: five steps, each a plain block toggled with [hidden] by
 * assets/seller/js/cretzo/seller-profile.js. Every field sits in its own
 * [data-czp-field] wrapper carrying an error slot, so a validation message can
 * only ever render in that field's own grid cell - on the step the field lives
 * on, at the moment the seller presses Continue. Nothing waits for Submit.
 *
 * Field NAMES are unchanged: seller/Login::update_user() reads them, along with
 * the old_<name> hidden inputs that keep an already-uploaded document when the
 * seller resubmits without picking a new file.
 */

$d = isset($fetched_data[0]) && is_array($fetched_data[0]) ? $fetched_data[0] : [];

if (!function_exists('czp_val')) {
    /** Escaped value of one profile column. */
    function czp_val($row, $key, $default = '')
    {
        $value = isset($row[$key]) && $row[$key] !== null ? (string) $row[$key] : (string) $default;
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    function czp_attr($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * One text-ish field: label, control, optional hint, optional async status
     * line, and the error slot the JS writes into.
     *
     * @param array $o name,label,value,required,col,type,placeholder,hint,
     *                 maxlength,digits,id,status,extra
     */
    function czp_text_field(array $o)
    {
        $name     = $o['name'];
        $id       = isset($o['id']) ? $o['id'] : ('f_' . $name);
        $col      = isset($o['col']) ? (int) $o['col'] : 6;
        $type     = isset($o['type']) ? $o['type'] : 'text';
        $required = !empty($o['required']);
        ?>
        <div class="czp-field czp-col-<?= $col ?>" data-czp-field="<?= czp_attr($name) ?>">
          <label class="czp-label" for="<?= czp_attr($id) ?>"<?= isset($o['label_id']) ? ' id="' . czp_attr($o['label_id']) . '"' : '' ?>>
            <?= $o['label'] ?><?= $required ? ' <i class="czp-req">*</i>' : '' ?>
          </label>
          <?php if ($type === 'textarea'): ?>
            <textarea class="czp-input"
                      id="<?= czp_attr($id) ?>"
                      name="<?= czp_attr($name) ?>"
                      rows="<?= isset($o['rows']) ? (int) $o['rows'] : 3 ?>"
                      placeholder="<?= czp_attr(isset($o['placeholder']) ? $o['placeholder'] : '') ?>"><?= czp_attr(isset($o['value']) ? $o['value'] : '') ?></textarea>
          <?php else: ?>
            <input class="czp-input"
                   type="<?= czp_attr($type) ?>"
                   id="<?= czp_attr($id) ?>"
                   name="<?= czp_attr($name) ?>"
                   value="<?= czp_attr(isset($o['value']) ? $o['value'] : '') ?>"
                   placeholder="<?= czp_attr(isset($o['placeholder']) ? $o['placeholder'] : '') ?>"
                   <?= isset($o['maxlength']) ? 'maxlength="' . (int) $o['maxlength'] . '"' : '' ?>
                   <?= isset($o['digits']) ? 'data-czp-digits="' . (int) $o['digits'] . '"' : '' ?>
                   <?= isset($o['contact']) ? 'data-czp-contact="' . czp_attr($o['contact']) . '"' : '' ?>
                   <?= isset($o['extra']) ? $o['extra'] : '' ?>>
          <?php endif; ?>
          <?php if (!empty($o['hint'])): ?>
            <small class="czp-hint"><?= $o['hint'] ?></small>
          <?php endif; ?>
          <?php if (!empty($o['status'])): ?>
            <span class="czp-status" id="<?= czp_attr($o['status']) ?>"></span>
          <?php endif; ?>
          <span class="czp-error" data-czp-error></span>
        </div>
        <?php
    }

    /**
     * One upload field. Kept as a single function because there are eleven of
     * them and they must behave identically - the old view had three separate
     * copies of this markup that had already drifted apart.
     *
     * @param array $o name,label,value,required,col,hint,extra_hint,images_only
     */
    function czp_file_field(array $o)
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
             id="<?= czp_attr($name) ?>_field"
             data-czp-field="<?= czp_attr($name) ?>"
             data-czp-file-label="<?= czp_attr($o['label']) ?>"
             <?= $required ? 'data-czp-file-required' : '' ?>
             <?= !empty($o['images_only']) ? 'data-czp-file-images' : '' ?>>
          <label class="czp-label" for="<?= czp_attr($id) ?>">
            <?= $o['label'] ?><?= $required ? ' <i class="czp-req">*</i>' : '' ?>
          </label>
          <small class="czp-hint"><?= $types ?> &middot; up to 8 MB<?= !empty($o['hint']) ? ' &middot; ' . $o['hint'] : '' ?></small>
          <?php if (!empty($o['extra_hint_id'])): ?>
            <small class="czp-hint" id="<?= czp_attr($o['extra_hint_id']) ?>" hidden></small>
          <?php endif; ?>

          <input type="file" class="czp-file" id="<?= czp_attr($id) ?>" name="<?= czp_attr($name) ?>" accept="<?= $accept ?>">
          <label class="czp-drop" for="<?= czp_attr($id) ?>">
            <span class="czp-drop-icon" aria-hidden="true">&#8679;</span>
            <span class="czp-drop-text" data-czp-drop-text>Choose a file <em>or drop it here</em></span>
          </label>

          <?php // Keeps whatever is already on record when the seller resubmits
                // without picking a new file. The x button clears it, so removing
                // a required document without replacing it fails validation. ?>
          <input type="hidden" name="old_<?= czp_attr($name) ?>" value="<?= czp_attr($value) ?>">

          <div class="czp-doc" data-czp-doc <?= $has ? '' : 'hidden' ?>>
            <a data-czp-doc-link target="_blank" rel="noopener" <?= ($has && !$is_pdf) ? 'href="' . czp_attr($url) . '"' : '' ?>>
              <img class="czp-doc-thumb" data-czp-doc-thumb alt=""
                   <?= ($has && !$is_pdf) ? 'src="' . czp_attr($url) . '"' : '' ?>
                   <?= ($has && !$is_pdf) ? '' : 'hidden' ?>>
            </a>
            <span class="czp-doc-file" data-czp-doc-file <?= $is_pdf ? '' : 'hidden' ?>>PDF</span>
            <span class="czp-doc-name" data-czp-doc-name><?= $has ? czp_attr(basename($value)) : '' ?></span>
            <button type="button" class="czp-doc-remove" data-czp-doc-remove
                    aria-label="Remove <?= czp_attr(strip_tags($o['label'])) ?>">&times;</button>
          </div>
          <small class="czp-hint" data-czp-doc-hint <?= $has ? '' : 'hidden' ?>>Already on record - leave blank to keep it.</small>
          <span class="czp-error" data-czp-error></span>
        </div>
        <?php
    }
}

$entity_type   = $d['entity_type'] ?? 'individual';
$is_gst_reg    = isset($d['is_gst_registered']) ? (string) $d['is_gst_registered'] : '1';
$is_non_gst    = ($is_gst_reg === '0');
$is_verified   = isset($d['status']) && (string) $d['status'] === '1';
$requested_at  = !empty($d['verification_request_at']) ? $d['verification_request_at'] : '';
$missing       = (isset($profile_missing_sections) && is_array($profile_missing_sections)) ? $profile_missing_sections : [];
$missing_keys  = array_column($missing, 'key');

// Deep link (?section=...) -> which step opens first. 'admin' is the review step.
$section_keys  = ['personal', 'store', 'business', 'account', 'admin'];
$initial       = in_array(($current_profile_section ?? 'personal'), $section_keys, true)
    ? $current_profile_section
    : 'personal';
?>

<link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/seller-profile.css') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/css/cretzo/seller-profile.css') ?: time() ?>">

<div class="content-wrapper">
  <div class="czp">

    <?php // Inside .czp on purpose: the colour tokens this toast uses are declared
          // there, and a fixed-position element styles the same wherever it sits. ?>
    <div id="czp_toast" class="czp-toast" role="status" aria-live="polite"></div>

    <header class="czp-head">
      <h1>Your Seller Profile</h1>
      <p>Five short steps. Each one is checked before you move on, so nothing comes back as a surprise at the end.</p>
    </header>

    <nav class="czp-rail" aria-label="Profile steps">
      <?php
      $rail = [
          ['Personal Details', 'Who you are'],
          ['Store Details', 'Your shop &amp; pickup'],
          ['Business Details', 'Entity, PAN &amp; GST'],
          ['Bank Account', 'Where payouts land'],
          ['Review &amp; Submit', 'Send for approval'],
      ];
      foreach ($rail as $i => $r): ?>
        <button type="button" class="czp-rail-item" data-czp-goto="<?= $i ?>">
          <span class="czp-rail-num"><span><?= $i + 1 ?></span></span>
          <span class="czp-rail-txt"><b><?= $r[0] ?></b><small><?= $r[1] ?></small></span>
        </button>
      <?php endforeach; ?>
    </nav>

    <div class="czp-card">
      <form id="seller_form" class="czp-form" enctype="multipart/form-data" novalidate>

        <!-- ============================ STEP 1: PERSONAL ==================== -->
        <section class="czp-step" data-czp-step="personal" data-czp-label="Personal Details" hidden>
          <div class="czp-step-head">
            <h2>Personal Details</h2>
            <p>Your name, how we reach you, and the two identity documents the admin team verifies.</p>
          </div>
          <div class="czp-alert" data-czp-alert hidden></div>

          <div class="czp-avatar-row czp-field" data-czp-field="seller_photo"
               data-czp-file-label="Your photo" data-czp-file-images>
            <input type="file" class="czp-file" id="seller_photo_input" name="seller_photo" accept=".jpg,.jpeg,.png,.gif">
            <div class="czp-avatar" data-czp-avatar tabindex="0" role="button" aria-label="Upload your photo">
              <svg data-czp-avatar-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" <?= !empty($d['image']) ? 'hidden' : '' ?>>
                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
              </svg>
              <img data-czp-avatar-img alt=""
                   <?= !empty($d['image']) ? 'src="' . czp_attr(base_url(USER_IMG_PATH . $d['image'])) . '"' : '' ?>
                   <?= !empty($d['image']) ? '' : 'hidden' ?>>
            </div>
            <div class="czp-avatar-copy">
              <b>Profile photo</b>
              <small class="czp-hint">Optional. JPG, PNG or GIF, up to 8 MB.</small>
              <span class="czp-error" data-czp-error></span>
            </div>
            <label class="czp-btn czp-btn-ghost czp-btn-sm" for="seller_photo_input">Choose photo</label>
          </div>

          <fieldset class="czp-group">
            <legend>Your name</legend>
            <div class="czp-grid">
              <?php
              czp_text_field(['name' => 'first_name', 'label' => 'First Name', 'value' => $d['first_name'] ?? '', 'required' => true, 'col' => 4, 'placeholder' => 'First name', 'maxlength' => 50]);
              czp_text_field(['name' => 'middle_name', 'label' => 'Middle Name', 'value' => $d['middle_name'] ?? '', 'col' => 4, 'placeholder' => 'Optional', 'maxlength' => 50]);
              czp_text_field(['name' => 'last_name', 'label' => 'Last Name', 'value' => $d['last_name'] ?? '', 'required' => true, 'col' => 4, 'placeholder' => 'Last name', 'maxlength' => 50]);
              ?>
            </div>
          </fieldset>

          <fieldset class="czp-group">
            <legend>How we reach you</legend>
            <div class="czp-grid">
              <?php
              czp_text_field([
                  'name' => 'phone', 'label' => 'Phone Number', 'value' => $d['phone'] ?? '', 'required' => true,
                  'id' => 'phone', 'placeholder' => '10-digit mobile number', 'digits' => 10, 'contact' => 'phone',
                  'hint' => 'Used for order and payout alerts.',
              ]);
              czp_text_field([
                  'name' => 'email', 'label' => 'Email ID', 'value' => $d['email'] ?? '', 'required' => true,
                  'id' => 'email', 'type' => 'email', 'placeholder' => 'name@example.com', 'maxlength' => 254, 'contact' => 'email',
              ]);
              ?>
            </div>
          </fieldset>

          <fieldset class="czp-group">
            <legend>Your address</legend>
            <div class="czp-grid">
              <?php
              czp_text_field(['name' => 'address1', 'label' => 'Address', 'value' => $d['address1'] ?? '', 'required' => true, 'col' => 12, 'placeholder' => 'House / street']);
              czp_text_field([
                  'name' => 'pin', 'label' => 'PIN Code', 'value' => $d['pin'] ?? '', 'required' => true,
                  'id' => 'pin', 'col' => 6, 'placeholder' => '6-digit PIN code', 'digits' => 6, 'status' => 'pin_status',
              ]);
              czp_text_field(['name' => 'state', 'label' => 'State', 'value' => $d['state'] ?? '', 'required' => true, 'id' => 'state', 'col' => 6, 'placeholder' => 'State']);
              czp_text_field(['name' => 'district', 'label' => 'District', 'value' => $d['district'] ?? '', 'required' => true, 'id' => 'district', 'col' => 6, 'placeholder' => 'District']);
              czp_text_field(['name' => 'city', 'label' => 'City/Village/Town', 'value' => $d['city'] ?? '', 'required' => true, 'id' => 'city', 'col' => 6, 'placeholder' => 'City, village or town']);
              ?>
            </div>
          </fieldset>

          <fieldset class="czp-group">
            <legend>Identity documents</legend>
            <div class="czp-grid">
              <?php
              czp_file_field(['name' => 'national_identity_card', 'label' => 'Identity Proof', 'value' => $d['national_identity_card'] ?? '', 'required' => true, 'hint' => 'Aadhaar, passport, voter ID or driving licence']);
              czp_file_field(['name' => 'authorized_signature', 'label' => 'Authorized Signatory', 'value' => $d['authorized_signature'] ?? '', 'required' => true, 'hint' => 'A clear scan of your signature']);
              ?>
            </div>
          </fieldset>

          <footer class="czp-nav">
            <span></span>
            <span class="czp-nav-count">Step 1 of 5</span>
            <button type="button" class="czp-btn czp-btn-primary" data-czp-next>Continue</button>
          </footer>
        </section>

        <!-- ============================ STEP 2: STORE ======================= -->
        <section class="czp-step" data-czp-step="store" data-czp-label="Store Details" hidden>
          <div class="czp-step-head">
            <h2>Store Details</h2>
            <p>How buyers see your shop, and where the courier collects your parcels.</p>
          </div>
          <div class="czp-alert" data-czp-alert hidden></div>

          <div class="czp-avatar-row czp-field" data-czp-field="store_logo"
               data-czp-file-label="Shop logo" data-czp-file-images>
            <input type="file" class="czp-file" id="store_logo_input" name="store_logo" accept=".jpg,.jpeg,.png,.gif">
            <input type="hidden" name="old_store_logo" value="<?= czp_val($d, 'logo') ?>">
            <div class="czp-avatar is-square" data-czp-avatar tabindex="0" role="button" aria-label="Upload shop logo">
              <svg data-czp-avatar-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" <?= !empty($d['logo']) ? 'hidden' : '' ?>>
                <path d="M14.5 3h-13a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5M2 4h12v5.5l-2.6-2.1a.5.5 0 0 0-.63 0L7.5 10 5.6 8.6a.5.5 0 0 0-.6 0L2 10.8zm3.5 1.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
              </svg>
              <img data-czp-avatar-img alt=""
                   <?= !empty($d['logo']) ? 'src="' . czp_attr(base_url($d['logo'])) . '"' : '' ?>
                   <?= !empty($d['logo']) ? '' : 'hidden' ?>>
            </div>
            <div class="czp-avatar-copy">
              <b>Shop logo</b>
              <small class="czp-hint">Optional, but shops with a logo convert better. JPG, PNG or GIF, up to 8 MB.</small>
              <span class="czp-error" data-czp-error></span>
            </div>
            <label class="czp-btn czp-btn-ghost czp-btn-sm" for="store_logo_input">Choose logo</label>
          </div>

          <fieldset class="czp-group">
            <legend>Shop identity</legend>
            <div class="czp-grid">
              <?php
              czp_text_field(['name' => 'shop_name', 'label' => 'Shop Name', 'value' => $d['shop_name'] ?? '', 'required' => true, 'placeholder' => 'Shop name', 'maxlength' => 100, 'hint' => 'Must be unique across Cretzo.']);
              czp_text_field([
                  'name' => 'slug', 'label' => 'Store URL', 'value' => $d['slug'] ?? '', 'id' => 'slug_input', 'maxlength' => 255,
                  'placeholder' => !empty($d['shop_name']) ? $d['shop_name'] : 'your-shop-name',
                  'hint' => 'Leave blank and we build it from your shop name.',
              ]);
              czp_text_field([
                  'name' => 'shop_phone', 'label' => 'Shop Phone Number', 'value' => $d['shop_phone'] ?? '', 'required' => true,
                  'id' => 'shop_phone', 'placeholder' => '10-digit mobile number', 'digits' => 10, 'contact' => 'shop_phone',
                  'hint' => 'Your own personal number is fine here.',
              ]);
              czp_text_field(['name' => 'social', 'label' => 'Social Media Handle', 'value' => $d['social'] ?? '', 'placeholder' => 'Instagram, Facebook or website']);
              czp_text_field(['name' => 'store_description', 'label' => 'Store Description', 'value' => $d['store_description'] ?? '', 'col' => 12, 'type' => 'textarea', 'placeholder' => 'Tell customers about your store...']);
              ?>
            </div>
          </fieldset>

          <fieldset class="czp-group">
            <legend>Pickup address</legend>
            <div class="czp-grid">
              <?php
              czp_text_field(['name' => 'pickup_address1', 'label' => 'Pickup Address Lane 1', 'value' => $d['pickup_address1'] ?? '', 'required' => true, 'placeholder' => 'Address lane 1']);
              czp_text_field(['name' => 'pickup_address2', 'label' => 'Pickup Address Lane 2', 'value' => $d['pickup_address2'] ?? '', 'placeholder' => 'Address lane 2 (optional)']);
              czp_text_field([
                  'name' => 'pickup_pin', 'label' => 'PIN Code', 'value' => $d['pickup_pin'] ?? '', 'required' => true,
                  'id' => 'pickup_pin', 'placeholder' => '6-digit PIN code', 'digits' => 6, 'status' => 'pickup_pin_status',
              ]);
              czp_text_field(['name' => 'pickup_state', 'label' => 'State', 'value' => $d['pickup_state'] ?? '', 'required' => true, 'id' => 'pickup_state', 'placeholder' => 'State']);
              czp_text_field(['name' => 'pickup_district', 'label' => 'District', 'value' => $d['pickup_district'] ?? '', 'required' => true, 'id' => 'pickup_district', 'placeholder' => 'District']);
              czp_text_field(['name' => 'pickup_city', 'label' => 'City', 'value' => $d['pickup_city'] ?? '', 'required' => true, 'id' => 'pickup_city', 'placeholder' => 'City']);
              ?>
            </div>
            <small class="czp-hint">The courier collects from this address, so State, District and City all have to be filled in.</small>
          </fieldset>

          <fieldset class="czp-group">
            <legend>What you sell</legend>
            <div class="czp-grid">
              <div class="czp-field czp-col-6" data-czp-field="primary_category_id">
                <label class="czp-label" for="primary_category_id">Primary Product Category <i class="czp-req">*</i></label>
                <select class="czp-input" id="primary_category_id" name="primary_category_id">
                  <option value="">Select a category</option>
                  <?php foreach ($all_categories as $cat): ?>
                    <?php if ((int) $cat['parent_id'] !== 0) continue; // top-level only ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (isset($d['primary_category_id']) && (string) $d['primary_category_id'] === (string) $cat['id']) ? 'selected' : '' ?>>
                      <?= czp_attr($cat['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <span class="czp-error" data-czp-error></span>
              </div>

              <div class="czp-field czp-col-6" data-czp-field="secondary_category_ids">
                <label class="czp-label">Secondary Categories <small class="czp-hint" style="display:inline">(optional)</small></label>
                <small class="czp-hint">Only sub-categories of your primary category can be added.</small>
                <div class="czp-pills" id="category_pills"></div>
                <input type="hidden" name="secondary_category_ids" id="secondary_category_ids" value="<?= czp_val($d, 'category_ids') ?>">
                <button type="button" class="czp-btn czp-btn-ghost czp-btn-sm" id="category_open">+ Add categories</button>
                <span class="czp-error" data-czp-error></span>
              </div>
            </div>
          </fieldset>

          <footer class="czp-nav">
            <button type="button" class="czp-btn czp-btn-ghost" data-czp-back>Back</button>
            <span class="czp-nav-count">Step 2 of 5</span>
            <button type="button" class="czp-btn czp-btn-primary" data-czp-next>Continue</button>
          </footer>
        </section>

        <!-- ============================ STEP 3: BUSINESS ==================== -->
        <section class="czp-step" data-czp-step="business" data-czp-label="Business Details" hidden>
          <div class="czp-step-head">
            <h2>Business Details</h2>
            <p>Your entity type decides which documents apply below - the form shows only the ones you need.</p>
          </div>
          <div class="czp-alert" data-czp-alert hidden></div>

          <fieldset class="czp-group">
            <legend>Entity</legend>
            <div class="czp-grid">
              <div class="czp-field czp-col-4" data-czp-field="entity_type">
                <label class="czp-label" for="entity_type">Entity Type <i class="czp-req">*</i></label>
                <select class="czp-input" id="entity_type" name="entity_type">
                  <option value="individual" <?= $entity_type === 'individual' ? 'selected' : '' ?>>Individual</option>
                  <option value="sole_proprietorship" <?= $entity_type === 'sole_proprietorship' ? 'selected' : '' ?>>Sole Proprietorship</option>
                  <option value="partnership_firm" <?= $entity_type === 'partnership_firm' ? 'selected' : '' ?>>Partnership Firm</option>
                  <?php // Pvt Ltd. stays disabled per client request (2026-07-29) - they have not
                        // sent its Business Details spec yet. Revisit when they do. ?>
                </select>
                <span class="czp-error" data-czp-error></span>
              </div>
              <?php
              czp_text_field([
                  'name' => 'legal_business_name', 'label' => 'Legal Business Name',
                  'label_id' => 'legal_business_name_label', 'col' => 4,
                  'value' => $d['legal_business_name'] ?? '', 'id' => 'legal_business_name_input',
                  'maxlength' => 255, 'placeholder' => 'Legal business name',
                  'hint' => 'For an individual this is simply your own name - we fill it in for you.',
              ]);
              czp_text_field([
                  'name' => 'pan', 'label' => 'Your PAN Number', 'label_id' => 'pan_label', 'col' => 4,
                  'value' => $d['pan'] ?? '', 'required' => true, 'maxlength' => 10, 'placeholder' => 'ABCDE1234F',
              ]);
              ?>
            </div>
          </fieldset>

          <fieldset class="czp-group">
            <legend>Business address</legend>
            <div class="czp-grid">
              <?php
              czp_text_field(['name' => 'business_address1', 'label' => 'Address Line 1', 'value' => $d['business_address1'] ?? '', 'required' => true, 'placeholder' => 'Street 1']);
              czp_text_field(['name' => 'business_address2', 'label' => 'Address Line 2', 'value' => $d['business_address2'] ?? '', 'placeholder' => 'Street 2 (optional)']);
              czp_text_field([
                  'name' => 'business_pin', 'label' => 'PIN Code', 'value' => $d['business_pin'] ?? '', 'required' => true,
                  'id' => 'business_pin', 'placeholder' => '6-digit PIN code', 'digits' => 6, 'status' => 'business_pin_status',
              ]);
              czp_text_field(['name' => 'business_state', 'label' => 'State', 'value' => $d['business_state'] ?? '', 'required' => true, 'id' => 'business_state', 'placeholder' => 'State']);
              czp_text_field(['name' => 'business_district', 'label' => 'District', 'value' => $d['business_district'] ?? '', 'required' => true, 'id' => 'business_district', 'placeholder' => 'District']);
              czp_text_field(['name' => 'business_city', 'label' => 'City/Village/Town', 'value' => $d['business_city'] ?? '', 'required' => true, 'id' => 'business_city', 'placeholder' => 'City, village or town']);
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
                    <label for="gst_check">We are not GST registered</label>
                    <small class="czp-hint">Tick this and we ask for your GST Enrollment ID instead of a GSTIN.</small>
                  </div>
                </div>
              </div>
              <?php
              czp_text_field([
                  'name' => 'gst', 'label' => 'GST Number', 'value' => $d['gst'] ?? '', 'required' => true,
                  'maxlength' => 15, 'placeholder' => '22ABCDE0000A1Z5',
              ]);
              czp_text_field([
                  'name' => 'gst_enrollment_number', 'label' => 'GST Enrollment ID', 'value' => $d['gst_enrollment_number'] ?? '', 'required' => true,
                  'maxlength' => 64, 'placeholder' => 'Enrollment ID',
                  'hint' => 'You can sell only within your own state (government regulation). <a href="https://reg.gst.gov.in/registration/generateuid" target="_blank" rel="noopener">Don\'t have one? Apply now.</a>',
              ]);
              ?>
            </div>
          </fieldset>

          <?php // All of this step's uploads in one group. A grid row is as tall as its
                // tallest cell, so pairing a plain text field with an upload (label +
                // hint + picker + file chip) left a tall blank gap under the text one -
                // uploads now only ever sit beside other uploads. PAN Card is always
                // required, so this group can never be empty and needs no hiding. ?>
          <fieldset class="czp-group">
            <legend>Documents</legend>
            <div class="czp-grid">
              <?php
              czp_file_field(['name' => 'pan_card_document', 'label' => 'PAN Card', 'value' => $d['pan_card_document'] ?? '', 'required' => true]);
              czp_file_field(['name' => 'gstin_document', 'label' => 'GSTIN Document', 'value' => $d['gstin_document'] ?? '', 'required' => true]);
              czp_file_field(['name' => 'gst_enrollment_ack_document', 'label' => 'GST Enrollment Acknowledgement Slip', 'value' => $d['gst_enrollment_ack_document'] ?? '', 'required' => true]);
              czp_file_field(['name' => 'partnership_deed_document', 'label' => 'Partnership Deed', 'value' => $d['partnership_deed_document'] ?? '', 'required' => true]);
              czp_file_field(['name' => 'business_proof_document', 'label' => 'Business Proof', 'value' => $d['business_proof_document'] ?? '', 'required' => true, 'extra_hint_id' => 'business_proof_document_hint_extra']);
              czp_file_field(['name' => 'business_address_proof_document', 'label' => 'Business Address Proof', 'value' => $d['business_address_proof_document'] ?? '', 'required' => true, 'hint' => 'Electricity bill, rent/lease agreement or bank statement']);
              ?>
            </div>
          </fieldset>

          <fieldset class="czp-group" id="entity_check_box_group">
            <legend>Declaration</legend>
            <div class="czp-check" id="entity_check_box" <?= $entity_type === 'individual' ? '' : 'hidden' ?>>
              <?php // Pre-ticked only when this profile was already saved as an Individual,
                    // i.e. the seller accepted the declaration on a previous save. ?>
              <input type="checkbox" id="entity_check" <?= $entity_type === 'individual' ? 'checked' : '' ?>>
              <div class="czp-check-body">
                <label for="entity_check">We are not a registered Entity. <i class="czp-req">*</i></label>
                <small class="czp-hint">Mandatory for the Individual entity type.</small>
                <span class="czp-error" data-czp-error></span>
              </div>
            </div>
          </fieldset>

          <footer class="czp-nav">
            <button type="button" class="czp-btn czp-btn-ghost" data-czp-back>Back</button>
            <span class="czp-nav-count">Step 3 of 5</span>
            <button type="button" class="czp-btn czp-btn-primary" data-czp-next>Continue</button>
          </footer>
        </section>

        <!-- ============================ STEP 4: BANK ======================== -->
        <section class="czp-step" data-czp-step="account" data-czp-label="Bank Account" hidden>
          <div class="czp-step-head">
            <h2>Bank Account</h2>
            <p>Where your payouts land. Double-check the account number - a wrong digit sends money to the wrong place.</p>
          </div>
          <div class="czp-alert" data-czp-alert hidden></div>

          <fieldset class="czp-group">
            <legend>Account</legend>
            <div class="czp-grid">
              <?php
              czp_text_field(['name' => 'account_number', 'label' => 'Account Number', 'value' => $d['account_number'] ?? '', 'required' => true, 'digits' => 18, 'placeholder' => 'Account number']);
              czp_text_field(['name' => 'confirm_account_number', 'label' => 'Confirm Account Number', 'value' => $d['account_number'] ?? '', 'required' => true, 'digits' => 18, 'placeholder' => 'Re-type the account number']);
              czp_text_field(['name' => 'account_holder_name', 'label' => "Account Holder's Name", 'value' => $d['account_holder_name'] ?? '', 'required' => true, 'placeholder' => 'Name exactly as on the account']);
              czp_text_field(['name' => 'ifsc', 'label' => 'IFSC Code', 'value' => $d['ifsc'] ?? '', 'required' => true, 'maxlength' => 11, 'placeholder' => 'SBIN0001234']);
              czp_text_field(['name' => 'branch', 'label' => 'Branch Name', 'value' => $d['branch'] ?? '', 'required' => true, 'placeholder' => 'Branch']);
              ?>

              <div class="czp-field czp-col-6" data-czp-field="bank_name">
                <label class="czp-label" for="bank_search">Bank Name <i class="czp-req">*</i></label>
                <?php if (!empty($indian_banks)): ?>
                  <div class="czp-combo">
                    <input type="text" class="czp-input" id="bank_search" placeholder="Start typing your bank..." autocomplete="off">
                    <div class="czp-combo-list" id="bank_dropdown"></div>
                  </div>
                  <?php // The hidden input is what gets submitted and what the validator
                        // reads - the visible box is only the search field. ?>
                  <input type="hidden" name="bank_name" id="bank_name_hidden" data-czp-control value="<?= czp_val($d, 'bank_name') ?>">
                  <small class="czp-hint">Not in the list? Just type it in - whatever you type is saved.</small>
                <?php else: ?>
                  <input type="text" class="czp-input" name="bank_name" id="bank_name_hidden" data-czp-control
                         value="<?= czp_val($d, 'bank_name') ?>" placeholder="Bank name">
                <?php endif; ?>
                <span class="czp-error" data-czp-error></span>
              </div>

              <?php czp_file_field(['name' => 'bank_account_proof_document', 'label' => 'Bank Account Proof', 'value' => $d['bank_account_proof_document'] ?? '', 'required' => false, 'hint' => 'Passbook, statement or cancelled cheque']); ?>
            </div>
          </fieldset>

          <footer class="czp-nav">
            <button type="button" class="czp-btn czp-btn-ghost" data-czp-back>Back</button>
            <span class="czp-nav-count">Step 4 of 5</span>
            <button type="button" class="czp-btn czp-btn-primary" data-czp-next>Continue</button>
          </footer>
        </section>

        <!-- ============================ STEP 5: REVIEW ====================== -->
        <section class="czp-step" data-czp-step="admin" data-czp-label="Review &amp; Submit" hidden>
          <div class="czp-step-head">
            <h2>Review &amp; Submit</h2>
            <p>Everything is already checked step by step - this page only sends it.</p>
          </div>
          <div class="czp-alert" data-czp-alert hidden></div>

          <?php if ($is_verified): ?>
            <div class="czp-alert czp-alert-success">
              <b>Your seller account is admin verified</b>
              Product management is unlocked. You can keep your details up to date here at any time.
            </div>

          <?php elseif ($requested_at !== ''): ?>
            <div class="czp-alert czp-alert-info">
              <b>Your details are with the admin team</b>
              Sent for review on <?= czp_attr(date('d M Y, h:i A', strtotime($requested_at))) ?>.
              Product listing and subscription unlock as soon as your account is approved, and you will be
              notified by email. You can still correct your details and save them again.
            </div>

          <?php else: ?>
            <div class="czp-alert czp-alert-warn">
              <b>Submitting sends your profile for admin verification</b>
              Press Submit to save. Once every section is filled in, your details go to the Cretzo team
              automatically - there is nothing else to click. Product listing and subscription unlock
              after the admin approves your account.
            </div>

            <ul class="czp-review-list">
              <?php foreach (seller_profile_sections() as $key => $section): ?>
                <?php $done = !in_array($key, $missing_keys, true); ?>
                <li class="<?= $done ? 'is-done' : 'is-todo' ?>">
                  <span class="czp-dot" aria-hidden="true"><?= $done ? '&#10003;' : '!' ?></span>
                  <span><?= czp_attr($section['label']) ?></span>
                  <?php if ($done): ?>
                    <small>Complete</small>
                  <?php else: ?>
                    <button type="button" class="czp-btn czp-btn-ghost czp-btn-sm"
                            onclick="openProfileSection('<?= czp_attr($key) ?>')">Fill this in</button>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>

            <?php if (!empty($missing)): ?>
              <small class="czp-hint">Your profile is saved either way, but it is only sent for verification once the sections above are complete.</small>
            <?php endif; ?>
          <?php endif; ?>

          <footer class="czp-nav">
            <button type="button" class="czp-btn czp-btn-ghost" data-czp-back>Back</button>
            <span class="czp-nav-count">Step 5 of 5</span>
            <button type="button" class="czp-btn czp-btn-primary" data-czp-submit>Submit</button>
          </footer>
        </section>

      </form>
    </div>
  </div>
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
        <label class="czp-pick" data-parent="<?= (int) $cat['parent_id'] ?>" data-label="<?= czp_attr($cat['name']) ?>" hidden>
          <input type="checkbox" value="<?= (int) $cat['id'] ?>">
          <span><?= czp_attr($cat['name']) ?></span>
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
window.CZP_CONFIG = {
  saveUrl: <?= json_encode(base_url('seller/login/update_user')) ?>,
  checkContactUrl: <?= json_encode(base_url('seller/login/check_contact')) ?>,
  homeUrl: <?= json_encode(base_url('seller/home')) ?>,
  initialSection: <?= json_encode($initial) ?>,
  banks: <?= json_encode(array_column($indian_banks ?: [], 'bank_name')) ?>
};
</script>
<script src="<?= base_url('assets/seller/js/cretzo/seller-profile.js') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/js/cretzo/seller-profile.js') ?: time() ?>"></script>
