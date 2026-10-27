<div class="content-wrapper admin-featured-section-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-star mr-2 text-primary-theme"></i>Home Page Sections</h4>
                    <p class="text-muted mb-0 small">Choose exactly which products appear on the buyer home page.</p>
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
                            <span class="header-icon bg-set mr-2"><i class="fas <?= isset($fetched_data[0]['id']) ? 'fa-pen' : 'fa-plus' ?>"></i></span>
                            <h5 class="mb-0"><?= (isset($fetched_data[0]['id'])) ? 'Edit section' : 'Add a section' ?></h5>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/Featured_sections/add_featured_section'); ?>" method="POST" enctype="multipart/form-data"<?= isset($fetched_data[0]['id']) ? ' data-redirect-url="' . base_url('admin/featured_sections') . '"' : '' ?>>
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" id="edit_featured_section" name="edit_featured_section" value="<?= @$fetched_data[0]['id'] ?>">
                                <input type="hidden" id="update_id" name="update_id" value="1">
                            <?php } ?>
                            <?php
                            // Home page sections show only what an admin picks - the automatic types are retired.
                            $is_edit = isset($fetched_data[0]['id']);
                            $current_type = ($is_edit && $fetched_data[0]['product_type'] == 'digital_product') ? 'digital_product' : 'custom_products';
                            $style_labels = [
                                'cretzo_trending'      => 'Trending Deals - 6 products, plus Shop by Categories tiles',
                                'cretzo_best_seller'   => 'Best Seller - 12 products',
                                'cretzo_featured'      => 'Featured Products - 12 products with Add to Cart',
                                'cretzo_new_arrivals'  => 'New Arrivals - 12 products in a grid',
                                'cretzo_special_picks' => 'Special Picks - 6 large tiles',
                            ];
                            $saved_style = $is_edit ? $fetched_data[0]['style'] : 'cretzo_featured';
                            if (!isset($style_labels[$saved_style])) {
                                // A section saved under another theme's layout keeps it until the admin picks a new one.
                                $style_labels = [$saved_style => ucwords(str_replace('_', ' ', $saved_style)) . ' (other theme)'] + $style_labels;
                            }
                            $pick_cards = [];
                            if ($is_edit && $current_type == 'custom_products' && !empty($product_details)) {
                                foreach ($product_details as $p) {
                                    $pick_cards[] = [
                                        'id'     => (int) $p['id'],
                                        'name'   => (string) $p['name'],
                                        'seller' => isset($p['seller_name']) ? (string) $p['seller_name'] : '',
                                        'price'  => isset($p['price']) ? (string) $p['price'] : '',
                                        'img'    => get_image_url($p['image'], 'thumb', 'sm'),
                                        'hidden' => (isset($p['listing_visibility']) && $p['listing_visibility'] == 2) || (isset($p['status']) && $p['status'] != 1),
                                    ];
                                }
                            }
                            ?>
                            <div class="card-body czfs">
                                <input type="hidden" name="product_type" value="<?= $current_type ?>">

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="title" class="czfs-label">Section name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" id="title" value="<?= ($is_edit ? html_escape($fetched_data[0]['title']) : '') ?>" placeholder="e.g. Trending Deals">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="style" class="czfs-label">Layout <span class="text-danger">*</span></label>
                                        <select name="style" id="style" class="form-control">
                                            <?php foreach ($style_labels as $key => $label) { ?>
                                                <option value="<?= $key ?>" <?= $saved_style == $key ? 'selected' : '' ?>><?= html_escape($label) ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="short_description" class="czfs-label">One-line description <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="short_description" id="short_description" value="<?= ($is_edit ? html_escape($fetched_data[0]['short_description']) : '') ?>" placeholder="Shown under the section name on the home page">
                                </div>

                                <div class="form-group select-categories">
                                    <label for="categories" class="czfs-label">Shop by Categories tiles</label>
                                    <select name="categories[]" id="categories" class="select_multiple w-100" multiple data-placeholder="Choose the categories to show as tiles">
                                        <?php
                                        $selected_vals = explode(',', $is_edit ? (string) $fetched_data[0]['categories'] : '');
                                        echo get_categories_option_html($categories, $selected_vals);
                                        ?>
                                    </select>
                                    <small class="text-muted">The category tiles that appear under Trending Deals.</small>
                                </div>

                                <?php if ($current_type == 'custom_products') { ?>
                                    <div class="czfs-picker">
                                        <div class="czfs-pane">
                                            <div class="czfs-pane-head">
                                                <span class="czfs-step">1</span>
                                                <div>
                                                    <strong>Find products</strong>
                                                    <div class="text-muted small">Search any seller's products, or browse one seller.</div>
                                                </div>
                                            </div>
                                            <div class="czfs-tools">
                                                <input type="search" id="czfs-search" class="form-control" placeholder="Search by product name" autocomplete="off">
                                                <select id="czfs-seller" class="form-control">
                                                    <option value="">All sellers</option>
                                                    <?php foreach ((isset($sellers) ? $sellers : []) as $seller_row) { ?>
                                                        <option value="<?= (int) $seller_row['user_id'] ?>"><?= html_escape($seller_row['name']) ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <button type="button" id="czfs-add-seller" class="btn btn-sm btn-outline-primary czfs-addall" disabled>Add this seller's newest products</button>
                                            <div id="czfs-results" class="czfs-list"></div>
                                            <button type="button" id="czfs-more" class="btn btn-sm btn-light btn-block d-none">Show more</button>
                                        </div>

                                        <div class="czfs-pane czfs-pane-picked">
                                            <div class="czfs-pane-head">
                                                <span class="czfs-step">2</span>
                                                <div>
                                                    <strong>On the home page <span class="czfs-count" id="czfs-count">0</span></strong>
                                                    <div class="text-muted small" id="czfs-hint"></div>
                                                </div>
                                            </div>
                                            <div id="czfs-picked" class="czfs-list"></div>
                                            <div id="czfs-inputs"></div>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="form-group digital_products">
                                        <label class="czfs-label">Digital products *</label>
                                        <select name="digital_product_ids[]" class="search_admin_digital_product w-100" multiple data-placeholder=" Type to search and select products">
                                            <?php foreach ((isset($product_details) ? $product_details : []) as $row) { ?>
                                                <option value="<?= $row['id'] ?>" selected><?= html_escape($row['name']) ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                <?php } ?>

                                <div class="czfs-actions">
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= $is_edit ? 'Save changes' : 'Create section' ?></button>
                                    <?php if ($is_edit) { ?>
                                        <a href="<?= base_url('admin/featured_sections') ?>" class="btn btn-light">Cancel</a>
                                    <?php } else { ?>
                                        <button type="reset" class="btn btn-light">Clear</button>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php if ($current_type == 'custom_products') { ?>
                            <script>
                                window.addEventListener('load', function () {
                                    var $ = window.jQuery;
                                    if (!$) return;
                                    var PAGE = 20;
                                    var SLOTS = {cretzo_trending: 6, cretzo_special_picks: 6};
                                    var picked = <?= json_encode($pick_cards, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
                                    var initial = picked.slice();
                                    var $style = $('#style'), $seller = $('#czfs-seller'), $search = $('#czfs-search');
                                    var $results = $('#czfs-results'), $picked = $('#czfs-picked'), $more = $('#czfs-more');
                                    var page = 1, total = 0, timer = null, reqId = 0;
                                    var PLACEHOLDER = base_url + 'assets/front_end/cretzo/img/product-placeholder.jpg';

                                    // The list endpoint returns display HTML; DOMParser turns it into text / an image
                                    // address without running scripts or loading anything.
                                    function parse(html) { return new DOMParser().parseFromString(String(html || ''), 'text/html'); }
                                    function toCard(x) {
                                        var name = (parse(x.product_name).body.textContent || '').replace(/\s*\(.*$/, '').trim();
                                        var img = parse(x.image).querySelector('img');
                                        return {
                                            id: Number(x.id),
                                            name: (parse(x.name).body.firstChild || {}).textContent || name,
                                            seller: (parse(x.name).querySelector('b') || {}).textContent || '',
                                            price: String(x.price || '').replace(/^[^\d]*/, ''),
                                            img: img ? img.getAttribute('src') : '',
                                            hidden: String(x.listing_visibility) === '2'
                                        };
                                    }
                                    function isPicked(id) { return picked.some(function (p) { return p.id === id; }); }
                                    function thumb(src) { return $('<img class="czfs-thumb" alt="" loading="lazy">').attr('src', src || PLACEHOLDER); }
                                    function meta(c) {
                                        var bits = [];
                                        if (c.seller) bits.push('by ' + c.seller);
                                        if (c.price) bits.push('₹' + c.price);
                                        return $('<div class="czfs-meta">').text(bits.join('  ·  '));
                                    }

                                    function renderPicked() {
                                        var slots = SLOTS[$style.val()] || 12;
                                        $picked.empty();
                                        $('#czfs-inputs').empty();
                                        if (!picked.length) {
                                            $picked.append('<div class="czfs-empty">Nothing picked yet.<br>Add products from the left - this section only shows what you choose.</div>');
                                        }
                                        picked.forEach(function (c, i) {
                                            if (i === slots) $picked.append('<div class="czfs-divider">Below this line: only on the "See all" page</div>');
                                            var $row = $('<div class="czfs-item">').toggleClass('is-extra', i >= slots);
                                            $row.append($('<span class="czfs-pos">').text(i + 1), thumb(c.img));
                                            var $info = $('<div class="czfs-info">').append($('<div class="czfs-name">').text(c.name), meta(c));
                                            if (c.hidden) $info.append('<span class="czfs-warn">Not visible in the shop right now</span>');
                                            $row.append($info);
                                            var $btns = $('<div class="czfs-btns">');
                                            $btns.append($('<button type="button" class="btn btn-sm btn-light" title="Move up">').html('&#9650;').prop('disabled', i === 0).on('click', function () { move(i, -1); }));
                                            $btns.append($('<button type="button" class="btn btn-sm btn-light" title="Move down">').html('&#9660;').prop('disabled', i === picked.length - 1).on('click', function () { move(i, 1); }));
                                            $btns.append($('<button type="button" class="btn btn-sm btn-light text-danger" title="Remove">').html('&times;').on('click', function () { picked.splice(i, 1); renderPicked(); renderResults(); }));
                                            $row.append($btns);
                                            $picked.append($row);
                                            $('#czfs-inputs').append($('<input type="hidden" name="product_ids[]">').val(c.id));
                                        });
                                        $('#czfs-count').text(picked.length);
                                        $('#czfs-hint').text('The home page shows the first ' + slots + ', in this order. Use the arrows to reorder.');
                                        $('.select-categories').toggle($style.val() === 'cretzo_trending');
                                    }
                                    function move(i, d) {
                                        var j = i + d;
                                        if (j < 0 || j >= picked.length) return;
                                        var t = picked[i]; picked[i] = picked[j]; picked[j] = t;
                                        renderPicked();
                                    }

                                    var shown = [];
                                    function renderResults() {
                                        $results.empty();
                                        if (!shown.length) {
                                            $results.append('<div class="czfs-empty">No products found.</div>');
                                            return;
                                        }
                                        shown.forEach(function (c) {
                                            var already = isPicked(c.id);
                                            var $row = $('<div class="czfs-item">').toggleClass('is-done', already);
                                            $row.append(thumb(c.img));
                                            var $info = $('<div class="czfs-info">').append($('<div class="czfs-name">').text(c.name), meta(c));
                                            if (c.hidden) $info.append('<span class="czfs-warn">Hidden by its seller’s plan - can’t be shown</span>');
                                            $row.append($info);
                                            var $btn = $('<button type="button" class="btn btn-sm">');
                                            if (already) $btn.addClass('btn-light').text('Added').prop('disabled', true);
                                            else if (c.hidden) $btn.addClass('btn-light').text('Hidden').prop('disabled', true);
                                            else $btn.addClass('btn-primary').text('Add').on('click', function () { picked.push(c); renderPicked(); renderResults(); });
                                            $row.append($('<div class="czfs-btns">').append($btn));
                                            $results.append($row);
                                        });
                                        $more.toggleClass('d-none', shown.length >= total);
                                    }

                                    function load(reset) {
                                        if (reset) { page = 1; shown = []; }
                                        var mine = ++reqId;
                                        $results.addClass('is-loading');
                                        $.getJSON(base_url + 'admin/product/get_product_data', {
                                            search: $.trim($search.val()), seller_id: $seller.val() || '', status: 1,
                                            limit: PAGE, offset: (page - 1) * PAGE
                                        }).done(function (r) {
                                            if (mine !== reqId) return;
                                            total = Number(r.total || 0);
                                            shown = shown.concat((r.rows || []).map(toCard));
                                            renderResults();
                                        }).always(function () { if (mine === reqId) $results.removeClass('is-loading'); });
                                    }

                                    $search.on('input', function () { clearTimeout(timer); timer = setTimeout(function () { load(true); }, 250); });
                                    $seller.on('change', function () {
                                        $('#czfs-add-seller').prop('disabled', !$seller.val());
                                        load(true);
                                    });
                                    $more.on('click', function () { page++; load(false); });
                                    $style.on('change', renderPicked);

                                    $('#czfs-add-seller').on('click', function () {
                                        var $btn = $(this).prop('disabled', true);
                                        $.getJSON(base_url + 'admin/product/get_product_data', {seller_id: $seller.val(), status: 1, limit: 40, offset: 0})
                                            .done(function (r) {
                                                var added = 0;
                                                (r.rows || []).map(toCard).forEach(function (c) {
                                                    if (c.hidden || added >= 12 || isPicked(c.id)) return;
                                                    picked.push(c); added++;
                                                });
                                                renderPicked(); renderResults();
                                                if (!added) alert('Nothing to add - this seller has no visible products that are not already picked.');
                                            })
                                            .always(function () { $btn.prop('disabled', !$seller.val()); });
                                    });

                                    $('form.form-submit-event').on('reset', function () {
                                        setTimeout(function () { picked = initial.slice(); renderPicked(); renderResults(); }, 0);
                                    });

                                    renderPicked();
                                    load(true);
                                });
                            </script>
                            <?php } else { ?>
                            <script>
                                window.addEventListener('load', function () {
                                    var $ = window.jQuery; if (!$) return;
                                    $('.select-categories').toggle($('#style').val() === 'cretzo_trending');
                                    $('#style').on('change', function () { $('.select-categories').toggle($(this).val() === 'cretzo_trending'); });
                                });
                            </script>
                            <?php } ?>
                            <div class="d-flex justify-content-center form-group">
                                <div id="error_box">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!--/.card-->
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

    /* hand-picked product picker */
    .admin-featured-section-page .czfs-label { font-weight: 600; font-size: 13px; color: var(--color-grey); margin-bottom: 4px; }
    .admin-featured-section-page .czfs-picker { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 8px 0 18px; }
    @media (max-width: 991px) { .admin-featured-section-page .czfs-picker { grid-template-columns: 1fr; } }
    .admin-featured-section-page .czfs-pane { border: 1px solid rgba(0,0,0,.08); border-radius: 12px; padding: 14px; background: #fcfcfd; min-width: 0; }
    .admin-featured-section-page .czfs-pane-picked { background: #fff8f2; border-color: rgba(255,122,0,.25); }
    .admin-featured-section-page .czfs-pane-head { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
    .admin-featured-section-page .czfs-step { flex: none; width: 28px; height: 28px; border-radius: 50%; background: var(--color-orange); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .admin-featured-section-page .czfs-count { display: inline-block; margin-left: 6px; padding: 0 8px; border-radius: 10px; background: var(--color-orange); color: #fff; font-size: 12px; }
    .admin-featured-section-page .czfs-tools { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
    .admin-featured-section-page .czfs-addall { margin-bottom: 8px; }
    .admin-featured-section-page .czfs-list { max-height: 440px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
    .admin-featured-section-page .czfs-list.is-loading { opacity: .5; }
    .admin-featured-section-page .czfs-item { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid rgba(0,0,0,.07); border-radius: 10px; padding: 8px; }
    .admin-featured-section-page .czfs-item.is-done { opacity: .55; }
    .admin-featured-section-page .czfs-item.is-extra { opacity: .6; border-style: dashed; }
    .admin-featured-section-page .czfs-thumb { flex: none; width: 48px; height: 48px; border-radius: 8px; object-fit: cover; background: #f1f1f1; }
    .admin-featured-section-page .czfs-pos { flex: none; width: 22px; text-align: center; font-weight: 700; color: var(--color-orange); }
    .admin-featured-section-page .czfs-info { flex: 1; min-width: 0; }
    .admin-featured-section-page .czfs-name { font-weight: 600; font-size: 13px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .admin-featured-section-page .czfs-meta { font-size: 12px; color: #777; white-space: pre; overflow: hidden; text-overflow: ellipsis; }
    .admin-featured-section-page .czfs-warn { display: inline-block; margin-top: 2px; font-size: 11px; color: #b42318; background: #fee4e2; border-radius: 6px; padding: 0 6px; }
    .admin-featured-section-page .czfs-btns { flex: none; display: flex; gap: 4px; }
    .admin-featured-section-page .czfs-empty { text-align: center; color: #888; padding: 28px 12px; font-size: 13px; }
    .admin-featured-section-page .czfs-divider { text-align: center; font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: .4px; border-top: 1px dashed rgba(0,0,0,.2); padding-top: 6px; margin-top: 2px; }
    .admin-featured-section-page .czfs-actions { display: flex; gap: 8px; padding-top: 4px; }
</style>
<!-- /.content -->
</div>