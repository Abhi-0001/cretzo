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
    

});

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