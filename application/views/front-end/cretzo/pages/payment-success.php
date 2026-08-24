<?php
/*
 * Order-placed confirmation.
 *
 * This page used to be a single hardcoded "Payment Complete / Payment Completed Succesfully"
 * panel shown after EVERY checkout - including Cash on Delivery, where nothing has been paid
 * yet. A COD customer reading "Payment Completed Successfully" reasonably concludes they owe
 * nothing on delivery and refuses to pay the courier, so the wording now comes from the order's
 * real payment method (Payment::success_wording()).
 *
 * $success_state / $order are set by Payment::success(); the defaults below keep the page safe
 * if it is ever rendered without them.
 */
$state = isset($success_state) && is_array($success_state) ? $success_state : [
    'is_paid' => false,
    'icon'    => 'uil uil-check-circle',
    'heading' => 'Order Placed Successfully',
    'message' => '',
];
$order_row = isset($order) && is_array($order) ? $order : null;

// This is an order-confirmation page, so the heading always confirms the ORDER; whether money
// has moved yet is a separate line below it. The admin-editable payment_completed_message label
// literally reads "Payment Completed Successfully", so it is only allowed to override that line
// on an order that really was paid - never on COD.
$heading = $state['heading'];
$message = $state['message'];
if (!empty($state['is_paid']) && !empty($this->lang->line('payment_completed_message'))) {
    $message = $this->lang->line('payment_completed_message');
}
?>
<section class="main-content mb-15">
    <div class="row">
        <div class="col-md-12 col-12 mt-4 pt-2">
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade bg-white show active shadow rounded p-4 text-center" id="dash" role="tabpanel" aria-labelledby="dashboard">
                    <i class="<?= html_escape($state['icon']) ?> fs-100 text-success"></i>
                    <h4 class="h4 text-success"><?= html_escape($heading) ?></h4>

                    <?php if ($order_row !== null) { ?>
                        <p class="mb-1">Order ID: <strong>#<?= (int) $order_row['id'] ?></strong></p>
                    <?php } ?>

                    <?php if (!empty($message)) { ?>
                        <p><?= html_escape($message) ?></p>
                    <?php } ?>

                    <p><?= !empty($this->lang->line('thank_you_for_shopping')) ? $this->lang->line('thank_you_for_shopping') : 'Thank you for Shopping with Us.' ?></p>

                    <div class="d-flex flex-wrap justify-content-center" style="gap:10px;">
                        <?php if ($order_row !== null) { ?>
                            <a class="cretzo btn btn-dark btn-primary mt-4" href="<?= base_url('my-account/orders') ?>">View My Orders</a>
                        <?php } ?>
                        <a class="cretzo btn btn-dark btn-primary mt-4" href="<?= base_url('products') ?>"><?= !empty($this->lang->line('continue_shopping')) ? $this->lang->line('continue_shopping') : 'Continue Shopping' ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
