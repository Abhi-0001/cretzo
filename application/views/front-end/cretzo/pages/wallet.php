<div class="overview-side-container">
    <h1 class="heading-b">Account</h1>
    <p class="text-n"><?= $users->username ?></p>
    <div class="overview-container">

        <?php $this->load->view('front-end/' . THEME . '/partials/my-account-sidebar', ['active_menu' => $main_page]); ?>
        
        <div class="overview-right">
            <div class=' border-0'>
                <div class="card-header bg-white">
                    <div class="row">
                        <h1 class="col-6 h4"><?= !empty($this->lang->line('wallet')) ? $this->lang->line('wallet') : 'Wallet' ?></h1>
                    </div>
                </div>
                <hr class="mt-0 mb-5">
                <div class="d-flex justify-content-center gap-8">
                    <div class="col-md-4 row">
                        <div class="card col-md-12">
                            <div class="row d-flex justify-content-center">
                                <div class="d-flex justify-content-center">
                                    <!-- <img src="<?= base_url("assets/front_end/modern/img/new/wallet.png") ?>" alt="wallet-image" height="80px" width="100px" /> -->
                                    <img class="mt-2" src="<?= base_url("assets/front_end/cretzo/img/new_cretzo/wallet.png") ?>" alt="wallet-image" height="60px" />
                                </div>
                                <div class="h4 d-flex  mt-3  justify-content-center">
                                    <?= !empty($this->lang->line('balance')) ? $this->lang->line('balance') : 'Balance' ?>
                                </div>
                                <div class="h4 d-flex justify-content-center  price">
                                    <p class="h4"><?= $settings['currency'] . ' ' . $user->balance ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 row">
                        <div class="card col-md-12">
                            <div class="card-body bg-transparent">
                                <div class="h4 d-flex justify-content-center mt-2">
                                    <a href="#" class="cretzo btn btn-dark btn-wallet-action" data-bs-toggle="modal" data-bs-target="#myModal"><i class="fa fa-plus-circle mr-2"></i>Add money</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="col-md-4 row">
                        <div class="card col-md-12">
                            <div class="card-body bg-transparent">
                                <div class="h4 d-flex justify-content-center mt-2">
                                    <a href="#" class="cretzo btn btn-dark btn-wallet-action" data-bs-toggle="modal" data-bs-target="#withdraw"><i class="fa fa-minus-circle mr-2"></i>Withdraw money</a>
                                </div>
                            </div>
                        </div>
                    </div> -->


                    <div class="modal" id="myModal">
                        <div class="modal-dialog">
                            <div class="modal-content card">

                                <!-- Modal Header -->
                                <div class="modal-header">
                                    <h4 class="modal-title price">Add money to wallet</h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <!-- Modal body -->
                                <div class="ship-details-wrapper mt-3 payment-methods">
                                    <form class="form-horizontal form-submit-event" method="POST" id="wallet_form" enctype="multipart/form-data">
                                        <?php
                                        $CUR_USERID = $_SESSION['user_id'];
                                        $order_id = 'wallet-refill-user' . "-" . $CUR_USERID . "-" . time() . "-" . rand("900", "999");


                                        $payment_methods = get_settings('payment_method', true);
                                        $name = fetch_details('users', ['id' => $_SESSION['user_id']]);


                                        ?>

                                        <input type="hidden" name="app_name" id="app_name" value="<?= $settings['app_name'] ?>" />
                                        <input type="hidden" id="flutterwave_currency" value="<?= $payment_methods['flutterwave_currency_code'] ?>" />
                                        <input type="hidden" id="user_email" value="<?= $_SESSION['email']  ?>" />

                                        <input type="hidden" id="username" value="<?= $username['username'] ?>" />
                                        <input type="hidden" id="user_contact" value="<?= $username['mobile'] ?>" />
                                        <input type="hidden" name="logo" id="logo" value="<?= base_url(get_settings('web_logo')) ?>" />


                                        <input type="hidden" name="order_id" id="order_id" value="<?= $order_id ?>">
                                        <input type="number" name="amount" id="amount" min="1" required class="col-md-11 ml-4 form-control" placeholder="Enter amount">


                                        <input type="text" name="message" class="col-md-11 ml-4 mt-3 ticket_msg form-control " id="message_input" placeholder="Type Message ...">
                                        <br>


                                        <div class="align-items-start d-flex flex-column pl-4">
                                            <div class="ship-title-detail">
                                                <h5><?= !empty($this->lang->line('payment_method')) ? $this->lang->line('payment_method') : 'Payment Method' ?></h5>
                                            </div>
                                            <div class="shipped-details">
                                                <table class="table table-step-shipping">
                                                    <tbody>

                                                        <?php if (isset($payment_methods['razorpay_payment_method']) && $payment_methods['razorpay_payment_method'] == 1) { ?>
                                                            <tr>
                                                                <td style="border: none;">
                                                                    <label for="razorpay">
                                                                        <input id="razorpay" class="form-check-input m-0" name="payment_method" type="radio" value="Razorpay" required>
                                                                    </label>
                                                                </td>
                                                                <td style="border: none;">
                                                                    <label for="razorpay">
                                                                        <img src="<?= THEME_ASSETS_URL . 'img/payments/razorpay.png' ?>" class="payment-gateway-images" alt="Razorpay" style="height: 30px;">
                                                                    </label>
                                                                </td>
                                                                <td style="border: none;">
                                                                    <label for="razorpay">
                                                                        RazorPay
                                                                    </label>
                                                                    <input type="hidden" class="form-check-input m-0" name="razorpay_order_id" id="razorpay_order_id" value="" />
                                                                    <input type="hidden" class="form-check-input m-0" name="razorpay_payment_id" id="razorpay_payment_id" value="" />
                                                                    <input type="hidden" class="form-check-input m-0" name="razorpay_signature" id="razorpay_signature" value="" />
                                                                </td>
                                                            </tr>
                                                        <?php } ?>

                                                    </tbody>
                                                </table>
                                                <div id="stripe_div">
                                                    <div id="stripe-card-element">
                                                        <!--Stripe.js injects the Card Element-->
                                                    </div>
                                                    <p id="card-error" role="alert"></p>
                                                    <p class="result-message hidden"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer p-0 p-5">
                                            <button type="submit" class="cretzo btn btn-dark" id="wallet_refill" value="Save"><?= labels('Refill', 'Refill') ?></button>
                                            <button type="button" class="cretzo btn btn-dark btn-danger" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </form>
                                    <!-- </form> -->
                                </div>
                                <!-- Modal footer -->
                            </div>
                        </div>
                    </div>

                </div>
                <hr class="mt-5 mb-5">
                <div class="card-body">
                    <table class='' data-toggle="table" data-url="<?= base_url('my-account/get-wallet-transactions') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="customer_wallet_query_paramss" id="customer_wallet_query_paramss">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="id" data-sortable="true"><?= !empty($this->lang->line('id')) ? $this->lang->line('id') : 'ID' ?></th>
                                <th data-field="name" data-sortable="false"><?= !empty($this->lang->line('username')) ? $this->lang->line('username') : 'Username' ?></th>
                                <th data-field="type" data-sortable="false"><?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Type' ?></th>
                                <th data-field="amount" data-sortable="false"><?= !empty($this->lang->line('amount')) ? $this->lang->line('amount') : 'Amount' ?></th>
                                <th data-field="status" data-sortable="false"><?= !empty($this->lang->line('status')) ? $this->lang->line('status') : 'Status' ?></th>
                                <th data-field="message" data-sortable="false"><?= !empty($this->lang->line('message')) ? $this->lang->line('message') : 'Message' ?></th>
                                <th data-field="date" data-sortable="false"><?= !empty($this->lang->line('date')) ? $this->lang->line('date') : 'Date' ?></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="<?= base_url('payment/paypal_wallet') ?>" id="paypal_form" method="POST">
    <input type="hidden" id="csrf_token" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <input type="hidden" name="order_id" id="paypal_order_id" value="" />
    <input type="hidden" name="amount" id="paypal_amount" value="" />
</form>



<input type="hidden" name="razorpay_key_id" id="razorpay_key_id" value="<?= $payment_methods['razorpay_key_id'] ?>" />


<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script src="<?= THEME_ASSETS_URL . '/js/wallet.js' ?>"></script>