    <div class="content-wrapper admin-manage-subscriptions-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0"><i class="fas fa-crown mr-2 text-primary-theme"></i>Manage Subscription Plans</h4>
                    <p class="text-muted mb-0 small">Plans sellers can subscribe to across the marketplace.</p>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Subscriptions</li>
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
                            <span class="header-icon bg-set mr-2"><i class="fas fa-plus"></i></span>
                            <h5 class="mb-0"><?= (isset($fetched_data[0]['id'])) ? 'Edit Subscription' : 'Add Subscription' ?></h5>
                        </div>
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/subscription/add_subscription'); ?>" method="POST" id="subscription_form">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <!-- This form is loaded into the edit modal as a fragment, but this page ALSO
                                     renders a static copy of the same form (for Add), so any edit loaded into
                                     the modal ends up with duplicate #subscription_form/#submit_btn ids on the
                                     page at once. custom.js's shared submit handler branches on #update_id to
                                     tell those two copies apart and disable the right Save button - without it,
                                     it always targeted the static page's button instead of the modal's, so on a
                                     validation error the real (modal) button never showed any busy/disabled state. -->
                                <input type="hidden" id="update_id" name="update_id" value="1">
                                <input type="hidden" id="edit_subscription" name="edit_subscription" value="<?= @$fetched_data[0]['id'] ?>">
                            <?php } ?>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="name" class="control-label">Plan Name <span class='text-danger text-xs'>*</span></label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" name="name" id="name" value="<?= (isset($fetched_data[0]['name']) ? html_escape($fetched_data[0]['name']) : '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="price" class="control-label">Price</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" onkeypress="return event.charCode >= 48 && event.charCode <= 57" name="price" id="price" value="<?= (isset($fetched_data[0]['price']) ? $fetched_data[0]['price'] : '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="listings_limit" class="control-label">Listings Limit</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" name="listings_limit" id="listings_limit" value="<?= (isset($fetched_data[0]['listings_limit']) ? $fetched_data[0]['listings_limit'] : '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="validity" class="control-label">Validity no of days</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" onkeypress="return event.charCode >= 48 && event.charCode <= 57" name="validity" id="validity" value="<?= (isset($fetched_data[0]['validity']) ? $fetched_data[0]['validity'] : '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="commission_first50" class="control-label">Commission (0-50 orders) %</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="number" step="any" class="form-control" name="commission_first50" id="commission_first50" value="<?= (isset($fetched_data[0]['commission_first50']) ? $fetched_data[0]['commission_first50'] : '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="commission_51_100" class="control-label">Commission (51-100 orders) %</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="number" step="any" class="form-control" name="commission_51_100" id="commission_51_100" value="<?= (isset($fetched_data[0]['commission_51_100']) ? $fetched_data[0]['commission_51_100'] : '') ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="commission_after100" class="control-label">Commission (after 100 orders) %</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="number" step="any" class="form-control" name="commission_after100" id="commission_after100" value="<?= (isset($fetched_data[0]['commission_after100']) ? $fetched_data[0]['commission_after100'] : '') ?>">
                                    </div>
                                </div>

                                <!-- Plan Features Section -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h5 class="mb-3">Plan Features</h5>
                                        <div class="feature-list" id="features_tbody">

                                            <?php
                                            if (!empty($fetched_data[0]['features'])) {
                                                $json = stripslashes($fetched_data[0]['features']);
                                                $features = json_decode($json, true);
                                                if (!empty($features)) {
                                                    foreach ($features as $index => $feature) {
                                            ?>
                                                <div class="feature-row">
                                                    <textarea
                                                        class="form-control feature_desc"
                                                        name="features[<?= $index ?>][description]"
                                                        rows="2"
                                                        placeholder="Feature description"><?= htmlspecialchars($feature['description']) ?></textarea>
                                                    <button type="button" class="feature-remove-btn delete-feature-row" title="Remove feature">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php
                                                    }
                                                }
                                            }
                                            ?>

                                        </div>
                                        <button type="button" class="btn btn-primary-theme btn-sm mt-2" id="add_feature_row">
                                            <i class="fa fa-plus"></i> Add Feature
                                        </button>
                                        <input type="hidden" id="features_json" name="features_json" value="">
                                    </div>
                                </div>

                                <div class="form-group mt-3">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Subscription' : 'Add Subscription' ?></button>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- add/edit form is loaded via modal -->
                <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLongTitle">Edit Subscription</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 main-content">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-list"></i></span>
                            <h5 class="mb-0">Plans</h5>
                        </div>
                        <div class="card-body">
                            <table class='table-striped fixed-row-height' data-toggle="table" data-url="<?= base_url('admin/subscription/view_subscription') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">ID</th>
                                        <th data-field="name" data-sortable="true">Plan Name</th>
                                        <th data-field="price" data-sortable="true">Price</th>
                                        <th data-field="listings_limit" data-sortable="true">Listings Limit</th>
                                        <th data-field="validity" data-sortable="true">Validity</th>
                                        <th data-field="commission_first50" data-sortable="true">Commission 0-50%</th>
                                        <th data-field="commission_51_100" data-sortable="true">Commission 51-100%</th>
                                        <th data-field="commission_after100" data-sortable="true">Commission >100%</th>
                                        <th data-field="operate" data-sortable="false">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div><!-- .card-body -->
                    </div><!-- .card -->
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-manage-subscriptions-page .text-primary-theme { color: var(--color-orange); }

    .admin-manage-subscriptions-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-manage-subscriptions-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-manage-subscriptions-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-manage-subscriptions-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .admin-manage-subscriptions-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-manage-subscriptions-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-manage-subscriptions-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-manage-subscriptions-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-manage-subscriptions-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-manage-subscriptions-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-manage-subscriptions-page table.table thead th {
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
    .admin-manage-subscriptions-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-manage-subscriptions-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-manage-subscriptions-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-manage-subscriptions-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-manage-subscriptions-page td:has(.action-btn) { white-space: nowrap; }
    .admin-manage-subscriptions-page .action-btn { display: inline-block; vertical-align: middle; }

    .admin-manage-subscriptions-page .feature-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .admin-manage-subscriptions-page .feature-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #fafafa;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 10px;
        padding: 10px;
    }
    .admin-manage-subscriptions-page .feature-row .feature_desc {
        flex: 1;
        background: #fff;
        border-radius: 8px;
        resize: vertical;
    }
    .admin-manage-subscriptions-page .feature-row .feature_desc:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    .admin-manage-subscriptions-page .feature-remove-btn {
        flex: none;
        width: 34px;
        height: 34px;
        margin-top: 2px;
        border: none;
        border-radius: 50%;
        background: rgba(220,53,69,0.1);
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }
    .admin-manage-subscriptions-page .feature-remove-btn:hover {
        background: #dc3545;
        color: #fff;
    }
</style>

<script>

$(document).ready(function () {

    // ADD FEATURE ROW
    $(document).on("click", "#add_feature_row", function (e) {

        e.preventDefault();

        let tableBody = $(this).closest(".card-body").find("#features_tbody");

        if (tableBody.length === 0) {
            tableBody = $("#features_tbody");
        }

        let count = tableBody.find(".feature-row").length;

        let newRow = `
        <div class="feature-row">
            <textarea
                class="form-control feature_desc"
                name="features[${count}][description]"
                placeholder="Feature description"
                rows="2"></textarea>
            <button type="button" class="feature-remove-btn delete-feature-row" title="Remove feature">
                <i class="fa fa-trash"></i>
            </button>
        </div>`;

        tableBody.append(newRow);

    });



    // DELETE FEATURE
    $(document).on("click", ".delete-feature-row", function () {

        $(this).closest(".feature-row").remove();

    });



    // SERIALIZE FEATURES BEFORE SUBMIT
    $(document).on("submit", "#subscription_form", function () {

        let features = [];

        $(this).find("#features_tbody .feature-row").each(function () {

            let description = $(this).find(".feature_desc").val().trim();

            if (description !== "") {

                features.push({
                    description: description
                });

            }

        });

        $("#features_json").val(JSON.stringify(features));

    });

});

</script>