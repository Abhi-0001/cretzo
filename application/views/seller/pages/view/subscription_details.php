<div class="content-wrapper subscription-payment-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-credit-card mr-2 text-primary-theme"></i>Complete Your Subscription</h4>
                    <p class="text-muted mb-0 small">Review your plan and choose how you'd like to pay.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/subscription/manage_subscriptions') ?>">Subscriptions</a></li>
                        <li class="breadcrumb-item active">Payment</li>
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

                <div class="sub-progress mb-4">
                    <span class="sub-progress-step done">Choose Plan</span>
                    <span class="sub-progress-connector done"></span>
                    <span class="sub-progress-step active">Payment</span>
                    <span class="sub-progress-connector"></span>
                    <span class="sub-progress-step">Confirmation</span>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-3">
                        <div class="card attribute-card h-100">
                            <div class="card-header attribute-card-header">
                                <span class="header-icon bg-set"><i class="fas fa-wallet"></i></span>
                                <h5 class="mb-0">Choose Your Payment Method</h5>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <ul class="payment-option-list mb-4">
                                    <li><i class="fas fa-credit-card"></i>Credit Card / Debit Card</li>
                                    <li><i class="fas fa-university"></i>Net Banking</li>
                                    <li>
                                        <i class="fas fa-mobile-alt"></i>
                                        <div>
                                            Pay by any UPI App
                                            <small class="d-block text-muted">Google Pay, PhonePe, Paytm and more</small>
                                        </div>
                                    </li>
                                </ul>
                                <?php
                                /* Pay from the wallet.
                                 *
                                 * Whole fee only, so the option is offered when the balance
                                 * covers the amount payable and explained rather than hidden
                                 * when it does not - a seller carrying referral credit needs
                                 * to know why they cannot spend it here yet, and how much is
                                 * missing.
                                 *
                                 * The amount compared against is what is actually payable
                                 * after any proration credit, not the plan's sticker price. */
                                $czw_payable = isset($amount_payable) ? (float) $amount_payable : (float) $plan['price'];
                                $czw_balance = isset($wallet_balance) ? (float) $wallet_balance : 0;
                                $czw_referral = isset($referral_credit) ? (float) $referral_credit : 0;
                                $czw_can_pay = ($czw_payable > 0 && $czw_balance + 0.001 >= $czw_payable);
                                ?>
                                <?php if ($czw_payable > 0) { ?>
                                    <div class="wallet-pay-block border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><i class="fas fa-wallet mr-1"></i> Pay from wallet</strong>
                                                <div class="small text-muted">
                                                    Balance ₹<?= number_format($czw_balance, 2) ?>
                                                    <?php if ($czw_referral > 0) { ?>
                                                        &middot; includes ₹<?= number_format($czw_referral, 2) ?> referral credit
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <?php if ($czw_can_pay) { ?>
                                                <button id="pay-wallet-btn" class="btn btn-outline-success">
                                                    Pay ₹<?= number_format($czw_payable, 2) ?> from wallet
                                                </button>
                                            <?php } else { ?>
                                                <button class="btn btn-outline-secondary" disabled
                                                        title="A subscription has to be paid in full from the wallet">
                                                    ₹<?= number_format(max(0, $czw_payable - $czw_balance), 2) ?> short
                                                </button>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>

                                <div class="mt-auto">
                                    <button id="proceed-pay-btn" class="btn btn-primary-theme btn-lg">Proceed to Pay ₹<?= html_escape($plan['price']); ?>/-</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-3">
                        <div class="card attribute-card plan-summary-card h-100">
                            <div class="card-body">
                                <p class="text-muted small mb-1">You selected</p>
                                <h3 class="plan-name mb-2"><?= html_escape($plan['name']); ?></h3>
                                <div class="plan-price mb-3"><?= (is_numeric($plan['price']) && (float) $plan['price'] <= 0) ? 'Free' : '₹' . html_escape($plan['price']); ?> <span class="text-muted font-weight-normal">/ <?= html_escape(plan_validity_text($plan['validity'])); ?></span></div>

                                <ul class="plan-detail-list mb-3">
                                    <li><span>Validity</span><strong><?= html_escape(plan_validity_text($plan['validity'])); ?></strong></li>
                                    <li><span>Access</span><strong><?= html_escape(plan_listings_text(isset($plan['listings_limit']) ? $plan['listings_limit'] : '')); ?></strong></li>
                                    <li><span>Support</span><strong>24*7</strong></li>
                                </ul>

                                <p class="mb-2"><strong>Commission</strong></p>
                                <ul class="commission-list mb-4">
                                    <li><?= html_escape($plan['commission_first50']); ?>% <span>(First 50 Orders)</span></li>
                                    <li><?= html_escape($plan['commission_51_100']); ?>% <span>(51-100 Orders)</span></li>
                                    <li><?= html_escape($plan['commission_after100']); ?>% <span>(Above 100 Orders)</span></li>
                                </ul>

                                <div class="plan-total d-flex justify-content-between align-items-center">
                                    <span>₹<?= html_escape($plan['price']); ?> / Year</span>
                                    <a class="change-plan-link" href="<?= base_url('seller/subscription/manage_subscriptions'); ?>">Change plan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <div class="accept-payments">
                <div class="text-muted small mb-2">We Accept Payments Via</div>
                <div class="accept-logos">
                    <img src="<?= base_url('assets/payment-all-image.png'); ?>" alt="payment methods">
                </div>
            </div>
        </div>
    </section>
</div>

<?php
// Checkout approval gate. The seller sees the whole plan - price, inclusions, commission
// slabs - and only meets this popup when they click Proceed to Pay, which is the point at
// which an unapproved account must be stopped. seller/Subscription::purchase() enforces the
// same rule server-side.
$this->load->view('seller/include-approval-modals', ['approval_modal_mode' => 'checkout']);
?>

<style>
    .subscription-payment-page .text-primary-theme { color: var(--color-orange); }
    .subscription-payment-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 700;
        padding: 12px 26px;
        border-radius: 8px;
    }
    .subscription-payment-page .btn-primary-theme:hover,
    .subscription-payment-page .btn-primary-theme:disabled {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .subscription-payment-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .subscription-payment-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .subscription-payment-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px;
    }
    .subscription-payment-page .header-icon.bg-set { background: var(--color-orange); }

    .subscription-payment-page .sub-progress { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 6px 0; }
    .subscription-payment-page .sub-progress-step { font-weight: 600; color: var(--color-grey); font-size: 15px; }
    .subscription-payment-page .sub-progress-step.active { color: var(--color-orange); font-weight: 800; }
    .subscription-payment-page .sub-progress-step.done { color: #333; }
    .subscription-payment-page .sub-progress-connector { width: 90px; height: 0; border-top: 2px dashed rgba(0,0,0,0.15); }
    .subscription-payment-page .sub-progress-connector.done { border-top-color: var(--color-orange); }
    @media (max-width: 600px) { .subscription-payment-page .sub-progress-connector { width: 40px; } }

    .subscription-payment-page .payment-option-list { list-style: none; margin: 0; padding: 0; background: var(--color-secondary); border-radius: 10px; padding: 16px 18px; }
    .subscription-payment-page .payment-option-list li { display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; font-size: 15px; color: #333; }
    .subscription-payment-page .payment-option-list li + li { border-top: 1px solid rgba(0,0,0,0.06); }
    .subscription-payment-page .payment-option-list i { color: var(--color-orange); font-size: 16px; margin-top: 2px; width: 18px; text-align: center; }

    .subscription-payment-page .plan-summary-card { border: 1px solid rgba(242,130,46,0.25); }
    .subscription-payment-page .plan-name { color: var(--color-orange); font-weight: 700; }
    .subscription-payment-page .plan-price { font-size: 22px; font-weight: 800; color: #222; }
    .subscription-payment-page .plan-detail-list { list-style: none; margin: 0; padding: 0; }
    .subscription-payment-page .plan-detail-list li { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; color: var(--color-grey); border-bottom: 1px dashed rgba(0,0,0,0.08); }
    .subscription-payment-page .plan-detail-list li strong { color: #222; }
    .subscription-payment-page .commission-list { list-style: none; margin: 0; padding: 0; font-size: 14px; }
    .subscription-payment-page .commission-list li { padding: 3px 0; font-weight: 700; color: #222; }
    .subscription-payment-page .commission-list li span { font-weight: 400; color: var(--color-grey); }
    .subscription-payment-page .plan-total { border-top: 1px solid rgba(0,0,0,0.08); padding-top: 14px; font-size: 20px; font-weight: 800; color: var(--color-orange-dark); }
    .subscription-payment-page .change-plan-link { color: var(--color-orange); font-weight: 700; font-size: 14px; text-decoration: none; }
    .subscription-payment-page .change-plan-link:hover { color: var(--color-orange-dark); text-decoration: underline; }

    .subscription-payment-page .accept-payments { text-align: center; margin-top: 10px; }
    .subscription-payment-page .accept-logos img { height: 44px; }
</style>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function () {
        var planId = <?= isset($plan['id']) ? (int)$plan['id'] : 0; ?>;
        var $btn = document.getElementById('proceed-pay-btn');
        if (!$btn) return;
        var originalBtnText = $btn.textContent;

        function resetButton() {
            $btn.disabled = false;
            $btn.textContent = originalBtnText;
        }

        // If the seller navigates back to this page after cancelling (browser back
        // button), the browser can restore the exact DOM state from its cache —
        // including the disabled "Please wait..." button — instead of a fresh load.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) resetButton();
        });

        // Seller approval stage, from seller_approval_state(). Anything other than
        // 'approved' means no payment may be started - previously an unapproved seller could
        // pay for a plan before the admin had even looked at their profile.
        var approvalStage = <?= json_encode(isset($seller_approval['stage']) ? $seller_approval['stage'] : 'incomplete') ?>;

        // Paying from the wallet is the same request with one extra field, and the
        // same approval gate: an unapproved seller may not buy a plan with wallet
        // money either.
        var $walletBtn = document.getElementById('pay-wallet-btn');
        if ($walletBtn) {
            $walletBtn.addEventListener('click', function () {
                if (approvalStage !== 'approved' && typeof window.czShowApprovalGate === 'function' && window.czShowApprovalGate()) {
                    return;
                }

                $walletBtn.disabled = true;
                var walletText = $walletBtn.textContent;
                $walletBtn.textContent = 'Please wait...';

                var data = { subscription_id: planId, payment_method: 'wallet' };
                data[csrfName] = csrfHash;

                fetch(base_url + 'seller/subscription/purchase', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(data)
                }).then(function (res) { return res.json(); }).then(function (result) {
                    if (result.csrfName && result.csrfHash) {
                        csrfName = result.csrfName; csrfHash = result.csrfHash;
                    }

                    if (result.error) {
                        alert(result.message);
                        $walletBtn.disabled = false;
                        $walletBtn.textContent = walletText;
                        return;
                    }

                    alert(result.message);
                    window.location.href = base_url + 'seller/subscription/manage_subscriptions';
                }).catch(function () {
                    alert('Request failed');
                    $walletBtn.disabled = false;
                    $walletBtn.textContent = walletText;
                });
            });
        }

        $btn.addEventListener('click', function () {
            if (approvalStage !== 'approved' && typeof window.czShowApprovalGate === 'function' && window.czShowApprovalGate()) {
                return;
            }

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
                        modal: {
                            // Fires when the seller just closes/cancels the checkout
                            // popup without attempting a payment — "payment.failed"
                            // only fires for an actual declined/errored attempt, so
                            // without this the button was stuck on "Please wait..."
                            // until the whole page was reloaded.
                            ondismiss: resetButton
                        },
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
                                    resetButton();
                                }
                            }).catch(function () { alert('Verification request failed'); resetButton(); });
                        }
                    };
                    if (result.seller_name) options.prefill = { name: result.seller_name, email: result.seller_email, contact: result.seller_contact };
                    var rzp = new Razorpay(options);
                    rzp.on('payment.failed', function () { alert('Payment failed or cancelled'); resetButton(); });
                    rzp.open();
                } else if (result.error === false && !result.requires_payment) {
                    // free plan activated
                    window.location.href = base_url + 'seller/subscription/payment_success?subscription_id=' + planId + '&payment_id=free';
                } else if (result.approval_required) {
                    // The server refused on approval grounds (direct POST, or the seller's
                    // status changed while this page was open) - show the same popup rather
                    // than a bare alert, so the copy and the next step match.
                    resetButton();
                    if (!(typeof window.czShowApprovalGate === 'function' && window.czShowApprovalGate())) {
                        alert(result.message || 'Admin approval is required before subscribing.');
                    }
                } else {
                    alert(result.message || 'Unable to create order'); resetButton();
                }
            }).catch(function () { alert('Request failed'); resetButton(); });
        });
    })();
</script>
