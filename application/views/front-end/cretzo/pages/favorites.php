<?php
/**
 * My Account > Wishlist.
 *
 * This page used to sit OUTSIDE the account layout entirely - a bare centred
 * "MY WISHLIST" heading with no sidebar, so from a customer's point of view
 * clicking Wishlist in My Account threw them out of My Account. It is inside
 * the shared shell now like every other account page.
 *
 * The product cards keep the theme's `.cretzo-card` markup and the
 * `.remove-from-wishlist-btn` / `.add_to_cart` hooks, so favorites.js and the
 * shared add-to-cart handler in custom.js are untouched.
 */

$products = (isset($products) && is_array($products)) ? $products : [];
$total_rows = isset($total_rows) ? (int) $total_rows : count($products);
$currency = isset($settings['currency']) ? $settings['currency'] : '₹';

/**
 * Price line for a wishlist card.
 *
 * Rewritten from the copy that used to live at the bottom of this file, which
 * had three problems: it hardcoded the rupee sign instead of using the store
 * currency, it divided by $price without checking for zero (a free or
 * misconfigured product raised a DivisionByZeroError in PHP 8), and its
 * no-discount branch emitted `class="ta-c no-wraptext-es"` - two class names
 * run together, so the font size never applied.
 */
function czap_price_element($product_row, $currency, $text_style = 'text-es')
{
    $variant = isset($product_row['variants'][0]) ? $product_row['variants'][0] : [];
    $price = isset($variant['price']) ? (float) $variant['price'] : 0;
    $special = isset($variant['special_price']) ? (float) $variant['special_price'] : 0;

    $has_discount = ($special > 0 && $special < $price && $price > 0);

    if (!$has_discount) {
        return '<p class="price-container ta-c no-wrap ' . $text_style . '">'
            . '<span class="discounted-price no-wrap">' . $currency . number_format($price, 0) . '</span>'
            . '</p>';
    }

    $percent = (int) round((($price - $special) / $price) * 100);

    return '<p class="price-container ta-c no-wrap ' . $text_style . '">'
        . '<span class="discounted-price no-wrap">' . $currency . number_format($special, 0) . '</span>'
        . '<span class="original-price op-6 no-wrap">' . $currency . number_format($price, 0) . '</span>'
        . '<span class="off-percent fw-b no-wrap">' . $percent . '% OFF</span>'
        . '</p>';
}

/* --------------------------------------------------------------- actions -- */
ob_start(); ?>
<a class="czap-btn czap-btn--ghost" href="<?= base_url('products') ?>">
    <i class="uil uil-shopping-bag"></i> Continue shopping
</a>
<?php $page_actions = ob_get_clean();

/* --------------------------------------------------------------- content -- */
ob_start(); ?>

<?php if (empty($products)) { ?>
    <div class="czap-empty">
        <div class="czap-empty__icon"><i class="uil uil-heart"></i></div>
        <h3 class="czap-empty__title">Your wishlist is empty</h3>
        <p class="czap-empty__text">
            Tap the heart on any product to save it here. Wishlisted items stay on your account,
            so you can come back to them from any device.
        </p>
        <a class="czap-btn czap-btn--primary" href="<?= base_url('products') ?>">
            <i class="uil uil-shopping-cart"></i>
            <?= !empty($this->lang->line('go_to_shop')) ? $this->lang->line('go_to_shop') : 'Go to shop' ?>
        </a>
    </div>
<?php } else { ?>

    <p class="czap-help" style="margin:0 0 18px">
        <i class="uil uil-heart"></i>
        <strong class="czap-wishlist-count"><?= $total_rows ?></strong>
        <?= $total_rows === 1 ? 'item saved' : 'items saved' ?>
    </p>

    <div class="wishlist">
        <div class="wishlist-card-container czap-wishlist-grid">
            <?php foreach ($products as $product_row) {

                /* Single-variant products go straight into the cart; multi-variant
                   ones open the theme's quick-view modal so a variant can be picked
                   (izimodal, via data-izimodal-open). */
                $variants = isset($product_row['variants']) ? $product_row['variants'] : [];
                $single_variant = (count($variants) <= 1);
                $variant_id = ($single_variant && isset($variants[0]['id'])) ? $variants[0]['id'] : '';
                $modal = $single_variant ? '' : '#quick-view';

                $variant_price = (isset($variants[0]['special_price']) && $variants[0]['special_price'] > 0)
                    ? $variants[0]['special_price']
                    : (isset($variants[0]['price']) ? $variants[0]['price'] : 0);

                $data_min = !empty($product_row['minimum_order_quantity']) ? $product_row['minimum_order_quantity'] : 1;
                /* The old line tested minimum_order_quantity but then used
                   quantity_step_size, so a product with a step but no minimum fell
                   back to 1 and the stepper moved one at a time regardless. */
                $data_step = !empty($product_row['quantity_step_size']) ? $product_row['quantity_step_size'] : 1;
                ?>
                <div class="cretzo-card card-type-one wishlist-card">

                    <a class="text-decoration-none" href="<?= base_url('products/details/' . $product_row['slug']) ?>">
                        <div class="card-img">
                            <img class="card-img-img lazy"
                                 src="<?= base_url('assets/front_end/cretzo/img/product-placeholder.jpg') ?>"
                                 data-src="<?= $product_row['image_sm'] ?>"
                                 alt="<?= html_escape($product_row['name']) ?>">
                        </div>
                        <div class="card-des">
                            <h1 class="text-b ta-c no-wrap"><?= html_escape($product_row['name']) ?></h1>
                            <?= czap_price_element($product_row, $currency) ?>
                        </div>
                    </a>

                    <?php /* Outside the product link: nested <a> elements are invalid and
                             the browser closes the outer one early, which is why the old
                             remove button sometimes navigated to the product instead of
                             removing the item. */ ?>
                    <a href="#" class="czap-wishlist-remove remove-from-wishlist-btn"
                       data-product-id="<?= (int) $product_row['id'] ?>"
                       title="Remove from wishlist" aria-label="Remove from wishlist">
                        <i class="uil uil-times"></i>
                    </a>

                    <button class="text-b bag-btn">
                        <a href="#" class="add_to_cart text-decoration-none"
                           data-product-id="<?= (int) $product_row['id'] ?>"
                           data-product-variant-id="<?= $variant_id ?>"
                           data-product-slug="<?= $product_row['slug'] ?>"
                           data-product-title="<?= html_escape($product_row['name']) ?>"
                           data-product-image="<?= $product_row['image'] ?>"
                           data-product-price="<?= $variant_price ?>"
                           data-min="<?= $data_min ?>"
                           data-step="<?= $data_step ?>"
                           data-product-description="<?= short_description_word_limit(output_escaping(str_replace('\r\n', '&#13;&#10;', strip_tags($product_row['short_description'])))) ?>"
                           data-izimodal-open="<?= $modal ?>">
                            <i class="uil uil-shopping-bag"></i>&nbsp;Move to bag
                        </a>
                    </button>
                </div>
            <?php } ?>
        </div>

        <?php if (!empty($links)) { ?>
            <nav id="products-pagination-nav" class="cz-pager-nav"
                 aria-label="<?= storefront_pagination_label('your wishlist') ?>"><?= $links ?></nav>
        <?php } ?>
    </div>

<?php }

$page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Wishlist',
    'page_sub'     => 'Products you saved for later',
    'page_icon'    => 'uil-heart',
    'page_actions' => $page_actions,
    'page_content' => $page_content,
]);
?>

<style>
    /* The wishlist grid inside the account card. favorites.css sizes these cards
       for the old full-width page, which had no sidebar next to it - so the
       columns are re-declared here for the narrower content column. */
    .czap-wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 18px;
        margin: 0;
        padding: 0;
    }

    .czap .wishlist-card {
        position: relative;
        margin: 0;
        width: auto;
        height: auto;
        border: 1px solid var(--czap-line);
        border-radius: var(--czap-r);
        overflow: hidden;
        background: #fff;
        display: flex;
        flex-direction: column;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .czap .wishlist-card:hover {
        border-color: var(--czap-orange-line);
        box-shadow: var(--czap-shadow);
    }

    .czap .wishlist-card .card-img {
        position: relative;
        aspect-ratio: 1 / 1;
        background: var(--czap-line-2);
    }

    .czap .wishlist-card .card-img-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .czap .wishlist-card .card-des {
        padding: 12px 12px 6px;
    }

    .czap .wishlist-card .card-des h1 {
        font-size: 14.5px;
        font-weight: 600;
        margin: 0 0 6px;
        color: var(--czap-ink);
        /* no-wrap + ellipsis: a long product name must not widen the grid cell. */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .czap .wishlist-card .price-container {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: center;
        gap: 6px;
        margin: 0;
        font-size: 13px;
    }

    .czap .wishlist-card .discounted-price {
        font-weight: 700;
        color: var(--czap-ink);
    }

    .czap .wishlist-card .original-price {
        text-decoration: line-through;
        color: var(--czap-ink-3);
        font-size: 12px;
    }

    .czap .wishlist-card .off-percent {
        color: var(--czap-ok);
        font-weight: 700;
        font-size: 12px;
    }

    /* Remove control: a circular button over the image, not the old bare "╳"
       glyph in a nested link. */
    .czap-wishlist-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 2;
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, .94);
        color: var(--czap-ink-2);
        font-size: 17px;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .16);
        transition: background-color .15s ease, color .15s ease;
    }

    .czap-wishlist-remove:hover {
        background: var(--czap-bad);
        color: #fff;
    }

    .czap .wishlist-card .bag-btn {
        /* favorites.css sets .bag-btn { width: 100% }, which with the side
           margins below made the button overflow the card. Size it from the
           card box instead. */
        display: block;
        width: auto;
        box-sizing: border-box;
        margin: auto 10px 10px;
        padding: 0;
        border: 1px solid var(--czap-orange);
        border-radius: var(--czap-r-pill);
        background: var(--czap-orange-soft);
        overflow: hidden;
        transition: background-color .15s ease;
    }

    .czap .wishlist-card .bag-btn:hover {
        background: var(--czap-orange);
    }

    .czap .wishlist-card .bag-btn a {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 9px 12px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--czap-orange-dark);
    }

    .czap .wishlist-card .bag-btn:hover a {
        color: #fff;
    }

    @media (max-width: 480px) {
        .czap-wishlist-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
    }
</style>

<script>
    /* favorites.js removes the card and then updates
       `.wishlist .no-of-item-text > span` - a selector this page has never
       rendered, so the count never moved. Keep it in step here instead. */
    $(document).on('click', '.remove-from-wishlist-btn', function () {
        setTimeout(function () {
            var left = $('.czap-wishlist-grid > .wishlist-card').length;
            $('.czap-wishlist-count').text(left);
            if (left === 0) {
                /* The empty state is server-rendered, so a reload is the honest way
                   to reach it rather than building a second copy of it in JS. */
                window.location.reload();
            }
        }, 400);
    });
</script>
