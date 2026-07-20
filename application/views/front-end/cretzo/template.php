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

            function show() {
                overlay.classList.add('active');
                // Never let the loader get stuck (e.g. cancelled navigation / AJAX buttons)
                clearTimeout(safetyTimer);
                safetyTimer = setTimeout(hide, 20000);
            }

            function hide() {
                overlay.classList.remove('active');
                clearTimeout(safetyTimer);
            }

            // Hide as soon as the (next) page is ready or restored from bfcache
            window.addEventListener('load', hide);
            window.addEventListener('pageshow', hide);

            // Fallback: fires for every real navigation away from the page
            window.addEventListener('beforeunload', show);

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
                if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;

                show();
            }, true);

            // Immediate feedback on form submissions
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (form && form.hasAttribute('data-no-loader')) return;
                show();
            }, true);

            // Show the same loader for AJAX / API calls (popups, filters, etc.).
            // Fires when the first request starts and hides when all finish.
            // To skip the loader for a specific request, pass { global: false }
            // in its jQuery $.ajax options.
            if (window.jQuery) {
                jQuery(document)
                    .ajaxStart(function () { show(); })
                    .ajaxStop(function () { hide(); });
            }
        })();
    </script>

</body>


</html>