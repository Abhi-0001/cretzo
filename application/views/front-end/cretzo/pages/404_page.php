<?php $logo = get_settings('web_logo'); ?>
<div class="page_404_topbar">
    <a href="<?= base_url() ?>" class="page_404_logo" aria-label="Go to homepage">
        <img src="<?= base_url($logo) ?>" alt="site-logo image">
    </a>
</div>
<section class="page_404">
    <div class="page_404_inner">
        <h1 class="text-center">404</h1>

        <div class="contant_box_404">
            <h3 class="h2">
                Oops! Page not found
            </h3>

            <p>The page you're looking for doesn't exist or isn't available right now.</p>

            <a href="<?= base_url() ?>" class="link_404">Go to Homepage</a>
        </div>
    </div>
</section>

<style type="text/css">
    .page_404_topbar {
        padding: 20px 32px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .page_404_logo {
        display: inline-block;
    }

    .page_404_logo img {
        max-height: 40px;
        width: auto;
    }

    .page_404 {
        min-height: calc(100vh - 82px);
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        font-family: 'Arvo', serif;
        padding: 40px 20px;
    }

    .page_404_inner {
        text-align: center;
        max-width: 480px;
    }

    .page_404 h1 {
        font-size: 80px;
        margin: 0;
        color: #222;
    }

    .contant_box_404 {
        margin-top: 16px;
    }

    .contant_box_404 h3 {
        font-size: 28px;
        margin-bottom: 8px;
    }

    .contant_box_404 p {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #6b7280;
        font-size: 16px;
    }

    .link_404 {
        color: #fff !important;
        padding: 10px 24px;
        background: var(--color-main-theme, #F2822E);
        margin-top: 20px;
        display: inline-block;
        border-radius: 4px;
        text-decoration: none;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 16px;
        transition: background 0.2s ease;
    }

    .link_404:hover {
        background: var(--color-orange-dark, #db7323);
        color: #fff !important;
    }
</style>
