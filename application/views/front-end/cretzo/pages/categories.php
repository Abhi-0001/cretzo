<section class="home-part-container">

    <h1 class="heading-b container-heading"><?= !empty($this->lang->line('category')) ? $this->lang->line('category') : 'Shop by Catagories' ?></h1>
    <p class="text-n op-6 container-des"><?= !empty($this->lang->line('looking_for_something_specific')) ? $this->lang->line('looking_for_something_specific') : 'Looking for something specific ?' ?></p>

    <!-- container of card type seven, categories (same design as home page 'Shop by Catagories') -->
    <?php if ((isset($categories) && !empty($categories))) { ?>
        <div class="card-container-two">
            <?php foreach ($categories as $key => $row) { ?>
                <div class="cretzo-card card-type-seven">
                    <a class="card-url" href="<?= base_url('products/category/' . html_escape($row['slug'])) ?>"></a>
                    <div class="card-img">
                        <img class="card-img-img lazy" src="<?= base_url('assets/front_end/cretzo/img/product-placeholder.jpg') ?>" data-src="<?= $row['image'] ?>" alt="<?= html_escape($row['name']) ?>" />
                    </div>
                    <div class="card-des">
                        <h1 class="ta-c text-n"><?= output_escaping(str_replace('\r\n', '&#13;&#10;', $row['name'])) ?></h1>
                        <p class="ta-c text-s">Shop Now></p>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="col-12 text-center my-5">
            <h1 class="h2"><?= !empty($this->lang->line('no_category_found')) ? $this->lang->line('no_category_found') : 'No Categories Found.' ?></h1>
            <a href="<?= base_url('products') ?>" class="btn btn-sm rounded-pill btn-warning"><?= !empty($this->lang->line('go_to_shop')) ? $this->lang->line('go_to_shop') : 'Go to Shop' ?></a>
        </div>
    <?php } ?>

</section>
