<div class="content-wrapper admin-shipping-policy-page">
  <!-- Content Header (Page header) -->
  <!-- Main content -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h4 class="mb-0"><i class="fas fa-shipping-fast mr-2 text-primary-theme"></i>Shipping Policy</h4>
          <p class="text-muted mb-0 small">Content shown to customers describing the marketplace's shipping policy.</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
            <li class="breadcrumb-item active">Shipping policy</li>
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
            <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
              <div class="d-flex align-items-center">
                <span class="header-icon bg-set mr-2"><i class="fas fa-shipping-fast"></i></span>
                <h5 class="mb-0">Shipping Policy Content</h5>
              </div>
            </div>
            <div class="card-body pt-3">
              <!-- form start -->
              <form class="form-horizontal form-submit-event" action="<?= base_url('admin/privacy_policy/update_shipping_policy_settings'); ?>" method="POST" enctype="multipart/form-data">
                <div class="pad">
                  <label for="other_images" class="font-weight-bold mb-1">Shipping Policy</label>
                  <a href="<?= base_url('admin/privacy-policy/shipping-policy-page') ?>" target='_blank' class="btn btn-primary btn-xs" title='View Shipping Policy'><i class='fa fa-eye'></i></a>
                  <div class="mb-3">
                    <textarea name="shipping_policy_input_description" class="textarea addr_editor" placeholder="Place some text here">
                            <?= $shipping_policy ?>
                    </textarea>
                  </div>
                </div>

                <div class="form-group">
                  <button type="reset" class="btn btn-warning">Reset</button>
                  <button type="submit" class="btn btn-success" id="submit_btn">Shipping Policy</button>
                </div>

                <div class="d-flex justify-content-center">
                  <div class="form-group" id="error_box">
                  </div>
                </div>
                <!-- /.card-body -->
              </form>
            </div>
          </div>
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
    .admin-shipping-policy-page .text-primary-theme { color: var(--color-orange); }

    .admin-shipping-policy-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-shipping-policy-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-shipping-policy-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-shipping-policy-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-shipping-policy-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-shipping-policy-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-shipping-policy-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-shipping-policy-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }
</style>