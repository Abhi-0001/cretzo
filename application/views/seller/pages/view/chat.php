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
                                <?php if (!empty($whatsapp_status) && !empty($whatsapp_number)) : ?>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp_number) ?>" target="_blank" class="btn btn-success btn-lg px-5 py-3" style="border-radius: 50px; font-size: 17px;">
                                        <i class="fab fa-whatsapp mr-2"></i> Continue to WhatsApp
                                    </a>
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
