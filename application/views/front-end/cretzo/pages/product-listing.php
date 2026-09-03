<input type="hidden" id="product-filters" value='<?= (!empty($filters)) ? escape_array($filters) : ""  ?>' data-key="<?= $filters_key ?>" />
<input type="hidden" id="brand-filters" value='<?= (!empty($brands)) ? escape_array($brands) : ""  ?>' data-key="<?= $filters_key ?>" />
<input type="hidden" id="category-filters" value='<?= (!empty($categories) ? ($categories) : "") ?>' data-key="<?= $filters_key ?>" />

<?php
// echo "<pre>";
// print_r($products['product'][0]['variants'][0]);
// die;

/* echo "<pre>";
print_r(count($products["product"]) . "\n");
print_r($total_rows);
die; */

/* echo "<pre>";
print_r($categories);
die; */

/* echo "<pre>";
print_r($products["product"]);
print_r(var_dump($links));
die; */

/* echo "<pre>";
print_r(var_dump($links));
die; */

?>

<!-- <div class="content-wrapper"> -->
<div>
    <section class="products-container">
        <div class="filter-container">

            <h1 class="filter-tag">Filter</h1>

            <?php /* Mobile sheet chrome (hidden on desktop by CSS).
                     The whole .filter-container becomes a bottom sheet below 1000px - see the
                     mobile block in product-listing-override.css - so it needs its own title bar
                     and a sticky action footer. The filter controls themselves are untouched:
                     they keep the same ids and classes product-listing.js already binds, which
                     is why the sheet is a restyle of this element rather than a second copy of
                     the form. */ ?>
            <div class="plp-sheet__head">
                <button type="button" class="plp-sheet__back" data-plp-close="filter" aria-label="Close filters">
                    <i class="uil uil-arrow-left"></i>
                </button>
                <span class="plp-sheet__title">Filters</span>
                <span class="plp-sheet__count" id="plp-filter-result"></span>
            </div>

            <?php if (isset($seller) && !empty($seller)) { ?>
                <div class="alert alert-info d-flex flex-column align-items-start p-3">
                    <div class="d-flex align-items-center mb-1 w-100">
                        <i class="uil uil-user fs-24 me-2"></i>
                        <p class="mb-0 text-wrap">Viewing products from this seller:</p>
                    </div>
                    <h5 id="seller-store-name" class="text-primary mb-2 orange fw-bold"><?= $seller[0]['store_name'] ?></h5>
                    <button class="btn btn-sm btn-outline-danger mt-auto w-100" onclick="removeSellerFilter()">
                        <i class="uil uil-times me-1"></i> Remove Filter
                    </button>
                </div>
            <?php } ?>

            <!-- bind here -->
            <div class="filter-container-inner">

                <div class="filter-label-section">
                    <h3 class="filter-label">Filters</h3>
                    <button id="clear-all-filters-btn" class="cretzo small-btn c-p px-4 text-s">Clear All</button>
                </div>

                <div class="filter-container-inner-section">
                    <?php if (!isset($is_category_page) || !$is_category_page): ?>
                    <div class="filter-section fs-category">
                        <h1 class="text-n filter-heading">Categories</h1>
                        <ul class="list filter-list">

                            <?php if (isset($categories)) {
                                
                                $selected_categories = [];
                                // Check if 'category' parameter is set in the URL
                                if (isset($_GET['category'])) {
                                    $selected_categories = explode('|', $_GET['category']);
                                    // If 'category' parameter contains only one value, put it into the array
                                    if (count($selected_categories) === 1 && $selected_categories[0] !== '') {
                                        $selected_categories = [$selected_categories[0]];
                                    }
                                }
                                
                                $categories_filter = json_decode(($categories), true);
                                foreach ($categories_filter as $key => $value) {
                                    $is_category_selected = in_array($value['id'], $selected_categories);
                                ?>

                                    <li class="filter-list-item">
                                        <input type="checkbox" data-value="<?= $value['id'] ?>" id="<?= $value['id'] ?>" value="" <?= $is_category_selected ? "checked" : "" ?>>
                                        <label class="text-s filter-name" for="<?= $value['id'] ?>">
                                            <?= $value['name'] ?>
                                        </label>
                                    </li>
                                
                                    <!-- <div class="form-check">
                                        <input class="form-check-input category" type="radio" name="categoryRadio" data-value="<?= $value['id'] ?>" id="<?= $value['id'] ?>" value="" checked>
                                        <label class="form-check-label" for="<?= $value['id'] ?>">
                                            <?= $value['name'] ?>
                                        </label>
                                    </div> -->
                                    
                                <?php } ?>
                            <?php  } ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                

                    <!-- slider -->
                    <div class="silder-container">
                        <h1 class="text-n filter-heading">Price</h1>

                        <?php
                            $minPrice = isset($products['min_price']) ? (float) $products['min_price'] : 0;
                            $maxPrice = isset($products['max_price']) ? (float) $products['max_price'] : 0;
                            $currentMinPrice = isset($_GET['min-price']) ? (float) $_GET['min-price'] : $minPrice;
                            $currentMaxPrice = isset($_GET['max-price']) ? (float) $_GET['max-price'] : $maxPrice;
                            $currentMinPrice = min(max($currentMinPrice, $minPrice), $maxPrice);
                            $currentMaxPrice = min(max($currentMaxPrice, $minPrice), $maxPrice);
                            if ($currentMaxPrice < $currentMinPrice) {
                                $currentMaxPrice = $maxPrice;
                            }
                            $priceSpan = ($maxPrice - $minPrice) ?: 1;
                            $leftPct   = (($currentMinPrice - $minPrice) / $priceSpan) * 100;
                            $rightPct  = 100 - ((($currentMaxPrice - $minPrice) / $priceSpan) * 100);
                            $priceStep = 10;
                        ?>

                        <div class="mt-4 price-slider" data-min="<?= $minPrice ?>" data-max="<?= $maxPrice ?>" data-step="<?= $priceStep ?>">
                            <div class="slider">
                                <div class="progress" style="left: <?= $leftPct ?>%; right: <?= $rightPct ?>%; "></div>
                            </div>
                            <div class="range-input">
                                <input type="range" class="range-min filter-price-btn" name="price" min="<?= $minPrice ?>" max="<?= $maxPrice ?>" value="<?= $currentMinPrice ?>" step="<?= $priceStep ?>">
                                <input type="range" class="range-max filter-price-btn" name="price" min="<?= $minPrice ?>" max="<?= $maxPrice ?>" value="<?= $currentMaxPrice ?>" step="<?= $priceStep ?>">
                            </div>
                            <div class="price-input">
                                <div class="silder-field">
                                    <span class="text-n">Min</span>
                                    <input class="input-min filter-price-btn text-s" type="number" value="<?= round($currentMinPrice) ?>">
                                </div>
                                <div class="separator"></div>
                                <div class="silder-field">
                                    <span class="text-n">Max</span>
                                    <input class="input-max filter-price-btn text-s" type="number" value="<?= round($currentMaxPrice) ?>">
                                </div>
                            </div>
                        </div>

                    </div>

                    <?php if (isset($brands) && !empty($brands)) { ?>

                        <div class="filter-section fs-brand">
                            <h1 class="text-n filter-heading">Brand</h1>
                            <ul class="list filter-list">
                                <?php

                                    $selected_brands = [];
                                    // Check if 'brand' parameter is set in the URL
                                    if (isset($_GET['brand'])) {
                                        $selected_brands = explode('|', $_GET['brand']);
                                        // If 'brand' parameter contains only one value, put it into the array
                                        if (count($selected_brands) === 1 && $selected_brands[0] !== '') {
                                            $selected_brands = [$selected_brands[0]];
                                        }
                                    }

                                    $brands_filter = json_decode(($brands), true);
                                    foreach ($brands_filter as $key => $value) { 
                                        $is_brand_selected = in_array($value['brand_slug'], $selected_brands);
                                    ?>
                                        <li class="filter-list-item">
                                            <input type="checkbox" data-value="<?= $value['brand_slug'] ?>" id="<?= $value['brand_id'] ?>-brand" <?= $is_brand_selected ? "checked" : "" ?>>
                                            <label class="text-s filter-name"> <?= $value['brand_name'] ?> </label>
                                            <!-- <img src="<?= base_url($value['brand_img']) ?>" alt="brand-logo" class="h-6"> -->
                                        </li>
                                    <?php } ?>
                            </ul>
                        </div>    
                    <?php } ?>

                    <?php if (isset($products['filters']) && !empty($products['filters'])) {
                        
                        foreach ($products['filters'] as $key => $row) {
                            $row_attr_name = str_replace(' ', '-', $row['name']);
                            $attribute_name = isset($_GET[strtolower('filter-' . $row_attr_name)]) ? $this->input->get(strtolower('filter-' . $row_attr_name), true) : 'null';
                            $selected_attributes = explode('|', $attribute_name);
                            $attribute_values = explode(',', $row['attribute_values']);
                            $attribute_values_id = explode(',', $row['attribute_values_id']);
                            $swatche_values = explode(',', $row['swatche_value'])
                        ?>

                            <div class="filter-section fs-attr">
                                <h1 class="text-n filter-heading"> <?= html_escape($row['name']) ?> </h1>
                                <ul class="list filter-list">
                                    <?php foreach ($attribute_values as $key => $value) {
                                        // print_r($swatche_values[$key]);
                                        // $value = strtolower($value);
                                        // print_r($value);
                                        // print_r($row);
                                        // die;
                                        $is_filter_selected = in_array(strtolower($value), $selected_attributes);
                                    ?>
                                        <li class="filter-list-item">
                                            <input type="checkbox" data-value="<?= strtolower($value) ?>" <?= $is_filter_selected ? "checked" : "" ?>>
                                            <?php 
                                                if($row['name'] === 'Color' || preg_match('/^#[a-f0-9]{6}$/i', $swatche_values[$key]) ){ ?>
                                                    <span class="ml-1 product-c" style="background-color:<?= $swatche_values[$key] ?>"></span>
                                            <?php 
                                                }
                                            ?>
                                            
                                            <label class="text-s filter-name"> <?= $value ?> </label>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>

                        <?php } ?>
                    <?php } ?>

                </div>

            </div>

            <?php /* Sticky sheet footer (mobile only). "Clear all" proxies to the existing
                     #clear-all-filters-btn so there is one clear-filters implementation, and
                     "Apply" only closes the sheet - filter changes already reload the grid over
                     AJAX the moment a box is ticked, so there is nothing left to submit. */ ?>
            <div class="plp-sheet__foot">
                <button type="button" class="plp-sheet__btn plp-sheet__btn--ghost" id="plp-filter-clear">Clear all</button>
                <button type="button" class="plp-sheet__btn plp-sheet__btn--primary" data-plp-close="filter">Apply</button>
            </div>

        </div>

        <div class="product-card-container">

            <div class="product-filter">
                <p class="text-n op-6">Showing <?= ($per_page*$page_no - $per_page + 1) . '-' . ($per_page*$page_no - $per_page + count($products['product'])) ?> of <?= $total_rows ?> </p>
                <div class="flex-1"></div>
                
                <p class="text-n op-6 plp-sortlabel"> Sort By: </p>
                <style>
                    .custom-select-wrapper {
                        position: relative;
                        display: inline-block;
                    }
                    .custom-select-trigger {
                        cursor: pointer;
                        user-select: none;
                        min-width: 170px;
                    }
                    .custom-select-options {
                        list-style: none;
                        margin: 4px 0 0;
                        padding: 4px 0;
                        position: absolute;
                        top: 100%;
                        right: 0;
                        min-width: 97%;
                        background: #fff;
                        border: 1px solid rgba(16, 9, 1, 0.15);
                        border-radius: 4px;
                        box-shadow: 0 4px 12px rgba(30, 34, 40, 0.12);
                        z-index: 50;
                        display: none;
                        max-height: 260px;
                        overflow-y: auto;
                    }
                    .custom-select-wrapper.open .custom-select-options {
                        display: block;
                    }
                    .custom-select-options li {
                        padding: 3px 14px;
                        color: #333;
                        cursor: pointer;
                        white-space: nowrap;
                    }
                    .custom-select-options li:hover,
                    .custom-select-options li.selected {
                        background: #F2822E;
                        color: #fff;
                    }
                </style>
                <div class="custom-select-wrapper" id="sort-select-wrapper">
                    <div class="sort-select custom-select-trigger" id="sort-select-trigger" tabindex="0" role="button" aria-haspopup="listbox" aria-expanded="false"></div>
                    <ul class="custom-select-options" id="sort-select-options" role="listbox"></ul>
                </div>
                <select id="product_sort_by" class="sort-select" style="display:none;">
                    <!-- <option><?= !empty($this->lang->line('relevance')) ? $this->lang->line('relevance') : 'Relevance' ?></option> -->
                    <option><?= !empty($this->lang->line('recommended')) ? $this->lang->line('recommended') : 'Recommended' ?></option>
                    <option value="top-rated" <?= ($this->input->get('sort') == "top-rated") ? 'selected' : '' ?>><?= !empty($this->lang->line('top_rated')) ? $this->lang->line('top_rated') : 'Top Rated' ?></option>
                    <option value="date-desc" <?= ($this->input->get('sort') == "date-desc") ? 'selected' : '' ?>><?= !empty($this->lang->line('newest_first')) ? $this->lang->line('newest_first') : 'Newest First' ?></option>
                    <option value="date-asc" <?= ($this->input->get('sort') == "date-asc") ? 'selected' : '' ?>><?= !empty($this->lang->line('oldest_first')) ? $this->lang->line('oldest_first') : 'Oldest First' ?></option>
                    <option value="price-asc" <?= ($this->input->get('sort') == "price-asc") ? 'selected' : '' ?>><?= !empty($this->lang->line('price_low_to_high')) ? $this->lang->line('price_low_to_high') : 'Price - Low To High' ?></option>
                    <option value="price-desc" <?= ($this->input->get('sort') == "price-desc") ? 'selected' : '' ?>><?= !empty($this->lang->line('price_high_to_low')) ? $this->lang->line('price_high_to_low') : 'Price - High To Low' ?></option>
                </select>

                <!-- <div class="d-md-grid ele-wrapper">
                    <div class="d-flex form-select-wrapper pl-0">
                        <label for="product_sort_by"></label>
                        <select id="product_sort_by" class="form-select">
                            <option><?= !empty($this->lang->line('relevance')) ? $this->lang->line('relevance') : 'Relevance' ?></option>
                            <option value="top-rated" <?= ($this->input->get('sort') == "top-rated") ? 'selected' : '' ?>><?= !empty($this->lang->line('top_rated')) ? $this->lang->line('top_rated') : 'Top Rated' ?></option>
                            <option value="date-desc" <?= ($this->input->get('sort') == "date-desc") ? 'selected' : '' ?>><?= !empty($this->lang->line('newest_first')) ? $this->lang->line('newest_first') : 'Newest First' ?></option>
                            <option value="date-asc" <?= ($this->input->get('sort') == "date-asc") ? 'selected' : '' ?>><?= !empty($this->lang->line('oldest_first')) ? $this->lang->line('oldest_first') : 'Oldest First' ?></option>
                            <option value="price-asc" <?= ($this->input->get('sort') == "price-asc") ? 'selected' : '' ?>><?= !empty($this->lang->line('price_low_to_high')) ? $this->lang->line('price_low_to_high') : 'Price - Low To High' ?></option>
                            <option value="price-desc" <?= ($this->input->get('sort') == "price-desc") ? 'selected' : '' ?>><?= !empty($this->lang->line('price_high_to_low')) ? $this->lang->line('price_high_to_low') : 'Price - High To Low' ?></option>
                        </select>
                    </div>
                </div> -->

            </div>

            <div class="products" id="productList">
                

            </div>

            <!-- No products found - TODO: DESIGN NEEDS TO BE IMPROVED -->
            <?php if ((!isset($sub_categories) || empty($sub_categories)) && (!isset($products) || empty($products['product']))) { ?>
                <div class="ta-c mt-4">
                    <h1 class="h2 ta-c">No Products Found.</h1>
                    <a href="<?= base_url('products') ?>" class="cretzo btn btn-dark btn-sm rounded-pill btn-warning"><?= !empty($this->lang->line('go_to_shop')) ? $this->lang->line('go_to_shop') : 'Go to Shop' ?></a>
                </div>
            <?php } ?>

            <?php // Kept in the DOM even when empty: product-listing.js renders the AJAX
                  // pager into this element, so it has to exist before the first reply.
                  // .cz-pager-nav:empty in cretzo-fixes.css collapses its margins. ?>
            <nav id="products-pagination-nav" class="cz-pager-nav" aria-label="<?= storefront_pagination_label('products') ?>"><?= (isset($links)) ? $links : '' ?></nav>

        
        </div>

    </section>

    <div id="bg-overlay"></div>

    <?php /* ------------------------------------------------------- mobile sort/filter --
     *
     * Below 1000px the sort <select> and the rotated "Filter" tab are replaced by a fixed
     * bottom action bar and two sheets, the pattern every large storefront uses on a phone.
     * Everything here is display:none above the breakpoint, and the desktop sidebar and
     * sort dropdown are left exactly as they were.
     */ ?>
    <div class="plp-scrim" id="plp-scrim" hidden></div>

    <nav class="plp-mbar" aria-label="Sort and filter">
        <button type="button" class="plp-mbar__btn" id="plp-sort-open">
            <i class="uil uil-sort-amount-down"></i> <span>Sort</span>
        </button>
        <span class="plp-mbar__sep" aria-hidden="true"></span>
        <button type="button" class="plp-mbar__btn" id="plp-filter-open">
            <i class="uil uil-filter"></i> <span>Filter</span>
            <span class="plp-mbar__badge" id="plp-filter-badge" hidden>0</span>
        </button>
    </nav>

    <div class="plp-sheet plp-sheet--sort" id="plp-sort-sheet" hidden
         role="dialog" aria-modal="true" aria-labelledby="plp-sort-title">
        <div class="plp-sheet__head">
            <span class="plp-sheet__title" id="plp-sort-title">Sort by</span>
            <button type="button" class="plp-sheet__x" data-plp-close="sort" aria-label="Close">
                <i class="uil uil-times"></i>
            </button>
        </div>
        <?php /* Options are cloned from #product_sort_by at runtime so this list can never
                 drift out of step with the real <select> (or its translated labels). */ ?>
        <ul class="plp-sortlist" id="plp-sortlist" role="radiogroup" aria-labelledby="plp-sort-title"></ul>
    </div>

</div>

<script>
(function() {
    function initCustomSortDropdown() {
        var $wrapper = $('#sort-select-wrapper');
        var $trigger = $('#sort-select-trigger');
        var $list = $('#sort-select-options');

        if (!$wrapper.length) return;

        function buildOptions() {
            var $select = $('#product_sort_by');
            $list.empty();
            $select.find('option').each(function() {
                var $opt = $(this);
                var $li = $('<li></li>')
                    .text($opt.text())
                    .attr('data-value', $opt.attr('value') || '')
                    .attr('role', 'option');
                if ($opt.is(':selected')) {
                    $li.addClass('selected');
                    $trigger.text($opt.text());
                }
                $list.append($li);
            });
        }

        buildOptions();

        $trigger.on('click', function() {
            var isOpen = $wrapper.toggleClass('open').hasClass('open');
            $trigger.attr('aria-expanded', isOpen);
        });

        $list.on('click', 'li', function() {
            var value = $(this).attr('data-value');
            var text = $(this).text();

            $list.find('li').removeClass('selected');
            $(this).addClass('selected');
            $trigger.text(text);

            $('#product_sort_by').val(value).trigger('change');

            $wrapper.removeClass('open');
            $trigger.attr('aria-expanded', false);
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#sort-select-wrapper').length) {
                $wrapper.removeClass('open');
                $trigger.attr('aria-expanded', false);
            }
        });

        $trigger.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $trigger.trigger('click');
            } else if (e.key === 'Escape') {
                $wrapper.removeClass('open');
                $trigger.attr('aria-expanded', false);
            }
        });
    }

    /* ------------------------------------------------------------------ mobile sheets
     *
     * Bottom-bar Sort/Filter for narrow screens (1000px and below). Both sheets drive
     * the SAME controls the desktop UI drives - the sort sheet writes to
     * #product_sort_by and fires its change handler, and the filter sheet IS the real
     * .filter-container - so no filtering or sorting logic is duplicated here. This is
     * presentation only.
     */
    function initMobileSheets() {
        var BREAKPOINT = 1000;
        var $body = $('body');
        var $scrim = $('#plp-scrim');
        var $sortSheet = $('#plp-sort-sheet');
        var $filterSheet = $('.filter-container');
        var $sortList = $('#plp-sortlist');

        if (!$scrim.length) { return; }

        function isMobile() { return $(window).width() <= BREAKPOINT; }

        /* The sheets animate with a transform, so they cannot start at display:none or the
         * first frame would jump. The sort sheet is un-hidden first, then given .is-open on
         * the next frame so the transition actually runs. */
        function openSheet(which) {
            var $sheet = (which === 'sort') ? $sortSheet : $filterSheet;
            closeSheets(true);
            $scrim.prop('hidden', false);
            if (which === 'sort') {
                buildSortList();
                $sheet.prop('hidden', false);
            }
            $body.addClass('plp-sheet-open');
            requestAnimationFrame(function () {
                $scrim.addClass('is-open');
                $sheet.addClass(which === 'sort' ? 'is-open' : 'active');
            });
        }

        function closeSheets(silent) {
            $sortSheet.removeClass('is-open');
            /* .active is the class product-listing.js and the #bg-overlay handler already
             * use for the filter panel; reusing it keeps those handlers in agreement with
             * this one instead of fighting it. */
            $filterSheet.removeClass('active');
            $('#bg-overlay').removeClass('active');
            $scrim.removeClass('is-open');
            $body.removeClass('plp-sheet-open');
            if (!silent) {
                // Wait out the slide-down before taking it back out of the layout.
                setTimeout(function () {
                    if (!$sortSheet.hasClass('is-open')) { $sortSheet.prop('hidden', true); }
                    if (!$scrim.hasClass('is-open')) { $scrim.prop('hidden', true); }
                }, 300);
            }
        }

        /* Mirror of the real select. Rebuilt on every open, because product-listing.js
         * replaces that element with a clone on ready and updateURL() can change which
         * option is selected. */
        function buildSortList() {
            var $select = $('#product_sort_by');
            $sortList.empty();
            $select.find('option').each(function (i) {
                var $opt = $(this);
                $('<li></li>')
                    .attr('role', 'radio')
                    .attr('tabindex', '0')
                    .attr('aria-checked', $opt.is(':selected') ? 'true' : 'false')
                    .attr('data-value', $opt.attr('value') || '')
                    .attr('data-index', i)
                    .addClass($opt.is(':selected') ? 'is-selected' : '')
                    .text($opt.text())
                    .appendTo($sortList);
            });
        }

        /* Number of filter GROUPS in play (categories, brand, price, each attribute),
         * which is what the badge on the bar reports. */
        function activeFilterCount() {
            var count = 0;
            $('.filter-section').each(function () {
                if ($(this).find('input[type="checkbox"]:checked').length) { count++; }
            });
            var params = new URLSearchParams(window.location.search);
            if (params.get('min-price') || params.get('max-price')) { count++; }
            return count;
        }

        function refreshBadge() {
            var n = activeFilterCount();
            var $badge = $('#plp-filter-badge');
            if (n > 0) {
                $badge.text(n).prop('hidden', false);
            } else {
                $badge.prop('hidden', true);
            }
        }

        function refreshSheetCount() {
            // The same number the toolbar shows, echoed in the sheet header so the effect
            // of a filter is visible without closing the sheet.
            var text = $('.product-filter .text-n.op-6').first().text();
            var match = text.match(/of\s+([\d,]+)/i);
            $('#plp-filter-result').text(match ? match[1] + ' products' : '');
        }

        $('#plp-sort-open').on('click', function () { openSheet('sort'); });
        $('#plp-filter-open').on('click', function () {
            refreshSheetCount();
            openSheet('filter');
        });

        $scrim.on('click', function () { closeSheets(false); });
        $(document).on('click', '[data-plp-close]', function () { closeSheets(false); });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') { closeSheets(false); }
        });

        function chooseSort($li) {
            var index = parseInt($li.attr('data-index'), 10);
            var $select = $('#product_sort_by');
            $sortList.find('li').removeClass('is-selected').attr('aria-checked', 'false');
            $li.addClass('is-selected').attr('aria-checked', 'true');

            /* Selected by index rather than by value: the first option ("Recommended") has
             * no value attribute at all, so .val('') would not select it. */
            $select.prop('selectedIndex', index).trigger('change');

            // Keep the desktop dropdown's own label in step, in case of a resize.
            $('#sort-select-trigger').text($li.text());
            closeSheets(false);
        }

        $sortList.on('click', 'li', function () { chooseSort($(this)); });
        $sortList.on('keydown', 'li', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                chooseSort($(this));
            }
        });

        /* Proxies to the one real clear-filters button so there is a single
         * clear-filters implementation. That button only rewrites the URL and reloads the
         * grid, so the boxes have to be unticked here. */
        $('#plp-filter-clear').on('click', function () {
            $('.filter-list-item input[type="checkbox"]:checked').prop('checked', false);
            $('#clear-all-filters-btn').trigger('click');
            refreshBadge();
            setTimeout(refreshSheetCount, 700);
        });

        $(document).on('change', '.filter-list-item input[type="checkbox"]', function () {
            refreshBadge();
            // The count comes from the AJAX reply, so read it after that has landed.
            setTimeout(refreshSheetCount, 700);
        });

        // A resize past the breakpoint must not leave a sheet stranded over the desktop layout.
        $(window).on('resize', function () {
            if (!isMobile()) { closeSheets(false); }
        });

        refreshBadge();
    }

    $(document).ready(function() {
        // Deferred so this runs after any other script that clones/replaces
        // #product_sort_by on document ready (see product-listing.js).
        setTimeout(function () {
            initCustomSortDropdown();
            initMobileSheets();
        }, 0);
    });
})();
</script>


<?php
    function generateDiscountPercentageElement($product) {
        $discountPercentage = 0;

        // Check if special price and regular price are set and different
        if (isset($product['variants'][0]['special_price']) && isset($product['variants'][0]['price'])) {
            $specialPrice = floatval($product['variants'][0]['special_price']);
            $regularPrice = floatval($product['variants'][0]['price']);

            if ($specialPrice < $regularPrice) {
                $discountPercentage = round((($regularPrice - $specialPrice) / $regularPrice) * 100);
            }
        }

        // Return discount percentage element if applicable
        if ($discountPercentage > 0) {
            return '<div class="off-container">
                        <p class="text-s fw-b">' . $discountPercentage . '% off</p>
                    </div>';
        } else {
            return '';
        }
    }
    
    function generateStarRatingElement($product) {
        $rounded_rating = number_format($product['rating'], 1);
        $star_image = base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png');

        return '<div class="rating-container op-8">
                    <p class="text-xxs">' . $rounded_rating . '</p>
                    <img class="star-icon" src="' . $star_image . '" >
                </div>';
    }

    function generatePriceElement($product_row, $textStyle = "text-es"){

        $discounted_price = $product_row['variants'][0]['special_price'];
        $price = $product_row['variants'][0]['price'];

        $discountPercentage = 0;
        if ($discounted_price < $price) {
            $discountPercentage = round((($price - $discounted_price) / $price) * 100);
        }
        
        // $currencyText = $settings['currency'];
        $currencyText = "₹";

        if (($discounted_price < $price) && ($discounted_price != 0)) {
            return '
            <p class="price-container ta-c no-wrap ' . $textStyle . '">
                <span class="discounted-price no-wrap">'
                    . $currencyText . number_format($discounted_price, 0) .
                '</span>
                <span class="original-price op-6 no-wrap">'
                    . $currencyText . number_format($price, 0) .
                '</span>
                <span class="off-percent fw-b no-wrap">'
                    . $discountPercentage . "% OFF" .
                '</span>
            </p>';
        } else {
            return '
            <p class="ta-c no-wrap' . $textStyle . '">
                <span class="discounted-price no-wrap">'
                    . $currencyText . number_format($price, 0) .
                '</span>
            </p>';
        }

    }
?>