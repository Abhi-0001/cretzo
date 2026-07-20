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