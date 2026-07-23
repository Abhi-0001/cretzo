<!DOCTYPE html>
<html lang="en">

<?php

$settings = get_settings('web_settings', true);
$primary_colour = (isset($settings['primary_color']) && !empty($settings['primary_color'])) ?  $settings['primary_color'] : '#f78b77';
$secondary_colour = (isset($settings['secondary_color']) && !empty($settings['secondary_color'])) ?  $settings['secondary_color'] : '#f78b77';
$font_color = (isset($settings['font_color']) && !empty($settings['font_color'])) ?  $settings['font_color'] : '#FFF';

?>



<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <meta name="keywords" content='<?= $keywords ?>'>
    <meta name="description" content='<?= $description ?>'>

    <!-- for image in link -->
    <meta name="product_image" property="og:image" content='<?= isset($product_image) ? $product_image : '' ?>'>
    <meta property="og:image:type" content="image/jpg,png,jpeg,gif,bmp,eps">
    <meta property="og:image:width" content="1024">
    <meta property="og:image:height" content="1024">


    <?php $cookie_lang = $this->input->cookie('language', TRUE);
    $path = $is_rtl = "";
    if (!empty($cookie_lang)) {
        $language = get_languages(0, $cookie_lang, 0, 1);
        if (!empty($language)) {
            $path = ($language[0]['is_rtl'] == 1) ? 'rtl/' : "";
            $is_rtl =  ($language[0]['is_rtl'] == 1) ? true : false;
        }
    } else {
        /* read the default language */
        $lang = $this->config->item('language');
        $language = get_languages(0, $lang, 0, 1);
        if (!empty($language)) {
            $path = ($language[0]['is_rtl'] == 1) ? 'rtl/' : "";
            $is_rtl =  ($language[0]['is_rtl'] == 1) ? true : false;
        }
    }
    $data['is_rtl'] = $is_rtl;
    ?>
    <?php $this->load->view('front-end/' . THEME . '/include-css', $data); ?>
    <style>
        * {
            --primary-color: <?= $primary_colour ?>;
            --secondary-color: <?= $secondary_colour ?>;
            --font-color: <?= $font_color ?>;
        }

        /* ===== Global page loader ===== */
        #global-page-loader {
            position: fixed;
            inset: 0;
            z-index: 2147483647;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.55);
            -webkit-backdrop-filter: blur(1px);
            backdrop-filter: blur(1px);
        }

        #global-page-loader.active {
            display: flex;
        }

        #global-page-loader .loader {
            width: 50px;
            aspect-ratio: 1;
            border-radius: 50%;
            background:
                radial-gradient(farthest-side, #ffa516 94%, #0000) top/8px 8px no-repeat,
                conic-gradient(#0000 30%, #ffa516);
            -webkit-mask: radial-gradient(farthest-side, #0000 calc(100% - 8px), #000 0);
            mask: radial-gradient(farthest-side, #0000 calc(100% - 8px), #000 0);
            animation: l13 1s infinite linear;
        }

        @keyframes l13 {
            100% {
                transform: rotate(1turn);
            }
        }
    </style>
</head>

<body id="body" data-is-rtl='<?= $is_rtl ?>'>

    <!-- Global page loader (shows on navigation / button clicks until the next page loads) -->
    <div id="global-page-loader" aria-hidden="true" role="status">
        <div class="loader"></div>
    </div>

    <?php
        if (!empty($hide_header_footer) && $hide_header_footer) {
            $this->load->view('front-end/' . THEME . '/imp-inputs');
            $this->load->view('front-end/' . THEME . '/pages/' . $main_page);
            $this->load->view('front-end/' . THEME . '/include-script');

            if($main_page === 'cart' || $main_page === 'checkout'){
                $this->load->view('front-end/' . THEME . '/payment_footer');
            }
        }
        else {
            $this->load->view('front-end/' . THEME . '/imp-inputs');
            $this->load->view('front-end/' . THEME . '/header');
            $this->load->view('front-end/' . THEME . '/pages/' . $main_page);
            $this->load->view('front-end/' . THEME . '/footer');
            $this->load->view('front-end/' . THEME . '/include-script');
        }
    ?>
    
    <?php 
        /* $this->load->view('front-end/' . THEME . '/header');
        $this->load->view('front-end/' . THEME . '/pages/' . $main_page);
        $this->load->view('front-end/' . THEME . '/footer');
        $this->load->view('front-end/' . THEME . '/include-script'); */
    ?>

    <!-- Global page loader controller -->
    <script>
        (function () {
            var overlay = document.getElementById('global-page-loader');
            if (!overlay) return;

            var safetyTimer = null;
            var ajaxShowTimer = null;
            var ajaxPending = 0;
            var navPending = false; // a real navigation (link/form/reload) is in progress

            function show() {
                overlay.classList.add('active');
                // Never let the loader get stuck (e.g. cancelled navigation / AJAX buttons)
                clearTimeout(safetyTimer);
                safetyTimer = setTimeout(hide, 15000);
            }

            // Loader shown for a page navigation. Stays up until the page
            // actually unloads / the next page is ready, so a background AJAX
            // finishing mid-navigation can't tear it down early.
            function showForNav() {
                navPending = true;
                show();
            }

            function hide() {
                clearTimeout(ajaxShowTimer);
                ajaxShowTimer = null;
                navPending = false;
                overlay.classList.remove('active');
                clearTimeout(safetyTimer);
            }

            // Hide as soon as the (next) page is ready or restored from bfcache
            window.addEventListener('load', hide);
            window.addEventListener('pageshow', hide);

            // Fallback: fires for every real navigation away from the page
            window.addEventListener('beforeunload', showForNav);

            // Immediate feedback on link clicks
            document.addEventListener('click', function (e) {
                var a = e.target.closest && e.target.closest('a');
                if (!a) return;

                var href = a.getAttribute('href');
                if (!href) return;

                // Skip in-page anchors, JS handlers, new tabs, downloads and opt-outs
                if (a.hasAttribute('data-no-loader')) return;
                if (a.target && a.target !== '_self') return;
                if (a.hasAttribute('download')) return;
                if (href.charAt(0) === '#') return;
                if (/^(javascript:|mailto:|tel:|whatsapp:)/i.test(href)) return;
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
                setTimeout(function () {
                        if (e.defaultPrevented) return;
                        showForNav();
                    }, 0);
                }, true);
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (form && form.hasAttribute('data-no-loader')) return;
                setTimeout(function () {
                    if (e.defaultPrevented) return;
                    showForNav();
                }, 0);
            }, true);

            // AJAX loader is OPT-IN, not automatic.
            //
            // Previously the blocking full-screen loader was shown for EVERY
            // jQuery AJAX request (ajaxStart) and only hidden when ALL of them
            // finished (ajaxStop). Every page fires background requests on load
            // (cart sync, typeahead search, ratings, etc.), so the loader kept
            // appearing AFTER the page was already visible and lingered until
            // the slowest background call returned - the "page loaded fast but
            // the loader keeps spinning" bug.
            //
            // Interactive AJAX actions already give their own feedback (buttons
            // switch to "Please Wait", the promo modal shows its own toast, and
            // cart quantity changes reload the page, which is covered by the
            // navigation loader below). So AJAX no longer triggers the overlay
            // by default.
            //
            // If a specific slow request genuinely needs the blocking loader,
            // opt in per-call:  $.ajax({ url: ..., globalLoader: true, ... })
            if (window.jQuery) {
                var AJAX_SHOW_DELAY = 250; // ms an opted-in request must run before we bother showing the loader

                jQuery(document)
                    .ajaxSend(function (event, jqXHR, settings) {
                        if (!settings || settings.globalLoader !== true) return;
                        ajaxPending++;
                        if (!ajaxShowTimer && !overlay.classList.contains('active')) {
                            ajaxShowTimer = setTimeout(function () {
                                ajaxShowTimer = null;
                                if (ajaxPending > 0) show();
                            }, AJAX_SHOW_DELAY);
                        }
                    })
                    .ajaxComplete(function (event, jqXHR, settings) {
                        if (!settings || settings.globalLoader !== true) return;
                        ajaxPending = Math.max(0, ajaxPending - 1);
                        // Don't tear down a navigation loader; that one waits
                        // for the page to actually change.
                        if (ajaxPending === 0 && !navPending) hide();
                    });
            }
        })();
    </script>

</body>


</html>