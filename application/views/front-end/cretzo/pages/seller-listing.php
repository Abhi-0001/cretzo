<section class="container listing-page mb-15">
    <div class="product-listing card-solid py-4">
        <div class="row mx-0">
            <!-- Dektop Sidebar -->
            <!-- remved filters -->
            <div class="col-md-12 order-md-2">
                <div class="container-fluid filter-section pt-3 pb-3">
                    <div class="col-12 pl-0">
                        <div class="dropdown">
                            <div class="filter-bars">
                                <div class="menu js-menu">
                                    <span class="menu__line"></span>
                                    <span class="menu__line"></span>
                                    <span class="menu__line"></span>

                                </div>
                            </div>
                            <?php if (isset($sellers) && !empty($sellers)) { ?>
                                <div class="ele-wrapper d-flex align-items-center flex-wrap">
                                    <div class="form-group col-md-5 pl-0">
                                        <label for="product_sort_by"></label>
                                        <select id="product_sort_by" class="form-control">
                                            <option><?= !empty($this->lang->line('relevance')) ? $this->lang->line('relevance') : 'Relevance' ?></option>
                                            <option value="top-rated" <?= ($this->input->get('sort') == "top-rated") ? 'selected' : '' ?>><?= !empty($this->lang->line('top_rated')) ? $this->lang->line('top_rated') : 'Top Rated' ?></option>
                                            <option value="date-desc" <?= ($this->input->get('sort') == "date-desc") ? 'selected' : '' ?>><?= !empty($this->lang->line('newest_first')) ? $this->lang->line('newest_first') : 'Newest First' ?></option>
                                            <option value="date-asc" <?= ($this->input->get('sort') == "date-asc") ? 'selected' : '' ?>><?= !empty($this->lang->line('oldest_first')) ? $this->lang->line('oldest_first') : 'Oldest First' ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="seller_search"></label>
                                        <input type="search" name="seller_search" class="form-control" id="seller_search" value="<?= (isset($seller_search) && !empty($seller_search)) ? $seller_search : "" ?>" placeholder="Search Seller">
                                    </div>
                                    <div class="dropdown float-md-right mb-4 col-md-3 form-select-wrapper">
                                        <div class="d-flex align-items-baseline mt-5">
                                            <label class="mr-2 dropdown-label"> <?= !empty($this->lang->line('show')) ? $this->lang->line('show') : 'Show' ?>:</label>
                                            <a class="dropdown-border form-select col-4 mr-2" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?= ($this->input->get('per-page', true) ? $this->input->get('per-page', true) : '12') ?> <span class="caret"></span></a>
                                            <a href="#" id="product_grid_view_btn" class="grid-view text-dark text-decoration-none"><i class="fs-20 uil uil-th"></i></a>
                                            <a href="#" id="product_list_view_btn" class="grid-view ps-3 text-dark text-decoration-none"><i class="fs-20 uil uil-list-ul"></i></a>
                                            <div class="dropdown-menu custom-dropdown-menu" aria-labelledby="navbarDropdown" id="per_page_sellers">
                                                <a class="dropdown-item" href="#" data-value=12>12</a>
                                                <a class="dropdown-item" href="#" data-value=16>16</a>
                                                <a class="dropdown-item" href="#" data-value=20>20</a>
                                                <a class="dropdown-item" href="#" data-value=24>24</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <?php if (isset($sellers) && !empty($sellers)) { ?>

                        <?php if (isset($_GET['type']) && $_GET['type'] == "list") { ?>
                            <div class="col-md-12 col-sm-6">
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h1 class="h4"><?= !empty($this->lang->line('sellers')) ? $this->lang->line('sellers') : 'Sellers' ?></h4>
                                    </div>
                                    <?php foreach ($sellers as $row) { ?>
                                        <div class="card mt-5" title="<?= $row['seller_name'] ?>">
                                            <div class="align-items-center d-flex">
                                                <div class="col-md-3">
                                                    <div class="">
                                                        <div class="product-image">
                                                            <div class="product-image-container">
                                                                <a href="<?= seller_profile_url($row['slug'] ?? '', $row['seller_id'] ?? '') ?>">
                                                                    <img class="pic-1 lazy blog-img" src="<?= base_url('assets/front_end/cretzo/img/product-placeholder.jpg') ?>" data-src="<?= $row['seller_profile'] ?>">
                                                                    <?php $row['seller_profile']; ?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="product-content">
                                                        <h3 class="list-product-title title" title="<?= $row['seller_name'] ?>"><a href="<?= seller_profile_url($row['slug'] ?? '', $row['seller_id'] ?? '') ?>"><?= $row['seller_name'] ?></a></h3>
                                                        <div class="rating">
                                                            <input id="input" name="rating" class="rating rating-loading d-none" data-size="xs" value="<?= number_format($row['seller_rating'], 1) ?>" data-show-clear="false" data-show-caption="false" readonly>
                                                        </div>
                                                        <p class="text-muted list-product-desc m-0"><?= $row['store_description'] ?></p>
                                                        <p class="price mb-2 list-view-price">
                                                            <?= $row['store_name'] ?>
                                                        </p>
                                                        <a href="<?= base_url('products?seller=' . $row['slug']) ?>" class="view-products  btn btn-sm btn-outline-primary rounded-pill mt-2"><?= !empty($this->lang->line('view_products')) ? $this->lang->line('view_products') : 'View Products' ?></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                        <?php } else { ?>
                            <div class="row w-100">
                                <div class="col-12">
                                    <h1 class="h4"><?= !empty($this->lang->line('sellers')) ? $this->lang->line('sellers') : 'Sellers' ?></h4>
                                </div>
                                <?php foreach ($sellers as $row) { ?>
                                    <div class="col-md-4 col-sm-6 mt-5" title="<?= $row['seller_name'] ?>">
                                        <div class=" card text-center">
                                            <!-- <div class="product-image"> -->
                                                <div class="seller-image-container">
                                                    <a href="<?= seller_profile_url($row['slug'] ?? '', $row['seller_id'] ?? '') ?>">
                                                        <img class="pic-1 lazy" src="<?= base_url('assets/front_end/cretzo/img/product-placeholder.jpg') ?>" data-src="<?= $row['seller_profile'] ?>">
                                                    </a>
                                                </div>
                                            <!-- </div> -->
                                            <div class="rating">
                                                <input id="input" name="rating" class="rating rating-loading d-none" data-size="xs" value="<?= number_format($row['seller_rating'], 1) ?>" data-show-clear="false" data-show-caption="false" readonly>
                                            </div>
                                            <div class="product-content mb-5">
                                                <h4 class="title m-0" title="<?= $row['seller_name'] ?>"><a class="text-decoration-none text-dark" href="<?= seller_profile_url($row['slug'] ?? '', $row['seller_id'] ?? '') ?>"><?= $row['seller_name'] ?></a></h4>
                                                <p class="price">
                                                    <?= $row['store_name'] ?>
                                                </p>
                                                <a href="<?= base_url('products?seller=' . $row['slug']) ?>" class="view-products  btn btn-sm btn-outline-primary rounded-pill mt-2"><?= !empty($this->lang->line('view_products')) ? $this->lang->line('view_products') : 'View Products' ?></a>

                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    <?php } ?>

                    <?php if (!isset($sellers) || empty($sellers)) { ?>
                        <div class="col-12 text-center">
                            <h1 class="h2"><?= !empty($this->lang->line('no_sellers_found')) ? $this->lang->line('no_sellers_found') : 'No Sellers Found.' ?></h1>
                            <a href="<?= base_url('products') ?>" class="btn rounded-pill btn-warning"><?= !empty($this->lang->line('go_to_shop')) ? $this->lang->line('go_to_shop') : 'Go to Shop' ?></a>
                        </div>
                    <?php } ?>
                    <?php if (!empty($links)) { ?>
                        <nav class="cz-pager-nav" aria-label="<?= storefront_pagination_label('sellers') ?>"><?= $links ?></nav>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>