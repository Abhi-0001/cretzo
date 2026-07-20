<section class="container mb-15 mt-md-5">
    <div class="category-section container-fluid text-center">
        <div class='my-4 featured-section-title'>
            <div class='col-md-12'>
                <h3 class='section-title'><?= !empty($this->lang->line('category')) ? $this->lang->line('category') : 'Browse Categories' ?></h3>
            </div>
            <hr class="mt-6 mb-6">
        </div>
        <div class="row">
            <?php foreach ($categories as $key => $row) { ?>
                <div class="col-md-2 col-sm-6 mb-6">
                    <div class="category-image justify-content-center d-flex">
                        <div class="category-image-container">
                            <a href="<?= base_url('products/category/' . html_escape($row['slug'])) ?>">
                                <img src="<?= $row['image'] ?>">
                            </a>
                            <div class="">
                                <span><?= html_escape($row['name']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ((!isset($categories) || empty($categories))) { ?>
                <div class="col-12 text-center">
                    <h1 class="h2"><?= !empty($this->lang->line('no_category_found')) ? $this->lang->line('no_category_found') : 'No Categories Found.' ?></h1>
                    <a href="<?= base_url('products') ?>" class="btn btn-sm rounded-pill btn-warning"><?= !empty($this->lang->line('go_to_shop')) ? $this->lang->line('go_to_shop') : 'Go to Shop' ?></a>
                </div>
            <?php } ?>
        </div>
    </div>
</section>