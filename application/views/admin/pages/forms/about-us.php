<div class="content-wrapper admin-about-us-page">
  <!-- Content Header (Page header) -->
  <!-- Main content -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h4 class="mb-0"><i class="fas fa-info-circle mr-2 text-primary-theme"></i>About Us</h4>
          <p class="text-muted mb-0 small">The About Us content shown to customers on the storefront.</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
            <li class="breadcrumb-item active">About Us</li>
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
            <span class="header-icon bg-set mr-2"><i class="fas fa-info-circle"></i></span>
            <h5 class="mb-0">About Us</h5>
          </div>
          <a href="<?= base_url('admin/about-us/about-us-page') ?>" target='_blank' class="btn btn-primary-theme btn-sm" title='View About Us'><i class='fa fa-eye mr-1'></i>View</a>
        </div>
        <div class="card-body pt-3">
          <!-- form start -->
          <form class="form-horizontal form-submit-event" action="<?= base_url('admin/About_us/update-about-us-settings'); ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
              <label for="other_images" class="form-group-label">About Us</label>
              <div class="mb-3">
                <textarea name="about_us_input_description" class="textarea addr_editor" placeholder="Place some text here">
                        <?= @$about_us ?>
                      </textarea>
              </div>
              <div class="form-group mb-0">
                <button type="reset" class="btn btn-warning">Reset</button>
                <button type="submit" class="btn btn-success" id="submit_btn">Update About Us</button>
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
    .admin-about-us-page .text-primary-theme { color: var(--color-orange); }

    .admin-about-us-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-about-us-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-about-us-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-about-us-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-about-us-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-about-us-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-about-us-page .form-group-label { font-weight: 600; margin-bottom: 6px; display: block; }

    .admin-about-us-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 0.2rem rgba(255, 145, 0, 0.15);
    }

    .admin-about-us-page .btn-success { background: var(--color-orange); border-color: var(--color-orange); }
    .admin-about-us-page .btn-success:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }
    .admin-about-us-page .btn-warning { color: var(--color-grey); }
</style>