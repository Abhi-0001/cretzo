<div class="content-wrapper admin-manage-category-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sitemap mr-2 text-primary-theme"></i>Manage Categories</h4>
                    <p class="text-muted mb-0 small">The category tree products are organised under across the marketplace.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Categories</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <!-- Edit modal. The shared edit_btn handler in custom.js loads the edit form into
                 .edit-modal-lg .modal-body, so this markup has to stay on the page. -->
            <div class="modal fade edit-modal-lg" id="category_form" tabindex="-1" role="dialog" aria-labelledby="editCategoryLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCategoryLabel">Edit Category</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-0"></div>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-sitemap"></i></span>
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="d-flex align-items-center flex-wrap category-header-actions">
                        <div class="btn-group mr-2 mb-2" role="group">
                            <button type="button" class="btn btn-outline-primary-theme btn-sm active" autofocus="autofocus" id='list_view'>
                                <i class="fas fa-list mr-1"></i>List View
                            </button>
                            <button type="button" class="btn btn-outline-primary-theme btn-sm" id='tree_view'>
                                <i class="fas fa-stream mr-1"></i>Tree View
                            </button>
                        </div>
                        <a href="<?= base_url('admin/category/create-category') ?>" class="btn btn-primary-theme btn-sm mb-2">
                            <i class="fas fa-plus mr-1"></i>Add Category
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="list_view_html">
                        <table class='table-striped' id='category_table' data-toggle="table"
                            data-url="<?= $base_category_url ?>" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                            data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                            data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                            data-export-options='{"fileName": "category-list", "ignoreColumn": ["operate"]}'
                            data-query-params="category_query_params">
                            <thead>
                                <tr>
                                    <?php // Shown rather than hidden: the category id is what the bulk
                                          // upload CSV's category_id column needs, and this table is where it
                                          // gets looked up. ?>
                                    <th data-field="id" data-sortable="true" data-align='center'>Category ID</th>
                                    <th data-field="name" data-sortable="true" data-align='center'>Name</th>
                                    <th data-field="image" data-sortable="false" data-align='center'>Image</th>
                                    <?php // Banner column removed: `categories`.`banner` is not rendered by ANY
                                          // storefront theme (checked cretzo, classic and modern), so the column was
                                          // a wall of NO IMAGE placeholders for a picture nothing displays. Only the
                                          // <th> is gone - the column is what bootstrap-table builds from - so no
                                          // data was touched: 32 categories still have a banner stored, the upload
                                          // field is still on the add/edit form, and Category_model still puts
                                          // `banner` in the payload (the mobile app's get_categories response
                                          // carries it). Re-add this one <th> to bring the column back.
                                          // NOTE: blog categories are a separate screen (tables/manage-categories.php,
                                          // via admin/Blogs.php) and keep their own banner column. ?>
                                    <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                    <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <?php
                    // Not given a d-none class: the existing #list_view/#tree_view toggle
                    // (assets/admin/custom/custom.js) uses jQuery's .show()/.hide(), which sets
                    // an inline style - Bootstrap's .d-none uses !important, which an inline
                    // style cannot override. Starting hidden via a class would have made the
                    // "Tree View" button stop working (the .show() call would be silently
                    // beaten by the !important rule). A short inline style matches how the
                    // toggle actually manipulates visibility.
                    ?>
                    <div id="tree_view_html" style="display:none;"></div>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-manage-category-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-category-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-category-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }
    .admin-manage-category-page .btn-outline-primary-theme {
        border: 1px solid var(--color-orange);
        color: var(--color-orange-dark);
        font-weight: 600;
        background: #fff;
    }
    .admin-manage-category-page .btn-outline-primary-theme:hover,
    .admin-manage-category-page .btn-outline-primary-theme.active {
        background: var(--color-orange);
        color: #fff;
    }

    .admin-manage-category-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-category-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-category-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-category-page .header-icon.bg-set { background: var(--color-orange); }
    .admin-manage-category-page .category-header-actions { gap: 4px; }

    .admin-manage-category-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-category-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-category-page .fixed-table-toolbar .btn-group > .btn,
    .admin-manage-category-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-manage-category-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-manage-category-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-manage-category-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-category-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-category-page table.table thead th {
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
    .admin-manage-category-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-category-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-category-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-category-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-category-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-category-page .action-btn { display: inline-block; vertical-align: middle; }

    #tree_view_html { min-height: 120px; padding: 8px 4px; }
</style>

<script>
    // Keeps the two toggle buttons' pressed/unpressed styling in sync with which view is
    // actually showing - previously both buttons used the same static "btn-primary" look
    // regardless of which one was active, so there was no visual indication of which mode
    // you were in.
    $(document).on('click', '#list_view', function () {
        $('#list_view').addClass('active');
        $('#tree_view').removeClass('active');
    });
    $(document).on('click', '#tree_view', function () {
        $('#tree_view').addClass('active');
        $('#list_view').removeClass('active');
    });
</script>
