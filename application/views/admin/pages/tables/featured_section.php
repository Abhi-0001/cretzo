<div class="content-wrapper admin-featured-section-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-star mr-2 text-primary-theme"></i>Manage Featured Sections</h4>
                    <p class="text-muted mb-0 small">Curated product/category groupings shown on the storefront.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Featured Sections</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card mb-3">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-plus"></i></span>
                            <h5 class="mb-0"><?= (isset($fetched_data[0]['id'])) ? 'Edit Featured Section' : 'Add Featured Section' ?></h5>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/Featured_sections/add_featured_section'); ?>" method="POST" enctype="multipart/form-data">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" id="edit_featured_section" name="edit_featured_section" value="<?= @$fetched_data[0]['id'] ?>">
                                <input type="hidden" id="update_id" name="update_id" value="1">
                            <?php } ?>
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="title" class="control-label">Title for section <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="title" id="title" value="<?= (isset($fetched_data[0]['title']) ? html_escape($fetched_data[0]['title']) : '') ?>" placeholder="Title">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="short_description" class="control-label">Short description <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="short_description" id="short_description" value="<?= (isset($fetched_data[0]['short_description']) ? html_escape($fetched_data[0]['short_description']) : '') ?>" placeholder="Short description">
                                    </div>
                                </div>
                                <div class="form-group row select-categories">
                                    <label for="categories" class="control-label">Categories</label>
                                    <div class="col-md-12">
                                        <select name="categories[]" class=" select_multiple w-100" multiple data-placeholder=" Type to search and select categories">
                                            <option value=""><?= (isset($categories) && empty($categories)) ? 'No Categories Exist' : 'Select Categories' ?>
                                            </option>
                                            <?php
                                            $selected_val = (isset($fetched_data[0]['id']) &&  !empty($fetched_data[0]['id'])) ? $fetched_data[0]['categories'] : '';
                                            $selected_vals = explode(',', $selected_val);
                                            echo get_categories_option_html($categories, $selected_vals);

                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <?php
                                    $style = ['default', 'style_1', 'style_2', 'style_3', 'style_4', 'cretzo_trending', 'cretzo_best_seller', 'cretzo_featured', 'cretzo_new_arrivals', 'cretzo_special_picks'];
                                    ?>
                                    <label for="style" class="control-label">Style <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-md-12">
                                        <select name="style" class="form-control">
                                            <option value=" ">Select Style</option>
                                            <?php foreach ($style as $row) { ?>
                                                <option value="<?= $row ?>" <?= (isset($fetched_data[0]['style']) && $fetched_data[0]['style'] == $row) ? 'Selected' : '' ?>><?= ucwords(str_replace('_', ' ', $row)) ?></option>
                                            <?php } ?>
                                        </select>
                                        <?php ?>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <?php
                                    $product_type = ['new_added_products', 'products_on_sale', 'top_rated_products', 'most_selling_products', 'custom_products','digital_product'];
                                    ?>
                                    <label for="product_type" class="control-label">Product Types <span class='text-danger text-sm'> * </span></label>
                                    <div class="col-md-12">
                                        <select name="product_type" class="form-control product_type">
                                            <option value=" ">Select Types</option>
                                            <?php foreach ($product_type as $row) { ?>
                                                <option value="<?= $row ?>" <?= (isset($fetched_data[0]['id']) &&  $fetched_data[0]['product_type'] == $row) ? "Selected" : "" ?>><?= ucwords(str_replace('_', ' ', $row)) ?></option>
                                            <?php
                                            } ?>
                                        </select>
                                        <?php ?>
                                    </div>
                                </div>

                                <!-- for custom product -->

                                <div class="form-group row custom_products <?= (isset($fetched_data[0]['id'])  && $fetched_data[0]['product_type'] == 'custom_products') ? '' : 'd-none' ?>">
                                    <label for="product_ids" class="control-label">Products *</label>
                                    <div class="col-md-12">
                                        <select name="product_ids[]" class="search_admin_product w-100" multiple data-placeholder=" Type to search and select products" onload="multiselect()">
                                            <?php
                                            if (isset($fetched_data[0]['id'])) {
                                                $product_id = explode(",", $fetched_data[0]['product_ids']);

                                                foreach ($product_details as $row) {
                                            ?>
                                                    <option value="<?= $row['id'] ?>" selected><?= html_escape($row['name']) ?></option>
                                            <?php
                                                }
                                            }

                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- for digital product -->

                                <div class="form-group row digital_products <?= (isset($fetched_data[0]['id'])  && $fetched_data[0]['product_type'] == 'digital_product') ? '' : 'd-none' ?>">
                                    <label for="digital_product_ids" class="control-label">Products *</label>
                                    <div class="col-md-12">
                                        <select name="digital_product_ids[]" class="search_admin_digital_product w-100" multiple data-placeholder=" Type to search and select products" onload="multiselect()">
                                            <?php
                                            if (isset($fetched_data[0]['id'])) {
                                                $product_id = explode(",", $fetched_data[0]['product_ids']);
                                               
                                                foreach ($product_details as $row) {
                                                    
                                            ?>
                                                    <option value="<?= $row['id'] ?>" selected><?= html_escape($row['name']) ?></option>
                                            <?php
                                                }
                                            }

                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Fetured Section' : 'Add Fetured Section' ?></button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center form-group">
                                <div id="error_box">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!--/.card-->
            </div>
            <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">Edit Fetured Section Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 main-content">
                <div class="card attribute-card">
                    <div class="card-header attribute-card-header">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-list"></i></span>
                        <h5 class="mb-0">Featured Sections</h5>
                    </div>
                    <div class="card-body">
                        <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/Featured_sections/get_section_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-export-options='{"fileName": "featured-section-list", "ignoreColumn": ["operate"]}' data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="title" data-sortable="true">Title</th>
                                    <th data-field="short_description" data-sortable="false">Short description</th>
                                    <th data-field="style" data-sortable="true">Style</th>
                                    <th data-field="categories" data-sortable="false" data-visible="false">Categories</th>
                                    <th data-field="product_ids" data-sortable="false" data-visible="false">Product ids</th>
                                    <th data-field="product_type" data-sortable="true">Product Type</th>
                                    <!-- Featured sections gained a publish flag (migration 046); this
                                         column surfaces it next to the Activate/Deactivate button. -->
                                    <th data-field="status" data-sortable="true">Status</th>
                                    <th data-field="date" data-sortable="true">Date</th>
                                    <th data-field="operate">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
</div><!-- /.container-fluid -->
</section>

<style>
    .admin-featured-section-page .text-primary-theme { color: var(--color-orange); }

    .admin-featured-section-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-featured-section-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .admin-featured-section-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-featured-section-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-featured-section-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-featured-section-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-featured-section-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-featured-section-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-featured-section-page table.table thead th {
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
    .admin-featured-section-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-featured-section-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-featured-section-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-featured-section-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }
    .admin-featured-section-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
<!-- /.content -->
</div>