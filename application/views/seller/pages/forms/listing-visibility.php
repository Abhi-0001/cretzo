<?php
/**
 * Which of the seller's products the shop may show.
 *
 * The plan's listings_limit is a cap on live listings, not just on how many products can
 * be created. When a seller has more products than their plan allows - after a downgrade,
 * or when a plan lapsed to the free tier - the overflow stops being shown to buyers, and
 * this page is where the seller picks which listings keep the slots.
 */
$limit    = isset($listing_state['limit']) ? $listing_state['limit'] : null;
$visible  = isset($listing_state['visible']) ? (int) $listing_state['visible'] : 0;
$total    = is_array($products) ? count($products) : 0;
$unlimited = ($limit === null);
$plan_name = !empty($current_plan['name']) ? $current_plan['name'] : '';
?>
<div class="content-wrapper seller-listing-visibility">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-eye mr-2 text-primary-theme"></i>Visible Listings</h4>
                    <p class="text-muted mb-0 small">Choose which of your products buyers can see.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/product') ?>">Products</a></li>
                        <li class="breadcrumb-item active">Visible Listings</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">

                    <div class="lv-summary <?= $unlimited ? 'lv-ok' : ($visible >= $limit && $total > $limit ? 'lv-full' : 'lv-ok') ?>">
                        <?php if ($unlimited) : ?>
                            <strong>All <?= $total ?> of your products are visible.</strong>
                            Your <?= html_escape($plan_name ?: 'current') ?> plan has no listing limit, so nothing is held back.
                        <?php else : ?>
                            <strong><?= $visible ?> of <?= (int) $limit ?> listing slots used</strong>
                            <?= $plan_name !== '' ? ' on the ' . html_escape($plan_name) . ' plan' : '' ?>.
                            <?php if ($total > $limit) : ?>
                                You have <?= $total ?> products, so <?= $total - $limit ?> of them
                                <?= ($total - $limit) === 1 ? 'is' : 'are' ?> hidden from buyers.
                                Tick the ones you want live &mdash; up to <?= (int) $limit ?> &mdash; and save.
                                <a href="<?= base_url('seller/subscription') ?>">Upgrade your plan</a> to show more.
                            <?php else : ?>
                                Every product you have is visible. Adding more stays within your plan.
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div id="lv-alert" class="alert d-none mt-3" role="alert"></div>

                    <?php if (empty($products)) : ?>
                        <p class="text-muted mt-3 mb-0">You have no products yet.
                            <a href="<?= base_url('seller/product/create-product') ?>">Add your first one</a>.</p>
                    <?php else : ?>
                        <form id="lv-form" action="<?= base_url('seller/product/save_listing_visibility') ?>" method="POST">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" id="lv_csrf">

                            <div class="d-flex justify-content-between align-items-center flex-wrap my-3">
                                <div>
                                    <input type="text" id="lv-search" class="form-control form-control-sm d-inline-block"
                                           style="width:220px" placeholder="Search your products...">
                                    <span class="ml-2 small text-muted">
                                        Selected: <strong id="lv-count"><?= $visible ?></strong><?= $unlimited ? '' : ' / ' . (int) $limit ?>
                                    </span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="lv-clear">Unselect all</button>
                                    <button type="submit" class="btn btn-primary btn-sm" id="lv-save">Save selection</button>
                                </div>
                            </div>

                            <div class="table-responsive lv-table-wrap">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:70px">Show</th>
                                            <th style="width:64px"></th>
                                            <th>Product</th>
                                            <th style="width:150px">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $row) :
                                            $is_visible = ((int) $row['listing_visibility'] === 1);
                                            $hidden_by_quota = ((int) $row['listing_visibility'] === 2);
                                        ?>
                                            <tr class="lv-row" data-name="<?= html_escape(strtolower($row['name'])) ?>">
                                                <td>
                                                    <input class="lv-check" type="checkbox" name="visible_ids[]"
                                                           value="<?= (int) $row['id'] ?>" <?= $is_visible ? 'checked' : '' ?>>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['image'])) : ?>
                                                        <img src="<?= base_url() . $row['image'] ?>" alt="" class="lv-thumb">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?= base_url('seller/product/create_product?edit_id=' . (int) $row['id']) ?>">
                                                        <?= html_escape($row['name']) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php if ((int) $row['status'] === 2) : ?>
                                                        <span class="badge badge-warning">Awaiting approval</span>
                                                    <?php elseif ((int) $row['status'] !== 1) : ?>
                                                        <span class="badge badge-secondary">Deactivated</span>
                                                    <?php elseif ($is_visible) : ?>
                                                        <span class="badge badge-success">Live</span>
                                                    <?php elseif ($hidden_by_quota) : ?>
                                                        <span class="badge badge-danger">Over plan limit</span>
                                                    <?php else : ?>
                                                        <span class="badge badge-secondary">Hidden by you</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .seller-listing-visibility .text-primary-theme { color: var(--color-orange); }
    .seller-listing-visibility .lv-summary { border-left: 4px solid var(--color-orange); background: #fff8ef; border-radius: 8px; padding: 12px 16px; font-size: 14px; }
    .seller-listing-visibility .lv-summary.lv-full { border-left-color: #dc3545; }
    .seller-listing-visibility .lv-table-wrap { max-height: 60vh; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 8px; }
    .seller-listing-visibility .lv-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; }
    .seller-listing-visibility table thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
    .seller-listing-visibility .lv-over-limit { color: #dc3545; font-weight: 700; }
</style>

<script>
$(function () {
    var LIMIT = <?= $unlimited ? 'null' : (int) $limit ?>;
    var $count = $('#lv-count');

    function selected() { return $('.lv-check:checked').length; }

    function refresh() {
        var n = selected();
        $count.text(n).toggleClass('lv-over-limit', LIMIT !== null && n > LIMIT);
        // Stop the seller building a selection the server is only going to reject.
        $('#lv-save').prop('disabled', LIMIT !== null && n > LIMIT);
    }

    $(document).on('change', '.lv-check', refresh);
    $('#lv-clear').on('click', function () { $('.lv-check').prop('checked', false); refresh(); });

    $('#lv-search').on('input', function () {
        var term = $.trim($(this).val()).toLowerCase();
        $('.lv-row').each(function () {
            $(this).toggle(!term || ($(this).data('name') + '').indexOf(term) > -1);
        });
    });

    $('#lv-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#lv-save').prop('disabled', true).text('Saving...');
        $.post($(this).attr('action'), $(this).serialize(), null, 'json')
            .done(function (res) {
                if (res.csrfName && res.csrfHash) { $('#lv_csrf').attr('name', res.csrfName).val(res.csrfHash); }
                $('#lv-alert')
                    .removeClass('d-none alert-success alert-danger')
                    .addClass(res.error ? 'alert-danger' : 'alert-success')
                    .text(res.message);
                if (!res.error) {
                    // Badges and counts are derived server-side, so re-read rather than
                    // trying to keep a second copy of that logic in here.
                    setTimeout(function () { window.location.reload(); }, 900);
                }
            })
            .fail(function () {
                $('#lv-alert').removeClass('d-none alert-success').addClass('alert-danger')
                    .text('Could not save your selection. Please try again.');
            })
            .always(function () { $btn.prop('disabled', false).text('Save selection'); refresh(); });
    });

    refresh();
});
</script>
