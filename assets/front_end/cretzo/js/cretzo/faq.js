/* ==========================================================================
 * Cretzo - FAQ / Help Centre
 * --------------------------------------------------------------------------
 * Pure progressive enhancement over pages/faq.php. Everything here only hides,
 * shows or highlights markup the server already rendered: with this file absent
 * or broken, every question and answer is still on the page and every <details>
 * still opens, because the accordion is native.
 *
 * include-script.php emits this automatically from $main_page === 'faq'.
 * ========================================================================== */
(function () {
    'use strict';

    var root = document.querySelector('.czfaq');
    if (!root) {
        return;
    }

    var search = document.getElementById('czfaq-search');
    var clearBtn = document.getElementById('czfaq-search-clear');
    var countEl = document.getElementById('czfaq-count');
    var noResult = document.getElementById('czfaq-noresult');
    var noResultTerm = document.getElementById('czfaq-noresult-term');
    var helpCard = root.querySelector('.czfaq__help');
    var topicBtns = Array.prototype.slice.call(root.querySelectorAll('.czfaq__topic'));
    var groups = Array.prototype.slice.call(root.querySelectorAll('.czfaq__group'));
    var items = Array.prototype.slice.call(root.querySelectorAll('.czfaq__item'));

    var activeTopic = 'all';

    /* The searchable text is captured once, before any highlight markup is
     * injected - re-reading textContent after a highlight would still work, but
     * caching keeps typing cheap and keeps the source of truth stable. */
    items.forEach(function (item) {
        var q = item.querySelector('.czfaq__q-text');
        var a = item.querySelector('.czfaq__a p');
        item._czfaqQ = q ? q.textContent : '';
        item._czfaqA = a ? a.textContent : '';
        item._czfaqHay = (item._czfaqQ + ' ' + item._czfaqA).toLowerCase();
        item._czfaqQNode = q;
    });

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* Highlighting rebuilds the question from the cached plain text every time,
     * so escaping here is what keeps a question containing "<" from becoming
     * markup. Never interpolate the raw DOM string. */
    function paintQuestion(item, term) {
        if (!item._czfaqQNode) {
            return;
        }
        var text = item._czfaqQ;
        if (!term) {
            item._czfaqQNode.textContent = text;
            return;
        }
        var at = text.toLowerCase().indexOf(term);
        if (at === -1) {
            item._czfaqQNode.textContent = text;
            return;
        }
        item._czfaqQNode.innerHTML =
            escapeHtml(text.slice(0, at)) +
            '<mark class="czfaq__hit">' + escapeHtml(text.slice(at, at + term.length)) + '</mark>' +
            escapeHtml(text.slice(at + term.length));
    }

    function apply() {
        var term = search ? search.value.trim().toLowerCase() : '';
        var shown = 0;

        items.forEach(function (item) {
            var group = item.closest ? item.closest('.czfaq__group') : null;
            var topicOk = activeTopic === 'all'
                || (group && group.getAttribute('data-topic') === activeTopic);
            var termOk = term === '' || item._czfaqHay.indexOf(term) !== -1;
            var visible = topicOk && termOk;

            item.hidden = !visible;
            /* A search should surface the answer, not just the question - but
             * closing on clear would fight someone who opened one by hand, so
             * only searching opens and only clearing the search closes. */
            if (term !== '') {
                item.open = visible;
            }
            paintQuestion(item, visible ? term : '');
            if (visible) {
                shown++;
            }
        });

        /* A group heading with nothing under it reads as a bug. */
        groups.forEach(function (group) {
            var any = group.querySelector('.czfaq__item:not([hidden])');
            group.hidden = !any;
        });

        topicBtns.forEach(function (btn) {
            var key = btn.getAttribute('data-topic');
            if (key === 'all') {
                btn.classList.toggle('is-empty', shown === 0);
                return;
            }
            var group = root.querySelector('.czfaq__group[data-topic="' + key + '"]');
            var hits = group
                ? group.querySelectorAll('.czfaq__item:not([hidden])').length
                : 0;
            /* With a topic filter on, the other topics are hidden by the filter,
             * not by the search - dimming them all would be a lie. */
            btn.classList.toggle('is-empty', term !== '' && activeTopic === 'all' && hits === 0);
        });

        if (noResult) {
            noResult.hidden = shown !== 0;
            if (shown === 0 && noResultTerm) {
                noResultTerm.textContent = search ? '"' + search.value.trim() + '"' : '';
            }
        }
        if (helpCard) {
            helpCard.hidden = shown === 0;
        }
        if (clearBtn) {
            clearBtn.hidden = term === '';
        }

        if (countEl) {
            if (term === '' && activeTopic === 'all') {
                countEl.textContent = '';
            } else if (shown === 0) {
                countEl.textContent = 'No matching answers';
            } else {
                countEl.textContent = shown + (shown === 1 ? ' answer' : ' answers') +
                    (term !== '' ? ' matching "' + search.value.trim() + '"' : '');
            }
        }
    }

    if (search) {
        var timer = null;
        search.addEventListener('input', function () {
            /* Debounced: every keystroke otherwise re-renders every question. */
            window.clearTimeout(timer);
            timer = window.setTimeout(apply, 120);
        });
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                search.value = '';
                apply();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            search.value = '';
            /* Closing what the search opened, so clearing returns the page to
             * the state it loaded in. */
            items.forEach(function (item) {
                item.open = false;
            });
            apply();
            search.focus();
        });
    }

    topicBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeTopic = btn.getAttribute('data-topic') || 'all';
            topicBtns.forEach(function (other) {
                other.classList.toggle('is-active', other === btn);
            });
            apply();
            /* On the phone layout the topics are a rail above the answers; the
             * list below has already changed off-screen without this. */
            if (window.matchMedia('(max-width: 991px)').matches) {
                var target = root.querySelector('.czfaq__main');
                if (target) {
                    window.scrollTo({
                        top: target.getBoundingClientRect().top + window.pageYOffset - 80,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    /* Deep link: /home/faq#faq-39 (support sends these) must land on the answer
     * open, not on a closed row. */
    function openFromHash() {
        var hash = window.location.hash;
        if (!hash || hash.indexOf('#faq-') !== 0) {
            return;
        }
        var item = document.getElementById(hash.slice(1));
        if (!item || !item.classList.contains('czfaq__item')) {
            return;
        }
        item.open = true;
        window.setTimeout(function () {
            item.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }, 80);
    }

    window.addEventListener('hashchange', openFromHash);
    openFromHash();
    apply();
})();
