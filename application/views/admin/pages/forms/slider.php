<div class="content-wrapper admin-slider-form-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-images mr-2 text-primary-theme"></i><?= (isset($fetched_data[0]['id'])) ? 'Edit Slider' : 'Add Slider' ?></h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/slider/manage-slider') ?>">Slider</a></li>
                        <li class="breadcrumb-item active"><?= (isset($fetched_data[0]['id'])) ? 'Edit' : 'Add' ?></li>
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
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-images"></i></span>
                            <h5 class="mb-0"><?= (isset($fetched_data[0]['id'])) ? 'Edit Slider' : 'Add Slider' ?></h5>
                        </div>
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/slider/add_slider'); ?>" method="POST" id="payment_setting_form" enctype="multipart/form-data">
                            <div class="card-body">

                                <div class="form-group">
                                    <?php if (isset($fetched_data[0]['id'])) {
                                    ?>
                                        <input type="hidden" name="edit_slider" value="<?= html_escape($fetched_data[0]['id']) ?>">
                                    <?php } ?>
                                    <label for="slider_type">Type <span class='text-danger text-sm'>*</span> </label>
                                    <select name="slider_type" id="slider_type" class="form-control type_event_trigger" required="">
                                        <option value=" ">Select Type</option>
                                        <option value="default" <?= (@$fetched_data[0]['type'] == "default") ? 'selected' : ' ' ?>>Default</option>
                                        <option value="categories" <?= (@$fetched_data[0]['type'] == "categories") ? 'selected' : ' ' ?>>Category</option>
                                        <option value="products" <?= (@$fetched_data[0]['type'] == "products") ? 'selected' : ' ' ?>>Product</option>
                                        <option value="slider_url" <?= (@$fetched_data[0]['type'] == "slider_url") ? 'selected' : ' ' ?>>Slider URL</option>
                                    </select>
                                </div>
                                <div id="type_add_html">
                                    <?php $hiddenStatus = (isset($fetched_data[0]['id']) && $fetched_data[0]['type']  == 'categories') ? '' : 'd-none' ?>
                                    <div class="form-group slider-categories <?= $hiddenStatus ?> ">

                                        <label for="category_id"> Categories <span class='text-danger text-sm'>*</span></label>
                                        <select name="category_id" class="form-control">
                                            <option value="">Select category </option>
                                            <?php
                                            if (!empty($categories)) {
                                                foreach ($categories as $row) {
                                                    $selected = ($row['id'] == @$fetched_data[0]['type_id'] && strtolower(@$fetched_data[0]['type']) == 'categories') ? 'selected' : '';
                                            ?>
                                                    <option value="<?= $row['id'] ?>" <?= $selected ?>> <?= html_escape($row['name']) ?></option>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <?php $hiddenStatus = (isset($fetched_data[0]['id']) && $fetched_data[0]['type']  == 'slider_url') ? '' : 'd-none' ?>
                                    <div class="form-group slider-url <?= $hiddenStatus ?> ">

                                        <label for="slider_url"> Link <span class='text-danger text-sm'>*</span></label>
                                        <input type="text" class="form-control" placeholder="https://example.com" name="link" value="<?= isset($fetched_data[0]['link'])?html_escape($fetched_data[0]['link']):"" ?>">
                                    </div>
                                    <?php $hiddenStatus = (isset($fetched_data[0]['id']) && $fetched_data[0]['type']  == 'products') ? '' : 'd-none' ?>
                                    <div class="form-group row slider-products <?= $hiddenStatus ?>">
                                        <label for="product_id" class="control-label">Products <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-md-12">
                                            <select name="product_id" class="search_admin_product w-100" data-placeholder=" Type to search and select products" onload="multiselect()">
                                                <?php
                                                if (isset($fetched_data[0]['id']) && $fetched_data[0]['type']  == 'products') {
                                                    $product_details = fetch_details('products', ['id' => $fetched_data[0]['type_id']], 'id,name');
                                                    if (!empty($product_details)) {
                                                ?>
                                                        <option value="<?= $product_details[0]['id'] ?>" selected> <?= html_escape($product_details[0]['name']) ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                                <div class="form-group">
                                    <label for="image">Slider Image <span class='text-danger text-sm'>*</span><small>(Recommended Size : 1648 x 610 pixels)</small></label>
                                    <div class="col-sm-10">
                                        <?php
                                        // Same direct-upload flow built for admin/category/create-category and
                                        // admin/brand/create_brand: the button opens the device's own file picker
                                        // directly and attaches the chosen photo as soon as it finishes uploading,
                                        // instead of the old two-step "upload to the media library, then separately
                                        // select it from a list" flow. Still uploads through the same
                                        // admin/media/upload endpoint, so it still lands in the shared media library too.
                                        ?>
                                        <div>
                                            <a href="javascript:void(0)" class="direct-upload-btn btn btn-primary text-white btn-sm" data-input='image'><i class='fa fa-upload'></i> Upload Photo</a>
                                            <input type="file" class="direct-upload-file-input d-none" accept="image/*" data-input='image'>
                                        </div>
                                        <div class="image-upload-section mt-3">
                                            <?php if (file_exists(FCPATH . @$fetched_data[0]['image']) && !empty(@$fetched_data[0]['image'])) { ?>
                                                <div class="mini-image-preview">
                                                    <img src="<?= BASE_URL() . $fetched_data[0]['image'] ?>" alt="Slider image">
                                                    <button type="button" class="mini-image-remove" title="Remove image">&times;</button>
                                                    <input type="hidden" name="image" value='<?= $fetched_data[0]['image'] ?>'>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Slider' : 'Add Slider' ?></button>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">

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

<script>
    $(document).on('click', '.direct-upload-btn', function (e) {
        e.preventDefault();
        $(this).siblings('.direct-upload-file-input').trigger('click');
    });

    $(document).on('change', '.direct-upload-file-input', function () {
        var $input = $(this);
        var $btn = $input.siblings('.direct-upload-btn');
        var file = this.files && this.files[0];
        if (!file) {
            return;
        }

        var inputName = $input.data('input');
        var $section = $btn.closest('.form-group').find('.image-upload-section');

        var formData = new FormData();
        formData.append('documents[]', file);
        formData.append(csrfName, csrfHash);

        var originalBtnHtml = $btn.html();

        $.ajax({
            url: "<?= base_url('admin/media/upload') ?>",
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function () {
                $btn.html('<i class="fa fa-spinner fa-spin"></i> Uploading...').addClass('disabled');
            },
            success: function (result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName;
                    csrfHash = result.csrfHash;
                }

                if (result.error || !result.uploaded_files || !result.uploaded_files.length) {
                    iziToast.error({ message: result.message || 'Upload failed. Please try again.' });
                    return;
                }

                var uploaded = result.uploaded_files[0];
                var path = uploaded.sub_directory + uploaded.name;
                var imageUrl = base_url + path;

                $section.html(
                    '<div class="mini-image-preview">' +
                    '<img src="' + imageUrl + '" alt="' + $('<div>').text(uploaded.name).html() + '">' +
                    '<button type="button" class="mini-image-remove" title="Remove image">&times;</button>' +
                    '<input type="hidden" name="' + inputName + '" value="' + path + '">' +
                    '</div>'
                );

                iziToast.success({ message: 'Photo uploaded.' });
            },
            error: function () {
                iziToast.error({ message: 'Something went wrong uploading the photo. Please try again.' });
            },
            complete: function () {
                $btn.html(originalBtnHtml).removeClass('disabled');
                $input.val('');
            }
        });
    });

    // Dedicated handler for these compact previews (rather than reusing the shared global
    // .remove-image handler, which deletes its whole wrapper - including the hidden input).
    // On edit, Slider::add_slider() only clears the stored image when the corresponding hidden
    // input is still present, so the input must survive removal, just emptied out. In practice
    // "image" is required on every slider submit, so clearing it and not re-uploading will
    // correctly fail validation rather than silently save a slider with no image.
    $(document).on('click', '.mini-image-remove', function (e) {
        e.preventDefault();
        var $preview = $(this).closest('.mini-image-preview');
        var $hiddenInput = $preview.find('input[type="hidden"]');
        var name = $hiddenInput.attr('name');
        $preview.replaceWith('<input type="hidden" name="' + name + '" value="">');
    });
</script>

<style>
    .admin-slider-form-page .text-primary-theme { color: var(--color-orange); }

    .admin-slider-form-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-slider-form-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .admin-slider-form-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-slider-form-page .header-icon.bg-set { background: var(--color-orange); }

    .mini-image-preview {
        position: relative;
        display: inline-block;
        width: 110px;
        height: 110px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
    }
    .mini-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #fff;
        display: block;
    }
    .mini-image-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.55);
        color: #fff;
        font-size: 15px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .mini-image-remove:hover {
        background: var(--color-orange-dark, #db7323);
    }
</style>
