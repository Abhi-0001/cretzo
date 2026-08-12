
<style>
        /* ═══════════════════════════════════════════════════════════════
   Product form — media upload areas (fixed-size)
   Replace or merge with your existing <style> block on the page.
   ═══════════════════════════════════════════════════════════════ */

/* ── Top two-column layout ──────────────────────────────────── */
.create-product-page .top-layout {
    display: flex;
    align-items: stretch;
    gap: 1rem;
    min-height: 520px;
}
.create-product-page .top-column {
    flex: 1;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    background: #fff;
    padding: 1rem;
    display: flex;
    flex-direction: column;
}
.create-product-page .inner-scroll {
    overflow-y: auto;
    flex: 1;
    padding-right: .5rem;
}
.create-product-page .block-title {
    margin-bottom: 1rem;
    padding-bottom: .5rem;
    border-bottom: 1px solid #f1f3f5;
    font-weight: 600;
}

/* ── Section header ─────────────────────────────────────────── */
.create-product-page .section-header {
    font-weight: 700;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: .5rem;
    margin-bottom: 1rem;
}

/* ── Media groups ───────────────────────────────────────────── */
.create-product-page .media-group {
    margin-bottom: 1.25rem;
}
.create-product-page .media-group h6 {
    font-size: .875rem;
    font-weight: 600;
    margin-bottom: .5rem;
    color: #374151;
}

/* ─────────────────────────────────────────────────────────────
   MAIN IMAGE — fixed 200×200 upload zone
   ───────────────────────────────────────────────────────────── */
.create-product-page #main_image_preview {
    width: 150px;
    height: 150px;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    margin-top: .5rem;
    transition: border-color .2s;
}
.create-product-page #main_image_preview:empty::after {
    content: 'No image yet';
    font-size: .75rem;
    color: #9ca3af;
}
/* The thumb wrapper inside the preview fills the container */
.create-product-page #main_image_preview .thumb-wrapper {
    width: 100%;
    height: 100%;
    display: block;
    border: none;
    padding: 0;
    border-radius: 0;
}
.create-product-page #main_image_preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    cursor: zoom-in;
}

/* ─────────────────────────────────────────────────────────────
   OTHER IMAGES — fixed-height scroll zone with uniform tiles
   ───────────────────────────────────────────────────────────── */
.create-product-page #other_images_preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: .5rem;
    min-height: 100px;      /* always shows even when empty       */
    max-height: 220px;      /* scrolls if many images added       */
    overflow-y: auto;
    padding: .25rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #f9fafb;
    margin-top: .5rem;
}
.create-product-page #other_images_preview:empty::after {
    content: 'No images yet';
    font-size: .75rem;
    color: #9ca3af;
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 80px;
}
/* Each tile is a fixed square */
.create-product-page .thumb-wrapper {
    position: relative;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
}
.create-product-page #other_images_preview .thumb-wrapper {
    width: 90px;
    height: 90px;
    flex-shrink: 0;
}
.create-product-page #other_images_preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
/* Remove button */
.create-product-page .remove-thumb {
    position: absolute;
    top: 3px;
    right: 3px;
    border: none;
    background: rgba(220, 53, 69, .85);
    color: #fff;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    line-height: 18px;
    text-align: center;
    font-size: 13px;
    cursor: pointer;
    z-index: 2;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.create-product-page .remove-thumb:hover { background: #dc3545; }

/* ─────────────────────────────────────────────────────────────
   VIDEO UPLOAD — fixed-height container
   ───────────────────────────────────────────────────────────── */
.create-product-page #video_file_container,
.create-product-page #video_url_container {
    min-height: 44px;
}

/* ─────────────────────────────────────────────────────────────
   Alert hint box (shows what's still missing)
   ───────────────────────────────────────────────────────────── */
#product-form-alert.alert-info {
    background: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
}

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 767px) {
    .create-product-page .top-layout {
        flex-direction: column;
    }
    .create-product-page #main_image_preview {
        width: 100%;
        height: 180px;
    }
}
        .seller-product-top-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 1.25rem;
        }
        .seller-product-media-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 4px 14px rgba(28, 37, 54, 0.06);
        }
        .seller-product-media-card .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: .75rem;
            
        }
        .seller-product-media-card .image-upload-section {
            min-height: 90px;
        }

        .seller-product-media-card .image-upload-div {
            width: 180px;
            height: 180px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .seller-product-media-card .image-upload-div img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: zoom-in;
            display: block;
        }
            
        @media (max-width: 991.98px) {
            .seller-product-top-grid {
                grid-template-columns: 1fr;
            }
        }
        #save-product .field-col {
            margin-bottom: 14px;
        }

        #save-product .field-col .col-form-label {
            display: block;
            min-height: 24px;
            margin-bottom: 6px;
        }

        #save-product .category-select-wrap .form-control {
            min-height: 38px;
        }

        /* ── Theme pass: simple, attractive, on-brand ──────────────── */
        .create-product-page .section-header {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: 1rem;
            color: #2b2f33;
            border-bottom: 2px solid var(--color-secondary);
        }
        .create-product-page .section-header i {
            color: var(--color-orange);
        }
        .create-product-page .top-column {
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        }
        .create-product-page .block-title {
            font-size: .95rem;
            color: #495057;
        }
        .create-product-page .card-light {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        }
        .create-product-page .card-light h6 {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            margin-bottom: .9rem;
        }
        .create-product-page .card-light h6 i {
            color: var(--color-orange);
        }
        .create-product-page .card-info {
            border: none;
            border-radius: 12px;
        }
        .create-product-page .form-control:focus {
            border-color: var(--color-orange);
            box-shadow: 0 0 0 .15rem var(--color-orange-light);
        }
        .create-product-page #main_image_input,
        .create-product-page #other_images_input,
        .create-product-page #video_file_input {
            border: 1px dashed #d1d5db;
            border-radius: 6px;
            padding: .35rem .5rem;
            font-size: .8rem;
        }
        .create-product-page #main_image_preview:hover,
        .create-product-page #other_images_preview {
            border-color: var(--color-orange-light);
        }
        .create-product-page .btn-outline-primary {
            color: var(--color-orange-dark);
            border-color: var(--color-orange);
        }
        .create-product-page .btn-outline-primary:hover {
            background: var(--color-orange);
            border-color: var(--color-orange);
            color: #fff;
        }
        .create-product-page .card-footer {
            background: #fff;
            border-top: 1px solid #f1f3f5;
            border-radius: 0 0 12px 12px;
        }
        .create-product-page #submit_product_btn {
            background: var(--color-orange);
            border-color: var(--color-orange);
            padding: .5rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
        }
        .create-product-page #submit_product_btn:hover:not(:disabled) {
            background: var(--color-orange-dark);
            border-color: var(--color-orange-dark);
        }
        .create-product-page .category-search-box {
            position: relative;
        }
        .create-product-page .category-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: .85rem;
            pointer-events: none;
        }
        .create-product-page .category-search-input {
            padding-left: 34px;
            padding-right: 30px;
        }
        .create-product-page .category-search-clear {
            display: none;
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #9ca3af;
            font-size: 1.15rem;
            line-height: 1;
            cursor: pointer;
            padding: 2px 6px;
        }
        .create-product-page .category-search-clear:hover {
            color: #4b5563;
        }
        .create-product-page #category_dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ced4da;
            border-top: none;
            border-radius: 0 0 .375rem .375rem;
            max-height: 320px;
            overflow-y: auto;
            z-index: 9999;
            box-shadow: 0 8px 16px rgba(0, 0, 0, .08);
        }
        .create-product-page .category-result-item {
            padding: 8px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }
        .create-product-page .category-result-item:last-child {
            border-bottom: none;
        }
        .create-product-page .category-result-item:hover {
            background: var(--color-orange-light);
        }
        .create-product-page .category-result-name {
            font-weight: 600;
            color: #1f2937;
            font-size: .9rem;
        }
        .create-product-page .category-result-path {
            font-size: .75rem;
            color: #9ca3af;
            margin-top: 2px;
        }
    </style>
    <div class="content-wrapper create-product-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4><?= isset($product_details[0]['id']) ? 'Update' : 'Add' ?> Product</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Products</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <?php
    // An expired subscription now blocks new listings, so it needs its own message - the
    // usage banner alone would read "0 remaining, you've reached your plan limit", which
    // points the seller at an upgrade when what they actually need is a renewal.
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
        <div class="card card-info shadow-sm">
                <form action="<?= base_url('seller/product/add_product'); ?>" method="POST" id="save-product" novalidate
                    data-subcategory-url="<?= base_url('seller/product/get_subcategories') ?>"
                    data-media-upload-url="<?= base_url('seller/media/upload') ?>"
                    data-product-list-url="<?= base_url('seller/product/') ?>">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" id="csrf_token_input">
                    <input type="hidden" name="seller_id" value="<?= (int)($seller_id ?? $_SESSION['user_id']); ?>">
                    <input type="hidden" name="attribute_values" value="">
                    <input type="hidden" name="category_id" id="selected_category_id" value="<?= isset($product_details[0]['category_id']) ? (int)$product_details[0]['category_id'] : '' ?>">
                    <input type="hidden" id="category_tree_data" value='<?= htmlspecialchars(json_encode($categories ?? []), ENT_QUOTES, "UTF-8") ?>'>
                    <input type="hidden" id="existing_variants_data" value='<?= htmlspecialchars(json_encode(($product_details[0]['type'] ?? '') === 'variable_product' ? ($product_variants ?? []) : []), ENT_QUOTES, "UTF-8") ?>'>
                    <?php if (isset($product_details[0]['id'])): ?>
                        <input type="hidden" name="edit_product_id" value="<?= (int)$product_details[0]['id'] ?>">
                    <?php endif; ?>

                    <div class="card-body">
                        <div id="product-form-alert" class="alert d-none" role="alert"></div>

                        <div class="section-header"><i class="fas fa-box-open"></i> Basic Information &amp; Media</div>
                        <div class="top-layout">
                            <div class="top-column">
                                <h5 class="block-title">Product Information</h5>
                                <div class="inner-scroll">
                                    <div class="form-group">
                                        <label for="pro_input_text">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="pro_input_text" name="pro_input_name" value="<?= isset($product_details[0]['name']) ? output_escaping($product_details[0]['name']) : '' ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="product_type">Product Type</label>
                                        <select class="form-control" name="product_type" id="product_type">
                                            <?php $selectedType = $product_details[0]['type'] ?? 'simple_product'; ?>
                                            <option value="simple_product" <?= $selectedType === 'simple_product' ? 'selected' : '' ?>>Simple Product</option>
                                            <option value="variable_product" <?= $selectedType === 'variable_product' ? 'selected' : '' ?>>Variable Product</option>
                                            <option value="digital_product" <?= $selectedType === 'digital_product' ? 'selected' : '' ?>>Digital Product</option>
                                        </select>
                                    </div>
                               
                                    <div class="form-group">
                                        <label for="short_description">Short Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="short_description" name="short_description" rows="3"><?= isset($product_details[0]['short_description']) ? output_escaping($product_details[0]['short_description']) : '' ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="pro_input_description">Additional Info</label>
                                        <textarea class="form-control" id="pro_input_description" name="pro_input_description" rows="4"><?= isset($product_details[0]['description']) ? output_escaping($product_details[0]['description']) : '' ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="extra_input_description">Extra Notes</label>
                                        <textarea class="form-control" id="extra_input_description" name="extra_input_description" rows="2"><?= isset($product_details[0]['extra_description']) ? output_escaping($product_details[0]['extra_description']) : '' ?></textarea>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label for="tags">Tags</label>
                                        <input type="text" class="form-control" id="tags" name="tags" placeholder="ac, cooler, smartphone" value="<?= isset($product_details[0]['tags']) ? output_escaping($product_details[0]['tags']) : '' ?>">    
                                </div>
                                </div>
                            </div>

                            <div class="top-column">
                                <h5 class="block-title">Product Media</h5>
                                <div class="inner-scroll">
                                    <div class="media-group">
                                        <h6>Main Image <span class="text-danger">*</span></h6>
                                        <input type="file" class="form-control-file" id="main_image_input" accept="image/*">
                                        <input type="hidden" name="pro_input_image" id="pro_input_image" value="<?= isset($product_details[0]['image']) ? output_escaping($product_details[0]['image']) : '' ?>">
                                        <div id="main_image_preview" class="preview-single">
                                            <?php if (!empty($product_details[0]['image'])) : ?>
                                                <div class="thumb-wrapper d-inline-block">
                                                    <button type="button" class="remove-thumb" data-role="remove-main">&times;</button>
                                                    <img src="<?= get_image_url($product_details[0]['image'], 'thumb', 'md') ?>" alt="Main">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="media-group">
                                        <h6>Other Images</h6>
                                        <input type="file" class="form-control-file" id="other_images_input" accept="image/*" multiple>
                                        <div id="other_images_preview" class="preview-grid">
                                            <?php
                                            $existing_other_images = [];
                                            if (!empty($product_details[0]['other_images'])) {
                                                $decoded = json_decode($product_details[0]['other_images'], true);
                                                if (is_array($decoded)) {
                                                    $existing_other_images = $decoded;
                                                }
                                            }
                                            ?>
                                            <?php foreach ($existing_other_images as $other_image_path) : ?>
                                                <?php if (empty($other_image_path)) continue; ?>
                                                <div class="thumb-wrapper" data-path="<?= output_escaping($other_image_path) ?>">
                                                    <button type="button" class="remove-thumb" data-role="remove-other">&times;</button>
                                                    <img src="<?= get_image_url($other_image_path, 'thumb', 'md') ?>" alt="Other">
                                                    <input type="hidden" name="other_images[]" value="<?= output_escaping($other_image_path) ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="media-group">
                                        <h6>Video Upload</h6>
                                        <?php $existing_video_type = $product_details[0]['video_type'] ?? ''; ?>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <select name="video_type" id="video_type" class="form-control">
                                                    <option value="" <?= $existing_video_type === '' ? 'selected' : '' ?>>None</option>
                                                    <option value="youtube" <?= $existing_video_type === 'youtube' ? 'selected' : '' ?>>YouTube URL</option>
                                                    <option value="vimeo" <?= $existing_video_type === 'vimeo' ? 'selected' : '' ?>>Vimeo URL</option>
                                                    <option value="self_hosted" <?= $existing_video_type === 'self_hosted' ? 'selected' : '' ?>>Upload Video File</option>
                                                </select>
                                            </div>

                                            <div class="form-group col-md-8" id="video_url_container">
                                                <label for="video">Video URL <span class="text-danger">*</span></label>
                                                <input type="url" class="form-control" name="video" id="video" placeholder="https://..." value="<?= ($existing_video_type === 'youtube' || $existing_video_type === 'vimeo') && !empty($product_details[0]['video']) ? output_escaping($product_details[0]['video']) : '' ?>">
                                            </div>
                                        </div>
                                        <div id="video_file_container" class="d-none">
                                            <label for="video_file_input">Video File <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control-file" id="video_file_input" accept="video/*">
                                            <input type="hidden" name="pro_input_video" id="pro_input_video" value="<?= $existing_video_type === 'self_hosted' && !empty($product_details[0]['video']) ? output_escaping($product_details[0]['video']) : '' ?>">
                                            <small id="video_file_name" class="text-muted"><?= $existing_video_type === 'self_hosted' && !empty($product_details[0]['video']) ? 'Current: ' . output_escaping(basename($product_details[0]['video'])) : '' ?></small>
                                        </div>
                                    </div>
                                    </div>
                            </div>
                        </div>

                        <div class="section-header mt-4"><i class="fas fa-tags"></i> Product Details</div>
                        <div class="card card-light mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label>Tax</label>
                                        <select class="form-control" name="pro_input_tax" id="pro_input_tax">
                                            <option value="0">No Tax</option>
                                            <?php foreach (($taxes ?? []) as $tax): ?>
                                                <option value="<?= (int)$tax['id'] ?>" <?= isset($product_details[0]['tax']) && (int)$product_details[0]['tax'] === (int)$tax['id'] ? 'selected' : '' ?>><?= output_escaping($tax['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Made In</label>
                                        <select class="form-control" name="made_in" id="made_in">
                                            <option value="">Select Country</option>
                                            <?php foreach (($countries ?? []) as $country): ?>
                                                <option value="<?= output_escaping($country['name']) ?>" <?= isset($product_details[0]['made_in']) && $product_details[0]['made_in'] === $country['name'] ? 'selected' : '' ?>><?= output_escaping($country['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Brand</label>
                                        <select class="form-control" name="brand" id="brand">
                                            <option value="">Select Brand</option>
                                            <?php foreach (($brands ?? []) as $brand): ?>
                                                <?php // products.brand is a plain string column matched by NAME everywhere
                                                      // (storefront filtering, Brand_model) - the option value must be the
                                                      // brand's name, not its id, or a seller-chosen brand never matches. ?>
                                                <option value="<?= output_escaping($brand['name']) ?>" <?= !empty($product_details[0]['brand']) && $product_details[0]['brand'] === $brand['name'] ? 'selected' : '' ?>><?= output_escaping($brand['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Indicator</label>
                                        <select class="form-control" name="indicator" id="indicator">
                                            <?php $selected_indicator = $product_details[0]['indicator'] ?? '0'; ?>
                                            <option value="0" <?= $selected_indicator === '0' ? 'selected' : '' ?>>None</option>
                                            <option value="1" <?= $selected_indicator === '1' ? 'selected' : '' ?>>Veg</option>
                                            <option value="2" <?= $selected_indicator === '2' ? 'selected' : '' ?>>Non-Veg</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>HSN Code</label>
                                        <input type="text" class="form-control" name="hsn_code" id="hsn_code" value="<?= isset($product_details[0]['hsn_code']) ? output_escaping($product_details[0]['hsn_code']) : '' ?>">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Warranty Period</label>
                                        <input type="text" class="form-control" name="warranty_period" placeholder="e.g. 1 Year" value="<?= isset($product_details[0]['warranty_period']) ? output_escaping($product_details[0]['warranty_period']) : '' ?>">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Guarantee Period</label>
                                        <input type="text" class="form-control" name="guarantee_period" placeholder="e.g. 6 Months" value="<?= isset($product_details[0]['guarantee_period']) ? output_escaping($product_details[0]['guarantee_period']) : '' ?>">
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <?php if (!empty($pickup_locations)) : ?>
                                            <label for="pickup_location">Pickup Location <span class="text-danger">*</span></label>
                                            <select class="form-control" name="pickup_location" id="pickup_location">
                                                <option value="">Select Pickup Location</option>
                                                <?php foreach ($pickup_locations as $loc) : ?>
                                                    <option value="<?= output_escaping($loc['pickup_location']) ?>" <?= isset($product_details[0]['pickup_location']) && $product_details[0]['pickup_location'] === $loc['pickup_location'] ? 'selected' : '' ?>><?= output_escaping($loc['pickup_location']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else : ?>
                                            <label>Pickup Location</label>
                                            <div class="alert alert-warning py-2 px-3 mb-0" style="font-size: 13px;">
                                                You haven't added a pickup location yet. <a href="<?= base_url('seller/pickup_location/manage_pickup_locations') ?>">Add one</a> so this product can be shipped.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-4 form-group">
    <label>Category  <span class="text-danger">*</span></label>
    <div class="category-combo" style="position:relative;">
        <div class="category-search-box">
            <i class="fas fa-search category-search-icon"></i>
            <input type="text"
                   class="form-control category-search-input"
                   id="category_level_1_input"
                   placeholder="Search category..."
                   autocomplete="off">
            <button type="button" class="category-search-clear" id="category_search_clear" aria-label="Clear">&times;</button>
        </div>
        <div id="category_dropdown"></div>
    </div>
    <small class="text-muted">Search and select an existing category</small>
</div>

                                    <div class="col-md-4 form-group">
                                        <label>Total Allowed Quantity</label>
                                        <input type="number" min="1" class="form-control" name="total_allowed_quantity" value="<?= !empty($product_details[0]['total_allowed_quantity']) ? (int)$product_details[0]['total_allowed_quantity'] : 1 ?>">
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label>Minimum Order Quantity</label>
                                        <input type="number" min="1" class="form-control" name="minimum_order_quantity" value="<?= !empty($product_details[0]['minimum_order_quantity']) ? (int)$product_details[0]['minimum_order_quantity'] : 1 ?>">
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label>Quantity Step Size</label>
                                        <input type="number" min="1" class="form-control" name="quantity_step_size" value="<?= !empty($product_details[0]['quantity_step_size']) ? (int)$product_details[0]['quantity_step_size'] : 1 ?>">
                                    </div>

                                    <?php
                                    $selected_deliverable_type = isset($product_details[0]['deliverable_type']) ? (string) $product_details[0]['deliverable_type'] : ALL;
                                    $existing_zipcodes = !empty($product_details[0]['deliverable_zipcodes']) ? str_replace(',', "\n", $product_details[0]['deliverable_zipcodes']) : '';
                                    $zipcode_restricted = in_array($selected_deliverable_type, [INCLUDED, EXCLUDED], true);
                                    ?>
                                    <div class="col-md-4 form-group">
                                        <label>Deliverable Areas</label>
                                        <select class="form-control" name="deliverable_type" id="deliverable_type">
                                            <option value="<?= NONE ?>" <?= $selected_deliverable_type === NONE ? 'selected' : '' ?>>Not Deliverable</option>
                                            <option value="<?= ALL ?>" <?= $selected_deliverable_type === ALL ? 'selected' : '' ?>>All Areas</option>
                                            <option value="<?= INCLUDED ?>" <?= $selected_deliverable_type === INCLUDED ? 'selected' : '' ?>>Only Selected Zipcodes</option>
                                            <option value="<?= EXCLUDED ?>" <?= $selected_deliverable_type === EXCLUDED ? 'selected' : '' ?>>All Except Selected Zipcodes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8 form-group <?= $zipcode_restricted ? '' : 'd-none' ?>" id="deliverable_zipcodes_wrap">
                                        <label>Zipcodes <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="deliverable_zipcodes_text" rows="2" placeholder="One per line or comma-separated, e.g. 400001, 400002"><?= output_escaping($existing_zipcodes) ?></textarea>
                                        <small class="text-muted">Used to restrict where this product can be delivered.</small>
                                        <div id="deliverable_zipcodes_hidden"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-header"><i class="fas fa-sliders-h"></i> Additional Settings</div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card card-light h-100">
                                        <div class="card-body">
                                            <h6><i class="fas fa-toggle-on"></i> Additional Info</h6>
                                            <div class="form-group mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_prices_inclusive_tax" id="is_prices_inclusive_tax" value="1"
                                                        <?= !empty($product_details[0]['is_prices_inclusive_tax']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_prices_inclusive_tax">Tax included in prices?</label>
                                                </div>
                                            </div>
                                            <div class="form-group mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="cod_allowed" id="cod_allowed" value="1"
                                                        <?= ($product_details[0]['cod_allowed'] ?? 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="cod_allowed">Is COD allowed?</label>
                                                </div>
                                            </div>
                                            <div class="form-group mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_returnable" id="is_returnable" value="1"
                                                        <?= !empty($product_details[0]['is_returnable']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_returnable">Is Returnable?</label>
                                                </div>
                                            </div>
                                            <div class="form-group mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_cancelable" id="is_cancelable" value="1"
                                                        <?= !empty($product_details[0]['is_cancelable']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_cancelable">Is Cancelable?</label>
                                                </div>
                                            </div>
                                            <div class="form-group mb-2 <?= empty($product_details[0]['is_cancelable']) ? 'd-none' : '' ?>" id="cancelable_till_wrap">
                                                <label for="cancelable_till" class="small mb-1">Cancelable till order is <span class="text-danger">*</span></label>
                                                <select class="form-control" name="cancelable_till" id="cancelable_till">
                                                    <?php $selected_cancelable_till = $product_details[0]['cancelable_till'] ?? 'received'; ?>
                                                    <option value="received" <?= $selected_cancelable_till === 'received' ? 'selected' : '' ?>>Received</option>
                                                    <option value="processed" <?= $selected_cancelable_till === 'processed' ? 'selected' : '' ?>>Processed</option>
                                                    <option value="shipped" <?= $selected_cancelable_till === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                </select>
                                            </div>
                                            <div class="form-group mb-0">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_attachment_required" id="is_attachment_required" value="1"
                                                        <?= !empty($product_details[0]['is_attachment_required']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_attachment_required">Require attachment on return/cancel?</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <div class="col-md-4">
                                <div class="card card-light h-100">
                                    <div class="card-body">
                                        <h6><i class="fas fa-box"></i> Digital Download <small class="text-muted d-none" id="digital_download_hint"></small></h6>
                                        <?php
                                        $selected_download_link_type = $product_details[0]['download_type'] ?? '';
                                        $existing_download_allowed = !empty($product_details[0]['download_allowed']);
                                        ?>
                                        <div class="form-group mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="download_allowed" id="download_allowed" value="on" <?= $existing_download_allowed ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="download_allowed">Downloadable file/link?</label>
                                            </div>
                                        </div>
                                        <div id="download_settings_wrap" class="<?= $existing_download_allowed ? '' : 'd-none' ?>">
                                            <div class="form-group">
                                                <label class="small mb-1">Download Type <span class="text-danger">*</span></label>
                                                <select class="form-control" name="download_link_type" id="download_link_type">
                                                    <option value="">Select</option>
                                                    <option value="add_link" <?= $selected_download_link_type === 'add_link' ? 'selected' : '' ?>>External Link</option>
                                                    <option value="self_hosted" <?= $selected_download_link_type === 'self_hosted' ? 'selected' : '' ?>>Upload File</option>
                                                </select>
                                            </div>
                                            <div class="form-group mb-0 <?= $selected_download_link_type === 'add_link' ? '' : 'd-none' ?>" id="download_link_wrap">
                                                <label class="small mb-1">Download URL <span class="text-danger">*</span></label>
                                                <input type="url" class="form-control" name="download_link" id="download_link" value="<?= $selected_download_link_type === 'add_link' && !empty($product_details[0]['download_link']) ? output_escaping($product_details[0]['download_link']) : '' ?>">
                                            </div>
                                            <div class="form-group mb-0 <?= $selected_download_link_type === 'self_hosted' ? '' : 'd-none' ?>" id="download_file_wrap">
                                                <label class="small mb-1">Download File <span class="text-danger">*</span></label>
                                                <div id="download_file_upload_container">
                                                    <a href="javascript:void(0)" class="uploadFile btn btn-outline-primary btn-sm" data-input="pro_input_zip" data-media_type="archive,document" data-isremovable="1" data-is-multiple-uploads-allowed="0" data-toggle="modal" data-target="#media-upload-modal">Upload File</a>
                                                    <div class="image-upload-section mt-2">
                                                        <?php if ($selected_download_link_type === 'self_hosted' && !empty($product_details[0]['download_link'])) : ?>
                                                            <div class="image col-md-6 col-12 shadow p-2 mb-2 bg-white rounded text-center">
                                                                <small class="d-block text-truncate"><?= output_escaping(basename($product_details[0]['download_link'])) ?></small>
                                                                <input type="hidden" name="pro_input_zip" value="<?= output_escaping($product_details[0]['download_link']) ?>">
                                                                <button type="button" class="remove-image btn btn-danger btn-xs mt-1">Remove</button>
                                                            </div>
                                                        <?php else : ?>
                                                            <input type="hidden" name="pro_input_zip" value="">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-light h-100">
                                    <div class="card-body">
                                        <h6><i class="fas fa-rupee-sign"></i> Pricing</h6>
                                        <?php
                                        // For simple/digital products the price lives on their single
                                        // product_variants row, not on the products row itself.
                                        $simple_variant = (($product_details[0]['type'] ?? 'simple_product') !== 'variable_product') && !empty($product_variants[0])
                                            ? $product_variants[0] : null;
                                        ?>
                                        <div id="simple_pricing_block">
                                            <div class="form-group">
                                                <label>Price <span class="text-danger">*</span></label>
                                                <input type="number" min="0" step="0.01" class="form-control" name="simple_price" id="simple_price" value="<?= $simple_variant['price'] ?? '' ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Special Price</label>
                                                <input type="number" min="0" step="0.01" class="form-control" name="simple_special_price" id="simple_special_price" value="<?= !empty($simple_variant['special_price']) ? $simple_variant['special_price'] : '' ?>">
                                            </div>
                                            <div class="form-row">
                                                <div class="col-6 form-group">
                                                    <label class="small mb-1">Weight (kg)</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" name="weight" value="<?= $simple_variant['weight'] ?? '' ?>">
                                                </div>
                                                <div class="col-6 form-group">
                                                    <label class="small mb-1">Height (cm)</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" name="height" value="<?= $simple_variant['height'] ?? '' ?>">
                                                </div>
                                                <div class="col-6 form-group mb-0">
                                                    <label class="small mb-1">Breadth (cm)</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" name="breadth" value="<?= $simple_variant['breadth'] ?? '' ?>">
                                                </div>
                                                <div class="col-6 form-group mb-0">
                                                    <label class="small mb-1">Length (cm)</label>
                                                    <input type="number" min="0" step="0.01" class="form-control" name="length" value="<?= $simple_variant['length'] ?? '' ?>">
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="simple_stock_management_status" value="1" <?= isset($simple_variant['stock']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="simple_stock_management_status">Track stock for this product?</label>
                                            </div>
                                            <div id="simple_stock_fields" class="<?= isset($simple_variant['stock']) ? '' : 'd-none' ?>">
                                                <div class="form-group">
                                                    <label class="small mb-1">SKU</label>
                                                    <input type="text" class="form-control" name="product_sku" value="<?= output_escaping($simple_variant['sku'] ?? '') ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="small mb-1">Total Stock</label>
                                                    <input type="number" min="0" class="form-control" name="product_total_stock" value="<?= $simple_variant['stock'] ?? '' ?>">
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label class="small mb-1">Stock Status</label>
                                                    <select class="form-control" name="simple_product_stock_status">
                                                        <option value="1" <?= (($simple_variant['availability'] ?? '1') === '1') ? 'selected' : '' ?>>In Stock</option>
                                                        <option value="0" <?= (($simple_variant['availability'] ?? '1') === '0') ? 'selected' : '' ?>>Out Of Stock</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="variable_pricing_block" class="d-none">
                                            <p class="text-muted small mb-0">Pick attribute values below and mark which ones define a variation - the variant matrix builds automatically underneath.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $pre_selected_attr_value_ids = [];
                        if (!empty($product_attributes[0]['attribute_value_ids'])) {
                            $pre_selected_attr_value_ids = array_map('intval', array_filter(explode(',', $product_attributes[0]['attribute_value_ids']), 'strlen'));
                        }
                        $pre_variation_attr_names = [];
                        if (!empty($product_variants[0]['attr_name'])) {
                            $pre_variation_attr_names = array_map('trim', explode(',', $product_variants[0]['attr_name']));
                        }
                        ?>
                        <div class="section-header"><i class="fas fa-tags"></i> Attributes</div>
                        <div class="card card-light mb-4">
                            <div class="card-body" id="attributes_section">
                                <?php if (empty($attributes_refind)) : ?>
                                    <p class="text-muted mb-0">No attributes have been set up yet. <a href="<?= base_url('seller/attributes') ?>">Manage Attributes</a></p>
                                <?php else : ?>
                                    <p class="text-muted small">Select the values that apply to this product. For a Variable Product, also tick "Use for variation" on the attributes that should generate priced variants (e.g. Size, Color).</p>
                                    <?php foreach ($attributes_refind as $attr_set_name => $attrs) : ?>
                                        <h6 class="text-muted mt-3"><?= output_escaping($attr_set_name) ?></h6>
                                        <?php foreach ($attrs as $attr_name => $values) : ?>
                                            <div class="attribute-row border rounded p-2 mb-2" data-attr-name="<?= output_escaping($attr_name) ?>">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong><?= output_escaping($attr_name) ?></strong>
                                                    <div class="form-check mb-0 variation-toggle-col d-none">
                                                        <input type="checkbox" class="form-check-input is_attribute_checked" <?= in_array($attr_name, $pre_variation_attr_names, true) ? 'checked' : '' ?>>
                                                        <label class="form-check-label small">Use for variation</label>
                                                    </div>
                                                </div>
                                                <?php foreach ($values as $val) : ?>
                                                    <div class="form-check form-check-inline">
                                                        <input type="checkbox" class="form-check-input attribute-value-checkbox" value="<?= (int) $val['id'] ?>" id="attr_val_<?= (int) $val['id'] ?>" <?= in_array((int) $val['id'], $pre_selected_attr_value_ids, true) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="attr_val_<?= (int) $val['id'] ?>"><?= output_escaping($val['text']) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="section-header d-none" id="variations_section_header"><i class="fas fa-layer-group"></i> Variations</div>
                        <div class="card card-light mb-4 d-none" id="variations_section">
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="variant_stock_management_status">
                                    <label class="form-check-label" for="variant_stock_management_status">Track stock for variants?</label>
                                </div>
                                <input type="hidden" name="variant_stock_status" id="variant_stock_status" value="1">
                                <div id="variant_stock_level_wrap" class="d-none mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="variant_stock_level_type_ui" id="stock_level_product" value="product_level" checked>
                                        <label class="form-check-label" for="stock_level_product">Same stock for all variants</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="variant_stock_level_type_ui" id="stock_level_variant" value="variant_level">
                                        <label class="form-check-label" for="stock_level_variant">Different stock per variant</label>
                                    </div>
                                    <input type="hidden" name="variant_stock_level_type" id="variant_stock_level_type" value="product_level">
                                    <div class="form-row mt-2" id="product_level_stock_fields">
                                        <div class="col-md-4 form-group">
                                            <label class="small mb-1">SKU</label>
                                            <input type="text" class="form-control" name="sku_variant_type" id="sku_variant_type">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label class="small mb-1">Total Stock</label>
                                            <input type="number" min="0" class="form-control" name="total_stock_variant_type" id="total_stock_variant_type">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label class="small mb-1">Stock Status</label>
                                            <select class="form-control" name="variant_status" id="variant_status">
                                                <option value="1">In Stock</option>
                                                <option value="0">Out Of Stock</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="text-muted mb-0" id="no-variants-added">Select attribute values above (and tick "Use for variation") to generate the variant matrix.</p>
                                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="reset_variants">Regenerate Variants</button>
                                </div>
                                <div id="variants_process"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end align-items-center">
                        <div id="submit_spinner" class="spinner-border spinner-border-sm text-primary mr-2 d-none" role="status"></div>
                        <button type="submit" class="btn btn-primary" id="submit_product_btn" disabled>
                            <?= isset($product_details[0]['id']) ? 'Update Product' : 'Create Product' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<style>
.create-product-page .section-header {font-weight: 700;border-bottom: 1px solid #e5e7eb;padding-bottom: .5rem;margin-bottom: 1rem;}
.create-product-page .top-layout {display: flex;align-items: stretch;gap: 1rem;min-height: 520px;}
.create-product-page .top-column {flex: 1;border: 1px solid #e5e7eb;border-radius: .5rem;background: #fff;padding: 1rem;display:flex;flex-direction:column;}
.create-product-page .inner-scroll {overflow-y: auto;flex:1;padding-right: .5rem;}
.create-product-page .block-title {margin-bottom: 1rem;padding-bottom: .5rem;border-bottom: 1px solid #f1f3f5;}
.create-product-page .preview-single img, .create-product-page .preview-grid img {width: 84px;height: 84px;object-fit: cover;border-radius: .25rem;}
.create-product-page .preview-grid {display:grid;grid-template-columns: repeat(auto-fill, minmax(100px,1fr));gap: .5rem;}
.create-product-page .thumb-wrapper {position: relative;border:1px solid #e5e7eb;padding: .25rem;border-radius: .25rem;}
.create-product-page .remove-thumb {position:absolute;top:2px;right:2px;border:none;background:#dc3545;color:#fff;border-radius:50%;width:20px;height:20px;line-height:16px;}
</style>
<script> 
    $(document).ready(function () {

// Parse categories from the hidden input your form already has and flatten the
// whole tree (every level, however deep) into one list. Each entry keeps its
// own leaf name, its top-level ancestor name (for the "in <Top Level>" line),
// and its full breadcrumb path (for the muted line underneath).
//
// A seller's category tree can list the same category twice — once nested
// under its real parent, and once again as its own "root" (the seller's
// assigned category_ids aren't always just top-level ids) — so we key by id
// and keep whichever occurrence has the deepest/most informative path.
var categoryById = {};
(function flattenCategoryTree(nodes, pathParts) {
    (nodes || []).forEach(function (cat) {
        var parts = pathParts.concat([cat.name]);
        var existing = categoryById[cat.id];
        if (!existing || parts.length > existing.depth) {
            categoryById[cat.id] = {
                id: cat.id,
                name: cat.name,
                fullPath: parts.join(' > '),
                depth: parts.length
            };
        }
        if (cat.children && cat.children.length) {
            flattenCategoryTree(cat.children, parts);
        }
    });
})((function () {
    try {
        return JSON.parse($('#category_tree_data').val() || '[]');
    } catch (e) {
        return [];
    }
})(), []);
var allCategories = Object.keys(categoryById).map(function (id) { return categoryById[id]; });

var $input     = $('#category_level_1_input');
var $dropdown  = $('#category_dropdown');
var $clearBtn  = $('#category_search_clear');

// Pre-fill the visible input with the current category's full path on edit.
(function () {
    var currentId = $('#selected_category_id').val();
    if (!currentId) return;
    var current = allCategories.filter(function (c) { return String(c.id) === String(currentId); })[0];
    if (current) {
        $input.val(current.fullPath);
        $clearBtn.show();
    }
})();

function renderDropdown(term) {
    $dropdown.empty();
    var term_lower = term.toLowerCase();

    var filtered = allCategories.filter(function (c) {
        return c.fullPath.toLowerCase().indexOf(term_lower) > -1;
    });

    filtered.forEach(function (cat) {
        var $item = $('<div>').addClass('category-result-item')
            .append($('<div>').addClass('category-result-name').text(cat.name));

        // Only show the path line when it adds information beyond the name
        // above it — a top-level category's path is just its own name again.
        if (cat.fullPath !== cat.name) {
            $item.append($('<div>').addClass('category-result-path').text(cat.fullPath));
        }

        $item.on('mousedown', function (e) {
            e.preventDefault();
            $input.val(cat.fullPath);
            $('#selected_category_id').val(cat.id);
            $clearBtn.show();
            $dropdown.hide();
        });

        $dropdown.append($item);
    });

    if ($dropdown.children().length > 0) {
        $dropdown.show();
    } else {
        $dropdown.hide();
    }
}

// Show all on focus
$input.on('focus', function () {
    renderDropdown($(this).val());
});

// Filter as user types
$input.on('input', function () {
    $('#selected_category_id').val('');
    $clearBtn.toggle($(this).val().length > 0);
    renderDropdown($(this).val());
});

$clearBtn.on('mousedown', function (e) {
    e.preventDefault();
    $input.val('').trigger('focus');
    $('#selected_category_id').val('');
    $clearBtn.hide();
    $dropdown.empty().hide();
});

// Hide on blur
$input.on('blur', function () {
    setTimeout(function () { $dropdown.hide(); }, 150);
});

// Close on outside click
$(document).on('click', function (e) {
    if (!$(e.target).closest('.category-combo').length) {
        $dropdown.hide();
    }
});

});
</script>

<script src="<?= base_url('assets/seller/js/product.js') ?>"></script>
<script src="<?= base_url('assets/seller/js/product-attributes.js') ?>"></script>