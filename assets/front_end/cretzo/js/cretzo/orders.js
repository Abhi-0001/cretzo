$(document).ready(function () {
    let $searchInput = $("#search-input");
    let $searchBtn = $("#search-btn");
    let $clearSearch = $("#clear-search");
    let $startDateInput = $("#start-date-input");
    let $endDateInput = $("#end-date-input");
    let $resetFilterBtn = $("#reset-filter-btn");
    let searchDebounceTimer;

    function buildOrdersUrl() {
        let params = new URLSearchParams(window.location.search);
        let query = $searchInput.val().trim();
        let startDate = $startDateInput.val();
        let endDate = $endDateInput.val();

        params.delete("search");
        params.delete("start_date");
        params.delete("end_date");
        params.delete("page");

        if (query !== "") {
            params.set("search", query);
        }
        if (startDate !== "") {
            params.set("start_date", startDate);
        }
        if (endDate !== "") {
            params.set("end_date", endDate);
        }

        let queryString = params.toString();
        return window.location.pathname + (queryString ? "?" + queryString : "");
    }

    function applyFilters() {
        window.location.href = buildOrdersUrl();
    }

    // Show or hide clear button based on input value
    $clearSearch.toggle($searchInput.val().trim().length > 0);

    // Handle search/apply button click
    $searchBtn.click(function () {
        applyFilters();
    });

    // Live search with debounce
    $searchInput.on("input", function () {
        $clearSearch.toggle($searchInput.val().trim().length > 0);
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function () {
            applyFilters();
        }, 450);
    });

    // Handle Enter key press in input field (instant)
    $searchInput.on("keypress", function (event) {
        if (event.which === 13) {
            event.preventDefault();
            clearTimeout(searchDebounceTimer);
            applyFilters();
        }
    });

    // Handle clear button click
    $clearSearch.click(function () {
        $searchInput.val("");
        applyFilters();
    });

    // Date filter events
    $startDateInput.on("change", function () {
        applyFilters();
    });

    $endDateInput.on("change", function () {
        applyFilters();
    });

    // Reset all filters
    $resetFilterBtn.on("click", function () {
        $searchInput.val("");
        $startDateInput.val("");
        $endDateInput.val("");
        window.location.href = window.location.pathname;
    });
});
