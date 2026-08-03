<div class="content-wrapper admin-manage-product-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-boxes mr-2 text-primary-theme"></i>Manage Products</h4>
                    <p class="text-muted mb-0 small">Every product listed across the marketplace, from every seller.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Products</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- ===== Product FAQs modal ===== -->
                <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id='product-faqs-modal'>
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Product FAQs</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="row">
                                    <div class="col-md-12 main-content">
                                        <div class="card content-area p-4">
                                            <div class="card-innr">
                                                <table class='table-striped' id='product-faqs-table' data-toggle="table"
                                                    data-url="<?= base_url('admin/product/get_faqs_list') ?>" data-click-to-select="true"
                                                    data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                                    data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                                                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                                                    data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]'
                                                    data-export-options='{"fileName": "product-faqs-list", "ignoreColumn": ["operate"]}'
                                                    data-query-params="queryParams">
                                                    <thead>
                                                        <tr>
                                                            <th data-field="id" data-sortable="true">ID</th>
                                                            <th data-field="user_id" data-sortable="false">User Id</th>
                                                            <th data-field="product_id" data-sortable="false">Product Id</th>
                                                            <th data-field="votes" data-sortable="false">Votes</th>
                                                            <th data-field="question" data-sortable="false">Question</th>
                                                            <th data-field="answer" data-sortable="false">Answer</th>
                                                            <th data-field="answered_by" data-sortable="false">Answered by</th>
                                                            <th data-field="username" data-width='500' data-sortable="false" class="col-md-6">Username</th>
                                                            <th data-field="date_added" data-sortable="false">Date added</th>
                                                            <th data-field="operate" data-sortable="false">Operate</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== Edit FAQ answer modal ===== -->
                <div id="product_faq_value_id" class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="editFaqLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editFaqLabel">Edit Product FAQ</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-0">
                                <form class="form-horizontal form-submit-event" id="product_edit_faq_form" action="<?= base_url('admin/product/edit_product_faqs'); ?>" method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <?php if (isset($fetched_data[0]['id'])) { ?>
                                            <input type="hidden" name="edit_product_faq" value="<?= @$fetched_data[0]['id'] ?>">
                                        <?php } ?>
                                        <div class="form-group row">
                                            <label for="question" class="col-sm-2 col-form-label">Question</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="question" placeholder="question" name="question" value="<?= @$fetched_data[0]['question'] ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="answer" class="col-sm-2 col-form-label">Answer <span class='text-danger text-sm'>*</span></label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="answer" placeholder="Answer" name="answer" value="<?= @$fetched_data[0]['answer'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Answer' : 'Add Answer' ?></button>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <div class="form-group" id="error_box"></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== Ratings modal ===== -->
                <div class="modal fade" id="product-rating-modal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Product Ratings</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <table class='table-striped' id="product-rating-table" data-toggle="table"
                                    data-url="<?= base_url('admin/product/get_rating_list') ?>" data-click-to-select="true"
                                    data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                    data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                                    data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                                    data-show-export="true" data-maintain-selected="true" data-query-params="ratingParams">
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-sortable="true">ID</th>
                                            <th data-field="username" data-width='500' data-sortable="false" class="col-md-6">Username</th>
                                            <th data-field="rating" data-sortable="false">Rating</th>
                                            <th data-field="comment" data-sortable="false">Comment</th>
                                            <th data-field="images" data-sortable="true">Images</th>
                                            <th data-field="data_added" data-sortable="false">Date added</th>
                                            <th data-field="operate" data-sortable="false">Operate</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== Product list ===== -->
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header product-page-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set"><i class="fas fa-boxes"></i></span>
                                <div>
                                    <h5 class="mb-0">All Products</h5>
                                    <small class="text-muted">Across every seller on the platform</small>
                                </div>
                            </div>
                            <a href="<?= base_url('admin/product/create_product') ?>" class="btn btn-primary-theme btn-sm">
                                <i class="fas fa-plus mr-1"></i>Add Product
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="product-filters-bar row align-items-end">
                                <div class="col-md-4 mb-2">
                                    <label for="category_parent" class="filter-label"><i class="fas fa-tag mr-1"></i>Product Category</label>
                                    <select id="category_parent" name="category_parent">
                                        <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'Select Categories' ?></option>
                                        <?php echo get_categories_option_html($categories); ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="status_filter" class="filter-label"><i class="fas fa-toggle-on mr-1"></i>Product Status</label>
                                    <select class='form-control' name='status' id="status_filter">
                                        <option value=''>Select Status</option>
                                        <option value='1'>Approved</option>
                                        <option value='2'>Not-Approved</option>
                                        <option value='0'>Deactivated</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="seller_filter" class="filter-label"><i class="fas fa-store mr-1"></i>Seller</label>
                                    <select class='form-control' name='seller_id' id="seller_filter">
                                        <option value="">All Sellers</option>
                                        <?php foreach ($sellers as $seller) :
                                            // A handful of seller accounts in this database have neither a
                                            // username nor a store name set, which previously rendered as a
                                            // blank, unselectable-looking option — indistinguishable from
                                            // every other blank one. Falls back to the account id so every
                                            // entry in the list can still be identified and picked.
                                            $seller_label = trim(($seller['seller_name'] ?? '') . (!empty($seller['store_name']) ? ' - ' . $seller['store_name'] : ''));
                                            if ($seller_label === '' || $seller_label === '-') {
                                                $seller_label = 'Seller #' . $seller['seller_id'];
                                            }
                                        ?>
                                            <option value="<?= (int) $seller['seller_id'] ?>"><?= html_escape($seller_label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <table class='table-striped' id='products_table' data-toggle="table"
                                data-url="<?= isset($_GET['flag']) ? base_url('admin/product/get_product_data?flag=') . html_escape($_GET['flag']) : base_url('admin/product/get_product_data') ?>"
                                data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                                data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                                data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true"
                                data-export-types='["txt","excel","csv"]'
                                data-export-options='{"fileName": "products-list", "ignoreColumn": ["state"]}'
                                data-query-params="product_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true" data-visible='false' data-align='center'>ID</th>
                                        <th data-field="image" data-sortable="true" data-align='center'>Image</th>
                                        <th data-field="name" data-sortable="false" data-align='center'>Name</th>
                                        <th data-field="brand" data-sortable="false" data-align='center'>Brand</th>
                                        <th data-field="category_name" data-sortable="false" data-align='center'>Category</th>
                                        <th data-field="rating" data-sortable="true" data-align='center'>Rating</th>
                                        <th data-field="variations" data-sortable="true" data-visible='false' data-align='center'>Variations</th>
                                        <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<style>
    .admin-manage-product-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-product-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-product-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-product-page .product-page-header {
        background: linear-gradient(to right, var(--color-secondary), #fff 65%);
        padding: 1rem 1.25rem;
    }
    .admin-manage-product-page .product-page-header h5 { font-weight: 700; color: #2b2f33; }
    .admin-manage-product-page .header-icon {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 17px; margin-right: 12px;
        box-shadow: 0 3px 8px rgba(242, 130, 46, 0.35);
    }
    .admin-manage-product-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-product-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .admin-manage-product-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .admin-manage-product-page .filter-label i { color: var(--color-orange); }

    .admin-manage-product-page #status_filter.form-control,
    .admin-manage-product-page #seller_filter.form-control,
    .admin-manage-product-page .select2-container--bootstrap4 .select2-selection {
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        min-height: 40px;
        box-shadow: none;
        box-sizing: border-box;
    }
    .admin-manage-product-page .select2-container--bootstrap4 .select2-selection--single { height: 40px !important; }
    .admin-manage-product-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { line-height: 38px; }
    .admin-manage-product-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        top: 0; bottom: 0; height: auto;
        display: flex; align-items: center; justify-content: center;
    }
    .admin-manage-product-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
        position: static; margin: 0;
        border-color: #888 transparent transparent transparent;
        border-style: solid; border-width: 5px 4px 0 4px;
        width: 0; height: 0;
    }
    .admin-manage-product-page #status_filter.form-control:focus,
    .admin-manage-product-page #seller_filter.form-control:focus,
    .admin-manage-product-page .select2-container--bootstrap4.select2-container--focus .select2-selection,
    .admin-manage-product-page .select2-container--bootstrap4.select2-container--open .select2-selection {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    .admin-manage-product-page .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
        background-color: var(--color-orange) !important;
    }
    .admin-manage-product-page .select2-container--open .select2-selection { visibility: hidden; }
    .admin-manage-product-page .select2-container--bootstrap4 .select2-dropdown {
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    }
    .admin-manage-product-page .select2-search--dropdown { padding: 0; }
    .admin-manage-product-page .select2-search--dropdown .select2-search__field {
        width: 100%; height: 38px; margin: 0; padding: 0 12px;
        border: none; border-radius: 8px 8px 0 0; outline: none; box-shadow: none;
    }
    .admin-manage-product-page .select2-results { border-top: 1px solid rgba(0,0,0,0.06); }

    .admin-manage-product-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
        border-radius: 6px;
    }
    .admin-manage-product-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-product-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-product-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-product-page .fixed-table-toolbar .btn-group > .btn,
    .admin-manage-product-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-manage-product-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-manage-product-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-manage-product-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-product-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-product-page table.table thead th {
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
    .admin-manage-product-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-product-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-product-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-product-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-product-page .action-btn { border-radius: 6px; }
    .admin-manage-product-page .modal-header { border-bottom: 2px solid var(--color-secondary); }
    .admin-manage-product-page .modal-title { color: #2b2f33; }
</style>

<script>
    // The admin panel's shared custom.js already wires up #category_parent (select2),
    // product_query_params, queryParams, ratingParams, the status-toggle and delete-product
    // handlers, the star-rating init for this table, and a change handler that refreshes
    // #products_table when #seller_filter changes. All that's missing here is giving the
    // seller dropdown the same searchable select2 styling as the category filter.
    $(function () {
        $('#seller_filter').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'All Sellers',
            allowClear: true
        });
    });
</script>
