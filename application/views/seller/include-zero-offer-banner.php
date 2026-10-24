<?php
/*
 * "Zero commission" promo banner for the seller login and sign-up pages. Shown only while the
 * offer still has free places (Commission_offer_model::is_open()), so it disappears on its own
 * once the first N sellers have joined or the admin switches the offer off. Uses the same
 * .launch-offer-banner styling as the listings promo above it (assets/admin/css/seller-auth.css).
 */
$CI = &get_instance();
$CI->load->model('Commission_offer_model');
if ($CI->Commission_offer_model->is_open()) :
    $zob = $CI->Commission_offer_model->settings();
    $zob_left = $CI->Commission_offer_model->slots_left();
?>
<div class="launch-offer-banner zero-offer-banner" role="note">
    <span class="lo-icon" aria-hidden="true">&#127873;</span>
    <span class="lo-text"><strong>0% Commission</strong>First <?= (int) $zob['seller_limit'] ?> sellers pay zero commission on their first <?= (int) $zob['orders_per_seller'] ?> orders &mdash; only <?= (int) $zob_left ?> place<?= $zob_left === 1 ? '' : 's' ?> left</span>
</div>
<style>
    .zero-offer-banner { display: flex; align-items: center; gap: 12px; margin: 8px 0 0; padding: 6px 10px; border-radius: 7px;
        background: linear-gradient(135deg, #eafaf0 0%, #d5f3e1 100%); border: 1px solid rgba(30, 123, 69, .3); line-height: 1.35; text-align: left; }
    .zero-offer-banner .lo-icon { font-size: 24px; line-height: 1; flex-shrink: 0; }
    .zero-offer-banner .lo-text { font-size: 13px; font-weight: 500; color: #3f4b45; }
    .zero-offer-banner .lo-text strong { display: block; color: #1e7b45; font-size: 14px; }
    .subscription-header .zero-offer-banner { max-width: 640px; margin: 0 auto 14px; }
</style>
<?php endif; ?>
