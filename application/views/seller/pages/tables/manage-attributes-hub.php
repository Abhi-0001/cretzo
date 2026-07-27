<div class="content-wrapper attributes-hub">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sliders-h mr-2 text-primary-theme"></i>Attributes Setup</h4>
                    <p class="text-muted mb-0 small">Manage attribute sets, attributes, and their values for your products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Attributes Setup</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row attribute-switch-row mb-4">
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
            </div>

            <div id="section-attribute-set" class="card attribute-card mb-4">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-layer-group"></i></span>
                    <h5 class="mb-0">Attribute Sets</h5>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/attribute_set/attribute_set_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'>
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="name" data-sortable="true">Name</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div id="section-attributes" class="card attribute-card mb-4">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-attr"><i class="fas fa-tags"></i></span>
                    <h5 class="mb-0">Attributes</h5>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/attributes/attribute_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'>
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="name" data-sortable="true">Name</th>
                                <th data-field="attribute_set" data-sortable="true">Attribute Set</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div id="section-attribute-values" class="card attribute-card mb-4">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-value"><i class="fas fa-list-ul"></i></span>
                    <h5 class="mb-0">Attribute Values</h5>
                </div>
                <div class="card-body pt-3">
                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/attribute_value/attribute_value_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'>
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="attributes" data-sortable="false">Attributes</th>
                                <th data-field="value" data-sortable="true">Value</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .attributes-hub .text-primary-theme { color: var(--color-orange); }

    .attributes-hub .attribute-switch-card {
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
    .attributes-hub .attribute-switch-card:hover {
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-color: var(--color-orange);
    }
    .attributes-hub .attribute-switch-card input.attribute-section-toggle {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .attributes-hub .switch-icon {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--color-orange-light);
        color: var(--color-orange);
        font-size: 16px;
    }
    .attributes-hub .switch-text {
        flex: 1 1 auto;
        line-height: 1.3;
    }
    .attributes-hub .switch-toggle {
        flex: 0 0 auto;
        width: 40px;
        height: 22px;
        border-radius: 999px;
        background: #d9d9d9;
        position: relative;
        transition: background .2s ease;
    }
    .attributes-hub .switch-toggle::after {
        content: "";
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        transition: left .2s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    .attributes-hub input.attribute-section-toggle:checked ~ .switch-toggle {
        background: var(--color-orange);
    }
    .attributes-hub input.attribute-section-toggle:checked ~ .switch-toggle::after {
        left: 20px;
    }
    .attributes-hub .attribute-switch-card.section-hidden {
        opacity: .6;
    }

    .attributes-hub .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .attributes-hub .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .attributes-hub .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .attributes-hub .header-icon.bg-set { background: var(--color-orange); }
    .attributes-hub .header-icon.bg-attr { background: var(--color-orange-dark); }
    .attributes-hub .header-icon.bg-value { background: var(--color-theme-red); }

    /* --- simplified bootstrap-table look --- */
    .attributes-hub .fixed-table-toolbar {
        margin-bottom: 10px;
    }
    .attributes-hub .fixed-table-toolbar > div {
        margin-left: 10px !important;
    }
    .attributes-hub .fixed-table-toolbar .btn-group > .btn,
    .attributes-hub .fixed-table-toolbar .btn-group > .keep-open {
        margin-left: 8px !important;
    }
    .attributes-hub .fixed-table-toolbar .btn-group > .btn:first-child,
    .attributes-hub .fixed-table-toolbar .btn-group > .keep-open:first-child {
        margin-left: 0 !important;
    }
    .attributes-hub .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .attributes-hub .fixed-table-toolbar .search input:focus {
        border-color: var(--color-orange);
    }
    .attributes-hub .fixed-table-toolbar .columns .btn,
    .attributes-hub .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .attributes-hub .fixed-table-toolbar .columns .btn:hover,
    .attributes-hub .fixed-table-toolbar .export .btn:hover {
        border-color: var(--color-orange);
        color: var(--color-orange);
    }

    .attributes-hub .fixed-table-container {
        border: none;
    }
    .attributes-hub table.table {
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }
    .attributes-hub table.table thead th {
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
    .attributes-hub table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .attributes-hub table.table tbody tr:hover {
        background-color: var(--color-orange-light);
    }
    .attributes-hub .fixed-table-pagination {
        margin-top: 12px;
    }
    .attributes-hub .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .attributes-hub .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .attributes-hub .fixed-table-pagination .page-list .btn {
        border-radius: 20px;
    }
</style>

<script>
    $(document).on('change', '.attribute-section-toggle', function() {
        const target = $(this).data('target');
        $(target).toggle(this.checked);
        $(this).closest('.attribute-switch-card').toggleClass('section-hidden', !this.checked);
    });
</script>
