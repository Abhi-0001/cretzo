<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4><?= (isset($fetched_data[0]['id'])) ? 'Edit Brand' : 'Add Brand' ?></h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/brand') ?>">Brand</a></li>
                        <li class="breadcrumb-item active"><?= (isset($fetched_data[0]['id'])) ? 'Edit' : 'Add' ?></li>
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
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/brand/add_brand'); ?>" method="POST" id="add_product_form" enctype="multipart/form-data">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" name="edit_brand" value="<?= @$fetched_data[0]['id'] ?>">
                            <?php } ?>
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="brand_input_name" class="col-sm-2 col-form-label">Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="brand_input_name" placeholder="Brand Name" name="brand_input_name" value="<?= isset($fetched_data[0]['name']) ? html_escape($fetched_data[0]['name']) : "" ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="image">Main Image <span class='text-danger text-sm'>*</span><small>(Recommended Size : 131 x 131 pixels)</small></label>
                                    <div class="col-sm-10">
                                        <?php
                                        // Same direct-upload flow built for admin/category/create-category: the button
                                        // opens the device's own file picker directly and attaches the chosen photo as
                                        // soon as it finishes uploading, instead of the old two-step "upload to the media
                                        // library, then separately select it from a list" flow. Still uploads through the
                                        // same admin/media/upload endpoint, so it still lands in the shared media library too.
                                        ?>
                                        <div>
                                            <a href="javascript:void(0)" class="direct-upload-btn btn btn-primary text-white btn-sm" data-input='brand_input_image'><i class='fa fa-upload'></i> Upload Photo</a>
                                            <input type="file" class="direct-upload-file-input d-none" accept="image/*" data-input='brand_input_image'>
                                        </div>
                                        <div class="image-upload-section mt-3">
                                            <?php if (file_exists(FCPATH . @$fetched_data[0]['image']) && !empty(@$fetched_data[0]['image'])) { ?>
                                                <div class="mini-image-preview">
                                                    <img src="<?= BASE_URL() . $fetched_data[0]['image'] ?>" alt="Brand image">
                                                    <button type="button" class="mini-image-remove" title="Remove image">&times;</button>
                                                    <input type="hidden" name="brand_input_image" value='<?= $fetched_data[0]['image'] ?>'>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Brand' : 'Add Brand' ?></button>
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
    // On edit, Brand::add_brand() only clears the stored image when the corresponding hidden
    // input is still present with an empty value, so the input must survive removal, just
    // emptied out.
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
