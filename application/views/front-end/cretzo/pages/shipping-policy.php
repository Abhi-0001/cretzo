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
                    <li class="breadcrumb-item active text-muted" aria-current="page"><?= !empty($this->lang->line('shipping_policy')) ? $this->lang->line('shipping_policy') : 'Shipping Policy' ?></li>
                </ol>
            </nav>
            <!-- /nav -->
        </div>
        <!-- /.container -->
    </section>
</div>
<!-- end breadcrumb -->

<section class="container main-content mb-15 my-4">
    <div class="text-center">
        <h1 class="display-2"><?= !empty($this->lang->line('shipping_policy')) ? $this->lang->line('shipping_policy') : 'Shipping Policy' ?></h1>
    </div>
    <?php // .policy-content scopes the prose styling for these static pages (see
          // cretzo-override.css). The theme's global `hr { margin: 4.5rem 0 }` is meant for
          // full-page section dividers, but these policies use <hr> as a rule between
          // numbered clauses - 11 of them in the shipping policy, 25 in the terms - which
          // added 144px of blank space per divider and left the pages full of huge gaps.
          //
          // Also no longer wrapped in a <p>: the stored content is block HTML, which is
          // invalid inside a paragraph and left two empty paragraphs behind. And no longer
          // .text-justify - justifying prose across the full container width stretched the
          // spaces between words into visible rivers. ?>
    <div class="hrDiv policy-content">
        <?= $shipping_policy ?>
    </div>
</section>
