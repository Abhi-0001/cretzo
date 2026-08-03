<div class="content-wrapper admin-product-order-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sort mr-2 text-primary-theme"></i>Product Display Order</h4>
                    <p class="text-muted mb-0 small">Drag to set the order products appear in on the storefront.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/product') ?>">Products</a></li>
                        <li class="breadcrumb-item active">Display Order</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card mb-3">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-filter"></i></span>
                    <h5 class="mb-0">Filter</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-2">
                            <label for="category_parent" class="filter-label">Product Category</label>
                            <select name="category_parent" id="category_parent" class="form-control">
                                <option value="0">All Categories</option>
                                <?php echo get_categories_option_html($categories); ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="product_order_search_input" class="filter-label">Find a Product</label>
                            <input type="text" class="form-control" id="product_order_search_input" placeholder="Type to find a product in the list below">
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-primary-theme btn-block" id="row_order_search">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-list"></i></span>
                        <h5 class="mb-0">Products <span class="text-muted font-weight-normal" id="product_order_count"></span></h5>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" id="save_product_order">
                        <i class="fas fa-save mr-1"></i>Save Order
                    </button>
                </div>
                <div class="card-body">
                    <div class="row product-order-header font-weight-bold mb-2 d-none d-md-flex">
                        <div class="col-md-1 text-center">Order</div>
                        <div class="col-md-2 text-center">Image</div>
                        <div class="col-md-6">Product</div>
                        <div class="col-md-3 text-center">Status</div>
                    </div>

                    <div id="product_order_loading" class="product-order-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading products...</p>
                    </div>
                    <div id="product_order_empty" class="product-order-state d-none">
                        <i class="fas fa-box-open"></i>
                        <p>No products found.</p>
                    </div>

                    <ul class="list-group order-container d-none" id="product_order_sortable"></ul>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-product-order-page .text-primary-theme { color: var(--color-orange); }

    .admin-product-order-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-product-order-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-product-order-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-product-order-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-product-order-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }

    .admin-product-order-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-product-order-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .admin-product-order-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }

    .admin-product-order-page .product-order-header { color: var(--color-grey); font-size: 13px; text-transform: uppercase; letter-spacing: .3px; }

    .admin-product-order-page .product-order-state {
        padding: 50px 0;
        text-align: center;
        color: var(--color-grey);
    }
    .admin-product-order-page .product-order-state i { font-size: 30px; margin-bottom: 10px; display: block; opacity: .5; }
    .admin-product-order-page .product-order-state p { margin: 0; }

    .admin-product-order-page .order-container { max-height: 65vh; overflow-y: auto; }
    .admin-product-order-page .product-order-item {
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        margin-bottom: 8px;
        padding: 10px 12px;
        background: #fff;
        cursor: grab;
    }
    .admin-product-order-page .product-order-item:active { cursor: grabbing; }
    .admin-product-order-page .product-order-item.ui-sortable-helper { box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
    .admin-product-order-page .product-order-placeholder {
        border: 2px dashed var(--color-orange);
        border-radius: 8px;
        margin-bottom: 8px;
        background: var(--color-orange-light);
    }
    .admin-product-order-page .product-order-item.filtered-out { display: none; }
    .admin-product-order-page .product-order-handle { color: var(--color-grey); margin-right: 4px; }
    .admin-product-order-page .product-order-image {
        width: 48px; height: 48px; border-radius: 6px; object-fit: cover;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-product-order-page .product-order-name { font-size: 14px; font-weight: 500; }
</style>

<script>
$(function () {
    var $list = $('#product_order_sortable');
    var $loading = $('#product_order_loading');
    var $empty = $('#product_order_empty');
    var $count = $('#product_order_count');
    var sortableReady = false;

    function itemHtml(product) {
        var statusBadge = product.status === 1
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-danger">Deactivated</span>';

        // Product names are escaped server-side (html_escape) before this JSON is built, so
        // they are safe to place directly here - unlike the page's previous implementation,
        // which concatenated the raw name straight into the page's HTML with no escaping at all.
        return '<li class="list-group-item product-order-item d-flex align-items-center" id="product_id-' + product.id + '">' +
            '<div class="col-md-1 col-2 text-center"><i class="fas fa-grip-vertical product-order-handle"></i></div>' +
            '<div class="col-md-2 col-3 text-center"><img src="' + product.image + '" class="product-order-image" alt=""></div>' +
            '<div class="col-md-6 col-4 product-order-name">' + product.name + '</div>' +
            '<div class="col-md-3 col-3 text-center">' + statusBadge + '</div>' +
            '</li>';
    }

    function renderProducts(products) {
        if (!products.length) {
            $list.addClass('d-none').empty();
            $empty.removeClass('d-none');
            $count.text('');
            return;
        }

        $empty.addClass('d-none');
        $count.text('(' + products.length + ')');

        var html = '';
        for (var i = 0; i < products.length; i++) {
            html += itemHtml(products[i]);
        }
        $list.html(html).removeClass('d-none');

        // jQuery UI's .sortable('serialize') reads each item's id in the form "product_id-5"
        // and turns it into product_id[]=5 in submission order - exactly what
        // update_product_order() expects. Initialized once and left running: replacing the
        // list's contents (on filter) does not require destroying and recreating it.
        if (!sortableReady) {
            $list.sortable({
                axis: 'y',
                placeholder: 'product-order-placeholder',
                handle: '.product-order-handle',
                forcePlaceholderSize: true
            });
            sortableReady = true;
        } else {
            $list.sortable('refresh');
        }

        applySearchFilter();
    }

    function loadProducts(categoryId) {
        $loading.removeClass('d-none');
        $empty.addClass('d-none');
        $list.addClass('d-none');

        $.ajax({
            type: 'GET',
            url: "<?= base_url('admin/product/search_category_wise_products') ?>",
            data: { cat_id: categoryId },
            dataType: 'json',
            success: function (result) {
                $loading.addClass('d-none');
                renderProducts((result && result.data) || []);
            },
            error: function () {
                $loading.addClass('d-none');
                iziToast.error({ message: 'Products could not be loaded. Please try again.' });
            }
        });
    }

    function applySearchFilter() {
        var term = $('#product_order_search_input').val().trim().toLowerCase();
        $list.find('.product-order-item').each(function () {
            var name = $(this).find('.product-order-name').text().toLowerCase();
            $(this).toggleClass('filtered-out', term.length > 0 && name.indexOf(term) === -1);
        });
    }

    $('#row_order_search').on('click', function () {
        loadProducts($('#category_parent').val());
    });

    // Purely a client-side visibility filter over whatever the category filter has already
    // loaded - it does not re-fetch anything, so it works instantly as you type.
    $('#product_order_search_input').on('input', applySearchFilter);

    $('#save_product_order').on('click', function () {
        var $btn = $(this);
        var data = $list.sortable('serialize');

        $.ajax({
            type: 'GET',
            url: "<?= base_url('admin/product/update_product_order') ?>",
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

    loadProducts(0);
});
</script>
