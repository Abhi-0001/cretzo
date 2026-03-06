<link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/form.css') ?>">
<script src="<?= base_url('assets/seller/js/cretzo/form.js') ?>"></script>
<div class="m-4">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
     
    <section class="content form-box">
        
    <?php $section === 'personal' ? $this->load->view('seller/components/personal_details_slider.php') :( $section === 'store' ?  $this->load->view('seller/components/store_details_slider.php') : $this->load->view('seller/components/bank_details_slider.php')) ?>

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card_seller card-info m-0 form-card">
                        <!-- form start -->
                        <form class="form-horizontal form-submit-event" action="<?= base_url('seller/auth/create-seller'); ?>" method="POST" id="add_product_form">
                            <?php if (isset($user_data) && !empty($user_data)) { ?>
                                <input type="hidden" name="user_id" value="<?= $user_data['to_be_seller_id'] ?>">
                                <input type='hidden' name='user_name' value='<?= $user_data['to_be_seller_name'] ?>'>
                                <input type='hidden' name='user_mobile' value='<?= $user_data['to_be_seller_mobile'] ?>'>
                            <?php
                            } ?>
                            <div class="card-body">
                                <div class="login-logo">
                                    <a href="<?= base_url() . 'seller/login' ?>"><img src="<?= base_url() . $logo ?>"></a>
                                </div>
                                <h4 class="mb-lg-5">Seller Registration</h4>
                                
                                <?php $section === 'personal' ?  $this->load->view('seller/pages/forms/personal-details-form.php') : ($section === 'store' ?  $this->load->view('seller/pages/forms/store-details-form.php') :  $this->load->view('seller/pages/forms/bank-details-form.php')) ?>

                               

                                
                                
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
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>