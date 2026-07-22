<?php include 'header.php'; ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 bg-white shadow" style="border-radius:30px">
            <div class="row">
                <div class="col-lg-8 content-center">
                    <div class="">
                        <h2 class="text-center">Signup</h2>
                        <div class="my-3">
                            <input type="text" class="form-control new-form" name="" placeholder="Enter your Name" id="">
                        </div>
                        <div class="my-3">
                            <input type="number" class="form-control new-form" name="" placeholder="Enter Your Phone Number" id="">
                        </div>
                        <div class="my-3">
                            <input type="email" class="form-control new-form" name="" placeholder="Enter Your Email" id="">
                        </div>
                        <div class="my-3">
                            <input type="password" class="form-control new-form" name="" placeholder="Enter Your Password" id="">
                        </div>
                        <div class="my-3">
                            <input type="password" class="form-control new-form" name="" placeholder="Re-Enter Your Password" id="">
                        </div>
                        <div class="my-3 d-flex">
                            <div>
                                <input type="checkbox"> <span style="font-size:small">Remember Me</span>
                            </div>
                           
                        </div>
                        <div class="my-3">
                            <div class="text-center">
                                <span class="bg-primary shadow-sm text-white p-2 rounded">
                                    <i class="bi bi-facebook text-white"></i> <span class="aTag"> Login with Facebook</span>
                                </span>
                            </div>
                            <div class="text-center my-3">
                                <span class="bg-white shadow-sm  p-2 rounded">
                                    <img src="assets/img/icons/Google.png"  alt=""> <span class="aTag"> Sign in With google</span>
                                </span>
                            </div>
                        </div>
                        <p>By Continuing, you agree to <span><a href="#" class="active">Terms of Use</a></span> and <span><a href="#" class="active">Privacy Policy</a></span> </p>

                        <div class="my-3 d-grid">
                            <button class="btnAddToCard"><a href="#" class="text-white aTag">Signup</a></button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 D-none">
                    <img src="assets/img/login.png" class="" style="border-top-right-radius:30px;border-bottom-right-radius:30px;" alt="">
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>