<?php
$is_edit = isset($fetched_data[0]['id']);
// Effective rate of every category, so the hint under the commission field can say what a blank
// value will inherit from the chosen parent.
$commission_rates = [];
foreach ($this->category_model->commission_overview() as $c) {
    $commission_rates[$c['id']] = ['rate' => $c['effective_rate'], 'name' => $c['name']];
}
$this->config->load('commission', true);
$commission_default = (float) $this->config->item('default_commission_percent', 'commission');
$own_commission = (isset($fetched_data[0]['commission_percent']) && $fetched_data[0]['commission_percent'] !== null && $fetched_data[0]['commission_percent'] !== '')
    ? rtrim(rtrim($fetched_data[0]['commission_percent'], '0'), '.')
    : '';
$img_ok = $is_edit && !empty($fetched_data[0]['image']) && file_exists(FCPATH . $fetched_data[0]['image']);
$ban_ok = $is_edit && !empty($fetched_data[0]['banner']) && file_exists(FCPATH . $fetched_data[0]['banner']);
?>
<div class="content-wrapper cat-form-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sitemap mr-2 cat-accent"></i><?= $is_edit ? 'Edit Category' : 'Add Category' ?></h4>
                    <p class="text-muted mb-0 small">
                        <?= $is_edit ? 'Update the name, parent, commission or pictures of this category.' : 'Create a main category, or pick a parent to add a subcategory under it.' ?>
                    </p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/category') ?>">Categories</a></li>
                        <li class="breadcrumb-item active"><?= $is_edit ? 'Edit' : 'Add' ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <form class="form-horizontal form-submit-event" action="<?= base_url('admin/category/add_category'); ?>" method="POST" id="add_product_form" enctype="multipart/form-data">
                <?php if ($is_edit) { ?>
                    <input type="hidden" name="edit_category" value="<?= html_escape(@$fetched_data[0]['id']) ?>">
                <?php } ?>
                <div class="row">
                    <div class="col-lg-7">
                        <div class="cat-card">
                            <div class="cat-card-head"><span class="cat-card-icon"><i class="fas fa-pen"></i></span><h5>Details</h5></div>
                            <div class="cat-card-body">
                                <div class="form-group">
                                    <label for="category_input_name">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control cat-input" id="category_input_name" placeholder="e.g. Handmade Rings" name="category_input_name" value="<?= html_escape($is_edit ? unslash($fetched_data[0]['name']) : '') ?>">
                                    <small class="form-text text-muted">A parent can't have two subcategories with the same name, but the same name can be used under different parents.</small>
                                </div>
                                <div class="form-group">
                                    <label for="category_parent">Parent category</label>
                                    <select id="category_parent" name="category_parent">
                                        <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'None (main category)' ?></option>
                                        <?php
                                        $selected_val = ($is_edit && !empty($fetched_data[0]['id'])) ? $fetched_data[0]['parent_id'] : '';
                                        $selected_vals = explode(',', $selected_val);
                                        echo get_categories_option_html($categories, $selected_vals);
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">Leave empty to create a main category.</small>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="commission_percent">Commission</label>
                                    <div class="input-group cat-commission">
                                        <input type="number" class="form-control cat-input" id="commission_percent" name="commission_percent" min="0" max="100" step="0.01" placeholder="Inherit" value="<?= html_escape($own_commission) ?>">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                    </div>
                                    <small class="form-text cat-hint" id="commission_hint"></small>
                                    <small class="form-text text-muted">Charged on the ex-GST value of every product sold in this category. Changing it only affects orders placed after you save.</small>
                                </div>
                                <script>
                                    (function () {
                                        var rates = <?= json_encode($commission_rates) ?>;
                                        var platformDefault = <?= json_encode($commission_default) ?>;
                                        function fmt(n) { return (Math.round(n * 100) / 100) + '%'; }
                                        function refreshHint() {
                                            var parentId = $('#category_parent').val();
                                            var p = parentId && rates[parentId];
                                            $('#commission_hint').text(p
                                                ? 'Leave blank to inherit ' + fmt(p.rate) + ' from "' + p.name + '".'
                                                : 'Leave blank to use the platform default of ' + fmt(platformDefault) + '.');
                                        }
                                        $(document).off('change.czcomm').on('change.czcomm', '#category_parent', refreshHint);
                                        refreshHint();
                                    })();
                                </script>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="cat-card">
                            <div class="cat-card-head"><span class="cat-card-icon"><i class="fas fa-image"></i></span><h5>Pictures</h5></div>
                            <div class="cat-card-body">
                                <?php
                                // Both buttons open the device's own file picker and attach the photo as soon
                                // as it has uploaded (admin/media/upload, so it still lands in the media library).
                                ?>
                                <div class="form-group cat-upload">
                                    <label>Main image <small class="text-muted">(optional &middot; 131 &times; 131 px)</small></label>
                                    <div class="cat-upload-row">
                                        <div class="image-upload-section">
                                            <?php if ($img_ok) { ?>
                                                <div class="mini-image-preview">
                                                    <img src="<?= BASE_URL() . $fetched_data[0]['image'] ?>" alt="Category image">
                                                    <button type="button" class="mini-image-remove" title="Remove image">&times;</button>
                                                    <input type="hidden" name="category_input_image" value='<?= $fetched_data[0]['image'] ?>'>
                                                </div>
                                            <?php } else { ?>
                                                <div class="cat-upload-empty"><i class="far fa-image"></i></div>
                                            <?php } ?>
                                        </div>
                                        <div>
                                            <a href="javascript:void(0)" class="direct-upload-btn btn cat-btn-outline btn-sm" data-input='category_input_image'><i class='fa fa-upload mr-1'></i> Upload photo</a>
                                            <input type="file" class="direct-upload-file-input d-none" accept="image/*" data-input='category_input_image'>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group cat-upload mb-0">
                                    <label>Banner image <small class="text-muted">(optional)</small></label>
                                    <div class="cat-upload-row">
                                        <div class="image-upload-section">
                                            <?php if ($ban_ok) { ?>
                                                <div class="mini-image-preview">
                                                    <img src="<?= BASE_URL() . $fetched_data[0]['banner'] ?>" alt="Banner image">
                                                    <button type="button" class="mini-image-remove" title="Remove image">&times;</button>
                                                    <input type="hidden" name="banner" value='<?= $fetched_data[0]['banner'] ?>'>
                                                </div>
                                            <?php } else { ?>
                                                <div class="cat-upload-empty"><i class="far fa-image"></i></div>
                                            <?php } ?>
                                        </div>
                                        <div>
                                            <a href="javascript:void(0)" class="direct-upload-btn btn cat-btn-outline btn-sm" data-input='banner'><i class='fa fa-upload mr-1'></i> Upload photo</a>
                                            <input type="file" class="direct-upload-file-input d-none" accept="image/*" data-input='banner'>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cat-actions">
                    <div id="error_box"></div>
                    <div class="cat-actions-btns">
                        <a href="<?= base_url('admin/category') ?>" class="btn cat-btn-ghost">Cancel</a>
                        <button type="reset" class="btn cat-btn-ghost">Reset</button>
                        <button type="submit" class="btn cat-btn-primary" id="submit_btn"><i class="fas fa-check mr-1"></i><?= $is_edit ? 'Update Category' : 'Add Category' ?></button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<style>
    .cat-form-page .cat-accent { color: var(--color-orange); }
    .cat-form-page .cat-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.06); margin-bottom: 20px; overflow: hidden; }
    .cat-form-page .cat-card-head { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid rgba(0,0,0,.06); }
    .cat-form-page .cat-card-head h5 { margin: 0; font-size: 16px; font-weight: 700; }
    .cat-form-page .cat-card-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--color-orange); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; }
    .cat-form-page .cat-card-body { padding: 20px; }
    .cat-form-page label { font-weight: 600; font-size: 13.5px; color: #2b2f36; margin-bottom: 6px; }
    .cat-form-page .cat-input { border-radius: 8px; height: 42px; border: 1px solid rgba(0,0,0,.14); box-shadow: none; }
    .cat-form-page .cat-input:focus { border-color: var(--color-orange); box-shadow: 0 0 0 3px rgba(232,131,58,.15); }
    .cat-form-page .cat-commission { max-width: 200px; }
    .cat-form-page .cat-commission .input-group-text { border-radius: 0 8px 8px 0; background: #f7f8fa; }
    .cat-form-page .cat-commission .cat-input { border-radius: 8px 0 0 8px; }
    .cat-form-page .cat-hint { color: var(--color-orange-dark); font-weight: 600; }
    .cat-form-page .select2-container { width: 100% !important; }
    .cat-form-page .select2-container .select2-selection--single { height: 42px; border-radius: 8px; border: 1px solid rgba(0,0,0,.14); }
    .cat-form-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 12px; }
    .cat-form-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }

    .cat-form-page .cat-upload-row { display: flex; align-items: center; gap: 16px; padding: 12px; border: 1px dashed rgba(0,0,0,.18); border-radius: 10px; background: #fafbfc; }
    .cat-form-page .cat-upload-empty { width: 110px; height: 110px; border-radius: 8px; background: #eef0f3; color: #b4bac4; display: flex; align-items: center; justify-content: center; font-size: 30px; }
    .cat-form-page .cat-btn-outline { border: 1px solid var(--color-orange); color: var(--color-orange-dark); background: #fff; font-weight: 600; border-radius: 20px; padding: 5px 16px; }
    .cat-form-page .cat-btn-outline:hover { background: var(--color-orange); color: #fff; }

    .cat-form-page .cat-actions { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #fff; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.06); padding: 14px 20px; margin-bottom: 24px; }
    .cat-form-page .cat-actions-btns { display: flex; gap: 10px; margin-left: auto; }
    .cat-form-page .cat-btn-primary { background: var(--color-orange); border-color: var(--color-orange); color: #fff; font-weight: 600; border-radius: 8px; padding: 8px 22px; }
    .cat-form-page .cat-btn-primary:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .cat-form-page .cat-btn-ghost { background: #fff; border: 1px solid rgba(0,0,0,.15); color: #444; font-weight: 600; border-radius: 8px; padding: 8px 18px; }
    .cat-form-page .cat-btn-ghost:hover { background: #f4f5f7; }
</style>

<script>
    // Bound on document (delegated) rather than directly on the button: this form is also
    // loaded into the category list's edit modal as an AJAX-fetched fragment
    // (application/views/admin/pages/tables/manage-category.php), so a direct .on() bound at
    // page-load time would miss that copy of the form entirely.
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
    // On edit, Category::add_category() only clears the stored image/banner when the
    // corresponding hidden input is still present with an empty value, so the input must
    // survive removal, just emptied out.
    $(document).on('click', '.mini-image-remove', function (e) {
        e.preventDefault();
        var $preview = $(this).closest('.mini-image-preview');
        var $hiddenInput = $preview.find('input[type="hidden"]');
        var name = $hiddenInput.attr('name');
        $preview.replaceWith('<input type="hidden" name="' + name + '" value="">');
    });
</script>

<style>
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