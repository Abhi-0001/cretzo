<?php
/**
 * ============================================================================
 * Site footer.
 * ============================================================================
 *
 * Rebuilt onto the `czfoot` design system. The previous footer was four bare
 * <ul>s in a flex row: no trust signals, no payment marks, no copyright line at
 * all (the `copyright_details` setting was stored and never printed), and the
 * social icons were four differently-styled PNGs sitting on the background.
 *
 * Structure, top to bottom:
 *   1. Brand column   - logo and positioning line.
 *   2. Link columns   - Information / Shop / Account, unchanged destinations,
 *                       then a "Get in touch" column: contact chips + social.
 *   3. Legal bar      - copyright and the accepted payment marks.
 *
 * A trust/USP strip was tried above these and removed at the owner's request -
 * do not reintroduce it. The "Sell with Cretzo" call to action WAS wanted back
 * (owner, 2026-09-06) and now lives in the brand column as a single pill, not
 * the full-width card that was removed.
 *
 * Every URL here is the URL the old footer used; nothing was re-pointed. The
 * signed-out variants of the account links still open #modal-signin exactly as
 * before, so the auth modal contract is untouched.
 *
 * On mobile the three link columns become <details> accordions - a 24-link
 * stack is otherwise most of the page. Desktop neutralises the <details> in CSS
 * (summary pointer-events:none, body forced open), so there is no JS here at
 * all and no way for the columns to end up collapsed on a wide screen.
 */

$web_settings = get_settings('web_settings', true);
$system_settings = get_settings('system_settings', true);

$czf_logo      = get_settings('web_logo');
$czf_about     = isset($web_settings['app_short_description']) ? trim($web_settings['app_short_description']) : '';
$czf_email     = isset($web_settings['support_email']) ? trim($web_settings['support_email']) : '';
$czf_phone     = isset($web_settings['support_number']) ? trim($web_settings['support_number']) : '';
$czf_address   = isset($web_settings['address']) ? trim($web_settings['address']) : '';
$czf_copyright = isset($web_settings['copyright_details']) ? trim($web_settings['copyright_details']) : '';
$czf_store     = (isset($system_settings['app_name']) && trim($system_settings['app_name']) !== '')
    ? trim($system_settings['app_name'])
    : 'Cretzo';

$czf_social = [
    ['key' => 'instagram_link', 'label' => 'Instagram', 'icon' => 'uil-instagram'],
    ['key' => 'facebook_link',  'label' => 'Facebook',  'icon' => 'uil-facebook-f'],
    ['key' => 'twitter_link',   'label' => 'X (Twitter)', 'icon' => 'uil-twitter'],
    ['key' => 'youtube_link',   'label' => 'YouTube',   'icon' => 'uil-youtube'],
];

/*
 * Account links are the only conditional destinations in the footer: a
 * signed-in customer goes to the page, everyone else opens the sign-in modal.
 * Sellers, admins and delivery accounts get the modal path too - /my-account is
 * customer-only and used to 302 them somewhere confusing.
 */
$czf_is_customer = $this->ion_auth->logged_in()
    && !$this->ion_auth->is_seller()
    && !$this->ion_auth->is_delivery_boy()
    && !$this->ion_auth->is_admin();

/*
 * Seller call to action. A visitor who is not signed in as a seller is a
 * prospective one, so the pill opens seller/auth/sign_up - the real signup form.
 * NOT seller/auth/register (no such route) and not base_url('seller'), which is
 * the seller LOGIN and is the wrong door for someone who has no account yet.
 * An existing seller gets their dashboard instead of a pitch to sign up again;
 * seller/home is behind the panel-access check, so a deactivated seller is
 * bounced to the seller login by that controller rather than by anything here.
 */
$czf_seller_is_seller = $this->ion_auth->logged_in() && $this->ion_auth->is_seller();
$czf_seller_url   = $czf_seller_is_seller ? base_url('seller/home') : base_url('seller/auth/sign_up');
$czf_seller_label = $czf_seller_is_seller ? 'Seller Dashboard' : 'Sell with ' . $czf_store;

/*
 * Contact chips + social row, rendered as the last footer column. Built up here
 * rather than inline so the empty-value skipping stays readable: an unset
 * support email or social link must not leave a hole in the list or icon row.
 */
$czf_contact_rows = '';
if ($czf_email !== '') {
    $czf_contact_rows .= '<li><i class="uil uil-envelope"></i>'
        . '<a href="mailto:' . html_escape($czf_email) . '">' . html_escape($czf_email) . '</a></li>';
}
if ($czf_phone !== '') {
    /* tel: must not carry the spaces an admin may have typed. */
    $czf_contact_rows .= '<li><i class="uil uil-phone"></i>'
        . '<a href="tel:' . html_escape(preg_replace('/[^0-9+]/', '', $czf_phone)) . '">' . html_escape($czf_phone) . '</a></li>';
}
if ($czf_address !== '') {
    $czf_contact_rows .= '<li><i class="uil uil-map-marker"></i><span>' . html_escape($czf_address) . '</span></li>';
}

/* The admin panel stores the four social links one per key; empty ones must not
 * leave a gap in the icon row. */
$czf_social_html = '';
foreach ($czf_social as $network) {
    if (empty($web_settings[$network['key']])) {
        continue;
    }
    $czf_social_html .= '<a class="czfoot__social-link" href="' . html_escape($web_settings[$network['key']]) . '"'
        . ' target="_blank" rel="noopener" aria-label="' . html_escape($network['label']) . '">'
        . '<i class="uil ' . $network['icon'] . '"></i></a>';
}

$czf_reach_html = '';
if ($czf_contact_rows !== '') {
    $czf_reach_html .= '<ul class="czfoot__contact">' . $czf_contact_rows . '</ul>';
}
if ($czf_social_html !== '') {
    $czf_reach_html .= '<div class="czfoot__social">'
        . '<p class="czfoot__social-label">Follow the makers</p>'
        . '<div class="czfoot__social-row">' . $czf_social_html . '</div>'
        . '</div>';
}

$czf_payment_marks = [
    'UPI_Logo.png'        => 'UPI',
    'Google_Pay_Logo.png' => 'Google Pay',
    'PhonePe_Logo.png'    => 'PhonePe',
    'Paytm_Logo.png'      => 'Paytm',
    'Visa_Logo.png'       => 'Visa',
    'Mastercard_Logo.png' => 'Mastercard',
    'RuPay_Logo.png'      => 'RuPay',
    'Razorpay_Logo.png'   => 'Razorpay',
];
?>

<!-- footer starts -->
<?php
/* A page can drop the visible site footer by setting $hide_footer in its controller.
   Only this <footer> is skipped - everything else in this view (auth + quick-view
   modals, the support chat launcher, the toast styling and the shared scripts below)
   still loads, which is why this is a flag here rather than $hide_header_footer:
   that one skips the whole view and would take those with it. */
$hide_footer = !empty($hide_footer);
?>
<?php if (!$hide_footer) { ?>
<footer class="czfoot">

    <!-- 1-2. brand + links -------------------------------------------------- -->
    <div class="czfoot__main">

        <div class="czfoot__brand">
            <a class="czfoot__logo" href="<?= base_url() ?>" aria-label="Cretzo home">
                <img src="<?= base_url($czf_logo) ?>" alt="Cretzo">
            </a>

            <?php if ($czf_about !== '') { ?>
                <p class="czfoot__about"><?= html_escape($czf_about) ?></p>
            <?php } else { ?>
                <p class="czfoot__about">A marketplace for handmade work - artists, artisans, suppliers and the people who value what they make.</p>
            <?php } ?>

            <a class="czfoot__seller" href="<?= $czf_seller_url ?>">
                <i class="uil uil-store"></i>
                <span><?= html_escape($czf_seller_label) ?></span>
                <i class="uil uil-arrow-right czfoot__seller-arrow"></i>
            </a>

        </div>

        <details class="czfoot__col" open>
            <summary class="czfoot__col-title">Information <i class="uil uil-angle-down"></i></summary>
            <ul class="czfoot__links">
                <li><a href="<?= base_url('about-us') ?>">About Us</a></li>
                <li><a href="<?= base_url('contact-us') ?>">Contact Us</a></li>
                <li><a href="<?= base_url('home/faq') ?>">Help Centre &amp; FAQs</a></li>
                <li><a href="<?= base_url('terms-and-conditions') ?>">Terms &amp; Conditions</a></li>
                <li><a href="<?= base_url('privacy-policy') ?>">Privacy Policy</a></li>
                <li><a href="<?= base_url('return-policy') ?>">Return &amp; Refund Policy</a></li>
                <li><a href="<?= base_url('shipping-policy') ?>">Shipping Policy</a></li>
            </ul>
        </details>

        <details class="czfoot__col" open>
            <summary class="czfoot__col-title">Shop <i class="uil uil-angle-down"></i></summary>
            <ul class="czfoot__links">
                <li><a href="<?= base_url('products') ?>">All Products</a></li>
                <li><a href="<?= base_url('home/categories') ?>">Categories</a></li>
                <li><a href="<?= base_url('sellers') ?>">Our Sellers</a></li>
                <li><a href="<?= base_url('cart') ?>">Cart</a></li>
                <li><a href="<?= base_url('blogs') ?>">Blog</a></li>
            </ul>
        </details>

        <details class="czfoot__col" open>
            <summary class="czfoot__col-title">Your Account <i class="uil uil-angle-down"></i></summary>
            <ul class="czfoot__links">
                <?php if ($czf_is_customer) { ?>
                    <li><a href="<?= base_url('my-account') ?>">My Account</a></li>
                    <li><a href="<?= base_url('my-account/orders') ?>">My Orders</a></li>
                    <li><a href="<?= base_url('my-account/favorites') ?>">Wishlist</a></li>
                    <li><a href="<?= base_url('my-account/wallet') ?>">Wallet</a></li>
                    <li><a href="<?= base_url('my-account/support') ?>">Support Tickets</a></li>
                <?php } else { ?>
                    <?php /* Same modal trigger the old footer used - the auth modal binds to
                             #modal-signin and nothing else. */ ?>
                    <li><a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin">Sign in</a></li>
                    <li><a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin">My Orders</a></li>
                    <li><a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin">Wishlist</a></li>
                    <li><a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin">Wallet</a></li>
                    <li><a href="<?= base_url('contact-us') ?>">Support</a></li>
                <?php } ?>
            </ul>
        </details>

        <?php if ($czf_reach_html !== '') { ?>
            <div class="czfoot__col czfoot__col--reach">
                <p class="czfoot__col-title czfoot__col-title--static">Get in touch</p>
                <?= $czf_reach_html ?>
            </div>
        <?php } ?>

    </div>

    <!-- 3. legal bar ------------------------------------------------------- -->
    <div class="czfoot__bar">
        <div class="czfoot__bar-inner">
            <p class="czfoot__copy">
                <?= ($czf_copyright !== '')
                    ? html_escape($czf_copyright)
                    : 'Copyright ' . date('Y') . ' &copy; cretzo.com. All Rights Reserved.' ?>
            </p>

            <div class="czfoot__pay">
                <span class="czfoot__pay-label">We accept</span>
                <?php foreach ($czf_payment_marks as $file => $label) { ?>
                    <img class="czfoot__pay-mark lazy"
                         src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/payment_methods/' . $file) ?>"
                         alt="<?= html_escape($label) ?>" loading="lazy">
                <?php } ?>
            </div>
        </div>
    </div>
</footer>
<?php } ?>
<!-- footer ends -->



<?php if (ALLOW_MODIFICATION == 0) { ?>

    <!-- color switcher -->
    <div id="colors-switcher">
        <div>
            <h6><?= !empty($this->lang->line('pick_your_favorite_color')) ? $this->lang->line('pick_your_favorite_color') : 'Pick Your Favorite Color' ?></h6>
            <ul class="color-style text-center mb-2">
                <li class="list-item-inline">
                    <a href="#" class="color-switcher orange" aria-label="orange-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/orange.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/orange.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher blue" aria-label="blue-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/blue.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/dark-blue.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher aqua" aria-label="aqua-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/aqua.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/aqua.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher fuchsia" aria-label="fuchsia-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/fuchsia.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/fuchsia.png") ?>"></a>
                </li>

                <li class="list-item-inline">
                    <a href="#" class="color-switcher grape" aria-label="grape-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/grape.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/grape.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher green" aria-label="green-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/green.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/green.png") ?>"></a>
                </li>

                <li class="list-item-inline">
                    <a href="#" class="color-switcher leaf" aria-label="leaf-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/leaf.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/leaf.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher navy" aria-label="navy-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/navy.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/navy.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher pink" aria-label="pink-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/pink.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/pink.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher purple" aria-label="purple-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/purple.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/purple.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher red" aria-label="red-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/red.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/red.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher sky" aria-label="sky-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/sky.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/sky.png") ?>"></a>
                </li>
                <li class="list-item-inline">
                    <a href="#" class="color-switcher violet" aria-label="violet-logo" data-url="<?= base_url("/assets/front_end/modern/css/colors/violet.css") ?>" data-image="<?= base_url("assets/front_end/modern/img/logo/violet.png") ?>"></a>
                </li>

            </ul>
            <div class="color-bottom">
                <a href="#" aria-label="color-switcher" class="settings bg-white d-block"><i class="fa fa-cog fa-lg fa-spin setting-icon"></i></a>
            </div>
        </div>
    </div> <!-- end color switcher -->
<?php } ?>


<?php $this->load->view('front-end/' . THEME . '/auth-modals'); ?>

<!-- quick view -->
<div id="quick-view" data-iziModal-group="grupo3" class='product-page-content'>
    <button data-izimodal-close="" class="icon-close qv-close" aria-label="Close">
        <i class="fa fa-close"></i>
    </button>
    <div class="row g-0 qv-grid">

        <!-- Gallery (desktop) -->
        <div class="col-12 col-lg-6 qv-gallery product-preview-image-section-md swiper-thumbs-container">
            <div class="swiper-container gallery-top overflow-hidden">
                <div class="swiper-wrapper-main swiper-wrapper"></div>
            </div>
            <div class="swiper-container gallery-thumbs overflow-hidden">
                <div class="swiper-wrapper-thumbs swiper-wrapper"></div>
            </div>
        </div>
        <!-- Gallery (mobile) -->
        <div class="col-12 qv-gallery-mobile product-preview-image-section-sm">
            <div class="swiper-container mobile-image-swiper">
                <div class="mobile-swiper swiper-wrapper-mobile swiper-wrapper"></div>
            </div>
        </div>

        <!-- Details — mirrors the product detail page layout/typography -->
        <div class="col-12 col-lg-6 qv-details product-page-details">

            <!-- seller ("Sold by …", injected) -->
            <div id="modal-product-sellers" class="qv-seller text-b op-6"></div>

            <!-- title -->
            <h1 class="heading-b product-name qv-title" id="modal-product-title"></h1>

            <!-- rating -->
            <div class="qv-rating-row rating-star-container">
                <input type="text" id="modal-product-rating" class="d-none" data-size="xs" value="0" data-show-clear="false" data-show-caption="false" readonly>
                <span class="qv-review-count text-n">(<span class="rating-status" id="modal-product-no-of-ratings">0</span> <?= !empty($this->lang->line('reviews')) ? $this->lang->line('reviews') : 'reviews' ?>)</span>
            </div>

            <div id="modal-product-brand" class="d-flex gap-1 qv-brand"></div>

            <!-- price + discount -->
            <p class="price qv-price">
                <span id="modal-product-price"></span>
                <span class="striped-price qv-old-price" id="modal-product-special-price-div">
                    <s id="modal-product-special-price"></s>
                </span>
                <span class="qv-discount" id="modal-product-discount"></span>
            </p>

            <!-- stock (injected) -->
            <div class="qv-stock product-stock" id="modal-product-stock"></div>

            <!-- short description -->
            <p id="modal-product-short-description" class="text-n product-description qv-desc"></p>

            <!-- variant options + zipcode (injected) -->
            <div class="qv-options" id="modal-product-variant-attributes"></div>
            <div id="modal-product-variants-div"></div>

            <!-- buy row: quantity + add to cart + wishlist + compare -->
            <div class="qv-buy-row">
                <div class="num-block skin-2 qv-qty">
                    <div class="num-in form-control d-flex align-items-center">
                        <span class="minus dis" data-min="1" data-step="1"></span>
                        <input type="text" class="in-num" id="modal-product-quantity" value="1" data-min="1" data-step="1" data-max="">
                        <span class="plus" data-max="" data-step="1"></span>
                    </div>
                </div>
                <button class="add_to_cart btn qv-add-btn" id="modal-add-to-cart-button"><i class="uil uil-shopping-bag"></i> <?= !empty($this->lang->line('add_to_cart')) ? $this->lang->line('add_to_cart') : 'Add To Cart' ?></button>
                <button type="button" class="wishlist-icon-btn qv-fav add-fav" id="add_to_favorite_btn" aria-label="Add to wishlist" title="Add to wishlist"><i class="fa fa-heart-o"></i></button>
                <button type="button" class="wishlist-icon-btn qv-compare compare-soon-btn" id="compare" aria-label="Compare product" title="Compare"><i class="uil uil-exchange-alt"></i></button>
            </div>

            <div class="qv-tags">
                <div id="modal-product-tags"></div>
            </div>
        </div>
    </div>
</div>

<?php if (ALLOW_MODIFICATION == 0) { ?>
    <div class="buy-now-btn">
        <a href="https://codecanyon.net/item/eshop-web-multi-vendor-ecommerce-marketplace-cms/34380052" target="_blank" class="btn btn-danger btn-sm rounded-pill"> <i class="fa fa-shopping-cart"></i>&nbsp; <?= !empty($this->lang->line('buy_now')) ? $this->lang->line('buy_now') : 'Buy Now' ?></a>
    </div>
<?php } ?>

<?php /* Same link as the footer, kept in a global so the "coming soon" dialogs in
         cretzo-fixes.js can send people to WhatsApp without hardcoding a number. */ ?>
<script>window.CRETZO_WHATSAPP_LINK = <?= json_encode(whatsapp_support_link()) ?>;</script>

<?php /* No floating WhatsApp launcher here on purpose: stacked next to the chat widget's own
         green bubble it read as a second, competing action. WhatsApp is reached from the footer,
         contact-us, the chat pages and the widget's own "WhatsApp Support" button instead. */ ?>
<div class="fixed-icon">
<?php /* The panel used to be a hardcoded 450x600 inline style, which is wider than a phone
         viewport - on mobile it hung off the right edge of the screen and could not be read.
         Sizing now lives in custom.css (#chat-iframe) where a media query can shrink it, and
         the iframe is loaded lazily so a widget nobody opens does not cost every page a
         second document. */ ?>
<button type="button" id="chat-button" aria-label="Open support chat" aria-expanded="false" aria-controls="chat-iframe">
    <img src="<?= add_ver(base_url('assets/front_end/cretzo/img/chat-fab-icon.png')) ?>" alt="" id="chat-button-icon">
    <i class="uil uil-times" id="chat-button-close-icon" aria-hidden="true"></i>
</button>
    <!-- Floating chat iframe -->
    <iframe src="<?= base_url('my-account/floating_chat_modern') ?>" id="chat-iframe" title="Support chat" loading="lazy"></iframe>
    <div class="progress-wrap mt-2">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>
</div>
<!-- end -->
<!-- main content ends -->
<script>
$(document).ready(function () {

    let searchDebounce = null;

    function clearSearchDropdown() {
        $("#append_desktop_search").html("");
        $("#append_mobile_search").html("");
    }

    function renderSuggestions(data) {
        let html = '';
        if (data && data.length > 0) {
            html += '<div class="list-group" style="max-height:320px;overflow-y:auto;">';

            $.each(data, function (index, item) {
                const rawName = String(item.name || '');
                const safeName = rawName.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                const safeUrl = String(item.url || '').replace(/"/g, '&quot;');

                html += `
                    <div class="search-item text-n mega-list-item" onclick="selectSuggestion(&quot;${safeName}&quot;,&quot;${safeUrl}&quot;)">
                        <div>${safeName}</div>
                    </div>
                `;
            });

            html += '</div>';
        } else {
            html = '<div class="search-item text-n mega-list-item">No results found</div>';
        }

        $("#append_desktop_search").html(html);
        $("#append_mobile_search").html(html);
    }

    $(".search_field").on("keyup", function (e) {
        let search = $(this).val().trim();

        if (e.key === 'Enter') {
            searchProduct();
            return;
        }

        if (search.length < 1) {
            clearSearchDropdown();
            return;
        }

        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }

        searchDebounce = setTimeout(function () {
            $.ajax({
                url: base_url + "/search/search_data",
                type: "GET",
                data: { search: search, limit: 12 },
                dataType: "json",
                success: function (response) {
                    if (typeof response === "string") {
                        response = JSON.parse(response);
                    }
                    renderSuggestions(response.data || []);
                },
                error: function () {
                    $("#append_desktop_search").html(
                        '<div class="search-item">Error fetching data</div>'
                    );
                    $("#append_mobile_search").html(
                        '<div class="search-item">Error fetching data</div>'
                    );
                }
            });
        }, 180);

    });

});

// Fill value into search box
function selectSuggestion(name, url) {
    $(".search_field").val('');
    if (url && url.length > 0) {
        window.location.href = url;
    } else {
        window.location.href = base_url + '/products/search?q=' + encodeURIComponent(name);
    }
        
    // $(".search_field").val(name);
    // $("#append_desktop_search").html("");
    // $("#append_mobile_search").html("");
}

// Hide suggestions when clicking outside
$(document).click(function (e) {
    if (!$(e.target).closest('.search-container-m, .search-container').length) {
        $("#append_desktop_search").html("");
        $("#append_mobile_search").html("");
    }
});
</script>

<link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<?php
/*
 * PERFORMANCE: sweetalert2 was loaded TWICE - this jsdelivr copy (v10) and the local
 * js/sweetalert2.min.js (v11.12.3) in include-script.php. template.php loads footer.php
 * BEFORE include-script.php, so v10 landed first and was then overwritten wholesale by
 * the local v11; every Swal call on the site, and the Toast mixin that custom.js builds
 * at load time and that fires 82 times across the storefront, is already v11. This tag
 * was a cross-origin, render-blocking download of a library the page then threw away.
 */
?>

<style>
/* ─────────────────────────────────────────────────────────────
   Cart / global notifications (SweetAlert2 toasts): sit just BELOW
   the header instead of overlapping it. The Toast mixin uses
   position:"top" (top-center); these rules push it down clear of the
   header. Offsets are tunable per header height.
   ───────────────────────────────────────────────────────────── */
body.swal2-toast-shown .swal2-container.swal2-top,
body.swal2-toast-shown .swal2-container.swal2-top-start,
body.swal2-toast-shown .swal2-container.swal2-top-end {
    top: 55px !important;   /* just below the desktop header / cart & profile icons */
}
@media (max-width: 768px) {
    /* On a phone, clearing the whole mobile header (logo row + search bar) pushed the
       toast ~140px down - a third of the way into a 400px-tall screen, well away from
       where the eye is and easy to miss entirely. There is no room to sit "below the
       header" on mobile, so the toast overlays the top of it instead: it already
       carries z-index 100060 (see cretzo-override.css), so it draws over the sticky
       header and disappears again on its 3s timer. */
    body.swal2-toast-shown .swal2-container.swal2-top,
    body.swal2-toast-shown .swal2-container.swal2-top-start,
    body.swal2-toast-shown .swal2-container.swal2-top-end {
        top: 8px !important;
    }
}

/* Legacy toastr styling kept for any remaining toastr.* calls (harmless if unused). */
#toast-container.toast-top-center {
    top: 90px;            /* clears the desktop header */
    right: 0;
    left: 0;
    margin: 0 auto;
    width: auto;
    pointer-events: none; /* don't block header clicks behind it */
}
#toast-container.toast-top-center > div {
    margin: 0 auto 6px auto;
    pointer-events: auto;
}
@media (max-width: 768px) {
    /* Same reasoning as the SweetAlert2 block above - keep it at the top of the
       screen rather than below the mobile header. */
    #toast-container.toast-top-center { top: 8px; }
}

/* Target the success toast specifically */
#toast-container > .toast-success {
    background-color: var(--color-orange, #F2822E) !important; /* theme orange */
    color: #fff !important;
    opacity: 1;
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.18);

    /* Compact pill */
    width: auto !important;
    max-width: 320px;
    min-height: 40px !important;
    padding: 12px 18px 12px 45px !important; /* room for the icon */
    font-size: 13px;
}

/* Adjusting the progress bar to match */
#toast-container > .toast-success .toast-progress {
    background-color: #fff;
    opacity: 0.4;
}

/* Ensure the icon stays white */
.toast-success {
    background-image: none !important; /* removes default icon if it looks crowded */
}
</style>

<script>
function addtocartMessage() {
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-center",
        "preventDuplicates": true,
        "showDuration": "300",
        "hideDuration": "300",
        "timeOut": "2000", // 2 seconds
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    toastr.success("Added to Cart");
}
</script>
<script>
    $(window).on('scroll', function () {
    if ($(window).scrollTop() > 100) {
        $('#append_desktop_search').css('display', 'none');
    }else{
        $('#append_desktop_search').css('display', 'block');
    }
});

</script>

