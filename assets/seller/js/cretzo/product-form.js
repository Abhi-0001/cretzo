/*
 * Product form (add / edit) behaviour shared by the seller and admin panels - see
 * assets/seller/css/cretzo/product-form.css. It only adds layout behaviour (collapsible
 * blocks, the section checklist); it never touches the fields' values, so the page's real
 * logic in assets/admin/custom/custom.js is unaffected.
 */
(function ($) {
    'use strict';
    if (!$ || !$('.czpf').length) { return; }

    var $root = $('.czpf').first();

    /* ---- collapsible blocks ------------------------------------------------ */
    function setOpen($fold, open) {
        $fold.toggleClass('is-open', open);
        $fold.children('.czpf-fold-toggle').attr('aria-expanded', open ? 'true' : 'false');
        // Widgets that size themselves to their container (select2, TinyMCE) re-measure on resize.
        if (open) { setTimeout(function () { $(window).trigger('resize'); }, 280); }
    }
    $root.on('click', '.czpf-fold-toggle', function () {
        var $fold = $(this).closest('.czpf-fold');
        setOpen($fold, !$fold.hasClass('is-open'));
    });

    // Picking the video type opens the video block (it holds the type select, so it is already
    // visible then) - nothing to do. A deep link to a hidden section opens it instead.
    function revealFor(hash) {
        var $t = $(hash);
        if ($t.length && $t.hasClass('czpf-fold')) { setOpen($t, true); }
    }

    /* ---- sticky offset: sit just below a fixed top bar, if the panel has one ---- */
    function setTop() {
        var $nav = $('.main-header').first(), top = 0;
        if ($nav.length) {
            var pos = $nav.css('position');
            if (pos === 'fixed' || pos === 'sticky') { top = $nav.outerHeight() || 0; }
        }
        $root[0].style.setProperty('--czpf-top', top + 'px');
        $root.find('.czpf-card').css('scroll-margin-top', (top + 70) + 'px');
    }
    setTop();
    $(window).on('resize', setTop);

    /* ---- section checklist ----------------------------------------------- */
    var $links = $('#czpf-steps a');
    var sections = {};
    $links.each(function () {
        var id = $(this).attr('href');
        sections[id] = $(id);
    });

    $('#czpf-steps').on('click', 'a', function (e) {
        var id = $(this).attr('href'), $s = $(id);
        if (!$s.length) { return; }
        e.preventDefault();
        revealFor(id);
        var top = $s.offset().top - (parseInt($root[0].style.getPropertyValue('--czpf-top'), 10) || 0) - 70;
        $('html, body').animate({ scrollTop: Math.max(0, top) }, 250);
    });

    function val(sel) { return $.trim($(sel).val() || ''); }

    function editorFilled(name) {
        // tinymce.get() with no id returns every editor on the page (v5/v6); older builds expose .editors
        var list = [];
        try { if (window.tinymce) { list = $.makeArray(tinymce.get ? tinymce.get() : tinymce.editors); } } catch (e) { list = []; }
        for (var i = 0; i < list.length; i++) {
            var ed = list[i], el = ed && ed.getElement && ed.getElement();
            if (el && el.name === name) { return $.trim(ed.getContent({ format: 'text' })) !== ''; }
        }
        return val('textarea[name="' + name + '"]') !== '';
    }

    var checks = {
        basics: function () {
            return val('#pro_input_text') !== '' && val('#product_category_id') !== '' && val('#short_description') !== '' &&
                ($('#seller_id').is('select') ? val('#seller_id') !== '' : true);
        },
        photos: function () { return val('input[name="pro_input_image"]') !== ''; },
        price: function () {
            var type = val('#product-type'), hidden = val('input[name="product_type"]');
            type = type || hidden;
            if (type === 'variable_product') { return $('input[name="variant_price[]"]').length > 0; }
            if (type === 'simple_product' || type === 'digital_product') {
                var ok = val('[name="simple_price"]') !== '';
                if ($('[name="simple_special_price"]').hasClass('stock-simple-mustfill-field')) { ok = ok && val('[name="simple_special_price"]') !== ''; }
                // a physical product also needs its parcel weight (g) and size (cm) - the Shiprocket order is booked from them
                if (type === 'simple_product') { ok = ok && val('#weight') !== '' && val('#height') !== '' && val('#breadth') !== '' && val('#length') !== ''; }
                return ok;
            }
            return false;
        },
        ship: function () { return val('#pickup_location') !== ''; },
        desc: function () { return editorFilled('pro_input_description'); }
    };

    function refresh() {
        $.each(checks, function (key, fn) {
            var done = false;
            try { done = !!fn(); } catch (e) { done = false; }
            $links.filter('[data-czpf-step="' + key + '"]').toggleClass('is-done', done);
        });

        // a lone "Price & stock" tab (no options / variations yet) is just noise - show the tabs once there are two
        $('.czpf .additional-info').each(function () {
            var visible = $(this).find('#product-tab .nav-link').not('.d-none').length;
            $(this).toggleClass('czpf-tabs-single', visible <= 1);
        });

        // a group of fields whose members are all hidden (digital products) should not leave its heading behind
        $('.czpf-hide-if-empty').each(function () {
            var $g = $(this), any = false;
            $g.find('.form-group').each(function () { if (!$(this).hasClass('d-none')) { any = true; } });
            $g.toggleClass('d-none', !any);
        });
    }

    /* ---- which section is on screen --------------------------------------- */
    function spy() {
        var top = (parseInt($root[0].style.getPropertyValue('--czpf-top'), 10) || 0) + 90, current = null;
        $links.each(function () {
            var $s = sections[$(this).attr('href')];
            if ($s && $s.length && !$s.hasClass('d-none') && $s[0].getBoundingClientRect().top <= top) { current = this; }
        });
        $links.removeClass('is-active');
        $(current || $links.first()).addClass('is-active');
    }

    var timer = null;
    function schedule() { clearTimeout(timer); timer = setTimeout(function () { refresh(); spy(); }, 120); }
    $(window).on('scroll', schedule);
    $root.on('input change click keyup', schedule);
    $(document).on('select2:select select2:clear', schedule);
    setInterval(refresh, 1500);   // catches what the page's own scripts fill in (uploads, import, TinyMCE)
    $(window).on('load', function () { refresh(); spy(); });
    refresh(); spy();


    /* ---- variations are built automatically (add form only) ---------------------
     * custom.js only builds the variation rows when the Variations tab is opened, so a seller who
     * added options and pressed Add Product straight away was told "Variation Needed". Here the rows
     * are (re)built whenever the ticked options change, and once more just before the form is sent.
     * The tab still works for fine-tuning individual variations. */
    function isAddForm() { return $('#tab-for-attributes').hasClass('product-attributes'); }
    function isVariable() { return $.trim($('#product-type').val() || '') === 'variable_product'; }
    function builtRows() { return $('#variants_process input[name="variants_ids[]"]').length; }
    function panelFrom() { return (typeof from !== 'undefined' && from) ? from : 'seller'; }

    function setStatus(html, kind) {
        var $s = $('#czpf-var-status');
        if (!$s.length) {
            $s = $('<div id="czpf-var-status" class="czpf-var-status" role="status" aria-live="polite"></div>');
            $('#attributes_process').after($s);
        }
        $s.attr('class', 'czpf-var-status' + (kind ? ' is-' + kind : '')).html(html).toggle(!!html);
    }

    // Resolves once the variation rows match the ticked options. Never rejects - the caller carries on either way.
    function syncVariants() {
        var done = $.Deferred();
        if (!isAddForm() || !isVariable() || typeof save_attributes !== 'function' || typeof create_variants !== 'function') {
            return done.resolve().promise();
        }
        save_attributes();   // fills the global attributes_values from the ticked options
        var sig = JSON.stringify(attributes_values);

        if (!attributes_values.length) {
            window.czVariantSig = sig;
            $('#variants_process').empty();
            $('.no-variants-added').show();
            setStatus('', '');
            return done.resolve().promise();
        }
        if (builtRows() > 0 && window.czVariantSig === sig) { return done.resolve().promise(); }

        window.czVariantSig = sig;
        var req;
        try { req = create_variants(false, panelFrom()); } catch (e) { req = null; }
        if (req && req.always) {
            req.always(function () {
                var n = builtRows();
                setStatus(n ? '<i class="fas fa-check-circle"></i> ' + n + ' variation' + (n === 1 ? '' : 's') + ' created from your options. ' +
                    'Set the price once in <b>Price &amp; stock</b> &mdash; it applies to all of them.' : '', n ? 'ok' : '');
                done.resolve();
            });
        } else { done.resolve(); }
        return done.promise();
    }

    var syncTimer = null;
    function scheduleSync() {
        if (!isAddForm() || !isVariable()) { return; }
        clearTimeout(syncTimer);
        syncTimer = setTimeout(syncVariants, 600);
    }
    $(document).on('change select2:select select2:unselect select2:clear',
        '#attributes_process select, #attributes_process .is_attribute_checked', scheduleSync);
    var attrBox = document.getElementById('attributes_process');
    if (attrBox && window.MutationObserver) {
        new MutationObserver(scheduleSync).observe(attrBox, { childList: true });   // an option row added / removed
    }
    $(document).on('change select2:select', '#product-type', function () { setTimeout(scheduleSync, 50); });

    // Just before the product is sent: make sure the variations exist, and give a clear message if there are no options.
    var resubmitting = false;
    $('#save-product').on('submit', function (e) {
        if (resubmitting || !isAddForm() || !isVariable()) { return; }
        save_attributes();
        if (!attributes_values.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            $('#product-tab a[href="#product-attributes"]').tab('show');
            if (window.Swal) {
                Swal.fire('Add an option first', 'Open the Options tab, add an option such as Size or Colour, pick its values, and keep "Use for variations" ticked.', 'warning');
            }
            return;
        }
        if (builtRows() > 0 && window.czVariantSig === JSON.stringify(attributes_values)) { return; }   // already up to date
        e.preventDefault();
        e.stopImmediatePropagation();
        var $form = $(this);
        syncVariants().always(function () {
            resubmitting = true;
            $form.trigger('submit');
            resubmitting = false;
        });
    });

    /* ---- variation rows: a one-line summary so each can be seen (and edited) at a glance ------ */
    function money(v) {
        var n = parseFloat(v);
        return isNaN(n) ? null : '₹' + (Math.round(n * 100) / 100);
    }
    function updateVariantSummaries() {
        $('#variants_process .product-variant-selectbox').each(function () {
            var $row = $(this);
            if (!$row.find('input[name="variants_ids[]"]').length) { return; }
            var $sum = $row.children('.czpf-var-sum');
            if (!$sum.length) {
                $sum = $('<div class="czpf-var-sum"></div>');
                var $actions = $row.children('.col.my-auto').last();
                if ($actions.length) { $sum.insertBefore($actions); } else { $row.append($sum); }
            }
            var price = money($row.find('[name="variant_special_price[]"]').val());
            var mrp = money($row.find('[name="variant_price[]"]').val());
            var stockEl = $row.find('[name="variant_total_stock[]"]');
            var html;
            if (price === null && mrp === null) {
                html = '<span class="czpf-var-missing"><i class="fas fa-exclamation-circle"></i> Add price</span>';
            } else {
                html = '<b>' + (price !== null ? price : mrp) + '</b>' + (price !== null && mrp !== null && price !== mrp ? ' <s>' + mrp + '</s>' : '');
            }
            if (stockEl.length && $.trim(stockEl.val()) !== '') {
                html += ' <span class="czpf-var-stock">&middot; Stock ' + $('<i>').text(stockEl.val()).html() + '</span>';
            }
            if ($sum.data('html') !== html) { $sum.data('html', html).html(html); }
        });
    }
    var sumTimer = null;
    function scheduleSummaries() { clearTimeout(sumTimer); sumTimer = setTimeout(updateVariantSummaries, 80); }
    $(document).on('input change', '#variants_process input, #variants_process select', scheduleSummaries);
    var varBox = document.getElementById('variants_process');
    if (varBox && window.MutationObserver) { new MutationObserver(scheduleSummaries).observe(varBox, { childList: true }); }
    setInterval(updateVariantSummaries, 1500);   // prices pre-filled from Price & stock arrive without an input event
    updateVariantSummaries();

    if (location.hash) { revealFor(location.hash); }
})(window.jQuery);
