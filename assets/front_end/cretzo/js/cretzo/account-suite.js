/* ==========================================================================
 * Cretzo - My Account shared behaviour.
 *
 * Loaded on every /my-account page by include-script.php. Provides:
 *
 *   window.CzAccount.open(sel) / close(sel) / toggle       popup controller
 *   window.CzAccount.confirm({...}) -> Promise<boolean>     styled confirm
 *   window.CzAccount.toast(msg, tone)                       thin Toast wrapper
 *   [data-czap-open="#id"] / [data-czap-close]              declarative popups
 *
 * WHY a hand-rolled popup instead of Bootstrap's: include-script.php loads
 * plugins.js (Bootstrap 5.2.2) and then bootstrap.min.js (Bootstrap 4.0.0).
 * `$.fn.modal` therefore comes from v4 while the `data-bs-*` data-api comes
 * from v5, and the two keep separate ideas of the backdrop and the body lock -
 * which is why address.js needed a `hidden.bs.modal` handler to sweep up
 * orphaned .modal-backdrop elements and a body left at overflow:hidden. One
 * controller with one owner removes that whole class of bug.
 * ========================================================================== */
(function (window, document) {
    'use strict';

    var openStack = [];
    var lastFocus = null;

    var FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),' +
        'select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

    function el(sel) {
        if (!sel) {
            return null;
        }
        if (sel.nodeType === 1) {
            return sel;
        }
        // Accept both '#id' and a bare 'id'.
        return document.querySelector(sel.charAt(0) === '#' || sel.charAt(0) === '.' ? sel : '#' + sel);
    }

    function lockBody(on) {
        document.body.classList.toggle('czap-locked', !!on);
    }

    function focusFirst(modal) {
        // Prefer an explicit autofocus target, else the first real control that
        // is not the close button, else the panel itself.
        var target = modal.querySelector('[data-czap-autofocus]');
        if (!target) {
            var candidates = modal.querySelectorAll(FOCUSABLE);
            for (var i = 0; i < candidates.length; i++) {
                if (!candidates[i].hasAttribute('data-czap-close')) {
                    target = candidates[i];
                    break;
                }
            }
        }
        if (!target) {
            target = modal.querySelector('.czap-modal__panel');
        }
        if (target) {
            try {
                target.focus({ preventScroll: true });
            } catch (e) {
                target.focus();
            }
        }
    }

    function open(sel) {
        var modal = el(sel);
        if (!modal || modal.classList.contains('is-open')) {
            return modal;
        }
        if (!openStack.length) {
            lastFocus = document.activeElement;
        }
        modal.hidden = false;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        openStack.push(modal);
        lockBody(true);
        focusFirst(modal);
        modal.dispatchEvent(new CustomEvent('czap:open', { bubbles: true }));
        return modal;
    }

    function close(sel) {
        var modal = sel ? el(sel) : openStack[openStack.length - 1];
        if (!modal || !modal.classList.contains('is-open')) {
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
        openStack = openStack.filter(function (m) {
            return m !== modal;
        });
        if (!openStack.length) {
            lockBody(false);
            if (lastFocus && document.contains(lastFocus)) {
                try {
                    lastFocus.focus({ preventScroll: true });
                } catch (e) {
                    lastFocus.focus();
                }
            }
            lastFocus = null;
        }
        modal.dispatchEvent(new CustomEvent('czap:close', { bubbles: true }));
    }

    function closeAll() {
        openStack.slice().forEach(function (m) {
            close(m);
        });
    }

    /* ---------------------------- declarative api ---------------------------- */

    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-czap-open]');
        if (opener) {
            e.preventDefault();
            open(opener.getAttribute('data-czap-open'));
            return;
        }

        var closer = e.target.closest('[data-czap-close]');
        if (closer) {
            e.preventDefault();
            // A [data-czap-close] with a value closes that popup; without one it
            // closes whichever popup contains it (so the scrim and the X work).
            var explicit = closer.getAttribute('data-czap-close');
            close(explicit ? explicit : closer.closest('.czap-modal'));
        }
    });

    document.addEventListener('keydown', function (e) {
        if (!openStack.length) {
            return;
        }

        if (e.key === 'Escape' || e.keyCode === 27) {
            var top = openStack[openStack.length - 1];
            if (top.getAttribute('data-czap-static') === null) {
                close(top);
            }
            return;
        }

        // Keep Tab inside the open popup - without this the user tabs into the
        // page behind the scrim, which they cannot see or click.
        if (e.key === 'Tab' || e.keyCode === 9) {
            var modal = openStack[openStack.length - 1];
            var items = Array.prototype.filter.call(modal.querySelectorAll(FOCUSABLE), function (n) {
                return n.offsetParent !== null || n === document.activeElement;
            });
            if (!items.length) {
                return;
            }
            var first = items[0];
            var last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });

    /* ------------------------------- confirm -------------------------------- */
    /*
     * Replaces window.confirm() on these pages. The native dialog cannot be
     * styled, gives no room for the consequence of the action, and on some
     * mobile browsers is suppressed entirely after a few uses - which silently
     * turned "Set as default address" into a dead button.
     *
     * Resolves true when confirmed, false when dismissed any other way.
     */
    var confirmHost = null;

    function buildConfirmHost() {
        if (confirmHost) {
            return confirmHost;
        }
        confirmHost = document.createElement('div');
        confirmHost.className = 'czap-modal czap-modal--sm';
        confirmHost.id = 'czap-confirm';
        confirmHost.hidden = true;
        confirmHost.setAttribute('role', 'dialog');
        confirmHost.setAttribute('aria-modal', 'true');
        confirmHost.setAttribute('aria-labelledby', 'czap-confirm-title');
        confirmHost.innerHTML =
            '<div class="czap-modal__scrim" data-czap-close></div>' +
            '<div class="czap-modal__panel" role="document">' +
            '<div class="czap-confirm">' +
            '<div class="czap-confirm__icon" data-role="icon"><i class="uil uil-exclamation-triangle"></i></div>' +
            '<h2 class="czap-confirm__title" id="czap-confirm-title" data-role="title"></h2>' +
            '<p class="czap-confirm__text" data-role="text"></p>' +
            '</div>' +
            '<div class="czap-modal__foot" style="justify-content:center;border-top:0;background:none">' +
            '<button type="button" class="czap-btn czap-btn--ghost" data-role="cancel"></button>' +
            '<button type="button" class="czap-btn czap-btn--primary" data-role="ok" data-czap-autofocus></button>' +
            '</div>' +
            '</div>';
        document.body.appendChild(confirmHost);
        return confirmHost;
    }

    function confirmDialog(opts) {
        opts = opts || {};
        var host = buildConfirmHost();
        var icon = host.querySelector('[data-role="icon"]');
        var okBtn = host.querySelector('[data-role="ok"]');
        var cancelBtn = host.querySelector('[data-role="cancel"]');

        host.querySelector('[data-role="title"]').textContent = opts.title || 'Are you sure?';
        var textNode = host.querySelector('[data-role="text"]');
        textNode.textContent = opts.text || '';
        textNode.style.display = opts.text ? '' : 'none';

        var tone = opts.tone === 'danger' ? 'bad' : (opts.tone === 'success' ? 'ok' : '');
        icon.className = 'czap-confirm__icon' + (tone ? ' czap-confirm__icon--' + tone : '');
        icon.innerHTML = '<i class="uil ' + (opts.icon || (tone === 'bad' ? 'uil-trash-alt' : 'uil-exclamation-triangle')) + '"></i>';

        okBtn.textContent = opts.confirmText || 'Confirm';
        okBtn.className = 'czap-btn ' + (tone === 'bad' ? 'czap-btn--solid-danger' : 'czap-btn--primary');
        cancelBtn.textContent = opts.cancelText || 'Cancel';

        return new Promise(function (resolve) {
            var settled = false;

            function finish(value) {
                if (settled) {
                    return;
                }
                settled = true;
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                host.removeEventListener('czap:close', onClose);
                resolve(value);
            }

            function onOk() {
                close(host);
                finish(true);
            }

            function onCancel() {
                close(host);
                finish(false);
            }

            // Covers the scrim, the Escape key and any other close path.
            function onClose() {
                finish(false);
            }

            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            host.addEventListener('czap:close', onClose);
            open(host);
        });
    }

    /* -------------------------------- toast --------------------------------- */
    /* custom.js sets up a SweetAlert2 `Toast` mixin; use it when it is there and
       fall back to an inline banner rather than throwing. */
    function toast(message, tone) {
        if (window.Toast && typeof window.Toast.fire === 'function') {
            window.Toast.fire({ icon: tone === 'error' ? 'error' : (tone || 'success'), title: message });
            return;
        }
        var bar = document.getElementById('czap-toast');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'czap-toast';
            bar.style.cssText = 'position:fixed;z-index:30000;left:50%;transform:translateX(-50%);' +
                'top:18px;padding:12px 20px;border-radius:999px;font:600 14px/1 "K2D",sans-serif;' +
                'box-shadow:0 12px 30px -12px rgba(0,0,0,.4)';
            document.body.appendChild(bar);
        }
        bar.style.background = tone === 'error' ? '#d13a3a' : '#1d9d5c';
        bar.style.color = '#fff';
        bar.textContent = message;
        bar.style.display = 'block';
        clearTimeout(bar._t);
        bar._t = setTimeout(function () {
            bar.style.display = 'none';
        }, 3200);
    }

    /* --------------------------- shared behaviours --------------------------- */

    function initRadioPills(root) {
        // Mirror :checked onto .is-checked so .czap-radio is styled on engines
        // without :has() support.
        var inputs = (root || document).querySelectorAll('.czap-radio input[type="radio"],.czap-radio input[type="checkbox"]');
        Array.prototype.forEach.call(inputs, function (input) {
            var sync = function () {
                var name = input.name;
                if (name && input.type === 'radio') {
                    var group = document.getElementsByName(name);
                    Array.prototype.forEach.call(group, function (peer) {
                        var pill = peer.closest('.czap-radio');
                        if (pill) {
                            pill.classList.toggle('is-checked', peer.checked);
                        }
                    });
                } else {
                    var own = input.closest('.czap-radio');
                    if (own) {
                        own.classList.toggle('is-checked', input.checked);
                    }
                }
            };
            input.addEventListener('change', sync);
            sync();
        });
    }

    function initSearchBoxes(root) {
        // A .czap-search with a clear button: toggle the button, clear on click,
        // and submit the owning form on Enter (the input may sit outside it).
        var boxes = (root || document).querySelectorAll('.czap-search');
        Array.prototype.forEach.call(boxes, function (box) {
            var input = box.querySelector('input');
            var clear = box.querySelector('.czap-search__clear');
            if (!input || !clear) {
                return;
            }
            var sync = function () {
                clear.classList.toggle('is-on', input.value.trim().length > 0);
            };
            input.addEventListener('input', sync);
            clear.addEventListener('click', function () {
                input.value = '';
                sync();
                if (clear.hasAttribute('data-czap-clear-reload')) {
                    window.location.href = window.location.pathname;
                } else {
                    input.focus();
                }
            });
            sync();
        });
    }

    function initDigitsOnly(root) {
        // [data-czap-digits] strips non-digits as they are typed and honours
        // maxlength on paste - maxlength alone does not stop a pasted string of
        // letters. Every such field is re-validated server side regardless.
        var fields = (root || document).querySelectorAll('[data-czap-digits]');
        Array.prototype.forEach.call(fields, function (field) {
            if (field.hasAttribute('readonly') || field.disabled) {
                return;
            }
            field.addEventListener('input', function () {
                var max = parseInt(field.getAttribute('maxlength'), 10);
                var digits = field.value.replace(/[^0-9]/g, '');
                if (max > 0) {
                    digits = digits.slice(0, max);
                }
                if (field.value !== digits) {
                    field.value = digits;
                }
            });
        });
    }

    function initCopy(root) {
        // [data-czap-copy="text"] - used for tracking ids and order numbers.
        var buttons = (root || document).querySelectorAll('[data-czap-copy]');
        Array.prototype.forEach.call(buttons, function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var value = btn.getAttribute('data-czap-copy');
                var done = function () {
                    toast('Copied to clipboard');
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(done, function () {
                        toast('Could not copy', 'error');
                    });
                    return;
                }
                // http:// origins get no navigator.clipboard, and this site is
                // reachable over plain http in development.
                var tmp = document.createElement('textarea');
                tmp.value = value;
                tmp.setAttribute('readonly', '');
                tmp.style.cssText = 'position:absolute;left:-9999px';
                document.body.appendChild(tmp);
                tmp.select();
                try {
                    document.execCommand('copy');
                    done();
                } catch (err) {
                    toast('Could not copy', 'error');
                }
                document.body.removeChild(tmp);
            });
        });
    }

    function initFilePickers(root) {
        // Shows the chosen file name and previews an image straight away -
        // otherwise the only feedback that a photo was picked is the page
        // reloading after a successful save.
        var inputs = (root || document).querySelectorAll('[data-czap-file]');
        Array.prototype.forEach.call(inputs, function (input) {
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) {
                    return;
                }
                var nameBox = document.querySelector(input.getAttribute('data-czap-file-name') || '');
                if (nameBox) {
                    nameBox.textContent = file.name;
                }
                var preview = document.querySelector(input.getAttribute('data-czap-file') || '');
                if (preview && /^image\//.test(file.type)) {
                    preview.classList.remove('is-placeholder');
                    preview.src = URL.createObjectURL(file);
                }
            });
        });
    }

    function initNavScroll() {
        // On the mobile chip rail the active item can start off-screen; bring it
        // into view without scrolling the page itself.
        var nav = document.querySelector('.czap-nav');
        if (!nav || nav.scrollWidth <= nav.clientWidth) {
            return;
        }
        var active = nav.querySelector('.czap-nav__link.is-active');
        if (active) {
            nav.scrollLeft = Math.max(0, active.offsetLeft - 16);
        }
    }

    function initAll(root) {
        initRadioPills(root);
        initSearchBoxes(root);
        initDigitsOnly(root);
        initCopy(root);
        initFilePickers(root);
    }

    /* -------------------------------- expose -------------------------------- */
    window.CzAccount = {
        open: open,
        close: close,
        closeAll: closeAll,
        confirm: confirmDialog,
        toast: toast,
        init: initAll
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll(document);
            initNavScroll();
        });
    } else {
        initAll(document);
        initNavScroll();
    }
})(window, document);
