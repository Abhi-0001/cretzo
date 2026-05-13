$(document).ready(function () {
    let $searchInput = $("#search-input");
    let $searchBtn = $("#search-btn");
    let $clearSearch = $("#clear-search");

    // Show or hide clear button based on input value
    $clearSearch.toggle($searchInput.val().trim().length > 0);

    // Handle search button click
    $searchBtn.click(function () {
        let query = $searchInput.val().trim();
        if (query !== "") {
            window.location.href = window.location.pathname + "?search=" + encodeURIComponent(query);
        }
    });

    // Handle Enter key press in input field
    $searchInput.keypress(function (event) {
        if (event.which === 13) { // Enter key
            event.preventDefault();
            $searchBtn.click();
        }
    });

    // Handle clear button click
    $clearSearch.click(function () {
        $searchInput.val("");
        window.location.href = window.location.pathname; // Reload page without search
    });
});
