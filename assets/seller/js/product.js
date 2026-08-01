
(function ($) {
    'use strict';

    var state = {
        otherImages: [],
        categoryCache: {},
        isSubmitting: false
    };

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>'"]/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function uploadFile(file, cb) {
        var form = $('#save-product');
        var formData = new FormData();
        formData.append('documents[]', file, file.name);
        formData.append(csrfName, csrfHash);

        $.ajax({
            url: form.data('media-upload-url'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                if (typeof res === 'string') { try { res = JSON.parse(res); } catch(e) {} }
                if (res.csrfName) csrfName = res.csrfName;
                if (res.csrfHash) csrfHash = res.csrfHash;
                cb(null, res);
            },
            error: function () { cb(new Error('Upload failed')); }
        });
    }

    /* ── Main image ───────────────────────────────────────────── */
    function handleMainImageUpload() {
        $('#main_image_input').on('change', function () {
            var file = this.files[0];
            if (!file) return;
            uploadFile(file, function (err, res) {
                if (err || res.error || !res.files || !res.files[0]) return;
                var uploaded = res.files[0];
                var path = uploaded.sub_directory + uploaded.name;
                $('#pro_input_image').val(path);
                $('#main_image_preview').html(
                    '<div class="thumb-wrapper d-inline-block">' +
                    '<button type="button" class="remove-thumb" data-role="remove-main">&times;</button>' +
                    '<img src="' + escapeHtml(uploaded.url) + '" alt="Main"></div>'
                );
                validateForm();
            });
        });

        $('#main_image_preview').on('click', '[data-role="remove-main"]', function () {
            $('#pro_input_image').val('');
            $('#main_image_input').val('');
            $('#main_image_preview').empty();
            validateForm();
        });
    }

    /* ── Other images ─────────────────────────────────────────── */
    function handleOtherImagesUpload() {
        $('#other_images_input').on('change', function () {
            var files = Array.from(this.files || []);
            files.forEach(function (file) {
                uploadFile(file, function (err, res) {
                    if (err || res.error || !res.files || !res.files[0]) return;
                    var uploaded = res.files[0];
                    var path = uploaded.sub_directory + uploaded.name;
                    state.otherImages.push(path);
                    $('#other_images_preview').append(
                        '<div class="thumb-wrapper" data-path="' + escapeHtml(path) + '">' +
                        '<button type="button" class="remove-thumb" data-role="remove-other">&times;</button>' +
                        '<img src="' + escapeHtml(uploaded.url) + '" alt="Other">' +
                        '<input type="hidden" name="other_images[]" value="' + escapeHtml(path) + '"></div>'
                    );
                });
            });
            $(this).val('');
        });

        $('#other_images_preview').on('click', '[data-role="remove-other"]', function () {
            var wrapper = $(this).closest('.thumb-wrapper');
            var path = wrapper.data('path');
            state.otherImages = state.otherImages.filter(function (img) { return img !== path; });
            wrapper.remove();
        });
    }

    /* ── Video ────────────────────────────────────────────────── */
    function handleVideoUpload() {
        $('#video_type').on('change', function () {
            var type = $(this).val();
            if (type === 'self_hosted') {
                $('#video_file_container').removeClass('d-none');
                $('#video_url_container').addClass('d-none');
            } else if (type === 'youtube' || type === 'vimeo') {
                $('#video_file_container').addClass('d-none');
                $('#video_url_container').removeClass('d-none');
            } else {
                $('#video_file_container').addClass('d-none');
                $('#video_url_container').addClass('d-none');
            }
        }).trigger('change');

        $('#video_file_input').on('change', function () {
            var file = this.files[0];
            if (!file) return;
            uploadFile(file, function (err, res) {
                if (err || res.error || !res.files || !res.files[0]) return;
                var uploaded = res.files[0];
                $('#pro_input_video').val(uploaded.sub_directory + uploaded.name);
                $('#video_file_name').text(uploaded.name + ' uploaded');
            });
        });
    }

    /* ── Category dropdowns ───────────────────────────────────── */
    function renderCategoryOptions($select, rows, placeholder) {
        var options = '<option value="">' + placeholder + '</option>';
        rows.forEach(function (row) {
            options += '<option value="' + row.id + '">' + escapeHtml(row.name) + '</option>';
        });
        $select.html(options).prop('disabled', rows.length === 0);
    }

    function handleCategoryChange() {
        var treeRaw = $('#category_tree_data').val() || '[]';
        var topCategories = [];
        try { topCategories = JSON.parse(treeRaw); } catch (e) { topCategories = []; }

        renderCategoryOptions($('#category_level_1'), topCategories, 'Select Level 1');

        function fetchSubcategories(parentId, cb) {
            if (!parentId) return cb([]);
            if (state.categoryCache[parentId]) return cb(state.categoryCache[parentId]);
            $.getJSON(
                $('#save-product').data('subcategory-url'),
                { parent_id: parentId },
                function (resp) {
                    var rows = resp.rows || [];
                    state.categoryCache[parentId] = rows;
                    cb(rows);
                }
            ).fail(function () { cb([]); });
        }

        $('#category_level_1').on('change', function () {
            var level1Id = $(this).val();
            $('#selected_category_id').val(level1Id || '');
            renderCategoryOptions($('#category_level_2'), [], 'Select Level 2');
            renderCategoryOptions($('#category_level_3'), [], 'Select Level 3');
            if (level1Id) {
                fetchSubcategories(level1Id, function (rows) {
                    renderCategoryOptions($('#category_level_2'), rows, 'Select Level 2');
                });
            }
            validateForm();
        });

        $('#category_level_2').on('change', function () {
            var level2Id = $(this).val();
            $('#selected_category_id').val(level2Id || $('#category_level_1').val() || '');
            renderCategoryOptions($('#category_level_3'), [], 'Select Level 3');
            if (level2Id) {
                fetchSubcategories(level2Id, function (rows) {
                    renderCategoryOptions($('#category_level_3'), rows, 'Select Level 3');
                });
            }
            validateForm();
        });

        $('#category_level_3').on('change', function () {
            $('#selected_category_id').val(
                $(this).val() ||
                $('#category_level_2').val() ||
                $('#category_level_1').val() || ''
            );
            validateForm();
        });
    }

    /* ── Product type toggle ──────────────────────────────────── */
    function handleProductTypeChange() {
        $('#product_type').on('change', function () {
            var type = $(this).val();
            if (type === 'variable_product') {
                $('#simple_pricing_block').addClass('d-none');
                $('#variable_pricing_block').removeClass('d-none');
                $('#simple_price').prop('required', false);
                $('.variant-price').prop('required', true);
            } else {
                $('#simple_pricing_block').removeClass('d-none');
                $('#variable_pricing_block').addClass('d-none');
                $('#simple_price').prop('required', true);
                $('.variant-price').prop('required', false);
            }
            validateForm();
        }).trigger('change');
    }

    /* ── Alert ────────────────────────────────────────────────── */
    function showAlert(message, type) {
        $('#product-form-alert')
            .removeClass('d-none alert-danger alert-success text-white')
            .addClass('alert-' + type + ' text-white')
            .html(message);
    }

    /* ── Validation ───────────────────────────────────────────── 
     *
     *  FIX: short_description is required by the PHP controller
     *       but was never checked here — added hasDescription.
     *
     *  FIX: for variable_product the check now looks at ALL
     *       variant-price inputs, including any that are hidden
     *       inside a d-none block, so we scope it to the visible
     *       block only.
     * ─────────────────────────────────────────────────────────── */
    function validateForm(updateAlert) {
        if (typeof updateAlert === 'undefined') updateAlert = true;

        var type        = $('#product_type').val();
        var hasName     = $.trim($('#pro_input_text').val()) !== '';
        var hasCategory = $.trim($('#selected_category_id').val()) !== '';
        var hasImage    = $.trim($('#pro_input_image').val()) !== '';
        var hasDesc     = $.trim($('#short_description').val()) !== '';   // ← required by PHP

        var hasPrice;
        if (type === 'variable_product') {
            // only look inside the visible variant block
            hasPrice = $('#variable_pricing_block .variant-price')
                .filter(function () { return $.trim($(this).val()) !== ''; })
                .length > 0;
        } else {
            hasPrice = $.trim($('#simple_price').val()) !== '';
        }

        var valid = hasName && hasCategory && hasImage && hasDesc && hasPrice;
        $('#submit_product_btn').prop('disabled', !valid || state.isSubmitting);

        // updateAlert=false skips touching #product-form-alert — used right after an
        // AJAX response so this doesn't immediately re-hide the server's error/success
        // message the same tick it was shown (the always() handler used to call this
        // unconditionally, which is why failed submissions used to show nothing at all).
        if (!updateAlert) {
            return valid;
        }

        /* helpful inline hints while fields are still empty */
        var hints = [];
        if (!hasName)     hints.push('Product name');
        if (!hasDesc)     hints.push('Short description');
        if (!hasCategory) hints.push('Category');
        if (!hasImage)    hints.push('Main image');
        if (!hasPrice)    hints.push('Price');

        if (hints.length && hints.length < 5) {
            // show a soft hint (not an error) so the user knows what's left
            $('#product-form-alert')
                .removeClass('d-none alert-danger alert-success text-white')
                .addClass('alert-info')
                .html('Still needed: <strong>' + hints.join(', ') + '</strong>');
        } else if (hints.length === 0) {
            $('#product-form-alert').addClass('d-none');
        }

        return valid;
    }

    /* Strip HTML tags for toast display — server validation messages arrive as
     * "<p>...</p>" (CI's default form_validation delimiters). */
    function stripHtml(html) {
        return $('<div>').html(html).text().trim();
    }

    /* ── Form submit ──────────────────────────────────────────── */
    function initFormSubmit() {
        // re-validate on any change inside the form
        $('#save-product').on('input change', 'input, select, textarea', validateForm);

        $('#save-product').on('submit', function (e) {
            e.preventDefault();
            if (!validateForm() || state.isSubmitting) {
                var incompleteMsg = 'Please complete all required fields: Name, Short Description, ' +
                    'Category, Main Image and Price before submitting.';
                showAlert(incompleteMsg, 'danger');
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({ title: 'Please fix the following', message: incompleteMsg, position: 'topRight' });
                }
                return;
            }

            state.isSubmitting = true;
            $('#submit_spinner').removeClass('d-none');
            $('#submit_product_btn').prop('disabled', true);

            $.ajax({
                url: this.action,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json'
            }).done(function (res) {
                if (res.csrfName && res.csrfHash) {
                    $('input[name="' + res.csrfName + '"]').val(res.csrfHash);
                    if (typeof csrfName !== 'undefined') csrfName = res.csrfName;
                    if (typeof csrfHash !== 'undefined') csrfHash = res.csrfHash;
                }
                if (res.error) {
                    var errorMsg = stripHtml(res.message) || 'Unable to save product. Please check the form and try again.';
                    showAlert(res.message || 'Unable to save product.', 'danger');
                    if (typeof iziToast !== 'undefined') {
                        iziToast.error({ title: 'Please fix the following', message: errorMsg, position: 'topRight' });
                    } else {
                        alert(errorMsg);
                    }
                } else {
                    var successMsg = res.message || 'Product saved successfully.';
                    showAlert(successMsg, 'success');
                    if (typeof iziToast !== 'undefined') {
                        iziToast.success({ message: successMsg, position: 'topRight' });
                    }
                    var redirectUrl = res.redirect || res.redirect||redirectBase;
                    console.log(redirectUrl);
                    setTimeout(function () {
                        window.location.href = redirectUrl;
                    }, 1500);
                }
            }).fail(function () {
                var failMsg = 'Failed to submit form. Please try again.';
                showAlert(failMsg, 'danger');
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({ message: failMsg, position: 'topRight' });
                } else {
                    alert(failMsg);
                }
            }).always(function () {
                state.isSubmitting = false;
                $('#submit_spinner').addClass('d-none');
                validateForm(false);
            });
        });
    }

    /* ── Bootstrap ────────────────────────────────────────────── */
    $(function () {
        if (!$('#save-product').length) return;
        handleMainImageUpload();
        handleOtherImagesUpload();
        handleVideoUpload();
        handleCategoryChange();
        handleProductTypeChange();
        initFormSubmit();
        validateForm();         // set initial button & hint state
    });

    /* ── Public API (kept for backward compat) ────────────────── */
    window.handleMainImageUpload   = handleMainImageUpload;
    window.handleOtherImagesUpload = handleOtherImagesUpload;
    window.handleVideoUpload       = handleVideoUpload;
    window.handleCategoryChange    = handleCategoryChange;
    window.handleProductTypeChange = handleProductTypeChange;
    window.validateForm            = validateForm;

})(jQuery);