<div class="content-wrapper admin-category-order-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sort mr-2 text-primary-theme"></i>Category Display Order</h4>
                    <p class="text-muted mb-0 small">Drag to set the order top-level categories appear in on the storefront.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/category') ?>">Categories</a></li>
                        <li class="breadcrumb-item active">Display Order</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-sitemap"></i></span>
                        <h5 class="mb-0">Categories <span class="text-muted font-weight-normal">(<?= count($categories ?? []) ?>)</span></h5>
                    </div>
                    <button type="button" class="btn btn-primary-theme btn-sm" id="save_category_order">
                        <i class="fas fa-save mr-1"></i>Save Order
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($categories)) { ?>
                        <div class="row category-order-header font-weight-bold mb-2 d-none d-md-flex">
                            <div class="col-md-1 text-center">Order</div>
                            <div class="col-md-2 text-center">Image</div>
                            <div class="col-md-9">Category</div>
                        </div>

                        <ul class="list-group order-container" id="category_order_sortable">
                            <?php foreach ($categories as $row) { ?>
                                <li class="list-group-item category-order-item d-flex align-items-center" id="category_id-<?= (int) $row['id'] ?>">
                                    <div class="col-md-1 col-2 text-center"><i class="fas fa-grip-vertical category-order-handle"></i></div>
                                    <div class="col-md-2 col-3 text-center"><img src="<?= $row['image'] ?>" class="category-order-image" alt=""></div>
                                    <div class="col-md-9 col-7 category-order-name"><?= html_escape($row['name']) ?></div>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="category-order-state">
                            <i class="fas fa-box-open"></i>
                            <p>No categories exist.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-category-order-page .text-primary-theme { color: var(--color-orange); }

    .admin-category-order-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-category-order-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-category-order-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-category-order-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-category-order-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-category-order-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-category-order-page .category-order-header { color: var(--color-grey); font-size: 13px; text-transform: uppercase; letter-spacing: .3px; }

    .admin-category-order-page .category-order-state {
        padding: 50px 0;
        text-align: center;
        color: var(--color-grey);
    }
    .admin-category-order-page .category-order-state i { font-size: 30px; margin-bottom: 10px; display: block; opacity: .5; }
    .admin-category-order-page .category-order-state p { margin: 0; }

    .admin-category-order-page .order-container { max-height: 65vh; overflow-y: auto; }
    .admin-category-order-page .category-order-item {
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        margin-bottom: 8px;
        padding: 10px 12px;
        background: #fff;
        cursor: grab;
    }
    .admin-category-order-page .category-order-item:active { cursor: grabbing; }
    .admin-category-order-page .category-order-item.ui-sortable-helper { box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
    .admin-category-order-page .category-order-placeholder {
        border: 2px dashed var(--color-orange);
        border-radius: 8px;
        margin-bottom: 8px;
        background: var(--color-orange-light);
    }
    .admin-category-order-page .category-order-handle { color: var(--color-grey); margin-right: 4px; }
    .admin-category-order-page .category-order-image {
        width: 48px; height: 48px; border-radius: 6px; object-fit: cover;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-category-order-page .category-order-name { font-size: 14px; font-weight: 500; }
</style>

<script>
$(function () {
    var $list = $('#category_order_sortable');

    // This page's own sortable list uses a page-scoped id (category_order_sortable), not the
    // legacy shared #sortable id that assets/admin/custom/custom.js still initializes globally
    // with two competing drag-and-drop libraries (jQuery UI Sortable and SortableJS) on every
    // admin page load - using a distinct id keeps this list clear of that conflict entirely.
    if ($list.length) {
        $list.sortable({
            axis: 'y',
            placeholder: 'category-order-placeholder',
            handle: '.category-order-handle',
            forcePlaceholderSize: true
        });
    }

    $('#save_category_order').on('click', function () {
        if (!$list.length) {
            return;
        }
        var $btn = $(this);
        var data = $list.sortable('serialize');

        $.ajax({
            type: 'GET',
            url: "<?= base_url('admin/category/update_category_order') ?>",
            data: data,
            dataType: 'json',
            beforeSend: function () {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving...');
            },
            success: function (response) {
                if (response.csrfName && response.csrfHash) {
                    csrfName = response.csrfName;
                    csrfHash = response.csrfHash;
                }
                if (response.error) {
                    iziToast.error({ message: response.message });
                } else {
                    iziToast.success({ message: response.message });
                }
            },
            error: function () {
                iziToast.error({ message: 'The order could not be saved. Please try again.' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Save Order');
            }
        });
    });
});
</script>
