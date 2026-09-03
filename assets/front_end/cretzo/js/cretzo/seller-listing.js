/* ==========================================================================
   Cretzo - seller directory (/sellers)
   --------------------------------------------------------------------------
   Progressive enhancement only: the toolbar in pages/seller-listing.php is a
   plain GET form that already works without this file. Here we just submit it
   on change, wire the clear button and the grid/list toggle, and strip empty
   parameters so the resulting URL stays readable and shareable.

   Nothing here touches #product_sort_by / #seller_search - the ids the old
   page shared with the product-listing handlers in custom.js. Those handlers
   each rewrote location.href on their own, so one change on this page could
   kick off several competing navigations.
   ========================================================================== */
(function () {
    'use strict';

    var form = document.getElementById('czsl-form');
    if (!form) {
        return;
    }

    var search = document.getElementById('czsl-search');
    var clearBtn = document.getElementById('czsl-clear');
    var typeField = document.getElementById('czsl-type');

    /*
     * Empty controls would otherwise land in the query string as
     * ?seller_search=&sort=&type=grid. Disabled fields are not submitted, so
     * blanking them here keeps /sellers clean - and re-enabling them
     * afterwards matters because a browser restoring this page from its
     * back/forward cache shows the very same DOM.
     */
    function submitClean() {
        var muted = [];
        var defaults = { seller_search: '', sort: '', type: 'grid' };

        Object.keys(defaults).forEach(function (name) {
            var field = form.elements[name];
            if (field && String(field.value).trim() === defaults[name]) {
                field.disabled = true;
                muted.push(field);
            }
        });

        form.submit();

        window.setTimeout(function () {
            muted.forEach(function (field) { field.disabled = false; });
        }, 0);
    }

    /* Sort + per-page: apply immediately, the way every other listing behaves. */
    ['czsl-sort', 'czsl-per-page'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', submitClean);
        }
    });

    /* Search. Enter submits natively; this only adds the "search" event the
       native clear "x" fires, and our own clear button. */
    if (search) {
        search.addEventListener('search', function () {
            if (search.value.trim() === '') {
                submitClean();
            }
        });
    }

    if (clearBtn && search) {
        clearBtn.addEventListener('click', function () {
            search.value = '';
            submitClean();
        });
    }

    /* Route every submit - including Enter in the search box - through the
       cleaner, so the URL has the same shape however it was triggered.
       form.submit() does not re-fire this event, so there is no loop. */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitClean();
    });

    /* Grid / list toggle. Writes the `type` param and reloads, so the choice
       survives paging and can be linked to. */
    Array.prototype.forEach.call(form.querySelectorAll('[data-czsl-set-view]'), function (btn) {
        btn.addEventListener('click', function () {
            var next = btn.getAttribute('data-czsl-set-view');
            if (!typeField || typeField.value === next) {
                return;
            }
            typeField.value = next;
            submitClean();
        });
    });
}());
