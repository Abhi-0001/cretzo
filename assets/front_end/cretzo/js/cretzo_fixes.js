
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