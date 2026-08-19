<!-- breadcrumb -->
<div class="content-wrapper">
    <section class="wrapper bg-soft-grape">
        <div class="container py-3 py-md-5">
            <nav class="d-inline-block" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent">
                    <li class="breadcrumb-item"><a href="<?= base_url() ?>" class="text-decoration-none"><?= !empty($this->lang->line('home')) ? $this->lang->line('home') : 'Home' ?></a></li>
                    <?php if (isset($right_breadcrumb) && !empty($right_breadcrumb)) {
                        foreach ($right_breadcrumb as $row) {
                    ?>
                            <li class="breadcrumb-item"><?= $row ?></li>
                    <?php }
                    } ?>
                    <li class="breadcrumb-item text-muted" aria-current="page"><?= !empty($this->lang->line('blogs')) ? $this->lang->line('blogs') : 'Blogs' ?></li>
                    <li class="breadcrumb-item active text-muted" aria-current="page"><?= html_escape($blog[0]['title']) ?></li>
                </ol>
            </nav>
            <!-- /nav -->
        </div>
        <!-- /.container -->
    </section>
</div>
<!-- end breadcrumb -->


<section class="listing-page content main-content">
    <div class="product-listing card-solid py-4">
        <div class="container mb-15 pt-3">
            <div class="row w-100">
                <!-- <div class="col-md-10"> -->
                    <div class="card">
                        <div class="blog-card-img">
                            <a href="#">
                                <?php /* Was a bare base_url() . $blog[0]['image'] - an empty or
                                   deleted image path produced a broken image (or, when empty,
                                   requested the site root as an image). get_image_url() resolves
                                   the generated thumb and falls back to the placeholder, which is
                                   what every admin-side render of this same column already does. */ ?>
                                <img src="<?= get_image_url($blog[0]['image'], 'thumb', 'md') ?>" alt="<?= html_escape($blog[0]['title']) ?>">
                            </a>
                        </div>
                        <div class="card-body">
                            <h2 class="view-blog-title mb-2 mt-2"><?= html_escape($blog[0]['title']) ?></h2>
                            <p class="card-text mt-5"><?= str_replace('\r\n', '&#13;&#10;', $blog[0]['description']) ?></p>
                        </div>
                    </div>
                <!-- </div> -->
            </div>
        </div>
    </div>
</section>