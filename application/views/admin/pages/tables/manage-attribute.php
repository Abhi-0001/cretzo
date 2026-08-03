<?php
// Each section loads from its own controller, and those controllers redirect to the dashboard
// when the signed-in system user lacks the matching read permission. Without these guards a
// restricted user would see three tables that silently fail to load, so only the sections they
// can actually read are rendered.
$can_read_set    = has_permissions('read', 'attribute_set');
$can_read_attr   = has_permissions('read', 'attribute');
$can_read_values = has_permissions('read', 'attribute_value');
?>
<div class="content-wrapper admin-attributes-hub">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sliders-h mr-2 text-primary-theme"></i>Attributes Setup</h4>
                    <p class="text-muted mb-0 small">Manage attribute sets, attributes and their values used across products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Attributes Setup</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Section toggles, mirroring the seller Attributes Setup page. -->
            <div class="row attribute-switch-row mb-4">
                <?php if ($can_read_set) : ?>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="attribute-switch-card" for="toggle-attribute-set">
                            <input class="attribute-section-toggle" type="checkbox" id="toggle-attribute-set" data-target="#section-attribute-set" checked>
                            <span class="switch-icon"><i class="fas fa-layer-group"></i></span>
                            <span class="switch-text">
                                <strong>Attribute Sets</strong>
                                <small class="text-muted d-block">Group attributes together</small>
                            </span>
                            <span class="switch-toggle"></span>
                        </label>
                    </div>
                <?php endif; ?>
                <?php if ($can_read_attr) : ?>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="attribute-switch-card" for="toggle-attributes">
                            <input class="attribute-section-toggle" type="checkbox" id="toggle-attributes" data-target="#section-attributes" checked>
                            <span class="switch-icon"><i class="fas fa-tags"></i></span>
                            <span class="switch-text">
                                <strong>Attributes</strong>
                                <small class="text-muted d-block">e.g. Size, Color, Material</small>
                            </span>
                            <span class="switch-toggle"></span>
                        </label>
                    </div>
                <?php endif; ?>
                <?php if ($can_read_values) : ?>
                    <div class="col-md-4">
                        <label class="attribute-switch-card" for="toggle-attribute-values">
                            <input class="attribute-section-toggle" type="checkbox" id="toggle-attribute-values" data-target="#section-attribute-values" checked>
                            <span class="switch-icon"><i class="fas fa-list-ul"></i></span>
                            <span class="switch-text">
                                <strong>Attribute Values</strong>
                                <small class="text-muted d-block">e.g. Small, Red, Cotton</small>
                            </span>
                            <span class="switch-toggle"></span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== Attribute Sets ===== -->
            <?php if ($can_read_set) : ?>
            <div id="section-attribute-set" class="card attribute-card mb-4">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-layer-group"></i></span>
                        <h5 class="mb-0">Attribute Sets</h5>
                    </div>
                    <a href="<?= base_url('admin/attribute_set/') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Attribute Set
                    </a>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' id="attribute_set_table" data-toggle="table"
                        data-url="<?= base_url('admin/attribute_set/attribute_set_list') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "attribute-set-list", "ignoreColumn": ["operate"]}'
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="name" data-sortable="true">Name</th>
                                <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <?php endif; ?>

            <!-- ===== Attributes =====
                 This section is the reason the page exists. Before this rebuild the page was a
                 copy of Manage Attribute Value and pointed at admin/attribute_value/attribute_value_list,
                 so the attributes list itself was unreachable from anywhere in the panel. -->
            <?php if ($can_read_attr) : ?>
            <div id="section-attributes" class="card attribute-card mb-4">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-attr mr-2"><i class="fas fa-tags"></i></span>
                        <h5 class="mb-0">Attributes</h5>
                    </div>
                    <a href="<?= base_url('admin/attributes/') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Attribute
                    </a>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' id="attributes_table" data-toggle="table"
                        data-url="<?= base_url('admin/attributes/attribute_list') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "attribute-list", "ignoreColumn": ["operate"]}'
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="name" data-sortable="true">Name</th>
                                <th data-field="attribute_set" data-sortable="true">Attribute Set</th>
                                <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <?php endif; ?>

            <!-- ===== Attribute Values ===== -->
            <?php if ($can_read_values) : ?>
            <div id="section-attribute-values" class="card attribute-card mb-4">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-value mr-2"><i class="fas fa-list-ul"></i></span>
                        <h5 class="mb-0">Attribute Values</h5>
                    </div>
                    <a href="<?= base_url('admin/attribute_value/') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Attribute Value
                    </a>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' id="attribute_values_table" data-toggle="table"
                        data-url="<?= base_url('admin/attribute_value/attribute_value_list') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "attribute-value-list", "ignoreColumn": ["operate"]}'
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="attributes" data-sortable="true">Attribute</th>
                                <th data-field="name" data-sortable="true">Value</th>
                                <th data-field="status" data-sortable="true" data-align='center'>Status</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Edit modal. The shared edit_btn handler in custom.js loads the relevant edit form into
         .edit-modal-lg .modal-body, so this markup has to stay on the page. -->
    <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="editAttributeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAttributeLabel">Edit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-attributes-hub .text-primary-theme { color: var(--color-orange); }

    .admin-attributes-hub .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-attributes-hub .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-attributes-hub .attribute-switch-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 10px;
        padding: 14px 16px;
        margin: 0;
        cursor: pointer;
        transition: box-shadow .15s ease, border-color .15s ease;
    }
    .admin-attributes-hub .attribute-switch-card:hover {
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-color: var(--color-orange);
    }
    .admin-attributes-hub .attribute-switch-card input.attribute-section-toggle {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .admin-attributes-hub .switch-icon {
        flex: 0 0 auto;
        width: 42px; height: 42px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: var(--color-orange-light);
        color: var(--color-orange);
        font-size: 16px;
    }
    .admin-attributes-hub .switch-text { flex: 1 1 auto; line-height: 1.3; }
    .admin-attributes-hub .switch-toggle {
        flex: 0 0 auto;
        width: 40px; height: 22px;
        border-radius: 999px;
        background: #d9d9d9;
        position: relative;
        transition: background .2s ease;
    }
    .admin-attributes-hub .switch-toggle::after {
        content: "";
        position: absolute;
        top: 2px; left: 2px;
        width: 18px; height: 18px;
        border-radius: 50%;
        background: #fff;
        transition: left .2s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    .admin-attributes-hub input.attribute-section-toggle:checked ~ .switch-toggle { background: var(--color-orange); }
    .admin-attributes-hub input.attribute-section-toggle:checked ~ .switch-toggle::after { left: 20px; }
    .admin-attributes-hub .attribute-switch-card.section-hidden { opacity: .6; }

    .admin-attributes-hub .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-attributes-hub .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-attributes-hub .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-attributes-hub .header-icon.bg-set { background: var(--color-orange); }
    .admin-attributes-hub .header-icon.bg-attr { background: var(--color-orange-dark); }
    .admin-attributes-hub .header-icon.bg-value { background: var(--color-theme-red); }

    .admin-attributes-hub .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-attributes-hub .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-attributes-hub .fixed-table-toolbar .btn-group > .btn,
    .admin-attributes-hub .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-attributes-hub .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-attributes-hub .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-attributes-hub .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-attributes-hub .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-attributes-hub .fixed-table-toolbar .columns .btn,
    .admin-attributes-hub .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-attributes-hub .fixed-table-toolbar .columns .btn:hover,
    .admin-attributes-hub .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-attributes-hub .fixed-table-container { border: none; }
    .admin-attributes-hub table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-attributes-hub table.table thead th {
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
    .admin-attributes-hub table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-attributes-hub table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-attributes-hub .action-btn { border-radius: 6px; }
    .admin-attributes-hub .badge { font-size: 12px; padding: 5px 10px; border-radius: 20px; font-weight: 600; }

    .admin-attributes-hub .fixed-table-pagination { margin-top: 12px; }
    .admin-attributes-hub .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-attributes-hub .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-attributes-hub .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>

<script>
    $(document).on('change', '.admin-attributes-hub .attribute-section-toggle', function () {
        var target = $(this).data('target');
        $(target).toggle(this.checked);
        $(this).closest('.attribute-switch-card').toggleClass('section-hidden', !this.checked);
    });

</script>
