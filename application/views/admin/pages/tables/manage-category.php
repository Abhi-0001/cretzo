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


            <!-- Bulk add: many categories under one parent in one go. -->
            <div class="modal fade" id="bulk_category_modal" tabindex="-1" role="dialog" aria-labelledby="bulkCategoryLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bulkCategoryLabel"><i class="fas fa-layer-group mr-2 text-primary-theme"></i>Bulk Add Categories</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="bulk_parent" class="font-weight-bold">Add under</label>
                                <select id="bulk_parent" class="form-control">
                                    <option value="">None (create main categories)</option>
                                    <?= get_categories_option_html($categories) ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label for="bulk_names" class="font-weight-bold">Category names <small class="text-muted font-weight-normal">- one per line, paste a list from Excel or Notes</small></label>
                                <textarea id="bulk_names" class="form-control" rows="9" placeholder="Sandals&#10;Flats&#10;Heels&#10;Sneakers"></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center bulk-meta">
                                <span class="text-muted small"><b id="bulk_count">0</b> name(s) ready &middot; duplicates under the same parent are skipped</span>
                                <a href="javascript:void(0)" id="bulk_clear" class="small">Clear</a>
                            </div>
                            <div id="bulk_result" class="mt-3" style="display:none;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary-theme" id="bulk_submit"><i class="fas fa-plus mr-1"></i>Add Categories</button>
                        </div>
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
                        <a href="<?= base_url('admin/category/commission') ?>" class="btn btn-outline-primary-theme btn-sm mb-2 mr-2">
                            <i class="fas fa-percentage mr-1"></i>Edit Commission Rates
                        </a>
                        <button type="button" class="btn btn-outline-primary-theme btn-sm mb-2 mr-2" data-toggle="modal" data-target="#bulk_category_modal">
                            <i class="fas fa-layer-group mr-1"></i>Bulk Add
                        </button>
                        <a href="<?= base_url('admin/category/create-category') ?>" class="btn btn-primary-theme btn-sm mb-2">
                            <i class="fas fa-plus mr-1"></i>Add Category
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($current_parent)) { ?>
    <div class="cat-drill-banner">
        <a href="<?= base_url('admin/category' . (!empty($current_parent['parent_id']) ? '?id=' . (int) $current_parent['parent_id'] : '')) ?>" class="cat-back"><i class="fas fa-arrow-left mr-1"></i>Back</a>
        <span class="text-muted">Subcategories of</span>
        <strong><?= html_escape(unslash($current_parent['name'])) ?></strong>
    </div>
<?php } ?>
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
                                    <th data-field="parent_name" data-sortable="false" data-align='center'>Parent Category</th>
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
                                    <th data-field="commission" data-sortable="false" data-align='center'>Commission</th>
                                    <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                    <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-manage-category-page .cat-parent-path { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 4px; }
    .admin-manage-category-page .cat-parent-anc { font-size: 12.5px; color: #6b7280; }
    .admin-manage-category-page .cat-parent-sep { color: #9ca3af; font-size: 12px; }

    .admin-manage-category-page .cat-parent-chip {
        display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12.5px; font-weight: 600;
        background: var(--color-orange-light, #fff4ea); color: var(--color-orange-dark); border: 1px solid rgba(219,115,35,.25);
        text-decoration: none; white-space: nowrap;
    }
    .admin-manage-category-page a.cat-parent-chip:hover { background: var(--color-orange); color: #fff; }
    .admin-manage-category-page .cat-parent-chip.cat-parent-root { background: #f4f5f7; color: #6b7280; border-color: rgba(0,0,0,.08); font-weight: 500; }
    .admin-manage-category-page .cat-drill-banner {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; padding: 10px 14px;
        background: #fafafa; border: 1px solid rgba(0,0,0,.06); border-radius: 8px; font-size: 14px;
    }
    .admin-manage-category-page .cat-drill-banner .cat-back { font-weight: 600; color: var(--color-orange-dark); }
    .admin-manage-category-page table.table tbody td a:not(.btn):not(.cat-parent-chip):not(.badge) { color: #1f2937; font-weight: 600; }
    .admin-manage-category-page table.table tbody td a:not(.btn):not(.cat-parent-chip):not(.badge):hover { color: var(--color-orange-dark); }
    .admin-manage-category-page table.table tbody td .image-box-100 img { width: 46px; height: 46px; object-fit: contain; background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 8px; padding: 2px; }
    .admin-manage-category-page .card-body { padding: 18px 20px; }

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

</style>

<script>
    (function () {
        var $parent = $('#bulk_parent');
        $parent.select2({
            theme: 'bootstrap4', width: '100%', dropdownParent: $('#bulk_category_modal'),
            templateResult: function (d) {
                if (!d.element) { return d.text; }
                var m = /l(\d+)/.exec(d.element.className || '');
                var level = m ? parseInt(m[1], 10) : 0;
                return $('<span>').css('padding-left', (level * 14) + 'px').text((level ? '\u21B3 ' : '') + d.text);
            }
        });

        function cleanNames() {
            var seen = {}, out = [];
            $('#bulk_names').val().split(/\r?\n/).forEach(function (l) {
                l = $.trim(l.replace(/\s+/g, ' '));
                if (l && !seen[l.toLowerCase()]) { seen[l.toLowerCase()] = 1; out.push(l); }
            });
            return out;
        }
        $(document).on('input', '#bulk_names', function () { $('#bulk_count').text(cleanNames().length); });
        $(document).on('click', '#bulk_clear', function () { $('#bulk_names').val('').trigger('input'); $('#bulk_result').hide().empty(); });

        $(document).on('click', '#bulk_submit', function () {
            var $btn = $(this), $res = $('#bulk_result');
            if (!cleanNames().length) { iziToast.error({ message: 'Enter at least one category name.' }); return; }
            var data = { category_parent: $parent.val(), names: $('#bulk_names').val() };
            data[csrfName] = csrfHash;
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Adding...');
            $.ajax({
                type: 'POST', url: base_url + 'admin/category/bulk_add_categories', data: data, dataType: 'json',
                success: function (r) {
                    if (r.csrfName && r.csrfHash) { csrfName = r.csrfName; csrfHash = r.csrfHash; }
                    var html = '';
                    if (r.added && r.added.length) {
                        html += '<div class="alert alert-success mb-2"><b>' + $('<div>').text(r.message).html() + '</b></div>';
                    } else {
                        html += '<div class="alert alert-danger mb-2"><b>' + $('<div>').text(r.message).html() + '</b></div>';
                    }
                    if (r.skipped && r.skipped.length) {
                        html += '<div class="small text-muted mb-1">Skipped:</div><ul class="small mb-0">';
                        r.skipped.forEach(function (s) { html += '<li>' + $('<div>').text(s.name).html() + ' &mdash; ' + $('<div>').text(s.reason).html() + '</li>'; });
                        html += '</ul>';
                    }
                    $res.html(html).show();
                    if (r.added && r.added.length) {
                        // Keep only what was not added, so a retry does not repeat the same names.
                        var done = {}; r.added.forEach(function (n) { done[n.toLowerCase()] = 1; });
                        $('#bulk_names').val(cleanNames().filter(function (n) { return !done[n.toLowerCase()]; }).join('\n')).trigger('input');
                        $('#category_table').bootstrapTable('refresh');
                    }
                },
                error: function () { iziToast.error({ message: 'Something went wrong. Please try again.' }); },
                complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i>Add Categories'); }
            });
        });
    })();
</script>
