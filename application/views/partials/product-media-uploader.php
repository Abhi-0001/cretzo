<?php
/**
 * Shared media behaviour for the admin and seller "add / edit product" forms.
 *
 *  - Direct upload: the Choose buttons open the OS file picker and POST straight to the
 *    panel's media/upload endpoint (no media-library modal). Every image type the server
 *    allows (jpg, jpeg, png, gif, webp, bmp) is accepted.
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
