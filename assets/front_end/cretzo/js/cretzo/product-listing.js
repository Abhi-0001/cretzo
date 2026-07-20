var price_filter_enabled = false;
// paging state for infinite scroll
var currentPage = 1;
var perPage = 20;          // default page size for scroll
var totalRows = 0;
var isLoading = false;
var useAjaxInfiniteScroll = false;
// Controller for the dual-handle price slider (assigned in $(document).ready).
// Exposed at module scope so ajaxProductList() can re-scale the slider bounds
// after a filter change.
var priceSlider = null;

function getCurrentPageFromUrl() {
    var pathParts = window.location.pathname.split('/').filter(Boolean);
    var lastPart = pathParts.length ? pathParts[pathParts.length - 1] : '';
    if (!isNaN(lastPart) && parseInt(lastPart, 10) > 0) {
        return parseInt(lastPart, 10);
    }
    var queryPage = new URLSearchParams(window.location.search).get('page');
    if (!isNaN(queryPage) && parseInt(queryPage, 10) > 0) {
        return parseInt(queryPage, 10);
    }
    return 1;
}

// Function to get base URL without page index
function getBaseURL() {
    var currentURL = window.location.href;
    var baseURL = currentURL.split('?')[0]; // Remove query string
    var baseURLParts = baseURL.split('/'); // Split by '/'
    var lastPart = baseURLParts[baseURLParts.length - 1];
    if (!isNaN(lastPart)) {
        // If last part is a number (page index), remove it
        baseURLParts.pop();
        baseURL = baseURLParts.join('/');
    }
    return baseURL;
}

// Function to update the URL with modified GET parameters
function updateURL() {
    // reset paging when filters change
    currentPage = 1;

    // Initialize an empty array to store the selected category IDs
    var selectedCategories = [];
    // Get all the checked category checkboxes
    $('.filter-section.fs-category .filter-list-item input[type="checkbox"]:checked').each(function() {
        selectedCategories.push($(this).data('value'));
    });

    // Initialize an empty array to store the selected brand slugs
    var selectedBrands = [];
    // Get all the checked brand checkboxes
    $('.filter-section.fs-brand .filter-list-item input[type="checkbox"]:checked').each(function() {
        selectedBrands.push($(this).data('value'));
    });

    // Initialize an empty object to store the selected attribute values
    var selectedAttributes = {};
    // Get all the checked attribute checkboxes
    $('.filter-section.fs-attr .filter-list-item input[type="checkbox"]:checked').each(function() {
        var attributeName = $(this).closest('.filter-section').find('.filter-heading').text().trim().toLowerCase();
        if (!selectedAttributes[attributeName]) {
            selectedAttributes[attributeName] = [];
        }
        selectedAttributes[attributeName].push($(this).data('value'));
    });

    // Construct the updated URL
    var url = getBaseURL();
    var params = [];
    if (selectedCategories.length > 0) {
        params.push('category=' + selectedCategories.join('|'));
    }
    if (selectedBrands.length > 0) {
        params.push('brand=' + selectedBrands.join('|'));
    }
    $.each(selectedAttributes, function(attributeName, attributeValues) {
        params.push('filter-' + attributeName.replace(/\s+/g, '-') + '=' + attributeValues.join('|'));
    });

    if(price_filter_enabled){
        const priceInput = document.querySelectorAll(".price-input input");
        var minPrice = priceInput[0].value;
        var maxPrice = priceInput[1].value;
        params.push('min-price=' + minPrice);
        params.push('max-price=' + maxPrice);
    }

    var urlParams = new URLSearchParams(window.location.search);
    var seller = urlParams.get('seller');
    if(seller){
        params.push('seller=' + seller);
    }

    var currentPerPage = urlParams.get('per-page');
    if (currentPerPage) {
        params.push('per-page=' + currentPerPage);
    }

    // Include sort parameter if a value is set (skip if the option has no 'value' attribute)
    var sortAttr = $('#product_sort_by').find('option:selected').attr('value');
    if (typeof sortAttr !== 'undefined' && sortAttr !== null && sortAttr !== '') {
        params.push('sort=' + sortAttr);
    }

    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    window.history.replaceState({}, '', url);
    ajaxProductList(1, false);
}

$(document).ready(function() {

    // Add event listeners for checkbox change events
    $('.filter-list-item input[type="checkbox"]').change(function() {
        updateURL();
    });

    // Price filter: the dual-handle slider + Min/Max boxes are driven by
    // initPriceSlider() (defined below). It updates the fill bar and value boxes
    // live while dragging, but only triggers a product reload ONCE, on
    // release/commit — so there is no mid-drag flicker and no burst of AJAX calls.
    // (The old debounced 'input' handler fired a full reload every time the drag
    // paused for 450ms, which is what made the grid "continuously change".)
    priceSlider = initPriceSlider();

    // Add event listener for 'Filter Price' button click
    $('#clear-all-filters-btn').click(function() {
        price_filter_enabled = false;
        var url = getBaseURL();
        window.history.replaceState({}, '', url);
        ajaxProductList(1, false);
    });

    /* Set state of price filter and related button */
    var urlParams = new URLSearchParams(window.location.search); //get all parameters
    var min_price_param = urlParams.get('min-price');
    var max_price_param = urlParams.get('max-price');
    price_filter_enabled = min_price_param || max_price_param;
    $('#clear-filter-price-btn').attr("disabled", !price_filter_enabled);

    /* Get seller details if seller param available */
    /* var seller = urlParams.get('seller');
    if(seller){
        getSellerDetails(seller);
    } */

    // Add event listener for tapping on .filter-container
    $('.filter-container').on('click', function() {
        // Check if screen size is less than or equal to 1000px
        if ($(window).width() <= 1000) {
            // Add active class to filter-container
            $(this).addClass('active');

            // Show background overlay
            // $('#bg-overlay').css('display', 'block');
            $('#bg-overlay').addClass('active');
        }
    });

    // Sort by change - ensure no other handlers cause a full page reload.
    (function() {
        var $sort = $('#product_sort_by');
        if (!$sort.length) return;

        // Preserve current selection
        var currentVal = $sort.val();

        // Replace the element with a clone without event listeners (removes other handlers)
        var $clone = $sort.clone(false);
        $sort.replaceWith($clone);

        var $newSort = $('#product_sort_by');
        $newSort.val(currentVal);

        // Bind our AJAX handler only
        $newSort.on('change.cretzo', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            updateURL();
        });
    })();

    // Add event listener for tapping on #bg-overlay
    $('#bg-overlay').on('click', function() {
        // Hide background overlay
        // $(this).css('display', 'none');
        $(this).removeClass('active');

        // Remove active class from filter-container
        $('.filter-container').removeClass('active');
    });

    // Override wishlist handler with login check, toast messages, and proper UI management
    $(document).off('click', '#add_to_favorite_btn');
    $(document).on('click', '#add_to_favorite_btn', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $button = $(this);
        var productId = $button.data('product-id');

        // Check if user is logged in using the hidden input
        var isLoggedIn = parseInt($('#is_loggedin').val()) === 1;
        
        if (!isLoggedIn) {
            // Show login modal/popup
            var loginModal = $('#modal-signin') || $('#login_modal') || document.querySelector('[data-bs-toggle="modal"][href="#login"]');
            if (loginModal && loginModal.length > 0) {
                $(loginModal).modal('show');
            } else if (loginModal = document.getElementById('modal-signin')) {
                var modal = new (typeof bootstrap !== 'undefined' ? bootstrap.Modal : Object)(loginModal);
                modal.show();
            }
            showToast('Please login to add items to wishlist', 'info');
            return;
        }

        var $icon = $button.find('i.fa').first();
        var $label = $button.find('span');
        var addLabel = 'Wishlist';        // label when the product is NOT wishlisted
        var removeLabel = 'Wishlisted';   // label when the product IS wishlisted
        var isFavoriteBefore = $button.attr('data-is-fav') === 'true' || $icon.hasClass('fa-heart');
        var isNowFavorite = !isFavoriteBefore;
        var originalText = $label.text().trim() || (isFavoriteBefore ? removeLabel : addLabel);

        var formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append('product_id', productId);

        $button.attr('disabled', true);
        if ($label.length) {
            $label.text('Please wait...');
        }

        $.ajax({
            type: 'POST',
            url: base_url + 'my-account/manage-favorites',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                csrfName = response.csrfName;
                csrfHash = response.csrfHash;

                if (response.error == 1 || response.error === true) {
                    $button.attr('disabled', false);
                    if ($label.length) {
                        $label.text(originalText);
                    }
                    showToast(response.message, 'error');
                    return;
                }

                // Toggle heart icon classes and color based on current state
                if (isNowFavorite) {
                    // Clear any inline color so the theme color (CSS) drives the heart consistently
                    $icon.removeClass('fa-heart-o').addClass('fa-heart').css('color', '');
                    $button.addClass('is-fav').attr('data-is-fav', 'true');
                    if ($label.length) {
                        $label.text(removeLabel);
                    }
                    showToast('✓ Added to Wishlist', 'success');
                } else {
                    $icon.removeClass('fa-heart').addClass('fa-heart-o').css('color', '');
                    $button.removeClass('is-fav').attr('data-is-fav', 'false');
                    if ($label.length) {
                        $label.text(addLabel);
                    }
                    showToast('✓ Removed from Wishlist', 'success');
                }

                $button.attr('disabled', false);

                // Determine new wishlist count from server if available, else apply delta
                var serverCount = null;
                if (typeof response.favorites_count !== 'undefined') {
                    serverCount = response.favorites_count;
                } else if (typeof response.favorite_count !== 'undefined') {
                    serverCount = response.favorite_count;
                } else if (response.data && typeof response.data.favorites_count !== 'undefined') {
                    serverCount = response.data.favorites_count;
                } else if (response.data && typeof response.data.favorite_count !== 'undefined') {
                    serverCount = response.data.favorite_count;
                }

                if (serverCount !== null) {
                    setWishlistCount(parseInt(serverCount, 10));
                } else {
                    updateWishlistCount(isNowFavorite ? 1 : -1);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $button.attr('disabled', false);
                if ($label.length) {
                    $label.text(originalText);
                }
                showToast('Unable to update wishlist. Please try again.', 'error');
            }
        });
    });

    // Helper function to show toast messages
    function showToast(message, type) {
        var icon = type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info');

        if (typeof Toast !== 'undefined' && typeof Toast.fire === 'function') {
            Toast.fire({
                icon: icon,
                title: message
            });
            return;
        }

        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: icon,
                title: message
            });
            return;
        }

        if (typeof toastr !== 'undefined') {
            toastr[type](message);
            return;
        }

        // Fallback: inject a simple top-right toast container and show a minimal message
        var container = document.getElementById('custom-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'custom-toast-container';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '99999';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.gap = '10px';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.style.minWidth = '220px';
        toast.style.padding = '10px 14px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0 5px 14px rgba(0,0,0,0.15)';
        toast.style.color = '#fff';
        toast.style.fontSize = '14px';
        toast.style.lineHeight = '1.4';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        toast.style.transform = 'translateX(20px)';

        if (type === 'success') {
            toast.style.background = '#28a745';
        } else if (type === 'error') {
            toast.style.background = '#dc3545';
        } else {
            toast.style.background = '#333';
        }

        toast.textContent = message;
        container.appendChild(toast);

        requestAnimationFrame(function() {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        });

        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(function() {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                if (container.childNodes.length === 0 && container.parentNode) {
                    container.parentNode.removeChild(container);
                }
            }, 250);
        }, 3200);
    }

    // Helper function to update wishlist count
    function updateWishlistCount(delta) {
        var countElement = $('[data-wishlist-count], #wishlist-count');
        if (countElement.length > 0) {
            countElement.each(function() {
                var currentCount = parseInt($(this).text()) || 0;
                var updated = currentCount + (parseInt(delta, 10) || 0);
                if (updated < 0) {
                    updated = 0;
                }
                $(this).text(updated);
            });
        }
    }

    function setWishlistCount(value) {
        var countElement = $('[data-wishlist-count], #wishlist-count');
        if (countElement.length > 0) {
            countElement.each(function() {
                $(this).text(parseInt(value, 10) || 0);
            });
        }
    }

    /* ────────────────────────────────────────────────────────────────
       Dual-handle price slider controller.

       Fixes over the old two-overlapping-<input type=range> approach:
       • Both handles stay draggable — the grabbed handle gets `.is-active`
         and is raised in z-order, and a minimum gap keeps the thumbs from
         ever fully overlapping (which is what made the min handle un-grabbable
         once it met the max, so you couldn't "revert" it).
       • Hard cap so min can never cross max (and vice-versa).
       • The product list reloads ONLY on release/commit, never mid-drag.
       • syncBounds() re-scales the track to the context-aware bounds returned
         by the AJAX fetch whenever a non-price filter changes the result set.
       Returns null when the slider markup isn't on the page.
       ──────────────────────────────────────────────────────────────── */
    function initPriceSlider() {
        var wrapper = document.querySelector('.price-slider');
        if (!wrapper) return null;

        var rangeMin = wrapper.querySelector('.range-min');
        var rangeMax = wrapper.querySelector('.range-max');
        var inputMin = wrapper.querySelector('.input-min');
        var inputMax = wrapper.querySelector('.input-max');
        var progress = wrapper.querySelector('.slider .progress');
        if (!rangeMin || !rangeMax || !progress) return null;

        var bounds = {
            min: parseFloat(wrapper.dataset.min) || 0,
            max: parseFloat(wrapper.dataset.max) || 0,
            step: parseFloat(wrapper.dataset.step) || 1
        };

        // Minimum distance (in price units) the handles must keep between them so
        // they never fully overlap and both remain grabbable. Scales with range.
        function gap() {
            return Math.max(bounds.step, Math.round((bounds.max - bounds.min) / 50));
        }

        function paint() {
            var span = (bounds.max - bounds.min) || 1;
            var lo = parseFloat(rangeMin.value);
            var hi = parseFloat(rangeMax.value);
            progress.style.left = ((lo - bounds.min) / span) * 100 + '%';
            progress.style.right = (100 - ((hi - bounds.min) / span) * 100) + '%';
            if (inputMin) inputMin.value = Math.round(lo);
            if (inputMax) inputMax.value = Math.round(hi);
        }

        function setActive(el) {
            rangeMin.classList.toggle('is-active', el === rangeMin);
            rangeMax.classList.toggle('is-active', el === rangeMax);
        }

        // --- Dragging a handle: update visuals only, NO product reload ---
        rangeMin.addEventListener('input', function () {
            var lo = parseFloat(rangeMin.value);
            var hi = parseFloat(rangeMax.value);
            if (lo > hi - gap()) {                       // cap against the max handle
                lo = Math.max(bounds.min, hi - gap());
                rangeMin.value = lo;
            }
            setActive(rangeMin);
            paint();
        });
        rangeMax.addEventListener('input', function () {
            var lo = parseFloat(rangeMin.value);
            var hi = parseFloat(rangeMax.value);
            if (hi < lo + gap()) {                       // cap against the min handle
                hi = Math.min(bounds.max, lo + gap());
                rangeMax.value = hi;
            }
            setActive(rangeMax);
            paint();
        });

        // Raise whichever handle the user grabs, so overlapping thumbs stay usable.
        ['pointerdown', 'mousedown', 'touchstart', 'focus'].forEach(function (ev) {
            rangeMin.addEventListener(ev, function () { setActive(rangeMin); });
            rangeMax.addEventListener(ev, function () { setActive(rangeMax); });
        });

        // --- Commit (handle released) → reload the product list ONCE ---
        function commit() {
            price_filter_enabled = true;
            updateURL();
        }
        rangeMin.addEventListener('change', commit);
        rangeMax.addEventListener('change', commit);

        // --- Editable Min / Max number boxes ---
        function commitFromInputs() {
            var lo = parseFloat(inputMin.value);
            var hi = parseFloat(inputMax.value);
            if (isNaN(lo)) lo = bounds.min;
            if (isNaN(hi)) hi = bounds.max;
            lo = Math.min(Math.max(lo, bounds.min), bounds.max);
            hi = Math.min(Math.max(hi, bounds.min), bounds.max);
            if (hi < lo) { var t = lo; lo = hi; hi = t; }   // fix swapped entry
            if (hi - lo < gap()) {                          // keep the min gap...
                if (lo + gap() <= bounds.max) {
                    hi = lo + gap();                        // ...by pushing max up,
                } else {
                    hi = bounds.max;                        // ...or pulling min down
                    lo = Math.max(bounds.min, hi - gap());  //    when max is capped
                }
            }
            rangeMin.value = lo;
            rangeMax.value = hi;
            paint();
            commit();
        }
        if (inputMin && inputMax) {
            [inputMin, inputMax].forEach(function (inp) {
                inp.addEventListener('change', commitFromInputs);
                inp.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); inp.blur(); }
                });
            });
        }

        paint(); // initial position

        return {
            // Re-scale to the new context-aware bounds returned by the AJAX fetch.
            // Called when a NON-price filter changes the result set. Keeps the
            // user's selection (clamped) when a price filter is active; otherwise
            // spans the full new range.
            syncBounds: function (newMin, newMax) {
                newMin = parseFloat(newMin);
                newMax = parseFloat(newMax);
                // No usable range (single price point / empty set) — leave the
                // slider as-is rather than rescaling to a zero-width range.
                if (isNaN(newMin) || isNaN(newMax) || newMax <= newMin) return;

                bounds.min = newMin;
                bounds.max = newMax;
                wrapper.dataset.min = newMin;
                wrapper.dataset.max = newMax;
                [rangeMin, rangeMax].forEach(function (r) {
                    r.min = newMin;
                    r.max = newMax;
                });
                [inputMin, inputMax].forEach(function (inp) {
                    if (inp) { inp.min = newMin; inp.max = newMax; }
                });

                if (!price_filter_enabled) {
                    rangeMin.value = newMin;
                    rangeMax.value = newMax;
                } else {
                    var lo = Math.min(Math.max(parseFloat(rangeMin.value), newMin), newMax);
                    var hi = Math.min(Math.max(parseFloat(rangeMax.value), newMin), newMax);
                    if (hi - lo < gap()) hi = Math.min(newMax, lo + gap());
                    rangeMin.value = lo;
                    rangeMax.value = hi;
                }
                paint();
            }
        };
    }

});

/* function getSellerDetails(seller_slug){
    $.ajax({
        type: "POST",
        url: base_url + "sellers/get_seller_details",
        data: {
            [csrfName]: csrfHash,
            'seller_slug': seller_slug,
        },
        dataType: "json",
        success: function (e) {
            csrfName = e.csrfName;
            csrfHash = e.csrfHash;
            if (e.error == false) {
                $('#seller-store-name').text(e.data[0]['store_name']);

                // Extract the seller's category IDs
                let allowedCategories = e.data[0]['seller_categories'].map(cat => String(cat.id));

                // Loop through all category checkboxes
                $('.filter-list-item').each(function () {
                    let categoryId = $(this).find('input[type="checkbox"]').data('value');

                    // Hide the category if not in the allowed categories
                    if (!allowedCategories.includes(String(categoryId))) {
                        $(this).hide(); // or .remove() if you want to completely remove it
                    }
                });
            }
        }
    });
} */

function removeSellerFilter() {
    // Remove 'seller' query parameter from the URL
    const url = new URL(window.location.href);
    url.searchParams.delete('seller');
    window.location.href = url.toString();
}


// Load products for the currently opened page URL.
currentPage = getCurrentPageFromUrl();
ajaxProductList(currentPage);

function getQueryQ() {
    const params = new URLSearchParams(window.location.search);
    return params.get('q') || '';
}

function getCategorySlugs() {
    const path = window.location.pathname.split('/').filter(Boolean);

    const category_slug       = path[2] || '';
    const sub_category_slug   = path[3] || '';

    return {
        category_slug,
        sub_category_slug
    };
}

function ajaxProductList(page = 1, append = false) {
     currentPage = page;
     let slugData = getCategorySlugs();
     let subCategory = "";
     let searchData = "";
    const params = new URLSearchParams(window.location.search);
    const q = params.get('q') || '';

     if(slugData.category_slug == 'category'){
        subCategory = slugData.sub_category_slug;
     }
     if(slugData.category_slug == 'search'){
         searchData = q; 
     }

    // always set pagination parameters
    params.set('page', currentPage);
    params.set('per-page', perPage);

    // The global page loader (bound to jQuery ajaxStart/ajaxStop in template.php)
    // now provides the loading feedback, so no separate in-grid spinner is needed.
    isLoading = true;

    $.ajax({
        url: base_url + 'products/ajax_get_products' +
             (params.toString()
                ? '?' + params.toString() + '&subCategory=' + subCategory + '&searchData=' + searchData
                : '?subCategory=' + subCategory + '&searchData=' + searchData
             ),

        type: 'GET',
        dataType: 'json',

        success: function (response) {
            isLoading = false;
            if (response.status === 'success') {
                totalRows = response.total_rows || 0;

                // Re-scale the price slider to the context-aware bounds of the
                // freshly filtered set (min/max returned by fetch_product). Keeps
                // the current selection when a price filter is active; otherwise
                // spans the full new range.
                if (priceSlider && response.products &&
                    typeof response.products.min_price !== 'undefined') {
                    priceSlider.syncBounds(response.products.min_price, response.products.max_price);
                }

                var html = renderProducts(response.products.product || []);
                if (append) {
                    $('#productList').append(html);
                } else {
                    $('#productList').html(html);
                }
                $('#products-pagination-nav').html(renderPagination(totalRows, currentPage, perPage));
                $('.result-count').text(response.result_count || '');

                // update 'Showing X of Y' text
                var shownCount = $('#productList .product').length;
                var total = totalRows;
                $('.product-filter .text-n.op-6').first().text('Showing ' + shownCount + ' of ' + total);

                // update URL parameters to keep in sync
                var newUrl = new URL(window.location.href);
                newUrl.searchParams.set('page', currentPage);
                newUrl.searchParams.set('per-page', perPage);
                window.history.replaceState({}, '', newUrl.toString());
            }
        },

        error: function (xhr, status, error) {
            isLoading = false;
            $('#productList').html('<div class="text-center py-5">Unable to load products. Please try again.</div>');
            console.error('AJAX Error:', status, error, xhr && xhr.responseText ? xhr.responseText : '');
        }
    });
}

function renderPagination(total, page, pageSize) {
    var totalPages = Math.ceil(total / pageSize);
    if (totalPages <= 1) {
        return '';
    }

    var html = '<ul class="pagination justify-content-center">';
    var maxLinks = 7;
    var start = Math.max(1, page - Math.floor(maxLinks / 2));
    var end = Math.min(totalPages, start + maxLinks - 1);
    start = Math.max(1, end - maxLinks + 1);

    if (page > 1) {
        html += '<li class="page-item"><a class="page-link ajax-page-link" href="#" data-page="' + (page - 1) + '"><i class="uil uil-arrow-left"></i></a></li>';
    }

    for (var i = start; i <= end; i++) {
        if (i === page) {
            html += '<li class="page-item active disabled"><a class="page-link" href="#">' + i + '</a></li>';
        } else {
            html += '<li class="page-item"><a class="page-link ajax-page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
        }
    }

    if (page < totalPages) {
        html += '<li class="page-item"><a class="page-link ajax-page-link" href="#" data-page="' + (page + 1) + '"><i class="uil uil-arrow-right"></i></a></li>';
    }

    html += '</ul>';
    return html;
}

$(document).on('click', '#products-pagination-nav .ajax-page-link', function(e) {
    e.preventDefault();
    var page = parseInt($(this).data('page'), 10);
    if (!isNaN(page) && page > 0) {
        ajaxProductList(page, false);
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }
});
// Infinite scroll is disabled for product listing pagination.
if (useAjaxInfiniteScroll) {
    $(window).on('scroll', function() {
        if (isLoading) return;
        if (currentPage * perPage >= totalRows) return;

        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            ajaxProductList(currentPage + 1, true);
        }
    });
}

function generateStarRatingHTML(product) {
    let rating = parseFloat(product.rating || 0);
    let fullStars = Math.floor(rating);
    let halfStar = rating % 1 >= 0.5 ? 1 : 0;
    let emptyStars = 5 - fullStars - halfStar;

    let html = '<div class="star-rating">';
    for (let i = 0; i < fullStars; i++) html += '<i class="fa fa-star"></i>';
    if (halfStar) html += '<i class="fa fa-star-half-o"></i>';
    for (let i = 0; i < emptyStars; i++) html += '<i class="fa fa-star-o"></i>';
    html += '</div>';

    return html;
}

function renderProducts(products) {
    if (!products.length) {
        return '<div class="text-center py-5">No products found</div>';
    }

    let html = '';

    products.forEach(product => {

        // Favorite button classes
        let isFav = product.is_favorite == 1;
        let heartClass = isFav ? 'fa-heart' : 'fa-heart-o';
        let favClass = isFav ? 'is-fav' : '';
        let favState = isFav ? 'true' : 'false';
        let favLabel = isFav ? 'Wishlisted' : 'Wishlist';

        // Product image
        let imgSrc = product.image_sm || base_url + 'assets/front_end/modern/img/product-placeholder.jpg';

        // Secondary image for the hover-swap (falls back to none when the product has only one image)
        let hoverSrc = (product.other_images_sm && product.other_images_sm.length) ? product.other_images_sm[0] : '';
        let hoverImgHTML = hoverSrc
            ? `<img class="card-img-img secondary-img" src="${hoverSrc}" alt="${product.name}">`
            : '';

        // Short description safely
        let shortDesc = product.short_description ? product.short_description.replace(/\r\n/g, '&#13;&#10;') : '';

        // Price HTML (match your PHP generatePriceElement function output)
        let priceHTML = '';
        if (product.variants && product.variants.length) {

    let variant = product.variants[0];

    let price = parseFloat(variant.price);
    let specialPrice = (variant.special_price && variant.special_price != 0)
        ? parseFloat(variant.special_price)
        : price;

    let oldPrice = '';
    let offPercentHTML = '';

    // Show discount only when special price < price
        if (specialPrice < price) {

            let discountPercent = Math.round(((price - specialPrice) / price) * 100);

            oldPrice = `<span class="discounted-price no-wrap">₹${price}</span>`;

            offPercentHTML = `
                <span class="off-percent fw-b no-wrap">
                    ${discountPercent}% OFF
                </span>
            `;
        }

        priceHTML = `
            <p class="price-container ta-c no-wrap text-es">
                ${oldPrice}
                <span class="original-price op-6 no-wrap">₹${specialPrice}</span>
                ${offPercentHTML}
            </p>
        `;
    }


        // Build HTML for each product
        html += `
        <div class="cretzo-card card-type-one product-card product">
            <a class="card-url" href="${base_url}products/details/${product.slug}"></a>

            <div class="card-img">
                <button class="small-btn small-btn-light prod-tag prod-tag-top">Sale</button>
                <button class="small-btn small-btn-dark prod-tag prod-tag-bottom">New</button>

                <img class="card-img-img primary-img lazy" src="${imgSrc}" data-src="${imgSrc}" alt="${product.name}">
                ${hoverImgHTML}

                ${generateStarRatingHTML(product)}

                <button class="text-n addwishlist-btn ${favClass}" id="add_to_favorite_btn" data-is-fav="${favState}" data-product-id="${product.id}">
                    <i class="heart-icon fa ${heartClass}"></i>
                    <span>${favLabel}</span>
                </button>
            </div>

            <div class="card-des">
                <h1 class="ta-c text-s product-name-no-wrap">${product.name}</h1>

                <p class="ta-c list-product-desc text-es product-name-no-wrap">
                    ${shortDesc}
                </p>

                ${priceHTML}
            </div>
        </div>`;
    });

    return html;
}



