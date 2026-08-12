/*
 * CSRF plumbing.
 *
 * config/csrf_protection is now TRUE, which makes CodeIgniter reject any POST that does
 * not carry a valid token. CI injects that token automatically into form_open(), but this
 * codebase has ~36 raw <form method="post"> tags and dozens of hand-written $.ajax/$.post
 * calls that CI knows nothing about - every one of those would have started failing.
 *
 * Rather than editing hundreds of call sites (and having the next one added break again),
 * this file makes the token universal:
 *
 *   1. Every same-origin, non-GET jQuery request gets the token appended - whether its
 *      `data` is a query string, a plain object, or a FormData (file uploads).
 *   2. Every <form> that posts gets a hidden token input, including forms injected into
 *      the DOM later (modals, bootstrap-table row editors).
 *   3. Native fetch()/XHR are covered too, for the few places that don't use jQuery.
 *   4. Any JSON response carrying a fresh csrfName/csrfHash pair updates the stored token,
 *      which is what the ~89 controllers already returning those fields were written for.
 *
 * Token values come from the <meta> tags emitted by the layout, so this file stays static
 * and cacheable.
 */
(function (window, document) {
    'use strict';

    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    }

    var tokenName = meta('csrf-token-name');
    var tokenHash = meta('csrf-token-hash');

    if (!tokenName) {
        return; // CSRF disabled server-side - nothing to do.
    }

    // Exposed so page scripts that build their own payloads can read the current value
    // instead of caching a stale one.
    window.CSRF = {
        name: function () { return tokenName; },
        hash: function () { return tokenHash; },
        update: function (name, hash) {
            if (name) { tokenName = name; }
            if (hash) {
                tokenHash = hash;
                syncFormInputs();
                var el = document.querySelector('meta[name="csrf-token-hash"]');
                if (el) { el.setAttribute('content', hash); }
            }
            // The admin/seller layouts also expose `csrfName`/`csrfHash` globals that
            // predate this file, and custom.js appends them to some payloads by hand.
            // Keep them in step so the two mechanisms can never send different values.
            try {
                if (typeof window.csrfName !== 'undefined' && name) { window.csrfName = tokenName; }
                if (typeof window.csrfHash !== 'undefined' && hash) { window.csrfHash = tokenHash; }
            } catch (e) { /* globals may be block-scoped consts - ignore */ }
        }
    };

    /* ------------------------------------------------------------------ forms */

    function ensureFormInput(form) {
        var method = (form.getAttribute('method') || '').toUpperCase();
        if (method !== 'POST') {
            return;
        }
        // Leave cross-origin form targets alone - never post our token elsewhere.
        var action = form.getAttribute('action');
        if (action && /^https?:\/\//i.test(action) && action.indexOf(window.location.origin) !== 0) {
            return;
        }
        var input = form.querySelector('input[name="' + tokenName + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = tokenName;
            form.appendChild(input);
        }
        input.value = tokenHash;
    }

    function syncFormInputs() {
        var forms = document.getElementsByTagName('form');
        for (var i = 0; i < forms.length; i++) {
            ensureFormInput(forms[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncFormInputs);
    } else {
        syncFormInputs();
    }

    // Forms added after load (modals, templates) - stamp them at submit time.
    document.addEventListener('submit', function (e) {
        if (e.target && e.target.tagName === 'FORM') {
            ensureFormInput(e.target);
        }
    }, true);

    /* ------------------------------------------------------------------ helpers */

    function isSameOrigin(url) {
        if (!url) { return true; }
        if (/^https?:\/\//i.test(url) || url.indexOf('//') === 0) {
            return url.indexOf(window.location.origin) === 0 || url.indexOf('//' + window.location.host) === 0;
        }
        return true; // relative URL
    }

    function needsToken(method) {
        return method && ['GET', 'HEAD', 'OPTIONS', 'TRACE'].indexOf(method.toUpperCase()) === -1;
    }

    /* ------------------------------------------------------------------ jQuery */

    function installJqueryHooks() {
        if (!window.jQuery || installJqueryHooks.done) {
            return !!installJqueryHooks.done;
        }
        installJqueryHooks.done = true;

        var $ = window.jQuery;

        $.ajaxPrefilter(function (options) {
            if (!needsToken(options.type) || !isSameOrigin(options.url)) {
                return;
            }

            // FormData (file uploads): set() overwrites any stale token already appended
            // by older page code, so the value is never sent twice with different values.
            if (window.FormData && options.data instanceof FormData) {
                if (typeof options.data.set === 'function') {
                    options.data.set(tokenName, tokenHash);
                } else {
                    options.data.append(tokenName, tokenHash);
                }
                return;
            }

            if (typeof options.data === 'string') {
                // Strip any existing copy so we don't send two conflicting values.
                var stripped = options.data
                    .split('&')
                    .filter(function (pair) { return pair.indexOf(encodeURIComponent(tokenName) + '=') !== 0 && pair.indexOf(tokenName + '=') !== 0; })
                    .join('&');
                options.data = (stripped ? stripped + '&' : '') + encodeURIComponent(tokenName) + '=' + encodeURIComponent(tokenHash);
                return;
            }

            if (options.data === undefined || options.data === null) {
                options.data = {};
            }

            if ($.isPlainObject(options.data)) {
                options.data[tokenName] = tokenHash;
            }
        });

        // Controllers already return a refreshed csrfName/csrfHash pair - adopt it.
        $(document).ajaxSuccess(function (event, xhr) {
            var body;
            try {
                body = xhr.responseJSON;
                if (!body && xhr.responseText) {
                    body = JSON.parse(xhr.responseText);
                }
            } catch (err) {
                return;
            }
            if (body && (body.csrfHash || body.csrfName)) {
                window.CSRF.update(body.csrfName, body.csrfHash);
            }
        });

        return true;
    }

    // Not every layout loads jQuery before this file - the seller login page, for one,
    // has a branch with its own <head>. Retry at the usual milestones so the prefilter is
    // always registered before page scripts start firing requests.
    if (!installJqueryHooks()) {
        document.addEventListener('DOMContentLoaded', installJqueryHooks);
        window.addEventListener('load', installJqueryHooks);
    }

    /* ------------------------------------------------------------------ fetch */

    if (window.fetch) {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            var url = (typeof input === 'string') ? input : (input && input.url);
            var method = init.method || (input && input.method) || 'GET';

            if (needsToken(method) && isSameOrigin(url)) {
                if (window.FormData && init.body instanceof FormData) {
                    init.body.set(tokenName, tokenHash);
                } else if (typeof init.body === 'string' && init.body.indexOf(tokenName + '=') === -1) {
                    // Only for urlencoded bodies; never touch a JSON payload.
                    var ct = (init.headers && (init.headers['Content-Type'] || init.headers['content-type'])) || '';
                    if (ct.indexOf('application/x-www-form-urlencoded') !== -1) {
                        init.body += (init.body ? '&' : '') + encodeURIComponent(tokenName) + '=' + encodeURIComponent(tokenHash);
                    }
                }
            }
            return nativeFetch.call(this, input, init);
        };
    }

    /* ------------------------------------------------------------------ raw XHR */

    if (window.XMLHttpRequest) {
        var origOpen = XMLHttpRequest.prototype.open;
        var origSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url) {
            this.__csrfNeeded = needsToken(method) && isSameOrigin(url);
            return origOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function (body) {
            if (this.__csrfNeeded) {
                if (window.FormData && body instanceof FormData) {
                    if (typeof body.set === 'function') {
                        body.set(tokenName, tokenHash);
                    }
                } else if (typeof body === 'string' && body.indexOf(tokenName + '=') === -1 && body.charAt(0) !== '{') {
                    body += (body ? '&' : '') + encodeURIComponent(tokenName) + '=' + encodeURIComponent(tokenHash);
                }
            }
            return origSend.call(this, body);
        };
    }
})(window, document);
