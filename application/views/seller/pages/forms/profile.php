<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/form.css') ?>">
<style>
  .is-invalid {
  border: 1px solid red;
}
.error-msg {
  font-size: 12px;
}
.main-footer{
  display: none;
}
#response {
  margin-top: 15px;
  padding: 0 10px;
  font-size: 14px;
}
.pincode-lookup-status {
  display: block;
  font-size: 12px;
  margin-top: 4px;
}
.pincode-lookup-status.error {
  color: #dc3545;
}
.pincode-lookup-status.success {
  color: #198754;
}
.pincode-lookup-status.info {
  color: #6c757d;
}
#toast-msg {
  display: none;
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  padding: 14px 28px;
  border-radius: 8px;
  font-weight: bold;
  font-size: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.category-pill {
  display: inline-flex;
  align-items: center;
  background: #eef2ff;
  color: #3730a3;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  margin: 4px 6px 0 0;
}
.category-picker-modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}
.category-picker-content {
  background: #fff;
  width: min(640px, 92vw);
  max-height: 80vh;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0,0,0,.2);
  display: flex;
  flex-direction: column;
}
.category-picker-header,
.category-picker-footer {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
}
.category-picker-footer {
  border-top: 1px solid #e5e7eb;
  border-bottom: 0;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
.category-picker-list {
  padding: 12px 16px;
  overflow-y: auto;
}
.category-picker-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 0;
}
.category-pill button {
  background: none;
  border: none;
  color: #3730a3;
  cursor: pointer;
  font-weight: bold;
  margin-left: 4px;
  padding: 0;
  line-height: 1;
}
.category-picker-close {
  background: none;
  border: none;
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
  color: #6c757d;
}
.btn-add-categories {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background-color: #fff;
  color: var(--color-orange, #F2822E);
  border: 1px solid var(--color-orange, #F2822E);
  border-radius: 0.6rem;
  padding: 0.5rem 1rem;
  font-size: 13px;
  cursor: pointer;
  margin-top: 0.5rem;
}
.btn-add-categories:hover {
  background-color: var(--color-orange, #F2822E);
  color: #fff;
}
.content-wrapper {
  background: transparent !important; 
}
.seller-form {
  position: static !important;
}
.form-container-main {
  left: auto !important;
  transform: none !important;
  margin: 1rem auto !important;
  height: calc(100vh - 65px) !important;
}
.personal-photo-preview {
  border-radius: 50%;
  width: 5rem;
  height: 5rem;
  aspect-ratio: 1;
  flex-shrink: 0;
  background-color: #f7f7f7;
  border: 2px solid var(--color-secondary, #ccc);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  cursor: pointer;
}
.personal-photo-preview:hover {
  border-color: var(--color-orange, #F2822E);
  box-shadow: 0 0 0 4px rgba(242, 130, 46, 0.15);
}
.personal-photo-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.personal-photo-icon {
  width: 2.4rem;
  height: 2.4rem;
  color: #999;
}
.doc-upload-thumb {
  max-height: 90px;
  border-radius: 6px;
  border: 1px solid #ddd;
  margin-top: 8px;
  display: block;
}
.doc-upload-hint {
  font-size: 12px;
  color: #6c757d;
  display: block;
  margin-top: 4px;
}
.doc-upload-preview-wrap {
  position: relative;
  display: inline-block;
}
/* This rule must stay AFTER .doc-upload-preview-wrap above: form.css's shared
   .hidden{display:none} loads earlier in the cascade and, at equal specificity,
   loses to the wrap's own display:inline-block — so it never actually hid. */
.doc-upload-preview-wrap.hidden {
  display: none;
}
.doc-remove-btn {
  position: absolute;
  top: 2px;
  right: -8px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #dc3545;
  color: #fff;
  border: 2px solid #fff;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}
.doc-remove-btn:hover {
  background: #bb2d3b;
}
</style>
</head>
<body>
<div id="toast-msg"></div>

<div class="content-wrapper">
  <section class="content w-100 seller-form">
      <div class="container-fluid">
        <div class="form-parent">
          <div class="form-container-main" style="margin-top: 0!important;">

              <div class="form-header w-100">
                  <div class="slider d-flex w-100 justify-content-between align-items-center">
                              <div class="form-indicator form-indicator-1  active">
                                  <p class="text-n text-capitalize">personal details</p>
                              </div>
                              <div class="completion-line completion-line-1"></div>
                              <div class="form-indicator form-indicator-2">
                                      <p class="text-n text-capitalize">store details</p>
                              </div>
                              <div class="completion-line completion-line-2"></div>
                              <div class="form-indicator form-indicator-3">
                                          <p class="text-n text-capitalize">business details</p>
                              </div>
                              <div class="completion-line completion-line-3"></div>
                              <div class="form-indicator form-indicator-4">
                                          <p class="text-n text-capitalize">admin verification</p>
                              </div>
                  </div>
              </div>

              <div class="form-container">

                <!-- FIX 1 — Removed onSubmit="submitForm(e)" (e was undefined, caused silent crash)
                     Form submission now handled entirely by JS event listener below -->
                <form id="seller_form" enctype="multipart/form-data"> 
                  
                    <div class="form-step form1">
                      <div class="photo-upload d-flex gap-4 justify-content-between align-items-center mb-3">
                        <input type="file" class="hidden" name="seller_photo" id="personalPhotoInput" accept="image/*">
                        <div class="personal-photo-preview" id="personalPhotoContainer">
                          <svg class="personal-photo-icon" id="personalPhotoIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="<?= !empty($fetched_data[0]['image']) ? 'display:none;' : '' ?>">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                          </svg>
                          <img id="personalPhotoPreview" src="<?= !empty($fetched_data[0]['image']) ? base_url(USER_IMG_PATH . $fetched_data[0]['image']) : '' ?>" class="<?= empty($fetched_data[0]['image']) ? 'hidden' : '' ?>">
                        </div>
                        <label for="personalPhotoInput" class="btn-upload-logo mt-3">
                          📷 Upload Your Photo
                        </label>
                      </div>
                      <div class="row gap-xl-5">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">First Name <span class="text-danger">*</span></label>
                          <input name="first_name" type="text" class="input" placeholder="First name" value="<?=$fetched_data[0]['first_name']?>" minlength="3" maxlength="50" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Middle Name</label>
                          <input name="middle_name" type="text" class="input" placeholder="Middle Name (optional)" value="<?=$fetched_data[0]['middle_name'] ?? ''?>" maxlength="50">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Last Name <span class="text-danger">*</span></label>
                          <input name="last_name" type="text" class="input" placeholder="Last Name" value="<?=$fetched_data[0]['last_name']?>" maxlength="50" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                          <input name="phone" type="text" id="phone" value="<?=$fetched_data[0]['phone']?>" class="input" placeholder="Enter Phone Number" required maxlength="10" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                          <span id="phone_error" class="text-danger"></span>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Email ID <span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 START — type="email" enforces email format at browser level, maxlength corrected from max_length to maxlength -->
                          <input name="email" type="email" id="email" class="input" placeholder="Enter Email ID" maxlength="254" value="<?=$fetched_data[0]['email']?>" required>
                          <!-- BUG FIX #6 END -->
                          <span id="email_error" class="text-danger"></span>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Address <span class="text-danger">*</span></label>
                          <input name="address1" type="text" class="input" placeholder="Street 1" value="<?=$fetched_data[0]['address1']?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                          <input name="pin" id="pin" type="text" class="input" placeholder="Enter PIN Code" value="<?=$fetched_data[0]['pin']?>" required maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                          <span id="pin_lookup_status" class="pincode-lookup-status"></span>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State <span class="text-danger">*</span></label>
                          <!-- State is filled by pincode lookup and can be edited manually -->
                          <input name="state" id="state" type="text" class="input" placeholder="Enter State" value="<?= htmlspecialchars($fetched_data[0]['state'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <!-- District is filled by pincode lookup and can be edited manually -->
                          <label class="form-label">District <span class="text-danger">*</span></label>
                          <input name="district" id="district" type="text" class="input" placeholder="Enter District" value="<?= htmlspecialchars($fetched_data[0]['district'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City/Village/Town <span class="text-danger">*</span></label>
                          <!-- City is filled by pincode lookup and can be edited manually -->
                          <input name="city" id="city" type="text" class="input" placeholder="Enter City/Village/Town" value="<?= htmlspecialchars($fetched_data[0]['city'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Identity Proof <span class="text-danger">*</span></label>
                          <input type="file" class="input" name="national_identity_card" id="national_identity_card_input" accept="image/*">
                          <input type="hidden" name="old_national_identity_card" value="<?= htmlspecialchars($fetched_data[0]['national_identity_card'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                          <div class="doc-upload-preview-wrap<?= empty($fetched_data[0]['national_identity_card']) ? ' hidden' : '' ?>" id="national_identity_card_wrap">
                            <a href="<?= !empty($fetched_data[0]['national_identity_card']) ? base_url($fetched_data[0]['national_identity_card']) : '' ?>" target="_blank" id="national_identity_card_link">
                              <img id="national_identity_card_preview" src="<?= !empty($fetched_data[0]['national_identity_card']) ? base_url($fetched_data[0]['national_identity_card']) : '' ?>" class="doc-upload-thumb" alt="Identity Proof">
                            </a>
                            <button type="button" class="doc-remove-btn" data-target="national_identity_card" aria-label="Remove Identity Proof">&times;</button>
                          </div>
                          <small class="doc-upload-hint" id="national_identity_card_hint" style="<?= empty($fetched_data[0]['national_identity_card']) ? 'display:none;' : '' ?>">Current file on record. Leave blank if there is no change.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Authorized Signatory <span class="text-danger">*</span></label>
                          <input type="file" class="input" name="authorized_signature" id="authorized_signature_input" accept="image/*">
                          <input type="hidden" name="old_authorized_signature" value="<?= htmlspecialchars($fetched_data[0]['authorized_signature'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                          <div class="doc-upload-preview-wrap<?= empty($fetched_data[0]['authorized_signature']) ? ' hidden' : '' ?>" id="authorized_signature_wrap">
                            <a href="<?= !empty($fetched_data[0]['authorized_signature']) ? base_url($fetched_data[0]['authorized_signature']) : '' ?>" target="_blank" id="authorized_signature_link">
                              <img id="authorized_signature_preview" src="<?= !empty($fetched_data[0]['authorized_signature']) ? base_url($fetched_data[0]['authorized_signature']) : '' ?>" class="doc-upload-thumb" alt="Authorized Signatory">
                            </a>
                            <button type="button" class="doc-remove-btn" data-target="authorized_signature" aria-label="Remove Authorized Signatory">&times;</button>
                          </div>
                          <small class="doc-upload-hint" id="authorized_signature_hint" style="<?= empty($fetched_data[0]['authorized_signature']) ? 'display:none;' : '' ?>">Current file on record. Leave blank if there is no change.</small>
                        </div>

                      </div>

                        <div class="text-center mt-3">
                          <button type="button" class="btn btn-next-1">Next</button>
                        </div>
                    </div>

                    <div class="form-step form2">
                        <div>
                          <div class="photo-upload d-flex gap-4 justify-content-between align-items-center mb-3">
                            <input type="file" class="hidden" name="store_logo" id="photoInput" accept="image/*">
                            <input type="hidden" name="old_store_logo" value="<?= htmlspecialchars($fetched_data[0]['logo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="preview-container">
                              <svg class="profile-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16" style="<?= !empty($fetched_data[0]['logo']) ? 'display:none;' : '' ?>">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                              </svg>
                              <img id="photoPreview" src="<?= !empty($fetched_data[0]['logo']) ? base_url($fetched_data[0]['logo']) : '' ?>" class="shop-logo<?= empty($fetched_data[0]['logo']) ? ' hidden' : '' ?>">
                            </div>
                            <label for="photoInput" class="btn-upload-logo mt-3">
                             📷 Upload Shop Logo
                         </label>
                         </div>
                        </div>

                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Shop Name <span class="text-danger">*</span></label>
                          <input name="shop_name" type="text" class="input" placeholder="Shop Name" value="<?=$fetched_data[0]['shop_name']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Social Media Handle</label>
                          <input name="social" type="text" class="input" placeholder="Enter Social Media" value="<?=$fetched_data[0]['social']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Shop Phone Number <span class="text-danger">*</span></label>
                          <input name="shop_phone" type="text" class="input" placeholder="Enter shop Phone Number" value="<?=$fetched_data[0]['shop_phone']?>" required maxlength="10" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Pickup Address Lane 1 <span class="text-danger">*</span></label>
                          <input name="pickup_address1" type="text" class="input" placeholder="Address Lane 1" value="<?=$fetched_data[0]['pickup_address1']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Pickup Address Lane 2</label>
                          <input name="pickup_address2" type="text" class="input" placeholder="Address Lane 2" value="<?=$fetched_data[0]['pickup_address2']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                          <input name="pickup_pin" id="pickup_pin" type="text" class="input" placeholder="Enter PIN Code" value="<?=$fetched_data[0]['pickup_pin']?>" required maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                          <span id="pickup_pin_lookup_status" class="pincode-lookup-status"></span>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State</label>
                          <input name="pickup_state" id="pickup_state" type="text" class="input" placeholder="Enter State" value="<?= htmlspecialchars($fetched_data[0]['pickup_state'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">District</label>
                          <input name="pickup_district" id="pickup_district" type="text" class="input" placeholder="Enter District" value="<?= htmlspecialchars($fetched_data[0]['pickup_district'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City</label>
                          <input name="pickup_city" id="pickup_city" type="text" class="input" placeholder="Enter City" value="<?= htmlspecialchars($fetched_data[0]['pickup_city'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                          <label class="form-label">Store URL</label>
                          <input name="slug" id="slug_input" type="text" class="input" placeholder="<?= !empty($fetched_data[0]['shop_name']) ? htmlspecialchars($fetched_data[0]['shop_name'], ENT_QUOTES, 'UTF-8') : 'your-shop-name' ?>" value="<?= htmlspecialchars($fetched_data[0]['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="255">
                        </div>
                        <div class="col-md-12 mb-3">
                          <label class="form-label">Store Description</label>
                          <textarea name="store_description" class="input" rows="3" placeholder="Tell customers about your store..."><?= htmlspecialchars($fetched_data[0]['store_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Primary Product Category <span class="text-danger">*</span></label>
                          <select name="primary_category_id" id="primary_category_id" class="input" required>
                            <option value="">Select a category</option>
                            <?php foreach ($all_categories as $cat): ?>
                              <?php if ((int) $cat['parent_id'] !== 0) continue; // top-level categories only ?>
                              <option value="<?= $cat['id'] ?>" <?= (isset($fetched_data[0]['primary_category_id']) && (string)$fetched_data[0]['primary_category_id'] === (string)$cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Secondary Categories <small class="text-muted">(optional)</small></label>
                          <small class="text-muted d-block mb-1">Choose a Primary Product Category first — only its sub-categories can be added here.</small>
                          <div id="secondary_category_pills"></div>
                          <button type="button" class="btn-add-categories" id="open_category_picker_btn">+ Add Categories</button>
                          <input type="hidden" name="secondary_category_ids" id="secondary_category_ids_hidden" value="<?= htmlspecialchars($fetched_data[0]['category_ids'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                      </div>

                      <div class="category-picker-modal" id="category_picker_modal">
                        <div class="category-picker-content">
                          <div class="category-picker-header d-flex justify-content-between align-items-center">
                            <strong>Select Secondary Categories</strong>
                            <button type="button" class="category-picker-close" id="close_category_picker_btn" aria-label="Close">&times;</button>
                          </div>
                          <div class="category-picker-list" id="category_picker_list">
                            <?php foreach ($all_categories as $cat): ?>
                              <?php if ((int) $cat['parent_id'] === 0) continue; // only sub-categories are selectable as "secondary" ?>
                              <label class="category-picker-item" data-parent="<?= (int) $cat['parent_id'] ?>">
                                <input type="checkbox" class="secondary-category-checkbox" value="<?= $cat['id'] ?>">
                                <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                              </label>
                            <?php endforeach; ?>
                            <p class="text-muted mb-0" id="category_picker_empty_msg" style="display:none;">Please select a Primary Product Category first.</p>
                          </div>
                          <div class="category-picker-footer">
                            <button type="button" class="btn-add-categories" id="cancel_category_picker_btn">Cancel</button>
                            <button type="button" class="btn-upload-logo" id="done_category_picker_btn" style="padding:0.5rem 1rem;">Done</button>
                          </div>
                        </div>
                      </div>

                      <div class="mt-3 w-100 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-back-1">Back</button>
                        <button type="button" class="btn btn-next-2">Next</button>
                      </div>

                    </div>

                    <?php
                      // Reusable Business Details document-upload widget: file input + hidden
                      // old_<field> fallback (so resubmitting without a new file keeps the one
                      // already on record) + an image thumbnail preview (PDFs show no preview,
                      // just the "Current file on record" hint below) + the same red × remove
                      // button as Identity Proof/Authorized Signatory. $extra_hint renders as a
                      // small note right under the label (e.g. which documents count as proof)
                      // — kept INSIDE this field's own column so it can't overlap the next one.
                      function render_business_doc_field($field, $fetched_data, $label, $required = true, $extra_hint = '') {
                        $value = $fetched_data[0][$field] ?? '';
                        $is_pdf = !empty($value) && preg_match('/\.pdf$/i', $value);
                        ob_start();
                    ?>
                        <div class="col-md-6 mb-3" id="<?= $field ?>_field">
                          <label class="form-label"><?= $label ?><?= $required ? ' <span class="text-danger">*</span>' : '' ?></label>
                          <?php if ($extra_hint !== ''): ?>
                            <small class="text-muted d-block" id="<?= $field ?>_extra_hint"><?= $extra_hint ?></small>
                          <?php else: ?>
                            <small class="text-muted d-block hidden" id="<?= $field ?>_extra_hint"></small>
                          <?php endif; ?>
                          <input type="file" class="input" name="<?= $field ?>" id="<?= $field ?>_input" accept="image/*,application/pdf">
                          <input type="hidden" name="old_<?= $field ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                          <div class="doc-upload-preview-wrap<?= empty($value) ? ' hidden' : '' ?>" id="<?= $field ?>_wrap">
                            <a href="<?= (!empty($value) && !$is_pdf) ? base_url($value) : '' ?>" target="_blank" id="<?= $field ?>_link">
                              <img id="<?= $field ?>_preview" src="<?= (!empty($value) && !$is_pdf) ? base_url($value) : '' ?>" class="doc-upload-thumb<?= ($is_pdf || empty($value)) ? ' hidden' : '' ?>" alt="<?= $label ?>">
                            </a>
                            <button type="button" class="doc-remove-btn" data-target="<?= $field ?>" aria-label="Remove <?= $label ?>">&times;</button>
                          </div>
                          <small class="doc-upload-hint" id="<?= $field ?>_hint" style="<?= empty($value) ? 'display:none;' : '' ?>">Current file on record. Leave blank if there is no change.</small>
                        </div>
                    <?php
                        return ob_get_clean();
                      }
                    ?>
                    <div class="form-step form3">

                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Entity Type <span class="text-danger">*</span></label>
                          <?php $selected_entity_type = $fetched_data[0]['entity_type'] ?? 'individual'; ?>
                          <select name="entity_type" class="input" id="entity_type">
                            <option value="individual" <?= $selected_entity_type === 'individual' ? 'selected' : '' ?>>Individual</option>
                            <option value="sole_proprietorship" <?= $selected_entity_type === 'sole_proprietorship' ? 'selected' : '' ?>>Sole Proprietorship</option>
                            <option value="partnership_firm" <?= $selected_entity_type === 'partnership_firm' ? 'selected' : '' ?>>Partnership Firm</option>
                            <!-- Pvt Ltd. disabled per client request (2026-07-29) — client hasn't sent
                                 its Business Details spec yet, revisit once they do.
                            <option value="pvt_ltd" <?= $selected_entity_type === 'pvt_ltd' ? 'selected' : '' ?>>Pvt Ltd.</option>
                            -->
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label" id="legal_business_name_label">Legal Business Name</label>
                          <span class="doc-upload-hint" id="legal_business_name_tooltip" style="display:none;" title="For individuals, this is the same as your personal name.">ⓘ same as your personal name</span>
                          <input name="legal_business_name" id="legal_business_name_input" type="text" class="input" placeholder="Legal Business Name" maxlength="255" value="<?= htmlspecialchars($fetched_data[0]['legal_business_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label" id="pan_label">Your PAN Number<span class="text-danger">*</span></label>
                          <input name="pan" type="text" maxlength="10" class="input" placeholder="Enter PAN Number" value="<?=$fetched_data[0]['pan']?>" required>
                        </div>
                        <?= render_business_doc_field('pan_card_document', $fetched_data, 'Upload PAN Card') ?>
                      </div>

                      <h3>Business Address</h3>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                          <input name="business_address1" type="text" class="input" placeholder="Street 1" value="<?= htmlspecialchars($fetched_data[0]['business_address1'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Address Line 2</label>
                          <input name="business_address2" type="text" class="input" placeholder="Street 2" value="<?= htmlspecialchars($fetched_data[0]['business_address2'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                          <input name="business_pin" id="business_pin" type="text" class="input" placeholder="Enter PIN Code" value="<?= htmlspecialchars($fetched_data[0]['business_pin'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                          <span id="business_pin_lookup_status" class="pincode-lookup-status"></span>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State <span class="text-danger">*</span></label>
                          <input name="business_state" id="business_state" type="text" class="input" placeholder="Enter State" value="<?= htmlspecialchars($fetched_data[0]['business_state'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">District <span class="text-danger">*</span></label>
                          <input name="business_district" id="business_district" type="text" class="input" placeholder="Enter District" value="<?= htmlspecialchars($fetched_data[0]['business_district'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City/Village/Town <span class="text-danger">*</span></label>
                          <input name="business_city" id="business_city" type="text" class="input" placeholder="Enter City/Village/Town" value="<?= htmlspecialchars($fetched_data[0]['business_city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                      </div>

                      <div class="row">
                        <?php
                          $is_gst_registered = isset($fetched_data[0]['is_gst_registered']) ? $fetched_data[0]['is_gst_registered'] : 1;
                          $gst_enrollment_number = isset($fetched_data[0]['gst_enrollment_number']) ? $fetched_data[0]['gst_enrollment_number'] : '';
                          $is_non_gst = ($is_gst_registered == 0);
                        ?>
                        <div class="col-md-6 mb-3" id="gst_number_div" style="<?= $is_non_gst ? 'display:none;' : '' ?>">
                          <label class="form-label">GST Number <span class="text-danger">*</span></label>
                          <input name="gst" type="text" maxlength="15" class="input" placeholder="22ABCDE0000A1Z5" value="<?=$fetched_data[0]['gst']?>" <?= $is_non_gst ? '' : 'required' ?>>
                        </div>
                        <?= render_business_doc_field('gstin_document', $fetched_data, 'Upload GSTIN PDF') ?>
                        <div class="col-md-6 mb-3" id="gst_enrollment_div" style="<?= $is_non_gst ? '' : 'display:none;' ?>">
                          <label class="form-label">GST Enrollment ID <span class="text-danger">*</span></label>
                          <input name="gst_enrollment_number" type="text" maxlength="64" class="input" placeholder="Enter GST Enrollment ID" value="<?= html_escape($gst_enrollment_number) ?>" <?= $is_non_gst ? 'required' : '' ?>>
                          <small class="text-muted d-block mt-1">You can sell only within your own state (as per government regulation).</small>
                          <small class="text-muted d-block mt-1"><a href="https://reg.gst.gov.in/registration/generateuid" target="_blank" rel="noopener">Don't have Enrollment ID? Apply Now.</a></small>
                        </div>
                        <?= render_business_doc_field('gst_enrollment_ack_document', $fetched_data, 'Upload GST Enrollment ID Acknowledgement Slip') ?>
                      </div>

                      <div class="row" id="partnership_deed_section" style="display:none;">
                        <?= render_business_doc_field('partnership_deed_document', $fetched_data, 'Upload Partnership Deed') ?>
                      </div>

                      <div class="row" id="business_proof_section" style="display:none;">
                        <?= render_business_doc_field('business_proof_document', $fetched_data, 'Business Proof', true, '') ?>
                        <?= render_business_doc_field('business_address_proof_document', $fetched_data, 'Business Address Proof (electricity bill, rent/lease agreement, or bank statement)', true) ?>
                      </div>

                      <h3>Declaration</h3>
                      <div class="d-flex flex-column justify-content-between align-items-start">
                          <div id="entity_check_div">
                              <input type="checkbox" id="entity_check" class="check-input">
                              <label for="entity_check">We are not a registered Entity.</label>
                          </div>
                          <div>
                              <input type="checkbox" id="gst_check" name="gst_check" value="1" class="check-input" <?= $is_non_gst ? 'checked' : '' ?>>
                              <label for="gst_check">We are not GST registered.</label>
                          </div>
                      </div>

                      <h3>Account Details</h3>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Account Number<span class="text-danger">*</span></label>
                          <input name="account_number" type="text" class="input" maxlength="18" placeholder="Enter your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Confirm Account Number<span class="text-danger">*</span></label>
                          <input name="confirm_account_number" type="text" class="input" maxlength="18" placeholder="Confirm your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Account Holder name<span class="text-danger">*</span></label>
                          <input name="account_holder_name" type="text" class="input" placeholder="Enter  the Account Holder's name" value="<?=$fetched_data[0]['account_holder_name']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">IFSC Code<span class="text-danger">*</span></label>
                          <input name="ifsc" type="text" class="input" placeholder="Enter IFSC Code" maxlength="11" value="<?=$fetched_data[0]['ifsc']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                          <input name="branch" type="text" class="input" placeholder="Enter Branch" value="<?=$fetched_data[0]['branch']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <?php if (!empty($indian_banks)): ?>
                            <input type="text" id="bank_search" class="input" placeholder="Search Bank Name..." autocomplete="off">
                            <input type="hidden" name="bank_name" id="bank_name_hidden" required value="<?= $fetched_data[0]['bank_name'] ?>">
                            <div id="bank_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                            <?php else: ?>
                            <input type="text" name="bank_name" id="bank_name_hidden" class="input" placeholder="Enter Bank Name" required value="<?= $fetched_data[0]['bank_name'] ?>">
                            <?php endif; ?>
                        </div>
                        <?= render_business_doc_field('bank_account_proof_document', $fetched_data, 'Bank Account Proof (passbook, statement, or cancelled cheque)', false) ?>
                      </div>

                      <div class="mt-3 w-100 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-back-2">Back</button>
                        <button type="button" class="btn btn-next-3">Next</button>
                      </div>

                    </div>

                    <div class="form-step form4">
                      <h3 class="mb-3">Admin Verification</h3>
                      <?php $is_admin_verified = isset($fetched_data[0]['status']) && (string)$fetched_data[0]['status'] === '1'; ?>
                      <?php if ($is_admin_verified): ?>
                        <p class="text-success mb-0"><i class="fa fa-check-circle"></i> Your seller account is admin verified. Product management is unlocked.</p>
                      <?php else: ?>
                        <p class="mb-2 text-muted">Submit this form to request admin verification. This step contributes <strong>20%</strong> to profile completion.</p>
                        <label for="verification_note" class="form-label">Verification note <span class="text-danger">*</span></label>
                        <textarea id="verification_note" name="verification_note" class="input" rows="3" placeholder="Write a short note for admin review..."><?= isset($fetched_data[0]['verification_request_note']) ? htmlspecialchars($fetched_data[0]['verification_request_note']) : '' ?></textarea>
                        <div class="mt-2">
                          <button type="button" id="request_verification_btn" class="btn btn-primary btn-sm">Request Admin Verification</button>
                          <?php if (!empty($fetched_data[0]['verification_requested_at'])): ?>
                            <small class="text-muted d-block mt-2">Last requested at: <?= htmlspecialchars($fetched_data[0]['verification_requested_at']) ?></small>
                          <?php endif; ?>
                          <div id="verification_response" class="small mt-2"></div>
                        </div>
                      <?php endif; ?>

                      <div class="mt-4 w-100 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-back-3">Back</button>
                        <button type="button" class="btn submit_btn">Submit</button>
                      </div>
                    </div>

                </form>

                <!-- FIX 3 — Moved response div OUTSIDE all form steps so it always
                     shows regardless of which step is visible when error/success occurs -->
                <div id="response" style="margin-top:15px; padding: 0 10px;"></div>

                </div>
            </div>
        </div>

      </div>

  </section>
</div>

<script>
if (typeof Dropzone !== 'undefined') Dropzone.autoDiscover = false;
const base_url = "<?php echo base_url(); ?>";
const submitBtn = document.querySelector('.submit_btn');
const initialSection = "<?= in_array(($current_profile_section ?? 'personal'), ['personal','store','account','admin']) ? $current_profile_section : 'personal' ?>";
// ── Searchable dropdown factory ──────────────────────────────────────────────
function makeSearchable(searchId, hiddenId, dropdownId, data, onSelect) {
  const searchEl   = document.getElementById(searchId);
  const hiddenEl   = document.getElementById(hiddenId);
  const dropdownEl = document.getElementById(dropdownId);

  if (hiddenEl.value) searchEl.value = hiddenEl.value;

  function renderDropdown(items) {
    dropdownEl.innerHTML = '';
    if (!items.length) { dropdownEl.style.display = 'none'; return; }
    items.forEach(function(item) {
      const div = document.createElement('div');
      div.textContent = item.label;
      div.style.cssText = 'padding:8px 12px; cursor:pointer;';
      div.addEventListener('mouseenter', function() { this.style.background = '#f0f0f0'; });
      div.addEventListener('mouseleave', function() { this.style.background = '#fff'; });
      div.addEventListener('click', function() {
        searchEl.value  = item.label;
        hiddenEl.value  = item.label;
        dropdownEl.style.display = 'none';
        if (onSelect) onSelect(item);
      });
      dropdownEl.appendChild(div);
    });
    dropdownEl.style.display = 'block';
  }

  searchEl.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    if (!q) { dropdownEl.style.display = 'none'; hiddenEl.value = ''; return; }
    const matches = data.filter(function(item) {
      return item.label.toLowerCase().includes(q);
    });
    renderDropdown(matches);
  });

  document.addEventListener('click', function(e) {
    if (e.target !== searchEl) dropdownEl.style.display = 'none';
  });

  // expose so cascade can call it
  return { setData: function(newData, selectedLabel) {
    data = newData;
    dropdownEl.style.display = 'none';
    searchEl.value  = selectedLabel || '';
    hiddenEl.value  = selectedLabel || '';
  }};
}

// ── Bank searchable ───────────────────────────────────────────────────────────
<?php if (!empty($indian_banks)): ?>
const bankData = [
  <?php foreach ($indian_banks as $bank): ?>
  { label: "<?= addslashes($bank['bank_name']) ?>", id: "<?= addslashes($bank['bank_name']) ?>" },
  <?php endforeach; ?>
];
makeSearchable('bank_search', 'bank_name_hidden', 'bank_dropdown', bankData, null);
(function () {
  var bankSearch = document.getElementById('bank_search');
  var bankHidden = document.getElementById('bank_name_hidden');
  if (!bankSearch || !bankHidden) return;
  bankSearch.addEventListener('input', function () { bankHidden.value = this.value.trim(); });
})();
<?php endif; ?>

// ── Form validation ───────────────────────────────────────────────────────────
function clearErrors(form) {
  form.querySelectorAll('.error-msg').forEach(function(e) { e.remove(); });
  form.querySelectorAll('.is-invalid').forEach(function(i) { i.classList.remove('is-invalid'); });
}
function showError(input, message) {
  input.classList.add('is-invalid');
  const error = document.createElement('small');
  error.className = 'error-msg';
  error.style.color = 'red';
  error.innerText = message;
  input.parentElement.appendChild(error);
}
// Delegate the final (Submit) validation to the shared, comprehensive validator in
// form.js so the Submit gate enforces exactly the same rules as the Next buttons.
function validateForm3() {
  var form3 = document.querySelector('.form3');
  return (typeof validateForm === 'function') ? validateForm(form3) : true;
}

// GST enrollment toggle: ticking "We are not GST registered" swaps the GST Number
// field for a mandatory GST Enrollment Number field (these sellers are state-restricted).
// Also drives entity-type-dependent labels/uploads in Business Details (PAN label,
// Legal Business/Firm Name, GSTIN/enrollment-ack uploads, Partnership Deed, Business Proof).
(function () {
  var gstCheck = document.getElementById('gst_check');
  var entityType = document.getElementById('entity_type');
  if (!gstCheck || !entityType) return;

  var PAN_LABELS = {
    individual: 'Your PAN Number',
    sole_proprietorship: "Proprietor's PAN Number",
    partnership_firm: "Firm's PAN Number",
    pvt_ltd: "Company's PAN Number"
  };
  var LEGAL_NAME_LABELS = {
    individual: 'Legal Business Name',
    sole_proprietorship: 'Legal Business Name',
    partnership_firm: "Legal Firm's Name",
    pvt_ltd: 'Legal Business Name'
  };
  var BUSINESS_PROOF_HINTS = {
    sole_proprietorship: '(Udyam/MSME Certificate)',
    partnership_firm: '(Partnership deed, Firm PAN card, or Udyam/MSME Certificate)',
    pvt_ltd: '(Udyam/MSME Certificate or Certificate of Incorporation)'
  };

  function syncGstFields() {
    var nonGst  = gstCheck.checked;
    var numDiv  = document.getElementById('gst_number_div');
    var enrDiv  = document.getElementById('gst_enrollment_div');
    var gstIn   = numDiv ? numDiv.querySelector('input[name="gst"]') : null;
    var enrIn   = enrDiv ? enrDiv.querySelector('input[name="gst_enrollment_number"]') : null;
    if (numDiv) numDiv.style.display = nonGst ? 'none' : '';
    if (enrDiv) enrDiv.style.display = nonGst ? '' : 'none';
    if (gstIn) { nonGst ? gstIn.removeAttribute('required') : gstIn.setAttribute('required', 'required'); }
    if (enrIn) { nonGst ? enrIn.setAttribute('required', 'required') : enrIn.removeAttribute('required'); }

    var gstinField = document.getElementById('gstin_document_field');
    var ackField = document.getElementById('gst_enrollment_ack_document_field');
    if (gstinField) gstinField.style.display = nonGst ? 'none' : '';
    if (ackField) ackField.style.display = nonGst ? '' : 'none';

    updateBusinessProofVisibility();
  }

  function updateBusinessProofVisibility() {
    var type = entityType.value;
    var section = document.getElementById('business_proof_section');
    var hint = document.getElementById('business_proof_document_extra_hint');
    var visible = gstCheck.checked && type !== 'individual' && type !== '';
    if (section) section.style.display = visible ? '' : 'none';
    if (hint) {
      var text = BUSINESS_PROOF_HINTS[type] || '';
      hint.textContent = text;
      hint.classList.toggle('hidden', !text);
    }
  }

  function updateEntityTypeUI() {
    var type = entityType.value;
    var panLabelEl = document.getElementById('pan_label');
    var legalLabelEl = document.getElementById('legal_business_name_label');
    var legalTooltip = document.getElementById('legal_business_name_tooltip');
    var legalInput = document.getElementById('legal_business_name_input');
    var partnershipSection = document.getElementById('partnership_deed_section');

    if (panLabelEl) panLabelEl.innerHTML = (PAN_LABELS[type] || 'PAN Number') + '<span class="text-danger">*</span>';
    if (legalLabelEl) legalLabelEl.textContent = LEGAL_NAME_LABELS[type] || 'Legal Business Name';
    if (legalTooltip) legalTooltip.style.display = (type === 'individual') ? '' : 'none';

    // Auto-fill from First+Last Name, but only while the field is still empty OR
    // still holds an earlier auto-fill (tracked via data-autofilled) — never overwrite
    // something the seller typed in themselves. Re-runs on every name-field blur, since
    // "individual" is the default entity type and its own 'change' event never fires
    // for a first-time seller who fills their name after the page has already loaded.
    if (type === 'individual' && legalInput && (!legalInput.value.trim() || legalInput.dataset.autofilled === '1')) {
      var firstName = document.querySelector('input[name="first_name"]');
      var lastName = document.querySelector('input[name="last_name"]');
      var fullName = [firstName ? firstName.value.trim() : '', lastName ? lastName.value.trim() : ''].filter(Boolean).join(' ');
      if (fullName) {
        legalInput.value = fullName;
        legalInput.dataset.autofilled = '1';
      }
    }

    if (partnershipSection) partnershipSection.style.display = (type === 'partnership_firm') ? '' : 'none';

    updateBusinessProofVisibility();
  }

  gstCheck.addEventListener('change', syncGstFields);
  entityType.addEventListener('change', updateEntityTypeUI);
  // "individual" is already the default selection, so entity_type's own 'change'
  // event never fires for the common case of a first-time seller typing their name
  // AFTER the page loads (rather than the entity type). Re-run the auto-fill check
  // once they leave either name field too.
  var firstNameInput = document.querySelector('input[name="first_name"]');
  var lastNameInput = document.querySelector('input[name="last_name"]');
  if (firstNameInput) firstNameInput.addEventListener('blur', updateEntityTypeUI);
  if (lastNameInput) lastNameInput.addEventListener('blur', updateEntityTypeUI);
  // A genuine keystroke (not our own programmatic .value set, which never fires
  // 'input') means the seller is editing it themselves — stop auto-filling it.
  var legalNameInput = document.getElementById('legal_business_name_input');
  if (legalNameInput) legalNameInput.addEventListener('input', function () { legalNameInput.dataset.autofilled = ''; });
  syncGstFields();
  updateEntityTypeUI();
})();

function openProfileSection(section) {
  const sectionMap = { personal: 1, store: 2, account: 3, admin: 4};
  const target = sectionMap[section] || 1;

  const steps = [document.querySelector('.form1'), document.querySelector('.form2'), document.querySelector('.form3'), document.querySelector('.form4')];
  const indicators = [document.querySelector('.form-indicator-1'), document.querySelector('.form-indicator-2'), document.querySelector('.form-indicator-3'), document.querySelector('.form-indicator-4')];
  const lines = [document.querySelector('.completion-line-1'), document.querySelector('.completion-line-2'), document.querySelector('.completion-line-3')];

  steps.forEach(function(step, index) {
    step.style.left = (index + 1 === target) ? '0' : ((index + 1 < target) ? '-500%' : '500%');
  });

  indicators.forEach(function(indicator, index) {
    if (index + 1 <= target) {
      indicator.classList.add('active');
    } else {
      indicator.classList.remove('active');
    }
  });

  lines.forEach(function(line, index) {
    if (index + 2 <= target) {
      line.classList.add('active');
    } else {
      line.classList.remove('active');
    }
  });
}

openProfileSection(initialSection);


function setPincodeStatus(statusElement, message, type) {
  if (!statusElement) return;
  statusElement.textContent = message || '';
  statusElement.classList.remove('error', 'success', 'info');
  if (type) {
    statusElement.classList.add(type);
  }
}

function getFirstAvailableValue(source, keys) {
  for (const key of keys) {
    if (source && source[key]) {
      return source[key];
    }
  }
  return '';
}

function setLocationInputValue(inputId, value) {
  const input = document.getElementById(inputId);
  if (input && value) input.value = value;
}

// Auto-fill State / District / City from a 6-digit Indian pincode.
//   Primary : India Post (api.postalpincode.in) — covers EVERY Indian pincode.
//   Fallback: zippopotam.us.
// If neither resolves, the fields stay fully editable so the seller can type the
// State/District/City by hand — a valid pincode is never treated as an error.
function bindPincodeAutofill(options) {
  const pinInput = document.getElementById(options.pinId);
  const statusElement = document.getElementById(options.statusId);
  let lookupTimer = null;
  let latestPincode = '';

  if (!pinInput) return;

  function firstMeaningful() {
    for (let i = 0; i < arguments.length; i++) {
      const v = (arguments[i] || '').toString().trim();
      if (v && !/^(na|nil|none)$/i.test(v)) return v;
    }
    return '';
  }

  function applyLocation(loc) {
    setLocationInputValue(options.stateInputId, loc.state);
    setLocationInputValue(options.districtInputId, loc.district);
    setLocationInputValue(options.cityInputId, loc.city);
  }

  // 1) India Post — full coverage of Indian pincodes.
  function lookupIndiaPost(pincode) {
    return fetch('https://api.postalpincode.in/pincode/' + encodeURIComponent(pincode))
      .then(function(r) { if (!r.ok) throw new Error('http'); return r.json(); })
      .then(function(data) {
        const rec = Array.isArray(data) ? data[0] : null;
        const offices = (rec && Array.isArray(rec.PostOffice)) ? rec.PostOffice : [];
        if (!rec || rec.Status !== 'Success' || !offices.length) throw new Error('not found');
        const po = offices[0];
        return {
          state: po.State || '',
          district: po.District || '',
          city: firstMeaningful(po.Block, po.Name, po.District)
        };
      });
  }

  // 2) zippopotam — fallback if India Post is unreachable.
  function lookupZippopotam(pincode) {
    return fetch('https://api.zippopotam.us/in/' + encodeURIComponent(pincode))
      .then(function(r) { if (!r.ok) throw new Error('http'); return r.json(); })
      .then(function(data) {
        const place = (Array.isArray(data.places) ? data.places : [])[0] || {};
        const placeName = getFirstAvailableValue(place, ['place name', 'place_name', 'city', 'town', 'locality']);
        const state = getFirstAvailableValue(place, ['state', 'state name', 'state_name']);
        const district = getFirstAvailableValue(place, ['district', 'district name', 'district_name', 'county', 'region']) || placeName;
        if (!placeName && !state && !district) throw new Error('empty');
        return { state: state, district: district, city: placeName };
      });
  }

  function fillAddressFromPincode() {
    const pincode = pinInput.value.replace(/\D/g, '').slice(0, 6);
    pinInput.value = pincode;

    if (pincode.length < 6) {
      latestPincode = '';
      setPincodeStatus(statusElement, '', '');
      return;
    }

    latestPincode = pincode;
    setPincodeStatus(statusElement, 'Fetching state, district and city…', 'info');

    lookupIndiaPost(pincode)
      .catch(function() { return lookupZippopotam(pincode); })
      .then(function(loc) {
        if (latestPincode !== pincode) return;
        applyLocation(loc);
        setPincodeStatus(statusElement, 'State, district and city filled from pincode. You can edit them if needed.', 'success');
      })
      .catch(function() {
        if (latestPincode !== pincode) return;
        // Not found in either source — NOT an error; let the seller type it in.
        setPincodeStatus(statusElement, 'Could not auto-detect this pincode. Please enter State, District and City manually.', 'info');
      });
  }

  pinInput.addEventListener('input', function() {
    clearTimeout(lookupTimer);
    lookupTimer = setTimeout(fillAddressFromPincode, 400);
  });
  pinInput.addEventListener('blur', fillAddressFromPincode);
}

bindPincodeAutofill({
  pinId: 'pin',
  stateInputId: 'state',
  districtInputId: 'district',
  cityInputId: 'city',
  statusId: 'pin_lookup_status'
});

bindPincodeAutofill({
  pinId: 'pickup_pin',
  stateInputId: 'pickup_state',
  districtInputId: 'pickup_district',
  cityInputId: 'pickup_city',
  statusId: 'pickup_pin_lookup_status'
});

bindPincodeAutofill({
  pinId: 'business_pin',
  stateInputId: 'business_state',
  districtInputId: 'business_district',
  cityInputId: 'business_city',
  statusId: 'business_pin_lookup_status'
});

// ── Personal photo preview (icon <-> photo swap, mirrors the Shop Logo widget on step 2) ──────
(function () {
  var input = document.getElementById('personalPhotoInput');
  var container = document.getElementById('personalPhotoContainer');
  var preview = document.getElementById('personalPhotoPreview');
  var icon = document.getElementById('personalPhotoIcon');
  if (!input || !preview) return;
  if (container) container.addEventListener('click', function () { input.click(); });
  input.addEventListener('change', function () {
    var file = this.files[0];
    if (file && file.type.startsWith('image/')) {
      var reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        if (icon) icon.style.display = 'none';
      };
      reader.readAsDataURL(file);
    }
  });
})();

// ── Identity Proof / Authorized Signatory thumbnail preview + remove ──────────
function bindDocPreview(fieldName) {
  var input = document.getElementById(fieldName + '_input');
  var preview = document.getElementById(fieldName + '_preview');
  var wrap = document.getElementById(fieldName + '_wrap');
  var link = document.getElementById(fieldName + '_link');
  var hint = document.getElementById(fieldName + '_hint');
  if (!input || !preview) return;
  input.addEventListener('change', function () {
    var file = this.files[0];
    if (file && file.type.startsWith('image/')) {
      var reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        if (link) link.href = e.target.result;
        if (wrap) wrap.classList.remove('hidden');
        if (hint) hint.style.display = 'none';
      };
      reader.readAsDataURL(file);
    }
  });
}
function bindDocRemove(fieldName) {
  var btn = document.querySelector('.doc-remove-btn[data-target="' + fieldName + '"]');
  var input = document.getElementById(fieldName + '_input');
  var oldHidden = document.querySelector('input[name="old_' + fieldName + '"]');
  var wrap = document.getElementById(fieldName + '_wrap');
  var hint = document.getElementById(fieldName + '_hint');
  if (!btn) return;
  btn.addEventListener('click', function () {
    if (!confirm('Remove this file? You will need to upload a new one to replace it.')) return;
    if (input) input.value = '';
    if (oldHidden) oldHidden.value = '';
    if (wrap) wrap.classList.add('hidden');
    if (hint) hint.style.display = 'none';
  });
}
['national_identity_card', 'authorized_signature'].forEach(function (fieldName) {
  bindDocPreview(fieldName);
  bindDocRemove(fieldName);
});

// ── Business Details document preview (images get a thumbnail; a PDF can't go
//    in an <img src>, so it shows no preview — just the "on record" hint below
//    and the × to remove it) ───────────────────────────────────────────────────
function bindDocPreviewFlexible(fieldName) {
  var input = document.getElementById(fieldName + '_input');
  var preview = document.getElementById(fieldName + '_preview');
  var wrap = document.getElementById(fieldName + '_wrap');
  var link = document.getElementById(fieldName + '_link');
  var hint = document.getElementById(fieldName + '_hint');
  if (!input || !preview) return;
  input.addEventListener('change', function () {
    var file = this.files[0];
    if (!file) return;
    if (file.type === 'application/pdf') {
      preview.classList.add('hidden');
      if (link) link.removeAttribute('href');
      if (wrap) wrap.classList.remove('hidden');
      if (hint) hint.style.display = 'none';
    } else if (file.type.startsWith('image/')) {
      var reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        if (link) link.href = e.target.result;
        if (wrap) wrap.classList.remove('hidden');
        if (hint) hint.style.display = 'none';
      };
      reader.readAsDataURL(file);
    }
  });
}
[
  'pan_card_document', 'gstin_document', 'gst_enrollment_ack_document',
  'business_proof_document', 'business_address_proof_document',
  'partnership_deed_document', 'bank_account_proof_document'
].forEach(function (fieldName) {
  bindDocPreviewFlexible(fieldName);
  bindDocRemove(fieldName);
});

// ── Secondary Categories picker (pill list + modal, checkboxes are UI-only —
//    the checked state is synced into the hidden secondary_category_ids input
//    that actually gets submitted). Scoped to sub-categories of whichever
//    Primary Product Category is currently selected. ─────────────────────────
(function () {
  var modal = document.getElementById('category_picker_modal');
  var openBtn = document.getElementById('open_category_picker_btn');
  var closeBtn = document.getElementById('close_category_picker_btn');
  var cancelBtn = document.getElementById('cancel_category_picker_btn');
  var doneBtn = document.getElementById('done_category_picker_btn');
  var hiddenInput = document.getElementById('secondary_category_ids_hidden');
  var pillsContainer = document.getElementById('secondary_category_pills');
  var primarySelect = document.getElementById('primary_category_id');
  var emptyMsg = document.getElementById('category_picker_empty_msg');
  if (!modal || !hiddenInput || !pillsContainer || !primarySelect) return;
  var items = Array.prototype.slice.call(document.querySelectorAll('.category-picker-item'));
  var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.secondary-category-checkbox'));

  var categoryNames = {};
  var categoryParent = {};
  items.forEach(function (item) {
    var cb = item.querySelector('.secondary-category-checkbox');
    var label = item.querySelector('span');
    if (!cb) return;
    categoryNames[cb.value] = label ? label.textContent : cb.value;
    categoryParent[cb.value] = item.getAttribute('data-parent');
  });

  function getSelectedIds() {
    return hiddenInput.value ? hiddenInput.value.split(',').filter(function (v) { return v !== ''; }) : [];
  }

  function renderPills() {
    var ids = getSelectedIds();
    pillsContainer.innerHTML = '';
    ids.forEach(function (id) {
      var pill = document.createElement('span');
      pill.className = 'category-pill';
      pill.textContent = categoryNames[id] || id;
      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.innerHTML = '&times;';
      removeBtn.setAttribute('aria-label', 'Remove category');
      removeBtn.addEventListener('click', function () {
        hiddenInput.value = getSelectedIds().filter(function (x) { return x !== id; }).join(',');
        syncCheckboxes();
        renderPills();
      });
      pill.appendChild(removeBtn);
      pillsContainer.appendChild(pill);
    });
  }

  function syncCheckboxes() {
    var ids = getSelectedIds();
    checkboxes.forEach(function (cb) { cb.checked = ids.indexOf(cb.value) !== -1; });
  }

  // Show only the sub-categories that belong to the currently selected Primary
  // Product Category; everything else stays in the DOM (so ids/labels are still
  // known) but hidden.
  function filterByPrimary() {
    var primaryId = primarySelect.value;
    var anyVisible = false;
    items.forEach(function (item) {
      var visible = !!primaryId && item.getAttribute('data-parent') === primaryId;
      item.style.display = visible ? '' : 'none';
      if (visible) anyVisible = true;
    });
    if (emptyMsg) emptyMsg.style.display = anyVisible ? 'none' : '';
    if (openBtn) {
      openBtn.disabled = !primaryId;
      openBtn.style.opacity = primaryId ? '1' : '0.5';
      openBtn.style.cursor = primaryId ? 'pointer' : 'not-allowed';
    }
  }

  // Changing the primary category invalidates any already-picked secondary
  // categories that no longer belong to it — drop them rather than silently
  // keeping a mismatched selection around.
  function pruneSelectionsOutsidePrimary() {
    var primaryId = primarySelect.value;
    var kept = getSelectedIds().filter(function (id) { return categoryParent[id] === primaryId; });
    hiddenInput.value = kept.join(',');
    renderPills();
  }

  function openModal() {
    if (primarySelect.value === '') return;
    syncCheckboxes();
    filterByPrimary();
    modal.style.display = 'flex';
  }
  function closeModal() { modal.style.display = 'none'; }
  function applyAndClose() {
    var ids = checkboxes.filter(function (cb) { return cb.checked && cb.closest('.category-picker-item').style.display !== 'none'; }).map(function (cb) { return cb.value; });
    hiddenInput.value = ids.join(',');
    renderPills();
    modal.style.display = 'none';
  }

  if (openBtn) openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
  if (doneBtn) doneBtn.addEventListener('click', applyAndClose);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
  primarySelect.addEventListener('change', function () {
    pruneSelectionsOutsidePrimary();
    filterByPrimary();
  });

  // Secondary categories only make sense once a Primary Product Category is
  // chosen — if this loaded with none selected, don't show (or silently
  // resubmit) whatever was left in category_ids from before that concept
  // existed.
  if (primarySelect.value === '') {
    hiddenInput.value = '';
  }
  filterByPrimary();
  renderPills();
})();

// Store URL placeholder mirrors the Shop Name field live, since the server
// auto-generates the slug from it when the seller leaves Store URL blank.
(function () {
  var shopNameInput = document.querySelector('input[name="shop_name"]');
  var slugInput = document.getElementById('slug_input');
  if (!shopNameInput || !slugInput) return;
  shopNameInput.addEventListener('input', function () {
    slugInput.placeholder = shopNameInput.value.trim() || 'your-shop-name';
  });
})();

// ── Submit ────────────────────────────────────────────────────────────────────
submitBtn.addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('response').innerHTML = '';
  if (!validateForm3()) return;
  const formData = new FormData(document.getElementById('seller_form'));
  submitBtn.disabled = true;
  submitBtn.innerText = 'Submitting...';
  fetch("<?php echo base_url('seller/login/update_user') ?>", { method: 'POST', body: formData })
    .then(function(res) {
      return res.text().then(function(text) {
        try { return JSON.parse(text.replace(/<!--[\s\S]*?-->/g, '').trim()); }
        catch(e) { console.error('Server returned non-JSON:', text); throw new Error('Invalid JSON response'); }
      });
    })
    .then(function(data) {
      submitBtn.disabled = false; submitBtn.innerText = 'Submit';
      const toast = document.getElementById('toast-msg');
      if (data.error == false) {
        toast.style.cssText = 'display:block; background:#d4edda; color:#155724; border:1px solid #c3e6cb;';
        toast.innerText = '✅ Updated successfully! Redirecting...';
        setTimeout(function() { window.location.href = base_url + 'seller/home'; }, 2000);
        return;
      }
      toast.style.cssText = 'display:block; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;';
      toast.innerText = '❌ ' + data.message;
      setTimeout(function() { toast.style.display = 'none'; }, 5000);
    })
    .catch(function(err) {
      submitBtn.disabled = false; submitBtn.innerText = 'Submit';
      const toast = document.getElementById('toast-msg');
      toast.style.cssText = 'display:block; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;';
      toast.innerText = '❌ Something went wrong. Please try again.';
      setTimeout(function() { toast.style.display = 'none'; }, 5000);
      console.error('Submit error:', err);
    });
});


const requestVerificationBtn = document.getElementById('request_verification_btn');
if (requestVerificationBtn) {
  requestVerificationBtn.addEventListener('click', function () {
    const responseBox = document.getElementById('verification_response');
    if (responseBox) {
      responseBox.className = 'small mt-2 text-muted';
      responseBox.innerText = 'Submitting verification request...';
    }
    const verificationNote = document.getElementById('verification_note');
    if (!verificationNote || verificationNote.value.trim().length < 10) {
      const toast = document.getElementById('toast-msg');
      toast.style.cssText = 'display:block; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;';
      toast.innerText = '❌ Verification note must be at least 10 characters.';
      setTimeout(function () { toast.style.display = 'none'; }, 5000);
      if (responseBox) {
        responseBox.className = 'small mt-2 text-danger';
        responseBox.innerText = 'Verification note must be at least 10 characters.';
      }
      return;
    }
    const verificationData = new FormData();
    verificationData.append('verification_note', verificationNote.value.trim());
    requestVerificationBtn.disabled = true;
    fetch(base_url + 'seller/home/request_admin_verification', {
      method: 'POST',
      body: verificationData
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        const toast = document.getElementById('toast-msg');
        if (data.error) {
          toast.style.cssText = 'display:block; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;';
          toast.innerText = '❌ ' + (data.message || 'Unable to submit request.');
          if (responseBox) {
            responseBox.className = 'small mt-2 text-danger';
            responseBox.innerText = data.message || 'Unable to submit request.';
          }
        } else {
          toast.style.cssText = 'display:block; background:#d4edda; color:#155724; border:1px solid #c3e6cb;';
          toast.innerText = '✅ ' + data.message;
          if (responseBox) {
            responseBox.className = 'small mt-2 text-success';
            responseBox.innerText = data.message;
          }
        }
        requestVerificationBtn.disabled = false;
        setTimeout(function () { toast.style.display = 'none'; }, 5000);
      })
      .catch(function () {
        const toast = document.getElementById('toast-msg');
        toast.style.cssText = 'display:block; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;';
        toast.innerText = '❌ Unable to submit verification request.';
        if (responseBox) {
          responseBox.className = 'small mt-2 text-danger';
          responseBox.innerText = 'Unable to submit verification request.';
        }
        requestVerificationBtn.disabled = false;
        setTimeout(function () { toast.style.display = 'none'; }, 5000);
      });
  });
}
</script>


  <script src="<?= base_url('assets/seller/js/cretzo/form.js') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/js/cretzo/form.js') ?: time() ?>"></script>

</body>
</html>