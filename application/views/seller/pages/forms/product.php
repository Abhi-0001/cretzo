<div class="content-wrapper seller-create-product-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0">
                        <i class="fas fa-box-open mr-2 text-primary-theme"></i><?= isset($product_details[0]['id']) ? 'Update' : 'Add' ?> Product
                    </h4>
                    <p class="text-muted mb-0 small">
                        <?= isset($product_details[0]['id']) ? 'Edit this product\'s details, pricing and stock.' : 'Create a new product for your store.' ?>
                    </p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/product') ?>">Products</a></li>
                        <li class="breadcrumb-item active"><?= isset($product_details[0]['id']) ? 'Update' : 'Add' ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <?php
    // Seller-only: listing quota. An expired subscription blocks new listings, so it needs
    // its own message - the usage banner alone would read "0 remaining, you've reached your
    // plan limit", pointing the seller at an upgrade when they actually need a renewal.
    $lq_expired = (!empty($listing_quota) && isset($listing_quota['status']) && $listing_quota['status'] === 'expired');
    ?>
    <?php if (empty($product_details[0]['id']) && $lq_expired) : ?>
        <section class="content pb-0">
            <div class="container-fluid">
                <div style="border-left:4px solid #dc3545; background:#fff8ef; border-radius:8px; padding:12px 16px; margin-bottom:6px; font-size:14px;">
                    <strong style="color:#dc3545;">Your subscription<?= $listing_quota['plan_name'] !== '' ? ' (' . html_escape($listing_quota['plan_name']) . ')' : '' ?> has expired.</strong>
                    Your existing <?= (int) $listing_quota['used'] ?> products stay live, but you can't add new ones until you renew.
                    <a href="<?= base_url('seller/subscription') ?>">Renew your plan</a> to continue listing.
                </div>
            </div>
        </section>
    <?php elseif (empty($product_details[0]['id']) && !empty($listing_quota) && $listing_quota['limit'] !== null) :
        $lq_remaining = (int) $listing_quota['remaining'];
        $lq_color = $lq_remaining <= 0 ? '#dc3545' : ($lq_remaining <= 5 ? '#F2822E' : '#2e7d32');
    ?>
        <section class="content pb-0">
            <div class="container-fluid">
                <div style="border-left:4px solid <?= $lq_color ?>; background:#fff8ef; border-radius:8px; padding:12px 16px; margin-bottom:6px; font-size:14px;">
                    <strong style="color:<?= $lq_color ?>;">Listings: <?= (int) $listing_quota['used'] ?> / <?= (int) $listing_quota['limit'] ?> used</strong>
                    &mdash; <?= $lq_remaining ?> remaining<?= $listing_quota['plan_name'] !== '' ? ' on the ' . html_escape($listing_quota['plan_name']) . ' plan' : '' ?>.
                    <?php if ($lq_remaining <= 0) : ?>
                        You've reached your plan limit. <a href="<?= base_url('seller/subscription') ?>">Upgrade your plan</a> to add more products.
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <!-- form start -->
                        <form class="form-horizontal" action="<?= base_url('seller/product/add_product'); ?>" method="POST" enctype="multipart/form-data" id="save-product">
                            <?php if (isset($product_details[0]['id'])) {
                            ?>
                                <input type="hidden" name="edit_product_id" value="<?= (isset($product_details[0]['id'])) ? $product_details[0]['id'] : "" ?>">
                                <?php // The seller_id was also emitted here as a hidden input while the Seller
                                      // dropdown below carries the same name, so the form submitted the field
                                      // twice and relied on document order to decide which value won. ?>
                                <input type="hidden" id="subcategory_id_js" value="<?= (isset($product_details[0]['subcategory_id'])) ? $product_details[0]['subcategory_id'] : "" ?>">
                            <?php } ?>
                            <?php
                            // Single source of truth for the chosen category, in both create and edit mode.
                            // Previously this input existed only when editing, so on the create screen the
                            // category lived solely inside the tree widget.
                            ?>
                            <input type="hidden" name="category_id" id="product_category_id" value="<?= isset($product_details[0]['category_id']) ? (int) $product_details[0]['category_id'] : '' ?>">
                            <div class="card-body">

                                <div class="section-header"><i class="fas fa-info-circle"></i> Basic Details</div>

                                <div class="form-group col-md-12">
                                    <label for="pro_input_text" class="col-form-label">Name <span class='text-danger text-sm'>*</span> </label>
                                    <input type="text" class="form-control" id="pro_input_text" placeholder="Product Name" name="pro_input_name" value="<?= (isset($product_details[0]['name'])) ? output_escaping(str_replace('\r\n', '&#13;&#10;', $product_details[0]['name'])) : "" ?>">
                                </div>
                                <div class="row col-md-12">
                                    <?php
                                    // The admin form picks a seller here; on this panel the seller is
                                    // always the logged-in one, so it travels as a hidden field. The
                                    // controller overwrites $_POST['seller_id'] with the session user
                                    // regardless, so this is for the model's benefit, not a trust
                                    // boundary.
                                    ?>
                                    <input type="hidden" name="seller_id" id="seller_id" value="<?= (int) ($seller_id ?? $this->session->userdata('user_id')) ?>">
                                    <?php if (empty($product_details[0]['id'])) { ?>
                                        <div class="form-group col-md-6">
                                            <label for="seller" class="col-form-label">Product Type </label>
                                            <select class='form-control' name='product_type_menu' id="product_type_menu">
                                                <option value="physical_product"> Physical Product </option>
                                                <option value="digital_product"> Digital Product </option>
                                            </select>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="pro_short_description" class="col-form-label">Short Description <span class='text-danger text-sm'>*</span></label>
                                    <textarea type="text" class="form-control" id="short_description" placeholder="Product Short Description" name="short_description"><?= isset($product_details[0]['short_description']) ? output_escaping(str_replace('\r\n', '&#13;&#10;', $product_details[0]['short_description'])) : ""; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <label for="tags">Tags <small>( These tags help you in search result )</small></label>
                                        <input name='tags' class='' id='tags' placeholder="AC, Cooler,Smartphones,etc" value="<?= (isset($product_details[0]['tags']) && !empty($product_details[0]['tags'])) ? $product_details[0]['tags'] : "" ?>" />
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-8">
                                        <div class="row col mt-3">
                                            <div class="col-md-4">
                                                <label for="pro_input_tax" class="col-form-label">Tax</label>
                                                <select class="col-md-12 form-control" name="pro_input_tax">
                                                    <?php if (empty($taxes)) { ?>
                                                        <option value="0" selected> No Taxes Are Added </option>
                                                    <?php } ?>
                                                    <?php foreach ($taxes as $row) {
                                                        if (isset($product_details[0]['tax']) && $product_details[0]['tax'] == $row['id']) {
                                                            $selected = 'selected';
                                                        } else {
                                                            $selected = '';
                                                        }
                                                    ?>
                                                        <option value="<?= $row['id'] ?>" <?= $selected ?>><?= $row['title'] ?><?php echo "(" . $row['percentage'] . "%)" ?></option>
                                                    <?php
                                                    } ?>
                                                </select>

                                            </div>
                                            <div class="col-md-4 indicator <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="indicator" class="col-form-label">Indicator</label>
                                                <select class='form-control' name='indicator'>
                                                    <option value='0' <?= (isset($product_details[0]['indicator']) &&  $product_details[0]['indicator'] == '0') ? 'selected' : ''; ?>>None</option>
                                                    <option value='1' <?= (isset($product_details[0]['indicator']) &&  $product_details[0]['indicator'] == '1') ? 'selected' : ''; ?>>Veg</option>
                                                    <option value='2' <?= (isset($product_details[0]['indicator']) &&  $product_details[0]['indicator'] == '2') ? 'selected' : ''; ?>>Non-Veg</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 made-in-select">
                                                <label for="made_in" class="col-form-label">Made In</label>
                                                <select class="col-md-12 form-control country_list" id="country_list" name="made_in">
                                                    <?php if (isset($product_details[0]['made_in']) && ($product_details[0]['made_in']) != '') {
                                                        // The option was marked selected only when the product's country
                                                        // happened to equal $countries[0] - the first row of the whole
                                                        // countries table - so the saved country was usually rendered as
                                                        // an unselected option and the field looked empty when editing.
                                                    ?>
                                                        <option value="<?= html_escape($product_details[0]['made_in']) ?>" selected><?= html_escape($product_details[0]['made_in']) ?></option>
                                                    <?php } ?>
                                                    <!-- countries display here  -->
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="brand" class="col-form-label">Brand</label>
                                                <select class=" col-md-12  form-control admin_brand_list" id="admin_brand_list" name="brand">
                                                    <?php
                                                    // Same defect as Made In: selected only when the product's brand
                                                    // matched the first row of the brands table.
                                                    if (isset($product_details[0]['brand']) && $product_details[0]['brand'] != '') {
                                                    ?>
                                                        <option value="<?= html_escape($product_details[0]['brand']) ?>" selected><?= html_escape($product_details[0]['brand']) ?></option>
                                                    <?php } ?>
                                                    <!-- brands display here  -->
                                                </select>
                                            </div>
                                            <div class="col-md-4 total_allowed_quantity <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="total_allowed_quantity" class="col-form-label">Total Allowed Quantity</label>
                                                <input type="number" class="col-md-12 form-control" name="total_allowed_quantity" value="<?= (isset($product_details[0]['total_allowed_quantity'])) ? $product_details[0]['total_allowed_quantity'] : ''; ?>" placeholder='Total Allowed Quantity'>
                                            </div>
                                            <div class="col-md-4 minimum_order_quantity <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="minimum_order_quantity" class="col-form-label">Minimum Order Quantity</label>
                                                <input type="number" class="col-md-12 form-control" name="minimum_order_quantity" min="1" value="<?= (isset($product_details[0]['minimum_order_quantity'])) ? $product_details[0]['minimum_order_quantity'] : 1; ?>" placeholder='Minimum Order Quantity'>
                                            </div>
                                            <div class="col-md-4 quantity_step_size <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="quantity_step_size" class="col-form-label">Quantity Step Size</label>
                                                <input type="number" class="col-md-12 form-control" name="quantity_step_size" min="1" value="<?= (isset($product_details[0]['quantity_step_size'])) ? $product_details[0]['quantity_step_size'] : 1; ?>" placeholder='Quantity Step Size'>
                                            </div>
                                            <div class="col-md-4 warranty_period <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="warranty_period" class="col-form-label">Warranty Period</label>
                                                <input type="text" class="col-md-12 form-control" name="warranty_period" value="<?= (isset($product_details[0]['warranty_period'])) ? $product_details[0]['warranty_period'] : "" ?>" placeholder='Warranty Period if any'>
                                            </div>
                                            <div class="col-md-4 guarantee_period <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="guarantee_period" class="col-form-label">Guarantee Period</label>
                                                <input type="text" class="col-md-12 form-control" name="guarantee_period" value="<?= (isset($product_details[0]['guarantee_period'])) ? $product_details[0]['guarantee_period'] : "" ?>" placeholder='Guarantee Period if any'>
                                            </div>
                                            <div class="row col mt-3 deliverable_type <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <div class="col-md-6">
                                                    <label for="zipcode" class="col-form-label">Deliverable Type</label>
                                                    <select class='form-control' name='deliverable_type' id="deliverable_type">
                                                        <option value=<?= NONE ?> <?= (isset($product_details[0]['deliverable_type']) &&  $product_details[0]['deliverable_type'] == NONE) ? 'selected' : ''; ?>>None</option>
                                                        <?php if (!isset($product_details)) { ?>
                                                            <option value=<?= ALL ?> selected>All</option>
                                                        <?php } else { ?>
                                                            <option value=<?= ALL ?> <?= (isset($product_details[0]['deliverable_type']) &&  $product_details[0]['deliverable_type'] == ALL) ? 'selected' : ''; ?>>All</option>
                                                        <?php } ?>
                                                        <option value=<?= INCLUDED ?> <?= (isset($product_details[0]['deliverable_type']) &&  $product_details[0]['deliverable_type'] == INCLUDED) ? 'selected' : ''; ?>>Included</option>
                                                        <option value=<?= EXCLUDED ?> <?= (isset($product_details[0]['deliverable_type']) &&  $product_details[0]['deliverable_type'] == EXCLUDED) ? 'selected' : ''; ?>>Excluded</option>
                                                    </select>
                                                </div>
                                                <?php
                                                $zipcodes = (isset($product_details[0]['deliverable_zipcodes']) &&  $product_details[0]['deliverable_zipcodes'] != NULL) ? explode(",", $product_details[0]['deliverable_zipcodes']) : "";
                                                ?>
                                                <div class="col-md-6">
                                                    <label for="zipcodes" class="col-form-label">Deliverable Zipcodes</label>
                                                    <select name="deliverable_zipcodes[]" class="search_zipcode form-control w-100" multiple onload="multiselect()" id="deliverable_zipcodes" <?= (isset($product_details[0]['deliverable_type']) &&  ($product_details[0]['deliverable_type'] == INCLUDED || $product_details[0]['deliverable_type'] == EXCLUDED))  ? "" : "disabled" ?>>
                                                        <?php if (isset($product_details[0]['deliverable_type']) &&  ($product_details[0]['deliverable_type'] == INCLUDED || $product_details[0]['deliverable_type'] == EXCLUDED)) {
                                                            $zipcodes_name =  fetch_details('zipcodes', "",  'zipcode,id', "", "", "", "", "id", $zipcodes);
                                                            foreach ($zipcodes_name as $row) {
                                                        ?>
                                                                <option value=<?= $row['id'] ?> <?= (in_array($row['id'], $zipcodes)) ? 'selected' : ''; ?>> <?= $row['zipcode'] ?></option>
                                                        <?php }
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>


                                            <!-- HSN Code -->
                                            <div class="col-md-4 col-sm-12 mt-3 hsn_code <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="zipcodes" class="col-form-label">HSN Code</label>
                                                <input type="text" class="col-md-12 form-control" name="hsn_code" value="<?= (isset($product_details[0]['hsn_code'])) ? $product_details[0]['hsn_code'] : "" ?>" placeholder='HSN Code'>
                                            </div>
                                        </div>
                                        <div class="row col mt-3 pickup_locations <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                            <div class="col-md-8 standdard_shipping">
                                                <label for="pickup_location" class="col-form-label">Pickup Location <small>(for standard shipping)</small> <span class='text-danger text-sm'>*</span></label>
                                                <?php
                                                // Admin loads this list over ajax because its seller can change
                                                // mid-form. Here the seller is fixed, so the controller's own
                                                // active pickup locations are rendered straight in.
                                                $current_pickup = (isset($product_details[0]['pickup_location']) && !empty($product_details[0]['pickup_location'])) ? $product_details[0]['pickup_location'] : "";
                                                $seller_pickup_rows = (array) ($pickup_locations ?? []);
                                                ?>
                                                <select class='form-control shiprocket_type' name="pickup_location" id="pickup_location"
                                                    data-selected="<?= html_escape($current_pickup) ?>">
                                                    <option value="">Select Pickup Location</option>
                                                    <?php foreach ($seller_pickup_rows as $row) { ?>
                                                        <option <?= ($row['pickup_location'] == $current_pickup) ? 'selected' : '' ?> value="<?= html_escape($row['pickup_location']); ?>"><?= html_escape($row['pickup_location']); ?></option>
                                                    <?php } ?>
                                                </select>
                                                <small class="text-muted d-block mt-1" id="pickup_hint">
                                                    <?= empty($seller_pickup_rows) ? 'You have no active pickup location yet — add one under Pickup Locations to ship this product.' : '' ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="row col mt-3">
                                            <div class="col-md-3 col-xs-6">
                                                <label for="is_prices_inclusive_tax" class="col-form-label">Tax included in prices?</label>
                                                <input type="checkbox" name="is_prices_inclusive_tax" <?= (isset($product_details[0]['is_prices_inclusive_tax']) && $product_details[0]['is_prices_inclusive_tax'] == '1') ? 'checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                            </div>
                                            <div class="col-md-2 col-xs-6 cod_allowed <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="is_cod_allowed" class="col-form-label">Is COD allowed?</label>
                                                <input type="checkbox" name="cod_allowed" <?= (isset($product_details[0]['cod_allowed']) && $product_details[0]['cod_allowed'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                            </div>
                                            <div class="col-md-2 col-xs-6 is_returnable <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="is_returnable" class="col-form-label">IS Returnable ?</label>
                                                <input type="checkbox" name="is_returnable" <?= (isset($product_details[0]['is_returnable']) && $product_details[0]['is_returnable'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                            </div>
                                            <div class="col-md-2 col-xs-6 is_cancelable <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="is_cancelable" class="col-form-label">Is cancelable ? </label>
                                                <input type="checkbox" name="is_cancelable" id="is_cancelable" class="switch" <?= (isset($product_details[0]['is_cancelable']) && $product_details[0]['is_cancelable'] == '1') ? 'Checked' : ''; ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" data-on-text="Yes" data-off-text="No">
                                            </div>
                                            <div class="col-md-3 col-xs-6 <?= (isset($product_details[0]['is_cancelable']) && $product_details[0]['is_cancelable'] == 1) ? '' : 'collapse' ?>" id='cancelable_till'>
                                                <label for="cancelable_till" class="col-form-label">Till which status ? <span class='text-danger text-sm'>*</span></label>
                                                <select class='form-control' name="cancelable_till">
                                                    <option value='received' <?= (isset($product_details[0]['cancelable_till']) && $product_details[0]['cancelable_till'] == 'received') ? 'selected' : '' ?>>Received</option>
                                                    <option value='processed' <?= (isset($product_details[0]['cancelable_till']) && $product_details[0]['cancelable_till'] == 'processed') ? 'selected' : '' ?>>Processed</option>
                                                    <option value='shipped' <?= (isset($product_details[0]['cancelable_till']) && $product_details[0]['cancelable_till'] == 'shipped') ? 'selected' : '' ?>>Shipped</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row col mt-3">
                                            <div class="col-md-4  is_attachment_required d-flex justify-content-between<?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                <label for="is_attachment_required" class="col-form-label is_attachment_required">Is Attachment Required ?</label>
                                                <a class=" form-switch  mr-1 mb-1" title="Deactivate" href="javascript:void(0)"> <input type="checkbox" class="form-check-input " role="switch" name="is_attachment_required" <?= (isset($product_details[0]['is_attachment_required']) && $product_details[0]['is_attachment_required'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success" /></a>
                                            </div>
                                        </div>

                                        <div class="row col mt-3">

                                            <div class="col pt-4 pb-4">
                                                <div class="section-header"><i class="fas fa-images"></i> Media</div>
                                                <div class="form-group col-sm-12">
                                                    <label for="image">Main Image <span class='text-danger text-sm'>*</span><small>(Recommended Size : 180 x 180 pixels)</small></label>
                                                    <div class='col-md-12'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='pro_input_image' data-isremovable='0' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                                    <?php
                                                    if (isset($product_details[0]['id']) && !empty($product_details[0]['id'])) {
                                                    ?>
                                                        <label class="text-danger mt-3">*Only Choose When Update is necessary</label>
                                                        <div class="container-fluid row image-upload-section">
                                                            <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image ">
                                                                <div class="image-upload-div"><img class="img-fluid mb-2" src="<?= BASE_URL() . $product_details[0]['image'] ?>" alt="Image Not Found"></div>
                                                                <input type="hidden" name="pro_input_image" value='<?= $product_details[0]['image'] ?>'>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    } else { ?>
                                                        <div class="container-fluid row image-upload-section">
                                                            <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                <div class="form-group">
                                                    <label for="other_images">Other Images <small>(Recommended Size : 180 x 180 pixels)</small></label>
                                                    <div class="col-sm-12">
                                                        <div class='col-md-3'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='other_images[]' data-isremovable='1' data-is-multiple-uploads-allowed='1' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                                        <?php
                                                        if (isset($product_details[0]['id']) && !empty($product_details[0]['id'])) {
                                                        ?>
                                                            <div class="container-fluid row image-upload-section">
                                                                <?php
                                                                $other_images = json_decode($product_details[0]['other_images']);
                                                                if (!empty($other_images)) {
                                                                    foreach ($other_images as $row) {
                                                                ?>
                                                                        <div class="col-md-3 col-sm-12 shadow bg-white rounded m-3 p-3 text-center grow">
                                                                            <div class='image-upload-div'><img src="<?= BASE_URL()  . $row ?>" alt="Image Not Found"></div>
                                                                            <a href="javascript:void(0)" class="delete-img-seller m-3" data-id="<?= $product_details[0]['id'] ?>" data-field="other_images" data-img="<?= $row ?>" data-table="products" data-path="<?= $row ?>" data-isjson="true">
                                                                                <span class="btn btn-block bg-gradient-danger btn-xs"><i class="far fa-trash-alt "></i> Delete</span></a>
                                                                            <input type="hidden" name="other_images[]" value='<?= $row ?>'>
                                                                        </div>
                                                                <?php
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                        <?php
                                                        } else { ?>
                                                            <div class="container-fluid row image-upload-section">
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="form-group d-flex">
                                                    <div class="form-group col-md-6">
                                                        <label for="video_type" class="col-form-label">Video Type</label>
                                                        <select class='form-control' name='video_type' id='video_type'>
                                                            <option value='' <?= (isset($product_details[0]['video_type']) && ($product_details[0]['video_type'] == '' || $product_details[0]['video_type'] == NULL)) ? 'selected' : ''; ?>>None</option>
                                                            <option value='self_hosted' <?= (isset($product_details[0]['video_type']) &&  $product_details[0]['video_type'] == 'self_hosted') ? 'selected' : ''; ?>>Self Hosted</option>
                                                            <option value='youtube' <?= (isset($product_details[0]['video_type']) &&  $product_details[0]['video_type'] == 'youtube') ? 'selected' : ''; ?>>Youtube</option>
                                                            <option value='vimeo' <?= (isset($product_details[0]['video_type']) &&  $product_details[0]['video_type'] == 'vimeo') ? 'selected' : ''; ?>>Vimeo</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 <?= (isset($product_details[0]['video_type']) && ($product_details[0]['video_type'] == 'youtube' ||  $product_details[0]['video_type'] == 'vimeo')) ? '' : 'd-none'; ?>" id="video_link_container">
                                                        <label for="video" class="col-form-label">Video Link <span class='text-danger text-sm'>*</span></label>
                                                        <input type="text" class='form-control' name='video' id='video' value="<?= (isset($product_details[0]['video_type']) && ($product_details[0]['video_type'] == 'youtube' || $product_details[0]['video_type'] == 'vimeo')) ? $product_details[0]['video'] : ''; ?>" placeholder="Paste Youtube / Vimeo Video link or URL here">
                                                    </div>
                                                    <div class="col-md-6 mt-2 <?= (isset($product_details[0]['video_type']) && ($product_details[0]['video_type'] == 'self_hosted')) ? '' : 'd-none'; ?>" id="video_media_container">
                                                        <label for="image" class="ml-2">Video <span class='text-danger text-sm'>*</span></label>
                                                        <div class='col-md-3'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='pro_input_video' data-isremovable='1' data-media_type='video' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                                        <?php if (isset($product_details[0]['id']) && !empty($product_details[0]['id']) && isset($product_details[0]['video_type']) &&  $product_details[0]['video_type'] == 'self_hosted') { ?>
                                                            <label class="text-danger mt-3">*Only Choose When Update is necessary</label>
                                                            <div class="container-fluid row image-upload-section ">
                                                                <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image">
                                                                    <div class='image-upload-div'><img class="img-fluid mb-2" src="<?= base_url('assets/admin/images/video-file.png') ?>" alt="Product Video" title="Product Video"></div>
                                                                    <input type="hidden" name="pro_input_video" value='<?= $product_details[0]['video'] ?>'>
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
                                        <div id="attributes_values_json_data" class="d-none">
                                            <select class="select_single" data-placeholder=" Type to search and select attributes">
                                                <option value=""></option>
                                                <?php
                                                foreach ($attributes_refind as $key => $value) {
                                                ?>
                                                    <optgroup label="<?= $key ?>"><?= $key ?>
                                                        <?php foreach ($value as $key => $value) {  ?>
                                                            <option name='<?= $key ?>' value='<?= $key ?>' data-values='<?= json_encode($value, 1) ?>'><?= $key ?></option>
                                                        <?php } ?>
                                                    </optgroup>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label class="col-form-label">Select Category <span class='text-danger text-sm'>*</span></label>
                                        <?php
                                        // Same searchable selector as the admin form. Sellers may list in any
                                        // active category (the categories on their profile are data-collection
                                        // only), so the full tree the controller loaded is used as-is - there is
                                        // no seller to switch between here, and so no ajax reload.
                                        ?>
                                        <div class="category-combo">
                                            <div class="category-search-box">
                                                <i class="fas fa-search category-search-icon"></i>
                                                <input type="text" class="form-control category-search-input"
                                                    id="category_search_input" placeholder="Search category..." autocomplete="off">
                                                <button type="button" class="category-search-clear" id="category_search_clear" aria-label="Clear">&times;</button>
                                            </div>
                                            <div id="category_dropdown"></div>
                                        </div>
                                        <small class="text-muted d-block mt-1" id="category_hint">Search and select a category</small>
                                        <input type="hidden" id="category_tree_data" value='<?= htmlspecialchars(json_encode($categories ?? []), ENT_QUOTES, "UTF-8") ?>'>
                                    </div>
                                    <div class="form-group  col-md-12 mb-3">
                                        <div class="section-header"><i class="fas fa-sliders-h"></i> Pricing, Stock &amp; Variations</div>

                                        <?php
                                        if (isset($product_details)) {
                                            $HideStatus = ((isset($product_details[0]['id']) && $product_details[0]['stock_type'] == NULL) ||  $product_details[0]['type'] == "digital_product") ? 'collapse' : '';
                                        ?>
                                            <div class="col-md-12 row additional-info existing-additional-settings">
                                                <div class="row mt-4 col-md-12 ">
                                                    <nav class="w-100">
                                                        <div class="nav nav-tabs" id="product-tab" role="tablist">
                                                            <a class="nav-item nav-link active" id="tab-for-general-price" data-toggle="tab" href="#general-settings" role="tab" aria-controls="general-price" aria-selected="true">General</a>
                                                            <a class="nav-item nav-link edit-product-attributes" id="tab-for-attributes" data-toggle="tab" href="#product-attributes" role="tab" aria-controls="product-attributes" aria-selected="false">Attributes</a>
                                                            <a class="nav-item nav-link <?= ($product_details[0]['type'] == 'simple_product') ? 'disabled d-none' : 'edit-variants'; ?>" id=" tab-for-variations" data-toggle="tab" href="#product-variants" role="tab" aria-controls="product-variants" aria-selected="false">Variations</a>
                                                        </div>
                                                    </nav>
                                                </div>

                                                <div class="tab-content p-3 col-md-12" id="nav-tabContent">
                                                    <div class="tab-pane fade active show" id="general-settings" role="tabpanel" aria-labelledby="general-settings-tab">
                                                        <div class="form-group">
                                                            <label for="type" class="col-md-12">Type Of Product :</label>
                                                            <div class="col-md-12">
                                                                <?php @$variant_stock_level = !empty($product_details[0]['stock_type']) && $product_details[0]['stock_type'] == '1' ? 'product_level' : 'variant_level' ?>
                                                                <input type="hidden" name="product_type" value="<?= isset($product_details[0]['type']) ? $product_details[0]['type'] : '' ?>">
                                                                <input type="hidden" name="simple_product_stock_status" <?= isset($product_details[0]['stock_type']) && !empty($product_details[0]['stock_type']) && $product_details[0]['type'] == 'simple_product' ? 'value="' . $product_details[0]['stock_type'] . '"'  : '' ?>>
                                                                <input type="hidden" name="variant_stock_level_type" <?= isset($product_details[0]['stock_type']) && !empty($product_details[0]['stock_type']) && $product_details[0]['type'] == 'variable_product' ? 'value="' . $variant_stock_level . '"'  : '' ?>>
                                                                <input type="hidden" name="variant_stock_status" <?= isset($product_details[0]['stock_type']) && !empty($product_details[0]['stock_type']) && $product_details[0]['type'] == 'variable_product' ? 'value="0"'  : '' ?>>
                                                                <select name="type" id="product-type" class="form-control" data-placeholder=" Type to search and select type" <?= isset($product_details[0]['id']) ? 'disabled' : '' ?>>
                                                                    <option value=" ">Select Type</option>
                                                                    <option value="simple_product" <?= ($product_details[0]['type'] == "simple_product") ? 'selected' : '' ?>>Simple Product</option>
                                                                    <option value="variable_product" <?= ($product_details[0]['type'] == "variable_product") ? 'selected' : '' ?>>Variable Product</option>
                                                                    <option value="digital_product" <?= ($product_details[0]['type'] == "digital_product") ? 'selected' : '' ?>>Digital Product</option>
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
                                                                        <label for="type" class="col-md-2">Price:</label>
                                                                        <div class="col-md-12">
                                                                            <input type="number" name="simple_price" class="form-control stock-simple-mustfill-field price" value="<?= $simple_variant['price'] ?? '' ?>" min='0' step="0.01">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="type" class="col-md-2">Special Price:</label>
                                                                        <div class="col-md-12">
                                                                            <input type="number" name="simple_special_price" class="form-control  discounted_price" value="<?= !empty($simple_variant['special_price']) ? $simple_variant['special_price'] : '' ?>" min='0' step="0.01">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group row mt-3 <?= $product_details[0]['type'] == "digital_product" ? 'd-none' : '' ?>" id="product-dimensions">
                                                                        <div class="col-md-6">
                                                                            <label for="weight" class="control-label col-md-12"><small>(These are the product parcel's dimentions.)</small></label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group row  dimensions  <?= $product_details[0]['type'] == "digital_product" ? 'd-none' : '' ?>">
                                                                        <div class="col-3">
                                                                            <label for="weight" class="control-label col-md-12">Weight <small>(kg)</small> <span class='text-danger text-xs'>*</span></label>
                                                                            <input type="number" class="form-control" name="weight" placeholder="Weight" id="weight" value="<?= $simple_variant['weight'] ?? '' ?>" step="0.01">
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <label for="height" class="control-label col-md-12">Height <small>(cms)</small></label>
                                                                            <input type="number" class="form-control" name="height" placeholder="Height" id="height" value="<?= $simple_variant['height'] ?? '' ?>" step="0.01">
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <label for="breadth" class="control-label col-md-12">Breadth <small>(cms)</small> </label>
                                                                            <input type="number" class="form-control" name="breadth" placeholder="Breadth" id="breadth" value="<?= $simple_variant['breadth'] ?? '' ?>" step="0.01">
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <label for="length" class="control-label col-md-12">Length <small>(cms)</small> </label>
                                                                            <input type="number" class="form-control" name="length" placeholder="Length" id="length" value="<?= $simple_variant['length'] ?? '' ?>" step="0.01">
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-group <?= (isset($product_details[0]['type']) && $product_details[0]['type'] == 'digital_product') ? 'd-none' : '' ?>">
                                                                        <div class="col">
                                                                            <input type="checkbox" name="simple_stock_management_status" class="align-middle simple_stock_management_status" <?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] != NULL) ? 'checked' : '' ?>> <span class="align-middle">Enable Stock Management</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group simple-product-level-stock-management <?= $HideStatus ?>">
                                                                    <div class="col col-xs-12">
                                                                        <label class="control-label">SKU :</label>
                                                                        <input type="text" name="product_sku" class="col form-control simple-pro-sku" value="<?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] != NULL) ? $product_details[0]['sku'] : '' ?>">
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
                                                                                <input type="url" class='form-control' name='download_link' id='download_link' value="<?= (isset($product_details[0]['download_type']) && ($product_details[0]['download_type'] == 'add_link')) ? $product_details[0]['download_link'] : ''; ?>" placeholder="Paste digital product link or URL here">
                                                                            </div>
                                                                            <div class="col-md-6 mt-2 <?= (isset($product_details[0]['download_type']) && ($product_details[0]['download_type'] == 'self_hosted')) ? '' : 'd-none'; ?>" id="digital_media_container">
                                                                                <label for="image" class="ml-2">File <span class='text-danger text-sm'>*</span></label>
                                                                                <div class='col-md-3'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='pro_input_zip' data-isremovable='1' data-media_type='archive,document' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
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
                                                                        <a href="javascript:void(0);" class="btn btn-primary save-settings">Save Settings</a>
                                                                        <a href="javascript:void(0);" class="btn btn-warning reset-settings">Reset Settings</a>
                                                                    </div>
                                                                </div>
                                                            <?php } else { ?>
                                                                <div id="variant_stock_level">
                                                                    <div class="form-group">
                                                                        <div class="col">
                                                                            <input type="checkbox" name="variant_stock_management_status" class="align-middle variant_stock_status" <?= (isset($product_details[0]['id']) && $product_details[0]['stock_type'] != NULL) ? 'checked' : '' ?>> <span class="align-middle"> Enable Stock Management</span>
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
                                                                                    <input type="text" name="sku_variant_type" class="col form-control" value="<?= (intval($product_details[0]['stock_type']) == 1 && isset($product_variants[0]['id']) && !empty($product_variants[0]['sku'])) ? $product_variants[0]['sku'] : '' ?>">
                                                                                </div>
                                                                                <div class="col col-xs-12">
                                                                                    <label class="control-label">Total Stock :</label>
                                                                                    <input type="number" min="1" name="total_stock_variant_type" class="col form-control variant-stock-mustfill-field" value="<?= (intval($product_details[0]['stock_type']) == 1 && isset($product_variants[0]['id']) && !empty($product_variants[0]['stock'])) ? $product_variants[0]['stock'] : '' ?>">
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
                                                                            <a href="javascript:void(0);" class="btn btn-primary save-variant-general-settings">Save Settings</a>
                                                                            <a href="javascript:void(0);" class="btn btn-warning reset-settings">Reset Settings</a>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="product-attributes" role="tabpanel" aria-labelledby="product-attributes-tab">
                                                        <div class="info col-12 p-3 d-none" id="note">
                                                            <div class=" col-12 d-flex align-center">
                                                                <strong>Note : </strong>
                                                                <input type="checkbox" checked="" class="ml-3 my-auto custom-checkbox" disabled>
                                                                <span class="ml-3">check if the attribute is to be used for variation </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <a href="javascript:void(0);" id="add_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm">Add Attributes</a>
                                                            <a href="javascript:void(0);" id="save_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm d-none">Save Attributes</a>
                                                        </div>
                                                        <div class="clearfix"></div>

                                                        <div id="attributes_process">
                                                            <div class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-attributes-added">
                                                                <div class="col-md-12 text-center">No Product attributes Are Added ! </div>
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
                                                            <div class="col-md-12 text-center"> No Product Variations Are Added ! </div>
                                                        </div>
                                                        <div id="variants_process" class="ui-sortable">

                                                            <div class="form-group move row my-auto p-2 border rounded bg-gray-light product-variant-selectbox">
                                                                <div class="col-1 text-center my-auto">
                                                                    <i class="fas fa-sort"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                            } else {

                                                ?>
                                                    <div class="col-12 row additional-info existing-additional-settings">
                                                        <div class="row mt-4 col-md-12 ">
                                                            <nav class="w-100">
                                                                <div class="nav nav-tabs" id="product-tab" role="tablist"> <a class="nav-item nav-link active" id="tab-for-general-price" data-toggle="tab" href="#general-settings" role="tab" aria-controls="general-price" aria-selected="true">General</a> <a class="nav-item nav-link disabled product-attributes" id="tab-for-attributes" data-toggle="tab" href="#product-attributes" role="tab" aria-controls="product-attributes" aria-selected="false">Attributes</a> <a class="nav-item nav-link disabled product-variants d-none" id="tab-for-variations" data-toggle="tab" href="#product-variants" role="tab" aria-controls="product-variants" aria-selected="false">Variations</a>
                                                                </div>
                                                            </nav>
                                                            <div class="tab-content p-3 col-md-12" id="nav-tabContent">
                                                                <div class="tab-pane fade active show" id="general-settings" role="tabpanel" aria-labelledby="general-settings-tab">
                                                                    <div class="form-group">
                                                                        <label for="type" class="col-md-12">Type Of Product :</label>
                                                                        <div class="col-md-12">
                                                                            <input type="hidden" name="product_type">
                                                                            <input type="hidden" name="simple_product_stock_status">
                                                                            <input type="hidden" name="variant_stock_level_type">
                                                                            <input type="hidden" name="variant_stock_status">
                                                                            <select name="type" id="product-type" class="form-control product-type" data-placeholder=" Type to search and select type">
                                                                                <option value=" ">Select Type</option>
                                                                                <option value="simple_product">Simple Product</option>
                                                                                <option value="variable_product">Variable Product</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div id="product-general-settings">
                                                                        <div id="general_price_section" class="collapse">
                                                                            <div class="form-group">
                                                                                <label for="type" class="col-md-2">Price:</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="number" name="simple_price" class="form-control stock-simple-mustfill-field price" min='0' step="0.01">
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group">
                                                                                <label for="type" class="col-md-2">Special Price:</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="number" name="simple_special_price" class="form-control discounted_price" min='0' step="0.01">
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group row mt-3" id="product-dimensions">
                                                                                <div class="col-md-6">
                                                                                    <label for="weight" class="control-label col-md-12"><small>(These are the product parcel's dimentions.)</small></label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-group row" id="product-dimensions">
                                                                                <div class="col-3">
                                                                                    <label for="weight" class="control-label col-md-12">Weight <small>(kg)</small> <span class='text-danger text-xs'>*</span></label>
                                                                                    <input type="number" class="form-control" name="weight" placeholder="Weight" id="weight" value="" step="0.01">
                                                                                </div>
                                                                                <div class="col-3">
                                                                                    <label for="height" class="control-label col-md-12">Height <small>(cms)</small></label>
                                                                                    <input type="number" class="form-control" name="height" placeholder="Height" id="height" value="" step="0.01">
                                                                                </div>
                                                                                <div class="col-3">
                                                                                    <label for="breadth" class="control-label col-md-12">Breadth <small>(cms)</small></label>
                                                                                    <input type="number" class="form-control" name="breadth" placeholder="Breadth" id="breadth" value="" step="0.01">
                                                                                </div>
                                                                                <div class="col-3">
                                                                                    <label for="length" class="control-label col-md-12">Length <small>(cms)</small></label>
                                                                                    <input type="number" class="form-control" name="length" placeholder="Length" id="length" value="" step="0.01">
                                                                                </div>
                                                                            </div>

                                                                            <div class="form-group  simple_stock_management">
                                                                                <div class="col">
                                                                                    <input type="checkbox" name="simple_stock_management_status" class="align-middle simple_stock_management_status"> <span class="align-middle">Enable Stock Management</span>
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
                                                                            <div class="col"> <a href="javascript:void(0);" class="btn btn-primary save-settings">Save Settings</a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div id="variant_stock_level" class="collapse">
                                                                        <div class="form-group">
                                                                            <div class="col">
                                                                                <input type="checkbox" name="variant_stock_management_status" class="align-middle variant_stock_status"> <span class="align-middle"> Enable Stock Management</span>
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
                                                                            <div class="col"> <a href="javascript:void(0);" class="btn btn-primary save-variant-general-settings">Save Settings</a>
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
                                                                                <div class='col-md-3'><a class="uploadFile img btn btn-primary text-white btn-sm" data-input='pro_input_zip' data-isremovable='1' data-media_type='archive,document' data-is-multiple-uploads-allowed='0' data-toggle="modal" data-target="#media-upload-modal" value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                                                                <div class="container-fluid row image-upload-section">
                                                                                    <div class="col-md-3 col-sm-12 shadow p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="form-group mt-3 ml-2">
                                                                                <div class="col"> <a href="javascript:void(0);" class="btn btn-primary save-digital-product-settings">Save Settings</a></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                                <div class="tab-pane fade" id="product-attributes" role="tabpanel" aria-labelledby="product-attributes-tab">
                                                                    <div class="info col-12 p-3 d-none" id="note">
                                                                        <div class=" col-12 d-flex align-center"> <strong>Note : </strong>
                                                                            <input type="checkbox" checked="checked" class="ml-3 my-auto custom-checkbox" disabled> <span class="ml-3">check if the attribute is to be used for variation </span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-12"> 
                                                                        <a href="javascript:void(0);" id="add_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm">Add Attributes</a> 
                                                                        <a href="javascript:void(0);" id="save_attributes" class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm d-none">Save Attributes</a>
                                                                    </div>
                                                                    <div class="clearfix"></div>
                                                                    <div id="attributes_process">
                                                                        <div class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-attributes-added">
                                                                            <div class="col-md-12 text-center">No Product Attribures Are Added !</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="tab-pane fade" id="product-variants" role="tabpanel" aria-labelledby="product-variants-tab">
                                                                    <div class="clearfix"></div>
                                                                    <div class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-variants-added">
                                                                        <div class="col-md-12 text-center">No Product Variations Are Added !</div>
                                                                    </div>
                                                                    <div id="variants_process" class="ui-sortable"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                            }
                                                ?>
                                                </div>
                                            </div>
                                    </div>
                                    <div class="card-body pad">
                                        <div class="section-header"><i class="fas fa-align-left"></i> Description</div>
                                        <div class="form-group col-md-12">
                                            <label for="pro_input_description">Description </label>
                                            <div class="mb-3">
                                                <textarea name="pro_input_description" class="textarea addr_editor" placeholder="Place some text here"><?= (isset($product_details[0]['id'])) ? output_escaping(str_replace('\r\n', '&#13;&#10;', $product_details[0]['description'])) : ''; ?></textarea>
                                            </div>
                                            <label for="pro_input_description">Extra Description </label>
                                            <div class="mb-3">
                                                <textarea name="extra_input_description" class="textarea addr_editor" placeholder="Place some text here"><?= (isset($product_details[0]['id'])) ? output_escaping(str_replace('\r\n', '&#13;&#10;', $product_details[0]['extra_description'])) : ''; ?></textarea>
                                            </div>
                                            <div class="d-flex justify-content-center">
                                                <div class="form-group" id="error_box">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <button type="reset" class="btn btn-warning">Reset</button>
                                                <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($product_details[0]['id'])) ? 'Update Product' : 'Add Product' ?></button>
                                            </div>
                                        </div>
                                    </div>

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

<style>
    .seller-create-product-page .text-primary-theme { color: var(--color-orange); }

    .seller-create-product-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }

    .seller-create-product-page .section-header {
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
    .seller-create-product-page .card-body > .section-header:first-child { margin-top: 0; }
    .seller-create-product-page .section-header i { color: var(--color-orange); }

    .seller-create-product-page label.col-form-label,
    .seller-create-product-page label.control-label { font-weight: 600; font-size: 14px; }
    .seller-create-product-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem rgba(242,130,46,.18);
    }
    .seller-create-product-page .nav-tabs .nav-link.active {
        color: var(--color-orange-dark);
        border-bottom: 2px solid var(--color-orange);
        font-weight: 600;
    }
    .seller-create-product-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
        font-weight: 600;
    }
    .seller-create-product-page .btn-success:hover:not(:disabled) { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }

    /* ---- searchable category selector ---- */
    .seller-create-product-page .category-combo { position: relative; }
    .seller-create-product-page .category-search-box { position: relative; }
    .seller-create-product-page .category-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-grey);
        font-size: 13px;
        pointer-events: none;
    }
    .seller-create-product-page .category-search-input { padding-left: 32px; padding-right: 30px; }
    .seller-create-product-page .category-search-clear {
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
    .seller-create-product-page .category-search-clear:hover { color: var(--color-theme-red); }
    .seller-create-product-page #category_dropdown {
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
    .seller-create-product-page .category-result-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .seller-create-product-page .category-result-item:last-child { border-bottom: none; }
    .seller-create-product-page .category-result-item:hover { background: var(--color-orange-light); }
    .seller-create-product-page .category-result-name { font-weight: 600; font-size: 14px; }
    .seller-create-product-page .category-result-path { font-size: 12px; color: var(--color-grey); }
    .seller-create-product-page .category-empty { padding: 10px 12px; color: var(--color-grey); font-size: 13px; }
</style>

<script>
$(document).ready(function () {
    // ------------------------------------------------------------------
    // Searchable category selector.
    //
    // Identical to the admin form's control, minus its per-seller reload: this panel
    // only ever lists categories for the logged-in seller.
    // ------------------------------------------------------------------
    var $input    = $('#category_search_input');
    var $dropdown = $('#category_dropdown');
    var $clearBtn = $('#category_search_clear');
    var $selected = $('#product_category_id');
    var $hint     = $('#category_hint');

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

        // Keep the saved selection only if that category still exists.
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
            $hint.text('No categories are available yet.');
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
                allCategories.length ? 'No category matches that search' : 'No categories available'
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

    // The seller is fixed on this panel, so the tree the controller rendered is the
    // whole list - no per-seller reload, which is the only part of the admin version
    // that does not apply here.
    var seed = [];
    try { seed = JSON.parse($('#category_tree_data').val() || '[]'); } catch (e) { seed = []; }
    setCategories(seed);

    // ------------------------------------------------------------------
    // Deleting a saved image.
    //
    // custom.js binds '.delete-img' to admin/home/delete_image, an endpoint a seller
    // is not authorized for - hence the distinct class on this page's markup, handled
    // against the seller's own endpoint. Behaviour is otherwise identical.
    // ------------------------------------------------------------------
    $(document).on('click', '.delete-img-seller', function () {
        var $trigger = $(this);
        Swal.fire({
            title: 'Are You Sure!',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return $.ajax({
                    type: 'POST',
                    url: "<?= base_url('seller/home/delete_image') ?>",
                    data: {
                        id: $trigger.data('id'),
                        path: $trigger.data('path'),
                        field: $trigger.data('field'),
                        img_name: $trigger.data('img'),
                        table_name: $trigger.data('table'),
                        isjson: $trigger.data('isjson'),
                        [csrfName]: csrfHash
                    },
                    dataType: 'json'
                }).then(function (result) {
                    csrfName = result['csrfName'];
                    csrfHash = result['csrfHash'];
                    if (result['is_deleted'] == true) {
                        $trigger.closest('div').remove();
                        Swal.fire('Success', 'Media Deleted !', 'success');
                    } else {
                        Swal.fire('Oops...', result['message'] || 'Something went wrong!', 'error');
                    }
                });
            },
            allowOutsideClick: false
        });
    });

    // custom.js binds the media modal's Upload button directly to the element, and
    // seller/include-footer.php binds its own delegated handler for the same button.
    // Both would fire and each picked file would be inserted twice; the footer's is the
    // one written against this panel's modal markup, so custom.js's copy is dropped.
    // This runs on ready, after custom.js has bound at parse time.
    $('#upload-media').off('click');
});
</script>