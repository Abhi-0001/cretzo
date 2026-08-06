<div class="content-wrapper admin-manage-stock-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-boxes mr-2 text-primary-theme"></i>Stock Management</h4>
                    <p class="text-muted mb-0 small">Adjust inventory levels across every seller's products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Product Stock</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <div id="product_faq_value_id" class="modal fade edit-modal-lg " tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-m ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">Manage Stock</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body ">
                            <form class="form-horizontal form-submit-event" id="stock_adjustment_form" action="<?= base_url('admin/manage_stock/update_stock'); ?>" method="POST" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php if (isset($fetched_data['product'][0]['id'])) { ?>
                                        <input type="hidden" name="variant_id" value="<?= $this->input->get('edit_id') ?>">
                                    <?php  } ?>

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="product_name">Product </label>
                                                <input type="text" class="form-control" id="product_name" placeholder="Product name" name="product_name" value="<?= (isset($attribute[0]['value']) && !empty($attribute[0]['value']) && isset($fetched_data['product'][0]['stock_type']) && $fetched_data['product'][0]['stock_type'] != 1) ? html_escape($fetched_data['product'][0]['name']  . ' - ' . ' ' . $attribute[0]['value']) : ((isset($fetched_data['product'][0]['name'])) ? html_escape($fetched_data['product'][0]['name']) : '')   ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="form-group">
                                                <label for="current_stock"><?= labels('current_stock', 'Current Stock') ?></label>
                                                <input type="text" class="form-control current_stock" name="current_stock" id="current_stock" value="<?= (isset($fetched_data['product'][0]['stock']) && !empty($fetched_data['product'][0]['stock'])) ? $fetched_data['product'][0]['stock'] :  '0'  ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label for="quantity"><?= labels('quantity', 'Quantity') ?></label><span class="asterisk text-danger">*</span>
                                                <input type="number" class="form-control" name="quantity" id="quantity" min=1>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label for="type"><?= labels('type', 'Type') ?></label>
                                                <select class="form-control" id="type" name="type">
                                                    <option value='add'><?= labels('add', 'Add') ?></option>
                                                    <option value='subtract'><?= labels('subtract', 'Subtract') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary-theme" value="Save"><?= labels('update_stock', 'Update Stock') ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set mr-2"><i class="fas fa-boxes"></i></span>
                    <h5 class="mb-0">Products</h5>
                </div>
                <div class="card-body">
                    <div class="row product-filters-bar align-items-end mb-3">
                        <div class="col-md-4">
                            <label for="seller_filter" class="filter-label">Filter By Seller</label>
                            <select class='form-control' name='seller_id' id="seller_filter">
                                <option value="">All Sellers</option>
                                <?php foreach ($sellers as $seller) { ?>
                                    <option value="<?= $seller['seller_id'] ?>" <?= (isset($product_details[0]['seller_id']) && $product_details[0]['seller_id'] == $seller['seller_id']) ? 'selected' : "" ?>><?= html_escape($seller['seller_name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="category_parent" class="filter-label">Filter By Category</label>
                            <select class="form-control" id="category_parent" name="category_parent">
                                <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'All Categories' ?>
                                </option>
                                <?php
                                echo get_categories_option_html($categories);
                                ?>
                            </select>
                        </div>
                    </div>

                    <table class='table-striped' id='products_table' data-toggle="table"
                        data-url="<?= isset($_GET['flag']) ? base_url('admin/manage_stock/get_stock_list?flag=') . $_GET['flag'] : base_url('admin/manage_stock/get_stock_list') ?>"
                        data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                        data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                        data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true"
                        data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "products-list","ignoreColumn": ["state"] }'
                        data-query-params="stock_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>Variant ID</th>
                                <th data-field="name" data-sortable="false" data-align='center'>Name</th>
                                <th data-field="seller_name" data-sortable="false" data-visible="false">Seller Name</th>
                                <th data-field="category_name" data-sortable="false" data-visible="false">Category</th>
                                <th data-field="image" data-sortable="false" data-align='center'>Image</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Variants - Stock</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-manage-stock-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-stock-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-stock-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-stock-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-stock-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-stock-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-stock-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-stock-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .admin-manage-stock-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }

    .admin-manage-stock-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-stock-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-stock-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-stock-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-stock-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-manage-stock-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-stock-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-stock-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-stock-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-stock-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
