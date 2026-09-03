/* $('.product-attr-label-general .product-attr-input').change(function() {
    $(".product-attr-label-general .product-attr-input").parent().removeClass("selected");
    $(".product-attr-label-general .product-attr-input:checked").parent().addClass("selected");
}); */

// Incomplete function, maybe will implement later. 
// ! This is called whenever a product variant is selected (i.e. a complete set is picked).
function product_variant_selected(selected_variant_id) {
    return; // TODO: implement later

    // remove all slides

    galleryTop.removeAllSlides();
    galleryTop.update();
    
    // galleryTop.updateSlides();

};

var scene = null;
var controller = null;

$(document).ready(function() {
    // Add click event listener to each description button
    $('.des-btn').click(function() {
        // Remove 'active-des-btn' class from all buttons
        $('.des-btn').removeClass('active-des-btn');

        // Add 'active-des-btn' class to the clicked button
        $(this).addClass('active-des-btn');

        // Get the index of the clicked button
        var index = $(this).index();

        // Hide all description elements
        $('.des').addClass('d-none');

        // Show the description element corresponding to the clicked button
        $('.des').eq(index).removeClass('d-none');
    });
    
    setupScrollMagicEffect();
    setupThumbnailHoverPreview();
    setupProductImageZoom();

});

// Switch the main image on hover instead of requiring a click, since the
// thumbs are just a preview rail.
function setupThumbnailHoverPreview() {
    var thumbSlides = document.querySelectorAll('.gallery-thumbs-1 .swiper-slide');
    thumbSlides.forEach(function (thumb, index) {
        thumb.addEventListener('mouseenter', function () {
            if (window.galleryTop) {
                window.galleryTop.slideTo(index);
            }
        });
    });
}

// Amazon-style hover-to-magnify: a lens on the main image highlights the
// hovered region, and a pane to the right shows that region zoomed in.
function setupProductImageZoom() {
    var wrap = document.querySelector('.big-img-container');
    if (!wrap) return;

    var ZOOM = 2.5;
    var GAP = 20;

    var lens = document.createElement('div');
    lens.className = 'zoom-lens';
    wrap.appendChild(lens);

    var pane = document.createElement('div');
    pane.className = 'zoom-pane';
    document.body.appendChild(pane);

    function activeImage() {
        var img = wrap.querySelector('.swiper-slide-active img.product-big-img');
        return (img && img.offsetParent !== null) ? img : null;
    }

    function canZoom() {
        return window.innerWidth > 992 && window.matchMedia('(hover: hover)').matches;
    }

    function hide() {
        lens.style.display = 'none';
        pane.style.display = 'none';
    }

    function render(e) {
        var img = activeImage();
        if (!canZoom() || !img) {
            hide();
            return;
        }

        var imgRect = img.getBoundingClientRect();
        var x = Math.max(0, Math.min(imgRect.width, e.clientX - imgRect.left));
        var y = Math.max(0, Math.min(imgRect.height, e.clientY - imgRect.top));

        var bgW = imgRect.width * ZOOM;
        var bgH = imgRect.height * ZOOM;
        var bgX = Math.min(0, Math.max(imgRect.width - bgW, imgRect.width / 2 - x * ZOOM));
        var bgY = Math.min(0, Math.max(imgRect.height - bgH, imgRect.height / 2 - y * ZOOM));

        pane.style.display = 'block';
        pane.style.width = imgRect.width + 'px';
        pane.style.height = imgRect.height + 'px';
        pane.style.top = imgRect.top + 'px';
        pane.style.left = (imgRect.right + GAP) + 'px';
        pane.style.backgroundImage = 'url("' + (img.currentSrc || img.src) + '")';
        pane.style.backgroundSize = bgW + 'px ' + bgH + 'px';
        pane.style.backgroundPosition = bgX + 'px ' + bgY + 'px';

        var lensW = imgRect.width / ZOOM;
        var lensH = imgRect.height / ZOOM;
        lens.style.display = 'block';
        lens.style.width = lensW + 'px';
        lens.style.height = lensH + 'px';
        lens.style.left = Math.max(0, Math.min(imgRect.width - lensW, x - lensW / 2)) + 'px';
        lens.style.top = Math.max(0, Math.min(imgRect.height - lensH, y - lensH / 2)) + 'px';
    }

    wrap.addEventListener('mousemove', render);
    wrap.addEventListener('mouseleave', hide);
    window.addEventListener('scroll', hide, { passive: true });
}

function setupScrollMagicEffect(){
    // Destroy scroll magic if it already exists
    destroyScrollMagic();

    if(getWindowWidth() <= 800)
        return;

    // Create a ScrollMagic Controller
    controller = new ScrollMagic.Controller();

    // Get the elements
    var detailContainerContent = document.querySelector(".detail-container-content");
    var imgContainer = document.querySelector(".img-container");
    
    // Calculate the duration: from the top of detail-container-content 
    // to the point where the end of detail-container-content aligns with the end of img-container
    var imgContainerHeight = imgContainer.offsetHeight;
    var duration = detailContainerContent.offsetHeight - imgContainerHeight + 16;

    // Get the height of the sticky header
    // var headerHeight = -1 * (document.querySelector(".header-container").offsetHeight + document.querySelector(".navbar-container").offsetHeight);

    // Create a Scene
    scene = new ScrollMagic.Scene({
        triggerElement: ".detail-container", // Trigger this when .scrollable-div comes into view
        //duration: document.querySelector('.detail-container-content').offsetHeight, // The length of the scrolling div
        duration: duration,
        // offset: headerHeight,
        triggerHook: "onLeave" // When .scrollable-div leaves the viewport
    })
    .setPin(".img-container") // Pin the .scrollable-div while scrolling
    .addTo(controller);

    // Optionally add a smooth animation
    scene.on("progress", function (event) {
        document.querySelector('.detail-container').scrollTop = event.progress * document.querySelector('.detail-container-content').offsetHeight;
    });
}

function destroyScrollMagic(){
    if(controller != null)
        controller.destroy(true);
    if(scene != null)
        scene.destroy(true);
}

function getWindowWidth(){
    return window.innerWidth && document.documentElement.clientWidth ?
        Math.min(window.innerWidth, document.documentElement.clientWidth) :
        window.innerWidth ||
        document.documentElement.clientWidth ||
        document.getElementsByTagName('body')[0].clientWidth;
}

/* P4.3 — Sticky mobile add-to-cart bar.
   The bar's button proxies to the in-page #add_cart button so all the
   existing variant/quantity/cart handling is reused, and the bar price
   mirrors the main current price when a variant is selected. */
$(function () {
    var $barBtn = $('#mobile-add-cart');
    var $mainAdd = $('#add_cart');
    if ($barBtn.length && $mainAdd.length) {
        $barBtn.on('click', function (e) {
            e.preventDefault();
            $mainAdd.trigger('click');
        });
    }
    var mainPrice = document.querySelector('.detail-container .current-price');
    var barPrice = document.getElementById('mbb-current-price');
    if (mainPrice && barPrice && window.MutationObserver) {
        var obs = new MutationObserver(function () {
            var t = mainPrice.textContent.trim();
            if (t) barPrice.textContent = t;
        });
        obs.observe(mainPrice, { childList: true, characterData: true, subtree: true });
    }
});


/* ---------------------------------------------------------------------------
 * Write-a-review panel (#czrev-form).
 *
 * Everything the customer needs to rate the product lives here: the star
 * picker, the photo picker with previews and client-side validation, the AJAX
 * submit, and deleting their own review.
 *
 * Why this is not left to custom.js's #product-rating-form handler:
 *   - that handler has no `error:` branch, so any 403/500/timeout left the
 *     button stuck on "Please Wait..." with nothing said to the customer;
 *   - it posts whatever the Krajee star plugin left in the field, which is 0
 *     when the plugin has not initialised - the server then answers with a
 *     validation error about a field the customer cannot see;
 *   - it does no file checking at all, so an oversized or wrong-typed photo
 *     was only rejected after a full upload.
 * The form deliberately uses a different id so BOTH handlers never fire for
 * one submit.
 * ------------------------------------------------------------------------- */
(function () {
    var form = document.getElementById('czrev-form');
    if (!form) {
        return;
    }

    var CAPTIONS = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
    var MAX_FILES = 5;
    /* Products.php configures the upload with 'max_size' => 8000, which CodeIgniter
       reads as kilobytes. Stay just under it so the browser rejects the file rather
       than the customer waiting for an upload that cannot succeed. */
    var MAX_BYTES = 7 * 1024 * 1024;
    var ALLOWED = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif'];

    var $form = $(form);
    var ratingField = document.getElementById('czrev-rating-value');
    var caption = document.getElementById('czrev-star-caption');
    var fileInput = document.getElementById('czrev-images');
    var fileCount = document.getElementById('czrev-file-count');
    var previews = document.getElementById('czrev-previews');
    var submitBtn = document.getElementById('czrev-submit');

    function toast(icon, title) {
        if (window.Toast && typeof Toast.fire === 'function') {
            Toast.fire({ icon: icon, title: title });
        } else {
            alert(title);
        }
    }

    /* --- stars ------------------------------------------------------------ */
    function paintStars(value) {
        $form.find('.czrev-star').each(function (i) {
            $(this).toggleClass('is-on', (i + 1) <= value);
        });
        if (caption) {
            caption.textContent = value ? CAPTIONS[value] : '';
        }
    }

    function selectedStar() {
        var checked = form.querySelector('input[name="czrev_star"]:checked');
        return checked ? parseInt(checked.value, 10) : 0;
    }

    $form.on('change', 'input[name="czrev_star"]', function () {
        var value = selectedStar();
        ratingField.value = value ? value : '';
        paintStars(value);
        $form.find('.czrev-stars').removeClass('czrev-invalid');
    });

    /* Hover preview, mouse only - the radios keep the keyboard path working. */
    $form.on('mouseenter', '.czrev-star', function () {
        paintStars($form.find('.czrev-star').index(this) + 1);
    }).on('mouseleave', '.czrev-stars', function () {
        paintStars(selectedStar());
    });

    paintStars(selectedStar());

    /* --- photos ----------------------------------------------------------- */
    function czrevValidateFiles(files) {
        if (files.length > MAX_FILES) {
            return 'Please choose at most ' + MAX_FILES + ' photos.';
        }
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            /* Some Windows/Android pickers hand over an empty type for a .jpg, so
               fall back to the extension rather than rejecting a valid photo. */
            var typeOk = ALLOWED.indexOf((f.type || '').toLowerCase()) !== -1 ||
                /\.(jpe?g|png|gif)$/i.test(f.name || '');
            if (!typeOk) {
                return '"' + f.name + '" is not a JPG, PNG or GIF.';
            }
            if (f.size > MAX_BYTES) {
                return '"' + f.name + '" is larger than 7 MB.';
            }
        }
        return '';
    }

    function renderPreviews(files) {
        previews.innerHTML = '';
        if (!files.length) {
            fileCount.textContent = 'No photos selected';
            return;
        }
        fileCount.textContent = files.length + (files.length === 1 ? ' photo selected' : ' photos selected');
        Array.prototype.forEach.call(files, function (file) {
            var url = URL.createObjectURL(file);
            var wrap = document.createElement('span');
            wrap.className = 'czrev-preview';
            var img = document.createElement('img');
            img.src = url;
            img.alt = file.name;
            /* Free the object URL once the browser has decoded it. */
            img.onload = function () { URL.revokeObjectURL(url); };
            wrap.appendChild(img);
            previews.appendChild(wrap);
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var problem = czrevValidateFiles(this.files);
            if (problem) {
                toast('error', problem);
                this.value = '';
                renderPreviews([]);
                return;
            }
            renderPreviews(this.files);
        });
    }

    /* --- submit ----------------------------------------------------------- */
    $form.on('submit', function (e) {
        e.preventDefault();

        if (!ratingField.value) {
            $form.find('.czrev-stars').addClass('czrev-invalid');
            toast('error', 'Please pick a star rating first.');
            return;
        }
        if (fileInput && fileInput.files.length) {
            var problem = czrevValidateFiles(fileInput.files);
            if (problem) {
                toast('error', problem);
                return;
            }
        }

        var original = submitBtn.innerHTML;
        var data = new FormData(form);
        /* The radios are UI only; `rating` is the field the server validates. */
        data.delete('czrev_star');
        if (window.csrfName && window.csrfHash) {
            data.append(csrfName, csrfHash);
        }

        $.ajax({
            type: 'POST',
            url: form.getAttribute('action'),
            data: data,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                submitBtn.innerHTML = 'Please wait...';
                submitBtn.disabled = true;
            },
            success: function (res) {
                if (res && res.csrfName) { csrfName = res.csrfName; }
                if (res && res.csrfHash) { csrfHash = res.csrfHash; }
                if (res && res.error === false) {
                    toast('success', res.message || 'Thanks for your review!');
                    window.location.reload();
                    return;
                }
                /* save_rating() returns validation_errors(), which is HTML. */
                var message = (res && res.message) ? $('<div>').html(res.message).text().trim() : '';
                toast('error', message || 'We could not save your review. Please try again.');
                submitBtn.innerHTML = original;
                submitBtn.disabled = false;
            },
            error: function (xhr) {
                toast('error', xhr.status === 403
                    ? 'Your session expired. Please reload the page and try again.'
                    : 'We could not save your review just now. Please try again.');
                submitBtn.innerHTML = original;
                submitBtn.disabled = false;
            }
        });
    });

    /* --- delete ----------------------------------------------------------- */
    $('#czrev-delete').on('click', function () {
        var btn = this;
        if (!window.confirm('Delete your review of this product?')) {
            return;
        }
        var payload = { rating_id: $(btn).data('rating-id') };
        if (window.csrfName && window.csrfHash) {
            payload[csrfName] = csrfHash;
        }
        $.ajax({
            type: 'POST',
            url: $(btn).data('url'),
            data: payload,
            dataType: 'json',
            beforeSend: function () { btn.disabled = true; },
            success: function (res) {
                if (res && res.csrfName) { csrfName = res.csrfName; }
                if (res && res.csrfHash) { csrfHash = res.csrfHash; }
                if (res && res.error === false) {
                    toast('success', res.message || 'Review deleted.');
                    window.location.reload();
                    return;
                }
                toast('error', (res && res.message) || 'We could not delete your review.');
                btn.disabled = false;
            },
            error: function () {
                toast('error', 'We could not delete your review just now. Please try again.');
                btn.disabled = false;
            }
        });
    });
})();
