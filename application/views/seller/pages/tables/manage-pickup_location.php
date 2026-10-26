<link rel="stylesheet" href="<?= base_url('assets/seller/css/cretzo/pickup-location.css') ?>?v=<?= @filemtime(FCPATH . 'assets/seller/css/cretzo/pickup-location.css') ?: time() ?>">
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h4>Pickup Locations</h4></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('seller/home') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Pickup Location</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="czpl">
                <div class="czpl-head">
                    <h1>Manage Pickup Locations</h1>
                    <p>Warehouses and shops where couriers collect your orders. Add one here, then verify it on Shiprocket.</p>
                </div>

                <div class="czpl-card">
                    <div class="czpl-card-head">
                        <span class="czpl-ico"><i class="fa fa-map-marker-alt"></i></span>
                        <h2><?= isset($fetched_data[0]['id']) ? 'Edit Pickup Location' : 'Add Pickup Location' ?></h2>
                    </div>
                    <form class="form-submit-event" action="<?= base_url('seller/pickup_location/add_pickup_location'); ?>" method="POST" id="add_pickup_location_form" enctype="multipart/form-data">
                        <?php if (isset($fetched_data[0]['id'])) { ?>
                            <input type="hidden" id="edit_pickup_location" name="edit_pickup_location" value="<?= (int) $fetched_data[0]['id'] ?>">
                            <input type="hidden" id="update_id" name="update_id" value="1">
                        <?php } ?>
                        <div class="czpl-card-body">
                            <div class="czpl-grid">
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="pickup_location">Pickup Location <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="pickup_location" id="pickup_location" placeholder="Nickname, max 36 characters" value="<?= (isset($fetched_data[0]['pickup_location']) ? htmlspecialchars($fetched_data[0]['pickup_location']) : '') ?>">
                                </div>
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="name">Name <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="name" id="name" placeholder="Shipper's name" value="<?= (isset($fetched_data[0]['name']) ? htmlspecialchars($fetched_data[0]['name']) : '') ?>">
                                </div>
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="email">Email <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="email" id="email" placeholder="Shipper's email address" value="<?= (isset($fetched_data[0]['email']) ? htmlspecialchars($fetched_data[0]['email']) : '') ?>">
                                </div>
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="phone">Phone <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="phone" id="phone" placeholder="Shipper's phone number" value="<?= (isset($fetched_data[0]['phone']) ? htmlspecialchars($fetched_data[0]['phone']) : '') ?>">
                                </div>
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="city">City <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="city" id="city" placeholder="City" value="<?= (isset($fetched_data[0]['city']) ? htmlspecialchars($fetched_data[0]['city']) : '') ?>">
                                </div>
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="state">State <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="state" id="state" placeholder="State" value="<?= (isset($fetched_data[0]['state']) ? htmlspecialchars($fetched_data[0]['state']) : '') ?>">
                                </div>
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="country">Country <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="country" id="country" placeholder="Country" value="<?= (isset($fetched_data[0]['country']) ? htmlspecialchars($fetched_data[0]['country']) : '') ?>">
                                </div>
                                <div class="czpl-field ">
                                    <label class="czpl-label" for="pincode">Pincode <span class="req">*</span></label>
                                    <input type="text" class="czpl-input" name="pincode" id="pincode" placeholder="Pincode" value="<?= (isset($fetched_data[0]['pin_code']) ? htmlspecialchars($fetched_data[0]['pin_code']) : '') ?>">
                                </div>
                                <div class="czpl-field czpl-span-2">
                                    <label class="czpl-label" for="address">Address <span class="req">*</span></label>
                                    <textarea class="czpl-input" name="address" id="address" placeholder="Shipper's primary address, max 80 characters"><?= (isset($fetched_data[0]['address']) ? htmlspecialchars($fetched_data[0]['address']) : '') ?></textarea>
                                    <p class="czpl-hint">Must include a house/flat/shop no. and road/street name (e.g. &quot;12, Jamia Nagar Road&quot;) - Shiprocket rejects addresses without one.</p>
                                </div>
                                <div class="czpl-field czpl-span-2">
                                    <label class="czpl-label" for="address2">Address 2</label>
                                    <textarea class="czpl-input" name="address2" id="address2" placeholder="Additional address details"><?= (isset($fetched_data[0]['address_2']) ? htmlspecialchars($fetched_data[0]['address_2']) : '') ?></textarea>
                                </div>
                                <div class="czpl-field czpl-span-2">
                                    <label class="czpl-label" for="latitude">Latitude</label>
                                    <input type="text" class="czpl-input" name="latitude" id="latitude" placeholder="Latitude (optional)" value="<?= (isset($fetched_data[0]['latitude']) ? htmlspecialchars($fetched_data[0]['latitude']) : '') ?>">
                                </div>
                                <div class="czpl-field czpl-span-2">
                                    <label class="czpl-label" for="longitude">Longitude</label>
                                    <input type="text" class="czpl-input" name="longitude" id="longitude" placeholder="Longitude (optional)" value="<?= (isset($fetched_data[0]['longitude']) ? htmlspecialchars($fetched_data[0]['longitude']) : '') ?>">
                                </div>
                            </div>
                            <div class="czpl-actions">
                                <button type="submit" class="czpl-btn czpl-btn-primary" id="submit_btn"><?= (isset($fetched_data[0]['id'])) ? 'Update Pickup Location' : 'Add Pickup Location' ?></button>
                                <button type="reset" class="czpl-btn czpl-btn-ghost">Reset</button>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="form-group d-none" id="error_box"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="czpl-card">
                    <div class="czpl-card-head">
                        <span class="czpl-ico"><i class="fa fa-warehouse"></i></span>
                        <h2>Your Pickup Locations</h2>
                    </div>
                    <div class="czpl-table-wrap">
                        <table id="pickup_locations_table" data-toggle="table" data-url="<?= base_url('seller/pickup_location/view_pickup_location') ?>" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="asc" data-toolbar="" data-show-export="true" data-export-types='["txt","excel"]' data-export-options='{"fileName": "pickup-locations", "ignoreColumn": ["operate"]}' data-maintain-selected="true" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">ID</th>
                                    <th data-field="seller_id" data-sortable="true" data-visible='false'>Seller ID</th>
                                    <th data-field="pickup_location" data-sortable="true">Location</th>
                                    <th data-field="name" data-sortable="true">Name</th>
                                    <th data-field="email" data-sortable="true" data-visible='false'>Email</th>
                                    <th data-field="phone" data-sortable="true">Phone</th>
                                    <th data-field="address">Address</th>
                                    <th data-field="address2" data-visible='false'>Address 2</th>
                                    <th data-field="city" data-sortable="true">City</th>
                                    <th data-field="pin_code" data-sortable="true">Pincode</th>
                                    <th data-field="approval">Status</th>
                                    <th data-field="operate">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

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

<script>
    // Below 768px the rows render as cards (see pickup-location.css); each cell needs its
    // column title as a data-label for the ::before caption. Read from the live <th>s so it
    // follows the column chooser (hidden columns have no cell and no header).
    $('#pickup_locations_table').on('post-body.bs.table', function () {
        var $table = $(this);
        var labels = [];
        $table.find('thead th').each(function () {
            labels.push($.trim($(this).find('.th-inner').text()));
        });
        $table.find('tbody tr').each(function () {
            $(this).find('td').each(function (i) {
                $(this).attr('data-label', labels[i] || '');
            });
        });
    });

    // queryParams normally comes from the admin-only custom.js, which seller pages never
    // load — same reason the .form-submit-event handler below has to live here.
    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search
        };
    }

    // Without this the form fell through to a plain browser POST and the controller's JSON
    // response was rendered as the whole page instead of an inline error / toast.
    $('#add_pickup_location_form').on('submit', function (e) {
        e.preventDefault();

        var error_box = $('#error_box');
        var submit_btn = $('#submit_btn');
        var button_text = submit_btn.html();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize() + '&' + csrfName + '=' + csrfHash,
            beforeSend: function () {
                // Cleared on every attempt - the old block stayed on screen after a retry, so a
                // stale rejection looked like it applied to the address just typed.
                error_box.addClass('d-none').empty();
                submit_btn.html('Please Wait..').prop('disabled', true);
            },
            success: function (response) {
                if (response.csrfName && response.csrfHash) {
                    csrfName = response.csrfName;
                    csrfHash = response.csrfHash;
                }
                if (response.error) {
                    // One toast only. The same text used to be printed into the page as well,
                    // so every failure appeared twice and the page copy never went away.
                    iziToast.error({ message: $('<div>').html(response.message).text(), position: 'topRight', timeout: 6000 });
                } else {
                    error_box.addClass('d-none');
                    iziToast.success({ message: response.message, position: 'topRight' });
                    $('#pickup_locations_table').bootstrapTable('refresh');
                    setTimeout(function () {
                        location.href = base_url + 'seller/pickup_location/manage_pickup_locations';
                    }, 1000);
                }
            },
            error: function () {
                iziToast.error({ message: 'Something went wrong. Please try again.', position: 'topRight' });
            },
            complete: function () {
                submit_btn.html(button_text).prop('disabled', false);
            }
        });
    });

    // The Deactivate and Delete buttons on each row have no handler for the same reason the
    // form submit above needs one: seller pages don't load admin custom.js, which is where
    // .update_active_status and generic delete buttons are normally wired up. Following the
    // pattern already used in manage-product.php for the same gap.
    $(document).on('click', '.update_active_status', function () {
        var id = $(this).data('id');
        var table = $(this).data('table');
        var status = $(this).data('status');
        $.ajax({
            type: 'GET',
            url: base_url + 'seller/home/update_status',
            data: { id: id, table: table, status: status },
            dataType: 'json'
        }).done(function (response) {
            if (response.csrfName && response.csrfHash) {
                csrfName = response.csrfName;
                csrfHash = response.csrfHash;
            }
            if (response.error === false) {
                iziToast.success({ message: '<span style="text-transform:capitalize">' + response.message + '</span> Status Updated' });
            } else {
                iziToast.error({ message: response.message });
            }
            $('#pickup_locations_table').bootstrapTable('refresh');
        }).fail(function () {
            iziToast.error({ message: 'Something went wrong. Please try again.' });
        });
    });

    $(document).on('click', '.delete-pickup-location', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Are You Sure!',
            text: "You won't be able to revert this!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                type: 'GET',
                url: base_url + 'seller/pickup_location/delete_pickup_location',
                data: { id: id },
                dataType: 'json'
            }).done(function (response) {
                if (response.error === false) {
                    Swal.fire('Deleted!', response.message, 'success');
                } else {
                    Swal.fire('Oops...', response.message, 'error');
                }
                $('#pickup_locations_table').bootstrapTable('refresh');
            }).fail(function () {
                Swal.fire('Oops...', 'Something went wrong!', 'error');
            });
        });
    });
</script>