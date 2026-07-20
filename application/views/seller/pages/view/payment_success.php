<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Payment Successful</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Payment Success</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body text-center" style="padding:60px;background:#FFF9E6">
                    <style>
                        .cretzo-progress { display:flex; align-items:center; justify-content:center; gap:12px; margin-bottom:18px; font-size:20px }
                        .cretzo-progress .step { color:#111; font-weight:600; padding:0 6px; }
                        .cretzo-progress .step.active { color:#F28C38; font-weight:800; }
                        .cretzo-progress .connector { width:160px; border:dashed 1.5px #222; }
                        .cretzo-progress .connector.orange { background: repeating-linear-gradient(90deg, #F28C38 0 12px, transparent 12px 24px); }
                        .cretzo-progress .connector.dark { background: repeating-linear-gradient(90deg, #222 0 12px, transparent 12px 24px); }
                    </style>

                    <div class="cretzo-progress" aria-hidden="true">
                        <div class="step">Choose Plan</div>
                        <div class="connector orange" aria-hidden="true"></div>
                        <div class="step">Payment</div>
                        <div class="connector dark" aria-hidden="true"></div>
                        <div class="step active">Confirmation</div>
                    </div>
                    <h1 style="color:#333">Payment Successful !!</h1>
                    <p style="margin-top:20px">Your subscription has been activated.</p>
                    <?php if (!empty($payment_id)) : ?>
                        <p><strong>Payment ID:</strong> <?= html_escape($payment_id); ?></p>
                    <?php endif; ?>
                    <a class="btn btn-primary" href="<?= base_url('seller/home'); ?>">Start Using Cretzo</a>
                    <a class="btn btn-outline-secondary" href="<?= base_url('seller/subscription/manage_subscriptions'); ?>">Manage Subscription</a>
                </div>
            </div>
        </div>
    </section>
</div>
