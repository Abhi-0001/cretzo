<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Subscription Details</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/subscription/manage_subscriptions') ?>">Subscriptions</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if (empty($plan)) : ?>
                <div class="alert alert-warning">Subscription plan not found.</div>
            <?php else : ?>
                <div class="card">
                    <div class="card-body" style="background:var(--bg-cream, #FFF9E6);padding:30px;border-radius:8px">
                        <style>
                            .cretzo-sub-grid { display:flex; gap:30px; align-items:flex-start; flex-wrap:wrap; }
                            .cretzo-left { flex:1; min-width:320px; }
                            .cretzo-right { width:420px; background:#fff; padding:24px; border-radius:12px; }
                            .cretzo-box { background:#fffdf6; padding:20px; border-radius:10px; text-align:left }
                            .payment-methods { background:#fffdf6; padding:20px; border-radius:10px; margin-top:10px }
                            .payment-methods .option { display:flex; align-items:flex-start; gap:10px; margin-bottom:14px }
                            .proceed-btn { background:#b8322e; color:#fff; border:none; padding:14px 22px; font-size:18px; border-radius:8px; font-weight:700 }
                            .change-plan { color:#F28C38; font-weight:700; float:right; text-decoration:none }
                            .accept-payments { text-align:center; margin-top:18px; color:#666; font-size:14px }
                            .accept-logos { display:flex; gap:10px; justify-content:center; align-items:center; margin-top:8px }
                            .accept-logos img { height:50px }
                        </style>

                        <style>
                            .cretzo-progress { display:flex; align-items:center; justify-content:center; gap:12px; margin-bottom:18px; font-size:20px }
                            .cretzo-progress .step { color:#111; font-weight:600; padding:0 6px; }
                            .cretzo-progress .step.active { color:#F28C38; font-weight:800; }
                            .cretzo-progress .connector { width:160px; border:dashed 1.5px #222; }
                            .cretzo-progress .connector.orange { background: repeating-linear-gradient(90deg, #F28C38 0 12px, transparent 12px 24px); }
                            .cretzo-progress .connector.dark { background: repeating-linear-gradient(90deg, #222 0 12px, transparent 12px 24px); }
                            @media(max-width:800px){ .cretzo-progress .connector{ width:80px } }
                        </style>

                        <div class="cretzo-progress" aria-hidden="true">
                            <div class="step">Choose Plan</div>
                            <div class="connector orange" aria-hidden="true"></div>
                            <div class="step active">Payment</div>
                            <div class="connector dark" aria-hidden="true"></div>
                            <div class="step">Confirmation</div>
                        </div>

                        <div class="cretzo-sub-grid">
                            <div class="cretzo-left">
                                <h1 style="font-size:30px;margin:0;color:#b02b2b">Complete Your<br/>Subscription</h1>
                                <p style="margin-top:12px;font-size:18px;color:#333">Choose Your Payment Method</p>

                                <div class="payment-methods cretzo-box">
                                    <div class="option"> <div>Credit Card / Debit Card</div></div>
                                    <div class="option"> <div>Net Banking</div></div>
                                    <div class="option"> <div>Pay by any UPI App<br/><small style="color:#666">Google Pay, PhonePe, Paytm and more</small></div></div>
                                </div>

                                <div style="margin-top:20px">
                                    <button id="proceed-pay-btn" class="proceed-btn">Proceed to Pay ₹<?= html_escape($plan['price']); ?>/-</button>
                                </div>

                                
                                
                            </div>

                            <div class="cretzo-right">
                                <div style="display:flex;align-items:center;justify-content:space-between">
                                    <div><strong>You selected:</strong></div>
                                </div>
                                <h2 style="color:var(--orange, #F28C38);margin-top:8px"><?= html_escape($plan['name']); ?></h2>
                                <div style="font-size:20px;font-weight:700;margin-top:8px">₹<?= html_escape($plan['price']); ?> / <?= html_escape($plan['validity']); ?></div>
                                <div style="margin-top:14px;text-align:left">
                                    <p><strong>Validity :</strong> <?= html_escape($plan['validity']); ?></p>
                                    <p><strong>Access :</strong> <?= !empty($plan['listings_limit']) ? intval($plan['listings_limit']) . ' extra listings' : 'Unlimited'; ?></p>
                                    <p><strong>Support :</strong> 24*7</p>
                                </div>
                                <div style="margin-top:6px"><strong>Commission</strong>
                                    <ul style="text-align:left;margin-top:6px">
                                        <li> <?= html_escape($plan['commission_first50']); ?>% (First 50 Orders)</li>
                                        <li> <?= html_escape($plan['commission_51_100']); ?>% (51-100 Orders)</li>
                                        <li> <?= html_escape($plan['commission_after100']); ?>% (Above 100 Orders)</li>
                                    </ul>
                                </div>
                                <div style="margin-top:18px;font-size:22px;color:#b8322e;font-weight:800">₹<?= html_escape($plan['price']); ?> / Year <a class="change-plan" href="<?= base_url('seller/subscription/manage_subscriptions'); ?>">Change plan</a></div>
                                

                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="accept-payments">
                                    <div>We Accept Payments Via</div>
                                    <div class="accept-logos">
                                        <img src="<?= base_url('assets/payment-all-image.png'); ?>" alt="payment methods">
                                    </div>
                                </div>
    </section>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function () {
        var planId = <?= isset($plan['id']) ? (int)$plan['id'] : 0; ?>;
        var $btn = document.getElementById('proceed-pay-btn');
        if (!$btn) return;
        $btn.addEventListener('click', function () {
            $btn.disabled = true;
            $btn.textContent = 'Please wait...';

            var data = {};
            data['subscription_id'] = planId;
            data[csrfName] = csrfHash;

            fetch(base_url + 'seller/subscription/purchase', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            }).then(function (res) { return res.json(); }).then(function (result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName; csrfHash = result.csrfHash;
                }
                if (result.error === false && result.requires_payment) {
                    // open Razorpay
                    var options = {
                        key: result.razorpay_key_id,
                        amount: Math.round(parseFloat(result.amount) * 100),
                        currency: result.currency || 'INR',
                        name: result.plan_name || 'Subscription',
                        description: 'Seller Subscription',
                        order_id: result.razorpay_order_id,
                        handler: function (response) {
                            var postData = {};
                            postData['subscription_id'] = planId;
                            postData['razorpay_order_id'] = response.razorpay_order_id;
                            postData['razorpay_payment_id'] = response.razorpay_payment_id;
                            postData['razorpay_signature'] = response.razorpay_signature;
                            postData[csrfName] = csrfHash;

                            fetch(base_url + 'seller/subscription/razorpay_callback', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams(postData)
                            }).then(function (r) { return r.json(); }).then(function (verify) {
                                if (verify.csrfName && verify.csrfHash) { csrfName = verify.csrfName; csrfHash = verify.csrfHash; }
                                if (verify.error === false) {
                                    window.location.href = base_url + 'seller/subscription/payment_success?subscription_id=' + planId + '&payment_id=' + response.razorpay_payment_id;
                                } else {
                                    alert(verify.message || 'Payment verification failed');
                                    $btn.disabled = false; $btn.textContent = 'Proceed to Pay';
                                }
                            }).catch(function () { alert('Verification request failed'); $btn.disabled = false; $btn.textContent = 'Proceed to Pay'; });
                        }
                    };
                    if (result.seller_name) options.prefill = { name: result.seller_name, email: result.seller_email, contact: result.seller_contact };
                    var rzp = new Razorpay(options);
                    rzp.on('payment.failed', function () { alert('Payment failed or cancelled'); $btn.disabled = false; $btn.textContent = 'Proceed to Pay'; });
                    rzp.open();
                } else if (result.error === false && !result.requires_payment) {
                    // free plan activated
                    window.location.href = base_url + 'seller/subscription/payment_success?subscription_id=' + planId + '&payment_id=free';
                } else {
                    alert(result.message || 'Unable to create order'); $btn.disabled = false; $btn.textContent = 'Proceed to Pay';
                }
            }).catch(function () { alert('Request failed'); $btn.disabled = false; $btn.textContent = 'Proceed to Pay'; });
        });
    })();
</script>
