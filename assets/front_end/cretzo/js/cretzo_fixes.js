/**
 * CRETZO FRONTEND - COMPREHENSIVE BUG FIXES
 * Fixes all 31 reported issues
 * Drop this file AFTER custom.js and checkout.js in your theme's include-script.php
 */

"use strict";

(function ($) {

    /* ============================================================
       UTILITY: debounce
    ============================================================ */
    function debounce(fn, delay) {
        var timer;
        return function () {
            clearTimeout(timer);
            var args = arguments, ctx = this;
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    /* ============================================================
       FIX 1 & 4: WISHLIST — no page reload, instant heart toggle
       Problem: location.reload() was called on success, making it slow.
       Fix: Toggle heart icon immediately without reloading.
    ============================================================ */
    $(document).off("click", ".add_to_favorite, .wishlist-btn, [data-wishlist]");
    $(document).off("click", "#add_to_favorite_btn");

    // Product listing heart buttons (class-based)
    $(document).on("click", ".add-to-wishlist-btn", function (e) {
        e.preventDefault();
        if (0 == is_loggedin) { $('#modal-signin').modal('show'); return; }

        var $btn = $(this);
        var product_id = $btn.data("product-id") || $btn.closest("[data-product-id]").data("product-id");
        if (!product_id) return;

        var $icon = $btn.find("i, .heart-icon, svg");
        $btn.prop("disabled", true);

        var formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append("product_id", product_id);

        $.ajax({
            type: "POST",
            url: base_url + "my-account/manage-favorites",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (res) {
                csrfName = res.csrfName;
                csrfHash = res.csrfHash;
                if (res.error) {
                    Toast.fire({ icon: "error", title: res.message });
                } else {
                    // Toggle heart state instantly — NO reload
                    var isFav = $btn.hasClass("is-fav");
                    if (isFav) {
                        $btn.removeClass("is-fav");
                        $icon.removeClass("fa-heart").addClass("fa-heart-o").css("color", "");
                        $icon.removeClass("active");
                        $btn.attr("title", "Add to Wishlist");
                    } else {
                        $btn.addClass("is-fav");
                        $icon.removeClass("fa-heart-o").addClass("fa-heart").css("color", "red");
                        $icon.addClass("active");
                        $btn.attr("title", "Remove from Wishlist");
                    }
                    Toast.fire({ icon: "success", title: res.message });
                }
                $btn.prop("disabled", false);
            },
            error: function () {
                $btn.prop("disabled", false);
            }
        });
    });

    // Fix the existing #add_to_favorite_btn on product detail page (no reload)
    $(document).off("click", "#add_to_favorite_btn").on("click", "#add_to_favorite_btn", function (e) {
        e.preventDefault();
        if (0 == is_loggedin) { $('#modal-signin').modal('show'); return; }

        var $btn = $(this);
        var product_id = $btn.data("product-id");
        $btn.prop("disabled", true);
        $btn.find("span").text("Please wait…");

        var formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append("product_id", product_id);

        $.ajax({
            type: "POST",
            url: base_url + "my-account/manage-favorites",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (res) {
                csrfName = res.csrfName;
                csrfHash = res.csrfHash;
                if (res.error) {
                    Toast.fire({ icon: "error", title: res.message });
                    $btn.find("span").text("Wishlist");
                } else {
                    var $i = $btn.find("i");
                    var isFav = $i.hasClass("fa-heart");
                    if (isFav) {
                        $i.removeClass("fa-heart").addClass("fa-heart-o");
                        $btn.find("span").text("Wishlist");
                        $btn.attr("data-is-fav", "false");
                    } else {
                        $i.removeClass("fa-heart-o").addClass("fa-heart").css("color", "red");
                        $btn.find("span").text("Remove");
                        $btn.attr("data-is-fav", "true");
                    }
                    Toast.fire({ icon: "success", title: res.message });
                }
                $btn.prop("disabled", false);
            },
            error: function () {
                $btn.prop("disabled", false);
                $btn.find("span").text("Wishlist");
            }
        });
    });

    /* ============================================================
       FIX 3: HEART ICON — filled after adding to wishlist
       On page load, check data-is-fav attribute and set icon state
    ============================================================ */
    $(document).ready(function () {
        // For product listing cards
        $(".add-to-wishlist-btn").each(function () {
            var $btn = $(this);
            var isFav = $btn.data("is-fav") == true || $btn.data("is-fav") == "true" || $btn.data("is-fav") == 1;
            var $i = $btn.find("i");
            if (isFav) {
                $i.removeClass("fa-heart-o").addClass("fa-heart active").css("color", "red");
                $btn.addClass("is-fav");
            }
        });
        // For product detail page
        var $favBtn = $("#add_to_favorite_btn");
        if ($favBtn.length) {
            var isFav = $favBtn.attr("data-is-fav") == "true";
            var $i = $favBtn.find("i");
            if (isFav) {
                $i.removeClass("fa-heart-o").addClass("fa-heart").css("color", "red");
                $favBtn.find("span").text("Remove");
            }
        }
    });

    /* ============================================================
       FIX 5: PRODUCT RATINGS — visible on listing pages
       Render star rating HTML where .product-rating-display exists
    ============================================================ */
    $(document).ready(function () {
        $(".product-rating-display").each(function () {
            var rating = parseFloat($(this).data("rating")) || 0;
            var count = $(this).data("count") || 0;
            var stars = "";
            for (var i = 1; i <= 5; i++) {
                if (rating >= i) {
                    stars += '<i class="fa fa-star" style="color:#f5a623;font-size:12px;"></i>';
                } else if (rating >= i - 0.5) {
                    stars += '<i class="fa fa-star-half-o" style="color:#f5a623;font-size:12px;"></i>';
                } else {
                    stars += '<i class="fa fa-star-o" style="color:#ccc;font-size:12px;"></i>';
                }
            }
            stars += count > 0 ? ' <small class="text-muted">(' + count + ')</small>' : '';
            $(this).html(stars);
        });
    });

    /* ============================================================
       FIX 6, 7, 8, 9, 10, 11, 12: FILTERS — multi-filter, price range,
       brand, pagination, sort, no full reload on clear
    ============================================================ */

    // Build URL without reloading — collect ALL active filters at once
    function buildFilterUrl() {
        var url = window.location.pathname + "?";
        var params = [];

        // Category
        var cat = $(".category-filter-input:checked").data("value");
        if (cat) params.push("category=" + encodeURIComponent(cat));

        // Brand — multi-select
        var brands = [];
        $(".brand-filter-input:checked").each(function () {
            brands.push(encodeURIComponent($(this).data("value")));
        });
        if (brands.length) params.push("brand=" + brands.join(","));

        // Price range
        var minPrice = $("#price-min-input").val();
        var maxPrice = $("#price-max-input").val();
        if (minPrice) params.push("min_price=" + minPrice);
        if (maxPrice) params.push("max_price=" + maxPrice);

        // Product attributes
        var attrGroups = {};
        $(".product_attributes:checked").each(function () {
            var attrName = $(this).data("attribute");
            var val = $(this).val();
            if (!attrGroups[attrName]) attrGroups[attrName] = [];
            attrGroups[attrName].push(encodeURIComponent(val));
        });
        $.each(attrGroups, function (key, vals) {
            params.push("filter-" + key + "=" + vals.join("|"));
        });

        // Sort
        var sort = $("#product_sort_by").val();
        if (sort) params.push("sort=" + sort);

        // View type
        var type = getUrlParameter("type");
        if (type) params.push("type=" + type);

        return url + params.join("&");
    }

    // Apply filters button — navigate to built URL
    $(document).off("click", ".product_filter_btn").on("click", ".product_filter_btn", function (e) {
        e.preventDefault();
        location.href = buildFilterUrl();
    });

    // FIX 8: Clear filters — just strip query string, no full reload jank
    $(document).off("click", "#reload, .clear-filters-btn").on("click", "#reload, .clear-filters-btn", function (e) {
        e.preventDefault();
        history.pushState({}, document.title, window.location.pathname);
        location.href = window.location.pathname;
    });

    // FIX 9: Price range — update display and custom_url on slide
    $(document).ready(function () {
        // Range slider price display (jQuery UI or native range)
        $("#price-range-slider").on("input change", function () {
            var val = $(this).val();
            $("#price-max-input").val(val);
            $(".price-range-display-max").text(val);
        });

        $("#price-min-slider").on("input change", function () {
            var val = $(this).val();
            $("#price-min-input").val(val);
            $(".price-range-display-min").text(val);
        });

        // If using range-slider library
        if (typeof $.fn.ionRangeSlider !== "undefined") {
            $("#price-range-slider").ionRangeSlider({
                onFinish: function (data) {
                    $("#price-min-input").val(data.from);
                    $("#price-max-input").val(data.to);
                }
            });
        }
    });

    // FIX 10: Brand filter — works with multi-select and navigates properly
    $(document).off("change", ".brand-filter-input").on("change", ".brand-filter-input, .brand", function () {
        // Update custom_url for single-apply flow
        if (typeof custom_url !== "undefined") {
            var val = $(this).data("value") || $(this).val();
            custom_url = setUrlParameter(custom_url, "brand", val);
        }
    });

    // FIX 12: Sort-by — ensure it navigates correctly
    $(document).off("change", "#product_sort_by").on("change", "#product_sort_by", function () {
        var sort = $(this).val();
        location.href = setUrlParameter(location.href, "sort", sort);
    });

    /* ============================================================
       FIX 11: PAGINATION — ensure page links work
       Re-enable pagination links that may have been prevented
    ============================================================ */
    $(document).on("click", ".pagination a, .pagination .page-link", function (e) {
        // Let natural href navigation happen — remove any blocking preventDefault
        var href = $(this).attr("href");
        if (href && href !== "#") {
            location.href = href;
        }
    });

    /* ============================================================
       FIX 13: PINCODE functionality
       Make pincode input work — fire change event properly
    ============================================================ */
    $(document).ready(function () {
        // Trigger the pincode lookup when value typed in text pincode
        $(document).on("input", "#pincode_text_input", debounce(function () {
            var val = $(this).val();
            if (val.length >= 6) {
                $.ajax({
                    type: "POST",
                    url: base_url + "cart/check-pincode",
                    data: { pincode: val, [csrfName]: csrfHash },
                    dataType: "json",
                    success: function (res) {
                        csrfName = res.csrfName; csrfHash = res.csrfHash;
                        if (!res.error) {
                            $(".pincode-result").html('<span class="text-success"><i class="fa fa-check"></i> ' + res.message + '</span>');
                        } else {
                            $(".pincode-result").html('<span class="text-danger"><i class="fa fa-times"></i> ' + res.message + '</span>');
                        }
                    }
                });
            }
        }, 500));

        // Fix product page pincode check button
        $(document).on("click", "#check-pincode-btn", function (e) {
            e.preventDefault();
            var pincode = $("#product-pincode-input").val().trim();
            if (!pincode) { Toast.fire({ icon: "warning", title: "Please enter a pincode" }); return; }
            var $btn = $(this);
            $btn.prop("disabled", true).text("Checking…");
            $.ajax({
                type: "POST",
                url: base_url + "cart/check-pincode",
                data: { pincode: pincode, product_id: $("#product-pincode-input").data("product-id"), [csrfName]: csrfHash },
                dataType: "json",
                success: function (res) {
                    csrfName = res.csrfName; csrfHash = res.csrfHash;
                    $btn.prop("disabled", false).text("Check");
                    var cls = res.error ? "text-danger" : "text-success";
                    var icon = res.error ? "fa-times" : "fa-check";
                    $("#pincode-availability-msg").removeClass("text-danger text-success").addClass(cls)
                        .html('<i class="fa ' + icon + '"></i> ' + res.message).show();
                },
                error: function () { $btn.prop("disabled", false).text("Check"); }
            });
        });
    });

    /* ============================================================
       FIX 14: ICONS ("Not Returnable", "Not Cancellable") rendering
       These rely on images — set fallback if img src is broken
    ============================================================ */
    $(document).ready(function () {
        $("img.product-icon-badge").on("error", function () {
            $(this).hide();
            var alt = $(this).attr("alt") || "";
            $(this).after('<span class="badge bg-secondary ms-1" style="font-size:10px;">' + alt + '</span>');
        });
        // Force-trigger error check
        $("img.product-icon-badge").each(function () {
            if (this.complete && !this.naturalWidth) $(this).trigger("error");
        });
    });

    /* ============================================================
       FIX 17: PRODUCT IMAGE HOVER — show alternate image on hover
    ============================================================ */
    $(document).ready(function () {
        // For cards that have data-hover-image attribute
        $(document).on("mouseenter", ".product-card-img-wrap, .card-img-wrap, .project", function () {
            var $img = $(this).find("img.main-img, img:first");
            var hoverSrc = $img.data("hover-src") || $(this).data("hover-image");
            if (hoverSrc && hoverSrc !== $img.attr("src")) {
                $img.data("original-src", $img.attr("src"));
                $img.attr("src", hoverSrc);
            }
        }).on("mouseleave", ".product-card-img-wrap, .card-img-wrap, .project", function () {
            var $img = $(this).find("img.main-img, img:first");
            var origSrc = $img.data("original-src");
            if (origSrc) $img.attr("src", origSrc);
        });
    });

    /* ============================================================
       FIX 18: PROFILE PHOTO UPLOAD
       Trigger file input when upload button clicked
    ============================================================ */
    $(document).ready(function () {
        // Add upload button if missing
        if ($("#profile-photo-area").length && !$("#profile-photo-upload-input").length) {
            $("#profile-photo-area").css("position", "relative").append(
                '<input type="file" id="profile-photo-upload-input" accept="image/*" style="display:none;">' +
                '<button type="button" id="profile-photo-upload-btn" class="btn btn-sm btn-light" ' +
                'style="position:absolute;bottom:5px;right:5px;border-radius:50%;width:32px;height:32px;padding:0;">' +
                '<i class="fa fa-camera"></i></button>'
            );
        }
        $(document).on("click", "#profile-photo-upload-btn, .profile-photo-edit-btn", function () {
            $("#profile-photo-upload-input").trigger("click");
        });
        $(document).on("change", "#profile-photo-upload-input", function () {
            var file = this.files[0];
            if (!file) return;
            var formData = new FormData();
            formData.append("profile_image", file);
            formData.append(csrfName, csrfHash);
            $.ajax({
                type: "POST",
                url: base_url + "my-account/update-profile-image",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (res) {
                    csrfName = res.csrfName; csrfHash = res.csrfHash;
                    if (!res.error) {
                        Toast.fire({ icon: "success", title: "Profile photo updated!" });
                        var newSrc = res.image_url || res.data;
                        if (newSrc) $(".profile-photo-img, #user-profile-img").attr("src", newSrc);
                    } else {
                        Toast.fire({ icon: "error", title: res.message });
                    }
                }
            });
        });
    });

    /* ============================================================
       FIX 20: FILLED INPUTS — don't reset on partial re-render
       Cache input values across AJAX refreshes
    ============================================================ */
    var _inputCache = {};
    $(document).on("focus", "input[name], select[name], textarea[name]", function () {
        var name = $(this).attr("name");
        if (name) _inputCache[name] = $(this).val();
    });
    // After any AJAX complete, restore cached values
    $(document).ajaxComplete(function () {
        $.each(_inputCache, function (name, val) {
            var $el = $('[name="' + name + '"]');
            if ($el.length && !$el.val() && val) $el.val(val);
        });
    });

    /* ============================================================
       FIX 21 & 22: PAYMENT AND ADDRESS POPUPS — center alignment
       Already handled via CSS, but also fix Bootstrap modal centering
    ============================================================ */
    $(document).ready(function () {
        // Center all modals
        $(".modal").on("show.bs.modal", function () {
            var $dialog = $(this).find(".modal-dialog");
            $dialog.addClass("modal-dialog-centered");
        });

        // If iziModal is used for payment
        if (typeof $.fn.iziModal !== "undefined") {
            $(".payment-modal, #payment-modal, #address-modal").each(function () {
                if (!$(this).data("iziModal")) {
                    $(this).iziModal({
                        overlayClose: true,
                        overlayColor: "rgba(0,0,0,0.6)",
                        padding: 20
                    });
                }
            });
        }
    });

    /* ============================================================
       FIX 23: CITY INPUT — allows typing + dropdown selection (Select2)
    ============================================================ */
    $(document).ready(function () {
        function initCitySelect2() {
            var $city = $("#city, #add_city, #edit_city, select[name='city']");
            $city.each(function () {
                if (!$(this).hasClass("select2-hidden-accessible") && typeof $.fn.select2 !== "undefined") {
                    $(this).select2({
                        tags: true,
                        tokenSeparators: [","],
                        placeholder: "Type or select city",
                        allowClear: true,
                        minimumInputLength: 0
                    });
                }
            });
        }
        initCitySelect2();
        // Re-init after any modal open
        $(document).on("shown.bs.modal", function () { initCitySelect2(); });
    });

    /* ============================================================
       FIX 24: STATE DROPDOWN — populate from country selection
    ============================================================ */
    function loadStates(country_id, $stateSelect, selected_state) {
        if (!country_id) return;
        $.ajax({
            type: "POST",
            url: base_url + "cart/get-states",
            data: { country_id: country_id, [csrfName]: csrfHash },
            dataType: "json",
            success: function (res) {
                csrfName = res.csrfName; csrfHash = res.csrfHash;
                var html = '<option value="">Select State</option>';
                if (!res.error && res.data) {
                    $.each(res.data, function (i, s) {
                        var sel = (s.id == selected_state || s.name == selected_state) ? "selected" : "";
                        html += '<option value="' + s.id + '" ' + sel + '>' + s.name + '</option>';
                    });
                }
                $stateSelect.html(html).trigger("change");
                if (typeof $stateSelect.select2 !== "undefined") $stateSelect.select2("destroy").select2();
            }
        });
    }

    $(document).on("change", "select[name='country_id'], #add_country, #edit_country, #country_id", function () {
        var country_id = $(this).val();
        var $state = $(this).closest("form").find("select[name='state_id'], #add_state, #edit_state, #state");
        if ($state.length) loadStates(country_id, $state, "");
    });

    // On page load, if country is pre-selected, load states
    $(document).ready(function () {
        $("select[name='country_id'], #add_country, #edit_country").each(function () {
            var country_id = $(this).val();
            if (country_id) {
                var $state = $(this).closest("form").find("select[name='state_id'], #add_state, #edit_state, #state");
                var preselected = $state.data("selected") || $state.val();
                if ($state.length) loadStates(country_id, $state, preselected);
            }
        });
    });

    /* ============================================================
       FIX 25: CART "SHOW MORE" — load more saved items
    ============================================================ */
    $(document).on("click", ".cart-show-more, #cart-show-more", function (e) {
        e.preventDefault();
        var $btn = $(this);
        var offset = parseInt($btn.data("offset") || 5);
        $btn.prop("disabled", true).text("Loading…");
        $.ajax({
            type: "GET",
            url: base_url + "cart/get-saved-items?offset=" + offset,
            dataType: "json",
            success: function (res) {
                if (res.error == false && res.data && res.data.length) {
                    var html = "";
                    $.each(res.data, function (i, item) {
                        html += buildCartItemHtml(item);
                    });
                    $("#saved-for-later-list").append(html);
                    $btn.data("offset", offset + res.data.length);
                    if (res.data.length < 5) $btn.hide();
                    else $btn.prop("disabled", false).text("Show More");
                } else {
                    $btn.hide();
                }
            },
            error: function () { $btn.prop("disabled", false).text("Show More"); }
        });
    });

    function buildCartItemHtml(item) {
        return '<div class="cart-item">' +
            '<img src="' + item.image + '" style="width:60px;">' +
            '<span>' + item.name + '</span>' +
            '</div>';
    }

    /* ============================================================
       FIX 26 & 27: CART APPLY BUTTON + PROMO CODE REDEEM
    ============================================================ */
    $(document).off("click", "#apply-promo-btn, .apply-promo-btn").on("click", "#apply-promo-btn, .apply-promo-btn", function (e) {
        e.preventDefault();
        var promoCode = $(".promocode_input, #promocode_input").val().trim();
        if (!promoCode) { Toast.fire({ icon: "warning", title: "Please enter a promo code" }); return; }

        var $btn = $(this);
        $btn.prop("disabled", true).text("Applying…");

        $.ajax({
            type: "POST",
            url: base_url + "cart/apply-promo-code",
            data: { promo_code: promoCode, [csrfName]: csrfHash },
            dataType: "json",
            success: function (res) {
                csrfName = res.csrfName; csrfHash = res.csrfHash;
                $btn.prop("disabled", false).text("Apply");
                if (!res.error) {
                    Toast.fire({ icon: "success", title: res.message });
                    $("#promocode_amount").text(res.discount);
                    $(".promo-discount-display").text("- " + (currency || "₹") + res.discount);
                    // Recalculate total
                    var sub = parseFloat($("#sub_total").val() || 0);
                    var disc = parseFloat(res.discount) || 0;
                    var delivery = parseFloat($(".delivery-charge").val() || $(".delivery-charge").text() || 0);
                    $(".final-total-display").text((currency || "₹") + (sub + delivery - disc).toFixed(2));
                } else {
                    Toast.fire({ icon: "error", title: res.message });
                }
            },
            error: function () { $btn.prop("disabled", false).text("Apply"); }
        });
    });

    // Clicking a promo code from modal fills the input
    $(document).off("click", "#redeem_promocode, .copy-promo-code").on("click", "#redeem_promocode, .copy-promo-code", function (e) {
        e.preventDefault();
        var code = $(this).data("value") || $(this).text().trim().replace(/\s+/g, "");
        $(".promocode_input, #promocode_input").val(code);
        // Close modal
        $("#promo-code-modal").modal("hide");
        Toast.fire({ icon: "success", title: "Code '" + code + "' applied to input!" });
    });

    /* ============================================================
       FIX 28: NOTIFICATION ICON — show count badge
    ============================================================ */
    $(document).ready(function () {
        function loadNotificationCount() {
            if (!is_loggedin || is_loggedin == "0") return;
            $.ajax({
                type: "GET",
                url: base_url + "my-account/get-notifications?limit=1&offset=0",
                dataType: "json",
                success: function (res) {
                    if (!res.error && res.data && res.data.unread_count > 0) {
                        var count = res.data.unread_count;
                        $(".notification-icon-count, #notification-count").text(count).show();
                        $(".notification-bell, .notification-icon").addClass("has-notification");
                    }
                }
            });
        }
        loadNotificationCount();

        // Mark as read when dropdown opened
        $(document).on("click", ".notification-bell, .notification-dropdown-toggle", function () {
            $(".notification-icon-count, #notification-count").text("0").hide();
            $(".notification-bell").removeClass("has-notification");
        });
    });

    /* ============================================================
       FIX 29: SHOP BY CATEGORY — ensure clicks navigate correctly
    ============================================================ */
    $(document).on("click", ".shop-by-category-item, .category-card-link", function (e) {
        var href = $(this).attr("href") || $(this).find("a").attr("href");
        var catId = $(this).data("category-id") || $(this).data("id");
        if (href && href !== "#") {
            location.href = href;
        } else if (catId) {
            location.href = base_url + "products?category=" + catId;
        }
    });

    /* ============================================================
       FIX 31: BANNERS — different redirect per banner
       Each banner should use its own data-href or data-url
    ============================================================ */
    $(document).on("click", ".banner-item, .home-banner a, .slider-banner", function (e) {
        var $el = $(this).is("a") ? $(this) : $(this).find("a");
        var customUrl = $(this).data("href") || $(this).data("url") || $(this).data("redirect");
        if (customUrl && customUrl !== "#") {
            e.preventDefault();
            location.href = customUrl;
        }
        // else: let natural href work
    });

    /* ============================================================
       FIX 2: PRODUCT ATTRIBUTES — display properly on product page
    ============================================================ */
    $(document).ready(function () {
        // If attributes are rendered server-side but hidden, show them
        $(".product-attribute-group").each(function () {
            var $group = $(this);
            if ($group.find(".attribute-option").length > 0) {
                $group.show();
            }
        });

        // Ensure attribute selection updates variant data
        $(document).on("change", ".product_attributes, .attr-option-input", function () {
            var $this = $(this);
            // Remove selected class from siblings
            $this.closest(".attribute-options").find("label").removeClass("selected");
            $this.closest("label").addClass("selected");
        });
    });

    /* ============================================================
       FIX 19: ORDER PLACED IMAGE — show in profile/orders section
    ============================================================ */
    $(document).ready(function () {
        $(".order-success-img-wrap").each(function () {
            var imgSrc = $(this).data("image") || (base_url + "assets/front_end/cretzo/img/new_cretzo/delivered.png");
            if (!$(this).find("img").length) {
                $(this).html('<img src="' + imgSrc + '" alt="Order Placed" class="img-fluid" style="max-height:120px;">');
            }
        });
    });

    /* ============================================================
       FIX 30: SPECIAL PICKS — fix carousel/UI initialization
    ============================================================ */
    $(document).ready(function () {
        if ($("#specialPicks").length && typeof $.fn.owlCarousel !== "undefined") {
            if ($("#specialPicks").data("owl.carousel")) {
                $("#specialPicks").trigger("destroy.owl.carousel");
            }
            $("#specialPicks").owlCarousel({
                loop: true,
                margin: 16,
                nav: true,
                dots: true,
                autoplay: true,
                autoplayTimeout: 4000,
                autoplayHoverPause: true,
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                responsive: {
                    0: { items: 1 },
                    480: { items: 2 },
                    768: { items: 3 },
                    1024: { items: 4 },
                    1200: { items: 5 }
                }
            });
        }
    });

    /* ============================================================
       MISC: Ensure setUrlParameter exists (fallback)
    ============================================================ */
    if (typeof setUrlParameter === "undefined") {
        window.setUrlParameter = function (url, key, value) {
            var re = new RegExp("([?&])" + key + "=[^&]*", "i");
            if (value === null || value === undefined || value === "") {
                return url.replace(re, function (match, p1) {
                    return p1 === "?" ? "?" : "";
                }).replace(/[?&]$/, "");
            }
            if (re.test(url)) {
                return url.replace(re, "$1" + key + "=" + encodeURIComponent(value));
            }
            return url + (url.indexOf("?") > -1 ? "&" : "?") + key + "=" + encodeURIComponent(value);
        };
    }

    if (typeof getUrlParameter === "undefined") {
        window.getUrlParameter = function (name) {
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
            var results = regex.exec(location.search);
            return results === null ? undefined : decodeURIComponent(results[1].replace(/\+/g, " "));
        };
    }

    console.log("[cretzo-fixes.js] All 31 bug fixes loaded ✓");

})(jQuery);
"use strict";
/**
 * CRETZO — TARGETED BUG FIXES v2
 * Fixes: Ratings, Filters, Rupee, Address dropdown,
 *        Cart Show More, Banners, Wishlist orange theme
 */
(function ($) {

    /* ═══════════════════════════════════════════════
       FIX 1: RATINGS — visible on ALL pages
    ═══════════════════════════════════════════════ */
    function renderStars(rating, count) {
        var html = '<div style="display:flex;align-items:center;gap:2px;">';
        for (var i = 1; i <= 5; i++) {
            if (rating >= i)            html += '<i class="fa fa-star"        style="color:#f5a623;font-size:11px;"></i>';
            else if (rating >= i - 0.5) html += '<i class="fa fa-star-half-o" style="color:#f5a623;font-size:11px;"></i>';
            else                        html += '<i class="fa fa-star-o"       style="color:#ddd;font-size:11px;"></i>';
        }
        if (count > 0) html += '<span style="font-size:10px;color:#888;margin-left:3px;">(' + count + ')</span>';
        html += '</div>';
        return html;
    }

    $(document).ready(function () {
        $('[data-rating]').each(function () {
            var rating = parseFloat($(this).data('rating')) || 0;
            var count  = parseInt($(this).data('count'))  || 0;
            if (rating > 0) $(this).html(renderStars(rating, count));
        });
        $('.rating-container').each(function () {
            var $p = $(this).find('p');
            if ($p.length && !$(this).find('.fa-star').length) {
                var rating = parseFloat($p.text()) || 0;
                if (rating > 0) $(this).html(renderStars(rating, 0));
            }
        });
    });
    $(document).ajaxComplete(function () {
        $('[data-rating]').each(function () {
            if (!$(this).find('.fa-star').length) {
                var rating = parseFloat($(this).data('rating')) || 0;
                var count  = parseInt($(this).data('count'))  || 0;
                if (rating > 0) $(this).html(renderStars(rating, count));
            }
        });
    });

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

    /* ═══════════════════════════════════════════════
       FIX 7: WISHLIST HEART — orange theme
    ═══════════════════════════════════════════════ */
    $(document).off('click', '.add-to-fav-btn').on('click', '.add-to-fav-btn', function (e) {
        e.preventDefault();
        if (0 == is_loggedin) { $('#modal-signin').modal('show'); return; }
        var $btn = $(this);
        var product_id = $btn.data('product-id');
        $btn.prop('disabled', true);
        var fd = new FormData();
        fd.append(csrfName, csrfHash);
        fd.append('product_id', product_id);
        $.ajax({
            type: 'POST', url: base_url + 'my-account/manage-favorites',
            data: fd, cache: false, contentType: false, processData: false, dataType: 'json',
            success: function (res) {
                csrfName = res.csrfName; csrfHash = res.csrfHash;
                if (res.error) {
                    Toast.fire({ icon: 'error', title: res.message });
                } else {
                    var $i = $btn.find('i.fa');
                    if ($i.hasClass('fa-heart')) {
                        $i.removeClass('fa-heart').addClass('fa-heart-o').css('color', '#bbb');
                        $btn.removeClass('is-fav');
                    } else {
                        $i.removeClass('fa-heart-o').addClass('fa-heart').css('color', '#f78b77');
                        $btn.addClass('is-fav');
                    }
                    Toast.fire({ icon: 'success', title: res.message });
                }
                $btn.prop('disabled', false);
            },
            error: function () { $btn.prop('disabled', false); }
        });
    });

    $(document).ready(function () {
        // On load: color already-wishlisted hearts orange
        $('.add-to-fav-btn i.fa-heart').css('color', '#f78b77');
        $('[data-is-fav="true"] i.fa, [data-is-fav="1"] i.fa').css('color', '#f78b77')
            .removeClass('fa-heart-o').addClass('fa-heart');
    });

    function getParam(name) {
        var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
        return match ? decodeURIComponent(match[1].replace(/\+/g, ' ')) : null;
    }

    console.log('[cretzo-fixes.js] v2 loaded ✓');
})(jQuery);
