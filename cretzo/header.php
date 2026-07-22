<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cretzo</title>
    <link rel="stylesheet" href="./assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- font -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <!-- owl cursor -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <style>
       a:link {
  text-decoration: none;
}

    </style>
</head>
<header>
    <div class="container-fluid D-none">
        <div class="row justify-content-center py-1">
            <div class="col-lg-2 col-12 text-center">
                <a href="index.php"><img src="./assets//img/logo.png" class="img-fluid" alt=""></a>
            </div>
            <div class="col-lg-8 col-12 text-end">
                <div class="input-group mt-3">
                    <input type="text" class="form-control btn-circle py-2" placeholder="Search" aria-describedby="basic-addon2">
                    <span class="input-group-text search-button" id="basic-addon2"><i class="bi bi-search"></i></span>
                </div>
            </div>
            <div class="col-lg-2 mt-3 fs-4 text-end">
                <span class="">
                    <span class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <a href="dashboard.php" class="mx-2"><i class="bi bi-person-circle fs-3"></i></a>
                    </span>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">Profile</a></li>
                        <li><a class="dropdown-item" href="order.php">Order</a></li>
                        <li><a class="dropdown-item" href="#">My Booking</a></li>
                        <li><a class="dropdown-item" href="#">Logout</a></li>
                    </ul>
                </span>
                <span class="position-relative mx-2">
                    <a href="wishlist.php" title="wishlist" ><i class="bi bi-heart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle theme-b-color" style="font-size:12px;">
                            9

                        </span></a>
                </span>
                <span class="position-relative mx-2">
                    <a href="addToCart.php" title="add to cart"><i class="bi bi-handbag"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle theme-b-color" style="font-size:12px;">
                            9

                        </span></a>
                </span>
                <span class="position-relative mx-2">
                <a href="addToCart.php" title="Notification">  <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle theme-b-color" style="font-size:12px;">
                        9

                    </span></a>
                </span>
            </div>
        </div>
    </div>
</header>
<!-- navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <div class="content-center">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class=""><a href="ndex.php"><img src="./assets//img/logo.png" class="d-lg-none" width="100" height="auto" style="margin-bottom:8px;" alt=""></a></span>
        </div>
        <span class="navbar-brand d-lg-none">
            <span class="position-relative mx-2 bg-none">
                <i class="bi bi-heart"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle theme-b-color" style="font-size:12px;">
                    9
                    <span class="visually-hidden">unread messages</span>
                </span>
            </span>
            <span class="position-relative mx-2">
                <i class="bi bi-handbag"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle theme-b-color" style="font-size:12px;">
                    9
                    <span class="visually-hidden">unread messages</span>
                </span>
            </span>
            <span class="position-relative mx-2">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle theme-b-color" style="font-size:12px;">
                    9
                    <span class="visually-hidden">unread messages</span>
                </span>
            </span>
        </span>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        JEWELLERY AND ACCESSORIES
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="Product.php">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        FOOTWEAR AND BAGS
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        HOME AND LIVING
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        CLOTHING
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        BEAUTY
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        ART AND COLLECTION
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        SNACKS AND FOOD
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        CRAFT AND SUPPLIES
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        GIFT GUIDE
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="#">Rings</a></li>
                        <li><a class="dropdown-item" href="#">Nackless</a></li>
                        <li><a class="dropdown-item" href="#">Category</a></li>
                    </ul>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="Bestsellers.php">BESTSELLERS</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="Product.php">EARRINGS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Pendants.php">PENDANTS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Rings.php">RINGS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Home&Living">HOME & LIVING</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Clothing.php">CLOTHING</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Jewellery.php">JEWELLERY</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Pickle.php">PICKLE</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="beautyProducts.php">BEAUTY PRODUCT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Art&Craft.php">ART & CRAFT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="CraftSupplies.php">CRAFT SUPPLIES</a>
                </li> -->
            </ul>
        </div>
        <div class="input-group d-lg-none my-1">
            <input type="text" class="form-control btn-circle bg-white" placeholder="Search" aria-describedby="basic-addon2">
            <span class="input-group-text search-button bg-white" id="basic-addon2"><i class="bi bi-search"></i></span>
        </div>
    </div>
</nav>

<body>