<div class="content-wrapper manage-product-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-boxes mr-2 text-primary-theme"></i>Manage Products</h4>
                    <p class="text-muted mb-0 small">View, filter, and manage everything you're selling.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Products</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="modal fade " tabindex="-1" role="dialog" aria-hidden="true" id='product-faqs-modal'>
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">View Products Faqs</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="row">
                                    <div class="col-md-12 main-content">
                                        <div class="card content-area p-4">
                                            <div class="card-innr">
                                                <div class="gaps-1-5x"></div>
                                                <table class='table-striped' id='product-faqs-table' data-toggle="table" data-url="<?= base_url('seller/product/get_faqs_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-export-options='{
                        "fileName": "product-faqs-list",
                        "ignoreColumn": ["operate"] 
                        }' data-query-params="queryParams">
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
                                            </div><!-- .card-innr -->
                                        </div><!-- .card -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="product_faq_value_id" class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLongTitle">Edit Product FAQs</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body p-0">
                                <form class="form-horizontal form-submit-event" id="product_edit_faq_form" action="<?= base_url('seller/product/edit_product_faqs'); ?>" method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <?php
                                        if (isset($fetched_data[0]['id'])) { ?>
                                            <input type="hidden" name="edit_product_faq" value="<?= @$fetched_data[0]['id'] ?>">
                                        <?php  } ?>
                                        <div class="form-group row">
                                            <label for="question" class="col-sm-2 col-form-label">Question </label>
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


                                        <div class="form-group">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Product Faq' : 'Add Product FAQ' ?></button>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <div class="form-group" id="error_box">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="product-rating-modal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">View Product Rating</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="tab-pane " role="tabpanel" aria-labelledby="product-rating-tab">
                                    <table class='table-striped' id="product-rating-table" data-toggle="table" data-url="<?= base_url('seller/product/get_rating_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-query-params="ratingParams">
                                        <thead>
                                            <tr>
                                                <th data-field="id" data-sortable="true">ID</th>
                                                <th data-field="username" data-width='500' data-sortable="false" class="col-md-6">Username</th>
                                                <th data-field="rating" data-sortable="false">Rating</th>
                                                <th data-field="comment" data-sortable="false">Comment</th>
                                                <th data-field="images" data-sortable="true">Images</th>
                                                <th data-field="data_added" data-sortable="false">Data added</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header product-page-header d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center">
                                <span class="header-icon bg-set"><i class="fas fa-boxes"></i></span>
                                <div>
                                    <h5 class="mb-0">Your Products</h5>
                                    <small class="text-muted">Everything you're currently selling, in one place</small>
                                </div>
                            </div>
                            <a href="<?= base_url() . 'seller/product/create_product' ?>" class="btn btn-primary-theme btn-sm"><i class="fas fa-plus mr-1"></i>Add Product</a>
                        </div>
                        <div class="card-body">
                            <div class="product-filters-bar row align-items-end">
                                <div class="col-md-4 mb-2">
                                    <label for="category_parent" class="filter-label"><i class="fas fa-tag mr-1"></i>Product Category</label>
                                    <select id="category_parent" name="category_parent">
                                        <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'Select Categories' ?>
                                        </option>
                                        <?php
                                        echo get_categories_option_html($categories);
                                        ?>
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
                            </div>
                            <table class='table-striped' id='products_table' data-toggle="table" data-url="<?= isset($_GET['flag']) ? base_url('seller/product/get_product_data?flag=') . $_GET['flag'] : base_url('seller/product/get_product_data') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "products-list","ignoreColumn": ["state"] }' data-query-params="product_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true" data-visible='false'>ID</th>
                                        <th data-field="image" data-sortable="true">Image</th>
                                        <th data-field="name" data-sortable="false">Name</th>
                                        <th data-field="rating" data-sortable="true">Rating</th>
                                        <th data-field="variations" data-sortable="true" data-visible='true'>Variations</th>
                                        <th data-field="operate" data-sortable="true">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div><!-- .card-body -->
                    </div><!-- .card -->
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .manage-product-page .text-primary-theme { color: var(--color-orange); }

    .manage-product-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .manage-product-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .manage-product-page .product-page-header {
        background: linear-gradient(to right, var(--color-secondary), #fff 65%);
        padding: 1rem 1.25rem;
    }
    .manage-product-page .product-page-header h5 {
        font-weight: 700;
        color: #2b2f33;
    }
    .manage-product-page .header-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 17px;
        margin-right: 12px;
        box-shadow: 0 3px 8px rgba(242, 130, 46, 0.35);
    }
    .manage-product-page .header-icon.bg-set { background: var(--color-orange); }

    .manage-product-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .manage-product-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .manage-product-page .filter-label i {
        color: var(--color-orange);
    }

    .manage-product-page #status_filter.form-control,
    .manage-product-page .select2-container--bootstrap4 .select2-selection {
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        min-height: 40px;
        box-shadow: none;
        box-sizing: border-box;
    }
    .manage-product-page .select2-container--bootstrap4 .select2-selection--single {
        height: 40px !important;
    }
    .manage-product-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    /* The stock select2 arrow is placed with a top:50%/margin-top combo tuned for
       the vendor theme's own fixed height; our custom border/height on the box threw
       that math off and left the triangle poking out below the box. Stretching the
       arrow to the full box height and centering its (now in-flow) triangle with
       flexbox sidesteps the percentage math entirely, so it can't drift again. */
    .manage-product-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        top: 0;
        bottom: 0;
        height: auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .manage-product-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
        position: static;
        margin: 0;
        border-color: #888 transparent transparent transparent;
        border-style: solid;
        border-width: 5px 4px 0 4px;
        width: 0;
        height: 0;
    }
    .manage-product-page #status_filter.form-control:focus,
    .manage-product-page .select2-container--bootstrap4.select2-container--focus .select2-selection,
    .manage-product-page .select2-container--bootstrap4.select2-container--open .select2-selection {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    .manage-product-page .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
        background-color: var(--color-orange) !important;
    }

    /* Category dropdown: the search box should read as the same field as "Select
       Categories", not a second box stacked underneath it. #category_parent's open
       dropdown gets shifted up (via JS) to sit exactly over the selection, so the
       selection itself is hidden while open and the search input takes its place. */
    .manage-product-page .select2-container--open .select2-selection {
        visibility: hidden;
    }
    .manage-product-page .select2-container--bootstrap4 .select2-dropdown {
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    }
    .manage-product-page .select2-search--dropdown {
        padding: 0;
    }
    .manage-product-page .select2-search--dropdown .select2-search__field {
        width: 100%;
        height: 38px;
        margin: 0;
        padding: 0 12px;
        border: none;
        border-radius: 8px 8px 0 0;
        outline: none;
        box-shadow: none;
    }
    .manage-product-page .select2-results {
        border-top: 1px solid rgba(0,0,0,0.06);
    }

    .manage-product-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
        border-radius: 6px;
    }
    .manage-product-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .manage-product-page .form-control:focus,
    .manage-product-page .select2-container--bootstrap4 .select2-selection:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    /* --- simplified bootstrap-table look (matches other redesigned pages) --- */
    .manage-product-page .fixed-table-toolbar {
        margin-bottom: 10px;
    }
    .manage-product-page .fixed-table-toolbar > div {
        margin-left: 10px !important;
    }
    .manage-product-page .fixed-table-toolbar .btn-group > .btn,
    .manage-product-page .fixed-table-toolbar .btn-group > .keep-open {
        margin-left: 8px !important;
    }
    .manage-product-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .manage-product-page .fixed-table-toolbar .btn-group > .keep-open:first-child {
        margin-left: 0 !important;
    }
    .manage-product-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .manage-product-page .fixed-table-toolbar .search input:focus {
        border-color: var(--color-orange);
    }
    .manage-product-page table.table thead th {
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
    .manage-product-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .manage-product-page table.table tbody tr:hover {
        background-color: var(--color-orange-light);
    }
    .manage-product-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .manage-product-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .manage-product-page .action-btn {
        border-radius: 6px;
    }

    .manage-product-page .modal-header {
        border-bottom: 2px solid var(--color-secondary);
    }
    .manage-product-page .modal-title {
        color: #2b2f33;
    }
</style>

<script>
    // The category/status filters above only work if (a) #category_parent is styled the
    // same way as #status_filter, and (b) changing either one actually re-queries the
    // products table with the selected values. Neither was wired up on the seller side
    // (that JS only ever shipped for the admin panel), so both are set up here.
    $(function () {
        $('#category_parent').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: '<?= (isset($categories) && empty($categories)) ? "No Categories Exist" : "Select Categories" ?>',
            allowClear: true
        });

        // Pull the dropdown's search field up over the "Select Categories" box itself
        // (instead of leaving it as its own row below) so typing happens in the same
        // spot the box already occupies.
        $('#category_parent').on('select2:open', function () {
            var height = $(this).data('select2').$container.find('.select2-selection').outerHeight();
            setTimeout(function () {
                var $dropdown = $('.select2-container--open .select2-dropdown');
                if ($dropdown.hasClass('select2-dropdown--above')) return;
                $dropdown.css('margin-top', -height + 'px');
                $dropdown.find('.select2-search__field').css('height', height + 'px');
            }, 0);
        });

        $(document).on('change', '#category_parent, #status_filter', function () {
            $('#products_table').bootstrapTable('refresh');
        });
    });

    function product_query_params(p) {
        return {
            category_id: $('#category_parent').val(),
            status: $('#status_filter').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    // The rating column's <input class="kv-fa"> only renders as stars once the
    // star-rating plugin initializes it — custom.js does that on document.ready, but
    // these rows are injected later via bootstrap-table's AJAX load, so without
    // re-running init here on every (re)load the input is left showing its raw
    // "loading" state instead of stars. Mirrors the admin panel's custom.js handlers.
    function init_kv_fa_rating() {
        $('.kv-fa').rating({
            theme: 'krajee-fa',
            filledStar: '<i class="fas fa-star"></i>',
            emptyStar: '<i class="far fa-star"></i>',
            showClear: false,
            size: 'md'
        });
    }

    $(document).on('load-success.bs.table', '#products_table', init_kv_fa_rating);
    $(document).on('column-switch.bs.table', '#products_table', init_kv_fa_rating);
    $(document).on('load-success.bs.table', '#product-rating-table', init_kv_fa_rating);

    // FAQs and Ratings modal tables below need their own query-params functions for the
    // same reason product_query_params above does — the shared queryParams/ratingParams
    // helpers only ever shipped in the admin-only custom.js, never loaded on seller pages.
    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    function ratingParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    // Deactivate/Activate/Not-Approved toggle and Delete buttons on each row had no click
    // handler at all on the seller side (same custom.js-not-loaded root cause) — wired up
    // here following the pattern already used in manage-product-faqs.php.
    $(document).on('click', '.update_active_status', function () {
        var id = $(this).data('id');
        var table = $(this).data('table');
        var status = $(this).data('status');
        $.ajax({
            type: 'GET',
            url: base_url + 'seller/home/update_status',
            data: { id: id, table: table, status: status },
            dataType: 'json'
        }).done(function (response) {
            if (response.csrfName && response.csrfHash) {
                csrfName = response.csrfName;
                csrfHash = response.csrfHash;
            }
            if (response.error === true) {
                iziToast.success({ message: '<span style="text-transform:capitalize">' + response.message + '</span> Status Updated' });
            } else {
                iziToast.error({ message: '<span style="text-transform:capitalize">' + response.message + '</span> Status Not Updated' });
            }
            $('#products_table').bootstrapTable('refresh');
        }).fail(function () {
            iziToast.error({ message: 'Something went wrong. Please try again.' });
        });
    });

    $(document).on('click', '.delete-product', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Are You Sure!',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                type: 'GET',
                url: base_url + 'seller/product/delete_product',
                data: { id: id },
                dataType: 'json'
            }).done(function (response) {
                if (response.csrfName && response.csrfHash) {
                    csrfName = response.csrfName;
                    csrfHash = response.csrfHash;
                }
                if (response.error === false) {
                    Swal.fire('Deleted!', response.message, 'success');
                } else {
                    Swal.fire('Oops...', response.message, 'error');
                }
                $('#products_table').bootstrapTable('refresh');
            }).fail(function () {
                Swal.fire('Oops...', 'Something went wrong!', 'error');
            });
        });
    });
</script>