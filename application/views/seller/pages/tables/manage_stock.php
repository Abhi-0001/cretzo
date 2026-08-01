<div class="content-wrapper manage-stock-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-warehouse mr-2 text-primary-theme"></i>Manage Stock</h4>
                    <p class="text-muted mb-0 small">Adjust stock levels for your product variants.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
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
                            <form class="form-horizontal" id="stock_adjustment_form" action="<?= base_url('seller/manage_stock/update_stock'); ?>" method="POST" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php if (isset($fetched_data['product'][0]['id'])) { ?>
                                        <input type="hidden" name="variant_id" value="<?= $this->input->get('edit_id') ?>">
                                    <?php  } ?>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="product_name">Product </label>
                                                <input type="text" class="form-control" id="product_name" placeholder="Product name" name="product_name" value="<?= (isset($attribute[0]['value'], $fetched_data['product'][0]) && !empty($attribute[0]['value']) && $fetched_data['product'][0]['stock_type'] != 1) ? $fetched_data['product'][0]['name']  . ' - ' . ' ' . $attribute[0]['value'] : ($fetched_data['product'][0]['name'] ?? '') ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="current_stock"><?= labels('current_stock', 'Current Stock') ?></label>
                                                <input type="text" class="form-control current_stock" name="current_stock" id="current_stock" value="<?= (isset($fetched_data['product'][0]['stock']) && !empty($fetched_data['product'][0]['stock'])) ? $fetched_data['product'][0]['stock'] : ($fetched ?? '') ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="quantity"><?= labels('quantity', 'Quantity') ?></label><span class="asterisk text-danger">*</span>
                                                <input type="number" class="form-control" name="quantity" id="quantity" min=1>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="type"><?= labels('type', 'Type') ?></label>
                                                <select class="form-control" id="type" name="type">
                                                    <option value='add'><?= labels('add', 'Add') ?></option>
                                                    <option value='subtract'><?= labels('subtract', 'Subtract') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary-theme" id="stock_submit_btn" value="Save"><?= labels('update_stock', 'Update Stock') ?></button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-warehouse"></i></span>
                    <h5 class="mb-0">Stock</h5>
                </div>
                <div class="card-body">
                    <div class="product-filters-bar row align-items-end">
                        <div class="col-md-4 mb-2">
                            <label class="filter-label"><i class="fas fa-tag mr-1"></i>Product Category</label>
                            <select id="category_parent" name="category_parent">
                                <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'Select Categories' ?>
                                </option>
                                <?php
                                echo get_categories_option_html($categories);
                                ?>
                            </select>
                        </div>
                    </div>
                    <table class='table-striped' id='products_table' data-toggle="table" data-url="<?= base_url('seller/manage_stock/get_stock_list')  ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{"fileName": "products-list","ignoreColumn": ["state"] }' data-query-params="stock_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">Variant ID</th>
                                <th data-field="name" data-sortable="false">Name</th>
                                <th data-field="category_name" data-sortable="false" data-visible="false">Category</th>
                                <th data-field="image" data-sortable="false">Image</th>
                                <th data-field="operate" data-sortable="true">Variants - Stock</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .manage-stock-page .text-primary-theme { color: var(--color-orange); }

    .manage-stock-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .manage-stock-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .manage-stock-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .manage-stock-page .header-icon.bg-set { background: var(--color-orange); }

    .manage-stock-page .product-filters-bar {
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 1rem 1rem 0.25rem;
        margin: 0 0 1.25rem;
    }
    .manage-stock-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .manage-stock-page .filter-label i { color: var(--color-orange); }

    .manage-stock-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .manage-stock-page .select2-container--bootstrap4 .select2-selection {
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        min-height: 40px;
        box-shadow: none;
        box-sizing: border-box;
    }
    .manage-stock-page .select2-container--bootstrap4 .select2-selection--single {
        height: 40px !important;
    }
    .manage-stock-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    /* The stock select2 arrow is placed with a top:50%/margin-top combo tuned for
       the vendor theme's own fixed height; our custom border/height on the box threw
       that math off and left the triangle poking out below the box. Stretching the
       arrow to the full box height and centering its (now in-flow) triangle with
       flexbox sidesteps the percentage math entirely, so it can't drift again. */
    .manage-stock-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        top: 0;
        bottom: 0;
        height: auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .manage-stock-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
        position: static;
        margin: 0;
        border-color: #888 transparent transparent transparent;
        border-style: solid;
        border-width: 5px 4px 0 4px;
        width: 0;
        height: 0;
    }
    .manage-stock-page .select2-container--bootstrap4.select2-container--focus .select2-selection,
    .manage-stock-page .select2-container--bootstrap4.select2-container--open .select2-selection {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    .manage-stock-page .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
        background-color: var(--color-orange) !important;
    }

    /* Category dropdown: the search box should read as the same field as "Select
       Categories", not a second box stacked underneath it. #category_parent's open
       dropdown gets shifted up (via JS) to sit exactly over the selection, so the
       selection itself is hidden while open and the search input takes its place. */
    .manage-stock-page .select2-container--open .select2-selection {
        visibility: hidden;
    }
    .manage-stock-page .select2-container--bootstrap4 .select2-dropdown {
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    }
    .manage-stock-page .select2-search--dropdown {
        padding: 0;
    }
    .manage-stock-page .select2-search--dropdown .select2-search__field {
        width: 100%;
        height: 38px;
        margin: 0;
        padding: 0 12px;
        border: none;
        border-radius: 8px 8px 0 0;
        outline: none;
        box-shadow: none;
    }
    .manage-stock-page .select2-results {
        border-top: 1px solid rgba(0,0,0,0.06);
    }

    .manage-stock-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .manage-stock-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }

    .manage-stock-page .fixed-table-toolbar { margin-bottom: 10px; }
    .manage-stock-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .manage-stock-page .fixed-table-toolbar .btn-group > .btn,
    .manage-stock-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .manage-stock-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .manage-stock-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .manage-stock-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .manage-stock-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .manage-stock-page table.table thead th {
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
    .manage-stock-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .manage-stock-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .manage-stock-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .manage-stock-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .manage-stock-page .modal-header { border-bottom: 2px solid var(--color-secondary); }
</style>

<script>

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
    });

    $(document).on('click', '.edit_btn', function () {
        var id = $(this).data('id');
        var url = $(this).data('url');
        $('#product_faq_value_id').modal('show').find('.modal-body').load(base_url + url + '?edit_id=' + id + ' #stock_adjustment_form');
    });

    $(document).on('submit', '#stock_adjustment_form', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.append(csrfName, csrfHash);
        var submitBtn = $('#stock_submit_btn', form);
        var originalText = submitBtn.html();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            beforeSend: function () {
                submitBtn.html('Please Wait...').prop('disabled', true);
            },
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName;
                    csrfHash = result.csrfHash;
                }
                if (result.error) {
                    iziToast.error({ message: result.message });
                } else {
                    iziToast.success({ message: result.message });
                    $('#product_faq_value_id').modal('hide');
                    $('#products_table').bootstrapTable('refresh');
                }
            },
            error: function () {
                iziToast.error({ message: 'Something went wrong. Please try again.' });
            },
            complete: function () {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    $(document).on('change', '#category_parent', function () {
        $('#products_table').bootstrapTable('refresh');
    });

    function stock_query_params(p) {
        return {
            category_id: $('#category_parent').val(),
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }
</script>
