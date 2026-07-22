<?php
$this->load->model('category_model');
$categories = $this->category_model->get_categories(null, 12);


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
<style>
/* Wrapper so dropdown stays aligned below input */
.search-wrapper {
    position: relative;
    width: 100%;
}

/* Suggestions box */
#append_desktop_search {
    position: absolute;
    top: 100%;         /* directly under input */
    left: 0;
    width: 100%;
    background: #fff;
    border-top: none;
    z-index: 9999;     /* highest z-index */
    display: block;     
}

/* Each suggestion */
.search-item {
    color:black;
    padding: 5px;
    padding-left:30px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

/* Hover effect */
.search-item:hover {
    background: #f7f7f7;
}

</style>

<!-- header starts -->
<!-- <header class="wrapper bg-soft-primary"> -->
<!-- removed the bg-soft-primary class to hide the color due to padding below header, temporary patch, remove the padding itself later maybe -->
<body class="cretzo-header">
    
    <!-- header starts -->
    <section class="header-container">
        <!-- main logo -->
        <?php $logo = get_settings('web_logo'); ?>
        <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="main-logo" alt="site-logo image"></a>

        <!-- search -->
        <div class="search-wrapper">
            <div class="search-container flex-1">
                <input class="search_field" type="text" placeholder="Search">
                <img class="search-icon" alt="search icon" 
                    src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/search-icon.png') ?>" 
                    onclick="searchProduct()">
            </div>

            <!-- Suggestions appear here -->
            <div id="append_desktop_search" class="search-result-box"></div>
        </div>

        <!-- icons -->
        <ul class="icon-container">
            
            <!-- display profile icon depending on whether the user is logged in or not -->
            <?php if( $this->ion_auth->logged_in() && !$this->ion_auth->is_seller() && !$this->ion_auth->is_delivery_boy() && !$this->ion_auth->is_admin()) { ?>
                <li class="nav-item dropdown active pb-1">
                    <!-- <a class="text-decoration-none" data-toggle="dropdown" href="#"><i class="uil uil-user"></i>
                        <span class="fs-16">
                            <?= (isset($user->username) && !empty($user->username)) ? "Hello " . $user->username  : 'Guest' ?>
                            <i class="fa fa-angle-down link-color"></i>
                        </span>
                    </a> -->

                    <a class="text-decoration-none" data-toggle="dropdown" href="#" aria-label="profile">
                        <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/user.png') ?>">
                    </a>

                    <div class="dropdown-menu dropdown-menu-lg profile-menu-loggedin">
                        
                        <div class="px-5 mb-2">
                            <span class="text-n fw-bold">Welcome <?= (isset($user->username) && !empty($user->username)) ? $user->username  : 'Guest' ?></span><br>
                            <span class="text-s">To manage profile and orders</span>
                        </div>

                        <div class="divider-b mb-2"></div>

                        <div class="px-5">
                            <a href="<?= base_url('my-account/wallet') ?>" class="dropdown-item text-decoration-none pb-2" aria-label="wallet"><i class="uil uil-wallet mr-2"></i> <?= $settings['currency']  ?><?= ' ' . isset($user->balance) && !empty($user->balance) ? number_format($user->balance, 2) : 0.0 ?></a>
                            <a href="<?= base_url('my-account') ?>" class="dropdown-item text-decoration-none pb-2" aria-label="my profile"><i class="uil uil-user mr-2"></i> <?= !empty($this->lang->line('profile')) ? $this->lang->line('profile') : 'Profile' ?> </a>
                            <a href="<?= base_url('my-account/orders') ?>" class="dropdown-item text-decoration-none pb-2" aria-label="orders"><i class="uil uil-history mr-2"></i> <?= !empty($this->lang->line('orders')) ? $this->lang->line('orders') : 'Orders' ?> </a>
                            <a href="<?= base_url('login/logout') ?>" class="dropdown-item text-decoration-none" style="color: red !important;" aria-label="logout"><i class="uil uil-signout mr-2"></i><?= !empty($this->lang->line('logout')) ? $this->lang->line('logout') : 'Logout' ?></a>
                        </div>
                    </div>
                </li>
            <?php } else { ?>
                <li class="icon profile-icon pb-1">
                    <!-- <a href="#" data-toggle="dropdown"> -->
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin">
                        <!-- <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/profile-icon.png') ?>"> -->
                        <!-- <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/profile-icon-2.png') ?>"> -->
                        <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/user.png') ?>">
                    </a>
                    <ul class="list profile-menu p-2">

                        <li class="profile-item">
                            <span class="text fw-bold">Welcome</span><br>
                            <span class="text-s">To manage profile and orders</span>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin">
                                <button style="display: inline-block;" class="cretzo btn btn-light-simple my-2"> <span>Login / Sign Up</span> </button>
                            </a>
                        </li>

                        <div class="divider-b"></div>

                        <a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin" class="text-decoration-none"><li class="text profile-item text-s">Orders</li></a>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin" class="text-decoration-none"><li class="text profile-item text-s">Wishlist</li></a>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin" class="text-decoration-none"><li class="text profile-item text-s">Saved Addresses</li></a>
                        <div class="divider-b"></div>
                        <a href="<?= base_url('home/contact-us') ?>" class="text-decoration-none"><li class="text profile-item text-s">Contact Us</li></a>
                        
                        <!-- <li class="profile-item">
                            <span class="text">Name</span><br>
                            <span class="text-s">Number</span>
                        </li>
                        <li class="text profile-item">item 2</li>
                        <li class="text profile-item">item 3</li> -->
                    </ul>

                    <!-- <div class="dropdown-menu dropdown-menu-lg">
                        <a href="#" class="dropdown-item text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-signin"> <?= !empty($this->lang->line('login')) ? $this->lang->line('login') : 'Login' ?> </a>
                        <a href="#" class="dropdown-item text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-signup"> <?= !empty($this->lang->line('register')) ? $this->lang->line('register') : 'Register' ?> </a>
                    </div> -->
                </li>
            <?php } ?>
            
            <li class="icon">
                <?php if( $this->ion_auth->logged_in() && !$this->ion_auth->is_seller() && !$this->ion_auth->is_delivery_boy() && !$this->ion_auth->is_admin()) { ?>
                    <a href="<?= base_url('my-account/favorites') ?>" class="nav-link" aria-label="favorites">
                        <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/love.png') ?>">
                    </a>
                <?php }
                else { ?>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin" class="nav-link" aria-label="favorites">
                        <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/love.png') ?>">
                    </a>
                <?php } ?>
                <p class="icon-num"><?= (count(get_favorites($this->session->userdata('user_id'))) != 0 ? count(get_favorites($this->session->userdata('user_id'))) : '0'); ?></p>
            </li>
            
            <!-- checkout/cart icon functionality based on whether user is on checkout page already or not -->
            <?php $page = $this->uri->segment(2) == 'checkout' ? 'checkout' : '' ?>
            <li class="icon">
                <?php if ($page == 'checkout') { ?>
                    <a href="<?= base_url('cart') ?>">
                <?php }
                else { ?>
                    <a href="<?= base_url('cart') ?>">
                    <!-- <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-cart"> -->
                <?php } ?>
                    <!-- <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/cart-icon.png') ?>"> -->
                    <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/shopping-bag.png') ?>">
                </a>

                <?php 
                    $cartCount = count($this->cart_model->get_user_cart($this->session->userdata('user_id')));
                ?>
                <p id="cart-count" class="icon-num"><?= ($cartCount != "0" && $cartCount != "" ? $cartCount : '0'); ?></p>
            </li>

            <li class="icon">
                <!-- <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/bell-icon.png') ?>"> -->
                <img class="icon-img" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/bell.png') ?>">
                <p class="icon-num">0</p>
            </li>
        </ul>
    </section>
    <!-- header ends -->

    
    <!-- navbar starts -->
    <section class="navbar-container">
        
        <!-- A div to prevent mega menu from closing when hovered just below the navbar (to reach the mega menu from the nav button without it closing) -->
        <!-- <div id="megamenu-hover-net"></div> -->

        <ul class="navbar-list">
            <!-- <li class="nav-btn active-nav-btn">Home</li> -->

            <!-- Shop All button -->
            <li class="nav-btn nav-mega-btn" onclick="toggleMegaMenu(this)">
                <a href="<?= base_url('products') ?>" class="text-decoration-none"> Shop All </a>
            </li>

             <!-- Change top-level category buttons to mega menu buttons -->
            <?php foreach ($categories as $key => $row) { ?>
                <li class="nav-btn nav-mega-btn" onclick="toggleMegaMenu(this)">
                    <a href="<?= base_url('products/category/' . html_escape($row['slug'])) ?>" class="text-decoration-none">
                        <?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])) ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
         <!-- Mega menus outside the <ul> element -->
        <div class="mega-menus">
            <!--  Mega menu for 'Shop All' button at index 0 -->

            <?php generateMegaMenu($categories, 5); ?>

            <!-- Mega menu for each top-level category -->
            <?php foreach ($categories as $key => $row) { ?>

                <?php generateMegaMenu($row['children'], 3); ?>

            <?php } ?>
        </div>
    </section>
    <!-- navbar ends -->


    <!-- header for mobile starts -->
    <section class="mobile-header-container">
        <div class="mobile-header">
            <!-- main logo -->
            <?php $logo = get_settings('web_logo'); ?>
            <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="main-logo-m" alt="site-logo image"></a>
            
            <div class="flex-1"></div>
            <ul class="icon-container-m">
                
                <!-- display profile icon depending on whether the user is logged in or not -->
                <?php if( $this->ion_auth->logged_in() && !$this->ion_auth->is_seller() && !$this->ion_auth->is_delivery_boy() && !$this->ion_auth->is_admin()) { ?>
                    <li class="nav-item dropdown active">
                        <a class="text-decoration-none" data-toggle="dropdown" href="#"><i class="uil uil-user"></i>
                            <span class="fs-16">
                                <?= (isset($user->username) && !empty($user->username)) ? "Hello " . $user->username  : 'Guest' ?>
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
                    <li class="icon-m">
                        <!-- <a href="#" data-toggle="dropdown"> -->
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modal-signin">
                            <!-- <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/profile-icon.png') ?>"> -->
                            <!-- <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/profile-icon-2.png') ?>"> -->
                            <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/user.png') ?>">
                        </a>

                        <!-- <div class="dropdown-menu dropdown-menu-lg">
                            <a href="#" class="dropdown-item text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-signin"> <?= !empty($this->lang->line('login')) ? $this->lang->line('login') : 'Login' ?> </a>
                            <a href="#" class="dropdown-item text-decoration-none" data-bs-toggle="modal" data-bs-target="#modal-signup"> <?= !empty($this->lang->line('register')) ? $this->lang->line('register') : 'Register' ?> </a>
                        </div> -->
                    </li>
                <?php } ?>

                <li class="icon">
                    <a href="<?= base_url('my-account/favorites') ?>" class="nav-link" aria-label="favorites">
                        <!-- <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/heart-icon.png') ?>"> -->
                        <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/love.png') ?>">
                    </a>
                    <p class="icon-num-m"><?= (count(get_favorites($this->session->userdata('user_id'))) != 0 ? count(get_favorites($this->session->userdata('user_id'))) : '0'); ?></p>
                </li>

                <!-- checkout/cart icon functionality based on whether user is on checkout page already or not -->
                <?php $page = $this->uri->segment(2) == 'checkout' ? 'checkout' : '' ?>
                <?php if ($page == 'checkout') { ?>
                    <li class="icon">
                        <a href="<?= base_url('cart') ?>">
                            <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/shopping-bag.png') ?>">
                        </a>
                        <p class="icon-num-m"><?= (count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) != 0 ? count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) : '0'); ?></p>
                    </li>
                <?php } else { ?>
                    <li class="icon">
                        <a href="<?= base_url('cart') ?>">
                        <!-- <a href="javascript:void(0);" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-cart"> -->
                            <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/shopping-bag.png') ?>">
                        </a>
                        <p class="icon-num-m"><?= (count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) != 0 ? count($this->cart_model->get_user_cart($this->session->userdata('user_id'))) : '0'); ?></p>
                    </li>
                <?php } ?>


                <li class="icon">
                    <!-- <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/bell-icon.png') ?>"> -->
                    <img class="icon-img-m" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/bell.png') ?>">
                    <p class="icon-num-m">0</p>
                </li>

                <!-- <li class="icon" onclick="openSideMenuFn()">
                    <img class="icon-img-m menu-icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/mobile-menu.png') ?>">
                </li> -->

                <!-- the class offcanvas-nav-btn is the only thing needed to toggle the original style sidebar on click -->
                <li class="nav-item d-lg-none">
                    <button class="btn btn-link btn-sm fs-20 text-body mr-2 offcanvas-nav-btn p-0 text-decoration-none uil uil-bars"><span></span></button>
                </li>

            </ul>
        </div>

        <!-- search -->
        <div class="search-container-m">
            <input class="search_field" type="text" placeholder="Search">
            <!-- <input class="input" type="text" placeholder="Search"> -->
            <img class="search-icon-m" alt="search icon" src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/search-icon.png') ?>" onclick="searchProduct()">
        </div>
        <div id="append_mobile_search"></div>

    </section>
    <!-- header for mobile ends -->


    <!-- <?php foreach ($categories as $key => $row) { ?>
        <div class="swiper-slide swiper-slide-category">
            <a href="<?= base_url('products/category/' . html_escape($row['slug'])) ?>" class="text-decoration-none">
                <img class="lazy" src="<?= base_url('assets/front_end/modern/img/product-placeholder.jpg') ?>" data-src="<?= $row['image'] ?>" alt="<?= html_escape($row['name']) ?>" />
                <h6 class="fs-14 mb-0"><?= html_escape($row['name']) ?></h6>
            </a>
        </div>
    <?php } ?> -->

    <!-- side menu starts (for mobile) -->
    <section id="sideMenu" class="side-menu-container d-none">
        <div class="menu-header">
            <div class="flex-1">
                <?php $logo = get_settings('web_logo'); ?>
                <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="brand-logo-link h-10" alt="site-logo image"></a>
            </div>
            <button type="button" class="btn-close" onclick="closeSideMenuFn()"></button>
        </div>
        <ul class="navbar-list-m">
            
            <!-- <li class="nav-btn-m">Home</li> -->

            <li class="nav-btn-m">
                <select class="nav-btn-select-m">
                    <option value="null">Shop All</option>
                    <option value="">option 1</option>
                    <option value="">option 2</option>
                    <option value="">option 3</option>
                </select>
            </li>

            <?php foreach ($categories as $key => $row) { ?>
                <li class="nav-btn-m">
                    <a href="<?= base_url('products/category/' . html_escape($row['slug'])) ?>" class="text-decoration-none">
                        <h6 class="fs-14 mb-0"><?= html_escape($row['name']) ?></h6>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </section>
    <!-- side menu ends -->


    <nav class="navbar navbar-expand-lg center-nav transparent navbar-light">
        <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start">
            <div class="offcanvas-header d-lg-none">
                <?php $logo = get_settings('web_logo'); ?>
                <a href="<?= base_url() ?>"><img src="<?= base_url($logo) ?>" data-src="<?= base_url($logo) ?>" class="brand-logo-link h-10" alt="site-logo image"></a>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            
            <!-- <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100"> -->
            <div class="offcanvas-body d-lg-none ms-lg-auto d-flex flex-column h-100">
                <ul class="navbar-nav">

                    <li class="nav-item">
                        <a class="nav-link <?= ($current_url == base_url('')) ? 'active' : '' ?>" aria-current="page" aria-label="home" href="<?= base_url() ?>"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a>
                    </li>

                    <?php foreach ($categories as $key => $row) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('products/category/' . html_escape($row['slug'])) ?>" >
                                <?= html_escape($row['name']) ?>
                                <!-- <h6 class="fs-14 mb-0"><?= html_escape($row['name']) ?></h6> -->
                            </a>
                        </li>
                    <?php } ?>

                </ul>
                <!-- /.offcanvas-footer -->
            </div>
            <!-- /.offcanvas-body -->
        </div>
    </nav>
    <!-- /.navbar -->
    


    
    <!-- <div class="offcanvas offcanvas-end bg-light" id="offcanvas-cart" data-bs-scroll="true" aria-modal="true" role="dialog">
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
                                <div class="d-flex flex-row gap-3 shopping-cart-item-main" title="<?= html_escape($items['name']) ?>">
                                    <figure class="rounded cart-img">
                                        <a href="<?= base_url('products/details/' . $items['product_slug']) ?>">
                                            <img src="<?= $items['product_variants'][0]['variant_image'] ?? base_url($items['image']) ?>" alt="<?= html_escape($items['name']) ?>" title="<?= html_escape($items['name']) ?>" style="object-fit: contain;">
                                        </a>
                                    </figure>
                                    <div class="w-100 cart-title">
                                        <a href="<?= base_url('products/details/' . $items['product_slug']) ?>">
                                            <h3 class="post-title fs-16 lh-xs mb-1 no-wrap" title="<?= html_escape($items['name']) ?>">
                                                <?= short_description_word_limit(strip_tags(output_escaping(str_replace('\r\n', '&#13;&#10;', $items['name']))), 35) ?> <?= (isset($check_current_stock_status['error'])  && $check_current_stock_status['error'] == TRUE) ? "<span class='badge badge-danger'>  Out of Stock </span>" :  "" ?>
                                            </h3>
                                        </a>

                                        <span>
                                            <?php if (!empty($items['product_variants'])) { ?>
                                                <?= str_replace(',', ' | ', $items['product_variants'][0]['variant_values']) ?>
                                            <?php } ?>
                                        </span>
                                        
                                        <p class="price"><ins>
                                            <span class="amount"><?= $settings['currency'] . ' ' . $price ?></span>
                                        </ins></p>

                                        <div class="product-pricing d-flex py-2 w-100">
                                            <div class="product-quantity product-sm-quantity">
                                                <input type="number" name="header_qty" class="form-control d-flex align-content-center h-9 w-14" value="<?= $items['qty'] ?>" data-id="<?= $items['product_variant_id'] ?>" data-price="<?= $price ?>" min="<?= $items['minimum_order_quantity'] ?>" max="<?= $items['total_allowed_quantity'] ?>" step="<?= $items['quantity_step_size'] ?>">
                                            </div>
                                            <div class="product-line-price align-self-center px-1 no-wrap"><?= $settings['currency'] . ' ' . number_format($items['qty'] * $price, 2) ?></div>
                                        </div>

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
                    <img src="<?= base_url('assets/front_end/cretzo/img/new/empty-cart(4).png') ?>" alt="Empty Cart" class="mt-16" />
                <?php } ?>
            <?php } else { ?>
                <h1 class="h4 text-center">
                    <?= !empty($this->lang->line('empty_cart_message')) ? $this->lang->line('empty_cart_message') : 'Your cart is empty' ?>
                </h1>
                <img src="<?= base_url('assets/front_end/cretzo/img/new/empty-cart(4).png') ?>" alt="Empty Cart" class="mt-16" />
            <?php } ?>

            <!-/.shopping-cart-item ->
            <!- /.shopping-cart->
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
        <!- /.offcanvas-footer->
        <!- /.offcanvas-body ->
    </div> -->

    <!-- <div class="offcanvas offcanvas-top bg-light" id="offcanvas-search" data-bs-scroll="true">
        <div class="container d-flex flex-row py-4">
            <!- <form class="w-100"> ->
            <select class="form-control rounded-0 search_product" type="text" aria-label="Search"></select>
            <!- </form> ->
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    </div> -->
    <!-- /.offcanvas -->

    <div id="bg-backdrop"></div>

</body>
<!-- header ends -->


<?php
    /* function generateDropdownItemsForCategories($categories) {
        $html = '';
        foreach ($categories as $category) {
            
            $hasChildren = !empty($category['children']);

            $html .= '<li class="dropdown-item">';
            $html .= '<a href="' . base_url('products/category/' . html_escape($category['slug'])) . '" class="text-decoration-none dropdown-nav-btn">';
            $html .= output_escaping(str_replace('\r\n', '&#13;&#10;', $category['name']));
            if ($hasChildren) {
                $html .= '<span class="dropdown-indicator">&#9658;</span>'; // Add an indicator for categories with subcategories
            }
            $html .= '</a>';
            if (!empty($category['children'])) {
                $html .= '<ul class="dropdown-menu dropdown-submenu">';
                $html .= generateDropdownItemsForCategories($category['children']); // Recursively call the function for sub-categories
                $html .= '</ul>';
            }
            $html .= '</li>';
        }
        return $html;
    } */
    function generateMegaMenuSubcategories($categories, $ignore_children = false, $child_depth = 0) {
        $html = '';
        foreach ($categories as $category) {
            
            $hasChildren = !empty($category['children']);
            $renderChildren = $hasChildren && !$ignore_children;

            // $html .= '<li class="text-n mega-list-item">';

            $margin_left = $child_depth * 8; // Calculate margin-left based on child_depth
            $html .= '<li class="text-n mega-list-item" style="margin-left: ' . $margin_left . 'px;">'; // Add margin-left
            
            $html .= '<a href="' . base_url('products/category/' . html_escape($category['slug'])) . '" class="text-decoration-none">';
            $html .= output_escaping(str_replace('\r\n', '&#13;&#10;', $category['name']));
            if ($renderChildren) {
                $html .= '<span class="dropdown-indicator">&#9658;</span>'; // Add an indicator for categories with subcategories
            }
            $html .= '</a>';
            if ($renderChildren) {
                $html .= generateMegaMenuSubcategories($category['children'], false, $child_depth + 1); // Recursively call the function for sub-categories
            }
            $html .= '</li>';
        }
        return $html;
    }

    function generateMegaMenu($categories, $noOfRows) {
        $totalCategories = count($categories);
        $categoriesPerRow = ceil($totalCategories / $noOfRows);
        
        $chunks = array_chunk($categories, $categoriesPerRow);
        
        echo '<div class="mega-menu">';
        foreach ($chunks as $chunk) {
            $chunkCount = count($chunk);
            $i = 0;

            echo '<div class="mega-list-container">';
            foreach ($chunk as $row) {
                $i++;
                echo '<ul class="list mega-list' . ($i == $chunkCount ? ' last' : '') . '">';
                echo '<a href="' . base_url('products/category/' . html_escape($row['slug'])) . '" class="text-decoration-none">';
                echo '<lh class="text-n mega-list-item mega-list-item-heading">' . output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])) . '</lh>';
                echo '</a>';
                echo generateMegaMenuSubcategories($row['children']);
                echo '</ul>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

?>
