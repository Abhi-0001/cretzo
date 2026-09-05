/**
 * ============================================================================
 * Referral QR codes - shared by the storefront, the seller panel and the
 * printable card.
 * ============================================================================
 *
 * Renders a referral share link as a QR code entirely in the browser. Nothing
 * is sent anywhere: the alternative - an image API like api.qrserver.com -
 * would hand every user's referral code to a third party on every page view,
 * stop working when that service changes, and put a network round trip in front
 * of a picture we can draw locally in a millisecond.
 *
 * Depends on assets/vendor/qrcode.min.js (qrcode-generator 1.4.4, MIT), which
 * must be loaded first.
 *
 * USAGE - markup only, no per-page JavaScript:
 *
 *     <div data-referral-qr="https://cretzo.com/?ref=ABCD1234&src=qr"
 *          data-qr-size="180"
 *          data-qr-filename="cretzo-referral-ABCD1234"></div>
 *
 *     <button data-qr-save="#the-container">Save</button>
 *     <button data-qr-zoom="#the-container">Enlarge</button>
 *
 * WHY THE MODULES ARE DRAWN BY HAND
 * ---------------------------------
 * The library can emit an <img> or a table, but a canvas is what makes the
 * download work: the same code is re-rendered at 1024px for saving, so a card
 * printed from the downloaded file is sharp at any size. Drawing rectangles
 * ourselves is also what lets the quiet zone be exact - see below.
 *
 * THE THREE RULES THAT DECIDE WHETHER A PRINTED QR ACTUALLY SCANS
 * ---------------------------------------------------------------
 *   1. Pure black on pure white. A brand-tinted QR fails in poor light and on
 *      cheap printers; the brand belongs in the card around the code.
 *   2. Error correction level Q (25% recoverable), so a card that gets creased
 *      in a parcel still reads.
 *   3. A quiet zone of 4 modules on every side. Cropping tight to the code is
 *      the commonest way to produce a QR that nothing can scan.
 */
(function (window, document) {
    'use strict';

    var QUIET_ZONE = 4;          // modules, per the QR spec's minimum
    var ERROR_LEVEL = 'Q';       // 25% recoverable
    var DOWNLOAD_PX = 1024;      // big enough to print at any card size

    function draw(canvas, url, pixels) {
        if (typeof window.qrcode !== 'function') {
            return false;
        }

        /* Type 0 = pick the smallest symbol version that fits the data. A
         * referral URL is short, so this lands on a low-density code that scans
         * quickly on an old phone camera. */
        var qr = window.qrcode(0, ERROR_LEVEL);
        qr.addData(url);
        qr.make();

        var count = qr.getModuleCount();
        var total = count + QUIET_ZONE * 2;

        /* The module size is floored to a whole number of device pixels and the
         * canvas resized to match. A fractional module size makes the browser
         * anti-alias the edges, and a blurred module boundary is exactly what a
         * scanner cannot resolve. */
        var moduleSize = Math.max(1, Math.floor(pixels / total));
        var size = moduleSize * total;

        canvas.width = size;
        canvas.height = size;
        canvas.style.width = '100%';
        canvas.style.height = 'auto';

        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, size, size);

        ctx.fillStyle = '#000000';
        for (var row = 0; row < count; row++) {
            for (var col = 0; col < count; col++) {
                if (qr.isDark(row, col)) {
                    ctx.fillRect(
                        (col + QUIET_ZONE) * moduleSize,
                        (row + QUIET_ZONE) * moduleSize,
                        moduleSize,
                        moduleSize
                    );
                }
            }
        }

        return true;
    }

    function render(container) {
        var url = container.getAttribute('data-referral-qr');
        if (!url) {
            return;
        }

        var size = parseInt(container.getAttribute('data-qr-size'), 10) || 180;
        var canvas = container.querySelector('canvas');

        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.className = 'referral-qr__canvas';
            /* The code is a picture of a link that is written out in text right
             * beside it on every surface that uses this, so the canvas itself
             * adds nothing for a screen reader. */
            canvas.setAttribute('role', 'img');
            canvas.setAttribute('aria-label', 'QR code for your referral link');
            container.appendChild(canvas);
        }

        if (!draw(canvas, url, size)) {
            /* The library failed to load. A missing picture is survivable - the
             * code and the copyable link are always printed next to it - so this
             * hides the empty box rather than showing a broken frame. */
            container.style.display = 'none';
        }
    }

    function save(container) {
        var url = container.getAttribute('data-referral-qr');
        var name = container.getAttribute('data-qr-filename') || 'cretzo-referral';

        /* Rendered fresh at print resolution rather than scaling the on-screen
         * canvas, which would save a 180px image blown up and blurred. */
        var big = document.createElement('canvas');
        if (!draw(big, url, DOWNLOAD_PX)) {
            return;
        }

        var link = document.createElement('a');
        link.download = name + '.png';
        link.href = big.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /* Enlarge: for showing the code to somebody standing in front of you, which
     * is the whole point of a QR on a phone. Built here rather than with the
     * theme's modal because this file runs in three different pages whose
     * modal libraries disagree with each other. */
    function zoom(container) {
        var url = container.getAttribute('data-referral-qr');

        var overlay = document.createElement('div');
        overlay.className = 'referral-qr-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-label', 'Referral QR code');
        overlay.innerHTML =
            '<div class="referral-qr-overlay__box">' +
            '<canvas class="referral-qr-overlay__canvas"></canvas>' +
            '<p class="referral-qr-overlay__hint">Point a phone camera at this code</p>' +
            '<button type="button" class="referral-qr-overlay__close">Close</button>' +
            '</div>';

        document.body.appendChild(overlay);
        draw(overlay.querySelector('canvas'), url, 640);

        var close = function () {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            document.removeEventListener('keydown', onKey);
        };
        var onKey = function (e) {
            if (e.key === 'Escape') {
                close();
            }
        };

        overlay.addEventListener('click', function (e) {
            // The backdrop closes; a click on the code itself must not.
            if (e.target === overlay || e.target.className === 'referral-qr-overlay__close') {
                close();
            }
        });
        document.addEventListener('keydown', onKey);
    }

    function init() {
        var containers = document.querySelectorAll('[data-referral-qr]');
        for (var i = 0; i < containers.length; i++) {
            render(containers[i]);
        }

        document.addEventListener('click', function (e) {
            var saveBtn = e.target.closest ? e.target.closest('[data-qr-save]') : null;
            if (saveBtn) {
                e.preventDefault();
                var target = document.querySelector(saveBtn.getAttribute('data-qr-save'));
                if (target) {
                    save(target);
                }
                return;
            }

            var zoomBtn = e.target.closest ? e.target.closest('[data-qr-zoom]') : null;
            if (zoomBtn) {
                e.preventDefault();
                var zoomTarget = document.querySelector(zoomBtn.getAttribute('data-qr-zoom'));
                if (zoomTarget) {
                    zoom(zoomTarget);
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.ReferralQR = { render: render, save: save, zoom: zoom };
})(window, document);
