<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Deliverable Locations</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Deliverable Locations</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card content-area p-4">
                <div class="card-head mb-3">
                    <h4 class="card-title">Choose location scope using checkboxes</h4>
                    <p class="text-muted mb-0">Enable the location types you want to manage for product deliverability.</p>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check form-check-inline mr-4">
                            <input class="form-check-input deliverable-scope-toggle" type="checkbox" id="toggle-pincodes" data-target="#scope-pincodes" checked>
                            <label class="form-check-label" for="toggle-pincodes">Pincodes</label>
                        </div>
                        <div class="form-check form-check-inline mr-4">
                            <input class="form-check-input deliverable-scope-toggle" type="checkbox" id="toggle-cities" data-target="#scope-cities" checked>
                            <label class="form-check-label" for="toggle-cities">Cities</label>
                        </div>
                        <div class="form-check form-check-inline mr-4">
                            <input class="form-check-input deliverable-scope-toggle" type="checkbox" id="toggle-states" data-target="#scope-states" <?= !empty($has_states_table) ? 'checked' : '' ?> <?= empty($has_states_table) ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="toggle-states">States</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input deliverable-scope-toggle" type="checkbox" id="toggle-districts" data-target="#scope-districts" <?= !empty($has_districts_table) ? 'checked' : '' ?> <?= empty($has_districts_table) ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="toggle-districts">Districts</label>
                        </div>
                    </div>
                </div>

                <div id="scope-pincodes" class="mb-3">
                    <div class="alert alert-light border mb-0 d-flex justify-content-between align-items-center">
                        <span>Manage deliverable pincodes.</span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= base_url('seller/area/manage-zipcodes') ?>">Open Pincodes</a>
                    </div>
                </div>

                <div id="scope-cities" class="mb-3">
                    <div class="alert alert-light border mb-0 d-flex justify-content-between align-items-center">
                        <span>Manage deliverable cities.</span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= base_url('seller/area/manage-cities') ?>">Open Cities</a>
                    </div>
                </div>

                <div id="scope-states" class="mb-3 <?= empty($has_states_table) ? 'd-none' : '' ?>">
                    <div class="alert alert-light border mb-0 d-flex justify-content-between align-items-center">
                        <span>States are available from address master data.</span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= base_url('seller/home/profile') ?>">Open Profile State Selector</a>
                    </div>
                </div>

                <div id="scope-districts" class="mb-3 <?= empty($has_districts_table) ? 'd-none' : '' ?>">
                    <div class="alert alert-light border mb-0 d-flex justify-content-between align-items-center">
                        <span>Districts are available from address master data.</span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= base_url('seller/home/profile') ?>">Open Profile District Selector</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    $(document).on('change', '.deliverable-scope-toggle', function() {
        const target = $(this).data('target');
        $(target).toggle(this.checked);
    });
</script>