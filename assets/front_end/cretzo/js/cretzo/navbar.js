/* To toggle mega menu */
var isMegaMenuOpen = false;
var isOpeningMegaMenu = false;

/* To toggle mega menu */
function toggleMegaMenu(btn){
    setMegaMenuHeight();
    
    indicateMegaMenuOpening();

    var index = Array.from(btn.parentElement.children).indexOf(btn);
    var megaMenu = document.querySelectorAll('.mega-menu')[index];
    var isClosed = !megaMenu.classList.contains('active');

    // Close all mega menus
    closeAllMegaMenus();

    if (isClosed) {
        megaMenu.classList.add('active');

        btn.classList.add('nav-mega-btn-active'); // Add class to btn when mega menu is opened

        $('#bg-backdrop').addClass('active');
        // $('#megamenu-hover-net').addClass('active');

        isMegaMenuOpen = true;
    } /* else {
        megaMenu.classList.add('d-none');
    } */
}

function closeAllMegaMenus() {
    var megaMenus = document.querySelectorAll('.mega-menu');
    megaMenus.forEach(function(menu) {
        menu.classList.remove('active');

        /* remove background grey */
        $('#bg-backdrop').removeClass('active');

        /* remove hover net */
        // $('#megamenu-hover-net').removeClass('active');
    });

    /* set 'isMegaMenuOpen' to false */
    isMegaMenuOpen = false;

    // remove active class from all buttons
    document.querySelectorAll('.nav-btn').forEach(function(button) {
        button.classList.remove('nav-mega-btn-active');
    });
}

function indicateMegaMenuOpening() {
    isOpeningMegaMenu = true;
    setTimeout(function() {
        isOpeningMegaMenu = false;
    }, 200); // 200 milliseconds = 0.2 seconds
}


/* Check if an element is being hovered on */
function isHover(element) {
    return element.matches(':hover');
}

/* Add event listener for mouseleave on the navbar */
/* document.querySelector('.navbar-container').addEventListener('mouseleave', function() {
    closeAllMegaMenus();
}); */

var megamenuCloseTimeout;
/* Add event listener for mouseleave on the navbar */
document.querySelector('.navbar-container').addEventListener('mouseleave', function() {
    // Set a timeout to close all mega menus after 0.25 seconds
    megamenuCloseTimeout = setTimeout(function() {
        closeAllMegaMenus();
    }, 250);
});
/* Add event listener for mouseenter on the navbar */
document.querySelector('.navbar-container').addEventListener('mouseenter', function() {
    // Clear the timeout to prevent closing mega menus
    clearTimeout(megamenuCloseTimeout);
});

/* Add event listeners for mouse hover on buttons */
document.querySelectorAll('.nav-btn').forEach(function(btn) {
    btn.addEventListener('mouseenter', function() {
        var index = Array.from(btn.parentElement.children).indexOf(btn);
        if(!isMegaMenuOpenAtIndex(index)){
            toggleMegaMenu(this); // Pass the associated mega menu
        }
    });
});

/* Function to check if the mega menu associated with a button is open */
function isMegaMenuOpenAtIndex(index) {
    var megaMenu = document.querySelectorAll('.mega-menu')[index];
    return megaMenu.classList.contains('active');
}


$(window).resize(function(){
    setMegaMenuHeight();
});

$(document).ready(function() {
    setupNavbarContainer();
    setupMegaMenu();
    setupHeaderScrollFN();
});

function setupNavbarContainer(){
    var $headerHeight = $('.header-container').outerHeight();
    $('.navbar-container').css('top', $headerHeight + 'px');

    setNavbarShadow($(window).scrollTop() > 0);
}

function setupMegaMenu(){
    /* Close mega menu if anything outside of mega menu is clicked */
    $(document).on('click', function(event) {
        var $target = $(event.target);
        var $megaMenu = $('.mega-menu');
        var $megaMenuToggleBtn = $('.nav-mega-btn');

        // Check if the clicked element is inside the mega menu or the dropdown toggle
        var isInsideMegaMenu = $target.closest($megaMenu).length > 0;
        var isMegaMenuToggleBtn = $target.closest($megaMenuToggleBtn).length > 0;

        // Close mega menu if clicked outside of it and not on the mega menu toggle button either
        if (!isInsideMegaMenu && !isMegaMenuToggleBtn) {
            closeAllMegaMenus();
        }
    });

    setMegaMenuHeight();
}

function setMegaMenuHeight(){
    /* Setup top of mega-menu to header height */
    var $headerHeight = $('.navbar-container').outerHeight();
    $('.mega-menu').css('top', $headerHeight-1 + 'px');
}

/* Auto hide/show header/navbar on scroll */
function setupHeaderScrollFN(){
    var prevScrollpos = $(window).scrollTop();
    
    /* Get the header and navbar elements and their original top positions */
    var headerDiv = $(".header-container");
    var navbarDiv = $(".navbar-container");
    
    var headerOriginalTop = headerDiv.css("top");
    var navbarOriginalTop = navbarDiv.css("top");
    /* var headerOriginalTop = headerDiv.position().top;
    var navbarOriginalTop = navbarDiv.position().top; */
    
    /* Get the bottom position of navbar */
    var navbarBottom = navbarOriginalTop + navbarDiv.outerHeight();
    
    $(window).scroll(function() {
        var currentScrollPos = $(window).scrollTop();
        
        /* if we're scrolling up manually, or we haven't passed the header or navbar,
        show them at their original top positions */
        if (prevScrollpos > currentScrollPos || currentScrollPos < navbarBottom) {  
            headerDiv.css("top", headerOriginalTop);
            navbarDiv.css("top", navbarOriginalTop);
        } else if (prevScrollpos < currentScrollPos && prevScrollpos-currentScrollPos<-0 && !isOpeningMegaMenu) {
            headerDiv.css("top", "-7.2rem");
            navbarDiv.css("top", "-7.2rem");
            closeAllMegaMenus();
        } 
        
        prevScrollpos = currentScrollPos;

        setNavbarShadow(currentScrollPos > 0);
    });
}

/* if scrolled to top, keep shadow on navbar */
function setNavbarShadow(enable){
    if(enable){
        $(".navbar-container").addClass("shadow");
    }
    else{
        $(".navbar-container").removeClass("shadow");
    }
}