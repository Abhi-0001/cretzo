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
                                          <p class="text-n text-capitalize">account details</p>
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
                      <div class="row gap-xl-5">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">First Name <span class="text-danger">*</span></label>
                          <input name="first_name" type="text" class="input" placeholder="First name" value="<?=$fetched_data[0]['first_name']?>" minlength="3" maxlength="50" required>
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
                        
                      </div>

                        <div class="text-center mt-3">
                          <button type="button" class="btn btn-next-1">Next</button>
                        </div>
                    </div>

                    <div class="form-step form2">
                        <div>
                          <div class="photo-upload d-flex gap-4 justify-content-between align-items-center mb-3">
                            <input type="file" class="hidden" name="store_logo" id="photoInput" accept="image/*">
                            <div class="preview-container">
                              <svg class="profile-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                              </svg>
                              <img id="photoPreview" src="" class="shop-logo hidden">
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
                        
                      </div>

                      <div class="mt-3 w-100 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-back-1">Back</button>
                        <button type="button" class="btn btn-next-2">Next</button>
                      </div>

                    </div>

                    <div class="form-step form3">

                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Entity Type <span class="text-danger">*</span></label>
                          <select name="entity_type" class="input" id="entity_type">
                            <option value="individual">Individual</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="partnership_firm">Partnership Firm</option>
                            <option value="pvt_ltd">Pvt Ltd.</option>
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PAN Number<span class="text-danger">*</span></label>
                          <input name="pan" type="text" maxlength="10" class="input" placeholder="Enter PAN Number" value="<?=$fetched_data[0]['pan']?>" required>
                        </div>
                        <?php
                          $is_gst_registered = isset($fetched_data[0]['is_gst_registered']) ? $fetched_data[0]['is_gst_registered'] : 1;
                          $gst_enrollment_number = isset($fetched_data[0]['gst_enrollment_number']) ? $fetched_data[0]['gst_enrollment_number'] : '';
                          $is_non_gst = ($is_gst_registered == 0);
                        ?>
                        <div class="col-md-6 mb-3" id="gst_number_div" style="<?= $is_non_gst ? 'display:none;' : '' ?>">
                          <label class="form-label">GST Number <span class="text-danger">*</span></label>
                          <input name="gst" type="text" maxlength="15" class="input" placeholder="22ABCDE0000A1Z5" value="<?=$fetched_data[0]['gst']?>" <?= $is_non_gst ? '' : 'required' ?>>
                        </div>
                        <div class="col-md-6 mb-3" id="gst_enrollment_div" style="<?= $is_non_gst ? '' : 'display:none;' ?>">
                          <label class="form-label">GST Enrollment Number <span class="text-danger">*</span></label>
                          <input name="gst_enrollment_number" type="text" maxlength="64" class="input" placeholder="Enter GST Enrollment Number" value="<?= html_escape($gst_enrollment_number) ?>" <?= $is_non_gst ? 'required' : '' ?>>
                          <small class="text-muted d-block mt-1">You can sell only within your own state (as per government regulation).</small>
                        </div>
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
(function () {
  var gstCheck = document.getElementById('gst_check');
  if (!gstCheck) return;
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
  }
  gstCheck.addEventListener('change', syncGstFields);
  syncGstFields();
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


  <script src="<?= base_url('assets/seller/js/cretzo/form.js') ?>"></script>

</body>
</html>