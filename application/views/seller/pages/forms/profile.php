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
</style>
</head>
<body>
<div id="toast-msg"></div>
  
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
                          <label for="photoInput">Shop Logo</label>
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

<script>
if (typeof Dropzone !== 'undefined') Dropzone.autoDiscover = false;
const base_url = "<?php echo base_url(); ?>";
const submitBtn = document.querySelector('.submit_btn');

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
  fetch(base_url + 'seller/home/get_districts_by_state?state_id=' + item.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      districtController.setData(distData, '');
    })
    .catch(function(err) { console.error('Districts failed:', err); });
});

// ── Wire up district searchable ───────────────────────────────────────────────
districtController = makeSearchable('district_search', 'district_hidden', 'district_dropdown', [], function(item) {
  // District selected → load cities
  cityController.setData([], '');
  const stateId = document.getElementById('state_hidden').value
    ? stateData.find(function(s) { return s.label === document.getElementById('state_hidden').value; })
    : null;
  if (!stateId) return;
  fetch(base_url + 'seller/home/get_cities_by_district?state_id=' + stateId.id + '&district_id=' + item.id)
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
  fetch(base_url + 'seller/home/get_districts_by_state?state_id=' + stateItem.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      districtController.setData(distData, savedDistrict);
      if (!savedDistrict) return;
      const distItem = distData.find(function(d) { return d.label === savedDistrict; });
      if (!distItem) return;
      fetch(base_url + 'seller/home/get_cities_by_district?state_id=' + stateItem.id + '&district_id=' + distItem.id)
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
  fetch(base_url + 'seller/home/get_districts_by_state?state_id=' + item.id)
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
  fetch(base_url + 'seller/home/get_cities_by_district?state_id=' + stateItem.id + '&district_id=' + item.id)
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
  fetch(base_url + 'seller/home/get_districts_by_state?state_id=' + stateItem.id)
    .then(function(r) { return r.json(); })
    .then(function(rows) {
      const distData = rows.map(function(r) { return { label: r.name, id: r.id }; });
      pickupDistrictController.setData(distData, savedDistrict);
      if (!savedDistrict) return;
      const distItem = distData.find(function(d) { return d.label === savedDistrict; });
      if (!distItem) return;
      fetch(base_url + 'seller/home/get_cities_by_district?state_id=' + stateItem.id + '&district_id=' + distItem.id)
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
</script>


  <script src="<?= base_url('assets/seller/js/cretzo/form.js') ?>"></script>

</body>
</html>
