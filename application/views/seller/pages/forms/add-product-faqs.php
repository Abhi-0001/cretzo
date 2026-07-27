<div class="content-wrapper add-product-faq-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-question-circle mr-2 text-primary-theme"></i>Add Product FAQ</h4>
                    <p class="text-muted mb-0 small">Pre-answer a common question for one of your products.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/product_faqs') ?>">Product FAQs</a></li>
                        <li class="breadcrumb-item active">Add</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card attribute-card">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set"><i class="fas fa-question-circle"></i></span>
                    <h5 class="mb-0">New FAQ</h5>
                </div>
                <div class="card-body">
                    <form id="add_product_faq_form" action="<?= base_url('seller/product_faqs/add_faqs'); ?>" method="POST" enctype="multipart/form-data">
                        <div class="form-group row">
                            <label for="product_id" class="col-sm-2 col-form-label">Select Product <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <select name="product_id" id="product_id" class="form-control" required>
                                    <option value="">Select a product</option>
                                    <?php foreach (($seller_products ?? []) as $row) : ?>
                                        <option value="<?= (int) $row['id'] ?>"><?= output_escaping($row['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="question" class="col-sm-2 col-form-label">Question <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="question" placeholder="Question" name="question" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="answer" class="col-sm-2 col-form-label">Answer <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="answer" placeholder="Answer" name="answer" required>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary-theme mr-2" id="submit_btn"><i class="fas fa-plus mr-1"></i>Add Product FAQ</button>
                            <button type="reset" class="btn btn-light border">Reset</button>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div class="form-group mb-0" id="error_box"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .add-product-faq-page .text-primary-theme { color: var(--color-orange); }
    .add-product-faq-page .attribute-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    }
    .add-product-faq-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .add-product-faq-page .header-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
    .add-product-faq-page .header-icon.bg-set { background: var(--color-orange); }
    .add-product-faq-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    .add-product-faq-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
        border-radius: 6px;
    }
    .add-product-faq-page .btn-primary-theme:hover {
        background: var(--color-orange-dark);
        border-color: var(--color-orange-dark);
        color: #fff;
    }
</style>

<script>
    // This form's submission was relying on a generic ".form-submit-event" handler that
    // only exists in the admin JS bundle (never loaded on seller pages), so clicking
    // "Add Product FAQ" previously did nothing. Wired up directly here instead.
    $(document).on('submit', '#add_product_faq_form', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.append(csrfName, csrfHash);
        var submitBtn = $('#submit_btn');
        var originalText = submitBtn.html();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            beforeSend: function () {
                submitBtn.html('Please Wait...').prop('disabled', true);
                $('#error_box').html('');
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
                    $('#error_box').html('<span class="text-danger">' + result.message + '</span>');
                } else {
                    iziToast.success({ message: result.message });
                    setTimeout(function () {
                        window.location.href = "<?= base_url('seller/product_faqs') ?>";
                    }, 1000);
                }
            },
            error: function () {
                $('#error_box').html('<span class="text-danger">Something went wrong. Please try again.</span>');
            },
            complete: function () {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
</script>
