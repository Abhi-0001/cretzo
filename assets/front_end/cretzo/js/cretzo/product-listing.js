var price_filter_enabled = false;
// paging state for infinite scroll
var currentPage = 1;
var perPage = 20;          // default page size for scroll
var totalRows = 0;
var isLoading = false;
var useAjaxInfiniteScroll = false;

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

    // Add event listener for 'Filter Price' button click
    $('.filter-price-btn').on('input', function() {
        price_filter_enabled = true;
        updateURL();
    });
    $('.filter-price-btn').on('change', function() {
        price_filter_enabled = true;
        updateURL();
    });
    // $('#filter-price-btn').click(function() {
    //     price_filter_enabled = true;
    //     updateURL();
    // });
    $('#clear-filter-price-btn').click(function() {
        price_filter_enabled = false;
        updateURL();
    });

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

    // Add event listener for tapping on #bg-overlay
    $('#bg-overlay').on('click', function() {
        // Hide background overlay
        // $(this).css('display', 'none');
        $(this).removeClass('active');

        // Remove active class from filter-container
        $('.filter-container').removeClass('active');
    });

    /* Setup Price Filter */
    const rangeInput = document.querySelectorAll(".range-input input"),
    priceInput = document.querySelectorAll(".price-input input"),
    range = document.querySelector(".slider .progress");
    let priceGap = 1000;

    priceInput.forEach((input) => {
        input.addEventListener("input", (e) => {
            let minPrice = parseInt(priceInput[0].value),
            maxPrice = parseInt(priceInput[1].value);

            if (maxPrice - minPrice >= priceGap && maxPrice <= rangeInput[1].max) {
            if (e.target.className === "input-min") {
                rangeInput[0].value = minPrice;
                range.style.left = (minPrice / rangeInput[0].max) * 100 + "%";
            } else {
                rangeInput[1].value = maxPrice;
                range.style.right = 100 - (maxPrice / rangeInput[1].max) * 100 + "%";
            }
            }
        });
    });

    rangeInput.forEach((input) => {
        input.addEventListener("input", (e) => {
            let minVal = parseInt(rangeInput[0].value),
            maxVal = parseInt(rangeInput[1].value);

            if (maxVal - minVal < priceGap) {
            if (e.target.className === "range-min") {
                rangeInput[0].value = maxVal - priceGap;
            } else {
                rangeInput[1].value = minVal + priceGap;
            }
            } else {
            priceInput[0].value = minVal;
            priceInput[1].value = maxVal;
            range.style.left = (minVal / rangeInput[0].max) * 100 + "%";
            range.style.right = 100 - (maxVal / rangeInput[1].max) * 100 + "%";
            }
        });
    });

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
 
function ajaxProductList() {
    console.log('pathname:', window.location.pathname);
    console.log('slugData:', slugData);
    console.log('search params:', window.location.search);
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

    $('#productList').html(
        '<div class="text-center py-5">' +
            '<div class="spinner-border text-warning"></div>' +
        '</div>'
    );

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
    var start = Math.max(1, page - 3);
    var end = Math.min(totalPages, page + 3);

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

        // Product image
        let imgSrc = product.image_sm || base_url + 'assets/front_end/modern/img/product-placeholder.jpg';

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

                <img class="card-img-img lazy" src="${imgSrc}" data-src="${imgSrc}" alt="${product.name}">

                ${generateStarRatingHTML(product)}

                <button class="text-n addwishlist-btn ${favClass}" id="add_to_favorite_btn" data-is-fav="${favState}" data-product-id="${product.id}">
                    <i class="heart-icon fa ${heartClass}"></i>
                    <span>Wishlist</span>
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



