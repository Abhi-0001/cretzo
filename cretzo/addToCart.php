<?php include 'header.php'; ?>
<div class="container-fluid">
    <div class="row justify-content-center">

        <div class="col-lg-12 text-center my-2">
            <h3>Your Cart</h3>
            <!-- <span style="font-size:small;">Featured products to Lorem Ipsum</span> -->
            <div class="text-center">
                <img src="./assets//img//arrow.png" class="img-fluid" alt="">
            </div>
        </div>
        <!-- <div class="col-lg-12 my-3 bg-light py-3 borderTop">
            <p class="aTag my-2">Have A Coupon? <a href="" class="aTag active">Click To Enter Your Code</a></p>
        </div> -->
        <div class="col-lg-8 my-3">
            <div>
                <div class="row">
                    <div class="p-2 rounded-3 border-color my-2">
                        <h5>Available Offer</h5>
                        <div class="mx-2">Selected Offer</div>
                        <div class="accordion accordion-flush" id="accordionFlushExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="flush-headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                        Offers
                                    </button>
                                </h2>
                                <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">
                                        <div style="cursor:pointer"><b>offer one</b></div>
                                        <div style="cursor:pointer"><b>offer two</b></div>
                                        <div style="cursor:pointer"><b>offer three</b></div>
                                        <div style="cursor:pointer"><b>offer Four</b></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-3 content-center">
                        <img src="assets/img//Rectangle 247.png" class="rounded img-fluid" alt="">
                    </div>
                    <div class="col-lg-10 col-9 cartMobile">
                        <div class="row">
                            <div class="col-lg-10 col-12">
                                <span class="text-secondary">Brand Name</span> <br>
                                <span>Lorem ipsum Lorem Ipsum nsjnjs ncsjnc ncjsnc dszdcjdbj</span>
                            </div>
                            <div class="col-lg-2 col-12 text-sm-start text-lg-end">
                                <div class="">
                                    <div class="fs-5 active"><i class="bi bi-currency-rupee"></i><span>230</span></div>
                                    <div class="fs-6 text-secondary"><i class="bi bi-currency-rupee"></i><del>230</del></div>
                                </div>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-10 col-10 d-flex">
                                <div>
                                    <span class="qtyDiv py-2">
                                        <button class="btn" onclick="decreaseQty()">-</button>
                                        <span id="quantity">1</span>
                                        <button class="btn" onclick="increaseQty()">+</button>
                                    </span>
                                </div>
                                <div>
                                    <div class="mx-1">
                                        <select name="" id="" class="form-control size-select">
                                            <option value="">S</option>
                                            <option value="">M</option>
                                            <option value="">L</option>
                                            <option value="">XL</option>
                                            <option value="">XXL</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-2 text-end">
                                <div class="" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                    <i class="bi bi-archive fs-3"></i>
                                </div>

                            </div>
                        </div>


                    </div>
                    <div class="col-lg-12 d-lg-flex my-1">
                        <span style="font-size:small;" class="mx-2"><i class="bi bi-arrow-repeat"></i> 30 Days Easy Returns</span>
                    </div>
                    <div class="col-lg-12 d-lg-flex my-1">
                        <span style="font-size:small;" class="mx-2"><i class="bi bi-check-circle text-success"></i> delivery by <span class="fw-bold"> 30 jun</span></span>
                    </div>

                </div>
                <hr>
            </div>
            <div>
                <div class="row">
                    <div class="col-lg-2 col-3 content-center">
                        <img src="assets/img//Rectangle 247.png" class="rounded img-fluid" alt="">
                    </div>
                    <div class="col-lg-10 col-9 cartMobile">
                        <div class="row">
                            <div class="col-lg-10 col-12">
                                <span class="text-secondary">Brand Name</span> <br>
                                <span>Lorem ipsum Lorem Ipsum nsjnjs ncsjnc ncjsnc dszdcjdbj</span>
                            </div>
                            <div class="col-lg-2 col-12 text-sm-start text-lg-end">
                                <div class="">
                                    <div class="fs-5 active"><i class="bi bi-currency-rupee"></i><span>230</span></div>
                                    <div class="fs-6 text-secondary"><i class="bi bi-currency-rupee"></i><del>230</del></div>
                                </div>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-10 col-10 d-flex">
                                <div>
                                    <span class="qtyDiv py-2">
                                        <button class="btn" onclick="decreaseQty()">-</button>
                                        <span id="quantity">1</span>
                                        <button class="btn" onclick="increaseQty()">+</button>
                                    </span>
                                </div>
                                <div>
                                    <div class="mx-1">
                                        <select name="" id="" class="form-control size-select">
                                            <option value="">S</option>
                                            <option value="">M</option>
                                            <option value="">L</option>
                                            <option value="">XL</option>
                                            <option value="">XXL</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-2 text-end">
                                <div class="" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                    <i class="bi bi-archive fs-3"></i>
                                </div>

                            </div>
                        </div>


                    </div>
                    <div class="col-lg-12 d-lg-flex my-1">
                        <span style="font-size:small;" class="mx-2"><i class="bi bi-arrow-repeat"></i> 30 Days Easy Returns</span>
                    </div>
                    <div class="col-lg-12 d-lg-flex my-1">
                        <span style="font-size:small;" class="mx-2"><i class="bi bi-check-circle text-success"></i> delivery by <span class="fw-bold"> 30 jun</span></span>
                    </div>

                </div>
                <hr>
            </div>
        </div>
        <div class="col-lg-3 my-3">
            <div class="px-2">
                <h6>coupons</h6>
                <div class="d-flex justify-content-between">
                    <h6 class="fw-bold">Apply Coupons</h6>
                    <span class="border-color px-2  rounded-3" style="cursor:pointer">Apply</span>
                </div>
                <div class="my-2">
                    <input type="text" class="form-control input-custom" placeholder="Code">
                </div>
                <hr>
                <h5>Price Details (2 items)</h5>
                <div class="row px-2">
                    <div class="col-lg-8 col-8">
                        <span>Total MRP</span>
                    </div>
                    <div class="col-lg-4 col-4">
                        <span>Rs. 230</span>
                    </div>
                    <div class="col-lg-8 col-8">
                        <span>Discount on MRP</span>
                    </div>
                    <div class="col-lg-4 col-4">
                        <span class="text-success">-<i class="bi bi-currency-rupee"></i>230</span>
                    </div>
                    <div class="col-lg-7 col-8">
                        <span>Coupon Discount</span>
                    </div>
                    <div class="col-lg-5 col-4">
                        <span class="active" style="font-size: small;">Apply Coupon</span>
                    </div>
                </div>
                <div class="row px-2">
                    <div class="col-lg-8 col-8">
                        <p>Shipping</p>
                    </div>
                    <div class="col-lg-4 col-4">
                        <p>Rs. 270</p>
                    </div>
                    <hr>
                </div>
                <div class="row px-2">
                    <div class="col-lg-8 col-8">
                        <p>Total Amount</p>
                    </div>
                    <div class="col-lg-4 col-4">
                        <p class="active">Rs. 300</p>
                    </div>
                    <hr>
                </div>
            </div>

            <div class="p-2 rounded-3 border-color">
                <span>Amit Kumar</span> <span class="mx-1">+91 870028172</span>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Assumenda voluptatum !</p>
                <div class="d-flex justify-content-between">
                    <div>
                        <span><a href="" class="aTag active" style="font-size:small;">Add New Address</a></span>
                    </div>
                    <div>
                        <span><a href="" class="aTag active" style="font-size:small;">Edit</a></span>
                        <span class="mx-2" style="font-size:small;cursor:pointer">Remove</span>
                    </div>
                </div>
            </div>
            <div class="d-grid my-3">
                <button class="btnAddToCard"><a href="checkOutt.php" class="text-white aTag">Proceed Checkout</a></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-evenly">
                    <button type="button" class="btnAddToCard mx-2">Move to wishlist</button>
                    <button type="button" class="btnAddToCard mx-2" data-bs-dismiss="modal">Remove Item</button>
                </div>

            </div>
        </div>
    </div>
</div>
<style>
    .size-select {
        box-shadow: none !important;
        border: 1px solid grey !important;
        border-radius: 30px;
    }
    .accordion-flush{
        box-shadow: none !important;
    }
</style>
<?php include 'footer.php'; ?>