<?php
/**
 * My Account > Wallet.
 *
 * Rebuilt on the shared account shell. Three real problems went with the
 * redesign:
 *
 *  1. "Add money" was `data-bs-toggle="modal"`. This theme loads plugins.js
 *     (Bootstrap 5.2.2) and then bootstrap.min.js (Bootstrap 4.0.0), so
 *     `$.fn.modal` is v4's while the `data-bs-*` data-api is v5's - the two
 *     disagree about the backdrop and the body lock, which is the same
 *     orphaned-backdrop bug address.js carries a sweep-up handler for. The
 *     popup now uses the account controller (CzAccount) instead.
 *  2. The close button was Bootstrap 5's `.btn-close`, which renders as a blank
 *     14x14 box under the Bootstrap 4 stylesheet this theme actually loads -
 *     so the popup had no visible way out except the backdrop.
 *  3. The form was BOTH `.form-submit-event` and had a `type="submit"` refill
 *     button, so one click fired custom.js's generic AJAX submit (to an empty
 *     action, i.e. back to this page) AND wallet.js's Razorpay handler. It is a
 *     plain form with a type="button" trigger now, so only wallet.js runs.
 */

$currency = isset($settings['currency']) ? $settings['currency'] : '';
/* $users is set by My_account::wallet(); $user is the constructor's copy. Either
 * is the same row, but only one of them is guaranteed on every action. */
$wallet_user = isset($users) && is_object($users) ? $users : (isset($user) && is_object($user) ? $user : null);
$balance = ($wallet_user && isset($wallet_user->balance)) ? (float) $wallet_user->balance : 0;

$payment_methods = get_settings('payment_method', true);
$razorpay_on = (isset($payment_methods['razorpay_payment_method']) && $payment_methods['razorpay_payment_method'] == 1);

/* wallet.js posts this as the refill's order id, so it has to be unique per
 * render - it is the idempotency key Razorpay is handed. */
$refill_order_id = 'wallet-refill-user-' . (int) $this->session->userdata('user_id') . '-' . time() . '-' . rand(900, 999);

/* --------------------------------------------------------------- actions -- */
ob_start(); ?>
<?php if ($razorpay_on) { ?>
    <button type="button" class="czap-btn czap-btn--primary" data-czap-open="#czap-wallet-modal">
        <i class="uil uil-plus-circle"></i> Add money
    </button>
<?php } ?>
<?php $page_actions = ob_get_clean();

/* --------------------------------------------------------------- content -- */
ob_start(); ?>

<div class="czap-cols" style="margin-bottom:26px">

    <div class="czap-panel czap-panel--brand" style="display:flex;align-items:center;gap:18px">
        <img src="<?= base_url('assets/front_end/cretzo/img/new_cretzo/wallet.png') ?>"
             alt="" height="58" style="flex:none">
        <div>
            <p class="czap-stat__label" style="font-size:13px;margin:0 0 2px">
                <?= !empty($this->lang->line('balance')) ? $this->lang->line('balance') : 'Available balance' ?>
            </p>
            <p class="czap-money czap-money--lg" style="margin:0">
                <?= html_escape($currency) ?><?= number_format($balance, 2) ?>
            </p>
        </div>
    </div>

    <div class="czap-panel czap-panel--soft">
        <p class="czap-panel__title"><i class="uil uil-info-circle"></i> How Cretzo cash works</p>
        <p class="czap-help" style="margin:0 0 12px">
            Your balance is applied at checkout before any other payment method, and refunds for
            cancelled or returned items are credited straight back here.
        </p>
        <?php if ($razorpay_on) { ?>
            <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm" data-czap-open="#czap-wallet-modal">
                <i class="uil uil-plus-circle"></i> Top up your wallet
            </button>
        <?php } else { ?>
            <?php /* Without a live gateway there is no way to take the money, so say
                     so rather than showing a button that opens a form with no
                     payment method in it. */ ?>
            <p class="czap-help is-bad" style="margin:0">
                <i class="uil uil-exclamation-circle"></i>
                Top-ups are unavailable right now because no online payment method is enabled.
                Refunds are still credited here automatically.
            </p>
        <?php } ?>
    </div>
</div>

<p class="czap-sec">Wallet history</p>
<div class="czap-table czap-scroll">
    <table data-toggle="table"
           data-url="<?= base_url('my-account/get-wallet-transactions') ?>"
           data-side-pagination="server" data-pagination="true"
           data-page-list="[5, 10, 20, 50, 100]" data-page-size="10"
           data-search="true" data-trim-on-search="false"
           data-show-refresh="true" data-show-columns="true" data-show-export="true"
           data-sort-name="id" data-sort-order="desc"
           data-mobile-responsive="true"
           data-query-params="customer_wallet_query_paramss"
           id="customer_wallet_query_paramss">
        <thead>
            <tr>
                <th data-field="id" data-sortable="true"><?= !empty($this->lang->line('id')) ? $this->lang->line('id') : 'ID' ?></th>
                <?php /* The Username column is dropped: this table is scoped to the
                         logged-in customer, so it printed the same name on every row. */ ?>
                <th data-field="type" data-sortable="false"><?= !empty($this->lang->line('type')) ? $this->lang->line('type') : 'Type' ?></th>
                <th data-field="amount" data-sortable="false"><?= !empty($this->lang->line('amount')) ? $this->lang->line('amount') : 'Amount' ?></th>
                <th data-field="status" data-sortable="false"><?= !empty($this->lang->line('status')) ? $this->lang->line('status') : 'Status' ?></th>
                <th data-field="message" data-sortable="false"><?= !empty($this->lang->line('message')) ? $this->lang->line('message') : 'Note' ?></th>
                <th data-field="date" data-sortable="false"><?= !empty($this->lang->line('date')) ? $this->lang->line('date') : 'Date' ?></th>
            </tr>
        </thead>
    </table>
</div>

<?php $page_content = ob_get_clean();

$this->load->view('front-end/' . THEME . '/partials/account-layout', [
    'active_menu'  => $main_page,
    'page_title'   => 'Wallet',
    'page_sub'     => 'Your Cretzo cash, top-ups and refunds',
    'page_icon'    => 'uil-wallet',
    'page_actions' => $page_actions,
    'page_content' => $page_content,
]);
?>

<?php if ($razorpay_on) { ?>
<!-- ========================= POPUP: add money ========================= -->
<div class="czap-modal" id="czap-wallet-modal" hidden aria-hidden="true"
     role="dialog" aria-modal="true" aria-labelledby="czap-wallet-modal-title">
    <div class="czap-modal__scrim" data-czap-close></div>
    <div class="czap-modal__panel" role="document">

        <?php /* NOT .form-submit-event, and the trigger below is type="button" - see the
                 note at the top of this file. wallet.js reads #wallet_form, #amount,
                 #order_id, the #razorpay_* hidden fields and the identity fields by id,
                 so every one of them is preserved. */ ?>
        <form method="POST" id="wallet_form" enctype="multipart/form-data">

            <div class="czap-modal__head">
                <div>
                    <h2 class="czap-modal__title" id="czap-wallet-modal-title">
                        <i class="uil uil-plus-circle"></i> Add money to wallet
                    </h2>
                    <p class="czap-modal__sub">Balance available instantly after a successful payment.</p>
                </div>
                <button type="button" class="czap-modal__x" data-czap-close aria-label="Close">&times;</button>
            </div>

            <div class="czap-modal__body">

                <input type="hidden" name="app_name" id="app_name" value="<?= html_escape($settings['app_name']) ?>">
                <input type="hidden" id="flutterwave_currency" value="<?= isset($payment_methods['flutterwave_currency_code']) ? html_escape($payment_methods['flutterwave_currency_code']) : '' ?>">
                <?php /* Read off the logged-in user row rather than re-querying the users
                         table and re-reading $_SESSION for each field, which is what this
                         used to do (a fetch_details() call per popup render). */ ?>
                <input type="hidden" id="user_email" value="<?= $wallet_user ? html_escape($wallet_user->email) : '' ?>">
                <input type="hidden" id="username" value="<?= $wallet_user ? html_escape($wallet_user->username) : '' ?>">
                <input type="hidden" id="user_contact" value="<?= $wallet_user ? html_escape($wallet_user->mobile) : '' ?>">
                <input type="hidden" name="logo" id="logo" value="<?= base_url(get_settings('web_logo')) ?>">
                <input type="hidden" name="order_id" id="order_id" value="<?= $refill_order_id ?>">

                <div class="czap-field" style="margin-bottom:16px">
                    <label class="czap-field__label" for="amount">Amount<span class="czap-req">*</span></label>
                    <input type="number" name="amount" id="amount" class="czap-input"
                           min="1" step="1" required placeholder="Enter an amount" data-czap-autofocus>
                    <p class="czap-help">In <?= html_escape($currency) ?>. Minimum 1.</p>
                </div>

                <?php /* Quick-pick chips: the amounts people actually top up with, so the
                         common case is one tap instead of typing. */ ?>
                <div class="czap-radios" style="margin-bottom:18px">
                    <?php foreach ([500, 1000, 2000, 5000] as $preset) { ?>
                        <button type="button" class="czap-btn czap-btn--ghost czap-btn--sm czap-wallet-preset"
                                data-amount="<?= $preset ?>">
                            + <?= html_escape($currency) ?><?= number_format($preset) ?>
                        </button>
                    <?php } ?>
                </div>

                <div class="czap-field" style="margin-bottom:18px">
                    <label class="czap-field__label" for="message_input">Note</label>
                    <input type="text" name="message" class="czap-input ticket_msg" id="message_input"
                           placeholder="Optional - what is this top-up for?">
                </div>

                <p class="czap-sec" style="margin-bottom:10px">
                    <?= !empty($this->lang->line('payment_method')) ? $this->lang->line('payment_method') : 'Payment method' ?>
                </p>
                <div class="czap-radios">
                    <label class="czap-radio is-checked" for="razorpay">
                        <?php /* Pre-selected because it is the only method here. It used to be
                                 `required` with nothing checked, so the first click always
                                 failed silently - wallet.js only acts when the checked value
                                 is exactly 'Razorpay'. */ ?>
                        <input id="razorpay" name="payment_method" type="radio" value="Razorpay" checked required>
                        <img src="<?= THEME_ASSETS_URL . 'img/payments/razorpay.png' ?>" alt="" style="height:20px">
                        RazorPay
                    </label>
                </div>
                <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="">
                <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
                <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="">

                <div id="czap-wallet-msg" style="display:none;margin-top:16px"></div>
            </div>

            <div class="czap-modal__foot">
                <button type="button" class="czap-btn czap-btn--quiet" data-czap-close>Cancel</button>
                <?php /* type="button": wallet.js binds a delegated click on #wallet_refill and
                         opens Razorpay itself. As a submit button it ALSO submitted the form. */ ?>
                <button type="button" class="czap-btn czap-btn--primary" id="wallet_refill">
                    <i class="uil uil-lock-alt"></i> <?= labels('Refill', 'Refill') ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<?php /* PayPal is not enabled on this install, but cart/wallet_refill can still
         route to it, and payment/paypal_wallet expects this form to exist. */ ?>
<form action="<?= base_url('payment/paypal_wallet') ?>" id="paypal_form" method="POST">
    <input type="hidden" id="csrf_token" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <input type="hidden" name="order_id" id="paypal_order_id" value="">
    <input type="hidden" name="amount" id="paypal_amount" value="">
</form>

<input type="hidden" name="razorpay_key_id" id="razorpay_key_id" value="<?= isset($payment_methods['razorpay_key_id']) ? html_escape($payment_methods['razorpay_key_id']) : '' ?>">

<?php if ($razorpay_on) { ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php } ?>
<script src="<?= add_ver(THEME_ASSETS_URL . 'js/wallet.js') ?>"></script>

<script>
    /* Amount preset chips, and the validation wallet.js does not do: its own
       amount check is commented out, so an empty amount used to open a Razorpay
       checkout for zero. */
    $(function () {
        var $amount = $('#amount');

        $('.czap-wallet-preset').on('click', function () {
            var add = parseInt($(this).data('amount'), 10) || 0;
            var now = parseInt($amount.val(), 10) || 0;
            $amount.val(now + add).trigger('change').focus();
        });

        $('#wallet_refill').on('click', function (e) {
            var value = parseFloat($amount.val());
            if (!(value > 0)) {
                e.stopImmediatePropagation();
                $('#czap-wallet-msg')
                    .attr('class', 'czap-alert czap-alert--bad')
                    .html('<i class="uil uil-exclamation-circle"></i><span>Enter an amount of at least 1 to continue.</span>')
                    .show();
                $amount.focus();
                return false;
            }
            $('#czap-wallet-msg').hide();
        });
    });
</script>
