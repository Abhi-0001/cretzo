<?php
// Add-Seller mode has no record to prefill from. Most fields already guard themselves, but
// the ones passed into render_admin_doc_field() below need a real array to receive.
$fetched_data = isset($fetched_data) && is_array($fetched_data) ? $fetched_data : [];
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4><?= (isset($fetched_data[0]['id'])) ? 'Update Seller' : 'Add Seller' ?></h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Seller</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <?php // The "Categories & Commission(%)" modal that lived here has been removed
                      // along with its trigger button. It saved per-category rates into the
                      // seller_commission table, which nothing reads: commission comes from the
                      // seller's subscription plan slabs. Keeping a working-looking form that
                      // wrote to a table no calculation consults was worse than not having it. ?>

                <div class="category-picker-modal" id="category_picker_modal">
                    <div class="category-picker-content">
                        <div class="category-picker-header d-flex justify-content-between align-items-center">
                            <strong>Select Secondary Categories</strong>
                            <button type="button" class="category-picker-close" id="close_category_picker_btn" aria-label="Close">&times;</button>
                        </div>
                        <div class="category-picker-list" id="category_picker_list">
                            <?php foreach ($all_categories as $cat): ?>
                                <?php if ((int) $cat['parent_id'] === 0) continue; // only sub-categories are selectable as "secondary" ?>
                                <label class="category-picker-item" data-parent="<?= (int) $cat['parent_id'] ?>">
                                    <input type="checkbox" class="secondary-category-checkbox" value="<?= $cat['id'] ?>">
                                    <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php endforeach; ?>
                            <p class="text-muted mb-0" id="category_picker_empty_msg" style="display:none;">Please select a Primary Product Category first.</p>
                        </div>
                        <div class="category-picker-footer">
                            <button type="button" class="btn-add-categories" id="cancel_category_picker_btn">Cancel</button>
                            <button type="button" class="btn-upload-logo" id="done_category_picker_btn">Done</button>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card card-info">
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/sellers/add_seller'); ?>" method="POST" enctype="multipart/form-data" id="add_product_form">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" name="edit_seller" value="<?= html_escape($fetched_data[0]['user_id']) ?>">
                                <input type="hidden" name="edit_seller_data_id" value="<?= html_escape($fetched_data[0]['id']) ?>">
                            <?php
                            } ?>
                            <div class="card-body">
                                <textarea cols="20" rows="20" id="cat_data" name="commission_data" style="display:none;"></textarea>

                                <h4>Personal / Account Details</h4>
                                <hr>

                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Seller Photo</label>
                                    <div class="col-sm-10 d-flex align-items-center gap-3">
                                        <input type="file" class="hidden" name="seller_photo" id="personalPhotoInput" accept="image/*,application/pdf" style="display:none;">
                                        <input type="hidden" name="old_seller_photo" value="<?= html_escape(htmlspecialchars($fetched_data[0]['image'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                        <?php
                                            /* Reuses get_user_avatar_url() (function_helper) instead of
                                               base_url(USER_IMG_PATH . image): `users`.`image` can name a file that is
                                               no longer on disk, and the raw concatenation rendered that as a broken
                                               image. '' means "no usable photo", which is what drives both the
                                               placeholder icon and whether the thumbnail is clickable below. */
                                            $seller_photo_url = get_user_avatar_url($fetched_data[0]['image'] ?? '');
                                            $has_seller_photo = ($seller_photo_url !== '');
                                        ?>
                                        <div class="personal-photo-preview" id="personalPhotoContainer">
                                            <svg class="personal-photo-icon" id="personalPhotoIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="<?= $has_seller_photo ? 'display:none;' : '' ?>">
                                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />
                                            </svg>
                                            <?php /* With a photo on file the thumbnail is a lightbox link so it opens the
                                                     full-size image, instead of the file dialog it used to open - uploading
                                                     is the button beside it. data-toggle="lightbox" is picked up by the
                                                     global ekko-lightbox handler in assets/admin/custom/custom.js, the same
                                                     way the seller document previews further down this form work. Its own
                                                     gallery name keeps the arrows from wandering into those documents. */ ?>
                                            <a href="<?= $seller_photo_url ?>" id="personalPhotoLink" class="personal-photo-link<?= $has_seller_photo ? '' : ' d-none' ?>"
                                               <?= $has_seller_photo ? 'data-toggle="lightbox" data-gallery="seller_photo" data-title="Seller Photo"' : '' ?>>
                                                <img id="personalPhotoPreview" src="<?= $seller_photo_url ?>" alt="Seller photo" class="<?= $has_seller_photo ? '' : 'hidden' ?>" style="<?= $has_seller_photo ? '' : 'display:none;' ?>">
                                            </a>
                                        </div>
                                        <label for="personalPhotoInput" class="btn btn-outline-primary btn-sm mb-0">Upload Photo</label>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="first_name" class="col-sm-2 col-form-label">First Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="first_name" placeholder="First Name" name="first_name" value="<?= html_escape(@$fetched_data[0]['first_name']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="middle_name" class="col-sm-2 col-form-label">Middle Name</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="middle_name" placeholder="Middle Name (optional)" name="middle_name" value="<?= html_escape(@$fetched_data[0]['middle_name']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="last_name" class="col-sm-2 col-form-label">Last Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="last_name" placeholder="Last Name" name="last_name" value="<?= html_escape(@$fetched_data[0]['last_name']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="phone" class="col-sm-2 col-form-label">Phone <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="phone" placeholder="Enter Phone Number" name="phone" maxlength="10" value="<?= html_escape(@$fetched_data[0]['phone']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="email" class="col-sm-2 col-form-label">Email <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="email" class="form-control" id="email" placeholder="Enter Email" name="email" value="<?= html_escape(@$fetched_data[0]['email']) ?>">
                                    </div>
                                </div>
                                <?php if (!isset($fetched_data[0]['id'])) { ?>
                                    <div class="form-group row ">
                                        <label for="password" class="col-sm-2 col-form-label">Password <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-sm-10">
                                            <input type="password" class="form-control" id="password" placeholder="Enter Password" name="password">
                                        </div>
                                    </div>
                                    <div class="form-group row ">
                                        <label for="confirm_password" class="col-sm-2 col-form-label">Confirm Password <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-sm-10">
                                            <input type="password" class="form-control" id="confirm_password" placeholder="Enter Confirm Password" name="confirm_password">
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class="form-group row">
                                    <label for="address1" class="col-sm-2 col-form-label">Address <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="address1" placeholder="Street" name="address1" value="<?= html_escape(@$fetched_data[0]['address1']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pin" class="col-sm-2 col-form-label">PIN Code <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pin" placeholder="Enter PIN Code" name="pin" maxlength="6" value="<?= html_escape(@$fetched_data[0]['pin']) ?>">
                                        <span id="pin_lookup_status" class="pincode-lookup-status"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="state" class="col-sm-2 col-form-label">State <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="state" placeholder="Enter State" name="state" value="<?= html_escape(htmlspecialchars($fetched_data[0]['state'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="district" class="col-sm-2 col-form-label">District <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="district" placeholder="Enter District" name="district" value="<?= html_escape(htmlspecialchars($fetched_data[0]['district'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="city" class="col-sm-2 col-form-label">City/Village/Town <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="city" placeholder="Enter City/Village/Town" name="city" value="<?= html_escape(htmlspecialchars($fetched_data[0]['city'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>

                                <?php
                                // $db_column defaults to $field, but store_logo's underlying seller_data
                                // column is named "logo" (Seller_model::add_seller() remaps
                                // store_logo -> logo) - without this override the existing logo would
                                // never preview and old_store_logo would always post back empty,
                                // wiping the seller's logo on every save that doesn't re-upload one.
                                function render_admin_doc_field($field, $fetched_data, $label, $required = true, $db_column = null) {
                                    $db_column = $db_column ?? $field;
                                    $value = $fetched_data[0][$db_column] ?? '';
                                    $is_pdf = !empty($value) && preg_match('/\.pdf$/i', $value);
                                ?>
                                    <div class="form-group row" id="<?= $field ?>_field">
                                        <label for="<?= $field ?>_input" class="col-sm-2 col-form-label"><?= $label ?><?= $required ? " <span class='text-danger text-sm'>*</span>" : '' ?></label>
                                        <div class="col-sm-10">
                                            <input type="file" class="form-control" name="<?= $field ?>" id="<?= $field ?>_input" accept="image/*,application/pdf">
                                            <input type="hidden" name="old_<?= $field ?>" value="<?= html_escape(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) ?>">
                                            <div class="doc-upload-preview-wrap<?= empty($value) ? ' hidden' : '' ?>" id="<?= $field ?>_wrap">
                                                <a href="<?= (!empty($value) && !$is_pdf) ? base_url($value) : '' ?>" target="_blank" id="<?= $field ?>_link" data-toggle="lightbox" data-gallery="gallery_seller">
                                                    <img id="<?= $field ?>_preview" src="<?= (!empty($value) && !$is_pdf) ? base_url($value) : '' ?>" class="doc-upload-thumb<?= ($is_pdf || empty($value)) ? ' hidden' : '' ?>" alt="<?= $label ?>">
                                                </a>
                                                <button type="button" class="doc-remove-btn" data-target="<?= $field ?>" aria-label="Remove <?= $label ?>">&times;</button>
                                            </div>
                                            <small class="doc-upload-hint" id="<?= $field ?>_hint" style="<?= empty($value) ? 'display:none;' : '' ?>">Current file on record. Leave blank if there is no change.</small>
                                        </div>
                                    </div>
                                <?php
                                }
                                render_admin_doc_field('national_identity_card', $fetched_data, 'Identity Proof');
                                render_admin_doc_field('authorized_signature', $fetched_data, 'Authorized Signatory');
                                ?>

                                <h4>Store Details</h4>
                                <hr>
                                <?php render_admin_doc_field('store_logo', $fetched_data, 'Store Logo', false, 'logo'); ?>
                                <div class="form-group row">
                                    <label for="shop_name" class="col-sm-2 col-form-label">Shop Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="shop_name" placeholder="Shop Name" name="shop_name" value="<?= html_escape(@$fetched_data[0]['shop_name']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="social" class="col-sm-2 col-form-label">Social Media Handle</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="social" placeholder="Enter Social Media" name="social" value="<?= html_escape(@$fetched_data[0]['social']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="shop_phone" class="col-sm-2 col-form-label">Shop Phone Number <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="shop_phone" placeholder="Enter Shop Phone Number" name="shop_phone" maxlength="10" value="<?= html_escape(@$fetched_data[0]['shop_phone']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pickup_address1" class="col-sm-2 col-form-label">Pickup Address Line 1 <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pickup_address1" placeholder="Address Line 1" name="pickup_address1" value="<?= html_escape(@$fetched_data[0]['pickup_address1']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pickup_address2" class="col-sm-2 col-form-label">Pickup Address Line 2</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pickup_address2" placeholder="Address Line 2" name="pickup_address2" value="<?= html_escape(@$fetched_data[0]['pickup_address2']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pickup_pin" class="col-sm-2 col-form-label">Pickup PIN Code <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pickup_pin" placeholder="Enter PIN Code" name="pickup_pin" maxlength="6" value="<?= html_escape(@$fetched_data[0]['pickup_pin']) ?>">
                                        <span id="pickup_pin_lookup_status" class="pincode-lookup-status"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pickup_state" class="col-sm-2 col-form-label">Pickup State</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pickup_state" placeholder="Enter State" name="pickup_state" value="<?= html_escape(htmlspecialchars($fetched_data[0]['pickup_state'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pickup_district" class="col-sm-2 col-form-label">Pickup District</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pickup_district" placeholder="Enter District" name="pickup_district" value="<?= html_escape(htmlspecialchars($fetched_data[0]['pickup_district'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pickup_city" class="col-sm-2 col-form-label">Pickup City</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pickup_city" placeholder="Enter City" name="pickup_city" value="<?= html_escape(htmlspecialchars($fetched_data[0]['pickup_city'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="slug_input" class="col-sm-2 col-form-label">Store URL</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="slug_input" name="slug" placeholder="<?= !empty($fetched_data[0]['shop_name']) ? htmlspecialchars($fetched_data[0]['shop_name'], ENT_QUOTES, 'UTF-8') : 'your-shop-name' ?>" value="<?= html_escape(htmlspecialchars($fetched_data[0]['slug'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="store_description" class="col-sm-2 col-form-label">Store Description</label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" id="store_description" placeholder="Tell customers about the store..." name="store_description"><?= htmlspecialchars($fetched_data[0]['store_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="primary_category_id" class="col-sm-2 col-form-label">Primary Product Category <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <select name="primary_category_id" id="primary_category_id" class="form-control">
                                            <option value="">Select a category</option>
                                            <?php foreach ($all_categories as $cat): ?>
                                                <?php if ((int) $cat['parent_id'] !== 0) continue; ?>
                                                <option value="<?= $cat['id'] ?>" <?= (isset($fetched_data[0]['primary_category_id']) && (string) $fetched_data[0]['primary_category_id'] === (string) $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Secondary Categories <small class="text-muted">(optional)</small></label>
                                    <div class="col-sm-10">
                                        <small class="text-muted d-block mb-1">Choose a Primary Product Category first — only its sub-categories can be added here.</small>
                                        <div id="secondary_category_pills"></div>
                                        <button type="button" class="btn-add-categories" id="open_category_picker_btn">+ Add Categories</button>
                                        <input type="hidden" name="secondary_category_ids" id="secondary_category_ids_hidden" value="<?= html_escape(htmlspecialchars($fetched_data[0]['category_ids'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>

                                <h4>Business / Legal Details</h4>
                                <hr>
                                <div class="form-group row">
                                    <label for="entity_type" class="col-sm-2 col-form-label">Entity Type <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <?php $selected_entity_type = $fetched_data[0]['entity_type'] ?? 'individual'; ?>
                                        <select name="entity_type" class="form-control" id="entity_type">
                                            <option value="individual" <?= $selected_entity_type === 'individual' ? 'selected' : '' ?>>Individual</option>
                                            <option value="sole_proprietorship" <?= $selected_entity_type === 'sole_proprietorship' ? 'selected' : '' ?>>Sole Proprietorship</option>
                                            <option value="partnership_firm" <?= $selected_entity_type === 'partnership_firm' ? 'selected' : '' ?>>Partnership Firm</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="legal_business_name_input" class="col-sm-2 col-form-label" id="legal_business_name_label">Legal Business Name</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="legal_business_name_input" name="legal_business_name" placeholder="Legal Business Name" value="<?= html_escape(htmlspecialchars($fetched_data[0]['legal_business_name'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pan" class="col-sm-2 col-form-label" id="pan_label">PAN Number <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pan" placeholder="Enter PAN Number" name="pan" maxlength="10" value="<?= html_escape(@$fetched_data[0]['pan']) ?>">
                                    </div>
                                </div>
                                <?php render_admin_doc_field('pan_card_document', $fetched_data, 'Upload PAN Card'); ?>

                                <h5 class="mt-3">Business Address</h5>
                                <div class="form-group row">
                                    <label for="business_address1" class="col-sm-2 col-form-label">Address Line 1 <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="business_address1" placeholder="Street 1" name="business_address1" value="<?= html_escape(htmlspecialchars($fetched_data[0]['business_address1'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="business_address2" class="col-sm-2 col-form-label">Address Line 2</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="business_address2" placeholder="Street 2" name="business_address2" value="<?= html_escape(htmlspecialchars($fetched_data[0]['business_address2'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="business_pin" class="col-sm-2 col-form-label">PIN Code <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="business_pin" placeholder="Enter PIN Code" name="business_pin" maxlength="6" value="<?= html_escape(htmlspecialchars($fetched_data[0]['business_pin'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                        <span id="business_pin_lookup_status" class="pincode-lookup-status"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="business_state" class="col-sm-2 col-form-label">State <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="business_state" placeholder="Enter State" name="business_state" value="<?= html_escape(htmlspecialchars($fetched_data[0]['business_state'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="business_district" class="col-sm-2 col-form-label">District <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="business_district" placeholder="Enter District" name="business_district" value="<?= html_escape(htmlspecialchars($fetched_data[0]['business_district'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="business_city" class="col-sm-2 col-form-label">City/Village/Town <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="business_city" placeholder="Enter City/Village/Town" name="business_city" value="<?= html_escape(htmlspecialchars($fetched_data[0]['business_city'] ?? '', ENT_QUOTES, 'UTF-8')) ?>">
                                    </div>
                                </div>

                                <?php
                                $is_gst_registered = isset($fetched_data[0]['is_gst_registered']) ? $fetched_data[0]['is_gst_registered'] : 1;
                                $gst_enrollment_number = isset($fetched_data[0]['gst_enrollment_number']) ? $fetched_data[0]['gst_enrollment_number'] : '';
                                $is_non_gst = ($is_gst_registered == 0);
                                ?>
                                <div class="form-group row" id="gst_number_div" style="<?= $is_non_gst ? 'display:none;' : '' ?>">
                                    <label for="gst" class="col-sm-2 col-form-label">GST Number <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="gst" placeholder="22ABCDE0000A1Z5" name="gst" maxlength="15" value="<?= html_escape(@$fetched_data[0]['gst']) ?>">
                                    </div>
                                </div>
                                <?php render_admin_doc_field('gstin_document', $fetched_data, 'Upload GSTIN PDF'); ?>
                                <div class="form-group row" id="gst_enrollment_div" style="<?= $is_non_gst ? '' : 'display:none;' ?>">
                                    <label for="gst_enrollment_number" class="col-sm-2 col-form-label">GST Enrollment ID <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="gst_enrollment_number" placeholder="Enter GST Enrollment ID" name="gst_enrollment_number" maxlength="64" value="<?= html_escape($gst_enrollment_number) ?>">
                                        <small class="text-muted d-block mt-1">Seller can sell only within their own state (as per government regulation).</small>
                                    </div>
                                </div>
                                <?php render_admin_doc_field('gst_enrollment_ack_document', $fetched_data, 'Upload GST Enrollment ID Acknowledgement Slip'); ?>

                                <div id="partnership_deed_section" style="display:none;">
                                    <?php render_admin_doc_field('partnership_deed_document', $fetched_data, 'Upload Partnership Deed'); ?>
                                </div>
                                <div id="business_proof_section" style="display:none;">
                                    <?php
                                    render_admin_doc_field('business_proof_document', $fetched_data, 'Business Proof');
                                    render_admin_doc_field('business_address_proof_document', $fetched_data, 'Business Address Proof (electricity bill, rent/lease agreement, or bank statement)');
                                    ?>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Not GST Registered?</label>
                                    <div class="col-sm-10">
                                        <input type="checkbox" id="gst_check" name="gst_check" value="1" <?= $is_non_gst ? 'checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                </div>

                                <h4>Bank / Account Details</h4>
                                <hr>
                                <div class="form-group row">
                                    <label for="account_number" class="col-sm-2 col-form-label">Account Number <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="account_number" placeholder="Account Number" name="account_number" maxlength="18" value="<?= html_escape(@$fetched_data[0]['account_number']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="confirm_account_number" class="col-sm-2 col-form-label">Confirm Account Number <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="confirm_account_number" placeholder="Confirm Account Number" name="confirm_account_number" maxlength="18" value="<?= html_escape(@$fetched_data[0]['account_number']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="account_holder_name" class="col-sm-2 col-form-label">Account Holder Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="account_holder_name" placeholder="Account Holder Name" name="account_holder_name" value="<?= html_escape(@$fetched_data[0]['account_holder_name']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="ifsc" class="col-sm-2 col-form-label">IFSC Code <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="ifsc" placeholder="Enter IFSC Code" name="ifsc" maxlength="11" value="<?= html_escape(@$fetched_data[0]['ifsc']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="branch" class="col-sm-2 col-form-label">Branch Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="branch" placeholder="Enter Branch" name="branch" value="<?= html_escape(@$fetched_data[0]['branch']) ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="bank_name_hidden" class="col-sm-2 col-form-label">Bank Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10" style="position:relative;">
                                        <?php if (!empty($indian_banks)): ?>
                                            <input type="text" id="bank_search" class="form-control" placeholder="Search Bank Name..." autocomplete="off">
                                            <input type="hidden" name="bank_name" id="bank_name_hidden" value="<?= html_escape(@$fetched_data[0]['bank_name']) ?>">
                                            <div id="bank_dropdown" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; background:#fff; position:absolute; z-index:999; width:100%;"></div>
                                        <?php else: ?>
                                            <input type="text" name="bank_name" id="bank_name_hidden" class="form-control" placeholder="Enter Bank Name" value="<?= html_escape(@$fetched_data[0]['bank_name']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php render_admin_doc_field('bank_account_proof_document', $fetched_data, 'Bank Account Proof (passbook, statement, or cancelled cheque)', false); ?>

                                <h4>Admin Controls</h4>
                                <hr>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Status <span class='text-danger text-sm'>*</span></label>
                                    <div id="status" class="btn-group col-sm-4">
                                        <label class="btn btn-default" data-toggle-class="btn-default" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="0" <?= (isset($fetched_data[0]['status']) && $fetched_data[0]['status'] == '0') ? 'Checked' : '' ?>> Deactive
                                        </label>
                                        <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="1" <?= (isset($fetched_data[0]['status']) && $fetched_data[0]['status'] == '1') ? 'Checked' : '' ?>> Approved
                                        </label>
                                        <label class="btn btn-danger" data-toggle-class="btn-danger" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="2" <?= (!isset($fetched_data[0]['status']) || $fetched_data[0]['status'] == '2') ? 'Checked' : '' ?>> Not-Approved
                                        </label>
                                    </div>
                                </div>
                                <?php
                                // The global "Commission(%)" input and the per-category commission
                                // picker that used to sit here wrote to seller_data.commission and the
                                // seller_commission table - neither of which is read by anything that
                                // computes money any more. Commission is taken from the seller's
                                // SUBSCRIPTION PLAN slabs (subscriptions.commission_first50 /
                                // commission_51_100 / commission_after100), see
                                // Seller_model::settle_seller_commission(). Leaving the old fields on
                                // the form meant an admin could carefully set a rate here and see it
                                // saved, while every settlement quietly ignored it. Replaced with a
                                // pointer to where the rate actually lives.
                                $plan_row = null;
                                if (isset($fetched_data[0]['user_id']) && !empty($fetched_data[0]['user_id'])) {
                                    $CI = &get_instance();
                                    $CI->load->model('Seller_subscription_model');
                                    $plan_row = $CI->Seller_subscription_model->get_current_plan($fetched_data[0]['user_id']);
                                }
                                ?>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Commission</label>
                                    <div class="col-sm-10">
                                        <div class="alert alert-info mb-0 py-2">
                                            <?php if (!empty($plan_row)) { ?>
                                                Commission is set by this seller's subscription plan
                                                &mdash; <strong><?= html_escape($plan_row['name']) ?></strong>:
                                                <span class="badge badge-light">First 50 orders: <?= html_escape($plan_row['commission_first50']) ?>%</span>
                                                <span class="badge badge-light">Orders 51&ndash;100: <?= html_escape($plan_row['commission_51_100']) ?>%</span>
                                                <span class="badge badge-light">After 100: <?= html_escape($plan_row['commission_after100']) ?>%</span>
                                                <div class="small mt-1">
                                                    Change the rate on the
                                                    <a href="<?= base_url('admin/subscription') ?>">subscription plan</a>,
                                                    or see this seller's
                                                    <a href="<?= base_url('admin/settlement') ?>">settlement records</a>.
                                                </div>
                                            <?php } else { ?>
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                This seller has <strong>no subscription plan</strong>, so there is no commission
                                                slab to settle against. Their delivered orders will stay uncredited until they
                                                are on a plan &mdash; see
                                                <a href="<?= base_url('admin/settlement') ?>">Commission &amp; Settlements</a>.
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if (isset($fetched_data[0]['permissions']) && !empty($fetched_data[0]['permissions'])) {
                                    $permit = json_decode($fetched_data[0]['permissions'], true);
                                } ?>
                                <div class="form-group row">
                                    <label for="require_products_approval" class="col-sm-2 form-label">Require Product's Approval? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="require_products_approval" <?= (isset($permit['require_products_approval']) && $permit['require_products_approval'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                    <label for="customer_privacy" class="form-label">View Customer's Details? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="customer_privacy" <?= (isset($permit['customer_privacy']) && $permit['customer_privacy'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                    <label for="view_order_otp" class="form-label">View Order's OTP? & Can change deliver status? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="view_order_otp" <?= (isset($permit['view_order_otp']) && $permit['view_order_otp'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                    <label for="assign_delivery_boy" class="form-label">Can assign delivery boy? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="assign_delivery_boy" <?= (isset($permit['assign_delivery_boy']) && $permit['assign_delivery_boy'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                </div>

                                <?php if (isset($fetched_data[0]['id'])) { ?>
                                    <h5 class="mt-3">Seller's Verification Request</h5>
                                    <div class="form-group row">
                                        <div class="col-sm-10 offset-2">
                                            <p class="mb-1"><strong>Seller's note:</strong> <?= !empty($fetched_data[0]['verification_request_note']) ? html_escape($fetched_data[0]['verification_request_note']) : '<span class="text-muted">&mdash;</span>' ?></p>
                                            <p class="text-muted small mb-0">Requested at: <?= !empty($fetched_data[0]['verification_request_at']) ? html_escape($fetched_data[0]['verification_request_at']) : '&mdash;' ?></p>
                                        </div>
                                    </div>
                                    <form id="verification_note_form" class="form-group row">
                                        <input type="hidden" name="user_id" value="<?= html_escape($fetched_data[0]['user_id']) ?>">
                                        <label for="verification_note_input" class="col-sm-2 col-form-label">Admin verification note</label>
                                        <div class="col-sm-10">
                                            <textarea name="verification_note" id="verification_note_input" class="form-control" rows="3" placeholder="Write a note back to the seller about their verification review..."><?= html_escape($fetched_data[0]['verification_note'] ?? '') ?></textarea>
                                            <button type="submit" class="btn btn-primary btn-sm mt-2">Save Verification Note</button>
                                            <span id="verification_note_result" class="ml-2"></span>
                                        </div>
                                    </form>
                                <?php } ?>

                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Seller' : 'Add Seller' ?></button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
                                    <div class="card text-white d-none mb-3">
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-footer -->
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

<script>
    $(document).on('submit', '#verification_note_form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $result = $('#verification_note_result');
        var $btn = $form.find('button[type="submit"]');
        $.ajax({
            type: 'POST',
            url: '<?= base_url('admin/sellers/save_verification_note') ?>',
            data: $form.serialize() + '&' + csrfName + '=' + csrfHash,
            dataType: 'json',
            beforeSend: function() {
                $btn.attr('disabled', true);
            },
            success: function(result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName;
                    csrfHash = result.csrfHash;
                }
                $result.text(result.message).css('color', result.error ? '#dc3545' : '#28a745');
                $btn.attr('disabled', false);
            },
            error: function() {
                $result.text('Something went wrong. Please try again.').css('color', '#dc3545');
                $btn.attr('disabled', false);
            }
        });
    });

    // ── Personal photo: click to enlarge when one is on file, click to upload
    //    when there isn't ──────────────────────────────────────────────────
    (function() {
        var input = document.getElementById('personalPhotoInput');
        var container = document.getElementById('personalPhotoContainer');
        var preview = document.getElementById('personalPhotoPreview');
        var icon = document.getElementById('personalPhotoIcon');
        var link = document.getElementById('personalPhotoLink');
        if (!input || !preview) return;

        if (container) {
            container.addEventListener('click', function(e) {
                // A saved photo makes the thumbnail a lightbox link; let that click through
                // to the ekko-lightbox handler rather than opening the file dialog on top of
                // it. Everything else on the circle still opens the dialog, so an empty
                // avatar behaves exactly as before.
                if (link && link.hasAttribute('data-toggle') && e.target.closest('#personalPhotoLink')) {
                    return;
                }
                input.click();
            });
        }

        input.addEventListener('change', function() {
            var file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = '';
                    preview.classList.remove('hidden');
                    if (icon) icon.style.display = 'none';
                    if (link) {
                        link.classList.remove('d-none');
                        // The picked file is not saved yet, and the lightbox resolves its
                        // type from the href - a data: URL is not something it handles. So
                        // the thumbnail stays a plain preview until the form is saved, and
                        // a click falls through to the dialog again in case the wrong file
                        // was chosen. Reloading after save restores the enlarge link.
                        link.removeAttribute('data-toggle');
                        link.removeAttribute('href');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    })();

    // ── Document upload preview / remove (images get a thumbnail; PDFs just
    //    show the "on record" hint and the remove button) ────────────────
    function bindDocPreviewFlexible(fieldName) {
        var input = document.getElementById(fieldName + '_input');
        var preview = document.getElementById(fieldName + '_preview');
        var wrap = document.getElementById(fieldName + '_wrap');
        var link = document.getElementById(fieldName + '_link');
        var hint = document.getElementById(fieldName + '_hint');
        if (!input || !preview) return;
        input.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            if (file.type === 'application/pdf') {
                preview.classList.add('hidden');
                if (link) link.removeAttribute('href');
                if (wrap) wrap.classList.remove('hidden');
                if (hint) hint.style.display = 'none';
            } else if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    if (link) link.href = e.target.result;
                    if (wrap) wrap.classList.remove('hidden');
                    if (hint) hint.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    function bindDocRemove(fieldName) {
        var btn = document.querySelector('.doc-remove-btn[data-target="' + fieldName + '"]');
        var input = document.getElementById(fieldName + '_input');
        var oldHidden = document.querySelector('input[name="old_' + fieldName + '"]');
        var wrap = document.getElementById(fieldName + '_wrap');
        var hint = document.getElementById(fieldName + '_hint');
        if (!btn) return;
        btn.addEventListener('click', function() {
            if (!confirm('Remove this file? You will need to upload a new one to replace it.')) return;
            if (input) input.value = '';
            if (oldHidden) oldHidden.value = '';
            if (wrap) wrap.classList.add('hidden');
            if (hint) hint.style.display = 'none';
        });
    }
    [
        'national_identity_card', 'authorized_signature', 'store_logo',
        'pan_card_document', 'gstin_document', 'gst_enrollment_ack_document',
        'business_proof_document', 'business_address_proof_document',
        'partnership_deed_document', 'bank_account_proof_document'
    ].forEach(function(fieldName) {
        bindDocPreviewFlexible(fieldName);
        bindDocRemove(fieldName);
    });

    // ── Bank searchable dropdown ─────────────────────────────────────────
    <?php if (!empty($indian_banks)): ?>
        var bankData = [
            <?php foreach ($indian_banks as $bank): ?>
                {
                    label: "<?= addslashes($bank['bank_name']) ?>",
                    id: "<?= addslashes($bank['bank_name']) ?>"
                },
            <?php endforeach; ?>
        ];
        (function() {
            var searchEl = document.getElementById('bank_search');
            var hiddenEl = document.getElementById('bank_name_hidden');
            var dropdownEl = document.getElementById('bank_dropdown');
            if (!searchEl || !hiddenEl || !dropdownEl) return;
            if (hiddenEl.value) searchEl.value = hiddenEl.value;

            function renderDropdown(items) {
                dropdownEl.innerHTML = '';
                if (!items.length) { dropdownEl.style.display = 'none'; return; }
                items.forEach(function(item) {
                    var div = document.createElement('div');
                    div.textContent = item.label;
                    div.style.cssText = 'padding:8px 12px; cursor:pointer;';
                    div.addEventListener('mouseenter', function() { this.style.background = '#f0f0f0'; });
                    div.addEventListener('mouseleave', function() { this.style.background = '#fff'; });
                    div.addEventListener('click', function() {
                        searchEl.value = item.label;
                        hiddenEl.value = item.label;
                        dropdownEl.style.display = 'none';
                    });
                    dropdownEl.appendChild(div);
                });
                dropdownEl.style.display = 'block';
            }
            searchEl.addEventListener('input', function() {
                var q = this.value.toLowerCase();
                hiddenEl.value = this.value.trim();
                if (!q) { dropdownEl.style.display = 'none'; return; }
                renderDropdown(bankData.filter(function(item) { return item.label.toLowerCase().includes(q); }));
            });
            document.addEventListener('click', function(e) {
                if (e.target !== searchEl) dropdownEl.style.display = 'none';
            });
        })();
    <?php endif; ?>

    // ── Entity-type / GST toggle + legal-name auto-fill ──────────────────
    (function() {
        var gstCheck = document.getElementById('gst_check');
        var entityType = document.getElementById('entity_type');
        if (!gstCheck || !entityType) return;

        var PAN_LABELS = {
            individual: 'PAN Number',
            sole_proprietorship: "Proprietor's PAN Number",
            partnership_firm: "Firm's PAN Number"
        };
        var LEGAL_NAME_LABELS = {
            individual: 'Legal Business Name',
            sole_proprietorship: 'Legal Business Name',
            partnership_firm: "Legal Firm's Name"
        };

        function syncGstFields() {
            var nonGst = gstCheck.checked;
            var numDiv = document.getElementById('gst_number_div');
            var enrDiv = document.getElementById('gst_enrollment_div');
            if (numDiv) numDiv.style.display = nonGst ? 'none' : '';
            if (enrDiv) enrDiv.style.display = nonGst ? '' : 'none';

            var gstinField = document.getElementById('gstin_document_field');
            var ackField = document.getElementById('gst_enrollment_ack_document_field');
            if (gstinField) gstinField.style.display = nonGst ? 'none' : '';
            if (ackField) ackField.style.display = nonGst ? '' : 'none';

            updateBusinessProofVisibility();
        }

        function updateBusinessProofVisibility() {
            var type = entityType.value;
            var section = document.getElementById('business_proof_section');
            var visible = gstCheck.checked && type !== 'individual' && type !== '';
            if (section) section.style.display = visible ? '' : 'none';
        }

        function updateEntityTypeUI() {
            var type = entityType.value;
            var panLabelEl = document.getElementById('pan_label');
            var legalLabelEl = document.getElementById('legal_business_name_label');
            var legalInput = document.getElementById('legal_business_name_input');
            var partnershipSection = document.getElementById('partnership_deed_section');

            if (panLabelEl) panLabelEl.innerHTML = (PAN_LABELS[type] || 'PAN Number') + '<span class="text-danger text-sm">*</span>';
            if (legalLabelEl) legalLabelEl.textContent = LEGAL_NAME_LABELS[type] || 'Legal Business Name';

            if (type === 'individual' && legalInput && (!legalInput.value.trim() || legalInput.dataset.autofilled === '1')) {
                var firstName = document.querySelector('input[name="first_name"]');
                var lastName = document.querySelector('input[name="last_name"]');
                var fullName = [firstName ? firstName.value.trim() : '', lastName ? lastName.value.trim() : ''].filter(Boolean).join(' ');
                if (fullName) {
                    legalInput.value = fullName;
                    legalInput.dataset.autofilled = '1';
                }
            }

            if (partnershipSection) partnershipSection.style.display = (type === 'partnership_firm') ? '' : 'none';

            syncGstFields();
        }

        gstCheck.addEventListener('change', syncGstFields);
        entityType.addEventListener('change', updateEntityTypeUI);
        var firstNameInput = document.querySelector('input[name="first_name"]');
        var lastNameInput = document.querySelector('input[name="last_name"]');
        if (firstNameInput) firstNameInput.addEventListener('blur', updateEntityTypeUI);
        if (lastNameInput) lastNameInput.addEventListener('blur', updateEntityTypeUI);
        var legalNameInput = document.getElementById('legal_business_name_input');
        if (legalNameInput) legalNameInput.addEventListener('input', function() { legalNameInput.dataset.autofilled = ''; });
        syncGstFields();
        updateEntityTypeUI();
    })();

    // ── Secondary Categories picker ──────────────────────────────────────
    (function() {
        var modal = document.getElementById('category_picker_modal');
        var openBtn = document.getElementById('open_category_picker_btn');
        var closeBtn = document.getElementById('close_category_picker_btn');
        var cancelBtn = document.getElementById('cancel_category_picker_btn');
        var doneBtn = document.getElementById('done_category_picker_btn');
        var hiddenInput = document.getElementById('secondary_category_ids_hidden');
        var pillsContainer = document.getElementById('secondary_category_pills');
        var primarySelect = document.getElementById('primary_category_id');
        var emptyMsg = document.getElementById('category_picker_empty_msg');
        if (!modal || !hiddenInput || !pillsContainer || !primarySelect) return;
        var items = Array.prototype.slice.call(document.querySelectorAll('.category-picker-item'));
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.secondary-category-checkbox'));

        var categoryNames = {};
        var categoryParent = {};
        items.forEach(function(item) {
            var cb = item.querySelector('.secondary-category-checkbox');
            var label = item.querySelector('span');
            if (!cb) return;
            categoryNames[cb.value] = label ? label.textContent : cb.value;
            categoryParent[cb.value] = item.getAttribute('data-parent');
        });

        function getSelectedIds() {
            return hiddenInput.value ? hiddenInput.value.split(',').filter(function(v) { return v !== ''; }) : [];
        }

        function renderPills() {
            var ids = getSelectedIds();
            pillsContainer.innerHTML = '';
            ids.forEach(function(id) {
                var pill = document.createElement('span');
                pill.className = 'category-pill';
                pill.textContent = categoryNames[id] || id;
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.innerHTML = '&times;';
                removeBtn.setAttribute('aria-label', 'Remove category');
                removeBtn.addEventListener('click', function() {
                    hiddenInput.value = getSelectedIds().filter(function(x) { return x !== id; }).join(',');
                    syncCheckboxes();
                    renderPills();
                });
                pill.appendChild(removeBtn);
                pillsContainer.appendChild(pill);
            });
        }

        function syncCheckboxes() {
            var ids = getSelectedIds();
            checkboxes.forEach(function(cb) { cb.checked = ids.indexOf(cb.value) !== -1; });
        }

        function filterByPrimary() {
            var primaryId = primarySelect.value;
            var anyVisible = false;
            items.forEach(function(item) {
                var visible = !!primaryId && item.getAttribute('data-parent') === primaryId;
                item.style.display = visible ? '' : 'none';
                if (visible) anyVisible = true;
            });
            if (emptyMsg) emptyMsg.style.display = anyVisible ? 'none' : '';
            if (openBtn) {
                openBtn.disabled = !primaryId;
                openBtn.style.opacity = primaryId ? '1' : '0.5';
                openBtn.style.cursor = primaryId ? 'pointer' : 'not-allowed';
            }
        }

        function pruneSelectionsOutsidePrimary() {
            var primaryId = primarySelect.value;
            var kept = getSelectedIds().filter(function(id) { return categoryParent[id] === primaryId; });
            hiddenInput.value = kept.join(',');
            renderPills();
        }

        function openModal() {
            if (primarySelect.value === '') return;
            syncCheckboxes();
            filterByPrimary();
            modal.style.display = 'flex';
        }
        function closeModal() { modal.style.display = 'none'; }
        function applyAndClose() {
            var ids = checkboxes.filter(function(cb) { return cb.checked && cb.closest('.category-picker-item').style.display !== 'none'; }).map(function(cb) { return cb.value; });
            hiddenInput.value = ids.join(',');
            renderPills();
            modal.style.display = 'none';
        }

        if (openBtn) openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (doneBtn) doneBtn.addEventListener('click', applyAndClose);
        modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
        primarySelect.addEventListener('change', function() {
            pruneSelectionsOutsidePrimary();
            filterByPrimary();
        });

        filterByPrimary();
        renderPills();
    })();

    // Store URL placeholder mirrors Shop Name live
    (function() {
        var shopNameInput = document.querySelector('input[name="shop_name"]');
        var slugInput = document.getElementById('slug_input');
        if (!shopNameInput || !slugInput) return;
        shopNameInput.addEventListener('input', function() {
            slugInput.placeholder = shopNameInput.value.trim() || 'your-shop-name';
        });
    })();

    // ── Pincode autofill (India Post, falls back to Zippopotam) ──────────
    function setPincodeStatus(statusElement, message, type) {
        if (!statusElement) return;
        statusElement.textContent = message || '';
        statusElement.classList.remove('error', 'success', 'info');
        if (type) statusElement.classList.add(type);
    }
    function getFirstAvailableValue(source, keys) {
        for (var i = 0; i < keys.length; i++) {
            if (source && source[keys[i]]) return source[keys[i]];
        }
        return '';
    }
    function setLocationInputValue(inputId, value) {
        var input = document.getElementById(inputId);
        if (input && value) input.value = value;
    }
    function bindPincodeAutofill(options) {
        var pinInput = document.getElementById(options.pinId);
        var statusElement = document.getElementById(options.statusId);
        var lookupTimer = null;
        var latestPincode = '';
        if (!pinInput) return;

        function firstMeaningful() {
            for (var i = 0; i < arguments.length; i++) {
                var v = (arguments[i] || '').toString().trim();
                if (v && !/^(na|nil|none)$/i.test(v)) return v;
            }
            return '';
        }
        function applyLocation(loc) {
            setLocationInputValue(options.stateInputId, loc.state);
            setLocationInputValue(options.districtInputId, loc.district);
            setLocationInputValue(options.cityInputId, loc.city);
        }
        function lookupIndiaPost(pincode) {
            return fetch('https://api.postalpincode.in/pincode/' + encodeURIComponent(pincode))
                .then(function(r) { if (!r.ok) throw new Error('http'); return r.json(); })
                .then(function(data) {
                    var rec = Array.isArray(data) ? data[0] : null;
                    var offices = (rec && Array.isArray(rec.PostOffice)) ? rec.PostOffice : [];
                    if (!rec || rec.Status !== 'Success' || !offices.length) throw new Error('not found');
                    var po = offices[0];
                    return { state: po.State || '', district: po.District || '', city: firstMeaningful(po.Block, po.Name, po.District) };
                });
        }
        function lookupZippopotam(pincode) {
            return fetch('https://api.zippopotam.us/in/' + encodeURIComponent(pincode))
                .then(function(r) { if (!r.ok) throw new Error('http'); return r.json(); })
                .then(function(data) {
                    var place = (Array.isArray(data.places) ? data.places : [])[0] || {};
                    var placeName = getFirstAvailableValue(place, ['place name', 'place_name', 'city', 'town', 'locality']);
                    var state = getFirstAvailableValue(place, ['state', 'state name', 'state_name']);
                    var district = getFirstAvailableValue(place, ['district', 'district name', 'district_name', 'county', 'region']) || placeName;
                    if (!placeName && !state && !district) throw new Error('empty');
                    return { state: state, district: district, city: placeName };
                });
        }
        function fillAddressFromPincode() {
            var pincode = pinInput.value.replace(/\D/g, '').slice(0, 6);
            pinInput.value = pincode;
            if (pincode.length < 6) {
                latestPincode = '';
                setPincodeStatus(statusElement, '', '');
                return;
            }
            latestPincode = pincode;
            setPincodeStatus(statusElement, 'Fetching state, district and city…', 'info');
            lookupIndiaPost(pincode)
                .catch(function() { return lookupZippopotam(pincode); })
                .then(function(loc) {
                    if (latestPincode !== pincode) return;
                    applyLocation(loc);
                    setPincodeStatus(statusElement, 'State, district and city filled from pincode.', 'success');
                })
                .catch(function() {
                    if (latestPincode !== pincode) return;
                    setPincodeStatus(statusElement, 'Could not auto-detect this pincode. Please enter manually.', 'info');
                });
        }
        pinInput.addEventListener('input', function() {
            clearTimeout(lookupTimer);
            lookupTimer = setTimeout(fillAddressFromPincode, 400);
        });
        pinInput.addEventListener('blur', fillAddressFromPincode);
    }
    bindPincodeAutofill({ pinId: 'pin', stateInputId: 'state', districtInputId: 'district', cityInputId: 'city', statusId: 'pin_lookup_status' });
    bindPincodeAutofill({ pinId: 'pickup_pin', stateInputId: 'pickup_state', districtInputId: 'pickup_district', cityInputId: 'pickup_city', statusId: 'pickup_pin_lookup_status' });
    bindPincodeAutofill({ pinId: 'business_pin', stateInputId: 'business_state', districtInputId: 'business_district', cityInputId: 'business_city', statusId: 'business_pin_lookup_status' });
</script>

<style>
    .pincode-lookup-status {
        display: block;
        font-size: 12px;
        margin-top: 4px;
    }

    .pincode-lookup-status.error {
        color: #dc3545;
    }

    .pincode-lookup-status.success {
        color: #198754;
    }

    .pincode-lookup-status.info {
        color: #6c757d;
    }

    .category-pill {
        display: inline-flex;
        align-items: center;
        background: #eef2ff;
        color: #3730a3;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        margin: 4px 6px 0 0;
    }

    .category-pill button {
        background: none;
        border: none;
        color: #3730a3;
        cursor: pointer;
        font-weight: bold;
        margin-left: 4px;
        padding: 0;
        line-height: 1;
    }

    .category-picker-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }

    .category-picker-content {
        background: #fff;
        width: min(640px, 92vw);
        max-height: 80vh;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
        display: flex;
        flex-direction: column;
    }

    .category-picker-header,
    .category-picker-footer {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .category-picker-footer {
        border-top: 1px solid #e5e7eb;
        border-bottom: 0;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .category-picker-list {
        padding: 12px 16px;
        overflow-y: auto;
    }

    /* Cancel and Done are two DIFFERENT button classes - .btn-add-categories is the outlined
       one used for "+ Add Categories" out on the form, .btn-upload-logo the solid one used for
       the photo pickers - so side by side in this footer they came out different heights:
       13px vs 0.9rem font, 1px border vs none, and .btn-add-categories additionally carries
       margin-top:0.5rem for its standalone use, which pushed Cancel out of line. Normalise the
       box for both, only inside the footer, so the classes keep behaving as before elsewhere. */
    .category-picker-footer .btn-add-categories,
    .category-picker-footer .btn-upload-logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 0;
        min-height: 40px;
        padding: 0.5rem 1.25rem;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
        border-radius: 0.6rem;
        /* A transparent border on the solid button too, so the outlined one's 1px cannot
           make it the shorter of the two. */
        border: 1px solid transparent;
    }

    .category-picker-footer .btn-add-categories {
        border-color: var(--color-orange, #F2822E);
    }

    .category-picker-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 0;
    }

    .category-picker-close {
        background: none;
        border: none;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        color: #6c757d;
    }

    .btn-add-categories {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background-color: #fff;
        color: var(--color-orange, #F2822E);
        border: 1px solid var(--color-orange, #F2822E);
        border-radius: 0.6rem;
        padding: 0.4rem 0.9rem;
        font-size: 13px;
        cursor: pointer;
        margin-top: 0.5rem;
    }

    .btn-add-categories:hover {
        background-color: var(--color-orange, #F2822E);
        color: #fff;
    }

    .personal-photo-preview {
        border-radius: 50%;
        width: 4rem;
        height: 4rem;
        flex-shrink: 0;
        background-color: #f7f7f7;
        border: 2px solid #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: pointer;
    }

    .personal-photo-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Fills the circle so the whole thumbnail is the click target for the lightbox. */
    .personal-photo-link {
        display: block;
        width: 100%;
        height: 100%;
        line-height: 0;
    }

    /* Only hint at zooming while the link is live - once a new file is picked the
       data-toggle comes off and the thumbnail goes back to being upload-on-click. */
    .personal-photo-link[data-toggle="lightbox"] {
        cursor: zoom-in;
    }

    .personal-photo-link[data-toggle="lightbox"]:hover img {
        transform: scale(1.08);
        transition: transform 0.15s ease;
    }

    .personal-photo-icon {
        width: 2rem;
        height: 2rem;
        color: #999;
    }

    .doc-upload-thumb {
        max-height: 90px;
        border-radius: 6px;
        border: 1px solid #ddd;
        margin-top: 8px;
        display: block;
    }

    .doc-upload-hint {
        font-size: 12px;
        color: #6c757d;
        display: block;
        margin-top: 4px;
    }

    .doc-upload-preview-wrap {
        position: relative;
        display: inline-block;
    }

    .doc-upload-preview-wrap.hidden {
        display: none;
    }

    .doc-remove-btn {
        position: absolute;
        top: 2px;
        right: -8px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        border: 2px solid #fff;
        font-size: 14px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .doc-remove-btn:hover {
        background: #bb2d3b;
    }
</style>
