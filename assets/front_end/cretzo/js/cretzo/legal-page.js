/* ==========================================================================
 * Cretzo - policy documents (Terms, Privacy, Returns, Shipping).
 *
 * Progressive enhancement only. Everything this file adds is a convenience -
 * the contents list, the anchors and the whole document work with scripting
 * off, because legal-page.php builds them server-side.
 *
 *   - highlights the clause you are currently reading in the contents list
 *   - collapses that list on narrow screens (25 links would otherwise push the
 *     document off the first screen)
 *   - wraps bare <table>s so a wide table scrolls in its own box instead of
 *     widening the page
 *   - back-to-top, and the print button
 * ========================================================================== */
(function () {
    'use strict';

    var root = document.querySelector('.czlegal');
    if (!root) {
        return;
    }

    document.body.classList.add('czlegal-body');

    var doc = document.getElementById('czlegal-doc');
    var toc = document.getElementById('czlegal-toc');

    /* ------------------------------ print ------------------------------ */
    var printBtn = document.getElementById('czlegal-print');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    /* --------------------------- table wrapping --------------------------- */
    /* The stored documents contain bare <table>s with no wrapper. On a phone a
       wide one would stretch the page and give the whole body a horizontal
       scrollbar, so each gets its own scroll box. */
    if (doc) {
        Array.prototype.forEach.call(doc.querySelectorAll('table'), function (table) {
            if (table.parentNode && table.parentNode.classList.contains('czlegal__table-scroll')) {
                return;
            }
            var wrap = document.createElement('div');
            wrap.className = 'czlegal__table-scroll';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    }

    /* --------------------------- back to top --------------------------- */
    var topBtn = document.getElementById('czlegal-top');
    if (topBtn) {
        topBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ------------------------- contents behaviour ------------------------- */
    if (!toc) {
        return;
    }

    var links = Array.prototype.slice.call(toc.querySelectorAll('.czlegal__toc-list a'));
    var headings = links
        .map(function (a) {
            return document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1)));
        })
        .filter(Boolean);

    /* ---- accurate jump ----
     * The browser's own anchor jump fires before the page has finished settling
     * (lazy images above the target still resolving), so it lands short - a
     * click on clause 14 put the heading 349px down the viewport instead of at
     * the anchor offset, and the contents list then highlighted clause 13.
     * Scrolling from JS after layout has settled lands exactly, and the hash is
     * still written so the URL stays shareable.
     */
    toc.addEventListener('click', function (e) {
        var link = e.target.closest('.czlegal__toc-list a');
        if (!link) {
            return;
        }
        var id = decodeURIComponent(link.getAttribute('href').slice(1));
        var target = document.getElementById(id);
        if (!target) {
            return; // let the browser try
        }

        e.preventDefault();

        var offset = parseInt(
            getComputedStyle(root).getPropertyValue('--czl-anchor-offset'),
            10
        ) || 90;

        var jump = function () {
            var top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        };

        // Two frames: one for any collapse/expand this click triggers, one for
        // the resulting reflow.
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(jump);
        });

        // history, not location - assigning location.hash would re-trigger the
        // native jump we just replaced.
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + id);
        }
        setCurrent(id);
    });

    /* ---- collapsible on narrow screens ---- */
    var label = toc.querySelector('.czlegal__toc-label');
    if (label) {
        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'czlegal__toc-toggle';
        toggle.innerHTML = '<span>' + label.textContent + '</span><i class="uil uil-angle-down"></i>';
        toggle.setAttribute('aria-expanded', 'true');
        label.parentNode.insertBefore(toggle, label);
        label.hidden = true;

        var NARROW = window.matchMedia('(max-width: 1024px)');

        var applyNarrow = function () {
            if (NARROW.matches) {
                toc.classList.add('is-collapsed');
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                toc.classList.remove('is-collapsed');
                toggle.setAttribute('aria-expanded', 'true');
            }
        };
        applyNarrow();

        // addListener is the deprecated name, but Safari < 14 has only that one.
        if (NARROW.addEventListener) {
            NARROW.addEventListener('change', applyNarrow);
        } else if (NARROW.addListener) {
            NARROW.addListener(applyNarrow);
        }

        toggle.addEventListener('click', function () {
            var collapsed = toc.classList.toggle('is-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });

        // Picking a clause on a phone should get out of the way afterwards.
        toc.addEventListener('click', function (e) {
            if (e.target.closest('.czlegal__toc-list a') && NARROW.matches) {
                toc.classList.add('is-collapsed');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ---- scroll spy ---- */
    if (!headings.length) {
        return;
    }

    var current = null;

    function setCurrent(id) {
        if (id === current) {
            return;
        }
        current = id;
        links.forEach(function (a) {
            var on = decodeURIComponent(a.getAttribute('href').slice(1)) === id;
            a.classList.toggle('is-current', on);
            if (on) {
                a.setAttribute('aria-current', 'true');
                // Keep the active entry visible in a long, scrolling list -
                // but only scroll the list itself, never the page.
                var list = a.closest('.czlegal__toc-list');
                if (list && list.scrollHeight > list.clientHeight) {
                    var aTop = a.offsetTop;
                    if (aTop < list.scrollTop || aTop > list.scrollTop + list.clientHeight - a.offsetHeight) {
                        list.scrollTop = aTop - list.clientHeight / 2;
                    }
                }
            } else {
                a.removeAttribute('aria-current');
            }
        });
    }

    function update() {
        // The heading whose top has most recently passed the reading line.
        var line = 140;
        var active = headings[0];
        for (var i = 0; i < headings.length; i++) {
            if (headings[i].getBoundingClientRect().top <= line) {
                active = headings[i];
            } else {
                break;
            }
        }

        // At the very bottom the last clause is the one being read, even if its
        // heading never crosses the line (a short final section).
        if (window.innerHeight + window.scrollY >= document.body.scrollHeight - 4) {
            active = headings[headings.length - 1];
        }

        setCurrent(active.id);
        if (topBtn) {
            topBtn.hidden = window.scrollY < 600;
        }
    }

    var ticking = false;
    function onScroll() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            update();
            ticking = false;
        });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    update();
})();
