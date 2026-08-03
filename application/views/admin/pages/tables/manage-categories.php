<div class="content-wrapper admin-manage-blog-category-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-tags mr-2 text-primary-theme"></i>Manage Blog Categories</h4>
                    <p class="text-muted mb-0 small">The categories blog posts are organised under.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Blog Categories</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">

            <!-- Dead leftover markup: nothing on this page ever opens this modal (the edit link
                 below navigates to a real page instead). -->
            <div class="modal fade edit-modal-lg" id="category_form" tabindex="-1" role="dialog" aria-labelledby="editBlogCategoryLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editBlogCategoryLabel">Edit Category</h5>
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
                        <span class="header-icon bg-set"><i class="fas fa-tags"></i></span>
                        <h5 class="mb-0">Blog Categories</h5>
                    </div>
                    <a href="<?= base_url('admin/blogs/create-category') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Category
                    </a>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='category_table' data-toggle="table"
                        data-url="<?= base_url('admin/blogs/view_categories') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "blog-category-list", "ignoreColumn": ["operate"]}'
                        data-query-params="category_query_params">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-visible='false'>ID</th>
                                <th data-field="name" data-sortable="true" data-align='center'>Name</th>
                                <th data-field="image" data-sortable="true" data-align='center'>Image</th>
                                <th data-field="banner" data-sortable="true" data-align='center'>Banner</th>
                                <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-manage-blog-category-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-blog-category-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-blog-category-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-blog-category-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-blog-category-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-blog-category-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-blog-category-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-blog-category-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-blog-category-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-blog-category-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-blog-category-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-blog-category-page table.table thead th {
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
    .admin-manage-blog-category-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-blog-category-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-blog-category-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-blog-category-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-blog-category-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-blog-category-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
