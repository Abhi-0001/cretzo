<!-- <?php echo '<pre>'; print_r($products); echo '</pre>'; ?> -->


<input type="hidden" id="product-filters" value='<?= (!empty($filters)) ? escape_array($filters) : ""  ?>' data-key="<?= $filters_key ?>" />
<input type="hidden" id="brand-filters" value='<?= (!empty($brands)) ? escape_array($brands) : ""  ?>' data-key="<?= $filters_key ?>" />
<input type="hidden" id="category-filters" value='<?= (!empty($categories) ? ($categories) : "") ?>' data-key="<?= $filters_key ?>" />


<div class="content-wrapper">
    <section class="wrapper listing-page bg-light">
        <div class="container pb-14 pb-md-16 pt-12">
            <div class="d-flex row">
                <div class="col-md-12 order-md-2 <?= (isset($products['filters']) && !empty($products['filters'])) || (isset($categories) && !empty($categories)) || (isset($brands) && !empty($brands)) ? "col-lg-9" : "col-lg-12" ?>">

                    <div class="align-items-center d-flex flex-nowrap justify-content-between mb-2">
                        <a href="#" class="nav-link filter-sidebar-mobile" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-category"><i class="fs-35 text-body uil uil-list-ui-alt"></i></a>
                        <div>
                            <h2 class="display-6 mb-1"><?= !empty($this->lang->line('products')) ? $this->lang->line('products') : 'Products' ?></h2>
                        </div>

                        <div class="align-items-md-center d-md-flex gap-2">
                            <?php if (isset($products) && !empty($products['product'])) { ?>
                                <!-- <div class="col-12 pl-0">
                                    <div class="dropdown">
                                        <div class="col-12 sort-by py-3 pl-0"> -->
                                <?php if (isset($products) && !empty($products['product'])) { ?>
                                    <div class="d-md-grid ele-wrapper">
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
                                    </div>

                                    <div class="align-items-center d-flex gap-2 justify-content-between dropdown">
                                        <label class="d-flex dropdown-label gap-1 mb-0 text-dark"> <?= !empty($this->lang->line('show')) ? $this->lang->line('show') : 'Show' ?>:
                                        </label>
                                        <a class="dropdown-border dropdown-toggle mr-4" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?= ($this->input->get('per-page', true) ? $this->input->get('per-page', true) : '12') ?> <span class="caret"></span></a>
                                        <a href="#" id="product_grid_view_btn" class="grid-view text-decoration-none text-dark"><i class="uil uil-th fs-20"></i></a>
                                        <a href="#" id="product_list_view_btn" class="grid-view ps-3 text-decoration-none text-dark"><i class="uil uil-list-ul fs-20"></i></a>
                                        <div class="dropdown-menu custom-dropdown-menu" aria-labelledby="navbarDropdown" id="per_page_products">
                                            <a class="dropdown-item" href="#" data-value=12>12</a>
                                            <a class="dropdown-item" href="#" data-value=16>16</a>
                                            <a class="dropdown-item" href="#" data-value=20>20</a>
                                            <a class="dropdown-item" href="#" data-value=24>24</a>
                                        </div>
                                    </div>

                                <?php } ?>
                                <!-- </div>
                                    </div>
                                </div> -->

                            <?php } ?>
                        </div>
                    </div>


                    <?php if (isset($sub_categories) && !empty($sub_categories)) { ?>
                        <div class="text-left py-3">
                            <?php if (isset($single_category) && !empty($single_category)) { ?>
                                <span class="h3"><?= $single_category['name'] ?> <?= !empty($this->lang->line('category')) ? $this->lang->line('category') : 'Category' ?></span>
                            <?php } ?>
                        </div>
                        <div class="category-section container-fluid text-center">
                            <div class="row">
                                <?php foreach ($sub_categories as $key => $row) { ?>
                                    <div class="card col-md-2 col-sm-6 mr-2 mb-2 shadow-xl w-20">
                                        <div class="category-image sub_category-image-container">
                                            <a href="<?= base_url('products/category/' . html_escape($row->slug)) ?>">
                                                <img class="pic-1 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $row->image ?>">
                                            </a>
                                            <div class="social">
                                                <span><?= html_escape($row->name) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>


                    <?php if (isset($products) && !empty($products['product'])) { ?>
                        <?php if (isset($_GET['type']) && $_GET['type'] == "list") { ?>
                            <?php foreach ($products['product'] as $row) { ?>
                                <div class="row p-4" title="<?= $row['name']; ?>">
                                    <div class="col-md-4">
                                        <div class="">
                                            <div class="product-image item">
                                                <figure class="rounded">
                                                    <a href="<?= base_url('products/details/' . $row['slug']) ?>">
                                                        <img class="pic-1 lazy  w-100" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $row['image_sm'] ?>" style="object-fit: cover;">
                                                    </a>
                                                    <a class="item-like text-decoration-none add-to-fav-btn 
                                            <?= ($row['is_favorite'] == 1) ? 'fa fa-heart' : 'fa fa-heart-o' ?>  
                                            " href="#" data-bs-toggle="white-tooltip" title="Add to wishlist" data-product-id="<?= $row['id'] ?>" style="color: <?= ($row['is_favorite'] == 1) ? 'red' : '' ?>">
                                                        <i class=""></i>
                                                    </a>

                                                    <a href="#" class="quick-view-btn item-view text-decoration-none" data-bs-toggle="white-tooltip" title="Quick View" data-tip="Quick View" data-product-id="<?= $row['id'] ?>" data-product-variant-id="<?= $row['variants'][0]['id'] ?>" data-izimodal-open="#quick-view">
                                                        <i class="uil uil-eye"></i>
                                                    </a>
                                                    <?php
                                                    if (count($row['variants']) <= 1) {
                                                        $variant_id = $row['variants'][0]['id'];
                                                        $modal = "";
                                                    } else {
                                                        $variant_id = "";
                                                        $modal = "#quick-view";
                                                    }
                                                    ?>

                                                    <?php
                                                    if (count($row['variants']) <= 1) {
                                                        $variant_id = $row['variants'][0]['id'];
                                                    } else {
                                                        $variant_id = "";
                                                    }
                                                    ?>
                                                    <a href="#" class="compare item-compare text-decoration-none" data-bs-toggle="white-tooltip" title="Compare" data-tip="Compare" data-product-id="<?= $row['id'] ?>" data-product-variant-id="<?= $variant_id ?>">
                                                        <i class="uil uil-exchange-alt"></i>
                                                    </a>

                                                    <?php if (isset($row['min_max_price']['special_price']) && $row['min_max_price']['special_price'] != '' && $row['min_max_price']['special_price'] != 0 && $row['min_max_price']['special_price'] < $row['min_max_price']['min_price']) { ?>
                                                        <span class="avatar bg-pink text-white w-10 h-10 position-absolute text-uppercase fs-13" style="top: 1rem; left: 1rem;">
                                                            <span><?= !empty($this->lang->line('sale')) ? $this->lang->line('sale') : 'Sale' ?></span>
                                                        </span>
                                                    <?php } ?>

                                                </figure>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="product-content">
                                            <h4 class="list-product-title title" title="<?= $row['name']; ?>"><a class="text-decoration-none text-dark" href="<?= base_url('products/details/' . $row['slug']) ?>"><?= $row['name'] ?></a></h4>
                                            <div class="col-md-12 mb-3 product-rating-small ps-0 " dir="ltr">
                                                <input id="input" name="rating" class="rating rating-loading d-none" data-size="xs" value="<?= $row['rating'] ?>" data-show-clear="false" data-show-caption="false" readonly>
                                            </div>

                                            <div class="mt-n2">
                                                <p class="text-muted list-product-desc"><?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($row['short_description']))), 80); ?></p>
                                            </div>
                                            <p class="price text-dark mt-n2">
                                                <span class="amount">
                                                    <?php echo $settings['currency'] ?>
                                                    <small style='font-size: 20px;'>
                                                        <?php
                                                        // $price = get_price_range_of_product($row['id']);
                                                        $price = $row['variants'][0]['price'];
                                                        echo number_format($price, 2);
                                                        ?>
                                                    </small>
                                                </span>
                                            </p>

                                            <div class="button button-sm m-0 p-0">
                                                <?php $variant_price = ($row['variants'][0]['special_price'] > 0 && $row['variants'][0]['special_price'] != '') ? $row['variants'][0]['special_price'] : $row['variants'][0]['price'];
                                                $data_min = (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1;
                                                $data_step = (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1;
                                                $data_max = (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : 0;
                                                ?>
                                                <a href="#" class="add_to_cart  btn btn-sm btn-outline-primary rounded-pill mt-2" data-product-id="<?= $row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-title="<?= $row['name'] ?>" data-product-slug="<?= $row['slug'] ?>" data-product-image="<?= $row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                                                    <i class="uil uil-shopping-bag"></i>&nbsp;<?= !empty($this->lang->line('add_to_cart')) ? $this->lang->line('add_to_cart') : 'Add To Cart' ?></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <!-- /.grid -->

                        <?php } else { ?>
                            <hr class="mb-10 mt-10">
                            <div class="grid grid-view mt-10 projects-masonry shop">
                                <div class="row gx-md-8 gy-10 gy-md-13 isotope">
                                    <?php foreach ($products['product'] as $row) { ?>
                                        <div class="project item col-md-6 col-xl-4" title="<?= $row['name']; ?>">
                                            <figure class="rounded mb-6">
                                                <a href="<?= base_url('products/details/' . $row['slug']) ?>">
                                                    <img class="pic-1 lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $row['image_sm'] ?>" style="object-fit: cover;">
                                                </a>
                                                <a class="item-like text-decoration-none add-to-fav-btn 
                                                        <?= ($row['is_favorite'] == 1) ? 'fa fa-heart' : 'fa fa-heart-o' ?>  
                                                        " href="#" data-bs-toggle="white-tooltip" title="Add to wishlist" data-product-id="<?= $row['id'] ?>" style="color: <?= ($row['is_favorite'] == 1) ? 'red' : '' ?>">
                                                    <i class=""></i>
                                                </a>

                                                <a href="#" class="quick-view-btn item-view text-decoration-none" data-bs-toggle="white-tooltip" title="Quick View" data-tip="Quick View" data-product-id="<?= $row['id'] ?>" data-product-variant-id="<?= $row['variants'][0]['id'] ?>" data-izimodal-open="#quick-view">
                                                    <i class="uil uil-eye"></i>
                                                </a>
                                                <?php
                                                if (count($row['variants']) <= 1) {
                                                    $variant_id = $row['variants'][0]['id'];
                                                    $modal = "";
                                                } else {
                                                    $variant_id = "";
                                                    $modal = "#quick-view";
                                                }
                                                ?>

                                                <?php
                                                if (count($row['variants']) <= 1) {
                                                    $variant_id = $row['variants'][0]['id'];
                                                } else {
                                                    $variant_id = "";
                                                }
                                                ?>
                                                <a href="#" class="compare item-compare text-decoration-none" data-bs-toggle="white-tooltip" title="Compare" data-tip="Compare" data-product-id="<?= $row['id'] ?>" data-product-variant-id="<?= $variant_id ?>">
                                                    <i class="uil uil-exchange-alt"></i>
                                                </a>

                                                <?php if (isset($row['min_max_price']['special_price']) && $row['min_max_price']['special_price'] != '' && $row['min_max_price']['special_price'] != 0 && $row['min_max_price']['special_price'] < $row['min_max_price']['min_price']) { ?>
                                                    <span class="avatar bg-pink text-white w-10 h-10 position-absolute text-uppercase fs-13" style="top: 1rem; left: 1rem;">
                                                        <span><?= !empty($this->lang->line('sale')) ? $this->lang->line('sale') : 'Sale' ?></span>
                                                    </span>
                                                <?php } ?>

                                                <?php $variant_price = ($row['variants'][0]['special_price'] > 0 && $row['variants'][0]['special_price'] != '') ? $row['variants'][0]['special_price'] : $row['variants'][0]['price'];
                                                $data_min = (isset($row['minimum_order_quantity']) && !empty($row['minimum_order_quantity'])) ? $row['minimum_order_quantity'] : 1;
                                                $data_step = (isset($row['minimum_order_quantity']) && !empty($row['quantity_step_size'])) ? $row['quantity_step_size'] : 1;
                                                $data_max = (isset($row['total_allowed_quantity']) && !empty($row['total_allowed_quantity'])) ? $row['total_allowed_quantity'] : 0;
                                                ?>
                                                <a href="#" class="add_to_cart item-cart text-decoration-none" data-product-id="<?= $row['id'] ?>" data-product-variant-id="<?= $variant_id ?>" data-product-title="<?= $row['name'] ?>" data-product-image="<?= $row['image']; ?>" data-product-price="<?= $variant_price; ?>" data-min="<?= $data_min; ?>" data-step="<?= $data_step; ?>" data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($row['short_description'])))); ?>" data-izimodal-open="<?= $modal ?>">
                                                    <i class="uil uil-shopping-cart-alt"></i>&nbsp;<?= !empty($this->lang->line('add_to_cart')) ? $this->lang->line('add_to_cart') : 'Add To Cart' ?></a>

                                            </figure>
                                            <div class="post-header">
                                                <div class="d-flex flex-row align-items-center justify-content-between mb-2">
                                                    <input id="input" name="rating" class="rating rating-loading d-none" data-size="xs" value="<?= $row['rating'] ?>" data-show-clear="false" data-show-caption="false" readonly>
                                                </div>
                                                <h4 class="post-title title" title="<?= $row['name']; ?>">
                                                    <a href="<?= base_url('products/details/' . $row['slug']) ?>" class="link-dark text-decoration-none"><?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($row['name']))), 26); ?></a>
                                                </h4>

                                                <p class="price text-muted">
                                                    <span class="amount">
                                                        <?php echo $settings['currency'] ?>
                                                        <small style='font-size: 20px;'>
                                                            <?php
                                                            // $price = get_price_range_of_product($row['id']);
                                                            $price = $row['variants'][0]['price'];
                                                            echo number_format($price, 2);
                                                            ?>
                                                        </small>
                                                    </span>
                                            </div>
                                            <!-- /.post-header -->
                                        </div>
                                    <?php } ?>
                                    <!-- /.item -->

                                    <!-- /.item -->
                                </div>
                                <!-- /.row -->
                            </div>
                        <?php } ?>
                    <?php } ?>

                    <?php if ((!isset($sub_categories) || empty($sub_categories)) && (!isset($products) || empty($products['product']))) { ?>
                        <div class="col-12 text-center">
                            <h1 class="h2">No Products Found.</h1>
                            <a href="<?= base_url('products') ?>" class="btn btn-sm rounded-pill btn-warning"><?= !empty($this->lang->line('go_to_shop')) ? $this->lang->line('go_to_shop') : 'Go to Shop' ?></a>
                        </div>
                    <?php } ?>


                    <nav class="text-center mt-14 d-flex overflow-auto" aria-label="pagination">
                        <?= (isset($links)) ? $links : '' ?>
                    </nav>
                    <!-- /nav -->
                </div>


                <aside class="col-md-3 sidebar">
                    <!-- Dektop Sidebar -->
                    <div class="row">
                        <div class=" order-md-1 filter-section sidebar-filter-sm pt-2 pb-2 filter-sidebar-view">
                            <?php if (isset($products['filters']) && !empty($products['filters'])) { ?>
                                <div class="align-content-center d-flex justify-content-between">
                                    <h6 class="m-0"><?= label('attributes', 'Attributes') ?></h6>
                                    <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                </div>
                                <div id="product-filters-desktop" class="filter_attributes mb-5 mt-2">
                                    <?php foreach ($products['filters'] as $key => $row) {
                                        $row_attr_name = str_replace(' ', '-', $row['name']);
                                        $attribute_name = isset($_GET[strtolower('filter-' . $row_attr_name)]) ? $this->input->get(strtolower('filter-' . $row_attr_name), true) : 'null';
                                        $selected_attributes = explode('|', $attribute_name);
                                        $attribute_values = explode(',', $row['attribute_values']);
                                        $attribute_values_id = explode(',', $row['attribute_values_id']);
                                    ?>

                                        <div class="accordion accordion-wrapper" id="accordionSimpleExample">

                                            <div class="card plain accordion-item">
                                                <div class="card-header" id="h1">
                                                    <button class="accordion-button text-decoration-none h6 text-dark collapsed" data-bs-toggle="collapse" data-bs-target="#c<?= $key ?>" aria-expanded="true" aria-controls="c<?= $key ?>" style="cursor: pointer;"><?= html_escape($row['name']) ?></button>
                                                </div>
                                                <!-- card-header -->
                                                <div id="c<?= $key ?>" class="accordion-collapse collapse <?= ($attribute_name != 'null') ? 'show' : '' ?>" aria-labelledby="h1" data-bs-parent="#accordionSimpleExample">
                                                    <div class="card-body">
                                                        <?php foreach ($attribute_values as $key => $values) {
                                                            $values = strtolower($values);
                                                        ?>
                                                            <div class="input-container d-flex">
                                                                <?= form_checkbox(
                                                                    $values,
                                                                    $values,
                                                                    (in_array($values, $selected_attributes)) ? TRUE : FALSE,
                                                                    array(
                                                                        'class' => 'toggle-input product_attributes',
                                                                        'id' => $row_attr_name . ' ' . $values,
                                                                        'data-attribute' => strtolower($row['name']),
                                                                    )
                                                                ) ?>
                                                                <label class="toggle checkbox" for="<?= $row_attr_name . ' ' . $values ?>">
                                                                    <div class="toggle-inner"></div>
                                                                </label>
                                                                <?= form_label($values, $row_attr_name . ' ' . $values, array('class' => 'text-label')) ?>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                            <div id="brand-filters-desktop" class="filter_brands mb-5 mt-2">
                                <?php if (isset($brands) && !empty($brands)) { ?>
                                    <div class="align-content-center d-flex justify-content-between">
                                        <h6 class="m-0"><?= label('brands', 'Brands') ?></h6>
                                        <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                    </div>
                                    <div class="brand_filter d-flex flex-wrap gap-4 mb-5 mt-2 p-1">
                                        <?php
                                        $brands_filter = json_decode(($brands), true);
                                        foreach ($brands_filter as $key => $value) {
                                            // echo "<pre>";
                                            // print_r($value);
                                            // die;
                                            //     $brand_data = fetch_details('brands', ['name' => $value]);
                                        ?>
                                            <div class="brand_div form-check">
                                                <label class="form-check-label" for="<?= $value['brand_id'] ?>-brand">
                                                    <input class="brand form-check-input" type="radio" name="brandRadio" data-value="<?= $value['brand_slug'] ?>" id="<?= $value['brand_id'] ?>-brand" checked>
                                                    <img src="<?= base_url($value['brand_img']) ?>" alt="brand-logo" class="h-6">
                                                </label>

                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <div id="category-filters-desktop" class="filter_categories mb-5 mt-2">
                                <?php if (isset($categories)) { ?>
                                    <div class="align-content-center d-flex justify-content-between">
                                        <h6 class="m-0"><?= label('categories', 'Categories') ?></h6>
                                        <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                    </div>
                                    <div class="category_filter mb-5 mt-2">
                                        <?php
                                        $categories_filter = json_decode(($categories), true);
                                        // echo "<pre>";
                                        // print_r($categories_filter);
                                        // die;
                                        foreach ($categories_filter as $key => $value) {
                                            // echo "<pre>";
                                            //     print_r($value);
                                            //     die;
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input category" type="radio" name="categoryRadio" data-value="<?= $value['id'] ?>" id="<?= $value['id'] ?>" value="" checked>
                                                <label class="form-check-label" for="<?= $value['id'] ?>">
                                                    <?= $value['name'] ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php  } ?>
                            </div>

                            <div class="text-center d-flex gap-2">
                                <button class="btn btn-primary btn-sm rounded-pill w-50 product_filter_btn"><?= label('filter', 'Filter') ?></button>
                                <a href="#" id="reload" class="btn btn-outline-red btn-sm rounded-pill w-50"><?= label('clear', 'Clear') ?></a>
                            </div>
                        </div>

                    </div>

                    <!-- Mobile Filter Menu -->
                    <div class="offcanvas offcanvas-start bg-light filter-sidebar-mobile" id="offcanvas-category" data-bs-scroll="true">
                        <div class="container">
                            <div class="offcanvas-header flex-row-reverse">
                                <!-- <h3 class="mt-0">Showing <span class="text-primary">12</span> Products</h3> -->
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                            </div>
                            <div class="col-lg-3">

                                <div id="product-filters-mobile">
                                    <div class="align-content-center d-flex justify-content-between">
                                        <h6 class="m-0"><?= label('attributes', 'Attributes') ?></h6>
                                        <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                    </div>
                                    <?php if (isset($products['filters']) && !empty($products['filters'])) { ?>
                                        <div class="accordion" id="accordionExample">
                                            <?php foreach ($products['filters'] as $key => $row) {
                                                $row_attr_name = str_replace(' ', '-', $row['name']);
                                                $attribute_name = isset($_GET[strtolower('filter-' . $row_attr_name)]) ? $this->input->get(strtolower('filter-' . $row_attr_name), true) : 'null';
                                                $selected_attributes = explode('|', $attribute_name);
                                                $attribute_values = explode(',', $row['attribute_values']);
                                                $attribute_values_id = explode(',', $row['attribute_values_id']);
                                            ?>


                                                <div class="plain accordion-item">
                                                    <div class="card-header m-2" id="headingOne">
                                                        <a class="accordion-button text-decoration-none h6 text-primary collapsed" data-bs-toggle="collapse" data-bs-target="#m<?= $key ?>" aria-expanded="false" aria-controls="#m<?= $key ?>" style="cursor: pointer;"><?= html_escape($row['name']) ?></a>
                                                    </div>
                                                    <!-- card-header -->
                                                    <div id="c<?= $key ?>" class="accordion-collapse collapse <?= ($attribute_name != 'null') ? 'show' : '' ?>" aria-labelledby="headingOne" data-bs-parent="#accordionExample">'+
                                                        <div class="card-body">
                                                            <?php foreach ($attribute_values as $key => $values) {
                                                                $values = strtolower($values);
                                                            ?>
                                                                <div class="input-container d-flex">
                                                                    <?= form_checkbox(
                                                                        $values,
                                                                        $values,
                                                                        (in_array($values, $selected_attributes)) ? TRUE : FALSE,
                                                                        array(
                                                                            'class' => 'toggle-input product_attributes',
                                                                            'id' => 'm' . $row_attr_name . ' ' . $values,
                                                                            'data-attribute' => strtolower($row['name']),
                                                                        )
                                                                    ) ?>
                                                                    <label class="toggle checkbox" for="<?= 'm' . $values ?>">
                                                                        <div class="toggle-inner"></div>
                                                                    </label>
                                                                    <?= form_label($values, 'm' . $row_attr_name . ' ' . $values, array('class' => 'text-label')) ?>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div id="brand-filters-mobile">
                                    <?php if (isset($brands)) { ?>
                                        <div class="align-content-center d-flex justify-content-between">
                                            <h6 class="m-0"><?= label('brands', 'Brands') ?></h6>
                                            <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                        </div>
                                        <div class="brand_filter d-flex flex-wrap gap-4 mb-5 mt-2 p-1">
                                            <?php
                                            $brands_filter = json_decode(($brands), true);
                                            foreach ($brands_filter as $key => $value) {
                                                // echo "<pre>";
                                                // print_r($value);
                                                // die;
                                                //     $brand_data = fetch_details('brands', ['name' => $value]);
                                            ?>
                                                <div class="brand_div">
                                                    <label class="" for="<?= $value['brand_id'] ?>">
                                                        <input class="brand" type="radio" name="brandRadio" data-value="<?= $value['brand_slug'] ?>" id="<?= $value['brand_id'] ?>" checked>
                                                        <img src="<?= base_url($value['brand_img']) ?>" alt="brand-logo" class="h-6">
                                                    </label>

                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div id="category-filters-mobile">
                                    <?php if (isset($categories)) { ?>
                                        <div class="align-content-center d-flex justify-content-between">
                                            <h6 class="m-0"><?= label('categories', 'Categories') ?></h6>
                                            <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                        </div>
                                        <div class="category_filter mb-5 mt-2">
                                            <?php
                                            $categories_filter = json_decode(($categories), true);
                                            foreach ($categories_filter as $key => $value) {
                                            ?>
                                                <div class="form-check">
                                                    <input class="form-check-input category" type="radio" name="categoryRadio" data-value="<?= $value['id'] ?>" id="<?= $value['id'] ?>" value="" checked>
                                                    <label class="form-check-label" for="<?= $value['id'] ?>">
                                                        <?= $value['name'] ?>
                                                    </label>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php  } ?>
                                </div>

                                <div class="text-center d-flex gap-2">
                                    <button class="btn btn-primary btn-sm rounded-pill product_filter_btn"><?= !empty($this->lang->line('filter')) ? $this->lang->line('filter') : 'Filter' ?></button>
                                    <a href="#" id="reload" class="btn btn-outline-red btn-sm rounded-pill w-50"><?= label('clear', 'Clear') ?></a>
                                </div>
                            </div>
                        </div>
                        <!-- /.container -->
                    </div>

                    <!-- </div> -->

                </aside>
                <!-- /column .sidebar -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container -->
    </section>
</div>
<!-- <div class="container">
    <div class="row justify-content-center">
      
        <div class="col-lg-2 D-none">
            <div class="my-2">
                <div class="categories">
                    <h5 class="textUpper">Filter</h5>
                    <hr>
                    <h6 class="textUpper">categories</h6>
                    <hr>
                    <div><a href="" class="aTag textCapital" style="line-height:30px;">fashionware (10)</a></div>
                    <div><a href="" class="aTag textCapital"style="line-height:30px;">kitchenware (5)</a></div>
                    <div><a href="" class="aTag textCapital"style="line-height:30px;">electronics (8)</a></div>
                    <div><a href="" class="aTag textCapital"style="line-height:30px;">accessories (4)</a></div>
                    <div><a href="" class="aTag textCapital"style="line-height:30px;">shoe (5)</a></div>
                    <div><a href="" class="aTag textCapital"style="line-height:30px;">toys (2)</a></div>
                </div>
                <div class="price">
                    <hr>
                    <h6 class="textUpper">Price</h6>
                    <hr>
                    <input type="range" id="priceRange" min="1" max="1000" onchange="updatePrice(this.value)">
                    <p class="aTag">Price: <i class="bi bi-currency-rupee"></i><span id="priceValue">1</span> - <i class="bi bi-currency-rupee"></i>1000</p>
                </div>
                <div class="brand">
                    <hr>
                    <h6 class="textUpper">Brand</h6>
                    <hr>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-1" class="checkbox-custom">
                        <label for="checkbox-1" class="checkbox-label textCapital">Studio (3)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-2" class="checkbox-custom">
                        <label for="checkbox-2" class="checkbox-label textCapital">Hastech (4)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label textCapital">Quickiin (15)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label textCapital">Graphic corner (10)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label textCapital">devItems (12)</label>
                    </div>
                </div>
                <div>
                    <hr>
                    <h6 class="textUpper">Color</h6>
                    <hr>
                    <div class="">
                        <span class="dot-1 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-2 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-3 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-4 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-5 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-6 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-7 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-8 mx-1" onclick="toggleIcon(this)"></span>
                    </div>
                </div>
                <div class="Size">
                    <hr>
                    <h6 class="textUpper">Size</h6>
                    <hr>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-1" class="checkbox-custom">
                        <label for="checkbox-1" class="checkbox-label">S (3)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-2" class="checkbox-custom">
                        <label for="checkbox-2" class="checkbox-label">M (4)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label">L (15)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label"> xl (10)</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-10">
            <div>
                <div class="row justify-content-between my-2">
                    <div class="col-lg-4 col-6">
                        <span class="text-secondary">Showing 1-16 of 50</span>
                    </div>

                    <div class="col-lg-4 col-6 text-end d-lg-none">
                        <i class="bi bi-filter fs-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling" style="cursor: pointer;"></i>
                    </div>
                    <div class="col-lg-2 col-6 D-none">
                       
                        <select name="" id="" class="form-control input-custom">
                            <option value="">Sort by</option>
                            <option value="">Popularity</option>
                            <option value="">Newest</option>
                            <option value="">Oldest</option>
                            <option value="">Rating (low to High)</option>
                            <option value="">Rating (High to low)</option>

                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                    <div class="col-lg-3 col-6 position-relative my-2">
                        <div>
                            <a href="productDetails.php"><img src="<?= base_url('assets/front_end/cretzo/img/il_300x300.4245808662_a3x4.jpg.png') ?>" class="img-fluid rounded-3" alt=""></a>
                        </div>
                        <div class="text-center p-3">
                            <span>Mony</span>
                            <h6>Product Name</h6>
                            <span class="active">Rs. 2500 <del class="text-secondary">Rs. 2500</del> (60%)</span> <br>
                        </div>
                        <div class="position-absolute top-0 start-0 mx-3">
                            
                            <div class="new my-2"><i class="bi bi-star"></i> 4.5</div>
                        </div>
                        <div class="position-absolute top-0 end-0 mx-3 active">
                        <i class="bi bi-heart fs-4"></i>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->

<!-- offcanvas for mobile -->
<!-- <div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
    <div class="offcanvas-header">
        <h6 class="offcanvas-title" id="offcanvasScrollingLabel">Filter</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
    <div class="my-2">
                <div class="categories">
                    <h5 class="textUpper">Filter</h5>
                    <h6 class="textUpper">categories</h6>
                    <hr>
                    <p><a href="" class="aTag textCapital">fashionware (10)</a></p>
                    <p><a href="" class="aTag textCapital">kitchenware (5)</a></p>
                    <p><a href="" class="aTag textCapital">electronics (8)</a></p>
                    <p><a href="" class="aTag textCapital">accessories (4)</a></p>
                    <p><a href="" class="aTag textCapital">shoe (5)</a></p>
                    <p><a href="" class="aTag textCapital">toys (2)</a></p>
                </div>
                <div class="price">
                    <h6 class="textUpper">Price</h6>
                    <hr>
                    <input type="range" id="priceRange" min="1" max="1000" onchange="updatePrice(this.value)">
                    <p class="aTag">Price: <i class="bi bi-currency-rupee"></i><span id="priceValue">1</span> - <i class="bi bi-currency-rupee"></i>1000</p>
                </div>
                <div class="brand">
                    <h6 class="textUpper">Brand</h6>
                    <hr>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-1" class="checkbox-custom">
                        <label for="checkbox-1" class="checkbox-label textCapital">Studio (3)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-2" class="checkbox-custom">
                        <label for="checkbox-2" class="checkbox-label textCapital">Hastech (4)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label textCapital">Quickiin (15)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label textCapital">Graphic corner (10)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label textCapital">devItems (12)</label>
                    </div>
                </div>
                <div>
                    <h6 class="textUpper">Color</h6>
                    <hr>
                    <div class="">
                        <span class="dot-1 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-2 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-3 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-4 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-5 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-6 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-7 mx-1" onclick="toggleIcon(this)"></span>
                        <span class="dot-8 mx-1" onclick="toggleIcon(this)"></span>
                    </div>
                </div>
                <div class="Size">
                    <h6 class="textUpper">Size</h6>
                    <hr>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-1" class="checkbox-custom">
                        <label for="checkbox-1" class="checkbox-label">S (3)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-2" class="checkbox-custom">
                        <label for="checkbox-2" class="checkbox-label">M (4)</label>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label">L (15)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="checkbox-3" class="checkbox-custom">
                        <label for="checkbox-3" class="checkbox-label"> xl (10)</label>
                    </div>
                </div>
            </div>
    </div>
</div> -->

