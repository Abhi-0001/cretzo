<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/form.css') ?>">
  <style>
    .main-footer { display: none; }
    #toast-msg { display:none; position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; padding:14px 28px; border-radius:8px; font-weight:bold; }
  </style>

</head>
<body>
<div id="toast-msg"></div>
  <section class="content w-100 seller-form">
      <div class="container-fluid">
        <div class="form-parent">
          <div class="form-container-main">

              <div class="form-header w-100">
                  
                  <div class="login-logo ">
                    <a href="<?= base_url() . 'seller/login' ?>">
                      <img class='w-50' src="<?= base_url() . $logo ?>">
                    </a>
                  </div>

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
              <form id="seller_form" enctype="multipart/form-data"> 
                    <div class="form-step form1">
                      <div class="row gap-xl-5">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">First Name <span class="text-danger">*</span></label>
                          <input name="first_name" type="text" class="input" placeholder="First name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Last Name <span class="text-danger">*</span></label>
                          <input name="last_name" type="text" class="input" placeholder="Last Name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                          <input name="phone" type="text" class="input" placeholder="Enter Phone Number" required maxlength="10" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Email ID <span class="text-danger">*</span></label>
                          <input name="email" type="email" class="input" placeholder="Enter Email ID" required maxlength="254">
                          
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Address
                            <span class="text-danger">*</span>
                          </label>
                          <input name="address1" type="text" class="input" placeholder="Street 1">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">&nbsp;</label>
                          <input name="address2"  type="text" class="input" placeholder="Street 2">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">District
                            <span class="text-danger">*</span>
                          </label>
                          <div style="position:relative;">
                            <input type="text" id="district_search" class="input" placeholder="Search District..." autocomplete="off">
                            <input type="hidden" name="district" id="district_hidden" required>
                            <div id="district_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City/Village/Town
                            <span class="text-danger">*</span>
                          </label>
                          <div style="position:relative;">
                            <input type="text" id="city_search" class="input" placeholder="Search City..." autocomplete="off">
                            <input type="hidden" name="city" id="city_hidden" required>
                            <div id="city_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State
                            <span class="text-danger">*</span>
                          </label>
                          <div style="position:relative;">
                            <input type="text" id="state_search" class="input" placeholder="Search State..." autocomplete="off">
                            <input type="hidden" name="state" id="state_hidden" required>
                            <div id="state_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code
                            <span class="text-danger">*</span>
                          </label>
                          <input name="pin" type="text" class="input" placeholder="Enter PIN Code" required maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </dig126v>
                      
                      </div>
                        
                        <div class="text-center mt-3">
                          <button type="button" class="btn btn-next-1 ">Next</button>
                        </div>
                    </div>

                    

                    <div class="form-step form2">
                        <div>
                          <div class="photo-upload d-flex gap-4 justify-content-between align-items-center mb-3">
                          <input type="file" class="hidden" id="photoInput" name="store_logo" accept="image/*">
                            <div class="preview-container ">
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
                          <input name="shop_name" type="text" class="input" placeholder="Shop Name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Social Media Handle <span class="text-danger">*</span></label>
                          <input name="social" type="text" class="input" placeholder="Enter Social Media" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Shop Phone Number <span class="text-danger">*</span></label>
                          <input name="shop_phone" type="text" class="input" placeholder="Enter shop  Phone Number" required maxlength="10" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Pickup Address Lane 1<span class="text-danger">*</span></label>
                          <input name="pickup_address1"  type="text" class="input" placeholder="Address Lane 1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label" >Pickup Address Lane 2</label>
                          <input name="pickup_address2" type="text" class="input" placeholder="Address Lane 2">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City</label>
                          <div style="position:relative;">
                            <input type="text" id="pickup_district_search" class="input" placeholder="Search District..." autocomplete="off">
                            <input type="hidden" name="pickup_district" id="pickup_district_hidden">
                            <div id="pickup_district_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label name="pickup_city" class="form-label">District</label>
                          <div style="position:relative;">
                            <input type="text" id="pickup_city_search" class="input" placeholder="Search City..." autocomplete="off">
                            <input type="hidden" name="pickup_city" id="pickup_city_hidden">
                            <div id="pickup_city_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State</label>
                          <div style="position:relative;">
                            <input type="text" id="pickup_city_search" class="input" placeholder="Search City..." autocomplete="off">
                            <input type="hidden" name="pickup_city" id="pickup_city_hidden">
                            <div id="pickup_city_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                          </div>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code</label>
                          <input name="pickup_pin" type="text" class="input" placeholder="Enter PIN Code" maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                      </div>
                      
                      <div class=" mt-3 w-100 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-back-1 ">Back</button>
                        <button type="button" class="btn btn-next-2 ">Next</button>
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
                          <input name="pan" type="text" class="input" maxlength="10" placeholder="Enter PAN Number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">GST Number <span class="text-danger">*</span></label>
                          <input name="gst" type="text" class="input" maxlength="15" placeholder="22ABCDE0000A1Z5" required>
                        </div>
                      </div>

                      <h3>Declaration</h3>
                      <div class="d-flex flex-column justify-content-between align-items-start">
                          <div>
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
                          <input name="account_number" type="text" class="input" maxlength="18" placeholder="Enter your Account Number" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Confirm Account Number<span class="text-danger">*</span></label>
                          <input name="confirm_account_number" type="text" class="input" maxlength="18" placeholder="Confirm your Account Number" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Account Holder name<span class="text-danger">*</span></label>
                          <input name="account_holder_name" type="text" class="input" placeholder="Enter  the Account Holder’s name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">IFSC Code<span class="text-danger">*</span></label>
                          <input name="ifsc" type="text" class="input" placeholder="Enter IFSC Code" maxlength="11" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Branch Name<span class="text-danger">*</span></label>
                          <input name="branch" type="text" class="input" placeholder="Enter  Branch" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Bank Name<span class="text-danger">*</span></label>
                          <input name="ifsc" type="text" class="input" placeholder="Enter IFSC Code" maxlength="11" required>
                          </div>
                        </div>

                      </div>
                      <div class=" mt-3 w-100 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-back-2 ">Back</button>
                        <button type="submit" class="btn submit_btn">Submit</button>
                      </div>

                      <div id="response">

                      </div>

                    </div>
                  
                </form>
              </div>
              
          </div>
        </div>
      </div>
      
  </section>
  <script>
    const base_url = "<?= base_url() ?>";
    const submitBtn = document.querySelector('.submit_btn');

    function makeSearchable(searchId, hiddenId, dropdownId, data, onSelect) {
      const searchEl = document.getElementById(searchId), hiddenEl = document.getElementById(hiddenId), dropdownEl = document.getElementById(dropdownId);
      function renderDropdown(items) {
        dropdownEl.innerHTML = '';
        if (!items.length) return dropdownEl.style.display = 'none';
        items.forEach(function(item){ const div=document.createElement('div'); div.textContent=item.label; div.style.cssText='padding:8px 12px;cursor:pointer;'; div.onclick=function(){ searchEl.value=item.label; hiddenEl.value=item.label; dropdownEl.style.display='none'; if(onSelect) onSelect(item); }; dropdownEl.appendChild(div);});
        dropdownEl.style.display = 'block';
      }
      searchEl.addEventListener('input', function(){ const q=this.value.toLowerCase(); if(!q){ hiddenEl.value=''; dropdownEl.style.display='none'; return; } renderDropdown(data.filter(function(d){ return d.label.toLowerCase().includes(q); })); });
      document.addEventListener('click', function(e){ if(e.target !== searchEl) dropdownEl.style.display='none';});
      return { setData: function(newData){ data = newData; searchEl.value=''; hiddenEl.value=''; dropdownEl.style.display='none'; }};
    }
    const stateData = [<?php foreach ($states as $s): ?>{ label: "<?= addslashes($s['name']) ?>", id: "<?= $s['id'] ?>" },<?php endforeach; ?>];
    let districtController = makeSearchable('district_search','district_hidden','district_dropdown',[], function(item){ const stateItem = stateData.find(s => s.label === document.getElementById('state_hidden').value); if(!stateItem) return; fetch(base_url + 'seller/home/get_cities_by_district?state_id='+stateItem.id+'&district_id='+item.id).then(r=>r.json()).then(rows=>cityController.setData(rows.map(r => ({label:r.name,id:r.id})))); });
    let cityController = makeSearchable('city_search','city_hidden','city_dropdown',[], null);
    makeSearchable('state_search','state_hidden','state_dropdown',stateData, function(item){ districtController.setData([]); cityController.setData([]); fetch(base_url + 'seller/home/get_districts_by_state?state_id='+item.id).then(r=>r.json()).then(rows=>districtController.setData(rows.map(r => ({label:r.name,id:r.id})))); });
    let pickupDistrictController = makeSearchable('pickup_district_search','pickup_district_hidden','pickup_district_dropdown',[], function(item){ const stateItem = stateData.find(s => s.label === document.getElementById('pickup_state_hidden').value); if(!stateItem) return; fetch(base_url + 'seller/home/get_cities_by_district?state_id='+stateItem.id+'&district_id='+item.id).then(r=>r.json()).then(rows=>pickupCityController.setData(rows.map(r => ({label:r.name,id:r.id})))); });
    let pickupCityController = makeSearchable('pickup_city_search','pickup_city_hidden','pickup_city_dropdown',[], null);
    makeSearchable('pickup_state_search','pickup_state_hidden','pickup_state_dropdown',stateData, function(item){ pickupDistrictController.setData([]); pickupCityController.setData([]); fetch(base_url + 'seller/home/get_districts_by_state?state_id='+item.id).then(r=>r.json()).then(rows=>pickupDistrictController.setData(rows.map(r => ({label:r.name,id:r.id})))); });
    const bankData = [<?php foreach ($indian_banks as $bank): ?>{ label: "<?= addslashes($bank['bank_name']) ?>" },<?php endforeach; ?>];
    makeSearchable('bank_search', 'bank_name_hidden', 'bank_dropdown', bankData, null);
    submitBtn.addEventListener('click', function(e){
      if (!validateForm(document.querySelector('.form3'))) return;
      const formData = new FormData(document.getElementById('seller_form'));
      submitBtn.disabled = true;
      fetch("<?php echo base_url("seller/auth/create_seller") ?>", { method:'POST', body:formData })
      .then(res => res.json())
      .then(data => {
        submitBtn.disabled = false;
        const toast = document.getElementById('toast-msg');
        if (data.error) {
          toast.style.cssText = 'display:block; background:#f8d7da; color:#721c24;';
          toast.innerText = '❌ ' + data.message;
        } else {
          toast.style.cssText = 'display:block; background:#f8d7da; color:#721c24;';
          toast.innerText = '❌ ' + data.message;
        }
        setTimeout(function () { toast.style.display = 'none'; }, 5000);
      }).catch(function(){
        submitBtn.disabled = false;
      });
  </script>
  <script src="<?= base_url('assets/seller/js/cretzo/form.js') ?>"></script>
</body>
</html>

