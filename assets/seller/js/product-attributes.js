/*
 * Seller product form — everything added to reach parity with the admin
 * product form beyond what assets/seller/js/product.js already handles:
 * the small field toggles (deliverable zipcodes, cancelable-till, digital
 * download, stock tracking) and the attribute/variant picker.
 *
 * Deliberately leaner than admin's version (assets/admin/custom/custom.js):
 * every attribute is shown upfront (grouped by attribute set) instead of a
 * dropdown-driven "add attribute row" flow, so there's no dependency on
 * select2 or a drag-reorder library, which the seller pages don't load.
 * Attribute-value text is read straight from each checkbox's own <label> in
 * the DOM (it's already server-rendered there) instead of a round trip to
 * an ajax endpoint, since the picker always shows every available value.
 */
(function ($) {
    'use strict';

    if (!$('#save-product').length) {
        return;
    }

    var INCLUDED = '2';
    var EXCLUDED = '3';

    /* ── Deliverable zipcodes ─────────────────────────────────────────── */
    function syncDeliverableType() {
        var restricted = ($('#deliverable_type').val() === INCLUDED || $('#deliverable_type').val() === EXCLUDED);
        $('#deliverable_zipcodes_wrap').toggleClass('d-none', !restricted);
        syncDeliverableZipcodesHidden();
    }

    function syncDeliverableZipcodesHidden() {
        var $wrap = $('#deliverable_zipcodes_hidden');
        $wrap.empty();
        if ($('#deliverable_zipcodes_wrap').hasClass('d-none')) {
            return;
        }
        var raw = $('#deliverable_zipcodes_text').val() || '';
        raw.split(/[\s,]+/).map(function (code) { return code.trim(); }).filter(Boolean).forEach(function (code) {
            $wrap.append($('<input>').attr({ type: 'hidden', name: 'deliverable_zipcodes[]' }).val(code));
        });
    }

    /* ── Cancelable till ──────────────────────────────────────────────── */
    function syncCancelableTill() {
        $('#cancelable_till_wrap').toggleClass('d-none', !$('#is_cancelable').is(':checked'));
    }

    /* ── Digital download ─────────────────────────────────────────────── */
    function syncDownloadAllowed() {
        $('#download_settings_wrap').toggleClass('d-none', !$('#download_allowed').is(':checked'));
    }

    function syncDownloadLinkType() {
        var type = $('#download_link_type').val();
        $('#download_link_wrap').toggleClass('d-none', type !== 'add_link');
        $('#download_file_wrap').toggleClass('d-none', type !== 'self_hosted');
    }

    /* ── Simple-product stock tracking ────────────────────────────────── */
    function syncSimpleStock() {
        $('#simple_stock_fields').toggleClass('d-none', !$('#simple_stock_management_status').is(':checked'));
    }

    /* ── Variant stock tracking ───────────────────────────────────────── */
    function isVariantStockTrackedPerRow() {
        return $('#variant_stock_management_status').is(':checked') &&
            $('input[name="variant_stock_level_type_ui"]:checked').val() === 'variant_level';
    }

    function syncVariantStock() {
        var enabled = $('#variant_stock_management_status').is(':checked');
        // Product_model::add_product() enables its stock branch when
        // variant_stock_status === '0' — an inverted-looking but pre-existing
        // convention kept as-is rather than "fixed" here.
        $('#variant_stock_status').val(enabled ? '0' : '1');
        $('#variant_stock_level_wrap').toggleClass('d-none', !enabled);

        var level = $('input[name="variant_stock_level_type_ui"]:checked').val() || 'product_level';
        $('#variant_stock_level_type').val(level);
        $('#product_level_stock_fields').toggleClass('d-none', level !== 'product_level');

        $('#variants_process .variant-row-stock').toggleClass('d-none', !isVariantStockTrackedPerRow());
    }

    /* ── Attribute selection → variant matrix ─────────────────────────── */
    function escapeHtml(text) {
        return String(text == null ? '' : text).replace(/[&<>'"]/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function getSelectedAttributeState() {
        var forVariation = [];
        var anySelected = false;
        $('.attribute-row').each(function () {
            var values = [];
            $(this).find('.attribute-value-checkbox:checked').each(function () {
                values.push({ id: parseInt($(this).val(), 10), text: $.trim($(this).next('label').text()) });
            });
            if (!values.length) {
                return;
            }
            anySelected = true;
            if ($(this).find('.is_attribute_checked').is(':checked')) {
                forVariation.push(values);
            }
        });
        return { forVariation: forVariation, anySelected: anySelected };
    }

    function getPermutation(arrays) {
        return arrays.reduce(function (acc, curr) {
            var result = [];
            acc.forEach(function (a) {
                curr.forEach(function (c) {
                    result.push(a.concat([c]));
                });
            });
            return result;
        }, [[]]);
    }

    function syncAttributeValuesHidden() {
        var ids = [];
        $('.attribute-value-checkbox:checked').each(function () { ids.push($(this).val()); });
        $('input[name="attribute_values"]').val(ids.join(','));
    }

    function findExistingRow(existingRows, comboIds) {
        var key = comboIds.slice().sort(function (a, b) { return a - b; }).join(',');
        return (existingRows || []).filter(function (row) {
            var rowKey = (row.variant_ids || '').split(',').map(function (x) { return parseInt(x, 10); })
                .sort(function (a, b) { return a - b; }).join(',');
            return rowKey === key;
        })[0];
    }

    function variantRowTemplate(combo, index, existing) {
        existing = existing || {};
        var idsValue = combo.map(function (v) { return v.id; }).join(',');
        var attrColsHtml = combo.map(function (v) {
            return '<div class="col-auto">' +
                '<input type="text" class="form-control form-control-sm" value="' + escapeHtml(v.text) + '" readonly style="width:auto;">' +
                '</div>';
        }).join('');

        var images = [];
        try { images = existing.images ? JSON.parse(existing.images) : []; } catch (e) { images = []; }
        var existingImagesHtml = images.filter(Boolean).map(function (imgPath) {
            return '<div class="image col-md-3 col-6 shadow p-2 mb-2 bg-white rounded text-center">' +
                '<div class="image-upload-div"><img class="img-fluid" src="' + base_url + escapeHtml(imgPath) + '"></div>' +
                '<input type="hidden" name="variant_images[' + index + '][]" value="' + escapeHtml(imgPath) + '">' +
                '<button type="button" class="remove-image btn btn-danger btn-xs mt-1">Remove</button></div>';
        }).join('');

        var stockHidden = !isVariantStockTrackedPerRow() ? ' d-none' : '';
        var availability = String(existing.availability == null ? '1' : existing.availability);

        return '' +
            '<div class="form-group row align-items-center border rounded p-2 mb-2 variant-row" data-index="' + index + '">' +
            (existing.id ? '<input type="hidden" name="edit_variant_id[]" value="' + existing.id + '">' : '') +
            '<input type="hidden" name="variants_ids[]" value="' + idsValue + '">' +
            attrColsHtml +
            '<div class="col-auto ml-auto"><button type="button" class="btn btn-tool text-danger remove_variant" title="Remove"><i class="far fa-times-circle"></i></button></div>' +
            '<div class="col-12">' +
            '<div class="form-row mt-2">' +
            '<div class="col-md-3 form-group"><label class="small mb-1">Price <span class="text-danger">*</span></label>' +
            '<input type="number" min="0" step="0.01" class="form-control variant-price" name="variant_price[]" value="' + escapeHtml(existing.price || '') + '"></div>' +
            '<div class="col-md-3 form-group"><label class="small mb-1">Special Price</label>' +
            '<input type="number" min="0" step="0.01" class="form-control" name="variant_special_price[]" value="' + escapeHtml(existing.special_price || '') + '"></div>' +
            '<div class="col-md-3 form-group variant-row-stock' + stockHidden + '"><label class="small mb-1">SKU</label>' +
            '<input type="text" class="form-control" name="variant_sku[]" value="' + escapeHtml(existing.sku || '') + '"></div>' +
            '<div class="col-md-3 form-group variant-row-stock' + stockHidden + '"><label class="small mb-1">Stock</label>' +
            '<input type="number" min="0" class="form-control mb-1" name="variant_total_stock[]" value="' + escapeHtml(existing.stock || '') + '">' +
            '<select class="form-control" name="variant_level_stock_status[]">' +
            '<option value="1" ' + (availability === '1' ? 'selected' : '') + '>In Stock</option>' +
            '<option value="0" ' + (availability === '0' ? 'selected' : '') + '>Out Of Stock</option>' +
            '</select></div>' +
            '</div>' +
            '<div class="form-row">' +
            '<div class="col-md-3 form-group"><label class="small mb-1">Weight</label><input type="number" min="0" step="0.01" class="form-control" name="weight[]" value="' + escapeHtml(existing.weight || '') + '"></div>' +
            '<div class="col-md-3 form-group"><label class="small mb-1">Height</label><input type="number" min="0" step="0.01" class="form-control" name="height[]" value="' + escapeHtml(existing.height || '') + '"></div>' +
            '<div class="col-md-3 form-group"><label class="small mb-1">Breadth</label><input type="number" min="0" step="0.01" class="form-control" name="breadth[]" value="' + escapeHtml(existing.breadth || '') + '"></div>' +
            '<div class="col-md-3 form-group"><label class="small mb-1">Length</label><input type="number" min="0" step="0.01" class="form-control" name="length[]" value="' + escapeHtml(existing.length || '') + '"></div>' +
            '</div>' +
            '<div class="form-group mb-0">' +
            '<label class="small mb-1 d-block">Images</label>' +
            '<a href="javascript:void(0)" class="uploadFile btn btn-outline-primary btn-sm" data-input="variant_images[' + index + '][]" data-isremovable="1" data-is-multiple-uploads-allowed="1" data-toggle="modal" data-target="#media-upload-modal">Upload</a>' +
            '<div class="image-upload-section row mt-2">' + existingImagesHtml + '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
    }

    function renderVariants(combos, existingRows) {
        var $container = $('#variants_process');
        $container.empty();
        combos.forEach(function (combo, index) {
            var comboIds = combo.map(function (v) { return v.id; });
            var existing = findExistingRow(existingRows, comboIds) || {};
            $container.append(variantRowTemplate(combo, index, existing));
        });
        $('#no-variants-added').toggleClass('d-none', combos.length > 0);
        $('#reset_variants').toggleClass('d-none', combos.length === 0)
            .removeClass('btn-warning').addClass('btn-outline-secondary');
        if (typeof window.validateForm === 'function') {
            window.validateForm();
        }
    }

    // variant_images[{index}][] bakes the row's position into the field name (unlike
    // every sibling per-variant field, which is a flat [] the model re-numbers by
    // submission order) — removing any row but the last one desyncs that baked index
    // from the row's new position, so images end up attached to the wrong variant or
    // dropped entirely. Re-stamp every remaining row's index after any removal.
    function reindexVariantRows() {
        $('#variants_process .variant-row').each(function (newIndex) {
            var $row = $(this).attr('data-index', newIndex);
            $row.find('input[name^="variant_images["]').each(function () {
                $(this).attr('name', 'variant_images[' + newIndex + '][]');
            });
            $row.find('.uploadFile[data-input^="variant_images["]').attr('data-input', 'variant_images[' + newIndex + '][]');
        });
    }

    function currentExistingRows() {
        var raw = $('#existing_variants_data').val();
        try { return JSON.parse(raw) || []; } catch (e) { return []; }
    }

    function regenerateVariants() {
        var state = getSelectedAttributeState();
        if (!state.forVariation.length) {
            renderVariants([], []);
            return;
        }
        renderVariants(getPermutation(state.forVariation), currentExistingRows());
    }

    function onAttributeSelectionChanged() {
        syncAttributeValuesHidden();
        if (!$('#variants_process').children().length) {
            regenerateVariants();
        } else {
            $('#reset_variants').removeClass('d-none btn-outline-secondary').addClass('btn-warning');
        }
    }

    function loadInitialVariants() {
        var rows = currentExistingRows();
        if (!rows.length) {
            return;
        }
        var combos = rows.map(function (row) {
            var ids = (row.variant_ids || '').split(',');
            var texts = (row.variant_values || '').split(',');
            return ids.map(function (id, i) { return { id: parseInt(id, 10), text: texts[i] || '' }; });
        });
        renderVariants(combos, rows);
    }

    /* ── Product type ─────────────────────────────────────────────────── */
    function syncForProductType() {
        var isVariable = ($('#product_type').val() === 'variable_product');
        $('.variation-toggle-col').toggleClass('d-none', !isVariable);
        $('#variations_section, #variations_section_header').toggleClass('d-none', !isVariable);
    }

    /* ── Wiring ────────────────────────────────────────────────────────── */
    $(document).on('change', '.attribute-value-checkbox, .is_attribute_checked', onAttributeSelectionChanged);
    $(document).on('input change', '#deliverable_zipcodes_text', syncDeliverableZipcodesHidden);
    $(document).on('click', '#variants_process .remove_variant', function () {
        $(this).closest('.variant-row').remove();
        reindexVariantRows();
        $('#no-variants-added').toggleClass('d-none', $('#variants_process').children().length > 0);
        if (typeof window.validateForm === 'function') {
            window.validateForm();
        }
    });
    $(document).on('click', '#reset_variants', function () {
        if (!$('#variants_process').children().length ||
            confirm('Regenerate the variant list from the current attribute selection? Prices/stock for combinations that no longer exist will be lost; matching combinations keep their data.')) {
            regenerateVariants();
        }
    });

    $(function () {
        $('#deliverable_type').on('change', syncDeliverableType);
        $('#is_cancelable').on('change', syncCancelableTill);
        $('#download_allowed').on('change', syncDownloadAllowed);
        $('#download_link_type').on('change', syncDownloadLinkType);
        $('#simple_stock_management_status').on('change', syncSimpleStock);
        $('#variant_stock_management_status').on('change', syncVariantStock);
        $('input[name="variant_stock_level_type_ui"]').on('change', syncVariantStock);
        $('#product_type').on('change', syncForProductType);

        syncDeliverableType();
        syncCancelableTill();
        syncDownloadAllowed();
        syncDownloadLinkType();
        syncSimpleStock();
        syncVariantStock();
        syncForProductType();
        loadInitialVariants();
        syncAttributeValuesHidden();
    });

})(jQuery);
