<div class="content-wrapper">
    <div class="main-wrapper main-wrapper-1">
        <section class="content">
            <div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 150px);">
                <div class="row justify-content-center w-100">
                    <div class="col-12 col-md-10 col-lg-8 col-xl-7">
                        <div class="card shadow-sm border-0" style="border-radius: 20px;">
                            <div class="card-body text-center px-5 py-5">
                                <div class="d-inline-flex align-items-center justify-content-center mb-4" style="width: 110px; height: 110px; border-radius: 50%; background: rgba(255, 193, 7, 0.12);">
                                    <i class="fas fa-comments text-warning" style="font-size: 54px;"></i>
                                </div>
                                <h2 class="font-weight-bold mb-3">Chat Coming Soon</h2>
                                <p class="text-muted mb-5" style="font-size: 16px; max-width: 480px; margin-left: auto; margin-right: auto;">
                                    We're working hard on bringing in-app chat to you. In the meantime, reach out to our support team directly on WhatsApp for any help.
                                </p>
                                <?php
                                /*
                                 * This used to read $whatsapp_number straight from the settings row and hide
                                 * itself when the field was blank - which it was, so sellers only ever saw
                                 * "WhatsApp support is currently unavailable". whatsapp_support_link() falls
                                 * back through the configured support numbers to the confirmed one, so the
                                 * button is there whatever state the settings row is in.
                                 */
                                $whatsapp_link = whatsapp_support_link('Hello Cretzo Support, I need help with my seller account.');
                                ?>
                                <?php if (!empty($whatsapp_link)) : ?>
                                    <a href="<?= html_escape($whatsapp_link) ?>" target="_blank" rel="noopener" class="btn btn-success btn-lg px-5 py-3" style="border-radius: 50px; font-size: 17px;">
                                        <i class="fab fa-whatsapp mr-2"></i> Continue to WhatsApp
                                    </a>
                                    <p class="text-muted mt-3 mb-0" style="font-size: 14px;">
                                        or call us on <a href="tel:+<?= html_escape(support_whatsapp_number()) ?>" class="text-muted"><?= html_escape(support_whatsapp_number()) ?></a>
                                    </p>
                                <?php else : ?>
                                    <p class="text-muted mb-0"><em>WhatsApp support is currently unavailable. Please contact the admin for assistance.</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div><!-- /.container-fluid -->
