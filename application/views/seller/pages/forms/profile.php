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
               
                <form  id="seller_form" onSubmit="submitForm(e)" enctype="multipart/form-data"> 
                  
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
                          <!-- <a>verify</a> -->
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Email ID <span class="text-danger">*</span></label>
                          <input name="email" type="email" id="email" class="input" placeholder="Enter Email ID" value="<?=$fetched_data[0]['email']?>" required>
                          <span id="email_error" class="text-danger"></span>
                          <!-- <a>verify</a> -->
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Address
                            <span class="text-danger">*</span>
                          </label>
                          <input name="address1" type="text" class="input" placeholder="Street 1" value="<?=$fetched_data[0]['address1']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">&nbsp;</label>
                          <input name="address2"  type="text" class="input" placeholder="Street 2" value="<?=$fetched_data[0]['address2']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">District
                            <span class="text-danger">*</span>
                          </label>
                          <input name="district" type="text" class="input" placeholder="Enter District" value="<?=$fetched_data[0]['district']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City/Village/Town
                            <span class="text-danger">*</span>
                          </label>
                          <input name="city" type="text" class="input" placeholder="Enter City/Village/Town" value="<?=$fetched_data[0]['city']?>" required> 
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">State
                            <span class="text-danger">*</span>
                          </label>
                          <input name="state" type="text" class="input" placeholder="Enter State" value="<?=$fetched_data[0]['state']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PIN Code
                            <span class="text-danger">*</span>
                          </label>
                          <input name="pin" type="text" class="input" placeholder="Enter PIN Code" value="<?=$fetched_data[0]['pin']?>" required maxlength="6" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                      
                      </div>
                        
                        <div class="text-center mt-3">
                          <button type="button" class="btn btn-next-1 ">Next</button>
                        </div>
                    </div>

                    

                    <div class="form-step form2">
                        <div>
                          <div class="photo-upload d-flex gap-4 justify-content-between align-items-center mb-3">
                            <input type="file" class="hidden" name="store_logo"  id="photoInput" accept="image/*">
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
                          <input name="shop_name" type="text" class="input" placeholder="Shop Name" value="<?=$fetched_data[0]['shop_name']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Social Media Handle <span class="text-danger">*</span></label>
                          <input name="social" type="text" class="input" placeholder="Enter Social Media" value="<?=$fetched_data[0]['social']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Shop Phone Number <span class="text-danger">*</span></label>
                          <input name="shop_phone" type="text" class="input" placeholder="Enter shop  Phone Number" value="<?=$fetched_data[0]['shop_phone']?>" required maxlength="10" onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Pickup Address Lane 1<span class="text-danger">*</span></label>
                          <input name="pickup_address1"  type="text" class="input" placeholder="Address Lane 1" value="<?=$fetched_data[0]['pickup_address1']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label" >Pickup Address Lane 2</label>
                          <input name="pickup_address2" type="text" class="input" placeholder="Address Lane 2" value="<?=$fetched_data[0]['pickup_address2']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">City</label>
                          <input  name="pickup_district" type="text" class="input" placeholder="Enter City" value="<?=$fetched_data[0]['pickup_city']?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label name="pickup_city" class="form-label">District</label>
                          <input type="text" class="input" placeholder="Enter District" value="<?=$fetched_data[0]['pickup_district']?>">
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
                            <option name="individual">Individual</option>
                            <option name="sole_proprietorship">Sole proprietorship</option>
                            <option name="partenership_firm">Partenership Firm</option>
                            <option name="individual">Pvt Ltd.</option>
                          </select>
                          
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">PAN Number<span class="text-danger">*</span></label>
                          <input name="pan" type="text" class="input" placeholder="Enter PAN Number" value="<?=$fetched_data[0]['pan']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">GST Number <span class="text-danger">*</span></label>
                          <input name="gst" type="text" class="input" placeholder="22ABCDE0000A1Z5" value="<?=$fetched_data[0]['gst']?>" required>
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
                          <input name="account_number" type="text" class="input" placeholder="Enter your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Confirm Account Number<span class="text-danger">*</span></label>
                          <input name="confirm_account_number" type="text" class="input" placeholder="Confirm your Account Number" value="<?=$fetched_data[0]['account_number']?>" required onkeypress="if ( isNaN(this.value + String.fromCharCode(event.keyCode) )) return false;">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Account Holder name<span class="text-danger">*</span></label>
                          <input name="account_holder_name" type="text" class="input" placeholder="Enter  the Account Holder’s name" value="<?=$fetched_data[0]['account_holder_name']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">IFSC Code<span class="text-danger">*</span></label>
                          <input name="ifsc" type="text" class="input" placeholder="Enter IFSC Code" value="<?=$fetched_data[0]['ifsc']?>" required >
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Branch Name<span class="text-danger">*</span></label>
                          <input name="branch" type="text" class="input" placeholder="Enter  Branch" value="<?=$fetched_data[0]['branch']?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Bank Name<span class="text-danger">*</span></label>
                          <input name="bank_name" type="text" class="input" placeholder="Enter Bank Name" value="<?=$fetched_data[0]['bank_name']?>" required>
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

    if (input.name === 'ifsc' &&
        !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(input.value)) {
      showError(input, 'Invalid IFSC Code');
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

function submitForm(e) {
  e.preventDefault();

  // ✅ ONLY validate third form
  if (!validateForm3()) return;

  const form = document.getElementById('seller_form');
  const formData = new FormData(form);

  submitBtn.disabled = true;
  submitBtn.innerText = 'Submitting...';

   fetch("<?php echo base_url('seller/login/update_user') ?>", {
     method: 'POST',
     body: formData
   })
   .then(res => res.json())
   .then(data => {
     submitBtn.disabled = false;
     submitBtn.innerText = 'Submit';
    
     if(data.error == false){
        window.location.href = base_url + 'seller/home';
     }

     document.getElementById('response').innerHTML =
       data.error
         ? `<div style="color:red;">${data.message}</div>`
         : `<div style="color:green;">${data.message}</div>`;
   })
   .catch(() => {
     submitBtn.disabled = false;
     submitBtn.innerText = 'Submit';
   });
}

submitBtn.addEventListener('click', submitForm);
</script>

  <script src="<?= base_url('assets/seller/js/cretzo/form.js') ?>"></script>
  
  

</body>
</html>

