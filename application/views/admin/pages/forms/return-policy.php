<div class="content-wrapper admin-return-policy-page">
  <!-- Content Header (Page header) -->
  <!-- Main content -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h4 class="mb-0"><i class="fas fa-undo-alt mr-2 text-primary-theme"></i>Return Policy</h4>
          <p class="text-muted mb-0 small">Content shown to customers describing the marketplace's return policy.</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
            <li class="breadcrumb-item active">Return Policy</li>
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
                <span class="header-icon bg-set mr-2"><i class="fas fa-undo-alt"></i></span>
                <h5 class="mb-0">Return Policy Content</h5>
              </div>
            </div>
            <div class="card-body pt-3">
              <!-- form start -->
              <form class="form-horizontal form-submit-event" action="<?= base_url('admin/privacy_policy/update_return_policy_settings'); ?>" method="POST" enctype="multipart/form-data">
                <div class="pad">
                  <label for="other_images" class="font-weight-bold mb-1">Return Policy</label>
                  <a href="<?= base_url('admin/privacy-policy/return-policy-page') ?>" target='_blank' class="btn btn-primary btn-xs" title='View return Policy'><i class='fa fa-eye'></i></a>
                  <div class="mb-3">
                    <textarea name="return_policy_input_description" class="textarea addr_editor" placeholder="Place some text here text">
                            <?= $return_policy ?>
                    </textarea>
                  </div>
                </div>

                <div class="form-group">
                  <button type="reset" class="btn btn-warning">Reset</button>
                  <button type="submit" class="btn btn-success" id="submit_btn">Return Policy</button>
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
    .admin-return-policy-page .text-primary-theme { color: var(--color-orange); }

    .admin-return-policy-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-return-policy-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-return-policy-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-return-policy-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-return-policy-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-return-policy-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-return-policy-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
    }
    .admin-return-policy-page .btn-success:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
    }
</style>