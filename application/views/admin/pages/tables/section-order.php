<div class="content-wrapper admin-section-order-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-sort mr-2 text-primary-theme"></i>Featured Section Display Order</h4>
                    <p class="text-muted mb-0 small">Drag to set the order featured sections appear on the storefront.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/featured-sections') ?>">Featured Sections</a></li>
                        <li class="breadcrumb-item active">Display Order</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set"><i class="fas fa-list"></i></span>
                        <h5 class="mb-0">Sections <span class="text-muted font-weight-normal">(<?= count($section_result ?? []) ?>)</span></h5>
                    </div>
                    <button type="button" class="btn btn-primary-theme btn-sm" id="save_section_order">
                        <i class="fas fa-save mr-1"></i>Save Order
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($section_result)) { ?>
                        <div class="row section-order-header font-weight-bold mb-2 d-none d-md-flex">
                            <div class="col-md-2 text-center">Order</div>
                            <div class="col-md-10">Title</div>
                        </div>

                        <ul class="list-group order-container" id="sortable">
                            <?php foreach ($section_result as $row) { ?>
                                <li class="list-group-item section-order-item d-flex align-items-center" id="section_id-<?= (int) $row['id'] ?>">
                                    <div class="col-md-1 col-2 text-center"><i class="fas fa-grip-vertical section-order-handle"></i></div>
                                    <div class="col-md-11 col-10 section-order-title"><?= html_escape($row['title']) ?></div>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="section-order-state">
                            <i class="fas fa-box-open"></i>
                            <p>No featured sections exist.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-section-order-page .text-primary-theme { color: var(--color-orange); }

    .admin-section-order-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-section-order-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-section-order-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-section-order-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-section-order-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-section-order-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-section-order-page .section-order-header { color: var(--color-grey); font-size: 13px; text-transform: uppercase; letter-spacing: .3px; }

    .admin-section-order-page .section-order-state {
        padding: 50px 0;
        text-align: center;
        color: var(--color-grey);
    }
    .admin-section-order-page .section-order-state i { font-size: 30px; margin-bottom: 10px; display: block; opacity: .5; }
    .admin-section-order-page .section-order-state p { margin: 0; }

    .admin-section-order-page .order-container { max-height: 65vh; overflow-y: auto; }
    .admin-section-order-page .section-order-item {
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        margin-bottom: 8px;
        padding: 10px 12px;
        background: #fff;
        cursor: grab;
    }
    .admin-section-order-page .section-order-item:active { cursor: grabbing; }
    .admin-section-order-page .section-order-item.ui-sortable-helper { box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
    .admin-section-order-page .section-order-placeholder {
        border: 2px dashed var(--color-orange);
        border-radius: 8px;
        margin-bottom: 8px;
        background: var(--color-orange-light);
    }
    .admin-section-order-page .section-order-handle { color: var(--color-grey); margin-right: 4px; }
    .admin-section-order-page .section-order-title { font-size: 14px; font-weight: 500; }
</style>
