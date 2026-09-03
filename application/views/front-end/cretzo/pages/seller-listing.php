<?php
/*
 * Seller directory (/sellers).
 *
 * Rebuilt onto its own `czsl` design system - assets/front_end/cretzo/css/cretzo/seller-listing.css
 * and js/cretzo/seller-listing.js, both picked up automatically by the $main_page
 * convention in include-css.php / include-script.php.
 *
 * Deliberately self-contained: the old markup reused `.product-listing`,
 * `#product_sort_by`, `#seller_search` and `#per_page_sellers`, all of which are also
 * bound by the product-listing handlers in custom.js and cretzo_fixes.js, so a single
 * change here fired several competing navigations. Every id and class below is unique
 * to this page.
 *
 * Controller contract (Sellers::index): $sellers, $total_sellers, $seller_search,
 * $sort_by, $per_page, $per_page_options, $view_type, $page_no, $links.
 */

$czsl_sellers   = (isset($sellers) && is_array($sellers)) ? $sellers : [];
$czsl_total     = isset($total_sellers) ? (int) $total_sellers : count($czsl_sellers);
$czsl_search    = isset($seller_search) ? (string) $seller_search : '';
$czsl_sort      = isset($sort_by) ? (string) $sort_by : '';
$czsl_per_page  = isset($per_page) ? (int) $per_page : 12;
$czsl_per_opts  = (isset($per_page_options) && is_array($per_page_options)) ? $per_page_options : [12, 16, 20, 24];
$czsl_view      = (isset($view_type) && $view_type === 'list') ? 'list' : 'grid';
$czsl_page_no   = isset($page_no) ? max(1, (int) $page_no) : 1;
$czsl_has_query = ($czsl_search !== '' || $czsl_sort !== '');

/* Range for the result line, so "Showing 13-24 of 37" stays honest on page 2+. */
$czsl_from = ($czsl_total > 0) ? (($czsl_page_no - 1) * $czsl_per_page) + 1 : 0;
$czsl_to   = ($czsl_total > 0) ? min($czsl_total, $czsl_from + count($czsl_sellers) - 1) : 0;

$czsl_placeholder = base_url('assets/front_end/cretzo/img/product-placeholder.jpg');

$czsl_lang = function ($key, $fallback) {
    $line = $this->lang->line($key);
    return !empty($line) ? $line : $fallback;
};

/*
 * seller_profile arrives from the model as base_url() . logo, so a seller who never
 * uploaded a logo yields the bare site root - which renders as a broken image. Detect
 * that and fall back to a monogram tile instead of shipping a broken <img>.
 */
$czsl_logo = function ($row) {
    $url = isset($row['seller_profile']) ? trim($row['seller_profile']) : '';
    $rel = trim(str_replace(rtrim(base_url(), '/'), '', $url), '/');
    return ($rel === '') ? '' : $url;
};

$czsl_initials = function ($name) {
    $name = trim(strip_tags((string) $name));
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name);
    $out = mb_substr($parts[0], 0, 1);
    if (count($parts) > 1) {
        $out .= mb_substr($parts[count($parts) - 1], 0, 1);
    }
    return mb_strtoupper($out);
};

/*
 * Five-star strip. Drawn here rather than with the Krajee plugin, which the old page
 * initialised through several inputs that all carried the same id="input".
 */
$czsl_stars = function ($rating) {
    $rating = (float) $rating;
    $out = '<span class="czsl-stars" aria-hidden="true">';
    for ($i = 1; $i <= 5; $i++) {
        $fill = max(0, min(1, $rating - ($i - 1))) * 100;
        $out .= '<span class="czsl-star"><i class="czsl-star__bg uil uil-star"></i>'
            . '<span class="czsl-star__fg" style="width:' . round($fill) . '%"><i class="uil uil-star"></i></span></span>';
    }
    return $out . '</span>';
};
?>

<section class="czsl czsl--<?= $czsl_view ?>" data-czsl-base="<?= base_url('sellers') ?>" data-czsl-view="<?= $czsl_view ?>">

    <nav class="czsl__crumbs" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>"><?= html_escape($czsl_lang('home', 'Home')) ?></a>
        <i class="uil uil-angle-right-b"></i>
        <span aria-current="page"><?= html_escape($czsl_lang('sellers', 'Sellers')) ?></span>
    </nav>

    <header class="czsl__hero">
        <div class="czsl__hero-text">
            <h1 class="czsl__title"><?= html_escape($czsl_lang('sellers', 'Sellers')) ?></h1>
            <p class="czsl__subtitle">
                <?= html_escape($czsl_lang('seller_listing_subtitle', 'Browse every verified store on Cretzo and shop straight from the people who stock the products.')) ?>
            </p>
        </div>
        <div class="czsl__hero-stat">
            <span class="czsl__hero-stat-num"><?= number_format($czsl_total) ?></span>
            <span class="czsl__hero-stat-label"><?= ($czsl_total == 1) ? 'store' : 'stores' ?></span>
        </div>
    </header>

    <?php /* A real GET form, so search works with the keyboard alone and with JS off;
             seller-listing.js only upgrades it (submit on change, clear button). */ ?>
    <form class="czsl__toolbar" method="get" action="<?= base_url('sellers') ?>" id="czsl-form">
        <input type="hidden" name="type" value="<?= $czsl_view ?>" id="czsl-type">

        <div class="czsl__search">
            <i class="uil uil-search czsl__search-icon" aria-hidden="true"></i>
            <label class="czsl__sr" for="czsl-search"><?= html_escape($czsl_lang('search_seller', 'Search seller')) ?></label>
            <input type="search" id="czsl-search" name="seller_search" value="<?= html_escape($czsl_search) ?>" placeholder="<?= html_escape($czsl_lang('search_seller', 'Search sellers or stores')) ?>" autocomplete="off">
            <?php if ($czsl_search !== '') { ?>
                <button type="button" class="czsl__search-clear" id="czsl-clear" aria-label="Clear search">
                    <i class="uil uil-times"></i>
                </button>
            <?php } ?>
        </div>

        <div class="czsl__controls">
            <div class="czsl__field">
                <label class="czsl__field-label" for="czsl-sort"><?= html_escape($czsl_lang('sort_by', 'Sort')) ?></label>
                <select id="czsl-sort" name="sort" class="czsl__select">
                    <option value=""><?= html_escape($czsl_lang('relevance', 'Relevance')) ?></option>
                    <option value="top-rated" <?= ($czsl_sort === 'top-rated') ? 'selected' : '' ?>><?= html_escape($czsl_lang('top_rated', 'Top Rated')) ?></option>
                    <option value="date-desc" <?= ($czsl_sort === 'date-desc') ? 'selected' : '' ?>><?= html_escape($czsl_lang('newest_first', 'Newest First')) ?></option>
                    <option value="date-asc" <?= ($czsl_sort === 'date-asc') ? 'selected' : '' ?>><?= html_escape($czsl_lang('oldest_first', 'Oldest First')) ?></option>
                </select>
            </div>

            <div class="czsl__field">
                <label class="czsl__field-label" for="czsl-per-page"><?= html_escape($czsl_lang('show', 'Show')) ?></label>
                <select id="czsl-per-page" name="per-page" class="czsl__select czsl__select--narrow">
                    <?php foreach ($czsl_per_opts as $opt) { ?>
                        <option value="<?= (int) $opt ?>" <?= ((int) $opt === $czsl_per_page) ? 'selected' : '' ?>><?= (int) $opt ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="czsl__viewtoggle" role="group" aria-label="View">
                <button type="button" class="czsl__viewbtn <?= ($czsl_view === 'grid') ? 'is-active' : '' ?>" data-czsl-set-view="grid" aria-pressed="<?= ($czsl_view === 'grid') ? 'true' : 'false' ?>" title="Grid view">
                    <i class="uil uil-apps"></i>
                </button>
                <button type="button" class="czsl__viewbtn <?= ($czsl_view === 'list') ? 'is-active' : '' ?>" data-czsl-set-view="list" aria-pressed="<?= ($czsl_view === 'list') ? 'true' : 'false' ?>" title="List view">
                    <i class="uil uil-list-ul"></i>
                </button>
            </div>

            <noscript><button type="submit" class="czsl__apply">Apply</button></noscript>
        </div>
    </form>

    <?php if (!empty($czsl_sellers)) { ?>
        <p class="czsl__resultline">
            <?php if ($czsl_search !== '') { ?>
                Showing <strong><?= $czsl_from ?>&ndash;<?= $czsl_to ?></strong> of <strong><?= number_format($czsl_total) ?></strong> for &ldquo;<?= html_escape($czsl_search) ?>&rdquo;
            <?php } else { ?>
                Showing <strong><?= $czsl_from ?>&ndash;<?= $czsl_to ?></strong> of <strong><?= number_format($czsl_total) ?></strong> sellers
            <?php } ?>
        </p>

        <div class="czsl__grid">
            <?php foreach ($czsl_sellers as $row) {
                $czsl_name      = isset($row['seller_name']) ? $row['seller_name'] : '';
                $czsl_store     = isset($row['store_name']) ? $row['store_name'] : '';
                $czsl_desc      = isset($row['store_description']) ? trim(strip_tags($row['store_description'])) : '';
                $czsl_rating    = isset($row['seller_rating']) ? (float) $row['seller_rating'] : 0;
                $czsl_nratings  = isset($row['no_of_ratings']) ? (int) $row['no_of_ratings'] : 0;
                $czsl_products  = isset($row['total_products']) ? (int) $row['total_products'] : 0;
                $czsl_store_url = seller_profile_url($row['slug'] ?? '', $row['seller_id'] ?? '');
                $czsl_img       = $czsl_logo($row);
                $czsl_heading   = ($czsl_store !== '') ? $czsl_store : $czsl_name;
            ?>
                <article class="czsl-card">
                    <a class="czsl-card__media" href="<?= $czsl_store_url ?>" tabindex="-1" aria-hidden="true">
                        <?php if ($czsl_img !== '') { ?>
                            <img src="<?= $czsl_img ?>" alt="" loading="lazy" onerror="this.onerror=null;this.src='<?= $czsl_placeholder ?>';">
                        <?php } else { ?>
                            <span class="czsl-card__monogram"><?= html_escape($czsl_initials($czsl_heading)) ?></span>
                        <?php } ?>
                    </a>

                    <div class="czsl-card__body">
                        <h2 class="czsl-card__name">
                            <a href="<?= $czsl_store_url ?>" title="<?= html_escape($czsl_heading) ?>"><?= html_escape($czsl_heading) ?></a>
                        </h2>

                        <?php if ($czsl_store !== '' && $czsl_name !== '' && $czsl_store !== $czsl_name) { ?>
                            <p class="czsl-card__seller"><i class="uil uil-user"></i> <?= html_escape($czsl_name) ?></p>
                        <?php } ?>

                        <div class="czsl-card__meta">
                            <span class="czsl-card__rating <?= ($czsl_nratings > 0) ? '' : 'is-empty' ?>">
                                <?= $czsl_stars($czsl_rating) ?>
                                <?php if ($czsl_nratings > 0) { ?>
                                    <span class="czsl-card__rating-num"><?= number_format($czsl_rating, 1) ?></span>
                                    <span class="czsl-card__rating-count">(<?= number_format($czsl_nratings) ?>)</span>
                                <?php } else { ?>
                                    <span class="czsl-card__rating-count">No ratings yet</span>
                                <?php } ?>
                            </span>
                            <span class="czsl-card__chip"><i class="uil uil-box"></i> <?= number_format($czsl_products) ?> <?= ($czsl_products == 1) ? 'product' : 'products' ?></span>
                        </div>

                        <?php if ($czsl_desc !== '') { ?>
                            <p class="czsl-card__desc"><?= html_escape($czsl_desc) ?></p>
                        <?php } ?>

                        <div class="czsl-card__actions">
                            <a class="czsl-btn czsl-btn--primary" href="<?= base_url('products?seller=' . urlencode($row['slug'] ?? '')) ?>">
                                <?= html_escape($czsl_lang('view_products', 'View Products')) ?>
                            </a>
                            <a class="czsl-btn czsl-btn--ghost" href="<?= $czsl_store_url ?>">
                                <?= html_escape($czsl_lang('visit_store', 'Visit Store')) ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php } ?>
        </div>

        <?php if (!empty($links)) { ?>
            <nav class="cz-pager-nav czsl__pager" aria-label="<?= storefront_pagination_label('sellers') ?>"><?= $links ?></nav>
        <?php } ?>

    <?php } else { ?>
        <div class="czsl__empty">
            <i class="uil uil-store-slash czsl__empty-icon" aria-hidden="true"></i>
            <h2 class="czsl__empty-title">
                <?php if ($czsl_has_query) { ?>
                    No sellers match that search
                <?php } else { ?>
                    <?= html_escape($czsl_lang('no_sellers_found', 'No Sellers Found.')) ?>
                <?php } ?>
            </h2>
            <p class="czsl__empty-text">
                <?php if ($czsl_has_query) { ?>
                    Try a shorter store name, or clear the filters to see every store.
                <?php } else { ?>
                    Stores appear here once they are approved and have products on sale.
                <?php } ?>
            </p>
            <div class="czsl__empty-actions">
                <?php if ($czsl_has_query) { ?>
                    <a class="czsl-btn czsl-btn--primary" href="<?= base_url('sellers') ?>">Clear filters</a>
                <?php } ?>
                <a class="czsl-btn czsl-btn--ghost" href="<?= base_url('products') ?>"><?= html_escape($czsl_lang('go_to_shop', 'Go to Shop')) ?></a>
            </div>
        </div>
    <?php } ?>
</section>
