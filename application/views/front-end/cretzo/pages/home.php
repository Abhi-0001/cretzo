<!-- <section class="slider container"> -->
<section class="slider">
    <div class="pb-md-1">
        <!--/.row -->
        <!-- <div class="row align-items-center"> -->
        <div class="swiper-container swiper-slide-container overflow-hidden" data-margin="30" data-nav="true" data-dots="true" data-items-xl="3" data-items-md="2" data-items-xs="1">
            <div class="swiper-wrapper">
                <?php if (isset($sliders) && !empty($sliders)) { ?>
                    <?php foreach ($sliders as $row) { ?>
                        <div class="swiper-slide">
                            <div class="slide-img">
                                <a href="<?= $row['link'] ?>">
                                    <img src="<?= base_url($row['image']) ?>" alt="Offer Slider" style="object-fit: cover;">
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <!-- Add Pagination -->
        <div class="swiper-controls">
            <div class="swiper-pagination slide-swiper-pagination swiper-pagination-clickable swiper-pagination-bullets swiper-pagination-horizontal">
                <span class="swiper-pagination-bullet" tabindex="0" role="button" aria-label="Go to slide 1"></span>
                <span class="swiper-pagination-bullet" tabindex="0" role="button" aria-label="Go to slide 2"></span>
                <span class="swiper-pagination-bullet swiper-pagination-bullet-active" tabindex="0" role="button" aria-label="Go to slide 3" aria-current="true"></span>
            </div>
        </div>
    </div>
</section>
<!-- sections -->





<!-- !!! BRANDS SECTION !!! (contains swiper/slider) we can use this later to implement swiper/slider to featured section cards -->

<!-- <?php if (isset($brands) && !empty($brands) && $brands != []) { ?>
    <section class="mt-3">
        <div class="container pb-md-1 pt-md-14 py-lg-1 overflow-hidden">
            <div class="row align-items-center">
                <div class="my-4 featured-section-title">
                    <div class="d-md-flex justify-content-md-between">
                        <div>
                            <h3 class="text-dark mb-0">Brands</h3>
                        </div>
                        <div>
                            <a href="<?= base_url('home/brands/') ?>" class="hover text-decoration-none">
                                <span> <?= !empty($this->lang->line('see_all')) ? $this->lang->line('see_all') : 'See All' ?></span>
                                <i class="uil uil-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!- Swiper ->
                <div class="swiper-container category-swiper " data-dots="true">
                    <div class="swiper-wrapper">
                        <?php
                        foreach ($brands as $key => $row) { ?>
                            <div class="swiper-slide swiper-slide-category">
                                <a href="<?= base_url('products?brand=' . html_escape($row['brand_slug'])) ?>" class="text-decoration-none">
                                    <img class="lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $row['brand_img'] ?>" alt="<?= html_escape($row['brand_name']) ?>" />
                                    <h6 class="fs-14 mb-0"><?= html_escape($row['brand_name']) ?></h6>
                                </a>
                            </div>
                        <?php }
                        ?>

                    </div>
                    <div class="swiper-controls">
                        <div class="swiper-pagination category-swiper-pagination swiper-pagination-clickable swiper-pagination-bullets swiper-pagination-horizontal">
                            <span class="swiper-pagination-bullet" tabindex="0" role="button" aria-label="Go to slide 1"></span>
                            <span class="swiper-pagination-bullet" tabindex="0" role="button" aria-label="Go to slide 2"></span>
                            <span class="swiper-pagination-bullet swiper-pagination-bullet-active" tabindex="0" role="button" aria-label="Go to slide 3" aria-current="true"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
<?php } ?> -->




<!-- sections -->

<!-- <section class="container main-content"> -->
<section class="main-content">

    <?php $offer_counter = 0;
    $offers =  get_offers();

    foreach ($sections as $count_key => $row) {
        if (!empty($row['product_details'])) {

            // <!-- 'Cretzo Trending' Style Design -->
            if ($row['style'] == 'cretzo_trending') {
                if ($count_key != 0) {
                    if (!empty($offers) && !empty($offers[$count_key - 1])) { ?>
                        <div class="offer-img">
                            <a href="<?= $offers[$count_key - 1]['link'] ?>">
                                <img class="img-fluid lazy my-4 rounded offer-image" data-src="<?= base_url($offers[$count_key - 1]['image']) ?>" alt="Offer image" src="https://placehold.co/1290x268?text=Loading%20Offers..%20.&font=Montserrat" >
                            </a>
                        </div>
                <?php }
                }
                ?>
                <!-- trending deals section starts -->
                <section class="home-part-container">
                    <h1 class="heading-b container-heading"><?= ucfirst($row['title']) ?></h1>
                    <p class="text-n op-8 container-des"><?= $row['short_description']; ?></p>
                    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->
                    
                    <!-- container of card type one, trending products -->
                    <div class="card-container card-container-one">
                        <?php
                            $i = 0;
                            if (count($row['product_details']) > 0) {
                                foreach ($row['product_details'] as $key => $product_row) {
                                    // break after 6 products
                                    if ($i == 6) {
                                        break;
                                    }
                            ?>
                            <?php if (true/* $key != 0 */) { ?>
                                <div class="cretzo-card card-type-one product-card">
                                    <a class="card-url" href="<?= base_url('products/details/' . $product_row['slug']) ?>"></a>
                                    <div class="card-img">
                                        <button class="small-btn small-btn-light prod-tag prod-tag-top">Sale</button>
                                        <button class="small-btn small-btn-dark prod-tag prod-tag-bottom">New</button>
                                        <img class="card-img-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product_row['image_sm'] ?>" alt="<?= $product_row['name'] ?>">

                                        <?php echo generateStarRatingElement($product_row); ?>
                                        <?php echo generateDiscountPercentageElement($product_row); ?>
                                        
                                    </div>
                                    <div class="card-des">
                                        <p class="ta-c text-xs">
                                            <?= isset($product_row['tags'][0]) ? $product_row['tags'][0] : $product_row['category_name'] ?>
                                        </p>
                                        <h1 class="ta-c text-n product-name-no-wrap">
                                            <?= $product_row['name'] ?>
                                        </h1>
                                        

                                        <?php echo generatePriceElement($product_row); ?>

                                        <!-- set variables for add to cart functionality -->
                                        <?php
                                            if (count($product_row['variants']) <= 1) {
                                                $variant_id = $product_row['variants'][0]['id'];
                                                $modal = "";
                                            } else {
                                                $variant_id = "";
                                                $modal = "#quick-view";
                                            }
                                            $variant_price = ($product_row['variants'][0]['special_price'] > 0 && $product_row['variants'][0]['special_price'] != '') ? $product_row['variants'][0]['special_price'] : $product_row['variants'][0]['price'];
                                            $data_min = (isset($product_row['minimum_order_quantity']) && !empty($product_row['minimum_order_quantity'])) ? $product_row['minimum_order_quantity'] : 1;
                                            $data_step = (isset($product_row['minimum_order_quantity']) && !empty($product_row['quantity_step_size'])) ? $product_row['quantity_step_size'] : 1;
                                            $data_max = (isset($product_row['total_allowed_quantity']) && !empty($product_row['total_allowed_quantity'])) ? $product_row['total_allowed_quantity'] : 0;
                                        ?>
                                        <!-- add to cart button -->
                                        <a href="#" class="add_to_cart cart-btn text-decoration-none text-b" style="padding-bottom: 12px; padding-top: 12px;" data-product-id="<?= $product_row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-slug="<?= $product_row['slug'] ?>" data-product-title="<?= $product_row['name'] ?>" data-product-image="<?= $product_row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                                                <i class="uil uil-shopping-bag"></i>&nbsp;Add to Cart</a>

                                    </div>
                                </div>
                            <?php 
                                }
                                $i++;
                                } 
                            ?>
                        <?php } ?>
                    </div>

                    <?php echo generateSeeAllButton($row, $this); ?>

                    <h1 class="heading-b container-heading" style="margin-top: 2em;">Shop by Catagories</h1>
                    <p class="text-n op-6 container-des">Looking for something specific ?</p>
                    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->
                
                    <!-- container of card type two, categories (as defined in section page via admin panel) -->
                    <div class="card-container-two">
                        <?php
                            $i = 0;
                            /* echo "<pre>";
                            print_r(var_dump($row));
                            die; */
                            if (isset($row['categories_arr']) && count($row['categories_arr']) > 0) {
                                foreach ($row['categories_arr'] as $key => $section_category) {
                                    // break after 12 categories
                                    if ($i == 12) {
                                        break;
                                    }
                                ?>
                                <?php if (true/* $key != 0 */) { ?>
                                    
                                    <div class="cretzo-card card-type-seven">
                                        <a class="card-url" href="<?= base_url('products/category/' . html_escape($section_category['slug'])) ?>" class="text-decoration-none"></a>
                                        <div class="card-img">
                                            <img class="card-img-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $section_category['image'] ?>" alt="<?= html_escape($section_category['name']) ?>" />
                                        </div>
                                        <div class="card-des">
                                            <h1 class="ta-c text-n"><?= output_escaping(str_replace('\r\n', '&#13;&#10;', $section_category['name'])) ?></h1>
                                            <p class="ta-c text-s">Shop Now></p>
                                        </div>
                                    </div>
                                    
                                <?php 
                                }
                                $i++;
                                } 
                            ?>
                        <?php } ?>
                    </div>

                </section>
                <!-- trending deals section ends -->

            <!-- 'Cretzo Best Seller' Style Design -->
            <?php } else if ($row['style'] == 'cretzo_best_seller') {
                if ($count_key != 0) {
                    if (!empty($offers) && !empty($offers[$count_key - 1])) { ?>
                        <div class="offer-img">
                            <a href="<?= $offers[$count_key - 1]['link'] ?>">
                                <img class="img-fluid lazy my-4 rounded offer-image" data-src="<?= base_url($offers[$count_key - 1]['image']) ?>" alt="Offer image" src="https://placehold.co/1290x268?text=Loading%20Offers..%20.&font=Montserrat">
                            </a>
                        </div>
                <?php }
                }
                ?>
                
                <!-- best seller starts -->
                <section class="home-part-container">
                    <h1 class="heading-b container-heading"><?= ucfirst($row['title']) ?></h1>
                    <p class="text-n op-8 container-des"><?= $row['short_description']; ?></p>
                    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->

                    <div class="card-container-one">
                        <?php
                            $i = 0;
                            if (count($row['product_details']) > 0) {
                                foreach ($row['product_details'] as $key => $product_row) {
                                    // break after 12 products
                                    if ($i == 12) {
                                        break;
                                    }
                            ?>
                            <?php if (true/* $key != 0 */) { ?>
                                <!-- card type one -->
                                <div class="cretzo-card card-type-one">
                                    <a class="card-url" href="<?= base_url('products/details/' . $product_row['slug']) ?>"></a>
                                    <div class="card-img">
                                        <img class="card-img-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product_row['image_sm'] ?>" alt="<?= $product_row['name'] ?>" >

                                        <?php echo generateStarRatingElement($product_row); ?>
                                    </div>
                                    <div class="card-des">
                                        <p class="ta-c text-xs">NEW ARRIVALS</p>
                                        <h1 class="ta-c text-n product-name-no-wrap"><?= $product_row['name'] ?></h1>
                                        <p class="ta-c text-s"><span class="shopnow-btn">Shop Now</span></p>                                            
                                    </div>
                                </div>
                            <?php 
                            }
                            $i++;
                            } 
                            ?>
                        <?php } ?>
                    </div>

                    <?php echo generateSeeAllButton($row, $this); ?>

                </section>
                <!-- best seller ends -->

            <!-- 'Cretzo Featured' Style Design -->
            <?php } else if ($row['style'] == 'cretzo_featured') {
                if ($count_key != 0) {
                    if (!empty($offers) && !empty($offers[$count_key - 1])) { ?>
                        <div class="offer-img">
                            <a href="<?= $offers[$count_key - 1]['link'] ?>">
                                <img class="img-fluid lazy my-4 rounded offer-image" data-src="<?= base_url($offers[$count_key - 1]['image']) ?>" alt="Offer image" src="https://placehold.co/1290x268?text=Loading%20Offers..%20.&font=Montserrat" >
                            </a>
                        </div>
                <?php }
                }
                ?>
                
                <!-- featured deals starts -->
                <section class="home-part-container">
                    <h1 class="heading-b container-heading"><?= ucfirst($row['title']) ?></h1>
                    <p class="text-n op-8 container-des"><?= $row['short_description']; ?></p>
                    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->
                    <div class="card-container-one">
                        <?php
                            $i = 0;
                            if (count($row['product_details']) > 0) {
                                foreach ($row['product_details'] as $key => $product_row) {
                                    // break after 12 products
                                    if ($i == 12) {
                                        break;
                                    }
                            ?>
                            <?php if (true/* $key != 0 */) { ?>
                                <!-- card type four -->
                                <div class="cretzo-card card-type-four product-card">
                                    <a class="card-url" href="<?= base_url('products/details/' . $product_row['slug']) ?>"></a>
                                    <div class="card-img">
                                        <img class="card-img-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product_row['image_sm'] ?>" alt="<?= $product_row['name'] ?>" >

                                        <?php echo generateStarRatingElement($product_row); ?>
                                        <?php echo generateDiscountPercentageElement($product_row); ?>
                                        
                                    </div>
                                    <div class="card-des">
                                        <p class="ta-c text-xs">
                                            <?= isset($product_row['tags'][0]) ? $product_row['tags'][0] : $product_row['category_name'] ?>
                                        </p>
                                        <!-- <ul class="list color-option-list">
                                            <li class="color-option" style="background-color: green;"></li>
                                            <li class="color-option" style="background-color: blue;"></li>
                                            <li class="color-option" style="background-color: antiquewhite;"></li>
                                            <li class="color-option" style="background-color: yellow;"></li>
                                        </ul> -->
                                        <h1 class="ta-c text-s product-name-no-wrap"><?= $product_row['name'] ?></h1>

                                        <?php echo generatePriceElement($product_row); ?>

                                        <!-- set variables for add to cart functionality -->
                                        <?php
                                            if (count($product_row['variants']) <= 1) {
                                                $variant_id = $product_row['variants'][0]['id'];
                                                $modal = "";
                                            } else {
                                                $variant_id = "";
                                                $modal = "#quick-view";
                                            }
                                            $variant_price = ($product_row['variants'][0]['special_price'] > 0 && $product_row['variants'][0]['special_price'] != '') ? $product_row['variants'][0]['special_price'] : $product_row['variants'][0]['price'];
                                            $data_min = (isset($product_row['minimum_order_quantity']) && !empty($product_row['minimum_order_quantity'])) ? $product_row['minimum_order_quantity'] : 1;
                                            $data_step = (isset($product_row['minimum_order_quantity']) && !empty($product_row['quantity_step_size'])) ? $product_row['quantity_step_size'] : 1;
                                            $data_max = (isset($product_row['total_allowed_quantity']) && !empty($product_row['total_allowed_quantity'])) ? $product_row['total_allowed_quantity'] : 0;
                                        ?>

                                        <!-- add to cart button -->
                                        <a href="#" class="add_to_cart cart-btn text-decoration-none text-s" style="padding-bottom: 0.8em; padding-top: 12px;" data-product-id="<?= $product_row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-slug="<?= $product_row['slug'] ?>" data-product-title="<?= $product_row['name'] ?>" data-product-image="<?= $product_row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                                                <i class="uil uil-shopping-bag"></i>&nbsp;Add to Cart</a>
                                    </div>
                                </div>
                            <?php 
                            }
                            $i++;
                            } 
                            ?>
                        <?php } ?>
                    </div>
                </section>
                <!-- featured deals ends -->

            <!-- 'Cretzo New Arrivals' Style Design -->
            <?php } else if ($row['style'] == 'cretzo_new_arrivals') {
                if ($count_key != 0) {
                    if (!empty($offers) && !empty($offers[$count_key - 1])) { ?>
                        <div class="offer-img">
                            <a href="<?= $offers[$count_key - 1]['link'] ?>">
                                <img class="img-fluid lazy my-4 rounded offer-image" data-src="<?= base_url($offers[$count_key - 1]['image']) ?>" alt="Offer image" src="https://placehold.co/1290x268?text=Loading%20Offers..%20.&font=Montserrat" >
                            </a>
                        </div>
                <?php }
                }
                ?>
                
                <!-- new arrivals starts -->
                <section class="home-part-container">
                    <h1 class="heading-b container-heading"><?= ucfirst($row['title']) ?></h1>
                    <p class="text-n op-8 container-des"><?= $row['short_description']; ?></p>
                    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->
                    <div class="card-container-two">
                        <?php
                            $i = 0;
                            if (count($row['product_details']) > 0) {
                                foreach ($row['product_details'] as $key => $product_row) {
                                    // break after 12 products
                                    if ($i == 12) {
                                        break;
                                    }
                            ?>
                            <?php if (true/* $key != 0 */) { ?>
                                <!-- card type two -->
                                <div class="cretzo-card card-type-two product-card">
                                    <a class="card-url" href="<?= base_url('products/details/' . $product_row['slug']) ?>"></a>
                                    <div class="card-img">
                                        <img class="card-img-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product_row['image_sm'] ?>" alt="<?= $product_row['name'] ?>">

                                        <?php echo generateStarRatingElement($product_row); ?>
                                        <?php echo generateDiscountPercentageElement($product_row); ?>
                                        
                                    </div>
                                    <div class="card-des">
                                        <h1 class="ta-c text-s product-name-no-wrap"><?= $product_row['name'] ?></h1>

                                        <?php echo generatePriceElement($product_row, "text-xs"); ?>

                                        <!-- set variables for add to cart functionality -->
                                        <?php
                                            if (count($product_row['variants']) <= 1) {
                                                $variant_id = $product_row['variants'][0]['id'];
                                                $modal = "";
                                            } else {
                                                $variant_id = "";
                                                $modal = "#quick-view";
                                            }
                                            $variant_price = ($product_row['variants'][0]['special_price'] > 0 && $product_row['variants'][0]['special_price'] != '') ? $product_row['variants'][0]['special_price'] : $product_row['variants'][0]['price'];
                                            $data_min = (isset($product_row['minimum_order_quantity']) && !empty($product_row['minimum_order_quantity'])) ? $product_row['minimum_order_quantity'] : 1;
                                            $data_step = (isset($product_row['minimum_order_quantity']) && !empty($product_row['quantity_step_size'])) ? $product_row['quantity_step_size'] : 1;
                                            $data_max = (isset($product_row['total_allowed_quantity']) && !empty($product_row['total_allowed_quantity'])) ? $product_row['total_allowed_quantity'] : 0;
                                        ?>
                                        <!-- add to cart button -->
                                        <a href="#" class="add_to_cart cart-btn text-decoration-none text-s" style="padding-bottom: 0.8em; padding-top: 12px;" data-product-id="<?= $product_row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-slug="<?= $product_row['slug'] ?>" data-product-title="<?= $product_row['name'] ?>" data-product-image="<?= $product_row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                                                <i class="uil uil-shopping-bag"></i>&nbsp;Add to Cart</a>

                                    </div>
                                </div>
                            <?php 
                            }
                            $i++;
                            } 
                            ?>
                        <?php } ?>
                    </div>

                    <?php echo generateSeeAllButton($row, $this); ?>
                    
                </section>
                <!-- new arrivals ends -->

            <!-- 'Cretzo Special Picks' Style Design -->
            <?php } else if ($row['style'] == 'cretzo_special_picks') {
                if ($count_key != 0) {
                    if (!empty($offers) && !empty($offers[$count_key - 1])) { ?>
                        <div class="offer-img">
                            <a href="<?= $offers[$count_key - 1]['link'] ?>">
                                <img class="img-fluid lazy my-4 rounded offer-image" data-src="<?= base_url($offers[$count_key - 1]['image']) ?>" alt="Offer image" src="https://placehold.co/1290x268?text=Loading%20Offers..%20.&font=Montserrat" >
                            </a>
                        </div>
                <?php }
                }
                ?>
                
                <!-- special picks starts -->
                <section class="home-part-container">
                    <h1 class="heading-b container-heading"><?= ucfirst($row['title']) ?></h1>
                    <p class="text-n op-8 container-des"><?= $row['short_description']; ?></p>
                    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->

                    <div class="card-container-two">
                        <?php
                            $i = 0;
                            if (count($row['product_details']) > 0) {
                                foreach ($row['product_details'] as $key => $product_row) {
                                    // break after 8 products
                                    if ($i == 6) {
                                        break;
                                    }
                            ?>
                            <?php if (true/* $key != 0 */) { ?>
                                <!-- card type six -->
                                <div class="cretzo-card card-type-six product-card">
                                    <a class="card-url" href="<?= base_url('products/details/' . $product_row['slug']) ?>"></a>
                                    <div class="card-img">
                                        <img class="card-img-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product_row['image_sm'] ?>" alt="<?= $product_row['name'] ?>">
                                    </div>
                                    <div class="card-des">
                                        <h1 class="ta-c text-s product-name-no-wrap"><?= $product_row['name'] ?></h1>

                                        <!-- <?php if (($product_row['variants'][0]['special_price'] < $product_row['variants'][0]['price']) && ($product_row['variants'][0]['special_price'] != 0)) { ?>
                                            <p class="ta-c text-xs" style="margin-top: 4px;">
                                                <span class="discounted-price">
                                                    <?php echo $settings['currency'] ?>
                                                    <?php
                                                        $price = $product_row['variants'][0]['special_price'];
                                                        echo number_format($price, 2);
                                                    ?>
                                                </span>
                                                <span class="original-price op-6">
                                                    <?php echo $settings['currency'] ?>
                                                    <?php
                                                        $price = $product_row['variants'][0]['price'];
                                                        echo number_format($price, 2);
                                                    ?>
                                                </span>
                                            </p>
                                        <?php } else { ?>
                                            <p class="ta-c text-n">
                                                <span class="discounted-price">
                                                    <?php echo $settings['currency'] ?>
                                                    <?php
                                                        $price = $product_row['variants'][0]['price'];
                                                        echo number_format($price, 2);
                                                    ?>
                                                </span>
                                                <!-- <span class="original-price op-6">Rs. 2500</span> -->
                                            </p>
                                        <?php } ?> -->

                                        <!-- set variables for add to cart functionality -->
                                        <?php
                                            if (count($product_row['variants']) <= 1) {
                                                $variant_id = $product_row['variants'][0]['id'];
                                                $modal = "";
                                            } else {
                                                $variant_id = "";
                                                $modal = "#quick-view";
                                            }
                                            $variant_price = ($product_row['variants'][0]['special_price'] > 0 && $product_row['variants'][0]['special_price'] != '') ? $product_row['variants'][0]['special_price'] : $product_row['variants'][0]['price'];
                                            $data_min = (isset($product_row['minimum_order_quantity']) && !empty($product_row['minimum_order_quantity'])) ? $product_row['minimum_order_quantity'] : 1;
                                            $data_step = (isset($product_row['minimum_order_quantity']) && !empty($product_row['quantity_step_size'])) ? $product_row['quantity_step_size'] : 1;
                                            $data_max = (isset($product_row['total_allowed_quantity']) && !empty($product_row['total_allowed_quantity'])) ? $product_row['total_allowed_quantity'] : 0;
                                        ?>
                                        <!-- add to cart button -->
                                        <!-- <a href="#" class="add_to_cart cart-btn text-decoration-none text-s" style="padding-bottom: 36px; padding-top: 12px;" data-product-id="<?= $product_row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-slug="<?= $product_row['slug'] ?>" data-product-title="<?= $product_row['name'] ?>" data-product-image="<?= $product_row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                                                <i class="uil uil-shopping-bag"></i>&nbsp;Add to Cart</a> -->

                                    </div>
                                </div>
                            <?php 
                            }
                            $i++;
                            } 
                            ?>
                        <?php } ?>
                    </div>

                    <?php echo generateSeeAllButton($row, $this); ?>

                </section>
                <!-- special picks ends -->

            <?php } ?>
            
    <?php }
        $offer_counter++;
    } ?>

    <!-- instagram section starts -->
    <section class="home-part-container">
        <h1 class="heading-b container-heading">Instagram</h1>
        <p class="text-n op-8 container-des">Display an Instagram feed from your Instagram account.</p>
        <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->

        <!-- instagram -->
        <!-- <div class="container-fluid my-3 instagram-container"> -->
        <div class="container-fluid my-3 instagram-container container">
            <div class="container-fluid my-1">

                <div class="row justify-content-center instagram-items-container">
                    
                    <!-- Place <div> tag where you want the feed to appear -->
                    <div id="curator-feed-default-feed-layout"><a href="https://curator.io" target="_blank" class="crt-logo crt-tag">Powered by Curator.io</a></div>

                    <!-- The Javascript can be moved to the end of the html page before the </body> tag -->
                    <script type="text/javascript">
                        /* curator-feed-default-feed-layout */
                        (function() {
                            var i, e, d = document,
                                s = "script";
                            i = d.createElement("script");
                            i.async = 1;
                            i.charset = "UTF-8";
                            i.src = "https://cdn.curator.io/published/b22ac81e-28c4-4e42-8277-146ac29a87b1.js";
                            e = d.getElementsByTagName(s)[0];
                            e.parentNode.insertBefore(i, e);
                        })();
                    </script>
                </div>

            </div>
        </div>
    </section>

    <!-- instagram section ends -->

</section>

<?php $web_settings = get_settings('web_settings', true); ?>
<?php if (isset($web_settings['app_download_section']) && $web_settings['app_download_section'] == 1) { ?>
    <section class="wrapper bg-soft-grape">
        <div class="align-items-md-center d-flex flex-wrap justify-content-center gap-5 pb-15">
            <div>
                <img class="w-100" src="<?= THEME_ASSETS_URL . 'demo/avtars/4530199.png' ?>" alt="Download - <?= $web_settings['app_download_section_title'] ?>" />
            </div>
            <div class="col-md-7">
                <h1 class="display-4 mb-4 px-md-10 px-lg-0"><?= $web_settings['app_download_section_title'] ?></h1>
                <h3 class="mt-3 header-p"><?= $web_settings['app_download_section_tagline'] ?></h3>
                <p class="lead fs-lg mb-7 px-md-10 px-lg-0 pe-xxl-15"><?= $web_settings['app_download_section_short_description'] ?></p>
                <span><a href="<?= $web_settings['app_download_section_appstore_url'] ?>" target="_blank" class="btn btn-dark btn-icon btn-icon-start rounded-pill me-2"><i class="uil uil-apple"></i>
                        App Store</a></span>
                <span><a href="<?= $web_settings['app_download_section_playstore_url'] ?>" target="_blank" class="btn btn-green btn-icon btn-icon-start rounded-pill"><i class="uil uil-google-play"></i>
                        Google Play</a></span>
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container -->
    </section>
<?php } ?>

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

        return '<div class="rating-container">
                    <p class="text-es">' . $rounded_rating . '</p>
                    <img class="star-icon" src="' . $star_image . '" >
                </div>';
    }
    function generateSeeAllButton($section, $obj){
        $url = base_url('products/section/' . $section['id'] . '/' . $section['slug']);
        $text = !empty($obj->lang->line("see_all")) ? $obj->lang->line("see_all") : "See All";
        return '
                <div class="container-des" style="margin-bottom: 1em; margin-top: 1em;">
                    <a href="' . $url . '">
                        <button class="see-all">
                            <span>' . $text . '</span>
                            <span class="see-all-arrow ml-1"> <img src="' . base_url('assets/front_end/cretzo/img/new_cretzo/arrow-right.svg') . '"/> </span>
                        </button>
                    </a>
                </div>
                ';
        /* return '
                <div class="container-des" style="margin-bottom: 1em; margin-top: 2em;">
                    <a href="' . $url . '" class="btn btn-expand btn-soft-primary rounded-pill">
                        <i class="uil uil-arrow-right"></i>
                        <span>' . $text . '</span>
                    </a>
                </div>
                '; */
    }

    function generatePriceElement($product_row, $textStyle = "text-s"){

        $discounted_price = $product_row['variants'][0]['special_price'];
        $price = $product_row['variants'][0]['price'];
        
        // $currencyText = $settings['currency'];
        $currencyText = "Rs. ";

        if (($discounted_price < $price) && ($discounted_price != 0)) {
            return '<p class="ta-c ' . $textStyle . '">
                <span class="discounted-price">'
                    . $currencyText . number_format($discounted_price, 2) .
                '</span>
                <span class="original-price op-6">'
                    . $currencyText . number_format($price, 2) .
                '</span>
            </p>';
        } else {
            return '<p class="ta-c ' . $textStyle . '">
                <span class="discounted-price">'
                    . $currencyText . number_format($price, 2) .
                '</span>
            </p>';
        }

    }
?>