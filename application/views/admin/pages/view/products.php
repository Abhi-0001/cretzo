<div class="content-wrapper admin-view-product-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-box-open mr-2 text-primary-theme"></i>View Product</h4>
                    <p class="text-muted mb-0 small">Product details, variants, attributes and customer ratings.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/product/manage-product') ?>">Products</a></li>
                        <li class="breadcrumb-item active">View Product</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card mb-4">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-box-open"></i></span>
                    <h5 class="mb-0">Product Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-lg-5">
                            <div class="product-main-image">
                                <a href="<?= $product_details[0]['image'] ?>" data-toggle="lightbox" data-gallery="product-gallery">
                                    <img src="<?= $product_details[0]['image'] ?>" class="w-100" />
                                </a>
                            </div>
                            <?php
                            $other_images = $product_details[0]['other_images'];
                            if (!empty($other_images)) {
                            ?>
                                <div class="product-image-thumbs">
                                    <?php foreach ($other_images as $row) { ?>
                                        <div class="product-image-thumb">
                                            <a href="<?= $row ?>" data-toggle="lightbox" data-gallery="product-gallery">
                                                <img src="<?= $row ?>">
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="product-no-images">
                                    <i class="far fa-images"></i>
                                    <p class="mb-0">No other images uploaded</p>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="col-12 col-lg-7">
                            <h3 class="product-title mb-1"><?= $product_details[0]['name'] ?></h3>
                            <span class="badge badge-light product-type-badge"><?= ucwords(str_replace('_', ' ', $product_details[0]['type'])) ?></span>
                            <p class="text-muted mt-3 mb-0"><?= $product_details[0]['short_description'] ?></p>

                            <div class="product-meta-row">
                                <span class="text-bold">Category:</span>
                                <span class="text-primary-theme"><?= ucfirst($product_details[0]['category_name']) ?></span>
                            </div>

                            <div class="product-rating-row">
                                <input type="text" class="kv-fa rating-loading" value="<?= $product_details[0]['rating'] ?>" data-size="sm" title="" readonly>
                                <?= (isset($product_rating['rating'][0]['no_of_rating']) && $product_rating['rating'][0]['no_of_rating'] > 0 && !empty($product_rating['rating'][0]['no_of_rating'])) ?  '<span class="text-muted ml-2">' . $product_rating['rating'][0]['no_of_rating'] . ' rating(s)</span>' : '' ?>
                            </div>

                            <?php
                            if (!empty($product_details[0]['type'])) {
                                if ($product_details[0]['type'] == 'simple_product') {
                            ?>
                                    <div class="product-price mt-3">
                                        <?php if ($product_variants[0]['special_price'] != null && $product_variants[0]['special_price'] > 0) { ?>
                                            <span class="price-current"><?= $currency . $product_variants[0]['special_price'] ?></span>
                                            <span class="price-strike"><?= $currency . $product_variants[0]['price'] ?></span>
                                        <?php } else { ?>
                                            <span class="price-current"><?= $currency . $product_variants[0]['price'] ?></span>
                                        <?php } ?>
                                    </div>
                            <?php
                                }
                                if ($product_details[0]['type'] == 'variable_product') {
                                    $price = "";
                            ?>
                                    <h5 class="section-subheading mt-4">Variants</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm variant-table fixed-row-height">
                                            <thead>
                                                <tr>
                                                    <th>Row ID</th>
                                                    <th>Variants</th>
                                                    <th>Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $flag = 0;
                                                foreach ($product_variants as $row) {
                                                    if ($row['special_price'] != null && $row['special_price'] > 0) {
                                                        $price = $row['special_price'];
                                                        $flag = 1;
                                                        $strike_off_price = $row['price'];
                                                    } else {
                                                        $price = $row['price'];
                                                    }
                                                ?>
                                                    <tr class='<?= ($row['status'] == 7) ? "table-danger" : (($row['status'] == 0) ? "table-warning" : ""); ?>'>
                                                        <td>
                                                            <?= $row['id'] ?>
                                                            <?php if ($row['status'] == 7) { ?>
                                                                <small class="badge badge-danger ml-1">Trashed</small>
                                                                <a class="ml-1" href="<?= base_url('admin/product/change_variant_status/' . $row['id'] . '/1/' . $product_details[0]['id']) ?>" title="Restore variant">Restore</a>
                                                            <?php } elseif ($row['status'] == 0) { ?>
                                                                <small class="badge badge-warning ml-1">Deactivated</small>
                                                                <a class="ml-1" href="<?= base_url('admin/product/change_variant_status/' . $row['id'] . '/1/' . $product_details[0]['id']) ?>" title="Activate variant">Activate</a>
                                                            <?php } else { ?>
                                                                <a class="ml-1" href="<?= base_url('admin/product/change_variant_status/' . $row['id'] . '/0/' . $product_details[0]['id']) ?>" title="Deactivate variant">Deactivate</a> |
                                                                <a href="<?= base_url('admin/product/change_variant_status/' . $row['id'] . '/7/' . $product_details[0]['id']) ?>" title="Move variant to Trash">Trash</a>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?= str_replace(',', ' | ', $row['variant_values']) ?></td>
                                                        <td>
                                                            <?php if ($flag == 1 && isset($strike_off_price) && !empty($strike_off_price)) { ?>
                                                                <span class="price-current"><?= $currency . $price ?></span>
                                                                <span class="price-strike"><?= $currency . $strike_off_price ?></span>
                                                            <?php } else { ?>
                                                                <span class="price-current"><?= $currency . $price ?></span>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php
                                                    $flag = 0;
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                            <?php
                                }
                            }
                            if (!empty($product_details[0]['attributes'])) {
                            ?>
                                <h5 class="section-subheading mt-4">Attributes</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm attribute-table fixed-row-height">
                                        <thead>
                                            <tr>
                                                <th>Row</th>
                                                <th>Attribute</th>
                                                <th>Values</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            foreach ($product_details[0]['attributes'] as $row) {
                                            ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $row['attr_name'] ?></td>
                                                    <td><?= str_replace(',', ' | ', $row['value']) ?></td>
                                                </tr>
                                            <?php
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($product_details[0]['description']) || !empty($product_rating['product_rating'])) { ?>
                <div class="card attribute-card">
                    <div class="card-header attribute-card-header p-0">
                        <?php
                        $rating_active = (empty($product_details[0]['description']) && !empty($product_rating['product_rating'])) ? 'active show' : '';
                        ?>
                        <div class="nav nav-tabs product-detail-tabs" id="product-tab" role="tablist">
                            <?php if (!empty($product_details[0]['description'])) { ?>
                                <a class="nav-item nav-link active" id="product-desc-tab" data-toggle="tab" href="#product-desc" role="tab" aria-controls="product-desc" aria-selected="true">
                                    <i class="fas fa-align-left mr-1"></i> Description
                                </a>
                            <?php } ?>
                            <?php if (!empty($product_rating['product_rating'])) { ?>
                                <a class="nav-item nav-link <?= $rating_active ?>" id="product-rating-tab" data-toggle="tab" href="#product-rating" role="tab" aria-controls="product-rating" aria-selected="false">
                                    <i class="fas fa-star mr-1"></i> Ratings
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="nav-tabContent">
                            <?php if (!empty($product_details[0]['description'])) { ?>
                                <div class="tab-pane active" id="product-desc" role="tabpanel" aria-labelledby="product-desc-tab"><?= $product_details[0]['description'] ?></div>
                            <?php } ?>
                            <?php
                            if (!empty($product_rating['product_rating'])) {
                            ?>
                                <input type="hidden" name="product_id" id="product_id" value="<?= (isset($product_details[0]['id']) && !empty($product_details[0]['id'])) ? $product_details[0]['id'] : 'null' ?>" />
                                <div class="tab-pane <?= $rating_active ?>" id="product-rating" role="tabpanel" aria-labelledby="product-rating-tab">
                                    <table class='table-striped fixed-row-height' id='product-rating-table' data-toggle="table" data-url="<?= base_url('admin/product/get_rating_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="product_rating_query_params">
                                        <thead>
                                            <tr>
                                                <th data-field="id" data-sortable="true">ID</th>
                                                <th data-field="username" data-width='500' data-sortable="false" class="col-md-6">Username</th>
                                                <th data-field="rating" data-sortable="false">Rating</th>
                                                <th data-field="comment" data-sortable="false">Comment</th>
                                                <th data-field="images" data-sortable="true">Images</th>
                                                <th data-field="data_added" data-sortable="false">Date added</th>
                                                <th data-field="operate" data-sortable="false">Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>
</div>

<style>
    .admin-view-product-page .text-primary-theme { color: var(--color-orange); }

    .admin-view-product-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-view-product-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-view-product-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-view-product-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-view-product-page .product-main-image {
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        overflow: hidden;
        background: #fafafa;
    }
    .admin-view-product-page .product-main-image img { width: 100%; display: block; }

    .admin-view-product-page .product-image-thumbs {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
    }
    .admin-view-product-page .product-image-thumb {
        width: 72px;
        height: 72px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-view-product-page .product-image-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .admin-view-product-page .product-no-images {
        margin-top: 12px;
        border: 2px dashed rgba(0,0,0,0.12);
        border-radius: 10px;
        background: #fafafa;
        text-align: center;
        padding: 24px 10px;
        color: var(--color-grey);
    }
    .admin-view-product-page .product-no-images i { font-size: 22px; display: block; margin-bottom: 6px; }

    .admin-view-product-page .product-title { color: #2b2f33; font-weight: 700; }
    .admin-view-product-page .product-type-badge {
        background: var(--color-orange-light);
        color: var(--color-orange-dark);
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .admin-view-product-page .product-meta-row { margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(0,0,0,0.06); }
    .admin-view-product-page .product-rating-row { margin-top: 10px; display: flex; align-items: center; }

    .admin-view-product-page .section-subheading {
        font-weight: 600;
        color: #2b2f33;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        padding-bottom: 8px;
    }

    .admin-view-product-page .price-current { font-size: 22px; font-weight: 700; color: var(--color-orange-dark); }
    .admin-view-product-page .price-strike { font-size: 14px; color: var(--color-grey); text-decoration: line-through; margin-left: 8px; }
    .admin-view-product-page .variant-table .price-current,
    .admin-view-product-page .attribute-table .price-current { font-size: 14px; }
    .admin-view-product-page .variant-table .price-strike,
    .admin-view-product-page .attribute-table .price-strike { font-size: 12px; margin-left: 4px; }

    .admin-view-product-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-view-product-page table.table tbody td { vertical-align: middle; font-size: 13px; border-top: 1px solid rgba(0,0,0,0.05); }

    .admin-view-product-page .product-detail-tabs { border-bottom: none; padding: 0 8px; }
    .admin-view-product-page .product-detail-tabs .nav-link {
        border: none;
        color: var(--color-grey);
        font-weight: 600;
        padding: 14px 18px;
        border-bottom: 3px solid transparent;
    }
    .admin-view-product-page .product-detail-tabs .nav-link.active {
        color: var(--color-orange-dark);
        border-bottom-color: var(--color-orange);
        background: transparent;
    }

    .admin-view-product-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-view-product-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-view-product-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }
</style>
