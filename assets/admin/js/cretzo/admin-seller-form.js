/* ==========================================================================
   Admin > Sellers > Add / Update Seller - step engine, validation, save
   --------------------------------------------------------------------------
   The counterpart of assets/seller/js/cretzo/seller-profile.js for the admin
   side, and deliberately close to it: the same [data-czp-field] wrappers, the
   same per-field error slots, the same file rules, the same pincode/bank/
   category helpers. The two forms write to the same columns through
   admin/Sellers::add_seller() and seller/Login::update_user(), so keeping the
   client rules identical is what stops one side accepting what the other
   rejects.

   Three things differ on purpose, because this is an operator tool and not a
   self-service wizard:

   1. NAVIGATION IS NEVER BLOCKED. The seller wizard gates Continue so a
      seller cannot skip a step; an admin opening seller #73 to flip Status to
      Approved must not be held hostage by a document that seller never
      uploaded. The rail and Back/Next always move. Problems surface on blur
      and on Save, which validates every step and jumps to the first one that
      has a problem.

   2. SAVE IS AVAILABLE FROM EVERY STEP, from the sticky action bar.

   3. The save POSTs to admin/sellers/add_seller and routes a rejection back
      to the field it names, instead of leaving the admin to read
      validation_errors() as one anonymous blob. (This is why the form no
      longer carries the .form-submit-event class - custom.js's delegated
      handler would otherwise submit it a second time.)
   ========================================================================== */

(function () {
  'use strict';

  var CFG = window.ASF_CONFIG || {};
  var form = document.getElementById('seller_admin_form');
  if (!form) return;

  // Declared up here because checkStep() reads it, and checkStep() can run from
  // the entity/GST sync that fires while the rest of this file is still being
  // evaluated - a `var` further down would be hoisted as undefined and throw.
  var contactState = { phone: null, shop_phone: null, email: null };

  /* ======================================================================
     Constants shared with the server
     ====================================================================== */

  // admin/Sellers::add_seller() -> allowed_types 'jpg|png|jpeg|gif|pdf'
  var ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
  // ...and max_size 8000, which CodeIgniter reads as kilobytes.
  var MAX_FILE_KB = 8000;

  var RE = {
    name: /^[A-Za-z][A-Za-z\s.'-]*$/,
    email: /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/,
    mobile: /^[6-9][0-9]{9}$/,
    pin: /^[1-9][0-9]{5}$/,
    pan: /^[A-Z]{5}[0-9]{4}[A-Z]$/,
    gst: /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/,
    ifsc: /^[A-Z]{4}0[A-Z0-9]{6}$/,
    enrolment: /^[0-9A-Za-z\-\/]{8,32}$/,
    account: /^[0-9]{9,18}$/,
    slug: /^[a-z0-9]+(-[a-z0-9]+)*$/i
  };

  function phoneCheck(label) {
    return function (v) {
      if (!/^[0-9]{10}$/.test(v)) return label + ' must be exactly 10 digits';
      if (!RE.mobile.test(v)) return label + ' must start with 6, 7, 8 or 9';
      return '';
    };
  }
  function pinCheck(v) { return RE.pin.test(v) ? '' : 'Enter a valid 6-digit PIN code'; }
  function minLen(n, msg) { return function (v) { return v.length < n ? msg : ''; }; }

  // name -> { label, required, upper, check(value) => error string }
  var RULES = {
    first_name: {
      label: 'First Name', required: true,
      check: function (v) {
        if (!RE.name.test(v)) return 'First name may contain only letters';
        if (v.length > 50) return 'First name must be under 50 characters';
        return '';
      }
    },
    middle_name: {
      label: 'Middle Name',
      check: function (v) { return RE.name.test(v) ? '' : 'Middle name may contain only letters'; }
    },
    last_name: {
      label: 'Last Name', required: true,
      check: function (v) {
        if (!RE.name.test(v)) return 'Last name may contain only letters';
        if (v.length > 50) return 'Last name must be under 50 characters';
        return '';
      }
    },
    phone: { label: 'Phone Number', required: true, check: phoneCheck('Phone number') },
    email: {
      label: 'Email', required: true,
      check: function (v) {
        if (v.length > 254) return 'Email must be 254 characters or less';
        return RE.email.test(v) ? '' : 'Enter a valid email address (example: name@example.com)';
      }
    },
    // Only rendered when adding - ion_auth hashes whatever is set here, and the
    // seller can change it later from their own profile.
    password: { label: 'Password', required: true },
    confirm_password: { label: 'Confirm Password', required: true },

    address1: { label: 'Address', required: true, check: minLen(5, 'Address must be at least 5 characters') },
    pin: { label: 'PIN Code', required: true, check: pinCheck },
    state: { label: 'State', required: true },
    district: { label: 'District', required: true },
    city: { label: 'City/Village/Town', required: true },

    shop_name: {
      label: 'Shop Name', required: true,
      check: function (v) { return (v.length < 2 || v.length > 100) ? 'Shop name must be 2-100 characters' : ''; }
    },
    slug: {
      label: 'Store URL',
      check: function (v) { return RE.slug.test(v) ? '' : 'Store URL may contain only letters, numbers and hyphens'; }
    },
    shop_phone: { label: 'Shop Phone Number', required: true, check: phoneCheck('Shop phone number') },
    social: { label: 'Social Media Handle' },
    store_description: { label: 'Store Description' },
    pickup_address1: { label: 'Pickup Address Line 1', required: true, check: minLen(5, 'Pickup address must be at least 5 characters') },
    pickup_address2: { label: 'Pickup Address Line 2' },
    pickup_pin: { label: 'Pickup PIN Code', required: true, check: pinCheck },
    pickup_state: { label: 'Pickup State', required: true },
    pickup_district: { label: 'Pickup District', required: true },
    pickup_city: { label: 'Pickup City', required: true },
    primary_category_id: { label: 'Primary Product Category', required: true },

    entity_type: { label: 'Entity Type', required: true },
    legal_business_name: { label: 'Legal Business Name' },
    pan: {
      label: 'PAN Number', required: true, upper: true,
      // The same expression add_seller() enforces, and the same one
      // Tax_compliance_model::classify_pan() reads to pick the statutory rate.
      check: function (v) { return RE.pan.test(v) ? '' : 'Invalid PAN. Example: ABCDE1234F'; }
    },
    business_address1: { label: 'Business Address Line 1', required: true, check: minLen(5, 'Business address must be at least 5 characters') },
    business_address2: { label: 'Business Address Line 2' },
    business_pin: { label: 'Business PIN Code', required: true, check: pinCheck },
    business_state: { label: 'Business State', required: true },
    business_district: { label: 'Business District', required: true },
    business_city: { label: 'Business City/Village/Town', required: true },
    gst: {
      label: 'GST Number', required: true, upper: true,
      check: function (v) { return RE.gst.test(v) ? '' : 'Invalid GST. Example: 22ABCDE0000A1Z5'; }
    },
    gst_enrollment_number: {
      label: 'GST Enrollment ID', required: true,
      check: function (v) { return RE.enrolment.test(v) ? '' : 'Enter a valid GST Enrollment ID (8-32 letters, numbers, - or /)'; }
    },

    account_number: {
      label: 'Account Number', required: true,
      check: function (v) { return RE.account.test(v) ? '' : 'Enter a valid account number (9-18 digits)'; }
    },
    confirm_account_number: { label: 'Confirm Account Number', required: true },
    account_holder_name: {
      label: "Account Holder's Name", required: true,
      check: function (v) { return RE.name.test(v) ? '' : 'Name may contain only letters'; }
    },
    ifsc: {
      label: 'IFSC Code', required: true, upper: true,
      check: function (v) { return RE.ifsc.test(v) ? '' : 'Invalid IFSC code. Example: SBIN0001234'; }
    },
    branch: { label: 'Branch Name', required: true },
    bank_name: { label: 'Bank Name', required: true },
    status: { label: 'Status', required: true }
  };

  /* ======================================================================
     Small DOM helpers
     ====================================================================== */

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // Rendered, ignoring the fact that its own step may be the hidden one -
  // validateAll() has to look at every step while still skipping fields a
  // toggle has switched off (the GST number when "not GST registered" is on).
  function isActiveWithinStep(el) {
    var node = el;
    while (node && node !== form) {
      if (node.hasAttribute && node.hasAttribute('hidden') && !node.hasAttribute('data-czp-step')) return false;
      if (node.style && node.style.display === 'none') return false;
      node = node.parentElement;
    }
    return true;
  }

  function fieldWrap(el) { return el.closest('[data-czp-field]'); }

  function setFieldError(wrap, message) {
    if (!wrap) return;
    var slot = $('[data-czp-error]', wrap);
    wrap.classList.toggle('has-error', !!message);
    if (!slot) return;
    slot.textContent = message || '';
    slot.classList.toggle('is-shown', !!message);
  }

  function focusable(wrap) {
    return $('input:not([type="hidden"]), select, textarea', wrap) || $('label.czp-drop', wrap) || wrap;
  }

  function labelOf(wrap) {
    var l = $('.czp-label', wrap) || $('legend', wrap);
    return l ? l.textContent.replace('*', '').trim() : (wrap.getAttribute('data-czp-field') || 'This field');
  }

  /* ======================================================================
     Steps
     ====================================================================== */

  var steps = $$('[data-czp-step]', form).map(function (el, i) {
    return {
      index: i,
      key: el.getAttribute('data-czp-step'),
      label: el.getAttribute('data-czp-label') || ('Step ' + (i + 1)),
      el: el,
      rail: $('[data-czp-goto="' + i + '"]'),
      alert: $('[data-czp-alert]', el)
    };
  });
  if (!steps.length) return;

  var current = 0;
  // Only steps that have actually been validated get a rail badge - marking an
  // untouched step red on load would be nagging, not helping.
  var attempted = {};
  var counter = $('[data-czp-step-count]');

  function stepOfName(name) {
    var wrap = $('[data-czp-field="' + name + '"]', form);
    if (!wrap) return null;
    var stepEl = wrap.closest('[data-czp-step]');
    for (var i = 0; i < steps.length; i++) if (steps[i].el === stepEl) return steps[i];
    return null;
  }

  function showStep(index, opts) {
    opts = opts || {};
    if (index < 0 || index >= steps.length) return;
    current = index;
    steps.forEach(function (s, i) { s.el.hidden = (i !== index); });
    refreshRail();
    if (counter) counter.textContent = 'Step ' + (index + 1) + ' of ' + steps.length + ' · ' + steps[index].label;
    var back = $('[data-czp-back]');
    var next = $('[data-czp-next]');
    if (back) back.disabled = (index === 0);
    if (next) next.disabled = (index === steps.length - 1);
    if (opts.scroll !== false) scrollToCard();
  }

  function refreshRail() {
    steps.forEach(function (s, i) {
      if (!s.rail) return;
      var bad = attempted[i] && !checkStep(s, { render: false }).ok;
      s.rail.classList.toggle('is-active', i === current);
      s.rail.classList.toggle('is-invalid', !!bad && i !== current);
      s.rail.classList.toggle('is-done', !bad && !!attempted[i] && i !== current);
      s.rail.setAttribute('aria-current', i === current ? 'step' : 'false');
    });
  }

  function scrollToCard() {
    var card = $('.czp-card');
    if (!card) return;
    var top = Math.max(0, card.getBoundingClientRect().top + window.pageYOffset - 80);
    try { window.scrollTo({ top: top, behavior: 'smooth' }); }
    catch (e) { window.scrollTo(0, top); }
  }

  /* ======================================================================
     Validation
     ====================================================================== */

  function validateControl(control) {
    var rule = RULES[control.name];
    if (!rule) return '';

    if (rule.upper && control.value.trim()) control.value = control.value.trim().toUpperCase();
    var value = control.value.trim();

    if (!value) {
      if (!rule.required) return '';
      // The label is used as written, not lower-cased: half of these are
      // acronyms, and "Please enter gst enrollment id" reads like a typo.
      var verb = (control.tagName === 'SELECT') ? 'Please select ' : 'Please enter ';
      return verb + rule.label;
    }
    return rule.check ? rule.check(value) : '';
  }

  function validateFile(wrap) {
    var input = $('input[type="file"]', wrap);
    if (!input) return '';
    var label = wrap.getAttribute('data-czp-file-label') || 'file';
    var required = wrap.hasAttribute('data-czp-file-required');
    var oldInput = form.querySelector('input[name="old_' + input.name + '"]');
    var hasExisting = !!(oldInput && oldInput.value.trim() !== '');

    if (!input.files || !input.files.length) {
      // Nothing new chosen: fine as long as something is already on record.
      // The x button clears old_<name>, so removing a required document
      // without replacing it does fail here.
      if (required && !hasExisting) return 'Please upload ' + label;
      return '';
    }

    var f = input.files[0];
    var ext = (f.name.split('.').pop() || '').toLowerCase();
    // A photo or a logo has to be an image: the server would take a PDF (one
    // allowed_types list covers every upload on this form) but nothing can
    // render it. Documents take either.
    var imagesOnly = wrap.hasAttribute('data-czp-file-images');
    var allowed = imagesOnly ? ['jpg', 'jpeg', 'png', 'gif'] : ALLOWED_EXT;
    if (allowed.indexOf(ext) === -1) {
      return label + (imagesOnly
        ? ' must be a JPG, JPEG, PNG or GIF image'
        : ' must be a JPG, JPEG, PNG, GIF or PDF file');
    }
    if (f.size === 0) return 'That file is empty. Please choose another one.';
    if (f.size > MAX_FILE_KB * 1024) {
      return label + ' must be under ' + MAX_FILE_KB / 1000 + ' MB (this one is ' +
        (f.size / (1024 * 1024)).toFixed(1) + ' MB)';
    }
    return '';
  }

  /**
   * Validate one step.
   *
   * @param {object} step
   * @param {object} opts  render:false to test quietly (the rail badges do this)
   * @returns {{ok:boolean, errors:Array, firstWrap:Element|null}}
   */
  function checkStep(step, opts) {
    opts = opts || {};
    var render = opts.render !== false;
    var errors = [];
    var firstWrap = null;

    function fail(wrap, message) {
      if (render) setFieldError(wrap, message);
      errors.push({ label: labelOf(wrap), message: message, wrap: wrap });
      if (!firstWrap) firstWrap = wrap;
    }

    $$('[data-czp-field]', step.el).forEach(function (wrap) {
      if (!isActiveWithinStep(wrap)) {
        if (render) setFieldError(wrap, '');
        return;
      }

      var message = '';
      if (wrap.hasAttribute('data-czp-file-label')) {
        message = validateFile(wrap);
      } else if (wrap.hasAttribute('data-czp-radio')) {
        // Status: a radio group has no single control to read.
        var checked = $('input[type="radio"]:checked', wrap);
        if (!checked) message = 'Please choose a status';
      } else {
        // [data-czp-control] names the control that actually carries the value
        // when it is not the visible one - Bank Name is typed into a search box
        // and stored in a hidden input, and it is the hidden one the rules and
        // the server both care about.
        var control = $('[data-czp-control]', wrap) ||
          $('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea', wrap);
        if (control) message = validateControl(control);
      }

      // A pending or failed server-side uniqueness answer counts as an error
      // too - see contactState below.
      if (!message) {
        var live = $('input[data-czp-contact]', wrap);
        if (live) {
          var st = contactState[live.getAttribute('data-czp-contact')];
          if (st && st.value === live.value.trim() && !st.valid) message = st.message;
        }
      }

      if (render) setFieldError(wrap, message);
      if (message) {
        errors.push({ label: labelOf(wrap), message: message, wrap: wrap });
        if (!firstWrap) firstWrap = wrap;
      }
    });

    // Cross-field: the two account numbers, and the two passwords, must match.
    var pairs = [
      ['account_number', 'confirm_account_number', 'Account numbers do not match'],
      ['password', 'confirm_password', 'Passwords do not match']
    ];
    pairs.forEach(function (pair) {
      var a = $('[name="' + pair[0] + '"]', step.el);
      var b = $('[name="' + pair[1] + '"]', step.el);
      if (!a || !b) return;
      if (a.value.trim() && b.value.trim() && a.value.trim() !== b.value.trim()) {
        fail(fieldWrap(b), pair[2]);
      }
    });

    if (render) renderStepAlert(step, errors);
    return { ok: errors.length === 0, errors: errors, firstWrap: firstWrap };
  }

  // The per-step summary banner, at the top of the step being looked at, so
  // nothing about a failed save happens off-screen.
  function renderStepAlert(step, errors) {
    if (!step.alert) return;
    if (!errors.length) {
      step.alert.hidden = true;
      step.alert.innerHTML = '';
      return;
    }
    var items = errors.slice(0, 8).map(function (e) {
      return '<li><b style="display:inline">' + esc(e.label) + ':</b> ' + esc(e.message) + '</li>';
    }).join('');
    var more = errors.length > 8 ? '<li>and ' + (errors.length - 8) + ' more below</li>' : '';
    step.alert.className = 'czp-alert czp-alert-error';
    step.alert.innerHTML =
      '<b>' + errors.length + (errors.length === 1 ? ' field needs' : ' fields need') +
      ' attention on this step</b><ul>' + items + more + '</ul>';
    step.alert.hidden = false;
  }

  function failStep(step, result) {
    attempted[step.index] = true;
    if (current !== step.index) showStep(step.index);
    else refreshRail();
    if (result.firstWrap) {
      var target = focusable(result.firstWrap);
      setTimeout(function () {
        try { target.focus({ preventScroll: true }); } catch (e) { try { target.focus(); } catch (e2) {} }
        result.firstWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 60);
    }
  }

  /* ======================================================================
     Navigation - free, on purpose (see the header note)
     ====================================================================== */

  function goTo(index, opts) {
    if (index === current) return;
    showStep(index, opts);
  }

  $$('[data-czp-goto]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      goTo(parseInt(btn.getAttribute('data-czp-goto'), 10));
    });
  });

  var backBtn = $('[data-czp-back]');
  var nextBtn = $('[data-czp-next]');
  if (backBtn) backBtn.addEventListener('click', function (e) { e.preventDefault(); goTo(current - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function (e) { e.preventDefault(); goTo(current + 1); });

  // Used by the verification panel's "Fix this" links.
  window.openSellerFormSection = function (key) {
    for (var i = 0; i < steps.length; i++) {
      if (steps[i].key === key) { showStep(i); return; }
    }
  };

  /* ======================================================================
     Live per-field feedback
     ====================================================================== */

  // Clear a field's error the moment it is being fixed, so the page never
  // argues with what is now on screen. File inputs are excluded: their own
  // change handler validates the chosen file and writes into this same slot.
  form.addEventListener('input', clearOnEdit);
  form.addEventListener('change', clearOnEdit);
  function clearOnEdit(e) {
    var target = e.target;
    if (!target || !target.closest) return;
    if (target.type === 'file') return;
    var wrap = fieldWrap(target);
    if (wrap) setFieldError(wrap, '');
  }

  // Digits only for the numeric fields, including pastes and Android keyboards
  // (which ignore keypress handlers entirely).
  $$('[data-czp-digits]', form).forEach(function (input) {
    var max = parseInt(input.getAttribute('data-czp-digits'), 10) || 10;
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('maxlength', String(max));
    input.addEventListener('input', function () {
      var cleaned = this.value.replace(/\D/g, '').slice(0, max);
      if (cleaned !== this.value) this.value = cleaned;
    });
  });

  // Validate on blur so problems surface field by field rather than all at
  // once on Save.
  $$('input:not([type="hidden"]):not([type="file"]):not([type="checkbox"]):not([type="radio"]), select, textarea', form)
    .forEach(function (control) {
      if (!RULES[control.name]) return;
      control.addEventListener('blur', function () {
        var wrap = fieldWrap(control);
        if (!wrap || !isActiveWithinStep(wrap)) return;
        setFieldError(wrap, validateControl(control));
      });
    });

  /* ======================================================================
     Uploads: preview, remove, inline validation
     ====================================================================== */

  function docParts(wrap) {
    return {
      input: $('input[type="file"]', wrap),
      drop: $('.czp-drop', wrap),
      box: $('[data-czp-doc]', wrap),
      thumb: $('[data-czp-doc-thumb]', wrap),
      badge: $('[data-czp-doc-file]', wrap),
      nameEl: $('[data-czp-doc-name]', wrap),
      link: $('[data-czp-doc-link]', wrap),
      remove: $('[data-czp-doc-remove]', wrap),
      hint: $('[data-czp-doc-hint]', wrap)
    };
  }

  function showChosen(wrap, file) {
    var p = docParts(wrap);
    var isPdf = /\.pdf$/i.test(file.name) || file.type === 'application/pdf';
    if (p.nameEl) p.nameEl.textContent = file.name;
    if (p.hint) p.hint.hidden = true;
    if (p.badge) p.badge.hidden = !isPdf;
    if (p.thumb) p.thumb.hidden = true;
    if (p.link) {
      // The picked file is not saved yet and ekko-lightbox resolves the type
      // from the href, which a data: URL is not. Drop the lightbox hook until
      // the save reloads the page with a real path.
      p.link.removeAttribute('href');
      p.link.removeAttribute('data-toggle');
    }
    if (p.box) p.box.hidden = false;

    if (!isPdf && /^image\//.test(file.type)) {
      var reader = new FileReader();
      reader.onload = function (ev) {
        if (p.thumb) { p.thumb.src = ev.target.result; p.thumb.hidden = false; }
      };
      reader.readAsDataURL(file);
    }
  }

  $$('[data-czp-file-label]', form).forEach(function (wrap) {
    var p = docParts(wrap);
    if (!p.input) return;

    p.input.addEventListener('change', function () {
      var f = this.files && this.files[0];
      if (!f) return;
      // Validated the moment the file is picked, with the picker still fresh
      // in mind - not on the last step.
      var message = validateFile(wrap);
      setFieldError(wrap, message);
      if (message) {
        this.value = '';
        if (p.box) p.box.hidden = true;
        return;
      }
      showChosen(wrap, f);
    });

    if (p.drop) {
      ['dragenter', 'dragover'].forEach(function (ev) {
        p.drop.addEventListener(ev, function (e) { e.preventDefault(); p.drop.classList.add('is-dragover'); });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        p.drop.addEventListener(ev, function () { p.drop.classList.remove('is-dragover'); });
      });
      p.drop.addEventListener('drop', function (e) {
        e.preventDefault();
        var dt = e.dataTransfer;
        if (!dt || !dt.files || !dt.files.length) return;
        try {
          p.input.files = dt.files;
          p.input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (err) { /* older browsers: click-to-pick still works */ }
      });
    }

    if (p.remove) {
      p.remove.addEventListener('click', function () {
        if (!confirm('Remove this file? A new one has to be uploaded to replace it.')) return;
        p.input.value = '';
        var oldInput = form.querySelector('input[name="old_' + p.input.name + '"]');
        if (oldInput) oldInput.value = '';
        if (p.box) p.box.hidden = true;
        if (p.hint) p.hint.hidden = true;
        // Removing a required document is worth saying out loud right away.
        setFieldError(wrap, validateFile(wrap));
      });
    }
  });

  /* ---- Round avatar pickers (seller photo, shop logo) ------------------ */
  $$('[data-czp-avatar]', form).forEach(function (box) {
    var wrapEl = box.closest('[data-czp-field]') || box.parentElement;
    var input = $('input[type="file"]', wrapEl);
    var img = $('[data-czp-avatar-img]', box);
    var icon = $('[data-czp-avatar-icon]', box);
    if (!input) return;

    box.addEventListener('click', function () { input.click(); });
    box.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });
    input.addEventListener('change', function () {
      var f = this.files && this.files[0];
      if (!f) return;
      var wrap = fieldWrap(this);
      var message = validateFile(wrap);
      setFieldError(wrap, message);
      if (message) { this.value = ''; return; }
      if (!/^image\//.test(f.type)) return; // a PDF is allowed but cannot be previewed
      var reader = new FileReader();
      reader.onload = function (ev) {
        if (img) { img.src = ev.target.result; img.removeAttribute('hidden'); }
        // setAttribute, NOT `.hidden = true`: the placeholder is an <svg>, and
        // `hidden` is an HTMLElement property SVGElement does not implement -
        // assigning it would leave the silhouette showing under the new photo.
        if (icon) icon.setAttribute('hidden', 'hidden');
      };
      reader.readAsDataURL(f);
    });
  });

  /* ======================================================================
     Entity type / GST toggles
     ====================================================================== */

  (function () {
    var entityType = $('#entity_type');
    var gstCheck = $('#gst_check');
    if (!entityType || !gstCheck) return;

    var PAN_LABELS = {
      individual: 'PAN Number',
      sole_proprietorship: "Proprietor's PAN Number",
      partnership_firm: "Firm's PAN Number"
    };
    var LEGAL_NAME_LABELS = { partnership_firm: "Legal Firm's Name" };
    var PROOF_HINTS = {
      sole_proprietorship: 'Udyam/MSME Certificate',
      partnership_firm: 'Partnership deed, Firm PAN card, or Udyam/MSME Certificate'
    };

    // A hidden field must stop being required AND stop being validated -
    // otherwise the save is blocked by a rule about a field nobody can see.
    // This mirrors add_seller()'s own $doc_required map exactly.
    function setActive(wrap, active) {
      if (!wrap) return;
      wrap.hidden = !active;
      if (!active) setFieldError(wrap, '');
    }

    function sync() {
      var type = entityType.value;
      var nonGst = gstCheck.checked;

      setActive($('[data-czp-field="gst"]'), !nonGst);
      setActive($('[data-czp-field="gstin_document"]'), !nonGst);
      setActive($('[data-czp-field="gst_enrollment_number"]'), nonGst);
      setActive($('[data-czp-field="gst_enrollment_ack_document"]'), nonGst);

      setActive($('[data-czp-field="partnership_deed_document"]'), type === 'partnership_firm');

      var wantsProof = nonGst && type !== 'individual' && type !== '';
      setActive($('[data-czp-field="business_proof_document"]'), wantsProof);
      setActive($('[data-czp-field="business_address_proof_document"]'), wantsProof);

      var proofHint = $('#business_proof_document_hint_extra');
      if (proofHint) {
        proofHint.textContent = PROOF_HINTS[type] || '';
        proofHint.hidden = !PROOF_HINTS[type];
      }

      var panLabel = $('#pan_label');
      if (panLabel) panLabel.innerHTML = esc(PAN_LABELS[type] || 'PAN Number') + ' <i class="czp-req">*</i>';

      var legalLabel = $('#legal_business_name_label');
      if (legalLabel) legalLabel.textContent = LEGAL_NAME_LABELS[type] || 'Legal Business Name';

      autofillLegalName();
      refreshRail();
    }

    // For an Individual the legal business name IS the seller's own name.
    // Fill it, but never over the top of something typed by hand:
    // data-autofilled marks a value this code put there, a keystroke clears it.
    function autofillLegalName() {
      var input = $('#legal_business_name_input');
      if (!input || entityType.value !== 'individual') return;
      if (input.value.trim() && input.dataset.autofilled !== '1') return;
      var first = $('[name="first_name"]', form);
      var last = $('[name="last_name"]', form);
      var full = [first ? first.value.trim() : '', last ? last.value.trim() : ''].filter(Boolean).join(' ');
      if (!full) return;
      input.value = full;
      input.dataset.autofilled = '1';
    }

    var legalInput = $('#legal_business_name_input');
    if (legalInput) legalInput.addEventListener('input', function () { legalInput.dataset.autofilled = ''; });

    // "individual" is the default, so entity_type never fires a change event
    // for the common case of a new seller's name being typed after load.
    ['first_name', 'last_name'].forEach(function (n) {
      var el = $('[name="' + n + '"]', form);
      if (el) el.addEventListener('blur', autofillLegalName);
    });

    entityType.addEventListener('change', sync);
    gstCheck.addEventListener('change', sync);
    sync();
  })();

  /* ======================================================================
     Status segments + permission switches: keep the visual state in step
     ====================================================================== */

  (function () {
    function paintRadios() {
      $$('.czp-seg-item', form).forEach(function (item) {
        var radio = $('input[type="radio"]', item);
        item.classList.toggle('is-on', !!(radio && radio.checked));
      });
    }
    function paintSwitches() {
      $$('.czp-switch', form).forEach(function (item) {
        var box = $('input[type="checkbox"]', item);
        item.classList.toggle('is-on', !!(box && box.checked));
      });
    }
    form.addEventListener('change', function (e) {
      if (!e.target) return;
      if (e.target.type === 'radio') paintRadios();
      if (e.target.type === 'checkbox') paintSwitches();
    });
    paintRadios();
    paintSwitches();
  })();

  /* ======================================================================
     Pincode -> state / district / city
     ====================================================================== */

  function setStatus(el, message, kind) {
    if (!el) return;
    el.textContent = message || '';
    el.className = 'czp-status' + (kind ? ' is-' + kind : '');
  }

  function bindPincode(opts) {
    var pin = $('#' + opts.pin);
    var status = $('#' + opts.status);
    if (!pin) return;
    var timer = null;
    var latest = '';

    function firstMeaningful() {
      for (var i = 0; i < arguments.length; i++) {
        var v = (arguments[i] || '').toString().trim();
        if (v && !/^(na|nil|none)$/i.test(v)) return v;
      }
      return '';
    }
    function indiaPost(code) {
      return fetch('https://api.postalpincode.in/pincode/' + encodeURIComponent(code))
        .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
        .then(function (data) {
          var rec = Array.isArray(data) ? data[0] : null;
          var offices = (rec && Array.isArray(rec.PostOffice)) ? rec.PostOffice : [];
          if (!rec || rec.Status !== 'Success' || !offices.length) throw new Error('not found');
          var po = offices[0];
          return { state: po.State || '', district: po.District || '', city: firstMeaningful(po.Block, po.Name, po.District) };
        });
    }
    function zippopotam(code) {
      return fetch('https://api.zippopotam.us/in/' + encodeURIComponent(code))
        .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
        .then(function (data) {
          var place = (Array.isArray(data.places) ? data.places : [])[0] || {};
          var city = place['place name'] || place.city || '';
          var state = place.state || place['state name'] || '';
          if (!city && !state) throw new Error('empty');
          return { state: state, district: place.district || city, city: city };
        });
    }
    function fill(id, value) {
      var el = $('#' + id);
      if (!el || !value) return;
      el.value = value;
      setFieldError(fieldWrap(el), '');
    }
    function lookup() {
      var code = pin.value.replace(/\D/g, '').slice(0, 6);
      pin.value = code;
      if (code.length < 6) { latest = ''; setStatus(status, '', ''); return; }
      latest = code;
      setStatus(status, 'Looking up state, district and city…', 'info');
      indiaPost(code)
        .catch(function () { return zippopotam(code); })
        .then(function (loc) {
          if (latest !== code) return; // stale: the admin kept typing
          fill(opts.state, loc.state);
          fill(opts.district, loc.district);
          fill(opts.city, loc.city);
          setStatus(status, 'State, district and city filled from the PIN code.', 'success');
        })
        .catch(function () {
          if (latest !== code) return;
          setStatus(status, 'Could not look up this PIN code - please fill the three fields in by hand.', 'info');
        });
    }
    pin.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(lookup, 400); });
    pin.addEventListener('blur', lookup);
  }

  bindPincode({ pin: 'pin', state: 'state', district: 'district', city: 'seller_city', status: 'pin_status' });
  bindPincode({ pin: 'pickup_pin', state: 'pickup_state', district: 'pickup_district', city: 'pickup_city', status: 'pickup_pin_status' });
  bindPincode({ pin: 'business_pin', state: 'business_state', district: 'business_district', city: 'business_city', status: 'business_pin_status' });

  /* ======================================================================
     Store URL placeholder mirrors Shop Name
     ====================================================================== */

  (function () {
    var shop = $('[name="shop_name"]', form);
    var slug = $('#slug_input');
    if (!shop || !slug) return;
    shop.addEventListener('input', function () {
      slug.placeholder = shop.value.trim() || 'your-shop-name';
    });
  })();

  /* ======================================================================
     Bank name combo box
     ====================================================================== */

  (function () {
    var search = $('#bank_search');
    var hidden = $('#bank_name_hidden');
    var list = $('#bank_dropdown');
    var banks = CFG.banks || [];
    if (!search || !hidden || !list) return;

    if (hidden.value) search.value = hidden.value;

    function render(items) {
      list.innerHTML = '';
      if (!items.length) { list.classList.remove('is-open'); return; }
      items.slice(0, 60).forEach(function (label) {
        var row = document.createElement('div');
        row.textContent = label;
        row.addEventListener('mousedown', function (e) {
          e.preventDefault();
          search.value = label;
          hidden.value = label;
          list.classList.remove('is-open');
          setFieldError(fieldWrap(hidden), '');
        });
        list.appendChild(row);
      });
      list.classList.add('is-open');
    }

    search.addEventListener('input', function () {
      // The typed text IS the value - a bank missing from the table must still
      // be enterable.
      hidden.value = this.value.trim();
      var q = this.value.trim().toLowerCase();
      if (!q) { list.classList.remove('is-open'); return; }
      render(banks.filter(function (b) { return b.toLowerCase().indexOf(q) !== -1; }));
    });
    search.addEventListener('focus', function () {
      if (this.value.trim()) return;
      render(banks);
    });
    // The hidden input is what the rules read, so the visible box hands the
    // blur check over to it.
    search.addEventListener('blur', function () {
      setFieldError(fieldWrap(hidden), validateControl(hidden));
    });
    document.addEventListener('click', function (e) {
      if (e.target !== search) list.classList.remove('is-open');
    });
  })();

  /* ======================================================================
     Secondary categories picker
     ====================================================================== */

  (function () {
    var modal = $('#category_modal');
    var openBtn = $('#category_open');
    var hidden = $('#secondary_category_ids');
    var pills = $('#category_pills');
    var primary = $('#primary_category_id');
    if (!modal || !openBtn || !hidden || !pills || !primary) return;

    var boxes = $$('.czp-pick input[type="checkbox"]', modal);
    var parentOf = {};
    var labelOfId = {};
    boxes.forEach(function (b) {
      parentOf[b.value] = b.closest('.czp-pick').getAttribute('data-parent');
      labelOfId[b.value] = b.closest('.czp-pick').getAttribute('data-label');
    });

    function ids() {
      return hidden.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    }

    function renderPills() {
      pills.innerHTML = '';
      var list = ids();
      if (!list.length) {
        pills.innerHTML = '<span class="czp-hint">None selected yet.</span>';
        return;
      }
      list.forEach(function (id) {
        var pill = document.createElement('span');
        pill.className = 'czp-pill';
        pill.appendChild(document.createTextNode(labelOfId[id] || ('#' + id)));
        var x = document.createElement('button');
        x.type = 'button';
        x.setAttribute('aria-label', 'Remove');
        x.textContent = '×';
        x.addEventListener('click', function () {
          hidden.value = ids().filter(function (v) { return v !== id; }).join(',');
          renderPills();
        });
        pill.appendChild(x);
        pills.appendChild(pill);
      });
    }

    // Only sub-categories of the chosen primary category may be picked.
    function filterToPrimary() {
      var pid = primary.value;
      var any = false;
      $$('.czp-pick', modal).forEach(function (row) {
        var show = pid !== '' && row.getAttribute('data-parent') === pid;
        row.hidden = !show;
        if (show) any = true;
      });
      var empty = $('#category_empty', modal);
      if (empty) {
        empty.hidden = any;
        empty.textContent = primary.value === ''
          ? 'Choose a Primary Product Category first.'
          : 'This category has no sub-categories to add.';
      }
    }

    function prune() {
      var pid = primary.value;
      hidden.value = ids().filter(function (id) { return parentOf[id] === pid; }).join(',');
      renderPills();
    }

    openBtn.addEventListener('click', function () {
      if (primary.value === '') {
        setFieldError($('[data-czp-field="primary_category_id"]'), 'Choose a Primary Product Category first');
        return;
      }
      var chosen = ids();
      boxes.forEach(function (b) { b.checked = chosen.indexOf(b.value) !== -1; });
      filterToPrimary();
      modal.classList.add('is-open');
    });

    function close() { modal.classList.remove('is-open'); }
    $$('[data-czp-modal-close]', modal).forEach(function (b) { b.addEventListener('click', close); });
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
    });

    var doneBtn = $('#category_done', modal);
    if (doneBtn) doneBtn.addEventListener('click', function () {
      hidden.value = boxes.filter(function (b) {
        return b.checked && !b.closest('.czp-pick').hidden;
      }).map(function (b) { return b.value; }).join(',');
      renderPills();
      close();
    });

    primary.addEventListener('change', function () { prune(); filterToPrimary(); });

    // Secondary categories only mean anything under a chosen primary, so drop
    // whatever a pre-primary save left behind in category_ids.
    if (primary.value === '') hidden.value = '';
    filterToPrimary();
    renderPills();
  })();

  /* ======================================================================
     Phone / shop phone / email: live uniqueness check
     ====================================================================== */

  function checkContact(field) {
    var input = $('[data-czp-contact="' + field + '"]', form);
    if (!input || !CFG.checkContactUrl) return;
    var wrap = fieldWrap(input);
    var value = input.value.trim();
    if (!value) { contactState[field] = null; return; }

    var format = validateControl(input);
    if (format) {
      contactState[field] = { value: value, valid: false, message: format };
      setFieldError(wrap, format);
      return;
    }

    var body = new FormData();
    body.append('field', field);
    body.append('value', value);
    // A seller may reuse their own personal number as the shop number, so the
    // server needs both to tell "mine" from "another account's"...
    var phone = $('[data-czp-contact="phone"]', form);
    body.append('phone', phone ? phone.value : '');
    // ...and the id being edited, so the seller's own row is not a clash.
    body.append('edit_seller', CFG.editSellerId || 0);

    fetch(CFG.checkContactUrl, { method: 'POST', body: body })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (input.value.trim() !== value) return; // stale: still typing
        if (data.csrfHash && window.CSRF) window.CSRF.update(data.csrfName, data.csrfHash);
        contactState[field] = { value: value, valid: !!data.valid, message: data.message || '' };
        setFieldError(wrap, data.valid ? '' : (data.message || ''));
        refreshRail();
      })
      .catch(function () { contactState[field] = null; });
  }

  ['phone', 'shop_phone', 'email'].forEach(function (field) {
    var input = $('[data-czp-contact="' + field + '"]', form);
    if (!input) return;
    input.addEventListener('input', function () { contactState[field] = null; });
    input.addEventListener('blur', function () { checkContact(field); });
    // Anything already saved is checked once, so a clash on an old record is
    // visible before the field is touched.
    if (input.value.trim()) checkContact(field);
  });

  /* ======================================================================
     Save
     ====================================================================== */

  // Server rejections that name a field, mapped back to that field. Without
  // this, "Shop Name already exists." arrives as an anonymous toast while the
  // admin is looking at a completely different step.
  var SERVER_FIELD_HINTS = [
    { re: /shop name already exists/i, field: 'shop_name' },
    { re: /store url already exists/i, field: 'slug' },
    { re: /\bGST Enrollment (ID|Number)\b/i, field: 'gst_enrollment_number' },
    { re: /\bGST Number\b/i, field: 'gst' },
    { re: /\bPAN Number\b/i, field: 'pan' },
    { re: /\bIFSC\b/i, field: 'ifsc' },
    { re: /\bConfirm Account Number\b/i, field: 'confirm_account_number' },
    { re: /\bAccount Number\b/i, field: 'account_number' },
    { re: /\bShop Phone\b/i, field: 'shop_phone' },
    { re: /\bPhone\b/i, field: 'phone' },
    { re: /\bEmail\b/i, field: 'email' },
    { re: /\bPassword\b/i, field: 'password' },
    { re: /\bIdentity Proof\b|\bNational Identity\b/i, field: 'national_identity_card' },
    { re: /\bAuthorized Signat/i, field: 'authorized_signature' },
    { re: /\bPan Card\b/i, field: 'pan_card_document' }
  ];

  function routeServerError(message) {
    var text = String(message || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    if (!text) text = 'Something went wrong. Please try again.';

    for (var i = 0; i < SERVER_FIELD_HINTS.length; i++) {
      if (!SERVER_FIELD_HINTS[i].re.test(text)) continue;
      var name = SERVER_FIELD_HINTS[i].field;
      var step = stepOfName(name);
      if (!step) continue;
      var wrap = $('[data-czp-field="' + name + '"]', form);
      setFieldError(wrap, text);
      attempted[step.index] = true;
      renderStepAlert(step, [{ label: labelOf(wrap), message: text, wrap: wrap }]);
      failStep(step, { firstWrap: wrap });
      return text;
    }

    // Not attributable to one field: show it on the step being looked at, so
    // it cannot scroll past unseen.
    var here = steps[current];
    if (here && here.alert) {
      here.alert.className = 'czp-alert czp-alert-error';
      here.alert.innerHTML = '<b>The server could not save this seller</b>' + esc(text);
      here.alert.hidden = false;
    }
    return text;
  }

  // iziToast is what every other admin save uses, so prefer it and keep the
  // seller form's own toast strictly as the fallback - showing both put the
  // same sentence on screen twice.
  function toast(kind, message) {
    if (typeof iziToast !== 'undefined') {
      if (kind === 'success') iziToast.success({ message: message });
      else iziToast.error({ message: message });
      return;
    }
    var el = $('#czp_toast');
    if (!el) return;
    el.className = 'czp-toast is-shown is-' + kind;
    el.textContent = message;
    clearTimeout(window.asfToastTimer);
    window.asfToastTimer = setTimeout(function () { el.className = 'czp-toast'; }, 6000);
  }

  var saveBtn = $('#submit_btn');
  var errorBox = $('#error_box');

  function setBoxMessage(kind, html) {
    if (!errorBox) return;
    errorBox.className = kind === 'success' ? 'msg_success rounded p-3' : 'msg_error rounded p-3';
    errorBox.innerHTML = html;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!saveBtn || saveBtn.disabled) return;

    // Every step, in order. The first one with a problem is where the admin is
    // taken, so no error surfaces for the first time after the page reloads.
    for (var i = 0; i < steps.length; i++) {
      attempted[i] = true;
      var result = checkStep(steps[i]);
      if (!result.ok) {
        failStep(steps[i], result);
        toast('error', 'Please fix the highlighted fields on "' + steps[i].label + '" first.');
        return;
      }
    }
    refreshRail();

    var original = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = 'Saving…';
    if (errorBox) { errorBox.className = ''; errorBox.innerHTML = ''; }

    // csrf-guard.js stamps the token onto any FormData sent through fetch, and
    // adopts the refreshed pair from the reply below.
    fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form) })
      .then(function (res) {
        return res.text().then(function (text) {
          try { return JSON.parse(text.replace(/<!--[\s\S]*?-->/g, '').trim()); }
          catch (err) {
            console.error('Seller save returned non-JSON:', text);
            throw new Error(res.status === 403
              ? 'Your session has expired. Please reload the page and try again.'
              : 'Invalid JSON response');
          }
        });
      })
      .then(function (data) {
        if (data && (data.csrfHash || data.csrfName) && window.CSRF) {
          window.CSRF.update(data.csrfName, data.csrfHash);
        }

        if (data && data.error === false) {
          var okMessage = data.message || 'Seller saved.';
          setBoxMessage('success', esc(okMessage));
          toast('success', okMessage);
          saveBtn.innerHTML = 'Saved';
          // Adding: go to the list, where the new seller is now visible.
          // Editing: reload, so the document previews, the verification panel
          // and the status pill all reflect what was just written.
          setTimeout(function () {
            if (CFG.isEdit) window.location.reload();
            else window.location.href = CFG.listUrl || window.location.href;
          }, 900);
          return;
        }

        saveBtn.disabled = false;
        saveBtn.innerHTML = original;
        var text = routeServerError(data && data.message);
        setBoxMessage('error', esc(text));
        toast('error', text);
      })
      .catch(function (err) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = original;
        var text = (err && err.message === 'Invalid JSON response')
          ? 'The server sent back something unexpected. Please try again.'
          : (err && err.message) || 'Something went wrong. Please try again.';
        setBoxMessage('error', esc(text));
        toast('error', text);
        console.error('Seller save failed:', err);
      });
  });

  // "Discard changes" reloads instead of firing a native reset: a reset would
  // blank the fields of a saved seller rather than putting back what is on
  // record, which looks like data loss.
  var discardBtn = $('[data-czp-discard]');
  if (discardBtn) discardBtn.addEventListener('click', function (e) {
    e.preventDefault();
    if (confirm('Discard unsaved changes and reload this seller?')) window.location.reload();
  });

  /* ======================================================================
     Boot
     ====================================================================== */

  var startIndex = 0;
  for (var s = 0; s < steps.length; s++) {
    if (steps[s].key === CFG.initialStep) { startIndex = s; break; }
  }
  showStep(startIndex, { scroll: false });
})();
