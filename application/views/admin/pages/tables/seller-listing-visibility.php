<?php
/**
 * Admin's view of which of a seller's products the shop may show.
 *
 * Mirrors the seller's own "Visible Listings" screen (seller/pages/forms/listing-visibility)
 * and drives the same model method, so both sides can only ever produce a selection that
 * respects the plan's listing limit.
 */
$limit     = isset($listing_state['limit']) ? $listing_state['limit'] : null;
$visible   = isset($listing_state['visible']) ? (int) $listing_state['visible'] : 0;
$total     = is_array($products) ? count($products) : 0;
$unlimited = ($limit === null);
$plan_name = !empty($current_plan['name']) ? $current_plan['name'] : 'None';
$shop      = !empty($seller['store_name']) ? $seller['store_name'] : $seller['username'];
?>
<div class="content-wrapper admin-listing-visibility">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-eye mr-2 text-primary-theme"></i>Visible Listings</h4>
                    <p class="text-muted mb-0 small"><?= html_escape($shop) ?> &mdash; which products buyers can see.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/subscription/seller_subscriptions') ?>">Seller Subscriptions</a></li>
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
                    <div class="alv-summary">
                        <strong>Plan:</strong> <?= html_escape($plan_name) ?> &nbsp;
                        <strong>Limit:</strong> <?= $unlimited ? 'Unlimited' : (int) $limit ?> &nbsp;
                        <strong>Products:</strong> <?= $total ?> &nbsp;
                        <strong>Visible:</strong> <?= $visible ?>
                        <?php if (!$unlimited && $total > $limit) : ?>
                            <span class="badge badge-danger ml-2"><?= $total - $limit ?> hidden by plan limit</span>
                        <?php endif; ?>
                    </div>

                    <div id="alv-alert" class="alert d-none mt-3" role="alert"></div>

                    <?php if (empty($products)) : ?>
                        <p class="text-muted mt-3 mb-0">This seller has no products.</p>
                    <?php else : ?>
                        <form id="alv-form" action="<?= base_url('admin/subscription/save_seller_listing_visibility') ?>" method="POST">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" id="alv_csrf">
                            <input type="hidden" name="seller_id" value="<?= (int) $seller['id'] ?>">

                            <div class="d-flex justify-content-between align-items-center flex-wrap my-3">
                                <div>
                                    <input type="text" id="alv-search" class="form-control form-control-sm d-inline-block"
                                           style="width:220px" placeholder="Search products...">
                                    <span class="ml-2 small text-muted">
                                        Selected: <strong id="alv-count"><?= $visible ?></strong><?= $unlimited ? '' : ' / ' . (int) $limit ?>
                                    </span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="alv-clear">Unselect all</button>
                                    <button type="submit" class="btn btn-primary btn-sm" id="alv-save">Save selection</button>
                                </div>
                            </div>

                            <div class="table-responsive alv-table-wrap">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:70px">Show</th>
                                            <th style="width:64px"></th>
                                            <th>Product</th>
                                            <th style="width:170px">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $row) :
                                            $is_visible = ((int) $row['listing_visibility'] === 1);
                                            $hidden_by_quota = ((int) $row['listing_visibility'] === 2);
                                        ?>
                                            <tr class="alv-row" data-name="<?= html_escape(strtolower($row['name'])) ?>">
                                                <td>
                                                    <input class="alv-check" type="checkbox" name="visible_ids[]"
                                                           value="<?= (int) $row['id'] ?>" <?= $is_visible ? 'checked' : '' ?>>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['image'])) : ?>
                                                        <img src="<?= base_url() . $row['image'] ?>" alt="" class="alv-thumb">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?= base_url('admin/product/create_product?edit_id=' . (int) $row['id']) ?>">
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
                                                        <span class="badge badge-secondary">Hidden manually</span>
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
    .admin-listing-visibility .text-primary-theme { color: var(--color-orange); }
    .admin-listing-visibility .alv-summary { border-left: 4px solid var(--color-orange); background: #fff8ef; border-radius: 8px; padding: 12px 16px; font-size: 14px; }
    .admin-listing-visibility .alv-table-wrap { max-height: 60vh; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 8px; }
    .admin-listing-visibility .alv-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; }
    .admin-listing-visibility table thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
    .admin-listing-visibility .alv-over-limit { color: #dc3545; font-weight: 700; }
</style>

<script>
$(function () {
    var LIMIT = <?= $unlimited ? 'null' : (int) $limit ?>;

    function refresh() {
        var n = $('.alv-check:checked').length;
        $('#alv-count').text(n).toggleClass('alv-over-limit', LIMIT !== null && n > LIMIT);
        $('#alv-save').prop('disabled', LIMIT !== null && n > LIMIT);
    }

    $(document).on('change', '.alv-check', refresh);
    $('#alv-clear').on('click', function () { $('.alv-check').prop('checked', false); refresh(); });

    $('#alv-search').on('input', function () {
        var term = $.trim($(this).val()).toLowerCase();
        $('.alv-row').each(function () {
            $(this).toggle(!term || ($(this).data('name') + '').indexOf(term) > -1);
        });
    });

    $('#alv-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#alv-save').prop('disabled', true).text('Saving...');
        $.post($(this).attr('action'), $(this).serialize(), null, 'json')
            .done(function (res) {
                if (res.csrfName && res.csrfHash) { $('#alv_csrf').attr('name', res.csrfName).val(res.csrfHash); }
                $('#alv-alert')
                    .removeClass('d-none alert-success alert-danger')
                    .addClass(res.error ? 'alert-danger' : 'alert-success')
                    .text(res.message);
                if (!res.error) { setTimeout(function () { window.location.reload(); }, 900); }
            })
            .fail(function () {
                $('#alv-alert').removeClass('d-none alert-success').addClass('alert-danger')
                    .text('Could not save the selection. Please try again.');
            })
            .always(function () { $btn.prop('disabled', false).text('Save selection'); refresh(); });
    });

    refresh();
});
</script>
