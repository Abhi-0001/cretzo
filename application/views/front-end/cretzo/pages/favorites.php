<!-- wishlist -->
<section class="wishlist-container">
    <h1 class="heading-b ta-c mt-4 fw-bold">MY WISHLIST</h1>
    <!-- <p class="text-n op-6 container-des">Products you wish that your poor ass had !</p> -->
    <!-- <p class="ta-c"><img class="container-img" src="<?= base_url('assets/front_end/cretzo/img/arrow.png') ?>"></p> -->

    <div class="wishlist">
        <p class="text-n no-of-item-text">My Wishlist <span><?= count($products) ?></span> item</p>

        

            <?php
                if (isset($products) && !empty($products)) { ?>
                    <div class="wishlist-card-container">
            <?php 
                    foreach ($products as $product_row) { ?>
                        <div class="cretzo-card card-type-one wishlist-card">
                            
                            <a class="text-decoration-none" href="<?= base_url('products/details/' . $product_row['slug']) ?>">
                                <div>
                                    <div class="card-img">
                                        
                                        <!-- <img class="card-img-img" src="../images/outfit1.png"> -->
                                        <img class="card-img-img lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $product_row['image_sm'] ?>" alt="<?= $product_row['name'] ?>">

                                        <a class="remove-from-wishlist-btn text-decoration-none" href="#" data-product-id="<?= $product_row['id'] ?>" style="color:red;">
                                            <div class="cross-container">
                                                ╳
                                                <!-- <img class="cross-icon2" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/cross-icon2.png') ?>"> -->
                                            </div>
                                            <!-- <div class="rating-container">
                                                <img class="cross-icon2" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/cross-icon2.png') ?>">
                                            </div> -->
                                        </a>
                                                    
                                        <button class="text-n addwishlist-btn"><img class="heart-icon" src="<?= base_url('assets/front_end/cretzo/img/heart-icon.png') ?>">Wishlist</button>
                                        
                                    </div>
                                    <div class="card-des">
                                        <h1 class="text-b ta-c no-wrap"><?= $product_row['name'] ?></h1>

                                        <?php echo generatePriceElement($product_row); ?>
                                        <!-- <p class="price-container text-es ta-c"><span class="discounted-price">Rs. 2500</span><span class="original-price op-6">Rs. 2500</span><span class="off-persent fw-b">(20% OFF)</span></p> -->

                                    </div>
                                </div>
                            </a>

                        
                            <!-- <button class="btn text-b bag-btn">Move to bag</button> -->
                            
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
                            <!-- add to cart button (Move to bag) -->
                            <button class="text-b bag-btn">
                                <a href="#" class="add_to_cart text-decoration-none" style="padding-bottom: 8px; padding-top: 10px;" data-product-id="<?= $product_row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-slug="<?= $product_row['slug'] ?>" data-product-title="<?= $product_row['name'] ?>" data-product-image="<?= $product_row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                                    <i class="uil uil-shopping-bag"></i>
                                    &nbsp;Move to bag
                                </a>
                            </button>

                        </div>
            <?php   } ?>
                    </div>
            <?php
                } else { ?>
                    <div class="col-12 m-5">
                        <div class="text-center">
                            <h1 class="h2 fw-normal">No products found in your wishlist.</h1>
                            <a href="<?= base_url('products') ?>" class="button button-rounded button-warning" style="color: var(--color-orange)"><?= !empty($this->lang->line('go_to_shop')) ? $this->lang->line('go_to_shop') : 'Go to Shop' ?></a>
                        </div>
                    </div>
            <?php } ?>
            
            <!-- dummy data -->
            <!-- <div class="cretzo-card card-type-one wishlist-card">
                <div class="card-img">
                    <img class="card-img-img" src="../images/outfit1.png">
                    <div class="rating-container">
                        <img class="cross-icon2" src="../images/cross-icon2.png">
                    </div>
                    <button class="text-n addwishlist-btn"><img class="heart-icon" src="../images/heart-icon.png">Wishlist</button>
                </div>
                <div class="card-des">
                    <h1 class="text-b ta-c">Lorem, ipsum dolor.</h1>
                    <p class="price-container text-es ta-c"><span class="discounted-price">Rs. 2500</span><span class="original-price op-6">Rs. 2500</span><span class="off-persent fw-b">(20% OFF)</span></p>
                </div>
                <button class="btn text-b bag-btn">Move to bag</button>
            </div> -->
            
        
    </div>
</section>
<!-- /wishlist -->

<?php
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