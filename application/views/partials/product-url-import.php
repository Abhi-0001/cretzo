<?php
/**
 * "Import from a product link" panel for the Add Product form.
 * Shared by the seller and admin forms - pass $import_panel = 'seller' | 'admin'.
 *
 * The inputs here deliberately have no `name`, so nothing in this panel is ever
 * submitted with the product. It only fills the real fields, which the user then
 * reviews and saves as usual.
 */
$import_panel = isset($import_panel) ? $import_panel : 'seller';
?>
<div class="czimp" id="czimp" data-panel="<?= html_escape($import_panel) ?>"
     data-fetch-url="<?= base_url($import_panel . '/product/fetch_from_url') ?>"
     data-images-url="<?= base_url($import_panel . '/product/import_images') ?>">
    <div class="czimp-head">
        <span class="czimp-ico"><i class="fas fa-link"></i></span>
        <div>
            <div class="czimp-title">Import from a product link</div>
            <div class="czimp-sub">Paste a product page link (your own website, Shopify, Amazon, Flipkart...) and we'll pull in the name, description and photos for you to review.</div>
        </div>
    </div>
    <div class="czimp-row">
        <input type="url" id="czimp-url" class="form-control" placeholder="https://www.example.com/product/..." autocomplete="off" inputmode="url">
        <button type="button" id="czimp-fetch" class="btn btn-primary"><i class="fas fa-cloud-download-alt"></i> Fetch</button>
    </div>
    <div class="czimp-msg" id="czimp-msg" role="status" aria-live="polite"></div>

    <div class="czimp-preview d-none" id="czimp-preview">
        <div class="czimp-src">Found on <strong id="czimp-source"></strong>. Untick anything you don't want.</div>

        <label class="czimp-field" data-field="name">
            <input type="checkbox" checked>
            <span class="czimp-lbl">Product name</span>
            <span class="czimp-val" data-val></span>
        </label>
        <label class="czimp-field" data-field="short_description">
            <input type="checkbox" checked>
            <span class="czimp-lbl">Short description</span>
            <span class="czimp-val" data-val></span>
        </label>
        <label class="czimp-field" data-field="description">
            <input type="checkbox" checked>
            <span class="czimp-lbl">Full description</span>
            <span class="czimp-val czimp-html" data-val></span>
        </label>
        <label class="czimp-field" data-field="tags">
            <input type="checkbox" checked>
            <span class="czimp-lbl">Tags</span>
            <span class="czimp-val" data-val></span>
        </label>
        <div class="czimp-field czimp-note d-none" id="czimp-price-note">
            <span class="czimp-lbl">Price on that page</span>
            <span class="czimp-val" id="czimp-price"></span>
            <span class="czimp-hint">Shown for reference only. Enter your own price and stock below.</span>
        </div>

        <div class="czimp-field czimp-imgs-wrap d-none" id="czimp-imgs-wrap">
            <span class="czimp-lbl">Photos <small>(tap to select, &#9733; to make main)</small></span>
            <div class="czimp-imgs" id="czimp-imgs"></div>
        </div>

        <label class="czimp-own">
            <input type="checkbox" id="czimp-own">
            <span>I own this product or have the right to use these photos and text. Copying other brands' content without permission can get a listing removed.</span>
        </label>

        <div class="czimp-actions">
            <button type="button" id="czimp-apply" class="btn btn-success" disabled><i class="fas fa-check"></i> Apply to form</button>
            <button type="button" id="czimp-cancel" class="btn btn-light">Cancel</button>
        </div>
    </div>
</div>

<style>
    .czimp { background: #fff8ef; border: 1px solid #f3d9b8; border-left: 4px solid #F2822E; border-radius: 10px; padding: 16px; margin: 0 15px 18px; }
    .czimp-head { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 12px; }
    .czimp-ico { flex: 0 0 38px; height: 38px; border-radius: 50%; background: #F2822E; color: #fff; display: flex; align-items: center; justify-content: center; }
    .czimp-title { font-weight: 700; font-size: 15px; color: #1f2937; }
    .czimp-sub { font-size: 13px; color: #6b7280; margin-top: 2px; }
    .czimp-row { display: flex; gap: 8px; }
    .czimp-row .form-control { flex: 1; min-width: 0; }
    .czimp-row .btn { white-space: nowrap; }
    .czimp-msg { font-size: 13px; margin-top: 8px; min-height: 0; }
    .czimp-msg.is-error { color: #b42318; }
    .czimp-msg.is-ok { color: #2e7d32; }
    .czimp-msg.is-busy { color: #6b7280; }
    .czimp-preview { margin-top: 14px; background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 14px; }
    .czimp-src { font-size: 13px; color: #6b7280; margin-bottom: 10px; }
    .czimp-field { display: grid; grid-template-columns: 22px 130px 1fr; gap: 8px; align-items: start; padding: 8px 0; border-top: 1px solid #f1f1f1; margin: 0; cursor: pointer; font-weight: normal; }
    .czimp-field.czimp-note, .czimp-field.czimp-imgs-wrap { grid-template-columns: 130px 1fr; cursor: default; }
    .czimp-field.czimp-note { padding-left: 30px; grid-template-columns: 130px 1fr; }
    .czimp-field.is-empty { display: none; }
    .czimp-lbl { font-weight: 600; font-size: 13px; color: #374151; }
    .czimp-val { font-size: 13px; color: #111827; word-break: break-word; max-height: 110px; overflow: auto; }
    .czimp-html p, .czimp-html ul, .czimp-html ol { margin: 0 0 6px; }
    .czimp-html ul, .czimp-html ol { padding-left: 18px; }
    .czimp-hint { grid-column: 2; font-size: 12px; color: #9ca3af; }
    .czimp-imgs-wrap { display: block !important; padding-left: 0; }
    .czimp-imgs-wrap.d-none { display: none !important; }
    .czimp-imgs { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
    .czimp-img { position: relative; width: 96px; height: 96px; border: 2px solid #e5e7eb; border-radius: 8px; overflow: hidden; cursor: pointer; background: #f9fafb; }
    .czimp-img.is-on { border-color: #F2822E; }
    .czimp-img img { width: 100%; height: 100%; object-fit: cover; opacity: .45; }
    .czimp-img.is-on img { opacity: 1; }
    .czimp-img .czimp-star { position: absolute; top: 3px; right: 3px; width: 22px; height: 22px; border: 0; border-radius: 50%; background: rgba(255,255,255,.92); color: #9ca3af; line-height: 22px; padding: 0; font-size: 13px; }
    .czimp-img.is-main .czimp-star { color: #F2822E; }
    .czimp-img .czimp-badge { position: absolute; left: 0; bottom: 0; right: 0; background: #F2822E; color: #fff; font-size: 11px; text-align: center; display: none; }
    .czimp-img.is-main.is-on .czimp-badge { display: block; }
    .czimp-own { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; color: #374151; margin: 12px 0 0; padding-top: 10px; border-top: 1px solid #f1f1f1; font-weight: normal; cursor: pointer; }
    .czimp-own input { margin-top: 3px; }
    .czimp-actions { display: flex; gap: 8px; margin-top: 12px; }
    .czimp-badge-imported { display: inline-block; background: #fff3e0; color: #b45309; border: 1px solid #f3d9b8; border-radius: 10px; font-size: 11px; font-weight: 600; padding: 0 8px; margin-left: 8px; vertical-align: middle; }
    .czimp-tile { position: relative; display: inline-block; width: 140px; margin: 8px; text-align: center; vertical-align: top; }
    .czimp-tile img { max-width: 100%; max-height: 120px; object-fit: contain; border: 1px solid #eee; border-radius: 6px; background: #fff; }
    .czimp-tile-remove { position: absolute; top: -6px; right: -6px; width: 22px; height: 22px; border-radius: 50%; border: 0; background: #dc3545; color: #fff; line-height: 20px; padding: 0; font-size: 15px; }
    @media (max-width: 575.98px) {
        .czimp { margin: 0 8px 14px; padding: 12px; }
        .czimp-row { flex-direction: column; }
        .czimp-field { grid-template-columns: 22px 1fr; }
        .czimp-field .czimp-val { grid-column: 2; }
        .czimp-field.czimp-note { grid-template-columns: 1fr; padding-left: 0; }
        .czimp-hint { grid-column: 1; }
    }
</style>

<script>
    (function () {
        function ready(fn) { if (window.jQuery) { jQuery(fn); } else { document.addEventListener('DOMContentLoaded', fn); } }

        ready(function () {
            var $ = window.jQuery;
            var $root = $('#czimp');
            if (!$root.length) return;

            var fetchUrl = $root.data('fetch-url');
            var imagesUrl = $root.data('images-url');
            var found = null;       // last fetch result
            var imgState = [];      // [{url, on}] in display order; first "on" is main

            function setMsg(text, cls) { $('#czimp-msg').attr('class', 'czimp-msg ' + (cls || '')).text(text || ''); }
            function syncCsrf(res) {
                if (res && res.csrfName) { window.csrfName = res.csrfName; }
                if (res && res.csrfHash) { window.csrfHash = res.csrfHash; }
            }
            function post(url, data) {
                var d = $.extend({}, data);
                d[window.csrfName] = window.csrfHash;
                return $.ajax({ url: url, type: 'POST', data: d, dataType: 'json' }).always(function (r) {
                    syncCsrf(r && r.responseJSON ? r.responseJSON : r);
                });
            }
            function updateApply() {
                $('#czimp-apply').prop('disabled', !$('#czimp-own').is(':checked'));
            }

            // ---------- Step 1: fetch ----------
            $('#czimp-fetch').on('click', doFetch);
            $('#czimp-url').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); doFetch(); } });

            function doFetch() {
                var url = $.trim($('#czimp-url').val());
                if (!url) { setMsg('Paste a product link first.', 'is-error'); return; }
                var $btn = $('#czimp-fetch');
                var label = $btn.html();
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Fetching...');
                setMsg('Reading the page, this can take a few seconds...', 'is-busy');
                $('#czimp-preview').addClass('d-none');

                post(fetchUrl, { url: url }).done(function (res) {
                    if (res.error) { setMsg(res.message || 'Could not import that link.', 'is-error'); return; }
                    found = res.data;
                    showPreview(found);
                    setMsg('', '');
                }).fail(function (xhr) {
                    var m = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not reach the server. Please try again.';
                    setMsg(m, 'is-error');
                }).always(function () {
                    $btn.prop('disabled', false).html(label);
                });
            }

            function showPreview(d) {
                $('#czimp-source').text(d.source || 'that page');
                var map = {
                    name: d.name,
                    short_description: d.short_description,
                    description: d.description,
                    tags: (d.tags || []).join(', ')
                };
                $.each(map, function (key, val) {
                    var $f = $root.find('.czimp-field[data-field="' + key + '"]');
                    var $v = $f.find('[data-val]');
                    if (key === 'description') { $v.html(val || ''); } else { $v.text(val || ''); }
                    $f.toggleClass('is-empty', !val);
                    $f.find('input').prop('checked', !!val);
                });
                if (d.price) { $('#czimp-price').text('₹ ' + d.price); $('#czimp-price-note').removeClass('d-none'); }
                else { $('#czimp-price-note').addClass('d-none'); }

                imgState = $.map(d.images || [], function (u) { return { url: u, on: true }; });
                renderImgs();
                $('#czimp-imgs-wrap').toggleClass('d-none', !imgState.length);
                $('#czimp-own').prop('checked', false);
                updateApply();
                $('#czimp-preview').removeClass('d-none');
            }

            function renderImgs() {
                var $box = $('#czimp-imgs').empty();
                var mainDone = false;
                $.each(imgState, function (i, im) {
                    var isMain = im.on && !mainDone;
                    if (isMain) mainDone = true;
                    var $t = $('<div class="czimp-img"></div>').toggleClass('is-on', im.on).toggleClass('is-main', isMain).attr('data-i', i);
                    // referrerpolicy: many shops refuse to serve an image when the Referer is a foreign site.
                    $t.append($('<img referrerpolicy="no-referrer" loading="lazy" alt="">').attr('src', im.url).on('error', function () { $(this).closest('.czimp-img').remove(); }));
                    $t.append('<button type="button" class="czimp-star" title="Make main image">&#9733;</button><span class="czimp-badge">Main</span>');
                    $box.append($t);
                });
            }
            $(document).on('click', '#czimp-imgs .czimp-img', function (e) {
                var i = +$(this).attr('data-i');
                if ($(e.target).hasClass('czimp-star')) {
                    var im = imgState.splice(i, 1)[0]; im.on = true; imgState.unshift(im);
                } else {
                    imgState[i].on = !imgState[i].on;
                }
                renderImgs();
            });
            $('#czimp-own').on('change', updateApply);
            $('#czimp-cancel').on('click', function () { $('#czimp-preview').addClass('d-none'); setMsg('', ''); });

            // ---------- Step 2: apply ----------
            function checked(key) {
                var $f = $root.find('.czimp-field[data-field="' + key + '"]');
                return !$f.hasClass('is-empty') && $f.find('input').is(':checked');
            }
            function flag($el) {
                var $label = $el.closest('.form-group').find('label').first();
                if ($label.length && !$label.find('.czimp-badge-imported').length) {
                    $label.append('<span class="czimp-badge-imported">Imported - please check</span>');
                }
            }
            function setEditor(name, html) {
                var $ta = $('textarea[name="' + name + '"]');
                if (!$ta.length) return;
                var ed = (window.tinymce && $ta.attr('id')) ? tinymce.get($ta.attr('id')) : null;
                if (!ed && window.tinymce && tinymce.editors) {
                    $.each(tinymce.editors, function (_, e) { if (e.getElement && e.getElement() === $ta[0]) { ed = e; } });
                }
                if (ed) { ed.setContent(html); ed.save(); } else { $ta.val(html); }
                flag($ta);
            }
            function setTags(list) {
                var el = document.getElementById('tags');
                if (!el || !list.length) return;
                var tg = el.__tagify || el._tagify || el.tagify;
                if (tg && tg.addTags) { tg.removeAllTags && tg.removeAllTags(); tg.addTags(list); }
                else { el.value = list.join(','); }
                flag($(el));
            }
            function tile(file, inputName) {
                var $t = $('<div class="czimp-tile"></div>');
                $t.append($('<img alt="">').attr('src', file.url));
                $t.append('<button type="button" class="czimp-tile-remove" title="Remove" aria-label="Remove">&times;</button>');
                $t.append($('<input type="hidden">').attr('name', inputName).val(file.sub_directory + file.name));
                return $t;
            }
            $(document).on('click', '.czimp-tile-remove', function () { $(this).closest('.czimp-tile').remove(); });

            function placeImages(files) {
                var $main = $('[data-input="pro_input_image"]').first().closest('.form-group').find('.image-upload-section').first();
                var $other = $('[data-input="other_images[]"]').first().closest('.form-group').find('.image-upload-section').first();
                if ($main.length && files.length) {
                    $main.removeClass('d-none').empty().append(tile(files[0], 'pro_input_image'));
                    flag($main);
                }
                if ($other.length && files.length > 1) {
                    $other.removeClass('d-none');
                    $.each(files.slice(1), function (_, f) { $other.append(tile(f, 'other_images[]')); });
                    flag($other);
                }
            }

            $('#czimp-apply').on('click', function () {
                if (!found) return;
                var $btn = $(this);
                var label = $btn.html();

                if (checked('name')) { var $n = $('input[name="pro_input_name"]').val(found.name); flag($n); }
                if (checked('short_description')) { var $s = $('textarea[name="short_description"]').val(found.short_description); flag($s); }
                if (checked('description')) { setEditor('pro_input_description', found.description); }
                if (checked('tags')) {
                    var list = found.tags || [];
                    if (found.brand && $.inArray(found.brand, list) === -1) { list = [found.brand].concat(list); }
                    setTags(list);
                }

                var picked = $.map(imgState, function (im) { return im.on ? im.url : null; });
                if (!picked.length) { done('Details applied. Please review each field.'); return; }

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving photos...');
                post(imagesUrl, { urls: picked }).done(function (res) {
                    if (res.error) { done('Details applied, but photos could not be imported: ' + (res.message || ''), true); return; }
                    placeImages(res.files);
                    done('Applied. ' + res.files.length + ' photo(s) added' + (res.skipped ? ', ' + res.skipped + ' skipped (site blocked them)' : '') + '. Please review everything before saving.');
                }).fail(function () {
                    done('Details applied, but the photos could not be imported. You can upload them manually.', true);
                }).always(function () {
                    $btn.html(label); updateApply();
                });
            });

            function done(text, isWarn) {
                $('#czimp-preview').addClass('d-none');
                setMsg(text, isWarn ? 'is-error' : 'is-ok');
                var el = document.getElementById('czimp');
                if (el && el.scrollIntoView) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            }
        });
    })();
</script>
