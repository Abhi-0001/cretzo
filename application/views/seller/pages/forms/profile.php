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
</style>
</head>
<body>
  
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
                          <!-- BUG FIX #6 — type="email" enforces email format at browser level, maxlength corrected from max_length to maxlength -->
                          <input name="email" type="email" id="email" class="input" placeholder="Enter Email ID" maxlength="254" value="<?=$fetched_data[0]['email']?>" required>
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
                          <label class="form-label">District <span class="text-danger">*</span></label>
                          <input name="district" type="text" class="input" placeholder="Enter District" value="<?=$fetched_data[0]['district']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City/Village/Town <span class="text-danger">*</span></label>
                          <input name="city" type="text" class="input" placeholder="Enter City/Village/Town" value="<?=$fetched_data[0]['city']?>" required> 
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State <span class="text-danger">*</span></label>
                          <input name="state" type="text" class="input" placeholder="Enter State" value="<?=$fetched_data[0]['state']?>" required>
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
                          <label class="form-label">City</label>
                          <input name="pickup_district" type="text" class="input" placeholder="Enter City" value="<?=$fetched_data[0]['pickup_city']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">District</label>
                          <!-- FIX — Added missing name attribute, was not POSTing -->
                          <input name="pickup_city" type="text" class="input" placeholder="Enter District" value="<?=$fetched_data[0]['pickup_district']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State</label>
                          <input name="pickup_state" type="text" class="input" placeholder="Enter State" value="<?=$fetched_data[0]['pickup_state']?>">
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
                          <!-- BUG FIX #6 — fixed name= to value= so selected option POSTs correctly -->
                          <select name="entity_type" class="input" id="entity_type">
                            <option value="individual">Individual</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="partnership_firm">Partnership Firm</option>
                            <option value="pvt_ltd">Pvt Ltd.</option>
                          </select>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PAN Number <span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 — maxlength enforces 10 character PAN -->
                          <input name="pan" type="text" maxlength="10" class="input" placeholder="Enter PAN Number" value="<?=$fetched_data[0]['pan']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">GST Number <span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 — maxlength enforces 15 character GST -->
                          <input name="gst" type="text" maxlength="15" class="input" placeholder="22ABCDE0000A1Z5" value="<?=$fetched_data[0]['gst']?>" required>
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
                          <label class="form-label">Account Number <span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 — maxlength enforces max 18 digits -->
                          <input name="account_number" type="text" class="input" maxlength="18" placeholder="Enter your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Confirm Account Number <span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 — maxlength matches account number limit -->
                          <input name="confirm_account_number" type="text" class="input" maxlength="18" placeholder="Confirm your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                          <input name="account_holder_name" type="text" class="input" placeholder="Enter the Account Holder's name" value="<?=$fetched_data[0]['account_holder_name']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">IFSC Code <span class="text-danger">*</span></label>
                          <!-- BUG FIX #6 — maxlength enforces exact 11 character IFSC -->
                          <input name="ifsc" type="text" class="input" placeholder="Enter IFSC Code" maxlength="11" value="<?=$fetched_data[0]['ifsc']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                          <input name="branch" type="text" class="input" placeholder="Enter Branch" value="<?=$fetched_data[0]['branch']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                          <input name="bank_name" type="text" class="input" placeholder="Enter Bank Name" value="<?=$fetched_data[0]['bank_name']?>" required>
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
// FIX 4 — base_url defined as JS variable so redirect works correctly after success
const base_url = "<?php echo base_url(); ?>";

const submitBtn = document.querySelector('.submit_btn');

function clearErrors(form) {
  form.querySelectorAll('.error-msg').forEach(e => e.remove());
  form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
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
  const inputs = form3.querySelectorAll('input[required], select[required]');

  inputs.forEach(input => {
    if (!input.value.trim()) {
      showError(input, 'This field is required');
      valid = false;
      return;
    }

    // Frontend IFSC validation
    if (input.name === 'ifsc' && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(input.value.toUpperCase())) {
      showError(input, 'Invalid IFSC Code. Example: SBIN0001234');
      valid = false;
    }

    // Frontend PAN validation
    if (input.name === 'pan' && !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(input.value.toUpperCase())) {
      showError(input, 'Invalid PAN. Example: ABCDE1234F');
      valid = false;
    }

    // Frontend GST validation
    if (input.name === 'gst' && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(input.value.toUpperCase())) {
      showError(input, 'Invalid GST. Example: 22ABCDE0000A1Z5');
      valid = false;
    }
  });

  // Account number match check
  const acc = form3.querySelector('[name="account_number"]');
  const conf = form3.querySelector('[name="confirm_account_number"]');
  if (acc && conf && acc.value !== conf.value) {
    showError(conf, 'Account numbers do not match');
    valid = false;
  }

  return valid;
}

// FIX 5 — Single clean event listener, no conflicting onSubmit on the form tag
submitBtn.addEventListener('click', function(e) {
  e.preventDefault();

  const responseDiv = document.getElementById('response');
  responseDiv.innerHTML = '';

  // Run frontend validation first — stops here if invalid
  if (!validateForm3()) return;

  const form = document.getElementById('seller_form');
  const formData = new FormData(form);

  submitBtn.disabled = true;
  submitBtn.innerText = 'Submitting...';

  fetch("<?php echo base_url('seller/login/update_user') ?>", {
    method: 'POST',
    body: formData
  })
  .then(function(res) {
    return res.text().then(function(text) {
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error('Server returned non-JSON:', text);
            throw new Error('Invalid JSON response');
        }
    });
})
  .then(function(data) {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Submit';

    if (data.error == false) {
      // FIX 6 — Show success message then redirect after 1 second
      responseDiv.innerHTML = '<div style="color:green; font-weight:bold;">✅ ' + data.message + ' Redirecting...</div>';
      setTimeout(function() {
        window.location.href = base_url + 'seller/home';
      }, 1000);
      return;
    }

    // Show backend error — scrolls into view so user always sees it
    responseDiv.innerHTML = '<div style="color:red;">' + data.message + '</div>';
    responseDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
  })
  .catch(function(err) {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Submit';
    responseDiv.innerHTML = '<div style="color:red;">Something went wrong. Please try again.</div>';
    console.error('Submit error:', err);
})
});
</script>

  <script src="<?= base_url('assets/seller/js/cretzo/form.js') ?>"></script>
  
</body>
</html>
