
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

    <?php if (empty($product_details[0]['id']) && !empty($listing_quota) && $listing_quota['limit'] !== null) :
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
                    <input type="hidden" name="deliverable_type" value="1">
                    <input type="hidden" name="attribute_values" value="">
                    <input type="hidden" name="category_id" id="selected_category_id" value="<?= isset($product_details[0]['category_id']) ? (int)$product_details[0]['category_id'] : '' ?>">
                    <input type="hidden" id="category_tree_data" value='<?= htmlspecialchars(json_encode($categories ?? []), ENT_QUOTES, "UTF-8") ?>'>
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
                                                <input type="url" class="form-control" name="video" id="video" placeholder="https://..." value="<?= ($existing_video_type === 'youtube' || $existing_video_type === 'vimeo') && !empty($product_details[0]['video']) ? output_escaping($product_details[0]['video']) : '' ?>">
                                            </div>
                                        </div>
                                        <div id="video_file_container" class="d-none">
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
                                                <option value="<?= (int)$brand['id'] ?>" <?= !empty($product_details[0]['brand']) && (int)$product_details[0]['brand'] === (int)$brand['id'] ? 'selected' : '' ?>><?= output_escaping($brand['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>HSN Code</label>
                                        <input type="text" class="form-control" name="hsn_code" id="hsn_code" value="<?= isset($product_details[0]['hsn_code']) ? output_escaping($product_details[0]['hsn_code']) : '' ?>">
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
                                            <div class="form-group mb-0">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_cancelable" id="is_cancelable" value="1"
                                                        <?= !empty($product_details[0]['is_cancelable']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_cancelable">Is Cancelable?</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <div class="col-md-4">
                                <div class="card card-light h-100">
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <div>
                                            <h6><i class="fas fa-tags"></i> Attributes</h6>
                                            <p class="text-muted">Manage reusable attributes from the attributes panel.</p>
                                        </div>
                                        <a href="<?= base_url('seller/attributes') ?>" class="btn btn-outline-primary">Manage Attributes</a>
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
                                            <div class="form-group mb-0">
                                                <label>Special Price</label>
                                                <input type="number" min="0" step="0.01" class="form-control" name="simple_special_price" id="simple_special_price" value="<?= !empty($simple_variant['special_price']) ? $simple_variant['special_price'] : '' ?>">
                                            </div>
                                        </div>
                                        <div id="variable_pricing_block" class="d-none">
                                            <div id="variant_rows">
                                                <div class="variant-row border rounded p-2 mb-2">
                                                    <input type="hidden" name="variants_ids[]" value="manual_variant">
                                                    <div class="form-group mb-2">
                                                        <label>Variant Price <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control variant-price" name="variant_price[]" min="0" step="0.01">
                                                    </div>
                                                    <div class="form-group mb-2">
                                                        <label>Variant Special Price</label>
                                                        <input type="number" class="form-control" name="variant_special_price[]" min="0" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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