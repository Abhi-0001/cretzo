/* ==========================================================================
   Seller Profile - step engine, validation, uploads, submit
   --------------------------------------------------------------------------
   Replaces assets/seller/js/cretzo/form.js for this page. What changed and why:

   1. Errors are shown ON THE STEP THE FIELD LIVES ON, when the seller presses
      Continue - never as a bare toast on the last (Review) step. The old flow
      only validated step 3 on Submit, so anything wrong on steps 1-2 (a file
      of the wrong type especially) got as far as the server and came back as
      one anonymous toast while the seller was looking at Review, with no clue
      which field it meant. Submit now re-validates EVERY step and, on failure,
      jumps to the first step that has a problem.

   2. File rules match the server exactly: seller/Login::update_user() uploads
      with allowed_types 'jpg|png|jpeg|gif|pdf' and max_size 8000 (KB). The old
      client rules demanded an image for the identity proof / signature / photo
      / logo even though the inputs advertise PDF and the server accepts it, and
      used an 8 MB (8192 KB) ceiling against the server's 8000 KB - so a PDF was
      wrongly blocked and a 8000-8192 KB file was wrongly let through, only to
      be rejected server-side on the last step.

   3. A server rejection that names a field ("Shop Name already exists.",
      "PAN Number is already registered...") is routed back to that field's own
      error slot on its own step, instead of vanishing into a toast.

   Every field lives in a [data-czp-field="<name>"] wrapper holding its own
   [data-czp-error] slot, so a message can only ever render in its own grid
   cell - it can never be appended into a neighbouring column.
   ========================================================================== */

(function () {
  'use strict';

  var CFG = window.CZP_CONFIG || {};
  var form = document.getElementById('seller_form');
  if (!form) return;

  /* ======================================================================
     Draft: restore what the seller had typed last time
     ======================================================================
     Runs BEFORE every widget below is wired, on purpose: the bank combo, the
     category pills and the entity / GST toggles all read their control's value
     once at start-up, so putting the draft into the controls first means they
     build themselves from it with no extra syncing. Files are never part of a
     draft - a browser cannot hand a chosen file back to a reloaded page. */

  var DRAFT = (CFG.draft && CFG.draft.data) ? CFG.draft : null;
  var draftRestored = false;

  // The entity toggle is a name-less checkbox; it is saved under its id.
  function draftKey(el) {
    return el.name || (el.id === 'entity_check' ? 'entity_check' : '');
  }

  function draftControls() {
    var csrfName = window.CSRF ? window.CSRF.name() : 'ekart_security_token';
    return Array.prototype.slice.call(form.elements).filter(function (el) {
      var key = draftKey(el);
      if (!key || /^old_/.test(key) || key === csrfName) return false;
      return ['file', 'password', 'button', 'submit', 'reset'].indexOf(el.type) === -1;
    });
  }

  function collectDraft() {
    var out = {};
    draftControls().forEach(function (el) {
      var key = draftKey(el);
      if (el.type === 'radio') { if (el.checked) out[key] = el.value; return; }
      if (el.type === 'checkbox') { out[key] = el.checked ? (el.value || '1') : ''; return; }
      out[key] = el.value;
    });
    return out;
  }

  if (DRAFT) {
    draftControls().forEach(function (el) {
      var key = draftKey(el);
      if (!Object.prototype.hasOwnProperty.call(DRAFT.data, key)) return;
      var v = String(DRAFT.data[key]);
      if (el.type === 'radio') el.checked = (el.value === v);
      else if (el.type === 'checkbox') el.checked = (v !== '');
      else if (el.tagName === 'SELECT') {
        for (var i = 0; i < el.options.length; i++) if (el.options[i].value === v) { el.value = v; break; }
      }
      else el.value = v;
      if (v !== '') draftRestored = true;
    });
  }

  /* ======================================================================
     Constants shared with the server
     ====================================================================== */

  // seller/Login::update_user() -> allowed_types 'jpg|png|jpeg|gif|webp|avif|pdf'
  var ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf'];
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
    enrolment: /^[A-Za-z0-9]{6,20}$/,
    account: /^[0-9]{9,18}$/,
    slug: /^[a-z0-9]+(-[a-z0-9]+)*$/i
  };

  // name -> { label, required, upper, check(value) => error string }
  var RULES = {
    first_name: {
      label: 'First Name', required: true,
      check: function (v) {
        if (!RE.name.test(v)) return 'First name may contain only letters';
        if (v.length < 3 || v.length > 50) return 'First name must be 3-50 characters';
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
    shop_phone: { label: 'Shop Phone Number', required: true, check: phoneCheck('Shop phone number') },
    email: {
      label: 'Email ID', required: true,
      check: function (v) {
        if (v.length > 254) return 'Email must be 254 characters or less';
        return RE.email.test(v) ? '' : 'Enter a valid email address (example: name@example.com)';
      }
    },
    address1: { label: 'Address', required: true, check: minLen(5, 'Address must be at least 5 characters') },
    pin: { label: 'PIN Code', required: true, check: pinCheck },
    state: { label: 'State', required: true },
    district: { label: 'District', required: true },
    city: { label: 'City/Village/Town', required: true },

    shop_name: {
      label: 'Shop Name', required: true,
      check: function (v) { return (v.length < 2 || v.length > 100) ? 'Shop name must be 2-100 characters' : ''; }
    },
    social: { label: 'Social Media Handle' },
    pickup_address1: { label: 'Pickup Address Lane 1', required: true, check: minLen(5, 'Pickup address must be at least 5 characters') },
    pickup_address2: { label: 'Pickup Address Lane 2' },
    pickup_pin: { label: 'Pickup PIN Code', required: true, check: pinCheck },
    pickup_state: { label: 'Pickup State', required: true },
    pickup_district: { label: 'Pickup District', required: true },
    pickup_city: { label: 'Pickup City', required: true },
    slug: {
      label: 'Store URL',
      check: function (v) { return RE.slug.test(v) ? '' : 'Store URL may contain only letters, numbers and hyphens'; }
    },
    store_description: { label: 'Store Description' },
    primary_category_id: { label: 'Primary Product Category', required: true },

    entity_type: { label: 'Entity Type', required: true },
    legal_business_name: { label: 'Legal Business Name' },
    pan: {
      label: 'PAN Number', required: true, upper: true,
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
      check: function (v) { return RE.enrolment.test(v) ? '' : 'Enter a valid GST Enrollment ID (6-20 letters/numbers)'; }
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
    bank_name: { label: 'Bank Name', required: true }
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

  /* ======================================================================
     Small DOM helpers
     ====================================================================== */

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  // Is this element rendered, ignoring the fact that its own step may be the
  // hidden one? Needed because validateAll() has to check every step, not just
  // the visible one, while still skipping fields a toggle has switched off
  // (the GST number field when "not GST registered" is ticked, say).
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

  function clearFieldError(name) {
    setFieldError($('[data-czp-field="' + name + '"]'), '');
  }

  function focusable(wrap) {
    return $('input:not([type="hidden"]), select, textarea', wrap) || $('label.czp-drop', wrap) || wrap;
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
  var current = 0;
  // Only steps the seller has actually tried to leave get a red rail badge -
  // marking an untouched step invalid on load would be nagging, not helping.
  var attempted = {};

  function stepOfName(name) {
    var wrap = $('[data-czp-field="' + name + '"]');
    if (!wrap) return null;
    var stepEl = wrap.closest('[data-czp-step]');
    for (var i = 0; i < steps.length; i++) if (steps[i].el === stepEl) return steps[i];
    return null;
  }

  function showStep(index, opts) {
    opts = opts || {};
    if (index < 0 || index >= steps.length) return;
    current = index;
    steps.forEach(function (s, i) {
      s.el.hidden = (i !== index);
    });
    refreshRail();
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
    var top = Math.max(0, card.getBoundingClientRect().top + window.pageYOffset - 16);
    try { window.scrollTo({ top: top, behavior: 'smooth' }); }
    catch (e) { window.scrollTo(0, top); }
  }

  /* ======================================================================
     Validation
     ====================================================================== */

  function validateControl(control) {
    var name = control.name;
    var rule = RULES[name];
    if (!rule) return '';

    if (rule.upper && control.value.trim()) control.value = control.value.trim().toUpperCase();
    var value = control.value.trim();

    if (!value) {
      if (!rule.required) return '';
      var verb = (control.tagName === 'SELECT') ? 'Please select ' : 'Please enter ';
      return verb + rule.label.toLowerCase();
    }
    return rule.check ? rule.check(value) : '';
  }

  function validateFile(wrap) {
    var input = $('input[type="file"]', wrap);
    if (!input) return '';
    var name = input.name;
    var label = wrap.getAttribute('data-czp-file-label') || 'file';
    var required = wrap.hasAttribute('data-czp-file-required');
    var oldInput = form.querySelector('input[name="old_' + name + '"]');
    var hasExisting = !!(oldInput && oldInput.value.trim() !== '');

    if (!input.files || !input.files.length) {
      // Nothing new chosen. Fine as long as something is already on record
      // (the x button clears old_<name>, so removing without replacing fails).
      if (required && !hasExisting) return 'Please upload ' + label;
      return '';
    }

    var f = input.files[0];
    var ext = (f.name.split('.').pop() || '').toLowerCase();
    // A photo or a logo has to be an image - the server would accept a PDF here
    // (one allowed_types list covers every upload on this form) but nothing can
    // render it. Documents take either.
    var imagesOnly = wrap.hasAttribute('data-czp-file-images');
    var allowed = imagesOnly ? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'] : ALLOWED_EXT;
    if (allowed.indexOf(ext) === -1) {
      return label + ': "' + f.name + '" is not an accepted file type. ' + (imagesOnly
        ? 'Please upload a JPG, JPEG, PNG, WEBP, AVIF or GIF image.'
        : 'Please upload a JPG, JPEG, PNG, WEBP, AVIF, GIF or PDF file.');
    }
    if (f.size === 0) {
      return label + ': "' + f.name + '" is empty. Please choose another file.';
    }
    if (f.size > MAX_FILE_KB * 1024) {
      return label + ': "' + f.name + '" is too large (' + (f.size / (1024 * 1024)).toFixed(1) +
        ' MB). The limit is ' + MAX_FILE_KB / 1000 + ' MB per file.';
    }
    return '';
  }

  /**
   * Reads the first bytes of a chosen file and names what it REALLY is. The extension alone is
   * not enough: a phone photo saved as HEIC and renamed to .jpg passes the extension check
   * but the server (which checks the content) rejects it - and only at final submit.
   * Resolves to '' when the content is acceptable, else a message for the seller.
   */
  function sniffFile(wrap, f) {
    return new Promise(function (resolve) {
      var label = wrap.getAttribute('data-czp-file-label') || 'file';
      var imagesOnly = wrap.hasAttribute('data-czp-file-images');
      var reader = new FileReader();
      reader.onerror = function () { resolve(''); }; // cannot read it here: let the server decide
      reader.onload = function (ev) {
        var a = new Uint8Array(ev.target.result);
        var ascii = function (from, to) {
          var s = '';
          for (var i = from; i < to && i < a.length; i++) s += String.fromCharCode(a[i]);
          return s;
        };
        var kind = '';
        if (a[0] === 0xFF && a[1] === 0xD8 && a[2] === 0xFF) kind = 'jpg';
        else if (ascii(1, 4) === 'PNG' && a[0] === 0x89) kind = 'png';
        else if (ascii(0, 4) === 'GIF8') kind = 'gif';
        else if (ascii(0, 4) === '%PDF') kind = 'pdf';
        else if (ascii(0, 4) === 'RIFF' && ascii(8, 12) === 'WEBP') kind = 'webp';
        else if (ascii(4, 8) === 'ftyp' && (ascii(8, 12) === 'avif' || ascii(8, 12) === 'avis')) kind = 'avif';
        else if (ascii(4, 8) === 'ftyp') kind = 'HEIC/HEIF';
        else if (ascii(0, 2) === 'BM') kind = 'BMP';
        else if (ascii(0, 4) === 'II*\x00' || ascii(0, 4) === 'MM\x00*') kind = 'TIFF';
        var ok = imagesOnly ? ['jpg', 'png', 'gif', 'webp', 'avif'] : ['jpg', 'png', 'gif', 'webp', 'avif', 'pdf'];
        if (ok.indexOf(kind) !== -1) return resolve('');
        resolve(label + ': "' + f.name + '" is ' + (kind ? 'really a ' + kind + ' file' : 'not a valid image') +
          ' that was only renamed to .' + ((f.name.split('.').pop() || '').toLowerCase()) +
          '. Please save or export it as a ' + (imagesOnly ? 'JPG, PNG, WEBP, AVIF or GIF image.' : 'JPG, PNG, WEBP, AVIF, GIF image or PDF.'));
      };
      reader.readAsArrayBuffer(f.slice(0, 16));
    });
  }

  /**
   * Validate one step.
   *
   * @param {object}  step
   * @param {object}  opts  render:false to test quietly (used by the rail badges)
   * @returns {{ok:boolean, errors:Array, firstWrap:Element|null}}
   */
  function checkStep(step, opts) {
    opts = opts || {};
    var render = opts.render !== false;
    var errors = [];
    var firstWrap = null;

    $$('[data-czp-field]', step.el).forEach(function (wrap) {
      if (!isActiveWithinStep(wrap)) {
        if (render) setFieldError(wrap, '');
        return;
      }

      var message = '';
      if (wrap.hasAttribute('data-czp-file-label')) {
        message = validateFile(wrap);
      } else {
        // [data-czp-control] names the control that actually carries the value
        // when it is not the visible one - Bank Name is typed into a search box
        // and stored in a hidden input, and it is the hidden one the rules and
        // the server both care about.
        var control = $('[data-czp-control]', wrap) ||
          $('input:not([type="hidden"]):not([type="checkbox"]), select, textarea', wrap);
        if (control) message = validateControl(control);
      }

      // A pending/failed server-side uniqueness answer for phone / shop phone /
      // email counts as an error too - see contactState below.
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

    // Cross-field: the two account numbers must match.
    var acc = $('[name="account_number"]', step.el);
    var acc2 = $('[name="confirm_account_number"]', step.el);
    if (acc && acc2 && acc.value.trim() && acc2.value.trim() && acc.value.trim() !== acc2.value.trim()) {
      var w = fieldWrap(acc2);
      if (render) setFieldError(w, 'Account numbers do not match');
      errors.push({ label: 'Confirm Account Number', message: 'Account numbers do not match', wrap: w });
      if (!firstWrap) firstWrap = w;
    }

    // Individual sellers must accept the "not a registered Entity" declaration.
    // A checkbox always reports value "1", so the shared value rules cannot see it.
    var entityType = $('#entity_type');
    var entityCheck = $('#entity_check', step.el);
    var entityBox = $('#entity_check_box', step.el);
    if (entityType && entityCheck && entityBox && isActiveWithinStep(entityBox)) {
      var need = entityType.value === 'individual' && !entityCheck.checked;
      if (render) entityBox.classList.toggle('has-error', need);
      if (render) {
        var slot = $('[data-czp-error]', entityBox);
        if (slot) {
          slot.textContent = need ? 'Please confirm you are not a registered Entity' : '';
          slot.classList.toggle('is-shown', need);
        }
      }
      if (need) {
        errors.push({ label: 'Declaration', message: 'Please confirm you are not a registered Entity', wrap: entityBox });
        if (!firstWrap) firstWrap = entityBox;
      }
    }

    if (render) renderStepAlert(step, errors);
    return { ok: errors.length === 0, errors: errors, firstWrap: firstWrap };
  }

  function labelOf(wrap) {
    var l = $('.czp-label', wrap);
    return l ? l.textContent.replace('*', '').trim() : (wrap.getAttribute('data-czp-field') || 'This field');
  }

  // The per-step summary banner. It sits at the top of the step the seller is
  // already looking at, so nothing about a failed Continue happens off-screen.
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
      ' your attention before you can continue</b><ul>' + items + more + '</ul>';
    step.alert.hidden = false;
  }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
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
     Navigation
     ====================================================================== */

  function goNext() {
    var step = steps[current];
    attempted[current] = true;
    var result = checkStep(step);
    if (!result.ok) { failStep(step, result); return; }
    refreshRail();
    showStep(Math.min(current + 1, steps.length - 1));
  }

  function goBack() {
    // Going back never blocks - the seller may well be going back BECAUSE
    // something on this step is wrong. Their entries are left untouched.
    showStep(Math.max(current - 1, 0));
  }

  // A rail jump forward has to clear every step in between, otherwise the rail
  // becomes a way to skip the validation the Continue button enforces.
  function goTo(index) {
    if (index === current) return;
    if (index < current) { showStep(index); return; }
    for (var i = current; i < index; i++) {
      attempted[i] = true;
      var r = checkStep(steps[i]);
      if (!r.ok) { failStep(steps[i], r); return; }
    }
    refreshRail();
    showStep(index);
  }

  form.addEventListener('click', function (e) {
    if (e.target.closest('[data-czp-next]')) { e.preventDefault(); goNext(); }
    else if (e.target.closest('[data-czp-back]')) { e.preventDefault(); goBack(); }
  });

  $$('[data-czp-goto]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      goTo(parseInt(btn.getAttribute('data-czp-goto'), 10));
    });
  });

  // Exposed for the review step's "Fill this in" links and any deep link.
  window.openProfileSection = function (key, scroll) {
    for (var i = 0; i < steps.length; i++) {
      if (steps[i].key === key) {
        // Deliberate jump to a named section: no validation gate, the point is
        // to take the seller to the thing that is missing.
        showStep(i, { scroll: scroll !== false });
        return;
      }
    }
  };

  /* ======================================================================
     Live per-field feedback
     ====================================================================== */

  // Clear a field's error the moment the seller starts fixing it, so the page
  // never argues with what is now on screen.
  //
  // File inputs are excluded: their own change handler VALIDATES the chosen file
  // and writes the result into this same slot. Both listeners see the same
  // event - the input's at target, this one on the way up - so clearing here
  // would erase the message the file handler had just set, and a rejected
  // upload would look accepted.
  function clearOnEdit(e) {
    var target = e.target;
    if (!target || !target.closest) return;
    if (target.type === 'file') return;
    var wrap = fieldWrap(target);
    if (wrap) setFieldError(wrap, '');
  }
  form.addEventListener('input', clearOnEdit);
  form.addEventListener('change', clearOnEdit);

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

  // Validate on blur so problems surface field by field rather than all at once
  // when Continue is pressed.
  $$('input:not([type="hidden"]):not([type="file"]):not([type="checkbox"]), select, textarea', form).forEach(function (control) {
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
      dropText: $('[data-czp-drop-text]', wrap),
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
    if (p.link) p.link.removeAttribute('href');
    if (p.box) {
      if (isPdf) p.box.setAttribute('data-czp-pdf-url', URL.createObjectURL(file));
      else p.box.removeAttribute('data-czp-pdf-url');
      p.box.hidden = false;
    }

    if (!isPdf && /^image\//.test(file.type)) {
      var reader = new FileReader();
      reader.onload = function (ev) {
        if (p.thumb) { p.thumb.src = ev.target.result; p.thumb.hidden = false; }
        if (p.link) p.link.href = ev.target.result;
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
      // Validate the moment the file is picked. The seller finds out here, on
      // this step, with the picker still fresh in mind - not four steps later.
      var input = this;
      var message = validateFile(wrap);
      var reject = function (msg) {
        setFieldError(wrap, msg);
        input.value = '';
        if (p.box) p.box.hidden = true;
        toast('error', msg);
      };
      if (message) return reject(message);
      sniffFile(wrap, f).then(function (bad) {
        if (bad) return reject(bad);
        showChosen(wrap, f);
      });
    });

    // Drag and drop onto the picker.
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
        } catch (err) { /* older browsers: the click-to-pick path still works */ }
      });
    }

    if (p.remove) {
      p.remove.addEventListener('click', function () {
        if (!confirm('Remove this file? You will need to upload a new one to replace it.')) return;
        p.input.value = '';
        var oldInput = form.querySelector('input[name="old_' + p.input.name + '"]');
        if (oldInput) oldInput.value = '';
        if (p.box) p.box.hidden = true;
        if (p.hint) p.hint.hidden = true;
        // Removing a required document is itself an error worth saying out loud
        // right away rather than at the next Continue.
        setFieldError(wrap, validateFile(wrap));
      });
    }
  });

  /* ---- Full-screen PDF viewer ------------------------------------------
     Images already open large; a PDF only had a bare badge. Clicking the
     badge or file name opens it in a full-screen overlay so it can be read
     properly. data-czp-pdf-url on the [data-czp-doc] box holds the source:
     the saved path from PHP, or a blob: URL for a file picked this visit. */
  var pdfViewer = null;

  function ensurePdfViewer() {
    if (pdfViewer) return pdfViewer;
    var st = document.createElement('style');
    st.textContent =
      '.czp-pdfv{position:fixed;inset:0;z-index:2147483000;display:flex;flex-direction:column;background:rgba(15,23,42,.92)}' +
      '.czp-pdfv[hidden]{display:none}' +
      '.czp-pdfv-bar{display:flex;align-items:center;gap:12px;padding:10px 16px;color:#fff;font:600 14px/1.2 system-ui,sans-serif}' +
      '.czp-pdfv-title{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}' +
      '.czp-pdfv-bar a,.czp-pdfv-bar button{color:#fff;background:rgba(255,255,255,.14);border:0;border-radius:8px;padding:8px 14px;font:inherit;text-decoration:none;cursor:pointer}' +
      '.czp-pdfv-bar a:hover,.czp-pdfv-bar button:hover{background:rgba(255,255,255,.26)}' +
      '.czp-pdfv-frame{flex:1;width:100%;border:0;background:#fff}';
    document.head.appendChild(st);

    var el = document.createElement('div');
    el.className = 'czp-pdfv';
    el.hidden = true;
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.innerHTML =
      '<div class="czp-pdfv-bar"><span class="czp-pdfv-title"></span>' +
      '<a target="_blank" rel="noopener">Open in new tab</a>' +
      '<button type="button">Close &times;</button></div>' +
      '<iframe class="czp-pdfv-frame" title="PDF preview"></iframe>';
    document.body.appendChild(el);

    var api = {
      el: el,
      frame: el.querySelector('iframe'),
      title: el.querySelector('.czp-pdfv-title'),
      tab: el.querySelector('a'),
      open: function (url, name) {
        api.frame.src = url;
        api.tab.href = url;
        api.title.textContent = name || 'Document';
        el.hidden = false;
        document.documentElement.style.overflow = 'hidden';
      },
      close: function () {
        el.hidden = true;
        api.frame.src = 'about:blank';
        document.documentElement.style.overflow = '';
      }
    };
    el.querySelector('button').addEventListener('click', api.close);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !el.hidden) api.close();
    });
    pdfViewer = api;
    return api;
  }

  form.addEventListener('click', function (e) {
    var box = e.target.closest('[data-czp-doc]');
    if (!box || e.target.closest('[data-czp-doc-remove]')) return;
    var url = box.getAttribute('data-czp-pdf-url');
    var badge = box.querySelector('[data-czp-doc-file]');
    if (!url || !badge || badge.hidden) return;
    e.preventDefault();
    var nameEl = box.querySelector('[data-czp-doc-name]');
    ensurePdfViewer().open(url, nameEl ? nameEl.textContent : '');
  });

  /* ---- Round avatar pickers (personal photo, shop logo) ---------------- */
  $$('[data-czp-avatar]', form).forEach(function (box) {
    var input = $('input[type="file"]', box.closest('[data-czp-field]') || box.parentElement);
    var img = $('[data-czp-avatar-img]', box);
    var icon = $('[data-czp-avatar-icon]', box);
    if (!input) return;
    box.addEventListener('click', function () { input.click(); });
    box.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });
    input.addEventListener('change', function () {
      var f = this.files && this.files[0];
      var wrap = fieldWrap(this);
      if (!f) return;
      var input = this;
      var message = validateFile(wrap);
      if (message) { setFieldError(wrap, message); input.value = ''; toast('error', message); return; }
      sniffFile(wrap, f).then(function (bad) {
        if (bad) { setFieldError(wrap, bad); input.value = ''; toast('error', bad); return; }
        setFieldError(wrap, '');
        showAvatar(f);
      });
    });
    function showAvatar(f) {
      if (!/^image\//.test(f.type)) return; // a PDF is allowed but cannot be previewed
      var reader = new FileReader();
      reader.onload = function (ev) {
        if (img) { img.src = ev.target.result; img.removeAttribute('hidden'); }
        // setAttribute, NOT `.hidden = true`: the placeholder is an <svg>, and
        // `hidden` is an HTMLElement property that SVGElement does not
        // implement - assigning it silently sets a JS expando and leaves the
        // attribute (and the silhouette) exactly where it was, showing the
        // placeholder and the new photo at the same time.
        if (icon) icon.setAttribute('hidden', 'hidden');
      };
      reader.readAsDataURL(f);
    }
  });

  /* ======================================================================
     Entity type / GST toggles
     ====================================================================== */

  (function () {
    var entityType = $('#entity_type');
    var gstCheck = $('#gst_check');
    if (!entityType || !gstCheck) return;

    var PAN_LABELS = {
      individual: 'Your PAN Number',
      sole_proprietorship: "Proprietor's PAN Number",
      partnership_firm: "Firm's PAN Number"
    };
    var LEGAL_NAME_LABELS = {
      partnership_firm: "Legal Firm's Name"
    };
    var PROOF_HINTS = {
      sole_proprietorship: 'Udyam/MSME Certificate',
      partnership_firm: 'Partnership deed, Firm PAN card, or Udyam/MSME Certificate',
      pvt_ltd: 'Udyam/MSME Certificate or Certificate of Incorporation'
    };

    // A hidden field must stop being required, and must stop being validated -
    // otherwise the seller is blocked by a rule about a field they cannot see.
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

      var wantsDeed = (type === 'partnership_firm');
      setActive($('[data-czp-field="partnership_deed_document"]'), wantsDeed);

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

      var entityBox = $('#entity_check_box');
      if (entityBox) {
        entityBox.hidden = (type !== 'individual');
        if (entityBox.hidden) {
          entityBox.classList.remove('has-error');
          var slot = $('[data-czp-error]', entityBox);
          if (slot) { slot.textContent = ''; slot.classList.remove('is-shown'); }
        }
      }

      autofillLegalName();
      refreshRail();
    }

    // For an Individual the legal business name IS their own name. Fill it, but
    // never over the top of something the seller typed: data-autofilled marks a
    // value this code put there, and a real keystroke clears the mark.
    function autofillLegalName() {
      var input = $('#legal_business_name_input');
      if (!input || entityType.value !== 'individual') return;
      if (input.value.trim() && input.dataset.autofilled !== '1') return;
      var first = $('[name="first_name"]');
      var last = $('[name="last_name"]');
      var full = [first ? first.value.trim() : '', last ? last.value.trim() : ''].filter(Boolean).join(' ');
      if (!full) return;
      input.value = full;
      input.dataset.autofilled = '1';
    }

    var legalInput = $('#legal_business_name_input');
    if (legalInput) legalInput.addEventListener('input', function () { legalInput.dataset.autofilled = ''; });

    // "individual" is the default selection, so entity_type never fires a
    // change event for the common case of a new seller typing their name after
    // the page has loaded. Re-run the autofill when they leave either name field.
    ['first_name', 'last_name'].forEach(function (n) {
      var el = $('[name="' + n + '"]', form);
      if (el) el.addEventListener('blur', autofillLegalName);
    });

    entityType.addEventListener('change', sync);
    gstCheck.addEventListener('change', sync);
    sync();
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

    // India Post covers every Indian pincode; zippopotam is the fallback when
    // it is unreachable. Neither resolving is NOT an error - the three fields
    // stay editable and the seller types them in.
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
          var city = place['place name'] || place.place_name || place.city || '';
          var state = place.state || place['state name'] || '';
          var district = place.district || place.county || place.region || city;
          if (!city && !state && !district) throw new Error('empty');
          return { state: state, district: district, city: city };
        });
    }

    function fill(id, value) {
      var el = $('#' + id);
      if (!el || !value) return;
      el.value = value;
      setFieldError(fieldWrap(el), '');
      // Programmatic writes fire no events. Announce it so anything mirroring this field
      // (the "pickup same as personal" copy) and the draft autosave both notice.
      el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function lookup() {
      // A PIN locked by the "same as personal address" copy must keep the values copied
      // from step 1; a fresh lookup could rewrite State/District/City under it.
      if (pin.readOnly) return;
      var code = pin.value.replace(/\D/g, '').slice(0, 6);
      pin.value = code;
      if (code.length < 6) { latest = ''; setStatus(status, '', ''); return; }
      latest = code;
      setStatus(status, 'Looking up state, district and city...', 'info');
      indiaPost(code)
        .catch(function () { return zippopotam(code); })
        .then(function (loc) {
          if (latest !== code) return;
          fill(opts.state, loc.state);
          fill(opts.district, loc.district);
          fill(opts.city, loc.city);
          setStatus(status, 'Filled from PIN code. Edit if anything looks wrong.', 'success');
        })
        .catch(function () {
          if (latest !== code) return;
          setStatus(status, 'Could not look up this PIN code. Please type State, District and City yourself.', 'info');
        });
    }

    pin.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(lookup, 400); });
    pin.addEventListener('blur', lookup);
  }

  bindPincode({ pin: 'pin', state: 'state', district: 'district', city: 'city', status: 'pin_status' });
  bindPincode({ pin: 'pickup_pin', state: 'pickup_state', district: 'pickup_district', city: 'pickup_city', status: 'pickup_pin_status' });
  bindPincode({ pin: 'business_pin', state: 'business_state', district: 'business_district', city: 'business_city', status: 'business_pin_status' });

  /* ======================================================================
     "Same as my personal address" (pickup address and business address)
     ====================================================================== */

  /**
   * cfg.boxId / cfg.checkId  the checkbox row and the checkbox
   * cfg.prefix               'pickup_' or 'business_' - the target field names
   * cfg.statusId             the target PIN field's status line
   * cfg.applies()            optional: false hides the option (e.g. wrong entity type)
   */
  function bindSameAsPersonal(cfg) {
    var box = $('#' + cfg.boxId);
    var check = $('#' + cfg.checkId);
    if (!box || !check) return;

    // [personal field, target field]. Target lane 2 has no personal counterpart and stays
    // the seller's own.
    var PAIRS = [
      ['address1', cfg.prefix + 'address1'],
      ['pin', cfg.prefix + 'pin'],
      ['state', cfg.prefix + 'state'],
      ['district', cfg.prefix + 'district'],
      ['city', cfg.prefix + 'city']
    ];
    var PERSONAL = PAIRS.map(function (p) { return p[0]; });

    function field(name) { return form.elements[name]; }
    function val(name) { var el = field(name); return el ? String(el.value).trim() : ''; }
    function applies() { return !cfg.applies || cfg.applies(); }

    // Offer the copy only when there is something worth copying.
    function personalComplete() {
      return PAIRS.every(function (p) { return val(p[0]) !== ''; }) && RE.pin.test(val('pin'));
    }
    function alreadyMatches() {
      return PAIRS.every(function (p) { return val(p[1]) !== '' && val(p[1]) === val(p[0]); });
    }

    // readOnly, not disabled: a disabled input is left out of the submitted form.
    function lock(on) {
      PAIRS.forEach(function (p) {
        var el = field(p[1]);
        if (!el) return;
        el.readOnly = on;
        el.classList.toggle('is-synced', on);
      });
    }

    function copy() {
      PAIRS.forEach(function (p) {
        var from = field(p[0]);
        var to = field(p[1]);
        if (!from || !to) return;
        if (to.value !== from.value) {
          to.value = from.value;
          // 'change', not 'input': input would start the target PIN lookup, which this
          // copy makes pointless. Change still reaches the draft autosave.
          to.dispatchEvent(new Event('change', { bubbles: true }));
        }
        setFieldError(fieldWrap(to), '');
      });
      setStatus($('#' + cfg.statusId), 'Copied from your personal address.', 'success');
    }

    function refresh() {
      var offer = applies() && personalComplete();
      if (!offer && check.checked) {
        // Nothing left to mirror (personal address emptied, or the option no longer applies
        // to this entity type). Keep the values the seller can see and hand the fields back.
        check.checked = false;
        lock(false);
      }
      box.hidden = !offer;
      if (check.checked) copy();
    }

    check.addEventListener('change', function () {
      lock(check.checked);
      if (check.checked) copy();
    });

    ['input', 'change'].forEach(function (type) {
      form.addEventListener(type, function (e) {
        var n = e.target && e.target.name;
        if (n && (PERSONAL.indexOf(n) !== -1 || n === 'entity_type')) refresh();
      });
    });

    // Opening state: ticked when the saved (or draft-restored) address already is the
    // personal one, so a seller who ticked it last time finds it ticked.
    check.checked = applies() && personalComplete() && alreadyMatches();
    lock(check.checked);
    refresh();
  }

  bindSameAsPersonal({ boxId: 'pickup_same_box', checkId: 'pickup_same_check', prefix: 'pickup_', statusId: 'pickup_pin_status' });
  // A company or firm has its own registered address; only the entity types that ARE the
  // person (an individual, a sole proprietor) offer the shortcut.
  bindSameAsPersonal({
    boxId: 'business_same_box', checkId: 'business_same_check', prefix: 'business_', statusId: 'business_pin_status',
    applies: function () {
      var t = form.elements.entity_type;
      return !!t && (t.value === 'individual' || t.value === 'sole_proprietorship');
    }
  });

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
      // The typed text IS the value - a seller whose bank is not in the table
      // must still be able to enter it.
      hidden.value = this.value.trim();
      var q = this.value.trim().toLowerCase();
      if (!q) { list.classList.remove('is-open'); return; }
      render(banks.filter(function (b) { return b.toLowerCase().indexOf(q) !== -1; }));
    });
    search.addEventListener('focus', function () {
      if (this.value.trim()) return;
      render(banks);
    });
    // The hidden input is what the rules read, so the visible box has to hand
    // the blur check over to it.
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

    // Secondary categories are simply "what else do you sell": any category at any
    // level may be picked, except the primary one itself (it is already selected).
    function filterToPrimary() {
      var pid = primary.value;
      $$('.czp-pick', modal).forEach(function (row) {
        row.hidden = pid !== '' && row.getAttribute('data-id') === pid;
      });
      var empty = $('#category_empty', modal);
      if (empty) empty.hidden = true;
    }

    function prune() {
      var pid = primary.value;
      hidden.value = ids().filter(function (id) { return id !== pid; }).join(',');
      renderPills();
    }

    openBtn.addEventListener('click', function () {
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

    prune();
    filterToPrimary();
    renderPills();
  })();

  /* ======================================================================
     Phone / shop phone / email: live uniqueness check
     ====================================================================== */

  var contactState = { phone: null, shop_phone: null, email: null };

  function checkContact(field) {
    var input = $('[data-czp-contact="' + field + '"]', form);
    if (!input) return;
    var wrap = fieldWrap(input);
    var value = input.value.trim();
    if (!value) { contactState[field] = null; return; }

    var control = validateControl(input);
    if (control) {
      contactState[field] = { value: value, valid: false, message: control };
      setFieldError(wrap, control);
      return;
    }

    var body = new FormData();
    body.append('field', field);
    body.append('value', value);
    // The seller's own personal number is allowed as the shop number too, so
    // the server needs it to tell "mine" from "another account's".
    var phone = $('[data-czp-contact="phone"]', form);
    body.append('phone', phone ? phone.value : '');

    fetch(CFG.checkContactUrl, { method: 'POST', body: body })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (input.value.trim() !== value) return; // stale: the seller kept typing
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
    // Anything already saved gets checked once, so a seller editing an old
    // profile sees the clash before touching the field.
    if (input.value.trim()) checkContact(field);
  });

  /* ======================================================================
     Submit
     ====================================================================== */

  // Server rejections that name a field, mapped back to that field. Without
  // this, "Shop Name already exists." arrived as an anonymous toast while the
  // seller was on the Review step, three steps away from the field it means.
  var SERVER_FIELD_HINTS = [
    { re: /shop name already exists/i, field: 'shop_name' },
    { re: /store url already exists/i, field: 'slug' },
    { re: /\bPAN Number\b/i, field: 'pan' },
    { re: /\bGST Enrollment ID\b/i, field: 'gst_enrollment_number' },
    { re: /\bGST Number\b/i, field: 'gst' },
    { re: /\bAccount Number\b/i, field: 'account_number' },
    { re: /\bShop Phone Number\b/i, field: 'shop_phone' },
    { re: /\bPhone Number\b/i, field: 'phone' },
    { re: /\bEmail(?: ID)?\b/i, field: 'email' }
  ];

  function routeServerError(message) {
    var text = String(message || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    if (!text) text = 'Something went wrong. Please try again.';

    for (var i = 0; i < SERVER_FIELD_HINTS.length; i++) {
      if (!SERVER_FIELD_HINTS[i].re.test(text)) continue;
      var name = SERVER_FIELD_HINTS[i].field;
      var step = stepOfName(name);
      if (!step) continue;
      var wrap = $('[data-czp-field="' + name + '"]');
      setFieldError(wrap, text);
      attempted[step.index] = true;
      renderStepAlert(step, [{ label: labelOf(wrap), message: text, wrap: wrap }]);
      failStep(step, { firstWrap: wrap });
      return true;
    }

    // Not attributable to one field: put it on the step the seller is on, where
    // they can actually see it, as well as in the toast.
    var here = steps[current];
    if (here && here.alert) {
      here.alert.className = 'czp-alert czp-alert-error';
      here.alert.innerHTML = '<b>The server could not save your profile</b>' + esc(text);
      here.alert.hidden = false;
    }
    return false;
  }

  function toast(kind, message) {
    var el = $('#czp_toast');
    if (!el) return;
    el.className = 'czp-toast is-shown is-' + kind;
    el.textContent = message;
    clearTimeout(window.czpToastTimer);
    window.czpToastTimer = setTimeout(function () { el.className = 'czp-toast'; }, 6000);
  }

  var submitBtn = $('[data-czp-submit]', form);

  /**
   * Validate and POST the whole form.
   *
   * @param {Element} btn         the button that was pressed (shows progress)
   * @param {boolean} singleStep  true for a per-step "Save changes": only the step the
   *                              seller is on has to be valid, the rest of the form is
   *                              posted as it stands (the server saves what it is given
   *                              and files nothing for approval unless the profile is
   *                              complete), and the seller stays on this page.
   */
  function saveProfile(btn, singleStep) {
    // Every step, in order. The first one with a problem is where the seller
    // is taken - so no error can ever surface for the first time here on Review.
    // A single-step save checks just the current step.
    var first = singleStep ? current : 0;
    var last = singleStep ? current + 1 : steps.length;
    for (var i = first; i < last; i++) {
      attempted[i] = true;
      var result = checkStep(steps[i]);
      if (!result.ok) {
        failStep(steps[i], result);
        toast('error', 'Please fix the highlighted fields on "' + steps[i].label + '" first.');
        return;
      }
    }
    refreshRail();

    // The final save replaces the draft; a late auto-save must not recreate it.
    draftOn = false;
    clearTimeout(draftTimer);

    var payload = new FormData(form);

    // The server drops the WHOLE request when the combined size of every field
    // and file passes post_max_size - and then answers with a bare 403 that names
    // nothing. The per-file 8 MB limit cannot see this, because eleven valid
    // documents can add up past it. Say so before uploading anything.
    var totalBytes = 0;
    $$('input[type="file"]', form).forEach(function (f) {
      if (f.files && f.files[0]) totalBytes += f.files[0].size;
    });
    if (CFG.maxPostBytes && totalBytes > CFG.maxPostBytes * 0.95) {
      draftOn = !!CFG.draftUrl;
      toast('error', 'The documents you attached add up to ' + (totalBytes / 1048576).toFixed(1) +
        ' MB, over the ' + Math.floor(CFG.maxPostBytes / 1048576) + ' MB the server accepts in one save. ' +
        'Please use smaller files or compress the images.');
      return;
    }

    btn.disabled = true;
    var original = btn.textContent;
    btn.textContent = singleStep ? 'Saving...' : 'Submitting...';

    function reset() {
      btn.disabled = false;
      btn.textContent = original;
      draftOn = !!CFG.draftUrl;
    }

    // PHP notices / deprecations (display_errors on) are printed ahead of the
    // JSON, which used to make a SUCCESSFUL save look like a failure. The
    // response is always one JSON object, so read from its first "{"error"".
    function parseReply(text) {
      var clean = String(text).replace(/<!--[\s\S]*?-->/g, '').trim();
      try { return JSON.parse(clean); } catch (e) { /* fall through */ }
      var at = clean.indexOf('{"error"');
      if (at !== -1) { try { return JSON.parse(clean.slice(at)); } catch (e2) { /* fall through */ } }
      return null;
    }

    function failMessage(status) {
      if (status === 0) return 'Could not reach the server. Check your internet connection and try again.';
      if (status === 403) return 'Your session expired or the upload was too large. Reload the page and try again.';
      if (status === 413) return 'The files you attached are too large. Please use smaller files.';
      if (status === 408 || status === 504 || status === 524) return 'The server took too long to respond. Please try again.';
      if (status >= 500) return 'The server had a problem saving your profile. Please try again in a moment.';
      return 'Something went wrong. Please try again.';
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', CFG.saveUrl, true);
    // Without a ceiling a stalled connection (or a proxy that never answers)
    // leaves the button on "Submitting..." for good.
    xhr.timeout = 180000;

    // Uploading several documents takes a while on a slow line; showing the
    // percentage is what tells the seller it is working and not frozen.
    if (xhr.upload) xhr.upload.onprogress = function (e) {
      if (!e.lengthComputable || !totalBytes) return;
      var pct = Math.min(100, Math.round((e.loaded / e.total) * 100));
      btn.textContent = pct < 100 ? 'Uploading... ' + pct + '%' : 'Saving...';
    };

    xhr.onload = function () {
      var data = parseReply(xhr.responseText);
      if (!data) {
        console.error('Profile save returned non-JSON (HTTP ' + xhr.status + '):', xhr.responseText);
        reset();
        toast('error', failMessage(xhr.status));
        return;
      }

      if (data.error === false) {
        // Stay disabled until the redirect: re-enabling here let a second click
        // submit the whole profile again during the pause.
        btn.textContent = 'Saved';
        toast('success', data.message || 'Profile saved.');
        // A save that did NOT go for verification says why, which is worth
        // reading before the redirect takes the page away.
        var wait = ((data.verification_filed === false || data.changes_pending) && data.message) ? 4000 : 1800;
        // A per-step save keeps the seller where they were; the page reloads so the
        // saved values, the "waiting for approval" banner and the document inputs are fresh.
        var dest = singleStep
          ? window.location.pathname + '?section=' + encodeURIComponent(steps[current].key)
          : CFG.homeUrl;
        setTimeout(function () { window.location.href = dest; }, wait);
        return;
      }

      reset();
      routeServerError(data.message);
      toast('error', String(data.message || 'Could not save your profile.').replace(/<[^>]*>/g, ' ').trim());
    };
    xhr.onerror = function () { reset(); toast('error', failMessage(0)); };
    xhr.ontimeout = function () {
      reset();
      toast('error', 'This is taking too long. Your details may have saved - reload the page to check, or try again with smaller files.');
    };
    xhr.send(payload);
  }

  if (submitBtn) submitBtn.addEventListener('click', function (e) {
    e.preventDefault();
    saveProfile(submitBtn, false);
  });

  // "Save changes" on each step: update just this page's fields without walking
  // through every other step.
  form.addEventListener('click', function (e) {
    var saveBtn = e.target.closest && e.target.closest('[data-czp-save]');
    if (!saveBtn) return;
    e.preventDefault();
    saveProfile(saveBtn, true);
  });

  /* ======================================================================
     Boot
     ====================================================================== */

  // Resume where the seller left off - unless the link names a section on purpose
  // (the review checklist's "Fill this in" links do), which always wins.
  var wantedStep = CFG.initialSection;
  if (!CFG.sectionExplicit && DRAFT && DRAFT.step) wantedStep = DRAFT.step;
  var startIndex = 0;
  for (var s = 0; s < steps.length; s++) {
    if (steps[s].key === wantedStep) { startIndex = s; break; }
  }
  // No scroll on load - the seller is already at the top, and a deep link
  // (?section=account) should not yank the page while it is still rendering.
  showStep(startIndex, { scroll: false });

  /* ======================================================================
     Draft: auto-save while the seller types
     ====================================================================== */

  var draftOn = !!CFG.draftUrl;
  var draftTimer = null;
  var lastSent = steps[current].key + JSON.stringify(collectDraft());
  var statusEl = null;
  var statusTimer = null;

  function draftStatus(text, kind) {
    if (!statusEl) {
      statusEl = document.createElement('div');
      statusEl.setAttribute('role', 'status');
      document.body.appendChild(statusEl);
    }
    statusEl.textContent = text;
    statusEl.className = 'czp-draft-status is-shown' + (kind ? ' is-' + kind : '');
    clearTimeout(statusTimer);
    statusTimer = setTimeout(function () { statusEl.className = 'czp-draft-status'; }, 2500);
  }

  function saveDraft(opts) {
    opts = opts || {};
    clearTimeout(draftTimer);
    if (!draftOn) return;
    var payload = JSON.stringify(collectDraft());
    var stepKey = steps[current].key;
    var signature = stepKey + payload;
    if (signature === lastSent) return;
    var body = new FormData();
    body.append('step', stepKey);
    body.append('draft', payload);
    fetch(CFG.draftUrl, { method: 'POST', body: body, keepalive: !!opts.unloading })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.error === false) {
          lastSent = signature;
          if (!opts.unloading) draftStatus('Draft saved', 'ok');
        } else if (!opts.unloading) {
          draftStatus('Could not save draft', 'error');
        }
      })
      .catch(function () { if (!opts.unloading) draftStatus('Could not save draft', 'error'); });
  }

  function scheduleDraft() {
    if (!draftOn) return;
    clearTimeout(draftTimer);
    draftTimer = setTimeout(saveDraft, 1200);
  }

  form.addEventListener('input', scheduleDraft);
  form.addEventListener('change', scheduleDraft);
  // Moving between steps is progress too, even if nothing was typed on the way.
  document.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest('[data-czp-next],[data-czp-back],[data-czp-goto]')) {
      setTimeout(saveDraft, 50);
    }
  });
  // Reload / tab close / app switch: flush whatever is still waiting on the timer.
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') saveDraft({ unloading: true });
  });
  window.addEventListener('pagehide', function () { saveDraft({ unloading: true }); });

  if (draftRestored) toast('success', 'Welcome back - we restored your unsaved details. Re-attach any documents you had chosen.');
})();
