<?php
/**
 * Seller > Refer & Grow > printable card.
 *
 * A bare page, not a panel page: it exists to be printed, and a sheet mostly
 * filled with a sidebar and a navbar is a wasted sheet.
 *
 * Two sizes on one screen so a seller chooses rather than configures:
 *
 *   A5      for a stall table, a studio wall, a workshop noticeboard
 *   Card    business-card size, to drop into a parcel
 *
 * Printing rules that decide whether the code actually scans afterwards:
 *   - the QR is black on white, never brand orange - the colour is in the frame
 *     around it, because a tinted code fails in poor light and on cheap printers;
 *   - the module grid is drawn at whole pixels with a 4-module quiet zone (see
 *     assets/referral-qr.js), and nothing here crops it;
 *   - the code is printed in readable type underneath, so a scanner that cannot
 *     read the picture is never the end of the road.
 *
 * The print stylesheet hides everything but the chosen size, so what comes out of
 * the printer is one card - not the toolbar, and not the other size.
 */
$currency = isset($currency) ? $currency : '';
$store = trim((string) $store_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Referral card &middot; <?= html_escape($code) ?></title>
    <link rel="stylesheet" href="<?= add_ver(base_url('assets/referral-qr.css')) ?>">
    <style>
        :root {
            --ink: #241d14;
            --ink-2: #5f5648;
            --ink-3: #8b8071;
            --orange: #F2822E;
            --cream: #fbe3bf;
            --line: rgba(36, 29, 20, .14);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eceff3;
            color: var(--ink);
            font-family: "K2D", "Assistant", system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }
        .bar h1 { margin: 0 auto 0 0; font-size: 17px; font-weight: 700; }
        .bar button, .bar a {
            padding: 8px 16px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: var(--ink);
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .bar .primary { border-color: transparent; background: var(--ink); color: #fff; }
        .bar .is-active { border-color: var(--orange); color: #d2691e; }

        .sheet { display: grid; place-items: center; padding: 26px 16px 50px; }

        /* ------------------------------------------------------------- A5 -- */
        .card-a5 {
            width: 148mm;
            height: 210mm;
            padding: 16mm 14mm;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 6px 26px rgba(36, 29, 20, .14);
            display: flex;
            flex-direction: column;
            align-items: center;
            /* Content sits optically centred with the web address pinned to the
               foot, rather than everything crowding the top of a tall sheet. */
            justify-content: center;
            text-align: center;
        }
        .card-a5 .brand { font-size: 13px; letter-spacing: .22em; text-transform: uppercase; color: var(--ink-3); }
        .card-a5 .store { margin: 6mm 0 2mm; font-size: 30px; font-weight: 800; line-height: 1.15; }
        .card-a5 .lede { margin: 0 0 8mm; max-width: 92mm; color: var(--ink-2); font-size: 15px; }
        .card-a5 .qr { width: 62mm; padding: 4mm; border: 1px solid var(--line); border-radius: 4mm; }
        .card-a5 .code {
            margin: 6mm 0 2mm;
            font-size: 26px; font-weight: 800; letter-spacing: .2em;
        }
        .card-a5 .offer {
            margin: 0;
            padding: 3mm 6mm;
            border-radius: 999px;
            background: var(--cream);
            font-size: 15px; font-weight: 600;
        }
        .card-a5 .foot { margin: auto 0 0; padding-top: 10mm; color: var(--ink-3); font-size: 12px; }

        /* ---------------------------------------------------- business card -- */
        .card-sm {
            width: 90mm;
            height: 54mm;
            padding: 6mm;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 6px 26px rgba(36, 29, 20, .14);
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            gap: 6mm;
        }
        .card-sm .qr { width: 34mm; }
        .card-sm .store { margin: 0 0 1mm; font-size: 15px; font-weight: 800; line-height: 1.2; }
        .card-sm .lede { margin: 0 0 2mm; color: var(--ink-2); font-size: 11px; }
        .card-sm .code { font-size: 15px; font-weight: 800; letter-spacing: .16em; }
        .card-sm .foot { margin: 1mm 0 0; color: var(--ink-3); font-size: 9.5px; }

        .hint {
            max-width: 148mm;
            margin: 18px auto 0;
            color: var(--ink-3);
            font-size: 13px;
            text-align: center;
        }

        [hidden] { display: none !important; }

        /* ------------------------------------------------------------ print -- */
        @media print {
            /* Only the chosen card reaches the paper. */
            .bar, .hint { display: none !important; }
            body { background: #fff; }
            .sheet { padding: 0; display: block; }
            .card-a5, .card-sm {
                box-shadow: none;
                border-radius: 0;
                margin: 0;
            }
            /* Colour matters here: the cream offer chip printing as white would
               leave the card looking unfinished. */
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        @page { margin: 8mm; }
    </style>
</head>
<body>

<div class="bar">
    <h1>Your referral card</h1>
    <button type="button" id="size-a5" class="is-active">A5 poster</button>
    <button type="button" id="size-sm">Business card</button>
    <button type="button" class="primary" onclick="window.print();">Print</button>
    <a href="<?= base_url('seller/refer') ?>">Back</a>
</div>

<div class="sheet">
    <!-- A5 ------------------------------------------------------------- -->
    <div class="card-a5" id="card-a5">
        <span class="brand"><?= html_escape($app_name) ?></span>
        <h2 class="store"><?= html_escape($store !== '' ? $store : 'Shop handmade') ?></h2>
        <p class="lede">Scan to shop handmade &mdash; and get your first order discount.</p>

        <div class="qr" id="qr-a5"
             data-referral-qr="<?= html_escape($qr_link) ?>"
             data-qr-size="620"
             data-qr-filename="cretzo-card-<?= html_escape($code) ?>"></div>

        <div class="code"><?= html_escape($code) ?></div>
        <p class="offer"><?= $currency . number_format($discount, 0) ?> off your first order over <?= $currency . number_format($min_cart, 0) ?></p>

        <p class="foot">Or enter the code above when you sign up at <?= html_escape(str_replace(['https://', 'http://'], '', rtrim(base_url(), '/'))) ?></p>
    </div>

    <!-- business card --------------------------------------------------- -->
    <div class="card-sm" id="card-sm" hidden>
        <div class="qr" id="qr-sm"
             data-referral-qr="<?= html_escape($qr_link) ?>"
             data-qr-size="380"
             data-qr-filename="cretzo-card-<?= html_escape($code) ?>"></div>
        <div>
            <p class="store"><?= html_escape($store !== '' ? $store : 'Shop handmade') ?></p>
            <p class="lede">Scan to shop handmade on <?= html_escape($app_name) ?>. <?= $currency . number_format($discount, 0) ?> off your first order over <?= $currency . number_format($min_cart, 0) ?>.</p>
            <p class="code"><?= html_escape($code) ?></p>
            <p class="foot"><?= html_escape(str_replace(['https://', 'http://'], '', rtrim(base_url(), '/'))) ?></p>
        </div>
    </div>
</div>

<p class="hint">
    Print at 100% scale &mdash; do not use &ldquo;fit to page&rdquo;, which shrinks the code and its
    quiet border. Anything smaller than about 2cm across stops scanning reliably.
</p>

<script src="<?= add_ver(base_url('assets/vendor/qrcode.min.js')) ?>"></script>
<script src="<?= add_ver(base_url('assets/referral-qr.js')) ?>"></script>
<script>
    (function () {
        var a5 = document.getElementById('card-a5');
        var sm = document.getElementById('card-sm');
        var btnA5 = document.getElementById('size-a5');
        var btnSm = document.getElementById('size-sm');

        function choose(isA5) {
            // .hidden, not a class: the print stylesheet must not be able to
            // resurrect the size that is not being printed.
            a5.hidden = !isA5;
            sm.hidden = isA5;
            btnA5.classList.toggle('is-active', isA5);
            btnSm.classList.toggle('is-active', !isA5);
        }

        btnA5.addEventListener('click', function () { choose(true); });
        btnSm.addEventListener('click', function () { choose(false); });
    })();
</script>

</body>
</html>
