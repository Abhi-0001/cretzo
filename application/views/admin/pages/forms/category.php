<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Add Category</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Category</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/category/add_category'); ?>" method="POST" id="add_product_form" enctype="multipart/form-data">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" name="edit_category" value="<?= html_escape(@$fetched_data[0]['id']) ?>">
                            <?php } ?>
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="category_input_name" class="col-sm-2 col-form-label">Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="category_input_name" placeholder="Category Name" name="category_input_name" value="<?= html_escape(isset($fetched_data[0]['name'])?output_escaping($fetched_data[0]['name']):"") ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="category_parent" class="col-sm-2 col-form-label">Select Parent</label>
                                    <div class="col-sm-10">
                                        <select id="category_parent" name="category_parent">
                                            <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'Select Parent' ?>
                                            </option>
                                            <?php
                                            $selected_val = (isset($fetched_data[0]['id']) &&  !empty($fetched_data[0]['id'])) ? $fetched_data[0]['parent_id'] : '';
                                            $selected_vals = explode(',', $selected_val);
                                            echo get_categories_option_html($categories, $selected_vals);

                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="image">Main Image <span class='text-danger text-sm'>*</span><br><small>(Recommended Size : 131 x 131 pixels)</small></label>
                                        <?php
                                        // Previously opened the shared media-library modal: upload a file there, then
                                        // separately click it in a list and click "Choose Media" to actually attach it
                                        // to this form - two steps for what should be one. This button now opens the
                                        // device's own file picker directly and attaches the chosen photo as soon as
                                        // it finishes uploading, with no extra selection step. It still uploads through
                                        // the same admin/media/upload endpoint (and so still lands in the shared media
                                        // library too), so nothing about how images are stored changes - only how many
                                        // clicks it takes to attach one here.
                                        ?>
                                        <div>
                                            <a href="javascript:void(0)" class="direct-upload-btn btn btn-primary text-white btn-sm" data-input='category_input_image'><i class='fa fa-upload'></i> Upload Photo</a>
                                            <input type="file" class="direct-upload-file-input d-none" accept="image/*" data-input='category_input_image'>
                                        </div>
                                        <div class="image-upload-section mt-3">
                                            <?php if (file_exists(FCPATH . @$fetched_data[0]['image']) && !empty(@$fetched_data[0]['image'])) { ?>
                                                <div class="mini-image-preview">
                                                    <img src="<?= BASE_URL() . $fetched_data[0]['image'] ?>" alt="Category image">
                                                    <button type="button" class="mini-image-remove" title="Remove image">&times;</button>
                                                    <input type="hidden" name="category_input_image" value='<?= $fetched_data[0]['image'] ?>'>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="image">Banner Image</label>
                                        <div>
                                            <a href="javascript:void(0)" class="direct-upload-btn btn btn-primary text-white btn-sm" data-input='banner'><i class='fa fa-upload'></i> Upload Photo</a>
                                            <input type="file" class="direct-upload-file-input d-none" accept="image/*" data-input='banner'>
                                        </div>
                                        <div class="image-upload-section mt-3">
                                            <?php if (file_exists(FCPATH . @$fetched_data[0]['banner']) && !empty(@$fetched_data[0]['banner'])) { ?>
                                                <div class="mini-image-preview">
                                                    <img src="<?= BASE_URL() . $fetched_data[0]['banner'] ?>" alt="Banner image">
                                                    <button type="button" class="mini-image-remove" title="Remove image">&times;</button>
                                                    <input type="hidden" name="banner" value='<?= $fetched_data[0]['banner'] ?>'>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning ">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Category' : 'Add Category' ?></button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
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