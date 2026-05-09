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
</style>
</head>
<body>
<div id="toast-msg"></div>
  
  <section class="content w-100 seller-form">
      <div class="container-fluid">
        <div class="form-parent">
          <div class="form-container-main" >

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
                          <input name="first_name" type="text" class="input" placeholder="First name" value="<?=$fetched_data[0]['first_name']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Last Name <span class="text-danger">*</span></label>
                          <input name="last_name" type="text" class="input" placeholder="Last Name" value="<?=$fetched_data[0]['last_name']?>" required>
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
                          <label class="form-label">&nbsp;</label>
                          <input name="address2" type="text" class="input" placeholder="Street 2" value="<?=$fetched_data[0]['address2']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State <span class="text-danger">*</span></label>
                          <!-- GET STATES BY FILTERING THE NAME -->
                          <div style="position:relative;">
                            <input type="text" id="state_search" class="input" placeholder="Search State..." autocomplete="off">
                            <input type="hidden" name="state" id="state_hidden" required value="<?= $fetched_data[0]['state'] ?>">
                            <div id="state_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <!-- GET DISTRICTS BY STATES RESPECTIVE -->
                          <label class="form-label">District <span class="text-danger">*</span></label>
                          <div style="position:relative;">
                            <input type="text" id="district_search" class="input" placeholder="Search District..." autocomplete="off">
                            <input type="hidden" name="district" id="district_hidden" required value="<?= $fetched_data[0]['district'] ?>">
                            <div id="district_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City/Village/Town <span class="text-danger">*</span></label>
                          <!-- GET CITIES BY STATES AND DISTRICT RESPECTIVE -->
                          <div style="position:relative;">
                            <input type="text" id="city_search" class="input" placeholder="Search City..." autocomplete="off">
                            <input type="hidden" name="city" id="city_hidden" required value="<?= $fetched_data[0]['city'] ?>">
                            <div id="city_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                          <input name="pin" type="text" class="input" placeholder="Enter PIN Code" value="<?=$fetched_data[0]['pin']?>" required maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
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
                              <img id="photoPreview" src="" class="shop-logo hidden" style="margin-top: 1rem;">
                            </div>
                            <label for="photoInput" class="btn btn-sm btn-outline-secondary" style="cursor:pointer;">
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
                          <label class="form-label">Social Media Handle <span class="text-danger">*</span></label>
                          <input name="social" type="text" class="input" placeholder="Enter Social Media" value="<?=$fetched_data[0]['social']?>" required>
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
                          <label class="form-label">State</label>
                          <div style="position:relative;">
                            <input type="text" id="pickup_state_search" class="input" placeholder="Search State..." autocomplete="off">
                            <input type="hidden" name="pickup_state" id="pickup_state_hidden" value="<?= $fetched_data[0]['pickup_state'] ?>">
                            <div id="pickup_state_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">District</label>
                          <div style="position:relative;">
                            <input type="text" id="pickup_district_search" class="input" placeholder="Search District..." autocomplete="off">
                            <input type="hidden" name="pickup_district" id="pickup_district_hidden" value="<?= $fetched_data[0]['pickup_district'] ?>">
                            <div id="pickup_district_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City</label>
                          <div style="position:relative;">
                            <input type="text" id="pickup_city_search" class="input" placeholder="Search City..." autocomplete="off">
                            <input type="hidden" name="pickup_city" id="pickup_city_hidden" value="<?= $fetched_data[0]['pickup_city'] ?>">
                            <div id="pickup_city_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code</label>
                          <input name="pickup_pin" type="text" class="input" placeholder="Enter PIN Code" value="<?=$fetched_data[0]['pickup_pin']?>" maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-12 mb-3">
                          <label class="form-label">Store Categories <span class="text-danger">*</span></label>
                          <input type="hidden" name="category_ids" id="category_ids_hidden" value="<?= htmlspecialchars($fetched_data[0]['category_ids'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                          <button type="button" class="btn btn-outline-primary btn-sm" id="open_category_picker">Select Categories</button>
                          <div id="selected_categories_display" class="mt-2"></div>
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
                          <!-- BUG FIX #6 START — fixed name= to value= so selected option POSTs correctly to backend -->
                          <select name="entity_type" class="input" id="entity_type">
                            <option value="individual">Individual</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="partnership_firm">Partnership Firm</option>
                            <option value="pvt_ltd">Pvt Ltd.</option>
                          </select>
                          <!-- BUG FIX #6 END -->
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PAN Number<span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 START — maxlength added to enforce 10 character PAN format at browser level -->
                          <input name="pan" type="text" maxlength="10" class="input" placeholder="Enter PAN Number" value="<?=$fetched_data[0]['pan']?>" required>
                          <!-- BUG FIX #6 END -->
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">GST Number <span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 START — maxlength added to enforce 15 character GST format at browser level -->
                          <input name="gst" type="text" maxlength="15" class="input" placeholder="22ABCDE0000A1Z5" value="<?=$fetched_data[0]['gst']?>" required>
                          <!-- BUG FIX #6 END -->
                        </div>
                      </div>

                      <h3>Declaration</h3>
                      <div class="d-flex flex-column justify-content-between align-items-start">
                          <div id="entity_check_div">
                              <input type="checkbox" id="entity_check" class="check-input">
                              <label for="entity_check">We are not a registered Entity.</label>
                          </div>
                          <div>
                              <input type="checkbox" id="gst_check" class="check-input">
                              <label for="gst_check">We are not GST registered.</label>
                          </div>
                      </div>
                      
                      <h3>Account Details</h3>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Account Number<span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 START — maxlength added to enforce max 18 digit account number at browser level -->
                          <input name="account_number" type="text" class="input" maxlength="18" placeholder="Enter your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                          <!-- BUG FIX #6 END -->
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Confirm Account Number<span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 START — maxlength added to match account number limit -->
                          <input name="confirm_account_number" type="text" class="input" maxlength="18" placeholder="Confirm your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                          <!-- BUG FIX #6 END -->
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Account Holder name<span class="text-danger">*</span></label>
                          <input name="account_holder_name" type="text" class="input" placeholder="Enter  the Account Holder's name" value="<?=$fetched_data[0]['account_holder_name']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">IFSC Code<span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 START — maxlength added to enforce exact 11 character IFSC format at browser level -->
                          <input name="ifsc" type="text" class="input" placeholder="Enter IFSC Code" maxlength="11" value="<?=$fetched_data[0]['ifsc']?>" required>
                          <!-- BUG FIX #6 END -->
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                          <input name="branch" type="text" class="input" placeholder="Enter Branch" value="<?=$fetched_data[0]['branch']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" id="bank_search" class="input" placeholder="Search Bank Name..." autocomplete="off">
                            <input type="hidden" name="bank_name" id="bank_name_hidden" required value="<?= $fetched_data[0]['bank_name'] ?>">
                            <div id="bank_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                        </div>
                      </div>

                      <div class="mt-3 w-100 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-back-2">Back</button>
                        <!-- FIX 2 — Changed type="submit" to type="button" to prevent default form submit
                             which was bypassing our fetch() handler -->
                        <button type="button" class="btn-next-3">Next</button>
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
  <div class="category-picker-modal" id="category_picker_modal">
    <div class="category-picker-content">
      <div class="category-picker-header">
        <strong>Select Store Categories</strong>
        <input type="text" id="category_picker_search" class="input mt-2" placeholder="Search categories...">
      </div>
      <div class="category-picker-list" id="category_picker_list"></div>
      <div class="category-picker-footer">
        <button type="button" class="btn btn-light btn-sm" id="close_category_picker">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="apply_category_picker">Apply Selection</button>
      </div>
    </div>
  </div>
<script>
if (typeof Dropzone !== 'undefined') Dropzone.autoDiscover = false;
const base_url = "<?php echo base_url(); ?>";
const submitBtn = document.querySelector('.submit_btn');
const initialSection = "<?= in_array(($current_profile_section ?? 'personal'), ['personal','store','account','admin']) ? $current_profile_section : 'personal' ?>";
const availableCategories = [
  <?php foreach (($all_categories ?? []) as $cat): ?>
  { id: "<?= (int)$cat['id'] ?>", label: "<?= addslashes($cat['name']) ?>" },
  <?php endforeach; ?>
];
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

// ── State data from PHP ───────────────────────────────────────────────────────
const stateData = [
  <?php foreach ($states as $s): ?>
  { label: "<?= addslashes($s['name']) ?>", id: "<?= $s['id'] ?>" },
  <?php endforeach; ?>
];

// ── District + City controllers (filled by cascade) ──────────────────────────
let districtController, cityController;

// ── Wire up state searchable ──────────────────────────────────────────────────
makeSearchable('state_search', 'state_hidden', 'state_dropdown', stateData, function(item) {
  // State selected → load districts
  districtController.setData([], '');
  cityController.setData([], '');
  fetch(base_url + 'seller/auth/get_districts_by_state?state_id=' + item.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      districtController.setData(distData, '');
    })
    .catch(function(err) { console.error('Districts failed:', err); });
});
function resolveStateByLabel(label) {
  if (!label) return null;
  const normalized = label.trim().toLowerCase();
  return stateData.find(function(s) { return String(s.label).trim().toLowerCase() === normalized; }) || null;
}

function loadDistrictsForCurrentState() {
  const stateLabel = document.getElementById('state_hidden').value || document.getElementById('state_search').value;
  const stateItem = resolveStateByLabel(stateLabel);
  if (!stateItem) return;
  document.getElementById('state_hidden').value = stateItem.label;
  fetch(base_url + 'seller/auth/get_districts_by_state?state_id=' + stateItem.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      districtController.setData(distData, '');
    })
    .catch(function(err) { console.error('Districts failed:', err); });
}

// ── Wire up district searchable ───────────────────────────────────────────────
districtController = makeSearchable('district_search', 'district_hidden', 'district_dropdown', [], function(item) {
  // District selected → load cities
  cityController.setData([], '');
  const stateId = document.getElementById('state_hidden').value
    ? stateData.find(function(s) { return s.label === document.getElementById('state_hidden').value; })
    : null;
  if (!stateId) return;
  fetch(base_url + 'seller/auth/get_cities_by_district?state_id=' + stateId.id + '&district_id=' + item.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const cityData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      cityController.setData(cityData, '');
    })
    .catch(function(err) { console.error('Cities failed:', err); });
});

// ── Wire up city searchable ───────────────────────────────────────────────────
cityController = makeSearchable('city_search', 'city_hidden', 'city_dropdown', [], null);

// ── On page load: prefill districts and cities for existing saved values ──────
(function() {
  const savedState    = "<?= addslashes($fetched_data[0]['state']) ?>";
  const savedDistrict = "<?= addslashes($fetched_data[0]['district']) ?>";
  const savedCity     = "<?= addslashes($fetched_data[0]['city']) ?>";
  if (!savedState) return;
  const stateItem = stateData.find(function(s) { return s.label === savedState; });
  if (!stateItem) return;
  fetch(base_url + 'seller/auth/get_districts_by_state?state_id=' + stateItem.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      districtController.setData(distData, savedDistrict);
      if (!savedDistrict) return;
      const distItem = distData.find(function(d) { return d.label === savedDistrict; });
      if (!distItem) return;
      fetch(base_url + 'seller/auth/get_cities_by_district?state_id=' + stateItem.id + '&district_id=' + distItem.id)
        .then(function(r) { return r.json(); })
        .then(function(cityRows) {
          const cityData = cityRows.map(function(r) { return { label: r.name, id: r.id }; });
          cityController.setData(cityData, savedCity);
        });
    });
})();
// ── Pickup State / District / City ────────────────────────────────────────────
let pickupDistrictController, pickupCityController;

makeSearchable('pickup_state_search', 'pickup_state_hidden', 'pickup_state_dropdown', stateData, function(item) {
  pickupDistrictController.setData([], '');
  pickupCityController.setData([], '');
  fetch(base_url + 'seller/auth/get_districts_by_state?state_id=' + item.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      pickupDistrictController.setData(distData, '');
    })
    .catch(function(err) { console.error('Pickup districts failed:', err); });
});

pickupDistrictController = makeSearchable('pickup_district_search', 'pickup_district_hidden', 'pickup_district_dropdown', [], function(item) {
  pickupCityController.setData([], '');
  const stateLabel = document.getElementById('pickup_state_hidden').value;
  const stateItem  = stateData.find(function(s) { return s.label === stateLabel; });
  if (!stateItem) return;
  fetch(base_url + 'seller/auth/get_cities_by_district?state_id=' + stateItem.id + '&district_id=' + item.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const cityData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      pickupCityController.setData(cityData, '');
    })
    .catch(function(err) { console.error('Pickup cities failed:', err); });
});

pickupCityController = makeSearchable('pickup_city_search', 'pickup_city_hidden', 'pickup_city_dropdown', [], null);

// ── Prefill pickup dropdowns on page load ─────────────────────────────────────
(function() {
  const savedState    = "<?= addslashes($fetched_data[0]['pickup_state']) ?>";
  const savedDistrict = "<?= addslashes($fetched_data[0]['pickup_district']) ?>";
  const savedCity     = "<?= addslashes($fetched_data[0]['pickup_city']) ?>";
  if (!savedState) return;
  const stateItem = stateData.find(function(s) { return s.label === savedState; });
  if (!stateItem) return;
  fetch(base_url + 'seller/auth/get_districts_by_state?state_id=' + stateItem.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      pickupDistrictController.setData(distData, savedDistrict);
      if (!savedDistrict) return;
      const distItem = distData.find(function(d) { return d.label === savedDistrict; });
      if (!distItem) return;
      fetch(base_url + 'seller/auth/get_cities_by_district?state_id=' + stateItem.id + '&district_id=' + distItem.id)
        .then(function(r) { return r.json(); })
        .then(function(cityRows) {
          const cityData = cityRows.map(function(r) { return { label: r.name, id: r.id }; });
          pickupCityController.setData(cityData, savedCity);
        });
    });
})();

// ── Bank searchable ───────────────────────────────────────────────────────────
const bankData = [
  <?php foreach ($indian_banks as $bank): ?>
  { label: "<?= addslashes($bank['bank_name']) ?>", id: "<?= addslashes($bank['bank_name']) ?>" },
  <?php endforeach; ?>
];
makeSearchable('bank_search', 'bank_name_hidden', 'bank_dropdown', bankData, null);

// ── Category multi-select picker ─────────────────────────────────────────────
const categoryHiddenInput = document.getElementById('category_ids_hidden');
const selectedCategoriesDisplay = document.getElementById('selected_categories_display');
const categoryModal = document.getElementById('category_picker_modal');
const categoryList = document.getElementById('category_picker_list');
const categorySearch = document.getElementById('category_picker_search');
const selectedCategoryIds = new Set(
  (categoryHiddenInput.value || '')
    .split(',')
    .map(function(v) { return v.trim(); })
    .filter(Boolean)
);

function renderSelectedCategories() {
  const labels = availableCategories
    .filter(function(cat) { return selectedCategoryIds.has(String(cat.id)); })
    .map(function(cat) { return cat.label; });
  categoryHiddenInput.value = Array.from(selectedCategoryIds).join(',');
  if (!labels.length) {
    selectedCategoriesDisplay.innerHTML = '<small class="text-muted">No categories selected.</small>';
    return;
  }
  selectedCategoriesDisplay.innerHTML = labels.map(function(label) {
    return '<span class="category-pill">' + label + '</span>';
  }).join('');
}

function renderCategoryPickerList(query) {
  const q = (query || '').toLowerCase();
  const filtered = availableCategories.filter(function(cat) {
    return cat.label.toLowerCase().includes(q);
  });
  if (!filtered.length) {
    categoryList.innerHTML = '<small class="text-muted">No categories found.</small>';
    return;
  }
  categoryList.innerHTML = filtered.map(function(cat) {
    const checked = selectedCategoryIds.has(String(cat.id)) ? 'checked' : '';
    return '<label class="category-picker-item">' +
      '<input type="checkbox" class="category-picker-checkbox" value="' + cat.id + '" ' + checked + '>' +
      '<span>' + cat.label + '</span>' +
      '</label>';
  }).join('');
}

document.getElementById('open_category_picker').addEventListener('click', function() {
  renderCategoryPickerList('');
  categorySearch.value = '';
  categoryModal.style.display = 'flex';
});
document.getElementById('close_category_picker').addEventListener('click', function() {
  categoryModal.style.display = 'none';
});
document.getElementById('apply_category_picker').addEventListener('click', function() {
  renderSelectedCategories();
  categoryModal.style.display = 'none';
});
categoryList.addEventListener('change', function(e) {
  if (!e.target.classList.contains('category-picker-checkbox')) return;
  var id = String(e.target.value);
  if (e.target.checked) selectedCategoryIds.add(id);
  else selectedCategoryIds.delete(id);
});
categorySearch.addEventListener('input', function() {
  renderCategoryPickerList(this.value);
});
categoryModal.addEventListener('click', function(e) {
  if (e.target === categoryModal) categoryModal.style.display = 'none';
});
renderSelectedCategories();



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
function validateForm3() {
  const form3 = document.querySelector('.form3');
  clearErrors(form3);
  let valid = true;
  form3.querySelectorAll('input[required], select[required]').forEach(function(input) {
    if (!input.value.trim()) { showError(input, 'This field is required'); valid = false; return; }
    if (input.name === 'ifsc' && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(input.value.toUpperCase())) {
      showError(input, 'Invalid IFSC Code. Example: SBIN0001234'); valid = false;
    }
    if (input.name === 'pan' && !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(input.value.toUpperCase())) {
      showError(input, 'Invalid PAN. Example: ABCDE1234F'); valid = false;
    }
    if (input.name === 'gst' && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(input.value.toUpperCase())) {
      showError(input, 'Invalid GST. Example: 22ABCDE0000A1Z5'); valid = false;
    }
  });
  const acc  = form3.querySelector('[name="account_number"]');
  const conf = form3.querySelector('[name="confirm_account_number"]');
  if (acc && conf && acc.value !== conf.value) { showError(conf, 'Account numbers do not match'); valid = false; }
  return valid;
}

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
        setTimeout(function() { window.location.href = base_url + 'seller/auth'; }, 2000);
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
    fetch(base_url + 'seller/auth/request_admin_verification', {
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
