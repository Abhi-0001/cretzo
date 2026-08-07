<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h4>Add Seller</h4>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Seller</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="modal fade " tabindex="-1" role="dialog" aria-hidden="true" id='set_commission_model'>
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Categories & Commission(%)</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-0">
                                <form class="form-horizontal" id="add-seller-commission-form" action="<?= base_url('admin/sellers/add-seller-commission'); ?>" method="POST" enctype="multipart/form-data">

                                    <div class="card-body row">
                                        <!-- dynamic section here -->
                                        <label for="Categories" class="col-sm-2 col-form-label">Categories</label>

                                        <div id="category_section"> </div>

                                        <div class="form-group col-md-12  text-center">
                                            <button type="button" id="add_category" class="btn btn-primary"> <i class="far fa-plus"></i> Add More Category </button>
                                        </div>
                                        <br>
                                        <div class="form-group ">
                                            <button type="reset" class="btn btn-warning">Reset</button>
                                            <button type="submit" class="btn btn-success" id="save_btn">Save</button>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <div class="form-group" id="error_box">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card card-info">
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/sellers/add_seller'); ?>" method="POST" id="add_product_form">
                            <?php if (isset($fetched_data[0]['id'])) { ?>
                                <input type="hidden" name="edit_seller" value="<?= $fetched_data[0]['user_id'] ?>">
                                <input type="hidden" name="edit_seller_data_id" value="<?= $fetched_data[0]['id'] ?>">
                                <input type="hidden" name="old_address_proof" value="<?= $fetched_data[0]['address_proof'] ?>">
                                <input type="hidden" name="old_store_logo" value="<?= $fetched_data[0]['logo'] ?>">
                                <input type="hidden" name="old_authorized_signature" value="<?= $fetched_data[0]['authorized_signature'] ?>">
                                <input type="hidden" name="old_national_identity_card" value="<?= $fetched_data[0]['national_identity_card'] ?>">
                            <?php
                            } ?>
                            <div class="card-body">
                                <div class="form-group row">
                                    <textarea cols="20" rows="20" id="cat_data" name="commission_data" style="display:none;"></textarea>
                                    <label for="name" class="col-sm-2 col-form-label">Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="name" placeholder="Seller Name" name="name" value="<?= @$fetched_data[0]['username'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="mobile" class="col-sm-2 col-form-label">Mobile <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="number" class="form-control" id="mobile" placeholder="Enter Mobile" name="mobile" value="<?= @$fetched_data[0]['mobile'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="email" class="col-sm-2 col-form-label">Email <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="email" class="form-control" id="email" placeholder="Enter Email" name="email" value="<?= @$fetched_data[0]['email'] ?>">
                                    </div>
                                </div>
                                <?php
                                if (!isset($fetched_data[0]['id'])) {
                                ?>
                                    <div class="form-group row ">
                                        <label for="password" class="col-sm-2 col-form-label">Password <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-sm-10">
                                            <input type="password" class="form-control" id="password" placeholder="Enter Passsword" name="password" value="<?= @$fetched_data[0]['password'] ?>">
                                        </div>
                                    </div>
                                    <div class="form-group row ">
                                        <label for="confirm_password" class="col-sm-2 col-form-label">Confirm Password <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-sm-10">
                                            <input type="password" class="form-control" id="confirm_password" placeholder="Enter Confirm Password" name="confirm_password">
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                                <div class="form-group row">
                                    <label for="address" class="col-sm-2 col-form-label">Address <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <textarea type="text" class="form-control" id="address" placeholder="Enter Address" name="address"><?= isset($fetched_data[0]['address']) ? @$fetched_data[0]['address'] : ""; ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="address_proof" class="col-sm-2 col-form-label">Address Proof <span class='text-danger text-sm'>*</span> </label>
                                    <div class="col-sm-10">
                                        <?php if (isset($fetched_data[0]['address_proof']) && !empty($fetched_data[0]['address_proof'])) { ?>
                                            <span class="text-danger">*Leave blank if there is no change</span>
                                        <?php } ?>
                                        <input type="file" class="form-control" name="address_proof" id="address_proof" accept="image/*" />
                                    </div>
                                </div>
                                <?php if (isset($fetched_data[0]['address_proof']) && !empty($fetched_data[0]['address_proof'])) { ?>
                                    <div class="form-group row">
                                        <div class="mx-auto product-image"><a href="<?= base_url($fetched_data[0]['address_proof']); ?>" data-toggle="lightbox" data-gallery="gallery_seller"><img src="<?= base_url($fetched_data[0]['address_proof']); ?>" class="img-fluid rounded"></a></div>
                                    </div>
                                <?php } ?>
                                <div class="form-group row">
                                    <label for="authorized_signature" class="col-sm-2 col-form-label">Authorized Signature <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <?php if (isset($fetched_data[0]['authorized_signature']) && !empty($fetched_data[0]['authorized_signature'])) { ?>
                                            <span class="text-danger">*Leave blank if there is no change</span>
                                        <?php } ?>
                                        <input type="file" class="form-control" name="authorized_signature" id="authorized_signature" accept="image/*" />
                                    </div>
                                </div>
                                <?php if (isset($fetched_data[0]['authorized_signature']) && !empty($fetched_data[0]['authorized_signature'])) { ?>
                                    <div class="form-group row">
                                        <div class="mx-auto product-image"><a href="<?= base_url($fetched_data[0]['authorized_signature']); ?>" data-toggle="lightbox" data-gallery="gallery_seller"><img src="<?= base_url($fetched_data[0]['authorized_signature']); ?>" class="img-fluid rounded"></a></div>
                                    </div>
                                <?php } ?>

                                <div class="form-group row">
                                    <label for="commission" class="col-sm-2 col-form-label">Commission(%) <small>(Commission(%) to be given to the Super Admin on order item globally.)</small> </label>
                                    <div class="col-sm-10">
                                        <input type="number" class="form-control" id="global_commission" placeholder="Enter Commission(%) to be given to the Super Admin on order item." name="global_commission" value="<?= @$fetched_data[0]['commission'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <?php
                                    $category_html =  get_categories_option_html($categories);
                                    ?>
                                    <label for="commission" class="col-sm-8 col-form-label">Choose Categories & Commission(%) <small>(Commission(%) to be given to the Super Admin on order item by Category you select.If you do not set the commission beside category then it will get global commission other wise perticuler category commission will be consider.)</small> </label>
                                    <div style="display:none" id="cat_html">
                                        <?= $category_html ?>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-3 offset-2">
                                        <a href="javascript:void(0)" id="seller_model" data-seller_id="<?= (isset($fetched_data[0]['user_id']) && !empty($fetched_data[0]['user_id'])) ? $fetched_data[0]['user_id'] : ""; ?>" data-cat_ids="<?= (isset($fetched_data[0]['id']) &&  !empty($fetched_data[0]['id'])) ? $fetched_data[0]['category_ids'] : ""; ?>" class=" btn btn-block  btn-outline-primary btn-sm" title="Manage Categories & Commission" data-target="#set_commission_model" data-toggle="modal">Manage</a>
                                    </div>
                                </div>
                                <h4>Store Details</h4>
                                <hr>
                                <div class="form-group row">
                                    <label for="store_name" class="col-sm-2 col-form-label">Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="store_name" placeholder="Store Name" name="store_name" value="<?= @$fetched_data[0]['store_name'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="store_url" class="col-sm-2 col-form-label">URL </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="store_url" placeholder="Store URL" name="store_url" value="<?= @$fetched_data[0]['store_url'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="store_description" class="col-sm-2 col-form-label">Description </label>
                                    <div class="col-sm-10">
                                        <textarea type="text" class="form-control" id="store_description" placeholder="Store Description" name="store_description"><?= isset($fetched_data[0]['store_description']) ? @$fetched_data[0]['store_description'] : ""; ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="logo" class="col-sm-2 col-form-label">Logo <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <?php if (isset($fetched_data[0]['logo']) && !empty($fetched_data[0]['logo'])) { ?>
                                            <span class="text-danger">*Leave blank if there is no change</span>
                                        <?php } ?>
                                        <input type="file" class="form-control" name="store_logo" id="store_logo" accept="image/*" />
                                    </div>
                                </div>
                                <?php if (isset($fetched_data[0]['logo']) && !empty($fetched_data[0]['logo'])) { ?>
                                    <div class="form-group row">
                                        <div class="mx-auto product-image"><a href="<?= base_url($fetched_data[0]['logo']); ?>" data-toggle="lightbox" data-gallery="gallery_seller"><img src="<?= base_url($fetched_data[0]['logo']); ?>" class="img-fluid rounded"></a></div>
                                    </div>
                                <?php } ?>
                                <h4>Bank Details</h4>
                                <hr>
                                <div class="form-group row">
                                    <label for="account_number" class="col-sm-2 col-form-label">Account Number </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="account_number" placeholder="Account Number" name="account_number" value="<?= @$fetched_data[0]['account_number'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="account_name" class="col-sm-2 col-form-label">Account Name </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="account_name" placeholder="Account Name" name="account_name" value="<?= @$fetched_data[0]['account_name'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="bank_code" class="col-sm-2 col-form-label">Bank Code</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="bank_code" placeholder="Bank Code" name="bank_code" value="<?= @$fetched_data[0]['bank_code'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="bank_name" class="col-sm-2 col-form-label">Bank Name </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="bank_name" placeholder="Bank Name" name="bank_name" value="<?= @$fetched_data[0]['bank_name'] ?>">
                                    </div>
                                </div>
                                <h4>Other Details</h4>
                                <hr>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Status <span class='text-danger text-sm'>*</span></label>
                                    <div id="status" class="btn-group col-sm-4">
                                        <label class="btn btn-default" data-toggle-class="btn-default" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="0" <?= (isset($fetched_data[0]['status']) && $fetched_data[0]['status'] == '0') ? 'Checked' : '' ?>> Deactive
                                        </label>
                                        <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="1" <?= (isset($fetched_data[0]['status']) && $fetched_data[0]['status'] == '1') ? 'Checked' : '' ?>> Approved
                                        </label>
                                        <label class="btn btn-danger" data-toggle-class="btn-danger" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="2" <?= (isset($fetched_data[0]['status']) && $fetched_data[0]['status'] == '2') ? 'Checked' : '' ?>> Not-Approved
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="national_identity_card" class="col-sm-2 col-form-label">National Identity Card <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <?php if (isset($fetched_data[0]['national_identity_card']) && !empty($fetched_data[0]['national_identity_card'])) { ?>
                                            <span class="text-danger">*Leave blank if there is no change</span>
                                        <?php } ?>
                                        <input type="file" class="form-control" name="national_identity_card" id="national_identity_card" accept="image/*" />
                                    </div>
                                </div>
                                <?php if (isset($fetched_data[0]['national_identity_card']) && !empty($fetched_data[0]['national_identity_card'])) { ?>
                                    <div class="form-group row">
                                        <div class="mx-auto product-image"><a href="<?= base_url($fetched_data[0]['national_identity_card']); ?>" data-toggle="lightbox" data-gallery="gallery_seller"><img src="<?= base_url($fetched_data[0]['national_identity_card']); ?>" class="img-fluid rounded"></a></div>
                                    </div>
                                <?php } ?>
                                <div class="form-group row">
                                    <label for="tax_name" class="col-sm-2 col-form-label">Tax Name <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="tax_name" placeholder="Tax Name" name="tax_name" value="<?= @$fetched_data[0]['tax_name'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="tax_number" class="col-sm-2 col-form-label">Tax Number <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="tax_number" placeholder="Tax Number" name="tax_number" value="<?= @$fetched_data[0]['tax_number'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="pan_number" class="col-sm-2 col-form-label">Pan Number </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="pan_number" placeholder="Pan Number" name="pan_number" value="<?= @$fetched_data[0]['pan_number'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="latitude" class="col-sm-2 col-form-label">Latitude </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="latitude" placeholder="Latitude" name="latitude" value="<?= @$fetched_data[0]['latitude'] ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="longitude" class="col-sm-2 col-form-label">Longitude </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="longitude" placeholder="Longitude" name="longitude" value="<?= @$fetched_data[0]['longitude'] ?>">
                                    </div>
                                </div>
                                <h4>Permissions </h4>
                                <hr>
                                <?php if (isset($fetched_data[0]['permissions']) && !empty($fetched_data[0]['permissions'])) {
                                    $permit = json_decode($fetched_data[0]['permissions'], true);
                                } ?>
                                <div class="form-group row">
                                    <label for="require_products_approval" class="col-sm-2 form-label">Require Product's Approval? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="require_products_approval" <?= (isset($permit['require_products_approval']) && $permit['require_products_approval'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                    <label for="customer_privacy" class="form-label">View Customer's Details? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="customer_privacy" <?= (isset($permit['customer_privacy']) && $permit['customer_privacy'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                    <label for="view_order_otp" class="form-label">View Order's OTP? & Can change deliver status? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="view_order_otp" <?= (isset($permit['view_order_otp']) && $permit['view_order_otp'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                    <label for="assign_delivery_boy" class="form-label">Can assign delivery boy? <span class='text-danger text-sm'>*</span></label>
                                    <div class="col-sm-1">
                                        <input type="checkbox" name="assign_delivery_boy" <?= (isset($permit['assign_delivery_boy']) && $permit['assign_delivery_boy'] == '1') ? 'Checked' : '' ?> data-bootstrap-switch data-off-color="danger" data-on-color="success">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="reset" class="btn btn-warning">Reset</button>
                                    <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Seller' : 'Add Seller' ?></button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group" id="error_box">
                                    <div class="card text-white d-none mb-3">
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-footer -->
                        </form>
                    </div>
                    <!--/.card-->
                </div>
                <!--/.col-md-12-->

                <?php if (isset($fetched_data[0]['id'])) {
                    $primary_category_name = '';
                    if (!empty($fetched_data[0]['primary_category_id'])) {
                        $primary_cat = fetch_details('categories', ['id' => $fetched_data[0]['primary_category_id']], 'name');
                        $primary_category_name = !empty($primary_cat[0]['name']) ? $primary_cat[0]['name'] : '';
                    }
                    $entity_type_labels = [
                        'individual' => 'Individual',
                        'sole_proprietorship' => 'Sole Proprietorship',
                        'partnership_firm' => 'Partnership Firm',
                        'pvt_ltd' => 'Pvt Ltd.',
                    ];
                    $kyc_field = function ($label, $value) {
                        $value = trim((string) $value);
                        echo '<div class="kyc-row"><span class="kyc-label">' . html_escape($label) . '</span><span class="kyc-value">' . ($value !== '' ? html_escape($value) : '<span class="text-muted">&mdash;</span>') . '</span></div>';
                    };
                    $kyc_doc = function ($label, $path) {
                        $path = trim((string) $path);
                        echo '<div class="kyc-row"><span class="kyc-label">' . html_escape($label) . '</span><span class="kyc-value">';
                        if ($path === '' || !file_exists(FCPATH . $path)) {
                            echo '<span class="text-muted">Not uploaded</span>';
                        } else {
                            $is_pdf = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
                            if ($is_pdf) {
                                echo '<a href="' . base_url($path) . '" target="_blank" class="btn btn-outline-primary btn-xs"><i class="fa fa-file-pdf"></i> View PDF</a>';
                            } else {
                                echo '<a href="' . base_url($path) . '" data-toggle="lightbox" data-gallery="gallery_seller_kyc"><img src="' . base_url($path) . '" class="kyc-doc-thumb" alt="' . html_escape($label) . '"></a>';
                            }
                        }
                        echo '</span></div>';
                    };
                ?>
                    <div class="col-md-12">
                        <div class="card attribute-card mt-4">
                            <div class="card-header attribute-card-header">
                                <span class="header-icon bg-set"><i class="fas fa-id-card"></i></span>
                                <h5 class="mb-0">KYC &amp; Onboarding Details</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-4">Everything below was submitted by the seller through their own onboarding/profile form. Shown here for review only - it is not editable from this page.</p>

                                <h6 class="kyc-section-title">Personal Information</h6>
                                <div class="kyc-grid">
                                    <?php
                                    $kyc_field('First Name', $fetched_data[0]['first_name'] ?? '');
                                    $kyc_field('Middle Name', $fetched_data[0]['middle_name'] ?? '');
                                    $kyc_field('Last Name', $fetched_data[0]['last_name'] ?? '');
                                    $kyc_field('Phone', $fetched_data[0]['phone'] ?? '');
                                    $kyc_field('Address Line 2', $fetched_data[0]['address2'] ?? '');
                                    $kyc_field('District', $fetched_data[0]['district'] ?? '');
                                    $kyc_field('City', $fetched_data[0]['city'] ?? '');
                                    $kyc_field('State', $fetched_data[0]['state'] ?? '');
                                    $kyc_field('Pin', $fetched_data[0]['pin'] ?? '');
                                    ?>
                                </div>

                                <h6 class="kyc-section-title">Shop Information</h6>
                                <div class="kyc-grid">
                                    <?php
                                    $kyc_field('Shop Phone', $fetched_data[0]['shop_phone'] ?? '');
                                    $kyc_field('Social Link', $fetched_data[0]['social'] ?? '');
                                    $kyc_field('Primary Category', $primary_category_name);
                                    ?>
                                </div>

                                <h6 class="kyc-section-title">Pickup Address</h6>
                                <div class="kyc-grid">
                                    <?php
                                    $kyc_field('Address Line 1', $fetched_data[0]['pickup_address1'] ?? '');
                                    $kyc_field('Address Line 2', $fetched_data[0]['pickup_address2'] ?? '');
                                    $kyc_field('District', $fetched_data[0]['pickup_district'] ?? '');
                                    $kyc_field('City', $fetched_data[0]['pickup_city'] ?? '');
                                    $kyc_field('State', $fetched_data[0]['pickup_state'] ?? '');
                                    $kyc_field('Pin', $fetched_data[0]['pickup_pin'] ?? '');
                                    ?>
                                </div>

                                <h6 class="kyc-section-title">Business / Legal Information</h6>
                                <div class="kyc-grid">
                                    <?php
                                    $kyc_field('Entity Type', $entity_type_labels[$fetched_data[0]['entity_type'] ?? ''] ?? ($fetched_data[0]['entity_type'] ?? ''));
                                    $kyc_field('Legal Business Name', $fetched_data[0]['legal_business_name'] ?? '');
                                    $kyc_field('Address Line 1', $fetched_data[0]['business_address1'] ?? '');
                                    $kyc_field('Address Line 2', $fetched_data[0]['business_address2'] ?? '');
                                    $kyc_field('District', $fetched_data[0]['business_district'] ?? '');
                                    $kyc_field('City', $fetched_data[0]['business_city'] ?? '');
                                    $kyc_field('State', $fetched_data[0]['business_state'] ?? '');
                                    $kyc_field('Pin', $fetched_data[0]['business_pin'] ?? '');
                                    $kyc_field('GST Registered?', (isset($fetched_data[0]['is_gst_registered']) && $fetched_data[0]['is_gst_registered'] == 0) ? 'No (GST Enrollment Number only)' : 'Yes');
                                    $kyc_field('GST Number', $fetched_data[0]['gst'] ?? '');
                                    $kyc_field('GST Enrollment Number', $fetched_data[0]['gst_enrollment_number'] ?? '');
                                    $kyc_field('PAN', $fetched_data[0]['pan'] ?? '');
                                    ?>
                                </div>

                                <h6 class="kyc-section-title">Bank Details</h6>
                                <div class="kyc-grid">
                                    <?php
                                    $kyc_field('Account Holder Name', $fetched_data[0]['account_holder_name'] ?? '');
                                    $kyc_field('IFSC', $fetched_data[0]['ifsc'] ?? '');
                                    $kyc_field('Branch', $fetched_data[0]['branch'] ?? '');
                                    ?>
                                </div>

                                <h6 class="kyc-section-title">Documents</h6>
                                <div class="kyc-grid">
                                    <?php
                                    $kyc_doc('PAN Card', $fetched_data[0]['pan_card_document'] ?? '');
                                    $kyc_doc('GSTIN Document', $fetched_data[0]['gstin_document'] ?? '');
                                    $kyc_doc('GST Enrollment Acknowledgement', $fetched_data[0]['gst_enrollment_ack_document'] ?? '');
                                    $kyc_doc('Business Proof', $fetched_data[0]['business_proof_document'] ?? '');
                                    $kyc_doc('Business Address Proof', $fetched_data[0]['business_address_proof_document'] ?? '');
                                    $kyc_doc('Partnership Deed', $fetched_data[0]['partnership_deed_document'] ?? '');
                                    $kyc_doc('Bank Account Proof', $fetched_data[0]['bank_account_proof_document'] ?? '');
                                    ?>
                                </div>

                                <h6 class="kyc-section-title">Verification</h6>
                                <div class="kyc-grid">
                                    <?php
                                    $kyc_field("Seller's Request Note", $fetched_data[0]['verification_request_note'] ?? '');
                                    $kyc_field('Requested At', $fetched_data[0]['verification_request_at'] ?? '');
                                    ?>
                                </div>
                                <form id="verification_note_form" class="mt-3">
                                    <input type="hidden" name="user_id" value="<?= $fetched_data[0]['user_id'] ?>">
                                    <div class="form-group">
                                        <label for="verification_note_input">Admin verification note</label>
                                        <textarea name="verification_note" id="verification_note_input" class="form-control" rows="3" placeholder="Write a note back to the seller about their verification review..."><?= html_escape($fetched_data[0]['verification_note'] ?? '') ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Save Verification Note</button>
                                    <span id="verification_note_result" class="ml-2"></span>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<script>
    $(document).on('submit', '#verification_note_form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $result = $('#verification_note_result');
        var $btn = $form.find('button[type="submit"]');
        $.ajax({
            type: 'POST',
            url: '<?= base_url('admin/sellers/save_verification_note') ?>',
            data: $form.serialize() + '&' + csrfName + '=' + csrfHash,
            dataType: 'json',
            beforeSend: function () {
                $btn.attr('disabled', true);
            },
            success: function (result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName;
                    csrfHash = result.csrfHash;
                }
                $result.text(result.message).css('color', result.error ? '#dc3545' : '#28a745');
                $btn.attr('disabled', false);
            },
            error: function () {
                $result.text('Something went wrong. Please try again.').css('color', '#dc3545');
                $btn.attr('disabled', false);
            }
        });
    });
</script>

<style>
    .kyc-section-title {
        font-weight: 600;
        color: #2b2f33;
        margin-top: 20px;
        padding-bottom: 8px;
        border-bottom: 2px solid rgba(0,0,0,0.06);
    }
    .kyc-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0 24px;
    }
    .kyc-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        max-width: 420px;
        padding: 7px 0;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        font-size: 13px;
    }
    .kyc-label { color: var(--color-grey, #6c757d); flex: none; }
    .kyc-value { color: #2b2f33; font-weight: 500; text-align: right; word-break: break-word; }
    .kyc-doc-thumb {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid rgba(0,0,0,0.08);
    }
</style>
