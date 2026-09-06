// to toggle side menu
function openSideMenuFn(){
    document.getElementById('sideMenu').classList.remove('d-none');
}
function closeSideMenuFn(){
    document.getElementById('sideMenu').classList.add('d-none');
}

// to search a product using the input field
function searchProduct() {
    var searchTerm = $(".search_field:visible").val().trim();
    window.location.assign(base_url + "products/search?q=" + searchTerm);
}

// if enter is pressed on search_product field
$(".search_field").on("keydown",function search(e) {
    if(e.keyCode == 13) {
        searchProduct();
    }
});

/* place navbar exactly below the header since they are sticky and cannot have any extra gap */
/* function positionNavbarBelowHeader(){
    var headerHeight = $('.header-container').outerHeight();

    $('.navbar-container').css({
        'top':headerHeight + 'px !important'
    });

    $(".navbar-container").css("top", headerHeight + "px !important");

    alert(headerHeight);
} */

$(document).ready(function() {

    // positionNavbarBelowHeader();

    /* Automatically make the submenu open to the left side if not enough space on the right side */
    $('.dropdown-menu > li').hover(function() {
        var $submenu = $(this).find('.dropdown-submenu');
        var parentWidth = $(this).outerWidth();
        var parentOffset = $(this).offset().left;
        var windowWidth = $(window).width();
        
        if (parentOffset + parentWidth + $submenu.outerWidth() > windowWidth) {
        $submenu.addClass('dropdown-submenu-left');
        } else {
        $submenu.removeClass('dropdown-submenu-left');
        }
    });

    /* Password hide/reveal (signup/login forms) */
    let pass = document.querySelectorAll('.password-container');
    pass.forEach(passwordField => {
        let passInputs = passwordField.querySelectorAll('.form-control');
        let passToggle = passwordField.querySelector('.password-toggle > i');

        // A .password-container without a toggle icon used to throw here on
        // addEventListener, and because this runs inside the shared ready handler the
        // TypeError killed every statement after it - including the #modal-signin reset
        // below. Skip such a container instead of taking the whole file down with it.
        if (!passToggle || !passInputs.length) {
            return;
        }

        passToggle.addEventListener('click', () => {
            // cretzo-fixes.js (FIX 12) binds one delegated toggle for every
            // .password-field / .password-container on the page, and the login
            // markup carries BOTH classes - so this listener, that handler and
            // theme.passVisibility() all fired on a single click and the type
            // flipped back to where it started. Checked at click time rather than
            // at bind time: this file loads before cretzo-fixes.js, so the flag
            // does not exist yet while the listeners are being attached.
            if (window.cretzoPassToggleBound) return;
            let isPassword = passInputs[0].type === "password"; // Check the type of the first input

            passInputs.forEach(passInput => {
            passInput.type = isPassword ? "text" : "password";
            });

            if (isPassword) {
            passToggle.classList.remove('uil-eye');
            passToggle.classList.add('uil-eye-slash');
            } else {
            passToggle.classList.remove('uil-eye-slash');
            passToggle.classList.add('uil-eye');
            }
        });
    });


    // Reset 'Sign In' Modal Dialog when dialog is dismissed or cancelled
    $("#modal-signin").on("hidden.bs.modal", function () {
        $("#login_div").removeClass("d-none").siblings("section").addClass("d-none");
    });

});
          
/* $(window).resize(function(){
    positionNavbarBelowHeader();
}); */

/* Close chat panel on clicking outside */
document.addEventListener("click", function (event) {
    let panel = document.getElementById("chat-iframe");
    let chat_button = document.getElementById("chat-button");
    if (panel && chat_button && $("#chat-iframe").hasClass("opened")) {
        if (!panel.contains(event.target) && !chat_button.contains(event.target)) {
            $('#chat-button').trigger('click');
        }
    }
});
/* ============================================================
   Footer link columns (czfoot).
   The three columns render as <details open> so that with JS off,
   or if this never runs, every link is visible - a footer that
   hides its own navigation is worse than a long one. On a phone
   they are collapsed here, where an expanded 24-link stack would
   be most of the screen, and re-opened if the viewport grows.
   ============================================================ */
(function () {
    var cols = document.querySelectorAll('.czfoot__col');
    if (!cols.length) return;

    var mq = window.matchMedia('(max-width: 767px)');

    function sync(e) {
        var narrow = e.matches;
        Array.prototype.forEach.call(cols, function (col) {
            col.open = !narrow;
        });
    }

    sync(mq);
    /* addEventListener on a MediaQueryList is the modern form; older WebKit
       only has addListener. */
    if (mq.addEventListener) {
        mq.addEventListener('change', sync);
    } else if (mq.addListener) {
        mq.addListener(sync);
    }
})();
