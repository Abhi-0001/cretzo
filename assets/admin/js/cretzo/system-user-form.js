/*
 * Add/Edit System User - turns the per-section access choice (None/View/Edit/Full) into the
 * permissions[module][action] fields the save endpoint expects.
 *
 *   view -> read
 *   edit -> read + create + update
 *   full -> every action the module offers (adds delete)
 *
 * Config comes from window.SU_PERM (see the view). A section whose saved permissions match none
 * of those shapes is shown as "Custom" and its saved values are kept until a level is picked.
 */
window.addEventListener('load', function () {
    'use strict';

    var $ = window.jQuery;
    var cfg = window.SU_PERM;
    if (!$ || !cfg) { return; }

    var levels = {};   // section -> 'none' | 'view' | 'edit' | 'full' | 'custom'
    var kept = {};     // section -> saved permissions kept while 'custom'

    function actionsFor(allowed, level) {
        if (level === 'view') { return allowed.indexOf('read') > -1 ? ['read'] : []; }
        if (level === 'edit') { return allowed.filter(function (a) { return a !== 'delete'; }); }
        if (level === 'full') { return allowed.slice(); }
        return [];
    }

    function detectLevel(mods) {
        var names = Object.keys(mods);
        var tried = ['none', 'view', 'edit', 'full'];
        for (var i = 0; i < tried.length; i++) {
            var ok = names.every(function (m) {
                var have = Object.keys((cfg.stored && cfg.stored[m]) || {}).sort().join(',');
                return have === actionsFor(mods[m], tried[i]).slice().sort().join(',');
            });
            if (ok) { return tried[i]; }
        }
        return 'custom';
    }

    function paint() {
        $('.su-section').each(function () {
            var lv = levels[$(this).data('section')];
            $(this).find('.su-level').removeClass('active');
            $(this).find('.su-level[data-level="' + lv + '"]').addClass('active');
            $(this).find('.su-custom').toggle(lv === 'custom');
        });
    }

    function writeInputs() {
        var $box = $('#su_perm_inputs').empty();
        if ($('#su_role').val() === '0') { return; }
        // Everyone can open and edit their own profile page; it is not a choice worth showing.
        ['read', 'update'].forEach(function (a) {
            $('<input type="hidden">').attr('name', 'permissions[profile][' + a + ']').val('on').appendTo($box);
        });
        $.each(cfg.sections, function (key, mods) {
            $.each(mods, function (m, allowed) {
                var acts = levels[key] === 'custom'
                    ? Object.keys((kept[key] && kept[key][m]) || {})
                    : actionsFor(allowed, levels[key]);
                acts.forEach(function (a) {
                    $('<input type="hidden">').attr('name', 'permissions[' + m + '][' + a + ']').val('on').appendTo($box);
                });
            });
        });
    }

    function applyRole(role, usePreset) {
        $('#su_role').val(role);
        $('.su-role').removeClass('active').filter('[data-role="' + role + '"]').addClass('active');
        $('#su_access_card').toggle(role !== '' && role !== '0');
        if (usePreset && cfg.presets[role]) {
            $.each(cfg.sections, function (key) {
                levels[key] = cfg.presets[role][key] || 'none';
            });
        }
        paint();
        writeInputs();
    }

    // Initial state: stored permissions when editing, otherwise nothing granted until a role is picked.
    $.each(cfg.sections, function (key, mods) {
        if (cfg.isEdit) {
            levels[key] = detectLevel(mods);
            if (levels[key] === 'custom') {
                kept[key] = {};
                Object.keys(mods).forEach(function (m) { kept[key][m] = (cfg.stored || {})[m] || {}; });
            }
        } else {
            levels[key] = 'none';
        }
    });
    applyRole($('#su_role').val(), false);

    $('.su-role').on('click', function () {
        applyRole(String($(this).data('role')), true);
    });

    $('.su-level').on('click', function () {
        var $s = $(this).closest('.su-section');
        levels[$s.data('section')] = $(this).data('level');
        paint();
        writeInputs();
    });
});
