<?php
/**
 * Shared media behaviour for the admin and seller "add / edit product" forms.
 *
 *  - Direct upload: the Choose buttons open the OS file picker and POST straight to the
 *    panel's media/upload endpoint (no media-library modal). Every image type the server
 *    allows (jpg, jpeg, png, gif, webp, bmp) is accepted.
 *  - Image editor: every image tile has an "Edit image" button (crop/fit, rotate, flip, colour);
 *    the edited copy is uploaded through the same endpoint and replaces the tile's file.
 *  - Drag and drop: images can be dragged to re-order the "Other Images", and any image
 *    can be dragged onto the Main Image (or the Main Image onto another image) to swap
 *    them. Files dragged in from the desktop are uploaded into the zone they are dropped on.
 *
 * Expects $upload_url (e.g. base_url('admin/media/upload')).
 */
?>
<style>
    .media-tile {
        position: relative;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        background: #fff;
        padding: 10px !important;
        margin: 0 12px 12px 0 !important;
        max-width: 170px;
        box-shadow: none !important;
        text-align: center;
    }
    .media-tile .image-upload-div {
        height: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .media-tile img { max-height: 110px; width: auto; max-width: 100%; object-fit: contain; }
    .media-tile-name { font-size: 11px; color: #6c757d; word-break: break-all; line-height: 1.25; }
    .media-remove-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        z-index: 3;
        width: 24px;
        height: 24px;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: #dc3545;
        color: #fff !important;
        font-size: 17px;
        line-height: 22px;
        text-align: center;
        text-decoration: none !important;
        cursor: pointer;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
    }
    .media-remove-btn:hover { background: #b02a37; }

    /* Drag and drop */
    .image-upload-section.media-dnd { min-height: 64px; border-radius: 10px; transition: background .15s, outline-color .15s; }
    .media-dnd .media-tile, .media-dnd .czimp-tile { cursor: grab; }
    .media-dnd .media-tile.is-dragging, .media-dnd .czimp-tile.is-dragging { opacity: .35; cursor: grabbing; }
    .media-dnd .media-drop-target { outline: 2px dashed #f2822e; outline-offset: 2px; background: rgba(242, 130, 46, .08); }
    .image-upload-section.media-dnd.media-drop-target { outline: 2px dashed #f2822e; background: rgba(242, 130, 46, .08); }
    .media-dnd-hint { font-size: 12px; color: #6c757d; margin: 6px 0 0; }
    .media-main-badge {
        position: absolute; left: 6px; top: 6px; z-index: 2;
        background: #f2822e; color: #fff; font-size: 10px; font-weight: 600;
        padding: 1px 7px; border-radius: 10px; letter-spacing: .02em;
    }

    /* Image editor */
    .media-edit-btn {
        margin-top: 6px; padding: 3px 12px; font-size: 12px; font-weight: 600; line-height: 1.5;
        color: #f2822e; background: #fff; border: 1px solid #f2822e; border-radius: 14px; cursor: pointer;
    }
    .media-edit-btn:hover { background: #f2822e; color: #fff; }
    .imged-overlay {
        position: fixed; inset: 0; z-index: 20000; display: none; align-items: center; justify-content: center;
        background: rgba(0, 0, 0, .6); padding: 16px;
    }
    .imged-overlay.is-open { display: flex; }
    .imged-box {
        background: #fff; border-radius: 12px; width: 100%; max-width: 860px; max-height: 100%; overflow: auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .3); font-size: 14px; color: #333;
    }
    .imged-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid #eee; }
    .imged-head h5 { margin: 0; font-size: 17px; font-weight: 700; }
    .imged-x { border: 0; background: none; font-size: 26px; line-height: 1; cursor: pointer; color: #666; }
    .imged-body { display: flex; flex-wrap: wrap; gap: 20px; padding: 20px; }
    .imged-stage { flex: 1 1 380px; display: flex; flex-direction: column; align-items: center; }
    .imged-canvas-wrap { background: #e9ecef; padding: 8px; border-radius: 8px; max-width: 100%; }
    .imged-canvas { display: block; max-width: 100%; height: auto; cursor: grab; touch-action: none; background: #fff; }
    .imged-canvas.is-panning { cursor: grabbing; }
    .imged-tip { font-size: 12px; color: #6c757d; margin: 8px 0 0; text-align: center; }
    .imged-panel { flex: 1 1 260px; min-width: 240px; }
    .imged-panel h6 { margin: 0 0 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #888; }
    .imged-group { margin-bottom: 16px; }
    .imged-row { display: flex; flex-wrap: wrap; gap: 6px; }
    .imged-chip { padding: 5px 12px; font-size: 13px; border: 1px solid #ccc; background: #fff; border-radius: 16px; cursor: pointer; color: #333; }
    .imged-chip:hover { border-color: #f2822e; }
    .imged-chip.is-on { background: #f2822e; border-color: #f2822e; color: #fff; }
    .imged-slider { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
    .imged-slider label { width: 78px; margin: 0; font-size: 13px; }
    .imged-slider input[type=range] { flex: 1; min-width: 0; }
    .imged-slider output { width: 42px; text-align: right; font-size: 12px; color: #666; }
    .imged-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid #eee; }
    .imged-foot button { padding: 8px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: 1px solid #ccc; background: #fff; }
    .imged-foot .imged-save { background: #f2822e; border-color: #f2822e; color: #fff; }
    .imged-foot button:disabled { opacity: .6; cursor: default; }
</style>

<script>
(function () {
    var UPLOAD_URL = <?= json_encode($upload_url) ?>;
    var MAIN = 'pro_input_image';
    var OTHER = 'other_images[]';
    var dragged = null;

    function sectionFor(inputName) {
        return $('.direct-upload-input[data-input="' + inputName + '"]').first()
            .closest('.form-group').find('.image-upload-section').first();
    }
    function $main() { return sectionFor(MAIN); }
    function $other() { return sectionFor(OTHER); }

    // A tile belongs to the dnd zones only if it carries a hidden input for the image fields.
    function tilesOf($section) {
        return $section.children().filter(function () {
            return $(this).find('input[type=hidden][name="' + MAIN + '"], input[type=hidden][name="' + OTHER + '"]').length > 0;
        });
    }

    // Bring saved-image markup (shadow/m-4 cards, Delete bars) in line with the upload tiles.
    function normalise($tile) {
        if ($tile.hasClass('czimp-tile')) return;
        $tile.removeClass('shadow p-3 mb-5 m-3 m-4 bg-white rounded col-sm-12').addClass('media-tile text-center');
        $tile.children('a.delete-img, a.delete-img-seller').each(function () {
            $(this).addClass('media-remove-btn').removeClass('m-3 btn').html('&times;');
        });
    }

    function refresh() {
        var $m = $main(), $o = $other();
        $m.add($o).addClass('media-dnd');
        tilesOf($m).add(tilesOf($o)).each(function () {
            normalise($(this));
            this.setAttribute('draggable', 'true');
        });
        $m.find('.media-main-badge').remove();
        tilesOf($m).each(function () {
            $(this).append('<span class="media-main-badge">Main</span>');
        });
        tilesOf($m).add(tilesOf($o)).each(function () {
            var $t = $(this);
            if (!$t.find('.media-edit-btn').length && $t.find('img').length) {
                $t.append('<div><button type="button" class="media-edit-btn" title="Crop, rotate and adjust this image"><i class="fa fa-edit"></i> Edit image</button></div>');
            }
        });
        $m.add($o).each(function () {
            var $z = $(this);
            if (!$z.next('.media-dnd-hint').length) {
                $z.after('<p class="media-dnd-hint"><i class="fa fa-arrows-alt"></i> ' +
                    ($z.is($m) ? 'Drag an image from "Other Images" here to make it the main image.'
                               : 'Drag images to re-order them, or drop one onto the main image.') + '</p>');
            }
        });
    }

    // Move the hidden input to the right field name and fix the controls for the new home.
    function adopt($tile, isMain) {
        $tile.find('input[type=hidden][name="' + MAIN + '"], input[type=hidden][name="' + OTHER + '"]')
            .attr('name', isMain ? MAIN : OTHER);
        if (isMain) {
            // The server-side delete anchors are tied to the other_images field.
            $tile.children('a.delete-img, a.delete-img-seller').remove();
            $tile.children('.media-remove-btn, .czimp-tile-remove').remove();
        } else if (!$tile.children('.media-remove-btn, .czimp-tile-remove').length) {
            $tile.prepend('<button type="button" class="media-remove-btn" title="Remove" aria-label="Remove">&times;</button>');
        }
        $tile.removeClass('d-none');
    }

    function swap($a, $b) {
        var $ap = $a.parent(), $bp = $b.parent();
        var $aMark = $('<span>').insertBefore($a), $bMark = $('<span>').insertBefore($b);
        $aMark.replaceWith($b);
        $bMark.replaceWith($a);
        adopt($a, $bp.is($main()));
        adopt($b, $ap.is($main()));
    }

    function clearTargets() { $('.media-drop-target').removeClass('media-drop-target'); }
    function isFileDrag(e) {
        var t = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.types;
        return !!t && Array.prototype.indexOf.call(t, 'Files') !== -1;
    }
    function zoneOf(el) {
        var $z = $(el).closest('.image-upload-section.media-dnd');
        return $z.length ? $z : null;
    }

    $(document).on('dragstart', '.media-dnd > [draggable="true"]', function (e) {
        dragged = $(this);
        dragged.addClass('is-dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        try { e.originalEvent.dataTransfer.setData('text/plain', 'media-tile'); } catch (err) {}
    });
    $(document).on('dragend', '.media-dnd > [draggable="true"]', function () {
        $(this).removeClass('is-dragging');
        dragged = null;
        clearTargets();
    });

    $(document).on('dragover', '.image-upload-section.media-dnd', function (e) {
        var $z = $(this);
        if (!dragged && !isFileDrag(e)) return;
        e.preventDefault();
        clearTargets();
        var $t = $(e.target).closest('.media-dnd > *');
        if (dragged && $t.length && !$t.is(dragged)) $t.addClass('media-drop-target');
        else $z.addClass('media-drop-target');
    });
    $(document).on('dragleave', '.image-upload-section.media-dnd', function (e) {
        if (!$.contains(this, e.relatedTarget) && e.relatedTarget !== this) clearTargets();
    });

    $(document).on('drop', '.image-upload-section.media-dnd', function (e) {
        var $z = $(this);
        var dt = e.originalEvent.dataTransfer;
        clearTargets();

        // Files dragged in from the desktop: upload into the zone they landed on.
        if (!dragged && dt && dt.files && dt.files.length) {
            e.preventDefault();
            var $inp = $z.closest('.form-group').find('.direct-upload-input').first();
            var files = Array.prototype.slice.call(dt.files);
            if (String($inp.data('multiple')) !== '1') files = files.slice(0, 1);
            uploadFiles(files, $inp);
            return;
        }
        if (!dragged) return;
        e.preventDefault();

        var $d = dragged;
        var $target = $(e.target).closest('.media-dnd > *');
        var toMain = $z.is($main());
        var fromMain = $d.parent().is($main());

        if ($target.length && $target.is($d)) return;

        if ($target.length && $target[0].getAttribute('draggable') === 'true') {
            if ($d.parent().is($z)) {
                // Re-order inside "Other Images": land before the tile that was dropped on
                // when moving up, after it when moving down.
                var kids = $z.children().filter('[draggable="true"]');
                if (kids.index($d) < kids.index($target)) $d.insertAfter($target); else $d.insertBefore($target);
            } else {
                swap($d, $target);
            }
        } else if (toMain && !fromMain) {
            // Dropped on the main-image area itself.
            var $current = tilesOf($z).first();
            if ($current.length) swap($d, $current);
            else { $z.append($d); adopt($d, true); }
        } else if (!toMain && !fromMain) {
            $z.append($d); // dropped on the empty part of the strip: move to the end
        }
        // Dropping the main image on empty space in "Other Images" is ignored: the
        // product must always keep a main image.
        refresh();
    });

    // Block the browser's default "open the dropped file" outside the zones.
    $(document).on('dragover drop', function (e) {
        if (isFileDrag(e) && !zoneOf(e.target)) e.preventDefault();
    });

    // ------------------------------------------------------------------ uploads
    function previewSrcFor(mediaType, url) {
        if (mediaType === 'image') return url;
        var icon = (mediaType.indexOf('video') === 0) ? 'video' : 'archive';
        return base_url + 'assets/admin/images/' + icon + '-file.png';
    }

    function buildTile(name, relPath, mediaType, url) {
        var $tile = $(
            '<div class="col-md-3 col-sm-12 text-center grow image media-tile">' +
                '<button type="button" class="media-remove-btn" title="Remove" aria-label="Remove">&times;</button>' +
                '<div class="image-upload-div"></div>' +
                '<div class="media-tile-name"></div>' +
            '</div>'
        );
        // .text()/.attr() rather than string-built HTML: names come from the upload response.
        $tile.find('.image-upload-div').append(
            $('<img class="img-fluid mb-2">').attr({ src: previewSrcFor(mediaType, url), alt: name, title: name })
        );
        $tile.find('.media-tile-name').text(name);
        $tile.append($('<input type="hidden">').attr('name', relPath.inputName).val(relPath.value));
        return $tile;
    }

    function uploadFiles(files, $input) {
        if (!files || !files.length) return;

        var inputName = $input.data('input');
        var mediaType = String($input.data('media-type') || 'image');
        var isMultiple = String($input.data('multiple')) === '1';
        var $group = $input.closest('.form-group');
        var $section = $group.find('.image-upload-section').first();
        var $btn = $input.siblings('.direct-upload-btn');
        var originalLabel = $btn.html();

        var fd = new FormData();
        for (var i = 0; i < files.length; i++) fd.append('documents[]', files[i]);
        fd.append('media_type', mediaType);
        fd.append(csrfName, csrfHash);

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

        $.ajax({
            url: UPLOAD_URL,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.csrfName) csrfName = res.csrfName;
                if (res.csrfHash) csrfHash = res.csrfHash;

                var uploaded = res.files || [];
                if (!uploaded.length) {
                    Swal.fire({ icon: 'error', title: 'Upload failed', text: res.message || 'No file was uploaded.' });
                    return;
                }
                if (res.error) {
                    Swal.fire({ icon: 'warning', title: 'Some files were rejected', html: res.error });
                }

                $section.removeClass('d-none').find('.image.d-none').remove();
                if (!isMultiple) $section.empty();

                $.each(uploaded, function (_, f) {
                    var value = f.sub_directory + f.name;
                    $section.append(buildTile(f.name, { inputName: inputName, value: value }, mediaType, f.url));
                });
                refresh();
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Upload failed', text: 'Could not reach the server. Please try again.' });
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalLabel);
                $input.val(''); // so re-picking the same file still fires `change`
            }
        });
    }

    $(document).on('click', '.direct-upload-btn', function () {
        $(this).siblings('.direct-upload-input').trigger('click');
    });
    $(document).on('change', '.direct-upload-input', function () {
        uploadFiles(Array.prototype.slice.call(this.files || []), $(this));
    });

    // Removes a tile that has not been saved yet - the form only submits what is in the DOM.
    // Saved images keep their server-side delete anchors.
    $(document).on('click', 'button.media-remove-btn', function () {
        $(this).closest('.media-tile').remove();
        refresh();
    });

    // ------------------------------------------------------------------ image editor
    // Crop frame + fit/fill + rotate/flip + colour, drawn on a canvas. Saving uploads the
    // result through the same endpoint as a normal upload and swaps it into the tile, so the
    // form submits the edited file's path exactly as it would for any other upload.
    var ED = { open: false };
    var ASPECTS = { '1:1': 1, '4:3': 4 / 3, '3:4': 3 / 4, 'orig': 0 };

    function buildEditor() {
        if (document.getElementById('imged-overlay')) return;
        var html =
            '<div class="imged-overlay" id="imged-overlay" role="dialog" aria-modal="true" aria-label="Edit image">' +
              '<div class="imged-box">' +
                '<div class="imged-head"><h5>Edit image</h5><button type="button" class="imged-x" data-act="close" aria-label="Close">&times;</button></div>' +
                '<div class="imged-body">' +
                  '<div class="imged-stage">' +
                    '<div class="imged-canvas-wrap"><canvas class="imged-canvas" id="imged-canvas"></canvas></div>' +
                    '<p class="imged-tip">Drag the image to reposition it. Scroll or use Zoom to resize.</p>' +
                  '</div>' +
                  '<div class="imged-panel">' +
                    '<div class="imged-group"><h6>Shape</h6><div class="imged-row" id="imged-aspects">' +
                      '<button type="button" class="imged-chip is-on" data-aspect="1:1">Square</button>' +
                      '<button type="button" class="imged-chip" data-aspect="4:3">4:3</button>' +
                      '<button type="button" class="imged-chip" data-aspect="3:4">3:4</button>' +
                      '<button type="button" class="imged-chip" data-aspect="orig">Original</button>' +
                    '</div></div>' +
                    '<div class="imged-group"><h6>Size in frame</h6><div class="imged-row">' +
                      '<button type="button" class="imged-chip" data-act="fit">Fit whole image</button>' +
                      '<button type="button" class="imged-chip" data-act="fill">Fill frame</button>' +
                    '</div></div>' +
                    '<div class="imged-group"><h6>Rotate &amp; flip</h6><div class="imged-row">' +
                      '<button type="button" class="imged-chip" data-act="rotl">&#8634; Left</button>' +
                      '<button type="button" class="imged-chip" data-act="rotr">&#8635; Right</button>' +
                      '<button type="button" class="imged-chip" data-act="flip">&#8646; Flip</button>' +
                    '</div></div>' +
                    '<div class="imged-group"><h6>Adjust</h6>' +
                      '<div class="imged-slider"><label for="imged-zoom">Zoom</label><input type="range" id="imged-zoom" min="30" max="300" value="100"><output></output></div>' +
                      '<div class="imged-slider"><label for="imged-bri">Brightness</label><input type="range" id="imged-bri" min="50" max="150" value="100"><output></output></div>' +
                      '<div class="imged-slider"><label for="imged-con">Contrast</label><input type="range" id="imged-con" min="50" max="150" value="100"><output></output></div>' +
                      '<div class="imged-slider"><label for="imged-sat">Colour</label><input type="range" id="imged-sat" min="0" max="200" value="100"><output></output></div>' +
                    '</div>' +
                    '<div class="imged-row"><button type="button" class="imged-chip" data-act="reset">Reset all</button></div>' +
                  '</div>' +
                '</div>' +
                '<div class="imged-foot"><button type="button" data-act="close">Cancel</button><button type="button" class="imged-save" data-act="save">Save image</button></div>' +
              '</div>' +
            '</div>';
        $('body').append(html);
        ED.$o = $('#imged-overlay');
        ED.canvas = document.getElementById('imged-canvas');
        ED.ctx = ED.canvas.getContext('2d');
    }

    function effDims() {
        var odd = ED.rot % 2 === 1;
        return { w: odd ? ED.img.naturalHeight : ED.img.naturalWidth, h: odd ? ED.img.naturalWidth : ED.img.naturalHeight };
    }
    function frameAspect() { var e = effDims(); return ASPECTS[ED.aspect] || (e.w / e.h); }

    // Preview canvas: long edge 480 css px.
    function sizeCanvas() {
        var a = frameAspect(), long = 480;
        ED.pw = a >= 1 ? long : Math.round(long * a);
        ED.ph = a >= 1 ? Math.round(long / a) : long;
        ED.canvas.width = ED.pw;
        ED.canvas.height = ED.ph;
    }
    function containScale() { var e = effDims(); return Math.min(ED.pw / e.w, ED.ph / e.h); }
    function coverScale() { var e = effDims(); return Math.max(ED.pw / e.w, ED.ph / e.h); }

    // zoom is relative to "fit whole image"; the slider tops out past "fill" so a tall photo
    // in a square frame can still be filled.
    function syncZoomRange() {
        var max = Math.max(300, Math.ceil(coverScale() / containScale() * 150));
        $('#imged-zoom').attr('max', max);
    }

    // k scales the preview-space pose (zoom/offset) up to the export canvas.
    function drawTo(ctx, W, H, k) {
        var s = containScale() * ED.zoom * k;
        ctx.save();
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, W, H);
        ctx.filter = 'brightness(' + ED.bri + '%) contrast(' + ED.con + '%) saturate(' + ED.sat + '%)';
        ctx.translate(W / 2 + ED.ox * k, H / 2 + ED.oy * k);
        ctx.rotate(ED.rot * Math.PI / 2);
        ctx.scale(ED.flip ? -1 : 1, 1);
        ctx.scale(s, s);
        ctx.drawImage(ED.img, -ED.img.naturalWidth / 2, -ED.img.naturalHeight / 2);
        ctx.restore();
    }
    function render() {
        drawTo(ED.ctx, ED.pw, ED.ph, 1);
        $('#imged-zoom').val(Math.round(ED.zoom * 100)).siblings('output').text(Math.round(ED.zoom * 100) + '%');
        $('#imged-bri').siblings('output').text(ED.bri + '%');
        $('#imged-con').siblings('output').text(ED.con + '%');
        $('#imged-sat').siblings('output').text(ED.sat + '%');
        $('#imged-aspects .imged-chip').each(function () { $(this).toggleClass('is-on', String($(this).data('aspect')) === ED.aspect); });
    }

    function resetPose() { ED.zoom = 1; ED.ox = 0; ED.oy = 0; }
    function resetAll() {
        ED.rot = 0; ED.flip = false; ED.bri = 100; ED.con = 100; ED.sat = 100; ED.aspect = '1:1';
        $('#imged-bri').val(100); $('#imged-con').val(100); $('#imged-sat').val(100);
        sizeCanvas(); resetPose(); syncZoomRange(); render();
    }

    function tileInput($tile) {
        return $tile.find('input[type=hidden][name="' + MAIN + '"], input[type=hidden][name="' + OTHER + '"]').first();
    }
    // Prefer the stored original over the (possibly resized) preview thumbnail.
    function sourceUrlFor($tile) {
        var v = tileInput($tile).val() || '';
        if (/^https?:\/\//i.test(v)) return v;
        if (v) return base_url + v.replace(/^\/+/, '');
        return $tile.find('img').first().attr('src');
    }

    function openEditor($tile) {
        buildEditor();
        var src = sourceUrlFor($tile);
        if (!src) return;
        var img = new Image();
        var triedFallback = false;
        img.onload = function () {
            ED.img = img; ED.$tile = $tile; ED.open = true;
            ED.$o.addClass('is-open');
            $('body').css('overflow', 'hidden');
            resetAll();
        };
        img.onerror = function () {
            var fallback = $tile.find('img').first().attr('src');
            if (!triedFallback && fallback && fallback !== img.src) { triedFallback = true; img.src = fallback; return; }
            Swal.fire({ icon: 'error', title: 'Cannot open image', text: 'This image could not be loaded for editing.' });
        };
        // Same-origin files load fine; CORS is only needed (and only honoured) for CDN hosts.
        if (src.indexOf(base_url) !== 0) img.crossOrigin = 'anonymous';
        img.src = src;
    }
    function closeEditor() {
        if (!ED.open) return;
        ED.open = false;
        ED.$o.removeClass('is-open');
        $('body').css('overflow', '');
    }

    function saveEdited() {
        var $save = ED.$o.find('.imged-save'), label = $save.text();
        var e = effDims();
        var long = Math.min(1600, Math.max(e.w, e.h));
        var a = frameAspect();
        var W = a >= 1 ? long : Math.round(long * a), H = a >= 1 ? Math.round(long / a) : long;
        var out = document.createElement('canvas');
        out.width = W; out.height = H;
        drawTo(out.getContext('2d'), W, H, W / ED.pw);

        function exportFailed() {
            Swal.fire({ icon: 'error', title: 'Cannot save', text: 'This image cannot be exported from the browser. Please re-upload it and try again.' });
        }
        function upload(blob) {
            if (!blob) return exportFailed();
            var base = (ED.$tile.find('.media-tile-name').text() || 'image').replace(/\.[^.]+$/, '').replace(/[^\w-]+/g, '-').slice(0, 40) || 'image';
            var fd = new FormData();
            fd.append('documents[]', new File([blob], base + '-edited.jpg', { type: 'image/jpeg' }));
            fd.append('media_type', 'image');
            fd.append(csrfName, csrfHash);
            ED.$o.find('button').prop('disabled', true);
            $save.text('Saving...');
            $.ajax({
                url: UPLOAD_URL, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
                success: function (res) {
                    if (res.csrfName) csrfName = res.csrfName;
                    if (res.csrfHash) csrfHash = res.csrfHash;
                    var f = (res.files || [])[0];
                    if (!f) {
                        Swal.fire({ icon: 'error', title: 'Save failed', text: res.message || res.error || 'The edited image was not saved.' });
                        return;
                    }
                    var $t = ED.$tile;
                    tileInput($t).val(f.sub_directory + f.name);
                    $t.find('img').first().attr({ src: f.url, alt: f.name, title: f.name });
                    $t.find('.media-tile-name').text(f.name);
                    closeEditor();
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Save failed', text: 'Could not reach the server. Please try again.' });
                },
                complete: function () {
                    ED.$o.find('button').prop('disabled', false);
                    $save.text(label);
                }
            });
        }
        try {
            out.toBlob(upload, 'image/jpeg', 0.92);
        } catch (err) {
            exportFailed();
        }
    }

    $(document).on('click', '.media-edit-btn', function (e) {
        e.preventDefault();
        openEditor($(this).closest('.media-tile, .czimp-tile'));
    });
    $(document).on('click', '#imged-overlay [data-aspect]', function () {
        ED.aspect = String($(this).data('aspect'));
        sizeCanvas(); resetPose(); syncZoomRange(); render();
    });
    $(document).on('click', '#imged-overlay [data-act]', function () {
        var act = $(this).data('act');
        if (act === 'close') return closeEditor();
        if (act === 'save') return saveEdited();
        if (act === 'reset') return resetAll();
        if (act === 'fit') resetPose();
        else if (act === 'fill') { ED.zoom = coverScale() / containScale(); ED.ox = 0; ED.oy = 0; }
        else if (act === 'rotl' || act === 'rotr') {
            ED.rot = (ED.rot + (act === 'rotr' ? 1 : 3)) % 4;
            if (ED.aspect === 'orig') sizeCanvas();
            resetPose();
        }
        else if (act === 'flip') ED.flip = !ED.flip;
        syncZoomRange(); render();
    });
    $(document).on('input', '#imged-zoom', function () { ED.zoom = this.value / 100; render(); });
    $(document).on('input', '#imged-bri', function () { ED.bri = +this.value; render(); });
    $(document).on('input', '#imged-con', function () { ED.con = +this.value; render(); });
    $(document).on('input', '#imged-sat', function () { ED.sat = +this.value; render(); });
    $(document).on('mousedown', '#imged-overlay', function (e) { if (e.target === this) closeEditor(); });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') closeEditor(); });
    $(document).on('wheel', '#imged-canvas', function (e) {
        e.preventDefault();
        var z = ED.zoom * (e.originalEvent.deltaY < 0 ? 1.06 : 1 / 1.06);
        ED.zoom = Math.max(0.3, Math.min(+$('#imged-zoom').attr('max') / 100, z));
        render();
    });

    // Drag to pan (pointer events cover mouse, touch and pen).
    (function () {
        var pan = null;
        $(document).on('pointerdown', '#imged-canvas', function (e) {
            var r = this.getBoundingClientRect();
            pan = { x: e.originalEvent.clientX, y: e.originalEvent.clientY, ox: ED.ox, oy: ED.oy, k: ED.pw / r.width };
            this.setPointerCapture(e.originalEvent.pointerId);
            $(this).addClass('is-panning');
        });
        $(document).on('pointermove', '#imged-canvas', function (e) {
            if (!pan) return;
            ED.ox = pan.ox + (e.originalEvent.clientX - pan.x) * pan.k;
            ED.oy = pan.oy + (e.originalEvent.clientY - pan.y) * pan.k;
            render();
        });
        $(document).on('pointerup pointercancel', '#imged-canvas', function () { pan = null; $(this).removeClass('is-panning'); });
    })();

    $(function () {
        refresh();
        // Tiles also arrive from the import-from-link panel; keep them draggable.
        var obs = new MutationObserver(function () { obs.disconnect(); refresh(); watch(); });
        function watch() {
            $main().add($other()).each(function () { obs.observe(this, { childList: true }); });
        }
        watch();
    });
})();
</script>
