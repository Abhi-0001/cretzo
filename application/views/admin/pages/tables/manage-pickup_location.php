<div class="content-wrapper admin-pickup-location-page">
    <!-- Content Header (Page header) -->
    <!-- Main content -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h4 class="mb-0"><i class="fas fa-map-marker-alt mr-2 text-primary-theme"></i>Pickup Locations</h4>
                    <p class="text-muted mb-0 small">Seller pickup addresses used for shipment collection.</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Pickup Location</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row d-none">
                <div class="col-md-12">
                    <div class="card attribute-card">
                        <div class="card-header attribute-card-header d-flex align-items-center">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-map-marker-alt"></i></span>
                            <h5 class="mb-0">Pickup Location Form</h5>
                        </div>

                        <form class="form-horizontal form-submit-event" action="<?= base_url('admin/pickup_location/add_pickup_location'); ?>" method="POST" id="add_product_form" enctype="multipart/form-data">
                            <?php
                            if (isset($fetched_data[0]['id'])) {
                            ?>
                                <input type="hidden" id="edit_pickup_location" name="edit_pickup_location" value="<?= @$fetched_data[0]['id'] ?>">
                                <input type="hidden" id="update_id" name="update_id" value="1">
                                <input type="hidden" name="seller_id" value="<?= (isset($fetched_data[0]['seller_id']) ? $fetched_data[0]['seller_id'] : '') ?>">
                            <?php
                            }
                            ?>

                            <div class="card-body">
                                <div class="form-group row">
                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Pickup Location <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="pickup_location" placeholder="The nickname of the new pickup location. Max 36 characters." id="pickup_location" value="<?= (isset($fetched_data[0]['pickup_location'])) ? html_escape($fetched_data[0]['pickup_location']) : '' ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Name <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="name" placeholder="The shipper's name." id="name" value="<?= (isset($fetched_data[0]['name'])) ? html_escape($fetched_data[0]['name']) : '' ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Email <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="email" placeholder="The shipper's email address." id="email" value="<?= (isset($fetched_data[0]['email'])) ? html_escape($fetched_data[0]['email']) : '' ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Phone <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="phone" placeholder="Shipper's phone number." id="phone" value="<?= (isset($fetched_data[0]['phone'])) ? html_escape($fetched_data[0]['phone']) : '' ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">City <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="city" placeholder="Pickup location city name." id="city" value="<?= (isset($fetched_data[0]['city'])) ? html_escape($fetched_data[0]['city']) : '' ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">State <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="state" placeholder="Pickup location state name." id="state" value="<?= (isset($fetched_data[0]['state'])) ? html_escape($fetched_data[0]['state']) : '' ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Country <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="country" placeholder="Pickup location country." id="country" value="<?= (isset($fetched_data[0]['country'])) ? html_escape($fetched_data[0]['country']) : '' ?>">
                                    </div>

                                    <div class="col-3">
                                        <label for="area_name" class="control-label col-md-12">Pincode <span class='text-danger text-xs'>*</span></label>
                                        <input type="text" class="form-control" name="pincode" placeholder="Pickup location pincode." id="pincode" value="<?= (isset($fetched_data[0]['pin_code'])) ? html_escape($fetched_data[0]['pin_code']) : '' ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-6">
                                        <label for="area_name" class="control-label col-md-12">Address <span class='text-danger text-xs'>*</span></label>
                                        <textarea class="form-control" name="address" placeholder="Shipper's primary address. Max 80 characters." id="address"><?= (isset($fetched_data[0]['address'])) ? html_escape($fetched_data[0]['address']) : '' ?></textarea>
                                        <small class="form-text text-muted">Must include a house/flat/shop no. and road/street name (e.g. "12, Jamia Nagar Road") - Shiprocket rejects addresses without one.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="area_name" class="control-label col-md-12">Address 2 </label>
                                        <textarea class="form-control" name="address2" placeholder="Additional address details." id="address2"><?= (isset($fetched_data[0]['address_2'])) ? html_escape($fetched_data[0]['address_2']) : '' ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-6">
                                        <label for="area_name" class="control-label col-md-12">Latitude</span></label>
                                        <input type="text" class="form-control" name="latitude" placeholder="Pickup location Latitude." id="latitude" value="<?= (isset($fetched_data[0]['latitude'])) ? html_escape($fetched_data[0]['latitude']) : '' ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="area_name" class="control-label col-md-12">Longitude</span></label>
                                        <input type="text" class="form-control" name="longitude" placeholder="Pickup location Longitude." id="longitude" value="<?= (isset($fetched_data[0]['longitude'])) ? html_escape($fetched_data[0]['longitude']) : '' ?>">
                                    </div>
                                </div>
                                <div class="form-group row"></div>
                                <div class="form-group row">
                                    <div>
                                        <button type="reset" class="btn btn-warning">Reset</button>
                                        <button type="submit" class="btn btn-success" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Pickup Location' : 'Add Pickup Location' ?></button>
                                    </div>
                                </div>
                            </div>
                    </div>
                    <div class="d-flex justify-content-center">
                        <div class="form-group" id="error_box">
                        </div>
                    </div>
                    </form>
                </div>

            </div>
            <div class="modal fade edit-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">Edit Pickup Location</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row pickup-main">
                <div class="col-md-12 main-content d-flex">
                <div class="card attribute-card">
                    <div class="card-header attribute-card-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex align-items-center">
                            <span class="header-icon bg-set mr-2"><i class="fas fa-map-marker-alt"></i></span>
                            <h5 class="mb-0">Pickup Location Details</h5>
                        </div>
                        <button type="button" class="btn btn-primary-theme btn-sm" data-toggle="modal" data-target="#verifyPickupLocations">
                            <i class="fas fa-check-circle mr-1"></i>Need to verify the pickup Locations
                        </button>
                    </div>
                    <div class="card-body pt-3">
                        <div class="gaps-1-5x"></div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label for="zipcode" class="col-form-label">Filter By Seller</label>

                                <select class='form-control' name='seller_id' id="seller_filter">
                                    <option value="">Select Seller </option>
                                    <?php foreach ($sellers as $seller) { ?>
                                        <option value="<?= $seller['seller_id'] ?>" <?= (isset($product_details[0]['seller_id']) && $product_details[0]['seller_id'] == $seller['seller_id']) ? 'selected' : "" ?>><?= $seller['seller_name'] . ' ' . '-' . ' ' . $seller['store_name'] . ' ' . '(store)' ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <table class='table-striped' id='pickup_location_table' data-toggle="table" data-url="<?= base_url('admin/pickup_location/view_pickup_location') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-size="10" data-page-list="[10, 25, 50, 100]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{
                        "fileName": "area-list",
                        "ignoreColumn": ["operate"]
                        }' data-maintain-selected="true" data-query-params="product_query_params">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="seller_id" data-sortable="true">Seller ID</th>
                                    <th data-field="pickup_location" data-sortable="true">Pickup Locations</th>
                                    <th data-field="name" data-sortable="true">Name</th>
                                    <th data-field="email" data-sortable="true">Email</th>
                                    <th data-field="phone" data-sortable="true">Phone</th>
                                    <th data-field="address">Address</th>
                                    <th data-field="address2">Address 2</th>
                                    <th data-field="city" data-sortable="true">City</th>
                                    <th data-field="pin_code" data-sortable="true">Pincode</th>
                                    <th data-field="verified">Verified</th>
                                    <th data-field="operate">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div><!-- .card-body -->
                </div><!-- .card -->
                </div><!-- /.col -->
            </div><!-- /.row -->
</div><!-- /.container-fluid -->
</section>
<!-- /.content -->
</div>

<style>
    .admin-pickup-location-page .text-primary-theme { color: var(--color-orange); }

    /* ============================ page height ============================
       This page used to be pinned to the viewport: height: calc(100vh - 57px) with
       overflow: hidden on the wrapper, a chain of flex parents down to the table, and only
       .fixed-table-body allowed to scroll. It did not hold up:

         - bootstrap-table builds its own wrappers and puts .fixed-table-pagination INSIDE
           them, so the pager ended up below the clipped box - at a 800px viewport it sat at
           y=917 inside a container cut off at y=848, i.e. off screen with no way to reach it.
         - .fixed-table-body never actually became a scroll container (scrollHeight ==
           clientHeight), so the rows that did not fit were simply cut off: overflow: hidden
           on the wrapper means no scrollbar anywhere. Choosing a page size of 25/50/100 hid
           most of the list.
         - the flex rules matched BOTH .attribute-card blocks on this page, including the
           hidden form card, so the two competed for the same height.

       So the document scrolls again - every row and the pager are always reachable - and the
       table body is given a generous bounded height instead of a computed one. Inside that
       height the body scrolls both ways, with the column headers sticky above the rows; when
       the viewport is too short for even that, the page scroll takes over. No ancestor may
       carry overflow: hidden, or it becomes the sticky header's scrollport and the header
       stops sticking.
       ===================================================================== */
    .admin-pickup-location-page .attribute-card { width: 100%; }

    /* The spacer above the seller filter only added dead height, and the filter row's own
       top margin added 16px more - both come straight out of the space the rows and the
       pager have to share. */
    .admin-pickup-location-page .card-body > .gaps-1-5x { display: none; }
    .admin-pickup-location-page .card-body > .row.mt-3 { margin-top: 0 !important; }
    .admin-pickup-location-page .card-body > .row label { margin-bottom: 2px; font-size: 12.5px; font-weight: 600; }

    .admin-pickup-location-page .bootstrap-table,
    .admin-pickup-location-page .fixed-table-container {
        padding-bottom: 0 !important;
    }
    .admin-pickup-location-page .fixed-table-body {
        /* 486px is measured, not guessed: the navbar, page header, card header, seller
           filter and table toolbar stack to 402px above this element, and the pager plus its
           gap takes 76px below it (402 + 76 = 478, plus 8px of breathing room). So on a
           768-800px screen the sticky header, the rows and the pager are all on screen at
           once, and a taller screen simply gets more rows. min-height keeps it usable on a
           short screen, where the document scroll - which this page no longer suppresses -
           takes over. */
        max-height: calc(100vh - 486px);
        min-height: 260px;
        overflow: auto;
    }
    .admin-pickup-location-page .fixed-table-body thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fafafa;
    }
    /* Twelve columns, so the body scrolls sideways rather than squeezing every column. */
    .admin-pickup-location-page table.table { min-width: 1500px; }
    .admin-pickup-location-page table.table tbody td {
        padding: 8px 10px;
        vertical-align: middle;
        /* Everything except the two address columns is a short value that reads best on
           one line. */
        white-space: nowrap;
    }
    /* Address and Address 2 are shown IN FULL - no ellipsis. These are the values an admin
       is on this page to check against what the seller entered and what Shiprocket will
       collect from, and a truncated "R 370 jogabai extension new ..." cannot be verified.
       They wrap instead, and the row grows to fit; the bounded, scrollable body above is
       what keeps that from running off the screen. */
    .admin-pickup-location-page table.table thead th:nth-child(7),
    .admin-pickup-location-page table.table thead th:nth-child(8),
    .admin-pickup-location-page table.table tbody td:nth-child(7),
    .admin-pickup-location-page table.table tbody td:nth-child(8) {
        white-space: normal;
        min-width: 260px;
        max-width: 320px;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }
    /* Email stays on one line. Wrapping it broke mid-word ("test@test / .com"), which is
       harder to read than letting the body scroll sideways to it. */
    .admin-pickup-location-page table.table thead th:nth-child(5),
    .admin-pickup-location-page table.table tbody td:nth-child(5) { min-width: 190px; }
    /* The pager belongs to the document, not to the scrolling body. */
    .admin-pickup-location-page .fixed-table-pagination {
        flex: 0 0 auto;
        margin-top: 10px;
        padding-bottom: 4px;
    }

    @media (max-width: 991px) {
        .admin-pickup-location-page .fixed-table-body {
            max-height: none;
            min-height: 0;
        }
    }

    .admin-pickup-location-page .btn-primary-theme {
        background: var(--color-orange);
        border-color: var(--color-orange);
        color: #fff;
        font-weight: 600;
    }
    .admin-pickup-location-page .btn-primary-theme:hover { background: var(--color-orange-dark); border-color: var(--color-orange-dark); color: #fff; }

    .admin-pickup-location-page .attribute-card { border: none; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .admin-pickup-location-page .attribute-card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        gap: 10px;
        border-radius: 10px 10px 0 0;
    }
    .admin-pickup-location-page .header-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex: none;
    }
    .admin-pickup-location-page .header-icon.bg-set { background: var(--color-orange); }

    .admin-pickup-location-page .fixed-table-toolbar { margin-bottom: 10px; }
    .admin-pickup-location-page .fixed-table-toolbar > div { margin-left: 10px !important; }
    .admin-pickup-location-page .fixed-table-toolbar .btn-group > .btn,
    .admin-pickup-location-page .fixed-table-toolbar .btn-group > .keep-open { margin-left: 8px !important; }
    .admin-pickup-location-page .fixed-table-toolbar .btn-group > .btn:first-child,
    .admin-pickup-location-page .fixed-table-toolbar .btn-group > .keep-open:first-child { margin-left: 0 !important; }
    .admin-pickup-location-page .fixed-table-toolbar .search input {
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.12);
        padding: 6px 14px;
        box-shadow: none;
    }
    .admin-pickup-location-page .fixed-table-toolbar .search input:focus { border-color: var(--color-orange); }
    .admin-pickup-location-page .fixed-table-toolbar .columns .btn,
    .admin-pickup-location-page .fixed-table-toolbar .export .btn {
        border-radius: 20px;
        border-color: rgba(0,0,0,0.12);
        background: #fff;
        color: var(--color-grey);
    }
    .admin-pickup-location-page .fixed-table-toolbar .columns .btn:hover,
    .admin-pickup-location-page .fixed-table-toolbar .export .btn:hover { border-color: var(--color-orange); color: var(--color-orange); }

    .admin-pickup-location-page .fixed-table-container { border: none; }
    .admin-pickup-location-page table.table { border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
    .admin-pickup-location-page table.table thead th {
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
    .admin-pickup-location-page table.table tbody td { vertical-align: middle; font-size: 14px; border-top: 1px solid rgba(0,0,0,0.05); }
    .admin-pickup-location-page table.table tbody tr:hover { background-color: var(--color-orange-light); }
    .admin-pickup-location-page .action-btn { border-radius: 6px; }
    .admin-pickup-location-page .badge { font-size: 12px; padding: 5px 10px; border-radius: 20px; font-weight: 600; }

    .admin-pickup-location-page .fixed-table-pagination { margin-top: 12px; }
    .admin-pickup-location-page .fixed-table-pagination .pagination .page-item.active .page-link {
        color: #fff; background-color: var(--color-orange); border-color: var(--color-orange);
    }
    .admin-pickup-location-page .fixed-table-pagination .pagination .page-link {
        color: var(--color-orange-dark); border-radius: 6px; margin: 0 2px; border: 1px solid rgba(0,0,0,0.08);
    }
    .admin-pickup-location-page .fixed-table-pagination .page-list .btn { border-radius: 20px; }
</style>

<!-- Modal for verify the pickup Locations -->

<div class="modal fade" id="verifyPickupLocations" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Need to verify the pickup Locations</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ">
                <ol>
                    <li> After adding the pickup location you need to verify the pickup location on shiprocket dashboard.</li>
                    <li> Note: You can verify unverified pickup locations from <a href="https://app.shiprocket.in/company-pickup-location?redirect_url=" target="_blank">shiprocket dashboard </a>. New number in pickup location has to be verified once, Later additions of pickup locations with a same number will not require verification.</li>
                    <li> After verifying the pickup location in shiprocket, you need to verify that location in table.</li>
                    <li> You will find Verified column in pickup location table in this page.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
