<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Subscription Plans</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Subscriptions</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php
            $current_plan_id = null;
            $current_plan = null;

            if (!empty($active_subscription)) {
                $current_plan_id = $active_subscription['subscription_id'];
            } elseif (!empty($latest_subscription)) {
                $current_plan_id = $latest_subscription['subscription_id'];
            }

            if (!empty($plans) && $current_plan_id) {
                foreach ($plans as $p) {
                    if (isset($p['id']) && (int) $p['id'] === (int) $current_plan_id) {
                        $current_plan = $p;
                        break;
                    }
                }
            }
            ?>

            <style>
        :root {
            --bg-cream: #FFF9E6;
            --bg-accent: #FFE5CC;
            --orange: #F28C38;
            --card-yellow: #FEF4C1;
            --text: #333;
        }

        .subscription-page-body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-cream);
            color: var(--text);
            text-align: center;
        }

        .subscription-launch-banner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            max-width: 760px;
            margin: 10px auto 20px;
            padding: 14px 20px;
            border-radius: 14px;
            /* background: linear-gradient(135deg, var(--orange) 0%, #d96d1a 100%);
            color: #ffffff;
            box-shadow: 0 12px 26px -12px rgba(242, 140, 56, 0.8); */
            text-align: left;
            line-height: 1.35;
            background: linear-gradient(135deg, #fff3e3 0%, #ffe7cc 100%);
            border: 1px solid rgba(224, 122, 72, 0.28);
            color: var(--cz-ink);
            box-shadow: 0 8px 18px -12px rgba(224, 122, 72, 0.45);
        }
        .subscription-launch-banner .slb-icon { font-size: 60px; line-height: 1; flex-shrink: 0; }
        .subscription-launch-banner .slb-title { font-weight: bold; font-size: 23px; display: block; }
        .subscription-launch-banner .slb-sub { font-size: 22px; opacity: 0.95; }

        .subscription-header { padding: 10px 5px; }
        h1 { font-size: 32px; margin-bottom: 5px; }
        .subtitle { color: var(--orange); font-weight: 600; margin-bottom: 10px; }

        /* Plans Logic Styling */
        .subscription-plans-container {
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: 20px;
            flex-wrap: wrap;
            padding: 10px;
        }

        .subscription-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            background-color: #ffffff;
            border-radius: 16px;
            width: 210px;
            max-width: 100%;
            padding: 20px 15px;
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #f0e6cf;
            box-shadow: 0 6px 18px -12px rgba(120, 72, 20, 0.35);
        }

        .subscription-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px -16px rgba(120, 72, 20, 0.45);
        }

        /* Active Plan Highlight */
        .subscription-card.active {
            border-color: var(--orange);
            box-shadow: 0 10px 24px -14px rgba(242, 140, 56, 0.6);
        }

        .active-badge {
            display: none;
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--orange);
            color: white;
            padding: 2px 15px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: bold;
        }

        .subscription-card.active .active-badge { display: block; }

        .subscription-card h2 {
            color: var(--orange);
            margin: 0 0 6px;
            font-size: 22px;
        }
        .price {
            font-size: 28px;
            font-weight: 700;
            margin: 4px 0 14px;
            padding-bottom: 14px;
            width: 100%;
            border-bottom: 1px solid #f0e6cf;
        }
        .listings { font-weight: 600; font-size: 15px; }
        .validity { color: var(--cz-muted, #6b6b6b); font-size: 13.5px; margin-top: 2px; }

        .upgrade-btn {
            margin-top: 18px;
            width: 100%;
            background: var(--orange);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .upgrade-btn:hover:not(:disabled) { background: #d96d1a; }
        .upgrade-btn:disabled { background: #e2e2e2; color: #888; cursor: not-allowed; }

        /* Commission Section */
        .subscription-commission-sec {
            background-color: var(--bg-accent);
            padding: 60px 20px;
            margin-top: 40px;
        }

        .subscription-table-box {
            background: #fffcf2;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 10px;
            padding: 20px;
            text-align: left;
        }

        table { width: 100%; border-collapse: collapse; }
        th { border-bottom: 1px solid #ddd; padding: 10px; font-size: 14px; }
        td { padding: 12px 10px; }
        .text-right { text-align: right; }

        .subscription-know-more {
            background: var(--orange);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 20px;
            margin-top: 30px;
            cursor: pointer;
        }

        .features-list {
            margin-top: 15px;
            text-align: left;
            padding-left: 20px;
        }

        .features-list li {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .hidden-features {
            display: none;
        }

        .view-all-link {
            color: var(--orange);
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            display: inline-block;
            text-decoration: underline;
        }

        .view-all-link:hover {
            text-decoration: none;
        }
            </style>

            <div class="card">
                <div class="card-body subscription-page-body">
                    <style>
                        .cretzo-progress { display:flex; align-items:center; justify-content:center; gap:12px; margin-bottom:50px; font-size:20px }
                        .cretzo-progress .step { color:#111; font-weight:600; padding:0 6px; }
                        .cretzo-progress .step.active { color:#F28C38; font-weight:600; }
                        .cretzo-progress .connector { width:160px; border:dashed 1.5px #222; }
                        .cretzo-progress .connector.orange { background: repeating-linear-gradient(90deg, #F28C38 0 12px, transparent 12px 24px); }
                        .cretzo-progress .connector.dark { background: repeating-linear-gradient(90deg, #222 0 12px, transparent 12px 24px); }
                        @media(max-width:800px){ .cretzo-progress .connector{ width:80px } }
                    </style>

                    <div class="cretzo-progress" aria-hidden="true">
                        <div class="step active">Choose Plan</div>
                        <div class="connector orange" aria-hidden="true"></div>
                        <div class="step">Payment</div>
                        <div class="connector dark" aria-hidden="true"></div>
                        <div class="step">Confirmation</div>
                    </div>
                    <section class="subscription-header">
                        <?php if (!empty($launch_offer_active)) : ?>
                        <div class="subscription-launch-banner" role="note">
                            <span class="slb-icon" aria-hidden="true">&#127881;</span>
                            <span>
                                <span class="slb-title">Launch Offer</span>
                                <span class="slb-sub">First 100 vendors get 50 free listings for 1 year</span>
                            </span>
                        </div>
                        <?php endif; ?>
                        <h1>Subscription Plans</h1>
                        <p class="subtitle">Choose a plan that fits your creative journey</p>

                        <?php if (!empty($current_plan)) : ?>
                            <p><strong>Current Plan:</strong> <?= html_escape($current_plan['name']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($plans)) : ?>
                            <div class="subscription-plans-container">
                                <?php foreach ($plans as $plan) :
                                    $is_active = $current_plan_id && isset($plan['id']) && (int) $plan['id'] === (int) $current_plan_id;
                                    $is_launch_offer = isset($plan['name']) && strcasecmp(trim($plan['name']), 'Launch Offer') === 0;

                                    $price_raw = isset($plan['price']) ? trim($plan['price']) : '';
                                    $price_numeric = preg_replace('/[^\d.]/', '', $price_raw);
                                    $is_free = ($price_raw === '' || (is_numeric($price_numeric) && (float) $price_numeric <= 0));

                                    $listings_text = isset($plan['listings_limit']) && $plan['listings_limit'] !== '' ? 'Up to ' . $plan['listings_limit'] . ' Listings' : 'Unlimited Listings';

                                    $validity_raw = isset($plan['validity']) ? trim($plan['validity']) : '';
                                    if ($validity_raw === '') {
                                        $validity_text = 'Lifetime';
                                    } elseif (ctype_digit($validity_raw)) {
                                        $days = (int) $validity_raw;
                                        if ($days > 0 && $days % 365 === 0) {
                                            $years = $days / 365;
                                            $validity_text = $years . ' Year' . ($years > 1 ? 's' : '');
                                        } elseif ($days > 0 && $days % 30 === 0) {
                                            $months = $days / 30;
                                            $validity_text = $months . ' Month' . ($months > 1 ? 's' : '');
                                        } else {
                                            $validity_text = $days . ' Days';
                                        }
                                    } else {
                                        $validity_text = $validity_raw;
                                    }
                                ?>
                                    <div class="subscription-card <?= $is_active ? 'active' : '' ?>" id="plan-<?= (int) $plan['id']; ?>">
                                        <div class="active-badge">CURRENT PLAN</div>
                                        <h2><?= html_escape($plan['name']); ?></h2>
                                        <div class="price"><?= $is_free ? 'Free&#127881;' : '₹' . html_escape($price_raw); ?></div>
                                        <div class="listings"><?= html_escape($listings_text); ?></div>
                                        <div class="validity"><?= html_escape($validity_text); ?></div>
                                            <?php if ($is_active) : ?>
                                            <button class="upgrade-btn" disabled>Active</button>
                                        <?php elseif ($is_launch_offer) : ?>
                                            <button class="upgrade-btn" disabled title="Automatically granted to the first 100 vendors on sign up">First 100 Vendors Only</button>
                                        <?php else : ?>
                                            <button class="upgrade-btn" onclick="window.location.href= base_url + 'seller/subscription/details/<?= (int) $plan['id']; ?>'">Choose Plan</button>
                                        <?php endif; ?>

                                        <div>
                                            <?php if (!empty($plan['features'])) :
                                                $json = stripslashes($plan['features']);
                                                $features = json_decode($json, true);
                                                if (!empty($features)) :
                                                    $total_features = count($features);
                                                    $show_view_all = $total_features > 10;
                                            ?>
                                                    <ul class="features-list" id="features-plan-<?= (int) $plan['id']; ?>">
                                                        <?php foreach ($features as $index => $feature) : 
                                                            $is_hidden = $index >= 5 ? 'hidden-features' : '';
                                                        ?>
                                                            <li class="<?= $is_hidden; ?>">
                                                                <?= html_escape($feature['description']); ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                    <?php if ($show_view_all) : ?>
                                                        <span class="view-all-link" onclick="toggleFeatures(<?= (int) $plan['id']; ?>, this)">
                                                            View All Features
                                                        </span>
                                                    <?php endif; ?>
                                            <?php
                                                endif;
                                            endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p>No subscription plans available.</p>
                        <?php endif; ?>
                    </section>

                    <section class="subscription-commission-sec">
                        <h1 style="color: var(--orange); font-size: 40px;">Commission</h1>
                        <div class="subscription-table-box">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Orders</th>
                                        <th class="text-right">Commission % per transaction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($current_plan) && (!empty($current_plan['commission_first50']) || !empty($current_plan['commission_51_100']) || !empty($current_plan['commission_after100']))) : ?>
                                        <tr>
                                            <td>For first 50 Orders</td>
                                            <td class="text-right"><?= html_escape($current_plan['commission_first50']); ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>51 - 100 Orders</td>
                                            <td class="text-right"><?= html_escape($current_plan['commission_51_100']); ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Commission after 100 orders</td>
                                            <td class="text-right"><?= html_escape($current_plan['commission_after100']); ?>%</td>
                                        </tr>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="2" class="text-center">Commission details will appear here once a plan is active.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button class="subscription-know-more">Know more</button>
                        <p style="font-size: 12px; margin-top: 10px;">Or talk to our customer support</p>
                    </section>
                </div>
            </div>

            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            <script>
                function startSubscriptionPaymentForPage(planId, serverData, $btn) {
                    if (!serverData || !serverData.razorpay_order_id || !serverData.razorpay_key_id) {
                        if (typeof iziToast !== 'undefined') {
                            iziToast.error({
                                message: 'Payment configuration is missing. Please contact support.',
                                position: 'topRight'
                            });
                        } else {
                            alert('Payment configuration is missing. Please contact support.');
                        }
                        $btn.prop('disabled', false).text('Change Plan');
                        return;
                    }

                    var options = {
                        key: serverData.razorpay_key_id,
                        amount: Math.round(parseFloat(serverData.amount) * 100),
                        currency: 'INR',
                        name: serverData.plan_name || 'Subscription',
                        description: 'Seller Subscription',
                        order_id: serverData.razorpay_order_id,
                        handler: function (response) {
                            var postData = {
                                subscription_id: planId,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature
                            };

                            if (serverData.csrfName && serverData.csrfHash) {
                                postData[serverData.csrfName] = serverData.csrfHash;
                            }

                            $.ajax({
                                url: base_url + 'seller/subscription/razorpay_callback',
                                type: 'POST',
                                data: postData,
                                dataType: 'json',
                                success: function (res) {
                                    if (res.csrfName && res.csrfHash) {
                                        csrfName = res.csrfName;
                                        csrfHash = res.csrfHash;
                                    }

                                    if (res.error === false) {
                                        if (typeof iziToast !== 'undefined') {
                                            iziToast.success({
                                                message: res.message,
                                                position: 'topRight'
                                            });
                                        } else {
                                            alert(res.message);
                                        }
                                        setTimeout(function () {
                                            location.reload();
                                        }, 1500);
                                    } else {
                                        if (typeof iziToast !== 'undefined') {
                                            iziToast.error({
                                                message: res.message || 'Unable to activate subscription after payment.',
                                                position: 'topRight'
                                            });
                                        } else {
                                            alert(res.message || 'Unable to activate subscription after payment.');
                                        }
                                        $btn.prop('disabled', false).text('Change Plan');
                                    }
                                },
                                error: function () {
                                    if (typeof iziToast !== 'undefined') {
                                        iziToast.error({
                                            message: 'Failed to verify payment. Please contact support with your payment details.',
                                            position: 'topRight'
                                        });
                                    } else {
                                        alert('Failed to verify payment. Please contact support with your payment details.');
                                    }
                                    $btn.prop('disabled', false).text('Change Plan');
                                }
                            });
                        }
                    };

                    if (serverData.seller_name) {
                        options.prefill = options.prefill || {};
                        options.prefill.name = serverData.seller_name;
                    }
                    if (serverData.seller_email) {
                        options.prefill = options.prefill || {};
                        options.prefill.email = serverData.seller_email;
                    }
                    if (serverData.seller_contact) {
                        options.prefill = options.prefill || {};
                        options.prefill.contact = serverData.seller_contact;
                    }

                    var rzp = new Razorpay(options);
                    rzp.on('payment.failed', function () {
                        if (typeof iziToast !== 'undefined') {
                            iziToast.error({
                                message: 'Payment failed or was cancelled. Please try again.',
                                position: 'topRight'
                            });
                        } else {
                            alert('Payment failed or was cancelled. Please try again.');
                        }
                        $btn.prop('disabled', false).text('Change Plan');
                    });
                    rzp.open();
                }

                function purchasePlan(planId, btn) {
                    //if (!confirm('Do you want to switch to this subscription plan?')) {
                      //  return;
                    //}

                    var $btn = $(btn);
                    var data = {};
                    data['subscription_id'] = planId;
                    data[csrfName] = csrfHash;

                    $btn.prop('disabled', true).text('Please Wait...');

                    $.ajax({
                        url: base_url + 'seller/subscription/purchase',
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: function (result) {
                            if (result.csrfName && result.csrfHash) {
                                csrfName = result.csrfName;
                                csrfHash = result.csrfHash;
                            }

                            if (result.error === false) {
                                if (result.requires_payment) {
                                    startSubscriptionPaymentForPage(planId, result, $btn);
                                } else {
                                    if (typeof iziToast !== 'undefined') {
                                        iziToast.success({
                                            message: result.message,
                                            position: 'topRight'
                                        });
                                    } else {
                                        alert(result.message);
                                    }
                                    setTimeout(function () {
                                        location.reload();
                                    }, 1500);
                                }
                            } else {
                                if (typeof iziToast !== 'undefined') {
                                    iziToast.error({
                                        message: result.message,
                                        position: 'topRight'
                                    });
                                } else {
                                    alert(result.message);
                                }
                                $btn.prop('disabled', false).text('Change Plan');
                            }
                        },
                        error: function () {
                            if (typeof iziToast !== 'undefined') {
                                iziToast.error({
                                    message: 'Something went wrong. Please try again.',
                                    position: 'topRight'
                                });
                            } else {
                                alert('Something went wrong. Please try again.');
                            }
                            $btn.prop('disabled', false).text('Change Plan');
                        }
                    });
                }

                function toggleFeatures(planId, element) {
                    var featuresList = document.getElementById('features-plan-' + planId);
                    if (!featuresList) return;

                    var hiddenItems = featuresList.querySelectorAll('.hidden-features');
                    var isExpanded = element.textContent.includes('Hide');

                    if (isExpanded) {
                        // Collapse
                        hiddenItems.forEach(function (item) {
                            item.style.display = 'none';
                        });
                        element.textContent = 'View All Features';
                    } else {
                        // Expand
                        hiddenItems.forEach(function (item) {
                            item.style.display = 'list-item';
                        });
                        element.textContent = 'Hide Features';
                    }
                }
            </script>
        </div>
    </section>
</div>
