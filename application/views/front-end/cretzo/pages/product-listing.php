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

                        <!-- <range-slider id="price-range-input" name="price" min="<?=$products['min_price'] ?>" max="<?= $products['max_price'] ?>" value="<?= isset($_GET['min-price']) ? $_GET['min-price'] : $products['min_price'] ?>-<?= isset($_GET['max-price']) ? $_GET['max-price'] : $products['max_price'] ?>" step="10">
                            <-- <input class="range" type="range" min="0" max="100" value="10" step="10"/> ->
                            <-- <input class="range" type="range" min="0" max="100" value="90" step="10"/> ->
                            <-- <input class="filler" disabled type="range"/> ->
                        </range-slider> -->

                        <!-- <div class="slider">
                            <p class="text-s">Rs.<?= isset($_GET['min-price']) ? $_GET['min-price'] : $products['min_price'] ?></p>
                            <-- <input class="slider-input" type="range" min="<?= isset($_GET['min-price']) ? $_GET['min-price'] : $products['min_price'] ?>" max="<?= $products['max_price'] ?>" value="<?= isset($_GET['max-price']) ? $_GET['max-price'] : $products['max_price'] ?>" step="100"> ->
                            <p class="text-s"> Rs.<?= isset($_GET['max-price']) ? $_GET['max-price'] : $products['max_price'] ?> </p>
                        </div> -->

                        <?php 
                            $currentMinPrice = isset($_GET['min-price']) ? $_GET['min-price'] : $products['min_price'];
                            $currentMaxPrice = isset($_GET['max-price']) ? $_GET['max-price'] : $products['max_price'];
                            $minPrice = $products['min_price'];
                            $maxPrice = $products['max_price'];
                        ?>

                        <div class="mt-4">
                            <div class="slider">
                                <div class="progress" style="left: <?= ($currentMinPrice / $maxPrice) * 100 ?>%; right: <?= 100 - ($currentMaxPrice / $maxPrice) * 100 ?>%; "></div>
                            </div>
                            <div class="range-input">
                                <input type="range" class="range-min filter-price-btn" name="price" min="<?=$minPrice ?>" max="<?= $maxPrice ?>" value="<?= $currentMinPrice ?>" step="10">
                                <input type="range" class="range-max filter-price-btn" name="price" min="<?=$minPrice ?>" max="<?= $maxPrice ?>" value="<?= $currentMaxPrice ?>" step="10">
                            </div>
                            <div class="price-input">
                                <div class="silder-field">
                                    <span class="text-n">Min</span>
                                    <input class="text-s filter-price-btn" type="number" class="input-min" value="<?= $currentMinPrice ?>">
                                    <!--<button id="filter-price-btn" class="small-btn c-p">Filter</button>-->
                                </div>
                                <div class="separator"></div>
                                <div class="silder-field">
                                    <span class="text-n">Max</span>
                                    <input class="text-s filter-price-btn" type="number" class="input-max" value="<?= $currentMaxPrice ?>">
                                    <!--<button id="clear-filter-price-btn" class="small-btn c-p px-4" disabled>Clear</button>-->
                                </div>
                            </div>
                        </div>

                        <!-- <button id="filter-price-btn" class="small-btn c-p">Filter</button> -->

                        <!-- <button id="clear-filter-price-btn" class="small-btn c-p px-4" disabled>Clear</button> -->

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
            
        </div>

        <div class="product-card-container">

            <div class="product-filter">
                <p class="text-n op-6">Showing <?= ($per_page*$page_no - $per_page + 1) . '-' . ($per_page*$page_no - $per_page + count($products['product'])) ?> of <?= $total_rows ?> </p>
                <div class="flex-1"></div>
                
                <p class="text-n op-6"> Sort By: </p>
                <select id="product_sort_by" class="sort-select">
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

            <!-- pager removed – infinite scroll handles loading -->

        
        </div>

    </section>

    <div id="bg-overlay"></div>

</div>

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
        $currencyText = "Rs. ";

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