<div class="content-wrapper send-withdrawal-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-money-bill-wave mr-2 text-primary-theme"></i>Send Withdrawal Request</h4>
                    <p class="text-muted mb-0 small">Request a payout from your available wallet balance.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/payment-request/withdrawal-requests') ?>">Withdrawal Requests</a></li>
                        <li class="breadcrumb-item active">Send</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-money-bill-wave"></i></span>
                    <h5 class="mb-0">New Withdrawal Request</h5>
                </div>
                <div class="card-body">
                    <?php
                    $settings = get_settings('system_settings', true);
                    $currency = isset($settings['currency']) ? $settings['currency'] : '';
                    ?>
                    <div class="alert alert-light border d-flex justify-content-between flex-wrap mb-3">
                        <span>Available balance: <strong><?= $currency . number_format($wallet_balance, 2) ?></strong></span>
                        <span class="text-muted">Minimum withdrawal: <?= $currency . number_format($min_withdrawal, 2) ?></span>
                    </div>
                    <?php if (!empty($has_pending)) { ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock mr-1"></i>
                            You already have a withdrawal request awaiting approval. You can send a new one once it has
                            been processed.
                        </div>
                    <?php } ?>
                    <form id="withdrawal_request_form" action="<?= base_url('seller/payment-request/add-withdrawal-request'); ?>" method="POST" enctype="multipart/form-data">
                        <div class="form-group row">
                            <label for="payment_address" class="col-sm-2 col-form-label">Payment Details <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="payment_address" placeholder="Bank account / UPI / PayPal details to receive payment" name="payment_address"></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="amount" class="col-sm-2 col-form-label">Amount <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <!-- min was 0, which let the form submit an amount the server
                                     would only then reject; it now matches the server's rule. -->
                                <input type="number" class="form-control" id="amount" placeholder="Amount" name="amount"
                                    min="<?= html_escape($min_withdrawal) ?>" max="<?= html_escape($wallet_balance) ?>" step="0.01">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary-theme mr-2" id="submit_btn" <?= !empty($has_pending) ? 'disabled' : '' ?>><i class="fas fa-paper-plane mr-1"></i>Send</button>
                            <button type="reset" class="btn btn-light border">Reset</button>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div class="form-group mb-0" id="error_box"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .send-withdrawal-page .text-primary-theme { color: var(--color-orange); }
    .send-withdrawal-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .send-withdrawal-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .send-withdrawal-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .send-withdrawal-page .header-icon.bg-set { background: var(--color-orange); }
    .send-withdrawal-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    .send-withdrawal-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .send-withdrawal-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }
</style>

<script>
    // This form relied on a generic ".form-submit-event" submit handler that only ever
    // shipped in the admin JS bundle (not loaded on seller pages), so clicking "Send"
    // previously did nothing. Wired up directly here. Also: the hidden user_id field this
    // form used to submit was removed — the backend now always uses the authenticated
    // seller's own id and ignores any submitted user_id (see Payment_request::add_withdrawal_request).
    $(document).on('submit', '#withdrawal_request_form', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.append(csrfName, csrfHash);
        var submitBtn = $('#submit_btn');
        var originalText = submitBtn.html();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            beforeSend: function () {
                submitBtn.html('Please Wait...').prop('disabled', true);
                $('#error_box').html('');
            },
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName;
                    csrfHash = result.csrfHash;
                }
                if (result.error) {
                    $('#error_box').html('<span class="text-danger">' + result.message + '</span>');
                } else {
                    iziToast.success({ message: result.message });
                    setTimeout(function () {
                        window.location.href = "<?= base_url('seller/payment-request/withdrawal-requests') ?>";
                    }, 1000);
                }
            },
            error: function () {
                $('#error_box').html('<span class="text-danger">Something went wrong. Please try again.</span>');
            },
            complete: function () {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
</script>
