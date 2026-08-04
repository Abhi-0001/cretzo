<div class="content-wrapper admin-priv-policy-page">
  <!-- Content Header (Page header) -->
  <!-- Main content -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h4 class="mb-0"><i class="fas fa-user-shield mr-2 text-primary-theme"></i>Privacy Policy And Terms & Conditions</h4>
          <p class="text-muted mb-0 small">Privacy Policy and Terms & Conditions content shown to customers on the storefront.</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
            <li class="breadcrumb-item active">Privacy Policy And Terms & Conditions</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>
  <section class="content">
    <div class="container-fluid">

      <div class="card attribute-card">
        <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
          <div class="d-flex align-items-center">
            <span class="header-icon bg-set mr-2"><i class="fas fa-user-shield"></i></span>
            <h5 class="mb-0">Privacy Policy & Terms</h5>
          </div>
        </div>
        <div class="card-body pt-3">
          <!-- form start -->
          <form class="form-horizontal form-submit-event" action="<?= base_url('admin/Privacy_policy/update-privacy-policy-settings'); ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group sub-section">
              <div class="d-flex justify-content-between align-items-center sub-section-title">
                <label for="other_images" class="form-group-label mb-0"><i class="fas fa-file-contract mr-2"></i>Privacy Policy</label>
                <a href="<?= base_url('admin/privacy-policy/privacy-policy-page') ?>" target='_blank' class="btn btn-primary-theme btn-sm" title='View Privacy Policy'><i class='fa fa-eye mr-1'></i>View</a>
              </div>
              <div class="mb-3">
                <textarea name="privacy_policy_input_description" class="textarea addr_editor" placeholder="Place some text here text">
                        <?= $privacy_policy ?>
                </textarea>
              </div>
            </div>
            <div class="form-group sub-section">
              <div class="d-flex justify-content-between align-items-center sub-section-title">
                <label for="other_images" class="form-group-label mb-0"><i class="fas fa-file-signature mr-2"></i>Terms & Conditions</label>
                <a href="<?= base_url('admin/privacy-policy/terms-and-conditions-page') ?>" target='_blank' class="btn btn-primary-theme btn-sm" title='View Terms && Condition'><i class='fa fa-eye mr-1'></i>View</a>
              </div>
              <div class="mb-3">
                <textarea name="terms_n_conditions_input_description" class="textarea addr_editor" placeholder="Place some text here">
                        <?= $terms_n_condition ?>
                      </textarea>
              </div>

              <div class="form-group mb-0">
                <button type="reset" class="btn btn-warning">Reset</button>
                <button type="submit" class="btn btn-success" id="submit_btn">Update Privacy Policy And Terms & Conditions</button>
              </div>
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
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>

<style>
    .admin-priv-policy-page .text-primary-theme { color: var(--color-orange); }

    .admin-priv-policy-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-priv-policy-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-priv-policy-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-priv-policy-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-priv-policy-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-priv-policy-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-priv-policy-page .form-group-label { font-weight: 600; margin-bottom: 6px; display: block; }

    .admin-priv-policy-page .sub-section { padding-bottom: 20px; margin-bottom: 20px; border-bottom: 1px dashed rgba(0,0,0,0.1); }
    .admin-priv-policy-page .sub-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .admin-priv-policy-page .sub-section-title { margin-bottom: 10px; }
    .admin-priv-policy-page .sub-section-title .form-group-label i { color: var(--color-orange); }

    .admin-priv-policy-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 0.2rem rgba(255, 145, 0, 0.15);
    }

    .admin-priv-policy-page .btn-success { background: var(--color-orange); border-color: var(--color-orange); }
    .admin-priv-policy-page .btn-success:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }
    .admin-priv-policy-page .btn-warning { color: var(--color-grey); }
</style>