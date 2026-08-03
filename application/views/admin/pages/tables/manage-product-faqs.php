<div class="content-wrapper admin-product-faqs-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-question-circle mr-2 text-primary-theme"></i>Product FAQs</h4>
                    <p class="text-muted mb-0 small">Questions customers have asked, and the answers given, across every product.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Product FAQs</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Edit modal. The shared edit_btn handler in custom.js loads the answer form into
                 .edit-modal-lg .modal-body, so this markup has to stay on the page. -->
            <div id="product_faq_value_id" class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="editFaqLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editFaqLabel">Answer Product FAQ</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-0">
                            <form class="form-horizontal form-submit-event" id="product_edit_faq_form" action="<?= base_url('admin/product/edit_product_faqs'); ?>" method="POST" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php if (isset($fetched_data[0]['id'])) { ?>
                                        <input type="hidden" name="edit_product_faq" value="<?= @$fetched_data[0]['id'] ?>">
                                    <?php } ?>
                                    <div class="form-group row">
                                        <label for="question" class="col-sm-2 col-form-label">Question</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="question" placeholder="Question" name="question" value="<?= @$fetched_data[0]['question'] ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="answer" class="col-sm-2 col-form-label">Answer <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="answer" placeholder="Answer" name="answer" value="<?= @$fetched_data[0]['answer'] ?>">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <button type="reset" class="btn btn-warning">Reset</button>
                                        <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Answer' : 'Add Answer' ?></button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <div class="form-group" id="error_box"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-question-circle"></i></span>
                        <h5 class="mb-0">All Product FAQs</h5>
                    </div>
                    <a href="<?= base_url('admin/product_faqs/create_product_faqs') ?>" class="btn btn-primary-theme btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add Product FAQ
                    </a>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='products_faqs_table' data-toggle="table"
                        data-url="<?= base_url('admin/product_faqs/get_faqs_list') ?>" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                        data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                        data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]'
                        data-export-options='{"fileName": "product-faqs-list", "ignoreColumn": ["operate"]}'
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-align='center'>ID</th>
                                <th data-field="user_id" data-sortable="false" data-visible='false'>User Id</th>
                                <th data-field="product_id" data-sortable="true" data-visible='false'>Product Id</th>
                                <th data-field="question" data-sortable="true">Question</th>
                                <th data-field="answer" data-sortable="true">Answer</th>
                                <th data-field="answered_by" data-sortable="false" data-visible='false'>Answered by</th>
                                <th data-field="answered_by_name" data-sortable="false" data-align='center'>Answered By</th>
                                <th data-field="username" data-width='300' data-sortable="true" data-align='center'>Asked By</th>
                                <th data-field="date_added" data-sortable="true" data-align='center'>Date</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-product-faqs-page .text-primary-theme { color: var(--color-orange); }

    .admin-product-faqs-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-product-faqs-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-product-faqs-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-product-faqs-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-product-faqs-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-product-faqs-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-product-faqs-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-product-faqs-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-product-faqs-page .fixed-table-toolbar .btn-group > .btn,
    .admin-product-faqs-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-product-faqs-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-product-faqs-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-product-faqs-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-product-faqs-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-product-faqs-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-product-faqs-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-product-faqs-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-product-faqs-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-product-faqs-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-product-faqs-page .action-btn { border-radius: 6px; }
    .admin-product-faqs-page .modal-header { border-bottom: 2px solid var(--color-secondary); }
    .admin-product-faqs-page .modal-title { color: #2b2f33; }
</style>
