<?php $total_images = 0; ?>

<?php $seller_slug = fetch_details("seller_data", ['user_id' => $product['product'][0]['seller_id']]); ?>

<?php

    /* echo "<pre>";
    print_r($product_ratings);
    print_r($my_rating);
    die; */

    /* echo "<pre>";
    print_r(var_dump($product_ratings['product_rating'][0]['images']));
    die; */

    /* echo "<pre>";
    print_r(var_dump($product['product'][0]['review_images'][0]));
    echo "------------------------------------------\n";
    echo "------------------------------------------\n";
    print_r(var_dump($product['product'][0]['review_images'][0]['product_rating'][0]));
    die; */

    // print_r($product['product'][0]['variant_attributes']);
    /* echo "<pre>";
    print_r($product['product'][0]['variants'][2]);
    die; */

    /* echo "<pre>";
    print_r($product['product'][0]['variants']);
    die; */


    /* echo "<pre>";
    print_r($product['product'][0]['variants']);
    print_r("------------------ ------------------ ------------------");
    print_r("------------------ ------------------ ------------------");
    print_r("------------------ ------------------ ------------------");
    print_r("------------------ ------------------ ------------------");
    print_r($product['product'][0]['variant_attributes']);
    die; */
?>

<!-- main container -->
<section class="product-container">

    <!-- img section -->
    <div class="img-container">

        <!-- <div class="big-img-container">
            <img class="big-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/product.png') ?>" alt="Picture">
        </div> -->

        <div class="big-img-container">

            <!-- main image slider -->
            <div class="swiper  gallery-top-1">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <figure class="rounded">
                            <img src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product['product'][0]['image'] ?>" alt="" class="lazy big-img product-big-img"> <!-- style="object-fit: cover;width: fit-content;"> -->
                            <a class="item-link text-decoration-none" href="<?= $product['product'][0]['image'] ?>" data-glightbox="" data-gallery="product-group"><i class="uil uil-focus-add"></i></a>
                        </figure>
                    </div>
                    <?php
                    $variant_images_md = array_column($product['product'][0]['variants'], 'images_md');
                    if (!empty($variant_images_md)) {
                        foreach ($variant_images_md as $variant_images) {
                            if (!empty($variant_images)) {
                                foreach ($variant_images as $image) {
                    ?>
                                    <div class="swiper-slide 12345">
                                        <figure class="rounded">
                                            <img src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image ?>" class="lazy big-img product-big-img" alt=""> <!-- style="object-fit: cover;width: fit-content;"> -->
                                            <a class="item-link text-decoration-none" href="<?= $image ?>" data-glightbox="" data-gallery="product-group"><i class="uil uil-focus-add"></i></a>
                                        </figure>
                                    </div>
                    <?php }
                            }
                        }
                    } ?>
                    <?php
                    if (!empty($product['product'][0]['other_images']) && isset($product['product'][0]['other_images'])) {
                        foreach ($product['product'][0]['other_images'] as $other_image) {
                            $total_images++;
                    ?>
                            <div class="swiper-slide">
                                <figure class="rounded">
                                    <img src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $other_image ?>" class="lazy big-img product-big-img" alt="" id="img_01"> <!-- style="object-fit: cover;width: fit-content;"> -->
                                    <a class="item-link text-decoration-none" href="<?= $other_image ?>" data-glightbox="" data-gallery="product-group"><i class="uil uil-focus-add"></i></a>
                                </figure>
                            </div>
                    <?php }
                    } ?>
                    <?php
                    if (isset($product['product'][0]['video_type']) && !empty($product['product'][0]['video_type'])) {
                        $total_images++;
                    ?>
                        <div class="swiper-slide" style="align-self: center;">
                            <figure class="rounded">
                                <?php if ($product['product'][0]['video_type'] == 'self_hosted') { ?>
                                    <video controls width="320" height="240" src="<?= $product['product'][0]['video'] ?>">
                                        <?= !empty($this->lang->line('no_video_tag_support')) ? $this->lang->line('no_video_tag_support') : 'Your browser does not support the video tag.' ?>
                                    </video>
                                <?php } else if ($product['product'][0]['video_type'] == 'youtube' || $product['product'][0]['video_type'] == 'vimeo') {
                                    if ($product['product'][0]['video_type'] == 'vimeo') {
                                        $url =  explode("/", $product['product'][0]['video']);
                                        $id = end($url);
                                        $url = 'https://player.vimeo.com/video/' . $id;
                                    } else if ($product['product'][0]['video_type'] == 'youtube') {
                                        if (strpos($product['product'][0]['video'], 'watch?v=') !== false) {
                                            $url = str_replace("watch?v=", "embed/", $product['product'][0]['video']);
                                        } else if (strpos($product['product'][0]['video'], "youtu.be/") !== false) {
                                            $url = explode("/", $product['product'][0]['video']);
                                            $url = "https://www.youtube.com/embed/" . end($url);
                                        } else if (strpos($product['product'][0]['video'], "shorts/") !== false) {
                                            $url = str_replace("shorts/", "embed/", $product['product'][0]['video']);
                                        } else {
                                            $url = $product['product'][0]['video'];
                                        }
                                    } else {
                                        $url = $product['product'][0]['video'];
                                    } ?>
                                    <iframe class="product-big-img-video-frame" src="<?= $url ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                <?php } ?>
                            </figure>
                        </div>
                    <?php } ?>
                    <!--/.swiper-slide -->

                </div>
                <!--/.swiper-wrapper -->
                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
            </div>

        </div>
            
        <!-- product images slider -->
        <div class="swiper-container product-gallery-thumbs gallery-thumbs-1 swiper-navigation-disabled swiper-container-initialized swiper-container-horizontal swiper-container-free-mode swiper-container-thumbs overflow-hidden" style="width: 100%;">
            <div class="swiper-wrapper" id="gal1" style="transform: translate3d(0px, 0px, 0px); transition: all 0ms ease 0s;">

                <div class="swiper-slide mb-1" style="margin-right: 10px;">
                    <div class="swiper-img" style="height: auto !important; margin-right: 10px;">
                        <img src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product['product'][0]['image'] ?>" class="rounded p-1 lazy product-small-img">
                    </div>
                </div>

                <?php
                    $variant_images_md = array_column($product['product'][0]['variants'], 'images_md');

                    if (!empty($variant_images_md)) {
                        foreach ($variant_images_md as $variant_images) {
                            if (!empty($variant_images)) {
                                foreach ($variant_images as $image) { ?>
                                    <div class="swiper-slide mb-1" style="margin-right: 10px;">
                                        <div class="swiper-img" style="height: auto !important; margin-right: 10px;">
                                            <img src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image ?>" class="rounded p-1 lazy product-small-img" alt="">
                                        </div>
                                    </div>
                    <?php }
                            }
                        }
                    } ?>

                <?php
                    if (!empty($product['product'][0]['other_images']) && isset($product['product'][0]['other_images'])) {
                        foreach ($product['product'][0]['other_images'] as $other_image) { ?>
                            <div class="swiper-slide mb-1" style="margin-right: 10px;">
                                <div class="swiper-img" style="height: auto !important; margin-right: 10px;">
                                    <img src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $other_image ?>" class="rounded p-1 lazy product-small-img" alt="">
                                </div>
                            </div>
                    <?php }
                    } ?>
                    
                <?php
                    if (isset($product['product'][0]['video_type']) && !empty($product['product'][0]['video_type'])) {
                        $total_images++;
                    ?>

                        <div class="swiper-slide mb-1" style="margin-right: 10px;">
                            <div class="swiper-img" style="height: auto !important; margin-right: 10px;">
                                <img src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/admin/images/video-file.png') ?>" class="rounded p-1 lazy product-small-img" alt="">
                            </div>
                        </div>
                        
                    <?php } ?>
                
            </div>
            <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
        </div>

    </div>
    <!-- /img section -->

    <!-- product details -->
    <div class="detail-container">

        <div class="detail-container-content">
        
            <!-- <p class="text-b op-6"><?= ucfirst($product['product'][0]['category_name']) ?></p> -->
            
            <p class="text-b op-6">
                <a target="_BLANK" href="<?= base_url('sellers/seller_details/' . $seller_slug[0]['slug']) ?>" class="store-seller-detail text-decoration-none">
                    <?= ucfirst($product['product'][0]['store_name']) . ' by ' . ucfirst($product['product'][0]['seller_name']) ?>
                </a>
            </p>

            <h1 class="heading-b product-name mb-1"><?= ucfirst($product['product'][0]['name']) ?></h1>
            
            <a href="#review-section" class="text-decoration-none">
                <div class="rating-star-container mb-2">
                    <p class="text-n"><?= (float)($product['product'][0]['rating']) ?></p>
                    <img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>">
                    <p class="text-n">| <?= $product['product'][0]['no_of_ratings'] ?> Ratings</p>
                </div>
            </a>

            <?= generatePriceElement2($product['product']) ?>

            <div class="product-stock mb-4">
                <img class="green-tick-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/orange-tick.png') ?>" alt="checkmark">
                <p id="stock-quantity" class="text-n"> <?= $product['product'][0]['variants'][0]['stock'] ?> in stock </p>
            </div>

            <div class="my-2">
                <p class="text-n product-description my-0 readMore"> <?= $product['product'][0]['short_description'] ?> <?= $product['product'][0]['short_description'] ?> <?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?> <?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?><?= $product['product'][0]['short_description'] ?></p>
            </div>

            <div class="product-quantity-container">
                <!-- <p class="text-n">Qty:</p>
                <div class="product-quantity-count text-b fw-b">
                    <span class="c-p">-</span><span class="sub-heading">1</span><span class="c-p">+</span>
                </div> -->

                <?php
                    if (count($product['product'][0]['variants']) <= 1) {
                        $variant_id = $product['product'][0]['variants'][0]['id'];
                    } else {
                        $variant_id = "";
                    }
                ?>

                <?php
                    if (!empty($product['product'][0]['variants']) && isset($product['product'][0]['variants'])) {
                        $total_images = 1;
                        foreach ($product['product'][0]['variants'] as $variant) {
                        ?>
                            <input type="hidden" class="variants" name="variants_ids" data-image-index="<?= $total_images ?>" data-name="" value="<?= $variant['variant_ids'] ?>" data-id="<?= $variant['id'] ?>" data-price="<?= $variant['price'] ?>" data-special_price="<?= $variant['special_price'] ?>" />
                    <?php
                            $total_images += count($variant['images']);
                        }
                    }
                ?>

                <!-- <button class="cretzo btn btn-dark">Add to Cart</button> -->

                <div class="num-block skin-2 py-1 mt-2">
                    <div class="num-in form-control d-flex align-items-center">
                        <span class="minus dis" data-min="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['minimum_order_quantity'])) ? $product['product'][0]['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['quantity_step_size'])) ? $product['product'][0]['quantity_step_size'] : 1 ?>"></span>
                        <input type="text" name="qty" class="in-num" value="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['minimum_order_quantity'])) ? $product['product'][0]['minimum_order_quantity'] : 1 ?>" data-step="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['quantity_step_size'])) ? $product['product'][0]['quantity_step_size'] : 1 ?>" data-min="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['minimum_order_quantity'])) ? $product['product'][0]['minimum_order_quantity'] : 1 ?>" data-max="<?= (isset($product['product'][0]['total_allowed_quantity']) && !empty($product['product'][0]['total_allowed_quantity'])) ? $product['product'][0]['total_allowed_quantity'] : '' ?>">
                        <span class="plus" data-max="<?= (isset($product['product'][0]['total_allowed_quantity']) && !empty($product['product'][0]['total_allowed_quantity'])) ? $product['product'][0]['total_allowed_quantity'] : '' ?> " data-step="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['quantity_step_size'])) ? $product['product'][0]['quantity_step_size'] : 1 ?>"></span>
                    </div>
                </div>

                <button type="button" name="add_cart" class="cretzo btn btn-light add_to_cart" id="add_cart" 
                    data-product-id=" <?= $product['product'][0]['id'] ?>" 
                    data-product-title="<?= $product['product'][0]['name'] ?>" 
                    data-product-slug="<?= $product['product'][0]['slug'] ?>" 
                    data-product-image="<?= $product['product'][0]['image'] ?>" 
                    data-product-price="<?= ($variant['special_price'] > 0 && $variant['special_price'] != '0' && $variant['special_price'] != '') ? $variant['special_price'] : $variant['price']; ?>" 
                    data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product['product'][0]['short_description'])))); ?>" 
                    data-step="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['quantity_step_size'])) ? $product['product'][0]['quantity_step_size'] : 1 ?>" 
                    data-min="<?= (isset($product['product'][0]['minimum_order_quantity']) && !empty($product['product'][0]['minimum_order_quantity'])) ? $product['product'][0]['minimum_order_quantity'] : 1 ?>" 
                    data-max="<?= (isset($product['product'][0]['total_allowed_quantity']) && !empty($product['product'][0]['total_allowed_quantity'])) ? $product['product'][0]['total_allowed_quantity'] : '' ?>" 
                    data-product-variant-id="<?= $variant_id ?>"> <i class="uil uil-shopping-bag mr-2"></i> Add to Cart </button>

                
            </div>

            
            <?php
                $color_code = $style = "";
                $product['product'][0]['variant_attributes'] = array_values($product['product'][0]['variant_attributes']);

                if (isset($product['product'][0]['variant_attributes']) && !empty($product['product'][0]['variant_attributes'])) { ?>
                    <?php
                    foreach ($product['product'][0]['variant_attributes'] as $attribute) {
                        $attribute_ids = explode(',', $attribute['ids']);
                        $attribute_values = explode(',', $attribute['values']);
                        $swatche_types = explode(',', $attribute['swatche_type']);
                        $swatche_values = explode(',', $attribute['swatche_value']);
                        for ($i = 0; $i < count($swatche_types); $i++) {
                            if (!empty($swatche_types[$i]) && $swatche_values[$i] != "") {
                                $style = '<style> .product-page-details .btn-group>.active { background-color: #ffffff; color: #000000; border: 1px solid black;}</style>';
                            } else if ($swatche_types[$i] == 0 && $swatche_values[$i] == null) {
                                $style1 = '<style> .product-page-details .btn-group>.active { background-color: var(--primary-color);color: white!important;}</style>';
                            }
                        }

                        $attribute_class = "product-size";
                        if ($swatche_types[0] == "1"){
                            $attribute_class = "product-color";
                        }
                    ?>
                        <div class="<?= $attribute_class ?>">
                            <p style="min-width: 50px" class="text-n"><?= $attribute['attr_name'] ?></p>
                            <?php
                            foreach ($attribute_values as $key => $row) {
                                if ($swatche_types[$key] == "1") {
                                    echo '<style> .product-page-details .btn-group>.active { border: 1px solid black;}</style>';
                                    $color_code = "style='background-color:" . $swatche_values[$key] . ";'";  ?>

                                    <label class="product-attr-label product-attr-label-color">
                                        <input class="product-attr-input hide-radio-btn attributes filter-input" type="radio" name="<?= $attribute['attr_name'] ?>" value="<?= $attribute_ids[$key] ?>" autocomplete="off">
                                        <!-- <span class="product-c" <?= $color_code ?>></span> -->
                                        <div class="photo-dimmer-overlay"></div>
                                        <?php
                                            // Loop over the variants
                                            foreach ($product['product'][0]['variants'] as $variant) {
                                                $variant_color_value = explode(',', $variant['swatche_value']);
                                                // Check if the current variant has the color_value
                                                if (in_array($swatche_values[$key], $variant_color_value)) { ?>
                                                    <img class="lazy product-color-image" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $variant['images_sm'][0] ?>">
                                                <?php
                                                    break;
                                                }
                                            }
                                        ?>

                                    </label>
                                    
                                <?php } else if ($swatche_types[$key] == "2") { ?>
                                    <?= $style ?>
                                    <ul class="p-0">
                                        <li class="list-unstyled">
                                            <label class="product-attr-label btn text-center ">
                                                <img class="swatche-image lazy category-image-container" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $swatche_values[$key] ?>">
                                                <input type="radio" name="<?= $attribute['attr_name'] ?>" value="<?= $attribute_ids[$key] ?>" autocomplete="off" class="attributes">
                                                <br>
                                            </label>
                                        </li>
                                    </ul>

                                <?php } else { ?>
                                    <?= '<style> .product-page-details .btn-group>.active { background-color: var(--primary-color);color: white!important;}</style>'; ?>
                                    
                                    <label class="cretzo btn size-btn product-attr-label product-attr-label-general">
                                        <input class="product-attr-input hide-radio-btn attributes" type="radio" name="<?= $attribute['attr_name'] ?>" value="<?= $attribute_ids[$key] ?>" autocomplete="off">
                                        <span><?= $row ?></span>
                                    </label>
                                    
                                <?php } ?>
                            <?php } ?>
                        </div>
                    <?php
                    }
                }
                if (!empty($product['product'][0]['variants']) && isset($product['product'][0]['variants'])) {
                    $total_images = 1;
                    foreach ($product['product'][0]['variants'] as $variant) {
                    ?>
                        <input type="hidden" class="variants" name="variants_ids" data-image-index="<?= $total_images ?>" data-name="" value="<?= $variant['variant_ids'] ?>" data-id="<?= $variant['id'] ?>" data-price="<?= $variant['price'] ?>" data-special_price="<?= $variant['special_price'] ?>" data-stock="<?= $variant['stock'] ?>" />
                <?php
                        $total_images += count($variant['images']);
                    }
                }
            ?>

            
            <div>

                <button style="display: inline-block;" class="cretzo btn btn-light wishlist-btn add-fav mr-1"> <span>Customization</span> </button>

                <?php 
                $isNotFav = $product['product'][0]['is_favorite'] == 0;
                if ($isNotFav) { ?>
                    <button style="display: inline-block;" class="cretzo btn btn-light wishlist-btn add-fav" id="add_to_favorite_btn" data-is-fav="false" data-product-id="<?= $product['product'][0]['id'] ?>"> <i class="fa fa-heart-o mr-1"></i> <span>Add to Wishlist</span> </button>
                <?php } else { ?>
                    <button style="display: inline-block;" class="cretzo btn btn-light wishlist-btn remove-fav" id="add_to_favorite_btn" data-is-fav="true" data-product-id="<?= $product['product'][0]['id'] ?>"> <i class="fa fa-heart mr-1"></i> <span>Remove from Wishlist</span> </button>
                <?php } ?>

            </div>


            <?php if ($product['product'][0]['type'] != 'digital_product') { ?>
                <form class="mt-8 mb-1" id="validate-zipcode-form" method="POST">

                    <div class="d-flex flex-row">
                        <h4 class="text-n mb-2 fw-bold opacity-75">DELIVERY OPTIONS</h4>
                        <img class="ship-icon ml-2" style="height: 15px; width: auto; margin-top: 4px;" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/shipping.png') ?>" alt="shipping">
                    </div>


                    <div class="input-group">
                        <input type="hidden" name="product_id" value="<?= $product['product'][0]['id'] ?>">
                        <input type="text" class="form-control rounded" id="zipcode" placeholder="Enter Pincode" name="zipcode" autocomplete="off" required value="<?= $product['product'][0]['zipcode']; ?>">

                        <button type="submit" class="cretzo btn btn-light btn-sm ml-0 btn-primary check-availability" id="validate_zipcode"><?= !empty($this->lang->line('check_availability')) ? $this->lang->line('check_availability') : 'Check Availability' ?></button>
                    </div>
                    <div class="input-group" id="error_box">
                        <?php if (!empty($product['product'][0]['zipcode'])) { ?>
                            <b class="text-<?= ($product['product'][0]['is_deliverable']) ? "success" : "danger" ?>"><?= !empty($this->lang->line('product_is')) ? $this->lang->line('product_is') : 'Product is' ?> <?= ($product['product'][0]['is_deliverable']) ? "" : "not" ?> <?= !empty($this->lang->line('delivarable_on')) ? $this->lang->line('delivarable_on') : 'delivarable on' ?> &quot; <?= $product['product'][0]['zipcode']; ?> &quot; </b>
                        <?php } ?>
                    </div>
                </form>
            <?php } ?>

            <p class="text-s mb-1"> Please enter PIN code to check delivery time & Cash on Delivery Availability </p>
            <p class="text-s"> 100% Original Products </p>
            <p class="text-s"> Cash on Delivery might be available </p>
            <p class="text-s"> Easy 14 days returns and exchanges </p>

            <div class="d-flex flex-row mt-4">
                <h4 class="text-n mb-2 fw-bold opacity-75">BEST OFFERS</h4>
                <img class="ship-icon ml-2" style="height: 18px; width: auto; margin-top: 2px;" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/offer.png') ?>" alt="shipping">
            </div>


            <div class="shipping-container">
                
                <div class="shipping-option">
                    <img class="ship-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/shipping.png') ?>" alt="shipping">
                    <p class="text-n">Free Ship</p>
                </div>

                <?php if (isset($product['product'][0]['cod_allowed']) && !empty($product['product'][0]['cod_allowed']) && $product['product'][0]['cod_allowed'] == 1) {  ?>
                    <div class="shipping-option">
                        <img class="ship-icon h-6 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/front_end/classic/images/cod_logo.png') ?>">
                        <p class="text-n">COD</p>
                    </div>
                <?php } ?>

                <?php if (isset($product['product'][0]['is_cancelable']) && !empty($product['product'][0]['is_cancelable']) && $product['product'][0]['is_cancelable'] == 1) {  ?>
                    <div class="shipping-option">
                        <img class="ship-icon h-6 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/front_end/classic/images/cancelable.png') ?>">
                        <p class="text-n">Cancelable <br> till<?= ' ' . $product['product'][0]['cancelable_till'] ?></p>
                    </div>
                <?php } else { ?>
                    <div class="shipping-option">
                        <img class="ship-icon h-6 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/front_end/classic/images/notcancelable.png') ?>">
                        <p class="text-n">Not Cancellable</p>
                    </div>
                <?php  } ?>

                <?php if (isset($product['product'][0]['is_returnable']) && !empty($product['product'][0]['is_returnable']) && $product['product'][0]['is_returnable'] == 1) {  ?>
                    <div class="shipping-option">
                        <img class="ship-icon h-6 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/front_end/classic/images/returnable.png') ?>">
                        <p class="text-n"><?= $settings['max_product_return_days'] ?> Days Returnable</p>
                    </div>
                <?php } else { ?>
                    <div class="shipping-option">
                        <img class="ship-icon h-6 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/front_end/classic/images/notreturnable.png') ?>">
                        <p class="text-n">Not Returnable</p>
                    </div>
                <?php  } ?>

                <?php if (isset($product['product'][0]['guarantee_period']) && !empty($product['product'][0]['guarantee_period'])) {  ?>
                    <div class="shipping-option">
                        <img class="ship-icon h-6 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/front_end/classic/images/guarantee.png') ?>">
                        <p class="text-n"><?= $product['product'][0]['guarantee_period'] ?> <br> Guarantee</p>
                    </div>
                <?php } ?>

                <?php if (isset($product['product'][0]['warranty_period']) && !empty($product['product'][0]['warranty_period'])) {  ?>
                    <div class="shipping-option">
                        <img class="ship-icon h-6 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= base_url('assets/front_end/classic/images/warranty.png') ?>">
                        <p class="text-n"><?= $product['product'][0]['warranty_period'] ?> <br> Warranty</p>
                    </div>
                <?php } ?>

            </div>

            <div class="customer-rating-container" id="review-section">
                <h1 class="heading-n">Ratings</h1>
                <div class="customer-rating">
                    <div class="customer-rating-number">
                        <p class="heading-b"><?= (float)($product['product'][0]['rating']) ?><img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>"></h1>
                        <!-- <p>381 Verified Buyers</p> -->
                        <p><?= $product['product'][0]['no_of_ratings'] ?> Ratings</p>
                    </div>
                    <div class="customer-rating-star">

                        <?php
                            $total_ratings = $product['product'][0]['no_of_ratings'];
                        ?>

                        <div class="customer-progress-bar-container">
                            <span class="text-s">5<span><img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>"></span></span>
                            <progress id="file" value="<?=($total_ratings > 0) ? ($product_ratings['star_5'] / $total_ratings) * 100 : 0?>" max="100"></progress>
                            <span class="text-s"><?=$product_ratings?$product_ratings['star_5']:0?></span>
                        </div>

                        <div class="customer-progress-bar-container">
                            <span class="text-s">4<span><img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>"></span></span>
                            <progress id="file" value="<?=($total_ratings > 0) ? ($product_ratings['star_4'] / $total_ratings) * 100 : 0?>" max="100"></progress>
                            <span class="text-s"><?=$product_ratings?$product_ratings['star_4']:0?></span>
                        </div>

                        <div class="customer-progress-bar-container">
                            <span class="text-s">3<span><img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>"></span></span>
                            <progress id="file" value="<?=($total_ratings > 0) ? ($product_ratings['star_3'] / $total_ratings) * 100 : 0?>" max="100"></progress>
                            <span class="text-s"><?=$product_ratings?$product_ratings['star_3']:0?></span>
                        </div>

                        <div class="customer-progress-bar-container">
                            <span class="text-s">2<span><img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>"></span></span>
                            <progress id="file" value="<?=($total_ratings > 0) ? ($product_ratings['star_2'] / $total_ratings) * 100 : 0?>" max="100"></progress>
                            <span class="text-s"><?=$product_ratings?$product_ratings['star_2']:0?></span>
                        </div>

                        <div class="customer-progress-bar-container">
                            <span class="text-s">1<span><img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>"></span></span>
                            <progress id="file" value="<?=($total_ratings > 0) ? ($product_ratings['star_1'] / $total_ratings) * 100 : 0?>" max="100"></progress>
                            <span class="text-s"><?=$product_ratings?$product_ratings['star_1']:0?></span>
                        </div>
                    </div>
                </div>

                <?php if (isset($product_ratings) && !empty($product_ratings)) { ?>
                    <h1 class="heading-n mt-2">Customer Photos (<?=$product_ratings['total_images_count']?>)</h1>
                    <div class="customer-photos">
                        <?php
                            $img_count = 0;
                            foreach ($product_ratings['product_rating'] as $row) {
                                if($img_count >= 4)
                                    break;

                                foreach ($row['images'] as $image) {
                                    if($img_count >= 4){
                                        break;
                                    }
                                    else if($img_count == 3 && $product_ratings['total_images_count'] > 4){
                                    ?>
                                        <div class="more-photo-container">
                                            <img class="customer-photos-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image; ?>" alt="Review Image">
                                            <a href="#" data-toggle="modal" data-target="#imageGalleryModal">
                                                <p class="more-photo-btn">+<?= $product_ratings['total_images_count']-3 ?></p>
                                            </a>
                                        </div>
                                    <?php
                                    }
                                    else{
                                    ?>
                                        <a href="<?= $image; ?>" data-lightbox="review-images-all" data-gallery="review-images-all">
                                            <img class="customer-photos-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image; ?>" alt="Review Image">
                                        </a>
                                    <?php
                                    }
                                    $img_count += 1;
                                }
                            }
                        } ?>
                    </div>

                <?php if($product['product'][0]['no_of_ratings'] > 0){ ?>
                    
                    <h1 class="heading-n mt-8">Customer Reviews (<?= $product['product'][0]['no_of_ratings'] ?>)</h1>

                    <!-- single review -->
                    <div class="customer-reviews">

                        <?php if (isset($product_ratings) && !empty($product_ratings)) {
                            foreach ($product_ratings['product_rating'] as $row) { 
                            ?>
                                <div class="customer-review-container">
                                    <div class="rating-star-container">
                                        <p class="text-s"><?= $row['rating'] ?></p>
                                        <img class="rating-star-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>">
                                    </div>
                                    <div>
                                        <p class="text-n"><?= $row['comment'] ?></p>
                                        <?php if(count($row['images']) > 0){ ?>
                                            <div class="customer-photo-contianer">
                                                <?php if (isset($product_ratings) && !empty($product_ratings)) {
                                                    $img_count = 0;
                                                    foreach ($product_ratings['product_rating'] as $row) {
                                                        if($img_count >= 4)
                                                            break;

                                                        foreach ($row['images'] as $image) {
                                                            if($img_count >= 4){
                                                                break;
                                                            }
                                                            else if($img_count == 3 && $product_ratings['total_images_count'] > 4){
                                                            ?>
                                                                <div class="more-photo-container">
                                                                    <img class="customer-photos-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image; ?>" alt="Review Image">
                                                                    <a href="#" data-toggle="modal" data-target="#imageGalleryModal">
                                                                        <p class="more-photo-btn">+<?= $product_ratings['total_images_count']-3 ?></p>
                                                                    </a>
                                                                </div>
                                                            <?php
                                                            }
                                                            else{
                                                            ?>
                                                                <a href="<?= $image; ?>" data-lightbox="review-images-<?= $row['user_name'] ?>">
                                                                    <img class="customer-photos-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image; ?>" alt="Review Image">
                                                                </a>
                                                            <?php
                                                            }
                                                            $img_count += 1;
                                                        }
                                                    }
                                                } ?>
                                            </div>
                                        <?php } ?>
                                        <div class="name-date-container">
                                            <p class="text-n"><span><?= $row['user_name'] ?></span> | <span>30 Apr 2022</span></p>
                                        </div>
                                    </div>
                                </div>       
                            <?php 
                            }
                        }
                        ?>

                    </div>
                
                <?php } ?>
                

            </div>

        </div>

    </div>
    <!-- /product details -->
</section>

<!-- product description -->
<section class="product-description-container">
    <div class="des-btn-container">
        <button class="text-n des-btn active-des-btn">Description</button>
        <button class="text-n des-btn">Information</button>
        <button class="text-n des-btn">FAQ's</button>
    </div>

    <div id="description" class="text-n des"><?= $product['product'][0]['description'] ?></div>
    <!-- <p id="description" class="text-n des">description Lorem ipsum dolor sit amet consectetur adipisicing elit. Nostrum debitis dignissimos nesciunt dolorem velit itaque, quaerat eaque voluptates culpa fugiat!</p> -->
    
    <div id="information" class="text-n des d-none">
        <?= $product['product'][0]['extra_description'] ?>
    </div>
    <!-- <p id="information" class="text-n des d-none">information Lorem ipsum dolor sit amet consectetur adipisicing elit. Nostrum debitis dignissimos nesciunt dolorem velit itaque, quaerat eaque voluptates culpa fugiat!</p> -->

    <div class="des d-none" id="product-faq" role="tabpanel" aria-labelledby="product-faq-tab">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="accordion accordion-wrapper" id="accordionSimpleExample">

                        <?php if ((!isset($faq['data']) && empty($faq['data'])) || $faq['data'] == []) { ?>
                            <div class="d-flex flex-column align-items-center">
                                <div class="d-flex flex-column">
                                    <img class="" src="<?= base_url('assets/front_end/modern/img/no-faq.jpg') ?>" alt="No Faq" width="160px" />
                                    <h4>No FAQs Found.</h4>
                                </div>
                                <div>
                                    <?php if ($this->ion_auth->logged_in()) { ?>
                                        <div class=" add-faqs-form float-right">
                                            <button class="btn btn-outline-primary btn-xs mt-2 rounded-pill" type="submit" data-toggle="modal" data-target="#add-faqs-form"><i class="uil uil-plus" aria-hidden="true"></i> &nbsp;Add your question here</button>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <?php foreach ($faq['data'] as $row) {
                            ?>
                                <?php if (isset($row['answer']) && !empty($row['answer']) && ($row['answer'] != '')) {
                                ?>
                                    <div class="card plain accordion-item">
                                        <div class="card-header" id="<?= "h-" . $row['id'] ?>">
                                            <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#<?= "c-" . $row['id'] ?>" aria-expanded="true" aria-controls="collapseSimpleOne">
                                                <?= html_escape($row['question']) ?>
                                            </button>
                                        </div>
                                        <!--/.card-header -->
                                        <?php $product_data = fetch_details('users', ['id' => $row['answered_by']], 'username'); ?>
                                        <div id="<?= "c-" . $row['id'] ?>" class="accordion-collapse collapse" aria-labelledby="<?= "h-" . $row['id'] ?>" data-bs-parent="#accordionSimpleExample">
                                            <div class="card-body">
                                                <p class="mb-1"><?= html_escape($row['answer']) ?></p>
                                                <p class="text-dark">Answer by : <?= isset($product_data[0]['username']) && !empty($product_data[0]['username']) ? html_escape($product_data[0]['username']) : "" ?></p>
                                            </div>
                                            <!--/.card-body -->
                                        </div>
                                        <!--/.accordion-collapse -->
                                    </div>
                                <?php } ?>
                        <?php } ?>
                        
                            <div>
                                <?php if ($this->ion_auth->logged_in()) { ?>
                                    <div class=" add-faqs-form ta-c">
                                        <button class="btn btn-outline-primary btn-xs mt-2 rounded-pill" type="submit" data-toggle="modal" data-target="#add-faqs-form"><i class="uil uil-plus" aria-hidden="true"></i> &nbsp;Add your question here</button>
                                    </div>
                                <?php } ?>
                            </div>

                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- <p id="review" class="text-n des d-none">review Lorem ipsum dolor sit amet consectetur adipisicing elit. Nostrum debitis dignissimos nesciunt dolorem velit itaque, quaerat eaque voluptates culpa fugiat!</p> -->

</section>

<!-- <hr style="margin: 1rem 0rem; border-top: 1px solid #bbb;" class="bg-gray"> -->


<!-- review -->

<!--
    
<section class="review-container" id="review-section">
    
    <!- - <h3 class="review-title mb-9"> <span id="no_ratings"><?= $product['product'][0]['no_of_ratings'] ?></span> Reviews For this Product</h3> - ->
    <h3 class="review-title"> <span id="no_ratings"><?= $product['product'][0]['no_of_ratings'] ?></span> Reviews For this Product</h3>
    
    <!- - <div class="ta-c">
        <button class="cretzo btn btn-light write-review-btn">Write a product review</button>
    </div> - ->
    
    <div class="review-heading">
        <h1 class="text-n"><?= isset($product_ratings['total_reviews']) ? $product_ratings['total_reviews'] : 0 ?> Review</h1>
        <p class="text-n">|</p>
        <p class="text-n"> <?= $product['product'][0]['rating'] ?> </p>
        <img class="rating-star-icon-2" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>">
    </div>


    <?php if (isset($my_rating) && !empty($my_rating)) {
        foreach ($my_rating['product_rating'] as $row) { ?>
            <div class="review my-review">
                <!- - <div class="person-img-container">
                    <img class="person-img" src="">
                </div> - ->
                <div class="review-text">
                    <div class="review-rating">
                        <p class="text-n"> <?= $row['rating'] ?> </p>
                        <img class="rating-star-icon-2" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>">
                        
                        <h1 style="display: inline-block;" class="text-n review-username"><?= $row['user_name'] ?></h1>
                        <a style="display: inline-block;" id="delete_rating" href="<?= base_url('products/delete-rating') ?>" class="text-decoration-none text-danger" data-rating-id="<?= $row['id'] ?>">
                            <i class="uil uil-trash-alt fs-22"></i>
                        </a>

                    </div>
                    <!- - <span>
                        <h1 style="display: inline-block;" class="text-n review-username"><?= $row['user_name'] ?></h1>
                        <a style="display: inline-block;" id="delete_rating" href="<?= base_url('products/delete-rating') ?>" class="text-decoration-none text-danger" data-rating-id="<?= $row['id'] ?>">
                            <i class="uil uil-trash-alt fs-22"></i>
                        </a>
                    </span> - ->
                    <p class="text-n review-des"><?= $row['comment'] ?></p>
                </div>

                <div class="row reviews">
                    <?php foreach ($row['images'] as $image) { ?>
                        <div class="col-md-2">
                            <div class="review-box review-img">
                                <!- - <a href="<?= file_exists(FCPATH . REVIEW_IMG_PATH . $image) ? $image : base_url() . NO_IMAGE; ?>" data-lightbox="review-images">
                                                <img  src="<?= file_exists(FCPATH . REVIEW_IMG_PATH . $image) ? $image : base_url() . NO_IMAGE; ?>" alt="Review Image">
                                            </a> - ->
                                <a href="<?= $image; ?>" data-lightbox="review-images">
                                    <img class="lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image; ?>" alt="Review Image">
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                
            </div>
        <?php }
    } ?>

    <?php if (isset($product_ratings) && !empty($product_ratings)) {
        $user_id = (isset($user->id)) ? $user->id : '';
        foreach ($product_ratings['product_rating'] as $row) {
            if ($row['user_id'] != $user_id) { ?>
                <div class="review">
                    <!- - <div class="person-img-container">
                        <img class="person-img" src="">
                    </div> - ->
                    <div class="review-text">
                        <div class="review-rating">
                            <p class="text-n"> <?= $row['rating'] ?> </p>
                            <img class="rating-star-icon-2" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/rating-star.png') ?>">
                            <h1 class="text-n review-username"><?= $row['user_name'] ?></h1>
                        </div>
                        <!- - <h1 class="text-n review-username"><?= $row['user_name'] ?></h1> - ->
                        <p class="text-n review-des"><?= $row['comment'] ?></p>
                    </div>

                    <div class="row reviews">
                        <?php foreach ($row['images'] as $image) { ?>
                            <div class="col-md-1">
                                <div class="review-box review-img">
                                    <!- - <a href="<?= file_exists(FCPATH . REVIEW_IMG_PATH . $image) ? $image : base_url() . NO_IMAGE; ?>" data-lightbox="review-images">
                                                <img  src="<?= file_exists(FCPATH . REVIEW_IMG_PATH . $image) ? $image : base_url() . NO_IMAGE; ?>" alt="Review Image">
                                            </a> - ->
                                    <a href="<?= $image; ?>" data-lightbox="review-images">
                                        <img class="lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $image; ?>" alt="Review Image">
                                    </a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    
                </div>
    <?php }
        }
    } ?>

</section>

<div class="row mt-2 mb-6" style="justify-content: center;">
    <aside class="col-lg-4 sidebar">
        <?php 
        // if (!empty($my_rating)) {
        if ($product['product'][0]['is_purchased'] == 1 && !empty($my_rating)) {
            $form_link = (!empty($my_rating)) ? base_url('products/save-rating') : base_url('products/save-rating');  ?>
            <div id="rating-box" class="">
                <div class="add-review p-3">
                    <h3 class="text-center mb-4">Edit Your Review</h3>
                    <form action="<?= $form_link ?>" id="product-rating-form" method="POST">
                        <?php if (!empty($my_rating)) { ?>
                            <input type="hidden" name="rating_id" value="<?= $my_rating['product_rating'][0]['id'] ?>">
                        <?php } ?>
                        <input type="hidden" name="product_id" value="<?= $product['product'][0]['id'] ?>">

                        <label for="rating" class="fs-17">Your rating</label>
                        <div class="pl-0 product-rating-small rating-form mb-2 mt-n2" dir="ltr">
                            <input id="input" name="rating" class="rating rating-loading d-none mt-n5" data-size="xs" value="<?= isset($my_rating['product_rating'][0]['rating']) ? $my_rating['product_rating'][0]['rating'] : '0' ?>" data-show-clear="false" data-show-caption="false" data-step="1">
                        </div>
                        <div class="form-group fs-17">
                            <label for="exampleFormControlTextarea1">Your Review</label>
                            <textarea class="form-control" name="comment" rows="3"><?= isset($my_rating['product_rating'][0]['comment']) ? $my_rating['product_rating'][0]['comment'] : '' ?></textarea>
                        </div>
                        <div class="form-group fs-17">
                            <label for="exampleFormControlTextarea1">Images</label>
                            <input type="file" name="images[]" accept="image/x-png,image/gif,image/jpeg" multiple />
                        </div>
                        <button class="cretzo btn btn-dark btn-primary rounded-pill w-100" id="rating-submit-btn">Submit</button>
                    </form>
                </div>
            </div>
        <?php }
        // else if (true) {
        else if ($product['product'][0]['is_purchased'] == 1) {
            $form_link = (!empty($my_rating)) ? base_url('products/edit-rating') : base_url('products/save-rating');
        ?>
            <!- - <div class=" p-3 bg-soft-primary <?= (!empty($my_rating)) ? 'd-none' : '' ?>" id="rating-box"> - ->
            <div class=" p-3" id="rating-box">
                <div class="add-review">
                    <h3 class="review-title"><?= !empty($this->lang->line('add_your_review')) ? $this->lang->line('add_your_review') : 'Add Your Review' ?></h3>
                    <form action="<?= $form_link ?>" id="product-rating-form" method="POST">
                        <?php if (!empty($my_rating)) { ?>
                            <input type="hidden" name="rating_id" value="<?= $my_rating['product_rating'][0]['id'] ?>">
                        <?php } ?>
                        <input type="hidden" name="product_id" value="<?= $product['product'][0]['id'] ?>">
                        <label for="rating" class="fs-17">Your rating</label>
                        <div class="col-md-12 pl-0 product-rating-small rating-form fs-17 mt-n2 mb-2" dir="ltr">
                            <input id="input" name="rating" class="rating rating-loading d-none mt-n5" data-size="xs" value="<?= isset($my_rating['product_rating'][0]['rating']) ? $my_rating['product_rating'][0]['rating'] : '0' ?>" data-show-clear="false" data-show-caption="false" data-step="1">
                        </div>
                        <div class="form-group">
                            <label for="exampleFormControlTextarea1">Your Review</label>
                            <textarea class="form-control" name="comment" rows="3"><?= isset($my_rating['product_rating'][0]['comment']) ? $my_rating['product_rating'][0]['comment'] : '' ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="exampleFormControlTextarea1"><?= !empty($this->lang->line('images')) ? $this->lang->line('images') : 'Images' ?></label>
                            <input type="file" name="images[]" accept="image/x-png,image/gif,image/jpeg" multiple />
                        </div>
                        <button class="cretzo btn btn-dark btn-primary rounded-pill w-100" id="rating-submit-btn"><?= !empty($this->lang->line('submit')) ? $this->lang->line('submit') : 'Submit' ?></button>
                    </form>
                </div>
            </div>
        <?php } ?>

    </aside>
</div> 

-->


<!-- /review -->

<!-- more form this seller -->
<section class="more-seller-container wrapper bg-gray" style="padding-bottom: 1rem;">
    <h1 class="sub-heading container-heading">More from this seller</h1>
    <p class="text-s op-8 container-des">Check out more products from this seller.</p>
    <p class="text-n op-8 container-des" style="color: var(--color-orange);">
        <!-- <a target="_BLANK" href="<?= base_url('products?seller=' . $seller_slug[0]['slug']) ?>" class="hover text-decoration-none">View Seller Profile</a> -->
        <a target="_BLANK" href="<?= base_url('sellers/seller_details/' . $seller_slug[0]['slug']) ?>" class="hover text-decoration-none">View Seller Profile</a>
    </p>
    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->
    <div class="card-container-more-seller">

        <?php 
        $i = 0;
        foreach ($seller_products['product'] as $product_row) { 
            // break after 6 products
            if ($i == 6) {
                break;
            }
        ?>
        
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
                    <!-- <?php
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
                    ?> -->

                    <!-- add to cart button -->
                    <!-- <a href="#" class="add_to_cart cart-btn text-decoration-none text-s" style="padding-bottom: 1.6em; padding-top: 12px;" data-product-id="<?= $product_row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-slug="<?= $product_row['slug'] ?>" data-product-title="<?= $product_row['name'] ?>" data-product-image="<?= $product_row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                            <i class="uil uil-shopping-bag"></i>&nbsp;Add to Cart</a> -->
                </div>
            </div>
            
        <?php
        $i++;
        } 
        ?>

    </div>

    <!-- <div class="container-des" style="margin-bottom: 1em; margin-top: 2em;">
        <a target="_BLANK" href="<?= base_url('products?seller=' . $seller_slug[0]['slug']) ?>" class="text-decoration-none">
            <button class="see-all" style="text-transform: none;">
                <span>More from '<?= ucfirst($product['product'][0]['store_name']) ?>' </span>
            </button>
        </a>
    </div> -->

    <?php echo generateSeeAllButton(base_url('products?seller=' . $seller_slug[0]['slug']), "More from " . ucfirst($product['product'][0]['store_name'])); ?>

</section>



<!-- <hr style="margin: 1rem 0rem; border-top: 1px solid #bbb;" class="bg-gray"> -->

<section class="more-seller-container wrapper bg-gray" style="padding-bottom: 2rem;">
    <h1 class="sub-heading container-heading mt-4">Similar Products</h1>
    <p class="text-s op-8 container-des">Not satisfied yet ? Check out more similar products !</p>
    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->
    <div class="card-container-more-seller">

        <?php 
        $i = 0;
        foreach ($related_products['product'] as $product_row) { 
            // break after 6 products
            if ($i == 6) {
                break;
            }
        ?>
        
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
                    <!-- <?php
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
                    ?> -->

                    <!-- add to cart button -->
                    <!-- <a href="#" class="add_to_cart cart-btn text-decoration-none text-s" style="padding-bottom: 1.6em; padding-top: 12px;" data-product-id="<?= $product_row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-slug="<?= $product_row['slug'] ?>" data-product-title="<?= $product_row['name'] ?>" data-product-image="<?= $product_row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                            <i class="uil uil-shopping-bag"></i>&nbsp;Add to Cart</a> -->
                </div>
            </div>
            
        <?php
        $i++;
        } 
        ?>
        
    </div>

    <?php echo generateSeeAllButton(base_url('products?seller=' . $seller_slug[0]['slug']), "More Similar Products" ); ?>
    
</section>


<div class="modal fade" id="add-faqs-form">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header pb-5 pt-8">
                <h4 class="modal-title">Add Faq</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body pb-8 pt-0">
                <form method="post" action='<?= base_url('products/add_faqs') ?>' id="add-faqs">
                    <div class="form-group">

                        <input type="hidden" name=" <?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                        <input type="hidden" name="user_id" value="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';  ?>">
                        <input type="hidden" name="seller_id" value="<?= $product['product'][0]['seller_id'];  ?>">
                        <input type="hidden" name="product_id" value="<?= $product['product'][0]['id']  ?>">
                        <input type="text" class="form-control" id="question" placeholder="Enter Your Question Here" name="question">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill" id="add-faqs" name="add-faqs" value="Save">Add Question</button>
                    <div class="mt-3">
                        <div id="add_faqs_result"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="user-review-images" class='product-page-content'>
    <div class="container" id="review-image-div">
        <?php
        if (isset($review_images['product_rating']) && !empty($review_images['product_rating'])) { ?>
            <div class="d-flex flex-wrap reviews" id="user_image_data">

            </div>
            <div id="load_more_div">
            </div>
        <?php } ?>
    </div>
</div>
<script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>

<!-- Modal for all customer images -->
<div class="modal fade" id="imageGalleryModal" tabindex="-1" role="dialog" aria-labelledby="imageGalleryModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageGalleryModalLabel">More Photos</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <!-- Thumbnails List -->
            <?php foreach ($product_ratings['product_rating'] as $row) {
                foreach ($row['images'] as $image) { ?>
                    <div class="col-md-4">
                        <a href="<?= $image; ?>" data-lightbox="review-images-all-total" data-title='"<?= $row['comment']?>" - <?=$row['user_name']?>'>
                            <img src="<?= $image; ?>" class="img-thumbnail" alt="Review Image">
                        </a>
                    </div>
            <?php }
            } ?>
        </div>
      </div>
      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div> -->
    </div>
  </div>
</div>

<?php

    function generatePriceElement2($product){

        $discounted_price = $product[0]['variants'][0]['special_price'];
        $price = $product[0]['variants'][0]['price'];

        $discountPercentage = 0;
        if ($discounted_price < $price) {
            $discountPercentage = round((($price - $discounted_price) / $price) * 100);
        }
        
        // $currencyText = $settings['currency'];
        // $currencyText = "Rs. ";
        $currencyText = "₹ ";

        if (($discounted_price < $price) && ($discounted_price != 0)) {
            return '
            <div class="heading-n fw-b product-price mb-0">
                <p id="discounted-price" class="current-price">'
                    . $currencyText . number_format($discounted_price, 2) .
                '</p>
                <p id="normal-price" class="text-b previous-price">'
                    . $currencyText . number_format($price, 2) .
                '</p>
                <p id="discount-percentage" class="discount">'
                    . "(" . $discountPercentage . "% OFF)" .
                '</p>
            </div>';
        } else {
            return '
            <div class="heading-n fw-b product-price mb-0">
                <p id="normal-price" class="current-price">'
                    . $currencyText . number_format($price, 2) .
                '</p>
            </div>';
        }

    }

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
    
    function generateSeeAllButton($href, $text){
        return '
                <div class="container-des" style="margin-bottom: 1em; margin-top: 0em;">
                    <a href="' . $href . '">
                        <button class="see-all" style="text-transform: none;">
                            <span>' . $text . '</span>
                            <span class="see-all-arrow ml-1"> <img src="' . base_url('assets/front_end/cretzo/img/new_cretzo/arrow-right.svg') . '"/> </span>
                        </button>
                    </a>
                </div>
                ';
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