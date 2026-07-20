<?php
$this->load->model('category_model');
$categories = $this->category_model->get_categories(null, 8);
$language = get_languages();
$cookie_lang = $this->input->cookie('language', TRUE);
$language_index = 0;
if (!empty($cookie_lang)) {
    $language_index = array_search($cookie_lang, array_column($language, "language"));
}
$auth_settings = get_settings('authentication_settings', true);
?>
<?php $current_url = current_url(); ?>
<input type="hidden" id="currency" class="form-control" value="<?= $settings['currency'] ?>">
<input type="hidden" id="auth_settings" name="auth_settings" value='<?= isset($auth_settings['authentication_method']) ? $auth_settings['authentication_method'] : ''; ?>'>

<!-- header starts -->
<header class="wrapper bg-soft-primary">
    <nav class="navbar navbar-expand-lg center-nav transparent navbar-light navbar-clone fixed">
    </nav>

    <div class="content-wrapper">
        <header class="wrapper bg-light">
            <div class="bg-gradient-reverse-primary fs-15 fw-bold">
                <div class="container d-flex flax-wrap is_rtl">
                    <div class="d-flex align-items-baseline gap-1">
                        <span class="text-center w-6">
                            <a href="<?= $web_settings['app_download_section_appstore_url'] ?>" target="_blank" class="text-decoration-none" aria-label="apple store">
                                <i class="fs-22 text-dark uil uil-apple"></i></a>
                        </span>
                        <span class="text-center w-6">
                            <a href="<?= $web_settings['app_download_section_playstore_url'] ?>" target="_blank" class="text-decoration-none" aria-label="google play store"><i class="fs-20 text-green uil uil-google-play"></i></a>
                        </span>
                    </div>
                    <div class="d-flex flex-fill">
                        <ul class="align-items-center flex-row ms-auto navbar-nav">
                            <li class="dropdown language-select nav-item text-uppercase mr-4">
                                <a class="text-decoration-none dropdown-toggle py-0" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <?php if ($cookie_lang) { ?>
                                        <span class="text-primary font-weight-bold"><?= ucfirst($language[$language_index]['code']) ?></span>
                                    <?php } else { ?>
                                        <span class="text-primary font-weight-bold">En</span>
                                    <?php } ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-lg">
                                    <?php foreach ($language as $row) { ?>
                                        <a href="<?= base_url('home/lang/' . strtolower($row['language'])) ?>" class="dropdown-item"><?= strtoupper($row['code']) . ' - ' . ucfirst($row['language']) ?></a>
                                    <?php } ?>
                                </div>
                            </li>


                            <?php if ($this->ion_auth->logged_in()) {
                            ?>
                                <li class="nav-item dropdown active">
                                    <a class="text-decoration-none" data-toggle="dropdown" href="#"><i class="uil uil-user"></i>
                                        <span class="fs-16">
                                            <?= (isset($user->username) && !empty($user->username)) ? "Hello " . $user->username  : 'Login / Register' ?>
                                            <i class="fa fa-angle-down link-color"></i>
                                        </span>
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-lg">
                                        <a href="<?= base_url('my-account/wallet') ?>" class="dropdown-item text-decoration-none" aria-label="wallet"><i class="uil uil-wallet mr-2 text-primary link-color"></i> <?= $settings['currency']  ?><?= ' ' . isset($user->balance) && !empty($user->balance) ? number_format($user->balance, 2) : 0.0 ?></a>
                                        <a href="<?= base_url('my-account') ?>" class="dropdown-item text-decoration-none" aria-label="my profile"><i class="uil uil-user text-primary link-color mr-2"></i> <?= !empty($this->lang->line('profile')) ? $this->lang->line('profile') : 'Profile' ?> </a>
                                        <a href="<?= base_url('my-account/orders') ?>" class="dropdown-item text-decoration-none" aria-label="orders"><i class="link-color mr-2 text-primary uil uil-history"></i> <?= !empty($this->lang->line('orders')) ? $this->lang->line('orders') : 'Orders' ?> </a>
                                        <a href="<?= base_url('login/logout') ?>" class="dropdown-item text-decoration-none" aria-label="logout"><i class="link-color mr-2 text-primary uil uil-signout"></i><?= !empty($this->lang->line('logout')) ? $this->lang->line('logout') : 'Logout' ?></a>
                                    </div>
                                </li>

                            <?php } else { ?>
                                <li class="nav-item active">
                                    <a href="#" aria-label="login" class="mx-1 mb-2 mb-md-0 text-decoration-none hover" data-bs-toggle="modal" data-bs-target="#modal-signin"><?= !empty($this->lang->line('login')) ? $this->lang->line('login') : 'Login' ?>
                                    </a>
                                </li>
                                /
                                <li class="nav-item active">
                                    <a href="#" aria-label="register" class="mx-1 mb-2 mb-md-0 text-decoration-none hover" data-bs-toggle="modal" data-bs-target="#modal-signup"><?= !empty($this->lang->line('register')) ? $this->lang->line('register') : 'Register' ?>
                                    </a>
                                </li>
                            <?php } ?>
                            <!-- </div> -->
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- /.container -->
            </div>

            <nav class="navbar navbar-expand-lg center-nav transparent navbar-light">
                <div class="container flex-lg-row flex-nowrap align-items-center">
                    <div class="navbar-brand w-100 logo-img">
                        <a class="ml-2" href="<?= base_url() ?>">
                            <?php if (ALLOW_MODIFICATION == 0) { ?>
                                <img src="<?= base_url("assets/front_end/modern/img/logo/orange.png") ?>" alt="site-logo image" class="brand-logo-link logo-img" style="object-fit: contain;">
                            <?php } else { ?>
                                <?php $logo = get_settings('web_logo'); ?>
                                <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="brand-logo-link" alt="site-logo image"></a>
                            <?php } ?>
                        </a>
                    </div>
                    <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start">
                        <div class="offcanvas-header d-lg-none">
                            <?php if (ALLOW_MODIFICATION == 0) { ?>
                                <img src="<?= base_url("assets/front_end/modern/img/logo/orange.png") ?>" class="brand-logo-link logo-img h-10" alt="site-logo image">
                            <?php } else { ?>
                                <?php $logo = get_settings('web_logo'); ?>
                                <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="brand-logo-link h-10" alt="site-logo image"></a>
                            <?php } ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100">
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_url == base_url('')) ? 'active' : '' ?>" aria-current="page" aria-label="home" href="<?= base_url() ?>"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_url == base_url('products')) ? 'active' : '' ?>" href="<?= base_url('products') ?>" aria-label="products"><?= !empty($this->lang->line('products')) ? $this->lang->line('products') : 'Products' ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_url == base_url('sellers')) ? 'active' : '' ?>" href="<?= base_url('sellers') ?>" aria-label="sellers"><?= !empty($this->lang->line('sellers')) ? $this->lang->line('sellers') : 'Sellers' ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_url == base_url('home/contact-us')) ? 'active' : '' ?>" href="<?= base_url('home/contact-us') ?>" aria-label="contact-us"><?= !empty($this->lang->line('contact_us')) ? $this->lang->line('contact_us') : 'Contact Us' ?></a>
                                </li>
                                <!-- <li class="nav-item">
                                    <a class="nav-link <?= ($current_url == base_url('home/about-us')) ? 'active' : '' ?>" href="<?= base_url('home/about-us') ?>"><?= !empty($this->lang->line('about_us')) ? $this->lang->line('about_us') : 'About Us' ?></a>
                                </li> -->
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_url == base_url('home/faq')) ? 'active' : '' ?>" href="<?= base_url('home/faq') ?>" aria-label="faq"><?= !empty($this->lang->line('faq')) ? $this->lang->line('faq') : 'FAQs' ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_url == base_url('blogs/')) ? 'active' : '' ?>" href="<?= base_url('blogs/') ?>"><?= !empty($this->lang->line('blogs')) ? $this->lang->line('blogs') : 'Blogs' ?></a>
                                </li>
                            </ul>
                            <!-- /.offcanvas-footer -->
                        </div>
                        <!-- /.offcanvas-body -->
                    </div>
                    <!-- /.navbar-collapse -->
                    <div class="navbar-other w-100 d-flex ms-auto position_rtl">
                        <ul class="navbar-nav flex-row align-items-center ms-auto ul_rtl">
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-search"><i class="uil uil-search"></i></a></li>
                            <li class="nav-item">
                                <a href="<?= base_url('my-account/favorites') ?>" class="nav-link" aria-label="favorites">
                                    <i class="uil uil-heart"></i>
                                </a>
                            </li>
                            <li class="nav-item d-flex flex-row align-items-center">
                                <a href="<?= base_url('compare') ?>" class="nav-link d-flex" onclick=display_compare() data-product-id="<?= ($row['id']) != 0 ? $row['id'] : '' ?>" aria-label="compare">
                                    <i class="uil uil-exchange-alt"></i>
                                    <span class="badge bg-primary badge-cart badge-sm" id='compare_count'></span>
                                </a>
                            </li>
                            <?php $page = $this->uri->segment(2) == 'checkout' ? 'checkout' : '' ?>
                            <?php if ($page == 'checkout') { ?>
                                <li class="nav-item d-flex flex-row align-items-center">
                                    <a href="<?= base_url('cart') ?>" class="nav-link d-flex" aria-label="cart-image">
                                        <i class="uil uil-shopping-cart"></i>
                                        <span class="badge badge-cart bg-primary" id='cart-count'><?= (count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) != 0 ? count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) : ''); ?></span>
                                    </a>
                                </li>

                            <?php } else { ?>
                                <li class="nav-item d-flex flex-row align-items-center">
                                    <a href="javascript:void(0);" class="nav-link d-flex" aria-label="cart-image" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-cart">
                                        <i class="uil uil-shopping-cart"></i>
                                        <span class="badge badge-cart bg-primary" id='cart-count'><?= (count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) != 0 ? count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) : ''); ?></span>
                                    </a>
                                </li>
                            <?php } ?>
                            <li class="nav-item d-lg-none">
                                <button class="btn btn-link btn-sm fs-20 text-body mr-2 offcanvas-nav-btn p-0 text-decoration-none uil uil-bars"><span></span></button>
                            </li>
                        </ul>
                        <!-- /.navbar-nav -->
                    </div>
                    <!-- /.navbar-other -->
                </div>
                <!-- /.container -->
            </nav>
            <!-- /.navbar -->

            <div class="offcanvas offcanvas-end bg-light" id="offcanvas-cart" data-bs-scroll="true" aria-modal="true" role="dialog">
                <input type="hidden" name="is_loggedin" id="is_loggedin" value="<?= (isset($user->id)) ? 1 : 0 ?>">
                <div class="offcanvas-header">
                    <h3 class="mb-0"><?= !empty($this->lang->line('shopping_cart')) ? $this->lang->line('shopping_cart') : 'Shopping Cart' ?></h3>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body d-flex flex-column" id="cart-item-sidebar">
                    <?php
                    if (isset($user->id)) {
                        $cart_items = $this->cart_model->get_user_cart($user->id);
                        if (count($cart_items) != 0) {
                            foreach ($cart_items as $items) {
                                $price = $items['special_price'] != '' && $items['special_price'] > 0 && $items['special_price'] != null ? $items['special_price'] : $items['price']; ?>
                                <div class="shopping-cart">
                                    <div class="shopping-cart-item d-flex justify-content-between mb-4">
                                        <div class="d-flex flex-row gap-3">

                                            <figure class="rounded cart-img">
                                                <a href="<?= base_url('products/details/' . $items['product_slug']) ?>">
                                                    <img src="<?= base_url($items['image']) ?>" alt="<?= html_escape($items['name']) ?>" title="<?= html_escape($items['name']) ?>" style="object-fit: contain;">
                                                </a>
                                            </figure>
                                            <div class="w-100">
                                                <h3 class="post-title fs-16 lh-xs mb-1">
                                                    <?= short_description_word_limit(strip_tags(output_escaping(str_replace('\r\n', '&#13;&#10;', $items['name']))), 35) ?> <?= (isset($check_current_stock_status['error'])  && $check_current_stock_status['error'] == TRUE) ? "<span class='badge badge-danger'>  Out of Stock </span>" :  "" ?>
                                                </h3>
                                                <span>
                                                    <?php if (!empty($items['product_variants'])) { ?>
                                                        <?= str_replace(',', ' | ', $items['product_variants'][0]['variant_values']) ?>
                                                    <?php } ?>
                                                </span>
                                                <p class="price">
                                                    <!-- <del><span class="amount">$55.00</span></del> -->
                                                    <span class="amount"><?= $settings['currency'] . ' ' . $price ?></span>
                                                </p>

                                                <div class="product-pricing d-flex py-2 w-100">
                                                    <div class="product-quantity product-sm-quantity">
                                                        <input type="number" name="header_qty" class="form-control d-flex align-content-center h-9 w-14" value="<?= $items['qty'] ?>" data-id="<?= $items['product_variant_id'] ?>" data-price="<?= $price ?>" min="<?= $items['minimum_order_quantity'] ?>" max="<?= $items['total_allowed_quantity'] ?>" step="<?= $items['quantity_step_size'] ?>">
                                                    </div>
                                                    <div class="product-line-price align-self-center px-1"><?= $settings['currency'] . ' ' . number_format($items['qty'] * $price, 2) ?></div>
                                                </div>
                                                <!--/.form-select-wrapper -->
                                            </div>

                                        </div>
                                        <div class="product-sm-removal">
                                            <button class="remove-product btn btn-sm btn-danger rounded-1 p-1 py-0" data-id="<?= $items['product_variant_id'] ?>">
                                                <i class="uil uil-trash-alt"></i>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            <?php
                            } ?>
                        <?php } else { ?>
                            <h1 class="h4 text-center">
                                <?= !empty($this->lang->line('empty_cart_message')) ? $this->lang->line('empty_cart_message') : 'Your cart is empty' ?>
                            </h1>
                            <img src="<?= base_url('assets/front_end/modern/img/new/empty-cart(4).png') ?>" alt="Empty Cart" class="mt-16" />
                        <?php } ?>
                    <?php } else { ?>
                        <h1 class="h4 text-center">
                            <?= !empty($this->lang->line('empty_cart_message')) ? $this->lang->line('empty_cart_message') : 'Your cart is empty' ?>
                        </h1>
                        <img src="<?= base_url('assets/front_end/modern/img/new/empty-cart(4).png') ?>" alt="Empty Cart" class="mt-16" />
                    <?php } ?>




                    <!--/.shopping-cart-item -->
                    <!-- /.shopping-cart-->
                </div>
                <div class="offcanvas-footer flex-column text-center container">
                    <a class="btn btn-red rounded-pill ms-6 mr-4 mb-5" href="<?= base_url('products') ?>">
                        <?= !empty($this->lang->line('return_to_shop')) ? $this->lang->line('return_to_shop') : 'Return to Shop' ?>
                    </a>

                    <?php if ((isset($user->id)) == 1) { ?>
                        <a href="<?= base_url('cart') ?>" class="btn btn-primary btn-icon btn-icon-start rounded-pill mb-4 view_cart_button ms-6 mr-4">
                            <i class="fs-18 uil uil-shopping-bag"></i>
                            <?= !empty($this->lang->line('view_cart')) ? $this->lang->line('view_cart') : 'View Cart' ?>
                        </a>
                    <?php } else { ?>
                        <a href="#" class="btn btn-primary rounded-pill btn-icon btn-icon-start mb-4 view_cart_button ms-6 mr-4" data-bs-toggle="modal" data-bs-target="#modal-signin">
                            <i class="fs-18 uil uil-shopping-bag"></i>
                            <?= !empty($this->lang->line('view_cart')) ? $this->lang->line('view_cart') : 'View Cart' ?>
                        </a>
                    <?php } ?>

                </div>
                <!-- /.offcanvas-footer-->
                <!-- /.offcanvas-body -->
            </div>

            <div class="offcanvas offcanvas-top bg-light" id="offcanvas-search" data-bs-scroll="true">
                <div class="container d-flex flex-row py-4">
                    <!-- <form class="w-100"> -->
                    <select class="form-control rounded-0 search_product" type="text" aria-label="Search"></select>
                    <!-- </form> -->
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
            </div>
            <!-- /.offcanvas -->


        </header>
        <!-- /header -->
    </div>

</header>
<!-- header ends -->

