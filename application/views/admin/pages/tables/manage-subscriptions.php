    <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-md-6">
                    <h4>Manage Subscription Plans</h4>
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
                    <div class="card card-info">
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/subscription/add_subscription'); ?>" method="POST" id="subscription_form">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" id="edit_subscription" name="edit_subscription" value="<?= @$fetched_data[0]['id'] ?>">
                            <?php } ?>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="name" class="control-label">Plan Name <span class='text-danger text-xs'>*</span></label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" name="name" id="name" value="<?= (isset($fetched_data[0]['name']) ? $fetched_data[0]['name'] : '') ?>">
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
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" id="features_table">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th style="width: 85%;">Description</th>
                                                        <th style="width: 15%; text-align: center;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="features_tbody">

                                            <?php
                                            
                                            
                                                if (!empty($fetched_data[0]['features'])) {
                                     
                                                
                                                     $json = stripslashes($fetched_data[0]['features']);
                                                    $features = json_decode($json, true);
                                                
                                                    
                                                    
                                                    if (!empty($features)) {
                                           
                                                    
                                                        foreach ($features as $index => $feature) {
                                                ?>

                                                <tr class="feature-row">

                                                <td>
                                                <textarea 
                                                class="form-control form-control-sm feature_desc"
                                                name="features[<?= $index ?>][description]"
                                                rows="2"
                                                placeholder="Feature description"><?= htmlspecialchars($feature['description']) ?></textarea>
                                                </td>

                                                <td style="text-align:center;">
                                                <button type="button" class="btn btn-danger btn-sm delete-feature-row">
                                                <i class="fa fa-trash"></i>
                                                </button>
                                                </td>

                                                </tr>

                                                <?php
                                                        }

                                                    }
                                                }
                                                ?>

                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="add_feature_row">
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
                    <div class="card content-area p-4">
                        <div class="card-head">
                            <h4 class="card-title">Subscription Details</h4>
                        </div>
                        <div class="card-innr">
                            <div class="gaps-1-5x"></div>
                            <table class='table-striped' data-toggle="table" data-url="<?= base_url('admin/subscription/view_subscription') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
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
                        </div><!-- .card-innr -->
                    </div><!-- .card -->
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<script>

$(document).ready(function () {

    // ADD FEATURE ROW
    $(document).on("click", "#add_feature_row", function (e) {

        e.preventDefault();

        let tableBody = $(this).closest(".card-body").find("#features_tbody");

        if (tableBody.length === 0) {
            tableBody = $("#features_tbody");
        }

        let count = tableBody.find("tr").length;

        let newRow = `
        <tr class="feature-row">
            <td>
                <textarea 
                class="form-control form-control-sm feature_desc"
                name="features[${count}][description]"
                placeholder="Feature description"
                rows="2"></textarea>
            </td>

            <td style="text-align:center;">
                <button type="button" class="btn btn-danger btn-sm delete-feature-row">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>`;

        tableBody.append(newRow);

    });



    // DELETE FEATURE
    $(document).on("click", ".delete-feature-row", function () {

        $(this).closest("tr").remove();

    });



    // SERIALIZE FEATURES BEFORE SUBMIT
    $(document).on("submit", "#subscription_form", function () {

        let features = [];

        $(this).find("#features_tbody tr").each(function () {

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