<div class="content-wrapper product-faqs-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-question-circle mr-2 text-primary-theme"></i>Product FAQs</h4>
                    <p class="text-muted mb-0 small">Questions and answers attached to your products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Product FAQs</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div id="product_faq_value_id" class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">Edit Product FAQs</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body p-0">
                            <form class="form-horizontal form-submit-event" id="product_edit_faq_form" action="<?= base_url('seller/product/edit_product_faqs'); ?>" method="POST" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php
                                    if (isset($fetched_data[0]['id'])) { ?>
                                        <input type="hidden" name="edit_product_faq" value="<?= @$fetched_data[0]['id'] ?>">
                                    <?php  } ?>
                                    <div class="form-group row">
                                        <label for="question" class="col-sm-2 col-form-label">Question </label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="question" placeholder="question" name="question" value="<?= @$fetched_data[0]['question'] ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="answer" class="col-sm-2 col-form-label">Answer <span class='text-danger text-sm'>*</span></label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="answer" placeholder="Answer" name="answer" value="<?= @$fetched_data[0]['answer'] ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="reset" class="btn btn-light border">Reset</button>
                                        <button type="submit" class="btn btn-primary-theme" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Product Faq' : 'Add Product FAQ' ?></button>
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

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-question-circle"></i></span>
                        <h5 class="mb-0">Your Product FAQs</h5>
                    </div>
                    <a href="<?= base_url() . 'seller/product_faqs/create_product_faqs' ?>" class="btn btn-primary-theme btn-sm"><i class="fas fa-plus mr-1"></i>Add Product FAQs</a>
                </div>
                <div class="card-body">
                    <table class='table-striped' id='products_faqs_table' data-toggle="table" data-url="<?= base_url('seller/product_faqs/get_faqs_list') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel","csv"]' data-export-options='{
                        "fileName": "products-faqs-list",
                        "ignoreColumn": ["state"]
                        }'>
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">ID</th>
                                <th data-field="user_id" data-sortable="false" data-visible='false'>User Id</th>
                                <th data-field="product_id" data-sortable="false" data-visible='false'>Product Id</th>
                                <th data-field="question" data-sortable="false">Question</th>
                                <th data-field="answer" data-sortable="false">Answer</th>
                                <th data-field="answered_by" data-sortable="false" data-visible='false'>Answered by</th>
                                <th data-field="answered_by_name" data-sortable="false">Answered by Name</th>
                                <th data-field="username" data-width='500' data-sortable="false" class="col-md-6">Username</th>
                                <th data-field="date_added" data-sortable="false">Date added</th>
                                <th data-field="operate" data-sortable="false">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .product-faqs-page .text-primary-theme { color: var(--color-orange); }

    .product-faqs-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .product-faqs-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .product-faqs-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        margin-right: 10px;
    }
    .product-faqs-page .header-icon.bg-set { background: var(--color-orange); }

    .product-faqs-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
        border-radius: 6px;
    }
    .product-faqs-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }
    .product-faqs-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }

    .product-faqs-page .fixed-table-toolbar {
        margin-bottom: 10px;
    }
    .product-faqs-page .fixed-table-toolbar > div {
        margin-left: 10px !important;
    }
    .product-faqs-page .fixed-table-toolbar .btn-group > .btn,
    .product-faqs-page .fixed-table-toolbar .btn-group > .keep-open {
        margin-left: 8px !important;
    }
    .product-faqs-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .product-faqs-page .fixed-table-toolbar .btn-group > .keep-open:first-child {
        margin-left: 0 !important;
    }
    .product-faqs-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .product-faqs-page .fixed-table-toolbar .search input:focus {
        border-color: var(--color-orange);
    }
    .product-faqs-page table.table thead th {
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
    .product-faqs-page table.table tbody td {
        vertical-align: middle;
        font-size: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    .product-faqs-page table.table tbody tr:hover {
        background-color: var(--color-orange-light);
    }
    .product-faqs-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff;    
        background-color: var(--color-orange);
        border-color: var(--color-orange);
    }
    .product-faqs-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark);
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .product-faqs-page .modal-header {
        border-bottom: 2px solid var(--color-secondary);
    }
</style>

<script>
    // None of this page's interactivity (open-edit-modal, submit-edit, delete) was wired up
    // for sellers before — the JS that implements it (.edit_btn / .form-submit-event /
    // .delete-seller-product-faq handlers) only ever shipped in the admin bundle, which
    // isn't loaded on seller pages. Everything below is self-contained to this page.

    $(document).on('click', '.edit_btn', function () {
        var id = $(this).data('id');
        var url = $(this).data('url');
        $('#product_faq_value_id').modal('show').find('.modal-body').load(base_url + url + '?edit_id=' + id + ' .form-submit-event');
    });

    $(document).on('submit', '#product_edit_faq_form', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.append(csrfName, csrfHash);
        var submitBtn = $('#submit_btn', form);
        var originalText = submitBtn.html();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            beforeSend: function () {
                submitBtn.html('Please Wait...').prop('disabled', true);
            },
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (result) {
                if (result.csrfName && result.csrfHash) {
                    csrfName = result.csrfName;
                    csrfHash = result.csrfHash;
                }
                if (result.error) {
                    $('#error_box', form).html('<span class="text-danger">' + result.message + '</span>');
                } else {
                    iziToast.success({ message: result.message });
                    $('#product_faq_value_id').modal('hide');
                    $('#products_faqs_table').bootstrapTable('refresh');
                }
            },
            error: function () {
                $('#error_box', form).html('<span class="text-danger">Something went wrong. Please try again.</span>');
            },
            complete: function () {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.delete-seller-product-faq', function () {
        var faq_id = $(this).data('id');
        Swal.fire({
            title: 'Are You Sure!',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                type: 'GET',
                url: base_url + 'seller/product_faqs/delete_product_faq',
                data: { id: faq_id },
                dataType: 'json'
            }).done(function (response) {
                if (response.csrfName && response.csrfHash) {
                    csrfName = response.csrfName;
                    csrfHash = response.csrfHash;
                }
                if (response.error === false) {
                    Swal.fire('Deleted!', response.message, 'success');
                } else {
                    Swal.fire('Oops...', response.message, 'error');
                }
                $('#products_faqs_table').bootstrapTable('refresh');
            }).fail(function () {
                Swal.fire('Oops...', 'Something went wrong!', 'error');
            });
        });
    });
</script>
