<!-- breadcrumb -->
<div class="content-wrapper">
    <section class="wrapper bg-soft-grape">
        <div class="container py-3 py-md-5">
            <nav class="d-inline-block" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= base_url() ?>" class="text-decoration-none"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a></li>

                    <li class="breadcrumb-item active" aria-current="page"><?= !empty($this->lang->line('products')) ? $this->lang->line('products') : 'Products' ?></li>

                    <?php if (isset($right_breadcrumb) && !empty($right_breadcrumb)) {
                        foreach ($right_breadcrumb as $row) {
                    ?>
                            <li class="breadcrumb-item"><?= $row ?></li>
                    <?php }
                    } ?>

                    <?php
                    if (isset($section_slug) && !empty($section_slug)) { ?>
                        <li class="breadcrumb-item active text-muted" aria-current="page"><?= $section_slug[0]['title'] ?></li>
                    <?php } ?>

                    <?php
                    if (isset($seller) && !empty($seller)) { ?>
                        <li class="breadcrumb-item active text-muted" aria-current="page"><?= $seller[0]['store_name'] ?></li>
                    <?php } ?>

                    <?php if (isset($single_category) && !empty($single_category)) { ?>
                        <li class="breadcrumb-item active text-muted" aria-current="page"><?= $single_category['name'] ?></li>
                    <?php } ?>

                </ol>
            </nav>
            <!-- /nav -->
        </div>
        <!-- /.container -->
    </section>
</div>
<!-- end breadcrumb -->

<?php
// echo "<pre>";
// print_r($brands['data']);

// foreach ($brands['data'] as $key => $value) {
//     print_r($value['name']);
// }
// die;  
?>


<input type="hidden" id="product-filters" value='<?= (!empty($filters)) ? escape_array($filters) : ""  ?>' data-key="<?= $filters_key ?>" />

<div class="content-wrapper">
    <section class="wrapper listing-page bg-light">
        <div class="container pb-14 pb-md-16 pt-12">
            <div class="d-flex row">
                <div class="col-md-12 order-md-2 <?= (isset($products['filters']) && !empty($products['filters'])) ? "col-lg-9" : "col-lg-12" ?>">

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
                                        <div class="category-image category-image-container">
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
                                <div class="row p-4 ">
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
                                            <h4 class="list-product-title title" title="<?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])) ?>"><a class="text-decoration-none text-dark" href="<?= base_url('products/details/' . $row['slug']) ?>"><?= $row['name'] ?></a></h4>
                                            <div class="col-md-12 mb-3 product-rating-small ps-0 " dir="ltr">
                                                <input id="input" name="rating" class="rating rating-loading d-none" data-size="xs" value="<?= $row['rating'] ?>" data-show-clear="false" data-show-caption="false" readonly>
                                            </div>

                                            <div class="mt-n2">
                                                <p class="text-muted list-product-desc"><?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($row['short_description']))), 80); ?></p>
                                            </div>
                                            <p class="price text-dark mt-n2">
                                                <span class="amount"><?php $price = get_price_range_of_product($row['id']);
                                                                        echo $price['range'];
                                                                        ?></span>
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
                                        <div class="project item col-md-6 col-xl-4">
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
                                                <h4 class="post-title title">
                                                    <a href="<?= base_url('products/details/' . $row['slug']) ?>" class="link-dark text-decoration-none"><?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($row['name']))), 26); ?></a>
                                                </h4>

                                                <p class="price text-muted">
                                                    <?php $price = get_price_range_of_product($row['id']);
                                                    echo $price['range']; ?></p>
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
                        <?php if (isset($products['filters']) && !empty($products['filters'])) { ?>
                            <div class=" order-md-1 filter-section sidebar-filter-sm pt-2 pb-2 filter-sidebar-view">
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

                                <?php if (isset($brands)) { ?>
                                    <div class="align-content-center d-flex justify-content-between">
                                        <h6 class="m-0"><?= label('brands', 'Brands') ?></h6>
                                        <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                    </div>
                                    <div class="brand_filter d-flex flex-wrap gap-4 mb-5 mt-2 p-1">
                                        <?php
                                        foreach ($brands as $key => $value) {
                                            $brand_data = fetch_details('brands', ['name' => $value]);
                                        ?>
                                            <div class="brand_div">
                                                <label class="" for="<?= $brand_data[0]['id'] ?>">
                                                    <input class="brand" type="radio" name="brandRadio" data-value="<?= $brand_data[0]['slug'] ?>" id="<?= $brand_data[0]['id'] ?>" checked>
                                                    <img src="<?= base_url($brand_data[0]['image']) ?>" alt="brand-logo" class="h-6">
                                                </label>

                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <?php if (isset($categories)) { ?>
                                    <div class="align-content-center d-flex justify-content-between">
                                        <h6 class="m-0"><?= label('categories', 'Categories') ?></h6>
                                        <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                    </div>
                                    <div class="category_filter mb-5 mt-2">
                                        <?php
                                        foreach ($categories as $key => $value) { ?>
                                            <div class="form-check">
                                                <input class="form-check-input category" type="radio" name="categoryRadio" data-value="<?= $value['id'] ?>" id="<?= $value['id'] ?>" value="" checked>
                                                <label class="form-check-label" for="<?= $value['id'] ?>">
                                                    <?= $value['name'] ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php  } ?>

                                <div class="text-center d-flex gap-2">
                                    <button class="btn btn-primary btn-sm rounded-pill w-50 product_filter_btn"><?= label('filter', 'Filter') ?></button>
                                    <a href="#" id="reload" class="btn btn-outline-red btn-sm rounded-pill w-50"><?= label('clear', 'Clear') ?></a>
                                </div>
                            </div>
                        <?php } ?>
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

                                <?php if (isset($brands)) { ?>
                                    <div class="align-content-center d-flex justify-content-between">
                                        <h6 class="m-0"><?= label('brands', 'Brands') ?></h6>
                                        <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                    </div>
                                    <div class="brand_filter d-flex flex-wrap gap-4 mb-5 mt-2 p-1">
                                        <?php
                                        foreach ($brands as $key => $value) {
                                            $brand_data = fetch_details('brands', ['name' => $value]);
                                        ?>
                                            <div class="brand_div">
                                                <label class="" for="<?= $brand_data[0]['id'] ?>">
                                                    <input class="brand" type="radio" name="brandRadio" data-value="<?= $brand_data[0]['slug'] ?>" id="<?= $brand_data[0]['id'] ?>" checked>
                                                    <img src="<?= base_url($brand_data[0]['image']) ?>" alt="brand-logo" class="h-6">
                                                </label>

                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <?php if (isset($categories)) { ?>
                                    <div class="align-content-center d-flex justify-content-between">
                                        <h6 class="m-0"><?= label('categories', 'Categories') ?></h6>
                                        <a href="#" class="text-decoration-none hover product_filter_btn"><?= label('filter', 'Filter') ?></a>
                                    </div>
                                    <div class="category_filter mb-5 mt-2">
                                        <?php
                                        foreach ($categories as $key => $value) { ?>
                                            <div class="form-check">
                                                <input class="form-check-input category" type="radio" name="categoryRadio" data-value="<?= $value['id'] ?>" id="<?= $value['id'] ?>" value="" checked>
                                                <label class="form-check-label" for="<?= $value['id'] ?>">
                                                    <?= $value['name'] ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php  } ?>

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