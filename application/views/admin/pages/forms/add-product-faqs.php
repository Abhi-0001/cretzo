<div class="content-wrapper admin-add-product-faq-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-question-circle mr-2 text-primary-theme"></i>Add Product FAQ</h4>
                    <p class="text-muted mb-0 small">Pre-answer a common question for any product on the marketplace.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/product_faqs') ?>">Product FAQs</a></li>
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
                    <!-- form start -->
                    <form class="form-horizontal form-submit-event" action="<?= base_url('admin/product_faqs/add_faqs'); ?>" method="POST" enctype="multipart/form-data">
                        <div class="form-group row">
                            <label for="product_id" class="col-sm-2 col-form-label">Select Product <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <?php
                                // Previously tried to preload this dropdown from a $product_details
                                // variable the controller never set, which raised two PHP warnings
                                // ("Undefined variable $product_details" and "foreach() argument
                                // must be of type array|object, null given") that rendered directly
                                // on the page every time it loaded. There is nothing to preload here
                                // - the field searches the full marketplace catalog on demand via
                                // the select2 ajax handler already wired up for
                                // .search_admin_product in custom.js - so the dead loop is removed
                                // rather than the variable being reintroduced.
                                ?>
                                <select name="product_id" class="search_admin_product w-100" data-placeholder="Type to search and select a product"></select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="question" class="col-sm-2 col-form-label">Question <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="question" placeholder="Question" name="question">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="answer" class="col-sm-2 col-form-label">Answer <span class='text-danger text-sm'>*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="answer" placeholder="Answer" name="answer">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <button type="reset" class="btn btn-warning">Reset</button>
                            <button type="submit" class="btn btn-success" id="submit_btn">Add Product FAQ</button>
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
    .admin-add-product-faq-page .text-primary-theme { color: var(--color-orange); }

    .admin-add-product-faq-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-add-product-faq-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-add-product-faq-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px;
    }
    .admin-add-product-faq-page .header-icon.bg-set { background: var(--color-orange); }
    .admin-add-product-faq-page .form-control:focus {
        border-color: var(--color-orange);
        box-shadow: 0 0 0 .15rem var(--color-orange-light);
    }
    .admin-add-product-faq-page .btn-success {
        background: var(--color-orange);
        border-color: var(--color-orange);
        font-weight: 600;
    }
    .admin-add-product-faq-page .btn-success:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); }
</style>
