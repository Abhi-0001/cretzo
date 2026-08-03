<div class="content-wrapper admin-promo-code-form-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-percent mr-2 text-primary-theme"></i><?= (isset($fetched_details[0]['id'])) ? 'Edit Promo Code' : 'Add Promo Code' ?></h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/promo-code/manage-promo-code') ?>">Promo Code</a></li>
                        <li class="breadcrumb-item active"><?= (isset($fetched_details[0]['id'])) ? 'Edit' : 'Add' ?></li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-percent"></i></span>
                            <h5 class="mb-0"><?= (isset($fetched_details[0]['id'])) ? 'Edit Promo Code' : 'Add Promo Code' ?></h5>
                        </div>
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/promo_code/add_promo_code'); ?>" method="POST">

                            <div class="card-body">
                                <?php
                                if (isset($fetched_details[0]['id']) && !empty($fetched_details[0]['id'])) {
                                ?>
                                    <input type="hidden" name="edit_promo_code" value="<?= $fetched_details[0]['id'] ?>">
                                <?php
                                }
                                ?>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="">Promo Code <span class='text-danger text-sm'>*</span></label>
                                        <input type="text" class="form-control" name="promo_code" value="<?= isset($fetched_details[0]['promo_code']) ? html_escape($fetched_details[0]['promo_code']) : '' ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">Message <span class='text-danger text-sm'>*</span></label>
                                        <input type="text" class="form-control" name="message" value="<?= isset($fetched_details[0]['message']) ? html_escape($fetched_details[0]['message']) : '' ?>">
                                    </div>
                                   
                                    <div class="form-group col-md-6">
                                        <label for="">Start Date <span class='text-danger text-sm'>*</span></label>
                                        <input type="date" class="form-control" name="start_date" id="start_date" min="<?= date('Y-m-d') ?>" value="<?= @$fetched_details[0]['start_date'] ?>">  
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">End Date <span class='text-danger text-sm'>*</span></label>
                                        <input type="date" class="form-control" name="end_date" id="end_date"  min="<?= date('Y-m-d') ?>" value="<?= @$fetched_details[0]['end_date'] ?>">
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="">No. Of Users <span class='text-danger text-sm'>*</span></label>
                                        <input type="number" min="0" class="form-control" name="no_of_users" value="<?= @$fetched_details[0]['no_of_users'] ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">Minimum Order Amount <span class='text-danger text-sm'>*</span></label>
                                        <input type="number" min="1" class="form-control" name="minimum_order_amount" value="<?= @$fetched_details[0]['minimum_order_amount'] ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">Discount <span class='text-danger text-sm'>*</span></label>
                                        <input type="number" min="1" class="form-control discount" name="discount" id="discount" value="<?= @$fetched_details[0]['discount'] ?>">
                                        <div class="error"></div>
                                    </div>


                                    <div class="form-group col-md-6">
                                        <label for="">Discount Type <span class='text-danger text-sm'>*</span></label>
                                        <select name="discount_type" class="form-control discount_type">
                                            <option value="">Select</option>
                                            <option value="percentage" <?= (isset($fetched_details[0]['discount_type']) && $fetched_details[0]['discount_type'] == 'percentage') ? 'selected' : '' ?>>Percentage</option>
                                            <option value="amount" <?= (isset($fetched_details[0]['discount_type']) && $fetched_details[0]['discount_type'] == 'amount') ? 'selected' : '' ?>>Amount</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">Max Discount Amount <span class='text-danger text-sm'>*</span></label>
                                        <input type="number" min="1" class="form-control" name="max_discount_amount" id="max_discount_amount" value="<?= @$fetched_details[0]['max_discount_amount'] ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">Repeat Usage <span class='text-danger text-sm'>*</span></label>
                                        <select name="repeat_usage" id="repeat_usage" class="form-control">
                                            <option value="">Select</option>
                                            <option value="1" <?= (isset($fetched_details[0]['repeat_usage']) && $fetched_details[0]['repeat_usage'] == '1') ? 'selected' : '' ?>>Allowed</option>
                                            <option value="0" <?= (isset($fetched_details[0]['repeat_usage']) && $fetched_details[0]['repeat_usage'] == '0') ? 'selected' : '' ?>>Not Allowed</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="">Status <span class='text-danger text-sm'>*</span></label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="">Select</option>
                                            <option value="1" <?= (isset($fetched_details[0]['status']) && $fetched_details[0]['status'] == '1') ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= (isset($fetched_details[0]['status']) && $fetched_details[0]['status'] == '0') ? 'selected' : '' ?>>Deactive</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6 <?= (isset($fetched_details[0]['repeat_usage']) && $fetched_details[0]['repeat_usage'] == '1') ? '' : 'd-none' ?>" id="repeat_usage_html">
                                        <label for=""> No of repeat usage </label>
                                        <input type="number" class="form-control" min='0' name="no_of_repeat_usage" id="no_of_repeat_usage" value="<?= @$fetched_details[0]['no_of_repeat_usage'] ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="is_cashback"> Is Cashback? </label>
                                        <div class="card-body">
                                            <input type="checkbox" name="is_cashback" <?= (isset($fetched_details[0]['is_cashback']) && $fetched_details[0]['is_cashback'] == '1') ? 'Checked' : ''  ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="is_cashback"> List Promocode? </label>
                                        <div class="card-body">
                                            <input type="checkbox" name="list_promocode" <?= (isset($fetched_details[0]['list_promocode']) && $fetched_details[0]['list_promocode'] == '0') ? '' : 'Checked'  ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success " id="submit_btn"><?= (isset($fetched_details[0]['id'])) ? 'Update Promo Code' : 'Add Promo Code' ?></button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
                                </div>
                            </div>
                            <!-- /.card-footer -->
                        </form>
                    </div>
                    <!--/.card-->
                </div>
                <!--/.col-md-12-->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<style>
    .admin-promo-code-form-page .text-primary-theme { color: var(--color-orange); }

    .admin-promo-code-form-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-promo-code-form-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px 10px 0 0;
    }
    .admin-promo-code-form-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-promo-code-form-page .header-icon.bg-set { background: var(--color-orange); }
</style>
