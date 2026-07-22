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

    /* Price slider min/max sync is now owned by initPriceSlider() in
       cretzo/product-listing.js (the dual-handle slider controller). The old
       delegated handlers here looked inside `.filter-section`, but the slider
       lives in `.silder-container`, so they never matched — removed to avoid
       per-drag-tick no-op work and confusion. */

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
       FIX 7: COMPARE — "coming soon" announcement
       The compare buttons on the product page and in the quick-view popup
       carry `.compare-soon-btn` (NOT `.compare`, so the real compare AJAX in
       custom.js never fires). Show a friendly, on-brand "coming soon" dialog.

       NOTE: we deliberately do NOT use SweetAlert2 here — cretzo-override.css
       globally hides `.swal2-container { display:none !important }` on purpose,
       so a self-contained lightweight modal is used instead.
    ═══════════════════════════════════════════════ */
    function closeComparisonSoon() {
        var $m = $('#cs-soon-modal');
        if (!$m.length) return;
        $m.removeClass('is-open');
        setTimeout(function () { $m.remove(); }, 220);
    }

    $(document).on('click', '.compare-soon-btn', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        $('#cs-soon-modal').remove();

        var html =
            '<div id="cs-soon-modal" class="cs-soon-overlay">' +
                '<div class="cs-soon-card" role="dialog" aria-modal="true" aria-label="Product customization coming soon">' +
                    '<button type="button" class="cs-soon-close" aria-label="Close">&times;</button>' +
                    '<div class="cs-soon">' +
                        '<div class="cs-soon-badge"><i class="uil uil-setting"></i></div>' +
                        '<h2 class="cs-soon-title">Customization Feature</h2>' +
                        '<span class="cs-soon-chip">Coming Soon</span>' +
                        '<p class="cs-soon-text">🚀 Launching Soon!</p>' +
                        '<p class="cs-soon-text">We’re working on bringing you a powerful Product Customization feature to make your experience even better.</p>' +
                        '<p class="cs-soon-text">This feature is currently under development and will be available soon after seller onboarding.</p>' +
                        '<p class="cs-soon-text">Stay tuned — we’re building something exciting for you!</p>' +
                        '<button type="button" class="cs-soon-confirm">Got it</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(html);
        var el = document.getElementById('cs-soon-modal');
        // force reflow so the open transition animates
        void el.offsetWidth;
        $(el).addClass('is-open');
    });

    $(document).on('click', '#cs-soon-modal .cs-soon-close, #cs-soon-modal .cs-soon-confirm', function () {
        closeComparisonSoon();
    });
    // click on the dark backdrop (but not the card) closes it
    $(document).on('click', '#cs-soon-modal', function (e) {
        if (e.target === this) closeComparisonSoon();
    });
    $(document).on('keydown', function (e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && $('#cs-soon-modal').length) closeComparisonSoon();
    });

    /* ═══════════════════════════════════════════════
       FIX 8: CHAT WITH SELLER — "live chat coming soon"
       The "Chat with seller" link on the seller-details page carries
       `.chat-seller-soon-btn`. Intercept it and show an on-brand
       "coming soon" dialog whose primary action sends the user to
       WhatsApp support (falls back to the floating chat widget).
       Reuses the same `.cs-soon-*` styling as the compare dialog.
    ═══════════════════════════════════════════════ */
    function closeChatSoon() {
        var $m = $('#cs-chat-modal');
        if (!$m.length) return;
        $m.removeClass('is-open');
        setTimeout(function () { $m.remove(); }, 220);
    }

    $(document).on('click', '.chat-seller-soon-btn', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        $('#cs-chat-modal').remove();

        var html =
            '<div id="cs-chat-modal" class="cs-soon-overlay">' +
                '<div class="cs-soon-card" role="dialog" aria-modal="true" aria-label="Live chat coming soon">' +
                    '<button type="button" class="cs-soon-close" aria-label="Close">&times;</button>' +
                    '<div class="cs-soon">' +
                        '<div class="cs-soon-badge"><i class="uil uil-comments"></i></div>' +
                        '<h2 class="cs-soon-title">💬 Live Chat</h2>' +
                        '<span class="cs-soon-chip">Coming Soon</span>' +
                        '<p class="cs-soon-text">We’re working on launching our Live Chat Support feature to provide you with a faster and smoother support experience.</p>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(html);
        var el = document.getElementById('cs-chat-modal');
        // force reflow so the open transition animates
        void el.offsetWidth;
        $(el).addClass('is-open');
    });

    // "Continue to WhatsApp": open the WhatsApp link if configured,
    // otherwise open the floating chat widget in the bottom-right corner.
    $(document).on('click', '#cs-chat-modal .cs-chat-whatsapp', function () {
        var waHref = $('.whatsapp-icon a').attr('href');
        closeChatSoon();
        if (waHref) {
            window.open(waHref, '_blank');
        } else {
            $('#chat-button').trigger('click');
        }
    });

    $(document).on('click', '#cs-chat-modal .cs-soon-close', function () {
        closeChatSoon();
    });
    // click on the dark backdrop (but not the card) closes it
    $(document).on('click', '#cs-chat-modal', function (e) {
        if (e.target === this) closeChatSoon();
    });
    $(document).on('keydown', function (e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && $('#cs-chat-modal').length) closeChatSoon();
    });

    /* ═══════════════════════════════════════════════
       FIX 9: MINI-CART REMOVE — item wasn't disappearing from the
       header "Shopping Cart" offcanvas.

       The offcanvas remove/qty handlers live in custom.js and call
       usercartTotal() -> updateCartDetails() in their completion
       callback. updateCartDetails() is ONLY defined in cart.js, which
       is loaded on the cart page alone. On every other page (product
       page, home, etc.) that call throws
       "ReferenceError: updateCartDetails is not defined" the moment a
       row is removed, aborting the callback before the cart UI is
       reconciled. Define a safe global fallback so the mini-cart stays
       consistent everywhere. (On the cart page cart.js's real version
       is already defined, so this no-ops.)
    ═══════════════════════════════════════════════ */
    if (typeof window.updateCartDetails !== 'function') {
        window.updateCartDetails = function () {
            var $sidebar = $('#cart-item-sidebar');
            if (!$sidebar.length) return;

            var count = $sidebar.find('.shopping-cart-item').length;

            // Keep the cart badge in sync (cart count only, not wishlist).
            $('#cart-count').text(count);

            // When the last item is removed, show the empty-cart state.
            if (count === 0 && !$sidebar.find('.mini-cart-empty').length) {
                $sidebar.html(
                    '<h1 class="h4 text-center mini-cart-empty">Your cart is empty</h1>' +
                    '<img src="' + base_url + 'assets/front_end/cretzo/img/new/empty-cart(4).png" alt="Empty Cart" class="mt-16" />'
                );
            }
        };
    }

