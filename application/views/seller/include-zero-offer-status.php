<?php
/*
 * The logged-in seller's zero-commission offer status: free orders used / left, with a progress
 * bar. Renders nothing for a seller who was never enrolled. One partial so every seller screen
 * (dashboard, subscription, categories, price calculator) states the same numbers.
 *
 * Optional: $zos_compact = true for a one-line version.
 */
$CI = &get_instance();
$CI->load->model('Commission_offer_model');
$zos = $CI->Commission_offer_model->seller_status((int) $CI->session->userdata('user_id'));
$zos_compact = !empty($zos_compact);
if (!empty($zos['enrolled']) || !empty($zos['revoked'])) :
    $zos_pct = $zos['limit'] > 0 ? (int) round($zos['used'] / $zos['limit'] * 100) : 100;
?>
<div class="zos <?= $zos['active'] ? 'is-active' : 'is-done' ?> <?= $zos_compact ? 'is-compact' : '' ?>">
    <div class="zos-head">
        <span class="zos-icon" aria-hidden="true">&#127873;</span>
        <div class="zos-copy">
            <?php if ($zos['active']) : ?>
                <b>Zero Commission Offer: <?= (int) $zos['remaining'] ?> of <?= (int) $zos['limit'] ?> commission-free orders left</b>
                <?php if (!$zos_compact) : ?>
                    <span>You pay <strong>0% commission</strong> on every item in your next <?= (int) $zos['remaining'] ?> order<?= $zos['remaining'] === 1 ? '' : 's' ?>. After that, the normal category rate applies. A cancelled or returned order gives its free slot back.</span>
                <?php endif; ?>
            <?php elseif (!empty($zos['revoked'])) : ?>
                <b>Zero Commission Offer ended</b>
                <span>Your place in the offer was closed after <?= (int) $zos['used'] ?> commission-free order<?= $zos['used'] === 1 ? '' : 's' ?>. Normal category rates now apply.</span>
            <?php else : ?>
                <b>Zero Commission Offer used: all <?= (int) $zos['limit'] ?> commission-free orders are done</b>
                <span>Normal category rates now apply to your new orders.</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="zos-bar" title="<?= (int) $zos['used'] ?> of <?= (int) $zos['limit'] ?> used"><i style="width: <?= $zos_pct ?>%"></i></div>
</div>
<style>
    .zos { border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; border: 1px solid rgba(30, 123, 69, .25); background: #effaf3; text-align: left; }
    .zos.is-done { border-color: rgba(0, 0, 0, .1); background: #f6f7f9; }
    .zos-head { display: flex; gap: 10px; align-items: flex-start; }
    .zos-icon { font-size: 20px; line-height: 1.2; }
    .zos-copy b { display: block; color: #1e7b45; font-size: 14.5px; }
    .zos.is-done .zos-copy b { color: #374151; }
    .zos-copy span { display: block; font-size: 13px; color: #4b5563; margin-top: 2px; }
    .zos-bar { height: 6px; background: rgba(0, 0, 0, .08); border-radius: 3px; overflow: hidden; margin-top: 10px; }
    .zos-bar i { display: block; height: 100%; background: #1e9e57; }
    .zos.is-done .zos-bar i { background: #9ca3af; }
    .zos.is-compact { padding: 9px 12px; }
    .zos.is-compact .zos-bar { margin-top: 7px; }
</style>
<?php endif; ?>
