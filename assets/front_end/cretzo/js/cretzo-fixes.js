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
