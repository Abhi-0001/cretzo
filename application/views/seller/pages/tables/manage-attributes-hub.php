<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Attributes Setup</h4>
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
            <div class="card content-area p-4">
                <div class="card-head mb-3">
                    <h4 class="card-title">Select the attribute sections to manage</h4>
                    <p class="text-muted mb-0">Use the checkboxes below to show Attribute Sets, Attributes, and Attribute Values in one place.</p>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check form-check-inline mr-4">
                            <input class="form-check-input attribute-section-toggle" type="checkbox" id="toggle-attribute-set" data-target="#section-attribute-set" checked>
                            <label class="form-check-label" for="toggle-attribute-set">Attribute Sets</label>
                        </div>
                        <div class="form-check form-check-inline mr-4">
                            <input class="form-check-input attribute-section-toggle" type="checkbox" id="toggle-attributes" data-target="#section-attributes" checked>
                            <label class="form-check-label" for="toggle-attributes">Attributes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input attribute-section-toggle" type="checkbox" id="toggle-attribute-values" data-target="#section-attribute-values" checked>
                            <label class="form-check-label" for="toggle-attribute-values">Attribute Values</label>
                        </div>
                    </div>
                </div>

                <div id="section-attribute-set" class="mb-4">
                    <h5>Attribute Sets</h5>
                    <table class='table-striped' data-toggle="table" data-url="<?= base_url('seller/attribute_set/attribute_set_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'>
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="name" data-sortable="true">Name</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div id="section-attributes" class="mb-4">
                    <h5>Attributes</h5>
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

                <div id="section-attribute-values">
                    <h5>Attribute Values</h5>
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

<script>
    $(document).on('change', '.attribute-section-toggle', function() {
        const target = $(this).data('target');
        $(target).toggle(this.checked);
    });
</script>