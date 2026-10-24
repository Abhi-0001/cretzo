<?php
/*
 * Seller-facing, read-only commission rate card.
 *
 * Commission is charged per product category (set by the admin in Categories > Category
 * Commission), not per subscription plan. This lists every active top-level category with the
 * rate its products are charged, plus any subcategory whose rate differs from its parent's -
 * a subcategory that just inherits is not repeated.
 *
 * Optional: $czcc_compact = true renders the bare list without the heading/explanation.
 */
$CI = &get_instance();
$CI->load->model('Category_model');
$czcc_rows = $CI->Category_model->commission_overview(true);
$czcc_fmt = function ($n) {
    return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
};

$czcc_eff = [];
foreach ($czcc_rows as $r) {
    $czcc_eff[$r['id']] = $r['effective_rate'];
}
$czcc_groups = [];
$czcc_current = null;
foreach ($czcc_rows as $r) {
    if ($r['depth'] === 0) {
        $czcc_groups[] = ['name' => $r['name'], 'rate' => $r['effective_rate'], 'exceptions' => []];
        $czcc_current = count($czcc_groups) - 1;
        continue;
    }
    $parent_rate = isset($czcc_eff[$r['parent_id']]) ? $czcc_eff[$r['parent_id']] : null;
    if ($czcc_current !== null && $parent_rate !== null && (float) $r['effective_rate'] !== (float) $parent_rate) {
        $czcc_groups[$czcc_current]['exceptions'][] = ['name' => $r['name'], 'rate' => $r['effective_rate']];
    }
}
$czcc_compact = !empty($czcc_compact);
?>
<div class="czcc">
    <?php if (!$czcc_compact) { $this->load->view('seller/include-zero-offer-status'); } ?>
    <?php if (!$czcc_compact) : ?>
        <p class="czcc-lead">Commission is charged on each item you sell, at the rate of the category the product is listed in. It is worked out on the price before GST, and the rate is fixed when the order is placed.</p>
    <?php endif; ?>
    <?php if (empty($czcc_groups)) : ?>
        <p class="czcc-empty">Commission rates will appear here once categories are set up.</p>
    <?php else : ?>
        <table class="czcc-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="czcc-r">Commission</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($czcc_groups as $g) : ?>
                    <tr>
                        <td class="czcc-root"><?= html_escape($g['name']) ?></td>
                        <td class="czcc-r czcc-rate"><?= $czcc_fmt($g['rate']) ?>%</td>
                    </tr>
                    <?php foreach ($g['exceptions'] as $x) : ?>
                        <tr class="czcc-sub">
                            <td>&#8627; <?= html_escape($x['name']) ?></td>
                            <td class="czcc-r czcc-rate"><?= $czcc_fmt($x['rate']) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php if (!$czcc_compact) : ?>
        <p class="czcc-example"><strong>Example:</strong> a &#8377;1,180 item with 18% GST has a taxable value of &#8377;1,000. In a 10% category the commission is &#8377;100 (plus 18% GST on that commission). Every figure for every settled order is in your <a href="<?= base_url('seller/settlement/settlement-history') ?>">Settlement History</a>.</p>
    <?php endif; ?>
</div>
<style>
    .czcc { text-align: left; }
    .czcc-lead, .czcc-example { font-size: 13.5px; color: #555; margin: 0 0 14px; }
    .czcc-example { margin: 14px 0 0; }
    .czcc-empty { font-size: 14px; color: #777; text-align: center; }
    .czcc-table { width: 100%; border-collapse: collapse; }
    .czcc-table th { border-bottom: 1px solid #ddd; padding: 8px 10px; font-size: 13px; color: #666; font-weight: 600; }
    .czcc-table td { padding: 9px 10px; font-size: 14px; border-bottom: 1px solid rgba(0, 0, 0, .05); }
    .czcc-r { text-align: right; }
    .czcc-root { font-weight: 600; color: #222; }
    .czcc-rate { font-weight: 700; color: #222; white-space: nowrap; }
    .czcc-sub td { font-size: 13px; color: #666; padding-left: 22px; }
</style>
