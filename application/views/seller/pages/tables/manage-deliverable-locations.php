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
            <?php if ($this->session->flashdata('message')) { ?>
                <div class="alert alert-success"><?= $this->session->flashdata('message') ?></div>
            <?php } ?>
            <?php if ($this->session->flashdata('error')) { ?>
                <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
            <?php } ?>
            <div class="card content-area p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Deliverable Source Locations</h4>
                    <?php if (!empty($has_pickup_locations_table)) { ?>
                        <a class="btn btn-sm btn-primary" href="<?= base_url('seller/pickup_location/manage_pickup_locations') ?>">+ Add Location</a>
                    <?php } ?>
                </div>

                <?php if (empty($has_pickup_locations_table)) { ?>
                    <div class="alert alert-warning mb-0">
                        Deliverable source locations table is not available in this database yet. Please run the migration/setup for <code>pickup_locations</code>.
                    </div>
                <?php } elseif (!empty($deliverable_locations)) { ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Location</th>
                                    <th>Address</th>
                                    <th>City / State / Pincode</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliverable_locations as $index => $location) { ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= output_escaping($location['pickup_location']) ?></td>
                                        <td>
                                            <?= output_escaping($location['address']) ?>
                                            <?= !empty($location['address_2']) ? ', ' . output_escaping($location['address_2']) : '' ?>
                                        </td>
                                        <td>
                                            <?= output_escaping($location['city']) ?>,
                                            <?= output_escaping($location['state']) ?> -
                                            <?= output_escaping($location['pin_code']) ?>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= base_url('seller/pickup_location/manage_pickup_locations?edit_id=' . $location['id']) ?>">Edit</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="alert alert-info mb-0">
                        No deliverable source location found. Click <strong>Add Location</strong> to create one.
                    </div>
                <?php } ?>
            </div>

            <div class="card content-area p-4">
                <div class="card-head mb-3">
                    <h4 class="card-title">Deliverability Scope (Pincode / City / State / District)</h4>
                    <p class="text-muted mb-0">Select a scope to open a table. Checkboxes on the right column will be saved for this seller.</p>
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
                    <div class="alert alert-light border mb-2 d-flex justify-content-between align-items-center">
                        <span>Pincodes available: <strong><?= (int)$zipcode_count ?></strong></span>
                    </div>
                    <form method="post" action="<?= base_url('seller/area/save_deliverable_scope') ?>">
                        <input type="hidden" name="location_type" value="zipcode">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pincode</th>
                                        <th class="text-right">Select</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($zipcodes_list)) { ?>
                                        <?php foreach ($zipcodes_list as $row) { ?>
                                            <tr>
                                                <td><?= (int)$row['id'] ?></td>
                                                <td><?= output_escaping($row['zipcode']) ?></td>
                                                <td class="text-right">
                                                    <input type="checkbox" name="selected_ids[]" value="<?= (int)$row['id'] ?>" <?= in_array((int)$row['id'], $selected_zipcodes) ? 'checked' : '' ?>>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="3" class="text-center">No pincodes available.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save Pincodes</button>
                    </form>
                </div>

                <div id="scope-cities" class="mb-3">
                    <div class="alert alert-light border mb-2 d-flex justify-content-between align-items-center">
                        <span>Cities available: <strong><?= (int)$city_count ?></strong></span>
                    </div>
                    <form method="post" action="<?= base_url('seller/area/save_deliverable_scope') ?>">
                        <input type="hidden" name="location_type" value="city">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>City</th>
                                        <th class="text-right">Select</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cities_list)) { ?>
                                        <?php foreach ($cities_list as $row) { ?>
                                            <tr>
                                                <td><?= (int)$row['id'] ?></td>
                                                <td><?= output_escaping($row['name']) ?></td>
                                                <td class="text-right">
                                                    <input type="checkbox" name="selected_ids[]" value="<?= (int)$row['id'] ?>" <?= in_array((int)$row['id'], $selected_cities) ? 'checked' : '' ?>>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="3" class="text-center">No cities available.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save Cities</button>
                    </form>
                </div>

                <div id="scope-states" class="mb-3 <?= empty($has_states_table) ? 'd-none' : '' ?>">
                    <div class="alert alert-light border mb-2 d-flex justify-content-between align-items-center">
                        <span>States available: <strong><?= (int)$state_count ?></strong></span>
                    </div>
                    <form method="post" action="<?= base_url('seller/area/save_deliverable_scope') ?>">
                        <input type="hidden" name="location_type" value="state">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>State</th>
                                        <th class="text-right">Select</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($states_list)) { ?>
                                        <?php foreach ($states_list as $row) { ?>
                                            <tr>
                                                <td><?= (int)$row['id'] ?></td>
                                                <td><?= output_escaping($row['name']) ?></td>
                                                <td class="text-right">
                                                    <input type="checkbox" name="selected_ids[]" value="<?= (int)$row['id'] ?>" <?= in_array((int)$row['id'], $selected_states) ? 'checked' : '' ?>>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="3" class="text-center">No states available.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save States</button>
                    </form>
                </div>

                <div id="scope-districts" class="mb-3 <?= empty($has_districts_table) ? 'd-none' : '' ?>">
                    <div class="alert alert-light border mb-2 d-flex justify-content-between align-items-center">
                        <span>Districts available: <strong><?= (int)$district_count ?></strong></span>
                    </div>
                    <form method="post" action="<?= base_url('seller/area/save_deliverable_scope') ?>">
                        <input type="hidden" name="location_type" value="district">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>District</th>
                                        <th class="text-right">Select</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($districts_list)) { ?>
                                        <?php foreach ($districts_list as $row) { ?>
                                            <tr>
                                                <td><?= (int)$row['id'] ?></td>
                                                <td><?= output_escaping($row['name']) ?></td>
                                                <td class="text-right">
                                                    <input type="checkbox" name="selected_ids[]" value="<?= (int)$row['id'] ?>" <?= in_array((int)$row['id'], $selected_districts) ? 'checked' : '' ?>>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="3" class="text-center">No districts available.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save Districts</button>
                    </form>
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