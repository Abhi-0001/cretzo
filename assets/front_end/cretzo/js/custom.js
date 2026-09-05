"use strict";
var quickViewgalleryThumbs, mobile_image_swiper, quickViewgalleryTop, galleryTop, galleryThumbs, custom_url = location.href,
    is_rtl = $("#body").data("is-rtl"),
    mode = 1 == is_rtl ? "right" : "left";
    
const is_loggedin = $("#is_loggedin").val();

/* Hanzyusuf: To view commented out toast fires, search the word: CommentedOutToast */

const Toast = Swal.mixin({
    toast: !0,
    /* cretzo: show notifications top-RIGHT, just BELOW the header (near the
       profile/cart icons). Vertical offset + z-index are applied via CSS in
       cretzo-override.css / footer.php. */
    position: "top-end",
    showConfirmButton: !1,
    timer: 3e3,
    timerProgressBar: !0,
    animation: !1,
    showClass: { popup: "" },
    hideClass: { popup: "" },

    // timer: 0,
    // iconColor: getComputedStyle(document.body).getPropertyValue('--color-orange')
});
function removeFromWishlistAfterMoveToBag($btn) {
    var $card = $btn.closest('.wishlist-card');
    var productId = $card.length ? $btn.attr('data-product-id') : null;

    if (!$card.length && $btn.closest('#quick-view').length) {
        // Multi-variant wishlist items open the quick-view popup instead of
        // adding straight to cart (data-izimodal-open="#quick-view"); its
        // "Add to Cart" button (#modal-add-to-cart-button) lives in
        // footer.php, not inside the wishlist card, so it can never be found
        // via .closest('.wishlist-card'). Trace back via the product id the
        // "Move to bag" click already stashed on #quick-view when it opened.
        var quickViewProductId = $("#quick-view").data("data-product-id");
        if (quickViewProductId) {
            $card = $('.wishlist-card .add_to_cart[data-product-id="' + quickViewProductId + '"]').closest('.wishlist-card');
            productId = quickViewProductId;
        }
    }

    if (!$card.length || !productId) return;

    var formdata = new FormData();
    formdata.append(csrfName, csrfHash);
    formdata.append('product_id', productId);
    $.ajax({
        type: 'POST',
        url: base_url + 'my-account/manage-favorites',
        data: formdata,
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (res) {
            csrfName = res.csrfName;
            csrfHash = res.csrfHash;
            $card.fadeOut(300, function () {
                $(this).remove();
                var newCount = $(".wishlist-card-container > .wishlist-card").length;
                $(".wishlist .no-of-item-text > span").text(newCount);
                if (newCount === 0) {
                    location.reload();
                }
            });
            $('[data-wishlist-count], #wishlist-count').each(function () {
                var current = parseInt($(this).text(), 10) || 0;
                $(this).text(Math.max(0, current - 1));
            });
        }
    });
}

function queryParams(e) {
    return {
        limit: e.limit,
        sort: e.sort,
        order: e.order,
        offset: e.offset,
        search: e.search
    }
}
var currency = $('#currency').val();
var auth_settings = $('#auth_settings').val();
console.log(auth_settings);

/* ---------------------------------------------------------------------------
 * Shared signup-modal helpers.
 *
 * Both the "firebase" and "sms" branches below drive the same two-step markup
 * (#send-otp-form -> #verify-otp-form), so step switching, button reset and
 * step-2 validation live here once instead of being copy-pasted (and drifting)
 * between the two branches.
 * ------------------------------------------------------------------------- */

// Step 1 was hidden with .hide() but step 2 was revealed by dropping .d-none.
// The modal's show.bs.modal reset only re-showed step 1 and never re-added
// .d-none, so re-opening the modal after a completed/abandoned attempt painted
// BOTH steps stacked in one dialog. Toggling both directions here fixes that.
// Three steps now, matching the seller registration flow:
//   1 Details (name/email/mobile, #send-otp-form)
//   2 Verify  (OTP, #signup-step-2 inside #verify-otp-form)
//   3 Password(#signup-step-3 inside the same form - submitting it registers)
// Steps 2 and 3 share one form so a single FormData carries the whole signup.
function showSignupStep(step) {
    if (step === 1) {
        $("#verify-otp-form").addClass("d-none").hide();
        $("#send-otp-form").show();
    } else {
        $("#send-otp-form").hide();
        $("#verify-otp-form").removeClass("d-none").show();
    }
    $("#signup-step-2").toggleClass("d-none", step === 3);
    $("#signup-step-3").toggleClass("d-none", step !== 3);
}

function resetRegisterButton() {
    $("#register_submit_btn").html("Register Now").attr("disabled", !1);
}

// Each of the three helpers returns an error message, or "" when that step is good.
function validateSignupDetails() {
    if (!$.trim($("#signup-name").val())) return "Please enter your full name.";
    var email = $.trim($("#signup-email").val());
    // Email stays optional (accounts are keyed on mobile), but if one is typed it
    // has to look like an address - the server rejects it otherwise.
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return "Please enter a valid email address.";
    if (!$.trim($("#phone-number").val())) return "Please enter your mobile number.";
    return "";
}

function validateSignupOtp() {
    if (!$.trim($("#otp").val())) return "Please enter the OTP sent to your phone.";
    return "";
}

function validateSignupPassword() {
    if (!$("#password").val()) return "Please enter a password.";
    // .val() on both sides - the sms branch used to compare the jQuery objects
    // themselves, which are never === equal, so it rejected every single signup
    // with "Passwords do not match !" no matter what was typed.
    if ($("#confirm-password").val() !== $("#password").val()) return "Passwords do not match !";
    if (!$("#signup-terms").is(":checked")) return "Please accept the Terms & Conditions to continue.";
    return "";
}

// The details typed on step 1 live in #send-otp-form, which is not the form that
// gets submitted, so they are attached to the registration payload by hand.
function appendSignupDetails(formData) {
    formData.append("name", $.trim($("#signup-name").val()));
    formData.append("email", $.trim($("#signup-email").val()));
    formData.append("mobile", $("#phone-number").val());
    formData.append("country_code", $(".selected-dial-code").text());
    // Referral code. Sent as friends_code because that is the users column it
    // lands in, and the name every registration path already reads.
    formData.append("friends_code", normaliseReferralCode($("#signup-referral").val()));
    // Which channel the code arrived on, so the ledger can tell a scanned card
    // from a forwarded message. 'typed' is the honest default: if no link put a
    // code in the field, the user entered it themselves.
    formData.append("referral_source", storedReferralSource() || "typed");
}

/* ---------------------------------------------------------------------------
 * Referral code on signup.
 *
 * Codes travel two ways: typed from a message, or carried on a ?ref= share link.
 * The link case is the common one, so arriving with ?ref= opens the field and
 * fills it in - a code the visitor never has to notice is a code that cannot be
 * lost between landing and signing up. It is kept in sessionStorage because the
 * signup usually happens several pages after the landing.
 *
 * Codes are stored upper-case and unpunctuated server-side, so the same
 * normalisation runs here - otherwise a pasted "hj4k-cd2p" fails a check that
 * the server would have passed.
 * ------------------------------------------------------------------------- */
var REFERRAL_STORAGE_KEY = "cretzo_ref";
var REFERRAL_SOURCE_KEY = "cretzo_ref_src";

function normaliseReferralCode(value) {
    return $.trim(value || "").toUpperCase().replace(/[^A-Z0-9]/g, "");
}

function rememberReferralCode(code) {
    // Private browsing and blocked site data both throw on access rather than
    // returning null, and a referral code is never worth an exception on a page
    // the user is trying to read.
    try {
        if (code) window.sessionStorage.setItem(REFERRAL_STORAGE_KEY, code);
    } catch (e) {}
}

function storedReferralCode() {
    try {
        return window.sessionStorage.getItem(REFERRAL_STORAGE_KEY) || "";
    } catch (e) {
        return "";
    }
}

function rememberReferralSource(source) {
    try {
        if (source) window.sessionStorage.setItem(REFERRAL_SOURCE_KEY, source);
    } catch (e) {}
}

function storedReferralSource() {
    try {
        return window.sessionStorage.getItem(REFERRAL_SOURCE_KEY) || "";
    } catch (e) {
        return "";
    }
}

/* ---------------------------------------------------------------------------
 * The banner a scan lands on.
 *
 * Someone who has just scanned a card at a stall is holding a phone and needs to
 * see that something happened - the silent field-prefill that serves a clicked
 * link is not enough feedback for a scan. Shown once per session, and never to
 * somebody who is already signed in: a referral code only applies to a new
 * account, and telling an existing customer otherwise invites a support ticket.
 * ------------------------------------------------------------------------- */
var REFERRAL_BANNER_KEY = "cretzo_ref_seen";

function showReferralBanner(code) {
    try {
        if (window.sessionStorage.getItem(REFERRAL_BANNER_KEY) === code) return;
    } catch (e) {}

    if ($("body").hasClass("logged-in") || $("#is-logged-in").val() === "1") {
        return;
    }

    var $bar = $(
        '<div class="cz-ref-banner" role="status">' +
        '<span class="cz-ref-banner__text">Invite applied &mdash; code <strong>' + code + '</strong>. ' +
        'Sign up to use it on your first order.</span>' +
        '<button type="button" class="cz-ref-banner__cta" data-bs-toggle="modal" data-bs-target="#modal-signup">Sign up</button>' +
        '<button type="button" class="cz-ref-banner__close" aria-label="Dismiss">&times;</button>' +
        '</div>'
    );

    $("body").prepend($bar);
    try { window.sessionStorage.setItem(REFERRAL_BANNER_KEY, code); } catch (e) {}

    $bar.on("click", ".cz-ref-banner__close", function () { $bar.remove(); });
}

function showReferralFeedback(message, ok) {
    $("#referral-feedback").text(message || "")
        .toggleClass("is-valid", !!ok)
        .toggleClass("is-invalid", !!message && !ok);
}

// Server-side check of a typed code, so a typo is caught before the account is
// created rather than silently costing the referrer their reward.
function checkReferralCode() {
    var code = normaliseReferralCode($("#signup-referral").val());
    $("#signup-referral").val(code);

    if (!code) {
        showReferralFeedback("", false);
        return;
    }

    var payload = { code: code };
    payload[csrfName] = csrfHash;

    $.ajax({
        type: "POST",
        url: base_url + "auth/validate_referral",
        data: payload,
        dataType: "json",
        success: function (e) {
            csrfName = e.csrfName;
            csrfHash = e.csrfHash;
            showReferralFeedback(e.message, !e.error);
        },
        error: function () {
            // A failed check must not block the signup - the binding is validated
            // again server-side at registration either way.
            showReferralFeedback("", false);
        }
    });
}

$(document).on("click", "#referral-toggle", function (e) {
    e.preventDefault();
    $("#referral-field").removeClass("d-none");
    $(this).addClass("d-none");
    $("#signup-referral").focus();
});

$(document).on("blur", "#signup-referral", checkReferralCode);

/* Put a remembered code back into the form and open the row for it.
 *
 * Called on page load AND every time the signup modal opens, because the modal's
 * show.bs.modal handler calls .reset() on the step-1 form - which clears this
 * field along with the rest. A visitor who lands on a share link or a scanned QR
 * and opens signup a few pages later would otherwise lose the code silently and
 * the referral would never be attributed: the commonest path of all, and the one
 * a curl-driven test cannot see, because it posts the field directly. */
function applyStoredReferral() {
    var code = storedReferralCode();
    if (!code) return;

    $("#signup-referral").val(code);
    $("#referral-field").removeClass("d-none");
    $("#referral-toggle").addClass("d-none");
    checkReferralCode();
}

$(function () {
    var fromUrl = "";
    var source = "";
    try {
        var params = new URLSearchParams(window.location.search);
        fromUrl = normaliseReferralCode(params.get("ref"));
        // Only the values we generate are honoured; anything else is a plain link.
        source = (params.get("src") === "qr") ? "qr" : (fromUrl ? "link" : "");
    } catch (e) {}

    if (fromUrl) {
        rememberReferralCode(fromUrl);
        rememberReferralSource(source);
        showReferralBanner(fromUrl);
    }

    applyStoredReferral();

    // 'shown', not 'show': the reset runs on 'show', so restoring the code has to
    // happen after it rather than racing it.
    $(document).on("shown.bs.modal", "#modal-signup", applyStoredReferral);
});

function showOtpError(message) {
    $("#otp-error").html(message).show();
    $("#verify-otp-button").html("Verify OTP").attr("disabled", !1);
}

// Shared step-2 -> step-3 hand-off for both auth branches.
function goToPasswordStep() {
    $("#otp-error").html("");
    $("#verify-otp-button").html("Verify OTP").attr("disabled", !1);
    $("#registration-error").html("");
    resetRegisterButton();
    showSignupStep(3);
    setTimeout(function () { $("#password").focus(); }, 50);
}

$(document).on("click", "#signup-back-to-details", function (e) {
    e.preventDefault();
    $("#otp-error").html("");
    $("#registration-error").html("");
    showSignupStep(1);
});

function showRegistrationError(message) {
    $("#registration-error").html(message).show();
    resetRegisterButton();
}

// Shared success path for both branches.
function onRegistrationSuccess(e) {
    Toast.fire({ icon: "success", title: e.message });
    resetRegisterButton();
    $("#registration-error").html("");
    $("#modal-signup").hide();
    $('#modal-signup').addClass('d-none');
    $("#modal-signin").show();
    $('#modal-signin').addClass('d-block show');
}

if (auth_settings == "firebase") {

    // Firebase's confirmationResult, held at module scope. It used to be captured
    // by a $(document).on("submit", "#verify-otp-form", ...) handler registered
    // INSIDE the signInWithPhoneNumber callback - so every "Send OTP" click bound
    // one more delegated handler to the document, and the Nth attempt fired N
    // parallel registrations for a single click of Register Now.
    window.otpConfirmationResult = null;

    function onSignInSubmit(e) {
        e.preventDefault();
        var detailsProblem = validateSignupDetails();
        if (detailsProblem) {
            $("#is-user-exist-error").html(detailsProblem).show();
            return;
        }
        $("#is-user-exist-error").html("");
        if (isPhoneNumberValid()) {
            $("#send-otp-button").html("Please Wait...");
            var t = is_user_exist();
            if (updateSignInButtonUI(), 1 == t.error) $("#is-user-exist-error").html(t.message), $("#send-otp-button").html("Send OTP");
            else {
                window.signingIn = !0;
                var a = getPhoneNumberFromUserInput(),
                    r = window.recaptchaVerifier;
                firebase.auth().signInWithPhoneNumber(a, r).then(function (e) {
                    window.otpConfirmationResult = e;
                    $("#send-otp-button").html("Send OTP"), $(".send-otp-form").unblock(), window.signingIn = !1, updateSignInButtonUI(), resetRecaptcha();
                    resetRegisterButton();
                    $("#registration-error").html("");
                    $("#otp-error").html("");
                    $("#signup-otp-mobile").text(getPhoneNumberFromUserInput());
                    window.otpVerified = !1;
                    showSignupStep(2);
                    setTimeout(function () { $("#otp").focus(); }, 50);
                }).catch(function (e) {
                    window.signingIn = !1, $("#is-user-exist-error").html(e.message).show(), $("#send-otp-button").html("Send OTP"), updateSignInButtonUI(), resetRecaptcha()
                })
            }
        }
    }

    // Step 2: confirm the code with Firebase before the password step opens. The
    // confirmation is consumed here, so the submit handler below must NOT confirm
    // again - a second confirm() on the same result always fails.
    window.otpVerified = !1;

    $(document).on("click", "#verify-otp-button", function (e) {
        e.preventDefault();
        $("#otp-error").html("");

        var problem = validateSignupOtp();
        if (problem) {
            showOtpError(problem);
            return;
        }
        if (!window.otpConfirmationResult) {
            showOtpError("Your OTP session expired. Please request a new OTP.");
            return;
        }

        $("#verify-otp-button").html("Please Wait...").attr("disabled", !0);

        window.otpConfirmationResult.confirm($("#otp").val()).then(function () {
            window.otpVerified = !0;
            goToPasswordStep();
        }).catch(function () {
            showOtpError("Invalid OTP. Please Enter Valid OTP");
        });
    });

    // Bound once, at load, outside any callback.
    $(document).on("submit", "#verify-otp-form", function (t) {
        t.preventDefault();
        $("#registration-error").html("");

        var problem = validateSignupPassword();
        if (problem) {
            showRegistrationError(problem);
            return;
        }
        if (!window.otpVerified) {
            showRegistrationError("Please verify the OTP sent to your phone first.");
            showSignupStep(2);
            return;
        }

        var formData = new FormData(this),
            action = $(this).attr("action");

        $("#register_submit_btn").html("Please Wait...").attr("disabled", !0);

        (function () {
            formData.append(csrfName, csrfHash);
            appendSignupDetails(formData);
            $.ajax({
                type: "POST",
                url: action,
                data: formData,
                processData: !1,
                contentType: !1,
                cache: !1,
                dataType: "json",
                success: function (e) {
                    csrfName = e.csrfName;
                    csrfHash = e.csrfHash;
                    if (e.error == true) {
                        resetRegisterButton();
                        Toast.fire({ icon: "error", title: e.message });
                        $("#registration-error").html(e.message).show();
                    } else {
                        onRegistrationSuccess(e);
                    }
                },
                error: function () {
                    // Without this the button stayed on "Please Wait..." forever
                    // whenever the request itself failed.
                    showRegistrationError("Something went wrong. Please try again.");
                }
            })
        })();
    });

    window.onload = function () {
        // #send-otp-form and #phone-number only exist on the login/signup screens.
        // These three lookups were unguarded, so on EVERY other page of the site
        // window.onload threw "Cannot read properties of null (reading
        // 'addEventListener')" - a red console error on every page load, which in
        // practice masks the real errors you are actually looking for. The throw
        // also aborted the rest of this handler, so the two #phone-number bindings
        // never ran even on a page where only the form was missing.
        var otpForm = document.getElementById("send-otp-form");
        var phoneInput = document.getElementById("phone-number");

        if (otpForm) {
            otpForm.addEventListener("submit", onSignInSubmit);
        }
        if (phoneInput) {
            phoneInput.addEventListener("keyup", updateSignInButtonUI);
            phoneInput.addEventListener("change", updateSignInButtonUI);
        }
    }
    function getPhoneNumberFromUserInput() {
        return $(".selected-dial-code").html() + $("#phone-number").val()
    }

    function isPhoneNumberValid() {
        return -1 !== getPhoneNumberFromUserInput().search(/^\+[0-9\s\-\(\)]+$/)
    }
}
function resetRecaptcha() {
    return window.recaptchaVerifier.render().then(function (e) {
        if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.reset === 'function') {
            try { grecaptcha.reset(e); } catch (ex) { }
        }
    })
}


if (auth_settings == "sms") {
    $(document).on("click", "#send-otp-button", function (e) {
        e.preventDefault();
        var detailsProblem = validateSignupDetails();
        if (detailsProblem) {
            $("#is-user-exist-error").html(detailsProblem).show();
            return;
        }
        $("#is-user-exist-error").html("");
        // r.append(csrfName, csrfHash), r.append("mobile", $("#phone-number").val()), r.append("country_code", $(".selected-dial-code").text()),
        console.log('not valid');
        console.log("in sms ");
        var t = $("#phone-number").val();
        console.log(t);
        // $phonenumber = $("#phone-number").val(), $username = $('input[name="username"]').val(), $email = $('input[name="email"]').val(), $passwd = $('input[name="password"]').val();
        $.ajax({
            type: "POST",
            async: !1,
            url: base_url + "auth/verify_user",
            data: {
                mobile: t,
                [csrfName]: csrfHash
            },
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName,
                    csrfHash = e.csrfHash,
                    resetRecaptcha();
                resetRegisterButton();
                $("#registration-error").html("");
                $("#otp-error").html("");
                $("#signup-otp-mobile").text($(".selected-dial-code").text() + t);
                showSignupStep(2);
                setTimeout(function () { $("#otp").focus(); }, 50);
            }
        })
    });

    // No client-side OTP check exists on this branch (the code is validated by
    // auth/register-user), so step 2 only checks the field is filled in and moves on.
    $(document).on("click", "#verify-otp-button", function (e) {
        e.preventDefault();
        var problem = validateSignupOtp();
        if (problem) {
            showOtpError(problem);
            return;
        }
        goToPasswordStep();
    });

    $(document).on("submit", "#verify-otp-form", function (t) {
        t.preventDefault();
        $("#registration-error").html("");

        var problem = validateSignupPassword() || validateSignupOtp();
        if (problem) {
            showRegistrationError(problem);
            return;
        }

        var formData = new FormData(this),
            action = $(this).attr("action");
        $("#register_submit_btn").html("Please Wait...").attr("disabled", !0);
        formData.append(csrfName, csrfHash);
        appendSignupDetails(formData);
        $.ajax({
            type: "POST",
            url: action,
            data: formData,
            processData: !1,
            contentType: !1,
            cache: !1,
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName;
                csrfHash = e.csrfHash;
                if (e.error == true) {
                    resetRegisterButton();
                    Toast.fire({ icon: "error", title: e.message });
                    $("#registration-error").html(e.message).show();
                } else {
                    onRegistrationSuccess(e);
                }
            },
            error: function () {
                showRegistrationError("Something went wrong. Please try again.");
            }
        })
    })

    function resetRecaptcha() {
        return window.recaptchaVerifier.render().then(function (e) {
            if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.reset === 'function') {
                try { grecaptcha.reset(e); } catch (ex) { }
            }
        })
    }
}

function updateSignInButtonUI() { }

function is_user_exist(e = "") {
    if ("" == e) var t = $("#phone-number").val();
    else t = e;
    var a;
    return $.ajax({
        type: "POST",
        async: !1,
        url: base_url + "auth/verify_user",
        data: {
            mobile: t,
            [csrfName]: csrfHash
        },
        dataType: "json",
        success: function (e) {
            csrfName = e.csrfName, csrfHash = e.csrfHash, a = e
        }
    }), a
}

function formatRepo(e) {
    if (e.loading) return e.text;
    var t = "<div class='select2-result-repository clearfix'><div class='select2-result-repository__avatar'><img src='" + e.image_sm + "' /></div><div class='select2-result-repository__meta'><div class='select2-result-repository__title'>" + e.name + "</div>";
    return e.category_name && (t += "<div class='select2-result-repository__description'> In " + e.category_name + "</div>"), t
}

function formatRepoSelection(e) {
    return e.name || e.text
}

function ensureAuthLoadingLayer() {
    var $loginModal = $("#modal-signin");
    if (!$loginModal.length) return;

    if (!$loginModal.find(".auth-login-loading").length) {
        $loginModal.append(
            '<div class="auth-login-loading d-none">' +
            '<div class="auth-login-loading-box">' +
            '<div class="auth-login-spinner"></div>' +
            '<div class="auth-login-loading-copy">' +
            '<div class="auth-login-loading-title">Signing in</div>' +
            '<div class="auth-login-loading-text">Please wait while we complete sign-in...</div>' +
            '<div class="auth-login-dots"><span></span><span></span><span></span></div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
    }
}

function setSocialButtonLoading(provider, isLoading) {
    var selector = '.social-auth-link[data-auth-provider="' + provider + '"]';
    var label = provider === 'google' ? 'Google' : 'Facebook';
    $(selector).each(function () {
        var $btn = $(this).find('.media-container');
        if (!$btn.length) return;
        if (!$btn.attr('data-original-html')) {
            $btn.attr('data-original-html', $btn.html());
        }
        if (isLoading) {
            $btn.addClass('auth-social-loading');
            $btn.html('<span class="auth-inline-spinner"></span><p class="text-s">Signing in with ' + label + '...</p>');
            $(this).css('pointer-events', 'none');
        } else {
            $btn.removeClass('auth-social-loading');
            $btn.html($btn.attr('data-original-html'));
            $(this).css('pointer-events', '');
        }
    });
}

function toggleAuthLoading(show, text) {
    ensureAuthLoadingLayer();
    var $loader = $("#modal-signin .auth-login-loading");
    if (!$loader.length) return;

    if (text) {
        $loader.find(".auth-login-loading-title").text(text);
    }

    if (show) {
        $loader.removeClass("d-none");
    } else {
        $loader.addClass("d-none");
    }
}

function closeLoginPopupFast() {
    var $loginModal = $("#modal-signin");
    if (!$loginModal.length) return;

    try {
        if (window.bootstrap && bootstrap.Modal) {
            var instance = bootstrap.Modal.getInstance($loginModal[0]);
            if (instance) {
                instance.hide();
            } else {
                $loginModal.modal('hide');
            }
        } else if (typeof $loginModal.modal === 'function') {
            $loginModal.modal('hide');
        }
    } catch (err) {
        // no-op
    }

    // Fallback cleanup in case modal plugin leaves overlay behind.
    $loginModal.removeClass("show").css("display", "none");
    $(".modal-backdrop").remove();
    $("body").removeClass("modal-open").css("padding-right", "");
    setSocialButtonLoading('google', false);
    setSocialButtonLoading('facebook', false);
    toggleAuthLoading(false);
}

// Bootstrap 4 JS (bootstrap.min.js) and Bootstrap 5 JS (plugins.js) are BOTH loaded
// while only BS4 CSS is present. In that mixed setup a modal hide can leave an orphaned
// .modal-backdrop + body.modal-open (overflow:hidden) behind, greying out and locking the
// whole page. This strips those artifacts whenever no modal/offcanvas is genuinely open.
function cleanupModalArtifacts() {
    if ($('.modal.show').length || $('.offcanvas.show').length) return;
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open')
             .css({ 'overflow': '', 'overflow-y': '', 'padding-right': '' });
}

// Normal path: Bootstrap finished hiding a modal/offcanvas (BS4 and BS5 both fire these,
// and BS5's native events bubble so this jQuery delegate catches both).
$(document).on('hidden.bs.modal', '.modal', cleanupModalArtifacts);
$(document).on('hidden.bs.offcanvas', '.offcanvas', cleanupModalArtifacts);

// The floating chat button (.fixed-icon) sits at a very high z-index so it stays above
// page content, which also puts it above the mini-cart / nav offcanvas panels. Hide it
// while any modal or offcanvas (e.g. the mini cart) is open so it can't float over them.
$(document).on('show.bs.offcanvas', '.offcanvas', function () {
    $('.fixed-icon').addClass('chat-fab-hidden');
});
$(document).on('shown.bs.modal', '.modal', function () {
    $('.fixed-icon').addClass('chat-fab-hidden');
});
$(document).on('hidden.bs.offcanvas hidden.bs.modal', '.offcanvas, .modal', function () {
    if (!$('.offcanvas.show').length && !$('.modal.show').length) {
        $('.fixed-icon').removeClass('chat-fab-hidden');
    }
});

// Any dismiss trigger — BS5 (data-bs-dismiss) or BS4 (data-dismiss).
$(document).on('click',
    '[data-bs-dismiss="modal"], [data-dismiss="modal"], [data-bs-dismiss="offcanvas"], [data-dismiss="offcanvas"]',
    function () { setTimeout(cleanupModalArtifacts, 400); });

// Escape hatch: an ORPHANED backdrop (no modal actually visible) must never trap the page —
// clicking the grey area force-clears it. If a modal is genuinely open, let Bootstrap handle it.
$(document).on('click', '.modal-backdrop', function () {
    if ($('.modal.show').length || $('.offcanvas.show').length) {
        setTimeout(cleanupModalArtifacts, 400);
        return;
    }
    $(this).remove();
    cleanupModalArtifacts();
});

// Safety net: ESC key close.
$(document).on('keydown', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        setTimeout(cleanupModalArtifacts, 400);
    }
});

$(document).on("submit", ".form-submit-event", function (e) {
    e.preventDefault();
    var t = new FormData(this),
        a = $(this).attr("id"),
        r = $("#error_box", this),
        s = $(this).find(".submit_btn"),
        i = $(this).find(".submit_btn").html(),
        o = $(this).find(".submit_btn").val(),
        n = "" != i || "undefined" != i ? i : o;
    t.append(csrfName, csrfHash),
        $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            data: t,
            beforeSend: function () {
                if ("login_form" == a) {
                    toggleAuthLoading(true, "Signing in...");
                    s.html("Signing in...");
                } else {
                    s.html("Please Wait..");
                }
                s.attr("disabled", !0)
            },
            cache: !1,
            contentType: !1,
            processData: !1,
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 1 == e.error ? (r.addClass("rounded p-3 alert alert-danger").removeClass("d-none alert-success"),
                    r.show().delay(5e3).fadeOut(),
                    r.html(e.message),
                    s.html(n), s.attr("disabled", !1),
                    "login_form" == a && toggleAuthLoading(false)) : (r.addClass("rounded p-3 alert alert-success").removeClass("d-none alert-danger"),
                        r.show().delay(3e3).fadeOut(),
                        r.html(e.message),
                        s.html(n),
                        s.attr("disabled", !1),
                        $(".form-submit-event")[0].reset(), "login_form" == a && cart_sync(),
                        "login_form" == a ? (closeLoginPopupFast(),
                            setTimeout(function () {
                                location.reload()
                            }, 120)) : setTimeout(function () {
                                location.reload()
                            }, 600))
            }
        })
}),
    // window.onload = function () {
    //     document.getElementById("send-otp-form").addEventListener("submit", onSignInSubmit),
    //     document.getElementById("phone-number").addEventListener("keyup", updateSignInButtonUI),
    //     document.getElementById("phone-number").addEventListener("change", updateSignInButtonUI)
    // },

    $(document).on("click", "#resend-otp", function (e) {
        e.preventDefault()
    }),

    $(document).on("submit", ".sign-up-form", function (e) {
        e.preventDefault();
        var t = $(".selected-dial-code").html();
        $phonenumber = $("#phone-number").val(), $username = $('input[name="username"]').val(), $email = $('input[name="email"]').val(), $passwd = $('input[name="password"]').val();
        $.ajax({
            type: "POST",
            url: base_url + "auth/register_user",
            data: {
                country_code: t,
                type: "phone",
                mobile: $phonenumber,
                name: $username,
                email: $email,
                password: $passwd,
                [csrfName]: csrfHash
            },
            dataType: "json",
            success: function (result) {
                if (result.error == true) {
                    $('#sign-up-error').html('<span class="text-danger" >' + result.message + '</span>');
                }
            }
        })
    });
var search_products = $(".search_product").select2({
    ajax: {
        url: base_url + "home/get_products",
        dataType: "json",
        delay: 250,
        data: function (e) {
            return {
                search: e.term,
                page: e.page
            }
        },
        processResults: function (e, t) {
            return console.log(e), t.page = t.page || 1, {
                results: e.data,
                pagination: {
                    more: 30 * t.page < e.total
                }
            }
        },
        cache: !0
    },
    theme: "bootstrap-5",
    dropdownParent: $("#offcanvas-search"),
    escapeMarkup: function (e) {
        return e
    },
    minimumInputLength: 1,
    templateResult: formatRepo,
    templateSelection: formatRepoSelection,
    placeholder: "Search for products, brands or categories"
});
search_products.on("select2:select", function (e) {
    var t = e.params.data;
    null != t.link && null != t.link && (window.location.href = t.link)
}),
    $("#leftside-navigation .sub-menu > a").click(function (e) {
        $("#leftside-navigation ul ul").slideUp(), !$("#leftside-navigation .sub-menu > a").next().is(":visible") && $("#leftside-navigation .sub-menu > a").find(".arrow").removeClass("fa-angle-down").addClass("fa-angle-left"), $(this).find(".arrow").hasClass("fa-angle-left") ? $(this).find(".arrow").removeClass("fa-angle-left").addClass("fa-angle-down") : $(this).find(".arrow").removeClass("fa-angle-down").addClass("fa-angle-left"), $(this).next().is(":visible") || $(this).next().slideDown(), e.stopPropagation()
    }), $("li.has-ul").click(function () {
        $(this).children(".sub-ul").slideToggle(500), $(this).toggleClass("active"), event.preventDefault()
    }),

    $(".add-to-fav-btn").on("click", function (e) {
        e.preventDefault();
        var t = new FormData,
            a = $(this).data("product-id"),
            r = $(this);
        t.append(csrfName, csrfHash), t.append("product_id", a), $.ajax({
            type: "POST",
            url: base_url + "my-account/manage-favorites",
            data: t,
            cache: !1,
            contentType: !1,
            processData: !1,
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName;
                csrfHash = e.csrfHash;
                if (e.error == true) {
                    Toast.fire({
                        icon: "error",
                        title: e.message
                    });
                } else {
                    if (r.hasClass("fa-heart-o")) {
                        r.removeClass("fa-heart-o");
                        r.addClass("fa-heart").css("color", "red");
                    } else if (r.hasClass("fa-heart")) {
                        // r.hasClass("fa-heart");
                        r.removeClass("fa-heart");
                        r.addClass("fa-heart-o").css("color", "black");
                    }
                }
            }
        })
    }),

    $(document).on("click", "#add_to_favorite_btn", function (e) {
        e.preventDefault();

        if (0 == is_loggedin) {
            $('#modal-signin').modal('show');
            return;
        }
        
        var t = new FormData,
            a = $(this).data("product-id"),
            r = $(this),
            s = $(this).html();
        t.append(csrfName, csrfHash), t.append("product_id", a), $.ajax({
            type: "POST",
            url: base_url + "my-account/manage-favorites",
            data: t,
            cache: !1,
            contentType: !1,
            processData: !1,
            dataType: "json",
            beforeSend: function () {
                r.attr("disabled", !0), r.find("span").text("Please wait")
            },
            success: function (e) {
                csrfName = e.csrfName;
                csrfHash = e.csrfHash;
                if (e.error == true) {
                    Toast.fire({
                        icon: "error",
                        title: e.message
                    });

                    r.find("span").text("Wishlist");
                    r.removeAttr("disabled");
                } else {

                    /* let isFav = r.attr("data-is-fav");
                    r.attr("data-is-fav", isFav == "true" ? "false" : "true");

                    let t_i = r.find("i");
                    let t_span = r.find("span");

                    if(r.attr("data-is-fav") == "true"){
                        t_span.text("Remove");

                        r.removeClass('add-fav');
                        r.addClass('remove-fav');

                        t_i.addClass("fa-heart");
                        t_i.removeClass("fa-heart-o");
                    }
                    else{
                        t_span.text("Wishlist");
                        
                        r.addClass('add-fav');
                        r.removeClass('remove-fav');

                        t_i.addClass("fa-heart-o");
                        t_i.removeClass("fa-heart");
                    }
                    
                    r.removeAttr("disabled"); */

                    /* Update UI in-place (no reload) */
                    csrfName = e.csrfName;
                    csrfHash = e.csrfHash;

                    // Toggle button state if present
                    try {
                        // buttons that carry an <i> icon and a <span>
                        var t_i = r.find('i');
                        var t_span = r.find('span');

                        if (t_i && t_i.length) {
                            if (t_i.hasClass('fa-heart-o')) {
                                t_i.removeClass('fa-heart-o').addClass('fa-heart');
                                t_i.css('color', ''); // let CSS theme the filled heart (orange, not red)
                            } else if (t_i.hasClass('fa-heart')) {
                                t_i.removeClass('fa-heart').addClass('fa-heart-o');
                                t_i.css('color', '');
                            }
                        }

                        // toggle classes for text buttons
                        if (r.hasClass('add-fav')) {
                            r.removeClass('add-fav').addClass('remove-fav');
                            if (t_span && t_span.length) t_span.text('Remove from Wishlist');
                        } else if (r.hasClass('remove-fav')) {
                            r.removeClass('remove-fav').addClass('add-fav');
                            if (t_span && t_span.length) t_span.text('Wishlist');
                        }

                        // For simple heart anchors (fa icons without span)
                        if (r.hasClass('fa-heart-o')) {
                            r.removeClass('fa-heart-o').addClass('fa-heart').css('color', 'red');
                        } else if (r.hasClass('fa-heart')) {
                            r.removeClass('fa-heart').addClass('fa-heart-o').css('color', '');
                        }

                        // Update wishlist count badges in header (if present)
                        try {
                            var $count = $('.icon-num, .icon-num-m').not('#cart-count').not('.cart-count').not('.cart-count-checked');
                            if ($count && $count.length && typeof e.total !== 'undefined') {
                                $count.text(e.total);
                            } else if ($count && $count.length) {
                                // best-effort: increment/decrement based on message
                                var cur = parseInt($count.first().text()) || 0;
                                var delta = /added|added to favorite|added to favorites/i.test(e.message) ? 1 : (/removed/i.test(e.message) ? -1 : 0);
                                cur = Math.max(0, cur + delta);
                                $count.text(cur);
                            }
                        } catch (ex) { /* ignore */ }

                    } catch (ex) { /* non-fatal UI update error */ }

                    // show success toast
                    Toast.fire({
                        icon: 'success',
                        title: e.message || 'Wishlist updated'
                    });

                    // restore button state
                    r.removeAttr('disabled');

                    /* if (r.hasClass("fa-heart-o")) {
                        r.removeClass("fa-heart-o");
                        r.addClass("fa-heart").css("color", "red");
                        
                        
                    } else if (r.hasClass("fa-heart")) {
                        // r.hasClass("fa-heart");
                        r.removeClass("fa-heart");
                        r.addClass("fa-heart-o").css("color", "black");
                    } */
                }
            }
        })
    }),



    $(function () {
        if ($(".auth-modal").iziModal({
            overlayClose: !1,
            overlayColor: "rgba(0, 0, 0, 0.6)"
        }),

            $("#user-review-images").length) {
            var e;
            e = $("#review-image-title").data("review-title");
            var t = $("#review-image-title").data("product-id"),
                a = "";

            $("#user-review-images").iziModal({
                overlayClose: !1,
                overlayColor: "rgba(0, 0, 0, 0.6)",
                title: e,
                // headerColor: "var(--primary-color)",
                arrowKeys: !1,
                fullscreen: !0,
                onOpening: function (e) {
                    e.startLoading();
                    var a = $("#review-image-title").data("review-limit"),
                        s = $("#review-image-title").data("review-offset"),
                        i = $("#review-image-title").data("reached-end");
                    $("#load_more_div").html('<div id="load_more"></div>'), 0 == i && r(t, a, s), e.stopLoading()
                },
                onOpened: function () {
                    $("div").bind("wheel", function (e) {
                        if ($("#load_more").length && $(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
                            var t = $("#review-image-title").data("product-id"),
                                a = $("#review-image-title").data("review-limit"),
                                s = $("#review-image-title").data("review-offset");
                            0 == $("#review-image-title").data("reached-end") && r(t, a, s)
                        }
                    })
                }
            })
        }

        function r(e, t, r, s = "#user_image_data") {
            $("#review-image-title").data("review-offset", r + t), $.getJSON(base_url + "products/get_rating?product_id=" + e + "&has_images=1&limit=" + t + "&offset=" + r, function (e) {
                $("#review-image-title").data("review-offset", r + t), a = "";
                var i = 0;
                if (0 == e.error)
                    for (var o = 0; o < e.data.product_rating.length; o++) {
                        i = e.data.product_rating[o];
                        for (var n = 0; n < i.images.length; n++) {
                            var c = i.images;
                            a += "<div class='review-box m-2'><a href='" + c[n] + "' data-lightbox='review-images-12345' data-title='<font >" + i.rating + " ★</font></br>" + i.user_name + "<br>" + i.comment + "'><img src='" + c[n] + "' alt='Review Image' style='height: 70px; width: 70px;'></a></div>"
                        }
                    } else $("#review-image-title").data("reached-end", "true");
                $(s).append(a)
            })
        }
        $("#seller_info").length && $("#seller_info").iziModal({
            overlayClose: !0,
            overlayColor: "rgba(0, 0, 0, 0.6)",
            title: "Sold By",
            headerColor: "#f44336c4",
            arrowKeys: !1,
            fullscreen: !0,
            onOpening: function (e) {
                e.startLoading(), e.stopLoading()
            }
        }),

            $("#quick-view").iziModal({

                overlayClose: !1,
                overlayColor: "rgba(0, 0, 0, 0.6)",
                width: 1e3,
                onOpening: function (modal) {

                    modal.startLoading();

                    $('#modal-product-tags').html('');

                    $.getJSON(base_url + 'products/get-details/' + modal.$element.data('dataProductId'), function (data) {
                        
                        var statistics = $('.item-view').data("statistics");
                        console.log(data);
                        console.log(statistics);
                        var total_images = 0;
                        $('#modal-add-to-cart-button').attr('data-product-id', data.id);
                        $('#modal-add-to-cart-button').attr('data-product-slug', data.slug);
                        if (data.type == "simple_product" || data.type == "digital_product") {
                            $('#modal-add-to-cart-button').attr('data-product-variant-id', data.variants[0].id);
                        } else {
                            $('#modal-add-to-cart-button').attr('data-product-variant-id', '');
                        }
                        if (data.minimum_order_quantity != 1 && data.minimum_order_quantity != '' && data.minimum_order_quantity != 'undefined') {
                            $(".in-num").attr({
                                "data-min": data.minimum_order_quantity // values (or variables) here
                            });
                            $(".minus").attr({
                                "data-min": data.minimum_order_quantity // values (or variables) here
                            });
                            $("#modal-add-to-cart-button").attr({
                                "data-min": data.minimum_order_quantity // values (or variables) here
                            });
                        } else {
                            $(".in-num").attr({
                                "data-min": 1 // values (or variables) here
                            });
                            $(".minus").attr({
                                "data-min": 1 // values (or variables) here
                            });
                            $("#modal-add-to-cart-button").attr({
                                "data-min": 1 // values (or variables) here
                            });
                        }

                        if (data.quantity_step_size != 1 && data.quantity_step_size != '' && data.quantity_step_size != 'undefined') {
                            $(".in-num").attr({
                                "data-step": data.quantity_step_size // values (or variables) here
                            });
                            $(".minus").attr({
                                "data-step": data.quantity_step_size // values (or variables) here
                            })
                            $(".plus").attr({
                                "data-step": data.quantity_step_size // values (or variables) here
                            })

                            $("#modal-add-to-cart-button").attr({
                                "data-step": data.quantity_step_size // values (or variables) here
                            })
                        } else {
                            $(".in-num").attr({
                                "data-step": 1 // values (or variables) here
                            });
                            $(".minus").attr({
                                "data-step": 1 // values (or variables) here
                            })
                            $(".plus").attr({
                                "data-step": 1 // values (or variables) here
                            })
                            $("#modal-add-to-cart-button").attr({
                                "data-step": 1 // values (or variables) here
                            })
                        }

                        if (data.total_allowed_quantity != '' && data.total_allowed_quantity != 'undefined' && data.total_allowed_quantity != null) {
                            $(".in-num").attr({
                                "data-max": data.total_allowed_quantity // values (or variables) here
                            });
                            $(".plus").attr({
                                "data-max": data.total_allowed_quantity // values (or variables) here
                            })
                            $("#modal-add-to-cart-button").attr({
                                "data-max": data.total_allowed_quantity // values (or variables) here
                            })
                        } else {
                            // No configured cap -> leave max empty (unlimited on the
                            // client, same as the product detail page). A hard "1" here
                            // locked the +/- stepper so it could never increment.
                            $(".in-num").attr({
                                "data-max": '' // values (or variables) here
                            });
                            $(".plus").attr({
                                "data-max": '' // values (or variables) here
                            });
                            $("#modal-add-to-cart-button").attr({
                                "data-max": '' // values (or variables) here
                            })
                        }

                        // jQuery caches .data() on first read and does NOT refresh it
                        // when .attr() changes the underlying data-* attribute. Clear the
                        // cache so the stepper handler re-reads this product's min/max/step.
                        $(".in-num, .minus, .plus").removeData("min").removeData("max").removeData("step");

                        $("#modal-product-quantity").val(data.minimum_order_quantity ? data.minimum_order_quantity : 1);
                        var title_slug = "";

                        if (data.name) {
                            var title_slug = '<a class="text-decoration-none" title="' + data.name + '" target="_blank" href="' + base_url + 'products/details/' + data.product_slug + '"><p class="text-dark">' + data.name + '</p></a>';
                            $('#modal-product-title').html(title_slug);
                        }

                        // $('#modal-product-title').text(data.name);
                        $('#modal-product-short-description').text(data.short_description);
                        $('#modal-product-rating').rating('update', data.rating);

                        // var price = data.get_price.range
                        if ((data.variants[0].special_price < data.variants[0].price) && (data.variants[0].special_price != 0)) {
                            var price = data.variants[0].special_price
                            $('#modal-product-special-price').text(currency + data.variants[0].price);
                            $('#modal-product-special-price-div').show();
                            // Discount %, mirroring the product detail page's "(NN% OFF)"
                            var qvDiscount = Math.round((data.variants[0].price - data.variants[0].special_price) / data.variants[0].price * 100);
                            $('#modal-product-discount').text(qvDiscount > 0 ? '(' + qvDiscount + '% OFF)' : '');
                        } else {
                            var price = data.variants[0].price
                            $('#modal-product-special-price-div').hide();
                            $('#modal-product-discount').text('');
                        }
                        // var price = data.variants[0].price
                        $('#modal-product-price').html(currency + price);

                        // Stock indicator, mirroring the product detail page ("N in stock")
                        var qvStock = (data.variants[0] && data.variants[0].stock != null) ? data.variants[0].stock : data.stock;
                        if (qvStock !== undefined && qvStock !== null && qvStock !== '') {
                            $('#modal-product-stock').html('<i class="uil uil-check-circle"></i> ' + qvStock + ' in stock').show();
                        } else {
                            $('#modal-product-stock').empty().hide();
                        }


                        //Quick View Product Modal Gallery Swiper

                        quickViewgalleryThumbs = new Swiper('.gallery-thumbs', {
                            spaceBetween: 10,
                            slidesPerView: 4,
                            freeMode: true,
                            watchSlidesVisibility: true,
                            watchSlidesProgress: true,
                        });

                        quickViewgalleryTop = new Swiper('.gallery-top', {
                            spaceBetween: 10,
                            navigation: {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
                            },
                            thumbs: {
                                swiper: quickViewgalleryThumbs
                            },
                            clickable: true
                        });

                        //preview-image-swiper 

                        mobile_image_swiper = new Swiper('.mobile-image-swiper', {
                            pagination: {
                                el: '.mobile-image-swiper-pagination',
                            },
                            navigation: {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
                            },
                            clickable: true
                        });

                        quickViewgalleryThumbs.removeAllSlides();
                        quickViewgalleryTop.removeAllSlides();
                        mobile_image_swiper.removeAllSlides();


                        var thumb_images = $('<div class="swiper-slide" style="height:100px; width:108px;">' +
                            '<img src="' + data.image_md + '" alt="" />' +
                            '</div>');
                        $(".swiper-wrapper-thumbs").append(thumb_images);


                        var main_images = $('<div class="swiper-slide swiper-image"><div class=product-view-image-container">' +
                            '<img src="' + data.image_md + '" class="rounded" alt="" />' +
                            '</div></div>');
                        $(".swiper-wrapper-main").append(main_images);

                        var mobile_slider_image = $('<div class="swiper-slide text-center"><img src="' + data.image_md + '"></div>');
                        $(".mobile-swiper").append(mobile_slider_image);

                        var variant_images_md = data.variants.map(function (value, index) {
                            return value.images_md;
                        });

                        $.each(variant_images_md, function (i, images) {

                            if (images != null && images != '') {

                                $.each(images, function (i, url) {

                                    var thumb_images = $('<div class="swiper-slide" style="height:100px; width:108px;">' +
                                        '<img src="' + url + '" alt="" />' +
                                        '</div>');
                                    $(".swiper-wrapper-thumbs").append(thumb_images);

                                    var main_images = $('<div class="swiper-slide swiper-image"><div class=product-view-image-container">' +
                                        '<img src="' + url + '" class="rounded" alt="" />' +
                                        '</div></div>');
                                    $(".swiper-wrapper-main").append(main_images);

                                    mobile_slider_image = $('<div class="swiper-slide text-center"><img src="' + url + '"></div>');

                                    $(".mobile-swiper").append(mobile_slider_image);

                                });
                            }
                        });


                        $.each(data.other_images_md, function (i, url) {

                            total_images++;

                            var thumb_images = $('<div class="swiper-slide" style="height:100px; width:108px;">' +
                                '<img src="' + url + '" alt="" />' +
                                '</div>');
                            $(".swiper-wrapper-thumbs").append(thumb_images);

                            var main_images = $('<div class="swiper-slide swiper-image"><div class="product-view-image-container">' +
                                '<img src="' + url + '" class="rounded" alt="" />' +
                                '</div></div>');
                            $(".swiper-wrapper-main").append(main_images);

                            mobile_slider_image = $('<div class="swiper-slide text-center"><img src="' + url + '"></div>');
                            $(".mobile-swiper").append(mobile_slider_image);

                        });

                        if (thumb_images.length > 1) {
                            quickViewgalleryThumbs.addSlide(1, thumb_images);
                        }
                        if (main_images.length > 1) {
                            quickViewgalleryTop.addSlide(1, main_images);

                        }
                        if (mobile_slider_image.length > 1) {
                            mobile_image_swiper.addSlide(1, mobile_slider_image);
                        }


                        var variant_attributes = '';

                        var is_image = 0;

                        var is_color = 0;

                        $.each(data.variant_attributes, function (i, e) {
                            var attribute_ids = e.ids.split(',');
                            var attribute_values = e.values.split(',');
                            var swatche_types = e.swatche_type.split(',');
                            var swatche_values = e.swatche_value.split(',');
                            var style = '<style> .product-page-details .btn-group>.active { border: 1px solid black;}</style>';


                            variant_attributes += '<h4>' + e.attr_name + '</h4><div class="btn-group btn-group-toggle gap-1" data-toggle="buttons">';

                            $.each(attribute_ids, function (j, id) {

                                var color_code = "";

                                if (swatche_types[j] == "1") {

                                    is_color = 1;

                                    color_code = ' style="background-color:' + swatche_values[j] + ' !important;"';

                                    variant_attributes += '<style> .product-page-details .btn-group>.active { border: 1px solid black;}</style>' +

                                        '<label class="btn text-center fullCircle rounded-circle p-3 h-0"' + color_code + '>' +

                                        '<input type="radio" name="' + e.attr_name + '" value="' + id + '" class="modal-product-attributes" autocomplete="off"><br>' +

                                        '</label>';

                                } else if (swatche_types[j] == "2") {

                                    is_image = 1;

                                    variant_attributes += '<style> .product-page-details .btn-group>.active { color: #000000; border: 1px solid black;}</style>' + '<label class="btn text-center bg-transparent h-10 w-10">' +

                                        '<img class="swatche-image h-10 w-10" src="' + swatche_values[j] + '">' +

                                        '<input type="radio" name="' + e.attr_name + '" value="' + id + '" class="modal-product-attributes" autocomplete="off"><br>' +

                                        '</label>';

                                } else {

                                    var style1 = '<style> .product-page-details .btn-group>.active { background-color: var(--primary-color);color: white!important;}</style>';

                                    variant_attributes += style1 +
                                        '<label class="btn btn-default text-center rounded-2 btn-aqua btn-sm">' +
                                        '<input type="radio" name="' + e.attr_name + '" value="' + id + '" class="modal-product-attributes" autocomplete="off">' + attribute_values[j] + '<br>' +
                                        '</label>';
                                }
                            });
                            variant_attributes += '</div>';
                        });

                        var className = (data.is_deliverable == false) ? "danger" : "success";
                        var is_not = (data.is_deliverable == false) ? "not" : "";
                        var err_msg = (data.zipcode != "" && typeof data.zipcode !== 'undefined') ? '<b class="text-' + className + '">Product is ' + is_not + ' delivarable on &quot; ' + data.zipcode + ' &quot; </b>' : "";

                        if (data.type != "digital_product") {
                            variant_attributes +=
                                '<div class="d-flex flex-row qv-delivery-heading">' +
                                '<h4 class="text-n mb-2 fw-bold opacity-75">DELIVERY OPTIONS</h4>' +
                                '<i class="uil uil-truck ship-icon ml-2"></i>' +
                                '</div>' +
                                '<form class="mt-2 validate_zipcode_quick_view "   method="post" >' +
                                '<div class="d-flex flex-nowrap input-group">' +
                                '<div class="pl-0">' +
                                '<input type="hidden" name="product_id" value="' + data.id + '">' +
                                '<input type="hidden" name="' + csrfName + '" value="' + csrfHash + '">' +
                                '<input type="text" class="form-control rounded" id="zipcode" placeholder="Enter Pincode" name="zipcode" autocomplete="off" required value="' + data.zipcode + '">' +
                                '</div>' +
                                '<button type="submit" class="cretzo btn btn-sm ml-0 btn-primary check-availability" data-product_id="' + data.id + '"  data-zipcode="' + data.zipcode + '"  id="validate_zipcode">Check Availability</button>' +
                                '</div>' +
                                '<div class="mt-2" id="error_box1">' +
                                err_msg +
                                ' </div>' +
                                ' </form>';
                        } else {
                            variant_attributes +=
                                '<form class="mt-2 validate_zipcode_quick_view "   method="post" >' +
                                '<div class="d-flex">' +
                                '<div class=" col-md-6 pl-0">' +
                                '<input type="hidden" name="product_id" value="' + data.id + '">' +
                                '<input type="hidden" name="' + csrfName + '" value="' + csrfHash + '">' +
                                '</div>' +
                                '</div>' +
                                '<div class="mt-2" id="error_box1">' +
                                err_msg +
                                ' </div>' +
                                ' </form>';
                        }

                        $('#modal-product-variant-attributes').html(variant_attributes);

                        if (data.is_deliverable == false && data.zipcode != "" && typeof data.zipcode !== 'undefined') {

                            $('#modal-add-to-cart-button').attr('disabled', 'true');

                        } else {

                            $('#modal-add-to-cart-button').removeAttr('disabled');

                        }

                        var variants = '';

                        total_images = 1;

                        $.each(data.variants, function (i, e) {

                            variants += '<input type="hidden" class="modal-product-variants" data-image-index="' + total_images + '" name="variants_ids" data-name="' + data.name + '" value="' + e.variant_ids + '" data-id="' + e.id + '" data-price="' + e.price + '" data-special_price="' + e.special_price + '">';

                            total_images += e.images.length;

                        });

                        $('#modal-product-variants-div').html(variants);

                        // Reset the wishlist button fully each open (the modal is reused,
                        // so stale add-fav/remove-fav + heart icon from a previous product
                        // must be cleared). Outline heart = not saved, filled = saved —
                        // same visual language as the product detail page.
                        var $favBtn = $('#add_to_favorite_btn');
                        $favBtn.attr('data-product-id', data.id);
                        var $favIcon = $favBtn.find('i');
                        if (data.is_favorite == 1) {
                            $favBtn.removeClass('add-fav').addClass('remove-fav');
                            $favIcon.removeClass('fa-heart-o').addClass('fa-heart');
                            $favBtn.attr('data-is-fav', 'true');
                        } else {
                            $favBtn.removeClass('remove-fav').addClass('add-fav');
                            $favIcon.removeClass('fa-heart').addClass('fa-heart-o');
                            $favBtn.attr('data-is-fav', 'false');
                        }
                        $favIcon.css('color', '');

                        $('#compare').attr('data-product-id', data.id);

                        if (data.type == "simple_product") {

                            $('#compare').attr('data-product-variant-id', data.variants[0].id);

                        } else {

                            $('#compare').attr('data-product-variant-id', '');

                        }

                        var compare = '';

                        $.each(data, function (i, e) {

                            compare += '<button type="button" name="compare" class="buttons btn-6-6 extra-small m-0 compare" id="compare" data-product-id="' + data.id + '" data-product-variant-id="' + data.variants.id + '"><i class="fa fa-random"></i> Compare</button>';

                        });

                        $('#modal-product-no-of-ratings').text(data.no_of_ratings);

                        if (!$.isEmptyObject(data.tags)) {

                            var tags = 'Tags ';

                            $.each(data.tags, function (i, e) {

                                tags += '<a href="' + base_url + 'products/tags/' + e + '" target="_blank"><span class="badge badge-secondary p-1 mr-1">' + e + '</span></a>';

                            });

                            $('#modal-product-tags').html(tags);

                        }

                        var seller_info = "";
                        var brand_info = "";

                        if (data.brand) {
                            var brand_info = '<h5>Brand : </h5><a class="text-decoration-none" target="_blank" href="' + base_url + 'products?brand=' + data.brand_slug + '"><p class="text-danger">' + data.brand + '</p></a>';
                            $('#modal-product-brand').html(brand_info);
                        }

                        if (data.seller_name) {

                            var seller_info = '<p> <span class="text-secondary"> Sold by </span> <a class="text text-danger text-decoration-none" target="_blank" href="' + base_url + 'products?seller=' + data.seller_slug + '">' + data.seller_name + '</a> <span class="badge badge-success ">' + data.seller_rating + ' <i class="fa fa-star"></i></span> <small class="text-muted"> Out of</small> <b> ' + data.seller_no_of_ratings + ' </b></p>';

                            $('#modal-product-sellers').html(seller_info);

                        }

                        modal.stopLoading();
                    })

                    // initializeSwiper();
                }
            }),
            $(document).on("change", ".modal-product-attributes", function (e) {
                e.preventDefault();
                var t, a, r = [],
                    s = "",
                    i = !1,
                    o = [],
                    n = [],
                    c = [],
                    l = [],
                    d = [],
                    u = [];
                $(".modal-product-variants").each(function () {
                    n = {
                        price: $(this).data("price"),
                        special_price: $(this).data("special_price")
                    }, d.push($(this).data("id")), c.push(n), o = $(this).val().split(","), l.push(o), u.push($(this).data("image-index"))
                }), t = o.length, $(".modal-product-attributes").each(function () {
                    if ($(this).prop("checked") && (r.push($(this).val()), r.length == t)) {
                        n = [];
                        var e = "";
                        $.each(l, function (t, s) {
                            arrays_equal(r, s) && (i = !0,
                                n.push(c[t]),
                                e = d[t],
                                a = u[t])
                        }), i ? (quickViewgalleryTop.slideTo(a, 500, !1),
                            mobile_image_swiper.slideTo(a, 500, !1),
                            n[0].special_price < n[0].price && 0 != n[0].special_price ? (s = n[0].special_price, $("#modal-product-price").text(currency + s), $("#modal-product-special-price").text(currency + n[0].price), $("#modal-add-to-cart-button").attr("data-product-variant-id", e), $("#modal-product-special-price-div").show()) : (s = n[0].price, $("#modal-product-price").html(currency + s), $("#modal-product-special-price-div").hide(), $("#modal-add-to-cart-button").attr("data-product-variant-id", e))) : $("#modal-product-special-price-div").hide()
                    }
                })
            }),

            $("#modal-add-to-cart-button").on("click", function (e) {
                console.log($("#quick-view").data("data-product-id"));
                e.preventDefault();
                var t = $("#modal-product-quantity").val(),
                    a = $("#modal-product-title").text(),
                    r = $("#modal-product-short-description").text(),
                    s = $(".product-view-image-container img").attr("src"),
                    i = $("#modal-product-price").text().replace(/\D/g, "");
                console.log(s);
                $("#quick-view").data("data-product-id", $(this).data("productId"));
                $("#quick-view").data("data-product-slug", $(this).data("productId"));
                var o = $(this).attr("data-product-variant-id"),
                    slug = $(this).attr("data-product-slug"),
                    n = $(this).attr("data-product-type");
                var c = $(this).attr("data-min"),
                    l = $(this).attr("data-max"),
                    d = $(this).attr("data-step"),
                    u = $(this),
                    p = $(this).html();
                o ? $.ajax({
                    type: "POST",
                    url: base_url + "cart/manage",
                    data: {
                        product_variant_id: o,
                        qty: $("#modal-product-quantity").val(),
                        is_saved_for_later: !1,
                        [csrfName]: csrfHash
                    },
                    dataType: "json",
                    beforeSend: function () {
                        u.html("Please Wait").text("Please Wait").attr("disabled", !0)
                    },
                    success: function (e) {
                        if (csrfName = e.csrfName, csrfHash = e.csrfHash, u.html(p).attr("disabled", !1), 0 == e.error) {
                            Toast.fire({
                                icon: "success",
                                title: e.message
                            }), $("#cart-count").text(e.data.cart_count);
                            $.each(e.data.items, function (idx, item) {
                                item.min = c;
                                item.max = l;
                                item.step = d;
                            });
                            display_cart(e.data.items);
                            removeFromWishlistAfterMoveToBag(u);
                        } else {
                            if (0 == is_loggedin) {
                                /* CommentedOutToast */
                              Toast.fire({
                                    icon: "success",
                                    title: "Item added to cart",
                                    iconColor: getComputedStyle(document.body).getPropertyValue('--color-orange')
                                }); 
                                var m = {
                                    product_variant_id: o.trim(),
                                    title: a,
                                    name: a,
                                    slug: slug,
                                    description: r,
                                    qty: t,
                                    image: s,
                                    price: i.trim(),
                                    min: c,
                                    max: l,
                                    step: d
                                },
                                    f = localStorage.getItem("cart");
                                return console.log(f), null != (f = null !== localStorage.getItem("cart") ? JSON.parse(f) : null) ? f.push(m) : f = [m], localStorage.setItem("cart", JSON.stringify(f)), void display_cart(f)
                            }
                            Toast.fire({
                                icon: "error",
                                title: e.message
                            })
                        }
                    }
                }) : Toast.fire({
                    icon: "error",
                    title: "Please select variant"
                })
            })
        $(".auth-modal").on("click", "header a", function (e) {
            e.preventDefault(), window.signingIn = !0;
            var t = $(this).index();
            $(this).addClass("active").siblings("a").removeClass("active"), $(this).parents("div").find("section").eq(t).removeClass("hide").siblings("section").addClass("hide"), 0 === $(this).index() ? $(".auth-modal .iziModal-content .icon-close").css("background", "#ddd") : $(".auth-modal .iziModal-content .icon-close").attr("style", "")
        })

        const listnerElement = document.getElementById("modal-signup")
        if (listnerElement != null) {
            document.getElementById("modal-signup").addEventListener("show.bs.modal", () => {
                    //  closeNav(),
                    $(".send-otp-form")[0].reset(),
                    $(".sign-up-form")[0].reset(), $(".sign-up-form").hide(),

                    // Reset to step 1 properly. This used to only .show() step 1 and
                    // never re-add .d-none to #verify-otp-form, so once step 2 had been
                    // reached the modal reopened with both steps rendered on top of
                    // each other.
                    showSignupStep(1),
                    $("#verify-otp-form")[0].reset(),
                    $("#otp-error").html(""),
                    window.otpConfirmationResult = null,
                    window.otpVerified = !1,
                    resetRegisterButton(),
                    $("#registration-error").html(""),

                    $("#is-user-exist-error").html(""), $("#sign-up-error").html(""), $("#recaptcha-container").html(""), window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier("recaptcha-container"), window.recaptchaVerifier.render().then(function (e) {
                        if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.reset === 'function') {
                            try { grecaptcha.reset(e); } catch (ex) { }
                        }
                    });
                var e = $("#phone-number"),
                    t = $("#error-msg"),
                    a = $("#valid-msg");
                e.intlTelInput({
                    allowExtensions: !0,
                    formatOnDisplay: !0,
                    autoFormat: !0,
                    autoHideDialCode: !0,
                    autoPlaceholder: !0,
                    defaultCountry: "in",
                    ipinfoToken: "yolo",
                    nationalMode: !1,
                    numberType: "MOBILE",
                    preferredCountries: ["in", "ae", "qa", "om", "bh", "kw", "ma"],
                    preventInvalidNumbers: !0,
                    separateDialCode: !0,
                    initialCountry: "in",
                    /* Was initialCountry:"auto" + a JSONP geoIpLookup to ipinfo.io using the
                       placeholder token "yolo". When that call is rate-limited, blocked or slow it
                       returns an empty country, and the widget then renders NO flag at all - which
                       is the blank/grey box in the country selector. The storefront prices in INR
                       and already declared defaultCountry:"in", so start on India directly: the flag
                       always renders, and one third-party request per modal open is removed.
                       Users can still pick any other country from the dropdown. */
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.9/js/utils.js"
                });
                var r = function () {
                    e.removeClass("error"), t.addClass("hide"), a.addClass("hide")
                };
                e.blur(function () {
                    r(), $.trim(e.val()) && (e.intlTelInput("isValidNumber") ? a.removeClass("hide") : (e.addClass("error"), t.removeClass("hide")))
                }), e.on("keyup change", r)
            })
        }

        $("#quick-view").on("click", ".submit", function (e) {
            e.preventDefault();
            var t = "wobble",
                a = $(this).closest(".iziModal");
            a.hasClass(t) || (a.addClass(t), setTimeout(function () {
                a.removeClass(t)
            }, 1500))
        }),
            $("#quick-view").on("click", "header a", function (e) {
                e.preventDefault();
                var t = $(this).index();
                $(this).addClass("active").siblings("a").removeClass("active"), $(this).parents("div").find("section").eq(t).removeClass("hide").siblings("section").addClass("hide"), 0 === $(this).index() ? $("#quick-view .iziModal-content .icon-close").css("background", "#ddd") : $("#quick-view .iziModal-content .icon-close").attr("style", "")
            }),
            $("#quick-view").on("click", ".submit", function (e) {
                e.preventDefault();
                var t = "wobble",
                    a = $(this).closest(".iziModal");
                a.hasClass(t) || (a.addClass(t), setTimeout(function () {
                    a.removeClass(t)
                }, 1500))
            }),
            $("#quick-view").on("click", "header a", function (e) {
                e.preventDefault();
                var t = $(this).index();
                $(this).addClass("active").siblings("a").removeClass("active"), $(this).parents("div").find("section").eq(t).removeClass("hide").siblings("section").addClass("hide"), 0 === $(this).index() ? $("#quick-view .iziModal-content .icon-close").css("background", "#ddd") : $("#quick-view .iziModal-content .icon-close").attr("style", "")
            })
    }),
    function () {
        new LazyLoad({
            threshold: 0,
            callback_enter: function (e) { },
            callback_exit: function (e) { },
            callback_cancel: function (e) { },
            callback_loading: function (e) { },
            callback_loaded: function (e) { },
            callback_error: function (e) {
                "https://via.placeholder.com/440x560/?text=Error+Placeholder"
            },
            callback_finish: function () { }
        })
    }(),
    function () {
        var e = document.querySelector(".range-slider");
        if (e) {
            var t = e.querySelectorAll("input[type=range]"),
                a = e.querySelectorAll("input[type=number]");
            t.forEach(function (e) {
                e.oninput = function () {
                    var e = parseFloat(t[0].value),
                        r = parseFloat(t[1].value);
                    e > r && ([e, r] = [r, e]), a[0].value = e, a[1].value = r, custom_url = setUrlParameter(custom_url = setUrlParameter(location.href, "min-price", e), "max-price", r)
                }
            }), a.forEach(function (e) {
                e.oninput = function () {
                    var e = parseFloat(a[0].value),
                        r = parseFloat(a[1].value);
                    if (e > r) {
                        var s = e;
                        a[0].value = r, a[1].value = s
                    }
                    t[0].value = e, t[1].value = r
                }
            })
        }
    }(), $(document).on("change", "input.in-num", function (e) {
        e.preventDefault();
        var t = $(this);
        null != t.val() && "string" != typeof t.val() || ($.isNumeric(t.val()) ? "0" == t.val() && t.val(1) : t.val(1))
    }), $(document).on("focusout", ".in-num", function (e) {
        e.preventDefault();
        var t = $(this).val(),
            a = $(this).data("min"),
            r = ($(this).data("step"), $(this).data("max"));
        t < a ? ($(this).val(a), Toast.fire({
            icon: "error",
            title: "Minimum allowed quantity is " + a
        })) : t > r && ($(this).val(r), Toast.fire({
            icon: "error",
            title: "Maximum allowed quantity is " + r
        }))
    }), $(document).on("click", ".num-block .num-in span", function (e) {
        e.preventDefault();
        var t = $(this).parents(".num-block").find("input.in-num");

        /* if current value is none, set initial value as the minimum allowed */
        if (null == t.val()){
            t.val($(this).data("min"));
        }

        if ($(this).hasClass("minus")) {
            var a = $(this).data("step"),
                r = parseFloat(t.val()) - a,
                s = $(this).data("min");

            r == 0 ? $(this).closest(".cart-item.cart-product").find("#remove_inventory").click() 
                    : r >= s ? t.val(r) : (t.val(s), Toast.fire({
                icon: "error",
                title: "Minimum allowed quantity is " + s
            }))
        } else {
            a = $(this).data("step");
            var i = $(this).data("max");
            r = parseFloat(t.val()) + a;
            0 != i ? r <= i ? (t.val(r), r > 1 && $(this).parents(".num-block").find(".minus").removeClass("dis")) : (t.val(i), Toast.fire({
                icon: "error",
                title: "Maximum allowed quantity is " + i
            })) : t.val(r)
        }
        return t.change(), !1
    }),
    $(document).ready(function () {

        $(".kv-fa").rating({
            theme: "krajee-fa",
            filledStar: '<i class="fas fa-star"></i>',
            emptyStar: '<i class="far fa-star"></i>',
            showClear: !1,
            showCaption: !1,
            size: "md"
        });
        var e = .05,
            t = 15,
            a = 300;

        function r() {
            var r = 0;
            $(".product").each(function () {
                r += parseFloat($(this).children(".product-line-price").text())
            });
            var s = r * e,
                i = r > 0 ? t : 0,
                o = r + s + i;
            $(".totals-value").fadeOut(a, function () {
                $("#cart-subtotal").html(r.toFixed(2)), $("#cart-tax").html(s.toFixed(2)), $("#cart-shipping").html(i.toFixed(2)), $("#cart-total").html(o.toFixed(2)), 0 == o ? $(".checkout").fadeOut(a) : $(".checkout").fadeIn(a), $(".totals-value").fadeIn(a)
            })
        }

        function s(e, t, oa = 0) {
            if ("cart" == e.data("page")) var s = $(e).parent().parent().parent().siblings(".total-price");
            else s = $(e).parent().parent();
            var i = t * $(e).val();
            
            /* s.children(".product-line-price").each(function () {
                $(this).fadeOut(a, function () {
                    $(this).text(currency + i.toFixed(2)), r(), usercartTotal(), $(this).fadeIn(a)
                })
            }) */

            // Additional adaptation code for Cretzo
            if ("cart" == e.data("page")){
                var totalMRP = oa * $(e).val(); // MRP * quantity

                var parent = $(e).closest('.cart-item-detail-span');
                parent.find(".discounted-price").each(function () {
                    $(this).fadeOut(a, function () {
                        $(this).text(currency + i.toFixed(2)), r(), usercartTotal(), $(this).fadeIn(a)
                    })
                })
                parent.find(".actual-price").each(function () {
                    $(this).fadeOut(a, function () {
                        $(this).text(currency + totalMRP.toFixed(2)), usercartTotal(), $(this).fadeIn(a)
                    })
                })
            }
            else {
                // cretzo: mini-cart (header offcanvas) — the original line-price
                // updater above is commented out and the cart-page branch never runs
                // here, so the displayed line total never changed when the quantity
                // changed. Update the ".product-line-price" that sits beside this
                // quantity input so the sum reflects the new quantity.
                s.find(".product-line-price").fadeOut(a, function () {
                    $(this).text(currency + i.toFixed(2));
                    $(this).fadeIn(a);
                });
            }
        }

        function i(e) {
            var t = $(e);

            var dp = $(`#delivery-p-${e.data('product-id')}`);

            dp.slideUp(a, function () {
                dp.remove();
            });

            t.slideUp(a, function () {
                t.remove(), 
                r(), 
                usercartTotal();
                // modified for cretzo
            });
        }
        $(document).on("change", ".product-quantity input,.product-sm-quantity input,.itemQty", function (e) {
            e.preventDefault();
            var t = $(this).data("id"),
                a = $(this).data("price"),
                oa = $(this).data("original-price"),
                r = $(this).val(),
                i = $(this);
            let o;
            o = $(this).attr("step") ? $(this).attr("step") : $(this).data("step");
            var n = $(this).attr("min");

            r <= 0 ? Toast.fire({
                icon: "error",
                title: `Oops! Please set minimum ${n} quantity for product`
            }) : r % o == 0 ? 1 == is_loggedin ? $.ajax({
                url: base_url + "cart/manage",
                type: "POST",
                data: {
                    product_variant_id: t,
                    qty: r,
                    [csrfName]: csrfHash
                },
                dataType: "json",
                success: function (e) {
                    csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? s(i, a, oa) : Toast.fire({
                        icon: "error",
                        title: e.message
                    })
                }
            }) : s(i, a, oa) : Toast.fire({
                icon: "error",
                title: `Oops! you can only set quantity in step size of ${o}`
            })
        }), $(document).on("click", ".product-removal button,.product-removal i,.product-sm-removal button", function (e) {
            e.preventDefault();
            var t = $(this).data("id"),
                a = void 0 !== $(this).data("is-save-for-later") && 1 == $(this).data("is-save-for-later") ? "1" : "0",
                // r = $(this).parent().parent().parent();
                r = $(this).parent().parent(); // the product in cretzo theme ends at it's 2nd parent
            if (true /* COMMENTED OUT TO REMOVE CONFIRMATION DIALOG // confirm("Are you sure want to remove this?") */)
                if (1 == is_loggedin) $.ajax({
                    url: base_url + "cart/remove",
                    type: "POST",
                    data: {
                        product_variant_id: t,
                        is_save_for_later: a,
                        [csrfName]: csrfHash
                    },
                    dataType: "json",
                    success: function (e) {
                        if (csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error) {
                            $("#cart-count").text(e.data.cart_count), i(r);
                            /* Re-render the mini-cart through the single renderer instead of a
                               second, divergent copy of the template. The old inline markup
                               dropped min/max/step off the quantity input (so stepping broke
                               after a removal) and wrote an empty string - a blank panel with
                               no empty state - once the last line was gone. */
                            if (e.data.items) {
                                display_cart(e.data.items.map(function (product) {
                                    return $.extend({}, product, {
                                        min: product.minimum_order_quantity,
                                        max: product.total_allowed_quantity,
                                        step: product.quantity_step_size
                                    })
                                }));
                                $("#cart-count").text(e.data.cart_count);
                            }
                        } else Toast.fire({
                            icon: "error",
                            title: e.message
                        })
                    }
                });
                else {
                    i(r);
                    var s = localStorage.getItem("cart");
                    if (s = null !== localStorage.getItem("cart") ? JSON.parse(s) : null) {
                        var o = s.filter(function (e) {
                            return e.product_variant_id != t
                        });
                        localStorage.setItem("cart", JSON.stringify(o)), s && display_cart(o)
                    }
                }
        })
    }),

    jQuery(document).ready(function (e) {
        function t(e) {
            this.element = e, this.mainNavigation = this.element.find(".main-nav"), this.mainNavigationItems = this.mainNavigation.find(".has-dropdown"), this.dropdownList = this.element.find(".dropdown-list"), this.dropdownWrappers = this.dropdownList.find(".dropdown"), this.dropdownItems = this.dropdownList.find(".content"), this.dropdownBg = this.dropdownList.find(".bg-layer"), this.mq = this.checkMq(), this.bindEvents()
        }
        t.prototype.checkMq = function () {
            return window.getComputedStyle(this.element.get(0), "::before").getPropertyValue("content").replace(/'/g, "").replace(/"/g, "").split(", ")
        }, t.prototype.bindEvents = function () {
            var t = this;
            this.mainNavigationItems.mouseenter(function (a) {
                t.showDropdown(e(this))
            }).mouseleave(function () {
                setTimeout(function () {
                    0 == t.mainNavigation.find(".has-dropdown:hover").length && 0 == t.element.find(".dropdown-list:hover").length && t.hideDropdown()
                }, 50)
            }), this.dropdownList.mouseleave(function () {
                setTimeout(function () {
                    0 == t.mainNavigation.find(".has-dropdown:hover").length && 0 == t.element.find(".dropdown-list:hover").length && t.hideDropdown()
                }, 50)
            }), this.mainNavigationItems.on("touchstart", function (a) {
                var r = t.dropdownList.find("#" + e(this).data("content"));
                t.element.hasClass("is-dropdown-visible") && r.hasClass("active") || (a.preventDefault(), t.showDropdown(e(this)))
            })
        }, t.prototype.showDropdown = function (e) {
            if (this.mq = this.checkMq(), "desktop" == this.mq) {
                var t = this,
                    a = this.dropdownList.find("#" + e.data("content")),
                    r = a.innerHeight() + 18,
                    s = 180 * a.children(".content").children("ul").children("li").length;
                s > 540 && (s = 540);
                var i = parseInt(s),
                    o = e.offset().left + e.innerWidth() / 2 - i / 2,
                    n = e[0].offsetParent.offsetLeft;
                this.updateDropdown(a, parseInt(r), i, parseInt(o)), this.element.find(".active").removeClass("active"), this.element.find(".morph-dropdown-wrapper").css({
                    "-moz-transform": "translateX(-" + n + "px)",
                    "-webkit-transform": "translateX(-" + n + "px)",
                    "-ms-transform": "translateX(-" + n + "px)",
                    "-o-transform": "translateX(-" + n + "px)",
                    transform: "translateX(-" + n + "px)"
                }), a.addClass("active").removeClass("move-left move-right").prevAll().addClass("move-left").end().nextAll().addClass("move-right"), e.addClass("active"), this.element.hasClass("is-dropdown-visible") || setTimeout(function () {
                    t.element.addClass("is-dropdown-visible")
                }, 10)
            }
        }, t.prototype.updateDropdown = function (e, t, a, r) {
            this.dropdownList.css({
                "-moz-transform": "translateX(" + r + "px)",
                "-webkit-transform": "translateX(" + r + "px)",
                "-ms-transform": "translateX(" + r + "px)",
                "-o-transform": "translateX(" + r + "px)",
                transform: "translateX(" + r + "px)",
                width: a + "px",
                height: t + "px"
            }), this.dropdownBg.css({
                "-moz-transform": "scaleX(" + a + ") scaleY(" + t + ")",
                "-webkit-transform": "scaleX(" + a + ") scaleY(" + t + ")",
                "-ms-transform": "scaleX(" + a + ") scaleY(" + t + ")",
                "-o-transform": "scaleX(" + a + ") scaleY(" + t + ")",
                transform: "scaleX(" + a + ") scaleY(" + t + ")"
            })
        }, t.prototype.hideDropdown = function () {
            this.mq = this.checkMq(), "desktop" == this.mq && this.element.removeClass("is-dropdown-visible").find(".active").removeClass("active").end().find(".move-left").removeClass("move-left").end().find(".move-right").removeClass("move-right")
        }, t.prototype.resetDropdown = function () {
            this.mq = this.checkMq(), "mobile" == this.mq && this.dropdownList.removeAttr("style")
        };
        var a = [];
        if (e(".cd-morph-dropdown").length > 0) {
            e(".cd-morph-dropdown").each(function () {
                a.push(new t(e(this)))
            });
            var r = !1;

            function s() {
                a.forEach(function (e) {
                    e.resetDropdown()
                }), r = !1
            }
            s(), e(window).on("resize", function () {
                r || (r = !0, window.requestAnimationFrame ? window.requestAnimationFrame(s) : setTimeout(s, 300))
            })
        }
    }), $(".navbar-top-search-box input").on("focus", function () {
        $(".navbar-top-search-box .input-group-text").css("border-color", "#0e7dd1")
    }), $(".navbar-top-search-box input").on("blur", function () {
        $(".navbar-top-search-box .input-group-text").css("border", "1px solid #ced4da")
    });
var swiper = new Swiper(".swiper1", {
    loop: !0,
    preloadImages: !1,
    lazy: !0,
    autoplay: {
        delay: 6e3,
        disableOnInteraction: !1
    },
    pagination: {
        el: ".swiper1-pagination",
        clickable: !0
    },
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev"
    }
}),
    swiperheader = new Swiper(".imageSliderHeader", {
        autoplay: {
            delay: 6e3
        },
        autoplay: {
            delay: 6e3,
            disableOnInteraction: !1
        },
        pagination: {
            el: ".imageSliderHeader-pagination",
            clickable: !0
        },
        loop: !0,
        grabCursor: !0
    }),
    swiperF = new Swiper(".preview-image-swiper", {
        pagination: {
            el: ".preview-image-swiper-pagination"
        }
    }),
    swiperV = new Swiper(".banner-swiper", {
        preloadImages: !1,
        lazy: !0,
        autoplay: !0,
        pagination: {
            el: ".banner-swiper-pagination"
        },
        loop: !0,
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev"
        }
    });

//Gallery Swiper

galleryThumbs = new Swiper('.gallery-thumbs-1', {

    spaceBetween: 10,

    slidesPerView: 4,

    freeMode: true,

    watchSlidesVisibility: true,

    watchSlidesProgress: true,

});

galleryTop = new Swiper('.gallery-top-1', {

    spaceBetween: 10,

    navigation: {

        nextEl: '.swiper-button-next',

        prevEl: '.swiper-button-prev',

    },

    thumbs: {

        swiper: galleryThumbs

    }

});

document.querySelectorAll(".product-image-swiper").forEach(function (e) {
    new Swiper(e, {
        grabCursor: !0,
        preloadImages: !1,
        lazyLoading: !0,
        updateOnImagesReady: !1,
        lazyLoadingInPrevNextAmount: 1,
        navigation: {
            nextEl: e.nextElementSibling,
            prevEl: e.nextElementSibling.nextElementSibling
        },
        breakpoints: {
            350: {
                slidesPerView: 1,
                spaceBetweenSlides: 10
            },
            400: {
                slidesPerView: 1,
                spaceBetweenSlides: 10
            },
            499: {
                slidesPerView: 1,
                spaceBetweenSlides: 10
            },
            550: {
                slidesPerView: 1,
                spaceBetweenSlides: 10
            },
            600: {
                slidesPerView: 2,
                spaceBetweenSlides: 10
            },
            700: {
                slidesPerView: 3,
                spaceBetweenSlides: 10
            },
            800: {
                slidesPerView: 4,
                spaceBetweenSlides: 10
            },
            999: {
                slidesPerView: 5,
                spaceBetweenSlides: 10
            },
            1900: {
                slidesPerView: 6,
                spaceBetweenSlides: 10
            },
            1900: {
                slidesPerView: 6,
                spaceBetweenSlides: 10
            }
        }
    })
});
var timer, swiperH = new Swiper(".swiper2", {
    slidesPerView: "auto",
    grabCursor: !0,
    spaceBetween: 20,
    pagination: {
        el: ".swiper2-pagination",
        clickable: !0
    }
});

$(document).ready(function () {
    jQuery(document).ready(function () {
        jQuery("#jquery-accordion-menu").jqueryAccordionMenu(), jQuery(".colors a").click(function () {
            "default" != $(this).attr("class") ? ($("#jquery-accordion-menu").removeClass(), $("#jquery-accordion-menu").addClass("jquery-accordion-menu").addClass($(this).attr("class"))) : ($("#jquery-accordion-menu").removeClass(), $("#jquery-accordion-menu").addClass("jquery-accordion-menu"))
        })
    })
}),
    function (e, t, a, r) {
        var s = "jqueryAccordionMenu",
            i = {
                speed: 300,
                showDelay: 0,
                hideDelay: 0,
                singleOpen: !0,
                clickEffect: !0
            };

        function o(t, a) {
            this.element = t, this.settings = e.extend({}, i, a), this._defaults = i, this._name = s, this.init()
        }
        e.extend(o.prototype, {
            init: function () {
                this.openSubmenu(), this.submenuIndicators(), i.clickEffect && this.addClickEffect()
            },
            openSubmenu: function () {
                e(this.element).children("ul").find("li").bind("click touchstart", function (a) {
                    if (a.stopPropagation(), a.preventDefault(), e(this).children(".submenu").length > 0) {
                        if ("none" == e(this).children(".submenu").css("display")) return e(this).children(".submenu").show(i.speed), e(this).children(".submenu").siblings("a").addClass("submenu-indicator-minus"), i.singleOpen && (e(this).siblings().children(".submenu").hide(i.speed), e(this).siblings().children(".submenu").siblings("a").removeClass("submenu-indicator-minus")), !1;
                        e(this).children(".submenu").delay(i.hideDelay).hide(i.speed), e(this).children(".submenu").siblings("a").hasClass("submenu-indicator-minus") && e(this).children(".submenu").siblings("a").removeClass("submenu-indicator-minus")
                    }
                    t.location.href = e(this).children("a").attr("href")
                })
            },
            submenuIndicators: function () {
                e(this.element).find(".submenu").length > 0 && e(this.element).find(".submenu").siblings("a").append("<span class='submenu-indicator'>+</span>")
            },
            addClickEffect: function () {
                var t, a, r, s;
                e(this.element).find("a > .submenu-indicator").on("click touchstart", function (i) {
                    e(".ink").remove(), 0 === e(this).children(".ink").length && e(this).prepend("<span class='ink'></span>"), (t = e(this).find(".ink")).removeClass("animate-ink"), t.height() || t.width() || (a = Math.max(e(this).outerWidth(), e(this).outerHeight()), t.css({
                        height: a,
                        width: a
                    })), r = i.pageX - e(this).offset().left - t.width() / 2, s = i.pageY - e(this).offset().top - t.height() / 2, t.css({
                        top: s + "px",
                        left: r + "px"
                    }).addClass("animate-ink")
                })
            }
        }), e.fn[s] = function (t) {
            return this.each(function () {
                e.data(this, "plugin_" + s) || e.data(this, "plugin_" + s, new o(this, t))
            }), this
        }
    }(jQuery, window, document), document.addEventListener("DOMContentLoaded", function (e) {
        function t() {
            this.classList.add("clicked")
        }
        document.querySelectorAll(".cart-button").forEach(e => {
            e.addEventListener("click", t)
        })
    });
var compareDate = new Date;

function timeBetweenDates(e) {
    var t = e,
        a = new Date,
        r = t.getTime() - a.getTime();
    if (r <= 0) clearInterval(timer);
    else {
        var s = Math.floor(r / 1e3),
            i = Math.floor(s / 60),
            o = Math.floor(i / 60),
            n = Math.floor(o / 24);
        o %= 24, i %= 60, s %= 60, $("#days").text(n), $("#hours").text(o), $("#minutes").text(i), $("#seconds").text(s)
    }
}
compareDate.setDate(compareDate.getDate() + 7), timer = setInterval(function () {
    timeBetweenDates(compareDate)
}, 1e3), $(window).scroll(function () {
    $(this).scrollTop() > 50 ? $(".back-to-top:hidden").stop(!0, !0).fadeIn() : $(".back-to-top").stop(!0, !0).fadeOut()
}), $(function () {
    $(".scroll").click(function () {
        return $("html,body").animate({
            scrollTop: $(".sidenav").offset().top
        }, "1000"), !1
    })
}), $("#newsletter-modal").on("show.bs.modal", function (e) {
    $(e.relatedTarget).data("whatever")
});
swiper = new Swiper(".swiper-container-client", {
    loop: !0,
    loopedSlides: 10,
    autoheight: !0,
    slidesPerView: 2,
    spaceBetween: 30,
    autoplay: {
        delay: 6e3,
        disableOnInteraction: !1
    },
    breakpoints: {
        600: {
            slidesPerView: 6,
            spaceBetween: 20
        }
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: !0
    }
});

function buildUrlParameterValue(e, t, a, r = "") {
    if ("" != r) var s = getUrlParameter(e, r);
    else s = getUrlParameter(e);
    return "add" == a ? (null == s ? s = t : s += "|" + t, s) : "remove" == a ? null != s ? ((s = s.split("|")).splice($.inArray(t, s), 1), s.join("|")) : "" : void 0
}

function getUrlParameter(e, t = "") {
    if (e = e.replace(/\s+/g, "-"), "" != t) {
        if (!(t.indexOf("?") > -1)) return;
        var a = t.substring(t.indexOf("?") + 1)
    } else a = window.location.search.substring(1);
    var r, s, i = a.split("&");
    for (s = 0; s < i.length; s++)
        if ((r = i[s].split("="))[0] === e) return void 0 === r[1] || decodeURIComponent(r[1])
}

function checkUrlHasParam(e = "") {
    return "" == e && (e = window.location.href), e.indexOf("?") > -1 || void 0
}

function setUrlParameter(e, t, a) {
    if (t = t.replace(/\s+/g, "-"), null == a || "" == a) return e.replace(new RegExp("[?&]" + t + "=[^&#]*(#.*)?$"), "$1").replace(new RegExp("([?&])" + t + "=[^&]*&"), "$1");
    var r = new RegExp("\\b(" + t + "=).*?(&|#|$)");
    return e.search(r) >= 0 ? e.replace(r, "$1" + a + "$2") : (e = e.replace(/[?#]$/, "")) + (e.indexOf("?") > 0 ? "&" : "?") + t + "=" + a
}

$("#back_to_top").on("click", function () {
    $("html, body").animate({
        scrollTop: 0
    }, "slow")
}),
    $("#per_page_products a").on("click", function (e) {
        e.preventDefault();
        var t = $(this).data("value");
        $(this).parent().siblings("a.dropdown-toggle").text($(this).text()), location.href = setUrlParameter(location.href, "per-page", t)
    }),
    $("#per_page_sellers a").on("click", function (e) {
        e.preventDefault();
        var t = $(this).data("value");
        $(this).parent().siblings("a.dropdown-toggle").text($(this).text()), location.href = setUrlParameter(location.href, "per-page", t)
    }),
    $("#product_sort_by").on("change", function (e) {
        e.preventDefault();
        var t = $(this).val();
        location.href = setUrlParameter(location.href, "sort", t)
    }),

    $("#seller_search").on("focusout", function (e) {
        e.preventDefault();
        var t = $(this).val();
        location.href = setUrlParameter(location.href, "seller_search", t)
    }),


    $(".sub-category").on("click", function (e) {
        e.preventDefault();
        var t = $(this).data("value");
        custom_url = setUrlParameter(custom_url, "category", t),
            location.href = custom_url
    }),

    $(document).on("change", ".brand", function (e) {
        e.preventDefault();
        var t = $(this).data("value");
        custom_url = setUrlParameter(custom_url, "brand", t);

        const brand_name = getUrlParameter('brand');
        var brands = $('[data-value="' + brand_name + '"]');
        $('[data-value="' + brand_name + '"]').attr('checked', true);
        var gp = $(brands).siblings();
        $(gp).removeClass('selected-brand');
        // location.href = custom_url
    }),

    $(document).on("change", ".category", function (e) {
        e.preventDefault();
        var t = $(this).data("value");
        custom_url = setUrlParameter(custom_url, "category", t);

        const category_id = getUrlParameter('category');
        var categories = $('[data-value="' + category_id + '"]');
        $('[data-value="' + category_id + '"]').attr('checked', true);
        $(categories).removeClass('selected-category');

        // location.href = custom_url
    }),

    $(document).on("change", ".product_attributes", function (e) {
        e.preventDefault();
        var t = $(this).data("attribute"),
            a = getUrlParameter(t = "filter-" + t),
            r = $(this).val();
        if (null == a && (a = ""), this.checked) var s = buildUrlParameterValue(t, r, "add", custom_url);
        else s = buildUrlParameterValue(t, r, "remove", custom_url);
        custom_url = setUrlParameter(custom_url, t, s)
    }),

    $(".product_filter_btn").on("click", function (e) {
        e.preventDefault(), location.href = custom_url
    });
var filters, type_url = "";

function arrays_equal(e, t) {
    if (!Array.isArray(e) || !Array.isArray(t) || e.length !== t.length) return !1;
    const a = e.concat().sort(),
        r = t.concat().sort();
    for (let e = 0; e < a.length; e++)
        if (a[e] !== r[e]) return !1;
    return !0
}

$("#reload").on("click", function (e) {
    window.location = window.location.href.split("?")[0];
});

function display_cart(e) {
    if(e == null)
        return;

    var t = e.length ? e.length : "0";
    $("#cart-count").text(t);
    /* An emptied cart used to leave the offcanvas blank - no items, no empty state. */
    if (!e.length) {
        $("#cart-item-sidebar").html('<h1 class="h4 text-center mini-cart-empty">Your cart is empty</h1><img src="' + base_url + 'assets/front_end/cretzo/img/new/empty-cart(4).png" alt="Empty Cart" class="mt-16" />');
        return;
    }
    var a = "";
    null !== e && e.length > 0 && e.forEach(e => {

        //a += '<div class="shopping-cart"><div class="shopping-cart-item d-flex justify-content-between mb-4"><div class="d-flex flex-row gap-3"  title = " ' + e.title + '"><figure class="rounded cart-img"><a href="' + base_url + 'products/details/' + e.slug + '"><img src="' + e.image + '" alt="Not Found" style="object-fit: contain;"></a></figure><div class="w-100"><a href="' + base_url + 'products/details/' + e.slug + '"><h3 class="post-title fs-16 lh-xs mb-1" title = " ' + e.title + '">' + e.title + '</h3></a><p class="price"><ins><span class="amount">' + currency + e.price + '</span></ins></p><div class="product-pricing d-flex py-2 px-1 w-100"><div class="align-items-center d-flex p-2 w-15"><input type="number" name="header_qty" class="form-control d-flex align-items-center" value="' + e.qty + '" data-id="' + e.product_variant_id + '" data-price="' + e.price + '" min="' + e.min + '" max="' + e.max + '" step="' + e.step + '" ></div><div class="product-line-price align-self-center px-1">' + currency + (e.qty * e.price) + '</div></div></div></div><div class="product-sm-removal"><button class="remove-product btn btn-sm btn-danger rounded-1 p-1 py-0" data-id="' + e.product_variant_id + '"><i class="uil uil-trash-alt"></i></button>   </div></div></div>'

        var variant_tag = "";
        if(e.product_variants != null && e.product_variants != "" && void 0 !== e.product_variants[0].variant_values && null != e.product_variants[0].variant_values){
            var v = (e.product_variants[0].variant_values).replace(", ", " | ");
            variant_tag = "<span>" + v + "</span>"
        }

        var img = base_url + (e.image || "").replace(base_url, "");
        var slug = base_url + 'products/details/' + (e.product_slug || e.slug || "").replace(base_url + 'products/details/', "");
        var s = e.special_price < e.price && 0 != e.special_price ? e.special_price : e.price;

        a += '<div class="shopping-cart"><div class="shopping-cart-item d-flex justify-content-between mb-4"><div class="d-flex flex-row gap-3" title=" ' + e.name + '"><figure class="rounded cart-img"><a href="' + slug + '"><img src="' + img + '" alt="' + e.name + '" title="' + e.name + '" style="object-fit: contain;"></a></figure><div class="w-100 cart-title"><a href="' + slug + '"><h3 class="post-title fs-16 lh-xs mb-1 no-wrap" title=" ' + e.name + '">' + e.name + '</h3></a>' + variant_tag + '<p class="price"><ins><span class="amount">' + currency + s + '</span></ins></p><div class="product-pricing d-flex w-100"><div class="product-quantity product-sm-quantity"><input type="number" name="header_qty" class="form-control d-flex align-content-center w-14" value="' + e.qty + '" data-id="' + e.product_variant_id + '" data-price="' + e.price + '" min="' + e.min + '" max="' + e.max + '" step="' + e.step + '"></div><div class="product-line-price align-self-center px-1 no-wrap" style="color: #F2822E;">' + currency + (e.qty * s) + '</div></div></div>            </div><div class="product-sm-removal"><button class="remove-product btn btn-sm btn-danger rounded-1 p-1 py-0" data-id="' + e.product_variant_id + '"><i class="uil uil-trash-alt"></i></button></div></div></div>'        

    }),
        //  console.log(a), 
        $("#cart-item-sidebar").html(a)
}

// function display_cart(e) {
//     var t = e.length ? e.length : "";
//     $("#cart-count").text(t);
//     var a = "";
//     null !== e && e.length > 0 && e.forEach(e => {
//         console.log(e);
//         a += '<div class="shopping-cart"><div class="shopping-cart-item d-flex justify-content-between mb-4"><div class="d-flex flex-row gap-3"  title = " ' + e.title + '"><figure class="rounded cart-img"><a href="' + base_url + 'products/details/' + e.product_slug + '"><img src="' + base_url + e.image + '" alt="Not Found" style="object-fit: contain;"></a></figure><div class="w-100"><a href="' + base_url + 'products/details/' + e.product_slug + '"><h3 class="post-title fs-16 lh-xs mb-1" title = " ' + e.name + '">' + e.name + '</h3></a><p class="price"><ins><span class="amount">' + currency + e.price + '</span></ins></p><div class="product-pricing d-flex py-2 px-1 w-100"><div class="align-items-center d-flex p-2 w-15"><input type="number" name="header_qty" class="form-control d-flex align-items-center" value="' + e.minimum_order_quantity + '" data-id="' + e.product_variant_id + '" data-price="' + e.price + '" min="' + e.minimum_order_quantity + '" max="' + e.total_allowed_quantity + '" step="' + e.quantity_step_size + '" ></div><div class="product-line-price align-self-center px-1">' + currency + (e.qty * e.price) + '</div></div></div></div><div class="product-sm-removal"><button class="remove-product btn btn-sm btn-danger rounded-1 p-1 py-0" data-id="' + e.product_variant_id + '"><i class="uil uil-trash-alt"></i></button>   </div></div></div>'
//     }),
//         // console.log(a),
//         $("#cart-item-sidebar").html(a)
// }

function cart_sync() {
    var e = localStorage.getItem("cart");
    if (null != e && e) $.ajax({
        type: "POST",
        url: base_url + "cart/cart_sync",
        /* dataType is deliberately left off: a stray warning or a second json object in the
           response used to make the strict json parse fail, which skipped this callback and
           left the guest cart in localStorage forever. */
        data: {
            [csrfName]: csrfHash,
            data: e,
            is_saved_for_later: !1
        },
        success: function (e) {
            var r = e;
            if ("string" == typeof r) try {
                r = JSON.parse(r)
            } catch (t) {
                /* Unparseable body - the merge may still have happened server-side. Drop the
                   guest cart anyway so it cannot outlive the login and haunt the mini-cart. */
                localStorage.removeItem("cart");
                return
            }
            csrfName = r.csrfName || csrfName, csrfHash = r.csrfHash || csrfHash;
            localStorage.removeItem("cart");
            if (r.message) Toast.fire({
                icon: 0 == r.error ? "success" : "error",
                title: r.message
            });
            return !0
        }
    });
    else;
}

function transaction_query_params(e) {
    return {
        transaction_type: "transaction",
        user_id: $("#transaction_user_id").val(),
        limit: e.limit,
        sort: e.sort,
        order: e.order,
        offset: e.offset,
        search: e.search
    }
}

function customer_wallet_query_paramss(e) {
    return {
        type: "wallet",
        limit: e.limit,
        sort: e.sort,
        order: e.order,
        offset: e.offset,
        search: e.search
    }
} (type_url = setUrlParameter(custom_url, "type", null), $("#product_grid_view_btn").attr("href", type_url), type_url = setUrlParameter(custom_url, "type", "list"), $("#product_list_view_btn").attr("href", type_url), "list" == getUrlParameter("type") ? $("#product_list_view_btn").addClass("active") : $("#product_grid_view_btn").addClass("active"), $("#category_parent").each(function () {
    $(this).select2({
        theme: "bootstrap4",
        width: $(this).data("width") ? $(this).data("width") : $(this).hasClass("w-100") ? "100%" : "style",
        placeholder: $(this).data("placeholder"),
        allowClear: Boolean($(this).data("allow-clear")),
        dropdownCssClass: "test",
        templateResult: function (e) {
            if (!e.element) return e.text;
            var t = $(e.element),
                a = $("<span></span>");
            return a.addClass(t[0].className), a.text(e.text), a
        }
    })
}), $("#category_parent").on("change", function (e) {
    e.preventDefault();
    var t = $(this).val();
    location.href = setUrlParameter(location.href, "category_id", t)
}), $("#blog_search").on("keyup", function (e) {
    e.preventDefault();
    var t = $(this).val();
    location.href = setUrlParameter(location.href, "blog_search", t)
}), $(".auth_model").on("click", function (e) {
    e.preventDefault();
    var t = $(this).data("value");
    $("#forgot_password_div").addClass("d-none"), "login" == t ? ($("#login_div").removeClass("d-none"), $("#login").addClass("active"), $("#register_div").addClass("hide"), $("#register").removeClass("active")) : "register" == t && ($("#login_div").addClass("d-none"), $("#login").removeClass("active"), $("#register_div").removeClass("hide"), $("#register").addClass("active"))
}),

    // Product Details Page.

    $('.attributes').on('change', function (e) {
        e.preventDefault();

        var selected_attributes = [];

        var attributes_length = "";

        var price = "";

        var is_variant_available = false;

        var variant = [];

        var prices = [];

        var variant_prices = [];

        var variant_stocks = [];

        var variant_availabilities = [];

        var variants = [];

        var variant_ids = [];

        var image_indexes = [];

        var selected_image_index;

        $('.variants').each(function () {

            prices = {

                price: $(this).data('price'),

                special_price: $(this).data('special_price')

            };

            variant_stocks.push($(this).data('stock'));

            variant_availabilities.push($(this).data('availability'));

            variant_ids.push($(this).data('id'));

            variant_prices.push(prices);

            variant = $(this).val().split(',');

            variants.push(variant);

            image_indexes.push($(this).data('image-index'));

        });

        attributes_length = variant.length;

        $('.attributes').each(function (i, e) {
            if ($(this).prop('checked')) {
                selected_attributes.push($(this).val());
                if (selected_attributes.length == attributes_length) {
                    /* compare the arrays */
                    prices = [];
                    var selected_variant_id = '';
                    var stock = '';
                    var availability = 1;
                    $.each(variants, function (i, e) {
                        if (arrays_equal(selected_attributes, e)) {
                            is_variant_available = true;
                            prices.push(variant_prices[i]);
                            selected_variant_id = variant_ids[i];
                            selected_image_index = image_indexes[i];
                            stock = variant_stocks[i];
                            availability = variant_availabilities[i];
                        }
                    });
                    
                    if (is_variant_available) {
                        $('#add_cart').attr('data-product-variant-id', selected_variant_id);

                        // Added call to a function in cretzo's product-page.js whenever a complete variant id set is selected
                        product_variant_selected(selected_variant_id);

                        try{
                            galleryTop.slideTo(selected_image_index, 500, false);
                            swiperF.slideTo(selected_image_index, 500, false);
                        }
                        catch(e){
                            console.log(e);
                        }
                        
                        if (prices[0].special_price < prices[0].price && prices[0].special_price != 0) {
                            let normalPrice = prices[0].price;
                            price = prices[0].special_price;
                            $('#price').html(currency + price);
                            $('#striped-price').html(currency + normalPrice);
                            $('#striped-price-div').show();
                            $('#add_cart').removeAttr('disabled');

                            $('#discounted-price').html(currency + price);
                            $('#normal-price').html(currency + normalPrice);

                            if (price < normalPrice) {
                                let discountPercentage = Math.round(((normalPrice - price) / normalPrice) * 100);
                                $('#discount-percentage').html(discountPercentage + '% OFF');
                            }
                            
                            $('#add_cart').attr('data-product-price', price);
                        } else {
                            price = prices[0].price;
                            $('#price').html(currency + price);
                            $('#striped-price-div').hide();
                            $('#add_cart').removeAttr('disabled');

                            $('#normal-price').html(currency + price);

                            $('#add_cart').attr('data-product-price', price);
                        }

                        // Reflect out-of-stock state for the selected variant. availability == 0
                        // means the variant is unavailable / out of stock (matches server-side
                        // validate_stock), so block adding it to the cart.
                        if (availability === 0 || availability === '0') {
                            $('#stock-quantity').html('<span class="text-danger fw-b">Out of Stock</span>');
                            $('#add_cart').attr('disabled', 'true');
                        } else {
                            $('#stock-quantity').html((stock !== '' && stock != null) ? stock + ' in stock' : 'In Stock');
                            $('#add_cart').removeAttr('disabled');
                        }

                    } else {
                        price = '<small class="text-danger h5">No Variant available!</small>';
                        $('#price').html(price);
                        $('#striped-price-div').hide();
                        $('#striped-price').html('');
                        $('#add_cart').attr('disabled', 'true');
                    }
                }
            }
        });
        variants = "";
    }), $(document).on("click", ".add_to_cart", function (e) {
        e.preventDefault();

        var t = $('[name="qty"]').val();
        $("#quick-view").data("data-product-id", $(this).data("productId"));
        var a = $(this).attr("data-product-variant-id"),
            r = ($(this).attr("data-product-type"), $(this).attr("data-user-id"), $(this).attr("data-product-title")),
            s = $(this).attr("data-product-image"),
            slug = $(this).attr("data-product-slug"),
            i = $(this).attr("data-product-price"),
            o = $(this).attr("data-product-description"),
            n = $(this).attr("data-min"),
            c = $(this).attr("data-max"),
            l = $(this).attr("data-step"),
            d = $(this),
            u = $(this).html(),
            p = $(this).attr("data-izimodal-open");
            
            

        // console.log(base_url + 'products/details/' + slug);
        a ? "" != p && null != p || $.ajax({
            type: "POST",
            url: base_url + "cart/manage",
            data: {
                product_variant_id: a,
                qty: t,
                is_saved_for_later: !1,
                [csrfName]: csrfHash
            },
            dataType: "json",
            beforeSend: function () {
            },
            success: function (e) {
                if (csrfName = e.csrfName, csrfHash = e.csrfHash, d.html(u).attr("disabled", !1), 0 == e.error) {
                    Toast.fire({
                        icon: "success",
                        title: e.message
                    }), $("#cart-count").text(e.data.cart_count);
                    // display_cart(e.data.items);

                    $.each(e.data.items, function (e, a) {
                        a.min = n;
                        a.max = c;
                        a.step = l;
                    });

                    display_cart(e.data.items);
                    removeFromWishlistAfterMoveToBag(d);

                    var t = "";
                    /* $.each(e.data.items, function (e, a) {
                        console.log(a);
                        var r = void 0 !== a.product_variants.variant_values && null != a.product_variants.variant_values ? a.product_variants.variant_values : "",
                            s = a.special_price < a.price && 0 != a.special_price ? a.special_price : a.price;
                        t += '<div class="shopping-cart"><div class="shopping-cart-item d-flex justify-content-between mb-4" title = " ' + a.name + '"><div class="d-flex flex-row gap-3"><figure class="rounded cart-img"><a href="' + base_url + 'products/details/' + a.slug + '"><img src="' + base_url + a.image + '" alt="Not Found" style="object-fit: contain;"></a></figure><div class="w-100"><a href="' + base_url + 'products/details/' + a.slug + '"><h3 class="post-title fs-16 lh-xs mb-1"  title = " ' + a.name + '">' + a.name + "</h3></a><span>" + r + '</span><p class="price"><ins><span class="amount">' + currency + s + '</span></ins></p><div class="product-pricing d-flex py-2 px-1 w-100"><div class="align-items-center d-flex p-2 w-15"><input type="number" name="header_qty" class="form-control d-flex align-items-center" value="' + n + '" data-id="' + a.product_variant_id + '" data-price="' + a.price + '" min="' + n + '" max="' + c + '" step="' + l + '" ></div><div class="product-line-price align-self-center px-1">' + currency + (a.qty * s) + '</div></div></div></div><div class="product-sm-removal"><button class="remove-product btn btn-sm btn-danger rounded-1 p-1 py-0" data-id="' + a.product_variant_id + '"><i class="uil uil-trash-alt"></i></button></div></div></div>'
                    }),
                        $("#cart-item-sidebar").html(t) */
                } else {
                    if (0 == is_loggedin) {
                        /* CommentedOutToast */
                       Toast.fire({
                            icon: "success",
                            title: "Item added to cart",
                            iconColor: getComputedStyle(document.body).getPropertyValue('--color-orange')
                        }); 
                        var p = {
                            product_variant_id: a.trim(),
                            title: r,
                            name: r,
                            slug: slug,
                            description: o,
                            qty: n,
                            image: s,
                            price: i.trim(),
                            min: n,
                            max: c,
                            step: l
                        },
                            m = localStorage.getItem("cart");
                        return m = null !== localStorage.getItem("cart") ? JSON.parse(m) : null, console.log(m), null != m ? m.push(p) : m = [p], localStorage.setItem("cart", JSON.stringify(m)), void display_cart(m)
                    }
                    Toast.fire({
                        icon: "error",
                        title: e.message
                    })
                }
            }
        }) : Toast.fire({
            icon: "error",
            title: "Please select variant"
        })
        
       
    }), $(document).ready(function () {
        /* The localStorage cart belongs to GUESTS only. This used to run for everyone and
           paint the stored list over the server-rendered mini-cart, so a guest cart left
           behind by a failed cart_sync kept showing items that are not in the DB - the
           remove button then answered "Cart Is Already Empty !" and the row never went
           away. Once signed in, the DB cart is the only source of truth; drop the leftover. */
        if (1 == is_loggedin) {
            localStorage.removeItem("cart");
            return;
        }
        var e = localStorage.getItem("cart");
        (e = null !== localStorage.getItem("cart") ? JSON.parse(e) : null) && display_cart(e)
    }), $(document).ready(function () {
        $(document).on("click", "#clear_cart", function () {
            confirm("Are you sure want to Clear Cart?") && $.ajax({
                type: "POST",
                data: {
                    [csrfName]: csrfHash
                },
                url: base_url + "cart/clear",
                success: function (e) {
                    csrfName = e.csrfName, csrfHash = e.csrfHash, location.reload()
                }
            })
        }), $(document).on("click", "#checkout", function (e) {
            confirm("Are You Sure want to Checkout?") || e.preventDefault()
        })
    }), $(".quick-view-btn").on("click", function () {
        $("#quick-view").data("data-product-id", $(this).data("productId"))
    }),
    $('.save-for-later').on('click', function (e) {
        e.preventDefault();
        var formdata = new FormData();
        var product_variant_id = $(this).data('id');
        var qty = $(this).parent().siblings('.item-quantity').find('.itemQty').val();
        var product = $(this);
        formdata.append(csrfName, csrfHash);
        formdata.append('product_variant_id', product_variant_id);
        formdata.append('is_saved_for_later', 1);
        formdata.append('qty', qty);
        $.ajax({
            type: 'POST',
            url: base_url + 'cart/manage',
            data: formdata,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                console.log(result);
                csrfName = result.csrfName;
                csrfHash = result.csrfHash;
                if (result.error == false) {
                    window.location.reload();
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: result.message
                    });
                }
            }
        });
    }),

    $(".move-to-cart").on("click", function (e) {
        e.preventDefault();
        var t = new FormData,
            a = $(this).data("id"),
            r = $(this).parent().parent().siblings(".itemQty").text();
        $(this);
        t.append(csrfName, csrfHash), t.append("product_variant_id", a), t.append("is_saved_for_later", 0), t.append("qty", r), $.ajax({
            type: "POST",
            url: base_url + "cart/manage",
            data: t,
            cache: !1,
            contentType: !1,
            processData: !1,
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? window.location.reload() : Toast.fire({
                    icon: "error",
                    title: e.message
                })
            }
        })
    }), $(".update-order-item").on("click", function (e) {
        e.preventDefault();
        var t = new FormData,
            a = $(this).data("item-id"),
            r = $(this).data("status"),
            s = $(this),
            i = s.text();
        t.append(csrfName, csrfHash), t.append("order_item_id", a), t.append("status", r), $.ajax({
            type: "POST",
            url: base_url + "my-account/update-order-item-status",
            data: t,
            cache: !1,
            contentType: !1,
            processData: !1,
            dataType: "json",
            beforeSend: function () {
                s.html("Please Wait").attr("disabled", !0)
            },
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? (Toast.fire({
                    icon: "success",
                    title: e.message
                }), setTimeout(function () {
                    window.location.reload()
                }, 3e3)) : Toast.fire({
                    icon: "error",
                    title: e.message
                }), s.html(i).attr("disabled", !1)
            }
        })
    }), $(".update-order").on("click", function (e) {
        e.preventDefault();
        var t = new FormData,
            a = $(this).data("order-id"),
            r = $(this).data("status"),
            s = "";
        if (s = "cancelled" == r ? "Cancel" : "Return", confirm("Are you sure you want to " + s + " this order ?")) {
            var i = $(this),
                o = i.text();
            t.append(csrfName, csrfHash), t.append("order_id", a), t.append("status", r), $.ajax({
                type: "POST",
                url: base_url + "my-account/update-order",
                data: t,
                cache: !1,
                contentType: !1,
                processData: !1,
                dataType: "json",
                beforeSend: function () {
                    i.html("Please Wait").attr("disabled", !0)
                },
                success: function (e) {
                    csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? (Toast.fire({
                        icon: "success",
                        title: e.message
                    }), setTimeout(function () {
                        window.location.reload()
                    }, 3e3)) : Toast.fire({
                        icon: "error",
                        title: e.message
                    }), i.html(o).attr("disabled", !1)
                }
            })
        }
    }), $("#add-address-form").on("submit", function (e) {
        return; // overridden in Cretzo's address.js file
        e.preventDefault();
        var t = new FormData(this);
        t.append(csrfName, csrfHash), $.ajax({
            type: "POST",
            data: t,
            url: $(this).attr("action"),
            dataType: "json",
            cache: !1,
            contentType: !1,
            processData: !1,
            beforeSend: function () {
                $("#save-address-submit-btn").val("Please Wait...").attr("disabled", !0)
            },
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? ($("#save-address-result").html("<div class='alert alert-success'>" + e.message + "</div>").delay(1500).fadeOut(), $("#add-address-form")[0].reset(), $("#address_list_table").bootstrapTable("refresh")) : $("#save-address-result").html("<div class='alert alert-danger'>" + e.message + "</div>").delay(1500).fadeOut(), $("#save-address-submit-btn").val("Save").attr("disabled", !1)
            }
        })
    }),
    $('#city').on('change', function (e) {
        e.preventDefault();
        var value = $(this).val()
        if (value == 0 || value == -1) {
            $('.city_name').removeClass('d-none')
            $('.area_name').removeClass('d-none')
            $('.area').addClass('d-none')
        } else {
            $('.city').removeClass('d-none')
            $('.area').removeClass('d-none')
            $('.city_name').addClass('d-none')
            $('.area_name').addClass('d-none')
        }

    }), $("#edit-address-form").on("submit", function (e) {
        return; // overridden in cretzo's address.js file
        e.preventDefault();
        var t = new FormData(this);
        t.append(csrfName, csrfHash), $.ajax({
            type: "POST",
            data: t,
            url: $(this).attr("action"),
            dataType: "json",
            cache: !1,
            contentType: !1,
            processData: !1,
            beforeSend: function () {
                $("#edit-address-submit-btn").val("Please Wait...").attr("disabled", !0)
            },
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? ($("#edit-address-result").html("<div class='alert alert-success'>" + e.message + "</div>").delay(1500).fadeOut(), $("#edit-address-form")[0].reset(), $("#address_list_table").bootstrapTable("refresh"), setTimeout(function () {
                    $("#address-modal").modal("hide");

                    // since the above line for hiding modal isn't working, we are adding this for now (cretzo):
                    $("#address-modal button.close").click();

                }, 2e3)) : $("#edit-address-result").html("<div class='alert alert-danger'>" + e.message + "</div>").delay(1500).fadeOut(), $("#edit-address-submit-btn").val("Save").attr("disabled", !1)
            }
        })
    }), $(document).on("click", ".delete-address", function (e) {
        e.preventDefault(), confirm("Are you sure ? You want to delete this address?") && $.ajax({
            type: "POST",
            data: {
                id: $(this).data("id"),
                [csrfName]: csrfHash
            },
            url: base_url + "my-account/delete-address",
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? $("#address_list_table").bootstrapTable("refresh") : Toast.fire({
                    icon: "error",
                    title: e.message
                })
            }
        })
    }), $(document).on("click", ".default-address", function (e) {
        e.preventDefault(), confirm("Are you sure ? You want to set this address as default?") && $.ajax({
            type: "POST",
            data: {
                id: $(this).data("id"),
                [csrfName]: csrfHash
            },
            url: base_url + "my-account/set-default-address",
            dataType: "json",
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? ($("#address_list_table").bootstrapTable("refresh"), Toast.fire({
                    icon: "success",
                    title: e.message
                })) : Toast.fire({
                    icon: "error",
                    title: e.message
                })
            }
        })
    }),
    // Reverse of #forgot_password_link: back to the sign-in panel with whatever was
    // typed there still intact (closing the modal used to be the only way out).
    $(document).on("click", ".back-to-login-link", function (e) {
        e.preventDefault();
        $("#forgot_password_div").addClass("d-none");
        $("#login_div").removeClass("d-none");
        $("#verify_forgot_password_otp_form").addClass("d-none");
        $("#send_forgot_password_otp_form").removeClass("d-none");
        $("#forgot_pass_error_box, #set_password_error_box").html("");
    }),
    $(document).on("click", "#forgot_password_link", function (e) {
        e.preventDefault(), $(".auth-modal").find("header a").removeClass("active"), $("#forgot_password_div").removeClass("d-none").siblings("section").addClass("d-none"),
            $("#forgot_password_number").intlTelInput({
                allowExtensions: !0,
                formatOnDisplay: !0,
                autoFormat: !0,
                autoHideDialCode: !0,
                autoPlaceholder: !0,
                defaultCountry: "in",
                ipinfoToken: "yolo",
                nationalMode: !1,
                numberType: "MOBILE",
                preferredCountries: ["in", "ae", "qa", "om", "bh", "kw", "ma"],
                preventInvalidNumbers: !0,
                separateDialCode: !0,
                initialCountry: "in",
                /* Was initialCountry:"auto" + a JSONP geoIpLookup to ipinfo.io using the
                   placeholder token "yolo". When that call is rate-limited, blocked or slow it
                   returns an empty country, and the widget then renders NO flag at all - which
                   is the blank/grey box in the country selector. The storefront prices in INR
                   and already declared defaultCountry:"in", so start on India directly: the flag
                   always renders, and one third-party request per modal open is removed.
                   Users can still pick any other country from the dropdown. */
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.9/js/utils.js"
            })
    }), $(document).on("submit", "#send_forgot_password_otp_form", function (e) {
        e.preventDefault();
        var t = $("#forgot_password_send_otp_btn").html(),
            mobile = $("#forgot_password_number").val();
        $("#forgot_password_send_otp_btn").html("Please Wait...").attr("disabled", !0);
        $.ajax({
            type: "POST",
            url: base_url + "home/send_reset_otp",
            data: { mobile_number: mobile, [csrfName]: csrfHash },
            dataType: "json",
            success: function (res) {
                $("#forgot_password_send_otp_btn").html(t).attr("disabled", !1);
                $("#forgot_pass_error_box").html(res.message);
                if (!res.error) {
                    $("#verify_forgot_password_otp_form").removeClass("d-none");
                    $("#send_forgot_password_otp_form").hide();
                }
            },
            error: function () {
                $("#forgot_password_send_otp_btn").html(t).attr("disabled", !1);
                $("#forgot_pass_error_box").html("Something went wrong. Please try again.");
            }
        })
    }), $(document).on("submit", "#verify_forgot_password_otp_form", function (e) {
        e.preventDefault();
        var t = $("#reset_password_submit_btn").html(),
            s = new FormData(this),
            mobile = $("#forgot_password_number").val();
        s.append(csrfName, csrfHash), s.append("mobile", mobile);
        $("#reset_password_submit_btn").html("Please Wait...").attr("disabled", !0);
        $.ajax({
            type: "POST",
            url: base_url + "home/reset-password",
            data: s,
            processData: !1,
            contentType: !1,
            cache: !1,
            dataType: "json",
            success: function (res) {
                csrfName = res.csrfName || csrfName, csrfHash = res.csrfHash || csrfHash;
                $("#reset_password_submit_btn").html(t).attr("disabled", !1);
                $("#set_password_error_box").html(res.message).show();
                0 == res.error && setTimeout(function () {
                    window.location.reload()
                }, 2e3)
            },
            error: function () {
                $("#reset_password_submit_btn").html(t).attr("disabled", !1);
                $("#set_password_error_box").html("Something went wrong. Please try again.").show();
            }
        })
    }), $("#contact-us-form").on("submit", function (e) {
        e.preventDefault();
        var t = $("#contact-us-submit-btn").html(),
            a = new FormData(this);
        a.append(csrfName, csrfHash), $.ajax({
            type: "POST",
            data: a,
            url: $(this).attr("action"),
            dataType: "json",
            cache: !1,
            contentType: !1,
            processData: !1,
            beforeSend: function () {
                $("#contact-us-submit-btn").html("Please Wait...").attr("disabled", !0)
            },
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? (Toast.fire({
                    icon: "success",
                    title: e.message
                }), $("#contact-us-form")[0].reset()) : Toast.fire({
                    icon: "error",
                    title: e.message
                }), $("#contact-us-submit-btn").html(t).attr("disabled", !1)
            },
            // Sending this form goes through SMTP, which is the slowest and least
            // reliable thing on the site - a timeout or a 500 is a realistic
            // outcome. Without an error branch the button was left reading
            // "Please Wait..." and disabled for ever, with nothing said, and the
            // visitor's message lost with no way to retry.
            error: function (xhr) {
                Toast.fire({
                    icon: "error",
                    title: xhr.status === 403
                        ? "Your session expired. Please reload the page and send again."
                        : "We could not send your message just now. Please try again, or email us directly."
                });
                $("#contact-us-submit-btn").html(t).attr("disabled", !1)
            }
        })
    }), $("#product-rating-form").on("submit", function (e) {
        e.preventDefault();
        var t = $("#rating-submit-btn").html(),
            a = new FormData(this);
        a.append(csrfName, csrfHash), $.ajax({
            type: "POST",
            data: a,
            url: $(this).attr("action"),
            dataType: "json",
            cache: !1,
            contentType: !1,
            processData: !1,
            beforeSend: function () {
                $("#rating-submit-btn").html("Please Wait...").attr("disabled", !0)
            },
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? (Toast.fire({
                    icon: "success",
                    title: e.message
                }), $("#product-rating-form")[0].reset(), window.location.reload()) : Toast.fire({
                    icon: "error",
                    title: e.message
                }), $("#rating-submit-btn").html(t).attr("disabled", !1)
            }
        })
    }), $("#delete_rating").on("click", function (e) {
        if (e.preventDefault(), confirm("Are you sure want to Delete Rating ?")) {
            var t = $(this).data("rating-id");
            $.ajax({
                type: "POST",
                data: {
                    [csrfName]: csrfHash,
                    rating_id: t
                },
                url: $(this).attr("href"),
                dataType: "json",
                success: function (e) {
                    csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? (Toast.fire({
                        icon: "success",
                        title: e.message
                    }), $("#delete_rating").parent().parent().parent().remove(), $("#no_ratings").text(e.data.rating[0].no_of_rating)) : Toast.fire({
                        icon: "error",
                        title: e.message
                    })
                }
            })
        }
    }), $("#edit_link").on("click", function (e) {
        e.preventDefault(), $("#rating-box").removeClass("d-none")
    }), $("#load-user-ratings").on("click", function (e) {
        e.preventDefault();
        var t = $(this).attr("data-limit"),
            a = $(this).attr("data-offset"),
            r = $(this).attr("data-product"),
            s = $(this).html(),
            i = $(this),
            o = "";
        $.ajax({
            type: "GET",
            data: {
                limit: t,
                offset: a,
                product_id: r
            },
            url: base_url + "products/get-rating",
            dataType: "json",
            beforeSend: function () {
                $(this).html("Please wait..").attr("disabled", !0)
            },
            success: function (e) {
                $(this).html(s).attr("disabled", !1), 0 == e.error ? ($.each(e.data.product_rating, function (e, t) {
                    o += '<li class="review-container"><div class="review-image"><img src="' + base_url + 'assets/front_end/modern/images/user.png" alt="" width="65" height="65"></div><div class="review-comment"><div class="rating-list"><div class="product-rating"><input type="text" class="kv-fa" value="' + t.rating + '" data-size="xs" title="" readonly></div></div><div class="review-info"><h4 class="reviewer-name">' + t.user_name + '</h4> <span class="review-date text-muted">' + t.data_added + '</span></div><div class="review-text"><p class="text-muted">' + t.comment + '</p></div><div class="row reviews">', $.each(t.images, function (e, t) {
                        o += '<div class="col-md-2"><div class="review-box"><a href="' + t + '" data-lightbox="review-images"><img src="' + t + '" alt="' + t + '"></a></div></div>'
                    }), o += "</div></div></li>"
                }), a += t, $("#review-list").append(o), $(".kv-fa").rating("create", {
                    filledStar: '<i class="fas fa-star"></i>',
                    emptyStar: '<i class="far fa-star"></i>',
                    size: "xs",
                    showCaption: !1
                }), i.attr("data-offset", a)) : Toast.fire({
                    icon: "error",
                    title: e.message
                })
            }
        })
    }),
    $('#edit_city').on('change', function (e) {

        e.preventDefault();

        var value = $(this).val()
        if (value == 0 || value == '') {
            $('.edit_area').addClass('d-none')
            $('#edit_area').val('')
            $('.other_city').removeClass('d-none')
            $('.other_areas').removeClass('d-none')
        } else {
            $('.edit_area').removeClass('d-none')
            $('.edit_city').removeClass('d-none')
            $('.other_city').addClass('d-none')
            $('.other_areas').addClass('d-none')
        }
    }))

// $("#edit_area").on("change", function (e, t) {
//     e.preventDefault();
//     var a = "" == t || "undefined" == t ? $(this).val() : t;
//     $.ajax({
//         type: "POST",
//         data: {
//             area_id: a,
//             [csrfName]: csrfHash
//         },
//         url: base_url + "my-account/get-zipcode",
//         dataType: "json",
//         success: function (e) {
//             csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? $("#edit_pincode").val(e.data[0].zipcode) : Toast.fire({
//                 icon: "error",
//                 title: e.message
//             })
//         }
//     })
// }))


if ($('#product-filters').length) {

    if (!checkUrlHasParam()) {

        sessionStorage.setItem($('#product-filters').data('key'), $('#product-filters').val());

        var filters = sessionStorage.getItem($('#product-filters').data('key'));

        filters = filters.replace(/\\/g, "");

        print_filters(filters, 'Desktop', '#product-filters-desktop');

        print_filters(filters, 'Mobile', '#product-filters-mobile');

    } else {

        if (sessionStorage.getItem($('#product-filters').data('key')) == undefined) {

            sessionStorage.setItem($('#product-filters').data('key'), $('#product-filters').val());

        }

        var filters = sessionStorage.getItem($('#product-filters').data('key'));

        filters = filters.replace(/\\/g, "");

        print_filters(filters, 'Desktop', '#product-filters-desktop');

        print_filters(filters, 'Mobile', '#product-filters-mobile');

    }

}

// if ($('#product-filters').length) {

//     if (!checkUrlHasParam()) {

//         sessionStorage.setItem($('#product-filters').data('key'), $('#product-filters').val());

//         var filters = sessionStorage.getItem($('#product-filters').data('key'));

//         filters = filters.replace(/\\/g, "");

//         print_filters(filters, 'Desktop', '#product-filters-desktop');

//         print_filters(filters, 'Mobile', '#product-filters-mobile');

//     } else {

//         if (sessionStorage.getItem($('#product-filters').data('key')) == undefined) {

//             sessionStorage.setItem($('#product-filters').data('key'), $('#product-filters').val());

//         }

//         var filters = sessionStorage.getItem($('#product-filters').data('key'));

//         filters = filters.replace(/\\/g, "");

//         print_filters(filters, 'Desktop', '#product-filters-desktop');

//         print_filters(filters, 'Mobile', '#product-filters-mobile');

//     }

// }


// if ($('#category-filters').length) {

//     if (!checkUrlHasParam()) {
//         console.log("in category fillter");
//         sessionStorage.setItem($('#category-filters').data('key'), $('#category-filters').val());
//         var filters = sessionStorage.getItem($('#category-filters').data('key'));
//         filters = filters.replace(/\\/g, "");
//         // console.log(filters);

//         print_filters(filters, 'Desktop', '#category-filters-desktop', 'category');
//         print_filters(filters, 'Mobile', '#category-filters-mobile', 'category');

//     } else {
//         console.log("in category fillter else");
//         if (sessionStorage.getItem($('#category-filters').data('key')) == undefined) {
//             sessionStorage.setItem($('#category-filters').data('key'), $('#category-filters').val());
//         }

//         var filters = sessionStorage.getItem($('#category-filters').data('key'));
//         filters = filters.replace(/\\/g, "");
//         // console.log(filters);
//         print_filters(filters, 'Desktop', '#category-filters-desktop', 'category');

//         print_filters(filters, 'Mobile', '#category-filters-mobile', 'category');

//     }

// }

// if ($('#brand-filters').length) {

//     if (!checkUrlHasParam()) {
//         console.log("in brand fillter");

//         sessionStorage.setItem($('#brand-filters').data('key'), $('#brand-filters').val());

//         var filters = sessionStorage.getItem($('#brand-filters').data('key'));

//         filters = filters.replace(/\\/g, "");

//         // print_filters(filters, 'Desktop', '#product-filters-desktop');

//         // print_filters(filters, 'Mobile', '#product-filters-mobile');

//     } else {
//         console.log("in brand fillter else");


//         if (sessionStorage.getItem($('#brand-filters').data('key')) == undefined) {

//             sessionStorage.setItem($('#brand-filters').data('key'), $('#brand-filters').val());

//         }

//         var filters = sessionStorage.getItem($('#brand-filters').data('key'));

//         filters = filters.replace(/\\/g, "");

//         print_filters(filters, 'Desktop', '#brand-filters-desktop');

//         print_filters(filters, 'Mobile', '#brand-filters-mobile');

//     }

// }



function print_filters(filters, prefix = '', target) {
    var html = '';
    var attribute_values_id;
    var attribute_values;
    var new_attr_val;
    var attr_name;
    var collapse_status;
    var selected_attributes;
    var attr_checked_status;
    var e_name;

    if (filters != "") {
        // console.log(filters); 
        $.each(JSON.parse(filters), function (i, e) {
            // console.log(e);

            e_name = e.name.replace(' ', '-').toLowerCase();
            e_name = decodeURIComponent(e_name);
            attr_name = getUrlParameter('filter-' + e_name);
            collapse_status = (attr_name == undefined) ? " " : "show";
            selected_attributes = (attr_name != undefined) ? attr_name.split('|') : "";


            const brand_name = getUrlParameter('brand');
            var brands = $('[data-value="' + brand_name + '"]');
            $('[data-value="' + brand_name + '"]').attr('checked', true);
            var gp = $(brands).siblings();
            $(gp).addClass('selected-brand');


            const category_id = getUrlParameter('category');
            var categories = $('[data-value="' + category_id + '"]');
            $('[data-value="' + category_id + '"]').attr('checked', true);
            $(categories).addClass('selected-category');


            html +=
                '<div class="accordion accordion-wrapper" id="accordionSimpleExample">' +
                '<div class="card plain accordion-item">' +
                '<div class="card-header" id="h' + i + '">' +
                '<button class="accordion-button text-decoration-none text-dark h6 collapsed" data-bs-toggle="collapse" data-bs-target="#' + prefix + i + '" aria-expanded="false" aria-controls="#' + prefix + i + '" style="cursor: pointer;">' + e.name + '</button>' +
                '</div>' +
                '<div id="' + prefix + i + '" class="accordion-collapse collapse" aria-labelledby="h' + i + '" data-bs-parent="#accordionSimpleExample">' +
                '<div class="card-body-custom ml-5">';

            attribute_values_id = e.attribute_values_id.split(',');
            attribute_values = e.attribute_values.split(',');

            $.each(attribute_values, function (j, v) {
                attr_checked_status = ($.inArray(v, selected_attributes) !== -1) ? "checked" : "";
                new_attr_val = e_name + ' ' + v;
                html +=
                    '<div class="input-container d-flex">' +
                    '<input type="checkbox" name="' + v + '" value="' + v + '" class="form-check-input toggle-input product_attributes" id="' + prefix + new_attr_val + '" data-attribute="' + e_name + '" ' + attr_checked_status + '>' +
                    '<label class="form-check-label toggle checkbox" for="' + prefix + new_attr_val + '">' +
                    '<div class="toggle-inner"></div>' +
                    '</label>' +
                    '<label for="' + prefix + new_attr_val + '" class="text-label">' + v + '</label>' +
                    '</div>';
            });
            html += '</div></div></div></div>';
            // } else {

            //     html += '<div class="form-check">'+
            //             '<input class="form-check-input category" type="radio" name="categoryRadio" data-value="'+e.id+'" id="'+e.id+'" value="" checked>'+
            //             '<label class="form-check-label" for="'+e.id+'">'+ e.name +'</label></div>';
            //     console.log("in else fceskjdl");
            // }

        });

    }
    $(target).html(html);

}

function usercartTotal() {
    /* var e = 0;
    $("#cart_item_table > tbody > tr > .total-price  > .product-line-price").each(function (t) {
        e = parseFloat(e) + parseFloat($(this).text().replace(/[^\d\.]/g, ""))
    }), $("#final_total").text(e.toFixed(2)) */

    // --- Updated for Cretzo

    updateCartDetails();
    
    /* var cart_count = 0;

    var subtotal = 0;
    $(".total-price  > .discounted-price.product-line-price").each(function (t) {
        subtotal = Math.round(parseFloat(subtotal) + parseFloat($(this).text().replace(/[^\d\.]/g, "")));
        cart_count++;
    });
    
    var total_mrp = 0;
    $(".total-price  > .actual-price.product-line-price").each(function (t) {
        total_mrp = Math.round(parseFloat(total_mrp) + parseFloat($(this).text().replace(/[^\d\.]/g, "")));
    });

    var shipping = parseFloat($("#final_shipping_fee").text().replace(/[^\d\.]/g, ""));
    var total = subtotal + shipping;

    $("#final_discount_mrp").text('₹'+moneyFormatIndia(total_mrp - subtotal));
    $("#final_total_mrp").text('₹'+moneyFormatIndia(total_mrp));
    $("#final_subtotal").text('₹'+moneyFormatIndia(subtotal));
    $("#final_total").text('₹'+moneyFormatIndia(total));

    // update cart count
    $(".cart-count").text(cart_count);

    // set state of checkout button
    if(cart_count <= 0){
        $(".checkout").addClass('disabled');
        $('#select-all-checkbox').
    }
    else{
        $(".checkout").removeClass('disabled');
    } */
}

function shortDescriptionWordLimit(e, t = 35, a = "...") {
    return e.length > t ? e.substring(0, t - a.length) + a : e
}

$(document).ready(function () {
    $(".kv-svg").rating({
        theme: "krajee-svg",
        showClear: !1,
        showCaption: !1,
        size: "md"
    });
});


function display_compare() {
    var e = localStorage.getItem("compare");
    e = null !== localStorage.getItem("compare") ? e : null, $.ajax({
        type: "POST",
        url: base_url + "compare/add_to_compare",
        data: {
            product_id: e,
            product_variant_id: e,
            [csrfName]: csrfHash
        },
        dataType: "json",
        success: function (t) {
            csrfName = t.csrfName, csrfHash = t.csrfHash;
            var a = e.length ? e.length : "base_url()";
            $("#compare_count").text(t.data.total);
            var r = "";
            0 == t.error ? (null !== e && a > 0 && (r += '<div class="align-self-end mb-7"><div class="compare-removal"><button class="remove-compare btn btn-danger btn-sm" >Clear Compare</button></div></div></div><div><table class="compare-table table-bordered"><tbody><tr><th class="compare-field w-19"> </th>', $.each(t.data.product, function (e, t) {
                var a = t.variants[0].special_price > 0 && "" != t.variants[0].special_price ? t.variants[0].special_price : t.variants[0].price,
                    s = t.minimum_order_quantity ? t.minimum_order_quantity : 1,
                    i = t.minimum_order_quantity && t.quantity_step_size ? t.quantity_step_size : 1,
                    o = t.total_allowed_quantity ? t.total_allowed_quantity : 1;
                if (r += '<td class="compare_item text-center text-justify"><div class="p-5"><div class="text-right"><a class="remove-compare-item"data-product-id="' + t.id + '" style="padding: 4px 8px border:0px !important" ><i class="fa-times fa-times-plus fa-lg fa link-color"></i></a></div><br><div class="product-grid" style="border:1px !important; padding:0 0 0px;"><div class="product-image"><div class="rounded compare-img"><a href="products/details/' + t.slug + '"><img class="pro-img" src="' + t.image + '" style="object-fit:cover;"></a></div></div><div itemscope itemtype="https://schema.org/Product">', t.rating && "" != t.no_of_rating ? r += '<div class="col-md-12 mb-3 product-rating-small" dir="ltr" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating"><meta itemprop="reviewCount" content="' + t.no_of_rating + '" /><meta itemprop="ratingValue" content="' + t.rating + '" /><input id="input" name="rating" class="kv-svg rating rating-loading d-none" data-size="xs" value="' + t.rating + '" data-show-clear="false" data-show-caption="false" readonly> <span class="my-auto mx-3"> ( ' + t.no_of_ratings + " reviews) </span></div>" : r += '<div class="col-md-12 mb-3 product-rating-small" dir="ltr"><input id="input" name="rating" class="kv-svg rating rating-loading d-none" data-size="xs" value="' + t.rating + '" data-show-clear="false" data-show-caption="false" readonly> <span class="my-auto mx-3"> ( ' + t.no_of_ratings + " reviews) </span></div>", r += "</div>", r += ' <h4 class="data-product-title" ><a class="text-decoration-none" href="products/details/' + t.slug + '">' + shortDescriptionWordLimit(t.name) + '</a></h4>   <div class="price mb-1">' + currency + ("simple_product" == t.type ? '<small style="font-size: 20px;">' + t.variants[0].price + "</small>" : '<small style="font-size: 20px;">' + t.min_max_price.max_special_price + '</small> - <small style="font-size: 20px;">' + t.min_max_price.max_price) + "</small> </div>", "simple_product" == t.type) var n = t.variants[0].id,
                    c = "";
                else n = "", c = "#quick-view";
                r += '  <a href="#" class="add_to_cart btn btn-sm btn-outline-primary rounded-pill" data-product-id="' + t.id + '" data-product-variant-id="' + n + '" data-izimodal-open="' + c + '" data-product-title="' + t.name + '" data-product-slug="' + t.slug + '" data-product-image="' + t.image + '" data-product-description="' + t.short_description + '"  data-product-price="' + a + '" data-min="' + s + '" data-max="' + o + '" data-step="' + i + '"><i class="uil uil-shopping-bag"></i> &nbsp; Add to Cart</a>'
            }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Description </th>', $.each(t.data.product, function (e, t) {
                    r += '<td class="text-center text-justify" data-title="Availability">' + (t.short_description ? t.short_description : t.short_description = "-") + "</td>"
                }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Variants </th>', $.each(t.data.product, function (e, t) {
                    var a = t.variants[0].attr_name.split(","),
                        s = t.variants[0].variant_values.split(",");
                    if ("variable_product" == t.type) {
                        r += '<td class="text-center text-justify" data-title="variants">';
                        for (e = 0; e < a.length; e++) a[e] !== s[e] && (r += a[e] + " : " + s[e] + "<br>");
                        r += "</td>"
                    } else r += '<td class="text-center text-justify" data-title="variants">-</td>'
                }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Availability  </th>', $.each(t.data.product, function (e, t) {
                    r += '<td class="text-center text-justify" data-title="Availability">' + ("1" == t.availability ? t.availability = "In Stock" : t.availability = "-") + "</td>"
                }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Made In </th>', $.each(t.data.product, function (e, t) {
                    r += '<td class="text-center text-justify" data-title="made in">' + (t.made_in ? t.made_in : "-") + "</td>"
                }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Warranty</th>', $.each(t.data.product, function (e, t) {
                    r += '<td class="text-center text-justify" data-title="warranty period">' + (t.warranty_period ? t.warranty_period : "-") + "</td>"
                }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Gaurantee</th>', $.each(t.data.product, function (e, t) {
                    r += '<td class="text-center text-justify" data-title="warranty period">' + (t.guarantee_period ? t.guarantee_period : "-") + "</td>"
                }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Returnable</th>', $.each(t.data.product, function (e, t) {
                    r += '<td class="text-center text-justify" data-title="Returnable">' + ("1" == t.is_returnable ? t.is_returnable = "Yes" : t.is_returnable = "No") + "</td>"
                }),

                r += "</tr>", r += '<tr><th class="compare-field text-dark fs-17 text-center">Cancellation</th>', $.each(t.data.product, function (e, t) {
                    r += '<td class="text-center text-justify" data-title="cancelable">' + ("1" == t.is_cancelable ? t.is_cancelable = "Yes" : t.is_cancelable = "No") + "</td>"
                }),

                r += "</tr>", r += "</tbody></table></div>"),

                $("#compare-items").html(r),
                $(".kv-svg").rating({
                    theme: "krajee-svg",
                    showClear: !1,
                    showCaption: !1,
                    size: "md"
                })
            ) : Toast.fire({
                icon: "error",
                title: t.message
            })
        }
    })
}
$(document).on("closed", "#quick-view", function (e) {
    $("#modal-product-special-price").html("")
}), $(document).ready(function () {
    navigator.geolocation && navigator.geolocation.getCurrentPosition(function (e) {
        var t = e.coords.latitude,
            a = e.coords.longitude;
        sessionStorage.setItem("latitude", t), sessionStorage.setItem("longitude", a)
    }, function (e) {
        switch (e.code) {
            case e.PERMISSION_DENIED:
                null !== sessionStorage.getItem("latitude") && sessionStorage.removeItem("latitude"), null !== sessionStorage.getItem("longitude") && sessionStorage.removeItem("longitude");
                break;
            case e.POSITION_UNAVAILABLE:
                console.log("Location information is unavailable.");
                break;
            case e.TIMEOUT:
                console.log("The request to get user location timed out.");
                break;
            case e.UNKNOWN_ERROR:
                console.log("An unknown error occurred.")
        }
    })
}), $("#send_bank_receipt_form").on("submit", function (e) {
    e.preventDefault();
    var t = new FormData(this);
    t.append(csrfName, csrfHash), $.ajax({
        type: "POST",
        url: $(this).attr("action"),
        data: t,
        beforeSend: function () {
            $("#submit_btn").html("Please Wait..").attr("disabled", !0)
        },
        cache: !1,
        contentType: !1,
        processData: !1,
        dataType: "json",
        success: function (e) {
            csrfHash = e.csrfHash, $("#submit_btn").html("Send").attr("disabled", !1), 0 == e.error ? ($("table").bootstrapTable("refresh"), Toast.fire({
                icon: "success",
                title: e.message
            }), window.location.reload()) : Toast.fire({
                icon: "error",
                title: e.message
            })
        }
    })
}), $(document).ready(function () {
    $(".hrDiv").length && ($(".hrDiv p").addClass("hrDiv"), $("div").css({
        "font-size": "",
        font: ""
    }))
}), $("#validate-zipcode-form").on("submit", function (e) {
    e.preventDefault();
    var t = new FormData(this);
    t.append(csrfName, csrfHash), $.ajax({
        type: "POST",
        url: base_url + "products/check_zipcode",
        data: t,
        beforeSend: function () {
            $("#validate_zipcode").html("Please Wait..").attr("disabled", !0)
        },
        cache: !1,
        contentType: !1,
        processData: !1,
        dataType: "json",
        success: function (e) {
            csrfHash = e.csrfHash, $("#validate_zipcode").html("Check Availability").attr("disabled", !1), 0 == e.error ? ($("#add_cart").removeAttr("disabled"), $("#error_box").html(e.message)) : ($("#add_cart").attr("disabled", "true"), $("#error_box").html(e.message))
        }
    })
}), $(document).on("submit", ".validate_zipcode_quick_view", function (e) {
    e.preventDefault();
    var t = new FormData(this);
    t.append(csrfName, csrfHash), $.ajax({
        type: "post",
        url: base_url + "products/check-zipcode",
        data: t,
        beforeSend: function () {
            $("#validate_zipcode").html("Please Wait..").attr("disabled", !0)
        },
        cache: !1,
        contentType: !1,
        processData: !1,
        dataType: "json",
        success: function (e) {
            csrfHash = e.csrfHash, $("#validate_zipcode").html("Check Availability").attr("disabled", !1), 0 == e.error ? ($("#modal-add-to-cart-button").removeAttr("disabled"), $("#error_box1").html(e.message)) : ($("#modal-add-to-cart-button").attr("disabled", "true"), $("#error_box1").html(e.message))
        }
    })
}),
    $(".view_cart_button").click(function () {
        return 0 != is_loggedin || ($("#modal-signin").show(),
            $("#login_div").removeClass("d-none"),
            $("#login").addClass("active"),
            // $("#register_div").addClass("hide"), 
            $("#register").removeClass("active"), !1)
    }),
    $(document).ready(function () {
        if (localStorage.getItem("compare")) {
            var e = localStorage.getItem("compare").length;
            (e = null !== e ? JSON.parse(e) : null) && display_compare()
        }
    }), $(document).on("click", ".compare", function (e) {
        e.preventDefault();
        var t = $(this).attr("data-product-id"),
            a = $(this).attr("data-product-variant-id"),
            r = {
                product_id: t.trim(),
                product_variant_id: a.trim()
            },
            s = localStorage.getItem("compare");
        if (Toast.fire({
            icon: "success",
            title: "products added to compare list"
        }), null != (s = null !== s ? JSON.parse(s) : null)) {
            if (s.find(e => e.product_id === t)) return void Toast.fire({
                icon: "error",
                title: "This item is already present in your compare list"
            });
            s.push(r)
        } else s = [r];
        localStorage.setItem("compare", JSON.stringify(s));
        var i = s.length ? s.length : "";
        if ($("#compare_count").text(i), null !== s && i <= 1) return Toast.fire({
            icon: "warning",
            title: "Please select 1 more item to compare"
        }), !1
    }), $(document).on("click", ".remove-compare-item", function (e) {
        e.preventDefault();
        var t = $(this).attr("data-product-id");
        if (confirm("Are you sure want to remove this?")) {
            var a = $("#compare_count").text();
            a--, $("#compare_count").text(a), a < 1 ? ($(this).parent().parent().remove(), location.reload()) : $(this).parent().parent().remove();
            var r = localStorage.getItem("compare");
            if (r = null !== r ? JSON.parse(r) : null) {
                var s = r.filter(function (e) {
                    return e.product_id != t
                });
                localStorage.setItem("compare", JSON.stringify(s)), display_compare()
            }
        }
    }), $(document).on("click", ".compare-removal button", function (e) {
        e.preventDefault();
        var t = $(this).attr("data-product-id"),
            a = $(this).parent().parent().parent();
        if (confirm("Are you sure want to remove this?")) {
            localStorage.removeItem("compare"), location.reload();
            a = localStorage.getItem("compare");
            if (a = null !== localStorage.getItem("compare") ? JSON.parse(a) : null) {
                var r = a.filter(function (e) {
                    return e.id != t
                });
                localStorage.setItem("compare", JSON.stringify(r)), a && display_compare(r)
            }
        }
    }), $(document).on("submit", "#add-faqs", function (e) {
        e.preventDefault();
        var t = new FormData(this);
        t.append(csrfName, csrfHash), $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            dataType: "json",
            data: t,
            processData: !1,
            contentType: !1,
            success: function (e) {
                csrfName = e.csrfName, csrfHash = e.csrfHash, 0 == e.error ? (Toast.fire({
                    icon: "success",
                    title: e.message
                }), $("#add-faqs")[0].reset()) : Toast.fire({
                    icon: "error",
                    title: e.message
                }), setTimeout(function () {
                    location.reload()
                }, 1e3)
            }
        })
    }), $(".search_faqs").select2({
        ajax: {
            url: base_url + "products/get_faqs_data",
            type: "GET",
            dataType: "json",
            delay: 250,
            data: function (e) {
                return {
                    search: e.term
                }
            },
            processResults: function (e) {
                return {
                    results: e
                }
            },
            cache: !0
        },
        minimumInputLength: 1,
        theme: "bootstrap4",
        placeholder: "Search for faqs"
    });

$(function () {
    $("#inspect_value").data("value");
    return !1
});

$(document).ready(function () {
    // Handle Redirect Sign-in Result on page load
    firebase.auth().getRedirectResult().then(function (result) {
        if (result && result.user) {
            var provider = 'google';
            if (result.credential && result.credential.providerId.indexOf('facebook') !== -1) {
                provider = 'facebook';
            } else if (result.user.providerData && result.user.providerData[0] && result.user.providerData[0].providerId.indexOf('facebook') !== -1) {
                provider = 'facebook';
            }
            toggleAuthLoading(true, 'Signing in with ' + provider + '...');
            setSocialButtonLoading(provider, true);
            handleSocialLogin(result.user, provider);
        }
    }).catch(function (error) {
        console.error("Redirect sign-in error: ", error);
        toggleAuthLoading(false);
    });

    $("#share").jsSocials({
        showLabel: false,
        showCount: false,
        shares: ["twitter", "facebook", "whatsapp", "pinterest", "linkedin", "googleplus"]
    });
    $(document).on('click', '.social-auth-link', function (e) {
        e.preventDefault();
        var provider = $(this).data('auth-provider');
        if (provider === 'facebook') {
            facebookSignIn();
        } else if (provider === 'google') {
            googleSignIn();
        }
    });
    $(document).on('click', '#googleLogout', function (e) {
        e.preventDefault();
        firebase.auth().signOut()
            .then(function () {
                // Sign-out successful.
                alert('You have been logged out.');
            })
            .catch(function (error) {
                // An error happened.
                console.error(error);
            });
    });

    function handleSocialLogin(user, providerName) {
        // One round trip. We send ONLY the Firebase ID token; the server verifies its
        // signature against Google's public keys and reads the uid/email/name out of the
        // verified token itself. Nothing identifying the user is taken from this request,
        // because a POSTed email could simply be forged.
        //
        // (This previously chained 3 AJAX calls - verifyUser -> auth/register_user ->
        // home/login - each enforcing a hard "must have an email" rule that blocked any
        // Facebook account which didn't return one.)
        function fail(msg) {
            setSocialButtonLoading(providerName, false);
            toggleAuthLoading(false);
            Toast.fire({ icon: 'error', title: msg || 'Something went wrong signing you in. Please try again.' });
        }

        user.getIdToken().then(function (idToken) {
            $.ajax({
                type: 'POST',
                url: base_url + 'home/social_login',
                data: {
                    id_token: idToken,
                    type: providerName,
                    [csrfName]: csrfHash
                },
                dataType: 'json',
                success: function (result) {
                    if (result.csrfName) { csrfName = result.csrfName; }
                    if (result.csrfHash) { csrfHash = result.csrfHash; }

                    if (result.error == false) {
                        closeLoginPopupFast();
                        setTimeout(function () { location.reload(); }, 120);
                    } else {
                        fail(result.message);
                    }
                },
                error: function () { fail(); }
            });
        }).catch(function () {
            fail();
        });
    }
    // The previous "account conflict" flow made a user who hit
    // auth/account-exists-with-different-credential click the OTHER provider first, wait
    // for a toast, then click again - two sign-ins and a lot of explaining. It is no longer
    // needed: that error only means Firebase already has this verified email under a
    // different provider, and the server-side flow matches an existing account by the email
    // inside the verified token, so a normal sign-in with either provider lands on the same
    // local account. We simply tell the user which provider to use and let them do it in
    // one click.
    function handleAccountConflict(error, attemptedProvider) {
        setSocialButtonLoading(attemptedProvider, false);
        toggleAuthLoading(false);
        var other = attemptedProvider === 'facebook' ? 'Google' : 'Facebook';
        Toast.fire({
            icon: 'info',
            title: 'This email is already registered with ' + other + '. Please continue with ' + other + '.',
            timer: 6000
        });
    }

    function googleSignIn() {
        toggleAuthLoading(true, 'Opening Google sign-in...');
        setSocialButtonLoading('google', true);
        var provider = new firebase.auth.GoogleAuthProvider();
        provider.addScope('email');
        firebase.auth().signInWithPopup(provider).then(function (result) {
            toggleAuthLoading(true, 'Signing in with Google...');
            handleSocialLogin(result.user, 'google');
        }).catch(function (error) {
            if (error && error.code === 'auth/account-exists-with-different-credential') {
                handleAccountConflict(error, 'google');
                return;
            }
            setSocialButtonLoading('google', false);
            toggleAuthLoading(false);
            if (error && (error.code === 'auth/popup-blocked' || error.code === 'auth/popup-closed-by-user')) {
                toggleAuthLoading(true, 'Redirecting to Google sign-in...');
                firebase.auth().signInWithRedirect(provider);
                return;
            }
            console.log(error.message);
        });
    }
    function facebookSignIn() {
        toggleAuthLoading(true, 'Opening Facebook sign-in...');
        setSocialButtonLoading('facebook', true);
        var provider = new firebase.auth.FacebookAuthProvider();
        provider.addScope('email');
        firebase.auth().signInWithPopup(provider).then(function (result) {
            toggleAuthLoading(true, 'Signing in with Facebook...');
            handleSocialLogin(result.user, 'facebook');
        }).catch(function (error) {
            if (error && error.code === 'auth/account-exists-with-different-credential') {
                handleAccountConflict(error, 'facebook');
                return;
            }
            setSocialButtonLoading('facebook', false);
            toggleAuthLoading(false);
            if (error && (error.code === 'auth/popup-blocked' || error.code === 'auth/popup-closed-by-user')) {
                toggleAuthLoading(true, 'Redirecting to Facebook sign-in...');
                firebase.auth().signInWithRedirect(provider);
                return;
            }
            console.log(error);
        });
    }
});


var swiperS = new Swiper('.category-swiper', {
    slidesPerView: 5,
    preloadImages: false,
    updateOnImagesReady: false,
    lazyLoadingInPrevNextAmount: 0,
    pagination: {
        el: ".category-swiper-pagination",
        clickable: !0
    },
    breakpoints: {
        350: {
            slidesPerView: 3,
            spaceBetweenSlides: 10
        },
        400: {
            slidesPerView: 4,
            spaceBetweenSlides: 10
        },
        499: {
            slidesPerView: 4,
            spaceBetweenSlides: 10
        },
        550: {
            slidesPerView: 5,
            spaceBetweenSlides: 10
        },
        600: {
            slidesPerView: 5,
            spaceBetweenSlides: 10
        },
        700: {
            slidesPerView: 6,
            spaceBetweenSlides: 10
        },
        800: {
            slidesPerView: 8,
            spaceBetweenSlides: 10
        },
        999: {
            slidesPerView: 8,
            spaceBetweenSlides: 10
        },
        1900: {
            slidesPerView: 8,
            spaceBetweenSlides: 10
        },
        1900: {
            slidesPerView: 8,
            spaceBetweenSlides: 10
        }
    }
});

swiper = new Swiper(".swiper-slide-container", {
    slidesPerView: 1,
    effect: "slide",
    pagination: {
        el: ".slide-swiper-pagination",
        clickable: !0
    },
    loop: !0,
    autoplay: {
        delay: 3500
    }
    // speed: 2e3
});


swiper = new Swiper(".mySwiper", {
    slidesPerView: 3,
    spaceBetween: 30,
    pagination: {
        el: ".product-swiper-pagination",
        clickable: !0
    },
    breakpoints: {
        300: {
            slidesPerView: 1,
            spaceBetweenSlides: 10
        },
        350: {
            slidesPerView: 1,
            spaceBetweenSlides: 10
        },
        400: {
            slidesPerView: 1,
            spaceBetweenSlides: 10
        },
        499: {
            slidesPerView: 1,
            spaceBetweenSlides: 10
        },
        600: {
            slidesPerView: 2,
            spaceBetweenSlides: 10
        },
        800: {
            slidesPerView: 2,
            spaceBetweenSlides: 10
        },
        801: {
            slidesPerView: 3,
            spaceBetweenSlides: 10
        }
    }
});




// color switcher

jQuery(document).ready(function ($) {
    $(".color-switcher").on("click", function () {
        // console.log($(this).data("value"));
        $("#color-switcher").attr("href", $(this).data("url"));
        $(".logo-img").attr("src", $(this).data("image"));
        return false;
    });
    $("ul.color-style li a").click(function (e) {
        e.preventDefault();
        $(this).parent().parent().find("a").removeClass("active");
        $(this).addClass("active");
    })
    $("#colors-switcher .color-bottom a.settings").click(function (e) {
        e.preventDefault();
        var div = $("#colors-switcher");
        if (div.css(mode) === "-189px") {
            $("#colors-switcher").animate({
                [mode]: "0px"
            });
        } else {
            $("#colors-switcher").animate({
                [mode]: "-189px"
            });
        }
    })
    $("#colors-switcher").animate({
        [mode]: "-189px"
    });
});

$(document).ready(function () {
    /* Support chat widget.
     *
     * `.toggle()` flipped display:none straight to display:block, so the panel snapped in with
     * no transition and the `opened` class it also set had nothing to animate. Opening now
     * unhides first and adds the class on the next frame so the CSS transition actually runs,
     * and closing waits for it to finish before hiding. The widget also had no way to close
     * itself from inside the iframe - the header's X posts a message that is handled here. */
    var $chatFab = $("#chat-button");
    var $chatPanel = $("#chat-iframe");

    function chatIsOpen() {
        return $chatPanel.hasClass("opened");
    }

    function openChat() {
        if (chatIsOpen()) {
            return;
        }
        $chatPanel.show();
        // Force a reflow so the browser has a pre-transition state to animate away from.
        $chatPanel[0].offsetHeight;
        $chatPanel.addClass("opened");
        $chatFab.addClass("opened").attr("aria-expanded", "true");
    }

    function closeChat() {
        if (!chatIsOpen()) {
            return;
        }
        $chatPanel.removeClass("opened");
        $chatFab.removeClass("opened").attr("aria-expanded", "false");
        window.setTimeout(function () {
            if (!chatIsOpen()) {
                $chatPanel.hide();
            }
        }, 240);
    }

    function toggleChat() {
        if (chatIsOpen()) {
            closeChat();
        } else {
            openChat();
        }
    }

    $chatFab.on("click", function (e) {
        e.preventDefault();
        toggleChat();
    });

    $("#chat-with-button").on("click", function (e) {
        e.preventDefault();
        $chatPanel.attr("src", base_url + "my-account/floating_chat_modern?user_id=" + $(this).data("id"));
        toggleChat();
    });

    // The widget's own close button and Escape key ask the parent to dismiss the panel;
    // the origin check keeps any other framed page from driving this.
    window.addEventListener("message", function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }
        var payload = event.data;
        if (payload && payload.cretzoChat === "close") {
            closeChat();
        }
    });

    // Escape on the host page closes it too, for people who never focused the iframe.
    $(document).on("keydown", function (e) {
        if ((e.key === "Escape" || e.keyCode === 27) && chatIsOpen()) {
            closeChat();
        }
    });
});
$(document).ready(function () {
    // Submit chat message to backend on form submit
    $("#chat-form2").submit(function (e) {
        e.preventDefault();
        var message = $("#message").val();

        $.ajax({
            url: "<?php echo base_url('ChatController/send_message'); ?>",
            type: "POST",
            dataType: "json",
            data: { message: message },
            success: function (response) {
                // Handle success response
                // Update chat UI with the sent message if needed
            },
            error: function (xhr, status, error) {
                // Handle error response
            }
        });
    });
});
$(document).ready(function () {
    // Submit chat message to backend on form submit
    $(".reorder-btn").on("click", (event) => {
        const variants = ($(event.target).data("variants")) + ""
        const qty = ($(event.target).data("quantity")) + ""
        console.log(variants)
        console.log(qty)
        let html = $(event.target).html()
        $.ajax({
            type: "POST",
            url: base_url + "cart/manage",
            data: {
                product_variant_id: variants,
                qty: qty,
                is_saved_for_later: false,
                [csrfName]: csrfHash
            },
            dataType: "json",
            beforeSend: function () {
                $(event.target).text("Please Wait").attr("disabled", true)
            },
            success: function (res) {
                $(event.target).text(html).attr("disabled", false)
                window.location.href = base_url + "cart/checkout"
            }
        })

    })

});

$(document).ready(function () {
    $('.select2-container').click(function (event) {
        event.preventDefault();
        if ($('#offcanvas-search').hasClass('show')) {
            console.log('in offcanvas search ');
            $('.select2-search--dropdown').addClass('mt-n10');
        }
    });
});
"use strict";

    /* ═══════════════════════════════════════════════
       FIX 2: FILTERS — collect ALL and Apply at once
    ═══════════════════════════════════════════════ */
    $(document).on('click', '#cretzo-apply-filter', function (e) {
        e.preventDefault();
        var base   = window.location.pathname;
        var params = {};
        var keep   = ['seller', 'seller_search', 'sort', 'type', 'per-page'];
        keep.forEach(function(k) { var v = getParam(k); if (v) params[k] = v; });

        // Categories
        var cats = [];
        $('.fs-category input[type="checkbox"]:checked').each(function () {
            var v = $(this).data('value') || $(this).val();
            if (v) cats.push(v);
        });
        if (cats.length) params['category'] = cats.join('|');

        // Brands
        var brands = [];
        $('.fs-brand input[type="checkbox"]:checked').each(function () {
            var v = $(this).data('value') || $(this).val();
            if (v) brands.push(v);
        });
        if (brands.length) params['brand'] = brands.join('|');

        // Price range sliders
        var rangeMin = $('.range-min').val();
        var rangeMax = $('.range-max').val();
        if (rangeMin) params['min-price'] = rangeMin;
        if (rangeMax) params['max-price'] = rangeMax;
        // Price text inputs override sliders
        var textMin = $('.price-input input').first().val();
        var textMax = $('.price-input input').last().val();
        if (textMin) params['min-price'] = textMin;
        if (textMax) params['max-price'] = textMax;

        // Attributes
        $('.fs-attr').each(function () {
            var attrName = $(this).find('.filter-heading').text().trim().toLowerCase().replace(/\s+/g, '-');
            var vals = [];
            $(this).find('input[type="checkbox"]:checked').each(function () {
                var v = $(this).data('value') || $(this).val();
                if (v) vals.push(v.toLowerCase());
            });
            if (vals.length) params['filter-' + attrName] = vals.join('|');
        });

        var qs = Object.keys(params).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        location.href = base + (qs ? '?' + qs : '');
    });

    $(document).on('click', '#cretzo-clear-filter, #clear-all-filters-btn', function (e) {
        e.preventDefault();
        location.href = window.location.pathname;
    });

    $(document).on('input', '.range-min', function () {
        $(this).closest('.filter-section').find('.price-input input').first().val($(this).val());
    });
    $(document).on('input', '.range-max', function () {
        $(this).closest('.filter-section').find('.price-input input').last().val($(this).val());
    });

    /* ═══════════════════════════════════════════════
       FIX 3: RUPEE SYMBOL — replace all Rs. with ₹
    ═══════════════════════════════════════════════ */
    function fixRupeeIn(selector) {
        $(selector).find('*').addBack().contents().filter(function () {
            return this.nodeType === 3;
        }).each(function () {
            var old = this.nodeValue;
            var updated = old.replace(/Rs\.?\s*/g, '₹');
            if (updated !== old) this.nodeValue = updated;
        });
    }
    $(document).ready(function () { fixRupeeIn('body'); });
    $(document).ajaxComplete(function () {
        fixRupeeIn('.price-container, .discounted-price, .original-price, .cart-item, #cart-item-sidebar');
    });

    /* ═══════════════════════════════════════════════
       FIX 4: ADDRESS POPUP — Select2 inside modal
    ═══════════════════════════════════════════════ */
    function initModalSelect2(modalId) {
        if (typeof $.fn.select2 === 'undefined') return;
        $('#' + modalId + ' .form-select2').each(function () {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    dropdownParent: $('#' + modalId),
                    placeholder: $(this).find('option:first').text(),
                    allowClear: true
                });
            }
        });
    }
    $(document).ready(function () {
        $('#add-address-modal').on('shown.bs.modal',  function () { initModalSelect2('add-address-modal');  });
        $('#edit-address-modal').on('shown.bs.modal', function () { initModalSelect2('edit-address-modal'); });
    });

    /* ═══════════════════════════════════════════════
       FIX 5: CART SHOW MORE
    ═══════════════════════════════════════════════ */
    $(document).on('click', '.show-more-text', function () {
        var $section = $(this).closest('.cart-left-two');
        var $hidden  = $section.find('.more-offers');
        if ($hidden.length) {
            $hidden.slideToggle(200);
            var isOpen = $hidden.is(':visible');
            $(this).html(isOpen
                ? 'Show Less <img class="show-more-img" src="' + (typeof base_url !== 'undefined' ? base_url : '/') + 'assets/front_end/cretzo/img/new_cretzo/orange-arrow.png" style="transform:rotate(180deg);">'
                : 'Show More <img class="show-more-img" src="' + (typeof base_url !== 'undefined' ? base_url : '/') + 'assets/front_end/cretzo/img/new_cretzo/orange-arrow.png">');
        } else {
            var extraOffers = [
                '10% off on HDFC Bank Credit Cards. Min spend ₹1,500.',
                '5% cashback on Paytm UPI transactions.',
                'No cost EMI on orders above ₹4,999.'
            ];
            var html = '<div class="more-offers" style="display:none;">';
            extraOffers.forEach(function(o) { html += '<p class="text-s" style="margin-top:6px;">' + o + '</p>'; });
            html += '</div>';
            $(this).before(html);
            $section.find('.more-offers').slideDown(200);
            $(this).html('Show Less <img class="show-more-img" src="' + (typeof base_url !== 'undefined' ? base_url : '/') + 'assets/front_end/cretzo/img/new_cretzo/orange-arrow.png" style="transform:rotate(180deg);">');
        }
    });

    /* ═══════════════════════════════════════════════
       FIX 6: BANNERS — use each banner's own href
    ═══════════════════════════════════════════════ */
    $(document).ready(function () {
        // Remove any overriding click on banner links — let natural href work
        $(document).off('click', '.swiper-slide .slide-img a');
    });

   
"use strict";

(function ($) {

    /* =========================================================
       Helpers
    ========================================================= */

    function debounce(fn, delay) {
        let timer;

        return function () {
            clearTimeout(timer);

            const context = this;
            const args = arguments;

            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    function safeValue(value, fallback = "") {
        return value !== undefined && value !== null ? value : fallback;
    }

    function updateCsrf(res) {
        if (res.csrfName) csrfName = res.csrfName;
        if (res.csrfHash) csrfHash = res.csrfHash;
    }

    /* =========================================================
       Product Ratings
    ========================================================= */

    function renderStars(rating, count = 0) {

        let html = '<div class="rating-stars">';

        for (let i = 1; i <= 5; i++) {

            if (rating >= i) {
                html += '<i class="fa fa-star text-warning"></i>';

            } else if (rating >= i - 0.5) {
                html += '<i class="fa fa-star-half-o text-warning"></i>';

            } else {
                html += '<i class="fa fa-star-o text-muted"></i>';
            }
        }

        if (count > 0) {
            html += `<small class="text-muted ms-1">(${count})</small>`;
        }

        html += '</div>';

        return html;
    }

    function initRatings() {

        $('[data-rating]').each(function () {

            const rating = parseFloat($(this).data('rating')) || 0;
            const count = parseInt($(this).data('count')) || 0;

            if (!$(this).find('.fa-star').length) {
                $(this).html(renderStars(rating, count));
            }
        });
    }

    /* =========================================================
       Filters
    ========================================================= */

    function buildFilterUrl() {

        const params = new URLSearchParams();

        // Category
        const categories = [];

        $('.category-filter-input:checked').each(function () {
            categories.push($(this).data('value'));
        });

        if (categories.length) {
            params.set('category', categories.join('|'));
        }

        // Brands
        const brands = [];

        $('.brand-filter-input:checked').each(function () {
            brands.push($(this).data('value'));
        });

        if (brands.length) {
            params.set('brand', brands.join('|'));
        }

        // Price
        const minPrice = $('#price-min-input').val();
        const maxPrice = $('#price-max-input').val();

        if (minPrice) params.set('min_price', minPrice);
        if (maxPrice) params.set('max_price', maxPrice);

        // Sort
        const sort = $('#product_sort_by').val();

        if (sort) {
            params.set('sort', sort);
        }

        return window.location.pathname + '?' + params.toString();
    }

    $(document).on('click', '.product_filter_btn', function (e) {

        e.preventDefault();

        window.location.href = buildFilterUrl();
    });

    $(document).on('click', '.clear-filters-btn', function (e) {

        e.preventDefault();

        window.location.href = window.location.pathname;
    });

    $(document).on('change', '#product_sort_by', function () {

        const sort = $(this).val();

        const url = new URL(window.location.href);

        url.searchParams.set('sort', sort);

        window.location.href = url.toString();
    });

    /* =========================================================
       Pincode Check
    ========================================================= */

    $(document).on(
        'input',
        '#pincode_text_input',
        debounce(function () {

            const pincode = $(this).val();

            if (pincode.length < 6) return;

            $.ajax({
                type: 'POST',
                url: base_url + 'cart/check-pincode',
                data: {
                    pincode: pincode,
                    [csrfName]: csrfHash
                },
                dataType: 'json',

                success: function (res) {

                    updateCsrf(res);

                    const cls = res.error
                        ? 'text-danger'
                        : 'text-success';

                    const icon = res.error
                        ? 'fa-times'
                        : 'fa-check';

                    $('.pincode-result').html(`
                        <span class="${cls}">
                            <i class="fa ${icon}"></i>
                            ${res.message}
                        </span>
                    `);
                }
            });

        }, 500)
    );

    /* =========================================================
       Promo Code
    ========================================================= */

    $(document).on('click', '#apply-promo-btn', function (e) {

        e.preventDefault();

        const promoCode = $('#promocode_input').val().trim();

        if (!promoCode) {

            Toast.fire({
                icon: 'warning',
                title: 'Please enter promo code'
            });

            return;
        }

        const $btn = $(this);

        $btn.prop('disabled', true).text('Applying...');

        $.ajax({
            type: 'POST',
            url: base_url + 'cart/apply-promo-code',

            data: {
                promo_code: promoCode,
                [csrfName]: csrfHash
            },

            dataType: 'json',

            success: function (res) {

                updateCsrf(res);

                $btn.prop('disabled', false).text('Apply');

                if (!res.error) {

                    Toast.fire({
                        icon: 'success',
                        title: res.message
                    });

                    $('.promo-discount-display').text(
                        `- ₹${res.discount}`
                    );

                } else {

                    Toast.fire({
                        icon: 'error',
                        title: res.message
                    });
                }
            },

            error: function () {

                $btn.prop('disabled', false).text('Apply');

                Toast.fire({
                    icon: 'error',
                    title: 'Something went wrong'
                });
            }
        });
    });

    /* =========================================================
       Banner Redirects
    ========================================================= */

    $(document).on(
        'click',
        '.banner-item, .slider-banner',
        function (e) {

            const redirectUrl =
                $(this).data('href') ||
                $(this).data('url');

            if (redirectUrl) {

                e.preventDefault();

                window.location.href = redirectUrl;
            }
        }
    );

    /* =========================================================
       Profile Photo Upload
    ========================================================= */

    $(document).on(
        'click',
        '#profile-photo-upload-btn',
        function () {

            $('#profile-photo-upload-input').trigger('click');
        }
    );

    $(document).on(
        'change',
        '#profile-photo-upload-input',
        function () {

            const file = this.files[0];

            if (!file) return;

            const formData = new FormData();

            formData.append('profile_image', file);
            formData.append(csrfName, csrfHash);

            $.ajax({
                type: 'POST',
                url: base_url + 'my-account/update-profile-image',

                data: formData,

                processData: false,
                contentType: false,
                dataType: 'json',

                success: function (res) {

                    updateCsrf(res);

                    if (!res.error) {

                        Toast.fire({
                            icon: 'success',
                            title: 'Profile updated'
                        });

                        if (res.image_url) {

                            $('.profile-photo-img').attr(
                                'src',
                                res.image_url
                            );
                        }

                    } else {

                        Toast.fire({
                            icon: 'error',
                            title: res.message
                        });
                    }
                }
            });
        }
    );

    /* =========================================================
       Initialize
    ========================================================= */

    $(document).ready(function () {

        initRatings();

        console.log('CRETZO fixes loaded successfully');
    });

    $(document).ajaxComplete(function () {

        initRatings();
    });

})(jQuery);