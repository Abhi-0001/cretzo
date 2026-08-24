<div class="content-wrapper manage-category-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sitemap mr-2 text-primary-theme"></i>Categories</h4>
                    <p class="text-muted mb-0 small">Categories your products can be listed under.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Category</li>
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
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary-theme active" autofocus="autofocus" id='list_view'><i class="fas fa-list mr-1"></i>List View</button>
                        <button type="button" class="btn btn-outline-primary-theme" id='tree_view'><i class="fas fa-stream mr-1"></i>Tree View</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="list_view_html">
                        <table class='table-striped' id='category_table' data-toggle="table" data-url="<?= base_url('seller/category/category_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{
                        "fileName": "categories-list",
                        "ignoreColumn": ["state"]
                        }'>
                            <thead>
                                <tr>
                                    <?php // Shown rather than hidden: the category id is what the bulk
                                          // upload CSV's category_id column needs, and this table is
                                          // where a seller looks it up. ?>
                                    <th data-field="id" data-sortable="true" data-align='center'>Category ID</th>
                                    <th data-field="name" data-sortable="false" data-align='center'>Name</th>
                                    <th data-field="image" data-sortable="true" data-align='center'>Image</th>
                                    <?php // Banner column removed: `categories`.`banner` is not rendered by any
                                          // storefront theme, so this was a wall of NO IMAGE placeholders for a
                                          // picture nothing displays - the same removal already made on the admin
                                          // screen. Only the <th> is gone (the column is what bootstrap-table
                                          // builds from), so no data was touched and Category_model still puts
                                          // `banner` in the payload for the mobile app. Re-add this one <th> to
                                          // bring the column back. ?>
                                    <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div id="tree_view_html" class="d-none"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .manage-category-page .text-primary-theme { color: var(--color-orange); }

    .manage-category-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .manage-category-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .manage-category-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        margin-right: 10px;
    }
    .manage-category-page .header-icon.bg-set { background: var(--color-orange); }

    .manage-category-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .manage-category-page .btn-primary-theme:hover,
    .manage-category-page .btn-primary-theme:focus {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }
    .manage-category-page .btn-outline-primary-theme {
        background: #fff;
        border-color: var(--color-orange);
        color: var(--color-orange-dark);
        font-weight: 600;
    }
    .manage-category-page .btn-outline-primary-theme:hover,
    .manage-category-page .btn-outline-primary-theme.active {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
    }

    .manage-category-page .fixed-table-toolbar { margin-bottom: 10px; }
    .manage-category-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .manage-category-page .fixed-table-toolbar .btn-group > .btn,
    .manage-category-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .manage-category-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .manage-category-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .manage-category-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .manage-category-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .manage-category-page table.table thead th {
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
    .manage-category-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .manage-category-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .manage-category-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .manage-category-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>

<script>
    // The List/Tree view toggle had no click handler at all for sellers (it only shipped
    // in the admin JS bundle, which isn't loaded here), so clicking "Tree View" did
    // nothing. Wired up directly here instead.
    var treeLoaded = false;

    $(document).on('click', '#list_view', function () {
        $(this).addClass('active');
        $('#tree_view').removeClass('active');
        $('#list_view_html').removeClass('d-none');
        $('#tree_view_html').addClass('d-none');
    });

    $(document).on('click', '#tree_view', function () {
        $(this).addClass('active');
        $('#list_view').removeClass('active');
        $('#tree_view_html').removeClass('d-none');
        $('#list_view_html').addClass('d-none');

        if (treeLoaded) return;
        treeLoaded = true;

        $.ajax({
            type: 'GET',
            url: base_url + 'seller/category/get_seller_categories',
            dataType: 'json',
            success: function (result) {
                $('#tree_view_html').jstree({
                    'core': {
                        'data': result.data
                    }
                });
            }
        });
    });
</script>
