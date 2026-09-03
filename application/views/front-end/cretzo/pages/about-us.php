<?php
/**
 * About Us.
 *
 * The previous version printed the stored blob - 50 flat <p> tags with no
 * headings - under a centred `display-2` title, full width. An unscannable wall
 * of text with nothing to look at on a page whose entire job is to make someone
 * believe in a handmade marketplace.
 *
 * This page keeps that copy (it is the owner's words and stays editable in the
 * admin panel) but sets it properly and surrounds it with things worth looking
 * at. Every image is REAL - actual listed products and actual category imagery
 * out of the catalogue - so nothing here is stock photography, and it stays
 * current on its own as the store grows.
 *
 * about_page_prepare() promotes the copy's bolded section titles to real <h2>s
 * (see the helper for why only some of them qualify).
 */

$prepared  = about_page_prepare(isset($about_us) ? $about_us : '');
$stats     = isset($about_stats) ? $about_stats : ['products' => 0, 'categories' => 0];
$cats      = isset($about_categories) ? $about_categories : [];
$products  = isset($about_products) ? $about_products : [];

$web    = get_settings('web_settings', true);
$system = get_settings('system_settings', true);
$store  = !empty($system['app_name']) ? $system['app_name'] : 'Cretzo';

$whatsapp = whatsapp_support_link();

/* Rounded down to a "+" figure. 290 products reads as a precise inventory
 * count; "290+" reads as a catalogue, and it stays true as items are added. */
function czabout_round($n)
{
    $n = (int) $n;
    if ($n < 10) {
        return (string) $n;
    }
    $step = ($n < 100) ? 10 : (($n < 1000) ? 50 : 500);
    return number_format((int) floor($n / $step) * $step) . '+';
}

/* What the store stands for. Owner-authored copy lives in the prose below; these
 * are the four promises the platform actually implements, each of which a
 * customer can verify elsewhere on the site. */
$values = [
    [
        'icon'  => 'uil-award',
        'title' => 'Genuinely handmade',
        'text'  => 'Every listing comes from a maker, not a warehouse. Sellers are verified before a single product goes live.',
    ],
    [
        'icon'  => 'uil-users-alt',
        'title' => 'Makers keep the credit',
        'text'  => 'Creators retain ownership of their original designs, and every product page names the artisan behind it.',
    ],
    [
        'icon'  => 'uil-shield-check',
        'title' => 'Protected checkout',
        'text'  => 'Secure payments, a clear return window, and refunds credited straight back to your Cretzo wallet.',
    ],
    [
        'icon'  => 'uil-truck',
        'title' => 'Tracked across India',
        'text'  => 'Orders ship through our courier network with live tracking from dispatch to your door.',
    ],
];

/* Who the platform is for - drawn from the "Creative Ecosystem for Everyone"
 * section of the copy below, so the page states it once visually and once in
 * the owner's own words. */
$audiences = [
    ['icon' => 'uil-palette', 'title' => 'Artisans & makers', 'text' => 'Open a storefront, price your own work, and reach buyers who came looking for handmade.'],
    ['icon' => 'uil-shopping-bag', 'title' => 'Conscious buyers', 'text' => 'Find pieces with a story behind them instead of another mass-produced shelf filler.'],
    ['icon' => 'uil-box', 'title' => 'Suppliers', 'text' => 'Sell raw materials and craft supplies to a marketplace of people who use them daily.'],
    ['icon' => 'uil-calendar-alt', 'title' => 'Workshop & event partners', 'text' => 'Reach a community that already turns up for craft, learning and making things by hand.'],
];
?>

<div class="czabout">

    <nav class="czabout__crumbs" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a>
        <span aria-hidden="true">/</span>
        <span class="czabout__crumbs-now"><?= !empty($this->lang->line('about_us')) ? $this->lang->line('about_us') : 'About Us' ?></span>
    </nav>

    <!-- ============================== hero ============================== -->
    <header class="czabout__hero">
        <div class="czabout__hero-copy">
            <span class="czabout__eyebrow"><i class="uil uil-palette"></i> Handmade marketplace</span>
            <h1 class="czabout__h1">
                Where creativity<br><span class="czabout__h1-accent">comes alive</span>
            </h1>
            <p class="czabout__lede">
                <?= html_escape($store) ?> is a creative ecosystem built to celebrate artisans, empower
                creators, and connect everyone who believes in the value of handmade craftsmanship.
            </p>
            <div class="czabout__hero-cta">
                <a class="czabout__btn czabout__btn--primary" href="<?= base_url('products') ?>">
                    <i class="uil uil-shopping-bag"></i> Explore handmade
                </a>
                <?php /* seller/auth/sign_up is the real signup form. NOT
                         seller/auth/register - that route does not exist, and because
                         this app sets $route['404_override'] a missing route answers
                         HTTP 200 with the themed "Page Not Found" page, so a dead CTA
                         looks perfectly healthy to anything that only checks status
                         codes. The footer's "Sell with Cretzo" points at base_url('seller'),
                         which redirects to the seller LOGIN - an About page should open
                         the signup instead, since the reader is not a seller yet. */ ?>
                <a class="czabout__btn czabout__btn--ghost" href="<?= base_url('seller/auth/sign_up') ?>">
                    <i class="uil uil-store"></i> Sell with <?= html_escape($store) ?>
                </a>
            </div>
        </div>

        <?php if (!empty($products)) { ?>
            <?php /* A collage of real listings. aria-hidden because it is decorative
                     here - the products are all reachable from the shop, and reading
                     out five product names adds nothing for a screen reader. */ ?>
            <div class="czabout__collage" aria-hidden="true">
                <?php foreach (array_slice($products, 0, 5) as $i => $product) { ?>
                    <figure class="czabout__collage-item czabout__collage-item--<?= $i + 1 ?>">
                        <img src="<?= get_image_url($product['image'], 'thumb', 'md') ?>"
                             alt="" loading="lazy" decoding="async">
                    </figure>
                <?php } ?>
                <span class="czabout__collage-badge">
                    <i class="uil uil-heart"></i> Made by hand
                </span>
            </div>
        <?php } ?>
    </header>

    <!-- ============================== stats ============================== -->
    <section class="czabout__stats" aria-label="<?= html_escape($store) ?> at a glance">
        <div class="czabout__stat">
            <span class="czabout__stat-num"><?= czabout_round($stats['products']) ?></span>
            <span class="czabout__stat-label">Handmade products listed</span>
        </div>
        <div class="czabout__stat">
            <span class="czabout__stat-num"><?= (int) $stats['categories'] ?></span>
            <span class="czabout__stat-label">Craft categories</span>
        </div>
        <div class="czabout__stat">
            <span class="czabout__stat-num">100%</span>
            <span class="czabout__stat-label">Verified sellers</span>
        </div>
        <div class="czabout__stat">
            <span class="czabout__stat-num">Pan-India</span>
            <span class="czabout__stat-label">Tracked delivery</span>
        </div>
    </section>

    <!-- ============================== values ============================== -->
    <section class="czabout__section">
        <div class="czabout__section-head">
            <h2 class="czabout__h2">What we stand for</h2>
            <p class="czabout__section-sub">Four promises the platform is actually built around.</p>
        </div>
        <div class="czabout__cards">
            <?php foreach ($values as $value) { ?>
                <article class="czabout__card">
                    <span class="czabout__card-icon"><i class="uil <?= $value['icon'] ?>"></i></span>
                    <h3 class="czabout__card-title"><?= $value['title'] ?></h3>
                    <p class="czabout__card-text"><?= $value['text'] ?></p>
                </article>
            <?php } ?>
        </div>
    </section>

    <!-- ========================= what makers create ========================= -->
    <?php if (!empty($cats)) { ?>
        <section class="czabout__section">
            <div class="czabout__section-head">
                <h2 class="czabout__h2">What our makers create</h2>
                <p class="czabout__section-sub">Real categories from the shop - tap one to browse it.</p>
            </div>
            <div class="czabout__cats">
                <?php foreach ($cats as $cat) { ?>
                    <?php /* products/category/<slug> - the same route the header nav and the
                             homepage category strip use. */ ?>
                    <a class="czabout__cat" href="<?= base_url('products/category/' . html_escape($cat['slug'])) ?>">
                        <img class="czabout__cat-img"
                             src="<?= get_image_url($cat['image'], 'thumb', 'md') ?>"
                             alt="<?= html_escape($cat['name']) ?>" loading="lazy" decoding="async">
                        <span class="czabout__cat-name"><?= html_escape($cat['name']) ?></span>
                    </a>
                <?php } ?>
            </div>
        </section>
    <?php } ?>

    <!-- ============================ the story ============================ -->
    <?php if (trim($prepared['html']) !== '') { ?>
        <section class="czabout__section czabout__story-wrap">
            <div class="czabout__section-head">
                <h2 class="czabout__h2">Our story</h2>
                <p class="czabout__section-sub">In our own words.</p>
            </div>

            <?php if (!empty($prepared['sections'])) { ?>
                <?php /* Jump chips. The copy is long; these make it skimmable without
                         imposing a sidebar on what is really a narrative page. */ ?>
                <nav class="czabout__jump" aria-label="Jump to a section">
                    <?php foreach ($prepared['sections'] as $section) { ?>
                        <a href="#<?= html_escape($section['id']) ?>"><?= html_escape($section['text']) ?></a>
                    <?php } ?>
                </nav>
            <?php } ?>

            <?php /* Owner-authored HTML from the admin panel's editor, printed as
                     HTML on purpose. It is not user input. */ ?>
            <article class="czabout__story"><?= $prepared['html'] ?></article>
        </section>
    <?php } ?>

    <!-- ============================= audiences ============================= -->
    <section class="czabout__section">
        <div class="czabout__section-head">
            <h2 class="czabout__h2">Built for everyone in the handmade world</h2>
            <p class="czabout__section-sub">Whichever side of the craft you are on, there is a place here.</p>
        </div>
        <div class="czabout__cards czabout__cards--4">
            <?php foreach ($audiences as $audience) { ?>
                <article class="czabout__card czabout__card--plain">
                    <span class="czabout__card-icon czabout__card-icon--soft"><i class="uil <?= $audience['icon'] ?>"></i></span>
                    <h3 class="czabout__card-title"><?= $audience['title'] ?></h3>
                    <p class="czabout__card-text"><?= $audience['text'] ?></p>
                </article>
            <?php } ?>
        </div>
    </section>

    <!-- =============================== cta =============================== -->
    <section class="czabout__cta">
        <div class="czabout__cta-copy">
            <h2 class="czabout__cta-title">Join the handmade movement</h2>
            <p class="czabout__cta-text">
                Whether you make, sell, supply or simply love handmade - there is room for you here.
            </p>
        </div>
        <div class="czabout__cta-actions">
            <a class="czabout__btn czabout__btn--primary" href="<?= base_url('seller/auth/sign_up') ?>">
                <i class="uil uil-store"></i> Start selling
            </a>
            <a class="czabout__btn czabout__btn--ghost" href="<?= base_url('products') ?>">
                <i class="uil uil-shopping-bag"></i> Start shopping
            </a>
            <?php if ($whatsapp !== '') { ?>
                <a class="czabout__btn czabout__btn--wa" href="<?= html_escape($whatsapp) ?>" target="_blank" rel="noopener">
                    <i class="uil uil-whatsapp"></i> Talk to us
                </a>
            <?php } ?>
        </div>
    </section>

    <p class="czabout__footnote">
        Questions about <?= html_escape($store) ?>?
        <a href="<?= base_url('contact-us') ?>">Get in touch</a> &middot;
        <a href="<?= base_url('terms-and-conditions') ?>">Terms of use</a> &middot;
        <a href="<?= base_url('privacy-policy') ?>">Privacy policy</a>
    </p>
</div>
