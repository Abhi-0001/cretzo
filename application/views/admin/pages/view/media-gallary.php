<div class="content-wrapper admin-media-gallery-page">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-photo-video mr-2 text-primary-theme"></i>Media Library</h4>
                    <p class="text-muted mb-0 small">Every file uploaded across the admin panel, in one place.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Media</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card attribute-card mb-3">
                <div class="card-header attribute-card-header">
                    <span class="header-icon bg-set mr-2"><i class="fas fa-upload"></i></span>
                    <h5 class="mb-0">Upload Files</h5>
                </div>
                <div class="card-body">
                    <!-- Page-scoped ids, not the shared #dropzone/#upload-files-btn used by
                         admin/include-footer.php's #media-upload-modal - that modal is included
                         on every admin page (including this one), so reusing its ids here meant
                         two elements sharing the same id existed simultaneously on this specific
                         page (invalid HTML; only harmless today because nothing on this page
                         ever opens that modal, so its copy is fully inert). -->
                    <div id="media-gallery-dropzone" class="dropzone"></div>
                    <br>
                    <a href="" id="media-gallery-upload-btn" class="btn btn-primary-theme float-right">Upload</a>
                </div>
            </div>

            <div class="card attribute-card">
                <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <span class="header-icon bg-set mr-2"><i class="fas fa-images"></i></span>
                        <h5 class="mb-0">Files</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row product-filters-bar align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="filter-label">Date Range</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" class="form-control" autocomplete="off" id="datepicker">
                                <input type="hidden" id="start_date">
                                <input type="hidden" id="end_date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="filter-label">Media Type</label>
                            <select class="form-control" id="media-type">
                                <option value="">All Media Items</option>
                                <option value="image">Images</option>
                                <option value="audio">Audio</option>
                                <option value="video">Video</option>
                                <option value="archive">Archive</option>
                                <option value="spreadsheet">Spreadsheet</option>
                                <option value="documents">Documents</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary-theme btn-sm mr-2" onclick="status_date_wise_search()">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetfilters()">Reset</button>
                        </div>
                    </div>

                    <table class='table-striped' id='media-table' data-page-size="5" data-toggle="table"
                        data-url="<?= base_url('admin/media/fetch') ?>" data-side-pagination="server" data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                        data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                        data-mobile-responsive="true" data-toolbar="" data-show-export="true"
                        data-query-params="mediaUploadParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true" data-visible='false' data-align='center'>ID</th>
                                <th data-field="seller_id" data-sortable="true" data-visible='false' data-align='center'>Seller ID</th>
                                <th data-field="name" data-sortable="true" data-align='center'>Name</th>
                                <th data-field="image" data-sortable="false" data-align='center'>Image</th>
                                <th data-field="extension" data-sortable="false" data-align='center'>Extension</th>
                                <th data-field="sub_directory" data-sortable="true" data-align='center'>Sub directory</th>
                                <th data-field="size" data-sortable="true" data-align='center'>Size</th>
                                <th data-field="operate" data-sortable="false" data-align='center'>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    .admin-media-gallery-page .text-primary-theme { color: var(--color-orange); }

    .admin-media-gallery-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-media-gallery-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-media-gallery-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-media-gallery-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-media-gallery-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-media-gallery-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-media-gallery-page .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--color-grey);
        margin-bottom: 6px;
    }
    .admin-media-gallery-page .form-control:focus { border-color: var(--color-orange); box-shadow: 0 0 0 .15rem var(--color-orange-light); }

    .admin-media-gallery-page .dropzone { border: 2px dashed rgba(0,0,0,0.15); border-radius: 10px; background: #fbfbfb; min-height: 120px; }
    .admin-media-gallery-page .dropzone:hover { border-color: var(--color-orange); background: var(--color-orange-light); }

    .admin-media-gallery-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-media-gallery-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-media-gallery-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-media-gallery-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-media-gallery-page table.table thead th {
        background: #fafafa;
        border-top: none;
        border-bottom: 2px solid rgba(0,0,0,0.06);
        color: var(--color-grey);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .admin-media-gallery-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-media-gallery-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-media-gallery-page .fixed-table-pagination .pagination .page-item.active .page-link { color: #fff; background-color: var(--color-orange); border-color: var(--color-orange); }
    .admin-media-gallery-page .fixed-table-pagination .pagination .page-link { color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08); }

    .admin-media-gallery-page td:has(.action-btn) { white-space: nowrap; }
    .admin-media-gallery-page .action-btn { display: inline-block; vertical-align: middle; }
</style>
