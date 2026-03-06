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
                                        <input type="text" class="form-control" name="price" id="price" value="<?= (isset($fetched_data[0]['price']) ? $fetched_data[0]['price'] : '') ?>">
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
                                        <label for="validity" class="control-label">Validity</label>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <input type="text" class="form-control" name="validity" id="validity" value="<?= (isset($fetched_data[0]['validity']) ? $fetched_data[0]['validity'] : '') ?>">
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
                                <div class="form-group">
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
