<div class="content-wrapper admin-create-product-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0">
                        <i class="fas fa-box-open mr-2 text-primary-theme"></i><?= isset($product_details[0]['id']) ? 'Update' : 'Add' ?> Product
                    </h4>
                    <p class="text-muted mb-0 small">
                        <?= isset($product_details[0]['id']) ? 'Edit this product\'s details, pricing and stock.' : 'Create a new product and assign it to a seller.' ?>
                    </p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/product') ?>">Products</a></li>
                        <li class="breadcrumb-item active"><?= isset($product_details[0]['id']) ? 'Update' : 'Add' ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <!-- form start -->
                        <form class="form-horizontal" action="<?= base_url('admin/product/add_product'); ?>" method="POST" enctype="multipart/form-data" id="save-product">
                            <?php if (isset($product_details[0]['id'])) {
                            ?>
                                <input type="hidden" name="edit_product_id" value="<?= html_escape((isset($product_details[0]['id'])) ? $product_details[0]['id'] : "") ?>">
                                <?php // The seller_id was also emitted here as a hidden input while the Seller
                                      // dropdown below carries the same name, so the form submitted the field
                                      // twice and relied on document order to decide which value won. ?>
                                <input type="hidden" id="subcategory_id_js" value="<?= html_escape((isset($product_details[0]['subcategory_id'])) ? $product_details[0]['subcategory_id'] : "") ?>">
                            <?php } ?>
                            <?php
                            // Single source of truth for the chosen category, in both create and edit mode.
                            // Previously this input existed only when editing, so on the create screen the
                            // category lived solely inside the tree widget.
                            ?>
                            <input type="hidden" name="category_id" id="product_category_id" value="<?= html_escape(isset($product_details[0]['category_id']) ? (int) $product_details[0]['category_id'] : '') ?>">
                            <?php
                            // Shared layout for the seller and admin product forms. Every name / id / class
                            // that assets/admin/custom/custom.js binds to is kept exactly as it was - only
                            // the grouping and the wording changed.
                            $pd = $product_details[0] ?? [];
                            $is_edit = !empty($pd['id']);
                            $is_digital = (($pd['type'] ?? '') === 'digital_product');
                            $dn = $is_digital ? 'd-none' : '';
                            $req = '<span class="text-danger">*</span>';
                            // used by the stock rows inside the edit-mode "Price & stock" pane
                            $HideStatus = ($is_edit && ((($pd['stock_type'] ?? null) == NULL) || ($pd['type'] ?? '') == 'digital_product')) ? 'collapse' : '';
                            ?>
                            <div class="card-body czpf <?= $is_edit ? 'czpf-edit' : 'czpf-create' ?>">

                                <nav class="czpf-steps" id="czpf-steps" aria-label="Form sections">
                                    <a href="#czpf-basics" data-czpf-step="basics"><b>1</b><span>Basics</span></a>
                                    <a href="#czpf-photos" data-czpf-step="photos"><b>2</b><span>Photos</span></a>
                                    <a href="#czpf-price" data-czpf-step="price"><b>3</b><span>Price &amp; stock</span></a>
                                    <a href="#czpf-ship" data-czpf-step="ship" class="pickup_locations <?= $dn ?>"><b>4</b><span>Pickup</span></a>
                                    <a href="#czpf-desc" data-czpf-step="desc"><b>5</b><span>Description</span></a>
                                    <a href="#czpf-more" data-czpf-step="more" class="czpf-step-optional"><b>+</b><span>More options</span></a>
                                </nav>

                                <?php if (!$is_edit) { $import_panel = 'admin'; $this->load->view('partials/product-url-import', ['import_panel' => $import_panel]); } ?>

                                <?php /* ============ 1. BASICS ============ */ ?>
                                <section class="czpf-card" id="czpf-basics">
                                    <header class="czpf-head"><span class="czpf-num">1</span><div><h5>Product basics</h5><p>What are you selling?</p></div></header>
                                    <div class="czpf-body">
                                        <div class="form-group">
                                            <label for="seller_id" class="col-form-label">Seller <?= $req ?></label>
                                            <select class='form-control' name='seller_id' id="seller_id">
                                                <option value="">Select Seller </option>
                                                <?php foreach ($sellers as $seller) { ?>
                                                    <option value="<?= $seller['seller_id'] ?>" <?= (isset($pd['seller_id']) && $pd['seller_id'] == $seller['seller_id']) ? 'selected' : "" ?>><?= html_escape($seller['seller_name'] . ' - ' . $seller['store_name'] . ' (store)') ?></option>
                                                <?php } ?>
                                            </select>
                                            <small class="form-text text-muted">The product is listed under this seller's store. Categories and pickup locations depend on the seller you pick.</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="pro_input_text" class="col-form-label">Product name <?= $req ?></label>
                                            <input type="text" class="form-control" id="pro_input_text" placeholder="e.g. Handmade brass diya, 6 inch" name="pro_input_name" value="<?= html_escape(isset($pd['name']) ? unslash(str_replace('\r\n', '&#13;&#10;', $pd['name'])) : "") ?>">
                                            <small class="form-text text-muted">Use a clear name that buyers would search for.</small>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group <?= $is_edit ? 'col-12' : 'col-lg-7' ?>">
                                                <label class="col-form-label">Category <?= $req ?></label>
                                                <div class="category-combo">
                                                    <div class="category-search-box">
                                                        <i class="fas fa-search category-search-icon"></i>
                                                        <input type="text" class="form-control category-search-input"
                                                            id="category_search_input" placeholder="Search category..." autocomplete="off">
                                                        <button type="button" class="category-search-clear" id="category_search_clear" aria-label="Clear">&times;</button>
                                                    </div>
                                                    <div id="category_dropdown"></div>
                                                </div>
                                                <small class="text-muted d-block mt-1" id="category_hint">Select a seller first to see their categories</small>

                                                <input type="hidden" id="category_tree_data" value='<?= htmlspecialchars(json_encode($categories ?? []), ENT_QUOTES, "UTF-8") ?>'>
                                            </div>
                                            <?php if (!$is_edit) { ?>
                                                <div class="form-group col-lg-5">
                                                    <label for="product_type_menu" class="col-form-label">What kind of product?</label>
                                                    <select class="form-control" name="product_type_menu" id="product_type_menu">
                                                        <option value="physical_product">Physical &mdash; shipped to the buyer</option>
                                                        <option value="digital_product">Digital &mdash; a download or link</option>
                                                    </select>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label for="short_description" class="col-form-label">Short description <?= $req ?></label>
                                            <textarea class="form-control" id="short_description" rows="3" placeholder="One or two lines buyers see under the product name" name="short_description"><?= html_escape(isset($pd['short_description']) ? unslash(str_replace('\r\n', '&#13;&#10;', $pd['short_description'])) : ""); ?></textarea>
                                        </div>
                                    </div>
                                </section>

                                <?php /* ============ 2. PHOTOS ============ */ ?>
                                <section class="czpf-card" id="czpf-photos">
                                    <header class="czpf-head"><span class="czpf-num">2</span><div><h5>Photos</h5><p>Clear, well-lit photos help products sell. The first one is the main photo.</p></div></header>
                                    <div class="czpf-body">
                                        <div class="form-group">
                                            <label>Main photo <?= $req ?> <small class="text-muted font-weight-normal">(recommended 180 &times; 180 px or larger)</small></label>
                                            <div class="px-0">
                                                <button type="button" class="btn btn-primary btn-sm direct-upload-btn"><i class='fa fa-upload'></i> Choose Image</button>
                                                <input type="file" class="d-none direct-upload-input" accept="image/*" data-input="pro_input_image" data-multiple="0" data-media-type="image">
                                            </div>
                                            <?php if ($is_edit) { ?>
                                                <small class="text-muted d-block mt-2">This is the current main photo. Choose a new one only if you want to replace it.</small>
                                                <div class="container-fluid row image-upload-section">
                                                    <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image ">
                                                        <div class="image-upload-div"><img class="img-fluid mb-2" src="<?= BASE_URL() . $pd['image'] ?>" alt="Image Not Found"></div>
                                                        <input type="hidden" name="pro_input_image" value='<?= html_escape($pd['image']) ?>'>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="container-fluid row image-upload-section">
                                                    <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <div class="form-group">
                                            <label>More photos <small class="text-muted font-weight-normal">(optional &mdash; show other angles)</small></label>
                                            <div class="px-0">
                                                <button type="button" class="btn btn-primary btn-sm direct-upload-btn"><i class='fa fa-upload'></i> Choose Images</button>
                                                <input type="file" class="d-none direct-upload-input" accept="image/*" multiple data-input="other_images[]" data-multiple="1" data-media-type="image">
                                            </div>
                                            <div class="container-fluid row image-upload-section">
                                                <?php
                                                if ($is_edit) {
                                                    $other_images = json_decode($pd['other_images']);
                                                    if (!empty($other_images)) {
                                                        foreach ($other_images as $row) {
                                                ?>
                                                            <div class="col-md-3 col-sm-12 shadow bg-white rounded m-3 p-3 text-center grow">
                                                                <div class='image-upload-div'><img src="<?= BASE_URL() . $row ?>" alt="Image Not Found"></div>
                                                                <a href="javascript:void(0)" class="delete-img media-remove-btn" title="Remove image" aria-label="Remove image" data-id="<?= $pd['id'] ?>" data-field="other_images" data-img="<?= $row ?>" data-table="products" data-path="<?= $row ?>" data-isjson="true">&times;</a>
                                                                <input type="hidden" name="other_images[]" value='<?= html_escape($row) ?>'>
                                                            </div>
                                                <?php
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <?php $has_video = !empty($pd['video_type']); ?>
                                        <div class="czpf-fold <?= $has_video ? 'is-open' : '' ?>">
                                            <button type="button" class="czpf-fold-toggle" aria-expanded="<?= $has_video ? 'true' : 'false' ?>"><i class="fas fa-chevron-right"></i> Add a product video <small>(optional)</small></button>
                                            <div class="czpf-fold-body">
                                                <div class="form-group mb-0">
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label for="video_type" class="col-form-label">Video type</label>
                                                            <select class='form-control' name='video_type' id='video_type'>
                                                                <option value='' <?= (isset($pd['video_type']) && ($pd['video_type'] == '' || $pd['video_type'] == NULL)) ? 'selected' : ''; ?>>None</option>
                                                                <option value='self_hosted' <?= (isset($pd['video_type']) && $pd['video_type'] == 'self_hosted') ? 'selected' : ''; ?>>Upload a video file</option>
                                                                <option value='youtube' <?= (isset($pd['video_type']) && $pd['video_type'] == 'youtube') ? 'selected' : ''; ?>>YouTube link</option>
                                                                <option value='vimeo' <?= (isset($pd['video_type']) && $pd['video_type'] == 'vimeo') ? 'selected' : ''; ?>>Vimeo link</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-6 <?= (isset($pd['video_type']) && ($pd['video_type'] == 'youtube' || $pd['video_type'] == 'vimeo')) ? '' : 'd-none'; ?>" id="video_link_container">
                                                            <label for="video" class="col-form-label">Video link <?= $req ?></label>
                                                            <input type="text" class='form-control' name='video' id='video' value="<?= html_escape((isset($pd['video_type']) && ($pd['video_type'] == 'youtube' || $pd['video_type'] == 'vimeo')) ? $pd['video'] : ''); ?>" placeholder="Paste the YouTube / Vimeo link here">
                                                        </div>
                                                        <div class="form-group col-md-6 <?= (isset($pd['video_type']) && ($pd['video_type'] == 'self_hosted')) ? '' : 'd-none'; ?>" id="video_media_container">
                                                            <label class="col-form-label">Video file <?= $req ?></label>
                                                            <div class='px-0'>
                                                                <button type="button" class="btn btn-primary btn-sm direct-upload-btn"><i class='fa fa-upload'></i> Choose Video</button>
                                                                <input type="file" class="d-none direct-upload-input" accept="video/*" data-input="pro_input_video" data-multiple="0" data-media-type="video">
                                                            </div>
                                                            <?php if ($is_edit && ($pd['video_type'] ?? '') == 'self_hosted') { ?>
                                                                <small class="text-muted d-block mt-2">Choose a new file only if you want to replace the current video.</small>
                                                                <div class="container-fluid row image-upload-section ">
                                                                    <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image">
                                                                        <div class='image-upload-div'><img class="img-fluid mb-2" src="<?= base_url('assets/admin/images/video-file.png') ?>" alt="Product Video" title="Product Video"></div>
                                                                        <input type="hidden" name="pro_input_video" value='<?= html_escape($pd['video']) ?>'>
                                                                    </div>
                                                                </div>
                                                            <?php } else { ?>
                                                                <div class="container-fluid row image-upload-section">
                                                                    <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <?php /* ============ 3. PRICE & STOCK ============ */ ?>
                                <section class="czpf-card" id="czpf-price">
                                    <header class="czpf-head"><span class="czpf-num">3</span><div><h5>Price &amp; stock</h5><p>Set the price, how many you have, and the parcel's weight and size.</p></div></header>
                                    <div class="czpf-body">

                                        <div class="form-row czpf-tax-row">
                                            <div class="form-group col-md-4">
                                                <label for="pro_input_tax" class="col-form-label">GST / tax</label>
                                                <select class="form-control" name="pro_input_tax" id="pro_input_tax">
                                                    <?php if (empty($taxes)) { ?>
                                                        <option value="0" selected> No Taxes Are Added </option>
                                                    <?php } ?>
                                                    <?php foreach ($taxes as $row) { ?>
                                                        <option value="<?= $row['id'] ?>" <?= (isset($pd['tax']) && $pd['tax'] == $row['id']) ? 'selected' : '' ?>><?= html_escape($row['title']) ?>(<?= $row['percentage'] ?>%)</option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4 hsn_code <?= $dn ?>">
                                                <label for="hsn_code" class="col-form-label">HSN code <small class="text-muted font-weight-normal">(if you have one)</small></label>
                                                <input type="text" class="form-control" id="hsn_code" name="hsn_code" value="<?= html_escape($pd['hsn_code'] ?? '') ?>" placeholder="HSN code">
                                            </div>
                                            <div class="form-group col-md-4 czpf-switch-cell">
                                                <label for="is_prices_inclusive_tax" class="col-form-label">Prices already include tax?</label>
                                                <div><input type="checkbox" name="is_prices_inclusive_tax" id="is_prices_inclusive_tax" <?= (isset($pd['is_prices_inclusive_tax']) && $pd['is_prices_inclusive_tax'] == '1') ? 'checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No"></div>
                                            </div>
                                        </div>

                                        <?php if ($is_edit) { ?>
                                            <div class="additional-info existing-additional-settings">
                                                <nav class="w-100">
                                                    <div class="nav nav-tabs" id="product-tab" role="tablist">
                                                        <a class="nav-item nav-link active" id="tab-for-general-price" data-toggle="tab" href="#general-settings" role="tab" aria-controls="general-price" aria-selected="true">Price &amp; stock</a>
                                                        <a class="nav-item nav-link edit-product-attributes" id="tab-for-attributes" data-toggle="tab" href="#product-attributes" role="tab" aria-controls="product-attributes" aria-selected="false">Options</a>
                                                        <a class="nav-item nav-link <?= ($pd['type'] == 'simple_product') ? 'disabled d-none' : 'edit-variants'; ?>" id=" tab-for-variations" data-toggle="tab" href="#product-variants" role="tab" aria-controls="product-variants" aria-selected="false">Variations</a>
                                                    </div>
                                                </nav>
                                                <div class="tab-content p-3" id="nav-tabContent">
                                                    <div class="tab-pane fade active show" id="general-settings" role="tabpanel" aria-labelledby="general-settings-tab">
                                                        <div class="form-group">
                                                            <label for="product-type" class="col-md-12 col-form-label">Does this product have options like size or colour?</label>
                                                            <div class="col-md-12">
                                                                <?php @$variant_stock_level = !empty($product_details[0]['stock_type']) && $product_details[0]['stock_type'] == '1' ? 'product_level' : 'variant_level' ?>
                                                                <input type="hidden" name="product_type" value="<?= html_escape(isset($product_details[0]['type']) ? $product_details[0]['type'] : '') ?>">
                                                                <input type="hidden" name="simple_product_stock_status" <?= isset($product_details[0]['stock_type']) && !empty($product_details[0]['stock_type']) && $product_details[0]['type'] == 'simple_product' ? 'value="' . $product_details[0]['stock_type'] . '"'  : '' ?>>
                                                                <input type="hidden" name="variant_stock_level_type" <?= isset($product_details[0]['stock_type']) && !empty($product_details[0]['stock_type']) && $product_details[0]['type'] == 'variable_product' ? 'value="' . $variant_stock_level . '"'  : '' ?>>
                                                                <input type="hidden" name="variant_stock_status" <?= isset($product_details[0]['stock_type']) && !empty($product_details[0]['stock_type']) && $product_details[0]['type'] == 'variable_product' ? 'value="0"'  : '' ?>>
                                                                <select name="type" id="product-type" class="form-control" data-placeholder=" Type to search and select type" <?= isset($product_details[0]['id']) ? 'disabled' : '' ?>>
                                                                    <option value=" ">Choose one...</option>
                                                                    <option value="simple_product" <?= ($product_details[0]['type'] == "simple_product") ? 'selected' : '' ?>>Simple &mdash; one price, no options</option>
                                                                    <option value="variable_product" <?= ($product_details[0]['type'] == "variable_product") ? 'selected' : '' ?>>With options &mdash; size, colour, etc.</option>
                                                                    <option value="digital_product" <?= ($product_details[0]['type'] == "digital_product") ? 'selected' : '' ?>>Digital download</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div id='product-general-settings'>
                                                            <?php
                                                            if ($product_details[0]['type'] == "simple_product" || $product_details[0]['type'] == "digital_product") {
                                                                // Price, weight and dimensions of a simple / digital product live on its single
                                                                // product_variants row. That row can be missing (older imported products) or
                                                                // soft-removed, which used to throw "Undefined array key 0" warnings all over
                                                                // this block - fall back to blank fields instead.
                                                                $simple_variant = isset($product_variants[0]) && is_array($product_variants[0]) ? $product_variants[0] : [];
                                                            ?>
                                                                <div id="general_price_section">
                                                                    <div class="form-group">
                                                                        <label for="type" class="col-md-2">Price (MRP):</label>
                                                                        <div class="col-md-12">
                                                                            <input type="number" name="simple_price" class="form-control stock-simple-mustfill-field price" value="<?= html_escape($simple_variant['price'] ?? '') ?>" min='0' step="0.01">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="type" class="col-md-2">Special price <small class="text-muted font-weight-normal">(optional)</small>:</label>
                                                                        <div class="col-md-12">
                                                                            <input type="number" name="simple_special_price" class="form-control  discounted_price" value="<?= html_escape(!empty($simple_variant['special_price']) ? $simple_variant['special_price'] : '') ?>" min='0' step="0.01">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group row mt-3 <?= $product_details[0]['type'] == "digital_product" ? 'd-none' : '' ?>" id="product-dimensions">
                                                                        <div class="col-md-6">
                                                                            <label for="weight" class="control-label col-md-12">Parcel weight &amp; size <small class="d-block text-muted font-weight-normal">Used by the courier to price delivery &mdash; keep it accurate.</small></label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group row  dimensions  <?= $product_details[0]['type'] == "digital_product" ? 'd-none' : '' ?>">
                                                                        <div class="col-3">
                                                                            <label for="weight" class="control-label col-md-12">Weight <small>(grams)</small> <span class='text-danger text-xs'>*</span></label>
                                                                            <input type="number" class="form-control" name="weight" placeholder="e.g. 500" id="weight" value="<?= weight_kg_to_grams($simple_variant['weight'] ?? 0) ?>" step="0.01">
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <label for="height" class="control-label col-md-12">Height <small>(cm)</small> <span class='text-danger text-xs'>*</span></label>
                                                                            <input type="number" class="form-control" name="height" placeholder="Height" id="height" value="<?= html_escape($simple_variant['height'] ?? '') ?>" step="0.01">
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <label for="breadth" class="control-label col-md-12">Breadth <small>(cm)</small> <span class='text-danger text-xs'>*</span></label>
                                                                            <input type="number" class="form-control" name="breadth" placeholder="Breadth" id="breadth" value="<?= html_escape($simple_variant['breadth'] ?? '') ?>" step="0.01">
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <label for="length" class="control-label col-md-12">Length <small>(cm)</small> <span class='text-danger text-xs'>*</span></label>
                                                                            <input type="number" class="form-control" name="length" placeholder="Length" id="length" value="<?= html_escape($simple_variant['length'] ?? '') ?>" step="0.01">
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-group <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                                        <div class="col">
                                                                            <input type="checkbox" name="simple_stock_management_status" class="align-middle simple_stock_management_status" <?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] != NULL) ? 'checked' : '' ?>> <span class="align-middle">Track my stock <small class="text-muted">(turn on to enter how many you have)</small></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group simple-product-level-stock-management <?= $HideStatus ?>">
                                                                    <div class="col col-xs-12">
                                                                        <label class="control-label">SKU :</label>
                                                                        <input type="text" name="product_sku" class="col form-control simple-pro-sku" value="<?= html_escape((isset($product_details[0]['id']) && $product_details[0]['stock_type'] != NULL) ? $product_details[0]['sku'] : '') ?>">
                                                                    </div>
                                                                    <div class="col col-xs-12">
                                                                        <label class="control-label">Total Stock :</label>
                                                                        <input type="number" min="1" name="product_total_stock" class="col form-control stock-simple-mustfill-field" <?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] != NULL) ? ' value="' . $product_details[0]['stock'] . '" ' : '' ?>>
                                                                    </div>
                                                                    <div class="col col-xs-12">
                                                                        <label class="control-label">Stock Status :</label>
                                                                        <select type="text" class="col form-control stock-simple-mustfill-field" id="simple_product_stock_status">
                                                                            <option value="1" <?= (isset($product_details[0]['stock_type']) &&
                                                                                                    $product_details[0]['stock_type'] != NULL && $product_details[0]['availability'] == "1") ? 'selected' : '' ?>>In Stock</option>
                                                                            <option value="0" <?= (isset($product_details[0]['stock_type']) &&
                                                                                                    $product_details[0]['stock_type'] != NULL && $product_details[0]['availability'] == "0") ? 'selected' : '' ?>>Out Of Stock</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <?php if (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') { ?>
                                                                    <div id="digital_product_setting">
                                                                        <div class="row form-group">
                                                                            <div class="col-md-2 col-xs-6 ml-2">
                                                                                <label for="is_cod_allowed" class="col-form-label">Is Download allowed?</label>
                                                                                <input type="checkbox" name="download_allowed" id="download_allowed" class="switch" <?= (isset($product_details[0]['download_allowed']) && $product_details[0]['download_allowed'] == '1') ? 'Checked' : ''; ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                                                            </div>
                                                                            <div class="col-md-3 col-xs-6 <?= (isset($product_details[0]['download_type'])) ? '' : 'collapse' ?>" id='download_type'>
                                                                                <label for="download_allowed" class="col-form-label">Download Link Type <span class='text-danger text-sm'>*</span></label>
                                                                                <select class='form-control' name="download_link_type" id="download_link_type">
                                                                                    <option value=''>None</option>
                                                                                    <option value='self_hosted' <?= (isset($product_details[0]['download_type']) && $product_details[0]['download_type'] == 'self_hosted') ? 'selected' : '' ?>>Self Hosted</option>
                                                                                    <option value='add_link' <?= (isset($product_details[0]['download_type']) && $product_details[0]['download_type'] == 'add_link') ? 'selected' : '' ?>>Add Link</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-6 <?= (isset($product_details[0]['download_type']) && ($product_details[0]['download_type'] == 'add_link')) ? '' : 'd-none'; ?>" id="digital_link_container">
                                                                                <label for="video" class="col-form-label ml-1">Digital Product Link <span class='text-danger text-sm'>*</span></label>
                                                                                <input type="url" class='form-control' name='download_link' id='download_link' value="<?= html_escape((isset($product_details[0]['download_type']) && ($product_details[0]['download_type'] == 'add_link')) ? $product_details[0]['download_link'] : '') ?>" placeholder="Paste digital product link or URL here">
                                                                            </div>
                                                                            <div class="col-md-6 mt-2 <?= (isset($product_details[0]['download_type']) && ($product_details[0]['download_type'] == 'self_hosted')) ? '' : 'd-none'; ?>" id="digital_media_container">
                                                                                <label for="image" class="ml-2">File <span class='text-danger text-sm'>*</span></label>
                                                                                <div class='col-md-3'><button type="button" class="btn btn-primary btn-sm direct-upload-btn"><i class='fa fa-upload'></i> Choose File</button><input type="file" class="d-none direct-upload-input" data-input="pro_input_zip" data-multiple="0" data-media-type="archive,document"></div>
                                                                                <?php if (isset($product_details[0]['id']) && !empty($product_details[0]['id']) && isset($product_details[0]['download_type']) &&  $product_details[0]['download_type'] == 'self_hosted') { ?>
                                                                                    <label class="text-danger mt-3">*Only Choose When Update is necessary</label>
                                                                                    <div class="container-fluid row image-upload-section">
                                                                                        <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image">
                                                                                            <div class='image-upload-div'><img class="img-fluid mb-2" src="<?= base_url('assets/admin/images/archive-file.png') ?>" alt="Image Not Found"></div>
                                                                                            <input type="hidden" name="pro_input_zip" value='<?= $product_details[0]['download_link'] ?>'>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php } else { ?>
                                                                                    <div class="container-fluid row image-upload-section">
                                                                                        <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                                                        </div>
                                                                                    </div>
                                                                                <?php } ?>

                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                <?php } ?>
                                                                <div class="form-group simple-product-save">
                                                                    <div class="col">
                                                                        <a href="javascript:void(0);" class="btn btn-primary save-settings">Save settings</a>
                                                                        <a href="javascript:void(0);" class="btn btn-warning reset-settings">Reset Settings</a>
                                                                    </div>
                                                                </div>
                                                            <?php } else { ?>
                                                                <div id="variant_stock_level">
                                                                    <div class="form-group">
                                                                        <div class="col">
                                                                            <input type="checkbox" name="variant_stock_management_status" class="align-middle variant_stock_status" <?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] != NULL) ? 'checked' : '' ?>> <span class="align-middle"> Track my stock <small class="text-muted">(turn on to enter how many you have)</small></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group <?= (intval($product_details[0]['stock_type']) > 0) ? '' : 'collapse' ?>" id='stock_level'>
                                                                        <label for="type" class="col-md-2">Choose Stock Management Type:</label>
                                                                        <div class="col-md-12">
                                                                            <select id="stock_level_type" class="form-control variant-stock-level-type" data-placeholder=" Type to search and select type">
                                                                                <option value=" ">Select Stock Type</option>
                                                                                <option value="product_level" <?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] == '1') ? 'Selected' : '' ?>> Product Level ( Stock Will Be Managed Generally )</option>
                                                                                <option value="variable_level" <?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] == '2') ? 'Selected' : '' ?>>Variable Level ( Stock Will Be Managed Variant Wise )</option>
                                                                            </select>
                                                                            <div class="form-group variant-product-level-stock-management <?= (intval($product_details[0]['stock_type']) == 1) ? '' : 'collapse' ?>">
                                                                                <div class="col col-xs-12">
                                                                                    <label class="control-label">SKU :</label>
                                                                                    <input type="text" name="sku_variant_type" class="col form-control" value="<?= html_escape((intval($product_details[0]['stock_type']) == 1 && isset($product_variants[0]['id']) && !empty($product_variants[0]['sku'])) ? $product_variants[0]['sku'] : '') ?>">
                                                                                </div>
                                                                                <div class="col col-xs-12">
                                                                                    <label class="control-label">Total Stock :</label>
                                                                                    <input type="number" min="1" name="total_stock_variant_type" class="col form-control variant-stock-mustfill-field" value="<?= html_escape((intval($product_details[0]['stock_type']) == 1 && isset($product_variants[0]['id']) && !empty($product_variants[0]['stock'])) ? $product_variants[0]['stock'] : '') ?>">
                                                                                </div>
                                                                                <div class="col col-xs-12">
                                                                                    <label class="control-label">Stock Status :</label>
                                                                                    <select type="text" id="stock_status_variant_type" name="variant_status" class="col form-control variant-stock-mustfill-field">
                                                                                        <option value="1" <?= (intval($product_details[0]['stock_type']) == 1 && isset($product_variants[0]['id']) && $product_variants[0]['availability'] == '1') ? 'Selected' : '' ?>>In Stock</option>
                                                                                        <option value="0" <?= (intval($product_details[0]['stock_type']) == 1 && isset($product_variants[0]['id']) && $product_variants[0]['availability'] == '0') ? 'Selected' : '' ?>>Out Of Stock</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <div class="col">
                                                                            <a href="javascript:void(0);" class="btn btn-primary save-variant-general-settings">Save settings</a>
                                                                            <a href="javascript:void(0);" class="btn btn-warning reset-settings">Reset Settings</a>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="product-attributes" role="tabpanel" aria-labelledby="product-attributes-tab">
<?php /* options intro */ ?><p class="czpf-opt-intro"><i class="fas fa-info-circle"></i> <span>Add the choices buyers pick from &mdash; e.g. <b>Size</b>: S, M, L or <b>Colour</b>: Red, Blue. Keep <b>Use for variations</b> ticked and every combination becomes a variation. After changing options, press <b>Save options</b>.</span></p>
                                                        <div class="info col-12 p-3 d-none" id="note">
                                                            <div class=" col-12 d-flex align-center">
                                                                <strong>Note : </strong>
                                                                <input type="checkbox" checked="" class="ml-3 my-auto custom-checkbox" disabled>
                                                                <span class="ml-3">Tick the box on an option if buyers choose between its values (e.g. Size), so each combination gets its own price.</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <a href="javascript:void(0);" id="add_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm">Add option</a>
                                                            <a href="javascript:void(0);" id="save_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm d-none">Save options</a>
                                                        </div>
                                                        <div class="clearfix"></div>

                                                        <div id="attributes_process">
                                                            <div class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-attributes-added">
                                                                <div class="col-md-12 text-center">No options added yet. Add one, e.g. Size or Colour.</div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="tab-pane fade" id="product-variants" role="tabpanel" aria-labelledby="product-variants-tab">
                                                        <div class="col-md-12">
                                                            <a href="javascript:void(0);" id="reset_variants" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm collapse">Reset Variants</a>
                                                        </div>
                                                        <div>
                                                            <a class="btn btn-outline-primary btn-sm mb-3" href="javascript:void(0)" id="expand_all">Expand All</a>
                                                            <a class="btn btn-outline-primary btn-sm mb-3 ml-4" href="javascript:void(0)" id="collapse_all">Collapse All</a>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-variants-added">
                                                            <div class="col-md-12 text-center"> No variations yet. Add options first, then set a price for each combination here. </div>
                                                        </div>
                                                        <div id="variants_process" class="ui-sortable">

                                                            <div class="form-group move row my-auto p-2 border rounded bg-gray-light product-variant-selectbox">
                                                                <div class="col-1 text-center my-auto">
                                                                    <i class="fas fa-sort"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } else { ?>
                                            <div class="additional-info existing-additional-settings">
                                                <nav class="w-100">
                                                    <div class="nav nav-tabs" id="product-tab" role="tablist"> <a class="nav-item nav-link active" id="tab-for-general-price" data-toggle="tab" href="#general-settings" role="tab" aria-controls="general-price" aria-selected="true">Price &amp; stock</a> <a class="nav-item nav-link disabled product-attributes" id="tab-for-attributes" data-toggle="tab" href="#product-attributes" role="tab" aria-controls="product-attributes" aria-selected="false">Options</a> <a class="nav-item nav-link disabled product-variants d-none" id="tab-for-variations" data-toggle="tab" href="#product-variants" role="tab" aria-controls="product-variants" aria-selected="false">Variations</a>
                                                    </div>
                                                </nav>
                                                <div class="tab-content p-3" id="nav-tabContent">
                                                                <div class="tab-pane fade active show" id="general-settings" role="tabpanel" aria-labelledby="general-settings-tab">
                                                                    <div class="form-group">
                                                                        <label for="product-type" class="col-md-12 col-form-label">Does this product have options like size or colour?</label>
                                                                        <div class="col-md-12">
                                                                            <input type="hidden" name="product_type">
                                                                            <input type="hidden" name="simple_product_stock_status">
                                                                            <input type="hidden" name="variant_stock_level_type">
                                                                            <input type="hidden" name="variant_stock_status">
                                                                            <select name="type" id="product-type" class="form-control product-type" data-placeholder=" Type to search and select type">
                                                                                <option value=" ">Choose one...</option>
                                                                                <option value="simple_product">Simple &mdash; one price, no options</option>
                                                                                <option value="variable_product">With options &mdash; size, colour, etc.</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div id="product-general-settings">
                                                                        <div id="general_price_section" class="collapse">
                                                                            <div class="form-group">
                                                                                <label for="type" class="col-md-2">Price (MRP):</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="number" name="simple_price" class="form-control stock-simple-mustfill-field price" min='0' step="0.01">
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group">
                                                                                <label for="type" class="col-md-2">Special price <small class="text-muted font-weight-normal">(optional)</small>:</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="number" name="simple_special_price" class="form-control discounted_price" min='0' step="0.01">
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group row mt-3" id="product-dimensions">
                                                                                <div class="col-md-6">
                                                                                    <label for="weight" class="control-label col-md-12">Parcel weight &amp; size <small class="d-block text-muted font-weight-normal">Used by the courier to price delivery &mdash; keep it accurate.</small></label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group row" id="product-dimensions">
                                                                                <div class="col-3">
                                                                                    <label for="weight" class="control-label col-md-12">Weight <small>(grams)</small> <span class='text-danger text-xs'>*</span></label>
                                                                                    <input type="number" class="form-control" name="weight" placeholder="e.g. 500" id="weight" value="" step="1" min="1">
                                                                                </div>
                                                                                <div class="col-3">
                                                                                    <label for="height" class="control-label col-md-12">Height <small>(cm)</small> <span class='text-danger text-xs'>*</span></label>
                                                                                    <input type="number" class="form-control" name="height" placeholder="Height" id="height" value="" step="0.01">
                                                                                </div>
                                                                                <div class="col-3">
                                                                                    <label for="breadth" class="control-label col-md-12">Breadth <small>(cm)</small> <span class='text-danger text-xs'>*</span></label>
                                                                                    <input type="number" class="form-control" name="breadth" placeholder="Breadth" id="breadth" value="" step="0.01">
                                                                                </div>
                                                                                <div class="col-3">
                                                                                    <label for="length" class="control-label col-md-12">Length <small>(cm)</small> <span class='text-danger text-xs'>*</span></label>
                                                                                    <input type="number" class="form-control" name="length" placeholder="Length" id="length" value="" step="0.01">
                                                                                </div>
                                                                            </div>

                                                                            <div class="form-group  simple_stock_management">
                                                                                <div class="col">
                                                                                    <input type="checkbox" name="simple_stock_management_status" class="align-middle simple_stock_management_status"> <span class="align-middle">Track my stock <small class="text-muted">(turn on to enter how many you have)</small></span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group simple-product-level-stock-management collapse">
                                                                            <div class="col col-xs-12">
                                                                                <label class="control-label">SKU :</label>
                                                                                <input type="text" name="product_sku" class="col form-control simple-pro-sku">
                                                                            </div>
                                                                            <div class="col col-xs-12">
                                                                                <label class="control-label">Total Stock :</label>
                                                                                <input type="number" min="1" name="product_total_stock" class="col form-control stock-simple-mustfill-field">
                                                                            </div>
                                                                            <div class="col col-xs-12">
                                                                                <label class="control-label">Stock Status :</label>
                                                                                <select type="text" class="col form-control stock-simple-mustfill-field" id="simple_product_stock_status">
                                                                                    <option value="1">In Stock</option>
                                                                                    <option value="0">Out Of Stock</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group collapse simple-product-save">
                                                                            <div class="col"> <a href="javascript:void(0);" class="btn btn-primary save-settings">Save settings</a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div id="variant_stock_level" class="collapse">
                                                                        <div class="form-group">
                                                                            <div class="col">
                                                                                <input type="checkbox" name="variant_stock_management_status" class="align-middle variant_stock_status"> <span class="align-middle"> Track my stock <small class="text-muted">(turn on to enter how many you have)</small></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group collapse" id="stock_level">
                                                                            <label for="type" class="col-md-2">Choose Stock Management Type:</label>
                                                                            <div class="col-md-12">
                                                                                <select id="stock_level_type" class="form-control variant-stock-level-type" data-placeholder=" Type to search and select type">
                                                                                    <option value=" ">Select Stock Type</option>
                                                                                    <option value="product_level">Product Level ( Stock Will Be Managed Generally )</option>
                                                                                    <option value="variable_level">Variable Level ( Stock Will Be Managed Variant Wise )</option>
                                                                                </select>
                                                                                <div class="form-group row variant-product-level-stock-management collapse">
                                                                                    <div class="col col-xs-12">
                                                                                        <label class="control-label">SKU :</label>
                                                                                        <input type="text" name="sku_variant_type" class="col form-control">
                                                                                    </div>
                                                                                    <div class="col col-xs-12">
                                                                                        <label class="control-label">Total Stock :</label>
                                                                                        <input type="number" min="1" name="total_stock_variant_type" class="col form-control variant-stock-mustfill-field">
                                                                                    </div>
                                                                                    <div class="col col-xs-12">
                                                                                        <label class="control-label">Stock Status :</label>
                                                                                        <select type="text" id="stock_status_variant_type" name="variant_status" class="col form-control variant-stock-mustfill-field">
                                                                                            <option value="1">In Stock</option>
                                                                                            <option value="0">Out Of Stock</option>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <div class="col"> <a href="javascript:void(0);" class="btn btn-primary save-variant-general-settings">Save settings</a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div id="digital_product_setting" class="collapse">
                                                                        <div class="row form-group">
                                                                            <div class="col-md-2 col-xs-6 ml-2">
                                                                                <label for="is_cod_allowed" class="col-form-label">Is Download allowed?</label>
                                                                                <input type="checkbox" name="download_allowed" id="download_allowed" class="switch" <?= (isset($product_details[0]['download_allowed']) && $product_details[0]['download_allowed'] == '1') ? 'Checked' : ''; ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                                                            </div>
                                                                            <div class="col-md-3 col-xs-6 collapse" id='download_type'>
                                                                                <label for="download_allowed" class="col-form-label">Download Link Type <span class='text-danger text-sm'>*</span></label>
                                                                                <select class='form-control' name="download_link_type" id="download_link_type">
                                                                                    <option value=''>None</option>
                                                                                    <option value='self_hosted'>Self Hosted</option>
                                                                                    <option value='add_link'>Add Link</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-6 d-none" id="digital_link_container">
                                                                                <label for="video" class="col-form-label ml-1">Digital Product Link <span class='text-danger text-sm'>*</span></label>
                                                                                <input type="url" class='form-control' name='download_link' id='download_link' value="" placeholder="Paste digital product link or URL here">
                                                                            </div>
                                                                            <div class="col-md-6 mt-2 d-none" id="digital_media_container">
                                                                                <label for="image" class="ml-2">File <span class='text-danger text-sm'>*</span></label>
                                                                                <div class='col-md-3'><button type="button" class="btn btn-primary btn-sm direct-upload-btn"><i class='fa fa-upload'></i> Choose File</button><input type="file" class="d-none direct-upload-input" data-input="pro_input_zip" data-multiple="0" data-media-type="archive,document"></div>
                                                                                <div class="container-fluid row image-upload-section">
                                                                                    <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="form-group mt-3 ml-2">
                                                                                <div class="col"> <a href="javascript:void(0);" class="btn btn-primary save-digital-product-settings">Save settings</a></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                                <div class="tab-pane fade" id="product-attributes" role="tabpanel" aria-labelledby="product-attributes-tab">
<?php /* options intro */ ?><p class="czpf-opt-intro"><i class="fas fa-info-circle"></i> <span>Add the choices buyers pick from &mdash; e.g. <b>Size</b>: S, M, L or <b>Colour</b>: Red, Blue. Keep <b>Use for variations</b> ticked and a variation is created for every combination <b>automatically</b> &mdash; there is nothing to save here. Prices come from <b>Price &amp; stock</b> and can be fine-tuned on the Variations tab.</span></p>
                                                                    <div class="info col-12 p-3 d-none" id="note">
                                                                        <div class=" col-12 d-flex align-center"> <strong>Note : </strong>
                                                                            <input type="checkbox" checked="checked" class="ml-3 my-auto custom-checkbox" disabled> <span class="ml-3">Tick the box on an option if buyers choose between its values (e.g. Size), so each combination gets its own price.</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-12"> 
                                                                        <a href="javascript:void(0);" id="add_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm">Add option</a> 
                                                                        <a href="javascript:void(0);" id="save_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm d-none">Save options</a>
                                                                    </div>
                                                                    <div class="clearfix"></div>
                                                                    <div id="attributes_process">
                                                                        <div class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-attributes-added">
                                                                            <div class="col-md-12 text-center">No options added yet. Add one, e.g. Size or Colour.</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="tab-pane fade" id="product-variants" role="tabpanel" aria-labelledby="product-variants-tab">
                                                                    <div class="clearfix"></div>
                                                                    <div class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-variants-added">
                                                                        <div class="col-md-12 text-center">No variations yet. Add options first, then set a price for each combination here.</div>
                                                                    </div>
                                                                    <div id="variants_process" class="ui-sortable"></div>
                                                                </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </section>

                                <?php /* ============ 4. PICKUP ============ */ ?>
                                <section class="czpf-card pickup_locations <?= $dn ?>" id="czpf-ship">
                                    <header class="czpf-head"><span class="czpf-num">4</span><div><h5>Pickup location</h5><p>Where should the courier collect this product from?</p></div></header>
                                    <div class="czpf-body">
                                        <div class="form-group mb-0 standdard_shipping">
                                            <label for="pickup_location" class="col-form-label">Pickup location <?= $req ?></label>
                                            <?php
                                            // Populated for the selected seller only and refreshed whenever the seller changes.
                                            $current_pickup = !empty($pd['pickup_location']) ? $pd['pickup_location'] : "";
                                            ?>
                                            <select class='form-control shiprocket_type' name="pickup_location" id="pickup_location"
                                                data-url="<?= base_url('admin/product/get_seller_pickup_locations') ?>"
                                                data-selected="<?= html_escape($current_pickup) ?>">
                                                <option value="">Select Pickup Location</option>
                                                <?php foreach ((array) $shipping_data as $row) { ?>
                                                    <option <?= ($row['pickup_location'] == $current_pickup) ? 'selected' : '' ?> value="<?= html_escape($row['pickup_location']); ?>"><?= html_escape($row['pickup_location']); ?></option>
                                                <?php } ?>
                                            </select>
                                            <small class="text-muted d-block mt-1" id="pickup_hint"></small>
                                        </div>
                                    </div>
                                </section>

                                <?php /* ============ 5. DESCRIPTION ============ */ ?>
                                <section class="czpf-card" id="czpf-desc">
                                    <header class="czpf-head"><span class="czpf-num">5</span><div><h5>Description</h5><p>Tell buyers about materials, size, care and anything else they should know.</p></div></header>
                                    <div class="czpf-body">
                                        <div class="form-group">
                                            <label class="col-form-label">Full description</label>
                                            <textarea name="pro_input_description" class="textarea addr_editor" placeholder="Place some text here"><?= $is_edit ? html_escape(unslash(str_replace('\r\n', '&#13;&#10;', $pd['description']))) : ''; ?></textarea>
                                        </div>
                                        <?php $has_extra = $is_edit && trim((string) ($pd['extra_description'] ?? '')) !== ''; ?>
                                        <div class="czpf-fold <?= $has_extra ? 'is-open' : '' ?>">
                                            <button type="button" class="czpf-fold-toggle" aria-expanded="<?= $has_extra ? 'true' : 'false' ?>"><i class="fas fa-chevron-right"></i> Add extra details <small>(optional)</small></button>
                                            <div class="czpf-fold-body">
                                                <div class="form-group mb-0">
                                                    <label class="col-form-label">Extra description</label>
                                                    <textarea name="extra_input_description" class="textarea addr_editor" placeholder="Place some text here"><?= $is_edit ? html_escape(unslash(str_replace('\r\n', '&#13;&#10;', $pd['extra_description']))) : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <?php /* ============ MORE OPTIONS (collapsed) ============ */ ?>
                                <section class="czpf-card czpf-more czpf-fold" id="czpf-more">
                                    <button type="button" class="czpf-fold-toggle czpf-more-toggle" aria-expanded="false">
                                        <span class="czpf-num czpf-num-plus">+</span>
                                        <span class="czpf-more-title"><b>More options</b> <small>optional &mdash; the defaults suit most products</small>
                                            <em>COD &amp; returns, brand, quantity limits, warranty, delivery area</em></span>
                                        <i class="fas fa-chevron-down czpf-more-chev"></i>
                                    </button>
                                    <div class="czpf-fold-body">
                                        <div class="czpf-body czpf-more-inner">

                                            <div class="czpf-group">
                                                <h6>Orders &amp; returns</h6>
                                                <div class="czpf-switches">
                                                    <div class="czpf-switch cod_allowed <?= $dn ?>">
                                                        <label for="cod_allowed">Allow cash on delivery</label>
                                                        <input type="checkbox" name="cod_allowed" id="cod_allowed" <?= (isset($pd['cod_allowed']) && $pd['cod_allowed'] == '1') ? 'checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                                    </div>
                                                    <div class="czpf-switch is_returnable <?= $dn ?>">
                                                        <label for="is_returnable">Buyer can return it</label>
                                                        <input type="checkbox" name="is_returnable" id="is_returnable" <?= (isset($pd['is_returnable']) && $pd['is_returnable'] == '1') ? 'checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                                    </div>
                                                    <div class="czpf-switch is_attachment_required <?= $dn ?>">
                                                        <label for="is_attachment_required">Buyer must attach a file</label>
                                                        <input type="checkbox" role="switch" name="is_attachment_required" id="is_attachment_required" <?= (isset($pd['is_attachment_required']) && $pd['is_attachment_required'] == '1') ? 'checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                                    </div>
                                                    <div class="czpf-switch is_cancelable <?= $dn ?>">
                                                        <label for="is_cancelable">Buyer can cancel the order</label>
                                                        <input type="checkbox" name="is_cancelable" id="is_cancelable" class="switch" <?= (isset($pd['is_cancelable']) && $pd['is_cancelable'] == '1') ? 'checked' : ''; ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                                    </div>
                                                    <div class="czpf-switch-select <?= (isset($pd['is_cancelable']) && $pd['is_cancelable'] == 1) ? '' : 'collapse' ?>" id="cancelable_till">
                                                        <label for="cancelable_till_select">Allow cancelling until <?= $req ?></label>
                                                        <select class='form-control' name="cancelable_till" id="cancelable_till_select">
                                                            <option value='received' <?= (isset($pd['cancelable_till']) && $pd['cancelable_till'] == 'received') ? 'selected' : '' ?>>Order received</option>
                                                            <option value='processed' <?= (isset($pd['cancelable_till']) && $pd['cancelable_till'] == 'processed') ? 'selected' : '' ?>>Order processed</option>
                                                            <option value='shipped' <?= (isset($pd['cancelable_till']) && $pd['cancelable_till'] == 'shipped') ? 'selected' : '' ?>>Order shipped</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="czpf-group">
                                                <h6>Brand &amp; search</h6>
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label for="admin_brand_list" class="col-form-label">Brand</label>
                                                        <select class="form-control admin_brand_list" id="admin_brand_list" name="brand">
                                                            <?php if (isset($pd['brand']) && $pd['brand'] != '') { ?>
                                                                <option value="<?= html_escape($pd['brand']) ?>" selected><?= html_escape($pd['brand']) ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-4 made-in-select">
                                                        <label for="country_list" class="col-form-label">Made in</label>
                                                        <select class="form-control country_list" id="country_list" name="made_in">
                                                            <?php if (isset($pd['made_in']) && $pd['made_in'] != '') { ?>
                                                                <option value="<?= html_escape($pd['made_in']) ?>" selected><?= html_escape($pd['made_in']) ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-4 indicator <?= $dn ?>">
                                                        <label for="indicator" class="col-form-label">Veg / non-veg mark</label>
                                                        <select class='form-control' name='indicator' id="indicator">
                                                            <option value='0' <?= (isset($pd['indicator']) && $pd['indicator'] == '0') ? 'selected' : ''; ?>>Not applicable</option>
                                                            <option value='1' <?= (isset($pd['indicator']) && $pd['indicator'] == '1') ? 'selected' : ''; ?>>Veg</option>
                                                            <option value='2' <?= (isset($pd['indicator']) && $pd['indicator'] == '2') ? 'selected' : ''; ?>>Non-Veg</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label for="tags" class="col-form-label">Search tags <small class="text-muted font-weight-normal">(words buyers might search for &mdash; press Enter after each)</small></label>
                                                    <input name='tags' class='' id='tags' placeholder="e.g. diya, brass, festival" value="<?= html_escape(!empty($pd['tags']) ? $pd['tags'] : "") ?>" />
                                                </div>
                                            </div>

                                            <div class="czpf-group czpf-hide-if-empty">
                                                <h6>Order quantity</h6>
                                                <div class="form-row">
                                                    <div class="form-group col-md-4 minimum_order_quantity <?= $dn ?>">
                                                        <label for="minimum_order_quantity" class="col-form-label">Minimum per order</label>
                                                        <input type="number" class="form-control" id="minimum_order_quantity" name="minimum_order_quantity" min="1" value="<?= html_escape($pd['minimum_order_quantity'] ?? 1) ?>">
                                                    </div>
                                                    <div class="form-group col-md-4 total_allowed_quantity <?= $dn ?>">
                                                        <label for="total_allowed_quantity" class="col-form-label">Maximum per order</label>
                                                        <input type="number" class="form-control" id="total_allowed_quantity" name="total_allowed_quantity" value="<?= html_escape($pd['total_allowed_quantity'] ?? '') ?>" placeholder="No limit">
                                                    </div>
                                                    <div class="form-group col-md-4 quantity_step_size <?= $dn ?>">
                                                        <label for="quantity_step_size" class="col-form-label">Sold in multiples of</label>
                                                        <input type="number" class="form-control" id="quantity_step_size" name="quantity_step_size" min="1" value="<?= html_escape($pd['quantity_step_size'] ?? 1) ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="czpf-group czpf-hide-if-empty">
                                                <h6>Warranty</h6>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6 warranty_period <?= $dn ?>">
                                                        <label for="warranty_period" class="col-form-label">Warranty period</label>
                                                        <input type="text" class="form-control" id="warranty_period" name="warranty_period" value="<?= html_escape($pd['warranty_period'] ?? '') ?>" placeholder="e.g. 1 year, if any">
                                                    </div>
                                                    <div class="form-group col-md-6 guarantee_period <?= $dn ?>">
                                                        <label for="guarantee_period" class="col-form-label">Guarantee period</label>
                                                        <input type="text" class="form-control" id="guarantee_period" name="guarantee_period" value="<?= html_escape($pd['guarantee_period'] ?? '') ?>" placeholder="e.g. 6 months, if any">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="czpf-group deliverable_type <?= $dn ?>">
                                                <h6>Delivery area</h6>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label for="deliverable_type" class="col-form-label">Deliver to</label>
                                                        <select class='form-control' name='deliverable_type' id="deliverable_type">
                                                            <option value=<?= NONE ?> <?= (isset($pd['deliverable_type']) && $pd['deliverable_type'] == NONE) ? 'selected' : ''; ?>>Nowhere (pause delivery)</option>
                                                            <?php if (!isset($product_details)) { ?>
                                                                <option value=<?= ALL ?> selected>All pincodes</option>
                                                            <?php } else { ?>
                                                                <option value=<?= ALL ?> <?= (isset($pd['deliverable_type']) && $pd['deliverable_type'] == ALL) ? 'selected' : ''; ?>>All pincodes</option>
                                                            <?php } ?>
                                                            <option value=<?= INCLUDED ?> <?= (isset($pd['deliverable_type']) && $pd['deliverable_type'] == INCLUDED) ? 'selected' : ''; ?>>Only the pincodes I choose</option>
                                                            <option value=<?= EXCLUDED ?> <?= (isset($pd['deliverable_type']) && $pd['deliverable_type'] == EXCLUDED) ? 'selected' : ''; ?>>Everywhere except the pincodes I choose</option>
                                                        </select>
                                                    </div>
                                                    <?php $zipcodes = (isset($pd['deliverable_zipcodes']) && $pd['deliverable_zipcodes'] != NULL) ? explode(",", $pd['deliverable_zipcodes']) : ""; ?>
                                                    <div class="form-group col-md-6">
                                                        <label for="deliverable_zipcodes" class="col-form-label">Pincodes</label>
                                                        <select name="deliverable_zipcodes[]" class="search_zipcode form-control w-100" multiple onload="multiselect()" id="deliverable_zipcodes" <?= (isset($pd['deliverable_type']) && ($pd['deliverable_type'] == INCLUDED || $pd['deliverable_type'] == EXCLUDED)) ? "" : "disabled" ?>>
                                                            <?php if (isset($pd['deliverable_type']) && ($pd['deliverable_type'] == INCLUDED || $pd['deliverable_type'] == EXCLUDED)) {
                                                                $zipcodes_name = fetch_details('zipcodes', "", 'zipcode,id', "", "", "", "", "id", $zipcodes);
                                                                foreach ($zipcodes_name as $row) { ?>
                                                                    <option value=<?= $row['id'] ?> <?= (in_array($row['id'], $zipcodes)) ? 'selected' : ''; ?>> <?= $row['zipcode'] ?></option>
                                                            <?php }
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </section>

                                <div id="attributes_values_json_data" class="d-none">
                                    <select class="select_single" data-placeholder=" Type to search and select attributes">
                                        <option value=""></option>
                                        <?php foreach ($attributes_refind as $key => $value) { ?>
                                            <optgroup label="<?= $key ?>"><?= $key ?>
                                                <?php foreach ($value as $key => $value) { ?>
                                                    <option name='<?= $key ?>' value='<?= $key ?>' data-values='<?= json_encode($value, 1) ?>'><?= $key ?></option>
                                                <?php } ?>
                                            </optgroup>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box"></div>
                                </div>
                                <div class="form-group product-form-actions">
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= $is_edit ? 'Update Product' : 'Add Product' ?></button>
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!--/.card-->
                </div>
                <!--/.col-md-12-->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/product-form.css') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/css/cretzo/product-form.css') ?: time() ?>">
<style>
    .admin-create-product-page .text-primary-theme { color: var(--color-orange); }

    .admin-create-product-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }

    .admin-create-product-page .section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--color-orange-dark);
        border-bottom: 2px solid var(--color-orange-light);
        padding-bottom: 8px;
        margin: 26px 0 18px;
        width: 100%;
    }
    .admin-create-product-page .card-body > .section-header:first-child { margin-top: 0; }
    .admin-create-product-page .section-header i { color: var(--color-orange); }

    .admin-create-product-page label.col-form-label,
    .admin-create-product-page label.control-label { font-weight: 600; font-size: 14px; }
    .admin-create-product-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem rgba(242,130,46,.18);
    }
    .admin-create-product-page .nav-tabs .nav-link.active {
        color: var(--color-orange-dark);
        border-bottom: 2px solid var(--color-orange);
        font-weight: 600;
    }
    .admin-create-product-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
        font-weight: 600;
    }
    .admin-create-product-page .btn-success:hover:not(:disabled) { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }

    /* ---- searchable category selector ---- */
    .admin-create-product-page .category-combo { position: relative; }
    .admin-create-product-page .category-search-box { position: relative; }
    .admin-create-product-page .category-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-grey);
        font-size: 13px;
        pointer-events: none;
    }
    .admin-create-product-page .category-search-input { padding-left: 32px; padding-right: 30px; }
    .admin-create-product-page .category-search-clear {
        display: none;
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        font-size: 20px;
        line-height: 1;
        color: var(--color-grey);
        cursor: pointer;
        padding: 0 4px;
    }
    .admin-create-product-page .category-search-clear:hover { color: var(--color-theme-red); }
    .admin-create-product-page #category_dropdown {
        display: none;
        position: absolute;
        z-index: 1050;
        left: 0;
        right: 0;
        max-height: 260px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.12);
        margin-top: 4px;
    }
    .admin-create-product-page .category-result-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .admin-create-product-page .category-result-item:last-child { border-bottom: none; }
    .admin-create-product-page .category-result-item:hover { background: var(--color-orange-light); }
    .admin-create-product-page .category-result-name { font-weight: 600; font-size: 14px; }
    .admin-create-product-page .category-result-path { font-size: 12px; color: var(--color-grey); }
    .admin-create-product-page .category-empty { padding: 10px 12px; color: var(--color-grey); font-size: 13px; }
</style>

<script>
$(document).ready(function () {
    // ------------------------------------------------------------------
    // Searchable category selector.
    //
    // The previous control was a jsTree checkbox tree fed from
    // admin/category/get_categories. Those nodes carry a "name" property, but
    // jsTree reads its label from "text" only and falls back to an empty string -
    // so every category rendered as an unlabelled checkbox. This replaces it with
    // the same search-and-select control used on the seller product form, and
    // reloads the list for whichever seller is chosen.
    // ------------------------------------------------------------------
    var $input    = $('#category_search_input');
    var $dropdown = $('#category_dropdown');
    var $clearBtn = $('#category_search_clear');
    var $selected = $('#product_category_id');
    var $hint     = $('#category_hint');
    var $seller   = $('#seller_id');

    var allCategories = [];

    // Flattens the category tree (to any depth) into one searchable list, keeping
    // each entry's own name plus its full breadcrumb path.
    function flatten(nodes, pathParts, into) {
        (nodes || []).forEach(function (cat) {
            var parts = pathParts.concat([cat.name]);
            var existing = into[cat.id];
            if (!existing || parts.length > existing.depth) {
                into[cat.id] = { id: cat.id, name: cat.name, fullPath: parts.join(' > '), depth: parts.length };
            }
            if (cat.children && cat.children.length) {
                flatten(cat.children, parts, into);
            }
        });
        return into;
    }

    function setCategories(tree) {
        var byId = flatten(tree, [], {});
        allCategories = Object.keys(byId).map(function (id) { return byId[id]; });

        // Keep the current selection only if it still exists for this seller.
        var currentId = $selected.val();
        if (currentId) {
            var match = allCategories.filter(function (c) { return String(c.id) === String(currentId); })[0];
            if (match) {
                $input.val(match.fullPath);
                $clearBtn.show();
            } else {
                $selected.val('');
                $input.val('');
                $clearBtn.hide();
            }
        }

        if (!allCategories.length) {
            $hint.text('This seller has no categories assigned.');
        } else {
            $hint.text('Search and select a category (' + allCategories.length + ' available)');
        }
    }

    function renderDropdown(term) {
        $dropdown.empty();
        var lower = (term || '').toLowerCase();
        var filtered = allCategories.filter(function (c) {
            return c.fullPath.toLowerCase().indexOf(lower) > -1;
        });

        if (!filtered.length) {
            $dropdown.append($('<div>').addClass('category-empty').text(
                allCategories.length ? 'No category matches that search' : 'No categories available for this seller'
            )).show();
            return;
        }

        filtered.slice(0, 200).forEach(function (cat) {
            var $item = $('<div>').addClass('category-result-item')
                .append($('<div>').addClass('category-result-name').text(cat.name));
            if (cat.fullPath !== cat.name) {
                $item.append($('<div>').addClass('category-result-path').text(cat.fullPath));
            }
            $item.on('mousedown', function (e) {
                e.preventDefault();
                $input.val(cat.fullPath);
                $selected.val(cat.id);
                $clearBtn.show();
                $dropdown.hide();
            });
            $dropdown.append($item);
        });
        $dropdown.show();
    }

    $input.on('focus', function () { renderDropdown($(this).val()); });
    $input.on('input', function () {
        // Typing invalidates the previous pick, so the hidden id is cleared until
        // something is chosen from the list again.
        $selected.val('');
        $clearBtn.toggle($(this).val().length > 0);
        renderDropdown($(this).val());
    });
    $clearBtn.on('mousedown', function (e) {
        e.preventDefault();
        $input.val('').trigger('focus');
        $selected.val('');
        $clearBtn.hide();
    });
    $input.on('blur', function () { setTimeout(function () { $dropdown.hide(); }, 150); });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.category-combo').length) { $dropdown.hide(); }
    });

    function loadCategoriesForSeller(sellerId) {
        if (!sellerId) {
            setCategories([]);
            $hint.text('Select a seller first to see their categories');
            return;
        }
        $hint.text('Loading categories...');
        $.ajax({
            url: "<?= base_url('admin/category/get_seller_categories') ?>",
            type: 'GET',
            data: { seller_id: sellerId, ignore_status: 1 },
            dataType: 'json',
            success: function (result) { setCategories((result && result.data) || []); },
            error: function () { setCategories([]); $hint.text('Categories could not be loaded. Please try again.'); }
        });
    }

    // ------------------------------------------------------------------
    // Pickup locations follow the selected seller.
    // ------------------------------------------------------------------
    var $pickup = $('#pickup_location');
    var $pickupHint = $('#pickup_hint');

    function loadPickupLocations(sellerId) {
        if (!$pickup.length) return;
        var keep = $pickup.data('selected') || '';
        if (!sellerId) {
            $pickup.html('<option value="">Select Pickup Location</option>');
            $pickupHint.text('Select a seller first to see their pickup locations');
            return;
        }
        $pickupHint.text('Loading pickup locations...');
        $.ajax({
            url: $pickup.data('url'),
            type: 'GET',
            data: { seller_id: sellerId },
            dataType: 'json',
            success: function (result) {
                var rows = (result && result.data) || [];
                var html = '<option value="">Select Pickup Location</option>';
                rows.forEach(function (row) {
                    var sel = (row.pickup_location === keep) ? ' selected' : '';
                    html += '<option value="' + $('<div>').text(row.pickup_location).html() + '"' + sel + '>' +
                            $('<div>').text(row.pickup_location).html() + '</option>';
                });
                $pickup.html(html);
                $pickupHint.text(rows.length ? '' : 'This seller has no active pickup location yet.');
            },
            error: function () {
                $pickup.html('<option value="">Select Pickup Location</option>');
                $pickupHint.text('Pickup locations could not be loaded. Please try again.');
            }
        });
    }

    $seller.on('change', function () {
        var sellerId = $(this).val();
        // Changing seller invalidates both selections, since categories and pickup
        // addresses both belong to a specific seller.
        $selected.val('');
        $input.val('');
        $clearBtn.hide();
        $pickup.data('selected', '');
        loadCategoriesForSeller(sellerId);
        loadPickupLocations(sellerId);
    });

    // Initial load. On the edit screen the seller is already known.
    var initialSeller = $seller.val();
    if (initialSeller) {
        loadCategoriesForSeller(initialSeller);
    } else {
        // Create screen with no seller chosen yet: show the full category tree the
        // controller already rendered into the page, so the field is usable straight away.
        var seed = [];
        try { seed = JSON.parse($('#category_tree_data').val() || '[]'); } catch (e) { seed = []; }
        setCategories(seed);
        $hint.text(allCategories.length ? 'Showing all categories - choose a seller to narrow this list' : 'Select a seller first to see their categories');
    }
});
</script>
<?php $this->load->view('partials/product-media-uploader', ['upload_url' => base_url('admin/media/upload')]); ?>
<script src="<?= base_url('assets/seller/js/cretzo/product-form.js') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/js/cretzo/product-form.js') ?: time() ?>"></script>
