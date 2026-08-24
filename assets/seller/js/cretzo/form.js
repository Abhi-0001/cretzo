

// const form1 = document.querySelector('.form1');
// const form2 = document.querySelector('.form2');
// const form3 = document.querySelector('.form3');

// const btnNext1 = document.querySelector('.btn-next-1');
// const btnNext2 = document.querySelector('.btn-next-2');
// const btnBack1 = document.querySelector('.btn-back-1');
// const btnBack2 = document.querySelector('.btn-back-2');


// const sliderLine1 = document.querySelector('.completion-line-1');
// const sliderLine2 = document.querySelector('.completion-line-2');

// const formIndicator2  = document.querySelector('.form-indicator-2');
// const formIndicator3  = document.querySelector('.form-indicator-3');

// const photoInput = document.getElementById('photoInput');
// const photoContainer = document.querySelector('.preview-container');
// const photoPreview = document.getElementById('photoPreview');
// const profileIcon = document.querySelector('.profile-icon');


// photoContainer.addEventListener('click', function(){
//     photoInput.click();
// })

// photoInput.addEventListener('change', function() {
//     const file = this.files[0];
//     if (file && file.type.startsWith('image/')) {
//       const reader = new FileReader();

//       reader.onload = function(e) {
//         photoPreview.src = e.target.result;
//         // photoPreview.style.display = 'block';
//         photoPreview.classList.remove('hidden');
//         profileIcon.classList.add('hidden');
//       }

//       reader.readAsDataURL(file);
//     } else {
//     //   photoPreview.style.display = 'none';
//         photoPreview.classList.add('hidden');
//         profileIcon.classList.remove('hidden');
//         photoPreview.src = '';
//     }
//   });

// btnNext1.addEventListener('click', function(){
//     form1.style.left = '-500%';
//     form2.style.left = '0';

//     sliderLine1.classList.add('active');
//     formIndicator2.classList.add('active');

// })

// btnNext2.addEventListener('click', function(){
//     form2.style.left = '-500%';
//     form3.style.left = '0';
//     sliderLine2.classList.add('active');
//     formIndicator3.classList.add('active');
// })

// btnBack1.addEventListener('click', function(){
//     form1.style.left = '0';
//     form2.style.left = '500%';

//     sliderLine1.classList.remove('active');
//     formIndicator2.classList.remove('active');

// })

// btnBack2.addEventListener('click', function(){
//     form2.style.left = '0';
//     form3.style.left = '500%';
//     sliderLine2.classList.remove('active');
//     formIndicator3.classList.remove('active');
// })






/* =====================
   STEP ELEMENTS
===================== */
const form1 = document.querySelector('.form1');
const form2 = document.querySelector('.form2');
const form3 = document.querySelector('.form3');
const form4 = document.querySelector('.form4');
const btnNext1 = document.querySelector('.btn-next-1');
const btnNext2 = document.querySelector('.btn-next-2');
const btnNext3 = document.querySelector('.btn-next-3');
const btnBack1 = document.querySelector('.btn-back-1');
const btnBack2 = document.querySelector('.btn-back-2');
const btnBack3 = document.querySelector('.btn-back-3');
const sliderLine1 = document.querySelector('.completion-line-1');
const sliderLine2 = document.querySelector('.completion-line-2');
const sliderLine3 = document.querySelector('.completion-line-3');
const formIndicator2 = document.querySelector('.form-indicator-2');
const formIndicator3 = document.querySelector('.form-indicator-3');
const formIndicator4 = document.querySelector('.form-indicator-4');

/* =====================
   IMAGE PREVIEW
===================== */
const photoInput = document.getElementById('photoInput');
const photoContainer = document.querySelector('.preview-container');
const photoPreview = document.getElementById('photoPreview');
const profileIcon = document.querySelector('.profile-icon');

if (photoContainer && photoInput) {
  photoContainer.addEventListener('click', () => photoInput.click());
}

if (photoInput) {
  photoInput.addEventListener('change', function () {
  const file = this.files[0];
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      photoPreview.src = e.target.result;
      photoPreview.classList.remove('hidden');
      profileIcon.classList.add('hidden');
    };
    reader.readAsDataURL(file);
  }
});
}

/* =====================
   VALIDATION FUNCTIONS
===================== */
function clearErrors(form) {
  form.querySelectorAll('.error-msg').forEach(e => e.remove());
  form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
}

function showError(input, message) {
  input.classList.add('is-invalid');
  const error = document.createElement('small');
  error.className = 'error-msg text-danger';
  error.innerText = message;
  input.parentElement.appendChild(error);
}

document.addEventListener('change', function (e) {
  if (!e.target || e.target.type !== 'file') return;
  e.target.classList.remove('is-invalid');
  const err = e.target.parentElement.querySelector('.error-msg');
  if (err) err.remove();
});

// Friendly "required" (empty-field) message per field name; falls back to generic.
const REQUIRED_MSG = {
  first_name: 'Please enter your first name',
  last_name: 'Please enter your last name',
  phone: 'Please enter your phone number',
  email: 'Please enter your email',
  address1: 'Please enter your address',
  pin: 'Please enter PIN code',
  state: 'Please enter your state',
  district: 'Please enter your district',
  city: 'Please enter your city/village/town',
  shop_name: 'Please enter your shop name',
  social: 'Please enter your social media handle',
  shop_phone: 'Please enter your shop phone number',
  pickup_address1: 'Please enter pickup address',
  pickup_pin: 'Please enter pickup PIN code',
  primary_category_id: 'Please select your primary product category',
  business_address1: 'Please enter your business address',
  business_pin: 'Please enter business PIN code',
  business_state: 'Please enter business state',
  business_district: 'Please enter business district',
  business_city: 'Please enter business city/village/town',
  entity_type: 'Please select an entity type',
  pan: 'Please enter your PAN number',
  gst: 'Please enter your GST number',
  gst_enrollment_number: 'Please enter your GST Enrollment ID',
  account_number: 'Please enter your account number',
  confirm_account_number: 'Please confirm your account number',
  account_holder_name: "Please enter the account holder's name",
  ifsc: 'Please enter IFSC code',
  branch: 'Please enter branch name',
  bank_name: 'Please select your bank'
};

// Reusable patterns.
const RE_NAME   = /^[A-Za-z][A-Za-z\s.'-]*$/;                                  // letters, spaces, . ' -
const RE_EMAIL  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const RE_MOBILE = /^[6-9][0-9]{9}$/;                                           // Indian 10-digit mobile
const RE_PIN    = /^[1-9][0-9]{5}$/;                                           // 6-digit PIN, not starting 0
const RE_PAN    = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
const RE_GST    = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
const RE_IFSC   = /^[A-Z]{4}0[A-Z0-9]{6}$/;
const RE_ENROL  = /^[A-Za-z0-9]{6,20}$/;                                       // GST enrollment no.
const RE_ACCOUNT = /^[0-9]{9,18}$/;

function validateForm(form) {
  clearErrors(form);
  let valid = true;

  form.querySelectorAll('input, select, textarea').forEach(input => {
    const name = input.name;
    if (input.type === 'file') return; // store_logo handled separately below

    // Normalise PAN / GST / IFSC to uppercase so the saved value is correct too.
    if ((name === 'pan' || name === 'gst' || name === 'ifsc') && input.value.trim()) {
      input.value = input.value.trim().toUpperCase();
    }

    const val = input.value.trim();

    // 1) Required (empty) check
    if (input.hasAttribute('required') && !val) {
      showError(input, REQUIRED_MSG[name] || 'This field is required');
      valid = false;
      return;
    }

    // Optional field left blank → nothing more to check.
    if (!val) return;

    // 2) Field-specific format checks
    switch (name) {
      case 'first_name':
        if (!RE_NAME.test(val)) { showError(input, 'First name may contain only letters'); valid = false; }
        else if (val.length < 3 || val.length > 50) { showError(input, 'First name must be 3–50 characters'); valid = false; }
        break;
      case 'last_name':
        if (!RE_NAME.test(val)) { showError(input, 'Last name may contain only letters'); valid = false; }
        else if (val.length > 50) { showError(input, 'Last name must be under 50 characters'); valid = false; }
        break;
      case 'account_holder_name':
        if (!RE_NAME.test(val)) { showError(input, 'Name may contain only letters'); valid = false; }
        break;
      case 'email':
        if (val.length > 254) { showError(input, 'Email must be 254 characters or less'); valid = false; }
        else if (!RE_EMAIL.test(val) || !/\.[A-Za-z]{2,}$/.test(val)) { showError(input, 'Enter a valid email address (example: name@example.com)'); valid = false; }
        break;
      case 'phone':
      case 'shop_phone': {
        // Split length vs series so the message says which rule was broken - "enter a valid
        // 10-digit number" is unhelpful when the number is 10 digits but starts with a 4.
        const phoneLabel = (name === 'phone') ? 'Phone number' : 'Shop phone number';
        if (!/^[0-9]{10}$/.test(val)) { showError(input, phoneLabel + ' must be exactly 10 digits'); valid = false; }
        else if (!RE_MOBILE.test(val)) { showError(input, phoneLabel + ' must start with 6, 7, 8 or 9'); valid = false; }
        break;
      }
      case 'pin':
      case 'pickup_pin':
      case 'business_pin':
        if (!RE_PIN.test(val)) { showError(input, 'Enter a valid 6-digit PIN code'); valid = false; }
        break;
      case 'address1':
      case 'pickup_address1':
      case 'business_address1':
        if (val.length < 5) { showError(input, 'Address must be at least 5 characters'); valid = false; }
        break;
      case 'shop_name':
        if (val.length < 2 || val.length > 100) { showError(input, 'Shop name must be 2–100 characters'); valid = false; }
        break;
      case 'slug':
        if (!/^[a-z0-9]+(-[a-z0-9]+)*$/i.test(val)) { showError(input, 'Store URL may contain only letters, numbers and hyphens'); valid = false; }
        break;
      case 'pan':
        if (!RE_PAN.test(val)) { showError(input, 'Invalid PAN. Example: ABCDE1234F'); valid = false; }
        break;
      case 'gst':
        if (!RE_GST.test(val)) { showError(input, 'Invalid GST. Example: 22ABCDE0000A1Z5'); valid = false; }
        break;
      case 'gst_enrollment_number':
        if (!RE_ENROL.test(val)) { showError(input, 'Enter a valid GST Enrollment ID (6–20 letters/numbers)'); valid = false; }
        break;
      case 'account_number':
        if (!RE_ACCOUNT.test(val)) { showError(input, 'Enter a valid account number (9–18 digits)'); valid = false; }
        break;
      case 'ifsc':
        if (!RE_IFSC.test(val)) { showError(input, 'Invalid IFSC code. Example: SBIN0001234'); valid = false; }
        break;
    }
  });

  // Image uploads: optional, but if a file is chosen it must be a valid image ≤ 8 MB.
  const FILE_LABELS = {
    store_logo: 'Logo',
    seller_photo: 'Photo',
    national_identity_card: 'Identity proof',
    authorized_signature: 'Authorized signatory document'
  };
  Object.keys(FILE_LABELS).forEach(function (fieldName) {
    const fileInput = form.querySelector('input[type="file"][name="' + fieldName + '"]');
    if (!fileInput || !fileInput.files || !fileInput.files.length) return;
    const f = fileInput.files[0];
    if (!/^image\/(jpeg|jpg|png|gif)$/i.test(f.type)) {
      showError(fileInput, 'Please upload a valid image (JPG, PNG or GIF)');
      valid = false;
    } else if (f.size > 8 * 1024 * 1024) {
      showError(fileInput, FILE_LABELS[fieldName] + ' must be under 8 MB');
      valid = false;
    }
  });

  // Business Details documents: same size check as above, but PDF is a valid type
  // too (GSTIN/enrollment-ack/business-proof docs are commonly issued as PDFs).
  const FILE_LABELS_FLEXIBLE = {
    pan_card_document: 'PAN card',
    gstin_document: 'GSTIN document',
    gst_enrollment_ack_document: 'GST enrollment acknowledgement',
    business_proof_document: 'Business proof',
    business_address_proof_document: 'Business address proof',
    partnership_deed_document: 'Partnership deed',
    bank_account_proof_document: 'Bank account proof'
  };
  Object.keys(FILE_LABELS_FLEXIBLE).forEach(function (fieldName) {
    const fileInput = form.querySelector('input[type="file"][name="' + fieldName + '"]');
    if (!fileInput || !fileInput.files || !fileInput.files.length) return;
    const f = fileInput.files[0];
    if (!/^image\/(jpeg|jpg|png|gif)$/i.test(f.type) && f.type !== 'application/pdf') {
      showError(fileInput, 'Please upload a valid image (JPG, PNG, GIF) or PDF');
      valid = false;
    } else if (f.size > 8 * 1024 * 1024) {
      showError(fileInput, FILE_LABELS_FLEXIBLE[fieldName] + ' must be under 8 MB');
      valid = false;
    }
  });

  // Identity Proof / Authorized Signatory (+ certain Business Details documents) are
  // required: either a new file is chosen, or the hidden "old_<field>" input still
  // carries a previously uploaded file (it's cleared by the × remove button, so
  // removing without replacing blocks here). Skips fields currently hidden by the
  // entity-type/GST toggles — those aren't applicable, so shouldn't be enforced.
  const REQUIRED_DOC_FIELDS = {
    national_identity_card: 'Identity Proof',
    authorized_signature: 'Authorized Signatory',
    pan_card_document: 'PAN Card',
    gstin_document: 'GSTIN document',
    gst_enrollment_ack_document: 'GST Enrollment Acknowledgement Slip',
    partnership_deed_document: 'Partnership Deed',
    business_proof_document: 'Business Proof',
    business_address_proof_document: 'Business Address Proof'
  };
  Object.keys(REQUIRED_DOC_FIELDS).forEach(function (fieldName) {
    const fileInput = form.querySelector('input[type="file"][name="' + fieldName + '"]');
    if (!fileInput || fileInput.offsetParent === null) return; // not on this step, or hidden by a toggle
    const oldInput = form.querySelector('input[name="old_' + fieldName + '"]');
    const hasNewFile = fileInput.files && fileInput.files.length > 0;
    const hasExisting = oldInput && oldInput.value.trim() !== '';
    if (!hasNewFile && !hasExisting) {
      showError(fileInput, 'Please upload ' + REQUIRED_DOC_FIELDS[fieldName]);
      valid = false;
    }
  });

  // Account number confirmation must match.
  const accountInput = form.querySelector('[name="account_number"]');
  const confirmAccountInput = form.querySelector('[name="confirm_account_number"]');
  if (accountInput && confirmAccountInput &&
      accountInput.value.trim() && confirmAccountInput.value.trim() &&
      accountInput.value.trim() !== confirmAccountInput.value.trim()) {
    showError(confirmAccountInput, 'Account numbers do not match');
    valid = false;
  }

  return valid;
}
/* =====================
   NEXT / BACK BUTTONS
===================== */
btnNext1.addEventListener('click', () => {
  if (!validateForm(form1)) return;

  form1.style.left = '-500%';
  form2.style.left = '0';
  sliderLine1.classList.add('active');
  formIndicator2.classList.add('active');
});

btnNext2.addEventListener('click', () => {
  if (!validateForm(form2)) return;

  form2.style.left = '-500%';
  form3.style.left = '0';
  sliderLine2.classList.add('active');
  formIndicator3.classList.add('active');
});
if (btnNext3 && form3 && form4) {
  btnNext3.addEventListener('click', () => {
    if (!validateForm(form3)) return;
    form3.style.left = '-500%';
    form4.style.left = '0';
    sliderLine3.classList.add('active');
    formIndicator4.classList.add('active');
  });
}

btnBack1.addEventListener('click', () => {
  form1.style.left = '0';
  form2.style.left = '500%';
  sliderLine1.classList.remove('active');
  formIndicator2.classList.remove('active');
});

btnBack2.addEventListener('click', () => {
  form2.style.left = '0';
  form3.style.left = '500%';
  sliderLine2.classList.remove('active');
  formIndicator3.classList.remove('active');
});

if (btnBack3 && form3 && form4) {
  btnBack3.addEventListener('click', () => {
    form3.style.left = '0';
    form4.style.left = '500%';
    sliderLine3.classList.remove('active');
    formIndicator4.classList.remove('active');
  });
}


$('#entity_type').on('change', function(){
        if($(this).val() == 'individual'){
            $('#entity_check_div').show();
        }else{
            $('#entity_check_div').hide();
        }
})



    

    