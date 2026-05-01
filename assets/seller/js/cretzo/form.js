

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

function validateForm(form) {
  clearErrors(form);
  let valid = true;

  const inputs = form.querySelectorAll('input, select');

  inputs.forEach(input => {
    if (!input.hasAttribute('required')) return;

    if (!input.value.trim()) {
      showError(input, 'This field is required');
      valid = false;
      return;
    }

    if (input.type === 'email' &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
      showError(input, 'Invalid email');
      valid = false;
    }

    if ((input.name === 'phone' || input.name === 'shop_phone') &&
        !/^[0-9]{10}$/.test(input.value)) {
      showError(input, 'Enter 10 digit number');
      valid = false;
    }

    if ((input.name === 'pin' || input.name === 'pickup_pin') &&
        !/^[0-9]{6}$/.test(input.value)) {
      showError(input, 'Enter valid PIN');
      valid = false;
    }
    
    if (input.name === 'pan' &&
      !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(input.value.toUpperCase())) {
    showError(input, 'Invalid PAN. Example: ABCDE1234F');
    valid = false;
  }

  if (input.name === 'gst' &&
      !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(input.value.toUpperCase())) {
    showError(input, 'Invalid GST. Example: 22ABCDE0000A1Z5');
    valid = false;
  }

    if (input.name === 'ifsc' && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(input.value.toUpperCase())) {
      showError(input, 'Invalid IFSC code');
      valid = false;
    }
  });
  
  const accountInput = form.querySelector('[name="account_number"]');
  const confirmAccountInput = form.querySelector('[name="confirm_account_number"]');
  if (accountInput && confirmAccountInput && accountInput.value !== confirmAccountInput.value) {
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



    

    