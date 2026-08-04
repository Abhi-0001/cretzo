<div class="content-wrapper admin-faq-page">
  <!-- Content Header (Page header) -->
  <!-- Main content -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h4 class="mb-0"><i class="fas fa-question-circle mr-2 text-primary-theme"></i>FAQ</h4>
          <p class="text-muted mb-0 small">Frequently asked questions shown to sellers and customers.</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
            <li class="breadcrumb-item active">FAQ</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="modal fade" id="faqModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <form class="form-horizontal form-submit-event" action="<?= base_url('admin/faq/add_faq'); ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-header mb-1">
                  <h5 class="modal-title">Add FAQ</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">

                  <div class="form-group">
                    <label for="question" class="col-form-label">Question <span class='text-danger text-xs'>*</span></label>
                    <input type="text" class="form-control" name="question">
                  </div>
                  <div class="form-group">
                    <label for="answer" class="col-form-label">Answer <span class='text-danger text-xs'>*</span></label>
                    <textarea class="form-control" name="answer"></textarea>
                  </div>
                  <div class="d-flex justify-content-center">
                    <div class="form-group" id="error_box">
                    </div>
                  </div>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-warning" data-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-success">Add Faq</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="col-md-12 text-right mb-2">
          <button class="btn btn-primary-theme" data-toggle="modal" data-target="#faqModal">
            <i class="fas fa-plus mr-1"></i>Add FAQ
          </button>
        </div>
        <div class="col-10 mx-auto">
          <div class="accordion faq-accordion" id="faqExample">
            <?php
            $i = 1;
            foreach ($faq as $row) {
            ?>
              <form class="form-horizontal form-submit-event" action="<?= base_url('admin/faq/add_faq'); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_faq" value="<?= $row['id'] ?>">
                <div class="card faq-item">
                  <div class="card-body p-2 row faq-item-header" id="headingOne">
                    <div class="col-md-6 d-flex display-content-between">
                      <h5 class="mb-0">
                        <span class="btn btn-link faq-question-toggle" data-toggle="collapse" data-target="#collapseOne<?= $i ?>" aria-expanded="true" aria-controls="collapseOne">
                          <i class="fas fa-question-circle mr-1 faq-question-icon"></i><?= $i ?>. <span class="faq_question"><?= html_escape($row['question']) ?></span>
                        </span>
                      </h5>
                      <input type="type" name="question" placeholder="Enter question here " class="ml-3 form-control col-md-12 d-none">
                    </div>
                    <div class="col-md-6 text-right">
                      <button class="btn btn-success btn-xs edit_faq action-btn mr-1 mb-1 ml-1" type="button">
                        <i class="fa fa-pen"></i>
                      </button>
                      <a href="javascript:void(0)" class="btn btn-danger action-btn btn-xs delete_faq mb-1 ml-1 mr-1" type="button" data-id="<?= $row['id'] ?>">
                        <i class="fa fa-trash"></i>
                      </a>
                    </div>
                  </div>
                  <div id="collapseOne<?= $i ?>" class="collapse" aria-labelledby="headingOne" data-parent="#faqExample">
                    <div class="card-body faq-item-body">
                      <b>Answer :</b> <span class="faq_answer"><?= html_escape($row['answer']) ?></span>
                      <textarea class="d-none form-control col-md-12" name="answer" placeholder="Enter The Answer Here"></textarea>
                    </div>
                  </div>
                  <div class="col-md-12 m-2 save d-none">
                    <button class="btn btn-success" type="submit">
                      <i class="fa fa-save"></i> Save
                    </button>
                  </div>
                </div>
              </form>
            <?php $i++;
            } ?>
          </div>

        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>

<style>
    .admin-faq-page .text-primary-theme { color: var(--color-orange); }

    .admin-faq-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-faq-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-faq-page .faq-accordion { display: flex; flex-direction: column; gap: 12px; }

    .admin-faq-page .faq-item {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        overflow: hidden;
        transition: background-color .15s ease-in-out;
    }

    .admin-faq-page .faq-item-header {
        background: #fff;
        align-items: center;
        border-bottom: 1px solid transparent;
    }

    .admin-faq-page .faq-question-toggle {
        color: var(--color-black, #333);
        font-weight: 600;
        text-decoration: none;
        white-space: normal;
        text-align: left;
        padding: 0;
    }
    .admin-faq-page .faq-question-toggle:hover,
    .admin-faq-page .faq-question-toggle:focus {
        color: var(--color-orange);
        text-decoration: none;
    }
    .admin-faq-page .faq-question-icon { color: var(--color-orange); }

    .admin-faq-page .faq-question-toggle[aria-expanded="true"] {
        color: var(--color-orange-dark);
    }
    .admin-faq-page .faq-item:has(.collapse.show) .faq-item-header {
        background: var(--color-orange-light);
        border-bottom-color: rgba(0,0,0,0.06);
    }

    .admin-faq-page .faq-item-body {
        background: var(--color-orange-light);
        font-size: 14px;
    }

    .admin-faq-page .faq-item .action-btn { border-radius: 6px; }
</style>