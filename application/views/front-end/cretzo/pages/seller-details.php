<?php
    /* echo '<pre>';
    // print_r(var_dump($seller_details));
    // print_r(var_dump($sellers));
    // print_r(var_dump($seller_categories));
    die; */
?>

<section class="aboutus-container">
    <!-- <div class="aboutus-one">
        <div></div>
        <div>
            <h1 class="heading-b">Pearls Spring Collection</h1>
            <p class="text-n">New pearl earrings and more from 99</p>
        </div>
        <div>
            <img class="aboutus-one-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/aboutus-earring-img.webp') ?>">
        </div>
    </div> -->

    <div class="aboutus-one">
        <img class="aboutus-one-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url($sellers[0]['store_banner']) ?>">
    </div>

    <div class="aboutus-two">
        <img class="profile-icon lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url($sellers[0]['logo']) ?>">
        
        <p class="heading-b orange"><?= $sellers[0]['store_name'] ?></p>

        <!-- <br> -->

        <div class="d-flex gap-4 align-items-center">
            <div class="d-flex">
                <i class="fs-60 orange uil uil-store"></i>
            </div>
            <div class="d-flex flex-column">
                <h1 class="h1"><?= $row['username'] ?></h1>
                <ul class="d-flex gap-4 pl-0">
                    <li class="d-inline-block">
                        <i class="text-warning uil uil-star"></i>
                        <?= $sellers[0]['rating'] ?> Ratings
                    </li>
                    <li class="d-inline-block">
                        <i class="text-success uil uil-check-circle"></i>
                        <?= $total_orders ?> Orders
                    </li>
                    <li class="d-inline-block">
                        <i class="text-sky uil uil-cube"></i>
                        <?= $seller_products_count ?> Products
                    </li>
                </ul>
            </div>
        </div>

        <!-- <br> -->

        <div class="row align-items-center">
            <!-- Heading and Description -->
            <div class="col">
                <h1 class="heading-n aboutus-heading">About us</h1>
                <p class="text-n aboutus-text readMore" data-read-more-length="650"><?= !empty($sellers[0]['store_description']) ? $sellers[0]['store_description'] : "Description not available for this seller." ?></p>
            </div>

            <div class="col-auto text-end d-flex align-items-center" style="translate: 0px 20px;">
                <a class="text-decoration-none cretzo-link d-flex align-items-center" href="<?= base_url('my-account/chat?seller-id=' . $seller_details[0]['id'] . '&seller-username=' . $seller_details[0]['username']) ?>">
                    <i class="uil uil-chat fs-28 me-1"></i>
                    <p class="text-b text-decoration-underline mb-0" style="text-underline-offset: 4px;">Chat with seller</p>
                </a>
            </div>
        </div>

    </div>

    <div class="aboutus-three">

        <?php
            foreach ($seller_categories as $category) { ?>
                <a class="text-decoration-none cretzo-link" href="<?= base_url('products?seller=' . $sellers[0]['slug'] . '&category=' . $category['id']) ?>">
                    <div class="catagory-container">
                        <img class="catagory-img" src="<?= $category['image'] ?>">
                        <p class="text-n"><?= $category['name'] ?></p>
                    </div>
                </a>
        <?php } ?>
        
        <!-- <div class="catagory-container">
            <img class="catagory-img" src="../images/card-img1.png">
            <p class="text-n">Catagory</p>
        </div> -->
    </div>

    <!-- more form this seller -->
    <div class="more-seller-container">

        <h1 class="heading-b container-heading">Featured Products</h1>
        <p class="text-n op-8 container-des">Our special handpicked products</p>
        <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->

        <div class="card-container-more-seller">
            <?php
                $i = 0;
                if (count($products_top_rated) > 0) {
                    foreach ($products_top_rated as $product_row) {
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
    </div>

    <div class="more-seller-container">

        <h1 class="heading-b container-heading">Best Seller</h1>
        <p class="text-n op-8 container-des">Check out our best selling products</p>
        <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->

        <div class="card-container-more-seller">

            <?php
                $i = 0;
                if (count($products_most_selling) > 0) {
                    foreach ($products_most_selling as $product_row) {
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
    </div>

    <div class="more-seller-container">

        <h1 class="heading-b container-heading">New Arrivals</h1>
        <p class="text-n op-8 container-des">Checkout what's new</p>
        <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->

        <div class="card-container-more-seller">

            <?php
                $i = 0;
                if (count($products_new_added) > 0) {
                    foreach ($products_new_added as $product_row) {
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
    </div>
</section>



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