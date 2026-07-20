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

        passToggle.addEventListener('click', () => {
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

/* Close chat modal on clicking outside */
document.addEventListener("click", function (event) {
    let modal = document.getElementById("quick-view");
    let chat_button = document.getElementById("chat-button");
    if (modal && $("#chat-iframe").hasClass("opened")) {
        if (!modal.contains(event.target) && !chat_button.contains(event.target)) {
            $('#chat-button').trigger('click');
        }
    }
});