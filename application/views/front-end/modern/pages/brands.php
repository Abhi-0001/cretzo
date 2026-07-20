<section class="container mb-15 mt-md-5">
    <div class="category-section container-fluid text-center">
        <div class='my-4 featured-section-title'>
            <div class='col-md-12'>
                <h3 class='section-title'><?= !empty($this->lang->line('brands')) ? $this->lang->line('brands') : 'Brands' ?></h3>
            </div>
            <hr class="mt-6 mb-6">
        </div>
        <div class="row">
            
            <?php foreach ($brands as $key => $row) { ?>
                <div class="col-md-2 col-sm-6 mb-6">
                    <div class="category-image justify-content-center d-flex">
                        <div class="category-image-container">
                            <a href="<?= base_url('products?brand=' . html_escape($row['brand_slug'])) ?>">
                                <img src="<?= base_url($row['brand_img']) ?>">
                            </a>
                            <div class="">
                                <span><?= html_escape($row['brand_name']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
        <nav class="text-center mt-14 d-flex overflow-auto" aria-label="pagination">
            <?= (isset($links)) ? $links : '' ?>
        </nav>
    </div>
</section>